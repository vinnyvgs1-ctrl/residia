<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Operations\ControlAcceso;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteController extends Controller
{
    /**
     * Reporte Dinámico de Tráfico y Accesos con Auditoría de Impresión/Exportación
     */
    public function reporteAccesos(Request $request): JsonResponse
    {
        $urbanizacionId = $request->header('X-Urbanizacion-Id') 
            ?: (session('active_urbanizacion_id') ?: $request->input('urbanizacion_id'));

        $query = ControlAcceso::with(['persona', 'inmueble', 'guardiaIngreso', 'guardiaSalida'])
            ->where('acc_urbanizacion_id', $urbanizacionId);

        if ($request->filled('estado')) {
            $query->where('acc_estado', $request->input('estado'));
        }

        if ($request->filled('tipo_ingreso')) {
            $query->where('acc_tipo_ingreso', $request->input('tipo_ingreso'));
        }

        if ($request->filled('placa')) {
            $query->where('acc_vehiculo_placa', 'ilike', '%' . trim($request->input('placa')) . '%');
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('acc_fecha_ingreso', '>=', $request->input('fecha_desde'));
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('acc_fecha_ingreso', '<=', $request->input('fecha_hasta'));
        }

        $accesos = $query->orderBy('acc_fecha_ingreso', 'desc')->get();

        return response()->json([
            'success' => true,
            'urbanizacion_id' => $urbanizacionId,
            'total_registros' => $accesos->count(),
            'filtros_aplicados' => $request->all(),
            'data' => $accesos,
        ]);
    }

    /**
     * Descarga de Reporte en Excel (CSV con cabecera formal y codificación UTF-8 BOM)
     */
    public function exportarExcel(Request $request): StreamedResponse
    {
        $urbanizacionId = $request->header('X-Urbanizacion-Id') 
            ?: (session('active_urbanizacion_id') ?: $request->input('urbanizacion_id'));

        $query = ControlAcceso::with(['persona', 'inmueble', 'guardiaIngreso'])
            ->where('acc_urbanizacion_id', $urbanizacionId);

        if ($request->filled('estado')) $query->where('acc_estado', $request->input('estado'));
        if ($request->filled('tipo_ingreso')) $query->where('acc_tipo_ingreso', $request->input('tipo_ingreso'));
        if ($request->filled('placa')) $query->where('acc_vehiculo_placa', 'ilike', '%' . trim($request->input('placa')) . '%');
        if ($request->filled('fecha_desde')) $query->whereDate('acc_fecha_ingreso', '>=', $request->input('fecha_desde'));
        if ($request->filled('fecha_hasta')) $query->whereDate('acc_fecha_ingreso', '<=', $request->input('fecha_hasta'));

        $accesos = $query->orderBy('acc_fecha_ingreso', 'desc')->get();
        $fileName = "reporte_accesos_" . date('Ymd_His') . ".csv";

        // Auditoría obligatoria de Print / Export
        AuditLogger::logReportExport(
            modulo: 'accesos',
            formato: 'EXCEL',
            nombreArchivo: $fileName,
            filtros: $request->all(),
            totalFilas: $accesos->count(),
            urbanizacionId: $urbanizacionId
        );

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($accesos) {
            $handle = fopen('php://output', 'w');
            
            // UTF-8 BOM para apertura perfecta en Microsoft Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Encabezado Institucional
            fputcsv($handle, ['ROBLEDAL CONNECTING - SISTEMA DE CONTROL DE ACCESOS RESIDIA']);
            fputcsv($handle, ['Reporte Oficial de Tráfico y Permanencia en Garita']);
            fputcsv($handle, ['Fecha de Emisión: ' . date('Y-m-d H:i:s'), 'Total Registros: ' . $accesos->count()]);
            fputcsv($handle, []);

            // Cabecera de columnas
            fputcsv($handle, [
                'ID Acceso',
                'Identificación',
                'Visitante / Habitante',
                'Tipo Ingreso',
                'Placa Vehicular',
                'Ocupantes',
                'Rol Visita',
                'Destino (Inmueble)',
                'Fecha y Hora Ingreso',
                'Fecha y Hora Salida',
                'Duración (Minutos)',
                'Estado',
                'Observaciones'
            ]);

            foreach ($accesos as $a) {
                $nombre = $a->persona ? "{$a->persona->per_nombres} {$a->persona->per_apellidos}" : 'No registrado';
                $identificacion = $a->persona ? $a->persona->per_identificacion : '---';
                $destino = $a->inmueble ? "{$a->inmueble->inm_manzana_bloque} - {$a->inmueble->inm_numero_villa}" : 'Área Comunal';

                fputcsv($handle, [
                    $a->acc_id,
                    $identificacion,
                    $nombre,
                    $a->acc_tipo_ingreso,
                    $a->acc_vehiculo_placa ?: 'N/A',
                    $a->acc_cantidad_ocupantes,
                    $a->acc_rol_visita,
                    $destino,
                    $a->acc_fecha_ingreso,
                    $a->acc_fecha_salida ?: 'EN CURSO',
                    $a->acc_duracion_minutos ?: '0',
                    $a->acc_estado,
                    $a->acc_observaciones ?: ''
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

        /**
     * Resumen Estadístico Completo para Dashboard Ejecutivo
     */
    public function metricasDashboard(Request $request): JsonResponse
    {
        $urbanizacionId = $request->header('X-Urbanizacion-Id') 
            ?: (session('active_urbanizacion_id') ?: $request->input('urbanizacion_id'));

        $hoy = now()->toDateString();

        // 1. Métricas de Garita
        $ingresosHoy = ControlAcceso::where('acc_urbanizacion_id', $urbanizacionId)
            ->whereDate('acc_fecha_ingreso', $hoy)
            ->count();

        $vehicularesHoy = ControlAcceso::where('acc_urbanizacion_id', $urbanizacionId)
            ->whereDate('acc_fecha_ingreso', $hoy)
            ->where('acc_tipo_ingreso', 'VEHICULAR')
            ->count();

        $peatonalesHoy = ControlAcceso::where('acc_urbanizacion_id', $urbanizacionId)
            ->whereDate('acc_fecha_ingreso', $hoy)
            ->where('acc_tipo_ingreso', 'PEATONAL')
            ->count();

        $dentroActual = ControlAcceso::where('acc_urbanizacion_id', $urbanizacionId)
            ->where('acc_estado', 'DENTRO')
            ->count();

        // 2. Tiempos de permanencia excedidos (visitas con más de 4 horas dentro)
        $hace4Horas = now()->subHours(4);
        $alertasExcedidas = ControlAcceso::where('acc_urbanizacion_id', $urbanizacionId)
            ->where('acc_estado', 'DENTRO')
            ->where('acc_fecha_ingreso', '<', $hace4Horas)
            ->count();

        // 3. Pases QR vigentes
        $pasesVigentes = \Illuminate\Support\Facades\DB::table('op_invitaciones_qr')
            ->where('inv_urbanizacion_id', $urbanizacionId)
            ->where('inv_estado', 'ACTIVO')
            ->where('inv_valido_hasta', '>=', now())
            ->whereNull('deleted_at')
            ->count();

        // 4. Inmuebles y Residentes
        $inmueblesTotal = \App\Models\Core\Inmueble::where('inm_urbanizacion_id', $urbanizacionId)->count();
        $residentesTotal = \Illuminate\Support\Facades\DB::table('core_inmueble_residentes')
            ->join('core_inmuebles', 'core_inmueble_residentes.res_inmueble_id', '=', 'core_inmuebles.inm_id')
            ->where('core_inmuebles.inm_urbanizacion_id', $urbanizacionId)
            ->where('core_inmueble_residentes.res_activo', true)
            ->count();

        // 5. Restricciones en Lista Negra
        $vetadosTotal = \Illuminate\Support\Facades\DB::table('op_lista_negra')
            ->where('lis_urbanizacion_id', $urbanizacionId)
            ->where('lis_activo', true)
            ->whereNull('deleted_at')
            ->count();

        // 6. Flujo de Accesos por Intervalos Horarios de Hoy
        $distribucionHoras = [];
        for ($h = 6; $h <= 22; $h += 2) {
            $horaInicio = sprintf("%s %02d:00:00", $hoy, $h);
            $horaFin = sprintf("%s %02d:59:59", $hoy, $h + 1);
            $cnt = ControlAcceso::where('acc_urbanizacion_id', $urbanizacionId)
                ->whereBetween('acc_fecha_ingreso', [$horaInicio, $horaFin])
                ->count();

            $distribucionHoras[] = [
                'intervalo' => sprintf("%02d:00 - %02d:00", $h, $h + 2),
                'total' => $cnt
            ];
        }

        return response()->json([
            'success' => true,
            'metricas' => [
                'ingresos_hoy' => $ingresosHoy,
                'vehiculares_hoy' => $vehicularesHoy,
                'peatonales_hoy' => $peatonalesHoy,
                'dentro_actual' => $dentroActual,
                'alertas_excedidas' => $alertasExcedidas,
                'pases_vigentes' => $pasesVigentes,
                'inmuebles_total' => $inmueblesTotal,
                'residentes_total' => $residentesTotal,
                'vetados_total' => $vetadosTotal,
                'distribucion_horas' => $distribucionHoras
            ]
        ]);
    }
}