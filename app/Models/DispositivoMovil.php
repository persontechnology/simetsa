<?php

// app/Models/DispositivoMovil.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dispositivo móvil registrado para recibir notificaciones push vía FCM.
 * La integración real con Firebase Cloud Messaging se activa en Fase 6.
 *
 * @property int              $id
 * @property int              $user_id
 * @property string           $token_fcm
 * @property string           $plataforma
 * @property bool             $activo
 * @property \Carbon\Carbon|null $ultimo_uso_at
 */
class DispositivoMovil extends Model
{
    use HasFactory;

    protected $table = 'dispositivos_moviles';

    public const PLATAFORMA_IOS     = 'ios';
    public const PLATAFORMA_ANDROID = 'android';

    protected $fillable = [
        'user_id', 'token_fcm', 'plataforma', 'activo', 'ultimo_uso_at',
    ];

    protected $casts = [
        'activo'        => 'boolean',
        'ultimo_uso_at' => 'datetime',
    ];

    /** Usuario propietario del dispositivo. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Plataformas válidas para selects y validaciones. */
    public static function plataformas(): array
    {
        return [self::PLATAFORMA_IOS, self::PLATAFORMA_ANDROID];
    }
}
