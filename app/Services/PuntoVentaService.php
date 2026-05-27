<?php
// app/Services/PuntoVentaService.php

namespace App\Services;

use App\Enums\RolSistema;
use App\Models\Parametro;
use App\Models\PuntoVenta;
use App\Models\SolicitudPuntoVenta;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Lógica de activación de puntos de venta (Art. 31, Art. 21).
 *
 * La resolución de identidad (cédula → correo → crear) se delega en
 * ResolutorCuentaService, compartido con AgenteParqueoService.
 */
class PuntoVentaService
{
    /**
     * @param  \App\Services\UsuarioService          $usuarioService  Creación del perfil de usuario.
     * @param  \App\Services\ResolutorCuentaService   $resolutor       Resolución de identidad de cuentas.
     */
    public function __construct(
        private UsuarioService $usuarioService,
        private ResolutorCuentaService $resolutor,
    ) {
    }

    /**
     * Genera el código correlativo del punto de venta (PV-0001, ...).
     *
     * @return string
     */
    public function generarCodigo(): string
    {
        $ultimo = PuntoVenta::withTrashed()->max('id') ?? 0;

        return 'PV-' . str_pad((string) ($ultimo + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Verifica la regla de "un punto de venta por cada tres cuadras" (Art. 31).
     *
     * @param  float     $lat        Latitud de la solicitud.
     * @param  float     $lng        Longitud de la solicitud.
     * @param  int|null  $exceptoId  ID a excluir (al reactivar uno existente).
     * @return bool                  true si existe un PV activo dentro de la distancia mínima.
     */
    public function existePuntoVentaCercano(float $lat, float $lng, ?int $exceptoId = null): bool
    {
        $minimo = (float) Parametro::obtener('distancia_minima_punto_venta_metros', 300);

        return PuntoVenta::query()
            ->where('estado', PuntoVenta::ESTADO_ACTIVO)
            ->when($exceptoId, fn ($q) => $q->where('id', '!=', $exceptoId))
            ->whereNotNull('latitud')
            ->whereNotNull('longitud')
            ->get(['id', 'latitud', 'longitud'])
            ->contains(fn ($pv) => $this->distanciaMetros($lat, $lng, (float) $pv->latitud, (float) $pv->longitud) <= $minimo);
    }

    /**
     * Firma el contrato y activa el punto de venta en una transacción.
     *
     * Identidad de la persona = cédula. Resolución de la cuenta: cédula → correo → crear.
     *
     * @param  array{email:string, numero_contrato:string, fecha_firma:string, fecha_inicio:string, fecha_fin?:?string, elaborado_por?:?string, observaciones?:?string}  $datos
     * @return array{punto_venta: PuntoVenta, password_temporal: ?string}
     *
     * @throws \DomainException  Si la solicitud no está en contrato, viola las 3 cuadras o hay conflicto de identidad.
     */
    public function activar(SolicitudPuntoVenta $solicitud, array $datos): array
    {
        if ($solicitud->estado !== SolicitudPuntoVenta::ESTADO_CONTRATO) {
            throw new DomainException('La solicitud no está en etapa de contrato.');
        }

        // Regla de las 3 cuadras (Art. 31): solo si la solicitud tiene ubicación.
        if ($solicitud->tieneUbicacion()
            && $this->existePuntoVentaCercano((float) $solicitud->latitud, (float) $solicitud->longitud)) {
            $minimo = (float) Parametro::obtener('distancia_minima_punto_venta_metros', 300);
            throw new DomainException("Ya existe un punto de venta activo a menos de {$minimo} m (Art. 31: un punto por cada tres cuadras).");
        }

        return DB::transaction(function () use ($solicitud, $datos) {
            // Resolución de identidad compartida: cédula → correo → crear.
            ['user' => $user, 'password_temporal' => $passwordTemporal] = $this->resolutor->resolver(
                $solicitud->cedula,
                $datos['email'],
                $solicitud->nombre_completo,
                'punto de venta'
            );

            if (PuntoVenta::where('user_id', $user->id)->exists()) {
                throw new DomainException('Esa persona ya está vinculada a un punto de venta.');
            }

            if (! $user->hasRole(RolSistema::PuntoVenta->value)) {
                $user->assignRole(RolSistema::PuntoVenta->value);
            }

            $this->asegurarPerfil($user, $solicitud);

            $punto = PuntoVenta::create([
                'codigo'                   => $this->generarCodigo(),
                'solicitud_punto_venta_id' => $solicitud->id,
                'user_id'                  => $user->id,
                'nombre_comercial'         => $solicitud->nombre_comercial,
                'direccion_local'          => $solicitud->direccion_local,
                'referencia_ubicacion'     => $solicitud->referencia_ubicacion,
                'latitud'                  => $solicitud->latitud,
                'longitud'                 => $solicitud->longitud,
                'estado'                   => PuntoVenta::ESTADO_ACTIVO,
            ]);

            $punto->contrato()->create([
                'numero_contrato'      => $datos['numero_contrato'],
                'fecha_firma'          => $datos['fecha_firma'],
                'fecha_inicio'         => $datos['fecha_inicio'],
                'fecha_fin'            => $datos['fecha_fin'] ?? null,
                'porcentaje_descuento' => (float) Parametro::obtener('descuento_punto_venta', 10), // Art. 31 (10%)
                'elaborado_por'        => $datos['elaborado_por'] ?? 'Procuraduría Síndica',
                'observaciones'        => $datos['observaciones'] ?? null,
            ]);

            $solicitud->update(['estado' => SolicitudPuntoVenta::ESTADO_ACTIVA]);

            return ['punto_venta' => $punto, 'password_temporal' => $passwordTemporal];
        });
    }

    /**
     * Crea el perfil del usuario si aún no lo tiene.
     *
     * @param  \App\Models\User                $user
     * @param  \App\Models\SolicitudPuntoVenta  $solicitud
     * @return void
     */
    private function asegurarPerfil(User $user, SolicitudPuntoVenta $solicitud): void
    {
        if ($user->perfil) {
            return;
        }

        $this->usuarioService->crearPerfil($user, [
            'cedula'                    => $solicitud->cedula,
            'telefono'                  => $solicitud->telefono,
            'telefono_celular'          => $solicitud->telefono_celular,
            'direccion'                 => $solicitud->direccion,
            'acepta_terminos'           => true,
            'fecha_aceptacion_terminos' => now(),
            'activo'                    => true,
        ]);
    }

    /**
     * Distancia Haversine en metros entre dos coordenadas.
     *
     * @return float  Distancia en metros.
     */
    private function distanciaMetros(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $radioTierra = 6371000; // metros
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $radioTierra * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}