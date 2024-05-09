<?php
declare (strict_types = 1);

namespace app\admin\controller;

use think\facade\Db;
use think\facade\Request;
use app\common\util\Crud as U;

class Crud extends Base
{
    protected $middleware = ['AdminCheck','AdminPermission'];
    
    // 系统配置
    public function index(){
        if (Request::isAjax()) {
            return $this->getJson(U::getTable());
        }
        return $this->fetch('',[
            'prefix' => config('database.connections.mysql.prefix')
        ]);
    }


    // 列表
    public function list($name){
        return $this->getJson(['code'=>0,'data'=>Db::getFields($name)]);
    }

    // 新增
    public function add(){
        if (Request::isAjax()) {
            return $this->getJson(U::goAdd());
        }
        return $this->fetch('',[
            'prefix' => config('database.connections.mysql.prefix')
        ]);
    }

    // 新增
    public function crud($name){
        if (Request::isAjax()) {
            return $this->getJson(U::goCrud($name));
        }
        return $this->fetch('',U::getCrud($name));
    }

    // 删除
    public function remove($name){
        return $this->getJson(U::goRemove($name));
    }
}
