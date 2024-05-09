<?php
declare (strict_types = 1);

namespace app\common\validate;

use think\Validate;
class YpayRegorder extends Validate
{
    protected $rule = ['type' => 'require','out_trade_no' => 'require','status' => 'require|number','create_time' => 'require','end_time' => 'require','regdata' => 'require',
    ];

    protected $message = ['type.require' => '为必填项','out_trade_no.require' => '为必填项','status.require' => '为必填项','status.number' => '需为数字','create_time.require' => '为必填项','end_time.require' => '为必填项','regdata.require' => '为必填项',
    ];

    /**
     * 添加
     */
    public function sceneAdd()
    {
        return $this->only(['type','out_trade_no','status','create_time','end_time','regdata',]);
    }

    /**
     * 编辑
     */
    public function sceneEdit()
    {
        return $this->only(['type','out_trade_no','status','create_time','end_time','regdata',]);
    }
}
