# API — Tipos de Vehículo

> **Base legal:** Art. 25 Ordenanza SIMETSA — catálogo de tipos de vehículo utilizados al registrar un vehículo de conductor.

Base URL: `${APP_URL}/api/v1` (desarrollo: `http://192.168.1.58:8000/api/v1`)

---

## Endpoints

| Método | URL | Auth | Permiso | Descripción |
|--------|-----|------|---------|-------------|
| GET | `/tipos-vehiculo` | Sanctum Bearer | ninguno específico¹ | Lista todos los tipos activos |

¹ Cualquier usuario autenticado con token Sanctum puede leer el catálogo (conductores, agentes, puntos de venta). No se requiere permiso `tipos_vehiculo.ver` en la API móvil.

---

## Estructura de respuesta

```json
{
  "exito": true,
  "mensaje": "Tipos de vehículo disponibles.",
  "datos": [
    {
      "id": 1,
      "codigo": "liviano_privado",
      "nombre": "Liviano privado",
      "descripcion": "Vehículo de uso personal o familiar.",
      "aplica_tarifa": true
    }
  ],
  "errores": null
}
```

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | integer | Identificador del tipo |
| `codigo` | string | Clave de negocio (snake_case, invariable) |
| `nombre` | string | Etiqueta legible para mostrar al usuario |
| `descripcion` | string\|null | Descripción ampliada |
| `aplica_tarifa` | boolean | `false` indica exoneración de pago (ej. institucional) |

### Códigos de tipo disponibles

| `codigo` | Nombre | `aplica_tarifa` |
|----------|--------|-----------------|
| `liviano_privado` | Liviano privado | `true` |
| `liviano_publico` | Liviano público | `true` |
| `taxi` | Taxi / ejecutivo | `true` |
| `furgoneta` | Furgoneta | `true` |
| `carga_liviana` | Carga liviana | `true` |
| `institucional` | Institucional | `false` |

---

## Curls

```bash
# ── 1. Obtener token (login) ──────────────────────────────────────────────────
curl -s -X POST http://192.168.1.58:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"conductor@simetsa.gob.ec","password":"password"}' \
  | jq '.datos.token'
# → Copiar el valor del token y asignarlo a TOKEN

TOKEN="<token_aqui>"

# ── 2. Happy path — lista de tipos activos ────────────────────────────────────
curl -s http://192.168.1.58:8000/api/v1/tipos-vehiculo \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  | jq .

# ── 3. Error: 401 sin token ───────────────────────────────────────────────────
curl -s http://192.168.1.58:8000/api/v1/tipos-vehiculo \
  -H "Accept: application/json" \
  | jq .
# → {"message":"Unauthenticated."} con HTTP 401
```

---

## Notas

- **Solo activos:** el endpoint filtra `activo = true`; los tipos desactivados por el administrador no aparecen.
- **Sin paginación:** la lista es pequeña y estática; se devuelve completa.
- **Uso típico en la app móvil:** llamar al montar el formulario de registro de vehículo para poblar el selector de tipo.
