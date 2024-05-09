<?php


namespace app\index\controller;
use app\common\controller\Frontend;
use think\facade\Request;
use think\facade\View;
use app\common\service\YiPay as epay;
use think\facade\Db;
use think\facade\Config;
use app\common\service\Jialanshen;
use app\common\model\YpayRisk as Risk;
use app\common\model\YpayUser as YpayUser;
use app\common\model\YpayDomain as domain;
use app\common\service\Paylist as payList;
use app\common\service\YpayRecharge;
use app\index\job\Order;

class Pay extends \app\BaseController
{
    /**
     * 发起支付
     */
    public function submit()
    {
        $data = Request::param('','','strip_tags');
        
        
        if(empty($data['notify_url']))
        {
            View::assign('error_tips', "异步通知不可为空");
            View::assign('error_url', '/');
            return $this->fetch();
        }
        if(empty($data['return_url']))
        {
            View::assign('error_tips', "同步通知不可为空");
            View::assign('error_url', '/');
            return $this->fetch();
        }
        
        //如果异步回调地址为空 则进入或者同步回调地址
        if(empty($data['notify_url'])){
            preg_match('/(https?:\/\/)?((?:[a-zA-Z0-9-]+\.)+[a-zA-Z]{2,6}|[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+):?(\d+)?(\/\S*)?/', $data['return_url'], $matches);
            $protocol = $matches[1] ? $matches[1] : 'http://'; // 添加默认值 http://
            $host = $protocol . $matches[2];
        }else if(empty($data['return_url']) && empty($data['notify_url'])){
            $host = '/';
        }else{
            preg_match('/(https?:\/\/)?((?:[a-zA-Z0-9-]+\.)+[a-zA-Z]{2,6}|[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+):?(\d+)?(\/\S*)?/', $data['notify_url'], $matches);
            $protocol = $matches[1] ? $matches[1] : 'http://'; // 添加默认值 http://
            $host = $protocol . $matches[2];
        }

        if(empty($data['pid']))
        {
            View::assign('error_tips', "PID不可为空");
            View::assign('error_url', $host);
            return $this->fetch();
        }
        
        if(empty($data['out_trade_no']))
        {
            View::assign('error_tips', "订单号不可为空");
            View::assign('error_url', $host);
            return $this->fetch();
        }
        if(empty($data['type']))
        {
            View::assign('error_tips', "支付类型不可为空");
            View::assign('error_url', $host);
            return $this->fetch();
        }
        if(empty($data['name']))
        {
            View::assign('error_tips', "商品名称不可为空");
            View::assign('error_url', $host);
            return $this->fetch();
        }
        if(empty($data['money']))
        {
            View::assign('error_tips', "金额不可为空");
            View::assign('error_url', $host);
            return $this->fetch();
        }
        
        $user = YpayUser::where('id',$data['pid'])->find();
        if(empty($user))
        {
            View::assign('error_tips', "商户不存在");
            View::assign('error_url', $host);
            return $this->fetch();
        }
        if($user['is_frozen']!=0)
        {
            View::assign('error_tips', "该用户已被冻结，请联系站长");
            View::assign('error_url', $host);
            return $this->fetch();
        }
        
        if(isset($user['vip_time'])){
            $time = strtotime($user['vip_time']);
            if($time<time())
            {
                View::assign('error_tips', "套餐已过期");
                View::assign('error_url', $host);
                return $this->fetch();
            } 
        }else{
            View::assign('error_tips', "未开通套餐");
            View::assign('error_url', $host);
            return $this->fetch();
        }
        
        if($data['money']<=0)
        {
            View::assign('error_tips', "金额错误");
            View::assign('error_url', $host);
            return $this->fetch();
        }
        if($data['money']< getConfig()['min_orderprice'])
        {
            View::assign('error_tips', "订单金额低于最低发起金额--".getConfig()['min_orderprice']."元");
            View::assign('error_url', $host);
            return $this->fetch();
        }
        if( $data['money'] > getConfig()['max_orderprice'])
        {
            View::assign('error_tips', "订单金额高于最高发起金额--".getConfig()['max_orderprice'] ."元");
            View::assign('error_url', $host);
            return $this->fetch();
        }
        if(strpos($data['name'],"=") !== false)
        {
            View::assign('error_tips', "商品名称违规");
            View::assign('error_url', $host);
            return $this->fetch();
        }
        $shield_key = getConfig()['shield_key'];
        if(isset($shield_key))
        {
            $weigui = explode('|',$shield_key);
            for($index=0;$index<count($weigui);$index++)
            {
                if(empty($weigui[$index]))
                {
                    continue;
                }
                if(strpos($data['name'],$weigui[$index]) !== false)
                {
                    $risk_data = [
                        'user_id' =>$data['pid'], 
                        'name' =>$data['name'],
                        'url' => $data['return_url']
                    ];
                    try {
                        Risk::create($risk_data);
                    }catch (\Exception $e){
                        View::assign('error_tips', getConfig()['shield_tips']);
                        View::assign('error_url', $host);
                        return $this->fetch();
                    }
                    View::assign('error_tips', getConfig()['shield_tips']);
                    View::assign('error_url', $host);
                    return $this->fetch();
                }
            }
        }
        
        $feilv_money = $data['money'] * $user['feilv'] / 100;
        if($user['money']<$feilv_money)
        {
            View::assign('error_tips', "账户余额不足,无法发起支付");
            View::assign('error_url', $host);
            return $this->fetch();
        }
        $epay = new epay();
        
        $isSign = $epay->verifySign($data,$user['user_key']); //生成签名结果
        if(!$isSign)
        {
            View::assign('error_tips', "验签失败,请检查PID或者Key是否正确");
            View::assign('error_url', $host);
            return $this->fetch();
        }
        $is_orderNo = Db::table('ypay_order')->where('out_trade_no', $data['out_trade_no'])->find();
        if($is_orderNo && $is_orderNo['account_id'] != 0)
        {
            View::assign('error_tips', "订单号重复,请重新发起");
            View::assign('error_url', $host);
            return $this->fetch();
        }
        
        $trade_no='YPay'.date("YmdHis").rand(11111,99999);
        
        $QR_row =  Db::name('ypay_account')->where('type',$data['type'])->where('user_id',$data['pid'])->where('status',1)->where('is_status',1)->orderRaw('rand()')->find();//随机获取通道
        if(empty($QR_row))
        {
            $paylist = Db::name('ypay_paylist')->where('user_id',$user['id'])->where('status',1)->order('id','desc')->find();

            if(!empty($paylist) && $paylist['type'] == 'epay')
                {
                    //转接订单
                    $orderdata = [
                        "pid"         => $data['pid'],//商户ID
                        "type"       => $data['type'],//支付方式
                        "out_trade_no"     => $data['out_trade_no'], //商户订单号
                        "notify_url" =>  $data['notify_url'],//异步通知地址
                        "return_url" =>  $data['return_url'],//同步通知地址
                        "name" => $data['name'], //商品名称
                        "money"      => $data['money'],//订单金额
                    ];
                    $res = Jialanshen::epay_zj($trade_no,$orderdata,$user);
                    //转接订单创建完毕
                    if($res)//进入转接流程
                    {
                        $request = \think\facade\Request::instance();
                        $notify_url = str_replace('/submit.php','',$request->root(true)).'/Notify/epay_notifyzj';
                        $return_url = str_replace('/submit.php','',$request->root(true)).'/Notify/epay_returnzj';
                        $datas = [
                            "pid"         => $paylist['pid'],//商户ID
                            "type"       => $data['type'],//支付方式
                            "out_trade_no"     => $data['out_trade_no'], //商户订单号
                            "notify_url" =>  $notify_url,//异步通知地址
                            "return_url" =>  $return_url,//同步通知地址
                            "name" => $data['name'], //商品名称
                            "money"      => $data['money'],//订单金额
                        ];
                        $epayzj = new epay($paylist['pid'],$paylist['key'],$paylist['url']);
                        $res = $epayzj->pagePay($datas);
                        echo($res);
                        die;
                    }
                    else
                    {
                        View::assign('error_tips', "订单创建失败请重试");
                        View::assign('error_url', $host);
                        return $this->fetch();
                    }
                }
            
            View::assign('error_tips', "暂无收款账号在线");
            View::assign('error_url', $host);
            return $this->fetch();
        }
        $action = $QR_row['code'];
        $res = Jialanshen::$action($trade_no,$QR_row,$data,$user);
        if($res)
        {
            exit("<script>window.location.href='/Pay/console?trade_no={$trade_no}';</script>");
        }
        else
        {
            View::assign('error_tips', "订单生成错误,请重新发起支付");
            View::assign('error_url', $host);
            return $this->fetch();
        }
    }
    
    public function console($trade_no='')
    {
        if (Request::isAjax()){
            $data = Request::param('','','strip_tags');
            $out_trade_no = $data['TradeNo'];
            if(empty($out_trade_no))
            {
                return json(['code'=>201,'msg'=>'订单号为空!']);
            }
            $order_row = Db::name('ypay_order')->where('out_trade_no', $out_trade_no)->find();
            $account = Db::name('ypay_account')->where('id', $order_row['account_id'])->find();
            if(empty($order_row))
            {
                return json(['code'=>201,'msg'=>'订单不存在!']);
            }
            
            if($order_row['status']==1)
            {
                $u = Jialanshen::creat_callback($order_row);
                return json(['code'=>200,'msg'=>'订单支付成功!','url'=>$u['return']]);
            }
            if($order_row['out_time']<time())
            {
                return json(['code'=>201,'msg'=>'订单超时!']);
            }
            $qr_row = Db::name('ypay_account')->where('id', $order_row['account_id'])->find();
            if(empty($qr_row))
            {
                return json(['code'=>201,'msg'=>'通道不存在!']);
            }
            if($qr_row['code']!='wxpay_cloudzs' && $qr_row['code']!='wxpay_skd')
            {
                if(getConfig()['create_qrCode'] == 1){
                    
                    $create_qrCode = '/qrcode.php?text=';
                }else{
                    $create_qrCode = 'http://minico.qq.com/qrcode/get?type=2&r=2&size=350&text=';
                }
                
                if($qr_row['code']=='qqpay_zg')
                {
                     if(!empty($order_row['qrcode']) && $order_row['qrcode'] != 'ewmLoading'){
                       $order_row['qrcode'] =$create_qrCode.(substr($order_row['qrcode'], 0, 4) !== 'http' ? $order_row['qrcode'] : urlencode($order_row['qrcode']) ); 
                       return json(['code'=>100,'msg'=>'二维码获取成功!','qr_url'=>$order_row['qrcode'],'h5_qrurl' =>$order_row['h5_qrurl'] ]);
                    }elseif($order_row['qrcode'] == 'ewmLoading'){
                        return json(['code'=>404,'msg'=>'二维码获取中!']);
                    }elseif($order_row['qrcode'] != 'ewmLoading'){
                        return json(['code'=>201,'msg'=>'二维码获取失败!']);
                    }
                    
                }else{
                    $order_row['qrcode'] =$create_qrCode.$order_row['qrcode'];
                }
                
            }
            return json(['code'=>100,'msg'=>'二维码获取成功!','qr_url'=>$order_row['qrcode']]);
        }
        $order = Db::name('ypay_order')->where('trade_no', $trade_no)->find();
        $basic = Db::name('ypay_userbasic')->where('user_id', $order['user_id'])->find();
        $acc = Db::name('ypay_account')->where('id', $order['account_id'])->find();
        
        // 监控倒计时
        $ms = $order['out_time']-time();
        
        // 获取超时地址
        if($basic['timeout_method'] == 1){
                    //如果异步回调地址为空 则进入或者同步回调地址
            if(empty($order['notify_url'])){
                preg_match('/(https?:\/\/)?((?:[a-zA-Z0-9-]+\.)+[a-zA-Z]{2,6}|[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+):?(\d+)?(\/\S*)?/', $order['return_url'], $matches);
                $protocol = $matches[1] ? $matches[1] : 'http://'; // 添加默认值 http://
                $timeout_url = $protocol . $matches[2];
            }else if(empty($order['return_url']) && empty($order['notify_url'])){
                $timeout_url = '/';
            }else{
                preg_match('/(https?:\/\/)?((?:[a-zA-Z0-9-]+\.)+[a-zA-Z]{2,6}|[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+):?(\d+)?(\/\S*)?/', $order['notify_url'], $matches);
                $protocol = $matches[1] ? $matches[1] : 'http://'; // 添加默认值 http://
                $timeout_url = $protocol . $matches[2];
            }

        }else{
            $timeout_url = $basic['timeout_url'];
        }
        
        View::assign('order',$order);
        View::assign('ms',$ms);
        View::assign('code',$acc['code']);
        View::assign('basic',$basic);
        View::assign('console_notity',$basic['console_notity']);
        View::assign('timeout_url',$timeout_url);
        View::assign('yuyin_tips',$basic['yuyin_tips']);
        View::assign('is_payPopUp',$basic['is_payPopUp']);
        return $this->fetch($basic['console_temp']);
    }
    
    //判断是安卓还是Ios
    public static function get_device_type()
    {
        //全部变成小写字母
        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        $type = 'other';
        //分别进行判断
        if(strpos($agent, 'iphone') || strpos($agent, 'ipad'))
        {
            $type = 'ios';
        } 
        if(strpos($agent, 'android'))
        {
            $type = 'android';
        }
        return $type;
    }
    
    public static function console_dopay($out_trade_no='')
    {
        if (Request::isAjax()){
            $data = Request::param('','','strip_tags');
            $out_trade_no = $data['TradeNo'];
            if(empty($out_trade_no))
            {
                return json(['code'=>0,'msg'=>'订单号为空!']);
            }
            $order_row = Db::name('ypay_recharge')->where('out_trade_no', $out_trade_no)->find();
            if(empty($order_row))
            {
                return json(['code'=>0,'msg'=>'订单不存在!']);
            }
            
            if($order_row['status']==1)
            {
                return json(['code'=>200,'msg'=>'订单支付成功!','url'=>Request::domain().'/Deal/Recharge']);
            }
            if($order_row['out_time']<time())
            {
                return json(['code'=>0,'msg'=>'订单超时!']);
            }
            if(getConfig()['create_qrCode'] == 1){
                    $create_qrCode = '/qrcode.php?text=';
            }else{
                    $create_qrCode = 'http://minico.qq.com/qrcode/get?type=2&r=2&size=250&text=';
            }
            $order_row['qrcode'] =$create_qrCode.$order_row['qrcode'];
            return json(['code'=>100,'msg'=>'二维码获取成功!','qr_url'=>$order_row['qrcode']]);
        }
        $order = Db::name('ypay_recharge')->where('out_trade_no', $out_trade_no)->find();
        $basic = Db::name('ypay_userbasic')->where('user_id', $order['user_id'])->find();
        $ms = $order['out_time']-time();
        $order['name'] = '在线充值';
        $order['truemoney'] = $order['money'];
        $order['type'] = 'alipay';
        View::assign('order',$order);
        View::assign('ms',180);
        View::assign('code','alipay_dmf');
        View::assign('console_notity',$basic['console_notity']);
        View::assign('timeout_url',$basic['timeout_url']);
        View::assign('yuyin_tips',$basic['yuyin_tips']);
        View::assign('is_payPopUp',$basic['is_payPopUp']);
        return $this->fetch('pay/newpay');
    }
    
    public function apisubmit()
    {
        $data = Request::param('','','strip_tags');
        if(empty($data['pid']))
        {
            return json(['code'=>201,'msg'=>'PID不可为空!']);
        }
        if(empty($data['out_trade_no']))
        {
            return json(['code'=>201,'msg'=>'订单号不可为空!']);
        }
        if(empty($data['type']))
        {
            return json(['code'=>201,'msg'=>'支付类型不可为空!']);
        }
        if(empty($data['notify_url']))
        {
            return json(['code'=>201,'msg'=>'异步通知不可为空!']);
        }
        if(empty($data['return_url']))
        {
            return json(['code'=>201,'msg'=>'同步通知不可为空!']);
        }
        if(empty($data['name']))
        {
            return json(['code'=>201,'msg'=>'商品名称不可为空!']);
        }
        if(empty($data['money']))
        {
            return json(['code'=>201,'msg'=>'金额不可为空!']);
        }
        $user = Db::table('ypay_user')->where('id',$data['pid'])->find();
        if(empty($user))
        {
            return json(['code'=>201,'msg'=>'商户不存在!']);
        }
        $time = strtotime($user['vip_time']);
        if($time<time())
        {
            return json(['code'=>201,'msg'=>'未开通套餐或套餐已过期!']);
        }
        if($data['money']<=0)
        {
            return json(['code'=>201,'msg'=>'金额错误!']);
        }
        if($data['money']< getConfig()['min_orderprice'])
        {
            return json(['code'=>201,'msg'=>'订单金额低于最低发起金额!']);
        }
        if( $data['money'] > getConfig()['max_orderprice'])
        {
            return json(['code'=>201,'msg'=>'订单金额高于最高发起金额!']);
        }
        if(!empty(getConfig()['shield_key']))
        {
            $weigui = explode('|',getConfig()['shield_key']);
            for($index=0;$index<count($weigui);$index++)
            {
                if(empty($weigui[$index]))
                {
                    continue;
                }
                if(strpos($data['name'],$weigui[$index]) !== false)
                {
                    $risk_data = [
                        'user_id' =>$data['pid'], 
                        'name' =>$data['name'],
                        'url' => $data['return_url']
                    ];
                    try {
                        Risk::create($risk_data);
                    }catch (\Exception $e){
                        return json(['code'=>201,'msg'=>'商品违规,已记录!']);
                    }
                    return json(['code'=>201,'msg'=>'商品违规,已记录!']);
                }
            }
        }
        
        

        $feilv_money = $data['money'] * $user['feilv'] / 100;
        if($user['money']<$feilv_money)
        {
            return json(['code'=>201,'msg'=>'账户余额不足,无法发起支付!']);
        }
        
        
        
        $epay = new epay();
        $isSign = $epay->verifySign($data,$data["sign"]); //生成签名结果
        if(!$isSign)
        {
            return json(['code'=>201,'msg'=>'验签失败,请检查PID或者Key是否正确!']);
        }
        $is_orderNo = Db::table('ypay_order')->where('out_trade_no', $data['out_trade_no'])->find();
        if($is_orderNo)
        {
            return json(['code'=>201,'msg'=>'订单号重复,请重新发起!']);
        }

        $trade_no='YPay'.date("YmdHis").rand(11111,99999);
        
        $QR_row =  Db::name('ypay_account')->where('type',$data['type'])->where('user_id',$data['pid'])->where('status',1)->where('is_status',1)->orderRaw('rand()')->find();//随机获取通道
        if(empty($QR_row))
        {
            return json(['code'=>201,'msg'=>'暂无收款账号在线!']);
        }
        $action = $QR_row['code'];
        $res = Jialanshen::$action($trade_no,$QR_row,$data,$user);
        if($res)
        {
            $order = Db::name('ypay_order')->where('trade_no', $trade_no)->find();
            if(getConfig()['create_qrCode'] == 1){
                $create_qrCode = Request::domain() .'/qrcode.php?text=';
            }else{
                $create_qrCode = 'http://minico.qq.com/qrcode/get?type=2&r=2&size=250&text=';
            }
            $data = array(
                    'code'=> 200,
                    'msg'=>'获取成功!',
                    'trade_no'=>$order['trade_no'],
                    'qrcode'=>$order['qrcode'],
                    'h5_qrurl'=>$order['h5_qrurl'],
                    'type'=>$order['type'],
                    'out_trade_no'=>$order['out_trade_no'],
                    'money'=>$order['truemoney'],
                    'code_url' =>  $create_qrCode . $order['qrcode'],
                );
            return json($data);
        }
        else
        {
            $data = array(
                    'code'=>201,
                    'msg'=>'订单生成错误,请重新发起支付!',
                );
            return json($data);
        }
    }
    
    //订单查询 $trade_no:订单号 $type:订单号类型
    public function chaorder($order_no = '',$type = '')
    {
        if(empty($order_no))
        {
            return json(['code'=>201,'msg'=>'请输入订单号!']);
        }
        if(empty($type)){
            return json(['code'=>201,'msg'=>'请输入订单号类型!']);
        }
        
        //判断查询订单类型
        if($type == 1){
            $order = Db::name('ypay_order')->where('trade_no', $order_no)->find();
        }else{
           $order = Db::name('ypay_order')->where('out_trade_no', $order_no)->find(); 
        }
        
        //判断是否有此订单信息
        if(empty($order)){
            return json(['code'=>201,'msg'=>'此订单号不是有效订单号',]);
        }
        //整理返回数据集
        $data = 
        [
            'id' => $order['user_id'],//商户ID
            'type' => $order['type'], //支付类型
            'trade_no' => $order['trade_no'],//商户订单号
            'out_trade_no' => $order['out_trade_no'],//本地订单号
            'name' => $order['name'],//商品名称
            'money' => $order['money'],//商品金额
            'status' => $order['status'],//商品付款状态
            'notify_url' => $order['notify_url'],//异步回调地址
            'return_url' => $order['return_url'],//同步回调地址
        ];
        return json(['code'=>200,'msg'=>'获取成功!','data'=>$data]);
    }
    
    
    //MAPI接口下单
    public static function mapi(){
        
        //获取订单参数
        $data = Request::param('','','strip_tags');
        
        if(empty($data['pid']))
        {
            return json(['code'=>201,'msg'=>'PID不可为空!']);
        }
        if(empty($data['out_trade_no']))
        {
            return json(['code'=>201,'msg'=>'订单号不可为空!']);
        }
        if(empty($data['type']))
        {
            return json(['code'=>201,'msg'=>'支付类型不可为空!']);
        }
        if(empty($data['notify_url']))
        {
            return json(['code'=>201,'msg'=>'异步通知不可为空!']);
        }
        if(empty($data['return_url']))
        {
            return json(['code'=>201,'msg'=>'同步通知不可为空!']);
        }
        if(empty($data['name']))
        {
            return json(['code'=>201,'msg'=>'商品名称不可为空!']);
        }
        if(empty($data['money']))
        {
            return json(['code'=>201,'msg'=>'金额不可为空!']);
        }
        $user = Db::table('ypay_user')->where('id',$data['pid'])->find();
        if(empty($user))
        {
            return json(['code'=>201,'msg'=>'商户不存在!']);
        }
        $time = strtotime($user['vip_time']);
        if($time<time())
        {
            return json(['code'=>201,'msg'=>'未开通套餐或套餐已过期!']);
        }
        if($data['money']<=0)
        {
            return json(['code'=>201,'msg'=>'金额错误!']);
        }
        if($data['money']< getConfig()['min_orderprice'])
        {
            return json(['code'=>201,'msg'=>'订单金额低于最低发起金额!']);
        }
        if( $data['money'] > getConfig()['max_orderprice'])
        {
            return json(['code'=>201,'msg'=>'订单金额高于最高发起金额!']);
        }
        if(!empty(getConfig()['shield_key']))
        {
            $weigui = explode('|',getConfig()['shield_key']);
            for($index=0;$index<count($weigui);$index++)
            {
                if(empty($weigui[$index]))
                {
                    continue;
                }
                if(strpos($data['name'],$weigui[$index]) !== false)
                {
                    $risk_data = [
                        'user_id' =>$data['pid'], 
                        'name' =>$data['name'],
                        'url' => $data['return_url']
                    ];
                    try {
                        Risk::create($risk_data);
                    }catch (\Exception $e){
                        return json(['code'=>201,'msg'=>'商品违规,已记录!']);
                    }
                    return json(['code'=>201,'msg'=>'商品违规,已记录!']);
                }
            }
        }
        
        $feilv_money = $data['money'] * $user['feilv'] / 100;
        if($user['money']<$feilv_money)
        {
            return json(['code'=>201,'msg'=>'账户余额不足,无法发起支付!']);
        }

        $epay = new epay();
        $isSign = $epay->verifySign($data,$user['user_key']); //生成签名结果
        if(!$isSign)
        {
            return json(['code'=>201,'msg'=>'验签失败,请检查PID或者Key是否正确!']);
        }
        $is_orderNo = Db::table('ypay_order')->where('out_trade_no', $data['out_trade_no'])->find();
        if($is_orderNo)
        {
            return json(['code'=>201,'msg'=>'订单号重复,请重新发起!']);
        }

        $trade_no='YPay'.date("YmdHis").rand(11111,99999);
        
        $QR_row =  Db::name('ypay_account')->where('type',$data['type'])->where('user_id',$data['pid'])->where('status',1)->where('is_status',1)->orderRaw('rand()')->find();//随机获取通道
        if(empty($QR_row))
        {
            return json(['code'=>201,'msg'=>'暂无收款账号在线!']);
        }
        $action = $QR_row['code'];
        $res = Jialanshen::$action($trade_no,$QR_row,$data,$user);
        if($res)
        {
            $order = Db::name('ypay_order')->where('trade_no', $trade_no)->find();
            if(getConfig()['create_qrCode'] == 1){
                $create_qrCode = Request::domain() .'/qrcode.php?text=';
            }else{
                $create_qrCode = 'http://minico.qq.com/qrcode/get?type=2&r=2&size=250&text=';
            }
            //根据类型返回QrCode
            if($data['type'] == 'alipay'){
                $qrcode =  urldecode($order['qrcode']);
            }else if($data['type'] == 'wxpay'){
                $qrcode =  $order['qrcode'];
            }else if($data['type'] == 'qqpay'){
                $qrcode =  $create_qrCode . $order['qrcode'];
            }
            
            $data = array(
                'code'=> 1,
                'msg'=>'获取成功!',
                'trade_no'=>$order['trade_no'],
                'qrcode'=> $qrcode
            );
            return json($data);
        }
        else
        {
            $data = array(
                    'code'=>201,
                    'msg'=>'订单生成错误,请重新发起支付!',
                );
            return json($data);
        }
    }
    
}
