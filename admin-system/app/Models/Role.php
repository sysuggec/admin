<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 't_role';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'sort_order',
        'status',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'status' => 'integer',
        ];
    }

    /**
     * 角色所属用户
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 't_user_role', 'role_id', 'user_id');
    }

    /**
     * 角色拥有的权限
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 't_role_permission', 'role_id', 'permission_id');
    }
}
