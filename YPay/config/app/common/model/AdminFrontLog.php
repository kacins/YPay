<?php
declare (strict_types = 1);

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;
class AdminFrontLog extends Model
{
    use SoftDelete;
     protected $deleteTime = false;
    // 获取列表
    public static function getList()
    {
        $where = [];
        $limit = input('get.limit');
        
               //按商户ID查找
               if ($uid = input("uid")) {
                   $where[] = ["uid", "like", "%" . $uid . "%"];
               }
               //按操作IP查找
               if ($ip = input("ip")) {
                   $where[] = ["ip", "like", "%" . $ip . "%"];
               }
               //按创建时间查找
               $start = input("get.create_time-start");
               $end = input("get.create_time-end");
               if ($start && $end) {
                   $where[]=["create_time","between",[$start,date("Y-m-d",strtotime("$end +1 day"))]];
               }
        $list = self::order('id','desc')->where($where)->paginate($limit);
        return ['code'=>0,'data'=>$list->items(),'extend'=>['count' => $list->total(), 'limit' => $limit]];
    }
    
    public static function getUserList($user_id)
    {
        $where = [];
        $limit = input('get.limit');
        
             
               //按操作IP查找
               if ($ip = input("ip")) {
                   $where[] = ["ip", "like", "%" . $ip . "%"];
               }
               //按创建时间查找
               $start = input("get.create_time-start");
               $end = input("get.create_time-end");
               if ($start && $end) {
                   $where[]=["create_time","between",[$start,date("Y-m-d",strtotime("$end +1 day"))]];
               }
               $where[] = ["uid",'=',$user_id];
        $list = self::order('id','desc')->where($where)->paginate($limit);
        return ['code'=>0,'data'=>$list->items(),'extend'=>['count' => $list->total(), 'limit' => $limit]];
    }
    
    
}
