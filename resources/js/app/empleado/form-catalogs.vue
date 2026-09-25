<template>
  <div>
    <h6 class="mb-3">Datos del puesto</h6>

    <div class="row mb-3">
      <inputSelect
        grid="col-md-6"
        label="Nombre del puesto"
        id="puesto_catalog_select"
        name="puesto_catalog_select"
        v-model="selectedPuesto"
        :options="puestoOptions"
        :multiple="false"
        labelKey="label"
        trackBy="codigo"
        :required="true"
        :allow-empty="false"
        :max-height="220"
        :options-limit="50"
        placeholder="Seleccione..."
        :error-message="errors.puesto"
      />

      <input type="hidden" id="nombre_puesto" name="nombre_puesto" :value="puestoNombre">
      <input type="hidden" id="codigo_puesto" name="codigo_puesto" :value="puestoCodigo">

      <div class="col-md-3">
        <label class="form-label">Código de puesto</label>
        <input
          type="text"
          id="codigo_puesto_text"
          class="form-control"
          :value="puestoCodigo"
          readonly
          required
        >
      </div>

      <div class="col-md-3">
        <label class="form-label">Nivel salarial</label>
        <input
          type="text"
          id="nivel_salarial"
          name="nivel_salarial"
          class="form-control"
          :value="puestoNivel"
          readonly
        >
      </div>
    </div>

    <h6 class="mb-3">Unidad / CLUES</h6>

    <div class="row mb-3">
      <inputSelect
        grid="col-md-4"
        label="CLUES"
        id="clues_catalog_select"
        name="clues_catalog_select"
        v-model="selectedClues"
        :options="cluesOptions"
        :multiple="false"
        labelKey="label"
        trackBy="catalog_key"
        :required="true"
        :allow-empty="false"
        :internal-search="false"
        :loading="isLoadingClues"
        :taggable="true"
        tag-placeholder="Capturar manualmente"
        :max-height="220"
        :options-limit="50"
        placeholder="Buscar CLUES..."
        :error-message="errors.clues"
        @search-change="handleCluesSearch"
        @tag="addManualCluesOption"
      />

      <input type="hidden" id="clues_catalog_key" name="clues_catalog_key" :value="cluesCatalogKey">
      <input type="hidden" id="id_clues" name="id_clues" :value="cluesId">
      <input type="hidden" id="clues_manual" name="clues_manual" :value="isManualClues ? '1' : '0'">

      <div class="col-md-3">
        <label class="form-label">Clave CLUES</label>
        <input
          type="text"
          id="clave_clues"
          name="clave_clues"
          class="form-control"
          :value="cluesClave"
          :readonly="!isManualClues"
          required
          style="text-transform: uppercase;"
          @input="updateManualCluesClave"
        >
      </div>

      <div class="col-md-5">
        <label class="form-label">Descripción CLUES</label>
        <input
          type="text"
          id="descripcion_clues"
          name="descripcion_clues"
          class="form-control"
          :value="cluesDescripcion"
          :readonly="!isManualClues"
          required
          style="text-transform: uppercase;"
          @input="updateManualCluesDescription"
        >
      </div>

      <div v-if="isManualClues" class="col-12 mt-2">
        <div class="alert alert-warning py-2 mb-0">
          No se encontro esta CLUES en el catalogo. Al guardar, se agregara a cat_clues_bi si todavia no existe.
        </div>
      </div>
    </div>

    <h6 class="mb-3">Adscripcion</h6>

    <div class="row mb-3">
      <inputSelect
        grid="col-md-12"
        label="Adscripcion"
        id="adscripcion_catalog_select"
        name="adscripcion_catalog_select"
        v-model="selectedAdscripcion"
        :options="adscripcionOptions"
        :multiple="false"
        labelKey="label"
        trackBy="id_adscripcion"
        :required="true"
        :allow-empty="false"
        :internal-search="false"
        :loading="isLoadingAdscripciones"
        :max-height="260"
        :options-limit="50"
        placeholder="Buscar adscripcion..."
        :error-message="errors.adscripcion"
        @search-change="handleAdscripcionSearch"
      />

      <input type="hidden" id="id_adscripcion" name="id_adscripcion" :value="adscripcionId">
      <input type="hidden" id="adscripcion" name="adscripcion" :value="adscripcionNombre">
      <input type="hidden" id="adscripcion_compl" name="adscripcion_compl" :value="adscripcionCompleta">
      <input type="hidden" id="id_unidad" name="id_unidad" :value="adscripcionIdUnidad">
      <input type="hidden" id="nombre_unidad" name="nombre_unidad" :value="adscripcionUnidad">
      <input type="hidden" id="id_coordinacion" name="id_coordinacion" :value="adscripcionIdCoordinacion">
      <input type="hidden" id="nombre_coordinacion" name="nombre_coordinacion" :value="adscripcionCoordinacion">

      <div class="col-md-4 mt-2">
        <label class="form-label">ID Adscripcion</label>
        <input type="text" class="form-control" :value="adscripcionId" readonly required>
      </div>

      <div class="col-md-4 mt-2">
        <label class="form-label">Unidad</label>
        <input type="text" class="form-control" :value="adscripcionUnidad" readonly required>
      </div>

      <div class="col-md-4 mt-2">
        <label class="form-label">Coordinacion</label>
        <input type="text" class="form-control" :value="adscripcionCoordinacion" readonly required>
      </div>

      <div class="col-12 mt-2">
        <label class="form-label">Adscripcion completa</label>
        <input type="text" class="form-control" :value="adscripcionCompleta" readonly required>
      </div>
    </div>

    <h6 class="mb-3">Datos de plantilla</h6>

    <div class="row mb-3">
      <inputSelect
        grid="col-md-12"
        label="Val Plantilla"
        id="val_plantilla_select"
        name="val_plantilla_select"
        v-model="selectedValPlantilla"
        :options="valPlantillaOptions"
        :multiple="false"
        labelKey="label"
        trackBy="value"
        :required="true"
        :allow-empty="false"
        :taggable="true"
        tag-placeholder="Agregar valor"
        :max-height="220"
        :options-limit="50"
        placeholder="Seleccione o capture..."
        :error-message="errors.valPlantilla"
        @search-change="handleValPlantillaSearch"
        @tag="addValPlantillaOption"
      />

      <input type="hidden" id="val_plantilla" name="val_plantilla" :value="valPlantillaValue">
    </div>

    <div class="row mb-3">
      <inputSelect
        grid="col-md-12"
        label="Nomina Dos"
        id="nomina_dos_select"
        name="nomina_dos_select"
        v-model="selectedNominaDos"
        :options="nominaDosOptions"
        :multiple="false"
        labelKey="label"
        trackBy="value"
        :required="false"
        :allow-empty="true"
        :max-height="220"
        :options-limit="50"
        placeholder="Seleccione..."
        :error-message="errors.nominaDos"
      />

      <input type="hidden" id="nomina_dos" name="nomina_dos" :value="nominaDosValue">
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import inputSelect from '@helpers/form/input-select.vue'

const props = readCatalogProps()
const old = props.old || {}
const puestoOptions = ref(Array.isArray(props.puestos) ? props.puestos : [])
const valPlantillaOptions = ref(Array.isArray(props.valPlantillaOptions) ? props.valPlantillaOptions : [])
const nominaDosOptions = ref(Array.isArray(props.nominaDosOptions) ? props.nominaDosOptions : [])
const cluesOptions = ref([])
const adscripcionOptions = ref([])
const selectedPuesto = ref(null)
const selectedClues = ref(null)
const selectedValPlantilla = ref(null)
const selectedNominaDos = ref(null)
const selectedAdscripcion = ref(null)
const isLoadingClues = ref(false)
const isLoadingAdscripciones = ref(false)
const manualCluesClave = ref('')
const manualCluesDescription = ref('')
const errors = reactive({
  puesto: '',
  clues: '',
  valPlantilla: '',
  nominaDos: '',
  adscripcion: '',
})

let cluesSearchTimer = null
let adscripcionSearchTimer = null
let formElement = null
const valPlantillaSearch = ref('')

const puestoCodigo = computed(() => selectedPuesto.value?.codigo || '')
const puestoNombre = computed(() => selectedPuesto.value?.puesto || '')
const puestoNivel = computed(() => selectedPuesto.value?.nivel || '')
const isManualClues = computed(() => !!selectedClues.value?.manual)
const cluesCatalogKey = computed(() => isManualClues.value ? makeManualCluesCatalogKey() : (selectedClues.value?.catalog_key || ''))
const cluesId = computed(() => selectedClues.value?.id_clues || '')
const cluesClave = computed(() => isManualClues.value ? manualCluesClave.value : (selectedClues.value?.clave_clues || ''))
const cluesDescripcion = computed(() => isManualClues.value ? manualCluesDescription.value : (selectedClues.value?.descripcion_clues || ''))
const valPlantillaValue = computed(() => selectedValPlantilla.value?.value || '')
const nominaDosValue = computed(() => selectedNominaDos.value?.value || '')
const adscripcionId = computed(() => selectedAdscripcion.value?.id_adscripcion || '')
const adscripcionNombre = computed(() => selectedAdscripcion.value?.adscripcion || '')
const adscripcionCompleta = computed(() => selectedAdscripcion.value?.adscripcion_compl || selectedAdscripcion.value?.label || '')
const adscripcionIdUnidad = computed(() => selectedAdscripcion.value?.id_unidad || '')
const adscripcionUnidad = computed(() => selectedAdscripcion.value?.nombre_unidad || '')
const adscripcionIdCoordinacion = computed(() => selectedAdscripcion.value?.id_coordinacion || '')
const adscripcionCoordinacion = computed(() => selectedAdscripcion.value?.nombre_coordinacion || '')

watch(selectedPuesto, (value) => {
  if (value?.codigo) {
    errors.puesto = ''
  }
})

watch(selectedClues, (value) => {
  if (!value) {
    manualCluesClave.value = ''
    manualCluesDescription.value = ''
    return
  }

  errors.clues = ''

  if (value.manual) {
    manualCluesClave.value = asString(value.clave_clues).toUpperCase()
    manualCluesDescription.value = asString(value.descripcion_clues).toUpperCase()
    return
  }

  setExternalField('nomina', value.nomina)
  setExternalField('entidad', value.entidad)
  setExternalField('nivel_atencion', value.nivel_atencion)
})

watch(selectedValPlantilla, (value) => {
  if (value?.value) {
    errors.valPlantilla = ''
  }
})

watch(selectedNominaDos, () => {
  errors.nominaDos = ''
})

watch(selectedAdscripcion, (value) => {
  if (value?.id_adscripcion) {
    errors.adscripcion = ''
  }
})

onMounted(() => {
  selectedPuesto.value = getInitialPuesto()
  selectedClues.value = getInitialClues()
  selectedValPlantilla.value = getInitialValPlantilla()
  selectedNominaDos.value = getInitialNominaDos()
  selectedAdscripcion.value = getInitialAdscripcion()

  if (selectedClues.value) {
    cluesOptions.value = [selectedClues.value]
  }

  if (selectedAdscripcion.value) {
    adscripcionOptions.value = [selectedAdscripcion.value]
  }

  hydrateSelectedClues()
  hydrateSelectedAdscripcion()

  formElement = document.getElementById('formEmpleado')
  formElement?.addEventListener('submit', validateCatalogs, true)
})

onBeforeUnmount(() => {
  window.clearTimeout(cluesSearchTimer)
  window.clearTimeout(adscripcionSearchTimer)
  formElement?.removeEventListener('submit', validateCatalogs, true)
})

function readCatalogProps() {
  const element = document.getElementById('empleado_catalog_props')

  if (!element) {
    return {}
  }

  try {
    return JSON.parse(element.textContent || '{}')
  } catch (error) {
    return {}
  }
}

function getInitialPuesto() {
  const codigo = asString(old.codigo_puesto)

  if (codigo === '') {
    return null
  }

  const option = puestoOptions.value.find((puesto) => asString(puesto.codigo) === codigo)

  if (option) {
    return option
  }

  return {
    label: asString(old.puesto_label) || [asString(old.nombre_puesto), codigo].filter(Boolean).join(' - '),
    codigo,
    puesto: asString(old.nombre_puesto),
    nivel: asString(old.nivel_salarial),
  }
}

function getInitialClues() {
  const catalogKey = asString(old.clues_catalog_key)
  const clave = asString(old.clave_clues)
  const descripcion = asString(old.descripcion_clues)

  if (catalogKey === '' && clave === '' && descripcion === '') {
    return null
  }

  return {
    label: asString(old.clues_label) || [descripcion, clave].filter(Boolean).join(' - '),
    catalog_key: catalogKey,
    id_clues: asString(old.id_clues),
    clave_clues: clave,
    descripcion_clues: descripcion,
    nomina: asString(old.nomina),
    entidad: asString(old.entidad),
    nivel_atencion: asString(old.nivel_atencion),
    manual: asString(old.clues_manual) === '1',
  }
}

function getInitialValPlantilla() {
  const value = asString(old.val_plantilla).toUpperCase()

  if (value === '') {
    return null
  }

  const option = valPlantillaOptions.value.find((item) => asString(item.value) === value)

  if (option) {
    return option
  }

  const newOption = {
    label: value,
    value,
  }

  valPlantillaOptions.value = [newOption, ...valPlantillaOptions.value]

  return newOption
}

function getInitialNominaDos() {
  const value = asString(old.nomina_dos).toUpperCase()

  if (value === '') {
    return null
  }

  const option = nominaDosOptions.value.find((item) => asString(item.value) === value)

  if (option) {
    return option
  }

  return {
    label: value,
    value,
  }
}

function getInitialAdscripcion() {
  const idAdscripcion = asString(old.id_adscripcion)

  if (idAdscripcion === '') {
    return null
  }

  return normalizeAdscripcionOption({
    id_adscripcion: idAdscripcion,
    adscripcion: asString(old.adscripcion),
    adscripcion_compl: asString(old.adscripcion_compl),
    id_unidad: asString(old.id_unidad),
    nombre_unidad: asString(old.nombre_unidad),
    id_coordinacion: asString(old.id_coordinacion),
    nombre_coordinacion: asString(old.nombre_coordinacion),
  })
}

async function hydrateSelectedClues() {
  const catalogKey = asString(old.clues_catalog_key)

  if (catalogKey === '' || !props.cluesSearchUrl) {
    return
  }

  try {
    const url = new URL(props.cluesSearchUrl, window.location.origin)
    url.searchParams.set('key', catalogKey)

    const response = await fetch(url.toString(), {
      headers: {
        Accept: 'application/json',
      },
    })

    const data = await response.json()
    const option = data?.options?.[0] || null

    if (response.ok && data?.status && option) {
      selectedClues.value = option
      cluesOptions.value = [option]
    }
  } catch (error) {
    // La validación del servidor vuelve a confirmar el catálogo al guardar.
  }
}

async function hydrateSelectedAdscripcion() {
  const idAdscripcion = asString(old.id_adscripcion)

  if (idAdscripcion === '' || !props.adscripcionesSearchUrl) {
    return
  }

  try {
    const url = new URL(props.adscripcionesSearchUrl, window.location.origin)
    url.searchParams.set('id_adscripcion', idAdscripcion)

    const response = await fetch(url.toString(), {
      headers: {
        Accept: 'application/json',
      },
    })

    const data = await response.json()
    const option = normalizeAdscripcionOption(data?.options?.[0] || null)

    if (response.ok && data?.status && option) {
      selectedAdscripcion.value = option
      adscripcionOptions.value = [option]
    }
  } catch (error) {
    // La validacion del servidor vuelve a confirmar el catalogo al guardar.
  }
}

function handleCluesSearch(search) {
  const term = asString(search)
  window.clearTimeout(cluesSearchTimer)

  if (term.length < 2) {
    cluesOptions.value = selectedClues.value ? [selectedClues.value] : []
    return
  }

  cluesSearchTimer = window.setTimeout(() => {
    fetchCluesOptions(term)
  }, 250)
}

function handleAdscripcionSearch(search) {
  const term = asString(search)
  window.clearTimeout(adscripcionSearchTimer)

  if (term.length < 2) {
    adscripcionOptions.value = selectedAdscripcion.value ? [selectedAdscripcion.value] : []
    return
  }

  adscripcionSearchTimer = window.setTimeout(() => {
    fetchAdscripcionOptions(term)
  }, 250)
}

async function fetchCluesOptions(term) {
  if (!props.cluesSearchUrl) {
    cluesOptions.value = selectedClues.value ? [selectedClues.value] : []
    return
  }

  isLoadingClues.value = true

  try {
    const url = new URL(props.cluesSearchUrl, window.location.origin)
    url.searchParams.set('q', term)

    const response = await fetch(url.toString(), {
      headers: {
        Accept: 'application/json',
      },
    })

    const data = await response.json()
    const options = response.ok && data?.status && Array.isArray(data.options)
      ? data.options
      : []

    cluesOptions.value = withSelectedClues(withManualCluesOption(options, term))
  } catch (error) {
    cluesOptions.value = selectedClues.value ? [selectedClues.value] : []
  } finally {
    isLoadingClues.value = false
  }
}

async function fetchAdscripcionOptions(term) {
  if (!props.adscripcionesSearchUrl) {
    adscripcionOptions.value = selectedAdscripcion.value ? [selectedAdscripcion.value] : []
    return
  }

  isLoadingAdscripciones.value = true

  try {
    const url = new URL(props.adscripcionesSearchUrl, window.location.origin)
    url.searchParams.set('q', term)

    const response = await fetch(url.toString(), {
      headers: {
        Accept: 'application/json',
      },
    })

    const data = await response.json()
    const options = response.ok && data?.status && Array.isArray(data.options)
      ? data.options.map((option) => normalizeAdscripcionOption(option)).filter(Boolean)
      : []

    adscripcionOptions.value = withSelectedAdscripcion(options)
  } catch (error) {
    adscripcionOptions.value = selectedAdscripcion.value ? [selectedAdscripcion.value] : []
  } finally {
    isLoadingAdscripciones.value = false
  }
}

function withSelectedClues(options) {
  if (!selectedClues.value?.catalog_key) {
    return options
  }

  const exists = options.some((option) => option.catalog_key === selectedClues.value.catalog_key)
  return exists ? options : [selectedClues.value, ...options]
}

function withManualCluesOption(options, term) {
  const clave = normalizeCluesClave(term)

  if (clave.length < 4 || options.length > 0) {
    return options
  }

  return [makeManualCluesOption(clave)]
}

function withSelectedAdscripcion(options) {
  if (!selectedAdscripcion.value?.id_adscripcion) {
    return options
  }

  const exists = options.some(
    (option) => String(option.id_adscripcion) === String(selectedAdscripcion.value.id_adscripcion)
  )

  return exists ? options : [selectedAdscripcion.value, ...options]
}

function normalizeAdscripcionOption(option) {
  if (!option?.id_adscripcion) {
    return null
  }

  const adscripcion = asString(option.adscripcion ?? option.adscripcion_txt).toUpperCase()
  const adscripcionCompleta = asString(option.adscripcion_compl).toUpperCase()
  const label = asString(option.label || adscripcionCompleta || adscripcion).toUpperCase()

  return {
    id: option.id_adscripcion,
    id_adscripcion: option.id_adscripcion,
    adscripcion,
    adscripcion_compl: adscripcionCompleta,
    id_unidad: option.id_unidad ?? '',
    nombre_unidad: asString(option.nombre_unidad ?? option.unidad_txt).toUpperCase(),
    id_coordinacion: option.id_coordinacion ?? '',
    nombre_coordinacion: asString(option.nombre_coordinacion ?? option.coordinacion_txt).toUpperCase(),
    label,
    descripcion: label,
  }
}

function addValPlantillaOption(tag) {
  const value = asString(tag).toUpperCase()

  if (value === '') {
    return
  }

  const option = valPlantillaOptions.value.find((item) => asString(item.value) === value) || {
    label: value,
    value,
  }

  if (!valPlantillaOptions.value.some((item) => asString(item.value) === value)) {
    valPlantillaOptions.value = [option, ...valPlantillaOptions.value]
  }

  selectedValPlantilla.value = option
  valPlantillaSearch.value = ''
  errors.valPlantilla = ''
}

function addManualCluesOption(tag) {
  const option = makeManualCluesOption(tag)

  if (!option) {
    return
  }

  cluesOptions.value = [option, ...cluesOptions.value.filter((item) => item.catalog_key !== option.catalog_key)]
  selectedClues.value = option
  errors.clues = ''
}

function makeManualCluesOption(value) {
  const clave = normalizeCluesClave(value)

  if (clave === '') {
    return null
  }

  return {
    label: `CAPTURAR MANUALMENTE: ${clave}`,
    catalog_key: '',
    id_clues: '',
    clave_clues: clave,
    descripcion_clues: '',
    nomina: '',
    entidad: '',
    nivel_atencion: '',
    manual: true,
  }
}

function makeManualCluesCatalogKey() {
  const payload = {
    idcat: '',
    clave_clues: cluesClave.value,
    descripcion_clues: cluesDescripcion.value,
    nomina: asString(document.getElementById('nomina')?.value).toUpperCase(),
    entidad: asString(document.getElementById('entidad')?.value).toUpperCase(),
    nivel_atencion: asString(document.getElementById('nivel_atencion')?.value).toUpperCase(),
    manual: true,
  }

  if (payload.clave_clues === '' || payload.descripcion_clues === '') {
    return ''
  }

  try {
    return window.btoa(unescape(encodeURIComponent(JSON.stringify(payload))))
  } catch (error) {
    return ''
  }
}

function updateManualCluesClave(event) {
  if (!isManualClues.value) {
    return
  }

  manualCluesClave.value = normalizeCluesClave(event.target.value)
  event.target.value = manualCluesClave.value
  updateManualCluesLabel()
}

function updateManualCluesDescription(event) {
  if (!isManualClues.value) {
    return
  }

  manualCluesDescription.value = asString(event.target.value).toUpperCase()
  event.target.value = manualCluesDescription.value
  updateManualCluesLabel()
}

function updateManualCluesLabel() {
  if (!selectedClues.value?.manual) {
    return
  }

  selectedClues.value = {
    ...selectedClues.value,
    label: [manualCluesDescription.value, manualCluesClave.value].filter(Boolean).join(' - ') || `CAPTURAR MANUALMENTE: ${manualCluesClave.value}`,
    clave_clues: manualCluesClave.value,
    descripcion_clues: manualCluesDescription.value,
    catalog_key: makeManualCluesCatalogKey(),
  }
}

function handleValPlantillaSearch(search) {
  valPlantillaSearch.value = asString(search).toUpperCase()
}

function validateCatalogs(event) {
  const hasPuesto = puestoCodigo.value !== '' && puestoNombre.value !== ''
  const hasClues = cluesCatalogKey.value !== '' && cluesClave.value !== '' && cluesDescripcion.value !== ''
  const hasAdscripcion = adscripcionId.value !== ''

  if (!selectedValPlantilla.value && valPlantillaSearch.value !== '') {
    addValPlantillaOption(valPlantillaSearch.value)
  }

  const hasValPlantilla = valPlantillaValue.value !== ''

  errors.puesto = hasPuesto ? '' : 'Selecciona un puesto del catalogo.'
  errors.clues = hasClues
    ? ''
    : (isManualClues.value ? 'Captura clave y descripcion de la CLUES.' : 'Selecciona una CLUES del catalogo o capturala manualmente si no existe.')

  errors.adscripcion = hasAdscripcion ? '' : 'Selecciona una Adscripcion del catalogo.'
  errors.valPlantilla = hasValPlantilla ? '' : 'Selecciona o captura Val Plantilla.'

  if (hasPuesto && hasClues && hasAdscripcion && hasValPlantilla) {
    return
  }

  event.preventDefault()
  event.stopImmediatePropagation()

  const targetId = !hasPuesto
    ? 'puesto_catalog_select'
    : (!hasClues ? 'clues_catalog_select' : (!hasAdscripcion ? 'adscripcion_catalog_select' : 'val_plantilla_select'))

  document.getElementById(targetId)?.scrollIntoView({ behavior: 'smooth', block: 'center' })
}

function setExternalField(id, value) {
  const element = document.getElementById(id)

  if (element) {
    element.value = asString(value).toUpperCase()
  }
}

function asString(value) {
  return String(value ?? '').trim()
}

function normalizeCluesClave(value) {
  return asString(value).toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 50)
}
</script>
