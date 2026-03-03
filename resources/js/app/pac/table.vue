<template>
  <div>
    <!-- Component to display the table title -->
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

            <div class="d-flex justify-content-end flex-wrap gap-1 mt-2">
              <tableButtonDefault
                color="white"
                icon="fas fa-brush"
                label="Limpiar"
                @click="clear_search"
                color_icon="#777777"
                :clickEventPayload="null"
              />
              <button type="button" class="btn btn-sm btn-secondary" @click="search_function">
                <span class="d-none d-sm-inline">Buscar</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <br />

    <!-- Spinner component shown while data is loading -->
    <tableSpinner ref="spinnerRef" />

    <div class="table-responsive">
      <!-- Main responsive table -->
      <table class="table align-items-center mb-0" id="table-default">
        <thead>
          <tr>
            <tableRow value="Acciones" />
            <tableRow value="¿Es atentido?" />
            <tableRow value="Nombre" />
            <tableRow value="CURP" />
            <tableRow value="Acción" />
          </tr>
        </thead>
        <tbody>
          <tableEmpty v-if="item.length === 0" :colspan="5" />

          <!-- ✅ FIX: key por id (id_empl_accion) -->
          <tr v-for="row in item" :key="row.id">
            <td class="text-center">
              <div class="button-container">
                <!-- Botón Atender -->
                <tableButtonDefault
                  color="#081F5E"
                  icon="fa fa-external-link"
                  label="Atender"
                  @click="setOption"
                  :clickEventPayload="row.id"
                  modalToggle="modal"
                  modalTarget="#modal_password_user"
                />

                <!-- Botón: Agregar curso -->
                <tableButtonDefault
                  color="#0E5E08"
                  icon="fa fa-plus"
                  label="Agregar curso"
                  @click="openAddCourse"
                  :clickEventPayload="row.id"
                  modalToggle="modal"
                  modalTarget="#modal_add_course"
                />
              </div>
            </td>

            <td class="align-middle text-center">
              <span class="text-secondary text-xs font-weight-bold">{{ row.atendido }}</span>
            </td>
            <td class="align-middle text-center">
              <p class="text-xs font-weight-bold mb-0">{{ row.nombre }}</p>
              <p class="text-xs text-secondary mb-0">{{ row.apellido }}</p>
            </td>
            <td class="align-middle text-center">
              <span class="text-secondary text-xs font-weight-bold">{{ row.curp }}</span>
            </td>
            <td class="align-middle text-center">
              <span class="text-secondary text-xs font-weight-bold">{{ row.accion }}</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <tableFooter :row="row" :rowsAll="rowsAll" />
  </div>

  <!-- Modal DATOS EMPLEADO -->
  <modalTemplate modalId="modal_password_user" title="Datos de empleado" :onConfirm="button_confirm" size="lg">
    <form role="form" id="data_form_x" enctype="multipart/form-data">
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
          <inputField label="Horas obligatorias" id="m_duracion_hrs" v-model="m_duracion_hrs" :disabled="true" />
          <inputField label="Horas realizadas" id="m_horas_real" v-model="m_horas_real" />
        </div>

        <div class="row" style="margin-top: -20px !important;">
          <inputField :grid="gridx3" type="date" label="Fecha Inicio" id="m_fecha_ini" v-model="m_fecha_ini" />
          <inputField :grid="gridx3" type="date" label="Fecha Fin" id="m_fecha_fin" v-model="m_fecha_fin" />
        </div>

        <div class="row" style="margin-top: -70px !important;">
          <inputSelect v-model="listSelectStatus" :options="listOptionStatus" id="id_cat_estatus" label="Estatus" :multiple="false" grid="col-md-6 col-sm-12" />
          <inputSelect v-model="listSelectInstance" :options="listOptionInstance" id="id_instancia" :multiple="false" label="Instancia" grid="col-md-6 col-sm-12" />
        </div>

        <div class="row g-2 mt-2">
          <inputSelect v-model="listSelectFinalidad" :options="listOptionFinalidad" id="id_finalidad" label="Finalidad" :multiple="false" grid="col-md-6 col-sm-12" />
          <inputField
            type="number"
            label="Calificación (70–100)"
            id="calificacion"
            v-model="m_calificacion"
            grid="col-md-6 col-sm-12"
            :disabled="!canEditCalificacion"
            :min="70"
            :max="100"
            :step="1"
          />
        </div>

        <div class="row">
          <inputSelect v-model="listSelectTematica" :options="listOptionTematica" id="id_cat_tematica" label="Temática" :multiple="false" grid="col-12" />
        </div>

        <div class="row">
          <inputField grid="col-12" label="Observaciones" id="m_observaciones" v-model="m_observaciones" :uppercase="true" />
        </div>

        <div class="row">
          <inputCheckbox v-model="m_eval_aprendizaje" :label="'¿Realizó la evaluación de aprendizaje?'" :id="'m_eval_aprendizaje'" />
        </div>
      </div>
    </form>
  </modalTemplate>

  <!-- MODAL: AGREGAR CURSO AL EMPLEADO -->
  <modalTemplate modalId="modal_add_course" title="Agregar curso al empleado" :onConfirm="confirmAddCourse" size="md">
    <form id="form_add_course">
      <div class="row">
        <inputSelect v-model="selectedCourse" :options="courseOptions" id="id_accion_add" label="Curso" :multiple="false" grid="col-12" :required="true" />
      </div>
    </form>
  </modalTemplate>

  <!-- MODAL: ASIGNACIÓN DE UNIDAD -->
  <modalTemplate modalId="modal_asignacion_unidad" title="Asignación de unidad" :onConfirm="confirmAsignacionUnidad" size="md">
    <form id="form_asignacion_unidad">
      <div class="p-2">
        <div class="d-flex align-items-center gap-2 mb-2">
          <i class="fa fa-sitemap text-secondary"></i>
          <div class="text-sm fw-bold">Selecciona la unidad y su coordinación</div>
        </div>

        <div class="row g-2">
          <div class="col-12 col-md-6">
            <inputSelect v-model="selectedUnidad" :options="unidadOptions" id="id_unidad" label="Unidad" :multiple="false" grid="col-12" :required="true" />
            <small class="text-muted d-block mt-1">Elige primero la unidad para cargar sus coordinaciones.</small>
          </div>

          <div class="col-12 col-md-6">
            <inputSelect v-model="selectedCoordinacion" :options="coordinacionOptions" id="id_coordinacion" label="Coordinación" :multiple="false" grid="col-12" :required="true" />
            <small class="text-muted d-block mt-1">Solo se muestran coordinaciones activas relacionadas.</small>
          </div>
        </div>

        <div v-if="asignacionActual.unidad || asignacionActual.coordinacion" class="alert alert-light border mt-3 mb-0" style="border-radius: 12px;">
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
import { ref, onMounted, watch } from 'vue'
import { notyf } from '@components/notyf.js'
import { BASE_URL } from '@/components/url.js'

import { setupTableEvents } from '@helpers/table/table-events.vue'
import { handlePagination } from '@helpers/table/table-pagination.vue'
import tableButtonEdit from '@helpers/table/table-button-edit.vue'
import inputField from '@helpers/form/input-field.vue'
import modalTemplate from '@helpers/modal/modal-template.vue'
import { showSpinner, hideSpinner } from '@components/spinner.js'

import tableTittle from '@helpers/table/table-tittle.vue'
import tableFooter from '@helpers/table/table-footer.vue'
import tableSpinner from '@helpers/table/table-spinner.vue'
import tableRow from '@helpers/table/table-row.vue'
import tableItem from '@helpers/table/table-item.vue'
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
const listSelectAcction = ref([])
const listOptionsAcction = ref([])

const item = ref([])
const rowsAll = ref(0)
const row = ref(0)
const currentPage = ref(1)
const limit = ref(5)
const searchTerm = ref('')
const spinnerRef = ref(null)

// data modal
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
const m_duracion_hrs = ref('')
const m_horas_real = ref('')
const m_eval_aprendizaje = ref(true)
const listSelectStatus = ref([])
const listOptionStatus = ref([])
const listSelectInstance = ref([])
const listOptionInstance = ref([])
const listSelectTematica = ref([])
const listOptionTematica = ref([])

// mostrar en modal principal
const m_unidad = ref('')
const m_coordinacion = ref('')

// finalidad
const listSelectFinalidad = ref([])
const listOptionFinalidad = ref([])

// calificación manual (70..100)
const m_calificacion = ref('100')
const canEditCalificacion = ref(false)

// total horas acumuladas
const m_total_horas = ref('')

// estado para "Agregar curso"
const selectedEmployeeId = ref(null)
const selectedCourse = ref(null)
const courseOptions = ref([])

// estado para "Asignación de unidad"
const selectedUnidad = ref(null)
const selectedCoordinacion = ref(null)
const unidadOptions = ref([])
const coordinacionOptions = ref([])

// asignación actual (precarga)
const asignacionActual = ref({
  unidad: '',
  coordinacion: '',
})

function clampCalificacion(val) {
  let n = parseInt(val ?? '100', 10)
  if (Number.isNaN(n)) n = 100
  if (n < 70) n = 70
  if (n > 100) n = 100
  return String(n)
}

watch(m_calificacion, (val) => {
  const fixed = clampCalificacion(val)
  if (fixed !== String(val)) m_calificacion.value = fixed
})

// helper para warning aria-hidden (no afecta el fetch)
function blurActiveElement() {
  try {
    const el = document.activeElement
    if (el && typeof el.blur === 'function') el.blur()
  } catch (e) {}
}

// cuando eligen unidad, cargar coordinaciones relacionadas
watch(selectedUnidad, async (u) => {
  selectedCoordinacion.value = null
  coordinacionOptions.value = []

  if (!u?.id) return

  try {
    const { data } = await axios.post('/pac/coordinaciones', { id_unidad: u.id })
    coordinacionOptions.value = data.listCoordinaciones ?? []
  } catch (e) {
    notyf.error('No se pudieron cargar las coordinaciones.')
  }
})

const fetchTableData = async () => {
  const MIN_SPINNER_DURATION = 1000
  const startTime = Date.now()

  spinnerRef.value?.show()
  const offset = (currentPage.value - 1) * limit.value

  try {
    const { data } = await axios.post('/pac/table', {
      limit: limit.value,
      offset,
      search: searchTerm.value,
      select: parseInt(document.getElementById('footer-filter')?.value || 5),
      name: name.value,
      curp: curp.value,
      is_complete: is_complete.value ? '1' : '0',
      id_accion: listSelectAcction.value?.id ?? '',
    })

    item.value = data.list
    rowsAll.value = data.allRow
    row.value = data.row
  } catch (error) {
    // opcional
  } finally {
    const elapsed = Date.now() - startTime
    const delay = elapsed < MIN_SPINNER_DURATION ? MIN_SPINNER_DURATION - elapsed : 0
    setTimeout(() => spinnerRef.value?.hide(), delay)
  }
}

onMounted(() => {
  main()
  fetchTableData()

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
  fetchTableData()
}

function search_function() {
  currentPage.value = 1
  fetchTableData()
}

// catálogo de cursos (filtro principal)
async function main() {
  try {
    const request = await axios.post('/pac/main')
    listOptionsAcction.value = request.data.listOptionsAcction ?? []
    listSelectAcction.value = (request.data.listSelectAcction ?? [])[0] ?? null
  } catch (error) {
    notyf.error('No se pudo completar la acción. Por favor, vuelve a intentarlo.')
  }
}

async function button_confirm() {
  try {
    const form = document.querySelector('#data_form_x')
    const formData = new FormData(form)
    const key = window._selectkybyemployee // este es id_empl_accion

    formData.append('id_cat_estatus', listSelectStatus.value?.id ?? '')
    formData.append('id_instancia', listSelectInstance.value?.id ?? '')
    formData.append('id_cat_tematica', listSelectTematica.value?.id ?? '')
    formData.append('id_finalidad', listSelectFinalidad.value?.id ?? '')
    formData.set('m_eval_aprendizaje', m_eval_aprendizaje.value ? '1' : '0')
    formData.append('calificacion', parseInt(m_calificacion.value, 10))

    showSpinner()
    clearErrors()

    // ✅ FIX: backend normalmente espera "id" (no id_respuesta)
    formData.append('id', key)

    const response = await axios.post('/pac/save', formData)

    if (!response.data.status) {
      clearErrors()
      notyf.error(response.data.message)
      return
    }

    blurActiveElement()
    $('#modal_password_user').modal('hide')
    notyf.success(response.data.message)
    fetchTableData()
  } catch (error) {
    clearErrors()
    if (error.response && error.response.data.errors) {
      handleErrors(error.response.data.errors)
    }
  } finally {
    hideSpinner()
    canEditCalificacion.value = false
  }
}

async function setOption(id) {
  clearErrors()
  window._selectkybyemployee = id // id_empl_accion
  canEditCalificacion.value = true

  showSpinner()

  try {
    // ✅ FIX: backend DataPacController lee $request->id
    const request = await axios.post('/pac/data', { id })

    if (!request.data?.status) {
      notyf.error(request.data?.message ?? 'No se pudo cargar el empleado.')
      return
    }

    const data = request.data.data
    const selectx = request.data

    m_nivel_salarial.value = data.nivel_salarial
    m_rfc.value = data.rfc
    m_codigo_puesto.value = data.codigo_puesto
    m_clave_clues.value = data.clave_clues
    m_puesto.value = data.puesto
    m_entidad.value = data.entidad
    m_contratacion.value = data.contratacion
    m_curp.value = data.curp
    m_nombre.value = data.nombre
    m_accion.value = data.accion
    m_fecha_ini.value = data.fecha_ini
    m_fecha_fin.value = data.fecha_fin
    m_observaciones.value = data.observaciones
    m_duracion_hrs.value = data.duracion_hrs?.toString() || ''
    m_horas_real.value = data.horas_real?.toString() || ''

    m_eval_aprendizaje.value = String(data.eval_aprendizaje ?? '0') === '1' || data.eval_aprendizaje === true
    m_total_horas.value = (selectx.totalHorasReal ?? 0).toString()

    listOptionStatus.value = selectx.listOptionStatus ?? []
    listSelectStatus.value = (selectx.listSelectStatus ?? [])[0] ?? null
    listOptionInstance.value = selectx.listOptionInstance ?? []
    listSelectInstance.value = (selectx.listSelectInstance ?? [])[0] ?? null
    listOptionTematica.value = selectx.listOptionTematica ?? []
    listSelectTematica.value = (selectx.listSelectTematica ?? [])[0] ?? null

    listOptionFinalidad.value = selectx.listOptionFinalidad ?? []
    listSelectFinalidad.value = (selectx.listSelectFinalidad ?? [])[0] ?? null

    m_calificacion.value = clampCalificacion(data.calificacion ?? 100)

    try {
      const asg = await axios.post('/pac/asignacion-unidad/data', { id })
      m_unidad.value = asg.data?.unidad_txt ?? ''
      m_coordinacion.value = asg.data?.coordinacion_txt ?? ''
    } catch (e) {
      m_unidad.value = ''
      m_coordinacion.value = ''
    }
  } catch (error) {
    notyf.error('No se pudo completar la acción. Por favor, vuelve a intentarlo.')
  } finally {
    hideSpinner()
  }
}

async function openAddCourse(id) {
  selectedEmployeeId.value = id
  selectedCourse.value = null
  canEditCalificacion.value = false

  try {
    const { data } = await axios.post('/pac/courses')
    courseOptions.value = data.listCourses ?? []
  } catch (error) {
    notyf.error('No se pudieron cargar los cursos.')
  }
}

async function confirmAddCourse() {
  if (!selectedEmployeeId.value || !selectedCourse.value?.id) {
    notyf.error('Selecciona un curso.')
    return
  }

  try {
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
    notyf.error('Error al agregar el curso.')
  }
}

async function openAsignacionUnidad() {
  const id = window._selectkybyemployee
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
    unidadOptions.value = data.listUnidades ?? []
  } catch (error) {
    notyf.error('No se pudieron cargar las unidades.')
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
      selectedUnidad.value = unidadOptions.value.find(u => String(u.id) === String(idUnidad)) ?? null
    }

    if (selectedUnidad.value?.id) {
      const cx = await axios.post('/pac/coordinaciones', { id_unidad: selectedUnidad.value.id })
      coordinacionOptions.value = cx.data.listCoordinaciones ?? []

      if (idCoord) {
        selectedCoordinacion.value = coordinacionOptions.value.find(c => String(c.id) === String(idCoord)) ?? null
      }
    }
  } catch (e) {
    // no precarga
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
    notyf.error('Error al guardar la asignación.')
  }
}
</script>