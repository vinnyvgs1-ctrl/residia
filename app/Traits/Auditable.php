<?php

namespace App\Traits;

use App\Jobs\ProcessAuditLogJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            static::dispatchAuditLog('INSERT', $model, null, $model->getAttributes());
        });

        static::updated(function ($model) {
            $oldValues = array_intersect_key($model->getOriginal(), $model->getDirty());
            $newValues = $model->getDirty();

            // Omitir si solo cambió updated_at
            if (count($newValues) === 1 && isset($newValues['updated_at'])) {
                return;
            }

            $diff = [];
            foreach ($newValues as $key => $val) {
                if ($key === 'updated_at') continue;
                $diff[$key] = [
                    'anterior' => $oldValues[$key] ?? null,
                    'nuevo' => $val,
                ];
            }

            static::dispatchAuditLog('UPDATE', $model, $oldValues, $newValues, $diff);
        });

        static::deleted(function ($model) {
            static::dispatchAuditLog('DELETE', $model, $model->getAttributes(), null);
        });
    }

    protected static function dispatchAuditLog(string $evento, $model, ?array $oldValues, ?array $newValues, ?array $diff = null): void
    {
        try {
            $user = Auth::user();
            $urbanizacionId = null;

            if (isset($model->acc_urbanizacion_id)) {
                $urbanizacionId = $model->acc_urbanizacion_id;
            } elseif (isset($model->inm_urbanizacion_id)) {
                $urbanizacionId = $model->inm_urbanizacion_id;
            } elseif (isset($model->urb_id)) {
                $urbanizacionId = $model->urb_id;
            } elseif (session()->has('active_urbanizacion_id')) {
                $urbanizacionId = session()->get('active_urbanizacion_id');
            } elseif (Request::header('X-Urbanizacion-Id')) {
                $urbanizacionId = Request::header('X-Urbanizacion-Id');
            }

            // Ocultar contraseñas o datos altamente sensibles
            $ocultar = ['usr_password', 'password', 'remember_token'];
            if ($oldValues) {
                foreach ($ocultar as $col) unset($oldValues[$col]);
            }
            if ($newValues) {
                foreach ($ocultar as $col) unset($newValues[$col]);
            }
            if ($diff) {
                foreach ($ocultar as $col) unset($diff[$col]);
            }

            $auditData = [
                'aud_urbanizacion_id' => $urbanizacionId,
                'aud_user_id' => $user ? ($user->usr_id ?? $user->id) : null,
                'aud_user_nombre' => $user ? ($user->usr_name ?? $user->name ?? $user->usr_email) : 'SISTEMA',
                'aud_evento' => $evento,
                'aud_tabla' => $model->getTable(),
                'aud_registro_id' => (string) $model->getKey(),
                'aud_old_values' => $oldValues,
                'aud_new_values' => $newValues,
                'aud_diff' => $diff,
                'aud_ip_address' => Request::ip(),
                'aud_user_agent' => Request::userAgent(),
            ];

            // Si las colas están activas, despacha el Job asíncrono
            ProcessAuditLogJob::dispatch($auditData);
        } catch (\Throwable $e) {
            // Silencioso para no romper la transacción del modelo principal
        }
    }
}
