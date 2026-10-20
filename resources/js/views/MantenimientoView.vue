<template>
  <div class="mantenimiento-page animate-fade-in">
    <!-- Header -->
    <div class="page-header">
      <div class="header-titles">
        <div class="badge-tag">
          <Wrench :size="14" class="text-amber-400" />
          <span>Facility Management & Operaciones</span>
        </div>
        <h1 class="page-title">Mantenimiento Preventivo & Órdenes de Trabajo</h1>
        <p class="page-subtitle">
          Control de activos críticos de la urbanización (bombas, generadores, luminarias), contratos con proveedores externos y fiscalización de costos.
        </p>
      </div>

      <div class="header-actions">
        <button @click="abrirModalProveedor" class="btn-secondary">
          <Truck :size="16" />
          <span>+ Nuevo Proveedor</span>
        </button>

        <button @click="abrirModalActivo" class="btn-secondary">
          <Cog :size="16" />
          <span>+ Registrar Activo</span>
        </button>

        <button @click="abrirModalOrden" class="btn-primary">
          <Plus :size="16" />
          <span>+ Nueva Orden de Trabajo</span>
        </button>
      </div>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-icon-wrap bg-amber-500/10 text-amber-400">
          <Clock :size="20" />
        </div>
        <div class="kpi-data">
          <span class="kpi-label">Órdenes en Curso / Pendientes</span>
          <span class="kpi-value">{{ kpis.ordenes_pendientes }}</span>
          <span class="kpi-desc">De un total de {{ kpis.total_ordenes }} generadas</span>
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-icon-wrap bg-emerald-500/10 text-emerald-400">
          <DollarSign :size="20" />
        </div>
        <div class="kpi-data">
          <span class="kpi-label">Inversión Ejecutada Mes</span>
          <span class="kpi-value">${{ formatMoneda(kpis.costo_total_mes) }}</span>
          <span class="kpi-desc">Liquidado con proveedores</span>
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-icon-wrap bg-rose-500/10 text-rose-400">
          <AlertTriangle :size="20" />
        </div>
        <div class="kpi-data">
          <span class="kpi-label">Activos en Alerta / Vencidos</span>
          <span class="kpi-value">{{ kpis.activos_alerta }}</span>
          <span class="kpi-desc">Requieren servicio preventivo</span>
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-icon-wrap bg-cyan-500/10 text-cyan-400">
          <ShieldCheck :size="20" />
        </div>
        <div class="kpi-data">
          <span class="kpi-label">Parque de Infraestructura</span>
          <span class="kpi-value">{{ kpis.total_activos }}</span>
          <span class="kpi-desc">{{ kpis.total_proveedores }} contratistas homologados</span>
        </div>
      </div>
    </div>

    <!-- Pestañas de Navegación -->
    <div class="tabs-nav">
      <button 
        @click="tabActiva = 'ordenes'" 
        :class="{ 'tab-btn-active': tabActiva === 'ordenes' }" 
        class="tab-btn"
      >
        <ClipboardList :size="16" />
        <span>Órdenes de Trabajo ({{ ordenes.length }})</span>
      </button>

      <button 
        @click="tabActiva = 'activos'" 
        :class="{ 'tab-btn-active': tabActiva === 'activos' }" 
        class="tab-btn"
      >
        <Boxes :size="16" />
        <span>Catálogo de Activos & Maquinaria ({{ activos.length }})</span>
      </button>

      <button 
        @click="tabActiva = 'proveedores'" 
        :class="{ 'tab-btn-active': tabActiva === 'proveedores' }" 
        class="tab-btn"
      >
        <Building2 :size="16" />
        <span>Contratistas & Proveedores ({{ proveedores.length }})</span>
      </button>
    </div>

    <!-- TAB 1: Órdenes de Trabajo -->
    <div v-if="tabActiva === 'ordenes'" class="tab-content animate-fade-in">
      <div class="filter-bar">
        <div class="search-box">
          <Search :size="16" class="search-icon" />
          <input 
            v-model="filtroOrdenes" 
            type="text" 
            placeholder="Buscar por folio, título, activo o proveedor..."
            class="search-input"
          />
        </div>

        <div class="filter-actions">
          <select v-model="filtroEstadoOrden" class="filter-select">
            <option value="TODOS">Todos los estados</option>
            <option value="PENDIENTE">Pendientes</option>
            <option value="EN_PROCESO">En Proceso</option>
            <option value="COMPLETADA">Completadas</option>
          </select>
        </div>
      </div>

      <div class="table-container">
        <div v-if="cargando" class="loading-state">
          <Loader2 :size="32" class="animate-spin text-amber-400" />
          <p>Cargando órdenes de trabajo...</p>
        </div>

        <table v-else class="mnt-table">
          <thead>
            <tr>
              <th>Folio</th>
              <th>Actividad & Descripción</th>
              <th>Activo / Ubicación</th>
              <th>Contratista</th>
              <th>Programación</th>
              <th>Costo Real / Estimado</th>
              <th>Estado</th>
              <th class="text-right">Acción</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="ord in ordenesFiltradas" :key="ord.ord_id" class="table-row">
              <td>
                <span class="folio-code">{{ ord.ord_codigo_folio }}</span>
                <span class="type-sub-badge" :class="'badge-' + ord.ord_tipo.toLowerCase()">
                  {{ ord.ord_tipo }}
                </span>
              </td>

              <td>
                <div class="title-cell">
                  <span class="ord-title">{{ ord.ord_titulo }}</span>
                  <span class="ord-desc">{{ ord.ord_descripcion }}</span>
                </div>
              </td>

              <td>
                <div v-if="ord.activo_nombre" class="activo-cell">
                  <span class="activo-name">{{ ord.activo_nombre }}</span>
                  <span class="activo-loc">{{ ord.activo_ubicacion }}</span>
                </div>
                <span v-else class="text-muted text-xs">Mantenimiento General</span>
              </td>

              <td>
                <div v-if="ord.proveedor_nombre" class="prv-cell">
                  <span class="prv-name">{{ ord.proveedor_nombre }}</span>
                  <span class="prv-phone">{{ ord.proveedor_telefono }}</span>
                </div>
                <span v-else class="text-muted text-xs">Personal Interno</span>
              </td>

              <td>
                <span class="date-badge">{{ formatearFecha(ord.ord_fecha_programada) }}</span>
              </td>

              <td>
                <div class="cost-cell">
                  <span class="cost-real">${{ formatMoneda(ord.ord_costo_real > 0 ? ord.ord_costo_real : ord.ord_costo_estimado) }}</span>
                  <span class="cost-sub">Est: ${{ formatMoneda(ord.ord_costo_estimado) }}</span>
                </div>
              </td>

              <td>
                <span class="status-pill" :class="'status-' + ord.ord_estado.toLowerCase()">
                  {{ ord.ord_estado.replace('_', ' ') }}
                </span>
              </td>

              <td class="text-right">
                <button 
                  v-if="ord.ord_estado !== 'COMPLETADA'"
                  @click="abrirModalGestionOrden(ord)" 
                  class="btn-row-action"
                  title="Actualizar estado o liquidar"
                >
                  <Edit :size="14" />
                </button>
                <span v-else class="text-emerald-400 text-xs font-semibold">Cerrada ✓</span>
              </td>
            </tr>

            <tr v-if="ordenesFiltradas.length === 0">
              <td colspan="8" class="empty-state">
                <ClipboardX :size="36" class="text-slate-600 mb-2" />
                <p>No hay órdenes registradas con los filtros seleccionados.</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- TAB 2: Catálogo de Activos -->
    <div v-if="tabActiva === 'activos'" class="tab-content animate-fade-in">
      <div class="cards-grid">
        <div v-for="act in activos" :key="act.act_id" class="card-item">
          <div class="card-header">
            <div class="card-header-icon bg-cyan-500/10 text-cyan-400">
              <Cog :size="20" />
            </div>
            <div>
              <span class="item-code">{{ act.act_codigo }}</span>
              <h4 class="item-title">{{ act.act_nombre }}</h4>
            </div>
          </div>

          <div class="card-body">
            <div class="info-row">
              <span class="info-label">Ubicación:</span>
              <span class="info-val">{{ act.act_ubicacion }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Categoría:</span>
              <span class="info-val">{{ act.act_categoria }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Frecuencia:</span>
              <span class="info-val">Cada {{ act.act_frecuencia_dias_mantenimiento }} días</span>
            </div>
            <div class="info-row">
              <span class="info-label">Próximo Servicio:</span>
              <span class="info-val text-amber-400 font-semibold">{{ formatearFecha(act.act_proximo_mantenimiento) }}</span>
            </div>
          </div>

          <div class="card-footer">
            <span class="status-pill" :class="'act-status-' + act.act_estado_operativo.toLowerCase()">
              {{ act.act_estado_operativo.replace('_', ' ') }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 3: Proveedores y Contratistas -->
    <div v-if="tabActiva === 'proveedores'" class="tab-content animate-fade-in">
      <div class="cards-grid">
        <div v-for="prv in proveedores" :key="prv.prv_id" class="card-item">
          <div class="card-header">
            <div class="card-header-icon bg-purple-500/10 text-purple-400">
              <Building2 :size="20" />
            </div>
            <div>
              <h4 class="item-title">{{ prv.prv_razon_social }}</h4>
              <span class="item-code">RUC: {{ prv.prv_ruc_identificacion || 'No registrado' }}</span>
            </div>
          </div>

          <div class="card-body">
            <div class="info-row">
              <span class="info-label">Especialidad:</span>
              <span class="info-val">{{ prv.prv_categoria }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Contacto:</span>
              <span class="info-val">{{ prv.prv_contacto_nombre || 'No asignado' }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Teléfono:</span>
              <span class="info-val text-cyan-400">{{ prv.prv_telefono || 'S/N' }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Email:</span>
              <span class="info-val">{{ prv.prv_email || 'S/N' }}</span>
            </div>
          </div>

          <div class="card-footer">
            <span class="badge-online">HOMOLOGADO</span>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL 1: Nueva Orden de Trabajo -->
    <div v-if="modalOrden" class="modal-overlay" @click.self="modalOrden = false">
      <div class="modal-box animate-scale-up">
        <div class="modal-header">
          <h3 class="modal-title">Programar Nueva Orden de Trabajo</h3>
          <button @click="modalOrden = false" class="modal-close-btn">&times;</button>
        </div>

        <form @submit.prevent="guardarOrden" class="modal-body">
          <div class="form-group">
            <label class="form-label">Título de la Actividad *</label>
            <input v-model="formOrden.titulo" required placeholder="Ej: Mantenimiento Preventivo de Bomba..." class="form-input" />
          </div>

          <div class="form-row">
            <div class="form-group flex-1">
              <label class="form-label">Tipo de Orden *</label>
              <select v-model="formOrden.tipo" class="form-select">
                <option value="PREVENTIVO">Mantenimiento Preventivo</option>
                <option value="CORRECTIVO">Reparación Correctiva</option>
                <option value="EMERGENCIA">Emergencia / Crítico</option>
              </select>
            </div>

            <div class="form-group flex-1">
              <label class="form-label">Prioridad *</label>
              <select v-model="formOrden.prioridad" class="form-select">
                <option value="BAJA">Baja</option>
                <option value="MEDIA">Media</option>
                <option value="ALTA">Alta</option>
                <option value="CRITICA">Crítica</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group flex-1">
              <label class="form-label">Activo Vinculado</label>
              <select v-model="formOrden.activo_id" class="form-select">
                <option :value="null">-- Mantenimiento General --</option>
                <option v-for="a in activos" :key="a.act_id" :value="a.act_id">
                  {{ a.act_codigo }} - {{ a.act_nombre }}
                </option>
              </select>
            </div>

            <div class="form-group flex-1">
              <label class="form-label">Contratista Asignado</label>
              <select v-model="formOrden.proveedor_id" class="form-select">
                <option :value="null">-- Personal Interno --</option>
                <option v-for="p in proveedores" :key="p.prv_id" :value="p.prv_id">
                  {{ p.prv_razon_social }}
                </option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group flex-1">
              <label class="form-label">Fecha Programada *</label>
              <input v-model="formOrden.fecha_programada" type="date" required class="form-input" />
            </div>

            <div class="form-group flex-1">
              <label class="form-label">Costo Estimado ($ USD)</label>
              <input v-model.number="formOrden.costo_estimado" type="number" step="0.01" class="form-input" />
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Descripción de Trabajos & Alcance *</label>
            <textarea v-model="formOrden.descripcion" required rows="3" class="form-textarea" placeholder="Detalle las tareas a ejecutar, repuestos o insumos..."></textarea>
          </div>

          <div class="modal-footer">
            <button type="button" @click="modalOrden = false" class="btn-secondary">Cancelar</button>
            <button type="submit" class="btn-primary" :disabled="guardando">
              {{ guardando ? 'Guardando...' : 'Crear Orden' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL 2: Actualizar Estado de Orden -->
    <div v-if="modalGestion" class="modal-overlay" @click.self="modalGestion = false">
      <div class="modal-box animate-scale-up">
        <div class="modal-header">
          <h3 class="modal-title">Actualizar {{ ordenSeleccionada?.ord_codigo_folio }}</h3>
          <button @click="modalGestion = false" class="modal-close-btn">&times;</button>
        </div>

        <form @submit.prevent="actualizarEstado" class="modal-body">
          <div class="form-group">
            <label class="form-label">Estado de la Orden *</label>
            <select v-model="formGestion.estado" class="form-select">
              <option value="PENDIENTE">PENDIENTE</option>
              <option value="EN_PROCESO">EN PROCESO (Técnicos en Sitio)</option>
              <option value="COMPLETADA">COMPLETADA (Trabajos Recibidos)</option>
              <option value="CANCELADA">CANCELADA</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Costo Real Liquidado ($ USD)</label>
            <input v-model.number="formGestion.costo_real" type="number" step="0.01" class="form-input" />
          </div>

          <div class="form-group">
            <label class="form-label">Informe Técnico / Observaciones de Cierre</label>
            <textarea v-model="formGestion.informe_tecnico" rows="3" class="form-textarea" placeholder="Describa el resultado de los trabajos realizados..."></textarea>
          </div>

          <div class="modal-footer">
            <button type="button" @click="modalGestion = false" class="btn-secondary">Cancelar</button>
            <button type="submit" class="btn-primary" :disabled="guardando">
              {{ guardando ? 'Actualizando...' : 'Guardar Cambios' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL 3: Registrar Activo -->
    <div v-if="modalActivo" class="modal-overlay" @click.self="modalActivo = false">
      <div class="modal-box animate-scale-up">
        <div class="modal-header">
          <h3 class="modal-title">Registrar Activo de Infraestructura</h3>
          <button @click="modalActivo = false" class="modal-close-btn">&times;</button>
        </div>

        <form @submit.prevent="guardarActivo" class="modal-body">
          <div class="form-row">
            <div class="form-group flex-1">
              <label class="form-label">Código del Activo *</label>
              <input v-model="formActivo.codigo" required placeholder="Ej: BOMB-02" class="form-input uppercase" />
            </div>

            <div class="form-group flex-1">
              <label class="form-label">Categoría *</label>
              <input v-model="formActivo.categoria" required placeholder="Ej: HIDRONEUMATICO" class="form-input uppercase" />
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Nombre del Equipo *</label>
            <input v-model="formActivo.nombre" required placeholder="Ej: Bomba Centrífuga de 7.5 HP" class="form-input" />
          </div>

          <div class="form-group">
            <label class="form-label">Ubicación Física *</label>
            <input v-model="formActivo.ubicacion" required placeholder="Ej: Cisterna Garita Sur" class="form-input" />
          </div>

          <div class="form-row">
            <div class="form-group flex-1">
              <label class="form-label">Frecuencia Mantenimiento (Días) *</label>
              <input v-model.number="formActivo.frecuencia_dias" type="number" required min="1" class="form-input" />
            </div>

            <div class="form-group flex-1">
              <label class="form-label">Estado Operativo *</label>
              <select v-model="formActivo.estado_operativo" class="form-select">
                <option value="OPERATIVO">OPERATIVO</option>
                <option value="REQUIERE_SERVICIO">REQUIERE SERVICIO</option>
                <option value="FUERA_DE_SERVICIO">FUERA DE SERVICIO</option>
              </select>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" @click="modalActivo = false" class="btn-secondary">Cancelar</button>
            <button type="submit" class="btn-primary" :disabled="guardando">Guardar Activo</button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL 4: Registrar Proveedor -->
    <div v-if="modalProveedor" class="modal-overlay" @click.self="modalProveedor = false">
      <div class="modal-box animate-scale-up">
        <div class="modal-header">
          <h3 class="modal-title">Registrar Contratista / Proveedor</h3>
          <button @click="modalProveedor = false" class="modal-close-btn">&times;</button>
        </div>

        <form @submit.prevent="guardarProveedor" class="modal-body">
          <div class="form-group">
            <label class="form-label">Razón Social / Nombre Comercial *</label>
            <input v-model="formProveedor.razon_social" required placeholder="Ej: Piscinas & Filtros del Valle Cía. Ltda." class="form-input" />
          </div>

          <div class="form-row">
            <div class="form-group flex-1">
              <label class="form-label">RUC / Identificación</label>
              <input v-model="formProveedor.ruc_identificacion" placeholder="1790000000001" class="form-input" />
            </div>

            <div class="form-group flex-1">
              <label class="form-label">Especialidad / Categoría *</label>
              <input v-model="formProveedor.categoria" required placeholder="Ej: PISCINAS" class="form-input uppercase" />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group flex-1">
              <label class="form-label">Persona de Contacto</label>
              <input v-model="formProveedor.contacto_nombre" placeholder="Nombre del asesor técnico" class="form-input" />
            </div>

            <div class="form-group flex-1">
              <label class="form-label">Teléfono Móvil *</label>
              <input v-model="formProveedor.telefono" required placeholder="0991234567" class="form-input" />
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Correo Electrónico</label>
            <input v-model="formProveedor.email" type="email" placeholder="contacto@proveedor.com" class="form-input" />
          </div>

          <div class="modal-footer">
            <button type="button" @click="modalProveedor = false" class="btn-secondary">Cancelar</button>
            <button type="submit" class="btn-primary" :disabled="guardando">Guardar Proveedor</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { 
  Wrench, Clock, DollarSign, AlertTriangle, ShieldCheck, 
  ClipboardList, Boxes, Building2, Search, Plus, Edit, Truck, 
  Cog, Loader2, ClipboardX 
} from 'lucide-vue-next';

const props = defineProps({
  urbanizacionId: {
    type: String,
    required: true
  }
});

const tabActiva = ref('ordenes');
const cargando = ref(true);
const guardando = ref(false);

const ordenes = ref([]);
const activos = ref([]);
const proveedores = ref([]);
const kpis = ref({
  total_ordenes: 0,
  ordenes_pendientes: 0,
  costo_total_mes: 0,
  activos_alerta: 0,
  total_activos: 0,
  total_proveedores: 0
});

const filtroOrdenes = ref('');
const filtroEstadoOrden = ref('TODOS');

// Modales
const modalOrden = ref(false);
const modalGestion = ref(false);
const modalActivo = ref(false);
const modalProveedor = ref(false);
const ordenSeleccionada = ref(null);

const formOrden = ref({
  titulo: '',
  descripcion: '',
  activo_id: null,
  proveedor_id: null,
  tipo: 'PREVENTIVO',
  prioridad: 'MEDIA',
  fecha_programada: new Date().toISOString().split('T')[0],
  costo_estimado: 0.00,
  pre_autoriza_garita: true
});

const formGestion = ref({
  estado: 'PENDIENTE',
  costo_real: 0.00,
  informe_tecnico: ''
});

const formActivo = ref({
  codigo: '',
  nombre: '',
  ubicacion: '',
  categoria: '',
  frecuencia_dias: 90,
  estado_operativo: 'OPERATIVO'
});

const formProveedor = ref({
  razon_social: '',
  ruc_identificacion: '',
  categoria: '',
  contacto_nombre: '',
  telefono: '',
  email: ''
});

const cargarDatos = async () => {
  cargando.value = true;
  try {
    const res = await axios.get('/api/mantenimiento');
    if (res.data.success) {
      ordenes.value = res.data.data.ordenes;
      activos.value = res.data.data.activos;
      proveedores.value = res.data.data.proveedores;
      kpis.value = res.data.data.kpis;
    }
  } catch (error) {
    console.error('Error al cargar mantenimiento:', error);
  } finally {
    cargando.value = false;
  }
};

const ordenesFiltradas = computed(() => {
  return ordenes.value.filter(o => {
    const matchTxt = !filtroOrdenes.value ||
      o.ord_codigo_folio.toLowerCase().includes(filtroOrdenes.value.toLowerCase()) ||
      o.ord_titulo.toLowerCase().includes(filtroOrdenes.value.toLowerCase()) ||
      (o.activo_nombre && o.activo_nombre.toLowerCase().includes(filtroOrdenes.value.toLowerCase())) ||
      (o.proveedor_nombre && o.proveedor_nombre.toLowerCase().includes(filtroOrdenes.value.toLowerCase()));

    const matchEst = filtroEstadoOrden.value === 'TODOS' || o.ord_estado === filtroEstadoOrden.value;
    return matchTxt && matchEst;
  });
});

const abrirModalOrden = () => {
  formOrden.value = {
    titulo: '',
    descripcion: '',
    activo_id: null,
    proveedor_id: null,
    tipo: 'PREVENTIVO',
    prioridad: 'MEDIA',
    fecha_programada: new Date().toISOString().split('T')[0],
    costo_estimado: 0.00,
    pre_autoriza_garita: true
  };
  modalOrden.value = true;
};

const abrirModalGestionOrden = (ord) => {
  ordenSeleccionada.value = ord;
  formGestion.value = {
    estado: ord.ord_estado,
    costo_real: parseFloat(ord.ord_costo_real || ord.ord_costo_estimado || 0),
    informe_tecnico: ord.ord_informe_tecnico || ''
  };
  modalGestion.value = true;
};

const abrirModalActivo = () => {
  formActivo.value = {
    codigo: '',
    nombre: '',
    ubicacion: '',
    categoria: '',
    frecuencia_dias: 90,
    estado_operativo: 'OPERATIVO'
  };
  modalActivo.value = true;
};

const abrirModalProveedor = () => {
  formProveedor.value = {
    razon_social: '',
    ruc_identificacion: '',
    categoria: '',
    contacto_nombre: '',
    telefono: '',
    email: ''
  };
  modalProveedor.value = true;
};

const guardarOrden = async () => {
  guardando.value = true;
  try {
    const res = await axios.post('/api/mantenimiento/ordenes', formOrden.value);
    if (res.data.success) {
      modalOrden.value = false;
      await cargarDatos();
    }
  } catch (error) {
    alert('Error al programar orden: ' + (error.response?.data?.message || error.message));
  } finally {
    guardando.value = false;
  }
};

const actualizarEstado = async () => {
  if (!ordenSeleccionada.value) return;
  guardando.value = true;
  try {
    const res = await axios.post(`/api/mantenimiento/ordenes/${ordenSeleccionada.value.ord_id}/estado`, formGestion.value);
    if (res.data.success) {
      modalGestion.value = false;
      await cargarDatos();
    }
  } catch (error) {
    alert('Error al actualizar estado: ' + (error.response?.data?.message || error.message));
  } finally {
    guardando.value = false;
  }
};

const guardarActivo = async () => {
  guardando.value = true;
  try {
    const res = await axios.post('/api/mantenimiento/activos', formActivo.value);
    if (res.data.success) {
      modalActivo.value = false;
      await cargarDatos();
    }
  } catch (error) {
    alert('Error al registrar activo: ' + (error.response?.data?.message || error.message));
  } finally {
    guardando.value = false;
  }
};

const guardarProveedor = async () => {
  guardando.value = true;
  try {
    const res = await axios.post('/api/mantenimiento/proveedores', formProveedor.value);
    if (res.data.success) {
      modalProveedor.value = false;
      await cargarDatos();
    }
  } catch (error) {
    alert('Error al registrar proveedor: ' + (error.response?.data?.message || error.message));
  } finally {
    guardando.value = false;
  }
};

const formatMoneda = (val) => {
  return Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatearFecha = (f) => {
  if (!f) return 'N/A';
  return f.split('T')[0];
};

onMounted(() => {
  cargarDatos();
});
</script>

<style scoped>
.mantenimiento-page {
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
  background: rgba(245, 158, 11, 0.1);
  border: 1px solid rgba(245, 158, 11, 0.25);
  border-radius: 9999px;
  color: #fbbf24;
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
  background: #f59e0b;
  color: #0f172a;
  padding: 0.6rem 1.2rem;
  border-radius: 0.5rem;
  font-weight: 700;
  font-size: 0.875rem;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary:hover:not(:disabled) {
  background: #d97706;
  transform: translateY(-1px);
}

.btn-secondary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(30, 41, 59, 0.8);
  color: #cbd5e1;
  padding: 0.6rem 1.1rem;
  border-radius: 0.5rem;
  font-weight: 600;
  font-size: 0.875rem;
  border: 1px solid rgba(255, 255, 255, 0.1);
  cursor: pointer;
  transition: all 0.2s;
}

.btn-secondary:hover {
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

.tabs-nav {
  display: flex;
  gap: 0.5rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  margin-bottom: 1.5rem;
}

.tab-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.25rem;
  color: #94a3b8;
  background: none;
  border: none;
  border-bottom: 2px solid transparent;
  font-weight: 600;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.2s;
}

.tab-btn:hover {
  color: #f8fafc;
}

.tab-btn-active {
  color: #fbbf24;
  border-bottom-color: #fbbf24;
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

.mnt-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.mnt-table th {
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

.mnt-table td {
  padding: 0.875rem 1rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.03);
  color: #cbd5e1;
}

.folio-code {
  font-family: monospace;
  font-weight: 700;
  color: #f8fafc;
  display: block;
}

.type-sub-badge {
  font-size: 0.65rem;
  text-transform: uppercase;
  font-weight: 700;
  padding: 0.1rem 0.35rem;
  border-radius: 0.2rem;
  display: inline-block;
  margin-top: 0.2rem;
}

.badge-preventivo {
  background: rgba(16, 185, 129, 0.15);
  color: #34d399;
}

.badge-correctivo {
  background: rgba(245, 158, 11, 0.15);
  color: #fbbf24;
}

.badge-emergencia {
  background: rgba(244, 63, 94, 0.15);
  color: #fb7185;
}

.ord-title {
  font-weight: 600;
  color: #f8fafc;
  display: block;
}

.ord-desc {
  font-size: 0.75rem;
  color: #94a3b8;
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.activo-name {
  font-weight: 600;
  color: #e2e8f0;
  display: block;
}

.activo-loc {
  font-size: 0.75rem;
  color: #64748b;
}

.prv-name {
  font-weight: 600;
  color: #cbd5e1;
  display: block;
}

.prv-phone {
  font-size: 0.75rem;
  color: #94a3b8;
}

.cost-real {
  font-weight: 700;
  color: #f8fafc;
  display: block;
}

.cost-sub {
  font-size: 0.75rem;
  color: #64748b;
}

.status-pill {
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.25rem 0.6rem;
  border-radius: 9999px;
  text-transform: uppercase;
}

.status-pendiente {
  background: rgba(245, 158, 11, 0.12);
  color: #fbbf24;
}

.status-en_proceso {
  background: rgba(56, 189, 248, 0.12);
  color: #38bdf8;
}

.status-completada {
  background: rgba(16, 185, 129, 0.12);
  color: #34d399;
}

.status-cancelada {
  background: rgba(100, 116, 139, 0.12);
  color: #94a3b8;
}

.act-status-operativo {
  background: rgba(16, 185, 129, 0.15);
  color: #34d399;
}

.act-status-requiere_servicio {
  background: rgba(245, 158, 11, 0.15);
  color: #fbbf24;
}

.act-status-fuera_de_servicio {
  background: rgba(244, 63, 94, 0.15);
  color: #fb7185;
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
  background: #f59e0b;
  color: #0f172a;
  border-color: #f59e0b;
}

/* Cards Grid */
.cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1.25rem;
}

.card-item {
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 0.75rem;
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
}

.card-header {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  margin-bottom: 1rem;
}

.card-header-icon {
  width: 40px;
  height: 40px;
  border-radius: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.item-code {
  font-size: 0.75rem;
  font-family: monospace;
  color: #94a3b8;
  display: block;
}

.item-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: #f8fafc;
}

.card-body {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  flex: 1;
  border-top: 1px solid rgba(255, 255, 255, 0.05);
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  padding: 0.75rem 0;
  margin-bottom: 0.75rem;
}

.info-row {
  display: flex;
  justify-content: space-between;
  font-size: 0.8rem;
}

.info-label {
  color: #64748b;
}

.info-val {
  color: #e2e8f0;
}

.card-footer {
  display: flex;
  justify-content: flex-end;
}

/* Modales */
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
  max-width: 550px;
  overflow: hidden;
}

.modal-header {
  padding: 1.25rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  display: flex;
  justify-content: space-between;
  align-items: center;
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

.form-row {
  display: flex;
  gap: 1rem;
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

.form-input, .form-select, .form-textarea {
  background: rgba(30, 41, 59, 0.8);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 0.5rem;
  padding: 0.6rem;
  color: #f8fafc;
  font-size: 0.875rem;
}

.form-input:focus, .form-select:focus, .form-textarea:focus {
  outline: none;
  border-color: #f59e0b;
}

.modal-footer {
  padding: 1rem 1.25rem;
  border-top: 1px solid rgba(255, 255, 255, 0.05);
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

.empty-state, .loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 1rem;
  color: #94a3b8;
}
</style>
