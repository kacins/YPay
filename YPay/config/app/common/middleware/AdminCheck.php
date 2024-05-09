<?php
declare (strict_types = 1);

namespace app\common\middleware;

use app\common\service\AdminAdmin as S;

class AdminCheck
{
    /**
     * 处理请求
     */
    public function handle($request, \Closure $next)
    {
        if(S::isLogin() == false){
            return redirect($request->root().'/login/index');
        }
        (new \app\common\model\AdminAdminLog)->record();
        return $next($request);
    }
}
