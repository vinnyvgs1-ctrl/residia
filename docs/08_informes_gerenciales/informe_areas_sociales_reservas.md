# 🏆 Manual Técnico & Operativo — Módulo de Reservas de Áreas Sociales & Amenidades
**Plataforma:** Residia CRM & Control Inteligente de Garitas (Robledal Connecting)  
**Versión:** 2.4.0-Amenities  
**Fecha:** Octubre 2026  
**Módulo:** Reservas Comunitarias, Aforos, Solvencia Financiera & Control de Garita  

---

## 1. Visión General del Módulo

El Módulo de Áreas Sociales y Amenidades permite a las administraciones de urbanizaciones cerradas parametrizar sus espacios comunes (canchas deportivas, casas club, áreas BBQ, piscinas) y habilita a los residentes para consultar disponibilidad y agendar turnos de uso de forma transparente y automatizada.

---

## 2. Esquema de Base de Datos (PostgreSQL)

### Tabla `op_areas_sociales`
Catálogo de espacios comunes y reglas de reserva por urbanización.

| Campo | Tipo | Restricción / Descripción |
| :--- | :--- | :--- |
| `are_id` | `BIGSERIAL` | Clave primaria |
| `are_urbanizacion_id`| `UUID` | Sede asociada |
| `are_nombre` | `VARCHAR(100)` | Nombre del área (ej. *Cancha de Pádel Cristal*) |
| `are_tipo` | `VARCHAR(50)` | `PADEL`, `BBQ`, `CASA_CLUB`, `PISCINA`, `GIMNASIO` |
| `are_aforo_maximo` | `INTEGER` | Capacidad máxima permitida de personas |
| `are_costo_reserva` | `DECIMAL(10,2)`| Tarifa por turno en USD ($0 si es gratuito) |
| `are_garantia_deposito`| `DECIMAL(10,2)`| Valor de garantía reembolsable |
| `are_hora_apertura` | `TIME` | Horario inicial de funcionamiento |
| `are_hora_cierre` | `TIME` | Horario límite de funcionamiento |
| `are_duracion_turno_horas` | `INTEGER` | Duración del bloque estándar de reserva |
| `are_requiere_aprobacion` | `BOOLEAN` | Define si requiere visto bueno administrativo |

### Tabla `op_reservas_amenidades`
Registro transaccional de reservas programadas.

| Campo | Tipo | Restricción / Descripción |
| :--- | :--- | :--- |
| `res_id` | `BIGSERIAL` | Clave primaria |
| `res_urbanizacion_id`| `UUID` | Sede asociada |
| `res_area_id` | `BIGINT` | FK referenciando a `op_areas_sociales.are_id` |
| `res_inmueble_id` | `BIGINT` | Predio o villa solicitante |
| `res_persona_id` | `BIGINT` | Residente titular de la reserva |
| `res_fecha` | `DATE` | Fecha programada |
| `res_hora_inicio` | `TIME` | Hora inicio del turno |
| `res_hora_fin` | `TIME` | Hora fin del turno |
| `res_cantidad_invitados`| `INTEGER` | Cantidad declarada de asistentes |
| `res_monto_pagado` | `DECIMAL(10,2)`| Costo cancelado |
| `res_estado` | `VARCHAR(20)` | `CONFIRMADA`, `PENDIENTE_PAGO`, `CANCELADA` |
| `res_lista_invitados` | `JSONB` | Lista de personas y placas pre-autorizadas en garita |

---

## 3. Endpoints REST API

- `GET /api/areas-sociales`: Catálogo de amenidades y especificaciones de aforo de la sede.
- `POST /api/areas-sociales`: Alta de nuevas áreas comunes en la urbanización.
- `GET /api/areas-sociales/reservas`: Agenda de reservas vigentes y pasadas.
- `POST /api/areas-sociales/reservas`: Generación de reserva con **doble candado de seguridad**:
  1. **Candado de Solvencia:** Si la villa registra cuotas de alícuota en mora (`fin_obligaciones_alicuotas`), la API rechaza la reserva (`HTTP 422`).
  2. **Candado Anti-Solapamiento:** Detección de colisiones de horario para el mismo espacio (`HTTP 409 Conflict`).
- `POST /api/areas-sociales/reservas/{id}/cancelar`: Anulación de la reserva y liberación del cupo.

---

## 4. Experiencia de Usuario & Vistas Frontend

- **Vista Administrativa (`AreasSocialesView.vue`)**:
  - Ubicada en el menú principal: **Administración ➔ Áreas Sociales & Reservas**.
  - Pestaña de **Catálogo Visual**: Muestra tarjetas con aforos, costos por turno, garantías y botón directo de reserva.
  - Pestaña de **Agenda & Historial**: Grilla completa de eventos, horarios, villas y titulares con opción de cancelación.
