import { createApp } from 'vue'

import vue_form_login from './app/auth/login.vue'
import vue_table_pac from './app/pac/table.vue'
import vue_table_action from './app/action/table.vue'
import vue_table_tematica from './app/tematica/table.vue'
import vue_table_instancia from './app/instancia/table.vue'
import vue_table_constancias from './app/constancias/table.vue'

const components = [  
  // login
  { selector: '#blade_form_login', component: vue_form_login },

  // pac
  { selector: '#blade_table_pac', component: vue_table_pac },

  // acción
  { selector: '#blade_table_action', component: vue_table_action },

  // temática
  { selector: '#blade_table_tematica', component: vue_table_tematica },

  // instancias
  { selector: '#blade_table_instancia', component: vue_table_instancia },

    // constancias
  { selector: '#blade_table_constancias', component: vue_table_constancias },
  
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
