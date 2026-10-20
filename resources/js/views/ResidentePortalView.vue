<template>
  <div class="residente-portal-view">
    <!-- Encabezado de Bienvenida al Hogar -->
    <div class="portal-header">
      <div class="portal-header-info">
        <span class="portal-badge">
          <Home :size="14" /> Mi Hogar en Urbanización Piloto Robledal
        </span>
        <h1 class="portal-title">
          {{ datos.inmueble ? `${datos.inmueble.inm_manzana_bloque} · ${datos.inmueble.inm_numero_villa}` : 'Mi Residencia' }}
        </h1>
        <p class="portal-subtitle">Gestión de pases QR para visitas, control de vehículos y registro de accesos</p>
      </div>

      <button @click="mostrarModalPase = true" class="btn-create-pass">
        <QrCode :size="18" />
        <span>Crear Pase QR para Visita</span>
      </button>
    </div>

    <!-- TARJETA CONSOLIDADA DE ALÍCUOTAS & ESTADO DE CUENTA ("MI HOGAR") -->
    <div class="estado-cuenta-banner" :class="datos.estado_cuenta?.al_dia ? 'banner-aldia' : 'banner-mora'">
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div class="flex items-center gap-3.5">
          <div class="ec-icon-wrap" :class="datos.estado_cuenta?.al_dia ? 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30' : 'bg-amber-500/20 text-amber-400 border-amber-500/30'">
            <DollarSign :size="24" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="text-xs font-bold uppercase tracking-wider text-slate-300">Estado de Cuenta de Alícuotas</span>
              <span 
                class="px-2 py-0.5 rounded text-[10px] font-bold"
                :class="datos.estado_cuenta?.al_dia ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-rose-500/20 text-rose-400 border border-rose-500/30'"
              >
                {{ datos.estado_cuenta?.al_dia ? '✓ AL DÍA' : `⚠️ ${datos.estado_cuenta?.cuotas_pendientes || 0} CUOTA(S) PENDIENTE(S)` }}
              </span>
            </div>
            <div class="text-xs text-[var(--text-muted)] mt-0.5">
              Predio: <strong class="text-white">{{ datos.inmueble ? `${datos.inmueble.inm_manzana_bloque} - ${datos.inmueble.inm_numero_villa}` : 'Cargando predio...' }}</strong>
              <span v-if="datos.estado_cuenta?.al_dia" class="ml-2 text-emerald-400 font-semibold">Sin valores pendientes. Acceso expedito en garita.</span>
              <span v-else class="ml-2 text-amber-300 font-semibold">Presenta saldo pendiente. Por favor reporte su transferencia.</span>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-6">
          <div class="text-right">
            <span class="text-[10px] uppercase font-bold text-[var(--text-muted)] block">Saldo Pendiente</span>
            <span class="text-xl font-black font-mono" :class="Number(datos.estado_cuenta?.deuda_total) > 0 ? 'text-rose-400' : 'text-emerald-400'">
              ${{ Number(datos.estado_cuenta?.deuda_total || 0).toFixed(2) }}
            </span>
          </div>

          <button 
            @click="mostrarModalEstadoCuenta = true" 
            class="px-3.5 py-2 rounded-lg bg-[var(--bg-surface)] hover:bg-[var(--bg-hover)] text-white text-xs font-bold border border-[var(--border-subtle)] flex items-center gap-1.5 transition-colors"
          >
            <Receipt :size="15" class="text-emerald-400" />
            <span>Ver Mis Cuotas & Recibos</span>
          </button>
        </div>
      </div>
    </div>

    <!-- BANNER DE COMUNICADOS OFICIALES RECIENTES -->
    <div v-if="comunicados.length > 0" class="comunicados-strip">
      <div class="comunicados-strip-header">
        <div class="flex items-center gap-2">
          <Megaphone :size="16" class="text-[var(--color-primary)] animate-pulse" />
          <span class="font-bold text-xs uppercase tracking-wider text-[var(--text-primary)]">
            Tablón Oficial de la Administración ({{ comunicados.length }})
          </span>
        </div>
        <span class="text-[11px] text-[var(--text-secondary)]">Publicaciones oficiales para residentes</span>
      </div>

      <div class="comunicados-scroll-grid">
        <div 
          v-for="c in comunicados" 
          :key="c.com_id" 
          class="comunicado-card"
          :class="{
            'com-urgente': c.com_prioridad === 'URGENTE' || c.com_prioridad === 'ALTA',
            'com-fijado': c.com_fijado
          }"
        >
          <div class="com-top">
            <span class="com-cat-tag" :class="catTagClass(c.com_categoria)">
              {{ c.com_categoria }}
            </span>
            <div class="flex items-center gap-1.5">
              <span v-if="c.com_fijado" class="badge-fijado" title="Anclado por la administración">
                <Pin :size="11" /> Fijado
              </span>
              <span class="text-[10px] text-[var(--text-muted)] font-mono">{{ c.com_fecha_publicacion }}</span>
            </div>
          </div>

          <h3 class="com-title">{{ c.com_titulo }}</h3>
          <p class="com-body">{{ c.com_contenido }}</p>

          <div class="com-footer">
            <span class="text-[10px] text-[var(--text-secondary)]">
              Emitido por: <strong>{{ c.autor?.usr_name || 'Directiva' }}</strong>
            </span>
          </div>
        </div>
      </div>
    </div>

    <div v-if="cargando" class="loading-state">
      <Loader2 :size="36" class="animate-spin text-[var(--color-primary)] mb-3" />
      <p>Cargando información de tu hogar...</p>
    </div>

    <div v-else class="portal-grid">
      <!-- COLUMNA IZQUIERDA: Pases QR Vigentes y Compartir -->
      <section class="portal-section">
        <div class="section-top">
          <div class="flex items-center gap-2">
            <QrCode :size="18" class="text-[var(--color-primary)]" />
            <h2 class="section-title">Pases de Visita Activos ({{ pasesActivos.length }})</h2>
          </div>
          <span class="text-xs text-[var(--text-secondary)]">Válidos para garita</span>
        </div>

        <div v-if="pasesActivos.length === 0" class="empty-state-card">
          <CheckCircle2 :size="36" class="text-[var(--text-muted)] opacity-40 mb-2" />
          <p class="text-sm">No tienes pases de visita generados actualmente.</p>
          <button @click="mostrarModalPase = true" class="btn-secondary mt-3">
            + Generar Primer Pase
          </button>
        </div>

        <div v-else class="pases-list">
          <div v-for="pase in pasesActivos" :key="pase.inv_id" class="pase-card">
            <div class="pase-card-main">
              <div>
                <span class="pase-tipo-tag" :class="pase.inv_tipo_ingreso === 'VEHICULAR' ? 'text-amber-400' : 'text-emerald-400'">
                  {{ pase.inv_tipo_ingreso }}
                </span>
                <div class="pase-guest-name">{{ pase.inv_visitante_nombre }}</div>
                <div class="pase-meta">
                  <span v-if="pase.inv_visitante_placa">🚗 Placa: <strong>{{ pase.inv_visitante_placa }}</strong> &bull; </span>
                  <span>Vence: {{ formatHora(pase.inv_valido_hasta) }}</span>
                </div>
              </div>

              <!-- Token Destacado -->
              <div class="token-container">
                <span class="token-label">CÓDIGO DE PASO</span>
                <span class="token-value">{{ pase.inv_codigo_token }}</span>
              </div>
            </div>

            <!-- Botones de Acción: Compartir WhatsApp y Copiar -->
            <div class="pase-card-footer">
              <button @click="compartirWhatsapp(pase)" class="btn-whatsapp">
                <Share2 :size="14" /> Compartir por WhatsApp
              </button>
              <button @click="copiarToken(pase.inv_codigo_token)" class="btn-copy">
                <Copy :size="14" /> Copiar Código
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- COLUMNA DERECHA: Vehículos con Tag RFID & Historial de Visitas -->
      <div class="space-y-6">
        <!-- 1. Vehículos & Tags RFID de la Familia -->
        <section class="portal-section">
          <div class="section-top">
            <div class="flex items-center gap-2">
              <Car :size="18" class="text-[var(--color-primary)]" />
              <h2 class="section-title">Vehículos de la Familia ({{ datos.vehiculos.length }})</h2>
            </div>
          </div>

          <div v-if="datos.vehiculos.length === 0" class="empty-state-card">
            <p class="text-xs text-[var(--text-muted)]">No hay vehículos registrados para tu inmueble.</p>
          </div>

          <div v-else class="vehiculos-mini-grid">
            <div v-for="v in datos.vehiculos" :key="v.veh_id" class="vehiculo-mini-card">
              <div class="flex items-center gap-3">
                <div class="veh-icon-box">
                  <Car :size="18" class="text-[var(--color-primary)]" />
                </div>
                <div>
                  <div class="font-bold text-xs font-mono text-white">🚗 {{ v.veh_placa }}</div>
                  <div class="text-[11px] text-[var(--text-secondary)]">{{ v.veh_marca }} &bull; {{ v.veh_color || 'Color estándar' }}</div>
                </div>
              </div>
              <div v-if="v.veh_tag_rfid" class="tag-rfid-chip">
                <Radio :size="12" class="text-emerald-400" />
                <span>{{ v.veh_tag_rfid }}</span>
              </div>
            </div>
          </div>
        </section>

        <!-- 1.1 Correspondencia y Paquetes en Garita -->
        <section class="portal-section">
          <div class="section-top">
            <div class="flex items-center gap-2">
              <Package :size="18" class="text-[var(--color-primary)]" />
              <h2 class="section-title">Paquetería & Correspondencia ({{ datos.paqueteria?.length || 0 }})</h2>
            </div>
            <span class="text-[10px] text-amber-400 font-bold" v-if="paquetesEnGaritaCount > 0">
              ● {{ paquetesEnGaritaCount }} por retirar
            </span>
          </div>

          <div v-if="!datos.paqueteria || datos.paqueteria.length === 0" class="empty-state-card">
            <p class="text-xs text-[var(--text-muted)]">No tienes paquetes pendientes en garita.</p>
          </div>

          <div v-else class="space-y-2.5">
            <div 
              v-for="p in datos.paqueteria" 
              :key="p.paq_id"
              class="p-2.5 rounded-lg border border-[var(--border-subtle)] bg-[var(--bg-hover)] flex items-center justify-between"
            >
              <div class="flex items-center gap-2.5">
                <div 
                  class="w-7 h-7 rounded-lg flex items-center justify-center"
                  :class="p.paq_estado === 'EN_GARITA' ? 'bg-amber-400/20 text-amber-400' : 'bg-emerald-400/20 text-emerald-400'"
                >
                  <Package :size="14" />
                </div>
                <div>
                  <div class="font-bold text-xs text-white">
                    {{ p.paq_empresa_courier }} 
                    <span v-if="p.paq_codigo_guia" class="font-mono text-[10px] text-[var(--text-muted)]">({{ p.paq_codigo_guia }})</span>
                  </div>
                  <div class="text-[10px] text-[var(--text-secondary)]">
                    {{ p.paq_descripcion || 'Paquete recibido' }}
                  </div>
                </div>
              </div>

              <div class="text-right">
                <span 
                  class="text-[10px] font-bold px-2 py-0.5 rounded"
                  :class="p.paq_estado === 'EN_GARITA' ? 'bg-amber-400/10 text-amber-400 border border-amber-400/30' : 'bg-emerald-400/10 text-emerald-400'"
                >
                  {{ p.paq_estado === 'EN_GARITA' ? 'En Garita' : 'Entregado' }}
                </span>
                <div class="text-[9px] text-[var(--text-muted)] font-mono mt-0.5">
                  {{ formatHora(p.paq_fecha_recepcion) }}
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- 2. Historial de Quién Ingresó a mi Casa -->
        <section class="portal-section">
          <div class="section-top">
            <div class="flex items-center gap-2">
              <History :size="18" class="text-[var(--color-primary)]" />
              <h2 class="section-title">Últimas Visitas Recibidas</h2>
            </div>
          </div>

          <div v-if="datos.visitas_recientes.length === 0" class="empty-state-card">
            <p class="text-xs text-[var(--text-muted)]">Aún no se registran visitas a tu domicilio.</p>
          </div>

          <div v-else class="visitas-mini-list">
            <div v-for="acc in datos.visitas_recientes" :key="acc.acc_id" class="visita-mini-item">
              <div class="flex items-center gap-3">
                <div class="status-dot-visita" :class="acc.acc_estado === 'DENTRO' ? 'bg-amber-400' : 'bg-emerald-400'"></div>
                <div>
                  <div class="text-xs font-semibold text-white">
                    {{ acc.persona ? `${acc.persona.per_nombres} ${acc.persona.per_apellidos}` : 'Visitante' }}
                  </div>
                  <div class="text-[10px] text-[var(--text-secondary)]">
                    {{ acc.acc_tipo_ingreso }} &bull; {{ acc.acc_vehiculo_placa ? `Placa: ${acc.acc_vehiculo_placa}` : 'Peatonal' }}
                  </div>
                </div>
              </div>
              <div class="text-right">
                <div class="text-xs font-mono text-[var(--text-secondary)]">{{ formatHora(acc.acc_fecha_ingreso) }}</div>
                <span class="text-[10px] font-semibold" :class="acc.acc_estado === 'DENTRO' ? 'text-amber-400' : 'text-emerald-400'">
                  {{ acc.acc_estado }}
                </span>
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>

    <!-- MODAL CREAR PASE QR -->
    <div v-if="mostrarModalPase" class="modal-overlay" @click.self="mostrarModalPase = false">
      <div class="modal-box">
        <div class="modal-header">
          <div class="flex items-center gap-2">
            <QrCode :size="20" class="text-[var(--color-primary)]" />
            <h3 class="modal-title">Generar Invitación de Visita</h3>
          </div>
          <button @click="mostrarModalPase = false" class="btn-close">&times;</button>
        </div>

        <form @submit.prevent="crearPase" class="space-y-4">
          <div class="input-block">
            <label>Nombre del Visitante *</label>
            <input v-model="formPase.visitante_nombre" placeholder="Ej. Dra. Sofía Noboa" class="form-input" required />
          </div>

          <div class="grid-2-cols">
            <div class="input-block">
              <label>Cédula (Opcional)</label>
              <input v-model="formPase.visitante_identificacion" placeholder="Ej. 1712345678" class="form-input" />
            </div>
            <div class="input-block">
              <label>Placa Vehicular</label>
              <input v-model="formPase.visitante_placa" placeholder="Ej. PDI-9012" class="form-input uppercase" />
            </div>
          </div>

          <div class="grid-2-cols">
            <div class="input-block">
              <label>Tipo de Ingreso</label>
              <select v-model="formPase.tipo_ingreso" class="form-select">
                <option value="VEHICULAR">Vehicular</option>
                <option value="PEATONAL">Peatonal</option>
              </select>
            </div>
            <div class="input-block">
              <label>Válido Hasta *</label>
              <input v-model="formPase.valido_hasta" type="datetime-local" class="form-input" required />
            </div>
          </div>

          <div class="input-block">
            <label>Observaciones o Instrucciones</label>
            <input v-model="formPase.observaciones" placeholder="Ej. Almuerzo familiar, dejar pasar a parqueo interno" class="form-input" />
          </div>

          <div class="modal-actions">
            <button type="button" @click="mostrarModalPase = false" class="btn-secondary">Cancelar</button>
            <button type="submit" :disabled="creandoPase" class="btn-primary">
              {{ creandoPase ? 'Generando Pase...' : 'Generar Pase & Compartir' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL ESTADO DE CUENTA DETALLADO & RECIBOS ("MI HOGAR") -->
    <div v-if="mostrarModalEstadoCuenta" class="modal-overlay" @click.self="mostrarModalEstadoCuenta = false">
      <div class="modal-card max-w-2xl bg-[var(--bg-card)] border border-[var(--border-subtle)] rounded-xl overflow-hidden shadow-2xl">
        <div class="modal-header p-4 bg-[var(--bg-surface)] border-b border-[var(--border-subtle)] flex items-center justify-between">
          <div class="flex items-center gap-2">
            <Receipt :size="18" class="text-emerald-400" />
            <h2 class="text-sm font-bold text-white">Historial de Cuotas y Alícuotas</h2>
          </div>
          <button @click="mostrarModalEstadoCuenta = false" class="text-slate-400 hover:text-white">✕</button>
        </div>

        <div class="p-5 space-y-4">
          <div class="grid grid-cols-3 gap-3">
            <div class="p-3 bg-[var(--bg-primary)] rounded-lg border border-[var(--border-subtle)] text-center">
              <span class="text-[10px] text-[var(--text-muted)] uppercase font-bold block">Total Pagado</span>
              <span class="text-base font-bold font-mono text-emerald-400">${{ Number(datos.estado_cuenta?.total_pagado || 0).toFixed(2) }}</span>
            </div>
            <div class="p-3 bg-[var(--bg-primary)] rounded-lg border border-[var(--border-subtle)] text-center">
              <span class="text-[10px] text-[var(--text-muted)] uppercase font-bold block">Saldo Pendiente</span>
              <span class="text-base font-bold font-mono" :class="Number(datos.estado_cuenta?.deuda_total) > 0 ? 'text-rose-400' : 'text-emerald-400'">${{ Number(datos.estado_cuenta?.deuda_total || 0).toFixed(2) }}</span>
            </div>
            <div class="p-3 bg-[var(--bg-primary)] rounded-lg border border-[var(--border-subtle)] text-center">
              <span class="text-[10px] text-[var(--text-muted)] uppercase font-bold block">Semáforo Garita</span>
              <span class="text-xs font-bold block mt-1" :class="datos.estado_cuenta?.al_dia ? 'text-emerald-400' : 'text-amber-400'">
                {{ datos.estado_cuenta?.al_dia ? '🟢 Normal / Paso Libre' : '🟡 Aviso Preventivo' }}
              </span>
            </div>
          </div>

          <div class="max-h-72 overflow-y-auto rounded-lg border border-[var(--border-subtle)]">
            <table class="w-full text-xs text-left border-collapse">
              <thead>
                <tr class="bg-[var(--bg-surface)] text-[var(--text-secondary)] uppercase text-[10px]">
                  <th class="p-2.5">Período</th>
                  <th class="p-2.5">Concepto</th>
                  <th class="p-2.5">Monto</th>
                  <th class="p-2.5">Estado</th>
                  <th class="p-2.5 text-right">Comprobante</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-[var(--border-subtle)]">
                <tr v-if="!datos.estado_cuenta?.obligaciones || datos.estado_cuenta.obligaciones.length === 0">
                  <td colspan="5" class="p-6 text-center text-[var(--text-muted)]">
                    No registra cuotas emitidas en el sistema.
                  </td>
                </tr>
                <tr v-for="obl in datos.estado_cuenta?.obligaciones" :key="obl.obl_id" class="hover:bg-[var(--bg-hover)]">
                  <td class="p-2.5 font-bold text-white font-mono">{{ obl.obl_periodo }}</td>
                  <td class="p-2.5 text-white">{{ obl.obl_concepto }}</td>
                  <td class="p-2.5 font-mono font-bold text-white">${{ Number(obl.obl_monto).toFixed(2) }}</td>
                  <td class="p-2.5">
                    <span 
                      class="px-2 py-0.5 rounded text-[10px] font-bold"
                      :class="obl.obl_estado === 'PAGADO' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-rose-500/20 text-rose-400 border border-rose-500/30'"
                    >
                      {{ obl.obl_estado }}
                    </span>
                  </td>
                  <td class="p-2.5 text-right">
                    <span v-if="obl.obl_estado === 'PAGADO'" class="text-[10px] text-emerald-400 font-mono font-bold">
                      {{ obl.obl_comprobante_ref || 'CONFIRMADO' }}
                    </span>
                    <span v-else class="text-[10px] text-amber-300 italic">
                      Pendiente
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="modal-footer flex items-center justify-between p-4 bg-[var(--bg-surface)] border-t border-[var(--border-subtle)]">
          <span class="text-xs text-[var(--text-muted)]">Para reportar un comprobante de transferencia, comuníquese con la administración.</span>
          <button @click="mostrarModalEstadoCuenta = false" class="btn-secondary">Cerrar</button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';
import { 
  Home, QrCode, Car, DollarSign, Receipt, CreditCard, Radio, Package, PackageCheck, History, CheckCircle2, Share2, 
  Copy, Loader2, Megaphone, Pin, AlertCircle, Bell, ChevronRight, Plus 
} from 'lucide-vue-next';

const props = defineProps({
  urbanizacionId: { type: String, default: 'ccce9ae7-ca93-48e7-8577-0dd546dda010' }
});

const cargando = ref(true);
const comunicados = ref([]);
const creandoPase = ref(false);
const mostrarModalPase = ref(false);
const mostrarModalEstadoCuenta = ref(false);

const datos = reactive({
  inmueble: null,
  habitantes: [],
  vehiculos: [],
  pases_qr: [],
  paqueteria: [],
  visitas_recientes: []
});

const paquetesEnGaritaCount = computed(() => {
  return (datos.paqueteria || []).filter(p => p.paq_estado === 'EN_GARITA').length;
});

const formPase = reactive({
  visitante_nombre: '',
  visitante_identificacion: '',
  visitante_placa: '',
  tipo_ingreso: 'VEHICULAR',
  inmueble_id: null,
  valido_desde: new Date().toISOString().slice(0, 16),
  valido_hasta: new Date(Date.now() + 86400000).toISOString().slice(0, 16),
  observaciones: ''
});

const pasesActivos = computed(() => {
  return datos.pases_qr.filter(p => p.inv_estado === 'ACTIVO');
});

const cargarComunicados = async () => {
  try {
    const token = localStorage.getItem('residia_token');
    const res = await axios.get('/api/comunicados', {
      headers: {
        'Authorization': `Bearer ${token}`,
        'X-Urbanizacion-Id': props.urbanizacionId
      }
    });
    if (res.data.success) {
      comunicados.value = res.data.data;
    }
  } catch (e) {
    console.error('Error cargando comunicados:', e);
  }
};

const catTagClass = (cat) => {
  switch (cat) {
    case 'MANTENIMIENTO': return 'tag-mantenimiento';
    case 'SEGURIDAD': return 'tag-seguridad';
    case 'URGENTE': return 'tag-urgente';
    case 'ASAMBLEA': return 'tag-asamblea';
    default: return 'tag-general';
  }
};

const cargarDatosHogar = async () => {
  cargando.value = true;
  try {
    const token = localStorage.getItem('residia_token');
    const res = await axios.get('/api/residente/mi-hogar', {
      headers: {
        'Authorization': `Bearer ${token}`,
        'X-Urbanizacion-Id': props.urbanizacionId
      }
    });

    if (res.data.success) {
      Object.assign(datos, res.data);
      if (datos.inmueble) {
        formPase.inmueble_id = datos.inmueble.inm_id;
      }
    }
  } catch (e) {
    console.error('Error cargando portal de residente:', e);
  } finally {
    cargando.value = false;
  }
};

const crearPase = async () => {
  creandoPase.value = true;
  try {
    const token = localStorage.getItem('residia_token');
    const res = await axios.post('/api/residente/pases-qr', formPase, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'X-Urbanizacion-Id': props.urbanizacionId
      }
    });

    if (res.data.success) {
      mostrarModalPase.value = false;
      const nuevo = res.data.invitacion;
      alert(`Pase generado con éxito! Token: ${nuevo.token}`);
      
      // Reset form
      formPase.visitante_nombre = '';
      formPase.visitante_identificacion = '';
      formPase.visitante_placa = '';
      formPase.observaciones = '';

      await cargarDatosHogar();
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Error generando el pase de visita');
  } finally {
    creandoPase.value = false;
  }
};

const compartirWhatsapp = (pase) => {
  const predio = datos.inmueble ? `${datos.inmueble.inm_manzana_bloque} - ${datos.inmueble.inm_numero_villa}` : 'mi domicilio';
  const texto = encodeURIComponent(
    `Hola ${pase.inv_visitante_nombre}, te comparto tu Pase Digital de Acceso a Urbanización Piloto Robledal.\n\n` +
    `📍 Destino: ${predio}\n` +
    `🎟️ Código de Garita: *${pase.inv_codigo_token}*\n` +
    `🕒 Válido hasta: ${formatHora(pase.inv_valido_hasta)}\n\n` +
    `Muestra este código al guardia de garita para tu ingreso inmediato.`
  );
  window.open(`https://api.whatsapp.com/send?text=${texto}`, '_blank');
};

const copiarToken = async (token) => {
  try {
    await navigator.clipboard.writeText(token);
    alert(`Código copiado al portapapeles: ${token}`);
  } catch (e) {
    alert(`Código: ${token}`);
  }
};

const formatHora = (str) => {
  if (!str) return '';
  const d = new Date(str);
  return d.toLocaleString([], { dateStyle: 'short', timeStyle: 'short' });
};

onMounted(() => {
  cargarDatosHogar();
  cargarComunicados();
});
</script>

<style scoped>
.residente-portal-view {
  padding: 16px 24px;
  width: 100%;
}
.portal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 14px;
  padding: 20px 24px;
  margin-bottom: 24px;
  flex-wrap: wrap;
  gap: 16px;
}
.portal-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: var(--color-primary-subtle);
  color: var(--color-primary);
  font-size: 11px;
  font-weight: 700;
  padding: 3px 10px;
  border-radius: 9999px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 6px;
}
.portal-title {
  font-size: 24px;
  font-weight: 800;
  color: var(--text-primary);
}
.portal-subtitle {
  font-size: 13px;
  color: var(--text-secondary);
  margin-top: 2px;
}
.btn-create-pass {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: var(--color-primary);
  color: #FFFFFF;
  padding: 12px 20px;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 700;
  border: none;
  cursor: pointer;
  box-shadow: 0 4px 14px var(--color-primary-subtle);
  transition: all 0.2s;
}
.btn-create-pass:hover {
  background: var(--color-primary-hover);
  transform: translateY(-1px);
}

.portal-grid {
  display: grid;
  grid-template-columns: 1.3fr 1fr;
  gap: 24px;
}
@media (max-width: 900px) {
  .portal-grid {
    grid-template-columns: 1fr;
  }
}

.portal-section {
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 14px;
  padding: 20px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
}
.section-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 1px solid var(--border-subtle);
}
.section-title {
  font-size: 15px;
  font-weight: 700;
  color: var(--text-primary);
}

.pases-list {
  display: flex;
  flex-direction: column;
  gap: 14px;
}
.pase-card {
  background: var(--bg-primary);
  border: 1px solid var(--border-subtle);
  border-radius: 12px;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  transition: all 0.2s;
}
.pase-card:hover {
  border-color: rgba(114, 193, 165, 0.4);
}
.pase-card-main {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}
.pase-tipo-tag {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.pase-guest-name {
  font-size: 15px;
  font-weight: 700;
  color: var(--text-primary);
  margin-top: 2px;
}
.pase-meta {
  font-size: 11px;
  color: var(--text-secondary);
  margin-top: 4px;
}
.token-container {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}
.token-label {
  font-size: 9px;
  text-transform: uppercase;
  color: var(--text-muted);
  font-weight: 700;
}
.token-value {
  font-family: monospace;
  font-size: 13px;
  font-weight: 800;
  color: var(--color-primary);
  background: var(--bg-surface);
  padding: 4px 8px;
  border-radius: 6px;
  border: 1px solid var(--border-subtle);
  margin-top: 2px;
}
.pase-card-footer {
  display: flex;
  gap: 10px;
  padding-top: 10px;
  border-top: 1px solid var(--border-subtle);
}
.btn-whatsapp {
  flex: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  background: rgba(37, 211, 102, 0.15);
  border: 1px solid rgba(37, 211, 102, 0.3);
  color: #25D366;
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
}
.btn-whatsapp:hover {
  background: #25D366;
  color: #FFFFFF;
}
.btn-copy {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  color: var(--text-secondary);
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
}
.btn-copy:hover {
  color: var(--text-primary);
  border-color: var(--text-primary);
}

.vehiculos-mini-grid {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.vehiculo-mini-card {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: var(--bg-primary);
  border: 1px solid var(--border-subtle);
  border-radius: 10px;
  padding: 10px 14px;
}
.veh-icon-box {
  width: 32px;
  height: 32px;
  background: var(--bg-surface);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.tag-rfid-chip {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  background: rgba(16, 185, 129, 0.15);
  border: 1px solid rgba(16, 185, 129, 0.3);
  color: #10B981;
  padding: 2px 8px;
  border-radius: 6px;
  font-family: monospace;
  font-size: 11px;
}

.visitas-mini-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.visita-mini-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: var(--bg-primary);
  border: 1px solid var(--border-subtle);
  border-radius: 10px;
  padding: 10px 14px;
}
.status-dot-visita {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.empty-state-card {
  padding: 30px 20px;
  text-align: center;
  color: var(--text-muted);
}

/* MODAL */
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
  margin-bottom: 18px;
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
.input-block {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-bottom: 12px;
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
  padding: 9px 12px;
  border-radius: 8px;
  font-size: 13px;
  outline: none;
  width: 100%;
}
.grid-2-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 18px;
  padding-top: 12px;
  border-top: 1px solid var(--border-subtle);
}
.btn-primary {
  background: var(--color-primary);
  color: #FFFFFF;
  padding: 9px 16px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  border: none;
  cursor: pointer;
}
.btn-secondary {
  background: var(--bg-primary);
  border: 1px solid var(--border-subtle);
  color: var(--text-primary);
  padding: 8px 14px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px 20px;
  color: var(--text-muted);
}

/* Tablón de Comunicados Oficiales */
.comunicados-strip {
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 14px;
  padding: 16px 20px;
  margin-bottom: 24px;
  box-shadow: var(--shadow-sm);
}
.comunicados-strip-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
  flex-wrap: wrap;
  gap: 8px;
}
.comunicados-scroll-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 14px;
}
.comunicado-card {
  background: var(--bg-hover);
  border: 1px solid var(--border-subtle);
  border-radius: 12px;
  padding: 14px 16px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  transition: all 0.2s ease;
}
.comunicado-card:hover {
  border-color: var(--color-primary);
  transform: translateY(-2px);
}
.comunicado-card.com-fijado {
  border-left: 4px solid var(--color-primary);
  background: var(--bg-surface-elevated, var(--bg-surface));
}
.comunicado-card.com-urgente {
  border-color: rgba(245, 158, 11, 0.4);
}
.com-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}
.com-cat-tag {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 2px 7px;
  border-radius: 5px;
}
.tag-mantenimiento {
  background: rgba(59, 130, 246, 0.15);
  color: #60a5fa;
}
.tag-seguridad {
  background: rgba(239, 68, 68, 0.15);
  color: #f87171;
}
.tag-urgente {
  background: rgba(245, 158, 11, 0.2);
  color: #fbbf24;
}
.tag-asamblea {
  background: rgba(168, 85, 247, 0.15);
  color: #c084fc;
}
.tag-general {
  background: rgba(114, 193, 165, 0.15);
  color: var(--color-primary);
}
.badge-fijado {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  background: rgba(114, 193, 165, 0.2);
  color: var(--color-primary);
  font-size: 10px;
  font-weight: 700;
  padding: 2px 6px;
  border-radius: 4px;
}
.com-title {
  font-size: 13px;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 6px;
  line-height: 1.3;
}
.com-body {
  font-size: 12px;
  color: var(--text-secondary);
  line-height: 1.45;
  margin-bottom: 12px;
  flex: 1;
}
.com-footer {
  border-top: 1px solid var(--border-subtle);
  padding-top: 8px;
}

/* BANNER ESTADO DE CUENTA EN MI HOGAR */
.estado-cuenta-banner {
  padding: 1.25rem 1.5rem;
  border-radius: 0.85rem;
  border: 1px solid var(--border-subtle);
  margin-bottom: 1.5rem;
  background: var(--bg-card);
  transition: all 0.2s;
}

.banner-aldia {
  border-left: 4px solid #10b981;
}

.banner-mora {
  border-left: 4px solid #f59e0b;
  background: linear-gradient(135deg, rgba(245, 158, 11, 0.05), var(--bg-card));
}

.ec-icon-wrap {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  border: 1px solid;
  display: flex;
  align-items: center;
  justify-content: center;
}

</style>
