<?php

namespace App\Models\Operations;

use App\Models\Core\Urbanizacion;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Garita extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'op_garitas';
    protected $primaryKey = 'gar_id';

    protected $fillable = [
        'gar_urbanizacion_id',
        'gar_nombre',
        'gar_ubicacion',
        'gar_permite_vehicular',
        'gar_permite_peatonal',
        'gar_activo',
    ];

    protected $casts = [
        'gar_permite_vehicular' => 'boolean',
        'gar_permite_peatonal' => 'boolean',
        'gar_activo' => 'boolean',
    ];

    public function urbanizacion()
    {
        return $this->belongsTo(Urbanizacion::class, 'gar_urbanizacion_id', 'urb_id');
    }
}
