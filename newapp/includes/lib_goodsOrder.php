<?php

/**
 * ECSHOP 购物流程函数库
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: lib_order.php 17217 2011-01-19 06:29:08Z liubo $
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

/*
 * 获取订单列表
 *
 */
function get_orders_list($where,$files='',$limit='',$order=''){
	$where=" 1=1 AND ".$where;
	$order=empty($order) ? ' order_id DESC ' : $order;
	$files=empty($files) ? ' * ' :$files;
	if(!empty($limit)){
		$sql = "SELECT $files ".
			"FROM ".$GLOBALS['ecs']->table('order_info') .
			" WHERE $where " .
//				" parent_id IN(SELECT order_id FROM " .$GLOBALS['ecs']->table('order_info'). " WHERE $where ) " .
			" ORDER BY $order" .
			" LIMIT $limit";
	}else{
		$sql = "SELECT $files ".
			"FROM ".$GLOBALS['ecs']->table('order_info') .
			" WHERE $where " .
//				" parent_id IN(SELECT order_id FROM " .$GLOBALS['ecs']->table('order_info'). " WHERE $where ) " .
			" ORDER BY $order" ;
	}

	$list = $GLOBALS['db']->getALL($sql);
	return $list;
}

/**获取订单个数 **/
function get_orders_count($where)
{
	$where=" 1=1 AND ".$where;
	$sql = 'SELECT COUNT(*) as count FROM ' .$GLOBALS['ecs']->table('order_info').
		" WHERE $where ";
	return $GLOBALS['db']->getOne($sql);
}

/***获取order_gooods***/
function get_orders_goods($where,$files='',$limit='',$order=''){
	$where=" 1=1 AND ".$where;
	$order=empty($order) ? ' rec_id DESC ' : $order;
	$files=empty($files) ? ' * ' :$files;
	if(!empty($limit)){
		$sql = "SELECT $files ".
				"FROM ".$GLOBALS['ecs']->table('order_goods') .
				" AS A LEFT JOIN ".$GLOBALS['ecs']->table('goods') .
				" AS B ON B.goods_id = A.goods_id " .
				" WHERE $where" .
				" ORDER BY $order" .
				" LIMIT $limit";
	}else{
		$sql = "SELECT $files,B.goods_thumb ".
				"FROM ".$GLOBALS['ecs']->table('order_goods') .
				" AS A LEFT JOIN ".$GLOBALS['ecs']->table('goods') .
				" AS B ON B.goods_id = A.goods_id " .
				" WHERE $where" .
				" ORDER BY A.$order" ;
	}
	$list = $GLOBALS['db']->getALL($sql);

	return $list;
}


/**获取商品个数 **/
function get_goods_count($where)
{
	$where=" 1=1 AND ".$where;
	$sql = 'SELECT COUNT(*) as count FROM ' .$GLOBALS['ecs']->table('order_goods').
	" WHERE $where ";
	return $GLOBALS['db']->getOne($sql);
}

/* 判断此订单是否有子订单 liujiyuan	2016-1-4   15:06*/
function get_order_info_son($where){
	$where=" 1=1 AND ".$where;
	$sql = "SELECT order_id ".
		"FROM ".$GLOBALS['ecs']->table('order_info') .
		" WHERE $where" ;
	$order_info_son = $GLOBALS['db']->getROW($sql);

	return $order_info_son;

}


?>