<template>
  <div class="reportes-view">
    <div class="view-header">
      <div>
        <h1 class="view-title">Centro de Reportes & Estadísticas</h1>
        <p class="view-subtitle">Consultas operativas, filtros dinámicos y exportación con auditoría estricta de impresión</p>
      </div>

      <!-- Acciones de Exportación con permiso 'print' -->
      <div class="export-actions">
        <button @click="imprimirReportePdf" class="btn-export btn-pdf" title="Generar Informe Oficial en PDF">
          <FileText :size="15" />
          <span>Imprimir / PDF Oficial</span>
        </button>
        <button @click="descargarExcel" :disabled="exportando" class="btn-export btn-excel" title="Descargar Libro en Excel (CSV)">
          <Table :size="15" />
          <span>{{ exportando ? 'Auditando...' : 'Descargar Excel' }}</span>
        </button>
      </div>
    </div>

    <!-- Filtros de Consulta -->
    <div class="filter-card">
      <div class="grid-filters">
        <div class="filter-group">
          <label>Estado de Acceso</label>
          <select v-model="filtros.estado" @change="cargarReporte" class="filter-select">
            <option value="">Todos los Estados</option>
            <option value="DENTRO">Actualmente Dentro</option>
            <option value="SALIO">Salida Registrada</option>
          </select>
        </div>

        <div class="filter-group">
          <label>Tipo de Ingreso</label>
          <select v-model="filtros.tipo_ingreso" @change="cargarReporte" class="filter-select">
            <option value="">Vehicular y Peatonal</option>
            <option value="VEHICULAR">Solo Vehicular</option>
            <option value="PEATONAL">Solo Peatonal</option>
          </select>
        </div>

        <div class="filter-group">
          <label>Placa Vehicular</label>
          <input 
            v-model="filtros.placa" 
            @input="debounceBuscar" 
            placeholder="Buscar por placa..." 
            class="filter-input uppercase"
          />
        </div>

        <div class="filter-group">
          <label>Fecha Desde</label>
          <input type="date" v-model="filtros.fecha_desde" @change="cargarReporte" class="filter-input" />
        </div>

        <div class="filter-group">
          <label>Fecha Hasta</label>
          <input type="date" v-model="filtros.fecha_hasta" @change="cargarReporte" class="filter-input" />
        </div>
      </div>
    </div>

    <!-- SECCIÓN IMPRIMIBLE PARA GENERACIÓN OFICIAL PDF -->
    <div id="seccion-reporte-imprimible" class="results-card">
      <div class="print-header-institucional">
        <div class="flex items-center justify-between border-b border-[var(--border-subtle)] pb-4 mb-4">
          <div>
            <div class="font-bold text-lg text-[var(--color-primary)]">ROBLEDAL CONNECTING &bull; RESIDIA</div>
            <div class="text-xs text-[var(--text-secondary)]">Informe Oficial de Control de Garita & Trazabilidad de Accesos</div>
          </div>
          <div class="text-right text-[11px] text-[var(--text-muted)] font-mono">
            <div>Fecha de Emisión: {{ new Date().toLocaleString() }}</div>
            <div>Sede: Urbanización Piloto Robledal</div>
          </div>
        </div>
      </div>

      <div class="results-header no-print">
        <div class="flex items-center gap-2">
          <Filter :size="16" class="text-[var(--color-primary)]" />
          <span class="text-sm font-semibold text-[var(--text-primary)]">
            Registros Encontrados: {{ registros.length }}
          </span>
        </div>
        <span class="badge-auditoria">Auditoría CDC Activa</span>
      </div>

      <div v-if="cargando" class="loading-state">
        <Loader2 :size="32" class="animate-spin text-[var(--color-primary)] mb-2" />
        <p>Procesando consulta en PostgreSQL...</p>
      </div>

      <div v-else-if="registros.length === 0" class="empty-state">
        <p>No se encontraron registros con los filtros seleccionados.</p>
      </div>

      <div v-else class="table-container">
        <table class="report-table">
          <thead>
            <tr>
              <th>Visitante / Identificación</th>
              <th>Ingreso</th>
              <th>Placa / Ocupantes</th>
              <th>Rol</th>
              <th>Destino</th>
              <th>Fecha Ingreso</th>
              <th>Fecha Salida</th>
              <th>Duración</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in registros" :key="r.acc_id">
              <td>
                <div class="font-medium text-xs text-[var(--text-primary)]">
                  {{ r.persona ? `${r.persona.per_nombres} ${r.persona.per_apellidos}` : 'No registrado' }}
                </div>
                <div class="text-[10px] text-[var(--text-secondary)]">
                  {{ r.persona?.per_identificacion || '---' }}
                </div>
              </td>
              <td>
                <span :class="r.acc_tipo_ingreso === 'VEHICULAR' ? 'text-amber-400' : 'text-emerald-400'" class="text-xs font-semibold">
                  {{ r.acc_tipo_ingreso }}
                </span>
              </td>
              <td>
                <span v-if="r.acc_vehiculo_placa" class="font-mono text-xs font-semibold text-[var(--color-primary)]">
                  🚗 {{ r.acc_vehiculo_placa }}
                </span>
                <span v-else class="text-xs text-[var(--text-muted)]">—</span>
                <div v-if="r.acc_cantidad_ocupantes > 1" class="text-[10px] text-[var(--text-muted)]">
                  {{ r.acc_cantidad_ocupantes }} pax
                </div>
              </td>
              <td>
                <span class="text-xs font-medium text-[var(--text-secondary)]">
                  {{ r.acc_rol_visita }}
                </span>
              </td>
              <td class="text-xs text-[var(--color-primary)]">
                {{ r.inmueble ? `${r.inmueble.inm_manzana_bloque} - ${r.inmueble.inm_numero_villa}` : 'Área Comunal' }}
              </td>
              <td class="text-xs font-mono">
                {{ formatFecha(r.acc_fecha_ingreso) }}
              </td>
              <td class="text-xs font-mono">
                {{ r.acc_fecha_salida ? formatFecha(r.acc_fecha_salida) : '---' }}
              </td>
              <td class="text-xs font-medium">
                {{ r.acc_duracion_minutos ? `${r.acc_duracion_minutos} min` : 'En curso' }}
              </td>
              <td>
                <span :class="r.acc_estado === 'DENTRO' ? 'tag-dentro' : 'tag-salio'">
                  {{ r.acc_estado }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import { FileText, Table, Filter, Loader2 } from 'lucide-vue-next';

const props = defineProps({
  urbanizacionId: { type: String, default: 'ccce9ae7-ca93-48e7-8577-0dd546dda010' }
});

const cargando = ref(false);
const exportando = ref(false);
const registros = ref([]);

const filtros = reactive({
  estado: '',
  tipo_ingreso: '',
  placa: '',
  fecha_desde: '',
  fecha_hasta: '',
});

let debounceTimer = null;
const debounceBuscar = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    cargarReporte();
  }, 400);
};

const cargarReporte = async () => {
  cargando.value = true;
  try {
    const params = new URLSearchParams();
    if (filtros.estado) params.append('estado', filtros.estado);
    if (filtros.tipo_ingreso) params.append('tipo_ingreso', filtros.tipo_ingreso);
    if (filtros.placa) params.append('placa', filtros.placa);
    if (filtros.fecha_desde) params.append('fecha_desde', filtros.fecha_desde);
    if (filtros.fecha_hasta) params.append('fecha_hasta', filtros.fecha_hasta);

    const token = localStorage.getItem('residia_token');
    const res = await axios.get(`/api/reportes/accesos?${params.toString()}`, {
      headers: { 
        'Authorization': `Bearer ${token}`,
        'X-Urbanizacion-Id': props.urbanizacionId 
      }
    });

    if (res.data.success) {
      registros.value = res.data.data;
    }
  } catch (err) {
    console.error('Error cargando reporte:', err);
  } finally {
    cargando.value = false;
  }
};

const descargarExcel = async () => {
  exportando.value = true;
  try {
    const params = new URLSearchParams();
    if (filtros.estado) params.append('estado', filtros.estado);
    if (filtros.tipo_ingreso) params.append('tipo_ingreso', filtros.tipo_ingreso);
    if (filtros.placa) params.append('placa', filtros.placa);
    if (filtros.fecha_desde) params.append('fecha_desde', filtros.fecha_desde);
    if (filtros.fecha_hasta) params.append('fecha_hasta', filtros.fecha_hasta);

    const token = localStorage.getItem('residia_token');
    const response = await axios.get(`/api/reportes/accesos/excel?${params.toString()}`, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'X-Urbanizacion-Id': props.urbanizacionId
      },
      responseType: 'blob'
    });

    const url = window.URL.createObjectURL(new Blob([response.data], { type: 'text/csv;charset=utf-8;' }));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `reporte_accesos_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    alert('Reporte en Excel descargado exitosamente y registrado en aud_reportes_descargas.');
  } catch (err) {
    alert('Error al descargar reporte. Verifique permisos.');
  } finally {
    exportando.value = false;
  }
};

const imprimirReportePdf = async () => {
  // Registrar auditoría del PDF
  const token = localStorage.getItem('residia_token');
  try {
    await axios.post('/api/reportes/accesos/exportar', {
      exportar: true,
      formato: 'PDF',
      ...filtros
    }, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'X-Urbanizacion-Id': props.urbanizacionId
      }
    });
  } catch (e) {}

  window.print();
};

const formatFecha = (str) => {
  if (!str) return '';
  const d = new Date(str);
  return d.toLocaleString([], { dateStyle: 'short', timeStyle: 'short' });
};

onMounted(() => {
  cargarReporte();
});
</script>

<style scoped>
.reportes-view {
  padding: 16px 24px;
  width: 100%; margin: 0;
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
.export-actions {
  display: flex;
  gap: 10px;
}
.btn-export {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 9px 16px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-pdf {
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  color: var(--text-primary);
}
.btn-pdf:hover {
  border-color: #EF4444;
  color: #EF4444;
}
.btn-excel {
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  color: var(--text-primary);
}
.btn-excel:hover {
  border-color: #10B981;
  color: #10B981;
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
  gap: 14px;
  flex-wrap: wrap;
}
.filter-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
  flex: 1;
  min-width: 160px;
}
.filter-group label {
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
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

.results-card {
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
}
.print-header-institucional {
  display: none;
}
.results-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 1px solid var(--border-subtle);
}
.badge-auditoria {
  background: var(--color-primary-subtle);
  color: var(--color-primary);
  font-size: 11px;
  padding: 3px 8px;
  border-radius: 6px;
  font-weight: 600;
}

.table-container {
  overflow-x: auto;
}
.report-table {
  width: 100%;
  border-collapse: collapse;
}
.report-table th {
  text-align: left;
  font-size: 11px;
  text-transform: uppercase;
  color: var(--text-muted);
  padding: 10px 8px;
  border-bottom: 1px solid var(--border-subtle);
}
.report-table td {
  padding: 12px 8px;
  border-bottom: 1px solid var(--border-subtle);
}
.tag-dentro {
  background: var(--color-primary-subtle);
  color: var(--color-primary);
  padding: 3px 8px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 600;
}
.tag-salio {
  background: var(--bg-primary);
  color: var(--text-muted);
  padding: 3px 8px;
  border-radius: 6px;
  font-size: 11px;
}

/* ESTILOS DE IMPRESIÓN OFICIAL (PDF/PRINT) */
@media print {
  body {
    background: #FFFFFF !important;
    color: #000000 !important;
  }
  .main-navbar, .export-actions, .filter-card, .no-print {
    display: none !important;
  }
  .reportes-view {
    padding: 0 !important;
  }
  .results-card {
    border: none !important;
    box-shadow: none !important;
    padding: 0 !important;
    background: #FFFFFF !important;
  }
  .print-header-institucional {
    display: block !important;
  }
  .report-table th, .report-table td {
    border-bottom: 1px solid #E5E7EB !important;
    color: #111827 !important;
  }
}
</style>