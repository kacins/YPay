<?php


namespace app\index\job;


use think\facade\Db;
//use think\queue\Job;
use app\common\service\Jialanshen;
use app\common\service\Notice as notice;
use app\common\model\YpayUserbasic as basic;
use app\common\util\Mail;


class Order
{

    /**
     * 在宝塔里的  Supervisord管理器==添加手护进程
     * 名称就是 队列名称，启动命令是：php think queue:listen --queue +队列名
     * 启动用户：root
     * @队列执行
     * @param Job $job
     * @param [type] $param
     *
     * @return void
     */
    public function fire($param)
    {
        try {
            //参数
            $data = $param;
            //根据参数筛选执行对应方法
            switch ($data['code']) {
                default:
                    $res = $this->handleOrder($data['code']); 
                    break;
            }
        }catch (\Exception $exception){
            record_log($exception->getMessage(),"exception");
        }
    }
    
    
    
    /**
     * @订单检测
     * @param [type] $id
     *
     * @return void
     */
    public function handleOrder($code)
    {
        
        //根据标识来进行处理心跳
        switch ($code) {
            case 'alipay_cron'://支付宝监控

                //账户查询参数
                $where = 
                [
                    ['type','=','alipay'],
                    ['status','=',1],
                    ['is_status','=',1],
                    ['code','<>','alipay_app'],
                    ['code','<>','alipay_dmf'],
                    ['code','<>','alipay_pc'],
                    ['update_time','<',date('Y-m-d H:i:s', time())]
                ];
            
                //获取账户信息
                $account = Db::name('ypay_account')->where($where)->orderRaw("rand()")->select();
                
                //如果没有账户 则退出 避免浪费资源
                if(empty($account)){
                    break;
                }
                $count = count($account);
                //开始循环
                foreach ($account as $row)
                {   
                    //记录账户数量
                    $row['count'] = $count;
                    //执行参数
                    $this->callback($row);
                }
                break;
           
        }
        return true;
    }
    
    
     /**
     * @订单请求回调
     * @param [type] $id
     *
     * @return void
     */
    public function callback($row){
        
        switch ($row['code']) {
   
            case 'alipay_grmg':
                    $cookie = base64_decode($row['cookie']);
                    $BeatMoney = self::getAlipayMoney($cookie);
                    if($BeatMoney == -1){
                        $BeatMoney = self::getAlipayMoney2($cookie);
                    }
                        
                    // 如果不等于 -1则进入流程 等于-1则为掉线
                    if($BeatMoney != -1)
                    {   
                        //大于或者等于10个则进行区分
                        if($row['count'] >= 10){
                            //更新账户更新时间
                            Db::name('ypay_account')->where('id', $row['id'])->update(['update_time'=>date('Y-m-d H:i:s', time()+rand(10,25))]);
                        }else{
                            //更新账户更新时间
                            Db::name('ypay_account')->where('id', $row['id'])->update(['update_time'=>date('Y-m-d H:i:s', time())]);
                        }
                        
                        //金额不相同则进入流程
                        if($row['money'] != $BeatMoney)
                        {
                                Db::name('ypay_account')->where('id', $row['id'])->update(['money' =>$BeatMoney]);//更新账户金额
                                $od_money = bcsub($BeatMoney, $row['money'], 2);
                                 //每次构建前先清空
                                $where = array();
                                // 构建订单查询条件
                                $where = 
                                [
                                    ['status','=',0],
                                    ['account_id','=',$row['id']],
                                    ['truemoney','=',$od_money],
                                    ['out_time','>',time()],
                                ];
                                
                                $order = Db::name('ypay_order')->where($where)->order('id desc')->lock(true)->find();
                                //如果该订单存在则执行回调操作
                                if(!empty($order))
                                {
                                    $url = Jialanshen::creat_callback($order);
                                    get_curl($url['notify']);
                                }
                            }
                    }else{
                        self::lose_expire($row['user_id'],'alipay',$row['id']);
                    }  
                break;
        }
        
    }
    
    /**
     * @掉线通知
     * @param [type] $id $type $channelID
     *
     * @return void
     */
     public function lose_expire($id,$type,$channelID){
        Db::name('ypay_account')->where('id',$channelID)->update(['status' => 0 , 'create_time' => date('Y-m-d H:i:s', time())]);//掉线
        $userinfo = Db::name('ypay_user')->find($id);//获取用户信息
        $basic = basic::where('user_id',$id)->find();//获取用户配置参数
        //调用通知方法 1.用户信息 2.用户配置参数 3.通道ID 4.通道类型
        notice::lose_tips($userinfo,$basic,$channelID,$type);
     }
    
    /**
     * @执行失败
     * @param [type] $data
     *
     * @return void
     */
    public function failed($data){
        // 记录日志
        record_log($data,'job_error');
    }
    
      public static function getAliPayMoney($Cookie){

        switch (rand(1, 5)) {
            case 1:
                $Url = "https://my.alipay.com/portal/i.htm?src=yy_taobao_gl_01&sign_from=3000&sign_account_no=&guide_q_control=top";
                break;
            case 2:
                $Url = "https://zht.alipay.com/asset/newIndex.htm";
                break;
            case 3:
                $Url = "https://shenghuo.alipay.com/send/payment/fill.htm?_pdType=adbhajcaccgejhgdaeih";
                break;
            case 4:
                $Url = "https://custweb.alipay.com/account/index.htm";
                break;
            case 5:
                $Url = "https://personalweb.alipay.com/portal/i.htm";
                break;
            default:
                $Url = "http://egg.alipay.com/egg/advice.htm";
                break;
        }

        //$Bao_Url = 'https://my.alipay.com/portal/i.htm?src=yy_taobao_gl_01&sign_from=3000&sign_account_no=&guide_q_control=top';
        $referer = $Url;
        $ua = 'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8
        accept-encoding: gzip, deflate, br
        accept-language: zh-CN,zh;q=0.9
        cache-control: max-age=0
        Cookie: ' . @$Cookie . '
        referer: ' . $referer . '
        upgrade-insecure-requests: 1
        user-agent: Mozilla/5.0 (Linux; Android 10.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.101 Safari/537.36 360Browser/9.2.5584.400';
        //$data = $this->Get_Money_curl_intl($Url, 0,$referer,$Cookie,0,$ua);
        $Money = self::Get_Alipay_Cookie_Beat($Url, $Cookie);
        return $Money;

    }
    
        //获取支付宝余额 参数:cookie
    public static function getAlipayMoney2($Cookie){
        switch(rand(1,9)){
				case 1:
					$data = self::Get_Alipay_Cookie('https://personalweb.alipay.com/portal/i.htm',$Cookie);
				break;
				case 2:
					$data = self::Get_Alipay_Cookie('https://my.alipay.com/wealth/index.html',$Cookie);
 				break;
				case 3:
					$data = self::Get_Alipay_Cookie('https://110.alipay.com/sc/index.htm',$Cookie);
				break;
				case 4:
					$data = self::Get_Alipay_Cookie('https://my.alipay.com/portal/i.htm',$Cookie);
				break;
				case 5:
					$data = self::Get_Alipay_Cookie('https://shanghu.alipay.com/home/switchPersonal.htm',$Cookie);
				break;
				case 6:
					$data = self::Get_Alipay_Cookie('https://cshall.alipay.com/lab/question.htm',$Cookie);
				break;
				case 7:
					$data = self::Get_Alipay_Cookie('https://cshall.alipay.com/lab/cateQuestion.htm',$Cookie);
				break;
				case 8:
					$data = self::Get_Alipay_Cookie('https://cshall.alipay.com/lab/help_detail.htm',$Cookie);
				break;
				case 9:
					$data = self::Get_Alipay_Cookie('https://egg.alipay.com/egg/index.htm',$Cookie);
				break;
				default:
					$data = self::Get_Alipay_Cookie('http://egg.alipay.com/egg/advice.htm',$Cookie);
				break;
			}

			return $data;
    }

    public static function Get_Alipay_Cookie($Url_Referer, $Cookie = null)
	{ 
		$Url ="https://shanghu.alipay.com/user/myAccount/index.htm";
		$referer = $Url_Referer.'?&t='.time();
		$ua = 'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8
            accept-encoding: gzip, deflate, br
            accept-language: zh-CN,zh;q=0.9
            cache-control: max-age=0
            Cookie: '.@$Cookie.'
            referer: '.$Url.'?referer='.$referer.'
            upgrade-insecure-requests: 1
            user-agent: Mozilla/5.0 (Linux; Android 10.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.101 Safari/537.36 360Browser/9.2.5584.400';
		$result = self::Get_Money_curl_intl($Url_Referer,0,$referer,$Cookie,0,$ua);
		$result = mb_convert_encoding(self::Get_Money_curl_intl($Url,0,$referer,$Cookie,0,$ua),"UTF-8","GB2312" );
		if(!strstr($result, '余额'))$result = "-1";else $result = getSubstr($result, '<em class="aside-available-amount">','</em>元</span></li>');
		return $result;
	} 
        public static function Get_Alipay_Cookie_Beat($Url_Referer, $Cookie = null)
    {
        //$ctoken = $this->getSubstr($Cookie, "ctoken=", ";");
        $alipay_url = "https://lab.alipay.com/user/balance/index.htm";
        $Url = "https://uemprod.alipay.com/service.json?ctoken=&_input_charset=utf-8&_ksTS=" . microtime(true) . "&operation=mrchcenter.artisan.v2.ext.query&data=%7B%22pageSource%22%3A%22b_site_mrchenter_home_index_route%22%7D";
        $referer = $Url_Referer . "?&t=" . time();
        $referer1 = "https://mrchportalweb.alipay.com/";
        $ua = "accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8
        accept-encoding: gzip, deflate, br
        accept-language: zh-CN,zh;q=0.9
        cache-control: max-age=0
        Cookie: " . $Cookie . "
        referer: " . $Url . "?referer=" . $referer . "
        upgrade-insecure-requests: 1
        user-agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.80 Safari/537.36 Core/1.47.277.400 QQBrowser/9.4.7658.400";
        $result = self::Get_Money_curl_intl($Url_Referer, 0, $referer, $Cookie, 0, $ua);
        $result = mb_convert_encoding(self::Get_Money_curl_intl($alipay_url, 0, $referer, $Cookie, 0), "UTF-8", "GB2312");
        if (!strstr($result, "可用余额")) {
            $result = "-1";
        } else {
            $result = getSubstr($result, "<span class=\"fm-free\">", "</span>");
        }
        return $result;
    }
    
        public static function Get_Money_curl_intl($url, $post = 0, $referer = 0, $cookie = 0, $header = 0, $ua = 0, $nobaody = 0)
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
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		if ($post) {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		}
		if ($header) {
			curl_setopt($ch, CURLOPT_HEADER, true);
		}
		if ($cookie) {
			curl_setopt($ch, CURLOPT_COOKIE, $cookie);
		}
		if ($referer) {
			if ($referer == 1) {
				curl_setopt($ch, CURLOPT_REFERER, "http://m.qzone.com/infocenter?g_f=");
			} else {
				curl_setopt($ch, CURLOPT_REFERER, $referer);
			}
		}
		if ($ua) {
			curl_setopt($ch, CURLOPT_USERAGENT, $ua);
		} else {
			curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Linux; U; Android 4.4.1; zh-cn; R815T Build/JOP40D) AppleWebKit/533.1 (KHTML, like Gecko)Version/4.0 MQQBrowser/4.5 Mobile Safari/533.1");
		}
		if ($nobaody) {
			curl_setopt($ch, CURLOPT_NOBODY, 1);
		}
		curl_setopt($ch, CURLOPT_ENCODING, "gzip");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$ret = curl_exec($ch);
		curl_close($ch);
		return $ret;
	}
	
	

}