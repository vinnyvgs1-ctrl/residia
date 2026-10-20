<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Core\Inmueble;
use App\Models\Core\Persona;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InmuebleController extends Controller
{
    /**
     * Listado de Inmuebles de la Urbanización Activa
     */
    public function index(Request $request): JsonResponse
    {
        $urbanizacionId = $request->header('X-Urbanizacion-Id') 
            ?: (session('active_urbanizacion_id') ?: $request->input('urbanizacion_id'));

        $query = Inmueble::with(['residentes'])
            ->where('inm_urbanizacion_id', $urbanizacionId);

        if ($request->filled('buscar')) {
            $b = trim($request->input('buscar'));
            $query->where(function ($q) use ($b) {
                $q->where('inm_manzana_bloque', 'ilike', "%{$b}%")
                  ->orWhere('inm_numero_villa', 'ilike', "%{$b}%")
                  ->orWhere('inm_tipo', 'ilike', "%{$b}%");
            });
        }

        if ($request->filled('estado')) {
            $query->where('inm_estado', $request->input('estado'));
        }

        $inmuebles = $query->orderBy('inm_manzana_bloque')
            ->orderBy('inm_numero_villa')
            ->get();

        return response()->json([
            'success' => true,
            'total' => $inmuebles->count(),
            'data' => $inmuebles,
        ]);
    }

    /**
     * Crear un nuevo Inmueble (Villa, Departamento o Lote)
     */
    public function store(Request $request): JsonResponse
    {
        $urbanizacionId = $request->header('X-Urbanizacion-Id') 
            ?: (session('active_urbanizacion_id') ?: $request->input('urbanizacion_id'));

        $validated = $request->validate([
            'inm_manzana_bloque' => 'required|string|max:30',
            'inm_numero_villa' => 'required|string|max:30',
            'inm_tipo' => 'required|in:CASA,DEPARTAMENTO,LOTE_VACIO,COMERCIAL',
            'inm_estado' => 'required|in:HABITADO,DESOCUPADO,EN_CONSTRUCCION',
            'inm_alicuota_porcentaje' => 'nullable|integer',
            'inm_observaciones' => 'nullable|string',
        ]);

        $validated['inm_urbanizacion_id'] = $urbanizacionId;
        $validated['inm_activo'] = true;

        $inmueble = Inmueble::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Inmueble registrado y auditado exitosamente.',
            'data' => $inmueble,
        ], 201);
    }

    /**
     * Asignar o Vincular un Residente / Propietario al Inmueble
     */
    public function asignarResidente(Request $request, int $id): JsonResponse
    {
        $inmueble = Inmueble::findOrFail($id);

        $validated = $request->validate([
            'identificacion' => 'required|string',
            'nombres' => 'required|string',
            'apellidos' => 'required|string',
            'telefono' => 'nullable|string',
            'email' => 'nullable|email',
            'tipo_relacion' => 'required|in:PROPIETARIO,ARRENDATARIO,FAMILIAR,EMPLEADO_DOMESTICO',
            'es_principal' => 'boolean',
        ]);

        // Registrar o actualizar persona en core_personas
        $persona = Persona::updateOrCreate(
            ['per_identificacion' => $validated['identificacion']],
            [
                'per_nombres' => $validated['nombres'],
                'per_apellidos' => $validated['apellidos'],
                'per_telefono' => $validated['telefono'] ?? null,
                'per_email' => $validated['email'] ?? null,
            ]
        );

        // Vincular en core_inmueble_residentes
        DB::table('core_inmueble_residentes')->updateOrInsert(
            [
                'res_inmueble_id' => $inmueble->inm_id,
                'res_persona_id' => $persona->per_id,
            ],
            [
                'res_tipo_relacion' => $validated['tipo_relacion'],
                'res_es_principal' => $validated['es_principal'] ?? false,
                'res_activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Residente asignado correctamente al inmueble.',
            'data' => $inmueble->fresh(['residentes']),
        ]);
    }

    /**
     * Desvincular un Residente del Inmueble
     */
    public function removerResidente(int $inmuebleId, int $personaId): JsonResponse
    {
        DB::table('core_inmueble_residentes')
            ->where('res_inmueble_id', $inmuebleId)
            ->where('res_persona_id', $personaId)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Residente desvinculado del inmueble.',
        ]);
    }

    /**
     * Actualizar datos del Inmueble
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $urbanizacionId = $request->header('X-Urbanizacion-Id') 
            ?: (session('active_urbanizacion_id') ?: $request->input('urbanizacion_id'));

        // Blindaje Anti-IDOR: Garantizar pertenencia a la sede activa
        $inmueble = Inmueble::where('inm_id', $id)
            ->where('inm_urbanizacion_id', $urbanizacionId)
            ->firstOrFail();

        $validated = $request->validate([
            'inm_tipo' => 'sometimes|in:CASA,DEPARTAMENTO,LOTE_VACIO,COMERCIAL',
            'inm_estado' => 'sometimes|in:HABITADO,DESOCUPADO,EN_CONSTRUCCION',
            'inm_alicuota_porcentaje' => 'nullable|integer',
            'inm_observaciones' => 'nullable|string',
            'inm_activo' => 'sometimes|boolean',
        ]);

        $inmueble->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Inmueble actualizado exitosamente.',
            'data' => $inmueble,
        ]);
    }

    /**
     * Eliminación lógica del Inmueble
     */
    public function destroy(int $id): JsonResponse
    {
        $urbanizacionId = request()->header('X-Urbanizacion-Id') 
            ?: (session('active_urbanizacion_id') ?: request()->input('urbanizacion_id'));

        // Blindaje Anti-IDOR: Garantizar pertenencia a la sede activa
        $inmueble = Inmueble::where('inm_id', $id)
            ->where('inm_urbanizacion_id', $urbanizacionId)
            ->firstOrFail();
        $inmueble->delete();

        return response()->json([
            'success' => true,
            'message' => 'Inmueble eliminado correctamente.',
        ]);
    }

    /**
     * Listar vehículos asignados a un Inmueble
     */
    public function listarVehiculos(int $id): JsonResponse
    {
        $vehiculos = DB::table('core_inmueble_vehiculos')
            ->leftJoin('core_personas', 'core_inmueble_vehiculos.veh_persona_id', '=', 'core_personas.per_id')
            ->where('veh_inmueble_id', $id)
            ->whereNull('core_inmueble_vehiculos.deleted_at')
            ->select(
                'core_inmueble_vehiculos.*',
                'core_personas.per_nombres',
                'core_personas.per_apellidos',
                'core_personas.per_identificacion'
            )
            ->orderBy('core_inmueble_vehiculos.created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $vehiculos,
        ]);
    }

    /**
     * Registrar vehículo con Tag RFID / Sticker LPR para un Inmueble
     */
    public function registrarVehiculo(Request $request, int $id): JsonResponse
    {
        $inmueble = Inmueble::findOrFail($id);

        $validated = $request->validate([
            'placa' => 'required|string|max:15',
            'marca' => 'nullable|string|max:60',
            'modelo' => 'nullable|string|max:60',
            'color' => 'nullable|string|max:40',
            'tipo' => 'required|in:AUTOMOVIL,SUV,MOTOCICLETA,CAMIONETA',
            'tag_rfid' => 'nullable|string|max:64',
            'sticker_lpr' => 'nullable|string|max:64',
            'persona_id' => 'nullable|exists:core_personas,per_id',
            'observaciones' => 'nullable|string',
        ]);

        $vehId = DB::table('core_inmueble_vehiculos')->insertGetId([
            'veh_inmueble_id' => $inmueble->inm_id,
            'veh_persona_id' => $validated['persona_id'] ?? null,
            'veh_placa' => strtoupper($validated['placa']),
            'veh_marca' => $validated['marca'] ?? null,
            'veh_modelo' => $validated['modelo'] ?? null,
            'veh_color' => $validated['color'] ?? null,
            'veh_tipo' => $validated['tipo'],
            'veh_tag_rfid' => !empty($validated['tag_rfid']) ? trim($validated['tag_rfid']) : null,
            'veh_sticker_lpr' => !empty($validated['sticker_lpr']) ? trim($validated['sticker_lpr']) : null,
            'veh_es_autorizado' => true,
            'veh_activo' => true,
            'veh_observaciones' => $validated['observaciones'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'veh_id');

        \App\Services\AuditLogger::log('RESIDENT_VEHICLE_ADDED', 'core_inmueble_vehiculos', (string)$vehId, [
            'inmueble' => "{$inmueble->inm_manzana_bloque} - {$inmueble->inm_numero_villa}",
            'placa' => strtoupper($validated['placa']),
            'tag_rfid' => $validated['tag_rfid'] ?? null
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vehículo y Tag RFID registrados exitosamente.',
            'id' => $vehId,
        ]);
    }

    /**
     * Eliminar vehículo asignado
     */
    public function removerVehiculo(int $inmuebleId, int $vehiculoId): JsonResponse
    {
        DB::table('core_inmueble_vehiculos')
            ->where('veh_inmueble_id', $inmuebleId)
            ->where('veh_id', $vehiculoId)
            ->update([
                'veh_activo' => false,
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);

        \App\Services\AuditLogger::log('RESIDENT_VEHICLE_REMOVED', 'core_inmueble_vehiculos', (string)$vehiculoId, []);

        return response()->json([
            'success' => true,
            'message' => 'Vehículo retirado del padrón.',
        ]);
    }

    /**
     * Listar obligaciones / alícuotas del inmueble
     */
    public function listarObligaciones(int $id): JsonResponse
    {
        $obligaciones = DB::table('fin_obligaciones_alicuotas')
            ->where('obl_inmueble_id', $id)
            ->whereNull('deleted_at')
            ->orderBy('obl_periodo', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $obligaciones,
        ]);
    }

    /**
     * Registrar nueva obligación / alícuota para el inmueble
     */
    public function registrarObligacion(Request $request, int $id): JsonResponse
    {
        $inmueble = Inmueble::findOrFail($id);

        $validated = $request->validate([
            'concepto' => 'required|string|max:120',
            'periodo' => 'required|string|max:7',
            'monto' => 'required|numeric|min:0',
            'fecha_vencimiento' => 'required|date',
            'observaciones' => 'nullable|string',
        ]);

        $oblId = DB::table('fin_obligaciones_alicuotas')->insertGetId([
            'obl_inmueble_id' => $inmueble->inm_id,
            'obl_periodo' => $validated['periodo'],
            'obl_concepto' => $validated['concepto'],
            'obl_monto' => $validated['monto'],
            'obl_fecha_emision' => now()->toDateString(),
            'obl_fecha_vencimiento' => $validated['fecha_vencimiento'],
            'obl_estado' => 'PENDIENTE',
            'obl_observaciones' => $validated['observaciones'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'obl_id');

        return response()->json([
            'success' => true,
            'message' => 'Obligación / Alícuota registrada exitosamente.',
            'id' => $oblId,
        ], 201);
    }

    /**
     * Registrar pago de una obligación / alícuota
     */
    public function registrarPagoObligacion(Request $request, int $inmuebleId, int $obligacionId): JsonResponse
    {
        $validated = $request->validate([
            'metodo_pago' => 'required|string|in:TRANSFERENCIA,EFECTIVO,TARJETA,DEPOSITO',
            'comprobante_ref' => 'nullable|string|max:100',
        ]);

        DB::table('fin_obligaciones_alicuotas')
            ->where('obl_inmueble_id', $inmuebleId)
            ->where('obl_id', $obligacionId)
            ->update([
                'obl_estado' => 'PAGADO',
                'obl_fecha_pago' => now(),
                'obl_metodo_pago' => $validated['metodo_pago'],
                'obl_comprobante_ref' => $validated['comprobante_ref'] ?? null,
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Pago de alícuota registrado exitosamente.',
        ]);
    }

}
