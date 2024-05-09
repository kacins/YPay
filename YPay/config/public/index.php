<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2019 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]
namespace think;

require __DIR__ . '/../vendor/autoload.php';

//定义分隔符
define('DS', DIRECTORY_SEPARATOR);

// 执行HTTP应用并响应
$http = (new App())->http;

/**
 * 扩张安装帮助页面
 */
if (extension_loaded('swoole_loader')) {
    $php_v = substr(PHP_VERSION, 0, 3);

    if ($php_v >= '8.1') {
        
    } else {
        exit('<small>YPay需要php8.1及以上版本支持</small>');
    }

} else {
    exit("<script>window.location.href='/help/swoole-compiler-loader.php';</script>");
}

// 检测程序安装
if(!is_file(__DIR__ . '/install.lock')){
    header("location:./install.php");
    exit;
}



// 域名绑定应用使用统一入口
// $response = $http->run();

// 应用入口
$response = $http->name('index')->run();

$response->send();

$http->end($response);
