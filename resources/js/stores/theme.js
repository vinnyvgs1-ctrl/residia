import { defineStore } from 'pinia';
import axios from 'axios';

export const useThemeStore = defineStore('theme', {
    state: () => ({
        temas: [],
        currentTema: null,
        isDark: true,
        loading: false,
    }),
    actions: {
        async fetchTemas() {
            this.loading = true;
            try {
                const res = await axios.get('/api/temas');
                if (res.data.success) {
                    this.temas = res.data.data;
                    const savedCode = localStorage.getItem('residia_theme_code') || 'robledal-dark';
                    const active = this.temas.find(t => t.tem_codigo === savedCode) || this.temas[0];
                    this.applyTheme(active);
                }
            } catch (err) {
                console.error('Error cargando temas desde la BD:', err);
                this.applyFallbackDark();
            } finally {
                this.loading = false;
            }
        },

        applyTheme(tema) {
            if (!tema || !tema.tem_variables_css) return;

            this.currentTema = tema;
            this.isDark = Boolean(tema.tem_es_oscuro);
            localStorage.setItem('residia_theme_code', tema.tem_codigo);

            const root = document.documentElement;
            const vars = typeof tema.tem_variables_css === 'string' 
                ? JSON.parse(tema.tem_variables_css) 
                : tema.tem_variables_css;

            Object.entries(vars).forEach(([cssVar, val]) => {
                root.style.setProperty(cssVar, val);
            });

            if (this.isDark) {
                root.classList.add('dark');
            } else {
                root.classList.remove('dark');
            }
        },

        toggleRobledalMode() {
            const nextCode = this.isDark ? 'robledal-light' : 'robledal-dark';
            const nextTema = this.temas.find(t => t.tem_codigo === nextCode);
            if (nextTema) {
                this.applyTheme(nextTema);
            }
        },

        applyFallbackDark() {
            const fallbackVars = {
                '--bg-primary': '#323637',
                '--bg-surface': '#2B2E2F',
                '--bg-card': '#3F4445',
                '--text-primary': '#FFFFFF',
                '--text-secondary': '#9BA3A7',
                '--color-primary': '#72C1A5',
                '--color-primary-hover': '#5EB093',
                '--border-subtle': 'rgba(255, 255, 255, 0.10)',
            };
            const root = document.documentElement;
            Object.entries(fallbackVars).forEach(([k, v]) => root.style.setProperty(k, v));
            root.classList.add('dark');
            this.isDark = true;
        }
    }
});
