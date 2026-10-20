# 📟 Manual Técnico — Integración de Hardware IoT & Control de Garita (LPR, RFID & Contingencia)
**Plataforma:** Residia CRM & Control Inteligente de Garitas (Robledal Connecting)  
**Versión:** 2.4.0-IoT  
**Fecha:** Octubre 2026  
**Módulo:** Automatización de Barreras Perimetrales & Contingencia Auditada  

---

## 1. Arquitectura de Integración Perimetral

El subsistema de Hardware de Residia permite orquestar de forma centralizada cámaras ANPR/LPR (Lectura Automática de Matrículas) y antenas RFID UHF de largo alcance con los controladores de relé que accionan las barreras vehiculares y torniquetes peatonales.

```
       [ Dispositivos en Carril ]
        ├── 📷 Cámara LPR (Hikvision ISAPI / HTTP Webhook)
        ├── 📡 Antena RFID UHF (ZKTeco ProRF-UHF 900MHz)
        └── 🎛️ Controlador de Relé (Barrier IP Relay)
                        │
                        ▼
         [ Endpoints IoT Residia API ]
      ├── POST /api/hardware/lpr-deteccion
      ├── POST /api/hardware/rfid-deteccion
      └── POST /api/hardware/apertura-contingencia
                        │
                        ▼
      [ PostgreSQL CDC & Bitácora Inmutable ]
```

---

## 2. Lógica de Decisión LPR (`POST /api/hardware/lpr-deteccion`)

Cuando la cámara LPR detecta un vehículo en el carril:
1. **Filtro de Lista Negra (Veto Inmediato):** Evalúa si la matrícula está registrada en `op_lista_negra`. Si está vetada, deniega el paso (`HTTP 403`), no activa el relé y registra la alerta crítica en la bitácora CDC con el motivo del veto.
2. **Vehículo de Residente:** Busca coincidencia en `core_inmueble_vehiculos`. Si el vehículo está activo:
   - Valida el estado de cuenta y alícuotas del predio.
   - Si no hay impedimentos severos, emite pulso de apertura inmediata (`apertura: true`).
   - Inserta el registro de ingreso en `op_control_accesos` con tipo `RESIDENTE`.
3. **Visitante con Pase QR Activo:** Si no es residente, busca invitaciones vigentes en `op_invitaciones_qr` para esa placa.
   - Si existe un pase activo en ventana de tiempo válida, abre la barrera, descuenta el uso permitido y registra el ingreso en `op_control_accesos` con tipo `VISITA`.
4. **Vehículo No Enrolado:** Retorna `apertura: false` notificando a la pantalla del guardia para inspección manual y solicitud de cédula.

---

## 3. Lógica Antenas RFID UHF (`POST /api/hardware/rfid-deteccion`)

Lectura instantánea de Tags UHF pegados al parabrisas:
- Consulta en `core_inmueble_vehiculos` por `veh_tag_rfid`.
- Al confirmar coincidencia, abre la barrera sin detención del vehículo (paso expedito) y audita el evento con el ID de la antena.

---

## 4. Modo Contingencia & Apertura Forzada Manual (`POST /api/hardware/apertura-contingencia`)

Para eventos de emergencia o fallos de infraestructura:
- **Campos Obligatorios:** Carril, motivo detallado (mínimo 5 caracteres).
- **Campos Opcionales:** Placa vehicular, inmueble destino.
- **Trazabilidad CDC:** Inserta el registro en `acc_aperturas_contingencia` vinculando la firma digital del guardia autenticado, fecha/hora exacta y dispara el registro CDC en `AuditLogger`.
