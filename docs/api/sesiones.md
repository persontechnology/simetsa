# API — Validación y sesiones de parqueo (Fase 5.D)

Endpoints para el agente en calle: validar ticket por placa e iniciar sesión de parqueo.  
Base legal: Arts. 13, 38 Ordenanza SIMETSA.

---

## Endpoints

| Método | URL | Permiso | Actor | Descripción |
|--------|-----|---------|-------|-------------|
| GET | `/api/v1/tickets/validar/{placa}` | `sesiones_parqueo.ver` | Agente | Validar ticket activo por placa (con tolerancia Art. 13) |
| POST | `/api/v1/sesiones-parqueo` | `sesiones_parqueo.iniciar` | Agente | Iniciar sesión cuando el vehículo estaciona |
| GET | `/api/v1/sesiones-parqueo/{id}` | `sesiones_parqueo.ver` | Agente / Supervisor | Detalle de sesión |

---

## Respuesta de validación por placa

```json
{
  "exito": true,
  "mensaje": "Validación de ticket por placa.",
  "datos": {
    "estado": "activo",
    "ticket": { ...TicketResource... },
    "minutos_restantes": 45,
    "en_tolerancia": false,
    "tolerancia_expira_en": null
  }
}
```

**Estados posibles:**

| Estado | Significado |
|--------|-------------|
| `sin_ticket` | No hay ticket vigente para esa placa |
| `pendiente` | Ticket comprado, sesión no iniciada aún |
| `activo` | Sesión iniciada, tiempo vigente |
| `en_tolerancia` | Vencido hace ≤ 5 min (Art. 13 — gracia antes de inmovilizar) |
| `expirado` | Vencido hace > 5 min |
| `anulado` | Ticket anulado administrativamente |

---

## SesionParqueoResource

```json
{
  "id": 1,
  "ticket_id": 5,
  "estado": "activa",
  "estado_label": "Activa",
  "estado_color": "success",
  "lat_inicio": -1.0458000,
  "lng_inicio": -78.5916000,
  "inicio_at": "2026-05-29T10:05:00+00:00",
  "fin_programado_at": "2026-05-29T11:05:00+00:00",
  "fin_real_at": null,
  "agente": { "id": 1, "codigo": "AG-0001" },
  "plaza": null
}
```

---

## Notas

- **Tolerancia de 5 minutos (Art. 13):** El estado `en_tolerancia` se calcula en tiempo real; no se persiste en BD hasta que un job programado actualice el estado.
- **`{placa}` en la URL:** se acepta con o sin guion (ej. `ABC1234` o `ABC-1234`), búsqueda insensible a mayúsculas.
- **Perfil de agente requerido:** el usuario debe tener un registro en la tabla `agentes_parqueo`. Sin él → 403.
- **Sesión 1:1 con ticket:** un ticket solo puede tener una sesión. Intentar iniciar segunda sesión → 422.

---

## curl examples

```bash
TOKEN_AGENTE="TU_TOKEN_AGENTE_AQUI"

# 1. Validar placa
curl "${APP_URL}/api/v1/tickets/validar/ABC-1234" \
  -H "Authorization: Bearer $TOKEN_AGENTE" \
  -H "Accept: application/json"

# 2. Iniciar sesión de parqueo (con coordenadas GPS opcionales)
curl -X POST "${APP_URL}/api/v1/sesiones-parqueo" \
  -H "Authorization: Bearer $TOKEN_AGENTE" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"ticket_id":5,"latitud":-1.0458,"longitud":-78.5916}'

# 3. Ver detalle de sesión
curl "${APP_URL}/api/v1/sesiones-parqueo/1" \
  -H "Authorization: Bearer $TOKEN_AGENTE" \
  -H "Accept: application/json"

# Error: 401 sin token
curl "${APP_URL}/api/v1/tickets/validar/ABC-1234" \
  -H "Accept: application/json"

# Error: 403 conductor intentando validar placa
# (usa token de conductor, no agente)

# Error: 422 segunda sesión para mismo ticket
curl -X POST "${APP_URL}/api/v1/sesiones-parqueo" \
  -H "Authorization: Bearer $TOKEN_AGENTE" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"ticket_id":5}'
# → {"exito":false,"mensaje":"Este ticket ya tiene una sesión de parqueo iniciada.",...}
```
