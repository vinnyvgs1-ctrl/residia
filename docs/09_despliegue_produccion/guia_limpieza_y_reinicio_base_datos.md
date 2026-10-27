# 🧹 Guía de Limpieza y Reinicio Seguro de Base de Datos
**Plataforma:** Residia CRM Multi-Urbanizaciones SaaS & Control Inteligente de Garitas  
**Organización:** Robledal Connecting  
**Versión:** 2.0 (Gold Master)  
**Propósito:** Reiniciar datos de prueba del prototipo o vaciar el sistema para iniciar con datos de clientes reales.  

---

## 📌 Modos de Limpieza Disponibles

El sistema cuenta con dos niveles de limpieza según la etapa en la que te encuentres:

| Nivel | Comando | ¿Qué se conserva? | ¿Qué se elimina? | ¿Cuándo usarlo? |
| :--- | :--- | :--- | :--- | :--- |
| **1. Modo Transaccional** *(Recomendado)* | `php artisan residia:clean --mode=transaccional` | **Conserva:** Urbanizaciones, Inmuebles (villas/manzanas), Personas, Vehículos, Cuentas, Roles y Permisos. | **Vacía:** Visitas en garita, Pases QR, Cobros de alícuotas, Reservas de áreas sociales, Paquetería, Novedades y Auditoría CDC. | **Después de hacer pruebas, demos a clientes o pilotos.** Deja las casas y residentes listos, pero con el historial en cero. |
| **2. Modo Total (Cero)** | `php artisan residia:clean --mode=total` | **Conserva únicamente:** Cuenta SuperAdmin (`admin@robledal.co`), Roles, Permisos y Temas visuales. | **Vacía:** Todas las urbanizaciones, inmuebles, vehículos, personas y transacciones. | **Para entregar el sistema completamente en blanco a un cliente nuevo.** |

---

## 🚀 Método 1: Vía Comando Artisan (Desde la terminal del VPS)

Es el método más seguro y rápido, ya que pide confirmación antes de borrar y limpia automáticamente la caché de Laravel:

### 1. Limpieza Transaccional (Reset de Pruebas):
```bash
cd /var/www/residia
php artisan residia:clean --mode=transaccional
```
*(El sistema te preguntará: `¿Está completamente seguro de continuar con la limpieza en modo [transaccional]? (yes/no)` $\rightarrow$ Escribe `yes` y presiona Enter).*

### 2. Limpieza Total (Lanzamiento en Blanco):
```bash
cd /var/www/residia
php artisan residia:clean --mode=total
```

---

## 🗄️ Método 2: Vía Script SQL Directo (PostgreSQL)

Si prefieres ejecutarlo directamente desde `psql`, pgAdmin o DBeaver:

```bash
cd /var/www/residia
PGPASSWORD="Residia2026.*" psql -h 127.0.0.1 -U residia -d residia -f database/scripts/limpiar_base_datos.sql
```

---

## 💾 Respaldo Preventivo (Buenas Prácticas)

Antes de realizar una limpieza en cualquier momento, puedes generar una copia de seguridad en 1 segundo ejecutando:

```bash
cd /var/www/residia
PGPASSWORD="Residia2026.*" pg_dump -h 127.0.0.1 -U residia -d residia > database/backups/backup_previo_limpieza_$(date +%Y%m%d).sql
```
