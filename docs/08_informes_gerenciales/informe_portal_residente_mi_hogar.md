# 🏡 Manual de Usuario & Técnico — Portal del Residente ("Mi Hogar")
**Plataforma:** Residia CRM & Control Inteligente de Garitas (Robledal Connecting)  
**Versión:** 2.4.0-Portal  
**Fecha:** Octubre 2026  
**Módulo:** Autoservicio para Propietarios e Inquilinos  

---

## 1. Visión General del Portal "Mi Hogar"

El Portal del Residente es la interfaz orientada al habitante (accesible desde navegador web y aplicaciones móviles PWA). Ofrece autoservicio integral para:
1. **Transparencia Financiera**: Consulta en tiempo real de alícuotas mensuales, cuotas vencidas y recibos confirmados.
2. **Acceso Ágil de Visitas**: Creación instantánea de pases de invitación con código QR compartible por WhatsApp.
3. **Control Perimetral y Garita**: Notificación de paquetería entregada por couriers y bitácora de visitas que han ingresado a su predio.
4. **Flota Familiar**: Listado de vehículos enrolados y confirmación de tags RFID UHF para apertura automática.

---

## 2. Componentes de la Experiencia del Residente

### 2.1 Banner Consolidado de Alícuotas & Estado de Cuenta
Ubicado en la parte superior de la vista:
- **Semáforo Financiero**:
  - `🟢 AL DÍA`: Destaca que el inmueble no registra valores pendientes y disfruta de acceso expedito en garita.
  - `🟡 AVISO PREVENTIVO`: Alerta el número de cuotas pendientes y el saldo adeudado para evitar demoras o avisos en garita.
- **Acceso a Detalle de Cuotas**: Botón *"Ver Mis Cuotas & Recibos"* que despliega el historial desglosado con:
  - Período fiscal (`YYYY-MM`).
  - Glosa de mantenimiento.
  - Monto exigible en USD.
  - Estado del comprobante (`PAGADO` / `PENDIENTE`).

### 2.2 Generación de Pases QR para Visitas
- **Modal de Creación**:
  - Nombre del visitante, identificación y placa (opcional para vehículos).
  - Tipo de ingreso: `VEHICULAR` o `PEATONAL`.
  - Ventana de validez: Fecha y hora de inicio / vencimiento.
- **Acción Compartir**:
  - Botón directo para compartir el pase y token QR por **WhatsApp**.
  - Sincronización instantánea con la pantalla del guardia vía Server-Sent Events (SSE) sin recargar.

### 2.3 Paquetería en Custodia
- Muestra los envíos recibidos por el guardia en portería (`Servientrega`, `DHL`, `Amazon`, etc.).
- Permite al residente ver el estado: `EN_GARITA` (con contador de paquetes por retirar) o `ENTREGADO`.

### 2.4 Flota Familiar & Tags RFID
- Despliega las placas autorizadas de la familia.
- Muestra el tag UHF activo asignado al parabrisas para verificación técnica.
