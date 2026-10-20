--
-- PostgreSQL database dump
--

\restrict 3zYUhP6Lq9FVNkgKeiF3YbUAdWpsdzIuns8qM8ozzaov8DM9aeebVb2YYyYkCc7

-- Dumped from database version 18.4
-- Dumped by pg_dump version 18.4

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: acc_aperturas_contingencia; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.acc_aperturas_contingencia (
    apt_id bigint NOT NULL,
    apt_urbanizacion_id uuid NOT NULL,
    apt_guardia_usr_id bigint NOT NULL,
    apt_carril character varying(50) NOT NULL,
    apt_motivo text NOT NULL,
    apt_vehiculo_placa character varying(15),
    apt_inmueble_id bigint,
    apt_fecha_hora timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.acc_aperturas_contingencia OWNER TO postgres;

--
-- Name: acc_aperturas_contingencia_apt_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.acc_aperturas_contingencia_apt_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.acc_aperturas_contingencia_apt_id_seq OWNER TO postgres;

--
-- Name: acc_aperturas_contingencia_apt_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.acc_aperturas_contingencia_apt_id_seq OWNED BY public.acc_aperturas_contingencia.apt_id;


--
-- Name: aud_reportes_descargas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.aud_reportes_descargas (
    rep_id bigint NOT NULL,
    rep_urbanizacion_id uuid,
    rep_user_id bigint,
    rep_modulo character varying(50) NOT NULL,
    rep_formato character varying(15) NOT NULL,
    rep_nombre_archivo character varying(255) NOT NULL,
    rep_filtros_aplicados jsonb,
    rep_total_filas integer DEFAULT 0 NOT NULL,
    rep_ip_address character varying(45),
    rep_created_at timestamp(0) with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.aud_reportes_descargas OWNER TO postgres;

--
-- Name: COLUMN aud_reportes_descargas.rep_modulo; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.aud_reportes_descargas.rep_modulo IS 'Modulo del reporte (accesos, finanzas, inmuebles)';


--
-- Name: COLUMN aud_reportes_descargas.rep_formato; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.aud_reportes_descargas.rep_formato IS 'PDF, EXCEL, CSV';


--
-- Name: COLUMN aud_reportes_descargas.rep_nombre_archivo; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.aud_reportes_descargas.rep_nombre_archivo IS 'Nombre generado del reporte';


--
-- Name: COLUMN aud_reportes_descargas.rep_filtros_aplicados; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.aud_reportes_descargas.rep_filtros_aplicados IS 'Rango de fechas, garitas, estados filtrados';


--
-- Name: COLUMN aud_reportes_descargas.rep_total_filas; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.aud_reportes_descargas.rep_total_filas IS 'Cantidad de registros exportados';


--
-- Name: aud_reportes_descargas_rep_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.aud_reportes_descargas_rep_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.aud_reportes_descargas_rep_id_seq OWNER TO postgres;

--
-- Name: aud_reportes_descargas_rep_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.aud_reportes_descargas_rep_id_seq OWNED BY public.aud_reportes_descargas.rep_id;


--
-- Name: aud_trazabilidad; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.aud_trazabilidad (
    aud_id bigint NOT NULL,
    aud_urbanizacion_id uuid,
    aud_user_id bigint,
    aud_user_nombre character varying(150),
    aud_evento character varying(30) NOT NULL,
    aud_tabla character varying(80) NOT NULL,
    aud_registro_id character varying(50) NOT NULL,
    aud_old_values jsonb,
    aud_new_values jsonb,
    aud_diff jsonb,
    aud_ip_address character varying(45),
    aud_user_agent text,
    aud_created_at timestamp(0) with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.aud_trazabilidad OWNER TO postgres;

--
-- Name: COLUMN aud_trazabilidad.aud_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.aud_trazabilidad.aud_id IS 'Identificador unico del registro de auditoria';


--
-- Name: COLUMN aud_trazabilidad.aud_urbanizacion_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.aud_trazabilidad.aud_urbanizacion_id IS 'Urbanizacion donde ocurrio el evento';


--
-- Name: COLUMN aud_trazabilidad.aud_user_nombre; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.aud_trazabilidad.aud_user_nombre IS 'Nombre del usuario para persistencia historica';


--
-- Name: COLUMN aud_trazabilidad.aud_evento; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.aud_trazabilidad.aud_evento IS 'INSERT, UPDATE, DELETE, RESTORE, LOGIN, LOGOUT, EXPORT_PDF, EXPORT_EXCEL';


--
-- Name: COLUMN aud_trazabilidad.aud_tabla; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.aud_trazabilidad.aud_tabla IS 'Nombre de la tabla afectada (ej. op_control_accesos, core_personas)';


--
-- Name: COLUMN aud_trazabilidad.aud_registro_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.aud_trazabilidad.aud_registro_id IS 'PK del registro afectado';


--
-- Name: COLUMN aud_trazabilidad.aud_old_values; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.aud_trazabilidad.aud_old_values IS 'Estado previo a la modificacion';


--
-- Name: COLUMN aud_trazabilidad.aud_new_values; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.aud_trazabilidad.aud_new_values IS 'Estado posterior a la modificacion';


--
-- Name: COLUMN aud_trazabilidad.aud_diff; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.aud_trazabilidad.aud_diff IS 'Diferencia calculada exacta';


--
-- Name: COLUMN aud_trazabilidad.aud_created_at; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.aud_trazabilidad.aud_created_at IS 'Momento exacto del log';


--
-- Name: aud_trazabilidad_aud_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.aud_trazabilidad_aud_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.aud_trazabilidad_aud_id_seq OWNER TO postgres;

--
-- Name: aud_trazabilidad_aud_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.aud_trazabilidad_aud_id_seq OWNED BY public.aud_trazabilidad.aud_id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache OWNER TO postgres;

--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache_locks OWNER TO postgres;

--
-- Name: core_inmueble_residentes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.core_inmueble_residentes (
    res_id bigint NOT NULL,
    res_inmueble_id bigint NOT NULL,
    res_persona_id bigint NOT NULL,
    res_tipo_relacion character varying(30) DEFAULT 'PROPIETARIO'::character varying NOT NULL,
    res_es_principal boolean DEFAULT false NOT NULL,
    res_activo boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE public.core_inmueble_residentes OWNER TO postgres;

--
-- Name: COLUMN core_inmueble_residentes.res_tipo_relacion; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.core_inmueble_residentes.res_tipo_relacion IS 'PROPIETARIO, ARRENDATARIO, FAMILIAR, EMPLEADO_DOMESTICO';


--
-- Name: COLUMN core_inmueble_residentes.res_es_principal; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.core_inmueble_residentes.res_es_principal IS 'Contacto principal del inmueble';


--
-- Name: core_inmueble_residentes_res_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.core_inmueble_residentes_res_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.core_inmueble_residentes_res_id_seq OWNER TO postgres;

--
-- Name: core_inmueble_residentes_res_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.core_inmueble_residentes_res_id_seq OWNED BY public.core_inmueble_residentes.res_id;


--
-- Name: core_inmueble_vehiculos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.core_inmueble_vehiculos (
    veh_id bigint NOT NULL,
    veh_inmueble_id bigint NOT NULL,
    veh_persona_id bigint,
    veh_placa character varying(15) NOT NULL,
    veh_marca character varying(60),
    veh_modelo character varying(60),
    veh_color character varying(40),
    veh_tipo character varying(30) DEFAULT 'AUTOMOVIL'::character varying NOT NULL,
    veh_tag_rfid character varying(64),
    veh_sticker_lpr character varying(64),
    veh_es_autorizado boolean DEFAULT true NOT NULL,
    veh_observaciones text,
    veh_activo boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE public.core_inmueble_vehiculos OWNER TO postgres;

--
-- Name: core_inmueble_vehiculos_veh_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.core_inmueble_vehiculos_veh_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.core_inmueble_vehiculos_veh_id_seq OWNER TO postgres;

--
-- Name: core_inmueble_vehiculos_veh_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.core_inmueble_vehiculos_veh_id_seq OWNED BY public.core_inmueble_vehiculos.veh_id;


--
-- Name: core_inmuebles; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.core_inmuebles (
    inm_id bigint NOT NULL,
    inm_urbanizacion_id uuid NOT NULL,
    inm_manzana_bloque character varying(30) NOT NULL,
    inm_numero_villa character varying(30) NOT NULL,
    inm_tipo character varying(30) DEFAULT 'CASA'::character varying NOT NULL,
    inm_estado character varying(30) DEFAULT 'HABITADO'::character varying NOT NULL,
    inm_alicuota_porcentaje integer,
    inm_observaciones text,
    inm_activo boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE public.core_inmuebles OWNER TO postgres;

--
-- Name: COLUMN core_inmuebles.inm_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.core_inmuebles.inm_id IS 'Identificador unico del inmueble';


--
-- Name: COLUMN core_inmuebles.inm_manzana_bloque; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.core_inmuebles.inm_manzana_bloque IS 'Manzana, Bloque o Sector';


--
-- Name: COLUMN core_inmuebles.inm_numero_villa; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.core_inmuebles.inm_numero_villa IS 'Numero de Villa, Casa o Departamento';


--
-- Name: COLUMN core_inmuebles.inm_tipo; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.core_inmuebles.inm_tipo IS 'CASA, DEPARTAMENTO, LOTE_VACIO, COMERCIAL';


--
-- Name: COLUMN core_inmuebles.inm_estado; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.core_inmuebles.inm_estado IS 'HABITADO, DESOCUPADO, EN_CONSTRUCCION';


--
-- Name: COLUMN core_inmuebles.inm_alicuota_porcentaje; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.core_inmuebles.inm_alicuota_porcentaje IS 'Porcentaje de alicuota base (x100)';


--
-- Name: core_inmuebles_inm_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.core_inmuebles_inm_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.core_inmuebles_inm_id_seq OWNER TO postgres;

--
-- Name: core_inmuebles_inm_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.core_inmuebles_inm_id_seq OWNED BY public.core_inmuebles.inm_id;


--
-- Name: core_personas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.core_personas (
    per_id bigint NOT NULL,
    per_identificacion character varying(30) NOT NULL,
    per_tipo_identificacion character varying(20) DEFAULT 'CEDULA'::character varying NOT NULL,
    per_nombres character varying(100) NOT NULL,
    per_apellidos character varying(100) NOT NULL,
    per_telefono character varying(30),
    per_email character varying(150),
    per_foto_url character varying(255),
    per_es_vetado boolean DEFAULT false NOT NULL,
    per_motivo_veto text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE public.core_personas OWNER TO postgres;

--
-- Name: COLUMN core_personas.per_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.core_personas.per_id IS 'Identificador unico de la persona fisica';


--
-- Name: COLUMN core_personas.per_identificacion; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.core_personas.per_identificacion IS 'Cedula de identidad, Pasaporte o RUC';


--
-- Name: COLUMN core_personas.per_tipo_identificacion; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.core_personas.per_tipo_identificacion IS 'CEDULA, PASAPORTE, RUC';


--
-- Name: COLUMN core_personas.per_nombres; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.core_personas.per_nombres IS 'Nombres de la persona';


--
-- Name: COLUMN core_personas.per_apellidos; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.core_personas.per_apellidos IS 'Apellidos de la persona';


--
-- Name: COLUMN core_personas.per_telefono; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.core_personas.per_telefono IS 'Telefono o celular principal';


--
-- Name: COLUMN core_personas.per_email; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.core_personas.per_email IS 'Correo electronico de contacto personal';


--
-- Name: COLUMN core_personas.per_foto_url; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.core_personas.per_foto_url IS 'Ruta a foto de perfil o captura de documento';


--
-- Name: COLUMN core_personas.per_es_vetado; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.core_personas.per_es_vetado IS 'Alerta roja de seguridad: persona vetada';


--
-- Name: COLUMN core_personas.per_motivo_veto; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.core_personas.per_motivo_veto IS 'Justificacion del veto en caso de estar activo';


--
-- Name: core_personas_per_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.core_personas_per_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.core_personas_per_id_seq OWNER TO postgres;

--
-- Name: core_personas_per_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.core_personas_per_id_seq OWNED BY public.core_personas.per_id;


--
-- Name: core_urbanizaciones; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.core_urbanizaciones (
    urb_id uuid NOT NULL,
    urb_codigo character varying(30) NOT NULL,
    urb_nombre character varying(150) NOT NULL,
    urb_ruc character varying(20),
    urb_direccion character varying(255) NOT NULL,
    urb_ciudad character varying(100) DEFAULT 'Quito'::character varying NOT NULL,
    urb_telefono character varying(30),
    urb_tema_id bigint,
    urb_configuracion jsonb,
    urb_activo boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE public.core_urbanizaciones OWNER TO postgres;

--
-- Name: COLUMN core_urbanizaciones.urb_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.core_urbanizaciones.urb_id IS 'Identificador UUID unico de la urbanizacion';


--
-- Name: COLUMN core_urbanizaciones.urb_codigo; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.core_urbanizaciones.urb_codigo IS 'Codigo alfanumerico (ej. URB-ROBLEDAL-01)';


--
-- Name: COLUMN core_urbanizaciones.urb_nombre; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.core_urbanizaciones.urb_nombre IS 'Nombre legal o comercial del conjunto / urbanizacion';


--
-- Name: COLUMN core_urbanizaciones.urb_ruc; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.core_urbanizaciones.urb_ruc IS 'RUC o identificacion tributaria';


--
-- Name: COLUMN core_urbanizaciones.urb_direccion; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.core_urbanizaciones.urb_direccion IS 'Direccion geografica';


--
-- Name: COLUMN core_urbanizaciones.urb_ciudad; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.core_urbanizaciones.urb_ciudad IS 'Ciudad donde se ubica la urbanizacion';


--
-- Name: COLUMN core_urbanizaciones.urb_telefono; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.core_urbanizaciones.urb_telefono IS 'Telefono de administracion';


--
-- Name: COLUMN core_urbanizaciones.urb_configuracion; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.core_urbanizaciones.urb_configuracion IS 'Reglas de garita, tiempo maximo de visitas, tolerancias';


--
-- Name: COLUMN core_urbanizaciones.urb_activo; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.core_urbanizaciones.urb_activo IS 'Estado de la suscripcion de la urbanizacion';


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.failed_jobs OWNER TO postgres;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.failed_jobs_id_seq OWNER TO postgres;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: fin_obligaciones_alicuotas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.fin_obligaciones_alicuotas (
    obl_id bigint NOT NULL,
    obl_inmueble_id bigint NOT NULL,
    obl_periodo character varying(7) NOT NULL,
    obl_concepto character varying(120) NOT NULL,
    obl_monto numeric(10,2) DEFAULT 50.00 NOT NULL,
    obl_fecha_emision date DEFAULT CURRENT_DATE NOT NULL,
    obl_fecha_vencimiento date NOT NULL,
    obl_estado character varying(20) DEFAULT 'PENDIENTE'::character varying NOT NULL,
    obl_fecha_pago timestamp without time zone,
    obl_metodo_pago character varying(50),
    obl_comprobante_ref character varying(100),
    obl_observaciones text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    deleted_at timestamp without time zone
);


ALTER TABLE public.fin_obligaciones_alicuotas OWNER TO postgres;

--
-- Name: fin_obligaciones_alicuotas_obl_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.fin_obligaciones_alicuotas_obl_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.fin_obligaciones_alicuotas_obl_id_seq OWNER TO postgres;

--
-- Name: fin_obligaciones_alicuotas_obl_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.fin_obligaciones_alicuotas_obl_id_seq OWNED BY public.fin_obligaciones_alicuotas.obl_id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


ALTER TABLE public.job_batches OWNER TO postgres;

--
-- Name: jobs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


ALTER TABLE public.jobs OWNER TO postgres;

--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.jobs_id_seq OWNER TO postgres;

--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE public.migrations OWNER TO postgres;

--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.migrations_id_seq OWNER TO postgres;

--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: op_activos_infraestructura; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.op_activos_infraestructura (
    act_id bigint NOT NULL,
    act_urbanizacion_id uuid NOT NULL,
    act_codigo character varying(50) NOT NULL,
    act_nombre character varying(150) NOT NULL,
    act_ubicacion character varying(150) NOT NULL,
    act_categoria character varying(50) NOT NULL,
    act_marca_modelo character varying(100),
    act_fecha_instalacion date,
    act_frecuencia_dias_mantenimiento integer DEFAULT 90 CONSTRAINT op_activos_infraestructura_act_frecuencia_dias_manteni_not_null NOT NULL,
    act_ultimo_mantenimiento date,
    act_proximo_mantenimiento date,
    act_estado_operativo character varying(30) DEFAULT 'OPERATIVO'::character varying NOT NULL,
    act_activo boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE public.op_activos_infraestructura OWNER TO postgres;

--
-- Name: op_activos_infraestructura_act_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.op_activos_infraestructura_act_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.op_activos_infraestructura_act_id_seq OWNER TO postgres;

--
-- Name: op_activos_infraestructura_act_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.op_activos_infraestructura_act_id_seq OWNED BY public.op_activos_infraestructura.act_id;


--
-- Name: op_areas_sociales; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.op_areas_sociales (
    are_id bigint NOT NULL,
    are_urbanizacion_id uuid NOT NULL,
    are_nombre character varying(100) NOT NULL,
    are_tipo character varying(50) NOT NULL,
    are_descripcion text,
    are_aforo_maximo integer DEFAULT 20 NOT NULL,
    are_costo_reserva numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    are_garantia_deposito numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    are_hora_apertura time(0) without time zone DEFAULT '08:00:00'::time without time zone NOT NULL,
    are_hora_cierre time(0) without time zone DEFAULT '22:00:00'::time without time zone NOT NULL,
    are_duracion_turno_horas integer DEFAULT 2 NOT NULL,
    are_dias_anticipacion_maxima integer DEFAULT 15 NOT NULL,
    are_requiere_aprobacion boolean DEFAULT false NOT NULL,
    are_activo boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE public.op_areas_sociales OWNER TO postgres;

--
-- Name: op_areas_sociales_are_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.op_areas_sociales_are_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.op_areas_sociales_are_id_seq OWNER TO postgres;

--
-- Name: op_areas_sociales_are_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.op_areas_sociales_are_id_seq OWNED BY public.op_areas_sociales.are_id;


--
-- Name: op_biometria_residentes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.op_biometria_residentes (
    bio_id bigint NOT NULL,
    bio_urbanizacion_id uuid NOT NULL,
    bio_persona_id bigint NOT NULL,
    bio_employee_no character varying(50) NOT NULL,
    bio_tipo_credencial character varying(30) DEFAULT 'FACIAL'::character varying NOT NULL,
    bio_tarjeta_no integer,
    bio_template_data text,
    bio_foto_rostro_path character varying(255),
    bio_estado_sincronizacion character varying(30) DEFAULT 'PENDIENTE'::character varying NOT NULL,
    bio_ultimo_sync timestamp(0) without time zone,
    bio_detalles_dispositivos jsonb,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE public.op_biometria_residentes OWNER TO postgres;

--
-- Name: op_biometria_residentes_bio_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.op_biometria_residentes_bio_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.op_biometria_residentes_bio_id_seq OWNER TO postgres;

--
-- Name: op_biometria_residentes_bio_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.op_biometria_residentes_bio_id_seq OWNED BY public.op_biometria_residentes.bio_id;


--
-- Name: op_biometria_sync_logs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.op_biometria_sync_logs (
    slog_id bigint NOT NULL,
    slog_urbanizacion_id uuid NOT NULL,
    slog_dispositivo_id bigint,
    slog_canal character varying(50) DEFAULT 'ISAPI_DIRECT'::character varying NOT NULL,
    slog_accion character varying(50) NOT NULL,
    slog_total_personas integer DEFAULT 0 NOT NULL,
    slog_exitosos integer DEFAULT 0 NOT NULL,
    slog_fallidos integer DEFAULT 0 NOT NULL,
    slog_detalles jsonb,
    slog_ejecutado_por character varying(100) DEFAULT 'SISTEMA'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.op_biometria_sync_logs OWNER TO postgres;

--
-- Name: op_biometria_sync_logs_slog_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.op_biometria_sync_logs_slog_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.op_biometria_sync_logs_slog_id_seq OWNER TO postgres;

--
-- Name: op_biometria_sync_logs_slog_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.op_biometria_sync_logs_slog_id_seq OWNED BY public.op_biometria_sync_logs.slog_id;


--
-- Name: op_comunicados; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.op_comunicados (
    com_id uuid NOT NULL,
    com_urbanizacion_id uuid NOT NULL,
    com_autor_usr_id bigint NOT NULL,
    com_titulo character varying(200) NOT NULL,
    com_contenido text NOT NULL,
    com_categoria character varying(50) DEFAULT 'GENERAL'::character varying NOT NULL,
    com_prioridad character varying(20) DEFAULT 'NORMAL'::character varying NOT NULL,
    com_fecha_publicacion date DEFAULT '2026-10-08'::date NOT NULL,
    com_fecha_expiracion date,
    com_fijado boolean DEFAULT false NOT NULL,
    com_activo boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE public.op_comunicados OWNER TO postgres;

--
-- Name: op_control_accesos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.op_control_accesos (
    acc_id bigint NOT NULL,
    acc_urbanizacion_id uuid NOT NULL,
    acc_persona_id bigint NOT NULL,
    acc_inmueble_id bigint,
    acc_tipo_ingreso character varying(20) DEFAULT 'VEHICULAR'::character varying NOT NULL,
    acc_rol_visita character varying(30) DEFAULT 'VISITA'::character varying NOT NULL,
    acc_empresa character varying(100),
    acc_vehiculo_placa character varying(20),
    acc_vehiculo_marca character varying(50),
    acc_vehiculo_modelo character varying(50),
    acc_vehiculo_color character varying(30),
    acc_cantidad_ocupantes smallint DEFAULT '1'::smallint NOT NULL,
    acc_ocupantes_detalle jsonb,
    acc_garita_ingreso_id bigint,
    acc_guardia_ingreso_usr_id bigint,
    acc_fecha_ingreso timestamp(0) with time zone NOT NULL,
    acc_garita_salida_id bigint,
    acc_guardia_salida_usr_id bigint,
    acc_fecha_salida timestamp(0) with time zone,
    acc_duracion_minutos integer,
    acc_estado character varying(20) DEFAULT 'DENTRO'::character varying NOT NULL,
    acc_tiempo_excedido boolean DEFAULT false NOT NULL,
    acc_observaciones text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.op_control_accesos OWNER TO postgres;

--
-- Name: COLUMN op_control_accesos.acc_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.op_control_accesos.acc_id IS 'Identificador unico de la transaccion de acceso';


--
-- Name: COLUMN op_control_accesos.acc_tipo_ingreso; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.op_control_accesos.acc_tipo_ingreso IS 'PEATONAL, VEHICULAR';


--
-- Name: COLUMN op_control_accesos.acc_rol_visita; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.op_control_accesos.acc_rol_visita IS 'VISITA, RESIDENTE, DELIVERY, PROVEEDOR, CONTRATISTA, TAXI';


--
-- Name: COLUMN op_control_accesos.acc_empresa; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.op_control_accesos.acc_empresa IS 'Empresa o entidad para delivery/proveedor';


--
-- Name: COLUMN op_control_accesos.acc_vehiculo_placa; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.op_control_accesos.acc_vehiculo_placa IS 'Placa del vehiculo';


--
-- Name: COLUMN op_control_accesos.acc_cantidad_ocupantes; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.op_control_accesos.acc_cantidad_ocupantes IS 'Total de personas dentro del auto';


--
-- Name: COLUMN op_control_accesos.acc_ocupantes_detalle; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.op_control_accesos.acc_ocupantes_detalle IS 'Lista estructurada con cedulas y nombres de acompanantes';


--
-- Name: COLUMN op_control_accesos.acc_fecha_ingreso; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.op_control_accesos.acc_fecha_ingreso IS 'Momento exacto del ingreso';


--
-- Name: COLUMN op_control_accesos.acc_fecha_salida; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.op_control_accesos.acc_fecha_salida IS 'Momento exacto de la salida';


--
-- Name: COLUMN op_control_accesos.acc_duracion_minutos; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.op_control_accesos.acc_duracion_minutos IS 'Calculado al momento de salida';


--
-- Name: COLUMN op_control_accesos.acc_estado; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.op_control_accesos.acc_estado IS 'DENTRO, SALIO, VENCIDO, ANULADO';


--
-- Name: COLUMN op_control_accesos.acc_tiempo_excedido; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.op_control_accesos.acc_tiempo_excedido IS 'Semáforo amarillo/rojo de permanencia';


--
-- Name: op_control_accesos_acc_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.op_control_accesos_acc_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.op_control_accesos_acc_id_seq OWNER TO postgres;

--
-- Name: op_control_accesos_acc_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.op_control_accesos_acc_id_seq OWNED BY public.op_control_accesos.acc_id;


--
-- Name: op_garita_dispositivos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.op_garita_dispositivos (
    disp_id bigint NOT NULL,
    disp_urbanizacion_id uuid NOT NULL,
    disp_nombre character varying(150) NOT NULL,
    disp_tipo character varying(50) NOT NULL,
    disp_ip character varying(45),
    disp_puerto integer DEFAULT 80 NOT NULL,
    disp_protocolo character varying(50) DEFAULT 'HIKVISION_ISAPI'::character varying NOT NULL,
    disp_carril character varying(50) DEFAULT 'ENTRADA_01'::character varying NOT NULL,
    disp_estado_conexion character varying(20) DEFAULT 'ONLINE'::character varying NOT NULL,
    disp_ultimo_ping timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    disp_usuario_api character varying(50) DEFAULT 'admin'::character varying,
    disp_password_api character varying(100),
    disp_modelo character varying(100),
    disp_firmware character varying(100)
);


ALTER TABLE public.op_garita_dispositivos OWNER TO postgres;

--
-- Name: op_garita_dispositivos_disp_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.op_garita_dispositivos_disp_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.op_garita_dispositivos_disp_id_seq OWNER TO postgres;

--
-- Name: op_garita_dispositivos_disp_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.op_garita_dispositivos_disp_id_seq OWNED BY public.op_garita_dispositivos.disp_id;


--
-- Name: op_garitas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.op_garitas (
    gar_id bigint NOT NULL,
    gar_urbanizacion_id uuid NOT NULL,
    gar_nombre character varying(100) NOT NULL,
    gar_ubicacion character varying(255),
    gar_permite_vehicular boolean DEFAULT true NOT NULL,
    gar_permite_peatonal boolean DEFAULT true NOT NULL,
    gar_activo boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE public.op_garitas OWNER TO postgres;

--
-- Name: COLUMN op_garitas.gar_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.op_garitas.gar_id IS 'Identificador de la garita';


--
-- Name: COLUMN op_garitas.gar_nombre; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.op_garitas.gar_nombre IS 'Nombre descriptivo (ej. Garita Principal, Garita de Servicio)';


--
-- Name: COLUMN op_garitas.gar_ubicacion; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.op_garitas.gar_ubicacion IS 'Referencia o puerta de acceso';


--
-- Name: op_garitas_gar_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.op_garitas_gar_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.op_garitas_gar_id_seq OWNER TO postgres;

--
-- Name: op_garitas_gar_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.op_garitas_gar_id_seq OWNED BY public.op_garitas.gar_id;


--
-- Name: op_invitaciones_qr; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.op_invitaciones_qr (
    inv_id uuid NOT NULL,
    inv_urbanizacion_id uuid NOT NULL,
    inv_inmueble_id bigint,
    inv_usuario_creador_id bigint NOT NULL,
    inv_codigo_token character varying(64) NOT NULL,
    inv_visitante_identificacion character varying(30),
    inv_visitante_nombre character varying(150) NOT NULL,
    inv_visitante_placa character varying(15),
    inv_tipo_ingreso character varying(20) DEFAULT 'VEHICULAR'::character varying NOT NULL,
    inv_valido_desde timestamp(0) without time zone NOT NULL,
    inv_valido_hasta timestamp(0) without time zone NOT NULL,
    inv_usos_permitidos integer DEFAULT 1 NOT NULL,
    inv_usos_realizados integer DEFAULT 0 NOT NULL,
    inv_estado character varying(20) DEFAULT 'ACTIVO'::character varying NOT NULL,
    inv_observaciones text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE public.op_invitaciones_qr OWNER TO postgres;

--
-- Name: op_lista_negra; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.op_lista_negra (
    lis_id bigint NOT NULL,
    lis_urbanizacion_id uuid NOT NULL,
    lis_persona_id bigint,
    lis_placa_vehiculo character varying(20),
    lis_motivo text NOT NULL,
    lis_registrado_por_usr_id bigint,
    lis_activo boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE public.op_lista_negra OWNER TO postgres;

--
-- Name: COLUMN op_lista_negra.lis_placa_vehiculo; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.op_lista_negra.lis_placa_vehiculo IS 'Placa vetada aunque cambie de chofer';


--
-- Name: COLUMN op_lista_negra.lis_motivo; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.op_lista_negra.lis_motivo IS 'Razon legal, policial o administrativa del bloqueo';


--
-- Name: op_lista_negra_lis_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.op_lista_negra_lis_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.op_lista_negra_lis_id_seq OWNER TO postgres;

--
-- Name: op_lista_negra_lis_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.op_lista_negra_lis_id_seq OWNED BY public.op_lista_negra.lis_id;


--
-- Name: op_novedades_turno; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.op_novedades_turno (
    nov_id uuid NOT NULL,
    nov_urbanizacion_id uuid NOT NULL,
    nov_guardia_usr_id bigint NOT NULL,
    nov_garita_id bigint,
    nov_tipo character varying(50) DEFAULT 'RONDA'::character varying NOT NULL,
    nov_titulo character varying(150) NOT NULL,
    nov_detalle text NOT NULL,
    nov_prioridad character varying(20) DEFAULT 'NORMAL'::character varying NOT NULL,
    nov_fecha_hora timestamp(0) without time zone DEFAULT '2026-10-08 22:47:03'::timestamp without time zone NOT NULL,
    nov_resuelta boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE public.op_novedades_turno OWNER TO postgres;

--
-- Name: op_ordenes_mantenimiento; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.op_ordenes_mantenimiento (
    ord_id bigint NOT NULL,
    ord_urbanizacion_id uuid NOT NULL,
    ord_codigo_folio character varying(50) NOT NULL,
    ord_activo_id bigint,
    ord_proveedor_id bigint,
    ord_tipo character varying(30) DEFAULT 'PREVENTIVO'::character varying NOT NULL,
    ord_titulo character varying(150) NOT NULL,
    ord_descripcion text NOT NULL,
    ord_prioridad character varying(20) DEFAULT 'MEDIA'::character varying NOT NULL,
    ord_estado character varying(30) DEFAULT 'PENDIENTE'::character varying NOT NULL,
    ord_fecha_programada date NOT NULL,
    ord_fecha_inicio timestamp(0) without time zone,
    ord_fecha_finalizacion timestamp(0) without time zone,
    ord_costo_estimado numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    ord_costo_real numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    ord_responsable_supervision character varying(100),
    ord_informe_tecnico text,
    ord_pre_autoriza_garita boolean DEFAULT true NOT NULL,
    ord_solicitante_usr_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE public.op_ordenes_mantenimiento OWNER TO postgres;

--
-- Name: op_ordenes_mantenimiento_ord_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.op_ordenes_mantenimiento_ord_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.op_ordenes_mantenimiento_ord_id_seq OWNER TO postgres;

--
-- Name: op_ordenes_mantenimiento_ord_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.op_ordenes_mantenimiento_ord_id_seq OWNED BY public.op_ordenes_mantenimiento.ord_id;


--
-- Name: op_paqueteria; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.op_paqueteria (
    paq_id uuid NOT NULL,
    paq_urbanizacion_id uuid NOT NULL,
    paq_inmueble_id bigint NOT NULL,
    paq_guardia_recibe_usr_id bigint NOT NULL,
    paq_guardia_entrega_usr_id bigint,
    paq_empresa_courier character varying(100) NOT NULL,
    paq_codigo_guia character varying(100),
    paq_destinatario_nombre character varying(150),
    paq_descripcion character varying(255),
    paq_estado character varying(20) DEFAULT 'EN_GARITA'::character varying NOT NULL,
    paq_fecha_recepcion timestamp(0) without time zone DEFAULT '2026-10-08 22:47:03'::timestamp without time zone NOT NULL,
    paq_fecha_entrega timestamp(0) without time zone,
    paq_retirado_por character varying(150),
    paq_observaciones text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE public.op_paqueteria OWNER TO postgres;

--
-- Name: op_proveedores; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.op_proveedores (
    prv_id bigint NOT NULL,
    prv_urbanizacion_id uuid NOT NULL,
    prv_razon_social character varying(150) NOT NULL,
    prv_ruc_identificacion character varying(30),
    prv_categoria character varying(50) DEFAULT 'SERVICIOS'::character varying NOT NULL,
    prv_contacto_nombre character varying(100),
    prv_telefono character varying(50),
    prv_email character varying(100),
    prv_direccion text,
    prv_activo boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE public.op_proveedores OWNER TO postgres;

--
-- Name: op_proveedores_prv_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.op_proveedores_prv_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.op_proveedores_prv_id_seq OWNER TO postgres;

--
-- Name: op_proveedores_prv_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.op_proveedores_prv_id_seq OWNED BY public.op_proveedores.prv_id;


--
-- Name: op_push_subscriptions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.op_push_subscriptions (
    sub_id bigint NOT NULL,
    sub_urbanizacion_id uuid NOT NULL,
    sub_user_id bigint,
    sub_persona_id bigint,
    sub_endpoint text NOT NULL,
    sub_p256dh character varying(255),
    sub_auth character varying(255),
    sub_user_agent character varying(255),
    sub_activo boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.op_push_subscriptions OWNER TO postgres;

--
-- Name: op_push_subscriptions_sub_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.op_push_subscriptions_sub_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.op_push_subscriptions_sub_id_seq OWNER TO postgres;

--
-- Name: op_push_subscriptions_sub_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.op_push_subscriptions_sub_id_seq OWNED BY public.op_push_subscriptions.sub_id;


--
-- Name: op_reservas_amenidades; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.op_reservas_amenidades (
    res_id bigint NOT NULL,
    res_urbanizacion_id uuid NOT NULL,
    res_area_id bigint NOT NULL,
    res_inmueble_id bigint NOT NULL,
    res_persona_id bigint NOT NULL,
    res_fecha date NOT NULL,
    res_hora_inicio time(0) without time zone NOT NULL,
    res_hora_fin time(0) without time zone NOT NULL,
    res_cantidad_invitados integer DEFAULT 1 NOT NULL,
    res_monto_pagado numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    res_monto_garantia numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    res_estado character varying(20) DEFAULT 'CONFIRMADA'::character varying NOT NULL,
    res_motivo_evento text,
    res_lista_invitados jsonb,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE public.op_reservas_amenidades OWNER TO postgres;

--
-- Name: op_reservas_amenidades_res_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.op_reservas_amenidades_res_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.op_reservas_amenidades_res_id_seq OWNER TO postgres;

--
-- Name: op_reservas_amenidades_res_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.op_reservas_amenidades_res_id_seq OWNED BY public.op_reservas_amenidades.res_id;


--
-- Name: op_votaciones; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.op_votaciones (
    vot_id bigint NOT NULL,
    vot_urbanizacion_id uuid NOT NULL,
    vot_titulo character varying(150) NOT NULL,
    vot_descripcion text,
    vot_tipo_calculo character varying(30) DEFAULT 'PREDIO_UNICO'::character varying NOT NULL,
    vot_fecha_inicio timestamp(0) without time zone NOT NULL,
    vot_fecha_fin timestamp(0) without time zone NOT NULL,
    vot_opciones jsonb NOT NULL,
    vot_estado character varying(20) DEFAULT 'ABIERTA'::character varying NOT NULL,
    vot_solo_al_dia boolean DEFAULT true NOT NULL,
    vot_creador_usr_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE public.op_votaciones OWNER TO postgres;

--
-- Name: op_votaciones_vot_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.op_votaciones_vot_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.op_votaciones_vot_id_seq OWNER TO postgres;

--
-- Name: op_votaciones_vot_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.op_votaciones_vot_id_seq OWNED BY public.op_votaciones.vot_id;


--
-- Name: op_votos_emitidos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.op_votos_emitidos (
    vto_id bigint NOT NULL,
    vto_urbanizacion_id uuid NOT NULL,
    vto_votacion_id bigint NOT NULL,
    vto_inmueble_id bigint NOT NULL,
    vto_persona_id bigint NOT NULL,
    vto_opcion_id integer NOT NULL,
    vto_peso_voto numeric(8,4) DEFAULT '1'::numeric NOT NULL,
    vto_fecha_hora timestamp(0) without time zone NOT NULL,
    vto_ip_origen character varying(45),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.op_votos_emitidos OWNER TO postgres;

--
-- Name: op_votos_emitidos_vto_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.op_votos_emitidos_vto_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.op_votos_emitidos_vto_id_seq OWNER TO postgres;

--
-- Name: op_votos_emitidos_vto_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.op_votos_emitidos_vto_id_seq OWNED BY public.op_votos_emitidos.vto_id;


--
-- Name: sec_permisos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.sec_permisos (
    prm_id bigint NOT NULL,
    prm_modulo character varying(50) NOT NULL,
    prm_accion character varying(20) NOT NULL,
    prm_slug character varying(80) NOT NULL,
    prm_descripcion character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.sec_permisos OWNER TO postgres;

--
-- Name: COLUMN sec_permisos.prm_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.sec_permisos.prm_id IS 'Identificador unico del permiso';


--
-- Name: COLUMN sec_permisos.prm_modulo; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.sec_permisos.prm_modulo IS 'Modulo funcional (urbanizaciones, inmuebles, personas, accesos, reportes, auditoria, temas)';


--
-- Name: COLUMN sec_permisos.prm_accion; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.sec_permisos.prm_accion IS 'Accion permitida: read, write, update, delete, print';


--
-- Name: COLUMN sec_permisos.prm_slug; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.sec_permisos.prm_slug IS 'Identificador unico compuesto (ej. accesos.read, accesos.print, reportes.print)';


--
-- Name: COLUMN sec_permisos.prm_descripcion; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.sec_permisos.prm_descripcion IS 'Descripcion para el panel de asignacion';


--
-- Name: sec_permisos_prm_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.sec_permisos_prm_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.sec_permisos_prm_id_seq OWNER TO postgres;

--
-- Name: sec_permisos_prm_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.sec_permisos_prm_id_seq OWNED BY public.sec_permisos.prm_id;


--
-- Name: sec_rol_permisos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.sec_rol_permisos (
    rxp_id bigint NOT NULL,
    rxp_rol_id bigint NOT NULL,
    rxp_permiso_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.sec_rol_permisos OWNER TO postgres;

--
-- Name: sec_rol_permisos_rxp_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.sec_rol_permisos_rxp_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.sec_rol_permisos_rxp_id_seq OWNER TO postgres;

--
-- Name: sec_rol_permisos_rxp_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.sec_rol_permisos_rxp_id_seq OWNED BY public.sec_rol_permisos.rxp_id;


--
-- Name: sec_rol_vistas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.sec_rol_vistas (
    rxv_id bigint NOT NULL,
    rxv_rol_id bigint NOT NULL,
    rxv_vista_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.sec_rol_vistas OWNER TO postgres;

--
-- Name: sec_rol_vistas_rxv_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.sec_rol_vistas_rxv_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.sec_rol_vistas_rxv_id_seq OWNER TO postgres;

--
-- Name: sec_rol_vistas_rxv_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.sec_rol_vistas_rxv_id_seq OWNED BY public.sec_rol_vistas.rxv_id;


--
-- Name: sec_roles; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.sec_roles (
    rol_id bigint NOT NULL,
    rol_codigo character varying(50) NOT NULL,
    rol_nombre character varying(100) NOT NULL,
    rol_descripcion character varying(255),
    rol_es_sistema boolean DEFAULT false NOT NULL,
    rol_activo boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE public.sec_roles OWNER TO postgres;

--
-- Name: COLUMN sec_roles.rol_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.sec_roles.rol_id IS 'Identificador unico del rol';


--
-- Name: COLUMN sec_roles.rol_codigo; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.sec_roles.rol_codigo IS 'Slug del rol (ej. superadmin, admin_urbanizacion, guardia_garita, residente)';


--
-- Name: COLUMN sec_roles.rol_nombre; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.sec_roles.rol_nombre IS 'Nombre legible del rol';


--
-- Name: COLUMN sec_roles.rol_descripcion; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.sec_roles.rol_descripcion IS 'Descripcion del alcance y responsabilidades del rol';


--
-- Name: COLUMN sec_roles.rol_es_sistema; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.sec_roles.rol_es_sistema IS 'Protege contra eliminacion si es rol base';


--
-- Name: COLUMN sec_roles.rol_activo; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.sec_roles.rol_activo IS 'Estado del rol';


--
-- Name: sec_roles_rol_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.sec_roles_rol_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.sec_roles_rol_id_seq OWNER TO postgres;

--
-- Name: sec_roles_rol_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.sec_roles_rol_id_seq OWNED BY public.sec_roles.rol_id;


--
-- Name: sec_temas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.sec_temas (
    tem_id bigint NOT NULL,
    tem_codigo character varying(50) NOT NULL,
    tem_nombre character varying(100) NOT NULL,
    tem_descripcion text,
    tem_variables_css jsonb NOT NULL,
    tem_es_oscuro boolean DEFAULT false NOT NULL,
    tem_es_sistema boolean DEFAULT false NOT NULL,
    tem_activo boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE public.sec_temas OWNER TO postgres;

--
-- Name: COLUMN sec_temas.tem_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.sec_temas.tem_id IS 'Identificador unico del tema';


--
-- Name: COLUMN sec_temas.tem_codigo; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.sec_temas.tem_codigo IS 'Codigo identificador del tema (ej. robledal-dark, robledal-light)';


--
-- Name: COLUMN sec_temas.tem_nombre; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.sec_temas.tem_nombre IS 'Nombre descriptivo del tema visual';


--
-- Name: COLUMN sec_temas.tem_descripcion; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.sec_temas.tem_descripcion IS 'Descripcion de uso o contexto del tema';


--
-- Name: COLUMN sec_temas.tem_variables_css; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.sec_temas.tem_variables_css IS 'Diccionario estructurado de variables CSS para inyeccion dinamica';


--
-- Name: COLUMN sec_temas.tem_es_oscuro; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.sec_temas.tem_es_oscuro IS 'Indica si la paleta corresponde a modo oscuro';


--
-- Name: COLUMN sec_temas.tem_es_sistema; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.sec_temas.tem_es_sistema IS 'Indica si es un tema oficial del sistema no eliminable';


--
-- Name: COLUMN sec_temas.tem_activo; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.sec_temas.tem_activo IS 'Estado de disponibilidad del tema';


--
-- Name: sec_temas_tem_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.sec_temas_tem_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.sec_temas_tem_id_seq OWNER TO postgres;

--
-- Name: sec_temas_tem_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.sec_temas_tem_id_seq OWNED BY public.sec_temas.tem_id;


--
-- Name: sec_urbanizacion_usuarios; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.sec_urbanizacion_usuarios (
    uxu_id bigint NOT NULL,
    uxu_urbanizacion_id uuid NOT NULL,
    uxu_user_id bigint NOT NULL,
    uxu_es_predeterminada boolean DEFAULT false NOT NULL,
    uxu_activo boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.sec_urbanizacion_usuarios OWNER TO postgres;

--
-- Name: COLUMN sec_urbanizacion_usuarios.uxu_es_predeterminada; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.sec_urbanizacion_usuarios.uxu_es_predeterminada IS 'Urbanizacion activa al iniciar sesion';


--
-- Name: sec_urbanizacion_usuarios_uxu_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.sec_urbanizacion_usuarios_uxu_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.sec_urbanizacion_usuarios_uxu_id_seq OWNER TO postgres;

--
-- Name: sec_urbanizacion_usuarios_uxu_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.sec_urbanizacion_usuarios_uxu_id_seq OWNED BY public.sec_urbanizacion_usuarios.uxu_id;


--
-- Name: sec_users; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.sec_users (
    usr_id bigint NOT NULL,
    usr_persona_id bigint,
    usr_name character varying(100) NOT NULL,
    usr_email character varying(150) NOT NULL,
    usr_email_verified_at timestamp(0) without time zone,
    usr_password character varying(255) NOT NULL,
    usr_es_superadmin boolean DEFAULT false NOT NULL,
    usr_activo boolean DEFAULT true NOT NULL,
    usr_preferencias jsonb,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE public.sec_users OWNER TO postgres;

--
-- Name: COLUMN sec_users.usr_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.sec_users.usr_id IS 'Identificador unico de la cuenta de login';


--
-- Name: COLUMN sec_users.usr_name; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.sec_users.usr_name IS 'Nombre para mostrar';


--
-- Name: COLUMN sec_users.usr_email; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.sec_users.usr_email IS 'Email para autenticacion en el sistema';


--
-- Name: COLUMN sec_users.usr_password; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.sec_users.usr_password IS 'Hash de contrasena Bcrypt o Argon2';


--
-- Name: COLUMN sec_users.usr_es_superadmin; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.sec_users.usr_es_superadmin IS 'Acceso irrestricto global a todas las urbanizaciones';


--
-- Name: COLUMN sec_users.usr_activo; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.sec_users.usr_activo IS 'Estado de la cuenta: activa o suspendida';


--
-- Name: COLUMN sec_users.usr_preferencias; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.sec_users.usr_preferencias IS 'Preferencias de interfaz (ej. tema nocturno, notificaciones)';


--
-- Name: sec_users_usr_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.sec_users_usr_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.sec_users_usr_id_seq OWNER TO postgres;

--
-- Name: sec_users_usr_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.sec_users_usr_id_seq OWNED BY public.sec_users.usr_id;


--
-- Name: sec_usuario_roles; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.sec_usuario_roles (
    uxr_id bigint NOT NULL,
    uxr_urbanizacion_usuario_id bigint NOT NULL,
    uxr_rol_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.sec_usuario_roles OWNER TO postgres;

--
-- Name: sec_usuario_roles_uxr_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.sec_usuario_roles_uxr_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.sec_usuario_roles_uxr_id_seq OWNER TO postgres;

--
-- Name: sec_usuario_roles_uxr_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.sec_usuario_roles_uxr_id_seq OWNED BY public.sec_usuario_roles.uxr_id;


--
-- Name: sec_vistas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.sec_vistas (
    vis_id bigint NOT NULL,
    vis_codigo character varying(60) NOT NULL,
    vis_nombre character varying(100) NOT NULL,
    vis_categoria character varying(50) NOT NULL,
    vis_icono character varying(40) DEFAULT 'Layout'::character varying NOT NULL,
    vis_descripcion character varying(255),
    vis_orden integer DEFAULT 0 NOT NULL,
    vis_es_menu boolean DEFAULT true NOT NULL,
    vis_activo boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE public.sec_vistas OWNER TO postgres;

--
-- Name: COLUMN sec_vistas.vis_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.sec_vistas.vis_id IS 'Identificador unico de la vista';


--
-- Name: COLUMN sec_vistas.vis_codigo; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.sec_vistas.vis_codigo IS 'Slug identificador (ej. garita, admin.urbanizaciones, admin.inmuebles, sec.usuarios, sec.roles, sec.vistas, sec.auditoria, rep.accesos)';


--
-- Name: COLUMN sec_vistas.vis_nombre; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.sec_vistas.vis_nombre IS 'Nombre visible de la pantalla';


--
-- Name: COLUMN sec_vistas.vis_categoria; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.sec_vistas.vis_categoria IS 'Categoria de agrupacion: OPERACION, ADMINISTRACION, SEGURIDAD, REPORTES';


--
-- Name: COLUMN sec_vistas.vis_icono; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.sec_vistas.vis_icono IS 'Nombre del icono lucide';


--
-- Name: COLUMN sec_vistas.vis_orden; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.sec_vistas.vis_orden IS 'Orden de aparicion en menu';


--
-- Name: COLUMN sec_vistas.vis_es_menu; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.sec_vistas.vis_es_menu IS 'Si se muestra como enlace de navegacion';


--
-- Name: sec_vistas_vis_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.sec_vistas_vis_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.sec_vistas_vis_id_seq OWNER TO postgres;

--
-- Name: sec_vistas_vis_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.sec_vistas_vis_id_seq OWNED BY public.sec_vistas.vis_id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


ALTER TABLE public.sessions OWNER TO postgres;

--
-- Name: acc_aperturas_contingencia apt_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.acc_aperturas_contingencia ALTER COLUMN apt_id SET DEFAULT nextval('public.acc_aperturas_contingencia_apt_id_seq'::regclass);


--
-- Name: aud_reportes_descargas rep_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.aud_reportes_descargas ALTER COLUMN rep_id SET DEFAULT nextval('public.aud_reportes_descargas_rep_id_seq'::regclass);


--
-- Name: aud_trazabilidad aud_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.aud_trazabilidad ALTER COLUMN aud_id SET DEFAULT nextval('public.aud_trazabilidad_aud_id_seq'::regclass);


--
-- Name: core_inmueble_residentes res_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.core_inmueble_residentes ALTER COLUMN res_id SET DEFAULT nextval('public.core_inmueble_residentes_res_id_seq'::regclass);


--
-- Name: core_inmueble_vehiculos veh_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.core_inmueble_vehiculos ALTER COLUMN veh_id SET DEFAULT nextval('public.core_inmueble_vehiculos_veh_id_seq'::regclass);


--
-- Name: core_inmuebles inm_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.core_inmuebles ALTER COLUMN inm_id SET DEFAULT nextval('public.core_inmuebles_inm_id_seq'::regclass);


--
-- Name: core_personas per_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.core_personas ALTER COLUMN per_id SET DEFAULT nextval('public.core_personas_per_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: fin_obligaciones_alicuotas obl_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.fin_obligaciones_alicuotas ALTER COLUMN obl_id SET DEFAULT nextval('public.fin_obligaciones_alicuotas_obl_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: op_activos_infraestructura act_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_activos_infraestructura ALTER COLUMN act_id SET DEFAULT nextval('public.op_activos_infraestructura_act_id_seq'::regclass);


--
-- Name: op_areas_sociales are_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_areas_sociales ALTER COLUMN are_id SET DEFAULT nextval('public.op_areas_sociales_are_id_seq'::regclass);


--
-- Name: op_biometria_residentes bio_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_biometria_residentes ALTER COLUMN bio_id SET DEFAULT nextval('public.op_biometria_residentes_bio_id_seq'::regclass);


--
-- Name: op_biometria_sync_logs slog_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_biometria_sync_logs ALTER COLUMN slog_id SET DEFAULT nextval('public.op_biometria_sync_logs_slog_id_seq'::regclass);


--
-- Name: op_control_accesos acc_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_control_accesos ALTER COLUMN acc_id SET DEFAULT nextval('public.op_control_accesos_acc_id_seq'::regclass);


--
-- Name: op_garita_dispositivos disp_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_garita_dispositivos ALTER COLUMN disp_id SET DEFAULT nextval('public.op_garita_dispositivos_disp_id_seq'::regclass);


--
-- Name: op_garitas gar_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_garitas ALTER COLUMN gar_id SET DEFAULT nextval('public.op_garitas_gar_id_seq'::regclass);


--
-- Name: op_lista_negra lis_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_lista_negra ALTER COLUMN lis_id SET DEFAULT nextval('public.op_lista_negra_lis_id_seq'::regclass);


--
-- Name: op_ordenes_mantenimiento ord_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_ordenes_mantenimiento ALTER COLUMN ord_id SET DEFAULT nextval('public.op_ordenes_mantenimiento_ord_id_seq'::regclass);


--
-- Name: op_proveedores prv_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_proveedores ALTER COLUMN prv_id SET DEFAULT nextval('public.op_proveedores_prv_id_seq'::regclass);


--
-- Name: op_push_subscriptions sub_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_push_subscriptions ALTER COLUMN sub_id SET DEFAULT nextval('public.op_push_subscriptions_sub_id_seq'::regclass);


--
-- Name: op_reservas_amenidades res_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_reservas_amenidades ALTER COLUMN res_id SET DEFAULT nextval('public.op_reservas_amenidades_res_id_seq'::regclass);


--
-- Name: op_votaciones vot_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_votaciones ALTER COLUMN vot_id SET DEFAULT nextval('public.op_votaciones_vot_id_seq'::regclass);


--
-- Name: op_votos_emitidos vto_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_votos_emitidos ALTER COLUMN vto_id SET DEFAULT nextval('public.op_votos_emitidos_vto_id_seq'::regclass);


--
-- Name: sec_permisos prm_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sec_permisos ALTER COLUMN prm_id SET DEFAULT nextval('public.sec_permisos_prm_id_seq'::regclass);


--
-- Name: sec_rol_permisos rxp_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sec_rol_permisos ALTER COLUMN rxp_id SET DEFAULT nextval('public.sec_rol_permisos_rxp_id_seq'::regclass);


--
-- Name: sec_rol_vistas rxv_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sec_rol_vistas ALTER COLUMN rxv_id SET DEFAULT nextval('public.sec_rol_vistas_rxv_id_seq'::regclass);


--
-- Name: sec_roles rol_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sec_roles ALTER COLUMN rol_id SET DEFAULT nextval('public.sec_roles_rol_id_seq'::regclass);


--
-- Name: sec_temas tem_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sec_temas ALTER COLUMN tem_id SET DEFAULT nextval('public.sec_temas_tem_id_seq'::regclass);


--
-- Name: sec_urbanizacion_usuarios uxu_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sec_urbanizacion_usuarios ALTER COLUMN uxu_id SET DEFAULT nextval('public.sec_urbanizacion_usuarios_uxu_id_seq'::regclass);


--
-- Name: sec_users usr_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sec_users ALTER COLUMN usr_id SET DEFAULT nextval('public.sec_users_usr_id_seq'::regclass);


--
-- Name: sec_usuario_roles uxr_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sec_usuario_roles ALTER COLUMN uxr_id SET DEFAULT nextval('public.sec_usuario_roles_uxr_id_seq'::regclass);


--
-- Name: sec_vistas vis_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sec_vistas ALTER COLUMN vis_id SET DEFAULT nextval('public.sec_vistas_vis_id_seq'::regclass);


--
-- Data for Name: acc_aperturas_contingencia; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.acc_aperturas_contingencia (apt_id, apt_urbanizacion_id, apt_guardia_usr_id, apt_carril, apt_motivo, apt_vehiculo_placa, apt_inmueble_id, apt_fecha_hora, created_at, updated_at) FROM stdin;
1	ccce9ae7-ca93-48e7-8577-0dd546dda010	1	BARRERA_ENTRADA_01	Corte temporal de suministro eléctrico perimetral	PBA-1000	\N	2026-10-19 21:14:23	2026-10-19 21:14:23	2026-10-19 21:14:23
\.


--
-- Data for Name: aud_reportes_descargas; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.aud_reportes_descargas (rep_id, rep_urbanizacion_id, rep_user_id, rep_modulo, rep_formato, rep_nombre_archivo, rep_filtros_aplicados, rep_total_filas, rep_ip_address, rep_created_at) FROM stdin;
\.


--
-- Data for Name: aud_trazabilidad; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.aud_trazabilidad (aud_id, aud_urbanizacion_id, aud_user_id, aud_user_nombre, aud_evento, aud_tabla, aud_registro_id, aud_old_values, aud_new_values, aud_diff, aud_ip_address, aud_user_agent, aud_created_at) FROM stdin;
1	ccce9ae7-ca93-48e7-8577-0dd546dda010	1	Admin Robledal	INITIAL_SYSTEM_SEED	core_urbanizaciones	ccce9ae7-ca93-48e7-8577-0dd546dda010	\N	{"codigo": "URB-ROBLEDAL-01", "nombre": "Urbanización Piloto Robledal"}	\N	127.0.0.1	CLI DatabaseSeeder Seed	2026-10-09 02:15:40-05
2	\N	\N	SISTEMA	UPDATE	sec_temas	1	{"tem_nombre": "Robledal Dark (Oficial)", "updated_at": "2026-10-09T02:15:40.000000Z"}	{"tem_nombre": "Robledal Dark (Oficial) - Audit Test", "updated_at": "2026-10-09 02:19:40"}	{"tem_nombre": {"nuevo": "Robledal Dark (Oficial) - Audit Test", "anterior": "Robledal Dark (Oficial)"}}	127.0.0.1	Symfony	2026-10-09 02:20:11-05
3	ccce9ae7-ca93-48e7-8577-0dd546dda010	\N	SISTEMA	INSERT	core_inmuebles	2	\N	{"inm_id": 2, "inm_tipo": "CASA", "created_at": "2026-10-09 03:14:53", "inm_activo": true, "inm_estado": "HABITADO", "updated_at": "2026-10-09 03:14:53", "inm_numero_villa": "Villa 01", "inm_manzana_bloque": "Manzana 01", "inm_urbanizacion_id": "ccce9ae7-ca93-48e7-8577-0dd546dda010", "inm_alicuota_porcentaje": 100}	\N	127.0.0.1	Symfony	2026-10-19 21:41:26-05
4	ccce9ae7-ca93-48e7-8577-0dd546dda010	\N	SISTEMA	INSERT	core_inmuebles	3	\N	{"inm_id": 3, "inm_tipo": "CASA", "created_at": "2026-10-09 03:14:53", "inm_activo": true, "inm_estado": "HABITADO", "updated_at": "2026-10-09 03:14:53", "inm_numero_villa": "Villa 02", "inm_manzana_bloque": "Manzana 01", "inm_urbanizacion_id": "ccce9ae7-ca93-48e7-8577-0dd546dda010", "inm_alicuota_porcentaje": 100}	\N	127.0.0.1	Symfony	2026-10-19 21:41:26-05
5	ccce9ae7-ca93-48e7-8577-0dd546dda010	\N	SISTEMA	INSERT	core_inmuebles	4	\N	{"inm_id": 4, "inm_tipo": "CASA", "created_at": "2026-10-09 03:14:53", "inm_activo": true, "inm_estado": "HABITADO", "updated_at": "2026-10-09 03:14:53", "inm_numero_villa": "Villa 05", "inm_manzana_bloque": "Manzana 02", "inm_urbanizacion_id": "ccce9ae7-ca93-48e7-8577-0dd546dda010", "inm_alicuota_porcentaje": 100}	\N	127.0.0.1	Symfony	2026-10-19 21:41:26-05
6	ccce9ae7-ca93-48e7-8577-0dd546dda010	\N	SISTEMA	INSERT	core_inmuebles	5	\N	{"inm_id": 5, "inm_tipo": "DEPARTAMENTO", "created_at": "2026-10-09 03:14:53", "inm_activo": true, "inm_estado": "HABITADO", "updated_at": "2026-10-09 03:14:53", "inm_numero_villa": "Depto 302", "inm_manzana_bloque": "Edificio Roble A", "inm_urbanizacion_id": "ccce9ae7-ca93-48e7-8577-0dd546dda010", "inm_alicuota_porcentaje": 100}	\N	127.0.0.1	Symfony	2026-10-19 21:41:26-05
7	\N	\N	SISTEMA	INSERT	core_personas	2	\N	{"per_id": 2, "per_email": "fgarcia@gmail.com", "created_at": "2026-10-09 03:14:53", "updated_at": "2026-10-09 03:14:53", "per_nombres": "Fernando", "per_telefono": "0998881111", "per_apellidos": "García Moreno", "per_identificacion": "1710000001"}	\N	127.0.0.1	Symfony	2026-10-19 21:41:26-05
8	\N	\N	SISTEMA	INSERT	core_personas	3	\N	{"per_id": 3, "per_email": "vparedes@gmail.com", "created_at": "2026-10-09 03:14:53", "updated_at": "2026-10-09 03:14:53", "per_nombres": "Valeria", "per_telefono": "0998882222", "per_apellidos": "Paredes Cruz", "per_identificacion": "1710000002"}	\N	127.0.0.1	Symfony	2026-10-19 21:41:26-05
9	\N	\N	SISTEMA	INSERT	core_personas	4	\N	{"per_id": 4, "per_email": "mandrade@gmail.com", "created_at": "2026-10-09 03:14:53", "updated_at": "2026-10-09 03:14:53", "per_nombres": "Mauricio", "per_telefono": "0998883333", "per_apellidos": "Andrade Solís", "per_identificacion": "1710000003"}	\N	127.0.0.1	Symfony	2026-10-19 21:41:26-05
10	\N	\N	SISTEMA	INSERT	core_personas	5	\N	{"per_id": 5, "created_at": "2026-10-09 03:14:53", "updated_at": "2026-10-09 03:14:53", "per_nombres": "Rodrigo", "per_apellidos": "Salazar Vintimilla", "per_es_vetado": true, "per_motivo_veto": "Altercado y amenazas a guardia de turno", "per_identificacion": "1799988877"}	\N	127.0.0.1	Symfony	2026-10-19 21:41:26-05
11	\N	\N	SISTEMA	INSERT	core_personas	6	\N	{"per_id": 6, "per_email": "guardia1@robledal.com", "created_at": "2026-10-09 03:26:28", "updated_at": "2026-10-09 03:26:28", "per_nombres": "Manuel", "per_telefono": "0987654321", "per_apellidos": "Toapanta Chasi", "per_es_vetado": false, "per_identificacion": "1755544433"}	\N	127.0.0.1	Symfony	2026-10-19 21:41:26-05
12	\N	\N	SISTEMA	INSERT	sec_users	2	\N	{"usr_id": 2, "usr_name": "Manuel Toapanta (Guardia Garita Norte)", "usr_email": "guardia1@robledal.com", "created_at": "2026-10-09 03:26:28", "updated_at": "2026-10-09 03:26:28", "usr_activo": true, "usr_persona_id": 6, "usr_es_superadmin": false}	\N	127.0.0.1	Symfony	2026-10-19 21:41:26-05
13	\N	\N	SISTEMA	INSERT	sec_users	3	\N	{"usr_id": 3, "usr_name": "Fernando García (Residente)", "usr_email": "residente1@robledal.com", "created_at": "2026-10-09 03:28:56", "updated_at": "2026-10-09 03:28:56", "usr_activo": true, "usr_persona_id": 2, "usr_es_superadmin": false}	\N	127.0.0.1	Symfony	2026-10-19 21:41:26-05
14	ccce9ae7-ca93-48e7-8577-0dd546dda010	\N	SISTEMA	RESIDENT_QR_PASS_CREATED	op_invitaciones_qr	98438ff9-a39b-43d7-a39b-084764759df8	\N	{"token": "QR-8PLAV4-456", "visitante": "Dra. Sofía Noboa", "creado_por": "Fernando García (Residente)"}	\N	127.0.0.1	\N	2026-10-19 21:41:26-05
15	ccce9ae7-ca93-48e7-8577-0dd546dda010	\N	SISTEMA	RESIDENT_QR_PASS_CREATED	op_invitaciones_qr	4de16779-870c-4ac3-b3f0-19adceab500c	\N	{"token": "QR-YYO1IW-296", "visitante": "Ing. Carlos Mendoza", "creado_por": "Fernando García (Residente)"}	\N	127.0.0.1	\N	2026-10-19 21:41:26-05
16	ccce9ae7-ca93-48e7-8577-0dd546dda010	\N	SISTEMA	RESIDENT_QR_PASS_CREATED	op_invitaciones_qr	88a50051-125c-43df-b3ef-5138ffc45cfc	\N	{"token": "QR-VFE6NK-227", "visitante": "Dra. Andrea Morales", "creado_por": "Fernando García (Residente)"}	\N	127.0.0.1	\N	2026-10-19 21:41:26-05
17	ccce9ae7-ca93-48e7-8577-0dd546dda010	\N	SISTEMA	RESIDENT_QR_PASS_CREATED	op_invitaciones_qr	f1766f1d-29e6-4a77-bd4f-8ec429883704	\N	{"token": "QR-NNX6CX-899", "visitante": "Dra. Andrea Morales", "inmueble_id": 2}	\N	127.0.0.1	\N	2026-10-19 21:41:26-05
18	\N	\N	SISTEMA	RESIDENT_QR_PASS_CREATED	op_invitaciones_qr	1f8b0d3d-07a1-4204-b23c-c70e4aaa885c	\N	{"token": "QR-J9NWA9-168", "visitante": "Test Error", "inmueble_id": 2}	\N	127.0.0.1	Symfony	2026-10-19 21:41:26-05
19	ccce9ae7-ca93-48e7-8577-0dd546dda010	\N	SISTEMA	INSERT	core_personas	7	\N	{"per_id": 7, "created_at": "2026-10-08 22:34:59", "updated_at": "2026-10-08 22:34:59", "per_nombres": "Test Error", "per_apellidos": "(Invitado QR)", "per_identificacion": "QR-1f8b0d3d"}	\N	127.0.0.1	\N	2026-10-19 21:41:26-05
20	ccce9ae7-ca93-48e7-8577-0dd546dda010	\N	SISTEMA	INSERT	op_control_accesos	3	\N	{"acc_id": 3, "acc_estado": "DENTRO", "created_at": "2026-10-08 22:34:59", "updated_at": "2026-10-08 22:34:59", "acc_persona_id": 7, "acc_rol_visita": "VISITANTE", "acc_inmueble_id": 2, "acc_tipo_ingreso": "VEHICULAR", "acc_fecha_ingreso": "2026-10-08 22:34:59", "acc_observaciones": "Ingreso mediante Pase QR [QR-J9NWA9-168]. ", "acc_vehiculo_placa": null, "acc_urbanizacion_id": "ccce9ae7-ca93-48e7-8577-0dd546dda010", "acc_cantidad_ocupantes": 1}	\N	127.0.0.1	\N	2026-10-19 21:41:26-05
21	ccce9ae7-ca93-48e7-8577-0dd546dda010	\N	SISTEMA	QR_PASS_REDEEMED	op_invitaciones_qr	1f8b0d3d-07a1-4204-b23c-c70e4aaa885c	\N	{"token": "QR-J9NWA9-168", "acceso_id": 3}	\N	127.0.0.1	\N	2026-10-19 21:41:26-05
22	ccce9ae7-ca93-48e7-8577-0dd546dda010	1	Admin Robledal	INSERT	op_garitas	2	\N	{"gar_id": 2, "created_at": "2026-10-08 22:40:38", "gar_activo": true, "gar_nombre": "Garita Sur (Proveedores & Servicios)", "updated_at": "2026-10-08 22:40:38", "gar_ubicacion": "Calle Secundaria B", "gar_urbanizacion_id": "ccce9ae7-ca93-48e7-8577-0dd546dda010", "gar_permite_peatonal": true, "gar_permite_vehicular": true}	\N	127.0.0.1	\N	2026-10-19 21:41:26-05
23	ccce9ae7-ca93-48e7-8577-0dd546dda010	1	Admin Robledal	GARITA_CREATED	op_garitas	2	\N	{"nombre": "Garita Sur (Proveedores & Servicios)", "urbanizacion_id": "ccce9ae7-ca93-48e7-8577-0dd546dda010"}	\N	127.0.0.1	\N	2026-10-19 21:41:26-05
24	ccce9ae7-ca93-48e7-8577-0dd546dda010	\N	SISTEMA	INSERT	core_personas	8	\N	{"per_id": 8, "created_at": "2026-10-08 22:41:01", "updated_at": "2026-10-08 22:41:01", "per_nombres": "Ing. Marcelo", "per_apellidos": "Cevallos", "per_identificacion": "1722334455"}	\N	127.0.0.1	\N	2026-10-19 21:41:26-05
25	ccce9ae7-ca93-48e7-8577-0dd546dda010	\N	SISTEMA	INSERT	op_control_accesos	5	\N	{"acc_id": 5, "acc_estado": "DENTRO", "created_at": "2026-10-08 22:41:01", "updated_at": "2026-10-08 22:41:01", "acc_persona_id": 8, "acc_rol_visita": "PROVEEDOR", "acc_inmueble_id": 2, "acc_tipo_ingreso": "VEHICULAR", "acc_fecha_ingreso": "2026-10-08 22:41:01", "acc_observaciones": "Ingreso por Garita Sur de Proveedores", "acc_vehiculo_color": null, "acc_vehiculo_marca": null, "acc_vehiculo_placa": "P-GARITASUR", "acc_urbanizacion_id": "ccce9ae7-ca93-48e7-8577-0dd546dda010", "acc_garita_ingreso_id": 2, "acc_ocupantes_detalle": null, "acc_cantidad_ocupantes": 1, "acc_guardia_ingreso_usr_id": 1}	\N	127.0.0.1	\N	2026-10-19 21:41:26-05
26	ccce9ae7-ca93-48e7-8577-0dd546dda010	\N	SISTEMA	INSERT	core_personas	9	\N	{"per_id": 9, "created_at": "2026-10-08 22:41:41", "updated_at": "2026-10-08 22:41:41", "per_nombres": "Arq. Gabriela", "per_apellidos": "Ponce", "per_identificacion": "1733445566"}	\N	127.0.0.1	\N	2026-10-19 21:41:26-05
27	ccce9ae7-ca93-48e7-8577-0dd546dda010	\N	SISTEMA	INSERT	op_control_accesos	6	\N	{"acc_id": 6, "acc_estado": "DENTRO", "created_at": "2026-10-08 22:41:41", "updated_at": "2026-10-08 22:41:41", "acc_persona_id": 9, "acc_rol_visita": "PROVEEDOR", "acc_inmueble_id": 2, "acc_tipo_ingreso": "VEHICULAR", "acc_fecha_ingreso": "2026-10-08 22:41:41", "acc_observaciones": "Inspección de obra ingresando por Garita Sur", "acc_vehiculo_color": null, "acc_vehiculo_marca": null, "acc_vehiculo_placa": "P-MULTIGARITA", "acc_urbanizacion_id": "ccce9ae7-ca93-48e7-8577-0dd546dda010", "acc_garita_ingreso_id": 2, "acc_ocupantes_detalle": null, "acc_cantidad_ocupantes": 1, "acc_guardia_ingreso_usr_id": 1}	\N	127.0.0.1	\N	2026-10-19 21:41:26-05
28	ccce9ae7-ca93-48e7-8577-0dd546dda010	\N	SISTEMA	UPDATE	op_control_accesos	6	{"acc_estado": "DENTRO", "acc_fecha_salida": null, "acc_observaciones": "Inspección de obra ingresando por Garita Sur", "acc_duracion_minutos": null, "acc_garita_salida_id": null, "acc_guardia_salida_usr_id": null}	{"acc_estado": "SALIO", "acc_fecha_salida": "2026-10-08 22:41:41", "acc_observaciones": "Inspección de obra ingresando por Garita Sur\\nSalida completada por Garita Principal Norte", "acc_duracion_minutos": 0, "acc_garita_salida_id": 1, "acc_guardia_salida_usr_id": 1}	{"acc_estado": {"nuevo": "SALIO", "anterior": "DENTRO"}, "acc_fecha_salida": {"nuevo": "2026-10-08 22:41:41", "anterior": null}, "acc_observaciones": {"nuevo": "Inspección de obra ingresando por Garita Sur\\nSalida completada por Garita Principal Norte", "anterior": "Inspección de obra ingresando por Garita Sur"}, "acc_duracion_minutos": {"nuevo": 0, "anterior": null}, "acc_garita_salida_id": {"nuevo": 1, "anterior": null}, "acc_guardia_salida_usr_id": {"nuevo": 1, "anterior": null}}	127.0.0.1	\N	2026-10-19 21:41:26-05
29	ccce9ae7-ca93-48e7-8577-0dd546dda010	1	Admin Robledal	INSERT	op_comunicados	f190443c-4759-41c4-8b00-ca6bfd7624d6	\N	{"com_id": "f190443c-4759-41c4-8b00-ca6bfd7624d6", "com_activo": true, "com_fijado": true, "com_titulo": "Mantenimiento Preventivo de Bomba de Agua Principal", "created_at": "2026-10-08 22:44:52", "updated_at": "2026-10-08 22:44:52", "com_categoria": "MANTENIMIENTO", "com_contenido": "Estimados residentes, este viernes de 09:00 a 12:00 se realizará el mantenimiento de la cisterna central. Rogamos tomar las precauciones necesarias.", "com_prioridad": "ALTA", "com_autor_usr_id": 1, "com_urbanizacion_id": "ccce9ae7-ca93-48e7-8577-0dd546dda010", "com_fecha_expiracion": null, "com_fecha_publicacion": "2026-10-08 00:00:00"}	\N	127.0.0.1	\N	2026-10-19 21:41:26-05
30	ccce9ae7-ca93-48e7-8577-0dd546dda010	1	Admin Robledal	COMUNICADO_CREATED	op_comunicados	f190443c-4759-41c4-8b00-ca6bfd7624d6	\N	{"titulo": "Mantenimiento Preventivo de Bomba de Agua Principal", "categoria": "MANTENIMIENTO", "prioridad": "ALTA"}	\N	127.0.0.1	\N	2026-10-19 21:41:26-05
31	ccce9ae7-ca93-48e7-8577-0dd546dda010	1	Admin Robledal	INSERT	op_comunicados	f476df78-5dd1-45d6-94fc-c806d23d38b3	\N	{"com_id": "f476df78-5dd1-45d6-94fc-c806d23d38b3", "com_activo": true, "com_fijado": false, "com_titulo": "Recordatorio de Seguridad: Límite de Velocidad Interna 20 km/h", "created_at": "2026-10-08 22:44:52", "updated_at": "2026-10-08 22:44:52", "com_categoria": "SEGURIDAD", "com_contenido": "Por la seguridad de nuestros niños y mascotas que transitan por las vías principales, recordamos a todos los conductores respetar el límite de 20 km/h.", "com_prioridad": "NORMAL", "com_autor_usr_id": 1, "com_urbanizacion_id": "ccce9ae7-ca93-48e7-8577-0dd546dda010", "com_fecha_expiracion": null, "com_fecha_publicacion": "2026-10-08 00:00:00"}	\N	127.0.0.1	\N	2026-10-19 21:41:26-05
32	ccce9ae7-ca93-48e7-8577-0dd546dda010	1	Admin Robledal	COMUNICADO_CREATED	op_comunicados	f476df78-5dd1-45d6-94fc-c806d23d38b3	\N	{"titulo": "Recordatorio de Seguridad: Límite de Velocidad Interna 20 km/h", "categoria": "SEGURIDAD", "prioridad": "NORMAL"}	\N	127.0.0.1	\N	2026-10-19 21:41:26-05
33	ccce9ae7-ca93-48e7-8577-0dd546dda010	2	Manuel Toapanta (Guardia Garita Norte)	INSERT	op_paqueteria	67165b5a-e4f6-4fe2-8e01-b4908c85da4b	\N	{"paq_id": "67165b5a-e4f6-4fe2-8e01-b4908c85da4b", "created_at": "2026-10-08 22:47:55", "paq_estado": "EN_GARITA", "updated_at": "2026-10-08 22:47:55", "paq_codigo_guia": "SRV-99887766", "paq_descripcion": "Caja mediana rotulada", "paq_inmueble_id": 2, "paq_observaciones": "Dejado por motorizado de Servientrega a las 10:45", "paq_empresa_courier": "Servientrega", "paq_fecha_recepcion": "2026-10-08 22:47:55", "paq_urbanizacion_id": "ccce9ae7-ca93-48e7-8577-0dd546dda010", "paq_destinatario_nombre": "Fernando García", "paq_guardia_recibe_usr_id": 2}	\N	127.0.0.1	\N	2026-10-19 21:41:26-05
34	ccce9ae7-ca93-48e7-8577-0dd546dda010	2	Manuel Toapanta (Guardia Garita Norte)	PACKAGE_RECEIVED	op_paqueteria	67165b5a-e4f6-4fe2-8e01-b4908c85da4b	\N	{"guia": "SRV-99887766", "courier": "Servientrega", "inmueble_id": 2}	\N	127.0.0.1	\N	2026-10-19 21:41:26-05
35	ccce9ae7-ca93-48e7-8577-0dd546dda010	2	Manuel Toapanta (Guardia Garita Norte)	INSERT	op_novedades_turno	a4bcd589-f829-4eb3-a2ea-3da6dbeb2b35	\N	{"nov_id": "a4bcd589-f829-4eb3-a2ea-3da6dbeb2b35", "nov_tipo": "RONDA", "created_at": "2026-10-08 22:47:56", "nov_titulo": "Ronda Perimetral Sector Manzana 01 y Garita Norte", "updated_at": "2026-10-08 22:47:56", "nov_detalle": "Cerco eléctrico con voltaje nominal 9.8kV, iluminación perimetral operativa al 100%, sin novedades.", "nov_resuelta": false, "nov_garita_id": 1, "nov_prioridad": "NORMAL", "nov_fecha_hora": "2026-10-08 22:47:56", "nov_guardia_usr_id": 2, "nov_urbanizacion_id": "ccce9ae7-ca93-48e7-8577-0dd546dda010"}	\N	127.0.0.1	\N	2026-10-19 21:41:26-05
36	ccce9ae7-ca93-48e7-8577-0dd546dda010	2	Manuel Toapanta (Guardia Garita Norte)	NOVEDAD_REGISTERED	op_novedades_turno	a4bcd589-f829-4eb3-a2ea-3da6dbeb2b35	\N	{"tipo": "RONDA", "titulo": "Ronda Perimetral Sector Manzana 01 y Garita Norte", "prioridad": "NORMAL"}	\N	127.0.0.1	\N	2026-10-19 21:41:26-05
37	ccce9ae7-ca93-48e7-8577-0dd546dda010	2	Manuel Toapanta (Guardia Garita Norte)	UPDATE	op_paqueteria	67165b5a-e4f6-4fe2-8e01-b4908c85da4b	{"paq_estado": "EN_GARITA", "updated_at": "2026-10-09T03:47:55.000000Z", "paq_retirado_por": null, "paq_fecha_entrega": null, "paq_observaciones": "Dejado por motorizado de Servientrega a las 10:45", "paq_guardia_entrega_usr_id": null}	{"paq_estado": "ENTREGADO", "updated_at": "2026-10-08 22:49:22", "paq_retirado_por": "Fernando García (Propietario)", "paq_fecha_entrega": "2026-10-08 22:49:22", "paq_observaciones": "Dejado por motorizado de Servientrega a las 10:45\\nRetiro: Retiro presencial en garita, verificado con cédula", "paq_guardia_entrega_usr_id": 2}	{"paq_estado": {"nuevo": "ENTREGADO", "anterior": "EN_GARITA"}, "paq_retirado_por": {"nuevo": "Fernando García (Propietario)", "anterior": null}, "paq_fecha_entrega": {"nuevo": "2026-10-08 22:49:22", "anterior": null}, "paq_observaciones": {"nuevo": "Dejado por motorizado de Servientrega a las 10:45\\nRetiro: Retiro presencial en garita, verificado con cédula", "anterior": "Dejado por motorizado de Servientrega a las 10:45"}, "paq_guardia_entrega_usr_id": {"nuevo": 2, "anterior": null}}	127.0.0.1	\N	2026-10-19 21:41:26-05
38	ccce9ae7-ca93-48e7-8577-0dd546dda010	2	Manuel Toapanta (Guardia Garita Norte)	PACKAGE_DELIVERED	op_paqueteria	67165b5a-e4f6-4fe2-8e01-b4908c85da4b	\N	{"retirado_por": "Fernando García (Propietario)", "guardia_entrega": "Manuel Toapanta (Guardia Garita Norte)"}	\N	127.0.0.1	\N	2026-10-19 21:41:26-05
39	ccce9ae7-ca93-48e7-8577-0dd546dda010	1	Admin Robledal	INSERT	core_personas	10	\N	{"per_id": 10, "per_email": "vinnyvgs@hotmail.com", "created_at": "2026-10-08 23:09:29", "updated_at": "2026-10-08 23:09:29", "per_nombres": "VINICIO FABIAN", "per_telefono": "0995338630", "per_apellidos": "TAPIA CABRERA", "per_es_vetado": false, "per_identificacion": "1717677503", "per_tipo_identificacion": "CEDULA"}	\N	127.0.0.1	\N	2026-10-19 21:41:26-05
40	ccce9ae7-ca93-48e7-8577-0dd546dda010	1	Admin Robledal	PERSONA_CREATED	core_personas	10	\N	{"nombre": "VINICIO FABIAN TAPIA CABRERA", "identificacion": "1717677503"}	\N	127.0.0.1	\N	2026-10-19 21:41:26-05
41	ccce9ae7-ca93-48e7-8577-0dd546dda010	1	Admin Robledal	PERSONA_UPDATED	core_personas	10	\N	{"per_email": "vinnyvgs@hotmail.com", "per_nombres": "VINICIO FABIAN", "per_telefono": "0995338630", "per_apellidos": "TAPIA CABRERA", "per_es_vetado": false, "per_motivo_veto": null, "per_identificacion": "1717677503", "per_tipo_identificacion": "CEDULA"}	\N	127.0.0.1	Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36	2026-10-19 21:41:26-05
42	ccce9ae7-ca93-48e7-8577-0dd546dda010	1	Admin Robledal	MASSIVE_ALICUOTA_ISSUED	fin_obligaciones_alicuotas	ccce9ae7-ca93-48e7-8577-0dd546dda010	\N	{"periodo": "2026-10", "concepto": "Alícuota Ordinaria Mantenimiento Octubre 2026", "emitidas": 5, "total_monto": 250}	\N	127.0.0.1	Python-urllib/3.9	2026-10-19 21:41:26-05
43	ccce9ae7-ca93-48e7-8577-0dd546dda010	\N	SISTEMA	RESIDENT_QR_PASS_CREATED	op_invitaciones_qr	d829125b-618a-4583-8ea9-610667286a04	\N	{"token": "QR-5Y28C8-665", "visitante": "Carlos Echeverría (Proveedor Técnico)", "inmueble_id": 2}	\N	127.0.0.1	Python-urllib/3.9	2026-10-19 21:41:26-05
44	ccce9ae7-ca93-48e7-8577-0dd546dda010	1	Admin Robledal	GARITA_APERTURA_CONTINGENCIA	acc_aperturas_contingencia	1	\N	{"placa": "PBA-1000", "carril": "BARRERA_ENTRADA_01", "motivo": "Corte temporal de suministro eléctrico perimetral", "guardia_id": 1}	\N	127.0.0.1	Python-urllib/3.9	2026-10-19 21:41:26-05
45	ccce9ae7-ca93-48e7-8577-0dd546dda010	1	Admin Robledal	RESERVA_AMENIDAD_CREADA	op_reservas_amenidades	1	\N	{"area": "Cancha de Pádel Cristal Central", "fecha": "2026-10-25", "horario": "16:00 - 18:00", "invitados": 4}	\N	127.0.0.1	Python-urllib/3.9	2026-10-19 21:41:26-05
46	ccce9ae7-ca93-48e7-8577-0dd546dda010	1	Admin Robledal	VOTO_DIGITAL_EMITIDO	op_votos_emitidos	1	\N	{"peso": 1, "inmueble_id": 2, "votacion_id": 1}	\N	127.0.0.1	Python-urllib/3.9	2026-10-19 21:41:26-05
47	\N	\N	SISTEMA	TEST_EMISION_VOTO	op_votos_emitidos	2	\N	{"peso": 1, "opcion": 1, "inmueble": "Mz Manzana A Villa Villa 01"}	\N	127.0.0.1	Symfony	2026-10-19 21:41:26-05
\.


--
-- Data for Name: cache; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cache (key, value, expiration) FROM stdin;
xiWgACU4EZCrZphD	s:7:"forever";	2106882065
\.


--
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cache_locks (key, owner, expiration) FROM stdin;
\.


--
-- Data for Name: core_inmueble_residentes; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.core_inmueble_residentes (res_id, res_inmueble_id, res_persona_id, res_tipo_relacion, res_es_principal, res_activo, created_at, updated_at, deleted_at) FROM stdin;
1	2	2	PROPIETARIO	t	t	2026-10-09 03:14:53	2026-10-09 03:14:53	\N
2	3	3	PROPIETARIO	t	t	2026-10-09 03:14:53	2026-10-09 03:14:53	\N
3	4	4	PROPIETARIO	t	t	2026-10-09 03:14:53	2026-10-09 03:14:53	\N
\.


--
-- Data for Name: core_inmueble_vehiculos; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.core_inmueble_vehiculos (veh_id, veh_inmueble_id, veh_persona_id, veh_placa, veh_marca, veh_modelo, veh_color, veh_tipo, veh_tag_rfid, veh_sticker_lpr, veh_es_autorizado, veh_observaciones, veh_activo, created_at, updated_at, deleted_at) FROM stdin;
1	2	2	PBA-1020	Toyota Rav4	\N	Gris	SUV	RFID-ROBLE-001	\N	t	\N	t	2026-10-09 03:14:53	2026-10-09 03:14:53	\N
2	3	3	PDH-3040	Mazda CX-5	\N	Blanco	SUV	RFID-ROBLE-002	\N	t	\N	t	2026-10-09 03:14:53	2026-10-09 03:14:53	\N
3	4	4	PCH-5060	Kia Sportage	\N	Negro	SUV	RFID-ROBLE-003	\N	t	\N	t	2026-10-09 03:14:53	2026-10-09 03:14:53	\N
\.


--
-- Data for Name: core_inmuebles; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.core_inmuebles (inm_id, inm_urbanizacion_id, inm_manzana_bloque, inm_numero_villa, inm_tipo, inm_estado, inm_alicuota_porcentaje, inm_observaciones, inm_activo, created_at, updated_at, deleted_at) FROM stdin;
1	ccce9ae7-ca93-48e7-8577-0dd546dda010	Manzana A	Villa 01	CASA	HABITADO	\N	\N	t	2026-10-09 02:15:40	2026-10-09 02:15:40	\N
2	ccce9ae7-ca93-48e7-8577-0dd546dda010	Manzana 01	Villa 01	CASA	HABITADO	100	\N	t	2026-10-09 03:14:53	2026-10-09 03:14:53	\N
3	ccce9ae7-ca93-48e7-8577-0dd546dda010	Manzana 01	Villa 02	CASA	HABITADO	100	\N	t	2026-10-09 03:14:53	2026-10-09 03:14:53	\N
4	ccce9ae7-ca93-48e7-8577-0dd546dda010	Manzana 02	Villa 05	CASA	HABITADO	100	\N	t	2026-10-09 03:14:53	2026-10-09 03:14:53	\N
5	ccce9ae7-ca93-48e7-8577-0dd546dda010	Edificio Roble A	Depto 302	DEPARTAMENTO	HABITADO	100	\N	t	2026-10-09 03:14:53	2026-10-09 03:14:53	\N
\.


--
-- Data for Name: core_personas; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.core_personas (per_id, per_identificacion, per_tipo_identificacion, per_nombres, per_apellidos, per_telefono, per_email, per_foto_url, per_es_vetado, per_motivo_veto, created_at, updated_at, deleted_at) FROM stdin;
1	1799999999	CEDULA	Administrador	Robledal	+593999999999	admin@robledal.com	\N	f	\N	2026-10-09 02:15:40	2026-10-09 02:15:40	\N
2	1710000001	CEDULA	Fernando	García Moreno	0998881111	fgarcia@gmail.com	\N	f	\N	2026-10-09 03:14:53	2026-10-09 03:14:53	\N
3	1710000002	CEDULA	Valeria	Paredes Cruz	0998882222	vparedes@gmail.com	\N	f	\N	2026-10-09 03:14:53	2026-10-09 03:14:53	\N
4	1710000003	CEDULA	Mauricio	Andrade Solís	0998883333	mandrade@gmail.com	\N	f	\N	2026-10-09 03:14:53	2026-10-09 03:14:53	\N
5	1799988877	CEDULA	Rodrigo	Salazar Vintimilla	\N	\N	\N	t	Altercado y amenazas a guardia de turno	2026-10-09 03:14:53	2026-10-09 03:14:53	\N
6	1755544433	CEDULA	Manuel	Toapanta Chasi	0987654321	guardia1@robledal.com	\N	f	\N	2026-10-09 03:26:28	2026-10-09 03:26:28	\N
7	QR-1f8b0d3d	CEDULA	Test Error	(Invitado QR)	\N	\N	\N	f	\N	2026-10-08 22:34:59	2026-10-08 22:34:59	\N
8	1722334455	CEDULA	Ing. Marcelo	Cevallos	\N	\N	\N	f	\N	2026-10-08 22:41:01	2026-10-08 22:41:01	\N
9	1733445566	CEDULA	Arq. Gabriela	Ponce	\N	\N	\N	f	\N	2026-10-08 22:41:41	2026-10-08 22:41:41	\N
10	1717677503	CEDULA	VINICIO FABIAN	TAPIA CABRERA	0995338630	vinnyvgs@hotmail.com	\N	f	\N	2026-10-08 23:09:29	2026-10-08 23:09:29	\N
12	1718889999	CEDULA	Carlos	Echeverría (Proveedor Técnico)	\N	\N	\N	f	\N	2026-10-19 21:12:39	2026-10-19 21:12:39	\N
\.


--
-- Data for Name: core_urbanizaciones; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.core_urbanizaciones (urb_id, urb_codigo, urb_nombre, urb_ruc, urb_direccion, urb_ciudad, urb_telefono, urb_tema_id, urb_configuracion, urb_activo, created_at, updated_at, deleted_at) FROM stdin;
ccce9ae7-ca93-48e7-8577-0dd546dda010	URB-ROBLEDAL-01	Urbanización Piloto Robledal	1790000000001	Av. Interoceánica Km 12, Cumbayá	Quito	022999999	1	{"tolerancia_salida_minutos": 15, "exigir_cedula_acompanantes": true, "tiempo_maximo_visita_minutos": 240}	t	2026-10-09 02:15:40	2026-10-09 02:15:40	\N
\.


--
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.failed_jobs (id, uuid, connection, queue, payload, exception, failed_at) FROM stdin;
\.


--
-- Data for Name: fin_obligaciones_alicuotas; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.fin_obligaciones_alicuotas (obl_id, obl_inmueble_id, obl_periodo, obl_concepto, obl_monto, obl_fecha_emision, obl_fecha_vencimiento, obl_estado, obl_fecha_pago, obl_metodo_pago, obl_comprobante_ref, obl_observaciones, created_at, updated_at, deleted_at) FROM stdin;
1	1	2026-10	Alícuota Ordinaria Mantenimiento Octubre 2026	50.00	2026-10-19	2026-10-31	PENDIENTE	\N	\N	\N	\N	2026-10-19 20:39:19	2026-10-19 20:39:19	\N
3	3	2026-10	Alícuota Ordinaria Mantenimiento Octubre 2026	50.00	2026-10-19	2026-10-31	PENDIENTE	\N	\N	\N	\N	2026-10-19 20:39:19	2026-10-19 20:39:19	\N
4	4	2026-10	Alícuota Ordinaria Mantenimiento Octubre 2026	50.00	2026-10-19	2026-10-31	PENDIENTE	\N	\N	\N	\N	2026-10-19 20:39:19	2026-10-19 20:39:19	\N
2	2	2026-10	Alícuota Ordinaria Mantenimiento Octubre 2026	50.00	2026-10-19	2026-10-31	PAGADO	2026-10-19 20:39:24	TRANSFERENCIA	TRX-99882233	\N	2026-10-19 20:39:19	2026-10-19 20:39:24	\N
5	5	2026-10	Alícuota Ordinaria Mantenimiento Octubre 2026	50.00	2026-10-19	2026-10-31	PAGADO	2026-10-19 20:54:41	TRANSFERENCIA	TRX-12345678	\N	2026-10-19 20:39:19	2026-10-19 20:54:41	\N
\.


--
-- Data for Name: job_batches; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.job_batches (id, name, total_jobs, pending_jobs, failed_jobs, failed_job_ids, options, cancelled_at, created_at, finished_at) FROM stdin;
\.


--
-- Data for Name: jobs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.jobs (id, queue, payload, attempts, reserved_at, available_at, created_at) FROM stdin;
47	auditoria	{"uuid":"9c028159-58c1-4bfb-bbd1-5b073a47b835","displayName":"App\\\\Jobs\\\\ProcessAuditLogJob","job":"Illuminate\\\\Queue\\\\CallQueuedHandler@call","maxTries":null,"maxExceptions":null,"failOnTimeout":false,"backoff":null,"timeout":null,"retryUntil":null,"data":{"commandName":"App\\\\Jobs\\\\ProcessAuditLogJob","command":"O:27:\\"App\\\\Jobs\\\\ProcessAuditLogJob\\":2:{s:12:\\"\\u0000*\\u0000auditData\\";a:11:{s:19:\\"aud_urbanizacion_id\\";N;s:11:\\"aud_user_id\\";N;s:15:\\"aud_user_nombre\\";s:7:\\"SISTEMA\\";s:10:\\"aud_evento\\";s:25:\\"HIKVISION_ISAPI_SYNC_LOTE\\";s:9:\\"aud_tabla\\";s:22:\\"op_biometria_sync_logs\\";s:15:\\"aud_registro_id\\";s:1:\\"1\\";s:14:\\"aud_old_values\\";N;s:14:\\"aud_new_values\\";a:4:{s:8:\\"terminal\\";s:60:\\"Cámara LPR Entrada Principal (Hikvision DS-2CD7A26G0\\/P-IZS)\\";s:5:\\"total\\";i:3;s:8:\\"exitosos\\";i:3;s:8:\\"fallidos\\";i:0;}s:8:\\"aud_diff\\";N;s:14:\\"aud_ip_address\\";s:9:\\"127.0.0.1\\";s:14:\\"aud_user_agent\\";s:7:\\"Symfony\\";}s:5:\\"queue\\";s:9:\\"auditoria\\";}"}}	0	\N	1792464212	1792464212
\.


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000001_create_cache_table	1
2	0001_01_01_000002_create_jobs_table	1
3	2026_10_08_000001_create_sec_temas_table	1
4	2026_10_08_000002_create_sec_roles_and_permisos_tables	1
5	2026_10_08_000003_create_core_personas_and_sec_users_tables	1
6	2026_10_08_000004_create_core_urbanizaciones_and_inmuebles_tables	1
7	2026_10_08_000005_create_op_garitas_and_control_accesos_tables	1
8	2026_10_08_000006_create_aud_trazabilidad_tables	1
9	2026_10_09_022439_create_sessions_table	2
10	2026_10_08_000007_create_sec_vistas_tables	3
11	2026_10_09_030330_create_op_invitaciones_qr_table	4
12	2026_10_09_030844_create_core_inmueble_vehiculos_table	5
13	2026_10_08_224420_create_op_comunicados_table	6
14	2026_10_08_224647_create_op_paqueteria_y_novedades_tables	7
\.


--
-- Data for Name: op_activos_infraestructura; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.op_activos_infraestructura (act_id, act_urbanizacion_id, act_codigo, act_nombre, act_ubicacion, act_categoria, act_marca_modelo, act_fecha_instalacion, act_frecuencia_dias_mantenimiento, act_ultimo_mantenimiento, act_proximo_mantenimiento, act_estado_operativo, act_activo, created_at, updated_at, deleted_at) FROM stdin;
1	ccce9ae7-ca93-48e7-8577-0dd546dda010	BOMB-01	Sistema Hidroneumático de Bombeo Principal	Cuarto de Máquinas Central	HIDRONEUMATICO	Grundfos Hydro MPC-E	2024-10-19	90	2026-08-05	2026-11-03	OPERATIVO	t	2026-10-19 21:49:41	2026-10-19 21:49:41	\N
2	ccce9ae7-ca93-48e7-8577-0dd546dda010	GEN-01	Generador Eléctrico Diésel de Emergencia 150 kVA	Subestación Eléctrica Garita Norte	GENERADOR	Cummins C150D5	2025-10-19	60	2026-08-15	2026-10-14	REQUIERE_SERVICIO	t	2026-10-19 21:49:41	2026-10-19 21:49:41	\N
\.


--
-- Data for Name: op_areas_sociales; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.op_areas_sociales (are_id, are_urbanizacion_id, are_nombre, are_tipo, are_descripcion, are_aforo_maximo, are_costo_reserva, are_garantia_deposito, are_hora_apertura, are_hora_cierre, are_duracion_turno_horas, are_dias_anticipacion_maxima, are_requiere_aprobacion, are_activo, created_at, updated_at, deleted_at) FROM stdin;
1	ccce9ae7-ca93-48e7-8577-0dd546dda010	Cancha de Pádel Cristal Central	PADEL	Cancha reglamentaria con iluminación LED para juego nocturno y césped sintético monofilamento.	4	15.00	0.00	07:00:00	23:00:00	2	7	f	t	2026-10-19 21:25:53	2026-10-19 21:25:53	\N
2	ccce9ae7-ca93-48e7-8577-0dd546dda010	Pérgola & Zona BBQ Los Robles	BBQ	Parrilla en acero inoxidable, mesones de granito, fregadero y mesas rústicas techadas.	25	20.00	30.00	11:00:00	22:00:00	4	15	t	t	2026-10-19 21:25:53	2026-10-19 21:25:53	\N
3	ccce9ae7-ca93-48e7-8577-0dd546dda010	Casa Club & Salón de Eventos Principal	CASA_CLUB	Salón climatizado para reuniones familiares, cumpleaños y asambleas. Incluye mobiliario para 60 personas.	60	50.00	100.00	10:00:00	01:00:00	6	30	t	t	2026-10-19 21:25:53	2026-10-19 21:25:53	\N
4	ccce9ae7-ca93-48e7-8577-0dd546dda010	Piscina Semi-Olímpica & Solárium	PISCINA	Área húmeda con piscina templada, reposeras y vestidores.	30	0.00	0.00	09:00:00	20:00:00	3	3	f	t	2026-10-19 21:25:53	2026-10-19 21:25:53	\N
\.


--
-- Data for Name: op_biometria_residentes; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.op_biometria_residentes (bio_id, bio_urbanizacion_id, bio_persona_id, bio_employee_no, bio_tipo_credencial, bio_tarjeta_no, bio_template_data, bio_foto_rostro_path, bio_estado_sincronizacion, bio_ultimo_sync, bio_detalles_dispositivos, created_at, updated_at, deleted_at) FROM stdin;
1	ccce9ae7-ca93-48e7-8577-0dd546dda010	2	1710000001	FACIAL	\N	\N	\N	SINCRONIZADO	2026-10-19 21:43:32	{"modo": "SIMULADO", "carril": "ENTRADA_01", "disp_id": 1, "disp_nombre": "Cámara LPR Entrada Principal (Hikvision DS-2CD7A26G0/P-IZS)"}	\N	2026-10-19 21:43:32	\N
2	ccce9ae7-ca93-48e7-8577-0dd546dda010	3	1710000002	FACIAL	\N	\N	\N	SINCRONIZADO	2026-10-19 21:43:32	{"modo": "SIMULADO", "carril": "ENTRADA_01", "disp_id": 1, "disp_nombre": "Cámara LPR Entrada Principal (Hikvision DS-2CD7A26G0/P-IZS)"}	\N	2026-10-19 21:43:32	\N
3	ccce9ae7-ca93-48e7-8577-0dd546dda010	4	1710000003	FACIAL	\N	\N	\N	SINCRONIZADO	2026-10-19 21:43:32	{"modo": "SIMULADO", "carril": "ENTRADA_01", "disp_id": 1, "disp_nombre": "Cámara LPR Entrada Principal (Hikvision DS-2CD7A26G0/P-IZS)"}	\N	2026-10-19 21:43:32	\N
\.


--
-- Data for Name: op_biometria_sync_logs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.op_biometria_sync_logs (slog_id, slog_urbanizacion_id, slog_dispositivo_id, slog_canal, slog_accion, slog_total_personas, slog_exitosos, slog_fallidos, slog_detalles, slog_ejecutado_por, created_at, updated_at) FROM stdin;
1	ccce9ae7-ca93-48e7-8577-0dd546dda010	1	ISAPI_DIRECT	ENROLAR	3	3	0	[{"nombre": "Fernando García Moreno", "status": "OK_SIMULADO", "mensaje": "Comando ISAPI UserInfo emitido y emulado exitosamente.", "employeeNo": "1710000001", "persona_id": 2}, {"nombre": "Valeria Paredes Cruz", "status": "OK_SIMULADO", "mensaje": "Comando ISAPI UserInfo emitido y emulado exitosamente.", "employeeNo": "1710000002", "persona_id": 3}, {"nombre": "Mauricio Andrade Solís", "status": "OK_SIMULADO", "mensaje": "Comando ISAPI UserInfo emitido y emulado exitosamente.", "employeeNo": "1710000003", "persona_id": 4}]	ADMIN	2026-10-19 21:43:32	2026-10-19 21:43:32
\.


--
-- Data for Name: op_comunicados; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.op_comunicados (com_id, com_urbanizacion_id, com_autor_usr_id, com_titulo, com_contenido, com_categoria, com_prioridad, com_fecha_publicacion, com_fecha_expiracion, com_fijado, com_activo, created_at, updated_at, deleted_at) FROM stdin;
f190443c-4759-41c4-8b00-ca6bfd7624d6	ccce9ae7-ca93-48e7-8577-0dd546dda010	1	Mantenimiento Preventivo de Bomba de Agua Principal	Estimados residentes, este viernes de 09:00 a 12:00 se realizará el mantenimiento de la cisterna central. Rogamos tomar las precauciones necesarias.	MANTENIMIENTO	ALTA	2026-10-08	\N	t	t	2026-10-08 22:44:52	2026-10-08 22:44:52	\N
f476df78-5dd1-45d6-94fc-c806d23d38b3	ccce9ae7-ca93-48e7-8577-0dd546dda010	1	Recordatorio de Seguridad: Límite de Velocidad Interna 20 km/h	Por la seguridad de nuestros niños y mascotas que transitan por las vías principales, recordamos a todos los conductores respetar el límite de 20 km/h.	SEGURIDAD	NORMAL	2026-10-08	\N	f	t	2026-10-08 22:44:52	2026-10-08 22:44:52	\N
\.


--
-- Data for Name: op_control_accesos; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.op_control_accesos (acc_id, acc_urbanizacion_id, acc_persona_id, acc_inmueble_id, acc_tipo_ingreso, acc_rol_visita, acc_empresa, acc_vehiculo_placa, acc_vehiculo_marca, acc_vehiculo_modelo, acc_vehiculo_color, acc_cantidad_ocupantes, acc_ocupantes_detalle, acc_garita_ingreso_id, acc_guardia_ingreso_usr_id, acc_fecha_ingreso, acc_garita_salida_id, acc_guardia_salida_usr_id, acc_fecha_salida, acc_duracion_minutos, acc_estado, acc_tiempo_excedido, acc_observaciones, created_at, updated_at) FROM stdin;
1	ccce9ae7-ca93-48e7-8577-0dd546dda010	2	2	VEHICULAR	RESIDENTE	\N	PBA-1020	\N	\N	\N	1	\N	\N	\N	2026-10-09 00:14:53-05	\N	\N	2026-10-09 02:14:53-05	120	SALIO	f	Ingreso regular residente	2026-10-09 00:14:53	2026-10-09 02:14:53
2	ccce9ae7-ca93-48e7-8577-0dd546dda010	3	3	VEHICULAR	VISITANTE	\N	PDH-3040	\N	\N	\N	2	\N	\N	\N	2026-10-09 02:29:53-05	\N	\N	\N	\N	DENTRO	f	Visita familiar autorizada	2026-10-09 02:29:53	2026-10-09 02:29:53
3	ccce9ae7-ca93-48e7-8577-0dd546dda010	7	2	VEHICULAR	VISITANTE	\N	\N	\N	\N	\N	1	\N	\N	\N	2026-10-08 22:34:59-05	\N	\N	\N	\N	DENTRO	f	Ingreso mediante Pase QR [QR-J9NWA9-168]. 	2026-10-08 22:34:59	2026-10-08 22:34:59
4	ccce9ae7-ca93-48e7-8577-0dd546dda010	3	3	VEHICULAR	PROVEEDOR	\N	P-EXCEDIDO	\N	\N	\N	1	\N	\N	\N	2026-10-08 17:19:02-05	\N	\N	\N	\N	DENTRO	f	Prueba de alerta de permanencia excedida	2026-10-08 22:39:02	2026-10-08 22:39:02
5	ccce9ae7-ca93-48e7-8577-0dd546dda010	8	2	VEHICULAR	PROVEEDOR	\N	P-GARITASUR	\N	\N	\N	1	\N	2	1	2026-10-08 22:41:01-05	\N	\N	\N	\N	DENTRO	f	Ingreso por Garita Sur de Proveedores	2026-10-08 22:41:01	2026-10-08 22:41:01
6	ccce9ae7-ca93-48e7-8577-0dd546dda010	9	2	VEHICULAR	PROVEEDOR	\N	P-MULTIGARITA	\N	\N	\N	1	\N	2	1	2026-10-08 22:41:41-05	1	1	2026-10-08 22:41:41-05	0	SALIO	f	Inspección de obra ingresando por Garita Sur\nSalida completada por Garita Principal Norte	2026-10-08 22:41:41	2026-10-08 22:41:41
8	ccce9ae7-ca93-48e7-8577-0dd546dda010	12	2	VEHICULAR	VISITA	\N	PBC-8812	\N	\N	\N	1	\N	\N	1	2026-10-19 21:12:39-05	\N	\N	\N	\N	DENTRO	f	Ingreso Automatizado LPR con Pase QR QR-5Y28C8-665 (Carlos Echeverría (Proveedor Técnico))	2026-10-19 21:12:39	2026-10-19 21:12:39
\.


--
-- Data for Name: op_garita_dispositivos; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.op_garita_dispositivos (disp_id, disp_urbanizacion_id, disp_nombre, disp_tipo, disp_ip, disp_puerto, disp_protocolo, disp_carril, disp_estado_conexion, disp_ultimo_ping, created_at, updated_at, deleted_at, disp_usuario_api, disp_password_api, disp_modelo, disp_firmware) FROM stdin;
1	ccce9ae7-ca93-48e7-8577-0dd546dda010	Cámara LPR Entrada Principal (Hikvision DS-2CD7A26G0/P-IZS)	CAMARA_LPR	192.168.10.150	80	HIKVISION_ISAPI	ENTRADA_01	ONLINE	2026-10-19 21:07:46	2026-10-19 21:07:46	2026-10-19 21:07:46	\N	admin	\N	\N	\N
2	ccce9ae7-ca93-48e7-8577-0dd546dda010	Antena RFID UHF Largo Alcance (ZKTeco ProRF-UHF 900MHz)	ANTENA_RFID	192.168.10.151	4370	WIEGAND_TCP	ENTRADA_01	ONLINE	2026-10-19 21:07:46	2026-10-19 21:07:46	2026-10-19 21:07:46	\N	admin	\N	\N	\N
3	ccce9ae7-ca93-48e7-8577-0dd546dda010	Controlador de Relé Barrera Vehicular (Relay IP / Wiegand Barrier)	CONTROLADOR_BARRERA	192.168.10.152	8080	HTTP_JSON_RELAY	BARRERA_ENTRADA_01	ONLINE	2026-10-19 21:07:46	2026-10-19 21:07:46	2026-10-19 21:07:46	\N	admin	\N	\N	\N
4	ccce9ae7-ca93-48e7-8577-0dd546dda010	Terminal Facial Hikvision Garita Norte (DS-K1T671)	BIOMETRICO_FACIAL	192.168.1.120	80	ISAPI_HTTP	PEATONAL_PRINCIPAL	ONLINE	2026-10-19 21:43:27	2026-10-19 21:43:27	2026-10-19 21:43:27	\N	admin	Admin2026.*	DS-K1T671MF	V3.2.0_build231015
\.


--
-- Data for Name: op_garitas; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.op_garitas (gar_id, gar_urbanizacion_id, gar_nombre, gar_ubicacion, gar_permite_vehicular, gar_permite_peatonal, gar_activo, created_at, updated_at, deleted_at) FROM stdin;
1	ccce9ae7-ca93-48e7-8577-0dd546dda010	Garita Principal (Acceso Norte)	Entrada Av. Interoceánica	t	t	t	2026-10-09 02:15:40	2026-10-09 02:15:40	\N
2	ccce9ae7-ca93-48e7-8577-0dd546dda010	Garita Sur (Proveedores & Servicios)	Calle Secundaria B	t	t	t	2026-10-08 22:40:38	2026-10-08 22:40:38	\N
\.


--
-- Data for Name: op_invitaciones_qr; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.op_invitaciones_qr (inv_id, inv_urbanizacion_id, inv_inmueble_id, inv_usuario_creador_id, inv_codigo_token, inv_visitante_identificacion, inv_visitante_nombre, inv_visitante_placa, inv_tipo_ingreso, inv_valido_desde, inv_valido_hasta, inv_usos_permitidos, inv_usos_realizados, inv_estado, inv_observaciones, created_at, updated_at, deleted_at) FROM stdin;
f9f454f9-58a8-4ef6-8835-573d037fe414	ccce9ae7-ca93-48e7-8577-0dd546dda010	2	1	QR-ROBLE-7788	1720304050	Dra. Gabriela Maldonado	PBI-4040	VEHICULAR	2026-10-09 01:14:53	2026-10-09 15:14:53	2	0	ACTIVO	Reunión médica familiar	2026-10-09 03:14:53	2026-10-09 03:14:53	\N
98438ff9-a39b-43d7-a39b-084764759df8	ccce9ae7-ca93-48e7-8577-0dd546dda010	2	3	QR-8PLAV4-456	1719998877	Dra. Sofía Noboa	PBI-4545	VEHICULAR	2026-10-09 05:31:50	2026-10-10 05:31:50	1	0	ACTIVO	Visita médica familiar	2026-10-09 03:31:50	2026-10-09 03:31:50	\N
4de16779-870c-4ac3-b3f0-19adceab500c	ccce9ae7-ca93-48e7-8577-0dd546dda010	2	3	QR-YYO1IW-296	0921443322	Ing. Carlos Mendoza	GTO-8899	VEHICULAR	2026-10-09 05:23:11	2026-10-09 17:33:11	1	0	ACTIVO	Entrega de documentos técnicos	2026-10-08 22:33:11	2026-10-08 22:33:11	\N
88a50051-125c-43df-b3ef-5138ffc45cfc	ccce9ae7-ca93-48e7-8577-0dd546dda010	2	3	QR-VFE6NK-227	1720304050	Dra. Andrea Morales	PCH-7788	VEHICULAR	2026-10-09 05:28:37	2026-10-10 05:33:37	1	0	ACTIVO	Consulta médica a domicilio	2026-10-08 22:33:37	2026-10-08 22:33:37	\N
f1766f1d-29e6-4a77-bd4f-8ec429883704	ccce9ae7-ca93-48e7-8577-0dd546dda010	2	3	QR-NNX6CX-899	1720304050	Dra. Andrea Morales	PCH-7788	VEHICULAR	2026-10-08 22:30:00	2026-10-09 23:59:00	1	0	ACTIVO	Consulta médica a domicilio	2026-10-08 22:33:57	2026-10-08 22:33:57	\N
1f8b0d3d-07a1-4204-b23c-c70e4aaa885c	ccce9ae7-ca93-48e7-8577-0dd546dda010	2	3	QR-J9NWA9-168	\N	Test Error	\N	VEHICULAR	2026-10-08 22:30:00	2026-10-09 23:59:00	1	1	USADO	\N	2026-10-08 22:34:18	2026-10-08 22:34:59	\N
d829125b-618a-4583-8ea9-610667286a04	ccce9ae7-ca93-48e7-8577-0dd546dda010	2	1	QR-5Y28C8-665	1718889999	Carlos Echeverría (Proveedor Técnico)	PBC-8812	VEHICULAR	2026-10-19 20:00:00	2026-10-20 20:00:00	1	1	CANJEADO	Mantenimiento urgente de internet fibra óptica	2026-10-19 21:02:11	2026-10-19 21:12:39	\N
\.


--
-- Data for Name: op_lista_negra; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.op_lista_negra (lis_id, lis_urbanizacion_id, lis_persona_id, lis_placa_vehiculo, lis_motivo, lis_registrado_por_usr_id, lis_activo, created_at, updated_at, deleted_at) FROM stdin;
1	ccce9ae7-ca93-48e7-8577-0dd546dda010	5	PBO-9999	Altercado violento en garita norte y daño a pluma vehicular	1	t	2026-10-09 03:14:53	2026-10-09 03:14:53	\N
\.


--
-- Data for Name: op_novedades_turno; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.op_novedades_turno (nov_id, nov_urbanizacion_id, nov_guardia_usr_id, nov_garita_id, nov_tipo, nov_titulo, nov_detalle, nov_prioridad, nov_fecha_hora, nov_resuelta, created_at, updated_at, deleted_at) FROM stdin;
a4bcd589-f829-4eb3-a2ea-3da6dbeb2b35	ccce9ae7-ca93-48e7-8577-0dd546dda010	2	1	RONDA	Ronda Perimetral Sector Manzana 01 y Garita Norte	Cerco eléctrico con voltaje nominal 9.8kV, iluminación perimetral operativa al 100%, sin novedades.	NORMAL	2026-10-08 22:47:56	f	2026-10-08 22:47:56	2026-10-08 22:47:56	\N
\.


--
-- Data for Name: op_ordenes_mantenimiento; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.op_ordenes_mantenimiento (ord_id, ord_urbanizacion_id, ord_codigo_folio, ord_activo_id, ord_proveedor_id, ord_tipo, ord_titulo, ord_descripcion, ord_prioridad, ord_estado, ord_fecha_programada, ord_fecha_inicio, ord_fecha_finalizacion, ord_costo_estimado, ord_costo_real, ord_responsable_supervision, ord_informe_tecnico, ord_pre_autoriza_garita, ord_solicitante_usr_id, created_at, updated_at, deleted_at) FROM stdin;
1	ccce9ae7-ca93-48e7-8577-0dd546dda010	MNT-2026-0001	2	1	PREVENTIVO	Cambio de Filtros y Mantenimiento Bimensual de Generador	Reemplazo de aceite sintético 15W40, filtros de combustible diésel y prueba en vacío de transferencia automática ATS.	ALTA	EN_PROCESO	2026-10-19	2026-10-19 21:49:41	\N	450.00	420.00	Administración Robledal	\N	t	\N	2026-10-19 21:49:41	2026-10-19 21:49:41	\N
\.


--
-- Data for Name: op_paqueteria; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.op_paqueteria (paq_id, paq_urbanizacion_id, paq_inmueble_id, paq_guardia_recibe_usr_id, paq_guardia_entrega_usr_id, paq_empresa_courier, paq_codigo_guia, paq_destinatario_nombre, paq_descripcion, paq_estado, paq_fecha_recepcion, paq_fecha_entrega, paq_retirado_por, paq_observaciones, created_at, updated_at, deleted_at) FROM stdin;
67165b5a-e4f6-4fe2-8e01-b4908c85da4b	ccce9ae7-ca93-48e7-8577-0dd546dda010	2	2	2	Servientrega	SRV-99887766	Fernando García	Caja mediana rotulada	ENTREGADO	2026-10-08 22:47:55	2026-10-08 22:49:22	Fernando García (Propietario)	Dejado por motorizado de Servientrega a las 10:45\nRetiro: Retiro presencial en garita, verificado con cédula	2026-10-08 22:47:55	2026-10-08 22:49:22	\N
\.


--
-- Data for Name: op_proveedores; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.op_proveedores (prv_id, prv_urbanizacion_id, prv_razon_social, prv_ruc_identificacion, prv_categoria, prv_contacto_nombre, prv_telefono, prv_email, prv_direccion, prv_activo, created_at, updated_at, deleted_at) FROM stdin;
1	ccce9ae7-ca93-48e7-8577-0dd546dda010	HidroElectro Soluciones S.A.	1792345678001	BOMBAS_AGUA	Ing. Carlos Mendoza	0998765432	soporte@hidroelectro.com.ec	Av. Galo Plaza Lasso N45-12	t	2026-10-19 21:49:41	2026-10-19 21:49:41	\N
\.


--
-- Data for Name: op_push_subscriptions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.op_push_subscriptions (sub_id, sub_urbanizacion_id, sub_user_id, sub_persona_id, sub_endpoint, sub_p256dh, sub_auth, sub_user_agent, sub_activo, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: op_reservas_amenidades; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.op_reservas_amenidades (res_id, res_urbanizacion_id, res_area_id, res_inmueble_id, res_persona_id, res_fecha, res_hora_inicio, res_hora_fin, res_cantidad_invitados, res_monto_pagado, res_monto_garantia, res_estado, res_motivo_evento, res_lista_invitados, created_at, updated_at, deleted_at) FROM stdin;
1	ccce9ae7-ca93-48e7-8577-0dd546dda010	1	2	1	2026-10-25	16:00:00	18:00:00	4	15.00	0.00	CONFIRMADA	Torneo de Pádel Familiar	\N	2026-10-19 21:25:53	2026-10-19 21:25:53	\N
\.


--
-- Data for Name: op_votaciones; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.op_votaciones (vot_id, vot_urbanizacion_id, vot_titulo, vot_descripcion, vot_tipo_calculo, vot_fecha_inicio, vot_fecha_fin, vot_opciones, vot_estado, vot_solo_al_dia, vot_creador_usr_id, created_at, updated_at, deleted_at) FROM stdin;
1	ccce9ae7-ca93-48e7-8577-0dd546dda010	Aprobación de Instalación de Cámaras LPR & Barreras de Alto Tráfico	Propuesta para modernizar el sistema de control de accesos perimetrales con cargo al fondo de reserva de mejoras extraordinarias.	PREDIO_UNICO	2026-10-17 21:28:54	2026-10-24 21:28:54	[{"id": 1, "texto": "A Favor (Aprobar Adquisición)"}, {"id": 2, "texto": "En Contra (Mantener Sistema Actual)"}, {"id": 3, "texto": "Abstención"}]	ABIERTA	t	1	2026-10-19 21:28:54	2026-10-19 21:28:54	\N
\.


--
-- Data for Name: op_votos_emitidos; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.op_votos_emitidos (vto_id, vto_urbanizacion_id, vto_votacion_id, vto_inmueble_id, vto_persona_id, vto_opcion_id, vto_peso_voto, vto_fecha_hora, vto_ip_origen, created_at, updated_at) FROM stdin;
1	ccce9ae7-ca93-48e7-8577-0dd546dda010	1	2	1	1	1.0000	2026-10-19 21:28:54	127.0.0.1	2026-10-19 21:28:54	2026-10-19 21:28:54
2	ccce9ae7-ca93-48e7-8577-0dd546dda010	1	1	1	1	1.0000	2026-10-19 21:40:10	127.0.0.1	2026-10-19 21:40:10	2026-10-19 21:40:10
\.


--
-- Data for Name: sec_permisos; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.sec_permisos (prm_id, prm_modulo, prm_accion, prm_slug, prm_descripcion, created_at, updated_at) FROM stdin;
1	urbanizaciones	read	urbanizaciones.read	Lectura y visualización en urbanizaciones	2026-10-19 20:38:20	2026-10-19 20:38:20
2	urbanizaciones	write	urbanizaciones.write	Creación y registro en urbanizaciones	2026-10-19 20:38:20	2026-10-19 20:38:20
3	urbanizaciones	update	urbanizaciones.update	Modificación y edición en urbanizaciones	2026-10-19 20:38:20	2026-10-19 20:38:20
4	urbanizaciones	delete	urbanizaciones.delete	Eliminación lógica en urbanizaciones	2026-10-19 20:38:20	2026-10-19 20:38:20
5	urbanizaciones	print	urbanizaciones.print	Descarga e impresión de reportes/documentos en urbanizaciones	2026-10-19 20:38:20	2026-10-19 20:38:20
6	inmuebles	read	inmuebles.read	Lectura y visualización en inmuebles	2026-10-19 20:38:20	2026-10-19 20:38:20
7	inmuebles	write	inmuebles.write	Creación y registro en inmuebles	2026-10-19 20:38:20	2026-10-19 20:38:20
8	inmuebles	update	inmuebles.update	Modificación y edición en inmuebles	2026-10-19 20:38:20	2026-10-19 20:38:20
9	inmuebles	delete	inmuebles.delete	Eliminación lógica en inmuebles	2026-10-19 20:38:20	2026-10-19 20:38:20
10	inmuebles	print	inmuebles.print	Descarga e impresión de reportes/documentos en inmuebles	2026-10-19 20:38:20	2026-10-19 20:38:20
11	personas	read	personas.read	Lectura y visualización en personas	2026-10-19 20:38:20	2026-10-19 20:38:20
12	personas	write	personas.write	Creación y registro en personas	2026-10-19 20:38:20	2026-10-19 20:38:20
13	personas	update	personas.update	Modificación y edición en personas	2026-10-19 20:38:20	2026-10-19 20:38:20
14	personas	delete	personas.delete	Eliminación lógica en personas	2026-10-19 20:38:20	2026-10-19 20:38:20
15	personas	print	personas.print	Descarga e impresión de reportes/documentos en personas	2026-10-19 20:38:20	2026-10-19 20:38:20
16	accesos	read	accesos.read	Lectura y visualización en accesos	2026-10-19 20:38:20	2026-10-19 20:38:20
17	accesos	write	accesos.write	Creación y registro en accesos	2026-10-19 20:38:20	2026-10-19 20:38:20
18	accesos	update	accesos.update	Modificación y edición en accesos	2026-10-19 20:38:20	2026-10-19 20:38:20
19	accesos	delete	accesos.delete	Eliminación lógica en accesos	2026-10-19 20:38:20	2026-10-19 20:38:20
20	accesos	print	accesos.print	Descarga e impresión de reportes/documentos en accesos	2026-10-19 20:38:20	2026-10-19 20:38:20
21	reportes	read	reportes.read	Lectura y visualización en reportes	2026-10-19 20:38:20	2026-10-19 20:38:20
22	reportes	write	reportes.write	Creación y registro en reportes	2026-10-19 20:38:20	2026-10-19 20:38:20
23	reportes	update	reportes.update	Modificación y edición en reportes	2026-10-19 20:38:20	2026-10-19 20:38:20
24	reportes	delete	reportes.delete	Eliminación lógica en reportes	2026-10-19 20:38:20	2026-10-19 20:38:20
25	reportes	print	reportes.print	Descarga e impresión de reportes/documentos en reportes	2026-10-19 20:38:20	2026-10-19 20:38:20
26	auditoria	read	auditoria.read	Lectura y visualización en auditoria	2026-10-19 20:38:20	2026-10-19 20:38:20
27	auditoria	write	auditoria.write	Creación y registro en auditoria	2026-10-19 20:38:20	2026-10-19 20:38:20
28	auditoria	update	auditoria.update	Modificación y edición en auditoria	2026-10-19 20:38:20	2026-10-19 20:38:20
29	auditoria	delete	auditoria.delete	Eliminación lógica en auditoria	2026-10-19 20:38:20	2026-10-19 20:38:20
30	auditoria	print	auditoria.print	Descarga e impresión de reportes/documentos en auditoria	2026-10-19 20:38:20	2026-10-19 20:38:20
31	temas	read	temas.read	Lectura y visualización en temas	2026-10-19 20:38:20	2026-10-19 20:38:20
32	temas	write	temas.write	Creación y registro en temas	2026-10-19 20:38:20	2026-10-19 20:38:20
33	temas	update	temas.update	Modificación y edición en temas	2026-10-19 20:38:20	2026-10-19 20:38:20
34	temas	delete	temas.delete	Eliminación lógica en temas	2026-10-19 20:38:20	2026-10-19 20:38:20
35	temas	print	temas.print	Descarga e impresión de reportes/documentos en temas	2026-10-19 20:38:20	2026-10-19 20:38:20
\.


--
-- Data for Name: sec_rol_permisos; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.sec_rol_permisos (rxp_id, rxp_rol_id, rxp_permiso_id, created_at, updated_at) FROM stdin;
1	2	1	2026-10-19 20:38:20	2026-10-19 20:38:20
2	2	2	2026-10-19 20:38:20	2026-10-19 20:38:20
3	2	3	2026-10-19 20:38:20	2026-10-19 20:38:20
4	2	4	2026-10-19 20:38:20	2026-10-19 20:38:20
5	2	5	2026-10-19 20:38:20	2026-10-19 20:38:20
6	2	6	2026-10-19 20:38:20	2026-10-19 20:38:20
7	2	7	2026-10-19 20:38:20	2026-10-19 20:38:20
8	2	8	2026-10-19 20:38:20	2026-10-19 20:38:20
9	2	9	2026-10-19 20:38:20	2026-10-19 20:38:20
10	2	10	2026-10-19 20:38:20	2026-10-19 20:38:20
11	2	11	2026-10-19 20:38:20	2026-10-19 20:38:20
12	2	12	2026-10-19 20:38:20	2026-10-19 20:38:20
13	2	13	2026-10-19 20:38:20	2026-10-19 20:38:20
14	2	14	2026-10-19 20:38:20	2026-10-19 20:38:20
15	2	15	2026-10-19 20:38:20	2026-10-19 20:38:20
16	2	16	2026-10-19 20:38:20	2026-10-19 20:38:20
17	2	17	2026-10-19 20:38:20	2026-10-19 20:38:20
18	2	18	2026-10-19 20:38:20	2026-10-19 20:38:20
19	2	19	2026-10-19 20:38:20	2026-10-19 20:38:20
20	2	20	2026-10-19 20:38:20	2026-10-19 20:38:20
21	2	21	2026-10-19 20:38:20	2026-10-19 20:38:20
22	2	22	2026-10-19 20:38:20	2026-10-19 20:38:20
23	2	23	2026-10-19 20:38:20	2026-10-19 20:38:20
24	2	24	2026-10-19 20:38:20	2026-10-19 20:38:20
25	2	25	2026-10-19 20:38:20	2026-10-19 20:38:20
26	2	26	2026-10-19 20:38:20	2026-10-19 20:38:20
27	2	27	2026-10-19 20:38:20	2026-10-19 20:38:20
28	2	28	2026-10-19 20:38:20	2026-10-19 20:38:20
29	2	29	2026-10-19 20:38:20	2026-10-19 20:38:20
30	2	30	2026-10-19 20:38:20	2026-10-19 20:38:20
31	2	31	2026-10-19 20:38:20	2026-10-19 20:38:20
32	2	32	2026-10-19 20:38:20	2026-10-19 20:38:20
33	2	33	2026-10-19 20:38:20	2026-10-19 20:38:20
34	2	34	2026-10-19 20:38:21	2026-10-19 20:38:21
35	2	35	2026-10-19 20:38:21	2026-10-19 20:38:21
36	3	16	2026-10-19 20:38:21	2026-10-19 20:38:21
37	3	17	2026-10-19 20:38:21	2026-10-19 20:38:21
38	3	18	2026-10-19 20:38:21	2026-10-19 20:38:21
39	3	11	2026-10-19 20:38:21	2026-10-19 20:38:21
40	3	12	2026-10-19 20:38:21	2026-10-19 20:38:21
41	3	6	2026-10-19 20:38:21	2026-10-19 20:38:21
42	3	21	2026-10-19 20:38:21	2026-10-19 20:38:21
\.


--
-- Data for Name: sec_rol_vistas; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.sec_rol_vistas (rxv_id, rxv_rol_id, rxv_vista_id, created_at, updated_at) FROM stdin;
1	1	1	2026-10-09 03:01:17	2026-10-09 03:01:17
2	2	1	2026-10-09 03:01:17	2026-10-09 03:01:17
17	3	1	2026-10-09 03:01:17	2026-10-09 03:01:17
3	1	2	2026-10-09 03:01:17	2026-10-09 03:01:17
4	2	2	2026-10-09 03:01:17	2026-10-09 03:01:17
5	1	3	2026-10-09 03:01:17	2026-10-09 03:01:17
6	2	3	2026-10-09 03:01:17	2026-10-09 03:01:17
7	1	4	2026-10-09 03:01:17	2026-10-09 03:01:17
8	2	4	2026-10-09 03:01:17	2026-10-09 03:01:17
9	1	5	2026-10-09 03:01:17	2026-10-09 03:01:17
10	2	5	2026-10-09 03:01:17	2026-10-09 03:01:17
11	1	6	2026-10-09 03:01:17	2026-10-09 03:01:17
12	2	6	2026-10-09 03:01:17	2026-10-09 03:01:17
13	1	7	2026-10-09 03:01:17	2026-10-09 03:01:17
14	2	7	2026-10-09 03:01:17	2026-10-09 03:01:17
15	1	8	2026-10-09 03:01:17	2026-10-09 03:01:17
16	2	8	2026-10-09 03:01:17	2026-10-09 03:01:17
18	3	8	2026-10-09 03:01:17	2026-10-09 03:01:17
19	1	9	2026-10-09 03:16:49	2026-10-09 03:16:49
20	2	9	2026-10-09 03:16:49	2026-10-09 03:16:49
21	3	9	2026-10-09 03:16:49	2026-10-09 03:16:49
22	1	10	2026-10-09 03:28:56	2026-10-09 03:28:56
23	2	10	2026-10-09 03:28:56	2026-10-09 03:28:56
24	4	10	2026-10-09 03:28:56	2026-10-09 03:28:56
25	1	11	2026-10-08 23:00:05	2026-10-08 23:00:05
26	2	11	2026-10-08 23:00:05	2026-10-08 23:00:05
27	1	12	2026-10-19 21:39:16	2026-10-19 21:39:16
28	1	13	2026-10-19 21:39:16	2026-10-19 21:39:16
29	1	14	2026-10-19 21:39:16	2026-10-19 21:39:16
30	1	15	2026-10-19 21:44:15	2026-10-19 21:44:15
31	1	16	2026-10-19 21:50:23	2026-10-19 21:50:23
\.


--
-- Data for Name: sec_roles; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.sec_roles (rol_id, rol_codigo, rol_nombre, rol_descripcion, rol_es_sistema, rol_activo, created_at, updated_at, deleted_at) FROM stdin;
1	superadmin	Super Administrador Global	Acceso total y configuración de todas las urbanizaciones	t	t	2026-10-19 20:38:20	2026-10-19 20:38:20	\N
2	admin_urbanizacion	Administrador de Urbanización	Gestión completa de su urbanización asignada	t	t	2026-10-19 20:38:20	2026-10-19 20:38:20	\N
3	guardia_garita	Operador de Garita / Seguridad	Control de entradas, salidas y novedades de accesos	t	t	2026-10-19 20:38:20	2026-10-19 20:38:20	\N
4	residente	Residente / Copropietario	Pre-autorización de visitas y consulta de estado propio	t	t	2026-10-19 20:38:20	2026-10-19 20:38:20	\N
\.


--
-- Data for Name: sec_temas; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.sec_temas (tem_id, tem_codigo, tem_nombre, tem_descripcion, tem_variables_css, tem_es_oscuro, tem_es_sistema, tem_activo, created_at, updated_at, deleted_at) FROM stdin;
1	robledal-dark	Robledal Dark (Oficial)	Modo oscuro minimalista con antracita #323637 y menta #72C1A5	{"--bg-card": "#3F4445", "--bg-hover": "#4B5153", "--bg-primary": "#323637", "--bg-surface": "#2B2E2F", "--text-muted": "#6E777B", "--border-focus": "#72C1A5", "--color-accent": "#FFFFFF", "--color-danger": "#EF4444", "--text-primary": "#FFFFFF", "--border-subtle": "rgba(255, 255, 255, 0.10)", "--color-primary": "#72C1A5", "--color-success": "#72C1A5", "--color-warning": "#F59E0B", "--text-secondary": "#9BA3A7", "--shadow-elevation": "0 10px 25px -5px rgba(0, 0, 0, 0.5)", "--color-primary-hover": "#5EB093", "--color-primary-subtle": "rgba(114, 193, 165, 0.15)"}	t	t	t	2026-10-19 20:38:20	2026-10-19 20:38:20	\N
2	robledal-light	Robledal Light Minimal	Modo claro ejecutivo con fondo limpio y acento menta #72C1A5	{"--bg-card": "#FFFFFF", "--bg-hover": "#F1F5F7", "--bg-primary": "#F8FAFB", "--bg-surface": "#FFFFFF", "--text-muted": "#9BA3A7", "--border-focus": "#72C1A5", "--color-accent": "#323637", "--color-danger": "#DC2626", "--text-primary": "#1E2223", "--border-subtle": "#E4E8EC", "--color-primary": "#72C1A5", "--color-success": "#059669", "--color-warning": "#D97706", "--text-secondary": "#636C70", "--shadow-elevation": "0 4px 20px -2px rgba(50, 54, 55, 0.08)", "--color-primary-hover": "#5EB093", "--color-primary-subtle": "rgba(114, 193, 165, 0.12)"}	f	t	t	2026-10-19 20:38:20	2026-10-19 20:38:20	\N
\.


--
-- Data for Name: sec_urbanizacion_usuarios; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.sec_urbanizacion_usuarios (uxu_id, uxu_urbanizacion_id, uxu_user_id, uxu_es_predeterminada, uxu_activo, created_at, updated_at) FROM stdin;
1	ccce9ae7-ca93-48e7-8577-0dd546dda010	1	t	t	2026-10-09 02:15:40	2026-10-09 02:15:40
2	ccce9ae7-ca93-48e7-8577-0dd546dda010	2	t	t	2026-10-09 03:26:28	2026-10-09 03:26:28
3	ccce9ae7-ca93-48e7-8577-0dd546dda010	3	t	t	2026-10-09 03:28:56	2026-10-09 03:28:56
\.


--
-- Data for Name: sec_users; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.sec_users (usr_id, usr_persona_id, usr_name, usr_email, usr_email_verified_at, usr_password, usr_es_superadmin, usr_activo, usr_preferencias, remember_token, created_at, updated_at, deleted_at) FROM stdin;
1	1	Admin Robledal	admin@robledal.com	\N	$2y$12$VsAJ38S0kKQWVElYBrKCpurr7CCnkp7ozI8YXFm1e7pjCe5EI5ZhG	t	t	{"theme": "robledal-dark"}	\N	2026-10-09 02:15:40	2026-10-09 02:15:40	\N
2	6	Manuel Toapanta (Guardia Garita Norte)	guardia1@robledal.com	\N	$2y$12$0.GkP.FmnGvamnndXpL7mu2Df.NrFg7LuUCJjK9MovPFIOwyHdf6u	f	t	\N	\N	2026-10-09 03:26:28	2026-10-09 03:26:28	\N
3	2	Fernando García (Residente)	residente1@robledal.com	\N	$2y$12$2geHeXmFY65opzPZQDZ4.uFVjH4D0UdgYqMrS25JQ1PVLLYW/zuFm	f	t	\N	\N	2026-10-09 03:28:56	2026-10-09 03:28:56	\N
\.


--
-- Data for Name: sec_usuario_roles; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.sec_usuario_roles (uxr_id, uxr_urbanizacion_usuario_id, uxr_rol_id, created_at, updated_at) FROM stdin;
1	1	1	2026-10-09 02:15:40	2026-10-09 02:15:40
2	2	3	2026-10-09 03:26:28	2026-10-09 03:26:28
3	3	4	2026-10-09 03:28:56	2026-10-09 03:28:56
\.


--
-- Data for Name: sec_vistas; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.sec_vistas (vis_id, vis_codigo, vis_nombre, vis_categoria, vis_icono, vis_descripcion, vis_orden, vis_es_menu, vis_activo, created_at, updated_at, deleted_at) FROM stdin;
1	garita	Control de Garita	OPERACION	ShieldAlert	\N	1	t	t	2026-10-09 02:47:48	2026-10-09 02:47:48	\N
2	admin.urbanizaciones	Urbanizaciones	ADMINISTRACION	Building2	\N	1	t	t	2026-10-09 02:47:48	2026-10-09 02:47:48	\N
3	admin.inmuebles	Inmuebles & Residentes	ADMINISTRACION	Home	\N	2	t	t	2026-10-09 02:47:48	2026-10-09 02:47:48	\N
4	sec.usuarios	Usuarios & Cuentas	SEGURIDAD	Users	\N	1	t	t	2026-10-09 02:47:48	2026-10-09 02:47:48	\N
5	sec.roles	Roles & Permisos	SEGURIDAD	KeyRound	\N	2	t	t	2026-10-09 02:47:48	2026-10-09 02:47:48	\N
6	sec.vistas	Administración de Vistas	SEGURIDAD	LayoutGrid	\N	3	t	t	2026-10-09 02:47:48	2026-10-09 02:47:48	\N
7	sec.auditoria	Bitácora CDC	SEGURIDAD	History	\N	4	t	t	2026-10-09 02:47:48	2026-10-09 02:47:48	\N
8	rep.accesos	Reportes & Estadísticas	REPORTES	BarChart3	\N	1	t	t	2026-10-09 02:47:48	2026-10-09 02:47:48	\N
9	dashboard	Dashboard Ejecutivo	OPERACION	LayoutDashboard	KPIs y Métricas de Garita en Tiempo Real	0	t	t	2026-10-09 03:16:49	2026-10-09 03:16:49	\N
10	portal.residente	Mi Hogar & Visitas	OPERACION	Home	Portal de Residente: Pases QR, Vehículos y Visitas	1	t	t	2026-10-09 03:28:56	2026-10-09 03:28:56	\N
11	admin.personas	Padrón de Personas	ADMINISTRACION	Layout	\N	0	t	t	2026-10-08 23:00:05	2026-10-08 23:00:05	\N
12	admin.areas_sociales	Áreas Sociales & Reservas	ADMIN	CalendarDays	Gestión de amenidades, canchas, aforos y agenda	6	t	t	2026-10-19 21:39:06	2026-10-19 21:39:06	\N
13	admin.votaciones	Comunicados & Asambleas Digitales	ADMIN	Vote	Circulares oficiales y sondeos/votaciones de asamblea	7	t	t	2026-10-19 21:39:16	2026-10-19 21:39:16	\N
14	admin.alicuotas	Tesorería & Alícuotas	ADMIN	DollarSign	Emisión masiva, cobros y recibos de expensas	5	t	t	2026-10-19 21:39:16	2026-10-19 21:39:16	\N
15	admin.biometria	Biometría ISAPI Hikvision	ADMIN	ScanFace	Sincronización de credenciales biométricas hacia terminales ISAPI e iVMS	8	t	t	2026-10-19 21:44:15	2026-10-19 21:44:15	\N
16	admin.mantenimiento	Mantenimiento & Facility	ADMIN	Wrench	Activos de infraestructura, órdenes de trabajo y contratistas	9	t	t	2026-10-19 21:50:23	2026-10-19 21:50:23	\N
\.


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.sessions (id, user_id, ip_address, user_agent, payload, last_activity) FROM stdin;
dqtBeYOsnhMQFA8XqP8cCLB9anvTUlt5lHs8cjNI	\N	127.0.0.1	Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36	YTozOntzOjY6Il90b2tlbiI7czo0MDoiUUpDMm1mTlc5VDJKRTZUbnc4ZjFZY1FCdzZLemNqcHVOMG9xYXZyZiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=	1791522042
Qa1DI2YygaJU2T8fD65Hditk8Q8WPgCcP355F12D	\N	127.0.0.1	curl/7.53.1	YTozOntzOjY6Il90b2tlbiI7czo0MDoiWjNsSXh3ZUtqenFreVJUdWtMN0hiRFpxZDBNUjZCWWdPTjR1aHdvYyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=	1792463548
Vv8Gd3mgh9iCvLrCcBzyi840r2jA3rlMkRGZMtV8	\N	127.0.0.1	Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36	YTozOntzOjY6Il90b2tlbiI7czo0MDoiWFFQU3R1YUdQN0IwUHpDdmIzbjhPZEF6cG9JdEd3RnVvU0JKNVk4OCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=	1792463856
\.


--
-- Name: acc_aperturas_contingencia_apt_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.acc_aperturas_contingencia_apt_id_seq', 1, true);


--
-- Name: aud_reportes_descargas_rep_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.aud_reportes_descargas_rep_id_seq', 1, false);


--
-- Name: aud_trazabilidad_aud_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.aud_trazabilidad_aud_id_seq', 47, true);


--
-- Name: core_inmueble_residentes_res_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.core_inmueble_residentes_res_id_seq', 3, true);


--
-- Name: core_inmueble_vehiculos_veh_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.core_inmueble_vehiculos_veh_id_seq', 3, true);


--
-- Name: core_inmuebles_inm_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.core_inmuebles_inm_id_seq', 5, true);


--
-- Name: core_personas_per_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.core_personas_per_id_seq', 12, true);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.failed_jobs_id_seq', 1, false);


--
-- Name: fin_obligaciones_alicuotas_obl_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.fin_obligaciones_alicuotas_obl_id_seq', 5, true);


--
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.jobs_id_seq', 47, true);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.migrations_id_seq', 14, true);


--
-- Name: op_activos_infraestructura_act_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.op_activos_infraestructura_act_id_seq', 2, true);


--
-- Name: op_areas_sociales_are_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.op_areas_sociales_are_id_seq', 4, true);


--
-- Name: op_biometria_residentes_bio_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.op_biometria_residentes_bio_id_seq', 3, true);


--
-- Name: op_biometria_sync_logs_slog_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.op_biometria_sync_logs_slog_id_seq', 1, true);


--
-- Name: op_control_accesos_acc_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.op_control_accesos_acc_id_seq', 8, true);


--
-- Name: op_garita_dispositivos_disp_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.op_garita_dispositivos_disp_id_seq', 4, true);


--
-- Name: op_garitas_gar_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.op_garitas_gar_id_seq', 2, true);


--
-- Name: op_lista_negra_lis_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.op_lista_negra_lis_id_seq', 1, true);


--
-- Name: op_ordenes_mantenimiento_ord_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.op_ordenes_mantenimiento_ord_id_seq', 1, true);


--
-- Name: op_proveedores_prv_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.op_proveedores_prv_id_seq', 1, true);


--
-- Name: op_push_subscriptions_sub_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.op_push_subscriptions_sub_id_seq', 1, false);


--
-- Name: op_reservas_amenidades_res_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.op_reservas_amenidades_res_id_seq', 1, true);


--
-- Name: op_votaciones_vot_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.op_votaciones_vot_id_seq', 1, true);


--
-- Name: op_votos_emitidos_vto_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.op_votos_emitidos_vto_id_seq', 2, true);


--
-- Name: sec_permisos_prm_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.sec_permisos_prm_id_seq', 35, true);


--
-- Name: sec_rol_permisos_rxp_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.sec_rol_permisos_rxp_id_seq', 42, true);


--
-- Name: sec_rol_vistas_rxv_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.sec_rol_vistas_rxv_id_seq', 31, true);


--
-- Name: sec_roles_rol_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.sec_roles_rol_id_seq', 4, true);


--
-- Name: sec_temas_tem_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.sec_temas_tem_id_seq', 2, true);


--
-- Name: sec_urbanizacion_usuarios_uxu_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.sec_urbanizacion_usuarios_uxu_id_seq', 3, true);


--
-- Name: sec_users_usr_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.sec_users_usr_id_seq', 3, true);


--
-- Name: sec_usuario_roles_uxr_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.sec_usuario_roles_uxr_id_seq', 3, true);


--
-- Name: sec_vistas_vis_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.sec_vistas_vis_id_seq', 16, true);


--
-- Name: acc_aperturas_contingencia acc_aperturas_contingencia_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.acc_aperturas_contingencia
    ADD CONSTRAINT acc_aperturas_contingencia_pkey PRIMARY KEY (apt_id);


--
-- Name: aud_reportes_descargas aud_reportes_descargas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.aud_reportes_descargas
    ADD CONSTRAINT aud_reportes_descargas_pkey PRIMARY KEY (rep_id);


--
-- Name: aud_trazabilidad aud_trazabilidad_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.aud_trazabilidad
    ADD CONSTRAINT aud_trazabilidad_pkey PRIMARY KEY (aud_id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: core_inmueble_residentes core_inmueble_residentes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.core_inmueble_residentes
    ADD CONSTRAINT core_inmueble_residentes_pkey PRIMARY KEY (res_id);


--
-- Name: core_inmueble_vehiculos core_inmueble_vehiculos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.core_inmueble_vehiculos
    ADD CONSTRAINT core_inmueble_vehiculos_pkey PRIMARY KEY (veh_id);


--
-- Name: core_inmueble_vehiculos core_inmueble_vehiculos_veh_tag_rfid_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.core_inmueble_vehiculos
    ADD CONSTRAINT core_inmueble_vehiculos_veh_tag_rfid_unique UNIQUE (veh_tag_rfid);


--
-- Name: core_inmuebles core_inmuebles_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.core_inmuebles
    ADD CONSTRAINT core_inmuebles_pkey PRIMARY KEY (inm_id);


--
-- Name: core_personas core_personas_per_identificacion_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.core_personas
    ADD CONSTRAINT core_personas_per_identificacion_unique UNIQUE (per_identificacion);


--
-- Name: core_personas core_personas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.core_personas
    ADD CONSTRAINT core_personas_pkey PRIMARY KEY (per_id);


--
-- Name: core_urbanizaciones core_urbanizaciones_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.core_urbanizaciones
    ADD CONSTRAINT core_urbanizaciones_pkey PRIMARY KEY (urb_id);


--
-- Name: core_urbanizaciones core_urbanizaciones_urb_codigo_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.core_urbanizaciones
    ADD CONSTRAINT core_urbanizaciones_urb_codigo_unique UNIQUE (urb_codigo);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: fin_obligaciones_alicuotas fin_obligaciones_alicuotas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.fin_obligaciones_alicuotas
    ADD CONSTRAINT fin_obligaciones_alicuotas_pkey PRIMARY KEY (obl_id);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: op_activos_infraestructura op_activos_infraestructura_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_activos_infraestructura
    ADD CONSTRAINT op_activos_infraestructura_pkey PRIMARY KEY (act_id);


--
-- Name: op_areas_sociales op_areas_sociales_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_areas_sociales
    ADD CONSTRAINT op_areas_sociales_pkey PRIMARY KEY (are_id);


--
-- Name: op_biometria_residentes op_biometria_residentes_bio_urbanizacion_id_bio_persona_id_bio_; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_biometria_residentes
    ADD CONSTRAINT op_biometria_residentes_bio_urbanizacion_id_bio_persona_id_bio_ UNIQUE (bio_urbanizacion_id, bio_persona_id, bio_tipo_credencial);


--
-- Name: op_biometria_residentes op_biometria_residentes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_biometria_residentes
    ADD CONSTRAINT op_biometria_residentes_pkey PRIMARY KEY (bio_id);


--
-- Name: op_biometria_sync_logs op_biometria_sync_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_biometria_sync_logs
    ADD CONSTRAINT op_biometria_sync_logs_pkey PRIMARY KEY (slog_id);


--
-- Name: op_comunicados op_comunicados_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_comunicados
    ADD CONSTRAINT op_comunicados_pkey PRIMARY KEY (com_id);


--
-- Name: op_control_accesos op_control_accesos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_control_accesos
    ADD CONSTRAINT op_control_accesos_pkey PRIMARY KEY (acc_id);


--
-- Name: op_garita_dispositivos op_garita_dispositivos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_garita_dispositivos
    ADD CONSTRAINT op_garita_dispositivos_pkey PRIMARY KEY (disp_id);


--
-- Name: op_garitas op_garitas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_garitas
    ADD CONSTRAINT op_garitas_pkey PRIMARY KEY (gar_id);


--
-- Name: op_invitaciones_qr op_invitaciones_qr_inv_codigo_token_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_invitaciones_qr
    ADD CONSTRAINT op_invitaciones_qr_inv_codigo_token_unique UNIQUE (inv_codigo_token);


--
-- Name: op_invitaciones_qr op_invitaciones_qr_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_invitaciones_qr
    ADD CONSTRAINT op_invitaciones_qr_pkey PRIMARY KEY (inv_id);


--
-- Name: op_lista_negra op_lista_negra_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_lista_negra
    ADD CONSTRAINT op_lista_negra_pkey PRIMARY KEY (lis_id);


--
-- Name: op_novedades_turno op_novedades_turno_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_novedades_turno
    ADD CONSTRAINT op_novedades_turno_pkey PRIMARY KEY (nov_id);


--
-- Name: op_ordenes_mantenimiento op_ordenes_mantenimiento_ord_codigo_folio_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_ordenes_mantenimiento
    ADD CONSTRAINT op_ordenes_mantenimiento_ord_codigo_folio_unique UNIQUE (ord_codigo_folio);


--
-- Name: op_ordenes_mantenimiento op_ordenes_mantenimiento_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_ordenes_mantenimiento
    ADD CONSTRAINT op_ordenes_mantenimiento_pkey PRIMARY KEY (ord_id);


--
-- Name: op_paqueteria op_paqueteria_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_paqueteria
    ADD CONSTRAINT op_paqueteria_pkey PRIMARY KEY (paq_id);


--
-- Name: op_proveedores op_proveedores_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_proveedores
    ADD CONSTRAINT op_proveedores_pkey PRIMARY KEY (prv_id);


--
-- Name: op_push_subscriptions op_push_subscriptions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_push_subscriptions
    ADD CONSTRAINT op_push_subscriptions_pkey PRIMARY KEY (sub_id);


--
-- Name: op_reservas_amenidades op_reservas_amenidades_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_reservas_amenidades
    ADD CONSTRAINT op_reservas_amenidades_pkey PRIMARY KEY (res_id);


--
-- Name: op_votaciones op_votaciones_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_votaciones
    ADD CONSTRAINT op_votaciones_pkey PRIMARY KEY (vot_id);


--
-- Name: op_votos_emitidos op_votos_emitidos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_votos_emitidos
    ADD CONSTRAINT op_votos_emitidos_pkey PRIMARY KEY (vto_id);


--
-- Name: op_votos_emitidos op_votos_emitidos_vto_votacion_id_vto_inmueble_id_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_votos_emitidos
    ADD CONSTRAINT op_votos_emitidos_vto_votacion_id_vto_inmueble_id_unique UNIQUE (vto_votacion_id, vto_inmueble_id);


--
-- Name: sec_permisos sec_permisos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sec_permisos
    ADD CONSTRAINT sec_permisos_pkey PRIMARY KEY (prm_id);


--
-- Name: sec_permisos sec_permisos_prm_slug_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sec_permisos
    ADD CONSTRAINT sec_permisos_prm_slug_unique UNIQUE (prm_slug);


--
-- Name: sec_rol_permisos sec_rol_permisos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sec_rol_permisos
    ADD CONSTRAINT sec_rol_permisos_pkey PRIMARY KEY (rxp_id);


--
-- Name: sec_rol_vistas sec_rol_vistas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sec_rol_vistas
    ADD CONSTRAINT sec_rol_vistas_pkey PRIMARY KEY (rxv_id);


--
-- Name: sec_roles sec_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sec_roles
    ADD CONSTRAINT sec_roles_pkey PRIMARY KEY (rol_id);


--
-- Name: sec_roles sec_roles_rol_codigo_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sec_roles
    ADD CONSTRAINT sec_roles_rol_codigo_unique UNIQUE (rol_codigo);


--
-- Name: sec_temas sec_temas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sec_temas
    ADD CONSTRAINT sec_temas_pkey PRIMARY KEY (tem_id);


--
-- Name: sec_temas sec_temas_tem_codigo_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sec_temas
    ADD CONSTRAINT sec_temas_tem_codigo_unique UNIQUE (tem_codigo);


--
-- Name: sec_urbanizacion_usuarios sec_urbanizacion_usuarios_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sec_urbanizacion_usuarios
    ADD CONSTRAINT sec_urbanizacion_usuarios_pkey PRIMARY KEY (uxu_id);


--
-- Name: sec_users sec_users_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sec_users
    ADD CONSTRAINT sec_users_pkey PRIMARY KEY (usr_id);


--
-- Name: sec_users sec_users_usr_email_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sec_users
    ADD CONSTRAINT sec_users_usr_email_unique UNIQUE (usr_email);


--
-- Name: sec_usuario_roles sec_usuario_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sec_usuario_roles
    ADD CONSTRAINT sec_usuario_roles_pkey PRIMARY KEY (uxr_id);


--
-- Name: sec_vistas sec_vistas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sec_vistas
    ADD CONSTRAINT sec_vistas_pkey PRIMARY KEY (vis_id);


--
-- Name: sec_vistas sec_vistas_vis_codigo_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sec_vistas
    ADD CONSTRAINT sec_vistas_vis_codigo_unique UNIQUE (vis_codigo);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: core_inmueble_residentes uq_core_inmueble_residentes; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.core_inmueble_residentes
    ADD CONSTRAINT uq_core_inmueble_residentes UNIQUE (res_inmueble_id, res_persona_id);


--
-- Name: core_inmuebles uq_core_inmuebles_ubicacion; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.core_inmuebles
    ADD CONSTRAINT uq_core_inmuebles_ubicacion UNIQUE (inm_urbanizacion_id, inm_manzana_bloque, inm_numero_villa);


--
-- Name: sec_rol_permisos uq_sec_rol_permisos; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sec_rol_permisos
    ADD CONSTRAINT uq_sec_rol_permisos UNIQUE (rxp_rol_id, rxp_permiso_id);


--
-- Name: sec_rol_vistas uq_sec_rol_vistas; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sec_rol_vistas
    ADD CONSTRAINT uq_sec_rol_vistas UNIQUE (rxv_rol_id, rxv_vista_id);


--
-- Name: sec_urbanizacion_usuarios uq_sec_urbanizacion_usuarios; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sec_urbanizacion_usuarios
    ADD CONSTRAINT uq_sec_urbanizacion_usuarios UNIQUE (uxu_urbanizacion_id, uxu_user_id);


--
-- Name: sec_usuario_roles uq_sec_usuario_roles; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sec_usuario_roles
    ADD CONSTRAINT uq_sec_usuario_roles UNIQUE (uxr_urbanizacion_usuario_id, uxr_rol_id);


--
-- Name: idx_aud_reportes_fecha; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_aud_reportes_fecha ON public.aud_reportes_descargas USING btree (rep_created_at);


--
-- Name: idx_aud_reportes_urb; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_aud_reportes_urb ON public.aud_reportes_descargas USING btree (rep_urbanizacion_id);


--
-- Name: idx_aud_reportes_usr; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_aud_reportes_usr ON public.aud_reportes_descargas USING btree (rep_user_id);


--
-- Name: idx_aud_trazabilidad_fecha; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_aud_trazabilidad_fecha ON public.aud_trazabilidad USING btree (aud_created_at);


--
-- Name: idx_aud_trazabilidad_tabla_registro; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_aud_trazabilidad_tabla_registro ON public.aud_trazabilidad USING btree (aud_tabla, aud_registro_id);


--
-- Name: idx_aud_trazabilidad_urb; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_aud_trazabilidad_urb ON public.aud_trazabilidad USING btree (aud_urbanizacion_id);


--
-- Name: idx_aud_trazabilidad_usr; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_aud_trazabilidad_usr ON public.aud_trazabilidad USING btree (aud_user_id);


--
-- Name: idx_core_inmuebles_urbanizacion; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_core_inmuebles_urbanizacion ON public.core_inmuebles USING btree (inm_urbanizacion_id);


--
-- Name: idx_core_personas_identificacion; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_core_personas_identificacion ON public.core_personas USING btree (per_identificacion);


--
-- Name: idx_core_personas_nombre_completo; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_core_personas_nombre_completo ON public.core_personas USING btree (per_apellidos, per_nombres);


--
-- Name: idx_inmueble_placa; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_inmueble_placa ON public.core_inmueble_vehiculos USING btree (veh_inmueble_id, veh_placa);


--
-- Name: idx_invitaciones_estado; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_invitaciones_estado ON public.op_invitaciones_qr USING btree (inv_estado, inv_valido_hasta);


--
-- Name: idx_invitaciones_token; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_invitaciones_token ON public.op_invitaciones_qr USING btree (inv_urbanizacion_id, inv_codigo_token);


--
-- Name: idx_op_control_accesos_estado; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_op_control_accesos_estado ON public.op_control_accesos USING btree (acc_urbanizacion_id, acc_estado);


--
-- Name: idx_op_control_accesos_ingreso; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_op_control_accesos_ingreso ON public.op_control_accesos USING btree (acc_fecha_ingreso);


--
-- Name: idx_op_control_accesos_per; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_op_control_accesos_per ON public.op_control_accesos USING btree (acc_persona_id);


--
-- Name: idx_op_control_accesos_placa; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_op_control_accesos_placa ON public.op_control_accesos USING btree (acc_vehiculo_placa);


--
-- Name: idx_op_control_accesos_urb; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_op_control_accesos_urb ON public.op_control_accesos USING btree (acc_urbanizacion_id);


--
-- Name: idx_op_lista_negra_placa; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_op_lista_negra_placa ON public.op_lista_negra USING btree (lis_urbanizacion_id, lis_placa_vehiculo);


--
-- Name: idx_sec_permisos_modulo_accion; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_sec_permisos_modulo_accion ON public.sec_permisos USING btree (prm_modulo, prm_accion);


--
-- Name: idx_sec_users_email; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_sec_users_email ON public.sec_users USING btree (usr_email);


--
-- Name: idx_sec_vistas_categoria_orden; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_sec_vistas_categoria_orden ON public.sec_vistas USING btree (vis_categoria, vis_orden);


--
-- Name: idx_vehiculo_rfid; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_vehiculo_rfid ON public.core_inmueble_vehiculos USING btree (veh_tag_rfid);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: op_comunicados_com_urbanizacion_id_com_activo_com_prioridad_ind; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX op_comunicados_com_urbanizacion_id_com_activo_com_prioridad_ind ON public.op_comunicados USING btree (com_urbanizacion_id, com_activo, com_prioridad);


--
-- Name: op_novedades_turno_nov_urbanizacion_id_nov_tipo_nov_prioridad_i; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX op_novedades_turno_nov_urbanizacion_id_nov_tipo_nov_prioridad_i ON public.op_novedades_turno USING btree (nov_urbanizacion_id, nov_tipo, nov_prioridad);


--
-- Name: op_paqueteria_paq_urbanizacion_id_paq_estado_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX op_paqueteria_paq_urbanizacion_id_paq_estado_index ON public.op_paqueteria USING btree (paq_urbanizacion_id, paq_estado);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: aud_reportes_descargas aud_reportes_descargas_rep_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.aud_reportes_descargas
    ADD CONSTRAINT aud_reportes_descargas_rep_user_id_foreign FOREIGN KEY (rep_user_id) REFERENCES public.sec_users(usr_id) ON DELETE SET NULL;


--
-- Name: aud_trazabilidad aud_trazabilidad_aud_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.aud_trazabilidad
    ADD CONSTRAINT aud_trazabilidad_aud_user_id_foreign FOREIGN KEY (aud_user_id) REFERENCES public.sec_users(usr_id) ON DELETE SET NULL;


--
-- Name: core_inmueble_residentes core_inmueble_residentes_res_inmueble_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.core_inmueble_residentes
    ADD CONSTRAINT core_inmueble_residentes_res_inmueble_id_foreign FOREIGN KEY (res_inmueble_id) REFERENCES public.core_inmuebles(inm_id) ON DELETE CASCADE;


--
-- Name: core_inmueble_residentes core_inmueble_residentes_res_persona_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.core_inmueble_residentes
    ADD CONSTRAINT core_inmueble_residentes_res_persona_id_foreign FOREIGN KEY (res_persona_id) REFERENCES public.core_personas(per_id) ON DELETE CASCADE;


--
-- Name: core_inmueble_vehiculos core_inmueble_vehiculos_veh_inmueble_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.core_inmueble_vehiculos
    ADD CONSTRAINT core_inmueble_vehiculos_veh_inmueble_id_foreign FOREIGN KEY (veh_inmueble_id) REFERENCES public.core_inmuebles(inm_id) ON DELETE CASCADE;


--
-- Name: core_inmueble_vehiculos core_inmueble_vehiculos_veh_persona_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.core_inmueble_vehiculos
    ADD CONSTRAINT core_inmueble_vehiculos_veh_persona_id_foreign FOREIGN KEY (veh_persona_id) REFERENCES public.core_personas(per_id) ON DELETE SET NULL;


--
-- Name: core_inmuebles core_inmuebles_inm_urbanizacion_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.core_inmuebles
    ADD CONSTRAINT core_inmuebles_inm_urbanizacion_id_foreign FOREIGN KEY (inm_urbanizacion_id) REFERENCES public.core_urbanizaciones(urb_id) ON DELETE CASCADE;


--
-- Name: core_urbanizaciones core_urbanizaciones_urb_tema_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.core_urbanizaciones
    ADD CONSTRAINT core_urbanizaciones_urb_tema_id_foreign FOREIGN KEY (urb_tema_id) REFERENCES public.sec_temas(tem_id) ON DELETE SET NULL;


--
-- Name: fin_obligaciones_alicuotas fin_obligaciones_alicuotas_obl_inmueble_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.fin_obligaciones_alicuotas
    ADD CONSTRAINT fin_obligaciones_alicuotas_obl_inmueble_id_fkey FOREIGN KEY (obl_inmueble_id) REFERENCES public.core_inmuebles(inm_id) ON DELETE CASCADE;


--
-- Name: op_activos_infraestructura op_activos_infraestructura_act_urbanizacion_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_activos_infraestructura
    ADD CONSTRAINT op_activos_infraestructura_act_urbanizacion_id_foreign FOREIGN KEY (act_urbanizacion_id) REFERENCES public.core_urbanizaciones(urb_id) ON DELETE CASCADE;


--
-- Name: op_biometria_residentes op_biometria_residentes_bio_persona_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_biometria_residentes
    ADD CONSTRAINT op_biometria_residentes_bio_persona_id_foreign FOREIGN KEY (bio_persona_id) REFERENCES public.core_personas(per_id) ON DELETE CASCADE;


--
-- Name: op_biometria_residentes op_biometria_residentes_bio_urbanizacion_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_biometria_residentes
    ADD CONSTRAINT op_biometria_residentes_bio_urbanizacion_id_foreign FOREIGN KEY (bio_urbanizacion_id) REFERENCES public.core_urbanizaciones(urb_id) ON DELETE CASCADE;


--
-- Name: op_comunicados op_comunicados_com_autor_usr_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_comunicados
    ADD CONSTRAINT op_comunicados_com_autor_usr_id_foreign FOREIGN KEY (com_autor_usr_id) REFERENCES public.sec_users(usr_id) ON DELETE CASCADE;


--
-- Name: op_comunicados op_comunicados_com_urbanizacion_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_comunicados
    ADD CONSTRAINT op_comunicados_com_urbanizacion_id_foreign FOREIGN KEY (com_urbanizacion_id) REFERENCES public.core_urbanizaciones(urb_id) ON DELETE CASCADE;


--
-- Name: op_control_accesos op_control_accesos_acc_garita_ingreso_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_control_accesos
    ADD CONSTRAINT op_control_accesos_acc_garita_ingreso_id_foreign FOREIGN KEY (acc_garita_ingreso_id) REFERENCES public.op_garitas(gar_id) ON DELETE SET NULL;


--
-- Name: op_control_accesos op_control_accesos_acc_garita_salida_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_control_accesos
    ADD CONSTRAINT op_control_accesos_acc_garita_salida_id_foreign FOREIGN KEY (acc_garita_salida_id) REFERENCES public.op_garitas(gar_id) ON DELETE SET NULL;


--
-- Name: op_control_accesos op_control_accesos_acc_guardia_ingreso_usr_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_control_accesos
    ADD CONSTRAINT op_control_accesos_acc_guardia_ingreso_usr_id_foreign FOREIGN KEY (acc_guardia_ingreso_usr_id) REFERENCES public.sec_users(usr_id) ON DELETE SET NULL;


--
-- Name: op_control_accesos op_control_accesos_acc_guardia_salida_usr_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_control_accesos
    ADD CONSTRAINT op_control_accesos_acc_guardia_salida_usr_id_foreign FOREIGN KEY (acc_guardia_salida_usr_id) REFERENCES public.sec_users(usr_id) ON DELETE SET NULL;


--
-- Name: op_control_accesos op_control_accesos_acc_inmueble_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_control_accesos
    ADD CONSTRAINT op_control_accesos_acc_inmueble_id_foreign FOREIGN KEY (acc_inmueble_id) REFERENCES public.core_inmuebles(inm_id) ON DELETE SET NULL;


--
-- Name: op_control_accesos op_control_accesos_acc_persona_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_control_accesos
    ADD CONSTRAINT op_control_accesos_acc_persona_id_foreign FOREIGN KEY (acc_persona_id) REFERENCES public.core_personas(per_id);


--
-- Name: op_control_accesos op_control_accesos_acc_urbanizacion_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_control_accesos
    ADD CONSTRAINT op_control_accesos_acc_urbanizacion_id_foreign FOREIGN KEY (acc_urbanizacion_id) REFERENCES public.core_urbanizaciones(urb_id) ON DELETE CASCADE;


--
-- Name: op_garitas op_garitas_gar_urbanizacion_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_garitas
    ADD CONSTRAINT op_garitas_gar_urbanizacion_id_foreign FOREIGN KEY (gar_urbanizacion_id) REFERENCES public.core_urbanizaciones(urb_id) ON DELETE CASCADE;


--
-- Name: op_invitaciones_qr op_invitaciones_qr_inv_inmueble_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_invitaciones_qr
    ADD CONSTRAINT op_invitaciones_qr_inv_inmueble_id_foreign FOREIGN KEY (inv_inmueble_id) REFERENCES public.core_inmuebles(inm_id) ON DELETE SET NULL;


--
-- Name: op_invitaciones_qr op_invitaciones_qr_inv_urbanizacion_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_invitaciones_qr
    ADD CONSTRAINT op_invitaciones_qr_inv_urbanizacion_id_foreign FOREIGN KEY (inv_urbanizacion_id) REFERENCES public.core_urbanizaciones(urb_id) ON DELETE CASCADE;


--
-- Name: op_invitaciones_qr op_invitaciones_qr_inv_usuario_creador_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_invitaciones_qr
    ADD CONSTRAINT op_invitaciones_qr_inv_usuario_creador_id_foreign FOREIGN KEY (inv_usuario_creador_id) REFERENCES public.sec_users(usr_id) ON DELETE CASCADE;


--
-- Name: op_lista_negra op_lista_negra_lis_persona_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_lista_negra
    ADD CONSTRAINT op_lista_negra_lis_persona_id_foreign FOREIGN KEY (lis_persona_id) REFERENCES public.core_personas(per_id) ON DELETE CASCADE;


--
-- Name: op_lista_negra op_lista_negra_lis_registrado_por_usr_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_lista_negra
    ADD CONSTRAINT op_lista_negra_lis_registrado_por_usr_id_foreign FOREIGN KEY (lis_registrado_por_usr_id) REFERENCES public.sec_users(usr_id) ON DELETE SET NULL;


--
-- Name: op_lista_negra op_lista_negra_lis_urbanizacion_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_lista_negra
    ADD CONSTRAINT op_lista_negra_lis_urbanizacion_id_foreign FOREIGN KEY (lis_urbanizacion_id) REFERENCES public.core_urbanizaciones(urb_id) ON DELETE CASCADE;


--
-- Name: op_novedades_turno op_novedades_turno_nov_garita_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_novedades_turno
    ADD CONSTRAINT op_novedades_turno_nov_garita_id_foreign FOREIGN KEY (nov_garita_id) REFERENCES public.op_garitas(gar_id) ON DELETE SET NULL;


--
-- Name: op_novedades_turno op_novedades_turno_nov_guardia_usr_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_novedades_turno
    ADD CONSTRAINT op_novedades_turno_nov_guardia_usr_id_foreign FOREIGN KEY (nov_guardia_usr_id) REFERENCES public.sec_users(usr_id) ON DELETE CASCADE;


--
-- Name: op_novedades_turno op_novedades_turno_nov_urbanizacion_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_novedades_turno
    ADD CONSTRAINT op_novedades_turno_nov_urbanizacion_id_foreign FOREIGN KEY (nov_urbanizacion_id) REFERENCES public.core_urbanizaciones(urb_id) ON DELETE CASCADE;


--
-- Name: op_ordenes_mantenimiento op_ordenes_mantenimiento_ord_activo_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_ordenes_mantenimiento
    ADD CONSTRAINT op_ordenes_mantenimiento_ord_activo_id_foreign FOREIGN KEY (ord_activo_id) REFERENCES public.op_activos_infraestructura(act_id) ON DELETE SET NULL;


--
-- Name: op_ordenes_mantenimiento op_ordenes_mantenimiento_ord_proveedor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_ordenes_mantenimiento
    ADD CONSTRAINT op_ordenes_mantenimiento_ord_proveedor_id_foreign FOREIGN KEY (ord_proveedor_id) REFERENCES public.op_proveedores(prv_id) ON DELETE SET NULL;


--
-- Name: op_ordenes_mantenimiento op_ordenes_mantenimiento_ord_urbanizacion_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_ordenes_mantenimiento
    ADD CONSTRAINT op_ordenes_mantenimiento_ord_urbanizacion_id_foreign FOREIGN KEY (ord_urbanizacion_id) REFERENCES public.core_urbanizaciones(urb_id) ON DELETE CASCADE;


--
-- Name: op_paqueteria op_paqueteria_paq_guardia_recibe_usr_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_paqueteria
    ADD CONSTRAINT op_paqueteria_paq_guardia_recibe_usr_id_foreign FOREIGN KEY (paq_guardia_recibe_usr_id) REFERENCES public.sec_users(usr_id) ON DELETE CASCADE;


--
-- Name: op_paqueteria op_paqueteria_paq_inmueble_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_paqueteria
    ADD CONSTRAINT op_paqueteria_paq_inmueble_id_foreign FOREIGN KEY (paq_inmueble_id) REFERENCES public.core_inmuebles(inm_id) ON DELETE CASCADE;


--
-- Name: op_paqueteria op_paqueteria_paq_urbanizacion_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_paqueteria
    ADD CONSTRAINT op_paqueteria_paq_urbanizacion_id_foreign FOREIGN KEY (paq_urbanizacion_id) REFERENCES public.core_urbanizaciones(urb_id) ON DELETE CASCADE;


--
-- Name: op_proveedores op_proveedores_prv_urbanizacion_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_proveedores
    ADD CONSTRAINT op_proveedores_prv_urbanizacion_id_foreign FOREIGN KEY (prv_urbanizacion_id) REFERENCES public.core_urbanizaciones(urb_id) ON DELETE CASCADE;


--
-- Name: op_push_subscriptions op_push_subscriptions_sub_urbanizacion_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.op_push_subscriptions
    ADD CONSTRAINT op_push_subscriptions_sub_urbanizacion_id_foreign FOREIGN KEY (sub_urbanizacion_id) REFERENCES public.core_urbanizaciones(urb_id) ON DELETE CASCADE;


--
-- Name: sec_rol_permisos sec_rol_permisos_rxp_permiso_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sec_rol_permisos
    ADD CONSTRAINT sec_rol_permisos_rxp_permiso_id_foreign FOREIGN KEY (rxp_permiso_id) REFERENCES public.sec_permisos(prm_id) ON DELETE CASCADE;


--
-- Name: sec_rol_permisos sec_rol_permisos_rxp_rol_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sec_rol_permisos
    ADD CONSTRAINT sec_rol_permisos_rxp_rol_id_foreign FOREIGN KEY (rxp_rol_id) REFERENCES public.sec_roles(rol_id) ON DELETE CASCADE;


--
-- Name: sec_rol_vistas sec_rol_vistas_rxv_rol_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sec_rol_vistas
    ADD CONSTRAINT sec_rol_vistas_rxv_rol_id_foreign FOREIGN KEY (rxv_rol_id) REFERENCES public.sec_roles(rol_id) ON DELETE CASCADE;


--
-- Name: sec_rol_vistas sec_rol_vistas_rxv_vista_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sec_rol_vistas
    ADD CONSTRAINT sec_rol_vistas_rxv_vista_id_foreign FOREIGN KEY (rxv_vista_id) REFERENCES public.sec_vistas(vis_id) ON DELETE CASCADE;


--
-- Name: sec_urbanizacion_usuarios sec_urbanizacion_usuarios_uxu_urbanizacion_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sec_urbanizacion_usuarios
    ADD CONSTRAINT sec_urbanizacion_usuarios_uxu_urbanizacion_id_foreign FOREIGN KEY (uxu_urbanizacion_id) REFERENCES public.core_urbanizaciones(urb_id) ON DELETE CASCADE;


--
-- Name: sec_urbanizacion_usuarios sec_urbanizacion_usuarios_uxu_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sec_urbanizacion_usuarios
    ADD CONSTRAINT sec_urbanizacion_usuarios_uxu_user_id_foreign FOREIGN KEY (uxu_user_id) REFERENCES public.sec_users(usr_id) ON DELETE CASCADE;


--
-- Name: sec_users sec_users_usr_persona_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sec_users
    ADD CONSTRAINT sec_users_usr_persona_id_foreign FOREIGN KEY (usr_persona_id) REFERENCES public.core_personas(per_id) ON DELETE SET NULL;


--
-- Name: sec_usuario_roles sec_usuario_roles_uxr_rol_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sec_usuario_roles
    ADD CONSTRAINT sec_usuario_roles_uxr_rol_id_foreign FOREIGN KEY (uxr_rol_id) REFERENCES public.sec_roles(rol_id) ON DELETE CASCADE;


--
-- Name: sec_usuario_roles sec_usuario_roles_uxr_urbanizacion_usuario_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sec_usuario_roles
    ADD CONSTRAINT sec_usuario_roles_uxr_urbanizacion_usuario_id_foreign FOREIGN KEY (uxr_urbanizacion_usuario_id) REFERENCES public.sec_urbanizacion_usuarios(uxu_id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict 3zYUhP6Lq9FVNkgKeiF3YbUAdWpsdzIuns8qM8ozzaov8DM9aeebVb2YYyYkCc7

