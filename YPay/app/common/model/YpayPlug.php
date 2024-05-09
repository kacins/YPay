<?php
declare (strict_types = 1);

namespace app\common\model;

use think\Model;
class YpayPlug extends Model
{
    // 获取列表
    public static function getList()
    {
        $where = [];
        $limit = input('get.limit');
        
               //按插件名称查找
               if ($name = input("name")) {
                   $where[] = ["name", "like", "%" . $name . "%"];
               }
               //按显示状态查找
               if ($status = input("status")) {
                   $where[] = ["status", "like", "%" . $status . "%"];
               }
        $list = self::order('id','desc')->where($where)->paginate($limit);
        return ['code'=>0,'data'=>$list->items(),'extend'=>['count' => $list->total(), 'limit' => $limit]];
    }
    
    // 获取列表
    public static function getPlugList()
    {
        $where[] = ["status",'=',1];
        $limit = input('get.limit');
        $list = self::order('id','desc')->where($where)->paginate($limit);
        return ['code'=>0,'data'=>$list->items(),'extend'=>['count' => $list->total(), 'limit' => $limit]];
    }
    
}
