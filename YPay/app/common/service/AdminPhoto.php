<?php
declare (strict_types = 1);

namespace app\common\service;

use think\facade\Request;
use app\common\model\AdminPhoto as M;
use app\common\util\Qiniu as QiniuService;
use app\common\util\Oss as OssService;
class AdminPhoto
{
    // 添加
    public static function goAdd()
    {
        if (Request::isPost()){
            $data = Request::post();
            //数据验证
            if (!preg_match('/^[a-zA-z]+$/i',$data['name'])) return ['code' => 201, 'msg' => '目录格式不正确'];
            @mkdir(public_path().'upload'.DS.$data['name']);
        }
    }

    // 添加
    public static function goDel($name)
    {
        if($name=='阿里云') return ['msg'=>'非本地目录无法删除','code'=>201];
        //进行转义，禁止跨目录
        $name = str_replace("\\","/",$name);
        try{
            $view = public_path().'upload'.DS.$name;
            if (file_exists($view)) delete_dir($view);
            M::where('path',$name)->delete();
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }

    // 删除
    public static function goRemove($id)
    {
        try{
            self::del($id);
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }
    
    // 删除
    public static function goBatchRemove($ids)
    {
        if (!is_array($ids)) return ['msg'=>'数据不存在','code'=>201];
        try{
            foreach ($ids as $k) {
                self::del($k);
            }
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }

    // 添加
    public static function add($info,$href,$path,$type)
    {
        M::create([
            'name' => substr($info->getOriginalName(),0,50),
            'href' => $href,
            'path' => $path,
            'type' => $type,
            'ext' => $info->getOriginalExtension(),
            'mime' => $info->getOriginalMime(),
            'size' => $info->getSize(),
        ]);
    }

    // 删除
    public static function del($id)
    {
        $photo =  M::find($id);
        if($photo['type']=='阿里云'){
            OssService::alYunDel($photo['href']);
        }elseif($photo['type']=='七牛云'){
            QiniuService::QiniuDel($photo['href']);
        }else{
            //删除本地文件
            $path = '../public'.$photo['href'];
            if (file_exists($path)) unlink($path);
        }
        $photo->delete();
    }
}
