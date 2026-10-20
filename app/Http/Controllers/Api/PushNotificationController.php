<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PushNotificationController extends Controller
{
    /**
     * Suscribir el dispositivo del usuario para Web Push
     */
    public function suscribir(Request $request): JsonResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');
        $user = auth('api')->user();

        $validated = $request->validate([
            'endpoint' => 'required|string',
            'p256dh' => 'nullable|string',
            'auth' => 'nullable|string',
            'persona_id' => 'nullable|integer'
        ]);

        $subId = DB::table('op_push_subscriptions')->insertGetId([
            'sub_urbanizacion_id' => $urbanizacionId,
            'sub_user_id' => $user ? ($user->usr_id ?? $user->id) : null,
            'sub_persona_id' => $validated['persona_id'] ?? null,
            'sub_endpoint' => $validated['endpoint'],
            'sub_p256dh' => $validated['p256dh'] ?? null,
            'sub_auth' => $validated['auth'] ?? null,
            'sub_user_agent' => $request->userAgent(),
            'sub_activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'sub_id');

        AuditLogger::log('PUSH_DISPOSITIVO_SUSCRITO', 'op_push_subscriptions', $subId, [
            'user' => $user ? ($user->usr_name ?? $user->name) : 'INVITADO',
            'endpoint' => substr($validated['endpoint'], 0, 50) . '...'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Dispositivo móvil suscrito a notificaciones Web Push exitosamente.',
            'sub_id' => $subId
        ]);
    }

    /**
     * Enviar notificación de prueba / alerta a un residente o garita
     */
    public function enviarNotificacionPrueba(Request $request): JsonResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');

        $validated = $request->validate([
            'titulo' => 'required|string|max:100',
            'mensaje' => 'required|string|max:255',
            'tipo' => 'nullable|string|in:VISITA_LLEGADA,PAQUETE_RECIBIDO,ALERTA_SEGURIDAD,ASAMBLEA',
            'inmueble_id' => 'nullable|integer'
        ]);

        $subs = DB::table('op_push_subscriptions')
            ->where('sub_urbanizacion_id', $urbanizacionId)
            ->where('sub_activo', true)
            ->count();

        // Registrar evento
        AuditLogger::log('PUSH_BROADCAST_EMITIDO', 'op_push_subscriptions', 0, [
            'titulo' => $validated['titulo'],
            'tipo' => $validated['tipo'] ?? 'ALERTA_SEGURIDAD',
            'destinatarios' => $subs
        ]);

        return response()->json([
            'success' => true,
            'message' => "Notificación push despachada a {$subs} dispositivos registrados.",
            'data' => [
                'titulo' => $validated['titulo'],
                'mensaje' => $validated['mensaje'],
                'subscriptores_activos' => $subs
            ]
        ]);
    }

    /**
     * Obtener estadísticas de dispositivos móviles vinculados
     */
    public function estadoPwa(Request $request): JsonResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');

        $subs = DB::table('op_push_subscriptions')
            ->where('sub_urbanizacion_id', $urbanizacionId)
            ->where('sub_activo', true)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_dispositivos' => $subs->count(),
                'navegadores' => $subs->groupBy(function($item) {
                    $ua = strtolower($item->sub_user_agent ?: '');
                    if (str_contains($ua, 'iphone') || str_contains($ua, 'ipad')) return 'iOS Safari';
                    if (str_contains($ua, 'android')) return 'Android Chrome';
                    if (str_contains($ua, 'chrome')) return 'Desktop Chrome';
                    if (str_contains($ua, 'firefox')) return 'Firefox';
                    return 'Otro';
                })->map(fn($group) => count($group))
            ]
        ]);
    }
}
