<?php

namespace App\Models\Core;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inmueble extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'core_inmuebles';
    protected $primaryKey = 'inm_id';

    protected $fillable = [
        'inm_urbanizacion_id',
        'inm_manzana_bloque',
        'inm_numero_villa',
        'inm_tipo',
        'inm_estado',
        'inm_alicuota_porcentaje',
        'inm_observaciones',
        'inm_activo',
    ];

    protected $casts = [
        'inm_activo' => 'boolean',
    ];

    public function urbanizacion()
    {
        return $this->belongsTo(Urbanizacion::class, 'inm_urbanizacion_id', 'urb_id');
    }

    public function residentes()
    {
        return $this->belongsToMany(Persona::class, 'core_inmueble_residentes', 'res_inmueble_id', 'res_persona_id')
            ->withPivot('res_tipo_relacion', 'res_es_principal', 'res_activo');
    }

    public function getUbicacionCompletaAttribute(): string
    {
        return "{$this->inm_manzana_bloque} - {$this->inm_numero_villa}";
    }
}
