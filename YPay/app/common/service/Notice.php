<?php
declare (strict_types = 1);

namespace app\common\service;
use think\facade\Db;
use think\facade\Request;
use think\facade\Config;
use app\common\util\Wxpusher as wxpusher;
use app\common\model\YpayUser as user;
use app\common\model\YpayUserbasic as basic;
use app\common\model\YpayAccount as Account;
use app\common\model\AdminChannel as Channel;
use app\common\util\Mail;
use think\facade\Cache;

class Notice
{
    //公共发件方法 1.通知类型 2.站点配置信息 3.发信内容 4.发信标题 5.用户信息 6.用户配置
    public static function core($switch,$config = '',$diy = '',$summary = '',$userinfo = '',$basic = ''){
        
        if($switch == 'domain_tips' || $switch == 'ticket_tips'){
            Mail::go($config['adminMail'],$summary,$diy);
        }elseif($switch == 'ticket_user'){
            Mail::go($userinfo['email'],$summary,$diy);
        }else{

            // 根据通知类型发送不同通知
            switch ($basic[$switch]) {
                case 'email':
                    if($config['email_switch'] == 1){
                        if(!empty($userinfo['email'])){
                            Mail::go($userinfo['email'],$summary,$diy);
                        }
                    }
                    break;
                case 'wxpusher':
                    if($config['wxpusher_switch'] == 1){
                        $Token = $config['wxpusher_appToken'];//获取WxPuserToken
                        $wxpusher = new wxpusher($Token);
                        $wxpusher->send($diy,$summary,'1','true',$userinfo['wxpusher_uid']);
                    }
                    break;
                default:
                    // code...
                    break;
            }
        }
    }
    
    //获取验证码
    public static function getCode($email,$title,$code){
        $config = getConfig();//获取配置参数
        $diy = $config['diy_codeTemp'];//获取自定义参数
        $code = strval($code);
        $diy = str_replace('[code]',$code,$diy);
        $res = Mail::go($email,$title,$diy);
        return $res;
    }
    
    //新用户注册邮箱通知
    public static function register_tips($data){
        
        //邮箱不能为空
        if(!empty($data['email'])){
            $config = getConfig();//获取配置参数
            $summary =  '注册成功';
            $diy = $config['diy_regTips'];//获取自定义参数
            $array = ["[userId]","[userName]","[register_ip]"];//定义自定义参数数组
            foreach($array as $value){
                if($value == "[userId]"){
                    $user = user::where('username',$data['username'])->find();
                    $temp = strval($user['id']);
                }
                if($value == "[userName]"){
                    $temp = strval($data['username']);
                }
                
                if($value == "[register_ip]"){
                    $temp = get_client_ip();
                }
                
                $diy = str_replace($value,$temp,$diy);
            }
            
            Mail::go($data['email'],$summary,$diy);
        }
    }
    
    // 新订单通知 $userinfo:用户信息 $data:订单数据 $basic:用户配置信息
    public static function order_tips($userinfo,$data,$basic){

        $config = getConfig();//获取配置参数
        //判断订单类型
        if($data['type'] == 'alipay'){
            $type = '支付宝';
        }elseif($data['type'] == 'wxpay'){
            $type = '微信';
        }elseif($data['type'] == 'lkl'){
            $type = '拉卡拉';
        }else{
            $type = 'QQ';
        }
        
        if($basic['order_tips'] == 'wxpusher'){
            $diy = '您在' . $config['sitename'] . '有新的订单啦,请及时处理!';
        }else{
            $diy = $config['diy_orderTips'];//获取自定义参数
        
            $array = ["[out_trade_no]","[name]","[money]","[type]","[account]","[create_time]","[end_time]"];//定义自定义参数数组
            
            foreach($array as $value){
                $str = trim($value, '[');
                $str = rtrim($str, ']');
                if($value == '[type]'){
                    $data[$str] = $type;
                }
                if($value == "[account]"){
                    //获取通道标识
                    $account = Account::where('id',$data['account_id'])->find();
                    //获取通道名称
                    $channel = Channel::where('code',$account['code'])->find();
                    $data[$str] = $channel['name'];
                }

                $diy = str_replace($value,$data[$str],$diy);
            } 
        }
        
       
        $summary = '购买成功通知';
        
        //调用公共方法
        self::core('order_tips',$config,$diy,$summary,$userinfo,$basic);
    }
    
    //账户登录提醒  $userinfo:用户信息 $basic:用户配置信息
    public static function login_tips($userinfo,$basic){
        $config = getConfig();//获取配置参数
        $summary =  '平台账户登录成功';
        $array = ["[login_time]","[login_ip]","[login_uid]","[login_name]"];//定义自定义参数数组
        $diy = $config['diy_loginTips'];//获取自定义参数
        foreach($array as $value){
            if($value == "[login_time]"){
                $temp = date('Y-m-d H:i:s', time());
            }
            
            if($value == "[login_ip]"){
                $temp = get_client_ip();
            }
            
            if($value == "[login_uid]"){
                $temp = strval($userinfo->id);
            }
            
            if($value == "[login_name]"){
                $temp = strval($userinfo->username);
            }
            
            $diy = str_replace($value,$temp,$diy);
        }
        
                //调用公共方法
        self::core('login_tips',$config,$diy,$summary,$userinfo,$basic);

    }
    
     //余额不足提醒  $userinfo:用户信息 $basic:用户配置信息
    public static function money_tips($userinfo,$basic){
        $config = getConfig();//获取配置参数
        $summary =  '余额不足提醒';
        $array = ["[userName]","[userId]","[money]"];//定义自定义参数数组
        $diy = $config['diy_moneyTips'];//获取自定义参数
        foreach($array as $value){
            if($value == "[userName]"){
                $temp = strval($userinfo['username']);
            }
            
            if($value == "[userId]"){
                $temp = strval($userinfo['id']);
            }
            
            if($value == "[money]"){
                $temp = strval($userinfo['money']);
            }
            
            $diy = str_replace($value,$temp,$diy);
        }
        //调用公共方法
        self::core('is_money_tips',$config,$diy,$summary,$userinfo,$basic);
    }
    
     //掉线提醒  $userinfo:用户信息 $basic:用户配置信息 $channelID:通道ID $type:通道类型 $notes:专属备注提示
    public static function lose_tips($userinfo,$basic,$channelID,$type,$notes = ''){
        $config = getConfig();//获取配置参数
        $summary = '通道掉线通知';
        
        //判断订单类型
        if($type == 'alipay'){
            $type = '支付宝';
        }elseif($type == 'wxpay'){
            $type = '微信';
        }elseif($type == 'lkl'){
            $type = '拉卡拉';
        }else{
            $type = 'QQ';
        }
        
        $array = ["[account_id]","[account_type]","[account_code]","[lose_time]"];//定义自定义参数数组
        $diy = $config['diy_loseTips'];//获取自定义参数
        foreach($array as $value){
            if($value == "[account_id]"){
                $temp = strval($channelID);
            }
            
            if($value == "[account_type]"){
                $temp = $type;
            }
            
            if($value == "[account_code]"){
                //获取通道标识
                $account = Account::where('id',$channelID)->find();
                //获取通道名称
                $channel = Channel::where('code',$account['code'])->find();
               $temp = $channel['name'];
            }
            
            if($value == "[lose_time]"){
                $temp = date('Y-m-d H:i:s', time());
            }
            
            $diy = str_replace($value,$temp,$diy);
        }
        
        //调用公共方法
        self::core('lose_tips',$config,$diy,$summary,$userinfo,$basic);
    }
    
    //域名审核1:用户ID 2:用户类型
    public static function domain_tips($userID){
        $config = getConfig();//获取配置参数
        
        //判断站长邮箱是否为空
        if(!empty($config['adminMail'])){
            $summary = '域名审核通知';
            $diy = '您好！用户ID:'.$userID.'提交了域名过白,请您抓紧处理!';
            //调用公共方法
            self::core('domain_tips',$config,$diy,$summary);
        }
        
    }
    
    //工单信息 1:用户ID 2:用户类型
    public static function ticket_tips($userID,$type){
        $config = getConfig();//获取配置参数
        if($type == 'admin'){
            $user = user::where('id',$userID)->find();
            $summary = '工单通知';
            $diy = '您好！您的工单已被管理员回复,请前往用户中心查看!';
            //调用公共方法
            self::core('ticket_user',$config,$diy,$summary,$user);
        }else{
            $summary = '工单通知';
            $diy = '您好！用户ID:'.$userID.'提交了工单,请您抓紧处理!';
            //调用公共方法
            self::core('ticket_tips',$config,$diy,$summary);
        }
    }
    
    //邮件发信
    public static function userEmail($data){
        switch ($data['type']) {
            case '1':
                $user = user::select();
                $count = user::count();
                
                $i = 0;
                
                foreach ($user as $key => $value) {
                    if(!empty($value['email'])){
                        Mail::go($value['email'],$data['title'],$data['content']);
                    }
                    $i++;
                    $num = $i / $count * 100;
                    $num = sprintf("%.2f", $num);
                    Cache::set('email_progress',$num);
                    sleep(2);
                }
                return ['msg' => '发送成功','code' => 200];
                break;
            case '2':
                Mail::go($data['email'],$data['title'],$data['content']);
                Cache::set('email_progress',100);
                return ['msg' => '发送成功','code' => 200];
                break;
            case '3':
                $user = user::where('vip_id','>',0)->select();
                $count = user::where('vip_id','>',0)->count();
                $i = 0;
                foreach ($user as $key => $value) {
                    if(!empty($value['email'])){
                        Mail::go($value['email'],$data['title'],$data['content']);
                    }
                    
                    $i++;
                    $num = $i / $count * 100;
                    $num = sprintf("%.2f", $num);
                    Cache::set('email_progress',$num);
                    sleep(2);
                }
                return ['msg' => '发送成功','code' => 200];
                break;
        }
    }
}
