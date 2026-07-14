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

        <form method="POST" action="{{ route('empleado.save') }}" id="formEmpleado">
            @csrf

            {{-- 🔹 CURP base fija, oculta para el usuario --}}
            <input type="hidden" name="curp_base" value="OIJN850210MMCRMN07">

            {{-- BLOQUE 2: Datos generales --}}
            <h6 class="mb-3">Datos generales</h6>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">CURP <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        name="curp"
                        class="form-control @error('curp') is-invalid @enderror"
                        value="{{ old('curp') }}"
                        maxlength="18"
                        required
                        style="text-transform: uppercase;"
                    >
                    @error('curp')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">RFC</label>
                    <input
                        type="text"
                        name="rfc"
                        class="form-control @error('rfc') is-invalid @enderror"
                        value="{{ old('rfc') }}"
                        maxlength="13"
                        style="text-transform: uppercase;"
                    >
                    @error('rfc')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Sexo <span class="text-danger">*</span></label>
                    @php
                        $sexoOld = old('sexo');
                    @endphp
                    <select
                        name="sexo"
                        class="form-select @error('sexo') is-invalid @enderror"
                        required
                    >
                        <option value="">Seleccione...</option>
                        <option value="HOMBRE" {{ $sexoOld === 'HOMBRE' ? 'selected' : '' }}>HOMBRE</option>
                        <option value="MUJER"  {{ $sexoOld === 'MUJER'  ? 'selected' : '' }}>MUJER</option>
                    </select>
                    @error('sexo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- BLOQUE 3: Nombre y apellidos --}}
            <h6 class="mb-3">Nombre del trabajador</h6>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        name="nombre"
                        class="form-control @error('nombre') is-invalid @enderror"
                        value="{{ old('nombre') }}"
                        required
                        style="text-transform: uppercase;"
                    >
                    @error('nombre')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Apellido paterno <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        name="apellido_paterno"
                        class="form-control @error('apellido_paterno') is-invalid @enderror"
                        value="{{ old('apellido_paterno') }}"
                        required
                        style="text-transform: uppercase;"
                    >
                    @error('apellido_paterno')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Apellido materno</label>
                    <input
                        type="text"
                        name="apellido_materno"
                        class="form-control @error('apellido_materno') is-invalid @enderror"
                        value="{{ old('apellido_materno') }}"
                        style="text-transform: uppercase;"
                    >
                    @error('apellido_materno')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- BLOQUE 4: Puesto --}}
            <h6 class="mb-3">Datos del puesto</h6>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Nombre del puesto <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        name="nombre_puesto"
                        class="form-control @error('nombre_puesto') is-invalid @enderror"
                        value="{{ old('nombre_puesto') }}"
                        required
                        style="text-transform: uppercase;"
                    >
                    @error('nombre_puesto')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Código de puesto</label>
                    <input
                        type="text"
                        name="codigo_puesto"
                        class="form-control @error('codigo_puesto') is-invalid @enderror"
                        value="{{ old('codigo_puesto') }}"
                        style="text-transform: uppercase;"
                    >
                    @error('codigo_puesto')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Nivel salarial</label>
                    <input
                        type="text"
                        name="nivel_salarial"
                        class="form-control @error('nivel_salarial') is-invalid @enderror"
                        value="{{ old('nivel_salarial') }}"
                        style="text-transform: uppercase;"
                    >
                    @error('nivel_salarial')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- BLOQUE 5: CLUES --}}
            <h6 class="mb-3">Unidad / CLUES</h6>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">CLUES</label>
                    <input
                        type="text"
                        name="clave_clues"
                        class="form-control @error('clave_clues') is-invalid @enderror"
                        value="{{ old('clave_clues') }}"
                        style="text-transform: uppercase;"
                    >
                    @error('clave_clues')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-8">
                    <label class="form-label">Descripción CLUES</label>
                    <input
                        type="text"
                        name="descripcion_clues"
                        class="form-control @error('descripcion_clues') is-invalid @enderror"
                        value="{{ old('descripcion_clues') }}"
                        style="text-transform: uppercase;"
                    >
                    @error('descripcion_clues')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- BLOQUE 6: Datos laborales --}}
            <h6 class="mb-3">Datos laborales</h6>

            <div class="row mb-3">
                <div class="col-md-6">
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

                <div class="col-md-6">
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

            <div class="row mb-3">
                <div class="col-md-6">
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

                <div class="col-md-6">
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

            {{-- BLOQUE 7: Otros datos --}}
            <h6 class="mb-3">Otros datos</h6>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Quincena</label>
                    <input
                        type="number"
                        name="quincena"
                        class="form-control @error('quincena') is-invalid @enderror"
                        value="{{ old('quincena') }}"
                        min="1"
                        max="24"
                    >
                    @error('quincena')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Si no capturas, se usará 18 por defecto.</small>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-12">
                    <label class="form-label">Observaciones <span class="text-danger">*</span></label>
                    <textarea
                        name="observaciones"
                        class="form-control @error('observaciones') is-invalid @enderror"
                        rows="3"
                        maxlength="1000"
                        style="text-transform: uppercase;"
                        required
                    >{{ old('observaciones') }}</textarea>
                    @error('observaciones')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-3">
                <a href="{{ route('pac') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fa fa-times me-1"></i> Cancelar
                </a>

                <button type="submit"
                        class="btn btn-sm text-white"
                        style="background-color:#235B4E;border-color:#235B4E;"
                        id="btnGuardar">
                    <i class="fa fa-save me-1"></i>
                    Guardar
                </button>
            </div>
        </form>

    </x-template.app-card>

    {{-- Script directo (sin @push) para que siempre se ejecute --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('formEmpleado');
            const btnGuardar = document.getElementById('btnGuardar');

            // Prevenir doble envío
            if (form && btnGuardar) {
                form.addEventListener('submit', function() {
                    btnGuardar.disabled = true;
                    btnGuardar.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i> Guardando...';
                });
            }

            // Mayúsculas automáticas
            const upperCaseInputs = document.querySelectorAll('input[style*="text-transform: uppercase"], textarea[style*="text-transform: uppercase"]');
            upperCaseInputs.forEach(function(input) {
                input.addEventListener('input', function() {
                    this.value = this.value.toUpperCase();
                });
            });

            // TOAST: éxito
            @if (session('success'))
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: @json(session('success')),
                    showConfirmButton: false,
                    timer: 4000,
                    timerProgressBar: true,
                });
            @endif

            // TOAST: errores de validación
            @if ($errors->any())
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'error',
                    title: 'Verifica la información capturada',
                    html: `{!! implode('<br>', $errors->all()) !!}`,
                    showConfirmButton: false,
                    timer: 7000,
                    timerProgressBar: true,
                });
            @endif
        });
    </script>

</x-template.app-page>
