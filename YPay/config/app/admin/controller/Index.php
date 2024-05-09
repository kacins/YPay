<?php
declare (strict_types = 1);

namespace app\admin\controller;

use think\facade\Session;
use think\facade\Request;
use app\common\util\Upload as Up;
use app\common\model\AdminPhoto as P;
use app\common\service\AdminAdmin as S;
use think\facade\Db;
use app\common\model\YpayOrder;
use app\common\model\YpayRecharge;
use app\common\model\YpayAccount;
use think\facade\View;
use app\common\core\core;
use app\common\model\YpayUser;

class Index extends Base
{
    protected $middleware = ['AdminCheck','AdminPermission'];
    
    // 首页
    public function index(){
        return $this->fetch('',[
            'nickname'  => get_field('admin_admin',Session::get('admin.id'),'nickname')
        ]);
    }

    // 清除缓存
    public function cache(){
        Session::clear();
         return $this->getJson(rm());
        }
    
    // 菜单
    public function menu(){
        return json(get_tree(Session::get('admin.menu')));
    }
    
      // 图库选择
    public function optPhoto(){
        if (Request::isAjax()) {
            return $this->getJson(P::getAll());
        }
        return $this->fetch('',P::getPath());
    }
    
    // 欢迎页
    public function home(){
        
        //获取欢迎语
        $sentence = '';
        $now = date('H');
        if ($now > 0 && $now <= 6) {
          $sentence = '午夜好，';
        } else if ($now > 6 && $now <= 11) {
          $sentence = '早上好，';
        } else if ($now > 11 && $now <= 14) {
          $sentence = '中午好，';
        } else if ($now > 14 && $now <= 18) {
          $sentence = '下午好，';
        } else {
          $sentence = '晚上好，';
        }
        $hello = $sentence . get_field('admin_admin',Session::get('admin.id'),'nickname');
        //毒鸡汤
        $chicken = 
        [
            '当你想要的只是你真正需要的,你就是成功的.',
            '无所谓好或不好,人生一场虚空大梦,韶华白首,不过转瞬.',
            '没有温暖的阳也没有希望的光,世界只是一片狼藉,不要抹灭你最后的希望.',
            '不管你曾经被伤害得有多深,总会有一个人的出现,让你原谅之前生活对你所有的刁难.',
            '做想要做的事和做擅长的事、以及做会做的事是完全不同的,为了实现自己的愿望,就算不擅长也要去做.',
            '每一个不向命运低头、努力生活的人，都值得被尊重。',
            '青年的肩上,从不只有清风明月,更有责任担当.岁月因青春慨然以赴而更加美好,世间因少年挺身向前而更加美丽.请相信,不会有人永远年轻,但永远有人年轻.',
            '人生路上,总有人走得比你快,但不必介意,也不必着急.一味羡慕别人的成绩,只会给自己平添压力、徒增烦恼.不盲从别人的脚步,坚定目标,才能找到自己的节奏,进而逢山开路、遇水搭桥.',
            '如果你真的在乎一个人,首先要学会的就是感恩对方的好.这样,对方才会在和你的相处中找到价值感,相处起来也会更加舒适愉悦.',
            '一个人只有心里装得下别人,有换位思考的品质,有为他人谋幸福的信念,才能真正做到慷慨施予.同样,也只有赠人玫瑰而无所求时,你才会手有余香、真有所得.',
        ];
        $i = array_rand($chicken);
        
        //获取时间
        $day = date('Y-m-d');
        $weekarray=array("日","一","二","三","四","五","六");
        $week = $weekarray[date("w")];
        $time = $day . '星期'. $week;
        
        //获取当前域名
        $path = $_SERVER['REQUEST_URI'];
        $path =substr($path,0,strrpos($path,"php"));
        
        $base = 
        [
            ['url'=>$path.'php/config','title' => '系统配置','icon' => 'layui-icon-slider','color' => '#ffc069'],
            ['url'=>$path.'php/ypay.user','title' => '会员管理','icon' => 'layui-icon-group','color' => ''],
            ['url'=>$path.'php/ypay.shop','title' => '商城总览','icon' => 'layui-icon-chart-screen','color' => '#95de64'],
            ['url'=>$path.'php/ypay.vip','title' => '会员套餐','icon' => 'layui-icon-cart','color' => '#ff9c6e'],
            ['url'=>$path.'php/admin.photo','title' => '图片管理','icon' => 'layui-icon-picture','color' => '#b37feb'],
            ['url'=>$path.'php/ypay.account','title' => '通道管理','icon' => 'layui-icon-layer','color' => '#ffd666'],
            ['url'=>$path.'php/ypay.news','title' => '通知公告','icon' => 'layui-icon-email','color' => '#5cdbd3'],
            ['url'=>$path.'php/ypay.shop/plus','title' => '后台充值','icon' => 'layui-icon-rmb','color' => '#ff85c0'],
        ];
        
        //创建Core对象
        $core  = new Core();
        
        //获取控制台左侧广告位
        $homeLeftAd = $core->getHomeLeftAd();
        
        //获取控制台右侧广告位
        $homeRightAd = $core->getHomeRightAd();
        
        $data = 
        [
          'hello'  => $hello,
          'chicken'   => $chicken[$i],
          'path' => $base,
          'time' => $time,
          'homeLeftAd' => $homeLeftAd,
          'homeRightAd' => $homeRightAd,
          
        ];
        View::assign('data', $data);
        return $this->fetch('',$this->getSystem());
    }

    // 修改密码
    public function pass(){
        if (Request::isAjax()){
            $this->getJson(S::goPass());
        }
        return $this->fetch();
    }

    // 通用上传
    public function upload(){
        return $this->getJson(Up::putFile(Request::file(),Request::post('path')));
    }

     //检测配置表数据是否正常
    public function detection(){
            //获取重复数据信息
            $result = Db::table('admin_config')->GROUP('config_name')->having('count(*)>1')->select()->toArray();
            if(!empty($result)){
                //循环查找并删除
                foreach ($result as $key => $value) {
                    //获取全部重复数据信息
                    $all = Db::table('admin_config')->where('config_name',$value['config_name'])->select();
                    //多余的数据执行删除操作
                    foreach ($all as $key => $value) {
                        if($key > 0){
                            Db::table('admin_config')->where('id',$value['id'])->delete();
                        }
                    }
                }
                $this->getJson(['msg' => '修复成功','code' => 200]);
            }
            $this->getJson(['msg' => '无重复数据','code' => 200]);
    }
    

}
