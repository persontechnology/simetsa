# API — Vehículos del Conductor

> **Base legal:** Art. 25 Ordenanza SIMETSA — cada conductor puede registrar uno o más vehículos activos a su nombre.

Base URL: `${APP_URL}/api/v1` (desarrollo: `http://localhost:8000/api/v1`)

---

## Endpoints

| Método | URL | Permiso requerido | Descripción |
|--------|-----|-------------------|-------------|
| GET | `/vehiculos` | `vehiculos.ver` | Lista los vehículos del conductor autenticado |
| POST | `/vehiculos` | `vehiculos.crear` | Registra un nuevo vehículo |
| GET | `/vehiculos/{id}` | `vehiculos.ver` | Detalle de un vehículo (solo propio) |
| PUT / PATCH | `/vehiculos/{id}` | `vehiculos.editar` | Actualiza datos del vehículo (solo propio) |
| DELETE | `/vehiculos/{id}` | `vehiculos.eliminar` | Elimina el vehículo (soft delete, solo propio) |

### Ownership

El conductor solo puede ver, editar y eliminar **sus propios vehículos**. Un intento de acceder al vehículo de otro conductor devuelve **403**.

`super_admin` y `comisario` tienen visibilidad total (bypass de política).

---

## Estructura de respuesta — objeto vehículo

```json
{
  "id": 3,
  "tipo_vehiculo_id": 1,
  "tipo_vehiculo": {
    "id": 1,
    "codigo": "liviano_privado",
    "nombre": "Liviano privado"
  },
  "placa": "ABC-1234",
  "marca": "Toyota",
  "modelo": "Corolla",
  "anio": 2022,
  "color": "Blanco",
  "estado": "activo",
  "observaciones": null,
  "fecha_registro": "2026-05-28"
}
```

| Campo | Tipo | Notas |
|-------|------|-------|
| `placa` | string | Formato ecuatoriano `ABC-1234`; normalizada a mayúsculas |
| `estado` | string | `activo` \| `inactivo` |
| `tipo_vehiculo` | object\|null | Solo presente si se cargó la relación (siempre en esta API) |

### Body de creación (`POST /vehiculos`)

| Campo | Tipo | Requerido | Reglas |
|-------|------|-----------|--------|
| `tipo_vehiculo_id` | integer | ✓ | Debe existir en `tipos_vehiculo` |
| `placa` | string | ✓ | Regex `/^[A-Z]{3}-\d{4}$/i`, max 10 chars, única entre vehículos activos |
| `marca` | string | ✓ | max 80 |
| `modelo` | string | ✓ | max 80 |
| `anio` | integer | ✓ | 1990 – año actual + 1 |
| `color` | string | ✓ | max 50 |
| `observaciones` | string | — | nullable, max 500 |

### Body de actualización (`PUT /PATCH /vehiculos/{id}`)

Todos los campos son opcionales (`sometimes`). Se pueden enviar solo los campos a modificar. Se puede actualizar `estado` (`activo` \| `inactivo`).

---

## Curls

```bash
# ── 0. Login ──────────────────────────────────────────────────────────────────
curl -s -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"conductor@simetsa.gob.ec","password":"password"}'

TOKEN="<pegar_token_aqui>"

# ── 1. Listar mis vehículos ───────────────────────────────────────────────────
curl -s http://localhost:8000/api/v1/vehiculos \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  | jq .

# ── 2. Registrar un vehículo ──────────────────────────────────────────────────
curl -s -X POST http://localhost:8000/api/v1/vehiculos \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "tipo_vehiculo_id": 1,
    "placa": "ABC-1234",
    "marca": "Toyota",
    "modelo": "Corolla",
    "anio": 2022,
    "color": "Blanco"
  }' | jq .
# → HTTP 201 con el vehículo creado

# ── 3. Ver detalle de un vehículo ─────────────────────────────────────────────
curl -s http://localhost:8000/api/v1/vehiculos/3 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  | jq .

# ── 4. Actualizar solo el color ───────────────────────────────────────────────
curl -s -X PATCH http://localhost:8000/api/v1/vehiculos/3 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"color": "Rojo"}'
# → HTTP 200 con el vehículo actualizado

# ── 5. Eliminar (soft delete) ─────────────────────────────────────────────────
curl -s -X DELETE http://localhost:8000/api/v1/vehiculos/3 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"
# → HTTP 200 {"exito":true,"mensaje":"Vehículo eliminado correctamente.",...}

# ── Error: 401 sin token ──────────────────────────────────────────────────────
curl -s http://localhost:8000/api/v1/vehiculos \
  -H "Accept: application/json"
# → HTTP 401

# ── Error: 422 placa inválida ─────────────────────────────────────────────────
curl -s -X POST http://localhost:8000/api/v1/vehiculos \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"tipo_vehiculo_id":1,"placa":"INVALIDA","marca":"Toyota","modelo":"X","anio":2022,"color":"Blanco"}' \
  | jq .
# → HTTP 422 {"exito":false,"mensaje":"Errores de validación.","errores":{"placa":[...]}}

# ── Error: 422 placa duplicada (Art. 25) ──────────────────────────────────────
curl -s -X POST http://localhost:8000/api/v1/vehiculos \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"tipo_vehiculo_id":1,"placa":"ABC-1234","marca":"Chevrolet","modelo":"Aveo","anio":2020,"color":"Azul"}' \
  | jq .
# → HTTP 422 {"exito":false,"mensaje":"La placa ABC-1234 ya está registrada en el sistema. (Art. 25)"}

# ── Error: 403 vehículo de otro conductor ─────────────────────────────────────
curl -s http://localhost:8000/api/v1/vehiculos/99 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"
# → HTTP 403 si el vehículo 99 no pertenece al conductor autenticado
```

---

## Notas

- **Placa normalizada:** siempre se guarda en mayúsculas (`ABC-1234`). El formato es el estándar ecuatoriano vigente.
- **Unicidad de placa:** se valida sobre vehículos *no eliminados* (índice parcial PostgreSQL sobre `deleted_at IS NULL`). Esto permite re-registrar una placa después de un soft-delete.
- **Sin paginación:** el conductor típico tiene pocos vehículos; la lista se devuelve completa ordenada por `created_at DESC`.
- **Soft delete:** el registro permanece en la base para auditoría; la placa queda libre para re-registro.
- **`estado` del vehículo** lo gestiona el comisario desde el backoffice web (`PATCH /vehiculos/{id}/estado`), no el conductor desde la app.
