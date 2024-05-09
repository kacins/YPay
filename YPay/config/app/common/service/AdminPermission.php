<?php
declare (strict_types = 1);

namespace app\common\service;

use think\facade\Session;
use think\facade\Request;
use think\facade\Db;
use app\common\model\AdminPermission as M;
use app\common\validate\AdminPermission as V;

class AdminPermission
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
            rm();Session::clear();
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
            rm();Session::clear();
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
            rm();
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }

    // 删除
    public static function goRemove($id)
    {
        $model = M::with('child')->find($id);
        if(Request::param('type')){
            $arr = Db::name('admin_permission')->where('pid',$id)->field('id,pid')->select();
            foreach($arr as $k=>$v){
                Db::name('admin_permission')->where('pid',$v['id'])->delete();
                Db::name('admin_role_permission')->where('permission_id',$v['id'])->delete();
                Db::name('admin_admin_permission')->where('permission_id',$v['id'])->delete();
            }
        }else{
            if (isset($model->child) && !$model->child->isEmpty()){
                return ['msg'=>'存在子权限，确认删除后不可恢复','code'=>201];
            }
        }
        $model->delete();
        Db::name('admin_role_permission')->where('permission_id', $id)->delete();
        Db::name('admin_admin_permission')->where('permission_id', $id)->delete();
        rm();Session::clear();
    }
    
    //创建菜单
    public static function goMenu($data)
    {
        $path = '/'.$data['left'].'.'.$data['right'].'/';
        $data = [
            'pid' => $data['menu'],
            'title' => $data['ename'],
            'href' =>$path.'index',
        ];
        $menu = M::create(array_merge($data, [
            'icon'=>'layui-icon layui-icon-fire'
        ]));
        $crud = [
            'add' => "新增",
            'edit' => "修改",
            'remove' => "删除",
            'batchRemove' => "批量删除",
            'recycle' => "回收站"
        ];
        $data['pid'] = $menu['id'];
        foreach ($crud as $k=>$v) {
            $data['title'] = $v.$menu['title'];
            $data['href'] = $path.$k;
            M::create($data);
        }
    }

}
