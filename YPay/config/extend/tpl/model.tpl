<?php
declare (strict_types = 1);

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;
class {{$table_hump}} extends Model
{
    use SoftDelete;
    {{$model_del}}
    // 获取列表
    public static function getList()
    {
        $where = [];
        $limit = input('get.limit');
        {{$model_search}}
        $list = self::order('id','desc')->where($where)->paginate($limit);
        return ['code'=>0,'data'=>$list->items(),'extend'=>['count' => $list->total(), 'limit' => $limit]];
    }
}
