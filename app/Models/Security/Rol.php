<?php

namespace App\Models\Security;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rol extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'sec_roles';
    protected $primaryKey = 'rol_id';

    protected $fillable = [
        'rol_codigo',
        'rol_nombre',
        'rol_descripcion',
        'rol_es_sistema',
        'rol_activo',
    ];

    protected $casts = [
        'rol_es_sistema' => 'boolean',
        'rol_activo' => 'boolean',
    ];

    public function permisos()
    {
        return $this->belongsToMany(Permiso::class, 'sec_rol_permisos', 'rxp_rol_id', 'rxp_permiso_id');
    }
}
