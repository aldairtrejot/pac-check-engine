@php
    // Ramos y UR ya usados en cat_instancias
    $ramoInstancias = DB::table('public.cat_instancias')
        ->select('ramo')
        ->whereNotNull('ramo')
        ->where('ramo', '<>', '0')
        ->distinct()
        ->orderBy('ramo')
        ->get();

    $urInstancias = DB::table('public.cat_instancias')
        ->select('ur')
        ->whereNotNull('ur')
        ->where('ur', '<>', '0')
        ->distinct()
        ->orderBy('ur')
        ->get();

    $ramoSeleccionado = old('ramo', $instancia->ramo ?? '');
    $urSeleccionado   = old('ur', $instancia->ur ?? '');
@endphp

<x-template.app-page>

    <x-template.app-header
        :tittle="isset($instancia) ? 'Editar instancia' : 'Agregar instancia'"
    >
        {{-- Regresar con mismo diseño que "Agregar" --}}
        <x-button.button-header-action
            route="{{ route('instancia') }}"
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

        <form method="POST" action="{{ route('instancia.save') }}">
            @csrf

            <input type="hidden" name="mode" value="{{ isset($instancia) ? 'edit' : 'create' }}">

            @if (isset($instancia))
                {{-- ID instancia (solo lectura) --}}
                <div class="mb-3">
                    <label class="form-label">ID instancia</label>
                    <input type="text" class="form-control" value="{{ $instancia->id_instancia }}" disabled>
                    <input type="hidden" name="id_instancia" value="{{ $instancia->id_instancia }}">
                </div>

                {{-- Consecutivo (solo lectura) --}}
                <div class="mb-3">
                    <label class="form-label">Consecutivo</label>
                    <input type="text" class="form-control" value="{{ $instancia->consecutivo }}" disabled>
                </div>
            @else
                <div class="mb-3">
                    <label class="form-label">ID instancia</label>
                    <input type="text" class="form-control" value="Se generará automáticamente" disabled>
                </div>

                <div class="mb-3">
                    <label class="form-label">Consecutivo</label>
                    <input type="text" class="form-control" value="Se generará automáticamente" disabled>
                </div>
            @endif

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Ramo</label>
                    <select name="ramo" class="form-select">
                        <option value="">Seleccione...</option>
                        @foreach($ramoInstancias as $r)
                            <option value="{{ $r->ramo }}"
                                {{ $ramoSeleccionado === $r->ramo ? 'selected' : '' }}>
                                {{ $r->ramo }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">UR</label>
                    <select name="ur" class="form-select">
                        <option value="">Seleccione...</option>
                        @foreach($urInstancias as $u)
                            <option value="{{ $u->ur }}"
                                {{ $urSeleccionado === $u->ur ? 'selected' : '' }}>
                                {{ $u->ur }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Nombre de la instancia</label>
                <input
                    type="text"
                    name="instancia"
                    class="form-control"
                    value="{{ old('instancia', $instancia->instancia ?? '') }}"
                    required
                >
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Año</label>
                    <input
                        type="number"
                        name="anio"
                        class="form-control"
                        value="{{ old('anio', $instancia->anio ?? date('Y')) }}"
                        required
                    >
                </div>

                <div class="col-md-8 mb-3">
                    <label class="form-label">Estatus</label>
                    @php
                        $estatusActual = old('estatus', $instancia->estatus ?? 'VIGENTE');
                    @endphp
                    <select name="estatus" class="form-select" required>
                        <option value="VIGENTE" {{ $estatusActual === 'VIGENTE' ? 'selected' : '' }}>VIGENTE</option>
                        <option value="INACTIVO" {{ $estatusActual === 'INACTIVO' ? 'selected' : '' }}>INACTIVO</option>
                    </select>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-3">
                <a href="{{ route('instancia') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fa fa-times me-1"></i> Cancelar
                </a>
                {{-- Guardar con color #235B4E --}}
                <button type="submit"
                        class="btn btn-sm text-white"
                        style="background-color:#235B4E;border-color:#235B4E;">
                    <i class="fa fa-save me-1"></i>
                    {{ isset($instancia) ? 'Actualizar' : 'Guardar' }}
                </button>
            </div>
        </form>

    </x-template.app-card>

</x-template.app-page>
