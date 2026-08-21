<template>
  <div class="cap-auth-shell">
    <main class="main-content cap-auth-main">
      <section class="cap-auth-section">
        <div class="page-header cap-auth-page">
          <div class="cap-login-frame">
            <section class="cap-login-card" aria-label="Acceso al sistema">
              <div class="cap-login-form-panel">
                <header class="cap-login-header">
                  <div class="cap-login-brand-row">
                    <div class="cap-login-logo-wrap">
                      <img
                        :src="logoSrc"
                        alt="IMSS-Bienestar"
                        class="cap-login-logo"
                      >
                    </div>

                    <div class="cap-login-badge">
                      <i class="fa fa-shield-halved" aria-hidden="true"></i>
                      <div>
                        <span>Acceso seguro</span>
                        <strong>Institucional</strong>
                      </div>
                    </div>
                  </div>

                  <span class="cap-login-kicker">Acceso institucional</span>
                  <h1>Sistema de Capacitación</h1>
                  <p>
                    Ingresa con tu cuenta registrada para continuar.
                  </p>

                  <div class="cap-login-highlights" aria-label="Informacion del sistema">
                    <span>
                      <i class="fa fa-certificate" aria-hidden="true"></i>
                      Constancias
                    </span>
                    <span>
                      <i class="fa fa-list-check" aria-hidden="true"></i>
                      Seguimiento
                    </span>
                    <span>
                      <i class="fa fa-user-check" aria-hidden="true"></i>
                      Perfiles
                    </span>
                  </div>
                </header>

                <form id="data_form" class="cap-login-form" @submit.prevent="sendData">
                  <div class="cap-form-group">
                    <label for="email">Correo electrónico</label>
                    <div class="cap-input-shell">
                      <i class="fa fa-envelope" aria-hidden="true"></i>
                      <input
                        type="email"
                        v-model="email"
                        class="form-control cap-input"
                        placeholder="usuario@dominio.gob.mx"
                        id="email"
                        name="email"
                        autocomplete="username"
                      >
                    </div>
                    <div id="error-email" class="text-danger text-error cap-error"></div>
                  </div>

                  <div class="cap-form-group">
                    <label for="password">Contraseña</label>
                    <div class="cap-input-shell cap-input-shell-password">
                      <i class="fa fa-lock" aria-hidden="true"></i>
                      <input
                        :type="showPassword ? 'text' : 'password'"
                        v-model="password"
                        class="form-control cap-input"
                        placeholder="Contraseña"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                      >
                      <button
                        type="button"
                        class="cap-icon-button"
                        @click="togglePassword"
                        :title="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                        :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                      >
                        <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'" aria-hidden="true"></i>
                      </button>
                    </div>
                    <div id="error-password" class="text-danger text-error cap-error"></div>
                  </div>

                  <div class="cap-form-group cap-captcha-group">
                    <div class="cap-captcha-title-row">
                      <label for="captcha">CAPTCHA</label>
                      <button
                        type="button"
                        class="cap-refresh-button"
                        @click="refreshCaptcha"
                      >
                        <i class="fa fa-rotate-right" aria-hidden="true"></i>
                        Actualizar
                      </button>
                    </div>

                    <div class="cap-captcha-card">
                      <img
                        v-if="captchaSrc"
                        :src="captchaSrc"
                        alt="Captcha"
                        class="cap-captcha-image"
                      >
                      <span v-else class="cap-captcha-placeholder">CAPTCHA</span>
                    </div>

                    <div class="cap-input-shell">
                      <i class="fa fa-shield-halved" aria-hidden="true"></i>
                      <input
                        type="text"
                        v-model="captcha"
                        class="form-control cap-input"
                        placeholder="Texto de la imagen"
                        id="captcha"
                        name="captcha"
                        autocomplete="off"
                      >
                    </div>
                    <div id="error-captcha" class="text-danger text-error cap-error"></div>
                  </div>

                  <button
                    type="submit"
                    class="btn cap-submit-button"
                    :disabled="isSubmitting"
                  >
                    <i class="fa fa-right-to-bracket" aria-hidden="true"></i>
                    <span>{{ isSubmitting ? 'Validando acceso...' : 'Ingresar' }}</span>
                  </button>
                </form>

                <p class="cap-login-footnote">
                  Coordinación Técnica de Capacitación y Evaluación
                </p>
              </div>

              <aside
                class="cap-login-visual-panel"
                :style="visualPanelStyle"
                aria-hidden="true"
              >
                <div class="cap-login-visual-content">
                  <div>
                    <span>Sistema de Capacitación</span>
                    <h2>Acceso seguro institucional</h2>
                    <p>
                      Plataforma para el seguimiento de capacitación, constancias y personal registrado.
                    </p>
                  </div>
                </div>
              </aside>
            </section>
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
const baseUrl = String(BASE_URL).replace(/\/$/, '')
const logoSrc = `${baseUrl}/assets/images/bienestar/logo-v-color.png`
const panelImageSrc = `${baseUrl}/assets/images/bienestar/gob_logo_M.png`
const visualPanelStyle = {
  backgroundImage: `linear-gradient(180deg, rgba(255, 255, 255, 0.04) 0%, rgba(255, 255, 255, 0.18) 54%, rgba(16, 49, 43, 0.92) 100%), url("${panelImageSrc}")`,
  backgroundPosition: 'center, center 34px',
  backgroundRepeat: 'no-repeat, no-repeat',
  backgroundSize: 'cover, 92% auto',
}

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

    window.location.href = `${baseUrl}/pac`
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

<style scoped>
.cap-auth-main,
.cap-auth-section {
  min-height: 100svh;
}

.cap-auth-page {
  align-items: flex-start;
  display: flex;
  justify-content: center;
  min-height: 100svh;
  overflow: visible;
  padding: 48px 16px;
}

.cap-login-frame {
  margin-block: auto;
  max-width: 1120px;
  padding: 0;
  width: 100%;
}

.cap-login-card {
  background: #ffffff;
  border: 1px solid rgba(16, 49, 43, 0.12);
  border-radius: 8px;
  box-shadow: 0 24px 60px rgba(16, 49, 43, 0.18);
  display: grid;
  grid-template-columns: minmax(400px, 0.98fr) minmax(420px, 1.02fr);
  min-height: 700px;
  overflow: hidden;
}

.cap-login-form-panel {
  background:
    linear-gradient(135deg, rgba(255, 255, 255, 0.98) 0%, rgba(255, 255, 255, 0.96) 52%, rgba(247, 250, 248, 0.98) 100%);
  display: flex;
  flex-direction: column;
  justify-content: center;
  overflow: visible;
  padding: 44px 38px;
  position: relative;
}

.cap-login-form-panel::before {
  background: linear-gradient(90deg, #235b4e 0%, #bc955c 48%, #9f2241 100%);
  content: "";
  height: 8px;
  left: 0;
  position: absolute;
  right: 0;
  top: 0;
}

.cap-login-form-panel::after {
  border: 1px solid rgba(35, 91, 78, 0.1);
  bottom: -132px;
  content: "";
  height: 220px;
  position: absolute;
  right: -96px;
  transform: rotate(45deg);
  width: 220px;
}

.cap-login-form-panel > * {
  position: relative;
  z-index: 1;
}

.cap-login-header {
  margin-bottom: 20px;
}

.cap-login-brand-row {
  align-items: center;
  display: flex;
  gap: 14px;
  justify-content: space-between;
  margin-bottom: 18px;
}

.cap-login-logo-wrap {
  align-items: center;
  background: #ffffff;
  border: 1px solid rgba(188, 149, 92, 0.32);
  border-radius: 8px;
  display: flex;
  flex: 1 1 210px;
  height: 62px;
  justify-content: center;
  padding: 10px 16px;
  width: min(260px, 100%);
}

.cap-login-logo {
  display: block;
  max-height: 44px;
  max-width: 100%;
  object-fit: contain;
}

.cap-login-badge {
  align-items: center;
  background: #f8fbfa;
  border: 1px solid rgba(35, 91, 78, 0.16);
  border-radius: 8px;
  color: #10312b;
  display: flex;
  flex: 0 0 auto;
  gap: 10px;
  min-height: 62px;
  padding: 10px 12px;
}

.cap-login-badge i {
  align-items: center;
  background: rgba(35, 91, 78, 0.1);
  border-radius: 8px;
  color: #235b4e;
  display: inline-flex;
  height: 32px;
  justify-content: center;
  width: 32px;
}

.cap-login-badge span {
  color: #6f7d78;
  display: block;
  font-size: 0.72rem;
  font-weight: 700;
  line-height: 1.1;
}

.cap-login-badge strong {
  color: #10312b;
  display: block;
  font-size: 0.86rem;
  line-height: 1.2;
}

.cap-login-kicker {
  color: #9f2241;
  display: block;
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0;
  margin-bottom: 8px;
  text-transform: uppercase;
}

.cap-login-header h1 {
  color: #10312b;
  font-size: 1.75rem;
  font-weight: 800;
  line-height: 1.12;
  margin: 0 0 8px;
}

.cap-login-header p {
  color: #56645f;
  font-size: 0.92rem;
  line-height: 1.42;
  margin: 0;
}

.cap-login-highlights {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 12px;
}

.cap-login-highlights span {
  align-items: center;
  background: #ffffff;
  border: 1px solid rgba(16, 49, 43, 0.1);
  border-radius: 8px;
  color: #314640;
  display: inline-flex;
  flex: 1 1 120px;
  font-size: 0.78rem;
  font-weight: 800;
  gap: 8px;
  justify-content: center;
  min-height: 34px;
  padding: 0 10px;
}

.cap-login-highlights i {
  color: #9f2241;
  font-size: 0.86rem;
}

.cap-login-form {
  display: grid;
  gap: 14px;
}

.cap-form-group {
  display: grid;
  gap: 8px;
}

.cap-form-group label {
  color: #253c36;
  font-size: 0.86rem;
  font-weight: 700;
  margin: 0;
}

.cap-input-shell {
  align-items: center;
  background: #f8faf9;
  border: 1px solid #d9e3df;
  border-radius: 8px;
  display: grid;
  gap: 10px;
  grid-template-columns: 20px 1fr;
  min-height: 44px;
  padding: 0 14px;
  transition: border-color 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
}

.cap-input-shell:focus-within {
  background: #ffffff;
  border-color: #235b4e;
  box-shadow: 0 0 0 4px rgba(35, 91, 78, 0.12);
}

.cap-input-shell i {
  color: #7a8b86;
  font-size: 0.95rem;
  text-align: center;
}

.cap-input-shell-password {
  grid-template-columns: 20px 1fr 36px;
  padding-right: 6px;
}

.cap-input {
  background: transparent !important;
  border: 0 !important;
  box-shadow: none !important;
  color: #102a25;
  font-size: 0.94rem;
  height: 42px;
  min-width: 0;
  padding: 0 !important;
}

.cap-input::placeholder {
  color: #95a39e;
}

.cap-icon-button {
  align-items: center;
  background: #ffffff;
  border: 1px solid #d9e3df;
  border-radius: 8px;
  color: #235b4e;
  display: inline-flex;
  height: 34px;
  justify-content: center;
  padding: 0;
  transition: background-color 0.18s ease, border-color 0.18s ease, color 0.18s ease;
  width: 34px;
}

.cap-icon-button:hover {
  background: #edf5f2;
  border-color: #235b4e;
  color: #10312b;
}

.cap-captcha-title-row {
  align-items: center;
  display: flex;
  gap: 12px;
  justify-content: space-between;
}

.cap-refresh-button {
  align-items: center;
  background: #ffffff;
  border: 1px solid rgba(188, 149, 92, 0.55);
  border-radius: 8px;
  color: #7b4f1e;
  display: inline-flex;
  font-size: 0.8rem;
  font-weight: 700;
  gap: 7px;
  min-height: 34px;
  padding: 0 12px;
  transition: background-color 0.18s ease, border-color 0.18s ease, color 0.18s ease;
  white-space: nowrap;
}

.cap-refresh-button:hover {
  background: #fbf6ef;
  border-color: #bc955c;
  color: #5e3b13;
}

.cap-captcha-card {
  align-items: center;
  background: linear-gradient(135deg, #f7faf8, #ffffff);
  border: 1px dashed rgba(35, 91, 78, 0.36);
  border-radius: 8px;
  display: flex;
  justify-content: center;
  min-height: 90px;
  padding: 10px 14px;
}

.cap-captcha-image {
  border: 0;
  border-radius: 4px;
  display: block;
  height: 72px;
  max-height: none;
  max-width: 100%;
  object-fit: contain;
  width: min(100%, 320px);
}

.cap-captcha-placeholder {
  color: #8a9994;
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0;
}

.cap-error {
  font-size: 0.78rem;
  margin-top: 0;
  min-height: 16px;
}

.cap-error:empty {
  display: none;
  min-height: 0;
}

.cap-submit-button {
  align-items: center;
  background: linear-gradient(135deg, #235b4e, #10312b);
  border: 0;
  border-radius: 8px;
  box-shadow: 0 12px 24px rgba(16, 49, 43, 0.24);
  color: #ffffff;
  display: inline-flex;
  font-size: 0.96rem;
  font-weight: 800;
  gap: 10px;
  justify-content: center;
  min-height: 50px;
  padding: 0 18px;
  transition: box-shadow 0.18s ease, filter 0.18s ease, transform 0.18s ease;
  width: 100%;
}

.cap-submit-button:hover:not(:disabled) {
  box-shadow: 0 16px 30px rgba(16, 49, 43, 0.28);
  color: #ffffff;
  filter: brightness(1.04);
  transform: translateY(-1px);
}

.cap-submit-button:disabled {
  cursor: wait;
  opacity: 0.72;
}

.cap-login-footnote {
  background: #ffffff;
  border: 1px solid rgba(16, 49, 43, 0.1);
  border-left: 4px solid #bc955c;
  border-radius: 8px;
  color: #6f7d78;
  font-size: 0.82rem;
  line-height: 1.45;
  margin: 16px 0 0;
  padding: 10px 14px;
  text-align: left;
}

.cap-login-visual-panel {
  background-color: #ffffff;
  background-position: center;
  background-size: cover;
  display: flex;
  min-height: 0;
  padding: 44px;
  position: relative;
}

.cap-login-visual-panel::after {
  border: 1px solid rgba(16, 49, 43, 0.16);
  border-radius: 8px;
  bottom: 24px;
  content: "";
  left: 24px;
  pointer-events: none;
  position: absolute;
  right: 24px;
  top: 24px;
}

.cap-login-visual-content {
  align-self: flex-end;
  color: #ffffff;
  max-width: 440px;
  position: relative;
  text-shadow: 0 2px 14px rgba(0, 0, 0, 0.28);
  z-index: 1;
}

.cap-login-visual-content span {
  color: #e7c991;
  display: block;
  font-size: 0.82rem;
  font-weight: 800;
  margin-bottom: 10px;
  text-transform: uppercase;
}

.cap-login-visual-content h2 {
  color: #ffffff;
  font-size: 1.9rem;
  font-weight: 800;
  line-height: 1.12;
  margin: 0 0 14px;
}

.cap-login-visual-content p {
  color: rgba(255, 255, 255, 0.86);
  font-size: 1rem;
  line-height: 1.58;
  margin: 0;
}

@media (max-width: 991.98px) {
  .cap-auth-page {
    overflow: visible;
    padding: 24px 14px;
  }

  .cap-login-card {
    grid-template-columns: 1fr;
    height: auto;
    min-height: auto;
  }

  .cap-login-visual-panel {
    grid-row: 1;
    min-height: 260px;
    padding: 32px;
  }

  .cap-login-visual-content {
    max-width: 640px;
  }

  .cap-login-visual-content h2 {
    font-size: 1.7rem;
  }

  .cap-login-form-panel {
    padding: 34px;
  }

  .cap-login-brand-row {
    align-items: stretch;
  }
}

@media (max-width: 575.98px) {
  .cap-auth-page {
    padding: 16px 10px;
  }

  .cap-login-form-panel,
  .cap-login-visual-panel {
    padding: 24px;
  }

  .cap-login-header h1 {
    font-size: 1.55rem;
  }

  .cap-login-brand-row {
    flex-direction: column;
  }

  .cap-login-logo-wrap {
    flex-basis: auto;
    height: 68px;
    width: 100%;
  }

  .cap-login-badge {
    min-height: 58px;
    width: 100%;
  }

  .cap-captcha-title-row {
    align-items: flex-start;
    flex-direction: column;
    gap: 8px;
  }

  .cap-refresh-button {
    width: 100%;
    justify-content: center;
  }

  .cap-captcha-card {
    min-height: 86px;
    padding: 8px;
  }

  .cap-captcha-image {
    height: 66px;
    width: 100%;
  }

  .cap-login-visual-panel {
    min-height: 220px;
  }

  .cap-login-visual-panel::after {
    bottom: 14px;
    left: 14px;
    right: 14px;
    top: 14px;
  }

  .cap-login-visual-content h2 {
    font-size: 1.35rem;
  }

  .cap-login-visual-content p {
    font-size: 0.9rem;
  }
}
</style>
