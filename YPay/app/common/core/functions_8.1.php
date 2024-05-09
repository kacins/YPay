<?php
declare (strict_types=1);

namespace app\common\core;

use think\facade\Cache;
use think\facade\Db;
use think\facade\Request;
use think\facade\Config;
use think\facade\Session;

class Core
{
    
    //获取商城总览广告位
    public static function getShopAd(){
        
        //获取对应数据
        $result = self::getAdCron('1752944621708677121','1752944525415845889');
        
        $data = [];
        
        //判断并且返回对应参数
        if($result['success'] && $result['code'] == 200){
            foreach ($result['result'] as $key => $value) {
                $data[] = ['title' => $value['title'],'url' => $value['url'],'images'=>'https://api.yfx.top/sys/common/static/'.$value['images'],'expireDate'=>$value['expireDate']];
            }
            return $data;
        }
    }
    
    //获取支付推荐
    public static function getEPayAd(){
        
        //获取对应数据
        $result = self::getAdCron('1752944434504306690','1752944525415845889');
        
        $data = [];
        
        //判断并且返回对应参数
        if($result['success'] && $result['code'] == 200){
            foreach ($result['result'] as $key => $value) {
                $data[] = ['title' => $value['title'],'url' => $value['url'],'expireDate'=>$value['expireDate']];
            }
            return $data;
        }
    }
    
    //获取聚合登录推荐
    public static function getLoginAd(){
        
        //获取对应数据
        $result = self::getAdCron('1752944469476413442','1752944525415845889');
        
        $data = [];
        
        //判断并且返回对应参数
        if($result['success'] && $result['code'] == 200){
            foreach ($result['result'] as $key => $value) {
                $data[] = ['title' => $value['title'],'url' => $value['url'],'expireDate'=>$value['expireDate']];
            }
            return $data;
        }
    }
    
    //获取控制台左边推荐
    public static function getHomeLeftAd(){
        
        //获取对应数据
        $result = self::getAdCron('1752944645544906754','1752944525415845889');
        
        $data = [];
        
        //判断并且返回对应参数
        if($result['success'] && $result['code'] == 200){
            foreach ($result['result'] as $key => $value) {
                $data[] = ['title' => $value['title'],'url' => $value['url'],'expireDate'=>$value['expireDate']];
            }
            return $data;
        }
    }
    
        //获取控制台右边推荐
    public static function getHomeRightAd(){
        
        //获取对应数据
        $result = self::getAdCron('1753297841471680513','1752944525415845889');
        
        $data = [];
        
        //判断并且返回对应参数
        if($result['success'] && $result['code'] == 200){
            foreach ($result['result'] as $key => $value) {
                $data[] = ['title' => $value['title'],'url' => $value['url'],'expireDate'=>$value['expireDate']];
            }
            return $data;
        }
    }
    
    //获取广告核心
    public static function getAdCron($positionId,$typeId){
                // 请求URL
        $url = "https://api.yfx.top/jeecg-system/uc/ad/list";
        
        // 参数数组
        $params = array(
            'positionId' => $positionId,
            'typeId' => $typeId
        );
        
        // 初始化curl
        $curl = curl_init($url);
        
        // 设置curl选项
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
        
        // 发送请求并获取返回结果
        $response = curl_exec($curl);
        
        // 关闭curl
        curl_close($curl);
        
        // 将返回结果转换为数组
        $result = json_decode($response, true);
        
        return $result;
    }
    
}
