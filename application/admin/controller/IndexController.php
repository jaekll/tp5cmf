<?php
namespace app\admin\controller;

use app\admin\model\MenuModel;
use think\Db;
class IndexController extends CommonController{

    /**
     * 后台框架首页
     */
    public function index() {
        if (config('lang_switch_on',null,false)){

        }
        $menuModel = new MenuModel();
        $menu_config = $menuModel->menu_json();
        $this->assign('username',session('name'));
        $this->assign("menus",$menu_config);
        return $this->fetch();
    }
}