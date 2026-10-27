# Manual Maestro de Despliegue en Producción & Puesta en Marcha
**Proyecto:** Residia SaaS • Plataforma Integral Multi-Urbanizaciones & Garita Inteligente  
**Organización:** Robledal Connecting  
**Versión de Lanzamiento:** 2.0.0 (Gold Master)  
**Última Actualización:** 27 de Octubre de 2026 (Validado en Producción)  
**Entorno Certificado:** Servidor Cloud VPS (Ubuntu Server 24.04 / 25 con PHP 8.4 / 8.5)  

---

## 1. Arquitectura y Requisitos del Servidor Cloud

| Componente | Especificación Certificada en Producción |
| :--- | :--- |
| **CPU / RAM** | 2 vCPU / 4 GB RAM (8 GB recomendado para alta concurrencia) |
| **Disco** | 40 GB SSD / NVMe |
| **Sistema Operativo** | Ubuntu Server 24.04 LTS o Ubuntu Server 25 |
| **Motor Web** | Nginx 1.24+ / 1.28+ con soporte HTTP/2 y compresión Gzip |
| **PHP Runtime** | PHP 8.4 o **PHP 8.5** con `php-fpm`, `pdo_pgsql`, `mbstring`, `xml`, `curl`, `zip`, `gd`, `bcmath`, `intl`, `redis`, `opcache` |
| **Base de Datos** | PostgreSQL 16+ / 17+ con extensiones `uuid-ossp` y `pgcrypto` |
| **Caché / MQ** | Redis Server 7+ |
| **Node.js** | Node.js 20 LTS o 22 LTS + NPM |
| **Procesos en Background** | Systemd (`residia-worker.service`) |

---

## 2. Preparación del Sistema Base & Dependencias

Conectarse como usuario con privilegios `sudo` o `root` en el VPS:

```bash
# 2.1 Actualizar paquetes del sistema
sudo apt update && sudo apt upgrade -y

# 2.2 Instalar herramientas de soporte y servidor web
sudo apt install -y curl wget gnupg2 ca-certificates lsb-release apt-transport-https \
                    software-properties-common git unzip ufw supervisor htop \
                    nginx redis-server postgresql postgresql-contrib

# 2.3 Instalar PHP (versión 8.5 o 8.4) y módulos oficiales
sudo apt install -y php-fpm php-cli php-common \
                    php-pgsql php-mbstring php-xml php-curl \
                    php-zip php-gd php-bcmath php-intl \
                    php-redis php-opcache

# 2.4 Instalar Composer v2 globalmente
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer

# 2.5 Instalar Node.js 22 LTS
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo bash -
sudo apt install -y nodejs
```

---

## 3. Configuración de Base de Datos PostgreSQL

En producción, la base de datos debe contar con un **usuario dedicado** y permisos explícitos sobre el esquema `public`:

```bash
# 3.1 Crear usuario 'residia' con contraseña y otorgar propiedad
sudo -u postgres psql << 'EOF'
DO $$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_catalog.pg_roles WHERE rolname = 'residia') THEN
        CREATE USER residia WITH PASSWORD 'Residia2026.*';
    ELSE
        ALTER USER residia WITH PASSWORD 'Residia2026.*';
    END IF;
END
$$;

CREATE DATABASE residia OWNER residia;
ALTER DATABASE residia OWNER TO residia;
GRANT ALL PRIVILEGES ON DATABASE residia TO residia;

\c residia
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";
ALTER SCHEMA public OWNER TO residia;
GRANT ALL ON SCHEMA public TO residia;
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO residia;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO residia;
EOF
```

---

## 4. Despliegue de Código & Variables de Entorno

```bash
# 4.1 Clonar la rama 'residia' en la ruta estándar
sudo mkdir -p /var/www/residia
sudo chown -R $USER:$USER /var/www/residia
git clone -b residia https://github.com/vinnyvgs1-ctrl/residia.git /var/www/residia
cd /var/www/residia

# 4.2 Preparar archivo .env
cp .env.example .env

# 4.3 Configurar variables de base de datos en .env
sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=pgsql/' .env
sed -i 's/^DB_HOST=.*/DB_HOST=127.0.0.1/' .env
sed -i 's/^DB_PORT=.*/DB_PORT=5432/' .env
sed -i 's/^DB_DATABASE=.*/DB_DATABASE=residia/' .env
sed -i 's/^DB_USERNAME=.*/DB_USERNAME=residia/' .env
sed -i 's/^DB_PASSWORD=.*/DB_PASSWORD=Residia2026.*/' .env
sed -i 's/^APP_ENV=.*/APP_ENV=production/' .env
sed -i 's/^APP_DEBUG=.*/APP_DEBUG=false/' .env

# 4.4 Restaurar Snapshot Maestro Certificado v2.0
PGPASSWORD="Residia2026.*" psql -h 127.0.0.1 -U residia -d residia -f database/backups/residia_release_v2_completo.sql
```

---

## 5. Instalación de Paquetes PHP, Frontend & Claves Criptográficas

> [!IMPORTANT]
> **Compatibilidad de Versión de PHP:**  
> Si tu servidor cuenta con PHP 8.4 o PHP 8.5, debe agregarse el parámetro `--ignore-platform-req=php+` para permitir que Composer resuelva las librerías `lcobucci/clock` y `lcobucci/jwt` de forma transparente.

```bash
cd /var/www/residia

# 5.1 Dependencias PHP Backend
composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-req=php+

# 5.2 Generación de APP_KEY y JWT_SECRET
php artisan key:generate --force
php artisan jwt:secret --force

# 5.3 Compilación de Assets con Vite y Vue 3
npm install --prefer-offline
npm run build

# 5.4 Enlace simbólico de Storage público
php artisan storage:link --force
```

---

## 6. Permisos de Archivos y Caché de Producción

Para evitar fallos de lectura/escritura (`Permission denied`) tanto desde Nginx (`www-data`) como desde la terminal CLI:

```bash
# 6.1 Permisos de almacenamiento y caché
sudo chown -R www-data:www-data /var/www/residia/storage /var/www/residia/bootstrap/cache
sudo chmod -R 777 /var/www/residia/storage /var/www/residia/bootstrap/cache

# 6.2 Precompilación de Cachés de Laravel
php artisan optimize:clear
php artisan optimize
```

---

## 7. Configuración de Nginx & FastCGI Socket

1. **Copiar plantilla VirtualHost**:
   ```bash
   sudo cp /var/www/residia/scripts/install/nginx_residia.conf /etc/nginx/sites-available/residia
   sudo ln -sf /etc/nginx/sites-available/residia /etc/nginx/sites-enabled/
   sudo rm -f /etc/nginx/sites-enabled/default
   ```

2. **Ajustar el Socket según la versión de PHP activa**:
   Verificar el nombre exacto del socket con `ls /run/php/` (por ejemplo `php8.5-fpm.sock` o `php8.4-fpm.sock`):
   ```bash
   # En Ubuntu 25 (PHP 8.5):
   sudo sed -i 's/php8.4-fpm.sock/php8.5-fpm.sock/g' /etc/nginx/sites-available/residia
   sudo sed -i 's/php8.2-fpm.sock/php8.5-fpm.sock/g' /etc/nginx/sites-available/residia
   ```

3. **Verificar sintaxis y recargar Nginx**:
   ```bash
   sudo nginx -t
   sudo systemctl reload nginx
   sudo systemctl restart php8.5-fpm
   ```

---

## 8. Seguridad Perimetral, Firewall UFW & Certificado SSL

```bash
# 8.1 Firewall del Sistema
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw --force enable

# 8.2 Certificado SSL Gratuito con Let's Encrypt (Certbot)
# (Reemplazar 'tudominio.com' por el dominio real apuntando a la IP)
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d tudominio.com
```

---

## 9. Servicio en Segundo Plano (Systemd Queue Worker)

Para la ejecución de notificaciones Web Push, eventos de garita y tareas programadas:

```bash
# 9.1 Instalar servicio
sudo cp /var/www/residia/scripts/install/residia-worker.service /etc/systemd/system/residia-worker.service

# 9.2 Habilitar e iniciar
sudo systemctl daemon-reload
sudo systemctl enable --now residia-worker.service
sudo systemctl status residia-worker.service
```

---

## 10. Credenciales Maestras de Acceso

- **URL de Ingreso:** `https://tudominio.com` o `http://IP_SERVIDOR/`
- **SuperAdmin:**
  - **Usuario:** `admin@robledal.com`
  - **Contraseña:** `Admin2026.*`
- **Puesto de Guardia (Garita):**
  - **Usuario:** `guardia1@robledal.com`
  - **Contraseña:** `Guardia2026.*`

---

## 11. Guía de Resolución de Incidencias Comunes (Troubleshooting)

### A. Error `502 Bad Gateway` en el navegador
- **Causa:** Nginx apunta a un socket de PHP-FPM inexistente o el servicio FPM está detenido.
- **Solución:**
  1. Revisar los sockets disponibles: `ls -la /run/php/`.
  2. Confirmar que el archivo `/etc/nginx/sites-available/residia` coincida con el socket activo (ej: `php8.5-fpm.sock`).
  3. Reiniciar servicios: `sudo systemctl restart php8.5-fpm && sudo systemctl reload nginx`.

### B. Error `Database file at path [.../database.sqlite] does not exist`
- **Causa:** El archivo `.env` tenía `DB_CONNECTION=sqlite` al compilar la caché inicial.
- **Solución:**
  1. Asegurar en `.env`: `DB_CONNECTION=pgsql` y las credenciales del usuario `residia`.
  2. Limpiar caché: `php artisan optimize:clear && php artisan optimize`.

### C. Error `Permission denied` en `laravel.log` o `config.php`
- **Causa:** Incompatibilidad de permisos entre el usuario de consola (`ubuntu`) y el del servidor web (`www-data`).
- **Solución:**
  ```bash
  sudo chown -R www-data:www-data /var/www/residia/storage /var/www/residia/bootstrap/cache
  sudo chmod -R 777 /var/www/residia/storage /var/www/residia/bootstrap/cache
  ```

### D. Error de Composer `requires php ~8.1.0 || ~8.2.0 -> your php version (8.5.x) does not satisfy`
- **Causa:** Paquetes fijados en `composer.lock` para versiones previas.
- **Solución:** Ejecutar Composer agregando el flag `--ignore-platform-req=php+`.
