# Manual Maestro de Despliegue en Producción & Puesta en Marcha Inicial
**Proyecto:** Residia SaaS • Plataforma Integral Multi-Urbanizaciones & Garita Inteligente  
**Organización:** Robledal Connecting  
**Versión de Lanzamiento:** 2.0.0 (Gold Master)  
**Fecha:** 19 de Octubre de 2026  
**Entorno de Destino:** Servidor Cloud VPS (Ubuntu 22.04 / 24.04 LTS, Debian 12 o Rocky Linux)  

---

## 1. Requisitos Previos del Servidor Cloud

| Componente | Requisito Mínimo Recomendado |
| :--- | :--- |
| **CPU / RAM** | 2 vCPU / 4 GB RAM (8 GB recomendado para alta concurrencia) |
| **Disco** | 40 GB SSD / NVMe |
| **Sistema Operativo** | Ubuntu 22.04 LTS o 24.04 LTS |
| **Motor Web** | Nginx 1.22+ (o Apache 2.4 con `mod_rewrite`) |
| **PHP Runtime** | PHP 8.2 o 8.3 con extensiones: `php-fpm`, `php-pgsql`, `php-mbstring`, `php-xml`, `php-curl`, `php-zip`, `php-gd`, `php-bcmath` |
| **Base de Datos** | PostgreSQL 16+ con extensiones `uuid-ossp` y `pgcrypto` |
| **Node.js** | Node.js 18 LTS o 20 LTS + NPM |
| **Gestores de Procesos** | Systemd (para colas de background) o Supervisor |

---

## 2. Preparación Rápida del Sistema Operativo (Ubuntu/Debian)

Ejecutar en el servidor remoto como usuario `root` o con privilegios `sudo`:

```bash
# Actualizar repositorios
sudo apt update && sudo apt upgrade -y

# Instalar Nginx, PostgreSQL, Git, Curl y Zip
sudo apt install -y nginx postgresql postgresql-contrib git curl unzip

# Instalar PHP 8.2 y extensiones requeridas
sudo apt install -y php8.2-fpm php8.2-pgsql php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath

# Instalar Composer globalmente
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer

# Instalar Node.js 20 LTS
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

---

## 3. Descarga del Repositorio desde GitHub

```bash
# Ubicación estándar de proyectos web
sudo mkdir -p /var/www/residia
sudo chown -R $USER:$USER /var/www/residia

# Clonar la rama 'residia' del proyecto
git clone -b residia https://github.com/vinnyvgs1-ctrl/residia.git /var/www/residia
cd /var/www/residia
```

---

## 4. Instalador Automatizado en 1-Paso (`install.sh`)

El proyecto incluye un script de instalación desatendida en [`scripts/install/install.sh`](file:///Applications/XAMPP/xamppfiles/htdocs/residia/scripts/install/install.sh) que realiza de forma secuencial:
1. Verificación de binarios de entorno.
2. Generación y lectura del archivo de variables `.env`.
3. Instalación de paquetes PHP de producción (`composer install --no-dev --optimize-autoloader`).
4. Generación automática de `APP_KEY` y `JWT_SECRET`.
5. Creación de la base de datos PostgreSQL, activación de extensiones `uuid-ossp`/`pgcrypto` y restauración íntegra del dump oficial [`database/backups/residia_release_v2_completo.sql`](file:///Applications/XAMPP/xamppfiles/htdocs/residia/database/backups/residia_release_v2_completo.sql).
6. Instalación y compilación de assets Vite / Vue 3 (`npm run build`).
7. Enlace simbólico de storage (`php artisan storage:link`) y precompilación de cachés (`config:cache`, `route:cache`, `view:cache`).

### Para ejecutar el instalador:

```bash
# Otorgar permisos de ejecución
chmod +x scripts/install/install.sh

# Ejecutar el instalador maestro
./scripts/install/install.sh
```

---

## 5. Configuración de Nginx & SSL (Certbot Let's Encrypt)

1. **Copiar plantilla de configuración de Nginx**:
   ```bash
   sudo cp scripts/install/nginx_residia.conf /etc/nginx/sites-available/residia
   sudo ln -s /etc/nginx/sites-available/residia /etc/nginx/sites-enabled/
   sudo rm -f /etc/nginx/sites-enabled/default
   ```

2. **Editar dominio**:
   Abrir `/etc/nginx/sites-available/residia` y modificar `server_name residia.tudominio.com;` con el dominio o subdominio asignado.

3. **Verificar y reiniciar Nginx**:
   ```bash
   sudo nginx -t
   sudo systemctl restart nginx
   ```

4. **Instalar Certificado SSL Gratuito (HTTPS)**:
   ```bash
   sudo apt install -y certbot python3-certbot-nginx
   sudo certbot --nginx -d residia.tudominio.com
   ```

---

## 6. Configuración del Worker Asíncrono de Auditoría (Systemd)

Para procesar la bitácora forense de auditoría CDC y los webhooks de garita en segundo plano de manera continua:

```bash
# Copiar archivo de servicio
sudo cp scripts/install/residia-worker.service /etc/systemd/system/residia-worker.service

# Recargar demonios e iniciar servicio
sudo systemctl daemon-reload
sudo systemctl enable residia-worker
sudo systemctl start residia-worker

# Verificar estado
sudo systemctl status residia-worker
```

---

## 7. Credenciales Iniciales de Administración

Una vez completada la instalación, la base de datos viene aprovisionada con el usuario maestro:

- **URL de Ingreso:** `https://residia.tudominio.com/` o `http://IP_SERVIDOR/`
- **Correo Electrónico:** `admin@robledal.com`
- **Contraseña Inicial:** `Admin2026.*`
- **Rol:** `Administrador / Superadmin` (Acceso irrestricto a todos los módulos: Garita, Alícuotas, Inmuebles, Áreas Sociales, Asambleas, Biometría ISAPI, Facility Management y Bitácora CDC).

---

## 8. Verificación Post-Instalación
1. Ingresar con las credenciales maestras.
2. Ir a **Administración $\rightarrow$ Urbanizaciones** y confirmar que la sede activa sea *Urbanización Piloto Robledal* o crear la urbanización definitiva.
3. Confirmar que el banner PWA aparezca al pie de página para permitir a los usuarios instalar la app en su dispositivo móvil.
4. En **Garita Inteligente**, testear el sonido del chime y la simulación de lectura LPR / RFID.
"""

target = "docs/09_despliegue_produccion/manual_despliegue_produccion.md"
with open(target, "w", encoding="utf-8") as f:
    f.write(doc_content)
print("Manual written successfully:", target)
