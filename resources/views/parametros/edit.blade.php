{{-- resources/views/parametros/edit.blade.php --}}
@extends('layouts.app')
@section('breadcrumb')
    {{ Breadcrumbs::render('parametros.edit', $parametro) }}
@endsection

@section('content')
<div class="row">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="POST" action="{{ route('parametros.update', $parametro) }}">
                    @csrf @method('PUT')

                    {{-- Información solo-lectura --}}
                    <div class="mb-3">
                        <label class="form-label text-muted small">Clave</label>
                        <div><code>{{ $parametro->clave }}</code></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small">Categoría</label>
                        <div>{{ $parametro->categoria_etiqueta }}</div>
                    </div>

                    @if($parametro->articulo_ordenanza)
                        <div class="mb-3">
                            <label class="form-label text-muted small">Origen legal</label>
                            <div>
                                <span class="badge bg-light text-dark border">
                                    Ordenanza SIMETSA — {{ $parametro->articulo_ordenanza }}
                                </span>
                            </div>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label text-muted small">Tipo de dato</label>
                        <div><code>{{ $parametro->tipo }}</code></div>
                    </div>

                    <hr>

                    {{-- Campo editable: valor --}}
                    <div class="mb-3">
                        <label for="valor" class="form-label">Valor *</label>

                        @switch($parametro->tipo)
                            @case(\App\Models\Parametro::TIPO_BOOLEAN)
                                <select name="valor" id="valor"
                                        class="form-select @error('valor') is-invalid @enderror" required>
                                    <option value="1" @selected(filter_var(old('valor', $parametro->valor), FILTER_VALIDATE_BOOLEAN))>Verdadero</option>
                                    <option value="0" @selected(!filter_var(old('valor', $parametro->valor), FILTER_VALIDATE_BOOLEAN))>Falso</option>
                                </select>
                                @break

                            @case(\App\Models\Parametro::TIPO_INTEGER)
                                <input type="number" name="valor" id="valor" step="1" min="0"
                                       class="form-control @error('valor') is-invalid @enderror"
                                       value="{{ old('valor', $parametro->valor) }}" required>
                                @break

                            @case(\App\Models\Parametro::TIPO_DECIMAL)
                                <input type="number" name="valor" id="valor" step="0.01" min="0"
                                       class="form-control @error('valor') is-invalid @enderror"
                                       value="{{ old('valor', $parametro->valor) }}" required>
                                @break

                            @default
                                <input type="text" name="valor" id="valor"
                                       class="form-control @error('valor') is-invalid @enderror"
                                       value="{{ old('valor', $parametro->valor) }}" required>
                        @endswitch

                        @error('valor')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Descripción (opcional) --}}
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea name="descripcion" id="descripcion" rows="2"
                                  class="form-control @error('descripcion') is-invalid @enderror"
                        >{{ old('descripcion', $parametro->descripcion) }}</textarea>
                        @error('descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('parametros.index') }}" class="btn btn-outline-danger">
                            <i class="ph ph-x-circle me-1"></i>
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="ph ph-check-circle me-1"></i> Guardar cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Advertencia lateral --}}
    <div class="col-lg-5">
        <div class="alert alert-warning small">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            <strong>Atención:</strong> Cambiar este parámetro afecta el
            comportamiento operativo del sistema en tiempo real. Por ejemplo,
            modificar el SBU recalcula las multas pendientes, y modificar
            los porcentajes de liquidación cambia el reparto de futuros pagos.
            <br><br>
            Los cambios quedan registrados con timestamp y futuras versiones
            del sistema agregarán bitácora de quién hizo cada cambio.
        </div>
    </div>
</div>

{{-- Historial de cambios del parámetro --}}
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white">
        <h2 class="h6 mb-0 text-simetsa">
            <i class="bi bi-clock-history me-1"></i>
            Historial de cambios
        </h2>
    </div>
    @php($bitacora = $parametro->bitacora()->with('user')->limit(20)->get())

    @if($bitacora->isEmpty())
        <div class="card-body text-muted small">
            Este parámetro no ha sido modificado desde su creación.
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-sm align-middle small mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Fecha / Hora</th>
                        <th>Usuario</th>
                        <th>Campo</th>
                        <th>Valor anterior</th>
                        <th>Valor nuevo</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bitacora as $b)
                        <tr>
                            <td>{{ $b->ocurrido_en->format('d/m/Y H:i:s') }}</td>
                            <td>
                                @if($b->user)
                                    <strong>{{ $b->user->name }}</strong>
                                @else
                                    <span class="text-muted">Sistema</span>
                                @endif
                            </td>
                            <td><code>{{ $b->campo }}</code></td>
                            <td><span class="text-danger">{{ $b->valor_anterior ?? '—' }}</span></td>
                            <td><span class="text-success">{{ $b->valor_nuevo ?? '—' }}</span></td>
                            <td><code class="small">{{ $b->ip ?? '—' }}</code></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($parametro->bitacora()->count() > 20)
            <div class="card-footer small text-muted">
                Mostrando las 20 entradas más recientes.
            </div>
        @endif
    @endif
</div>
@endsection