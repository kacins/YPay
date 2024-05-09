<?php
declare (strict_types = 1);

namespace app\common\middleware;

use think\facade\Session;

class AdminPermission
{
    use \app\common\traits\Base;

    /**
     * 处理请求
     *
     * @param \think\Request $request
     * @param \Closure       $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {
        //超级管理员不需要验证
        if (Session::get('admin.id') == 1) return $next($request);
        return $next($request);
    }
}
