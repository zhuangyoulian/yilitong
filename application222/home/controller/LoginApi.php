<?php

/**

 * Created by PhpStorm.

 * User: jiayi

 * Date: 2017/5/24

 * Time: 10:29

 */

namespace ylt\home\controller;

use ylt\home\logic\UsersLogic;

use ylt\home\logic\CartLogic;

use ylt\home\controller\User;

use think\Request;

use think\Url;

use think\Db;

class LoginApi extends Base {

    public $config;

    public $oauth;

    public $class_obj;



    public function __construct(){

        parent::__construct();

//        unset($_GET['oauth']);   // 删除掉 以免被进入签名

//        unset($_REQUEST['oauth']);// 删除掉 以免被进入签名



        $this->oauth = I('get.oauth');
        // dump($this->oauth);

        //获取配置

        $data = Db::name('Plugin')->where("code",$this->oauth)->where("type","login")->find();
        $this->config = unserialize($data['config_value']); // 配置反序列化
        if(!$this->oauth)

            $this->error('非法操作',Url::build('User/user_login'));

        include_once  "plugins/login/{$this->oauth}/{$this->oauth}.class.php";

        $class = '\\'.$this->oauth; //
        $this->class_obj = new $class($this->config); //实例化对应的登陆插件

        // dump($this->config);        
        // die;
    }



    public function login(){

        $referurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : Url::build("Home/User/index");
        session('referurl',$referurl);

        if(!$this->oauth)

            $this->error('非法操作',Url::build('User/user_login'));

        include_once  "plugins/login/{$this->oauth}/{$this->oauth}.class.php";

        $this->class_obj->login();

    }



    public function callback(){

        $data = $this->class_obj->respon();
        $logic = new UsersLogic();

        if(session('?user')){

            $res = $logic->oauth_bind($data);//已有账号绑定第三方账号

            if($res['status'] == 1){

                $this->success('绑定成功',Url::build('User/index'));

            }else{

                $this->error('绑定失败',Url::build('User/index'));

            }

        }

        $data = $logic->thirdLogin($data);

        if($data['status'] != 1){

			$this->error($data['msg']);

		}

            //dump($data['result']);

        session('user',$data['result']);

        setcookie('user_id',$data['result']['user_id'],null,'/');

        setcookie('is_distribut',$data['result']['is_distribut'],null,'/');

        $nickname = empty($data['result']['nickname']) ? '第三方用户' : $data['result']['nickname'];

        setcookie('user_name',urlencode($nickname),null,'/');

        setcookie('cn',0,time()-3600,'/');

        // 登录后将购物车的商品的 user_id 改为当前登录的id

        $cartLogic = new CartLogic();

        $cartLogic->login_cart_handle($this->session_id,$data['result']['user_id']);  //用户登录后 需要对购物车 一些操作
        $referurl = session('referurl');
         header("Location:".$referurl);
         //$this->success('登陆成功',Url::build($referurl));
    }
}