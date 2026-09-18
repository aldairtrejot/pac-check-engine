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

            {{-- BLOQUE 2: Datos generales --}}
            <h6 class="mb-3">Datos generales</h6>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">CURP <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        id="curp"
                        name="curp"
                        class="form-control js-uppercase @error('curp') is-invalid @enderror"
                        value="{{ old('curp') }}"
                        minlength="18"
                        maxlength="18"
                        pattern="[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[A-Z0-9][0-9]"
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
                        id="rfc"
                        name="rfc"
                        class="form-control js-uppercase @error('rfc') is-invalid @enderror"
                        value="{{ old('rfc') }}"
                        maxlength="13"
                        style="text-transform: uppercase;"
                    >
                    @error('rfc')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted d-block mt-1">
                        Si no cuenta con el RFC al momento del registro, puede dejar este campo vacío y capturarlo posteriormente.
                    </small>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Sexo <span class="text-danger">*</span></label>
                    @php
                        $sexoOld = old('sexo');
                    @endphp
                    <select
                        id="sexo"
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
                        class="form-control js-uppercase @error('nombre') is-invalid @enderror"
                        value="{{ old('nombre') }}"
                        maxlength="100"
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
                        class="form-control js-uppercase @error('apellido_paterno') is-invalid @enderror"
                        value="{{ old('apellido_paterno') }}"
                        maxlength="100"
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
                        class="form-control js-uppercase @error('apellido_materno') is-invalid @enderror"
                        value="{{ old('apellido_materno') }}"
                        maxlength="100"
                        style="text-transform: uppercase;"
                    >
                    @error('apellido_materno')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- BLOQUES 4 y 5: Catálogos renderizados con el mismo selector de Mi Plantilla --}}
            <div id="blade_form_empleado_catalogs"></div>

            {{-- BLOQUE 6: Datos laborales --}}
            <h6 class="mb-3">Datos laborales</h6>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Tipo de contratación</label>
                    <input
                        type="text"
                        name="tipo_contratacion"
                        class="form-control js-uppercase @error('tipo_contratacion') is-invalid @enderror"
                        value="{{ old('tipo_contratacion') }}"
                        maxlength="50"
                        style="text-transform: uppercase;"
                    >
                    @error('tipo_contratacion')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Nómina</label>
                    <input
                        type="text"
                        id="nomina"
                        name="nomina"
                        class="form-control js-uppercase @error('nomina') is-invalid @enderror"
                        value="{{ old('nomina') }}"
                        maxlength="50"
                        style="text-transform: uppercase;"
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
                        class="form-control js-uppercase @error('nivel_atencion') is-invalid @enderror"
                        value="{{ old('nivel_atencion') }}"
                        maxlength="50"
                        style="text-transform: uppercase;"
                    >
                    @error('nivel_atencion')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Entidad</label>
                    <input
                        type="text"
                        id="entidad"
                        name="entidad"
                        class="form-control js-uppercase @error('entidad') is-invalid @enderror"
                        value="{{ old('entidad') }}"
                        maxlength="100"
                        style="text-transform: uppercase;"
                    >
                    @error('entidad')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- BLOQUE 7: Otros datos --}}
            <h6 class="mb-3">Otros datos</h6>

            <div class="row mb-3">
                <div class="col-md-8">
                    <label class="form-label">Val Plantilla <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        id="val_plantilla"
                        name="val_plantilla"
                        class="form-control js-uppercase @error('val_plantilla') is-invalid @enderror"
                        value="{{ old('val_plantilla') }}"
                        list="val_plantilla_options"
                        maxlength="100"
                        required
                        style="text-transform: uppercase;"
                    >
                    <datalist id="val_plantilla_options">
                        @foreach (($valPlantillaOptions ?? collect()) as $valPlantillaOption)
                            <option value="{{ $valPlantillaOption }}"></option>
                        @endforeach
                    </datalist>
                    @error('val_plantilla')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

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
                    <label class="form-label">Observaciones Plantilla <span class="text-danger">*</span></label>
                    <textarea
                        name="observaciones_plantilla"
                        class="form-control js-uppercase @error('observaciones_plantilla') is-invalid @enderror"
                        rows="3"
                        maxlength="1000"
                        style="text-transform: uppercase;"
                        required
                    >{{ old('observaciones_plantilla') }}</textarea>
                    @error('observaciones_plantilla')
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

    <script type="application/json" id="empleado_catalog_props">{!! json_encode($catalogProps ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>

    {{-- Script directo (sin @push) para que siempre se ejecute --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('formEmpleado');
            const btnGuardar = document.getElementById('btnGuardar');
            const curpInput = document.getElementById('curp');
            const sexoSelect = document.getElementById('sexo');
            const curpRegex = /^[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[A-Z0-9][0-9]$/;
            const upperCaseInputs = form
                ? form.querySelectorAll('.js-uppercase')
                : [];

            function normalizeUppercase(input) {
                if (!input) {
                    return;
                }

                const start = input.selectionStart;
                const end = input.selectionEnd;
                input.value = input.value.toUpperCase();

                if (
                    typeof start === 'number' &&
                    typeof end === 'number' &&
                    typeof input.setSelectionRange === 'function'
                ) {
                    input.setSelectionRange(start, end);
                }
            }

            // Mayúsculas automáticas mientras el usuario captura.
            upperCaseInputs.forEach(function(input) {
                input.addEventListener('input', function() {
                    normalizeUppercase(this);
                });

                // También normaliza valores restaurados con old().
                normalizeUppercase(input);
            });

            // Prevenir doble envío y normalizar una última vez antes de guardar.
            if (form && btnGuardar) {
                form.addEventListener('submit', function(event) {
                    upperCaseInputs.forEach(normalizeUppercase);
                    syncSexoFromCurp();

                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                        form.reportValidity();
                        return;
                    }

                    btnGuardar.disabled = true;
                    btnGuardar.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i> Guardando...';
                });
            }

            function syncSexoFromCurp() {
                if (!curpInput || !sexoSelect) {
                    return;
                }

                const curp = curpInput.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 18);
                curpInput.value = curp;

                if (curp.length === 0 || curp.length < 18) {
                    curpInput.setCustomValidity('');
                    return;
                }

                if (!curpRegex.test(curp)) {
                    sexoSelect.value = '';
                    curpInput.setCustomValidity('El CURP no tiene un formato válido.');
                    return;
                }

                curpInput.setCustomValidity('');
                sexoSelect.value = curp.charAt(10) === 'H' ? 'HOMBRE' : 'MUJER';
            }

            if (curpInput) {
                curpInput.addEventListener('input', syncSexoFromCurp);
                syncSexoFromCurp();
            }

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
