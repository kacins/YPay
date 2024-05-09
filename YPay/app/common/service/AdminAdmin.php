<?php
declare (strict_types = 1);

namespace app\common\service;

use think\facade\Session;
use think\facade\Cookie;
use think\facade\Request;
use think\facade\Db;
use app\common\model\AdminAdmin as M;
use app\common\validate\AdminAdmin as V;
use app\common\service\YpayUser as S;

class AdminAdmin
{
    // 添加
    public static function goAdd($data)
    {
        //验证
        $validate = new V;
        if(!$validate->scene('add')->check($data))
        return ['msg'=>$validate->getError(),'code'=>201];
        try {
            $password =  set_password($data['password']);
            M::create(array_merge($data, [
                'password' => $password,
            ]));
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
            $model = M::find($id);
            //是否需要修改密码
            if ($data['password']){
                $model->password = set_password($data['password']);
                $model->token = null;
            } 
            $model->username = $data['username'];
            $model->nickname = $data['nickname'];
            $model->save(); rm();
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
                'token' => null
             ]);
             rm();
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
            Db::name('admin_admin_role')->where('admin_id', $id)->delete();
            Db::name('admin_admin_permission')->where('admin_id', $id)->delete();
            rm();
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
            Db::name('admin_admin_role')->whereIn('admin_id', $ids)->delete();
            Db::name('admin_admin_permission')->whereIn('admin_id', $ids)->delete();
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
                Db::name('admin_admin_permission')->where('admin_id',$id)->delete();
                //填充新的直接权限
                foreach ($data as $v){
                    Db::name('admin_admin_permission')->insert([
                        'admin_id' => $id,
                        'permission_id' => $v,
                    ]);
                }
                Db::commit();
            }catch (DbException $exception){
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
        //按用户名
        $where = [];
        $limit = input('get.limit');
        if ($search = input('get.username')) {
            $where[] = ['username', 'like', "%" . $search . "%"];
        }
        $list = M::onlyTrashed()->order('id','desc')->withoutField('password,token')->where($where)->paginate($limit);
        return ['code'=>0,'data'=>$list->items(),'extend'=>['count' => $list->total(), 'limit' => $limit]];
    }

     // 修改密码
     public static function goPass()
     {
        $data = Request::post();
        $validate = new V;
        if(!$validate->scene('pass')->check($data)) 
        return ['msg'=>$validate->getError(),'code'=>201];
        M::where('id',Session::get('admin.id'))->update(['password' => set_password(trim($data['password']))]);
        self::logout();
     }
    

    // 用户登录验证
    public static function login(array $data)
    {   

        $validate = new V;
        if(!$validate->scene('login')->check($data)) 
        return ['msg'=>$validate->getError(),'code'=>201];

        //验证用户
        $admin = M::where([
            'username' => trim($data['username']),
            'password' => set_password(trim($data['password'])),
            'status' => 1
            ])->find();
        if(!$admin) return ['msg'=>'用户名密码错误','code'=>201];
        $admin->token = rand_string().$admin->id.microtime(true);
        $admin->save();
        //是否记住密码
        $time = 3600;
        if (isset($data['remember'])) $time = 30 * 86400;
        //缓存登录信息
        $info = [
            'id' => $admin->id,
            'token' => $admin->token,
            'menu' => M::permissions($admin->id,Request::root())
        ];
        Session::set('admin', $info);
        Cookie::set('token',$admin->token, $time);
        // 触发登录成功事件
        event('AdminLog');
        return ['msg'=>'登录成功'];
    }
    
    // 判断是否登录
    public static function isLogin()
    {
        if(Session::get('admin')) return true; 
        if(Cookie::has('token')){
            $admin = M::where(['token'=>Cookie::get('token'),'status'=>1])->find();
            if(!$admin) return false;
            return Session::set('admin',[
                'id' => $admin->id,
                'token' => $admin->token,
                'menu' => M::permissions($admin->id,Request::root())
            ]); 
        }
        return false;
    }
    
    // 退出登陆
    public static function logout()
    {
        Session::delete('admin');
        Cookie::delete('token');
        Cookie::delete('sign');
        return ['msg'=>'退出成功'];
    }
}
