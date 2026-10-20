<?php

namespace App\Models\Core;

use App\Models\Operations\ControlAcceso;
use App\Models\Operations\Garita;
use App\Models\Security\Tema;
use App\Models\Security\User;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Urbanizacion extends Model
{
    use HasFactory, HasUuids, SoftDeletes, Auditable;

    protected $table = 'core_urbanizaciones';
    protected $primaryKey = 'urb_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'urb_id',
        'urb_codigo',
        'urb_nombre',
        'urb_ruc',
        'urb_direccion',
        'urb_ciudad',
        'urb_telefono',
        'urb_tema_id',
        'urb_configuracion',
        'urb_activo',
    ];

    protected $casts = [
        'urb_configuracion' => 'array',
        'urb_activo' => 'boolean',
    ];

    public function tema()
    {
        return $this->belongsTo(Tema::class, 'urb_tema_id', 'tem_id');
    }

    public function inmuebles()
    {
        return $this->hasMany(Inmueble::class, 'inm_urbanizacion_id', 'urb_id');
    }

    public function garitas()
    {
        return $this->hasMany(Garita::class, 'gar_urbanizacion_id', 'urb_id');
    }

    public function usuarios()
    {
        return $this->belongsToMany(User::class, 'sec_urbanizacion_usuarios', 'uxu_urbanizacion_id', 'uxu_user_id');
    }
}
