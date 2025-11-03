// resources/js/app.js
import { createApp } from 'vue'

import vue_form_login from './app/auth/login.vue'
import vue_table_pac from './app/pac/table.vue'
import vue_table_action from './app/action/table.vue'
import vue_table_tematica from './app/tematica/table.vue' // 👈 NUEVO

const components = [
    // login
    { selector: '#blade_form_login', component: vue_form_login },

    // pac
    { selector: '#blade_table_pac', component: vue_table_pac },

    // action
    { selector: '#blade_table_action', component: vue_table_action },

    // tematica 👈 NUEVO
    { selector: '#blade_table_tematica', component: vue_table_tematica },
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
