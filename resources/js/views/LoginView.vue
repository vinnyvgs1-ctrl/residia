<template>
  <div class="login-wrapper">
    <div class="login-card">
      <div class="login-header">
        <RobledalLogo :size="48" :is-dark="themeStore.isDark" />
        <p class="login-subtitle">Gestión Integral de Urbanizaciones & Control de Accesos</p>
      </div>

      <!-- Alerta de Error -->
      <div v-if="errorMsg" class="alert-error">
        <AlertCircle :size="16" />
        <span>{{ errorMsg }}</span>
      </div>

      <form @submit.prevent="ejecutarLogin" class="login-form">
        <div class="form-group">
          <label>Correo Electrónico o Cédula / Identificación</label>
          <div class="input-wrap">
            <UserIcon :size="16" class="input-icon" />
            <input 
              v-model="loginInput" 
              type="text" 
              placeholder="admin@robledal.com o 1799999999" 
              class="form-input" 
              required 
            />
          </div>
          <span class="text-[11px] text-[var(--text-muted)] mt-1">
            Puedes entrar con tu correo oficial o con tu número de cédula/pasaporte.
          </span>
        </div>

        <div class="form-group">
          <label>Contraseña</label>
          <div class="input-wrap">
            <Lock :size="16" class="input-icon" />
            <input 
              v-model="password" 
              :type="mostrarPassword ? 'text' : 'password'" 
              placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" 
              class="form-input" 
              required 
            />
            <button type="button" @click="mostrarPassword = !mostrarPassword" class="btn-eye">
              <EyeOff v-if="mostrarPassword" :size="16" />
              <Eye v-else :size="16" />
            </button>
          </div>
        </div>

        <button type="submit" :disabled="authStore.loading" class="btn-login">
          <Loader2 v-if="authStore.loading" :size="18" class="animate-spin" />
          <LogIn v-else :size="18" />
          <span>{{ authStore.loading ? 'Autenticando con JWT...' : 'Iniciar Sesión' }}</span>
        </button>
      </form>

      <!-- Selector de Tema en el Footer del Login -->
      <div class="login-footer">
        <span class="text-xs text-[var(--text-muted)]">Tema Visual:</span>
        <button @click="themeStore.toggleRobledalMode()" class="btn-theme-toggle">
          <Sun v-if="themeStore.isDark" :size="14" />
          <Moon v-else :size="14" />
          <span>{{ themeStore.isDark ? 'Modo Claro' : 'Modo Oscuro' }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useThemeStore } from '../stores/theme';
import RobledalLogo from '../components/RobledalLogo.vue';
import { User as UserIcon, Lock, Eye, EyeOff, LogIn, AlertCircle, Loader2, Sun, Moon } from 'lucide-vue-next';

const authStore = useAuthStore();
const themeStore = useThemeStore();

const loginInput = ref('admin@robledal.com');
const password = ref('Admin2026.*');
const mostrarPassword = ref(false);
const errorMsg = ref('');

const ejecutarLogin = async () => {
  errorMsg.value = '';
  const res = await authStore.login(loginInput.value, password.value);
  if (!res.success) {
    errorMsg.value = res.message;
  }
};
</script>

<style scoped>
.login-wrapper {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  background: radial-gradient(circle at 50% 30%, var(--bg-surface) 0%, var(--bg-primary) 70%);
}
.login-card {
  width: 100%;
  max-width: 440px;
  background: var(--bg-card);
  border: 1px solid var(--border-subtle);
  border-radius: 16px;
  padding: 36px 32px;
  box-shadow: var(--shadow-elevation);
}
.login-header {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  margin-bottom: 28px;
}
.login-subtitle {
  font-size: 12px;
  color: var(--text-secondary);
  margin-top: 14px;
  line-height: 1.4;
}
.alert-error {
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(239, 68, 68, 0.15);
  border: 1px solid rgba(239, 68, 68, 0.3);
  color: #EF4444;
  padding: 10px 14px;
  border-radius: 8px;
  font-size: 12px;
  margin-bottom: 20px;
}
.login-form {
  display: flex;
  flex-direction: column;
  gap: 18px;
}
.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.form-group label {
  font-size: 12px;
  color: var(--text-secondary);
  font-weight: 500;
}
.input-wrap {
  position: relative;
  display: flex;
  align-items: center;
}
.input-icon {
  position: absolute;
  left: 12px;
  color: var(--text-muted);
  pointer-events: none;
}
.form-input {
  width: 100%;
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  color: var(--text-primary);
  padding: 11px 40px 11px 38px;
  border-radius: 8px;
  font-size: 13px;
  outline: none;
}
.form-input:focus {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 2px var(--color-primary-subtle);
}
.btn-eye {
  position: absolute;
  right: 12px;
  background: transparent;
  border: none;
  color: var(--text-muted);
  cursor: pointer;
  display: flex;
  align-items: center;
}
.btn-login {
  width: 100%;
  background: var(--color-primary);
  color: #FFFFFF;
  border: none;
  padding: 12px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  cursor: pointer;
  margin-top: 6px;
  box-shadow: 0 4px 14px var(--color-primary-subtle);
}
.btn-login:hover:not(:disabled) {
  background: var(--color-primary-hover);
}
.login-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 28px;
  padding-top: 18px;
  border-top: 1px solid var(--border-subtle);
}
.btn-theme-toggle {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  color: var(--text-secondary);
  padding: 5px 10px;
  border-radius: 6px;
  font-size: 11px;
  cursor: pointer;
}
</style>
