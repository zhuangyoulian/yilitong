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

//添加购物车接口
if($action=='add_to_cart'){
	//base64_encode
	//base64_decode();
	if(empty($userinfo)){
		$rs=array('result'=>'0','info'=>'请您先登录，再加入购物车');
		exit($json->json_encode_ex($rs));
	}
	$data=array();
	$goods_id=intval(trim($_REQUEST['goods_id']));
	/* if($goods_id=='1509'){
		$rs=array('result'=>'0','info'=>'此商品为活动商品，不能加入购物车，请用pc网页抢购');
		exit($json->json_encode_ex($rs));
	} */
	$number=!empty($_REQUEST['number']) ? intval(trim($_REQUEST['number'])) : 1 ;
	$atrr=trim($_REQUEST['attr']);
	
	//判断是否为活动商品
	$rs_act=check_act($userinfo[userid],$number,$goods_id);
	
	if($rs_act['result']=='0'){
		exit($json->json_encode_ex($rs_act));
	}elseif($rs_act['result']=='1'){
		$data['prom_id']=$rs_act['prom_id'];
		$data['prom_type']=$rs_act['prom_type'];
		if(!empty($rs_act['price'])){
			$act_price=$rs_act['price'];
		}
	}
	
	//$goods_id=3;
	//$atrr='114,117';
	$key_value=str_replace(",","_",$atrr);
	$arr=explode(',',$atrr);
	//看商品是否存在属性，有属性，必须要传属性
	$prolist=get_is_pro($goods_id);
	//查看是否有交集
	if(!empty($prolist)){
		$temp=array_intersect($prolist,$arr);
		$count1=count($arr);
		$count2=count($temp);
		if($count1!=$count2){
			$rs=array('result'=>'0','info'=>'属性参数错误');
			exit($json->json_encode_ex($rs));
		}
	}
	if(!empty($key_value) && !empty($goods_id)){
		$sql="select G.goods_id,G.key,G.key_name,G.price,G.store_count from ".$GLOBALS['ecs']->table('goods_price')." as G where G.key='{$key_value}' AND G.goods_id={$goods_id}";
		$row=$GLOBALS['db']->getRow($sql);
	}
	
	//获取商品详情信息
	$goods=get_goods_id($goods_id);
	if($act_price>0){
		//活动价格
		$data['price']=$act_price;
	}else{
		$data['price']=!empty($row['price']) ? $row['price'] :$goods['shop_price'];
	}
	
	$data['store_count']=!empty($row['store_count']) ? $row['store_count'] :$goods['store_count'];
	$data['spec_key']=!empty($row['key']) ? $row['key'] : '';
	$data['spec_key_name']=!empty($row['key_name']) ? $row['key_name'] :'';
	
	if($number>$data['store_count']){
		$rs=array('result'=>'1','info'=>'库存不足');
		exit($json->json_encode_ex($rs));
	}
	$data['user_id']=$userinfo['userid'];
	$data['goods']=$goods;
	$data['selected']="1";
	$sql="select count(*) from ".$GLOBALS['ecs']->table('cart')." where user_id={$userinfo['userid']}";
	$count=$GLOBALS['db']->getOne($sql);
	if($count>15){
		$rs=array('result'=>'1','info'=>'最多添加15件商品');
		exit($json->json_encode_ex($rs));
	}

	//加入购物车
	if(add_cart($goods_id,$number,$data)){
		$rs=array('result'=>'1','info'=>'添加购物车成功');
		exit($json->json_encode_ex($rs));
	}else{
		$rs=array('result'=>'0','info'=>'添加购物车失败');
		exit($json->json_encode_ex($rs));
	}  
}
//修改购物车
elseif($action=="update_cart"){
	$ids=$_REQUEST['cid'];
	$userid=$userinfo['userid'];
	if(empty($ids) || empty($userid)){
		$rs=array('result'=>'0','info'=>'缺少参数');
		exit($json->json_encode_ex($rs));
	}
	$arr=explode(',',$ids);
	$type=1;
	if(!empty($arr)){
		foreach ($arr as $val){
			$str=explode('|',$val);
			$id=$str['0'];
			$number=!empty($str['1']) ? $str['1'] : '1';
			//获取购物车商品信息
			$sql="select * from ".$GLOBALS['ecs']->table('cart')." where id={$id} AND user_id={$userid}";
			$good_cart=$GLOBALS['db']->getRow($sql);
			if(empty($good_cart)){
				$type=2;
				break;
				//$rs=array('result'=>'0','info'=>'商品不存在');
				//exit($json->json_encode_ex($rs));
			}
			$key_value=$good_cart['spec_key'];
			$goods_id=$good_cart['goods_id'];
			
			//获取商品详情信息
			$goods=get_goods_id($goods_id);
			
			//查看库存是否够了
			if(!empty($key_value) && !empty($goods_id)){
				$sql="select G.goods_id,G.key,G.key_name,G.price,G.store_count from ".$GLOBALS['ecs']->table('goods_price')." as G where G.key='{$key_value}' AND G.goods_id={$goods_id}";
				$row=$GLOBALS['db']->getRow($sql);
			}
			$price=!empty($row['price']) ? $row['price'] :$goods['shop_price'];
			$store_count=!empty($row['store_count']) ? $row['store_count'] :$goods['store_count'];
			if($number>$store_count){
				$number=$store_count;
			}
			$sql = " SELECT count(*) from ".$GLOBALS['ecs']->table('cart')." WHERE id='{$id}' AND user_id='{$userid}'";
			$flag=$GLOBALS['db']->getOne($sql);
			if(!$flag){
				$type=3;
				break;
				//$rs=array('result'=>'0','info'=>'购物车中不存在此商品');
				//exit($json->json_encode_ex($rs));
			}
			//修改购物车数量
			$sql = "UPDATE ".$GLOBALS['ecs']->table('cart')." SET goods_num= '{$number}', goods_price = '{$price}' WHERE id='{$id}' AND user_id='{$userid}'";
			$rs=$GLOBALS['db']->query($sql);
			if(!$rs){
				$type=4;
				break ;
			}
			
		}
	}
	
	if($type=='1'){
		//计算购物车统计价，单价
		$total = get_cart_goods($userid,'');
		$total=$total['total'];
		$rs=array('result'=>'1','info'=>'成功','total'=>$total);
		exit($json->json_encode_ex($rs));
	}elseif($type=='2'){
		$rs=array('result'=>'0','info'=>'商品不存在');
		exit($json->json_encode_ex($rs));
	}elseif($type==3){
		$rs=array('result'=>'0','info'=>'购物车中不存在此商品');
		exit($json->json_encode_ex($rs));
	}else{
		$rs=array('result'=>'0','info'=>'更新失败');
		exit($json->json_encode_ex($rs));
	}
	
	
}
//删除购物车商品
elseif($action=="del_cart"){
	$id=$_REQUEST['cid'];
	if(empty($id) || empty($userinfo['userid'])){
		$rs=array('result'=>'0','info'=>'缺少必要参数');
		exit($json->json_encode_ex($rs));
	}
	$row=del_cart($id,$userinfo['userid']);
	if($row){
		$rs=array('result'=>'1','info'=>'删除成功');
		exit($json->json_encode_ex($rs));
	}else{
		$rs=array('result'=>'0','info'=>'删除失败');
		exit($json->json_encode_ex($rs));
	}
}
//购物车商品列表
elseif($action=='cart_list'){
	$userid=$userinfo['userid'];
	$cart_list=get_cart_list($userid);
	$all_price=$cart_list['all_price'];
	$all_carriage=$cart_list['all_carriage'];
	unset($cart_list['all_carriage']);
	unset($cart_list['all_price']);
	if(!empty($cart_list)){
		$rs=array('result'=>'1','info'=>'成功','cart_list'=>$cart_list,'all_price'=>$all_price,'all_carriage'=>$all_carriage);
		exit($json->json_encode_ex($rs));
	}else{
		$rs=array('result'=>'1','info'=>'无数据',"cart_list"=>array());
		exit($json->json_encode_ex($rs));
	}
}

//确认订单接口【检测购物车】接口
elseif($action=='check_cart'){
	$userid=$userinfo['userid'];
	//选中的购物车里边的id
	$ids=trim($_REQUEST['cart_ids']);
	
	//过滤下架的商品
	$sql="select GROUP_CONCAT(A.id) from ".$GLOBALS['ecs']->table('cart')." as A left join ".$GLOBALS['ecs']->table('goods')." as B ON A.goods_id=B.goods_id where B.examine=1 AND A.user_id={$userid} AND A.id in ({$ids})";
	$ids=$GLOBALS['db']->getOne($sql);
	if(empty($ids)){
		$rs=array('result'=>'0','info'=>'购物车没有商品');
		exit($json->json_encode_ex($rs));
	}
	/* $sql="select GROUP_CONCAT(id) as ids from ".$GLOBALS['ecs']->table('cart')." where id in ({$ids}) ";
	$cart=$GLOBALS['db']->getOne($sql);
	if(empty($cart)){
		$rs=array('result'=>'0','info'=>'购物车为空');
		exit($json->json_encode_ex($rs));
	} */
	//获取购物商品
	$rs=get_condition_cartlist($userid,$ids);
	//库存不足
	if(!empty($rs['goods_id'])){
		$strid=implode(',',$rs['goods_id']);
		unset($rs['goods_id']);
		$rs=array('result'=>'0','info'=>'库存不足','good_ids'=>$strid);
		exit($json->json_encode_ex($rs));
	}
	
	//加载默认地址
	$sql="select address_id,consignee,province,city,district,address,mobile,is_default from ".$GLOBALS['ecs']->table('user_address')." where user_id={$userid} AND is_default=1 ";
	$address=$GLOBALS['db']->getRow($sql);
	if(!empty($address)){
		if (is_numeric($address['province'])) {
			$address['province_name']=get_regin_name($address['province']);
			$address['city_name']=get_regin_name($address['city']);
			$address['district_name']=get_regin_name($address['district']);
		}else{
			$address['province_name']=$address['province'];
			$address['city_name']=$address['city'];
			$address['district_name']=$address['district'];
		}
	}else{
		$address=(object)array();
	}
	
	//计算总的价格
	$where=" id in ($ids) ";
	$total = get_cart_goods($userid,$where);
	$price=price_format($total['total']['goods_price']+$rs['all_carriage'],false);
	
	unset($rs['all_carriage']);
	$rs=array('result'=>'1','info'=>'请求成功','allprice'=>$price,'cart_ids'=>$ids,'good_list'=>$rs,'address'=>$address);
	exit($json->json_encode_ex($rs));
}

//去支付，确认订单接口
elseif($action=="confirm_order"){
	$address_id=$_REQUEST['address_id'];
	$userid=$userinfo['userid'];
	$ids=trim($_REQUEST['cart_ids']);
	
	if(empty($ids) || empty($address_id) || empty($userid)){
		$rs=array('result'=>'0','info'=>'缺少必要参数');
		exit($json->json_encode_ex($rs));
	}
	
	//检测是有默认填写的地址
	$sql="select address_id, user_id,consignee,province,city,district,address,mobile from".$GLOBALS['ecs']->table('user_address')." where user_id={$userid} AND address_id={$address_id}";
	$address=$GLOBALS['db']->getRow($sql);
	if(empty($address)){
		$rs=array('result'=>'0','info'=>'请填写收货地址');
		exit($json->json_encode_ex($rs));
	}
	//过滤下架的商品
	$sql="select GROUP_CONCAT(A.id) from ".$GLOBALS['ecs']->table('cart')." as A left join ".$GLOBALS['ecs']->table('goods')." as B ON A.goods_id=B.goods_id where B.examine=1 AND A.user_id={$userid} AND A.id in ({$ids})";
	$ids=$GLOBALS['db']->getOne($sql);
	if(empty($ids)){
		$rs=array('result'=>'0','info'=>'购物车没有商品');
		exit($json->json_encode_ex($rs));
	}
	
	//检测购物车里边是否有对应商品
	$sql="select count(*) from".$GLOBALS['ecs']->table('cart')." where user_id={$userid} AND id in ({$ids})";
	$cartnum=$GLOBALS['db']->getOne($sql);
	if(empty($cartnum)){
		$rs=array('result'=>'0','info'=>'购物车没有商品');
		exit($json->json_encode_ex($rs));
	}
	//检测是否有库存
	$rs=get_condition_cartlist($userid,$ids);
	
	if(!empty($rs['goods_id'])){
		$strid=implode(',',$rs['goods_id']);
		$rs=array('result'=>'0','info'=>'库存不足','good_ids'=>$strid);
		exit($json->json_encode_ex($rs));
	}
	
	//写入事务触发器
	$GLOBALS['db']->query("begin");
	$is_act=true;
	//增加活动判断
	$act_rs=check_activity($userid,$ids);
	if($act_rs['result']=='5'){
		$is_act=false;
	}elseif($act_rs['result']=='1' || $act_rs['result']=='2' || $act_rs['result']=='3' || $act_rs['result']=='4'){
		$act_rs['result']="0";
		exit($json->json_encode_ex($act_rs));
	}
	
	
	//获取总价格
	$where=" id in ({$ids})";
	$total = get_cart_goods($userid,$where);
	$price=price_format($total['total']['goods_price']+$rs['all_carriage'],false);
	
	//总的邮费
	$shipping_price=price_format($rs['all_carriage'],false);
	unset($rs['all_carriage']);
	
	$ordertype=true;
	$carttype=true;
	//减去相应的库存
	$flag1=update_shop_num($userid,$where);
	$count_num=count($rs);

	$order_sn=get_order_sn();
	if(!empty($order_sn)){
		$sql="select count(*) from ".$GLOBALS['ecs']->table('order')." where order_sn='".$order_sn."'";
		$count=$db->getOne($sql);
		if($count>0){
			$order_sn=get_order_sn();
		}
	}
	//只有一个，为一个订单
	if($count_num==1){
		$sid=$rs['0']['supplier_id'];
		$sql="select is_designer from ".$GLOBALS['ecs']->table('supplier')." where supplier_id={$sid} ";
		$is_designer=$GLOBALS['db']->getOne($sql);
		//订单留言
		$user_note=$_REQUEST["note_{$sid}"];
		//插入订单信息
		$order=array(
				'order_sn'=>$order_sn,
				'user_id'=>$userid,
				'order_status'=>0,
				'pay_status'=>0,
				'consignee'=>$address['consignee'],
				'province'=>$address['province'],
				'city'=>$address['city'],
				'district'=>$address['district'],
				'address'=>$address['address'],
				'mobile'=>$address['mobile'],
				'goods_price'=>$total['total']['goods_price'],
				'order_amount'=>$price,
				'total_amount'=>$price,
				'shipping_price'=>$shipping_price,
				'add_time'=>time(),
				'source'=>'app',
				'is_parent'=>'0',
				'supplier_id'=>$rs['0']['supplier_id'],
				'supplier_name'=>$rs['0']['supplier_name'],
				'user_note'=>$user_note,
				'is_designer' =>$is_designer
		);
		
		$flag2=$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('order'), $order, 'INSERT');
		$new_order_id=$order_id = $db->insert_id();
		
		/* 插入订单商品 */
		$sql = "INSERT INTO " . $ecs->table('order_goods') . "( " .
				"order_id, goods_id, goods_name, goods_sn, goods_num, market_price, ".
				"goods_price,spec_key,spec_key_name,prom_type,prom_id) ".
				" SELECT '$new_order_id', goods_id, goods_name, goods_sn, goods_num, market_price, ".
				"goods_price,spec_key,spec_key_name,prom_type,prom_id".
				" FROM " .$ecs->table('cart') .
				" WHERE user_id = '".$userid."' AND id in ($ids)";
		$flag3=$db->query($sql);
		
	}else{
		//生成父单,多个订单
		$order=array(
				'order_sn'=>$order_sn,
				'user_id'=>$userid,
				'order_status'=>0,
				'pay_status'=>0,
				'consignee'=>$address['consignee'],
				'province'=>$address['province'],
				'city'=>$address['city'],
				'district'=>$address['district'],
				'address'=>$address['address'],
				'mobile'=>$address['mobile'],
				'goods_price'=>$total['total']['goods_price'],
				'order_amount'=>$price,
				'total_amount'=>$price,
				'shipping_price'=>$shipping_price,
				'add_time'=>time(),
				'source'=>'app',
				'is_parent'=>'1',
				'supplier_id'=>"",
		);
	
		$flag2=$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('order'), $order, 'INSERT');
		$new_order_id=$order_id = $db->insert_id();
		
		if(!empty($rs)){
			foreach ($rs as $key =>$val){
				$goods_amount=0;
				$arrid=array();
				$all_carriage=array();
				if(!empty($val['list'])){
					 foreach ($val['list'] as $val2){
					 	$arrid[]=$val2['id'];
					 	$goods_amount+=price_format($val2['goods_price']*$val2['goods_num'],false);
					 	if($val2['is_free_shipping']!='1'){
					 		$all_carriage[]=price_format($val2['shipping_price'],false);
					 	}
					 }
				}
				$carriage=0;
				if(!empty($all_carriage)){
					//获取最大的邮费
					sort($all_carriage);
					$carriage = end($all_carriage);
				}
				$order_amount=$total_amount=price_format($carriage+$goods_amount,false);
				$sid=$val['supplier_id'];
				$sql="select is_designer from ".$GLOBALS['ecs']->table('supplier')." where supplier_id={$sid} ";
				$is_designer=$GLOBALS['db']->getOne($sql);
				$user_note=$_REQUEST["note_{$sid}"];
				$order=array(
						'order_sn'=>get_order_sn(),
						'user_id'=>$userid,
						'order_status'=>0,
						'pay_status'=>0,
						'consignee'=>$address['consignee'],
						'province'=>$address['province'],
						'city'=>$address['city'],
						'district'=>$address['district'],
						'address'=>$address['address'],
						'mobile'=>$address['mobile'],
						'goods_price'=>$goods_amount,
						'order_amount'=>$order_amount,
						'total_amount'=>$total_amount,
						'shipping_price'=>$carriage,
						'add_time'=>time(),
						'source'=>'app',
						'is_parent'=>'0',
						'parent_id'=>$new_order_id,
						'supplier_id'=>$val['supplier_id'],
						'supplier_name'=>$val['supplier_name'],
						'user_note'=>$user_note,
						'is_designer' =>$is_designer
				);
				$inser=$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('order'), $order, 'INSERT');
				if(!$inser){
					$ordertype=false;
					break;
				}
				$new_son_id=$db->insert_id();
				
				if(!empty($arrid)){
					$cids=implode(",",$arrid);
				}
				/* 插入订单商品 */
				$sql = "INSERT INTO " . $ecs->table('order_goods') . "( " .
						"order_id, goods_id, goods_name, goods_sn, goods_num, market_price, ".
						"goods_price,spec_key,spec_key_name) ".
						" SELECT '$new_son_id', goods_id, goods_name, goods_sn, goods_num, market_price, ".
						"goods_price,spec_key,spec_key_name ".
						" FROM " .$ecs->table('cart') .
						" WHERE user_id = '".$userid."' AND id in ($cids)";
				
				$flag3=$db->query($sql);
				if(!$flag3){
					$carttype=false;
					break;
				}
				
			}
		}
	}

	//删除购物车信息
	$sql="select count(*) from ".$ecs->table('cart') ." WHERE user_id = '".$userid."' AND id in ($ids) ";
	$count=$db->getOne($sql); 
	
	 $flag4=false;
	if($count>0){
		$sql="DELETE from ". $ecs->table('cart') ." WHERE user_id = '".$userid."' AND id in ($ids) ";
		$flag4=$db->query($sql);
	} 
	//echo "flag1={$flag1}&flag2={$flag2}&flag3={$flag3}&flag4={$flag4}&ordertype={$ordertype}&carttype={$carttype}";

	if($is_act && $flag1 && $flag2 && $flag3 && $flag4 && $ordertype && $carttype){
		$GLOBALS['db']->query("COMMIT");
		$rs=array('result'=>'1','info'=>'请求成功','order_sn'=>$order_sn,'money'=>$price,'order_id'=>$new_order_id);
		exit($json->json_encode_ex($rs));
	}else{
		$GLOBALS['db']->query("ROLLBACK");
		$rs=array('result'=>'0','info'=>'请求失败');
		exit($json->json_encode_ex($rs));
	}
}

//立即购买接口
elseif($action=="buy_now"){
	$userid=$userinfo['userid'];
	$number=!empty($_REQUEST['number']) ? $_REQUEST['number'] : 1;
	//选中的购物车里边的id
	$goods_id=trim($_REQUEST['goods_id']);
	/* if($goods_id=='1509'){
		$rs=array('result'=>'0','info'=>'此商品为活动商品，不能加入购物车，请用pc网页抢购');
		exit($json->json_encode_ex($rs));
	} */
	
	$atrr=!empty($_REQUEST['attr']) ? trim($_REQUEST['attr']) : "";
	if($atrr){
		$key_value=str_replace(",","_",$atrr);
		$arr=explode(',',$atrr);
		//看商品是否存在属性，有属性，必须要传属性
		$prolist=get_is_pro($goods_id);
		//查看是否有交集
		if(!empty($prolist)){
			$temp=array_intersect($prolist,$arr);
			$count1=count($arr);
			$count2=count($temp);
			if($count1!=$count2){
				$rs=array('result'=>'0','info'=>'属性参数错误');
				exit($json->json_encode_ex($rs));
				exit;
			}
		}
		
		if(!empty($key_value) && !empty($goods_id)){
			$sql="select G.goods_id,G.key,G.key_name,G.price,G.store_count from ".$GLOBALS['ecs']->table('goods_price')." as G where G.key='{$key_value}' AND G.goods_id={$goods_id}";
			$row=$GLOBALS['db']->getRow($sql);
		}
		
	}else{
		$sql="select G.goods_id,G.key,G.key_name,G.price,G.store_count from ".$GLOBALS['ecs']->table('goods_price')." as G where  G.goods_id={$goods_id}";
		$row=$GLOBALS['db']->getRow($sql);
	}
	
	  
		//获取商品详情信息
	   $goods=get_goods_id($goods_id);
		$data['price']=!empty($row['price']) ? $row['price'] :$goods['shop_price'];
		if(!empty($row['price'])){
			$goods['shop_price']=$row['price'];
		}
		//立即购买检测商品
		$rs_act=check_act($userid,$number,$goods_id);
		if($rs_act['result']=='0'){
			exit($json->json_encode_ex($rs_act));
		}elseif($rs_act['result']=='1'){
			if(!empty($rs_act['price'])){
				$goods['shop_price']=$rs_act['price'];
			}
		}
		
		$data['store_count']=!empty($row['store_count']) ? $row['store_count'] :$goods['store_count'];
		$data['spec_key']=!empty($row['key']) ? $row['key'] : '';
		$data['spec_key_name']=!empty($row['key_name']) ? $row['key_name'] :'';
		$goods['spec_key_name']=!empty($row['key_name']) ? $row['key_name'] :'';
		if($number>$data['store_count']){
			$rs=array('result'=>'0','info'=>'库存不足');
			exit($json->json_encode_ex($rs));
		}
	
	//加载默认地址
	$sql="select address_id,consignee,province,city,district,address,mobile,is_default from ".$GLOBALS['ecs']->table('user_address')." where user_id={$userid} AND is_default=1 ";
	$address=$GLOBALS['db']->getRow($sql);
	if(!empty($address)){
		if (is_numeric($address['province'])) {
			$address['province_name']=get_regin_name($address['province']);
			$address['city_name']=get_regin_name($address['city']);
			$address['district_name']=get_regin_name($address['district']);
		}else{
			$address['province_name']=$address['province'];
			$address['city_name']=$address['city'];
			$address['district_name']=$address['district'];
		}
	}else{
		$address=(object)array();
	}
	
	//计算总的价格,运费加上商品的价格
	if(!empty($goods['original_img'])){
		$goods['original_img']=IMG_HOST.$goods['original_img'];
	}
	//is_free_shipping=1 包邮
	if($goods['is_free_shipping']=='1'){
		$price=price_format($goods['shop_price']*$number,false);
		$goods['shipping_price']='0.00';
	}else{
		$price=price_format($goods['shop_price']*$number+$goods['shipping_price'],false);
	}
	$goods['number']=$number;
	$goods['attr']=$atrr;
	unset($goods['goods_content']);
	//获取入驻店铺信息
	if(!empty($goods['supplier_id'])){
		$row=get_supplier($goods['supplier_id']);
	}
	$goods['supplier_name']=!empty($row['supplier_name']) ? $row['supplier_name'] : '';
	$goods['company_name']=!empty($row['company_name']) ? $row['company_name'] : '';
	$goods['business_sphere']=!empty($row['business_sphere']) ? $row['business_sphere'] : '';
	$rs=array('result'=>'1','info'=>'请求成功','goods'=>$goods,'address'=>$address,'allprice'=>$price);
	exit($json->json_encode_ex($rs));
}
//立即购买生成订单接口
elseif ($action=='buy_now_order'){
	
	$address_id=$_REQUEST['address_id'];
	$userid=$userinfo['userid'];
	$goods_id=$_REQUEST['goods_id'];
	$number=$_REQUEST['number'];
	$atrr=trim($_REQUEST['attr']);
	
	//$ids=trim($_REQUEST['cart_ids']);
	//$pay_type=$_REQUEST['pay_type'];
	
	if(empty($goods_id) || empty($address_id) || empty($number)){
		$rs=array('result'=>'0','info'=>'缺少必要参数');
		exit($json->json_encode_ex($rs));
	}
	//检测是有默认填写的地址
	$sql="select address_id, user_id,consignee,province,city,district,address,mobile from".$GLOBALS['ecs']->table('user_address')." where user_id={$userid} AND address_id={$address_id}";

	$address=$GLOBALS['db']->getRow($sql);

	if(empty($address)){
		$rs=array('result'=>'0','info'=>'请填写收货地址');
		exit($json->json_encode_ex($rs));
	}
	
	  //检测是否有库存
    	$key_value=str_replace(",","_",$atrr);
    	$arr=explode(',',$atrr);
    
    	$d_arr = array_reverse($arr);
    	foreach ($d_arr as $key=> $val){
    		if ($key==0) {
    			$b_key_value = $val;
    		}else{
    			$b_key_value = $b_key_value."_".$val;
    		}
    	}
    	
    	//看商品是否存在属性，有属性，必须要传属性
    	$prolist=get_is_pro($goods_id);
    	//查看是否有交集
    	if(!empty($prolist)){
    		$temp=array_intersect($prolist,$arr);
    		$count1=count($arr);
    		$count2=count($temp);
    		if($count1!=$count2){
    			$rs=array('result'=>'0','info'=>'属性参数错误');
    			exit($json->json_encode_ex($rs));
    		}
    	}
	 
	  if(!empty($key_value) && !empty($goods_id)){
			$sql="select G.goods_id,G.key,G.key_name,G.price,G.store_count from ".$GLOBALS['ecs']->table('goods_price')." as G where (G.key='{$b_key_value}' or G.key='{$key_value}') AND G.goods_id={$goods_id}";
			
			$row=$GLOBALS['db']->getRow($sql);
		}
		
		//获取商品详情信息
		$goods=get_goods_id($goods_id);
		
		$data['price']=!empty($row['price']) ? $row['price'] :$goods['shop_price'];
		
		if(!empty($key_value) && !empty($goods_id)){
			$data['store_count']=!empty($row['store_count']) ? $row['store_count'] : 0;
		}else{
			$data['store_count']=!empty($goods['store_count']) ? $goods['store_count'] :0;
		}
	
		//$data['store_count']=!empty($row['store_count']) ? $row['store_count'] :$goods['store_count'];
		if($number>$data['store_count']){
			$rs=array('result'=>'0','info'=>'库存不足');
			exit($json->json_encode_ex($rs));
		}
	
	$shipping_price=0;
	//写入事务触发器
	$GLOBALS['db']->query("begin");
	$is_act=true;
	//检测是否参加活动
	$act_rs=check_goods_activity($goods_id,$userid,$number);
	if($act_rs['result']=='5'){
		$is_act=false;
	}elseif($act_rs['result']=='1' || $act_rs['result']=='2' || $act_rs['result']=='3' || $act_rs['result']=='4'){
		$act_rs['result']="0";
		exit($json->json_encode_ex($act_rs));
	}elseif($act_rs['result']=='0'){
		//满足条件，可以参加活动
		if(!empty($act_rs['price'])){
			$data['price']=$act_rs['price'];
		}
	}
	
	//获取总价格
    if($goods['is_free_shipping']=='1'){
		$price=price_format($data['price']*$number,false);
	}else{
		//获取邮费
		$shipping_price=price_format($goods['shipping_price'],false);
		$price=price_format(($data['price']*$number)+$goods['shipping_price'],false);
	}
	$goods_price=price_format($data['price']*$number,false);
	
	
	//减去相应的库存
	$flag1=update_storenum_BygoodsId($goods_id,$number,$key_value);
	$order_sn=get_order_sn();
	if(!empty($order_sn)){
		$sql="select count(*) from ".$GLOBALS['ecs']->table('order')." where order_sn='".$order_sn."'";
		$count=$db->getOne($sql);
		if($count>0){
			$order_sn=get_order_sn();
		}
	}
	if(!empty($goods['supplier_id'])){
		$supplier=get_supplier($goods['supplier_id']);
	}
	$supid=$goods['supplier_id'];
	$note=$_REQUEST["note_$supid"];
	//插入订单信息
	
	$order=array(
			'order_sn'=>$order_sn,
			'user_id'=>$userid,
			'order_status'=>0,
			'pay_status'=>0,
			'consignee'=>$address['consignee'],
			'province'=>$address['province'],
			'city'=>$address['city'],
			'district'=>$address['district'],
			'address'=>$address['address'],
			'mobile'=>$address['mobile'],
			'goods_price'=>$goods_price,
			'order_amount'=>$price,
			'total_amount'=>$price,
			'shipping_price'=>$shipping_price,
			'add_time'=>time(),
			'source'=>'app',
			'is_parent'=>'0',
			'supplier_id'=>$goods['supplier_id'],
			'supplier_name'=>$supplier['supplier_name'],
			'user_note'=>$note,
			'is_designer' =>$goods['is_designer']
	);
	
	$flag2=$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('order'), $order, 'INSERT');
	$new_order_id=$order_id = $db->insert_id();
	
	/* 插入订单商品 */
	$sql = "INSERT INTO " . $ecs->table('order_goods') . "( " .
			"order_id, goods_id, goods_name, goods_sn, goods_num, market_price, ".
			"goods_price,spec_key,spec_key_name,prom_type,prom_id) values('{$new_order_id}', {$goods_id}, '{$goods[goods_name]}', '{$goods[goods_sn]}', {$number}, {$goods[market_price]},{$data[price]},'{$row[key]}','{$row[key_name]}','$act_rs[prom_type]','$act_rs[prom_id]')";
	$flag3=$db->query($sql);
	if($is_act && $flag1 && $flag2 && $flag3){
		$GLOBALS['db']->query("COMMIT");
		$rs=array('result'=>'1','info'=>'请求成功','order_sn'=>$order_sn,'money'=>$price,'order_id'=>$new_order_id);
		exit($json->json_encode_ex($rs));
	}else{
		$GLOBALS['db']->query("ROLLBACK");
		$rs=array('result'=>'0','info'=>'请求失败');
		exit($json->json_encode_ex($rs));
	}
}
//购物车是否选择状态
elseif($action=="cart_sel"){
	$cid=$_REQUEST['cid'];
	$type=!empty($_REQUEST['type']) ? $_REQUEST['type'] : '0';
	$userid=!empty($userinfo['userid']) ? $userinfo['userid'] : "";
	if(empty($cid) || empty($userid)){
		$rs=array('result'=>'0','info'=>'缺少必要参数');
		exit($json->json_encode_ex($rs));
	}
	$sql="select count(*) from ".$GLOBALS['ecs']->table('cart')." where id in({$cid}) AND user_id={$userid}";
	$sel=$GLOBALS['db']->getOne($sql);
	$flag=false;
	if(!empty($sel) && $type=='1'){
		$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " set selected = 1 WHERE id in ({$cid}) AND user_id={$userid}";
		$flag=$GLOBALS['db']->query($sql);
	}else{
		$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " set selected = 0 WHERE id in ({$cid}) AND user_id={$userid}";
		$flag=$GLOBALS['db']->query($sql);
	}
	$total=get_cart_goods($userid,'');
	if($flag){
		$rs=array('result'=>'1','info'=>'请求成功','all_price'=>$total['total']['goods_price']);
		exit($json->json_encode_ex($rs));
	}else{
		$rs=array('result'=>'0','info'=>'请求失败','all_price'=>"");
		exit($json->json_encode_ex($rs));
	} 
	
}

function get_regin_name($id){
	$sql="select name from ".$GLOBALS['ecs']->table('region')." where id={$id}";
	return $GLOBALS['db']->getOne($sql);
}

//根据条件获取购物车信息
function get_condition_cartlist($userid,$ids=''){
	
	//商品变价更改，购物车价格
	update_cart_price($userid);
	$file="C.id,C.goods_id,C.market_price,C.goods_price,C.spec_key,C.goods_num,C.goods_sn,C.goods_name,C.add_time,C.spec_key_name,";
	$file.="G.store_count,G.original_img,G.supplier_id,G.shipping_price,G.is_free_shipping,G.examine";
	$sql="select {$file} from ".$GLOBALS['ecs']->table('cart')." as C left join ".$GLOBALS['ecs']->table('goods')." as G ON C.goods_id=G.goods_id where id in ($ids) AND C.user_id={$userid} GROUP BY G.goods_id,G.supplier_id,C.spec_key ";
	$list=$GLOBALS['db']->getAll($sql);
	if(empty($list)){
		return false;
	}
	$rs=array();
	$arr=array();
	$newarr=array();
	$goods_num=array();
	if(!empty($list)){
		$max=array();
		$price1=0;
		$price2=0;
		foreach ($list as $key=> $val){
			if(!empty($val['supplier_id'])){
				if(!empty($val['supplier_id'])){
					$row=get_supplier($val['supplier_id']);
					$arr[$val['supplier_id']]['supplier_id']=$val['supplier_id'];
					$arr[$val['supplier_id']]['supplier_name']=$row['supplier_name'];
					$arr[$val['supplier_id']]['company_name']=$row['company_name'];
					$arr[$val['supplier_id']]['business_sphere']=$row['business_sphere'];
				}else{
					$arr[$val['supplier_id']]['supplier_id']="";
					$arr[$val['supplier_id']]['supplier_name']="";
					$arr[$val['supplier_id']]['company_name']="";
					$arr[$val['supplier_id']]['business_sphere']="";
				}
				
				//是否包邮  1包邮0不包邮 
				if($val['is_free_shipping']!=1){
					if($max[$val['supplier_id']]<$val['shipping_price']){
						$max[$val['supplier_id']]=$val['shipping_price'];
					}
					$arr[$val['supplier_id']]['carriage']=!empty($max[$val['supplier_id']]) ? $max[$val['supplier_id']] : '0';
				}else{
					$arr[$val['supplier_id']]['carriage']='0';
				}
				
				$arr[$val['supplier_id']]['list'][$key]=$val;
				$arr[$val['supplier_id']]['list'][$key]['original_img']=!empty($val['original_img']) ? IMG_HOST.$val['original_img'] : '';
			}else{
				if($val['is_free_shipping']!=1){
					$rs[$key]['carriage']=!empty($val['shipping_price']) ? $val['shipping_price'] : '0';
					$price2+=$val['shipping_price'];
				}
				//$all_carriage+=$val['shipping_price'];
				$rs[$key]['supplier_id']="";
				$rs[$key]['supplier_name']="";
				$rs[$key]['company_name']="";
				$rs[$key]['business_sphere']="";
				if(empty($val['supplier_id'])){
					$val['supplier_id']="";
				}
				if(!empty($val['original_img'])){
					$val['original_img']=IMG_HOST.$val['original_img'];
				}else{
					$val['original_img']="";
				}
				$rs[$key]['list'][]=$val;
			}
			
			$key_value=$val['spec_key'];
			$goods_id=$val['goods_id'];
			//检测库存是否够
			if(!empty($key_value) && !empty($goods_id)){
				$sql="select G.goods_id,G.key,G.key_name,G.price,G.store_count from ".$GLOBALS['ecs']->table('goods_price')." as G where G.key='{$key_value}' AND G.goods_id={$goods_id}";
				$row=$GLOBALS['db']->getRow($sql);
				if(!empty($row)){
					if($val['goods_num']>$row['store_count'] || $val['goods_num']>$val['store_count']){
						$rs['goods_id'][]=$goods_id;
					}
				}
				
			}
		}
		
		$rs2=array();
		if(!empty($arr)){
			foreach ($arr as $key=>$val){
				$temp['supplier_id']=$val['supplier_id'];
				$temp['supplier_name']=$val['supplier_name'];
				$temp['company_name']=$val['company_name'];
				$temp['business_sphere']=$val['business_sphere'];
				//$temp['carriage']=$val['carriage'];
				$temp['carriage']=$max[$val['supplier_id']];
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
	
	//获取分类的价格
	if(!empty($max)){
		foreach ($max as $val){
			$price1+=$val;
		}
		
	}
	
	$rs['all_carriage']=$price1+$price2;
	return $rs;
}


//购物车列表
function get_cart_list($userid){
	update_cart_price($userid);
	$file="C.id as cart_id,C.goods_id,C.market_price,C.goods_price,C.spec_key_name as spec_key,C.goods_num,C.goods_sn,C.goods_name,C.add_time,C.selected,";
	$file.="G.store_count,G.original_img,G.supplier_id,G.is_designer,G.shipping_price,G.is_free_shipping";
	$sql="select {$file} from ".$GLOBALS['ecs']->table('cart')." as C left join ".$GLOBALS['ecs']->table('goods')." as G ON C.goods_id=G.goods_id where  C.user_id={$userid} GROUP BY G.goods_id,G.supplier_id,C.spec_key order by C.id DESC";
	$list=$GLOBALS['db']->getAll($sql);
	if(empty($list)){
		return false;
	}
	$rs=array();
	$arr=array();
	$newarr=array();
	$goods_num=array();
	if(!empty($list)){
		$max=array();
		$price1=0;
		$price2=0;
		$price3=0;
		foreach ($list as $key=> $val){
			if($val['selected']==1){
				$price3+=$val['goods_price']*$val['goods_num'];
			}
			if(!empty($val['supplier_id'])){
				if(!empty($val['supplier_id'])){
					$row=get_supplier($val['supplier_id']);
					$arr[$val['supplier_id']]['supplier_name']=$row['supplier_name'];
					$arr[$val['supplier_id']]['company_name']=$row['company_name'];
					$arr[$val['supplier_id']]['business_sphere']=$row['business_sphere'];
				}else{
					$arr[$val['supplier_id']]['supplier_name']='';
					$arr[$val['supplier_id']]['company_name']='';
					$arr[$val['supplier_id']]['business_sphere']='';
				}
	
				$max[$val['supplier_id']]=0;
				//是否包邮
				if($val['is_free_shipping']!=1){
					if($max[$val['supplier_id']]<$val['shipping_price']){
						$max[$val['supplier_id']]=$val['shipping_price'];
					}
					$arr[$val['supplier_id']]['carriage']=!empty($max[$val['supplier_id']]) ? $max[$val['supplier_id']] : '0';
				}
	
				$arr[$val['supplier_id']]['list'][$key]=$val;
				$arr[$val['supplier_id']]['list'][$key]['original_img']=!empty($val['original_img']) ? IMG_HOST.$val['original_img'] : '';
			}else{
				if($val['is_free_shipping']!=1){
					$rs[$key]['carriage']=!empty($val['shipping_price']) ? $val['shipping_price'] : '0';
					$price2+=$val['shipping_price'];
				}
				//$all_carriage+=$val['shipping_price'];
				$rs[$key]['supplier_name']="";
				$rs[$key]['company_name']="";
				$rs[$key]['business_sphere']="";
				if(empty($val['supplier_id'])){
					$val['supplier_id']="";
				}
				if(!empty($val['original_img'])){
					$val['original_img']=IMG_HOST.$val['original_img'];
				}else{
					$val['original_img']="";
				}
				$rs[$key]['list'][]=$val;
			}
				
			$key_value=$val['spec_key'];
			$goods_id=$val['goods_id'];
			//检测库存是否够
			if(!empty($key_value) && !empty($goods_id)){
				$sql="select G.goods_id,G.key,G.key_name,G.price,G.store_count from ".$GLOBALS['ecs']->table('goods_price')." as G where G.key='{$key_value}' AND G.goods_id={$goods_id}";
				$row=$GLOBALS['db']->getRow($sql);
				if(!empty($row)){
					if($val['goods_num']>$row['store_count'] || $val['goods_num']>$val['store_count']){
						$rs['goods_id'][]=$goods_id;
					}
				}
	
			}
		}
		 
		$rs2=array();
		if(!empty($arr)){
			foreach ($arr as $key=>$val){
				$temp['supplier_name']=$val['supplier_name'];
				$temp['company_name']=$val['company_name'];
				$temp['business_sphere']=$val['business_sphere'];
				$temp['carriage']=$val['carriage'];
				if(!empty($val['list'])){
					rsort($val['list']);
					$temp['list']=$val['list'];
				}else{
					$temp['list']=array();
				}
				$rs2[]=$temp;
			}
			
		} 
		
		$rs=array_merge($rs,$rs2);
	}
	if(!empty($max)){
		foreach ($max as $val){
			$price1+=$val;
		}
	
	}
	$rs['all_carriage']=$price1+$price2;
	$rs['all_price']=$price3;
	return $rs;
	
	//商品变价更改，购物车价格
	/* update_cart_price($userid);
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
			$i=0;
			if(!empty($val['supplier_id'])){
				if(!empty($val['supplier_id'])){
					$row=get_supplier($val['supplier_id']);
					$rs[$val['supplier_id']]['supplier_name']=$row['supplier_name'];
					$rs[$val['supplier_id']]['company_name']=$row['company_name'];
					$rs[$val['supplier_id']]['business_sphere']=$row['business_sphere'];
				}else{
					$rs[$val['supplier_id']]['supplier_name']='';
					$rs[$val['supplier_id']]['company_name']='';
					$rs[$val['supplier_id']]['business_sphere']='';
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
			$i++;
		}
	}
	
	return $rs; */
}


function update_cart_price($userid){
	//商品价格变动，更改商品价格
	$sql="SELECT id, goods_price,goods_id,spec_key from ".$GLOBALS['ecs']->table('cart')." where user_id={$userid}";
	$list=$GLOBALS['db']->getAll($sql);
	if(!empty($list)){
		foreach ($list as $val){
			//活动改价格
			$act_price=get_act_price($val['goods_id']);
			if($act_price != false){
				if($val['goods_price']==$act_price){
					continue;
				}
				$price=$act_price;
				$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " set goods_price = {$price} WHERE user_id={$userid} AND id='{$val[id]}'";
				$GLOBALS['db']->query($sql);
			}else{
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
}
//检测活动
function check_activity($userid,$ids){
	//增加活动判断
	$sql="select GROUP_CONCAT(A.goods_id) from ".$GLOBALS['ecs']->table('cart')." as A left join ".$GLOBALS['ecs']->table('goods')." as B ON A.goods_id=B.goods_id where B.examine=1 AND A.user_id={$userid} AND A.id in ({$ids})";
	$all_goods_id=$GLOBALS['db']->getOne($sql);
	
	//取出活动商品id
	$sql="select count(*) from ".$GLOBALS['ecs']->table('panic_buying')." where goods_id in ({$all_goods_id}) AND is_end=0";
	$act_count=$GLOBALS['db']->getOne($sql);
	if($act_count>0){
		$sql="select * from ".$GLOBALS['ecs']->table('panic_buying')." where goods_id in ({$all_goods_id}) AND is_end=0";
		$act_list=$GLOBALS['db']->getAll($sql);
		$strtime=time();
		if(!empty($act_list)){
			foreach ($act_list as $key =>$val){
				//活动商品库存不足
				if($val['goods_num']<=$val['buy_num']){
					return array('result'=>'1','info'=>"{$val[goods_name]},已经抢购完了");
				}
	
				$sql="select goods_num from ".$GLOBALS['ecs']->table('cart')." where goods_id ={$val['goods_id']} AND user_id={$userid}";
				$cart_goods_num=$GLOBALS['db']->getOne($sql);
	
				//活动是否过期
				if($val['buy_type']==5 && !($strtime>=$val['start_time'] && $strtime<=$val['end_time'])){
					return array('result'=>'3','info'=>"{$val[goods_name]},活动已过期！");
				}
				
				$sql="select SUM(B.goods_num) from ".$GLOBALS['ecs']->table('order')." as A LEFT JOIN ".$GLOBALS['ecs']->table('order_goods')." as B ON A.order_id=B.order_id where A.user_id={$userid} AND goods_id={$val['goods_id']} AND B.prom_id={$val[id]}";
				$order_count=$GLOBALS['db']->getOne($sql);
				//订单有存在 是否活动时间内
				if(($order_count>=$val['buy_limit'] && $order_count>0) || ($cart_goods_num+$order_count>$val['buy_limit'])){
					return array('result'=>'2','info'=>"{$val[goods_name]},不能超过限制数量");
				}
				/* if($val['buy_type']==5){
					//查看是否做过任务,特价活动，只能购买一次
					$sql="select A.order_id,A.add_time from ".$GLOBALS['ecs']->table('order')." as A LEFT JOIN ".$GLOBALS['ecs']->table('order_goods')." as B ON A.order_id=B.order_id  where A.user_id={$userid} AND goods_id={$val['goods_id']} AND B.prom_id={$val[id]}";
					$order_order=$GLOBALS['db']->getRow($sql);
				    //订单有存在 是否活动时间内
					if(!empty($order_order)){
						return array('result'=>'2','info'=>"{$val[goods_name]},您已购买过了");
					}
				}else{
					$sql="select A.order_id,A.add_time from ".$GLOBALS['ecs']->table('order')." as A LEFT JOIN ".$GLOBALS['ecs']->table('order_goods')." as B ON A.order_id=B.order_id  where A.user_id={$userid} AND goods_id={$val['goods_id']} AND B.prom_id={$val[id]}";
					$order_order=$GLOBALS['db']->getRow($sql);
					//订单有存在 是否活动时间内
					if(!empty($order_order) && ($order_order['add_time']>=$val['start_time'] && $order_order['add_time']<=$val['end_time'])){
						return array('result'=>'2','info'=>"{$val[goods_name]},您已购买过了");
					}
				} */
				
				//购物车超过数量
				if($cart_goods_num>$val['buy_limit'] || ($cart_goods_num+$val['buy_num'])>$val['goods_num']){
					return array('result'=>'4','info'=>"{$val[goods_name]},超过购买数量！");
				}
				//修改活动商品信息
				$sql = "UPDATE ".$GLOBALS['ecs']->table('panic_buying')."  SET buy_num= buy_num+'{$cart_goods_num}',order_num=order_num+1 where id={$val[id]}";
				$is_act=$GLOBALS['db']->query($sql);
				if(empty($is_act)){
					return array('result'=>'5','info'=>"修改失败");
				}
			}
		}
	}
}

//检测活动
function check_goods_activity($goods_id,$userid,$number){
	//取出活动商品id
	$sql="select count(*) from ".$GLOBALS['ecs']->table('panic_buying')." where goods_id = {$goods_id} AND is_end=0";
	$act_count=$GLOBALS['db']->getOne($sql);
	if($act_count>0){
		$sql="select * from ".$GLOBALS['ecs']->table('panic_buying')." where goods_id = {$goods_id} AND is_end=0";
		$val=$GLOBALS['db']->getRow($sql);
		$strtime=time();
		//活动商品库存不足
		if($val['goods_num']<=$val['buy_num']){
			return array('result'=>'1','info'=>"{$val[goods_name]},已经抢购完了");
		}
		//活动是否过期
		if($val['buy_type']==5 && !($strtime>=$val['start_time'] && $strtime<=$val['end_time'])){
			return array('result'=>'3','info'=>"{$val[goods_name]},活动已过期！");
		}
		
		$sql="select SUM(B.goods_num) from ".$GLOBALS['ecs']->table('order')." as A LEFT JOIN ".$GLOBALS['ecs']->table('order_goods')." as B ON A.order_id=B.order_id where A.user_id={$userid} AND goods_id={$goods_id} AND B.prom_id={$val[id]}";
		$order_count=$GLOBALS['db']->getOne($sql);
		//订单有存在 是否活动时间内
		if(($order_count>=$val['buy_limit'] && $order_count>0) || ($order_count+$number>$val['buy_limit'])){
			return array('result'=>'2','info'=>"{$val[goods_name]},已超过限购数量");
		}
		/* if($val['buy_type']==5){
			//查看是否做过任务
			$sql="select count(*) from ".$GLOBALS['ecs']->table('order')." as A LEFT JOIN ".$GLOBALS['ecs']->table('order_goods')." as B ON A.order_id=B.order_id where A.user_id={$userid} AND goods_id={$goods_id} AND B.prom_id={$val[id]}"; 
			$order_count=$GLOBALS['db']->getRow($sql);
			//订单有存在 是否活动时间内
			if(!empty($order_order)){
				return array('result'=>'2','info'=>"{$val[goods_name]},已购买过了");
			}
		}else{
			$sql="select A.order_id,A.add_time from ".$GLOBALS['ecs']->table('order')." as A LEFT JOIN ".$GLOBALS['ecs']->table('order_goods')." as B ON A.order_id=B.order_id where A.user_id={$userid} AND goods_id={$goods_id} AND B.prom_id={$val[id]}";
			$order_order=$GLOBALS['db']->getRow($sql);
			//订单有存在 是否活动时间内
			if(!empty($order_order) && ($order_order['add_time']>=$val['start_time'] && $order_order['add_time']<=$val['end_time'])){
				return array('result'=>'2','info'=>"{$val[goods_name]},已购买过了");
			}
			
		} */
		//活动是否过期
		if($val['buy_type']==1 && !($strtime>=$val['start_time'] && $strtime<=$val['end_time'])){
			$val['price']='';
		}
		
		//购物车超过数量
		if($number>$val['buy_limit'] || ($number+$val['buy_num'])>$val['goods_num']){
			return array('result'=>'4','info'=>"{$val[goods_name]},超过购买数量！");
		}
		//修改活动商品信息
		$sql = "UPDATE ".$GLOBALS['ecs']->table('panic_buying')."  SET order_num= order_num+1,buy_num=buy_num+{$number} where id={$val[id]}";
		$is_act=$GLOBALS['db']->query($sql);
		if(empty($is_act)){
			return array('result'=>'5','info'=>"修改失败");
		}
		return array('result'=>"0",'price'=>$val['price'],'prom_type'=>$val['buy_type'],'prom_id'=>$val['id']);
		
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
 	$sql="select is_designer from ".$GLOBALS['ecs']->table('goods')." where goods_id={$goods_id} ";
	$is_disgner=$GLOBALS['db']->getOne($sql);
    $parent = array(
        'user_id'       => $data['user_id'],
        'session_id'    => $_REQUEST['ticket'],
        'goods_id'      => $goods_id,
        'goods_sn'      => addslashes($data['goods']['goods_sn']),
        'goods_name'    => addslashes($data['goods']['goods_name']),
        'market_price'  => $data['goods']['market_price'],
    	'supplier_id'	=> $data['goods']['supplier_id'],
    	'supplier_name' => $data['goods']['supplier_name'],
    	'goods_price'   =>$data['price'],
    	'goods_num'	    =>$number,
    	'is_designer'   =>$is_disgner,
    	'spec_key'      =>$data['spec_key'],
    	'spec_key_name'	=>$data['spec_key_name'],
        'add_time' =>time(),
    	'prom_id'=>$data['prom_id'],
    	'prom_type'=>$data['prom_type'],
    );
    $spec_key=$data['spec_key'];
    $user_id=$data['user_id'];
    $goods_name=addslashes($data['goods']['goods_name']);
    //查询购物车，是否存在该规格商品，如果存在，增加数量
    $sql="select count(*) from ".$GLOBALS['ecs']->table('cart')." where user_id={$user_id} AND spec_key='{$spec_key}' AND goods_id={$goods_id} ";
    $rs=$GLOBALS['db']->getOne($sql);
    
    if($rs){
    	$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " set goods_num = goods_num+{$number} WHERE user_id={$user_id} AND spec_key='{$spec_key}' AND goods_id={$goods_id} ";
        return $GLOBALS['db']->query($sql);
    }
	return $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $parent, 'INSERT');
}

//修改商品库存数量
function update_shop_num($userid,$where=''){
	if(!empty($where)){
		$where=" AND {$where}";
	}
	/* 循环、统计 */
	$sql = "SELECT * " .
			" FROM " . $GLOBALS['ecs']->table('cart') . " " .
			" WHERE user_id ={$userid} {$where}";
	$res = $GLOBALS['db']->query($sql);
	//修改库存
	if($res){
		while ($row = $GLOBALS['db']->fetchRow($res))
		{
			$flag='';
			if(!empty($row['spec_key'])){
				$sql="select count(*) from ".$GLOBALS['ecs']->table('goods_price')." as P where P.goods_id={$row[goods_id]} AND P.key='".$row['spec_key']."'";
				$flag=$GLOBALS['db']->getOne($sql);
			}
			
			if($flag){
				$sql="select P.store_count from ".$GLOBALS['ecs']->table('goods_price')." as P where P.goods_id={$row[goods_id]} AND P.key='".$row['spec_key']."'";
				$store_count=$GLOBALS['db']->getOne($sql);
				if($row['goods_num']>$store_count){
					return false;
				}
				$number=$store_count-$row['goods_num'];
				$sql = "UPDATE ".$GLOBALS['ecs']->table('goods_price')." as P SET P.store_count= '{$number}' where P.goods_id={$row[goods_id]} AND P.key='".$row['spec_key']."'";
				$update=$GLOBALS['db']->query($sql);
				if(!$update){
					return false;
				}
			}else{
				$sql="select store_count from ".$GLOBALS['ecs']->table('goods')." where goods_id={$row[goods_id]} ";
				$store_count=$GLOBALS['db']->getOne($sql);
				if($row['goods_num']>$store_count){
					return false;
				}
				$number=$store_count-$row['goods_num'];
				$sql = "UPDATE ".$GLOBALS['ecs']->table('goods')." SET store_count= '{$number}' where goods_id={$row[goods_id]} ";
				$update=$GLOBALS['db']->query($sql);
				if(!$update){
					return false;
				}
			} 
		}
		return true;
	}else{
		return false;
	}	
	
}


function update_storenum_BygoodsId($goodid,$number,$spec_key=''){
	$row['goods_id']=$goodid;
	$row['goods_num']=$number;
	$row['spec_key']=$spec_key;
	if(!empty($row['spec_key'])){
		$sql="select count(*) from ".$GLOBALS['ecs']->table('goods_price')." as P where P.goods_id={$row[goods_id]} AND P.key='".$row['spec_key']."'";
		$flag=$GLOBALS['db']->getOne($sql);
	}
	
	if($flag){
		$sql="select P.store_count from ".$GLOBALS['ecs']->table('goods_price')." as P where P.goods_id={$row[goods_id]} AND P.key='".$row['spec_key']."'";
		$store_count=$GLOBALS['db']->getOne($sql);
		if($row['goods_num']>$store_count){
			return false;
		}
		$number=$store_count-$row['goods_num'];
		$sql = "UPDATE ".$GLOBALS['ecs']->table('goods_price')." as P SET P.store_count= '{$number}' where P.goods_id={$row[goods_id]} AND P.key='".$row['spec_key']."'";
		return $GLOBALS['db']->query($sql);
	}else{
		$sql="select store_count from ".$GLOBALS['ecs']->table('goods')." where goods_id={$row[goods_id]} ";
		$store_count=$GLOBALS['db']->getOne($sql);
		if($row['goods_num']>$store_count){
			return false;
		}
		$number=$store_count-$row['goods_num'];
		$sql = "UPDATE ".$GLOBALS['ecs']->table('goods')." SET store_count= '{$number}' where goods_id={$row[goods_id]} ";
		return $GLOBALS['db']->query($sql);
	}
	return true;
}

//计算购物车统计价，单价
function get_cart_goods($userid,$where=''){
	if(!empty($where)){
		$where=" AND {$where}";
	}
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
			" WHERE user_id ={$userid} {$where}";
	$res = $GLOBALS['db']->query($sql);
	
	while ($row = $GLOBALS['db']->fetchRow($res))
	{
		//选中计算价格
		//if($row['selected']=='1'){
			//满足活动修改价格
			$price=check_act_pirce($row['goods_id']);
			if($price!=false){
				$total['goods_price']  += $price * $row['goods_num'];
			}else{
				$total['goods_price']  += $row['goods_price'] * $row['goods_num'];
			}
			
			$total['market_price'] += $row['market_price'] * $row['goods_num'];
			$total['goods_count']  += $row['goods_num'];
		//}
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
//检测活动，是否可以正常购买
function check_act($userid,$number,$goods_id){
	//判断是否为活动商品
	$sql="select * from ".$GLOBALS['ecs']->table('panic_buying')." where goods_id={$goods_id} AND is_end=0";
	$act=$GLOBALS['db']->getRow($sql);
	if(!empty($act)){
		//是否存在购物车
		//查询购物车，是否存在该规格商品，如果存在，增加数量
		$sql="select prom_id,prom_type,goods_num from ".$GLOBALS['ecs']->table('cart')." where user_id={$userid} AND  goods_id={$goods_id} ";
		$cart_row=$GLOBALS['db']->getRow($sql);
		if($cart_row['prom_type']=='5' && $act['goods_num']==$act['buy_num']){
			$rs=array('result'=>'0','info'=>'商品已抢购完了');
			return $rs;
		}
		if($cart_row['prom_type']=='5' && ($cart_row['goods_num']+$number+$act['buy_num'])>$act['goods_num']){
			$rs=array('result'=>'0','info'=>'超过活动，限购数量');
			return $rs;
		}
		$strtime=time();
		//修改价格
		if($act['buy_type']=='5'){
			if(!($strtime>=$act['start_time'] && $strtime<=$act['end_time'])){
				$rs=array('result'=>'0','info'=>'活动还未开始');
				return $rs;
			}
			$act_price=$act['price'];
		}
		//限时可以购买，看是否
		if($act['buy_type']=='1'){
			if(($strtime>=$act['start_time'] && $strtime<=$act['end_time'])){
				$act_price=$act['price'];
			}
		}
		if($strtime>$act['end_time']){
			$rs=array('result'=>'0','info'=>'活动已结束');
			return $rs;
		}
	
		if($number>$act['buy_limit']){
			$rs=array('result'=>'0','info'=>'活动商品，只能购买'.$act['buy_limit'].'个');
			return $rs;
		}
		return array('result'=>'1','prom_id'=>$act['id'],'prom_type'=>$act['buy_type'],'price'=>$act_price);
	}
	
}


//检测活动，是否可以正常购买
function get_act_price($goods_id){
	$strtime=time();
	//判断是否为活动商品
	$sql="select * from ".$GLOBALS['ecs']->table('panic_buying')." where goods_id={$goods_id} AND is_end=0";
	$act=$GLOBALS['db']->getRow($sql);
	if(!empty($act)){
		//修改价格
			if($strtime>=$act['start_time'] && $strtime<=$act['end_time']){
				return $act['price'];
			}
			return false;
		}
	return false;
}

function check_act_pirce($goods_id){
	$strtime=time();
	$sql="select * from ".$GLOBALS['ecs']->table('panic_buying')." where goods_id = {$goods_id} AND is_end=0";
	$result=$GLOBALS['db']->getRow($sql);
	if(!empty($result)){
	    //活动是否过期
		if($strtime>=$result['start_time'] && $strtime<=$result['end_time']){
			return $result['price'];
		}
	  return false;
	}
	return false;
}

function get_goods_id($good_id){
	$file="goods_id,is_designer,cat_id,goods_sn,goods_name,brand_id,store_count,market_price,shop_price,supplier_id,add_time,goods_type,spec_type,goods_content,shipping_price,is_free_shipping,original_img,supplier_id,supplier_name ";
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
function get_supplier($id){
	$sql="select supplier_id,user_id,supplier_name,company_name,business_sphere from ".$GLOBALS['ecs']->table('supplier')." where supplier_id={$id}";
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
function get_order_sn(){
	mt_srand((double) microtime() * 1000000);
	return date('YmdHis') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
}
//数组排序
function array_orderby()
{
	$args = func_get_args();
	$data = array_shift($args);
	foreach ($args as $n => $field) {
		if (is_string($field)) {
			$tmp = array();
			foreach ($data as $key => $row)
				$tmp[$key] = $row[$field];
			$args[$n] = $tmp;
		}
	}
	$args[] = &$data;
	call_user_func_array('array_multisort', $args);
	return array_pop($args);
}


?>