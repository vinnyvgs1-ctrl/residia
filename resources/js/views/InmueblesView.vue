<template>
  <div class="inmuebles-view">
    <div class="view-header">
      <div>
        <h1 class="view-title">Inmuebles & Unidades Residenciales</h1>
        <p class="view-subtitle">Gestión de Manzanas, Villas, Departamentos, Residentes, Flota Vehicular y Obligaciones de Alícuota</p>
      </div>
      <button @click="abrirModalNuevoInmueble" class="btn-primary flex items-center gap-2">
        <Plus :size="16" />
        <span>Nuevo Inmueble</span>
      </button>
    </div>

    <!-- Filtros y Búsqueda -->
    <div class="filters-bar">
      <div class="search-wrap">
        <Search :size="16" class="search-icon" />
        <input 
          v-model="filtro" 
          placeholder="Buscar por manzana, villa o tipo..." 
          class="search-input"
        />
      </div>

      <select v-model="estadoFiltro" @change="cargarInmuebles" class="filter-select">
        <option value="">Todos los Estados</option>
        <option value="HABITADO">Habitado</option>
        <option value="DESOCUPADO">Desocupado</option>
        <option value="EN_CONSTRUCCION">En Construcción</option>
      </select>
    </div>

    <!-- Listado de Inmuebles -->
    <div v-if="cargando" class="loading-state">
      <Loader2 :size="32" class="animate-spin text-[var(--color-primary)] mb-2" />
      <p class="text-xs text-[var(--text-muted)]">Cargando catálogo de inmuebles...</p>
    </div>

    <div v-else-if="inmueblesFiltrados.length === 0" class="empty-state">
      <Building2 :size="48" class="text-[var(--text-muted)] opacity-40 mb-2" />
      <p class="text-sm font-semibold text-[var(--text-primary)]">No se encontraron inmuebles registrados.</p>
    </div>

    <div v-else class="inmuebles-grid">
      <div v-for="inm in inmueblesFiltrados" :key="inm.inm_id" class="inmueble-card">
        <div class="card-top">
          <div>
            <span class="tag-tipo">{{ inm.inm_tipo }}</span>
            <h3 class="inm-title">{{ inm.inm_manzana_bloque }} - {{ inm.inm_numero_villa }}</h3>
          </div>
          <div class="flex items-center gap-2">
            <span :class="getEstadoClass(inm.inm_estado)" class="tag-estado">
              {{ inm.inm_estado }}
            </span>
            <button @click="abrirGestionIntegral(inm)" class="btn-card-edit" title="Gestionar Ficha Integral del Inmueble">
              <Pencil :size="13" />
            </button>
          </div>
        </div>

        <!-- Residentes Vinculados Resumen -->
        <div class="residentes-section">
          <div class="section-subtitle">
            <span>Residentes ({{ inm.residentes?.length || 0 }})</span>
            <button @click="abrirGestionIntegral(inm, 'personas')" class="btn-text-action">
              + Gestionar
            </button>
          </div>

          <div v-if="!inm.residentes || inm.residentes.length === 0" class="no-residentes">
            Sin habitantes asignados
          </div>

          <div v-else class="residentes-list">
            <div v-for="r in inm.residentes.slice(0, 2)" :key="r.per_id" class="residente-pill">
              <div class="res-info">
                <span class="res-name">{{ r.per_nombres }} {{ r.per_apellidos }}</span>
                <span class="res-meta">{{ r.pivot?.res_tipo_relacion }} &bull; {{ r.per_identificacion }}</span>
              </div>
            </div>
            <div v-if="inm.residentes.length > 2" class="text-[10px] text-[var(--text-muted)] pl-1">
              + {{ inm.residentes.length - 2 }} habitante(s) más...
            </div>
          </div>
        </div>

        <!-- Botones de Acción: Flota Vehicular y Ficha Completa -->
        <div class="card-footer">
          <button @click="abrirGestionIntegral(inm, 'vehiculos')" class="btn-card-action">
            <Car :size="13" /> Vehículos & Tags RFID
          </button>
          <button @click="abrirGestionIntegral(inm, 'alicuotas')" class="btn-card-action btn-alicuota">
            <DollarSign :size="13" /> Alícuotas
          </button>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL INTEGRAL CON 3 TABS: PERSONAS, VEHÍCULOS Y ALÍCUOTAS     -->
    <!-- ============================================================== -->
    <div v-if="mostrarModalGestion" class="modal-overlay" @click.self="mostrarModalGestion = false">
      <div class="modal-box modal-integral-wide">
        <div class="modal-header">
          <div class="flex items-center gap-3">
            <div class="inm-avatar-icon">
              <Home :size="20" class="text-[var(--color-primary)]" />
            </div>
            <div>
              <h3 class="modal-title flex items-center gap-2">
                <span>{{ inmSeleccionado?.inm_manzana_bloque }} - {{ inmSeleccionado?.inm_numero_villa }}</span>
                <span :class="getEstadoClass(inmSeleccionado?.inm_estado)" class="tag-estado-sm">
                  {{ inmSeleccionado?.inm_estado }}
                </span>
              </h3>
              <p class="text-xs text-[var(--text-muted)]">Tipo: {{ inmSeleccionado?.inm_tipo }} &bull; Alícuota Base: {{ inmSeleccionado?.inm_alicuota_porcentaje || 100 }}%</p>
            </div>
          </div>
          <button @click="mostrarModalGestion = false" class="btn-close">&times;</button>
        </div>

        <!-- TABS DE NAVEGACIÓN DENTRO DEL INMUEBLE -->
        <div class="modal-tabs-header">
          <button 
            type="button" 
            class="modal-tab-btn" 
            :class="{ 'modal-tab-active': tabInmueble === 'datos' }"
            @click="tabInmueble = 'datos'"
          >
            <Settings :size="14" />
            <span>Datos Físicos</span>
          </button>
          <button 
            type="button" 
            class="modal-tab-btn" 
            :class="{ 'modal-tab-active': tabInmueble === 'personas' }"
            @click="tabInmueble = 'personas'"
          >
            <Users :size="14" />
            <span>Residentes & Habitantes ({{ inmSeleccionado?.residentes?.length || 0 }})</span>
          </button>
          <button 
            type="button" 
            class="modal-tab-btn" 
            :class="{ 'modal-tab-active': tabInmueble === 'vehiculos' }"
            @click="tabInmueble = 'vehiculos'"
          >
            <Car :size="14" />
            <span>Flota Vehicular & RFID ({{ vehiculosInmueble.length }})</span>
          </button>
          <button 
            type="button" 
            class="modal-tab-btn" 
            :class="{ 'modal-tab-active': tabInmueble === 'alicuotas' }"
            @click="tabInmueble = 'alicuotas'"
          >
            <DollarSign :size="14" />
            <span>Alícuotas & Obligaciones ({{ obligacionesList.length }})</span>
          </button>
        </div>

        <!-- TAB 1: DATOS FÍSICOS Y ESTADO DEL PREDIO -->
        <div v-show="tabInmueble === 'datos'" class="tab-panel-inner space-y-4">
          <form @submit.prevent="actualizarDatosInmueble" class="space-y-4">
            <div class="grid-2-cols">
              <div class="input-block">
                <label>Tipo de Inmueble</label>
                <select v-model="formEdicionInm.inm_tipo" class="form-select">
                  <option value="CASA">Casa Residencial</option>
                  <option value="DEPARTAMENTO">Departamento</option>
                  <option value="LOTE_VACIO">Lote Vacío</option>
                  <option value="COMERCIAL">Local Comercial</option>
                </select>
              </div>
              <div class="input-block">
                <label>Estado de Ocupación</label>
                <select v-model="formEdicionInm.inm_estado" class="form-select">
                  <option value="HABITADO">Habitado</option>
                  <option value="DESOCUPADO">Desocupado</option>
                  <option value="EN_CONSTRUCCION">En Construcción</option>
                </select>
              </div>
            </div>

            <div class="grid-2-cols">
              <div class="input-block">
                <label>Ponderación Alícuota (%)</label>
                <input v-model.number="formEdicionInm.inm_alicuota_porcentaje" type="number" min="0" max="1000" class="form-input" />
              </div>
              <div class="input-block">
                <label>Observaciones o Notas</label>
                <input v-model="formEdicionInm.inm_observaciones" placeholder="Ej. Medidor de agua #1209" class="form-input" />
              </div>
            </div>

            <div class="flex justify-end pt-2">
              <button type="submit" :disabled="guardandoDatosInm" class="btn-primary flex items-center gap-1.5">
                <Check :size="14" />
                <span>{{ guardandoDatosInm ? 'Guardando...' : 'Actualizar Inmueble' }}</span>
              </button>
            </div>
          </form>
        </div>

        <!-- TAB 2: PERSONAS & RESIDENTES VINCULADOS -->
        <div v-show="tabInmueble === 'personas'" class="tab-panel-inner space-y-4">
          <!-- Formulario para Vincular Nueva Persona -->
          <div class="sub-form-box">
            <div class="text-xs font-bold text-[var(--color-primary)] uppercase tracking-wider mb-2 flex items-center gap-2">
              <UserPlus :size="14" /> Vincular Nuevo Residente / Habitante
            </div>
            <form @submit.prevent="asignarResidente" class="space-y-3">
              <div class="grid-3-cols">
                <div class="input-block">
                  <label>Identificación / Cédula *</label>
                  <input v-model="formResidente.identificacion" placeholder="1712345678" class="form-input" required />
                </div>
                <div class="input-block">
                  <label>Nombres *</label>
                  <input v-model="formResidente.nombres" placeholder="Nombres" class="form-input" required />
                </div>
                <div class="input-block">
                  <label>Apellidos *</label>
                  <input v-model="formResidente.apellidos" placeholder="Apellidos" class="form-input" required />
                </div>
              </div>

              <div class="grid-3-cols">
                <div class="input-block">
                  <label>Tipo Relación *</label>
                  <select v-model="formResidente.tipo_relacion" class="form-select" required>
                    <option value="PROPIETARIO">Propietario / Dueño</option>
                    <option value="ARRENDATARIO">Arrendatario / Inquilino</option>
                    <option value="FAMILIAR">Familiar Conviviente</option>
                    <option value="EMPLEADO_DOMESTICO">Empleado Doméstico</option>
                  </select>
                </div>
                <div class="input-block">
                  <label>Teléfono</label>
                  <input v-model="formResidente.telefono" placeholder="0999999999" class="form-input" />
                </div>
                <div class="input-block">
                  <label>Correo Electrónico</label>
                  <input v-model="formResidente.email" type="email" placeholder="residente@mail.com" class="form-input" />
                </div>
              </div>

              <div class="flex items-center justify-between pt-1">
                <label class="flex items-center gap-2 text-xs text-[var(--text-secondary)] cursor-pointer">
                  <input type="checkbox" v-model="formResidente.es_principal" class="form-checkbox" />
                  <span>Contacto Principal para Garita y Emergencias</span>
                </label>
                <button type="submit" :disabled="guardandoResidente" class="btn-primary text-xs py-1.5 px-3">
                  {{ guardandoResidente ? 'Vinculando...' : '+ Vincular al Predio' }}
                </button>
              </div>
            </form>
          </div>

          <!-- Listado de Residentes Actuales -->
          <div class="table-wrap-compact">
            <table class="data-table-compact">
              <thead>
                <tr>
                  <th>Habitante</th>
                  <th>Cédula</th>
                  <th>Relación</th>
                  <th>Contacto</th>
                  <th>Titular</th>
                  <th class="text-right">Acción</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!inmSeleccionado?.residentes || inmSeleccionado.residentes.length === 0">
                  <td colspan="6" class="text-center py-4 text-xs text-[var(--text-muted)]">No hay habitantes asignados aún.</td>
                </tr>
                <tr v-for="r in inmSeleccionado?.residentes" :key="r.per_id">
                  <td>
                    <div class="font-bold text-xs text-[var(--text-primary)]">{{ r.per_nombres }} {{ r.per_apellidos }}</div>
                  </td>
                  <td class="font-mono text-xs">{{ r.per_identificacion }}</td>
                  <td>
                    <span class="badge-tag-xs">{{ r.pivot?.res_tipo_relacion }}</span>
                  </td>
                  <td class="text-xs text-[var(--text-secondary)]">
                    <div>{{ r.per_telefono || 'Sin teléfono' }}</div>
                    <div class="text-[10px] text-[var(--text-muted)]">{{ r.per_email || '' }}</div>
                  </td>
                  <td>
                    <span v-if="r.pivot?.res_es_principal" class="badge-principal-xs">Titular</span>
                    <span v-else class="text-[11px] text-[var(--text-muted)]">Secundario</span>
                  </td>
                  <td class="text-right">
                    <button @click="removerResidente(inmSeleccionado.inm_id, r.per_id)" class="btn-del-icon" title="Desvincular Residente">
                      <Trash2 :size="13" />
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- TAB 3: FLOTA VEHICULAR & TAGS RFID -->
        <div v-show="tabInmueble === 'vehiculos'" class="tab-panel-inner space-y-4">
          <!-- Formulario Agregar Vehículo -->
          <div class="sub-form-box">
            <div class="text-xs font-bold text-[var(--color-primary)] uppercase tracking-wider mb-2 flex items-center gap-2">
              <Car :size="14" /> Registrar Vehículo para Apertura de Garita
            </div>
            <form @submit.prevent="guardarVehiculo" class="space-y-3">
              <div class="grid-3-cols">
                <div class="input-block">
                  <label>Placa Vehicular *</label>
                  <input v-model="formVehiculo.placa" placeholder="PBX-1234" class="form-input uppercase" required />
                </div>
                <div class="input-block">
                  <label>Tipo Vehículo *</label>
                  <select v-model="formVehiculo.tipo" class="form-select" required>
                    <option value="AUTOMOVIL">Automóvil / Sedán</option>
                    <option value="SUV">SUV / Jeep</option>
                    <option value="CAMIONETA">Camioneta</option>
                    <option value="MOTOCICLETA">Motocicleta</option>
                  </select>
                </div>
                <div class="input-block">
                  <label>Marca / Modelo</label>
                  <input v-model="formVehiculo.marca" placeholder="Ej. Chevrolet Tracker" class="form-input" />
                </div>
              </div>

              <div class="grid-3-cols">
                <div class="input-block">
                  <label>Color</label>
                  <input v-model="formVehiculo.color" placeholder="Blanco" class="form-input" />
                </div>
                <div class="input-block">
                  <label>Tag RFID (Antena UHF)</label>
                  <input v-model="formVehiculo.tag_rfid" placeholder="Ej. E2801100..." class="form-input font-mono" />
                </div>
                <div class="input-block">
                  <label>Conductor Habitual</label>
                  <select v-model="formVehiculo.persona_id" class="form-select">
                    <option :value="null">-- Predio General --</option>
                    <option v-for="r in inmSeleccionado?.residentes" :key="r.per_id" :value="r.per_id">
                      {{ r.per_nombres }} {{ r.per_apellidos }}
                    </option>
                  </select>
                </div>
              </div>

              <div class="flex justify-end pt-1">
                <button type="submit" :disabled="guardandoVehiculo" class="btn-primary text-xs py-1.5 px-3">
                  {{ guardandoVehiculo ? 'Registrando...' : '+ Registrar Vehículo & Tag' }}
                </button>
              </div>
            </form>
          </div>

          <!-- Listado de Vehículos del Inmueble -->
          <div class="table-wrap-compact">
            <table class="data-table-compact">
              <thead>
                <tr>
                  <th>Placa</th>
                  <th>Tipo / Modelo</th>
                  <th>Tag RFID / LPR</th>
                  <th>Conductor</th>
                  <th class="text-right">Acción</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="vehiculosInmueble.length === 0">
                  <td colspan="5" class="text-center py-4 text-xs text-[var(--text-muted)]">No hay vehículos registrados para este inmueble.</td>
                </tr>
                <tr v-for="v in vehiculosInmueble" :key="v.veh_id">
                  <td>
                    <span class="placa-badge font-mono font-bold">{{ v.veh_placa }}</span>
                  </td>
                  <td class="text-xs">{{ v.veh_tipo }} &bull; {{ v.veh_marca || '' }} {{ v.veh_color || '' }}</td>
                  <td>
                    <span v-if="v.veh_tag_rfid" class="tag-rfid-badge">
                      <Radio :size="11" /> {{ v.veh_tag_rfid }}
                    </span>
                    <span v-else class="text-[10px] text-[var(--text-muted)] italic">Sin Tag UHF</span>
                  </td>
                  <td class="text-xs text-[var(--text-secondary)]">
                    {{ v.per_nombres ? `${v.per_nombres} ${v.per_apellidos}` : 'Predio General' }}
                  </td>
                  <td class="text-right">
                    <button @click="removerVehiculo(v.veh_id)" class="btn-del-icon" title="Retirar Vehículo">
                      <Trash2 :size="13" />
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- TAB 4: ALÍCUOTAS & OBLIGACIONES FINANCIERAS -->
        <div v-show="tabInmueble === 'alicuotas'" class="tab-panel-inner space-y-4">
          <!-- Formulario Nueva Alícuota / Obligación -->
          <div class="sub-form-box">
            <div class="text-xs font-bold text-[var(--color-primary)] uppercase tracking-wider mb-2 flex items-center gap-2">
              <DollarSign :size="14" /> Generar Cargo de Alícuota / Cuota Extraordinaria
            </div>
            <form @submit.prevent="guardarObligacion" class="space-y-3">
              <div class="grid-3-cols">
                <div class="input-block">
                  <label>Concepto *</label>
                  <input v-model="formObligacion.concepto" placeholder="Ej. Alícuota Ordinaria Noviembre" class="form-input" required />
                </div>
                <div class="input-block">
                  <label>Período (Año-Mes) *</label>
                  <input v-model="formObligacion.periodo" type="month" class="form-input" required />
                </div>
                <div class="input-block">
                  <label>Monto a Cobrar ($) *</label>
                  <input v-model.number="formObligacion.monto" type="number" step="0.01" min="0" class="form-input" required />
                </div>
              </div>

              <div class="grid-2-cols">
                <div class="input-block">
                  <label>Fecha Límite Vencimiento *</label>
                  <input v-model="formObligacion.fecha_vencimiento" type="date" class="form-input" required />
                </div>
                <div class="input-block">
                  <label>Observaciones de Cobro</label>
                  <input v-model="formObligacion.observaciones" placeholder="Ej. Cuota acordada en asamblea" class="form-input" />
                </div>
              </div>

              <div class="flex justify-end pt-1">
                <button type="submit" :disabled="guardandoObligacion" class="btn-primary text-xs py-1.5 px-3">
                  {{ guardandoObligacion ? 'Generando...' : '+ Emitir Obligación de Pago' }}
                </button>
              </div>
            </form>
          </div>

          <!-- Historial de Obligaciones y Estado de Pago -->
          <div class="table-wrap-compact">
            <table class="data-table-compact">
              <thead>
                <tr>
                  <th>Período</th>
                  <th>Concepto</th>
                  <th>Monto ($)</th>
                  <th>Vencimiento</th>
                  <th>Estado</th>
                  <th class="text-right">Cobro / Pago</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="obligacionesList.length === 0">
                  <td colspan="6" class="text-center py-4 text-xs text-[var(--text-muted)]">No existen obligaciones registradas para esta unidad.</td>
                </tr>
                <tr v-for="obl in obligacionesList" :key="obl.obl_id">
                  <td class="font-mono text-xs font-bold">{{ obl.obl_periodo }}</td>
                  <td class="text-xs">{{ obl.obl_concepto }}</td>
                  <td class="font-bold text-xs">${{ Number(obl.obl_monto).toFixed(2) }}</td>
                  <td class="text-xs text-[var(--text-muted)]">{{ obl.obl_fecha_vencimiento }}</td>
                  <td>
                    <span v-if="obl.obl_estado === 'PAGADO'" class="badge-pago-ok">
                      <CheckCircle2 :size="11" /> Pagado
                    </span>
                    <span v-else class="badge-pago-pendiente">
                      <Clock :size="11" /> Pendiente
                    </span>
                  </td>
                  <td class="text-right">
                    <button 
                      v-if="obl.obl_estado !== 'PAGADO'"
                      @click="registrarPago(obl)" 
                      class="btn-registrar-pago"
                      title="Registrar Pago de Alícuota"
                    >
                      <CreditCard :size="12" /> Registrar Pago
                    </button>
                    <span v-else class="text-[10px] text-[var(--text-muted)]">
                      {{ obl.obl_metodo_pago }} ({{ obl.obl_fecha_pago?.substring(0, 10) }})
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Footer del Modal -->
        <div class="modal-footer pt-3 border-t border-[var(--border-subtle)]">
          <button type="button" @click="mostrarModalGestion = false" class="btn-secondary text-xs">
            Cerrar Ficha
          </button>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL NUEVO INMUEBLE RÁPIDO                                    -->
    <!-- ============================================================== -->
    <div v-if="mostrarModalInmueble" class="modal-overlay" @click.self="mostrarModalInmueble = false">
      <div class="modal-box">
        <div class="modal-header">
          <h3 class="modal-title">Registrar Nuevo Inmueble</h3>
          <button @click="mostrarModalInmueble = false" class="btn-close">&times;</button>
        </div>

        <form @submit.prevent="guardarInmueble" class="space-y-4">
          <div class="grid-2-cols">
            <div class="input-block">
              <label>Manzana / Bloque *</label>
              <input v-model="formInmueble.inm_manzana_bloque" placeholder="Ej. Manzana 14" class="form-input" required />
            </div>
            <div class="input-block">
              <label>Villa / Número *</label>
              <input v-model="formInmueble.inm_numero_villa" placeholder="Ej. Villa 08" class="form-input" required />
            </div>
          </div>

          <div class="grid-2-cols">
            <div class="input-block">
              <label>Tipo de Inmueble</label>
              <select v-model="formInmueble.inm_tipo" class="form-select">
                <option value="CASA">Casa Residencial</option>
                <option value="DEPARTAMENTO">Departamento</option>
                <option value="LOTE_VACIO">Lote Vacío</option>
                <option value="COMERCIAL">Local Comercial</option>
              </select>
            </div>
            <div class="input-block">
              <label>Estado de Ocupación</label>
              <select v-model="formInmueble.inm_estado" class="form-select">
                <option value="HABITADO">Habitado</option>
                <option value="DESOCUPADO">Desocupado</option>
                <option value="EN_CONSTRUCCION">En Construcción</option>
              </select>
            </div>
          </div>

          <div class="modal-actions">
            <button type="button" @click="mostrarModalInmueble = false" class="btn-secondary">Cancelar</button>
            <button type="submit" :disabled="guardandoInmueble" class="btn-primary">
              {{ guardandoInmueble ? 'Guardando...' : 'Crear Inmueble' }}
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
  Building2, Plus, Search, Car, Users, UserPlus, Trash2, 
  Loader2, DollarSign, Settings, Check, CheckCircle2, Clock, 
  CreditCard, Radio, Home, Pencil
} from 'lucide-vue-next';

const props = defineProps({
  urbanizacionId: {
    type: String,
    required: true
  }
});

const token = localStorage.getItem('residia_token');
const getHeaders = () => ({
  headers: {
    'Authorization': `Bearer ${token}`,
    'X-Urbanizacion-Id': props.urbanizacionId
  }
});

const cargando = ref(true);
const inmuebles = ref([]);
const filtro = ref('');
const estadoFiltro = ref('');

// Modal Integral con Tabs
const mostrarModalGestion = ref(false);
const inmSeleccionado = ref(null);
const tabInmueble = ref('datos'); // 'datos', 'personas', 'vehiculos', 'alicuotas'

// Formularios
const guardandoDatosInm = ref(false);
const formEdicionInm = reactive({
  inm_tipo: 'CASA',
  inm_estado: 'HABITADO',
  inm_alicuota_porcentaje: 100,
  inm_observaciones: '',
});

const guardandoResidente = ref(false);
const formResidente = reactive({
  identificacion: '',
  nombres: '',
  apellidos: '',
  telefono: '',
  email: '',
  tipo_relacion: 'PROPIETARIO',
  es_principal: false
});

const guardandoVehiculo = ref(false);
const vehiculosInmueble = ref([]);
const formVehiculo = reactive({
  placa: '',
  tipo: 'AUTOMOVIL',
  marca: '',
  color: '',
  tag_rfid: '',
  persona_id: null
});

const guardandoObligacion = ref(false);
const obligacionesList = ref([]);
const formObligacion = reactive({
  concepto: 'Alícuota Ordinaria ' + new Date().toLocaleString('es-ES', { month: 'long' }),
  periodo: new Date().toISOString().substring(0, 7),
  monto: 50.00,
  fecha_vencimiento: new Date(new Date().getFullYear(), new Date().getMonth() + 1, 5).toISOString().substring(0, 10),
  observaciones: ''
});

// Modal Nuevo Inmueble
const mostrarModalInmueble = ref(false);
const guardandoInmueble = ref(false);
const formInmueble = reactive({
  inm_manzana_bloque: '',
  inm_numero_villa: '',
  inm_tipo: 'CASA',
  inm_estado: 'HABITADO',
});

const inmueblesFiltrados = computed(() => {
  let list = inmuebles.value;
  if (estadoFiltro.value) {
    list = list.filter(i => i.inm_estado === estadoFiltro.value);
  }
  if (filtro.value) {
    const f = filtro.value.toLowerCase();
    list = list.filter(i => 
      i.inm_manzana_bloque?.toLowerCase().includes(f) ||
      i.inm_numero_villa?.toLowerCase().includes(f) ||
      i.inm_tipo?.toLowerCase().includes(f) ||
      i.residentes?.some(r => 
        r.per_nombres?.toLowerCase().includes(f) || 
        r.per_apellidos?.toLowerCase().includes(f) || 
        r.per_identificacion?.includes(f)
      )
    );
  }
  return list;
});

const cargarInmuebles = async () => {
  cargando.value = true;
  try {
    const res = await axios.get('/api/inmuebles', getHeaders());
    if (res.data.success) {
      inmuebles.value = res.data.data;
      if (inmSeleccionado.value) {
        const found = res.data.data.find(i => i.inm_id === inmSeleccionado.value.inm_id);
        if (found) inmSeleccionado.value = found;
      }
    }
  } catch (e) {
    console.error('Error cargando inmuebles:', e);
  } finally {
    cargando.value = false;
  }
};

const abrirModalNuevoInmueble = () => {
  formInmueble.inm_manzana_bloque = '';
  formInmueble.inm_numero_villa = '';
  formInmueble.inm_tipo = 'CASA';
  formInmueble.inm_estado = 'HABITADO';
  mostrarModalInmueble.value = true;
};

const guardarInmueble = async () => {
  guardandoInmueble.value = true;
  try {
    const res = await axios.post('/api/inmuebles', formInmueble, getHeaders());
    if (res.data.success) {
      alert('Inmueble registrado exitosamente.');
      mostrarModalInmueble.value = false;
      await cargarInmuebles();
    }
  } catch (e) {
    alert(e.response?.data?.message || 'Error guardando inmueble');
  } finally {
    guardandoInmueble.value = false;
  }
};

// Abrir Modal Integral con el tab deseado
const abrirGestionIntegral = async (inm, tab = 'datos') => {
  inmSeleccionado.value = inm;
  tabInmueble.value = tab;

  // Cargar datos de edición del inmueble
  formEdicionInm.inm_tipo = inm.inm_tipo || 'CASA';
  formEdicionInm.inm_estado = inm.inm_estado || 'HABITADO';
  formEdicionInm.inm_alicuota_porcentaje = inm.inm_alicuota_porcentaje || 100;
  formEdicionInm.inm_observaciones = inm.inm_observaciones || '';

  // Limpiar formulario nuevo residente
  formResidente.identificacion = '';
  formResidente.nombres = '';
  formResidente.apellidos = '';
  formResidente.telefono = '';
  formResidente.email = '';
  formResidente.tipo_relacion = 'PROPIETARIO';
  formResidente.es_principal = false;

  // Cargar vehículos y obligaciones en paralelo
  await Promise.all([
    cargarVehiculos(inm.inm_id),
    cargarObligaciones(inm.inm_id)
  ]);

  mostrarModalGestion.value = true;
};

const actualizarDatosInmueble = async () => {
  if (!inmSeleccionado.value) return;
  guardandoDatosInm.value = true;
  try {
    const res = await axios.put(`/api/inmuebles/${inmSeleccionado.value.inm_id}`, formEdicionInm, getHeaders());
    if (res.data.success) {
      alert('Datos del inmueble actualizados.');
      await cargarInmuebles();
    }
  } catch (e) {
    alert(e.response?.data?.message || 'Error actualizando inmueble');
  } finally {
    guardandoDatosInm.value = false;
  }
};

const asignarResidente = async () => {
  guardandoResidente.value = true;
  try {
    const res = await axios.post(`/api/inmuebles/${inmSeleccionado.value.inm_id}/residentes`, formResidente, getHeaders());
    if (res.data.success) {
      alert('Residente vinculado exitosamente.');
      formResidente.identificacion = '';
      formResidente.nombres = '';
      formResidente.apellidos = '';
      formResidente.telefono = '';
      formResidente.email = '';
      await cargarInmuebles();
    }
  } catch (e) {
    alert(e.response?.data?.message || 'Error vinculando residente');
  } finally {
    guardandoResidente.value = false;
  }
};

const removerResidente = async (inmId, perId) => {
  if (!confirm('¿Desea desvincular este habitante del inmueble?')) return;
  try {
    await axios.delete(`/api/inmuebles/${inmId}/residentes/${perId}`, getHeaders());
    await cargarInmuebles();
  } catch (e) {
    alert('Error al desvincular habitante');
  }
};

const cargarVehiculos = async (inmId) => {
  try {
    const res = await axios.get(`/api/inmuebles/${inmId}/vehiculos`, getHeaders());
    if (res.data.success) {
      vehiculosInmueble.value = res.data.data;
    }
  } catch (e) {
    vehiculosInmueble.value = [];
  }
};

const guardarVehiculo = async () => {
  guardandoVehiculo.value = true;
  try {
    const res = await axios.post(`/api/inmuebles/${inmSeleccionado.value.inm_id}/vehiculos`, formVehiculo, getHeaders());
    if (res.data.success) {
      alert('Vehículo registrado exitosamente.');
      formVehiculo.placa = '';
      formVehiculo.marca = '';
      formVehiculo.color = '';
      formVehiculo.tag_rfid = '';
      await cargarVehiculos(inmSeleccionado.value.inm_id);
    }
  } catch (e) {
    alert(e.response?.data?.message || 'Error guardando vehículo');
  } finally {
    guardandoVehiculo.value = false;
  }
};

const removerVehiculo = async (vehId) => {
  if (!confirm('¿Desea retirar este vehículo del padrón?')) return;
  try {
    await axios.delete(`/api/inmuebles/${inmSeleccionado.value.inm_id}/vehiculos/${vehId}`, getHeaders());
    await cargarVehiculos(inmSeleccionado.value.inm_id);
  } catch (e) {
    alert('Error al retirar vehículo');
  }
};

const cargarObligaciones = async (inmId) => {
  try {
    const res = await axios.get(`/api/inmuebles/${inmId}/obligaciones`, getHeaders());
    if (res.data.success) {
      obligacionesList.value = res.data.data;
    }
  } catch (e) {
    obligacionesList.value = [];
  }
};

const guardarObligacion = async () => {
  guardandoObligacion.value = true;
  try {
    const res = await axios.post(`/api/inmuebles/${inmSeleccionado.value.inm_id}/obligaciones`, formObligacion, getHeaders());
    if (res.data.success) {
      alert('Obligación / Alícuota emitida exitosamente.');
      await cargarObligaciones(inmSeleccionado.value.inm_id);
    }
  } catch (e) {
    alert(e.response?.data?.message || 'Error emitiendo obligación');
  } finally {
    guardandoObligacion.value = false;
  }
};

const registrarPago = async (obl) => {
  const metodo = prompt('Ingrese método de pago (TRANSFERENCIA, EFECTIVO, TARJETA, DEPOSITO):', 'TRANSFERENCIA');
  if (!metodo) return;
  const ref = prompt('Ingrese número de comprobante o referencia bancaria:', 'REF-' + Date.now().toString().slice(-6));
  
  try {
    const res = await axios.post(`/api/inmuebles/${inmSeleccionado.value.inm_id}/obligaciones/${obl.obl_id}/pago`, {
      metodo_pago: metodo.toUpperCase(),
      comprobante_ref: ref
    }, getHeaders());
    if (res.data.success) {
      alert('Pago registrado exitosamente.');
      await cargarObligaciones(inmSeleccionado.value.inm_id);
    }
  } catch (e) {
    alert('Error al registrar pago');
  }
};

const getEstadoClass = (st) => {
  if (st === 'HABITADO') return 'tag-habitado';
  if (st === 'DESOCUPADO') return 'tag-desocupado';
  return 'tag-construccion';
};

onMounted(() => {
  cargarInmuebles();
});
</script>

<style scoped>
.inmuebles-view {
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
  font-size: 19px;
  font-weight: 700;
  color: var(--text-primary);
}
.view-subtitle {
  font-size: 13px;
  color: var(--text-secondary);
  margin-top: 4px;
}
.filters-bar {
  display: flex;
  gap: 14px;
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
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  color: var(--text-primary);
  padding: 9px 12px 9px 38px;
  border-radius: 8px;
  font-size: 13px;
  outline: none;
}
.filter-select {
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  color: var(--text-primary);
  padding: 9px 12px;
  border-radius: 8px;
  font-size: 13px;
}

.inmuebles-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 16px;
}
.inmueble-card {
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 12px;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 14px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
  transition: all 0.2s;
}
.inmueble-card:hover {
  border-color: var(--color-primary);
}
.card-top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}
.tag-tipo {
  font-size: 10px;
  text-transform: uppercase;
  color: var(--color-primary);
  font-weight: 700;
  letter-spacing: 0.05em;
}
.inm-title {
  font-size: 16px;
  font-weight: 700;
  color: var(--text-primary);
  margin-top: 2px;
}
.tag-estado {
  font-size: 11px;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 6px;
}
.tag-estado-sm {
  font-size: 10px;
  font-weight: 700;
  padding: 1px 6px;
  border-radius: 4px;
}
.tag-habitado { background: var(--color-primary-subtle); color: var(--color-primary); }
.tag-desocupado { background: rgba(245, 158, 11, 0.15); color: #F59E0B; }
.tag-construccion { background: rgba(148, 163, 184, 0.15); color: #94A3B8; }

.btn-card-edit {
  background: var(--bg-primary);
  border: 1px solid var(--border-subtle);
  color: var(--color-primary);
  width: 26px;
  height: 26px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-card-edit:hover {
  background: var(--bg-hover);
  border-color: var(--color-primary);
}

.section-subtitle {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 11px;
  font-weight: 600;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 8px;
}
.btn-text-action {
  background: transparent;
  border: none;
  color: var(--color-primary);
  font-size: 11px;
  font-weight: 600;
  cursor: pointer;
}
.residentes-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.residente-pill {
  background: var(--bg-primary);
  border: 1px solid var(--border-subtle);
  border-radius: 6px;
  padding: 6px 10px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.res-info {
  display: flex;
  flex-direction: column;
}
.res-name {
  font-size: 12px;
  font-weight: 600;
  color: var(--text-primary);
}
.res-meta {
  font-size: 10px;
  color: var(--text-muted);
}
.card-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
  padding-top: 10px;
  border-top: 1px solid var(--border-subtle);
}
.btn-card-action {
  flex: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  background: var(--bg-primary);
  border: 1px solid var(--border-subtle);
  color: var(--text-primary);
  padding: 6px 10px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-card-action:hover {
  border-color: var(--color-primary);
  color: var(--color-primary);
}
.btn-alicuota:hover {
  border-color: #38bdf8;
  color: #38bdf8;
}

/* ============================================================== */
/* ESTILOS MODAL INTEGRAL CON TABS                                */
/* ============================================================== */
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
  max-width: 520px;
  padding: 24px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
}
.modal-integral-wide {
  max-width: 860px;
}
.inm-avatar-icon {
  width: 38px;
  height: 38px;
  border-radius: 8px;
  background: rgba(16, 185, 129, 0.12);
  border: 1px solid rgba(16, 185, 129, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
}
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
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

/* MODAL TABS */
.modal-tabs-header {
  display: flex;
  gap: 6px;
  border-bottom: 1px solid var(--border-subtle);
  padding-bottom: 10px;
  margin-bottom: 16px;
  overflow-x: auto;
}
.modal-tab-btn {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 7px 14px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  color: var(--text-muted);
  background: var(--bg-primary);
  border: 1px solid var(--border-subtle);
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.2s;
}
.modal-tab-btn:hover {
  color: var(--text-primary);
  background: var(--bg-hover);
}
.modal-tab-active {
  background: var(--color-primary);
  color: #0b0f19;
  border-color: var(--color-primary);
  font-weight: 700;
}
.tab-panel-inner {
  min-height: 280px;
}

.sub-form-box {
  background: var(--bg-primary);
  border: 1px dashed var(--border-subtle);
  border-radius: 10px;
  padding: 14px;
}
.table-wrap-compact {
  border: 1px solid var(--border-subtle);
  border-radius: 8px;
  overflow: hidden;
  max-height: 240px;
  overflow-y: auto;
}
.data-table-compact {
  width: 100%;
  border-collapse: collapse;
}
.data-table-compact th {
  text-align: left;
  font-size: 10px;
  text-transform: uppercase;
  color: var(--text-muted);
  padding: 8px 10px;
  background: rgba(255, 255, 255, 0.02);
  border-bottom: 1px solid var(--border-subtle);
}
.data-table-compact td {
  padding: 9px 10px;
  border-bottom: 1px solid var(--border-subtle);
}
.badge-tag-xs {
  display: inline-block;
  font-size: 10px;
  padding: 2px 6px;
  border-radius: 4px;
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
}
.badge-principal-xs {
  display: inline-block;
  font-size: 10px;
  font-weight: 700;
  color: #34d399;
  background: rgba(16, 185, 129, 0.15);
  border: 1px solid rgba(16, 185, 129, 0.3);
  padding: 1px 6px;
  border-radius: 4px;
}
.placa-badge {
  background: var(--bg-hover);
  border: 1px solid var(--border-subtle);
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 11px;
  color: var(--color-primary);
}
.tag-rfid-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  background: rgba(16, 185, 129, 0.12);
  border: 1px solid rgba(16, 185, 129, 0.25);
  color: #34d399;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 10px;
  font-family: monospace;
}
.badge-pago-ok {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  color: #34d399;
  font-size: 11px;
  font-weight: 700;
}
.badge-pago-pendiente {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  color: #fbbf24;
  font-size: 11px;
  font-weight: 700;
}
.btn-registrar-pago {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  background: rgba(56, 189, 248, 0.15);
  border: 1px solid rgba(56, 189, 248, 0.3);
  color: #38bdf8;
  padding: 3px 8px;
  border-radius: 5px;
  font-size: 10px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-registrar-pago:hover {
  background: #38bdf8;
  color: #0b0f19;
}
.btn-del-icon {
  background: transparent;
  border: none;
  color: var(--text-muted);
  cursor: pointer;
  padding: 4px;
}
.btn-del-icon:hover { color: #ef4444; }

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
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  color: var(--text-primary);
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 13px;
  outline: none;
  width: 100%;
}
.grid-2-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.grid-3-cols { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; }
@media (max-width: 768px) {
  .grid-2-cols, .grid-3-cols { grid-template-columns: 1fr; }
}

.modal-actions, .modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 16px;
}
.btn-primary {
  background: var(--color-primary);
  color: #0b0f19;
  padding: 8px 16px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 700;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-primary:hover { opacity: 0.9; }
.btn-secondary {
  background: var(--bg-primary);
  border: 1px solid var(--border-subtle);
  color: var(--text-primary);
  padding: 8px 14px;
  border-radius: 8px;
  font-size: 12px;
  cursor: pointer;
}
.loading-state, .empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  color: var(--text-muted);
}
</style>
