<?php
declare (strict_types = 1);

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

class AdminRole extends Model
{
    use SoftDelete;

    // 获取列表
    public static function getList()
    {
        $limit = input('get.limit');
        $list = self::order('id','desc')->paginate($limit);
        return ['code'=>0,'data'=>$list->items(),'extend'=>['count' => $list->total(), 'limit' => $limit]];
    }

    // 获取用户直接权限
    public static function getPermission($id)
    {
        $role = self::with('permissions')->find($id);
        $permissions = AdminPermission::order('sort','asc')->select();
        foreach ($permissions as $permission){
            foreach ($role->permissions as $v){
                if ($permission->id == $v['id']){
                    $permission->own = true;
                }
            }
        }
        $permissions = get_tree($permissions->toArray());
        return ['role'=>$role,'permissions'=>$permissions];
    }

    // 角色所有的权限
    public function permissions()
    {
        return $this->belongsToMany('AdminPermission','admin_role_permission','permission_id','role_id');
    }
}
