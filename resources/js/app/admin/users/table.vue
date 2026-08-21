<template>
  <div class="admin-users-module">
    <div class="users-head">
      <div>
        <h5 class="card-title mb-1">Administración de usuarios</h5>
        <div class="users-head-meta">
          <span>{{ rowsAll }} registros</span>
          <span>{{ activeCount }} activos en la página</span>
          <span>{{ inactiveCount }} inactivos en la página</span>
        </div>
      </div>

      <button
        type="button"
        class="btn btn-sm cap-btn-primary users-main-action"
        @click="openCreateModal"
      >
        <i class="fa fa-user-plus"></i>
        <span>Nuevo usuario</span>
      </button>
    </div>

    <div class="users-filter-bar">
      <div class="users-search-wrap">
        <i class="fa fa-search"></i>
        <input
          id="table-search"
          v-model="searchTerm"
          type="text"
          class="form-control"
          placeholder="Buscar"
          autocomplete="off"
        />
      </div>

      <select v-model="filters.status" class="form-select users-filter-select" @change="applyFilters">
        <option value="">Estatus</option>
        <option value="1">Activo</option>
        <option value="0">Inactivo</option>
      </select>

      <select v-model="filters.role_id" class="form-select users-filter-select" @change="applyFilters">
        <option value="">Rol</option>
        <option v-for="role in roles" :key="role.id" :value="role.id">
          {{ role.name }}
        </option>
      </select>

      <select v-model="filters.id_entidad" class="form-select users-filter-select" @change="applyFilters">
        <option value="">Entidad</option>
        <option v-for="entidad in entidades" :key="entidad.id" :value="entidad.id">
          {{ entidad.descripcion }}
        </option>
      </select>

      <select v-model="filters.id_tipo_nomina" class="form-select users-filter-select" @change="applyFilters">
        <option value="">Tipo nómina</option>
        <option v-for="tipo in tiposNomina" :key="tipo.id" :value="tipo.id">
          {{ tipo.descripcion }}
        </option>
      </select>

      <select v-model="filters.id_clues" class="form-select users-filter-select" @change="applyFilters">
        <option value="">CLUES</option>
        <option v-for="clue in clues" :key="clue.id" :value="clue.id">
          {{ clue.descripcion }}
        </option>
      </select>

      <button
        type="button"
        class="btn btn-sm users-icon-action"
        aria-label="Limpiar filtros"
        title="Limpiar filtros"
        @click="clearFilters"
      >
        <i class="fa fa-brush"></i>
      </button>
    </div>

    <tableSpinner ref="spinnerRef" />

    <div class="table-responsive">
      <table class="table align-items-center mb-0" id="table-default">
        <thead>
          <tr>
            <tableRow value="Acciones" />
            <tableRow value="Usuario" />
            <tableRow value="Roles" />
            <tableRow value="Alcance" />
            <tableRow value="Estatus" />
            <tableRow value="Actualizado" />
          </tr>
        </thead>

        <tbody>
          <tableEmpty v-if="item.length === 0" :colspan="6" />

          <tr v-for="row in item" :key="row.id">
            <td class="text-center users-actions-cell">
              <div class="button-container">
                <button
                  type="button"
                  class="icon-button cap-icon-button users-table-button users-edit-button"
                  aria-label="Editar"
                  title="Editar"
                  @click="openEditModal(row)"
                >
                  <i class="fa fa-edit"></i>
                  <span class="custom-tooltip">Editar</span>
                </button>

                <button
                  type="button"
                  class="icon-button cap-icon-button users-table-button"
                  :class="row.status ? 'users-disable-button' : 'users-enable-button'"
                  :aria-label="row.status ? 'Desactivar' : 'Activar'"
                  :title="row.status ? 'Desactivar' : 'Activar'"
                  @click="toggleUserStatus(row)"
                >
                  <i :class="row.status ? 'fa fa-user-slash' : 'fa fa-user-check'"></i>
                  <span class="custom-tooltip">{{ row.status ? 'Desactivar' : 'Activar' }}</span>
                </button>
              </div>
            </td>

            <td class="align-middle users-user-cell">
              <div class="users-name">{{ row.name }}</div>
              <div class="users-email">{{ row.email }}</div>
              <div class="users-id">ID {{ row.id }}</div>
            </td>

            <td class="align-middle text-center users-roles-cell">
              <div v-if="roleNames(row).length" class="users-role-list">
                <span v-for="role in roleNames(row)" :key="`${row.id}-${role}`" class="users-role-chip">
                  {{ role }}
                </span>
              </div>
              <span v-else class="users-muted">Sin rol</span>
            </td>

            <td class="align-middle users-scope-cell">
              <div class="users-scope-line">
                <i class="fa fa-map-marker-alt"></i>
                <span>{{ row.entidad_nombre || 'Sin entidad' }}</span>
              </div>
              <div class="users-scope-line">
                <i class="fa fa-id-card"></i>
                <span>{{ nominaLabel(row) }}</span>
              </div>
              <div class="users-scope-line">
                <i class="fa fa-hospital"></i>
                <span>{{ row.clues_codigo || 'Sin CLUES' }}</span>
              </div>
            </td>

            <td class="align-middle text-center">
              <span class="users-status-pill" :class="row.status ? 'is-active' : 'is-inactive'">
                {{ row.status ? 'Activo' : 'Inactivo' }}
              </span>
            </td>

            <td class="align-middle text-center users-date-cell">
              <div>{{ row.updated_at || row.created_at || '-' }}</div>
              <small>Creado {{ row.created_at || '-' }}</small>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <tableFooter :row="row" :rowsAll="rowsAll" />

    <div class="modal fade" id="modal_admin_user" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content users-modal">
          <div class="modal-header users-modal-header">
            <div class="users-modal-title-wrap">
              <span class="users-modal-icon">
                <i :class="formMode === 'create' ? 'fa fa-user-plus' : 'fa fa-user-edit'"></i>
              </span>
              <div>
                <h5 class="modal-title users-modal-title">
                  {{ formMode === 'create' ? 'Nuevo usuario' : 'Editar usuario' }}
                </h5>
                <p class="users-modal-subtitle">
                  {{ form.email || 'Cuenta de acceso' }}
                </p>
              </div>
            </div>

            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>

          <div class="modal-body users-modal-body">
            <form id="form_admin_user" autocomplete="off" @submit.prevent="submitUser">
              <input type="hidden" name="id" :value="form.id" />

              <div class="row g-3">
                <div class="col-12">
                  <div class="users-section-title">
                    <i class="fa fa-user"></i>
                    <span>Cuenta</span>
                  </div>
                </div>

                <inputField
                  grid="col-12 col-lg-6"
                  label="Nombre"
                  id="name"
                  v-model="form.name"
                  :uppercase="true"
                  :required="true"
                  autocomplete="off"
                />

                <inputField
                  grid="col-12 col-lg-6"
                  label="Correo"
                  id="email"
                  v-model="form.email"
                  type="email"
                  :required="true"
                  autocomplete="off"
                />

                <inputField
                  grid="col-12 col-lg-6"
                  :label="passwordLabel"
                  id="password"
                  v-model="form.password"
                  type="password"
                  :required="formMode === 'create'"
                  autocomplete="new-password"
                />

                <inputField
                  grid="col-12 col-lg-6"
                  label="Confirmar contraseña"
                  id="password_confirmation"
                  v-model="form.password_confirmation"
                  type="password"
                  :required="formMode === 'create'"
                  autocomplete="new-password"
                />

                <div class="col-12 col-lg-4">
                  <label class="form-label required">Estatus</label>
                  <select v-model="form.status" name="status" class="form-select" required>
                    <option :value="1">Activo</option>
                    <option :value="0">Inactivo</option>
                  </select>
                  <div id="error-status" class="text-danger text-error mt-1"></div>
                </div>

                <div class="col-12">
                  <div class="users-section-title">
                    <i class="fa fa-users-cog"></i>
                    <span>Roles y permisos</span>
                  </div>
                </div>

                <div class="col-12">
                  <div class="users-role-picker">
                    <label
                      v-for="role in roles"
                      :key="role.id"
                      class="users-role-option"
                      :class="{ selected: form.role_ids.includes(role.id) }"
                    >
                      <input
                        v-model="form.role_ids"
                        type="checkbox"
                        name="role_ids"
                        :value="role.id"
                      />
                      <span class="users-role-option-icon">
                        <i :class="roleIcon(role)"></i>
                      </span>
                      <span>
                        <strong>{{ role.name }}</strong>
                        <small>{{ role.code }}</small>
                      </span>
                    </label>
                  </div>
                  <div id="error-role_ids" class="text-danger text-error mt-1"></div>
                </div>

                <div class="col-12">
                  <div class="users-section-title">
                    <i class="fa fa-map-marked-alt"></i>
                    <span>Alcance operativo</span>
                  </div>
                </div>

                <div class="col-12 col-lg-4">
                  <label class="form-label">Entidad</label>
                  <select v-model="form.id_entidad" name="id_entidad" class="form-select">
                    <option value="">Sin entidad</option>
                    <option v-for="entidad in entidades" :key="entidad.id" :value="entidad.id">
                      {{ entidad.descripcion }}
                    </option>
                  </select>
                  <div id="error-id_entidad" class="text-danger text-error mt-1"></div>
                </div>

                <div class="col-12 col-lg-4">
                  <label class="form-label">Tipo nómina</label>
                  <select v-model="form.id_tipo_nomina" name="id_tipo_nomina" class="form-select">
                    <option value="">Sin tipo</option>
                    <option v-for="tipo in tiposNomina" :key="tipo.id" :value="tipo.id">
                      {{ tipo.descripcion }}
                    </option>
                  </select>
                  <div id="error-id_tipo_nomina" class="text-danger text-error mt-1"></div>
                </div>

                <div class="col-12 col-lg-4">
                  <label class="form-label">CLUES</label>
                  <select v-model="form.id_clues" name="id_clues" class="form-select">
                    <option value="">Sin CLUES</option>
                    <option v-for="clue in clues" :key="clue.id" :value="clue.id">
                      {{ clue.descripcion }}
                    </option>
                  </select>
                  <div id="error-id_clues" class="text-danger text-error mt-1"></div>
                </div>
              </div>
            </form>
          </div>

          <div class="modal-footer users-modal-footer">
            <button type="button" class="btn btn-sm cap-btn-outline" data-bs-dismiss="modal">
              Cancelar
            </button>
            <button type="button" class="btn btn-sm cap-btn-primary" @click="submitUser">
              <i class="fa fa-save"></i>
              <span>Guardar</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import Swal from 'sweetalert2'
import axios from '@axios'
import { notyf } from '@components/notyf.js'
import { showSpinner, hideSpinner } from '@components/spinner.js'
import { clearErrors } from '@components/clearErrors.js'
import { handleErrors } from '@components/handleErrors.js'
import { setupTableEvents } from '@helpers/table/table-events.vue'
import { handlePagination } from '@helpers/table/table-pagination.vue'
import tableFooter from '@helpers/table/table-footer.vue'
import tableSpinner from '@helpers/table/table-spinner.vue'
import tableRow from '@helpers/table/table-row.vue'
import tableEmpty from '@helpers/table/table-empty.vue'
import inputField from '@helpers/form/input-field.vue'

const item = ref([])
const rowsAll = ref(0)
const row = ref(0)
const currentPage = ref(1)
const limit = ref(5)
const searchTerm = ref('')
const spinnerRef = ref(null)
const formMode = ref('create')

const roles = ref([])
const entidades = ref([])
const tiposNomina = ref([])
const clues = ref([])

const filters = reactive({
  status: '',
  role_id: '',
  id_entidad: '',
  id_tipo_nomina: '',
  id_clues: '',
})

const blankForm = () => ({
  id: null,
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  status: 1,
  role_ids: [],
  id_entidad: '',
  id_tipo_nomina: '',
  id_clues: '',
})

const form = reactive(blankForm())

const activeCount = computed(() => item.value.filter((user) => !!user.status).length)
const inactiveCount = computed(() => item.value.filter((user) => !user.status).length)
const passwordLabel = computed(() => formMode.value === 'create' ? 'Contraseña' : 'Nueva contraseña')

onMounted(async () => {
  await fetchOptions()
  await fetchTableData()

  setupTableEvents({
    fetchTableData,
    searchTerm,
    currentPage,
    limit,
    handlePagination,
  })
})

async function fetchOptions() {
  try {
    const { data } = await axios.post('/usuarios/options')

    roles.value = data.roles || []
    entidades.value = data.entidades || []
    tiposNomina.value = data.tipos_nomina || []
    clues.value = data.clues || []
  } catch (error) {
    notyf.error('No se pudieron cargar las opciones del módulo.')
  }
}

async function fetchTableData() {
  const MIN_SPINNER_DURATION = 350
  const startTime = Date.now()
  const offset = (currentPage.value - 1) * limit.value

  spinnerRef.value?.show()

  try {
    const { data } = await axios.post('/usuarios/table', {
      limit: limit.value,
      offset,
      search: searchTerm.value,
      status: filters.status,
      role_id: filters.role_id,
      id_entidad: filters.id_entidad,
      id_tipo_nomina: filters.id_tipo_nomina,
      id_clues: filters.id_clues,
      select: parseInt(document.getElementById('footer-filter')?.value || 5),
    })

    item.value = data.list || []
    rowsAll.value = data.allRow || 0
    row.value = data.row || 0
  } catch (error) {
    notyf.error('No se pudo cargar la tabla de usuarios.')
  } finally {
    const elapsed = Date.now() - startTime
    const delay = elapsed < MIN_SPINNER_DURATION ? MIN_SPINNER_DURATION - elapsed : 0
    setTimeout(() => spinnerRef.value?.hide(), delay)
  }
}

function applyFilters() {
  currentPage.value = 1
  fetchTableData()
}

function clearFilters() {
  filters.status = ''
  filters.role_id = ''
  filters.id_entidad = ''
  filters.id_tipo_nomina = ''
  filters.id_clues = ''
  searchTerm.value = ''

  const searchInput = document.getElementById('table-search')
  if (searchInput) {
    searchInput.value = ''
  }

  applyFilters()
}

function resetForm() {
  Object.assign(form, blankForm())
  clearErrors()
}

function openCreateModal() {
  formMode.value = 'create'
  resetForm()
  $('#modal_admin_user').modal('show')
}

function openEditModal(row) {
  formMode.value = 'edit'
  resetForm()

  const selectedRoleIds = roles.value
    .filter((role) => (row.role_codes || []).includes(role.code))
    .map((role) => role.id)

  Object.assign(form, {
    id: row.id,
    name: row.name || '',
    email: row.email || '',
    password: '',
    password_confirmation: '',
    status: row.status ? 1 : 0,
    role_ids: selectedRoleIds,
    id_entidad: row.id_entidad || '',
    id_tipo_nomina: row.id_tipo_nomina || '',
    id_clues: row.id_clues || '',
  })

  $('#modal_admin_user').modal('show')
}

async function submitUser() {
  try {
    showSpinner()
    clearErrors()

    const payload = normalizePayload()
    const endpoint = formMode.value === 'create' ? '/usuarios/save' : '/usuarios/update'
    const { data } = await axios.post(endpoint, payload)

    if (!data.status) {
      notyf.error(data.message || 'No se pudo guardar el usuario.')
      return
    }

    $('#modal_admin_user').modal('hide')
    notyf.success(data.message || 'Usuario guardado correctamente.')
    await fetchTableData()
  } catch (error) {
    clearErrors()

    if (error.response?.data?.errors) {
      handleErrors(error.response.data.errors)
      return
    }

    notyf.error(error.response?.data?.message || 'No se pudo completar la acción.')
  } finally {
    hideSpinner()
  }
}

function normalizePayload() {
  const payload = {
    id: form.id,
    name: form.name,
    email: form.email,
    password: form.password,
    password_confirmation: form.password_confirmation,
    status: form.status,
    role_ids: form.role_ids.map((id) => Number(id)),
    id_entidad: normalizeNullableNumber(form.id_entidad),
    id_tipo_nomina: normalizeNullableNumber(form.id_tipo_nomina),
    id_clues: normalizeNullableNumber(form.id_clues),
  }

  if (formMode.value === 'create') {
    delete payload.id
  }

  return payload
}

function normalizeNullableNumber(value) {
  if (value === '' || value === null || typeof value === 'undefined') {
    return null
  }

  return Number(value)
}

async function toggleUserStatus(row) {
  const nextStatus = row.status ? 0 : 1
  const action = nextStatus ? 'activar' : 'desactivar'

  const result = await Swal.fire({
    icon: nextStatus ? 'question' : 'warning',
    title: nextStatus ? 'Activar usuario' : 'Desactivar usuario',
    text: `¿Deseas ${action} la cuenta de ${row.name}?`,
    showCancelButton: true,
    confirmButtonText: nextStatus ? 'Activar' : 'Desactivar',
    cancelButtonText: 'Cancelar',
    reverseButtons: true,
    confirmButtonColor: nextStatus ? '#235B4E' : '#8B0000',
    cancelButtonColor: '#6c757d',
  })

  if (!result.isConfirmed) return

  try {
    showSpinner()

    const { data } = await axios.post('/usuarios/toggle-status', {
      id: row.id,
      status: nextStatus,
    })

    if (!data.status) {
      notyf.error(data.message || 'No se pudo cambiar el estatus.')
      return
    }

    notyf.success(data.message || 'Estatus actualizado.')
    await fetchTableData()
  } catch (error) {
    notyf.error(error.response?.data?.message || 'No se pudo completar la acción.')
  } finally {
    hideSpinner()
  }
}

function roleNames(row) {
  return String(row.roles || '')
    .split(',')
    .map((role) => role.trim())
    .filter(Boolean)
}

function nominaLabel(row) {
  const code = String(row.tipo_nomina_codigo || '').trim()
  const name = String(row.tipo_nomina_nombre || '').trim()

  if (code && name) {
    return `${code} - ${name}`
  }

  return code || name || 'Sin tipo nómina'
}

function roleIcon(role) {
  const code = String(role.code || '').toUpperCase()

  if (code.includes('ADMIN')) return 'fa fa-shield-alt'
  if (code.includes('SUPERVISOR')) return 'fa fa-user-tie'
  if (code.includes('REVISOR')) return 'fa fa-user-check'

  return 'fa fa-user-tag'
}
</script>

<style scoped>
.admin-users-module {
  width: 100%;
}

.users-head {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.users-head-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.45rem;
  color: #667085;
  font-size: 0.75rem;
  font-weight: 750;
}

.users-head-meta span {
  display: inline-flex;
  align-items: center;
  min-height: 1.55rem;
  padding: 0.25rem 0.55rem;
  border-radius: 0.45rem;
  background: #f6f8f7;
  border: 1px solid #e5ebe8;
}

.users-main-action {
  display: inline-flex;
  gap: 0.45rem;
  align-items: center;
  white-space: nowrap;
}

.users-filter-bar {
  display: grid;
  grid-template-columns: minmax(16rem, 1.5fr) repeat(5, minmax(9rem, 1fr)) 2.4rem;
  gap: 0.55rem;
  align-items: center;
  margin-bottom: 1rem;
}

.users-search-wrap {
  position: relative;
  min-width: 0;
}

.users-search-wrap i {
  position: absolute;
  left: 0.82rem;
  top: 50%;
  transform: translateY(-50%);
  color: #667085;
  font-size: 0.78rem;
  pointer-events: none;
}

.users-search-wrap .form-control {
  padding-left: 2.15rem;
}

.users-filter-select,
.users-search-wrap .form-control {
  height: 2.35rem;
  border-radius: 0.5rem;
  font-size: 0.82rem;
}

.users-icon-action {
  width: 2.35rem;
  height: 2.35rem;
  padding: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 0.5rem;
  border: 1px solid #d7dedb;
  color: #667085;
  background: #ffffff;
}

.users-icon-action:hover {
  color: #235b4e;
  border-color: #b9cbc4;
  background: #f6f8f7;
}

.users-actions-cell {
  width: 6.5rem;
}

.users-table-button {
  width: 2.1rem;
  height: 2.1rem;
  min-width: 2.1rem;
}

.users-edit-button {
  background: #235b4e;
}

.users-disable-button {
  background: #8b0000;
}

.users-enable-button {
  background: #bc955c;
}

.users-table-button i {
  color: #ffffff;
}

.users-user-cell {
  min-width: 15rem;
}

.users-name {
  color: #111827;
  font-size: 0.82rem;
  font-weight: 850;
  line-height: 1.3;
  overflow-wrap: anywhere;
}

.users-email,
.users-id,
.users-muted {
  color: #667085;
  font-size: 0.72rem;
  font-weight: 700;
  line-height: 1.35;
  overflow-wrap: anywhere;
}

.users-roles-cell {
  min-width: 13rem;
}

.users-role-list {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 0.32rem;
}

.users-role-chip {
  display: inline-flex;
  align-items: center;
  max-width: 11rem;
  min-height: 1.45rem;
  padding: 0.24rem 0.5rem;
  border-radius: 0.45rem;
  background: rgba(35, 91, 78, 0.09);
  color: #235b4e;
  font-size: 0.7rem;
  font-weight: 850;
  line-height: 1.15;
  overflow: hidden;
  text-overflow: ellipsis;
}

.users-scope-cell {
  min-width: 14rem;
}

.users-scope-line {
  display: grid;
  grid-template-columns: 1rem minmax(0, 1fr);
  gap: 0.35rem;
  align-items: center;
  color: #475467;
  font-size: 0.74rem;
  font-weight: 750;
  line-height: 1.35;
}

.users-scope-line i {
  color: #9aa5b1;
  font-size: 0.7rem;
}

.users-scope-line span {
  overflow-wrap: anywhere;
}

.users-status-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 4.8rem;
  min-height: 1.6rem;
  padding: 0.3rem 0.58rem;
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 850;
  line-height: 1;
}

.users-status-pill.is-active {
  color: #235b4e;
  background: rgba(35, 91, 78, 0.1);
  border: 1px solid rgba(35, 91, 78, 0.18);
}

.users-status-pill.is-inactive {
  color: #8b0000;
  background: rgba(139, 0, 0, 0.08);
  border: 1px solid rgba(139, 0, 0, 0.14);
}

.users-date-cell {
  min-width: 9rem;
  color: #475467;
  font-size: 0.74rem;
  font-weight: 750;
}

.users-date-cell small {
  display: block;
  color: #98a2b3;
  font-size: 0.68rem;
  font-weight: 700;
}

.users-modal {
  border-radius: 1rem;
  overflow: hidden;
}

.users-modal-header {
  align-items: flex-start;
  background: #ffffff;
  border-bottom: 1px solid #e9ecef;
}

.users-modal-title-wrap {
  display: flex;
  gap: 0.85rem;
  align-items: center;
  min-width: 0;
}

.users-modal-icon {
  width: 2.55rem;
  height: 2.55rem;
  min-width: 2.55rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 0.75rem;
  color: #235b4e;
  background: rgba(35, 91, 78, 0.1);
}

.users-modal-title {
  color: #10312b;
  font-size: 1.05rem;
  font-weight: 850;
  line-height: 1.2;
  margin: 0;
}

.users-modal-subtitle {
  color: #667085;
  font-size: 0.78rem;
  font-weight: 700;
  line-height: 1.25;
  margin: 0.18rem 0 0 0;
  overflow-wrap: anywhere;
}

.users-modal-body {
  background: #ffffff;
}

.users-modal-footer {
  background: #ffffff;
  border-top: 1px solid #e9ecef;
}

.users-section-title {
  display: flex;
  gap: 0.45rem;
  align-items: center;
  color: #10312b;
  font-size: 0.78rem;
  font-weight: 900;
  text-transform: uppercase;
  margin-top: 0.15rem;
  padding-bottom: 0.35rem;
  border-bottom: 1px solid #eef2f0;
}

.users-section-title i {
  color: #235b4e;
}

.users-role-picker {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 0.65rem;
}

.users-role-option {
  display: grid;
  grid-template-columns: auto 2rem minmax(0, 1fr);
  gap: 0.55rem;
  align-items: center;
  min-height: 4.1rem;
  padding: 0.72rem;
  border: 1px solid #d7dedb;
  border-radius: 0.65rem;
  background: #ffffff;
  cursor: pointer;
  transition: background 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
}

.users-role-option:hover,
.users-role-option.selected {
  background: #f6f8f7;
  border-color: rgba(35, 91, 78, 0.35);
  box-shadow: 0 0.4rem 1rem rgba(16, 49, 43, 0.06);
}

.users-role-option input {
  width: 1rem;
  height: 1rem;
  accent-color: #235b4e;
}

.users-role-option-icon {
  width: 2rem;
  height: 2rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 0.55rem;
  color: #235b4e;
  background: rgba(35, 91, 78, 0.09);
}

.users-role-option strong,
.users-role-option small {
  display: block;
  line-height: 1.2;
  overflow-wrap: anywhere;
}

.users-role-option strong {
  color: #111827;
  font-size: 0.78rem;
  font-weight: 850;
}

.users-role-option small {
  color: #667085;
  font-size: 0.68rem;
  font-weight: 750;
  margin-top: 0.16rem;
}

@media (max-width: 1400px) {
  .users-filter-bar {
    grid-template-columns: minmax(14rem, 1fr) repeat(2, minmax(9rem, 1fr)) 2.4rem;
  }

  .users-role-picker {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (max-width: 992px) {
  .users-head {
    align-items: stretch;
    flex-direction: column;
  }

  .users-filter-bar {
    grid-template-columns: 1fr 1fr;
  }

  .users-icon-action {
    width: 100%;
  }

  .users-role-picker {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 576px) {
  .users-filter-bar,
  .users-role-picker {
    grid-template-columns: 1fr;
  }

  .users-main-action {
    justify-content: center;
    width: 100%;
  }
}
</style>
