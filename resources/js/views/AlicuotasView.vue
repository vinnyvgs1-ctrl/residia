<template>
  <div class="alicuotas-container">
    <!-- CABECERA PRINCIPAL & ACCIONES RÁPIDAS -->
    <div class="top-header-panel">
      <div>
        <h2 class="text-lg font-bold text-white flex items-center gap-2">
          <DollarSign class="text-emerald-400" :size="20" />
          <span>Tesorería, Alícuotas & Cartera Consolidada</span>
        </h2>
        <p class="text-xs text-[var(--text-muted)] mt-0.5">
          Gestión financiera de expensas, emisión masiva por cuota ponderada, semáforo de morosidad y comprobantes oficiales.
        </p>
      </div>

      <div class="flex items-center gap-3">
        <button 
          @click="cargarDatos" 
          :disabled="cargando"
          class="btn-secondary flex items-center gap-1.5"
          title="Recargar Métricas y Cartera"
        >
          <RotateCcw :size="15" :class="{ 'animate-spin': cargando }" />
          <span>Actualizar</span>
        </button>

        <button 
          @click="abrirModalEmision"
          class="btn-primary flex items-center gap-1.5"
        >
          <Zap :size="15" class="text-amber-300" />
          <span>⚡ Emisión Masiva de Cuotas</span>
        </button>
      </div>
    </div>

    <!-- 4 KPI CARDS FINANCIEROS -->
    <div class="grid-kpi-cards">
      <div class="kpi-card card-recaudado">
        <div class="kpi-icon-wrap bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
          <TrendingUp :size="20" />
        </div>
        <div>
          <div class="kpi-label">Total Recaudado</div>
          <div class="kpi-val text-emerald-400">${{ Number(metricas.total_recaudado || 0).toFixed(2) }}</div>
          <div class="kpi-subtext">Ingresos cobrados y conciliados</div>
        </div>
      </div>

      <div class="kpi-card card-pendiente">
        <div class="kpi-icon-wrap bg-amber-500/10 text-amber-400 border border-amber-500/20">
          <AlertCircle :size="20" />
        </div>
        <div>
          <div class="kpi-label">Cartera Pendiente / Mora</div>
          <div class="kpi-val text-amber-400">${{ Number(metricas.total_pendiente || 0).toFixed(2) }}</div>
          <div class="kpi-subtext">Suma de saldos por cobrar</div>
        </div>
      </div>

      <div class="kpi-card card-aldia">
        <div class="kpi-icon-wrap bg-blue-500/10 text-blue-400 border border-blue-500/20">
          <CheckCircle2 :size="20" />
        </div>
        <div>
          <div class="kpi-label">% Predios al Día</div>
          <div class="kpi-val text-blue-400">{{ metricas.porcentaje_al_dia || 100 }}%</div>
          <div class="kpi-subtext">{{ metricas.inmuebles_al_dia }} de {{ metricas.total_inmuebles }} predios sin mora</div>
        </div>
      </div>

      <div class="kpi-card card-morosos">
        <div class="kpi-icon-wrap bg-rose-500/10 text-rose-400 border border-rose-500/20">
          <ShieldAlert :size="20" />
        </div>
        <div>
          <div class="kpi-label">Predios en Morosidad</div>
          <div class="kpi-val text-rose-400">{{ metricas.inmuebles_morosos || 0 }}</div>
          <div class="kpi-subtext">Con alerta preventiva en garita</div>
        </div>
      </div>
    </div>

    <!-- BARRA DE BÚSQUEDA Y FILTROS -->
    <div class="filters-bar">
      <div class="relative flex-1">
        <Search class="absolute left-3 top-2.5 text-[var(--text-muted)]" :size="15" />
        <input 
          v-model="filtroBusqueda" 
          placeholder="Buscar por manzana, villa, depto o titular..."
          class="form-input pl-9 text-xs" 
        />
      </div>

      <div class="flex items-center gap-2">
        <button 
          @click="filtroEstado = 'TODOS'"
          class="filter-pill-btn"
          :class="{ 'filter-pill-active': filtroEstado === 'TODOS' }"
        >
          Todos ({{ cartera.length }})
        </button>
        <button 
          @click="filtroEstado = 'MOROSOS'"
          class="filter-pill-btn"
          :class="{ 'filter-pill-active': filtroEstado === 'MOROSOS' }"
        >
          Morosos ({{ carteraMorososCount }})
        </button>
        <button 
          @click="filtroEstado = 'AL_DIA'"
          class="filter-pill-btn"
          :class="{ 'filter-pill-active': filtroEstado === 'AL_DIA' }"
        >
          Al Día ({{ carteraAlDiaCount }})
        </button>
      </div>
    </div>

    <!-- DATA-GRID DE CARTERA POR INMUEBLE -->
    <div class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th>Ubicación / Predio</th>
            <th>Tipo & Estado</th>
            <th>Titular / Propietario</th>
            <th>Cuotas Pend.</th>
            <th>Saldo en Mora</th>
            <th>Semáforo Garita</th>
            <th class="text-right">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="cargando">
            <td colspan="7" class="text-center py-10 text-[var(--text-muted)]">
              <RotateCcw :size="20" class="animate-spin inline-block mr-2" />
              Cargando balance consolidado de la sede...
            </td>
          </tr>
          <tr v-else-if="carteraFiltrada.length === 0">
            <td colspan="7" class="text-center py-10 text-[var(--text-muted)]">
              No se encontraron predios con los criterios seleccionados.
            </td>
          </tr>
          <tr 
            v-for="inm in carteraFiltrada" 
            :key="inm.inm_id"
            class="hover:bg-[var(--bg-surface)] transition-colors"
          >
            <!-- Predio -->
            <td>
              <div class="font-bold text-white text-xs">
                {{ inm.inm_manzana_bloque }} - {{ inm.inm_numero_villa }}
              </div>
              <div class="text-[10px] text-[var(--text-muted)]">
                Ponderación: {{ inm.inm_alicuota_porcentaje ? inm.inm_alicuota_porcentaje + '%' : '100% (Base)' }}
              </div>
            </td>

            <!-- Tipo & Estado -->
            <td>
              <span class="badge-tipo">{{ inm.inm_tipo }}</span>
              <span 
                class="badge-estado ml-1"
                :class="inm.inm_estado === 'HABITADO' ? 'text-emerald-400 bg-emerald-500/10' : 'text-slate-400 bg-slate-500/10'"
              >
                {{ inm.inm_estado }}
              </span>
            </td>

            <!-- Titular -->
            <td>
              <div v-if="inm.titular" class="text-xs">
                <div class="font-semibold text-white">
                  {{ inm.titular.per_nombres }} {{ inm.titular.per_apellidos }}
                </div>
                <div class="text-[10px] text-[var(--text-muted)] flex items-center gap-2">
                  <span>CI: {{ inm.titular.per_identificacion }}</span>
                  <span v-if="inm.titular.per_telefono">&bull; {{ inm.titular.per_telefono }}</span>
                </div>
              </div>
              <div v-else class="text-xs text-[var(--text-muted)] italic">
                Sin residente titular asignado
              </div>
            </td>

            <!-- Cuotas Pendientes -->
            <td>
              <span 
                class="font-bold text-xs"
                :class="Number(inm.cuotas_pendientes) > 0 ? 'text-rose-400' : 'text-slate-400'"
              >
                {{ inm.cuotas_pendientes }} cuota(s)
              </span>
              <div v-if="inm.ultimo_periodo_deuda" class="text-[10px] text-[var(--text-muted)]">
                Últ: {{ inm.ultimo_periodo_deuda }}
              </div>
            </td>

            <!-- Deuda Total -->
            <td>
              <div 
                class="font-mono font-bold text-xs"
                :class="Number(inm.deuda_total) > 0 ? 'text-rose-400' : 'text-emerald-400'"
              >
                ${{ Number(inm.deuda_total).toFixed(2) }}
              </div>
            </td>

            <!-- Semáforo Garita -->
            <td>
              <span 
                v-if="Number(inm.deuda_total) > 0"
                class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-bold bg-amber-500/15 text-amber-300 border border-amber-500/30"
              >
                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                Aviso Moroso
              </span>
              <span 
                v-else
                class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30"
              >
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                Al Día
              </span>
            </td>

            <!-- Acciones -->
            <td class="text-right">
              <button 
                @click="abrirDetalleObligaciones(inm)"
                class="btn-row-action"
                title="Ver historial de alícuotas, registrar pago o ver recibos"
              >
                <Receipt :size="13" />
                <span>Historial & Pagos</span>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- MODAL 1: EMISIÓN MASIVA DE ALÍCUOTAS -->
    <div v-if="modalEmisionVisible" class="modal-overlay" @click.self="modalEmisionVisible = false">
      <div class="modal-content max-w-lg">
        <div class="modal-header">
          <div class="flex items-center gap-2">
            <Zap :size="18" class="text-amber-400" />
            <h3 class="text-sm font-bold text-white">Emisión Masiva de Cuotas Ordinarias</h3>
          </div>
          <button @click="modalEmisionVisible = false" class="text-slate-400 hover:text-white">✕</button>
        </div>

        <form @submit.prevent="ejecutarEmisionMasiva" class="modal-body space-y-4">
          <div class="p-3 bg-amber-500/10 border border-amber-500/30 rounded-lg text-xs text-amber-200">
            Esta acción generará automáticamente la obligación de pago para todos los inmuebles de la urbanización correspondientes al período seleccionado. Si ya existe la cuota para un predio, se omitirá para evitar duplicidades.
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div class="input-block">
              <label>Período Fiscal (YYYY-MM) *</label>
              <input 
                v-model="formEmision.periodo" 
                type="month" 
                class="form-input" 
                required 
              />
            </div>

            <div class="input-block">
              <label>Monto Base por Predio ($) *</label>
              <input 
                v-model.number="formEmision.monto_base" 
                type="number" 
                step="0.01" 
                class="form-input font-mono" 
                required 
              />
            </div>
          </div>

          <div class="input-block">
            <label>Concepto / Glosa Oficial *</label>
            <input 
              v-model="formEmision.concepto" 
              class="form-input" 
              placeholder="Ej. Alícuota Ordinaria Mantenimiento"
              required 
            />
          </div>

          <div class="input-block">
            <label>Fecha de Vencimiento *</label>
            <input 
              v-model="formEmision.fecha_vencimiento" 
              type="date" 
              class="form-input" 
              required 
            />
          </div>

          <div class="space-y-2 pt-2 border-t border-[var(--border-subtle)]">
            <label class="checkbox-row">
              <input type="checkbox" v-model="formEmision.usar_ponderacion" class="form-checkbox" />
              <span class="text-xs text-[var(--text-secondary)]">
                Calcular proporcionalmente con el porcentaje de alícuota del predio (ej. alícuota 120% = ${{ (formEmision.monto_base * 1.2).toFixed(2) }})
              </span>
            </label>

            <label class="checkbox-row">
              <input type="checkbox" v-model="formEmision.solo_habitados" class="form-checkbox" />
              <span class="text-xs text-[var(--text-secondary)]">
                Emitir únicamente a inmuebles con estado "HABITADO"
              </span>
            </label>
          </div>

          <div class="modal-footer flex items-center justify-end gap-2 pt-4">
            <button 
              type="button" 
              @click="modalEmisionVisible = false" 
              class="btn-secondary"
            >
              Cancelar
            </button>
            <button 
              type="submit" 
              :disabled="procesandoEmision" 
              class="btn-primary flex items-center gap-1.5"
            >
              <Check :size="15" />
              <span>{{ procesandoEmision ? 'Generando en lote...' : 'Confirmar Emisión Masiva' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL 2: HISTORIAL DE OBLIGACIONES & REGISTRO DE PAGOS DEL PREDIO -->
    <div v-if="modalDetalleVisible" class="modal-overlay" @click.self="modalDetalleVisible = false">
      <div class="modal-content max-w-3xl">
        <div class="modal-header">
          <div>
            <div class="text-xs font-bold text-emerald-400 uppercase tracking-wider">
              Estado de Cuenta Individual
            </div>
            <h3 class="text-base font-bold text-white">
              {{ predioSeleccionado?.inm_manzana_bloque }} - {{ predioSeleccionado?.inm_numero_villa }}
            </h3>
          </div>
          <button @click="modalDetalleVisible = false" class="text-slate-400 hover:text-white">✕</button>
        </div>

        <div class="modal-body space-y-4">
          <!-- RESUMEN PREVIO -->
          <div class="flex items-center justify-between p-3 bg-[var(--bg-primary)] rounded-lg border border-[var(--border-subtle)]">
            <div class="text-xs">
              <span class="text-[var(--text-muted)]">Titular: </span>
              <strong class="text-white">
                {{ predioSeleccionado?.titular ? `${predioSeleccionado.titular.per_nombres} ${predioSeleccionado.titular.per_apellidos}` : 'Sin Titular Asignado' }}
              </strong>
            </div>
            <div class="text-xs font-bold">
              <span class="text-[var(--text-muted)]">Saldo Total: </span>
              <span :class="Number(predioSeleccionado?.deuda_total) > 0 ? 'text-rose-400' : 'text-emerald-400'" class="font-mono text-sm ml-1">
                ${{ Number(predioSeleccionado?.deuda_total || 0).toFixed(2) }}
              </span>
            </div>
          </div>

          <!-- LISTADO DE OBLIGACIONES -->
          <div class="max-h-80 overflow-y-auto rounded-lg border border-[var(--border-subtle)]">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Período</th>
                  <th>Concepto</th>
                  <th>Monto</th>
                  <th>Estado</th>
                  <th>Vencimiento</th>
                  <th class="text-right">Acción</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="cargandoObligaciones">
                  <td colspan="6" class="text-center py-6 text-[var(--text-muted)]">
                    <RotateCcw :size="16" class="animate-spin inline-block mr-2" />
                    Cargando obligaciones...
                  </td>
                </tr>
                <tr v-else-if="obligacionesPredio.length === 0">
                  <td colspan="6" class="text-center py-6 text-[var(--text-muted)]">
                    No registra obligaciones emitidas en este predio.
                  </td>
                </tr>
                <tr v-for="obl in obligacionesPredio" :key="obl.obl_id">
                  <td class="font-bold text-xs text-white">{{ obl.obl_periodo }}</td>
                  <td class="text-xs">{{ obl.obl_concepto }}</td>
                  <td class="font-mono font-bold text-xs text-white">${{ Number(obl.obl_monto).toFixed(2) }}</td>
                  <td>
                    <span 
                      class="px-2 py-0.5 rounded text-[10px] font-bold"
                      :class="badgeClassEstado(obl.obl_estado)"
                    >
                      {{ obl.obl_estado }}
                    </span>
                  </td>
                  <td class="text-xs text-[var(--text-muted)]">{{ obl.obl_fecha_vencimiento }}</td>
                  <td class="text-right">
                    <div class="flex items-center justify-end gap-1.5">
                      <!-- BOTÓN PAGAR (SI PENDIENTE) -->
                      <button 
                        v-if="obl.obl_estado === 'PENDIENTE' || obl.obl_estado === 'VENCIDO'"
                        @click="abrirModalRegistrarPago(obl)"
                        class="px-2 py-1 rounded bg-emerald-600 hover:bg-emerald-500 text-white text-[11px] font-bold flex items-center gap-1"
                        title="Registrar cobro de cuota"
                      >
                        <Check :size="12" />
                        <span>Cobrar</span>
                      </button>

                      <!-- BOTÓN VER / IMPRIMIR RECIBO (SI PAGADO) -->
                      <button 
                        v-if="obl.obl_estado === 'PAGADO'"
                        @click="verReciboOficial(obl.obl_id)"
                        class="px-2 py-1 rounded bg-slate-700 hover:bg-slate-600 text-emerald-300 text-[11px] font-bold flex items-center gap-1"
                        title="Ver Comprobante Oficial Imprimible"
                      >
                        <Printer :size="12" />
                        <span>Recibo</span>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="modal-footer flex items-center justify-between pt-3">
          <span class="text-xs text-[var(--text-muted)]">
            Total obligaciones: {{ obligacionesPredio.length }}
          </span>
          <button @click="modalDetalleVisible = false" class="btn-secondary">
            Cerrar
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL 3: REGISTRO DE COBRO / PAGO -->
    <div v-if="modalPagoVisible" class="modal-overlay" @click.self="modalPagoVisible = false">
      <div class="modal-content max-w-md">
        <div class="modal-header">
          <div class="flex items-center gap-2">
            <DollarSign :size="18" class="text-emerald-400" />
            <h3 class="text-sm font-bold text-white">Registrar Cobro de Alícuota</h3>
          </div>
          <button @click="modalPagoVisible = false" class="text-slate-400 hover:text-white">✕</button>
        </div>

        <form @submit.prevent="guardarPagoCuota" class="modal-body space-y-3">
          <div class="p-3 bg-[var(--bg-primary)] rounded border border-[var(--border-subtle)] text-xs">
            <div>Período: <strong>{{ obligacionSeleccionada?.obl_periodo }}</strong></div>
            <div>Concepto: <strong>{{ obligacionSeleccionada?.obl_concepto }}</strong></div>
            <div class="text-emerald-400 font-bold font-mono text-sm mt-1">
              Monto a Recaudar: ${{ Number(obligacionSeleccionada?.obl_monto).toFixed(2) }}
            </div>
          </div>

          <div class="input-block">
            <label>Método de Pago *</label>
            <select v-model="formPago.metodo_pago" class="form-select" required>
              <option value="TRANSFERENCIA">Transferencia Bancaria</option>
              <option value="DEPOSITO">Depósito Bancario</option>
              <option value="EFECTIVO">Efectivo en Garita/Administración</option>
              <option value="TARJETA">Tarjeta de Débito / Crédito</option>
              <option value="OTRO">Otro Método</option>
            </select>
          </div>

          <div class="input-block">
            <label>Número de Comprobante / Referencia Bancaria *</label>
            <input 
              v-model="formPago.comprobante_ref" 
              class="form-input" 
              placeholder="Ej. TRX-88992211"
              required 
            />
          </div>

          <div class="input-block">
            <label>Observaciones de Conciliación</label>
            <textarea 
              v-model="formPago.observaciones" 
              class="form-input" 
              rows="2"
              placeholder="Ej. Conciliado con cuenta corriente Banco Pichincha"
            ></textarea>
          </div>

          <div class="modal-footer flex items-center justify-end gap-2 pt-3">
            <button type="button" @click="modalPagoVisible = false" class="btn-secondary">
              Cancelar
            </button>
            <button type="submit" :disabled="procesandoPago" class="btn-primary flex items-center gap-1.5">
              <Check :size="15" />
              <span>{{ procesandoPago ? 'Procesando...' : 'Confirmar Cobro' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL 4: RECIBO OFICIAL IMPRIMIBLE ROBLEDAL CONNECTING -->
    <div v-if="modalReciboVisible" class="modal-overlay" @click.self="modalReciboVisible = false">
      <div class="modal-content max-w-2xl bg-white text-slate-800 p-0 overflow-hidden">
        <!-- BARRA DE CONTROL SUPERIOR (NO SE IMPRIME) -->
        <div class="no-print bg-slate-900 text-white p-3 flex items-center justify-between">
          <div class="text-xs font-bold flex items-center gap-2">
            <Printer :size="16" class="text-emerald-400" />
            <span>Vista Previa del Comprobante Oficial</span>
          </div>
          <div class="flex items-center gap-2">
            <button @click="imprimirRecibo" class="btn-primary text-xs py-1 px-3 flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-500">
              <Printer :size="14" />
              <span>Imprimir / Descargar PDF</span>
            </button>
            <button @click="modalReciboVisible = false" class="btn-secondary text-xs py-1 px-3">
              Cerrar
            </button>
          </div>
        </div>

        <!-- CUERPO DEL RECIBO (IMPRIMIBLE) -->
        <div id="recibo-imprimible" class="p-8 space-y-6">
          <!-- CABECERA INSTITUCIONAL -->
          <div class="flex items-start justify-between border-b-2 border-slate-800 pb-4">
            <div>
              <h1 class="text-xl font-black tracking-tight text-slate-900 uppercase">
                {{ datosRecibo?.urbanizacion?.nombre || 'URBANIZACIÓN RESIDENCIAL' }}
              </h1>
              <p class="text-xs text-slate-600 font-medium">
                {{ datosRecibo?.urbanizacion?.direccion || 'Av. Principal' }} &bull; {{ datosRecibo?.urbanizacion?.ciudad || 'Ecuador' }}
              </p>
              <p class="text-xs text-slate-600">
                RUC: <strong>{{ datosRecibo?.urbanizacion?.ruc || '1799999999001' }}</strong> &bull; Telf: {{ datosRecibo?.urbanizacion?.telefono || '099999999' }}
              </p>
            </div>
            <div class="text-right">
              <div class="inline-block bg-slate-100 border border-slate-300 rounded px-3 py-1.5 text-center">
                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">COMPROBANTE DE PAGO</div>
                <div class="text-base font-black font-mono text-emerald-700">
                  {{ datosRecibo?.numero_comprobante || 'REC-000000' }}
                </div>
              </div>
              <div class="text-[11px] text-slate-500 mt-1 font-mono">
                Fecha Emisión: {{ datosRecibo?.obligacion?.obl_fecha_pago || new Date().toISOString().slice(0, 10) }}
              </div>
            </div>
          </div>

          <!-- DATOS DEL PROPIETARIO Y PREDIO -->
          <div class="grid grid-cols-2 gap-4 bg-slate-50 p-4 rounded-lg border border-slate-200 text-xs">
            <div>
              <span class="text-slate-500 block uppercase text-[10px] font-bold">Datos del Residente / Titular:</span>
              <strong class="text-slate-900 text-sm block">
                {{ datosRecibo?.titular ? `${datosRecibo.titular.per_nombres} ${datosRecibo.titular.per_apellidos}` : 'PROPIETARIO REGISTRADO' }}
              </strong>
              <span class="text-slate-600 block">Cédula/RUC: {{ datosRecibo?.titular?.per_identificacion || 'N/A' }}</span>
              <span class="text-slate-600 block">Teléfono: {{ datosRecibo?.titular?.per_telefono || 'N/A' }}</span>
            </div>
            <div>
              <span class="text-slate-500 block uppercase text-[10px] font-bold">Ubicación del Inmueble:</span>
              <strong class="text-slate-900 text-sm block">
                {{ datosRecibo?.obligacion?.inm_manzana_bloque }} - {{ datosRecibo?.obligacion?.inm_numero_villa }}
              </strong>
              <span class="text-slate-600 block">Tipo: {{ datosRecibo?.obligacion?.inm_tipo || 'CASA' }}</span>
              <span class="text-slate-600 block">Período Cancelado: <strong>{{ datosRecibo?.obligacion?.obl_periodo }}</strong></span>
            </div>
          </div>

          <!-- TABLA DE DETALLE DE LA ALÍCUOTA -->
          <div>
            <table class="w-full text-xs text-left border-collapse">
              <thead>
                <tr class="bg-slate-800 text-white uppercase text-[10px]">
                  <th class="p-2.5">Descripción de la Obligación</th>
                  <th class="p-2.5">Período</th>
                  <th class="p-2.5">Método de Pago</th>
                  <th class="p-2.5">Referencia</th>
                  <th class="p-2.5 text-right">Total Pagado</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200">
                <tr class="text-slate-700">
                  <td class="p-2.5 font-medium">
                    {{ datosRecibo?.obligacion?.obl_concepto }}
                  </td>
                  <td class="p-2.5 font-mono">{{ datosRecibo?.obligacion?.obl_periodo }}</td>
                  <td class="p-2.5">{{ datosRecibo?.obligacion?.obl_metodo_pago }}</td>
                  <td class="p-2.5 font-mono text-slate-500">{{ datosRecibo?.obligacion?.obl_comprobante_ref || 'TRX-DIRECTA' }}</td>
                  <td class="p-2.5 text-right font-mono font-bold text-slate-900 text-sm">
                    ${{ Number(datosRecibo?.obligacion?.obl_monto || 0).toFixed(2) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- RESUMEN FINAL Y FIRMA -->
          <div class="flex items-end justify-between pt-6 border-t border-slate-200">
            <div class="text-[11px] text-slate-500 max-w-xs">
              <p class="italic">
                "Este comprobante certifica la cancelación efectiva del concepto indicado. Documento generado electrónicamente por el sistema Residia."
              </p>
              <p class="font-mono text-[10px] mt-1 text-slate-400">
                UUID: {{ datosRecibo?.obligacion?.urb_id }}
              </p>
            </div>

            <div class="text-right">
              <div class="text-xs text-slate-500 uppercase tracking-widest font-bold">TOTAL CANCELADO</div>
              <div class="text-2xl font-black font-mono text-emerald-700">
                ${{ Number(datosRecibo?.obligacion?.obl_monto || 0).toFixed(2) }} USD
              </div>
              <div class="mt-6 border-t border-slate-400 pt-1 text-center text-[10px] text-slate-600 font-bold uppercase">
                Administración y Recaudación
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';
import { 
  DollarSign, TrendingUp, AlertCircle, CheckCircle2, ShieldAlert, 
  RotateCcw, Zap, Search, Check, Receipt, Printer 
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
const metricas = reactive({
  total_inmuebles: 0,
  inmuebles_al_dia: 0,
  inmuebles_morosos: 0,
  total_recaudado: 0,
  total_pendiente: 0,
  porcentaje_al_dia: 100
});
const cartera = ref([]);
const filtroBusqueda = ref('');
const filtroEstado = ref('TODOS');

// Modal Emisión Masiva
const modalEmisionVisible = ref(false);
const procesandoEmision = ref(false);
const mesActual = new Date().toISOString().slice(0, 7);
const formEmision = reactive({
  periodo: mesActual,
  monto_base: 50.00,
  concepto: `Alícuota Ordinaria Mantenimiento ${mesActual}`,
  fecha_vencimiento: `${mesActual}-28`,
  usar_ponderacion: false,
  solo_habitados: true
});

// Modal Detalle / Historial
const modalDetalleVisible = ref(false);
const cargandoObligaciones = ref(false);
const predioSeleccionado = ref(null);
const obligacionesPredio = ref([]);

// Modal Pago
const modalPagoVisible = ref(false);
const procesandoPago = ref(false);
const obligacionSeleccionada = ref(null);
const formPago = reactive({
  metodo_pago: 'TRANSFERENCIA',
  comprobante_ref: '',
  observaciones: ''
});

// Modal Recibo
const modalReciboVisible = ref(false);
const datosRecibo = ref(null);

const cargarDatos = async () => {
  cargando.value = true;
  try {
    const res = await axios.get('/api/alicuotas/resumen', headersConfig);
    if (res.data.success) {
      Object.assign(metricas, res.data.metricas);
      cartera.value = res.data.cartera;
    }
  } catch (error) {
    console.error('Error al cargar balance de alícuotas:', error);
  } finally {
    cargando.value = false;
  }
};

const carteraMorososCount = computed(() => {
  return cartera.value.filter(i => Number(i.deuda_total) > 0).length;
});

const carteraAlDiaCount = computed(() => {
  return cartera.value.filter(i => Number(i.deuda_total) === 0).length;
});

const carteraFiltrada = computed(() => {
  let list = cartera.value;

  if (filtroEstado.value === 'MOROSOS') {
    list = list.filter(i => Number(i.deuda_total) > 0);
  } else if (filtroEstado.value === 'AL_DIA') {
    list = list.filter(i => Number(i.deuda_total) === 0);
  }

  if (filtroBusqueda.value.trim() !== '') {
    const q = filtroBusqueda.value.toLowerCase().trim();
    list = list.filter(i => {
      const predio = `${i.inm_manzana_bloque} ${i.inm_numero_villa}`.toLowerCase();
      const titular = i.titular ? `${i.titular.per_nombres} ${i.titular.per_apellidos} ${i.titular.per_identificacion}`.toLowerCase() : '';
      return predio.includes(q) || titular.includes(q);
    });
  }

  return list;
});

const abrirModalEmision = () => {
  modalEmisionVisible.value = true;
};

const ejecutarEmisionMasiva = async () => {
  procesandoEmision.value = true;
  try {
    const res = await axios.post('/api/alicuotas/emision-masiva', formEmision, headersConfig);
    if (res.data.success) {
      alert(`✓ ${res.data.message}`);
      modalEmisionVisible.value = false;
      await cargarDatos();
    }
  } catch (error) {
    alert(error.response?.data?.message || 'Error al ejecutar emisión masiva');
  } finally {
    procesandoEmision.value = false;
  }
};

const abrirDetalleObligaciones = async (inm) => {
  predioSeleccionado.value = inm;
  modalDetalleVisible.value = true;
  cargandoObligaciones.value = true;
  try {
    const res = await axios.get(`/api/inmuebles/${inm.inm_id}/obligaciones`, headersConfig);
    if (res.data.success) {
      obligacionesPredio.value = res.data.data;
    }
  } catch (error) {
    console.error('Error cargando obligaciones:', error);
  } finally {
    cargandoObligaciones.value = false;
  }
};

const abrirModalRegistrarPago = (obl) => {
  obligacionSeleccionada.value = obl;
  formPago.metodo_pago = 'TRANSFERENCIA';
  formPago.comprobante_ref = '';
  formPago.observaciones = '';
  modalPagoVisible.value = true;
};

const guardarPagoCuota = async () => {
  if (!obligacionSeleccionada.value) return;
  procesandoPago.value = true;
  try {
    const res = await axios.post(
      `/api/inmuebles/${predioSeleccionado.value.inm_id}/obligaciones/${obligacionSeleccionada.value.obl_id}/pago`,
      formPago,
      headersConfig
    );
    if (res.data.success) {
      alert('✓ Pago registrado exitosamente.');
      modalPagoVisible.value = false;
      // Recargar obligaciones del predio y el balance global
      await abrirDetalleObligaciones(predioSeleccionado.value);
      await cargarDatos();
      // Abrir recibo automáticamente
      await verReciboOficial(obligacionSeleccionada.value.obl_id);
    }
  } catch (error) {
    alert(error.response?.data?.message || 'Error al procesar el pago.');
  } finally {
    procesandoPago.value = false;
  }
};

const verReciboOficial = async (oblId) => {
  try {
    const res = await axios.get(`/api/alicuotas/recibo/${oblId}`, headersConfig);
    if (res.data.success) {
      datosRecibo.value = res.data.recibo;
      modalReciboVisible.value = true;
    }
  } catch (error) {
    alert('No se pudo generar el comprobante del recibo.');
  }
};

const imprimirRecibo = () => {
  window.print();
};

const badgeClassEstado = (estado) => {
  if (estado === 'PAGADO') return 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30';
  if (estado === 'VENCIDO') return 'bg-rose-500/15 text-rose-400 border border-rose-500/30';
  return 'bg-amber-500/15 text-amber-300 border border-amber-500/30';
};

onMounted(() => {
  cargarDatos();
});
</script>

<style scoped>
.alicuotas-container {
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

.grid-kpi-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1rem;
}

.kpi-card {
  background: var(--bg-card);
  padding: 1.25rem;
  border-radius: 0.75rem;
  border: 1px solid var(--border-subtle);
  display: flex;
  align-items: center;
  gap: 1rem;
}

.kpi-icon-wrap {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.kpi-label {
  font-size: 0.75rem;
  color: var(--text-secondary);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.kpi-val {
  font-size: 1.5rem;
  font-weight: 800;
  font-family: ui-monospace, monospace;
}

.kpi-subtext {
  font-size: 0.7rem;
  color: var(--text-muted);
}

.filters-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  background: var(--bg-card);
  padding: 0.75rem 1rem;
  border-radius: 0.75rem;
  border: 1px solid var(--border-subtle);
}

.filter-pill-btn {
  background: var(--bg-surface);
  color: var(--text-secondary);
  border: 1px solid var(--border-subtle);
  padding: 0.4rem 0.85rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.filter-pill-btn:hover {
  background: var(--bg-hover);
  color: var(--text-primary);
}

.filter-pill-active {
  background: var(--color-primary);
  color: #ffffff;
  border-color: var(--color-primary);
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

.badge-tipo {
  display: inline-block;
  padding: 0.15rem 0.4rem;
  font-size: 0.65rem;
  font-weight: 700;
  border-radius: 4px;
  background: var(--bg-hover);
  color: var(--text-secondary);
}

.badge-estado {
  display: inline-block;
  padding: 0.15rem 0.4rem;
  font-size: 0.65rem;
  font-weight: 700;
  border-radius: 4px;
}

.btn-row-action {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.35rem 0.75rem;
  background: var(--bg-surface);
  color: var(--color-primary);
  border: 1px solid var(--border-subtle);
  border-radius: 0.5rem;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-row-action:hover {
  background: var(--bg-hover);
  border-color: var(--color-primary);
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
  z-index: 50;
  padding: 1rem;
}

.modal-content {
  width: 100%;
  background: var(--bg-card);
  border: 1px solid var(--border-subtle);
  border-radius: 0.75rem;
  overflow: hidden;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid var(--border-subtle);
  background: var(--bg-surface);
}

.modal-body {
  padding: 1.25rem;
}

.modal-footer {
  padding: 0.75rem 1.25rem;
  border-top: 1px solid var(--border-subtle);
  background: var(--bg-surface);
}

.checkbox-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
}

@media print {
  body * {
    visibility: hidden;
  }
  #recibo-imprimible, #recibo-imprimible * {
    visibility: visible;
  }
  #recibo-imprimible {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
  }
  .no-print {
    display: none !important;
  }
}
</style>
