<x-template.app-page>

    <x-template.app-header tittle="Temática">
        <a href="{{ route('tematica') }}" class="btn btn-sm btn-secondary">
            <i class="fa fa-arrow-left me-1"></i> Regresar
        </a>
    </x-template.app-header>

    <x-template.app-card>
        <form action="{{ route('tematica.save') }}" method="POST">
            @csrf

            {{-- create / edit --}}
            <input type="hidden" name="mode" value="{{ isset($tematica) ? 'edit' : 'create' }}">

            <div class="row mb-3">
                {{-- ID TEMÁTICA --}}
                <div class="col-md-4">
                    <label class="form-label">ID temática</label>

                    @isset($tematica)
                        {{-- EDICIÓN: mostrar ID real (solo lectura) y enviarlo al backend --}}
                        <input type="text"
                               name="id_tematica"
                               class="form-control @error('id_tematica') is-invalid @enderror"
                               value="{{ old('id_tematica', $tematica->id_tematica) }}"
                               readonly>
                        @error('id_tematica')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    @else
                        {{-- ALTA: solo informativo --}}
                        <input type="text"
                               class="form-control"
                               value="Se generará automáticamente (consecutivo + ramo + ur)"
                               disabled>
                    @endisset
                </div>

                {{-- CONSECUTIVO (solo lectura en edición, automático en alta) --}}
                <div class="col-md-4">
                    <label class="form-label">Consecutivo</label>

                    @isset($tematica)
                        <input type="number"
                               class="form-control"
                               value="{{ $tematica->consecutivo }}"
                               readonly>
                    @else
                        <input type="text"
                               class="form-control"
                               value="Se asignará automáticamente"
                               disabled>
                    @endisset
                </div>

                {{-- RAMO --}}
                <div class="col-md-4">
                    <label class="form-label">Ramo</label>
                    <input type="text"
                           name="ramo"
                           class="form-control @error('ramo') is-invalid @enderror"
                           value="{{ old('ramo', $tematica->ramo ?? '0') }}">
                    @error('ramo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- UR / TEMÁTICA --}}
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">UR</label>
                    <input type="text"
                           name="ur"
                           class="form-control @error('ur') is-invalid @enderror"
                           value="{{ old('ur', $tematica->ur ?? '0') }}">
                    @error('ur')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-8">
                    <label class="form-label">Temática</label>
                    <input type="text"
                           name="tematica"
                           class="form-control @error('tematica') is-invalid @enderror"
                           value="{{ old('tematica', $tematica->tematica ?? '') }}">
                    @error('tematica')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- CATEGORÍAS / ENFOQUE --}}
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Categorías</label>
                    <input type="text"
                           name="categorias"
                           class="form-control @error('categorias') is-invalid @enderror"
                           value="{{ old('categorias', $tematica->categorias ?? '') }}">
                    @error('categorias')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Enfoque</label>
                    <input type="text"
                           name="enfoque"
                           class="form-control @error('enfoque') is-invalid @enderror"
                           value="{{ old('enfoque', $tematica->enfoque ?? '') }}">
                    @error('enfoque')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-success">
                    <i class="fa fa-save me-1"></i>
                    {{ isset($tematica) ? 'Actualizar' : 'Guardar' }}
                </button>
            </div>
        </form>
    </x-template.app-card>

</x-template.app-page>
