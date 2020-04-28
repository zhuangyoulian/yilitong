<?php

/**
 * 管理员操作记录
 * @param $log_url 操作URL
 * @param $log_info 记录信息
 */
 
 use think\Db;
  
function adminLog($log_info){
    $add['log_time'] = time();
    $add['red_admin_id'] = session('red_admin_id');
    $add['log_info'] = $log_info;
    $add['log_ip'] = getIP();
    $add['log_url'] = request()->baseUrl();
	// $add['redsupplier_id'] =  session('redsupplier_id');
    Db::name('redsupplier_admin_log')->insert($add);
}


function getAdminInfo($red_admin_id){
	return Db::name('redsupplier_user')->where("red_admin_id", $red_admin_id)->find();
}

 
/**
 * 面包屑导航  用于后台管理
 * 根据当前的控制器名称 和 action 方法
 */
function navigate_admin()
{            
    $navigate = include APP_PATH.'redsupplier/conf/navigate.php';
    $location = strtolower('redsupplier/'.CONTROLLER_NAME);
    $arr = array(
        '后台首页'=>'javascript:void();',
        $navigate[$location]['name']=>'javascript:void();',
       // $navigate[$location]['action'][ACTION_NAME]=>'javascript:void();'
    );
    return $arr;
}

/**
 * 导出excel
 * @param $strTable	表格内容
 * @param $filename 文件名
 */
function downloadExcel($strTable,$filename)
{
	header("Content-type: application/vnd.ms-excel");
	header("Content-Type: application/force-download");
	header("Content-Disposition: attachment; filename=".$filename."_".date('Y-m-d').".xls");
	header('Expires:0');
	header('Pragma:public');
	echo '<html><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'.$strTable.'</html>';
}

/**
 * 格式化字节大小
 * @param  number $size      字节数
 * @param  string $delimiter 数字和单位分隔符
 * @return string            格式化后的带单位的大小
 */
function format_bytes($size, $delimiter = '') {
	$units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
	for ($i = 0; $size >= 1024 && $i < 5; $i++) $size /= 1024;
	return round($size, 2) . $delimiter . $units[$i];
}

// /**
//  * 根据id获取地区名字
//  * @param $regionId id
//  */
// function getRegionName($regionId){
//     $data = Db::name('region')->where(array('id'=>$regionId))->field('name')->find();
//     return $data['name'];
// }

// function getMenuList($act_list){
// 	//根据角色权限过滤菜单
// 	$menu_list = getAllMenu();
// 	if($act_list != 'all'){
// 		$right = Db::name('supplier_menu')->where("id", "in", $act_list)->cache(true)->column('right');
// 		foreach ($right as $val){
// 			$role_right .= $val.',';
// 		}
// 		$role_right = explode(',', $role_right);		
// 		foreach($menu_list as $k=>$mrr){
// 			foreach ($mrr['sub_menu'] as $j=>$v){
// 				if(!in_array($v['control'].'Controller@'.$v['act'], $role_right)){
// 					unset($menu_list[$k]['sub_menu'][$j]);//过滤菜单
// 				}
// 			}
// 		}
// 	}
// 	return $menu_list;
// }

function getAllMenu(){
	return	array(
			'goods' => array('name' => '商品管理', 'icon'=>'fa-book', 'sub_menu' => array(
					// array('name' => '商品分类', 'act'=>'categoryList', 'control'=>'Goods'),
					array('name' => '商品列表', 'act'=>'goodsList', 'control'=>'Goods'),
					array('name' => '商品回收站','act'=>'goodsDelete','control'=>'Goods'),
			)),
			'order' => array('name' => '订单管理', 'icon'=>'fa-money', 'sub_menu' => array(
					array('name' => '订单列表', 'act'=>'index', 'control'=>'Order'),
					// array('name' => '发货单', 'act'=>'delivery_list', 'control'=>'Order'),
					// array('name' => '退货单', 'act'=>'back_order_list', 'control'=>'Order'),
					// array('name' => '订单日志', 'act'=>'order_log', 'control'=>'Order'),
			)),
	);
}
/**
 * 转换字节
 * @param $bytes 传入字节数值
 * @param int $decimals
 * @return string BKMGTP
 */
function human_filesize($bytes, $decimals = 2) {
	$sz = 'BKMGTP';
	$factor = floor((strlen($bytes) - 1) / 3);
	return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
}
/**
 * 删除该目录以及该目录下面的所有文件和文件夹
 * @param $dir 目录
 * @return bool
 */
function removeDir($dirName) {
	//判断传入参数是否目录，如不是执行删除文件
	if (!is_dir($dirName)) {
		//删除文件
		@unlink($dirName);
	}
	//如果传入是目录，使用@opendir将该目录打开，将返回的句柄赋值给$handle
	$handle = @opendir($dirName);
	//这里明确地测试返回值是否全等于（值和类型都相同）FALSE
	//否则任何目录项的名称求值为 FALSE 的都会导致循环停止（例如一个目录名为“0”）
	while (($file = @readdir($handle)) !== false) {
		//在文件结构中，都会包含形如“.”和“..”的向上结构
		//但是它们不是文件或者文件夹
		if ($file != '.' && $file != '..') {
			//当前文件$dir为文件目录+文件
			$dir = $dirName . '/' .$file;
			//判断$dir是否为目录，如果是目录则递归调用reMoveDir($dirName)函数
			//将其中的文件和目录都删除；如果不是目录，则删除该文件
			is_dir($dir) ? removeDir($dir) : @unlink($dir);
		}
	}
	closedir($handle);
	return rmdir($dirName);
}



function respose($res){
	exit(json_encode($res));
}