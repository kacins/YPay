<?php
declare (strict_types=1);

namespace app\admin\controller;

use think\facade\Db;
use think\facade\Request;
use app\common\model\YpayVip;
use app\common\model\YpayPaylist;
use app\common\model\YpayQuicklogin;
use system\GoogleAuthenticator;
use think\facade\Session;

class Config extends Base
{
    protected $middleware = ['AdminCheck', 'AdminPermission'];

    // 系统配置
    public function index()
    {
    if (Request::isPost()) {
            $data = Request::post('', '', '');
            
            $info = $this->getConfig($data);
       
            if(isset($info['url'])){
                return json($info);
            }
            return $this->getJson($info);
        }
        
        $data = $this->getConfig();
        foreach ($data as $key => $value){
            if($key == 'diy_recharge' && !empty($value)){
                $array = explode(",", $value);
                for ($i = 0; $i < count($array); $i++) {
                    $array[$i] = '"' . $array[$i] . '"';
                }
                $data['diy_recharge'] = implode(",", $array);
            }elseif($key == 'diy_demoPay' && !empty($value)){
                $array = explode(",", $value);
                for ($i = 0; $i < count($array); $i++) {
                    $array[$i] = '"' . $array[$i] . '"';
                }
                $data['diy_demoPay'] = implode(",", $array);
            }
        }
        
        return $this->fetch('', [
            'data' => $data,
            'domain' => Request::domain(),
            'pay' => YpayPaylist::where(['status'=> 1,'user_id' => 0])->select(),
        ]);
    }
    
    //获取谷歌二维码
    public function getGoogleAuthQrCode(){
        //谷歌验证码
        $google=new GoogleAuthenticator();
        //生成验证秘钥
        $secret=$google->createSecret();
        //生成验证二维码 $username 需要绑定的用户名
        $qrCodeUrl = $google->getQRCodeGoogleUrl('Admin', $secret);
        Session::set('secret', $secret);
        return json(['code'=>200,'msg'=>$qrCodeUrl]);
    }
    
     //绑定谷歌信息
    public function bindGoogleAuth(){
        $data = Request::param('','','strip_tags');
        //获取session信息
        $secret = Session::get('secret');
        $google =new GoogleAuthenticator();
        $checkResult = $google->verifyCode($secret, $data['google_captcha'], 4);
        if ($checkResult)
        {
            Db::table('admin_config')->where("config_name",'isAdminSecurity')->update(['config_value' => 1]);
            Db::table('admin_config')->where("config_name",'adminSecurityKey')->update(['config_value' => $secret]);
            return json(['code'=>200,'msg'=>'绑定成功']);
        }
        else
        {
            return json(['code'=>201,'msg'=>'谷歌验证码错误或未绑定']);
        }
    }
    
    //解绑谷歌验证码
    public function uBindGoogleAuth(){
        $data = Request::param('','','strip_tags');
        //获取用户的密钥信息
        $google =new GoogleAuthenticator();
        $admin = Db::table('admin_config')->where("config_name",'adminSecurityKey')->find();
        //$google_secret 存入的谷歌秘钥  ，$code 谷歌动态验证码
        $checkResult = $google->verifyCode($admin['config_value'], $data['google_captcha'], 4);
        if ($checkResult)
        {
            Db::table('admin_config')->where("config_name",'isAdminSecurity')->update(['config_value' => 0]);
            Db::table('admin_config')->where("config_name",'adminSecurityKey')->update(['config_value' => '']);
            return json(['code'=>200,'msg'=>'解绑成功']);
        }
        else
        {
            return json(['code'=>201,'msg'=>'谷歌验证码错误']);
        }
    }
}
