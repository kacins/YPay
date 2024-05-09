<?php
namespace app\common\util;

use OSS\OssClient;
use OSS\Core\OssException;
class Oss
{
   /**
    *阿里云
    */
    public static function alYunOSS($filePath,$Extension,$path){
       $data = getConfig();
       $accessKeyId =  $data['file-accessKeyId']; 
       $accessKeySecret = $data['file-accessKeySecret']; 
       $endpoint = $data['file-endpoint'];
       $bucket= $data['file-OssName'];    
       $object = $path.'/'.date("Ymd").'/'.time().rand(10000,99999).'.'.$Extension;    // 文件名称
       try{
           $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint,true);
           $rel = $ossClient->uploadFile($bucket, $object, $filePath);
               return  ['code' => 200,'src' => $rel["info"]["url"]];
       } catch(OssException $e) {
               return ['code' => 201,'msg' => $e->getMessage()];
       }
   }

   /**
    *删除oss
    */
    public static function alYunDel($path)
    {
        $data = getConfig();
        $accessKeyId =  $data['file-accessKeyId']; 
        $accessKeySecret = $data['file-accessKeySecret']; 
        $endpoint = $data['file-endpoint'];
        $bucket= $data['file-OssName'];  
        try{
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint,true);
            $ossClient->deleteObject($bucket, $path);
            return true;
        }catch (OssException $e){
            return $e->getMessage();
        }
    }
}