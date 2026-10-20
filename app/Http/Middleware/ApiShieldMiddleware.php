<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class ApiShieldMiddleware
{
    /**
     * Valida API Request Signing (HMAC-SHA256), timestamps de 5 min y Nonces en Redis
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Si la petición ya viene con sesión web autenticada, permitir paso
        if (auth()->check() || auth('api')->check()) {
            return $next($request);
        }

        $appKey = $request->header('X-App-Key');
        $timestamp = $request->header('X-App-Timestamp');
        $signature = $request->header('X-App-Signature');
        $nonce = $request->header('X-Nonce');

        // 1. Cabeceras obligatorias
        if (!$appKey || !$timestamp || !$signature || !$nonce) {
            return response()->json([
                'success' => false,
                'error' => 'SHIELD_HEADERS_MISSING',
                'message' => 'Acceso denegado: Faltan cabeceras de seguridad criptográfica obligatorias.'
            ], 403);
        }

        // 2. Validación de Ventana Temporal (máximo 300 segundos / 5 minutos)
        $currentTime = time();
        if (abs($currentTime - (int) $timestamp) > 300) {
            return response()->json([
                'success' => false,
                'error' => 'SHIELD_TIMESTAMP_EXPIRED',
                'message' => 'Acceso denegado: Marca de tiempo expirada o desincronizada.'
            ], 403);
        }

        // 3. Mitigación de Replay Attacks con Nonce en Redis
        try {
            $nonceKey = "shield:nonce:{$nonce}";
            $isUnique = Redis::set($nonceKey, '1', 'EX', 300, 'NX');
            if (!$isUnique) {
                return response()->json([
                    'success' => false,
                    'error' => 'REPLAY_ATTACK_DETECTED',
                    'message' => 'Acceso denegado: Petición duplicada detectada (Nonce ya utilizado).'
                ], 403);
            }
        } catch (\Throwable $e) {
            // Si Redis no está disponible temporalmente, continuar con advertencia en log
            \Illuminate\Support\Facades\Log::warning('Redis no disponible para validar Nonce: ' . $e->getMessage());
        }

        // 4. Verificación de Firma HMAC-SHA256
        $secret = config('app.api_shield_secret', env('API_SHIELD_SECRET', 'robledal_residia_shield_secret_key_2026'));
        $method = strtoupper($request->method());
        $path = $request->getPathInfo();
        $payload = $request->getContent();
        $payloadHash = hash('sha256', $payload ?: '');

        $signingString = "{$method}|{$path}|{$timestamp}|{$nonce}|{$payloadHash}";
        $expectedSignature = hash_hmac('sha256', $signingString, $secret);

        if (!hash_equals($expectedSignature, $signature)) {
            return response()->json([
                'success' => false,
                'error' => 'SHIELD_SIGNATURE_INVALID',
                'message' => 'Acceso denegado: Firma criptográfica no coincide.'
            ], 403);
        }

        return $next($request);
    }
}
