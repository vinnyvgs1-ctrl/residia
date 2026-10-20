<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AreaSocialController extends Controller
{
    /**
     * Listar catálogo de áreas comunes / amenidades de la sede
     */
    public function index(Request $request): JsonResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');

        $areas = DB::table('op_areas_sociales')
            ->where('are_urbanizacion_id', $urbanizacionId)
            ->whereNull('deleted_at')
            ->orderBy('are_nombre', 'asc')
            ->get();

        // Inicializar amenidades base por defecto si la urbanización no tiene
        if ($areas->isEmpty()) {
            $defaultAreas = [
                [
                    'are_urbanizacion_id' => $urbanizacionId,
                    'are_nombre' => 'Cancha de Pádel Cristal Central',
                    'are_tipo' => 'PADEL',
                    'are_descripcion' => 'Cancha reglamentaria con iluminación LED para juego nocturno y césped sintético monofilamento.',
                    'are_aforo_maximo' => 4,
                    'are_costo_reserva' => 15.00,
                    'are_garantia_deposito' => 0.00,
                    'are_hora_apertura' => '07:00:00',
                    'are_hora_cierre' => '23:00:00',
                    'are_duracion_turno_horas' => 2,
                    'are_dias_anticipacion_maxima' => 7,
                    'are_requiere_aprobacion' => false,
                    'are_activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'are_urbanizacion_id' => $urbanizacionId,
                    'are_nombre' => 'Pérgola & Zona BBQ Los Robles',
                    'are_tipo' => 'BBQ',
                    'are_descripcion' => 'Parrilla en acero inoxidable, mesones de granito, fregadero y mesas rústicas techadas.',
                    'are_aforo_maximo' => 25,
                    'are_costo_reserva' => 20.00,
                    'are_garantia_deposito' => 30.00,
                    'are_hora_apertura' => '11:00:00',
                    'are_hora_cierre' => '22:00:00',
                    'are_duracion_turno_horas' => 4,
                    'are_dias_anticipacion_maxima' => 15,
                    'are_requiere_aprobacion' => true,
                    'are_activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'are_urbanizacion_id' => $urbanizacionId,
                    'are_nombre' => 'Casa Club & Salón de Eventos Principal',
                    'are_tipo' => 'CASA_CLUB',
                    'are_descripcion' => 'Salón climatizado para reuniones familiares, cumpleaños y asambleas. Incluye mobiliario para 60 personas.',
                    'are_aforo_maximo' => 60,
                    'are_costo_reserva' => 50.00,
                    'are_garantia_deposito' => 100.00,
                    'are_hora_apertura' => '10:00:00',
                    'are_hora_cierre' => '01:00:00',
                    'are_duracion_turno_horas' => 6,
                    'are_dias_anticipacion_maxima' => 30,
                    'are_requiere_aprobacion' => true,
                    'are_activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'are_urbanizacion_id' => $urbanizacionId,
                    'are_nombre' => 'Piscina Semi-Olímpica & Solárium',
                    'are_tipo' => 'PISCINA',
                    'are_descripcion' => 'Área húmeda con piscina templada, reposeras y vestidores.',
                    'are_aforo_maximo' => 30,
                    'are_costo_reserva' => 0.00,
                    'are_garantia_deposito' => 0.00,
                    'are_hora_apertura' => '09:00:00',
                    'are_hora_cierre' => '20:00:00',
                    'are_duracion_turno_horas' => 3,
                    'are_dias_anticipacion_maxima' => 3,
                    'are_requiere_aprobacion' => false,
                    'are_activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ];

            foreach ($defaultAreas as $area) {
                DB::table('op_areas_sociales')->insert($area);
            }

            $areas = DB::table('op_areas_sociales')
                ->where('are_urbanizacion_id', $urbanizacionId)
                ->whereNull('deleted_at')
                ->orderBy('are_nombre', 'asc')
                ->get();
        }

        return response()->json([
            'success' => true,
            'data' => $areas
        ]);
    }

    /**
     * Crear una nueva área social en el catálogo
     */
    public function store(Request $request): JsonResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');

        $validated = $request->validate([
            'are_nombre' => 'required|string|max:100',
            'are_tipo' => 'required|string|max:50',
            'are_descripcion' => 'nullable|string',
            'are_aforo_maximo' => 'required|integer|min:1',
            'are_costo_reserva' => 'required|numeric|min:0',
            'are_garantia_deposito' => 'nullable|numeric|min:0',
            'are_hora_apertura' => 'required',
            'are_hora_cierre' => 'required',
            'are_duracion_turno_horas' => 'required|integer|min:1|max:24',
            'are_dias_anticipacion_maxima' => 'required|integer|min:1',
            'are_requiere_aprobacion' => 'boolean',
        ]);

        $validated['are_urbanizacion_id'] = $urbanizacionId;
        $validated['are_activo'] = true;
        $validated['created_at'] = now();
        $validated['updated_at'] = now();

        $id = DB::table('op_areas_sociales')->insertGetId($validated, 'are_id');

        AuditLogger::log('AREA_SOCIAL_CREADA', 'op_areas_sociales', $id, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Área social agregada al catálogo exitosamente.',
            'are_id' => $id
        ]);
    }

    /**
     * Listar reservas vigentes y pasadas (con filtro opcional de área o fecha)
     */
    public function reservas(Request $request): JsonResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');
        $areaId = $request->input('area_id');
        $fecha = $request->input('fecha');

        $query = DB::table('op_reservas_amenidades')
            ->join('op_areas_sociales', 'op_reservas_amenidades.res_area_id', '=', 'op_areas_sociales.are_id')
            ->join('core_inmuebles', 'op_reservas_amenidades.res_inmueble_id', '=', 'core_inmuebles.inm_id')
            ->join('core_personas', 'op_reservas_amenidades.res_persona_id', '=', 'core_personas.per_id')
            ->where('op_reservas_amenidades.res_urbanizacion_id', $urbanizacionId)
            ->whereNull('op_reservas_amenidades.deleted_at');

        if ($areaId) {
            $query->where('op_reservas_amenidades.res_area_id', $areaId);
        }

        if ($fecha) {
            $query->where('op_reservas_amenidades.res_fecha', $fecha);
        }

        $reservas = $query->select(
            'op_reservas_amenidades.*',
            'op_areas_sociales.are_nombre',
            'op_areas_sociales.are_tipo',
            'core_inmuebles.inm_manzana_bloque',
            'core_inmuebles.inm_numero_villa',
            'core_personas.per_nombres',
            'core_personas.per_apellidos',
            'core_personas.per_telefono'
        )
        ->orderBy('op_reservas_amenidades.res_fecha', 'desc')
        ->orderBy('op_reservas_amenidades.res_hora_inicio', 'asc')
        ->get();

        return response()->json([
            'success' => true,
            'data' => $reservas
        ]);
    }

    /**
     * Crear una nueva reserva (con validación de morosidad y anti-solapamiento)
     */
    public function crearReserva(Request $request): JsonResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');
        $user = auth('api')->user();

        $validated = $request->validate([
            'area_id' => 'required|exists:op_areas_sociales,are_id',
            'inmueble_id' => 'required|exists:core_inmuebles,inm_id',
            'fecha' => 'required|date|after_or_equal:today',
            'hora_inicio' => 'required',
            'hora_fin' => 'required|after:hora_inicio',
            'cantidad_invitados' => 'required|integer|min:1',
            'motivo_evento' => 'nullable|string',
            'lista_invitados' => 'nullable|array', // Nombres o placas para pre-autorizar en garita
        ]);

        // 1. VALIDACIÓN FINANCIERA: Si el inmueble tiene cuotas de alícuota en mora, no puede reservar
        $cuotasMora = DB::table('fin_obligaciones_alicuotas')
            ->where('obl_inmueble_id', $validated['inmueble_id'])
            ->whereIn('obl_estado', ['PENDIENTE', 'VENCIDO'])
            ->whereNull('deleted_at')
            ->count();

        if ($cuotasMora > 0) {
            return response()->json([
                'success' => false,
                'message' => "El inmueble presenta {$cuotasMora} cuota(s) de alícuota pendiente(s). Para realizar reservas en áreas sociales debe encontrarse al día."
            ], 422);
        }

        // 2. VALIDACIÓN DE HORARIO Y AFORO DEL ÁREA
        $area = DB::table('op_areas_sociales')
            ->where('are_id', $validated['area_id'])
            ->first();

        if ($validated['cantidad_invitados'] > $area->are_aforo_maximo) {
            return response()->json([
                'success' => false,
                'message' => "La cantidad de invitados ({$validated['cantidad_invitados']}) excede el aforo máximo permitido ({$area->are_aforo_maximo} personas)."
            ], 422);
        }

        // 3. VALIDACIÓN ANTI-SOLAPAMIENTO (Conflict detection)
        $solapamiento = DB::table('op_reservas_amenidades')
            ->where('res_area_id', $validated['area_id'])
            ->where('res_fecha', $validated['fecha'])
            ->whereIn('res_estado', ['CONFIRMADA', 'PENDIENTE_PAGO', 'EN_CURSO'])
            ->whereNull('deleted_at')
            ->where(function ($q) use ($validated) {
                $q->whereBetween('res_hora_inicio', [$validated['hora_inicio'], $validated['hora_fin']])
                  ->orWhereBetween('res_hora_fin', [$validated['hora_inicio'], $validated['hora_fin']])
                  ->orWhere(function ($q2) use ($validated) {
                      $q2->where('res_hora_inicio', '<=', $validated['hora_inicio'])
                         ->where('res_hora_fin', '>=', $validated['hora_fin']);
                  });
            })
            ->exists();

        if ($solapamiento) {
            return response()->json([
                'success' => false,
                'message' => 'El horario solicitado entra en conflicto con otra reserva ya programada en esta área social.'
            ], 409);
        }

        // 4. Determinar Persona ID que reserva
        $personaId = $user->usr_persona_id;
        if (!$personaId) {
            $primerHabitante = DB::table('core_inmueble_residentes')
                ->where('res_inmueble_id', $validated['inmueble_id'])
                ->value('res_persona_id');
            $personaId = $primerHabitante ?: 1;
        }

        $reservaId = DB::table('op_reservas_amenidades')->insertGetId([
            'res_urbanizacion_id' => $urbanizacionId,
            'res_area_id' => $validated['area_id'],
            'res_inmueble_id' => $validated['inmueble_id'],
            'res_persona_id' => $personaId,
            'res_fecha' => $validated['fecha'],
            'res_hora_inicio' => $validated['hora_inicio'],
            'res_hora_fin' => $validated['hora_fin'],
            'res_cantidad_invitados' => $validated['cantidad_invitados'],
            'res_monto_pagado' => (float)$area->are_costo_reserva,
            'res_monto_garantia' => (float)$area->are_garantia_deposito,
            'res_estado' => $area->are_requiere_aprobacion ? 'PENDIENTE_PAGO' : 'CONFIRMADA',
            'res_motivo_evento' => $validated['motivo_evento'] ?? 'Uso social / recreativo',
            'res_lista_invitados' => !empty($validated['lista_invitados']) ? json_encode($validated['lista_invitados']) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'res_id');

        AuditLogger::log(
            'RESERVA_AMENIDAD_CREADA',
            'op_reservas_amenidades',
            $reservaId,
            [
                'area' => $area->are_nombre,
                'fecha' => $validated['fecha'],
                'horario' => "{$validated['hora_inicio']} - {$validated['hora_fin']}",
                'invitados' => $validated['cantidad_invitados']
            ]
        );

        return response()->json([
            'success' => true,
            'message' => $area->are_requiere_aprobacion 
                ? 'Reserva registrada exitosamente. Pendiente de aprobación/garantía de administración.'
                : '✓ Reserva confirmada exitosamente. Se ha generado la programación del área.',
            'reserva_id' => $reservaId
        ]);
    }

    /**
     * Cancelar una reserva
     */
    public function cancelarReserva(int $id): JsonResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') ?: request()->header('X-Urbanizacion-Id');

        $reserva = DB::table('op_reservas_amenidades')
            ->where('res_id', $id)
            ->where('res_urbanizacion_id', $urbanizacionId)
            ->first();

        if (!$reserva) {
            return response()->json(['success' => false, 'message' => 'Reserva no encontrada'], 404);
        }

        DB::table('op_reservas_amenidades')
            ->where('res_id', $id)
            ->update([
                'res_estado' => 'CANCELADA',
                'updated_at' => now()
            ]);

        AuditLogger::log('RESERVA_AMENIDAD_CANCELADA', 'op_reservas_amenidades', $id);

        return response()->json([
            'success' => true,
            'message' => 'Reserva cancelada exitosamente.'
        ]);
    }
}
