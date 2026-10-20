# Arquitectura: Party Model (Separación de Identidad vs Cuentas)

## 1. Principio Fundamental
En **Residia**, separamos estrictamente la existencia física de un ser humano de sus credenciales de acceso al sistema:

1. **`core_personas`**:
   - Padrón unificado de identidad.
   - Aplica a: Residentes, Visitas, Conductores, Proveedores, Deliveries, Contratistas y Guardias.
   - Llave única natural: `per_identificacion` (Cédula / Pasaporte / RUC).
   - Control de seguridad: `per_es_vetado` (Lista negra inmediata en garita).

2. **`sec_users`**:
   - Cuentas de login con correo y contraseña.
   - Exclusivo para: Administradores del CRM, Operadores de Garita y Residentes con acceso a la App / Portal.
   - Llave foránea `usr_persona_id` hacia `core_personas`.

---

## 2. Beneficios Operativos
- Cero registros basura en la tabla de autenticación.
- Búsqueda en garita en milisegundos por cédula sin importar el rol de la persona.
- Historial transversal de visitas de una persona a través de múltiples urbanizaciones.
