<?php
declare (strict_types = 1);

namespace app\common\service;
use think\facade\Db;
use think\facade\Request;
use think\facade\Config;
use app\common\service\YiPay as epay;
use iboxs\payment\Client;
use iboxs\payment\Notify;
use app\common\model\YpayUser as User;
use app\common\model\YpayPaylist as PayBasic;


class Paylist
{
    //公共方法 1:订单数据 2:回调类型
    public static function core($data,$backType){
        $config = getConfig(); //获取配置数据
        
        $temp = self::corePaylist($data);//获取支付配置参数
        //根据配置类型筛选调用不同方法
        switch ($temp['type']) {
            case 'epay':
                    $epay = new epay();
                    $isSign = $epay->verifySign($data,$temp['key']); //生成签名结果
                    //判断回调方式
                    if($backType == "notify"){
                        self::epay_notify($data,$config,$isSign,'epay',$backType);//调用异步回调
                    }else if($backType == "return"){
                       return self::epay_return($data,$isSign);//调用同步回调
                    }
                break;
            case 'alipay':
                    return self::alipay_notify($data,$config,'alipay',$backType);//调用同步回调
                break;
            case 'dmf':
                    return self::alipay_notify($data,$config,'dmf',$backType);//调用同步回调
                break;
            case 'wxpay':
                    return self::wxpay_notify($data,$config,'wxpay');//调用同步回调
                break;
            default:
                // code...
                break;
        }
        
    }
    
    //获取支付配置数据 1.订单数据
    public static function corePaylist($data){
        $config = getConfig(); //获取配置数据
        
        //判断支付类型是否存在
        if(isset($data['type'])){
            if($data['type'] == 'wxpay'){
                $type = 'wechat';
            }else{
                $type = $data['type'];
            }
        }else{
            if(isset($data['method']) || isset($data['notify_type'])){
                $type = 'alipay';
            }
        }
        
        $payList = PayBasic::select(); //获取全部充值通道
        
        $temp = []; //定义接收数据数组
        
        //循环找到对应的支付通道配置
        foreach($payList as $key => $value){
            
            //判断是否和配置的支付ID一样且赋值
            if($value['id'] == $config[$type]){
                $temp = $payList[$key];
                break;
            }
        }
        return $temp;
    }
    
    //公共异步回调变更数据方法 1.充值数据 2.配置数据
    public static function upStatus($data,$config,$type){
            $recharge = Db::name('ypay_recharge')->where('out_trade_no', $data['out_trade_no'])->find();//获取充值订单参数
            //判断充值订单状态
            if($recharge['status']==0)
            {
                //变更订单状态并且给客户加款
                Db::name('ypay_recharge')->where('id', $recharge['id'])->update(['status' =>1,'end_time'=>date('Y-m-d H:i:s', time())]);
                User::money($recharge['money'],$recharge['user_id'], '商户在线充值');
                $user = Db::name('ypay_user')->where('id', $recharge['user_id'])->find();

                if($type == 'epay'){
                    echo 'success';die;
                }else{
                    echo 'success';
                }
                
            }
            else
            {
                if($type == 'epay'){
                    echo 'success';die;
                }else{
                    echo 'success';
                }
            }
        
    }
    
    //付费回调新增用户
    public static function addUser($data){
        $ods = Db::name('ypay_recharge')->where('out_trade_no', $data['out_trade_no'])->find();
        if($ods['status']==0)
        {
            //变更订单状态
            Db::name('ypay_recharge')->where('id', $ods['id'])->update(['status' =>1,'end_time'=>date('Y-m-d H:i:s', time())]);
            //创建用户
            try {
                User::create(json_decode($ods['regdata'],true));
                }catch (\Exception $e){
                    return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
                }
            
            echo 'success'; die;
        }
        else
        {
            echo 'success'; die;
        }
    }
    
    //微信/支付宝官方配置参数 1.充值数据
    public static function payConfig($data){

        $pay = self::corePaylist($data);//获取支付配置参数
        if($pay['type'] == 'wxpay'){
            //微信配置信息
            $config=[
                'mchid' =>$pay['other'], //商户号
                'apiKey' =>$pay['key'], //微信密钥
                'appid' => $pay['pid'],   //应用APPID
                'notify_url' => $data["notify_url"]??'',  //异步通知地址
                'return_url' => $data["return_url"]??'',   //同步通知地址
            ];
        }else{
            //支付宝配置信息
            $config=[
                'publicKey' =>$pay['other'], //支付宝公钥
                'rsaPrivateKey' =>$pay['key'], //商户私钥
                'appid' => $pay['pid'],   //应用APPID
                'notify_url' => $data["notify_url"]??'',  //异步通知地址
                'return_url' => $data["return_url"]??'',   //同步通知地址
                'charset' => "UTF-8",
                'sign_type'=>"RSA2",
                'gatewayUrl' =>"https://openapi.alipay.com/gateway.do"   //应用网关，若为沙箱环境则为："https://openapi.alipaydev.com/gateway.do"
            ];
        }
        return $config;
    }
    
    //易支付 - 1:数据 2:订单号
    public static function epay($data,$order_id){
        $pay = self::corePaylist($data);//获取支付配置参数
        $temp = 
        [
            "pid"         => $pay['pid'],//商户ID
            "type"       => $data['type'],//支付方式
            "out_trade_no"     => $order_id, //商户订单号
            "notify_url" =>  $data["notify_url"],//异步通知地址
            "return_url" =>  $data["return_url"],//同步通知地址
            "name" => "在线充值", //商品名称
            "money"      => $data['money'],//订单金额
        ];
        $epay = new epay($pay['pid'],$pay['key'],$pay['url']);
        $res = $epay->pagePay($temp);
        
        return $res;
    }
    
    //易支付异步回调 1:订单参数 2.站点配置参数 3.加密验签结果 4.支付类型 5:回调类型
    public static function epay_notify($data,$config,$isSign,$type,$backType){
        if($backType == 'register'){
            //判断签名是否正确
            if(!$isSign)
            {
                echo 'fail'; die;
            }
            else
            {
                self::addUser($data);//处理回调内容
            }
        }else{
            //判断签名是否正确
            if(!$isSign)
            {
                echo 'fail'; die;
            }
            else
            {
                self::upStatus($data,$config,$type);
            }
        }

    }
    
    //易支付同步回调 1:订单参数 2.加密验签结果
    public static function epay_return($data,$isSign){
        //判断签名是否正确
        if(!$isSign)
        {
            echo 'fail'; die;
        }
        else
        {
            $recharge = Db::name('ypay_recharge')->where('out_trade_no', $data['out_trade_no'])->find();//获取充值订单参数
            
            //判断充值订单状态
            if($recharge['status']==0)
            {
                return redirect(Request::root().'/Deal/Recharge');
            }
            else
            {
                return redirect(Request::root().'/Deal/Recharge');
            }
        }
    }
    
    //微信官方支付 - 1:数据 2:订单号
    public static function wxpay($data,$order_id){
        $config = self::payConfig($data);//获取支付配置参数
        $temp = self::corePaylist($data);//获取支付类型
        // 支付
        $order = [
            'out_trade_no' =>$order_id,
            'amount' => $data['money'],
            'order_name' => '在线充值',
        ];
        $wxpay = new Client($config);
        $wxpay = $wxpay->WxPayCode($order);
        return $wxpay;
    }
    
    //微信回调 1:数据 2:站点配置参数 3:支付类型
    public static function wxpay_notify($data,$basic,$type){
        
        $config = self::payConfig($data);//获取支付配置参数

        $result=Notify::WxPayNotify($config,$data);

        //判断回调状态
        if($result != false){
            self::upStatus($data,$basic,$type);//处理回调内容
            return redirect(Request::root().'/Deal/Recharge');
        }
        else
        {
            return redirect(Request::root().'/Deal/Recharge');
        }
    }
    
    //支付宝官方支付 - 1:数据 2:订单号
    public static function alipay($data,$order_id){
        $config = self::payConfig($data);//获取支付配置参数
        $temp = self::corePaylist($data);//获取支付类型
        // 支付
        $order = [
            'out_trade_no' =>$order_id,
            'amount' => $data['money'],
            'order_name' => '在线充值',
        ];
        if($temp['type'] == 'dmf'){
            $alipay = new Client($config);
            $alipay = $alipay->AlipayCode($order);
        }else{
            $alipay = new Client($config);
            $alipay = $alipay->AlipayWeb($order);
        }
        return $alipay;
    }
    
    //支付宝回调 1:数据 2:站点配置参数 3:支付类型
    public static function alipay_notify($data,$basic,$type,$backType = null){
        
        $config = self::payConfig($data);//获取支付配置参数

        $result=Notify::alipayNotify($config,$data);
        
        if($backType == 'register'){
            if($result){
                if($type == 'dmf' && $data['trade_status'] == 'TRADE_SUCCESS'){
                    self::addUser($data);//处理回调内容
                }else{
                    self::addUser($data);//处理回调内容
                }
            }
        }else{
            //判断回调状态
            if($result){
                if($type == 'dmf' && $data['trade_status'] == 'TRADE_SUCCESS'){
                    self::upStatus($data,$basic,$type);//处理回调内容
                }else{
                    self::upStatus($data,$basic,$type);//处理回调内容
                    return redirect(Request::root().'/Deal/Recharge');
                }
            }
            else
            {
                return redirect(Request::root().'/Deal/Recharge');
            } 
        }
    }

}
