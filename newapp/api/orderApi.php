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
/***
 * auth:cx
 * 获取订单列表
 */
if($action=='order_list'){
	$userid=$userinfo['userid'];
	$type= $_REQUEST['type'] ;
	$page=!empty($_REQUEST['page']) ? $_REQUEST['page'] : 0 ;
	$size=10;
	//检测未支付订单是否超过支付时间，活动订单三十分钟，其他订单2天
	check_nopay_order($userid);
	$list=get_order_list($userid,$type,$page,$size);
	$order_count=!empty($rs['goods_count']) ? $rs['goods_count'] : '0';
	$number=$list['count'];
	$goods_num=$list['goods_num'];
	$all_price=$list['all_price'];
	unset($list['goods_num']);
	unset($list['goods_count']);
	unset($list['count']);
	unset($list['all_price']);
	if(!empty($list['list'])){
		$rs=array('result'=>'1','info'=>'请求成功','list'=>$list['list'],'page'=>$page,'size'=>$size,'count'=>$count,'goods_num'=>$goods_num,'all_price'=>$all_price,'order_count'=>$order_count);
		exit($json->json_encode_ex($rs));
	}else{
		$rs=array('result'=>'1','info'=>'无数据');
		exit($json->json_encode_ex($rs));
	}
}
//订单隐藏
elseif($action=="order_hiding"){
	$order_id=$_REQUEST['order_id'];
	$userid=$userinfo['userid'];

	if ($userid &&$order_id) {
		$flag=false;
		$sql = "UPDATE " . $GLOBALS['ecs']->table('order') . " set is_delete = 1 WHERE  order_status in (2,3,4,5) AND user_id={$userid} AND order_id='{$order_id}'";
		$flag=$GLOBALS['db']->query($sql);	
		if($flag){
			$rs=array('result'=>'1','info'=>'订单已删除');
		}else{
			$rs=array('result'=>'0','info'=>'删除失败');
		}
	}else{
		$rs=array('result'=>'0','info'=>'删除失败');
	}
	exit($json->json_encode_ex($rs));
}

/***
 * auth:cx
 * 获取订单详情接口
 */
elseif($action=="order_info"){
	$order_id=$_REQUEST['order_id'];
	$userid=$userinfo['userid'];
	
	$list=get_order_info2($userid,$order_id);
	$sql = "select logistics_information from ".$GLOBALS['ecs']->table('shipping_order') . " where order_id = {$order_id}";
	$logistics_information=$db->getOne($sql);
	if ($logistics_information) {
		$logistics_information = json_decode($logistics_information,true);
		$list['shipping_info']=$logistics_information['lastResult']['data'][0]['context'];
	}else{
		$list['shipping_info']='暂无物流信息';
	}
	
	
	
	
	//$order=get_order_info($userid,$order_id);
	if(!empty($list['goods_list'])){
		$rs=array('result'=>'1','info'=>'请求成功','order'=>$list);
		exit($json->json_encode_ex($rs));
	}else{
		$rs=array('result'=>'1','info'=>'无数据','order'=>array());
		exit($json->json_encode_ex($rs));
	}
}
//可评论的订单商品列表
elseif($action=="comment_list"){
	$order_id=$_REQUEST['order_id'];
	$sql = "select O.goods_id,O.goods_name ,G.goods_thumb,O.spec_key_name from ".$GLOBALS['ecs']->table('order_goods') . "as O left join ".$GLOBALS['ecs']->table('goods')." as G ON O.goods_id=G.goods_id where O.order_id={$order_id} AND O.is_comment = 0";
	$goods_list=$db->getAll($sql);
	
	foreach ($goods_list as $key =>$val){
		if ($val['goods_thumb']) {
			$goods_list[$key]['goods_thumb']=IMG_HOST.$val['goods_thumb'];
		}
	}
	if($goods_list){
		$rs=array('result'=>'1','info'=>'请求成功','goods_list'=>$goods_list);
		exit($json->json_encode_ex($rs));
	}else{
		$goods_list=array();
		$rs=array('result'=>'1','info'=>'无可评论的商品','goods_list'=>$goods_list);
		exit($json->json_encode_ex($rs));
	}
}
//二级可评论的订单商品列表
elseif($action=="second_comment_list"){
	$order_id=$_REQUEST['order_id'];
	$userid=$userinfo['userid'];

	$sql = "select O.goods_id,O.goods_name ,G.goods_thumb,O.spec_key_name,O.is_comment from ".$GLOBALS['ecs']->table('order_goods') . "as O left join ".$GLOBALS['ecs']->table('goods')." as G ON O.goods_id=G.goods_id where O.order_id={$order_id} and O.is_comment<>0";
	
	$goods_list=$db->getAll($sql);
	foreach ($goods_list as $key =>$val){
		$comments_list = array();
		$sql = "select content,goods_rank as deliver_rank,add_time,img,spec_key_name from ".$GLOBALS['ecs']->table('comment') ." where goods_id = {$val['goods_id']} AND order_id = {$order_id}  AND user_id = {$userid}  AND spec_key_name = '{$val['spec_key_name']}'";
		
		$comments=$db->getAll($sql);
		if ($comments) {
			$goods_list[$key]['comments'] = $comments;
			foreach ($goods_list[$key]['comments'] as $k =>$v)
			if($v['img']){
				$arr=unserialize($v['img']);
				if(!empty($arr) && is_array($arr)){
					foreach ($arr as $key1 =>$val1){
						if(!empty($val1)){
							if(strpos($val1,'yilitong.com') !== false){
								$arr[$key1]=$val1;
							}else{
								$arr[$key1]=IMG_HOST.$val1;
							}
						}
					}
					$goods_list[$key]['comments'][$k]['img']=$arr;
					
				}else{
					$goods_list[$key]['comments'][$k]['img']=array();
				}
			}else{
				$goods_list[$key]['comments'][$k]['img']=array();
			}
		}else{
			$goods_list[$key]['comments'] = $comments_list;
		}
		if ($val['goods_thumb']) {
			$goods_list[$key]['goods_thumb']=IMG_HOST.$val['goods_thumb'];
		}
		
	}
	if($goods_list){
		$rs=array('result'=>'1','info'=>'请求成功','goods_list'=>$goods_list);
		exit($json->json_encode_ex($rs));
	}else{
		$goods_list=array();
		$rs=array('result'=>'0','info'=>'无可评论的商品','goods_list'=>$goods_list);
		exit($json->json_encode_ex($rs));
	}
}
/**
 * cx
 * by2017.4.13
 * 取消订单接口
 * 
 */
elseif($action=='cancel_order'){
	$userid=$userinfo['userid'];
	$order_id=$_REQUEST['order_id'];
	$sql="select count(*) from ".$GLOBALS['ecs']->table('order')." where order_id={$order_id} AND user_id={$userid} AND pay_status = 0 AND order_status = 0 ";
	$count=$db->getOne($sql);
	$flag=false;
	if($count>0){
	  $sql = "UPDATE " . $GLOBALS['ecs']->table('order') . " set order_status = 3 WHERE user_id={$userid} AND order_id='{$order_id}'";
	  $flag=$GLOBALS['db']->query($sql);	
	}
	if($flag){
		$rs=array('result'=>'1','info'=>'订单已取消');
		exit($json->json_encode_ex($rs));
	}else{
		$rs=array('result'=>'0','info'=>'取消失败');
		exit($json->json_encode_ex($rs));
	}
}

/***
 * 
 * 确认收货接口
 * 
 * 
 */
elseif($action=='conf_order'){
	$userid=$userinfo['userid'];
	$order_id=$_REQUEST['order_id'];
	$sql="select order_sn,is_designer,order_id from ".$GLOBALS['ecs']->table('order')." where order_id={$order_id} AND user_id={$userid} AND pay_status = 1 AND order_status = 1 ";
	$order=$db->getRow($sql);
	$flag=false;
	if($order){
		if($order['is_designer'] == 1){
			$allow_time = time() +604800;
			$sql = "UPDATE " . $GLOBALS['ecs']->table('account_log') . " set allow_time = {$allow_time} , sign_status = 1 WHERE  order_sn=".$order['order_sn'];
			$flag=$GLOBALS['db']->query($sql);
		}
		$sql = "UPDATE " . $GLOBALS['ecs']->table('order') . " set order_status = 2 WHERE user_id={$userid} AND order_id=".$order['order_id'];
		$flag=$GLOBALS['db']->query($sql);
	}
	if($flag){
		$rs=array('result'=>'1','info'=>'操作成功');
		exit($json->json_encode_ex($rs));
	}else{
		$rs=array('result'=>'0','info'=>'操作失败');
		exit($json->json_encode_ex($rs));
	}
}

/**
 * 申请退款申请接口(待发货和待收货状态)
 * 
 */
elseif($action=='back_money'){
	$order_id=$_REQUEST['order_id'];
	$goods_id=$_REQUEST['goods_id'];
	$spec_key=$_REQUEST['spec_key'];
	$spec_key_name=$_REQUEST['spec_key_name'];
	$userid=$userinfo['userid'];
	$reason=$_REQUEST['reason'];
	if(empty($userinfo['userid']) || empty($order_id) || empty($goods_id)){
		$rs=array('result'=>'0','info'=>'缺少参数');
		exit($json->json_encode_ex($rs));
	}
	//$note=trim($_REQUEST['note']);
	//查询订单表
	//待发货状态  (pay_status=1 OR pay_code='cod') AND shipping_status !=1 AND order_status in(0,1)
	//待收货状态 (shipping_status=1 AND order_status = 1) 
	//$where="((pay_status=1 OR pay_code !='') AND shipping_status !=1 AND order_status in(0,1)) OR (shipping_status=1 AND order_status = 1) ";
	$where=" pay_status=1 ";
	$sql="SELECT parent_id,order_amount,order_id,order_sn,order_status,supplier_id,supplier_name from ".$GLOBALS['ecs']->table('order')." where order_id={$order_id} AND user_id={$userid} and ($where)";
	
	$order=$db->getRow($sql);
	if ($order['parent_id']!=0) {
		$sql="SELECT order_amount from ".$GLOBALS['ecs']->table('order')." where order_id={$order['parent_id']} ";
		$order['order_amount']=$db->getOne($sql);
	}
	if(!empty($spec_key)){
		$sql="SELECT goods_amount,order_id,goods_name,goods_id,goods_sn,goods_num,goods_price,spec_key from ".$GLOBALS['ecs']->table('order_goods')." where order_id={$order_id} AND goods_id={$goods_id} AND  spec_key='{$spec_key}'";
	}else{
		$sql="SELECT goods_amount,order_id,goods_name,goods_id,goods_sn,goods_num,goods_price,spec_key from ".$GLOBALS['ecs']->table('order_goods')." where order_id={$order_id} AND goods_id={$goods_id} ";
	}
	//查询订单商品表
	$goods=$db->getRow($sql);

	if(empty($order) || empty($goods)){
		$rs=array('result'=>'0','info'=>'申请失败');
		exit($json->json_encode_ex($rs));
	}
	if ($goods['goods_amount'] == 0) {
		$goods['goods_amount']=$goods['goods_price']*$goods['goods_num'];
	}
	//退货申请失败，删除以前的申请记录，重新申请
	check_back_goods($order_id,$order['order_sn'],$goods_id,$userid);
	
	//生成退货表
	$back_order=array(
			'order_id'=>$order_id,
			'order_sn'=>$order['order_sn'],
			'goods_id'=>$goods_id,
			'type'=>1,
			'reason'=>$reason,
			'imgs'=>'',
			'addtime'=>time(),
	        'status'=>0,
			'user_id'=>$userid,
			'spec_key'=>$goods['spec_key'],
			'supplier_id'=>$order['supplier_id'],
			'supplier_name'=>$order['supplier_name'],
			'shop_price'=>$goods['goods_amount'],
			'total_amount'=>$order['order_amount'],
	);
	
	//看退货单是否已经生成
	if(!empty($goods['spec_key'])){
		$sql="select count(*) from ".$GLOBALS['ecs']->table('back_order')." where order_id={$order_id} AND order_sn={$order[order_sn]} AND user_id={$userid} AND spec_key='{$goods[spec_key]}' AND goods_id = {$goods_id} AND status != 7 ";
	}else{
		$sql="select count(*) from ".$GLOBALS['ecs']->table('back_order')." where order_id={$order_id} AND order_sn={$order[order_sn]} AND user_id={$userid} AND goods_id = {$goods_id} AND status != 7 ";
	}
	$count=$db->getOne($sql);
	if($count){
		$rs=array('result'=>'0','info'=>'退款已申请');
		exit($json->json_encode_ex($rs));
	}
	$back_price=$goods['goods_amount']/$goods['goods_num'];
	$flag1=$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('back_order'), $back_order, 'INSERT');
	$new_order_id=$db->insert_id();
	if($flag1){
		//生成退货商品
		$back_goods=array(
				'back_id'=>$new_order_id,
				'goods_id'=>$goods_id,
				'goods_name'=>$goods['goods_name'],
				'goods_sn'=>$goods['goods_sn'],
				'send_number'=>$goods['goods_num'],
				'money'=>$goods['goods_amount'],
				'goods_price'=>$goods['goods_price'],
				'back_price'=>$back_price,
				'spec_key'=>$spec_key,
				'spec_key_name'=>$spec_key_name
		);
		$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('back_goods'), $back_goods, 'INSERT');
		
		//退货家标识
		if(!empty($spec_key)){
			$sql = "UPDATE ".$GLOBALS['ecs']->table('order_goods')." SET is_service = 1 WHERE order_id={$order_id} AND goods_id={$goods_id}  AND spec_key='{$spec_key}'";
		}else{
			$sql = "UPDATE ".$GLOBALS['ecs']->table('order_goods')." SET is_service = 1 WHERE order_id={$order_id} AND goods_id={$goods_id}";
		}
		$GLOBALS['db']->query($sql);
			
		$rs=array('result'=>'1','info'=>'申请成功');
		exit($json->json_encode_ex($rs));
	}
	
	
	$rs=array('result'=>'0','info'=>'申请失败');
	exit($json->json_encode_ex($rs));
	
}

/***
 * 申请退换货列表
 * 
 */
elseif ($action=="back_goods_list"){
	$userid = empty($userinfo['userid']) ? '' : $userinfo['userid'];
	$page=!empty($_REQUEST['page']) ? $_REQUEST['page']: 0;
	$size=5;
	$begin = $page*$size;
	$limit = " LIMIT $begin,$size";
	
	$sql="SELECT count(*) from " .$GLOBALS['ecs']->table('back_order')." as A left join ".$GLOBALS['ecs']->table('back_goods')." as B ON A.id=B.back_id where A.user_id={$userid}";
	$goods_count=$db->getOne($sql);

	$allpage=ceil($goods_count/$size);
	$file="A.order_id,A.id as oid,A.order_sn,A.goods_id,A.type,A.reason,A.addtime,A.status,A.spec_key,B.goods_name,B.send_number,B.is_real,B.money,A.supplier_id";
	
	$sql="SELECT {$file} from " .$GLOBALS['ecs']->table('back_order')." as A left join ".$GLOBALS['ecs']->table('back_goods')." as B ON A.id=B.back_id where A.user_id={$userid}  ORDER BY oid DESC {$limit}";
	
	$list=$db->getAll($sql);
	
	if(!empty($list)){
		foreach ($list as $key =>$val){
			$list[$key]=$val;
			$supper=get_supplier($val['supplier_id'],"A.supplier_id,A.supplier_name,A.company_name");
			if(!empty($supper)){
				$list[$key]['supplier_id']=$supper['supplier_id'];
				$list[$key]['supplier_name']=$supper['supplier_name'];
				$list[$key]['company_name']=$supper['supplier_name'];
			}else{
				$list[$key]['supplier_id']="";
				$list[$key]['supplier_name']="";
				$list[$key]['company_name']="";
			}
			$image=get_image_goods($val['goods_id']);
			$list[$key]['original_img']=!empty($image) ? IMG_HOST.$image : "";
			if(!empty($val['spec_key'])){
				$keyv=$val['spec_key'];
				$goods_id=$val['goods_id'];
				$sql="SELECT A.key_name from ".$GLOBALS['ecs']->table('goods_price')." as A where A.goods_id={$goods_id} AND A.key='{$keyv}'";
				$spec_key_name=$db->getOne($sql);
				$list[$key]['spec_key_name']=!empty($spec_key_name) ? $spec_key_name : "";
			}else{
				$list[$key]['spec_key_name']="";
			}
		}
	}
	if(!empty($list)){
		$results = array('result' =>1,'info' => '成功','list'=>$list,'page'=>$page,'count'=>$allpage);
		exit($json->json_encode_ex($results));
	}else{
		$results = array('result' =>1,'info' => '无数据','list'=>array(),'page'=>$page,'count'=>$allpage);
		exit($json->json_encode_ex($results));
	}
	
}


/***
 * 申请退换货接口
 */
elseif($action=='back_goods'){
	$type=$_REQUEST['type'];
	$number=!empty($_REQUEST['number']) ? $_REQUEST['number'] : '1';
	$money=$_REQUEST['money'];
	
	$shop_price = price_format($number* $money, false);
	$note=$_REQUEST['reason'];
	$userid = empty($userinfo['userid']) ? '' : $userinfo['userid'];
	$image_url=$_REQUEST['name'];
	
	
	$order_id=$_REQUEST['order_id'];
	$goods_id=$_REQUEST['goods_id'];
	$spec_key=$_REQUEST['spec_key'];
	$spec_key_name=$_REQUEST['spec_key_name'];
	
	 if(empty($number) || empty($type) || empty($order_id) || empty($goods_id)){
		$results = array('result' => 0,'info' => '缺少必要参数');
		exit($json->json_encode_ex($results));
	}
	if(empty($note)){
		$results = array('result' => 0,'info' => '请填写退换货说明');
		exit($json->json_encode_ex($results));
	}

	//查询订单表
	//待评价
	$where=" ( order_status = 2 or (shipping_status=1 AND order_status = 1)) AND pay_status=1 ";
	$sql="SELECT parent_id,order_amount,order_id,order_sn,order_status,supplier_id,total_amount from ".$GLOBALS['ecs']->table('order')." where order_id={$order_id} AND user_id={$userid} and $where";
	$order=$db->getRow($sql);
	
	if ($order['parent_id']!=0) {
		$sql="SELECT order_amount from ".$GLOBALS['ecs']->table('order')." where order_id={$order['parent_id']} ";
		$order['order_amount']=$db->getOne($sql);
	}
	
	if(!empty($spec_key)){
		$sql="SELECT goods_amount,order_id,goods_name,goods_id,goods_sn,goods_num,goods_price,spec_key from ".$GLOBALS['ecs']->table('order_goods')." where order_id={$order_id} AND goods_id={$goods_id} AND  spec_key='{$spec_key}'";
	}else{
		$sql="SELECT goods_amount,order_id,goods_name,goods_id,goods_sn,goods_num,goods_price,spec_key from ".$GLOBALS['ecs']->table('order_goods')." where order_id={$order_id} AND goods_id={$goods_id} ";
	}
	//查询订单商品表
	$goods=$db->getRow($sql);
	if ($goods['goods_amount']==0) {
		$goods['goods_amount']=$goods['goods_num']*$goods['goods_price'];
	}
	if(empty($order) || empty($goods)){
		$rs=array('result'=>'0','info'=>'申请失败');
		exit($json->json_encode_ex($rs));
	}
	//检测是否已经，拒绝退换货
	check_back_goods($order_id,$order['order_sn'],$goods_id,$userid,$spec_key);
//	$money=price_format($goods['goods_price']*$number, false);
	//生成退货表
	$back_order=array(
			'order_id'=>$order_id,
			'order_sn'=>$order['order_sn'],
			'goods_id'=>$goods_id,
			'type'=>$type,
			'reason'=>$note,
			'addtime'=>time(),
			'status'=>0,
			'user_id'=>$userid,
			'imgs'=>$image_url,
			'spec_key'=>$goods['spec_key'],
			'shop_price'=>$shop_price,
			'total_amount'=>$order['order_amount'],
			'supplier_id'=>$order['supplier_id']
	);
	$goods_num=$goods['goods_num'];
	//看退货单是否已经生成	
	if(!empty($spec_key)){
		//有规格id
		$sql="select id,status from ".$GLOBALS['ecs']->table('back_order')." where order_id={$order_id} AND goods_id={$goods_id} AND user_id={$userid}  AND spec_key='{$spec_key}' ";
	}else{
		$sql="select id,status from ".$GLOBALS['ecs']->table('back_order')." where order_id={$order_id} AND goods_id={$goods_id} AND user_id={$userid} ";
	}
	$back_list = $GLOBALS['db']->getAll($sql);

	if ($back_list) {
		foreach ($back_list as $key =>$v){
			if ($v['status'] != -1 && $v['status'] !=7) {
				$sql="select send_number from ".$GLOBALS['ecs']->table('back_goods')." where back_id={$v['id']} AND goods_id={$goods_id} AND spec_key='{$spec_key}' ";
				$back_goods = $GLOBALS['db']->getAll($sql);
				foreach ($back_goods as $key =>$v){
					$goods_num=$goods_num-$v['send_number'];
				}
			}
		}
	}

	
	if($goods_num<1){
		$rs=array('result'=>'0','info'=>'退货单已申请');
		exit($json->json_encode_ex($rs));
	}
	if ($number >$goods_num) {
		$rs=array('result'=>'0','info'=>'退货数量超出限制');
		exit($json->json_encode_ex($rs));
	}
	
	$flag1=$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('back_order'), $back_order, 'INSERT');
	//$sql = "UPDATE ".$GLOBALS['ecs']->table('order_goods')." SET is_service = {$type} WHERE order_id={$order_id} AND goods_id={$goods_id}  AND spec_key='{$goods[spec_key]}'";
	//$GLOBALS['db']->query($sql);
	$new_order_id=$db->insert_id();
	if($flag1){
		//生成退货商品
		$back_goods=array(
				'back_id'=>$new_order_id,
				'goods_id'=>$goods_id,
				'goods_name'=>$goods['goods_name'],
				'goods_sn'=>$goods['goods_sn'],
				'send_number'=>$number,
				'money'=>$shop_price,
				'back_price'=>$money,
				'goods_price'=>$goods['goods_price'],
				'spec_key'=> $goods['spec_key'],
				'spec_key_name'=>$spec_key_name,
				
		);
		$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('back_goods'), $back_goods, 'INSERT');
		
		//退货货标识
		if(!empty($spec_key)){
			$sql = "UPDATE ".$GLOBALS['ecs']->table('order_goods')." SET is_service = 1 WHERE order_id={$order_id} AND goods_id={$goods_id}  AND spec_key='{$spec_key}'";
		}else{
			$sql = "UPDATE ".$GLOBALS['ecs']->table('order_goods')." SET is_service = 1 WHERE order_id={$order_id} AND goods_id={$goods_id}";
		}
		$GLOBALS['db']->query($sql);
		
		$rs=array('result'=>'1','info'=>'申请成功');
		exit($json->json_encode_ex($rs));
	}
	
}
/**
 * 查看退换货详情
 * 这里有毛病
 */
elseif($action=="back_order_detail"){
	$back_id=$_REQUEST['oid'];
	$userid=!empty($userinfo['userid']) ? $userinfo['userid'] : "";
	if(empty($back_id) || empty($userid)){
		$rs=array('result'=>'0','info'=>'缺少参数');
		exit($json->json_encode_ex($rs));
	}
	$file="B.back_id,B.rec_id,A.id,A.order_id,A.order_sn,A.type,A.reason,A.addtime,A.status,A.remark,A.imgs as images,B.goods_name,B.send_number,B.money,A.refund_shipping_no,A.refund_shipping_no";
	$sql="SELECT {$file} from ".$GLOBALS['ecs']->table('back_order')." as A left join ".$GLOBALS['ecs']->table('back_goods')." as B on B.back_id=A.id where A.id={$back_id} AND A.user_id={$userid}";
	$row=$db->getRow($sql);
	$imgs = (explode(",",$row['images']));
	$row['imgs']=array();
	unset($row['images']);
	foreach ($imgs as $key =>$v){
		if ($v) {
			$row['imgs'][$key]=IMG_HOST.$v;
		}
	}
	
	$message=array();
	if(!empty($row['rec_id'])){
		
		$rec_id=$row['id'];
		$sql="SELECT rec_id,add_time,user_id,supplier_id,content from ".$GLOBALS['ecs']->table('back_msg')." where rec_id={$rec_id} order by add_time asc";
		$message=$db->getAll($sql);
	}
	$row['message']= $message;
	if(!empty($row)){
		$row['replay_time']=$row['addtime'];
		$rs=array('result'=>'1','info'=>'请求成功','data'=>$row);
		exit($json->json_encode_ex($rs));
	}else{
		$rs=array('result'=>'0','info'=>'请求失败');
		exit($json->json_encode_ex($rs));
	}
	
}
elseif($action=="send_back_msg"){
	$back_id=$_REQUEST['back_id'];
	$userid=!empty($userinfo['userid']) ? $userinfo['userid'] : "";
	$content = $_REQUEST['content'];
	$addTime = time();
	$username= $_REQUEST['username'];
	if(empty($back_id) || empty($userid) || empty($content)){
		$rs=array('result'=>'0','info'=>'缺少参数');
	}else{
		$sql="select id from  ".$GLOBALS['ecs']->table('back_order')." where user_id = {$userid} and id = {$back_id}";
		$back_order=$db->getOne($sql);
		
		if ($back_order ) {
			$sql="INSERT INTO ".$GLOBALS['ecs']->table('back_msg')." (rec_id,content,add_time,user_id) values({$back_id},'{$content}',{$addTime},{$userid})";
			$rs=$db->query($sql);
			if($rs){
				$rs=array('result'=>'1','info'=>'回复成功');
			}else{
				$rs=array('result'=>'0','info'=>'回复失败');
			}
		}else{
			$rs=array('result'=>'0','info'=>'不是您的退货单');
		}
		
	}
	exit($json->json_encode_ex($rs));
	
}
/**
 * 获取商品退货，退钱订单详情接口
 */
elseif($action=="back_info"){
	
	$order_id=$_REQUEST['order_id'];
	$goods_id=$_REQUEST['goods_id'];
	$userid=!empty($userinfo['userid']) ? $userinfo['userid'] : "";
	$spec_key=$_REQUEST['spec_key'];
	if(empty($order_id) || empty($goods_id) || empty($userid)){
		$rs=array('result'=>'0','info'=>'缺少参数');
		exit($json->json_encode_ex($rs));
	}
	
	if(!empty($spec_key)){
		$sql="SELECT goods_amount,order_id,goods_id,goods_sn,goods_num,goods_price,spec_key from ".$GLOBALS['ecs']->table('order_goods')." where order_id={$order_id} AND goods_id={$goods_id} AND spec_key='{$spec_key}'";
	}else{
		$sql="SELECT goods_amount,order_id,goods_id,goods_sn,goods_num,goods_price,spec_key from ".$GLOBALS['ecs']->table('order_goods')." where order_id={$order_id} AND goods_id={$goods_id}";
	}
	$goods=$db->getRow($sql);
	if ($goods['goods_amount']==0) {
		$goods['goods_amount']=$goods['goods_num']*$goods['goods_price'];
	}
	if($goods){
		$rs=array('result'=>'1','info'=>'成功','number'=>$goods['goods_num'],'price'=>$goods['goods_price'],'goods_amount'=>$goods['goods_amount'],'spec_key'=>$goods['spec_key']);
		exit($json->json_encode_ex($rs));
	}else{
		$rs=array('result'=>'0','info'=>'失败');
		exit($json->json_encode_ex($rs));
	}
}



/***
 * 
 * 退货填写物流信息接口
 * 
 */
elseif($action=="fill_address"){
	$rs=array('result'=>'0','info'=>'失败');
	exit($json->json_encode_ex($rs));
}

//增加建议意见
elseif($action=="add_opinion"){
	$userid=!empty($userinfo['userid']) ? $userinfo['userid'] : '';
	if(empty($userid)){
		$rs=array('result'=>'0','info'=>'参数错误');
		exit($json->json_encode_ex($rs));
	}
	$comment=trim($_REQUEST['comment']);
	$sql="select count(*) from ".$GLOBALS['ecs']->table('app_opinion')." where user_id={$userid} AND comments='{$comment}'";
	$count=$db->getOne($sql);
	if($count>1){
		$rs=array('result'=>'0','info'=>'已经评论过了');
		exit($json->json_encode_ex($rs));
	} 
	
	$beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
	$endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
	
	$sql="select count(*) from ".$GLOBALS['ecs']->table('app_opinion')." where user_id={$userid} AND addtime>{$beginToday} AND addtime<{$endToday}";
	$count2=$db->getOne($sql);
	if($count2>=3){
		$rs=array('result'=>'0','info'=>'每天最多反馈3次建议,感谢您的支持');
		exit($json->json_encode_ex($rs));
	}
   
	$count=$db->getOne($sql);
	$strtime=time();
	$sql="INSERT INTO ".$GLOBALS['ecs']->table('app_opinion')."(user_id,comments,addtime) values({$userid},'{$comment}',{$strtime})";
	$rs=$db->query($sql);
	if($rs){
		$rs=array('result'=>'1','info'=>'感谢您提的宝贵意见');
		exit($json->json_encode_ex($rs));
	}else{
		$rs=array('result'=>'0','info'=>'操作失败');
		exit($json->json_encode_ex($rs));
	}
}

/**
 * 
 * 撤销订单处理
 * 
 */
elseif($action=="cancel_banck_order"){
	$back_id=$_REQUEST['back_id'];
	$userid=$userinfo['userid'];
	$sql="select count(*) from ".$GLOBALS['ecs']->table('back_order')." where id={$back_id}  AND type in (1,2) AND user_id={$userid} AND status!=7";
	$count=$db->getOne($sql);
	if($count>0){
		$sql="UPDATE ".$GLOBALS['ecs']->table('back_order')." SET status= 7 where id={$back_id} AND user_id={$userid}";
		$GLOBALS['db']->query($sql);
	    $rs=array('result'=>'1','info'=>'撤销成功');
	    exit($json->json_encode_ex($rs));
	}else{
		$rs=array('result'=>'0','info'=>'失败');
		exit($json->json_encode_ex($rs));
	}
}


/**
 *
 * 换货确认订单处理
 *
 */
elseif($action=="confirm_banck_order"){
	$back_id=$_REQUEST['back_id'];
	$userid=$userinfo['userid'];
	$sql="select count(*) from ".$GLOBALS['ecs']->table('back_order')." where id={$back_id}  AND type=2 AND user_id={$userid} AND status=5";
	$count=$db->getOne($sql);
	if($count>0){
		$sql="UPDATE ".$GLOBALS['ecs']->table('back_order')." SET status= 6 where id={$back_id} AND user_id={$userid}";
		$GLOBALS['db']->query($sql);
		$rs=array('result'=>'1','info'=>'确认已换货成功');
		exit($json->json_encode_ex($rs));
	}else{
		$rs=array('result'=>'0','info'=>'失败');
		exit($json->json_encode_ex($rs));
	}
}

/****
 * 获取订单商品评论
 * 
 */
elseif($action=="comments"){
	$order_id=$_REQUEST['order_id'];
	$userid=!empty($userinfo['userid']) ? $userinfo['userid'] : "";
	if(empty($order_id) || empty($userid)){
		$rs=array('result'=>'0','info'=>'缺少参数');
		exit($json->json_encode_ex($rs));
	}
	$file="A.order_id,B.goods_id";
	$sql="SELECT {$file} from ".$GLOBALS['ecs']->table('order')." as A left join ".$GLOBALS['ecs']->table('order_goods')." as B on A.order_id=B.order_id where A.user_id={$userid} AND A.order_id={$order_id} AND order_status=2";
	$list=$GLOBALS['db']->getAll($sql);
	$list=array_unique($list);
	if(!empty($list)){
		foreach ($list as $key=>$val){
			$list[$key]=$val;
			$image=get_image_goods($val['goods_id']);
			$list[$key]["image"]=!empty($image) ? IMG_HOST.$image : array();
		}
		$rs=array('result'=>'1','info'=>'请求成功','list'=>$list);
	}else{
		$rs=array('result'=>'1','info'=>'无数据','list'=>$list);
	}
	exit($json->json_encode_ex($rs));
}
/***
 * 添加评论
 */
elseif($action=="add_comment"){
	$goods_id=$_REQUEST['goods_id'];
	$userid=$userinfo['userid'];
	$order_id=$_REQUEST['order_id'];
	$image_url="";
	//多个评论
	if(strpos($goods_id,',')){
		$arrid=explode(',',$goods_id);
		$error=0;
		$rs=0;
		if(!empty($arrid)){
			foreach ($arrid as $val){
				//评论信息
				$comment=$_REQUEST["content_{$val}"];
				$data['goods_id']=$val;
				$data['order_id']=$order_id;
				$data['content']=$comment;
				$data['user_id']=$userid;
				$data['username']=$userinfo['username'];
				$data['img']=$_REQUEST["name_{$val}"];
				//评论为空，不插入评论
				if(empty($comment)){
					continue;
				}
				$rs=insert_comments($data);
			}
			
			if ($rs && $error=='0'){
				$sql="SELECT count(*) from ".$GLOBALS['ecs']->table('order')." as A left join ".$GLOBALS['ecs']->table('order_goods')." as B on A.order_id=B.order_id where A.user_id={$userid} AND A.order_id={$order_id} AND A.order_status=2";
				$count=$GLOBALS['db']->getOne($sql);
	            if($count>0){
	            	//修改一下订单状态
	            	$sql = "UPDATE " . $GLOBALS['ecs']->table('order') . " set order_status = 4 WHERE user_id={$userid} AND order_id={$order_id}";
	            	$GLOBALS['db']->query($sql);
	            }
				$results = array('result' => 1,'info' => '成功');
				exit($json->json_encode_ex($results));
			}else{
				$results = array('result' => 0,'info' => '失败');
				exit($json->json_encode_ex($results));
			}
			
		}
	}else{
		//当个评论
		$comment=$_REQUEST["content_{$goods_id}"];
		$data['content']=$comment;
		$data['goods_id']=$goods_id;
		$data['order_id']=$order_id;
		$data['user_id']=$userid;
		$data['username']=$userinfo['username'];
		//图片上传
		$data['img']=$_FILES["name_{$goods_id}"];
		$rs=insert_comments($data);
		if($rs){
		     $sql="SELECT count(*) from ".$GLOBALS['ecs']->table('order')." as A left join ".$GLOBALS['ecs']->table('order_goods')." as B on A.order_id=B.order_id where A.user_id={$userid} AND A.order_id={$order_id} AND A.order_status=2";
		     $count=$GLOBALS['db']->getOne($sql);
	         if($count>0){
	            	//修改一下订单状态
	            	$sql = "UPDATE " . $GLOBALS['ecs']->table('order') . " set order_status = 4 WHERE user_id={$userid} AND order_id={$order_id}";
	            	$GLOBALS['db']->query($sql);

	         }
			$rs=array('result'=>'1','info'=>'评论成功');
			exit($json->json_encode_ex($rs));
		}else{
			$rs=array('result'=>'0','info'=>'失败');
			exit($json->json_encode_ex($rs));
		}
	}
}

/***
 * 添加评论 用这个
*/
elseif($action=="comment_goods"){
		 $goods_id=$_REQUEST['goods_id'];
		 $userid=$userinfo['userid'];
		 $order_id=$_REQUEST['order_id'];
		 $spec_key_name=$_REQUEST['spec_key_name'];
		//当个评论
		$comment=trim($_REQUEST["content"]);
		$data['content']=$comment;
		$data['goods_id']=$goods_id;
		$data['order_id']=$order_id;
		$data['user_id']=$userid;
		$data['username']=$userinfo['username'];
		$data['spec_key_name']=$spec_key_name;
		
		$data['deliver_rank']=!empty($_REQUEST['deliver_rank']) ? $_REQUEST['deliver_rank'] : '5' ;

		$data['goods_rank']=!empty($_REQUEST['goods_rank']) ? $_REQUEST['goods_rank'] : '5';
		$data['service_rank']=!empty($_REQUEST['service_rank']) ? $_REQUEST['service_rank'] : '5';
		
		$data['img']="";
		//图片上传
		if(!empty($_REQUEST["name"])){
			//$data['img']=serialize($_REQUEST["name"]);
			if(strpos(($_REQUEST["name"]),',')){
				$arr=explode(',',$_REQUEST["name"]);
				$data['img']=serialize($arr);
			}else{
				$data['img']=serialize(array('0'=>$_REQUEST["name"]));
			}
		}
		if(empty($comment) || empty($goods_id) || empty($order_id)){
			$rs=array('result'=>'0','info'=>'缺少参数');
			exit($json->json_encode_ex($rs));
		}
		$rs=insert_comments($data);
		if($rs){
			$sql="SELECT count(*) from ".$GLOBALS['ecs']->table('order')." as A left join ".$GLOBALS['ecs']->table('order_goods')." as B on A.order_id=B.order_id where A.user_id={$userid} AND A.order_id={$order_id} AND A.order_status=2";
			$count=$GLOBALS['db']->getOne($sql);
			if($count>0){
				//如果此订单下的所有商品，都评论了。修改状态
				$sql="SELECT count(goods_id) from ".$GLOBALS['ecs']->table('order_goods')." where order_id={$order_id}";
				$goods_num1=$GLOBALS['db']->getOne($sql);
				//查询是否都有评价，如若全部评价修改状态
				$sql="SELECT count(*) from ".$GLOBALS['ecs']->table('comment')." where order_id={$order_id}  AND user_id={$userid}";
				$goods_num2=$GLOBALS['db']->getOne($sql);
				
				if(!empty($goods_num1) && !empty($goods_num2) && $goods_num1==$goods_num2){
					//修改一下订单状态
					$sql = "UPDATE " . $GLOBALS['ecs']->table('order') . " set order_status = 4 WHERE user_id={$userid} AND order_id={$order_id}";
					$GLOBALS['db']->query($sql);
				}
			}
			$rs=array('result'=>'1','info'=>'评论成功');
			exit($json->json_encode_ex($rs));
		}else{
			$rs=array('result'=>'0','info'=>'失败');
			exit($json->json_encode_ex($rs));
		}
}


//图片上传
elseif ($action=="upload_img"){
	$file=$_FILES['name'];
	/*  foreach ($file as $key =>$val){
		$fp = fopen(ROOT_PATH ."uplod.txt","a");
		flock($fp, LOCK_EX) ;
		fwrite($fp,"执行日期：".strftime("%Y%m%d%H%M%S",time())."\n".$key."=>".$val."\n");
		flock($fp, LOCK_UN);
		fclose($fp);
	}
	
	$post=$_POST;
	foreach ($post as $key =>$val){
		$fp = fopen(ROOT_PATH ."uplod.txt","a");
		flock($fp, LOCK_EX) ;
		fwrite($fp,"执行日期：".strftime("%Y%m%d%H%M%S",time())."\n".$key."=>".$val."\n");
		flock($fp, LOCK_UN);
		fclose($fp);
	} */
	
	if(!empty($file))
	{
		$img_desc =$file;
		$upload_path = parse_path('public','upload','common','image');
		include_once ROOT_PATH.'includes/Upload.php';
		$up = new \includes\Upload();
		$result = $up ->set_dir(dirname(ROOT_PATH)."/".$upload_path, "{y}/{m}")->execute();
		$result=$result['name'];
		if(!empty($result)){
			 $imgarr="/public/upload/common/image/".$result['name'];
			 $path=IMG_HOST.$imgarr;
			 if($result['flag']=='-1'){
			 	$results = array('result' => 0,'info' => '文件类型不允许');
			 	exit($json->json_encode_ex($results));
			 }
			 elseif($result['flag']=='-2'){
			 	$results = array('result' => 0,'info' => '文件过大');
			 	exit($json->json_encode_ex($results));
			 }
			elseif($result['flag']=='1'){
				$results = array('result' => 1,'info' => '上传成功','img_url'=>$imgarr,'path'=>$path);
				exit($json->json_encode_ex($results));
			}else{
				$results = array('result' => 0,'info' => '上传失败');
				exit($json->json_encode_ex($results));
			}
		}else{
			$results = array('result' => 0,'info' => '上传失败');
			exit($json->json_encode_ex($results));
		}
	}

}
/***
 * 退货物流接口,填写发货信息
 * 
 */
elseif($action=='return_logistics'){
	$type=$_REQUEST["type"];
	$userid=!empty($userinfo['userid']) ? $userinfo['userid'] : 0;
	$rec_id=$_REQUEST['rec_id'];
	$order_sn=trim($_REQUEST['order_sn']);
	$shipping_name=trim($_REQUEST['companyname']);
	
	$sql="SELECT back_id from ".$GLOBALS['ecs']->table('back_goods')." where rec_id={$rec_id}";

	$back_id=$GLOBALS['db']->getOne($sql);
	if($type==3){
		//获取退货信息
		$sql="SELECT refund_shipping_no as order_sn,shipping_name  as companyname from ".$GLOBALS['ecs']->table('back_order')." where user_id={$userid} AND id={$back_id}";
		$row=$GLOBALS['db']->getRow($sql);

		if ($row) {
			$results = array('result' => 1,'info' => '请求成功','data'=>$row);
		}else{
			$results = array('result' => 0,'info' => '缺少必要参数','data'=>$row);
		}
		exit($json->json_encode_ex($results));
	} 
	if(empty($userid) || empty($back_id) || empty($order_sn)||empty($shipping_name) ){
		$results = array('result' => 0,'info' => '缺少必要参数');
		exit($json->json_encode_ex($results));
	}
	//查看是否存在退货商品
	$sql="SELECT count(*) from ".$GLOBALS['ecs']->table('back_goods')." where back_id={$back_id}";
	$count1=$GLOBALS['db']->getOne($sql);
	if($count1>1){
		$results = array('result' => 0,'info' => '退货商品不存在');
		exit($json->json_encode_ex($results));
	}
	
	$sql="SELECT count(*) from ".$GLOBALS['ecs']->table('back_order')." where user_id={$userid} AND id={$back_id}";
	$count=$GLOBALS['db']->getOne($sql);
	$arr['refund_shipping_no']=$order_sn;
	$arr['retund_shipping_time']=time();
	$arr['shipping_name']=$shipping_name;
	$arr['status']='2';
	//如果type==1 添加物流信息

		$where="user_id={$userid} AND id={$back_id}";
		//修改物流信息
		$flag=$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('back_order'), $arr, '',$where);
		if($flag){
			$results = array('result' => 1,'info' => '提交成功','data'=>array());
			exit($json->json_encode_ex($results));
		}
		
		$results = array('result' => 0,'info' => '修改失败');
		exit($json->json_encode_ex($results));
	
	
	
}
/***
 * 查看物流信息
 */
elseif($action=="logistics"){
	$order_sn=$_REQUEST['order_sn'];
	$type=$_REQUEST['type'];
	if(!empty($type) && $type=="1"){
		$desc=" order by id DESC ";
	}else{
		$desc=" order by id ";
	}
	//$order_sn="201704271710449344";
	//$userid=!empty($userinfo['userid']) ? $userinfo['userid'] : " ";
	$userid=!empty($userinfo['userid']) ? $userinfo['userid'] : " ";
	if(empty($order_sn) || empty($userid)){
		$results = array('result' => 0,'info' => '缺少参数');
		exit($json->json_encode_ex($results));
	} 
	$sql="select shipping_name,invoice_no,logistics_information from ".$GLOBALS['ecs']->table('shipping_order')." where order_sn='".$order_sn."' AND user_id={$userid} {$desc}";
	$info=$GLOBALS['db']->getRow($sql);
	
	if(!empty($info['logistics_information'])){
		$data=object_array($info['logistics_information']);
	}
	$data=json_decode($data,true);
	if(!empty($data['lastResult']['data'])){
		$results = array('result' => 1,'info' => '成功','shipping_name'=>$info['shipping_name'],'invoice_no'=>$info['invoice_no'],'data'=>$data['lastResult']['data']);
		exit($json->json_encode_ex($results));
	}else{
		$results = array('result' => 1,'info' => '成功','data'=>array());
		exit($json->json_encode_ex($results));
	}
	
}

//退货货留言
elseif($action=="back_good_msg"){
	
	$rec_id=trim($_REQUEST['rec_id']);
	$content=trim($_REQUEST['content']);
	$userid=!empty($userinfo['userid']) ? $userinfo['userid'] : "";
	$username=!empty($userinfo['username']) ? $userinfo['username'] : "";
	if(empty($rec_id) || empty($content) || empty($userid)){
		$results = array('result' => 0,'info' => '缺少参数');
		exit($json->json_encode_ex($results));
	}
	 $arr['rec_id']=$rec_id;
	 $arr['content']=$content;
	 $arr['add_time']=time();
	 $arr['username']=$username;
	 $arr['user_id']=$userid;
	 $sql="SELECT count(*) from ".$GLOBALS['ecs']->table('back_msg')." where user_id={$userid} AND rec_id={$rec_id} AND content='{$content}'";
	 $count=$GLOBALS['db']->getOne($sql);
	if($count>0){
		$results = array('result' =>1,'info' => '已留言');
		exit($json->json_encode_ex($results));
	}
	$flag= $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('back_msg'), $arr, 'INSERT');
	if($flag){
		$results = array('result' => 1,'info' => '留言成功');
		exit($json->json_encode_ex($results));
	}else{
		$results = array('result' => 0,'info' => '留言失败');
		exit($json->json_encode_ex($results));
	}
		
}
//查看订单角标
elseif($action=='remark_count'){
	$user_id=!empty($userinfo['userid']) ? $userinfo['userid'] : "";
	if(empty($user_id)){
		$results = array('result' => 0,'info' => '缺少参数');
		exit($json->json_encode_ex($results));
	}
	   
	$num1=get_order_sum($user_id,"0");
	$num2=get_order_sum($user_id,"1");
	$num3=get_order_sum($user_id,"2");
	$num4=get_order_sum($user_id,"3");
	
	$sql="SELECT count(*) from " .$GLOBALS['ecs']->table('back_order')." as A left join ".$GLOBALS['ecs']->table('back_goods')." as B ON A.id=B.back_id where A.user_id={$user_id}";
	$back_goods_count=$db->getOne($sql);
	
	//购物车总的数量
	$sql = " SELECT count(*) from ".$GLOBALS['ecs']->table('cart')." WHERE  user_id='{$user_id}'";
	$cart_count=$GLOBALS['db']->getOne($sql);
	
	$results = array('result' => '1','info' => '成功','no_pay_num'=>$num1,'send_goods_num'=>$num2,'wait_goods_num'=>$num3,'comment_num'=>$num4,'back_goods_num'=>$back_goods_count,'cart_count'=>$cart_count);
	exit($json->json_encode_ex($results));
}

//换货已完成状态修改


function get_order_sum($userid,$type){
	//$type状态 0 为待付款，1待发货，2为待收货，3为待评价，4为已完成，5为已取消，
	$where=" where O.user_id={$userid} AND O.is_parent !=1 AND ";
	switch ($type)
	{
		case 0:
			$where.=" O.order_status=0 AND O.pay_status = 0 AND O.is_delete = 0";
			break;
		case 1:
		 $where.=" O.pay_status=1 AND O.shipping_status !=1 AND O.order_status in(0,1) AND O.is_delete = 0";
		 break;
		case 2:
			$where.=" O.order_status=1 AND O.shipping_status=1 AND O.is_delete = 0";
			break;
		case 3:
			$where.=" O.order_status=2 AND O.is_delete = 0";
			break;
		case 4:
			$where.=" O.order_status=4 AND O.is_delete = 0";
			break;
		case 5:
			$where.=" O.order_status in (3,5)  AND O.is_delete = 0";
			break;
		default:
			$where.=" O.order_status in (0,1,2,3,4) AND O.is_delete = 0";
	}
	//获取订单总数
	$sql="select count(*) from ".$GLOBALS['ecs']->table('order')." as O {$where}";
	return  $GLOBALS['db']->getOne($sql);
	
}


function object_array($array) {
	if(is_object($array)) {
		$array = (array)$array;
	} if(is_array($array)) {
		foreach($array as $key=>$value) {
			$array[$key] = object_array($value);
		}
	}
	return $array;
}

/***
 * 增加评论
 */
function insert_comments($data){
	
	

	$sql="SELECT count(*) from ".$GLOBALS['ecs']->table('comment')." where order_id={$data['order_id']}  AND user_id={$data[user_id]} AND goods_id={$data['goods_id']} AND spec_key_name ='{$data['spec_key_name']}'";
	$count2=$GLOBALS['db']->getOne($sql);
	
	if($count2==0){
		$is_comment=1;
	}elseif ($count2==1){
		$is_comment=2;
	}else{
		return false;
	}
	
	$sql="SELECT supplier_id from ".$GLOBALS['ecs']->table('order')." where order_id={$data['order_id']} ";
	$arr['supplier_id']=$GLOBALS['db']->getOne($sql);
	
	

    $sql = "UPDATE " . $GLOBALS['ecs']->table('order_goods') . " set is_comment = {$is_comment} WHERE goods_id={$data[goods_id]} AND spec_key_name='{$data['spec_key_name']}' AND order_id={$data[order_id]}";
 
    $GLOBALS['db']->query($sql);

	 $arr['goods_id']=$data['goods_id'];
	 $arr['order_id']=$data['order_id'];
	 $arr['content']=$data['content'];
	 $arr['img']=$data['img'];
	 $arr['add_time']=time();
	 $arr['ip_address']=real_ip();
	 $arr['username']=$data['username'];
	 $arr['user_id']=$data['user_id'];
	 $arr['deliver_rank']=$data['deliver_rank'];
	 $arr['goods_rank']=$data['goods_rank'];
	 $arr['service_rank']=$data['service_rank'];
	 $arr['spec_key_name']=$data['spec_key_name'];
	return $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('comment'), $arr, 'INSERT');
	
}

//获取订单详情信息
function get_order_info2($userid,$order_id){
	$file="O.coupon_price,O.order_prom_amount,O.pay_code,O.pay_code,O.user_note,O.order_id,O.order_sn,O.user_id,O.order_status,O.shipping_status,O.pay_status,O.consignee,O.province,O.city,O.district,O.address,O.mobile,O.shipping_code,O.goods_price,O.shipping_price,O.order_amount,O.add_time";
	$sql="select $file from ".$GLOBALS['ecs']->table('order')." as O where O.order_id={$order_id} AND user_id={$userid}";
	$order=$GLOBALS['db']->getRow($sql);
	
	if(!empty($order['province'])){
		$order['province_name']=get_regin_name($order['province']);
		$order['province']=get_regin_name($order['province']);
	}
	if(!empty($order['city'])){
		$order['city_name']=get_regin_name($order['city']);
		$order['city']=get_regin_name($order['city']);
	}
	if(!empty($order['district'])){
		$order['district_name']=get_regin_name($order['district']);
		$order['district']=get_regin_name($order['district']);
	}
	if ($order['pay_code']=="weixin"||$order['pay_code']=="weixinJSAPI") {
		$order['pay_code']="微信支付";
	}elseif($order['pay_code']=="alipay"||$order['pay_code']=="alipayMobile"){
		$order['pay_code']="支付宝";
	}else{
		$order['pay_code']="未支付";
	}
	//检测是否为活动的订单
	if($order['order_status']=='0' && $order['pay_status']=='0'){
		$count=check_active_order($order['order_id']);
		if($count>0){
			if(time()<$order['add_time']+1800){
				$order['end_time']=$order['add_time']+1800-time();
			}else{
				$order['end_time']="";
			}
		}else{
			if(time()<$order['add_time']+48*3600){
				$order['end_time']=($order['add_time']+48*3600)-time();
			}else{
				$order['end_time']="";
			}
		}
	}else{
		$order['end_time']="";
	}
	$order['comment']=array();
	//获取订单状态
     if($order['order_status']=='0' && $order['pay_status']=='0'){
		    	$order['order_status']=0;
		    }elseif(($order['pay_status']==1 || $order['pay_code']!='') && $order['shipping_status']!=1 && ($order['order_status']==1 || $order['order_status']=='0')){
		    	$order['order_status']=1;
		    }elseif($order['order_status']==1 && $order['shipping_status']==1){
		    	$order['order_status']=2;
		    }elseif($order['order_status']==2){
		    	$order['order_status']=3;
		    }elseif($order['order_status']==4){
		    	
		    	$order['order_status']=4;
		    }elseif($order['order_status']==3){
		    	$order['order_status']=5;
		 }
	
	//获取订单商品信息
	$order['goods_list']=get_order_goods2($order_id,$userid,$order['order_status']);
	$order['number']=$order['goods_list']['goods_num'];
	$order['add_time']=date("Y-m-d H:i",$order['add_time']);
	$order['discount_price']="0.00";
	$order['discount_price']=number_format($order['goods_price']-$order['order_amount'] , 2);
	

	if($order['goods_list'][0]['supplier_id']=='18' || empty($order['goods_list'][0]['supplier_mobile'])){
		$supplier_mobile='400-089-7879';
	}else{
		$supplier_mobile=$order['goods_list'][0]['supplier_mobile'];
	} 
	$order['customer_phone']=$supplier_mobile;
	unset($order['goods_list']['goods_num']);

	return $order;
	
}

function get_order_goods2($id,$user_id="",$order_type=""){
	$file="A.goods_amount,A.goods_id,A.goods_name,A.goods_sn,A.goods_num,A.market_price,A.goods_price,A.spec_key,A.spec_key_name,A.is_send,A.delivery_id,A.send_number,B.supplier_id,B.original_img";
	$sql="select $file from ".$GLOBALS['ecs']->table('order_goods')." AS A left join ".$GLOBALS['ecs']->table('goods')." as B ON A.goods_id=B.goods_id where A.order_id={$id}";
	$list=$GLOBALS['db']->getAll($sql);
	$rs=array();
	$arr=array();
	$newarr=array();
   if(!empty($list)){
		$max=array();
		$price1=0;
		$price2=0;	
		$number=0;
		
		
		
	foreach ($list as $key=> $val){
			
			if ($val['goods_amount']==0) {
				$val['goods_amount']=$val['goods_num']*$val['goods_price'];
			}
			if ($val['goods_amount']) {
				$val['back_price']=$val['goods_amount']/$val['goods_num'];
			}
		    $number+=$val['goods_num'];
			if(!empty($val['supplier_id'])){
				if(!empty($val['supplier_id'])){
					$row=get_supplier($val['supplier_id']);
					$arr[$val['supplier_id']]['back_price']="{$val['back_price']}";
					$arr[$val['supplier_id']]['supplier_id']=$val['supplier_id'];
					$arr[$val['supplier_id']]['supplier_name']=$row['supplier_name'];
					$arr[$val['supplier_id']]['company_name']=$row['supplier_name'];
					$arr[$val['supplier_id']]['supplier_mobile']=$row['mobile'];
				}else{
					$arr[$val['supplier_id']]['supplier_id']="";
					$arr[$val['supplier_id']]['supplier_name']="";
					$arr[$val['supplier_id']]['company_name']="";
					$arr[$val['supplier_id']]['supplier_mobile']="";
					$arr[$val['supplier_id']]['back_price']='0';
				}
				
				
				$val['status']='';
				if($order_type=="1" || $order_type=="2" || $order_type=="3" || $order_type=="4"){
					
					$allow_back_num=get_back_order($id,$val['goods_id'],$user_id,$val['spec_key'],$val['goods_num']);
					$val['allow_back_num']=$allow_back_num;
				}/* elseif($order_type=='2'){
					$status=get_back_order($id,$val['goods_id'],$user_id,$val['spec_key']);
					//待收货状态
					$val['status']=$status;
				}elseif($order_type=='3'){
					$count=get_comment_type($id,$user_id,$val['goods_id']);
					if($count>0){
						$val['status']="1";
					}else{
						$val['status']="0";
					} 
				} */else{
					$val['allow_back_num']='0';
				}
				$arr[$val['supplier_id']]['list'][$key]=$val;
				$arr[$val['supplier_id']]['list'][$key]['original_img']=!empty($val['original_img']) ? IMG_HOST.$val['original_img'] : '';
			}else{
				$rs[$key]['supplier_id']="";
				$rs[$key]['supplier_name']="";
				$rs[$key]['company_name']="";
				$val['status']="";
				if(empty($val['supplier_id'])){
					$val['supplier_id']="";
				}
				if(!empty($val['original_img'])){
					$val['original_img']=IMG_HOST.$val['original_img'];
				}else{
					$val['original_img']="";
				}
				if($order_type=='1' || $order_type=='2' || $order_type=='3'){
					$allow_back_num=get_back_order($id,$val['goods_id'],$user_id,$val['spec_key'],$val['goods_num']);
					$val['allow_back_num']=$allow_back_num;
				}/* elseif($order_type=='2'){
					$status=get_back_order($id,$val['goods_id'],$user_id,$val['spec_key']);
					//待收货状态
					$val['status']=$status;//!empty($status) ? $status : "0";
				}elseif($order_type=='3'){
					//待评论
					$count=get_comment_type($id,$user_id,$val['goods_id']);
					if($count>0){
						$val['status']="1";
					}else{
						$val['status']="0";
					}
				} */else{
					$val['allow_back_num']="0";
				}
				$rs[$key]['list'][]=$val;
			}
			
		}
		
		$rs2=array();
		if(!empty($arr)){
			foreach ($arr as $key=>$val){
				$temp['supplier_name']=$val['supplier_name'];
				$temp['supplier_id']=$val['supplier_id'];
				$temp['company_name']=$val['supplier_name'];
				$temp['business_sphere']=$val['business_sphere'];
				$temp['supplier_mobile']=$val['supplier_mobile'];
				$temp['carriage']=$val['carriage'];
				if(!empty($val['list'])){
					sort($val['list']);
					$temp['list']=$val['list'];
				}else{
					$temp['list']=array();
				}
				$rs2[]=$temp;
			}
		}
		$rs=array_merge($rs2,$rs);
	}

	$rs["goods_num"]=$number;
	return $rs;
}

//获取订单详情信息
function get_order_info($userid,$order_id){
	$file="O.order_id,O.order_sn,O.user_id,O.order_status,O.shipping_status,O.pay_status,O.consignee,O.province,O.city,O.district,O.address,O.mobile,O.shipping_code,O.goods_price,O.shipping_price,O.order_amount,O.add_time";
	$sql="select $file from ".$GLOBALS['ecs']->table('order')." as O where O.order_id={$order_id} AND user_id={$userid}";
	$order=$GLOBALS['db']->getRow($sql);
	
	if(!empty($order['province'])){
		$order['province_name']=get_regin_name($order['province']);
	}
	if(!empty($order['city'])){
		$order['city_name']=get_regin_name($order['city']);
	}
	if(!empty($order['district'])){
		$order['district_name']=get_regin_name($order['district']);
	}
	//获取订单商品信息
	$order['goods']=get_order_goods($order_id);
	$order['number']=$order['goods']['number'];
	$order['add_time']=date("Y-m-d H:i",$order['add_time']);
	$order['customer_phone']="400-089-7879";
	unset($order['goods']['number']);
	return $order;
}

//获取省市县名称
function get_regin_name($id){
	$sql="select name from ".$GLOBALS['ecs']->table('region')." where id={$id}";
	return $GLOBALS['db']->getOne($sql);
}
//获取订单列表
function get_order_list($userid,$type,$page=0,$size=10){
	$rs=array();
	$begin = $page*$size;
	$limit = " LIMIT $begin,$size";
	//全部列表type不传      0 为待付款，1待发货，2为待收货，3为待评价，4为已完成，5为已取消

	$where=" where O.user_id= {$userid} AND O.is_parent !=1 AND ";
	switch ($type)
	{ 
	case '0':
	  	$where.=" O.order_status=0 AND O.pay_status = 0 and O.is_delete = 0";//为待付款 
	  	break;
	case '1': 
	 	$where.=" O.pay_status=1 AND O.shipping_status !=1 AND O.order_status in(0,1) and O.is_delete = 0";//为待发货
	  	break;
	case '2':
	  	$where.=" O.order_status=1 AND O.shipping_status=1 and O.is_delete = 0";//为待收货
	  	break;
	case '3':
		$where.=" O.order_status=2 and O.is_delete = 0";//为待评价
	 	break;
	 case '4':
	  	$where.=" O.order_status=4 and O.is_delete = 0 ";//为已完成
	  	break;
	 case '5':
	  	$where.=" O.order_status in (3,5) and O.is_delete = 0";//为已取消
	  	break;
	default:
	  	$where.=" O.order_status in (0,1,2,3,4,5) and O.is_delete = 0";
	}
	
	//获取订单总数
	$sql="select count(*) from ".$GLOBALS['ecs']->table('order')." as O {$where}";

	$goods_count = $GLOBALS['db']->getOne($sql);
	$allpage=ceil($goods_count/$size);
	//$file="O.order_id,O.order_sn,O.user_id,O.order_status,O.shipping_status,O.pay_status,O.consignee,O.province,O.city,O.district,O.address,O.mobile,O.shipping_code,O.goods_price,O.shipping_price,O.order_amount,O.add_time";
	$file="O.order_id,O.order_sn,O.user_id,O.order_status,O.shipping_status,O.pay_status,O.consignee,O.goods_price,O.shipping_price,O.order_amount,O.add_time,O.supplier_name,O.supplier_id";
	$sql="select $file from ".$GLOBALS['ecs']->table('order')." as O {$where} order by O.order_id DESC {$limit}"; 
	
	$list=$GLOBALS['db']->getAll($sql);

	$goods_num=0;
	$all_price=0;
	if(!empty($list)){
		foreach ($list as $key=>$val){
		    // $list[$key]=$val;
		     $all_price+=$val['order_amount'];
		    if($val['order_status']=='0' && $val['pay_status']=='0' && $val['pay_code']!=''){
		    	$list[$key]['order_status']=0;
		    }elseif(($val['pay_status']==1 || $val['pay_code']!='') && $val['shipping_status']!=1 && ($val['order_status']==1 || $val['order_status']=='0')){
		    	$list[$key]['order_status']=1;
		    }elseif($val['order_status']==1 && $val['shipping_status']==1){
		    	$list[$key]['order_status']=2;
		    }elseif($val['order_status']==2){
		    	$list[$key]['order_status']=3;
		    }elseif($val['order_status']==4){
		    	$list[$key]['order_status']=4;
		    }elseif($val['order_status']==3){
		    	$list[$key]['order_status']=5;
		    } 
		    $rs=get_order_goodByID($val['order_id'],$userid,$list[$key]['order_status']);
		    $goods_num+=$rs['goods_num'];
		    //增加订单的数量
		    $list[$key]['discount_price']="0.00";
		    $list[$key]['discount_price']=number_format($val['goods_price']-$val['order_amount'], 2);
		    $list[$key]['goods_num']=$rs['goods_num'];
		    unset($rs['goods_num']);
		    $list[$key]['goods']=$rs;
		}
	}

	$rs['list']=$list;
	$rs['count']=$allpage;
	$rs['goods_num']=$goods_num;
	$rs['all_price']=$all_price;
	$rs['goods_count']=$goods_count;
	return $rs;
	
}
//根据订单id,查询所有的商品id
function get_order_goodByID($id, $user_id="",$order_type=""){
	$file="A.goods_id,A.goods_name,A.goods_sn,A.goods_num,A.market_price,A.goods_price,A.spec_key,A.spec_key_name,A.is_send,A.delivery_id,A.send_number,B.supplier_id,B.original_img";
	$sql="select $file from ".$GLOBALS['ecs']->table('order_goods')." AS A left join ".$GLOBALS['ecs']->table('goods')." as B ON A.goods_id=B.goods_id where A.order_id={$id}";
	$list=$GLOBALS['db']->getAll($sql);
	$rs=array();
	if(!empty($list)){
		$number=0;
		foreach ($list as $key=> $val){
			$rs[$key]=$val;
			$number+=$val['goods_num'];
			if(!empty($val['original_img'])){
				$rs[$key]['original_img']=IMG_HOST.$val['original_img'];
			}else{
				$rs[$key]['original_img']="";
			}
			if($order_type=='1'){
				$status=get_back_order($id,$val['goods_id'],$user_id,$val['spec_key']);
				$rs[$key]['status']=$status;
			}elseif($order_type=='2'){
				$status=get_back_order($id,$val['goods_id'],$user_id,$val['spec_key']);
				//待收货状态
				$rs[$key]['status']=$status;
			}elseif($order_type=='3'){
				//待评论
				$count=get_comment_type($id,$user_id,$val['goods_id']);
				if($count>0){
					$rs[$key]['status']="1";
				}else{
					$rs[$key]['status']="0";
				}
			}else{
				$rs[$key]['status']="";
			}
		}
	
	$rs["goods_num"]=$number;
	return $rs;
 }
}

//获取退货，退钱订单状态
function get_back_order($order_id,$goods_id,$user_id,$spec_key='',$goods_num = 0){
	
	if(!empty($spec_key)){
		//有规格id
		$sql="select id,status from ".$GLOBALS['ecs']->table('back_order')." where order_id={$order_id} AND goods_id={$goods_id} AND user_id={$user_id}  AND spec_key='{$spec_key}' ";
	}else{
		$sql="select id,status from ".$GLOBALS['ecs']->table('back_order')." where order_id={$order_id} AND goods_id={$goods_id} AND user_id={$user_id} ";
	}
	$back_list = $GLOBALS['db']->getAll($sql);
	
	if ($back_list) {
		foreach ($back_list as $key =>$v){
			if ($v['status'] != -1 && $v['status'] != 7) {
				$sql="select send_number from ".$GLOBALS['ecs']->table('back_goods')." where back_id={$v['id']} AND goods_id={$goods_id} AND spec_key='{$spec_key}' ";
				$back_goods = $GLOBALS['db']->getAll($sql);
				foreach ($back_goods as $key =>$v){
					$goods_num=$goods_num-$v['send_number'];
				}
			}
		}
	}
	
	return $goods_num;
}

//获取评论类型
function get_comment_type($order_id,$user_id,$good_id){
	$sql="SELECT count(*) from ".$GLOBALS['ecs']->table('comment')." where order_id={$order_id}  AND user_id={$user_id} AND goods_id={$good_id}";
	return $GLOBALS['db']->getOne($sql);
}

//获取订单信息
function get_order_goods($id){
	if(empty($id)){
		return false;
	}
	$file="goods_id,goods_name,goods_sn,goods_num,market_price,goods_price,spec_key,spec_key_name,is_send,delivery_id,send_number";
	$sql="select $file from ".$GLOBALS['ecs']->table('order_goods')." where order_id={$id}";
	$list=$GLOBALS['db']->getAll($sql);
	$number=0;
	if(!empty($list)){
		foreach ($list as $key =>$val){
			$number+=$val['goods_num'];
			$list[$key]=$val;
			$image=get_image_goods($val['goods_id']);
			$list[$key]['original_img']=!empty($image) ? IMG_HOST.$image : "";
			$list[$key]['supplier_id']="";
			$list[$key]['supplier_name']="";
			
			$goods=get_goods_id($val['goods_id'],"supplier_id");
			//获取商家
			if(!empty($goods['supplier_id'])){
				$supplier=get_supplier($goods['supplier_id'],"A.supplier_id,A.supplier_name");
				if(!empty($supplier)){
					$list[$key]['supplier_id']=$goods['supplier_id'];
					$list[$key]['supplier_name']=$goods['supplier_name'];
					
				}
			}
			
		}
	}
	$list['number']=$number;
	return $list;
}



function  get_image_goods($goods_id){
	$sql="select original_img from ".$GLOBALS['ecs']->table('goods')." where goods_id={$goods_id}";
	return $GLOBALS['db']->getOne($sql);
}
//购物车列表
function get_cart_list($userid){
	//商品变价更改，购物车价格
	update_cart_price($userid);
	$file="C.goods_id,C.market_price,C.goods_price,C.spec_key,C.goods_num,C.goods_sn,C.goods_name,C.add_time,";
	$file.="G.store_count,G.original_img,G.supplier_id";
	$sql="select {$file} from ".$GLOBALS['ecs']->table('cart')." as C left join ".$GLOBALS['ecs']->table('goods')." as G ON C.goods_id=G.goods_id where G.is_on_sale=1 AND C.user_id={$userid} GROUP BY G.goods_id,G.supplier_id,C.spec_key ";
	$list=$GLOBALS['db']->getAll($sql);
	if(empty($list)){
		return false;
	}
	$rs=array();
	$newarr=array();
	if(!empty($list)){
		foreach ($list as $key=> $val){
			$i++;
			if(!empty($val['supplier_id'])){
				if(!empty($val['supplier_id'])){
					$row=get_supplier($val['supplier_id']);
					$rs[$val['supplier_id']]['supplier_name']=$row['supplier_name'];
					$rs[$val['supplier_id']]['company_name']=$row['company_name'];
					$rs[$val['supplier_id']]['business_sphere']=$row['business_sphere'];
					$rs[$val['supplier_id']]['supplier_mobile']=$row['mobile'];
				}else{
					$rs[$val['supplier_id']]['supplier_name']='';
					$rs[$val['supplier_id']]['company_name']='';
					$rs[$val['supplier_id']]['business_sphere']='';
					$rs[$val['supplier_id']]['supplier_mobile']="";
				}
				//运费
				$rs[$val['supplier_id']]['carriage']=0;
				$rs[$val['supplier_id']][$key]=$val;
				$rs[$val['supplier_id']][$key]['original_img']=IMG_HOST.$val['original_img'];
				
			}else{
				$rs[$i]['carriage']=0;
				$rs[$i]['supplier_name']='';
				$rs[$i]['company_name']='';
				$rs[$i]['business_sphere']='';
				if(empty($val['supplier_id'])){
					$val['supplier_id']='';
				}
				if(!empty($val['original_img'])){
					$val['original_img']=IMG_HOST.$val['original_img'];
				}
				$rs[$i][]=$val;
			}	
		}
	}
	return $rs;
}


function update_cart_price($userid){
	//商品价格变动，更改商品价格
	$sql="SELECT id, goods_price,goods_id,spec_key from ".$GLOBALS['ecs']->table('cart')." where user_id={$userid}";
	$list=$GLOBALS['db']->getAll($sql);
	if(!empty($list)){
		foreach ($list as $val){
			$price=get_real_price($val['goods_id'],$val['spec_key']);
			if($val['goods_price']!=$price){
				if(empty($price)){
					continue;
				}
				$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " set goods_price = {$price} WHERE user_id={$userid} AND id='{$val[id]}'";
				$GLOBALS['db']->query($sql);
			}
			
		}
	}
}


//获取商品的真实价格
function get_real_price($goods_id,$key_value){
	$sql="select G.goods_id,G.key,G.key_name,G.price,G.store_count from ".$GLOBALS['ecs']->table('goods_price')." as G where G.key='{$key_value}' AND G.goods_id={$goods_id}";
	$row=$GLOBALS['db']->getRow($sql);
	if(!empty($row['price'])){
		return $row['price'];
	}
	//获取商品详情信息
	$goods=get_goods_id($goods_id);
	return $goods['shop_price'];
}

//删除购物车方法
function del_cart($id,$userid){
	$sql="select count(*) from ".$GLOBALS['ecs']->table('cart')." where id={$id} AND user_id={$userid}";
	$rs=$GLOBALS['db']->getOne($sql);
	
	if($rs){
		$sql="delete from ".$GLOBALS['ecs']->table('cart')." where id={$id} AND user_id={$userid}";
		return $GLOBALS['db']->query($sql);
	}
	return false;
	
}

//添加购物车方法
function add_cart($goods_id,$number=1,$data){
	 /* 初始化要插入购物车的基本件数据 */
    $parent = array(
        'user_id'       => $data['user_id'],
        'session_id'    => $_REQUEST['ticket'],
        'goods_id'      => $goods_id,
        'goods_sn'      => addslashes($data['goods']['goods_sn']),
        'goods_name'    => addslashes($data['goods']['goods_name']),
        'market_price'  => $data['goods']['market_price'],
    	'supplier_id'	=> $data['goods']['supplier_id'],
    	'goods_price'   =>$data['price'],
    	'goods_num'	    =>$number,
    	'spec_key'      =>$data['spec_key'],
    	'spec_key_name'	=>$data['spec_key_name'],
        'add_time' =>time()
    );
    $spec_key=$data['spec_key'];
    $user_id=$data['user_id'];
    $goods_name=addslashes($data['goods']['goods_name']);
    //查询购物车，是否存在该规格商品，如果存在，增加数量
    $sql="select count(*) from ".$GLOBALS['ecs']->table('cart')." where user_id={$user_id} AND spec_key='{$spec_key}' ";
    $rs=$GLOBALS['db']->getOne($sql);
    
    if($rs){
    	$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " set goods_num = goods_num+{$number} WHERE user_id={$user_id} AND spec_key='{$spec_key}' ";
        return $GLOBALS['db']->query($sql);
    }
	return $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $parent, 'INSERT');
	
}

//计算购物车统计价，单价
function get_cart_goods($userid){
	/* 初始化 */
	$goods_list = array();
	$total = array(
			'goods_price'  => 0, // 本店售价合计（有格式）
			'market_price' => 0, // 市场售价合计（有格式）
			'saving'       => 0, // 节省金额（有格式）
			'save_rate'    => 0, // 节省百分比
			'goods_amount' => 0, // 本店售价合计（无格式）
			'goods_count'  => 0
	);
	
	/* 循环、统计 */
	$sql = "SELECT * " .
			" FROM " . $GLOBALS['ecs']->table('cart') . " " .
			" WHERE user_id ={$userid}";
	$res = $GLOBALS['db']->query($sql);
	
	while ($row = $GLOBALS['db']->fetchRow($res))
	{
		$total['goods_price']  += $row['goods_price'] * $row['goods_num'];
		$total['market_price'] += $row['market_price'] * $row['goods_num'];
		$total['goods_count']  += $row['goods_num'];
	
		$row['subtotal']     = price_format($row['goods_price'] * $row['goods_num'], false);
		$row['goods_price']  = price_format($row['goods_price'], false);
		$row['market_price'] = price_format($row['market_price'], false);
		$goods_list[] = $row;
	}
	$total['goods_amount'] = $total['goods_price'];
	$total['saving']       = price_format($total['market_price'] - $total['goods_price'], false);
	if ($total['market_price'] > 0)
	{
		$total['save_rate'] = $total['market_price'] ? round(($total['market_price'] - $total['goods_price']) *
				100 / $total['market_price']).'%' : 0;
	}
	$total['goods_price']  = price_format($total['goods_price'], false);
	$total['market_price'] = price_format($total['market_price'], false);
	
	return array('goods_list' => $goods_list, 'total' => $total);
}

function get_goods_id($good_id,$files=''){
	if(empty($files)){
		$file="goods_id,cat_id,goods_sn,goods_name,brand_id,store_count,market_price,shop_price,supplier_id,add_time,goods_type,spec_type,goods_content,shipping_price,is_free_shipping,original_img";
	}else {
		$file=$files;
	}
	//商品详情
	$sql="select {$file} from ".$GLOBALS['ecs']->table('goods')." where goods_id={$good_id}";
	return $GLOBALS['db']->getRow($sql);
}



//获取规格包装属性
function get_prolist($good_id){
	$sql="select A.attr_id, A.attr_value,B.attr_name from ".$GLOBALS['ecs']->table('goods_attr')." A left join ".$GLOBALS['ecs']->table('goods_attribute')." AS B ON A.attr_id=B.attr_id where goods_id={$good_id}";
	return $GLOBALS['db']->getRow($sql);
}
//店铺伤信息
function get_supplier($id,$files=""){
	$file=!empty($files) ? $files : "A.supplier_id,A.user_id,A.supplier_name,A.company_name,A.business_sphere,B.mobile";
	$sql="select {$file} from ".$GLOBALS['ecs']->table('supplier')." as A left join ".$GLOBALS['ecs']->table('supplier_user')." AS B on B.admin_id=A.supplier_id  where A.supplier_id={$id}";
	return $GLOBALS['db']->getRow($sql);
} 
//获取默认属性
function get_goods_defaultpro($good_id){
	$sql="select G.goods_id,G.key,G.key_name,G.price,G.store_count from ".$GLOBALS['ecs']->table('goods_price')." as G where G.goods_id={$good_id}";
	return $GLOBALS['db']->getRow($sql);
}
//获取商品属性
function get_is_pro($good_id){
	$sql="select GROUP_CONCAT(G.key) as ids from ".$GLOBALS['ecs']->table('goods_price')." as G where G.goods_id={$good_id} group by G.goods_id";
	$ids=$GLOBALS['db']->getOne($sql);
	$arr=array();
	if(!empty($ids)){
		$ids=str_replace('_',',',$ids);
		$arr=explode(',',$ids);
		$arr =array_unique($arr);
	}
	return $arr;
}


//获取商品一级分类
function get_cate_list(){
  $sql="SELECT id,name,mobile_name,image from ".$GLOBALS['ecs']->table('goods_category')." where parent_id=0 AND is_show=1 order by sort_order limit 0,8";
  return $GLOBALS['db']->getAll($sql);
}

//获取三级分类id
function get_three_cate($cateid){
	$cateids=$cateid;
	$sql="SELECT GROUP_CONCAT(id) as ids from ".$GLOBALS['ecs']->table('goods_category')." where parent_id={$cateid} group by parent_id ";
	$row=$GLOBALS['db']->getRow($sql);
	if(!empty($row)){
		$cateids.=",".$row['ids'];
		$where=" where parent_id in( ".$row['ids']." )";
		$sql="SELECT GROUP_CONCAT(id) as ids from ".$GLOBALS['ecs']->table('goods_category')." {$where} group by parent_id ";
		$row2=$GLOBALS['db']->getRow($sql);
		if($row2){
			$cateids.=",".$row2['ids'];
			$where=" where parent_id in( ".$row2['ids']." )";
			$sql="SELECT GROUP_CONCAT(id) as ids from ".$GLOBALS['ecs']->table('goods_category')." {$where} group by parent_id ";
			$row3=$GLOBALS['db']->getRow($sql);
			if(!empty($row3)){
				$cateids.=",".$row3['ids'];
			}
		}
	}
	return $cateids;
}
//多张图片解析
function reArrayFiles($file)
{
	$file_ary = array();
	$file_count = count($file['name']);
	$file_key = array_keys($file);

	for($i=0;$i<$file_count;$i++)
	{
		foreach($file_key as $val)
		{
			$file_ary[$i][$val] = $file[$val][$i];
		}
	}
	return $file_ary;
}

function parse_path($array)
{
	if (!is_array($array)) {
		$array = func_get_args();
	}
	if ('\\' == DIRECTORY_SEPARATOR) {
		return implode('\\', $array);
	} else {
		return implode('/', $array);
	}
}
//检测是否存在活动订单
function check_active_order($order_id){
	$sql="select count(*) from ".$GLOBALS['ecs']->table('order_goods')." where order_id={$order_id} AND prom_id !=0 AND prom_type !=0";
	return $GLOBALS['db']->getOne($sql);
}

function check_nopay_order($userid){
  $sql="select A.order_id,A.order_status,A.pay_status,A.add_time,B.prom_type,B.prom_id from ".$GLOBALS['ecs']->table('order')." as A LEFT JOIN ".$GLOBALS['ecs']->table('order_goods')." as B ON A.order_id=B.order_id where A.user_id={$userid} AND A.order_status=0 AND A.pay_status=0";
  $list=$GLOBALS['db']->getAll($sql);
  $temptime=time();
  if(!empty($list)){
  	foreach ($list as $order){
  		if(!empty($order['prom_type']) && !empty($order['prom_id'])){
  				if($temptime>$order['add_time']+1800){
  					//修改状态
  					$sql=" update ".$GLOBALS['ecs']->table('order')." set order_status=3 where order_id={$order['order_id']}";
  					$GLOBALS['db']->query($sql);
					$sql=" update ".$GLOBALS['ecs']->table('panic_buying')." set order_num = order_num-1 , buy_num = order_num -1 where id = {$order['prom_id']}";
					$GLOBALS['db']->query($sql);
  				}
  			}else{
  				if($temptime>$order['add_time']+48*3600){
  					//修改状态
  					$sql=" update ".$GLOBALS['ecs']->table('order')." set order_status=3 where order_id={$order['order_id']}";
  					$GLOBALS['db']->query($sql);
  				}
  			}
  		}
  	}
}


//查看退货商品是否已撤销，若撤销，删除退货信息
function check_back_goods($order_id,$order_sn,$goods_id,$userid,$spec_key=""){
    if(!empty($spec_key)){
		$sql="select * from ".$GLOBALS['ecs']->table('back_order')." where order_id={$order_id} AND order_sn={$order_sn} AND user_id={$userid} AND goods_id={$goods_id} AND spec_key='{$spec_key}'";
	}else{
		$sql="select * from ".$GLOBALS['ecs']->table('back_order')." where order_id={$order_id} AND order_sn={$order_sn} AND user_id={$userid} AND goods_id={$goods_id}";
	}
	$row=$GLOBALS['db']->getRow($sql);
	//申请失败，删除退货商品，退货表
	if($row['type']=='1' && $row['status']=="-1"){
		if(!empty($spec_key)){
			$sql="DELETE FROM ".$GLOBALS['ecs']->table('back_order')." WHERE  order_id={$order_id} AND order_sn={$order_sn} AND user_id={$userid} AND goods_id={$goods_id} AND spec_key='{$spec_key}'";
		}else{
			$sql="DELETE FROM ".$GLOBALS['ecs']->table('back_order')." WHERE  order_id={$order_id} AND order_sn={$order_sn} AND user_id={$userid} AND goods_id={$goods_id} ";
		}
		$GLOBALS['db']->Query($sql);
		
		$sql2="DELETE FROM ".$GLOBALS['ecs']->table('back_goods')." WHERE  back_id={$row['id']} AND goods_id={$goods_id} ";
		$GLOBALS['db']->Query($sql2);
		return true;
	}
	return false;
}

//填写物流，修改表退换货的状态
function bank_order_status($rec_id){
	//提交物流信息，修改退款，退货状态
	$sql="SELECT A.back_id,B.order_sn,B.goods_id,B.status,B.spec_key,B.id,B.status from ".$GLOBALS['ecs']->table('back_goods')." as A left join ".$GLOBALS['ecs']->table('back_order')." as B on A.back_id =B.id where A.rec_id={$rec_id}";
	$back=$GLOBALS['db']->getRow($sql);
	if($back['status']=='1'){
		$sql = "UPDATE " . $GLOBALS['ecs']->table('back_order') . " set status = 2 WHERE id={$back[id]}";
		return $GLOBALS['db']->query($sql);
	}
	return false;
}


?>