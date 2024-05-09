<?php
declare (strict_types = 1);

namespace app\common\service;
use think\facade\Session;
use think\facade\Cookie;
use think\facade\Db;
use think\App;
use think\facade\Request;
use app\common\model\YpayAccount as M;
use app\common\validate\YpayAccount as V;
use app\common\service\YpayUser as S;
use think\facade\Config;

class YpayAccount
{
    // 添加
    public static function goAdd($data)
    {
        $user = Db::table('ypay_user')->where('id',Session::get('front.id'))->find();
        if(empty($user['vip_time'])){
            return ['msg'=>"未开通会员套餐",'code'=>201];
        }
        $time = strtotime($user['vip_time']);
        if($time<time())
        {
            return ['msg'=>"会员套餐已过期",'code'=>201];
        }

        $data['user_id'] = Session::get('front.id');
        $channel = Db::table('admin_channel')->where('code',$data['code'])->find();
        $data['type'] = $channel['type'];
        $data['succcount'] = 0;
        $data['succprice'] = 0;
        //验证
        $validate = new V;
        if(!$validate->scene('add')->check($data))
        return ['msg'=>$validate->getError(),'code'=>201];
        if(empty($channel))
        {
            return ['msg'=>"通道不存在或标识重复",'code'=>201];
        }
        

        if($data['code']=="wxpay_dy")
        {
            $data['status'] = 1;
        }
 
        if($data['type']=="qqpay" || $data['code'] == 'qqpay_wzq'){
            if(empty($data['qq']))
            {
                return ['msg'=>"qq号不可为空",'code'=>201];
            }
            
        }
        if($data['code']=='alipay_app' || $data['code']=='wxpay_app' || $data['code']=='wxpay_zg' )
        {
            $data['status'] = 1;
        }
        
        
        if(!empty($data['aliappkey'])){
            $data['qr_url'] = $data['aliappkey'];
        }
        if($data['code']=='alipay_mck' || $data['code']=='alipay_dmf')
        {
            $data['wxname'] = $data['zfbapppid'];
            $data['status'] = 1;
        }
 
        
        if(!empty($data['remark'])){
            $data['remark'] = strip_tags($data['remark']);
        }
    
        try {
            M::create($data);
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }

    
    //修改支付宝当面付/商家账单通道
    public static function goEditAliPay($data){
        $update = [];
        try {
            if($data['code'] == 'alipay_dmf'){
                $update = 
                [
                    'wxname' => $data['appId'],
                    'cookie' => $data['publicKey'],
                    'qr_url' => $data['privateKey'],
                    'memo' => $data['memo']
                    ];
                M::where('id',$data['id'])->update($update);
            }else if($data['code'] == 'alipay_mck'){
                $update = 
                [
                    'zfb_pid' => $data['pid'],
                    'wxname' => $data['appId'],
                    'cookie' => $data['publicKey'],
                    'qr_url' => $data['privateKey'],
                    'memo' => $data['memo'],
                    'status' => 1
                    ];
                M::where('id',$data['id'])->update($update);
            }else if($data['code'] == 'alipay_app'){
                $update = 
                [
                    'zfb_pid' => $data['pid'],
                    'memo' => $data['memo']
                    ];
                M::where('id',$data['id'])->update($update);
            }else if($data['code'] == 'lkl_alipay' || $data['code'] == 'lkl_wxpay'){
                $update = 
                [
                    'zfb_pid' => $data['pid'],
                    'wxname' => $data['appId'],
                    'qr_url' => $data['privateKey'],
                    'remark' => $data['remark'],
                    'memo' => $data['memo'],
                    'status' => 1
                    ];
                M::where('id',$data['id'])->update($update);
            }else if($data['code'] == 'dougong_alipay' || $data['code'] == 'dougong_wxpay'){
                $update = 
                [
                    'zfb_pid' => $data['pid'],
                    'wxname' => $data['appId'],
                    'cookie' => $data['publicKey'],
                    'qr_url' => $data['privateKey'],
                    'remark' => $data['remark'],
                    'memo' => $data['memo']
                    ];
                M::where('id',$data['id'])->update($update);
            }else if($data['code'] == 'lebrush_alipay' || $data['code'] == 'lebrush_wxpay'){
                $update = 
                [
                    'zfb_pid' => $data['pid'],
                    'wxname' => $data['appId'],
                    'memo' => $data['memo']
                    ];
                M::where('id',$data['id'])->update($update);
            }
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }
    
    //修改微信APP挂机/自挂/店员通道
    public static function goEditWxPay($data){
        $update = [];
        try {
            if($data['code'] == 'wxpay_dy'){
                $update = 
                [
                    'wxname' => $data['wxname'],
                    'qr_url' => $data['qr_url'],
                    'memo' => $data['memo']
                    ];
                M::where('id',$data['id'])->update($update);
            }else{
                $update = 
                [
                    'qr_url' => $data['qr_url'],
                    'memo' => $data['memo']
                    ];
                M::where('id',$data['id'])->update($update);
            }
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }
    
    //修改QQ通道
    public static function goEditQQPay($data){
        $update = [];
        try {
            if($data['code'] == 'qqpay_zg'){
                $update = 
                [
                    'qq' => $data['qq'],
                    'memo' => $data['memo']
                    ];
                M::where('id',$data['id'])->update($update);
            }
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }
    
    // 编辑
    public static function goEdit($data,$id)
    {
        //获取通道ID
        $new_data['id'] = $id;
        //获取更改后的微信昵称
        $new_data['wxname'] = $data['wxname'];
        //验证
        // $validate = new V;
        // if(!$validate->scene('edit')->check($data))
        // return ['msg'=>$validate->getError(),'code'=>201];
        try {
             M::update($new_data);
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }

    // 更改在线状态
    public static function goStatus($data,$id)
    {
        $model =  M::find($id);
        if ($model->isEmpty())  return ['msg'=>'数据不存在','code'=>201];
        try{
            $model->save([
                'status' => $data,
            ]);
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }
    
    //更改使用状态
    public static function goIsStatus($data,$id)
    {
        $model =  M::find($id);
        if ($model->isEmpty())  return ['msg'=>'数据不存在','code'=>201];
        try{
            $model->save([
                'is_status' => $data,
            ]);
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }

    // 删除
    public static function goRemove($id)
    {
        $model = M::find($id);
        if ($model->isEmpty()) return ['msg'=>'数据不存在','code'=>201];
        try{
           $model->delete();
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }

    // 批量删除
    public static function goBatchRemove($ids)
    {
        if (!is_array($ids)) return ['msg'=>'数据不存在','code'=>201];
        try{
            M::destroy($ids);
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }
    
    // 一键清理所有离线通道
    public static function goRemove_line($data)
    {   
        if(empty($data['type'])){
            return ['code' => 201,'msg' =>'请先选择类型'];
        }
         $where = ['status' => 0,'type'=>$data['type']];
        try{
            M::where($where)->delete();
        }catch (\Exception $e){
            return ['msg'=>'操作失败'.$e->getMessage(),'code'=>201];
        }
    }
    
    //获取二维码信息
    public static function GetQrlistQrcode($id)
    {
        $acc = Db::table('ypay_account')->where('id', $id)->where('user_id',S::getUserId())->find();
        if(empty($acc))
        {
            return ['code'=>0,'msg'=>'通道不存在!'];
        }
        if($acc['type']=="alipay")
        {
            //获取支付宝登录二维码
            $data = self::GetAlipayLoginQrcode();
            
            Db::name('ypay_account')->where('id', $id)->update(['remark' => $data['loginid']]);
            return ['code'=>1,'msg'=>'获取成功!','qr_url'=>'/qrcode.php?text='.$data['qrcodeurl'],'loginid'=>$data['loginid']];
        }
        
        return ['code'=>0,'msg'=>'系统错误!'];
    }
    
    //获取扫码状态
    public static function GetChannelLoginStatus($id='')
    {
        $acc = Db::table('ypay_account')->where('id',$id)->where('user_id', S::getUserId())->find();
        if(empty($acc))
        {
            return ['code'=>0,'msg'=>'通道不存在!'];
        }
        if($acc['type']=="alipay")
        {
            $data = self::GetAlipayLoginStatus($acc['remark']);
            if($data['code']==1)
            {
                $pid = getSubstr(base64_decode($data['cookie']),"CLUB_ALIPAY_COM=",";");
                Db::name('ypay_account')->where('id', $id)->update(['cookie' => $data['cookie'],'status'=>1,'zfb_pid'=>$pid]);
                return ['code'=>1,'msg'=>'账号登录成功!','nick'=>"用户PID为：".$pid];
            }
            else
            {
                return $data;
            }
        }
        return ['code'=>0,'msg'=>'系统错误!'];
    }
    
        //获取支付宝登录二维码
    public static function GetAlipayLoginQrcode()
    {
        error_reporting(0);
        $url = "https://auth.alipay.com/login/index.htm";
		$data = self::Get_curl_header($url, 0, "Accept: image/gif, image/jpeg, image/pjpeg, application/x-ms-application, application/xaml+xml, application/x-ms-xbap, */*
Referer: https://auth.alipay.com/login/index.htm?goto=https%3A%2F%2Fmy.alipay.com%2Fportal%2Fi.htm
Accept-Language: zh-Hans-CN,zh-Hans;q=0.5
Accept-Encoding: gzip, deflate
User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.0) like Gecko
Host: authet2.alipay.com
Connection: Keep-Alive
Cache-Control: no-cache");
		preg_match("/securityId: \"(.*?)\",/", $data["body"], $match);
		$authcenter_qrcode_login = $match[1];
		preg_match("/s.sid = \"(.*?)\"/", $data["body"], $match);
		$authcenter_querypwd_login = $match[1];
		$rds_form_token = getSubstr($data["body"], "<input type=\"hidden\" value=\"", "\" name=\"rds_form_token\"/>");
		$alieditUid = getSubstr($data["body"], "<input type=\"hidden\" id=\"alieditUid\" name=\"alieditUid\" value=\"", "\" />");
		preg_match("/ALIPAYJSESSIONID=(.*?);/", $data["header"], $match);
		$ALIPAYJSESSIONID = $match[1];
		$AliQCode = "https://qr.alipay.com/_d?_b=PAI_LOGIN_DY&amp;securityId=" . urlencode($authcenter_qrcode_login);
        $array['loginid'] = $authcenter_qrcode_login . "YPay" . $authcenter_querypwd_login . "YPay" . $rds_form_token . "YPay" . $alieditUid;
        $array['qrcodeurl'] = urlencode($AliQCode);
        return $array;
    }
    
        //获取支付宝登录状态
    public static function GetAlipayLoginStatus($id = '')
    {
        error_reporting(0);
        if (empty($id)) {
            $jialan['code'] = 0;
            $jialan['msg'] = "loginid不能为空";
            return $jialan;
        } 
        
        $YPay = explode("YPay", $id);
		$url = "https://securitycore.alipay.com/barcode/barcodeProcessStatus.json?";
		$post_intl["securityId"] = $YPay[0];
		$post_intl["_callback"] = "light.request._callbacks.callback2";
		$post_intl = http_build_query($post_intl);
		$data_intl = get_curl($url . $post_intl, 0, 0, 0, 0, "Accept: image/gif, image/jpeg, image/pjpeg, application/x-ms-application, application/xaml+xml, application/x-ms-xbap, */*
Referer: https://auth.alipay.com/login/index.htm?goto=https%3A%2F%2Fmy.alipay.com%2Fportal%2Fi.htm
Accept-Language: zh-Hans-CN,zh-Hans;q=0.5
Accept-Encoding: gzip, deflate
User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.0) like Gecko
Host: authet2.alipay.com
Connection: Keep-Alive
Cache-Control: no-cache");

        if($data_intl == false){
            $data_intl = get_curl("https://securitycore.alipay.com/barcode/barcodeProcessStatus.json?securityId=" . $YPay[0] . "&_callback=light.request._callbacks.callback1", 0, "https://auth.alipay.com/login/index.htm?goto=https%3A%2F%2Fconsumeprod.alipay.com%2Frecord%2Fstandard.htm", param($id, "cookie"), 0, "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 UBrowser/6.2.4094.1 Safari/537.36");
        }

		if (strpos($data_intl, "waiting")) {
			$array['code'] = 0;
            $array['msg'] = "二维码还未扫描";
            return $array;
		} elseif (strpos($data_intl, "scanned")) {
			$array['code'] = 0;
            $array['msg'] = "等待确认中";
            return $array;
		} else {
		$url = "https://authet2.alipay.com/login/index.htm";
		$post["support"] = "000001";
		$post["needTransfer"] = "";
		$post["CtrlVersion"] = "1,1,0,1";
		$post["loginScene"] = "index";
		$post["redirectType"] = "";
		$post["personalLoginError"] = "";
		$post["goto"] = "https://www.alipay.com/";
		$post["errorVM"] = "";
		$post["sso_hid"] = "";
		$post["site"] = "";
		$post["errorGoto"] = "";
		$post["rds_form_token"] = $YPay[2];
		$post["json_tk"] = "";
		$post["method"] = "qrCodeLogin";
		$post["logonId"] = "";
		$post["superSwitch"] = "true";
		$post["noActiveX"] = "false";
		$post["passwordSecurityId"] = $YPay[1];
		$post["qrCodeSecurityId"] = $YPay[0];
		$post["scid"] = "";
		$post["password_input"] = "";
		$post["J_aliedit_using"] = "true";
		$post["password"] = "";
		$post["J_aliedit_key_hidn"] = "password";
		$post["J_aliedit_uid_hidn"] = "alieditUid";
		$post["alieditUid"] = $YPay[3];
		$post["REMOTE_PCID_NAME"] = "_seaside_gogo_pcid";
		$post["_seaside_gogo_pcid"] = "";
		$post["_seaside_gogo_"] = "";
		$post["_seaside_gogo_p"] = "";
		$post["J_aliedit_prod_type"] = "";
		$post["security_activeX_enabled"] = "true";
		$post["checkCode"] = "";
		$post["idPrefix"] = "";
		$post["preCheckTimes"] = 5;
		$post["ua"] = "dxlTasyfLePewE7ifH7HyT5wJ5cASsGqMaYnOomEiyTBeHyI6CezVWDWcDouca6W6Y4Svep9ulZ8H0cF1X4Mgi.JZTbQL3NddYS7bCmG.cFh45fZXR3kTmjsMi3xByeTW2V5hnaat0y1OOiv8qoAfKgaUuigtJAp3UL2QgUVrpASMRKdStX0h.hzFfH26FHtMkCmnf1nRcw74yljdFFMC03XWUBNZDhPUI0aL76t.NVxaOJQngu.KFQoPrVjSWYgym6MackvvBhmL37Y0s4H.ROLsAdVrDnLoQR7y07wcwWbUSqq.6AdBebIIVg1RHjyn3K9ahqPk_HOBlXyg6_voFZWwvoFlVAZ9c_ARvTidwDCE.18sT9z2ELtWGaAVClk6HN0HXMQUwOH7Rr7sfpn3zp__eOAe75qTBmYMNXnYnChZmqWOAaVdJAcFpjoUtwtwwqcZvdvoX4_UH.06SpF.Z0i85GWt4jSkki5ijEyvav5KeLQX6Tvj7MziuxQasAOVX6CHZu62D3FhWwj1cesYq9iKyzNmhMcqc.ULS88i3oq2vZko8vOI3BFufd0GcYAMI.YS8a4IqoaE4ydLO4ALR.8WuHvpOZiilHq.hOZogZB2QoQApBuo5smKdhzGlcybdYtsoxPtD_jIRXtf7aRzIWtIlcgEHk6RyOhjsA7bSuWurcukkCAvTZtO05xq6s_lmjMUVNeyS34DpiEXKJlqmbi.amq3hj2Oph0AZtvPb8LvZJy8V.aYdcC4RH9UeAOEzzJgpeiiAAUcMRexpGs5ZDcBFdgn4MIXfq2aTIUFVHf0W3in7tGaeNmx1MUIWHHTL_3SmNYyPVaEo5qZzNLTMrc318MBiIFjcTmaub1IJw7IZefBjspVK9bzzYMwhe0ljkUqoCoshjN9rlXjKyJ.vsXLosDb7KEKmWejetEAPRlw2e49JmWJj5ohGrOGzlsTzyMUtY07nlv3gjXWkaUaE";
		$post = http_build_query($post);
		 $data = self::Get_curl_header($url, $post, 0, "Accept: image/gif, image/jpeg, image/pjpeg, application/x-ms-application, application/xaml+xml, application/x-ms-xbap, */*
Referer: https://auth.alipay.com/login/index.htm?goto=https%3A%2F%2Fmy.alipay.com%2Fportal%2Fi.htm
Accept-Language: zh-Hans-CN,zh-Hans;q=0.5
Accept-Encoding: gzip, deflate
User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.0) like Gecko
Host: authet2.alipay.com
Connection: Keep-Alive
Cache-Control: no-cache");
		$data = $data["header"];
		$ALIPAYJSESSIONID = explode("ALIPAYJSESSIONID=", $data)[2];
		$ALIPAYJSESSIONID = explode("Domain=", $ALIPAYJSESSIONID)[0];
		$ctoken = explode("ctoken=", $data)[2];
		$ctoken = explode("Domain=", $ctoken)[0];
		$CLUB_ALIPAY_COM = explode("CLUB_ALIPAY_COM=", $data)[1];
		$CLUB_ALIPAY_COM = explode("Domain=", $CLUB_ALIPAY_COM)[0];
		$JSESSIONID = explode("JSESSIONID=", $data)[1];
		$JSESSIONID = explode(";", $JSESSIONID)[0];
		$alipay = explode("alipay=", $data)[1];
		$alipay = explode(";", $alipay)[0];
		$spanner = explode("spanner=", $data)[1];
		$spanner = explode(";", $spanner)[0];
		if ($ALIPAYJSESSIONID && $CLUB_ALIPAY_COM) {
			$cookies = "zone=GZ00C; JSESSIONID=" . $JSESSIONID . "; ali_apache_tracktmp=\"uid=" . $CLUB_ALIPAY_COM . "\"; IntroKey=true; session.cookieNameId=ALIPAYJSESSIONID; ALIPAYJSESSIONID=" . $ALIPAYJSESSIONID . " ctoken=" . $ctoken . " CLUB_ALIPAY_COM=" . $CLUB_ALIPAY_COM;
		}
			$array['msg'] = "登录成功";
			$array['code'] = 1;
			$array['cookie'] = base64_encode($cookies);
			return $array;
		}

    }
    
    	public static function Get_curl_header($url, $post, $ua)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		$httpheader[] = "Accept: */*";
		$httpheader[] = "Accept-Encoding: gzip,deflate,sdch";
		$httpheader[] = "Accept-Language: zh-CN,zh;q=0.8";
		$httpheader[] = "Connection: keep-alive";
		curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_ENCODING, "gzip");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if ($post) {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		}
		if ($ua) {
			curl_setopt($ch, CURLOPT_USERAGENT, $ua);
		} else {
			curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.25 Safari/537.36 Core/1.70.3741.400 QQBrowser/10.5.3863.400");
		}
		$ret = curl_exec($ch);
		$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($ret, 0, $headerSize);
		$body = substr($ret, $headerSize);
		$ret = [];
		$ret["header"] = $header;
		$ret["body"] = $body;
		curl_close($ch);
		file_put_contents("1.txt", "\r\n【" . date("Y-m-d H:i:s") . "\r\n" . json_encode($ret), FILE_APPEND);
		return $ret;
	}
}


