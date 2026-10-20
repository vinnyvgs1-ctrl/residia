<template>
  <div class="personas-wrapper">
    <!-- Encabezado -->
    <div class="header-card">
      <div class="header-info">
        <div class="flex items-center gap-2">
          <Users :size="22" class="text-[var(--color-primary)]" />
          <h1 class="header-title">Padrón Único de Personas & Visitantes</h1>
        </div>
        <p class="header-subtitle">
          Directorio centralizado de residentes, proveedores, contratistas y visitas recurrentes. Al ingresar la cédula en Garita, el sistema autocompleta inmediatamente su nombre, placa habitual y destino.
        </p>
      </div>

      <button @click="abrirModalCrear" class="btn-primary flex items-center gap-2">
        <UserPlus :size="16" />
        <span>Registrar Persona</span>
      </button>
    </div>

    <!-- Barra de Búsqueda, Filtros y Paginación Resumen -->
    <div class="filter-bar">
      <div class="search-input-wrap">
        <Search :size="16" class="search-icon" />
        <input 
          v-model="busqueda" 
          @input="alBuscar"
          placeholder="Buscar por cédula, nombres, apellidos, teléfono o email..." 
          class="search-input"
        />
      </div>

      <div class="filter-pills">
        <button 
          :class="filtroVetado === 'TODOS' ? 'pill-active' : 'pill-inactive'"
          @click="cambiarFiltro('TODOS')"
        >
          Todos ({{ totalPersonas }})
        </button>
        <button 
          :class="filtroVetado === 'VETADOS' ? 'pill-active' : 'pill-inactive'"
          @click="cambiarFiltro('VETADOS')"
        >
          <ShieldAlert :size="13" /> Solo Vetados
        </button>

        <div class="view-mode-toggle">
          <button 
            type="button"
            :class="modoVista === 'tabla' ? 'toggle-active' : 'toggle-inactive'" 
            @click="cambiarModoVista('tabla')"
            title="Vista de Tabla / Grid Compacta"
          >
            <Table :size="14" /> Tabla
          </button>
          <button 
            type="button"
            :class="modoVista === 'grid' ? 'toggle-active' : 'toggle-inactive'" 
            @click="cambiarModoVista('grid')"
            title="Vista de Tarjetas"
          >
            <LayoutGrid :size="14" /> Tarjetas
          </button>
        </div>
      </div>
    </div>

    <!-- Contenedor Principal: GRID DE TARJETAS MODERNO -->
    <div v-if="cargando" class="loading-state">
      <Loader2 :size="36" class="animate-spin text-[var(--color-primary)] mb-3" />
      <p class="text-xs text-[var(--text-muted)]">Consultando padrón...</p>
    </div>

    <div v-else-if="personas.length === 0" class="empty-state">
      <Users :size="48" class="text-[var(--text-muted)] opacity-30 mb-2" />
      <p class="text-sm font-semibold text-[var(--text-primary)]">No se encontraron personas con los criterios indicados</p>
      <p class="text-xs text-[var(--text-muted)] mt-1">Prueba con otro término de búsqueda o registra una nueva persona.</p>
    </div>

    <!-- VISTA TABLA / DATATABLE -->
    <div v-else-if="modoVista === 'tabla'" class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th>Persona</th>
            <th>Identificación</th>
            <th>Contacto</th>
            <th>Estado en Garita</th>
            <th>Fecha Registro</th>
            <th class="text-right">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in personas" :key="p.per_id" :class="{ 'row-vetada': p.per_es_vetado }">
            <td>
              <div class="flex items-center gap-3">
                <div class="persona-avatar-sm" :class="p.per_es_vetado ? 'avatar-vetado' : 'avatar-normal'">
                  {{ p.per_nombres?.substring(0, 1) }}{{ p.per_apellidos?.substring(0, 1) }}
                </div>
                <div>
                  <div class="font-semibold text-xs text-[var(--text-primary)]">
                    {{ p.per_nombres }} {{ p.per_apellidos }}
                  </div>
                  <div v-if="p.inmuebles && p.inmuebles.length > 0" class="text-[10px] text-[var(--color-primary)]">
                    {{ p.inmuebles.map(i => `${i.inm_manzana_bloque || ''} ${i.inm_numero_villa || ''}`).join(', ') }}
                  </div>
                </div>
              </div>
            </td>
            <td>
              <span class="id-badge font-mono">{{ p.per_identificacion }}</span>
              <span class="text-[10px] text-[var(--text-muted)] block">{{ p.per_tipo_identificacion || 'CÉDULA' }}</span>
            </td>
            <td>
              <div class="text-xs text-[var(--text-secondary)]">
                <span v-if="p.per_telefono" class="block">{{ p.per_telefono }}</span>
                <span v-if="p.per_email" class="text-[11px] text-[var(--text-muted)] block">{{ p.per_email }}</span>
                <span v-if="!p.per_telefono && !p.per_email" class="text-[11px] text-[var(--text-muted)] italic">Sin contacto</span>
              </div>
            </td>
            <td>
              <span v-if="p.per_es_vetado" class="badge-vetado-sm" :title="p.per_motivo_veto">
                <ShieldAlert :size="11" /> VETADO
              </span>
              <span v-else class="badge-habilitado-sm">
                <CheckCircle2 :size="11" /> Habilitado
              </span>
            </td>
            <td class="text-xs text-[var(--text-muted)]">
              {{ formatFecha(p.created_at) }}
            </td>
            <td class="text-right">
              <div class="flex items-center justify-end gap-2">
                <button @click="editarPersona(p)" class="btn-row-action" title="Editar Persona">
                  <Pencil :size="14" />
                </button>
                <button @click="eliminarPersona(p)" class="btn-row-action btn-row-del" title="Eliminar del Padrón">
                  <Trash2 :size="14" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- VISTA TARJETAS / CARDS -->
    <div v-else class="personas-grid">
      <div 
        v-for="p in personas" 
        :key="p.per_id" 
        class="persona-card"
        :class="{ 'persona-card-vetada': p.per_es_vetado }"
      >
        <div class="card-header-persona">
          <div class="flex items-center gap-3">
            <div class="persona-avatar" :class="p.per_es_vetado ? 'avatar-vetado' : 'avatar-normal'">
              {{ obtenerIniciales(p.per_nombres, p.per_apellidos) }}
            </div>
            <div>
              <h3 class="persona-name">{{ p.per_nombres }} {{ p.per_apellidos }}</h3>
              <div class="persona-doc font-mono">
                <Fingerprint :size="12" class="text-[var(--color-primary)]" />
                <span>{{ p.per_identificacion }}</span>
                <span class="text-[10px] text-[var(--text-muted)]">({{ p.per_tipo_identificacion }})</span>
              </div>
            </div>
          </div>

          <div class="card-actions">
            <button @click="editarPersona(p)" class="btn-icon-action" title="Editar Ficha">
              <Pencil :size="14" />
            </button>
            <button @click="eliminarPersona(p)" class="btn-icon-action text-red-400 hover:text-red-300" title="Eliminar del Padrón">
              <Trash2 :size="14" />
            </button>
          </div>
        </div>

        <!-- Información de Contacto y Metadatos -->
        <div class="persona-body">
          <div class="contact-row">
            <Phone :size="12" class="text-[var(--text-muted)]" />
            <span>{{ p.per_telefono || 'Sin teléfono' }}</span>
          </div>
          <div class="contact-row">
            <Mail :size="12" class="text-[var(--text-muted)]" />
            <span class="truncate">{{ p.per_email || 'Sin correo registrado' }}</span>
          </div>
        </div>

        <!-- Estado de Garita (Habilitado / Alerta de Veto) -->
        <div class="persona-footer">
          <div v-if="p.per_es_vetado" class="veto-alert-badge">
            <ShieldBan :size="13" class="text-red-400 flex-shrink-0" />
            <span class="truncate" :title="p.per_motivo_veto">VETADO: {{ p.per_motivo_veto }}</span>
          </div>
          <div v-else class="habilitado-badge">
            <CheckCircle2 :size="13" class="text-emerald-400 flex-shrink-0" />
            <span>Habilitado para Garita</span>
          </div>

          <span class="text-[10px] font-mono text-[var(--text-muted)]">
            {{ formatFecha(p.created_at) }}
          </span>
        </div>
      </div>
    </div>

    <!-- Paginación Inferior -->
    <div v-if="paginacion.last_page > 1" class="pagination-bar">
      <button 
        :disabled="paginacion.current_page <= 1"
        @click="cambiarPagina(paginacion.current_page - 1)"
        class="btn-page"
      >
        &larr; Anterior
      </button>

      <span class="page-info">
        Página {{ paginacion.current_page }} de {{ paginacion.last_page }} ({{ totalPersonas }} registros)
      </span>

      <button 
        :disabled="paginacion.current_page >= paginacion.last_page"
        @click="cambiarPagina(paginacion.current_page + 1)"
        class="btn-page"
      >
        Siguiente &rarr;
      </button>
    </div>

    <!-- MODAL CREAR / EDITAR PERSONA -->
    <div v-if="mostrarModal" class="modal-overlay" @click.self="mostrarModal = false">
      <div class="modal-box">
        <div class="modal-header">
          <h3 class="modal-title flex items-center gap-2">
            <UserCheck :size="18" class="text-[var(--color-primary)]" />
            <span>{{ modoEdicion ? 'Actualizar Ficha de Persona' : 'Registrar Persona en Padrón Único' }}</span>
          </h3>
          <button @click="mostrarModal = false" class="btn-close">&times;</button>
        </div>

        <form @submit.prevent="guardarPersona" class="space-y-4">
          <div class="grid-2-cols">
            <div class="input-block">
              <label>Tipo de Documento *</label>
              <select v-model="form.per_tipo_identificacion" class="form-select" required>
                <option value="CEDULA">Cédula de Identidad</option>
                <option value="PASAPORTE">Pasaporte</option>
                <option value="RUC">RUC</option>
              </select>
            </div>
            <div class="input-block">
              <label>N° Identificación *</label>
              <input v-model="form.per_identificacion" placeholder="Ej. 1718293041" class="form-input" required />
            </div>
          </div>

          <div class="grid-2-cols">
            <div class="input-block">
              <label>Nombres Completos *</label>
              <input v-model="form.per_nombres" placeholder="Ej. Carlos Patricio" class="form-input" required />
            </div>
            <div class="input-block">
              <label>Apellidos Completos *</label>
              <input v-model="form.per_apellidos" placeholder="Ej. Mendoza Paredes" class="form-input" required />
            </div>
          </div>

          <div class="grid-2-cols">
            <div class="input-block">
              <label>Teléfono / Celular</label>
              <input v-model="form.per_telefono" placeholder="Ej. 0998887766" class="form-input" />
            </div>
            <div class="input-block">
              <label>Correo Electrónico</label>
              <input v-model="form.per_email" type="email" placeholder="Ej. cmendoza@gmail.com" class="form-input" />
            </div>
          </div>

          <!-- Restricción / Veto -->
          <div class="p-3 rounded-xl border border-[var(--border-subtle)] bg-[var(--bg-hover)] space-y-2">
            <label class="flex items-center gap-2 text-xs font-semibold text-[var(--text-primary)] cursor-pointer">
              <input type="checkbox" v-model="form.per_es_vetado" class="form-checkbox text-red-500" />
              <span :class="{ 'text-red-400 font-bold': form.per_es_vetado }">Marcar como persona VETADA en Garita</span>
            </label>

            <div v-if="form.per_es_vetado" class="input-block mt-2">
              <label class="text-xs text-red-400 font-medium">Motivo obligatorio de la restricción *</label>
              <input v-model="form.per_motivo_veto" placeholder="Ej. Incidente de seguridad, prohibición judicial de ingreso" class="form-input border-red-500" required />
            </div>
          </div>

          <div class="modal-actions">
            <button type="button" @click="mostrarModal = false" class="btn-secondary">Cancelar</button>
            <button type="submit" :disabled="guardando" class="btn-primary">
              {{ guardando ? 'Guardando en Base...' : (modoEdicion ? 'Actualizar Ficha' : 'Guardar Persona') }}
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
  Users, UserPlus, Search, ShieldAlert, Fingerprint, ShieldBan, 
  CheckCircle2, Pencil, Trash2, UserCheck, Loader2, Phone, Mail, LayoutGrid, Table 
} from 'lucide-vue-next';

const getHeaders = () => {
  const token = localStorage.getItem('residia_token');
  const urbId = localStorage.getItem('residia_urb_id') || 'ccce9ae7-ca93-48e7-8577-0dd546dda010';
  return {
    headers: {
      'Authorization': `Bearer ${token}`,
      'X-Urbanizacion-Id': urbId
    }
  };
};

const personas = ref([]);
const totalPersonas = ref(0);
const cargando = ref(true);
const busqueda = ref('');
const filtroVetado = ref('TODOS');
const modoVista = ref(localStorage.getItem('residia_vista_personas') || 'tabla');
const cambiarModoVista = (m) => { modoVista.value = m; localStorage.setItem('residia_vista_personas', m); };

const paginacion = reactive({
  current_page: 1,
  last_page: 1
});

const mostrarModal = ref(false);
const modoEdicion = ref(false);
const personaEditandoId = ref(null);
const guardando = ref(false);

const form = reactive({
  per_identificacion: '',
  per_tipo_identificacion: 'CEDULA',
  per_nombres: '',
  per_apellidos: '',
  per_telefono: '',
  per_email: '',
  per_es_vetado: false,
  per_motivo_veto: ''
});

let debounceTimer = null;
const alBuscar = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    paginacion.current_page = 1;
    cargarPersonas();
  }, 300);
};

const cambiarFiltro = (filtro) => {
  filtroVetado.value = filtro;
  paginacion.current_page = 1;
  cargarPersonas();
};

const cambiarPagina = (pagina) => {
  paginacion.current_page = pagina;
  cargarPersonas();
};

const cargarPersonas = async () => {
  cargando.value = true;
  try {
    let url = `/api/personas?page=${paginacion.current_page}&buscar=${encodeURIComponent(busqueda.value)}`;
    if (filtroVetado.value === 'VETADOS') {
      url += '&vetado=true';
    }
    const res = await axios.get(url, getHeaders());
    if (res.data.success) {
      if (res.data.data.data) {
        personas.value = res.data.data.data;
        totalPersonas.value = res.data.data.total;
        paginacion.current_page = res.data.data.current_page;
        paginacion.last_page = res.data.data.last_page;
      } else {
        personas.value = res.data.data;
        totalPersonas.value = personas.value.length;
      }
    }
  } catch (e) {
    console.error('Error cargando personas:', e);
  } finally {
    cargando.value = false;
  }
};

const obtenerIniciales = (nombres, apellidos) => {
  const n = (nombres || '').trim()[0] || '';
  const a = (apellidos || '').trim()[0] || '';
  return (n + a).toUpperCase() || 'P';
};

const abrirModalCrear = () => {
  modoEdicion.value = false;
  personaEditandoId.value = null;
  form.per_identificacion = '';
  form.per_tipo_identificacion = 'CEDULA';
  form.per_nombres = '';
  form.per_apellidos = '';
  form.per_telefono = '';
  form.per_email = '';
  form.per_es_vetado = false;
  form.per_motivo_veto = '';
  mostrarModal.value = true;
};

const editarPersona = (p) => {
  modoEdicion.value = true;
  personaEditandoId.value = p.per_id;
  form.per_identificacion = p.per_identificacion;
  form.per_tipo_identificacion = p.per_tipo_identificacion;
  form.per_nombres = p.per_nombres;
  form.per_apellidos = p.per_apellidos;
  form.per_telefono = p.per_telefono || '';
  form.per_email = p.per_email || '';
  form.per_es_vetado = Boolean(p.per_es_vetado);
  form.per_motivo_veto = p.per_motivo_veto || '';
  mostrarModal.value = true;
};

const guardarPersona = async () => {
  guardando.value = true;
  try {
    if (modoEdicion.value) {
      const res = await axios.put(`/api/personas/${personaEditandoId.value}`, form, getHeaders());
      if (res.data.success) {
        alert('Ficha de persona actualizada exitosamente.');
        mostrarModal.value = false;
        cargarPersonas();
      }
    } else {
      const res = await axios.post('/api/personas', form, getHeaders());
      if (res.data.success) {
        alert('Persona registrada en el padrón exitosamente.');
        mostrarModal.value = false;
        cargarPersonas();
      }
    }
  } catch (e) {
    alert(e.response?.data?.message || 'Error al guardar persona');
  } finally {
    guardando.value = false;
  }
};

const eliminarPersona = async (p) => {
  if (!confirm(`¿Eliminar a ${p.per_nombres} ${p.per_apellidos} del padrón único?`)) return;
  try {
    const res = await axios.delete(`/api/personas/${p.per_id}`, getHeaders());
    if (res.data.success) {
      alert('Persona eliminada con éxito.');
      cargarPersonas();
    }
  } catch (e) {
    alert('Error al eliminar persona');
  }
};

const formatFecha = (str) => {
  if (!str) return '';
  return new Date(str).toLocaleDateString([], { year: 'numeric', month: 'short', day: 'numeric' });
};

onMounted(() => {
  cargarPersonas();
});
</script>

<style scoped>
.personas-wrapper {
  padding: 16px 24px;
  width: 100%;
}
.header-card {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 14px;
  padding: 20px 24px;
  margin-bottom: 20px;
  flex-wrap: wrap;
  gap: 16px;
}
.header-title {
  font-size: 18px;
  font-weight: 700;
  color: var(--text-primary);
}
.header-subtitle {
  font-size: 12px;
  color: var(--text-secondary);
  margin-top: 4px;
  max-width: 750px;
  line-height: 1.45;
}
.filter-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  gap: 14px;
  flex-wrap: wrap;
}
.search-input-wrap {
  position: relative;
  flex: 1;
  min-width: 280px;
}
.search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-muted);
}
.search-input {
  width: 100%;
  padding: 10px 14px 10px 38px;
  border-radius: 10px;
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  color: var(--text-primary);
  font-size: 13px;
  outline: none;
  transition: all 0.2s;
}
.search-input:focus {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 2px var(--bg-primary-subtle);
}
.filter-pills {
  display: flex;
  align-items: center;
  gap: 8px;
}
.pill-active {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  border-radius: 8px;
  background: var(--bg-primary-subtle);
  color: var(--color-primary);
  border: 1px solid var(--border-primary-subtle);
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
}
.pill-inactive {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  border-radius: 8px;
  background: var(--bg-surface);
  color: var(--text-secondary);
  border: 1px solid var(--border-subtle);
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
}
.pill-inactive:hover {
  background: var(--bg-hover);
  color: var(--text-primary);
}

/* GRID DE PERSONAS */
.personas-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 16px;
}
.persona-card {
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 14px;
  padding: 16px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  transition: all 0.2s ease;
  box-shadow: var(--shadow-sm);
}
.persona-card:hover {
  border-color: var(--color-primary);
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}
.persona-card-vetada {
  border-color: rgba(239, 68, 68, 0.4);
  background: rgba(239, 68, 68, 0.03);
}
.card-header-persona {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 12px;
}
.persona-avatar {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 13px;
  flex-shrink: 0;
}
.avatar-normal {
  background: var(--bg-primary-subtle);
  color: var(--color-primary);
  border: 1px solid var(--border-primary-subtle);
}
.avatar-vetado {
  background: rgba(239, 68, 68, 0.2);
  color: #f87171;
  border: 1px solid rgba(239, 68, 68, 0.4);
}
.persona-name {
  font-size: 13px;
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1.3;
}
.persona-doc {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 11px;
  color: var(--text-secondary);
  margin-top: 2px;
}
.card-actions {
  display: flex;
  align-items: center;
  gap: 4px;
}
.btn-icon-action {
  background: transparent;
  border: none;
  color: var(--text-muted);
  padding: 5px;
  border-radius: 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s;
}
.btn-icon-action:hover {
  background: var(--bg-hover);
  color: var(--text-primary);
}
.persona-body {
  border-top: 1px dashed var(--border-subtle);
  border-bottom: 1px dashed var(--border-subtle);
  padding: 10px 0;
  margin-bottom: 12px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.contact-row {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 11px;
  color: var(--text-secondary);
}
.persona-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
}
.veto-alert-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: rgba(239, 68, 68, 0.15);
  color: #f87171;
  border: 1px solid rgba(239, 68, 68, 0.3);
  padding: 3px 8px;
  border-radius: 6px;
  font-size: 10px;
  font-weight: 700;
  max-width: 200px;
}
.habilitado-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: rgba(16, 185, 129, 0.12);
  color: #34d399;
  border: 1px solid rgba(16, 185, 129, 0.25);
  padding: 3px 8px;
  border-radius: 6px;
  font-size: 10px;
  font-weight: 600;
}
.loading-state, .empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 14px;
  text-align: center;
}
.pagination-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 24px;
  padding: 14px 20px;
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 12px;
}
.btn-page {
  padding: 6px 14px;
  border-radius: 8px;
  border: 1px solid var(--border-subtle);
  background: var(--bg-hover);
  color: var(--text-primary);
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
}
.btn-page:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}
.page-info {
  font-size: 12px;
  color: var(--text-secondary);
}

/* MODALES */
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
.modal-box {
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 14px;
  width: 100%;
  max-width: 500px;
  padding: 24px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
}
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 12px;
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
.btn-close:hover {
  color: var(--text-primary);
}
.grid-2-cols {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}
.input-block {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.input-block label {
  font-size: 11px;
  font-weight: 600;
  color: var(--text-secondary);
}
.form-input, .form-select {
  padding: 9px 12px;
  border-radius: 8px;
  background: var(--bg-surface-elevated, var(--bg-surface));
  border: 1px solid var(--border-subtle);
  color: var(--text-primary);
  font-size: 13px;
  outline: none;
  width: 100%;
}
.form-input:focus, .form-select:focus {
  border-color: var(--color-primary);
}
.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
  padding-top: 14px;
  border-top: 1px solid var(--border-subtle);
}
.btn-primary {
  background: var(--color-primary);
  color: #111827;
  font-weight: 700;
  padding: 9px 16px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  font-size: 13px;
  transition: all 0.15s;
}
.btn-primary:hover {
  opacity: 0.9;
  transform: translateY(-1px);
}
.btn-secondary {
  background: transparent;
  border: 1px solid var(--border-subtle);
  color: var(--text-secondary);
  font-weight: 600;
  padding: 9px 16px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 13px;
}
.btn-secondary:hover {
  background: var(--bg-hover);
  color: var(--text-primary);
}



/* ESTILOS TOGGLE VISTA Y TABLA COMPACTA */
.view-mode-toggle {
  display: flex;
  background: var(--bg-hover);
  border: 1px solid var(--border-color);
  border-radius: 8px;
  padding: 2px;
  gap: 2px;
  margin-left: 8px;
}
.toggle-active {
  background: var(--color-primary);
  color: #0b0f19;
  font-weight: 700;
  font-size: 11px;
  padding: 4px 10px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  gap: 5px;
  transition: all 0.2s;
}
.toggle-inactive {
  background: transparent;
  color: var(--text-muted);
  font-size: 11px;
  padding: 4px 10px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  gap: 5px;
  transition: all 0.2s;
}
.toggle-inactive:hover {
  color: var(--text-primary);
}

.table-container {
  background: var(--bg-surface);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  overflow-x: auto;
  box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}
.data-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}
.data-table th {
  background: rgba(255, 255, 255, 0.02);
  color: var(--text-muted);
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 12px 16px;
  border-bottom: 1px solid var(--border-color);
}
.data-table td {
  padding: 12px 16px;
  border-bottom: 1px solid var(--border-color);
  font-size: 12px;
}
.data-table tbody tr {
  transition: background 0.15s ease;
}
.data-table tbody tr:hover {
  background: var(--bg-hover);
}
.row-vetada {
  background: rgba(239, 68, 68, 0.04) !important;
}
.row-vetada:hover {
  background: rgba(239, 68, 68, 0.08) !important;
}
.persona-avatar-sm {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  font-size: 11px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.badge-vetado-sm {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  background: rgba(239, 68, 68, 0.15);
  color: #fca5a5;
  border: 1px solid rgba(239, 68, 68, 0.3);
  padding: 2px 8px;
  border-radius: 6px;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
}
.badge-habilitado-sm {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  background: rgba(16, 185, 129, 0.12);
  color: #6ee7b7;
  border: 1px solid rgba(16, 185, 129, 0.25);
  padding: 2px 8px;
  border-radius: 6px;
  font-size: 10px;
  font-weight: 600;
}
.id-badge {
  background: var(--bg-hover);
  border: 1px solid var(--border-color);
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 11px;
  color: var(--text-primary);
}


/* DISEÑO PREMIUM ROBLEDAL PARA DATATABLE */
.table-container {
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 14px;
  overflow: hidden;
  box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.4);
}
.data-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  text-align: left;
}
.data-table th {
  background: var(--bg-primary);
  color: var(--text-muted);
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  padding: 14px 16px;
  border-bottom: 1px solid var(--border-subtle);
}
.data-table td {
  padding: 14px 16px;
  border-bottom: 1px solid var(--border-subtle);
  color: var(--text-primary);
  vertical-align: middle;
}
.data-table tbody tr {
  transition: all 0.15s ease-in-out;
}
.data-table tbody tr:hover {
  background: var(--bg-hover);
}
.data-table tbody tr:last-child td {
  border-bottom: none;
}
.btn-row-action {
  background: var(--bg-primary);
  border: 1px solid var(--border-subtle);
  color: var(--text-secondary);
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-row-action:hover {
  background: var(--bg-hover);
  color: var(--color-primary);
  border-color: var(--color-primary);
}
.btn-row-del:hover {
  background: rgba(239, 68, 68, 0.15);
  color: #f87171;
  border-color: rgba(239, 68, 68, 0.4);
}

</style>
