<?php
declare (strict_types = 1);

namespace app\common\service;

use think\facade\Request;
use app\common\model\YpayOrder as M;
use think\facade\Db;
use app\common\validate\YpayOrder as V;
use app\common\service\Jialanshen;

class YpayOrder
{
    // 添加
    public static function goAdd($data)
    {
        //验证
        $validate = new V;
        if(!$validate->scene('add')->check($data))
        return ['msg'=>$validate->getError(),'code'=>201];
        try {
            M::create($data);
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }
    
    // 编辑
    public static function goEdit($data,$id)
    {
        $data['id'] = $id;
        //验证
        $validate = new V;
        if(!$validate->scene('edit')->check($data))
        return ['msg'=>$validate->getError(),'code'=>201];
        try {
             M::update($data);
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }

    // 删除
    public static function goRemove($id)
    {
        $model = M::find($id);
        if ($model->isEmpty()) return ['msg'=>'数据不存在','code'=>201];
        try{
           $model->force()->delete();
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }
    
    //回调订单
    public static function goReback($id){
        $model = M::find($id);
        if ($model->isEmpty()) return ['msg'=>'数据不存在','code'=>201];
        try{
            $url = Jialanshen::creat_callback($model);
            $res = get_curl($url['notify']);
            if($res=='success' || $res =="fail")
            {
                M::where('id',$id)->update(['api_memo'=>$res]);
            }
            else
            {   
                M::where('id',$id)->update(['api_memo'=>'error']);
            }
           
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }

    // 批量删除
    public static function goBatchRemove($ids)
    {
        if (!is_array($ids)) return ['msg'=>'数据不存在','code'=>201];
        try{
            M::destroy($ids,true);
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }
    
    // 清除多少天前订单
    public static function goDaysRemove($day){
        $time = date("Y-m-d H:i:s",strtotime('-'.$day.' day'));
        
        try{ 
            M::whereTime('create_time','<',$time)->select()->delete();
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }
    // 一键清理未支付订单
    public static function goAllRemove()
    {
        try{
            
            M::destroy(function($query){
                $query->where('status','=',0);
            },true);
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }

}
