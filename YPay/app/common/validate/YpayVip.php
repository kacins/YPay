<?php
declare (strict_types = 1);

namespace app\common\validate;

use think\Validate;
class YpayVip extends Validate
{
    protected $rule = ['viptime' => 'require|number',
    ];

    protected $message = ['viptime.require' => '套餐时间为必填项','viptime.number' => '套餐时间需为数字',
    ];

    /**
     * 添加
     */
    public function sceneAdd()
    {
        return $this->only(['viptime',]);
    }

    /**
     * 编辑
     */
    public function sceneEdit()
    {
        return $this->only(['viptime',]);
    }
}
