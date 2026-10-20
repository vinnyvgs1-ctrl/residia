<template>
  <div class="urbanizaciones-view">
    <!-- ============================================================== -->
    <!-- NIVEL 1: LISTADO GENERAL DE SEDES / URBANIZACIONES (OVERVIEW) -->
    <!-- ============================================================== -->
    <div v-if="!sedeSeleccionada" class="nivel-general">
      <div class="view-header">
        <div>
          <h1 class="view-title">Gestión de Urbanizaciones</h1>
          <p class="view-subtitle">Administración de condominios, conjuntos residenciales, reglas de garita y sedes SaaS</p>
        </div>
        <button @click="abrirModalCrear" class="btn-primary flex items-center gap-2">
          <Plus :size="16" />
          <span>Nueva Urbanización</span>
        </button>
      </div>

      <!-- Buscador y Toggle de Vista (Tarjetas / Tabla) -->
      <div class="search-bar">
        <div class="search-input-wrap">
          <Search :size="16" class="search-icon" />
          <input 
            v-model="busqueda" 
            placeholder="Buscar urbanización por nombre, código o ciudad..." 
            class="input-search"
          />
        </div>

        <div class="view-mode-toggle">
          <button 
            type="button"
            :class="modoVista === 'grid' ? 'toggle-active' : 'toggle-inactive'" 
            @click="cambiarModoVista('grid')"
            title="Vista de Tarjetas"
          >
            <LayoutGrid :size="14" /> Tarjetas
          </button>
          <button 
            type="button"
            :class="modoVista === 'tabla' ? 'toggle-active' : 'toggle-inactive'" 
            @click="cambiarModoVista('tabla')"
            title="Vista de Tabla / Grid Compacta"
          >
            <Table :size="14" /> Tabla
          </button>
        </div>
      </div>

      <!-- Estado de Carga -->
      <div v-if="cargando" class="loading-state">
        <Loader2 :size="36" class="animate-spin text-[var(--color-primary)] mb-3" />
        <p class="text-xs text-[var(--text-muted)]">Cargando sedes desde PostgreSQL...</p>
      </div>

      <!-- VISTA TABLA COMPACTA -->
      <div v-else-if="modoVista === 'tabla'" class="table-container">
        <table class="data-table">
          <thead>
            <tr>
              <th>Urbanización / Sede</th>
              <th>Código / RUC</th>
              <th>Ubicación</th>
              <th>Capacidad</th>
              <th>Tema Activo</th>
              <th>Estado</th>
              <th class="text-right">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="urb in urbanizacionesFiltradas" :key="urb.urb_id">
              <td>
                <div class="flex items-center gap-3">
                  <div class="urb-avatar-sm">
                    {{ urb.urb_nombre.substring(0, 2).toUpperCase() }}
                  </div>
                  <div>
                    <div class="font-bold text-xs text-[var(--text-primary)]">
                      {{ urb.urb_nombre }}
                    </div>
                    <span class="text-[10px] text-[var(--text-muted)]">{{ urb.urb_telefono || 'Sin teléfono' }}</span>
                  </div>
                </div>
              </td>
              <td>
                <span class="id-badge font-mono">{{ urb.urb_codigo }}</span>
                <span v-if="urb.urb_ruc" class="text-[10px] text-[var(--text-muted)] block font-mono">RUC: {{ urb.urb_ruc }}</span>
              </td>
              <td>
                <div class="text-xs text-[var(--text-secondary)]">
                  <span class="font-semibold block">{{ urb.urb_ciudad }}</span>
                  <span class="text-[10px] text-[var(--text-muted)] block truncate max-w-[200px]">{{ urb.urb_direccion }}</span>
                </div>
              </td>
              <td>
                <div class="flex items-center gap-2 text-xs">
                  <span class="pill-stat-xs"><Home :size="11" /> {{ urb.inmuebles_count || 0 }}</span>
                  <span class="pill-stat-xs"><ShieldCheck :size="11" /> {{ urb.garitas_count || 0 }}</span>
                </div>
              </td>
              <td>
                <span class="badge-tema-xs">
                  <Palette :size="11" /> {{ urb.tema?.tem_nombre || 'Robledal Dark' }}
                </span>
              </td>
              <td>
                <span :class="urb.urb_activo ? 'status-active' : 'status-inactive'">
                  {{ urb.urb_activo ? 'Activa' : 'Inactiva' }}
                </span>
              </td>
              <td class="text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <button @click="abrirDetalleSede(urb)" class="btn-row-action btn-row-primary" title="Gestionar Sede Integral (Inmuebles & Residentes)">
                    <Sliders :size="13" />
                  </button>
                  <button @click="seleccionarSede(urb)" class="btn-row-action" title="Operar Garita de esta Sede">
                    <LogIn :size="13" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- VISTA TARJETAS DE SEDES -->
      <div v-else class="urbanizaciones-grid">
        <div v-for="urb in urbanizacionesFiltradas" :key="urb.urb_id" class="urb-card">
          <div class="urb-card-header">
            <div>
              <span class="code-badge">{{ urb.urb_codigo }}</span>
              <h3 class="urb-name">{{ urb.urb_nombre }}</h3>
              <p class="urb-city">{{ urb.urb_ciudad }} &bull; {{ urb.urb_direccion }}</p>
            </div>
            <span :class="urb.urb_activo ? 'status-active' : 'status-inactive'">
              {{ urb.urb_activo ? 'Activa' : 'Inactiva' }}
            </span>
          </div>

          <div class="urb-stats">
            <div class="stat-pill">
              <Home :size="14" class="text-[var(--color-primary)]" />
              <span>{{ urb.inmuebles_count || 0 }} Inmuebles</span>
            </div>
            <div class="stat-pill">
              <ShieldCheck :size="14" class="text-[var(--color-primary)]" />
              <span>{{ urb.garitas_count || 0 }} Garitas</span>
            </div>
            <div class="stat-pill">
              <Palette :size="14" class="text-[var(--color-primary)]" />
              <span>{{ urb.tema?.tem_nombre || 'Robledal Dark' }}</span>
            </div>
          </div>

          <!-- Resumen de Reglas de Sede -->
          <div class="reglas-preview-box">
            <div class="regla-item">
              <Clock :size="12" class="text-[var(--color-primary)]" />
              <span>Max Visita: <strong>{{ getConfig(urb, 'tiempo_maximo_visita_minutos', 240) }} min</strong></span>
            </div>
            <div class="regla-item">
              <Hourglass :size="12" class="text-amber-400" />
              <span>Tolerancia Salida: <strong>{{ getConfig(urb, 'tolerancia_salida_minutos', 15) }} min</strong></span>
            </div>
            <div class="regla-item">
              <UserCheck :size="12" class="text-emerald-400" />
              <span>Exigir Cédula Acompañantes: <strong>{{ getConfig(urb, 'exigir_cedula_acompanantes', true) ? 'Sí' : 'No' }}</strong></span>
            </div>
          </div>

          <!-- Acciones en la base de la tarjeta -->
          <div class="urb-footer-actions">
            <button @click="abrirDetalleSede(urb)" class="btn-config flex items-center justify-center gap-1.5" title="Gestionar Sede Completa (Inmuebles, Residentes y Reglas)">
              <Sliders :size="14" />
              <span>Gestionar Sede</span>
            </button>
            <button @click="seleccionarSede(urb)" class="btn-action flex items-center justify-center gap-1.5" title="Ingresar a la Garita">
              <LogIn :size="14" />
              <span>Operar Garita</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- NIVEL 2: FICHA DE LA SEDE EN PANTALLA COMPLETA (PADRE + HIJOS) -->
    <!-- ============================================================== -->
    <div v-else class="nivel-detalle-sede">
      <!-- Barra Superior de Navegación y Retorno -->
      <div class="detalle-topbar">
        <button @click="cerrarDetalleSede" class="btn-back">
          <ArrowLeft :size="16" />
          <span>Volver a Sedes</span>
        </button>

        <div class="sede-badge-header">
          <div class="urb-avatar-md">
            {{ sedeSeleccionada.urb_nombre?.substring(0, 2).toUpperCase() }}
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h2 class="text-base font-bold text-[var(--text-primary)]">{{ sedeSeleccionada.urb_nombre }}</h2>
              <span class="code-badge">{{ sedeSeleccionada.urb_codigo }}</span>
              <span :class="sedeSeleccionada.urb_activo ? 'status-active' : 'status-inactive'">
                {{ sedeSeleccionada.urb_activo ? 'Activa' : 'Inactiva' }}
              </span>
            </div>
            <p class="text-xs text-[var(--text-secondary)] mt-0.5">
              {{ sedeSeleccionada.urb_ciudad }} &bull; {{ sedeSeleccionada.urb_direccion }} &bull; {{ sedeSeleccionada.urb_telefono || 'Sin teléfono' }}
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <button @click="seleccionarSede(sedeSeleccionada)" class="btn-primary flex items-center gap-1.5 text-xs py-2 px-3">
            <LogIn :size="14" />
            <span>Operar Garita</span>
          </button>
        </div>
      </div>

      <!-- TABS DE TRABAJO A PANTALLA COMPLETA -->
      <div class="detalle-tabs-bar">
        <button 
          class="detalle-tab-btn" 
          :class="{ 'tab-btn-active': tabActiva === 'inmuebles' }"
          @click="tabActiva = 'inmuebles'"
        >
          <Home :size="15" />
          <span>Inmuebles & Residentes (Hijos & Nietos)</span>
        </button>

        <button 
          class="detalle-tab-btn" 
          :class="{ 'tab-btn-active': tabActiva === 'alicuotas' }"
          @click="tabActiva = 'alicuotas'"
        >
          <DollarSign :size="15" />
          <span>Alícuotas, Tesorería & Cartera</span>
        </button>

        <button 
          class="detalle-tab-btn" 
          :class="{ 'tab-btn-active': tabActiva === 'reglas' }"
          @click="tabActiva = 'reglas'"
        >
          <Sliders :size="15" />
          <span>Reglas de Sede & Políticas de Garita</span>
        </button>

        <button 
          class="detalle-tab-btn" 
          :class="{ 'tab-btn-active': tabActiva === 'datos' }"
          @click="tabActiva = 'datos'"
        >
          <Pencil :size="15" />
          <span>Editar Datos Institucionales</span>
        </button>
      </div>

      <!-- CONTENIDO TAB 1: INMUEBLES & RESIDENTES (PANTALLA COMPLETA) -->
      <div v-show="tabActiva === 'inmuebles'" class="tab-view-content">
        <InmueblesView :urbanizacion-id="sedeSeleccionada.urb_id" />
      </div>

      <!-- CONTENIDO TAB ALÍCUOTAS & TESORERÍA -->
      <div v-show="tabActiva === 'alicuotas'" class="tab-view-content">
        <AlicuotasView :urbanizacion-id="sedeSeleccionada.urb_id" />
      </div>

      <!-- CONTENIDO TAB 2: REGLAS DE SEDE Y PARÁMETROS -->
      <div v-show="tabActiva === 'reglas'" class="tab-view-content">
        <div class="card-section-full">
          <div class="section-top-header">
            <div>
              <h3 class="text-sm font-bold text-[var(--text-primary)]">Reglas Operativas & Límites de Garita</h3>
              <p class="text-xs text-[var(--text-muted)]">Afecta en tiempo real al cálculo del semáforo y alarmas sonoras de guardia</p>
            </div>
            <button @click="guardarSoloReglas" :disabled="guardando" class="btn-primary flex items-center gap-1.5">
              <Check :size="14" />
              <span>{{ guardando ? 'Guardando...' : 'Guardar Reglas' }}</span>
            </button>
          </div>

          <form @submit.prevent="guardarSoloReglas" class="space-y-6 max-w-4xl mt-4">
            <div class="bg-[var(--bg-primary)] p-4 rounded-xl border border-[var(--border-subtle)] space-y-4">
              <div class="flex items-center gap-2 text-xs font-bold text-[var(--color-primary)] uppercase tracking-wider">
                <Clock :size="15" /> Tiempos de Permanencia y Alertas
              </div>

              <div class="grid-3-cols">
                <div class="input-block">
                  <label>Tiempo Máximo Visita Normal (Minutos) *</label>
                  <input v-model.number="formReglas.tiempo_maximo_visita_minutos" type="number" class="form-input" required />
                  <span class="text-[10px] text-[var(--text-muted)]">Dispara alarma si la visita excede este tiempo</span>
                </div>
                <div class="input-block">
                  <label>Tolerancia de Salida (Minutos) *</label>
                  <input v-model.number="formReglas.tolerancia_salida_minutos" type="number" class="form-input" required />
                  <span class="text-[10px] text-[var(--text-muted)]">Margen de gracia permitido</span>
                </div>
                <div class="input-block">
                  <label>Tiempo Máximo Delivery (Minutos) *</label>
                  <input v-model.number="formReglas.tiempo_maximo_delivery_minutos" type="number" class="form-input" required />
                  <span class="text-[10px] text-[var(--text-muted)]">Uber Eats, Rappi, mensajería</span>
                </div>
              </div>
            </div>

            <div class="bg-[var(--bg-primary)] p-4 rounded-xl border border-[var(--border-subtle)] space-y-4">
              <div class="flex items-center gap-2 text-xs font-bold text-amber-400 uppercase tracking-wider">
                <ShieldAlert :size="15" /> Políticas de Identificación & Control Perimetral
              </div>

              <div class="space-y-3">
                <label class="checkbox-item-row">
                  <input type="checkbox" v-model="formReglas.exigir_cedula_acompanantes" class="form-checkbox" />
                  <div>
                    <span class="font-bold text-xs text-[var(--text-primary)]">Exigir Cédula a Todos los Acompañantes</span>
                    <p class="text-[11px] text-[var(--text-muted)]">El guardia deberá registrar obligatoriamente los datos de cada persona a bordo del vehículo.</p>
                  </div>
                </label>

                <label class="checkbox-item-row">
                  <input type="checkbox" v-model="formReglas.bloquear_morosos_garita" class="form-checkbox" />
                  <div>
                    <span class="font-bold text-xs text-[var(--text-primary)]">Alerta Informativa de Alícuota Pendiente en Garita</span>
                    <p class="text-[11px] text-[var(--text-muted)]">Muestra aviso amarillo en pantalla cuando la villa presenta cuotas de mantenimiento pendientes.</p>
                  </div>
                </label>

                <label class="checkbox-item-row">
                  <input type="checkbox" v-model="formReglas.habilitar_reconocimiento_lpr" class="form-checkbox" />
                  <div>
                    <span class="font-bold text-xs text-[var(--text-primary)]">Habilitar Apertura Automática LPR / Tags RFID</span>
                    <p class="text-[11px] text-[var(--text-muted)]">Apertura expedita sin intervención del guardia para residentes con Tag UHF activo.</p>
                  </div>
                </label>
              </div>
            </div>
          </form>
        </div>
      </div>

      <!-- CONTENIDO TAB 3: DATOS INSTITUCIONALES -->
      <div v-show="tabActiva === 'datos'" class="tab-view-content">
        <div class="card-section-full">
          <div class="section-top-header">
            <div>
              <h3 class="text-sm font-bold text-[var(--text-primary)]">Ficha Institucional & Parametrización</h3>
              <p class="text-xs text-[var(--text-muted)]">Identificación legal, ubicación y personalización visual</p>
            </div>
            <button @click="guardarSoloDatos" :disabled="guardando" class="btn-primary flex items-center gap-1.5">
              <Check :size="14" />
              <span>{{ guardando ? 'Guardando...' : 'Guardar Cambios' }}</span>
            </button>
          </div>

          <form @submit.prevent="guardarSoloDatos" class="space-y-4 max-w-3xl mt-4">
            <div class="grid-2-cols">
              <div class="input-block">
                <label>Código Identificador *</label>
                <input v-model="form.urb_codigo" class="form-input uppercase" required />
              </div>
              <div class="input-block">
                <label>Nombre de la Sede *</label>
                <input v-model="form.urb_nombre" class="form-input" required />
              </div>
            </div>

            <div class="grid-2-cols">
              <div class="input-block">
                <label>RUC Institucional</label>
                <input v-model="form.urb_ruc" class="form-input" />
              </div>
              <div class="input-block">
                <label>Ciudad *</label>
                <input v-model="form.urb_ciudad" class="form-input" required />
              </div>
            </div>

            <div class="input-block">
              <label>Dirección Completa *</label>
              <input v-model="form.urb_direccion" class="form-input" required />
            </div>

            <div class="grid-2-cols">
              <div class="input-block">
                <label>Teléfono de Contacto</label>
                <input v-model="form.urb_telefono" class="form-input" />
              </div>
              <div class="input-block">
                <label>Tema Visual</label>
                <select v-model="form.urb_tema_id" class="form-select">
                  <option v-for="t in temasList" :key="t.tem_id" :value="t.tem_id">
                    {{ t.tem_nombre }}
                  </option>
                </select>
              </div>
            </div>

            <div class="checkbox-container mt-4">
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" v-model="form.urb_activo" class="form-checkbox" />
                <span class="font-bold text-xs text-[var(--text-primary)]">Sede Urbanística Operativa / Activa</span>
              </label>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL RÁPIDO PARA CREAR NUEVA URBANIZACIÓN -->
    <!-- ============================================================== -->
    <div v-if="mostrarModalCrear" class="modal-backdrop" @click.self="mostrarModalCrear = false">
      <div class="modal-content modal-reglas-wide">
        <div class="modal-header">
          <div class="flex items-center gap-2">
            <Plus :size="18" class="text-[var(--color-primary)]" />
            <h3 class="text-base font-bold text-[var(--text-primary)]">Registrar Nueva Urbanización</h3>
          </div>
          <button @click="mostrarModalCrear = false" class="btn-close">&times;</button>
        </div>

        <form @submit.prevent="crearNuevaUrbanizacion" class="space-y-4">
          <div class="grid-2-cols">
            <div class="input-block">
              <label>Código Identificador *</label>
              <input v-model="formNuevo.urb_codigo" placeholder="Ej. URB-ROBLEDAL-02" class="form-input uppercase" required />
            </div>
            <div class="input-block">
              <label>Nombre de la Sede *</label>
              <input v-model="formNuevo.urb_nombre" placeholder="Ej. Condominio El Roble" class="form-input" required />
            </div>
          </div>

          <div class="grid-2-cols">
            <div class="input-block">
              <label>RUC Institucional</label>
              <input v-model="formNuevo.urb_ruc" placeholder="1790000000001" class="form-input" />
            </div>
            <div class="input-block">
              <label>Ciudad *</label>
              <input v-model="formNuevo.urb_ciudad" placeholder="Ej. Quito" class="form-input" required />
            </div>
          </div>

          <div class="input-block">
            <label>Dirección Completa *</label>
            <input v-model="formNuevo.urb_direccion" placeholder="Ej. Av. Interoceánica Km 12" class="form-input" required />
          </div>

          <div class="grid-2-cols">
            <div class="input-block">
              <label>Teléfono de Contacto</label>
              <input v-model="formNuevo.urb_telefono" placeholder="Ej. 022999999" class="form-input" />
            </div>
            <div class="input-block">
              <label>Tema de Marca</label>
              <select v-model="formNuevo.urb_tema_id" class="form-select">
                <option v-for="t in temasList" :key="t.tem_id" :value="t.tem_id">
                  {{ t.tem_nombre }}
                </option>
              </select>
            </div>
          </div>

          <div class="modal-actions pt-4 border-t border-[var(--border-subtle)]">
            <button type="button" @click="mostrarModalCrear = false" class="btn-secondary">Cancelar</button>
            <button type="submit" :disabled="guardando" class="btn-primary">
              {{ guardando ? 'Guardando en Base de Datos...' : 'Crear Urbanización' }}
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
  Plus, Search, Home, ShieldCheck, Palette, LogIn, Loader2, 
  Sliders, Clock, Hourglass, UserCheck, ShieldAlert, Pencil, Trash2, 
  LayoutGrid, Table, ArrowLeft, Check
} from 'lucide-vue-next';
import InmueblesView from './InmueblesView.vue';
import AlicuotasView from './AlicuotasView.vue';
import { DollarSign } from 'lucide-vue-next';

const emit = defineEmits(['cambiarVista', 'cambiarSede']);

const token = localStorage.getItem('residia_token');
const headersConfig = {
  headers: {
    'Authorization': `Bearer ${token}`
  }
};

const cargando = ref(true);
const guardando = ref(false);
const urbanizaciones = ref([]);
const temasList = ref([]);
const busqueda = ref('');

// Modo de visualización del catálogo (grid o tabla)
const modoVista = ref(localStorage.getItem('residia_vista_urbanizaciones') || 'grid');
const cambiarModoVista = (m) => {
  modoVista.value = m;
  localStorage.setItem('residia_vista_urbanizaciones', m);
};

// Navegación Drill-down: Sede seleccionada en pantalla completa
const sedeSeleccionada = ref(null);
const tabActiva = ref('inmuebles');

// Formularios
const mostrarModalCrear = ref(false);
const formNuevo = reactive({
  urb_codigo: '',
  urb_nombre: '',
  urb_ruc: '',
  urb_direccion: '',
  urb_ciudad: '',
  urb_telefono: '',
  urb_tema_id: 1,
});

const form = reactive({
  urb_codigo: '',
  urb_nombre: '',
  urb_ruc: '',
  urb_direccion: '',
  urb_ciudad: '',
  urb_telefono: '',
  urb_tema_id: 1,
  urb_activo: true,
});

const formReglas = reactive({
  tiempo_maximo_visita_minutos: 240,
  tolerancia_salida_minutos: 15,
  tiempo_maximo_delivery_minutos: 20,
  capacidad_carriles: 2,
  exigir_cedula_acompanantes: true,
  bloquear_morosos_garita: false,
  habilitar_reconocimiento_lpr: true
});

const urbanizacionesFiltradas = computed(() => {
  if (!busqueda.value) return urbanizaciones.value;
  const f = busqueda.value.toLowerCase();
  return urbanizaciones.value.filter(u => 
    u.urb_nombre?.toLowerCase().includes(f) ||
    u.urb_codigo?.toLowerCase().includes(f) ||
    u.urb_ciudad?.toLowerCase().includes(f)
  );
});

const getConfig = (urb, key, defaultVal) => {
  if (!urb || !urb.urb_configuracion) return defaultVal;
  const cfg = typeof urb.urb_configuracion === 'string' 
    ? JSON.parse(urb.urb_configuracion) 
    : urb.urb_configuracion;
  return cfg[key] !== undefined ? cfg[key] : defaultVal;
};

const cargarUrbanizaciones = async () => {
  cargando.value = true;
  try {
    const res = await axios.get('/api/urbanizaciones', headersConfig);
    if (res.data.success) {
      urbanizaciones.value = res.data.data;
      // Si hay una sede activa seleccionada, actualizarla
      if (sedeSeleccionada.value) {
        const encontrada = res.data.data.find(u => u.urb_id === sedeSeleccionada.value.urb_id);
        if (encontrada) sedeSeleccionada.value = encontrada;
      }
    }
  } catch (e) {
    console.error('Error cargando urbanizaciones:', e);
  } finally {
    cargando.value = false;
  }
};

const cargarTemas = async () => {
  try {
    const res = await axios.get('/api/temas', headersConfig);
    if (res.data.success) {
      temasList.value = res.data.data;
      if (temasList.value.length > 0) {
        formNuevo.urb_tema_id = temasList.value[0].tem_id;
      }
    }
  } catch (e) {}
};

// Abrir detalle completo de sede (Nivel 2)
const abrirDetalleSede = (urb) => {
  sedeSeleccionada.value = urb;
  tabActiva.value = 'inmuebles';

  // Sincronizar formulario datos generales
  form.urb_codigo = urb.urb_codigo || '';
  form.urb_nombre = urb.urb_nombre || '';
  form.urb_ruc = urb.urb_ruc || '';
  form.urb_direccion = urb.urb_direccion || '';
  form.urb_ciudad = urb.urb_ciudad || '';
  form.urb_telefono = urb.urb_telefono || '';
  form.urb_tema_id = urb.urb_tema_id || 1;
  form.urb_activo = Boolean(urb.urb_activo);

  // Sincronizar reglas
  const cfg = typeof urb.urb_configuracion === 'string' 
    ? JSON.parse(urb.urb_configuracion || '{}') 
    : (urb.urb_configuracion || {});

  formReglas.tiempo_maximo_visita_minutos = cfg.tiempo_maximo_visita_minutos || 240;
  formReglas.tolerancia_salida_minutos = cfg.tolerancia_salida_minutos || 15;
  formReglas.tiempo_maximo_delivery_minutos = cfg.tiempo_maximo_delivery_minutos || 20;
  formReglas.capacidad_carriles = cfg.capacidad_carriles || 2;
  formReglas.exigir_cedula_acompanantes = cfg.exigir_cedula_acompanantes !== undefined ? Boolean(cfg.exigir_cedula_acompanantes) : true;
  formReglas.bloquear_morosos_garita = Boolean(cfg.bloquear_morosos_garita);
  formReglas.habilitar_reconocimiento_lpr = cfg.habilitar_reconocimiento_lpr !== undefined ? Boolean(cfg.habilitar_reconocimiento_lpr) : true;
};

const cerrarDetalleSede = () => {
  sedeSeleccionada.value = null;
  cargarUrbanizaciones();
};

const abrirModalCrear = () => {
  formNuevo.urb_codigo = '';
  formNuevo.urb_nombre = '';
  formNuevo.urb_ruc = '';
  formNuevo.urb_direccion = '';
  formNuevo.urb_ciudad = '';
  formNuevo.urb_telefono = '';
  mostrarModalCrear.value = true;
};

const crearNuevaUrbanizacion = async () => {
  guardando.value = true;
  try {
    const res = await axios.post('/api/urbanizaciones', formNuevo, headersConfig);
    if (res.data.success) {
      alert('Urbanización creada exitosamente.');
      mostrarModalCrear.value = false;
      await cargarUrbanizaciones();
      // Abrir inmediatamente la ficha completa de la sede recién creada
      abrirDetalleSede(res.data.data);
    }
  } catch (e) {
    alert(e.response?.data?.message || 'Error registrando urbanización');
  } finally {
    guardando.value = false;
  }
};

const guardarSoloReglas = async () => {
  if (!sedeSeleccionada.value) return;
  guardando.value = true;
  try {
    const payload = {
      ...form,
      urb_configuracion: { ...formReglas }
    };
    const res = await axios.put(`/api/urbanizaciones/${sedeSeleccionada.value.urb_id}`, payload, headersConfig);
    if (res.data.success) {
      alert('Reglas de la sede actualizadas correctamente.');
      sedeSeleccionada.value.urb_configuracion = { ...formReglas };
      await cargarUrbanizaciones();
    }
  } catch (e) {
    alert(e.response?.data?.message || 'Error guardando reglas');
  } finally {
    guardando.value = false;
  }
};

const guardarSoloDatos = async () => {
  if (!sedeSeleccionada.value) return;
  guardando.value = true;
  try {
    const payload = {
      ...form,
      urb_configuracion: { ...formReglas }
    };
    const res = await axios.put(`/api/urbanizaciones/${sedeSeleccionada.value.urb_id}`, payload, headersConfig);
    if (res.data.success) {
      alert('Datos institucionales actualizados correctamente.');
      sedeSeleccionada.value.urb_nombre = form.urb_nombre;
      sedeSeleccionada.value.urb_codigo = form.urb_codigo;
      await cargarUrbanizaciones();
    }
  } catch (e) {
    alert(e.response?.data?.message || 'Error actualizando datos de urbanización');
  } finally {
    guardando.value = false;
  }
};

const seleccionarSede = (urb) => {
  localStorage.setItem('residia_urb_id', urb.urb_id);
  localStorage.setItem('residia_urb_nombre', urb.urb_nombre);
  emit('cambiarSede', urb);
  emit('cambiarVista', 'garita');
};

onMounted(() => {
  cargarUrbanizaciones();
  cargarTemas();
});
</script>

<style scoped>
.urbanizaciones-view {
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

.search-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
  gap: 12px;
}
.search-input-wrap {
  flex: 1;
  position: relative;
  display: flex;
  align-items: center;
}
.search-icon {
  position: absolute;
  left: 14px;
  color: var(--text-muted);
}
.input-search {
  width: 100%;
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  color: var(--text-primary);
  padding: 10px 14px 10px 42px;
  border-radius: 10px;
  font-size: 13px;
  outline: none;
  transition: border-color 0.2s;
}
.input-search:focus {
  border-color: var(--color-primary);
}

.view-mode-toggle {
  display: flex;
  background: var(--bg-hover);
  border: 1px solid var(--border-subtle);
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

/* CARDS DE SEDES */
.urbanizaciones-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
  gap: 20px;
}
.urb-card {
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 14px;
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
  transition: transform 0.2s, border-color 0.2s;
}
.urb-card:hover {
  border-color: var(--color-primary);
  transform: translateY(-2px);
}
.urb-card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}
.code-badge {
  display: inline-block;
  font-family: monospace;
  font-size: 10px;
  font-weight: 700;
  background: var(--bg-primary);
  border: 1px solid var(--border-subtle);
  color: var(--color-primary);
  padding: 2px 6px;
  border-radius: 4px;
  margin-bottom: 6px;
}
.urb-name {
  font-size: 17px;
  font-weight: 700;
  color: var(--text-primary);
}
.urb-city {
  font-size: 12px;
  color: var(--text-secondary);
  margin-top: 2px;
}
.status-active {
  background: rgba(16, 185, 129, 0.12);
  color: #34d399;
  border: 1px solid rgba(16, 185, 129, 0.3);
  font-size: 11px;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 6px;
}
.status-inactive {
  background: rgba(239, 68, 68, 0.12);
  color: #f87171;
  border: 1px solid rgba(239, 68, 68, 0.3);
  font-size: 11px;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 6px;
}

.urb-stats {
  display: flex;
  gap: 10px;
}
.stat-pill {
  display: flex;
  align-items: center;
  gap: 6px;
  background: var(--bg-primary);
  border: 1px solid var(--border-subtle);
  padding: 6px 10px;
  border-radius: 8px;
  font-size: 12px;
  color: var(--text-secondary);
}

.reglas-preview-box {
  background: var(--bg-primary);
  border: 1px dashed var(--border-subtle);
  border-radius: 10px;
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.regla-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 11px;
  color: var(--text-secondary);
}

.urb-footer-actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  margin-top: auto;
  padding-top: 8px;
  border-top: 1px solid var(--border-subtle);
}
.btn-config {
  background: var(--bg-primary);
  border: 1px solid var(--border-subtle);
  color: var(--text-primary);
  padding: 9px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-config:hover {
  background: var(--bg-hover);
  border-color: var(--color-primary);
  color: var(--color-primary);
}
.btn-action {
  background: var(--bg-hover);
  border: 1px solid var(--border-subtle);
  color: var(--text-primary);
  padding: 9px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-action:hover {
  background: var(--color-primary);
  color: #0b0f19;
  border-color: var(--color-primary);
}

/* ============================================================== */
/* ESTILOS NIVEL 2: FICHA DE LA SEDE EN PANTALLA COMPLETA */
/* ============================================================== */
.nivel-detalle-sede {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.detalle-topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 14px;
  padding: 14px 20px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}
.btn-back {
  display: flex;
  align-items: center;
  gap: 8px;
  background: var(--bg-primary);
  border: 1px solid var(--border-subtle);
  color: var(--text-secondary);
  padding: 8px 14px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-back:hover {
  background: var(--bg-hover);
  color: var(--color-primary);
  border-color: var(--color-primary);
}
.sede-badge-header {
  display: flex;
  align-items: center;
  gap: 14px;
}
.urb-avatar-md {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  background: rgba(16, 185, 129, 0.15);
  color: var(--color-primary);
  border: 1px solid rgba(16, 185, 129, 0.3);
  font-size: 15px;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* BARRA DE TABS DE LA SEDE */
.detalle-tabs-bar {
  display: flex;
  gap: 8px;
  border-bottom: 1px solid var(--border-subtle);
  padding-bottom: 2px;
}
.detalle-tab-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  border-radius: 10px 10px 0 0;
  font-size: 13px;
  font-weight: 600;
  color: var(--text-muted);
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-bottom: none;
  cursor: pointer;
  transition: all 0.2s;
}
.detalle-tab-btn:hover {
  color: var(--text-primary);
  background: var(--bg-hover);
}
.tab-btn-active {
  background: var(--color-primary);
  color: #0b0f19;
  border-color: var(--color-primary);
  font-weight: 700;
}

.tab-view-content {
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 0 0 14px 14px;
  padding: 20px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.card-section-full {
  background: transparent;
}
.section-top-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 14px;
  border-bottom: 1px solid var(--border-subtle);
}

/* FORMULARIOS Y CHECKBOXES */
.input-block {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.input-block label {
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  color: var(--text-secondary);
}
.form-input, .form-select {
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  color: var(--text-primary);
  padding: 9px 12px;
  border-radius: 8px;
  font-size: 13px;
  outline: none;
  width: 100%;
}
.grid-2-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.grid-3-cols { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }
@media (max-width: 768px) {
  .grid-2-cols, .grid-3-cols { grid-template-columns: 1fr; }
}

.checkbox-item-row {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 10px 14px;
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 8px;
  cursor: pointer;
}
.checkbox-item-row:hover {
  border-color: var(--color-primary);
}
.form-checkbox {
  margin-top: 3px;
  cursor: pointer;
}

/* TABLA DATATABLE COMPACTA */
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
.data-table tbody tr:hover {
  background: var(--bg-hover);
}
.urb-avatar-sm {
  width: 34px;
  height: 34px;
  border-radius: 8px;
  background: rgba(16, 185, 129, 0.12);
  color: var(--color-primary);
  border: 1px solid rgba(16, 185, 129, 0.3);
  font-size: 12px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.pill-stat-xs {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 2px 7px;
  border-radius: 6px;
  background: var(--bg-primary);
  border: 1px solid var(--border-subtle);
  font-size: 10px;
  color: var(--text-secondary);
}
.badge-tema-xs {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 2px 7px;
  border-radius: 6px;
  background: rgba(168, 85, 247, 0.1);
  color: #c084fc;
  border: 1px solid rgba(168, 85, 247, 0.25);
  font-size: 10px;
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
.btn-row-primary:hover {
  background: var(--color-primary);
  color: #0b0f19;
  border-color: var(--color-primary);
}

/* BOTONES GLOBALES Y MODAL RÁPIDO */
.btn-primary {
  background: var(--color-primary);
  color: #0b0f19;
  padding: 9px 16px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 700;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-primary:hover {
  opacity: 0.9;
  transform: translateY(-1px);
}
.btn-secondary {
  background: var(--bg-primary);
  border: 1px solid var(--border-subtle);
  color: var(--text-primary);
  padding: 8px 14px;
  border-radius: 8px;
  font-size: 12px;
  cursor: pointer;
}
.modal-backdrop {
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
.modal-content {
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 14px;
  width: 100%;
  max-width: 650px;
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
.btn-close {
  background: transparent;
  border: none;
  font-size: 20px;
  color: var(--text-muted);
  cursor: pointer;
}
.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
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
