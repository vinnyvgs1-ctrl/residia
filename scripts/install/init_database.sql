-- ========================================================================
-- RESIDIA CRM • ROBLEDAL CONNECTING
-- Script Inicializador Maestro de Base de Datos PostgreSQL 16+
-- ========================================================================

-- 1. Extensiones Criptográficas y de Identificadores UUID
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- 2. Asegurar Esquema y Zona Horaria
SET TIMEZONE = 'America/Guayaquil';

-- 3. Mensaje de Confirmación
DO $$ 
BEGIN 
    RAISE NOTICE 'Extensiones pgcrypto y uuid-ossp inicializadas correctamente en base residia.';
END $$;
