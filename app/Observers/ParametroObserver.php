<?php
// app/Observers/ParametroObserver.php

namespace App\Observers;

use App\Models\Parametro;
use App\Models\ParametroBitacora;

/**
 * Observer que registra automáticamente cambios sobre Parametro
 * en la tabla `parametros_bitacora`.
 *
 * Hooks utilizados:
 *  - updated: dispara una bitácora por cada campo auditable modificado.
 *
 * Campos auditados: 'valor' y 'descripcion'.
 *
 * Diseño:
 *  - Usa el evento `updated` (post-UPDATE) para evitar entradas huérfanas.
 *  - getOriginal($campo) retorna el valor PREVIO porque syncOriginal()
 *    se invoca recién en finishSave(), tras el evento updated.
 *  - Tolerante a fallos: si falla la escritura, se loguea pero NO
 *    se propaga la excepción para no romper el guardado del Parametro.
 */
class ParametroObserver
{
    /**
     * Campos cuyo cambio se audita.
     *
     * @var array<int, string>
     */
    private const CAMPOS_AUDITABLES = ['valor', 'descripcion'];

    /**
     * Hook que se ejecuta DESPUÉS de un update exitoso.
     * Por cada campo auditable que haya cambiado, crea una fila en la bitácora.
     *
     * @param  \App\Models\Parametro  $parametro
     * @return void
     */
    public function updated(Parametro $parametro): void
    {
        foreach (self::CAMPOS_AUDITABLES as $campo) {
            if ($parametro->wasChanged($campo)) {
                $this->registrarCambio($parametro, $campo);
            }
        }
    }

    /**
     * Inserta una entrada de bitácora para un campo específico.
     * Maneja excepciones para no propagar fallas al flujo de guardado.
     *
     * @param  \App\Models\Parametro  $parametro
     * @param  string                  $campo
     * @return void
     */
    private function registrarCambio(Parametro $parametro, string $campo): void
    {
        try {
            ParametroBitacora::create([
                'parametro_id'   => $parametro->id,
                'user_id'        => auth()->id(),           // null en CLI/seeds/jobs
                'campo'          => $campo,
                'valor_anterior' => $parametro->getOriginal($campo),
                'valor_nuevo'    => $parametro->{$campo},
                'ip'             => request()?->ip(),       // null en CLI/seeds/jobs
                'ocurrido_en'    => now(),
            ]);
        } catch (\Throwable $e) {
            logger()->error('Falló registrar bitácora de parámetro', [
                'parametro_id' => $parametro->id,
                'campo'        => $campo,
                'exception'    => $e->getMessage(),
            ]);
        }
    }
}