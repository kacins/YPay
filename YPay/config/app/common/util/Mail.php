<?php
namespace app\common\util;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use app\BaseController as B;
use think\facade\Request;
use PHPMailer\PHPMailer\Exception;
class Mail
{
    /**
     * 发送邮箱
     * 
     * 使用方式
     * use app\common\util\Mail;
     * Mail::go('123@qq.com','这是来自一封信','你好！')
     * 
     * @param array $data
     * @param string $addr 地址
     * @param string $title 标题
     * @param string $content 内容
     * * @param string $type 发件类别
     * @return mixed
     */
    public static function go($addr,$title,$content,$type = null)
    {   
        $mail = new PHPMailer(true);
        $data = getConfig();
        try {
             //Server settings
             $mail->SMTPDebug = 0;                      //SMTP::DEBUG_SERVER/1为开启调试模式
             $mail->CharSet = 'utf-8';  
             $mail->isSMTP();                                            //Send using SMTP
             $mail->Host       = $data['smtp-host'];                     //Set the SMTP server to send through
             $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
             $mail->Username   = $data['smtp-user'];                     //SMTP username
             $mail->Password   = $data['smtp-pass'];                               //SMTP password
             $mail->SMTPSecure = $data['SmtpSecure'];            //Enable implicit TLS encryption
             $mail->Port = $data['smtp-port'];                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer          ::ENCRYPTION_STARTTLS`
            
             //Recipients
            $mail->setFrom($data['smtp-user'],$data['sitename']);
            $mail->addAddress($addr);    
            $mail->isHTML(true);                                 
            $mail->Subject = $data['sitename'].'-'.$title;
            $mail->Body = $content;
            $mail->send();
            return ['code'=>'200','msg'=>'发送成功'];
        } catch (Exception $e) {
            return ['code'=>'201','msg'=>'发送失败'.$e->errorMessage()];
        }
    }

}