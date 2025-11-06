<x-template.app-page>

    {{-- Catálogo de finalidades (opcional) --}}
    @inject('finalidadModel', 'App\Models\Pac\Collection\CollectionFinalidadModel')
    @php
        $finalidadList = $finalidadModel->listCollection();

        // Estatus por defecto: VIGENTE
        $estatusActual = old('estatus', $accion->estatus ?? 'VIGENTE');
    @endphp

    <x-template.app-header
        :tittle="isset($accion) ? 'Editar acción' : 'Agregar acción'"
    >
        <x-button.button-header-action
            route="{{ route('action') }}"
            icon="fa fa-arrow-left me-sm-1"
            tittle="Regresar"
        />
    </x-template.app-header>

    <x-template.app-card>
        <form action="{{ route('action.save') }}" method="POST">
            @csrf

            @isset($accion)
                <input type="hidden" name="id_accion" value="{{ $accion->id_accion }}">
            @endisset

            {{-- 1) RAMO / UR / INSTITUCIÓN --}}
            <div class="row mb-3">
                <div class="col-md-2">
                    <label class="form-label">Ramo</label>
                    <select name="ramo" class="form-select @error('ramo') is-invalid @enderror">
                        <option value="">Seleccione...</option>
                        @foreach($ramoList as $r)
                            <option value="{{ $r->ramo }}"
                                {{ old('ramo', $accion->ramo ?? '') == $r->ramo ? 'selected' : '' }}>
                                {{ $r->ramo }}
                            </option>
                        @endforeach
                    </select>
                    @error('ramo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">UR</label>
                    <select name="ur" class="form-select @error('ur') is-invalid @enderror">
                        <option value="">Seleccione...</option>
                        @foreach($urList as $ur)
                            <option value="{{ $ur->descripcion }}"
                                {{ old('ur', $accion->ur ?? '') == $ur->descripcion ? 'selected' : '' }}>
                                {{ $ur->descripcion }}
                            </option>
                        @endforeach
                    </select>
                    @error('ur')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-7">
                    <label class="form-label">Institución</label>
                    <select name="institucion" class="form-select @error('institucion') is-invalid @enderror">
                        <option value="">Seleccione...</option>
                        @foreach($instList as $ins)
                            <option value="{{ $ins->descripcion }}"
                                {{ old('institucion', $accion->institucion ?? '') == $ins->descripcion ? 'selected' : '' }}>
                                {{ $ins->descripcion }}
                            </option>
                        @endforeach
                    </select>
                    @error('institucion')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- 2) ESTATUS / NOMBRE ACCIÓN --}}
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Estatus</label>
                    <select name="estatus"
                            class="form-select @error('estatus') is-invalid @enderror">
                        <option value="">Seleccione...</option>
                        @foreach($statusList as $st)
                            @if(in_array($st->descripcion, ['VIGENTE', 'NO VIGENTE']))
                                <option value="{{ $st->descripcion }}"
                                    {{ $estatusActual === $st->descripcion ? 'selected' : '' }}>
                                    {{ $st->descripcion }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                    @error('estatus')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-8">
                    <label class="form-label">Nombre de la acción</label>
                    <input type="text"
                           name="nombre_accion"
                           class="form-control @error('nombre_accion') is-invalid @enderror"
                           value="{{ old('nombre_accion', $accion->nombre_accion ?? '') }}">
                    @error('nombre_accion')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- 3) TEMÁTICA / DURACIÓN --}}
            <div class="row mb-3">
                <div class="col-md-8">
                    <label class="form-label">Temática</label>
                    <select name="tematica"
                            class="form-select @error('tematica') is-invalid @enderror">
                        <option value="">Seleccione...</option>
                        @foreach($tematicaList as $tm)
                            <option value="{{ $tm->descripcion }}"
                                {{ old('tematica', $accion->tematica ?? '') == $tm->descripcion ? 'selected' : '' }}>
                                {{ $tm->descripcion }}
                            </option>
                        @endforeach
                    </select>
                    @error('tematica')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Duración (hrs)</label>
                    <input type="number"
                           step="0.5"
                           name="duracion_hrs"
                           class="form-control @error('duracion_hrs') is-invalid @enderror"
                           value="{{ old('duracion_hrs', $accion->duracion_hrs ?? '') }}">
                    @error('duracion_hrs')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- 4) TIPO CAPACITACIÓN / MODALIDAD --}}
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Tipo de capacitación</label>
                    <select name="tipo_capacitacion"
                            class="form-select @error('tipo_capacitacion') is-invalid @enderror">
                        <option value="">Seleccione...</option>
                        @foreach($tipoCapList as $tipo)
                            <option value="{{ $tipo->descripcion }}"
                                {{ old('tipo_capacitacion', $accion->tipo_capacitacion ?? '') == $tipo->descripcion ? 'selected' : '' }}>
                                {{ $tipo->descripcion }}
                            </option>
                        @endforeach
                    </select>
                    @error('tipo_capacitacion')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Modalidad</label>
                    <select name="modalidad"
                            class="form-select @error('modalidad') is-invalid @enderror">
                        <option value="">Seleccione...</option>
                        @foreach($modalidadList as $mod)
                            <option value="{{ $mod->descripcion }}"
                                {{ old('modalidad', $accion->modalidad ?? '') == $mod->descripcion ? 'selected' : '' }}>
                                {{ $mod->descripcion }}
                            </option>
                        @endforeach
                    </select>
                    @error('modalidad')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- 5) FINALIDAD (combo, guarda la DESCRIPCIÓN, default F6-SENSIBILIZACION) --}}
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Finalidad</label>
                    <select name="finalidad"
                            class="form-select @error('finalidad') is-invalid @enderror">
                        <option value="">Seleccione...</option>
                        @foreach($finalidadList as $fin)
                            <option value="{{ $fin->descripcion }}"
                                {{ old('finalidad', $accion->finalidad ?? 'F6-SENSIBILIZACION') == $fin->descripcion ? 'selected' : '' }}>
                                {{ $fin->descripcion }}
                            </option>
                        @endforeach
                    </select>
                    @error('finalidad')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-3">
                <a href="{{ route('action') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fa fa-times me-1"></i> Cancelar
                </a>

            <button type="submit"
                    class="btn btn-sm text-white"
                    style="background-color:#235B4E;border-color:#235B4E;">
                <i class="fa fa-save me-1"></i>
                @isset($accion)
                    Actualizar
                @else
                    Guardar
                @endisset
            </button>
        </div>
        </form>
    </x-template.app-card>

</x-template.app-page>
