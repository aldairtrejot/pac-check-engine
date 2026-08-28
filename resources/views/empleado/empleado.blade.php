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

            {{-- BLOQUE 4: Puesto --}}
            <h6 class="mb-3">Datos del puesto</h6>

            @php
                $codigoPuestoOld = old('codigo_puesto');
            @endphp

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Nombre del puesto <span class="text-danger">*</span></label>
                    <select
                        id="codigo_puesto"
                        name="codigo_puesto"
                        class="form-select @error('codigo_puesto') is-invalid @enderror @error('nombre_puesto') is-invalid @enderror"
                        required
                    >
                        <option value="">Seleccione...</option>
                        @foreach (($puestos ?? collect()) as $puesto)
                            <option
                                value="{{ $puesto->codigo_puesto }}"
                                data-puesto="{{ $puesto->puesto }}"
                                data-nivel="{{ $puesto->nivel }}"
                                {{ $codigoPuestoOld === $puesto->codigo_puesto ? 'selected' : '' }}
                            >
                                {{ $puesto->label }}
                            </option>
                        @endforeach
                    </select>
                    <input
                        type="hidden"
                        id="nombre_puesto"
                        name="nombre_puesto"
                        value="{{ old('nombre_puesto') }}"
                    >
                    @error('nombre_puesto')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    @error('codigo_puesto')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Código de puesto</label>
                    <input
                        type="text"
                        id="codigo_puesto_text"
                        class="form-control"
                        value="{{ old('codigo_puesto') }}"
                        readonly
                    >
                </div>

                <div class="col-md-3">
                    <label class="form-label">Nivel salarial</label>
                    <input
                        type="text"
                        id="nivel_salarial"
                        name="nivel_salarial"
                        class="form-control @error('nivel_salarial') is-invalid @enderror"
                        value="{{ old('nivel_salarial') }}"
                        readonly
                    >
                    @error('nivel_salarial')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- BLOQUE 5: CLUES --}}
            <h6 class="mb-3">Unidad / CLUES</h6>

            @php
                $cluesCatalogKeyOld = old('clues_catalog_key');
                $cluesLabelOld = old('clues_label');
            @endphp

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">CLUES <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        id="clues_search"
                        name="clues_label"
                        list="clues_options"
                        class="form-control @error('clues_catalog_key') is-invalid @enderror @error('clave_clues') is-invalid @enderror"
                        value="{{ $cluesLabelOld }}"
                        autocomplete="off"
                        required
                    >
                    <datalist id="clues_options"></datalist>
                    <input
                        type="hidden"
                        id="clues_catalog_key"
                        name="clues_catalog_key"
                        value="{{ $cluesCatalogKeyOld }}"
                    >
                    @error('clave_clues')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    @error('clues_catalog_key')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Clave CLUES</label>
                    <input
                        type="text"
                        id="clave_clues"
                        name="clave_clues"
                        class="form-control @error('clave_clues') is-invalid @enderror"
                        value="{{ old('clave_clues') }}"
                        readonly
                        required
                    >
                    <input
                        type="hidden"
                        id="id_clues"
                        name="id_clues"
                        value="{{ old('id_clues') }}"
                    >
                </div>

                <div class="col-md-5">
                    <label class="form-label">Descripción CLUES</label>
                    <input
                        type="text"
                        id="descripcion_clues"
                        name="descripcion_clues"
                        class="form-control @error('descripcion_clues') is-invalid @enderror"
                        value="{{ old('descripcion_clues') }}"
                        readonly
                        required
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

    {{-- Script directo (sin @push) para que siempre se ejecute --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('formEmpleado');
            const btnGuardar = document.getElementById('btnGuardar');
            const curpInput = document.getElementById('curp');
            const sexoSelect = document.getElementById('sexo');
            const puestoSelect = document.getElementById('codigo_puesto');
            const codigoPuestoText = document.getElementById('codigo_puesto_text');
            const nombrePuestoInput = document.getElementById('nombre_puesto');
            const nivelSalarialInput = document.getElementById('nivel_salarial');
            const cluesSearchInput = document.getElementById('clues_search');
            const cluesDatalist = document.getElementById('clues_options');
            const cluesCatalogKeyInput = document.getElementById('clues_catalog_key');
            const idCluesInput = document.getElementById('id_clues');
            const claveCluesInput = document.getElementById('clave_clues');
            const descripcionCluesInput = document.getElementById('descripcion_clues');
            const nominaInput = document.getElementById('nomina');
            const entidadInput = document.getElementById('entidad');
            const curpRegex = /^[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[A-Z0-9][0-9]$/;
            const cluesSearchUrl = @json(route('empleado.catalogos.clues'));
            const cluesOptionsByLabel = new Map();
            let selectedCluesLabel = cluesSearchInput ? cluesSearchInput.value : '';
            let cluesSearchTimer = null;

            // Prevenir doble envío
            if (form && btnGuardar) {
                form.addEventListener('submit', function() {
                    syncSexoFromCurp();
                    syncPuesto();
                    syncCluesFromText();

                    if (!form.checkValidity()) {
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

            function syncPuesto() {
                if (!puestoSelect || !codigoPuestoText || !nombrePuestoInput) {
                    return;
                }

                const option = puestoSelect.selectedOptions[0];

                if (!option || !option.value) {
                    codigoPuestoText.value = '';
                    nombrePuestoInput.value = '';
                    if (nivelSalarialInput) {
                        nivelSalarialInput.value = '';
                    }
                    return;
                }

                codigoPuestoText.value = option.value;
                nombrePuestoInput.value = option.dataset.puesto || '';

                if (nivelSalarialInput) {
                    nivelSalarialInput.value = option.dataset.nivel || '';
                }
            }

            function renderCluesOptions(options) {
                cluesOptionsByLabel.clear();

                if (!cluesDatalist) {
                    return;
                }

                cluesDatalist.innerHTML = '';

                options.forEach(function(optionData) {
                    cluesOptionsByLabel.set(optionData.label, optionData);

                    const option = document.createElement('option');
                    option.value = optionData.label;
                    cluesDatalist.appendChild(option);
                });
            }

            async function fetchCluesOptions(query) {
                if (!cluesSearchUrl || query.trim().length < 2) {
                    renderCluesOptions([]);
                    return;
                }

                try {
                    const url = new URL(cluesSearchUrl, window.location.origin);
                    url.searchParams.set('q', query.trim());

                    const response = await fetch(url.toString(), {
                        headers: {
                            'Accept': 'application/json',
                        },
                    });

                    const data = await response.json();

                    if (!response.ok || !data.status) {
                        renderCluesOptions([]);
                        return;
                    }

                    renderCluesOptions(data.options || []);
                    syncCluesFromText();
                } catch (error) {
                    renderCluesOptions([]);
                }
            }

            function clearCluesFields() {
                if (cluesCatalogKeyInput) {
                    cluesCatalogKeyInput.value = '';
                }

                if (idCluesInput) {
                    idCluesInput.value = '';
                }

                if (claveCluesInput) {
                    claveCluesInput.value = '';
                }

                if (descripcionCluesInput) {
                    descripcionCluesInput.value = '';
                }
            }

            function applyCluesOption(optionData) {
                if (!optionData || !claveCluesInput || !descripcionCluesInput) {
                    return;
                }

                if (cluesCatalogKeyInput) {
                    cluesCatalogKeyInput.value = optionData.catalog_key || '';
                }

                if (idCluesInput) {
                    idCluesInput.value = optionData.id_clues || '';
                }

                claveCluesInput.value = optionData.clave_clues || '';
                descripcionCluesInput.value = optionData.descripcion_clues || '';
                selectedCluesLabel = optionData.label || '';

                if (nominaInput && optionData.nomina) {
                    nominaInput.value = optionData.nomina;
                }

                if (entidadInput && optionData.entidad) {
                    entidadInput.value = optionData.entidad;
                }
            }

            function syncCluesFromText() {
                if (!cluesSearchInput) {
                    return;
                }

                const typedLabel = cluesSearchInput.value.trim();

                if (typedLabel === '') {
                    selectedCluesLabel = '';
                    clearCluesFields();
                    return;
                }

                const optionData = cluesOptionsByLabel.get(typedLabel);

                if (optionData) {
                    applyCluesOption(optionData);
                    return;
                }

                if (typedLabel !== selectedCluesLabel) {
                    clearCluesFields();
                }
            }

            if (curpInput) {
                curpInput.addEventListener('input', syncSexoFromCurp);
                syncSexoFromCurp();
            }

            if (puestoSelect) {
                puestoSelect.addEventListener('change', syncPuesto);
                syncPuesto();
            }

            if (cluesSearchInput) {
                cluesSearchInput.addEventListener('input', function() {
                    syncCluesFromText();

                    window.clearTimeout(cluesSearchTimer);
                    cluesSearchTimer = window.setTimeout(function() {
                        fetchCluesOptions(cluesSearchInput.value);
                    }, 250);
                });

                cluesSearchInput.addEventListener('change', syncCluesFromText);
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
