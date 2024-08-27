<?php
declare (strict_types = 1);

namespace app\index\controller;
use think\facade\Cache;
use think\facade\Session;
use think\facade\Request;
use think\facade\View;
use app\common\service\YpayUser as S;
use app\common\model\YpayUser as M;
use app\common\validate\YpayUser as V;
use think\facade\Db;
use think\api\Client;
use app\common\model\AdminFrontLog as Log;
use app\common\model\YpayDomain as domain;
use app\common\model\YpayTicket as ticket;
use app\common\service\Notice as notice;

class My extends \app\BaseController
{
    protected $middleware = ['FrontCheck'];
    
    //控制台页面
    public function userpro()
    {
        View::assign(
            [
                'user' => S::getUser(),
                'vip' => S::getVip(),
            ]);
        if (Request::isAjax()){
            $data = Request::param('','','strip_tags');
            $validate = new V;
            if(!$validate->scene('edit')->check($data))
            return ['msg'=>$validate->getError(),'code'=>201];
            Db::name('ypay_user')->where('id', S::getUserId())->update(['mobile'=>$data['mobile'],'email'=>$data['email']]);
            return json(['code'=>1,'msg'=>'个人信息修改成功!']);
        }
        return $this->fetch();
    }

    
    //安全设置页面
    public function Security()
    {
        //获取配置参数
        $config = getConfig();
        $request = \think\facade\Request::instance();
        //判断网关地址
        if($config['is_pay_api'] == 1){
            $url = $config['pay_api'];
        }else{
            $url = $request->root(true).'/';
        }
        View::assign(
            [
                'user' => S::getUser(),
                'vip' => S::getVip(),
                'url'=> $url
            ]);
        return $this->fetch();
    }
    
    //通知设置页面
    public function Notifications()
    {
        if (Request::isAjax()){
            $this->getJson(S::saveNotifications(Request::param('','','strip_tags')));
        }
        View::assign(
            [
                'user' => S::getUser(),
                'vip' => S::getVip(),
                'basic' => S::getBasic(),
                'notice' => S::getNotice(),
                'noticeType' => S::getNoticeType()
            ]);
        return $this->fetch();
    }
    
    //快捷绑定页面
    public function Connections()
    {
        View::assign(
            [
                'user' => S::getUser(),
                'vip' => S::getVip(),
                'epwModel' =>S::emwModel()
            ]);
        return $this->fetch();
    }
    
    //修改密码
    public function updatepwd()
    {
        if (Request::isAjax()){
            $this->getJson(S::goPass(Request::param('','','strip_tags')));
        }
        return $this->fetch('Security');
    }
    
        //注销账户
    public function cancellation(){
       $user_res = Db::name('ypay_user')->where('id',S::getUserId())->delete();
       $basic_res = Db::name('ypay_userbasic')->where('user_id',S::getUserId())->delete();
       if($user_res && $basic_res){
          return json(['code'=>200]); 
       }
    }
    
    
    public function loginlog()
    {
        $log = Log::getUserList(S::getUserId());
        json_encode($log, JSON_FORCE_OBJECT);
        return $log;
    }
    
    //重置密钥
    public function GeneratingKey()
    {
        return json(['key'=>S::goUserKey(),'code'=>1]);
    }
    
    //获取解绑手机/邮箱验证码
    public function getUBindCode($mobile='',$email='',$bind=''){
        return $this->getJson(S::getCode('UBind',$mobile,$email,$bind));
    }
    
    //获取绑定手机/邮箱验证码
    public function getBindCode($mobile='',$email='',$bind=''){
        return $this->getJson(S::getCode('bind',$mobile,$email,$bind));
    }
    
    //执行绑定或者解绑邮箱操作
    public function bindOrUBindEmail(){
        // 获取页面提交的数据传值
        if (Request::isAjax()){
            
            $data = Request::param('','','strip_tags');
            $msg = '绑定成功';
            $update = $data['email'];
            if($data['type'] == 2){
                $update = null;
                $msg = '解绑成功';
            }
            
            //验证验证码是否为空
            $is_captcha =  S::is_captcha(1,$data['captcha']);
            // 判断是否有值返回
            if(!empty($is_captcha)){
               return  $this->getJson($is_captcha);
            }
            //验证
            $validate = new V;
            //验证数据是否填写
            if(!$validate->scene('email')->check($data))return ['msg'=>$validate->getError(),'code'=>201];
            //验证验证码是否正确
            $code = Cache::get('captcha');
            if($data['captcha']==$code)//验证通过
            {
                try {
                    M::where('id',S::getUserId())->update(['email' => $update]);
                    return json(['code'=>1,'msg'=>$msg]);
                } catch (\Exception $e) {
                  return json(['msg'=>'操作失败'.$e->getMessage(),'code'=>201]);
                }
            }
            else
            {
                return json(['msg'=>'验证码错误','code'=>201]);
            }
            
            
        }
    }
    
    //执行绑定或者解绑手机号操作
    public function bindOrUBindMobile(){
        // 获取页面提交的数据传值
        if (Request::isAjax()){
            
            $data = Request::param('','','strip_tags');
            $msg = '绑定成功';
            $update = $data['mobile'];
            if($data['type'] == 2){
                $update = null;
                $msg = '解绑成功';
            }
            
            //验证验证码是否为空
            $is_captcha =  S::is_captcha(1,$data['captcha']);
            // 判断是否有值返回
            if(!empty($is_captcha)){
               return  $this->getJson($is_captcha);
            }
            //验证
            $validate = new V;
            //验证数据是否填写
            if(!$validate->scene('mobile')->check($data))return ['msg'=>$validate->getError(),'code'=>201];
            //验证验证码是否正确
            $code = Cache::get('captcha');
            if($data['captcha']==$code)//验证通过
            {
                try {
                    M::where('id',S::getUserId())->update(['mobile' => $update]);
                    return json(['code'=>1,'msg'=>$msg]);
                } catch (\Exception $e) {
                  return json(['msg'=>'操作失败'.$e->getMessage(),'code'=>201]);
                }
            }
            else
            {
                return json(['msg'=>'验证码错误','code'=>201]);
            }
            
            
        }
    }
    
}
