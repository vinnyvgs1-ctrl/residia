# 📘 Manual Técnico & Operativo — Módulo Financiero Consolidado de Alícuotas & Tesorería
**Plataforma:** Residia CRM & Control Inteligente de Garitas (Robledal Connecting)  
**Versión:** 2.4.0-Fin  
**Fecha:** Octubre 2026  
**Módulo:** Tesorería, Cartera Vencida & Cobranzas Masivas  

---

## 1. Resumen Ejecutivo

El Módulo Financiero de Residia centraliza la recaudación de expensas, mantenimiento y alícuotas ordinarias/extraordinarias para urbanizaciones cerradas y condominios. Proporciona a la administración herramientas de emisión masiva en bloque, conciliación rápida de pagos y sincronización en tiempo real con las garitas de acceso vehicular y peatonal para la prevención de morosidad.

---

## 2. Arquitectura de Datos (PostgreSQL)

### Tabla Principal: `fin_obligaciones_alicuotas`
Almacena cada obligación financiera individual generada para un predio o villa.

| Campo | Tipo | Restricción / Descripción |
| :--- | :--- | :--- |
| `obl_id` | `BIGSERIAL` | Clave primaria autoincremental |
| `obl_inmueble_id` | `BIGINT` | FK referenciando a `core_inmuebles.inm_id` |
| `obl_periodo` | `VARCHAR(7)` | Período fiscal en formato `YYYY-MM` (ej. `2026-10`) |
| `obl_concepto` | `VARCHAR(150)` | Glosa descriptiva del cobro |
| `obl_monto` | `DECIMAL(10,2)`| Valor nominal exigible |
| `obl_fecha_emision` | `DATE` | Fecha de generación del cargo |
| `obl_fecha_vencimiento` | `DATE` | Límite de pago antes de incurrir en mora |
| `obl_estado` | `VARCHAR(20)` | `PENDIENTE`, `PAGADO`, `VENCIDO`, `ANULADO` |
| `obl_fecha_pago` | `TIMESTAMP` | Marca de tiempo al registrarse la recaudación |
| `obl_metodo_pago` | `VARCHAR(30)` | `TRANSFERENCIA`, `DEPOSITO`, `EFECTIVO`, `TARJETA` |
| `obl_comprobante_ref` | `VARCHAR(100)`| Número de transacción bancaria o comprobante |
| `obl_observaciones` | `TEXT` | Notas de conciliación contable |
| `created_at` / `updated_at` | `TIMESTAMP` | Marcas de auditoría del sistema |
| `deleted_at` | `TIMESTAMP` | Soft deletes para trazabilidad histórica |

---

## 3. Endpoints API REST

Todos los endpoints exigen autenticación JWT (`auth:api`) y cabecera de sede activa (`X-Urbanizacion-Id`).

### `GET /api/alicuotas/resumen`
Retorna el balance financiero global de la sede activa y el desglose de cartera predio por predio.

**Respuesta JSON:**
```json
{
  "success": true,
  "metricas": {
    "total_inmuebles": 5,
    "inmuebles_al_dia": 4,
    "inmuebles_morosos": 1,
    "total_recaudado": 50.00,
    "total_pendiente": 200.00,
    "porcentaje_al_dia": 80.0
  },
  "cartera": [
    {
      "inm_id": 2,
      "inm_manzana_bloque": "Manzana 01",
      "inm_numero_villa": "Villa 01",
      "inm_tipo": "CASA",
      "inm_estado": "HABITADO",
      "inm_alicuota_porcentaje": 100.00,
      "cuotas_pendientes": 0,
      "deuda_total": 0.00,
      "es_moroso": false,
      "titular": {
        "per_nombres": "Fernando",
        "per_apellidos": "García Moreno",
        "per_identificacion": "1710000001",
        "per_telefono": "0998881111",
        "res_tipo_relacion": "PROPIETARIO"
      }
    }
  ]
}
```

---

### `POST /api/alicuotas/emision-masiva`
Genera las obligaciones para todos los inmuebles de la urbanización en una sola transacción atómica con validación de idempotencia (evita duplicar cobros para un mismo predio en el mismo período/concepto).

**Payload:**
```json
{
  "periodo": "2026-10",
  "concepto": "Alícuota Ordinaria Mantenimiento Octubre 2026",
  "monto_base": 50.00,
  "fecha_vencimiento": "2026-10-31",
  "usar_ponderacion": false,
  "solo_habitados": true
}
```

---

### `POST /api/inmuebles/{id}/obligaciones/{oblId}/pago`
Registra el cobro de una cuota pendiente y actualiza el saldo deudor de la villa.

**Payload:**
```json
{
  "metodo_pago": "TRANSFERENCIA",
  "comprobante_ref": "TRX-99882233",
  "observaciones": "Conciliado con estado de cuenta Banco Pichincha"
}
```

---

### `GET /api/alicuotas/recibo/{id}`
Provee los datos consolidados para emitir el comprobante oficial imprimible con numeración correlativa (`REC-000001`). Incluye datos fiscales de la urbanización (RUC, dirección, teléfono), titular del predio y desglose del pago.

---

## 4. Integración Garita & Semáforo de Morosidad

Al ingresar un vehículo detectado por placa (LPR) o antena RFID UHF:
1. El backend evalúa en tiempo real si el inmueble destino tiene cuotas en estado `PENDIENTE` o `VENCIDO`.
2. Si la urbanización tiene activa la directiva `bloquear_morosos_garita` en sus reglas de sede:
   - El banner en garita transmuta a tono ámbar preventivo con aviso sonoro/visual:
   > **⚠️ AVISO GARITA: VILLA CON ALÍCUOTA PENDIENTE**  
   > *Predio: Manzana 01 - Villa 01 • Saldo: $50.00 (1 cuota/s) • Recordar Pago*
3. No bloquea el paso físico de forma arbitraria, pero alerta al guardia de turno para que notifique al residente o gestione el pago.

---

## 5. Formato de Comprobante Imprimible

El comprobante oficial de Residia cuenta con soporte nativo de estilos `@media print`:
- **Cabecera Institucional**: Nombre legal de la sede, RUC, dirección, ciudad y teléfono.
- **Folio Fiscal**: Numeración `REC-XXXXXX`.
- **Datos del Predio y Titular**: Manzana, villa, cédula del propietario.
- **Detalle de Obligación**: Concepto, período, referencia bancaria y total pagado en USD.
- **Botón Directo**: Ejecuta `window.print()` ocultando elementos de interfaz del CRM para impresión limpia en hojas A4 o comprobante térmico.
