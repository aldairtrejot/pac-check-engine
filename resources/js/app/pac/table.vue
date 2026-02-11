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

            <div class="row">
              <inputSelect
                v-model="listSelectAcction"
                :options="listOptionsAcction"
                id="id_accion"
                label="Acción"
                :multiple="false"
                grid="col-12"
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

    <tableSpinner ref="spinnerRef" />

    <div class="table-responsive">
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

          <tr v-for="row in item" :key="row.id">
            <td class="text-center">
              <div class="button-container">
                <tableButtonDefault
                  color="#081F5E"
                  icon="fa fa-external-link"
                  label="Atender"
                  @click="setOption"
                  :clickEventPayload="row.id"
                  modalToggle="modal"
                  modalTarget="#modal_password_user"
                />

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
  <modalTemplate
    modalId="modal_password_user"
    title="Datos de empleado"
    :onConfirm="button_confirm"
    size="lg"
  >
    <form role="form" id="data_form" enctype="multipart/form-data">
      <div class="row">
        <li class="list-group-item border-0 d-flex p-4 mb-2 bg-gray-100 border-radius-lg">
          <div class="d-flex flex-column w-100">
            <h6 class="mb-3 text-sm">{{ m_nombre }}</h6>

            <div class="row g-2">
              <div class="col-md-6">
                <span class="mb-2 text-xs d-block">CURP: <span class="text-dark font-weight-bold ms-sm-2">{{ m_curp }}</span></span>
                <span class="mb-2 text-xs d-block">RFC: <span class="text-dark ms-sm-2 font-weight-bold">{{ m_rfc }}</span></span>
                <span class="mb-2 text-xs d-block">Cod. Puesto: <span class="text-dark font-weight-bold ms-sm-2">{{ m_codigo_puesto }}</span></span>
                <span class="mb-2 text-xs d-block">Puesto: <span class="text-dark ms-sm-2 font-weight-bold">{{ m_puesto }}</span></span>
              </div>

              <div class="col-md-6">
                <span class="mb-2 text-xs d-block">Contratación: <span class="text-dark ms-sm-2 font-weight-bold">{{ m_contratacion }}</span></span>
                <span class="mb-2 text-xs d-block">Nivel salarial: <span class="text-dark ms-sm-2 font-weight-bold">{{ m_nivel_salarial }}</span></span>
                <span class="mb-2 text-xs d-block">CLUES: <span class="text-dark font-weight-bold ms-sm-2">{{ m_clave_clues }}</span></span>
                <span class="mb-2 text-xs d-block">Entidad: <span class="text-dark ms-sm-2 font-weight-bold">{{ m_entidad }}</span></span>
              </div>
            </div>

            <div class="row g-2 mt-2">
              <div class="col-12">
                <span class="mb-2 text-xs d-block">Acción: <span class="text-dark ms-sm-2 font-weight-bold">{{ m_accion }}</span></span>
                <span class="mb-2 text-xs d-block">Horas acumuladas: <span class="text-dark ms-sm-2 font-weight-bold">{{ m_total_horas }}</span></span>
              </div>
            </div>
          </div>
        </li>

        <!-- ⚠️ Nota: tienes forms anidados; lo dejo como lo traes para no romperte,
             pero idealmente QUITA el form interno y deja solo uno -->
        <form role="form" id="data_form_x" enctype="multipart/form-data">
          <div class="row g-2">
            <inputField label="Horas obligatorias" id="m_duracion_hrs" v-model="m_duracion_hrs" :disabled="true" grid="col-12 col-md-6 mb-2" />
            <inputField label="Horas realizadas" id="m_horas_real" v-model="m_horas_real" grid="col-12 col-md-6 mb-2" />
          </div>

          <div class="row g-2">
            <inputField type="date" label="Fecha Inicio" id="m_fecha_ini" v-model="m_fecha_ini" grid="col-12 col-md-6 mb-2" />
            <inputField type="date" label="Fecha Fin" id="m_fecha_fin" v-model="m_fecha_fin" grid="col-12 col-md-6 mb-2" />
          </div>

          <div class="row g-2">
            <inputSelect v-model="listSelectStatus" :options="listOptionStatus" id="id_cat_estatus" label="Estatus" :multiple="false" grid="col-12 col-md-6 mb-2" />
            <inputSelect v-model="listSelectInstance" :options="listOptionInstance" id="id_instancia" :multiple="false" label="Instancia" grid="col-12 col-md-6 mb-2" />
          </div>

          <div class="row g-2">
            <inputSelect v-model="listSelectFinalidad" :options="listOptionFinalidad" id="id_finalidad" label="Finalidad" :multiple="false" grid="col-12 col-md-6 mb-2" />
            <inputField
              type="number"
              label="Calificación (70–100)"
              id="calificacion"
              v-model="m_calificacion"
              grid="col-12 col-md-6 mb-2"
              :disabled="!canEditCalificacion"
              :min="70"
              :max="100"
              :step="1"
            />
          </div>

          <div class="row g-2">
            <inputSelect v-model="listSelectTematica" :options="listOptionTematica" id="id_cat_tematica" label="Temática" :multiple="false" grid="col-12 mb-2" />
          </div>

          <div class="row g-2">
            <inputField grid="col-12 mb-2" label="Observaciones" id="m_observaciones" v-model="m_observaciones" :uppercase="true" />
          </div>

          <div class="row g-2">
            <inputCheckbox v-model="m_eval_aprendizaje" :label="'¿Realizó la evaluación de aprendizaje?'" :id="'m_eval_aprendizaje'" />
          </div>
        </form>
      </div>
    </form>
  </modalTemplate>

  <!-- MODAL: AGREGAR CURSO AL EMPLEADO -->
  <modalTemplate
    modalId="modal_add_course"
    title="Agregar curso al empleado"
    :onConfirm="confirmAddCourse"
    size="md"
  >
    <form id="form_add_course">
      <div class="row g-2">
        <inputSelect
          :key="coursesKey"
          v-model="selectedCourse"
          :options="courseOptions"
          id="id_accion_add"
          label="Curso"
          :multiple="false"
          grid="col-12"
          :required="true"
        />
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

const gridx3 = ref('col-12 col-md-6 mb-2')
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

// finalidad
const listSelectFinalidad = ref([])
const listOptionFinalidad = ref([])

// calificación manual (70..100)
const m_calificacion = ref(100)
const canEditCalificacion = ref(false)

// total horas acumuladas
const m_total_horas = ref('')

// estado para "Agregar curso"
const selectedEmployeeId = ref(null)
const selectedCourse = ref(null)
const courseOptions = ref([])
const coursesKey = ref(0) // ✅ fuerza refresh del inputSelect

function clampCalificacion(val) {
  let n = parseInt(val ?? 100, 10)
  if (Number.isNaN(n)) n = 100
  if (n < 70) n = 70
  if (n > 100) n = 100
  return n
}

watch(m_calificacion, (val) => {
  const fixed = clampCalificacion(val)
  if (fixed !== val) m_calificacion.value = fixed
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
    const key = window._selectkybyemployee

    formData.append('id_cat_estatus', listSelectStatus.value?.id ?? '')
    formData.append('id_instancia', listSelectInstance.value?.id ?? '')
    formData.append('id_cat_tematica', listSelectTematica.value?.id ?? '')
    formData.append('id_finalidad', listSelectFinalidad.value?.id ?? '')
    formData.set('m_eval_aprendizaje', m_eval_aprendizaje.value ? '1' : '0')
    formData.append('calificacion', clampCalificacion(m_calificacion.value))

    showSpinner()
    clearErrors()

    formData.append('id', key)

    const response = await axios.post('/pac/save', formData)

    if (!response.data.status) {
      clearErrors()
      notyf.error(response.data.message)
    }

    if (response.data.status) {
      $('#modal_password_user').modal('hide')
      notyf.success(response.data.message)
      fetchTableData()
    }
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
  window._selectkybyemployee = id

  canEditCalificacion.value = true
  showSpinner()

  try {
    const request = await axios.post('/pac/data', { id })
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
    m_eval_aprendizaje.value = data.eval_aprendizaje === true

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

  } catch (error) {
    notyf.error('No se pudo completar la acción. Por favor, vuelve a intentarlo.')
  } finally {
    hideSpinner()
  }
}

// Abrir modal "Agregar curso"
async function openAddCourse(id) {
  selectedEmployeeId.value = id
  selectedCourse.value = null
  canEditCalificacion.value = false

  try {
    const { data } = await axios.post('/pac/courses')
    courseOptions.value = data.listCourses ?? []
    coursesKey.value++ // ✅ fuerza re-render del select
  } catch (error) {
    const msg =
      error?.response?.data?.message ||
      error?.response?.data?.error ||
      'No se pudieron cargar los cursos.'
    notyf.error(msg)
  }
}

// Confirmar alta de curso para el empleado
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

    $('#modal_add_course').modal('hide')
    notyf.success(data.message ?? 'Curso agregado correctamente.')
    fetchTableData()
  } catch (error) {
    const msg =
      error?.response?.data?.message ||
      error?.response?.data?.error ||
      'Error al agregar el curso.'
    notyf.error(msg)
  }
}
</script>