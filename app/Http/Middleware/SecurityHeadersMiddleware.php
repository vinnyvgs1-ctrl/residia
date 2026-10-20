<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Inyecta cabeceras HTTP de seguridad de grado bancario (OWASP Hardening)
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // 1. Anti-Clickjacking: Prohibir renderizado en frames externos
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // 2. Anti-MIME Sniffing: Forzar al navegador a respetar los Content-Types declarados
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // 3. Protección XSS de Navegadores heredados
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // 4. Política de Referrer: No fugar parámetros ni tokens en URL
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // 5. Deshabilitar permisos invasivos de hardware para el portal web
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        // 6. HSTS (HTTP Strict Transport Security)
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }
}
