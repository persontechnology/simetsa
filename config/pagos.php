<?php

// config/pagos.php

/**
 * Configuración de proveedores de pago del SIMETSA.
 *
 * PAYMENTS_DEFAULT_PROVIDER determina qué gateway se usa por defecto.
 * Cada proveedor tiene su propio bloque de configuración.
 */
return [

    'default_provider' => env('PAYMENTS_DEFAULT_PROVIDER', 'deuna'),

    // ──── Deuna ────────────────────────────────────────────────────────────
    'deuna' => [
        /*
         * DEUNA_ENABLED=false  → modo stub: transacciones simuladas, sin llamadas HTTP.
         * DEUNA_ENABLED=true   → modo real: requiere credenciales válidas de Deuna.
         */
        'enabled'        => (bool) env('DEUNA_ENABLED', false),

        /*
         * DEUNA_MODE=fake   → fuerza modo stub incluso si DEUNA_ENABLED=true (útil en staging).
         * DEUNA_MODE=real   → usa el endpoint real de DEUNA_BASE_URL.
         */
        'mode'           => env('DEUNA_MODE', 'fake'),

        'base_url'       => env('DEUNA_BASE_URL', 'https://sandbox.example.invalid'),
        'api_key'        => env('DEUNA_API_KEY', ''),
        'merchant_id'    => env('DEUNA_MERCHANT_ID', ''),
        'webhook_secret' => env('DEUNA_WEBHOOK_SECRET', ''),
    ],

];
