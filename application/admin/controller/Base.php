<?php
/**
 * Created by PhpStorm.
 * User: lijiayi
 * Date: 2017/3/20
 * Time: 16:45
 */
namespace ylt\admin\controller;
use ylt\admin\logic\UpgradeLogic;
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
        //用户中心面包屑导航
        $navigate_admin = navigate_admin();
        $this->assign('navigate_admin',$navigate_admin);
        tpversion();        
    }    
    
    /*
     * 初始化操作
     */
    public function _initialize() 
    {
        
        //过滤不需要登陆的行为
        if(in_array(ACTION_NAME,array('login','logout','vertify')) || in_array(CONTROLLER_NAME,array('Ueditor','Uploadify'))){
        	//return;
			if(in_array(CONTROLLER_NAME,array('Ueditor','Uploadify'))){
				
				if(session('admin_id') == '')
					$this->redirect('Admin/Admin/login');
			}
        }else{
        	if(session('admin_id') > 0){
              //if(session('admin_id') > 0 && !session('supplier_id')){
        		$this->check_priv();//检查管理员菜单操作权限
        	}else{
				    session_unset();
					session_destroy();
					session::clear();
        		//$this->error('请先登陆',Url::build('Admin/Admin/login'),1);
				 $url = $_SERVER['HTTP_HOST'];
				if($url != 'admin.yilitong.com'){ 
				  $this->redirect('/');
				  exit;
				}
				$this->redirect('Admin/Admin/login');
				exit;
        	}
        }
        $act_list_user = Db::name('admin_user')->alias('a')->join('admin_role r','a.role_id=r.role_id')->field('r.act_list,r.role_name,r.is_three,a.admin_id')->where('a.admin_id',session('admin_id'))->find();
        if (stripos($act_list_user['act_list'],'27') != false and $act_list_user['is_three']==1) {
            $this->act_list  = $act_list_user;
            $this->assign('act_list',$this->act_list);
        }
        $this->public_assign();
        $this->del_order_cs();
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
    		$right = Db::name('system_menu')->where("id", "in", $act_list)->cache(true)->column('right');
    		foreach ($right as $val){
    			$role_right .= $val.',';
    		}
    		$role_right = explode(',', $role_right);
    		//检查是否拥有此操作权限
    		if(!in_array($ctl.'Controller@'.$act, $role_right)){
    			$this->error('您没有操作权限,请联系超级管理员分配权限',Url::build('Admin/Index/welcome'));
    		}
    	}
    }
    
    public function ajaxReturn($data,$type = 'json'){                        
            exit(json_encode($data));
    }

    /**
     * [delete_order 删除已取消和已作废订单]
     * @return [type] [description]
     */
    public function del_order_cs(){
        $time = strtotime("-7 day");
        $time_s = strtotime("-1 ");
        $r = Db::name('order')->where('order_status = 3 and shipping_status = 0 and pay_status = 0 and add_time <'.$time)->field('order_id')->select();
        foreach ($r as $key => $value) {
            Db::name('order_goods')->where('is_send = 0 and order_id ='.$value['order_id'])->delete();
        }
        Db::name('order')->where('order_status = 3 and shipping_status = 0 and pay_status = 0 and add_time <'.$time)->delete();
    }    
}