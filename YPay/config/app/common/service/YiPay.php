<?php
declare (strict_types = 1);

namespace app\common\service;
use think\facade\Request;
use think\facade\Db;
use think\facade\Config;

class YiPay
{
    
    private $pid;
	private $key;
	private $submit_url;
	private $sign_type = 'MD5';

    function __construct($eappid='', $esecret_key='',$egateway_url='')
    {

            $this->pid = $eappid;//这里改成码支付ID
            $this->key = $esecret_key; //这是您的通讯密钥
            $this->submit_url = $egateway_url. 'submit.php';
        
    }
    
    //生成签名
	static public function makeSign($data, $key) {
		ksort($data);
		$signStr = '';
		foreach ($data as $k => $v) {
			if($k != 'sign' && $k != 'sign_type' && $v != ''){
				$signStr .= $k . '=' . $v . '&';
			}
		}
		$signStr = substr($signStr, 0, -1);
		$sign = md5($signStr . $key);
		return $sign;
	}
    
    //验证签名
	static public function verifySign($data, $key) {
		if(!isset($data['sign'])) return false;
		$sign = self::makeSign($data, $key);
		return $sign === $data['sign'];
	}

    
    	// 发起支付（页面跳转）
	public function pagePay($param_tmp, $button='正在跳转'){
		$param = $this->buildRequestParam($param_tmp);

		$html = '<form id="dopay" action="'.$this->submit_url.'" method="post">';
		foreach ($param as $k=>$v) {
			$html.= '<input type="hidden" name="'.$k.'" value="'.$v.'"/>';
		}
		$html .= '<input type="submit" value="'.$button.'"></form><script>document.getElementById("dopay").submit();</script>';

		return $html;
	}
	
	private function buildRequestParam($param){
		$mysign = self::makeSign($param,$this->key);
		$param['sign'] = $mysign;
		$param['sign_type'] = $this->sign_type;
		return $param;
	}

	// 计算签名
	private function getSign($param){
		ksort($param);
		reset($param);
		$signstr = '';
	
		foreach($param as $k => $v){
			if($k != "sign" && $k != "sign_type" && $v!=''){
				$signstr .= $k.'='.$v.'&';
			}
		}
		$signstr = substr($signstr,0,-1);
		$signstr .= $this->key;
		$sign = md5($signstr);
		return $sign;
	}
    
    
}
