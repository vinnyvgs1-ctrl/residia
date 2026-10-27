# 📚 Índice Maestro de Documentación — Residia CRM (Robledal Connecting)
**Plataforma:** Residia CRM Multi-Urbanizaciones SaaS & Control Inteligente de Garitas  
**Fecha de Actualización:** 19 de Octubre de 2026  
**Versión:** 2.4.0 (Enterprise Ready)  

Bienvenido a la biblioteca central de documentación técnica, operacional y estratégica de **Residia**. A continuación se detalla la estructura organizada de carpetas y el contenido de cada documento disponible:

---

## 🗂️ Estructura del Directorio `docs/`

```
residia/docs/
├── README.md                                           <-- Este índice maestro
├── 01_arquitectura/                                    <-- Fundamentos, diagramas y base de datos
│   ├── 01_plan_maestro_arquitectura.md                 # Arquitectura global multi-tenant y micro-módulos
│   ├── 02_party_model_personas_vs_users.md             # Padrón Party Model: core_personas vs sec_users
│   └── 03_estandares_prefijos_base_datos.md            # Convenciones de nomenclatura y esquemas PostgreSQL
├── 02_seguridad_y_shielding/                           <-- Criptografía y protección de endpoints
│   └── 01_api_shielding_hmac_redis.md                  # Blindaje HMAC SHA-256, cabeceras y control anti-tamper
├── 03_operaciones_garita/                              <-- Control perimetral, tiempo real e IoT
│   ├── 01_flujo_control_accesos_y_permanencia.md       # Diagrama de flujo de visitas y cálculo de tiempos
│   ├── informe_notificaciones_realtime_garita.md       # Streaming Server-Sent Events (SSE) y chime Web Audio
│   └── informe_hardware_iot_lpr_rfid_garita.md         # Cámaras LPR Hikvision, tags RFID UHF y apertura manual
├── 04_theming_robledal/                                <-- Identidad visual y experiencia de usuario
│   └── 01_paleta_robledal_y_css_variables.md           # Tokens de diseño, dark mode esmeralda y accesibilidad
├── 05_bitacora_de_cambios/                             <-- Registro histórico CDC
│   └── 01_registro_de_instalacion_y_proceso.md         # Bitácora cronológica de despliegues y migraciones
├── 06_assets_marca/                                    <-- Logotipos e imagotipos
├── 07_pendientes_y_auditoria_seguridad/                <-- Certificaciones, auditoría y hoja de ruta
│   ├── 00_matriz_general_de_pendientes_y_roadmap.md    # Matriz inicial de requerimientos
│   ├── 01_evaluacion_blindaje_y_readiness_ethical_hacking.md # Análisis de vulnerabilidades y hardening
│   ├── 02_informe_remediacion_y_blindaje_total.md      # Certificado de mitigación de brechas
│   ├── 03_proximas_fases_y_pendientes_tecnicos.md      # Backlog y pendientes v2.1+ (testing, pagos, SRI, bundles)
│   └── pendientes_y_hoja_de_ruta_residia.md            # Hoja de ruta 100% completada y cerrada
├── 08_informes_gerenciales/                            <-- Manuales de usuario y módulos de negocio
│   ├── resumen_ejecutivo_y_manual_usuario_gerencia.md  # 🌟 Documento Maestro para Gerencia: Resumen y Manual de Usuario
│   ├── informe_ejecutivo_gerencia_residia.md           # Visión general para el comité de administración
│   ├── informe_modulo_financiero_alicuotas.md          # Tesorería, emisión masiva y recibos imprimibles
│   └── informe_portal_residente_mi_hogar.md            # Autoservicio de habitantes, pases QR y alícuotas
└── 09_despliegue_produccion/                           <-- Manuales de despliegue y puesta en marcha
    ├── guia_limpieza_y_reinicio_base_datos.md          # 🧹 Guía de Limpieza y Reinicio de Datos (Artisan/SQL)
    ├── guia_instalacion_paso_a_paso_ubuntu25_php84.md  # Guía paso a paso Ubuntu Server 25 + PHP 8.5
    └── manual_despliegue_produccion.md                 # Manual de operaciones y arquitectura cloud
```

---

## 📌 Guía Rápida por Módulo Implementado

### 1. Módulo Financiero de Alícuotas & Cartera Consolidada
- **Ubicación:** `docs/08_informes_gerenciales/informe_modulo_financiero_alicuotas.md`
- **Contenido Clave:**
  - Modelo de datos `fin_obligaciones_alicuotas`.
  - Algoritmo de emisión en bloque por cuota base o ponderada (`%`).
  - Semáforo preventivo en garita (`bloquear_morosos_garita`).
  - Sistema de recibos oficiales con numeración `REC-XXXXXX` y soporte de impresión directa (`window.print()`).

### 2. Notificaciones en Tiempo Real (SSE & Garita)
- **Ubicación:** `docs/03_operaciones_garita/informe_notificaciones_realtime_garita.md`
- **Contenido Clave:**
  - Canal persistente de streaming `text/event-stream` sin servicios externos de pago.
  - Evento instantáneo `NUEVO_PASE_QR` al generar autorizaciones desde el móvil.
  - Chime melódico ascendente (784Hz ➔ 1046Hz) vía Web Audio API nativo.
  - Notificación push ante exceso de permanencia vehicular.

### 3. Integración de Hardware Perimetral (IoT, LPR & RFID)
- **Ubicación:** `docs/03_operaciones_garita/informe_hardware_iot_lpr_rfid_garita.md`
- **Contenido Clave:**
  - Conexión con cámaras LPR (Hikvision ISAPI / ANPR) y antenas RFID UHF.
  - Apertura automática de barreras vehiculares para residentes y visitantes con invitación válida.
  - Detección inmediata de vehículos en Lista Negra (`HTTP 403`).
  - Banco de pruebas interactivo en garita y modo contingencia con registro inmutable CDC (`acc_aperturas_contingencia`).

### 4. Portal del Residente ("Mi Hogar")
- **Ubicación:** `docs/08_informes_gerenciales/informe_portal_residente_mi_hogar.md`
- **Contenido Clave:**
  - Consulta directa de estado de cuenta y cuotas adeudadas.
  - Generación de pases QR temporales con enlace directo para compartir por WhatsApp.
  - Seguimiento de paquetería entregada por couriers en portería.
  - Padrón familiar y tags RFID UHF de la flota vehicular del hogar.
