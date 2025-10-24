// resources/js/app.js
import { createApp } from 'vue'

import vue_form_login from './app/auth/login/login.vue'


const components = [
    // Student
    { selector: '#blade_form_login', component: vue_form_login },

]


// Mounting components if their div exists in the DOM
document.addEventListener('DOMContentLoaded', () => {
    components.forEach(({ selector, component }) => {
        const el = document.querySelector(selector)
        if (el) {
            createApp(component).mount(el)
        }
    })
})

