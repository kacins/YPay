<?php


namespace app\index\controller;
use think\facade\Config;
use think\facade\Db;
use app\common\service\YpayRecharge;
use app\common\service\YiPay as epay;
use app\common\model\YpayUser as M;
use think\facade\View;
use think\facade\Request;

class Demo extends \app\BaseController
{

public function isMobile()
 {
       if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
        return true;
    }
    if (isset($_SERVER['HTTP_VIA'])) {
        return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
    }
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $clientkeywords = array('nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp', 'sie-', 'philips', 'panasonic', 'alcatel', 'lenovo', 'iphone', 'ipod', 'blackberry', 'meizu', 'android', 'netfront', 'symbian', 'ucweb', 'windowsce', 'palm', 'operamini', 'operamobi', 'openwave', 'nexusone', 'cldc', 'midp', 'wap', 'mobile');
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
            return true;
        }
    }
    if (isset($_SERVER['HTTP_ACCEPT'])) {
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'textml') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'textml')))) {
            return true;
        }
    }
    return false;
  }
    public function index()
    {
        if(self::isMobile()){
            $list = Db::table('ypay_navs')->where('status', 1)->order('id','asc')->select();
            View::assign(['nav' => $list,]);
            $array = self::get_MobilePayButton();
            if($array == 'no'){
                    View::assign('error_tips', "未开启测试支付");
                    View::assign('error_url', "/");
                    return $this->fetch('pay/submit');
            }
            View::assign(['array' => $array]);
            return $this->fetch('mobile',$this->getSystem());
        }else{
            $array = self::get_PcPayButton();
            if($array == 'no'){
                    View::assign('error_tips', "未开启测试支付");
                    View::assign('error_url', "/");
                    return $this->fetch('pay/submit');
            }
            View::assign(['array' => $array]);
            return $this->fetch('',$this->getSystem());
        }
        
    }
    
    //获取Pc界面支付按钮
    public static function get_PcPayButton(){
        //获取配置信息
        $config = getConfig();
        $array = 
        [
            ['id' => 'wxpay', 'class' => 'wechat_pay','name' => '微信支付','src' => '/static/index/images/demo/wxpay.png','style' => 'margin:0 auto;width:110px','isOpen' => 'yes'],
            ['id' => 'alipay' ,'class' => 'ali_pay','name' => '支付宝','src' => '/static/index/images/demo/alipay.svg','style' => 'margin:0 auto;width:80px','isOpen' => 'yes'],
            ['id' => 'qqpay' ,'class' => 'qq_pay','name' => 'QQ支付','src' => '/static/index/images/demo/qqpay.png','style' => 'margin:0 auto;width:110px','isOpen' => 'yes'],
        ];
    
        
        foreach ($array as $key => $value){
            if(strpos($config['diy_demoPay'], $value['id']) === false){
                $array[$key]['isOpen'] = 'no'; 
            }
        }
        
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
        
        return $array;
    }
    
    //获取手机端界面支付按钮
    public static function get_MobilePayButton(){
        //获取配置信息
        $config = getConfig();
        $array = 
        [
            ['id' => 'wxpay', 'name' => '微信支付','src' => '/static/index/images/demo/wxpay-icon.svg','isOpen' => 'yes'],
            ['id' => 'alipay' ,'name' => '支付宝','src' => 'static/index/images/demo/alipay-icon.svg','isOpen' => 'yes'],
            ['id' => 'qqpay' ,'name' => 'QQ支付','src' => '/static/index/images/demo/qq.webp','isOpen' => 'yes'],
        ];
    
        
        foreach ($array as $key => $value){
            if(strpos($config['diy_demoPay'], $value['id']) === false){
                $array[$key]['isOpen'] = 'no'; 
            }
        }
        
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
        
        return $array;
    }
    
    public function demo_success(){
        return $this->fetch('',$this->getSystem());
    }
    
    public function dopay()
    {
        //提交参数
        $data = Request::param('','','strip_tags');
        //获取配置参数
        $config = getConfig();
        $request = \think\facade\Request::instance();
        
        //判断是否为空,为空则提示配置
        if(empty($config['epayid_demo']) || empty($config['epaykey_demo']) || empty($config['epayurl_demo'])){
            View::assign('error_tips', "测试支付信息未配置好");
            View::assign('error_url', "/");
            return $this->fetch('/pay/submit');
        }
        
        $creat_data = 
        [
            "type"       => $data['type'],
            "out_trade_no"     => $data['out_trade_no'],
            "user_id" => $config['epayid_demo'],
            "status" => 0, //商品名称
            "money"      => $config['demopay_money'],//订单金额
        ];
        YpayRecharge::goAdd($creat_data);
        $datas = [
            "pid"         => $config['epayid_demo'],//商户ID
            "type"       => $data['type'],//支付方式
            "out_trade_no"     => $data['out_trade_no'], //商户订单号
            "notify_url" =>  $request->root(true).'/Demo/notify_epay',//异步通知地址
            "return_url" =>  $request->root(true).'/Demo/return_epay',//同步通知地址
            "name" => $config['demopay_name'], //商品名称
            "money"      => $config['demopay_money'],//订单金额
        ];
        $epay = new epay($config['epayid_demo'],$config['epaykey_demo'],$config['epayurl_demo']);
        $res = $epay->pagePay($datas);
        echo($res);
        die;
        //return $this->fetch();
    }
    
     //异步通知
    public function notify_epay()
    {
        $data = Request::param('','','strip_tags');
        $user_key = getConfig()['epaykey_demo'];
        $epay = new epay();
        $isSign = $epay->verifySign($data,$user_key); //生成签名结果
        if(!$isSign)
        {
            echo 'fail'; die;
        }
        else
        {
            $ods = Db::name('ypay_recharge')->where('out_trade_no', $data['out_trade_no'])->find();
            if($ods['status']==0)
            {
                //变更订单状态并且给客户加款
                Db::name('ypay_recharge')->where('id', $ods['id'])->update(['status' =>1,'end_time'=>date('Y-m-d H:i:s', time())]);
                M::money($ods['money'],$ods['user_id'], getConfig()['demopay_name']);
                echo 'success'; die;
            }
            else
            {
                echo 'error'; die;
            }
        }
    }
    
    //充值同步通知
    public function return_epay()
    {
        $data = Request::param('','','strip_tags');
        $user_key = getConfig()['epaykey_demo'];
        $epay = new epay();
        $isSign = $epay->verifySign($data,$user_key); //生成签名结果
        if(!$isSign)
        {
            echo 'fail'; die;
        }
        else
        {
            $ods = Db::name('ypay_recharge')->where('out_trade_no', $data['out_trade_no'])->find();
            if($ods['status']==0)
            {
                //变更订单状态并且给客户加款
                Db::name('ypay_recharge')->where('id', $ods['id'])->update(['status' =>1,'end_time'=>date('Y-m-d H:i:s', time())]);
                M::money($ods['money'],$ods['user_id'], getConfig()['demopay_name']);
                return redirect(Request::root().'/Demo/demo_success');
            }
            else
            {
                return redirect(Request::root().'/Demo/demo_success');
            }
        }
    }
  
}
