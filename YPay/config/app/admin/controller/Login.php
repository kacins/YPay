<?php
declare (strict_types = 1);

namespace app\admin\controller;

use think\captcha\facade\Captcha;
use think\facade\Request;
use app\common\service\AdminAdmin as S;
use app\common\service\YpayUser;

class Login extends Base
{
    //后台登录
    public function index(){
        //是否已经登录
        if (S::isLogin()){
            return redirect(Request::root().'/index');
        }
        if (Request::isAjax()){
            $this->getJson(S::login(Request::param('','','strip_tags')));
        }
        return $this->fetch();
    }

    // 验证码
    public function verify(){
        ob_clean();
        return Captcha::create();
    }

    //退出登陆
    public function logout(){
        return $this->getJson(S::logout());
    }
}
