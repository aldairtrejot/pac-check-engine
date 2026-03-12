import axios from 'axios';
import { BASE_URL } from '@/components/url.js';

function getCookie(name) {
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) {
    return parts.pop().split(';').shift();
  }
  return null;
}

const url = axios.create({
  baseURL: BASE_URL,
  timeout: 300000,
  withCredentials: true,
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
  xsrfCookieName: 'XSRF-TOKEN',
  xsrfHeaderName: 'X-XSRF-TOKEN',
});

url.interceptors.request.use(config => {
  const metaToken = document.querySelector('meta[name="csrf-token"]');

  if (metaToken?.content) {
    config.headers['X-CSRF-TOKEN'] = metaToken.content;
  }

  const xsrfToken = getCookie('XSRF-TOKEN');
  if (xsrfToken) {
    config.headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrfToken);
  }

  return config;
});

export default url;