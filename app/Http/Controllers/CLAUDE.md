# Convenciones de Controllers

Los controllers (web y API) son **delgados**. Su único trabajo es:

1. Autorización (vía `HasMiddleware`).
2. Recibir un Form Request validado.
3. Delegar al servicio correspondiente.
4. Convertir el resultado o la `DomainException` en una respuesta HTTP.

Toda regla de negocio vive en `app/Services` — ver `app/Services/CLAUDE.md`.

---

## Patrón base (Laravel 11)

```php
class PuntoVentaController extends Controller implements HasMiddleware
{
    public function __construct(private readonly PuntoVentaService $servicio) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:puntos_venta.ver',     only: ['index', 'show']),
            new Middleware('permission:puntos_venta.crear',   only: ['activar']),
            new Middleware('permission:puntos_venta.editar',  only: ['cambiarEstado']),
            new Middleware('permission:puntos_venta.eliminar', only: ['destroy']),
        ];
    }
}
```

- **No** usar `authorizeResource` en el constructor (incompatible con Laravel 11).
- Permisos formato `modulo.accion` (ver enum `RolSistema` y `config/simetsa_permisos.php`).
- Inyectar servicios por constructor con `private readonly`.

---

## Controller WEB

```php
public function activar(ActivacionPuntoVentaRequest $request, SolicitudPuntoVenta $solicitud): RedirectResponse
{
    try {
        $r = $this->servicio->activar($solicitud, $request->validated());
    } catch (DomainException $e) {
        return back()->with('error', $e->getMessage());
    }

    return redirect()->route('puntos-venta.show', $r['punto_venta'])
        ->with('success', 'Punto de venta activado.');
}
```

- Recibe **Form Request** (validación de formato).
- Pasa al servicio el **array `validated()`**, no el Request.
- Captura `DomainException` → `back()->with('error', $e->getMessage())`.
- Devuelve `RedirectResponse` o `View`.

---

## Controller API (móvil — Fase 9+)

```php
// app/Http/Controllers/Api/V1/PuntoVentaController.php
public function activar(ActivacionPuntoVentaRequest $request, SolicitudPuntoVenta $solicitud): JsonResponse
{
    try {
        $r = $this->servicio->activar($solicitud, $request->validated());
    } catch (DomainException $e) {
        return response()->json([
            'exito' => false, 'mensaje' => $e->getMessage(),
            'datos' => null, 'errores' => null,
        ], 422);
    }

    return response()->json([
        'exito' => true, 'mensaje' => 'Punto de venta activado.',
        'datos' => new PuntoVentaResource($r['punto_venta']), 'errores' => null,
    ]);
}
```

- Autenticación con **Sanctum**.
- **Mismo servicio** que el controller web — no duplicar lógica de negocio.
- Respuesta JSON estándar:
  ```json
  { "exito": true|false, "mensaje": "...", "datos": {...}, "errores": null|{...} }
  ```
- Códigos HTTP: **200/201** éxito, **422** regla de negocio, **401/403** auth, **404** no encontrado, **500** server.
- Usar **API Resources** (`*Resource`) para serializar; nunca devolver modelos crudos.
- Prefijo de rutas API: `/api/v1/` (ver `routes/api.php`).

---

## Reglas duras

- Si la lógica toca más de un modelo, va al **servicio** (no al controller).
- Si necesitás un dato derivado: lo pide al servicio o se calcula en un accessor del modelo — nunca en el controller.
- Si la API necesita una variante (paginación distinta, filtros adicionales), **agregar un método al servicio**, no duplicar lógica en el controller API.
- Validar **formato** en el Form Request. Validar **reglas de negocio** en el servicio con `DomainException`.
- Nombrar acciones del controller en **verbo y en español**: `activar`, `aprobarDocumentacion`, `cambiarEstado`, `rechazar`.

---

## Form Requests

- Uno por acción de escritura: `EntidadStoreRequest`, `EntidadUpdateRequest`. Para acciones específicas (activar, autorizar), su propio request: `ActivacionPuntoVentaRequest`.
- Solo validación de **formato y constraints simples** (`required`, `email`, `date`, `digits:13`, `mimes:pdf,jpg,jpeg,png`, reglas custom como `CedulaEcuatoriana`).
- No incluir reglas que dependan del estado de la base (esas van al servicio como guardas).
- `authorize()` devuelve `true` — la autorización está en `HasMiddleware` del controller.

---

## Entregables al implementar endpoints API

Al terminar cualquier endpoint o conjunto de endpoints en `routes/api.php`, además del código y los tests:

### 1. Mostrar comandos curl al final del mensaje de cierre

Bloque listo para copy-paste a la terminal, cubriendo:

- Obtención del token Sanctum (login) si aún no se mostró en el chat.
- El **happy path** del endpoint con body de ejemplo válido.
- Al menos **un caso de error esperado**: 401 sin token, 422 de validación, o 403 de ownership cuando aplique.

### 2. Actualizar o crear `docs/api/{recurso}.md`

Documentación viva en español por cada recurso de API, con:

- Tabla resumen de endpoints (método, URL, permiso requerido, descripción).
- Los mismos curls del punto 1 (happy path + errores).
- Notas de paginación, filtros, ownership, y estructura del JSON de respuesta.

Esta carpeta sobrevive entre sesiones de Claude Code y será la base del manual para integrar la app móvil en Fase 9.

### Plantilla de curls

```bash
# Login (obtener token Sanctum)
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"conductor@simetsa.gob.ec","password":"password"}'
# → Copiar el "token" del JSON de respuesta.

# GET — listado del usuario autenticado
curl http://localhost:8000/api/v1/vehiculos \
  -H "Authorization: Bearer TU_TOKEN_AQUI" \
  -H "Accept: application/json"

# POST — crear recurso
curl -X POST http://localhost:8000/api/v1/vehiculos \
  -H "Authorization: Bearer TU_TOKEN_AQUI" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"placa":"PCH1234","tipo_vehiculo_id":1,"marca":"Toyota","modelo":"Hilux","color":"Blanco","anio":2020}'

# Caso de error: 401 sin token
curl http://localhost:8000/api/v1/vehiculos \
  -H "Accept: application/json"

# Caso de error: 422 validación
curl -X POST http://localhost:8000/api/v1/vehiculos \
  -H "Authorization: Bearer TU_TOKEN_AQUI" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"placa":""}'
```

Si la base URL del entorno no es `http://localhost:8000`, usar `${APP_URL}` y aclararlo al inicio del bloque.