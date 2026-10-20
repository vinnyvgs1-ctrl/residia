<template>
  <div class="com-vot-container">
    <!-- CABECERA -->
    <div class="top-header-panel">
      <div>
        <h2 class="text-lg font-bold text-white flex items-center gap-2">
          <Vote class="text-indigo-400" :size="20" />
          <span>Asamblea Digital, Votaciones & Comunicados</span>
        </h2>
        <p class="text-xs text-[var(--text-muted)] mt-0.5">
          Publicación de circulares oficiales, consultas vinculantes y votaciones por predio o alícuota ponderada.
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
          v-if="tabActiva === 'comunicados'"
          @click="modalNuevoComunicado = true"
          class="btn-primary flex items-center gap-1.5"
        >
          <Megaphone :size="15" />
          <span>Nuevo Comunicado</span>
        </button>

        <button 
          v-else
          @click="modalNuevaVotacion = true"
          class="btn-primary flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-500"
        >
          <Plus :size="15" />
          <span>Nueva Votación</span>
        </button>
      </div>
    </div>

    <!-- TABS -->
    <div class="subtabs-bar">
      <button 
        class="subtab-btn"
        :class="{ 'subtab-active': tabActiva === 'votaciones' }"
        @click="tabActiva = 'votaciones'"
      >
        <Vote :size="15" />
        <span>Votaciones & Asambleas ({{ votaciones.length }})</span>
      </button>

      <button 
        class="subtab-btn"
        :class="{ 'subtab-active': tabActiva === 'comunicados' }"
        @click="tabActiva = 'comunicados'"
      >
        <Megaphone :size="15" />
        <span>Tablón de Comunicados Oficiales ({{ comunicados.length }})</span>
      </button>
    </div>

    <!-- TAB 1: VOTACIONES & ASAMBLEAS VIRTUALES -->
    <div v-show="tabActiva === 'votaciones'" class="space-y-4">
      <div v-if="cargando" class="text-center py-10 text-[var(--text-muted)] text-xs">
        <RotateCcw :size="16" class="animate-spin inline-block mr-2" />
        Cargando asambleas y consultas...
      </div>
      <div v-else-if="votaciones.length === 0" class="text-center py-10 text-[var(--text-muted)] text-xs">
        No hay procesos de votación activos en este momento.
      </div>
      <div 
        v-for="vot in votaciones" 
        :key="vot.vot_id"
        class="votacion-card"
      >
        <div class="vot-header">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 flex items-center justify-center font-bold">
              <Vote :size="20" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <span class="text-sm font-bold text-white">{{ vot.vot_titulo }}</span>
                <span 
                  class="px-2 py-0.5 rounded text-[10px] font-bold"
                  :class="vot.vot_estado === 'ABIERTA' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-slate-500/20 text-slate-400 border border-slate-500/30'"
                >
                  {{ vot.vot_estado }}
                </span>
                <span class="text-[10px] text-indigo-300 font-mono bg-indigo-500/10 px-2 py-0.5 rounded border border-indigo-500/20">
                  {{ vot.vot_tipo_calculo === 'PREDIO_UNICO' ? '1 Voto x Predio' : 'Voto Ponderado (%)' }}
                </span>
              </div>
              <p class="text-xs text-[var(--text-muted)] mt-1 max-w-3xl">
                {{ vot.vot_descripcion || 'Consulta oficial para copropietarios.' }}
              </p>
            </div>
          </div>

          <div class="text-right">
            <span class="text-[10px] uppercase font-bold text-[var(--text-muted)] block">Total Votos Emitidos</span>
            <span class="text-xl font-black font-mono text-white">{{ vot.total_votos }}</span>
          </div>
        </div>

        <!-- ESCRUTINIO EN TIEMPO REAL -->
        <div class="vot-escrutinio-box">
          <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2 flex items-center justify-between">
            <span>Resultados & Escrutinio en Tiempo Real</span>
            <span class="text-[10px] text-emerald-400">Vigente hasta {{ vot.vot_fecha_fin }}</span>
          </div>

          <div class="space-y-3">
            <div 
              v-for="op in vot.escrutinio" 
              :key="op.opcion_id"
              class="opcion-bar-wrap"
            >
              <div class="flex items-center justify-between text-xs mb-1">
                <strong class="text-white">{{ op.texto }}</strong>
                <span class="font-mono text-slate-300 font-bold">
                  {{ op.votos_recibidos }} votos ({{ op.porcentaje }}%)
                </span>
              </div>
              <div class="progress-track">
                <div 
                  class="progress-fill"
                  :style="{ width: `${op.porcentaje}%` }"
                  :class="op.opcion_id === 1 ? 'bg-emerald-500' : (op.opcion_id === 2 ? 'bg-rose-500' : 'bg-indigo-500')"
                ></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 2: TABLÓN DE COMUNICADOS OFICIALES -->
    <div v-show="tabActiva === 'comunicados'" class="grid-comunicados">
      <div 
        v-for="com in comunicados" 
        :key="com.com_id"
        class="comunicado-box"
      >
        <div class="flex items-center justify-between mb-2">
          <span 
            class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider"
            :class="badgeCategoria(com.com_categoria)"
          >
            {{ com.com_categoria }}
          </span>
          <span class="text-[11px] text-[var(--text-muted)] font-mono">
            {{ com.com_fecha_publicacion.slice(0, 10) }}
          </span>
        </div>

        <h3 class="text-sm font-bold text-white mb-2">{{ com.com_titulo }}</h3>
        <p class="text-xs text-[var(--text-secondary)] line-clamp-3 leading-relaxed mb-3">
          {{ com.com_contenido }}
        </p>

        <div class="flex items-center justify-between pt-2 border-t border-[var(--border-subtle)] text-[11px] text-[var(--text-muted)]">
          <span>Autor: {{ com.autor_nombre || 'Administración' }}</span>
          <span v-if="com.com_fijado" class="text-amber-400 font-bold flex items-center gap-1">
            📌 Fijado
          </span>
        </div>
      </div>
    </div>

    <!-- MODAL NUEVO COMUNICADO -->
    <div v-if="modalNuevoComunicado" class="modal-overlay" @click.self="modalNuevoComunicado = false">
      <div class="modal-card max-w-lg">
        <div class="modal-header">
          <div class="flex items-center gap-2">
            <Megaphone :size="18" class="text-emerald-400" />
            <h3 class="text-sm font-bold text-white">Publicar Comunicado Oficial</h3>
          </div>
          <button @click="modalNuevoComunicado = false" class="text-slate-400 hover:text-white">✕</button>
        </div>

        <form @submit.prevent="guardarComunicado" class="p-5 space-y-4">
          <div class="input-block">
            <label>Título del Comunicado *</label>
            <input v-model="formComunicado.titulo" placeholder="Ej. Convocatoria a Asamblea General Extraordinaria" class="form-input" required />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div class="input-block">
              <label>Categoría *</label>
              <select v-model="formComunicado.categoria" class="form-select" required>
                <option value="GENERAL">General / Informativo</option>
                <option value="MANTENIMIENTO">Mantenimiento de Sede</option>
                <option value="SEGURIDAD">Seguridad Perimetral</option>
                <option value="ASAMBLEA">Convocatoria a Asamblea</option>
                <option value="URGENTE">Urgente / Contingencia</option>
              </select>
            </div>

            <div class="input-block">
              <label>Nivel de Prioridad *</label>
              <select v-model="formComunicado.prioridad" class="form-select" required>
                <option value="BAJA">Baja</option>
                <option value="MEDIA">Normal</option>
                <option value="ALTA">Alta</option>
                <option value="URGENTE">Urgente</option>
              </select>
            </div>
          </div>

          <div class="input-block">
            <label>Mensaje / Contenido *</label>
            <textarea v-model="formComunicado.contenido" rows="4" class="form-input text-xs" placeholder="Escriba el detalle del comunicado oficial..." required></textarea>
          </div>

          <label class="flex items-center gap-2 cursor-pointer text-xs text-white">
            <input type="checkbox" v-model="formComunicado.fijado" class="form-checkbox" />
            <span>Fijar en la parte superior del portal del residente</span>
          </label>

          <div class="modal-footer flex items-center justify-end gap-2 pt-3 border-t border-[var(--border-subtle)]">
            <button type="button" @click="modalNuevoComunicado = false" class="btn-secondary">Cancelar</button>
            <button type="submit" :disabled="guardando" class="btn-primary">
              {{ guardando ? 'Publicando...' : 'Publicar Circular' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL NUEVA VOTACIÓN -->
    <div v-if="modalNuevaVotacion" class="modal-overlay" @click.self="modalNuevaVotacion = false">
      <div class="modal-card max-w-xl">
        <div class="modal-header">
          <div class="flex items-center gap-2">
            <Vote :size="18" class="text-indigo-400" />
            <h3 class="text-sm font-bold text-white">Crear Nueva Consulta / Votación Digital</h3>
          </div>
          <button @click="modalNuevaVotacion = false" class="text-slate-400 hover:text-white">✕</button>
        </div>

        <form @submit.prevent="guardarVotacion" class="p-5 space-y-4">
          <div class="input-block">
            <label>Título de la Consulta / Asunto a Votar *</label>
            <input v-model="formVotacion.titulo" placeholder="Ej. Aprobación del Presupuesto Anual 2027" class="form-input" required />
          </div>

          <div class="input-block">
            <label>Descripción / Antecedentes</label>
            <textarea v-model="formVotacion.descripcion" rows="2" class="form-input text-xs" placeholder="Contexto técnico y económico del punto a resolver..."></textarea>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div class="input-block">
              <label>Modalidad de Cómputo *</label>
              <select v-model="formVotacion.tipo_calculo" class="form-select" required>
                <option value="PREDIO_UNICO">1 Voto por Inmueble (Democrático)</option>
                <option value="PONDERADO_ALICUOTA">Voto Ponderado según % Alícuota</option>
              </select>
            </div>

            <div class="input-block">
              <label>Cierre de Votación *</label>
              <input v-model="formVotacion.fecha_fin" type="datetime-local" class="form-input" required />
            </div>
          </div>

          <label class="flex items-center gap-2 cursor-pointer text-xs text-white">
            <input type="checkbox" v-model="formVotacion.solo_al_dia" class="form-checkbox" />
            <span>Exigir solvencia (solo predios sin alícuotas vencidas pueden votar)</span>
          </label>

          <div class="modal-footer flex items-center justify-end gap-2 pt-3 border-t border-[var(--border-subtle)]">
            <button type="button" @click="modalNuevaVotacion = false" class="btn-secondary">Cancelar</button>
            <button type="submit" :disabled="guardando" class="btn-primary bg-indigo-600 hover:bg-indigo-500">
              {{ guardando ? 'Creando...' : 'Iniciar Votación' }}
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
  Vote, Megaphone, RotateCcw, Plus 
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
const tabActiva = ref('votaciones');
const comunicados = ref([]);
const votaciones = ref([]);
const guardando = ref(false);

const modalNuevoComunicado = ref(false);
const modalNuevaVotacion = ref(false);

const formComunicado = reactive({
  titulo: '',
  contenido: '',
  categoria: 'GENERAL',
  prioridad: 'MEDIA',
  fijado: false
});

const ahora = new Date();
const proximaSemana = new Date(ahora.getTime() + 7 * 24 * 60 * 60 * 1000);
const formVotacion = reactive({
  titulo: '',
  descripcion: '',
  tipo_calculo: 'PREDIO_UNICO',
  fecha_inicio: ahora.toISOString().slice(0, 16),
  fecha_fin: proximaSemana.toISOString().slice(0, 16),
  solo_al_dia: true,
  opciones: [
    { id: 1, texto: 'A Favor / Aprobar' },
    { id: 2, texto: 'En Contra / Rechazar' },
    { id: 3, texto: 'Abstención' }
  ]
});

const cargarDatos = async () => {
  cargando.value = true;
  try {
    const [resCom, resVot] = await Promise.all([
      axios.get('/api/comunicados', headersConfig),
      axios.get('/api/votaciones', headersConfig)
    ]);
    if (resCom.data.success) comunicados.value = resCom.data.data;
    if (resVot.data.success) votaciones.value = resVot.data.data;
  } catch (error) {
    console.error('Error cargando asambleas y comunicados:', error);
  } finally {
    cargando.value = false;
  }
};

const guardarComunicado = async () => {
  guardando.value = true;
  try {
    const res = await axios.post('/api/comunicados', formComunicado, headersConfig);
    if (res.data.success) {
      alert('✓ Comunicado oficial publicado exitosamente.');
      modalNuevoComunicado.value = false;
      formComunicado.titulo = '';
      formComunicado.contenido = '';
      await cargarDatos();
    }
  } catch (e) {
    alert(e.response?.data?.message || 'Error publicando comunicado');
  } finally {
    guardando.value = false;
  }
};

const guardarVotacion = async () => {
  guardando.value = true;
  try {
    const res = await axios.post('/api/votaciones', formVotacion, headersConfig);
    if (res.data.success) {
      alert('✓ Votación digital iniciada exitosamente.');
      modalNuevaVotacion.value = false;
      formVotacion.titulo = '';
      formVotacion.descripcion = '';
      await cargarDatos();
    }
  } catch (e) {
    alert(e.response?.data?.message || 'Error creando votación');
  } finally {
    guardando.value = false;
  }
};

const badgeCategoria = (cat) => {
  if (cat === 'URGENTE') return 'bg-rose-500/20 text-rose-400 border border-rose-500/30';
  if (cat === 'MANTENIMIENTO') return 'bg-amber-500/20 text-amber-300 border border-amber-500/30';
  if (cat === 'SEGURIDAD') return 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/30';
  if (cat === 'ASAMBLEA') return 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/30';
  return 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30';
};

onMounted(() => {
  cargarDatos();
});
</script>

<style scoped>
.com-vot-container {
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
  background: #6366f1;
  color: #ffffff;
  border-color: #6366f1;
}

.votacion-card {
  background: var(--bg-card);
  border: 1px solid var(--border-subtle);
  border-radius: 0.85rem;
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.vot-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
}

.vot-escrutinio-box {
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 0.75rem;
  padding: 1rem;
}

.progress-track {
  width: 100%;
  height: 8px;
  background: rgba(255, 255, 255, 0.08);
  border-radius: 9999px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  border-radius: 9999px;
  transition: width 0.4s ease;
}

.grid-comunicados {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1.25rem;
}

.comunicado-box {
  background: var(--bg-card);
  border: 1px solid var(--border-subtle);
  border-radius: 0.85rem;
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
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
