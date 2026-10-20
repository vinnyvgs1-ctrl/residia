# Informe Técnico & Manual de Operación: Comunicados Oficiales, Votaciones & Asambleas Digitales
**Proyecto:** Residia SaaS • Plataforma Integral Multi-Urbanizaciones  
**Módulo:** Participación Ciudadana, Asambleas y Escrutinio Digital  
**Versión:** 1.0.0  
**Fecha:** 19 de Octubre de 2026  
**Auditoría & Trazabilidad:** Cumple estándar Robledal Connecting & `AuditLogger`  

---

## 1. Resumen Ejecutivo & Justificación
En comunidades residenciales y urbanizaciones privadas, la toma de decisiones asamblearias tradicionalmente adolece de problemas críticos:
1. **Falta de Quórum:** Los copropietarios ausentes o de viaje no pueden emitir su postura.
2. **Impugnación de Resultados:** Cuestionamientos sobre el recuento manual de votos y si los votantes estaban al día en sus obligaciones de expensas comunes.
3. **Ponderación Alícuotas vs. 1 Voto por Predio:** Determinados reglamentos de propiedad horizontal exigen voto proporcional al coeficiente de alícuota sobre el área privativa, mientras que consultas operativas aplican la regla de 1 voto por predio.
4. **Dispersión de Canales de Comunicación:** Los avisos por grupos informales generan desinformación y falta de acuse oficial.

El presente módulo introduce una plataforma integral de **Circulares/Comunicados Oficiales** con niveles de urgencia y acuse de lectura, aunada a un motor de **Votaciones & Asambleas Digitales Cripto-Verificables** con candado de solvencia de alícuotas, escrutinio en tiempo real y soporte tanto nominal como ponderado.

---

## 2. Modelo de Datos y Esquema Relacional (PostgreSQL)

### A. Tabla `residia.op_comunicados`
Registra los avisos, circulares y normativas emitidas por el Directorio o Administración.

```sql
CREATE TABLE IF NOT EXISTS residia.op_comunicados (
    com_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    com_urbanizacion_id UUID NOT NULL REFERENCES residia.conf_urbanizaciones(urb_id) ON DELETE CASCADE,
    com_autor_id UUID REFERENCES residia.gen_personas(per_id) ON DELETE SET NULL,
    com_titulo VARCHAR(255) NOT NULL,
    com_contenido TEXT NOT NULL,
    com_categoria VARCHAR(50) DEFAULT 'general', -- 'general', 'mantenimiento', 'seguridad', 'asamblea', 'emergencia'
    com_urgente BOOLEAN DEFAULT FALSE,
    com_fijado BOOLEAN DEFAULT FALSE,
    com_adjuntos JSONB DEFAULT '[]'::jsonb,
    com_publicado_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
```

### B. Tabla `residia.op_votaciones`
Define las asambleas, consultas vinculantes o sondeos de opinión.

```sql
CREATE TABLE IF NOT EXISTS residia.op_votaciones (
    vot_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    vot_urbanizacion_id UUID NOT NULL REFERENCES residia.conf_urbanizaciones(urb_id) ON DELETE CASCADE,
    vot_titulo VARCHAR(255) NOT NULL,
    vot_descripcion TEXT,
    vot_tipo_ponderacion VARCHAR(30) DEFAULT 'un_voto_por_predio', -- 'un_voto_por_predio', 'alicuota_ponderada'
    vot_exige_solvencia BOOLEAN DEFAULT TRUE,
    vot_estado VARCHAR(20) DEFAULT 'abierta', -- 'borrador', 'abierta', 'cerrada', 'impugnada'
    vot_opciones JSONB NOT NULL DEFAULT '["A Favor", "En Contra", "Abstención"]'::jsonb,
    vot_fecha_inicio TIMESTAMP WITH TIME ZONE NOT NULL,
    vot_fecha_fin TIMESTAMP WITH TIME ZONE NOT NULL,
    vot_quorum_minimo_pct NUMERIC(5,2) DEFAULT 50.00,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
```

### C. Tabla `residia.op_votos_emitidos`
Garantiza la regla de unicidad por predio y la persistencia del coeficiente ponderado en el momento del sufragio.

```sql
CREATE TABLE IF NOT EXISTS residia.op_votos_emitidos (
    vto_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    vto_votacion_id UUID NOT NULL REFERENCES residia.op_votaciones(vot_id) ON DELETE CASCADE,
    vto_inmueble_id UUID NOT NULL REFERENCES residia.conf_inmuebles(inm_id) ON DELETE CASCADE,
    vto_persona_id UUID NOT NULL REFERENCES residia.gen_personas(per_id) ON DELETE CASCADE,
    vto_opcion_seleccionada VARCHAR(255) NOT NULL,
    vto_peso_ponderado NUMERIC(7,4) DEFAULT 1.0000,
    vto_observacion TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_votacion_inmueble UNIQUE (vto_votacion_id, vto_inmueble_id)
);
```

---

## 3. Endpoints de la API REST (`ComunicadoVotacionController.php`)

| Método | Endpoint | Middleware | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/comunicados` | `auth:api`, `urbanizacion.scope` | Listado paginado con filtros de urgencia, categoría y fijados. |
| `POST` | `/api/comunicados` | `auth:api`, `urbanizacion.scope` | Publicación de circular con broadcast a residentes y auditoría. |
| `GET` | `/api/votaciones` | `auth:api`, `urbanizacion.scope` | Lista de votaciones con quórum alcanzado y escrutinio en vivo. |
| `POST` | `/api/votaciones` | `auth:api`, `urbanizacion.scope` | Apertura de asamblea/votación con parámetros de ponderación y solvencia. |
| `POST` | `/api/votaciones/{id}/votar` | `auth:api`, `urbanizacion.scope` | Emisión blindada de voto. Comprueba duplicidad y solvencia de expensas. |

---

## 4. Reglas de Negocio & Candados de Integridad

1. **Candado de Solvencia:** Si `vot_exige_solvencia = true`, el sistema consulta la tabla `residia.fin_obligaciones_alicuotas`. Si el predio registra obligaciones pendientes vencidas (`estado = 'pendiente'` y `fecha_vencimiento < NOW()`), el voto es rechazado con error `403 Forbidden` (*"El inmueble presenta cuotas vencidas y requiere estar al día según el reglamento de asamblea"*).
2. **Unicidad de Sufragio por Inmueble:** Constraint estricta `(vto_votacion_id, vto_inmueble_id)`. Se impide que dos copropietarios del mismo predio voten dos veces.
3. **Ponderación Coeficiente:**
   - En modalidad `un_voto_por_predio`: Cada voto suma `1.0`.
   - En modalidad `alicuota_ponderada`: Se extrae el coeficiente porcentual del predio (`inm_alicuota_porcentaje`, ej. `2.45%`) y dicho valor se consolida en el totalizador de la opción.
4. **Cálculo de Quórum:** Se compara el universo total de inmuebles habilitados en la urbanización vs. predios participantes.

---

## 5. Experiencia de Usuario (Frontend Vue 3)
La interfaz `ComunicadosVotacionesView.vue` cuenta con:
- Pestaña de **Boletines & Circulares**: Tarjetas con distintivos de `Urgente`, etiquetas por categoría (`Seguridad`, `Mantenimiento`, `Asamblea`), fecha formateada y visor de documentos adjuntos.
- Pestaña de **Asambleas & Votaciones**:
  - Modalidad de urna interactiva con barras de progreso animadas en tiempo real.
  - Indicador de estado (`🟢 Abierta`, `🔴 Cerrada`).
  - Badge informativo de ponderación (`1 Voto x Predio` vs `Ponderado por Alícuota`).
  - Modal de emisión con selector de inmuebles asignados al usuario y advertencia de solvencia.
- Menú lateral en `Navbar.vue` integrado bajo la sección Administración con ícono de sufragio institucional.

---

## 6. Verificación y Auditoría
Todas las operaciones quedan asentadas en `residia.aud_logs_sistema` con el formato:
- `EMISION_VOTO_ASAMBLEA` sobre tabla `op_votos_emitidos`.
- `PUBLICACION_COMUNICADO` sobre tabla `op_comunicados`.
- `CREACION_ASAMBLEA_VOTACION` sobre tabla `op_votaciones`.
