<?php
namespace app\admin\controller;

use app\admin\model\AuthRoleModel;
use app\admin\model\MenuModel;
use com\Tree;
use think\Exception;
use think\exception\PDOException;

class MenuController extends CommonController{


    public function index(){
       session('admin_menu_index','Menu/index');
        $menu = new MenuModel();
        $categorys = $menu->formatMenuTree();
        $this->assign('categorys',$categorys);
        return $this->fetch();

    }

    public function lists(){
        session('admin_menu_index','Menu/lists');
        $menu = new MenuModel();
        $result = $menu->order(array("app" => "ASC","model" => "ASC","action" => "ASC"))->select();
        $this->assign('menus',$result);
        return $this->fetch();
    }


    public function add(){
        $tree = new Tree();
        $parentid = input('param.parentid');
        $menu = new MenuModel();
        $result = $menu->order(array("listorder" => "ASC"))->select();
        $array = [];
        foreach ($result as $r) {

            $r['selected'] = $r['id'] == $parentid ? 'selected' : '';
            $array[] = $r->toArray();
        }
        $str = "<option value='\$id' \$selected>\$spacer \$name</option>";

        $tree->init($array);
        $select_categorys = $tree->get_tree(0, $str);
        $this->assign("select_categorys", $select_categorys);
        return $this->fetch();
    }

    /**
     *  添加
     */
    public function add_post() {
        if ($this->request->isPost()) {
            $menu = new MenuModel();
            $role_auth = new AuthRoleModel();

            $parentid = input('post.parentid');
            $app=input("post.app");
            $model=input("post.model");
            $action=input("post.action");
            $name=strtolower("$app/$model/$action");
            $menu_name=input("post.name");
            $mwhere=array("name"=>$name);
            $data = input('post.data');
            $status = input('post.status');
            $type = input('post.type');
            $icon = input('post.icon');
            $remark = input('post.remark');

            try{
                $menu->insert(['parentid'=>$parentid,'app'=>$app,'name'=>$menu_name,'model'=>$model,'action'=>$action,'data'=>$data,'status'=>$status,'type'=>$type,'icon'=>$icon,'remark'=>$remark]);
                $find_rule = $role_auth->where($mwhere)->find();
                if(!$find_rule){
                    $role_auth->insert(array("name"=>$name,"module"=>$app,"type"=>"admin_url","title"=>$menu_name));//type 1-admin rule;2-user rule
                }
                $to = empty(session('admin_menu_index')) ? "Menu/index" : session('admin_menu_index');
                $this->success("添加成功！", url($to));
            }catch (PDOException $e){
                $this->error("添加失败！".$e->getMessage());
            }

        }
    }

    /**
     *  删除
     */
    public function delete() {
        $id = intval(input("get.id"));
        $menu = new MenuModel();
        $count = $menu->where(array("parentid" => $id))->count();
        if ($count > 0) {
            $this->error("该菜单下还有子菜单，无法删除！");
        }
        $menu->id = $id;
        if ($menu->delete()!==false) {
            $this->success("删除菜单成功！");
        } else {
            $this->error("删除失败！");
        }
    }

    /**
     *  编辑
     */
    public function edit() {
        $tree = new Tree();
        $menu = new MenuModel();
        $id = intval(input("param.id"));
        $rs = $menu->where(array("id" => $id))->find();
        $result = $menu->order(array("listorder" => "ASC"))->select();
        foreach ($result as $r) {
            $r['selected'] = $r['id'] == $rs['parentid'] ? 'selected' : '';
            $array[] = $r->toArray();
        }
        $str = "<option value='\$id' \$selected>\$spacer \$name</option>";
        $tree->init($array);
        $select_categorys = $tree->get_tree(0, $str);
        $this->assign("data", $rs);
        $this->assign("select_categorys", $select_categorys);
        return $this->fetch();
    }

    /**
     *  编辑
     */
    public function edit_post() {
        if ($this->request->isPost()) {
            $menu = new MenuModel();
            $role_auth = new AuthRoleModel();
            if ($menu->create()) {
                if ($menu->save() !== false) {
                    $app=input("post.app");
                    $model=input("post.model");
                    $action=input("post.action");
                    $name=strtolower("$app/$model/$action");
                    $menu_name=input("post.name");
                    $mwhere=array("name"=>$name);

                    $find_rule = $role_auth->where($mwhere)->find();
                    if(!$find_rule){
                        $role_auth->insert(array("name"=>$name,"module"=>$app,"type"=>"admin_url","title"=>$menu_name));//type 1-admin rule;2-user rule
                    }else{
                        $role_auth->where($mwhere)->save(array("name"=>$name,"module"=>$app,"type"=>"admin_url","title"=>$menu_name));//type 1-admin rule;2-user rule
                    }

                    $this->success("更新成功！");
                } else {
                    $this->error("更新失败！");
                }
            } else {
                $this->error($menu->getError());
            }
        }
    }

    //排序
    public function listorders() {
        $menu = new MenuModel();
        $status = parent::_listorders($menu);
        if ($status) {
            $this->success("排序更新成功！");
        } else {
            $this->error("排序更新失败！");
        }
    }

    public function spmy_export_menu(){

        $menuModel = new MenuModel();
        $menus = $menuModel->get_menu_tree(0);

        $menus_str= var_export($menus,true);
        preg_replace("/\s+\d+\s=>\s(\n|\r)/", "\n", $menus_str);

        foreach ($menus as $m){
            $app = $m['app'];
            $menudir = RUNTIME_PATH ."Menu/".$app;
            if(!file_exists($menudir)){
                mkdir($menudir);
            }
            $model = strtolower($m['model']);
            $menus_str = var_export($m,true);
            $menus_str = preg_replace("/\s+\d+\s=>\s(\n|\r)/", "\n", $menus_str);

            file_put_contents($menudir."/admin_$model.php", "<?php\nreturn $menus_str;");

        }
        return $this->display("export_menu");
    }

    public function spmy_export_menu_lang(){

        $menuModel = new MenuModel();
        $apps = scan_dir(APP_PATH."*",GLOB_ONLYDIR);
        foreach ($apps as $app){
            if(is_dir(APP_PATH.$app)){
                $lang_dirs = scan_dir(APP_PATH."$app/Lang/*",GLOB_ONLYDIR);

                $menus = $menuModel->where(array("app"=>$app))->order(array("listorder"=>"ASC","app" => "ASC","model" => "ASC","action" => "ASC"))->select();
                foreach ($lang_dirs as $lang_dir){
                    $admin_menu_lang_file=APP_PATH.$app."/Lang/".$lang_dir."/admin_menu.php";
                    $lang=array();
                    if(is_file($admin_menu_lang_file)){
                        $lang = include $admin_menu_lang_file;
                    }

                    foreach ($menus as $menu){
                        $lang_key=strtoupper($menu['app'].'_'.$menu['model'].'_'.$menu['action']);
                        if(!isset($lang[$lang_key])){
                            $lang[$lang_key]=$menu['name'];
                        }
                    }

                    $lang_str= var_export($lang,true);
                    $lang_str=preg_replace("/\s+\d+\s=>\s(\n|\r)/", "\n", $lang_str);
                    file_put_contents($admin_menu_lang_file, "<?php\nreturn $lang_str;");
                }
            }
        }

        echo "success!";
    }

    private function _import_menu($menus,$parentid=0,&$error_menus=array()){

        $menuModel = new MenuModel();
        $role_auth = new AuthRoleModel();
        foreach ($menus as $menu){

            $app=$menu['app'];
            $model=$menu['model'];
            $action=$menu['action'];

            $where['app']=$app;
            $where['model']=$model;
            $where['action']=$action;
            $children=isset($menu['children'])?$menu['children']:false;
            unset($menu['children']);
            $find_menu = $menuModel->where($where)->find();
            if($find_menu){
                $newmenu=array_merge($find_menu,$menu);
                $result = $menuModel->save($newmenu);
                if($result===false){
                    $error_menus[]="$app/$model/$action";
                    $parentid2=false;
                }else{
                    $parentid2=$find_menu['id'];
                }
            }else{
                $menu['parentid']=$parentid;
                $result = $menuModel->insert($menu);
                if($result===false){
                    $error_menus[]="$app/$model/$action";
                    $parentid2=false;
                }else{
                    $parentid2=$result;
                }
            }

            $name=strtolower("$app/$model/$action");
            $mwhere=array("name"=>$name);

            $find_rule = $role_auth->where($mwhere)->find();
            if(!$find_rule){
                $role_auth->insert(array("name"=>$name,"module"=>$app,"type"=>"admin_url","title"=>$menu['name']));//type 1-admin rule;2-user rule
            }else{
                $role_auth->where($mwhere)->save(array("module"=>$app,"type"=>"admin_url","title"=>$menu['name']));//type 1-admin rule;2-user rule
            }

            if($children && $parentid!==false){
                $this->_import_menu($children,$parentid2,$error_menus);
            }
        }

    }

    public function spmy_import_menu(){

        $apps=scan_dir(RUNTIME_PATH."*",GLOB_ONLYDIR);
        $error_menus=array();
        foreach ($apps as $app){
            if(is_dir(RUNTIME_PATH."Menu")){
                $menudir = RUNTIME_PATH."Menu".$app;
                $menu_files = scan_dir($menudir."/admin_*.php",null);
                if(count($menu_files)){
                    foreach ($menu_files as $mf){
                        //是php文件
                        $mf_path=$menudir."/$mf";
                        if(file_exists($mf_path)){
                            $menudatas = include   $mf_path;
                            if(is_array($menudatas) && !empty($menudatas)){
                                $this->_import_menu(array($menudatas),0,$error_menus);
                            }
                        }

                    }
                }

            }
        }
        $this->assign("errormenus",$error_menus);
        $this->display("import_menu");
    }

    private function _import_submenu($submenus,$parentid){

        $menuModel = new MenuModel();
        foreach($submenus as $sm){
            $data=$sm;
            $data['parentid']=$parentid;
            unset($data['items']);
            $id = $menuModel->insert($data);
            if(!empty($sm['items'])){
                $this->_import_submenu($sm['items'],$id);
            }else{
                return;
            }
        }
    }

    private function _generate_submenu(&$rootmenu,$m){
        $menuModel = new MenuModel();
        $parentid=$m['id'];
        $rm = $menuModel->menu($parentid);
        unset($rootmenu['id']);
        unset($rootmenu['parentid']);
        if(count($rm)){
            $count=count($rm);
            for($i=0;$i<$count;$i++){
                $this->_generate_submenu($rm[$i],$rm[$i]);
            }
            $rootmenu['items']=$rm;
        }else{
            return ;
        }

    }


    public function spmy_getactions(){

        $menuModel = new MenuModel();
        $role_auth = new AuthRoleModel();
        $apps_r=array("Comment");
        $groups=config("MODULE_ALLOW_LIST");
        $group_count=count($groups);
        $newmenus=array();
        $g=input("get.app");
        if(empty($g)){
            $g=$groups[0];
        }

        if(in_array($g, $groups)){
            if(is_dir(APP_PATH.$g)){
                if(!(strpos($g, ".") === 0)){
                    $actiondir=APP_PATH.$g."/Controller";
                    $actions=scan_dir($actiondir."/*");
                    if(count($actions)){
                        foreach ($actions as $mf){
                            if(!(strpos($mf, ".") === 0)){
                                if($g=="Admin"){
                                    $m=str_replace("Controller.class.php", "",$mf);
                                    $noneed_models=array("Public","Index","Main");
                                    if(in_array($m, $noneed_models)){
                                        continue;
                                    }
                                }else{
                                    if(strpos($mf,"adminController.class.php") || strpos($mf,"Admin")===0){
                                        $m=str_replace("Controller.class.php", "",$mf);
                                    }else{
                                        continue;
                                    }
                                }
                                $class=controller($g."/".$m);
                                $adminbaseaction=new \Common\Controller\AdminbaseController();
                                $base_methods=get_class_methods($adminbaseaction);
                                $methods=get_class_methods($class);
                                $methods=array_diff($methods, $base_methods);

                                foreach ($methods as $a){
                                    if(!(strpos($a, "_") === 0) && !(strpos($a, "spmy_") === 0)){
                                        $where['app']=$g;
                                        $where['model']=$m;
                                        $where['action']=$a;
                                        $count = $menuModel->where($where)->count();
                                        if(!$count){
                                            $data['parentid']=0;
                                            $data['app']=$g;
                                            $data['model']=$m;
                                            $data['action']=$a;
                                            $data['type']="1";
                                            $data['status']="0";
                                            $data['name']="未知";
                                            $data['listorder']="0";
                                            $result =$menuModel->insert($data);
                                            if($result!==false){
                                                $newmenus[]=   $g."/".$m."/".$a."";
                                            }
                                        }

                                        $name=strtolower("$g/$m/$a");
                                        $mwhere=array("name"=>$name);

                                        $find_rule = $role_auth->where($mwhere)->find();
                                        if(!$find_rule){
                                            $role_auth->insert(array("name"=>$name,"module"=>$g,"type"=>"admin_url","title"=>""));//type 1-admin rule;2-user rule
                                        }
                                    }
                                }
                            }


                        }
                    }
                }
            }

            $index=array_search($g, $groups);
            $nextindex=$index+1;
            $nextindex=$nextindex>=$group_count?0:$nextindex;
            if($nextindex){
                $this->assign("nextapp",$groups[$nextindex]);
            }
            $this->assign("app",$g);
        }

        $this->assign("newmenus",$newmenus);
        return $this->fetch("getactions");

    }

}