# 🚀 Guía de Instalación Paso a Paso en VPS — Ubuntu Server 25 & PHP 8.4+
**Plataforma:** Residia CRM Multi-Urbanizaciones SaaS & Control Inteligente de Garitas  
**Organización:** Robledal Connecting  
**Versión de la Suite:** 2.0.0 (Gold Master)  
**Fecha de Creación:** 26 de Octubre de 2026  
**Sistema Operativo Objetivo:** Ubuntu Server 25 (25.04 / 25.10)  
**Stack Tecnológico:** PHP 8.4+, Nginx, PostgreSQL (16/17), Redis, Node.js 22 LTS, Composer v2  
**Estado:** PENDIENTE DE EJECUCIÓN (A la espera de acceso SSH al VPS)  

---

## 📋 Índice de Pasos

1. [Bloque 1: Actualización del Sistema y Paquetes Esenciales](#bloque-1-actualización-del-sistema-y-paquetes-esenciales)
2. [Bloque 2: Instalación de PHP 8.4 y Módulos de Producción](#bloque-2-instalación-de-php-84-y-módulos-de-producción)
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

### Bloque 2: Instalación de PHP 8.4 y Módulos de Producción

En Ubuntu 25, PHP 8.4 está disponible. Nos aseguramos de contar con el repositorio actualizado:

```bash
# 2.1 Agregar repositorio Ondřej Surý (para parches y extensiones oficiales)
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update

# 2.2 Instalar PHP 8.4 FPM, CLI y todas las extensiones requeridas por Residia
sudo apt install -y php8.4 php8.4-fpm php8.4-cli php8.4-common \
                    php8.4-pgsql php8.4-pdo-pgsql \
                    php8.4-mbstring php8.4-xml php8.4-curl \
                    php8.4-zip php8.4-gd php8.4-bcmath \
                    php8.4-intl php8.4-redis php8.4-opcache

# 2.3 Optimizar límites en php.ini para cargas de archivos y alto rendimiento
sudo sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 50M/' /etc/php/8.4/fpm/php.ini
sudo sed -i 's/^post_max_size = .*/post_max_size = 50M/' /etc/php/8.4/fpm/php.ini
sudo sed -i 's/^memory_limit = .*/memory_limit = 512M/' /etc/php/8.4/fpm/php.ini
sudo sed -i 's/^max_execution_time = .*/max_execution_time = 300/' /etc/php/8.4/fpm/php.ini

# 2.4 Reiniciar y habilitar PHP 8.4-FPM
sudo systemctl restart php8.4-fpm
sudo systemctl enable php8.4-fpm
php -v
```

---

### Bloque 3: Instalación y Configuración de PostgreSQL

```bash
# 3.1 Instalar PostgreSQL y utilidades contrib
sudo apt install -y postgresql postgresql-contrib
sudo systemctl enable --now postgresql

# 3.2 Crear usuario y base de datos con contraseña segura
# (Cambiar 'TuPasswordSeguro2026*' por una clave propia)
sudo -u postgres psql -c "CREATE USER residia WITH PASSWORD 'TuPasswordSeguro2026*';"
sudo -u postgres psql -c "CREATE DATABASE residia OWNER residia;"
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE residia TO residia;"

# 3.3 Habilitar extensiones criptográficas requeridas por Residia
sudo -u postgres psql -d residia -c "CREATE EXTENSION IF NOT EXISTS \"uuid-ossp\";"
sudo -u postgres psql -d residia -c "CREATE EXTENSION IF NOT EXISTS \"pgcrypto\";"
sudo -u postgres psql -d residia -c "ALTER SCHEMA public OWNER TO residia;"
sudo -u postgres psql -d residia -c "GRANT ALL ON SCHEMA public TO residia;"
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

# 6.3 Configurar las variables en .env
# Modificar:
# APP_ENV=production
# APP_DEBUG=false
# APP_URL=https://tudominio.com (o http://IP-DEL-VPS)
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=residia
# DB_USERNAME=residia
# DB_PASSWORD=TuPasswordSeguro2026*
nano .env

# 6.4 Restaurar la base de datos completa certificada v2.0
PGPASSWORD="TuPasswordSeguro2026*" psql -h 127.0.0.1 -U residia -d residia -f database/backups/residia_release_v2_completo.sql
```

---

### Bloque 7: Compilación de Frontend y Permisos de Laravel

```bash
cd /var/www/residia

# 7.1 Instalar dependencias PHP de producción
composer install --no-dev --optimize-autoloader --no-interaction

# 7.2 Generar claves maestras de cifrado
php artisan key:generate --force
php artisan jwt:secret --force

# 7.3 Instalar dependencias de Node y compilar Frontend Vite
npm install --prefer-offline
npm run build

# 7.4 Enlace simbólico de storage público
php artisan storage:link --force

# 7.5 Asignar permisos seguros al servidor web
sudo chown -R www-data:www-data /var/www/residia
sudo chmod -R 775 /var/www/residia/storage /var/www/residia/bootstrap/cache

# 7.6 Precompilar cachés de producción
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

### Bloque 8: Configuración del Worker de Colas (Systemd)

Para procesar notificaciones, correos y tareas en background:

```bash
# 8.1 Copiar archivo de servicio systemd
sudo cp /var/www/residia/scripts/install/residia-worker.service /etc/systemd/system/residia-worker.service

# 8.2 Recargar y activar worker
sudo systemctl daemon-reload
sudo systemctl enable --now residia-worker.service
sudo systemctl status residia-worker.service
```

---

### Bloque 9: Seguridad Perimetral (Firewall UFW y Certificado SSL)

```bash
# 9.1 Copiar y activar VirtualHost Nginx
sudo cp /var/www/residia/scripts/install/nginx_residia.conf /etc/nginx/sites-available/residia
sudo ln -sf /etc/nginx/sites-available/residia /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default

# 9.2 Editar server_name con el dominio asignado
sudo nano /etc/nginx/sites-available/residia

# 9.3 Probar sintaxis y recargar Nginx
sudo nginx -t
sudo systemctl reload nginx

# 9.4 Activar Firewall UFW
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw --force enable
sudo ufw status

# 9.5 Instalar Certificado SSL Gratuito (si ya se cuenta con dominio apuntando a la IP)
sudo certbot --nginx -d tudominio.com
```

---

### Bloque 10: Verificación Final y Credenciales de Acceso

Una vez completada la ejecución:

1. **URL de Acceso:** `https://tudominio.com` o `http://IP-DEL-VPS`
2. **Credenciales Maestras Iniciales:**
   - **Usuario SuperAdmin:** `admin@robledal.com`
   - **Contraseña:** `Admin2026.*`
3. **Credencial de Guardia (Garita):**
   - **Usuario:** `guardia1@robledal.com`
   - **Contraseña:** `Guardia2026.*`

---
*Documento guardado para ejecución inmediata cuando se disponga de conexión SSH al VPS.*
