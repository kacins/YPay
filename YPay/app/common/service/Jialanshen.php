<?php
declare (strict_types = 1);

namespace app\common\service;
use think\facade\Db;
use think\facade\Request;
use app\common\model\YpayOrder as M;
use app\common\model\YpayUser as S;
use app\common\service\YpayUser;
use app\common\model\YpayUserbasic as basic;
use app\common\model\YpayVip as vip;
use app\common\service\YiPay as epay;
use app\common\service\Notice as notice;


class Jialanshen
{
    
    //支付异步同调方法
    public static function creat_callback($data)
    {
        $userinfo = S::where('id',$data['user_id'])->find();//获取用户信息
        $basic = basic::where('user_id',$data['user_id'])->find();//获取用户配置参数
        $order = Db::name('ypay_order')->where('id',$data['id'])->find();
        $signArr = 
        [
            'money' => $data['money'],
            'name' => $data['name'],
            'out_trade_no' => $data['out_trade_no'],
            'pid' => $data['user_id'],
            'trade_no' => $data['trade_no'],
            'trade_status' => 'TRADE_SUCCESS',
            'type' => $data['type']
        ];
        $sign = epay::makeSign($signArr,$userinfo['user_key']);
        $array=array('pid'=>$data['user_id'],'trade_no'=>$data['trade_no'],'out_trade_no'=>$data['out_trade_no'],'type'=>$data['type'],'name'=>$data['name'],'money'=>$data['money'],'trade_status'=>'TRADE_SUCCESS');
        

        if($data['status']==0)
        {
            if($order['is_order_tips'] == 0 && $basic['order_tips'] != 'close' && !empty($basic['order_tips'])){
                Db::name('ypay_order')->where('id', $data['id'])->update(['status' =>1,'is_order_tips'=>1,'end_time'=>date('Y-m-d H:i:s', time())]);
                $order = Db::name('ypay_order')->where('id', $data['id'])->find();
                //调用通知方法
                notice::order_tips($userinfo,$order,$basic);
            }else{
                Db::name('ypay_order')->where('id', $data['id'])->update(['status' =>1,'end_time'=>date('Y-m-d H:i:s', time())]);
            }
            S::money("-".$data['feilvmoney'],$data['user_id'], '商户费率扣除');
            if($basic['money_tips'] >= $userinfo['money'] && $basic['is_money_tips'] != 'close' && !empty($basic['is_money_tips'])){
                //调用通知方法
                notice::money_tips($userinfo,$basic);
            }
        }
        $urlstr=http_build_query($array);
        //更改订单状态,商户单号、结束时间
        if(strpos($data['notify_url'],'?'))
        {
            $url['notify']=$data['notify_url'].'&'.$urlstr.'&sign='.$sign.'&sign_type=MD5';
        }
        else
        {
            $url['notify']=$data['notify_url'].'?'.$urlstr.'&sign='.$sign.'&sign_type=MD5';
        }
        if(strpos($data['return_url'],'?'))
        {
            $url['return']=$data['return_url'].'&'.$urlstr.'&sign='.$sign.'&sign_type=MD5';
        }
        else
        {
            $url['return']=$data['return_url'].'?'.$urlstr.'&sign='.$sign.'&sign_type=MD5';
        }
		return $url;
    }
    
    //创建订单
    public static function create_order($trade_no,$QR_row,$data,$user,$type){
        //查询用户配置信息
        $basic = basic::where('user_id',$user['id'])->find();
        //获取用户VIP信息
        $vip = vip::where('id',$user['vip_id'])->find();
        
        //判断站点名称是否填写 未填写即默认为空
        if(empty($data['sitename'])){
            $data['sitename'] = "";
        }
        
        //判断是否开启了订单加费
        if(!empty($vip)){
            //需要会员组开启此功能和判断用户是否把费率承担改为他的客户
            if($vip['is_profiteer'] == 1 && $basic['is_rate'] == 1){
                $money = $data['money'] + ($data['money'] * $user['feilv'] / 100);
                $feilv_money = $data['money'] * $user['feilv'] / 100;
            }else{
                $money = $data['money'];
                $feilv_money = $data['money'] * $user['feilv'] / 100;
            }
        }else{
            $money = $data['money'];
            $feilv_money = $data['money'] * $user['feilv'] / 100;
        }
        
        //转换金额类型,且最多保留2位小数
        $money = floatval($money);
        $feilv_money = floatval($feilv_money);
        $money = round($money, 2);
        $feilv_money = round($feilv_money, 2); 

        $i = 1;
        
        //判断是否有相同金额的订单,有则加0.01 - 0.1
        while(true)
        {
            $ods = M::where('truemoney',$money)->where('status',0)->where('account_id',$QR_row['id'])->where('out_time','>',time())->order('id desc')->find();
            if(empty($ods))
            {
                break;
            }
            else
            {
                //没设置浮动金额则默认调用官方提供
                if(!empty($basic['floating_amount'])){
                        $arr = explode(",", $basic['floating_amount']);
                        $rand_keys=array_rand($arr,1);
                        $number=$arr[$rand_keys];
                        
                        //保留初始金额
                        if($i == 1){
                            $temp_money = $money;
                        }
                        $money = sprintf("%.2f",$money + floatval($number));
                        //适配递减规则 如果金额小于0 则进入官方定义付款组
                        if(0 > $money){
                            $arr=['0.01','0.02','0.03','0.04','0.05','0.06','0.07','0.08','0.09','0.1'];
                            $rand_keys=array_rand($arr,1);
                            $number=$arr[$rand_keys];
                            $money = $temp_money + floatval($number);
                            break;
                        }
                }else{
                        $arr=['0.01','0.02','0.03','0.04','0.05','0.06','0.07','0.08','0.09','0.1'];
                        $rand_keys=array_rand($arr,1);
                        $number=$arr[$rand_keys];
                        $money = $money + floatval($number);
                        break;
                    }
            }
            
            $i++;
        }
        //筛选类型
        switch ($type) {
            case 'alipay':
                    //判断通道模式
                    if($basic['channelMode'] == 1){
                        $qrcode = urlencode('https://ds.alipay.com/?from=pc&appId=20000116&actionType=toAccount&goBack=NO&amount=' . $money . '&userId='.$QR_row['zfb_pid'].'&memo=' . $data['out_trade_no']);
                        $h5url = 'alipayqr://platformapi/startapp?saId=10000007&qrcode='.urlencode('https://ds.alipay.com/?from=pc&appId=20000116&actionType=toAccount&goBack=NO&amount=' . $money . '&userId='.$QR_row['zfb_pid'].'&memo=' . $data['out_trade_no'] . '');
                    }
                    if($basic['channelMode'] == 2){
                        $qrcode = urlencode('https://ds.alipay.com/?from=pc&appId=20000116&actionType=toAccount&goBack=NO&amount=' . $money . '&userId='.$QR_row['zfb_pid']. '');
                        $h5url = 'alipayqr://platformapi/startapp?saId=10000007&qrcode='.urlencode('https://ds.alipay.com/?from=pc&appId=20000116&actionType=toAccount&goBack=NO&amount=' . $money . '&userId='.$QR_row['zfb_pid'].'');
                    }
                    
                    if($basic['channelMode'] == 3){
                        $qrcode = urlencode('https://ds.alipay.com/?from=pc&appId=20000116&actionType=toAccount&goBack=NO&userId='.$QR_row['zfb_pid']. '');
                        $h5url = 'alipayqr://platformapi/startapp?saId=10000007&qrcode='.urlencode('https://ds.alipay.com/?from=pc&appId=20000116&actionType=toAccount&goBack=NO&userId='.$QR_row['zfb_pid'].'');
                    }
                    
                    if($basic['channelMode'] == 4){

                        $qrcode = urlencode(Request::domain().'/url.php?user_id='.$QR_row['zfb_pid'].'&price='.$money.'&trade_no='.$data['out_trade_no']);
                        $h5url = 'alipayqr://platformapi/startapp?saId=20000032&url='.urlencode('alipayqr://platformapi/startapp?appId=20000123&actionType=scan&biz_data=%7B%22s%22%3A%22money%22%2C%22u%22%3A%22'.$QR_row['zfb_pid'].'%22%2C%22a%22%3A%22'.$money.'%22%2C%22m%22%3A%22'.$data['out_trade_no'].'%22%7D');

                    }
                break;
            case 'wxpay':
                //获取账户信息
                $account = Db::name('ypay_account')->where('id', $QR_row['id'])->find();
                if($QR_row['code'] == 'wxpay_dy' || $QR_row['code'] == 'wxpay_app' || $QR_row['code'] == 'wxpay_zg' || $QR_row['code'] == 'wxpay_cloudzs'){//判断是否是微信店员通道
                    $qrcode = $QR_row['qr_url'];
                }

                $h5url = 'weixin://'; 
                break;
            case 'qqpay':
                //获取账户信息
                $account = Db::name('ypay_account')->where('id', $QR_row['id'])->find();
                $qrcode = "ewmLoading";
                $qrcode = urlencode($qrcode);
                $h5url = '/'; 
                break;
        }
        
        //如果超时时间为空,则默认为180秒
        if(empty($basic['timeout_time'])){
            $basic['timeout_time'] = 180;
        }
        
        //如果超时时间大于后台设置最大超时时间则调用后台设置最大超时时间
        if($basic['timeout_time'] > getConfig()['timeout']){
            $basic['timeout_time'] = getConfig()['timeout'];
        }
        
        $mbLen = mb_strlen($data['name']);
    
    $strArr = [];
    for ($i = 0; $i < $mbLen; $i++) {
        $mbSubstr = mb_substr($data['name'], $i, 1, 'utf-8');
        if (strlen($mbSubstr) >= 4) {
            continue;
        }
        $strArr[] = $mbSubstr;
    }
        $data['name'] = implode('', $strArr);
        //创建订单实例
        $odmodels = [
            'name' => $data['name'],
            'sitename' => $data['sitename'],
            'type' => $type,
            'account_id' => $QR_row['id'],
            'trade_no' => $trade_no,
            'out_trade_no' => $data['out_trade_no'],
            'notify_url' => $data['notify_url'],
            'return_url' => $data['return_url'],
            'user_id' => $user['id'],
            'money' => $data['money'],
            'truemoney' => $money,
            'feilvmoney' => $feilv_money,
            'status' => '0',
            'create_time' => date('Y-m-d H:i:s', time()),
            'qrcode' => $qrcode,
            'h5_qrurl' => $h5url,
            'ip' => get_client_ip(),
            'out_time' => time() + $basic['timeout_time'],
        ];
        try {
            M::create($odmodels);
            return true;
        }catch (\Exception $e){
            return false;
        }
    }
    
    //支付宝个人免挂
    public static function alipay_grmg($trade_no,$QR_row,$data,$user)
    {
        $result = self::create_order($trade_no,$QR_row,$data,$user,'alipay');
        return $result;
    }
    
    //支付宝APP
    public static function alipay_app($trade_no,$QR_row,$data,$user)
    {
        $result = self::create_order($trade_no,$QR_row,$data,$user,'alipay');
        return $result;
    }
    
    //支付宝PC挂机
    public static function alipay_pc($trade_no,$QR_row,$data,$user)
    {
        $result = self::create_order($trade_no,$QR_row,$data,$user,'alipay');
        return $result;
    }
    

    //微信店员
    public static function wxpay_dy($trade_no,$QR_row,$data,$user)
    {
        $result = self::create_order($trade_no,$QR_row,$data,$user,'wxpay');
        return $result;
    }
    
        //微信APP
    public static function wxpay_app($trade_no,$QR_row,$data,$user)
    {
        $result = self::create_order($trade_no,$QR_row,$data,$user,'wxpay');
        return $result;
    }
    
    //微信自挂
    public static function wxpay_zg($trade_no,$QR_row,$data,$user)
    {
        $result = self::create_order($trade_no,$QR_row,$data,$user,'wxpay');
        return $result;
        
    }
    
        //QQ免挂版-软件
    public static function qqpay_cloud($trade_no,$QR_row,$data,$user)
    {
        $result = self::create_order($trade_no,$QR_row,$data,$user,'qqpay');
        return $result;
    }
    
        //QQ免挂版-本地
    public static function qqpay_mg($trade_no,$QR_row,$data,$user)
    {
        $result = self::create_order($trade_no,$QR_row,$data,$user,'qqpay');
        return $result;
    }
    
    //QQ自挂版
    public static function qqpay_zg($trade_no,$QR_row,$data,$user)
    {
        $result = self::create_order($trade_no,$QR_row,$data,$user,'qqpay');
        return $result;
    }
    
    
    /**
     * @掉线通知
     * @param [type] $id $type $channelID
     *
     * @return void
     */
     public  static function lose_expire($user,$basic,$channelID,$type,$notes = ''){
        Db::name('ypay_account')->where('id',$channelID)->update(['status' => 0 , 'create_time' => date('Y-m-d H:i:s', time())]);//掉线
        //调用通知方法 1.用户信息 2.用户配置参数 3.通道ID 4.通道类型 5.备注
        notice::lose_tips($user,$basic,$channelID,$type,$notes);
     }
    
    //支付宝当面付
    public static function alipay_dmf($trade_no,$QR_row,$data,$user)
    {
        $basic = basic::where('user_id',$user['id'])->find();
        $request = \think\facade\Request::instance();
        $notifyUrl = str_replace('/submit.php','',$request->root(true)).'/Notify/alipay_dmf';
        $appid = $QR_row['wxname'];//https://open.alipay.com 账户中心->密钥管理->开放平台密钥，填写添加了电脑网站支付的应用的APPID
        $signType = 'RSA2';//签名算法类型，支持RSA2和RSA，推荐使用RSA2
        $rsaPrivateKey=$QR_row['qr_url'];//商户私钥，填写对应签名算法类型的私钥，如何生成密钥参考：https://docs.open.alipay.com/291/105971和https://docs.open.alipay.com/200/105310
        $requestConfigs = array(
            'out_trade_no'=>$data['out_trade_no'],
            'total_amount'=>$data['money'], //单位 元
            'subject'=>$data['name'],  //订单标题
            'timeout_express'=>'3m'       //该笔订单允许的最晚付款时间，逾期将关闭交易。取值范围：1m～15d。m-分钟，h-小时，d-天，1c-当天（1c-当天的情况下，无论交易何时创建，都在0点关闭）。 该参数数值不接受小数点， 如 1.5h，可转换为 90m。
        );
        $commonConfigs = array(
            //公共参数
            'app_id' => $appid,
            'method' => 'alipay.trade.precreate',//接口名称
            'format' => 'JSON',
            'charset'=> 'utf-8',
            'sign_type'=>'RSA2',
            'timestamp'=>date('Y-m-d H:i:s'),
            'version'=>'1.0',
            'notify_url' => $notifyUrl,
            'biz_content'=>json_encode($requestConfigs),
        );
        $sign = Jialanshen::sign($rsaPrivateKey,Jialanshen::getSignContent($commonConfigs), $commonConfigs['sign_type']);
        if(!$sign)
        {
            return '密钥错误';
        }
        $commonConfigs["sign"] = $sign;
        $result = Jialanshen::curlPost('https://openapi.alipay.com/gateway.do?charset=utf-8',$commonConfigs);
        $json = json_decode($result,TRUE);
        $json = $json['alipay_trade_precreate_response'];
        
        if($json['code'] && $json['code']=='10000')
        {
            //生成成功，将订单数据添加到数据库并返回
            if(empty($data['sitename']))
            {
               $data['sitename'] = "";
            }
            $money = $data['money'];
            $qrcode = $json['qr_code'];
            $h5url = "alipayqr://platformapi/startapp?saId=10000007&qrcode=" .$json['qr_code'];
            $feilv_money = $data['money'] * $user['feilv'] / 100;
                    
            //如果超时时间为空,则默认为180秒
            if(empty($basic['timeout_time'])){
                $basic['timeout_time'] = 180;
            }
            
            //如果超时时间大于后台设置最大超时时间则调用后台设置最大超时时间
            if($basic['timeout_time'] > getConfig()['timeout']){
                $basic['timeout_time'] = getConfig()['timeout'];
            }
            $odmodels = [
                'name' => $data['name'],
                'sitename' => $data['sitename'],
                'type' => 'alipay',
                'account_id' => $QR_row['id'],
                'trade_no' => $trade_no,
                'out_trade_no' => $data['out_trade_no'],
                'notify_url' => $data['notify_url'],
                'return_url' => $data['return_url'],
                'user_id' => $user['id'],
                'money' => $data['money'],
                'truemoney' => $money,
                'feilvmoney' => $feilv_money,
                'status' => '0',
                'create_time' => date('Y-m-d H:i:s', time()),
                'qrcode' => $qrcode,
                'h5_qrurl' => $h5url,
                'ip' => get_client_ip(),
                'out_time' => time() + $basic['timeout_time'],
            ];
            try {
                M::create($odmodels);
                return true;
            }catch (\Exception $e){
                return false;
            }
        }
        else
        {
            return false;//返回失败信息
        }
    }

    
    public static function epay_zj($trade_no,$data,$user)
    {
         $basic = basic::where('user_id',$user['id'])->find();
        if(empty($data['sitename'])){
            $data['sitename'] = "";
        }
        $money = $data['money'];
        $feilv_money = $data['money'] * $user['feilv'] / 100;
        $odmodels = [
            'name' => $data['name'],
            'sitename' => $data['sitename'],
            'type' => $data['type'],
            'account_id' => 0,
            'trade_no' => $trade_no,
            'out_trade_no' => $data['out_trade_no'],
            'notify_url' => $data['notify_url'],
            'return_url' => $data['return_url'],
            'user_id' => $user['id'],
            'money' => $data['money'],
            'truemoney' => $money,
            'feilvmoney' => $feilv_money,
            'status' => '0',
            'create_time' => date('Y-m-d H:i:s', time()),
            'qrcode' => '',
            'h5_qrurl' => '',
            'ip' => get_client_ip(),
            'out_time' => time() + $basic['timeout_time'],
            'pla_type'=>2
        ];
        try {
            M::create($odmodels);
            return true;
        }catch (\Exception $e){
            return false;
        }
    }
    
    
    
    public static function sign($priKey,$data, $signType = "RSA") {
        error_reporting(0);
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($priKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
        ($res) or die('');
        if ("RSA2" == $signType) {
            openssl_sign($data, $sign, $res, version_compare(PHP_VERSION,'5.4.0', '<') ? SHA256 : OPENSSL_ALGO_SHA256); //OPENSSL_ALGO_SHA256是php5.4.8以上版本才支持
        } else {
            openssl_sign($data, $sign, $res);
        }
        $sign = base64_encode($sign);
        return $sign;
    }
    public static function checkEmpty($value) {
        if (!isset($value))
            return true;
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;
        return false;
    }
    public static function getSignContent($params) {
        ksort($params);
        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === Jialanshen::checkEmpty($v) && "@" != substr($v, 0, 1)) {
                // 转换成目标字符集
                $v = Jialanshen::characet($v, 'utf-8');
                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }
        unset ($k, $v);
        return $stringToBeSigned;
    }
    static function characet($data, $targetCharset) {
        if (!empty($data)) {
            $fileType = 'utf-8';
            if (strcasecmp($fileType, $targetCharset) != 0) {
                $data = mb_convert_encoding($data, $targetCharset, $fileType);
                //$data = iconv($fileType, $targetCharset.'//IGNORE', $data);
            }
        }
        return $data;
    }
    public static function curlPost($url = '', $postData = '', $options = array())
    {
        if (is_array($postData)) {
            $postData = http_build_query($postData);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //设置cURL允许执行的最长秒数
        if (!empty($options)) {
            curl_setopt_array($ch, $options);
        }
        //https请求 不验证证书和host
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
    public static function rsaCheck($params,$priKey) {
        $sign = $params['sign'];
        $signType = $params['sign_type'];
        unset($params['sign_type']);
        unset($params['sign']);
        return Jialanshen::verify($priKey,Jialanshen::getSignContent($params),$sign,$signType);
    }
    public static function verify($priKey,$data,$sign,$signType = 'RSA') {
        
        $res = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($priKey, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";
        ($res) or die('');
        //调用openssl内置方法验签，返回bool值
        if ("RSA2" == $signType) {
            $result = (bool)openssl_verify($data, base64_decode($sign), $res, version_compare(PHP_VERSION,'5.4.0', '<') ? SHA256 : OPENSSL_ALGO_SHA256);
        } else {
            $result = (bool)openssl_verify($data, base64_decode($sign), $res);
        }
        return $result;
    }
    
    
    
    
    
}
