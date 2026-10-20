<template>
  <div class="vistas-admin-view">
    <!-- Encabezado Principal -->
    <div class="view-header">
      <div>
        <h1 class="view-title">Administración de Vistas & Acceso a Pantallas</h1>
        <p class="view-subtitle">Catálogo centralizado de pantallas del sistema, creación de nuevas vistas y asignación granular a roles</p>
      </div>

      <div class="flex items-center gap-3">
        <button @click="abrirModalCrearVista" class="btn-secondary flex items-center gap-2">
          <Plus :size="15" class="text-[var(--color-primary)]" />
          <span>Nueva Pantalla / Vista</span>
        </button>

        <button @click="guardarAsignacion" :disabled="guardando" class="btn-primary flex items-center gap-2">
          <Check :size="16" />
          <span>{{ guardando ? 'Guardando en BD...' : 'Guardar Vistas del Rol' }}</span>
        </button>
      </div>
    </div>

    <!-- Barra de pestañas superiores del módulo -->
    <div class="module-tabs-header mb-5">
      <button 
        type="button" 
        class="mod-tab-btn" 
        :class="{ 'mod-tab-active': tabPrincipal === 'asignacion' }"
        @click="tabPrincipal = 'asignacion'"
      >
        <ShieldCheck :size="14" />
        <span>Matriz de Acceso por Roles</span>
      </button>

      <button 
        type="button" 
        class="mod-tab-btn" 
        :class="{ 'mod-tab-active': tabPrincipal === 'catalogo' }"
        @click="tabPrincipal = 'catalogo'"
      >
        <LayoutGrid :size="14" />
        <span>Catálogo Maestro de Vistas ({{ totalVistas }})</span>
      </button>
    </div>

    <!-- ============================================================== -->
    <!-- TAB 1: ASIGNACIÓN DE VISTAS POR ROL (VISTA ACTUAL MEJORADA)    -->
    <!-- ============================================================== -->
    <div v-show="tabPrincipal === 'asignacion'" class="vistas-grid">
      <!-- Selector de Rol Lateral -->
      <div class="roles-sidebar">
        <div class="sidebar-title flex items-center justify-between">
          <span>Seleccionar Rol</span>
          <span class="text-[10px] text-[var(--color-primary)] font-bold">{{ roles.length }} Roles</span>
        </div>
        <div class="roles-list">
          <button 
            v-for="r in roles" 
            :key="r.rol_id" 
            :class="rolSeleccionado?.rol_id === r.rol_id ? 'rol-btn-active' : 'rol-btn'"
            @click="cargarVistasDelRol(r)"
          >
            <div class="font-bold text-xs">{{ r.rol_nombre }}</div>
            <span class="text-[10px] text-[var(--text-muted)] font-mono">{{ r.rol_codigo }}</span>
          </button>
        </div>
      </div>

      <!-- Pantallas agrupadas por Categoría -->
      <div class="vistas-main">
        <div v-if="cargando" class="loading-state">
          <Loader2 :size="32" class="animate-spin text-[var(--color-primary)] mb-2" />
          <p class="text-xs text-[var(--text-muted)]">Cargando catálogo de vistas...</p>
        </div>

        <div v-else class="categories-list">
          <div v-for="(vistasCat, categoria) in vistasAgrupadas" :key="categoria" class="category-card">
            <div class="category-header">
              <span class="category-badge">{{ categoria }}</span>
              <div class="flex items-center gap-3">
                <span class="text-xs text-[var(--text-secondary)]">{{ vistasCat.length }} Pantallas</span>
                <button 
                  type="button" 
                  class="btn-select-all"
                  @click="toggleTodasCategoria(vistasCat)"
                >
                  {{ todasMarcadas(vistasCat) ? 'Desmarcar todas' : 'Marcar todas' }}
                </button>
              </div>
            </div>

            <div class="vistas-cards">
              <div 
                v-for="v in vistasCat" 
                :key="v.vis_id" 
                class="vista-item"
                :class="{ 'vista-selected': vistasSeleccionadas.has(v.vis_id) }"
                @click="toggleVista(v.vis_id)"
              >
                <div class="vista-check-box">
                  <CheckSquare v-if="vistasSeleccionadas.has(v.vis_id)" :size="16" class="text-[var(--color-primary)]" />
                  <Square v-else :size="16" class="text-[var(--text-muted)]" />
                </div>
                <div class="vista-meta">
                  <div class="flex items-center justify-between w-full">
                    <span class="font-bold text-xs text-[var(--text-primary)]">{{ v.vis_nombre }}</span>
                  </div>
                  <span class="font-mono text-[10px] text-[var(--color-primary)] block mt-0.5">{{ v.vis_codigo }}</span>
                  <p v-if="v.vis_descripcion" class="text-[10px] text-[var(--text-muted)] mt-1 truncate">{{ v.vis_descripcion }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- TAB 2: CATÁLOGO MAESTRO DE VISTAS (TABLA DATA-GRID & CRUD)     -->
    <!-- ============================================================== -->
    <div v-show="tabPrincipal === 'catalogo'" class="catalogo-container">
      <div class="table-container">
        <table class="data-table">
          <thead>
            <tr>
              <th>Nombre de la Vista</th>
              <th>Código de Ruta / Slug</th>
              <th>Categoría</th>
              <th>Menú Lateral</th>
              <th>Orden</th>
              <th>Estado</th>
              <th class="text-right">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="v in todasLasVistasList" :key="v.vis_id">
              <td>
                <div class="flex items-center gap-2.5">
                  <div class="vista-icon-box">
                    <LayoutGrid :size="14" class="text-[var(--color-primary)]" />
                  </div>
                  <div>
                    <div class="font-bold text-xs text-[var(--text-primary)]">{{ v.vis_nombre }}</div>
                    <span v-if="v.vis_descripcion" class="text-[10px] text-[var(--text-muted)] block">{{ v.vis_descripcion }}</span>
                  </div>
                </div>
              </td>
              <td>
                <span class="id-badge font-mono">{{ v.vis_codigo }}</span>
              </td>
              <td>
                <span class="badge-cat-tag">{{ v.vis_categoria }}</span>
              </td>
              <td>
                <span v-if="v.vis_es_menu" class="badge-menu-ok">Visible en Menú</span>
                <span v-else class="text-[10px] text-[var(--text-muted)]">Oculta / Secundaria</span>
              </td>
              <td class="font-mono text-xs">{{ v.vis_orden || 10 }}</td>
              <td>
                <span v-if="v.vis_activo" class="badge-active-xs">Activa</span>
                <span v-else class="badge-inactive-xs">Inactiva</span>
              </td>
              <td class="text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <button @click="abrirModalEditarVista(v)" class="btn-row-action" title="Editar Vista">
                    <Pencil :size="13" />
                  </button>
                  <button @click="eliminarVista(v)" class="btn-row-action btn-row-del" title="Eliminar del Catálogo">
                    <Trash2 :size="13" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL PARA CREAR O EDITAR VISTA DEL SISTEMA                    -->
    <!-- ============================================================== -->
    <div v-if="mostrarModalVista" class="modal-overlay" @click.self="mostrarModalVista = false">
      <div class="modal-box modal-vista-wide">
        <div class="modal-header">
          <div class="flex items-center gap-2">
            <LayoutGrid :size="18" class="text-[var(--color-primary)]" />
            <h3 class="modal-title">{{ modoEdicionVista ? 'Editar Pantalla / Vista' : 'Crear Nueva Pantalla / Vista' }}</h3>
          </div>
          <button @click="mostrarModalVista = false" class="btn-close">&times;</button>
        </div>

        <form @submit.prevent="guardarVista" class="space-y-4">
          <div class="grid-2-cols">
            <div class="input-block">
              <label>Código Único (Slug de Ruta) *</label>
              <input 
                v-model="formVista.vis_codigo" 
                placeholder="ej. admin.contratistas" 
                class="form-input font-mono" 
                :disabled="modoEdicionVista"
                required 
              />
              <span class="text-[10px] text-[var(--text-muted)]">Identificador interno para permisos y rutas</span>
            </div>

            <div class="input-block">
              <label>Nombre Visible en Menú *</label>
              <input 
                v-model="formVista.vis_nombre" 
                placeholder="ej. Gestión de Contratistas" 
                class="form-input" 
                required 
              />
            </div>
          </div>

          <div class="grid-2-cols">
            <div class="input-block">
              <label>Categoría / Módulo *</label>
              <select v-model="formVista.vis_categoria" class="form-select" required>
                <option value="ADMINISTRACION">ADMINISTRACIÓN</option>
                <option value="OPERACION">OPERACIÓN</option>
                <option value="REPORTES">REPORTES</option>
                <option value="SEGURIDAD">SEGURIDAD</option>
              </select>
            </div>

            <div class="input-block">
              <label>Orden de Despliegue</label>
              <input v-model.number="formVista.vis_orden" type="number" min="1" max="999" class="form-input" />
            </div>
          </div>

          <div class="input-block">
            <label>Descripción Funcional</label>
            <input v-model="formVista.vis_descripcion" placeholder="Breve resumen de la pantalla..." class="form-input" />
          </div>

          <div class="grid-2-cols pt-1">
            <label class="checkbox-box-option">
              <input type="checkbox" v-model="formVista.vis_es_menu" class="form-checkbox" />
              <div>
                <span class="font-bold text-xs text-[var(--text-primary)]">Mostrar en Menú de Navegación</span>
                <p class="text-[10px] text-[var(--text-muted)]">Aparece como enlace en la barra superior</p>
              </div>
            </label>

            <label class="checkbox-box-option">
              <input type="checkbox" v-model="formVista.vis_activo" class="form-checkbox" />
              <div>
                <span class="font-bold text-xs text-[var(--text-primary)]">Pantalla Habilitada en el Sistema</span>
                <p class="text-[10px] text-[var(--text-muted)]">Permite el acceso a los usuarios autorizados</p>
              </div>
            </label>
          </div>

          <div class="modal-actions pt-3 border-t border-[var(--border-subtle)]">
            <button type="button" @click="mostrarModalVista = false" class="btn-secondary">Cancelar</button>
            <button type="submit" :disabled="guardandoVista" class="btn-primary">
              {{ guardandoVista ? 'Guardando...' : (modoEdicionVista ? 'Actualizar Vista' : 'Crear Pantalla') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';
import { 
  Check, Loader2, Plus, LayoutGrid, ShieldCheck, 
  CheckSquare, Square, Pencil, Trash2 
} from 'lucide-vue-next';

const roles = ref([]);
const vistasAgrupadas = ref({});
const rolSeleccionado = ref(null);
const vistasSeleccionadas = ref(new Set());
const cargando = ref(true);
const guardando = ref(false);

// Navegación en pestañas: 'asignacion' (Roles) o 'catalogo' (CRUD de Vistas)
const tabPrincipal = ref('asignacion');

// Modal Crear / Editar Vista
const mostrarModalVista = ref(false);
const modoEdicionVista = ref(false);
const vistaEditandoId = ref(null);
const guardandoVista = ref(false);

const formVista = reactive({
  vis_codigo: '',
  vis_nombre: '',
  vis_categoria: 'ADMINISTRACION',
  vis_descripcion: '',
  vis_orden: 10,
  vis_es_menu: true,
  vis_activo: true
});

const todasLasVistasList = computed(() => {
  const lista = [];
  Object.values(vistasAgrupadas.value).forEach(arr => {
    lista.push(...arr);
  });
  return lista.sort((a, b) => (a.vis_categoria || '').localeCompare(b.vis_categoria || '') || (a.vis_orden - b.vis_orden));
});

const totalVistas = computed(() => todasLasVistasList.value.length);

const cargarDatosIniciales = async () => {
  cargando.value = true;
  try {
    const [resRoles, resVistas] = await Promise.all([
      axios.get('/api/seguridad/roles'),
      axios.get('/api/seguridad/vistas')
    ]);

    if (resRoles.data.success) {
      roles.value = resRoles.data.roles;
    }
    if (resVistas.data.success) {
      vistasAgrupadas.value = resVistas.data.agrupadas;
    }

    if (roles.value.length > 0 && !rolSeleccionado.value) {
      cargarVistasDelRol(roles.value[0]);
    } else if (rolSeleccionado.value) {
      cargarVistasDelRol(rolSeleccionado.value);
    }
  } catch (err) {
    console.error('Error cargando vistas:', err);
  } finally {
    cargando.value = false;
  }
};

const cargarVistasDelRol = async (rol) => {
  rolSeleccionado.value = rol;
  try {
    const res = await axios.get(`/api/seguridad/roles/${rol.rol_id}/vistas`);
    if (res.data.success) {
      vistasSeleccionadas.value = new Set(res.data.vista_ids);
    }
  } catch (err) {
    console.error('Error cargando vistas del rol:', err);
  }
};

const toggleVista = (id) => {
  if (vistasSeleccionadas.value.has(id)) {
    vistasSeleccionadas.value.delete(id);
  } else {
    vistasSeleccionadas.value.add(id);
  }
};

const todasMarcadas = (vistasCat) => {
  return vistasCat.every(v => vistasSeleccionadas.value.has(v.vis_id));
};

const toggleTodasCategoria = (vistasCat) => {
  const todas = todasMarcadas(vistasCat);
  vistasCat.forEach(v => {
    if (todas) {
      vistasSeleccionadas.value.delete(v.vis_id);
    } else {
      vistasSeleccionadas.value.add(v.vis_id);
    }
  });
};

const guardarAsignacion = async () => {
  if (!rolSeleccionado.value) return;
  guardando.value = true;
  try {
    const res = await axios.post(`/api/seguridad/roles/${rolSeleccionado.value.rol_id}/vistas`, {
      vista_ids: Array.from(vistasSeleccionadas.value)
    });
    if (res.data.success) {
      alert(`Vistas actualizadas exitosamente para ${rolSeleccionado.value.rol_nombre}`);
    }
  } catch (err) {
    alert('Error guardando asignación de vistas');
  } finally {
    guardando.value = false;
  }
};

// CRUD de Pantallas / Vistas
const abrirModalCrearVista = () => {
  modoEdicionVista.value = false;
  vistaEditandoId.value = null;
  formVista.vis_codigo = '';
  formVista.vis_nombre = '';
  formVista.vis_categoria = 'ADMINISTRACION';
  formVista.vis_descripcion = '';
  formVista.vis_orden = (todasLasVistasList.value.length + 1) * 10;
  formVista.vis_es_menu = true;
  formVista.vis_activo = true;
  mostrarModalVista.value = true;
};

const abrirModalEditarVista = (v) => {
  modoEdicionVista.value = true;
  vistaEditandoId.value = v.vis_id;
  formVista.vis_codigo = v.vis_codigo || '';
  formVista.vis_nombre = v.vis_nombre || '';
  formVista.vis_categoria = v.vis_categoria || 'ADMINISTRACION';
  formVista.vis_descripcion = v.vis_descripcion || '';
  formVista.vis_orden = v.vis_orden || 10;
  formVista.vis_es_menu = Boolean(v.vis_es_menu);
  formVista.vis_activo = Boolean(v.vis_activo);
  mostrarModalVista.value = true;
};

const guardarVista = async () => {
  guardandoVista.value = true;
  try {
    if (modoEdicionVista.value) {
      const res = await axios.put(`/api/seguridad/vistas/${vistaEditandoId.value}`, formVista);
      if (res.data.success) {
        alert('Pantalla actualizada exitosamente.');
        mostrarModalVista.value = false;
        await cargarDatosIniciales();
      }
    } else {
      const res = await axios.post('/api/seguridad/vistas', formVista);
      if (res.data.success) {
        alert('Pantalla creada exitosamente e incorporada al catálogo.');
        mostrarModalVista.value = false;
        await cargarDatosIniciales();
      }
    }
  } catch (e) {
    alert(e.response?.data?.message || 'Error guardando vista');
  } finally {
    guardandoVista.value = false;
  }
};

const eliminarVista = async (v) => {
  if (!confirm(`¿Eliminar la vista "${v.vis_nombre}" (${v.vis_codigo}) del catálogo del sistema?`)) return;
  try {
    const res = await axios.delete(`/api/seguridad/vistas/${v.vis_id}`);
    if (res.data.success) {
      alert('Vista eliminada correctamente.');
      await cargarDatosIniciales();
    }
  } catch (e) {
    alert(e.response?.data?.message || 'Error eliminando vista');
  }
};

onMounted(() => {
  cargarDatosIniciales();
});
</script>

<style scoped>
.vistas-admin-view {
  padding: 16px 24px;
  width: 100%; 
  margin: 0;
}
.view-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
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

/* TABS DEL MÓDULO */
.module-tabs-header {
  display: flex;
  gap: 8px;
  border-bottom: 1px solid var(--border-subtle);
  padding-bottom: 2px;
}
.mod-tab-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 9px 16px;
  border-radius: 8px 8px 0 0;
  font-size: 12px;
  font-weight: 600;
  color: var(--text-muted);
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-bottom: none;
  cursor: pointer;
  transition: all 0.2s;
}
.mod-tab-btn:hover {
  color: var(--text-primary);
  background: var(--bg-hover);
}
.mod-tab-active {
  background: var(--color-primary);
  color: #0b0f19;
  border-color: var(--color-primary);
  font-weight: 700;
}

.btn-primary {
  background: var(--color-primary);
  color: #0b0f19;
  border: none;
  padding: 8px 16px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-primary:hover {
  opacity: 0.9;
}
.btn-secondary {
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  color: var(--text-primary);
  padding: 8px 14px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
}
.btn-secondary:hover {
  background: var(--bg-hover);
  border-color: var(--color-primary);
}

.vistas-grid {
  display: grid;
  grid-template-columns: 240px 1fr;
  gap: 18px;
}
.roles-sidebar {
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 12px;
  padding: 14px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
}
.sidebar-title {
  font-size: 11px;
  text-transform: uppercase;
  color: var(--text-secondary);
  font-weight: 700;
  margin-bottom: 10px;
}
.roles-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.rol-btn, .rol-btn-active {
  text-align: left;
  padding: 9px 12px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}
.rol-btn {
  background: var(--bg-primary);
  color: var(--text-primary);
  border: 1px solid var(--border-subtle);
}
.rol-btn:hover {
  background: var(--bg-hover);
  border-color: var(--color-primary);
}
.rol-btn-active {
  background: rgba(16, 185, 129, 0.12);
  color: var(--color-primary);
  border: 1px solid var(--color-primary);
}

.categories-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.category-card {
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 12px;
  padding: 16px 18px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
}
.category-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
  padding-bottom: 8px;
  border-bottom: 1px solid var(--border-subtle);
}
.category-badge {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.08em;
  color: var(--color-primary);
  text-transform: uppercase;
}
.btn-select-all {
  background: transparent;
  border: none;
  font-size: 11px;
  color: var(--color-primary);
  cursor: pointer;
  font-weight: 600;
}
.btn-select-all:hover {
  text-decoration: underline;
}

.vistas-cards {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 10px;
}
.vista-item {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  background: var(--bg-primary);
  border: 1px solid var(--border-subtle);
  border-radius: 8px;
  padding: 10px 12px;
  cursor: pointer;
  user-select: none;
  transition: all 0.2s;
}
.vista-item:hover {
  background: var(--bg-hover);
  border-color: var(--color-primary);
}
.vista-selected {
  border-color: var(--color-primary);
  background: rgba(16, 185, 129, 0.08);
}
.vista-check-box {
  margin-top: 1px;
}

/* TABLA DATA-GRID DE CATÁLOGO */
.catalogo-container {
  width: 100%;
}
.table-container {
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
}
.data-table {
  width: 100%;
  border-collapse: collapse;
}
.data-table th {
  background: var(--bg-primary);
  color: var(--text-muted);
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  padding: 12px 16px;
  border-bottom: 1px solid var(--border-subtle);
  text-align: left;
}
.data-table td {
  padding: 12px 16px;
  border-bottom: 1px solid var(--border-subtle);
  font-size: 12px;
  vertical-align: middle;
}
.data-table tbody tr:hover {
  background: var(--bg-hover);
}
.vista-icon-box {
  width: 28px;
  height: 28px;
  border-radius: 6px;
  background: rgba(16, 185, 129, 0.1);
  border: 1px solid rgba(16, 185, 129, 0.25);
  display: flex;
  align-items: center;
  justify-content: center;
}
.badge-cat-tag {
  display: inline-block;
  font-size: 10px;
  font-weight: 700;
  color: var(--color-primary);
  background: rgba(16, 185, 129, 0.1);
  border: 1px solid rgba(16, 185, 129, 0.25);
  padding: 2px 7px;
  border-radius: 4px;
}
.badge-menu-ok {
  display: inline-block;
  font-size: 10px;
  color: #38bdf8;
  background: rgba(56, 189, 248, 0.1);
  border: 1px solid rgba(56, 189, 248, 0.25);
  padding: 2px 6px;
  border-radius: 4px;
}
.badge-active-xs {
  color: #34d399;
  background: rgba(16, 185, 129, 0.15);
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 10px;
  font-weight: 700;
}
.badge-inactive-xs {
  color: #f87171;
  background: rgba(239, 68, 68, 0.15);
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 10px;
  font-weight: 700;
}
.btn-row-action {
  background: var(--bg-primary);
  border: 1px solid var(--border-subtle);
  color: var(--text-secondary);
  width: 30px;
  height: 30px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-row-action:hover {
  color: var(--color-primary);
  border-color: var(--color-primary);
}
.btn-row-del:hover {
  color: #f87171;
  border-color: rgba(239, 68, 68, 0.4);
}

/* MODAL CREAR VISTA */
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
  max-width: 580px;
  padding: 22px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
}
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding-bottom: 10px;
  border-bottom: 1px solid var(--border-subtle);
}
.modal-title {
  font-size: 15px;
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
.input-block {
  display: flex;
  flex-direction: column;
  gap: 5px;
}
.input-block label {
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  color: var(--text-secondary);
}
.form-input, .form-select {
  background: var(--bg-primary);
  border: 1px solid var(--border-subtle);
  color: var(--text-primary);
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 13px;
  outline: none;
  width: 100%;
}
.grid-2-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.checkbox-box-option {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 10px;
  background: var(--bg-primary);
  border: 1px solid var(--border-subtle);
  border-radius: 8px;
  cursor: pointer;
}
.form-checkbox {
  margin-top: 2px;
  cursor: pointer;
}
.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 14px;
}
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  color: var(--text-muted);
}
</style>
