<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Operations\NovedadTurno;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NovedadController extends Controller
{
    /**
     * Listar novedades de turno y rondas
     */
    public function index(Request $request): JsonResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');

        $query = NovedadTurno::with([
            'guardia:usr_id,usr_name',
            'garita:gar_id,gar_nombre'
        ])
        ->where('nov_urbanizacion_id', $urbanizacionId)
        ->orderBy('nov_fecha_hora', 'desc');

        if ($request->filled('tipo') && $request->tipo !== 'TODOS') {
            $query->where('nov_tipo', $request->tipo);
        }

        if ($request->filled('prioridad') && $request->prioridad !== 'TODAS') {
            $query->where('nov_prioridad', $request->prioridad);
        }

        return response()->json([
            'success' => true,
            'data' => $query->get()
        ]);
    }

    /**
     * Registrar una nueva novedad en el turno
     */
    public function store(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');

        $validated = $request->validate([
            'nov_titulo' => 'required|string|max:150',
            'nov_detalle' => 'required|string',
            'nov_tipo' => 'required|in:RONDA,INCIDENCIA,RELEVO,MANTENIMIENTO,ALERTA',
            'nov_prioridad' => 'required|in:BAJA,NORMAL,ALTA,CRITICA',
            'nov_garita_id' => 'nullable|exists:op_garitas,gar_id',
        ]);

        $uuid = (string) Str::uuid();

        $novedad = NovedadTurno::create([
            'nov_id' => $uuid,
            'nov_urbanizacion_id' => $urbanizacionId,
            'nov_guardia_usr_id' => $user->usr_id,
            'nov_garita_id' => $validated['nov_garita_id'] ?? null,
            'nov_tipo' => $validated['nov_tipo'],
            'nov_titulo' => $validated['nov_titulo'],
            'nov_detalle' => $validated['nov_detalle'],
            'nov_prioridad' => $validated['nov_prioridad'],
            'nov_fecha_hora' => now(),
            'nov_resuelta' => false,
        ]);

        AuditLogger::log('NOVEDAD_REGISTERED', 'op_novedades_turno', $uuid, [
            'tipo' => $novedad->nov_tipo,
            'prioridad' => $novedad->nov_prioridad,
            'titulo' => $novedad->nov_titulo
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Novedad asentada en el libro de turno.',
            'data' => $novedad->load('guardia:usr_id,usr_name', 'garita:gar_id,gar_nombre')
        ], 201);
    }

    /**
     * Marcar incidencia como resuelta
     */
    public function resolver(Request $request, $id): JsonResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');

        $novedad = NovedadTurno::where('nov_urbanizacion_id', $urbanizacionId)->findOrFail($id);
        $novedad->update(['nov_resuelta' => true]);

        AuditLogger::log('NOVEDAD_RESOLVED', 'op_novedades_turno', $id, [
            'titulo' => $novedad->nov_titulo
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Novedad marcada como resuelta.',
            'data' => $novedad
        ]);
    }
}
