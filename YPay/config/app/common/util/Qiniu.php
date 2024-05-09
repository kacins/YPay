<?php
namespace app\common\util;

use \Qiniu\Auth;
use \Qiniu\Storage\UploadManager;

class Qiniu
{
    /**
     *七牛云
     */
    public static function QiniuOSS($filePath, $Extension, $path)
    {
        $data = getConfig();
        $accessKey = $data['qiniu-AK'];
        $secretKey = $data['qiniu-SK'];
        $domain = $data['qiniu-Domain'];
        $bucket = $data['qiniu-Bucket'];
        // 初始化签权对象
        $auth = new Auth($accessKey, $secretKey);

        // 生成上传Token
        $token = $auth->uploadToken($bucket);
        // 构建 UploadManager 对象
        $uploadMgr = new UploadManager();

        $key = $path . '/' . date("Ymd") . '/' . time() . rand(10000, 99999) . '.' . $Extension; // 文件名称

        list($res, $err) = $uploadMgr->putFile($token, $key, $filePath);

        if ($err !== null) {
            return ['code' => 201, 'msg' => $err];
        } else {
            return ['code' => 200, 'src' => $domain . $res['key']];
        }
    }

    /**
     *删除资源
     */
    public static function QiniuDel($path)
    {
        $data = getConfig();
        $accessKey = $data['qiniu-AK'];
        $secretKey = $data['qiniu-SK'];
        $domain = $data['qiniu-Domain'];
        $bucket = $data['qiniu-Bucket'];
        //初始化Auth状态
        $auth = new Auth($accessKey, $secretKey);
        $config = new \Qiniu\Config();
        $bucketManager = new \Qiniu\Storage\BucketManager($auth, $config);
        $err = $bucketManager->delete($bucket, $path);

        if ($err !== null) {
            return ['code' => 201, 'msg' => $err];
        } else {
            return true;
        }
    }
}
