<?php

namespace ylt\redsupplier\controller;
use think\db;
use think\Url;
use think\Request;
class System extends Base{
	
	 
	 /**
	 * 入驻信息
	 */
	 
	 public function supplier_info(){
		$info = Db::name('redsupplier_user')->where('red_admin_id',session('red_admin_id'))->find();
		$this->assign('su',$info);
     	return $this->fetch();
	 }
	 
	 
	 
	
}