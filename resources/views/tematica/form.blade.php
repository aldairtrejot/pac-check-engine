<x-template.app-page>

    <x-template.app-header
        :tittle="isset($tematica) ? 'Editar temática' : 'Agregar temática'"
    >
        {{-- Regresar con mismo diseño que "Agregar" --}}
        <x-button.button-header-action
            route="{{ route('tematica') }}"
            icon="fa fa-arrow-left me-sm-1"
            tittle="Regresar"
        />
    </x-template.app-header>

    <x-template.app-card>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('tematica.save') }}">
            @csrf

            {{-- modo: create / edit --}}
            <input type="hidden" name="mode" value="{{ isset($tematica) ? 'edit' : 'create' }}">

            @if (isset($tematica))
                {{-- ID temática (solo lectura) --}}
                <div class="mb-3">
                    <label class="form-label">ID temática</label>
                    <input
                        type="text"
                        class="form-control"
                        value="{{ $tematica->id_tematica }}"
                        disabled
                    >
                    <input type="hidden" name="id_tematica" value="{{ $tematica->id_tematica }}">
                </div>

                {{-- Consecutivo (solo lectura) --}}
                <div class="mb-3">
                    <label class="form-label">Consecutivo</label>
                    <input
                        type="text"
                        class="form-control"
                        value="{{ $tematica->consecutivo }}"
                        disabled
                    >
                </div>
            @else
                {{-- En alta se informan como automáticos --}}
                <div class="mb-3">
                    <label class="form-label">ID temática</label>
                    <input
                        type="text"
                        class="form-control"
                        value="Se generará automáticamente"
                        disabled
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Consecutivo</label>
                    <input
                        type="text"
                        class="form-control"
                        value="Se generará automáticamente"
                        disabled
                    >
                </div>
            @endif

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Ramo</label>
                    <input
                        type="text"
                        name="ramo"
                        class="form-control"
                        value="{{ old('ramo', $tematica->ramo ?? '0') }}"
                    >
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">UR</label>
                    <input
                        type="text"
                        name="ur"
                        class="form-control"
                        value="{{ old('ur', $tematica->ur ?? '0') }}"
                    >
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Temática</label>
                <input
                    type="text"
                    name="tematica"
                    class="form-control"
                    value="{{ old('tematica', $tematica->tematica ?? '') }}"
                    maxlength="200"
                    required
                >
            </div>

            <div class="mb-3">
                <label class="form-label">Categorías</label>
                <input
                    type="text"
                    name="categorias"
                    class="form-control"
                    value="{{ old('categorias', $tematica->categorias ?? '') }}"
                    maxlength="200"
                    required
                >
            </div>

            <div class="mb-3">
                <label class="form-label">Enfoque</label>
                <input
                    type="text"
                    name="enfoque"
                    class="form-control"
                    value="{{ old('enfoque', $tematica->enfoque ?? '') }}"
                    maxlength="100"
                    required
                >
            </div>

            <div class="d-flex justify-content-end gap-2 mt-3">
                <a href="{{ route('tematica') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fa fa-times me-1"></i> Cancelar
                </a>
                {{-- Guardar con color #235B4E --}}
                <button type="submit"
                        class="btn btn-sm text-white"
                        style="background-color:#235B4E;border-color:#235B4E;">
                    <i class="fa fa-save me-1"></i>
                    {{ isset($tematica) ? 'Actualizar' : 'Guardar' }}
                </button>
            </div>
        </form>

    </x-template.app-card>

</x-template.app-page>
