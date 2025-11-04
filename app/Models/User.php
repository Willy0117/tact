<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasRoles;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
   // User → Role (pivot に tenant_id)
    public function roles()
    {
        return $this->belongsToMany(Role::class)->withPivot('tenant_id');
    }

    // User → Permission (pivot に tenant_id)
    public function permissions()
    {
        return $this->belongsToMany(Permission::class)->withPivot('tenant_id');
    }

    // Tenant リレーション
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * ログインユーザーの tenant_id でフィルターしたロールを取得
     */
    public function tenantRoles()
    {
        return $this->roles()->wherePivot('tenant_id', $this->tenant_id);
    }

    /**
     * ログインユーザーの tenant_id でフィルターした権限を取得
     */
    public function tenantPermissions()
    {
        return $this->permissions()->wherePivot('tenant_id', $this->tenant_id);
    }
}
