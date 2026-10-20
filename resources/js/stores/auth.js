import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        token: localStorage.getItem('residia_token') || null,
        user: JSON.parse(localStorage.getItem('residia_user') || 'null'),
        vistasAutorizadas: JSON.parse(localStorage.getItem('residia_vistas') || '[]'),
        loading: false,
        error: null,
    }),
    getters: {
        isAuthenticated: (state) => Boolean(state.token && state.user),
        isSuperAdmin: (state) => Boolean(state.user?.es_superadmin),
        puedeVerVista: (state) => (codigoVista) => {
            if (state.user?.es_superadmin) return true;
            if (!state.vistasAutorizadas || state.vistasAutorizadas.length === 0) return false;
            return state.vistasAutorizadas.includes(codigoVista);
        },
    },
    actions: {
        async login(loginInput, password) {
            this.loading = true;
            this.error = null;
            try {
                const res = await axios.post('/api/auth/login', { 
                    login: loginInput, 
                    password: password 
                });
                if (res.data.success) {
                    this.token = res.data.access_token;
                    this.user = res.data.user;

                    localStorage.setItem('residia_token', this.token);
                    localStorage.setItem('residia_user', JSON.stringify(this.user));

                    axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
                    
                    // Cargar vistas autorizadas del usuario
                    await this.cargarMisVistas();

                    return { success: true };
                }
                return { success: false, message: 'Respuesta inválida del servidor' };
            } catch (err) {
                this.error = err.response?.data?.message || 'Error de credenciales o de conexión';
                return { success: false, message: this.error };
            } finally {
                this.loading = false;
            }
        },

        async cargarMisVistas() {
            if (!this.token) return;
            try {
                const res = await axios.get('/api/seguridad/mis-vistas', {
                    headers: {
                        'Authorization': `Bearer ${this.token}`,
                        'X-Urbanizacion-Id': 'ccce9ae7-ca93-48e7-8577-0dd546dda010'
                    }
                });
                if (res.data.success) {
                    this.vistasAutorizadas = res.data.codigos || [];
                    localStorage.setItem('residia_vistas', JSON.stringify(this.vistasAutorizadas));
                }
            } catch (e) {
                console.error('Error cargando vistas autorizadas:', e);
            }
        },

        logout() {
            try {
                if (this.token) {
                    axios.post('/api/auth/logout', {}, {
                        headers: { Authorization: `Bearer ${this.token}` }
                    }).catch(() => {});
                }
            } finally {
                this.token = null;
                this.user = null;
                this.vistasAutorizadas = [];
                localStorage.removeItem('residia_token');
                localStorage.removeItem('residia_user');
                localStorage.removeItem('residia_vistas');
                delete axios.defaults.headers.common['Authorization'];
            }
        },

        async initAuth() {
            if (this.token) {
                axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
                await this.cargarMisVistas();
            }
        }
    }
});