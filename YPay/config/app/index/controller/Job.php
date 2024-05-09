<?php


namespace app\index\controller;
use think\facade\Config;
//use think\facade\Queue;
use think\facade\Db;
use app\index\job\Order;

class Job extends \app\BaseController
{

    /**
     * @定时任务
     *
     * @return void
     */
    public function test($code,$task_key){
        //参数
        $params = ['code'=>$code];
        if($task_key!=getConfig()['diy_task_key'])
        {
            echo '监控密钥错误';
            exit;
        }
        $JkOrder = new Order();
        //$isPushed = Queue::later(3, \app\index\job\Order::class, $params, "order");
        $isPushed = $JkOrder->fire($params);
        header("Content-type:application/json; charset=UTF-8");
        echo date('Y-m-d H:i:s') . "监控任务执行成功";
        exit;
    }

}