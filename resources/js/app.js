// resources/js/app.js
import { createApp } from 'vue'

import vue_form_login from './app/auth/login.vue'
import vue_table_pac from './app/pac/table.vue'

const components = [
    // Student
    { selector: '#blade_form_login', component: vue_form_login },
    { selector: '#blade_table_pac', component: vue_table_pac },

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

