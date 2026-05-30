# API — Pagos (Fase 6)

Base URL: `${APP_URL}/api/v1`

---

## Resumen de endpoints

| Método | URL | Auth | Descripción |
|---|---|---|---|
| `POST` | `/pagos/webhook/{proveedor}` | Ninguna (firmado por gateway) | Recibe callback del gateway de pago |

> Los endpoints de compra de ticket (`POST /tickets`) aceptan ahora los campos `metodo_pago` y `proveedor`. Ver `docs/api/tickets.md`.

---

## Cambios en `POST /tickets` — campo `proveedor`

Desde Fase 6, la compra de tickets acepta un proveedor de pago digital opcional.

### Campos nuevos en el request

| Campo | Tipo | Requerido | Descripción |
|---|---|---|---|
| `metodo_pago` | string | Sí | `efectivo`, `link`, `qr`, `pago_simulado` (solo no-prod) |
| `proveedor` | string | No | `none` (default), `manual`, `deuna` |

### Relación `metodo_pago` ↔ `proveedor`

| `metodo_pago` | `proveedor` esperado | Descripción |
|---|---|---|
| `efectivo` | `none` | Pago en caja, sin gateway |
| `pago_simulado` | `manual` | Solo entornos no-productivos |
| `link` | `deuna` | URL de pago generada por Deuna |
| `qr` | `deuna` | Código QR de Deuna |

### Respuesta — ticket con proveedor digital (estado `pendiente_pago`)

```json
{
  "exito": true,
  "mensaje": "Ticket comprado correctamente.",
  "datos": {
    "id": 42,
    "codigo": "T-2026-00042",
    "estado": "pendiente_pago",
    "estado_label": "Pendiente de pago",
    "metodo_pago": "link",
    "proveedor": "deuna",
    "monto": 0.25,
    "comprado_en": "2026-03-10T10:00:00-05:00",
    "expira_en": "2026-03-10T11:00:00-05:00"
  },
  "errores": null
}
```

> En modo fake (`DEUNA_ENABLED=false`), la `TransaccionPago` se crea con `payment_url` simulada. El ticket pasa a `pendiente` automáticamente al recibir el webhook de confirmación.

---

## POST `/pagos/webhook/{proveedor}`

Endpoint público (sin token Sanctum). El gateway llama a esta URL al confirmar o rechazar un pago.

### Parámetros de URL

| Parámetro | Descripción |
|---|---|
| `proveedor` | Nombre del gateway: `deuna`, `pagomedios` |

### Headers esperados

| Header | Descripción |
|---|---|
| `X-Signature` | Firma HMAC-SHA256 del payload (en modo real; ignorada en modo fake) |

### Body (ejemplo Deuna)

```json
{
  "external_reference": "abc-123-uuid",
  "status": "approved",
  "amount": 0.25,
  "currency": "USD"
}
```

### Valores de `status` reconocidos

| `status` | `EstadoTransaccion` resultante |
|---|---|
| `approved`, `completed`, `success` | `completada` |
| `processing`, `pending_payment` | `procesando` |
| `declined`, `failed`, `error` | `fallida` |
| `refunded`, `reversed` | `reembolsada` |

### Respuesta exitosa

```json
{
  "exito": true,
  "mensaje": "Webhook procesado.",
  "datos": { "recibido": true },
  "errores": null
}
```

### Comportamiento al completar

Si `status = approved` → `Ticket::acreditar()` transiciona el ticket de `pendiente_pago` a `pendiente`. El agente puede entonces iniciar la sesión de parqueo normalmente.

### Idempotencia

Si la transacción ya está en estado terminal (`completada`, `fallida`, `reembolsada`), el webhook devuelve `200` sin modificar nada.

---

## Curls de referencia

```bash
# Login (obtener token Sanctum)
curl -X POST ${APP_URL}/api/v1/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"conductor@simetsa.gob.ec","password":"password"}'

# Comprar ticket con pago digital Deuna (modo fake)
curl -X POST ${APP_URL}/api/v1/tickets \
  -H "Authorization: Bearer TU_TOKEN_AQUI" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "vehiculo_id": 1,
    "zona_id": 1,
    "horas_compradas": 1,
    "metodo_pago": "link",
    "proveedor": "deuna"
  }'

# Simular webhook de pago aprobado (modo fake, sin firma)
curl -X POST ${APP_URL}/api/v1/pagos/webhook/deuna \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "external_reference": "fake-UUID-del-response-anterior",
    "status": "approved"
  }'

# Verificar que el ticket pasó a estado pendiente
curl ${APP_URL}/api/v1/tickets/42 \
  -H "Authorization: Bearer TU_TOKEN_AQUI" \
  -H "Accept: application/json"

# Error: proveedor no registrado
curl -X POST ${APP_URL}/api/v1/pagos/webhook/inexistente \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"external_reference": "abc", "status": "approved"}'
# → 422 "Proveedor de pago 'inexistente' no está registrado."
```

---

## Variables de entorno (Fase 6)

```dotenv
# Pagos
PAYMENTS_DEFAULT_PROVIDER=deuna
DEUNA_ENABLED=false          # true para modo real
DEUNA_MODE=fake              # fake | real
DEUNA_BASE_URL=https://sandbox.example.invalid
DEUNA_API_KEY=...
DEUNA_MERCHANT_ID=...
DEUNA_WEBHOOK_SECRET=...

# FCM
FCM_ENABLED=false            # true para envío real
FIREBASE_CREDENTIALS=/ruta/segura/firebase.json
```
