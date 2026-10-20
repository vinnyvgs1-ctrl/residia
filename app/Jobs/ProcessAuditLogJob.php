<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessAuditLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $auditData;

    /**
     * Create a new job instance.
     */
    public function __construct(array $auditData)
    {
        $this->auditData = $auditData;
        $this->onQueue('auditoria');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            DB::table('aud_trazabilidad')->insert([
                'aud_urbanizacion_id' => $this->auditData['aud_urbanizacion_id'] ?? null,
                'aud_user_id' => $this->auditData['aud_user_id'] ?? null,
                'aud_user_nombre' => $this->auditData['aud_user_nombre'] ?? 'SISTEMA',
                'aud_evento' => $this->auditData['aud_evento'] ?? 'EVENTO',
                'aud_tabla' => $this->auditData['aud_tabla'] ?? 'GENERAL',
                'aud_registro_id' => (string) ($this->auditData['aud_registro_id'] ?? '0'),
                'aud_old_values' => isset($this->auditData['aud_old_values']) ? json_encode($this->auditData['aud_old_values'], JSON_UNESCAPED_UNICODE) : null,
                'aud_new_values' => isset($this->auditData['aud_new_values']) ? json_encode($this->auditData['aud_new_values'], JSON_UNESCAPED_UNICODE) : null,
                'aud_diff' => isset($this->auditData['aud_diff']) ? json_encode($this->auditData['aud_diff'], JSON_UNESCAPED_UNICODE) : null,
                'aud_ip_address' => $this->auditData['aud_ip_address'] ?? null,
                'aud_user_agent' => $this->auditData['aud_user_agent'] ?? null,
                'aud_created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Error registrando log asincrono de auditoria CDC: ' . $e->getMessage(), [
                'payload' => $this->auditData
            ]);
        }
    }
}
