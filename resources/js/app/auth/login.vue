<template>
  <div style="margin:0; padding:0;">
    <main class="main-content" style="padding:0; margin:0;">
      <section style="padding:0; margin:0;">
        <div
          class="page-header d-flex justify-content-center align-items-center"
          style="padding:0; margin:0; min-height:100vh; overflow:visible;"
        >
          <div class="col-xl-3 col-lg-4 col-md-5 col-sm-8 col-10" style="padding:0;">
            <div
              class="card card-plain"
              style="
                background-color: transparent !important;
                box-shadow: 0 12px 30px rgba(0, 0, 0, 0.4) !important;
                border-radius: 12px !important;
              "
            >
              <div class="card-header pb-0 text-left bg-transparent">
                <h3 class="font-weight-bolder text-info text-gradient text-center">
                  Validación de Plantillas PAC
                </h3>
                <p class="mb-0 text-center">
                  Introduce tu correo y contraseña para iniciar sesión.
                </p>
              </div>

              <div class="card-body">
                <form id="data_form" @submit.prevent="sendData">
                  <label for="email">Correo electrónico</label>
                  <div class="mb-3">
                    <input
                      type="email"
                      v-model="email"
                      class="form-control"
                      placeholder="Correo electrónico"
                      id="email"
                      name="email"
                      autocomplete="username"
                    >
                    <div id="error-email" class="text-danger text-error" style="margin-top: 5px;"></div>
                  </div>

                  <label for="password">Contraseña</label>
                  <div class="mb-3">
                    <div class="input-group">
                      <input
                        :type="showPassword ? 'text' : 'password'"
                        v-model="password"
                        class="form-control"
                        placeholder="Contraseña"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                      >
                      <button
                        type="button"
                        class="btn btn-outline-secondary mb-0"
                        @click="togglePassword"
                        :title="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                      >
                        <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                      </button>
                    </div>
                    <div id="error-password" class="text-danger text-error" style="margin-top: 5px;"></div>
                  </div>

                  <label for="captcha">Captcha</label>

                  <div class="mb-2 text-center">
                    <img
                      v-if="captchaSrc"
                      :src="captchaSrc"
                      alt="Captcha"
                      style="height:80px; border-radius:8px; border:1px solid #dcdcdc; max-width:100%;"
                    >
                  </div>

                  <div class="mb-2 text-center">
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-dark"
                      @click="refreshCaptcha"
                    >
                      Recargar Captcha
                    </button>
                  </div>

                  <div class="mb-3">
                    <input
                      type="text"
                      v-model="captcha"
                      class="form-control"
                      placeholder="Escribe el texto de la imagen"
                      id="captcha"
                      name="captcha"
                      autocomplete="off"
                    >
                    <div id="error-captcha" class="text-danger text-error" style="margin-top: 5px;"></div>
                  </div>

                  <div class="text-center">
                    <button
                      type="submit"
                      class="btn bg-gradient-info w-100 mt-4 mb-0"
                      :disabled="isSubmitting"
                    >
                      {{ isSubmitting ? 'Validando...' : 'Ingresar' }}
                    </button>
                  </div>
                </form>
              </div>

              <div class="card-footer text-center pt-0 px-lg-2 px-1">
                <p class="mb-4 text-sm mx-auto">
                  <!-- ¿Olvidaste tu contraseña?-->
                  <!-- <a href="" class="text-info text-gradient font-weight-bold">accede aqui</a>-->
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { clearErrors } from '@components/clearErrors.js'
import { handleErrors } from '@components/handleErrors.js'
import { showSpinner, hideSpinner } from '@components/spinner.js'
import { notyf } from '@components/notyf.js'
import axios from '@axios'
import { BASE_URL } from '@/components/url.js'

const email = ref('')
const password = ref('')
const captcha = ref('')
const captchaSrc = ref('')
const showPassword = ref(false)
const isSubmitting = ref(false)

onMounted(async () => {
  hideSpinner()
  await refreshCaptcha()
})

function togglePassword() {
  showPassword.value = !showPassword.value
}

async function refreshCaptcha() {
  try {
    const response = await axios.get('/auth/captcha')
    const captchaUrl = response.data?.captcha_src ?? ''
    const separator = captchaUrl.includes('?') ? '&' : '?'

    captchaSrc.value = captchaUrl ? `${captchaUrl}${separator}_t=${Date.now()}` : ''
    captcha.value = ''
  } catch (error) {
    notyf.error('No se pudo cargar el captcha.')
  }
}

async function sendData() {
  try {
    isSubmitting.value = true
    showSpinner()
    clearErrors()

    const response = await axios.post('/auth/login', {
      email: email.value.trim(),
      password: password.value,
      captcha: captcha.value.trim(),
    })

    if (!response.data.status) {
      notyf.error(response.data.message ?? 'No se pudo completar la acción.')
      await refreshCaptcha()
      return
    }

    window.location.href = `${String(BASE_URL).replace(/\/$/, '')}/pac`
  } catch (error) {
    clearErrors()

    if (error.response?.status === 419) {
      notyf.error('Sesión/CSRF inválido. Recarga la página e intenta de nuevo.')
      await refreshCaptcha()
      return
    }

    if (error.response?.status === 422 && error.response?.data?.errors) {
      handleErrors(error.response.data.errors)
      await refreshCaptcha()
      return
    }

    notyf.error(
      error.response?.data?.message ??
      'No se pudo completar la acción. Por favor, vuelve a intentarlo.'
    )

    await refreshCaptcha()
  } finally {
    isSubmitting.value = false
    hideSpinner()
  }
}
</script>