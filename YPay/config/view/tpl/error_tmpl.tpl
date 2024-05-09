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
					<div class="error">
					<svg viewBox="64 64 896 896" data-icon="close-circle" width="80px" height="80px" fill="currentColor" aria-hidden="true" focusable="false" class=""><path d="M685.4 354.8c0-4.4-3.6-8-8-8l-66 .3L512 465.6l-99.3-118.4-66.1-.3c-4.4 0-8 3.5-8 8 0 1.9.7 3.7 1.9 5.2l130.1 155L340.5 670a8.32 8.32 0 0 0-1.9 5.2c0 4.4 3.6 8 8 8l66.1-.3L512 564.4l99.3 118.4 66 .3c4.4 0 8-3.5 8-8 0-1.9-.7-3.7-1.9-5.2L553.5 515l130.1-155c1.2-1.4 1.8-3.3 1.8-5.2z"></path><path d="M512 65C264.6 65 64 265.6 64 513s200.6 448 448 448 448-200.6 448-448S759.4 65 512 65zm0 820c-205.4 0-372-166.6-372-372s166.6-372 372-372 372 166.6 372 372-166.6 372-372 372z"></path></svg>
				    </div>
					<h2 class="title">{$msg}</h2>
					<p class="desc">
                    {$data}
					</p>
					<div class="action">
					 {if $msg=='权限不足'}
					 <a href="javascript:void(0);" id="href" class="pear-btn pear-btn-primary">立即关闭</a>
					 <button  class="pear-btn"><b id="close"><?php echo($wait);?></b>秒后自动关闭</button>
					 {else}
					 <a id="href" href="{$url}" class="pear-btn pear-btn-primary">立即跳转</a>
					 <button  class="pear-btn"><b id="wait"><?php echo($wait);?></b>秒后自动跳转</button>
					 {/if}
					</div>
				</div>
			</div>
		</div>
		<script src="/static/component/layui/layui.js"></script>
		<script src="/static/component/pear/pear.js"></script>
        <script type="text/javascript">
		{if $msg=='权限不足'}
			layui.use(['jquery'], function() {
				let $ = layui.jquery;
				var close = document.getElementById('close'),
					href = document.getElementById('href');
					href.onclick = function(){window.closeOpen();}
					var interval = setInterval(function(){
						var time = --close.innerHTML;
						if(time <= 0) {
							window.closeOpen();
							clearInterval(interval);
						};
					}, 1000);
				window.closeOpen = function() {
					parent.layer.close(parent.layer.getFrameIndex(window.name))??$(".layui-this i ",parent.document).click();
				}
			})
		{else}
			var wait = document.getElementById('wait'),
				href = document.getElementById('href').href;
			var interval = setInterval(function(){
				var time = --wait.innerHTML;
				if(time <= 0) {
					location.href = href;
					clearInterval(interval);
				};
			}, 1000);
		{/if}
    </script>
	</body>
</html>
