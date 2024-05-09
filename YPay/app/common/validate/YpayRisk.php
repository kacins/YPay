<?php
declare (strict_types = 1);

namespace app\common\validate;

use think\Validate;
class YpayRisk extends Validate
{
    protected $rule = ['user_id' => 'require|number',
    ];

    protected $message = ['user_id.require' => '会员ID为必填项','user_id.number' => '会员ID需为数字',
    ];

    /**
     * 添加
     */
    public function sceneAdd()
    {
        return $this->only(['user_id',]);
    }

    /**
     * 编辑
     */
    public function sceneEdit()
    {
        return $this->only(['user_id',]);
    }
}
