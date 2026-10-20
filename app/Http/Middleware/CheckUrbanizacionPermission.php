<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUrbanizacionPermission
{
    /**
     * Valida si el usuario posee el permiso granular (ej. 'accesos.write' o 'reportes.print')
     * en la urbanización activa.
     */
    public function handle(Request $request, Closure $next, string $permissionSlug): Response
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'UNAUTHORIZED',
                'message' => 'No autenticado.'
            ], 401);
        }

        // El SuperAdmin tiene acceso global a todos los módulos
        if ($user->usr_es_superadmin) {
            return $next($request);
        }

        // Resolver la urbanización activa
        $urbanizacionId = $request->header('X-Urbanizacion-Id') 
            ?: (session('active_urbanizacion_id') ?: $request->input('urbanizacion_id'));

        if (!$urbanizacionId) {
            return response()->json([
                'success' => false,
                'error' => 'NO_URBANIZACION_CONTEXT',
                'message' => 'No se ha especificado el contexto de urbanización activa (X-Urbanizacion-Id).'
            ], 400);
        }

        if (!$user->hasPermissionInUrbanizacion($permissionSlug, (string) $urbanizacionId)) {
            return response()->json([
                'success' => false,
                'error' => 'FORBIDDEN',
                'message' => "No tienes el permiso necesario ({$permissionSlug}) en esta urbanización."
            ], 403);
        }

        return $next($request);
    }
}
