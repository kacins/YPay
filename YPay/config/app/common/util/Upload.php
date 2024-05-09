<?php
namespace app\common\util;
use think\facade\Filesystem;
use app\common\service\AdminPhoto;
use think\exception\ValidateException;
use think\facade\Config;
use ZipArchive;
use think\facade\Db;
use Zxing\QrReader as qrReader;

class Upload
{

    //通用上传
    public static function putFile($file, $path)
    {
        if (!$path) {
            $path = 'default';
        }

        try {
            validate(['file' => [
                'fileSize' => 410241024,
                'fileExt' => 'jpg,jpeg,png,bmp,gif',
                'fileMime' => 'image/jpeg,image/png,image/gif',
            ]])->check(['file' => $file]);
        } catch (\think\exception\ValidateException $e) {
            return ['msg' => '上传失败', 'code' => 201, 'data' => $e->getMessage()];
        }
        foreach ($file as $k) {
            if (getConfig()['file-type'] == 2) {
                //阿里云上传
                $res = Oss::alYunOSS($k, $k->extension(), $path);
                if ($res["code"] == 201) {
                    return ['msg' => '上传失败', 'code' => 201, 'data' => $res["msg"]];
                }

                $name = $res['src'];
                AdminPhoto::add($k, $name, $path, 2);
            } elseif (getConfig()['file-type'] == 3) {
                //七牛上传
                $res = Qiniu::QiniuOSS($k, $k->extension(), $path);
                if ($res["code"] == 201) {
                    return ['msg' => '上传失败', 'code' => 201, 'data' => $res["msg"]];
                }

                $name = $res['src'];
                AdminPhoto::add($k, $name, $path, 3);
            } else {
                $savename = '/' . 'upload' . '/' . \think\facade\Filesystem::disk('public')->putFile($path, $k);
                $name = str_replace("\\", "/", $savename);
                AdminPhoto::add($k, $name, $path, 1);
            }
        }
        return ['msg' => '上传成功', 'code' => 0, 'data' => ['src' => $name, 'thumb' => $name]];
    }
    
    //收款码上传
    public static function qrputFile($file, $path ,$code = '')
    {
        if (!$path) {
            $path = 'qrcode';
        }

        try {
            validate(['file' => [
                'fileSize' => 410241024,
                'fileExt' => 'jpg,jpeg,png,bmp,gif',
                'fileMime' => 'image/jpeg,image/png,image/gif',
            ]])->check(['file' => $file]);
        } catch (\think\exception\ValidateException $e) {
            return ['msg' => '上传失败', 'code' => 201, 'data' => $e->getMessage()];
        }
        
        if($code == 'wxpay_cloud' || $code == 'wxpay_cloudzs'){
            foreach ($file as $k) {
            if (getConfig()['file-type'] == 2) {
                //阿里云上传
                $res = Oss::alYunOSS($k, $k->extension(), $path);
                if ($res["code"] == 201) {
                    return ['msg' => '上传失败', 'code' => 201, 'data' => $res["msg"]];
                }

                $name = $res['src'];
                AdminPhoto::add($k, $name, $path, 2);
            } elseif (getConfig()['file-type'] == 3) {
                //七牛上传
                $res = Qiniu::QiniuOSS($k, $k->extension(), $path);
                if ($res["code"] == 201) {
                    return ['msg' => '上传失败', 'code' => 201, 'data' => $res["msg"]];
                }

                $name = $res['src'];
                AdminPhoto::add($k, $name, $path, 3);
            } else {
                $savename = '/' . 'upload' . '/' . \think\facade\Filesystem::disk('public')->putFile($path, $k);
                $name = str_replace("\\", "/", $savename);
                AdminPhoto::add($k, $name, $path, 1);
            }
        }
        return ['msg' => '上传成功', 'code' => 0, 'data' => ['src' => $name, 'thumb' => $name]];
        }else{
            foreach ($file as $k) {
            $savename = '/' . 'upload' . '/' . \think\facade\Filesystem::disk('public')->putFile($path, $k);
            $name = str_replace("\\", "/", $savename);
            // AdminPhoto::add($k, $name, $path, 1);
                $request = \think\facade\Request::instance();
                $erweima = $request->root(true).$name;//二维码的网络地址
                //1为API解码 2为本地解码
                if(getConfig()['qr_codeType'] == 1){
                    // 定义解码API接口数组
                    $api = array(
                        ['id'=>1,'api'=>'https://cli.im/Api/Browser/deqr?data='],
                        ['id'=>2,'api'=>'https://tenapi.cn/jxqr/?url='],
                        ['id'=>3,'api'=>'https://api.uomg.com/api/qr.encode?url='],
                        ['id'=>4,'api'=>'https://www.devtool.top/api/qrcode/decode/remote?url='],
                    );
                    
                     // 循环API接口数组分别解析获取数据
                    foreach ($api as $key => $value){
                        $ret = get_curl($value['api'].$erweima);
                        $ret = json_decode($ret,true);
                        if(!empty($ret['data']['RawData']) && $value['id'] == 1){
                            $qr_url = $ret['data']['RawData'];
                            break;
                        }elseif(!empty($ret['data']['qrtext']) && $value['id'] == 2){
                            $qr_url = $ret['data']['qrtext'];
                            break;
                        }elseif(!empty($ret['qrurl']) && $value['id'] == 3){
                            $qr_url = $ret['qrurl'];
                            break;
                        }elseif(!empty($ret['data']['text']) && $value['id'] == 4){
                            $qr_url = $ret['data']['text'];
                            break;
                        }else{
                            continue;
                        }
                    }
                }elseif(getConfig()['qr_codeType'] == 2){
                    $defaultOpts = [
                        'ssl' => [
                          'verify_peer' => false,
                          'verify_peer_name' => false,
                        ]
                    ];
                    stream_context_set_default($defaultOpts);
                    $qrReader = new qrReader($erweima);
                    $qr_url = $qrReader->text();
                }
                if(!empty($qr_url))
                {
                    if (file_exists(app()->getRootPath().'public'.$name)) unlink(app()->getRootPath().'public'.$name);//删除本地文件
                }
                else
                {
                    if (file_exists(app()->getRootPath().'public'.$name)) unlink(app()->getRootPath().'public'.$name);//删除本地文件
                    return ['code'=>201,'msg'=>'二维码解码失败,请手动解码输入'];
                }
            }
            return ['msg' => '解析成功', 'code' => 0, 'data' => ['src' => $qr_url, 'thumb' => $qr_url]];
        }
        
        
    }
    
    //主题上传解压
    public static function themePutFile($file)
    {

        try {
            validate(['file' => [
                'fileExt' => 'zip',
            ]])->check(['file' => $file]);
        } catch (\think\exception\ValidateException $e) {
            return ['msg' => '上传失败', 'code' => 201, 'data' => $e->getMessage()];
        }
        // 上传目录
        $uploadPath = public_path() . '/home/';
    
        // 将文件移动到上传目录
        $info = $file->move($uploadPath, $file->getOriginalName());
    
        if ($info) {
            // 获取上传后的文件路径和文件名
            $filePath = $info->getPathName();
            $fileName = $info->getFileName();
    
            // 判断文件类型
            $extension = $info->getExtension();
            if ($extension == 'zip') {
                // 判断是否是一个文件夹
                if (is_dir($filePath)) {
                    // 解压文件夹
                    $zip = new \ZipArchive();
                    if ($zip->open($filePath) === true) {
                        $zip->extractTo($uploadPath);
                        $zip->close();
                         // 删除上传的压缩包文件
                        unlink($filePath);
                        return ['code' => 200 ,'msg' => '上传成功'];
                    } else {
                        return ['code' => 201 ,'msg' => '无法打开压缩文件'];
                    }
                } else {
                    // 创建以压缩包名字命名的文件夹并解压
                    $folderName = pathinfo($fileName, PATHINFO_FILENAME);
                    $folderPath = $uploadPath . $folderName;
                    if (!is_dir($folderPath)) {
                        mkdir($folderPath, 0755, true);
                    }
                    $zip = new \ZipArchive();
                    if ($zip->open($filePath) === true) {
                        $zip->extractTo($folderPath);
                        $zip->close();
                       // 删除上传的压缩包文件
                        unlink($filePath);
                        return ['code' => 200 ,'msg' => '上传成功'];
                    } else {
                        return ['code' => 201 ,'msg' => '无法打开压缩文件'];
                    }
                }
            }  else {
                // 删除上传的文件
                unlink($filePath);
                return ['code' => 201 ,'msg' => '不支持的文件类型'];
            }
        } else {
            return ['code' => 201 ,'msg' => '文件上传失败'];
        }
    }
    
    //更新包上传解压
    public static function updatePutFile($file)
    {

        try {
            validate(['file' => [
                'fileExt' => 'zip',
            ]])->check(['file' => $file]);
        } catch (\think\exception\ValidateException $e) {
            return ['msg' => '上传失败', 'code' => 201, 'data' => $e->getMessage()];
        }
        // 上传目录
        $uploadPath = app()->getRootPath() . "runtime/upgrade/";
    
        // 将文件移动到上传目录
        $info = $file->move($uploadPath, $file->getOriginalName());
       
        if ($info) {
            // 获取上传后的文件路径和文件名
            $fileName = $info->getFileName();
            $filePath = $uploadPath .$fileName;
 
                    // 解压文件夹
                    $zip = new \ZipArchive();
                    //打开压缩包
                if ($zip->open($filePath) === true) {
     
                    $toPath = app()->getRootPath();
                    try {
                    //解压文件到toPath路径下，用于覆盖差异文件
                    $zip->extractTo($toPath);
     
                    //判断文件是否存在
                    if (file_exists($toPath . '/upDelete.txt')) {
                        //读取删除文件文本
                        $fp = fopen($toPath . '/upDelete.txt', 'r');
                        $upDelete = fread($fp, filesize($toPath . '/upDelete.txt'));
                        @eval("\$array = $upDelete;");//将文本内容转为数组格式
                        foreach ($array as $value) {
                            $dir = $toPath . $value;
                            //判断是目录还是文件
                            if (is_dir($dir)) {
                                //调用公共删除文件方法
                                delete_dir($dir);
                            } else {
                                if (file_exists($dir)) {
                                    unlink($dir); //删除不需要的文件
                                }
                            }

                        }
                        fclose($fp);
                        unlink($toPath . '/upDelete.txt'); //删除-删除文件文本
                    }
                    unlink($filePath); //删除更新包

                    } catch (Exception $e) {
                    return $this->getJson(["msg" => $e->getMessage(), "code" => 201]);
                }
                    //文件差异覆盖完成，开始更新数据库
                    //执行数据库
                    $dbpk = '';
                    $dbhost = Config::get('database.connections.mysql.hostname');
                    $dbport = Config::get('database.connections.mysql.hostport');
                    $dbname = Config::get('database.connections.mysql.database');
                    $dsn = "mysql:host=$dbhost:$dbport;dbname=$dbname";
                    $db = new \PDO($dsn, Config::get('database.connections.mysql.username'), Config::get('database.connections.mysql.password'));
    
                    $list = scandir($_SERVER['DOCUMENT_ROOT'] . '/../app/update');
                    // 文件头两个是 . 和 .. 要去掉
                    unset($list[0]);
                    unset($list[1]);
                    
                  
                    
                    // 获取当前数据库版本号
                    $db_version = Db::name('admin_config')->where(['config_name' => 'db_version'])->find();
                    $last = '';
                    foreach ($list as $item) {
                        $tmp = str_replace('.sql', '', $item);
                        
                        if ((int)$tmp > (int)$db_version['config_value']) {
                            self::createTables($db, $dbpk, $_SERVER['DOCUMENT_ROOT'] . '/../app/update/' . $tmp . '.sql');
                        }
    
                        $last = $tmp;
                    }
                    // 将最后一次更新的版本号记录到数据库
                    if (!$db_version) {
                        Db::name('admin_config')->insert([
                            'config_name' => 'db_version',
                            'config_value' => $last,
                        ]);
                    } else {
                        Db::name('admin_config')->where(['config_name' => 'db_version'])->save([
                            'config_name' => 'db_version',
                            'config_value' => $last,
                        ]);
                    }
     
                    return ['code' => 200, 'msg' => '版本更新成功请刷新缓存!'];
                } else {
                    unlink($filePath); //删除更新包
                    return ["msg" => "更新包解压失败，请重试！", "code" => 201];
                }
            }  else {
                // 删除上传的文件
                unlink($filePath);
                return ['code' => 201 ,'msg' => '不支持的文件类型'];
            }
    }
    
    public static function createTables($db, $pk, $sql_file = '')
    {
        $sql = str_replace(
            ['{{$pk}}'],
            [$pk],
            file_get_contents($sql_file)
        );
        $sql_array = preg_split("/;[\r\n]+/", $sql);
        foreach ($sql_array as $k => $v) {
            if (!empty($v)) {
                try {
                    if (substr($v, 0, 12) == 'CREATE TABLE') {
                        $name = preg_replace("/^CREATE TABLE `(\w+)` .*/s", "\\1", $v);
                        $msg = "创建数据表{$name}";
                        $res = $db->query($v);
                        if ($res == false) {
                            return $msg . '失败';
                        }
                    } else {
                        $res = $db->query($v);
                        if ($res == false) {
                            return '数据插入失败';
                        }
                    }
                } catch (Exception $exception) {

                }
            }
        }
        return false;
    }
}
