<template>
  <div>
    <!-- Component to display the table title -->
    <tableTittle value="Tipos de Acción" />

    <!-- Spinner component shown while data is loading -->
    <tableSpinner ref="spinnerRef" />

    <div class="table-responsive">
      <!-- Main responsive table -->
      <table class="table align-items-center mb-0" id="table-default">
        <thead>
          <tr>
            <!-- Table column headers -->
            <tableRow value="Acciones" />
            <tableRow value="Estatus" />
            <tableRow value="Descripción" />
            <tableRow value="Temática" />
          </tr>
        </thead>
        <tbody>
          <!-- Show empty row component if no data is available -->
          <tableEmpty v-if="item.length === 0" :colspan="5" />

          <!-- Loop through each row in the data and render a table row -->
          <tr v-for="row in item" :key="row.id">
            <td class="text-center">
              <div class="button-container">

                <tableButtonEdit :href="`${BASE_URL}/user/edit/` + row.id" icon="fa fa-edit" label="Editar"
                  bgColor="#10312B" />

              </div>
            </td>

            <td class="align-middle text-center">
              <span class="text-secondary text-xs font-weight-bold">{{ row.estatus }}</span>
            </td>
            <td class="align-middle text-center">
              <span class="text-secondary text-xs font-weight-bold">{{ row.nombre_accion }}</span>
            </td>
            <td class="align-middle text-center">
              <span class="text-secondary text-xs font-weight-bold">{{ row.tematica }}</span>
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

// Import custom table-related components
import tableTittle from '@helpers/table/table-tittle.vue'
import tableFooter from '@helpers/table/table-footer.vue'
import tableSpinner from '@helpers/table/table-spinner.vue'
import tableRow from '@helpers/table/table-row.vue'
import tableEmpty from '@helpers/table/table-empty.vue'

// Import axios instance for HTTP requests
import axios from '@axios'

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
    const { data } = await axios.post('/action/table', {
      limit: limit.value, // Number of items per page
      offset, // Starting point for current page
      search: searchTerm.value, // Search input value
      select: parseInt(document.getElementById('footer-filter')?.value || 5), // Footer filter value, default 5
    });

    console.log(data)

    item.value = data.list; // Set items to display
    rowsAll.value = data.allRow; // Set total row count
    row.value = data.row; // Set current row count
  } catch (error) {
    notyf.error('No se pudo completar la acción. Por favor, vuelve a intentarlo.'); // Show error notification
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
  fetchTableData()
  setupTableEvents({
    fetchTableData,
    searchTerm,
    currentPage,
    limit,
    handlePagination
  })
})

</script>
