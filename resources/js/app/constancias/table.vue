<template>
  <div>
    <tableTittle value="Revisión de constancias" />

    <!-- Filtros -->
    <div class="accordion accordion-flush shadow-sm rounded-3 border" id="accordionFiltersConst">
      <div class="accordion-item">
        <h2 class="accordion-header" id="headingFiltersConst">
          <button
            class="accordion-button collapsed d-flex align-items-center gap-2 fw-bold"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#collapseFiltersConst"
            aria-expanded="false"
            aria-controls="collapseFiltersConst"
          >
            <i class="fas fa-filter text-secondary"></i> Filtros de búsqueda
          </button>
        </h2>

        <div
          id="collapseFiltersConst"
          class="accordion-collapse collapse"
          aria-labelledby="headingFiltersConst"
          data-bs-parent="#accordionFiltersConst"
        >
          <div class="accordion-body bg-light rounded-bottom p-2">
            <div class="row g-1 mb-0">
              <inputField :grid="gridx3" label="CURP" id="curp" v-model="f_curp" :uppercase="true" />
              <inputField :grid="gridx3" label="Nombre del curso" id="curso" v-model="f_curso" :uppercase="true" />
            </div>

            <div class="row" style="margin-top: -70px !important;">
              <inputField
                grid="col-12 col-md-6 mb-6"
                type="number"
                label="Año"
                id="anio"
                v-model="f_anio"
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

    <!-- Spinner -->
    <tableSpinner ref="spinnerRef" />

    <!-- Tabla -->
    <div class="table-responsive">
      <table class="table align-items-center mb-0" id="table-default">
        <thead>
          <tr>
            <tableRow value="Acciones" />
            <tableRow value="CURP" />
            <tableRow value="Nombre del curso" />
            <tableRow value="Estatus" />
          </tr>
        </thead>

        <tbody>
          <tableEmpty v-if="item.length === 0" :colspan="4" />

          <tr v-for="row in item" :key="row.id">
            <td class="text-center">
              <div class="button-container">
                <tableButtonDefault
                  color="#081F5E"
                  icon="fa fa-info-circle"
                  label="Detalles"
                  @click="openDetails"
                  :clickEventPayload="row.id"
                />
              </div>
            </td>

            <td class="align-middle text-center">
              <span class="text-secondary text-xs font-weight-bold">{{ row.curp }}</span>
            </td>

            <td class="align-middle text-center">
              <span class="text-secondary text-xs font-weight-bold">{{ row.nombre_curso }}</span>
            </td>

            <td class="align-middle text-center">
              <span class="text-secondary text-xs font-weight-bold">{{ row.estatus }}</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <tableFooter :row="row" :rowsAll="rowsAll" />

    <!-- MODAL: DETALLES -->
    <div class="modal fade" id="modal_constancia_detalles" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 14px;">
          <div class="modal-header">
            <h5 class="modal-title">Detalles de constancia</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>

          <div class="modal-body">
            <div class="row g-2">
              <div class="col-12 col-md-6">
                <div class="d-flex align-items-start mb-1" style="line-height:1.28;">
                  <span class="text-xs text-secondary" style="min-width:120px;">CURP:</span>
                  <span class="text-xs text-dark font-weight-bold flex-grow-1" style="overflow-wrap:anywhere;">
                    {{ m_curp || '—' }}
                  </span>
                </div>

                <div class="d-flex align-items-start mb-1" style="line-height:1.28;">
                  <span class="text-xs text-secondary" style="min-width:120px;">Curso:</span>
                  <span class="text-xs text-dark font-weight-bold flex-grow-1" style="overflow-wrap:anywhere;">
                    {{ m_curso || '—' }}
                  </span>
                </div>

                <div class="d-flex align-items-start mb-1" style="line-height:1.28;">
                  <span class="text-xs text-secondary" style="min-width:120px;">Año:</span>
                  <span class="text-xs text-dark font-weight-bold flex-grow-1">
                    {{ m_anio || '—' }}
                  </span>
                </div>
              </div>

              <div class="col-12 col-md-6">
                <div class="d-flex align-items-start mb-1" style="line-height:1.28;">
                  <span class="text-xs text-secondary" style="min-width:120px;">Estatus:</span>
                  <span class="text-xs text-dark font-weight-bold flex-grow-1">
                    {{ m_estatus || '—' }}
                  </span>
                </div>

                <div class="mt-2">
                  <a
                    v-if="m_link_constancia"
                    :href="m_link_constancia"
                    target="_blank"
                    class="btn btn-sm btn-outline-primary"
                    style="border-radius: 10px;"
                  >
                    <i class="fa fa-eye me-1"></i> Ver constancia
                  </a>
                  <div v-else class="text-muted text-sm">
                    Sin constancia ligada (pendiente BD).
                  </div>
                </div>
              </div>
            </div>

            <hr />

            <!-- Placeholder para los datos aún no definidos -->
            <div class="alert alert-light border mb-0" style="border-radius: 12px;">
              <div class="text-xs text-secondary fw-bold mb-1">Información adicional (pendiente de definición)</div>
              <pre class="mb-0" style="white-space:pre-wrap;">{{ m_detalles_pretty }}</pre>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">
              Cerrar
            </button>

            <button
              type="button"
              class="btn btn-sm text-white"
              style="background:#8B0000;border-color:#8B0000;"
              @click="updateStatus('RECHAZADA')"
            >
              <i class="fa fa-times me-1"></i> Rechazar
            </button>

            <button
              type="button"
              class="btn btn-sm text-white"
              style="background:#235B4E;border-color:#235B4E;"
              @click="updateStatus('ACEPTADA')"
            >
              <i class="fa fa-check me-1"></i> Aceptar
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from '@axios'
import { notyf } from '@components/notyf.js'
import { showSpinner, hideSpinner } from '@components/spinner.js'

// Helpers tabla
import { setupTableEvents } from '@helpers/table/table-events.vue'
import { handlePagination } from '@helpers/table/table-pagination.vue'

// UI tabla
import tableTittle from '@helpers/table/table-tittle.vue'
import tableFooter from '@helpers/table/table-footer.vue'
import tableSpinner from '@helpers/table/table-spinner.vue'
import tableRow from '@helpers/table/table-row.vue'
import tableEmpty from '@helpers/table/table-empty.vue'
import tableButtonDefault from '@helpers/table/table-button-default.vue'
import inputField from '@helpers/form/input-field.vue'

// Estado filtros
const gridx3 = ref('col-12 col-md-6 mb-6')
const f_curp = ref('')
const f_curso = ref('')
const f_anio = ref('')

// Estado tabla
const item = ref([])
const rowsAll = ref(0)
const row = ref(0)
const currentPage = ref(1)
const limit = ref(5)
const searchTerm = ref('')
const spinnerRef = ref(null)

// Modal (datos base + placeholder)
const selectedId = ref(null)
const m_curp = ref('')
const m_curso = ref('')
const m_anio = ref('')
const m_estatus = ref('')
const m_link_constancia = ref('')
const m_detalles_pretty = ref('—')

// Fetch tabla
const fetchTableData = async () => {
  const MIN_SPINNER_DURATION = 1000
  const startTime = Date.now()

  spinnerRef.value?.show()
  const offset = (currentPage.value - 1) * limit.value

  try {
    const { data } = await axios.post('/constancias/table', {
      limit: limit.value,
      offset,
      search: searchTerm.value,
      curp: f_curp.value,
      curso: f_curso.value,
      anio: f_anio.value,
      select: parseInt(document.getElementById('footer-filter')?.value || 5),
    })

    item.value = data.list || []
    rowsAll.value = data.allRow || 0
    row.value = data.row || 0

    // Toast si no hay resultados y el usuario filtró
    const hasFilters = !!(f_curp.value || f_curso.value || f_anio.value || searchTerm.value)
    if (hasFilters && (data.allRow || 0) === 0) {
      notyf.error('No se encontraron constancias con los filtros seleccionados.')
    }
  } catch (e) {
    notyf.error('No se pudo completar la acción. Por favor, vuelve a intentarlo.')
  } finally {
    const elapsed = Date.now() - startTime
    const delay = elapsed < MIN_SPINNER_DURATION ? MIN_SPINNER_DURATION - elapsed : 0
    setTimeout(() => spinnerRef.value?.hide(), delay)
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

function clear_search() {
  currentPage.value = 1
  f_curp.value = ''
  f_curso.value = ''
  f_anio.value = ''
  fetchTableData()
}

function search_function() {
  currentPage.value = 1
  fetchTableData()
}

// Abrir modal Detalles
async function openDetails(id) {
  if (!id) return
  selectedId.value = id

  // reset
  m_curp.value = ''
  m_curso.value = ''
  m_anio.value = ''
  m_estatus.value = ''
  m_link_constancia.value = ''
  m_detalles_pretty.value = '—'

  try {
    showSpinner()

    const { data } = await axios.post('/constancias/data', { id })
    if (!data.status) {
      notyf.error(data.message ?? 'No se pudo cargar el detalle.')
      return
    }

    const d = data.data || {}

    // ✅ Ajusta llaves según BD final:
    m_curp.value = d.curp ?? ''
    m_curso.value = d.nombre_curso ?? ''
    m_anio.value = d.anio ?? ''
    m_estatus.value = d.estatus ?? ''

    // Link viene de BD (aún por definir):
    // ejemplo: d.link_constancia (puede ser url completa o ruta)
    m_link_constancia.value = d.link_constancia ?? ''

    // Placeholder de “datos no definidos”: si luego guardan json, aquí lo pintas
    // ejemplo: d.detalles_json
    if (d.detalles_json) {
      try {
        m_detalles_pretty.value = JSON.stringify(d.detalles_json, null, 2)
      } catch (e) {
        m_detalles_pretty.value = String(d.detalles_json)
      }
    } else {
      m_detalles_pretty.value = 'Pendiente de definición por el equipo.'
    }

    $('#modal_constancia_detalles').modal('show')
  } catch (e) {
    notyf.error('No se pudo cargar el detalle. Intenta nuevamente.')
  } finally {
    hideSpinner()
  }
}

// Aceptar / Rechazar
async function updateStatus(estatus) {
  if (!selectedId.value) return

  try {
    showSpinner()

    const { data } = await axios.post('/constancias/estatus', {
      id: selectedId.value,
      estatus,
    })

    if (!data.status) {
      notyf.error(data.message ?? 'No se pudo actualizar el estatus.')
      return
    }

    notyf.success(data.message ?? 'Estatus actualizado.')
    m_estatus.value = data.estatus ?? estatus

    // actualizar en la tabla local
    const idx = item.value.findIndex(x => String(x.id) === String(selectedId.value))
    if (idx >= 0) item.value[idx].estatus = m_estatus.value

    $('#modal_constancia_detalles').modal('hide')
  } catch (e) {
    notyf.error('Error al actualizar el estatus.')
  } finally {
    hideSpinner()
  }
}
</script>