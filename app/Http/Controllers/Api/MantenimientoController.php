<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MantenimientoController extends Controller
{
    /**
     * Dashboard y listado integral de órdenes de trabajo, activos y contratistas
     */
    public function index(Request $request): JsonResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');

        // 1. Obtener órdenes de trabajo
        $ordenes = DB::table('op_ordenes_mantenimiento')
            ->leftJoin('op_activos_infraestructura', 'op_ordenes_mantenimiento.ord_activo_id', '=', 'op_activos_infraestructura.act_id')
            ->leftJoin('op_proveedores', 'op_ordenes_mantenimiento.ord_proveedor_id', '=', 'op_proveedores.prv_id')
            ->where('op_ordenes_mantenimiento.ord_urbanizacion_id', $urbanizacionId)
            ->whereNull('op_ordenes_mantenimiento.deleted_at')
            ->select(
                'op_ordenes_mantenimiento.*',
                'op_activos_infraestructura.act_nombre as activo_nombre',
                'op_activos_infraestructura.act_codigo as activo_codigo',
                'op_activos_infraestructura.act_ubicacion as activo_ubicacion',
                'op_proveedores.prv_razon_social as proveedor_nombre',
                'op_proveedores.prv_telefono as proveedor_telefono'
            )
            ->orderBy('op_ordenes_mantenimiento.ord_fecha_programada', 'desc')
            ->get();

        // 2. Obtener activos críticos
        $activos = DB::table('op_activos_infraestructura')
            ->where('act_urbanizacion_id', $urbanizacionId)
            ->whereNull('deleted_at')
            ->orderBy('act_nombre')
            ->get();

        // 3. Obtener proveedores / contratistas
        $proveedores = DB::table('op_proveedores')
            ->where('prv_urbanizacion_id', $urbanizacionId)
            ->whereNull('deleted_at')
            ->orderBy('prv_razon_social')
            ->get();

        // Seed demo inicial si está vacío
        if ($activos->isEmpty()) {
            $act1 = DB::table('op_activos_infraestructura')->insertGetId([
                'act_urbanizacion_id' => $urbanizacionId,
                'act_codigo' => 'BOMB-01',
                'act_nombre' => 'Sistema Hidroneumático de Bombeo Principal',
                'act_ubicacion' => 'Cuarto de Máquinas Central',
                'act_categoria' => 'HIDRONEUMATICO',
                'act_marca_modelo' => 'Grundfos Hydro MPC-E',
                'act_fecha_instalacion' => now()->subYears(2),
                'act_frecuencia_dias_mantenimiento' => 90,
                'act_ultimo_mantenimiento' => now()->subDays(75),
                'act_proximo_mantenimiento' => now()->addDays(15),
                'act_estado_operativo' => 'OPERATIVO',
                'created_at' => now(),
                'updated_at' => now(),
            ], 'act_id');

            $act2 = DB::table('op_activos_infraestructura')->insertGetId([
                'act_urbanizacion_id' => $urbanizacionId,
                'act_codigo' => 'GEN-01',
                'act_nombre' => 'Generador Eléctrico Diésel de Emergencia 150 kVA',
                'act_ubicacion' => 'Subestación Eléctrica Garita Norte',
                'act_categoria' => 'GENERADOR',
                'act_marca_modelo' => 'Cummins C150D5',
                'act_fecha_instalacion' => now()->subYear(),
                'act_frecuencia_dias_mantenimiento' => 60,
                'act_ultimo_mantenimiento' => now()->subDays(65),
                'act_proximo_mantenimiento' => now()->subDays(5),
                'act_estado_operativo' => 'REQUIERE_SERVICIO',
                'created_at' => now(),
                'updated_at' => now(),
            ], 'act_id');

            $prvId = DB::table('op_proveedores')->insertGetId([
                'prv_urbanizacion_id' => $urbanizacionId,
                'prv_razon_social' => 'HidroElectro Soluciones S.A.',
                'prv_ruc_identificacion' => '1792345678001',
                'prv_categoria' => 'BOMBAS_AGUA',
                'prv_contacto_nombre' => 'Ing. Carlos Mendoza',
                'prv_telefono' => '0998765432',
                'prv_email' => 'soporte@hidroelectro.com.ec',
                'prv_direccion' => 'Av. Galo Plaza Lasso N45-12',
                'created_at' => now(),
                'updated_at' => now(),
            ], 'prv_id');

            DB::table('op_ordenes_mantenimiento')->insert([
                'ord_urbanizacion_id' => $urbanizacionId,
                'ord_codigo_folio' => 'MNT-2026-0001',
                'ord_activo_id' => $act2,
                'ord_proveedor_id' => $prvId,
                'ord_tipo' => 'PREVENTIVO',
                'ord_titulo' => 'Cambio de Filtros y Mantenimiento Bimensual de Generador',
                'ord_descripcion' => 'Reemplazo de aceite sintético 15W40, filtros de combustible diésel y prueba en vacío de transferencia automática ATS.',
                'ord_prioridad' => 'ALTA',
                'ord_estado' => 'EN_PROCESO',
                'ord_fecha_programada' => now(),
                'ord_fecha_inicio' => now(),
                'ord_costo_estimado' => 450.00,
                'ord_costo_real' => 420.00,
                'ord_responsable_supervision' => 'Administración Robledal',
                'ord_pre_autoriza_garita' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $this->index($request);
        }

        // KPIs
        $totalOrdenes = $ordenes->count();
        $ordenesPendientes = $ordenes->whereIn('ord_estado', ['PENDIENTE', 'EN_PROCESO'])->count();
        $costoTotalMes = (float) $ordenes->sum('ord_costo_real');
        $activosAlerta = $activos->where('act_estado_operativo', '!=', 'OPERATIVO')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'ordenes' => $ordenes,
                'activos' => $activos,
                'proveedores' => $proveedores,
                'kpis' => [
                    'total_ordenes' => $totalOrdenes,
                    'ordenes_pendientes' => $ordenesPendientes,
                    'costo_total_mes' => $costoTotalMes,
                    'activos_alerta' => $activosAlerta,
                    'total_activos' => $activos->count(),
                    'total_proveedores' => $proveedores->count()
                ]
            ]
        ]);
    }

    /**
     * Crear una nueva Orden de Trabajo
     */
    public function storeOrden(Request $request): JsonResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');

        $validated = $request->validate([
            'titulo' => 'required|string|max:150',
            'descripcion' => 'required|string',
            'activo_id' => 'nullable|exists:op_activos_infraestructura,act_id',
            'proveedor_id' => 'nullable|exists:op_proveedores,prv_id',
            'tipo' => 'required|in:PREVENTIVO,CORRECTIVO,EMERGENCIA',
            'prioridad' => 'required|in:BAJA,MEDIA,ALTA,CRITICA',
            'fecha_programada' => 'required|date',
            'costo_estimado' => 'nullable|numeric|min:0',
            'responsable_supervision' => 'nullable|string|max:100',
            'pre_autoriza_garita' => 'boolean'
        ]);

        $year = date('Y');
        $count = DB::table('op_ordenes_mantenimiento')
            ->where('ord_urbanizacion_id', $urbanizacionId)
            ->count() + 1;
        $folio = sprintf('MNT-%s-%04d', $year, $count);

        $ordId = DB::table('op_ordenes_mantenimiento')->insertGetId([
            'ord_urbanizacion_id' => $urbanizacionId,
            'ord_codigo_folio' => $folio,
            'ord_activo_id' => $validated['activo_id'] ?? null,
            'ord_proveedor_id' => $validated['proveedor_id'] ?? null,
            'ord_tipo' => $validated['tipo'],
            'ord_titulo' => $validated['titulo'],
            'ord_descripcion' => $validated['descripcion'],
            'ord_prioridad' => $validated['prioridad'],
            'ord_estado' => 'PENDIENTE',
            'ord_fecha_programada' => $validated['fecha_programada'],
            'ord_costo_estimado' => $validated['costo_estimado'] ?? 0.00,
            'ord_costo_real' => 0.00,
            'ord_responsable_supervision' => $validated['responsable_supervision'] ?? null,
            'ord_pre_autoriza_garita' => $validated['pre_autoriza_garita'] ?? true,
            'ord_solicitante_usr_id' => auth()->id() ?: 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'ord_id');

        AuditLogger::log('ORDEN_MANTENIMIENTO_CREADA', 'op_ordenes_mantenimiento', $ordId, $validated);

        return response()->json([
            'success' => true,
            'message' => "Orden de trabajo {$folio} programada exitosamente.",
            'ord_id' => $ordId,
            'ord_codigo_folio' => $folio
        ]);
    }

    /**
     * Actualizar estado o finalizar Orden de Trabajo
     */
    public function actualizarEstadoOrden(Request $request, int $id): JsonResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');

        $validated = $request->validate([
            'estado' => 'required|in:PENDIENTE,EN_PROCESO,COMPLETADA,CANCELADA',
            'costo_real' => 'nullable|numeric|min:0',
            'informe_tecnico' => 'nullable|string'
        ]);

        $orden = DB::table('op_ordenes_mantenimiento')
            ->where('ord_id', $id)
            ->where('ord_urbanizacion_id', $urbanizacionId)
            ->first();

        if (!$orden) {
            return response()->json(['success' => false, 'message' => 'Orden no encontrada.'], 404);
        }

        $updates = [
            'ord_estado' => $validated['estado'],
            'updated_at' => now()
        ];

        if (isset($validated['costo_real'])) {
            $updates['ord_costo_real'] = $validated['costo_real'];
        }
        if (isset($validated['informe_tecnico'])) {
            $updates['ord_informe_tecnico'] = $validated['informe_tecnico'];
        }

        if ($validated['estado'] === 'EN_PROCESO' && !$orden->ord_fecha_inicio) {
            $updates['ord_fecha_inicio'] = now();
        }

        if ($validated['estado'] === 'COMPLETADA') {
            $updates['ord_fecha_finalizacion'] = now();

            // Si tiene activo vinculado, actualizar fechas de mantenimiento
            if ($orden->ord_activo_id) {
                $activo = DB::table('op_activos_infraestructura')->where('act_id', $orden->ord_activo_id)->first();
                if ($activo) {
                    $dias = $activo->act_frecuencia_dias_mantenimiento ?: 90;
                    DB::table('op_activos_infraestructura')
                        ->where('act_id', $orden->ord_activo_id)
                        ->update([
                            'act_ultimo_mantenimiento' => now(),
                            'act_proximo_mantenimiento' => now()->addDays($dias),
                            'act_estado_operativo' => 'OPERATIVO',
                            'updated_at' => now()
                        ]);
                }
            }
        }

        DB::table('op_ordenes_mantenimiento')
            ->where('ord_id', $id)
            ->update($updates);

        AuditLogger::log('ORDEN_MANTENIMIENTO_ESTADO_ACTUALIZADO', 'op_ordenes_mantenimiento', $id, $updates);

        return response()->json([
            'success' => true,
            'message' => 'Estado de la orden de trabajo actualizado exitosamente.'
        ]);
    }

    /**
     * Registrar un nuevo Activo de Infraestructura
     */
    public function storeActivo(Request $request): JsonResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');

        $validated = $request->validate([
            'codigo' => 'required|string|max:50',
            'nombre' => 'required|string|max:150',
            'ubicacion' => 'required|string|max:150',
            'categoria' => 'required|string|max:50',
            'marca_modelo' => 'nullable|string|max:100',
            'frecuencia_dias' => 'required|integer|min:1',
            'estado_operativo' => 'required|in:OPERATIVO,REQUIERE_SERVICIO,FUERA_DE_SERVICIO'
        ]);

        $actId = DB::table('op_activos_infraestructura')->insertGetId([
            'act_urbanizacion_id' => $urbanizacionId,
            'act_codigo' => strtoupper($validated['codigo']),
            'act_nombre' => $validated['nombre'],
            'act_ubicacion' => $validated['ubicacion'],
            'act_categoria' => $validated['categoria'],
            'act_marca_modelo' => $validated['marca_modelo'] ?? null,
            'act_frecuencia_dias_mantenimiento' => $validated['frecuencia_dias'],
            'act_estado_operativo' => $validated['estado_operativo'],
            'act_ultimo_mantenimiento' => now(),
            'act_proximo_mantenimiento' => now()->addDays($validated['frecuencia_dias']),
            'act_activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'act_id');

        AuditLogger::log('ACTIVO_INFRAESTRUCTURA_REGISTRADO', 'op_activos_infraestructura', $actId, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Activo de infraestructura registrado exitosamente.',
            'act_id' => $actId
        ]);
    }

    /**
     * Registrar un nuevo Proveedor / Contratista
     */
    public function storeProveedor(Request $request): JsonResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');

        $validated = $request->validate([
            'razon_social' => 'required|string|max:150',
            'ruc_identificacion' => 'nullable|string|max:30',
            'categoria' => 'required|string|max:50',
            'contacto_nombre' => 'nullable|string|max:100',
            'telefono' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'direccion' => 'nullable|string'
        ]);

        $prvId = DB::table('op_proveedores')->insertGetId([
            'prv_urbanizacion_id' => $urbanizacionId,
            'prv_razon_social' => $validated['razon_social'],
            'prv_ruc_identificacion' => $validated['ruc_identificacion'] ?? null,
            'prv_categoria' => $validated['categoria'],
            'prv_contacto_nombre' => $validated['contacto_nombre'] ?? null,
            'prv_telefono' => $validated['telefono'] ?? null,
            'prv_email' => $validated['email'] ?? null,
            'prv_direccion' => $validated['direccion'] ?? null,
            'prv_activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'prv_id');

        AuditLogger::log('PROVEEDOR_MANTENIMIENTO_REGISTRADO', 'op_proveedores', $prvId, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Proveedor registrado exitosamente en el catálogo.',
            'prv_id' => $prvId
        ]);
    }
}
