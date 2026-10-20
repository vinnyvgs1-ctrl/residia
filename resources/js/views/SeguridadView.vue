<template>
  <div class="seguridad-view">
    <div class="view-header">
      <div>
        <h1 class="view-title">Módulo de Seguridad & Control RBAC</h1>
        <p class="view-subtitle">Gestión de usuarios del sistema, roles por urbanización y matriz de permisos granulares</p>
      </div>

      <!-- Sub-pestañas internas -->
      <div class="subtabs">
        <button 
          :class="subtab === 'usuarios' ? 'subtab-active' : 'subtab-inactive'"
          @click="subtab = 'usuarios'"
        >
          <Users :size="15" /> Usuarios & Cuentas
        </button>
        <button 
          :class="subtab === 'roles' ? 'subtab-active' : 'subtab-inactive'"
          @click="subtab = 'roles'"
        >
          <ShieldAlert :size="15" /> Matriz de Permisos (RBAC)
        </button>
      </div>
    </div>

    <!-- SUBTAB 1: USUARIOS Y CUENTAS -->
    <div v-if="subtab === 'usuarios'" class="tab-content">
      <div class="actions-bar">
        <div class="search-wrap">
          <Search :size="16" class="search-icon" />
          <input 
            v-model="filtroUsuario" 
            placeholder="Buscar por nombre, email o cédula..." 
            class="search-input"
          />
        </div>

        <div class="flex items-center gap-3">
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

          <button @click="abrirModalCrear" class="btn-primary flex items-center gap-2">
            <UserPlus :size="16" />
            <span>Nuevo Usuario</span>
          </button>
        </div>
      </div>

      <div v-if="cargando" class="loading-state">
        <Loader2 :size="32" class="animate-spin text-[var(--color-primary)] mb-2" />
        <p>Cargando cuentas de acceso...</p>
      </div>

      <!-- VISTA TABLA / DATATABLE DE USUARIOS -->
      <div v-else-if="modoVista === 'tabla'" class="table-container">
        <table class="data-table">
          <thead>
            <tr>
              <th>Usuario</th>
              <th>Identificación / Persona</th>
              <th>Correo Electrónico</th>
              <th>Rol / Permisos</th>
              <th>Estado</th>
              <th class="text-right">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="u in usuariosFiltrados" :key="u.usr_id">
              <td>
                <div class="flex items-center gap-3">
                  <div class="usuario-avatar-sm" :class="u.usr_es_superadmin ? 'avatar-superadmin' : 'avatar-user'">
                    {{ u.usr_name.substring(0, 2).toUpperCase() }}
                  </div>
                  <div>
                    <div class="font-semibold text-xs text-[var(--text-primary)]">
                      {{ u.usr_name }}
                    </div>
                    <span v-if="u.usr_es_superadmin" class="badge-role-superadmin-sm">SuperAdmin</span>
                    <span v-else class="text-[10px] text-[var(--text-muted)]">{{ u.roles?.map(r => r.rol_nombre).join(', ') || 'Sin rol' }}</span>
                  </div>
                </div>
              </td>
              <td>
                <div v-if="u.persona">
                  <span class="id-badge font-mono">{{ u.persona.per_identificacion }}</span>
                  <span class="text-[10px] text-[var(--text-muted)] block">{{ u.persona.per_nombres }} {{ u.persona.per_apellidos }}</span>
                </div>
                <span v-else class="text-[11px] text-[var(--text-muted)] italic">No vinculada</span>
              </td>
              <td class="text-xs text-[var(--text-secondary)]">
                {{ u.usr_email }}
              </td>
              <td>
                <div class="flex flex-wrap gap-1">
                  <span v-if="u.usr_es_superadmin" class="badge-role-superadmin-sm">SuperAdmin</span>
                  <span v-for="r in u.roles_list" :key="r.rol_id" class="badge-role-sm">
                    {{ r.rol_nombre }}
                  </span>
                  <span v-if="(!u.roles_list || u.roles_list.length === 0) && !u.usr_es_superadmin" class="text-[11px] text-[var(--text-muted)] italic">Sin rol asignado</span>
                </div>
              </td>
              <td>
                <span v-if="u.usr_activo" class="badge-habilitado-sm">
                  <CheckCircle2 :size="11" /> Activo
                </span>
                <span v-else class="badge-inactivo-sm">
                  <XCircle :size="11" /> Inactivo
                </span>
              </td>
              <td class="text-right">
                <div class="flex items-center justify-end gap-2">
                  <button @click="abrirModalEditar(u)" class="btn-row-action" title="Editar Usuario">
                    <Pencil :size="14" />
                  </button>
                  <button @click="eliminarUsuario(u)" class="btn-row-action btn-row-del" title="Eliminar Usuario">
                    <Trash2 :size="14" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- VISTA TARJETAS / CARDS -->
      <div v-else class="usuarios-grid">
        <div v-for="u in usuariosFiltrados" :key="u.usr_id" class="usuario-card">
          <div class="usuario-card-header">
            <div class="flex items-center gap-3">
              <div class="usuario-avatar" :class="u.usr_es_superadmin ? 'avatar-superadmin' : 'avatar-user'">
                {{ u.usr_name.substring(0, 2).toUpperCase() }}
              </div>
              <div>
                <h3 class="usuario-name">{{ u.usr_name }}</h3>
                <span class="usuario-role-tag" :class="u.usr_es_superadmin ? 'role-superadmin' : 'role-normal'">
                  {{ u.usr_es_superadmin ? '⚡ SuperAdmin Global' : (obtenerNombreRol(u)) }}
                </span>
              </div>
            </div>
            <span :class="u.usr_activo ? 'badge-activo' : 'badge-inactivo'">
              {{ u.usr_activo ? 'Activo' : 'Suspendido' }}
            </span>
          </div>

          <div class="usuario-details">
            <div class="detail-row">
              <div class="flex items-center gap-1.5 text-xs text-[var(--text-secondary)]">
                <Fingerprint :size="13" class="text-[var(--color-primary)]" />
                <span>Cédula / ID:</span>
              </div>
              <span class="font-mono text-xs font-semibold text-white">{{ u.persona?.per_identificacion || 'N/A' }}</span>
            </div>

            <div class="detail-row">
              <div class="flex items-center gap-1.5 text-xs text-[var(--text-secondary)]">
                <Mail :size="13" class="text-[var(--color-primary)]" />
                <span>Correo:</span>
              </div>
              <span class="text-xs font-medium text-[var(--text-primary)] truncate max-w-[180px]">{{ u.usr_email }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- SUBTAB 2: MATRIZ DE ROLES Y PERMISOS DE 5 DIMENSIONES -->
    <div v-if="subtab === 'roles'" class="tab-content">
      <div class="roles-layout">
        <!-- Selector de Rol -->
        <div class="roles-sidebar">
          <h3 class="sidebar-title">Roles del Sistema</h3>
          <div class="roles-list">
            <button 
              v-for="rol in roles" 
              :key="rol.rol_id" 
              :class="rolSeleccionado?.rol_id === rol.rol_id ? 'rol-btn-active' : 'rol-btn'"
              @click="seleccionarRol(rol)"
            >
              <div class="font-medium text-xs">{{ rol.rol_nombre }}</div>
              <span class="text-[10px] text-[var(--text-secondary)] font-mono">{{ rol.rol_codigo }}</span>
            </button>
          </div>
        </div>

        <!-- Matriz de 5 Permisos (read, write, update, delete, print) -->
        <div class="permissions-matrix-wrap">
          <div class="matrix-header">
            <div>
              <h3 class="text-base font-semibold text-[var(--text-primary)]">
                Permisos para: {{ rolSeleccionado?.rol_nombre }}
              </h3>
              <p class="text-xs text-[var(--text-secondary)]">Marque los permisos permitidos para este rol</p>
            </div>
            <button @click="guardarPermisosRol" :disabled="guardandoMatriz" class="btn-primary">
              <Check :size="16" />
              <span>{{ guardandoMatriz ? 'Guardando en BD...' : 'Guardar Cambios' }}</span>
            </button>
          </div>

          <div class="matrix-table-wrap">
            <table class="matrix-table">
              <thead>
                <tr>
                  <th>Módulo</th>
                  <th class="text-center">Lectura (read)</th>
                  <th class="text-center">Creación (write)</th>
                  <th class="text-center">Modificación (update)</th>
                  <th class="text-center">Eliminación (delete)</th>
                  <th class="text-center">Impresión (print)</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="modulo in modulosDisponibles" :key="modulo">
                  <td class="font-semibold text-xs capitalize text-[var(--text-primary)]">
                    {{ modulo }}
                  </td>
                  <td v-for="accion in ['read', 'write', 'update', 'delete', 'print']" :key="accion" class="text-center">
                    <input 
                      type="checkbox" 
                      :checked="tienePermiso(`${modulo}.${accion}`)"
                      @change="e => togglePermiso(`${modulo}.${accion}`, e.target.checked)"
                      class="perm-checkbox"
                    />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Crear Usuario -->
    <div v-if="mostrarModalUsuario" class="modal-overlay" @click.self="mostrarModalUsuario = false">
      <div class="modal-box">
        <div class="modal-header">
          <h3 class="modal-title">{{ modoEdicion ? 'Editar Cuenta de Acceso' : 'Crear Cuenta de Acceso' }}</h3>
          <button @click="mostrarModalUsuario = false" class="btn-close">&times;</button>
        </div>
        <!-- TABS DE NAVEGACIÓN DENTRO DEL MODAL -->
        <div class="modal-tabs-header">
          <button 
            type="button"
            class="modal-tab-btn" 
            :class="{ 'modal-tab-active': modalTab === 'general' }"
            @click="modalTab = 'general'"
          >
            <Users :size="14" />
            <span>Datos Generales</span>
          </button>
          <button 
            type="button"
            class="modal-tab-btn" 
            :class="{ 'modal-tab-active': modalTab === 'perfiles' }"
            @click="modalTab = 'perfiles'"
          >
            <ShieldCheck :size="14" />
            <span>Perfiles y Permisos ({{ nuevoUser.rol_ids.length }})</span>
          </button>
          <button 
            type="button"
            class="modal-tab-btn" 
            :class="{ 'modal-tab-active': modalTab === 'urbanizaciones' }"
            @click="modalTab = 'urbanizaciones'"
          >
            <Building2 :size="14" />
            <span>Urbanizaciones ({{ nuevoUser.urbanizacion_ids.length }})</span>
          </button>
        </div>

        <form @submit.prevent="guardarUsuario" class="modal-form-content">
          <!-- TAB 1: DATOS GENERALES -->
          <div v-show="modalTab === 'general'" class="space-y-4 tab-panel">
            <div class="input-block">
              <label>Cédula o Identificación *</label>
              <input v-model="nuevoUser.identificacion" placeholder="1712345678" class="form-input" required />
            </div>
            <div class="grid-2-cols">
              <div class="input-block">
                <label>Nombres *</label>
                <input v-model="nuevoUser.nombres" placeholder="Nombres" class="form-input" required />
              </div>
              <div class="input-block">
                <label>Apellidos *</label>
                <input v-model="nuevoUser.apellidos" placeholder="Apellidos" class="form-input" required />
              </div>
            </div>
            <div class="grid-2-cols">
              <div class="input-block">
                <label>Correo Electrónico (Login) *</label>
                <input v-model="nuevoUser.email" type="email" placeholder="usuario@robledal.com" class="form-input" required />
              </div>
              <div class="input-block">
                <label>Contraseña {{ modoEdicion ? '(Opcional al editar)' : '*' }}</label>
                <input 
                  v-model="nuevoUser.password" 
                  type="password" 
                  placeholder="Mínimo 6 caracteres" 
                  class="form-input" 
                  :required="!modoEdicion" 
                />
              </div>
            </div>

            <!-- PRIVILEGIOS DE SUPERADMIN -->
            <div class="checkbox-container mt-2">
              <label class="checkbox-label">
                <input type="checkbox" v-model="nuevoUser.es_superadmin" class="form-checkbox" />
                <div>
                  <span class="font-bold text-xs text-amber-300">Privilegios de SuperAdministrador Global</span>
                  <p class="text-[11px] text-[var(--text-muted)]">Otorga control de infraestructura a nivel matriz, creación de nuevas urbanizaciones y bypass de restricciones.</p>
                </div>
              </label>
            </div>
          </div>

          <!-- TAB 2: PERFILES Y ROLES -->
          <div v-show="modalTab === 'perfiles'" class="space-y-4 tab-panel">
            <div class="tab-helper-banner">
              <ShieldCheck :size="16" class="text-[var(--color-primary)] flex-shrink-0" />
              <p class="text-xs text-[var(--text-secondary)]">
                Selecciona uno o más roles que definirán las acciones y accesos permitidos para este usuario en el sistema.
              </p>
            </div>

            <div class="selection-grid-tall">
              <div 
                v-for="r in roles" 
                :key="r.rol_id" 
                class="selection-card-item"
                :class="{ 'card-item-selected': nuevoUser.rol_ids.includes(r.rol_id) }"
                @click="toggleRol(r.rol_id)"
              >
                <div class="flex items-center justify-between w-full">
                  <div class="flex items-center gap-2.5">
                    <CheckSquare v-if="nuevoUser.rol_ids.includes(r.rol_id)" :size="16" class="text-[var(--color-primary)]" />
                    <Square v-else :size="16" class="text-[var(--text-muted)]" />
                    <span class="font-bold text-xs text-[var(--text-primary)]">{{ r.rol_nombre }}</span>
                  </div>
                  <span class="text-[10px] text-[var(--text-muted)]">ID: #{{ r.rol_id }}</span>
                </div>
                <p class="text-[11px] text-[var(--text-muted)] mt-1.5 pl-6">
                  {{ r.rol_descripcion || 'Permisos configurados en la matriz RBAC de la plataforma.' }}
                </p>
              </div>
            </div>
          </div>

          <!-- TAB 3: URBANIZACIONES AUTORIZADAS -->
          <div v-show="modalTab === 'urbanizaciones'" class="space-y-4 tab-panel">
            <div class="tab-helper-banner bg-blue-500/10 border-blue-500/30">
              <Building2 :size="16" class="text-blue-400 flex-shrink-0" />
              <p class="text-xs text-blue-200">
                Alcance Multi-Urbanización: Marca los condominios y conjuntos donde este usuario tiene autorización para operar.
              </p>
            </div>

            <div class="selection-grid-tall">
              <div 
                v-for="urb in listaUrbanizaciones" 
                :key="urb.urb_id" 
                class="selection-card-item"
                :class="{ 'card-item-selected': nuevoUser.urbanizacion_ids.includes(urb.urb_id) }"
                @click="toggleUrbanizacion(urb.urb_id)"
              >
                <div class="flex items-center justify-between w-full">
                  <div class="flex items-center gap-2.5">
                    <CheckSquare v-if="nuevoUser.urbanizacion_ids.includes(urb.urb_id)" :size="16" class="text-[var(--color-primary)]" />
                    <Square v-else :size="16" class="text-[var(--text-muted)]" />
                    <span class="font-bold text-xs text-[var(--text-primary)]">{{ urb.urb_nombre }}</span>
                  </div>
                  <span class="text-[10px] text-[var(--color-primary)] font-mono font-bold">{{ urb.urb_identificador || 'URB' }}</span>
                </div>
                <p class="text-[11px] text-[var(--text-muted)] mt-1.5 pl-6">
                  {{ urb.urb_direccion || 'Urbanización registrada en Residia CRM' }}
                </p>
              </div>
            </div>
          </div>

          <!-- BOTONES DE ACCIÓN DEL MODAL -->
          <div class="modal-actions pt-3 border-t border-[var(--border-subtle)] flex items-center justify-between">
            <div class="flex items-center gap-2">
              <button 
                v-if="modalTab !== 'general'" 
                type="button" 
                @click="modalTab = modalTab === 'urbanizaciones' ? 'perfiles' : 'general'"
                class="btn-secondary text-xs"
              >
                &larr; Anterior
              </button>
              <button 
                v-if="modalTab !== 'urbanizaciones'" 
                type="button" 
                @click="modalTab = modalTab === 'general' ? 'perfiles' : 'urbanizaciones'"
                class="btn-secondary text-xs"
              >
                Siguiente &rarr;
              </button>
            </div>

            <div class="flex items-center gap-2">
              <button type="button" @click="mostrarModalUsuario = false" class="btn-secondary">Cancelar</button>
              <button type="submit" class="btn-primary" :disabled="guardando">
                <Loader2 v-if="guardando" :size="15" class="animate-spin" />
                <span>{{ modoEdicion ? 'Actualizar Cuenta' : 'Crear Cuenta' }}</span>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { Users, ShieldAlert, UserPlus, Search, Check, Loader2, Mail, Fingerprint, Shield, Pencil, Trash2 } from 'lucide-vue-next';

const props = defineProps({
  urbanizacionId: { type: String, default: 'ccce9ae7-ca93-48e7-8577-0dd546dda010' }
});

const subtab = ref('usuarios');
const cargando = ref(true);
const usuarios = ref([]);
const roles = ref([]);
const permisos = ref([]);
const listaUrbanizaciones = ref([]);
const rolSeleccionado = ref(null);
const permisosRolActivo = ref(new Set());
const guardandoMatriz = ref(false);

const filtroUsuario = ref('');
const modoVista = ref(localStorage.getItem('residia_vista_usuarios') || 'tabla');
const cambiarModoVista = (m) => {
  modoVista.value = m;
  localStorage.setItem('residia_vista_usuarios', m);
};
const mostrarModalUsuario = ref(false);
const modoEdicion = ref(false);
const modalTab = ref('general');
const usuarioEditandoId = ref(null);


const toggleRol = (rolId) => {
  const idx = nuevoUser.value.rol_ids.indexOf(rolId);
  if (idx > -1) {
    nuevoUser.value.rol_ids.splice(idx, 1);
  } else {
    nuevoUser.value.rol_ids.push(rolId);
  }
};

const toggleUrbanizacion = (urbId) => {
  const idx = nuevoUser.value.urbanizacion_ids.indexOf(urbId);
  if (idx > -1) {
    nuevoUser.value.urbanizacion_ids.splice(idx, 1);
  } else {
    nuevoUser.value.urbanizacion_ids.push(urbId);
  }
};

const abrirModalCrear = () => {
  modalTab.value = 'general';
  modoEdicion.value = false;
  usuarioEditandoId.value = null;
  nuevoUser.value = {
    identificacion: '',
    nombres: '',
    apellidos: '',
    email: '',
    password: '',
    rol_ids: roles.value.length > 0 ? [roles.value[0].rol_id] : [2],
    urbanizacion_ids: listaUrbanizaciones.value.map(u => u.urb_id),
    es_superadmin: false,
    activo: true
  };
  mostrarModalUsuario.value = true;
};

const abrirModalEditar = (u) => {
  modalTab.value = 'general';
  modoEdicion.value = true;
  usuarioEditandoId.value = u.usr_id;
  
  // Dividir nombres si vienen concatenados
  const partes = (u.usr_name || '').split(' ');
  const nombres = partes.slice(0, Math.ceil(partes.length / 2)).join(' ');
  const apellidos = partes.slice(Math.ceil(partes.length / 2)).join(' ');

  nuevoUser.value = {
    identificacion: u.persona?.per_identificacion || '',
    nombres: u.persona?.per_nombres || nombres,
    apellidos: u.persona?.per_apellidos || apellidos,
    email: u.usr_email || '',
    password: '',
    rol_ids: u.roles_ids && u.roles_ids.length > 0 ? [...u.roles_ids] : [2],
    urbanizacion_ids: u.urbanizaciones_ids && u.urbanizaciones_ids.length > 0 ? [...u.urbanizaciones_ids] : listaUrbanizaciones.value.map(urb => urb.urb_id),
    es_superadmin: Boolean(u.usr_es_superadmin),
    activo: Boolean(u.usr_activo)
  };
  mostrarModalUsuario.value = true;
};

const eliminarUsuario = async (u) => {
  if (!confirm(`¿Está seguro de desactivar la cuenta del usuario "${u.usr_name}"?`)) return;
  try {
    const res = await axios.delete(`/api/seguridad/usuarios/${u.usr_id}`);
    if (res.data.success) {
      alert('Cuenta de usuario desactivada.');
      cargarDatos();
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Error al desactivar usuario');
  }
};

const nuevoUser = ref({
  identificacion: '',
  nombres: '',
  apellidos: '',
  email: '',
  password: '',
  rol_id: 2,
  es_superadmin: false,
});

const obtenerNombreRol = (u) => {
  if (u.usr_es_superadmin) return 'SuperAdmin Global';
  if (u.usr_email && u.usr_email.includes('guardia')) return 'Operador de Garita / Seguridad';
  return 'Administrador de Urbanización';
};

const modulosDisponibles = ['urbanizaciones', 'inmuebles', 'personas', 'accesos', 'reportes', 'auditoria', 'temas'];

const cargarDatos = async () => {
  cargando.value = true;
  try {
    const [resUsers, resRoles] = await Promise.all([
      axios.get('/api/seguridad/usuarios', { headers: { 'X-Urbanizacion-Id': props.urbanizacionId } }),
      axios.get('/api/seguridad/roles')
    ]);

    if (resUsers.data.success) {
      usuarios.value = resUsers.data.data;
    }
    if (resRoles.data.success) {
      roles.value = resRoles.data.roles;
      permisos.value = resRoles.data.permisos;
      if (roles.value.length > 0) {
        seleccionarRol(roles.value[0]);
      }
    }
  } catch (e) {
    console.error('Error cargando seguridad:', e);
  } finally {
    cargando.value = false;
  }
};

const usuariosFiltrados = computed(() => {
  if (!filtroUsuario.value.trim()) return usuarios.value;
  const f = filtroUsuario.value.toLowerCase();
  return usuarios.value.filter(u => 
    u.usr_name.toLowerCase().includes(f) ||
    u.usr_email.toLowerCase().includes(f) ||
    (u.persona?.per_identificacion && u.persona.per_identificacion.includes(f))
  );
});

const seleccionarRol = (rol) => {
  rolSeleccionado.value = rol;
  permisosRolActivo.value = new Set(rol.permisos?.map(p => p.prm_slug) || []);
};

const tienePermiso = (slug) => {
  return permisosRolActivo.value.has(slug);
};

const togglePermiso = (slug, checked) => {
  if (checked) {
    permisosRolActivo.value.add(slug);
  } else {
    permisosRolActivo.value.delete(slug);
  }
};

const guardarPermisosRol = async () => {
  guardandoMatriz.value = true;
  try {
    const ids = permisos.value
      .filter(p => permisosRolActivo.value.has(p.prm_slug))
      .map(p => p.prm_id);

    const res = await axios.put(`/api/seguridad/roles/${rolSeleccionado.value.rol_id}/permisos`, {
      permiso_ids: ids
    });

    if (res.data.success) {
      alert('Matriz de permisos actualizada exitosamente.');
      rolSeleccionado.value.permisos = res.data.data.permisos;
    }
  } catch (err) {
    alert('Error actualizando permisos del rol');
  } finally {
    guardandoMatriz.value = false;
  }
};

const guardarUsuario = async () => {
  try {
    const res = await axios.post('/api/seguridad/usuarios', nuevoUser.value, {
      headers: { 'X-Urbanizacion-Id': props.urbanizacionId }
    });
    if (res.data.success) {
      alert('Usuario creado exitosamente.');
      mostrarModalUsuario.value = false;
      nuevoUser.value = { identificacion: '', nombres: '', apellidos: '', email: '', password: '', rol_id: 2, es_superadmin: false };
      cargarDatos();
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Error creando usuario');
  }
};

onMounted(() => {
  cargarDatos();
});
</script>

<style scoped>
.seguridad-view {
  padding: 24px;
  width: 100%; margin: 0;
}
.view-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}
.view-title {
  font-size: 22px;
  font-weight: 700;
  color: var(--text-primary);
}
.view-subtitle {
  font-size: 13px;
  color: var(--text-secondary);
  margin-top: 4px;
}
.subtabs {
  display: flex;
  gap: 8px;
  background: var(--bg-card);
  padding: 4px;
  border-radius: 10px;
  border: 1px solid var(--border-subtle);
}
.subtab-active, .subtab-inactive {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 14px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  border: none;
  cursor: pointer;
}
.subtab-active {
  background: var(--color-primary);
  color: #FFFFFF;
}
.subtab-inactive {
  background: transparent;
  color: var(--text-secondary);
}
.actions-bar {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 20px;
}
.search-wrap {
  flex: 1;
  position: relative;
  display: flex;
  align-items: center;
}
.search-icon {
  position: absolute;
  left: 12px;
  color: var(--text-muted);
}
.search-input {
  width: 100%;
  background: var(--bg-card);
  border: 1px solid var(--border-subtle);
  color: var(--text-primary);
  padding: 10px 14px 10px 36px;
  border-radius: 8px;
  font-size: 13px;
  outline: none;
}
.btn-primary {
  background: var(--color-primary);
  color: #FFFFFF;
  border: none;
  padding: 10px 16px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
}
.cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 18px;
}
.user-card {
  background: var(--bg-card);
  border: 1px solid var(--border-subtle);
  border-radius: 12px;
  padding: 18px;
  box-shadow: var(--shadow-elevation);
}
.user-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 14px;
}
.avatar-circle {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background: var(--color-primary);
  color: #FFFFFF;
  font-weight: 700;
  font-size: 13px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.user-name-title {
  font-size: 14px;
  font-weight: 600;
  color: var(--text-primary);
}
.status-active {
  font-size: 10px;
  background: var(--color-primary-subtle);
  color: var(--color-primary);
  padding: 3px 8px;
  border-radius: 9999px;
  font-weight: 600;
}
.status-inactive {
  font-size: 10px;
  background: rgba(239, 68, 68, 0.15);
  color: #EF4444;
  padding: 3px 8px;
  border-radius: 9999px;
  font-weight: 600;
}
.user-details {
  border-top: 1px dashed var(--border-subtle);
  padding-top: 10px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.detail-row {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
}
.detail-label {
  color: var(--text-secondary);
}
.detail-val {
  color: var(--text-primary);
}

/* ROLES Y MATRIZ */
.roles-layout {
  display: grid;
  grid-template-columns: 260px 1fr;
  gap: 20px;
}
.roles-sidebar {
  background: var(--bg-card);
  border: 1px solid var(--border-subtle);
  border-radius: 12px;
  padding: 16px;
  box-shadow: var(--shadow-elevation);
}
.sidebar-title {
  font-size: 13px;
  text-transform: uppercase;
  color: var(--text-secondary);
  font-weight: 700;
  margin-bottom: 12px;
}
.roles-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.rol-btn, .rol-btn-active {
  text-align: left;
  padding: 10px 12px;
  border-radius: 8px;
  border: 1px solid transparent;
  cursor: pointer;
}
.rol-btn {
  background: var(--bg-surface);
  color: var(--text-primary);
  border-color: var(--border-subtle);
}
.rol-btn-active {
  background: var(--color-primary-subtle);
  color: var(--color-primary);
  border-color: var(--color-primary);
}
.permissions-matrix-wrap {
  background: var(--bg-card);
  border: 1px solid var(--border-subtle);
  border-radius: 12px;
  padding: 20px;
  box-shadow: var(--shadow-elevation);
}
.matrix-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 18px;
  padding-bottom: 14px;
  border-bottom: 1px solid var(--border-subtle);
}
.matrix-table {
  width: 100%;
  border-collapse: collapse;
}
.matrix-table th {
  padding: 10px 8px;
  font-size: 11px;
  text-transform: uppercase;
  color: var(--text-secondary);
  border-bottom: 1px solid var(--border-subtle);
}
.matrix-table td {
  padding: 12px 8px;
  border-bottom: 1px solid var(--border-subtle);
}
.perm-checkbox {
  width: 16px;
  height: 16px;
  accent-color: var(--color-primary);
  cursor: pointer;
}
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.75);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}
.modal-content {
  background: var(--bg-card);
  border: 1px solid var(--border-subtle);
  border-radius: 12px;
  width: 100%;
  max-width: 500px;
  padding: 24px;
}
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}
.modal-header h3 {
  font-size: 16px;
  font-weight: 600;
  color: var(--text-primary);
}
.btn-close {
  background: transparent;
  border: none;
  font-size: 20px;
  color: var(--text-muted);
  cursor: pointer;
}
.form-group {
  margin-bottom: 12px;
}
.form-group label {
  display: block;
  font-size: 12px;
  color: var(--text-secondary);
  margin-bottom: 4px;
}
.form-input {
  width: 100%;
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  color: var(--text-primary);
  padding: 9px 12px;
  border-radius: 8px;
  font-size: 13px;
  outline: none;
}
.grid-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}
.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 18px;
}
.btn-cancel {
  background: transparent;
  border: 1px solid var(--border-subtle);
  color: var(--text-secondary);
  padding: 8px 16px;
  border-radius: 8px;
  font-size: 13px;
  cursor: pointer;
}
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  color: var(--text-muted);
}

/* GRID DE USUARIOS */
.usuarios-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 16px;
}
.usuario-card {
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 14px;
  padding: 16px;
  box-shadow: var(--shadow-sm);
  transition: all 0.2s ease;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}
.usuario-card:hover {
  border-color: var(--color-primary);
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}
.usuario-card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 12px;
}
.usuario-avatar {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  font-weight: 700;
  font-size: 13px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.avatar-user {
  background: var(--bg-primary-subtle);
  color: var(--color-primary);
  border: 1px solid var(--border-primary-subtle);
}
.avatar-superadmin {
  background: rgba(245, 158, 11, 0.2);
  color: #fbbf24;
  border: 1px solid rgba(245, 158, 11, 0.4);
}
.usuario-name {
  font-size: 13px;
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1.3;
}
.usuario-role-tag {
  font-size: 10px;
  font-weight: 600;
  display: inline-block;
  margin-top: 2px;
}
.role-superadmin {
  color: #fbbf24;
}
.role-normal {
  color: var(--color-primary);
}
.badge-activo {
  font-size: 10px;
  font-weight: 600;
  background: rgba(16, 185, 129, 0.12);
  color: #34d399;
  border: 1px solid rgba(16, 185, 129, 0.25);
  padding: 2px 7px;
  border-radius: 6px;
}
.badge-inactivo {
  font-size: 10px;
  font-weight: 600;
  background: rgba(239, 68, 68, 0.15);
  color: #f87171;
  border: 1px solid rgba(239, 68, 68, 0.3);
  padding: 2px 7px;
  border-radius: 6px;
}
.usuario-details {
  border-top: 1px dashed var(--border-subtle);
  padding-top: 10px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
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




/* ESTILOS TOGGLE VISTA Y TABLA COMPACTA DE USUARIOS */
.view-mode-toggle {
  display: flex;
  background: var(--bg-hover);
  border: 1px solid var(--border-color);
  border-radius: 8px;
  padding: 2px;
  gap: 2px;
}
.toggle-active {
  background: var(--color-primary);
  color: #0b0f19;
  font-weight: 700;
  font-size: 11px;
  padding: 5px 12px;
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
  padding: 5px 12px;
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
.usuario-avatar-sm {
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
.badge-role-sm {
  display: inline-flex;
  padding: 2px 8px;
  border-radius: 6px;
  font-size: 10px;
  font-weight: 600;
  background: rgba(59, 130, 246, 0.12);
  color: #93c5fd;
  border: 1px solid rgba(59, 130, 246, 0.25);
}
.badge-role-superadmin-sm {
  display: inline-flex;
  padding: 1px 6px;
  border-radius: 4px;
  font-size: 9px;
  font-weight: 700;
  background: rgba(234, 179, 8, 0.15);
  color: #fde047;
  border: 1px solid rgba(234, 179, 8, 0.3);
  text-transform: uppercase;
  margin-top: 2px;
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
.badge-inactivo-sm {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  background: rgba(239, 68, 68, 0.12);
  color: #fca5a5;
  border: 1px solid rgba(239, 68, 68, 0.25);
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


/* DISEÑO PREMIUM ROBLEDAL PARA DATATABLE DE USUARIOS */
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


/* ESTILOS PARA SELECCIÓN MÚLTIPLE DE ROLES Y URBANIZACIONES */
.selection-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 8px;
  max-height: 180px;
  overflow-y: auto;
  padding: 4px;
  background: var(--bg-primary);
  border: 1px solid var(--border-subtle);
  border-radius: 8px;
}
.selection-chip {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  padding: 8px 10px;
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
  user-select: none;
}
.selection-chip:hover {
  border-color: var(--color-primary);
  background: var(--bg-hover);
}
.chip-selected {
  border-color: var(--color-primary);
  background: rgba(16, 185, 129, 0.08);
}
.chip-info {
  display: flex;
  flex-direction: column;
}
.chip-title {
  font-size: 11px;
  font-weight: 700;
  color: var(--text-primary);
}
.chip-desc {
  font-size: 9px;
  color: var(--text-muted);
}
.checkbox-container {
  background: rgba(234, 179, 8, 0.06);
  border: 1px solid rgba(234, 179, 8, 0.25);
  border-radius: 8px;
  padding: 10px 14px;
}
.checkbox-label {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  cursor: pointer;
}
.form-checkbox {
  margin-top: 2px;
  cursor: pointer;
}


/* ESTILOS TABS EN MODAL DE USUARIOS */
.modal-tabs-header {
  display: flex;
  gap: 6px;
  border-bottom: 1px solid var(--border-subtle);
  padding-bottom: 10px;
  margin-bottom: 16px;
}
.modal-tab-btn {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 8px 14px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  color: var(--text-muted);
  background: var(--bg-primary);
  border: 1px solid var(--border-subtle);
  cursor: pointer;
  transition: all 0.2s ease;
}
.modal-tab-btn:hover {
  background: var(--bg-hover);
  color: var(--text-primary);
}
.modal-tab-active {
  background: var(--color-primary);
  color: #0b0f19;
  border-color: var(--color-primary);
  font-weight: 700;
}
.tab-panel {
  min-height: 260px;
}
.tab-helper-banner {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  border-radius: 8px;
  background: rgba(16, 185, 129, 0.08);
  border: 1px solid rgba(16, 185, 129, 0.25);
  margin-bottom: 12px;
}
.selection-grid-tall {
  display: grid;
  grid-template-columns: 1fr;
  gap: 10px;
  max-height: 250px;
  overflow-y: auto;
  padding-right: 4px;
}
.selection-card-item {
  padding: 12px 14px;
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.15s ease-in-out;
}
.selection-card-item:hover {
  background: var(--bg-hover);
  border-color: var(--color-primary);
  transform: translateY(-1px);
}
.card-item-selected {
  background: rgba(16, 185, 129, 0.08) !important;
  border-color: var(--color-primary) !important;
  box-shadow: 0 0 0 1px var(--color-primary);
}

</style>
