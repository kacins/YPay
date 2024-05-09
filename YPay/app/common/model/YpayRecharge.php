<?php
declare (strict_types = 1);

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;
class YpayRecharge extends Model
{
    // 获取列表
    public static function getList()
    {
        $where = [];
        $limit = input('get.limit');
               //按状态查询
               $status = input("status");
               if ($status !="" && $status!=null) {
                   $where[] = ["status", "=",$status];
               }
               //按支付类型查找
               if ($type = input("type")) {
                   $where[] = ["type", "like", "%" . $type . "%"];
               }
               //按商户订单查找
               if ($trade_no = input("trade_no")) {
                   $where[] = ["trade_no", "like", "%" . $trade_no . "%"];
               }
               //按本地订单查找
               if ($out_trade_no = input("out_trade_no")) {
                   $where[] = ["out_trade_no", "like", "%" . $out_trade_no . "%"];
               }
               //按会员ID查找
               if ($user_id = input("user_id")) {
                   $where[] = ["user_id", "like", "%" . $user_id . "%"];
               }
               //按订单状态查找
               if ($status = input("status")) {
                   $where[] = ["status", "like", "%" . $status . "%"];
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
}
