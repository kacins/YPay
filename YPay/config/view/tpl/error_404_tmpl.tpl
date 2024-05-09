<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no"/>
		<title></title>
		<link href="/static/component/pear/css/pear.css" rel="stylesheet" />
		<link href="/static/admin/css/other/error.css" rel="stylesheet" />
	</head>
	<body>
		<div class="content">
			<img src="/static/admin/images/404.svg" alt="">
			<div class="content-r">
				<h1>404</h1>
				<p>抱歉，你访问的页面不存在</p>
				<button class="pear-btn pear-btn-primary" id="href">返回首页</button>
			</div>
		</div>
		<!-- 资 源 引 入 -->
		<script src="/static/component/layui/layui.js"></script>
		<script src="/static/component/pear/pear.js"></script>
		<script>
        layui.use(['jquery'], function () {
            var $ = layui.jquery;
			$('#href').on('click', function () {
				location.href = '/';
			});
		})
		</script>
	</body>
</html>
