<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Core\Urbanizacion;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UrbanizacionController extends Controller
{
    /**
     * Listado de Urbanizaciones
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user() ?: auth()->user();

        $query = Urbanizacion::with('tema')
            ->withCount(['inmuebles', 'garitas']);

        if (!$user || !$user->usr_es_superadmin) {
            $userUrbIds = $user ? $user->urbanizaciones()->pluck('core_urbanizaciones.urb_id')->toArray() : [];
            $query->whereIn('urb_id', $userUrbIds);
        }

        if ($request->filled('buscar')) {
            $b = trim($request->input('buscar'));
            $query->where(function ($q) use ($b) {
                $q->where('urb_nombre', 'ilike', "%{$b}%")
                  ->orWhere('urb_codigo', 'ilike', "%{$b}%")
                  ->orWhere('urb_ciudad', 'ilike', "%{$b}%");
            });
        }

        $urbanizaciones = $query->orderBy('urb_nombre')->get();

        return response()->json([
            'success' => true,
            'total' => $urbanizaciones->count(),
            'data' => $urbanizaciones,
        ]);
    }

    /**
     * Crear una nueva Urbanización
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'urb_codigo' => 'required|string|max:30|unique:core_urbanizaciones,urb_codigo',
            'urb_nombre' => 'required|string|max:150',
            'urb_ruc' => 'nullable|string|max:20',
            'urb_direccion' => 'required|string|max:255',
            'urb_ciudad' => 'required|string|max:100',
            'urb_telefono' => 'nullable|string|max:30',
            'urb_tema_id' => 'nullable|exists:sec_temas,tem_id',
            'urb_configuracion' => 'nullable|array',
        ]);

        $validated['urb_id'] = (string) Str::uuid();
        $validated['urb_activo'] = true;

        $urbanizacion = Urbanizacion::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Urbanización creada exitosamente.',
            'data' => $urbanizacion->load('tema'),
        ], 201);
    }

    /**
     * Ver detalle de una Urbanización
     */
    public function show(string $id): JsonResponse
    {
        $urbanizacion = Urbanizacion::with(['tema', 'inmuebles', 'garitas'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $urbanizacion,
        ]);
    }

    /**
     * Actualizar configuración o datos de la Urbanización
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $urbanizacion = Urbanizacion::findOrFail($id);

        $validated = $request->validate([
            'urb_nombre' => 'sometimes|required|string|max:150',
            'urb_ruc' => 'nullable|string|max:20',
            'urb_direccion' => 'sometimes|required|string|max:255',
            'urb_ciudad' => 'sometimes|required|string|max:100',
            'urb_telefono' => 'nullable|string|max:30',
            'urb_tema_id' => 'nullable|exists:sec_temas,tem_id',
            'urb_configuracion' => 'nullable|array',
            'urb_activo' => 'sometimes|boolean',
        ]);

        $urbanizacion->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Urbanización actualizada exitosamente.',
            'data' => $urbanizacion->fresh(['tema']),
        ]);
    }

    /**
     * Eliminación lógica (Soft Delete)
     */
    public function destroy(string $id): JsonResponse
    {
        $urbanizacion = Urbanizacion::findOrFail($id);
        $urbanizacion->delete();

        return response()->json([
            'success' => true,
            'message' => 'Urbanización desactivada / eliminada correctamente.',
        ]);
    }
}
