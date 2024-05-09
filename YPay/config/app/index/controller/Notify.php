<?php
declare (strict_types = 1);

namespace app\index\controller;
use think\facade\Session;
use think\Response;
use think\facade\Request;
use app\common\model\YpayUser as M;
use think\facade\Config;
use think\facade\Db;
use app\common\util\Mail;
use app\common\service\Jialanshen;
use app\common\service\YiPay as epay;
use app\common\service\Third;
use app\common\service\YpayUser as S;
use app\common\model\YpayUserbasic as basic;
use app\common\service\Paylist as paylist;
use app\common\model\YpayPaylist as m_paylist;

class Notify extends \app\BaseController
{
    
    //易支付异步通知
    public function notify()
    {
        $data = Request::param('','','strip_tags');
        paylist::core($data,'notify');
    }
    
    //易支付同步通知
    public function return()
    {
        $data = Request::param('','','strip_tags');
        return paylist::core($data,'return');
    }

    
   //店员密钥验证
    public function shop_auth($apikey='')
    {
        $shopkey = getConfig()['clerk_key'];
        if(empty($apikey))
        {
            return json(['code'=>0,'msg'=>'请设置密钥信息!']);
        }
        if(empty($shopkey))
        {
            return json(['code'=>0,'msg'=>'请设置密钥信息!']);
        }
        if($shopkey!=$apikey)
        {
            return json(['code'=>0,'msg'=>'通讯密钥有误!']);
        }
        else
        {
            return json(['code'=>1,'msg'=>'密钥验证成功!']);
        }
    }
    
    //微信店员通知地址
    public function wechat_notify($apikey='',$money='',$wxname='')
    {
        $shopkey = getConfig()['clerk_key'];
        if(empty($apikey))
        {
            return json(['code'=>0,'msg'=>'请设置密钥信息!']);
        }
        if(empty($wxname))
        {
            return json(['code'=>0,'msg'=>'请修改收款账号的昵称!']);
        }
        if(empty($money))
        {
            return json(['code'=>0,'msg'=>'金额不可为空!']);
        }
        if(empty($shopkey))
        {
            return json(['code'=>0,'msg'=>'请设置密钥信息!']);
        }
        if($shopkey!=$apikey)
        {
            return json(['code'=>0,'msg'=>'通讯密钥有误!']);
        }
        $vaccount = Db::name('ypay_account')->where('wxname', $wxname)->find();
        if(empty($vaccount))
        {
            return json(['code'=>0,'msg'=>'收款账号昵称不存在!']);
        }
        $order = Db::name('ypay_order')->where('account_id',$vaccount['id'])->where('status',0)->where('type','wxpay')->where('truemoney',$money)->where('out_time','>',time())->order('id desc')->find();
        if(!empty($order))
        {
            $url = Jialanshen::creat_callback($order);
            get_curl($url['notify']);
            return json(['code'=>1,'msg'=>'回调成功!']);
        }
        else
        {
            return json(['code'=>0,'msg'=>'订单超时或者不存在!']);
        }
    }
    
    public function app_auth($apiid,$apikey)
    {
        $basic = basic::where('user_id',$apiid)->where('appkey',$apikey)->find();
        if(empty($basic))
        {
            return json(['code'=>0,'msg'=>'商户不存在或密钥错误!']);
        }
        return json(['code'=>1,'msg'=>'密钥验证成功!']);
    }
    
    public function appnotify($apiid,$apikey,$money,$type,$channel)
    {
        $basic = basic::where('user_id',$apiid)->where('appkey',$apikey)->find();
        if(empty($basic))
        {
            return json(['code'=>0,'msg'=>'商户不存在或密钥错误']);
        }
        if(empty($money) || empty($type) || empty($channel))
        {
            return json(['code'=>0,'msg'=>'参数不可为空']);
        }
        if($channel==0)
        {
            return json(['code'=>1,'msg'=>'不发起回调']);
        }
        $vaccount = Db::name('ypay_account')->where('id',$channel)->where('type', $type)->find();
        if(empty($vaccount))
        {
            return json(['code'=>0,'msg'=>'通道不存在']);
        }
        $order = Db::name('ypay_order')->where('account_id',$vaccount['id'])->where('status',0)->where('truemoney',$money)->where('out_time','>',time())->order('id desc')->find();
        if(!empty($order))
        {
            $url = Jialanshen::creat_callback($order);
            get_curl($url['notify']);
            return json(['code'=>1,'msg'=>'回调成功!']);
        }
        else
        {
            return json(['code'=>0,'msg'=>'订单超时或不存在']);
        }
    }
    
    public function ali_auth($apiid='',$apikey='',$channel='',$pid='')
    {
        if(empty($apiid)  || empty($apikey)  || empty($channel)  || empty($pid))
        {
            return json(['code'=>100,'msg'=>'参数不可为空']);
        }
        $user = basic::where('user_id',$apiid)->where('appkey',$apikey)->find();
        if(empty($user))
        {
            return json(['code'=>0,'msg'=>'商户不存在或密钥错误!']);
        }
        //更新状态
        Db::name('ypay_account')->where('id', $channel)->update(['status' =>1,'zfb_pid'=>$pid]);
        return json(['code'=>1,'msg'=>'密钥验证成功!']);
    }
    
    //PCQQ自挂通知接口，并且执行上线
    public function pcqq_auth($apiid='',$apikey='',$channel='',$status=1)
    {
        if(empty($apiid)  || empty($apikey)  || empty($channel))
        {
            return json(['code'=>100,'msg'=>'参数不可为空']);
        }
        $user = basic::where('user_id',$apiid)->where('appkey',$apikey)->find();
        if(empty($user))
        {
            return json(['code'=>0,'msg'=>'商户不存在或密钥错误!']);
        }
        //更新状态
        Db::name('ypay_account')->where('id', $channel)->where('user_id',$apiid)->update(['status' =>$status]);
        return json(['code'=>1,'msg'=>'密钥验证成功!']);
    }
    
    //自挂通道通过订单号写入订单二维码
    public function pcqq_creatqr($apiid='',$apikey='',$order='',$qrcode='')
    {
        if(empty($apiid)  || empty($apikey)  || empty($order) || empty($qrcode))
        {
            return json(['code'=>100,'msg'=>'参数不可为空']);
        }
        $user = basic::where('user_id',$apiid)->where('appkey',$apikey)->find();
        if(empty($user))
        {
            return json(['code'=>0,'msg'=>'商户不存在或密钥错误!']);
        }
        //更新状态
        $h5url = base64_encode('https://qun.qq.com/qrcode/index?data='.urlencode($qrcode));
        $h5url = 'mqqapi://forward/url?version=1&src_type=web&url_prefix='.$h5url;
        Db::name('ypay_order')->where('trade_no', $order)->where('user_id', $apiid)->update(['qrcode' =>$qrcode,'h5_qrurl' =>$h5url]);
        return json(['code'=>1,'msg'=>'写入成功!']);
    }
    
    //自挂通道检查是否有未支付的订单
    public function pcqq_checkorder($apiid,$apikey,$channel)
    {
        $user = basic::where('user_id',$apiid)->where('appkey',$apikey)->find();
        if(empty($user))
        {
            return json(['code'=>100,'msg'=>'商户不存在或密钥错误']);
        }
        if(empty($apiid)  || empty($apikey)  || empty($channel))
        {
            return json(['code'=>100,'msg'=>'参数不可为空']);
        }
        if($channel==0)
        {
            return json(['code'=>100,'msg'=>'不发起回调']);
        }
        $vaccount = Db::name('ypay_account')->where('id',$channel)->where('user_id',$user['user_id'])->where('code', 'qqpay_zg')->find();
        if(empty($vaccount))
        {
            return json(['code'=>100,'msg'=>'通道不存在']);
        }
        $orderlist = Db::name('ypay_order')->where('account_id',$vaccount['id'])->where('status',0)->where('out_time','>',time())->order('id desc')->select();
        return json(['code'=>0,'msg'=>'返回成功','data' => $orderlist]);
    }
    
    
    
    public function ali_checkorder($apiid,$apikey,$channel)
    {
        $user = basic::where('user_id',$apiid)->where('appkey',$apikey)->find();
        if(empty($user))
        {
            return json(['code'=>100,'msg'=>'商户不存在或密钥错误']);
        }
        if(empty($apiid)  || empty($apikey)  || empty($channel))
        {
            return json(['code'=>100,'msg'=>'参数不可为空']);
        }
        if($channel==0)
        {
            return json(['code'=>100,'msg'=>'不发起回调']);
        }
        $vaccount = Db::name('ypay_account')->where('id',$channel)->where('code', 'alipay_pc')->find();
        if(empty($vaccount))
        {
            return json(['code'=>100,'msg'=>'通道不存在']);
        }
        $order = Db::name('ypay_order')->where('account_id',$vaccount['id'])->where('status',0)->where('out_time','>',time())->order('id desc')->count();
        return json(['code'=>$order,'msg'=>'返回成功']);
    }
    
    public function alinotify($apiid,$apikey,$money,$channel,$memo)
    {
        $user = basic::where('user_id',$apiid)->where('appkey',$apikey)->find();
        if(empty($user))
        {
            return json(['code'=>0,'msg'=>'商户不存在或密钥错误']);
        }
        if(empty($money) || empty($memo) || empty($channel))
        {
            return json(['code'=>0,'msg'=>'参数不可为空']);
        }
        if($channel==0)
        {
            return json(['code'=>1,'msg'=>'不发起回调']);
        }
        $vaccount = Db::name('ypay_account')->where('id',$channel)->where('code', 'alipay_pc')->find();
        if(empty($vaccount))
        {
            return json(['code'=>0,'msg'=>'通道不存在']);
        }
        $order = Db::name('ypay_order')->where('account_id',$vaccount['id'])->where('status',0)->where('out_trade_no',$memo)->where('truemoney',$money)->where('out_time','>',time())->order('id desc')->find();
        if(!empty($order))
        {
            $url = Jialanshen::creat_callback($order);
            get_curl($url['notify']);
            return json(['code'=>1,'msg'=>'回调成功!']);
        }
        else
        {
            return json(['code'=>0,'msg'=>'订单超时或不存在']);
        }
    }

    
    public function alipay_dmf()
    {
        $data = Request::param('','','strip_tags');
        if(!$data)
        {
            return json(['code'=>0,'msg'=>'数据不可为空!']);
        }
        $order = Db::name('ypay_order')->where('out_trade_no',$data['out_trade_no'])->find();
        if(empty($order))
        {
            return json(['code'=>0,'msg'=>'当前订单不存在!']);
        }
        $account = Db::name('ypay_account')->where('id',$order['account_id'])->where('code','alipay_dmf')->find();
        if(empty($account))
        {
            return json(['code'=>0,'msg'=>'通道不存在!']);
        }
        
        $priKey = $account['cookie'];
        $res = Jialanshen::rsaCheck($data,$priKey);
        if($res===true && $data['trade_status'] == 'TRADE_SUCCESS')
        {
            $url = Jialanshen::creat_callback($order);
            get_curl($url['notify']);
            return json(['code'=>1,'msg'=>'回调成功!']);
        }
        else
        {
            return json(['code'=>0,'msg'=>'订单超时或不存在']);
        }
    }
    
    
     public function dopay_dmf()
    {
        $data = Request::param('','','strip_tags');
        if(!$data)
        {
            return json(['code'=>0,'msg'=>'数据不可为空!']);
        }
        $order = Db::name('ypay_order')->where('out_trade_no',$data['out_trade_no'])->find();
        if(empty($order))
        {
            return json(['code'=>0,'msg'=>'当前订单不存在!']);
        }
        $priKey = getConfig()['dmf_openid'];
        $res = Jialanshen::rsaCheck($data,$priKey);
        if($res===true)
        {
            $url = Jialanshen::creat_callback($order);
            get_curl($url['notify']);
            return json(['code'=>1,'msg'=>'回调成功!']);
        }
        else
        {
            return json(['code'=>0,'msg'=>'订单超时或不存在']);
        }
    }
    
    //转接异步通知
    public function epay_returnzj()
    {
        $data = Request::param('','','strip_tags');
        //查询订单是否存在
        $order = Db::table('ypay_order')->where('out_trade_no', $data['out_trade_no'])->find();
        if(empty($order))
        {
            echo '该订单不存在'; die;
        }
        //获取配置信息
        $user = Db::table('ypay_user')->where('id', $order['user_id'])->find();
        if(empty($user))
        {
            echo '该商户不存在'; die;
        }
        $paylist = m_paylist::where('user_id',$user['id'])->where('status',1)->order('id','desc')->find();
        //实例化配置信息
        $epayzj = new epay($paylist['pid'],$paylist['key'],$paylist['url']);
        $isSign = $epayzj->getEpaySignVeryfy($data,$data["sign"],$paylist['key']); //生成签名结果
        if(!$isSign)
        {
            echo '验签失败，请检查配置信息'; die;
        }
        else
        {
            //验证通过
            $url = Jialanshen::creat_callback($order);
            $tj_url = $url['return'];
            //跳转
            header("Location:$tj_url");
        }
    }
    
    //转接易支付同步回调
    public function epay_notifyzj()
    {
        $data = Request::param('','','strip_tags');
        //查询订单是否存在
        $order = Db::table('ypay_order')->where('out_trade_no', $data['out_trade_no'])->find();
        if(empty($order))
        {
            echo 'fail'; die;
        }
        //获取配置信息
        $user = Db::table('ypay_user')->where('id', $order['user_id'])->find();
        if(empty($user))
        {
            echo 'fail'; die;
        }
        $paylist = m_paylist::where('user_id',$user['id'])->where('status',1)->order('id','desc')->find();
        //实例化配置信息
        $epayzj = new epay($paylist['pid'],$paylist['key'],$paylist['url']);
        $isSign = $epayzj->getEpaySignVeryfy($data,$data["sign"],$paylist['key']); //生成签名结果
        if(!$isSign)
        {
            echo 'fail'; die;
        }
        else
        {
            //验证通过
            $url = Jialanshen::creat_callback($order);
            get_curl($url['notify']);
            echo 'success'; die;
        }
    }
    
    //获取WxPusher回调
    public function wxpusher(){
        $data = Request::param('','','strip_tags');
        if($data['action'] == 'app_subscribe'){
            try {
                Db::table('ypay_user')->where('id', $data['data']['extra'])->update(['wxpusher_uid' => $data['data']['uid']]);
            } catch (\Exception $e) {
                echo $e->getMessage();
            } 
        }
        
    }
    
}
