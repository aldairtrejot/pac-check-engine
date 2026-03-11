import axios from 'axios';
window.axios = axios;
 
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
 
// Forzar la lectura del token CSRF desde el meta-tag del head
let token = document.head.querySelector('meta[name="csrf-token"]');
 
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found: Asegúrate de que la vista tenga <meta name="csrf-token">');
}