<?php
// database/seeders/RolPermisoSeeder.php

namespace Database\Seeders;

use App\Enums\RolSistema;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeder que crea los roles y permisos del SIMETSA y asigna
 * los permisos correspondientes a cada rol según la Ordenanza.
 *
 * Distribución de responsabilidades por rol:
 * - super_admin:        todos los permisos.
 * - comisario:          operación, agentes, infracciones, órdenes de pago (Art. 37).
 * - director_seguridad: catálogos base, autorización de agentes (Art. 36), liquidaciones.
 * - agente_parqueo:     venta de tickets y registro de infracciones en calle (Art. 38).
 * - punto_venta:        venta de tickets en local comercial (Art. 31).
 * - conductor:          autogestión, compra de tickets, pago de multas (Art. 41).
 */
class RolPermisoSeeder extends Seeder
{
    /**
     * Punto de entrada del seeder. Coordina el orden de creación:
     *   1) limpiar caché de Spatie
     *   2) crear permisos del catálogo
     *   3) crear los 6 roles
     *   4) asignar permisos a cada rol
     *
     * @return void
     */
    public function run(): void
    {
        // Limpia la caché de Spatie para evitar conflictos en re-ejecuciones.
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->crearPermisos();
        $this->crearRoles();
        $this->asignarPermisos();
    }

    /**
     * Crea (o reutiliza si ya existen) todos los permisos definidos en
     * el catálogo config/simetsa_permisos.php.
     *
     * @return void
     */
    private function crearPermisos(): void
    {
        // El catálogo está agrupado por módulo → entidad → acciones.
        $catalogo = config('simetsa_permisos');

        foreach ($catalogo as $modulo) {
            foreach ($modulo as $entidad => $acciones) {
                foreach ($acciones as $accion) {
                    // firstOrCreate evita duplicados al re-ejecutar el seeder.
                    Permission::firstOrCreate([
                        'name'       => "{$entidad}.{$accion}",
                        'guard_name' => 'web',
                    ]);
                }
            }
        }
    }

    /**
     * Crea (o reutiliza si ya existen) los 6 roles del sistema.
     * Los nombres provienen del Enum RolSistema (fuente única de verdad).
     *
     * @return void
     */
    private function crearRoles(): void
    {
        foreach (RolSistema::cases() as $rol) {
            Role::firstOrCreate([
                'name'       => $rol->value,
                'guard_name' => 'web',
            ]);
        }
    }

    /**
     * Asigna a cada rol los permisos correspondientes a sus
     * responsabilidades según la Ordenanza SIMETSA.
     *
     * Se usa syncPermissions() para que la re-ejecución del seeder
     * deje el estado limpio (idempotente).
     *
     * @return void
     */
    private function asignarPermisos(): void
    {
        // Super Admin: acceso total
        Role::findByName(RolSistema::SuperAdmin->value)
            ->syncPermissions(Permission::all());

        // Comisario de Higiene y Salubridad (Art. 4, 15, 28, 37)
        Role::findByName(RolSistema::Comisario->value)
            ->syncPermissions($this->permisosComisario());

        // Director de Seguridad Ciudadana (Art. 4, 10, 36)
        Role::findByName(RolSistema::DirectorSeguridad->value)
            ->syncPermissions($this->permisosDirectorSeguridad());

        // Agente de Parqueo (Art. 38)
        Role::findByName(RolSistema::AgenteParqueo->value)
            ->syncPermissions($this->permisosAgenteParqueo());

        // Punto de Venta (Art. 31)
        Role::findByName(RolSistema::PuntoVenta->value)
            ->syncPermissions($this->permisosPuntoVenta());

        // Conductor (Art. 41)
        Role::findByName(RolSistema::Conductor->value)
            ->syncPermissions($this->permisosConductor());
    }

    /**
     * Permisos del Comisario de Higiene y Salubridad.
     *
     * Responsabilidades operativas según la Ordenanza:
     * - Coordinación con direcciones municipales (Art. 37.1).
     * - Atención de reclamos de usuarios y agentes (Art. 37.2).
     * - Trámite de procedimientos administrativos sancionatorios (Art. 37.3).
     * - Elaboración de horarios rotativos de agentes (Art. 37.4).
     * - Informes de incumplimiento de agentes al Director (Art. 37.5).
     * - Determinación de implementos y distintivos (Art. 37.6).
     * - Generación de órdenes de pago por multas (Art. 28).
     * - Autorización de inmovilizaciones (Art. 15).
     *
     * @return array<string>
     */
    private function permisosComisario(): array
    {
        return [
            // Seguridad
            'usuarios.ver', 'usuarios.crear', 'usuarios.editar',
            'roles.ver', 'roles.asignar',
            'accesos.ver',

            // Agentes (Art. 32-40)
            'agentes.ver', 'agentes.crear', 'agentes.editar',
            'solicitudes_agente.ver', 'solicitudes_agente.aprobar', 'solicitudes_agente.rechazar',
            'documentos_agente.ver', 'documentos_agente.subir',
            'cursos.ver', 'cursos.crear', 'cursos.editar',
            'inscripciones.ver', 'inscripciones.crear',
            'calificaciones.ver', 'calificaciones.registrar',
            'horarios_rotativos.ver', 'horarios_rotativos.crear', 'horarios_rotativos.editar',
            'amonestaciones.ver', 'amonestaciones.registrar',
            'expedientes_agente.ver',

            // Conductores y vehículos (Art. 25-27, Fase 4)
            'conductores.ver', 'conductores.editar',
            'vehiculos.ver',
            'tipos_vehiculo.ver',
            'credenciales_discapacidad.ver', 'credenciales_discapacidad.aprobar',
            'vehiculos_exonerados.ver', 'vehiculos_exonerados.crear', 'vehiculos_exonerados.editar', 'vehiculos_exonerados.eliminar',

            // Puntos de venta (Art. 31)
            'puntos_venta.ver', 'puntos_venta.crear', 'puntos_venta.editar',
            'solicitudes_punto_venta.ver', 'solicitudes_punto_venta.crear', 'solicitudes_punto_venta.aprobar', 'solicitudes_punto_venta.rechazar',
            'contratos_punto_venta.ver',

            // Tickets (control y anulación)
            'tickets.ver', 'tickets.anular',
            'sesiones_parqueo.ver',
            'cancelaciones.ver', 'cancelaciones.registrar',

            // Infracciones, multas, inmovilizaciones (Art. 15, 17, 28-30)
            'infracciones.ver', 'infracciones.registrar',
            'multas.ver',
            'inmovilizaciones.ver', 'inmovilizaciones.aplicar', 'inmovilizaciones.retirar',
            'ordenes_pago.ver', 'ordenes_pago.generar',
            'notificaciones_infraccion.ver', 'notificaciones_infraccion.enviar',
            'impugnaciones.ver', 'impugnaciones.resolver',

            // Pagos (consulta)
            'pagos.ver',
            'comprobantes.ver',
            'liquidaciones.ver',

            // Reportes
            'reportes.ver', 'reportes.generar', 'reportes.exportar',
            'kpi.ver',

            // Fiscalización (supervisión)
            'turnos.ver',
            'recorridos.ver',
            'incidentes.ver',
            'reportes_ecu911.ver',
        ];
    }

    /**
     * Permisos del Director de Seguridad Ciudadana.
     *
     * Responsabilidades según la Ordenanza:
     * - Administración del servicio (Art. 4).
     * - Designación de codificaciones de zonas y manzanas (Art. 10).
     * - Distribución de talonarios/tickets (Art. 20).
     * - Autorización final de Agentes de Parqueo (Art. 36).
     *
     * @return array<string>
     */
    private function permisosDirectorSeguridad(): array
    {
        return [
            // Seguridad (consulta)
            'usuarios.ver',
            'roles.ver',
            'accesos.ver', 'auditoria.ver',

            // Catálogos base (Art. 5, 10, 12, 16)
            'zonas.ver', 'zonas.crear', 'zonas.editar', 'zonas.eliminar',
            'calles.ver', 'calles.crear', 'calles.editar', 'calles.eliminar',
            'manzanas.ver', 'manzanas.crear', 'manzanas.editar', 'manzanas.eliminar',
            'plazas.ver', 'plazas.crear', 'plazas.editar', 'plazas.eliminar',
            'tipos_plaza.ver', 'tipos_plaza.crear', 'tipos_plaza.editar',
            'tarifas.ver', 'tarifas.crear', 'tarifas.editar',
            'horarios.ver', 'horarios.crear', 'horarios.editar',
            'feriados.ver', 'feriados.crear', 'feriados.editar',
            'parametros.ver', 'parametros.editar',

            // Agentes (autorización final - Art. 36)
            'agentes.ver', 'agentes.crear', 'agentes.editar',
            'solicitudes_agente.ver', 'solicitudes_agente.aprobar',
            'asignaciones_zona.ver', 'asignaciones_zona.asignar',

            // Conductores y vehículos (Art. 25-27, Fase 4)
            'conductores.ver',
            'vehiculos.ver',
            'tipos_vehiculo.ver', 'tipos_vehiculo.crear', 'tipos_vehiculo.editar', 'tipos_vehiculo.eliminar',
            'credenciales_discapacidad.ver', 'credenciales_discapacidad.aprobar',
            'vehiculos_exonerados.ver', 'vehiculos_exonerados.crear', 'vehiculos_exonerados.editar', 'vehiculos_exonerados.eliminar',

            // Reportes
            'reportes.ver', 'reportes.generar', 'reportes.exportar',
            'kpi.ver',

            // Pagos y liquidaciones (Art. 21)
            'liquidaciones.ver', 'liquidaciones.generar',
            'conciliaciones.ver', 'conciliaciones.ejecutar',
            'comprobantes.ver',
        ];
    }

    /**
     * Permisos del Agente de Parqueo (operación en calle).
     *
     * Responsabilidades según Art. 38:
     * - Venta de tickets en su zona asignada (Art. 24, 38.b).
     * - Reporte de incumplimiento del usuario (Art. 13, 17).
     * - Reporte al ECU 911 ante incidentes (Art. 38.m).
     * - Operación de su turno y recorrido.
     *
     * @return array<string>
     */
    private function permisosAgenteParqueo(): array
    {
        return [
            // Tickets (venta en calle - Art. 24)
            'tickets.ver', 'tickets.comprar',

            // Infracciones (constatación - Art. 13, 15, 17)
            'infracciones.ver', 'infracciones.registrar',
            'inmovilizaciones.ver',

            // Fiscalización
            'turnos.ver', 'turnos.iniciar', 'turnos.cerrar',
            'recorridos.ver',
            'incidentes.ver', 'incidentes.registrar',
            'reportes_ecu911.ver', 'reportes_ecu911.enviar',

            // Pagos (cobro en calle)
            'pagos.registrar',
            'comprobantes.ver', 'comprobantes.emitir',

            // Liquidación propia (Art. 21 - 60/40)
            'liquidaciones.ver',
        ];
    }

    /**
     * Permisos del Punto de Venta autorizado.
     *
     * Función: venta de tickets en local comercial con 10% de descuento (Art. 31).
     *
     * @return array<string>
     */
    private function permisosPuntoVenta(): array
    {
        return [
            // Tickets (venta en mostrador - Art. 31)
            'tickets.ver', 'tickets.comprar',

            // Pagos (cobro al cliente)
            'pagos.registrar',
            'comprobantes.ver', 'comprobantes.emitir',

            // Liquidación propia (Art. 21 - 90/10)
            'liquidaciones.ver',
        ];
    }

    /**
     * Permisos del Conductor (usuario final de la app móvil).
     *
     * Obligaciones según Art. 41:
     * - Adquirir el ticket (Art. 41.b).
     * - Pagar la multa para retirar el candado (Art. 41.d).
     * - Sugerir mejoras (Art. 41.h).
     *
     * Nota: el alcance "solo sus propios registros" se controla con
     * Policies en las fases 4-7, no aquí.
     *
     * @return array<string>
     */
    private function permisosConductor(): array
    {
        return [
            // Autogestión
            'perfiles.ver', 'perfiles.editar',

            // Vehículos propios
            'tipos_vehiculo.ver',
            'vehiculos.ver', 'vehiculos.crear', 'vehiculos.editar', 'vehiculos.eliminar',
            'credenciales_discapacidad.ver', 'credenciales_discapacidad.crear', 'credenciales_discapacidad.editar',

            // Tickets propios
            'tickets.ver', 'tickets.comprar',
            'sesiones_parqueo.ver',

            // Pagos propios
            'pagos.ver',
            'comprobantes.ver',

            // Infracciones e impugnaciones (Art. 41.d, jurisdicción del usuario)
            'infracciones.ver',
            'multas.ver',
            'impugnaciones.ver', 'impugnaciones.registrar',

            // Notificaciones móviles
            'notificaciones.ver',
            'dispositivos_moviles.ver', 'dispositivos_moviles.registrar',
            'preferencias_notificacion.ver', 'preferencias_notificacion.editar',
        ];
    }
}