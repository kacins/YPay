<?php
declare (strict_types = 1);

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;
class MoneyLog extends Model
{
    use SoftDelete;
     protected $deleteTime = false;
    // 获取列表
    public static function getList()
    {
        $where = [];
        $limit = input('get.limit');
               //按会员ID查找
               if ($user_id = input("user_id")) {
                   $where[] = ["user_id", "like", "%" . $user_id . "%"];
               }
               //按变更类型查询查找
               if ($memo = input("memo")) {
                   $where[] = ["memo", "like", "%" . $memo . "%"];
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
    
    
    public static function getUserList($userid)
    {
        $where = [];
        $limit = input('get.limit');
        $where[] = ["user_id", "=", $userid];
        $list = self::order('id','desc')->where($where)->paginate($limit);
        return ['code'=>0,'data'=>$list->items(),'extend'=>['count' => $list->total(), 'limit' => $limit]];
    }
    
    
    //获取后台充值列表数据
    public static function getPlusList(){
        $where = [];
        $limit = input('get.limit');
        //按会员ID查找
        if ($user_id = input("user_id")) {
            $where[] = ["user_id", "like", "%" . $user_id . "%"];
        }
        $where[] = ["memo", "=", '后台充值余额'];
        
        $list = self::order('id','desc')->where($where)->paginate($limit);
        return ['code'=>0,'data'=>$list->items(),'extend'=>['count' => $list->total(), 'limit' => $limit]];
    }
    
    
}
