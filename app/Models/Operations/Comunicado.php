<?php

namespace App\Models\Operations;

use App\Models\Core\Urbanizacion;
use App\Models\Security\User;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comunicado extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'op_comunicados';
    protected $primaryKey = 'com_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'com_id',
        'com_urbanizacion_id',
        'com_autor_usr_id',
        'com_titulo',
        'com_contenido',
        'com_categoria',
        'com_prioridad',
        'com_fecha_publicacion',
        'com_fecha_expiracion',
        'com_fijado',
        'com_activo',
    ];

    protected $casts = [
        'com_fecha_publicacion' => 'date',
        'com_fecha_expiracion' => 'date',
        'com_fijado' => 'boolean',
        'com_activo' => 'boolean',
    ];

    public function urbanizacion()
    {
        return $this->belongsTo(Urbanizacion::class, 'com_urbanizacion_id', 'urb_id');
    }

    public function autor()
    {
        return $this->belongsTo(User::class, 'com_autor_usr_id', 'usr_id');
    }
}
