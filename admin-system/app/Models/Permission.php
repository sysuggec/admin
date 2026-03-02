<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 't_permission';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'display_name',
        'type',
        'parent_id',
        'path',
        'api_path',
        'icon',
        'sort_order',
        'status',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'parent_id' => 'integer',
            'sort_order' => 'integer',
            'status' => 'integer',
        ];
    }

    /**
     * 权限所属角色
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 't_role_permission', 'permission_id', 'role_id');
    }

    /**
     * 子权限
     */
    public function children()
    {
        return $this->hasMany(Permission::class, 'parent_id', 'id')->orderBy('sort_order');
    }

    /**
     * 父权限
     */
    public function parent()
    {
        return $this->belongsTo(Permission::class, 'parent_id', 'id');
    }

    /**
     * 获取权限树
     */
    public static function getTree(?int $parentId = null): array
    {
        $permissions = self::where('status', 1)
            ->orderBy('sort_order')
            ->get()
            ->toArray();

        return self::buildTree($permissions, $parentId ?? 0);
    }

    /**
     * 构建树结构
     */
    public static function buildTree(array $items, int $parentId = 0): array
    {
        $tree = [];
        foreach ($items as $item) {
            if ($item['parent_id'] == $parentId) {
                $children = self::buildTree($items, $item['id']);
                if ($children) {
                    $item['children'] = $children;
                }
                $tree[] = $item;
            }
        }
        return $tree;
    }
}
