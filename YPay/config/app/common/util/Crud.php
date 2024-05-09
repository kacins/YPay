<?php
declare (strict_types = 1);

namespace app\common\util;

use think\facade\Request;
use think\facade\Db;

class Crud
{
    //验证
    protected static $check = ['admin_admin','admin_admin_log','admin_admin_permission','admin_admin_role','admin_permission','admin_photo','admin_role','admin_role_permission'];
    //参数
    protected static $data;

    // 获取所有表
    public static function getTable()
    {
        foreach (Db::getTables() as $k =>$v) {
            $list[] = ['name'=>$v];
        }
        return ['code'=>0,'data'=>$list];
    }

    // 添加
    public static function goAdd()
    {
        if (Request::isPost()){
            $data = Request::post();
            //数据验证
            if (!preg_match('/^[a-z]+_[a-z]+$/i',$data['name'])) return ['code' => 201, 'msg' => '表名格式不正确'];
            try {
                Db::execute('CREATE TABLE '.config('database.connections.mysql.prefix').$data['name'].'(
                    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT "id",
                    `create_time` timestamp NULL DEFAULT NULL COMMENT "创建时间",
                    `update_time` timestamp NULL DEFAULT NULL COMMENT "更新时间",
                    `delete_time` timestamp NULL DEFAULT NULL COMMENT "删除时间",
                    PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 COMMENT="'.$data['desc'].'";
                    ');
            }catch (\Exception $e){
                return ['code'=>201,'msg'=>$e->getMessage()];
            }
        }
    }

    // 获取Crud变量
    public static function getCrud($name)
    {
        return [
            'data' => Db::getFields($name),
            'permissions' => get_tree((new \app\common\model\AdminPermission)->order('sort','asc')->select()->toArray()),
            'desc' => Db::query('SELECT TABLE_COMMENT FROM information_schema.TABLES WHERE TABLE_NAME = "' . $name . '"')[0]['TABLE_COMMENT']
        ];
    }

        // 获取Crud变量
    public static function goRemove($name)
    {
        if (Request::isPost()){
            $type = Request::param('type');
            //验证
            if (in_array(substr($name,strlen(config('database.connections.mysql.prefix'))),self::$check)) return ['code'=>201,'msg'=>'默认字段禁止操作'];
            Db::query('drop table '.$name);
            if(Request::param('type')){
                try {
                    $data['table'] = substr($name,strlen(config('database.connections.mysql.prefix')));
                    //表名转驼峰
                    $data['table_hump'] = underline_hump($data['table']);
                    //左
                    $data['left'] = strstr($data['table'], '_',true);
                    //右
                    $data['right'] = substr($data['table'],strlen($data['left'])+1);
                    //右转驼峰
                    $data['right_hump'] = underline_hump($data['right']);
                    $commom = root_path().'app'.DS.'common'.DS;
                    // 控制器
                    $controller = app_path().'controller'.DS.$data['left'].DS.$data['right_hump'].'.php';
                    if (file_exists($controller)) unlink($controller);
                    // 模型
                    $model = $commom.DS.'model'.DS.$data['table_hump'].'.php';
                    if (file_exists($model)) unlink($model);
                    // 服务
                    $model = $commom.DS.'service'.DS.$data['table_hump'].'.php';
                    if (file_exists($model)) unlink($model);
                    // 验证器
                    $model = $commom.DS.'validate'.DS.$data['table_hump'].'.php';
                    if (file_exists($model)) unlink($model);
                    //删除视图目录
                    $view = root_path().'view'.DS.'admin'.DS.$data['left'].DS.$data['right'];
                    if (file_exists($view)) delete_dir($view);
                    //删除菜单
                    (new \app\common\model\AdminPermission)->where('href', 'like', "%" . $data['left'].'.'.$data['right'] . "%")->delete();rm();
                }catch (\Exception $e){
                    return ['msg'=>'删除失败','code'=>'201'];
                }
            }
        }
    }


    // CRUD生成
    public static function goCrud($name)
    {
        if (Request::isPost()){
            //参数
            self::$data = Request::post();
            //去除前缀表名
            self::$data['table'] = substr($name,strlen(config('database.connections.mysql.prefix')));
            //验证字段
            if (in_array(self::$data['table'],self::$check)) return ['code'=>201,'msg'=>'默认字段禁止操作'];
            //表名转驼峰
            self::$data['table_hump'] = underline_hump(self::$data['table']);
            //左
            self::$data['left'] = strstr(self::$data['table'], '_',true);
            //右
            self::$data['right'] = substr(self::$data['table'],strlen(self::$data['left'])+1);
            //右转驼峰
            self::$data['right_hump'] = underline_hump(self::$data['right']);
            //构造选中参数补全
            for ($i=0; $i < count(self::$data['name']); $i++) { 
                self::getNull($i);self::getList($i);self::getSearch($i);self::getForm($i);self::getNull($i);
                if (self::$data['name'][$i] == 'delete_time'){
                    self::$data['model_del'] = ' protected $deleteTime = "delete_time";';
                }else{
                    self::$data['model_del'] = ' protected $deleteTime = false;';
                }
            }
            //路径
            $tpl = root_path().'extend'.DS.'tpl'.DS;
            $commom = root_path().'app'.DS.'common'.DS;
            $view = root_path().'view'.DS.'admin'.DS.self::$data['left'].DS.self::$data['right'].DS;
            $crud = [
                app_path().'controller'.DS.self::$data['left'].DS.self::$data['right_hump'].'.php' => self::getController($tpl.'controller.tpl'),
                $commom.'model'.DS.self::$data['table_hump'].'.php' => self::getModel($tpl.'model.tpl'),
                $commom.'service'.DS.self::$data['table_hump'].'.php' => self::getService($tpl.'service.tpl'),
                $commom.'validate'.DS.self::$data['table_hump'].'.php' => self::getValidate($tpl.'validate.tpl'),
                $view.'index.html' => self::getIndex($tpl.'index.tpl'),
                $view.'add.html' => self::getAdd($tpl.'add.tpl'),
                $view.'edit.html' => self::getAdd($tpl.'edit.tpl'),
                $view.'recycle.html' => self::getRecycle($tpl.'recycle.tpl'),
            ];
            foreach ($crud as $k=>$v) {
                @mkdir(dirname($k), 0755, true);
                @file_put_contents($k, $v);
            }
            //添加菜单
            if(self::$data['menu']!='') (new \app\common\service\AdminPermission)->goMenu(self::$data);
        }
    }

    // 控制器
    public static function getController($tpl)
    {
        return str_replace(['{{$left}}','{{$right_hump}}','{{$table_hump}}'],
        [self::$data['left'],self::$data['right_hump'],self::$data['table_hump']],file_get_contents($tpl));
    }

    // 模型
    public static function getModel($tpl)
    {
        return str_replace(['{{$table_hump}}','{{$model_search}}','{{$model_del}}'],
        [self::$data['table_hump'],implode("",self::$data['model_search']??[]),self::$data['model_del']??''],file_get_contents($tpl));
    }

    // 服务
    public static function getService($tpl)
    {
        return str_replace(['{{$table_hump}}','{{$model_search}}'],
        [self::$data['table_hump'],implode("",self::$data['model_search']??[])],file_get_contents($tpl));
    }

    // 验证
    public static function getValidate($tpl)
    {
        return str_replace(['{{$table_hump}}','{{$validate_rule}}','{{$validate_msg}}','{{$validate_scene}}'],
        [self::$data['table_hump'],implode("",self::$data['validate_rule']??[]),
        implode("",self::$data['validate_msg']??[]),implode("",self::$data['validate_scene']??[])],
        file_get_contents($tpl));
    }

    // 列表页面
    public static function getIndex($tpl)
    {
        return str_replace(['{{$ename}}','{{$table_hump}}','{{$left}}','{{$right}}','{{$index_search}}','{{$index_list}}','{{$index_status}}','{{$index_status_js}}','{{$js_search}}'],
        [self::$data['ename'],self::$data['table_hump'],self::$data['left'], self::$data['right'],implode("",self::$data['index_search']??[]),implode("",self::$data['index_list']??[]),
        implode("",self::$data['index_status']??[]),implode("",self::$data['index_status_js']??[]),implode("",self::$data['js_search']??[])],
        file_get_contents($tpl));
    }

    // 添加页面
    public static function getAdd($tpl)
    {
        return str_replace(['{{$html_form}}','{{$html_js}}','{{$html_js_data}}'],
        [implode("",self::$data['html_form']??[]),implode("",self::$data['html_js']??[]),implode("",self::$data['html_js_data']??[])],
        file_get_contents($tpl));
    }

    // 编辑页面
    public static function getEdit($tpl)
    {
        return str_replace(['{{$ename}}','{{$table_hump}}','{{$left}}','{{$right}}','{{$index_search}}','{{$index_list}}','{{$index_status}}','{{$index_status_js}}'],
        [self::$data['ename'],self::$data['table_hump'],self::$data['left'], self::$data['right'],implode("",self::$data['index_search']??[]),implode("",self::$data['index_list']??[]),
        implode("",self::$data['index_status']??[]),implode("",self::$data['index_status_js']??[])],
        file_get_contents($tpl));
    }

    // 回收站页面
    public static function getRecycle($tpl)
    {
        return str_replace(['{{$ename}}','{{$table_hump}}','{{$left}}','{{$right}}','{{$index_search}}','{{$index_list}}','{{$js_search}}'],
        [self::$data['ename'],self::$data['table_hump'],self::$data['left'], self::$data['right'],implode("",self::$data['index_search']??[]),implode("",self::$data['index_list']??[]),
        implode("",self::$data['js_search']??[])],
        file_get_contents($tpl));
    }

   // 列表处理
   public static function getList($i)
   { 
       if(isset(self::$data['list'][$i])){
           // 开关
           if(self::$data['formType'][$i]=="4"){
               self::$data['index_list'][$i] = '{
                       field: "'.self::$data['name'][$i].'",
                       title: "'.self::$data['desc'][$i].'",
                       unresize: "true",
                       align: "center",
                       templet:"#'.self::$data['name'][$i].'"
                   }, ';

               self::$data['index_status'][$i] ='
               <script type="text/html" id="'.self::$data['name'][$i].'">
                   <input type="checkbox" name="'.self::$data['name'][$i].'" value="{{d.id}}" lay-skin="switch" lay-text="启用|禁用" lay-filter="'.self::$data['name'][$i].'" {{# if(d.'.self::$data['name'][$i].'==1){ }} checked {{# } }}>
               </script>';

               self::$data['index_status_js'][$i] ='
               form.on("switch('.self::$data['name'][$i].')", function(data) {
                   var status = data.elem.checked?1:2;
                   var id = this.value;
                   var load = layer.load();
                   $.post(MODULE_PATH + "status",{'.self::$data['name'][$i].':status,id:id},function (res) {
                       layer.close(load);
                       //判断有没有权限
                       if(res && res.code==999){
                           layer.msg(res.msg, {
                               icon: 5,
                               time: 2000, 
                           })
                           return false;
                       }else if (res.code==200){
                           layer.msg(res.msg,{icon:1,time:1500})
                       } else {
                           layer.msg(res.msg,{icon:2,time:1500},function () {
                               $(data.elem).prop("checked",!$(data.elem).prop("checked"));
                               form.render()
                           })
                       }
                   })
               });';
           }else{
               self::$data['index_list'][$i] = '{
                       field: "'.self::$data['name'][$i].'",
                       title: "'.self::$data['desc'][$i].'",
                       unresize: "true",
                       align: "center"
                   }, ';
           }
       }
   }
   
   // 搜索处理
   public static function getSearch($i)
   { 
       if(isset(self::$data['search'][$i])){
           if(strstr(self::$data['name'][$i],"time")){
               //模型处理
               self::$data['model_search'][$i] = '
               //按'.self::$data['desc'][$i].'查找
               $start = input("get.'.self::$data['name'][$i].'-start");
               $end = input("get.'.self::$data['name'][$i].'-end");
               if ($start && $end) {
                   $where[]=["'.self::$data['name'][$i].'","between",[$start,date("Y-m-d",strtotime("$end +1 day"))]];
               }';
               //页面处理
               self::$data['index_search'][$i] = '   
               <div class="layui-form-item layui-inline">
                   <label class="layui-form-label">'.self::$data['desc'][$i].'</label>
                   <div class="layui-input-inline">
                       <input type="text" class="layui-input" id="'.self::$data['name'][$i].'-start" name="'.self::$data['name'][$i].'-start" placeholder="开始时间" autocomplete="off">
                   </div>
                   <div class="layui-input-inline">
                        <input type="text" class="layui-input" id="'.self::$data['name'][$i].'-end" name="'.self::$data['name'][$i].'-end" placeholder="结束时间" autocomplete="off">
                    </div>
               </div>';
               //页面JS处理
               self::$data['js_search'][$i] = 'laydate.render({elem: "#'.self::$data['name'][$i].'-start"});laydate.render({elem: "#'.self::$data['name'][$i].'-end"});';
           }else{
               //模型处理
               self::$data['model_search'][$i] = '
               //按'.self::$data['desc'][$i].'查找
               if ($'.self::$data['name'][$i].' = input("'.self::$data['name'][$i].'")) {
                   $where[] = ["'.self::$data['name'][$i].'", "like", "%" . $'.self::$data['name'][$i].' . "%"];
               }';
               //页面处理
               self::$data['index_search'][$i] = '   
               <div class="layui-form-item layui-inline">
                   <label class="layui-form-label">'.self::$data['desc'][$i].'</label>
                   <div class="layui-input-inline">
                       <input type="text" name="'.self::$data['name'][$i].'" placeholder="" class="layui-input">
                   </div>
               </div>';
           }
       }
   }

  // 表单处理
  public static function getForm($i)
  {
     if(isset(self::$data['form'][$i]) && self::$data['formType'][$i]!='4'){
        $form = '<div class="layui-form-item">
                    <label class="layui-form-label">
                        ' . self::$data['desc'][$i] . '
                    </label>
                    <div class="layui-input-block">
                        ';
            $html_js = '';$html_js_data = '';$lay_verify = '';
            switch (self::$data['formType'][$i]) {
                case '5':
                    if(self::$data['null'][$i] === 'NO') {
                        $lay_verify = ' lay-verify="required ';
                    }
                    $form .= '<textarea class="layui-textarea"' . $lay_verify . ' name="' . self::$data['name'][$i] . '" >{$model[\'' . self::$data['name'][$i] . '\']??""}</textarea>';
                    break;
                case '3':
                    if (self::$data['null'] === 'NO') {
                        $lay_verify = ' lay-verify="uploadimg"';
                    }
                    $form .= '{:opt_photo("' .  self::$data['name'][$i]  . '")}
                    <button class="pear-btn pear-btn-primary pear-btn-sm upload-image" type="button">
                        <i class="fa fa-image">
                        </i>
                        上传图片
                    </button>
                    <input' . $lay_verify . ' name="' .  self::$data['name'][$i]  . '" type="hidden" value="{$model[\'' . self::$data['name'][$i] . '\']??""}"/>
                    <div class="upload-image">
                        <span>
                        </span>
                        <img class="upload-image" src="{$model[\'' . self::$data['name'][$i] . '\']??""}"/>
                    </div>';
                    break;
                case '2':
                    $form .= '<textarea id="' .self::$data['name'][$i] . '" name="' . self::$data['name'][$i] . '" type="text/plain" style="width:100%;margin-bottom:20px;">{$model[\'' . self::$data['name'][$i] . '\']??""}</textarea>';
                    $html_js .= 'var '.self::$data['name'][$i].'  = layedit.build("'.self::$data['name'][$i].'", {height: 400});';
                    $html_js_data .= 'data.field.'.self::$data['name'][$i].'=layedit.getContent('.self::$data['name'][$i].');';    
                    break;
                default:
                    if(self::$data['null'][$i] === 'NO') {
                        $lay_verify = ' lay-verify="required ';
                        if (in_array(self::$data['type'][$i], ['int', 'decimal', 'float', 'double'])) {$lay_verify .= '|number';}
                        $lay_verify .= '"';
                    }
                    $form .= '<input type="text" class="layui-input layui-form-danger"' . $lay_verify . ' name="' . self::$data['name'][$i] . '" type="text" value="{$model[\'' . self::$data['name'][$i] . '\']??""}"/>';
                    break;
            }
            $form .= '
                    </div>
                </div>';
        self::$data['html_form'][$i] = $form;
        self::$data['html_js'][$i] = $html_js;
        self::$data['html_js_data'][$i] = $html_js_data;
     }
  }

    // 空处理
    public static function getNull($i)
    {
        if(self::$data['null'][$i] == '1' && self::$data['formType'][$i]!="4" && self::$data['name'][$i] != "id"){
            self::$data['validate_rule'][$i] = '\''.self::$data['name'][$i] . '\' => \'require' . '\',';
            self::$data['validate_msg'][$i] = '\''.self::$data['name'][$i] . '.require\' => \'' . self::$data['desc'][$i] . '为必填项\',';
        if (strstr(self::$data['type'][$i], 'int')||strstr(self::$data['type'][$i], 'decimal')||strstr(self::$data['type'][$i], 'float')||strstr(self::$data['type'][$i], 'double')){
            self::$data['validate_rule'][$i] = '\''.self::$data['name'][$i] . '\' => \'require|number' . '\',';
            self::$data['validate_msg'][$i] = '\''.self::$data['name'][$i] . '.require\' => \'' . self::$data['desc'][$i] . '为必填项\','
            . '\''.self::$data['name'][$i] . '.number\' => \'' . self::$data['desc'][$i] . '需为数字\',';
        }
            self::$data['validate_scene'][$i] = '\'' . self::$data['name'][$i] . '\',';
        }
    }


}