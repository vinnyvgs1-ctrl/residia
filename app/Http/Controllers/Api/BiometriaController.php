<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BiometriaController extends Controller
{
    /**
     * Obtener listado de residentes y su estado de enrolamiento biométrico
     */
    public function index(Request $request): JsonResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');

        $personas = DB::table('core_personas')
            ->join('core_inmueble_residentes', 'core_personas.per_id', '=', 'core_inmueble_residentes.res_persona_id')
            ->join('core_inmuebles', 'core_inmueble_residentes.res_inmueble_id', '=', 'core_inmuebles.inm_id')
            ->where('core_inmuebles.inm_urbanizacion_id', $urbanizacionId)
            ->where('core_inmueble_residentes.res_activo', true)
            ->whereNull('core_personas.deleted_at')
            ->select(
                'core_personas.per_id',
                'core_personas.per_identificacion',
                'core_personas.per_nombres',
                'core_personas.per_apellidos',
                'core_personas.per_email',
                'core_personas.per_telefono',
                'core_personas.per_foto_url',
                'core_personas.per_es_vetado',
                'core_inmuebles.inm_id',
                'core_inmuebles.inm_manzana_bloque',
                'core_inmuebles.inm_numero_villa',
                'core_inmueble_residentes.res_tipo_relacion',
                'core_inmueble_residentes.res_es_principal'
            )
            ->distinct()
            ->orderBy('core_inmuebles.inm_manzana_bloque')
            ->orderBy('core_inmuebles.inm_numero_villa')
            ->get();

        // Enlazar estado biométrico registrado
        $biometrias = DB::table('op_biometria_residentes')
            ->where('bio_urbanizacion_id', $urbanizacionId)
            ->whereNull('deleted_at')
            ->get()
            ->groupBy('bio_persona_id');

        $items = $personas->map(function ($p) use ($biometrias) {
            $bioList = $biometrias->get($p->per_id, collect());
            $tieneFacial = $bioList->firstWhere('bio_tipo_credencial', 'FACIAL');
            $tieneHuella = $bioList->firstWhere('bio_tipo_credencial', 'HUELLA');
            $tieneTarjeta = $bioList->firstWhere('bio_tipo_credencial', 'TARJETA');

            return [
                'persona_id' => $p->per_id,
                'identificacion' => $p->per_identificacion,
                'nombre_completo' => trim("{$p->per_nombres} {$p->per_apellidos}"),
                'email' => $p->per_email,
                'telefono' => $p->per_telefono,
                'es_vetado' => (bool) $p->per_es_vetado,
                'inmueble' => "Mz {$p->inm_manzana_bloque} V.{$p->inm_numero_villa}",
                'inmueble_id' => $p->inm_id,
                'tipo_relacion' => $p->res_tipo_relacion,
                'es_principal' => (bool) $p->res_es_principal,
                'biometria' => [
                    'facial' => $tieneFacial ? [
                        'estado' => $tieneFacial->bio_estado_sincronizacion,
                        'ultimo_sync' => $tieneFacial->bio_ultimo_sync,
                        'foto' => $tieneFacial->bio_foto_rostro_path
                    ] : null,
                    'huella' => $tieneHuella ? [
                        'estado' => $tieneHuella->bio_estado_sincronizacion,
                        'ultimo_sync' => $tieneHuella->bio_ultimo_sync
                    ] : null,
                    'tarjeta' => $tieneTarjeta ? [
                        'numero' => $tieneTarjeta->bio_tarjeta_no,
                        'estado' => $tieneTarjeta->bio_estado_sincronizacion
                    ] : null,
                ]
            ];
        });

        // Dispositivos biométricos disponibles
        $dispositivos = DB::table('op_garita_dispositivos')
            ->where('disp_urbanizacion_id', $urbanizacionId)
            ->whereIn('disp_tipo', ['BIOMETRICO_FACIAL', 'HUELLERO', 'CONTROL_ACCESO', 'LPR'])
            ->whereNull('deleted_at')
            ->get();

        // Resumen métricas
        $totalResidentes = $items->count();
        $enroladosFacial = $items->filter(fn($i) => !empty($i['biometria']['facial']))->count();
        $enroladosHuella = $items->filter(fn($i) => !empty($i['biometria']['huella']))->count();

        return response()->json([
            'success' => true,
            'data' => [
                'residentes' => $items,
                'dispositivos' => $dispositivos,
                'kpis' => [
                    'total_residentes' => $totalResidentes,
                    'enrolados_facial' => $enroladosFacial,
                    'enrolados_huella' => $enroladosHuella,
                    'cobertura_facial_pct' => $totalResidentes > 0 ? round(($enroladosFacial / $totalResidentes) * 100, 1) : 0,
                    'terminales_online' => $dispositivos->where('disp_estado_conexion', 'ONLINE')->count(),
                    'total_terminales' => $dispositivos->count()
                ]
            ]
        ]);
    }

    /**
     * Sincronizar un lote de residentes hacia una terminal Hikvision vía ISAPI
     */
    public function syncLoteHikvision(Request $request): JsonResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');

        $validated = $request->validate([
            'dispositivo_id' => 'required|exists:op_garita_dispositivos,disp_id',
            'persona_ids' => 'nullable|array',
            'persona_ids.*' => 'integer|exists:core_personas,per_id',
            'sync_todo' => 'nullable|boolean'
        ]);

        $disp = DB::table('op_garita_dispositivos')
            ->where('disp_id', $validated['dispositivo_id'])
            ->where('disp_urbanizacion_id', $urbanizacionId)
            ->first();

        if (!$disp) {
            return response()->json(['success' => false, 'message' => 'Dispositivo terminal no encontrado.'], 404);
        }

        // Obtener residentes a sincronizar
        $query = DB::table('core_personas')
            ->join('core_inmueble_residentes', 'core_personas.per_id', '=', 'core_inmueble_residentes.res_persona_id')
            ->join('core_inmuebles', 'core_inmueble_residentes.res_inmueble_id', '=', 'core_inmuebles.inm_id')
            ->where('core_inmuebles.inm_urbanizacion_id', $urbanizacionId)
            ->where('core_inmueble_residentes.res_activo', true)
            ->whereNull('core_personas.deleted_at')
            ->select('core_personas.*', 'core_inmuebles.inm_manzana_bloque', 'core_inmuebles.inm_numero_villa')
            ->distinct();

        if (empty($validated['sync_todo']) && !empty($validated['persona_ids'])) {
            $query->whereIn('core_personas.per_id', $validated['persona_ids']);
        }

        $residentes = $query->get();

        $exitosos = 0;
        $fallidos = 0;
        $detalles = [];

        // Simulación / Conexión ISAPI protegida
        $isSimulation = ($disp->disp_ip === '127.0.0.1' || str_starts_with($disp->disp_ip, '192.168.') || empty($disp->disp_password_api));

        foreach ($residentes as $res) {
            $employeeNo = (string) $res->per_identificacion ?: "PER-{$res->per_id}";
            $nombre = trim("{$res->per_nombres} {$res->per_apellidos}");

            if ($isSimulation) {
                // Modo Simulación de Enrolamiento ISAPI (Desarrollo y demostración local)
                $exitosos++;
                $detalles[] = [
                    'persona_id' => $res->per_id,
                    'nombre' => $nombre,
                    'employeeNo' => $employeeNo,
                    'status' => 'OK_SIMULADO',
                    'mensaje' => 'Comando ISAPI UserInfo emitido y emulado exitosamente.'
                ];
            } else {
                // Intento de conexión ISAPI con Digest Auth
                try {
                    $client = new Client([
                        'base_uri' => "http://{$disp->disp_ip}:{$disp->disp_puerto}",
                        'timeout' => 5,
                        'auth' => [$disp->disp_usuario_api ?: 'admin', $disp->disp_password_api ?: 'Admin2026.*', 'digest'],
                        'verify' => false
                    ]);

                    // Payload ISAPI UserInfo
                    $payload = [
                        'UserInfo' => [
                            'employeeNo' => $employeeNo,
                            'name' => substr($nombre, 0, 32),
                            'userType' => 'normal',
                            'closeDelay' => 0,
                            'userVerifyMode' => 'cardOrFaceOrFp',
                            'Valid' => [
                                'enable' => true,
                                'beginTime' => now()->format('Y-m-d\TH:i:s'),
                                'endTime' => now()->addYears(2)->format('Y-m-d\TH:i:s'),
                                'timeType' => 'local'
                            ]
                        ]
                    ];

                    $resIsapi = $client->put('/ISAPI/AccessControl/UserInfo/SetUp?format=json', [
                        'json' => $payload
                    ]);

                    if ($resIsapi->getStatusCode() >= 200 && $resIsapi->getStatusCode() < 300) {
                        $exitosos++;
                        $detalles[] = [
                            'persona_id' => $res->per_id,
                            'nombre' => $nombre,
                            'status' => 'OK_ISAPI',
                            'mensaje' => 'Sincronizado en hardware Hikvision.'
                        ];
                    } else {
                        $fallidos++;
                        $detalles[] = [
                            'persona_id' => $res->per_id,
                            'nombre' => $nombre,
                            'status' => 'ERROR_STATUS',
                            'mensaje' => 'Código de respuesta: ' . $resIsapi->getStatusCode()
                        ];
                    }
                } catch (\Throwable $e) {
                    $fallidos++;
                    $detalles[] = [
                        'persona_id' => $res->per_id,
                        'nombre' => $nombre,
                        'status' => 'CONNECTION_FAILED',
                        'mensaje' => $e->getMessage()
                    ];
                }
            }

            // Actualizar o crear registro en op_biometria_residentes
            DB::table('op_biometria_residentes')->updateOrInsert(
                [
                    'bio_urbanizacion_id' => $urbanizacionId,
                    'bio_persona_id' => $res->per_id,
                    'bio_tipo_credencial' => 'FACIAL'
                ],
                [
                    'bio_employee_no' => $employeeNo,
                    'bio_estado_sincronizacion' => $fallidos > 0 && !$isSimulation ? 'ERROR' : 'SINCRONIZADO',
                    'bio_ultimo_sync' => now(),
                    'bio_detalles_dispositivos' => json_encode([
                        'disp_id' => $disp->disp_id,
                        'disp_nombre' => $disp->disp_nombre,
                        'carril' => $disp->disp_carril,
                        'modo' => $isSimulation ? 'SIMULADO' : 'ISAPI_DIGEST'
                    ]),
                    'updated_at' => now(),
                ]
            );
        }

        // Registrar bitácora de sincronización
        $logId = DB::table('op_biometria_sync_logs')->insertGetId([
            'slog_urbanizacion_id' => $urbanizacionId,
            'slog_dispositivo_id' => $disp->disp_id,
            'slog_canal' => 'ISAPI_DIRECT',
            'slog_accion' => 'ENROLAR',
            'slog_total_personas' => $residentes->count(),
            'slog_exitosos' => $exitosos,
            'slog_fallidos' => $fallidos,
            'slog_detalles' => json_encode($detalles),
            'slog_ejecutado_por' => auth('api')->user()->usr_name ?? 'ADMIN',
            'created_at' => now(),
            'updated_at' => now(),
        ], 'slog_id');

        AuditLogger::log('HIKVISION_ISAPI_SYNC_LOTE', 'op_biometria_sync_logs', $logId, [
            'terminal' => $disp->disp_nombre,
            'total' => $residentes->count(),
            'exitosos' => $exitosos,
            'fallidos' => $fallidos
        ]);

        return response()->json([
            'success' => true,
            'message' => "Sincronización completada. {$exitosos} procesados con éxito.",
            'data' => [
                'total' => $residentes->count(),
                'exitosos' => $exitosos,
                'fallidos' => $fallidos,
                'modo' => $isSimulation ? 'Simulador ISAPI Activo' : 'Conexión ISAPI Directa',
                'detalles' => $detalles
            ]
        ]);
    }

    /**
     * Exportar plantilla masiva compatible con iVMS-4200 / HikCentral / VMS
     */
    public function exportarPlantillaIvms(Request $request): StreamedResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');
        $urb = DB::table('core_urbanizaciones')->where('urb_id', $urbanizacionId)->first();
        $orgName = $urb ? $urb->urb_nombre : 'Residia Urbanización';

        $residentes = DB::table('core_personas')
            ->join('core_inmueble_residentes', 'core_personas.per_id', '=', 'core_inmueble_residentes.res_persona_id')
            ->join('core_inmuebles', 'core_inmueble_residentes.res_inmueble_id', '=', 'core_inmuebles.inm_id')
            ->where('core_inmuebles.inm_urbanizacion_id', $urbanizacionId)
            ->where('core_inmueble_residentes.res_activo', true)
            ->whereNull('core_personas.deleted_at')
            ->select('core_personas.*', 'core_inmuebles.inm_manzana_bloque', 'core_inmuebles.inm_numero_villa')
            ->distinct()
            ->orderBy('core_personas.per_nombres')
            ->get();

        $filename = "iVMS4200_Plantilla_Biometrica_" . date('Ymd_His') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        // Columnas exactas de la especificación iVMS-4200
        $columns = [
            '*Person ID',
            '*Organization',
            '*Person Name',
            '*Gender',
            'Contact',
            'Email',
            'Effective Time',
            'Expiry Time',
            'Card No.',
            'Room No.',
            'Floor No.'
        ];

        $callback = function () use ($residentes, $columns, $orgName) {
            $handle = fopen('php://output', 'w');
            // Inyectar BOM UTF-8 para compatibilidad absoluta con Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, $columns);

            foreach ($residentes as $r) {
                $nombreCompleto = trim("{$r->per_nombres} {$r->per_apellidos}");
                $personId = $r->per_identificacion ?: "RES" . str_pad($r->per_id, 6, '0', STR_PAD_LEFT);
                $villa = "Mz {$r->inm_manzana_bloque} V.{$r->inm_numero_villa}";

                fputcsv($handle, [
                    $personId,
                    $orgName,
                    $nombreCompleto,
                    'Male', // Default standard
                    $r->per_telefono ?: '',
                    $r->per_email ?: '',
                    date('Y-m-d'),
                    date('Y-m-d', strtotime('+3 years')),
                    $personId, // Default Card No to ID
                    $villa,
                    $r->inm_manzana_bloque ?: '1'
                ]);
            }

            fclose($handle);
        };

        // Auditoría obligatoria de exportación
        AuditLogger::log('EXPORT_IVMS4200_TEMPLATE', 'core_personas', 0, [
            'total_registros' => $residentes->count(),
            'organizacion' => $orgName,
            'formato' => 'CSV_IVMS4200'
        ]);

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Importar y procesar archivo de retorno de iVMS-4200
     */
    public function importarIvms(Request $request): JsonResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');

        $request->validate([
            'archivo' => 'required|file|max:5120'
        ]);

        $file = $request->file('archivo');
        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            return response()->json(['success' => false, 'message' => 'No se pudo abrir el archivo.'], 400);
        }

        $header = fgetcsv($handle);
        $procesados = 0;
        $errores = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (empty($row[0])) continue;

            $personId = trim($row[0]);
            $cardNo = isset($row[8]) ? trim($row[8]) : null;

            $persona = DB::table('core_personas')
                ->where('per_identificacion', $personId)
                ->first();

            if ($persona) {
                DB::table('op_biometria_residentes')->updateOrInsert(
                    [
                        'bio_urbanizacion_id' => $urbanizacionId,
                        'bio_persona_id' => $persona->per_id,
                        'bio_tipo_credencial' => 'FACIAL'
                    ],
                    [
                        'bio_employee_no' => $personId,
                        'bio_tarjeta_no' => is_numeric($cardNo) ? (int)$cardNo : null,
                        'bio_estado_sincronizacion' => 'SINCRONIZADO',
                        'bio_ultimo_sync' => now(),
                        'updated_at' => now(),
                    ]
                );
                $procesados++;
            } else {
                $errores++;
            }
        }

        fclose($handle);

        AuditLogger::log('IMPORT_IVMS4200_RESULT', 'op_biometria_residentes', 0, [
            'procesados' => $procesados,
            'no_encontrados' => $errores
        ]);

        return response()->json([
            'success' => true,
            'message' => "Importación completada: {$procesados} registros biométricos vinculados. ({$errores} no encontrados en el padrón)."
        ]);
    }
}
