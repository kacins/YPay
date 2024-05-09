<?php
declare (strict_types = 1);

namespace app\common\validate;

use think\Validate;
class YpayUser extends Validate
{
    protected $rule = [
        'username|用户名' => 'require',
        'password|密码' => 'require',
        'mobile|手机号' => 'mobile',
        'email' => 'email',
        'captcha'=>'require'
        
    ];
    
    protected $message = [
        'username.unique' => '用户名已存在',
        'email.unique' => '邮箱已存在',
        'mobile.unique' => '手机号已存在',
        'email.require' => '请输入您的邮箱',
        'mobile.require' => '请输入您的手机号',
        'captcha.require' => '请输入正确的验证码',
    ];

    /**
     * 添加
     */
    public function sceneAdd()
    {
        return $this->only(['username','password','email','mobile'])
            ->append('email', 'unique:ypay_user')
            ->append('mobile', 'unique:ypay_user')
            ->append('username', 'unique:ypay_user');
            
    }
    
    public function sceneLogin()
    {
        if(getConfig()['captcha-type']==1){
            return $this->only(['username','password','ordinary_captcha'])->append('ordinary_captcha|验证码', 'require|captcha');
        }else{
            return $this->only(['username','password'])->append('ordinary_captcha|验证码', 'require|captcha');
        }
    }
    
    public function sceneEmail()
    {
        return $this->only(['email','captcha'])
        ->append('email', 'require:email');
    }
    
    public function sceneMobile()
    {
        return $this->only(['mobile','captcha'])
        ->append('mobile', 'require:mobile');
    }
    
    /**
     * 编辑
     */
    public function sceneEdit()
    {
        return $this->only(['email','mobile'])
            ->append('email', 'unique:ypay_user')
            ->append('mobile', 'unique:ypay_user');
    }
}
