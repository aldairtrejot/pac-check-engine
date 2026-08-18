<x-template.app-page>

    {{-- Catálogo de finalidades (opcional) --}}
    @inject('finalidadModel', 'App\Models\Pac\Collection\CollectionFinalidadModel')

    @php
        $finalidadList = $finalidadModel->listCollection();

        // Estatus por defecto: VIGENTE
        $estatusActual = old('estatus', $accion->estatus ?? 'VIGENTE');

        $hasRamoActual = isset($accion) && collect($ramoList)->contains(fn ($r) => (string) $r->ramo === (string) ($accion->ramo ?? ''));
        $hasUrActual = isset($accion) && collect($urList)->contains(fn ($ur) => (string) $ur->descripcion === (string) ($accion->ur ?? ''));
        $hasInstitucionActual = isset($accion) && collect($instList)->contains(fn ($ins) => (string) $ins->descripcion === (string) ($accion->institucion ?? ''));
        $hasTematicaActual = isset($accion) && collect($tematicaList)->contains(fn ($tm) => (string) $tm->descripcion === (string) ($accion->tematica ?? ''));
        $hasTipoCapActual = isset($accion) && collect($tipoCapList)->contains(fn ($tipo) => (string) $tipo->descripcion === (string) ($accion->tipo_capacitacion ?? ''));
        $hasModalidadActual = isset($accion) && collect($modalidadList)->contains(fn ($mod) => (string) $mod->descripcion === (string) ($accion->modalidad ?? ''));
        $hasFinalidadActual = isset($accion) && collect($finalidadList)->contains(fn ($fin) => (string) $fin->descripcion === (string) ($accion->finalidad ?? ''));
    @endphp

    <x-template.app-header
        :tittle="isset($accion) ? 'Editar acción' : 'Agregar acción'"
    >
        <x-button.button-header-action
            route="{{ route('action') }}"
            icon="fa fa-arrow-left me-sm-1"
            tittle="Regresar"
        />
    
        {{-- Revisión de constancias --}}
        <x-button.button-header-action
            route="{{ route('constancias') }}"
            icon="fa fa-file-pdf me-sm-1"
            tittle="Revisión de constancias"
        />
    </x-template.app-header>

    <x-template.app-card>
        <form id="form_action"
              action="{{ route('action.save') }}"
              method="POST"
              autocomplete="off">
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
                        @if(isset($accion) && !$hasRamoActual && trim((string) ($accion->ramo ?? '')) !== '')
                            <option value="{{ $accion->ramo }}" selected>{{ $accion->ramo }}</option>
                        @endif
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
                        @if(isset($accion) && !$hasUrActual && trim((string) ($accion->ur ?? '')) !== '')
                            <option value="{{ $accion->ur }}" selected>{{ $accion->ur }}</option>
                        @endif
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
                        @if(isset($accion) && !$hasInstitucionActual && trim((string) ($accion->institucion ?? '')) !== '')
                            <option value="{{ $accion->institucion }}" selected>{{ $accion->institucion }}</option>
                        @endif
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
                            @if(in_array($st->descripcion, ['VIGENTE', 'ALTA', 'NO VIGENTE']))
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
                           autocomplete="off"
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
                        @if(isset($accion) && !$hasTematicaActual && trim((string) ($accion->tematica ?? '')) !== '')
                            <option value="{{ $accion->tematica }}" selected>{{ $accion->tematica }}</option>
                        @endif
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
                           autocomplete="off"
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
                        @if(isset($accion) && !$hasTipoCapActual && trim((string) ($accion->tipo_capacitacion ?? '')) !== '')
                            <option value="{{ $accion->tipo_capacitacion }}" selected>{{ $accion->tipo_capacitacion }}</option>
                        @endif
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
                        @if(isset($accion) && !$hasModalidadActual && trim((string) ($accion->modalidad ?? '')) !== '')
                            <option value="{{ $accion->modalidad }}" selected>{{ $accion->modalidad }}</option>
                        @endif
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

            {{-- 5) FINALIDAD --}}
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Finalidad</label>
                    <select name="finalidad"
                            class="form-select @error('finalidad') is-invalid @enderror">
                        <option value="">Seleccione...</option>
                        @if(isset($accion) && !$hasFinalidadActual && trim((string) ($accion->finalidad ?? '')) !== '')
                            <option value="{{ $accion->finalidad }}" selected>{{ $accion->finalidad }}</option>
                        @endif
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
                        form="form_action"
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
