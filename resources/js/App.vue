<template>
  <div class="app-layout">
    <LoginView v-if="!authStore.isAuthenticated" />

    <template v-else>
      <Navbar :vista-actual="vistaActual" @cambiar-vista="v => vistaActual = v" />

      <main class="main-content">
        <!-- 0. Dashboard Ejecutivo -->
        <DashboardView 
          v-if="vistaActual === 'dashboard'" 
          :urbanizacion-id="sedeActiva?.urb_id || 'ccce9ae7-ca93-48e7-8577-0dd546dda010'" 
          @cambiar-vista="v => vistaActual = v"
        />

        <!-- 1. Operación Garita -->
        <GaritaView 
          v-else-if="vistaActual === 'garita'" 
          :urbanizacion-activa="sedeActiva" 
        />

        <!-- 1.1 Portal Móvil / Residente (Mi Hogar & Visitas QR) -->
        <ResidentePortalView
          v-else-if="vistaActual === 'portal.residente'"
          :urbanizacion-id="sedeActiva?.urb_id || 'ccce9ae7-ca93-48e7-8577-0dd546dda010'"
        />

        <!-- 2. Grupo Administración -->
        <UrbanizacionesView 
          v-else-if="vistaActual === 'urbanizaciones'" 
          @cambiar-vista="v => vistaActual = v"
          @cambiar-sede="s => sedeActiva = s"
        />

        <InmueblesView 
          v-else-if="vistaActual === 'inmuebles'" 
          :urbanizacion-id="sedeActiva?.urb_id || 'ccce9ae7-ca93-48e7-8577-0dd546dda010'"
        />

                <AlicuotasView 
          v-else-if="vistaActual === 'alicuotas'" 
          :urbanizacion-id="sedeActiva?.urb_id || 'ccce9ae7-ca93-48e7-8577-0dd546dda010'"
        />

                <AreasSocialesView 
          v-else-if="vistaActual === 'areas-sociales'" 
          :urbanizacion-id="sedeActiva?.urb_id || 'ccce9ae7-ca93-48e7-8577-0dd546dda010'"
        />

        <ComunicadosVotacionesView 
          v-else-if="vistaActual === 'votaciones' || vistaActual === 'comunicados'" 
          :urbanizacion-id="sedeActiva?.urb_id || 'ccce9ae7-ca93-48e7-8577-0dd546dda010'"
        />

        <BiometriaView 
          v-else-if="vistaActual === 'biometria'" 
          :urbanizacion-id="sedeActiva?.urb_id || 'ccce9ae7-ca93-48e7-8577-0dd546dda010'"
        />

        <MantenimientoView 
          v-else-if="vistaActual === 'mantenimiento'" 
          :urbanizacion-id="sedeActiva?.urb_id || 'ccce9ae7-ca93-48e7-8577-0dd546dda010'"
        />

        <PersonasView 
          v-else-if="vistaActual === 'personas'" 
        />

        <!-- 3. Grupo Seguridad & Auditoría -->
        <SeguridadView 
          v-else-if="vistaActual === 'usuarios' || vistaActual === 'roles'" 
          :urbanizacion-id="sedeActiva?.urb_id || 'ccce9ae7-ca93-48e7-8577-0dd546dda010'"
          :subtab-inicial="vistaActual === 'roles' ? 'roles' : 'usuarios'"
        />

        <VistasAdminView 
          v-else-if="vistaActual === 'vistas'" 
        />

        <AuditoriaView 
          v-else-if="vistaActual === 'auditoria'" 
          :urbanizacion-id="sedeActiva?.urb_id || 'ccce9ae7-ca93-48e7-8577-0dd546dda010'"
        />

        <!-- 4. Reportes -->
        <ReportesView 
          v-else-if="vistaActual === 'reportes'" 
          :urbanizacion-id="sedeActiva?.urb_id || 'ccce9ae7-ca93-48e7-8577-0dd546dda010'"
        />
      </main>

    <!-- Banner PWA & Notificaciones Web Push -->
    <PwaInstallBanner />
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from './stores/auth';
import { useThemeStore } from './stores/theme';
import LoginView from './views/LoginView.vue';
import Navbar from './components/Navbar.vue';
import DashboardView from './views/DashboardView.vue';
import GaritaView from './views/GaritaView.vue';
import InmueblesView from './views/InmueblesView.vue';
import AlicuotasView from './views/AlicuotasView.vue';
import AreasSocialesView from './views/AreasSocialesView.vue'
import ComunicadosVotacionesView from './views/ComunicadosVotacionesView.vue'
import BiometriaView from './views/BiometriaView.vue'
import MantenimientoView from './views/MantenimientoView.vue'
import PwaInstallBanner from './components/PwaInstallBanner.vue';
import PersonasView from './views/PersonasView.vue';
import UrbanizacionesView from './views/UrbanizacionesView.vue';
import SeguridadView from './views/SeguridadView.vue';
import VistasAdminView from './views/VistasAdminView.vue';
import AuditoriaView from './views/AuditoriaView.vue';
import ReportesView from './views/ReportesView.vue';
import ResidentePortalView from './views/ResidentePortalView.vue';

const authStore = useAuthStore();
const themeStore = useThemeStore();

const vistaActual = ref('dashboard');
const sedeActiva = ref({
  urb_id: 'ccce9ae7-ca93-48e7-8577-0dd546dda010',
  urb_nombre: 'Urbanización Piloto Robledal',
  urb_codigo: 'URB-ROBLEDAL-01'
});

onMounted(() => {
  authStore.initAuth();
  themeStore.fetchTemas();

  // Si el usuario es un residente o no tiene permiso de dashboard, redirigir a su vista permitida
  if (!authStore.puedeVerVista('dashboard')) {
    if (authStore.puedeVerVista('portal.residente')) {
      vistaActual.value = 'portal.residente';
    } else if (authStore.puedeVerVista('garita')) {
      vistaActual.value = 'garita';
    }
  }
});
</script>

<style scoped>
.app-layout {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}
.main-content {
  flex: 1;
}
</style>
