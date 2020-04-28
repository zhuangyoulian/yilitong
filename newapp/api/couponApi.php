<?php
define('IN_ECS', true);
require('init.php');
header("Content-Type:text/html;charset=UTF-8");
$action  = $_REQUEST['act'];
$ticket = $_REQUEST['ticket'];
$userinfo = '';
if(!empty($ticket)){
	$userinfo = split_user_ticket($ticket);
}
//获取优惠劵，和已领取优惠劵列表
if($action=="coupon_list"){
	$temptime=time();
	
	$where=" send_start_time <= {$temptime} AND send_end_time>={$temptime}";
	$sql = "SELECT * ".
			" FROM " .$GLOBALS['ecs']->table('coupon').
			" WHERE is_display = 1 AND {$where}";
	$res = $GLOBALS['db']->getAll($sql);
	
	$temp=array();
	if(!empty($res)){
		foreach ($res as $val){
			$temp[$key]=$val;
			$temp[$key]['cid']=$val['id'];
			$temp[$key]['note']="满{$val[condition]}元可用";
			$temp[$key]['use_start_time']=!empty($val['use_start_time']) ? date('Y-m-d',$val['use_start_time']) :'';
			$temp[$key]['use_end_time']=!empty($val['use_end_time']) ? date('Y-m-d',$val['use_end_time']) : '';
		}
	}
	if(!empty($temp)){
		$results = array(
				'result' => 1,
				'info' =>'请求成功',
				'list' =>$temp//	商品数据
		);
		exit($json->json_encode_ex($results));
	}
	$results = array(
			'result' =>1,
			'info'=>'无数据',
			'list' =>$temp
	);
	exit($json->json_encode_ex($results));
}

//领取优惠劵
elseif($action=="get_coupon"){
	$userid=!empty($userinfo['userid']) ? $userinfo['userid'] : '';
	$cid=!empty($_REQUEST['cid']) ? $_REQUEST['cid'] :'';
	if(empty($userid) || empty($cid)){
		$results = array(
				'result' =>0,
				'info'=>'缺少参数',
		);
		exit($json->json_encode_ex($results));
	}
	//查看优惠劵是否可用领取
	$temptime=time();
	$where=" send_start_time <= {$temptime} AND send_end_time>={$temptime}";
	$sql = "SELECT * ".
			" FROM " .$GLOBALS['ecs']->table('coupon').
			" WHERE is_display = 1 AND {$where}";
	
	$rs = $GLOBALS['db']->getRow($sql);
	if(empty($rs)){
		$results = array(
				'result' =>0,
				'info'=>'优惠劵暂不能领用',
		);
		exit($json->json_encode_ex($results));
	}

	//查看是否领取优惠劵
	$sql="select id from ".$GLOBALS['ecs']->table('coupon_list')." where cid={$cid} AND uid={$userid}";
	$row=$GLOBALS['db']->getRow($sql);
	if(!empty($row)){
		$results = array(
				'result' =>0,
				'info'=>'此优惠劵已领取',
		);
		exit($json->json_encode_ex($results));
	}
	//领取优惠劵
	$sql="insert INTO ".$GLOBALS['ecs']->table('coupon_list')."(uid,type,cid,send_time) values ({$userid},'{$rs[type]}',{$cid},'{$temptime}')";
	$one=$GLOBALS['db']->query($sql);
	if($one){
		$results = array(
				'result' =>1,
				'info'=>'优惠劵领取成功',
		);
	}else{
		$results = array(
				'result' =>0,
				'info'=>'优惠劵领取失败',
		);
	}
	exit($json->json_encode_ex($results));
}

//获取用户已领取的优惠劵
else if($action=='user_coupon_list'){
	$userid=!empty($userinfo['userid']) ? $userinfo['userid'] : '';
	if (empty($userid)) {
		$results = array(
				'result' =>0,
				'info'=>'未登录',
		);
		exit($json->json_encode_ex($results));
	}
	$temptime=time();
	$status = !empty($_REQUEST['status']) ? $_REQUEST['status'] : '0';
	$page=!empty($_REQUEST['page']) ? $_REQUEST['page'] : '0';
	$size =10;
	$begin = $page*$size;
	$limit = " LIMIT $begin,$size";
	
	if($status==1){
		//已使用
		$where = ' A.use_time >0 ';
	}elseif($status==2){
		//已过期
		$where = " A.use_time =0 AND B.use_end_time<'{$temptime}' ";
	}else{
		//未使用
		$where = " A.use_time =0 AND B.use_end_time>'{$temptime}' ";
	}
	//优惠劵列表1
	$file="B.goods_id,B.supplier_id,B.id,A.cid,A.type,A.uid,A.order_id,A.use_time,A.send_time,B.money,B.condition,B.use_start_time,B.use_end_time,B.name as coupon_name";
	$sql="select {$file} from ".$GLOBALS['ecs']->table('coupon_list')." as A LEFT JOIN ".$GLOBALS['ecs']->table('coupon')." as B ON A.cid=B.id where {$where} AND A.uid={$userid} order BY B.use_end_time $limit ";

	$rs=$GLOBALS['db']->getAll($sql); 
	
	$arr=array();
	if(!empty($rs)){
		foreach ($rs as $key => $val){
			$sql = "select supplier_name from ".$GLOBALS['ecs']->table('supplier')." where supplier_id ={$val['supplier_id']}";
			$supplier_name = $GLOBALS['db']->getOne($sql); 
			if ($val['goods_id']) {
				$supplier= $supplier_name."部分商品适用";
			}else{
				$supplier= $supplier_name."全部商品适用";
			}
			$arr[$key]['supplier']=$supplier;
			$arr[$key]['cid']=$val['id'];
			$arr[$key]['money']=$val['money'];
			$arr[$key]['note']="满{$val[condition]}可用";
			$arr[$key]['coupon_name']=$val['coupon_name'];
			$arr[$key]['use_start_time']=!empty(date("Y.m.d",$val['use_start_time'])) ? date("Y.m.d",$val['use_start_time']) : '';
			$arr[$key]['use_end_time']=!empty(date("Y.m.d",$val['use_end_time'])) ? date("Y.m.d",$val['use_end_time']) : '';
			
		}
	}	
	
	$sql="select count(*) from ".$GLOBALS['ecs']->table('coupon_list')." as A LEFT JOIN ".$GLOBALS['ecs']->table('coupon')." as B ON A.cid=B.id where uid={$userid} AND B.is_display=1 ";
	$goods_count=$GLOBALS['db']->getOne($sql); 
	$allpage=ceil($goods_count/$size);
	if(!empty($arr)){
		$results = array(
				'result' =>1,
				'info'=>'成功',
				'list'=>$arr,
				'page'=>$page,
				'count'=>$allpage,
				'size'=>$size
		);
	}else{
		$results = array(
				'result' =>1,
				'info'=>'无数据',
				'list'=>$arr,
				'page'=>$page,
				'count'=>$allpage,
				'size'=>$size
		);
	}
	exit($json->json_encode_ex($results));
}
//可用优惠券的商品列表
else if($action=='coupon_goods_list'){
	$temptime=time();
	$sort = (isset($_REQUEST['sort'])  && in_array(trim(strtolower($_REQUEST['sort'])), array( 'shop_price', 'sales_sum','is_new'))) ? trim($_REQUEST['sort'])  : 'sort';
	$orderby = (isset($_REQUEST['orderby']) && in_array(trim(strtoupper($_REQUEST['orderby'])), array('ASC', 'DESC'))) ? trim($_REQUEST['orderby']) : 'ASC';
	
	$cid = !empty($_REQUEST['cid']) ? $_REQUEST['cid'] : '0';
	$page=!empty($_REQUEST['page']) ? $_REQUEST['page'] : '0';
	$size =20;
	$begin = $page*$size;
	$limit = " LIMIT $begin,$size";
	$sql = "select * from ".$GLOBALS['ecs']->table('coupon')." where id = {$cid} AND use_end_time >{$temptime} AND `use` <> 1" ;
	
	$coupon = $GLOBALS['db']->getRow($sql);

	if ($coupon) {
		if ($coupon['goods_id']) {
			$sql = "select goods_thumb,goods_name,shop_price,goods_id from ".$GLOBALS['ecs']->table('goods')." where goods_id in ({$coupon['goods_id']}) order by {$sort} {$orderby} {$limit}";
			$sql1 = "select COUNT(goods_id) from ".$GLOBALS['ecs']->table('goods')." where goods_id in ({$coupon['goods_id']}) ";
		}else{
			$sql = "select goods_thumb,goods_name,shop_price,goods_id from ".$GLOBALS['ecs']->table('goods')." where supplier_id={$coupon['supplier_id']} order by {$sort} {$orderby} {$limit}";
			$sql1 = "select COUNT(goods_id) from ".$GLOBALS['ecs']->table('goods')." where supplier_id={$coupon['supplier_id']} ";
		}
		$goods_list = $GLOBALS['db']->getAll($sql);
		
		
		if (empty($goods_list)) {
			$goods_list=array();
		}else{
			foreach ($goods_list as $k =>$v){
				if ($v['goods_thumb']) {
					$goods_list[$k]['goods_thumb']=IMG_HOST.$v['goods_thumb'];
				}else{
					$goods_list[$k]['goods_thumb']="";
				}
			}
		}
		$goods_count=$GLOBALS['db']->getOne($sql1);
		$allpage=ceil($goods_count/$size);
		$results = array(
			'result' =>1,
			'info'=>'请求成功',
			'goods_list'=>$goods_list,
			'coupon_name'=>$coupon['name'],
			'page'=>$page,
			'count'=>$allpage,
			'size'=>$size
		);
	}else{
		$results = array(
			'result' =>0,
			'info'=>'活动已结束',
		);
	}
	exit($json->json_encode_ex($results));

}




