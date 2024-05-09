<?php
declare (strict_types = 1);

namespace app\common\service;

use think\facade\Request;
use app\common\model\AdminChannel as M;
use app\common\validate\AdminChannel as V;

class AdminChannel
{

    
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
    
     // 更改排序
    public static function goSort($data,$sort_new,$sort_old)
    {
        //$data为表单当前分页传递的数据
        //$sort_new为拖动列的新数据索引,在当前页数据内的
        //$sort_old为拖动列的旧数据索引,在当前页数据内的
        
        foreach ($data as $key => $value){
            $model =  M::find($value['id']);
            if ($model->isEmpty())  return ['msg'=>'数据不存在','code'=>201];
            if($sort_new > $sort_old){ //判断是向下拖动还是向上拖动 例:[新数据索引 > 旧数据索引] 就是向下拖动 , 另则反之
                if($key == $sort_new){ 
                    $model->save(['sort'=>$value['sort']-($sort_new-$sort_old)]);//必须[新数据索引 - 旧数据索引],或者会为负数
                    break;
                }elseif($sort_new >=$key && $key >= $sort_old){//判断是否在新/旧索引之间
                      $model->save(['sort'=>$value['sort']+1]);//向下拖动 范围内索引+1让位置
                }
            }else{   
                if($key == $sort_new){
                  $model->save(['sort'=>$value['sort']+($sort_old-$sort_new)]);//必须[新旧数据索引 - 新数据索引],或者会为负数
                }elseif($key >=$sort_new && $sort_old >= $key){//判断是否在新/旧索引之间
                    $model->save(['sort'=>$value['sort']-1]);//向上拖动 范围内索引-1让位置
                }
            }
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
        
               //按通道名称查找
               if ($name = input("name")) {
                   $where[] = ["name", "like", "%" . $name . "%"];
               }
               //按通道标识查找
               if ($code = input("code")) {
                   $where[] = ["code", "like", "%" . $code . "%"];
               }
        $list = M::onlyTrashed()->where($where)->paginate($limit);
        return ['code'=>0,'data'=>$list->items(),'extend'=>['count' => $list->total(), 'limit' => $limit]];
    }
}
