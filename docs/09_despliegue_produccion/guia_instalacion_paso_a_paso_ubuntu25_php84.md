# 🚀 Guía de Instalación Paso a Paso en VPS — Ubuntu Server 25 & PHP 8.5+
**Plataforma:** Residia CRM Multi-Urbanizaciones SaaS & Control Inteligente de Garitas  
**Organización:** Robledal Connecting  
**Versión de la Suite:** 2.0.0 (Gold Master)  
**Fecha de Validación:** 27 de Octubre de 2026  
**Sistema Operativo Objetivo:** Ubuntu Server 25 (25.04 / 25.10)  
**Stack Tecnológico:** PHP 8.5+, Nginx 1.28+, PostgreSQL 16/17, Redis, Node.js 22 LTS, Composer v2  
**Estado:** ✅ COMPLETADO Y CERTIFICADO EN PRODUCCIÓN  

---

## 📋 Índice de Pasos

1. [Bloque 1: Actualización del Sistema y Paquetes Esenciales](#bloque-1-actualización-del-sistema-y-paquetes-esenciales)
2. [Bloque 2: Instalación de PHP 8.5 y Módulos de Producción](#bloque-2-instalación-de-php-85-y-módulos-de-producción)
3. [Bloque 3: Instalación y Configuración de PostgreSQL](#bloque-3-instalación-y-configuración-de-postgresql)
4. [Bloque 4: Instalación de Redis, Composer y Node.js 22 LTS](#bloque-4-instalación-de-redis-composer-y-nodejs-22-lts)
5. [Bloque 5: Instalación y Configuración de Nginx](#bloque-5-instalación-y-configuración-de-nginx)
6. [Bloque 6: Clonado del Proyecto y Restauración de Base de Datos](#bloque-6-clonado-del-proyecto-y-restauración-de-base-de-datos)
7. [Bloque 7: Compilación de Frontend y Permisos de Laravel](#bloque-7-compilación-de-frontend-y-permisos-de-laravel)
8. [Bloque 8: Configuración del Worker de Colas (Systemd)](#bloque-8-configuración-del-worker-de-colas-systemd)
9. [Bloque 9: Seguridad Perimetral (Firewall UFW y Certificado SSL)](#bloque-9-seguridad-perimetral-firewall-ufw-y-certificado-ssl)
10. [Bloque 10: Verificación Final y Credenciales de Acceso](#bloque-10-verificación-final-y-credenciales-de-acceso)

---

### Bloque 1: Actualización del Sistema y Paquetes Esenciales

Conectarse por SSH al VPS con usuario `sudo` o `root`:

```bash
# 1.1 Actualizar índice de paquetes y el sistema
sudo apt update && sudo apt upgrade -y

# 1.2 Instalar utilidades esenciales
sudo apt install -y curl wget gnupg2 ca-certificates lsb-release apt-transport-https \
                    software-properties-common git unzip ufw supervisor htop
```

---

### Bloque 2: Instalación de PHP 8.5 y Módulos de Producción

En Ubuntu 25, PHP 8.5 se instala de forma nativa o mediante el PPA oficial de Ondřej Surý:

```bash
# 2.1 Agregar repositorio Ondřej Surý para parches oficiales
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update

# 2.2 Instalar PHP 8.5 FPM, CLI y todas las extensiones requeridas por Residia
sudo apt install -y php8.5 php8.5-fpm php8.5-cli php8.5-common \
                    php8.5-pgsql php8.5-pdo-pgsql \
                    php8.5-mbstring php8.5-xml php8.5-curl \
                    php8.5-zip php8.5-gd php8.5-bcmath \
                    php8.5-intl php8.5-redis php8.5-opcache

# 2.3 Optimizar límites en php.ini para cargas de archivos y alto rendimiento
sudo sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 50M/' /etc/php/8.5/fpm/php.ini
sudo sed -i 's/^post_max_size = .*/post_max_size = 50M/' /etc/php/8.5/fpm/php.ini
sudo sed -i 's/^memory_limit = .*/memory_limit = 512M/' /etc/php/8.5/fpm/php.ini
sudo sed -i 's/^max_execution_time = .*/max_execution_time = 300/' /etc/php/8.5/fpm/php.ini

# 2.4 Reiniciar y habilitar PHP 8.5-FPM
sudo systemctl restart php8.5-fpm
sudo systemctl enable php8.5-fpm
php -v
```

---

### Bloque 3: Instalación y Configuración de PostgreSQL

```bash
# 3.1 Instalar PostgreSQL y utilidades contrib
sudo apt install -y postgresql postgresql-contrib
sudo systemctl enable --now postgresql

# 3.2 Crear usuario dedicado 'residia' con contraseña y otorgar control del esquema public
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

### Bloque 4: Instalación de Redis, Composer y Node.js 22 LTS

```bash
# 4.1 Instalar y habilitar Redis Server
sudo apt install -y redis-server
sudo systemctl enable --now redis-server
redis-cli ping # Debe responder PONG

# 4.2 Instalar Composer v2 globalmente
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
composer --version

# 4.3 Instalar Node.js 22 LTS y NPM
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo bash -
sudo apt install -y nodejs
node -v && npm -v
```

---

### Bloque 5: Instalación y Configuración de Nginx

```bash
# 5.1 Instalar Nginx y Certbot
sudo apt install -y nginx certbot python3-certbot-nginx
sudo systemctl enable --now nginx

# 5.2 Crear directorio para la aplicación
sudo mkdir -p /var/www/residia
sudo chown -R $USER:$USER /var/www/residia
```

---

### Bloque 6: Clonado del Proyecto y Restauración de Base de Datos

```bash
# 6.1 Clonar la rama 'residia' desde GitHub
git clone -b residia https://github.com/vinnyvgs1-ctrl/residia.git /var/www/residia
cd /var/www/residia

# 6.2 Copiar archivo de variables de entorno
cp .env.example .env

# 6.3 Configurar las variables en .env con PostgreSQL
sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=pgsql/' .env
sed -i 's/^DB_HOST=.*/DB_HOST=127.0.0.1/' .env
sed -i 's/^DB_PORT=.*/DB_PORT=5432/' .env
sed -i 's/^DB_DATABASE=.*/DB_DATABASE=residia/' .env
sed -i 's/^DB_USERNAME=.*/DB_USERNAME=residia/' .env
sed -i 's/^DB_PASSWORD=.*/DB_PASSWORD=Residia2026.*/' .env
sed -i 's/^APP_ENV=.*/APP_ENV=production/' .env
sed -i 's/^APP_DEBUG=.*/APP_DEBUG=false/' .env

# 6.4 Restaurar la base de datos completa certificada v2.0
PGPASSWORD="Residia2026.*" psql -h 127.0.0.1 -U residia -d residia -f database/backups/residia_release_v2_completo.sql
```

---

### Bloque 7: Compilación de Frontend y Permisos de Laravel

```bash
cd /var/www/residia

# 7.1 Instalar dependencias PHP de producción (con bypass de versión para librerías locked)
composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-req=php+

# 7.2 Generar claves maestras de cifrado
php artisan key:generate --force
php artisan jwt:secret --force

# 7.3 Instalar dependencias de Node y compilar Frontend Vite
npm install --prefer-offline
npm run build

# 7.4 Enlace simbólico de storage público
php artisan storage:link --force

# 7.5 Asignar permisos completos a storage y caché (evita Permission denied)
sudo chown -R www-data:www-data /var/www/residia/storage /var/www/residia/bootstrap/cache
sudo chmod -R 777 /var/www/residia/storage /var/www/residia/bootstrap/cache

# 7.6 Precompilar cachés de producción
php artisan optimize:clear
php artisan optimize
```

---

### Bloque 8: Configuración del VirtualHost de Nginx & FastCGI Socket

```bash
# 8.1 Copiar y activar VirtualHost Nginx
sudo cp /var/www/residia/scripts/install/nginx_residia.conf /etc/nginx/sites-available/residia
sudo ln -sf /etc/nginx/sites-available/residia /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default

# 8.2 Asegurar que el socket coincida con PHP 8.5
sudo sed -i 's/php8.4-fpm.sock/php8.5-fpm.sock/g' /etc/nginx/sites-available/residia
sudo sed -i 's/php8.2-fpm.sock/php8.5-fpm.sock/g' /etc/nginx/sites-available/residia

# 8.3 Probar sintaxis y reiniciar servicios
sudo nginx -t
sudo systemctl reload nginx
sudo systemctl restart php8.5-fpm
```

---

### Bloque 9: Worker de Colas y Seguridad Perimetral

```bash
# 9.1 Servicio en segundo plano para colas (Systemd)
sudo cp /var/www/residia/scripts/install/residia-worker.service /etc/systemd/system/residia-worker.service
sudo systemctl daemon-reload
sudo systemctl enable --now residia-worker.service

# 9.2 Firewall UFW
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw --force enable

# 9.3 Certificado SSL Gratuito Let's Encrypt (cuando se configure el dominio)
sudo certbot --nginx -d tudominio.com
```

---

### Bloque 10: Verificación Final y Credenciales de Acceso

1. **URL de Acceso:** `http://IP_SERVIDOR/` o `https://tudominio.com`
2. **Credenciales Maestras:**
   - **SuperAdmin:** `admin@robledal.com` / `Admin2026.*`
   - **Guardia (Garita):** `guardia1@robledal.com` / `Guardia2026.*`
