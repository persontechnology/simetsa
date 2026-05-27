<?php
// app/Http/Controllers/PerfilController.php

namespace App\Http\Controllers;

use App\Http\Requests\MiPerfilRequest;
use App\Models\PerfilUsuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Controlador de auto-gestión del PerfilUsuario.
 *
 * El usuario autenticado completa o edita SU PROPIO perfil:
 *  - Primera vez: crea el PerfilUsuario y registra fecha de aceptación LOPDP.
 *  - Posteriores: actualiza datos preservando la fecha de aceptación original.
 *
 * El flujo es disparado por el middleware `perfil.completo` cuando el usuario
 * intenta acceder a una ruta protegida sin tener su perfil completo.
 */
class PerfilController extends Controller
{
    /**
     * Muestra el formulario "Mi perfil SIMETSA".
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function mostrar(Request $request): View
    {
        $user   = $request->user();
        $perfil = $user->perfil; // null si aún no existe

        return view('perfil.completar', [
            'perfil'        => $perfil,
            'yaConsintio'   => (bool) $perfil?->acepta_terminos,
            'esPrimeraVez'  => $perfil === null,
            'generos'       => [
                'M'  => 'Masculino',
                'F'  => 'Femenino',
                'O'  => 'Otro',
                'ND' => 'No declara',
            ],
        ]);
    }

    /**
     * Guarda el perfil del usuario autenticado.
     * Maneja tanto creación inicial como actualización.
     *
     * @param  \App\Http\Requests\MiPerfilRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function actualizar(MiPerfilRequest $request): RedirectResponse
    {
        $user   = $request->user();
        $datos  = $request->validated();

        DB::transaction(function () use ($user, $datos, $request) {

            // firstOrNew permite manejar primera carga + ediciones con el mismo flujo
            $perfil = PerfilUsuario::firstOrNew(['user_id' => $user->id]);

            // Procesar foto si se subió una nueva
            $rutaFoto = $this->procesarFoto(
                $request->file('foto_perfil'),
                $perfil->foto_perfil
            );

            // Asignar campos del perfil
            $perfil->fill([
                'cedula'           => $datos['cedula'],
                'telefono'         => $datos['telefono'] ?? null,
                'telefono_celular' => $datos['telefono_celular'],
                'direccion'        => $datos['direccion'] ?? null,
                'fecha_nacimiento' => $datos['fecha_nacimiento'] ?? null,
                'genero'           => $datos['genero'] ?? null,
                'foto_perfil'      => $rutaFoto,
            ]);

            // Consentimiento LOPDP: solo registrar la fecha la PRIMERA vez
            // que se acepta. En ediciones posteriores se preserva el original.
            if (!empty($datos['acepta_terminos']) && !$perfil->acepta_terminos) {
                $perfil->acepta_terminos           = true;
                $perfil->fecha_aceptacion_terminos = now();
            }

            // Si es nuevo, marcarlo activo por defecto
            if (!$perfil->exists) {
                $perfil->activo = true;
            }

            $perfil->save();
        });

        return redirect()
            ->route('dashboard')
            ->with('success', '¡Listo! Tu perfil quedó completo y los datos guardados.');
    }

    /**
     * Procesa el archivo de foto subido: lo guarda en storage/app/public/perfiles
     * y elimina la foto anterior si la había.
     *
     * @param  \Illuminate\Http\UploadedFile|null  $archivo
     * @param  string|null                          $rutaAnterior
     * @return string|null  Ruta relativa guardada, o la anterior si no hubo upload
     */
    private function procesarFoto($archivo, ?string $rutaAnterior): ?string
    {
        if (!$archivo) {
            return $rutaAnterior;
        }

        // Borra la foto anterior si existe
        if ($rutaAnterior && Storage::disk('public')->exists($rutaAnterior)) {
            Storage::disk('public')->delete($rutaAnterior);
        }

        return $archivo->store('perfiles', 'public');
    }
}