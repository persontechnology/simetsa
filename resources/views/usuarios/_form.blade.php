{{-- resources/views/usuarios/_form.blade.php --}}
@php
    $modo    = $modo ?? 'crear';
    $perfil  = $usuario?->perfil;
    $rolActual = $usuario?->roles?->first()?->name;
@endphp

<div class="row g-4">

    {{-- =========================================
         Sección 1: Datos de cuenta (User)
         ========================================= --}}
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-bottom-0 pt-4 px-4 pb-0">
                <h2 class="h6 text-uppercase fw-bold text-muted mb-0 title-tracking">
                    <i class="bi bi-shield-check me-2 text-dark fs-5 align-middle"></i>Datos de acceso y cuenta
                </h2>
            </div>
            <div class="card-body p-4">
                
                {{-- Nombre --}}
                <div class="mb-3">
                    <label for="name" class="form-label small fw-medium text-dark">Nombre completo <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name"
                           class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $usuario?->name) }}" placeholder="Ej. Juan Pérez" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Email --}}
                <div class="mb-3">
                    <label for="email" class="form-label small fw-medium text-dark">Correo electrónico <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" id="email"
                               class="form-control border-start-0 ps-0 @error('email') is-invalid @enderror"
                               value="{{ old('email', $usuario?->email) }}" placeholder="usuario@correo.com" required>
                    </div>
                    @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                {{-- Password (Fila combinada para optimizar espacio horizontal) --}}
                <div class="row g-3 mb-3">
                    <div class="col-12 col-sm-6">
                        <label for="password" class="form-label small fw-medium text-dark">
                            Contraseña {{ $modo === 'crear' ? '*' : '' }}
                        </label>
                        <input type="password" name="password" id="password"
                               class="form-control @error('password') is-invalid @enderror"
                               {{ $modo === 'crear' ? 'required' : '' }}
                               autocomplete="new-password" placeholder="••••••••">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12 col-sm-6">
                        <label for="password_confirmation" class="form-label small fw-medium text-dark">
                            Confirmar contraseña
                        </label>
                        <input type="password" name="password_confirmation" id="password_confirmation"
                               class="form-control"
                               {{ $modo === 'crear' ? 'required' : '' }}
                               autocomplete="new-password" placeholder="••••••••">
                    </div>
                    
                    @if($modo === 'editar')
                        <div class="col-12 mt-2">
                            <div class="p-2 bg-light rounded text-muted fs-7 border-start border-secondary border-3">
                                <i class="bi bi-info-circle me-1"></i> Dejar en blanco para mantener la clave actual del usuario.
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Bloque de Roles del sistema (múltiples) --}}
                <div class="mb-0">
                    <label class="form-label small fw-medium text-dark">Roles del sistema <span class="text-danger">*</span></label>

                    @php
                        $rolesActuales = old('roles', $usuario?->roles->pluck('name')->toArray() ?? []);
                    @endphp

                    <div class="border rounded p-3 bg-light-subtle overflow-auto @error('roles') is-invalid border-danger @enderror" style="max-height: 320px;">
                        <div class="d-flex flex-column gap-3">
                            @foreach($roles as $valor => $etiqueta)
                                <div class="form-check p-0 m-0 d-flex align-items-start gap-2">
                                    <input class="form-check-input ms-0 mt-1"
                                        type="checkbox"
                                        name="roles[]"
                                        id="rol_{{ $valor }}"
                                        value="{{ $valor }}"
                                        @checked(in_array($valor, $rolesActuales, true))>
                                    <label class="form-check-label flex-grow-1 lh-sm" for="rol_{{ $valor }}">
                                        <span class="fw-semibold text-dark d-block fs-6 mb-1">{{ $etiqueta }}</span>
                                        <span class="text-muted small d-block fs-7">
                                            @switch($valor)
                                                @case('super_admin')        Acceso total y crítico del ecosistema. @break
                                                @case('comisario')          Administración operativa y multas <span class="text-secondary fw-medium">(Art. 4, 37)</span>. @break
                                                @case('director_seguridad') Catálogos base y autorizaciones <span class="text-secondary fw-medium">(Art. 4, 36)</span>. @break
                                                @case('agente_parqueo')     Venta de tickets en calle y registro de infracciones <span class="text-secondary fw-medium">(Art. 38)</span>. @break
                                                @case('punto_venta')        Venta de tickets en local comercial <span class="text-secondary fw-medium">(Art. 31)</span>. @break
                                                @case('conductor')          Usuario final de la app móvil de parqueo <span class="text-secondary fw-medium">(Art. 41)</span>. @break
                                                @default                    Asignación de privilegios personalizados.
                                            @endswitch
                                        </span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <span class="form-text text-muted fs-7 d-block mt-2 lh-sm">
                        <i class="bi bi-info-circle me-1"></i> Es posible asignar múltiples roles simultáneos a una cuenta.
                    </span>

                    @error('roles')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    @error('roles.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    {{-- =========================================
         Sección 2: Datos personales (PerfilUsuario)
         ========================================= --}}
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-bottom-0 pt-4 px-4 pb-0">
                <h2 class="h6 text-uppercase fw-bold text-muted mb-0 title-tracking">
                    <i class="bi bi-person-lines-fill me-2 text-dark fs-5 align-middle"></i>Información Personal
                </h2>
            </div>
            <div class="card-body p-4">

                {{-- Cédula --}}
                <div class="mb-3">
                    <label for="cedula" class="form-label small fw-medium text-dark">Número de Cédula <span class="text-danger">*</span></label>
                    <input type="text" name="cedula" id="cedula"
                           class="form-control font-monospace @error('cedula') is-invalid @enderror"
                           value="{{ old('cedula', $perfil?->cedula) }}"
                           placeholder="0123456789" maxlength="10" inputmode="numeric" required>
                    <span class="form-text text-muted fs-7">Identificación ecuatoriana obligatoria a 10 dígitos.</span>
                    @error('cedula')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Teléfonos --}}
                <div class="row g-3 mb-3">
                    <div class="col-12 col-sm-6">
                        <label for="telefono_celular" class="form-label small fw-medium text-dark">Celular <span class="text-danger">*</span></label>
                        <input type="tel" name="telefono_celular" id="telefono_celular"
                               class="form-control @error('telefono_celular') is-invalid @enderror"
                               value="{{ old('telefono_celular', $perfil?->telefono_celular) }}" placeholder="Ej. 0999999999" required>
                        @error('telefono_celular')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    
                    <div class="col-12 col-sm-6">
                        <label for="telefono" class="form-label small fw-medium text-dark">Teléfono fijo</label>
                        <input type="tel" name="telefono" id="telefono"
                               class="form-control @error('telefono') is-invalid @enderror"
                               value="{{ old('telefono', $perfil?->telefono) }}" placeholder="Ej. 032444555">
                        @error('telefono')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- Dirección --}}
                <div class="mb-3">
                    <label for="direccion" class="form-label small fw-medium text-dark">Dirección domiciliaria</label>
                    <input type="text" name="direccion" id="direccion"
                           class="form-control @error('direccion') is-invalid @enderror"
                           value="{{ old('direccion', $perfil?->direccion) }}" placeholder="Calle principal, secundaria y Nro. de casa">
                    @error('direccion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Fecha Nacimiento / Género --}}
                <div class="row g-3 mb-3">
                    <div class="col-12 col-sm-6">
                        <label for="fecha_nacimiento" class="form-label small fw-medium text-dark">Fecha de nacimiento</label>
                        <input type="date" name="fecha_nacimiento" id="fecha_nacimiento"
                               class="form-control @error('fecha_nacimiento') is-invalid @enderror"
                               value="{{ old('fecha_nacimiento', $perfil?->fecha_nacimiento?->format('Y-m-d')) }}">
                        @error('fecha_nacimiento')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    
                    <div class="col-12 col-sm-6">
                        <label for="genero" class="form-label small fw-medium text-dark">Género</label>
                        <select name="genero" id="genero"
                                class="form-select @error('genero') is-invalid @enderror">
                            <option value="">— No especifica —</option>
                            @foreach($generos as $valor => $etiqueta)
                                <option value="{{ $valor }}"
                                    @selected(old('genero', $perfil?->genero) === $valor)>
                                    {{ $etiqueta }}
                                </option>
                            @endforeach
                        </select>
                        @error('genero')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- Foto de perfil --}}
                <div class="mb-0">
                    <label for="foto_perfil" class="form-label small fw-medium text-dark">Fotografía de perfil</label>
                    <input type="file" name="foto_perfil" id="foto_perfil"
                           class="form-control @error('foto_perfil') is-invalid @enderror"
                           accept="image/*">
                    
                    @if($perfil?->foto_perfil)
                        <div class="mt-2 d-inline-flex align-items-center bg-light border rounded px-3 py-2 fs-7">
                            <i class="bi bi-image text-muted me-2"></i>
                            <span class="text-secondary me-2">Existe una foto registrada.</span>
                            <a href="{{ $perfil->url_foto_perfil }}" target="_blank" class="fw-semibold text-dark text-decoration-none border-bottom border-dark">
                                Ver actual <i class="bi bi-box-arrow-up-right small"></i>
                            </a>
                        </div>
                    @endif
                    @error('foto_perfil')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Acepta términos (LOPDP) - solo en crear --}}
                @if($modo === 'crear')
                    <div class="mt-2 pt-2">
                        
                        <div class="form-check d-flex align-items-start gap-1 m-0">
                            <input class="form-check-input ms-0 mt-1 flex-shrink-0" 
                                type="checkbox"
                                name="acepta_terminos" 
                                id="acepta_terminos" 
                                value="1"
                                @checked(old('acepta_terminos', '1'))
                                style="width: 1.15rem; height: 1.15rem; cursor: pointer;">
                            
                            <label class="form-check-label text-secondary small lh-base" for="acepta_terminos" style="cursor: pointer;">
                                <span class="fw-bold text-dark d-block mb-1 fs-6">Consentimiento LOPDP</span>
                                Declarar la aceptación de términos bajo la **Ley Orgánica de Protección de Datos Personales** en nombre de este usuario. Si se desmarca, el sistema le exigirá la revisión y aceptación obligatoria en su primer inicio de sesión.
                            </label>
                        </div>
                        
                    </div>
                @endif

            </div>
        </div>
    </div>
</div>

{{-- 3. Botones de acción / Footer --}}
<div class="mt-4 d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm border border-light-subtle">
    <a href="{{ route('usuarios.index') }}" class="btn btn-light text-secondary border-light-subtle" title="Cancelar y regresar al listado">
        <i class="bi bi-arrow-left me-1"></i> Cancelar y volver
    </a>
    <button type="submit" class="btn btn-dark px-4" title="{{ $modo === 'crear' ? 'Confirmar creación' : 'Guardar actualizaciones' }}">
        <i class="bi bi-floppy me-1"></i>
        {{ $modo === 'crear' ? 'Crear usuario' : 'Guardar cambios' }}
    </button>
</div>