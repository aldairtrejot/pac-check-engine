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
        :max-height="220"
        :options-limit="50"
        placeholder="Buscar CLUES..."
        :error-message="errors.clues"
        @search-change="handleCluesSearch"
      />

      <input type="hidden" id="clues_catalog_key" name="clues_catalog_key" :value="cluesCatalogKey">
      <input type="hidden" id="id_clues" name="id_clues" :value="cluesId">

      <div class="col-md-3">
        <label class="form-label">Clave CLUES</label>
        <input
          type="text"
          id="clave_clues"
          name="clave_clues"
          class="form-control"
          :value="cluesClave"
          readonly
          required
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
          readonly
          required
        >
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import inputSelect from '@helpers/form/input-select.vue'

const props = readCatalogProps()
const old = props.old || {}
const puestoOptions = ref(Array.isArray(props.puestos) ? props.puestos : [])
const cluesOptions = ref([])
const selectedPuesto = ref(null)
const selectedClues = ref(null)
const isLoadingClues = ref(false)
const errors = reactive({
  puesto: '',
  clues: '',
})

let cluesSearchTimer = null
let formElement = null

const puestoCodigo = computed(() => selectedPuesto.value?.codigo || '')
const puestoNombre = computed(() => selectedPuesto.value?.puesto || '')
const puestoNivel = computed(() => selectedPuesto.value?.nivel || '')
const cluesCatalogKey = computed(() => selectedClues.value?.catalog_key || '')
const cluesId = computed(() => selectedClues.value?.id_clues || '')
const cluesClave = computed(() => selectedClues.value?.clave_clues || '')
const cluesDescripcion = computed(() => selectedClues.value?.descripcion_clues || '')

watch(selectedPuesto, (value) => {
  if (value?.codigo) {
    errors.puesto = ''
  }
})

watch(selectedClues, (value) => {
  if (!value) {
    return
  }

  errors.clues = ''
  setExternalField('nomina', value.nomina)
  setExternalField('entidad', value.entidad)
})

onMounted(() => {
  selectedPuesto.value = getInitialPuesto()
  selectedClues.value = getInitialClues()

  if (selectedClues.value) {
    cluesOptions.value = [selectedClues.value]
  }

  hydrateSelectedClues()

  formElement = document.getElementById('formEmpleado')
  formElement?.addEventListener('submit', validateCatalogs, true)
})

onBeforeUnmount(() => {
  window.clearTimeout(cluesSearchTimer)
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
  }
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

    cluesOptions.value = withSelectedClues(options)
  } catch (error) {
    cluesOptions.value = selectedClues.value ? [selectedClues.value] : []
  } finally {
    isLoadingClues.value = false
  }
}

function withSelectedClues(options) {
  if (!selectedClues.value?.catalog_key) {
    return options
  }

  const exists = options.some((option) => option.catalog_key === selectedClues.value.catalog_key)
  return exists ? options : [selectedClues.value, ...options]
}

function validateCatalogs(event) {
  const hasPuesto = puestoCodigo.value !== '' && puestoNombre.value !== ''
  const hasClues = cluesCatalogKey.value !== '' && cluesClave.value !== '' && cluesDescripcion.value !== ''

  errors.puesto = hasPuesto ? '' : 'Selecciona un puesto del catálogo.'
  errors.clues = hasClues ? '' : 'Selecciona una CLUES del catálogo.'

  if (hasPuesto && hasClues) {
    return
  }

  event.preventDefault()
  event.stopImmediatePropagation()

  const targetId = !hasPuesto ? 'puesto_catalog_select' : 'clues_catalog_select'
  document.getElementById(targetId)?.scrollIntoView({ behavior: 'smooth', block: 'center' })
}

function setExternalField(id, value) {
  const element = document.getElementById(id)

  if (element && asString(value) !== '') {
    element.value = value
  }
}

function asString(value) {
  return String(value ?? '').trim()
}
</script>
