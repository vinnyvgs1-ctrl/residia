<?php

namespace App\Models\Operations;

use App\Models\Core\Inmueble;
use App\Models\Core\Persona;
use App\Models\Core\Urbanizacion;
use App\Models\Security\User;
use App\Traits\Auditable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ControlAcceso extends Model
{
    use HasFactory, Auditable;

    protected $table = 'op_control_accesos';
    protected $primaryKey = 'acc_id';

    protected $fillable = [
        'acc_urbanizacion_id',
        'acc_persona_id',
        'acc_inmueble_id',
        'acc_tipo_ingreso',
        'acc_rol_visita',
        'acc_empresa',
        'acc_vehiculo_placa',
        'acc_vehiculo_marca',
        'acc_vehiculo_modelo',
        'acc_vehiculo_color',
        'acc_cantidad_ocupantes',
        'acc_ocupantes_detalle',
        'acc_garita_ingreso_id',
        'acc_guardia_ingreso_usr_id',
        'acc_fecha_ingreso',
        'acc_garita_salida_id',
        'acc_guardia_salida_usr_id',
        'acc_fecha_salida',
        'acc_duracion_minutos',
        'acc_estado',
        'acc_tiempo_excedido',
        'acc_observaciones',
    ];

    protected $casts = [
        'acc_ocupantes_detalle' => 'array',
        'acc_fecha_ingreso' => 'datetime',
        'acc_fecha_salida' => 'datetime',
        'acc_tiempo_excedido' => 'boolean',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'acc_persona_id', 'per_id');
    }

    public function inmueble()
    {
        return $this->belongsTo(Inmueble::class, 'acc_inmueble_id', 'inm_id');
    }

    public function urbanizacion()
    {
        return $this->belongsTo(Urbanizacion::class, 'acc_urbanizacion_id', 'urb_id');
    }

    public function guardiaIngreso()
    {
        return $this->belongsTo(User::class, 'acc_guardia_ingreso_usr_id', 'usr_id');
    }

    public function guardiaSalida()
    {
        return $this->belongsTo(User::class, 'acc_guardia_salida_usr_id', 'usr_id');
    }

    public function garitaIngreso()
    {
        return $this->belongsTo(Garita::class, 'acc_garita_ingreso_id', 'gar_id');
    }

    public function garitaSalida()
    {
        return $this->belongsTo(Garita::class, 'acc_garita_salida_id', 'gar_id');
    }

    /**
     * Marca la salida del vehículo o persona y calcula automáticamente la duración
     */
    public function registrarSalida(int $garitaSalidaId, int $guardiaSalidaUsrId, ?string $observaciones = null): void
    {
        $salida = now();
        $duracion = (int) round(Carbon::parse($this->acc_fecha_ingreso)->diffInMinutes($salida));

        $this->update([
            'acc_fecha_salida' => $salida,
            'acc_garita_salida_id' => $garitaSalidaId,
            'acc_guardia_salida_usr_id' => $guardiaSalidaUsrId,
            'acc_duracion_minutos' => $duracion,
            'acc_estado' => 'SALIO',
            'acc_observaciones' => $observaciones ? "{$this->acc_observaciones}\n{$observaciones}" : $this->acc_observaciones,
        ]);
    }
}
