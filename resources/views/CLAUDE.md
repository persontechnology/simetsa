# Convenciones de Vistas Blade

- Vistas **básicas, limpias y funcionales** con **Bootstrap 5**. Nunca otro framework CSS (sin Tailwind).
- `@extends('layouts.app')` heredado de Breeze.
- Partials reutilizables (`@include('partials.xxx')`).
- Componentes Bootstrap estándar: `container`, `card`, `table`, `btn`, `form-control`, `alert`, `modal`, `navbar`.
- Formularios con validación visual (`is-invalid` + `invalid-feedback`).
- Mensajes flash con alerts en el layout:
  ```blade
  @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
  @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div>   @endif
  ```

---

## Stacks expuestos por el layout

En el layout principal `app.blade.php` existen dos stacks para cargar recursos específicos de cada vista:

- `@stack('scriptsHeader')` — ubicado dentro del `<head>`.
- `@stack('scriptsFooter')` — ubicado antes del cierre de `</body>`.

### Regla general

Toda librería, plugin o dependencia frontend específica de una vista debe cargarse en `@push('scriptsHeader')`.

Toda lógica propia de la vista debe escribirse en `@push('scriptsFooter')`.

Esto aplica para cualquier plugin o librería, por ejemplo:

- Leaflet.
- MarkerCluster.
- InputTags.
- Select2.
- DataTables.
- Flatpickr.
- Chart.js.
- Cualquier otro plugin JS usado por una vista.

### Uso de `scriptsHeader`

Usar `scriptsHeader` para cargar archivos necesarios antes de ejecutar la lógica de la vista:

```blade
@push('scriptsHeader')
<script src="{{ asset('assets/js/vendor/plugin/plugin.min.js') }}"></script>
@endpush
```

En `scriptsHeader` pueden ir:

- Archivos JS de librerías o plugins.
- Archivos CSS requeridos por plugins, si el proyecto los carga desde este stack.
- Dependencias externas requeridas por la vista.

No debe ir en `scriptsHeader`:

- Inicialización de componentes.
- Eventos del DOM.
- AJAX.
- Validaciones propias de la vista.
- Lógica de negocio del frontend.
- Código que dependa de elementos HTML ya renderizados.

### Uso de `scriptsFooter`

Usar `scriptsFooter` para la lógica propia de la vista:

```blade
@push('scriptsFooter')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Inicialización del plugin y lógica propia de la vista
});
</script>
@endpush
```

En `scriptsFooter` debe ir:

- Inicialización de plugins.
- Configuración de mapas.
- Eventos `click`, `change`, `submit`, etc.
- AJAX.
- Validaciones frontend.
- Lógica de negocio de la vista.
- Código que dependa de elementos HTML presentes en el DOM.

No poner `<script>` inline fuera de `@push('scriptsFooter')`, salvo que sea estrictamente necesario y esté justificado.

No duplicar la carga de librerías/plugins si ya fueron incluidos en la vista, en el layout o en un partial reutilizable.

### Ejemplo con Leaflet

```blade
@push('scriptsHeader')
<script src="{{ asset('assets/js/vendor/maps/leaflet/leaflet.min.js') }}"></script>
<script src="{{ asset('assets/js/vendor/maps/leaflet/plugins/markercluster.min.js') }}"></script>
@endpush

@push('scriptsFooter')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const mapa = L.map('mapaPunto').setView([lat, lng], 17);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap',
        maxZoom: 19,
    }).addTo(mapa);

    L.marker([lat, lng]).addTo(mapa);
});
</script>
@endpush
```

### Ejemplo con InputTags

```blade
@push('scriptsHeader')
<script src="{{ asset('assets/js/vendor/forms/tags/inputtags.min.js') }}"></script>
@endpush

@push('scriptsFooter')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.querySelector('#tags');

    if (input) {
        new InputTags(input, {
            // Configuración del plugin
        });
    }
});
</script>
@endpush
```

---

## Autorización en vistas

- `@can('modulo.accion') ... @endcan` para botones y secciones gateadas.
- `@canany(['agentes.ver', 'puntos_venta.ver']) ... @endcanany` para dropdowns que aplican a varios módulos.

---

## Botones de eliminar / desactivar

En todos los módulos, los botones para eliminar, desactivar o ejecutar acciones destructivas deben seguir el formato global del proyecto.

Reglas obligatorias:

- La acción debe estar protegida con `@can(...)` usando el permiso correspondiente del módulo.
- La acción debe enviarse mediante un formulario `POST` con `@csrf` y `@method('DELETE')`.
- El formulario debe usar `class="d-inline"` para mantener la alineación de botones en tablas, cards o listados.
- El botón debe usar la clase visual global `btn btn-sm btn-outline-danger`.
- El botón debe incluir:
  - `type="submit"`
  - `title="Desactivar"` o el texto equivalente según la acción.
  - `data-confirm`
  - `data-action="desactivar"` o la acción correspondiente.
  - `data-msg="..."` con un mensaje claro de confirmación.
- El ícono estándar para esta acción es `<i class="bi bi-trash"></i>`.
- No crear botones destructivos con enlaces `<a>` directos.
- No ejecutar eliminaciones, desactivaciones o borrados mediante GET.
- No omitir la confirmación global mediante `data-confirm`.

Ejemplo base:

```blade
@can('tipos_plaza.eliminar')
    <form method="POST" action="{{ route('tipos-plaza.destroy', $t) }}" class="d-inline">
        @csrf
        @method('DELETE')

        <button type="submit"
                class="btn btn-sm btn-outline-danger"
                title="Desactivar"
                data-confirm
                data-action="desactivar"
                data-msg="¿Desactivar el tipo {{ $t->nombre }}?">
            <i class="bi bi-trash"></i>
        </button>
    </form>
@endcan
```

Cuando se implemente este patrón en otro módulo, adaptar únicamente:

- El permiso de `@can`.
- La ruta del formulario.
- La variable del modelo.
- El texto del `title`.
- El valor de `data-action`.
- El mensaje de `data-msg`.

La estructura general del formulario, método, clases, atributos `data-*` e ícono debe mantenerse igual en todos los módulos.

---

## Edición de sub-registros con modales compartidos

Cuando una entidad tiene tablas de sub-registros editables en una página, por ejemplo el expediente del agente con asignaciones, horarios y amonestaciones, usamos **un modal compartido por sección + JS que se autopobla** desde `data-*` y `data-url`:

```blade
{{-- Botón "Editar" en cada fila de la tabla --}}
<button type="button" class="btn btn-sm btn-outline-primary btn-editar-asignacion"
    data-bs-toggle="modal" data-bs-target="#modalEditarAsignacion"
    data-url="{{ route('asignaciones-zona.update', $asig) }}"
    data-zona-id="{{ $asig->zona_id }}"
    data-fecha-inicio="{{ $asig->fecha_inicio?->format('Y-m-d') }}">
    <i class="bi bi-pencil"></i>
</button>

{{-- Modal único por sección --}}
<div class="modal fade" id="modalEditarAsignacion" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" id="formEditarAsignacion" class="modal-content">
      @csrf @method('PATCH')
      <input type="hidden" id="editAsigZona" name="zona_id">
      <input type="date" id="editAsigInicio" name="fecha_inicio">
      {{-- ... otros campos ... --}}
    </form>
  </div>
</div>

@push('scriptsFooter')
<script>
document.querySelectorAll('.btn-editar-asignacion').forEach(b => b.addEventListener('click', () => {
    document.getElementById('formEditarAsignacion').action = b.dataset.url;
    document.getElementById('editAsigZona').value = b.dataset.zonaId;
    document.getElementById('editAsigInicio').value = b.dataset.fechaInicio || '';
}));
</script>
@endpush
```

- Los campos del modal tienen **`id` único** por sección (`editAsigZona`, `editHorDia`, etc.).
- El JS debe poblar los campos por `id`, no por `name`, para evitar conflictos con campos ocultos, especialmente en patrones como hidden + checkbox de `activa`.
- `form.action` se setea dinámicamente desde `data-url` para que un mismo modal sirva a todas las filas.
- La lógica JavaScript de estos modales debe ir en `@push('scriptsFooter')`.

---

## Mapas con Leaflet + OpenStreetMap

Para mapas se debe usar Leaflet con OpenStreetMap respetando la convención global de carga de plugins:

- Leaflet y sus plugins van en `@push('scriptsHeader')`.
- La lógica de inicialización del mapa va en `@push('scriptsFooter')`.
- No inicializar mapas en `scriptsHeader`.
- No mezclar lógica de negocio con la carga de librerías.
- No duplicar la carga de Leaflet o sus plugins en una misma vista.
- Inicializar los mapas con `DOMContentLoaded` para asegurar que el contenedor del mapa ya exista en el DOM.

Ejemplo de carga de Leaflet y plugins:

```blade
@push('scriptsHeader')
<script src="{{ asset('assets/js/vendor/maps/leaflet/leaflet.min.js') }}"></script>
<script src="{{ asset('assets/js/vendor/maps/leaflet/plugins/markercluster.min.js') }}"></script>
@endpush
```

Ejemplo de inicialización del mapa:

```blade
@push('scriptsFooter')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const mapa = L.map('mapaPunto').setView([lat, lng], 17);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap',
        maxZoom: 19,
    }).addTo(mapa);

    L.marker([lat, lng]).addTo(mapa);
});
</script>
@endpush
```

Para editores o mapas reutilizables, priorizar los partials existentes:

- `partials.mapa-editor`: para mapas con polígonos, polilíneas, zonas, calles o manzanas.
- `partials.mapa-punto`: para mapas de puntos simples como plazas, ubicaciones o puntos de venta.

Antes de crear una nueva implementación de mapa, revisar si alguno de estos partials ya cubre el caso de uso.

---

## Convención de campos booleanos en formularios

Para no perder el valor cuando el checkbox no está marcado, usar el patrón **hidden + checkbox** con el mismo `name`:

```blade
<input type="hidden" name="activa" value="0">
<input class="form-check-input" type="checkbox" id="editAsigActiva" name="activa" value="1">
```

- El input hidden debe ir antes del checkbox.
- El checkbox debe mantener el mismo `name` que el hidden.
- En modales compartidos, asignar un `id` único al checkbox para poder manipularlo correctamente desde JavaScript.