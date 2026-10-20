<template>
  <div class="areas-container">
    <!-- CABECERA -->
    <div class="top-header-panel">
      <div>
        <h2 class="text-lg font-bold text-white flex items-center gap-2">
          <CalendarDays class="text-emerald-400" :size="20" />
          <span>Áreas Sociales, Amenidades & Reservas</span>
        </h2>
        <p class="text-xs text-[var(--text-muted)] mt-0.5">
          Gestión de espacios comunes, canchas deportivas, aforos, calendarios y control de solvencia de predios.
        </p>
      </div>

      <div class="flex items-center gap-3">
        <button 
          @click="cargarDatos" 
          :disabled="cargando"
          class="btn-secondary flex items-center gap-1.5"
        >
          <RotateCcw :size="15" :class="{ 'animate-spin': cargando }" />
          <span>Actualizar</span>
        </button>

        <button 
          @click="modalNuevaReserva = true"
          class="btn-primary flex items-center gap-1.5"
        >
          <Plus :size="15" />
          <span>Nueva Reserva</span>
        </button>
      </div>
    </div>

    <!-- TABS DE NAVEGACIÓN -->
    <div class="subtabs-bar">
      <button 
        class="subtab-btn"
        :class="{ 'subtab-active': tabActiva === 'catalogo' }"
        @click="tabActiva = 'catalogo'"
      >
        <Sparkles :size="15" />
        <span>Catálogo de Espacios & Amenidades ({{ areas.length }})</span>
      </button>

      <button 
        class="subtab-btn"
        :class="{ 'subtab-active': tabActiva === 'agenda' }"
        @click="tabActiva = 'agenda'"
      >
        <Calendar :size="15" />
        <span>Agenda & Historial de Reservas ({{ reservas.length }})</span>
      </button>
    </div>

    <!-- TAB 1: CATÁLOGO DE ÁREAS SOCIALES (TARJETAS VISUALES) -->
    <div v-show="tabActiva === 'catalogo'" class="grid-amenidades">
      <div 
        v-for="area in areas" 
        :key="area.are_id"
        class="amenidad-card"
      >
        <div class="amenidad-top">
          <div class="amenidad-icon-box">
            <Trophy v-if="area.are_tipo === 'PADEL'" :size="22" class="text-emerald-400" />
            <Flame v-else-if="area.are_tipo === 'BBQ'" :size="22" class="text-amber-400" />
            <Waves v-else-if="area.are_tipo === 'PISCINA'" :size="22" class="text-cyan-400" />
            <Building v-else :size="22" class="text-indigo-400" />
          </div>

          <div class="text-right">
            <span class="amenidad-costo-badge font-mono">
              {{ Number(area.are_costo_reserva) > 0 ? `$${Number(area.are_costo_reserva).toFixed(2)} / turno` : 'Gratuito' }}
            </span>
            <div v-if="Number(area.are_garantia_deposito) > 0" class="text-[10px] text-amber-300 font-mono mt-0.5">
              Garantía: ${{ Number(area.are_garantia_deposito).toFixed(2) }}
            </div>
          </div>
        </div>

        <div class="amenidad-body">
          <h3 class="amenidad-title">{{ area.are_nombre }}</h3>
          <p class="amenidad-desc">{{ area.are_descripcion || 'Espacio común para recreación de residentes.' }}</p>

          <div class="amenidad-specs">
            <div class="spec-item">
              <Users :size="13" class="text-[var(--text-muted)]" />
              <span>Aforo: <strong>{{ area.are_aforo_maximo }} pers.</strong></span>
            </div>
            <div class="spec-item">
              <Clock :size="13" class="text-[var(--text-muted)]" />
              <span>Turnos: <strong>{{ area.are_duracion_turno_horas }}h</strong></span>
            </div>
            <div class="spec-item">
              <CalendarCheck :size="13" class="text-[var(--text-muted)]" />
              <span>Horario: {{ area.are_hora_apertura.slice(0, 5) }} - {{ area.are_hora_cierre.slice(0, 5) }}</span>
            </div>
          </div>
        </div>

        <div class="amenidad-footer">
          <button 
            @click="abrirReservaParaArea(area)"
            class="btn-primary w-full text-xs py-2 flex items-center justify-center gap-1.5"
          >
            <CalendarPlus :size="14" />
            <span>Reservar Este Espacio</span>
          </button>
        </div>
      </div>
    </div>

    <!-- TAB 2: DATA-GRID DE RESERVAS REALIZADAS -->
    <div v-show="tabActiva === 'agenda'" class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th>Área Social</th>
            <th>Fecha & Horario</th>
            <th>Inmueble / Villa</th>
            <th>Titular / Contacto</th>
            <th>Invitados</th>
            <th>Estado</th>
            <th class="text-right">Acción</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="cargando">
            <td colspan="7" class="text-center py-8 text-[var(--text-muted)]">
              <RotateCcw :size="16" class="animate-spin inline-block mr-2" />
              Cargando agenda de reservas...
            </td>
          </tr>
          <tr v-else-if="reservas.length === 0">
            <td colspan="7" class="text-center py-8 text-[var(--text-muted)]">
              No hay reservas programadas en este momento.
            </td>
          </tr>
          <tr v-for="res in reservas" :key="res.res_id" class="hover:bg-[var(--bg-surface)]">
            <td class="font-bold text-white text-xs">
              <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                <span>{{ res.are_nombre }}</span>
              </div>
            </td>
            <td>
              <div class="font-mono text-xs text-emerald-300 font-bold">{{ res.res_fecha }}</div>
              <div class="text-[11px] text-[var(--text-muted)] font-mono">{{ res.res_hora_inicio.slice(0, 5) }} - {{ res.res_hora_fin.slice(0, 5) }}</div>
            </td>
            <td>
              <span class="font-bold text-xs text-white">{{ res.inm_manzana_bloque }} - {{ res.inm_numero_villa }}</span>
            </td>
            <td>
              <div class="text-xs text-white font-medium">{{ res.per_nombres }} {{ res.per_apellidos }}</div>
              <div class="text-[10px] text-[var(--text-muted)]">{{ res.per_telefono || 'Sin teléfono' }}</div>
            </td>
            <td>
              <span class="font-bold text-xs text-slate-300">{{ res.res_cantidad_invitados }} pers.</span>
            </td>
            <td>
              <span 
                class="px-2 py-0.5 rounded text-[10px] font-bold"
                :class="badgeEstadoReserva(res.res_estado)"
              >
                {{ res.res_estado }}
              </span>
            </td>
            <td class="text-right">
              <button 
                v-if="res.res_estado !== 'CANCELADA'"
                @click="cancelarReserva(res.res_id)"
                class="px-2 py-1 rounded bg-rose-500/10 hover:bg-rose-500/20 text-rose-300 border border-rose-500/30 text-[11px] font-bold"
              >
                Cancelar
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- MODAL NUEVA RESERVA -->
    <div v-if="modalNuevaReserva" class="modal-overlay" @click.self="modalNuevaReserva = false">
      <div class="modal-card max-w-lg">
        <div class="modal-header">
          <div class="flex items-center gap-2">
            <CalendarPlus :size="18" class="text-emerald-400" />
            <h3 class="text-sm font-bold text-white">Programar Reserva de Amenidad</h3>
          </div>
          <button @click="modalNuevaReserva = false" class="text-slate-400 hover:text-white">✕</button>
        </div>

        <form @submit.prevent="guardarReserva" class="p-5 space-y-4">
          <div class="input-block">
            <label>Área Social / Amenidad *</label>
            <select v-model="formReserva.area_id" class="form-select" required>
              <option v-for="a in areas" :key="a.are_id" :value="a.are_id">
                {{ a.are_nombre }} (Aforo: {{ a.are_aforo_maximo }} pers. - Costo: ${{ Number(a.are_costo_reserva).toFixed(2) }})
              </option>
            </select>
          </div>

          <div class="input-block">
            <label>Inmueble / Villa Solicitante *</label>
            <select v-model="formReserva.inmueble_id" class="form-select" required>
              <option v-for="inm in inmuebles" :key="inm.inm_id" :value="inm.inm_id">
                {{ inm.inm_manzana_bloque }} - {{ inm.inm_numero_villa }} ({{ inm.inm_tipo }})
              </option>
            </select>
            <span class="text-[10px] text-[var(--text-muted)]">El sistema verificará automáticamente que el predio no mantenga cuotas en mora.</span>
          </div>

          <div class="grid grid-cols-3 gap-3">
            <div class="input-block">
              <label>Fecha *</label>
              <input v-model="formReserva.fecha" type="date" class="form-input" required />
            </div>
            <div class="input-block">
              <label>Hora Inicio *</label>
              <input v-model="formReserva.hora_inicio" type="time" class="form-input font-mono" required />
            </div>
            <div class="input-block">
              <label>Hora Fin *</label>
              <input v-model="formReserva.hora_fin" type="time" class="form-input font-mono" required />
            </div>
          </div>

          <div class="input-block">
            <label>Cantidad de Personas / Invitados *</label>
            <input v-model.number="formReserva.cantidad_invitados" type="number" min="1" class="form-input" required />
          </div>

          <div class="input-block">
            <label>Motivo del Evento / Observaciones</label>
            <input v-model="formReserva.motivo_evento" placeholder="Ej. Cumpleaños infantil / Partido amistoso" class="form-input" />
          </div>

          <div class="modal-footer flex items-center justify-end gap-2 pt-3 border-t border-[var(--border-subtle)]">
            <button type="button" @click="modalNuevaReserva = false" class="btn-secondary">Cancelar</button>
            <button type="submit" :disabled="guardandoReserva" class="btn-primary flex items-center gap-1.5">
              <Check :size="15" />
              <span>{{ guardandoReserva ? 'Verificando y reservando...' : 'Confirmar Reserva' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import { 
  CalendarDays, RotateCcw, Plus, Sparkles, Calendar, Trophy, Flame, 
  Waves, Building, Users, Clock, CalendarCheck, CalendarPlus, Check 
} from 'lucide-vue-next';

const props = defineProps({
  urbanizacionId: {
    type: String,
    required: true
  }
});

const token = localStorage.getItem('residia_token');
const headersConfig = {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json',
    'X-Urbanizacion-Id': props.urbanizacionId
  }
};

const cargando = ref(false);
const tabActiva = ref('catalogo');
const areas = ref([]);
const reservas = ref([]);
const inmuebles = ref([]);

const modalNuevaReserva = ref(false);
const guardandoReserva = ref(false);

const formReserva = reactive({
  area_id: null,
  inmueble_id: null,
  fecha: new Date().toISOString().slice(0, 10),
  hora_inicio: '16:00',
  hora_fin: '18:00',
  cantidad_invitados: 4,
  motivo_evento: ''
});

const cargarDatos = async () => {
  cargando.value = true;
  try {
    const [resAreas, resReservas, resInmuebles] = await Promise.all([
      axios.get('/api/areas-sociales', headersConfig),
      axios.get('/api/areas-sociales/reservas', headersConfig),
      axios.get('/api/inmuebles', headersConfig)
    ]);

    if (resAreas.data.success) areas.value = resAreas.data.data;
    if (resReservas.data.success) reservas.value = resReservas.data.data;
    if (resInmuebles.data.success) {
      inmuebles.value = resInmuebles.data.data;
      if (inmuebles.value.length > 0 && !formReserva.inmueble_id) {
        formReserva.inmueble_id = inmuebles.value[0].inm_id;
      }
    }
  } catch (error) {
    console.error('Error cargando áreas sociales:', error);
  } finally {
    cargando.value = false;
  }
};

const abrirReservaParaArea = (area) => {
  formReserva.area_id = area.are_id;
  formReserva.cantidad_invitados = Math.min(4, area.are_aforo_maximo);
  modalNuevaReserva.value = true;
};

const guardarReserva = async () => {
  guardandoReserva.value = true;
  try {
    const res = await axios.post('/api/areas-sociales/reservas', formReserva, headersConfig);
    if (res.data.success) {
      alert(`✓ ${res.data.message}`);
      modalNuevaReserva.value = false;
      await cargarDatos();
      tabActiva.value = 'agenda';
    }
  } catch (error) {
    alert(error.response?.data?.message || 'Error al procesar la reserva');
  } finally {
    guardandoReserva.value = false;
  }
};

const cancelarReserva = async (id) => {
  if (!confirm('¿Desea cancelar esta reserva?')) return;
  try {
    const res = await axios.post(`/api/areas-sociales/reservas/${id}/cancelar`, {}, headersConfig);
    if (res.data.success) {
      alert('✓ Reserva cancelada.');
      await cargarDatos();
    }
  } catch (error) {
    alert('Error al cancelar la reserva.');
  }
};

const badgeEstadoReserva = (estado) => {
  if (estado === 'CONFIRMADA') return 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30';
  if (estado === 'PENDIENTE_PAGO') return 'bg-amber-500/20 text-amber-300 border border-amber-500/30';
  if (estado === 'CANCELADA') return 'bg-rose-500/20 text-rose-400 border border-rose-500/30';
  return 'bg-blue-500/20 text-blue-400 border border-blue-500/30';
};

onMounted(() => {
  cargarDatos();
});
</script>

<style scoped>
.areas-container {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.top-header-panel {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: var(--bg-card);
  padding: 1.25rem 1.5rem;
  border-radius: 0.75rem;
  border: 1px solid var(--border-subtle);
}

.subtabs-bar {
  display: flex;
  gap: 0.75rem;
  border-bottom: 1px solid var(--border-subtle);
  padding-bottom: 0.5rem;
}

.subtab-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 0.5rem;
  color: var(--text-secondary);
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.subtab-btn:hover {
  background: var(--bg-hover);
  color: var(--text-primary);
}

.subtab-active {
  background: var(--color-primary);
  color: #ffffff;
  border-color: var(--color-primary);
}

.grid-amenidades {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1.25rem;
}

.amenidad-card {
  background: var(--bg-card);
  border: 1px solid var(--border-subtle);
  border-radius: 0.85rem;
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  gap: 1rem;
  transition: all 0.2s;
}

.amenidad-card:hover {
  border-color: rgba(114, 193, 165, 0.4);
  transform: translateY(-2px);
  box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.3);
}

.amenidad-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
}

.amenidad-icon-box {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  display: flex;
  align-items: center;
  justify-content: center;
}

.amenidad-costo-badge {
  font-size: 0.75rem;
  font-weight: 800;
  color: #34d399;
  background: rgba(16, 185, 129, 0.1);
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
  border: 1px solid rgba(16, 185, 129, 0.2);
}

.amenidad-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: #ffffff;
  margin-bottom: 0.35rem;
}

.amenidad-desc {
  font-size: 0.75rem;
  color: var(--text-muted);
  line-height: 1.4;
  margin-bottom: 0.85rem;
}

.amenidad-specs {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  font-size: 0.7rem;
  color: var(--text-secondary);
}

.spec-item {
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.table-container {
  background: var(--bg-card);
  border-radius: 0.75rem;
  border: 1px solid var(--border-subtle);
  overflow: hidden;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.8rem;
  text-align: left;
}

.data-table th {
  background: var(--bg-surface);
  color: var(--text-secondary);
  padding: 0.75rem 1rem;
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 1px solid var(--border-subtle);
}

.data-table td {
  padding: 0.85rem 1rem;
  border-bottom: 1px solid var(--border-subtle);
  vertical-align: middle;
}

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

.modal-card {
  width: 100%;
  background: var(--bg-card);
  border: 1px solid var(--border-subtle);
  border-radius: 0.85rem;
  overflow: hidden;
  box-shadow: 0 25px 30px -5px rgba(0, 0, 0, 0.6);
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  background: var(--bg-surface);
  border-bottom: 1px solid var(--border-subtle);
}
</style>
