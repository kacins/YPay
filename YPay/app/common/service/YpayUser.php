<?php
declare (strict_types = 1);

namespace app\common\service;
use think\facade\Session;
use think\facade\Cookie;
use think\facade\Cache;
use think\facade\Request;
use think\facade\Db;
use think\facade\Validate;
use app\common\model\YpayUser as M;
use app\common\validate\YpayUser as V;
use app\common\model\YpayVip;
use app\common\model\YpayOrder;
use app\common\model\MoneyLog;
use think\facade\Config;
use app\common\util\Sms;
use app\common\util\Mail;
use app\common\model\AdminFrontLog as Log;
use app\common\model\YpayRecharge as Recharge;
use app\common\model\YpayUserbasic as basic;
use app\common\model\YpayAccount as account;
use app\common\model\YpayDomain as domain;
use app\common\service\Notice as notice;

class YpayUser
{
    // 添加
    public static function goAdd($data)
    {
        //验证
        $validate = new V;
        if(!$validate->scene('add')->check($data))
        return ['msg'=>$validate->getError(),'code'=>201];
        try {
            M::create($data);
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }
     // 编辑
    public static function goEdit($data,$id)
    {
        $data['id'] = $id;
        //验证
        $validate = new V;
        if(!$validate->scene('edit')->check($data))
        return ['msg'=>$validate->getError(),'code'=>201];
      
        try {
             M::update($data);
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }
    
     // 编辑
    public static function goBasicEdit($data,$id)
    {
        
        //如果超时时间大于规定超时时间则提示
        if($data['timeout_time'] > getConfig()['timeout']){
            return ['msg'=>'超时时间不能大于规定数:'.getConfig()['timeout'],'code'=>201];
        }
        
        try {
             basic::where('user_id',$id)->update($data);
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }
    /*
     * 后台相关操作方法
     */
    
    // 添加用户
    public static function goUserAdd($data)
    {
        //验证
        $validate = new V;
        if(!$validate->scene('add')->check($data))
        return ['msg'=>$validate->getError(),'code'=>201];
        $data['password'] = set_password(trim($data['password']));
        $data['user_key'] = rand_string();
        if($data['vip_id'] != 0){
            $vip = YpayVip::where(['id'=>$data['vip_id']])->find();
            $data['vip_time'] = date("Y-m-d H:i:s",strtotime("+ ".$vip['viptime']." day"));
            $data['feilv'] = $vip['feilv'];
        }else{
            $data['vip_time'] =null; 
        }
        try {
            M::create($data);
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }
    
    // 编辑用户信息
    public static function goUserEdit($data,$id)
    {
        $data['id'] = $id;
        //验证
        $validate = new V;
        if(!$validate->scene('edit')->check($data))
        return ['msg'=>$validate->getError(),'code'=>201];
        if(!empty($data['password']) && $data['password']!="")
        {
            $data['password'] = set_password(trim($data['password']));
        }
        else
        {
            $yuser = M::find($id);
            $data['password'] = $yuser['password'];
        }
        if($data['vip_id'] != 0){
            $vip = YpayVip::where(['id'=>$data['vip_id']])->find();
            $data['vip_time'] = empty($data['vip_time']) ? date("Y-m-d H:i:s",strtotime("+ ".$vip['viptime']." day")):$data['vip_time'];
            $data['feilv'] = (empty($data['feilv']) && $data['feilv'] != 0 ) ? $vip['feilv']:$data['feilv'];
        }else{
            $data['vip_time'] = null;
            $data['feilv'] = null;
        }
        
        //判断费率是否填写正确
        if($data['vip_id'] != 0 && !preg_match('/^\d+(\.\d+)?$/',$data['feilv'])){
            return ['msg'=>'费率填写不正确','code'=>201];
        }
        //判断费率是否为空
        if($data['vip_id'] != 0 && $data['feilv'] != 0 && empty($data['feilv'])){
            return ['msg'=>'费率不能为空','code'=>201];
        }
          
            
        if($data['is_frozen'] == 0){
           $data['frozen_reason'] = null; 
        }
        
        try {
             M::update($data);
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }
    
    // 冻结/解冻账户
     public static function goFrozenStatus($data)
    {
        //查询是否有此用户
        $model =  M::find($data['id']);
        
        $data = 
        [
            'is_frozen' => $data['is_frozen'],
            'frozen_reason' => $data['frozen_reason'],
        ];
        
        if ($model->isEmpty())  return ['msg'=>'数据不存在','code'=>201];
        try{
            $model->save($data);
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }
    
    //邮箱发信
    public static function goEmail($data){
        if($data['type'] == 2 && empty($data['email'])){
           return ['msg'=>'请填写接受邮箱','code'=>201]; 
        }
        if(empty($data['title'])){
           return ['msg'=>'请填写邮件标题','code'=>201]; 
        }
        if(empty($data['content'])){
           return ['msg'=>'请填写邮件内容','code'=>201]; 
        }
        Notice::userEmail($data);
    }

    // 根据ID删除用户
    public static function goRemove($id)
    {
        $model = M::find($id);
        $account = account::where('user_id',$id)->select();
        if ($model->isEmpty()) return ['msg'=>'数据不存在','code'=>201];
        try{
           $model->delete();
           $account ->delete(1);
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }

    // 批量删除用户
    public static function goBatchRemove($ids)
    {
        if (!is_array($ids)) return ['msg'=>'数据不存在','code'=>201];
        try{
            M::destroy($ids);
            foreach ($ids as $temp){
               basic::where('user_id',$temp)->delete(); 
               account::where('user_id',$temp)->delete();
            }
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }
    
    /*
     * 前台相关操作方法
     */
    
    //获取邮箱/手机号/微信通知方式
    public static function emwModel(){
        $config = getConfig();//获取配置参数
        //定义登录类型
        $array = 
        [
            ['id'=>'email','name'=>'邮 箱','icon' => 'fa-solid fa-envelope-open-text','user_class' => 'width:26.25px;','isOpen'=>'yes'],
            ['id'=>'wxpusher_uid','name'=>'WxPusher','icon' => 'fa-brands fa-pushed','user_class' => 'width:26.25px;','isOpen'=>'yes'],
        ];
        foreach ($array as $key => $value){
            if($config['email_switch'] != 1 && $value['id'] == 'email'){
                $array[$key]['isOpen'] = 'no';
            }
            if($config['wxpusher_switch'] != 1 && $value['id'] == 'wxpusher_uid'){
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
            return 'no';
        }
        return $array;
    }
    
    // 用户登录
    public static function login(array $data)
    {
        //getConfig() 获取所有系统配置参数
        // getConfig()['captcha-type'] 获取验证码类型 Tips: 0:关闭验证码/1:普通验证码/2:腾讯防水墙
        // getConfig()['logincode-type'] 获取登录方式类型 Tips: 0:用户名+密码登录/1:手机号登录/2:邮箱登录
        $config = getConfig();
        $captcha_type = getConfig()['captcha-type'];
        $logincode_type = getConfig()['logincode-type'];
        $validate = new V;
        $user = null;
         //调用验证方法数据方法/传入验证码类型和验证码参数
        $ordinary_captcha = empty($data['ordinary_captcha']) ? "":$data['ordinary_captcha'];
        //获取验证验证码信息
        $is_captcha = self::is_captcha($captcha_type,$ordinary_captcha);
        //判断是否有值返回
        if(!empty($is_captcha)){
           return  $is_captcha;
        }
        // 判断登录类型切获取用户数据
        switch ($logincode_type) {
            case 0:
                //验证数据是否填写
                if(!$validate->scene('login')->check($data))return ['msg'=>$validate->getError(),'code'=>201];
                //验证是否存在此用户
                $user = M::where([
                    'username' => trim($data['username']),
                    'password' => set_password(trim($data['password']))
                ])->find();
                break;
            case 1:
                //验证数据是否填写
                if(!$validate->scene('mobile')->check($data))return ['msg'=>$validate->getError(),'code'=>201];
                $code = Cache::get('captcha'); ;
                if($data['captcha']==$code)//验证通过
                {
                    $user = M::where([
                    'mobile' => trim($data['mobile'])
                    ])->find();
                }else
                {
                    return ['msg'=>'验证码错误','code'=>201];
                }
                break;
                
            default:
                //验证数据是否填写
                if(!$validate->scene('email')->check($data))return ['msg'=>$validate->getError(),'code'=>201];
                $code = Cache::get('captcha');
                if($data['captcha']==$code)//验证通过
                {
                    $user = M::where([
                    'email' => trim($data['email'])
                    ])->find();
                }
                else
                {
                    return ['msg'=>'验证码错误','code'=>201];
                }
                break;
        }
        
        //判断账户密码是否正确
        if(!$user){return ['msg'=>'用户名/密码错误','code'=>201];} 
        
        //判断该账户是否被冻结
        if($user['is_frozen']) return ['msg'=>$user['frozen_reason'],'code'=>201];
    

        $user->token = rand_string().$user->id.microtime(true);
        $user->save();
        //是否记住密码
        $time = 3600;
        if (isset($data['remember'])) $time = 7 * 86400;
        //缓存登录信息
        $info = [
            'id' => $user->id,
            'token' => $user->token
        ];
        Session::set('front', $info);
        Cookie::set('front_token',$user->token, $time);
        //记录登录日志
        $info = [
           'uid'       => $user->id,
           'url'      => Request::url(),
           'desc'    => '商户登录成功', 
           'ip'       => get_client_ip(),
           'user_agent'=> Request::server('HTTP_USER_AGENT')
        ];
        Log::create($info);

        return ['msg'=>'登录成功','code'=>200];
    }
    
    // 用户登录通知
    public static function login_tips()
    {
        $basic = basic::where('user_id',self::getUserId())->find();
        //开启了登录提醒
        if(!empty($basic['login_tips']))
        {
            notice::login_tips(self::getUser(),$basic);
        }
     
    }
    
        
    //注册成功发送注册成功邮件
    public static function register_tips($data){
        //注册成功通知信息模板
        notice::register_tips($data);
    }
    
    // 用户注册
    public static function register($data)
    {
        return self::reg_or_bind('reg',$data);
     
    }
    
    // 快捷登录绑定信息
    public static function bind($data)
    {
         return self::reg_or_bind('bind',$data);

    }
    
    // 找回密码
    public static function golostpwd()
     {
        // getConfig()['captcha-type'] 获取验证码类型 Tips: 0:关闭验证码/1:普通验证码/2:腾讯防水墙
        // getConfig()['retrieve-type'] 获取找回方式类型 Tips: 0:关闭/1:手机号找回/2:邮箱找回
        $captcha_type = getConfig()['captcha-type'];
        $retrieve_type = getConfig()['retrieve-type'];
        $code = Cache::get('captcha'); // 获取验证码信息
        $data = Request::post(); // 获取传递参数
        // 验证
        $validate = new V;
        
        //判断验证码是否正确
        if($retrieve_type != 0)
        {
            if($retrieve_type == 1){
                $where = ['mobile'=>$data['mobile']];
                if(!$validate->scene('mobile')->check($data))return ['msg'=>$validate->getError(),'code'=>201];
            }else{
                $where = ['email'=>$data['email']];
                if(!$validate->scene('email')->check($data))return ['msg'=>$validate->getError(),'code'=>201];
            }
        }
         //调用验证方法数据方法/传入验证码类型和验证码参数
        $ordinary_captcha = empty($data['ordinary_captcha']) ? "":$data['ordinary_captcha'];
        //获取验证验证码信息
        $is_captcha = self::is_captcha($captcha_type,$ordinary_captcha);
        //判断是否有值返回
        if(!empty($is_captcha)){
           return  $is_captcha;
        }

        
        //判断验证码正确就找回
        if($data['captcha'] == $code)
        {
            M::where($where)->update(['password' => set_password('123456')]);
            return ['msg'=>'密码找回成功','code'=>200];
        }else{
            return ['msg'=>'验证码错误!','code'=>201];
        }
        
     }
    
    // 判断是否登录
    public static function isLogin()
    {
        if(Cookie::has('front_token')){
            $user = M::where(['token'=>Cookie::get('front_token'),'is_frozen'=>0])->find();
            if(!$user) return false;
            Session::set('front',[
                'id' => $user->id,
                'token' => $user->token,
            ]); 
            return true;
        }
        return false;
    }
    
    //获取用户ID
    public static function getUserId()
    {
        
        $user = M::where(['token'=>Cookie::get('front_token'),'is_frozen'=>0])->find();
        return $user->id;
    }
    
    //获取用户配置参数
    public static function getBasic(){
        $basic = basic::where('user_id',self::getUserId())->find();
        return $basic;
    }
    
    // 退出登陆
    public static function logout()
    {
        Session::delete('front_auth');
        Session::delete('front');
        Cookie::delete('front_token');
        Cookie::delete('sign');
        return ['msg'=>'退出成功'];
    }
    
    // 修改密码
     public static function goPass()
     {
        $data = Request::post();
        $validate = new V;
        $user = M::where(['token'=>Cookie::get('front_token'),'is_frozen'=>0])->find();
        M::where('id',$user->id)->update(['password' => set_password(trim($data['newpwd']))]);
        self::logout();
     }
    
     //获取当前登录的用户信息
     public static function getUser()
     {
        $user = M::where(['token'=>Cookie::get('front_token'),'is_frozen'=>0])->find();
        $email   = $user['email'];
      
        
        // 使用正则表达式判断邮箱格式是否为 QQ 邮箱  
if (!empty($email) && preg_match( '/^[1-9][0-9]{4,10}@qq\.com$/i', $email ) ) {  
    // 如果为 QQ 邮箱，则生成 QQ 头像地址  
     $user['head'] = 'https://q1.qlogo.cn/g?b=qq&nk='.$email.'&s=100';  
}elseif (!empty($email) && preg_match( '/^[a-zA-Z0-9_-]+@[a-zA-Z0-9_-]+\.[a-zA-Z0-9_-]+$/i', $email ) ) {  
    // 如果为普通邮箱，则生成 Cravatar 头像地址  
    $address = strtolower( trim( $email ) );
    $hash    = md5( $address ); 
    $user['head'] =  'https://cravatar.cn/avatar/' . $hash .'?d=/static/admin/images/avatar.jpg';  
}  else {  
    // 否则返回默认头像地址  
    $user['head'] = '/static/admin/images/avatar.jpg';  
}
        
        
        return $user;
     }
     
     //获取当前用户的会员套餐
     public static function getVip()
     {
        $user = self::getUser();
        $vip = YpayVip::where('id',$user['vip_id'])->find();
        if(empty($vip)){
            $vip['name'] = '未开通会员';
            $vip['money'] = 'Free';
            $vip['avatar_frame'] = '';
            $vip['is_profiteer'] = '';
            $vip['is_addChannelNum'] = '';
            $vip['viptime'] = '未开通会员';
            $vip['feilv'] = '未开通会员';
            $vip['is_quota'] = 0;
            $vip['is_passage'] = 0;
        }
        return $vip;
     }
     
     //获取语录
     public static function getQuotations(){
         $config = getConfig();
         if($config['is_quotations'] == 1){
            $res = get_curl($config['quotations']);
         }else{
             $res = " 最 慢 的 步 伐 不 是 跬 步 ，而 是 徘 徊 ， 最 快 的 脚 步 不 是 冲 刺 ， 而 是 坚 持 。";
         }
        return $res;
     }
     
     //获取用户中心-总收益
     public static function getUser_totalRevenue(){
        $day=[];
        $__day=[];
        // 获取7天时间
        for ($i=0; $i < 7; $i++) { 
	           $_day = 7-$i;
	           $time=mktime(0, 0, 0,(int)date('m'), date('d') - $_day, (int)date('Y'));
	           $day[$i] = date('m-d',$time);
	           $__day[$i] = date('Y-m-d',$time);
        }

        $sum_data = [];
        foreach ($__day as $k => $time) {
            $endTime = date("Y-m-d",strtotime($time ." + 1 day"));
        	$__sum_data[$k] = YpayOrder::where('status',1)->where('user_id',self::getUserId())->whereTime('create_time', 'between', [$time, $endTime])->sum('money');
        }
        $time = [];
        $time['time_arr'] = str_replace('"',"'",json_encode($day));
        $time['sum_data'] = json_encode($__sum_data);
        return $time;
     }
     
     //获取用户中心-对比数据
     public static function getUser_ComparisonData(){
        $comparison = [];
        $user_id = self::getUserId();//获取用户ID
        //获取成交率
        $order_ok = YpayOrder::where('status',1)->where('user_id',$user_id)->count();//获取成交成功订单
        $order_all = YpayOrder::where('user_id',$user_id)->count();//获取全部订单
        $comparison['turnover']  =  sprintf("%.2f",$order_all == 0 ? 0:$order_ok/$order_all * 100);//获取成交率
        
        //获取月收益率
        $money = YpayOrder::where('status',1)->where('user_id',$user_id)->whereMonth('create_time')->sum('money');//获取本月收益
        $last_money = YpayOrder::where('status',1)->where('user_id',$user_id)->whereMonth('create_time', 'last month')->sum('money'); //获取上月收益
        if($last_money == 0){
            $comparison['monthly_income'] =  sprintf("%.0f",($money - $last_money));//获取月收益率
        }else{
            $comparison['monthly_income'] = sprintf("%.0f",($money - $last_money)/$last_money * 100);//获取月收益率
        }
        
        
        //获取上周 And 上月收益
        $last_weekMoney = YpayOrder::where('status',1)->where('user_id',$user_id)->whereWeek('create_time', 'last week')->sum('money');//获取上周收益
        $last_monthMoney = YpayOrder::where('status',1)->where('user_id',$user_id)->whereMonth('create_time', 'last month')->sum('money');//获取上月收益
        //当收益大于对应数的时候进行处理
        if($last_weekMoney >= 1000){
            $last_weekMoney = sprintf("%.2f",($last_weekMoney / 1000)) . 'K';
        }
        if($last_monthMoney >= 1000){
            $last_monthMoney = sprintf("%.2f",($last_monthMoney / 1000)) . 'K';
        }
        $comparison['last_weekMoney'] = $last_weekMoney;
        $comparison['last_monthMoney'] = $last_monthMoney;
        
        return $comparison;
     }
     
     //获取用户中心-右侧5格
     public static function getUser_rightSide(){
         $rightSide = [];
         $user_id = self::getUserId();//获取用户ID
         //定义时间
         $time = ['today' => 'whereDay','last' => 'whereDay','week' => 'whereWeek','month' => 'whereMonth','year' => 'whereYear'];
         
         //获取数据
         foreach ($time as $key => $value){
            if($key != 'last'){
                $rightSide[$key . '_money'] = YpayOrder::where(['user_id'=>$user_id,'status' => 1])->$value('create_time')->sum('money');
                $tempOne = YpayOrder::where('user_id',$user_id)->where('status',1)->$value('create_time')->count();
                $tempTwo = YpayOrder::where('user_id',$user_id)->$value('create_time')->count();
                if($tempTwo != 0 && !empty($tempTwo)){
                    $rightSide[$key . '_per'] = sprintf("%.2f",$tempOne / $tempTwo * 100); 
                }else{
                    $rightSide[$key . '_per'] = 0;
                }
                
            }else{
                $rightSide[$key . '_money'] = YpayOrder::where(['user_id'=>$user_id,'status' => 1])->$value('create_time', 'yesterday')->sum('money');
                $tempOne = YpayOrder::where('user_id',$user_id)->where('status',1)->$value('create_time', 'yesterday')->count();
                $tempTwo = YpayOrder::where('user_id',$user_id)->$value('create_time', 'yesterday')->count();
                if($tempTwo != 0 && !empty($tempTwo)){
                    $rightSide[$key . '_per'] = sprintf("%.2f",$tempOne / $tempTwo * 100); 
                }else{
                    $rightSide[$key . '_per'] = 0;
                }
            }
            
         }
         return $rightSide;
     }
     
     //获取用户中心-底部数据
     public static function getUser_bottomInfo(){
         $bottom = [];
         $user_id = self::getUserId();//获取用户ID
         
        //  板块1
        $array = ['all' => 'all','wechat' => 'wxpay','ali' => 'alipay', 'qq' => 'qqpay'];
        foreach ($array as $key => $value){
            if($key == 'all'){
                //获取全部订单数据
                $bottom[$key . 'Order'] = YpayOrder::where('user_id',$user_id)->count();//获取总订单
            }else{
                //获取订单数据
                $bottom[$key . '_order'] = YpayOrder::where('user_id',$user_id)->where('type',$value)->count();

                //获取百分比
                if(isset($bottom['allOrder'])){
                    $allOrder = $bottom['allOrder'] == 0 ? 1:$bottom['allOrder'];
                    $bottom[$key . '_per'] = sprintf("%.2f",$bottom[$key . '_order'] / $allOrder * 100);
                }
                
                if($bottom[$key . '_order'] >= 1000){
                    $bottom[$key . '_order'] = sprintf("%.2f",($bottom[$key . '_order'] / 1000)) . 'K';
                }
            }
        }
        
        //板块2
        $temp = M::where('id',$user_id)->field('money')->find();
        $bottom['money'] = $temp['money'];//获取用户余额
        $day=[];
        $__day=[];
        // 获取6天时间
        for ($i=0; $i < 6; $i++) { 
	           $_day = 6-$i;
	           $time=mktime(0, 0, 0,(int)date('m'), date('d') - $_day, (int)date('Y'));
	           $day[$i] = date('m-d',$time);
	           $__day[$i] = date('Y-m-d',$time);
        }

        $sum_data = [];
        foreach ($__day as $k => $time) {
            $endTime = date("Y-m-d",strtotime($time ." + 1 day"));
        	$__sum_data[$k] = MoneyLog::where('user_id',$user_id)->whereTime('create_time', 'between', [$time, $endTime])->sum('money');
        }
        $time = [];
        $bottom['time_arr'] = str_replace('"',"'",json_encode($day));
        $bottom['sum_data'] = json_encode($__sum_data);
        
        $bottom['week_money'] = abs(MoneyLog::where('user_id',$user_id)->whereWeek('create_time')->sum('money'));//获取本周消费余额
        $last_weekMoney = abs(MoneyLog::where('user_id',$user_id)->whereWeek('create_time', 'last week')->sum('money'));//获取上周消费余额
        $temp = $bottom['week_money'] - $last_weekMoney;
        if($temp > 0){
            $bottom['week_txt'] = '比上周高' . $temp . '元';  
        }elseif(0 > $temp){
            $bottom['week_txt'] = '比上周低' . abs($temp) . '元';
        }else{
            $bottom['week_txt'] = '暂时没有上周消费日志';  
        }
        
        //板块3
        $order = YpayOrder::where('user_id',$user_id)->where('status',1)->order('id', 'desc')->limit(6)->select();
       
       //获取支付类型
        foreach ($order as $key => $value){
            $order[$key]['type'] =  getPayType($value['type']);
        }
        $bottom['order'] = $order;
        return $bottom;
     }
     
     //重置密钥信息
     public static function goUserKey()
     {
        $user = M::where(['token'=>Cookie::get('front_token'),'is_frozen'=>0])->find();
        $data = rand_string();
        M::where('id',$user->id)->update(['user_key' => $data]);
        return $data;
     }
     
    //购买VIP套餐
     public static function govip($data)
    {
        if(empty($data['tcid']))
        {
            return ['msg'=>'请选择套餐','code'=>201];
        }
        $user = M::where(['token'=>Cookie::get('front_token'),'is_frozen'=>0])->find();
        if(!$user) return ['msg'=>'会员不存在','code'=>201];
        $vip = YpayVip::where(['id'=>$data['tcid']])->find();
        if(!$vip) return ['msg'=>'套餐不存在','code'=>201];
        if($user['money']<$vip['money'])
        {
            return ['msg'=>'余额不足请充值','code'=>201];
        }
        try {
            
            M::money("-".$vip['money'],$user->id, '购买套餐扣款');

            $viptime = $vip['viptime'];
            if(empty($user['vip_time']))
            {
                M::where('id',$user->id)->update(['vip_id' => $vip['id'],'vip_time' =>date("Y-m-d H:i:s",strtotime("+ $viptime day")),'feilv'=>$vip['feilv']]);
            }
            else
            {
                if($user['feilv'] == $vip['feilv'] )
                {
                    $sjc = strtotime($user['vip_time']);
                    if($sjc<time())
                    {
                        M::where('id',$user->id)->update(['vip_id' => $vip['id'],'vip_time' =>date("Y-m-d H:i:s",strtotime("+ $viptime day")),'feilv'=>$vip['feilv']]);
                    }
                    else
                    {
                        $uviptime = $user['vip_time'];
                        M::where('id',$user->id)->update(['vip_id' => $vip['id'],'vip_time' =>date("Y-m-d H:i:s",strtotime("$uviptime + $viptime day"))]);
                    }
                }
                else
                {
                     M::where('id',$user->id)->update(['vip_id' => $vip['id'],'vip_time' =>date("Y-m-d H:i:s",strtotime("+ $viptime day")),'feilv'=>$vip['feilv']]);
                }
            }
            return ['msg'=>'操作成功'];
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }
    
    //注册/找回公共方法
    public static function reg_or_bind($type,$data){
        
        //判断是否重复点击注册按钮
        $is_register = Cache::get('is_register');
        
        if($is_register == 'yes'){
            return ['code'=>201,'msg'=>'请勿重复注册,60秒后再尝试!'];
        }
        
        
        // getConfig() 获取系统配置信息
        // getConfig()['is_reg'] 检查是否开启注册 Tips: 0:关闭/1:开启
        // getConfig()['captcha-type'] 获取验证码类型 Tips: 0:关闭验证码/1:普通验证码/2:腾讯防水墙
        // getConfig()['regcode-type'] 获取注册方式类型 Tips: 0:用户名+密码+邮箱注册/1:手机号注册/2:邮箱注册
        $config = getConfig();
        $is_reg = $config['is_reg'];
        $captcha_type = $config['captcha-type'];
        $regcode_type = $config['regcode-type'];
        
        // 判断注册功能是否开启0/1 = 关闭/开启
        if($is_reg !=1 )
        {
            return ['code'=>201,'msg'=>'注册功能已关闭!'];
        }
        
         // 验证
        $validate = new V;
        
        // 调用验证方法数据方法/传入验证码类型和验证码参数
        $ordinary_captcha = empty($data['ordinary_captcha']) ? "":$data['ordinary_captcha'];
        
        // 获取验证验证码信息
        $is_captcha = self::is_captcha($captcha_type,$ordinary_captcha);
        
        // 判断是否有值返回
        if(!empty($is_captcha)){
           return  $is_captcha;
        }
        
        //判断验证码是否正确
        if($regcode_type != 0 )
        {
            if($regcode_type == 1){
                if(!$validate->scene('mobile')->check($data))return ['msg'=>$validate->getError(),'code'=>201];
            }else{
                if(!$validate->scene('email')->check($data))return ['msg'=>$validate->getError(),'code'=>201];
            }
            $code = Cache::get('captcha');
            if($data['captcha']!=$code)
            {
                return ['msg'=>'验证码错误','code'=>201];
            }
        }
        
        // 验证提交数据
        if(!$validate->scene('add')->check($data))
        return ['msg'=>$validate->getError(),'code'=>201];

        
        // 判断是否是快捷登录
        if($type == 'bind'){
            if($data['type'] == 'qq'){
                $data['is_bindqq'] = $data['is_bind'];
                $data['qq_sid'] = $data['open_id'];
            }else{
                $data['is_bindwx'] = $data['is_bind'];
                $data['wx_sid'] = $data['open_id'];
            }
        }
        $data['password'] = set_password(trim($data['password']));
        $data['user_key'] = rand_string();
        $data['username'] = htmlspecialchars($data['username']);
        
        //检查是否开启赠送余额功能
        $zsoff = $config['is_reg_give_price'];
        if($zsoff==1)
        {
            $data['money'] = $config['reg_give_price'];
        }
        
        //记录注册缓存
        Cache::set('is_register','yes',60);
        
        try {
            M::create($data);
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }


    

    
    //判断验证码是否为空和验证
    public static function is_captcha($captcha_type,$ordinary_captcha="",$is_admin = ""){
        // 判断普通验证码是否为空
        if($captcha_type==1 &&  empty($ordinary_captcha) )
        {
            return ['msg'=>'请输入验证码','code'=>201];
        }

    }
    
    // 快捷登录缓存用户信息
    public static function thirdlogin($user)
    {
        $user['token'] = rand_string().$user['id'].microtime(true);
        M::where('id',$user['id'])->update(['token' =>$user['token']]);
        //是否记住密码
        $time = 3600;
        if (isset($data['remember'])) $time = 30 * 86400;
        //缓存登录信息
        $info = [
            'id' => $user['id'],
            'token' => $user['token']
        ];
        Session::set('front', $info);
        Cookie::set('front_token',$user['token'], $time);
        try {
            $info = [
               'uid'       => $user['id'],
               'url'      => Request::url(),
               'type'    => 1,
               'desc'    => '商户快捷登录成功', 
               'ip'       => get_client_ip(),
               'user_agent'=> Request::server('HTTP_USER_AGENT')
            ];
            Log::create($info);
            $basic = basic::where('user_id',$user->id)->find();
            //开启了登录提醒
            if(!empty($basic['login_tips']) && $basic['login_tips'])
            {
                notice::login_tips($user,$basic);
            }
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
        return ['msg'=>'登录成功','code'=>200];
    }
    
    // 获取短信公共方法
    public static function getCode($type = '',$mobile='',$email='',$bind ='')
    {
        $ip = get_client_ip();//获取IP   
        $time = Log::where(['type'=>2,'ip'=>$ip])->order('id','desc')->find();
        $count = Log::where(['type'=>2,'ip'=>$ip])->whereDay('create_time')->count();
        if(!empty($time)){
            if(strtotime($time['create_time']) > time()-60){
                return ['msg'=>'两次发送验证码之间需要相隔60秒！','code'=>201];
            }
        }
        if(!empty($count)){
           
            if($count > 8 ){
                return ['msg'=>'你今天请求次数过多，已被禁止发送','code'=>201];
            } 
        }
        $validate = new V;
        $data = 
        [
            'email' => $email,
            'mobile' => $mobile
        ];
        
        if($type == 'register'){
            //判断手机号/邮箱是否符合规范
            if(!$validate->scene('Edit')->check($data))
            return ['msg'=>$validate->getError(),'code'=>201];
        }
        // 获取全部配置参数
        $config  = getConfig();
        $num = mt_rand(100000,999999);//生产随机验证码
        Cache::set('captcha', $num, 300);
        //获取短信注册类型 1/2/3 - 阿里云/腾讯云/短信宝
        $smsType = $config['smstype'];
        if($type == 'login' || $type == 'retrieve' || $type == 'UBind'){
            
            $code_msg = '该手机号不存在!';
            $email_msg = '该邮箱不存在!';
            switch ($type) {
                case 'login':
                    $code_type = 'logincode-type';
                    $email_goMsg_title = '平台登录验证码';
                    break;
                case 'retrieve':
                    $code_type = 'retrieve-type';
                    $email_goMsg_title = '平台找回验证码';
                    break;
                default:
                    $code_type = 'UBind-type';
                    $email_goMsg_title = '平台解绑验证码';
                    break;
            }
        }elseif($type == 'register' || $type == 'bind'){
            $code_msg = '该手机号已存在!';
            $email_msg = '该邮箱已存在!';
            switch ($type) {
                case 'register':
                    $code_type = 'regcode-type';
                    $email_goMsg_title = '平台注册验证码';
                    break;
                default:
                    $code_type = 'bind-type';
                    $email_goMsg_title = '平台绑定验证码';
                    break;
            }
        }
        
        if($code_type == 'bind-type' || $code_type == 'UBind-type'){
            if($bind == "mobile"){
                $isType = 1;
            }elseif($bind == "email"){
                $isType = 2;
            }
        }else{
            $isType = $config[$code_type];
        }

        //判断短信发送方法 1/2 短信/邮箱
        switch ($isType) {
            case 1:
                if(empty($mobile)){
                    return ['msg'=>'手机号不能为空','code'=>201];
                }
                $model =  M::where('mobile',$mobile)->find();
                if(empty($model) && ($type == 'login' || $type == 'retrieve'))
                {
                    return ['code'=>201,'msg'=>$code_msg];
                }elseif(!empty($model) && $type == 'register'){
                    return ['code'=>201,'msg'=>$code_msg];
                }
                if($type == 'login' || $type == 'retrieve' || $type == 'bind' || $type == 'UBind'){
                    $res = Sms::send($mobile,$num);
                }else{
                    $res = Sms::goReg($mobile,$num);
                }
                
                break;
            default:
                if(empty($email)){
                    return ['msg'=>'邮箱不能为空','code'=>201];
                }
            
                $model =  M::where('email',$email)->find();
                if(empty($model) && ($type == 'login' || $type == 'retrieve' || $type == 'UBind'))
                {
                    return ['code'=>201,'msg'=>$email_msg];
                }elseif(!empty($model) && ($type == 'register' || $type == 'bind')){
                    return ['code'=>201,'msg'=>$email_msg];
                }
                
                //调用发信模板
                $res = notice::getCode($email,$email_goMsg_title,$num);
                break;
            }
        
        if($res['code'] == 201)
        {
            return ['code'=>201,'msg'=>'发送失败!'];
        }
        switch ($type) {
            case 'UBind':
                $uid = self::getUserId();
                $desc ='请求解绑验证码';
                break;
            case 'bind':
                $uid = self::getUserId();
                $desc ='请求绑定验证码';
                break;
            default:
                $uid = 0;
                $desc ='发送验证码';
                break;
        }
        try {
            $info = [
               'uid'       => $uid,
               'url'      => Request::url(),
               'type'    => 2,
               'desc'    => $desc, 
               'ip'       => $ip,
               'user_agent'=> Request::server('HTTP_USER_AGENT')
            ];
            Log::create($info);
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
        return ['code'=>200,'msg'=>'发送成功!'];
    }
    
    //获取通知设置
    public static function getNotice(){
        $config = getConfig();//获取配置参数
        $basic = self::getBasic();
        $array = 
        [
            ['id' => 'order_tips','name' => '新 订 单 通 知', 'child' => [ ['id' => 'close','isOpen' => 'yes','class' => ''],['id' => 'email','isOpen' => 'no','class' => ''],['id' => 'mobile','isOpen' => 'no','class' => ''],['id' => 'wxpusher','isOpen' => 'no','class' => ''] ]],
            ['id' => 'lose_tips','name' => '通 道 掉 线 提 醒', 'child' => [ ['id' => 'close','isOpen' => 'yes','class' => ''],['id' => 'email','isOpen' => 'no','class' => ''],['id' => 'mobile','isOpen' => 'no','class' => ''],['id' => 'wxpusher','isOpen' => 'no','class' => ''] ]],
            ['id' => 'login_tips','name' => '账 号 登 录 提 醒', 'child' => [ ['id' => 'close','isOpen' => 'yes','class' => ''],['id' => 'email','isOpen' => 'no','class' => ''],['id' => 'mobile','isOpen' => 'no','class' => ''],['id' => 'wxpusher','isOpen' => 'no','class' => ''] ]],
            ['id' => 'is_money_tips','name' => '余 额 不 足 通 知', 'child' => [ ['id' => 'close','isOpen' => 'yes','class' => ''],['id' => 'email','isOpen' => 'no','class' => ''],['id' => 'mobile','isOpen' => 'no','class' => ''],['id' => 'wxpusher','isOpen' => 'no','class' => ''] ]]
        ];
        
        $temp = '';//定义一个空接收参数
        
        //根据循环判断是否开启和选中
        foreach ($array as $key => $value) {
            
            switch ($basic[$value['id']]) {
                case 'email':
                       $temp = 'checked'; 
                    break;
                // case 'mobile':
                //       $temp = 'checked'; 
                //     break;
                case 'wxpusher':
                       $temp = 'checked'; 
                    break;
                case 'close':
                       $temp = 'checked'; 
                    break;
                default:
                        $temp = ''; 
                    break;
            }
            
            foreach ($value['child'] as $ckey => $cvalue) {
                
                if($config['email_switch'] == 1 && $cvalue['id'] == 'email'){
                    $array[$key]['child'][$ckey]['isOpen'] = 'yes';
                }
                if($config['wxpusher_switch'] == 1 && $cvalue['id'] == 'wxpusher'){
                    $array[$key]['child'][$ckey]['isOpen'] = 'yes';
                }
                
                if($basic[$value['id']] == $cvalue['id']){
                    $array[$key]['child'][$ckey]['class'] = $temp;
                }
            }
            
        }
        return $array;
    }
    
    //获取通知类型
    public static function getNoticeType(){
        $config = getConfig();//获取配置参数
        $array = 
        [
            ['id' => 'close','name' => '关 闭','isOpen' => 'yes','icon' => 'fa-solid fa-circle-xmark'],
            ['id' => 'email','name' => '邮 件','isOpen' => 'no','icon' => 'fa-solid fa-envelope-open-text'],
            ['id' => 'wxpusher','name' => '微 信','isOpen' => 'no','icon' => 'fa-brands fa-weixin']
        ];
        
        foreach ($array as $key => $value) {
            if($config['email_switch'] == 1 && $value['id'] == 'email'){
                    $array[$key]['isOpen'] = 'yes';
                }
                if($config['wxpusher_switch'] == 1 && $value['id'] == 'wxpusher'){
                    $array[$key]['isOpen'] = 'yes';
                }
        }
        
        return $array;
    }
    
    //保存通知配置信息
    public static function saveNotifications(){
        $data = Request::post();
        try {
            basic::where('user_id',self::getUserId())->update($data);
        } catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }

}
