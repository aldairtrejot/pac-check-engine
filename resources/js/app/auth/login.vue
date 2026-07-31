<template>
  <div class="cap-auth-shell">
    <main class="main-content cap-auth-main">
      <section class="cap-auth-section">
        <div
          class="page-header cap-auth-page d-flex justify-content-center align-items-center"
        >
          <div class="col-xl-3 col-lg-4 col-md-6 col-sm-8 col-11">
            <div
              class="card card-plain cap-auth-card"
            >
              <div class="card-header pb-0 text-center bg-transparent cap-auth-header">
                <div class="cap-auth-logo-wrap mx-auto">
                  <img
                    :src="logoSrc"
                    alt="Sistema de Capacitación"
                    class="cap-auth-logo"
                  >
                </div>

                <h3 class="font-weight-bolder text-center cap-auth-title">
                  Sistema de Capacitación
                </h3>
                <p class="mb-0 text-center cap-auth-subtitle">
                  Ingresa con tu cuenta institucional para continuar.
                </p>
              </div>

              <div class="card-body cap-auth-body">
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

                  <div class="mb-2 text-center cap-captcha-box">
                    <img
                      v-if="captchaSrc"
                      :src="captchaSrc"
                      alt="Captcha"
                      class="cap-captcha-image"
                    >
                  </div>

                  <div class="mb-2 text-center">
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-secondary cap-btn-soft"
                      @click="refreshCaptcha"
                    >
                      <i class="fa fa-rotate-right me-1"></i>
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
                      class="btn w-100 mt-4 mb-0 cap-btn-primary"
                      :disabled="isSubmitting"
                    >
                      <i class="fa fa-right-to-bracket me-1"></i>
                      {{ isSubmitting ? 'Validando...' : 'Ingresar' }}
                    </button>
                  </div>
                </form>
              </div>

              <div class="card-footer text-center pt-0 px-lg-2 px-1 cap-auth-footer">
                <p class="mb-0 text-sm mx-auto">
                  Coordinación Técnica de Capacitación y Evaluación
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
const logoSrc = `${String(BASE_URL).replace(/\/$/, '')}/assets/images/bienestar/logo-v-color.png`

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
