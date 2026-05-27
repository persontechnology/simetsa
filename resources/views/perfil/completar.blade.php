{{-- resources/views/perfil/completar.blade.php --}}
@extends('layouts.app')


@section('content')

    {{-- Banner de bienvenida en primera carga --}}
    @if($esPrimeraVez)
        <div class="alert alert-info d-flex align-items-start">
            <i class="bi bi-info-circle-fill me-2 mt-1"></i>
            <div>
                <strong>Bienvenido al SIMETSA.</strong>
                Antes de usar el sistema debes completar tus datos personales y
                aceptar los términos del tratamiento de datos (LOPDP, Art. 7).
                Esta información se usa exclusivamente para la operación del
                Sistema Municipal de Estacionamiento Tarifado del Cantón Salcedo.
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('perfil.actualizar') }}"
          enctype="multipart/form-data">
        @csrf
        @method('PATCH')

        <div class="row g-4">

            {{-- ===== Foto de perfil ===== --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <h2 class="h6 text-primary mb-3">
                            <i class="bi bi-image me-1"></i> Foto de perfil
                        </h2>

                        @if($perfil?->foto_perfil)
                            <img src="{{ $perfil->url_foto_perfil }}"
                                 alt="Foto de perfil"
                                 class="rounded-circle mb-2"
                                 style="width: 120px; height: 120px; object-fit: cover;">
                                 
                        @else
                            <div class="d-inline-flex align-items-center justify-content-center
                                        bg-light rounded-circle mb-2"
                                 style="width: 120px; height: 120px;">
                                <i class="bi bi-person text-muted" style="font-size: 4rem;"></i>
                            </div>
                        @endif

                        <input type="file" name="foto_perfil" id="foto_perfil"
                               class="form-control @error('foto_perfil') is-invalid @enderror"
                               accept="image/*">
                        <small class="form-text text-muted">Opcional. Máximo 2 MB.</small>
                        @error('foto_perfil')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- Resumen de cuenta --}}
                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-body small">
                        <h2 class="h6 text-primary mb-2">
                            <i class="bi bi-person-badge me-1"></i> Datos de cuenta
                        </h2>
                        <p class="mb-1"><strong>{{ auth()->user()->name }}</strong></p>
                        <p class="text-muted mb-2">{{ auth()->user()->email }}</p>
                        <a href="{{ route('profile.edit') }}" class="text-decoration-none">
                            <i class="bi bi-pencil"></i> Editar nombre / email / contraseña
                        </a>
                    </div>
                </div>
            </div>

            {{-- ===== Datos personales ===== --}}
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h6 text-primary mb-3">
                            <i class="bi bi-card-list me-1"></i> Mis datos personales
                        </h2>

                        {{-- Cédula --}}
                        <div class="mb-3">
                            <label for="cedula" class="form-label">Cédula *</label>
                            <input type="text" name="cedula" id="cedula"
                                   class="form-control @error('cedula') is-invalid @enderror"
                                   value="{{ old('cedula', $perfil?->cedula) }}"
                                   maxlength="10" inputmode="numeric" required>
                            <small class="form-text text-muted">
                                10 dígitos — Cédula ecuatoriana.
                            </small>
                            @error('cedula')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row g-2">
                            <div class="col-md-6 mb-3">
                                <label for="telefono_celular" class="form-label">Celular *</label>
                                <input type="tel" name="telefono_celular" id="telefono_celular"
                                       class="form-control @error('telefono_celular') is-invalid @enderror"
                                       value="{{ old('telefono_celular', $perfil?->telefono_celular) }}"
                                       required>
                                @error('telefono_celular')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="telefono" class="form-label">Teléfono fijo</label>
                                <input type="tel" name="telefono" id="telefono"
                                       class="form-control @error('telefono') is-invalid @enderror"
                                       value="{{ old('telefono', $perfil?->telefono) }}">
                                @error('telefono')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="direccion" class="form-label">Dirección</label>
                            <input type="text" name="direccion" id="direccion"
                                   class="form-control @error('direccion') is-invalid @enderror"
                                   value="{{ old('direccion', $perfil?->direccion) }}">
                            @error('direccion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row g-2">
                            <div class="col-md-6 mb-3">
                                <label for="fecha_nacimiento" class="form-label">Fecha de nacimiento</label>
                                <input type="date" name="fecha_nacimiento" id="fecha_nacimiento"
                                       class="form-control @error('fecha_nacimiento') is-invalid @enderror"
                                       value="{{ old('fecha_nacimiento', $perfil?->fecha_nacimiento?->format('Y-m-d')) }}">
                                @error('fecha_nacimiento')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="genero" class="form-label">Género</label>
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
                    </div>
                </div>

                {{-- ===== Consentimiento LOPDP ===== --}}
                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-body">
                        <h2 class="h6 text-primary mb-3">
                            <i class="bi bi-shield-check me-1"></i>
                            Tratamiento de datos personales (LOPDP)
                        </h2>

                        @if($yaConsintio)
                            {{-- Ya consintió: solo informativo --}}
                            <div class="alert alert-success small mb-0">
                                <i class="bi bi-check-circle-fill me-1"></i>
                                Aceptaste los términos de tratamiento de datos el
                                <strong>{{ $perfil->fecha_aceptacion_terminos?->format('d/m/Y H:i') }}</strong>.
                                Tu consentimiento queda registrado conforme al Art. 7 de la LOPDP.
                            </div>
                        @else
                            {{-- Primera vez: checkbox obligatorio --}}
                            <div class="bg-light p-3 rounded mb-3 small">
                                <p class="mb-2">
                                    Tus datos personales serán tratados por el GAD Municipal de Salcedo
                                    con la finalidad exclusiva de operar el Sistema Municipal de
                                    Estacionamiento Tarifado (SIMETSA), incluyendo: identificación
                                    del usuario, emisión de tickets, registro de infracciones,
                                    notificaciones de servicio y liquidaciones.
                                </p>
                                <p class="mb-0">
                                    Tus datos no serán compartidos con terceros sin tu consentimiento
                                    expreso, salvo por mandato legal (ANT, ECU 911, autoridad judicial).
                                    Podrás ejercer tus derechos ARCO (acceso, rectificación, cancelación,
                                    oposición) ante el GAD Municipal de Salcedo en cualquier momento.
                                </p>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input @error('acepta_terminos') is-invalid @enderror"
                                       type="checkbox"
                                       name="acepta_terminos" id="acepta_terminos" value="1"
                                       @checked(old('acepta_terminos'))
                                       required>
                                <label class="form-check-label" for="acepta_terminos">
                                    <strong>Acepto</strong> el tratamiento de mis datos personales
                                    según los términos descritos arriba, conforme al Art. 7 de la LOPDP. *
                                </label>
                                @error('acepta_terminos')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Botones de acción --}}
        <div class="mt-4 d-flex justify-content-end gap-2">
            @if(!$esPrimeraVez)
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                    Cancelar
                </a>
            @endif
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle me-1"></i>
                {{ $esPrimeraVez ? 'Aceptar y guardar' : 'Guardar cambios' }}
            </button>
        </div>
    </form>
@endsection