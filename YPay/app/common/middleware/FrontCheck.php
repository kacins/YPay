<?php
declare (strict_types = 1);

namespace app\common\middleware;

use app\common\service\YpayUser as S;

class FrontCheck
{
    /**
     * 处理请求
     */
    public function handle($request, \Closure $next)
    {
        if(S::isLogin() == false){
            return redirect($request->root().'/User/Login');
        }
        //(new \app\common\model\AdminAdminLog)->record();
        return $next($request);
    }
}
