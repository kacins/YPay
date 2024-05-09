<?php
declare (strict_types = 1);

namespace app\common\model;

use think\Model;

class AdminPermission extends Model
{

    // 获取列表
    public static function getList()
    {
        $list = self::order('id','desc')->select();
        return ['code'=>0,'data'=>$list->toArray(),'extend'=>['count' => $list->count()]];
    }

    // 获取一条数据
    public static function getFind($id)
    {
        return ['model' => self::find($id),'permissions' => get_tree((self::order('sort','asc'))->select()->toArray())];
    }

    // 子权限
    public function child()
    {
        return $this->hasMany('AdminPermission','pid','id');
    }
}
