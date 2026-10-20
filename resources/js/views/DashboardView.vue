<template>
  <div class="dashboard-view">
    <!-- Encabezado de Dashboard -->
    <div class="view-header">
      <div>
        <h1 class="view-title">Dashboard Ejecutivo & Métricas en Vivo</h1>
        <p class="view-subtitle">Indicadores clave de desempeño (KPIs), semáforo de permanencia y flujo vehicular en tiempo real</p>
      </div>

      <div class="flex items-center gap-3">
        <span class="live-pill">
          <span class="live-dot"></span> Monitor Activo
        </span>
        <button @click="cargarMetricas" class="btn-refresh" :disabled="cargando">
          <RefreshCw :size="15" :class="{ 'animate-spin': cargando }" />
          <span>Actualizar</span>
        </button>
      </div>
    </div>

    <div v-if="cargando" class="loading-state">
      <Loader2 :size="36" class="animate-spin text-[var(--color-primary)] mb-3" />
      <p>Calculando métricas operativas de la urbanización...</p>
    </div>

    <div v-else class="space-y-6">
      <!-- 1. TARJETAS DE KPIS PRINCIPALES (GRID 4x) -->
      <div class="kpis-grid">
        <!-- KPI 1: Ingresos de Hoy -->
        <div class="kpi-card">
          <div class="kpi-top">
            <span class="kpi-label">Ingresos Registrados Hoy</span>
            <div class="kpi-icon-wrap bg-emerald-500/15 text-emerald-400">
              <UserCheck :size="20" />
            </div>
          </div>
          <div class="kpi-value text-white">{{ metricas.ingresos_hoy }}</div>
          <div class="kpi-meta text-emerald-400">
            <span>🚗 {{ metricas.vehiculares_hoy }} Vehiculares</span> &bull; 
            <span>🚶 {{ metricas.peatonales_hoy }} Peatonales</span>
          </div>
        </div>

        <!-- KPI 2: Activos Dentro Ahora -->
        <div class="kpi-card">
          <div class="kpi-top">
            <span class="kpi-label">Permanencia Activa</span>
            <div class="kpi-icon-wrap bg-[var(--color-primary-subtle)] text-[var(--color-primary)]">
              <Clock :size="20" />
            </div>
          </div>
          <div class="kpi-value text-[var(--color-primary)]">{{ metricas.dentro_actual }}</div>
          <div class="kpi-meta text-[var(--text-secondary)]">
            Personas / vehículos dentro del predio
          </div>
        </div>

        <!-- KPI 3: Alertas de Tiempo Excedido -->
        <div class="kpi-card" :class="{ 'border-amber-500/40': metricas.alertas_excedidas > 0 }">
          <div class="kpi-top">
            <span class="kpi-label">Visitas Excedidas (>4h)</span>
            <div class="kpi-icon-wrap bg-amber-500/15 text-amber-400">
              <Hourglass :size="20" />
            </div>
          </div>
          <div class="kpi-value" :class="metricas.alertas_excedidas > 0 ? 'text-amber-400' : 'text-white'">
            {{ metricas.alertas_excedidas }}
          </div>
          <div class="kpi-meta" :class="metricas.alertas_excedidas > 0 ? 'text-amber-400' : 'text-[var(--text-secondary)]'">
            {{ metricas.alertas_excedidas > 0 ? 'Requieren verificación de garita' : 'Tiempos en rango normal' }}
          </div>
        </div>

        <!-- KPI 4: Pases QR Vigentes -->
        <div class="kpi-card">
          <div class="kpi-top">
            <span class="kpi-label">Pases QR Vigentes</span>
            <div class="kpi-icon-wrap bg-purple-500/15 text-purple-400">
              <QrCode :size="20" />
            </div>
          </div>
          <div class="kpi-value text-purple-400">{{ metricas.pases_vigentes }}</div>
          <div class="kpi-meta text-[var(--text-secondary)]">
            Pre-autorizaciones listas para canjear
          </div>
        </div>
      </div>

      <!-- 2. SEGUNDA FILA: GRÁFICO DE FLUJO HORARIO Y PADRÓN -->
      <div class="dashboard-split-grid">
        <!-- Gráfico de Barras: Distribución Horaria de Tráfico -->
        <div class="chart-card">
          <div class="card-header-chart">
            <div class="flex items-center gap-2">
              <BarChart3 :size="18" class="text-[var(--color-primary)]" />
              <h2 class="text-sm font-bold text-[var(--text-primary)]">Distribución de Flujo Vehicular y Peatonal (Hoy)</h2>
            </div>
            <span class="text-xs text-[var(--text-secondary)]">Intervalos de 2 Horas</span>
          </div>

          <div class="chart-container">
            <div v-for="bar in metricas.distribucion_horas" :key="bar.intervalo" class="bar-col">
              <div class="bar-fill-track">
                <div 
                  class="bar-fill" 
                  :style="{ height: getBarHeight(bar.total) }"
                  :title="`${bar.total} accesos en ${bar.intervalo}`"
                >
                  <span v-if="bar.total > 0" class="bar-tooltip-count">{{ bar.total }}</span>
                </div>
              </div>
              <span class="bar-label">{{ bar.intervalo.split(' - ')[0] }}</span>
            </div>
          </div>
        </div>

        <!-- Tarjeta de Salud y Padrón de la Urbanización -->
        <div class="padron-kpi-card">
          <div class="card-header-chart">
            <div class="flex items-center gap-2">
              <ShieldCheck :size="18" class="text-[var(--color-primary)]" />
              <h2 class="text-sm font-bold text-[var(--text-primary)]">Padrón & Parámetros Operativos</h2>
            </div>
          </div>

          <div class="padron-stats-list">
            <div class="padron-stat-item">
              <div class="flex items-center gap-3">
                <div class="stat-icon-circle bg-blue-500/15 text-blue-400">
                  <Home :size="16" />
                </div>
                <div>
                  <div class="stat-name">Inmuebles Registrados</div>
                  <div class="stat-desc">Villas, Casas y Departamentos</div>
                </div>
              </div>
              <div class="stat-num">{{ metricas.inmuebles_total }}</div>
            </div>

            <div class="padron-stat-item">
              <div class="flex items-center gap-3">
                <div class="stat-icon-circle bg-emerald-500/15 text-emerald-400">
                  <Users :size="16" />
                </div>
                <div>
                  <div class="stat-name">Habitantes / Residentes</div>
                  <div class="stat-desc">Población activa vinculada</div>
                </div>
              </div>
              <div class="stat-num">{{ metricas.residentes_total }}</div>
            </div>

            <div class="padron-stat-item">
              <div class="flex items-center gap-3">
                <div class="stat-icon-circle bg-red-500/15 text-red-400">
                  <ShieldAlert :size="16" />
                </div>
                <div>
                  <div class="stat-name">Vetos Activos (Lista Negra)</div>
                  <div class="stat-desc">Personas o placas restringidas</div>
                </div>
              </div>
              <div class="stat-num text-red-400">{{ metricas.vetados_total }}</div>
            </div>
          </div>

          <!-- Acceso Rápido a Operaciones -->
          <div class="quick-access-box">
            <button @click="$emit('cambiarVista', 'garita')" class="btn-quick-primary">
              <ShieldAlert :size="15" /> Ir a Control de Garita
            </button>
            <button @click="$emit('cambiarVista', 'reportes')" class="btn-quick-secondary">
              <BarChart3 :size="15" /> Consultar Reportes
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import { 
  RefreshCw, Loader2, UserCheck, Clock, Hourglass, QrCode, 
  BarChart3, ShieldCheck, Home, Users, ShieldAlert 
} from 'lucide-vue-next';

const props = defineProps({
  urbanizacionId: { type: String, default: 'ccce9ae7-ca93-48e7-8577-0dd546dda010' }
});

const emit = defineEmits(['cambiarVista']);

const cargando = ref(true);
const metricas = reactive({
  ingresos_hoy: 0,
  vehiculares_hoy: 0,
  peatonales_hoy: 0,
  dentro_actual: 0,
  alertas_excedidas: 0,
  pases_vigentes: 0,
  inmuebles_total: 0,
  residentes_total: 0,
  vetados_total: 0,
  distribucion_horas: []
});

const maxTraficoHora = ref(1);

const cargarMetricas = async () => {
  cargando.value = true;
  try {
    const token = localStorage.getItem('residia_token');
    const res = await axios.get('/api/dashboard/metricas', {
      headers: {
        'Authorization': `Bearer ${token}`,
        'X-Urbanizacion-Id': props.urbanizacionId
      }
    });

    if (res.data.success && res.data.metricas) {
      Object.assign(metricas, res.data.metricas);
      
      const totales = metricas.distribucion_horas.map(b => b.total);
      maxTraficoHora.value = Math.max(...totales, 5);
    }
  } catch (e) {
    console.error('Error cargando métricas:', e);
  } finally {
    cargando.value = false;
  }
};

const getBarHeight = (total) => {
  if (!total || total === 0) return '6px';
  const pct = Math.min(Math.round((total / maxTraficoHora.value) * 100), 100);
  return `${Math.max(pct, 12)}%`;
};

onMounted(() => {
  cargarMetricas();
});
</script>

<style scoped>
.dashboard-view {
  padding: 16px 24px;
  width: 100%;
}
.view-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}
.view-title {
  font-size: 20px;
  font-weight: 700;
  color: var(--text-primary);
}
.view-subtitle {
  font-size: 13px;
  color: var(--text-secondary);
  margin-top: 4px;
}
.live-pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  padding: 6px 14px;
  border-radius: 9999px;
  font-size: 12px;
  font-weight: 600;
  color: var(--text-secondary);
}
.live-dot {
  width: 8px;
  height: 8px;
  background: var(--color-primary);
  border-radius: 50%;
  box-shadow: 0 0 8px var(--color-primary);
}
.btn-refresh {
  display: flex;
  align-items: center;
  gap: 8px;
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  color: var(--text-primary);
  padding: 7px 14px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
}
.btn-refresh:hover {
  border-color: var(--color-primary);
  color: var(--color-primary);
}

/* KPIS GRID */
.kpis-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 16px;
}
.kpi-card {
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 12px;
  padding: 18px 20px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}
.kpi-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.kpi-label {
  font-size: 12px;
  font-weight: 600;
  color: var(--text-secondary);
}
.kpi-icon-wrap {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.kpi-value {
  font-size: 32px;
  font-weight: 800;
  line-height: 1.1;
  margin: 12px 0 6px;
}
.kpi-meta {
  font-size: 11px;
}

/* SEGUNDA FILA */
.dashboard-split-grid {
  display: grid;
  grid-template-columns: 1.8fr 1.2fr;
  gap: 20px;
}
@media (max-width: 960px) {
  .dashboard-split-grid {
    grid-template-columns: 1fr;
  }
}

.chart-card, .padron-kpi-card {
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}
.card-header-chart {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 12px;
  border-bottom: 1px solid var(--border-subtle);
}
.chart-container {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 12px;
  height: 200px;
  padding-top: 20px;
}
.bar-col {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  height: 100%;
}
.bar-fill-track {
  width: 100%;
  max-width: 32px;
  height: 170px;
  background: var(--bg-primary);
  border-radius: 6px;
  display: flex;
  align-items: flex-end;
  overflow: hidden;
}
.bar-fill {
  width: 100%;
  background: linear-gradient(180deg, var(--color-primary) 0%, rgba(114, 193, 165, 0.4) 100%);
  border-radius: 6px 6px 0 0;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  position: relative;
  transition: height 0.4s ease;
}
.bar-tooltip-count {
  font-size: 10px;
  font-weight: 700;
  color: #FFFFFF;
  margin-top: 4px;
}
.bar-label {
  font-size: 10px;
  color: var(--text-muted);
  margin-top: 8px;
  font-mono: true;
}

.padron-stats-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.padron-stat-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: var(--bg-primary);
  border: 1px solid var(--border-subtle);
  border-radius: 10px;
  padding: 12px 16px;
}
.stat-icon-circle {
  width: 34px;
  height: 34px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.stat-name {
  font-size: 13px;
  font-weight: 600;
  color: var(--text-primary);
}
.stat-desc {
  font-size: 11px;
  color: var(--text-muted);
}
.stat-num {
  font-size: 20px;
  font-weight: 800;
  color: var(--text-primary);
}

.quick-access-box {
  display: flex;
  gap: 12px;
  margin-top: 20px;
  padding-top: 14px;
  border-top: 1px solid var(--border-subtle);
}
.btn-quick-primary, .btn-quick-secondary {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 10px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  border: none;
  transition: all 0.2s;
}
.btn-quick-primary {
  background: var(--color-primary);
  color: #FFFFFF;
}
.btn-quick-primary:hover {
  background: var(--color-primary-hover);
}
.btn-quick-secondary {
  background: var(--bg-primary);
  border: 1px solid var(--border-subtle);
  color: var(--text-primary);
}
.btn-quick-secondary:hover {
  border-color: var(--color-primary);
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px 20px;
  color: var(--text-muted);
  font-size: 13px;
}
</style>
