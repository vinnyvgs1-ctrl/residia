# Estándares de Base de Datos y Prefijos Modulares: Residia CRM

## 1. Convención de Prefijos por Módulo
| Prefijo | Módulo / Dominio | Descripción |
| :--- | :--- | :--- |
| **`sec_`** | **Seguridad & Acceso** | Temas dinámicos, cuentas, roles, permisos, sesiones y API keys. |
| **`core_`** | **Padrón & Estructura** | Urbanizaciones, personas físicas (Party Model), inmuebles y residentes. |
| **`op_`** | **Operaciones de Garita** | Garitas, control de accesos (peatonal/vehicular) y listas negras. |
| **`aud_`** | **Trazabilidad & CDC** | Diffs transaccionales y bitácora de exportación de reportes. |
| **`rep_`** | **Vistas de Reportes** | Plantillas de reportes y programaciones. |

---

## 2. Prefijos de Columnas
- `sec_temas` $\rightarrow$ `tem_id`, `tem_codigo`, `tem_nombre`, `tem_variables_css`, `tem_es_oscuro`.
- `sec_users` $\rightarrow$ `usr_id`, `usr_persona_id`, `usr_email`, `usr_password`, `usr_activo`.
- `core_urbanizaciones` $\rightarrow$ `urb_id`, `urb_codigo`, `urb_nombre`, `urb_ruc`, `urb_direccion`, `urb_configuracion`, `urb_activo`.
- `core_personas` $\rightarrow$ `per_id`, `per_identificacion`, `per_tipo_identificacion`, `per_nombres`, `per_apellidos`, `per_telefono`, `per_es_vetado`.
- `core_inmuebles` $\rightarrow$ `inm_id`, `inm_urbanizacion_id`, `inm_manzana_bloque`, `inm_numero_villa`, `inm_tipo`, `inm_estado`.
- `op_control_accesos` $\rightarrow$ `acc_id`, `acc_urbanizacion_id`, `acc_persona_id`, `acc_inmueble_id`, `acc_tipo_ingreso`, `acc_vehiculo_placa`, `acc_fecha_ingreso`, `acc_fecha_salida`, `acc_estado`.
- `aud_trazabilidad` $\rightarrow$ `aud_id`, `aud_urbanizacion_id`, `aud_user_id`, `aud_evento`, `aud_old_values`, `aud_new_values`, `aud_diff`.
