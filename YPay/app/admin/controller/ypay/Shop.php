<?php
declare (strict_types = 1);

namespace app\admin\controller\ypay;

use think\facade\Session;
use think\facade\Request;
use app\common\util\Upload as Up;
use app\common\model\AdminPhoto as P;
use app\common\service\AdminAdmin as S;
use think\facade\Db;
use app\common\model\YpayOrder;
use app\common\model\YpayRecharge;
use app\common\model\YpayAccount;
use app\common\model\MoneyLog;
use app\common\model\AdminAdminLog;
use app\common\model\AdminFrontLog;
use think\facade\View;
use app\common\model\YpayUser;
use app\common\model\YpayTicket;
use app\common\model\YpayCdk;
use app\common\model\YpayVip;
use app\common\service\Notice as notice;
use app\common\core\core;

class Shop extends  \app\admin\controller\Base
{
    protected $middleware = ['AdminCheck','AdminPermission'];

    // 列表
    public function index(){
        $total_water_order = YpayOrder::where('status',1)->count();//平台总流水订单
        $total_recharge_order = YpayRecharge::where('status',1)->count();//平台总充值订单
        
        //获取左边头部内容
        $arr_itme = [
	        ['name' => '今日','time' => 'Day',],
	        ['name' => '本月','time' => 'Month',],
	        ['name' => '今年','time' => 'Year',],
        ];
        
        foreach ($arr_itme as $key => $value){
            $function = 'where'.$value['time'];
            $value['order_ok'] = YpayOrder::where('status',1)->$function('create_time')->count();
            $value['order'] = YpayOrder::$function('create_time')->count();
            $value['success'] = ($value['order_ok']==0 || $value['order']==0) ? 0 : sprintf("%.2f",$value['order_ok']/$value['order']*100);
            $value['money_ok'] = YpayOrder::where('status',1)->$function('create_time')->sum('truemoney');
            $value['money'] = YpayOrder::$function('create_time')->sum('truemoney');
            $top[$key] = $value;
        }
        
        //获取左边底部内容
        $day=[];
        $__day=[];
        // 获取30天时间
        for ($i=0; $i < 30; $i++) { 
	        $_day = 30-$i;
	        $time=mktime(0, 0, 0,(int)date('m'), date('d') - $_day, (int)date('Y'));
	   
	        $day[$i] = date('Y-m-d',$time);
	        $__day[$i] = date('Y-m-d',$time);
        }

        $__sum_data = [];
        $__sum_ok_data = [];
        $__sum_no_data = [];
        foreach ($__day as $k => $time) {
            $endTime = date("Y-m-d",strtotime($time ." + 1 day"));
        	$__sum_data[$k] = YpayOrder::whereTime('create_time', 'between', [$time, $endTime])->count();
        	$__sum_ok_data[$k] = YpayOrder::where('status',1)->whereTime('create_time', 'between', [$time, $endTime])->count();
        	$__sum_no_data[$k] = YpayOrder::where('status',0)->whereTime('create_time', 'between', [$time, $endTime])->count();
        }
        $time = [];
        $time['time_arr'] = str_replace('"',"'",json_encode($day));
        $time['__sum_data'] = json_encode($__sum_data);
        $time['__sum_ok_data'] = json_encode($__sum_ok_data);
        $time['__sum_no_data'] = json_encode($__sum_no_data);
        
        // 优化总余额池资金展示
        
        // 获取上周的起始时间和结束时间
        $startOfWeek = date('Y-m-d H:i:s', strtotime('last week monday'));
        $endOfWeek = date('Y-m-d H:i:s', strtotime('last week sunday'));

        // 获取上月的起始时间和结束时间
        $startOfMonth = date('Y-m-d H:i:s', strtotime('first day of last month'));
        $endOfMonth = date('Y-m-d H:i:s', strtotime('last day of last month'));
        
        
        //获取右边头部内容
        $other_info =[
		         		array('title' => '总用户','value' => YpayUser::count() ),
		         		array('title' => '总订单','value' => $total_water_order + $total_recharge_order ),
		         		array('title' => '总余额池','value' => sprintf('%.2f',YpayUser::sum('money')) ),
		         		array('title' => '总在线通道','value' => YpayAccount::where('status',1)->count() ),
		         		array('title' => '总充值订单','value' => $total_recharge_order ),
		         		array('title' => '今日新增用户','value' => YpayUser::whereDay('create_time')->count() ),
		         		array('title' => '今日充值订单','value' => YpayRecharge::where('status',1)->whereDay('create_time')->count()),
		         		array('title' => '昨天交易订单','value' => YpayOrder::where('status',1)->whereDay('create_time', 'yesterday')->count()),
		         		array('title' => '昨天收款金额','value' => YpayOrder::where('status',1)->whereDay('create_time', 'yesterday')->sum('truemoney')),
		         		array('title' => '上周交易金额','value' => YpayOrder::where('status',1) ->where('create_time', 'between', [$startOfWeek, $endOfWeek])->sum('truemoney')),
		         		array('title' => '上月交易金额','value' => YpayOrder::where('status',1) ->where('create_time', 'between', [$startOfMonth, $endOfMonth])->sum('truemoney')),
		         		array('title' => 'QQ在线通道','value' => YpayAccount::where('status',1)->where('type','qqpay')->count() ),
		         		array('title' => '微信在线通道','value' => YpayAccount::where('status',1)->where('type','wxpay')->count() ),
		         		array('title' => '支付宝在线通道','value' => YpayAccount::where('status',1)->where('type','alipay')->count() ),
		         	];
        
        $data = 
        [
            //统计图 - 总流水
            "wechat_month_money" => YpayOrder::where('status',1)->whereTime('create_time', 'month')->where('type','wxpay')->sum('truemoney'),//微信本月总金额
            "wechat_week_money" => YpayOrder::where('status',1)->whereWeek('create_time')->where('type','wxpay')->sum('truemoney'),//微信本周总金额
            "wechat_today_money" => YpayOrder::where('status',1)->whereDay('create_time')->where('type','wxpay')->sum('truemoney'),//微信今日总金额
            
            "ali_month_money" => YpayOrder::where('status',1)->whereTime('create_time', 'month')->where('type','alipay')->sum('truemoney'),//支付宝本月总金额
            "ali_week_money" => YpayOrder::where('status',1)->whereWeek('create_time')->where('type','alipay')->sum('truemoney'),//支付宝本周总金额
            "ali_today_money" => YpayOrder::where('status',1)->whereDay('create_time')->where('type','alipay')->sum('truemoney'),//支付宝今日总金额
            
            "qq_month_money" => YpayOrder::where('status',1)->whereTime('create_time', 'month')->where('type','qqpay')->sum('truemoney'),//QQ本月总金额
            "qq_week_money" => YpayOrder::where('status',1)->whereWeek('create_time')->where('type','qqpay')->sum('truemoney'),//QQ本周总金额
            "qq_today_money" => YpayOrder::where('status',1)->whereDay('create_time')->where('type','qqpay')->sum('truemoney'),//QQ今日总金额
            
            //统计图 - 总充值
            "wechat_month_recharge" => YpayRecharge::where('status',1)->whereTime('create_time', 'month')->where('type','wxpay')->sum('money'),//微信本月总充值
            "wechat_week_recharge" => YpayRecharge::where('status',1)->whereWeek('create_time')->where('type','wxpay')->sum('money'),//微信本周总充值
            "wechat_today_recharge" => YpayRecharge::where('status',1)->whereDay('create_time')->where('type','wxpay')->sum('money'),//微信今日总充值
            
            "ali_month_recharge" => YpayRecharge::where('status',1)->whereTime('create_time', 'month')->where('type','alipay')->sum('money'),//支付宝本月总充值
            "ali_week_recharge" => YpayRecharge::where('status',1)->whereWeek('create_time')->where('type','alipay')->sum('money'),//支付宝本周总充值
            "ali_today_recharge" => YpayRecharge::where('status',1)->whereDay('create_time')->where('type','alipay')->sum('money'),//支付宝今日总充值
            
            "qq_month_recharge" => YpayRecharge::where('status',1)->whereTime('create_time', 'month')->where('type','qqpay')->sum('money'),//QQ本月总充值
            "qq_week_recharge" => YpayRecharge::where('status',1)->whereWeek('create_time')->where('type','qqpay')->sum('money'),//QQ本周总充值
            "qq_today_recharge" => YpayRecharge::where('status',1)->whereDay('create_time')->where('type','qqpay')->sum('money'),//QQ今日总充值
        ];
        
        //创建Core对象
        $core  = new Core();
        
        //获取商城总览广告位
        $shopAd = $core->getShopAd();
        
        View::assign(['data'=>$data,'top'=>$top,'time'=>$time,'other_info'=>$other_info,'shopAd' => $shopAd]);
        return $this->fetch();
    }
    
    // 数据清理
    public function clear(){
        $allOrder = YpayOrder::count(); //全部订单
        $noPayOrder = YpayOrder::where('status',0)->count();//未支付订单
        
        $isOrderClear = true;
        if($noPayOrder == 0){
            $isOrderClear = false;
            $orderProportion = 0 . '%';
        }else{
            $orderProportion = number_format((($noPayOrder/$allOrder) * 100), 2) . '%';//未支付订单占比
        }
        
        
        $allRecharge = YpayRecharge::count();//全部充值订单
        $noPayRecharge = YpayRecharge::where('status',0)->count();//未支付充值订单
        $isRechargeClear = true;
        if($noPayRecharge == 0){
            $isRechargeClear = false;
            $rechargeproportion = 0 . '%';
        }else{
            $rechargeproportion= number_format((($noPayRecharge/$allRecharge) * 100), 2) . '%';//未支付充值订单占比
        }
        
        $allAdminLog = AdminAdminLog::count();//全部后台日志统计
        $isAdminLogClear = false;
        if($allAdminLog >= 500){
            $isAdminLogClear = true;
        }
        
        $allUserLog = AdminFrontLog::count();//全部用户端日志统计
        $isUserLogClear = false;
        if($allUserLog >= 500){
            $isUserLogClear = true;
        }
        
        //定义数据数组
       $array = 
       [
           ['id' => 'clearOrder','title' => '订单清理','desc' => '总计&nbsp;<strong class="attention"><span>'. $allOrder .'</span></strong>&nbsp;个订单记录&nbsp;<strong class="attention"><span>'. $noPayOrder .'&nbsp;个未支付订单。</span></strong>' , 'details' => '未支付全部订单(用户)' , 'total' => $noPayOrder , 'proportion' => $orderProportion ,'isClear' => $isOrderClear],
           ['id' => 'clearRecharge','title' => '充值记录清理','desc' => '总计&nbsp;<strong class="attention"><span>'. $allRecharge .'</span></strong>&nbsp;个订单记录&nbsp;<strong class="attention"><span>'. $noPayRecharge .'&nbsp;个未支付订单</span></strong>。' ,'details' => '未支付充值订单(平台)' , 'total' => $noPayRecharge , 'proportion' => $rechargeproportion ,'isClear' => $isRechargeClear],
           ['id' => 'clearAdminLog','title' => '后台日志清理','desc' => '总计&nbsp;<strong class="attention"><span>'. $allAdminLog .'</span></strong>&nbsp;个后台日志,小于500条日志可无需清理' ,'details' => '后台操作日志' , 'total' => $allAdminLog , 'proportion' => '100.00%' ,'isClear' => $isAdminLogClear],
           ['id' => 'clearUserLog','title' => '用户日志清理','desc' => '总计&nbsp;<strong class="attention"><span>'. $allUserLog .'</span></strong>&nbsp;个用户日志,小于500条日志可无需清理' ,'details' => '用户行为日志' , 'total' => $allUserLog , 'proportion' => '100.00%' ,'isClear' => $isUserLogClear],
        ];
        View::assign(['data'=>$array]);
        return $this->fetch();
    }
    
    //清理未支付订单
    public function clearOrder(){
        try {
            YpayOrder::where('status',0)->delete(true);
            return json(['code'=>200,'msg'=>'清理成功!']);
        }catch (\Exception $e){
            return json(['msg'=>'操作失败'.$e->getMessage(),'code'=>201]);
        }
    }
    
    //清理充值记录
    public function clearRecharge(){
        try {
            YpayRecharge::where('status',0)->delete(true);
            return json(['code'=>200,'msg'=>'清理成功!']);
        }catch (\Exception $e){
            return json(['msg'=>'操作失败'.$e->getMessage(),'code'=>201]);
        }
    }
    
    //清除后台操作日志
    public function clearAdminLog(){
        try {
            Db::query('truncate table admin_admin_log');
            return json(['code'=>200,'msg'=>'清理成功!']);
        }catch (\Exception $e){
            return json(['msg'=>'操作失败'.$e->getMessage(),'code'=>201]);
        }
    }
    
    //清理用户行为日志
    public function clearUserLog(){
        try {
            Db::query('truncate table admin_front_log');
            return json(['code'=>200,'msg'=>'清理成功!']);
        }catch (\Exception $e){
            return json(['msg'=>'操作失败'.$e->getMessage(),'code'=>201]);
        }
    }
    
    //后台加/扣款
    public function plus(){
        
        if (Request::isAjax()) {
            return $this->getJson(MoneyLog::getPlusList());
        }
        return $this->fetch();
    }
    
    //加款操作处理
    public function add_plus(){
        if (Request::isAjax()) {
            $data = Request::post();
            $user = YpayUser::find($data['id']);//获取用户数据
            $money = $data['money'];
            $memo = '后台充值余额';
            if(0 > $money){
              $memo = '后台扣除余额';  
            }
            $before = $user->money;
            //$after = $user->money + $money;
            $after = function_exists('bcadd') ? bcadd($user->money, $money, 2) : $user->money + $money;
            //更新会员信息
            $user->save(['money' => $after]);
            //写入日志
            MoneyLog::create([
                'user_id' => $data['id'],
                'type' => 1 , //类型1为后台充值
                'money'   => $money,
                'beforemoney'  => $before,
                'after'   => $after,
                'memo'    => $memo,
            ]);
            return $this->getJson(['code' => 200]);
        }
        return $this->fetch();
    }

}
