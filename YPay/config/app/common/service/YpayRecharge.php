<?php
declare (strict_types = 1);

namespace app\common\service;

use think\facade\Request;
use app\common\model\YpayRecharge as M;
use app\common\validate\YpayRecharge as V;
use app\common\model\YpayOrder as order;

class YpayRecharge
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

    // 状态
    public static function goStatus($data,$id)
    {
        $model =  M::find($id);
        if ($model->isEmpty())  return ['msg'=>'数据不存在','code'=>201];
        try{
            $model->save([
                'status' => $data,
            ]);
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
           $model->delete();
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }

    // 批量删除
    public static function goBatchRemove($ids)
    {
        if (!is_array($ids)) return ['msg'=>'数据不存在','code'=>201];
        try{
            M::destroy($ids);
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }
    
    // 一键清理未支付订单
    public static function allRemove()
    {
        try{
            M::where('status','=',0)->delete();
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }
    
        //回调订单
    public static function goReback($id){
        $model = M::find($id);
        $order = order::where('out_trade_no',$model['out_trade_no'])->find();
        if ($model->isEmpty()) return ['msg'=>'数据不存在','code'=>201];
        try{
            $url = Jialanshen::creat_callback($order);
            $res = get_curl($url['notify']);
           
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }

    // 获取列表
    public static function goRecycle()
    {
        if (Request::isPost()){
            $ids = Request::param('ids');
            if (!is_array($ids)) return ['msg'=>'参数错误','code'=>'201'];
            try{
                if(Request::param('type')){
                    $data = M::onlyTrashed()->whereIn('id', $ids)->select();
                    foreach($data as $k){
                        $k->restore();
                    }
                }else{
                    M::destroy($ids,true);
                }
            }catch (\Exception $e){
                return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
            }
            return ['msg'=>'操作成功'];
        }
        //按用户名
        $where = [];
        $limit = input('get.limit');
        
               //按支付类型查找
               if ($type = input("type")) {
                   $where[] = ["type", "like", "%" . $type . "%"];
               }
               //按商户订单查找
               if ($trade_no = input("trade_no")) {
                   $where[] = ["trade_no", "like", "%" . $trade_no . "%"];
               }
               //按本地订单查找
               if ($out_trade_no = input("out_trade_no")) {
                   $where[] = ["out_trade_no", "like", "%" . $out_trade_no . "%"];
               }
               //按会员ID查找
               if ($user_id = input("user_id")) {
                   $where[] = ["user_id", "like", "%" . $user_id . "%"];
               }
               //按订单状态查找
               if ($status = input("status")) {
                   $where[] = ["status", "like", "%" . $status . "%"];
               }
               //按创建时间查找
               $start = input("get.create_time-start");
               $end = input("get.create_time-end");
               if ($start && $end) {
                   $where[]=["create_time","between",[$start,date("Y-m-d",strtotime("$end +1 day"))]];
               }
        $list = M::onlyTrashed()->where($where)->paginate($limit);
        return ['code'=>0,'data'=>$list->items(),'extend'=>['count' => $list->total(), 'limit' => $limit]];
    }
}
