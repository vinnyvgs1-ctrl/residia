<?php

namespace App\Models\Security;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permiso extends Model
{
    use HasFactory;

    protected $table = 'sec_permisos';
    protected $primaryKey = 'prm_id';

    protected $fillable = [
        'prm_modulo',
        'prm_accion',
        'prm_slug',
        'prm_descripcion',
    ];

    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'sec_rol_permisos', 'rxp_permiso_id', 'rxp_rol_id');
    }
}
