<?php
declare (strict_types = 1);

namespace app\admin\controller\ypay;

use think\facade\Request;
use app\common\service\YpayOrder as S;
use app\common\model\YpayOrder as M;

class Order extends  \app\admin\controller\Base
{
    protected $middleware = ['AdminCheck','AdminPermission'];

    // 列表
    public function index(){
        if (Request::isAjax()) {
            return $this->getJson(M::getList());
        }
        return $this->fetch();
    }

    // 添加
    public function add(){
        if (Request::isAjax()) {
            return $this->getJson(S::goAdd(Request::post()));
        }
        return $this->fetch();
    }

    // 查看订单信息
    public function edit($id){
        $orderInfo = M::find($id);
        
        //获取支付类型
        $type = $this->getPayType($orderInfo['type']);
        
        $temp = 
        [
            ['name' => '商品名称','value' => $orderInfo['name']],
            ['name' => '网站名称','value' => $orderInfo['sitename']],
            ['name' => '支付方式','value' => $type],
            ['name' => '本地单号','value' => $orderInfo['out_trade_no']],
            ['name' => '商户单号','value' => $orderInfo['trade_no']],
            ['name' => '下单IP信息','value' => $orderInfo['ip']],
            ['name' => '异步通知地址','value' => $orderInfo['notify_url']],
            ['name' => '同步通知地址','value' => $orderInfo['return_url']],
            ['name' => '用户ID','value' => $orderInfo['user_id']],
            ['name' => '实付金额','value' => $orderInfo['truemoney']],
            ['name' => '手续费金额','value' => $orderInfo['feilvmoney']],
            ['name' => '下单时间','value' => $orderInfo['create_time']],
            ['name' => '支付时间','value' => $orderInfo['end_time']],
        ];
        return $this->fetch('',['temp' => $temp]);
    }


    // 删除
    public function remove($id){
        return $this->getJson(S::goRemove($id));
        }
    
    //回调处理
    public function reback($id){
        return $this->getJson(S::goReback($id));
    }
        

    // 批量删除
    public function batchRemove(){
        return $this->getJson(S::goBatchRemove(Request::post('ids')));
        }
    
    // 根据天数清理订单
    public function daysRemove($day){
        return $this->getJson(S::goDaysRemove($day));
    } 
      
    // 一键清理未支付订单
    public function allRemove(){
        return $this->getJson(S::goAllRemove());
    }


}
