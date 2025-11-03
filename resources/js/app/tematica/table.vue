<template>
  <div>
    <tableTittleSearch value="Temáticas" />
    <tableSpinner ref="spinnerRef" />

    <div class="table-responsive">
      <table class="table align-items-center mb-0" id="table-default">
        <thead>
          <tr>
            <tableRow value="Acciones" />
            <tableRow value="ID" />
            <tableRow value="Temática" />
            <tableRow value="Categorías" />
            <tableRow value="Enfoque" />
          </tr>
        </thead>
        <tbody>
          <tableEmpty v-if="item.length === 0" :colspan="5" />

          <tr v-for="row in item" :key="row.id">
            <td class="text-center">
              <div class="button-container">
                <tableButtonEdit
                  :href="`${BASE_URL}/tematica/edit/` + row.id"
                  icon="fa fa-edit"
                  label="Editar"
                  bgColor="#10312B"
                />
              </div>
            </td>
            <td class="align-middle text-center">
              <span class="text-secondary text-xs font-weight-bold">{{ row.id }}</span>
            </td>
            <td class="align-middle text-center">
              <span class="text-secondary text-xs font-weight-bold">{{ row.tematica }}</span>
            </td>
            <td class="align-middle text-center">
              <span class="text-secondary text-xs font-weight-bold">{{ row.categorias }}</span>
            </td>
            <td class="align-middle text-center">
              <span class="text-secondary text-xs font-weight-bold">{{ row.enfoque }}</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <tableFooter :row="row" :rowsAll="rowsAll" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { notyf } from '@components/notyf.js'
import { BASE_URL } from '@/components/url.js'

import { setupTableEvents } from '@helpers/table/table-events.vue'
import { handlePagination } from '@helpers/table/table-pagination.vue'
import tableButtonEdit from '@helpers/table/table-button-edit.vue'
import tableTittleSearch from '@helpers/table/table-tittle-search.vue'
import tableFooter from '@helpers/table/table-footer.vue'
import tableSpinner from '@helpers/table/table-spinner.vue'
import tableRow from '@helpers/table/table-row.vue'
import tableEmpty from '@helpers/table/table-empty.vue'

import axios from '@axios'

const item = ref([])
const rowsAll = ref(0)
const row = ref(0)
const currentPage = ref(1)
const limit = ref(5)
const searchTerm = ref('')
const spinnerRef = ref(null)

const fetchTableData = async () => {
  const MIN_SPINNER_DURATION = 1000
  const startTime = Date.now()
  spinnerRef.value?.show()
  const offset = (currentPage.value - 1) * limit.value

  try {
    const { data } = await axios.post('/tematica/table', {
      limit: limit.value,
      offset,
      search: searchTerm.value,
      select: parseInt(document.getElementById('footer-filter')?.value || 5),
    })

    item.value = data.list
    rowsAll.value = data.allRow
    row.value = data.row
  } catch (error) {
    notyf.error('No se pudo completar la acción. Por favor, vuelve a intentarlo.')
  } finally {
    const elapsed = Date.now() - startTime
    const delay = elapsed < MIN_SPINNER_DURATION ? MIN_SPINNER_DURATION - elapsed : 0
    setTimeout(() => {
      spinnerRef.value?.hide()
    }, delay)
  }
}

onMounted(() => {
  fetchTableData()
  setupTableEvents({
    fetchTableData,
    searchTerm,
    currentPage,
    limit,
    handlePagination,
  })
})
</script>
