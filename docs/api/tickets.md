# API — Tickets digitales de parqueo (Fase 5.C)

Gestión de tickets desde la app móvil del conductor.  
Base legal: Arts. 13, 14, 19, 22 Ordenanza SIMETSA.

---

## Endpoints

| Método | URL | Permiso | Descripción |
|--------|-----|---------|-------------|
| GET | `/api/v1/tickets` | `tickets.ver` | Tickets vigentes del conductor (pendiente/activo/en_tolerancia) |
| POST | `/api/v1/tickets` | `tickets.comprar` | Comprar ticket (valida horario, feriados, máx 2h, exoneraciones) |
| GET | `/api/v1/tickets/historial` | `tickets.ver` | Historial paginado (todos los estados) |
| GET | `/api/v1/tickets/{id}` | `tickets.ver` | Detalle de un ticket (ownership) |
| POST | `/api/v1/tickets/{id}/cancelar` | `tickets.cancelar` | Cancelar antes de iniciar sesión (solo estado `pendiente`) |

Todos los endpoints requieren `Authorization: Bearer <token>` (Sanctum).

---

## Estructura de respuesta

```json
{
  "exito": true,
  "mensaje": "Ticket comprado correctamente.",
  "datos": { ...TicketResource... },
  "errores": null
}
```

Errores de validación (422):
```json
{
  "exito": false,
  "mensaje": "Los datos enviados no son válidos.",
  "datos": null,
  "errores": {
    "vehiculo_id": ["Debe seleccionar un vehículo."],
    "horas_compradas": ["El tiempo máximo es 2 horas (Art. 14)."]
  }
}
```

---

## TicketResource

```json
{
  "id": 1,
  "codigo": "T-2026-00001",
  "estado": "pendiente",
  "estado_label": "Pendiente",
  "estado_color": "secondary",
  "horas_compradas": 1,
  "monto": 0.25,
  "metodo_pago": "efectivo",
  "metodo_pago_label": "Efectivo",
  "es_exonerado": false,
  "tipo_exoneracion": null,
  "comprado_en": "2026-05-29T10:00:00+00:00",
  "expira_en": "2026-05-29T11:00:00+00:00",
  "vehiculo": { "id": 1, "placa": "ABC-1234", "marca": "Toyota", "color": "Blanco" },
  "zona": { "id": 1, "codigo": "centro", "nombre": "Centro SIMETSA" },
  "sesion": null
}
```

Estados posibles: `pendiente` | `activo` | `en_tolerancia` | `expirado` | `cancelado` | `anulado`

---

## Notas

- **Horario operativo (Art. 12):** mar–vie y dom, 08:00–18:00. Comprar fuera → 422.
- **Feriados (Art. 12):** días registrados en `dias_feriado` → 422 con mensaje informativo.
- **Máximo 2 horas (Art. 14):** `horas_compradas` acepta solo 1 o 2. También valida que el ticket no cruce el cierre de jornada.
- **Tolerancia (Art. 13):** El agente puede ver estado `en_tolerancia` hasta 5 minutos post-expiración.
- **Exoneraciones:** CONADIS (Art. 26) e institucionales (Art. 27) → `monto = 0.00`, `es_exonerado = true`.
- **Ownership:** el conductor solo puede ver y cancelar sus propios tickets.
- **Historial:** paginado (15 por página). La respuesta tiene estructura `datos.data`, `datos.links`, `datos.meta`.

---

## curl examples

```bash
# 1. Login — obtener token Sanctum
curl -X POST ${APP_URL}/api/v1/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"conductor@simetsa.gob.ec","password":"password"}'
# → copiar el campo "token" de la respuesta

TOKEN="TU_TOKEN_AQUI"

# 2. Comprar ticket (happy path)
curl -X POST ${APP_URL}/api/v1/tickets \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"vehiculo_id":1,"zona_id":1,"horas_compradas":1,"metodo_pago":"efectivo"}'

# 3. Listar tickets vigentes
curl ${APP_URL}/api/v1/tickets \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"

# 4. Historial paginado
curl "${APP_URL}/api/v1/tickets/historial?page=1" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"

# 5. Detalle de ticket
curl ${APP_URL}/api/v1/tickets/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"

# 6. Cancelar ticket pendiente
curl -X POST ${APP_URL}/api/v1/tickets/1/cancelar \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"motivo":"Ya no necesito el espacio."}'

# Error: 401 sin token
curl ${APP_URL}/api/v1/tickets \
  -H "Accept: application/json"

# Error: 422 fuera de horario
curl -X POST ${APP_URL}/api/v1/tickets \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"vehiculo_id":1,"zona_id":1,"horas_compradas":1,"metodo_pago":"efectivo"}'
# (ejecutar a las 20:00 → 422 "Fuera del horario operativo")

# Error: 403 cancelar ticket de otro conductor
curl -X POST ${APP_URL}/api/v1/tickets/99/cancelar \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"motivo":"Intento de cancelar ticket ajeno."}'
```
