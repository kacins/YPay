<?php
declare (strict_types = 1);

namespace app\common\validate;

use think\Validate;

class AdminPermission extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
    protected $rule = [
	    'title|名称' => 'require',
        'type|类型' => 'require',
        'sort|排序' => 'require|between:1,99',
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [];
}