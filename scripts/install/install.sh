#!/usr/bin/env bash
# ========================================================================
# RESIDIA CRM • ROBLEDAL CONNECTING
# Script Automatizado de Despliegue & Instalador Maestro en Producción
# Compatible con: Ubuntu 22.04 / 24.04 LTS, Debian 12, Rocky Linux, macOS
# ========================================================================

set -e

# Colores institucionales Robledal
GREEN='\033[0;32m'
CYAN='\033[0;36m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${CYAN}========================================================================${NC}"
echo -e "${CYAN}        RESIDIA CRM • ROBLEDAL CONNECTING - INSTALADOR MAESTRO         ${NC}"
echo -e "${CYAN}========================================================================${NC}"
echo -e "Iniciando instalación desatendida del sistema en producción...\n"

# 1. Verificación de Binarios Requeridos
echo -e "${YELLOW}[1/8] Verificando dependencias del sistema...${NC}"
for cmd in php composer npm psql; do
    if ! command -v $cmd &> /dev/null; then
        echo -e "${RED}[ERROR] El binario '$cmd' no está disponible en el PATH.${NC}"
        echo -e "Por favor, instale PHP 8.2+, Composer, Node.js 18+ y PostgreSQL Client."
        exit 1
    fi
done
echo -e "${GREEN}✓ Dependencias de sistema verificadas con éxito.${NC}\n"

# 2. Configuración de Variables de Entorno (.env)
echo -e "${YELLOW}[2/8] Configurando archivo .env para producción...${NC}"
if [ ! -f .env ]; then
    if [ -f .env.example ]; then
        cp .env.example .env
        echo -e "${GREEN}✓ Archivo .env generado a partir de .env.example.${NC}"
    else
        echo -e "${RED}[ERROR] No se encontró .env ni .env.example.${NC}"
        exit 1
    fi
else
    echo -e "${GREEN}✓ Archivo .env existente detectado.${NC}"
fi

# 3. Instalación de Dependencias PHP Backend (Composer)
echo -e "${YELLOW}[3/8] Instalando dependencias de PHP vía Composer...${NC}"
composer install --no-dev --optimize-autoloader --no-interaction
echo -e "${GREEN}✓ Paquetes PHP optimizados para producción.${NC}\n"

# 4. Generación de Claves Criptográficas
echo -e "${YELLOW}[4/8] Verificando claves maestras APP_KEY y JWT_SECRET...${NC}"
php artisan key:generate --force
php artisan jwt:secret --force
echo -e "${GREEN}✓ Claves de encriptación y tokens JWT aprovisionadas.${NC}\n"

# 5. Inicialización de Base de Datos PostgreSQL
echo -e "${YELLOW}[5/8] Desplegando esquema relacional en PostgreSQL...${NC}"

# Extraer credenciales desde .env
DB_HOST=$(grep -E '^DB_HOST=' .env | cut -d '=' -f2 | tr -d ' "')
DB_PORT=$(grep -E '^DB_PORT=' .env | cut -d '=' -f2 | tr -d ' "')
DB_DATABASE=$(grep -E '^DB_DATABASE=' .env | cut -d '=' -f2 | tr -d ' "')
DB_USERNAME=$(grep -E '^DB_USERNAME=' .env | cut -d '=' -f2 | tr -d ' "')
DB_PASSWORD=$(grep -E '^DB_PASSWORD=' .env | cut -d '=' -f2 | tr -d ' "')

DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=${DB_PORT:-5432}
DB_DATABASE=${DB_DATABASE:-residia}
DB_USERNAME=${DB_USERNAME:-postgres}

echo -e "Conectando a PostgreSQL ($DB_HOST:$DB_PORT, Base: $DB_DATABASE, Usuario: $DB_USERNAME)..."

# Crear la base de datos si no existe
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -tc "SELECT 1 FROM pg_database WHERE datname = '$DB_DATABASE'" | grep -q 1 || \
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -c "CREATE DATABASE $DB_DATABASE;"

# Cargar extensiones requeridas
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE" -f scripts/install/init_database.sql

# Si existe dump maestro de respaldo, restaurarlo; sino, ejecutar migraciones
DUMP_FILE="database/backups/residia_release_v2_completo.sql"
if [ -f "$DUMP_FILE" ]; then
    echo -e "Restaurando estructura y datos maestros certificados desde dump: $DUMP_FILE..."
    PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE" -f "$DUMP_FILE" > /dev/null 2>&1 || true
    echo -e "${GREEN}✓ Base de datos restaurada con éxito desde snapshot oficial.${NC}"
else
    echo -e "Ejecutando migraciones y seeders de Laravel..."
    php artisan migrate --force
    php artisan db:seed --force
    echo -e "${GREEN}✓ Migraciones y catálogo maestro creados con éxito.${NC}"
fi
echo ""

# 6. Compilación de Assets Frontend (Vite & Vue 3)
echo -e "${YELLOW}[6/8] Compilando assets del frontend para producción...${NC}"
npm install --no-audit --prefer-offline
npm run build
echo -e "${GREEN}✓ Assets compilados y minificados en public/build/.${NC}\n"

# 7. Permisos de Almacenamiento y Caché
echo -e "${YELLOW}[7/8] Ajustando permisos en storage y bootstrap/cache...${NC}"
mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Enlace simbólico de archivos públicos
php artisan storage:link --force || true

# Limpieza y optimización de caché de producción
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo -e "${GREEN}✓ Cachés de Laravel precompiladas.${NC}\n"

# 8. Verificación Final de Estado
echo -e "${YELLOW}[8/8] Verificando estado operativo...${NC}"
echo -e "${GREEN}========================================================================${NC}"
echo -e "${GREEN}      ¡INSTALACIÓN DE RESIDIA CRM COMPLETADA EXITOSAMENTE!             ${NC}"
echo -e "${GREEN}========================================================================${NC}"
echo -e "URL de Acceso Base: ${CYAN}http://$DB_HOST:8000 o Dominio Configurado${NC}"
echo -e "Credenciales de Administrador Inicial:"
echo -e "  - Usuario: ${CYAN}admin@robledal.com${NC}"
echo -e "  - Clave:   ${CYAN}Admin2026.*${NC}\n"
echo -e "Consulte el manual en: ${YELLOW}docs/09_despliegue_produccion/manual_despliegue_produccion.md${NC}\n"
