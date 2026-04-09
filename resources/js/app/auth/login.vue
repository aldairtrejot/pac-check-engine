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
                  Introduce tu usuario y contraseña para iniciar sesión.
                </p>
              </div>

              <div class="card-body">
                <form id="data_form" @submit.prevent="sendData">
                  <label for="email">Usuario</label>
                  <div class="mb-3">
                    <input
                      type="text"
                      v-model="email"
                      class="form-control"
                      placeholder="Usuario"
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
                        placeholder="Password"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                      >
                      <button
                        type="button"
                        class="btn btn-outline-secondary mb-0"
                        @click="togglePassword"
                      >
                        <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                      </button>
                    </div>
                    <div id="error-password" class="text-danger text-error" style="margin-top: 5px;"></div>
                  </div>

                  <div class="mb-3">
                    <div id="recaptcha-container" class="d-flex justify-content-center"></div>
                    <div id="error-captcha_token" class="text-danger text-error text-center" style="margin-top: 5px;"></div>
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
                  ¿Olvidaste tu contraseña?
                  <a href="" class="text-info text-gradient font-weight-bold">accede aqui</a>
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
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { clearErrors } from '@components/clearErrors.js'
import { handleErrors } from '@components/handleErrors.js'
import { showSpinner, hideSpinner } from '@components/spinner.js'
import { notyf } from '@components/notyf.js'
import axios from '@axios'
import { BASE_URL } from '@/components/url.js'

const email = ref('')
const password = ref('')
const showPassword = ref(false)
const isSubmitting = ref(false)

const captchaToken = ref('')
const captchaWidgetId = ref(null)
const recaptchaSiteKey = ref('')

function togglePassword() {
  showPassword.value = !showPassword.value
}

function onCaptchaSuccess(token) {
  captchaToken.value = token
}

function onCaptchaExpired() {
  captchaToken.value = ''
  notyf.error('El captcha expiró. Vuelve a validarlo.')
  resetCaptcha()
}

function onCaptchaError() {
  captchaToken.value = ''
  notyf.error('El captcha tuvo un problema de red. Inténtalo de nuevo.')
}

function renderRecaptcha() {
  const container = document.getElementById('recaptcha-container')

  if (!container) {
    notyf.error('No se encontró el contenedor del captcha.')
    return
  }

  if (!window.grecaptcha) {
    notyf.error('reCAPTCHA no está disponible todavía.')
    return
  }

  if (!recaptchaSiteKey.value) {
    notyf.error('La clave pública de reCAPTCHA no está configurada.')
    return
  }

  if (captchaWidgetId.value !== null) {
    return
  }

  captchaWidgetId.value = window.grecaptcha.render(container, {
    sitekey: recaptchaSiteKey.value,
    callback: onCaptchaSuccess,
    'expired-callback': onCaptchaExpired,
    'error-callback': onCaptchaError,
    theme: 'light',
  })
}

function loadRecaptchaScript() {
  return new Promise((resolve, reject) => {
    if (window.grecaptcha && window.grecaptcha.render) {
      resolve()
      return
    }

    window.onRecaptchaLoaded = () => {
      resolve()
    }

    const existing = document.getElementById('google-recaptcha-script')
    if (existing) {
      return
    }

    const script = document.createElement('script')
    script.id = 'google-recaptcha-script'
    script.src = 'https://www.google.com/recaptcha/api.js?onload=onRecaptchaLoaded&render=explicit&hl=es'
    script.async = true
    script.defer = true
    script.onerror = () => {
      reject(new Error('No se pudo cargar el script de Google reCAPTCHA.'))
    }

    document.head.appendChild(script)
  })
}

function resetCaptcha() {
  captchaToken.value = ''

  if (window.grecaptcha && captchaWidgetId.value !== null) {
    window.grecaptcha.reset(captchaWidgetId.value)
  }
}

onMounted(async () => {
  hideSpinner()

  const mountEl = document.querySelector('#blade_form_login')
  recaptchaSiteKey.value = mountEl?.dataset?.recaptchaSiteKey ?? ''

  try {
    await loadRecaptchaScript()
    renderRecaptcha()
  } catch (error) {
    notyf.error(error.message ?? 'No se pudo cargar el captcha.')
  }
})

onBeforeUnmount(() => {
  if (window.onRecaptchaLoaded) {
    delete window.onRecaptchaLoaded
  }
})

async function sendData() {
  try {
    isSubmitting.value = true
    showSpinner()
    clearErrors()

    if (!captchaToken.value) {
      notyf.error('Debes completar el captcha antes de ingresar.')
      return
    }

    const response = await axios.post('/auth/login', {
      email: email.value.trim(),
      password: password.value,
      captcha_token: captchaToken.value,
    })

    if (!response.data.status) {
      notyf.error(response.data.message ?? 'No se pudo completar la acción.')
      resetCaptcha()
      return
    }

    window.location.href = `${BASE_URL}/pac`
  } catch (error) {
    clearErrors()

    if (error.response?.status === 419) {
      notyf.error('Sesión/CSRF inválido. Recarga la página e intenta de nuevo.')
      resetCaptcha()
      return
    }

    if (error.response?.status === 422 && error.response?.data?.errors) {
      handleErrors(error.response.data.errors)
      resetCaptcha()
      return
    }

    notyf.error(
      error.response?.data?.message ??
      'No se pudo completar la acción. Por favor, vuelve a intentarlo.'
    )

    resetCaptcha()
  } finally {
    isSubmitting.value = false
    hideSpinner()
  }
}
</script>