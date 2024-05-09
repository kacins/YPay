<?php
declare (strict_types = 1);

namespace app\common\service;

use think\facade\Request;
use think\facade\Db;
use app\common\model\AdminRole as M;
use app\common\validate\AdminRole as V;

class AdminRole
{
    // 添加
    public static function goAdd($data)
    {
        //验证
        $validate = new V;
        if(!$validate->check($data))
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
        if(!$validate->check($data))
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
            $model->delete();
            Db::name('admin_admin_role')->where('role_id', $id)->delete();
            Db::name('admin_role_permission')->where('role_id', $id)->delete();
            rm();
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }

    // 用户分配角色
    public static function goRole($data,$id)
    {
        if($data){
            Db::startTrans();
            try{
                //清除原先的角色
                Db::name('admin_admin_role')->where('admin_id',$id)->delete();
                //添加新的角色
                foreach ($data as $v){
                    Db::name('admin_admin_role')->insert([
                        'admin_id' => $id,
                        'role_id' => $v,
                    ]);
                }
                Db::commit();
                rm();
            }catch (\Exception $e){
                Db::rollback();
                return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
            }
        }
    }

    // 用户分配直接权限
    public static function goPermission($data,$id)
    {
        if($data){
            Db::startTrans();
            try{
                //清除原有的直接权限
                Db::name('admin_role_permission')->where('role_id',$id)->delete();
                //填充新的直接权限
                foreach ($data as $p){
                    Db::name('admin_role_permission')->insert([
                        'role_id' => $id,
                        'permission_id' => $p,
                    ]);
                }
                rm();
                Db::commit();
            }catch (DbException $e){
                Db::rollback();
                return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
            }
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
        $limit = input('get.limit');
        $list = M::onlyTrashed()->order('id','desc')->paginate($limit);
        return ['code'=>0,'data'=>$list->items(),'extend'=>['count' => $list->total(), 'limit' => $limit]];
    }

}
