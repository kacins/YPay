<?php
declare (strict_types = 1);

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;
use app\common\model\YpayOrder;

class YpayUser extends Model
{
    use SoftDelete;
     protected $deleteTime = false;
    // 获取列表
    public static function getList()
    {
        $where = [];
        $limit = input('get.limit');
        
               //按用户ID查找
               if ($id = input("id")) {
                   $where[] = ["id", "like", "%" . $id . "%"];
               }
               //按用户名查找
               if ($username = input("username")) {
                   $where[] = ["username", "like", "%" . $username . "%"];
               }
               //按邮箱查找
               if ($email = input("email")) {
                   $where[] = ["email", "like", "%" . $email . "%"];
               }
               //按手机号查找
               if ($mobile = input("mobile")) {
                   $where[] = ["mobile", "like", "%" . $mobile . "%"];
               }
        $list = self::order('id','desc')->where($where)->paginate($limit)->each(function($item, $key){
                $item['total_money'] =  YpayOrder::where(['user_id'	=>	$item['id'],'status'=>	1])->sum('truemoney');
                $item['yesterday_money'] = YpayOrder::whereTime('create_time', 'yesterday')->where(['user_id'	=>	$item['id'],'status'=>	1])->sum('truemoney');
                $item['today_money'] = YpayOrder::whereDay('create_time')->where(['user_id'	=>	$item['id'],'status'=>	1])->sum('truemoney');
                //获取VIP名称
                if(!empty($item['vip_id'])){
                    $vip = YpayVip::where(['id'	=>	$item['vip_id'],'status'=>	1])->find();
                    if(empty($vip)){
                        $item['vip'] = '该会员套餐已关闭';  
                    }else{
                        $item['vip'] = $vip['name'];
                    }
                    
                }else{
                    $item['vip'] = '未开通会员';
                }
                
                //获取登录信息
                $loginLog = AdminFrontLog::where(['uid'	=>	$item['id'],'type'=>0])->order('id', 'desc')->find();
                if(!empty($loginLog)){
                    $item['login_time'] = $loginLog['create_time'];
                    $item['login_ip'] = $loginLog['ip'];
                }

                return $item;
        });
        return ['code'=>0,'data'=>$list->items(),'extend'=>['count' => $list->total(), 'limit' => $limit]];
    }
    
    public static function money($money, $user_id, $memo)
    {
        $user = self::find($user_id);
        if ($user && $money != 0) {
            $before = $user->money;
            //$after = $user->money + $money;
            $after = function_exists('bcadd') ? bcadd($user->money, $money, 2) : $user->money + $money;
            //更新会员信息
            $user->save(['money' => $after]);
            //写入日志
            MoneyLog::create([
                'user_id' => $user_id,
                'money'   => $money,
                'beforemoney'  => $before,
                'after'   => $after,
                'memo'    => $memo,
            ]);
        }
    }
    
    //查找下级用户
    public static function getAffList($user_id)
    {
        $user = self::find($user_id);
        $where = ['superior_id' => $user_id];
        $limit = input('get.limit');
        
        $list = self::order('id','desc')->where($where)->paginate($limit)->each(function($item, $key){

                return $item;
        });
        return ['code'=>0,'data'=>$list->items(),'extend'=>['count' => $list->total(), 'limit' => $limit]];
    }
    
}
