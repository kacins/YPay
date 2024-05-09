<?php


namespace app\index\controller;
use think\facade\Config;
use think\facade\Db;
use think\facade\View;
use app\common\service\YpayUser as S;

class News extends \app\BaseController
{
    
    /**
     * 首页
     */
    public function index($type=1)
    {
        $news = Db::name('ypay_news')->where('type',$type)->where('status', 1)->order('id desc')->paginate(10);
        foreach ($news as $key => $value){
            $value['month'] = substr($value['create_time'],5,2);
            $value['day'] = substr($value['create_time'],8,2);
            $news[$key] = $value;
        }
        $is_login = 0;
        if (S::isLogin()){
            $is_login = 1;
        }
        View::assign('is_login', $is_login);
        View::assign('news', $news);
        $list = Db::table('ypay_navs')->where('status', 1)->order('sort','asc')->select();
        View::assign('nav', $list);
        return $this->fetch('',['config'=>getConfig()]);
    }
    
    public function categories($type = ''){
        $list = Db::table('ypay_navs')->where('status', 1)->order('sort','asc')->select();
        $news = Db::name('ypay_news')->where('type',$type)->where('status', 1)->order('id desc')->paginate(10);
        foreach ($news as $key => $value){
            $value['month'] = substr($value['create_time'],5,2);
            $value['day'] = substr($value['create_time'],8,2);
            $news[$key] = $value;
        }
        switch ($type) {
            case 1:
                $title = '平 台 公 告';
                break;
            case 2:
                $title = '行 业 动 态';
                break;
            case 3:
                $title = '常 见 问 题';
                break;
        }
                $is_login = 0;
        if (S::isLogin()){
            $is_login = 1;
        }
        View::assign('is_login', $is_login);
        View::assign('title',$title);
        View::assign('news', $news);
        View::assign('nav', $list);
        return $this->fetch();   
    }
    
    public function detail($id='')
    {
                $is_login = 0;
        if (S::isLogin()){
            $is_login = 1;
        }
        View::assign('is_login', $is_login);
        $news = Db::name('ypay_news')->where('id',$id)->where('status', 1)->find();
        View::assign('news', $news);
        $list = Db::table('ypay_navs')->where('status', 1)->order('id','asc')->select();
        View::assign('nav', $list);
        return $this->fetch();
    }
    
}
