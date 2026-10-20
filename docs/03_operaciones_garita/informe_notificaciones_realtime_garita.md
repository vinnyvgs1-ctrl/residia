# ⚡ Manual Técnico — Notificaciones en Tiempo Real (SSE & Garita)
**Plataforma:** Residia CRM & Control Inteligente de Garitas (Robledal Connecting)  
**Versión:** 2.4.0-Realtime  
**Fecha:** Octubre 2026  
**Módulo:** Notificaciones Instantáneas SSE & Chime Sonoro de Garita  

---

## 1. Arquitectura de Notificaciones en Tiempo Real

El sistema implementa un canal persistente bidireccional-ligero basado en **Server-Sent Events (SSE)** con `Content-Type: text/event-stream`, eliminando la sobrecarga y latencia del sondeo convencional por intervalos sin requerir servicios externos de terceros (como Pusher o Firebase).

```
[ App Móvil / Portal Residente ] 
               │
               ▼ (POST /api/residente/pases-qr)
     [ PostgreSQL Database ]
               │
               ▼ (STREAM /api/notificaciones/stream)
     [ Controller SSE Streamer ]
               │
               ▼ (EventSource Browser API)
   [ Pantalla de Garita (GaritaView.vue) ]
      ├── 🔔 Chime Musical Web Audio API (784Hz -> 1046Hz)
      ├── 🎫 Toast Animado Verde Esmeralda (Slide-Down)
      └── 🔄 Refresco Instantáneo de la Tabla de Pases QR
```

---

## 2. Eventos Transmitidos en el Stream

### Evento A: `NUEVO_PASE_QR`
- **Disparador:** Un habitante genera una invitación para un visitante, delivery o contratista desde su portal o app móvil.
- **Payload:**
```json
{
  "tipo": "NUEVO_PASE_QR",
  "titulo": "🎫 Nuevo Pase QR de Residente",
  "mensaje": "El habitante de Manzana 01 - Villa 01 autorizó el ingreso de Carlos Echeverría (Vehículo PBC-8812)",
  "datos": {
    "inv_id": 14,
    "inv_visitante_nombre": "Carlos Echeverría",
    "inv_visitante_placa": "PBC-8812",
    "inm_manzana_bloque": "Manzana 01",
    "inm_numero_villa": "Villa 01"
  },
  "timestamp": "2026-10-19T21:02:11-05:00"
}
```
- **Respuesta en Garita:**
  - Se reproduce una señal audible de 2 tonos en frecuencia melódica (Sol5 ➔ Do6).
  - Se despliega un toast superior en gradiente esmeralda.
  - La grilla de pases vigentes se refresca automáticamente sin recargar la página.

### Evento B: `EXCESO_PERMANENCIA`
- **Disparador:** Una visita registrada dentro de la urbanización sobrepasa los minutos configurados en las reglas de sede (`tiempo_maximo_visita_minutos`).
- **Respuesta en Garita:**
  - Dispara el oscilador sonoro de alerta (`sawtooth` 587Hz ➔ 880Hz).
  - Activa el banner amarillo titilante con el listado de vehículos y placas excedidas.

---

## 3. Síntesis Sonora en el Navegador (Web Audio API)

Para garantizar funcionamiento sin archivos MP3 externos ni latencia de red, la garita cuenta con un sintetizador de audio nativo:
- **Alerta de Infracción / Exceso:** Oscilador de onda de sierra (`sawtooth`) con salto tonal a 880Hz.
- **Chime Melodioso de Notificación:** Oscilador de onda senoidal (`sine`) pura con caída exponencial de ganancia para un tono agradable y distinguible para el guardia.
