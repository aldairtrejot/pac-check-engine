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

          <tr v-for="row in item" :key="row.id_respuesta">
            <td class="text-center">
              <div class="button-container">
                <tableButtonDefault
                  color="#081F5E"
                  icon="fa fa-info-circle"
                  label="Detalles"
                  @click="openDetails"
                  :clickEventPayload="row.id_respuesta"
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
              <span class="text-secondary text-xs font-weight-bold">{{ row.estatus_txt }}</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <tableFooter :row="row" :rowsAll="rowsAll" />

    <!-- MODAL: DETALLES (sin fondo oscuro, estilo claro institucional) -->
    <div class="modal fade" id="modal_constancia_detalles" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 16px; overflow:hidden;">

          <!-- Header blanco, acento institucional -->
          <div
            class="modal-header"
            style="
              background:#ffffff;
              border-bottom:1px solid #e9ecef;
              position:relative;
              padding:16px 16px 14px 16px;
            "
          >
            <div
              style="
                position:absolute;
                left:0; top:0; bottom:0;
                width:6px;
                background:#235B4E;
              "
            ></div>

            <div class="d-flex align-items-center gap-3 w-100" style="padding-left: 8px;">
              <span
                class="d-inline-flex align-items-center justify-content-center"
                style="
                  width:42px; height:42px;
                  border-radius:14px;
                  background: rgba(16,49,43,.10);
                  color:#10312B;
                  flex:0 0 auto;
                "
              >
                <i class="fa fa-certificate"></i>
              </span>

              <div class="flex-grow-1 min-w-0">
                <h5 class="modal-title mb-1" style="font-weight:800; color:#10312B; letter-spacing:.2px;">
                  Detalles de constancia
                </h5>
                <div class="text-xs text-muted" style="line-height:1.25;">
                  Valida la constancia y actualiza el estatus del registro.
                </div>
              </div>

              <span
                class="badge"
                :class="statusBadgeClass(d.estatus_txt)"
                style="border-radius:999px; padding:8px 10px; font-weight:800; letter-spacing:.2px;"
              >
                {{ d.estatus_txt || 'SIN ESTATUS' }}
              </span>
            </div>

            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>

          <!-- Body totalmente CLARO (sin fondo oscuro) -->
          <div class="modal-body" style="background:#ffffff;">
            <div class="row g-3">
              <!-- Participante -->
              <div class="col-12">
                <div class="border" style="border-radius:14px; padding:14px;">
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                      <span
                        class="d-inline-flex align-items-center justify-content-center"
                        style="width:34px;height:34px;border-radius:12px;background:rgba(16,49,43,.10);color:#10312B;"
                      >
                        <i class="fa fa-user"></i>
                      </span>
                      <div>
                        <div class="text-sm" style="font-weight:800;color:#10312B;">Participante</div>
                        <div class="text-xs text-muted">Datos de identificación</div>
                      </div>
                    </div>

                    <div class="text-xs text-muted d-none d-md-block" style="font-weight:700;">
                      ID respuesta: <span style="color:#111;">{{ d.id_respuesta ?? '—' }}</span>
                    </div>
                  </div>

                  <div class="row g-2">
                    <div class="col-12 col-md-7">
                      <div class="text-xs text-secondary" style="font-weight:800;">Nombre</div>
                      <div class="text-sm" style="font-weight:900; color:#111; overflow-wrap:anywhere;">
                        {{ d.nombre_persona || '—' }}
                      </div>
                    </div>

                    <div class="col-12 col-md-5">
                      <div class="text-xs text-secondary" style="font-weight:800;">CURP</div>
                      <div class="text-sm" style="font-weight:900; color:#111; overflow-wrap:anywhere;">
                        {{ d.curp || '—' }}
                      </div>
                    </div>

                    <div class="col-12 col-md-7">
                      <div class="text-xs text-secondary" style="font-weight:800;">Correo</div>
                      <div class="text-sm" style="font-weight:800; color:#111; overflow-wrap:anywhere;">
                        {{ d.correo_electronico || '—' }}
                      </div>
                    </div>

                    <div class="col-12 col-md-5">
                      <div class="text-xs text-secondary" style="font-weight:800;">Año</div>
                      <div class="text-sm" style="font-weight:900; color:#111;">
                        {{ d.anio ?? '—' }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Curso -->
              <div class="col-12 col-lg-8">
                <div class="border" style="border-radius:14px; padding:14px; height:100%;">
                  <div class="d-flex align-items-center gap-2 mb-2">
                    <span
                      class="d-inline-flex align-items-center justify-content-center"
                      style="width:34px;height:34px;border-radius:12px;background:rgba(16,49,43,.10);color:#10312B;"
                    >
                      <i class="fa fa-book"></i>
                    </span>
                    <div>
                      <div class="text-sm" style="font-weight:800;color:#10312B;">Curso</div>
                      <div class="text-xs text-muted">Información del curso y periodo</div>
                    </div>
                  </div>

                  <div class="row g-2">
                    <div class="col-12">
                      <div class="text-xs text-secondary" style="font-weight:800;">Nombre del curso</div>
                      <div class="text-sm" style="font-weight:900; color:#111; overflow-wrap:anywhere;">
                        {{ d.nombre_curso || '—' }}
                      </div>
                    </div>

                    <div class="col-12 col-md-6">
                      <div class="text-xs text-secondary" style="font-weight:800;">Instancia</div>
                      <div class="text-sm" style="font-weight:800; color:#111; overflow-wrap:anywhere;">
                        {{ d.instancia || d.instancia_otro || '—' }}
                      </div>
                    </div>

                    <div class="col-6 col-md-3">
                      <div class="text-xs text-secondary" style="font-weight:800;">Fecha inicio</div>
                      <div class="text-sm" style="font-weight:900; color:#111;">
                        {{ d.fecha_inicio || '—' }}
                      </div>
                    </div>

                    <div class="col-6 col-md-3">
                      <div class="text-xs text-secondary" style="font-weight:800;">Fecha fin</div>
                      <div class="text-sm" style="font-weight:900; color:#111;">
                        {{ d.fecha_final || '—' }}
                      </div>
                    </div>

                    <div class="col-6 col-md-3">
                      <div class="text-xs text-secondary" style="font-weight:800;">Horas</div>
                      <div class="text-sm" style="font-weight:900; color:#111;">
                        {{ d.horas_realizadas || '—' }}
                      </div>
                    </div>

                    <div class="col-6 col-md-3">
                      <div class="text-xs text-secondary" style="font-weight:800;">Calificación</div>
                      <div class="text-sm" style="font-weight:900; color:#111;">
                        {{ d.calificacion || '—' }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Evidencia -->
              <div class="col-12 col-lg-4">
                <div class="border" style="border-radius:14px; padding:14px; height:100%;">
                  <div class="d-flex align-items-center gap-2 mb-2">
                    <span
                      class="d-inline-flex align-items-center justify-content-center"
                      style="width:34px;height:34px;border-radius:12px;background:rgba(16,49,43,.10);color:#10312B;"
                    >
                      <i class="fa fa-file-pdf"></i>
                    </span>
                    <div>
                      <div class="text-sm" style="font-weight:800;color:#10312B;">Evidencia</div>
                      <div class="text-xs text-muted">Constancia asociada</div>
                    </div>
                  </div>

                  <div class="text-xs text-muted mb-2" style="line-height:1.25;">
                    Abre el documento para validar que corresponda al registro.
                  </div>

                  <a
                    v-if="d.link_constancia"
                    :href="d.link_constancia"
                    target="_blank"
                    rel="noopener"
                    class="btn btn-sm text-white w-100 d-inline-flex align-items-center justify-content-center gap-2"
                    style="
                      background:#235B4E;
                      border-color:#235B4E;
                      border-radius:12px;
                      padding:10px 12px;
                      box-shadow: 0 10px 18px rgba(16, 49, 43, 0.18);
                      font-weight:800;
                      letter-spacing:.2px;
                    "
                  >
                    <i class="fa fa-eye"></i>
                    Ver constancia
                    <i class="fa fa-external-link-alt ms-1" style="opacity:.9;"></i>
                  </a>

                  <button
                    v-else
                    type="button"
                    class="btn btn-sm w-100"
                    disabled
                    style="
                      background:#E9ECEF;
                      border-color:#DEE2E6;
                      color:#6c757d;
                      border-radius:12px;
                      padding:10px 12px;
                      font-weight:800;
                    "
                  >
                    Constancia no disponible
                  </button>

                  <div class="mt-2 text-xs text-muted">
                    Si el link es incorrecto, se corregirá desde la base de datos.
                  </div>
                </div>
              </div>

              <!-- Información adicional -->
              <div class="col-12">
                <div class="border" style="border-radius:14px; padding:14px;">
                  <div class="d-flex align-items-center gap-2 mb-2">
                    <span
                      class="d-inline-flex align-items-center justify-content-center"
                      style="width:34px;height:34px;border-radius:12px;background:rgba(16,49,43,.10);color:#10312B;"
                    >
                      <i class="fa fa-clipboard-list"></i>
                    </span>
                    <div>
                      <div class="text-sm" style="font-weight:800;color:#10312B;">Información adicional</div>
                      <div class="text-xs text-muted">Pendiente de definición por el equipo</div>
                    </div>
                  </div>

                  <pre class="mb-0" style="white-space:pre-wrap; font-size: 12px; color:#444;">{{ extraPretty }}</pre>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="modal-footer" style="background:#fff; border-top:1px solid #e9ecef;">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">
              Cerrar
            </button>

            <button
              type="button"
              class="btn btn-sm text-white"
              style="background:#8B0000;border-color:#8B0000;border-radius:12px;"
              @click="updateStatus('RECHAZAR')"
            >
              <i class="fa fa-times me-1"></i> Rechazar
            </button>

            <button
              type="button"
              class="btn btn-sm text-white"
              style="background:#235B4E;border-color:#235B4E;border-radius:12px;"
              @click="updateStatus('ACEPTAR')"
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

import { setupTableEvents } from '@helpers/table/table-events.vue'
import { handlePagination } from '@helpers/table/table-pagination.vue'

import tableTittle from '@helpers/table/table-tittle.vue'
import tableFooter from '@helpers/table/table-footer.vue'
import tableSpinner from '@helpers/table/table-spinner.vue'
import tableRow from '@helpers/table/table-row.vue'
import tableEmpty from '@helpers/table/table-empty.vue'
import tableButtonDefault from '@helpers/table/table-button-default.vue'
import inputField from '@helpers/form/input-field.vue'

const gridx3 = ref('col-12 col-md-6 mb-6')

// filtros
const f_curp = ref('')
const f_curso = ref('')
const f_anio = ref('')

// tabla
const item = ref([])
const rowsAll = ref(0)
const row = ref(0)
const currentPage = ref(1)
const limit = ref(5)
const searchTerm = ref('')
const spinnerRef = ref(null)

// modal
const selectedId = ref(null)
const d = ref({})
const extraPretty = ref('Pendiente de definición por el equipo.')

// Badge institucional por estatus
function statusBadgeClass(statusTxt) {
  const s = String(statusTxt || '').toUpperCase().trim()
  if (s === 'ACEPTADA') return 'bg-success'
  if (s === 'RECHAZADA') return 'bg-danger'
  if (s === 'PENDIENTE') return 'bg-secondary'
  return 'bg-dark'
}

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

async function openDetails(id_respuesta) {
  if (!id_respuesta) return
  selectedId.value = id_respuesta
  d.value = {}
  extraPretty.value = 'Pendiente de definición por el equipo.'

  try {
    showSpinner()
    const { data } = await axios.post('/constancias/data', { id_respuesta })

    if (!data.status) {
      notyf.error(data.message ?? 'No se pudo cargar el detalle.')
      return
    }

    d.value = data.data || {}
    extraPretty.value = 'Pendiente de definición por el equipo.'

    $('#modal_constancia_detalles').modal('show')
  } catch (e) {
    notyf.error('No se pudo cargar el detalle. Intenta nuevamente.')
  } finally {
    hideSpinner()
  }
}

async function updateStatus(accion) {
  if (!selectedId.value) return

  try {
    showSpinner()
    const { data } = await axios.post('/constancias/estatus', {
      id_respuesta: selectedId.value,
      accion, // ACEPTAR | RECHAZAR
    })

    if (!data.status) {
      notyf.error(data.message ?? 'No se pudo actualizar el estatus.')
      return
    }

    notyf.success(data.message ?? 'Estatus actualizado.')

    // refrescar tabla
    fetchTableData()

    $('#modal_constancia_detalles').modal('hide')
  } catch (e) {
    notyf.error('Error al actualizar el estatus.')
  } finally {
    hideSpinner()
  }
}
</script>