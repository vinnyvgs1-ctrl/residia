<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Core\Persona;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PersonaController extends Controller
{
    /**
     * Listar personas registradas en el padrón con filtros y paginación
     */
    public function index(Request $request): JsonResponse
    {
        $query = Persona::query()->whereNull('deleted_at')->orderBy('per_nombres', 'asc');

        if ($request->filled('buscar')) {
            $b = '%' . strtolower($request->buscar) . '%';
            $query->where(function ($q) use ($b) {
                $q->whereRaw('LOWER(per_identificacion) LIKE ?', [$b])
                  ->orWhereRaw('LOWER(per_nombres) LIKE ?', [$b])
                  ->orWhereRaw('LOWER(per_apellidos) LIKE ?', [$b])
                  ->orWhereRaw('LOWER(per_telefono) LIKE ?', [$b])
                  ->orWhereRaw('LOWER(per_email) LIKE ?', [$b]);
            });
        }

        if ($request->filled('vetado')) {
            $query->where('per_es_vetado', filter_var($request->vetado, FILTER_VALIDATE_BOOLEAN));
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate(25)
        ]);
    }

    /**
     * Buscar una persona específica por cédula o pasaporte para autocompletar en garita
     */
    public function buscar(Request $request): JsonResponse
    {
        $identificacion = trim((string) $request->input('identificacion'));
        if ($identificacion === '') {
            return response()->json(['success' => false, 'message' => 'Identificación requerida'], 422);
        }

        $persona = Persona::where('per_identificacion', $identificacion)
            ->whereNull('deleted_at')
            ->first();

        // Si existe la persona, buscar si tiene un vehículo o destino habitual en la urbanización
        $ultimoAcceso = null;
        if ($persona) {
            $ultimoAcceso = \Illuminate\Support\Facades\DB::table('op_control_accesos')
                ->where('acc_persona_id', $persona->per_id)
                ->orderBy('acc_fecha_ingreso', 'desc')
                ->first(['acc_vehiculo_placa', 'acc_vehiculo_marca', 'acc_vehiculo_color', 'acc_inmueble_id', 'acc_rol_visita']);
        }

        return response()->json([
            'success' => true,
            'encontrado' => (bool) $persona,
            'persona' => $persona,
            'ultimo_acceso' => $ultimoAcceso
        ]);
    }

    /**
     * Registrar manualmente una persona en el padrón
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_identificacion' => 'required|string|max:30|unique:core_personas,per_identificacion',
            'per_tipo_identificacion' => 'required|in:CEDULA,RUC,PASAPORTE',
            'per_nombres' => 'required|string|max:100',
            'per_apellidos' => 'required|string|max:100',
            'per_telefono' => 'nullable|string|max:20',
            'per_email' => 'nullable|email|max:150',
            'per_es_vetado' => 'boolean',
            'per_motivo_veto' => 'nullable|string|max:255',
        ]);

        $persona = Persona::create($validated);

        AuditLogger::log('PERSONA_CREATED', 'core_personas', $persona->per_id, [
            'identificacion' => $persona->per_identificacion,
            'nombre' => "{$persona->per_nombres} {$persona->per_apellidos}"
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Persona registrada en el padrón único exitosamente.',
            'data' => $persona
        ], 201);
    }

    /**
     * Actualizar datos de una persona
     */
    public function update(Request $request, $id): JsonResponse
    {
        $persona = Persona::findOrFail($id);

        $validated = $request->validate([
            'per_identificacion' => 'sometimes|required|string|max:30|unique:core_personas,per_identificacion,' . $id . ',per_id',
            'per_tipo_identificacion' => 'sometimes|required|in:CEDULA,RUC,PASAPORTE',
            'per_nombres' => 'sometimes|required|string|max:100',
            'per_apellidos' => 'sometimes|required|string|max:100',
            'per_telefono' => 'nullable|string|max:20',
            'per_email' => 'nullable|email|max:150',
            'per_es_vetado' => 'boolean',
            'per_motivo_veto' => 'nullable|string|max:255',
        ]);

        $persona->update($validated);

        AuditLogger::log('PERSONA_UPDATED', 'core_personas', $id, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Datos actualizados exitosamente.',
            'data' => $persona
        ]);
    }

    /**
     * Eliminar (soft delete) persona del padrón
     */
    public function destroy($id): JsonResponse
    {
        $persona = Persona::findOrFail($id);
        $persona->delete();

        AuditLogger::log('PERSONA_DELETED', 'core_personas', $id, [
            'identificacion' => $persona->per_identificacion
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Persona eliminada del padrón.'
        ]);
    }
}
