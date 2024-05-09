<?php


namespace app\index\controller;
use think\facade\Config;
use think\facade\Db;
use app\common\service\YpayUser as S;
use think\facade\View;
use think\facade\Request;

class Doc extends \app\BaseController
{
    
    //默认首页发起
    public function index()
    {
        $list = Db::table('ypay_navs')->where('status', 1)->order('id','asc')->select();
        View::assign('domain',Request::domain());
        View::assign('nav', $list);
        return $this->fetch('',$this->getSystem());
    }
    
    //API下单接口
    public function api()
    {
        $list = Db::table('ypay_navs')->where('status', 1)->order('id','asc')->select();
        View::assign('domain',Request::domain());
        View::assign('nav', $list);
        return $this->fetch('',$this->getSystem());
    }
    
    //查询接口接口
    public function result()
    {
        $list = Db::table('ypay_navs')->where('status', 1)->order('id','asc')->select();
        View::assign('domain',Request::domain());
        View::assign('nav', $list);
        return $this->fetch('',$this->getSystem());
    }
    
    //查询订单接口
    public function chaorder()
    {
        $list = Db::table('ypay_navs')->where('status', 1)->order('id','asc')->select();
        View::assign('domain',Request::domain());
        View::assign('nav', $list);
        return $this->fetch('',$this->getSystem());
    }
    
}
