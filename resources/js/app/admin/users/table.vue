<template>
  <div>
    <!-- Título + buscador -->
    <tableTittleSearch value="Usuarios (Administrador)" />

    <!-- Acciones superiores -->
    <div class="d-flex justify-content-end mb-2">
      <tableButtonDefault
        color="#10312B"
        icon="fa fa-user-plus"
        label="Nuevo usuario"
        modalToggle="modal"
        modalTarget="#modal_create_user"
      />
    </div>

    <!-- Spinner tabla -->
    <tableSpinner ref="spinnerRef" />

    <div class="table-responsive">
      <table class="table align-items-center mb-0" id="table-default">
        <thead>
          <tr>
            <tableRow value="Acciones" />
            <tableRow value="ID" />
            <tableRow value="Nombre" />
            <tableRow value="Correo" />
            <tableRow value="Admin" />
            <tableRow value="Creado" />
          </tr>
        </thead>

        <tbody>
          <tableEmpty v-if="item.length === 0" :colspan="6" />

          <tr v-for="row in item" :key="row.id">
            <td class="text-center">
              <div class="button-container">
                <!-- Editar (de momento lo dejo en # para no reventar si no tienes ruta edit) -->
                <tableButtonEdit
                  href="#"
                  icon="fa fa-edit"
                  label="Editar"
                  bgColor="#10312B"
                />

                <!-- Eliminar -->
                <tableButtonDefault
                  color="#8B0000"
                  icon="fa fa-trash"
                  label="Eliminar"
                  @click="deleteUser"
                  :clickEventPayload="row.id"
                />
              </div>
            </td>

            <td class="align-middle text-center">
              <span class="text-secondary text-xs font-weight-bold">{{ row.id }}</span>
            </td>

            <td class="align-middle text-center">
              <span class="text-secondary text-xs font-weight-bold">{{ row.name }}</span>
            </td>

            <td class="align-middle text-center">
              <span class="text-secondary text-xs font-weight-bold">{{ row.email }}</span>
            </td>

            <td class="align-middle text-center">
              <span class="text-secondary text-xs font-weight-bold">
                {{ row.is_admin ? 'SÍ' : 'NO' }}
              </span>
            </td>

            <td class="align-middle text-center">
              <span class="text-secondary text-xs font-weight-bold">{{ row.created_at }}</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <tableFooter :row="row" :rowsAll="rowsAll" />

    <!-- MODAL: CREAR USUARIO -->
    <modalTemplate
      modalId="modal_create_user"
      title="Crear usuario"
      :onConfirm="confirmCreateUser"
      size="md"
    >
      <form role="form" id="form_create_user">
        <div class="row">
          <inputField
            grid="col-12"
            label="Nombre"
            id="name"
            v-model="m_name"
            :uppercase="true"
          />
        </div>

        <div class="row" style="margin-top: -20px !important;">
          <inputField
            grid="col-12"
            label="Correo"
            id="email"
            v-model="m_email"
          />
        </div>

        <div class="row" style="margin-top: -20px !important;">
          <inputField
            grid="col-12"
            label="Contraseña"
            id="password"
            v-model="m_password"
          />
        </div>

        <div class="row" style="margin-top: -20px !important;">
          <inputField
            grid="col-12"
            label="Confirmar contraseña"
            id="password_confirmation"
            v-model="m_password_confirmation"
          />
        </div>

        <div class="row mt-2">
          <inputCheckbox
            v-model="m_is_admin"
            :label="'¿Es administrador?'"
            :id="'is_admin'"
          />
        </div>
      </form>
    </modalTemplate>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { notyf } from '@components/notyf.js'
import { showSpinner, hideSpinner } from '@components/spinner.js'
import { clearErrors } from '@components/clearErrors.js'
import { handleErrors } from '@components/handleErrors.js'

// Helpers tabla
import { setupTableEvents } from '@helpers/table/table-events.vue'
import { handlePagination } from '@helpers/table/table-pagination.vue'
import tableButtonEdit from '@helpers/table/table-button-edit.vue'
import tableButtonDefault from '@helpers/table/table-button-default.vue'

// Componentes tabla
import tableTittleSearch from '@helpers/table/table-tittle-search.vue'
import tableFooter from '@helpers/table/table-footer.vue'
import tableSpinner from '@helpers/table/table-spinner.vue'
import tableRow from '@helpers/table/table-row.vue'
import tableEmpty from '@helpers/table/table-empty.vue'

// Form/Modal
import modalTemplate from '@helpers/modal/modal-template.vue'
import inputField from '@helpers/form/input-field.vue'
import inputCheckbox from '@helpers/form/input-checkbox.vue'

// Axios
import axios from '@axios'

// Estado tabla
const item = ref([])
const rowsAll = ref(0)
const row = ref(0)
const currentPage = ref(1)
const limit = ref(5)
const searchTerm = ref('')
const spinnerRef = ref(null)

// Modal create user
const m_name = ref('')
const m_email = ref('')
const m_password = ref('')
const m_password_confirmation = ref('')
const m_is_admin = ref(false)

// Cargar datos
const fetchTableData = async () => {
  const MIN_SPINNER_DURATION = 1000
  const startTime = Date.now()

  spinnerRef.value?.show()

  const offset = (currentPage.value - 1) * limit.value

  try {
    // ✅ AQUI ESTABA EL ERROR: antes era /admin/users/table
    const { data } = await axios.post('/usuarios/table', {
      limit: limit.value,
      offset,
      search: searchTerm.value,
      select: parseInt(document.getElementById('footer-filter')?.value || 5),
    })

    item.value = data.list || []
    rowsAll.value = data.allRow || 0
    row.value = data.row || 0
  } catch (error) {
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

// Crear usuario
async function confirmCreateUser() {
  try {
    showSpinner()
    clearErrors()

    const payload = {
      name: m_name.value,
      email: m_email.value,
      password: m_password.value,
      password_confirmation: m_password_confirmation.value,
      is_admin: m_is_admin.value ? 1 : 0,
    }

    // ✅ AQUI ESTABA EL ERROR: antes era /admin/users/save
    const response = await axios.post('/usuarios/save', payload)

    if (!response.data.status) {
      notyf.error(response.data.message ?? 'No se pudo completar la acción.')
      return
    }

    $('#modal_create_user').modal('hide')
    notyf.success(response.data.message ?? 'Usuario creado correctamente.')

    // reset modal
    m_name.value = ''
    m_email.value = ''
    m_password.value = ''
    m_password_confirmation.value = ''
    m_is_admin.value = false

    fetchTableData()
  } catch (error) {
    clearErrors()

    if (error.response?.status === 419) {
      notyf.error('Sesión/CSRF inválido. Recarga la página e intenta de nuevo.')
      return
    }

    if (error.response?.data?.errors) {
      handleErrors(error.response.data.errors)
      return
    }

    notyf.error(error.response?.data?.message ?? 'No se pudo completar la acción. Por favor, vuelve a intentarlo.')
  } finally {
    hideSpinner()
  }
}

// Eliminar
async function deleteUser(id) {
  if (!id) return
  const ok = confirm('¿Seguro que deseas eliminar este usuario?')
  if (!ok) return

  try {
    showSpinner()

    // ✅ AQUI ESTABA EL ERROR: antes era /admin/users/delete
    const { data } = await axios.post('/usuarios/delete', { id })

    if (!data.status) {
      notyf.error(data.message ?? 'No se pudo eliminar el usuario.')
      return
    }

    notyf.success(data.message ?? 'Usuario eliminado.')
    fetchTableData()
  } catch (error) {
    notyf.error('No se pudo completar la acción. Por favor, vuelve a intentarlo.')
  } finally {
    hideSpinner()
  }
}
</script>
