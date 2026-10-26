# 📋 Matriz de Pendientes Técnicos & Próximas Fases (Residia v2.1+)
**Proyecto:** Residia CRM Multi-Urbanizaciones SaaS & Control Inteligente de Garitas  
**Organización:** Robledal Connecting  
**Fecha de Registro:** 26 de Octubre de 2026  
**Estado General:** Release v2.0 Gold Master Operativo en Producción  

---

## 📌 Resumen de Observaciones y Líneas de Trabajo

A continuación se formalizan los requerimientos técnicos y mejoras detectadas durante la auditoría de la versión 2.0, priorizadas para su ejecución en las siguientes iteraciones:

| ID | Prioridad | Módulo / Componente | Descripción del Pendiente | Impacto Esperado |
| :---: | :---: | :--- | :--- | :--- |
| **PEN-00** | **INMEDIATA** | **Despliegue en Servidor VPS (Ubuntu 25 + PHP 8.4)** | Puesta en marcha en VPS de producción desde cero: instalación de PHP 8.4-FPM con todas sus extensiones, Nginx, PostgreSQL (16/17), Redis, Node.js 22 LTS, restauración del snapshot maestro SQL, SSL Let's Encrypt y worker de colas. Ver guía: [`guia_instalacion_paso_a_paso_ubuntu25_php84.md`](file:///Applications/XAMPP/xamppfiles/htdocs/residia/docs/09_despliegue_produccion/guia_instalacion_paso_a_paso_ubuntu25_php84.md). | Salida a producción real del sistema. |
| **PEN-01** | **ALTA** | **Migraciones Formales de Base de Datos** | Desarrollar los archivos individuales de migración en `database/migrations/` para todas las tablas introducidas en la v2.0 (`fin_obligaciones_alicuotas`, `op_activos_infraestructura`, `op_areas_sociales`, `op_biometria_residentes`, etc.), asegurando reproducibilidad completa mediante `php artisan migrate:fresh --seed`. | Mantenibilidad, CI/CD y portabilidad limpia de entornos. |
| **PEN-02** | **ALTA** | **Suite de Pruebas Automatizadas (PHPUnit)** | Diseñar e implementar pruebas de integración (Feature Tests) y unitarias para los flujos críticos: flujo de garita (LPR, RFID, canje QR), cálculo y bloqueo de alícuotas, API Shielding (HMAC SHA-256) y motor anti-solapamiento de áreas sociales. | Calidad de software, prevención de regresiones en updates. |
| **PEN-03** | **ALTA** | **Pasarela de Pagos Digitales ("Mi Hogar")** | Integrar botón de pago en línea (PayPhone, Kushki, Stripe o Datafast) en el Portal del Residente para cobro automático de alícuotas con conciliación bancaria instantánea mediante Webhooks. | Reducción de mora, mayor agilidad y cero validación manual. |
| **PEN-04** | **MEDIA** | **Integración Contable & SRI (Contabit)** | Desarrollar adaptador / conector API con el sistema contable **Contabit** para la emisión automática de comprobantes electrónicos autorizados por el SRI y generación de asientos contables por cobro de expensas. | Cumplimiento tributario ecuatoriano y automatización contable. |
| **PEN-05** | **MEDIA** | **Optimización de Bundle & Code-Splitting** | Implementar carga diferida (*lazy loading*) con `defineAsyncComponent` en las vistas de mayor peso (`GaritaView.vue`, `InmueblesView.vue`, `SeguridadView.vue`) para reducir el bundle JS inicial de 600 kB a menos de 150 kB. | Carga ultra-rápida de la PWA en dispositivos móviles con señal 3G/4G débil. |

---

## 🛠️ Detalle de Implementación por Pendiente

### PEN-00: Despliegue en Servidor VPS (Ubuntu Server 25 & PHP 8.4+)
- **Contexto:** El servidor VPS cuenta con una instalación limpia de Ubuntu Server 25 y requiere aprovisionamiento desde cero.
- **Documento Maestro:** [`docs/09_despliegue_produccion/guia_instalacion_paso_a_paso_ubuntu25_php84.md`](file:///Applications/XAMPP/xamppfiles/htdocs/residia/docs/09_despliegue_produccion/guia_instalacion_paso_a_paso_ubuntu25_php84.md).
- **Herramientas de Apoyo:**
  - Script automatizado: [`scripts/install/setup_vps_ubuntu25.sh`](file:///Applications/XAMPP/xamppfiles/htdocs/residia/scripts/install/setup_vps_ubuntu25.sh)
  - Configuración Nginx: [`scripts/install/nginx_residia.conf`](file:///Applications/XAMPP/xamppfiles/htdocs/residia/scripts/install/nginx_residia.conf)
- **Estado Actual:** En espera de que el administrador disponga de credenciales / acceso SSH al VPS.

### PEN-01: Migraciones Formales de Laravel (DB)
- **Contexto:** Actualmente la base de datos se inicializa con el snapshot maestro SQL (`database/backups/residia_release_v2_completo.sql`).
- **Entregables:**
  - Migración `create_fin_obligaciones_alicuotas_table`.
  - Migración `create_op_areas_sociales_y_reservas_tables`.
  - Migración `create_op_activos_y_ordenes_mantenimiento_tables`.
  - Migración `create_op_biometria_tables`.
  - Migración `create_op_votaciones_y_votos_tables`.
  - Migración `create_op_lista_negra_table`.
  - Migración `create_op_push_subscriptions_table`.

### PEN-02: Suite de Pruebas Automatizadas (PHPUnit)
- **Entregables:**
  - `GaritaOperacionTest.php`: Simulación de ingreso LPR, bloqueo por Lista Negra, validación QR y registro CDC de apertura de contingencia.
  - `AlicuotasCalculoTest.php`: Emisión masiva ponderada, cálculo de mora y semáforo preventivo.
  - `ApiShieldingTest.php`: Replay attack con nonce duplicado, firma HMAC inválida y rate-limiting.
  - `AreasSocialesTest.php`: Validación de solapamiento de horarios y bloqueo a predios morosos.

### PEN-03: Pasarela de Pagos Digitales
- **Entregables:**
  - Controlador `PagoEnLineaController.php` con endpoints para inicializar sesión de pago y recepción de Webhook bancario.
  - Componente de pago en `ResidentePortalView.vue`.
  - Emisión y marcado automático de obligación como `PAGADO` con su recibo formal.

### PEN-04: Conector Contable Contabit
- **Entregables:**
  - Servicio `ContabitClientService.php`.
  - Mapeo de cuentas contables por concepto de alícuota / fondo de reserva.
  - Generación de XML firmado y autorización SRI en línea.

### PEN-05: Optimización de Frontend (Vite & Vue 3)
- **Entregables:**
  - Refactor de `App.vue` con `defineAsyncComponent` y suspense/fallback skeletons.
  - Configuración de `rollupOptions.output.manualChunks` en `vite.config.js` para separar dependencias de vendor (Lucide, Axios, Pinia).
