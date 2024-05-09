<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no"/>
		<title>{$msg}</title>
		<link rel="stylesheet" href="/static/component/pear/css/pear.css" />
	    <link rel="stylesheet" href="/static/admin/css/other/result.css" />
	</head>
	<body class="pear-container">
    	<div class="layui-card">
			<div class="layui-card-body">
				<div class="result">
					<div class="success">
					<svg viewBox="64 64 896 896" data-icon="check-circle" width="80px" height="80px" fill="currentColor" aria-hidden="true" focusable="false" class=""><path d="M699 353h-46.9c-10.2 0-19.9 4.9-25.9 13.3L469 584.3l-71.2-98.8c-6-8.3-15.6-13.3-25.9-13.3H325c-6.5 0-10.3 7.4-6.5 12.7l124.6 172.8a31.8 31.8 0 0 0 51.7 0l210.6-292c3.9-5.3.1-12.7-6.4-12.7z"></path><path d="M512 64C264.6 64 64 264.6 64 512s200.6 448 448 448 448-200.6 448-448S759.4 64 512 64zm0 820c-205.4 0-372-166.6-372-372s166.6-372 372-372 372 166.6 372 372-166.6 372-372 372z"></path></svg>
				    </div>
					<h2 class="title">{$msg}</h2>
					<p class="desc">
						 {$data}
					</p>
					<div class="action">
                     <a id="href" href="{$url}" class="pear-btn pear-btn-primary">立即跳转</a>
                     <button  class="pear-btn"><b id="wait"><?php echo($wait);?></b>秒后自动跳转</button>
					</div>
				</div>
			</div>
		</div>
        <script type="text/javascript">
        (function(){
            var wait = document.getElementById('wait'),
                href = document.getElementById('href').href;
            var interval = setInterval(function(){
                var time = --wait.innerHTML;
                if(time <= 0) {
                    location.href = href;
                    clearInterval(interval);
                };
            }, 1000);
        })();
    </script>
	</body>
</html>
