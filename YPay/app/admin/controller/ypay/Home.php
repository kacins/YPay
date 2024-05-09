<?php
declare (strict_types = 1);

namespace app\admin\controller\ypay;

use think\facade\Request;
use think\File;
use think\facade\Db;
use app\common\util\Upload as Up;
use think\facade\Filesystem\File as delete;

class Home extends  \app\admin\controller\Base
{
    protected $middleware = ['AdminCheck','AdminPermission'];

    // 模板列表
    public function index(){
        if (Request::isAjax()) {
         $data  = [
                "msg"=> "not data",
                "count"=> 30,
                "code" => 0,
            ];
                $basePath = app()->getRootPath();
        $folderPath = $basePath . '/public/home';
        $files = scandir($folderPath);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $fileInfo = file_get_contents($folderPath . '/' . $file . '/style.css');
                // 去除字符串中的注释符号和空格
                $string = str_replace(array("/*", "*/"), "", $fileInfo);
                $string = trim($string);
                
                // 使用正则表达式提取id和value
                $pattern = '/(\w+)\s*=\s*([^=]+)(?:\s+|$)/';
                preg_match_all($pattern, $string, $matches);
                
                // 将提取的id和value存储在关联数组中
                $array = array();
                foreach ($matches[1] as $index => $id) {
                    $value = $matches[2][$index];
                    $array[$id] = $value;
                }
                if(isset($array['ThemeName']) && isset($array['Description']) && isset($array['Version'])){
                    $data['data'][] = [
                       "id" => $file,
			           "image" => Request::domain() . '/home/' . $file . '/screenshot.png',
			           "title" => $array['ThemeName'],
			           "remark" => $array['Description'],
			           "version" => $array['Version'],
                    ];
                }
                
            }
        }
            return $this->getJson($data);
        }
        return $this->fetch();
    }
    
    //保存模板信息
    public function saveTheme(){
        if(Request::post()){
            $data = Request::post();
            try {
                Db::name('admin_config')->where('config_name','home_temp')->update(['config_value' => $data['id']]);
                return json(['msg'=>'设置主题成功','code'=>200]);
            } catch (\Exception $e) {
                return json(['msg'=>'操作失败'.$e->getMessage(),'code'=>201]);
            }
            
        }
    }
    
    //删除模板信息
    public function deleteTheme(){
        if(Request::post()){
            $data = Request::post();
            if($data['id'] == 'default' || $data['id'] == 'old'){
                 return json(['msg'=>'请勿删除默认模板','code'=>201]);
            }
            
            $home = Db::name('admin_config')->where('config_name','home_temp')->find();

            try {
                
                if($home['config_value'] == $data['id']){
                    Db::name('admin_config')->where('config_name','home_temp')->update(['config_value' => 'default']);
                }
                
                $directory = public_path() . '/home/' . $data['id'];  // 要删除的目录

                if (!file_exists($directory)) {
                    return json(['msg'=>'模板不存在','code'=>201]);;
                }

                $files = array_diff(scandir($directory), ['.', '..']);

                foreach ($files as $file) {
                    $path = $directory . '/' . $file;

                    if (is_dir($path)) {
                        delete_dir($path);
                    } else {
                        unlink($path);
                    }
                }

                rmdir($directory);

                return json(['msg'=>'主题删除成功','code'=>200]);
            } catch (\Exception $e) {
                return json(['msg'=>'操作失败'.$e->getMessage(),'code'=>201]);
            }
            
        }
    }
    
    //上传解压主题
    public function upload(){
        return json(Up::themePutFile(Request::file('file')));
    }
}
