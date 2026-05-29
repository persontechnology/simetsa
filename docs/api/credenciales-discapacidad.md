# API — Credencial CONADIS (Discapacidad)

> **Base legal:** Art. 26 Ordenanza SIMETSA — el conductor con discapacidad registrada ante el CONADIS puede solicitar exoneración del pago de parqueo adjuntando su credencial.

Base URL: `${APP_URL}/api/v1` (desarrollo: `http://192.168.1.58:8000/api/v1`)

---

## Endpoints

| Método | URL | Permiso requerido | Descripción |
|--------|-----|-------------------|-------------|
| POST | `/vehiculos/{vehiculo}/credencial` | `credenciales_discapacidad.crear` | Solicita una nueva credencial CONADIS para el vehículo |
| GET | `/vehiculos/{vehiculo}/credencial` | `credenciales_discapacidad.ver` | Devuelve la credencial más reciente del vehículo |

### Ownership

El conductor solo puede solicitar y consultar credenciales de **sus propios vehículos**. Un intento sobre el vehículo de otro conductor devuelve **403**.

`super_admin` y `comisario` tienen visibilidad total.

### Flujo de aprobación

```
Conductor (app)            Comisario (backoffice web)
    │                               │
    │── POST /credencial ──────────▶│  estado: pendiente
    │                               │
    │                      PATCH /credenciales-discapacidad/{id}/aprobar
    │                               │  estado: aprobada
    │                               │
    │◀── GET /credencial ───────────│
    │    estado: "aprobada"         │
```

La aprobación y el rechazo son acciones **exclusivas del backoffice web** y no están expuestas en la API móvil.

---

## Estructura de respuesta — objeto credencial

```json
{
  "id": 1,
  "vehiculo_id": 3,
  "numero_conadis": "17-AB12-CONADIS",
  "nombre_beneficiario": "Pedro Tigse Caisaguano",
  "porcentaje_discapacidad": 45,
  "fecha_emision": "2023-01-15",
  "fecha_vencimiento": null,
  "estado": "pendiente",
  "url_archivo": "http://192.168.1.58:8000/storage/credenciales/6/3/conadis_1748484000.pdf",
  "observaciones": null,
  "aprobada_por": null,
  "fecha_aprobacion": null,
  "fecha_solicitud": "2026-05-28"
}
```

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `estado` | string | `pendiente` \| `aprobada` \| `rechazada` \| `vencida` |
| `url_archivo` | string\|null | URL pública del PDF/imagen adjunto (disco `public`) |
| `porcentaje_discapacidad` | integer\|null | Porcentaje CONADIS (mínimo 30 %) |
| `aprobada_por` | integer\|null | ID del usuario que aprobó (comisario/director) |
| `fecha_aprobacion` | datetime\|null | ISO 8601 cuando fue aprobada |

### Body de creación (`POST /vehiculos/{vehiculo}/credencial`)

| Campo | Tipo | Requerido | Reglas |
|-------|------|-----------|--------|
| `numero_conadis` | string | ✓ | max 50 |
| `nombre_beneficiario` | string | ✓ | max 200 |
| `fecha_emision` | date | ✓ | `YYYY-MM-DD`, no puede ser futura |
| `fecha_vencimiento` | date | — | nullable, posterior a `fecha_emision` |
| `porcentaje_discapacidad` | integer | — | nullable, 30–100 |
| `archivo` | file | — | nullable, PDF/JPG/PNG, máx. 5 MB |
| `observaciones` | string | — | nullable, max 500 |

> **Nota multipart:** si se adjunta `archivo`, la petición debe enviarse como `multipart/form-data` (no `application/json`).

---

## Curls

```bash
# ── 0. Login ──────────────────────────────────────────────────────────────────
curl -s -X POST http://192.168.1.58:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"conductor@simetsa.gob.ec","password":"password"}'

TOKEN="<pegar_token_aqui>"
VEHICULO_ID=3   # ID de un vehículo propio del conductor

# ── 1. Solicitar credencial (sin archivo) ─────────────────────────────────────
curl -s -X POST http://192.168.1.58:8000/api/v1/vehiculos/${VEHICULO_ID}/credencial \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "numero_conadis": "17-AB12-CONADIS",
    "nombre_beneficiario": "Pedro Tigse Caisaguano",
    "fecha_emision": "2023-01-15",
    "porcentaje_discapacidad": 45
  }' | jq .
# → HTTP 201 con la credencial en estado "pendiente"

# ── 2. Solicitar credencial (con archivo PDF) ─────────────────────────────────
curl -s -X POST http://192.168.1.58:8000/api/v1/vehiculos/${VEHICULO_ID}/credencial \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -F "numero_conadis=17-AB12-CONADIS" \
  -F "nombre_beneficiario=Pedro Tigse Caisaguano" \
  -F "fecha_emision=2023-01-15" \
  -F "porcentaje_discapacidad=45" \
  -F "archivo=@/ruta/local/credencial_conadis.pdf" \
  | jq .
# → HTTP 201; el campo "url_archivo" apuntará al PDF almacenado

# ── 3. Consultar credencial del vehículo ──────────────────────────────────────
curl -s http://192.168.1.58:8000/api/v1/vehiculos/${VEHICULO_ID}/credencial \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  | jq .
# → HTTP 200 con la credencial más reciente

# ── Error: 401 sin token ──────────────────────────────────────────────────────
curl -s -X POST http://192.168.1.58:8000/api/v1/vehiculos/${VEHICULO_ID}/credencial \
  -H "Accept: application/json" \
  -d '{}'
# → HTTP 401

# ── Error: 422 segunda credencial activa (Art. 26) ────────────────────────────
# (el vehículo ya tiene una credencial en estado pendiente o aprobada)
curl -s -X POST http://192.168.1.58:8000/api/v1/vehiculos/${VEHICULO_ID}/credencial \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "numero_conadis": "17-XXXX-CONADIS",
    "nombre_beneficiario": "Pedro Tigse",
    "fecha_emision": "2024-01-01"
  }' | jq .
# → HTTP 422 {"exito":false,"mensaje":"Este vehículo ya tiene una credencial CONADIS activa. (Art. 26)"}

# ── Error: 403 vehículo de otro conductor ─────────────────────────────────────
VEHICULO_AJENO=99
curl -s -X POST http://192.168.1.58:8000/api/v1/vehiculos/${VEHICULO_AJENO}/credencial \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"numero_conadis":"X","nombre_beneficiario":"X","fecha_emision":"2024-01-01"}' \
  | jq .
# → HTTP 403 {"exito":false,"mensaje":"No tienes acceso a este vehículo."}

# ── Error: 404 vehículo sin credencial ────────────────────────────────────────
VEHICULO_NUEVO=5  # vehículo que aún no tiene credencial
curl -s http://192.168.1.58:8000/api/v1/vehiculos/${VEHICULO_NUEVO}/credencial \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  | jq .
# → HTTP 404 {"exito":false,"mensaje":"Este vehículo no tiene credencial CONADIS registrada."}
```

---

## Notas

- **Una activa por vehículo (Art. 26):** el sistema bloquea una segunda solicitud si ya existe una credencial en estado `pendiente` o `aprobada`. Se puede re-solicitar si la anterior fue `rechazada` o `vencida`.
- **Archivo opcional:** la credencial puede registrarse sin adjunto y el documento puede presentarse físicamente en la Comisaría para su validación.
- **`url_archivo`:** devuelve la URL pública del disco `public` de Laravel (`storage/app/public/credenciales/{conductor_id}/{vehiculo_id}/`). Requiere que el enlace simbólico `php artisan storage:link` esté activo en el servidor.
- **Credencial más reciente:** `GET /credencial` devuelve el registro con el `id` más alto (último registrado), sea cual sea su estado.
- **Vencimiento automático:** la transición de `aprobada` → `vencida` cuando `fecha_vencimiento < hoy` está pendiente como deuda técnica (`simetsa:marcar-credenciales-vencidas`). Ver `CLAUDE.md` → Deuda técnica.
