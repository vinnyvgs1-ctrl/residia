<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Operations\ControlAcceso;
use App\Models\Core\Inmueble;
use App\Models\Core\Persona;
use App\Models\Operations\Paqueteria;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PortalResidenteController extends Controller
{
    /**
     * Obtener el resumen del hogar del habitante autenticado:
     * Inmueble, cohabitantes, vehículos familiares registrados, pases QR activos y últimas visitas.
     */
    public function miHogar(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');

        // Buscar el inmueble asociado al usuario residente a través de core_inmueble_residentes
        $residenteRel = DB::table('core_inmueble_residentes')
            ->join('core_inmuebles', 'core_inmuebles.inm_id', '=', 'core_inmueble_residentes.res_inmueble_id')
            ->where('core_inmueble_residentes.res_persona_id', $user->usr_persona_id)
            ->where('core_inmuebles.inm_urbanizacion_id', $urbanizacionId)
            ->whereNull('core_inmuebles.deleted_at')
            ->select('core_inmuebles.*', 'core_inmueble_residentes.res_tipo_relacion')
            ->first();

        // Si no tiene relación específica, tomar el primer inmueble de la urbanización como fallback seguro
        if (!$residenteRel) {
            $inmueble = DB::table('core_inmuebles')
                ->where('inm_urbanizacion_id', $urbanizacionId)
                ->whereNull('deleted_at')
                ->first();
        } else {
            $inmueble = $residenteRel;
        }

        $inmuebleId = $inmueble ? $inmueble->inm_id : null;

        // 1. Habitantes de la casa
        $habitantes = $inmuebleId ? DB::table('core_inmueble_residentes')
            ->join('core_personas', 'core_personas.per_id', '=', 'core_inmueble_residentes.res_persona_id')
            ->where('core_inmueble_residentes.res_inmueble_id', $inmuebleId)
            ->whereNull('core_personas.deleted_at')
            ->select('core_personas.*', 'core_inmueble_residentes.res_tipo_relacion', 'core_inmueble_residentes.res_es_principal')
            ->get() : [];

        // 2. Vehículos y Tags RFID del inmueble
        $vehiculos = $inmuebleId ? DB::table('core_inmueble_vehiculos')
            ->where('veh_inmueble_id', $inmuebleId)
            ->where('veh_activo', true)
            ->whereNull('deleted_at')
            ->get() : [];

        // 3. Pases QR generados por este habitante
        $pasesQr = DB::table('op_invitaciones_qr')
            ->where('inv_urbanizacion_id', $urbanizacionId)
            ->where('inv_usuario_creador_id', $user->usr_id)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->get();

        // 4. Paquetería recibida en garita para este inmueble
        $paqueteria = $inmuebleId ? \App\Models\Operations\Paqueteria::where('paq_inmueble_id', $inmuebleId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get() : [];

        // 5. Historial reciente de visitas ingresadas al inmueble
        $visitasRecientes = $inmuebleId ? ControlAcceso::with('persona')
            ->where('acc_urbanizacion_id', $urbanizacionId)
            ->where('acc_inmueble_id', $inmuebleId)
            ->orderBy('acc_fecha_ingreso', 'desc')
            ->limit(10)
            ->get() : [];

        // 6. Alícuotas & Estado de Cuenta del Residente
        $obligaciones = $inmuebleId ? DB::table('fin_obligaciones_alicuotas')
            ->where('obl_inmueble_id', $inmuebleId)
            ->whereNull('deleted_at')
            ->orderBy('obl_periodo', 'desc')
            ->get() : collect();

        $cuotasPendientes = $obligaciones->whereIn('obl_estado', ['PENDIENTE', 'VENCIDO'])->count();
        $totalDeuda = (float) $obligaciones->whereIn('obl_estado', ['PENDIENTE', 'VENCIDO'])->sum('obl_monto');
        $totalPagado = (float) $obligaciones->where('obl_estado', 'PAGADO')->sum('obl_monto');

        return response()->json([
            'success' => true,
            'inmueble' => $inmueble,
            'habitantes' => $habitantes,
            'vehiculos' => $vehiculos,
            'pases_qr' => $pasesQr,
            'paqueteria' => $paqueteria,
            'visitas_recientes' => $visitasRecientes,
            'estado_cuenta' => [
                'cuotas_pendientes' => $cuotasPendientes,
                'deuda_total' => $totalDeuda,
                'total_pagado' => $totalPagado,
                'al_dia' => $cuotasPendientes === 0,
                'obligaciones' => $obligaciones
            ]
        ]);
    }

    /**
     * Crear un nuevo Pase QR de Visita desde el móvil del habitante
     */
    public function crearPaseVisita(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');

        $validated = $request->validate([
            'visitante_nombre' => 'required|string|max:150',
            'visitante_identificacion' => 'nullable|string|max:30',
            'visitante_placa' => 'nullable|string|max:15',
            'tipo_ingreso' => 'required|in:VEHICULAR,PEATONAL',
            'inmueble_id' => 'required|exists:core_inmuebles,inm_id',
            'valido_desde' => 'required',
            'valido_hasta' => 'required',
            'observaciones' => 'nullable|string',
        ]);

        $token = 'QR-' . strtoupper(Str::random(6)) . '-' . rand(100, 999);
        $uuid = (string) Str::uuid();

        $validoDesde = Carbon::parse($validated['valido_desde'], 'America/Guayaquil');
        $validoHasta = Carbon::parse($validated['valido_hasta'], 'America/Guayaquil');

        DB::table('op_invitaciones_qr')->insert([
            'inv_id' => $uuid,
            'inv_urbanizacion_id' => $urbanizacionId,
            'inv_inmueble_id' => $validated['inmueble_id'],
            'inv_usuario_creador_id' => $user->usr_id,
            'inv_codigo_token' => $token,
            'inv_visitante_nombre' => $validated['visitante_nombre'],
            'inv_visitante_identificacion' => $validated['visitante_identificacion'] ?? null,
            'inv_visitante_placa' => !empty($validated['visitante_placa']) ? strtoupper($validated['visitante_placa']) : null,
            'inv_tipo_ingreso' => $validated['tipo_ingreso'],
            'inv_valido_desde' => $validoDesde,
            'inv_valido_hasta' => $validoHasta,
            'inv_usos_permitidos' => 1,
            'inv_usos_realizados' => 0,
            'inv_estado' => 'ACTIVO',
            'inv_observaciones' => $validated['observaciones'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AuditLogger::log('RESIDENT_QR_PASS_CREATED', 'op_invitaciones_qr', $uuid, [
            'token' => $token,
            'visitante' => $validated['visitante_nombre'],
            'inmueble_id' => $validated['inmueble_id']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pase QR generado exitosamente. Listo para compartir por WhatsApp.',
            'invitacion' => [
                'id' => $uuid,
                'token' => $token,
                'visitante' => $validated['visitante_nombre'],
                'placa' => $validated['visitante_placa'] ?? null,
                'valido_hasta' => $validoHasta->toDateTimeString(),
            ]
        ]);
    }
}
