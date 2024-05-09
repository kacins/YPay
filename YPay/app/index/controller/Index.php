<?php


namespace app\index\controller;
use think\facade\Config;
use think\facade\Db;
use think\facade\Session;
use app\common\service\YpayUser as S;
use think\facade\View;
use think\facade\Request;

class Index extends \app\BaseController
{
    
    public function index()
    {
        $config = getConfig();//获取系统配置参数
        if(!$config['is_weboff'])
        {
            return redirect(Request::root().'/User/Login');
        }else if($config['is_weboff'] == 2){
            if (filter_var($config['home_url'], FILTER_VALIDATE_URL)) {
                            return '<html><frameset framespacing="0" border="0" rows="0" frameborder="0">
        <frame name="main" src="'. $config['home_url'] .'" scrolling="auto" noresize>
    </frameset></html>';
            } else {
               return redirect(Request::root().'/User/Login');
            }

        }
        $list = Db::table('ypay_navs')->where('status', 1)->order('sort','asc')->select();
        $news1 = Db::name('ypay_news')->where('type',1)->where('status',1)->order('id desc')->paginate(5);
        $news2 = Db::name('ypay_news')->where('type',2)->where('status',1)->order('id desc')->paginate(5);
        $news3 = Db::name('ypay_news')->where('type',3)->where('status',1)->order('id desc')->paginate(5);
        $is_login = 0;
        if (S::isLogin()){
            $is_login = 1;
        }
        $homeTemp = $config['home_temp'];
        View::assign([
            'news1'  => $news1,
            'news2' => $news2,
            'news3' => $news3,
            'nav' => $list,
            'is_login' => $is_login,
            'resource' => $homeTemp
        ]);
        return $this->fetch('../../public/home/'. $homeTemp .'/index',$this->getSystem());
    }
    
}
