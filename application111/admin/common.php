<?php

/**
 * 管理员操作记录
 * @param $log_url 操作URL
 * @param $log_info 记录信息
 */
use think\Db;
function adminLog($log_info){
	
    $add['log_time'] = time();
    $add['admin_id'] = session('admin_id');
    $add['log_info'] = $log_info;
    $add['log_ip'] = getIP();
    $add['log_url'] = request()->baseUrl() ;
    
    Db::name('admin_log')->insert($add);
}
/*一创*/
function agen_adminLog($log_info){
	
    $add['log_time'] = time();
    $add['admin_id'] = 1;
    $add['log_info'] = $log_info;
    $add['log_ip'] = getIP();
    $add['log_url'] = request()->baseUrl() ;
    
    Db::name('agen_log')->insert($add);
}
/*红礼*/
function red_adminLog($log_info){
	
    $add['log_time'] = time();
    $add['admin_id'] = 1;
    $add['log_info'] = $log_info;
    $add['log_ip'] = getIP();
    $add['log_url'] = request()->baseUrl() ;
    
    Db::name('red_log')->insert($add);
}

function getAdminInfo($admin_id){
	return Db::name('admin_user')->where("admin_id", $admin_id)->find();
}

function tpversion()
{     
         
}
 
/**
 * 面包屑导航  用于后台管理
 * 根据当前的控制器名称 和 action 方法
 */
function navigate_admin()
{            
    $navigate = include APP_PATH.'admin/conf/navigate.php';
    $location = strtolower('Admin/'.CONTROLLER_NAME);
    $arr = array(
        '后台首页'=>'javascript:void();',
        $navigate[$location]['name']=>'javascript:void();',
        $navigate[$location]['action'][ACTION_NAME]=>'javascript:void();'
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

/**
 * 根据id获取地区名字
 * @param $regionId id
 */
function getRegionName($regionId){
    $data = Db::name('region')->where(array('id'=>$regionId))->field('name')->find();
    return $data['name'];
}
/**
 * 二级分类
 */
function getMenuList($act_list){
	//根据角色权限过滤菜单
	$menu_list = getAllMenu();
	if($act_list != 'all'){
		$right = Db::name('system_menu')->where("id", "in", $act_list)->cache(true)->column('right');
		foreach ($right as $val){
			$role_right .= $val.',';
		}
		$role_right = explode(',', $role_right);		
		foreach($menu_list as $k=>$mrr){
			foreach ($mrr['sub_menu'] as $j=>$v){
				if(!in_array($v['control'].'Controller@'.$v['act'], $role_right)){
					unset($menu_list[$k]['sub_menu'][$j]);//过滤菜单
				}
			}
		}
	}
	// dump($menu_list);
	// die;
	return $menu_list;
}

/**
 * 三级分类
 */
function getMenuArr(){
	$menuArr = getAllMenuArr();
	$act_list = session('act_list');
	if($act_list != 'all' && !empty($act_list)){
		$right = Db::name('system_menu')->where("id in ($act_list)")->cache(true)->getField('right',true);
		foreach ($right as $val){
			$role_right .= $val.',';
		}
		 
		$role_right = str_replace("Controller","",$role_right);//替换掉Controller
		foreach($menuArr as $k=>$val){
			foreach ($val['child'] as $j=>$v){
				foreach ($v['child'] as $s=>$son){
					if(strpos($role_right,$son['op'].'@'.$son['act']) === false){
						unset($menuArr[$k]['child'][$j]['child'][$s]);//过滤菜单
					}
				}
			}
		}
		foreach ($menuArr as $mk=>$mr){
			foreach ($mr['child'] as $nk=>$nrr){
				if(empty($nrr['child'])){
					unset($menuArr[$mk]['child'][$nk]);
				}
			}
		}
	}
	// dump($menuArr);
	// die;
	return $menuArr;
}

/**
 * 二级分类
 */
function getAllMenu(){
	return	array(
			
			'member' => array('name'=>'用户管理','icon'=>'fa-user','sub_menu'=>array(
					array('name' => '用户列表','act'=>'index','control'=>'User'),
					//array('name' => '用户等级','act'=>'rankList','control'=>'User'),
					array('name' => '用户统计', 'act'=>'user', 'control'=>'Report'),
					array('name' => '用户排行', 'act'=>'userTop', 'control'=>'Report'),
			)),
			'goods' => array('name' => '商品管理', 'icon'=>'fa-book', 'sub_menu' => array(
					array('name' => '商品分类', 'act'=>'categoryList', 'control'=>'Goods'),
              		array('name' => '场景分类', 'act'=>'scenarioCategoryList', 'control'=>'Goods'),//新增场景分类
					array('name' => '商品列表', 'act'=>'goodsList', 'control'=>'Goods'),
					array('name' => '商品模型', 'act'=>'goodsTypeList', 'control'=>'Goods'),
					array('name' => '商品规格', 'act' =>'specList', 'control' => 'Goods'),
					array('name' => '品牌列表', 'act'=>'brandList', 'control'=>'Goods'),
					array('name' => '商品评论','act'=>'index','control'=>'Comment'),
					array('name' => '商品咨询','act'=>'ask_list','control'=>'Comment'),
					array('name' => '商品回收站','act'=>'goodsDelete','control'=>'Goods'),
			)),
			'order' => array('name' => '订单管理', 'icon'=>'fa-money', 'sub_menu' => array(
					array('name' => '订单列表', 	'act'=>'index', 'control'=>'Order'),
					array('name' => '发货单', 		'act'=>'delivery_list', 'control'=>'Order'),
					array('name' => '退货单', 		'act'=>'back_order_list', 'control'=>'Order'),
					array('name' => '订单日志', 	'act'=>'order_log', 'control'=>'Order'),
			)),
			'supplier' => array('name' => '入驻商管理', 'icon'=>'fa-flag', 'sub_menu' => array(
					array('name' => '入驻商列表', 	'act'=>'BusinessList', 'control'=>'Supplier'),
					array('name' => '入驻商审核', 	'act'=>'BusinessExamine', 'control'=>'Supplier'),
					array('name' => '入驻商商品', 	'act'=>'suppliergoods', 'control'=>'Goods'),
					//array('name' => '企业采集审核', 	'act'=>'PurchaseExamine', 'control'=>'Supplier'),
					array('name' => '询报价管理', 	'act'=>'PurchaseExamine', 'control'=>'Supplier'),//新增调试中
					array('name' => '入驻商订单列表', 'act'=>'supplier_order', 'control'=>'Order'),
					array('name' => '入驻商日志',   'act'=>'supplier_log', 'control'=>'Supplier'),
					array('name' => '商家结算', 	'act'=>'supplier_settlement_list', 'control'=>'Supplier'),
					array('name' => '兑换设置',   'act'=>'exchange_goods', 'control'=>'Supplier'),
					array('name' => '兑换记录',   'act'=>'exchange_log', 'control'=>'Supplier'),
			)),
			'designer' => array('name' => '设计师管理', 'icon'=>'fa-flag', 'sub_menu' => array(
					array('name' => '设计师列表',  'act'=>'DesignerList', 'control'=>'Designer'),
					array('name' => '设计师审核',  'act'=>'DesignerExamine', 'control'=>'Designer'),
				    array('name' => '设计师分类',  'act'=>'CategoryList', 'control'=>'Designer'),
					array('name' => '作品分类',  'act'=>'WorksCategoryList', 'control'=>'Designer'),
					array('name' => '设计师作品',  'act'=>'WorksList', 'control'=>'Designer'),
					array('name' => '设计师商品',  'act'=>'DesignerGoods', 'control'=>'Goods'),
					array('name' => '设计师订单列表', 'act'=>'DesignerOrder', 'control'=>'Order'),
					array('name' => '设计师日志',  'act'=>'DesignerLog', 'control'=>'Designer'),
					array('name' => '设计师提现',  'act'=>'WithdrawalsLog', 'control'=>'Designer'),
			)),
			'promotion' => array('name' => '促销管理', 'icon'=>'fa-bell', 'sub_menu' => array(
					array('name' => '抢购管理', 	'act'=>'panic_buying', 'control'=>'Promotion'),
					array('name' => '活动类型管理', 'act'=>'cate_list', 'control'=>'Promotion'),
					array('name' => '折扣活动列表', 'act'=>'activity_list', 'control'=>'Promotion'),
					//array('name' => '团购管理', 	'act'=>'group_buy_list', 'control'=>'Promotion'),
					array('name' => '商品促销', 	'act'=>'prom_goods_list', 'control'=>'Promotion'),
					//array('name' => '订单促销', 	'act'=>'prom_order_list', 'control'=>'Promotion'),*/
					array('name' => '代金券管理',	'act'=>'index', 'control'=>'Coupon'),
			)),
			'PcAd' => array('name' => '电脑端广告管理', 	'icon'=>'fa-flag', 'sub_menu' => array(
					array('name' => '广告列表', 	'act'=>'adList', 'control'=>'Ad'),
					array('name' => '广告位置', 	'act'=>'positionList', 'control'=>'Ad'),
					array('name' => '送礼攻略', 	'act'=>'giftsCategoryList', 'control'=>'Ad'),
					array('name' => '店铺精选', 	'act'=>'supplierRecommendList', 'control'=>'Ad'),
			)),
			'AppAd' => array('name' => '移动端广告管理', 	'icon'=>'fa-flag', 'sub_menu' => array(
					array('name' => 'Banner广告列表', 	'act'=>'adList', 'control'=>'Ad','id'=>'12'),
					array('name' => '首页单张广告列表', 	'act'=>'adList', 'control'=>'Ad','id'=>'39'),
					array('name' => '首页金刚区', 	'act'=>'adList', 'control'=>'Ad','id'=>'27'),
					array('name' => '品牌推荐列表', 	'act'=>'brandList', 'control'=>'Ad'),
					array('name' => '广告位置', 	'act'=>'positionList', 'control'=>'Ad'),
					array('name' => '送礼攻略', 	'act'=>'giftsCategoryList', 'control'=>'Ad'),
					array('name' => '店铺精选', 	'act'=>'supplierRecommendList', 'control'=>'Ad'),
			)),
			'content' => array('name' => '内容管理', 'icon'=>'fa-comments', 'sub_menu' => array(
					array('name' => '文章列表', 	'act'=>'articleList', 'control'=>'Article'),
					array('name' => '文章分类', 	'act'=>'categoryList', 'control'=>'Article'),
					array('name' => '评论列表', 	'act'=>'commentList', 'control'=>'Article'),
			)),	
			'application' => array('name' => '应用概况', 'icon'=>'fa-plug', 'sub_menu' => array(
					array('name' => '应用概况', 	'act'=>'welcome', 'control'=>'Report'),
			)),
			'download' => array('name' => '渠道数据', 'icon'=>'fa-signal', 'sub_menu' => array(
					array('name' => '渠道统计', 	'act'=>'download', 'control'=>'Report'),
					array('name' => '下载统计', 	'act'=>'download_statisticss', 'control'=>'Report'),
			)),
			// 'count' => array('name' => '统计报表', 	'icon'=>'fa-signal', 'sub_menu' => array(
			// 		array('name' => '销售概况', 	'act'=>'index', 'control'=>'Report'),
			// 		array('name' => '销售排行', 	'act'=>'saleTop', 'control'=>'Report'),
			// 		array('name' => '销售明细', 	'act'=>'saleList', 'control'=>'Report'),
			// )),
			'count' => array('name' => '统计报表', 	'icon'=>'fa-signal', 'sub_menu' => array(
					array('name' => '销售概况', 	'act'=>'index', 'control'=>'Report'),
					array('name' => '销售排行', 	'act'=>'saleTop', 'control'=>'Report'),
					array('name' => '销售明细', 	'act'=>'saleList', 'control'=>'Report'),
			)),
			'access' => array('name' => '权限管理', 'icon'=>'fa-gears', 'sub_menu' => array(
					array('name' => '权限板块列表',	'act'=>'plate_list','op'=>'System'),
					array('name' => '权限功能列表',	'act'=>'right_list','control'=>'System'),
					array('name' => '角色管理', 	'act'=>'role', 'control'=>'Admin'),
					array('name' => '管理员列表', 	'act'=>'index', 'control'=>'Admin'),
					array('name' => '管理员日志', 	'act'=>'log', 'control'=>'Admin'),
			)),
			'tools' => array('name' => '数据', 		'icon'=>'fa-plug', 'sub_menu' => array(
					array('name' => '数据备份', 	'act'=>'index', 'control'=>'Tools'),
					array('name' => '数据还原', 	'act'=>'restore', 'control'=>'Tools'),
			)),
			'system' => array('name'=>'系统设置','icon'=>'fa-cog','sub_menu'=>array(
					array('name' => '网站设置',		'act'=>'index','control'=>'System'),
					array('name' => '支付设置', 	'act'=>'pay', 'control'=>'System'),
					array('name' => '友情链接',		'act'=>'linkList','control'=>'Article'),
					array('name' => '自定义导航',	'act'=>'navigationList','control'=>'System'),
					array('name' => '区域管理',		'act'=>'region','control'=>'Tools'),
					array('name' => '极光推送',		'act'=>'jpush','control'=>'System'),
			)),
			'sales' => array('name'=>'分销2019','icon'=>'fa-cog','sub_menu'=>array(
					array('name' => '店主列表',		'act'=>'shopkeeper','control'=>'Sales'),
					array('name' => '订单列表',		'act'=>'orders','control'=>'Sales'),
					// array('name' => '礼豆列表',		'act'=>'peas','op'=>'Sales'),
					array('name' => '提现申请',		'act'=>'withdraw','control'=>'Sales'),
					array('name' => '佣金设置',		'act'=>'set','control'=>'Sales'),
					array('name' => '奖励金发放列表',		'act'=>'bonus_list','control'=>'Sales'),
			)),
			'agen' => array('name'=>'一创物料库','icon'=>'fa-cog','sub_menu'=>array(
					array('name' => '物流商品',		'act'=>'a_gen_list','control'=>'Agen'),
					array('name' => '物流订单',		'act'=>'orderIndex','control'=>'Agen'),
					array('name' => '反馈建议',		'act'=>'suggestList','control'=>'Agen'),
					array('name' => '用户管理',		'act'=>'userList','control'=>'Agen'),
			)),
			'RedGift' => array('name'=>'红礼供应链','icon'=>'fa-cog','sub_menu'=>array(
					array('name' => '供货商家',		'act'=>'BusinessList','control'=>'RedGift'),
					array('name' => '物流商品',		'act'=>'a_red_list','control'=>'RedGift'),
					array('name' => '物流订单',		'act'=>'red_orderIndex','control'=>'RedGift'),
					array('name' => '反馈建议',		'act'=>'red_suggestList','control'=>'RedGift'),
					array('name' => '用户管理',		'act'=>'red_userList','control'=>'RedGift'),
			)),
			'RiteHome' => array('name'=>'礼至家居','icon'=>'fa-cog','sub_menu'=>array(
					array('name' => '家居用户',		'act'=>'user_index','control'=>'RiteHome'),
					array('name' => '家居订单',		'act'=>'inquire_index','control'=>'RiteHome'),
			)),
	);
}

/**
 * 三级分类数据
 */
function getAllMenuArr(){
	return	array(	
	'system'=>array('name'=>'系统','child'=>array(
				array('name' => '设置','child' => array(
					array('name' => '网站设置',		'act'=>'index','op'=>'System'),
					array('name' => '支付设置', 		'act'=>'pay', 'op'=>'System'),
					array('name' => '友情链接',		'act'=>'linkList','op'=>'Article'),
					array('name' => '自定义导航',	'act'=>'navigationList','op'=>'System'),
					array('name' => '区域管理',		'act'=>'region','op'=>'Tools'),
					array('name' => '极光推送',		'act'=>'jpush','op'=>'System'),
					array('name' => '抗疫行动',		'act'=>'charity','op'=>'System'),
					// array('name' => '预约活动',		'act'=>'appointment','op'=>'System'),
					array('name' => '奖励申请',		'act'=>'respirator','op'=>'System'),
					// array('name' => '合同列表',		'act'=>'contract_list','op'=>'System'),
				)),
				array('name' => '数据备份','child' => array(
					array('name' => '数据备份', 	'act'=>'index', 'op'=>'Tools'),
					array('name' => '数据还原', 	'act'=>'restore', 'op'=>'Tools'),
				)),
				array('name' => '权限管理','child' => array(
					array('name' => '权限板块列表',	'act'=>'plate_list','op'=>'System'),
					array('name' => '权限功能列表',	'act'=>'right_list','op'=>'System'),
					array('name' => '角色管理', 	'act'=>'role', 'op'=>'Admin'),
					array('name' => '管理员列表', 	'act'=>'index', 'op'=>'Admin'),
					array('name' => '管理员日志', 	'act'=>'log', 'op'=>'Admin'),
				)),
				array('name' => '渠道数据','child' => array(
					array('name' => '渠道统计', 	'act'=>'download', 'op'=>'Report'),
					array('name' => '下载统计', 	'act'=>'download_statisticss', 'op'=>'Report'),
				)),
				array('name' => '应用概况','child' => array(
					array('name' => '应用概况', 	'act'=>'welcome', 'op'=>'Report'),
				)),
				array('name' => '充值配置','child' => array(
					array('name' => '充值分类', 	'act'=>'refill_class', 'op'=>'Refillcard'),
					array('name' => '充值配置', 	'act'=>'refill_lists', 'op'=>'Refillcard'),
					array('name' => '兑换设置', 	'act'=>'conversionLise', 'op'=>'Refillcard'),
					array('name' => '订单列表', 	'act'=>'orderlist', 'op'=>'Refillcard'),
				)),
	)),
	'shop'=>array('name'=>'商城','child'=>array(
					array('name' => '订单数据','child'=>array(
							array('name' => '集团数据', 	'act'=>'index', 'op'=>'Report'),
							array('name' => '礼至家居', 	'act'=>'index_home', 'op'=>'Report'),
							array('name' => '礼至礼品', 	'act'=>'index_gift', 'op'=>'Report'),
							array('name' => '一礼通', 	'act'=>'index_all', 'op'=>'Report'),
							array('name' => '红礼供应链', 	'act'=>'index_red', 'op'=>'Report'),
					)),
					array('name' => '商品管理','child' => array(
						array('name' => '商品分类', 'act'=>'categoryList', 'op'=>'Goods'),
	                  	array('name' => '场景分类', 'act'=>'scenarioCategoryList', 'op'=>'Goods'),//新增场景分类
						array('name' => '商品列表', 'act'=>'goodsList', 'op'=>'Goods'),
						array('name' => '商品模型', 'act'=>'goodsTypeList', 'op'=>'Goods'),
						array('name' => '商品规格', 'act' =>'specList', 'op' => 'Goods'),
						array('name' => '品牌列表', 'act'=>'brandList', 'op'=>'Goods'),
						array('name' => '商品评论','act'=>'index','op'=>'Comment'),
						array('name' => '商品咨询','act'=>'ask_list','op'=>'Comment'),
						array('name' => '商品回收站','act'=>'goodsDelete','op'=>'Goods'),
	                                    
					)),
					array('name' => '订单管理','child'=>array(
							array('name' => '订单列表', 	'act'=>'index', 'op'=>'Order'),
							array('name' => '发货单', 		'act'=>'delivery_list', 'op'=>'Order'),
							array('name' => '退货单', 		'act'=>'back_order_list', 'op'=>'Order'),
							array('name' => '订单日志', 	'act'=>'order_log', 'op'=>'Order'),
					)),
					array('name' => '促销管理','child'=>array(
						//	array('name' => '抢购管理', 	'act'=>'panic_buying', 'op'=>'Promotion'),
						//	array('name' => '活动类型管理', 'act'=>'cate_list', 'op'=>'Promotion'),
						//	array('name' => '折扣活动列表', 'act'=>'activity_list', 'op'=>'Promotion'),
							array('name' => '折扣/秒杀', 'act'=>'discount_list', 'op'=>'Promotion'),
							array('name' => '预约活动', 	'act'=>'appointment_list', 'op'=>'Promotion'),
							array('name' => '拼单活动', 	'act'=>'share_the_bill_list', 'op'=>'Promotion'),
							array('name' => '满减活动', 	'act'=>'prom_goods_list', 'op'=>'Promotion'),
							array('name' => '优惠券', 'act'=>'index', 'op'=>'Coupon'),
							array('name' => '礼品卡', 'act'=>'index', 'op'=>'Code'),
							array('name' => '活动专区', 'act'=>'index', 'op'=>'Dedicated'),
					)),
					// array('name' => '统计报表','child'=>array(
					// 		array('name' => '销售概况', 	'act'=>'index', 'op'=>'Report'),
					// 		array('name' => '销售排行', 	'act'=>'saleTop', 'op'=>'Report'),
					// 		array('name' => '销售明细', 	'act'=>'saleList', 'op'=>'Report'),
					// )),
					array('name' => '用户管理','child'=>array(
							array('name' => '用户列表','act'=>'index','op'=>'User'),
							//array('name' => '用户等级','act'=>'rankList','op'=>'User'),
							array('name' => '用户统计', 'act'=>'user', 'op'=>'Report'),
							array('name' => '用户排行', 'act'=>'userTop', 'op'=>'Report'),
					)),
					array('name' => '线上合同','child'=>array(
							array('name' => '合同模板',		'act'=>'contract_form_list','op'=>'System'),
							array('name' => '合同列表',		'act'=>'contract_list','op'=>'System'),
					)),
					array('name' => '分销管理','child'=>array(
							array('name' => '业务等级','act'=>'rankList','op'=>'Business'),
							array('name' => '市级代理','act'=>'cityAgent','op'=>'Business'),
							array('name' => '区级代理','act'=>'districtAgent','op'=>'Business'),
							array('name' => '区域经理','act'=>'bsConsultant','op'=>'Business'),
							array('name' => '礼品店主','act'=>'shopkeeper','op'=>'Business'),
							array('name' => '商品订单分成','act'=>'orderDividedList','op'=>'Business'),
							array('name' => '礼豆列表','act'=>'beanGiftList','op'=>'Business'),
							array('name' => '礼金列表','act'=>'cashGiftList','op'=>'Business'),
							array('name' => '销售奖列表','act'=>'saleGiftList','op'=>'Business'),
							array('name' => '开发奖列表','act'=>'openGiftList','op'=>'Business'),
							array('name' => '提现申请','act'=>'bsWithdrawals','op'=>'Business'),
							
					)),
					array('name' => '分销2019','child'=>array(
							array('name' => '店主列表','act'=>'shopkeeper','op'=>'Sales'),
							array('name' => '订单列表','act'=>'orders','op'=>'Sales'),
							// array('name' => '礼豆列表','act'=>'peas','op'=>'Sales'),
							array('name' => '提现申请','act'=>'withdraw','op'=>'Sales'),
							array('name' => '佣金设置','act'=>'set','op'=>'Sales'),
							array('name' => '奖励金发放列表','act'=>'bonus_list','op'=>'Sales'),
					)),

	)),
	
	'supplier'=>array('name'=>'入驻商家','child'=>array(
				array('name' => '入驻商管理','child' => array(
					array('name' => '入驻商列表', 	'act'=>'BusinessList', 'op'=>'Supplier'),
					array('name' => '入驻商审核', 	'act'=>'BusinessExamine', 'op'=>'Supplier'),
					array('name' => '入驻商商品', 	'act'=>'suppliergoods', 'op'=>'Goods'),
					//array('name' => '企业采集审核', 	'act'=>'PurchaseExamine', 'op'=>'Supplier'),
					array('name' => '询报价管理', 	'act'=>'PurchaseExamine2', 'op'=>'Supplier'),//新增完善中
					array('name' => '入驻商订单列表', 'act'=>'supplier_order', 'op'=>'Order'),
					array('name' => '入驻商日志',   'act'=>'supplier_log', 'op'=>'Supplier'),
					array('name' => '商家结算', 	'act'=>'supplier_settlement_list', 'op'=>'Supplier'),
					array('name' => '兑换设置',   'act'=>'exchange_goods', 'op'=>'Supplier'),
					array('name' => '兑换记录',   'act'=>'exchange_log', 'op'=>'Supplier'),
				)),
				array('name' => '设计师管理','child' => array(
					array('name' => '设计师列表',  'act'=>'DesignerList', 'op'=>'Designer'),
					array('name' => '设计师审核',  'act'=>'DesignerExamine', 'op'=>'Designer'),
				    array('name' => '设计师分类',  'act'=>'CategoryList', 'op'=>'Designer'),
					array('name' => '作品分类',  'act'=>'WorksCategoryList', 'op'=>'Designer'),
					array('name' => '设计师作品',  'act'=>'WorksList', 'op'=>'Designer'),
					array('name' => '设计师商品',  'act'=>'DesignerGoods', 'op'=>'Goods'),
					array('name' => '设计师订单列表', 'act'=>'DesignerOrder', 'op'=>'Order'),
					array('name' => '设计师日志',  'act'=>'DesignerLog', 'op'=>'Designer'),
					array('name' => '设计师提现',  'act'=>'WithdrawalsLog', 'op'=>'Designer'),
				)),
	
	)),
	'ad'=>array('name'=>'广告管理','child'=>array(
				array('name' => '电脑端广告','child' => array(
					array('name' => '广告列表', 	'act'=>'adList', 'op'=>'Ad'),
					array('name' => '广告位置', 	'act'=>'positionList', 'op'=>'Ad'),
				)),
				array('name' => '移动端广告','child' => array(
					array('name' => 'Banner广告', 	'act'=>'adList', 'op'=>'Ad','id'=>'12'),
					array('name' => '首页单张广告', 	'act'=>'adList', 'op'=>'Ad','id'=>'39'),
					array('name' => '首页金刚区', 	'act'=>'adList', 'op'=>'Ad','id'=>'27'),
					array('name' => '品牌推荐', 	'act'=>'brandList', 'op'=>'Ad'),
					array('name' => '送礼攻略', 	'act'=>'giftsCategoryList', 'op'=>'Ad'),
					array('name' => '店铺精选', 	'act'=>'supplierRecommendList', 'op'=>'Ad'),
				)),
				array('name' => '文章管理','child' => array(
					array('name' => '文章列表', 	'act'=>'articleList', 'op'=>'Article'),
					array('name' => '文章分类', 	'act'=>'categoryList', 'op'=>'Article'),
					array('name' => '评论列表', 	'act'=>'commentList', 'op'=>'Article'),
				)),
	)),
	'a_gen'=>array('name'=>'一创物料库','child'=>array(
				array('name' => '物料商品','child' => array(
					array('name' => '商品列表', 	'act'=>'a_gen_list', 'op'=>'Agen'),
					array('name' => '商品分类', 	'act'=>'categoryList', 'op'=>'Agen'),
					array('name' => '商品模型', 	'act'=>'goodsTypeList', 'op'=>'Agen'),
				)),
				array('name' => '物料订单','child' => array(
					array('name' => '订单列表', 	'act'=>'orderIndex', 'op'=>'Agen'),
				)),
				array('name' => '反馈建议','child' => array(
					array('name' => '反馈列表', 	'act'=>'suggestList', 'op'=>'Agen'),
				)),
				array('name' => '用户管理','child' => array(
					array('name' => '用户列表', 	'act'=>'userList', 'op'=>'Agen'),
					array('name' => '操作日志', 	'act'=>'operation', 'op'=>'Agen'),
				)),

	)),
	'RedGift'=>array('name'=>'红礼供应链','child'=>array(
				array('name' => '供货商家','child' => array(
					array('name' => '商家列表', 	'act'=>'BusinessList', 'op'=>'RedGift'),
					array('name' => '商家日志', 	'act'=>'BusinessLog', 'op'=>'RedGift'),
				)),
				array('name' => '物料商品','child' => array(
					array('name' => '商品列表', 	'act'=>'a_red_list', 'op'=>'RedGift'),
					array('name' => '商品回收站', 	'act'=>'goodsDelete', 'op'=>'RedGift'),
					// array('name' => '商品分类', 	'act'=>'red_categoryList', 'op'=>'RedGift'),
					// array('name' => '商品模型', 	'act'=>'red_goodsTypeList', 'op'=>'RedGift'),
				)),
				array('name' => '物料订单','child' => array(
					array('name' => '订单列表', 	'act'=>'red_orderIndex', 'op'=>'RedGift'),
				)),
				array('name' => '反馈建议','child' => array(
					array('name' => '反馈列表', 	'act'=>'red_suggestList', 'op'=>'RedGift'),
				)),
				array('name' => '用户管理','child' => array(
					array('name' => '用户列表', 	'act'=>'red_userList', 'op'=>'RedGift'),
					array('name' => '操作日志', 	'act'=>'red_operation', 'op'=>'RedGift'),
				)),
	)),
	'RiteHome'=>array('name'=>'礼至家居','child'=>array(
				array('name' => '家居订单','child' => array(
					array('name' => '订单列表', 	'act'=>'inquire_index', 'op'=>'RiteHome'),
				)),
				array('name' => '家居用户','child' => array(
					array('name' => '用户列表', 	'act'=>'user_index', 'op'=>'RiteHome'),
				)),
				array('name' => '家电预配','child' => array(
					array('name' => '山水郡表单',	'act'=>'hengda','op'=>'System'),
					array('name' => '御景表单',		'act'=>'hdyujing','op'=>'System'),
				)),
	)),
	'visitor'=>array('name'=>'游客','child'=>array(
				array('name' => '商品','child' => array(
					array('name' => '商品列表', 'act'=>'visitor_goods', 'op'=>'Visitor'),
				)),

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