<?php

namespace App\Models\Operations;

use App\Models\Core\Urbanizacion;
use App\Models\Security\User;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NovedadTurno extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'op_novedades_turno';
    protected $primaryKey = 'nov_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'nov_id',
        'nov_urbanizacion_id',
        'nov_guardia_usr_id',
        'nov_garita_id',
        'nov_tipo',
        'nov_titulo',
        'nov_detalle',
        'nov_prioridad',
        'nov_fecha_hora',
        'nov_resuelta',
    ];

    protected $casts = [
        'nov_fecha_hora' => 'datetime',
        'nov_resuelta' => 'boolean',
    ];

    public function urbanizacion()
    {
        return $this->belongsTo(Urbanizacion::class, 'nov_urbanizacion_id', 'urb_id');
    }

    public function guardia()
    {
        return $this->belongsTo(User::class, 'nov_guardia_usr_id', 'usr_id');
    }

    public function garita()
    {
        return $this->belongsTo(Garita::class, 'nov_garita_id', 'gar_id');
    }
}
