<?php
namespace app\admin\controller;

use app\admin\model\RoleUserModel;
use app\admin\model\UserModel;
use think\captcha\Captcha;
use think\Controller;
use think\Session;
use think\Request;

class PublicController extends Controller{

    public function __construct(Request $request)
    {
        parent::__construct($request);
        Session::init();
    }

    public function login(){
        if($_SESSION['ADMIN_ID']){
            $this->success(lang('LOGIN_SUCCESS'),url("Index/index"));
        }else{
           return $this->fetch();
        }
    }

    public function logout(){
        session('ADMIN_ID',null);
        //unset($_SESSION['ADMIN_ID']);
//        $_SESSION['ADMIN_ID'] = null;
        //Session::delete('ADMIN_ID');
        //echo $_SESSION['ADMIN_ID'];
        $this->redirect('/admin');
    }

    public function verifycode(){
        ob_end_clean();
        $verify = new Captcha (config('verify'));
        return $verify->entry('luo');
    }

    public function dologin(){
        if($_SESSION['ADMIN_ID']){
            $this->success(lang('LOGIN_SUCCESS'),url("Index/index"));
        }
//        $data = input('post.');
//        $this->validate($data,[
//            'captcha|验证码'=>'require|captcha',
//            'username|用户名'=>'require',
//            'password|密码'=>'require'
//        ]);
        $name = input("post.username");
        if(empty($name)){
            $this->error(lang('USERNAME_OR_EMAIL_EMPTY'));
        }
        $pass = input("post.password");
        if(empty($pass)){
            $this->error(lang('PASSWORD_REQUIRED'));
        }
        $verrify = input("post.verify");
        if(empty($verrify)){
            $this->error(lang('CAPTCHA_REQUIRED'));
        }
        //验证码
        if(!captcha_check($verrify,'luo',config('verify'))){
            $this->error(lang('CAPTCHA_NOT_RIGHT'));
        }else{
            $user = new UserModel();
            if(strpos($name,"@")>0){//邮箱登陆
                $where['user_email']=$name;
            }else{
                $where['user_login']=$name;
            }

            $result = $user->where($where)->find();
            if(!empty($result) && $result['user_type']==1){
                if(compare_password($pass,$result['user_pass'])){

                    $role_user_model =new RoleUserModel();

                    $role_user_join = config('DB_PREFIX').'role as b on a.role_id =b.id';

                    $groups = $role_user_model->alias("a")->join($role_user_join)->where(array("user_id"=>$result["id"],"status"=>1))->field("role_id",true);

                    if( $result["id"]!=1 && ( empty($groups) || empty($result['user_status']) ) ){
                        $this->error(lang('USE_DISABLED'));
                    }

                    //登入成功页面跳转
//                    $_SESSION["ADMIN_ID"]=$result["id"];
//                    $_SESSION['name']=$result["user_login"];
//                    $result['last_login_ip'] = get_client_ip(0,true);
//                    $result['last_login_time']=date("Y-m-d H:i:s");
                    session('ADMIN_ID',$result["id"]);
                    session('name',$result["user_login"]);
                    $result->last_login_ip = get_client_ip(0,true);
                    $result->last_login_time = date("Y-m-d H:i:s");
                    $result->save();


                    setcookie("admin_username",$name,time()+30*24*3600,"/");
                    $this->success(lang('LOGIN_SUCCESS'),url("Index/index"));
                }else{
                    $this->error(lang('PASSWORD_NOT_RIGHT'));
                }
            }else{
                $this->error(lang('USERNAME_NOT_EXIST'));
            }
        }
    }
}