# 🏛️ RESUMEN EJECUTIVO & MANUAL DE USUARIO INTEGRAL — RESIDIA CRM v2.0
**A la atención de:** Gerencia General, Directorio de Copropietarios & Comité de Administración  
**Organización:** Robledal Connecting  
**Plataforma:** Residia CRM Multi-Urbanizaciones SaaS & Control Inteligente de Garitas  
**Fecha de Publicación:** 27 de Octubre de 2026  
**Entorno de Acceso:** Producción Certificada con Certificado SSL Seguro  
**URL Oficial de la Plataforma:** [https://residia.robledal.co](https://residia.robledal.co)  

---

## 📌 PARTE I: RESUMEN EJECUTIVO PARA GERENCIA

### 1. Visión General de la Solución
**Residia CRM** es un ecosistema tecnológico unificado concebido para modernizar, blindar y rentabilizar la administración de comunidades residenciales privadas, conjuntos habitacionales y urbanizaciones cerradas de alta plusvalía.

La plataforma elimina por completo la dispersión operativa (libretas en papel de guardias, planillas Excel desvinculadas, recibos manuales y llamadas de cobranza desgastantes) conectando en tiempo real a cuatro actores clave:
1. **La Gerencia y el Comité de Administración:** A través de tableros de control con KPIs financieros, reportes oficiales y trazabilidad forense.
2. **El Residente y Copropietario:** Mediante la Progressive Web App (PWA) *"Mi Hogar"* instalable en smartphones para autogestión de visitas, pagos y amenidades.
3. **El Personal de Seguridad en Garita:** Mediante una consola táctil de alto rendimiento con validación vehicular y peatonal en menos de 2 segundos.
4. **Los Proveedores y Contratistas:** Con trazabilidad de servicios y pre-autorización directa en accesos.

---

### 2. Matriz de Retorno de Inversión (ROI) y Beneficios de Negocio

| Reto Administrativo Tradicional | Solución Implementada en Residia v2.0 | Impacto Operativo & Retorno de Inversión |
| :--- | :--- | :--- |
| **Colas de vehículos en garita en horas pico.** | Reconocimiento automático de matrícula (LPR) y lectura de Tags RFID UHF en parabrisas. | Ingreso de residentes autorizados en **menos de 2 segundos**. |
| **Morosidad recurrente en alícuotas.** | Emisión en bloque, semáforo preventivo en garita y estado de cuenta en el celular del habitante. | **Reducción de hasta un 35% en cartera vencida** y cobro no conflictivo. |
| **Falta de quórum en asambleas ordinarias.** | Votaciones electrónicas con cálculo de quórum en vivo y sufragio ponderado por alícuota (`%`). | Toma de decisiones jurídicamente respaldadas sin ausentismo. |
| **Daños imprevistos en bombas y generadores.** | Cronograma de mantenimiento preventivo y alertas automáticas de servicio próximo. | **Ahorro de hasta un 40%** en gastos imprevistos de reparación de emergencia. |
| **Disputas y falta de transparencia.** | Bitácora forense de auditoría CDC con visor de cambios (valor anterior en rojo vs. nuevo en verde). | Transparencia absoluta y blindaje legal para el Directorio. |
| **Costos elevados de apps en Google Play/App Store.** | Progressive Web App (PWA) instalable en 1-clic directamente desde el navegador web. | **Cero costos de licenciamiento o comisiones de tiendas**. |

---

### 3. Ficha Técnica de Despliegue en Servidor

- **Servidor Cloud:** VPS Dedicado Ubuntu Server 25 (Arquitectura de Alta Disponibilidad).
- **Motor Web & Cifrado:** Nginx 1.28 con Certificado SSL Let's Encrypt y forzado estricto HTTPS (`https://residia.robledal.co`).
- **Motor de Backend:** Laravel 11.57.0 sobre PHP 8.5 optimizado con OPcache y FPM.
- **Base de Datos:** PostgreSQL con extensiones criptográficas `uuid-ossp` y `pgcrypto`.
- **Caché en Memoria:** Redis Server 7.

---

## 📖 PARTE II: MANUAL DE USUARIO & GUÍA OPERATIVA

---

### 1. Acceso al Sistema y Roles de Usuario

1. Ingrese desde cualquier navegador (Google Chrome, Safari, Edge o Firefox) a:  
   👉 **[https://residia.robledal.co](https://residia.robledal.co)**
2. Ingrese sus credenciales asignadas:
   - **Administrador General:**  
     - Usuario: `admin@robledal.co`  
     - Contraseña: `Admin2026.*`
   - **Guardia de Turno:**  
     - Usuario: `guardia1@robledal.co`  
     - Contraseña: `Guardia2026.*`
3. Al ingresar, el sistema despliega el menú lateral adaptado según los permisos y el rol asignado (RBAC 5D).

---

### 2. Módulo de Garita Inteligente (Control Perimetral)

Este módulo es la herramienta principal del personal de seguridad en los accesos vehiculares y peatonales:

#### A. Ingreso Rápido por Placa o Tag RFID
1. Cuando un vehículo arriba al carril, el guardia digita la placa o el sistema la detecta vía cámara LPR o lector de tag UHF.
2. Si el vehículo pertenece a un residente autorizado, la pantalla se ilumina en **Verde**, muestra la foto del residente, villa asignada y abre la barrera.
3. El tiempo de validación promedio es inferior a **2 segundos**.

#### B. Canje de Pases Pre-autorizados con Código QR
1. Si arriba un visitante, técnico o taxi, el guardia hace clic en el botón **`[Leer QR]`** o ingresa el código numérico presentado por el visitante.
2. El sistema valida al instante si el pase está vigente y dentro de la ventana horaria autorizada por el residente.
3. Se presiona **`Registrar Ingreso`** y se emite el comprobante digital de entrada.

#### C. Detección Crítica de Lista Negra
- Si la persona o el vehículo ingresado registra una orden de veto o restricción legal en el sistema, la pantalla se tiñe inmediatamente de **Rojo Crítico**, emite un sonido de advertencia y **bloquea la apertura de barrera**, notificando al supervisor de turno.

#### D. Semáforo de Permanencia & Salidas
- El panel de garita cuenta con un cronómetro en tiempo real de todos los visitantes que se encuentran dentro del predio.
- Si un visitante supera el tiempo máximo fijado por la urbanización (ej. 120 minutos), la tarjeta pasa a color **Ámbar** o **Rojo**, indicando al guardia la necesidad de contactar a la villa o enviar una patrulla de ronda.
- Al salir, el guardia presiona **`Registrar Salida`** para cerrar el registro.

#### E. Apertura Manual de Contingencia
- Ante un fallo eléctrico de la barrera o una ambulancia de emergencia, el guardia dispone del botón **`Apertura de Emergencia`**.
- El sistema **obliga** a escribir el motivo de la apertura manual y lo graba de forma inalterable en la bitácora de auditoría forense para revisión de la Gerencia.

---

### 3. Portal del Residente ("Mi Hogar") & PWA Móvil

El portal para los habitantes no requiere instalar aplicaciones pesadas desde tiendas; funciona como una **Progressive Web App (PWA)** instalable con un clic.

#### A. Instalación en Celulares Android / iPhone
1. El residente abre `https://residia.robledal.co` en el navegador de su teléfono (Chrome o Safari).
2. Aparecerá un banner inferior: **`[Instalar Residia en Pantalla de Inicio]`**.
3. Se agrega un icono en el escritorio de su celular que abre la plataforma como una aplicación nativa.

#### B. Emisión y Envío de Pases QR por WhatsApp
1. El residente hace clic en **`+ Nueva Invitación`**.
2. Ingresa el nombre del visitante, su placa (opcional), fecha y horario de validez.
3. Presiona **`Generar Pase QR`** y luego el botón **`Compartir por WhatsApp`**.
4. El invitado recibe en su chat un enlace interactivo con el pase QR para presentarlo en la garita.

#### C. Estado Financiero en Vivo
- El residente visualiza en su pantalla principal su semáforo de pagos:
  - 🟢 **Al Día:** Sin valores pendientes.
  - 🟡 **Cuota por Vencer:** Muestra el valor de la alícuota del mes en curso.
  - 🔴 **Pendiente:** Muestra el detalle de meses adeudados con desglose.

#### D. Custodia de Paquetería (Encomiendas)
- Cuando un courier (Servientrega, DHL, Amazon) deja correspondencia en portería, el guardia la ingresa al sistema y el residente recibe una notificación push al celular indicando:  
  *«Paquete recibido en garita por el guardia [Nombre]»*.

---

### 4. Módulo Financiero, Tesorería & Alícuotas Masivas

Diseñado para que la administración liquide expensas y controle la cartera en minutos:

#### A. Emisión Masiva de Cuotas
1. Vaya a **Administración $\rightarrow$ Alícuotas**.
2. Presione el botón **`+ Emitir Cuotas Masivas`**.
3. Seleccione el **Período Fiscal** (ejemplo: `2026-10`) y el **Concepto** (*Alícuota Ordinaria*, *Fondo de Reserva*, *Seguridad*).
4. Elija el método de cálculo:
   - **Por Coeficiente Ponderado (%):** Aplica la fórmula sobre el metraje de cada villa.
   - **Valor Fijo Base:** Asigna el mismo valor a todas las unidades habitadas.
5. Presione **`Procesar Emisión`**. El sistema genera las obligaciones en bloque omitiendo predios ya facturados para evitar duplicidades.

#### B. Registro de Cobros y Recibos Imprimibles (`REC-XXXXXX`)
1. En la lista de cartera, ubique la villa correspondiente y haga clic en **`Registrar Pago`**.
2. Seleccione la fecha de cobro, método (*Transferencia*, *Depósito*, *Efectivo*, *Tarjeta*) y número de comprobante bancario.
3. El sistema marca la obligación como pagada y habilita el botón **`Ver Recibo`**.
4. Se abre el formato oficial institucional con membrete de Robledal Connecting y desglose fiscal, listo para imprimir o descargar en PDF (`window.print()`).

#### C. Semáforo Preventivo de Morosidad en Garita
- Si la urbanización activa la política de cobro en sus reglas de sede, cuando el vehículo de una villa con alícuotas vencidas ingresa a la garita, el guardia visualiza una alerta preventiva color ámbar para gestionar la recaudación sin entorpecer el libre tránsito.

---

### 5. Gestión de Áreas Sociales & Amenidades

Control de Casa Club, canchas de pádel, tenis, piscina, gimnasio y pérgolas BBQ:

1. **Catálogo de Espacios:** La administración configura el aforo máximo, costo por turno y valor del depósito en garantía.
2. **Calendario Interactivo:** Desde el Portal del Residente o la administración se selecciona la fecha y franja horaria deseada.
3. **Motor Anti-Solapamiento:** El sistema impide automáticamente que dos copropietarios reserven el mismo espacio en horarios cruzados.
4. **Candado de Solvencia:** Si una villa presenta deudas en alícuotas, el sistema bloquea la solicitud de áreas sociales hasta que regularice sus pagos.
5. **Pase Automático de Invitados:** La nómina de asistentes a un evento social se transmite automáticamente a la garita para permitirles paso expedito el día de la reserva.

---

### 6. Gobierno Corporativo: Asambleas, Votaciones & Circulares

Permite gestionar asambleas de copropietarios ordinarias y extraordinarias con validez digital:

1. **Circulares Oficiales:** Publicación de comunicados clasificados por urgencia (Seguridad, Cortes de Servicio, Asambleas) con adjuntos descargables en PDF.
2. **Urna Digital de Sufragio:** Creación de consultas vinculantes con opciones múltiples.
3. **Modalidades de Voto:**
   - **1 Voto por Predio:** Cada villa representa exactamente 1 sufragio.
   - **Voto Ponderado según Alícuota (%):** El voto de cada copropietario se pondera automáticamente por el porcentaje de copropiedad de su inmueble.
4. **Escrutinio y Quórum en Vivo:** Barras gráficas de porcentaje calculadas en tiempo real para verificar si la asamblea alcanza el quórum legal.

---

### 7. Facility Management & Mantenimiento Preventivo

Protege la infraestructura crítica de la urbanización contra paradas no programadas:

1. **Hojas de Vida de Activos:** Registro de bombas de agua hidroneumáticas, generadores diésel de emergencia, motores de portón y cerco eléctrico con su frecuencia de mantenimiento recomendada en días.
2. **Directorio de Proveedores Homologados:** Empresas de plomería, electricidad y seguridad registradas con RUC, contacto y especialidad técnica.
3. **Órdenes de Trabajo (`MNT-YYYY-XXXX`):** Registro de mantenimientos preventivos y correctivos, costos presupuestados vs. liquidados e informe técnico de entrega.
4. **Recálculo Automático del Próximo Servicio:** Al cerrarse una orden de mantenimiento, el sistema calcula de inmediato la fecha del siguiente servicio y notifica preventivamente a la administración.
5. **Acceso de Cuadrillas a Garita:** Los técnicos agendados en una orden de trabajo quedan pre-autorizados en garita automáticamente en la fecha prevista.

---

### 8. Padrón Único de Inmuebles, Vehículos y Residentes

1. **Ficha Integral de Inmueble (Padre, Hijos y Nietos):** Cada villa cuenta con su panel de control con 4 pestañas:
   - *Datos Físicos:* Metraje, manzana, villa y coeficiente de alícuota.
   - *Habitantes:* Propietarios, arrendatarios y dependientes vinculados al padrón de personas.
   - *Flota Vehicular:* Matrículas, marca, modelo y número de Tag RFID UHF para el parabrisas.
   - *Historial Financiero:* Todas las alícuotas emitidas, pagadas y adeudadas de ese predio.
2. **Filtros Avanzados:** Búsqueda instantánea por manzana, villa, nombre del titular o placa vehicular.

---

### 9. Auditoría Forense CDC & Reportes Oficiales

1. **Trazabilidad Inmutable (Change Data Capture):**
   - Cada inserción, modificación o eliminación queda registrada con fecha, hora, usuario ejecutor, dirección IP y el visor de diferencias comparativo (valor anterior en **rojo** vs. valor nuevo en **verde**).
2. **Centro de Reportes:**
   - Descarga de reportes en formato **Excel / CSV** con codificación internacional UTF-8 BOM (se abre en Excel sin caracteres extraños ni desconfiguración de tildes).
   - Impresión directa en formato membretado corporativo Robledal.
   - Permisos estrictos: Solo usuarios autorizados pueden exportar datos sensibles, garantizando el cumplimiento de la ley de protección de datos personales.

---

## 📞 PARTE III: CANALES DE ATENCIÓN Y CONTACTO

- **Soporte Tecnológico:** Dirección de Arquitectura & Sistemas • Robledal Connecting
- **Portal de Producción:** [https://residia.robledal.co](https://residia.robledal.co)
- **Correo Electrónico Oficial:** `admin@robledal.co`
- **Disponibilidad:** 24 horas al día, 7 días a la semana (Plataforma Cloud en Alta Disponibilidad).

---
*Documento institucional emitido y certificado por el Equipo de Desarrollo de Robledal Connecting.*
