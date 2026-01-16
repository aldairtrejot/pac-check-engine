import axios from 'axios';

const url = axios.create({
  baseURL: import.meta.env.VITE_BASE_URL,
  timeout: 300000,
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
    'Accept': 'application/json',
    // ❌ NO fijes Content-Type global
  },
  withCredentials: true,
});

url.interceptors.request.use(config => {
  const token = document.querySelector('meta[name="csrf-token"]');
  if (token?.content) {
    config.headers['X-CSRF-TOKEN'] = token.content;
  }
  return config;
});

export default url;
