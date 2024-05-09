<?php
declare (strict_types = 1);

namespace app\index\controller;
use think\facade\Session;
use think\facade\Request;
use think\facade\View;
use think\Collection;
use app\common\service\YpayUser as S;
use app\common\model\YpayUser as M;
use app\common\model\YpayPaylist;
use app\common\model\MoneyLog;
use app\common\model\YpayOrder;
use app\common\model\YpayVip;
use think\facade\Db;
use app\common\model\YpayOrder as O;
use app\common\service\Paylist as payList;
use app\common\service\YpayRecharge;
use app\common\service\Jialanshen;
use think\facade\Config;
use app\common\model\YpayCdk;

class Deal extends \app\BaseController
{
    protected $middleware = ['FrontCheck'];
    
    //控制台页面
    public function recharge()
    {
        //获取充值通道
        $config = getConfig();
        $array = 
        [
            ['id' => 'qqpay','name' => 'Q Q','payListId' => $config['qqpay'],'isOpen' => 'yes'],
            ['id' => 'wxpay','name' => '微 信','payListId' => $config['wechat'],'isOpen' => 'yes'],
            ['id' => 'alipay','name' => '支 付 宝','payListId' => $config['alipay'],'isOpen' => 'yes'],
        ];
        $diy_recharge = $config['diy_recharge'];
        //判断是否为空
        if(empty($diy_recharge)){
            $diy_recharge ="qqpay,wxpay,alipay";
        }
        foreach ($array as $key => $value){
            $position = strpos($diy_recharge, $value['id']);
            $temp = YpayPaylist::where('status',1)->find($value['payListId']);
            if(empty($temp)){
                $array[$key]['isOpen'] = 'no'; 
            }
            $array[$key]['sort'] = $position;
        }
        
        $collection = new Collection($array);
        $array = $collection->sort(function ($a, $b) {return $a['sort'] - $b['sort'];})->values()->all();
        
        //判断是否全部关闭快捷登录方式
        $temp = array_column($array, 'isOpen');
        foreach ($temp as $key => $value){
            if($value == 'no'){
                $is_temp = true;
            }else{
                $is_temp = false;
                break;
            }
        }
        if($is_temp){
            $array = 'no';
        }
        
        View::assign(
            [
                'user' => S::getUser(),
                'vip' => S::getVip(),
                'recharge' => $array
            ]);
        return $this->fetch();
    }
    
    
    public function vip()
    {
        if (Request::isAjax()){
            $this->getJson(S::govip(Request::param('','','strip_tags')));
        }
        $user = S::getUser();
        $viplist = Db::table('ypay_vip')->where('status', 1)->order('sort','asc')->select()->toArray();
        foreach ($viplist as $key => $value){
            if($value['id'] == $user['vip_id']){
                $viplist[$key]['isBuy'] = 'yes'; 
            }else{
                $viplist[$key]['isBuy'] = 'no'; 
            }
        }
         View::assign(
            [
                'user' => S::getUser(),
                'vip' => S::getVip(),
            ]);
        View::assign('viplist', $viplist);
        return $this->fetch();
    }
    
    public function moneylog()
    {
        $log = MoneyLog::getUserList(S::getUserId());
        json_encode($log, JSON_FORCE_OBJECT);
        return $log;
    }
    
    public function orderlog()
    {
        if (Request::isAjax()){
            $order = YpayOrder::getUserList(S::getUserId());
            json_encode($order, JSON_FORCE_OBJECT);
            return $order;
        }
        $data = 
        [
            "allordercount"       => YpayOrder::where('status',1)->where('user_id',S::getUserId())->count(),
            "dayordercount"     => YpayOrder::where('status',1)->where('user_id',S::getUserId())->whereDay('create_time')->count(),
            "allmoney" => YpayOrder::where('user_id',S::getUserId())->where('status',1)->sum('truemoney'),
            "daymoney" => YpayOrder::where('user_id',S::getUserId())->where('status',1)->whereDay('create_time')->sum('truemoney')
        ];
        View::assign('tj', $data);
        
        View::assign(
            [
                'user' => S::getUser(),
                'vip' => S::getVip(),
                'totalRevenue' => S::getUser_totalRevenue(),
                'comparison' => S::getUser_ComparisonData(),
            ]);
        return $this->fetch();
    }
    
    //充值分化数据
    public function dopay()
    {
        $config = getConfig(); //获取配置数据
        $data = Request::param('','','strip_tags'); //获取对应参数
        $request = \think\facade\Request::instance();
        $order_id = 'YPay'.date("YmdHis").rand(11111,99999); //生成订单号
        
        //判断充值金额是否合规
        if($data['money'] < $config['min_recharge'])
        {
            View::assign('error_tips', "充值金额低于最低充值金额--".getConfig()['min_recharge']."元");
            View::assign('error_url', "/Deal/Recharge");
            return $this->fetch('pay/submit');
        }
        if( $data['money'] > $config['max_recharge'])
        {
            View::assign('error_tips', "充值金额高于最高充值金额--".getConfig()['max_recharge']."元");
            View::assign('error_url', "/Deal/Recharge");
            return $this->fetch('pay/submit');
        }
        
        //获取支付类型
        if($data['type'] == 'wxpay'){
            $type = 'wechat';
        }else{
            $type = $data['type'];
        }
        
        $payList = YpayPaylist::select(); //获取全部充值通道
        
        $temp = []; //定义接收数据数组
        
        //循环找到对应的支付通道配置
        foreach($payList as $key => $value){
            
            //判断是否和配置的支付ID一样且赋值
            if($value['id'] == $config[$type]){
                $temp = $value;
            }
        }
        
        //添加数据
        $creat_data = 
            [
                "type"       => $data['type'],
                "rtype" => 0,
                "out_trade_no"     => $order_id,
                "user_id" => S::getUserId(),
                "status" => 0, //支付状态
                "money"      => $data['money'],//订单金额
            ];
        YpayRecharge::goAdd($creat_data);
        
        //获取回调地址
        $data["notify_url"] =  $request->root(true).'/Notify/notify';//异步通知地址
        $data["return_url"] =  $request->root(true).'/Notify/return';//同步通知地址
        
        //根据支付类型调用不同方法
        //1:支付参数 2:数据 3:订单号
        switch ($temp['type']) {
            case 'epay':
                $res = payList::epay($data,$order_id);
                break;
            case 'dmf':
                $res = payList::alipay($data,$order_id);
                $order = Db::name('ypay_recharge')->where('out_trade_no', $order_id)->find();
                $basic = Db::name('ypay_userbasic')->where('user_id', $order['user_id'])->find();
                        
                //如果超时时间为空,则默认为180秒
                if(empty($basic['timeout_time'])){
                    $basic['timeout_time'] = 180;
                }
                
                //如果超时时间大于后台设置最大超时时间则调用后台设置最大超时时间
                if($basic['timeout_time'] > getConfig()['timeout']){
                    $basic['timeout_time'] = getConfig()['timeout'];
                }
                $data = [
                    'user_id' => $order['user_id'],
                    'status' => 0,
                    'out_time' => time() + $basic['timeout_time'],
                    'qrcode' => $res['qr_code'],
                    ];
                YpayRecharge::goEdit($data,$order['id']);
                $order = Db::name('ypay_recharge')->where('out_trade_no', $order_id)->find();
                $ms = $order['out_time']-time();
                $order['name'] = '在线充值';
                $order['h5_qrurl'] = $res['qr_code'];
                $order['trade_no'] = $order['out_trade_no'];
                $order['truemoney'] = $order['money'];
                $order['type'] = 'alipay';
                View::assign('order',$order);
                View::assign('ms',180);
                View::assign('code','alipay_dmf');
                View::assign('console_notity',$basic['console_notity']);
                View::assign('timeout_url',$basic['timeout_url']);
                View::assign('yuyin_tips',$basic['yuyin_tips']);
                View::assign('is_payPopUp',$basic['is_payPopUp']);
                return $this->fetch('pay/console_dopay');
                die;
                break;
            case 'alipay':
                $res = payList::alipay($data,$order_id);
                break;
            case 'wxpay':
                $res = payList::wxpay($data,$order_id);
                break;
            default:
                // code...
                break;
        }
    
        echo($res);
        die;
        // }
        //return $this->fetch();
    }
    
    
    public function Reback($id)
    {
        //获取用户信息
        $user = S::getUser();
        
        //判断余额是否不足
        if(0 > $user['money']){
            return json(['code'=>0,'msg'=>'你的余额不足!']);
        }
        
        //查询订单
        $order = Db::name('ypay_order')->where('user_id',$user['id'])->where('id',$id)->find();
        
        //判断订单是否查找到订单
        if(empty($order))
        {
            return json(['code'=>0,'msg'=>'订单不存在!']);
        }
        
        //执行回调
        $url = Jialanshen::creat_callback($order);
        $res = get_curl($url['notify']);
        if($res=='success' || $res =="fail")
        {
            Db::name('ypay_order')->where('id',$id)->update(['api_memo' =>$res]);
        }
        else
        {
            Db::name('ypay_order')->where('id',$id)->update(['api_memo' =>'error']);
        }
        return json(['code'=>1,'msg'=>$res]);
    }
    
}
