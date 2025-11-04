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
              <!--
              <div class="col-12 col-md-4 mb-0">
                <inputCheckbox v-model="is_complete" :label="'¿Es atendido?'" :id="'estatus'" />
              </div>
              -->
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
            <!-- Table column headers -->
            <tableRow value="Acciones" />
            <tableRow value="¿Es atentido?" />
            <tableRow value="Nombre" />
            <tableRow value="CURP" />
            <tableRow value="Acción" />
          </tr>
        </thead>
        <tbody>
          <!-- Show empty row component if no data is available -->
          <tableEmpty v-if="item.length === 0" :colspan="5" />

          <!-- Loop through each row in the data and render a table row -->
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

    <!-- Table footer with pagination -->
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
            <div class="row">

              <div class="col-md-6">
                <span class="mb-2 text-xs d-block">
                  CURP:
                  <span class="text-dark font-weight-bold ms-sm-2">{{ m_curp }}</span>
                </span>
                <span class="mb-2 text-xs d-block">
                  RFC:
                  <span class="text-dark ms-sm-2 font-weight-bold">{{ m_rfc }}</span>
                </span>
                <span class="mb-2 text-xs d-block">
                  Cod. Puesto:
                  <span class="text-dark font-weight-bold ms-sm-2">{{ m_codigo_puesto }}</span>
                </span>
                <span class="mb-2 text-xs d-block">
                  Puesto:
                  <span class="text-dark ms-sm-2 font-weight-bold">{{ m_puesto }}</span>
                </span>
              </div>

              <div class="col-md-6">
                <span class="mb-2 text-xs d-block">
                  Contratación:
                  <span class="text-dark ms-sm-2 font-weight-bold">{{ m_contratacion }}</span>
                </span>
                <span class="mb-2 text-xs d-block">
                  Nivel salarial:
                  <span class="text-dark ms-sm-2 font-weight-bold">{{ m_nivel_salarial }}</span>
                </span>
                <span class="mb-2 text-xs d-block">
                  CLUES:
                  <span class="text-dark font-weight-bold ms-sm-2">{{ m_clave_clues }}</span>
                </span>
                <span class="mb-2 text-xs d-block">
                  Entidad:
                  <span class="text-dark ms-sm-2 font-weight-bold">{{ m_entidad }}</span>
                </span>
              </div>
            </div>
            <div class="row">
              <span class="mb-2 text-xs d-block">
                Acción:
                <span class="text-dark ms-sm-2 font-weight-bold">{{ m_accion }}</span>
              </span>
            </div>
          </div>
        </li>

        <form role="form" id="data_form_x" enctype="multipart/form-data">
          <div class="row">
            <inputField
              label="Horas obligatorias"
              id="m_duracion_hrs"
              v-model="m_duracion_hrs"
              :disabled="true"
            />
            <inputField label="Horas realizadas" id="m_horas_real" v-model="m_horas_real" />
          </div>
          <div class="row" style="margin-top: -20px !important;">
            <inputField
              :grid="gridx3"
              type="date"
              label="Fecha Inicio"
              id="m_fecha_ini"
              v-model="m_fecha_ini"
            />
            <inputField
              :grid="gridx3"
              type="date"
              label="Fecha Fin"
              id="m_fecha_fin"
              v-model="m_fecha_fin"
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
            />

            <inputSelect
              v-model="listSelectInstance"
              :options="listOptionInstance"
              id="id_instancia"
              :multiple="false"
              label="Instancia"
              grid="col-md-6 col-sm-12"
            />
          </div>
          <div class="row">
            <inputSelect
              v-model="listSelectTematica"
              :options="listOptionTematica"
              id="id_cat_tematica"
              label="Temática"
              :multiple="false"
              grid="col-12"
            />
            <inputSelect v-model="listSelectTematica" :options="listOptionTematica" id="id_cat_tematica"
              label="Temática" :multiple="false" grid="col-12" :disabled="true"/>
          </div>
          <div class="row">
            <inputField
              grid="col-12"
              label="Observaciones"
              id="m_observaciones"
              v-model="m_observaciones"
              :uppercase="true"
            />
          </div>
          <div class="row">
            <inputCheckbox
              v-model="m_eval_aprendizaje"
              :label="'¿Realizó la evaluación de aprendizaje?'"
              :id="'m_eval_aprendizaje'"
            />
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
      <div class="row">
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
    </form>
  </modalTemplate>
</template>

<script setup>
// Import reactivity functions from Vue
import { ref, onMounted } from 'vue'
import { notyf } from '@components/notyf.js'
import { BASE_URL } from '@/components/url.js'

// Helper functions for table events and pagination
import { setupTableEvents } from '@helpers/table/table-events.vue'
import { handlePagination } from '@helpers/table/table-pagination.vue'
import tableButtonEdit from '@helpers/table/table-button-edit.vue'
import inputField from '@helpers/form/input-field.vue'
import modalTemplate from '@helpers/modal/modal-template.vue' // Custom modal component
import { showSpinner, hideSpinner } from '@components/spinner.js'
// Import custom table-related components
import tableTittle from '@helpers/table/table-tittle.vue'
import tableFooter from '@helpers/table/table-footer.vue'
import tableSpinner from '@helpers/table/table-spinner.vue'
import tableRow from '@helpers/table/table-row.vue'
import tableItem from '@helpers/table/table-item.vue'
import tableEmpty from '@helpers/table/table-empty.vue'
import tableButtonDefault from '@helpers/table/table-button-default.vue'
import inputSelect from '@helpers/form/input-select.vue'
import inputCheckbox from '@helpers/form/input-checkbox.vue'
import { clearErrors } from '@components/clearErrors.js' // Importing function to clear previous errors
import { handleErrors } from '@components/handleErrors.js'

// Import axios instance for HTTP requests
import axios from '@axios'

const gridx3 = ref('col-12 col-md-6 mb-6')
const name = ref('')
const curp = ref('')
const is_complete = ref(false)
const listSelectAcction = ref([])
const listOptionsAcction = ref([])

// Reactive variables
const item = ref([]) // Table data list
const rowsAll = ref(0) // Total number of rows
const row = ref(0) // Number of rows currently shown
const currentPage = ref(1) // Current page number
const limit = ref(5) // Limit of rows per page
const searchTerm = ref('') // Search input value
const spinnerRef = ref(null) // Spinner reference

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

// estado para "Agregar curso"
const selectedEmployeeId = ref(null)
const selectedCourse = ref(null)
const courseOptions = ref([])

// Function to fetch table data from backend
const fetchTableData = async () => {
  const MIN_SPINNER_DURATION = 1000 // Minimum spinner visible time in milliseconds
  const startTime = Date.now() // Capture start time

  spinnerRef.value?.show() // Show spinner

  const offset = (currentPage.value - 1) * limit.value // Calculate pagination offset

  try {
    const { data } = await axios.post('/pac/table', {
      limit: limit.value, // Number of items per page
      offset, // Starting point for current page
      search: searchTerm.value, // Search input value
      select: parseInt(document.getElementById('footer-filter')?.value || 5), // Footer filter value, default 5
      name: name.value,
      curp: curp.value,
      is_complete: is_complete.value ? '1' : '0',
      id_accion: listSelectAcction.value?.id ?? '',
    })

    item.value = data.list // Set items to display
    rowsAll.value = data.allRow // Set total row count
    row.value = data.row // Set current row count
  } catch (error) {
    // notyf.error('No se pudo completar la acción. Por favor, vuelve a intentarlo.')
  } finally {
    const elapsed = Date.now() - startTime // Calculate elapsed time
    const delay = elapsed < MIN_SPINNER_DURATION ? MIN_SPINNER_DURATION - elapsed : 0 // Calculate remaining delay

    setTimeout(() => {
      spinnerRef.value?.hide() // Hide spinner after minimum duration
    }, delay)
  }
}

// Load data and set up table events on component mount
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

// La función limpia los filtros y actualiza la información
function clear_search() {
  currentPage.value = 1
  name.value = ''
  curp.value = ''
  is_complete.value = false
  listSelectAcction.value = null
  fetchTableData()
}

// La función realiza la búsqueda
function search_function() {
  currentPage.value = 1
  fetchTableData()
}

// Inicio de catálogo de cursos (filtro principal)
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
    const form = document.querySelector('#data_form_x') // Select the form
    const formData = new FormData(form) // Create a FormData object with the form data
    const key = window._selectkybyemployee

    formData.append('id_cat_estatus', listSelectStatus.value?.id ?? '')
    formData.append('id_instancia', listSelectInstance.value?.id ?? '')
    formData.append('id_cat_tematica', listSelectTematica.value?.id ?? '')
    formData.set('m_eval_aprendizaje', m_eval_aprendizaje.value ? '1' : '0')

    showSpinner() // Start the loader to indicate processing
    clearErrors() // Clear any previous errors

    // Send a POST request to the backend with the form data
    formData.append('id', key)

    const response = await axios.post('/pac/save', formData)

    if (!response.data.status) {
      clearErrors() // Clear errors if there is an issue
      notyf.error(response.data.message) // Show the error message using the notification system
    }

    if (response.data.status) {
      $('#modal_password_user').modal('hide')
      notyf.success(response.data.message)
      fetchTableData()
    }
  } catch (error) {
    // Handle any errors that occur during the request
    clearErrors() // Clear previous errors
    if (error.response && error.response.data.errors) {
      handleErrors(error.response.data.errors) // Display validation errors using the handleErrors function
    }
  } finally {
    hideSpinner() // Stop the loader after the request is finished
  }
}

async function setOption(id) {
  clearErrors()
  window._selectkybyemployee = id
  showSpinner()
  try {
    const request = await axios.post('/pac/data', { id: id })
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

    listOptionStatus.value = selectx.listOptionStatus ?? []
    listSelectStatus.value = (selectx.listSelectStatus ?? [])[0] ?? null
    listOptionInstance.value = selectx.listOptionInstance ?? []
    listSelectInstance.value = (selectx.listSelectInstance ?? [])[0] ?? null
    listOptionTematica.value = selectx.listOptionTematica ?? []
    listSelectTematica.value = (selectx.listSelectTematica ?? [])[0] ?? null
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

  try {
    const { data } = await axios.post('/pac/courses')
    courseOptions.value = data.listCourses ?? []
  } catch (error) {
    notyf.error('No se pudieron cargar los cursos.')
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
    notyf.success(data.message ?? 'Curso agregado correctamente con ID generado y finalidad asignada.')
    fetchTableData()
  } catch (error) {
    notyf.error('Error al agregar el curso.')
  }
}
</script>
