<?php

namespace App\Models\Security;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tema extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'sec_temas';
    protected $primaryKey = 'tem_id';

    protected $fillable = [
        'tem_codigo',
        'tem_nombre',
        'tem_descripcion',
        'tem_variables_css',
        'tem_es_oscuro',
        'tem_es_sistema',
        'tem_activo',
    ];

    protected $casts = [
        'tem_variables_css' => 'array',
        'tem_es_oscuro' => 'boolean',
        'tem_es_sistema' => 'boolean',
        'tem_activo' => 'boolean',
    ];
}
