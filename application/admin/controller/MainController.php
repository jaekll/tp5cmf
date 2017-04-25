<?php
namespace app\admin\controller;

class MainController extends CommonController{
    public function index(){
        $this->assign('info','主页');
        return $this->fetch();
    }
}