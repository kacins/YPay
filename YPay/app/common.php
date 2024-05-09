<?php
// 应用公共文件

// 应用公共文件
if (!function_exists('opt_photo'))
{
    //图库选择
    function opt_photo($val)
    {
       return '<button class="pear-btn pear-btn-primary pear-btn-sm" style="margin:4px 5px;vertical-align:top;" id="'.$val.'" type="button">图库选择</button>
       <script>
       layui.use(["jquery"],function() {
        let $ = layui.jquery;
        //弹出窗设置 自己设置弹出百分比
        function screen() {
            if (typeof width !== "number" || width === 0) {
            width = $(window).width() * 0.8;
            }
            if (typeof height !== "number" || height === 0) {
            height = $(window).height() - 20;
            }
            return [width + "px", height + "px"];
        }
        $("#'.$val.'").on("click", function () {
            layer.open({
                type: 2,
                maxmin: true,
                title: "图库选择",
                shade: 0.1,
                area: screen(),
                content:"../index/optPhoto",
                success:function (layero,index) {
                    var iframe = window["layui-layer-iframe" + index];
                    iframe.child("'.$val.'")
                }
            });
        });
        })
        </script>';
    }
}



if (!function_exists('getConfig')) {
    function getConfig()
    {
        $config = function_exists('mysql_get_server_info') ? mysql_get_server_info() : \think\facade\Db::table('admin_config')->select()->toArray();
        foreach ($config as $key => $value) {
            $data[$value['config_name']] = $value['config_value'];
        }
        return $data;
    }
}

//获取支付类型
if (!function_exists('getPayType')) {
    function getPayType($type = '')
    {

        //不为空执行筛选
        if (!empty($type)) {
            switch ($type) {
                case 'alipay':
                    $type = '支付宝';
                    break;
                case 'wxpay':
                    $type = '微信';
                    break;
                case 'epay_wechat':
                    $type = '易支付-微信';
                    break;
                case 'epay_ali':
                    $type = '易支付-支付宝';
                    break;
                default:
                    $type = 'QQ';
                    break;
            }
        }

        return $type;
    }
}
if (!function_exists('rm')) {
    //清除缓存
    function rm()
    {
        delete_dir(root_path() . 'runtime');
    }
}

if (!function_exists('is_url')) {
    //是否
    function is_url($url)
    {
        if (preg_match("/^http(s)?:\\/\\/.+/", $url)) return $url;
    }
}

if (!function_exists('rand_string')) {
    /**
     *  随机数
     *
     * @param string $length 长度
     * @param string $type 类型
     * @return void
     */
    function rand_string($length = '32', $type = 4): string
    {
        $rand = '';
        switch ($type) {
            case '1':
                $randstr = '0123456789';
                break;
            case '2':
                $randstr = 'abcdefghijklmnopqrstuvwxyz';
                break;
            case '3':
                $randstr = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            default:
                $randstr = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
        }
        $max = strlen($randstr) - 1;
        mt_srand((double)microtime() * 1000000);
        for ($i = 0; $i < $length; $i++) {
            $rand .= $randstr[mt_rand(0, $max)];
        }
        return $rand;
    }
}

if (!function_exists('set_password')) {
    //密码截取
    function set_password($password): string
    {
        return substr(md5($password), 3, -3);
    }
}
/**
 * 数据签名认证
 */
function data_sign($data = [])
{
    if (!is_array($data)) {
        $data = (array)$data;
    }
    ksort($data);
    $code = http_build_query($data);
    $sign = sha1($code);
    return $sign;
}



if (!function_exists('get_arr_tree')) {
    /**
     * 递归配置数组
     */
    function get_arr_tree($key, $data, $level = "\t")
    {
        $i = "$level'$key' => [\r\n";
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $i .= get_arr_tree($k, $v, $level . "\t");
            } else {
                $i .= "$level\t'$k' => '$v',";
                $i .= "\r\n";
            }
        }
        return $i . "$level" . '],' . "\r\n";
    }
}

if (!function_exists('aes_encrypt')) {
    /**
     *
     * @param string $string 需要加密的字符串
     * @param string $key 密钥
     * @return string
     */
    function aes_encrypt($string, $key = "ONSPEED"): string
    {
        $data = openssl_encrypt($string, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
        return strtolower(bin2hex($data));
    }
}

if (!function_exists('aes_decrypt')) {
    /**
     * @param string $string 需要解密的字符串
     * @param string $key 密钥
     * @return string
     */
    function aes_decrypt($string, $key = "ONSPEED"): string
    {
        try {
            return openssl_decrypt(hex2bin($string), 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
        } catch (\Exception $e) {
            return false;
        }
    }
}

if (!function_exists('get_field')) {
    /**
     * 获取指定表指定行指定字段
     * @param string $tn 完整表名
     * @param string|array $where 参数数组或者id值
     * @param string $field 字段名,默认'name'
     * @param string $default 获取失败的默认值,默认''
     * @param array $order 排序数组
     * @return string                获取到的内容
     */
    function get_field($tn, $where, $field = 'name', $default = '', $order = ['id' => 'desc'])
    {
        if (!is_array($where)) {
            $where = ['id' => $where];
        }
        $row = \think\facade\Db::name($tn)->field([$field])->where($where)->order($order)->find();
        return $row === null ? $default : $row[$field];
    }
}

if (!function_exists('delete_dir')) {
    /**
     * 遍历删除文件夹所有内容
     * @param string $dir 要删除的文件夹
     */
    function delete_dir($dir)
    {
        $dh = opendir($dir);
        while ($file = readdir($dh)) {
            if ($file != '.' && $file != '..') {
                $filepath = $dir . '/' . $file;
                if (is_dir($filepath)) {
                    delete_dir($filepath);
                } else {
                    @unlink($filepath);
                }
            }
        }
        closedir($dh);
        @rmdir($dir);
    }
}

if (!function_exists('get_tree')) {
    /**
     * 递归无限级分类权限
     * @param array $data
     * @param int $pid
     * @param string $field1 父级字段
     * @param string $field2 子级关联的父级字段
     * @param string $field3 子级键值
     * @return mixed
     */
    function get_tree($data, $pid = 0, $field1 = 'id', $field2 = 'pid', $field3 = 'children')
    {
        $arr = [];
        foreach ($data as $k => $v) {
            if ($v[$field2] == $pid) {
                $v[$field3] = get_tree($data, $v[$field1]);
                $arr[] = $v;
            }
        }
        return $arr;
    }
}

if (!function_exists('hump_underline')) {
    /**
     * 驼峰转下划线
     * @param string $str 需要转换的字符串
     * @return string      转换完毕的字符串
     */
    function hump_underline($str)
    {
        return strtolower(trim(preg_replace('/[A-Z]/', '_\\0', $str), '_'));
    }
}

if (!function_exists('underline_hump')) {
    /**
     * 下划线转驼峰
     * @param string $str 需要转换的字符串
     * @return string      转换完毕的字符串
     */
    function underline_hump($str)
    {
        return ucfirst(
            preg_replace_callback('/_([a-zA-Z])/', function ($match) {
                return strtoupper($match[1]);
            }, $str)
        );
    }
}

if (!function_exists('record_log')) {
    /**
     * @记录日志
     * @param [type] $param
     * @param string $file
     *
     * @return void
     */
    function record_log($param, $file = '')
    {
        $path = root_path() . '/runtime/log/' . $file . "/";
        if (!is_dir($path)) @mkdir($path, 0777, true);
        if (is_array($param)) {
            $param = json_encode($param, JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE);
        }
        @file_put_contents(
            $path . date("Y_m_d", time()) . ".txt",
            "执行日期：" . "\r\n" . date('Y-m-d H:i:s', time()) . ' ' . "\n" . $param . "\r\n",
            FILE_APPEND
        );
    }

}
/**
 * 支付宝方法
 */
function getqrtoken($qrsig)
{
    $len = strlen($qrsig);
    $hash = 0;
    for ($i = 0; $i < $len; $i++) {
        $hash += (($hash << 5) & 2147483647) + ord($qrsig[$i]) & 2147483647;
        $hash &= 2147483647;
    }
    return $hash & 2147483647;
}

function getEncryptPassword($password, $salt = '')
{
    return md5(md5($password) . $salt);
}

function get_curl2($url, $post = 0, $referer = 0, $cookie = 0, $httpheaders = 0, $header = 0, $ua = 0, $nobaody = 0, $split = 0)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $httpheader[] = "Accept: application/json";
    $httpheader[] = "Accept-Encoding: gzip,deflate,sdch";
    $httpheader[] = "Accept-Language: zh-CN,zh;q=0.8";
    $httpheader[] = "Connection: close";
    if ($httpheaders) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheaders);
    } else {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
    }
    if ($post) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    }
    if ($header) {
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
    }
    if ($cookie) {
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    }
    if ($referer) {
        curl_setopt($ch, CURLOPT_REFERER, $referer);
    }
    if ($ua) {
        curl_setopt($ch, CURLOPT_USERAGENT, $ua);
    } else {
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.100 Safari/537.36');
    }
    if ($nobaody) {
        curl_setopt($ch, CURLOPT_NOBODY, 1);

    }
    $ip_long = array(
        array('607649792', '608174079'),
        array('1038614528', '1039007743'),
        array('1783627776', '1784676351'),
        array('2035023872', '2035154943'),
        array('2078801920', '2079064063'),
        array('-1950089216', '-1948778497'),
        array('-1425539072', '-1425014785'),
        array('-1236271104', '-1235419137'),
        array('-770113536', '-768606209'),
        array('-569376768', '-564133889'),
    );
    $rand_key = mt_rand(0, 9);
    $ip = long2ip(mt_rand($ip_long[$rand_key][0], $ip_long[$rand_key][1]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:' . $ip, 'CLIENT-IP:' . $ip));
    curl_setopt($ch, CURLOPT_ENCODING, "gzip");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $ret = curl_exec($ch);
    if ($split) {
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($ret, 0, $headerSize);
        $body = substr($ret, $headerSize);
        $ret = array();
        $ret['header'] = $header;
        $ret['body'] = $body;
    }
    curl_close($ch);
    return $ret;
}

function param($id, $param)
{
    $json = file_get_contents("./ck/" . $id . ".txt");
    $json = json_decode($json, true);
    return base64_decode($json[$param]);
}

function trimall($str)
{
    $qian = array(" ", "　", "\t", "\n", "\r");
    return str_replace($qian, '', $str);
}

function get_curl($url, $post = 0, $referer = 0, $cookie = 0, $header = 0, $ua = 0, $nobaody = 0, $split = 0)
{

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $httpheader[] = "Accept:*/*";
    $httpheader[] = "Accept-Encoding:gzip,deflate,sdch";
    $httpheader[] = "Accept-Language:zh-CN,zh;q=0.8";
    $httpheader[] = "Connection:close";
    curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
    if ($post) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    }
    if ($header) {
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
    }
    if ($cookie) {
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    }
    if ($referer) {
        curl_setopt($ch, CURLOPT_REFERER, $referer);
    }
    if ($ua) {
        curl_setopt($ch, CURLOPT_USERAGENT, $ua);
    } else {
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.152 Safari/537.36');
    }
    if ($nobaody) {
        curl_setopt($ch, CURLOPT_NOBODY, 1);

    }
    curl_setopt($ch, CURLOPT_ENCODING, "gzip");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    $ret = curl_exec($ch);
    if ($split) {
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($ret, 0, $headerSize);
        $body = substr($ret, $headerSize);
        $ret = array();
        $ret['header'] = $header;
        $ret['body'] = $body;
    }
    curl_close($ch);
    return $ret;
}

function get_curl_json(string $url, array $data)
{
    $data_json = json_encode($data);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json; charset=utf-8',
        'Content-Length: ' . strlen($data_json),
    ]);

    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}
  function coreget_curl($url,$post=0,$referer=0,$cookie=0,$header=0,$ua=0,$nobaody=0){
	$ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $klsf[] = 'accept: text/plain"';
    $klsf[] = 'Content-Type: application/json-patch+json';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $klsf);
    if ($post) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
    }
    if ($header) {
        curl_setopt($ch, CURLOPT_HEADER, true);
    }
    if ($cookie) {
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    }
    if ($nobaody) {
        curl_setopt($ch, CURLOPT_NOBODY, 1);
    }
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $ret = curl_exec($ch);
    curl_close($ch);
	return $ret;
}
  

function getcookie($head = 0)
{
    if (empty($head)) {
        return false;
    }
    $preg = '/Set-Cookie:\ (.*?);/';//获取
    preg_match_all($preg, $head, $view);
    $v = $view[1];
    for ($i = 0; $i < count($v); $i++) {
        $string .= $v[$i] . ';';
    }
    return $string;
}

function getbstr($str, $leftStr)
{
    $left = strpos($str, $leftStr);
    return substr($str, $left + strlen($leftStr));
}

//取中间文本
function getSubstr($str, $leftStr, $rightStr)
{
    $left = strpos($str, $leftStr);
    $right = strpos($str, $rightStr, $left);
    if ($left < 0 or $right < $left) return '';
    return substr($str, $left + strlen($leftStr), $right - $left - strlen($leftStr));
}

function cloud_get_curl($url, $post = 0, $cookie = 0, $header = 0, $nobaody = 0)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $klsf[] = 'accept: text/plain"';
    $klsf[] = 'Content-Type: application/json-patch+json';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $klsf);
    if ($post) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
    }
    if ($header) {
        curl_setopt($ch, CURLOPT_HEADER, true);
    }
    if ($cookie) {
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    }
    if ($nobaody) {
        curl_setopt($ch, CURLOPT_NOBODY, 1);
    }
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $ret = curl_exec($ch);
    curl_close($ch);
    return json_decode($ret);
}

function qcloud_get_curl($url, $post = 0, $cookie = 0, $header = 0, $nobaody = 0)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $klsf[] = 'accept: text/plain"';
    $klsf[] = 'Content-Type: application/json-patch+json';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $klsf);
    if ($post) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
    }
    if ($header) {
        curl_setopt($ch, CURLOPT_HEADER, true);
    }
    if ($cookie) {
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    }
    if ($nobaody) {
        curl_setopt($ch, CURLOPT_NOBODY, 1);
    }
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $ret = curl_exec($ch);
    curl_close($ch);
    return $ret;
}

function w_dump($var)
{
    if (is_object($var) and $var instanceof Closure) {
        $str = 'function (';
        $r = new ReflectionFunction($var);
        $params = array();
        foreach ($r->getParameters() as $p) {
            $s = '';
            if ($p->isArray()) {
                $s .= 'array ';
            } else if ($p->getClass()) {
                $s .= $p->getClass()->name . ' ';
            }
            if ($p->isPassedByReference()) {
                $s .= '&';
            }
            $s .= '$' . $p->name;
            if ($p->isOptional()) {
                $s .= ' = ' . var_export($p->getDefaultValue(), TRUE);
            }
            $params [] = $s;
        }
        $str .= implode(', ', $params);
        $str .= '){' . PHP_EOL;
        $lines = file($r->getFileName());
        for ($l = $r->getStartLine(); $l < $r->getEndLine(); $l++) {
            $str .= $lines[$l];
        }
        echo $str;
        return;
    } else if (is_array($var)) {
        echo "<xmp class='a-left'>";
        print_r($var);
        echo "</xmp>";
        return;
    } else {
        var_dump($var);
        return;
    }
}

// Parse version of php
function w_parse_version($version)
{
    $versionList = [];
    if (is_string($version)) {
        $rawVersionList = explode('.', $version);
        if (isset($rawVersionList[0])) {
            $versionList[] = $rawVersionList[0];
        }
        if (isset($rawVersionList[1])) {
            $versionList[] = $rawVersionList[1];
        }
    }
    return $versionList;
}

function encodeCrypt($data, $key)
{
    $privateKey = $key;
    //补齐方式: OPENSSL_ZERO_PADDING
    if (strlen($data) % 8) {
        $data = str_pad($data, strlen($data) + 8 - strlen($data) % 8, "\0");
    }
    $encrypted = openssl_encrypt($data, "DES-ECB", $privateKey, OPENSSL_NO_PADDING);
    return bin2hex($encrypted);
}

function decodeCrypt($string, $key)
{
    $privateKey = $key;
    //解密
    $encryptedData = hex2bin($string);
    $decrypted = openssl_decrypt($encryptedData, "DES-ECB", $privateKey, OPENSSL_NO_PADDING);
    return $decrypted;
}

 /**
    * 获取真实IP
    * @param int $type
    * @param bool $client
    * @return mixed
    */
    function get_client_ip($type = 0,$client=true) 
    {
            $type       =  $type ? 1 : 0;
            static $ip  =   NULL;
            if ($ip !== NULL) return $ip[$type];
            if($client){
                if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                    $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                    $pos    =   array_search('unknown',$arr);
                    if(false !== $pos) unset($arr[$pos]);
                    $ip     =   trim($arr[0]);
                }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                    $ip     =   $_SERVER['HTTP_CLIENT_IP'];
                }elseif (isset($_SERVER['REMOTE_ADDR'])) {
                    $ip     =   $_SERVER['REMOTE_ADDR'];
                }
            }elseif (isset($_SERVER['REMOTE_ADDR'])) {
                $ip     =   $_SERVER['REMOTE_ADDR'];
            }
            // 防止IP伪造
            $long = sprintf("%u",ip2long($ip));
            $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
            return $ip[$type];
        }
        
        
        function qqyunpost($url,$post_data=0) 
        {
                $timeout = 5 ;
                $ch = curl_init();
        		curl_setopt ($ch, CURLOPT_URL, $url);
                curl_setopt ($ch, CURLOPT_POST, 1);
                if($post_data != ''){
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
                }
                curl_setopt ($ch, CURLOPT_HTTPHEADER,array(
                  'X-Token: yoyohuhu',
                  'User-Agent: Apifox/1.0.0 (https://apifox.com)',
                  'Content-Type: application/json',
                  //'X-Bot-Id: 790818231'
               ));
                curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 
                curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
                //curl_setopt($ch, CURLOPT_HEADER, false);
                $file_contents = curl_exec($ch);
                curl_close($ch);
                return json_decode($file_contents,true);
        }
