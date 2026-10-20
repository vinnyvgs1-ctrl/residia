# Matriz General de Pendientes & Roadmap de Desarrollo
**Proyecto:** RESIDIA CRM Multi-Urbanizaciones & Garita Inteligente  
**Organización:** Robledal Connecting  
**Fecha de Actualización:** 2026-10-08  
**Estado General del Sistema:** 100% Núcleo Operativo Completado · Núcleo y Seguridad Blindados al 100%

---

## 1. Módulos y Funcionalidades Completadas al 100%

- [x] **Arquitectura Base & Multi-Tenant:** PostgreSQL 16 + Laravel 11.57.0 (PHP 8.2), aislamiento por UUID de urbanización.
- [x] **Party Model:** Separación arquitectónica estricta entre `core_personas` (padrón físico) y `sec_users` (cuentas de acceso).
- [x] **Motor de Temas Robledal:** Menta Tecnológica (`#72C1A5`), Carbón Mate (`#323637`) y Minimal Claro (`#F8FAFB`), inyectados dinámicamente mediante variables CSS desde `sec_temas`.
- [x] **UX & 100% Responsive:** Menús desplegables fluidos, vista de ancho completo (`100% width`), drawer táctil en móvil y selector de tema minimalista con tooltip.
- [x] **Dashboard Ejecutivo en Tiempo Real:** KPIs de accesos, histograma de picos de tráfico horario por intervalos de 2h y panel de salud del padrón.
- [x] **Operación Garita Inteligente:**
  - [x] Detección automática por Placa / Tag RFID de residentes (autocompletado inmediato y paso expedito en <2s).
  - [x] Banner de Alerta Crítica Roja de Lista Negra en tiempo real ante cédula o placa vetada con bloqueo de barrera.
  - [x] Pases Pre-autorizados con Códigos QR: generación, lector óptico y canje de acceso instantáneo.
  - [x] Control de permanencias activas y cálculo de duración en minutos al registrar salidas.
- [x] **Administración & Catálogos:**
  - [x] Urbanizaciones con Motor de Reglas de Sede (tiempos máximos, tolerancias de gracia, políticas de acompañantes).
  - [x] Inmuebles (Villas, Casas, Deptos) y asignación de habitantes.
  - [x] Flota Vehicular con asignación de Tags RFID y Stickers LPR por vivienda.
- [x] **Centro de Reportes Oficial:**
  - [x] Informe oficial para Impresión / PDF con membrete institucional de Robledal Connecting.
  - [x] Descarga de Excel/CSV en Streaming con codificación BOM UTF-8.
  - [x] Validación obligatoria del permiso `print` y registro en `aud_reportes_descargas`.
- [x] **Auditoría Forense CDC:** Bitácora transaccional con visor interactivo de diffs JSON lado a lado (rojo anterior vs verde nuevo).
- [x] **Blindaje & Ethical Hacking Ready (100%):**
  - [x] Rate limiting anti-fuerza bruta en login (`429 Too Many Attempts` a los 5 intentos).
  - [x] Invalidación y revocación forzada de tokens JWT en logout.
  - [x] Inyección global de cabeceras de seguridad (`X-Frame-Options: SAMEORIGIN`, `nosniff`, `Referrer-Policy`, etc.).
  - [x] Verificación anti-IDOR estricta a nivel de tenant en controladores.
  - [x] Mitigación de Replay Attacks con Nonces atómicos en Redis y HMAC-SHA256 (`ApiShieldMiddleware`).
- [x] **Aislamiento de Cuentas de Guardias (Punto 1):** Cuenta de guardia aprovisionada (`guardia1@robledal.com`) con rol `guardia_garita` y menú restringido estrictamente a Garita, Dashboard y Reportes.

---

## 2. Matriz de Módulos Implementados & Certificados

A continuación se detalla el estado final de todos los componentes del sistema:

| # | Prioridad | Módulo / Característica | Descripción del Pendiente | Estado |
| :-: | :---: | :--- | :--- | :---: |
| **1** | **COMPLETADO** | **Cuentas de Guardias & Operadores** | Cuenta de guardia creada y validación de aislamiento de menús mediante `sec_rol_vistas`. | **Hecho ✓** |
| **2** | **COMPLETADO** | **Portal del Residente ("Mi Hogar")** | Pantalla exclusiva de habitantes: generación de pases QR por WhatsApp, alícuotas en tiempo real, paquetería y vehículos. | **Hecho ✓** |
| **3** | **COMPLETADO** | **Notificaciones Web & Alertas Sonoras de Permanencia** | Canal SSE en tiempo real (`/api/notificaciones/stream`), chime melódico Web Audio API y detección de permanencia. | **Hecho ✓** |
| **4** | **COMPLETADO** | **Gestión Multi-Garita por Sede** | CRUD de puestos de garita, selector de puesto activo e imputación de carriles vehiculares y peatonales. | **Hecho ✓** |
| **5** | **COMPLETADO** | **Hardware IoT & Biometría Hikvision ISAPI** | Integración LPR Hikvision, antenas RFID UHF, sincronización masiva ISAPI de rostros/huellas y plantillas iVMS-4200. | **Hecho ✓** |
| **6** | **COMPLETADO** | **Tesorería & Alícuotas Masivas** | Cobros masivos, semáforo de solvencia en garita y recibos imprimibles oficiales `REC-XXXXXX`. | **Hecho ✓** |
| **7** | **COMPLETADO** | **Reservas de Áreas Sociales & Amenidades** | Calendario de Casa Club, canchas de pádel, BBQ con aforos y candado de solvencia de alícuotas. | **Hecho ✓** |
| **8** | **COMPLETADO** | **Comunicados Oficiales & Asambleas Digitales** | Circulares prioritarias, votaciones electrónicas (voto ponderado o 1 voto x predio) y quórum en vivo. | **Hecho ✓** |
| **9** | **COMPLETADO** | **Despliegue PWA Móvil & Notificaciones Push** | Manifiesto `manifest.json`, Service Worker offline, instalación 1-clic y notificaciones push al celular. | **Hecho ✓** |

---

## 3. Documentación Asociada en la Carpeta `docs/`

- 📁 [docs/07_pendientes_y_auditoria_seguridad/](file:///Applications/XAMPP/xamppfiles/htdocs/residia/docs/07_pendientes_y_auditoria_seguridad):
  - [00_matriz_general_de_pendientes_y_roadmap.md](file:///Applications/XAMPP/xamppfiles/htdocs/residia/docs/07_pendientes_y_auditoria_seguridad/00_matriz_general_de_pendientes_y_roadmap.md) $\rightarrow$ **Este documento maestro de pendientes**.
  - [01_evaluacion_blindaje_y_readiness_ethical_hacking.md](file:///Applications/XAMPP/xamppfiles/htdocs/residia/docs/07_pendientes_y_auditoria_seguridad/01_evaluacion_blindaje_y_readiness_ethical_hacking.md) $\rightarrow$ Evaluación de riesgos preliminares.
  - [02_informe_remediacion_y_blindaje_total.md](file:///Applications/XAMPP/xamppfiles/htdocs/residia/docs/07_pendientes_y_auditoria_seguridad/02_informe_remediacion_y_blindaje_total.md) $\rightarrow$ Evidencias del blindaje perimetral al 100%.
- 📄 [docs/05_bitacora_de_cambios/01_registro_de_instalacion_y_proceso.md](file:///Applications/XAMPP/xamppfiles/htdocs/residia/docs/05_bitacora_de_cambios/01_registro_de_instalacion_y_proceso.md) $\rightarrow$ Registro de los 23 pasos completados.
