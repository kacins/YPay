<?php
declare (strict_types = 1);

namespace app\common\validate;

use think\Validate;
class YpayPlug extends Validate
{
    protected $rule = [
    ];

    protected $message = [
    ];

    /**
     * 添加
     */
    public function sceneAdd()
    {
        return $this->only([]);
    }

    /**
     * 编辑
     */
    public function sceneEdit()
    {
        return $this->only([]);
    }
}
