<?php
$array =$_GET;
$alipayh5url='alipayqr://platformapi/startapp?saId=20000032&url='.urlencode('alipays://platformapi/startapp?appId=20000123&actionType=scan&biz_data=%7B%22s%22%3A%22money%22%2C%22u%22%3A%22'.$array['user_id'].'%22%2C%22a%22%3A%22'.$array['price'].'%22%2C%22m%22%3A%22'.$array['trade_no'].'%22%7D');
?>

<html>
<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" name="viewport" />
<meta charset="utf-8">
<title>官方认证</title>

<body style="margin:0;padding:0;height:100%" scroll=no>
<div id="msg">loading...</div>
<object data="https://baidu.com" width="0" height="0"></object>
<iframe id="infrm" marginwidth="0" marginheight="0" width="100%" scrolling=auto frameborder="0" ="100%" target="_parent"></iframe>
<script src="//lib.baomitu.com/jquery/3.7.1/jquery.js"></script>;




  <script type="text/javascript">

  if (window.navigator.userAgent.match(/(phone|pad|pod|iPhone|iPod|ios|iPad|Android|Mobile|BlackBerry|IEMobile|MQQBrowser|JUC|Fennec|wOSBrowser|BrowserNG|WebOS|Symbian|Windows Phone)/i)) {
		var url_scheme = '<?=$alipayh5url?>';
		window.location.href = url_scheme;
		layer.msg('正在自动唤醒支付宝...', {shade: 0,time: 1000});
        }

</script>