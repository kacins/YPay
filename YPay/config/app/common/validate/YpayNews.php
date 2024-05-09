<?php
declare (strict_types = 1);

namespace app\common\validate;

use think\Validate;
class YpayNews extends Validate
{
    protected $rule = ['type' => 'require|number',
    ];

    protected $message = ['type.require' => '公告类型为必填项','type.number' => '公告类型需为数字',
    ];

    /**
     * 添加
     */
    public function sceneAdd()
    {
        return $this->only(['type',]);
    }

    /**
     * 编辑
     */
    public function sceneEdit()
    {
        return $this->only(['type',]);
    }
}
