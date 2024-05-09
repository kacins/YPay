<?php
declare (strict_types = 1);

namespace app\common\model;

use think\facade\Request;
use think\facade\Session;
use think\Model;

class AdminAdminLog extends Model
{
    
    public function log()
    {
        return $this->belongsTo('AdminAdmin','uid','id');
    }

    // 管理员日志记录
    public static function record()
    {
        $desc = Request::except(['s','_pjax'])??'';
        if(isset($desc['page'])&&isset($desc['limit']))return;
        foreach ($desc as $k => $v) {
            if(stripos($k, 'fresh') !== false) return;
            if (is_string($v) && strlen($v) > 255 || stripos($k, 'password') !== false)  {
                unset($desc[$k]);
            }
        }
        $info = [
           'uid'       => Session::get('admin.id'),
           'url'      => Request::url(),
           'desc'    => json_encode($desc), 
           'ip'       => get_client_ip(),
           'user_agent'=> Request::server('HTTP_USER_AGENT')
        ];
        $res = self::where('uid',$info['uid'])
            ->order('id', 'desc')
            ->find();
        if (isset($res['url'])!==$info['url']) {
            self::create($info);
        }
    }
}
