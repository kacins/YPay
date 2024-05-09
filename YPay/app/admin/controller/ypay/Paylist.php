<?php
declare (strict_types = 1);

namespace app\admin\controller\ypay;

use think\facade\Request;
use app\common\model\YpayPaylist as M;
use app\common\service\YpayPaylist as S;
use app\common\core\core;
use think\facade\View;

class Paylist extends  \app\admin\controller\Base
{
    protected $middleware = ['AdminCheck','AdminPermission'];

    // 列表
    public function index(){
        if (Request::isAjax()) {
            return $this->getJson(M::getList());
        }
        return $this->fetch();
    }
    
    // 新增通道
    public function add(){
        if (Request::isAjax()) {
            return $this->getJson(S::goAdd(Request::post()));
        }
                
        //创建Core对象
        $core  = new Core();
        
        //获取商城总览广告位
        $EPayAd = $core->getEPayAd();
        
        View::assign(['epayAd'=>$EPayAd]);
        
        return $this->fetch();
    }
    
    // 编辑通道信息
    public function edit($id){
        if (Request::isAjax()) {
            return $this->getJson(S::goEdit(Request::post(),$id));
        }
        $model  = M::find($id);
                
        //创建Core对象
        $core  = new Core();
        
        //获取商城总览广告位
        $EPayAd = $core->getEPayAd();
        
        View::assign(['epayAd'=>$EPayAd]);
        
        return $this->fetch('',['model' => $model]);
    }
    // 删除
    public function remove($id){
        return $this->getJson(S::goRemove($id));
    }
    
    // 批量删除
    public function batchRemove(){
        return $this->getJson(S::goBatchRemove(Request::post('ids')));
        }
    // 更改状态
    public function status($id){
        return $this->getJson(S::goStatus(Request::post('status'),$id));
    }
    
}
