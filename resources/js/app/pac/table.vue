<template>
  <div>
    <!-- Component to display the table title -->
    <tableTittle value="Mi plantilla" />


    <div class="accordion accordion-flush shadow-sm rounded-3 border" id="accordionFilters">
      <div class="accordion-item">
        <h2 class="accordion-header" id="headingFilters">
          <button class="accordion-button collapsed d-flex align-items-center gap-2 fw-bold" type="button"
            data-bs-toggle="collapse" data-bs-target="#collapseFilters" aria-expanded="false"
            aria-controls="collapseFilters">
            <i class="fas fa-filter text-secondary"></i> Filtros de búsqueda
          </button>
        </h2>

        <div id="collapseFilters" class="accordion-collapse collapse" aria-labelledby="headingFilters"
          data-bs-parent="#accordionFilters">
          <div class="accordion-body bg-light rounded-bottom">

            <div class="row g-2 mb-0">
              <inputField :grid="gridx3" label="Nombre" id="curp" v-model="name" :uppercase="true" />
              <inputField :grid="gridx3" label="CURP" id="curp" v-model="curp" :uppercase="true" />
              <div class="col-12 col-md-4 mb-3">
                <inputCheckbox v-model="is_complete" :label="'¿Es atendido?'" :id="'estatus'" />
              </div>
            </div>

            <div class="row mt-0">
              <inputSelect v-model="listSelectAcction" :options="listOptionsAcction" id="id_accion" label="Curso"
                :multiple="false" grid="col-md-12 col-sm-12" :required="true" />
            </div>
            <br>

            <div class="d-flex justify-content-end flex-wrap gap-2">
              <tableButtonDefault color="white" icon="fas fa-brush" label="Limpiar" @click="clear_search"
                color_icon="#777777" :clickEventPayload="null" />
              <button type="button" class="btn btn-sm btn-secondary" @click="search_function">
                <span class="d-none d-sm-inline">Buscar</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
    <br>



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
            <tableRow value="Curso" />
          </tr>
        </thead>
        <tbody>
          <!-- Show empty row component if no data is available -->
          <tableEmpty v-if="item.length === 0" :colspan="5" />

          <!-- Loop through each row in the data and render a table row -->
          <tr v-for="row in item" :key="row.id">
            <td class="text-center">
              <div class="button-container">

                <tableButtonDefault color="#081F5E" icon="fa fa-external-link" label="Atender" @click="clear_search"
                  :clickEventPayload="null" />

                <tableButtonDefault color="#0E5E08" icon="fa fa-history" label="Check" @click="clear_search"
                  :clickEventPayload="null" />

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
</template>

<script setup>
// Import reactivity functions from Vue
import { ref, onMounted } from 'vue'
import { notyf } from '@components/notyf.js';
import { BASE_URL } from '@/components/url.js'

// Helper functions for table events and pagination
import { setupTableEvents } from '@helpers/table/table-events.vue'
import { handlePagination } from '@helpers/table/table-pagination.vue'
import tableButtonEdit from '@helpers/table/table-button-edit.vue'
import inputField from '@helpers/form/input-field.vue';

// Import custom table-related components
import tableTittle from '@helpers/table/table-tittle.vue'
import tableFooter from '@helpers/table/table-footer.vue'
import tableSpinner from '@helpers/table/table-spinner.vue'
import tableRow from '@helpers/table/table-row.vue'
import tableItem from '@helpers/table/table-item.vue'
import tableEmpty from '@helpers/table/table-empty.vue'
import tableButtonDefault from '@helpers/table/table-button-default.vue'
import inputSelect from '@helpers/form/input-select.vue';
import inputCheckbox from '@helpers/form/input-checkbox.vue';

// Import axios instance for HTTP requests
import axios from '@axios'
const gridx3 = ref('col-12 col-md-4 mb-3')
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

// Function to fetch table data from backend
const fetchTableData = async () => {
  const MIN_SPINNER_DURATION = 1000; // Minimum spinner visible time in milliseconds
  const startTime = Date.now(); // Capture start time

  spinnerRef.value?.show(); // Show spinner

  const offset = (currentPage.value - 1) * limit.value; // Calculate pagination offset

  try {
    const { data } = await axios.post('/pac/table', {
      limit: limit.value, // Number of items per page
      offset, // Starting point for current page
      search: searchTerm.value, // Search input value
      select: parseInt(document.getElementById('footer-filter')?.value || 5), // Footer filter value, default 5
      name: name.value,
      curp: curp.value,
      is_complete: is_complete.value ? '1' : '0',
      id_accion: listSelectAcction.value?.id ?? ''
    });
    console.log(data)

    item.value = data.list; // Set items to display
    rowsAll.value = data.allRow; // Set total row count
    row.value = data.row; // Set current row count
  } catch (error) {
    // notyfEM.error('No se pudo completar la acción. Por favor, vuelve a intentarlo.'); // Show error notification
  } finally {
    const elapsed = Date.now() - startTime; // Calculate elapsed time
    const delay = elapsed < MIN_SPINNER_DURATION ? MIN_SPINNER_DURATION - elapsed : 0; // Calculate remaining delay

    setTimeout(() => {
      spinnerRef.value?.hide(); // Hide spinner after minimum duration
    }, delay);
  }
};


// Load data and set up table events on component mount
onMounted(() => {
  main()
  fetchTableData()
  setupTableEvents({
    fetchTableData,
    searchTerm,
    currentPage,
    limit,
    handlePagination
  })
})

// LA función limpia los filtros y actualiza la información
function clear_search() {
  currentPage.value = 1;
  name.value = ''
  curp.value = ''
  is_complete.value = false
  listSelectAcction.value = null,
    fetchTableData();
}

// La función realiza la bsuqueda
function search_function() {
  currentPage.value = 1;
  fetchTableData();
}

// Inicio de catalogo de cursos
async function main() {

  try {
    const request = await axios.post('/pac/main')

    listOptionsAcction.value = request.data.listOptionsAcction ?? []
    listSelectAcction.value = (request.data.listSelectAcction ?? [])[0] ?? null
  } catch (error) {
    notyf.error('No se pudo completar la acción. Por favor, vuelve a intentarlo.')
  }
}

</script>
