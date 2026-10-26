#!/usr/bin/env bash
# ========================================================================
# RESIDIA CRM • ROBLEDAL CONNECTING
# Script Maestro de Aprovisionamiento VPS en Ubuntu Server 25
# Stack: PHP 8.4+, Nginx, PostgreSQL (16/17), Redis, Node.js 22 LTS, Composer
# ========================================================================

set -e

# Colores de salida
GREEN='\033[0;32m'
CYAN='\033[0;36m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${CYAN}========================================================================${NC}"
echo -e "${CYAN}   RESIDIA CRM • APROVISIONAMIENTO COMPLETO EN UBUNTU SERVER 25        ${NC}"
echo -e "${CYAN}========================================================================${NC}"
echo -e "Iniciando instalación del entorno base desde cero con PHP 8.4+...\n"

# Comprobar privilegios root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}[ERROR] Este script debe ejecutarse como root o con sudo.${NC}"
    exit 1
fi

# 1. Actualización del Sistema
echo -e "${YELLOW}[1/8] Actualizando paquetes y repositorios de Ubuntu 25...${NC}"
apt update && apt upgrade -y
apt install -y curl wget gnupg2 ca-certificates lsb-release apt-transport-https \
               software-properties-common git unzip ufw supervisor htop
echo -e "${GREEN}✓ Paquetes esenciales del sistema instalados.${NC}\n"

# 2. Instalación de PHP 8.4 y Extensiones Requeridas
echo -e "${YELLOW}[2/8] Instalando PHP 8.4 y módulos oficiales para Laravel...${NC}"
# Agregar PPA Ondrej PHP si es necesario para asegurar parches actualizados
if ! grep -q "^deb .*ondrej/php" /etc/apt/sources.list /etc/apt/sources.list.d/* 2>/dev/null; then
    add-apt-repository -y ppa:ondrej/php || true
    apt update
fi

apt install -y php8.4 php8.4-fpm php8.4-cli php8.4-common \
               php8.4-pgsql php8.4-pdo-pgsql \
               php8.4-mbstring php8.4-xml php8.4-curl \
               php8.4-zip php8.4-gd php8.4-bcmath \
               php8.4-intl php8.4-redis php8.4-opcache

# Ajustes de rendimiento en php.ini (FPM y CLI)
for ini in /etc/php/8.4/fpm/php.ini /etc/php/8.4/cli/php.ini; do
    if [ -f "$ini" ]; then
        sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 50M/' "$ini"
        sed -i 's/^post_max_size = .*/post_max_size = 50M/' "$ini"
        sed -i 's/^memory_limit = .*/memory_limit = 512M/' "$ini"
        sed -i 's/^max_execution_time = .*/max_execution_time = 300/' "$ini"
    fi
done

systemctl restart php8.4-fpm
systemctl enable php8.4-fpm
echo -e "${GREEN}✓ PHP 8.4-FPM configurado y activo.${NC}\n"

# 3. Instalación de Composer Global
echo -e "${YELLOW}[3/8] Instalando Composer v2...${NC}"
if ! command -v composer &> /dev/null; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi
echo -e "${GREEN}✓ $(composer --version) instalado correctamente.${NC}\n"

# 4. Instalación de Node.js 22 LTS & NPM
echo -e "${YELLOW}[4/8] Instalando Node.js 22 LTS...${NC}"
if ! command -v node &> /dev/null; then
    curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
    apt install -y nodejs
fi
echo -e "${GREEN}✓ Node $(node -v) y NPM $(npm -v) instalados.${NC}\n"

# 5. Instalación y Configuración de PostgreSQL
echo -e "${YELLOW}[5/8] Instalando PostgreSQL y utilidades contrib...${NC}"
apt install -y postgresql postgresql-contrib
systemctl enable --now postgresql
echo -e "${GREEN}✓ PostgreSQL $(psql --version) en ejecución.${NC}\n"

# 6. Instalación de Redis Server
echo -e "${YELLOW}[6/8] Instalando Redis Server (Caché & WebSockets)...${NC}"
apt install -y redis-server
systemctl enable --now redis-server
echo -e "${GREEN}✓ Redis Server activo y protegido.${NC}\n"

# 7. Instalación de Nginx Web Server & Firewall UFW
echo -e "${YELLOW}[7/8] Instalando Nginx y configurando Firewall (UFW)...${NC}"
apt install -y nginx certbot python3-certbot-nginx
systemctl enable --now nginx

# Configurar Firewall básico
ufw allow 22/tcp || true
ufw allow 80/tcp || true
ufw allow 443/tcp || true
echo "y" | ufw enable || true
echo -e "${GREEN}✓ Nginx instalado y puertos 22, 80, 443 abiertos en UFW.${NC}\n"

# 8. Estructura de Directorios de Residia
echo -e "${YELLOW}[8/8] Preparando directorio destino /var/www/residia...${NC}"
mkdir -p /var/www/residia
chown -R www-data:www-data /var/www/residia
chmod -R 775 /var/www/residia

echo -e "${GREEN}========================================================================${NC}"
echo -e "${GREEN}   ¡SERVIDOR UBUNTU 25 APROVISIONADO EXITOSAMENTE PARA RESIDIA!        ${NC}"
echo -e "${GREEN}========================================================================${NC}"
echo -e "Componentes instalados y verificados:"
echo -e "  - PHP:        ${CYAN}$(php -v | head -n 1)${NC}"
echo -e "  - Web Server: ${CYAN}$(nginx -v 2>&1)${NC}"
echo -e "  - Database:   ${CYAN}$(psql --version)${NC}"
echo -e "  - Cache/MQ:   ${CYAN}$(redis-server -v | cut -d ' ' -f1-3)${NC}"
echo -e "  - Node / NPM: ${CYAN}Node $(node -v) / NPM $(npm -v)${NC}"
echo -e "  - Composer:   ${CYAN}$(composer --version | cut -d ' ' -f1-3)${NC}\n"
echo -e "Próximos pasos:"
echo -e "  1. Clonar el repositorio en /var/www/residia: "
echo -e "     ${YELLOW}git clone -b residia https://github.com/vinnyvgs1-ctrl/residia.git /var/www/residia${NC}"
echo -e "  2. Ejecutar el instalador de la aplicación:"
echo -e "     ${YELLOW}cd /var/www/residia && ./scripts/install/install.sh${NC}\n"
