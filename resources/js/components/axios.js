import axios from 'axios';

// Create an axios instance with custom configuration
const url = axios.create({
    // Base URL for all HTTP requests, read from environment variables
    baseURL: import.meta.env.VITE_BASE_URL,
    // Request timeout set to 5
    timeout: 300000,
    // Default headers for all requests, specifying JSON content type
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'Content-Type': 'application/json'
    },
    // Send cookies and authentication information with requests
    withCredentials: true,
});

// Interceptor para incluir el token CSRF en cada petición
url.interceptors.request.use(config => {
    const token = document.querySelector('meta[name="csrf-token"]');
    if (token) {
        config.headers['X-CSRF-TOKEN'] = token.content;
    }
    return config;
});

/*
url.interceptors.response.use(
    response => response, // Deja pasar respuestas exitosas
    error => {
        // Detectar si la sesión expiró
        if (error.response && error.response.status === 401) {

            // Limpiar datos si es necesario
            localStorage.clear();
            sessionStorage.clear();

            // Redirigir al login
            window.location.href = '/login'; // ajusta la ruta según tu app

            // Detener ejecución
            return Promise.reject('Sesión expirada');
        }

        // Otros errores siguen su curso
        return Promise.reject(error);
    }
);
*/

// Export the configured axios instance for use in other modules
export default url;
