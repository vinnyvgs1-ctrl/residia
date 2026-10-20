<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UrbanizacionTenantScope
{
    /**
     * Resuelve y valida el contexto multi-urbanización para la petición actual
     */
    public function handle(Request $request, Closure $next): Response
    {
        $urbanizacionId = $request->header('X-Urbanizacion-Id') 
            ?: (session('active_urbanizacion_id') ?: $request->input('urbanizacion_id'));

        if ($urbanizacionId) {
            session(['active_urbanizacion_id' => $urbanizacionId]);
            $request->attributes->set('active_urbanizacion_id', $urbanizacionId);
        }

        return $next($request);
    }
}
