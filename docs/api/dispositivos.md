# API — Dispositivos móviles FCM (Fase 5.G)

Registro de tokens FCM para notificaciones push.  
**Fase 5:** solo se persiste el token y se encolan las notificaciones. El envío real a Firebase Cloud Messaging se integra en **Fase 6**.

---

## Endpoints

| Método | URL | Permiso | Descripción |
|--------|-----|---------|-------------|
| POST | `/api/v1/dispositivos` | `dispositivos_moviles.registrar` | Registrar o actualizar token FCM (idempotente) |
| DELETE | `/api/v1/dispositivos/{token}` | `dispositivos_moviles.registrar` | Eliminar token FCM del usuario autenticado |

Todos los endpoints requieren `Authorization: Bearer <token>` (Sanctum).  
Disponible para cualquier usuario autenticado que tenga el permiso (conductor, agente, etc.).

---

## Respuesta de registro

```json
{
  "exito": true,
  "mensaje": "Token FCM registrado correctamente.",
  "datos": {
    "id": 1,
    "plataforma": "android",
    "activo": true,
    "ultimo_uso_at": "2026-05-29T10:05:00+00:00",
    "created_at": "2026-05-29T10:05:00+00:00"
  },
  "errores": null
}
```

- `201 Created` si el token era nuevo.
- `200 OK` si el token ya existía para ese usuario (actualiza `plataforma` y `ultimo_uso_at`).

---

## Notas

- **Idempotente:** el par `(user_id, token_fcm)` tiene índice único. Registrar el mismo token dos veces actualiza el registro en lugar de duplicarlo.
- **`{token}` en DELETE:** el token FCM puede contener caracteres especiales; la ruta acepta cualquier cadena gracias a `->where('token', '.+')`.
- **Ownership:** solo se puede eliminar el token del usuario autenticado. Intentar eliminar un token de otro usuario devuelve 404 (no revela si existe o no).
- **Cola de notificaciones:** `NotificacionPushService::encolar()` persiste la intención de notificar en la tabla `notificaciones_push`. El dispatch real ocurre en Fase 6.

---

## curl examples

```bash
# 1. Login — obtener token Sanctum
curl -X POST ${APP_URL}/api/v1/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"conductor@simetsa.gob.ec","password":"password"}'

TOKEN="TU_TOKEN_AQUI"

# 2. Registrar token FCM (primera vez → 201)
curl -X POST ${APP_URL}/api/v1/dispositivos \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"token_fcm":"dGhpcyBpcyBhIHNhbXBsZSBGQ00gdG9rZW4...","plataforma":"android"}'

# 3. Registrar mismo token de nuevo (idempotente → 200)
curl -X POST ${APP_URL}/api/v1/dispositivos \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"token_fcm":"dGhpcyBpcyBhIHNhbXBsZSBGQ00gdG9rZW4...","plataforma":"ios"}'

# 4. Eliminar token FCM
curl -X DELETE "${APP_URL}/api/v1/dispositivos/dGhpcyBpcyBhIHNhbXBsZSBGQ00gdG9rZW4..." \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"

# Error: 401 sin token
curl -X POST ${APP_URL}/api/v1/dispositivos \
  -H "Accept: application/json" \
  -d '{"token_fcm":"abc","plataforma":"android"}'

# Error: 422 plataforma inválida
curl -X POST ${APP_URL}/api/v1/dispositivos \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"token_fcm":"abc123","plataforma":"windows"}'
# → {"exito":false,"errores":{"plataforma":["La plataforma debe ser ios o android."]}}

# Error: 404 token inexistente
curl -X DELETE "${APP_URL}/api/v1/dispositivos/token_inexistente" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"
```
