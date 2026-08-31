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
                        id="curp"
                        name="curp"
                        class="form-control @error('curp') is-invalid @enderror"
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
                        id="nomina"
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
                        id="entidad"
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

    <script type="application/json" id="empleado_catalog_props">{!! json_encode($catalogProps ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>

    {{-- Script directo (sin @push) para que siempre se ejecute --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('formEmpleado');
            const btnGuardar = document.getElementById('btnGuardar');
            const curpInput = document.getElementById('curp');
            const sexoSelect = document.getElementById('sexo');
            const curpRegex = /^[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[A-Z0-9][0-9]$/;

            // Prevenir doble envío
            if (form && btnGuardar) {
                form.addEventListener('submit', function(event) {
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

            // Mayúsculas automáticas
            const upperCaseInputs = document.querySelectorAll('input[style*="text-transform: uppercase"], textarea[style*="text-transform: uppercase"]');
            upperCaseInputs.forEach(function(input) {
                input.addEventListener('input', function() {
                    this.value = this.value.toUpperCase();
                });
            });

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
