<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Operations\Garita;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GaritaController extends Controller
{
    /**
     * Listar garitas / puestos de guardia de la urbanización activa
     */
    public function index(Request $request): JsonResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');

        $garitas = Garita::where('gar_urbanizacion_id', $urbanizacionId)
            ->orderBy('gar_nombre', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $garitas
        ]);
    }

    /**
     * Crear un nuevo puesto de garita en la urbanización
     */
    public function store(Request $request): JsonResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');

        $validated = $request->validate([
            'gar_nombre' => 'required|string|max:100',
            'gar_ubicacion' => 'nullable|string|max:200',
            'gar_permite_vehicular' => 'boolean',
            'gar_permite_peatonal' => 'boolean',
            'gar_activo' => 'boolean',
        ]);

        $validated['gar_urbanizacion_id'] = $urbanizacionId;
        $validated['gar_permite_vehicular'] = $validated['gar_permite_vehicular'] ?? true;
        $validated['gar_permite_peatonal'] = $validated['gar_permite_peatonal'] ?? true;
        $validated['gar_activo'] = $validated['gar_activo'] ?? true;

        $garita = Garita::create($validated);

        AuditLogger::log('GARITA_CREATED', 'op_garitas', $garita->gar_id, [
            'nombre' => $garita->gar_nombre,
            'urbanizacion_id' => $urbanizacionId
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Puesto de garita creado exitosamente.',
            'data' => $garita
        ], 201);
    }

    /**
     * Actualizar configuración de un puesto de garita
     */
    public function update(Request $request, $id): JsonResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');

        $garita = Garita::where('gar_urbanizacion_id', $urbanizacionId)
            ->findOrFail($id);

        $validated = $request->validate([
            'gar_nombre' => 'sometimes|required|string|max:100',
            'gar_ubicacion' => 'nullable|string|max:200',
            'gar_permite_vehicular' => 'boolean',
            'gar_permite_peatonal' => 'boolean',
            'gar_activo' => 'boolean',
        ]);

        $garita->update($validated);

        AuditLogger::log('GARITA_UPDATED', 'op_garitas', $garita->gar_id, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Puesto de garita actualizado exitosamente.',
            'data' => $garita
        ]);
    }

    /**
     * Eliminar (soft delete) puesto de garita
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');

        $garita = Garita::where('gar_urbanizacion_id', $urbanizacionId)
            ->findOrFail($id);

        $garita->delete();

        AuditLogger::log('GARITA_DELETED', 'op_garitas', $id, [
            'nombre' => $garita->gar_nombre
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Puesto de garita eliminado exitosamente.'
        ]);
    }
}
