<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NotificacionRealtimeController extends Controller
{
    /**
     * Canal Server-Sent Events (SSE) para garitas y puestos de guardia
     */
    public function stream(Request $request): StreamedResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') 
            ?: ($request->header('X-Urbanizacion-Id') ?: $request->input('urbanizacion_id'));

        $response = new StreamedResponse(function () use ($urbanizacionId) {
            if (ob_get_level()) {
                ob_end_clean();
            }

            echo ": ping - conexion establecida Residia Realtime SSE\n\n";
            flush();

            $lastCheckTime = now()->subSeconds(5);
            $cycles = 0;

            // Mantener stream activo 40 segundos
            while ($cycles < 20) {
                if (connection_aborted()) {
                    break;
                }

                // 1. Nuevos Pases QR generados en op_invitaciones_qr
                $queryPases = DB::table('op_invitaciones_qr')
                    ->join('core_inmuebles', 'op_invitaciones_qr.inv_inmueble_id', '=', 'core_inmuebles.inm_id')
                    ->where('op_invitaciones_qr.created_at', '>', $lastCheckTime)
                    ->whereNull('op_invitaciones_qr.deleted_at');

                if ($urbanizacionId) {
                    $queryPases->where('op_invitaciones_qr.inv_urbanizacion_id', $urbanizacionId);
                }

                $nuevosPases = $queryPases->select(
                    'op_invitaciones_qr.inv_id',
                    'op_invitaciones_qr.inv_codigo_token',
                    'op_invitaciones_qr.inv_visitante_nombre',
                    'op_invitaciones_qr.inv_visitante_placa',
                    'op_invitaciones_qr.inv_valido_desde',
                    'op_invitaciones_qr.inv_valido_hasta',
                    'op_invitaciones_qr.created_at',
                    'core_inmuebles.inm_manzana_bloque',
                    'core_inmuebles.inm_numero_villa'
                )->get();

                if ($nuevosPases->isNotEmpty()) {
                    foreach ($nuevosPases as $pase) {
                        $payload = [
                            'tipo' => 'NUEVO_PASE_QR',
                            'titulo' => '🎫 Nuevo Pase QR de Residente',
                            'mensaje' => "El habitante de {$pase->inm_manzana_bloque} - {$pase->inm_numero_villa} autorizó el ingreso de {$pase->inv_visitante_nombre}" . ($pase->inv_visitante_placa ? " (Vehículo {$pase->inv_visitante_placa})" : ""),
                            'datos' => $pase,
                            'timestamp' => now()->toIso8601String()
                        ];
                        echo "data: " . json_encode($payload) . "\n\n";
                        flush();
                    }
                }

                // 2. Visitas que superaron el tiempo de permanencia
                $tiempoMaximo = 240;
                if ($urbanizacionId) {
                    $reglas = DB::table('core_urbanizaciones')
                        ->where('urb_id', $urbanizacionId)
                        ->value('urb_configuracion_reglas');
                    if ($reglas) {
                        $config = json_decode($reglas, true);
                        if (!empty($config['tiempo_maximo_visita_minutos'])) {
                            $tiempoMaximo = (int)$config['tiempo_maximo_visita_minutos'];
                        }
                    }
                }

                $limiteExceso = now()->subMinutes($tiempoMaximo);
                $queryExcedidos = DB::table('acc_accesos_registro')
                    ->whereNull('acc_fecha_hora_salida')
                    ->where('acc_fecha_hora_ingreso', '<', $limiteExceso)
                    ->where('acc_rol_visita', '!=', 'RESIDENTE');

                if ($urbanizacionId) {
                    $queryExcedidos->where('acc_urbanizacion_id', $urbanizacionId);
                }

                $totalExcedidos = $queryExcedidos->count();
                if ($totalExcedidos > 0 && ($cycles % 5 === 0)) {
                    $payloadAlerta = [
                        'tipo' => 'EXCESO_PERMANENCIA',
                        'titulo' => '⚠️ Alerta de Permanencia Vencida',
                        'mensaje' => "Existen {$totalExcedidos} visita(s) dentro con límite de tiempo excedido (+{$tiempoMaximo} min).",
                        'total_excedidos' => $totalExcedidos,
                        'timestamp' => now()->toIso8601String()
                    ];
                    echo "data: " . json_encode($payloadAlerta) . "\n\n";
                    flush();
                }

                $lastCheckTime = now();
                $cycles++;
                sleep(2);
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }

    /**
     * Fallback REST para obtener pases recientes generados
     */
    public function recientes(Request $request): JsonResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') 
            ?: ($request->header('X-Urbanizacion-Id') ?: $request->input('urbanizacion_id'));

        $pasesRecientes = DB::table('op_invitaciones_qr')
            ->join('core_inmuebles', 'op_invitaciones_qr.inv_inmueble_id', '=', 'core_inmuebles.inm_id')
            ->when($urbanizacionId, function ($q) use ($urbanizacionId) {
                $q->where('op_invitaciones_qr.inv_urbanizacion_id', $urbanizacionId);
            })
            ->whereNull('op_invitaciones_qr.deleted_at')
            ->select(
                'op_invitaciones_qr.*',
                'core_inmuebles.inm_manzana_bloque',
                'core_inmuebles.inm_numero_villa'
            )
            ->orderByDesc('op_invitaciones_qr.created_at')
            ->take(10)
            ->get();

        return response()->json([
            'success' => true,
            'pases' => $pasesRecientes,
            'timestamp' => now()->toIso8601String()
        ]);
    }
}
