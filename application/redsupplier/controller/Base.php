<?php

/**
 * Created by PhpStorm.
 * User: lijiayi
 * Date: 2017/3/20
 * Time: 16:45
 */
namespace ylt\redsupplier\controller;
use ylt\redsupplier\logic\UpgradeLogic;
use think\Controller;
use think\Db;
use think\response\Json;
use think\Session;
use think\Url;
class Base extends Controller {

    /**
     * 析构函数
     */
    function __construct() 
    {
        Session::start();
        header("Cache-control: private");  
        parent::__construct();   
        session('supplier_id',686);  //红礼ID
        //用户中心面包屑导航
        $navigate_admin = navigate_admin();
        $this->assign('navigate_admin',$navigate_admin);

   }    
    
    /*
     * 初始化操作
     */
    public function _initialize() 
    {
        //过滤不需要登陆的行为
        if(in_array(ACTION_NAME,array('login','logout','vertify'))){
        	//return;
        }else{
        	if(session('red_admin_id') > 0){
        		// $this->check_priv();//检查管理员菜单操作权限
            }else{
				session_unset();
				session_destroy();
				session::clear();
				$this->redirect('Home/RedBusiness/login');
				exit;
        	}
        }
        $this->public_assign();
    }
    
    /**
     * 保存公告变量到 smarty中 比如 导航 
     */
    public function public_assign()
    {
        $config = array();
       $tp_config = Db::name('config')->cache(true)->select();
       foreach($tp_config as $k => $v)
       {
           $config[$v['inc_type'].'_'.$v['name']] = $v['value'];
       }
       $this->assign('config', $config);
    }
    
    public function check_priv()
    {
    	$ctl = CONTROLLER_NAME;
    	$act = ACTION_NAME;
        $act_list = session('act_list');
		//无需验证的操作
		$uneed_check = array('login','logout','vertifyHandle','vertify','imageUp','upload','login_task');
    	if($ctl == 'Index' || $act_list == 'all'){
    		//后台首页控制器无需验证,超级管理员无需验证
    		return true;
    	}elseif(strpos($act,'ajax') || in_array($act,$uneed_check)){
    		//所有ajax请求不需要验证权限
    		return true;
    	}else{
    		$right = Db::name('redsupplier_menu')->where("id", "in", $act_list)->cache(true)->column('right');
    		foreach ($right as $val){
    			$role_right .= $val.',';
    		}
    		$role_right = explode(',', $role_right);
    		//检查是否拥有此操作权限
    		if(!in_array($ctl.'Controller@'.$act, $role_right)){
    			$this->error('您没有操作权限,请联系超级管理员分配权限',Url::build('redsupplier/Index/welcome'));
    		}
    	}
    }
    
    public function ajaxReturn($data,$type = 'json'){                        
            exit(json_encode($data));
    }    
}