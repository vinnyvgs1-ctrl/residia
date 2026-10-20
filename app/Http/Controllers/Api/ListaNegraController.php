<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Core\Persona;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ListaNegraController extends Controller
{
    /**
     * Listar personas y placas vetadas en la urbanización
     */
    public function index(Request $request): JsonResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');

        $vetados = DB::table('op_lista_negra')
            ->leftJoin('core_personas', 'op_lista_negra.lis_persona_id', '=', 'core_personas.per_id')
            ->leftJoin('sec_users', 'op_lista_negra.lis_registrado_por_usr_id', '=', 'sec_users.usr_id')
            ->where('lis_urbanizacion_id', $urbanizacionId)
            ->whereNull('op_lista_negra.deleted_at')
            ->where('lis_activo', true)
            ->select(
                'op_lista_negra.*',
                'core_personas.per_identificacion',
                'core_personas.per_nombres',
                'core_personas.per_apellidos',
                'sec_users.usr_name as registrado_por'
            )
            ->orderBy('op_lista_negra.created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $vetados,
        ]);
    }

    /**
     * Verificar en tiempo real si una identificación o placa tiene veto/alerta
     */
    public function verificarVeto(Request $request): JsonResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');
        $identificacion = trim((string) $request->input('identificacion'));
        $placa = strtoupper(trim((string) $request->input('placa')));

        $vetoPersona = null;
        $vetoPlaca = null;

        if ($identificacion !== '') {
            $persona = Persona::where('per_identificacion', $identificacion)->first();
            if ($persona && $persona->per_es_vetado) {
                $vetoPersona = [
                    'tipo' => 'PERSONA',
                    'identificacion' => $persona->per_identificacion,
                    'nombre' => "{$persona->per_nombres} {$persona->per_apellidos}",
                    'motivo' => $persona->per_motivo_veto ?: 'Restricción activa en padrón general',
                ];
            } else if ($persona) {
                $enLista = DB::table('op_lista_negra')
                    ->where('lis_urbanizacion_id', $urbanizacionId)
                    ->where('lis_persona_id', $persona->per_id)
                    ->where('lis_activo', true)
                    ->whereNull('deleted_at')
                    ->first();

                if ($enLista) {
                    $vetoPersona = [
                        'tipo' => 'PERSONA',
                        'identificacion' => $persona->per_identificacion,
                        'nombre' => "{$persona->per_nombres} {$persona->per_apellidos}",
                        'motivo' => $enLista->lis_motivo,
                    ];
                }
            }
        }

        if ($placa !== '') {
            $enListaPlaca = DB::table('op_lista_negra')
                ->where('lis_urbanizacion_id', $urbanizacionId)
                ->where('lis_placa_vehiculo', $placa)
                ->where('lis_activo', true)
                ->whereNull('deleted_at')
                ->first();

            if ($enListaPlaca) {
                $vetoPlaca = [
                    'tipo' => 'VEHICULO',
                    'placa' => $enListaPlaca->lis_placa_vehiculo,
                    'motivo' => $enListaPlaca->lis_motivo,
                ];
            }
        }

        $tieneVeto = (bool) ($vetoPersona || $vetoPlaca);

        return response()->json([
            'success' => true,
            'vetado' => $tieneVeto,
            'alerta' => $vetoPersona ?: $vetoPlaca,
            'veto_persona' => $vetoPersona,
            'veto_placa' => $vetoPlaca,
        ]);
    }

    /**
     * Registrar un nuevo veto en lista negra
     */
    public function store(Request $request): JsonResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');
        $user = auth('api')->user();

        $validated = $request->validate([
            'identificacion' => 'nullable|string',
            'nombres' => 'nullable|string',
            'apellidos' => 'nullable|string',
            'placa_vehiculo' => 'nullable|string',
            'motivo' => 'required|string',
        ]);

        if (empty($validated['identificacion']) && empty($validated['placa_vehiculo'])) {
            return response()->json([
                'success' => false,
                'message' => 'Debe ingresar al menos una identificación de persona o placa vehicular'
            ], 422);
        }

        $personaId = null;
        if (!empty($validated['identificacion'])) {
            $persona = Persona::updateOrCreate(
                ['per_identificacion' => $validated['identificacion']],
                [
                    'per_nombres' => $validated['nombres'] ?? 'No especificado',
                    'per_apellidos' => $validated['apellidos'] ?? 'No especificado',
                    'per_es_vetado' => true,
                    'per_motivo_veto' => $validated['motivo'],
                ]
            );
            $personaId = $persona->per_id;
        }

        $id = DB::table('op_lista_negra')->insertGetId([
            'lis_urbanizacion_id' => $urbanizacionId,
            'lis_persona_id' => $personaId,
            'lis_placa_vehiculo' => !empty($validated['placa_vehiculo']) ? strtoupper($validated['placa_vehiculo']) : null,
            'lis_motivo' => $validated['motivo'],
            'lis_registrado_por_usr_id' => $user ? $user->usr_id : null,
            'lis_activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'lis_id');

        AuditLogger::log('BLACKLIST_ENTRY_CREATED', 'op_lista_negra', (string)$id, [
            'identificacion' => $validated['identificacion'] ?? null,
            'placa' => $validated['placa_vehiculo'] ?? null,
            'motivo' => $validated['motivo']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Veto registrado exitosamente en Lista Negra.',
            'id' => $id,
        ]);
    }

    /**
     * Levantar / Inactivar un veto
     */
    public function destroy(int $id): JsonResponse
    {
        $registro = DB::table('op_lista_negra')->where('lis_id', $id)->first();
        if (!$registro) {
            return response()->json(['success' => false, 'message' => 'Registro no encontrado'], 404);
        }

        DB::table('op_lista_negra')->where('lis_id', $id)->update([
            'lis_activo' => false,
            'deleted_at' => now(),
            'updated_at' => now(),
        ]);

        if ($registro->lis_persona_id) {
            Persona::where('per_id', $registro->lis_persona_id)->update([
                'per_es_vetado' => false,
                'per_motivo_veto' => null,
            ]);
        }

        AuditLogger::log('BLACKLIST_ENTRY_REVOKED', 'op_lista_negra', (string)$id, []);

        return response()->json([
            'success' => true,
            'message' => 'Veto revocado exitosamente.',
        ]);
    }
}
