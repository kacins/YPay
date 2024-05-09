<?php
declare (strict_types=1);

namespace app\install\controller;

use think\facade\Cache;
use think\facade\Session;
use app\BaseController;

const SUCCESS = 'layui-icon-ok-circle';
const ERROR = 'layui-icon-close-fill';

class Index extends BaseController
{
    /**
     * 使用协议
     *
     * @return \support\Response
     */
    public function index()
    {
        Cache::clear();
        return view('/index/index');
    }

    /**
     * 检测安装环境
     *
     */
    public function step1()
    {

        if (request()->isPost()) {

            // 检测生产环境
            foreach ($this->checkEnv() as $key => $value) {
                if ($key == 'php' && (float)$value < 8.0) {
                    return $this->error('PHP版本过低！');
                }
                if ($key == 'fileinfo' && !$value) {
                    return $this->error('请先安装fileinfo扩展');
                }
            }

            // 检测目录权限
            foreach ($this->checkDirFile() as $value) {
                if ($value[1] == ERROR
                    || $value[2] == ERROR) {
                    return $this->error($value[3] . ' 权限读写错误！');
                }
            }

            Cache::set('checkEnv', 'success');
            Session::set('checkEnv', 'success');
            return json(['code' => 200, 'url' => '/install.php/index/step2']);
        }
        
        $dirlist = $this->checkDirFile();
        return view('/index/step1', [
            'checkEnv' => $this->checkEnv(),
            'checkfile' => $dirlist,
        ]);
    }


    /**
     * 检测环境变量
     */
    protected function checkEnv(): array
    {
        $items['php'] = PHP_VERSION;
        $items['mysqli'] = extension_loaded('mysqli');
        $items['curl'] = extension_loaded('curl');
        $items['fileinfo'] = extension_loaded('fileinfo');
        return $items;
    }

    /**
     * 检测读写环境
     */
    protected function checkDirFile(): array
    {
        $items = array(
            array('dir', SUCCESS, SUCCESS, './'),
            array('dir', SUCCESS, SUCCESS, './public'),
            array('dir', SUCCESS, SUCCESS, './public/upload'),
            array('dir', SUCCESS, SUCCESS, './runtime'),
            array('dir', SUCCESS, SUCCESS, './extend'),
        );

        foreach ($items as &$value) {

            $item = root_path() . $value[3];

            // 写入权限
            if (!is_writable($item)) {
                $value[1] = ERROR;
            }

            // 读取权限
            if (!is_readable($item)) {
                $value[2] = ERROR;
            }
        }

        return $items;
    }


    /**
     * 检查环境变量
     */
    public function step2()
    {
        if (!Cache::get('checkEnv') && !Session::get('checkEnv')) {
            return redirect('/install.php/index/step1');
        }

        if (request()->isPost()) {

            // 链接数据库
            $params = request()->all();
            $connect = @mysqli_connect($params['host'] . ':' . $params['port'], $params['user'], $params['pass']);
            if (!$connect) {
                return $this->error('数据库链接失败');
            }
            // 检测MySQL版本
            $mysqlInfo = @mysqli_get_server_info($connect);

            // 查询数据库名
            $database = false;
            $mysql_table = @mysqli_query($connect, 'SHOW DATABASES');
            while ($row = @mysqli_fetch_assoc($mysql_table)) {
                if ($row['Database'] == $params['name']) {
                    $database = true;
                    break;
                }
            }

            if (!$database) {
                $query = "CREATE DATABASE IF NOT EXISTS `" . $params['name'] . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;";
                if (!@mysqli_query($connect, $query)) {
                    return $this->error('数据库创建失败或已存在，请手动修改');
                }
            }

            Cache::set('mysqlInfo', $params);
            return json(['code' => 200, 'url' => '/install.php/index/step3']);
        }

        return view('/index/step2');
    }

    /**
     * 初始化数据库
     */
    public function step3()
    {
        $mysqlInfo = Cache::get('mysqlInfo');
        if (!$mysqlInfo) {
            return redirect('/install.php/index/step2');
        }

        return view('/index/step3');
    }

    /**
     * 安装数据缓存
     */
    public function install()
    {
        if (request()->isAjax()) {

            $mysqlInfo = Cache::get('mysqlInfo');
            // 读取SQL文件加载进缓存
            $mysqlPath = app_path().'data'. DS .'data.sql';
            $sqlRecords = file_get_contents($mysqlPath);
            $sqlRecords = preg_split("/;[\r\n]+/", $sqlRecords);
       
            
            // 创建数据表
            $sqlConnect = @mysqli_connect($mysqlInfo['host'] . ':' . $mysqlInfo['port'], $mysqlInfo['user'], $mysqlInfo['pass']);
            mysqli_select_db($sqlConnect, $mysqlInfo['name']);
            mysqli_query($sqlConnect, "set names utf8mb4");

            foreach ($sqlRecords as $index => $sqlLine) {
                $sqlLine = trim($sqlLine);
                if (!empty($sqlLine)) {
                    try {
                        // 创建表数据
                        if (mysqli_query($sqlConnect, $sqlLine) === false) {
                            throw new \Exception(mysqli_error($sqlConnect));
                        }
                    } catch (\Throwable $th) {
                        return $this->error($th->getMessage());
                    }
                }
            }

            // 获取数据库信息
            $dbhost = trim($mysqlInfo['host']);
            $dbuser = trim($mysqlInfo['user']);
            $dbpass = trim($mysqlInfo['pass']);
            $dbport = trim($mysqlInfo['port']);
            $dbname = trim($mysqlInfo['name']);
            
            // 创建管理员信息
            $username = trim($mysqlInfo['username']);
            $nickname = trim($mysqlInfo['nickname']);
            $password = set_password($mysqlInfo['password']);
            $sql = "insert into admin_admin  (id,username,nickname,password) values('1','$username','$nickname','$password');";
            mysqli_query($sqlConnect,$sql);
            // 将数据库信息存入配置文件
            $content = str_replace(['{{$dbhost}}','{{$dbname}}','{{$dbuser}}','{{$dbpass}}','{{$dbport}}','{{$dbpk}}'], 
            [$dbhost,$dbname,$dbuser,$dbpass,$dbport], 
            file_get_contents(app_path().'data'. DS .'database.tpl'));
            @mkdir(root_path()."config/database.php", 0755, true);
            @file_put_contents(root_path()."config/database.php",$content);
            // 创建安装锁
            @touch(public_path().'install.lock');
            return json(['code' => 200]);
        }
    }
}