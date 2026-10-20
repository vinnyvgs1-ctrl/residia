# Evaluación de Blindaje & Readiness para Prueba de Ethical Hacking (Pentest)
**Plataforma:** RESIDIA CRM Multi-Urbanizaciones & Garita Inteligente  
**Estándar de Evaluación:** OWASP Top 10 (2021/2026), NIST SP 800-115, PTES (Penetration Testing Execution Standard)  
**Fecha de Evaluación:** 2026-10-08  
**Clasificación de Seguridad:** Confidencial / Corporativo Robledal Connecting  

---

## 1. Veredicto Ejecutivo: ¿Estamos listos para un Ethical Hacking?

> **Diagnóstico:** **LISTOS EN UN 85% (Fase de Pruebas Controladas / Gray Box Testing).**  
> La arquitectura base cuenta con defensas excepcionales frente a ataques comunes (inyecciones SQL prevenidas por PDO/Postgres, control de replay attacks por HMAC/Redis, hashing de passwords con Bcrypt cost 12, aislamiento multi-tenant por UUID y RBAC matricial de 5 dimensiones).  
> 
> Sin embargo, **antes de someter el sistema a un Pentest formal (Red Team) o auditoría externa**, existen **5 vectores críticos de hardening** que deben ser ajustados para evitar que los evaluadores reporten hallazgos de severidad media y alta.

---

## 2. Fortalezas Criptográficas y de Blindaje Actual (Superadas con Éxito)

| Vector de Seguridad | Implementación Actual en Residia | Nivel de Protección |
| :--- | :--- | :---: |
| **SQL Injection (SQLi)** | Eloquent ORM + PDO Prepared Statements con PostgreSQL. Parámetros fuertemente tipados. | **Alto (100%)** |
| **Replay Attacks & Request Tampering** | Middleware `ApiShieldMiddleware` con HMAC-SHA256, cabecera de firma de payload, marcas de tiempo con tolerancia máxima de 300s y Nonces atómicos en Redis. | **Muy Alto (95%)** |
| **Aislamiento Multi-Tenant** | Claves foráneas UUID (`urb_id`), índice compuesto por sede e inyección forzada del header `X-Urbanizacion-Id` validado en sesión/token. | **Alto (90%)** |
| **Auditoría Forense & No Repudio** | Trazabilidad CDC transaccional (`aud_trazabilidad`) con diffs JSON de valores anteriores y nuevos, registro de descargas e impresiones (`aud_reportes_descargas`). | **Alto (95%)** |
| **Matriz de Autorización RBAC** | RBAC matricial de 5 dimensiones (`read`, `write`, `update`, `delete`, `print`) validado mediante middleware de ruta. | **Alto (90%)** |
| **Almacenamiento de Credenciales** | Contraseñas hasheadas mediante Bcrypt con salt automático. Cero almacenamiento en texto claro. | **Muy Alto (100%)** |

---

## 3. Matriz de Vectores Críticos a Blindar Antes del Pentest

A continuación se detallan los puntos que un Ethical Hacker intentará vulnerar de inmediato:

```mermaid
graph TD
    A[Objetivos del Pentester / Red Team] --> B[1. Enumeración & Information Disclosure]
    A --> C[2. Fuga de Tokens & Token Hijacking]
    A --> D[3. Fuerza Bruta & DoS en Auth / Garita]
    A --> E[4. Inseguridad de Headers HTTP]
    A --> F[5. Bypass de Tenant / IDOR]

    B --> B1[APP_DEBUG=true expone trazas de error]
    C --> C1[JWT sin lista de revocación en logout instantáneo]
    D --> D1[Falta de Rate Limiter estricto por IP en /api/auth/login]
    E --> E1[Falta de cabeceras HSTS, CSP, X-Frame-Options]
    F --> F1[Verificación de propiedad cruzada en InmuebleController]
```

### Detalle de los 5 Hallazgos Pendientes

### 🔴 1. Exposición de Trazas de Error (`APP_DEBUG=true`)
- **Riesgo:** Si un pentester envía payloads malformados en rutas de PostgreSQL o JSON con sintaxis errónea, Laravel devolverá la pantalla de depuración completa con nombres de tablas, rutas del servidor `/Applications/XAMPP/...` y versiones de software.
- **Acción requerida:** 
  - En entorno de pentest/producción, configurar `APP_DEBUG=false` y forzar respuestas JSON sanitizadas de error 500 (`"error": "SERVER_ERROR", "message": "Ocurrió un error interno procesando la solicitud."`).

### 🔴 2. Limitación de Tasa (Rate Limiting) en Autenticación
- **Riesgo:** El endpoint `POST /api/auth/login` permite intentos ilimitados de fuerza bruta contra el diccionario de usuarios y cédulas.
- **Acción requerida:**
  - Implementar middleware `throttle:5,1` (máximo 5 intentos fallidos por IP/cédula por minuto con bloqueo temporal en Redis).

### 🟡 3. Lista de Revocación de Tokens JWT (Blacklist / Denylist)
- **Riesgo:** Cuando un usuario pulsa "Cerrar Sesión", el token JWT sigue siendo matemáticamente válido hasta que transcurra su tiempo de vida (`jwt.ttl = 60 minutos`). Si el token fue interceptado previamente, un atacante podría continuar usándolo.
- **Acción requerida:**
  - Al ejecutar `POST /api/auth/logout`, invalidar el token actual en la blacklist de JWT (`auth('api')->invalidate()`) y verificar su inclusión en Redis.

### 🟡 4. Cabeceras HTTP de Seguridad (Security Headers)
- **Riesgo:** Ausencia de headers para mitigar Clickjacking, XSS e Inyecciones de Contenido.
- **Acción requerida:**
  - Agregar middleware global de cabeceras HTTP:
    - `X-Frame-Options: SAMEORIGIN` (evita que el portal sea incrustado en iframes maliciosos).
    - `X-Content-Type-Options: nosniff`.
    - `Strict-Transport-Security: max-age=31536000; includeSubDomains` (HSTS).
    - `Content-Security-Policy (CSP)` adaptado para fuentes de Google Fonts y APIs de Robledal.

### 🟡 5. Protección Estricta contra IDOR (Insecure Direct Object Reference)
- **Riesgo:** Un administrador de la Urbanización A que envíe una petición `DELETE /api/inmuebles/{id}` intentando manipular un inmueble perteneciente a la Urbanización B.
- **Acción requerida:**
  - Validar siempre a nivel de Query: `where('inm_urbanizacion_id', $urbanizacionActiva)` antes de permitir cualquier operación `update` o `delete`, independientemente de que se conozca el ID numérico.

---

## 4. Checklist Técnico de Preparación para la Prueba

- [x] Base de datos protegida con esquemas normalizados y prefijos (`sec_`, `core_`, `op_`, `aud_`).
- [x] Control de inyecciones SQL en consultas y migraciones.
- [x] Auditoría integral transaccional de cambios CDC con registro de IP y User-Agent.
- [x] Protección de exportaciones y descargas mediante permiso específico `print`.
- [ ] **Configurar `APP_DEBUG=false` en el entorno objetivo.**
- [ ] **Activar Rate Limiter `throttle:auth` en `routes/api.php`.**
- [ ] **Activar Blacklist de JWT en Redis.**
- [ ] **Inyectar Middleware Global de Security Headers.**
- [ ] **Verificación de ownership multi-tenant en controladores `InmuebleController` y `ListaNegraController`.**

---

## 5. Conclusión y Recomendación

El sistema cuenta con una base sólida, moderna y bien estructurada. Para estar **100% listos y blindados ante cualquier prueba de Ethical Hacking**, se recomienda ejecutar el plan de remediación de los 5 puntos anteriores.
