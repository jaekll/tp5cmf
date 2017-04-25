<?php

namespace app\admin\controller;

use app\admin\model\RoleModel;

class RbacController extends CommonController{

    public function index(){
        $role = new RoleModel();
        $data = $role->order(array("listorder" => "asc", "id" => "desc"))->select();
       // dump($data);die;
        $this->assign("roles", $data);
        return $this->fetch();
    }

    /*
    * 添加角色
    */
    public function roleadd() {

        if ($this->request->isPost()) {
            $role = new RoleModel();
            if ($role->create()) {
                if ($role->insert()!==false) {
                    $this->success("添加角色成功",U("rbac/index"));
                } else {
                    $this->error("添加失败！");
                }
            } else {
                $this->error($role->getError());
            }
        }

        return $this->fetch();
    }
}