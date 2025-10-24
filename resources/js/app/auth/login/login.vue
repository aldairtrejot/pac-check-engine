<template>

    <body class="" style="margin:0; padding:0;">
        <main class="main-content" style="padding:0; margin:0;">
            <section style="padding:0; margin:0;">
                <div class="page-header d-flex justify-content-center align-items-center"
                    style="padding:0; margin:0; min-height:100vh; overflow:visible;">
                    <!-- CAMBIO AQUÍ: Se redujo el ancho -->
                    <div class="col-xl-3 col-lg-4 col-md-5 col-sm-8 col-10" style="padding:0;">
                        <div class="card card-plain" style="background-color: transparent !important; 
                                box-shadow: 0 12px 30px rgba(0, 0, 0, 0.4) !important; 
                                border-radius: 12px !important;">
                            <div class="card-header pb-0 text-left bg-transparent">
                                <h3 class="font-weight-bolder text-info text-gradient text-center">
                                    Validación de Plantillas PAC
                                </h3>
                                <p class="mb-0 text-center">Introduce tu usuario y contraseña para iniciar sesión.</p>
                            </div>
                            <div class="card-body">

                                <form role="form" id="data_form" enctype="multipart/form-data">

                                    <label for="email">Usuario</label>
                                    <div class="mb-3">
                                        <input type="email" v-model="email" class="form-control" placeholder="Email"
                                            aria-label="Email" aria-describedby="email-addon" id="email" name="email"
                                            autocomplete="username">
                                        <div id="error-email" class="text-danger text-error" style="margin-top: 5px;">
                                        </div>
                                    </div>

                                    <label for="password">Contraseña</label>
                                    <div class="mb-3">
                                        <input type="password" v-model="password" class="form-control"
                                            placeholder="Password" aria-label="Password"
                                            aria-describedby="password-addon" id="password" name="password"
                                            autocomplete="current-password">
                                        <div id="error-password" class="text-danger text-error"
                                            style="margin-top: 5px;"></div>
                                    </div>

                                    <div class="text-center">
                                        <button type="button" @click="sendData"
                                            class="btn bg-gradient-info w-100 mt-4 mb-0">Ingresar</button>
                                    </div>
                                </form>

                            </div>
                            <div class="card-footer text-center pt-0 px-lg-2 px-1">
                                <p class="mb-4 text-sm mx-auto">
                                    ¿Olvidaste tu contraseña?
                                    <a href="" class="text-info text-gradient font-weight-bold">accede
                                        aqui</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </body>

</template>



<script setup>
import { ref, onMounted } from 'vue'
import { clearErrors } from '@components/clearErrors.js';
import { handleErrors } from '@components/handleErrors.js';
import { showSpinner, hideSpinner } from '@components/spinner.js'
import { notyf } from '@components/notyf.js';
import axios from '@axios'
import { BASE_URL } from '@/components/url.js';

// Campos del formulario
const email = ref('')
const password = ref('')


// Inicialización
onMounted(() => {
    try {
        showSpinner();
    } finally {
        hideSpinner();
    }
})

// Enviar datos
async function sendData() {
    try {

        showSpinner();
        clearErrors();

        const form = document.querySelector('#data_form');
        const formData = new FormData(form);

        const response = await axios.post('/auth/login', formData);

        if (!response.data.status) {
            notyf.error(response.data.message);
        }

        if (response.data.status) {
            window.location.href = `${BASE_URL}/login`;
        }

    } catch (error) {
        clearErrors();

        if (error.response?.data?.errors) {
            handleErrors(error.response.data.errors);
        }

    } finally {
        hideSpinner();
    }
}
</script>
