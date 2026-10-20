<?php

namespace App\Services;

use App\Jobs\ProcessAuditLogJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * Registra un evento de auditoría explícito (login, logout, cambio de turno, etc.)
     */
    public static function log(string $evento, string $tabla, string|int $registroId, ?array $newValues = null, ?array $oldValues = null, ?string $urbanizacionId = null): void
    {
        $user = Auth::user();
        $urbId = $urbanizacionId 
            ?: (session('active_urbanizacion_id') ?: Request::header('X-Urbanizacion-Id'));

        $data = [
            'aud_urbanizacion_id' => $urbId,
            'aud_user_id' => $user ? ($user->usr_id ?? $user->id) : null,
            'aud_user_nombre' => $user ? ($user->usr_name ?? $user->name ?? $user->usr_email) : 'SISTEMA',
            'aud_evento' => $evento,
            'aud_tabla' => $tabla,
            'aud_registro_id' => (string) $registroId,
            'aud_old_values' => $oldValues,
            'aud_new_values' => $newValues,
            'aud_diff' => null,
            'aud_ip_address' => Request::ip(),
            'aud_user_agent' => Request::userAgent(),
        ];

        ProcessAuditLogJob::dispatch($data);
    }

    /**
     * Registra la auditoría obligatoria de exportación o impresión de un reporte
     */
    public static function logReportExport(string $modulo, string $formato, string $nombreArchivo, ?array $filtros = null, int $totalFilas = 0, ?string $urbanizacionId = null): void
    {
        try {
            $user = Auth::user();
            $urbId = $urbanizacionId 
                ?: (session('active_urbanizacion_id') ?: Request::header('X-Urbanizacion-Id'));

            DB::table('aud_reportes_descargas')->insert([
                'rep_urbanizacion_id' => $urbId,
                'rep_user_id' => $user ? ($user->usr_id ?? $user->id) : null,
                'rep_modulo' => $modulo,
                'rep_formato' => strtoupper($formato),
                'rep_nombre_archivo' => $nombreArchivo,
                'rep_filtros_aplicados' => $filtros ? json_encode($filtros, JSON_UNESCAPED_UNICODE) : null,
                'rep_total_filas' => $totalFilas,
                'rep_ip_address' => Request::ip(),
                'rep_created_at' => now(),
            ]);

            // Duplicar también en la trazabilidad general como evento EXPORT
            self::log("EXPORT_{$formato}", "rep_{$modulo}", 0, [
                'archivo' => $nombreArchivo,
                'total_filas' => $totalFilas,
                'filtros' => $filtros,
            ], null, $urbId);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error registrando auditoria de reporte: ' . $e->getMessage());
        }
    }
}
