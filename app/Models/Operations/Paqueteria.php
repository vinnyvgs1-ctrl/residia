<?php

namespace App\Models\Operations;

use App\Models\Core\Inmueble;
use App\Models\Core\Urbanizacion;
use App\Models\Security\User;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Paqueteria extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'op_paqueteria';
    protected $primaryKey = 'paq_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'paq_id',
        'paq_urbanizacion_id',
        'paq_inmueble_id',
        'paq_guardia_recibe_usr_id',
        'paq_guardia_entrega_usr_id',
        'paq_empresa_courier',
        'paq_codigo_guia',
        'paq_destinatario_nombre',
        'paq_descripcion',
        'paq_estado',
        'paq_fecha_recepcion',
        'paq_fecha_entrega',
        'paq_retirado_por',
        'paq_observaciones',
    ];

    protected $casts = [
        'paq_fecha_recepcion' => 'datetime',
        'paq_fecha_entrega' => 'datetime',
    ];

    public function inmueble()
    {
        return $this->belongsTo(Inmueble::class, 'paq_inmueble_id', 'inm_id');
    }

    public function urbanizacion()
    {
        return $this->belongsTo(Urbanizacion::class, 'paq_urbanizacion_id', 'urb_id');
    }

    public function guardiaRecibe()
    {
        return $this->belongsTo(User::class, 'paq_guardia_recibe_usr_id', 'usr_id');
    }

    public function guardiaEntrega()
    {
        return $this->belongsTo(User::class, 'paq_guardia_entrega_usr_id', 'usr_id');
    }
}
