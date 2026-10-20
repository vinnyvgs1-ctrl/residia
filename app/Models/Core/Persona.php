<?php

namespace App\Models\Core;

use App\Models\Security\User;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Persona extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'core_personas';
    protected $primaryKey = 'per_id';

    protected $fillable = [
        'per_identificacion',
        'per_tipo_identificacion',
        'per_nombres',
        'per_apellidos',
        'per_telefono',
        'per_email',
        'per_foto_url',
        'per_es_vetado',
        'per_motivo_veto',
    ];

    protected $casts = [
        'per_es_vetado' => 'boolean',
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'usr_persona_id', 'per_id');
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->per_nombres} {$this->per_apellidos}");
    }
}
