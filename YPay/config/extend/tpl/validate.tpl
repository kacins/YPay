<?php
declare (strict_types = 1);

namespace app\common\validate;

use think\Validate;
class {{$table_hump}} extends Validate
{
    protected $rule = [{{$validate_rule}}
    ];

    protected $message = [{{$validate_msg}}
    ];

    /**
     * 添加
     */
    public function sceneAdd()
    {
        return $this->only([{{$validate_scene}}]);
    }

    /**
     * 编辑
     */
    public function sceneEdit()
    {
        return $this->only([{{$validate_scene}}]);
    }
}
