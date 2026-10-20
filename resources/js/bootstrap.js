import axios from "axios";
window.axios = axios;

window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

// Interceptor global para inyectar automáticamente el Bearer Token JWT y el Tenant ID
axios.interceptors.request.use((config) => {
  const token = localStorage.getItem("residia_token");
  if (token) {
    config.headers["Authorization"] = `Bearer ${token}`;
  }
  const urbId = localStorage.getItem("residia_urb_id") || "ccce9ae7-ca93-48e7-8577-0dd546dda010";
  config.headers["X-Urbanizacion-Id"] = urbId;
  return config;
}, (error) => {
  return Promise.reject(error);
});

// Interceptor de respuesta para detectar expiración de sesión (401)
axios.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response && error.response.status === 401) {
      console.warn('Sesión expirada o token no autorizado (401)');
      // Si la URL actual no es login, limpiar y avisar
      if (!window.location.hash.includes('/login') && !window.location.pathname.includes('/login')) {
        localStorage.removeItem('residia_token');
        localStorage.removeItem('residia_user');
        window.location.reload();
      }
    }
    return Promise.reject(error);
  }
);
