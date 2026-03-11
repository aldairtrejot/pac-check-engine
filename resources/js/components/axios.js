// axios.js
import axios from 'axios';

const url = axios.create({
  baseURL: '/pac-check-engine/public', // <- relativo
  timeout: 300000,
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
    'Accept': 'application/json',
  },
  withCredentials: true,
});

url.interceptors.request.use(config => {
  const token = document.querySelector('meta[name="csrf-token"]');
  if (token?.content) config.headers['X-CSRF-TOKEN'] = token.content;
  return config;
});

export default url;