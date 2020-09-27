<?php

/**
 * Created by PhpStorm.
 * User: lijiayi
 * Date: 2017/3/21
 * Time: 10:45
 */
 
namespace ylt\supplier\controller; 
use think\AjaxPage;
use think\Controller;
use think\Url;
use think\Config;
use think\Page;
use think\Verify;
use think\Db;
use think\Request;
class Index extends Base {

    public function index(){
        $act_list = session('act_list');
		$supplier_id = session('supplier_id');
        $menu_list = getMenuList($act_list);         
        $this->assign('menu_list',$menu_list);
        $admin_info = getAdminInfo(session('admin_id'));
        $order_amount = DB::name('order')->where("order_status=0 and (pay_status=1 or pay_code='cod') and supplier_id = '$supplier_id'")->count();

        $this->assign('order_amount',$order_amount);
        $this->assign('admin_info',$admin_info);              
        return $this->fetch();
    }
	
   
    public function welcome(){
		$supplier_id = session('supplier_id');
    	$this->assign('sys_info',$this->get_sys_info());
    	$today = strtotime("-1 day");
    	$count['handle_order'] = DB::name('order')->where("order_status=0 and (pay_status=1 or pay_code='cod') and supplier_id = '$supplier_id'")->count();//待处理订单
    	$count['new_order'] = DB::name('order')->where("add_time>$today and supplier_id = '$supplier_id'")->count();//今天新增订单
    	$count['goods'] =  DB::name('goods')->where("1=1 and supplier_id = '$supplier_id'")->count();//商品总数
    	$this->assign('count',$count);
        return $this->fetch();
    }
    
    public function get_sys_info(){
		$sys_info['os']             = PHP_OS;
		$sys_info['zlib']           = function_exists('gzclose') ? 'YES' : 'NO';//zlib
		$sys_info['safe_mode']      = (boolean) ini_get('safe_mode') ? 'YES' : 'NO';//safe_mode = Off		
		$sys_info['timezone']       = function_exists("date_default_timezone_get") ? date_default_timezone_get() : "no_timezone";
		$sys_info['curl']			= function_exists('curl_init') ? 'YES' : 'NO';	
		$sys_info['web_server']     = $_SERVER['SERVER_SOFTWARE'];
		$sys_info['phpv']           = phpversion();
		$sys_info['ip'] 			= GetHostByName($_SERVER['SERVER_NAME']);
		$sys_info['fileupload']     = @ini_get('file_uploads') ? ini_get('upload_max_filesize') :'unknown';
		$sys_info['max_ex_time'] 	= @ini_get("max_execution_time").'s'; //脚本最大执行时间
		$sys_info['set_time_limit'] = function_exists("set_time_limit") ? true : false;
		$sys_info['domain'] 		= $_SERVER['HTTP_HOST'];
		$sys_info['memory_limit']   = ini_get('memory_limit');	                                
        $sys_info['version']   	    = 1.0;
		$mysqlinfo = Db::query("SELECT VERSION() as version");
		$sys_info['mysql_version']  = $mysqlinfo[0]['version'];
		if(function_exists("gd_info")){
			$gd = gd_info();
			$sys_info['gdinfo'] 	= $gd['GD Version'];
		}else {
			$sys_info['gdinfo'] 	= "未知";
		}
		return $sys_info;
    }
	
	    /**
     * ajax 修改指定表数据字段  一般修改状态 比如 是否推荐 是否开启 等 图标切换 修改更新时间 myAjax2.js
     * table,id_name,id_value,field,value
     */
    public function changeTableVal(){  
            $table     = I('table');     // 表名
            $id_name   = I('id_name');   // 表主键id名
            $id_value  = I('id_value');  // 表主键id值
            $field     = I('field');     // 修改哪个字段
            $value     = I('value');     // 修改字段值
            Db::name($table)->where("$id_name = $id_value")->update(array($field=>$value,'last_update' => time())); // 根据条件保存修改的数据
    }	
    
 
    	    

}