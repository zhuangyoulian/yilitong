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
//获取微店主信息
if($action=="shopkeeper_info"){
	if(!empty($userinfo['userid'])){
		$user_id=$userinfo['userid'];
		$info = get_shopkeeper_info($user_id);
		if ($info) {
			$results=array(
				'result' => '1',
				'info' => '请求成功',
				'shop_info' => $info
			);
		}else{
			$results=array(
				'result' => '0',
				'info' => '微店主不存在'
			);
		}
	}else{
		$results=array(
			'result' => '0',
			'info' => '请登录'
		);
	}
	exit($json->json_encode_ex($results));
	
}
//微店主已添加的商品列表
elseif($action=="goods_list"){
	//登录信息、店铺名称、店铺头像、背景图不能为空
	if(!empty($userinfo['userid'])){
		$user_id=$userinfo['userid'];
		$info = get_shopkeeper_info($user_id);	
		$sort = !empty($_REQUEST['sort']) ? $_REQUEST['sort'] : "";
		$orderby = !empty($_REQUEST['order']) ? $_REQUEST['order'] : "asc";
		$sql="select recommend_code from " .$GLOBALS['ecs']->table('users')." where user_id={$user_id}";
		$recommend_code = $GLOBALS['db']->getOne($sql);
		$goods_list=array();
		if (empty($info)) {
			$results=array(
				'result' => '0',
				'info' => '不存在该微店主'
			);
			exit($json->json_encode_ex($results));
		}else{
		
			if ($sort=="price") {	
				if ($orderby =="asc") {//商品价格由低到高
					$sql="SELECT G.supplier_id,S.sort,G.goods_id,G.goods_name,G.goods_thumb,G.shop_price,G.sales_sum,G.store_count,G.commission_price FROM ".$GLOBALS['ecs']->table('goods')." as G left join "
							.$GLOBALS['ecs']->table('shop_goods').	" as S ON  G.goods_id = S.goods_id where S.user_id ={$user_id} order by G.shop_price asc  ";
			
				}else{//商品价格由高到低
				
					$sql="SELECT G.supplier_id,S.sort,G.goods_id,G.goods_name,G.goods_thumb,G.shop_price,G.sales_sum,G.store_count,G.commission_price FROM ".$GLOBALS['ecs']->table('goods')." as G left join "
							.$GLOBALS['ecs']->table('shop_goods').	" as S ON  G.goods_id = S.goods_id where S.user_id ={$user_id} order by G.shop_price desc  ";
				}
			}elseif ($sort=="sales"){
				if ($orderby =="asc") {//商品销量由低到高
					$sql="SELECT G.supplier_id,S.sort,G.goods_id,G.goods_name,G.goods_thumb,G.shop_price,G.sales_sum,G.store_count,G.commission_price FROM ".$GLOBALS['ecs']->table('goods')." as G left join "
						.$GLOBALS['ecs']->table('shop_goods').	" as S ON  G.goods_id = S.goods_id where S.user_id ={$user_id} order by G.sales_sum asc  ";
				}else{//商品销量由高到低
					$sql="SELECT G.supplier_id,S.sort,G.goods_id,G.goods_name,G.goods_thumb,G.shop_price,G.sales_sum,G.store_count,G.commission_price FROM ".$GLOBALS['ecs']->table('goods')." as G left join "
						.$GLOBALS['ecs']->table('shop_goods').	" as S ON  G.goods_id = S.goods_id where S.user_id ={$user_id} order by G.sales_sum desc  ";
				}
			}else{//综合排序 根据shop_goods表的排序sort规则
				$sql="SELECT G.supplier_id,S.sort,G.goods_id,G.goods_name,G.goods_thumb,G.shop_price,G.sales_sum,G.store_count,G.commission_price FROM ".$GLOBALS['ecs']->table('goods')." as G left join "
					.$GLOBALS['ecs']->table('shop_goods').	" as S ON  G.goods_id = S.goods_id where S.user_id ={$user_id} order by S.sort desc , S.add_time desc  ";
			}
			$goods_list=$GLOBALS['db']->getAll($sql);
			$goods_list=get_commission_price($goods_list);
			foreach ($goods_list as $k =>$v){
				$goods_list[$k]['recommend_url']="http://www.yilitong.com/mobile/Goods/goodsInfo/id/{$v['goods_id']}/recode/{$recommend_code}.html";
			}
			if ($sort=="commission"){
				$len = count($goods_list);
				if  ($orderby =="asc") {///商品佣金由低到高
					for($i=1;$i<$len;$i++){
						for($j=$len-1;$j>=$i;$j--){
							if($goods_list[$j]['commission_price']<$goods_list[$j-1]['commission_price']){
								$x=$goods_list[$j];
								$goods_list[$j]=$goods_list[$j-1];
								$goods_list[$j-1]=$x;
							}
						}
					}
				}else{//商品佣金由高到低
					for($i=1;$i<$len;$i++){
						for($j=$len-1;$j>=$i;$j--){					
							if($goods_list[$j]['commission_price']>$goods_list[$j-1]['commission_price']){
								$x=$goods_list[$j];
								$goods_list[$j]=$goods_list[$j-1];
								$goods_list[$j-1]=$x;
							}
						}
					}
				}
			}
			$results=array(
				'result' => '1',
				'info' => '请求成功',
				'goods_list'=>$goods_list,
				'shop_info' =>$info
			);
		}
	}else{
		$results=array(
			'result' => '0',
			'info' => '未登录'
		);
	}
	exit($json->json_encode_ex($results));
}
//微店主信息修改
elseif($action=="update_info"){
	//登录信息、店铺名称、店铺头像、背景图不能为空 
	if(!empty($userinfo['userid'])&&!empty($_REQUEST['shop_name'])&&!empty($_REQUEST['shop_image'])&&!empty($_REQUEST['shop_background'])){
		$user_id=$userinfo['userid'];
		$shop_name = $_REQUEST['shop_name'];
		$shop_image	= $_REQUEST['shop_image'];
		$shop_background = $_REQUEST['shop_background'];
		$shop_synopsis = !empty($_REQUEST['shop_synopsis']) ? $_REQUEST['shop_synopsis'] : "";
		$info = get_shopkeeper_info($user_id);
		if (empty($info)) {
			$results=array(
				'result' => '0',
				'info' => '不存在该微店主'
			);
			exit($json->json_encode_ex($results));
		}
		if (empty($shop_synopsis)) {
			$sql="UPDATE ".$GLOBALS['ecs']->table('shop')." SET shop_name='$shop_name',shop_image='$shop_image',shop_background='$shop_background' where user_id={$user_id}";
		}else{
			$sql="UPDATE ".$GLOBALS['ecs']->table('shop')." SET shop_name='$shop_name',shop_image='$shop_image',shop_background='$shop_background',shop_synopsis='$shop_synopsis' where user_id={$user_id}";
		}
		$rs=$GLOBALS['db']->query($sql);
		$info = get_shopkeeper_info($user_id);
		if ($rs) {
			$results=array(
				'result' => '1',
				'info' => '修改成功',
				'shop_info' => $info
			);
		}else{
			$results=array(
				'result' => '0',
				'info' => '修改失败'
			);
		}
	}else{
		$results=array(
			'result' => '0',
			'info' => '参数有误'
		);
	}
	exit($json->json_encode_ex($results));
}
//微店主关键字查询列表接口(搜索接口列表)  
elseif($action=='shopkeeper_search_goodslist'){
	$sort = (isset($_REQUEST['sort'])  && in_array(trim(strtolower($_REQUEST['sort'])), array('goods_id', 'shop_price', 'sales_sum','is_new'))) ? trim($_REQUEST['sort'])  : 'sort';
	$order = (isset($_REQUEST['order']) && in_array(trim(strtoupper($_REQUEST['order'])), array('ASC', 'DESC'))) ? trim($_REQUEST['order']) : 'ASC';
	$keywords  = !empty($_REQUEST['keywords'])   ? htmlspecialchars(trim($_REQUEST['keywords']))  : '';
	$brand_id     = !empty($_REQUEST['brand_id'])      ? intval($_REQUEST['brand_id'])      : 0;
	$category_id   = !empty($_REQUEST['category_id'])   ? intval($_REQUEST['category_id'])   : 0;
	$min_price = !empty($_REQUEST['min_price'])  ? intval($_REQUEST['min_price'])  : 0;
	$max_price = !empty($_REQUEST['max_price'])  ? intval($_REQUEST['max_price'])  : 0;
	
	if(!empty($userinfo['userid'])){//检测是否登录
		$user_id=$userinfo['userid'];
	}
	$arr = explode(' ', $keywords);

	if(!empty($keywords)){
		if (!empty($_COOKIE['hot_search']))
		{
			$history = explode(',', $_COOKIE['hot_search']);
			array_unshift($history, $keywords);
			$history = array_unique($history);
			while (count($history) > 5)
			{
				array_pop($history);
			}
			setcookie('hot_search', implode(',', $history), gmtime() + 3600 * 24 * 30);
		}
		else
		{
			setcookie('hot_search', $keywords, gmtime() + 3600 * 24 * 30);
		}
	}

	$where=" WHERE is_on_sale=1 AND examine = 1 AND is_designer = 0 AND 1=1";
	if(!empty($arr)){
		$where.=" AND ( ";
		foreach ($arr as $val){
			$where.=" goods_name like '%".$val."%' OR keywords like '%".$val."%' OR";
		}
		$where=substr($where, 1, -2);
		$where.=" ) ";
	}

	if(!empty($brand_id)){
		$where.=" AND brand_id={$brand_id}";
	}
	if(!empty($category_id)){
		$cateids=get_three_cate($category_id);
		$where.=" AND cat_id in({$cateids})";
	}
	if(!empty($min_price)){
		$where.=" AND shop_price >={$min_price}";
	}
	if(!empty($max_price)){
		$where.=" AND shop_price >={$max_price}";
	}
	$page=!empty($_REQUEST['page']) ? $_REQUEST['page'] : 0;
	$size =10;
	$begin = $page*$size;
	$limit = " LIMIT $begin,$size";
	//按条件获取商品列表
	
	$sql="SELECT commission_price,supplier_id,goods_id,goods_name,shop_price,original_img from ".$GLOBALS['ecs']->table('goods')." {$where} order by {$sort} {$order} {$limit}";
	$list=$GLOBALS['db']->getAll($sql);
	
	if(!empty($list)){
		foreach ($list as $key =>$val){
			$list[$key]=$val;
			if(!empty($val['original_img'])){
				$list[$key]['original_img']=IMG_HOST.$val['original_img'];
			}else{
				$list[$key]['original_img']='';
			}
		}
	}
	if(!empty($userinfo['userid'])){//检测是否登录
		$sql = "select id from  ".$GLOBALS['ecs']->table('shop')." where user_id={$user_id}";
		$shop_id = $GLOBALS['db']->getOne($sql);
		if ($shop_id) {
			$sql = "select goods_id from ".$GLOBALS['ecs']->table('shop_goods')." where user_id= {$user_id}";
			$goods_list = $GLOBALS['db']->getAll($sql);
			
			$sql = "select sale_gift,platform_sale_gift from ".$GLOBALS['ecs']->table('busines_rank')." where rank_id = 5";
			$busines_rank = $GLOBALS['db']->getRow($sql);
			foreach ($list as $k =>$v){
				$list[$k]['exist']='0';
				if ($list[$k]['supplier_id']=='41') {
					$list[$k]['commission_price']=$list[$k]['commission_price']*$busines_rank['platform_sale_gift']*0.01;
				}else{
					$list[$k]['commission_price']=$list[$k]['shop_price']*$busines_rank['sale_gift']*0.0006 + $list[$k]['commission_price'];
				}
				$list[$k]['commission_price']=sprintf('%.2f',$list[$k]['commission_price']);
				foreach($goods_list as $key =>$val){
					if ($val['goods_id'] == $v['goods_id']) {
						$list[$k]['exist']='1';
					}
				}
			}
		}
	}else{
		foreach ($list as $k =>$v){
			$list[$k]['exist']='0';
			$list[$k]['commission_price']='0';
		}
	}
	//获取商品数量
	$sql="SELECT count(goods_id) as count from ".$GLOBALS['ecs']->table('goods')."{$where}";
	$goods_count=$GLOBALS['db']->getOne($sql);
	$allpage=ceil($goods_count/$size);
	$rs=array(
			'result'=>'1',
			'info'=>'请求成功',
			'goods_list'=>$list,
			'page'=>$page,
			'size'=>$size,
			'count'=>$allpage,
			'keywords'=>$keywords,
			'brand_id'=>$brand_id,
			'category_id'=>$category_id,
			'min_price'=>$min_price,
			'max_price'=>$max_price
	);
	exit($json->json_encode_ex($rs));
}
//微店主添加商品
elseif($action=='add_goods'){
	$goods_id = trim($_REQUEST['goods_id']);
	$user_id=$userinfo['userid'];
	$addtime= time();
	if(!empty($user_id)&&!empty($goods_id)){//检测是否登录
		$info = get_shopkeeper_info($user_id);
		if (empty($info)) {
			$results=array(
				'result' => '0',
				'info' => '微店主不存在'
			);
		}else{
			$sql ="select count(id) from ".$GLOBALS['ecs']->table('shop_goods')." where user_id = {$user_id} ";
			$goods_num=$GLOBALS['db']->getOne($sql);
			if ($goods_num>50) {
				$results=array(
						'result' => '0',
						'info' => '添加商品数量不得超过50个'
					);
			}else{
				$sql ="select id from ".$GLOBALS['ecs']->table('shop_goods')." where user_id = {$user_id} and goods_id={$goods_id} ";
				$goods=$GLOBALS['db']->getOne($sql);
				if (empty($goods)) {
					$sql = "INSERT INTO " .$GLOBALS['ecs']->table('shop_goods'). "(`user_id`,`goods_id`,`add_time`) VALUES ('$user_id', '{$goods_id}','{$addtime}')";
					$GLOBALS['db']->query($sql);
					$results=array(
							'result' => '1',
							'info' => '添加成功'
					);
				}else{
					$results=array(
							'result' => '1',
							'info' => '商品已添加'
					);
				}
			}
		}
	}else{
		$results=array(
			'result' => '0',
			'info' => '参数有误'
		);
	}
	exit($json->json_encode_ex($results));
}
//微店主删除商品
elseif($action=='delete_goods'){
	$goods_id = trim($_REQUEST['goods_id']);
	$user_id=$userinfo['userid'];
	if(!empty($user_id)&&!empty($goods_id)){//检测是否登录
		$sql="DELETE from ". $ecs->table('shop_goods') ." WHERE user_id ='{$user_id}' AND goods_id in ( {$goods_id} ) ";
		$GLOBALS['db']->query($sql);
		$results=array(
			'result' => '1',
			'info' => '移除成功'
		);
	}else{
		$results=array(
			'result' => '0',
			'info' => '参数有误'
		);
	}
	exit($json->json_encode_ex($results));
}
//微店置顶商品
elseif($action=='stick_goods'){
	$goods_id = trim($_REQUEST['goods_id']);
	$user_id=$userinfo['userid'];
	$sql = "select id,sort from ". $ecs->table('shop_goods') ." where user_id ={$user_id} and goods_id = {$goods_id}";
	$goods = $GLOBALS['db']->getRow($sql);
	if ($goods) {
		if ($goods['sort'] == 1) {
			$sql="UPDATE ".$GLOBALS['ecs']->table('shop_goods')." set sort = 0 where id = {$goods['id']}";
			$GLOBALS['db']->query($sql);
			$results=array(
					'result' => '1',
					'info' => '取消置顶'
			);
		}else{
			$sql="UPDATE ".$GLOBALS['ecs']->table('shop_goods')." set sort = 1 where id = {$goods['id']}";
			$GLOBALS['db']->query($sql);
			$results=array(
					'result' => '1',
					'info' => '置顶成功'
			);
		}
	}else{
		$results=array(
				'result' => '1',
				'info' => '查无商品'
		);
	}
	exit($json->json_encode_ex($results));
	
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
//获取微店主信息
function get_shopkeeper_info($shopkeeper_id){
	$sql="select shop_name,shop_image,shop_background,shop_synopsis from ".$GLOBALS['ecs']->table('shop')." where user_id = {$shopkeeper_id} ";
	$info = $GLOBALS['db']->getRow($sql);
	if(!strstr($info['shop_background'],"yilitong")){
		$info['shop_background']=IMG_HOST.$info['shop_background'];
	}
	if(!strstr($info['shop_image'],"yilitong")){
		$info['shop_image']=IMG_HOST.$info['shop_image'];
	}
	return $info;
}
function get_commission_price($list){
	$sql = "select sale_gift,platform_sale_gift from ".$GLOBALS['ecs']->table('busines_rank')." where rank_id = 5";
	$busines_rank = $GLOBALS['db']->getRow($sql);
	foreach ($list as $k =>$v){
		if ($list[$k]['supplier_id']=='41') {
		
			$list[$k]['commission_price']=$list[$k]['commission_price']*$busines_rank['platform_sale_gift']*0.01;
		}else{
			$list[$k]['commission_price']=$list[$k]['shop_price']*$busines_rank['sale_gift']*0.0006 + $list[$k]['commission_price'];
		}
		$list[$k]['commission_price']=sprintf('%.2f',$list[$k]['commission_price']);
		if(!empty($v['goods_thumb'])){
			$list[$k]['goods_thumb']=IMG_HOST.$v['goods_thumb'];
		}else{
			$list[$k]['goods_thumb']='';
		}
	}
	return $list;
}
?>