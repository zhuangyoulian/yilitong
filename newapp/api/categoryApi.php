<?php
define('IN_ECS', true);
require('init.php');
$affiliate = unserialize($GLOBALS['_CFG']['affiliate']);
header("Content-Type:text/html;charset=UTF-8");
$action  = $_REQUEST['act'];
$ticket = $_REQUEST['ticket'];
$userinfo = '';
if(!empty($ticket)){
	$userinfo = split_user_ticket($ticket);
}
//送礼攻略
if($action=='gifts_category'){
	$sql="select id as g_id,name,image  from ".$GLOBALS['ecs']->table('gifts_category')."  where  is_show = 1 and parent_id = 0 order by sort_order desc";
	$gifts_list=$GLOBALS['db']->getAll($sql);
	foreach ($gifts_list as $k =>$v){
		$gifts_list[$k]['image']=IMG_HOST.$v['image'];
		$sql="select name,id as g_id,image from ".$GLOBALS['ecs']->table('gifts_category')."  where  is_show = 1 and parent_id = {$v['g_id']} order by sort_order desc";
		$list=$GLOBALS['db']->getAll($sql);
		foreach ($list as $key =>$value){
			$list[$key]['image']=IMG_HOST.$value['image'];
		}
		$gifts_list[$k]['cate_list']=$list;
	}
	$rs=array('result'=>'1','info'=>'请求成功','gifts_list'=>$gifts_list);
	exit($json->json_encode_ex($rs));
}
//送礼攻略商品列表
elseif($action=='gifts_goodslist'){
	$gifts_id=$_REQUEST['g_id'];
	$sort = (isset($_REQUEST['sort'])  && in_array(trim(strtolower($_REQUEST['sort'])), array( 'shop_price', 'sales_sum','is_new'))) ? trim($_REQUEST['sort'])  : 'sort';
	$orderby = (isset($_REQUEST['orderby']) && in_array(trim(strtoupper($_REQUEST['orderby'])), array('ASC', 'DESC'))) ? trim($_REQUEST['orderby']) : 'ASC';
	
	$where=" WHERE is_on_sale=1 AND examine = 1 AND is_designer = 0 AND 1=1";
    $where  .= " and goods_id !=5898";//预约产品id限制 修改日期2020.3.10
	$page=!empty($_REQUEST['page']) ? $_REQUEST['page'] : 0;
	$size =10;
	
	$begin = $page*$size;
	$limit = " LIMIT $begin,$size";
	//按条件获取商品列表

	$sql="select goods_id  from ".$GLOBALS['ecs']->table('gifts_category')."  where  is_show = 1 and id={$gifts_id}";
	$goods_id=$GLOBALS['db']->getOne($sql);
	if ($goods_id) {
		$where .=" AND goods_id in ({$goods_id}) ";
		$sql = "select COUNT(goods_id) from " .$GLOBALS['ecs']->table('goods'). "  {$where} ";
		$goods_count = $GLOBALS['db']->getOne($sql);
		$allpage=ceil($goods_count/$size);
		
		$sql="SELECT goods_id,goods_name,shop_price,market_price,sales_sum,goods_thumb as original_img from ".$GLOBALS['ecs']->table('goods')." {$where} order by {$sort} {$orderby} {$limit}";
		$list=$GLOBALS['db']->getAll($sql);
		if ($list) {
			foreach($list as $k =>$v){
				$list[$k]['original_img'] = IMG_HOST.$v['original_img'];
			}
			$rs=array('result'=>'1','info'=>'请求成功','goods_list'=>$list,'page' => $page,	'count' => $allpage,'size' => $size);
		}else{
			$list=array();
			$rs=array('result'=>'1','info'=>'请求成功','goods_list'=>$list,'page' => $page,	'count' => $allpage,'size' => $size);
		}
	}else{
		$rs=array('result'=>'0','info'=>'暂无商品','goods_list'=>$list,'page' => $page,	'count' => $allpage,'size' => $size);
	}
	exit($json->json_encode_ex($rs));
}
//二级分类
elseif($action=='category_list'){
	
	$categoryId=$_REQUEST['id'];
	
	$sql="select id as g_id,mobile_name,image  from ".$GLOBALS['ecs']->table('goods_category')."  where  id = {$categoryId}  order by sort_order desc";
	$category=$GLOBALS['db']->getRow($sql);
	if($category){
		$category['image']=IMG_HOST.$category['image'];
		$sql="select id as g_id,mobile_name,image,cat_jump from ".$GLOBALS['ecs']->table('goods_category')."  where  parent_id = {$categoryId} and is_show = 1 order by sort_order desc";
		$list=$GLOBALS['db']->getAll($sql);
		foreach ($list as $key =>$value){
			$list[$key]['image']=IMG_HOST.$value['image'];
			$list[$key]['g_id'] = $value['cat_jump'] ? $value['cat_jump'] : $value['g_id'];
		}
		$category['cate_list']=$list;
	}
	$rs=array('result'=>'1','info'=>'请求成功','category'=>$category);
	exit($json->json_encode_ex($rs));
}



?>