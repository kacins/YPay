<?php
declare (strict_types = 1);

namespace app\common\service;
use think\facade\Db;
use think\facade\Request;
use think\facade\Config;
use app\common\model\YpayQuicklogin as Quicklogin;

class Third
{
    
    //聚合登录
    public static function OAuthAccountLogin($type)
    {
        
        $config = getConfig();
        switch ($type) {
            case 'qq':
                 $where = $config['qq_login'];
                break;
            case 'wx':
                 $where = $config['wechat_login'];
                break;
            default:
                // code...
                break;
        }
        $temp = Quicklogin::where('status',1)->find($where);
        //获取登录配置信息
        $appid = $temp['appid'];
        $appkey= $temp['appkey'];
        $request = \think\facade\Request::instance();
        $siteurl = $request->root(true).'/Notify/CallBack';
        $url = $temp['url'] . 'connect.php?act=login&appid='.$appid.'&appkey='.$appkey.'&type='.$type.'&redirect_uri='.$siteurl;
        //避免有些聚合登录回调卡顿 信息不能及时通知
        $is_while = true;
        
        while($is_while){
            $res = get_curl($url);
            if($res != false){
                $is_while = false;
            }
        }
        $res = json_decode($res,true);
        return $res;
    }
    
    public static function CallBackSid($type,$code)
    {
        //获取登录配置信息
        $config = getConfig();
        switch ($type) {
            case 'qq':
                 $where = $config['qq_login'];
                break;
            case 'wx':
                 $where = $config['wechat_login'];
                break;
            default:
                // code...
                break;
        }
        $temp = Quicklogin::where('status',1)->find($where);
        //获取登录配置信息
        $appid = $temp['appid'];
        $appkey= $temp['appkey'];
        $url = $temp['url'] . 'connect.php?act=callback&appid='.$appid.'&appkey='.$appkey.'&type='.$type.'&code='.$code;
       //避免有些聚合登录回调卡顿 信息不能及时通知
        $is_while = true;
        
        while($is_while){
            $res = get_curl($url);
            if($res != false){
                $is_while = false;
            }
        }
        $res = json_decode($res,true);
        return $res;
    }
    
    //QQ官方登录
    public static function QQ(){
        $config = getConfig();
        $where = $config['qq_login'];
        $temp = Quicklogin::where('status',1)->find($where);
        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        $qqOAuth = new \Yurun\OAuthLogin\QQ\OAuth2($temp['appid'], $temp['appkey'],$http_type.$_SERVER['HTTP_HOST'].'/Notify/qqcallback');
        return $qqOAuth;
    }
    
    // QQ官方登录回调
    public static function QQNotify(){
        $config = getConfig();
        $where = $config['qq_login'];
        $temp = Quicklogin::where('status',1)->find($where);
        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        $qqOAuth = new \Yurun\OAuthLogin\QQ\OAuth2($temp['appid'], $temp['appkey'],$http_type.$_SERVER['HTTP_HOST'].'/Notify/qqcallback');
        return $qqOAuth;
    }
    
}
