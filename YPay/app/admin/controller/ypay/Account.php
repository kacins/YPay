<?php
declare (strict_types = 1);

namespace app\admin\controller\ypay;

use think\facade\Request;
use app\common\service\YpayAccount as S;
use app\common\model\YpayAccount as M;
use app\common\model\AdminChannel as channel;
use think\facade\View;

class Account extends  \app\admin\controller\Base
{
    protected $middleware = ['AdminCheck','AdminPermission'];

    // 列表
    public function index(){
        if (Request::isAjax()) {
            return $this->getJson(M::getList());
        }
        
        //获取通道数据
        $channel = channel::where(['status'=>1,'type'=>'alipay'])->select();
        View::assign('channel', $channel);
        return $this->fetch();
    }
    
    //获取通道列表
    public function getChannel(){
        $data = Request::param();
        $channel = channel::where(['status'=>1,'type'=>$data['type']])->select();
        return json(['code'=>1,'channel'=>$channel]);
    }
    
    // 编辑
    public function edit($id){
        if (Request::isAjax()) {
            return $this->getJson(S::goEdit(Request::post(),$id));
        }
        return $this->fetch('',['model' => M::getUserInfo($id)]);
    }

    // 状态
    public function status($id){
        return $this->getJson(S::goStatus(Request::post('status'),$id));
        }
    public function is_status($id){
        return $this->getJson(S::goIsStatus(Request::post('is_status'),$id));
        }

    // 删除
    public function remove($id){
        return $this->getJson(S::goRemove($id));
        }

    // 批量删除
    public function batchRemove(){
        return $this->getJson(S::goBatchRemove(Request::post('ids')));
        }
     // 根据类型删除离线通道
    public function remove_line(){
        if (Request::isAjax()) {
            return $this->getJson(S::goRemove_line(Request::post()));
        }
        
    }
}
