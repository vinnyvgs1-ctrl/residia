<?php

use App\Models\Core\Inmueble;
use App\Models\Core\Persona;
use App\Models\Core\Urbanizacion;
use App\Models\Operations\ControlAcceso;
use App\Models\Security\Tema;
use App\Models\Security\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas API Públicas / Protegidas por API Shielding (Residia CRM)
|--------------------------------------------------------------------------
*/

// Endpoint de prueba de salud de API Shielding
Route::get('/health', function () {
    return response()->json([
        'status' => 'OK',
        'platform' => 'Residia CRM (Robledal Connecting)',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Endpoint público para obtener temas de Robledal (con variables CSS)
Route::get('/temas', function () {
    $temas = Tema::where('tem_activo', true)->get();
    return response()->json([
        'success' => true,
        'data' => $temas,
    ]);
});

// -------------------------------------------------------------
// Rutas de Autenticación JWT Híbrida (Email O Cédula/Identificación)
// -------------------------------------------------------------
Route::prefix('auth')->group(function () {
    Route::post('/login', function (Request $request) {
        // Blindaje Anti-Fuerza Bruta: máximo 5 intentos erróneos por minuto por IP
        $throttleKey = 'login_attempt:' . $request->ip();
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($throttleKey);
            return response()->json([
                'success' => false,
                'error' => 'TOO_MANY_ATTEMPTS',
                'message' => "Demasiados intentos fallidos. Bloqueo temporal activo. Intente en {$seconds} segundos."
            ], 429);
        }

        $loginInput = trim((string) ($request->input('login') ?: $request->input('email')));
        $password = (string) $request->input('password');

        if ($loginInput === '' || $password === '') {
            return response()->json([
                'success' => false,
                'error' => 'MISSING_DATA',
                'message' => 'Por favor ingrese su correo o identificación y contraseña.'
            ], 422);
        }

        // Buscar el usuario por Email O por Cédula/Pasaporte en core_personas
        $user = User::where('usr_email', 'ilike', $loginInput)
            ->orWhereHas('persona', function ($q) use ($loginInput) {
                $q->where('per_identificacion', $loginInput);
            })
            ->first();

        if (! $user) {
            \Illuminate\Support\Facades\RateLimiter::hit($throttleKey, 60);
            return response()->json([
                'success' => false,
                'error' => 'INVALID_CREDENTIALS',
                'message' => 'Credenciales de acceso incorrectas (usuario no encontrado).'
            ], 401);
        }

        // Intentar autenticación JWT con el email oficial de la cuenta
        if (! $token = auth('api')->attempt(['usr_email' => $user->usr_email, 'password' => $password])) {
            return response()->json([
                'success' => false,
                'error' => 'INVALID_CREDENTIALS',
                'message' => 'Contraseña incorrecta.'
            ], 401);
        }

        if (! $user->usr_activo) {
            try { auth('api')->invalidate(true); } catch (\Throwable $e) {}
            auth('api')->logout();
            return response()->json([
                'success' => false,
                'error' => 'USER_INACTIVE',
                'message' => 'Cuenta suspendida o inactiva.'
            ], 403);
        }

        \Illuminate\Support\Facades\RateLimiter::clear($throttleKey);
        \Illuminate\Support\Facades\RateLimiter::clear($throttleKey);
        return response()->json([
            'success' => true,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => [
                'id' => $user->usr_id,
                'name' => $user->usr_name,
                'email' => $user->usr_email,
                'identificacion' => $user->persona?->per_identificacion,
                'es_superadmin' => $user->usr_es_superadmin,
                'preferencias' => $user->usr_preferencias,
            ]
        ]);
    });

    Route::middleware('auth:api')->group(function () {
        Route::get('/me', function () {
            return response()->json([
                'success' => true,
                'user' => auth('api')->user()->load('persona', 'urbanizaciones'),
            ]);
        });

        Route::post('/logout', function () {
            try { auth('api')->invalidate(true); } catch (\Throwable $e) {}
            auth('api')->logout();
            return response()->json(['success' => true, 'message' => 'Sesión cerrada exitosamente']);
        });

        // Actualizar Perfil del Usuario Autenticado
        Route::put('/perfil', function (Request $request) {
            $user = auth('api')->user();
            $validated = $request->validate([
                'name' => 'required|string|max:100',
                'email' => 'required|email|unique:sec_users,usr_email,' . $user->usr_id . ',usr_id',
                'telefono' => 'nullable|string|max:25',
            ]);

            $user->usr_name = $validated['name'];
            $user->usr_email = $validated['email'];
            $user->save();

            if ($user->persona) {
                if (isset($validated['telefono'])) {
                    $user->persona->per_telefono = $validated['telefono'];
                }
                $user->persona->per_email = $validated['email'];
                $user->persona->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Perfil actualizado exitosamente.',
                'user' => [
                    'id' => $user->usr_id,
                    'name' => $user->usr_name,
                    'email' => $user->usr_email,
                    'telefono' => $user->persona?->per_telefono,
                    'identificacion' => $user->persona?->per_identificacion,
                    'es_superadmin' => $user->usr_es_superadmin,
                ]
            ]);
        });

        // Cambiar Contraseña del Usuario Autenticado
        Route::put('/cambiar-password', function (Request $request) {
            $user = auth('api')->user();
            $validated = $request->validate([
                'password_actual' => 'required|string',
                'nuevo_password' => 'required|string|min:6|confirmed',
            ]);

            if (!\Illuminate\Support\Facades\Hash::check($validated['password_actual'], $user->usr_password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La contraseña actual ingresada es incorrecta.'
                ], 422);
            }

            $user->usr_password = \Illuminate\Support\Facades\Hash::make($validated['nuevo_password']);
            $user->save();

            \App\Services\AuditLogger::log('PASSWORD_CHANGED', 'sec_users', (string)$user->usr_id, [
                'email' => $user->usr_email
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contraseña modificada exitosamente.'
            ]);
        });

        Route::post('/refresh', function () {
            return response()->json([
                'success' => true,
                'access_token' => auth('api')->refresh(),
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
            ]);
        });
    });
});

// Rutas protegidas por API Shielding (HMAC + Nonces en Redis)
Route::middleware(['api.shield', 'urbanizacion.scope'])->group(function () {
    // -------------------------------------------------------------
    // Portal Móvil de Residentes / Copropietarios
    // -------------------------------------------------------------
    Route::get('/residente/mi-hogar', [\App\Http\Controllers\Api\PortalResidenteController::class, 'miHogar']);
    Route::post('/residente/pases-qr', [\App\Http\Controllers\Api\PortalResidenteController::class, 'crearPaseVisita']);

    // Búsqueda Inteligente de Vehículo Residente por Placa o Tag RFID
    Route::get('/accesos/buscar-vehiculo-residente', function (\Illuminate\Http\Request $request) {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');
        $placa = strtoupper(trim((string) $request->input('placa')));
        $tagRfid = trim((string) $request->input('tag_rfid'));

        if ($placa === '' && $tagRfid === '') {
            return response()->json(['success' => false, 'encontrado' => false]);
        }

        $query = \Illuminate\Support\Facades\DB::table('core_inmueble_vehiculos')
            ->join('core_inmuebles', 'core_inmueble_vehiculos.veh_inmueble_id', '=', 'core_inmuebles.inm_id')
            ->leftJoin('core_personas', 'core_inmueble_vehiculos.veh_persona_id', '=', 'core_personas.per_id')
            ->where('core_inmuebles.inm_urbanizacion_id', $urbanizacionId)
            ->where('core_inmueble_vehiculos.veh_activo', true)
            ->whereNull('core_inmueble_vehiculos.deleted_at');

        if ($tagRfid !== '') {
            $query->where('core_inmueble_vehiculos.veh_tag_rfid', $tagRfid);
        } else {
            $query->where('core_inmueble_vehiculos.veh_placa', $placa);
        }

        $vehiculo = $query->select(
            'core_inmueble_vehiculos.*',
            'core_inmuebles.inm_id',
            'core_inmuebles.inm_manzana_bloque',
            'core_inmuebles.inm_numero_villa',
            'core_personas.per_id',
            'core_personas.per_identificacion',
            'core_personas.per_nombres',
            'core_personas.per_apellidos'
        )->first();

        if ($vehiculo) {
            $cuotasPendientes = \Illuminate\Support\Facades\DB::table('fin_obligaciones_alicuotas')
                ->where('obl_inmueble_id', $vehiculo->inm_id)
                ->whereIn('obl_estado', ['PENDIENTE', 'VENCIDO'])
                ->whereNull('deleted_at')
                ->count();
            
            $deudaTotal = (float) \Illuminate\Support\Facades\DB::table('fin_obligaciones_alicuotas')
                ->where('obl_inmueble_id', $vehiculo->inm_id)
                ->whereIn('obl_estado', ['PENDIENTE', 'VENCIDO'])
                ->whereNull('deleted_at')
                ->sum('obl_monto');

            $vehiculo->cuotas_pendientes = $cuotasPendientes;
            $vehiculo->deuda_total = $deudaTotal;
            $vehiculo->es_moroso = $cuotasPendientes > 0;

            return response()->json([
                'success' => true,
                'encontrado' => true,
                'vehiculo' => $vehiculo
            ]);
        }

        return response()->json([
            'success' => true,
            'encontrado' => false
        ]);
    });

    Route::get('/dashboard/metricas', [\App\Http\Controllers\Api\ReporteController::class, 'metricasDashboard']);

    // -------------------------------------------------------------
    // Control de Lista Negra & Veto Inteligente
    // -------------------------------------------------------------
    Route::get('/lista-negra', [\App\Http\Controllers\Api\ListaNegraController::class, 'index']);
    Route::get('/lista-negra/verificar-veto', [\App\Http\Controllers\Api\ListaNegraController::class, 'verificarVeto']);
    Route::post('/lista-negra', [\App\Http\Controllers\Api\ListaNegraController::class, 'store']);
    Route::delete('/lista-negra/{id}', [\App\Http\Controllers\Api\ListaNegraController::class, 'destroy']);

    // -------------------------------------------------------------
    // Pases y Pre-Autorizaciones de Visitas con QR
    // -------------------------------------------------------------
    Route::get('/invitaciones', [\App\Http\Controllers\Api\InvitacionController::class, 'index']);
    Route::post('/invitaciones', [\App\Http\Controllers\Api\InvitacionController::class, 'store']);
    Route::post('/invitaciones/canjear-qr', [\App\Http\Controllers\Api\InvitacionController::class, 'validarYCanjear']);


    // 1. Padrón Rápido de Personas para Garita (con autocompletado inteligente de vehículo y destino habitual)
    Route::get('/personas/buscar', [\App\Http\Controllers\Api\PersonaController::class, 'buscar']);
    Route::get('/accesos/buscar-persona', [\App\Http\Controllers\Api\PersonaController::class, 'buscar']);
    
    // 2. Registro de Ingreso a Garita (Vehicular o Peatonal)
    Route::post('/accesos/ingreso', function (Request $request) {
        $validated = $request->validate([
            'persona_identificacion' => 'required|string',
            'persona_nombres' => 'required|string',
            'persona_apellidos' => 'required|string',
            'tipo_ingreso' => 'required|in:PEATONAL,VEHICULAR',
            'inmueble_id' => 'nullable|exists:core_inmuebles,inm_id',
            'rol_visita' => 'nullable|string',
            'vehiculo_placa' => 'nullable|string',
            'vehiculo_marca' => 'nullable|string',
            'vehiculo_color' => 'nullable|string',
            'cantidad_ocupantes' => 'nullable|integer|min:1',
            'ocupantes_detalle' => 'nullable|array',
            'observaciones' => 'nullable|string',
        ]);

        $persona = Persona::updateOrCreate(
            ['per_identificacion' => $validated['persona_identificacion']],
            [
                'per_nombres' => $validated['persona_nombres'],
                'per_apellidos' => $validated['persona_apellidos'],
            ]
        );

        if ($persona->per_es_vetado) {
            return response()->json([
                'success' => false,
                'error' => 'ACCESO_DENEGADO_VETO',
                'message' => "ALERTA ROJA: Persona con ingreso vetado. Motivo: {$persona->per_motivo_veto}"
            ], 403);
        }

        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');

        $acceso = ControlAcceso::create([
            'acc_urbanizacion_id' => $urbanizacionId,
            'acc_persona_id' => $persona->per_id,
            'acc_inmueble_id' => $validated['inmueble_id'] ?? null,
            'acc_tipo_ingreso' => $validated['tipo_ingreso'],
            'acc_rol_visita' => $validated['rol_visita'] ?? 'VISITA',
            'acc_vehiculo_placa' => $validated['vehiculo_placa'] ? strtoupper(trim($validated['vehiculo_placa'])) : null,
            'acc_vehiculo_marca' => $validated['vehiculo_marca'] ?? null,
            'acc_vehiculo_color' => $validated['vehiculo_color'] ?? null,
            'acc_cantidad_ocupantes' => $validated['cantidad_ocupantes'] ?? 1,
            'acc_ocupantes_detalle' => $validated['ocupantes_detalle'] ?? null,
            'acc_garita_ingreso_id' => $request->input('garita_ingreso_id') ?: 1,
            'acc_guardia_ingreso_usr_id' => auth()->id() ?: 1,
            'acc_fecha_ingreso' => now(),
            'acc_estado' => 'DENTRO',
            'acc_observaciones' => $validated['observaciones'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ingreso registrado exitosamente.',
            'data' => $acceso->load('persona', 'inmueble'),
        ], 201);
    });

    // 3. Listado de Visitas y Vehículos Actualmente Dentro (Semáforo & Reglas de Permanencia)
    Route::get('/accesos/activos-dentro', function (Request $request) {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');

        $urb = \Illuminate\Support\Facades\DB::table('core_urbanizaciones')
            ->where('urb_id', $urbanizacionId)
            ->first(['urb_configuracion', 'urb_nombre']);

        $config = $urb && $urb->urb_configuracion ? json_decode($urb->urb_configuracion, true) : [];
        $tiempoMaximoMinutos = (int) ($config['tiempo_maximo_visita_minutos'] ?? 240);
        $toleranciaMinutos = (int) ($config['tolerancia_salida_minutos'] ?? 15);

        $activos = ControlAcceso::with(['persona', 'inmueble'])
            ->where('acc_urbanizacion_id', $urbanizacionId)
            ->where('acc_estado', 'DENTRO')
            ->orderBy('acc_fecha_ingreso', 'desc')
            ->get();

        $ahora = now();
        $totalExcedidos = 0;

        $activosFormateados = $activos->map(function ($acc) use ($tiempoMaximoMinutos, $toleranciaMinutos, $ahora, &$totalExcedidos) {
            $ingreso = \Carbon\Carbon::parse($acc->acc_fecha_ingreso); $minutosDentro = max(0, (int) $ingreso->diffInMinutes($ahora, false));
            $minutosDentro = (int) $ingreso->diffInMinutes($ahora);
            $esVisitaOProveedor = in_array($acc->acc_rol_visita, ['VISITANTE', 'PROVEEDOR', 'DELIVERY', 'EMPLEADO']);
            $tiempoLimite = $tiempoMaximoMinutos + $toleranciaMinutos;
            $excedido = $esVisitaOProveedor && ($minutosDentro > $tiempoLimite);

            if ($excedido) {
                $totalExcedidos++;
            }

            $arr = $acc->toArray();
            $arr['minutos_dentro'] = $minutosDentro;
            $arr['tiempo_maximo_permitido'] = $tiempoMaximoMinutos;
            $arr['tiempo_excedido_alerta'] = $excedido;
            $arr['exceso_minutos'] = $excedido ? ($minutosDentro - $tiempoLimite) : 0;
            return $arr;
        });

        return response()->json([
            'success' => true,
            'total_dentro' => $activos->count(),
            'total_excedidos' => $totalExcedidos,
            'reglas' => [
                'tiempo_maximo_visita_minutos' => $tiempoMaximoMinutos,
                'tolerancia_salida_minutos' => $toleranciaMinutos,
                'limite_alerta_minutos' => $tiempoMaximoMinutos + $toleranciaMinutos
            ],
            'data' => $activosFormateados,
        ]);
    });
    // 4. Registro de Salida de Garita
    Route::post('/accesos/{id}/salida', function (Request $request, $id) {
        $acceso = ControlAcceso::findOrFail($id);

        if ($acceso->acc_estado === 'SALIO') {
            return response()->json(['success' => false, 'message' => 'Este acceso ya tiene salida registrada'], 422);
        }

        $guardiaId = auth()->id() ?: 1;
        $acceso->registrarSalida(
            garitaSalidaId: (int) ($request->input('garita_salida_id') ?? 1),
            guardiaSalidaUsrId: (int) $guardiaId,
            observaciones: $request->input('observaciones')
        );

        return response()->json([
            'success' => true,
            'message' => 'Salida registrada exitosamente.',
            'duracion_minutos' => $acceso->acc_duracion_minutos,
            'data' => $acceso,
        ]);
    });
});

// -------------------------------------------------------------
// Rutas de Gestión de Urbanizaciones y Reportes (JWT + Tenant Scope)
// -------------------------------------------------------------
Route::middleware(['auth:api', 'urbanizacion.scope'])->group(function () {
    Route::apiResource('urbanizaciones', \App\Http\Controllers\Api\UrbanizacionController::class);
    Route::apiResource('personas', \App\Http\Controllers\Api\PersonaController::class);
    Route::apiResource('garitas', \App\Http\Controllers\Api\GaritaController::class);
    Route::apiResource('comunicados', \App\Http\Controllers\Api\ComunicadoController::class);
    
    // Módulo de Paquetería & Cuaderno Digital de Novedades
    Route::get('/paqueteria', [\App\Http\Controllers\Api\PaqueteriaController::class, 'index']);
    Route::post('/paqueteria', [\App\Http\Controllers\Api\PaqueteriaController::class, 'store']);
    Route::post('/paqueteria/{id}/entregar', [\App\Http\Controllers\Api\PaqueteriaController::class, 'marcarEntregado']);
    
    Route::get('/novedades', [\App\Http\Controllers\Api\NovedadController::class, 'index']);
    Route::post('/novedades', [\App\Http\Controllers\Api\NovedadController::class, 'store']);
    Route::post('/novedades/{id}/resolver', [\App\Http\Controllers\Api\NovedadController::class, 'resolver']);
    
    // Módulo de Reportes con Permiso Granular de Impresión
    Route::get('/reportes/accesos', [\App\Http\Controllers\Api\ReporteController::class, 'reporteAccesos'])
        ->middleware('permiso:reportes.read');

    Route::get('/reportes/accesos/excel', [\App\Http\Controllers\Api\ReporteController::class, 'exportarExcel'])
        ->middleware('permiso:reportes,print');
    Route::post('/reportes/accesos/exportar', [\App\Http\Controllers\Api\ReporteController::class, 'reporteAccesos'])
        ->middleware('permiso:reportes.print');

    Route::get('/reportes/metricas', [\App\Http\Controllers\Api\ReporteController::class, 'metricasDashboard'])
        ->middleware('permiso:reportes.read');
});

// -------------------------------------------------------------
// Rutas de Gestión de Inmuebles y Residentes (Tenant Scoped)
// -------------------------------------------------------------
Route::middleware(['auth:api', 'urbanizacion.scope'])->group(function () {
    Route::apiResource('inmuebles', \App\Http\Controllers\Api\InmuebleController::class);
    Route::post('/inmuebles/{id}/residentes', [\App\Http\Controllers\Api\InmuebleController::class, 'asignarResidente']);
    // Gestión de Vehículos & Tags RFID de Inmuebles
    Route::get('/inmuebles/{id}/vehiculos', [\App\Http\Controllers\Api\InmuebleController::class, 'listarVehiculos']);
    Route::post('/inmuebles/{id}/vehiculos', [\App\Http\Controllers\Api\InmuebleController::class, 'registrarVehiculo']);
    Route::delete('/inmuebles/{id}/vehiculos/{vehId}', [\App\Http\Controllers\Api\InmuebleController::class, 'removerVehiculo']);
    Route::delete('/inmuebles/{id}/residentes/{perId}', [\App\Http\Controllers\Api\InmuebleController::class, 'removerResidente']);
    // Gestión Financiera de Alícuotas y Obligaciones
    Route::get('/inmuebles/{id}/obligaciones', [\App\Http\Controllers\Api\InmuebleController::class, 'listarObligaciones']);
    Route::post('/inmuebles/{id}/obligaciones', [\App\Http\Controllers\Api\InmuebleController::class, 'registrarObligacion']);
    Route::post('/inmuebles/{id}/obligaciones/{oblId}/pago', [\App\Http\Controllers\Api\InmuebleController::class, 'registrarPagoObligacion']);
    Route::delete('/inmuebles/{id}/vehiculos/{vehiculoId}', [\App\Http\Controllers\Api\InmuebleController::class, 'removerVehiculo']);
    Route::delete('/inmuebles/{inmuebleId}/residentes/{personaId}', [\App\Http\Controllers\Api\InmuebleController::class, 'removerResidente']);
});

// -------------------------------------------------------------
// Rutas del Módulo de Seguridad y Auditoría (RBAC & CDC)
// -------------------------------------------------------------
Route::middleware(['auth:api', 'urbanizacion.scope'])->group(function () {
    Route::get('/seguridad/usuarios', [\App\Http\Controllers\Api\SeguridadController::class, 'usuarios']);
    Route::post('/seguridad/usuarios', [\App\Http\Controllers\Api\SeguridadController::class, 'storeUsuario']);
    Route::put('/seguridad/usuarios/{id}', [\App\Http\Controllers\Api\SeguridadController::class, 'updateUsuario']);
    Route::delete('/seguridad/usuarios/{id}', [\App\Http\Controllers\Api\SeguridadController::class, 'destroyUsuario']);
    Route::get('/seguridad/roles', [\App\Http\Controllers\Api\SeguridadController::class, 'roles']);
    Route::put('/seguridad/roles/{id}/permisos', [\App\Http\Controllers\Api\SeguridadController::class, 'actualizarPermisosRol']);
    Route::get('/auditoria/logs', [\App\Http\Controllers\Api\SeguridadController::class, 'auditoriaLogs']);
});

// -------------------------------------------------------------
// Rutas de Administración de Vistas y Asignación a Roles
// -------------------------------------------------------------
Route::middleware(['auth:api', 'urbanizacion.scope'])->group(function () {
    Route::get('/seguridad/vistas', [\App\Http\Controllers\Api\VistaController::class, 'index']);
    Route::post('/seguridad/vistas', [\App\Http\Controllers\Api\VistaController::class, 'store']);
    Route::put('/seguridad/vistas/{id}', [\App\Http\Controllers\Api\VistaController::class, 'update']);
    Route::delete('/seguridad/vistas/{id}', [\App\Http\Controllers\Api\VistaController::class, 'destroy']);
    Route::get('/seguridad/mis-vistas', [\App\Http\Controllers\Api\VistaController::class, 'misVistas']);
    Route::get('/seguridad/roles/{id}/vistas', [\App\Http\Controllers\Api\VistaController::class, 'vistasPorRol']);
    Route::post('/seguridad/roles/{id}/vistas', [\App\Http\Controllers\Api\VistaController::class, 'asignarVistasRol']);
});

// -------------------------------------------------------------
// Rutas de Gestión Financiera Consolidada, Alícuotas y Tesorería
// -------------------------------------------------------------
Route::middleware(['auth:api', 'urbanizacion.scope'])->group(function () {
    Route::get('/alicuotas/resumen', [\App\Http\Controllers\Api\AlicuotaController::class, 'resumen']);
    Route::post('/alicuotas/emision-masiva', [\App\Http\Controllers\Api\AlicuotaController::class, 'emisionMasiva']);
    Route::get('/alicuotas/recibo/{id}', [\App\Http\Controllers\Api\AlicuotaController::class, 'recibo']);
});

// -------------------------------------------------------------
// Rutas de Notificaciones en Tiempo Real (SSE & Realtime Garita)
// -------------------------------------------------------------
Route::get('/notificaciones/stream', [\App\Http\Controllers\Api\NotificacionRealtimeController::class, 'stream']);
Route::middleware(['auth:api', 'urbanizacion.scope'])->group(function () {
    Route::get('/notificaciones/recientes', [\App\Http\Controllers\Api\NotificacionRealtimeController::class, 'recientes']);
});

// -------------------------------------------------------------
// Rutas de Integración de Hardware IoT & Control de Garita (LPR / RFID / Barreras)
// -------------------------------------------------------------
Route::middleware(['auth:api', 'urbanizacion.scope'])->group(function () {
    Route::get('/hardware/dispositivos', [\App\Http\Controllers\Api\DispositivoHardwareController::class, 'estadoDispositivos']);
    Route::post('/hardware/lpr-deteccion', [\App\Http\Controllers\Api\DispositivoHardwareController::class, 'webhookLpr']);
    Route::post('/hardware/rfid-deteccion', [\App\Http\Controllers\Api\DispositivoHardwareController::class, 'webhookRfid']);
    Route::post('/hardware/apertura-contingencia', [\App\Http\Controllers\Api\DispositivoHardwareController::class, 'aperturaContingencia']);
});

// -------------------------------------------------------------
// Rutas de Reservas de Áreas Sociales & Amenidades
// -------------------------------------------------------------
Route::middleware(['auth:api', 'urbanizacion.scope'])->group(function () {
    Route::get('/areas-sociales', [\App\Http\Controllers\Api\AreaSocialController::class, 'index']);
    Route::post('/areas-sociales', [\App\Http\Controllers\Api\AreaSocialController::class, 'store']);
    Route::get('/areas-sociales/reservas', [\App\Http\Controllers\Api\AreaSocialController::class, 'reservas']);
    Route::post('/areas-sociales/reservas', [\App\Http\Controllers\Api\AreaSocialController::class, 'crearReserva']);
    Route::post('/areas-sociales/reservas/{id}/cancelar', [\App\Http\Controllers\Api\AreaSocialController::class, 'cancelarReserva']);
});

// -------------------------------------------------------------
// Rutas de Comunicados Oficiales & Votaciones / Asambleas
// -------------------------------------------------------------
Route::middleware(['auth:api', 'urbanizacion.scope'])->group(function () {
    Route::get('/comunicados', [\App\Http\Controllers\Api\ComunicadoVotacionController::class, 'indexComunicados']);
    Route::post('/comunicados', [\App\Http\Controllers\Api\ComunicadoVotacionController::class, 'storeComunicado']);
    Route::get('/votaciones', [\App\Http\Controllers\Api\ComunicadoVotacionController::class, 'indexVotaciones']);
    Route::post('/votaciones', [\App\Http\Controllers\Api\ComunicadoVotacionController::class, 'storeVotacion']);
    Route::post('/votaciones/{id}/votar', [\App\Http\Controllers\Api\ComunicadoVotacionController::class, 'emitirVoto']);
});


// -------------------------------------------------------------
// Rutas de Sincronización Biométrica Hikvision ISAPI & iVMS-4200
// -------------------------------------------------------------
Route::middleware(['auth:api', 'urbanizacion.scope'])->group(function () {
    Route::get('/biometria', [\App\Http\Controllers\Api\BiometriaController::class, 'index']);
    Route::post('/biometria/sync-isapi', [\App\Http\Controllers\Api\BiometriaController::class, 'syncLoteHikvision']);
    Route::get('/biometria/export-ivms', [\App\Http\Controllers\Api\BiometriaController::class, 'exportarPlantillaIvms']);
    Route::post('/biometria/import-ivms', [\App\Http\Controllers\Api\BiometriaController::class, 'importarIvms']);
});

// -------------------------------------------------------------
// Rutas de PWA & Notificaciones Web Push
// -------------------------------------------------------------
Route::middleware(['auth:api', 'urbanizacion.scope'])->group(function () {
    Route::post('/push/suscribir', [\App\Http\Controllers\Api\PushNotificationController::class, 'suscribir']);
    Route::post('/push/enviar-prueba', [\App\Http\Controllers\Api\PushNotificationController::class, 'enviarNotificacionPrueba']);
    Route::get('/push/estado-pwa', [\App\Http\Controllers\Api\PushNotificationController::class, 'estadoPwa']);
});

// -------------------------------------------------------------
// Rutas de Mantenimiento Preventivo & Órdenes de Trabajo (Facility)
// -------------------------------------------------------------
Route::middleware(['auth:api', 'urbanizacion.scope'])->group(function () {
    Route::get('/mantenimiento', [\App\Http\Controllers\Api\MantenimientoController::class, 'index']);
    Route::post('/mantenimiento/ordenes', [\App\Http\Controllers\Api\MantenimientoController::class, 'storeOrden']);
    Route::post('/mantenimiento/ordenes/{id}/estado', [\App\Http\Controllers\Api\MantenimientoController::class, 'actualizarEstadoOrden']);
    Route::post('/mantenimiento/activos', [\App\Http\Controllers\Api\MantenimientoController::class, 'storeActivo']);
    Route::post('/mantenimiento/proveedores', [\App\Http\Controllers\Api\MantenimientoController::class, 'storeProveedor']);
});
