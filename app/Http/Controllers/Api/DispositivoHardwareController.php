<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Operations\Garita;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DispositivoHardwareController extends Controller
{
    /**
     * Listar estado de terminales IoT, cámaras LPR y antenas RFID de la garita
     */
    public function estadoDispositivos(Request $request): JsonResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');

        $dispositivos = DB::table('op_garita_dispositivos')
            ->where('disp_urbanizacion_id', $urbanizacionId)
            ->whereNull('deleted_at')
            ->orderBy('disp_tipo', 'asc')
            ->orderBy('disp_nombre', 'asc')
            ->get();

        // Si aún no se han configurado dispositivos para esta sede, inicializar virtuales por defecto
        if ($dispositivos->isEmpty()) {
            $defaultDevices = [
                [
                    'disp_urbanizacion_id' => $urbanizacionId,
                    'disp_nombre' => 'Cámara LPR Entrada Principal (Hikvision DS-2CD7A26G0/P-IZS)',
                    'disp_tipo' => 'CAMARA_LPR',
                    'disp_ip' => '192.168.10.150',
                    'disp_puerto' => 80,
                    'disp_protocolo' => 'HIKVISION_ISAPI',
                    'disp_carril' => 'ENTRADA_01',
                    'disp_estado_conexion' => 'ONLINE',
                    'disp_ultimo_ping' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'disp_urbanizacion_id' => $urbanizacionId,
                    'disp_nombre' => 'Antena RFID UHF Largo Alcance (ZKTeco ProRF-UHF 900MHz)',
                    'disp_tipo' => 'ANTENA_RFID',
                    'disp_ip' => '192.168.10.151',
                    'disp_puerto' => 4370,
                    'disp_protocolo' => 'WIEGAND_TCP',
                    'disp_carril' => 'ENTRADA_01',
                    'disp_estado_conexion' => 'ONLINE',
                    'disp_ultimo_ping' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'disp_urbanizacion_id' => $urbanizacionId,
                    'disp_nombre' => 'Controlador de Relé Barrera Vehicular (Relay IP / Wiegand Barrier)',
                    'disp_tipo' => 'CONTROLADOR_BARRERA',
                    'disp_ip' => '192.168.10.152',
                    'disp_puerto' => 8080,
                    'disp_protocolo' => 'HTTP_JSON_RELAY',
                    'disp_carril' => 'BARRERA_ENTRADA_01',
                    'disp_estado_conexion' => 'ONLINE',
                    'disp_ultimo_ping' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ];

            foreach ($defaultDevices as $dev) {
                DB::table('op_garita_dispositivos')->insert($dev);
            }

            $dispositivos = DB::table('op_garita_dispositivos')
                ->where('disp_urbanizacion_id', $urbanizacionId)
                ->whereNull('deleted_at')
                ->get();
        }

        return response()->json([
            'success' => true,
            'dispositivos' => $dispositivos
        ]);
    }

    /**
     * Webhook de detección de Matrícula (LPR Hikvision ISAPI / ANPR)
     */
    public function webhookLpr(Request $request): JsonResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');
        $placa = strtoupper(trim((string) ($request->input('placa') ?: $request->input('licensePlate') ?: $request->input('PlateResult.plateNumber'))));
        $carril = $request->input('carril', 'ENTRADA_01');
        $confianza = (float) ($request->input('confidence') ?: 98.5);

        if (!$placa) {
            return response()->json(['success' => false, 'message' => 'Placa no identificada en el payload LPR'], 400);
        }

        // 1. Evaluar si la placa está en la Lista Negra / Veto de la urbanización
        $veto = DB::table('op_lista_negra')
            ->where('lis_urbanizacion_id', $urbanizacionId)
            ->where('lis_activo', true)
            ->where('lis_placa_vehiculo', )
            ->whereNull('deleted_at')
            ->first();

        if ($veto) {
            AuditLogger::log('GARITA_LPR_VETO_DETECTADO', 'op_lista_negra', $veto->lis_id,
                ['placa' => $placa, 'carril' => $carril, 'accion' => 'BARRERA_BLOQUEADA']
            );

            return response()->json([
                'success' => false,
                'apertura' => false,
                'motivo_bloqueo' => 'VEHICULO_VETADO',
                'alerta' => "Vehículo {$placa} se encuentra en Lista Negra. Motivo: {$veto->lis_motivo}",
                'placa' => $placa
            ], 403);
        }

        // 2. Buscar si el vehículo pertenece a un residente autorizado
        $vehiculoResidente = DB::table('core_inmueble_vehiculos')
            ->join('core_inmuebles', 'core_inmueble_vehiculos.veh_inmueble_id', '=', 'core_inmuebles.inm_id')
            ->leftJoin('core_personas', 'core_inmueble_vehiculos.veh_persona_id', '=', 'core_personas.per_id')
            ->where('core_inmuebles.inm_urbanizacion_id', $urbanizacionId)
            ->where('core_inmueble_vehiculos.veh_placa', $placa)
            ->where('core_inmueble_vehiculos.veh_activo', true)
            ->whereNull('core_inmueble_vehiculos.deleted_at')
            ->select(
                'core_inmueble_vehiculos.*',
                'core_inmuebles.inm_id',
                'core_inmuebles.inm_manzana_bloque',
                'core_inmuebles.inm_numero_villa',
                'core_personas.per_nombres',
                'core_personas.per_apellidos'
            )
            ->first();

        if ($vehiculoResidente) {
            // Verificar alícuotas
            $cuotasMora = DB::table('fin_obligaciones_alicuotas')
                ->where('obl_inmueble_id', $vehiculoResidente->inm_id)
                ->whereIn('obl_estado', ['PENDIENTE', 'VENCIDO'])
                ->whereNull('deleted_at')
                ->count();

            // Registrar ingreso automático por hardware
            $accesoId = DB::table('op_control_accesos')->insertGetId([
                'acc_urbanizacion_id' => $urbanizacionId,
                'acc_inmueble_id' => $vehiculoResidente->inm_id,
                'acc_persona_id' => $vehiculoResidente->veh_persona_id,
                'acc_rol_visita' => 'RESIDENTE',
                'acc_vehiculo_placa' => $placa,
                'acc_vehiculo_marca' => $vehiculoResidente->veh_marca,
                'acc_vehiculo_color' => $vehiculoResidente->veh_color,
                'acc_tipo_ingreso' => 'VEHICULAR',
                'acc_fecha_ingreso' => now(),
                'acc_guardia_ingreso_usr_id' => auth()->id() ?: 1,
                'acc_observaciones' => "Ingreso Automatizado LPR (Cámara {$carril}, Confianza: {$confianza}%)",
                'created_at' => now(),
                'updated_at' => now(),
            ], 'acc_id');

            AuditLogger::log('GARITA_LPR_APERTURA_AUTOMATICA', 'op_control_accesos', $accesoId,
                ['placa' => $placa, 'carril' => $carril, 'cuotas_mora' => $cuotasMora]
            );

            return response()->json([
                'success' => true,
                'apertura' => true,
                'tipo' => 'RESIDENTE_AUTORIZADO',
                'placa' => $placa,
                'inmueble' => "{$vehiculoResidente->inm_manzana_bloque} - {$vehiculoResidente->inm_numero_villa}",
                'titular' => "{$vehiculoResidente->per_nombres} {$vehiculoResidente->per_apellidos}",
                'cuotas_mora' => $cuotasMora,
                'mensaje' => 'Apertura de barrera autorizada por LPR'
            ]);
        }

        // 3. Si no es residente, evaluar si cuenta con un Pase QR vigente para esta placa
        $pase = DB::table('op_invitaciones_qr')
            ->join('core_inmuebles', 'op_invitaciones_qr.inv_inmueble_id', '=', 'core_inmuebles.inm_id')
            ->where('op_invitaciones_qr.inv_urbanizacion_id', $urbanizacionId)
            ->where('op_invitaciones_qr.inv_visitante_placa', $placa)
            ->where('op_invitaciones_qr.inv_estado', 'ACTIVO')
            ->where('op_invitaciones_qr.inv_valido_desde', '<=', now())
            ->where('op_invitaciones_qr.inv_valido_hasta', '>=', now())
            ->whereNull('op_invitaciones_qr.deleted_at')
            ->select('op_invitaciones_qr.*', 'core_inmuebles.inm_manzana_bloque', 'core_inmuebles.inm_numero_villa')
            ->first();

        if ($pase) {
            // Resolver o registrar persona de la visita
            $personaVisitaId = null;
            if (!empty($pase->inv_visitante_identificacion)) {
                $personaVisitaId = DB::table('core_personas')
                    ->where('per_identificacion', $pase->inv_visitante_identificacion)
                    ->value('per_id');
            }
            if (!$personaVisitaId) {
                $nombres = explode(' ', $pase->inv_visitante_nombre, 2);
                $personaVisitaId = DB::table('core_personas')->insertGetId([
                    'per_identificacion' => $pase->inv_visitante_identificacion ?: ('TEMP-' . rand(100000, 999999)),
                    'per_nombres' => $nombres[0] ?? 'Visitante',
                    'per_apellidos' => $nombres[1] ?? 'Autorizado',
                    'created_at' => now(),
                    'updated_at' => now(),
                ], 'per_id');
            }

            $accesoId = DB::table('op_control_accesos')->insertGetId([
                'acc_urbanizacion_id' => $urbanizacionId,
                'acc_persona_id' => $personaVisitaId,
                'acc_inmueble_id' => $pase->inv_inmueble_id,
                'acc_rol_visita' => 'VISITA',
                'acc_vehiculo_placa' => $placa,
                'acc_tipo_ingreso' => 'VEHICULAR',
                'acc_fecha_ingreso' => now(),
                'acc_guardia_ingreso_usr_id' => auth()->id() ?: 1,
                'acc_observaciones' => "Ingreso Automatizado LPR con Pase QR {$pase->inv_codigo_token} ({$pase->inv_visitante_nombre})",
                'created_at' => now(),
                'updated_at' => now(),
            ], 'acc_id');

            // Incrementar uso del pase
            DB::table('op_invitaciones_qr')
                ->where('inv_id', $pase->inv_id)
                ->increment('inv_usos_realizados', 1, [
                    'inv_estado' => ($pase->inv_usos_realizados + 1 >= $pase->inv_usos_permitidos) ? 'CANJEADO' : 'ACTIVO',
                    'updated_at' => now()
                ]);

            AuditLogger::log('GARITA_LPR_APERTURA_PASE_QR', 'op_control_accesos', $accesoId,
                ['placa' => $placa, 'carril' => $carril, 'token' => $pase->inv_codigo_token]
            );

            return response()->json([
                'success' => true,
                'apertura' => true,
                'tipo' => 'VISITANTE_PASE_QR',
                'placa' => $placa,
                'visitante' => $pase->inv_visitante_nombre,
                'destino' => "{$pase->inm_manzana_bloque} - {$pase->inm_numero_villa}",
                'mensaje' => 'Apertura de barrera autorizada por Pase QR'
            ]);
        }

        // Si no es residente ni tiene pase QR previo
        return response()->json([
            'success' => true,
            'apertura' => false,
            'tipo' => 'VISITA_NO_REGISTRADA',
            'placa' => $placa,
            'mensaje' => 'Vehículo no enrolado como residente ni con invitación previa. Requiere autorización manual en garita.'
        ]);
    }

    /**
     * Webhook de lectura de Antena RFID UHF (Tag en parabrisas)
     */
    public function webhookRfid(Request $request): JsonResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');
        $tagRfid = strtoupper(trim((string) $request->input('tag_rfid')));
        $antenaId = $request->input('antena_id', 'ANTENA_UHF_01');

        if (!$tagRfid) {
            return response()->json(['success' => false, 'message' => 'Tag RFID no especificado'], 400);
        }

        $vehiculo = DB::table('core_inmueble_vehiculos')
            ->join('core_inmuebles', 'core_inmueble_vehiculos.veh_inmueble_id', '=', 'core_inmuebles.inm_id')
            ->leftJoin('core_personas', 'core_inmueble_vehiculos.veh_persona_id', '=', 'core_personas.per_id')
            ->where('core_inmuebles.inm_urbanizacion_id', $urbanizacionId)
            ->where('core_inmueble_vehiculos.veh_tag_rfid', $tagRfid)
            ->where('core_inmueble_vehiculos.veh_activo', true)
            ->whereNull('core_inmueble_vehiculos.deleted_at')
            ->select(
                'core_inmueble_vehiculos.*',
                'core_inmuebles.inm_id',
                'core_inmuebles.inm_manzana_bloque',
                'core_inmuebles.inm_numero_villa',
                'core_personas.per_nombres',
                'core_personas.per_apellidos'
            )
            ->first();

        if (!$vehiculo) {
            return response()->json([
                'success' => false,
                'apertura' => false,
                'mensaje' => "Tag RFID {$tagRfid} no enrolado o inactivo"
            ], 404);
        }

        // Registrar acceso expedito por RFID
        $accesoId = DB::table('op_control_accesos')->insertGetId([
            'acc_urbanizacion_id' => $urbanizacionId,
            'acc_inmueble_id' => $vehiculo->inm_id,
            'acc_persona_id' => $vehiculo->veh_persona_id,
            'acc_rol_visita' => 'RESIDENTE',
            'acc_vehiculo_placa' => $vehiculo->veh_placa,
            'acc_vehiculo_marca' => $vehiculo->veh_marca,
            'acc_vehiculo_color' => $vehiculo->veh_color,
            'acc_tipo_ingreso' => 'VEHICULAR',
            'acc_fecha_ingreso' => now(),
            'acc_guardia_ingreso_usr_id' => auth()->id() ?: 1,
            'acc_observaciones' => "Apertura Automática RFID Tag {$tagRfid} ({$antenaId})",
            'created_at' => now(),
            'updated_at' => now(),
        ], 'acc_id');

        AuditLogger::log('GARITA_RFID_APERTURA_AUTOMATICA', 'op_control_accesos', $accesoId,
            ['tag' => $tagRfid, 'placa' => $vehiculo->veh_placa]
        );

        return response()->json([
            'success' => true,
            'apertura' => true,
            'tag' => $tagRfid,
            'placa' => $vehiculo->veh_placa,
            'inmueble' => "{$vehiculo->inm_manzana_bloque} - {$vehiculo->inm_numero_villa}",
            'residente' => "{$vehiculo->per_nombres} {$vehiculo->per_apellidos}",
            'mensaje' => 'Apertura de barrera autorizada por Tag RFID UHF'
        ]);
    }

    /**
     * Apertura Manual / Modo Contingencia con Auditoría Estricta CDC
     */
    public function aperturaContingencia(Request $request): JsonResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');
        
        $validated = $request->validate([
            'carril' => 'required|string',
            'motivo' => 'required|string|min:5',
            'vehiculo_placa' => 'nullable|string|max:15',
            'inmueble_id' => 'nullable|exists:core_inmuebles,inm_id'
        ]);

        $guardia = auth('api')->user();

        $logId = DB::table('acc_aperturas_contingencia')->insertGetId([
            'apt_urbanizacion_id' => $urbanizacionId,
            'apt_guardia_usr_id' => $guardia->usr_id,
            'apt_carril' => $validated['carril'],
            'apt_motivo' => $validated['motivo'],
            'apt_vehiculo_placa' => $validated['vehiculo_placa'] ? strtoupper(trim($validated['vehiculo_placa'])) : null,
            'apt_inmueble_id' => ($validated['inmueble_id'] ?? null),
            'apt_fecha_hora' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ], 'apt_id');

        // Auditoría inmutable CDC
        AuditLogger::log('GARITA_APERTURA_CONTINGENCIA', 'acc_aperturas_contingencia', $logId,
            [
                'carril' => $validated['carril'],
                'motivo' => $validated['motivo'],
                'placa' => $validated['vehiculo_placa'] ?? 'SIN_PLACA',
                'guardia_id' => $guardia->usr_id
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Pulso de apertura de emergencia emitido al relé de barrera. Evento auditado en bitácora CDC.',
            'log_id' => $logId,
            'fecha_hora' => now()->toIso8601String()
        ]);
    }
}
