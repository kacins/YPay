--
-- 表的结构 `admin_admin`
--

CREATE TABLE `admin_admin` (
  `id` int(11) UNSIGNED NOT NULL COMMENT 'ID',
  `username` varchar(30) NOT NULL COMMENT '用户名，登陆使用',
  `password` varchar(30) NOT NULL COMMENT '用户密码',
  `nickname` varchar(30) NOT NULL COMMENT '用户昵称',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '用户状态：1正常,2禁用 默认1',
  `token` varchar(60) DEFAULT NULL COMMENT 'token',
  `create_time` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT NULL COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='管理表';


-- --------------------------------------------------------

--
-- 表的结构 `admin_admin_log`
--

CREATE TABLE `admin_admin_log` (
  `id` int(11) UNSIGNED NOT NULL COMMENT 'ID',
  `uid` int(11) DEFAULT NULL COMMENT '管理员ID',
  `url` varchar(255) NOT NULL DEFAULT '' COMMENT '操作页面',
  `desc` text COMMENT '日志内容',
  `ip` varchar(20) NOT NULL DEFAULT '' COMMENT '操作IP',
  `user_agent` text NOT NULL COMMENT 'User-Agent',
  `create_time` timestamp NULL DEFAULT NULL COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='管理员日志';

-- --------------------------------------------------------

--
-- 表的结构 `admin_admin_permission`
--

CREATE TABLE `admin_admin_permission` (
  `id` int(11) UNSIGNED NOT NULL COMMENT 'ID',
  `admin_id` int(11) DEFAULT NULL COMMENT '用户ID',
  `permission_id` int(11) DEFAULT NULL COMMENT '权限ID'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='管理-权限中间表';

-- --------------------------------------------------------

--
-- 表的结构 `admin_admin_role`
--

CREATE TABLE `admin_admin_role` (
  `id` int(11) UNSIGNED NOT NULL COMMENT 'ID',
  `admin_id` int(11) DEFAULT NULL COMMENT '用户ID',
  `role_id` int(11) DEFAULT NULL COMMENT '角色ID'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='管理-角色中间表';

-- --------------------------------------------------------

--
-- 表的结构 `admin_channel`
--

CREATE TABLE `admin_channel` (
  `id` int(11) UNSIGNED NOT NULL COMMENT 'id',
  `name` varchar(50) DEFAULT NULL COMMENT '通道名称',
  `type` varchar(50) DEFAULT NULL COMMENT '支付类型',
  `create_type` int(1) DEFAULT '1' COMMENT '创建类型',
  `code` varchar(50) DEFAULT NULL COMMENT '通道标识',
  `info` varchar(225) DEFAULT NULL COMMENT '通道介绍',
  `status` int(11) NOT NULL DEFAULT '1' COMMENT '通道状态',
  `create_time` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `maxcount` int(11) NOT NULL DEFAULT '10'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='通道列表';

--
-- 转存表中的数据 `admin_channel`
--

INSERT INTO `admin_channel` (`id`, `name`, `type`, `create_type`, `code`, `info`, `status`, `create_time`, `sort`, `maxcount`) VALUES
(2, '支付宝个人版', 'alipay', 1, 'alipay_grmg', '支付宝个人版免挂', 1, '2022-05-18 20:28:59', 5, 10),
(3, '支付宝软件版', 'alipay', 1, 'alipay_pc', '用户自行使用软件挂机', 1, '2022-05-18 20:30:03', 3, 10),
(5, '微信店员版', 'wxpay', 1, 'wxpay_dy', '微信店员免挂模式', 1, '2022-05-22 09:51:43', 6, 10),
(11, '微信个人自挂', 'wxpay', 1, 'wxpay_zg', '鲲鹏框架用户自挂', 1, '2022-06-02 10:52:46', 9, 10),
(12, '微信APP挂机', 'wxpay', 1, 'wxpay_app', '微信APP软件挂机', 1, '2022-06-14 20:31:24', 10, 10),
(13, '支付宝APP挂机', 'alipay', 1, 'alipay_app', '支付宝APP挂机', 1, '2022-06-14 20:31:54', 0, 10),
(14, '支付宝当面付', 'alipay', 1, 'alipay_dmf', '支付宝当面付接口', 1, '2022-07-01 17:57:03', 1, 10),
(18, 'PCQQ自挂', 'qqpay', 1, 'qqpay_zg', 'QQPC自挂', 1, '2023-06-06 22:49:59', 14, 10);

-- --------------------------------------------------------

--
-- 表的结构 `admin_config`
--

CREATE TABLE `admin_config` (
  `id` int(11) NOT NULL,
  `config_name` varchar(191) NOT NULL,
  `config_value` longtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `admin_config`
--

INSERT INTO `admin_config` (`id`, `config_name`, `config_value`) VALUES
(1, 'sitename', 'YPay'),
(2, 'title', '一个专业的系统平台开发商,值得一试'),
(3, 'key', 'YPay,源支付'),
(4, 'desc', '一个专业的系统平台开发商,值得一试'),
(6, 'is_weboff', '1'),
(8, 'logo', '/upload/images/20220825/cdacdbbf182b79cf9303bf4767273094.png'),
(9, 'diy_js', ''),
(10, 'smtp-host', ''),
(11, 'SmtpSecure', 'ssl'),
(12, 'smtp-port', ''),
(13, 'smtp-user', ''),
(14, 'smtp-pass', ''),
(15, 'smstype', 'qcloud'),
(16, 'alisms-accessKeyId', ''),
(17, 'alisms-Secret', ''),
(18, 'alisms-SignName', ''),
(19, 'alisms-LoginCodeId', ''),
(20, 'alisms-RegCodeId', ''),
(21, 'tensms-accessKeyId', ''),
(22, 'tensms-Secret', ''),
(23, 'tensms-SignName', ''),
(24, 'tensms-AppId', ''),
(25, 'tensms-LoginCodeId', ''),
(26, 'tensms-RegCodeId', ''),
(27, 'smsbao-user', ''),
(28, 'smsbao-pass', ''),
(29, 'smsbao-SignName', ''),
(30, 'file-type', '1'),
(31, 'file-endpoint', ''),
(32, 'file-OssName', ''),
(33, 'file-accessKeyId', ''),
(34, 'file-accessKeySecret', ''),
(35, 'qiniu-Domain', ''),
(36, 'qiniu-Bucket', ''),
(37, 'qiniu-AK', ''),
(38, 'qiniu-SK', ''),
(39, 'min_orderprice', '0'),
(40, 'max_orderprice', '1000'),
(41, 'shield_key', '百度云|摆渡|云盘|点券|芸盘|萝莉|罗莉|网盘|黑号|q币|Q币|扣币|qq货币|QQ货币|花呗|baidu云|bd云|吃鸡|透视|自瞄|后座|穿墙|脚本|外挂|辅助|检测|武器|套装'),
(42, 'shield_tips', '温馨提醒该商品禁止出售，如有疑问请联系客服QQ：'),
(46, 'diy_clerkqr', ''),
(55, 'clerk_key', ''),
(63, 'clerk_id', ''),
(64, 'diy_task_key', 'PV7OXA8hOH'),
(65, 'bgtype', '0'),
(66, 'bg', '/upload/images/20240427/db8d3c42cdabfd7145a4b6afa300d905.jpg'),
(67, 'api_bg', ''),
(70, 'logincode-type', '0'),
(71, 'regcode-type', '0'),
(72, 'user_agreement', ''),
(73, 'privacy', ''),
(85, 'demopay_name', '一个奥利奥'),
(96, 'captcha-type', '0'),
(106, 'smsbao-api', ''),
(107, 'email_switch', '0'),
(108, 'code_switch', '0'),
(109, 'is_reg', '1'),
(110, 'is_notice', '1'),
(111, 'sh_notice', ''),
(112, 'td_notice', ''),
(113, 'index_popup', ''),
(114, 'reg_popup', ''),
(125, 'min_recharge', '0'),
(126, 'max_recharge', '1000'),
(128, 'retrieve-type', '0'),
(129, 'diy_orderTips', '你有一个新订单!请留意网站,订单号:[out_trade_no],商品名称:[name],商品金额:[money],收款方式:[type],收款通道:[account],下单时间:[create_time],支付时间,[end_time]'),
(135, 'demopay_money', '1'),
(136, 'qqpay', '0'),
(137, 'wechat', '0'),
(138, 'alipay', '0'),
(141, 'qq_login', '1'),
(142, 'wechat_login', '1'),
(143, 'epayurl_demo', ''),
(144, 'epayid_demo', ''),
(145, 'epaykey_demo', ''),
(146, 'wxpusher_switch', '0'),
(147, 'wxpusher_appToken', ''),
(150, 'db_version', '170'),
(153, 'qr_codeType', '2'),
(154, 'favicon', ''),
(164, 'home_temp', 'default'),
(170, 'create_qrCode', '2'),
(173, 'orderDisplay', '1000'),
(174, 'diy_recharge', 'qqpay,wxpay,alipay'),
(175, 'imageSize', '2000'),
(176, 'randomKey', ''),
(184, 'forceRealName', '0'),
(185, 'realNameBear', '0'),
(186, 'bearMoney', NULL),
(187, 'reportPos', '0'),
(188, 'reportUrl', '/'),
(189, 'reportTips', '<p style=\"font-size: 28px;font-weight: bold; color: red;\">防诈骗告知</p><p>尊敬消费者你好，您所使用的网站接入了我们的个人二维码支付收款系统</p><p>为保证您的权益和监督商家所提供的商品合规合法，如您被诈骗或发现此网站商品存在违规违法</p><p>请截图保留证据并积极向我们联系举报，如果您举报问题属实，我们将对此类商家进行清退处理</p>'),
(190, 'reportTitle', '举报商家'),
(191, 'reportYes', '举 报 该 商 家'),
(192, 'reportNo', '商 家 没 问 题'),
(194, 'adminMail', NULL),
(213, 'home_popup', ''),
(217, 'diy_codeTemp', '你好！验证码为：[code]，5分钟内有效'),
(218, 'diy_loginTips', '您好,您的账号ID：[login_uid] ,账户:[login_name]已登录成功,登录IP:[login_ip],登录时间:[login_time]'),
(223, 'diy_regTips', '注册成功,您得ID为:[userId],您的账户为:[userName],您的注册IP为[register_ip]'),
(224, 'diy_loseTips', '您好！，您有[account_type]通道已掉线，通道为:[account_code]通道ID为：[account_id]，掉线时间为:[lose_time]'),
(226, 'diy_moneyTips', '你的账户还剩[money],请尽快充值,避免影响使用!'),
(229, 'timeout', '300'),
(231, 'diy_demoPay', 'qqpay,wxpay,alipay'),
(234, 'home_url', '1'),
(238, 'is_logOff', '1'),
(239, 'is_pay_api', '1'),
(240, 'is_quotations', '1'),
(241, 'pay_api', '/'),
(242, 'quotations', 'https://v.api.aa1.cn/api/yiyan/index.php'),
(243, 'is_reg_give_price', '1'),
(244, 'reg_give_price', '1'),
(245, 'copyright', 'Copyright © <script>\r\n document.write(new Date().getFullYear());\r\n </script> <a href=\"/\">YPAY</a> - All\r\n rights reserved<span class=\"sep\"> | </span><a href=\"https://beian.miit.gov.cn\"\r\n target=\"_blank\" rel=\"noreferrer nofollow\">ICP备88888888</a>');

-- --------------------------------------------------------

--
-- 表的结构 `admin_front_log`
--

CREATE TABLE `admin_front_log` (
  `id` int(11) UNSIGNED NOT NULL COMMENT 'ID',
  `uid` int(11) DEFAULT NULL COMMENT '商户ID',
  `url` varchar(255) NOT NULL DEFAULT '' COMMENT '操作页面',
  `type` int(1) NOT NULL DEFAULT '0' COMMENT '日志类型',
  `desc` text COMMENT '日志内容',
  `ip` varchar(20) NOT NULL DEFAULT '' COMMENT '操作IP',
  `user_agent` text NOT NULL COMMENT 'User-Agent',
  `create_time` timestamp NULL DEFAULT NULL COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='管理员日志';

-- --------------------------------------------------------

--
-- 表的结构 `admin_permission`
--

CREATE TABLE `admin_permission` (
  `id` int(11) UNSIGNED NOT NULL COMMENT 'ID',
  `pid` int(11) NOT NULL DEFAULT '0' COMMENT '父级ID',
  `title` varchar(50) DEFAULT NULL COMMENT '名称',
  `href` varchar(50) NOT NULL COMMENT '地址',
  `icon` varchar(50) DEFAULT NULL COMMENT '图标',
  `sort` tinyint(4) NOT NULL DEFAULT '99' COMMENT '排序',
  `type` tinyint(1) DEFAULT '1' COMMENT '菜单',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='权限表';

--
-- 转存表中的数据 `admin_permission`
--

INSERT INTO `admin_permission` (`id`, `pid`, `title`, `href`, `icon`, `sort`, `type`, `status`) VALUES
(1, 0, '后台权限', '', 'layui-icon layui-icon layui-icon-username', 4, 0, 1),
(2, 1, '管理员', '/admin.admin/index', '', 1, 1, 1),
(3, 2, '新增管理员', '/admin.admin/add', '', 1, 1, 1),
(4, 2, '编辑管理员', '/admin.admin/edit', '', 1, 1, 1),
(5, 2, '修改管理员状态', '/admin.admin/status', '', 1, 1, 1),
(6, 2, '删除管理员', '/admin.admin/remove', '', 1, 1, 1),
(7, 2, '批量删除管理员', '/admin.admin/batchRemove', '', 1, 1, 1),
(8, 2, '管理员分配角色', '/admin.admin/role', '', 1, 1, 1),
(9, 2, '管理员分配直接权限', '/admin.admin/permission', '', 1, 1, 1),
(10, 2, '管理员回收站', '/admin.admin/recycle', '', 1, 1, 1),
(11, 1, '角色管理', '/admin.role/index', '', 99, 1, 1),
(12, 11, '新增角色', '/admin.role/add', '', 99, 1, 1),
(13, 11, '编辑角色', '/admin.role/edit', '', 99, 1, 1),
(14, 11, '删除角色', '/admin.role/remove', '', 99, 1, 1),
(15, 11, '角色分配权限', '/admin.role/permission', '', 99, 1, 1),
(16, 11, '角色回收站', '/admin.role/recycle', '', 99, 1, 1),
(17, 1, '菜单权限', '/admin.permission/index', '', 99, 1, 1),
(18, 17, '新增菜单', '/admin.permission/add', '', 99, 1, 1),
(19, 17, '编辑菜单', '/admin.permission/edit', '', 99, 1, 1),
(20, 17, '修改菜单状态', '/admin.permission/status', '', 99, 1, 1),
(21, 17, '删除菜单', '/admin.permission/remove', '', 99, 1, 1),
(22, 0, '系统管理', '', 'layui-icon layui-icon-set', 3, 0, 1),
(23, 22, '后台日志', '/admin.admin/log', '', 2, 1, 1),
(24, 23, '清空管理员日志', '/admin.admin/removeLog', '', 1, 1, 1),
(25, 22, '系统设置', '/config/index', '', 1, 1, 1),
(26, 22, '图片管理', '/admin.photo/index', '', 2, 1, 1),
(27, 26, '新增图片文件夹', '/admin.photo/add', '', 2, 1, 1),
(28, 26, '删除图片文件夹', '/admin.photo/del', '', 2, 1, 1),
(29, 26, '图片列表', '/admin.photo/list', '', 2, 1, 1),
(30, 26, '添加单图', '/admin.photo/addPhoto', '', 2, 1, 1),
(31, 26, '添加多图', '/admin.photo/addPhotos', '', 2, 1, 1),
(32, 26, '删除图片', '/admin.photo/remove', '', 2, 1, 1),
(33, 26, '批量删除图片', '/admin.photo/batchRemove', '', 2, 1, 1),
(34, 0, '通道管理', '', 'layui-icon layui-icon layui-icon-app', 10, 0, 1),
(36, 35, '新增通道列表', '/admin.channel/add', NULL, 99, 1, 1),
(37, 35, '修改通道列表', '/admin.channel/edit', NULL, 99, 1, 1),
(38, 35, '删除通道列表', '/admin.channel/remove', NULL, 99, 1, 1),
(39, 35, '批量删除通道列表', '/admin.channel/batchRemove', NULL, 99, 1, 1),
(40, 35, '回收站通道列表', '/admin.channel/recycle', NULL, 99, 1, 1),
(41, 34, '通道列表', '/admin.channel/index', 'layui-icon layui-icon layui-icon-fire', 97, 1, 1),
(42, 41, '新增通道列表', '/admin.channel/add', NULL, 99, 1, 1),
(43, 41, '修改通道列表', '/admin.channel/edit', NULL, 99, 1, 1),
(44, 41, '删除通道列表', '/admin.channel/remove', NULL, 99, 1, 1),
(45, 41, '批量删除通道列表', '/admin.channel/batchRemove', NULL, 99, 1, 1),
(46, 41, '回收站通道列表', '/admin.channel/recycle', NULL, 99, 1, 1),
(53, 0, '会员管理', '', 'layui-icon layui-icon-username', 10, 0, 1),
(54, 53, '余额日志', '/money.log/index', 'layui-icon layui-icon-fire', 99, 1, 1),
(55, 54, '新增余额日志', '/money.log/add', NULL, 99, 1, 1),
(56, 54, '修改余额日志', '/money.log/edit', NULL, 99, 1, 1),
(57, 54, '删除余额日志', '/money.log/remove', NULL, 99, 1, 1),
(58, 54, '批量删除余额日志', '/money.log/batchRemove', NULL, 99, 1, 1),
(59, 54, '回收站余额日志', '/money.log/recycle', NULL, 99, 1, 1),
(60, 53, '会员列表', '/ypay.user/index', 'layui-icon layui-icon layui-icon-fire', 98, 1, 1),
(61, 60, '新增会员列表', '/ypay.user/add', NULL, 99, 1, 1),
(62, 60, '修改会员列表', '/ypay.user/edit', NULL, 99, 1, 1),
(63, 60, '删除会员列表', '/ypay.user/remove', NULL, 99, 1, 1),
(64, 60, '批量删除会员列表', '/ypay.user/batchRemove', NULL, 99, 1, 1),
(65, 60, '回收站会员列表', '/ypay.user/recycle', NULL, 99, 1, 1),
(66, 53, '会员套餐', '/ypay.vip/index', 'layui-icon layui-icon-fire', 99, 1, 1),
(67, 66, '新增会员套餐', '/ypay.vip/add', NULL, 99, 1, 1),
(68, 66, '修改会员套餐', '/ypay.vip/edit', NULL, 99, 1, 1),
(69, 66, '删除会员套餐', '/ypay.vip/remove', NULL, 99, 1, 1),
(70, 66, '批量删除会员套餐', '/ypay.vip/batchRemove', NULL, 99, 1, 1),
(71, 66, '回收站会员套餐', '/ypay.vip/recycle', NULL, 99, 1, 1),
(72, 34, '账号管理', '/ypay.account/index', 'layui-icon layui-icon layui-icon-fire', 98, 1, 1),
(73, 72, '新增账号管理', '/ypay.account/add', NULL, 99, 1, 2),
(74, 72, '修改账号管理', '/ypay.account/edit', NULL, 99, 1, 1),
(75, 72, '删除账号管理', '/ypay.account/remove', NULL, 99, 1, 1),
(76, 72, '批量删除账号管理', '/ypay.account/batchRemove', NULL, 99, 1, 1),
(77, 72, '回收站账号管理', '/ypay.account/recycle', NULL, 99, 1, 2),
(78, 0, '商城管理', '', 'layui-icon layui-icon-rmb', 10, 0, 1),
(79, 78, '订单记录', '/ypay.order/index', 'layui-icon layui-icon layui-icon-fire', 3, 1, 1),
(80, 79, '新增订单记录', '/ypay.order/add', NULL, 99, 1, 1),
(81, 79, '修改订单记录', '/ypay.order/edit', NULL, 99, 1, 1),
(82, 79, '删除订单记录', '/ypay.order/remove', NULL, 99, 1, 1),
(83, 79, '批量删除订单记录', '/ypay.order/batchRemove', NULL, 99, 1, 1),
(84, 79, '回收站订单记录', '/ypay.order/recycle', NULL, 99, 1, 2),
(85, 78, '收益记录', '/ypay.recharge/index', 'layui-icon layui-icon layui-icon-fire', 2, 1, 1),
(86, 85, '新增充值记录', '/ypay.recharge/add', NULL, 99, 1, 1),
(87, 85, '修改充值记录', '/ypay.recharge/edit', NULL, 99, 1, 1),
(88, 85, '删除充值记录', '/ypay.recharge/remove', NULL, 99, 1, 1),
(89, 85, '批量删除充值记录', '/ypay.recharge/batchRemove', NULL, 99, 1, 1),
(90, 85, '回收站充值记录', '/ypay.recharge/recycle', NULL, 99, 1, 1),
(91, 0, '安全管理', '', 'layui-icon layui-icon-diamond', 10, 0, 1),
(92, 91, '风控记录', '/ypay.risk/index', 'layui-icon layui-icon-fire', 99, 1, 1),
(93, 92, '新增风控记录', '/ypay.risk/add', NULL, 99, 1, 1),
(94, 92, '修改风控记录', '/ypay.risk/edit', NULL, 99, 1, 1),
(95, 92, '删除风控记录', '/ypay.risk/remove', NULL, 99, 1, 1),
(96, 92, '批量删除风控记录', '/ypay.risk/batchRemove', NULL, 99, 1, 1),
(97, 92, '回收站风控记录', '/ypay.risk/recycle', NULL, 99, 1, 1),
(98, 0, '下载管理', '', 'layui-icon layui-icon-download-circle', 10, 0, 1),
(99, 98, '插件下载', '/ypay.plug/index', 'layui-icon layui-icon-fire', 99, 1, 1),
(100, 99, '新增插件下载', '/ypay.plug/add', NULL, 99, 1, 1),
(101, 99, '修改插件下载', '/ypay.plug/edit', NULL, 99, 1, 1),
(102, 99, '删除插件下载', '/ypay.plug/remove', NULL, 99, 1, 1),
(103, 99, '批量删除插件下载', '/ypay.plug/batchRemove', NULL, 99, 1, 1),
(104, 99, '回收站插件下载', '/ypay.plug/recycle', NULL, 99, 1, 1),
(105, 22, '导航管理', '/ypay.navs/index', 'layui-icon layui-icon-fire', 99, 1, 1),
(106, 105, '新增导航管理', '/ypay.navs/add', NULL, 99, 1, 1),
(107, 105, '修改导航管理', '/ypay.navs/edit', NULL, 99, 1, 1),
(108, 105, '删除导航管理', '/ypay.navs/remove', NULL, 99, 1, 1),
(109, 105, '批量删除导航管理', '/ypay.navs/batchRemove', NULL, 99, 1, 1),
(110, 105, '回收站导航管理', '/ypay.navs/recycle', NULL, 99, 1, 1),
(111, 22, '公告管理', '/ypay.news/index', 'layui-icon layui-icon-fire', 99, 1, 1),
(112, 111, '新增公告管理', '/ypay.news/add', NULL, 99, 1, 1),
(113, 111, '修改公告管理', '/ypay.news/edit', NULL, 99, 1, 1),
(114, 111, '删除公告管理', '/ypay.news/remove', NULL, 99, 1, 1),
(115, 111, '批量删除公告管理', '/ypay.news/batchRemove', NULL, 99, 1, 1),
(116, 111, '回收站公告管理', '/ypay.news/recycle', NULL, 99, 1, 1),
(117, 0, '控制端', '/index', 'layui-icon layui-icon layui-icon layui-icon-home', 1, 1, 1),
(119, 53, '行为日志', '/admin.front_log/index', 'layui-icon layui-icon-fire', 99, 1, 1),
(120, 119, '新增登录日志', '/admin.front_log/add', NULL, 99, 1, 1),
(121, 119, '修改登录日志', '/admin.front_log/edit', NULL, 99, 1, 1),
(122, 119, '删除登录日志', '/admin.front_log/remove', NULL, 99, 1, 1),
(123, 119, '批量删除登录日志', '/admin.front_log/batchRemove', NULL, 99, 1, 1),
(124, 119, '回收站登录日志', '/admin.front_log/recycle', NULL, 99, 1, 1),
(127, 126, '新增云端地域', '/ypay.cloud/add', NULL, 99, 1, 1),
(128, 126, '修改云端地域', '/ypay.cloud/edit', NULL, 99, 1, 1),
(129, 126, '删除云端地域', '/ypay.cloud/remove', NULL, 99, 1, 1),
(130, 126, '批量删除云端地域', '/ypay.cloud/batchRemove', NULL, 99, 1, 1),
(138, 78, '商城总览', '/ypay.shop/index', 'layui-icon layui-inline layui-iconpicker-title', 1, 1, 1),
(139, 78, '后台充值', '/ypay.shop/plus', 'layui-icon layui-icon layui-icon layui-icon-face-s', 4, 1, 1),
(140, 22, '支付配置', '/ypay.paylist/index', 'layui-icon layui-icon layui-icon layui-icon layui-', 10, 1, 1),
(143, 78, '数据清理', '/ypay.shop/clear', 'layui-icon layui-icon layui-icon-face-smile', 99, 1, 1),
(144, 22, '首页模板', '/ypay.home/index', 'layui-icon layui-icon layui-icon layui-icon-face-s', 99, 1, 1),
(147, 53, '邮件发信', '/ypay.user/email', 'layui-icon layui-icon-face-smile', 99, 1, 1);

-- --------------------------------------------------------

--
-- 表的结构 `admin_photo`
--

CREATE TABLE `admin_photo` (
  `id` int(11) UNSIGNED NOT NULL COMMENT 'ID',
  `name` varchar(50) NOT NULL COMMENT '文件名称',
  `href` varchar(255) DEFAULT NULL COMMENT '文件路径',
  `path` varchar(30) DEFAULT NULL COMMENT '路径',
  `mime` varchar(50) NOT NULL COMMENT 'mime类型',
  `size` varchar(30) NOT NULL COMMENT '大小',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1本地2阿里云3七牛云',
  `ext` varchar(10) DEFAULT NULL COMMENT '文件后缀',
  `create_time` timestamp NULL DEFAULT NULL COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='图片表';

--
-- 转存表中的数据 `admin_photo`
--

INSERT INTO `admin_photo` (`id`, `name`, `href`, `path`, `mime`, `size`, `type`, `ext`, `create_time`) VALUES
(1, '1613564243-bf130567ccd7e68.png', '/upload/images/20220825/cdacdbbf182b79cf9303bf4767273094.png', 'images', 'image/png', '54518', 1, 'png', '2022-08-24 09:55:22'),
(54, '4e02aa6030b0c7fd1b17d6588db6c73d.jpg', '/upload/images/20240427/db8d3c42cdabfd7145a4b6afa300d905.jpg', 'images', 'image/jpeg', '1013512', 1, 'jpg', '2024-04-26 18:33:58');

-- --------------------------------------------------------

--
-- 表的结构 `admin_role`
--

CREATE TABLE `admin_role` (
  `id` int(11) UNSIGNED NOT NULL COMMENT 'ID',
  `name` varchar(30) DEFAULT NULL COMMENT '名称',
  `desc` varchar(100) DEFAULT NULL COMMENT '描述',
  `create_time` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `delete_time` timestamp NULL DEFAULT NULL COMMENT '删除时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='角色表';

--
-- 转存表中的数据 `admin_role`
--

INSERT INTO `admin_role` (`id`, `name`, `desc`, `create_time`, `update_time`, `delete_time`) VALUES
(1, '超级管理员', '拥有所有管理权限', '2020-08-31 03:01:34', '2020-08-31 03:01:34', NULL);

-- --------------------------------------------------------

--
-- 表的结构 `admin_role_permission`
--

CREATE TABLE `admin_role_permission` (
  `id` int(11) UNSIGNED NOT NULL COMMENT 'ID',
  `role_id` int(11) DEFAULT NULL COMMENT '角色ID',
  `permission_id` int(11) DEFAULT NULL COMMENT '权限ID'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='角色-权限中间表';

-- --------------------------------------------------------

--
-- 表的结构 `money_log`
--

CREATE TABLE `money_log` (
  `id` int(11) UNSIGNED NOT NULL COMMENT 'id',
  `user_id` int(11) DEFAULT NULL COMMENT '会员ID',
  `type` int(1) DEFAULT NULL COMMENT '日志类型',
  `money` decimal(10,3) DEFAULT NULL COMMENT '变更金额',
  `beforemoney` decimal(10,3) DEFAULT NULL COMMENT '变更前金额',
  `after` decimal(10,3) DEFAULT NULL COMMENT '变更后金额',
  `create_time` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `memo` varchar(50) DEFAULT NULL COMMENT '备注'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='余额日志';

-- --------------------------------------------------------

--
-- 表的结构 `ypay_account`
--

CREATE TABLE `ypay_account` (
  `id` int(11) UNSIGNED NOT NULL COMMENT 'ID',
  `code` varchar(50) DEFAULT NULL COMMENT '通道标识',
  `type` varchar(50) DEFAULT NULL COMMENT '通道类型',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '会员ID',
  `qr_url` varchar(2500) DEFAULT NULL COMMENT '二维码地址',
  `wxname` varchar(50) DEFAULT NULL COMMENT '微信昵称',
  `zfb_pid` varchar(50) DEFAULT NULL COMMENT '支付宝PID',
  `wx_guid` varchar(50) DEFAULT NULL COMMENT '微信GUID',
  `qq` varchar(50) DEFAULT NULL COMMENT 'QQ',
  `status` int(11) NOT NULL DEFAULT '0' COMMENT '状态',
  `is_status` int(11) NOT NULL DEFAULT '1' COMMENT '是否启用',
  `create_time` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `succcount` int(11) NOT NULL DEFAULT '0' COMMENT '收款笔数',
  `succprice` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '收款金额',
  `memo` varchar(50) DEFAULT NULL COMMENT '备注',
  `endtime` int(11) DEFAULT NULL COMMENT '结束时间戳',
  `cookie` text COMMENT 'CK信息',
  `tong_time` int(11) DEFAULT NULL COMMENT '通用通道时间戳',
  `allmaxcount` int(11) NOT NULL DEFAULT '0' COMMENT '上限笔数',
  `allmaxmoney` varchar(50) DEFAULT NULL COMMENT '上限金额',
  `daymaxcount` int(11) NOT NULL DEFAULT '0' COMMENT '日上限笔数',
  `daymaxmoney` varchar(50) DEFAULT NULL COMMENT '日上限金额',
  `remark` varchar(225) DEFAULT NULL COMMENT '备用字段',
  `money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '支付宝余额'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='账号管理';

-- --------------------------------------------------------

--
-- 表的结构 `ypay_navs`
--

CREATE TABLE `ypay_navs` (
  `id` int(11) UNSIGNED NOT NULL COMMENT 'id',
  `name` varchar(50) DEFAULT NULL COMMENT '导航名称',
  `url` text COMMENT '导航地址',
  `is_target` int(11) NOT NULL DEFAULT '0' COMMENT '是否跳转',
  `status` int(11) NOT NULL DEFAULT '1' COMMENT '状态',
  `create_time` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='导航管理';

--
-- 转存表中的数据 `ypay_navs`
--

INSERT INTO `ypay_navs` (`id`, `name`, `url`, `is_target`, `status`, `create_time`, `sort`) VALUES
(1, '首页', '/', 0, 1, '2022-06-09 14:52:12', 1),
(2, '开发文档', '/doc', 0, 1, '2022-06-09 14:52:58', 2),
(3, '支付测试', '/demo', 0, 1, '2022-06-09 14:53:29', 3),
(4, '公告中心', '/News/Index', 0, 1, '2022-06-09 14:53:53', 4);

-- --------------------------------------------------------

--
-- 表的结构 `ypay_news`
--

CREATE TABLE `ypay_news` (
  `id` int(11) UNSIGNED NOT NULL COMMENT 'id',
  `type` int(11) NOT NULL DEFAULT '1' COMMENT '公告类型',
  `title` varchar(2500) DEFAULT NULL COMMENT '公告标题',
  `color` varchar(50) DEFAULT NULL COMMENT '标题颜色',
  `content` longtext COMMENT '公告内容',
  `status` int(11) NOT NULL DEFAULT '1' COMMENT '状态',
  `create_time` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT NULL COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='公告管理';

-- --------------------------------------------------------

--
-- 表的结构 `ypay_order`
--

CREATE TABLE `ypay_order` (
  `id` int(11) UNSIGNED NOT NULL COMMENT 'ID',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '商品名称',
  `sitename` varchar(50) DEFAULT NULL COMMENT '网站名称',
  `type` varchar(50) DEFAULT NULL COMMENT '支付类型',
  `account_id` int(11) DEFAULT NULL COMMENT '账号ID',
  `trade_no` varchar(50) DEFAULT NULL COMMENT '商户单号',
  `out_trade_no` varchar(50) DEFAULT NULL COMMENT '本地单号',
  `alipay_order_no` varchar(255) DEFAULT NULL COMMENT '支付宝商户订单号',
  `notify_url` text COMMENT '异步通知地址',
  `return_url` text COMMENT '同步地址',
  `user_id` int(11) DEFAULT NULL COMMENT '会员ID',
  `money` decimal(10,2) DEFAULT NULL COMMENT '金额',
  `truemoney` decimal(10,2) DEFAULT NULL COMMENT '实付金额',
  `feilvmoney` decimal(10,3) DEFAULT NULL COMMENT '费率金额',
  `status` int(11) NOT NULL DEFAULT '0' COMMENT '状态',
  `ip` varchar(50) DEFAULT NULL COMMENT 'IP地址',
  `end_time` timestamp NULL DEFAULT NULL COMMENT '支付时间',
  `out_time` int(11) DEFAULT NULL COMMENT '有效时间',
  `qrcode` text COMMENT '二维码信息',
  `h5_qrurl` text COMMENT 'H5链接',
  `create_time` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `api_memo` text,
  `pla_type` int(11) NOT NULL DEFAULT '1',
  `is_order_tips` int(1) DEFAULT '0' COMMENT '是否邮箱通知过'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='订单记录';

-- --------------------------------------------------------

--
-- 表的结构 `ypay_paylist`
--

CREATE TABLE `ypay_paylist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `type` varchar(255) DEFAULT NULL COMMENT '支付类型',
  `status` int(11) DEFAULT '1' COMMENT '状态',
  `name` varchar(255) DEFAULT NULL COMMENT '支付名称',
  `url` varchar(255) DEFAULT NULL COMMENT '网关地址',
  `pid` text COMMENT '对接PID',
  `key` text COMMENT '对接密钥',
  `other` text COMMENT '其他/公钥等',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

--
-- 表的结构 `ypay_plug`
--

CREATE TABLE `ypay_plug` (
  `id` int(11) UNSIGNED NOT NULL COMMENT 'id',
  `name` varchar(50) DEFAULT NULL COMMENT '插件名称',
  `downurl` text COMMENT '下载地址',
  `introduce` text COMMENT '插件介绍',
  `status` int(11) NOT NULL DEFAULT '1' COMMENT '显示状态',
  `create_time` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT NULL COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='插件下载';

-- --------------------------------------------------------

--
-- 表的结构 `ypay_proxy`
--

CREATE TABLE `ypay_proxy` (
  `id` int(11) UNSIGNED NOT NULL COMMENT 'id',
  `name` varchar(50) DEFAULT NULL COMMENT '地域名称',
  `sort` int(25) DEFAULT '0' COMMENT '排序',
  `address` varchar(225) DEFAULT NULL COMMENT 'IP地址',
  `prot` varchar(50) DEFAULT NULL COMMENT '端口',
  `user` varchar(50) DEFAULT NULL COMMENT '账号',
  `pass` varchar(50) DEFAULT NULL COMMENT '密码',
  `status` int(11) DEFAULT '1' COMMENT '状态',
  `create_time` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT NULL COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='地域代理';

-- --------------------------------------------------------

--
-- 表的结构 `ypay_recharge`
--

CREATE TABLE `ypay_recharge` (
  `id` int(11) UNSIGNED NOT NULL COMMENT 'ID',
  `type` varchar(50) DEFAULT NULL COMMENT '支付类型',
  `rtype` int(1) DEFAULT '0' COMMENT '收益类型',
  `out_trade_no` varchar(225) DEFAULT NULL COMMENT '本地订单',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '会员ID',
  `money` decimal(10,2) DEFAULT '0.00' COMMENT '订单金额',
  `qrcode` varchar(50) DEFAULT NULL COMMENT '二维码地址',
  `status` int(11) NOT NULL DEFAULT '0' COMMENT '订单状态',
  `create_time` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `end_time` timestamp NULL DEFAULT NULL COMMENT '支付时间',
  `update_time` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `out_time` int(11) DEFAULT NULL COMMENT '有效时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='充值记录';

-- --------------------------------------------------------

--
-- 表的结构 `ypay_risk`
--

CREATE TABLE `ypay_risk` (
  `id` int(11) UNSIGNED NOT NULL COMMENT 'id',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '会员ID',
  `name` varchar(225) DEFAULT NULL COMMENT '商品名称',
  `url` varchar(2500) DEFAULT NULL COMMENT '来源地址',
  `create_time` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT NULL COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='风控记录';

-- --------------------------------------------------------

--
-- 表的结构 `ypay_user`
--

CREATE TABLE `ypay_user` (
  `id` int(11) UNSIGNED NOT NULL COMMENT '会员ID',
  `username` varchar(50) DEFAULT NULL COMMENT '会员账号',
  `password` varchar(50) DEFAULT NULL COMMENT '会员密码',
  `superior_id` int(11) DEFAULT NULL COMMENT '上级id',
  `salt` varchar(50) DEFAULT NULL COMMENT '密码盐',
  `email` varchar(50) DEFAULT NULL COMMENT '邮箱',
  `mobile` varchar(50) DEFAULT NULL COMMENT '手机号',
  `wxpusher_uid` varchar(50) DEFAULT NULL COMMENT 'WxPusher_UID',
  `money` decimal(10,2) DEFAULT '0.00' COMMENT '余额',
  `user_key` varchar(50) DEFAULT NULL COMMENT '密钥信息',
  `vip_id` int(15) DEFAULT NULL COMMENT 'VIP套餐ID',
  `vip_time` datetime DEFAULT NULL COMMENT '套餐时间',
  `feilv` varchar(50) DEFAULT NULL COMMENT '费率',
  `is_bindqq` int(11) NOT NULL DEFAULT '0' COMMENT '是否绑定QQ',
  `qq_sid` varchar(225) DEFAULT NULL COMMENT 'OpenID',
  `is_bindwx` int(11) NOT NULL DEFAULT '0' COMMENT '是否绑定微信',
  `wx_sid` varchar(225) DEFAULT NULL COMMENT 'VXOpenID',
  `create_time` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `token` varchar(225) DEFAULT NULL COMMENT 'Token',
  `is_frozen` int(1) NOT NULL DEFAULT '0' COMMENT '是否冻结账号',
  `frozen_reason` varchar(255) DEFAULT NULL COMMENT '封禁理由',
  `remarks` varchar(255) DEFAULT NULL COMMENT '备注'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='会员列表';

-- --------------------------------------------------------

--
-- 表的结构 `ypay_userbasic`
--

CREATE TABLE `ypay_userbasic` (
  `id` int(255) NOT NULL,
  `user_id` int(255) NOT NULL COMMENT '用户ID',
  `timeout_method` int(1) NOT NULL DEFAULT '2' COMMENT '超时跳转方式',
  `timeout_url` varchar(255) DEFAULT '/' COMMENT '超时地址',
  `timeout_time` varchar(255) DEFAULT '180' COMMENT '超时时间',
  `loginfailure` int(10) DEFAULT '0' COMMENT '登录失败次数',
  `console_notity` varchar(255) DEFAULT NULL COMMENT '收银提示',
  `console_temp` varchar(50) DEFAULT 'console' COMMENT '收银模板',
  `yuyin_tips` int(1) DEFAULT '0' COMMENT '语音提示',
  `login_tips` varchar(20) DEFAULT '0' COMMENT '登录提醒',
  `is_money_tips` varchar(20) DEFAULT '0' COMMENT '余额不足提示',
  `money_tips` varchar(50) DEFAULT '0' COMMENT '余额提醒不足金额',
  `appkey` varchar(50) DEFAULT NULL COMMENT '通讯密钥',
  `order_tips` varchar(20) DEFAULT '0' COMMENT '订单提醒',
  `lose_tips` varchar(20) DEFAULT '0' COMMENT '通道掉线提醒',
  `is_payPopUp` int(1) DEFAULT '0' COMMENT '支付页弹窗',
  `is_rate` int(1) DEFAULT '0' COMMENT '费率承担',
  `cashierMode` int(3) DEFAULT '1' COMMENT '收银模式',
  `channelMode` int(10) DEFAULT '1' COMMENT '跳转模式',
  `floating_amount` varchar(255) DEFAULT '0.01,0.02,0.03,0.04,0.05,0.06,0.07,0.08,0.09,0.1' COMMENT '浮动金额',
  `is_jump` int(1) DEFAULT '1' COMMENT '是否跳转'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `ypay_vip`
--

CREATE TABLE `ypay_vip` (
  `id` int(11) UNSIGNED NOT NULL COMMENT 'id',
  `name` varchar(50) DEFAULT NULL COMMENT '套餐名称',
  `feilv` varchar(50) DEFAULT NULL COMMENT '套餐费率',
  `money` decimal(10,2) DEFAULT NULL COMMENT '套餐金额',
  `viptime` int(11) NOT NULL DEFAULT '0' COMMENT '套餐时间',
  `status` int(11) NOT NULL DEFAULT '1' COMMENT '状态',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `is_profiteer` int(1) DEFAULT NULL COMMENT '是否开启订单加费',
  `create_time` timestamp NULL DEFAULT NULL COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='会员套餐';


--
-- 触发器 `ypay_user`
--
CREATE TRIGGER `basic_Insert` AFTER INSERT ON `ypay_user` FOR EACH ROW  INSERT INTO ypay_userbasic (user_id) VALUES (NEW.id);
CREATE TRIGGER `basic_Delete` AFTER DELETE ON `ypay_user` FOR EACH ROW  DELETE FROM ypay_userbasic WHERE user_id = OLD.id;

--
-- 转储表的索引
--

--
-- 表的索引 `admin_admin`
--
ALTER TABLE `admin_admin`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `admin_admin_log`
--
ALTER TABLE `admin_admin_log`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `admin_admin_permission`
--
ALTER TABLE `admin_admin_permission`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `admin_admin_role`
--
ALTER TABLE `admin_admin_role`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `admin_channel`
--
ALTER TABLE `admin_channel`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `admin_config`
--
ALTER TABLE `admin_config`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `admin_front_log`
--
ALTER TABLE `admin_front_log`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `admin_permission`
--
ALTER TABLE `admin_permission`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pid` (`pid`);

--
-- 表的索引 `admin_photo`
--
ALTER TABLE `admin_photo`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `admin_role`
--
ALTER TABLE `admin_role`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `admin_role_permission`
--
ALTER TABLE `admin_role_permission`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `money_log`
--
ALTER TABLE `money_log`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `ypay_account`
--
ALTER TABLE `ypay_account`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `ypay_navs`
--
ALTER TABLE `ypay_navs`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `ypay_news`
--
ALTER TABLE `ypay_news`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `ypay_order`
--
ALTER TABLE `ypay_order`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `ypay_paylist`
--
ALTER TABLE `ypay_paylist`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `ypay_plug`
--
ALTER TABLE `ypay_plug`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `ypay_proxy`
--
ALTER TABLE `ypay_proxy`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `ypay_recharge`
--
ALTER TABLE `ypay_recharge`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `ypay_risk`
--
ALTER TABLE `ypay_risk`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `ypay_user`
--
ALTER TABLE `ypay_user`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `ypay_userbasic`
--
ALTER TABLE `ypay_userbasic`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `ypay_vip`
--
ALTER TABLE `ypay_vip`
  ADD PRIMARY KEY (`id`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `admin_admin`
--
ALTER TABLE `admin_admin`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID', AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `admin_admin_log`
--
ALTER TABLE `admin_admin_log`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `admin_admin_permission`
--
ALTER TABLE `admin_admin_permission`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `admin_admin_role`
--
ALTER TABLE `admin_admin_role`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `admin_channel`
--
ALTER TABLE `admin_channel`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'id', AUTO_INCREMENT=34;

--
-- 使用表AUTO_INCREMENT `admin_config`
--
ALTER TABLE `admin_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=238;

--
-- 使用表AUTO_INCREMENT `admin_front_log`
--
ALTER TABLE `admin_front_log`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `admin_permission`
--
ALTER TABLE `admin_permission`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID', AUTO_INCREMENT=148;

--
-- 使用表AUTO_INCREMENT `admin_photo`
--
ALTER TABLE `admin_photo`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID', AUTO_INCREMENT=55;

--
-- 使用表AUTO_INCREMENT `admin_role`
--
ALTER TABLE `admin_role`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID', AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `admin_role_permission`
--
ALTER TABLE `admin_role_permission`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `money_log`
--
ALTER TABLE `money_log`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'id';

--
-- 使用表AUTO_INCREMENT `ypay_account`
--
ALTER TABLE `ypay_account`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `ypay_navs`
--
ALTER TABLE `ypay_navs`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'id', AUTO_INCREMENT=5;

--
-- 使用表AUTO_INCREMENT `ypay_news`
--
ALTER TABLE `ypay_news`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'id';

--
-- 使用表AUTO_INCREMENT `ypay_order`
--
ALTER TABLE `ypay_order`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `ypay_paylist`
--
ALTER TABLE `ypay_paylist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `ypay_plug`
--
ALTER TABLE `ypay_plug`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'id';

--
-- 使用表AUTO_INCREMENT `ypay_proxy`
--
ALTER TABLE `ypay_proxy`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'id';

--
-- 使用表AUTO_INCREMENT `ypay_recharge`
--
ALTER TABLE `ypay_recharge`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID';

--
-- 使用表AUTO_INCREMENT `ypay_risk`
--
ALTER TABLE `ypay_risk`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'id', AUTO_INCREMENT=5;

--
-- 使用表AUTO_INCREMENT `ypay_user`
--
ALTER TABLE `ypay_user`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '会员ID';

--
-- 使用表AUTO_INCREMENT `ypay_userbasic`
--
ALTER TABLE `ypay_userbasic`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `ypay_vip`
--
ALTER TABLE `ypay_vip`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'id';
COMMIT;