-- ========================================================================
-- RESIDIA CRM • ROBLEDAL CONNECTING
-- Script SQL para Limpieza y Reinicio Seguro de Base de Datos
-- ========================================================================

-- ========================================================================
-- OPCIÓN A: LIMPIEZA TRANSACCIONAL (Reset de Pruebas y Movimientos)
-- Mantiene: Urbanizaciones, Inmuebles, Personas, Vehículos, Cuentas y Roles.
-- Elimina: Visitas en garita, Pases QR, Cobros, Reservas y Auditoría.
-- ========================================================================

BEGIN;

TRUNCATE TABLE 
    op_control_accesos,
    acc_aperturas_contingencia,
    op_invitaciones_qr,
    fin_obligaciones_alicuotas,
    op_reservas_amenidades,
    op_paqueteria,
    op_novedades_turno,
    op_ordenes_mantenimiento,
    op_votos_emitidos,
    op_biometria_sync_logs,
    aud_trazabilidad,
    aud_reportes_descargas,
    sessions
CASCADE;

COMMIT;

-- ========================================================================
-- OPCIÓN B: LIMPIEZA TOTAL (Lanzamiento en Blanco Cero)
-- (Descomentar para usar si se desea vaciar absolutamente todo y dejar
--  únicamente al usuario administrador inicial admin@robledal.co)
-- ========================================================================

/*
BEGIN;

-- 1. Vaciar transaccionales
TRUNCATE TABLE 
    op_control_accesos,
    acc_aperturas_contingencia,
    op_invitaciones_qr,
    fin_obligaciones_alicuotas,
    op_reservas_amenidades,
    op_paqueteria,
    op_novedades_turno,
    op_ordenes_mantenimiento,
    op_votos_emitidos,
    op_biometria_sync_logs,
    aud_trazabilidad,
    aud_reportes_descargas,
    sessions,
    op_biometria_residentes,
    op_lista_negra,
    op_comunicados,
    op_votaciones,
    op_areas_sociales,
    op_activos_infraestructura,
    op_proveedores,
    op_garita_dispositivos,
    op_garitas,
    op_push_subscriptions,
    core_inmueble_vehiculos,
    core_inmueble_residentes,
    core_inmuebles,
    sec_urbanizacion_usuarios,
    core_urbanizaciones
CASCADE;

-- 2. Eliminar usuarios excepto admin@robledal.co
DELETE FROM sec_usuario_roles WHERE uro_user_id NOT IN (SELECT usr_id FROM sec_users WHERE usr_email = 'admin@robledal.co');
DELETE FROM sec_users WHERE usr_email != 'admin@robledal.co';
DELETE FROM core_personas WHERE per_id NOT IN (SELECT usr_persona_id FROM sec_users WHERE usr_email = 'admin@robledal.co');

COMMIT;
*/
