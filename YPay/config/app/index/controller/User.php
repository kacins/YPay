<?php
declare (strict_types = 1);

namespace app\index\controller;
use think\facade\Request;
use think\captcha\facade\Captcha;
use app\common\service\YpayUser as S;
use app\common\model\YpayUser as M;
use app\common\model\YpayPlug as Plug;
use think\facade\Session;
use think\facade\Cookie;
use think\facade\Config;
use think\facade\Db;
use think\facade\View;
use app\common\service\Third;

class User extends \app\BaseController
{
    /**
     * 首页
     */
    public function index()
    {
        // 如果未登录进入用户中心界面则跳转至登录界面
        if (!S::isLogin()){
            return redirect(Request::root().'/User/Login');
        }
        $user = S::getUser();
        
        $users['head'] = $user['head'];
        $users['username'] = $user['username'];
        
        View::assign(
            [
                'user' => $users,
                'vip' => S::getVip(),
                'quotations' =>S::getQuotations(),
                'totalRevenue' => S::getUser_totalRevenue(),
                'comparison' => S::getUser_ComparisonData(),
                'rightSide' => S::getUser_rightSide(),
                'bottom' => S::getUser_bottomInfo()
            ]);
        return $this->fetch();
    }
     
    //登录
    public function login()
    {
        //如果已登录则进入用户中心
        if (S::isLogin()){
            return redirect(Request::root().'/User/Index');
        }
        //获取页面提交的数据传值
        if (Request::isAjax()){
            $this->getJson(S::login(Request::param('','','strip_tags')));
        }
        return $this->fetch('',['config'=>getConfig(),'pop'=>'no']);
    }
    
    
    // 注册
    public function reg()
    {
        // 如果已登录则进入用户中心
        if (S::isLogin())
        {
            return redirect(Request::root().'/User/Index');
        }
        // 获取页面提交的数据传值
        if (Request::isAjax()){
            $this->getJson(S::register(Request::param('','','strip_tags')));
        }
        return $this->fetch('',['config'=>getConfig(),'pop'=>'yes']);
    }
    
    
    // 登录 - 获取短信
    public function getLoginCode($mobile='',$email='')
    {
       return $this->getJson(S::getCode('login',$mobile,$email));
    }
    
    // 注册 - 获取短信
    public function getRegCode($mobile='',$email='')
    {
         return $this->getJson(S::getCode('register',$mobile,$email));
    }
    
    
    // 找回 - 获取短信
    public function getLostCode($mobile='',$email='')
    {
         return $this->getJson(S::getCode('retrieve',$mobile,$email));
    }
    
    
    //找回密码
    public function lostpwd()
    {
        if (Request::isAjax()){
            $this->getJson(S::golostpwd(Request::param('','','strip_tags')));
        }
        
        return $this->fetch('',['config'=>getConfig()]);
    }
    
    //验证码
    public function verify(){
        ob_clean(); 
        return Captcha::create();
    }
    
    //退出登录
    public function logout(){
        S::logout();
        return redirect(Request::root().'/User/Login');
    }
    
    //登录/注册成功通知
    public function notice($type,$data = null){
        if($type == 'login'){
            S::login_tips();
        }else{
            parse_str($data, $dataArray);
            S::register_tips($dataArray);
        }
        
    }
    
    //插件下载
    public function PlugDownload(){
        if (Request::isAjax()) {
            $plug = Plug::getPlugList();
            json_encode($plug, JSON_FORCE_OBJECT);
            return $plug;
        }
        View::assign(
            [
                'user' => S::getUser(),
                'vip' => S::getVip(),
            ]);
        return $this->fetch();
    }
    
}
