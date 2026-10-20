<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Operations\Paqueteria;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaqueteriaController extends Controller
{
    /**
     * Listar paquetes recibidos en garita
     */
    public function index(Request $request): JsonResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');

        $query = Paqueteria::with([
            'inmueble:inm_id,inm_manzana_bloque,inm_numero_villa',
            'guardiaRecibe:usr_id,usr_name',
            'guardiaEntrega:usr_id,usr_name'
        ])
        ->where('paq_urbanizacion_id', $urbanizacionId)
        ->orderBy('created_at', 'desc');

        if ($request->filled('estado') && $request->estado !== 'TODOS') {
            $query->where('paq_estado', $request->estado);
        }

        if ($request->filled('inmueble_id')) {
            $query->where('paq_inmueble_id', $request->inmueble_id);
        }

        if ($request->filled('buscar')) {
            $b = '%' . strtolower($request->buscar) . '%';
            $query->where(function ($q) use ($b) {
                $q->whereRaw('LOWER(paq_codigo_guia) LIKE ?', [$b])
                  ->orWhereRaw('LOWER(paq_empresa_courier) LIKE ?', [$b])
                  ->orWhereRaw('LOWER(paq_destinatario_nombre) LIKE ?', [$b]);
            });
        }

        return response()->json([
            'success' => true,
            'data' => $query->get()
        ]);
    }

    /**
     * Registrar recepción de un paquete en garita
     */
    public function store(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');

        $validated = $request->validate([
            'inmueble_id' => 'required|exists:core_inmuebles,inm_id',
            'empresa_courier' => 'required|string|max:100',
            'codigo_guia' => 'nullable|string|max:100',
            'destinatario_nombre' => 'nullable|string|max:150',
            'descripcion' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string',
        ]);

        $uuid = (string) Str::uuid();

        $paquete = Paqueteria::create([
            'paq_id' => $uuid,
            'paq_urbanizacion_id' => $urbanizacionId,
            'paq_inmueble_id' => $validated['inmueble_id'],
            'paq_guardia_recibe_usr_id' => $user->usr_id,
            'paq_empresa_courier' => $validated['empresa_courier'],
            'paq_codigo_guia' => $validated['codigo_guia'] ?? null,
            'paq_destinatario_nombre' => $validated['destinatario_nombre'] ?? null,
            'paq_descripcion' => $validated['descripcion'] ?? null,
            'paq_estado' => 'EN_GARITA',
            'paq_fecha_recepcion' => now(),
            'paq_observaciones' => $validated['observaciones'] ?? null,
        ]);

        AuditLogger::log('PACKAGE_RECEIVED', 'op_paqueteria', $uuid, [
            'courier' => $paquete->paq_empresa_courier,
            'guia' => $paquete->paq_codigo_guia,
            'inmueble_id' => $paquete->paq_inmueble_id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Paquete recepcionado exitosamente en garita.',
            'data' => $paquete->load('inmueble', 'guardiaRecibe:usr_id,usr_name')
        ], 201);
    }

    /**
     * Registrar la entrega / retiro de un paquete por el residente
     */
    public function marcarEntregado(Request $request, $id): JsonResponse
    {
        $user = auth('api')->user();
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');

        $paquete = Paqueteria::where('paq_urbanizacion_id', $urbanizacionId)->findOrFail($id);

        if ($paquete->paq_estado === 'ENTREGADO') {
            return response()->json(['success' => false, 'message' => 'El paquete ya fue entregado previamente.'], 422);
        }

        $validated = $request->validate([
            'retirado_por' => 'required|string|max:150',
            'observaciones' => 'nullable|string',
        ]);

        $paquete->update([
            'paq_estado' => 'ENTREGADO',
            'paq_guardia_entrega_usr_id' => $user->usr_id,
            'paq_fecha_entrega' => now(),
            'paq_retirado_por' => $validated['retirado_por'],
            'paq_observaciones' => $validated['observaciones'] 
                ? ($paquete->paq_observaciones . "\nRetiro: " . $validated['observaciones']) 
                : $paquete->paq_observaciones,
        ]);

        AuditLogger::log('PACKAGE_DELIVERED', 'op_paqueteria', $id, [
            'retirado_por' => $validated['retirado_por'],
            'guardia_entrega' => $user->usr_name
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Paquete entregado y firmado digitalmente.',
            'data' => $paquete->load('inmueble', 'guardiaEntrega:usr_id,usr_name')
        ]);
    }
}
