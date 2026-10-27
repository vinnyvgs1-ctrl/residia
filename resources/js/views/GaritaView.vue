<template>
  <div class="garita-wrapper">
    <!-- Sub-Barra de Contexto Operativo Garita -->
    <div class="garita-status-bar">
      <div class="status-left">
        <div class="badge-urbanizacion">
          <span class="dot-online"></span>
          <span>{{ urbanizacionActiva?.urb_nombre || 'Urbanización Piloto Robledal' }}</span>
        </div>

        <!-- Selector Rápido de Puesto Multi-Garita -->
        <div class="garita-selector-wrap">
          <label class="text-[11px] text-[var(--text-muted)] flex items-center gap-1 font-semibold uppercase">
            <Building2 :size="12" class="text-[var(--color-primary)]" /> Puesto:
          </label>
          <select v-model="garitaActivaId" class="garita-select-control" @change="cambiarGaritaActiva">
            <option v-for="g in garitasDisponibles" :key="g.gar_id" :value="g.gar_id">
              {{ g.gar_nombre }} {{ g.gar_ubicacion ? `(${g.gar_ubicacion})` : '' }}
            </option>
          </select>

          <button 
            v-if="authStore.user?.es_superadmin || authStore.puedeVerVista('admin.urbanizaciones')"
            @click="mostrarModalNuevaGarita = true" 
            class="btn-icon-subtle" 
            title="Añadir Nuevo Puesto de Garita"
          >
            <Plus :size="14" />
          </button>
        </div>
        <span class="tag-live">Turno Activo</span>
      </div>

      <div class="status-right">
        <!-- Pestañas de modo operativo en Garita -->
        <div class="mode-pills">
          <button 
            :class="tabGarita === 'ingreso' ? 'pill-active' : 'pill-inactive'"
            @click="tabGarita = 'ingreso'"
          >
            <UserCheck :size="14" /> Ingreso Estándar
          </button>
          <button 
            :class="tabGarita === 'qr' ? 'pill-active' : 'pill-inactive'"
            @click="tabGarita = 'qr'"
          >
            <QrCode :size="14" /> Canje Pase QR
          </button>
          <button 
            :class="tabGarita === 'vetados' ? 'pill-active' : 'pill-inactive'"
            @click="tabGarita = 'vetados'"
          >
            <ShieldBan :size="14" /> Lista Negra ({{ vetadosList.length }})
          </button>
          <button 
            :class="tabGarita === 'paqueteria' ? 'pill-active' : 'pill-inactive'"
            @click="tabGarita = 'paqueteria'"
          >
            <Package :size="14" /> Paquetería ({{ paqueteriaPendiente.length }})
          </button>
          <button 
            :class="tabGarita === 'novedades' ? 'pill-active' : 'pill-inactive'"
            @click="tabGarita = 'novedades'"
          >
            <BookOpen :size="14" /> Novedades / Relevo ({{ novedadesList.length }})
          </button>
          <button 
            :class="tabGarita === 'iot' ? 'pill-active' : 'pill-inactive'"
            @click="tabGarita = 'iot'"
          >
            <Cpu :size="14" /> Hardware IoT & Barreras
          </button>
        </div>

        <div class="guard-pill">
          <Shield :size="15" class="text-[var(--color-primary)]" />
          <span>{{ authStore.user?.name || 'Guardia de Turno' }}</span>
        </div>
      </div>
    </div>

    <!-- BANNER TOAST FLOTANTE DE NOTIFICACIÓN EN TIEMPO REAL (SSE / NUEVOS PASES QR) -->
    <transition name="alert-anim">
      <div v-if="notificacionReciente" class="banner-notif-realtime">
        <div class="flex items-center gap-3">
          <div class="notif-pulse-icon">
            <Radio :size="20" class="text-emerald-400 animate-ping" />
          </div>
          <div class="flex-1">
            <div class="text-xs font-bold text-emerald-400 uppercase tracking-wider flex items-center gap-2">
              <span>{{ notificacionReciente.titulo }}</span>
              <span class="text-[10px] text-slate-400 font-mono">{{ notificacionReciente.hora }}</span>
            </div>
            <div class="text-xs text-white mt-0.5">
              {{ notificacionReciente.mensaje }}
            </div>
          </div>
        </div>
        <button @click="notificacionReciente = null" class="btn-cerrar-notif" title="Descartar">
          ✕
        </button>
      </div>
    </transition>

    <!-- BANNER DE ALERTA POR EXCESO DE PERMANENCIA (TIEMPO MÁXIMO VENCIDO) -->
    <transition name="alert-anim">
      <div v-if="visitasExcedidas.length > 0" class="banner-permanencia-alerta">
        <div class="flex items-center gap-3">
          <div class="alerta-permanencia-icon">
            <AlertTriangle :size="24" class="text-amber-400 animate-bounce" />
          </div>
          <div>
            <div class="text-xs font-bold text-amber-400 uppercase tracking-wider flex items-center gap-2">
              <span>⚠️ ALERTA DE PERMANENCIA: {{ visitasExcedidas.length }} VISITA(S) EXCEDIERON EL LÍMITE</span>
              <span class="badge-tiempo-limite">Límite: {{ reglasPermanencia.limite_alerta_minutos }} min</span>
            </div>
            <div class="text-xs text-white mt-0.5">
              <span v-for="(v, idx) in visitasExcedidas.slice(0, 3)" :key="v.acc_id">
                <strong>{{ v.persona?.per_nombres }} {{ v.persona?.per_apellidos }}</strong> 
                ({{ v.acc_vehiculo_placa || 'Peatonal' }}) excedido por <strong>+{{ v.exceso_minutos }} min</strong><span v-if="idx < Math.min(visitasExcedidas.length, 3) - 1"> &bull; </span>
              </span>
              <span v-if="visitasExcedidas.length > 3"> y {{ visitasExcedidas.length - 3 }} más...</span>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <button 
            @click="sonidoHabilitado = !sonidoHabilitado" 
            class="btn-audio-toggle" 
            :title="sonidoHabilitado ? 'Silenciar Alarma Sonora' : 'Activar Alarma Sonora'"
          >
            <Volume2 v-if="sonidoHabilitado" :size="16" class="text-emerald-400" />
            <VolumeX v-else :size="16" class="text-zinc-400" />
            <span class="text-xs">{{ sonidoHabilitado ? 'Alarma ON' : 'Silencio' }}</span>
          </button>
        </div>
      </div>
    </transition>

    <!-- BANNER DE ALERTA ROJA (VETO DETECTADO EN TIEMPO REAL) -->
    <transition name="alert-anim">
      <div v-if="alertaVeto" class="banner-veto-alerta">
        <div class="alerta-icon-box">
          <AlertOctagon :size="32" class="text-white animate-pulse" />
        </div>
        <div class="alerta-body">
          <div class="alerta-title">
            ALERTA DE SEGURIDAD CRÍTICA · INGRESO PROHIBIDO
          </div>
          <div class="alerta-desc">
            <strong>{{ alertaVeto.tipo === 'PERSONA' ? 'Persona Restringida:' : 'Vehículo Restringido:' }}</strong> 
            {{ alertaVeto.nombre || alertaVeto.placa || alertaVeto.identificacion }} — 
            <span>Motivo del Veto: {{ alertaVeto.motivo }}</span>
          </div>
        </div>
        <button @click="alertaVeto = null" class="btn-cerrar-alerta">
          Ignorar Aviso
        </button>
      </div>
    </transition>

    <!-- 1. MODO INGRESO ESTÁNDAR -->
    <main v-if="tabGarita === 'ingreso'" class="garita-grid">
      <!-- Columna Izquierda: Formulario Ágil de Entrada -->
      <section class="card-box form-section">
        <div class="card-header">
          <div class="flex items-center gap-2">
            <UserCheck :size="18" class="text-[var(--color-primary)]" />
            <h2 class="card-title">Registro de Ingreso</h2>
          </div>
          <span class="tag-status">Control de Barrera</span>
        </div>

                  <!-- BANNER DE VEHÍCULO RESIDENTE DETECTADO (LPR / TAG RFID) -->
          <div v-if="vehiculoResidenteDetectado" class="banner-residente-detectado" :style="vehiculoResidenteDetectado.es_moroso && reglasPermanencia.bloquear_morosos_garita ? 'border-color: #f59e0b; background: rgba(245, 158, 11, 0.12);' : ''">
            <div class="flex items-center justify-between w-full">
              <div class="flex items-center gap-3">
                <div class="tag-rfid-indicator">
                  <Radio :size="18" :class="vehiculoResidenteDetectado.es_moroso && reglasPermanencia.bloquear_morosos_garita ? 'text-amber-400' : 'text-emerald-400'" class="animate-pulse" />
                </div>
                <div>
                  <div class="text-xs font-bold uppercase tracking-wider flex items-center gap-2" :class="vehiculoResidenteDetectado.es_moroso && reglasPermanencia.bloquear_morosos_garita ? 'text-amber-400' : 'text-emerald-400'">
                    <span>{{ vehiculoResidenteDetectado.es_moroso && reglasPermanencia.bloquear_morosos_garita ? "⚠️ AVISO GARITA: VILLA CON ALÍCUOTA PENDIENTE" : "✓ VEHÍCULO DE RESIDENTE AUTORIZADO (TAG RFID / LPR)" }}</span>
                  </div>
                  <div class="text-xs text-white">
                    <strong>{{ vehiculoResidenteDetectado.veh_marca }} ({{ vehiculoResidenteDetectado.veh_placa }})</strong> &bull; 
                    Predio: {{ vehiculoResidenteDetectado.inm_manzana_bloque }} - {{ vehiculoResidenteDetectado.inm_numero_villa }}
                    <span v-if="vehiculoResidenteDetectado.es_moroso" class="text-amber-300 font-bold ml-1">
                      &bull; Saldo: ${{ Number(vehiculoResidenteDetectado.deuda_total).toFixed(2) }} ({{ vehiculoResidenteDetectado.cuotas_pendientes }} cuota/s)
                    </span>
                  </div>
                </div>
              </div>
              <span v-if="vehiculoResidenteDetectado.es_moroso && reglasPermanencia.bloquear_morosos_garita" class="bg-amber-500/20 text-amber-300 border border-amber-500/40 px-2.5 py-1 rounded text-[11px] font-bold uppercase tracking-wider">
                Recordar Pago
              </span>
              <span v-else class="badge-acceso-rapido">Paso Expedito</span>
            </div>
          </div>
        <form @submit.prevent="registrarIngreso" class="space-y-4">
          <!-- Búsqueda en Padrón core_personas -->
          <div class="input-block">
            <label>Cédula o Pasaporte *</label>
            <div class="input-search-wrap">
              <input 
                v-model="form.persona_identificacion" 
                @blur="alCambiarIdentificacion" 
                placeholder="Ej. 1712345678" 
                class="form-input" 
                :class="{ 'border-red-500': alertaVeto && alertaVeto.tipo === 'PERSONA' }"
                required 
              />
              <button type="button" @click="alCambiarIdentificacion" class="btn-search-icon">
                <Search :size="16" />
              </button>
            </div>
            <p v-if="busquedaMsg" :class="personaEncontrada ? 'text-green-400' : 'text-amber-400'" class="hint-text">
              {{ busquedaMsg }}
            </p>
          </div>

          <div class="grid-2-cols">
            <div class="input-block">
              <label>Nombres *</label>
              <input v-model="form.persona_nombres" placeholder="Nombres" class="form-input" required />
            </div>
            <div class="input-block">
              <label>Apellidos *</label>
              <input v-model="form.persona_apellidos" placeholder="Apellidos" class="form-input" required />
            </div>
          </div>

          <!-- Tipo de Ingreso: Peatonal o Vehicular -->
          <div class="input-block">
            <label>Tipo de Ingreso</label>
            <div class="tab-options">
              <button 
                type="button" 
                :class="form.tipo_ingreso === 'VEHICULAR' ? 'tab-active' : 'tab-inactive'" 
                @click="form.tipo_ingreso = 'VEHICULAR'"
              >
                <Car :size="16" /> Vehicular
              </button>
              <button 
                type="button" 
                :class="form.tipo_ingreso === 'PEATONAL' ? 'tab-active' : 'tab-inactive'" 
                @click="form.tipo_ingreso = 'PEATONAL'"
              >
                <Footprints :size="16" /> Peatonal
              </button>
            </div>
          </div>

          <!-- Campos Vehiculares -->
          <div v-if="form.tipo_ingreso === 'VEHICULAR'" class="vehiculo-box">
            <div class="grid-2-cols">
              <div class="input-block">
                <label>Placa Vehicular *</label>
                <input 
                  v-model="form.vehiculo_placa" 
                  @blur="alCambiarPlaca"
                  placeholder="Ej. PCH-1234" 
                  class="form-input uppercase" 
                  :class="{ 'border-red-500': alertaVeto && alertaVeto.tipo === 'VEHICULO' }"
                  required 
                />
              </div>
              <div class="input-block">
                <label>Total Ocupantes</label>
                <input v-model.number="form.cantidad_ocupantes" type="number" min="1" max="10" class="form-input" />
              </div>
            </div>
            <div class="grid-2-cols mt-2">
              <div class="input-block">
                <label>Marca / Modelo</label>
                <input v-model="form.vehiculo_marca" placeholder="Ej. Toyota Fortuner" class="form-input" />
              </div>
              <div class="input-block">
                <label>Color</label>
                <input v-model="form.vehiculo_color" placeholder="Ej. Plateado" class="form-input" />
              </div>
            </div>
          </div>

          <!-- Destino y Rol -->
          <div class="grid-2-cols">
            <div class="input-block">
              <label>Rol de Acceso</label>
              <select v-model="form.rol_visita" class="form-select">
                <option value="VISITANTE">Visitante</option>
                <option value="PROVEEDOR">Proveedor / Servicio</option>
                <option value="DELIVERY">Delivery</option>
                <option value="EMPLEADO">Empleado Doméstico / Obrero</option>
                <option value="RESIDENTE">Residente (Sin Tag)</option>
              </select>
            </div>

            <div class="input-block">
              <label>Inmueble / Destino</label>
              <select v-model="form.inmueble_id" class="form-select">
                <option :value="null">Área Comunal / Directiva</option>
                <option v-for="inm in inmuebles" :key="inm.inm_id" :value="inm.inm_id">
                  {{ inm.inm_manzana_bloque }} - {{ inm.inm_numero_villa }}
                </option>
              </select>
            </div>
          </div>

          <div class="input-block">
            <label>Observaciones</label>
            <input v-model="form.observaciones" placeholder="Novedades, gafete o detalle adicional" class="form-input" />
          </div>

          <button 
            type="submit" 
            :disabled="guardando || (alertaVeto && Boolean(alertaVeto))" 
            class="btn-submit"
            :class="{ 'opacity-50 cursor-not-allowed bg-red-600 hover:bg-red-600': alertaVeto }"
          >
            <CheckCircle2 :size="18" />
            <span>{{ alertaVeto ? 'BLOQUEADO POR VETO ACTIVO' : (guardando ? 'Registrando en PostgreSQL...' : 'Autorizar Ingreso & Abrir Barrera') }}</span>
          </button>
        </form>
      </section>

      <!-- Columna Derecha: Vehículos y Personas Dentro -->
      <section class="card-box table-section">
        <div class="card-header">
          <div class="flex items-center gap-2">
            <Clock :size="18" class="text-[var(--color-primary)]" />
            <h2 class="card-title">En Urbanización Actualmente</h2>
          </div>
          <span class="counter-badge">{{ accesosDentro.length }} Activos</span>
        </div>

        <div v-if="accesosDentro.length === 0" class="empty-state">
          <CheckCircle2 :size="40" class="text-[var(--color-primary)] opacity-40 mb-2" />
          <p>No hay visitas o vehículos registrados dentro en este momento.</p>
        </div>

        <div v-else class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>Persona / Destino</th>
                <th>Tipo / Placa</th>
                <th>Permanencia / Semáforo</th>
                <th>Hora Entrada</th>
                <th>Acción</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in accesosDentro" :key="item.acc_id">
                <td>
                  <div class="font-medium text-[var(--text-primary)]">
                    {{ item.persona?.per_nombres }} {{ item.persona?.per_apellidos }}
                  </div>
                  <div class="text-xs text-[var(--text-secondary)]">
                    {{ item.persona?.per_identificacion }} &bull; {{ item.acc_rol_visita }}
                  </div>
                  <div class="text-[11px] text-[var(--color-primary)]">
                    Destino: {{ item.inmueble ? `${item.inmueble.inm_manzana_bloque} - ${item.inmueble.inm_numero_villa}` : 'Área Comunal' }}
                  </div>
                </td>
                <td>
                  <span v-if="item.acc_tipo_ingreso === 'VEHICULAR'" class="tag-vehicular">
                    🚗 {{ item.acc_vehiculo_placa }}
                  </span>
                  <span v-else class="tag-peatonal">
                    🚶 Peatonal
                  </span>
                  <div v-if="item.acc_cantidad_ocupantes > 1" class="text-[10px] text-[var(--text-secondary)] mt-1">
                    👥 {{ item.acc_cantidad_ocupantes }} ocupantes
                  </div>
                </td>
                <td>
                  <div class="semaforo-cell">
                    <span 
                      class="semaforo-badge" 
                      :class="{
                        'badge-verde': item.acc_rol_visita === 'RESIDENTE' || item.minutos_dentro < 120,
                        'badge-amarillo': item.acc_rol_visita !== 'RESIDENTE' && item.minutos_dentro >= 120 && !item.tiempo_excedido_alerta,
                        'badge-rojo animate-pulse': item.tiempo_excedido_alerta
                      }"
                    >
                      ⏱️ {{ item.minutos_dentro }} min
                    </span>
                    <span v-if="item.tiempo_excedido_alerta" class="text-[10px] text-amber-400 font-bold block mt-1">
                      +{{ item.exceso_minutos }} min excedido
                    </span>
                  </div>
                </td>
                <td>
                  <div class="text-xs font-mono text-[var(--text-primary)]">
                    {{ formatHora(item.acc_fecha_ingreso) }}
                  </div>
                </td>
                <td>
                  <button @click="marcarSalida(item.acc_id)" class="btn-salida">
                    <LogOut :size="14" /> Salida
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </main>

    <!-- 2. MODO CANJE DE PASE QR (LECTOR EXPRÉS) -->
    <main v-else-if="tabGarita === 'qr'" class="garita-grid">
      <section class="card-box">
        <div class="card-header">
          <div class="flex items-center gap-2">
            <QrCode :size="18" class="text-[var(--color-primary)]" />
            <h2 class="card-title">Canje Exprés de Pase QR</h2>
          </div>
          <span class="tag-status">Escáner / Código</span>
        </div>

        <div class="qr-scan-box">
          <div class="qr-graphic-frame">
            <ScanLine :size="48" class="text-[var(--color-primary)] animate-pulse" />
            <p class="text-xs text-[var(--text-secondary)] mt-2">Apunte el lector óptico de garita o tipee el token del pase</p>
          </div>

          <form @submit.prevent="canjearPaseQr" class="mt-4 flex gap-3">
            <input 
              v-model="qrTokenInput" 
              placeholder="Ej. QR-A1B2C3D4-5678" 
              class="form-input font-mono text-base tracking-wider uppercase flex-1" 
              required 
              autofocus
            />
            <button type="submit" :disabled="canjeandoQr" class="btn-primary flex items-center gap-2">
              <CheckCircle2 :size="16" />
              <span>{{ canjeandoQr ? 'Verificando...' : 'Canjear & Abrir' }}</span>
            </button>
          </form>

          <div v-if="qrResultado" class="qr-result-card" :class="qrResultado.valido ? 'qr-success' : 'qr-error'">
            <div class="font-bold text-sm">{{ qrResultado.message }}</div>
            <div v-if="qrResultado.visitante" class="text-xs mt-1">
              Visitante: <strong>{{ qrResultado.visitante }}</strong> 
              <span v-if="qrResultado.placa">· Placa: {{ qrResultado.placa }}</span>
            </div>
          </div>
        </div>

        <!-- Generador de Nuevos Pases para Residentes / Guardia -->
        <div class="mt-6 pt-4 border-t border-[var(--border-subtle)]">
          <button @click="mostrarModalNuevoPase = true" class="btn-secondary w-full flex items-center justify-center gap-2">
            <Plus :size="16" /> Generar Nuevo Pase de Visita con QR
          </button>
        </div>
      </section>

      <!-- Pases Activos Recientes -->
      <section class="card-box">
        <div class="card-header">
          <div class="flex items-center gap-2">
            <CalendarCheck :size="18" class="text-[var(--color-primary)]" />
            <h2 class="card-title">Pases QR Vigentes</h2>
          </div>
          <span class="counter-badge">{{ pasesList.length }} Pases</span>
        </div>

        <div v-if="pasesList.length === 0" class="empty-state">
          <p>No hay pases QR registrados aún.</p>
        </div>

        <div v-else class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>Visitante / Placa</th>
                <th>Token QR</th>
                <th>Vigencia</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in pasesList" :key="p?.inv_id || Math.random()">
                <template v-if="p && p.inv_id">
                  <td>
                    <div class="font-semibold text-xs text-[var(--text-primary)]">{{ p.inv_visitante_nombre }}</div>
                    <div class="text-[11px] text-[var(--text-secondary)]">
                      {{ p.inv_visitante_placa || 'Peatonal' }} · Anfitrión: {{ p.creador_nombre || p.anfitrion || 'Residente' }}
                    </div>
                  </td>
                  <td>
                    <span class="font-mono text-xs text-[var(--color-primary)] bg-[var(--bg-primary)] px-2 py-1 rounded border border-[var(--border-subtle)]">
                      {{ p.inv_codigo_token }}
                    </span>
                  </td>
                  <td>
                    <div class="text-[11px] text-[var(--text-secondary)]">Hasta: {{ p.inv_valido_hasta }}</div>
                    <div class="text-[10px] text-[var(--text-muted)]">Usos: {{ p.inv_usos_realizados }} / {{ p.inv_usos_permitidos }}</div>
                  </td>
                  <td>
                    <span class="tag-status" :class="p.inv_estado === 'ACTIVO' ? 'bg-green-500/20 text-green-400' : 'bg-zinc-500/20 text-zinc-400'">
                      {{ p.inv_estado }}
                    </span>
                  </td>
                </template>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </main>

    <!-- 3. MODO GESTIÓN DE LISTA NEGRA & VETOS -->
    <main v-else-if="tabGarita === 'vetados'" class="card-box">
      <div class="card-header">
        <div class="flex items-center gap-2">
          <ShieldBan :size="18" class="text-red-400" />
          <h2 class="card-title">Padrón de Restricciones & Lista Negra de Garita</h2>
        </div>
        <button @click="mostrarModalNuevoVeto = true" class="btn-danger-action flex items-center gap-2">
          <Plus :size="16" /> Registrar Persona / Placa Vetada
        </button>
      </div>

      <div v-if="vetadosList.length === 0" class="empty-state">
        <p>No hay restricciones activas en la lista negra de esta urbanización.</p>
      </div>

      <div v-else class="table-container">
        <table class="data-table">
          <thead>
            <tr>
              <th>Identificación / Persona</th>
              <th>Placa Vehicular</th>
              <th>Motivo del Veto</th>
              <th>Registrado Por</th>
              <th>Acción</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="v in vetadosList" :key="v.lis_id">
              <td>
                <div class="font-semibold text-xs text-[var(--text-primary)]">
                  {{ v.per_nombres ? `${v.per_nombres} ${v.per_apellidos}` : 'No asociada a persona' }}
                </div>
                <div class="text-[11px] font-mono text-[var(--text-secondary)]">
                  {{ v.per_identificacion || 'S/N' }}
                </div>
              </td>
              <td>
                <span v-if="v.lis_placa_vehiculo" class="tag-vehicular text-red-400 border-red-500/30">
                  🚫 {{ v.lis_placa_vehiculo }}
                </span>
                <span v-else class="text-[11px] text-[var(--text-muted)]">—</span>
              </td>
              <td>
                <div class="text-xs text-red-300 font-medium">{{ v.lis_motivo }}</div>
                <div class="text-[10px] text-[var(--text-muted)]">{{ v.created_at }}</div>
              </td>
              <td>
                <span class="text-xs text-[var(--text-secondary)]">{{ v.registrado_por || 'Sistema' }}</span>
              </td>
              <td>
                <button @click="levantarVeto(v.lis_id)" class="btn-salida text-zinc-300 hover:text-white" title="Levantar Veto">
                  <Trash2 :size="14" /> Levantar
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </main>

    <!-- 4. MODO PAQUETERÍA Y CORRESPONDENCIA -->
    <main v-else-if="tabGarita === 'paqueteria'" class="garita-grid">
      <!-- Formulario Recepcionar Paquete -->
      <section class="card-box">
        <div class="card-header">
          <div class="flex items-center gap-2">
            <Package :size="18" class="text-[var(--color-primary)]" />
            <h2 class="card-title">Recepción de Paquetería</h2>
          </div>
          <span class="tag-status">Garita / Mensajería</span>
        </div>

        <form @submit.prevent="guardarPaquete" class="space-y-4">
          <div class="input-block">
            <label>Inmueble Destino *</label>
            <select v-model="formPaquete.inmueble_id" class="form-select" required>
              <option :value="null" disabled>Seleccione Manzana / Villa</option>
              <option v-for="inm in inmuebles" :key="inm.inm_id" :value="inm.inm_id">
                {{ inm.inm_manzana_bloque }} - {{ inm.inm_numero_villa }}
              </option>
            </select>
          </div>

          <div class="grid-2-cols">
            <div class="input-block">
              <label>Empresa Courier *</label>
              <input v-model="formPaquete.empresa_courier" placeholder="Ej. Servientrega, DHL, Amazon" class="form-input" required />
            </div>
            <div class="input-block">
              <label>N° Guía / Tracking</label>
              <input v-model="formPaquete.codigo_guia" placeholder="Ej. SRV-908123" class="form-input" />
            </div>
          </div>

          <div class="input-block">
            <label>Destinatario (Residente)</label>
            <input v-model="formPaquete.destinatario_nombre" placeholder="Ej. Fernando García" class="form-input" />
          </div>

          <div class="input-block">
            <label>Descripción del Paquete</label>
            <input v-model="formPaquete.descripcion" placeholder="Ej. Caja mediana sellada, sobre manila" class="form-input" />
          </div>

          <button type="submit" :disabled="guardandoPaquete" class="btn-primary w-full">
            {{ guardandoPaquete ? 'Registrando...' : 'Ingresar Paquete a Garita' }}
          </button>
        </form>
      </section>

      <!-- Listado de Paquetes en Garita y Entregados -->
      <section class="card-box">
        <div class="card-header">
          <div class="flex items-center gap-2">
            <PackageCheck :size="18" class="text-[var(--color-primary)]" />
            <h2 class="card-title">Correspondencia en Custodia ({{ paqueteriaPendiente.length }})</h2>
          </div>
          <span class="counter-badge">{{ paqueteriaList.length }} Total</span>
        </div>

        <div v-if="paqueteriaList.length === 0" class="empty-state">
          <p>No hay paquetes registrados en garita.</p>
        </div>

        <div v-else class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>Destino / Destinatario</th>
                <th>Courier / Guía</th>
                <th>Recepción</th>
                <th>Estado</th>
                <th>Acción</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in paqueteriaList" :key="p.paq_id">
                <td>
                  <div class="font-bold text-xs text-white">
                    {{ p.inmueble ? `${p.inmueble.inm_manzana_bloque} - ${p.inmueble.inm_numero_villa}` : 'Inmueble' }}
                  </div>
                  <div class="text-[11px] text-[var(--text-secondary)]">{{ p.paq_destinatario_nombre || 'Familiar' }}</div>
                </td>
                <td>
                  <div class="text-xs font-semibold text-[var(--color-primary)]">{{ p.paq_empresa_courier }}</div>
                  <div class="text-[10px] text-[var(--text-muted)] font-mono">{{ p.paq_codigo_guia || 'Sin Guía' }}</div>
                </td>
                <td>
                  <div class="text-xs font-mono">{{ formatHora(p.paq_fecha_recepcion) }}</div>
                  <div class="text-[10px] text-[var(--text-muted)]">Por: {{ p.guardia_recibe?.usr_name || 'Guardia' }}</div>
                </td>
                <td>
                  <span 
                    class="semaforo-badge"
                    :class="p.paq_estado === 'EN_GARITA' ? 'badge-amarillo animate-pulse' : 'badge-verde'"
                  >
                    {{ p.paq_estado === 'EN_GARITA' ? 'En Custodia' : 'Entregado' }}
                  </span>
                </td>
                <td>
                  <button 
                    v-if="p.paq_estado === 'EN_GARITA'"
                    @click="abrirEntregaPaquete(p)"
                    class="btn-primary text-xs py-1 px-2.5"
                  >
                    Entregar
                  </button>
                  <span v-else class="text-[10px] text-[var(--text-muted)]">
                    Retiró: {{ p.paq_retirado_por }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </main>

    <!-- 5. MODO CUADERNO DIGITAL DE NOVEDADES & RELEVO -->
    <main v-else-if="tabGarita === 'novedades'" class="garita-grid">
      <!-- Formulario Asentar Novedad -->
      <section class="card-box">
        <div class="card-header">
          <div class="flex items-center gap-2">
            <BookOpen :size="18" class="text-[var(--color-primary)]" />
            <h2 class="card-title">Asentar Novedad en el Turno</h2>
          </div>
          <span class="tag-status">Libro Digital Oficial</span>
        </div>

        <form @submit.prevent="guardarNovedad" class="space-y-4">
          <div class="grid-2-cols">
            <div class="input-block">
              <label>Tipo de Novedad *</label>
              <select v-model="formNovedad.nov_tipo" class="form-select" required>
                <option value="RONDA">Ronda de Vigilancia</option>
                <option value="INCIDENCIA">Incidencia / Suceso</option>
                <option value="RELEVO">Relevo de Puesto</option>
                <option value="MANTENIMIENTO">Fallo de Equipo / Luminaria</option>
                <option value="ALERTA">Alerta de Seguridad</option>
              </select>
            </div>

            <div class="input-block">
              <label>Nivel de Prioridad *</label>
              <select v-model="formNovedad.nov_prioridad" class="form-select" required>
                <option value="BAJA">Baja</option>
                <option value="NORMAL">Normal</option>
                <option value="ALTA">Alta</option>
                <option value="CRITICA">Crítica</option>
              </select>
            </div>
          </div>

          <div class="input-block">
            <label>Título Breve de la Novedad *</label>
            <input v-model="formNovedad.nov_titulo" placeholder="Ej. Ronda perimetral sin novedad / Puerta peatonal trabada" class="form-input" required />
          </div>

          <div class="input-block">
            <label>Detalle Exhaustivo de la Novedad *</label>
            <textarea v-model="formNovedad.nov_detalle" placeholder="Describa acontecimientos, personas involucradas, estado de equipos y acciones tomadas" class="form-input h-24" required></textarea>
          </div>

          <button type="submit" :disabled="guardandoNovedad" class="btn-primary w-full">
            {{ guardandoNovedad ? 'Asentando en Bitácora...' : 'Firmar y Asentar Novedad' }}
          </button>
        </form>
      </section>

      <!-- Historial de Novedades del Turno -->
      <section class="card-box">
        <div class="card-header">
          <div class="flex items-center gap-2">
            <FileText :size="18" class="text-[var(--color-primary)]" />
            <h2 class="card-title">Cuaderno de Bitácora Digital</h2>
          </div>
          <span class="counter-badge">{{ novedadesList.length }} Entradas</span>
        </div>

        <div v-if="novedadesList.length === 0" class="empty-state">
          <p>No hay novedades asentadas en el turno actual.</p>
        </div>

        <div v-else class="space-y-3">
          <div 
            v-for="nov in novedadesList" 
            :key="nov.nov_id" 
            class="p-3.5 rounded-xl border border-[var(--border-subtle)] bg-[var(--bg-hover)]"
            :class="{ 'border-l-4 border-l-red-500': nov.nov_prioridad === 'CRITICA', 'border-l-4 border-l-amber-500': nov.nov_prioridad === 'ALTA' }"
          >
            <div class="flex items-center justify-between mb-1.5">
              <div class="flex items-center gap-2">
                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-[var(--bg-surface)] text-[var(--color-primary)] uppercase">
                  {{ nov.nov_tipo }}
                </span>
                <span class="text-xs font-bold text-white">{{ nov.nov_titulo }}</span>
              </div>
              <span class="text-[11px] text-[var(--text-muted)] font-mono">{{ formatHora(nov.nov_fecha_hora) }}</span>
            </div>

            <p class="text-xs text-[var(--text-secondary)] leading-relaxed mb-2">{{ nov.nov_detalle }}</p>

            <div class="flex items-center justify-between text-[10px] text-[var(--text-muted)] pt-2 border-t border-[var(--border-subtle)]">
              <span>Oficial de Guardia: <strong>{{ nov.guardia?.usr_name || 'Guardia en turno' }}</strong></span>
              <span v-if="nov.nov_resuelta" class="text-emerald-400 font-semibold">✓ Resuelta</span>
              <button 
                v-else-if="nov.nov_tipo === 'INCIDENCIA' || nov.nov_tipo === 'MANTENIMIENTO'"
                @click="resolverNovedad(nov.nov_id)"
                class="text-amber-400 hover:text-amber-300 font-semibold underline"
              >
                Marcar Resuelta
              </button>
            </div>
          </div>
        </div>
      </section>
    </main>

    <!-- MODAL FIRMA DE ENTREGA DE PAQUETE -->
    <div v-if="mostrarModalEntrega" class="modal-overlay" @click.self="mostrarModalEntrega = false">
      <div class="modal-box">
        <div class="modal-header">
          <h3 class="modal-title flex items-center gap-2">
            <PackageCheck :size="18" class="text-emerald-400" /> Confirmar Entrega de Paquete
          </h3>
          <button @click="mostrarModalEntrega = false" class="btn-close">&times;</button>
        </div>

        <form @submit.prevent="confirmarEntregaPaquete" class="space-y-4">
          <div class="text-xs text-[var(--text-secondary)] bg-[var(--bg-hover)] p-3 rounded-lg border border-[var(--border-subtle)]">
            <div>Courier: <strong>{{ paqueteSeleccionado?.paq_empresa_courier }}</strong> (Guía: {{ paqueteSeleccionado?.paq_codigo_guia || 'S/N' }})</div>
            <div>Inmueble: <strong>{{ paqueteSeleccionado?.inmueble?.inm_manzana_bloque }} - {{ paqueteSeleccionado?.inmueble?.inm_numero_villa }}</strong></div>
          </div>

          <div class="input-block">
            <label>Nombre de quien retira en garita *</label>
            <input v-model="formEntrega.retirado_por" placeholder="Ej. Fernando García / Doméstica" class="form-input" required />
          </div>

          <div class="input-block">
            <label>Observaciones de entrega</label>
            <input v-model="formEntrega.observaciones" placeholder="Ej. Retirado en persona, conforme con el estado del paquete" class="form-input" />
          </div>

          <div class="flex justify-end gap-3 mt-4">
            <button type="button" @click="mostrarModalEntrega = false" class="btn-secondary">Cancelar</button>
            <button type="submit" :disabled="guardandoEntrega" class="btn-primary">
              {{ guardandoEntrega ? 'Confirmando...' : 'Confirmar Retiro & Cerrar' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL GESTIÓN / NUEVA GARITA -->
    <div v-if="mostrarModalNuevaGarita" class="modal-overlay" @click.self="mostrarModalNuevaGarita = false">
      <div class="modal-box">
        <div class="modal-header">
          <h3 class="modal-title flex items-center gap-2">
            <Building2 :size="18" class="text-[var(--color-primary)]" /> Registrar Nuevo Puesto de Garita
          </h3>
          <button @click="mostrarModalNuevaGarita = false" class="btn-close">&times;</button>
        </div>

        <form @submit.prevent="guardarNuevaGarita" class="space-y-4">
          <div class="input-block">
            <label>Nombre del Puesto / Garita *</label>
            <input v-model="formGarita.gar_nombre" placeholder="Ej. Garita Sur (Proveedores / Mantenimiento)" class="form-input" required />
          </div>

          <div class="input-block">
            <label>Ubicación o Vía de Acceso</label>
            <input v-model="formGarita.gar_ubicacion" placeholder="Ej. Puerta posterior Calle Manabí" class="form-input" />
          </div>

          <div class="grid-2-cols">
            <label class="flex items-center gap-2 text-xs text-[var(--text-primary)] cursor-pointer">
              <input type="checkbox" v-model="formGarita.gar_permite_vehicular" class="form-checkbox" />
              <span>Permite Acceso Vehicular</span>
            </label>

            <label class="flex items-center gap-2 text-xs text-[var(--text-primary)] cursor-pointer">
              <input type="checkbox" v-model="formGarita.gar_permite_peatonal" class="form-checkbox" />
              <span>Permite Acceso Peatonal</span>
            </label>
          </div>

          <div class="flex justify-end gap-3 mt-4">
            <button type="button" @click="mostrarModalNuevaGarita = false" class="btn-secondary">Cancelar</button>
            <button type="submit" :disabled="guardandoGarita" class="btn-primary">
              {{ guardandoGarita ? 'Creando Puesto...' : 'Registrar Garita' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL NUEVO VETO -->
    <div v-if="mostrarModalNuevoVeto" class="modal-overlay" @click.self="mostrarModalNuevoVeto = false">
      <div class="modal-box">
        <div class="modal-header">
          <h3 class="modal-title flex items-center gap-2">
            <ShieldBan :size="18" class="text-red-400" /> Registrar en Lista Negra
          </h3>
          <button @click="mostrarModalNuevoVeto = false" class="btn-close">&times;</button>
        </div>

        <form @submit.prevent="guardarVeto" class="space-y-4">
          <div class="grid-2-cols">
            <div class="input-block">
              <label>Cédula o Pasaporte</label>
              <input v-model="formVeto.identificacion" placeholder="Ej. 1798765432" class="form-input" />
            </div>
            <div class="input-block">
              <label>Placa Vehicular</label>
              <input v-model="formVeto.placa_vehiculo" placeholder="Ej. PBX-9999" class="form-input uppercase" />
            </div>
          </div>

          <div class="grid-2-cols">
            <div class="input-block">
              <label>Nombres</label>
              <input v-model="formVeto.nombres" placeholder="Nombres del vetado" class="form-input" />
            </div>
            <div class="input-block">
              <label>Apellidos</label>
              <input v-model="formVeto.apellidos" placeholder="Apellidos" class="form-input" />
            </div>
          </div>

          <div class="input-block">
            <label>Motivo Obligatorio del Veto *</label>
            <textarea 
              v-model="formVeto.motivo" 
              placeholder="Indique con detalle la razón (ej. Agresión a guardias, antecedentes, orden de restricción)" 
              class="form-input h-20" 
              required
            ></textarea>
          </div>

          <div class="flex justify-end gap-3 mt-4">
            <button type="button" @click="mostrarModalNuevoVeto = false" class="btn-secondary">Cancelar</button>
            <button type="submit" :disabled="guardandoVeto" class="btn-danger-action">
              {{ guardandoVeto ? 'Guardando...' : 'Aplicar Restricción' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL NUEVO PASE QR -->
    <div v-if="mostrarModalNuevoPase" class="modal-overlay" @click.self="mostrarModalNuevoPase = false">
      <div class="modal-box">
        <div class="modal-header">
          <h3 class="modal-title flex items-center gap-2">
            <QrCode :size="18" class="text-[var(--color-primary)]" /> Generar Pase QR de Visita
          </h3>
          <button @click="mostrarModalNuevoPase = false" class="btn-close">&times;</button>
        </div>

        <form @submit.prevent="guardarPaseQr" class="space-y-4">
          <div class="input-block">
            <label>Nombre Completo del Visitante *</label>
            <input v-model="formPase.visitante_nombre" placeholder="Ej. Carlos Mendoza" class="form-input" required />
          </div>

          <div class="grid-2-cols">
            <div class="input-block">
              <label>Identificación</label>
              <input v-model="formPase.visitante_identificacion" placeholder="Ej. 1718293041" class="form-input" />
            </div>
            <div class="input-block">
              <label>Placa Vehículo</label>
              <input v-model="formPase.visitante_placa" placeholder="Ej. PDI-4567" class="form-input uppercase" />
            </div>
          </div>

          <div class="grid-2-cols">
            <div class="input-block">
              <label>Válido Desde *</label>
              <input v-model="formPase.valido_desde" type="datetime-local" class="form-input" required />
            </div>
            <div class="input-block">
              <label>Válido Hasta *</label>
              <input v-model="formPase.valido_hasta" type="datetime-local" class="form-input" required />
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
              <label>Usos Permitidos</label>
              <input v-model.number="formPase.usos_permitidos" type="number" min="1" max="10" class="form-input" />
            </div>
          </div>

          <div class="flex justify-end gap-3 mt-4">
            <button type="button" @click="mostrarModalNuevoPase = false" class="btn-secondary">Cancelar</button>
            <button type="submit" :disabled="guardandoPase" class="btn-primary">
              {{ guardandoPase ? 'Generando...' : 'Crear Pase & Generar Token' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { useAuthStore } from '../stores/auth';
import { useThemeStore } from '../stores/theme';
import { 
  Shield, CheckCircle2, Search, Car, Footprints, Clock, LogOut, UserCheck,
  AlertOctagon, QrCode, ShieldBan, ScanLine, Plus, CalendarCheck, Trash2, Volume2, VolumeX, AlertTriangle, Radio
} from 'lucide-vue-next';

const props = defineProps({
  urbanizacionActiva: { type: Object, default: () => ({ urb_id: 'ccce9ae7-ca93-48e7-8577-0dd546dda010', urb_nombre: 'Urbanización Piloto Robledal' }) }
});

const authStore = useAuthStore();
const themeStore = useThemeStore();

const token = localStorage.getItem('residia_token');
const headersConfig = {
  headers: {
    'Authorization': `Bearer ${token}`,
    'X-Urbanizacion-Id': 'ccce9ae7-ca93-48e7-8577-0dd546dda010'
  }
};

const tabGarita = ref('ingreso');
const paqueteriaList = ref([]);
const novedadesList = ref([]);

const paqueteriaPendiente = computed(() => {
  return paqueteriaList.value.filter(p => p.paq_estado === 'EN_GARITA');
});

const guardandoPaquete = ref(false);
const formPaquete = reactive({
  inmueble_id: null,
  empresa_courier: '',
  codigo_guia: '',
  destinatario_nombre: '',
  descripcion: ''
});

const mostrarModalEntrega = ref(false);
const paqueteSeleccionado = ref(null);
const guardandoEntrega = ref(false);
const formEntrega = reactive({
  retirado_por: '',
  observaciones: ''
});

const guardandoNovedad = ref(false);
const formNovedad = reactive({
  nov_tipo: 'RONDA',
  nov_prioridad: 'NORMAL',
  nov_titulo: '',
  nov_detalle: '',
  nov_garita_id: null
});
const garitasDisponibles = ref([]);
const garitaActivaId = ref(1);
const mostrarModalNuevaGarita = ref(false);
const guardandoGarita = ref(false);
const formGarita = reactive({
  gar_nombre: '',
  gar_ubicacion: '',
  gar_permite_vehicular: true,
  gar_permite_peatonal: true,
  gar_activo: true
});
const accesosDentro = ref([]);
const inmuebles = ref([]);
const vetadosList = ref([]);
const pasesList = ref([]);
const alertaVeto = ref(null);
const sonidoHabilitado = ref(true);
const reglasPermanencia = reactive({
  tiempo_maximo_visita_minutos: 240,
  tolerancia_salida_minutos: 15,
  limite_alerta_minutos: 255
});

const visitasExcedidas = computed(() => {
  return accesosDentro.value.filter(a => a.tiempo_excedido_alerta);
});

// Generador de tono sintetizado de advertencia (Web Audio API nativo)
let audioCtx = null;

// ==========================================
// NOTIFICACIONES EN TIEMPO REAL (SSE / STREAM)
// ==========================================
const notificacionReciente = ref(null);
let sseConnection = null;
let notifTimeout = null;

const emitirAlertaNotificacionPase = () => {
  if (!sonidoHabilitado.value) return;
  try {
    const AudioContext = window.AudioContext || window.webkitAudioContext;
    if (!AudioContext) return;
    if (!audioCtx) audioCtx = new AudioContext();
    if (audioCtx.state === 'suspended') audioCtx.resume();

    const osc = audioCtx.createOscillator();
    const gain = audioCtx.createGain();
    osc.type = 'sine';
    // Chime melodioso ascendente de 2 notas (G5 -> C6)
    osc.frequency.setValueAtTime(783.99, audioCtx.currentTime); 
    osc.frequency.setValueAtTime(1046.50, audioCtx.currentTime + 0.15); 

    gain.gain.setValueAtTime(0.15, audioCtx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.45);

    osc.connect(gain);
    gain.connect(audioCtx.destination);

    osc.start();
    osc.stop(audioCtx.currentTime + 0.45);
  } catch (e) {}
};

const iniciarStreamSSE = () => {
  if (typeof EventSource === 'undefined') return;
  const urbId = urbanizacionIdActiva.value || 'ccce9ae7-ca93-48e7-8577-0dd546dda010';
  const url = `/api/notificaciones/stream?urbanizacion_id=${urbId}`;

  try {
    if (sseConnection) sseConnection.close();
    sseConnection = new EventSource(url);

    sseConnection.onmessage = (event) => {
      try {
        const data = JSON.parse(event.data);
        if (data.tipo === 'NUEVO_PASE_QR') {
          notificacionReciente.value = {
            titulo: data.titulo,
            mensaje: data.mensaje,
            hora: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' })
          };
          emitirAlertaNotificacionPase();
          cargarPases();
  iniciarStreamSSE();
  cargarDispositivosIot(); // Refrescar lista de pases activos al instante

          if (notifTimeout) clearTimeout(notifTimeout);
          notifTimeout = setTimeout(() => {
            notificacionReciente.value = null;
          }, 8000);
        } else if (data.tipo === 'EXCESO_PERMANENCIA') {
          if (sonidoHabilitado.value) emitirAlertaSonora();
          cargarDentro();
        }
      } catch (err) {}
    };

    sseConnection.onerror = () => {
      // Reintento limpio gestionado por el navegador
    };
  } catch (err) {
    console.warn('SSE no soportado o conexión fallida:', err);
  }
};

const emitirAlertaSonora = () => {
  if (!sonidoHabilitado.value) return;
  try {
    const AudioContext = window.AudioContext || window.webkitAudioContext;
    if (!AudioContext) return;
    if (!audioCtx) audioCtx = new AudioContext();
    if (audioCtx.state === 'suspended') audioCtx.resume();

    const osc = audioCtx.createOscillator();
    const gain = audioCtx.createGain();
    osc.type = 'sawtooth';
    osc.frequency.setValueAtTime(587.33, audioCtx.currentTime); // Tono D5
    osc.frequency.setValueAtTime(880, audioCtx.currentTime + 0.15); // Tono A5
    
    gain.gain.setValueAtTime(0.12, audioCtx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.4);
    
    osc.connect(gain);
    gain.connect(audioCtx.destination);
    
    osc.start();
    osc.stop(audioCtx.currentTime + 0.4);
  } catch (err) {
    console.warn('Audio no inicializado o restringido por política del navegador:', err);
  }
};

const guardando = ref(false);
const busquedaMsg = ref('');
const personaEncontrada = ref(false);

const qrTokenInput = ref('');
const canjeandoQr = ref(false);
const qrResultado = ref(null);

const mostrarModalNuevoVeto = ref(false);
const guardandoVeto = ref(false);
const formVeto = reactive({
  identificacion: '',
  placa_vehiculo: '',
  nombres: '',
  apellidos: '',
  motivo: ''
});

const mostrarModalNuevoPase = ref(false);
const guardandoPase = ref(false);
const formPase = reactive({
  visitante_nombre: '',
  visitante_identificacion: '',
  visitante_placa: '',
  tipo_ingreso: 'VEHICULAR',
  usos_permitidos: 1,
  valido_desde: new Date().toISOString().slice(0, 16),
  valido_hasta: new Date(Date.now() + 86400000).toISOString().slice(0, 16),
  inmueble_id: null
});

const form = reactive({
  persona_identificacion: '',
  persona_nombres: '',
  persona_apellidos: '',
  tipo_ingreso: 'VEHICULAR',
  vehiculo_placa: '',
  vehiculo_marca: '',
  vehiculo_color: '',
  cantidad_ocupantes: 1,
  rol_visita: 'VISITANTE',
  inmueble_id: null,
  observaciones: ''
});

// Validación reactiva de Veto al ingresar Cédula
const alCambiarIdentificacion = async () => {
  await buscarEnPadron();
  await verificarVetoEnTiempoReal();
};

// Validación reactiva de Veto al ingresar Placa
const vehiculoResidenteDetectado = ref(null);

const alCambiarPlaca = async () => {
  await verificarVetoEnTiempoReal();
  await buscarVehiculoResidente();
};

const buscarVehiculoResidente = async () => {
  if (!form.vehiculo_placa || form.vehiculo_placa.length < 5) {
    vehiculoResidenteDetectado.value = null;
    return;
  }
  try {
    const res = await axios.get(`/api/accesos/buscar-vehiculo-residente?placa=${form.vehiculo_placa}`, headersConfig);
    if (res.data.success && res.data.encontrado) {
      const v = res.data.vehiculo;
      vehiculoResidenteDetectado.value = v;
      form.rol_visita = 'RESIDENTE';
      form.inmueble_id = v.inm_id;
      if (v.per_identificacion) {
        form.persona_identificacion = v.per_identificacion;
        form.persona_nombres = v.per_nombres;
        form.persona_apellidos = v.per_apellidos;
        busquedaMsg.value = `✓ Residente asignado a ${v.inm_manzana_bloque} - ${v.inm_numero_villa}`;
        personaEncontrada.value = true;
      }
      if (v.veh_marca) form.vehiculo_marca = v.veh_marca;
      if (v.veh_color) form.vehiculo_color = v.veh_color;
    } else {
      vehiculoResidenteDetectado.value = null;
    }
  } catch (e) {
    vehiculoResidenteDetectado.value = null;
  }
};

const verificarVetoEnTiempoReal = async () => {
  if (!form.persona_identificacion && !form.vehiculo_placa) return;
  try {
    const res = await axios.get(
      `/api/lista-negra/verificar-veto?identificacion=${form.persona_identificacion}&placa=${form.vehiculo_placa}`,
      headersConfig
    );
    if (res.data.success && res.data.vetado) {
      alertaVeto.value = res.data.alerta;
    } else {
      alertaVeto.value = null;
    }
  } catch (e) {
    console.error('Error verificando veto:', e);
  }
};

const buscarEnPadron = async () => {
  if (!form.persona_identificacion || form.persona_identificacion.length < 5) return;
  try {
    const res = await axios.get(`/api/personas/buscar?identificacion=${form.persona_identificacion}`, headersConfig);
    if (res.data.success && res.data.encontrado && res.data.persona) {
      const p = res.data.persona;
      form.persona_nombres = p.per_nombres;
      form.persona_apellidos = p.per_apellidos;
      personaEncontrada.value = true;

      // Autocompletar inteligente con datos de su último acceso (placa y predio habitual)
      if (res.data.ultimo_acceso) {
        const u = res.data.ultimo_acceso;
        if (!form.vehiculo_placa && u.acc_vehiculo_placa) {
          form.vehiculo_placa = u.acc_vehiculo_placa;
          if (u.acc_vehiculo_marca) form.vehiculo_marca = u.acc_vehiculo_marca;
          if (u.acc_vehiculo_color) form.vehiculo_color = u.acc_vehiculo_color;
        }
        if (!form.inmueble_id && u.acc_inmueble_id) {
          form.inmueble_id = u.acc_inmueble_id;
        }
        if (u.acc_rol_visita) {
          form.rol_visita = u.acc_rol_visita;
        }
        busquedaMsg.value = `✓ Encontrado: ${p.per_nombres} ${p.per_apellidos} (Autocompletados placa ${u.acc_vehiculo_placa || 'N/A'})`;
      } else {
        busquedaMsg.value = `✓ Encontrado en Padrón: ${p.per_nombres} ${p.per_apellidos}`;
      }
    } else {
      busquedaMsg.value = '+ Persona nueva (se registrará automáticamente en el Padrón Único)';
      personaEncontrada.value = false;
    }
  } catch (e) {
    busquedaMsg.value = '';
  }
};

let timerSonido = null;
let pollingInterval = null;

const cargarDentro = async () => {
  try {
    const res = await axios.get('/api/accesos/activos-dentro', headersConfig);
    if (res.data.success) {
      accesosDentro.value = res.data.data;
      if (res.data.reglas) {
        Object.assign(reglasPermanencia, res.data.reglas);
      }
      if (res.data.total_excedidos > 0 && sonidoHabilitado.value) {
        emitirAlertaSonora();
      }
    }
  } catch (e) {
    console.error('Error cargando accesos dentro:', e);
  }
};

const cargarInmuebles = async () => {
  try {
    const res = await axios.get('/api/inmuebles', headersConfig);
    if (res.data.success) {
      inmuebles.value = res.data.data;
    }
  } catch (e) {}
};

const cargarGaritas = async () => {
  try {
    const res = await axios.get('/api/garitas', headersConfig);
    if (res.data.success && res.data.data.length > 0) {
      garitasDisponibles.value = res.data.data;
      const guardado = localStorage.getItem('residia_garita_id');
      if (guardado && garitasDisponibles.value.some(g => g.gar_id == guardado)) {
        garitaActivaId.value = parseInt(guardado);
      } else {
        garitaActivaId.value = garitasDisponibles.value[0].gar_id;
      }
    }
  } catch (e) {
    console.error('Error cargando garitas:', e);
  }
};

const cambiarGaritaActiva = () => {
  localStorage.setItem('residia_garita_id', garitaActivaId.value);
};

const guardarNuevaGarita = async () => {
  guardandoGarita.value = true;
  try {
    const res = await axios.post('/api/garitas', formGarita, headersConfig);
    if (res.data.success) {
      alert('Puesto de garita registrado exitosamente.');
      mostrarModalNuevaGarita.value = false;
      formGarita.gar_nombre = '';
      formGarita.gar_ubicacion = '';
      await cargarGaritas();
      garitaActivaId.value = res.data.data.gar_id;
      cambiarGaritaActiva();
    }
  } catch (e) {
    alert(e.response?.data?.message || 'Error registrando garita');
  } finally {
    guardandoGarita.value = false;
  }
};

const cargarPaqueteria = async () => {
  try {
    const res = await axios.get('/api/paqueteria', headersConfig);
    if (res.data.success) paqueteriaList.value = res.data.data;
  } catch (e) {}
};

const cargarNovedades = async () => {
  try {
    const res = await axios.get('/api/novedades', headersConfig);
    if (res.data.success) novedadesList.value = res.data.data;
  } catch (e) {}
};

const guardarPaquete = async () => {
  guardandoPaquete.value = true;
  try {
    const res = await axios.post('/api/paqueteria', formPaquete, headersConfig);
    if (res.data.success) {
      alert('Paquete registrado en garita.');
      formPaquete.inmueble_id = null;
      formPaquete.empresa_courier = '';
      formPaquete.codigo_guia = '';
      formPaquete.destinatario_nombre = '';
      formPaquete.descripcion = '';
      cargarPaqueteria();
    }
  } catch (e) {
    alert(e.response?.data?.message || 'Error guardando paquete');
  } finally {
    guardandoPaquete.value = false;
  }
};

const abrirEntregaPaquete = (p) => {
  paqueteSeleccionado.value = p;
  formEntrega.retirado_por = p.paq_destinatario_nombre || '';
  formEntrega.observaciones = '';
  mostrarModalEntrega.value = true;
};

const confirmarEntregaPaquete = async () => {
  if (!paqueteSeleccionado.value) return;
  guardandoEntrega.value = true;
  try {
    const res = await axios.post(`/api/paqueteria/${paqueteSeleccionado.value.paq_id}/entregar`, formEntrega, headersConfig);
    if (res.data.success) {
      alert('Paquete entregado y retirado con éxito.');
      mostrarModalEntrega.value = false;
      cargarPaqueteria();
    }
  } catch (e) {
    alert(e.response?.data?.message || 'Error al confirmar entrega');
  } finally {
    guardandoEntrega.value = false;
  }
};

const guardarNovedad = async () => {
  guardandoNovedad.value = true;
  try {
    formNovedad.nov_garita_id = garitaActivaId.value;
    const res = await axios.post('/api/novedades', formNovedad, headersConfig);
    if (res.data.success) {
      alert('Novedad asentada en el libro digital.');
      formNovedad.nov_titulo = '';
      formNovedad.nov_detalle = '';
      cargarNovedades();
    }
  } catch (e) {
    alert(e.response?.data?.message || 'Error al asentar novedad');
  } finally {
    guardandoNovedad.value = false;
  }
};

const resolverNovedad = async (id) => {
  try {
    const res = await axios.post(`/api/novedades/${id}/resolver`, {}, headersConfig);
    if (res.data.success) {
      alert('Incidencia marcada como resuelta.');
      cargarNovedades();
    }
  } catch (e) {
    alert('Error al resolver novedad');
  }
};

const cargarVetados = async () => {
  try {
    const res = await axios.get('/api/lista-negra', headersConfig);
    if (res.data.success) {
      vetadosList.value = res.data.data;
    }
  } catch (e) {}
};

const cargarPases = async () => {
  try {
    const res = await axios.get('/api/invitaciones', headersConfig);
    if (res.data.success) {
      const raw = res.data.data;
      pasesList.value = Array.isArray(raw) ? raw : (raw?.data || []);
    }
  } catch (e) {
    pasesList.value = [];
  }
};

const registrarIngreso = async () => {
  if (alertaVeto.value) {
    alert('Acción cancelada: No se puede autorizar el ingreso de un elemento vetado.');
    return;
  }
  guardando.value = true;
  try {
    const payload = { ...form, garita_ingreso_id: garitaActivaId.value };
    const res = await axios.post('/api/accesos/ingreso', payload, headersConfig);
    if (res.data.success) {
      alert('Ingreso registrado exitosamente y auditado en PostgreSQL.');
      form.persona_identificacion = '';
      form.persona_nombres = '';
      form.persona_apellidos = '';
      form.vehiculo_placa = '';
      form.observaciones = '';
      busquedaMsg.value = '';
      alertaVeto.value = null;
      vehiculoResidenteDetectado.value = null;
      cargarGaritas();
  cargarDentro();
  cargarPaqueteria();
  cargarNovedades();
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Error registrando ingreso');
  } finally {
    guardando.value = false;
  }
};

const canjearPaseQr = async () => {
  canjeandoQr.value = true;
  qrResultado.value = null;
  try {
    const res = await axios.post('/api/invitaciones/canjear-qr', { token: qrTokenInput.value }, headersConfig);
    if (res.data.success) {
      qrResultado.value = {
        valido: true,
        message: res.data.message,
        visitante: res.data.visitante,
        placa: res.data.placa,
      };
      qrTokenInput.value = '';
      cargarDentro();
      cargarPases();
    }
  } catch (err) {
    qrResultado.value = {
      valido: false,
      message: err.response?.data?.message || 'Error al validar el código QR',
    };
  } finally {
    canjeandoQr.value = false;
  }
};

const guardarVeto = async () => {
  guardandoVeto.value = true;
  try {
    const res = await axios.post('/api/lista-negra', formVeto, headersConfig);
    if (res.data.success) {
      alert('Restricción registrada exitosamente en Lista Negra.');
      mostrarModalNuevoVeto.value = false;
      formVeto.identificacion = '';
      formVeto.placa_vehiculo = '';
      formVeto.nombres = '';
      formVeto.apellidos = '';
      formVeto.motivo = '';
      cargarVetados();
    }
  } catch (e) {
    alert(e.response?.data?.message || 'Error guardando veto');
  } finally {
    guardandoVeto.value = false;
  }
};

const levantarVeto = async (id) => {
  if (!confirm('¿Desea levantar esta restricción de lista negra?')) return;
  try {
    const res = await axios.delete(`/api/lista-negra/${id}`, headersConfig);
    if (res.data.success) {
      alert('Veto revocado con éxito.');
      cargarVetados();
    }
  } catch (e) {
    alert('Error al revocar veto');
  }
};

const guardarPaseQr = async () => {
  guardandoPase.value = true;
  try {
    const res = await axios.post('/api/invitaciones', formPase, headersConfig);
    if (res.data.success) {
      alert(`Pase generado con éxito! Token: ${res.data.invitacion.token}`);
      mostrarModalNuevoPase.value = false;
      cargarPases();
    }
  } catch (e) {
    alert(e.response?.data?.message || 'Error al generar pase');
  } finally {
    guardandoPase.value = false;
  }
};

const marcarSalida = async (id) => {
  if (!confirm('¿Confirmar la salida de esta persona/vehículo?')) return;
  try {
    const res = await axios.post(`/api/accesos/${id}/salida`, { garita_salida_id: garitaActivaId.value }, headersConfig);
    if (res.data.success) {
      alert(`Salida registrada. Tiempo dentro: ${res.data.duracion_minutos || 0} min.`);
      cargarDentro();
    }
  } catch (e) {
    alert('Error registrando salida');
  }
};

const formatHora = (str) => {
  if (!str) return '';
  const d = new Date(str);
  return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};


// ==========================================
// INTEGRACIÓN DE HARDWARE IOT & BARRERAS
// ==========================================
const dispositivosIot = ref([]);
const cargandoIot = ref(false);
const simulando = ref(false);
const enviandoContingencia = ref(false);
const resultadoSimulacion = ref(null);

const simuladorLpr = reactive({
  placa: 'PBC-8812'
});

const simuladorRfid = reactive({
  tag: 'UHF-778899'
});

const formContingencia = reactive({
  carril: 'BARRERA_ENTRADA_01',
  motivo: '',
  vehiculo_placa: '',
  inmueble_id: null
});

const cargarDispositivosIot = async () => {
  cargandoIot.value = true;
  try {
    const res = await axios.get('/api/hardware/dispositivos', headersConfig);
    if (res.data.success) {
      dispositivosIot.value = res.data.dispositivos;
    }
  } catch (e) {
    console.error('Error cargando dispositivos IoT:', e);
  } finally {
    cargandoIot.value = false;
  }
};

const simularDeteccionLpr = async () => {
  if (!simuladorLpr.placa) return;
  simulando.value = true;
  resultadoSimulacion.value = null;
  try {
    const res = await axios.post('/api/hardware/lpr-deteccion', {
      placa: simuladorLpr.placa,
      carril: 'ENTRADA_01',
      confidence: 99.4
    }, headersConfig);
    resultadoSimulacion.value = res.data;
    if (res.data.apertura) {
      emitirAlertaNotificacionPase();
      cargarDentro();
    } else {
      emitirAlertaSonora();
    }
  } catch (error) {
    resultadoSimulacion.value = {
      apertura: false,
      mensaje: error.response?.data?.alerta || error.response?.data?.message || 'Error procesando LPR'
    };
    emitirAlertaSonora();
  } finally {
    simulando.value = false;
  }
};

const simularDeteccionRfid = async () => {
  if (!simuladorRfid.tag) return;
  simulando.value = true;
  resultadoSimulacion.value = null;
  try {
    const res = await axios.post('/api/hardware/rfid-deteccion', {
      tag_rfid: simuladorRfid.tag,
      antena_id: 'ANTENA_UHF_01'
    }, headersConfig);
    resultadoSimulacion.value = res.data;
    if (res.data.apertura) {
      emitirAlertaNotificacionPase();
      cargarDentro();
    }
  } catch (error) {
    resultadoSimulacion.value = {
      apertura: false,
      mensaje: error.response?.data?.mensaje || 'Tag no reconocido'
    };
    emitirAlertaSonora();
  } finally {
    simulando.value = false;
  }
};

const ejecutarAperturaContingencia = async () => {
  if (!formContingencia.motivo) return;
  enviandoContingencia.value = true;
  try {
    const res = await axios.post('/api/hardware/apertura-contingencia', formContingencia, headersConfig);
    if (res.data.success) {
      alert(`✓ ${res.data.message}`);
      formContingencia.motivo = '';
      formContingencia.vehiculo_placa = '';
      formContingencia.inmueble_id = null;
    }
  } catch (error) {
    alert(error.response?.data?.message || 'Error en pulso de apertura');
  } finally {
    enviandoContingencia.value = false;
  }
};

onMounted(() => {
  cargarDentro();
  cargarInmuebles();
  cargarVetados();
  cargarPases();

  // Polling automático cada 30s para refrescar semáforo de garita
  pollingInterval = setInterval(() => {
    cargarDentro();
  }, 30000);
});

onUnmounted(() => {
  if (pollingInterval) clearInterval(pollingInterval);
});
</script>

<style scoped>
.garita-wrapper {
  width: 100%;
  padding: 16px 24px;
}
.garita-status-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 16px;
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 10px;
  margin-bottom: 20px;
  flex-wrap: wrap;
  gap: 12px;
}
.status-left {
  display: flex;
  align-items: center;
  gap: 14px;
}
.badge-urbanizacion {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 600;
  color: var(--text-primary);
}
.dot-online {
  width: 8px;
  height: 8px;
  background: var(--color-primary);
  border-radius: 50%;
  box-shadow: 0 0 8px var(--color-primary);
}
.tag-live {
  background: var(--color-primary-subtle);
  color: var(--color-primary);
  font-size: 11px;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 6px;
}
.status-right {
  display: flex;
  align-items: center;
  gap: 16px;
}
.mode-pills {
  display: flex;
  background: var(--bg-primary);
  padding: 3px;
  border-radius: 8px;
  border: 1px solid var(--border-subtle);
  gap: 4px;
}
.pill-active, .pill-inactive {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
}
.pill-active {
  background: var(--color-primary);
  color: #FFFFFF;
}
.pill-inactive {
  background: transparent;
  color: var(--text-secondary);
}
.pill-inactive:hover {
  color: var(--text-primary);
}
.guard-pill {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: var(--text-secondary);
}

/* BANNER DE VETO */
.banner-permanencia-alerta {
  background: rgba(245, 158, 11, 0.12);
  border: 1px solid rgba(245, 158, 11, 0.4);
  border-radius: 12px;
  padding: 12px 18px;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  box-shadow: 0 4px 16px rgba(245, 158, 11, 0.1);
}
.alerta-permanencia-icon {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  background: rgba(245, 158, 11, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
}
.badge-tiempo-limite {
  background: rgba(245, 158, 11, 0.25);
  color: #fbbf24;
  padding: 2px 8px;
  border-radius: 6px;
  font-size: 10px;
  font-weight: 700;
}
.btn-audio-toggle {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 8px;
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  color: var(--text-primary);
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}
.btn-audio-toggle:hover {
  background: var(--bg-hover);
  border-color: var(--color-primary);
}
.semaforo-cell {
  display: flex;
  flex-direction: column;
}
.semaforo-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px 8px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 700;
  font-family: monospace;
  width: fit-content;
}
.badge-verde {
  background: rgba(16, 185, 129, 0.12);
  color: #34d399;
  border: 1px solid rgba(16, 185, 129, 0.3);
}
.badge-amarillo {
  background: rgba(245, 158, 11, 0.15);
  color: #fbbf24;
  border: 1px solid rgba(245, 158, 11, 0.35);
}
.badge-rojo {
  background: rgba(239, 68, 68, 0.2);
  color: #f87171;
  border: 1px solid rgba(239, 68, 68, 0.4);
}
.banner-veto-alerta {
  background: #DC2626;
  color: #FFFFFF;
  border-radius: 12px;
  padding: 14px 20px;
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 20px;
  box-shadow: 0 8px 24px rgba(220, 38, 38, 0.4);
  animation: shake 0.4s ease-in-out;
}
@keyframes shake {
  0%, 100% { transform: translateX(0); }
  20%, 60% { transform: translateX(-6px); }
  40%, 80% { transform: translateX(6px); }
}
.alerta-icon-box {
  flex-shrink: 0;
}
.alerta-body {
  flex: 1;
}
.alerta-title {
  font-weight: 800;
  font-size: 14px;
  letter-spacing: 0.04em;
}
.alerta-desc {
  font-size: 13px;
  margin-top: 2px;
  opacity: 0.95;
}
.btn-cerrar-alerta {
  background: rgba(0, 0, 0, 0.25);
  border: 1px solid rgba(255, 255, 255, 0.3);
  color: #FFFFFF;
  font-size: 11px;
  font-weight: 600;
  padding: 6px 12px;
  border-radius: 6px;
  cursor: pointer;
}

/* BANNER RESIDENTE DETECTADO */
.banner-residente-detectado {
  background: rgba(16, 185, 129, 0.15);
  border: 1px solid rgba(16, 185, 129, 0.4);
  border-radius: 10px;
  padding: 10px 14px;
  margin-bottom: 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.tag-rfid-indicator {
  width: 32px;
  height: 32px;
  background: rgba(16, 185, 129, 0.2);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.badge-acceso-rapido {
  background: #10B981;
  color: #FFFFFF;
  font-size: 10px;
  font-weight: 700;
  padding: 3px 8px;
  border-radius: 6px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.garita-grid {
  display: grid;
  grid-template-columns: 1.1fr 1.9fr;
  gap: 20px;
  align-items: start;
}
@media (max-width: 960px) {
  /* BANNER RESIDENTE DETECTADO */
.banner-residente-detectado {
  background: rgba(16, 185, 129, 0.15);
  border: 1px solid rgba(16, 185, 129, 0.4);
  border-radius: 10px;
  padding: 10px 14px;
  margin-bottom: 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.tag-rfid-indicator {
  width: 32px;
  height: 32px;
  background: rgba(16, 185, 129, 0.2);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.badge-acceso-rapido {
  background: #10B981;
  color: #FFFFFF;
  font-size: 10px;
  font-weight: 700;
  padding: 3px 8px;
  border-radius: 6px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.garita-grid {
    grid-template-columns: 1fr;
  }
}

.card-box {
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
}
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 1px solid var(--border-subtle);
}
.card-title {
  font-size: 15px;
  font-weight: 700;
  color: var(--text-primary);
}
.tag-status {
  background: var(--color-primary-subtle);
  color: var(--color-primary);
  font-size: 11px;
  padding: 3px 8px;
  border-radius: 6px;
  font-weight: 600;
}
.counter-badge {
  background: var(--bg-primary);
  border: 1px solid var(--border-subtle);
  color: var(--color-primary);
  padding: 3px 10px;
  border-radius: 9999px;
  font-size: 11px;
  font-weight: 600;
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
  letter-spacing: 0.05em;
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
  transition: all 0.2s;
}
.form-input:focus, .form-select:focus {
  border-color: var(--border-focus);
  box-shadow: 0 0 0 2px var(--color-primary-subtle);
}
.input-search-wrap {
  display: flex;
  position: relative;
}
.btn-search-icon {
  position: absolute;
  right: 8px;
  top: 8px;
  background: transparent;
  border: none;
  color: var(--text-secondary);
  cursor: pointer;
}
.grid-2-cols {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.tab-options {
  display: grid;
  grid-template-columns: 1fr 1fr;
  background: var(--bg-primary);
  padding: 4px;
  border-radius: 8px;
  border: 1px solid var(--border-subtle);
  gap: 4px;
}
.tab-active, .tab-inactive {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 7px;
  font-size: 12px;
  font-weight: 600;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}
.tab-active {
  background: var(--color-primary);
  color: #FFFFFF;
}
.tab-inactive {
  background: transparent;
  color: var(--text-secondary);
}

.vehiculo-box {
  background: var(--bg-primary);
  padding: 12px;
  border-radius: 8px;
  border: 1px dashed var(--border-subtle);
  margin-bottom: 12px;
}

.btn-submit, .btn-primary {
  background: var(--color-primary);
  color: #FFFFFF;
  padding: 10px 16px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-submit {
  width: 100%;
  padding: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-top: 10px;
}
.btn-submit:hover, .btn-primary:hover {
  background: var(--color-primary-hover);
  transform: translateY(-1px);
}
.btn-secondary {
  background: var(--bg-primary);
  border: 1px solid var(--border-subtle);
  color: var(--text-primary);
  padding: 8px 14px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
}
.btn-danger-action {
  background: #DC2626;
  color: #FFFFFF;
  padding: 8px 14px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  border: none;
  cursor: pointer;
}
.btn-danger-action:hover {
  background: #B91C1C;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  color: var(--text-muted);
  font-size: 13px;
}
.table-container {
  overflow-x: auto;
}
.data-table {
  width: 100%;
  border-collapse: collapse;
}
.data-table th {
  text-align: left;
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--text-muted);
  padding: 10px 8px;
  border-bottom: 1px solid var(--border-subtle);
}
.data-table td {
  padding: 12px 8px;
  border-bottom: 1px solid var(--border-subtle);
}
.tag-vehicular {
  background: var(--bg-primary);
  border: 1px solid var(--border-subtle);
  padding: 3px 8px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
}
.tag-peatonal {
  background: var(--bg-primary);
  padding: 3px 8px;
  border-radius: 6px;
  font-size: 12px;
}
.btn-salida {
  background: var(--bg-primary);
  border: 1px solid var(--border-subtle);
  color: var(--color-warning);
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: all 0.2s;
}
.btn-salida:hover {
  background: var(--color-warning);
  color: #FFFFFF;
}
.hint-text {
  font-size: 11px;
  margin-top: 3px;
}

/* SCANNER QR STYLES */
.qr-scan-box {
  background: var(--bg-primary);
  border: 1px dashed var(--border-subtle);
  border-radius: 12px;
  padding: 24px;
}
.qr-graphic-frame {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 30px;
  background: var(--bg-surface);
  border-radius: 12px;
  border: 1px solid var(--border-subtle);
}
.qr-result-card {
  margin-top: 14px;
  padding: 12px 16px;
  border-radius: 8px;
  border: 1px solid transparent;
}
.qr-success {
  background: rgba(16, 185, 129, 0.15);
  border-color: rgba(16, 185, 129, 0.3);
  color: #10B981;
}
.qr-error {
  background: rgba(239, 68, 68, 0.15);
  border-color: rgba(239, 68, 68, 0.3);
  color: #EF4444;
}

/* MODALES */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.7);
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
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
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
.garita-selector-wrap {
  display: flex;
  align-items: center;
  gap: 6px;
  background: var(--bg-hover);
  padding: 4px 8px;
  border-radius: 8px;
  border: 1px solid var(--border-subtle);
}
.garita-select-control {
  background: transparent;
  border: none;
  font-size: 12px;
  font-weight: 600;
  color: var(--text-primary);
  outline: none;
  cursor: pointer;
}
.garita-select-control option {
  background: var(--bg-surface);
  color: var(--text-primary);
}
.btn-icon-subtle {
  background: transparent;
  border: none;
  color: var(--color-primary);
  cursor: pointer;
  padding: 2px;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.btn-icon-subtle:hover {
  background: var(--bg-primary-subtle);
}

/* TOAST NOTIFICACIÓN EN TIEMPO REAL SSE */
.banner-notif-realtime {
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(6, 78, 59, 0.4));
  border: 1px solid rgba(16, 185, 129, 0.5);
  padding: 0.75rem 1rem;
  border-radius: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
  box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.2);
  backdrop-filter: blur(8px);
  animation: slideDown 0.3s ease-out;
}

.notif-pulse-icon {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  background: rgba(16, 185, 129, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-cerrar-notif {
  background: transparent;
  border: none;
  color: var(--text-muted);
  cursor: pointer;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.85rem;
  transition: all 0.2s;
}

.btn-cerrar-notif:hover {
  background: rgba(255, 255, 255, 0.1);
  color: #ffffff;
}

@keyframes slideDown {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}

</style>