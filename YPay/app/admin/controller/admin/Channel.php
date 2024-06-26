<?php
declare (strict_types = 1);

namespace app\admin\controller\admin;

use think\facade\Request;
use app\common\service\AdminChannel as S;
use app\common\model\AdminChannel as M;

class Channel extends  \app\admin\controller\Base
{
    protected $middleware = ['AdminCheck','AdminPermission'];

    // 列表
    public function index(){
        if (Request::isAjax()) {
            return $this->getJson(M::getList());
        }
        return $this->fetch();
    }

    // 编辑
    public function edit($id){
        if (Request::isAjax()) {
            return $this->getJson(S::goEdit(Request::post(),$id));
        }
        return $this->fetch('',['model' => M::find($id)]);
    }

    // 状态
    public function status($id){
        return $this->getJson(S::goStatus(Request::post('status'),$id));
        }

    // 删除
    public function remove($id){
        return $this->getJson(S::goRemove($id));
        }

    // 排序
    public function sort($data,$sort_new,$sort_old){
        return $this->getJson(S::goSort($data,$sort_new,$sort_old));
    }

    // 批量删除
    public function batchRemove(){
        return $this->getJson(S::goBatchRemove(Request::post('ids')));
        }

    // 回收站
    public function recycle(){
        if (Request::isAjax()) {
            return $this->getJson(S::goRecycle());
        }
        return $this->fetch();
    }

}
