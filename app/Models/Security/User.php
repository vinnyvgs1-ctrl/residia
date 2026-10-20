<?php

namespace App\Models\Security;

use App\Models\Core\Persona;
use App\Models\Core\Urbanizacion;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes, Auditable;

    protected $table = 'sec_users';
    protected $primaryKey = 'usr_id';

    protected $fillable = [
        'usr_persona_id',
        'usr_name',
        'usr_email',
        'usr_password',
        'usr_es_superadmin',
        'usr_activo',
        'usr_preferencias',
    ];

    protected $hidden = [
        'usr_password',
        'remember_token',
    ];

    protected $casts = [
        'usr_email_verified_at' => 'datetime',
        'usr_password' => 'hashed',
        'usr_es_superadmin' => 'boolean',
        'usr_activo' => 'boolean',
        'usr_preferencias' => 'array',
    ];

    public function getAuthPassword()
    {
        return $this->usr_password;
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'es_superadmin' => (bool) $this->usr_es_superadmin,
            'email' => $this->usr_email,
        ];
    }

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'usr_persona_id', 'per_id');
    }

    public function urbanizaciones()
    {
        return $this->belongsToMany(Urbanizacion::class, 'sec_urbanizacion_usuarios', 'uxu_user_id', 'uxu_urbanizacion_id')
            ->withPivot('uxu_id', 'uxu_es_predeterminada', 'uxu_activo');
    }

    public function hasPermissionInUrbanizacion(string $permissionSlug, string $urbanizacionId): bool
    {
        if ($this->usr_es_superadmin) {
            return true;
        }

        $cacheKey = "perms:usr:{$this->usr_id}:urb:{$urbanizacionId}";
        $slugs = Cache::remember($cacheKey, 600, function () use ($urbanizacionId) {
            $uxu = DB::table('sec_urbanizacion_usuarios')
                ->where('uxu_user_id', $this->usr_id)
                ->where('uxu_urbanizacion_id', $urbanizacionId)
                ->where('uxu_activo', true)
                ->first();

            if (!$uxu) return [];

            return DB::table('sec_usuario_roles')
                ->join('sec_rol_permisos', 'sec_usuario_roles.uxr_rol_id', '=', 'sec_rol_permisos.rxp_rol_id')
                ->join('sec_permisos', 'sec_rol_permisos.rxp_permiso_id', '=', 'sec_permisos.prm_id')
                ->where('sec_usuario_roles.uxr_urbanizacion_usuario_id', $uxu->uxu_id)
                ->pluck('sec_permisos.prm_slug')
                ->unique()
                ->toArray();
        });

        return in_array($permissionSlug, $slugs, true);
    }
}
