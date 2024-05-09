<?php
namespace app\common\util;

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;

class Bill
{
   /**
    * 海报 
    * 
    * 使用方式
    * use app\common\util\Bill;
    * Bill::go('0001','http://www.baidu.com','static/admin/images/show.png','["200","300"]')
    * 
    * @param string $file 图片索引
    * @param string $link 链接
    * @param string $addr 海报地址
    * @param string $position 生成图像位置
    * @param string $path 最终生成位置
    * @return mixed
    */
    public static function go($file,$link,$addr,$position=['100','100'],$path='play_bill')
    {
        if (!$addr||filter_var($addr, FILTER_VALIDATE_URL) !== false){
             return ['code'=>'201','msg'=>'请配置海报图片,须为本地'];
        }
        if (!file_exists('./'.$path.'/')) mkdir('./'.$path.'/', 0777, true);
        $new_file = './'.$path.'/' .$file . '.png';
        $writer = new PngWriter();
        $qrCode = QrCode::create($link)
            ->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(new ErrorCorrectionLevelLow())
            ->setSize(144)
            ->setMargin(10)
            ->setRoundBlockSizeMode(new RoundBlockSizeModeMargin())
            ->setForegroundColor(new Color(0, 0, 0))
            ->setBackgroundColor(new Color(255, 255, 255));
        $result = $writer->write($qrCode);
        header('Content-Type: '.$result->getMimeType());
        $result->saveToFile($new_file);
        $image = \think\Image::open(public_path().$addr);
        $image->water($new_file,$position)->save(public_path().$new_file);
        return ['msg'=>$new_file];
    }
}