{{-- resources/views/roles/_form.blade.php --}}
{{--
    Formulario reutilizable para crear/editar roles.

    Variables esperadas:
      $rol                    ?Role     Rol existente o null al crear.
      $catalogoPermisos       array     config('simetsa_permisos').
      $permisosSeleccionados  array     Lista de nombres de permisos ya asignados.
      $totalesPorModulo       array     Conteo total de permisos por módulo.
      $esRolDelSistema        bool      Si es uno de los 6 del Enum.
      $deshabilitarPermisos   bool      true para super_admin (no se editan permisos).
      $modo                   string    'crear' o 'editar'.
--}}
@php $modo = $modo ?? 'crear'; @endphp

<div class="row g-4">

    {{-- ===== Datos del rol ===== --}}
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm mb-4 mb-lg-0">
            <div class="card-body p-4">
                <h2 class="h6 text-dark fw-bold text-uppercase mb-4 title-tracking">
                    <i class="bi bi-info-circle me-2 text-secondary align-middle"></i>Datos del rol
                </h2>

                <div class="mb-3">
                    <label for="name" class="form-label small fw-medium text-dark">Nombre del rol <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name"
                           class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $rol?->name) }}"
                           {{ $esRolDelSistema ? 'readonly' : '' }}
                           placeholder="ej_rol_usuario"
                           required>
                    
                    @if($esRolDelSistema)
                        <div class="form-text text-warning d-flex align-items-start gap-1 mt-2">
                            <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                            <span>Los roles del sistema no permiten cambiar el nombre. Solo se editan sus permisos correspondientes.</span>
                        </div>
                    @else
                        <span class="form-text text-muted fs-7 d-block mt-1.5">
                            Formato obligatorio: minúsculas, números y guiones bajos (snake_case).
                        </span>
                    @endif
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                @if($deshabilitarPermisos)
                    <div class="p-3 bg-warning-subtle text-warning-heading border-start border-warning border-3 rounded small mb-0 mt-3">
                        <i class="bi bi-shield-fill-exclamation me-1.5"></i>
                        El rol <strong>super_admin</strong> posee acceso total e incondicional al ecosistema. Su asignación de permisos no es modificable desde la interfaz de usuario.
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ===== Asignación de permisos ===== --}}
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
                    <h2 class="h6 text-dark fw-bold text-uppercase mb-0 title-tracking">
                        <i class="bi bi-key me-2 text-secondary align-middle"></i>Permisos asignados
                    </h2>
                    @unless($deshabilitarPermisos)
                        <div class="d-flex gap-1.5">
                            <button type="button" class="btn btn-sm btn-light border border-light-subtle text-secondary" id="btn-expandir-todos">
                                <i class="bi bi-arrows-expand me-1"></i> Expandir todos
                            </button>
                            <button type="button" class="btn btn-sm btn-light border border-light-subtle text-secondary" id="btn-colapsar-todos">
                                <i class="bi bi-arrows-collapse me-1"></i> Colapsar todos
                            </button>
                        </div>
                    @endunless
                </div>

                <div class="permission-container @if($deshabilitarPermisos) opacity-50 @endif">

                    @foreach($catalogoPermisos as $modulo => $entidades)
                        <div class="card border border-light-subtle mb-3 modulo-permisos shadow-xs" data-modulo="{{ $modulo }}">
                            
                            {{-- Cabecera del Módulo --}}
                            <div class="card-header d-flex justify-content-between align-items-center bg-light-subtle py-2.5 px-3 modulo-toggle collapsed"
                                 data-bs-toggle="collapse"
                                 data-bs-target="#collapse-{{ $modulo }}"
                                 style="cursor: pointer; user-select: none;">
                                <span class="fw-semibold text-dark d-flex align-items-center">
                                    <i class="bi bi-chevron-right me-2 text-muted chevron-icon transition-transform"></i>
                                    {{ ucfirst(str_replace('_', ' ', $modulo)) }}
                                </span>
                                <span class="badge bg-dark text-white rounded-pill px-2.5 py-1 font-monospace fs-7 contador-modulo"
                                      data-total="{{ $totalesPorModulo[$modulo] ?? 0 }}">
                                    0 / {{ $totalesPorModulo[$modulo] ?? 0 }}
                                </span>
                            </div>

                            {{-- Cuerpo del Módulo --}}
                            <div id="collapse-{{ $modulo }}" class="collapse">
                                <div class="card-body p-3 bg-white">

                                    @unless($deshabilitarPermisos)
                                        <div class="d-flex gap-2 mb-3 pb-2 border-bottom border-light">
                                            <button type="button" class="btn btn-xs btn-light text-dark border-light-subtle btn-marcar-modulo">
                                                <i class="bi bi-check2-all me-1"></i> Seleccionar todos
                                            </button>
                                            <button type="button" class="btn btn-xs btn-light text-danger border-light-subtle btn-desmarcar-modulo">
                                                <i class="bi bi-x-circle me-1"></i> Deseleccionar todos
                                            </button>
                                        </div>
                                    @endunless

                                    @foreach($entidades as $entidad => $acciones)
                                        <div class="mb-3 last-mb-0">
                                            <div class="fw-semibold text-secondary small text-uppercase mb-2 tracking-wider fs-7">
                                                {{ $entidad }}
                                            </div>
                                            <div class="d-flex flex-wrap gap-3">
                                                @foreach($acciones as $accion)
                                                    @php($permiso = "{$entidad}.{$accion}")
                                                    <div class="form-check d-flex align-items-center gap-1.5 m-0">
                                                        <input class="form-check-input mt-0 permiso-check"
                                                               type="checkbox"
                                                               name="permisos[]"
                                                               id="perm_{{ $permiso }}"
                                                               value="{{ $permiso }}"
                                                               @checked(in_array($permiso, old('permisos', $permisosSeleccionados), true))
                                                               @disabled($deshabilitarPermisos)
                                                               style="cursor: pointer;">
                                                        <label class="form-check-label text-dark small" for="perm_{{ $permiso }}" style="cursor: pointer;">
                                                            {{ $accion }}
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach

                                </div>
                            </div>

                        </div>
                    @endforeach

                </div>

                @error('permisos')<div class="text-danger small mt-2"><i class="bi bi-x-circle me-1"></i> {{ $message }}</div>@enderror
                @error('permisos.*')<div class="text-danger small mt-2"><i class="bi bi-x-circle me-1"></i> {{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>

{{-- Botones de acción / Footer --}}
<div class="mt-4 d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm border border-light-subtle">
    <a href="{{ route('roles.index') }}" class="btn btn-light text-secondary border-light-subtle" title="Cancelar y regresar al listado">
        <i class="bi bi-arrow-left me-1"></i> Cancelar y volver
    </a>
    <button type="submit" class="btn btn-dark px-4" title="{{ $modo === 'crear' ? 'Confirmar creación' : 'Guardar actualizaciones' }}">
        <i class="bi bi-floppy me-1"></i>
        {{ $modo === 'crear' ? 'Crear rol' : 'Guardar cambios' }}
    </button>
</div>



@push('scriptsFooter')
<script>
document.addEventListener('DOMContentLoaded', function () {

    /**
     * Recalcula el contador del módulo dado.
     * @param {HTMLElement} modulo
     */
    function actualizarContador(modulo) {
        const checks   = modulo.querySelectorAll('.permiso-check');
        const marcados = modulo.querySelectorAll('.permiso-check:checked').length;
        const contador = modulo.querySelector('.contador-modulo');
        
        contador.textContent = `${marcados} / ${checks.length}`;
        
        if (marcados > 0 && marcados === checks.length) {
            contador.className = "badge bg-success text-white rounded-pill px-2.5 py-1 font-monospace fs-7 contador-modulo";
        } else if (marcados > 0) {
            contador.className = "badge bg-warning text-dark rounded-pill px-2.5 py-1 font-monospace fs-7 contador-modulo";
        } else {
            contador.className = "badge bg-secondary text-white rounded-pill px-2.5 py-1 font-monospace fs-7 contador-modulo";
        }
    }

    document.querySelectorAll('.modulo-permisos').forEach(modulo => {
        actualizarContador(modulo);

        modulo.querySelectorAll('.permiso-check').forEach(check => {
            check.addEventListener('change', () => actualizarContador(modulo));
        });

        const btnMarcar = modulo.querySelector('.btn-marcar-modulo');
        if (btnMarcar) {
            btnMarcar.addEventListener('click', (e) => {
                e.stopPropagation();
                modulo.querySelectorAll('.permiso-check').forEach(c => c.checked = true);
                actualizarContador(modulo);
            });
        }

        const btnDesmarcar = modulo.querySelector('.btn-desmarcar-modulo');
        if (btnDesmarcar) {
            btnDesmarcar.addEventListener('click', (e) => {
                e.stopPropagation();
                modulo.querySelectorAll('.permiso-check').forEach(c => c.checked = false);
                actualizarContador(modulo);
            });
        }
    });

    // Botones globales expandir/colapsar sin romper instancias de Bootstrap 5
    document.getElementById('btn-expandir-todos')?.addEventListener('click', () => {
        document.querySelectorAll('.modulo-permisos .collapse').forEach(c => {
            const instance = bootstrap.Collapse.getOrCreateInstance(c, { toggle: false });
            instance.show();
        });
    });
    document.getElementById('btn-colapsar-todos')?.addEventListener('click', () => {
        document.querySelectorAll('.modulo-permisos .collapse').forEach(c => {
            const instance = bootstrap.Collapse.getOrCreateInstance(c, { toggle: false });
            instance.hide();
        });
    });
});
</script>
@endpush