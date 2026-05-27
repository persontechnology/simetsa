# Convenciones de Controllers

Los controllers web y API son **delgados**. Su único trabajo es:

1. Autorización mediante middleware en el constructor.
2. Recibir un Form Request validado.
3. Delegar al servicio correspondiente.
4. Convertir el resultado o la `DomainException` en una respuesta HTTP.

Toda regla de negocio vive en `app/Services` — ver `app/Services/CLAUDE.md`.

---

## Patrón base de middleware

En este proyecto, los permisos se registran en el constructor del controller usando `$this->middleware(...)->only(...)`.

No usar `HasMiddleware` ni el método estático `middleware()`.

Patrón correcto:

```php
class CalleController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:calles.ver')->only('index');
        $this->middleware('permission:calles.crear')->only(['create', 'store']);
        $this->middleware('permission:calles.editar')->only(['edit', 'update']);
        $this->middleware('permission:calles.eliminar')->only('destroy');
    }
}
```

Si el controller necesita un servicio, inyectarlo en el mismo constructor y mantener ahí los middleware:

```php
class PuntoVentaController extends Controller
{
    public function __construct(private readonly PuntoVentaService $servicio)
    {
        $this->middleware('permission:puntos_venta.ver')->only(['index', 'show']);
        $this->middleware('permission:puntos_venta.crear')->only(['create', 'store', 'activar']);
        $this->middleware('permission:puntos_venta.editar')->only(['edit', 'update', 'cambiarEstado']);
        $this->middleware('permission:puntos_venta.eliminar')->only('destroy');
    }
}
```

Reglas:

- Usar permisos con formato `modulo.accion`.
- Usar `$this->middleware('permission:...')->only(...)`.
- Agrupar acciones relacionadas en arrays cuando aplique: `['create', 'store']`, `['edit', 'update']`.
- No usar `HasMiddleware`.
- No usar `public static function middleware(): array`.
- No usar `new Middleware(...)` en controllers.
- No usar `authorizeResource` en el constructor.
- Inyectar servicios por constructor con `private readonly` cuando el controller necesite delegar lógica de negocio.

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

Reglas:

- Recibe **Form Request** para validación de formato.
- Pasa al servicio el array `$request->validated()`, no el Request completo.
- Captura `DomainException` y responde con `back()->with('error', $e->getMessage())`.
- Devuelve `RedirectResponse` o `View`.
- No poner reglas de negocio en el controller.
- No consultar múltiples modelos desde el controller para resolver una regla de negocio; eso va en el servicio.

---

## Controller API móvil — Fase 9+

```php
// app/Http/Controllers/Api/V1/PuntoVentaController.php
public function activar(ActivacionPuntoVentaRequest $request, SolicitudPuntoVenta $solicitud): JsonResponse
{
    try {
        $r = $this->servicio->activar($solicitud, $request->validated());
    } catch (DomainException $e) {
        return response()->json([
            'exito' => false,
            'mensaje' => $e->getMessage(),
            'datos' => null,
            'errores' => null,
        ], 422);
    }

    return response()->json([
        'exito' => true,
        'mensaje' => 'Punto de venta activado.',
        'datos' => new PuntoVentaResource($r['punto_venta']),
        'errores' => null,
    ]);
}
```

Reglas:

- Autenticación con **Sanctum**.
- Usar el **mismo servicio** que el controller web.
- No duplicar lógica de negocio en el controller API.
- Respuesta JSON estándar:

```json
{
  "exito": true,
  "mensaje": "...",
  "datos": {},
  "errores": null
}
```

Códigos HTTP:

- `200` / `201` para éxito.
- `422` para regla de negocio.
- `401` / `403` para autenticación o autorización.
- `404` para recurso no encontrado.
- `500` para error de servidor.

Reglas adicionales:

- Usar **API Resources** (`*Resource`) para serializar respuestas.
- Nunca devolver modelos crudos en respuestas API.
- Prefijo de rutas API: `/api/v1/` en `routes/api.php`.

---

## Reglas duras

- Si la lógica toca más de un modelo, va al **servicio**, no al controller.
- Si se necesita un dato derivado, pedirlo al servicio o calcularlo en un accessor del modelo.
- Nunca calcular datos derivados complejos directamente en el controller.
- Si la API necesita una variante, como paginación distinta o filtros adicionales, agregar un método al servicio.
- No duplicar lógica entre controller web y controller API.
- Validar **formato** en el Form Request.
- Validar **reglas de negocio** en el servicio usando `DomainException`.
- Nombrar acciones del controller en **verbo y en español**: `activar`, `aprobarDocumentacion`, `cambiarEstado`, `rechazar`.

---

## Form Requests

- Usar un Form Request por acción de escritura.
- Para CRUD estándar:
  - `EntidadStoreRequest`
  - `EntidadUpdateRequest`
- Para acciones específicas, crear su propio request:
  - `ActivacionPuntoVentaRequest`
  - `CambioEstadoRequest`
  - `AprobacionDocumentacionRequest`

Los Form Requests deben contener solo validación de formato y constraints simples, por ejemplo:

- `required`
- `email`
- `date`
- `digits:13`
- `mimes:pdf,jpg,jpeg,png`
- reglas custom como `CedulaEcuatoriana`

Reglas:

- No incluir reglas que dependan del estado de la base de datos.
- Las reglas de negocio que dependen del estado actual van al servicio como guardas.
- `authorize()` debe devolver `true`.
- La autorización real está en el middleware del controller mediante `$this->middleware('permission:...')`.