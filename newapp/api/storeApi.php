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
//店铺首页
if($action=='index'){
	$supplier_id=trim($_REQUEST['supplier_id']);
	$user_id=!empty($userinfo['userid']) ? $userinfo['userid'] : "";
	if(empty($supplier_id)){
		$results=array(
				'result' => '0',
				'info' => '缺少必填参数'
		);
		exit($json->json_encode_ex($results));
	}
	$sql="SELECT value FROM ".$GLOBALS['ecs']->table('supplier_config')." where supplier_id ='".$supplier_id."' and name='mobile' ";
	$mobile=$db->getOne($sql);
	$sql="SELECT value FROM ".$GLOBALS['ecs']->table('supplier_config')." where supplier_id ='".$supplier_id."' and name='phone' ";
	$phone=$db->getOne($sql);
	$sql="SELECT value FROM ".$GLOBALS['ecs']->table('supplier_config')." where supplier_id ='".$supplier_id."' and name='contact' ";
	$contact=$db->getOne($sql);

	$file="supplier_id,supplier_name,address,phone_number,logo,introduction,add_time,supplier_money,business_sphere";
	$sql="SELECT {$file} FROM ".$GLOBALS['ecs']->table('supplier')." where supplier_id ='".$supplier_id."' ";
	$store=$db->getRow($sql);
	
	$sql="SELECT count(*) FROM ".$GLOBALS['ecs']->table('supplier_collect')." where supplier_id ='".$supplier_id."' ";
	$collect_num=$db->getOne($sql);
	$store['collect_num']=!empty($collect_num) ? $collect_num : $collect_num;
	$store['logo']=!empty($store['logo']) ? IMG_HOST.$store['logo'] : '';
	$store['ad_img']=$store['logo'];
	$store['is_collect']="0";
		
	$store['supplier_id']=!empty($store['supplier_id']) ? $store['supplier_id'] :'';
	$store['supplier_name']=!empty($store['supplier_name']) ? $store['supplier_name'] :'';
	$store['address']=!empty($store['address']) ? $store['address'] :'';
	$store['phone_number']=!empty($phone) ? $phone : $store['phone_number'];
	$store['mobile']=!empty($mobile) ? $mobile : '';
	$store['contact']=!empty($contact) ? $contact : '';
	$store['logo']=!empty($store['logo']) ? $store['logo'] :'';
	$store['introduction']=!empty($store['introduction']) ? $store['introduction'] :'';
	$store['add_time']=!empty($store['add_time']) ? $store['add_time'] :'';
	$store['supplier_money']=!empty($store['supplier_money']) ? $store['supplier_money'] :'';
	$store['business_sphere']=!empty($store['business_sphere']) ? $store['business_sphere'] :'';
	
	if(empty($store['introduction'])){
		$store['introduction']=$store['business_sphere'];
	}
	$sql="SELECT count(*) FROM ".$GLOBALS['ecs']->table('supplier_collect')." where supplier_id ='".$supplier_id."' AND user_id={$user_id} ";
	$store['supplier_bond']=$store['supplier_money'] > 0  ? "1" : "0";
	if(!empty($user_id)){
		$sql="SELECT count(*) FROM ".$GLOBALS['ecs']->table('supplier_collect')." where supplier_id ='".$supplier_id."' AND user_id={$user_id} ";
		if($db->getOne($sql)>0){
			$store['is_collect']=1;
		}
	}
	$results=array(
			'result' => '1',
			'info' => '成功',
			'store'=>$store
	);
	exit($json->json_encode_ex($results));
}
//获取店铺分类
elseif($action=='cate'){
	$supplier_id=trim($_REQUEST['supplier_id']);
	if(empty($supplier_id)){
		$results=array(
				'result' => '0',
				'info' => '缺少必填参数'
		);
		exit($json->json_encode_ex($results));
	}
	$file="id as cate_id,name,mobile_name,image";
	$sql="SELECT {$file} FROM ".$GLOBALS['ecs']->table('supplier_goods_category')." where supplier_id ='".$supplier_id."' AND is_show=1 AND level=1 order by sort_order DESC ";
	$list=$db->getAll($sql);
	
	$arr=array();
	if(!empty($list)){
		foreach ($list as $key =>$val){
			$arr[$key]=$val;
			$arr[$key]['mobile_name']=!empty($val['mobile_name']) ? $val['mobile_name'] : $val['name'];
			if(!empty($val['image'])){
				$arr[$key]['image']=!empty($val['image']) ? IMG_HOST.$val['image'] : "";
			}
		}
	}
	
	$results=array(
				'result' => '1',
				'info' => '成功',
				'list'=>$arr
	);
	exit($json->json_encode_ex($results));	
}
//获取精选店铺列表
elseif($action=="supplier_list"){
	$page=!empty($_REQUEST['page']) ? $_REQUEST['page'] : 0 ;
	/* 查询商品 */
	$page=!empty($page) ? $page : 0;
	$size =10;
	$sql = "select COUNT(supplier_id) from " .$GLOBALS['ecs']->table('supplier_recommend'). " where is_show = 1";
	$supplier_count = $GLOBALS['db']->getOne($sql);
	$allpage=ceil($supplier_count/$size);
	$begin = $page*$size;
	$limit = " LIMIT $begin,$size";
	$sql = "select logo_img,number,supplier_name,supplier_id,introduction from " .$GLOBALS['ecs']->table('supplier_recommend'). " where is_show = 1 order by number desc,sort desc {$limit}";
	$list = $GLOBALS['db']->getAll($sql);
	
	if ($list) {
		foreach ($list as $k =>$v){
			$list[$k]['logo_img']=IMG_HOST.$v['logo_img'];
		}
		$rs=array('result'=>'1','info'=>'请求成功','supplier_list'=>$list,'page' => $page,	'count' => $allpage,'size' => $size	);
	}else{
		$list=array();
		$rs=array('result'=>'1','info'=>'暂无数据','supplier_list'=>$list,'page' => $page,	'count' => $allpage,'size' => $size	);
	}
	exit($json->json_encode_ex($rs));
}
//获取店铺分类列表
elseif($action=="serach_goods"){
	$supplier_id=trim($_REQUEST['supplier_id']);
	$cate_id=trim($_REQUEST['cate_id']);
	
	if(empty($supplier_id)){
		$results=array(
				'result' => '0',
				'info' => '缺少必填参数'
		);
		exit($json->json_encode_ex($results));
	}
	
	$page=!empty($_REQUEST['page']) ? $_REQUEST['page'] : 0 ;
	/* 查询商品 */
	$page=!empty($page) ? $page : 0;
	$size =50;
	$begin = $page*$size;
	$limit = " LIMIT $begin,$size";
	
	$where=" AND g.examine = 1 AND g.supplier_id={$supplier_id}";
	if(!empty($cate_id)){
		$ids=get_store_cate($cate_id,$supplier_id);
		$where.=" AND g.extend_cat_id in ({$ids})";
	}
	$sql = "SELECT g.goods_id, g.goods_name, g.market_price, g.shop_price,g.original_img".
			" FROM " .$GLOBALS['ecs']->table('goods'). " AS g ".
			" WHERE g.is_on_sale = 1 {$where} order by g.sort asc  {$limit}";

	$res = $GLOBALS['db']->getAll($sql);
	$rs=array();
	if(!empty($res)){
		foreach ($res as $key =>$val){
			if(!empty($val['original_img'])){
				$temp['image']=IMG_HOST.$val['original_img'];
			}else{
				$temp['image']='';
			}
			$temp['goods_id']=$val['goods_id'];
			$temp['goods_name']=$val['goods_name'];
			$temp['market_price']=$val['market_price'];
			$temp['shop_price']=$val['shop_price'];
			$rs[]=$temp;
		}
	}
	
	//搜下下的总商品数量
	$sql = "SELECT COUNT(g.goods_id) FROM " .$GLOBALS['ecs']->table('goods').
	" as g  WHERE g.is_on_sale = 1 {$where}";
	$goods_count = $GLOBALS['db']->getOne($sql);
	$allpage=ceil($goods_count/$size);
	$results = array(
			'result' => 1,
			'goods_list' =>$rs,	//	商品数据
			'page' => $page,	//	当前页码
			'count' => $allpage,	//	总页数
			'size' => $size	//	每页取得商品数据条数
	);
	exit($json->json_encode_ex($results));	
}

//店铺收藏，取消店铺收藏
elseif($action=="collect_store"){
	$supplier_id=$_REQUEST['supplier_id'];
	if(!empty($userinfo['userid'])){
		$userid=$userinfo['userid'];
	}
	if(empty($supplier_id) || empty($userid)){
		$rs=array('result'=>'0','info'=>'缺少必要参数');
		exit($json->json_encode_ex($rs));
	}
	$temtime=time();
	$sql="select count(*) from ".$GLOBALS['ecs']->table('supplier_collect')." where supplier_id={$supplier_id} AND user_id={$userid}";
	$one=$GLOBALS['db']->getOne($sql);
	
	if(!empty($one)){
		$sql="delete from ".$GLOBALS['ecs']->table('supplier_collect')." where supplier_id={$supplier_id} AND user_id={$userid}";
		$del=$GLOBALS['db']->query($sql);
		if($del){
			$rs=array('result'=>'1','info'=>'取消成功');
			exit($json->json_encode_ex($rs));
		}
		$rs=array('result'=>'0','info'=>'取消失败');
		exit($json->json_encode_ex($rs));
	}else{
		$sql="insert INTO ".$GLOBALS['ecs']->table('supplier_collect')."(user_id,supplier_id,add_time) values ({$userid},{$supplier_id},{$temtime})";
		$one=$GLOBALS['db']->query($sql);
		$rs=array('result'=>'1','info'=>'收藏成功');
		exit($json->json_encode_ex($rs));
	}
	
}
//店铺收藏列表
elseif($action=="collect_store_list"){
	$user_id=!empty($userinfo['userid']) ? $userinfo['userid'] : " ";
	if(empty($user_id)){
		$results=array(
				'result' => '0',
				'info' => '缺少必填参数'
		);
		exit($json->json_encode_ex($results));
	}
	$sql="SELECT A.supplier_id,B.logo,B.supplier_name FROM ".$GLOBALS['ecs']->table('supplier_collect')." as A left join " .$GLOBALS['ecs']->table('supplier')." as B on A.supplier_id=B.supplier_id  where A.user_id='".$user_id."' ";
	$list=$db->getAll($sql);

	$arr=array();
	if(!empty($list)){
		foreach ($list as $key =>$val){
			$arr[$key]=$val;
			$arr[$key]['logo']=!empty($val['logo']) ?  IMG_HOST.$val['logo'] : "";
			$arr[$key]['collect_num']=get_collect_num($val['supplier_id']);
		}
	}
	$results=array(
			'result' => '1',
			'info' => '请求成功',
			'list'=>$arr
	);
	exit($json->json_encode_ex($results));
}
//店铺搜索列表
else if($action=="search_store"){
	$keyword=!empty($_REQUEST['keyword']) ? trim($_REQUEST['keyword']) : '';
	$page=!empty($_REQUEST['page']) ? $_REQUEST['page'] : 0 ;
	/* 查询商品 */
	$page=!empty($page) ? $page : 0;
	$size =20;
	$begin = $page*$size;
	$limit = " LIMIT $begin,$size";
	
	$where=" status = 1 ";
	if(!empty($keyword)){
		$arr = explode(' ',$keyword);
		if(!empty($arr)){
			$where.=" AND ( ";
			foreach ($arr as $val){
				$where.=" supplier_name like '%".$val."%' OR";
			}
			$where=substr($where, 1, -2);
			$where.=" ) ";
		}
	}
	
	$file=" supplier_id,supplier_name,supplier_money,logo,company_name";	
	$sql = "SELECT {$file} "." FROM " .$GLOBALS['ecs']->table('supplier'). " WHERE {$where} {$limit}";
	$res = $GLOBALS['db']->getAll($sql);
	//搜下下的总店铺数量
	$sql = "SELECT COUNT(1) FROM " .$GLOBALS['ecs']->table('supplier').
	" WHERE  {$where}";
	$store_count = $GLOBALS['db']->getOne($sql);
	$allpage=ceil($store_count/$size);
	if(!empty($res)){
		foreach ($res as $key =>$val){
			$res[$key]=$val;
			$res[$key]['supplier_name']=!empty($val['supplier_name']) ? $val['supplier_name']."的店铺" : '';
			$res[$key]['collect_num']=get_collect_num($val['supplier_id']);
			if(!empty($val['logo'])){
				$res[$key]['logo']=IMG_HOST.$val['logo'];
			}else{
				$res[$key]['logo']='';
			}
			if($val['supplier_money'] > 0){
				$res[$key]['supplier_bond']=1;
			}else{
				$res[$key]['supplier_bond']=0;
			}
		}
	}
	$results = array(
			'result' => 1,
			'list' =>$res,	//	商品数据
			'page' => $page,	//	当前页码
			'count' => $allpage,	//	总页数
			'size' => $size	//	每页取得商品数据条数
	);
	exit($json->json_encode_ex($results));		
}


//获取店铺三级分类id
function get_store_cate($cateid,$supplier_id){
	$cateids=$cateid;
	$sql="SELECT GROUP_CONCAT(id) as ids from ".$GLOBALS['ecs']->table('supplier_goods_category')." where supplier_id={$supplier_id} AND parent_id={$cateid} group by parent_id ";
	$row=$GLOBALS['db']->getRow($sql);
	if(!empty($row)){
		$cateids.=",".$row['ids'];
		$where=" where supplier_id={$supplier_id} AND parent_id in( ".$row['ids']." )";
		$sql="SELECT GROUP_CONCAT(id) as ids from ".$GLOBALS['ecs']->table('supplier_goods_category')." {$where} group by parent_id ";
		$row2=$GLOBALS['db']->getRow($sql);
		if($row2){
			$cateids.=",".$row2['ids'];
			$where=" where supplier_id={$supplier_id} AND  parent_id in( ".$row2['ids']." )";
			$sql="SELECT GROUP_CONCAT(id) as ids from ".$GLOBALS['ecs']->table('supplier_goods_category')." {$where} group by parent_id ";
			$row3=$GLOBALS['db']->getRow($sql);
			if(!empty($row3)){
				$cateids.=",".$row3['ids'];
			}
		}
	}
	return $cateids;
}

//收藏列表
function get_collect_num($supplier_id){
	if(empty($supplier_id)){
		return 0;
	}
	$sql="SELECT count(*) FROM ".$GLOBALS['ecs']->table('supplier_collect')." where supplier_id ='".$supplier_id."'";
	return $GLOBALS['db']->getOne($sql);
}

