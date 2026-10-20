<template>
  <div class="auditoria-view">
    <div class="view-header">
      <div>
        <h1 class="view-title">Bitácora de Auditoría Integral (CDC)</h1>
        <p class="view-subtitle">Change Data Capture transaccional con análisis forense de diffs y trazabilidad de operaciones</p>
      </div>

      <button @click="cargarLogs" class="btn-refresh" :disabled="cargando">
        <RefreshCw :size="15" :class="{ 'animate-spin': cargando }" />
        <span>Actualizar Bitácora</span>
      </button>
    </div>

    <!-- Barra de Filtros -->
    <div class="filter-card">
      <div class="grid-filters">
        <div class="filter-group">
          <label>Tipo de Evento</label>
          <select v-model="filtros.evento" @change="cargarLogs" class="filter-select">
            <option value="">Todos los Eventos</option>
            <option value="INSERT">INSERT (Creaciones)</option>
            <option value="UPDATE">UPDATE (Modificaciones)</option>
            <option value="DELETE">DELETE (Eliminaciones)</option>
            <option value="ACCESS_ENTRY">ACCESS_ENTRY (Ingreso Garita)</option>
            <option value="ACCESS_EXIT">ACCESS_EXIT (Salida Garita)</option>
            <option value="BLACKLIST_ENTRY_CREATED">BLACKLIST_ENTRY_CREATED (Veto)</option>
            <option value="QR_INVITATION_CREATED">QR_INVITATION_CREATED (Pase QR)</option>
            <option value="QR_PASS_REDEEMED">QR_PASS_REDEEMED (Canje QR)</option>
            <option value="EXPORT_PDF">EXPORT_PDF (Descarga PDF)</option>
            <option value="EXPORT_EXCEL">EXPORT_EXCEL (Descarga Excel)</option>
          </select>
        </div>

        <div class="filter-group">
          <label>Tabla / Recurso</label>
          <select v-model="filtros.tabla" @change="cargarLogs" class="filter-select">
            <option value="">Todas las Tablas</option>
            <option value="op_control_accesos">op_control_accesos (Garita)</option>
            <option value="op_lista_negra">op_lista_negra (Vetos)</option>
            <option value="op_invitaciones_qr">op_invitaciones_qr (Pases QR)</option>
            <option value="core_inmuebles">core_inmuebles (Viviendas)</option>
            <option value="core_personas">core_personas (Padrón)</option>
            <option value="core_urbanizaciones">core_urbanizaciones (Sedes)</option>
            <option value="sec_users">sec_users (Cuentas)</option>
            <option value="sec_roles">sec_roles (Roles)</option>
          </select>
        </div>

        <div class="filter-group flex-2">
          <label>Búsqueda Rápida</label>
          <input 
            v-model="filtros.buscar" 
            @input="debounceBuscar" 
            placeholder="Buscar por usuario, IP, tabla o ID de registro..." 
            class="filter-input"
          />
        </div>
      </div>
    </div>

    <!-- Listado de Logs CDC -->
    <div class="logs-card">
      <div v-if="cargando" class="loading-state">
        <Loader2 :size="32" class="animate-spin text-[var(--color-primary)] mb-2" />
        <p>Extrayendo bitácora transaccional desde PostgreSQL...</p>
      </div>

      <div v-else-if="logs.length === 0" class="empty-state">
        <ShieldCheck :size="40" class="text-[var(--text-muted)] opacity-40 mb-2" />
        <p>No se encontraron registros de auditoría con los filtros aplicados.</p>
      </div>

      <div v-else class="logs-timeline">
        <div v-for="log in logs" :key="log.aud_id" class="log-entry">
          <div class="log-indicator">
            <span :class="getEventBadgeClass(log.aud_evento)" class="event-badge">
              {{ log.aud_evento }}
            </span>
            <span class="log-time">{{ formatHora(log.aud_created_at) }}</span>
          </div>

          <div class="log-body">
            <div class="log-main-info">
              <span class="font-semibold text-xs text-[var(--text-primary)]">
                Recurso: <span class="font-mono text-[var(--color-primary)]">{{ log.aud_tabla }}</span> #{{ log.aud_registro_id || 'N/A' }}
              </span>
              <div class="flex items-center gap-3">
                <span class="text-xs text-[var(--text-secondary)]">
                  Por: <strong class="text-[var(--text-primary)]">{{ log.aud_user_nombre || 'SISTEMA' }}</strong> &bull; IP: <span class="font-mono">{{ log.aud_ip_address || '127.0.0.1' }}</span>
                </span>
                <button @click="abrirModalDiff(log)" class="btn-inspect-diff" title="Abrir Visor Forense de Cambios">
                  <ScanText :size="13" /> Inspeccionar Diff
                </button>
              </div>
            </div>

            <!-- Previsualización Rápida Inline de Cambios -->
            <div v-if="log.aud_diff" class="diff-box">
              <div class="diff-grid">
                <div v-for="(val, campo) in parseJson(log.aud_diff)" :key="campo" class="diff-item">
                  <span class="diff-field font-mono">{{ campo }}:</span>
                  <span class="diff-old line-through">{{ formatShortValue(val.anterior) }}</span>
                  <span class="diff-arrow">&rarr;</span>
                  <span class="diff-new font-semibold">{{ formatShortValue(val.nuevo) }}</span>
                </div>
              </div>
            </div>

            <div v-else-if="log.aud_new_values" class="diff-box">
              <div class="diff-grid">
                <div v-for="(val, campo) in parseJson(log.aud_new_values)" :key="campo" class="diff-item">
                  <span class="diff-field font-mono">{{ campo }}:</span>
                  <span class="diff-new">{{ formatShortValue(val) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL VISOR INTERACTIVO FORENSE DE DIFFS -->
    <div v-if="modalLog" class="modal-overlay" @click.self="modalLog = null">
      <div class="modal-box modal-diff-viewer">
        <div class="modal-header">
          <div class="flex items-center gap-2">
            <Layers :size="20" class="text-[var(--color-primary)]" />
            <div>
              <h3 class="modal-title">Inspección Forense de Registro</h3>
              <p class="text-xs text-[var(--text-secondary)]">Evento: {{ modalLog.aud_evento }} &bull; {{ modalLog.aud_tabla }} #{{ modalLog.aud_registro_id }}</p>
            </div>
          </div>
          <button @click="modalLog = null" class="btn-close">&times;</button>
        </div>

        <div class="modal-body space-y-4">
          <!-- Metadatos del Evento -->
          <div class="meta-grid">
            <div class="meta-item">
              <span class="meta-label">Usuario:</span>
              <span class="meta-value">{{ modalLog.aud_user_nombre || 'SISTEMA' }}</span>
            </div>
            <div class="meta-item">
              <span class="meta-label">Dirección IP:</span>
              <span class="meta-value font-mono">{{ modalLog.aud_ip_address || '127.0.0.1' }}</span>
            </div>
            <div class="meta-item">
              <span class="meta-label">Fecha y Hora:</span>
              <span class="meta-value font-mono">{{ formatHora(modalLog.aud_created_at) }}</span>
            </div>
            <div class="meta-item">
              <span class="meta-label">Agente de Navegador:</span>
              <span class="meta-value text-[11px] truncate">{{ modalLog.aud_user_agent || 'Mozilla/5.0...' }}</span>
            </div>
          </div>

          <!-- Pestañas de Vista Comparativa -->
          <div class="diff-tabs">
            <button 
              :class="tabDiff === 'comparativa' ? 'dtab-active' : 'dtab-inactive'" 
              @click="tabDiff = 'comparativa'"
            >
              <GitCompare :size="14" /> Comparativa Lado a Lado (Diff)
            </button>
            <button 
              :class="tabDiff === 'json' ? 'dtab-active' : 'dtab-inactive'" 
              @click="tabDiff = 'json'"
            >
              <Code2 :size="14" /> JSON Completo
            </button>
          </div>

          <!-- LADO A LADO: ANTERIOR VS NUEVO -->
          <div v-if="tabDiff === 'comparativa'" class="side-by-side-grid">
            <!-- Columna Anterior -->
            <div class="diff-column col-old">
              <div class="col-title text-red-400">
                <MinusCircle :size="14" /> Estado Anterior (Old Snapshot)
              </div>
              <div v-if="!modalLog.aud_old_values" class="no-diff-msg">
                (Registro nuevo, sin estado anterior)
              </div>
              <div v-else class="json-table">
                <div v-for="(v, k) in parseJson(modalLog.aud_old_values)" :key="k" class="row-entry">
                  <span class="field-k">{{ k }}:</span>
                  <span class="field-v line-through">{{ JSON.stringify(v) }}</span>
                </div>
              </div>
            </div>

            <!-- Columna Nuevo -->
            <div class="diff-column col-new">
              <div class="col-title text-green-400">
                <PlusCircle :size="14" /> Estado Nuevo (New Snapshot)
              </div>
              <div v-if="!modalLog.aud_new_values" class="no-diff-msg">
                (Registro eliminado)
              </div>
              <div v-else class="json-table">
                <div v-for="(v, k) in parseJson(modalLog.aud_new_values)" :key="k" class="row-entry">
                  <span class="field-k">{{ k }}:</span>
                  <span class="field-v font-semibold">{{ JSON.stringify(v) }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- JSON PURO -->
          <div v-else class="raw-json-viewer">
            <pre>{{ formatJson(modalLog) }}</pre>
          </div>
        </div>

        <div class="modal-footer">
          <button @click="modalLog = null" class="btn-primary">
            Cerrar Visor
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import { 
  RefreshCw, ShieldCheck, Loader2, ScanText, Layers, GitCompare, 
  Code2, PlusCircle, MinusCircle 
} from 'lucide-vue-next';

const props = defineProps({
  urbanizacionId: { type: String, default: 'ccce9ae7-ca93-48e7-8577-0dd546dda010' }
});

const cargando = ref(true);
const logs = ref([]);
const modalLog = ref(null);
const tabDiff = ref('comparativa');

const filtros = reactive({
  evento: '',
  tabla: '',
  buscar: '',
});

let debounceTimer = null;
const debounceBuscar = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    cargarLogs();
  }, 400);
};

const cargarLogs = async () => {
  cargando.value = true;
  try {
    const params = new URLSearchParams();
    if (filtros.evento) params.append('evento', filtros.evento);
    if (filtros.tabla) params.append('tabla', filtros.tabla);
    if (filtros.buscar) params.append('buscar', filtros.buscar);

    const token = localStorage.getItem('residia_token');
    const res = await axios.get(`/api/auditoria/logs?${params.toString()}`, {
      headers: { 
        'Authorization': `Bearer ${token}`,
        'X-Urbanizacion-Id': props.urbanizacionId 
      }
    });

    if (res.data.success) {
      logs.value = res.data.data.data || res.data.data;
    }
  } catch (e) {
    console.error('Error cargando auditoria:', e);
  } finally {
    cargando.value = false;
  }
};

const abrirModalDiff = (log) => {
  modalLog.value = log;
  tabDiff.value = 'comparativa';
};

const getEventBadgeClass = (ev) => {
  if (ev === 'INSERT') return 'badge-insert';
  if (ev === 'UPDATE') return 'badge-update';
  if (ev === 'DELETE') return 'badge-delete';
  if (ev?.startsWith('EXPORT')) return 'badge-export';
  if (ev?.startsWith('QR')) return 'badge-qr';
  if (ev?.startsWith('BLACKLIST')) return 'badge-black';
  return 'badge-insert';
};

const formatHora = (str) => {
  if (!str) return '';
  const d = new Date(str);
  return d.toLocaleString([], { dateStyle: 'short', timeStyle: 'medium' });
};

const parseJson = (val) => {
  if (typeof val === 'object' && val !== null) return val;
  try { return JSON.parse(val); } catch (e) { return {}; }
};

const formatShortValue = (val) => {
  if (val === null || val === undefined) return 'null';
  if (typeof val === 'object') return JSON.stringify(val);
  return String(val);
};

const formatJson = (val) => {
  return JSON.stringify(val, null, 2);
};

onMounted(() => {
  cargarLogs();
});
</script>

<style scoped>
.auditoria-view {
  padding: 16px 24px;
  width: 100%; 
  margin: 0;
}
.view-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
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
.btn-refresh {
  display: flex;
  align-items: center;
  gap: 8px;
  background: var(--bg-card);
  border: 1px solid var(--border-subtle);
  color: var(--text-primary);
  padding: 8px 14px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-refresh:hover {
  border-color: var(--color-primary);
  color: var(--color-primary);
}
.filter-card {
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 12px;
  padding: 16px 20px;
  margin-bottom: 20px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
}
.grid-filters {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
}
.filter-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
  flex: 1;
  min-width: 180px;
}
.filter-group.flex-2 {
  flex: 2;
  min-width: 250px;
}
.filter-group label {
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--text-secondary);
}
.filter-select, .filter-input {
  background: var(--bg-primary);
  border: 1px solid var(--border-subtle);
  color: var(--text-primary);
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 13px;
  outline: none;
  width: 100%;
}
.filter-select:focus, .filter-input:focus {
  border-color: var(--color-primary);
}

.logs-card {
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
}
.logs-timeline {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.log-entry {
  display: grid;
  grid-template-columns: 180px 1fr;
  gap: 16px;
  background: var(--bg-primary);
  border: 1px solid var(--border-subtle);
  border-radius: 10px;
  padding: 12px 16px;
  transition: all 0.2s;
}
.log-entry:hover {
  border-color: rgba(114, 193, 165, 0.3);
}
@media (max-width: 800px) {
  .log-entry {
    grid-template-columns: 1fr;
  }
}
.log-indicator {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.event-badge {
  font-size: 10px;
  font-weight: 700;
  padding: 3px 8px;
  border-radius: 4px;
  letter-spacing: 0.05em;
  text-align: center;
  display: inline-block;
  width: fit-content;
}
.badge-insert { background: var(--color-primary-subtle); color: var(--color-primary); }
.badge-update { background: rgba(245, 158, 11, 0.15); color: #F59E0B; }
.badge-delete { background: rgba(239, 68, 68, 0.15); color: #EF4444; }
.badge-export { background: rgba(56, 189, 248, 0.15); color: #38BDF8; }
.badge-qr { background: rgba(168, 85, 247, 0.15); color: #A855F7; }
.badge-black { background: rgba(239, 68, 68, 0.2); color: #F87171; }

.log-time {
  font-size: 11px;
  color: var(--text-muted);
  font-family: monospace;
}
.log-body {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.log-main-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
}
.btn-inspect-diff {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  color: var(--color-primary);
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-inspect-diff:hover {
  background: var(--color-primary);
  color: #FFFFFF;
}

.diff-box {
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 8px;
  padding: 8px 12px;
}
.diff-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}
.diff-item {
  font-size: 11px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}
.diff-field { color: var(--color-primary); font-weight: 600; }
.diff-old { color: #EF4444; }
.diff-arrow { color: var(--text-muted); }
.diff-new { color: #10B981; }

/* MODAL DIFF FORENSE */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.75);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  padding: 20px;
}
.modal-diff-viewer {
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 14px;
  width: 100%;
  max-width: 860px;
  padding: 24px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
  max-height: 90vh;
  display: flex;
  flex-direction: column;
}
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 14px;
  border-bottom: 1px solid var(--border-subtle);
}
.modal-title {
  font-size: 16px;
  font-weight: 700;
  color: var(--text-primary);
}
.btn-close {
  background: transparent;
  border: none;
  font-size: 20px;
  color: var(--text-muted);
  cursor: pointer;
}
.modal-body {
  overflow-y: auto;
  padding: 16px 0;
  flex: 1;
}
.meta-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 10px;
  background: var(--bg-primary);
  padding: 12px 16px;
  border-radius: 8px;
  border: 1px solid var(--border-subtle);
}
.meta-item {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.meta-label {
  font-size: 10px;
  text-transform: uppercase;
  color: var(--text-muted);
  font-weight: 600;
}
.meta-value {
  font-size: 12px;
  color: var(--text-primary);
  font-weight: 500;
}
.diff-tabs {
  display: flex;
  gap: 8px;
  border-bottom: 1px solid var(--border-subtle);
  padding-bottom: 8px;
}
.dtab-active, .dtab-inactive {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  border: none;
  cursor: pointer;
}
.dtab-active { background: var(--color-primary); color: #FFFFFF; }
.dtab-inactive { background: transparent; color: var(--text-secondary); }

.side-by-side-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}
@media (max-width: 700px) {
  .side-by-side-grid {
    grid-template-columns: 1fr;
  }
}
.diff-column {
  background: var(--bg-primary);
  border: 1px solid var(--border-subtle);
  border-radius: 10px;
  padding: 14px;
}
.col-old { border-color: rgba(239, 68, 68, 0.2); }
.col-new { border-color: rgba(16, 185, 129, 0.2); }
.col-title {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 700;
  margin-bottom: 10px;
}
.json-table {
  display: flex;
  flex-direction: column;
  gap: 6px;
  max-height: 280px;
  overflow-y: auto;
}
.row-entry {
  font-size: 11px;
  font-family: monospace;
  display: flex;
  flex-direction: column;
  padding: 4px 0;
  border-bottom: 1px solid var(--border-subtle);
}
.field-k { color: var(--color-primary); font-weight: 600; }
.field-v { color: var(--text-secondary); word-break: break-all; }
.no-diff-msg {
  font-size: 12px;
  color: var(--text-muted);
  font-style: italic;
  padding: 20px 0;
  text-align: center;
}
.raw-json-viewer pre {
  background: var(--bg-primary);
  border: 1px solid var(--border-subtle);
  border-radius: 8px;
  padding: 14px;
  font-size: 11px;
  font-family: monospace;
  color: var(--text-secondary);
  max-height: 300px;
  overflow-y: auto;
}
.modal-footer {
  display: flex;
  justify-content: flex-end;
  padding-top: 14px;
  border-top: 1px solid var(--border-subtle);
}
.btn-primary {
  background: var(--color-primary);
  color: #FFFFFF;
  padding: 8px 16px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  border: none;
  cursor: pointer;
}
.loading-state, .empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  color: var(--text-muted);
  font-size: 13px;
}
</style>