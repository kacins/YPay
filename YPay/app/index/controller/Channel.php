<?php
declare (strict_types = 1);

namespace app\index\controller;
use think\facade\Session;
use think\facade\Request;
use think\facade\View;
use app\common\util\Upload as Up;
use app\common\service\YpayUser as S;
use think\facade\Db;
use app\common\model\YpayOrder;
use app\common\model\YpayAccount as Yaccount ;
use app\common\model\YpayPaylist as paylist;
use app\common\service\YpayAccount;
use app\common\service\YpayPaylist as s_paylist;
use app\common\model\YpayUserbasic as basic;
use app\common\service\Jialanshen;
use app\common\core\core;

class Channel extends \app\BaseController
{
    protected $middleware = ['FrontCheck'];
    
    public function upload()
    {
        $res = Up::qrputFile(Request::file(),Request::post('path'),Request::post('channel_code'));
        return $this->getJson($res);
    }
    
    //通道列表
    public function index()
    {
        if (Request::isAjax()) {
            $account = Yaccount::getUserList(S::getUserId());
            json_encode($account, JSON_FORCE_OBJECT);
            return $account;
        }
        $channel = Db::table('admin_channel')->where(['status'=>1,'type'=>'wxpay'])->order('sort', 'desc')->select();

        View::assign('channel', $channel);
        View::assign(
            [
                'user' => S::getUser(),
                'vip' => S::getVip(),
            ]);
        return $this->fetch();
    }
    
    
    //通道配置
    public function basic()
    {
        if (Request::isAjax()) {
            $paylist = paylist::getUserList(S::getUserId());
            json_encode($paylist, JSON_FORCE_OBJECT);
            return $paylist;
        }
        $basic = basic::where('user_id',S::getUserId())->find();
        
        $cashierMode = 
        [
            ['id' => 2,'name' => '模式①:转账模式(风控制:低)']
        ];
        
        $channelMode = 
        [
            ['id' => 1,'name' => '模式①:带备注跳转模式','cashierType' => 'all'],
            ['id' => 2,'name' => '模式②:无备注跳转模式','cashierType' => 'all'],
            ['id' => 3,'name' => '模式③:手动输入金额跳转模式','cashierType' => 'all'],
            ['id' => 4,'name' => '模式④:锁死金额/订单号跳转模式','cashierType' => 'all'],
        ];
        
        
        
        View::assign(
            [
                'user' => S::getUser(),
                'vip' => S::getVip(),
                'basic' => S::getBasic(),
                'cashierMode' => $cashierMode,
                'channelMode' => $channelMode,
            ]);
        return $this->fetch();
    }
    
    //筛选支付宝交易模式
    public function cashierMode(){
        $data = Request::param();
        $channelMode = 
        [
            ['id' => 1,'name' => '模式①:带备注跳转模式','cashierType' => 'all'],
            ['id' => 2,'name' => '模式②:无备注跳转模式','cashierType' => 'all'],
        ];
        return json(['code'=>1,'channelMode'=>$channelMode]);
    }
    
    // 修改通道配置信息
    public function edit_basic(){
        if (Request::isAjax()) {
            return $this->getJson(S::goBasicEdit(Request::param('','','strip_tags'),S::getUserId()));
        }
    }
    
    
    
    //添加转接通道
    public function addtransfer(){
        if (Request::isAjax()) {
            $data = Request::post();
            $data['user_id'] = Session::get('front.id');
            return $this->getJson(s_paylist::goAdd($data));
        }
    }
    
    //修改转接通道
    public function editTransfer(){
        if (Request::isAjax()) {
            $data = Request::post();
            return $this->getJson(s_paylist::goEdit($data,$data['id']));
        }
    }
    
    // 删除转接通道
    public function delTransfer(){
        $data = Request::param('','','strip_tags');
        return $this->getJson(s_paylist::goRemove($data['id']));
    }
    
    //更改转接通道状态
    public function editTransferStatus(){
        $data = Request::param('','','strip_tags');
        return $this->getJson(s_paylist::goStatus($data['status'],$data['id']));
    }
    
    public function type()
    {
        $data = Request::param();
        $channel = Db::table('admin_channel')->where(['status'=>1,'type'=>$data['id']])->order('sort', 'desc')->select();
        return json(['code'=>1,'channel'=>$channel]);
    }
    
    //新增通道
    public function addchannel()
    {
        $data = Request::param('','','strip_tags');
        $vip = S::getVip();//获取对应套餐配置信息
        
        if($data['code']=='wxpay_dy' || $data['code']=='wxpay_zg' || $data['code']=='wxpay_app')
        {
            if($data['code']=='wxpay_dy'){
                if(empty($data['wxname']))
                {
                    return ['msg'=>"收款微信昵称不可为空",'code'=>201];
                }
                $verywx = Yaccount::where('wxname',$data['wxname'])->find();
                if(!empty($verywx))
                {
                    return ['msg'=>"收款微信昵称已存在,请检查",'code'=>201];
                }
            }
        }
        return $this->getJson(YpayAccount::goAdd($data));
    }
    
    
    //修改支付宝当面付/商家账单通道
    public function editAliPay(){
        $data = Request::param('','','strip_tags');
        return $this->getJson(YpayAccount::goEditAliPay($data));
    }
    
   //修改微信APP挂机/自挂/店员通道
    public function editWxPay(){
        $data = Request::param('','','strip_tags');
        if($data['code']=='wxpay_dy'){
            if(empty($data['wxname']))
            {
                return ['msg'=>"收款微信昵称不可为空",'code'=>201];
            }
        }
        return $this->getJson(YpayAccount::goEditWxPay($data));
    }
    
    //修改QQ通道
    public function editQQPay(){
        $data = Request::param('','','strip_tags');
        return $this->getJson(YpayAccount::goEditQQPay($data));
    }
    
    //获取二维码信息
    public function GetQrlistQrcode($id)
    {
        return json(YpayAccount::GetQrlistQrcode($id));
    }
    
    
    
    //获取扫码状态
    public function GetChannelLoginStatus($id='')
    {
        return json(YpayAccount::GetChannelLoginStatus($id));
    }
    
    //删除通道 参数:通道ID
    public function DelChannel($id='')
    {
        //创建Core实例
        $core  = new Core();
        //查询通道信心
        $account =  Db::table('ypay_account')->where('id',$id)->find();
        
        if($account['code']=='wxpay_cloud'){
            // 执行删除云端内微信
            $core->getDelWechatAccount($account['wx_guid'],$account['vcloudurl']);
        }
        
        try {
            //执行删除该通道
            Db::table('ypay_account')->where('id',$id)->where('user_id',S::getUserId())->delete();
            return json(['code'=>1,'msg'=>'删除成功!']);
        } catch (\Exception $e){
          return ['msg'=>'请检查通道是否存在','code'=>201]; 
        }
        
    }
    
    //更改收款状态
    public function SaveStatus($id='',$status='')
    {
        $a = Db::table('ypay_account')->where('id',$id)->where('user_id',S::getUserId())->find();
        if(empty($a))
        {
            return json(['code'=>0,'msg'=>'通道不存在!']);
        }
        YpayAccount::goIsStatus($status,$id);
        return json(['code'=>1,'msg'=>'操作成功!']);
    }
    
    //测试支付
    public function testPay(){
        
        $temp = Request::param('','','strip_tags');
        $request = \think\facade\Request::instance();
        // 生成订单号
        if(getConfig()['isDiy_orderNo'] == 1){
            $trade_no=getConfig()['diy_orderNo'].date("YmdHis").rand(11111,99999);
            $out_trade_no = getConfig()['diy_orderNo'].date("YmdHis").rand(11111,99999);
        }else{
            $trade_no='Y'.date("YmdHis").rand(11111,99999);
            $out_trade_no = 'Y'.date("YmdHis").rand(11111,99999);
        }
        
        //获取通道信息
        $account =  Db::name('ypay_account')->where('id',$temp['id'])->find();//获取通道
        
        //检查通道是否掉线
        if($account['status'] == 0){
            return json(['code'=>201,'msg'=>'通道处于掉线状态']);
        }
        
        //检查通道是否关闭
        if($account['is_status'] == 0){
            return json(['code'=>201,'msg'=>'通道收款开关关闭']);
        }
        
        //创建测试数据数组
        $data =   
            [
                "type"  => $account['type'],
                "out_trade_no"  => $out_trade_no,
                "pid" => S::getUserId(),
                "money"      => $temp['money'],//订单金额
                'name' => '测试支付',
            ];
        $data["notify_url"] =  $request->root(true).'/Notify/testPay';//异步通知地址
        $data["return_url"] =  $request->root(true).'/Channel/Index';//同步通知地址
        $action = $account['code'];
        $res = Jialanshen::$action($trade_no,$account,$data,S::getUser());
        if($res)
        {
            return json(['code'=>200,'url'=>'/Pay/console?trade_no='.$trade_no]);
        }
        else
        {
            View::assign('error_tips', "订单生成错误,请重新发起支付");
            View::assign('error_url', '/User');
            return $this->fetch();
        }
    }
    
}
