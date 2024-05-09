<?php
declare (strict_types = 1);

namespace app\common\validate;

use think\Validate;
class AdminFrontLog extends Validate
{
    protected $rule = ['url' => 'require','ip' => 'require','user_agent' => 'require',
    ];

    protected $message = ['url.require' => '操作页面为必填项','ip.require' => '操作IP为必填项','user_agent.require' => 'User-Agent为必填项',
    ];

    /**
     * 添加
     */
    public function sceneAdd()
    {
        return $this->only(['url','ip','user_agent',]);
    }

    /**
     * 编辑
     */
    public function sceneEdit()
    {
        return $this->only(['url','ip','user_agent',]);
    }
}
