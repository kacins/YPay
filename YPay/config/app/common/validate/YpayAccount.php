<?php
declare (strict_types = 1);

namespace app\common\validate;

use think\Validate;
class YpayAccount extends Validate
{
    protected $rule = ['user_id' => 'require|number','succcount' => 'require|number','succprice' => 'require|number','allmaxcount' => 'require|number','daymaxcount' => 'require|number',
    ];

    protected $message = ['user_id.require' => '会员ID为必填项','user_id.number' => '会员ID需为数字','succcount.require' => '收款笔数为必填项','succcount.number' => '收款笔数需为数字','succprice.require' => '收款金额为必填项','succprice.number' => '收款金额需为数字','allmaxcount.require' => '上限笔数为必填项','allmaxcount.number' => '上限笔数需为数字','daymaxcount.require' => '日上限笔数为必填项','daymaxcount.number' => '日上限笔数需为数字',
    ];

    /**
     * 添加
     */
    public function sceneAdd()
    {
        return $this->only(['user_id']);
    }

    /**
     * 编辑
     */
    public function sceneEdit()
    {
        return $this->only(['user_id','succcount','succprice','allmaxcount','daymaxcount',]);
    }
}
