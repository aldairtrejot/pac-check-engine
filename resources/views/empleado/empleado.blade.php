<x-template.app-page>

    <x-template.app-header
        :tittle="'Agregar empleado'"
    >
        <x-button.button-header-action
            route="{{ route('pac') }}"
            icon="fa fa-arrow-left me-sm-1"
            tittle="Regresar"
        />
    </x-template.app-header>

    <x-template.app-card>

        {{-- Mensaje de éxito --}}
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        {{-- Errores --}}
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('empleado.save') }}">
            @csrf

            {{-- 1) CURP BASE (plantilla) --}}
            <div class="mb-3">
                <label class="form-label">CURP base (empleado plantilla)</label>
                <input
                    type="text"
                    name="curp_base"
                    class="form-control @error('curp_base') is-invalid @enderror"
                    value="{{ old('curp_base') }}"
                    maxlength="18"
                >
                @error('curp_base')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="text-muted">
                    (Opcional) Solo referencia, por ahora no se usa para copiar datos.
                </small>
            </div>

            <hr>

            <h6 class="mb-3">Datos del nuevo empleado</h6>

            {{-- 2) CURP / RFC / Sexo --}}
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">CURP</label>
                    <input
                        type="text"
                        name="curp"
                        class="form-control @error('curp') is-invalid @enderror"
                        value="{{ old('curp') }}"
                        maxlength="18"
                    >
                    @error('curp')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">RFC</label>
                    <input
                        type="text"
                        name="rfc"
                        class="form-control @error('rfc') is-invalid @enderror"
                        value="{{ old('rfc') }}"
                        maxlength="13"
                    >
                    @error('rfc')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Sexo</label>
                    @php
                        $sexoOld = old('sexo');
                    @endphp
                    <select
                        name="sexo"
                        class="form-select @error('sexo') is-invalid @enderror"
                    >
                        <option value="">Seleccione...</option>
                        <option value="HOMBRE" {{ $sexoOld === 'HOMBRE' ? 'selected' : '' }}>HOMBRE</option>
                        <option value="MUJER" {{ $sexoOld === 'MUJER' ? 'selected' : '' }}>MUJER</option>
                    </select>
                    @error('sexo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- 3) Nombre y apellidos --}}
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Nombre</label>
                    <input
                        type="text"
                        name="nombre"
                        class="form-control @error('nombre') is-invalid @enderror"
                        value="{{ old('nombre') }}"
                    >
                    @error('nombre')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Apellido paterno</label>
                    <input
                        type="text"
                        name="apellido_paterno"
                        class="form-control @error('apellido_paterno') is-invalid @enderror"
                        value="{{ old('apellido_paterno') }}"
                    >
                    @error('apellido_paterno')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Apellido materno</label>
                    <input
                        type="text"
                        name="apellido_materno"
                        class="form-control @error('apellido_materno') is-invalid @enderror"
                        value="{{ old('apellido_materno') }}"
                    >
                    @error('apellido_materno')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- 4) Puesto / Código / Nivel salarial --}}
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Nombre del puesto</label>
                    <input
                        type="text"
                        name="nombre_puesto"
                        class="form-control @error('nombre_puesto') is-invalid @enderror"
                        value="{{ old('nombre_puesto') }}"
                    >
                    @error('nombre_puesto')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Código de puesto</label>
                    <input
                        type="text"
                        name="codigo_puesto"
                        class="form-control @error('codigo_puesto') is-invalid @enderror"
                        value="{{ old('codigo_puesto') }}"
                    >
                    @error('codigo_puesto')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Nivel salarial</label>
                    <input
                        type="text"
                        name="nivel_salarial"
                        class="form-control @error('nivel_salarial') is-invalid @enderror"
                        value="{{ old('nivel_salarial') }}"
                    >
                    @error('nivel_salarial')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- 5) CLUES --}}
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">CLUES</label>
                    <input
                        type="text"
                        name="clave_clues"
                        class="form-control @error('clave_clues') is-invalid @enderror"
                        value="{{ old('clave_clues') }}"
                    >
                    @error('clave_clues')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-8 mb-3">
                    <label class="form-label">Descripción CLUES</label>
                    <input
                        type="text"
                        name="descripcion_clues"
                        class="form-control @error('descripcion_clues') is-invalid @enderror"
                        value="{{ old('descripcion_clues') }}"
                    >
                    @error('descripcion_clues')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- 6) Contratación / Nómina --}}
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Tipo de contratación</label>
                    <input
                        type="text"
                        name="tipo_contratacion"
                        class="form-control @error('tipo_contratacion') is-invalid @enderror"
                        value="{{ old('tipo_contratacion') }}"
                    >
                    @error('tipo_contratacion')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Nómina</label>
                    <input
                        type="text"
                        name="nomina"
                        class="form-control @error('nomina') is-invalid @enderror"
                        value="{{ old('nomina') }}"
                    >
                    @error('nomina')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- 7) Nivel de atención / Entidad --}}
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nivel de atención</label>
                    <input
                        type="text"
                        name="nivel_atencion"
                        class="form-control @error('nivel_atencion') is-invalid @enderror"
                        value="{{ old('nivel_atencion') }}"
                    >
                    @error('nivel_atencion')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Entidad</label>
                    <input
                        type="text"
                        name="entidad"
                        class="form-control @error('entidad') is-invalid @enderror"
                        value="{{ old('entidad') }}"
                    >
                    @error('entidad')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- 8) Quincena --}}
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Quincena</label>
                    <input
                        type="number"
                        name="quincena"
                        class="form-control @error('quincena') is-invalid @enderror"
                        value="{{ old('quincena') }}"
                    >
                    @error('quincena')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Si no capturas, se usará 18 por defecto.</small>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-3">
                <a href="{{ route('pac') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fa fa-times me-1"></i> Cancelar
                </a>

                <button type="submit"
                        class="btn btn-sm text-white"
                        style="background-color:#235B4E;border-color:#235B4E;">
                    <i class="fa fa-save me-1"></i>
                    Guardar
                </button>
            </div>
        </form>

    </x-template.app-card>

</x-template.app-page>
