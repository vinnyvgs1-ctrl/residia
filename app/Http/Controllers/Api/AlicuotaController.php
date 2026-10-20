<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Core\Inmueble;
use App\Models\Core\Urbanizacion;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AlicuotaController extends Controller
{
    /**
     * Resumen financiero, cartera vencida y metricas de morosidad
     */
    public function resumen(Request $request): JsonResponse
    {
        $urbanizacionId = $request->header('X-Urbanizacion-Id') 
            ?: (session('active_urbanizacion_id') ?: $request->input('urbanizacion_id'));

        if (!$urbanizacionId) {
            return response()->json(['success' => false, 'message' => 'Urbanizacion no especificada'], 400);
        }

        $inmuebles = Inmueble::where('inm_urbanizacion_id', $urbanizacionId)
            ->whereNull('deleted_at')
            ->pluck('inm_id');

        $totalInmuebles = $inmuebles->count();

        $obligaciones = DB::table('fin_obligaciones_alicuotas')
            ->whereIn('obl_inmueble_id', $inmuebles)
            ->whereNull('deleted_at');

        $totalRecaudado = (float) (clone $obligaciones)->where('obl_estado', 'PAGADO')->sum('obl_monto');
        $totalPendiente = (float) (clone $obligaciones)->whereIn('obl_estado', ['PENDIENTE', 'VENCIDO'])->sum('obl_monto');
        
        $inmueblesMorososIds = (clone $obligaciones)
            ->whereIn('obl_estado', ['PENDIENTE', 'VENCIDO'])
            ->distinct()
            ->pluck('obl_inmueble_id');

        $inmueblesAlDiaCount = $totalInmuebles - $inmueblesMorososIds->count();
        $inmueblesMorososCount = $inmueblesMorososIds->count();

        $cartera = DB::table('core_inmuebles')
            ->where('core_inmuebles.inm_urbanizacion_id', $urbanizacionId)
            ->whereNull('core_inmuebles.deleted_at')
            ->leftJoin('fin_obligaciones_alicuotas', function ($join) {
                $join->on('core_inmuebles.inm_id', '=', 'fin_obligaciones_alicuotas.obl_inmueble_id')
                     ->whereNull('fin_obligaciones_alicuotas.deleted_at');
            })
            ->select(
                'core_inmuebles.inm_id',
                'core_inmuebles.inm_manzana_bloque',
                'core_inmuebles.inm_numero_villa',
                'core_inmuebles.inm_tipo',
                'core_inmuebles.inm_estado',
                'core_inmuebles.inm_alicuota_porcentaje',
                DB::raw("COUNT(CASE WHEN fin_obligaciones_alicuotas.obl_estado IN ('PENDIENTE', 'VENCIDO') THEN 1 END) as cuotas_pendientes"),
                DB::raw("COALESCE(SUM(CASE WHEN fin_obligaciones_alicuotas.obl_estado IN ('PENDIENTE', 'VENCIDO') THEN fin_obligaciones_alicuotas.obl_monto ELSE 0 END), 0) as deuda_total"),
                DB::raw("MAX(CASE WHEN fin_obligaciones_alicuotas.obl_estado IN ('PENDIENTE', 'VENCIDO') THEN fin_obligaciones_alicuotas.obl_periodo END) as ultimo_periodo_deuda")
            )
            ->groupBy(
                'core_inmuebles.inm_id',
                'core_inmuebles.inm_manzana_bloque',
                'core_inmuebles.inm_numero_villa',
                'core_inmuebles.inm_tipo',
                'core_inmuebles.inm_estado',
                'core_inmuebles.inm_alicuota_porcentaje'
            )
            ->orderByRaw("deuda_total DESC")
            ->orderBy('core_inmuebles.inm_manzana_bloque')
            ->orderBy('core_inmuebles.inm_numero_villa')
            ->get();

        foreach ($cartera as $item) {
            $titular = DB::table('core_inmueble_residentes')
                ->join('core_personas', 'core_inmueble_residentes.res_persona_id', '=', 'core_personas.per_id')
                ->where('core_inmueble_residentes.res_inmueble_id', $item->inm_id)
                ->where('core_inmueble_residentes.res_activo', true)
                ->orderByDesc('core_inmueble_residentes.res_es_principal')
                ->select(
                    'core_personas.per_nombres',
                    'core_personas.per_apellidos',
                    'core_personas.per_identificacion',
                    'core_personas.per_telefono',
                    'core_personas.per_email',
                    'core_inmueble_residentes.res_tipo_relacion'
                )
                ->first();

            $item->titular = $titular;
            $item->es_moroso = (float)$item->deuda_total > 0;
        }

        return response()->json([
            'success' => true,
            'metricas' => [
                'total_inmuebles' => $totalInmuebles,
                'inmuebles_al_dia' => $inmueblesAlDiaCount,
                'inmuebles_morosos' => $inmueblesMorososCount,
                'total_recaudado' => $totalRecaudado,
                'total_pendiente' => $totalPendiente,
                'porcentaje_al_dia' => $totalInmuebles > 0 ? round(($inmueblesAlDiaCount / $totalInmuebles) * 100, 1) : 100
            ],
            'cartera' => $cartera,
        ]);
    }

    /**
     * Emision Masiva de Alicuotas para todos los inmuebles de la Urbanizacion
     */
    public function emisionMasiva(Request $request): JsonResponse
    {
        $urbanizacionId = $request->header('X-Urbanizacion-Id') 
            ?: (session('active_urbanizacion_id') ?: $request->input('urbanizacion_id'));

        $validated = $request->validate([
            'periodo' => 'required|string|max:7',
            'concepto' => 'required|string|max:120',
            'monto_base' => 'required|numeric|min:0.01',
            'fecha_vencimiento' => 'required|date',
            'aplicar_ponderacion' => 'boolean',
            'solo_habitados' => 'boolean',
        ]);

        $query = Inmueble::where('inm_urbanizacion_id', $urbanizacionId)
            ->whereNull('deleted_at');

        if (!empty($validated['solo_habitados'])) {
            $query->where('inm_estado', 'HABITADO');
        }

        $inmuebles = $query->get();

        if ($inmuebles->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No hay inmuebles disponibles para emitir obligaciones.',
            ], 422);
        }

        $emitidas = 0;
        $yaExistentes = 0;
        $totalMonto = 0;

        DB::beginTransaction();
        try {
            foreach ($inmuebles as $inm) {
                $existe = DB::table('fin_obligaciones_alicuotas')
                    ->where('obl_inmueble_id', $inm->inm_id)
                    ->where('obl_periodo', $validated['periodo'])
                    ->where('obl_concepto', $validated['concepto'])
                    ->whereNull('deleted_at')
                    ->exists();

                if ($existe) {
                    $yaExistentes++;
                    continue;
                }

                $montoFinal = (float) $validated['monto_base'];
                if (!empty($validated['aplicar_ponderacion']) && $inm->inm_alicuota_porcentaje > 0) {
                    $montoFinal = round($montoFinal * ($inm->inm_alicuota_porcentaje / 100), 2);
                }

                DB::table('fin_obligaciones_alicuotas')->insert([
                    'obl_inmueble_id' => $inm->inm_id,
                    'obl_periodo' => $validated['periodo'],
                    'obl_concepto' => $validated['concepto'],
                    'obl_monto' => $montoFinal,
                    'obl_fecha_emision' => now()->toDateString(),
                    'obl_fecha_vencimiento' => $validated['fecha_vencimiento'],
                    'obl_estado' => 'PENDIENTE',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $emitidas++;
                $totalMonto += $montoFinal;
            }

            DB::commit();

            AuditLogger::log('MASSIVE_ALICUOTA_ISSUED', 'fin_obligaciones_alicuotas', (string)$urbanizacionId, [
                'periodo' => $validated['periodo'],
                'concepto' => $validated['concepto'],
                'emitidas' => $emitidas,
                'total_monto' => $totalMonto,
            ], null, $urbanizacionId);

            return response()->json([
                'success' => true,
                'message' => "Emision masiva completada: {$emitidas} obligaciones generadas por un total de $" . number_format($totalMonto, 2) . ($yaExistentes > 0 ? " ({$yaExistentes} ya existian)." : "."),
                'data' => [
                    'emitidas' => $emitidas,
                    'ya_existentes' => $yaExistentes,
                    'total_monto' => $totalMonto,
                ]
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar emision masiva: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Datos para el Recibo Imprimible de Pago
     */
    public function recibo(int $id): JsonResponse
    {
        $obligacion = DB::table('fin_obligaciones_alicuotas')
            ->join('core_inmuebles', 'fin_obligaciones_alicuotas.obl_inmueble_id', '=', 'core_inmuebles.inm_id')
            ->join('core_urbanizaciones', 'core_inmuebles.inm_urbanizacion_id', '=', 'core_urbanizaciones.urb_id')
            ->where('fin_obligaciones_alicuotas.obl_id', $id)
            ->select(
                'fin_obligaciones_alicuotas.*',
                'core_inmuebles.inm_id',
                'core_inmuebles.inm_manzana_bloque',
                'core_inmuebles.inm_numero_villa',
                'core_inmuebles.inm_tipo',
                'core_urbanizaciones.urb_id',
                'core_urbanizaciones.urb_nombre',
                'core_urbanizaciones.urb_codigo',
                'core_urbanizaciones.urb_ruc',
                'core_urbanizaciones.urb_direccion',
                'core_urbanizaciones.urb_ciudad',
                'core_urbanizaciones.urb_telefono'
            )
            ->first();

        if (!$obligacion) {
            return response()->json(['success' => false, 'message' => 'Obligacion no encontrada'], 404);
        }

        $titular = DB::table('core_inmueble_residentes')
            ->join('core_personas', 'core_inmueble_residentes.res_persona_id', '=', 'core_personas.per_id')
            ->where('core_inmueble_residentes.res_inmueble_id', $obligacion->inm_id)
            ->where('core_inmueble_residentes.res_activo', true)
            ->orderByDesc('core_inmueble_residentes.res_es_principal')
            ->select(
                'core_personas.per_nombres',
                'core_personas.per_apellidos',
                'core_personas.per_identificacion',
                'core_personas.per_telefono',
                'core_personas.per_email'
            )
            ->first();

        return response()->json([
            'success' => true,
            'recibo' => [
                'numero_comprobante' => 'REC-' . str_pad((string)$obligacion->obl_id, 6, '0', STR_PAD_LEFT),
                'obligacion' => $obligacion,
                'titular' => $titular,
                'urbanizacion' => [
                    'nombre' => $obligacion->urb_nombre,
                    'codigo' => $obligacion->urb_codigo,
                    'ruc' => $obligacion->urb_ruc,
                    'direccion' => $obligacion->urb_direccion,
                    'ciudad' => $obligacion->urb_ciudad,
                    'telefono' => $obligacion->urb_telefono,
                ]
            ]
        ]);
    }
}
