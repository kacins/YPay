<?php
declare (strict_types = 1);

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;
class YpayAccount extends Model
{
    use SoftDelete;
     protected $deleteTime = false;
    // 获取列表
    public static function getList()
    {
        $where = [];
        $limit = input('get.limit');
        
               //按通道标识查找
               if ($code = input("code")) {
                   $where[] = ["code", "like", "%" . $code . "%"];
               }
               //按通道类型查找
               if ($type = input("type")) {
                   $where[] = ["type", "like", "%" . $type . "%"];
               }
               //按会员ID查找
               if ($user_id = input("user_id")) {
                   $where[] = ["user_id", "like", "%" . $user_id . "%"];
               }
        $list = self::order('id','desc')->where($where)->paginate($limit);
        
        foreach ($list->items() as $item)
        {
            $item['succcount'] = YpayOrder::where('status',1)->where('account_id',$item['id'])->count();
            $item['code_name'] = AdminChannel::where('code',$item['code'])->field('name')->find()['name'];
            $item['succprice'] = YpayOrder::where('status',1)->where('account_id',$item['id'])->sum('truemoney');
            
        }
        return ['code'=>0,'data'=>$list->items(),'extend'=>['count' => $list->total(), 'limit' => $limit]];
    }
    
    public static function getUserList($user_id)
    {
        $where[] = ["user_id",'=',$user_id];
        $limit = self::where($where)->count();
        $list = self::order('id','desc')->where($where)->paginate($limit);
        foreach ($list->items() as $item)
        {
            $item['today_money'] = YpayOrder::where('status',1)->whereDay('create_time')->where('account_id',$item['id'])->sum('truemoney');
            $item['yesterday_money'] = YpayOrder::where('status',1)->whereDay('create_time', 'yesterday')->where('account_id',$item['id'])->sum('truemoney');
            $item['succcount'] = YpayOrder::where('status',1)->where('account_id',$item['id'])->count();
            $item['code_name'] = AdminChannel::where('code',$item['code'])->field('name')->find()['name'];
            $item['succprice'] = YpayOrder::where('status',1)->where('account_id',$item['id'])->sum('truemoney');
            
            //判断通道是否在线
            if($item['status'] == 1){
                
                // 定义两个时间戳
                $temp_time = date('Y-m-d H:i:s', time());
                
                //对部分通道进行无创建时间处理
                if(empty($item['create_time'])){
                    self::where('id',$item['id'])->update(['create_time' => $temp_time]);
                    $start = strtotime($temp_time);
                }else{
                    $start = strtotime($item['create_time']);
                }
            
                $end = strtotime(date('Y-m-d H:i:s', time()));
                
                // 计算时间差
                $diff = $end - $start;
                
                // 计算天、小时、分钟
                $days = floor($diff / (60 * 60 * 24));
                $hours = floor(($diff - $days * 60 * 60 * 24) / (60 * 60));
                $minutes = floor(($diff - $days * 60 * 60 * 24 - $hours * 60 * 60) / 60);
    
                $item['online_time'] = '<p style="color:red;">'.$days."天".$hours."小时".$minutes."分钟".'</p>';
            }else{
                $item['online_time'] = '<p style="color:black;">已掉线</p>';
            }
            
            if($item['code'] == 'wxpay_cloud' || $item['code'] == 'wxpay_cloudzs' || $item['code'] == 'wxpay_skd' || $item['code'] == 'qqpay_cloud' || $item['code'] == 'qqpay_wzq'){
                $cloud = YpayCloud::where('address',$item['vcloudurl'])->find();
                
                if(!empty($cloud)){
                    $item['cloud_name'] =$cloud['name'];
                }else{
                    $item['cloud_name'] = '云端已失效';
                }
                
            }
            
        }
        
        return ['code'=>0,'data'=>$list->items(),'extend'=>['count' => $list->total(), 'limit' => $limit]];
    }
    
    public static function getUserInfo($id){
        $item = self::find($id);
        return $item;
    }
}
