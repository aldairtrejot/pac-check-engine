<template>
  <div>
    <tableTittle value="Mi plantilla" />

    <div class="accordion accordion-flush shadow-sm rounded-3 border" id="accordionFilters">
      <div class="accordion-item">
        <h2 class="accordion-header" id="headingFilters">
          <button
            class="accordion-button collapsed d-flex align-items-center gap-2 fw-bold"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#collapseFilters"
            aria-expanded="false"
            aria-controls="collapseFilters"
          >
            <i class="fas fa-filter text-secondary"></i> Filtros de búsqueda
          </button>
        </h2>

        <div
          id="collapseFilters"
          class="accordion-collapse collapse"
          aria-labelledby="headingFilters"
          data-bs-parent="#accordionFilters"
        >
          <div class="accordion-body bg-light rounded-bottom p-2">
            <div class="row g-1 mb-0">
              <inputField :grid="gridx3" label="Nombre" id="name" v-model="name" :uppercase="true" />
              <inputField :grid="gridx3" label="CURP" id="curp" v-model="curp" :uppercase="true" />
            </div>

            <div class="row" style="margin-top: -70px !important;">
              <inputSelect
                v-model="listSelectAcction"
                :options="listOptionsAcction"
                id="id_accion"
                label="Acción"
                :multiple="false"
                grid="col-md-12 col-sm-12"
                :required="true"
              />
            </div>

            <div v-if="isAdminPac" class="row g-2 mt-2">
              <inputSelect
                grid="col-12 col-md-4 mb-2"
                label="Entidad"
                id="f_entidad"
                name="f_entidad"
                v-model="f_entidad"
                :options="opcionesEntidades"
                :multiple="false"
                labelKey="label"
                trackBy="value"
                placeholder="Todas"
              />

              <inputSelect
                grid="col-12 col-md-4 mb-2"
                label="Tipo nómina"
                id="f_tipo_nomina"
                name="f_tipo_nomina"
                v-model="f_tipo_nomina"
                :options="opcionesTiposNomina"
                :multiple="false"
                labelKey="label"
                trackBy="value"
                placeholder="Todos"
              />

              <inputSelect
                grid="col-12 col-md-4 mb-2"
                label="CLUES"
                id="f_clues"
                name="f_clues"
                v-model="f_clues"
                :options="opcionesClues"
                :multiple="false"
                labelKey="label"
                trackBy="value"
                :disabled="!optionValue(f_entidad) || isLoadingClues"
                :placeholder="optionValue(f_entidad) ? (isLoadingClues ? 'Cargando CLUES...' : 'Todas') : 'Selecciona una entidad'"
              />
            </div>

            <div class="d-flex justify-content-end flex-wrap gap-1 mt-2">
              <tableButtonDefault
                color="white"
                icon="fas fa-brush"
                label="Limpiar"
                @click="clear_search"
                color_icon="#777777"
                :clickEventPayload="null"
              />
              <button type="button" class="btn btn-sm cap-btn-primary" @click="search_function">
                <i class="fa fa-search"></i>
                <span class="d-none d-sm-inline">Buscar</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <br />

    <tableSpinner ref="spinnerRef" />

    <div class="table-responsive">
      <table class="table align-items-center mb-0" id="table-default">
        <thead>
          <tr>
            <tableRow value="Acciones" />
            <tableRow value="Atendido" />
            <tableRow value="Nombre" />
            <tableRow value="CURP" />
            <tableRow value="Acción" />
          </tr>
        </thead>
        <tbody>
          <tableEmpty v-if="item.length === 0" :colspan="5" />

          <tr v-for="rowx in item" :key="getRowId(rowx)">
            <td class="text-center">
              <div class="button-container">
                <!-- Atender -->
                <tableButtonDefault
                  color="#081F5E"
                  icon="fa fa-external-link"
                  label="Atender"
                  @click="setOption"
                  :clickEventPayload="getRowId(rowx)"
                />

                <!-- Agregar curso -->
                <tableButtonDefault
                  color="#0E5E08"
                  icon="fa fa-plus"
                  label="Agregar curso"
                  @click="openAddCourse"
                  :clickEventPayload="getRowId(rowx)"
                />
              </div>
            </td>

            <td class="align-middle text-center">
              <span class="cap-status-pill" :class="statusPillClass(rowx.atendido)">
                {{ rowx.atendido || '—' }}
              </span>
            </td>
            <td class="align-middle text-center">
              <p class="text-xs font-weight-bold mb-0">{{ rowx.nombre }}</p>
              <p class="text-xs text-secondary mb-0">{{ rowx.apellido }}</p>
            </td>
            <td class="align-middle text-center">
              <span class="text-secondary text-xs font-weight-bold">{{ rowx.curp }}</span>
            </td>
            <td class="align-middle text-center">
              <span class="text-secondary text-xs font-weight-bold">{{ rowx.accion }}</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <tableFooter :row="row" :rowsAll="rowsAll" />
  </div>

  <!-- Modal DATOS EMPLEADO -->
  <modalTemplate modalId="modal_password_user" title="Datos de empleado" :onConfirm="button_confirm" size="lg">
    <form role="form" id="data_form_x" enctype="multipart/form-data" novalidate>
      <div class="row">
        <li class="list-group-item border-0 p-3 mb-2 bg-gray-100 border-radius-lg">
          <div class="d-flex flex-column w-100">
            <h6 class="text-sm mb-2" style="font-weight:600; letter-spacing:.2px;">
              {{ m_nombre }}
            </h6>

            <div class="row g-2">
              <div class="col-12 col-md-6">
                <div class="d-flex align-items-start mb-1" style="line-height:1.28;">
                  <span class="text-xs text-secondary" style="min-width:120px;">CURP:</span>
                  <span class="text-xs text-dark font-weight-bold flex-grow-1" style="min-width:0; overflow-wrap:anywhere; word-break:break-word;">
                    {{ m_curp }}
                  </span>
                </div>

                <div class="d-flex align-items-start mb-1" style="line-height:1.28;">
                  <span class="text-xs text-secondary" style="min-width:120px;">RFC:</span>
                  <span class="text-xs text-dark font-weight-bold flex-grow-1" style="min-width:0; overflow-wrap:anywhere; word-break:break-word;">
                    {{ m_rfc }}
                  </span>
                </div>

                <div class="d-flex align-items-start mb-1" style="line-height:1.28;">
                  <span class="text-xs text-secondary" style="min-width:120px;">Cod. Puesto:</span>
                  <span class="text-xs text-dark font-weight-bold flex-grow-1" style="min-width:0; overflow-wrap:anywhere; word-break:break-word;">
                    {{ m_codigo_puesto }}
                  </span>
                </div>

                <div class="d-flex align-items-start mb-1" style="line-height:1.28;">
                  <span class="text-xs text-secondary" style="min-width:120px;">Puesto:</span>
                  <span class="text-xs text-dark font-weight-bold flex-grow-1" style="min-width:0; overflow-wrap:anywhere; word-break:break-word;">
                    {{ m_puesto }}
                  </span>
                </div>

                <div class="d-flex align-items-start mb-1" style="line-height:1.28;">
                  <span class="text-xs text-secondary" style="min-width:120px;">Acción:</span>
                  <span class="text-xs text-dark font-weight-bold flex-grow-1" style="min-width:0; overflow-wrap:anywhere; word-break:break-word;">
                    {{ m_accion }}
                  </span>
                </div>

                <div class="d-flex align-items-start mb-0" style="line-height:1.28;">
                  <span class="text-xs text-secondary" style="min-width:120px;">Horas acum.:</span>
                  <span class="text-xs text-dark font-weight-bold flex-grow-1" style="min-width:0; overflow-wrap:anywhere; word-break:break-word;">
                    {{ m_total_horas }}
                  </span>
                </div>

                <div class="d-flex align-items-start mb-0 mt-1" style="line-height:1.28;">
                  <span class="text-xs text-secondary" style="min-width:120px;">Calificación:</span>
                  <span class="text-xs text-dark font-weight-bold flex-grow-1" style="min-width:0; overflow-wrap:anywhere; word-break:break-word;">
                    {{ m_calificacion || '—' }}
                  </span>
                </div>
              </div>

              <div class="col-12 col-md-6">
                <div class="d-flex align-items-start mb-1" style="line-height:1.28;">
                  <span class="text-xs text-secondary" style="min-width:120px;">Contratación:</span>
                  <span class="text-xs text-dark font-weight-bold flex-grow-1" style="min-width:0; overflow-wrap:anywhere; word-break:break-word;">
                    {{ m_contratacion }}
                  </span>
                </div>

                <div class="d-flex align-items-start mb-1" style="line-height:1.28;">
                  <span class="text-xs text-secondary" style="min-width:120px;">Nivel:</span>
                  <span class="text-xs text-dark font-weight-bold flex-grow-1" style="min-width:0; overflow-wrap:anywhere; word-break:break-word;">
                    {{ m_nivel_salarial }}
                  </span>
                </div>

                <div class="d-flex align-items-start mb-1" style="line-height:1.28;">
                  <span class="text-xs text-secondary" style="min-width:120px;">CLUES:</span>
                  <span class="text-xs text-dark font-weight-bold flex-grow-1" style="min-width:0; overflow-wrap:anywhere; word-break:break-word;">
                    {{ m_clave_clues }}
                  </span>
                </div>

                <div class="d-flex align-items-start mb-1" style="line-height:1.28;">
                  <span class="text-xs text-secondary" style="min-width:120px;">Entidad:</span>
                  <span class="text-xs text-dark font-weight-bold flex-grow-1" style="min-width:0; overflow-wrap:anywhere; word-break:break-word;">
                    {{ m_entidad }}
                  </span>
                </div>

                <div class="d-flex align-items-start mb-1" style="line-height:1.28;">
                  <span class="text-xs text-secondary" style="min-width:120px;">Unidad:</span>
                  <span class="text-xs text-dark font-weight-bold flex-grow-1" style="min-width:0; overflow-wrap:anywhere; word-break:break-word;">
                    {{ m_unidad || '—' }}
                  </span>
                </div>

                <div class="d-flex align-items-start mb-0" style="line-height:1.28;">
                  <span class="text-xs text-secondary" style="min-width:120px;">Coordinación:</span>
                  <span class="text-xs text-dark font-weight-bold flex-grow-1" style="min-width:0; overflow-wrap:anywhere; word-break:break-word;">
                    {{ m_coordinacion || '—' }}
                  </span>
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-end mt-2">
              <button
                type="button"
                class="btn btn-sm mb-0"
                @click="openAsignacionUnidad"
                style="
                  background: #10312B;
                  color: #fff;
                  border-radius: 12px;
                  padding: 7px 12px;
                  display: inline-flex;
                  align-items: center;
                  gap: 8px;
                  box-shadow: 0 8px 16px rgba(16, 49, 43, 0.18);
                "
              >
                <i class="fa fa-sitemap"></i>
                Asignación de unidad
              </button>
            </div>
          </div>
        </li>

        <div class="row">
          <inputField
            label="Horas obligatorias"
            id="m_duracion_hrs"
            v-model="m_duracion_hrs"
            :disabled="true"
            :required="true"
          />

          <inputField
            label="Horas realizadas"
            id="m_horas_real"
            v-model="m_horas_real"
            :required="true"
          />
        </div>

        <div class="row" style="margin-top: -20px !important;">
          <inputField
            :grid="gridx3"
            type="date"
            label="Fecha Inicio"
            id="m_fecha_ini"
            v-model="m_fecha_ini"
            :required="true"
          />

          <inputField
            :grid="gridx3"
            type="date"
            label="Fecha Fin"
            id="m_fecha_fin"
            v-model="m_fecha_fin"
            :required="true"
          />
        </div>

        <div class="row" style="margin-top: -70px !important;">
          <inputSelect
            v-model="listSelectStatus"
            :options="listOptionStatus"
            id="id_cat_estatus"
            label="Estatus"
            :multiple="false"
            grid="col-md-6 col-sm-12"
            :required="true"
          />

          <inputSelect
            v-model="listSelectInstance"
            :options="listOptionInstance"
            id="id_instancia"
            :multiple="false"
            label="Instancia"
            grid="col-md-6 col-sm-12"
            :required="true"
          />
        </div>

        <div class="row g-2 mt-2">
          <inputSelect
            v-model="listSelectFinalidad"
            :options="listOptionFinalidad"
            id="id_finalidad"
            label="Finalidad"
            :multiple="false"
            grid="col-md-6 col-sm-12"
            :required="true"
          />

          <inputField
            type="text"
            label="Calificación (70 a 100)"
            id="calificacion"
            v-model="m_calificacion"
            grid="col-md-6 col-sm-12"
            :disabled="!canEditCalificacion"
            :required="true"
          />

          <div class="col-12 mt-1">
            <small class="text-muted">
              Captura un valor entre 70 y 100. Se permiten hasta 2 decimales. Ejemplo: 89.5
            </small>
          </div>
        </div>

        <div class="row">
          <inputSelect
            v-model="listSelectTematica"
            :options="listOptionTematica"
            id="id_cat_tematica"
            label="Temática"
            :multiple="false"
            grid="col-12"
            :required="true"
          />
        </div>

        <div class="row">
          <inputField
            grid="col-12"
            label="Observaciones"
            id="m_observaciones"
            v-model="m_observaciones"
            :uppercase="true"
            :required="true"
          />

          <div v-if="observacionesError" class="col-12 mt-1">
            <small class="text-danger fw-bold">
              {{ observacionesError }}
            </small>
          </div>
        </div>

        <div class="row">
          <inputCheckbox
            v-model="m_eval_aprendizaje"
            :label="'¿Realizó la evaluación de aprendizaje?'"
            :id="'m_eval_aprendizaje'"
          />
        </div>
      </div>
    </form>
  </modalTemplate>

  <!-- MODAL: AGREGAR CURSO AL EMPLEADO -->
  <modalTemplate modalId="modal_add_course" title="Agregar curso al empleado" :onConfirm="confirmAddCourse" size="xl">
    <form id="form_add_course" novalidate>
      <div class="row">
        <div class="col-12 course-select-container">
          <inputSelect
            v-model="selectedCourse"
            :options="courseOptions"
            id="id_accion_add"
            label="Curso"
            :multiple="false"
            grid="col-12"
            :required="true"
          />
        </div>
      </div>
    </form>
  </modalTemplate>

  <!-- MODAL: ASIGNACIÓN DE UNIDAD -->
  <modalTemplate
    modalId="modal_asignacion_unidad"
    title="Asignación de unidad"
    :onConfirm="confirmAsignacionUnidad"
    size="md"
  >
    <form id="form_asignacion_unidad" novalidate>
      <div class="p-2">
        <div class="d-flex align-items-center gap-2 mb-2">
          <i class="fa fa-sitemap text-secondary"></i>
          <div class="text-sm fw-bold">Selecciona la unidad y su coordinación</div>
        </div>

        <div class="row g-2">
          <div class="col-12 col-md-6">
            <inputSelect
              v-model="selectedUnidad"
              :options="unidadOptions"
              id="id_unidad"
              label="Unidad"
              :multiple="false"
              grid="col-12"
              :required="true"
            />
            <small class="text-muted d-block mt-1">Elige primero la unidad para cargar sus coordinaciones.</small>
          </div>

          <div class="col-12 col-md-6">
            <inputSelect
              v-model="selectedCoordinacion"
              :options="coordinacionOptions"
              id="id_coordinacion"
              label="Coordinación"
              :multiple="false"
              grid="col-12"
              :required="true"
            />
            <small class="text-muted d-block mt-1">Solo se muestran coordinaciones activas relacionadas.</small>
          </div>
        </div>

        <div
          v-if="asignacionActual.unidad || asignacionActual.coordinacion"
          class="alert alert-light border mt-3 mb-0"
          style="border-radius: 12px;"
        >
          <div class="text-xs text-secondary fw-bold mb-1">Asignación actual</div>
          <div class="text-sm">
            <div><span class="fw-bold">Unidad:</span> {{ asignacionActual.unidad || '—' }}</div>
            <div><span class="fw-bold">Coordinación:</span> {{ asignacionActual.coordinacion || '—' }}</div>
          </div>
        </div>
      </div>
    </form>
  </modalTemplate>
</template>

<script setup>
import { ref, onMounted, watch, nextTick } from 'vue'
import { notyf } from '@components/notyf.js'
import { setupTableEvents } from '@helpers/table/table-events.vue'
import { handlePagination } from '@helpers/table/table-pagination.vue'
import inputField from '@helpers/form/input-field.vue'
import modalTemplate from '@helpers/modal/modal-template.vue'
import { showSpinner, hideSpinner } from '@components/spinner.js'
import tableTittle from '@helpers/table/table-tittle.vue'
import tableFooter from '@helpers/table/table-footer.vue'
import tableSpinner from '@helpers/table/table-spinner.vue'
import tableRow from '@helpers/table/table-row.vue'
import tableEmpty from '@helpers/table/table-empty.vue'
import tableButtonDefault from '@helpers/table/table-button-default.vue'
import inputSelect from '@helpers/form/input-select.vue'
import inputCheckbox from '@helpers/form/input-checkbox.vue'
import { clearErrors } from '@components/clearErrors.js'
import { handleErrors } from '@components/handleErrors.js'
import axios from '@axios'

const gridx3 = ref('col-12 col-md-6 mb-6')

const name = ref('')
const curp = ref('')
const is_complete = ref(false)
const listSelectAcction = ref(null)
const listOptionsAcction = ref([])
const f_entidad = ref(null)
const f_tipo_nomina = ref(null)
const f_clues = ref(null)
const isAdminPac = ref(false)
const opcionesEntidades = ref([])
const opcionesTiposNomina = ref([])
const opcionesClues = ref([])
const isLoadingClues = ref(false)
const cluesRequestSeq = ref(0)

const item = ref([])
const rowsAll = ref(0)
const row = ref(0)
const currentPage = ref(1)
const limit = ref(5)
const searchTerm = ref('')
const spinnerRef = ref(null)

// modal principal
const m_nivel_salarial = ref('')
const m_rfc = ref('')
const m_codigo_puesto = ref('')
const m_puesto = ref('')
const m_clave_clues = ref('')
const m_nombre = ref('')
const m_entidad = ref('')
const m_contratacion = ref('')
const m_curp = ref('')
const m_accion = ref('')
const m_fecha_ini = ref('')
const m_fecha_fin = ref('')
const m_observaciones = ref('')
const observacionesError = ref('')
const m_duracion_hrs = ref('')
const m_horas_real = ref('')
const m_eval_aprendizaje = ref(true)
const listSelectStatus = ref(null)
const listOptionStatus = ref([])
const listSelectInstance = ref(null)
const listOptionInstance = ref([])
const listSelectTematica = ref(null)
const listOptionTematica = ref([])
const listSelectFinalidad = ref(null)
const listOptionFinalidad = ref([])
const m_unidad = ref('')
const m_coordinacion = ref('')
const m_calificacion = ref('100')
const canEditCalificacion = ref(false)
const m_total_horas = ref('')

// bloqueos de envío
const isSavingPac = ref(false)
const isSavingCourse = ref(false)
const isSavingAsignacion = ref(false)

// agregar curso
const selectedEmployeeId = ref(null)
const selectedCourse = ref(null)
const courseOptions = ref([])

// asignación unidad
const selectedUnidad = ref(null)
const selectedCoordinacion = ref(null)
const unidadOptions = ref([])
const coordinacionOptions = ref([])
const asignacionActual = ref({
  unidad: '',
  coordinacion: '',
})

function getRowId(rowx) {
  return rowx?.id ?? rowx?.id_empl_accion ?? null
}

function statusPillClass(statusTxt) {
  const status = String(statusTxt || '').toUpperCase().trim()

  if (status === 'CONCLUIDO') {
    return 'cap-status-success'
  }

  return 'cap-status-pending'
}

function getApiErrorMessage(error, fallback = 'No se pudo completar la acción. Por favor, vuelve a intentarlo.') {
  const status = error?.response?.status
  const data = error?.response?.data

  if (typeof data === 'string' && data.trim() !== '') {
    return `HTTP ${status ?? ''}: ${data}`
  }

  if (data?.message) {
    return data.message
  }

  if (data?.error) {
    return data.error
  }

  if (status === 403) {
    return 'No tienes permisos para realizar esta acción.'
  }

  if (status === 404) {
    return 'La ruta solicitada no fue encontrada.'
  }

  if (status === 500) {
    return 'Ocurrió un error interno en el servidor.'
  }

  return fallback
}

function sanitizeCalificacionInput(value) {
  let raw = String(value ?? '').trim()

  raw = raw.replace(/,/g, '.')
  raw = raw.replace(/[^\d.]/g, '')

  const firstDot = raw.indexOf('.')
  if (firstDot !== -1) {
    const intPart = raw.slice(0, firstDot + 1)
    const decPart = raw.slice(firstDot + 1).replace(/\./g, '')
    raw = intPart + decPart
  }

  if (raw.includes('.')) {
    const [entero, decimal = ''] = raw.split('.')
    raw = `${entero}.${decimal.slice(0, 2)}`
  }

  return raw
}

function formatCalificacionForInput(value) {
  if (value === null || value === undefined || value === '') {
    return '100'
  }

  const n = Number(String(value).replace(',', '.'))
  if (Number.isNaN(n)) {
    return '100'
  }

  const safe = Math.min(100, Math.max(70, n))
  return safe.toFixed(2).replace(/\.?0+$/, '')
}

function optionValue(option) {
  if (option && typeof option === 'object') {
    return String(option.value ?? '')
  }

  return String(option ?? '')
}

function optionLabel(option) {
  if (option && typeof option === 'object') {
    return String(option.label ?? option.value ?? '')
  }

  return String(option ?? '')
}

function normalizeOptions(options) {
  return (options || [])
    .map((option) => ({
      value: optionValue(option),
      label: optionLabel(option),
    }))
    .filter((option) => option.value !== '')
}

function normalizeCalificacionForSubmit() {
  const sanitized = sanitizeCalificacionInput(m_calificacion.value)

  if (sanitized === '' || sanitized === '.') {
    throw new Error('Debes capturar una calificación.')
  }

  const n = Number(sanitized)

  if (Number.isNaN(n)) {
    throw new Error('La calificación debe ser numérica.')
  }

  if (n < 70 || n > 100) {
    throw new Error('La calificación debe estar entre 70 y 100.')
  }

  const normalized = Number(n.toFixed(2))
  m_calificacion.value = normalized.toFixed(2).replace(/\.?0+$/, '')
  return normalized
}

watch(m_calificacion, (val) => {
  const sanitized = sanitizeCalificacionInput(val)

  if (sanitized !== String(val ?? '')) {
    m_calificacion.value = sanitized
  }
})

watch(m_observaciones, (val) => {
  if (String(val ?? '').trim() !== '') {
    observacionesError.value = ''
  }
})

watch(f_entidad, async (entidad, previousEntidad) => {
  const entidadValue = optionValue(entidad)

  if (entidadValue === optionValue(previousEntidad)) {
    return
  }

  f_clues.value = null
  await fetchCluesOptions(entidadValue)
})

function blurActiveElement() {
  try {
    const el = document.activeElement
    if (el && typeof el.blur === 'function') el.blur()
  } catch (e) {}
}

function resetEmployeeModalData() {
  m_nivel_salarial.value = ''
  m_rfc.value = ''
  m_codigo_puesto.value = ''
  m_puesto.value = ''
  m_clave_clues.value = ''
  m_nombre.value = ''
  m_entidad.value = ''
  m_contratacion.value = ''
  m_curp.value = ''
  m_accion.value = ''
  m_fecha_ini.value = ''
  m_fecha_fin.value = ''
  m_observaciones.value = ''
  observacionesError.value = ''
  m_duracion_hrs.value = ''
  m_horas_real.value = ''
  m_eval_aprendizaje.value = true
  listSelectStatus.value = null
  listOptionStatus.value = []
  listSelectInstance.value = null
  listOptionInstance.value = []
  listSelectTematica.value = null
  listOptionTematica.value = []
  listSelectFinalidad.value = null
  listOptionFinalidad.value = []
  m_unidad.value = ''
  m_coordinacion.value = ''
  m_calificacion.value = '100'
  m_total_horas.value = ''
}

watch(selectedUnidad, async (u) => {
  selectedCoordinacion.value = null
  coordinacionOptions.value = []

  if (!u?.id) return

  try {
    const { data } = await axios.post('/pac/coordinaciones', { id_unidad: u.id })
    coordinacionOptions.value = data.listCoordinaciones ?? []
  } catch (error) {
    console.error('Error en /pac/coordinaciones:', error?.response?.data ?? error)
    notyf.error(getApiErrorMessage(error, 'No se pudieron cargar las coordinaciones.'))
  }
})

function resetAdminFilterState() {
  f_entidad.value = null
  f_tipo_nomina.value = null
  f_clues.value = null
  opcionesEntidades.value = []
  opcionesTiposNomina.value = []
  opcionesClues.value = []
}

async function fetchAdminFilterOptions() {
  if (!isAdminPac.value) {
    resetAdminFilterState()
    return
  }

  try {
    const entidad = optionValue(f_entidad.value)
    const payload = entidad ? { entidad } : {}
    const { data } = await axios.post('/pac/filter-options', payload)

    if (!data?.status || !data?.is_admin) {
      resetAdminFilterState()
      isAdminPac.value = false
      return
    }

    opcionesEntidades.value = normalizeOptions(data.entidades)
    opcionesTiposNomina.value = normalizeOptions(data.tipos_nomina)
    opcionesClues.value = normalizeOptions(data.clues)
  } catch (error) {
    resetAdminFilterState()

    if (error?.response?.status === 403) {
      isAdminPac.value = false
      return
    }

    notyf.error(getApiErrorMessage(error, 'No se pudieron cargar los filtros administrativos.'))
  }
}

async function fetchCluesOptions(entidad = '') {
  if (!isAdminPac.value) {
    return
  }

  const requestId = cluesRequestSeq.value + 1
  cluesRequestSeq.value = requestId
  isLoadingClues.value = true

  try {
    const { data } = await axios.post('/pac/filter-options', { entidad })

    if (requestId !== cluesRequestSeq.value) {
      return
    }

    if (!data?.status || !data?.is_admin) {
      opcionesClues.value = []
      return
    }

    opcionesClues.value = normalizeOptions(data.clues)
  } catch (error) {
    if (requestId === cluesRequestSeq.value) {
      opcionesClues.value = []
    }
  } finally {
    if (requestId === cluesRequestSeq.value) {
      isLoadingClues.value = false
    }
  }
}

const fetchTableData = async () => {
  const MIN_SPINNER_DURATION = 1000
  const startTime = Date.now()

  spinnerRef.value?.show()
  const offset = (currentPage.value - 1) * limit.value

  try {
    const payload = {
      limit: limit.value,
      offset,
      search: searchTerm.value,
      select: parseInt(document.getElementById('footer-filter')?.value || 5, 10),
      name: name.value,
      curp: curp.value,
      is_complete: is_complete.value ? '1' : '0',
      id_accion: listSelectAcction.value?.id ?? '',
    }

    if (isAdminPac.value) {
      payload.entidad = optionValue(f_entidad.value)
      payload.tipo_nomina = optionValue(f_tipo_nomina.value)
      payload.clues = optionValue(f_clues.value)
    }

    const { data } = await axios.post('/pac/table', payload)

    if (typeof data.is_admin !== 'undefined') {
      isAdminPac.value = !!data.is_admin
    }

    if (!data.status) {
      notyf.error(data.message ?? 'No se pudo cargar la tabla PAC.')
      item.value = []
      rowsAll.value = 0
      row.value = 0
      return
    }

    item.value = (data.list ?? []).map((rowx) => ({
      ...rowx,
      id: rowx.id ?? rowx.id_empl_accion ?? null,
    }))

    rowsAll.value = data.allRow ?? 0
    row.value = data.row ?? 0
  } catch (error) {
    console.error('Error en /pac/table:', error?.response?.data ?? error)
    item.value = []
    rowsAll.value = 0
    row.value = 0
  } finally {
    const elapsed = Date.now() - startTime
    const delay = elapsed < MIN_SPINNER_DURATION ? MIN_SPINNER_DURATION - elapsed : 0
    setTimeout(() => spinnerRef.value?.hide(), delay)
  }
}

onMounted(async () => {
  await main()

  if (isAdminPac.value) {
    await fetchAdminFilterOptions()
  }

  await fetchTableData()

  setupTableEvents({
    fetchTableData,
    searchTerm,
    currentPage,
    limit,
    handlePagination,
  })
})

function clear_search() {
  currentPage.value = 1
  name.value = ''
  curp.value = ''
  is_complete.value = false
  listSelectAcction.value = null
  f_entidad.value = null
  f_tipo_nomina.value = null
  f_clues.value = null
  fetchTableData()
}

function search_function() {
  currentPage.value = 1
  fetchTableData()
}

async function main() {
  try {
    const request = await axios.post('/pac/main')

    if (!request.data?.status) {
      notyf.error(request.data?.message ?? 'No se pudieron cargar los catálogos.')
      return
    }

    listOptionsAcction.value = request.data.listOptionsAcction ?? []
    listSelectAcction.value = (request.data.listSelectAcction ?? [])[0] ?? null
    isAdminPac.value = !!request.data.is_admin

    if (!isAdminPac.value) {
      resetAdminFilterState()
    }
  } catch (error) {
    console.error('Error en /pac/main:', error?.response?.data ?? error)
    notyf.error(getApiErrorMessage(error, 'No se pudo completar la acción. Por favor, vuelve a intentarlo.'))
  }
}

function isBlank(value) {
  return String(value ?? '').trim() === ''
}

function focusFieldById(id) {
  nextTick(() => {
    const input =
      document.getElementById(id) ||
      document.querySelector(`[name="${id}"]`)

    if (input && typeof input.scrollIntoView === 'function') {
      input.scrollIntoView({
        behavior: 'smooth',
        block: 'center',
      })
    }

    if (input && typeof input.focus === 'function') {
      input.focus()
    }
  })
}

function validateEmployeeModalRequiredFields() {
  const requiredFields = [
    {
      label: 'Horas obligatorias',
      id: 'm_duracion_hrs',
      valid: !isBlank(m_duracion_hrs.value),
    },
    {
      label: 'Horas realizadas',
      id: 'm_horas_real',
      valid: !isBlank(m_horas_real.value),
    },
    {
      label: 'Fecha Inicio',
      id: 'm_fecha_ini',
      valid: !isBlank(m_fecha_ini.value),
    },
    {
      label: 'Fecha Fin',
      id: 'm_fecha_fin',
      valid: !isBlank(m_fecha_fin.value),
    },
    {
      label: 'Estatus',
      id: 'id_cat_estatus',
      valid: !!listSelectStatus.value?.id,
    },
    {
      label: 'Instancia',
      id: 'id_instancia',
      valid: !!listSelectInstance.value?.id,
    },
    {
      label: 'Finalidad',
      id: 'id_finalidad',
      valid: !!listSelectFinalidad.value?.id,
    },
    {
      label: 'Calificación',
      id: 'calificacion',
      valid: !isBlank(m_calificacion.value),
    },
    {
      label: 'Temática',
      id: 'id_cat_tematica',
      valid: !!listSelectTematica.value?.id,
    },
    {
      label: 'Observaciones',
      id: 'm_observaciones',
      valid: !isBlank(m_observaciones.value),
    },
    {
      label: 'Evaluación de aprendizaje',
      id: 'm_eval_aprendizaje',
      valid: m_eval_aprendizaje.value === true || m_eval_aprendizaje.value === false,
    },
  ]

  const missing = requiredFields.find((field) => !field.valid)

  if (missing) {
    if (missing.id === 'm_observaciones') {
      observacionesError.value = 'El campo Observaciones es obligatorio.'
    } else {
      observacionesError.value = ''
    }

    notyf.error(`El campo "${missing.label}" es obligatorio.`)
    focusFieldById(missing.id)

    return false
  }

  observacionesError.value = ''
  m_observaciones.value = String(m_observaciones.value ?? '').trim()

  return true
}

async function button_confirm() {
  if (isSavingPac.value) return

  try {
    isSavingPac.value = true
    clearErrors()
    observacionesError.value = ''

    const key = parseInt(window._selectkybyemployee ?? 0, 10)

    if (!key) {
      notyf.error('No se encontró el identificador del registro.')
      return
    }

    if (!validateEmployeeModalRequiredFields()) {
      return
    }

    let calificacionNormalizada = null

    try {
      calificacionNormalizada = normalizeCalificacionForSubmit()
    } catch (e) {
      notyf.error(e.message || 'La calificación no es válida.')
      return
    }

    await nextTick()

    const form = document.querySelector('#data_form_x')
    const formData = new FormData(form)

    formData.set('id_cat_estatus', String(listSelectStatus.value?.id ?? ''))
    formData.set('id_instancia', String(listSelectInstance.value?.id ?? ''))
    formData.set('id_cat_tematica', String(listSelectTematica.value?.id ?? ''))
    formData.set('id_finalidad', String(listSelectFinalidad.value?.id ?? ''))
    formData.set('m_eval_aprendizaje', m_eval_aprendizaje.value ? '1' : '0')
    formData.set('calificacion', String(calificacionNormalizada))
    formData.set('m_observaciones', String(m_observaciones.value ?? '').trim())
    formData.set('id', String(key))

    showSpinner()

    const response = await axios.post('/pac/save', formData)

    if (!response.data.status) {
      clearErrors()
      notyf.error(response.data.message ?? 'No se pudo guardar el registro.')
      return
    }

    blurActiveElement()
    $('#modal_password_user').modal('hide')
    notyf.success(response.data.message ?? 'Registro actualizado correctamente.')
    fetchTableData()
  } catch (error) {
    clearErrors()
    console.error('Error en /pac/save:', error?.response?.data ?? error)

    if (error.response?.data?.errors) {
      handleErrors(error.response.data.errors)
      return
    }

    notyf.error(getApiErrorMessage(error, 'No se pudo guardar la información.'))
  } finally {
    hideSpinner()
    isSavingPac.value = false
  }
}

async function setOption(id) {
  clearErrors()
  resetEmployeeModalData()

  const safeId = parseInt(id ?? 0, 10)

  if (!safeId) {
    notyf.error('No se encontró el identificador del registro.')
    return
  }

  window._selectkybyemployee = safeId
  canEditCalificacion.value = true

  showSpinner()

  try {
    const request = await axios.post('/pac/data', { id: safeId })

    if (!request.data?.status) {
      notyf.error(request.data?.message ?? 'No se pudo cargar el empleado.')
      return
    }

    const data = request.data.data ?? {}
    const selectx = request.data ?? {}

    m_nivel_salarial.value = data.nivel_salarial ?? ''
    m_rfc.value = data.rfc ?? ''
    m_codigo_puesto.value = data.codigo_puesto ?? ''
    m_clave_clues.value = data.clave_clues ?? ''
    m_puesto.value = data.puesto ?? ''
    m_entidad.value = data.entidad ?? ''
    m_contratacion.value = data.contratacion ?? ''
    m_curp.value = data.curp ?? ''
    m_nombre.value = data.nombre ?? ''
    m_accion.value = data.accion ?? ''
    m_fecha_ini.value = data.fecha_ini ?? ''
    m_fecha_fin.value = data.fecha_fin ?? ''
    m_observaciones.value = data.observaciones ?? ''
    observacionesError.value = ''
    m_duracion_hrs.value = data.duracion_hrs?.toString() || ''
    m_horas_real.value = data.horas_real?.toString() || ''
    m_eval_aprendizaje.value =
      String(data.eval_aprendizaje ?? '0') === '1' || data.eval_aprendizaje === true
    m_total_horas.value = String(selectx.totalHorasReal ?? 0)

    listOptionStatus.value = selectx.listOptionStatus ?? []
    listSelectStatus.value = (selectx.listSelectStatus ?? [])[0] ?? null

    listOptionInstance.value = selectx.listOptionInstance ?? []
    listSelectInstance.value = (selectx.listSelectInstance ?? [])[0] ?? null

    listOptionTematica.value = selectx.listOptionTematica ?? []
    listSelectTematica.value = (selectx.listSelectTematica ?? [])[0] ?? null

    listOptionFinalidad.value = selectx.listOptionFinalidad ?? []
    listSelectFinalidad.value = (selectx.listSelectFinalidad ?? [])[0] ?? null

    m_calificacion.value = formatCalificacionForInput(data.calificacion ?? 100)

    try {
      const asg = await axios.post('/pac/asignacion-unidad/data', { id: safeId })
      m_unidad.value = asg.data?.unidad_txt ?? ''
      m_coordinacion.value = asg.data?.coordinacion_txt ?? ''
    } catch (error) {
      console.error('Error en /pac/asignacion-unidad/data:', error?.response?.data ?? error)
      m_unidad.value = ''
      m_coordinacion.value = ''
    }

    blurActiveElement()
    $('#modal_password_user').modal('show')
  } catch (error) {
    console.error('Error en /pac/data:', error?.response?.data ?? error)
    notyf.error(getApiErrorMessage(error, 'No se pudo completar la acción. Por favor, vuelve a intentarlo.'))
  } finally {
    hideSpinner()
  }
}

async function openAddCourse(id) {
  const safeId = parseInt(id ?? 0, 10)

  if (!safeId) {
    notyf.error('No se encontró el identificador del registro.')
    return
  }

  selectedEmployeeId.value = safeId
  selectedCourse.value = null
  canEditCalificacion.value = false

  try {
    const { data } = await axios.post('/pac/courses')

    if (!data?.status) {
      notyf.error(data?.message ?? 'No se pudieron cargar los cursos.')
      return
    }

    courseOptions.value = (data.listCourses ?? []).filter((course) => {
      const estatus = String(
        course.estatus ??
        course.status ??
        course.descripcion_estatus ??
        ''
      ).toUpperCase().trim()

      return estatus === 'VIGENTE' || estatus === 'ALTA'
    })

    blurActiveElement()
    $('#modal_add_course').modal('show')
  } catch (error) {
    console.error('Error en /pac/courses:', error?.response?.data ?? error)
    notyf.error(getApiErrorMessage(error, 'No se pudieron cargar los cursos.'))
  }
}

async function confirmAddCourse() {
  if (isSavingCourse.value) return

  if (!selectedEmployeeId.value || !selectedCourse.value?.id) {
    notyf.error('Selecciona un curso.')
    return
  }

  try {
    isSavingCourse.value = true

    const { data } = await axios.post('/pac/employee/add-course', {
      id_empl_accion_base: selectedEmployeeId.value,
      id_accion: selectedCourse.value.id,
    })

    if (!data.status) {
      notyf.error(data.message ?? 'No se pudo agregar el curso.')
      return
    }

    blurActiveElement()
    $('#modal_add_course').modal('hide')
    notyf.success(data.message ?? 'Curso agregado correctamente.')
    fetchTableData()
  } catch (error) {
    console.error('Error en /pac/employee/add-course:', error?.response?.data ?? error)
    notyf.error(getApiErrorMessage(error, 'Error al agregar el curso.'))
  } finally {
    isSavingCourse.value = false
  }
}

async function openAsignacionUnidad() {
  const id = parseInt(window._selectkybyemployee ?? 0, 10)

  if (!id) {
    notyf.error('No se detectó el ID del registro.')
    return
  }

  selectedEmployeeId.value = id
  selectedUnidad.value = null
  selectedCoordinacion.value = null
  unidadOptions.value = []
  coordinacionOptions.value = []
  asignacionActual.value = { unidad: '', coordinacion: '' }

  try {
    const { data } = await axios.post('/pac/unidades')

    if (!data?.status) {
      notyf.error(data?.message ?? 'No se pudieron cargar las unidades.')
      return
    }

    unidadOptions.value = data.listUnidades ?? []
  } catch (error) {
    console.error('Error en /pac/unidades:', error?.response?.data ?? error)
    notyf.error(getApiErrorMessage(error, 'No se pudieron cargar las unidades.'))
    return
  }

  try {
    const resp = await axios.post('/pac/asignacion-unidad/data', { id })
    const d = resp.data ?? {}

    asignacionActual.value = {
      unidad: d.unidad_txt ?? '',
      coordinacion: d.coordinacion_txt ?? '',
    }

    const idUnidad = d.id_unidad ?? null
    const idCoord = d.id_coordinacion ?? null

    if (idUnidad) {
      selectedUnidad.value = unidadOptions.value.find((u) => String(u.id) === String(idUnidad)) ?? null
    }

    if (selectedUnidad.value?.id) {
      const cx = await axios.post('/pac/coordinaciones', { id_unidad: selectedUnidad.value.id })
      coordinacionOptions.value = cx.data.listCoordinaciones ?? []

      if (idCoord) {
        selectedCoordinacion.value =
          coordinacionOptions.value.find((c) => String(c.id) === String(idCoord)) ?? null
      }
    }
  } catch (error) {
    console.error('Error en precarga de asignación:', error?.response?.data ?? error)
  }

  $('#modal_asignacion_unidad')
    .off('hidden.bs.modal.unidad')
    .on('hidden.bs.modal.unidad', async function () {
      $('#modal_password_user').modal('show')
      if (selectedEmployeeId.value) {
        await setOption(selectedEmployeeId.value)
      }
    })

  blurActiveElement()
  $('#modal_password_user').modal('hide')

  setTimeout(() => {
    $('#modal_asignacion_unidad').modal('show')
  }, 350)
}

async function confirmAsignacionUnidad() {
  if (isSavingAsignacion.value) return

  const id = selectedEmployeeId.value

  if (!id) {
    notyf.error('No se detectó el registro a actualizar.')
    return
  }
  if (!selectedUnidad.value?.id) {
    notyf.error('Selecciona una unidad.')
    return
  }
  if (!selectedCoordinacion.value?.id) {
    notyf.error('Selecciona una coordinación.')
    return
  }

  try {
    isSavingAsignacion.value = true

    const { data } = await axios.post('/pac/asignacion-unidad/save', {
      id,
      id_unidad: selectedUnidad.value.id,
      id_coordinacion: selectedCoordinacion.value.id,
    })

    if (!data.status) {
      notyf.error(data.message ?? 'No se pudo guardar la asignación.')
      return
    }

    notyf.success(data.message ?? 'Unidad y coordinación asignadas correctamente.')
    fetchTableData()

    m_unidad.value = data.unidad ?? m_unidad.value
    m_coordinacion.value = data.coordinacion ?? m_coordinacion.value

    blurActiveElement()
    $('#modal_asignacion_unidad').modal('hide')
  } catch (error) {
    console.error('Error en /pac/asignacion-unidad/save:', error?.response?.data ?? error)
    notyf.error(getApiErrorMessage(error, 'Error al guardar la asignación.'))
  } finally {
    isSavingAsignacion.value = false
  }
}
</script>

<style scoped>
:deep(#modal_add_course .modal-dialog) {
  max-width: 1100px !important;
  width: calc(100% - 2rem) !important;
}

:deep(#modal_add_course .modal-body) {
  overflow: visible !important;
}

:deep(#modal_add_course .course-select-container) {
  width: 100%;
}

:deep(#modal_add_course .form-select),
:deep(#modal_add_course select) {
  width: 100% !important;
  white-space: normal !important;
  height: auto !important;
  min-height: 38px !important;
}

:deep(#modal_add_course .form-select option),
:deep(#modal_add_course select option) {
  white-space: normal !important;
}
</style>
