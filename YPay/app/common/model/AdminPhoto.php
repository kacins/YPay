<?php
declare (strict_types = 1);

namespace app\common\model;

use think\Model;

class AdminPhoto extends Model
{
   // 获取所有路径
   public static function getPath()
   {
        $path = public_path().'upload'.DS;
        foreach (scandir($path) as $k) {
            if(is_dir($path.$k) && $k!="." &&$k!=".."){
                $data[] = ['name'=>$k];
            }
        }
        $data[] = ['name'=>'阿里云'] ;
        return ['code'=>0,'data'=>$data];
   }

    // 获取列表
    public static function getAll()
    {
        $where = [];
        $limit = input('get.limit');
        if ($search = input('get.path')) {
            $where[] =  ['path', '=',$search];
        }
        $list = self::order('id','desc')->where($where)->paginate($limit);
        return ['code'=>0,'data'=>$list->items(),'extend'=>['count' => $list->total(), 'limit' => $limit]];
    }

    // 获取列表
    public static function getList($name)
    {
        $limit = input('get.limit');
        $list = self::order('id','desc')->where('path',$name)->paginate($limit);
        return ['code'=>0,'data'=>$list->items(),'extend'=>['count' => $list->total(), 'limit' => $limit]];
    }

    public function getTypeAttr($value)
    {
        $type = ['1' => '本地', '2' => '阿里云','3'=>'七牛云'];
        return $type[$value];
    }
}