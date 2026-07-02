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
          <div class="accordion-body bg-light rounded-bottom p-3">
            <div class="row g-2">
              <inputField :grid="gridx2" label="CURP" id="curp" v-model="f_curp" :uppercase="true" />
              <inputField :grid="gridx2" label="Nombre del curso" id="curso" v-model="f_curso" :uppercase="true" />
            </div>

            <div class="row g-2">
              <inputField
                grid="col-12 col-md-6 mb-2"
                type="number"
                label="Año"
                id="anio"
                v-model="f_anio"
              />

              <div class="col-12 col-md-6 mb-2">
                <label class="form-label">Estatus</label>
                <select v-model="f_estatus" class="form-select">
                  <option value="">Todos</option>
                  <option :value="1">Pendiente</option>
                  <option :value="2">Aceptado</option>
                  <option :value="3">Rechazado</option>
                </select>
              </div>
            </div>

            <!-- Filtros administrativos: solo ADMIN -->
            <div v-if="isAdminConstancias" class="row g-2">
              <div class="col-12 col-md-4 mb-2">
                <label class="form-label">Entidad</label>
                <select v-model="f_entidad" class="form-select">
                  <option value="">Todas</option>
                  <option v-for="op in opcionesEntidades" :key="op" :value="op">
                    {{ op }}
                  </option>
                </select>
              </div>

              <div class="col-12 col-md-4 mb-2">
                <label class="form-label">Tipo nómina</label>
                <select v-model="f_tipo_nomina" class="form-select">
                  <option value="">Todos</option>
                  <option v-for="op in opcionesTiposNomina" :key="op" :value="op">
                    {{ op }}
                  </option>
                </select>
              </div>

              <div class="col-12 col-md-4 mb-2">
                <label class="form-label">CLUES</label>
                <select v-model="f_clues" class="form-select">
                  <option value="">Todas</option>
                  <option v-for="op in opcionesClues" :key="op" :value="op">
                    {{ op }}
                  </option>
                </select>
              </div>
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

            <template v-if="isAdminConstancias">
              <tableRow value="Entidad" />
              <tableRow value="Tipo nómina" />
              <tableRow value="CLUES" />
            </template>

            <tableRow value="Estatus" />
          </tr>
        </thead>

        <tbody>
          <tableEmpty v-if="item.length === 0" :colspan="tableColspan" />

          <tr v-for="row in item" :key="row.id_respuesta">
            <td class="text-center" style="width: 90px;">
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

            <td class="align-middle text-center" style="width: 170px;">
              <span class="text-secondary text-xs" style="font-weight:600;">
                {{ row.curp || '—' }}
              </span>
            </td>

            <td class="align-middle text-start" style="min-width: 360px;">
              <span
                class="text-secondary text-xs"
                style="font-weight:600; white-space:normal; overflow-wrap:anywhere;"
              >
                {{ row.nombre_curso || '—' }}
              </span>
            </td>

            <template v-if="isAdminConstancias">
              <td class="align-middle text-center" style="min-width: 180px;">
                <span
                  class="text-secondary text-xs"
                  style="font-weight:600; white-space:normal; overflow-wrap:anywhere;"
                >
                  {{ row.entidad || '—' }}
                </span>
              </td>

              <td class="align-middle text-center" style="width: 130px;">
                <span class="text-secondary text-xs" style="font-weight:600;">
                  {{ row.tipo_nomina || '—' }}
                </span>
              </td>

              <td class="align-middle text-center" style="width: 140px;">
                <span class="text-secondary text-xs" style="font-weight:600;">
                  {{ row.clues || '—' }}
                </span>
              </td>
            </template>

            <td class="align-middle text-center" style="width: 130px;">
              <span class="text-secondary text-xs" style="font-weight:600;">
                {{ row.estatus_txt || '—' }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <tableFooter :row="row" :rowsAll="rowsAll" />

    <!-- MODAL: DETALLES -->
    <div class="modal fade" id="modal_constancia_detalles" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 16px; overflow:hidden;">

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
                        {{ d.horas_realizadas ?? '—' }}
                      </div>
                    </div>

                    <div class="col-6 col-md-3">
                      <div class="text-xs text-secondary" style="font-weight:800;">Calificación</div>
                      <div class="text-sm" style="font-weight:900; color:#111;">
                        {{ formatCalificacion(d.calificacion_n ?? d.calificacion) }}
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

              <!-- Motivo de rechazo -->
              <div
                v-if="isRejectedStatus(d.estatus_txt) && (d.motivo_rechazo_view || d.motivo_rechazo)"
                class="col-12"
              >
                <div
                  class="border"
                  style="border-radius:14px; padding:14px; background:#fff5f5; border-color:#f5c2c7 !important;"
                >
                  <div class="d-flex align-items-center gap-2 mb-2">
                    <span
                      class="d-inline-flex align-items-center justify-content-center"
                      style="width:34px;height:34px;border-radius:12px;background:rgba(139,0,0,.10);color:#8B0000;"
                    >
                      <i class="fa fa-times-circle"></i>
                    </span>
                    <div>
                      <div class="text-sm" style="font-weight:800;color:#8B0000;">Motivo del rechazo</div>
                      <div class="text-xs text-muted">
                        {{ d.fecha_rechazo_view || d.fecha_ultima_accion || 'Sin fecha registrada' }}
                      </div>
                    </div>
                  </div>

                  <div
                    class="text-sm"
                    style="white-space: pre-wrap; color:#111; font-weight:700;"
                  >
                    {{ d.motivo_rechazo_view || d.motivo_rechazo }}
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

            <template v-if="canShowDecisionButtons(d.estatus_txt)">
              <button
                type="button"
                class="btn btn-sm text-white"
                style="background:#8B0000;border-color:#8B0000;border-radius:12px;"
                @click="openRejectModal"
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
            </template>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL: RECHAZO -->
    <div class="modal fade" id="modal_constancia_rechazo" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; overflow:hidden;">
          <div class="modal-header" style="background:#ffffff; border-bottom:1px solid #e9ecef;">
            <h5 class="modal-title" style="font-weight:800; color:#8B0000;">
              Rechazar constancia
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>

          <div class="modal-body" style="background:#ffffff;">
            <div class="mb-2 text-sm text-muted">
              Captura el motivo del rechazo. Este campo es obligatorio y se enviará por correo al usuario.
            </div>

            <label class="form-label fw-bold">
              Motivo del rechazo <span class="text-danger">*</span>
            </label>

            <textarea
              v-model="rejectReason"
              class="form-control"
              rows="5"
              maxlength="2000"
              placeholder="Escribe aquí el motivo del rechazo..."
              :class="{ 'is-invalid': rejectReasonTouched && !rejectReasonValid }"
            ></textarea>

            <div v-if="rejectReasonTouched && !rejectReasonValid" class="invalid-feedback d-block">
              Debes capturar el motivo del rechazo.
            </div>

            <div class="text-end mt-1">
              <small class="text-muted">{{ rejectReason.length }}/2000</small>
            </div>
          </div>

          <div class="modal-footer" style="background:#fff; border-top:1px solid #e9ecef;">
            <button
              type="button"
              class="btn btn-sm btn-outline-secondary"
              data-bs-dismiss="modal"
              @click="resetRejectForm"
            >
              Cancelar
            </button>

            <button
              type="button"
              class="btn btn-sm text-white"
              style="background:#8B0000;border-color:#8B0000;border-radius:12px;"
              @click="confirmReject"
            >
              Confirmar rechazo
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from '@axios'
import Swal from 'sweetalert2'
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

const gridx2 = ref('col-12 col-md-6 mb-2')

// filtros
const f_curp = ref('')
const f_curso = ref('')
const f_anio = ref('')
const f_estatus = ref('')

// filtros administrativos
const f_entidad = ref('')
const f_tipo_nomina = ref('')
const f_clues = ref('')

const isAdminConstancias = ref(false)
const opcionesEntidades = ref([])
const opcionesTiposNomina = ref([])
const opcionesClues = ref([])

const tableColspan = computed(() => isAdminConstancias.value ? 7 : 4)

// tabla
const item = ref([])
const rowsAll = ref(0)
const row = ref(0)
const currentPage = ref(1)
const limit = ref(5)
const searchTerm = ref('')
const spinnerRef = ref(null)

// modal detalle
const selectedId = ref(null)
const d = ref({})
const extraPretty = ref('Pendiente de definición por el equipo.')

// modal rechazo
const rejectReason = ref('')
const rejectReasonTouched = ref(false)
const duplicatePromptOpen = ref(false)

const rejectReasonValid = computed(() => rejectReason.value.trim().length > 0)

function normalizeStatus(statusTxt) {
  return String(statusTxt || '').toUpperCase().trim()
}

function isPendingStatus(statusTxt) {
  const s = normalizeStatus(statusTxt)
  return s === 'PENDIENTE'
}

function isRejectedStatus(statusTxt) {
  const s = normalizeStatus(statusTxt)
  return s === 'RECHAZADO' || s === 'RECHAZADA'
}

function canShowDecisionButtons(statusTxt) {
  return isPendingStatus(statusTxt)
}

function statusBadgeClass(statusTxt) {
  const s = normalizeStatus(statusTxt)
  if (s === 'ACEPTADO' || s === 'ACEPTADA') return 'bg-success'
  if (s === 'RECHAZADO' || s === 'RECHAZADA') return 'bg-danger'
  if (s === 'PENDIENTE') return 'bg-secondary'
  return 'bg-dark'
}

function formatCalificacion(value) {
  if (value === null || value === undefined || value === '') {
    return '—'
  }

  const num = Number(String(value).replace(',', '.'))

  if (!Number.isFinite(num)) {
    return String(value)
  }

  return num.toFixed(2).replace(/\.00$/, '')
}

async function fetchFilterOptions() {
  try {
    const { data } = await axios.post('/constancias/filter-options')

    isAdminConstancias.value = !!data.is_admin

    if (!isAdminConstancias.value) {
      opcionesEntidades.value = []
      opcionesTiposNomina.value = []
      opcionesClues.value = []

      f_entidad.value = ''
      f_tipo_nomina.value = ''
      f_clues.value = ''

      return
    }

    opcionesEntidades.value = data.entidades || []
    opcionesTiposNomina.value = data.tipos_nomina || []
    opcionesClues.value = data.clues || []
  } catch (e) {
    isAdminConstancias.value = false
    opcionesEntidades.value = []
    opcionesTiposNomina.value = []
    opcionesClues.value = []

    f_entidad.value = ''
    f_tipo_nomina.value = ''
    f_clues.value = ''
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
      curp: f_curp.value,
      curso: f_curso.value,
      anio: f_anio.value,
      estatus: f_estatus.value,
      select: parseInt(document.getElementById('footer-filter')?.value || 5),
    }

    if (isAdminConstancias.value) {
      payload.entidad = f_entidad.value
      payload.tipo_nomina = f_tipo_nomina.value
      payload.clues = f_clues.value
    }

    const { data } = await axios.post('/constancias/table', payload)

    item.value = data.list || []
    rowsAll.value = data.allRow || 0
    row.value = data.row || 0

    if (typeof data.is_admin !== 'undefined') {
      isAdminConstancias.value = !!data.is_admin
    }

    const hasAdminFilters = isAdminConstancias.value && (
      f_entidad.value ||
      f_tipo_nomina.value ||
      f_clues.value
    )

    const hasFilters = !!(
      f_curp.value ||
      f_curso.value ||
      f_anio.value ||
      f_estatus.value ||
      searchTerm.value ||
      hasAdminFilters
    )

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

onMounted(async () => {
  await fetchFilterOptions()
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
  f_curp.value = ''
  f_curso.value = ''
  f_anio.value = ''
  f_estatus.value = ''
  f_entidad.value = ''
  f_tipo_nomina.value = ''
  f_clues.value = ''
  fetchTableData()
}

function search_function() {
  currentPage.value = 1
  fetchTableData()
}

function resetRejectForm() {
  rejectReason.value = ''
  rejectReasonTouched.value = false
}

async function loadDetails(id_respuesta, openModal = false) {
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

    const detalle = data.data || {}

    d.value = {
      ...detalle,
      calificacion: detalle.calificacion_n ?? detalle.calificacion ?? null,
    }

    extraPretty.value = 'Pendiente de definición por el equipo.'

    if (openModal) {
      $('#modal_constancia_detalles').modal('show')
    }
  } catch (e) {
    notyf.error('No se pudo cargar el detalle. Intenta nuevamente.')
  } finally {
    hideSpinner()
  }
}

async function openDetails(id_respuesta) {
  await loadDetails(id_respuesta, true)
}

function openRejectModal() {
  resetRejectForm()
  $('#modal_constancia_rechazo').modal('show')
}

async function confirmReject() {
  rejectReasonTouched.value = true

  if (!rejectReasonValid.value) {
    notyf.error('Debes capturar el motivo del rechazo.')
    return
  }

  await updateStatus('RECHAZAR', rejectReason.value.trim())
}

function isTruthy(value) {
  return value === true || value === 1 || value === '1' || value === 'true' || value === 'TRUE'
}

function isDuplicateConfirmationResponse(data) {
  return !!(
    data &&
    isTruthy(data.requires_confirmation) &&
    isTruthy(data.duplicate_concluido)
  )
}

async function handleDuplicateConstanciaConfirmation() {
  if (duplicatePromptOpen.value) return

  duplicatePromptOpen.value = true

  // Muy importante: quitar el overlay global antes de abrir SweetAlert2.
  // Si se deja activo, puede parecer que la ventana tarda o queda bloqueada.
  hideSpinner()

  await new Promise((resolve) => setTimeout(resolve, 80))

  const result = await Swal.fire({
    icon: 'warning',
    title: 'Curso ya concluido',
    html: `
      <div style="text-align:left; font-size:14px; line-height:1.45;">
        <p>
          Este curso ya se encuentra concluido para el trabajador.
        </p>

        <p>
          Si seleccionas <b>Continuar</b>, la información será actualizada
          o se realizará nuevamente el proceso de generación de la constancia.
        </p>

        <p>
          Si seleccionas <b>Cancelar/Rechazar</b>, se enviará automáticamente
          un correo electrónico informando que la constancia ya había sido
          generada y enviada con anterioridad, evitando duplicidades en el proceso.
        </p>
      </div>
    `,
    showCancelButton: true,
    confirmButtonText: 'Continuar',
    cancelButtonText: 'Cancelar/Rechazar',
    reverseButtons: true,
    allowOutsideClick: false,
    allowEscapeKey: false,
    confirmButtonColor: '#235B4E',
    cancelButtonColor: '#8B0000',
    customClass: {
      popup: 'swal2-constancia-duplicada',
      confirmButton: 'swal2-confirm-constancia',
      cancelButton: 'swal2-cancel-constancia',
    },
  })

  duplicatePromptOpen.value = false

  if (result.isConfirmed) {
    await updateStatus('ACEPTAR', '', {
      confirmar_duplicado: true,
    })

    return
  }

  await updateStatus('ACEPTAR', '', {
    rechazar_duplicado: true,
  })
}

async function processSuccessfulStatusResponse(data, accion) {
  if (!data.status) {
    if (isDuplicateConfirmationResponse(data)) {
      hideSpinner()
      await handleDuplicateConstanciaConfirmation()
      return
    }

    notyf.error(data.message ?? 'No se pudo actualizar el estatus.')
    return
  }

  notyf.success(data.message ?? 'Estatus actualizado.')

  await fetchTableData()

  if (accion === 'RECHAZAR') {
    $('#modal_constancia_rechazo').modal('hide')
    resetRejectForm()
    await loadDetails(selectedId.value, false)
    return
  }

  $('#modal_constancia_rechazo').modal('hide')
  resetRejectForm()
  $('#modal_constancia_detalles').modal('hide')
}

async function updateStatus(accion, motivo = '', extraPayload = {}) {
  if (!selectedId.value) return

  try {
    showSpinner()

    const payload = {
      id_respuesta: selectedId.value,
      accion,
      ...extraPayload,
    }

    if (accion === 'RECHAZAR') {
      payload.motivo = motivo
    }

    const { data } = await axios.post('/constancias/estatus', payload)

    await processSuccessfulStatusResponse(data, accion)

  } catch (e) {
    const data = e.response?.data || {}

    if (isDuplicateConfirmationResponse(data)) {
      hideSpinner()
      await handleDuplicateConstanciaConfirmation()
      return
    }

    notyf.error(data.message || data.error || 'Error al actualizar el estatus.')
  } finally {
    hideSpinner()
  }
}
</script>