<?php
declare (strict_types = 1);

namespace app\common\model;

use think\Model;

class YpayPaylist extends Model
{
   // 获取列表
    public static function getList()
    {
        $where[] = ["user_id",'=',0];
        $limit = input('get.limit');
        
        $list = self::order('id','desc')->where($where)->paginate($limit);
        return ['code'=>0,'data'=>$list->items(),'extend'=>['count' => $list->total(), 'limit' => $limit]];
    }

    // 获取列表
    public static function getUserList($user_id)
    {
        $where[] = ["user_id",'=',$user_id];
        $limit = self::where($where)->count();
        
        $list = self::order('id','desc')->where($where)->paginate($limit);
        return ['code'=>0,'data'=>$list->items(),'extend'=>['count' => $list->total(), 'limit' => $limit]];
    }
}
