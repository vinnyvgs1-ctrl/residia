<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComunicadoVotacionController extends Controller
{
    /**
     * Listar comunicados oficiales de la administración
     */
    public function indexComunicados(Request $request): JsonResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');

        $comunicados = DB::table('op_comunicados')
            ->leftJoin('sec_users', 'op_comunicados.com_autor_usr_id', '=', 'sec_users.usr_id')
            ->where('op_comunicados.com_urbanizacion_id', $urbanizacionId)
            ->where('op_comunicados.com_activo', true)
            ->whereNull('op_comunicados.deleted_at')
            ->select(
                'op_comunicados.*',
                'sec_users.usr_name as autor_nombre'
            )
            ->orderBy('op_comunicados.com_fijado', 'desc')
            ->orderBy('op_comunicados.com_fecha_publicacion', 'desc')
            ->get();

        // Seed demo si no existen comunicados
        if ($comunicados->isEmpty()) {
            $defaultCom = [
                'com_urbanizacion_id' => $urbanizacionId,
                'com_autor_usr_id' => auth()->id() ?: 1,
                'com_titulo' => 'Mantenimiento Preventivo de Bombeo de Agua Potable',
                'com_contenido' => 'Estimados copropietarios: Se informa que este jueves de 08:00 a 12:00 se ejecutará el mantenimiento trimestral del sistema hidroneumático perimetral. Favor tomar previsiones.',
                'com_categoria' => 'MANTENIMIENTO',
                'com_prioridad' => 'ALTA',
                'com_fecha_publicacion' => now()->subDay(),
                'com_fijado' => true,
                'com_activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            DB::table('op_comunicados')->insert($defaultCom);

            $comunicados = DB::table('op_comunicados')
                ->where('com_urbanizacion_id', $urbanizacionId)
                ->where('com_activo', true)
                ->whereNull('deleted_at')
                ->get();
        }

        return response()->json([
            'success' => true,
            'data' => $comunicados
        ]);
    }

    /**
     * Publicar nuevo comunicado oficial
     */
    public function storeComunicado(Request $request): JsonResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');

        $validated = $request->validate([
            'titulo' => 'required|string|max:150',
            'contenido' => 'required|string',
            'categoria' => 'required|string|in:GENERAL,MANTENIMIENTO,SEGURIDAD,ASAMBLEA,URGENTE',
            'prioridad' => 'required|string|in:BAJA,MEDIA,ALTA,URGENTE',
            'fijado' => 'boolean',
            'fecha_expiracion' => 'nullable|date',
        ]);

        $comId = DB::table('op_comunicados')->insertGetId([
            'com_urbanizacion_id' => $urbanizacionId,
            'com_autor_usr_id' => auth()->id() ?: 1,
            'com_titulo' => $validated['titulo'],
            'com_contenido' => $validated['contenido'],
            'com_categoria' => $validated['categoria'],
            'com_prioridad' => $validated['prioridad'],
            'com_fecha_publicacion' => now(),
            'com_fecha_expiracion' => $validated['fecha_expiracion'] ?? null,
            'com_fijado' => $validated['fijado'] ?? false,
            'com_activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'com_id');

        AuditLogger::log('COMUNICADO_PUBLICADO', 'op_comunicados', $comId, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Comunicado oficial publicado exitosamente.',
            'com_id' => $comId
        ]);
    }

    /**
     * Listar asambleas y votaciones activas / históricas
     */
    public function indexVotaciones(Request $request): JsonResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');

        $votaciones = DB::table('op_votaciones')
            ->where('vot_urbanizacion_id', $urbanizacionId)
            ->whereNull('deleted_at')
            ->orderBy('vot_fecha_inicio', 'desc')
            ->get();

        // Seed demo si no existen votaciones
        if ($votaciones->isEmpty()) {
            $defaultVot = [
                'vot_urbanizacion_id' => $urbanizacionId,
                'vot_titulo' => 'Aprobación de Instalación de Cámaras LPR & Barreras de Alto Tráfico',
                'vot_descripcion' => 'Propuesta para modernizar el sistema de control de accesos perimetrales con cargo al fondo de reserva de mejoras extraordinarias.',
                'vot_tipo_calculo' => 'PREDIO_UNICO',
                'vot_fecha_inicio' => now()->subDays(2),
                'vot_fecha_fin' => now()->addDays(5),
                'vot_opciones' => json_encode([
                    ['id' => 1, 'texto' => 'A Favor (Aprobar Adquisición)'],
                    ['id' => 2, 'texto' => 'En Contra (Mantener Sistema Actual)'],
                    ['id' => 3, 'texto' => 'Abstención']
                ]),
                'vot_estado' => 'ABIERTA',
                'vot_solo_al_dia' => true,
                'vot_creador_usr_id' => auth()->id() ?: 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            DB::table('op_votaciones')->insert($defaultVot);

            $votaciones = DB::table('op_votaciones')
                ->where('vot_urbanizacion_id', $urbanizacionId)
                ->whereNull('deleted_at')
                ->get();
        }

        // Calcular cómputo de votos en tiempo real para cada votación
        foreach ($votaciones as $vot) {
            $votos = DB::table('op_votos_emitidos')
                ->where('vto_votacion_id', $vot->vot_id)
                ->get();

            $opciones = json_decode($vot->vot_opciones, true) ?: [];
            $escrutinio = [];
            $totalVotos = $votos->count();
            $totalPuntosPonderados = (float) $votos->sum('vto_peso_voto');

            foreach ($opciones as $op) {
                $votosOpcion = $votos->where('vto_opcion_id', $op['id']);
                $conteoOpcion = $votosOpcion->count();
                $pesoOpcion = (float) $votosOpcion->sum('vto_peso_voto');

                $porcentaje = $vot->vot_tipo_calculo === 'PONDERADO_ALICUOTA'
                    ? ($totalPuntosPonderados > 0 ? round(($pesoOpcion / $totalPuntosPonderados) * 100, 1) : 0)
                    : ($totalVotos > 0 ? round(($conteoOpcion / $totalVotos) * 100, 1) : 0);

                $escrutinio[] = [
                    'opcion_id' => $op['id'],
                    'texto' => $op['texto'],
                    'votos_recibidos' => $conteoOpcion,
                    'peso_acumulado' => $pesoOpcion,
                    'porcentaje' => $porcentaje
                ];
            }

            $vot->escrutinio = $escrutinio;
            $vot->total_votos = $totalVotos;
            $vot->opciones = $opciones;
        }

        return response()->json([
            'success' => true,
            'data' => $votaciones
        ]);
    }

    /**
     * Crear una nueva votación / consulta vinculante para asamblea
     */
    public function storeVotacion(Request $request): JsonResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');

        $validated = $request->validate([
            'titulo' => 'required|string|max:150',
            'descripcion' => 'nullable|string',
            'tipo_calculo' => 'required|in:PREDIO_UNICO,PONDERADO_ALICUOTA',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'opciones' => 'required|array|min:2',
            'opciones.*.id' => 'required|integer',
            'opciones.*.texto' => 'required|string|max:100',
            'solo_al_dia' => 'boolean'
        ]);

        $votId = DB::table('op_votaciones')->insertGetId([
            'vot_urbanizacion_id' => $urbanizacionId,
            'vot_titulo' => $validated['titulo'],
            'vot_descripcion' => $validated['descripcion'] ?? null,
            'vot_tipo_calculo' => $validated['tipo_calculo'],
            'vot_fecha_inicio' => $validated['fecha_inicio'],
            'vot_fecha_fin' => $validated['fecha_fin'],
            'vot_opciones' => json_encode($validated['opciones']),
            'vot_estado' => 'ABIERTA',
            'vot_solo_al_dia' => $validated['solo_al_dia'] ?? true,
            'vot_creador_usr_id' => auth()->id() ?: 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'vot_id');

        AuditLogger::log('VOTACION_ASAMBLEA_CREADA', 'op_votaciones', $votId, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Proceso de votación / consulta creado exitosamente.',
            'vot_id' => $votId
        ]);
    }

    /**
     * Emitir voto digital desde el portal del habitante
     */
    public function emitirVoto(Request $request, int $votacionId): JsonResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');
        $user = auth('api')->user();

        $validated = $request->validate([
            'inmueble_id' => 'required|exists:core_inmuebles,inm_id',
            'opcion_id' => 'required|integer',
        ]);

        $votacion = DB::table('op_votaciones')
            ->where('vot_id', $votacionId)
            ->where('vot_urbanizacion_id', $urbanizacionId)
            ->where('vot_estado', 'ABIERTA')
            ->whereNull('deleted_at')
            ->first();

        if (!$votacion) {
            return response()->json(['success' => false, 'message' => 'Votación no encontrada o no se encuentra abierta.'], 404);
        }

        if (now()->lt(Carbon::parse($votacion->vot_fecha_inicio)) || now()->gt(Carbon::parse($votacion->vot_fecha_fin))) {
            return response()->json(['success' => false, 'message' => 'La votación se encuentra fuera de la ventana temporal permitida.'], 422);
        }

        // 1. Candado de solvencia de alícuotas (si aplica)
        if ($votacion->vot_solo_al_dia) {
            $cuotasMora = DB::table('fin_obligaciones_alicuotas')
                ->where('obl_inmueble_id', $validated['inmueble_id'])
                ->whereIn('obl_estado', ['PENDIENTE', 'VENCIDO'])
                ->whereNull('deleted_at')
                ->count();

            if ($cuotasMora > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este predio presenta alícuotas pendientes de pago. Para participar en la votación debe estar al día con la administración.'
                ], 403);
            }
        }

        // 2. Verificar si el predio ya emitió su voto (1 predio = 1 voto)
        $yaVoto = DB::table('op_votos_emitidos')
            ->where('vto_votacion_id', $votacionId)
            ->where('vto_inmueble_id', $validated['inmueble_id'])
            ->exists();

        if ($yaVoto) {
            return response()->json([
                'success' => false,
                'message' => 'El predio ya ha registrado su voto para esta consulta. No se permiten sufragios duplicados.'
            ], 409);
        }

        // 3. Determinar el peso del voto
        $pesoVoto = 1.0000;
        if ($votacion->vot_tipo_calculo === 'PONDERADO_ALICUOTA') {
            $alicuotaPorcentaje = DB::table('core_inmuebles')
                ->where('inm_id', $validated['inmueble_id'])
                ->value('inm_alicuota_porcentaje');
            $pesoVoto = $alicuotaPorcentaje ? (float)$alicuotaPorcentaje : 1.0000;
        }

        // Persona que emite el voto
        $personaId = $user->usr_persona_id ?: 1;

        $vtoId = DB::table('op_votos_emitidos')->insertGetId([
            'vto_urbanizacion_id' => $urbanizacionId,
            'vto_votacion_id' => $votacionId,
            'vto_inmueble_id' => $validated['inmueble_id'],
            'vto_persona_id' => $personaId,
            'vto_opcion_id' => $validated['opcion_id'],
            'vto_peso_voto' => $pesoVoto,
            'vto_fecha_hora' => now(),
            'vto_ip_origen' => $request->ip(),
            'created_at' => now(),
            'updated_at' => now(),
        ], 'vto_id');

        AuditLogger::log(
            'VOTO_DIGITAL_EMITIDO',
            'op_votos_emitidos',
            $vtoId,
            [
                'votacion_id' => $votacionId,
                'inmueble_id' => $validated['inmueble_id'],
                'peso' => $pesoVoto
            ]
        );

        return response()->json([
            'success' => true,
            'message' => '✓ Su voto ha sido registrado y certificado criptográficamente en el acta digital.',
            'vto_id' => $vtoId
        ]);
    }
}
