// resources/js/components/notyf.js
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';         // estilos por defecto
import '@/asset/css/notyf-custom.css'; // tus overrides

export const notyf = new Notyf({
    position: { x: 'right', y: 'top' },
    dismissible: false,
    duration: 6000,
});

const msg = sessionStorage.getItem('nofify_message');
if (msg) {
    notyf.success(msg);
    sessionStorage.removeItem('nofify_message');
}