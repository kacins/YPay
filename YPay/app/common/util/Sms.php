<?php
namespace app\common\util;
use Overtrue\EasySms\EasySms;
use think\facade\Cache;

class Sms
{
    
    //获取设置项
    public static function getSet($code = ''){
        $data = getConfig();
        $num = mt_rand(100000,999999);//生产随机验证码
        Cache::set('captcha', $num, 300);
        $data['code'] = $num;
        return $data;
    }
    
    /**
     * @name 短信发送
     * @param string $phone  手机号码
     * @param int $code  验证码
     * @return bool  true
     */
    public static function send($phone,$code)
    {
        $data = getConfig();
        $config = [
            // HTTP 请求的超时时间（秒）
            'timeout' => 5.0,
        
            // 默认发送配置
            'default' => [
                // 网关调用策略，默认：顺序调用
                'strategy' => \Overtrue\EasySms\Strategies\OrderStrategy::class,
        
                // 默认可用的发送网关
                'gateways' => [$data['smstype'],'errorlog',],
            ],
            // 可用的网关配置
            'gateways' => [
                'errorlog' => [
                    'file' => '/tmp/easy-sms.log',
                ],
                'aliyun' => [
                    'access_key_id' => $data['alisms-accessKeyId'],
                    'access_key_secret' => $data['alisms-Secret'],
                    'sign_name' => $data['alisms-SignName'],
                ],
                'qcloud' => [
                    'sdk_app_id' => $data['tensms-AppId'], // 短信应用的 SDK APP ID
                    'secret_id' => $data['tensms-accessKeyId'], // SECRET ID
                    'secret_key' => $data['tensms-Secret'], // SECRET KEY
                    'sign_name' => $data['tensms-SignName'], // 短信签名
                ],
                'smsbao' => [
                    'user'  => $data['smsbao-user'],    //账号
                    'password'   => $data['smsbao-pass']   //密码
                ],
                //...
            ],
        ];
        
        $msg = null;
        $template = null;

        switch ($data['smstype']) {
            case 'smsbao':
                $msg = '【'.$data['smsbao-SignName'].'】您的验证码是'.$code.'，验证码3分钟有效。';
                break;
            case 'qcloud':
                $template = $data['tensms-LoginCodeId'];
                break;
             case 'aliyun':
                $template = $data['alisms-LoginCodeId'];
                break;
        }
        $easySms = new EasySms($config);
            $result = $easySms->send($phone,[
                'content'  => $msg,
                'template' => $template,
                'data' => [
                    'code' => $code
                ],
            ] );
    
            if($result[$data['smstype']]['status'] == 'success'){
                return ['code' => 200 , 'msg' => '发送成功'];
            }else{
                return ['code' => 201 , 'msg' => '发送失败'];
            }
        
    }
    
    /**
     * @name 注册短信
     * @param string $phone  手机号码
     * @param int $code  验证码
     * @return bool  true
     */
    public static function goReg($phone,$code)
    {
        $data = getConfig();
        $config = [
            // HTTP 请求的超时时间（秒）
            'timeout' => 5.0,
        
            // 默认发送配置
            'default' => [
                // 网关调用策略，默认：顺序调用
                'strategy' => \Overtrue\EasySms\Strategies\OrderStrategy::class,
        
                // 默认可用的发送网关
                'gateways' => [$data['smstype'],'errorlog',],
            ],
            // 可用的网关配置
            'gateways' => [
                'errorlog' => [
                    'file' => '/tmp/easy-sms.log',
                ],
                'aliyun' => [
                    'access_key_id' => $data['alisms-accessKeyId'],
                    'access_key_secret' => $data['alisms-Secret'],
                    'sign_name' => $data['alisms-SignName'],
                ],
                'qcloud' => [
                    'sdk_app_id' => $data['tensms-AppId'], // 短信应用的 SDK APP ID
                    'secret_id' => $data['tensms-accessKeyId'], // SECRET ID
                    'secret_key' => $data['tensms-Secret'], // SECRET KEY
                    'sign_name' => $data['tensms-SignName'], // 短信签名
                ],
                'smsbao' => [
                    'user'  => $data['smsbao-user'],    //账号
                    'password'   => $data['smsbao-pass']   //密码
                ],
                //...
            ],
        ];
        
        $msg = null;
        $template = null;

        switch ($data['smstype']) {
            case 'smsbao':
                $msg = '【'.$data['smsbao-SignName'].'】您的验证码是'.$code.'，验证码3分钟有效。';
                break;
            case 'qcloud':
                $template = $data['tensms-LoginCodeId'];
                break;
             case 'aliyun':
                $template = $data['alisms-LoginCodeId'];
                break;
        }
        $easySms = new EasySms($config);
            $result = $easySms->send($phone,[
                'content'  => $msg,
                'template' => $template,
                'data' => [
                    'code' => $code
                ],
            ] );
            if($result[$data['smstype']]['status'] == 'success'){
                return ['code' => 200 , 'msg' => '发送成功'];
            }else{
                return ['code' => 201 , 'msg' => '发送失败'];
            }
        
        
    }
}