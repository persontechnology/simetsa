# API — Infracciones y Candado Inmovilizador

**Fase 7.C** | Arts. 15, 17, 18, 28, 29, 30 — Ordenanza SIMETSA

Endpoints usados por la **app del agente en calle** para registrar infracciones y gestionar el candado inmovilizador.

---

## Resumen de endpoints

| Método | URL | Permiso | Descripción |
|--------|-----|---------|-------------|
| `POST` | `/api/v1/infracciones` | `infracciones.registrar` | Registra una nueva infracción y calcula la multa |
| `GET`  | `/api/v1/infracciones/{id}` | `infracciones.ver` | Detalle de una infracción |
| `POST` | `/api/v1/infracciones/{id}/inmovilizar` | `inmovilizaciones.aplicar` | Coloca el candado inmovilizador |
| `POST` | `/api/v1/infracciones/{id}/liberar` | `inmovilizaciones.retirar` | Retira el candado |

Todas las rutas requieren `Authorization: Bearer {token}` (Sanctum).

---

## Cálculo automático de multas

El monto se calcula al crear la infracción a partir del **SBU vigente** (parámetro `sbu_vigente`):

| Tipo | Artículo | % SBU |
|------|----------|-------|
| `tiempo_excedido` (6–60 min) | Art. 28 | **2 %** |
| `tiempo_excedido` (61–120 min) | Art. 28 | **4 %** |
| `tiempo_excedido` (> 120 min) | Art. 28 | **8 %** |
| `sin_ticket_visible`, `sin_adquirir_ticket`, `intercambio_tickets` | Art. 29 | **2 %** |
| `ticket_alterado`, `retirar_candado`, `doble_columna`, `calle_prohibida_buses`, `vehiculo_prohibido`, `fuera_de_area` | Art. 29 | **20 %** |
| `agresion_agente` | Art. 30 | **50 %** |
| `negar_pago` | Art. 17.g | **0** (sin cargo explícito) |

---

## POST `/api/v1/infracciones`

Registra una infracción. El SBU se captura como snapshot al momento del registro.

### Body

```json
{
  "placa": "ABC1234",
  "tipo_infraccion": "sin_ticket_visible",
  "zona_id": 1,
  "calle_id": null,
  "ticket_id": null,
  "conductor_id": null,
  "minutos_excedidos": null,
  "descripcion": "Vehículo sin ticket en parabrisas.",
  "foto_evidencia": null,
  "latitud": -1.0485,
  "longitud": -78.5975
}
```

> Para `tipo_infraccion = tiempo_excedido`, `minutos_excedidos` debe ser ≥ 6 (Art. 28 + Art. 13).

### Respuesta 201

```json
{
  "exito": true,
  "mensaje": "Infracción registrada correctamente.",
  "datos": {
    "id": 1,
    "placa": "ABC1234",
    "tipo_infraccion": "sin_ticket_visible",
    "tipo_label": "Sin ticket visible (Art. 17.b)",
    "estado": "pendiente",
    "estado_label": "Pendiente de pago",
    "estado_color": "warning",
    "monto_multa": "9.20",
    "sbu_vigente": "460.00",
    "minutos_excedidos": null,
    "descripcion": "Vehículo sin ticket en parabrisas.",
    "foto_evidencia": null,
    "latitud": "-1.0485000",
    "longitud": "-78.5975000",
    "registrada_en": "2026-05-30T10:30:00+00:00",
    "zona": { "id": 1, "nombre": "Centro", "codigo": "centro" },
    "calle": null,
    "agente": { "id": 1, "codigo": "AG-0001" },
    "conductor": null,
    "inmovilizacion": null
  },
  "errores": null
}
```

---

## GET `/api/v1/infracciones/{id}`

Devuelve el detalle completo con la inmovilización embebida.

**Ownership:** el agente solo ve las infracciones que él registró. El comisario y director ven todas.

---

## POST `/api/v1/infracciones/{id}/inmovilizar`

Coloca el candado inmovilizador (Art. 15). Solo posible si la infracción está `pendiente` y no tiene inmovilización previa.

### Body

```json
{
  "foto_candado": null,
  "notas": "Candado colocado a las 10:35."
}
```

### Respuesta 201

```json
{
  "exito": true,
  "mensaje": "Vehículo inmovilizado correctamente.",
  "datos": {
    "id": 1,
    "placa": "ABC1234",
    ...
    "inmovilizacion": {
      "id": 1,
      "infraccion_id": 1,
      "estado": "activa",
      "estado_label": "Activa",
      "estado_color": "danger",
      "foto_candado": null,
      "notas": "Candado colocado a las 10:35.",
      "inmovilizada_en": "2026-05-30T10:35:00+00:00",
      "liberada_en": null,
      "agente": { "id": 1, "codigo": "AG-0001" }
    }
  },
  "errores": null
}
```

---

## POST `/api/v1/infracciones/{id}/liberar`

Retira el candado. Se puede usar de dos formas:

1. **Sin body** — si la infracción ya está `pagada` (el webhook acreditó el pago automáticamente via `Infraccion::acreditar()`).
2. **Con `motivo`** — liberación forzada administrativa (vehículo exonerado no identificado, error, etc.).

### Body

```json
{ "motivo": "Vehículo exonerado no identificado al registrar." }
```

### Respuesta 200

```json
{
  "exito": true,
  "mensaje": "Candado retirado. Vehículo liberado.",
  "datos": {
    ...
    "inmovilizacion": {
      "estado": "liberada",
      "estado_label": "Liberada",
      "liberada_en": "2026-05-30T11:00:00+00:00"
    }
  },
  "errores": null
}
```

---

## Errores comunes

| Código | Causa |
|--------|-------|
| `401` | Sin token |
| `403` | Sin permiso o infracción de otro agente |
| `422` | `minutos_excedidos < 6`, agente suspendido, vehículo ya inmovilizado, liberar sin pago ni motivo |
| `404` | Infracción no encontrada |

---

## Curls de referencia

```bash
# Login agente
curl -X POST ${APP_URL}/api/v1/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"agente@simetsa.gob.ec","password":"password"}'
# → copiar "token"

# Registrar infracción (sin ticket visible, Art. 17.b)
curl -X POST ${APP_URL}/api/v1/infracciones \
  -H "Authorization: Bearer TU_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"placa":"ABC1234","tipo_infraccion":"sin_ticket_visible","zona_id":1,"latitud":-1.0485,"longitud":-78.5975}'

# Registrar infracción por tiempo excedido (75 min, Art. 28 → 4% SBU)
curl -X POST ${APP_URL}/api/v1/infracciones \
  -H "Authorization: Bearer TU_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"placa":"XYZ9876","tipo_infraccion":"tiempo_excedido","zona_id":1,"minutos_excedidos":75}'

# Inmovilizar vehículo
curl -X POST ${APP_URL}/api/v1/infracciones/1/inmovilizar \
  -H "Authorization: Bearer TU_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"notas":"Sin ticket. Candado colocado."}'

# Liberar candado (forzado por comisario)
curl -X POST ${APP_URL}/api/v1/infracciones/1/liberar \
  -H "Authorization: Bearer TU_TOKEN_COMISARIO" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"motivo":"Vehículo exonerado no identificado al registrar."}'

# Error: minutos_excedidos fuera de rango (< 6)
curl -X POST ${APP_URL}/api/v1/infracciones \
  -H "Authorization: Bearer TU_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"placa":"ABC1234","tipo_infraccion":"tiempo_excedido","zona_id":1,"minutos_excedidos":3}'
# → 422: "Para infracción por tiempo excedido debe indicar al menos 6 minutos (Art. 28)."

# Error: sin token
curl ${APP_URL}/api/v1/infracciones/1 -H "Accept: application/json"
# → 401
```
