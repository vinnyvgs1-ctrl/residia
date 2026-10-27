<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Operations\ControlAcceso;
use App\Models\Core\Persona;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvitacionController extends Controller
{
    /**
     * Listar invitaciones / pases QR de la urbanización activa
     */
    public function index(Request $request): JsonResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');

        $query = DB::table('op_invitaciones_qr')
            ->leftJoin('core_inmuebles', 'core_inmuebles.inm_id', '=', 'op_invitaciones_qr.inv_inmueble_id')
            ->leftJoin('sec_users', 'sec_users.usr_id', '=', 'op_invitaciones_qr.inv_usuario_creador_id')
            ->where('op_invitaciones_qr.inv_urbanizacion_id', $urbanizacionId)
            ->whereNull('op_invitaciones_qr.deleted_at')
            ->select(
                'op_invitaciones_qr.*',
                'core_inmuebles.inm_manzana_bloque',
                'core_inmuebles.inm_numero_villa',
                'sec_users.usr_name as creador_nombre'
            )
            ->orderBy('op_invitaciones_qr.created_at', 'desc');

        if ($request->filled('estado')) {
            $query->where('op_invitaciones_qr.inv_estado', $request->estado);
        }

        if ($request->filled('buscar')) {
            $b = '%' . strtolower($request->buscar) . '%';
            $query->where(function ($q) use ($b) {
                $q->whereRaw('LOWER(inv_visitante_nombre) LIKE ?', [$b])
                  ->orWhereRaw('LOWER(inv_codigo_token) LIKE ?', [$b])
                  ->orWhereRaw('LOWER(inv_visitante_placa) LIKE ?', [$b]);
            });
        }

        $data = $request->boolean('paginate') ? $query->paginate(20) : $query->limit(50)->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Emitir un nuevo Pase QR pre-autorizado
     */
    public function store(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');

        $validated = $request->validate([
            'visitante_nombre' => 'required|string|max:150',
            'visitante_identificacion' => 'nullable|string|max:30',
            'visitante_placa' => 'nullable|string|max:15',
            'tipo_ingreso' => 'required|in:VEHICULAR,PEATONAL',
            'inmueble_id' => 'nullable|exists:core_inmuebles,inm_id',
            'valido_desde' => 'required',
            'valido_hasta' => 'required',
            'usos_permitidos' => 'integer|min:1|max:10',
            'observaciones' => 'nullable|string',
        ]);

        $token = 'QR-' . strtoupper(Str::random(8)) . '-' . rand(1000, 9999);
        $uuid = (string) Str::uuid();

        $valDesde = Carbon::parse($validated['valido_desde']);
        $valHasta = Carbon::parse($validated['valido_hasta']);

        DB::table('op_invitaciones_qr')->insert([
            'inv_id' => $uuid,
            'inv_urbanizacion_id' => $urbanizacionId,
            'inv_inmueble_id' => $validated['inmueble_id'] ?? null,
            'inv_usuario_creador_id' => $user->usr_id,
            'inv_codigo_token' => $token,
            'inv_visitante_nombre' => $validated['visitante_nombre'],
            'inv_visitante_identificacion' => $validated['visitante_identificacion'] ?? null,
            'inv_visitante_placa' => !empty($validated['visitante_placa']) ? strtoupper($validated['visitante_placa']) : null,
            'inv_tipo_ingreso' => $validated['tipo_ingreso'],
            'inv_valido_desde' => $valDesde,
            'inv_valido_hasta' => $valHasta,
            'inv_usos_permitidos' => $validated['usos_permitidos'] ?? 1,
            'inv_usos_realizados' => 0,
            'inv_estado' => 'ACTIVO',
            'inv_observaciones' => $validated['observaciones'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AuditLogger::log('QR_INVITATION_CREATED', 'op_invitaciones_qr', $uuid, [
            'token' => $token,
            'visitante' => $validated['visitante_nombre']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pase QR generado exitosamente.',
            'invitacion' => [
                'id' => $uuid,
                'token' => $token,
                'visitante' => $validated['visitante_nombre'],
                'valido_hasta' => $valHasta->toDateTimeString(),
            ]
        ]);
    }

    /**
     * Validar y Canjear Pase QR en Garita (Registro de Entrada Exprés)
     */
    public function validarYCanjear(Request $request): JsonResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');
        $token = trim((string) ($request->input('token') ?: $request->input('codigo_token')));

        if ($token === '') {
            return response()->json(['success' => false, 'message' => 'Token QR requerido'], 422);
        }

        $inv = DB::table('op_invitaciones_qr')
            ->where('inv_urbanizacion_id', $urbanizacionId)
            ->where('inv_codigo_token', $token)
            ->whereNull('deleted_at')
            ->first();

        if (!$inv) {
            return response()->json([
                'success' => false,
                'valido' => false,
                'message' => 'Pase QR no encontrado o inválido para esta urbanización.'
            ], 404);
        }

        $now = now();
        $desde = Carbon::parse($inv->inv_valido_desde);
        $hasta = Carbon::parse($inv->inv_valido_hasta);

        // Tolerancia de 10 minutos antes de la hora estipulada
        if ($now->lt($desde->subMinutes(10))) {
            return response()->json([
                'success' => false,
                'valido' => false,
                'message' => "El pase aún no está vigente. Válido a partir de: {$inv->inv_valido_desde}"
            ], 422);
        }

        if ($now->gt($hasta) || $inv->inv_estado === 'EXPIRADO') {
            DB::table('op_invitaciones_qr')->where('inv_id', $inv->inv_id)->update(['inv_estado' => 'EXPIRADO']);
            return response()->json([
                'success' => false,
                'valido' => false,
                'message' => 'El pase QR ha expirado.'
            ], 422);
        }

        if ($inv->inv_estado !== 'ACTIVO' || $inv->inv_usos_realizados >= $inv->inv_usos_permitidos) {
            return response()->json([
                'success' => false,
                'valido' => false,
                'message' => 'El pase QR ya ha alcanzado el límite de usos autorizados.'
            ], 422);
        }

        // Registrar o actualizar persona
        $persona = Persona::firstOrCreate(
            ['per_identificacion' => $inv->inv_visitante_identificacion ?: ('QR-' . substr($inv->inv_id, 0, 8))],
            [
                'per_nombres' => $inv->inv_visitante_nombre,
                'per_apellidos' => '(Invitado QR)',
            ]
        );

        if ($persona->per_es_vetado) {
            return response()->json([
                'success' => false,
                'valido' => false,
                'error' => 'ACCESO_DENEGADO_VETO',
                'message' => "ALERTA ROJA: El titular de este pase figura vetado en garita."
            ], 403);
        }

        // Crear acceso
        $acceso = ControlAcceso::create([
            'acc_urbanizacion_id' => $urbanizacionId,
            'acc_persona_id' => $persona->per_id,
            'acc_inmueble_id' => $inv->inv_inmueble_id,
            'acc_tipo_ingreso' => $inv->inv_tipo_ingreso,
            'acc_rol_visita' => 'VISITANTE',
            'acc_vehiculo_placa' => $inv->inv_visitante_placa,
            'acc_cantidad_ocupantes' => 1,
            'acc_fecha_ingreso' => now(),
            'acc_estado' => 'DENTRO',
            'acc_observaciones' => "Ingreso mediante Pase QR [{$inv->inv_codigo_token}]. " . ($inv->inv_observaciones ?: ''),
        ]);

        $usosActualizados = $inv->inv_usos_realizados + 1;
        $nuevoEstado = $usosActualizados >= $inv->inv_usos_permitidos ? 'USADO' : 'ACTIVO';

        DB::table('op_invitaciones_qr')->where('inv_id', $inv->inv_id)->update([
            'inv_usos_realizados' => $usosActualizados,
            'inv_estado' => $nuevoEstado,
            'updated_at' => now(),
        ]);

        AuditLogger::log('QR_PASS_REDEEMED', 'op_invitaciones_qr', $inv->inv_id, [
            'acceso_id' => $acceso->acc_id,
            'token' => $token
        ]);

        return response()->json([
            'success' => true,
            'valido' => true,
            'message' => 'Pase QR validado y acceso registrado con éxito.',
            'acceso' => $acceso,
            'visitante' => $inv->inv_visitante_nombre,
            'placa' => $inv->inv_visitante_placa,
        ]);
    }
}
