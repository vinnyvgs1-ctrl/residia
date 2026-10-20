<template>
  <div class="biometria-page animate-fade-in">
    <!-- Header -->
    <div class="page-header">
      <div class="header-titles">
        <div class="badge-tag">
          <ScanFace :size="14" class="text-cyan-400" />
          <span>Control Físico & IoT</span>
        </div>
        <h1 class="page-title">Sincronización Biométrica Hikvision & iVMS-4200</h1>
        <p class="page-subtitle">
          Gestión centralizada de credenciales faciales, huellas y tarjetas RFID hacia terminales ISAPI de garita y pasarelas peatonales.
        </p>
      </div>

      <div class="header-actions">
        <button @click="descargarPlantillaIvms" class="btn-secondary" :disabled="exportando">
          <Download :size="16" />
          <span>{{ exportando ? 'Generando...' : 'Exportar Formato iVMS-4200' }}</span>
        </button>

        <button @click="abrirModalSync" class="btn-primary" :disabled="kpis.terminales_online === 0">
          <RefreshCw :size="16" :class="{ 'animate-spin': sincronizando }" />
          <span>Sincronizar Lote ISAPI</span>
        </button>
      </div>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-icon-wrap bg-cyan-500/10 text-cyan-400">
          <Users :size="20" />
        </div>
        <div class="kpi-data">
          <span class="kpi-label">Padrón de Habitantes</span>
          <span class="kpi-value">{{ kpis.total_residentes }}</span>
          <span class="kpi-desc">Residentes con acceso autorizado</span>
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-icon-wrap bg-emerald-500/10 text-emerald-400">
          <ScanFace :size="20" />
        </div>
        <div class="kpi-data">
          <span class="kpi-label">Enrolados Facialmente</span>
          <span class="kpi-value">{{ kpis.enrolados_facial }}</span>
          <span class="kpi-desc">{{ kpis.cobertura_facial_pct }}% cobertura total</span>
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-icon-wrap bg-purple-500/10 text-purple-400">
          <Fingerprint :size="20" />
        </div>
        <div class="kpi-data">
          <span class="kpi-label">Plantillas de Huella</span>
          <span class="kpi-value">{{ kpis.enrolados_huella }}</span>
          <span class="kpi-desc">Huellas digitales activas</span>
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-icon-wrap bg-blue-500/10 text-blue-400">
          <Cpu :size="20" />
        </div>
        <div class="kpi-data">
          <span class="kpi-label">Terminales Hardware</span>
          <span class="kpi-value">{{ kpis.terminales_online }} / {{ kpis.total_terminales }}</span>
          <span class="kpi-desc text-emerald-400">Terminales ISAPI en línea</span>
        </div>
      </div>
    </div>

    <!-- Terminal Status Banner -->
    <div class="terminal-banner">
      <div class="terminal-info">
        <div class="terminal-status-dot pulse-green"></div>
        <div>
          <span class="terminal-name">{{ dispositivoActivo?.disp_nombre || 'Terminal Facial Hikvision Garita Norte' }}</span>
          <span class="terminal-specs">IP: {{ dispositivoActivo?.disp_ip || '192.168.1.120' }} &bull; Protocolo: ISAPI Digest &bull; Modelo: {{ dispositivoActivo?.disp_modelo || 'DS-K1T671MF' }}</span>
        </div>
      </div>
      <div class="terminal-quick-actions">
        <span class="badge-online">ONLINE</span>
      </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="filter-bar">
      <div class="search-box">
        <Search :size="16" class="search-icon" />
        <input 
          v-model="filtroBusqueda" 
          type="text" 
          placeholder="Buscar por nombre, cédula o villa..."
          class="search-input"
        />
      </div>

      <div class="filter-actions">
        <select v-model="filtroEstado" class="filter-select">
          <option value="TODOS">Todos los estados</option>
          <option value="SINCRONIZADO">Sincronizados</option>
          <option value="PENDIENTE">Pendientes de enrolamiento</option>
        </select>
      </div>
    </div>

    <!-- Data Grid -->
    <div class="table-container">
      <div v-if="cargando" class="loading-state">
        <Loader2 :size="32" class="animate-spin text-cyan-400" />
        <p>Consultando estado de credenciales biométricas...</p>
      </div>

      <table v-else class="bio-table">
        <thead>
          <tr>
            <th>Habitante / Residente</th>
            <th>Predio Asignado</th>
            <th>Cédula / EmployeeNo</th>
            <th>Reconocimiento Facial</th>
            <th>Huella Dactilar</th>
            <th>Tarjeta RFID</th>
            <th class="text-right">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="res in residentesFiltrados" :key="res.persona_id" class="table-row">
            <td>
              <div class="user-cell">
                <div class="user-avatar">
                  {{ res.nombre_completo.charAt(0) }}
                </div>
                <div class="user-info">
                  <span class="user-name">{{ res.nombre_completo }}</span>
                  <span class="user-role">{{ res.tipo_relacion }} {{ res.es_principal ? '(Titular)' : '' }}</span>
                </div>
              </div>
            </td>

            <td>
              <span class="villa-badge">{{ res.inmueble }}</span>
            </td>

            <td>
              <span class="id-code">{{ res.identificacion }}</span>
            </td>

            <td>
              <div v-if="res.biometria.facial" class="status-badge badge-synced">
                <CheckCircle2 :size="14" />
                <span>Sincronizado</span>
              </div>
              <div v-else class="status-badge badge-pending">
                <AlertCircle :size="14" />
                <span>Pendiente</span>
              </div>
            </td>

            <td>
              <div v-if="res.biometria.huella" class="status-badge badge-synced">
                <CheckCircle2 :size="14" />
                <span>Enrolado</span>
              </div>
              <div v-else class="status-badge badge-neutral">
                <span>No registrada</span>
              </div>
            </td>

            <td>
              <span v-if="res.biometria.tarjeta?.numero" class="tag-code">
                TAG-{{ res.biometria.tarjeta.numero }}
              </span>
              <span v-else class="text-muted text-xs">Sin asignar</span>
            </td>

            <td class="text-right">
              <button 
                @click="sincronizarIndividual(res)" 
                class="btn-row-action"
                title="Sincronizar a terminal"
              >
                <RefreshCw :size="14" />
              </button>
            </td>
          </tr>

          <tr v-if="residentesFiltrados.length === 0">
            <td colspan="7" class="empty-state">
              <UserX :size="36" class="text-slate-600 mb-2" />
              <p>No se encontraron residentes con el filtro especificado.</p>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal de Sincronización en Lote ISAPI -->
    <div v-if="modalSync" class="modal-overlay" @click.self="modalSync = false">
      <div class="modal-box animate-scale-up">
        <div class="modal-header">
          <div class="modal-title-wrap">
            <ScanFace :size="20" class="text-cyan-400" />
            <h3 class="modal-title">Sincronización en Lote hacia Terminal ISAPI</h3>
          </div>
          <button @click="modalSync = false" class="modal-close-btn">&times;</button>
        </div>

        <div class="modal-body">
          <p class="modal-desc">
            Se enviará el comando ISAPI <code>/ISAPI/AccessControl/UserInfo/SetUp</code> con autenticación Digest a la terminal seleccionada para aprovisionar a los residentes autorizados.
          </p>

          <div class="form-group">
            <label class="form-label">Terminal de Destino:</label>
            <select v-model="terminalSeleccionada" class="form-select">
              <option v-for="d in dispositivos" :key="d.disp_id" :value="d.disp_id">
                {{ d.disp_nombre }} ({{ d.disp_ip }}) - {{ d.disp_estado_conexion }}
              </option>
            </select>
          </div>

          <div class="sync-summary-box">
            <div class="summary-item">
              <span class="sum-label">Residentes a procesar:</span>
              <span class="sum-val">{{ residentes.length }}</span>
            </div>
            <div class="summary-item">
              <span class="sum-label">Protocolo:</span>
              <span class="sum-val text-cyan-400">ISAPI HTTP Digest</span>
            </div>
            <div class="summary-item">
              <span class="sum-label">Modo:</span>
              <span class="sum-val text-emerald-400">Actualización y Registro</span>
            </div>
          </div>

          <!-- Progreso o Resultado -->
          <div v-if="resultadoSync" class="result-alert" :class="resultadoSync.fallidos === 0 ? 'alert-success' : 'alert-warning'">
            <CheckCircle2 v-if="resultadoSync.fallidos === 0" :size="18" />
            <AlertTriangle v-else :size="18" />
            <div>
              <strong>{{ resultadoSync.fallidos === 0 ? 'Sincronización Exitosa' : 'Sincronización con Observaciones' }}</strong>
              <p>{{ resultadoSync.exitosos }} residentes enrolados. Modo: {{ resultadoSync.modo }}</p>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button @click="modalSync = false" class="btn-secondary" :disabled="sincronizando">Cancelar</button>
          <button @click="ejecutarSincronizacionLote" class="btn-primary" :disabled="sincronizando">
            <RefreshCw :size="16" :class="{ 'animate-spin': sincronizando }" />
            <span>{{ sincronizando ? 'Sincronizando...' : 'Iniciar Sincronización' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { 
  ScanFace, Users, Fingerprint, Cpu, Download, RefreshCw, 
  Search, CheckCircle2, AlertCircle, AlertTriangle, Loader2, UserX 
} from 'lucide-vue-next';

const props = defineProps({
  urbanizacionId: {
    type: String,
    required: true
  }
});

const cargando = ref(true);
const sincronizando = ref(false);
const exportando = ref(false);
const modalSync = ref(false);
const terminalSeleccionada = ref(null);
const resultadoSync = ref(null);

const filtroBusqueda = ref('');
const filtroEstado = ref('TODOS');

const residentes = ref([]);
const dispositivos = ref([]);
const kpis = ref({
  total_residentes: 0,
  enrolados_facial: 0,
  enrolados_huella: 0,
  cobertura_facial_pct: 0,
  terminales_online: 0,
  total_terminales: 0
});

const dispositivoActivo = computed(() => {
  return dispositivos.value.find(d => d.disp_tipo === 'BIOMETRICO_FACIAL') || dispositivos.value[0];
});

const cargarDatos = async () => {
  cargando.value = true;
  try {
    const res = await axios.get('/api/biometria');
    if (res.data.success) {
      residentes.value = res.data.data.residentes;
      dispositivos.value = res.data.data.dispositivos;
      kpis.value = res.data.data.kpis;
      if (dispositivos.value.length > 0) {
        terminalSeleccionada.value = dispositivos.value[0].disp_id;
      }
    }
  } catch (error) {
    console.error('Error al cargar datos biométricos:', error);
  } finally {
    cargando.value = false;
  }
};

const residentesFiltrados = computed(() => {
  return residentes.value.filter(r => {
    const matchTxt = !filtroBusqueda.value || 
      r.nombre_completo.toLowerCase().includes(filtroBusqueda.value.toLowerCase()) ||
      r.identificacion.toLowerCase().includes(filtroBusqueda.value.toLowerCase()) ||
      r.inmueble.toLowerCase().includes(filtroBusqueda.value.toLowerCase());

    const matchEst = filtroEstado.value === 'TODOS' || 
      (filtroEstado.value === 'SINCRONIZADO' && r.biometria.facial) ||
      (filtroEstado.value === 'PENDIENTE' && !r.biometria.facial);

    return matchTxt && matchEst;
  });
});

const abrirModalSync = () => {
  resultadoSync.value = null;
  modalSync.value = true;
};

const ejecutarSincronizacionLote = async () => {
  sincronizando.value = true;
  resultadoSync.value = null;
  try {
    const res = await axios.post('/api/biometria/sync-isapi', {
      dispositivo_id: terminalSeleccionada.value,
      sync_todo: true
    });
    if (res.data.success) {
      resultadoSync.value = res.data.data;
      await cargarDatos();
    }
  } catch (error) {
    console.error('Error al sincronizar lote:', error);
    alert('Error al comunicarse con la terminal ISAPI: ' + (error.response?.data?.message || error.message));
  } finally {
    sincronizando.value = false;
  }
};

const sincronizarIndividual = async (residente) => {
  if (!dispositivoActivo.value) {
    alert('No hay terminales configuradas para sincronizar.');
    return;
  }
  try {
    const res = await axios.post('/api/biometria/sync-isapi', {
      dispositivo_id: dispositivoActivo.value.disp_id,
      persona_ids: [residente.persona_id]
    });
    if (res.data.success) {
      alert(`Credencial de ${residente.nombre_completo} sincronizada exitosamente.`);
      await cargarDatos();
    }
  } catch (error) {
    alert('Error al sincronizar credencial: ' + (error.response?.data?.message || error.message));
  }
};

const descargarPlantillaIvms = () => {
  exportando.value = true;
  window.location.href = '/api/biometria/export-ivms';
  setTimeout(() => {
    exportando.value = false;
  }, 2000);
};

onMounted(() => {
  cargarDatos();
});
</script>

<style scoped>
.biometria-page {
  padding: 1.5rem;
  max-width: 1400px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
  gap: 1rem;
}

.badge-tag {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 0.25rem 0.6rem;
  background: rgba(6, 182, 212, 0.1);
  border: 1px solid rgba(6, 182, 212, 0.25);
  border-radius: 9999px;
  color: #38bdf8;
  margin-bottom: 0.5rem;
}

.page-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: #f8fafc;
  letter-spacing: -0.02em;
  margin-bottom: 0.25rem;
}

.page-subtitle {
  color: #94a3b8;
  font-size: 0.875rem;
  max-width: 700px;
}

.header-actions {
  display: flex;
  gap: 0.75rem;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: #0284c7;
  color: #fff;
  padding: 0.6rem 1.2rem;
  border-radius: 0.5rem;
  font-weight: 600;
  font-size: 0.875rem;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary:hover:not(:disabled) {
  background: #0369a1;
  transform: translateY(-1px);
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-secondary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(30, 41, 59, 0.8);
  color: #cbd5e1;
  padding: 0.6rem 1.2rem;
  border-radius: 0.5rem;
  font-weight: 600;
  font-size: 0.875rem;
  border: 1px solid rgba(255, 255, 255, 0.1);
  cursor: pointer;
  transition: all 0.2s;
}

.btn-secondary:hover:not(:disabled) {
  background: rgba(51, 65, 85, 0.8);
  color: #fff;
}

.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.kpi-card {
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 0.75rem;
  padding: 1.25rem;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.kpi-icon-wrap {
  width: 44px;
  height: 44px;
  border-radius: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.kpi-data {
  display: flex;
  flex-direction: column;
}

.kpi-label {
  font-size: 0.75rem;
  color: #94a3b8;
  font-weight: 500;
}

.kpi-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #f8fafc;
  line-height: 1.2;
}

.kpi-desc {
  font-size: 0.75rem;
  color: #64748b;
}

.terminal-banner {
  background: linear-gradient(90deg, rgba(6, 182, 212, 0.08), rgba(15, 23, 42, 0.6));
  border: 1px solid rgba(6, 182, 212, 0.2);
  border-radius: 0.75rem;
  padding: 1rem 1.25rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.terminal-info {
  display: flex;
  align-items: center;
  gap: 0.875rem;
}

.terminal-status-dot {
  width: 10px;
  height: 10px;
  border-radius: 9999px;
  background: #10b981;
}

.pulse-green {
  box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
  70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
  100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}

.terminal-name {
  font-weight: 600;
  color: #f8fafc;
  display: block;
  font-size: 0.925rem;
}

.terminal-specs {
  font-size: 0.75rem;
  color: #94a3b8;
}

.badge-online {
  font-size: 0.7rem;
  font-weight: 700;
  background: rgba(16, 185, 129, 0.15);
  color: #34d399;
  border: 1px solid rgba(16, 185, 129, 0.3);
  padding: 0.2rem 0.5rem;
  border-radius: 0.25rem;
}

.filter-bar {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1rem;
}

.search-box {
  position: relative;
  flex: 1;
  max-width: 400px;
}

.search-icon {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  color: #64748b;
}

.search-input {
  width: 100%;
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 0.5rem;
  padding: 0.55rem 0.75rem 0.55rem 2.25rem;
  color: #f8fafc;
  font-size: 0.875rem;
}

.search-input:focus {
  outline: none;
  border-color: #0284c7;
}

.filter-select {
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 0.5rem;
  padding: 0.55rem 0.75rem;
  color: #f8fafc;
  font-size: 0.875rem;
}

.table-container {
  background: rgba(15, 23, 42, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 0.75rem;
  overflow: hidden;
}

.bio-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.bio-table th {
  background: rgba(30, 41, 59, 0.5);
  padding: 0.75rem 1rem;
  text-align: left;
  font-weight: 600;
  color: #94a3b8;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.bio-table td {
  padding: 0.875rem 1rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.03);
  color: #cbd5e1;
}

.table-row:hover td {
  background: rgba(255, 255, 255, 0.02);
}

.user-cell {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.user-avatar {
  width: 32px;
  height: 32px;
  border-radius: 9999px;
  background: #0284c7;
  color: #fff;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
}

.user-name {
  font-weight: 600;
  color: #f8fafc;
  display: block;
}

.user-role {
  font-size: 0.75rem;
  color: #64748b;
}

.villa-badge {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.08);
  padding: 0.2rem 0.5rem;
  border-radius: 0.25rem;
  font-weight: 600;
  font-size: 0.8rem;
  color: #e2e8f0;
}

.id-code {
  font-family: monospace;
  font-size: 0.825rem;
  color: #94a3b8;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  font-size: 0.75rem;
  font-weight: 600;
}

.badge-synced {
  background: rgba(16, 185, 129, 0.12);
  color: #34d399;
  border: 1px solid rgba(16, 185, 129, 0.25);
}

.badge-pending {
  background: rgba(245, 158, 11, 0.12);
  color: #fbbf24;
  border: 1px solid rgba(245, 158, 11, 0.25);
}

.badge-neutral {
  background: rgba(255, 255, 255, 0.05);
  color: #64748b;
}

.tag-code {
  font-family: monospace;
  font-size: 0.75rem;
  background: rgba(99, 102, 241, 0.12);
  color: #a5b4fc;
  padding: 0.15rem 0.4rem;
  border-radius: 0.2rem;
}

.btn-row-action {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: #94a3b8;
  width: 28px;
  height: 28px;
  border-radius: 0.35rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-row-action:hover {
  background: #0284c7;
  color: #fff;
  border-color: #0284c7;
}

.loading-state, .empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 1rem;
  color: #94a3b8;
}

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.75);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 50;
  padding: 1rem;
}

.modal-box {
  background: #0f172a;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 0.75rem;
  width: 100%;
  max-width: 500px;
  overflow: hidden;
}

.modal-header {
  padding: 1.25rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-title-wrap {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.modal-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: #f8fafc;
}

.modal-close-btn {
  background: none;
  border: none;
  color: #94a3b8;
  font-size: 1.5rem;
  cursor: pointer;
}

.modal-body {
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.modal-desc {
  font-size: 0.85rem;
  color: #94a3b8;
  line-height: 1.5;
}

.modal-desc code {
  background: rgba(255, 255, 255, 0.08);
  padding: 0.15rem 0.35rem;
  border-radius: 0.2rem;
  color: #38bdf8;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.form-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: #cbd5e1;
}

.form-select {
  background: rgba(30, 41, 59, 0.8);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 0.5rem;
  padding: 0.6rem;
  color: #f8fafc;
  font-size: 0.875rem;
}

.sync-summary-box {
  background: rgba(30, 41, 59, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 0.5rem;
  padding: 0.75rem 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  font-size: 0.825rem;
}

.sum-label {
  color: #94a3b8;
}

.sum-val {
  font-weight: 600;
  color: #f8fafc;
}

.result-alert {
  padding: 0.75rem 1rem;
  border-radius: 0.5rem;
  display: flex;
  gap: 0.75rem;
  font-size: 0.85rem;
}

.alert-success {
  background: rgba(16, 185, 129, 0.1);
  border: 1px solid rgba(16, 185, 129, 0.25);
  color: #34d399;
}

.alert-warning {
  background: rgba(245, 158, 11, 0.1);
  border: 1px solid rgba(245, 158, 11, 0.25);
  color: #fbbf24;
}

.modal-footer {
  padding: 1rem 1.25rem;
  border-top: 1px solid rgba(255, 255, 255, 0.05);
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}
</style>
