# API — Infracciones y Candado Inmovilizador

**Fases 7.C y 7.D** | Arts. 15, 17, 18, 28, 29, 30 — Ordenanza SIMETSA

Dos actores consumen estos endpoints: el **agente en calle** (registro y candado) y el **conductor** (historial y pago de multa).

---

## Resumen de endpoints

### Agente de parqueo

| Método | URL | Permiso | Descripción |
|--------|-----|---------|-------------|
| `POST` | `/api/v1/infracciones` | `infracciones.registrar` | Registra una nueva infracción y calcula la multa automáticamente |
| `GET`  | `/api/v1/infracciones/{id}` | `infracciones.ver` | Detalle de una infracción (con inmovilización embebida) |
| `POST` | `/api/v1/infracciones/{id}/inmovilizar` | `inmovilizaciones.aplicar` | Coloca el candado inmovilizador (Art. 15) |
| `POST` | `/api/v1/infracciones/{id}/liberar` | `inmovilizaciones.retirar` | Retira el candado tras pago o por motivo administrativo |

### Conductor

| Método | URL | Permiso | Descripción |
|--------|-----|---------|-------------|
| `GET`  | `/api/v1/conductor/infracciones` | `infracciones.ver` | Historial paginado de infracciones propias (por placa o conductor_id) |
| `POST` | `/api/v1/infracciones/{id}/pagar` | `infracciones.ver` | Inicia el pago de una multa vía gateway (devuelve URL/QR de pago) |

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

---

## Endpoints del conductor (Fase 7.D)

### GET `/api/v1/conductor/infracciones`

Devuelve el historial paginado de infracciones del conductor autenticado. Incluye:
- Infracciones donde `conductor_id` coincide con el conductor.
- Infracciones donde la placa coincide con alguno de sus vehículos registrados.

**Ownership:** el conductor solo ve las suyas. El resultado es una colección paginada (15 por página).

#### Respuesta 200

```json
{
  "exito": true,
  "mensaje": "Historial de infracciones.",
  "datos": [
    {
      "id": 1,
      "placa": "ABC1234",
      "tipo_infraccion": "sin_ticket_visible",
      "tipo_label": "Sin ticket visible (Art. 17.b)",
      "estado": "pendiente",
      "estado_label": "Pendiente de pago",
      "estado_color": "warning",
      "monto_multa": "9.20",
      "registrada_en": "2026-05-30T10:30:00+00:00",
      "inmovilizacion": {
        "estado": "activa",
        "inmovilizada_en": "2026-05-30T10:35:00+00:00"
      }
    }
  ],
  "errores": null
}
```

---

### POST `/api/v1/infracciones/{id}/pagar`

Inicia el pago de una multa a través del gateway indicado. El conductor solo puede pagar multas de sus propios vehículos (por placa registrada o `conductor_id`).

El flujo completo es:
1. Conductor llama a este endpoint → se crea una `TransaccionPago` y se retorna la URL/QR de pago.
2. El conductor completa el pago en el gateway externo.
3. El gateway llama al webhook `POST /api/v1/pagos/webhook/deuna` → `Infraccion::acreditar()` → estado `pagada` + candado `liberado` (Art. 15).

#### Body

```json
{ "proveedor": "deuna" }
```

#### Respuesta 201

```json
{
  "exito": true,
  "mensaje": "Pago iniciado. Completa el pago en el gateway.",
  "datos": {
    "transaccion_id": 5,
    "estado": "pendiente",
    "monto": 9.20,
    "moneda": "USD",
    "payment_url": "https://pay.deuna.com/order/abc123",
    "qr_payload": null,
    "external_reference": "SIMETSA-INF-1-1748596200"
  },
  "errores": null
}
```

#### Curls del conductor

```bash
# Login conductor
curl -X POST ${APP_URL}/api/v1/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"conductor@simetsa.gob.ec","password":"password"}'

# Historial de infracciones propias
curl ${APP_URL}/api/v1/conductor/infracciones \
  -H "Authorization: Bearer TU_TOKEN_CONDUCTOR" \
  -H "Accept: application/json"

# Pagar multa propia
curl -X POST ${APP_URL}/api/v1/infracciones/1/pagar \
  -H "Authorization: Bearer TU_TOKEN_CONDUCTOR" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"proveedor":"deuna"}'

# Simular confirmación de pago (webhook Deuna)
curl -X POST ${APP_URL}/api/v1/pagos/webhook/deuna \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"order_id":"SIMETSA-INF-1-1748596200","status":"COMPLETED"}'

# Error: intentar pagar multa de otro vehículo
curl -X POST ${APP_URL}/api/v1/infracciones/99/pagar \
  -H "Authorization: Bearer TU_TOKEN_CONDUCTOR" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"proveedor":"deuna"}'
# → 422: "No puede pagar una multa que no corresponde a sus vehículos."
```
