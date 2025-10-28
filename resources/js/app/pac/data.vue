<template>
    <!-- Modal component for changing password -->
    <modalTemplate modalId="modal_password_user" title="Cambiar contraseña" :onConfirm="button_confirm" size="lg">
        <form role="form" id="data_form" enctype="multipart/form-data">
            <div class="row">
                <li class="list-group-item border-0 d-flex p-4 mb-2 bg-gray-100 border-radius-lg">
                    <div class="d-flex flex-column">
                        <h6 class="mb-3 text-sm">Oliver Liam</h6>
                        <span class="mb-2 text-xs">Company Name: <span class="text-dark font-weight-bold ms-sm-2">Viking
                                Burrito</span></span>
                        <span class="mb-2 text-xs">Email Address: <span
                                class="text-dark ms-sm-2 font-weight-bold">oliver@burrito.com</span></span>
                        <span class="text-xs">VAT Number: <span
                                class="text-dark ms-sm-2 font-weight-bold">FRB1235476</span></span>
                    </div>
                    <div class="ms-auto text-end">
                        <a class="btn btn-link text-danger text-gradient px-3 mb-0" href="javascript:;"><i
                                class="far fa-trash-alt me-2"></i>Delete</a>
                        <a class="btn btn-link text-dark px-3 mb-0" href="javascript:;"><i
                                class="fas fa-pencil-alt text-dark me-2" aria-hidden="true"></i>Edit</a>
                    </div>
                </li>
            </div>
        </form>
    </modalTemplate>
</template>

<script setup>
import { ref } from 'vue'
import axios from '@axios'; // Importing Axios for making HTTP requests
import { onMounted } from 'vue'
import { notyf } from '@components/notyf.js'; // Importing the notification function for showing success/error messages
import modalTemplate from '@helpers/modal/modal-template.vue'; // Custom modal component
import inputField from '@helpers/form/input-field.vue'; // Custom input component
import { clearErrors } from '@components/clearErrors.js'; // Importing function to clear previous errors
import { handleErrors } from '@components/handleErrors.js'; // Importing function to handle and display validation errors

const password = ref('') // Reactive variable to bind the password field

onMounted(() => {
    const modal = document.getElementById('modal_password_user')

    // When the modal is shown, clear the password field
    modal?.addEventListener('shown.bs.modal', () => {
        password.value = '' // Reset password input on modal open
    })
})

// Method to send password update request
async function button_confirm() {
    const form = document.querySelector('#data_form'); // Get the form element
    const formData = new FormData(form); // Create a FormData object to submit data
    const userId = window._selectedUserIdForPassword // Get the user ID from global variable
    formData.append('id', userId); // Add user ID to form data

    try {
        startLoader(); // Show loading indicator
        clearErrors(); // Clear any previous validation errors

        const response = await axios.post('/user/changePassword', formData); // Send request to change password
        if (response.data.status) {
            notyfEM.success(response.data.message); // Show success notification
            bootstrap.Modal.getInstance(document.getElementById('modal_password_user'))?.hide(); // Close modal
        } else {
            clearErrors(); // Clear any residual errors
            notyfEM.error(response.data.message); // Show error notification
        }
    } catch (error) {
        clearErrors(); // Clear previous errors
        if (error.response && error.response.data.errors) {
            handleErrors(error.response.data.errors); // Handle and show validation errors
        }
    } finally {
        stopLoader(); // Hide loading indicator
    }
}
</script>
