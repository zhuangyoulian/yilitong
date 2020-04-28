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
//首页信息
if($action=='menu'){
	$list[0]['image'] = IMG_HOST."/public/images/btn_under_home@2x.png";
	$list[0]['checked_image'] = IMG_HOST."/public/images/btn_under_home_red@2x.png";
	$list[0]['value'] = '一礼通';
	$list[1]['image'] = IMG_HOST."/public/images/btn_under_classification@2x.png";
	$list[1]['checked_image'] = IMG_HOST."/public/images/btn_under_classification_red@2x.png";
	$list[1]['value'] = '分类';
	$list[2]['image'] = IMG_HOST."/public/images/design_unpre@2x.png";
	$list[2]['checked_image'] = IMG_HOST."/public/images/design_pre@2x.png";
	$list[2]['value'] = '找设计';
	$list[3]['image'] = IMG_HOST."/public/images/btn_under_shopping@2x.png";
	$list[3]['checked_image'] = IMG_HOST."/public/images/btn_under_shopping_red@2x.png";
	$list[3]['value'] = '购物车';
	$list[4]['image'] = IMG_HOST."/public/images/btn_under_mime@2x.png";
	$list[4]['checked_image'] = IMG_HOST."/public/images/btn_under_mime_red@2x.png";
	$list[4]['value'] = '我的';
	$results=array(
		'result' => '1',
		'info' => '请求成功',
		'list' => $list
	);
	exit($json->json_encode_ex($results));
}
/**
 * 检测登录状态
 */
elseif ($action=='check_login_status'){
	if(!empty($userinfo['userid'])){
		$user_id = $userinfo['userid'];
		$device_token = $userinfo['device_token'];
	
		$sql =" select user_id FROM ".$GLOBALS['ecs']->table('users')." where user_id ='{$user_id}' and device_token='{$device_token}' ";
		$user= $db->getOne($sql);
		if(!empty($user)){
			$sql="UPDATE ".$GLOBALS['ecs']->table('users')." SET last_login= ".time() ." where user_id = {$user_id} ";
			$rs3=$GLOBALS['db']->query($sql);
			exit($json->json_encode_ex(['result'=>1,'info' => 'OK','type'=>1]));
		}else{
			exit($json->json_encode_ex(['result'=>1,'info' => '重新登录','type'=>2]));
		}
	}else{
		exit($json->json_encode_ex(['result'=>1,'info' => '未登录','type'=>0]));
	}
}

// 获取 启动APP广告
elseif($action=='advertisement'){

	$temp=get_Ads(37);
	
	
	$results=array('result'=>1,'info'  =>'请求成功','ad_data'  =>$temp);
	
	exit($json->json_encode_ex($results));
}

// 获取 引导页广告
elseif($action=='boot_page'){

	$temp=get_Ads1(38);


	$results=array('result'=>1,'info'  =>'请求成功','ad_data'  =>$temp);

	exit($json->json_encode_ex($results));
}
//首页信息
elseif($action=='index'){
	//首页广告 banner
	$banner=get_Ads(12);
	//金刚区版块   版块：section
	$section=get_Ads(27);
    //app中间广告,获取单张广告
	$center_ads=get_Ads(39);
	//品牌推荐
	$brand_list = get_recommend_brand();
	// 首页公告
	$sql = "SELECT article_id,title FROM " .$GLOBALS['ecs']->table('article')." WHERE  is_open='1' and cat_id ='4' ORDER BY `cat_id` LIMIT 5";
	$article_list = $GLOBALS['db']->getAll($sql);
	
	if(!empty($banner) || !empty($section)){
		$results=array('result'=>1,'info'  =>'请求成功','banner'=>$banner,'section'=>$section,'center_ads'=>$center_ads,'brand_list'=>$brand_list,'article'=>$article_list);
	    exit($json->json_encode_ex($results));
	}else{
		$results=array(
				'result'=>0,
				'info'  =>'无数据'
		);
		exit($json->json_encode_ex($results));
	}
  }

//首页推荐列表
elseif($action=='index_goods'){
	
	$page=!empty($_REQUEST['page']) ? $_REQUEST['page'] : 0 ;
	/* 查询商品 */
	$page=!empty($page) ? $page : 0;
	$size =30;
	$begin = $page*$size;
	$limit = " LIMIT $begin,$size";
	
	$where=" AND g.examine = 1 AND g.is_recommend = 1";
	$sql = "SELECT g.goods_id, g.goods_name, g.market_price, g.shop_price,g.original_img,g.goods_thumb".
			" FROM " .$GLOBALS['ecs']->table('goods'). " AS g ".
			" WHERE g.is_on_sale = 1 AND g.is_designer = 0 {$where} order by g.sort asc ,add_time desc {$limit}";
	$res = $GLOBALS['db']->getAll($sql);
	
	$rs=array();
	if(!empty($res)){
		foreach ($res as $key =>$val){
			if(!empty($val['original_img'])){
				$temp['image']=IMG_HOST.$val['goods_thumb'];
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
	$sql = "SELECT COUNT(goods_id) FROM " .$GLOBALS['ecs']->table('goods').
	"  WHERE is_on_sale = 1 AND is_recommend = 1 AND is_designer = 0 ";
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
//模糊查询接口
elseif ($action=='fuzzy_query'){
	$type=!empty($_REQUEST['type']) ? $_REQUEST['type'] : 0 ;
	$keyword = sql_injection($_REQUEST['keyword'] );
	if ($type=='1') {
		$sql = "select supplier_id as s_id ,supplier_name as keyword from " . $GLOBALS['ecs']->table('supplier') . " where supplier_name like '%{$keyword}%' and status =1  limit 10";
	
	}else{
		$sql = "select count as s_id,keyword from " . $GLOBALS['ecs']->table('keywords') . " where keyword like '%{$keyword}%' order by count desc limit 10";	
	}
	if(preg_match("/[\x7f-\xff]/", $keyword)){
		$keywords = $GLOBALS['db']->getAll($sql);
	}
	if (!$keywords) {
		$keywords=array();
	}
	
	$rs=array('result'=>'1','info'=>'请求成功','keywords'=>$keywords);
	exit($json->json_encode_ex($rs));
}
//搜索关键字接口
elseif ($action=='search_keywords'){
	//热门搜索
	$serachkeywords=$_CFG['search_keywords'];
	$rs['result']=1;
	$rs['keywords']=explode(',',$serachkeywords);
	$cokies=$_COOKIE['hot_search'];
	 if(!empty($cokies)){
		$rs['near_search']=explode(',',$_COOKIE['hot_search']);
	 } 
	//品牌列表
	/* $sql = 'SELECT id as brand_id, name FROM ' . $GLOBALS['ecs']->table('brand') . ' ORDER BY sort';
	$rs['brand_list'] = $GLOBALS['db']->getAll($sql);
	
	//分类列表
	$sql = 'SELECT id as category_id,mobile_name as name FROM ' . $GLOBALS['ecs']->table('goods_category') . ' where parent_id=0 AND is_show=1 ORDER BY sort_order';
	$rs['category_list']= $GLOBALS['db']->getAll($sql);
	
	//组装筛选价格
	$rs['price_list']=array(
			0=>array('name'=>'0-29','min_price'=>0,'max_price'=>29),
			1=>array('name'=>'30-59','min_price'=>30,'max_price'=>59),
			2=>array('name'=>'60-99','min_price'=>60,'max_price'=>99),
			3=>array('name'=>'100-159','min_price'=>100,'max_price'=>159),
			4=>array('name'=>'160以上','min_price'=>160,'max_price'=>99999)
	); */
	exit($json->json_encode_ex($rs));
}

//关键字查询列表接口(搜索接口列表) //新增微店主
elseif($action=='search_goodslist'){
	$sort = (isset($_REQUEST['sort'])  && in_array(trim(strtolower($_REQUEST['sort'])), array('goods_id', 'shop_price', 'sales_sum','is_new'))) ? trim($_REQUEST['sort'])  : 'sort';
	$order = (isset($_REQUEST['order']) && in_array(trim(strtoupper($_REQUEST['order'])), array('ASC', 'DESC'))) ? trim($_REQUEST['order']) : 'ASC';
	$keywords  = !empty($_REQUEST['keywords'])   ? htmlspecialchars(trim($_REQUEST['keywords']))  : '';
	$brand_id     = !empty($_REQUEST['brand_id'])      ? intval($_REQUEST['brand_id'])      : 0;
	$category_id   = !empty($_REQUEST['category_id'])   ? intval($_REQUEST['category_id'])   : 0;
	$min_price = !empty($_REQUEST['min_price'])  ? intval($_REQUEST['min_price'])  : 0;
	$max_price = !empty($_REQUEST['max_price'])  ? intval($_REQUEST['max_price'])  : 0;
	$list=array();
	
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
    $where  .= " and goods_id !=5898";//预约产品id限制 修改日期2020.3.10
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
	$is_shopkeeper = '0';
	if(!empty($userinfo['userid'])){//检测是否登录
		$user_id=$userinfo['userid'];
		$sql = "select id from  ".$GLOBALS['ecs']->table('shop')." where user_id={$user_id}";
		$shop_id = $GLOBALS['db']->getOne($sql);
		if ($shop_id) {
			$is_shopkeeper = '1';
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
		}else{
			foreach ($list as $k =>$v){
				$list[$k]['exist']='0';
				$list[$k]['commission_price']='0';
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
			'max_price'=>$max_price,
			'is_shopkeeper'=>$is_shopkeeper
	);
	exit($json->json_encode_ex($rs));
}

//关键字查询列表接口(搜索接口列表)//旧接口
elseif($action=='search_goodslist_old'){
	$sort = (isset($_REQUEST['sort'])  && in_array(trim(strtolower($_REQUEST['sort'])), array('goods_id', 'shop_price', 'sales_sum','is_new'))) ? trim($_REQUEST['sort'])  : 'sort';
	$order = (isset($_REQUEST['order']) && in_array(trim(strtoupper($_REQUEST['order'])), array('ASC', 'DESC'))) ? trim($_REQUEST['order']) : 'ASC';
	 
	$keywords  = !empty($_REQUEST['keywords'])   ? htmlspecialchars(trim($_REQUEST['keywords']))  : '';
	$brand_id     = !empty($_REQUEST['brand_id'])      ? intval($_REQUEST['brand_id'])      : 0;
	$category_id   = !empty($_REQUEST['category_id'])   ? intval($_REQUEST['category_id'])   : 0;
	$min_price = !empty($_REQUEST['min_price'])  ? intval($_REQUEST['min_price'])  : 0;
	$max_price = !empty($_REQUEST['max_price'])  ? intval($_REQUEST['max_price'])  : 0;
	
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
    $where  .= " and goods_id !=5898";//预约产品id限制 修改日期2020.3.10
	$page=!empty($_REQUEST['page']) ? $_REQUEST['page'] : 0;
	$size =10;
	$begin = $page*$size;
	$limit = " LIMIT $begin,$size";
	//按条件获取商品列表
	$sql="SELECT goods_id,goods_name,shop_price,market_price,sales_sum,original_img from ".$GLOBALS['ecs']->table('goods')." {$where} order by {$sort} {$order} {$limit}";
	
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

//商品详情信息
elseif($action=='good_detail'){
	
	$good_id=$_REQUEST['goods_id'];
	$goods=get_goods_id($good_id);
	if(!empty($userinfo['userid'])){
		$userid=$userinfo['userid'];
	}
	if(empty($goods) || empty($good_id)){
	
		$rs=array(
				'result'=>'0',
				'info'=>'请求失败',
				'goods'=>array(),
		);
		exit($json->json_encode_ex($rs));
	}
	if($goods['examine'] != '1' ){
		$rs=array(
				'result'=>'0',
				'info'=>'该商品已下架',
				'goods'=>array(),
		);
		exit($json->json_encode_ex($rs));
	}
	
	$GLOBALS['db']->query("UPDATE ".$GLOBALS['ecs']->table('goods')." SET click_count= click_count + 1 where goods_id={$good_id}"); //点击数
	
	$sql="select image_url from ".$GLOBALS['ecs']->table('goods_images')." where goods_id={$good_id}";
	$arr_img=$GLOBALS['db']->getAll($sql);
	if(!empty($arr_img)){
		foreach ($arr_img as $val){
			$goods['image'][]=IMG_HOST.$val['image_url'];
		}
	}else{
		$goods['image']=array();
	}
	
	if(!empty($goods['goods_content'])){		
		$pregRule = '/<img src=\"\/public\//';
		$rep_content = preg_replace_callback($pregRule, function(){
			return '<img src="'.IMG_HOST."public/";
		}, $goods['goods_content']);
	//	$rep_content=get_img_thumb_url($rep_content);
		$goods['goods_content'] = "<style>p{ margin:0px; padding:0px;}</style>";
		$goods['goods_content'] .=$rep_content;
	} 
	
	
	
	$goods['sale_support']="一礼通商城上产品均属于正规渠道的产品；凡是您所购买的产品有任何质量问题我们都将为您处理，介于商城建设初期，售后政策逐步完善中，您在购物过程中遇到的问题可联系在线客服，拨打24小时售后服务热线：400-089-7879 。我们的售后人员都是一对一的为您解决，直到您满意为止！一礼通竭诚为您服务！";
	// 获得商品的规格 
	$properties = get_goods_pro($good_id);  // 获得商品的规格和属性
	
	if(!empty($properties)){
		$goods['pro_list']=$properties;
	}else{
		$goods['pro_list']=array();
	}
	$goods['is_collect']='0';
	//是否收藏商品
	$goods['exist']=0;//是否已经添加到微店
	$goods['recommend_url']="http://www.yilitong.com/mobile/Goods/goodsInfo/id/{$good_id}.html";
	if(!empty($userid) && !empty($good_id)){
		$sql="select  count(*) from ".$GLOBALS['ecs']->table('goods_collect')." where user_id={$userid} AND goods_id={$good_id}";
		$goods_collect_num=$GLOBALS['db']->getOne($sql);
		if($goods_collect_num>0){
			$goods['is_collect']='1';
		}	
	
		//检查是否添加到微店
		$sql = "select id from ".$GLOBALS['ecs']->table('shop_goods')." where user_id ={$userid} and goods_id ={$good_id}";
		$is_exist=$GLOBALS['db']->getOne($sql);
	
		if ($is_exist) {
			$goods['exist']=1;
		}
		//检查是否为微店主，如果是 算取佣金
		$sql = "select id from ".$GLOBALS['ecs']->table('shop')." where user_id = {$userid}";
		$shopkeeper = $GLOBALS['db']->getOne($sql);

		//如果是微店主 算取佣金
		if ($shopkeeper) {
			$sql="select recommend_code from " .$GLOBALS['ecs']->table('users')." where user_id={$userid}";
			$recommend_code = $GLOBALS['db']->getOne($sql);
			if ($recommend_code) {
				$goods['recommend_url']="http://www.yilitong.com/mobile/Goods/goodsInfo/id/{$good_id}/recode/{$recommend_code}.html";
			}
			$sql = "select sale_gift,platform_sale_gift from ".$GLOBALS['ecs']->table('busines_rank')." where rank_id = 5";
			$busines_rank = $GLOBALS['db']->getRow($sql);
			if ($goods['supplier_id']=='41') {
				$goods['commission_price']=$goods['commission_price']*$busines_rank['platform_sale_gift']*0.01;
			}else{
				$goods['commission_price']=$goods['shop_price']*$busines_rank['sale_gift']*0.0006 + $goods['commission_price'];
			}
			$goods['commission_price']=sprintf('%.2f',$goods['commission_price']);
		}
	}
	
	//获取默认属性,价格
	$defaultpro=get_goods_defaultpro($good_id);
	if(!empty($defaultpro)){
		$goods['default_spe']=!empty($defaultpro['key_name']) ? $defaultpro['key_name'] : "";
		$keystr="";
		
		if(strstr($defaultpro['key'], '_')){
			$keystr=str_ireplace('_',',',$defaultpro['key']);
		}else{
			$keystr=$defaultpro['key'];
		}
		$goods['default_spe_attr']=!empty($keystr) ? $keystr : "";
		$sql="select G.store_count from ".$GLOBALS['ecs']->table('goods_price')." as G where G.goods_id={$good_id} AND G.KEY ='{$defaultpro['key']}' ";

		$number= $GLOBALS['db']->getOne($sql);	
		if ($number>0) {
			$goods['default_spe_num']="1";
		}else{
			$goods['default_spe_num']="0";
		}
		
		$sql="select G.store_count from ".$GLOBALS['ecs']->table('goods_price')." as G where G.goods_id={$good_id}";
		
		$all_attr= $GLOBALS['db']->getAll($sql);
	
		if ($all_attr) {
			$all_attr_count = 0;
			foreach ($all_attr as $key =>$val){
				$all_attr_count = $val['store_count'] + $all_attr_count;
			}
		}
		
		
		$goods['store_count']=!empty($all_attr_count) ? $all_attr_count: $goods['store_count'];
		$goods['shop_price']=!empty($goods['shop_price']) ? $goods['shop_price'] : $goods['shop_price'];
		
	}else{
		$sql="select G.store_count from ".$GLOBALS['ecs']->table('goods')." as G where G.goods_id={$good_id}";
		$number= $GLOBALS['db']->getOne($sql);
		if ($number>0) {
			$goods['default_spe_num']="1";
		}else{
			$goods['default_spe_num']="0";
		}
		$goods['default_spe']="";
		$goods['default_spe_attr']="";
	}
	
	//获取店铺信息
	if(!empty($goods['supplier_id'])){
		$storeinfo=get_supplier($goods['supplier_id']);
		if(!empty($storeinfo)){
			$sql = "select value FROM ".$GLOBALS['ecs']->table('supplier_config')." where supplier_id = {$goods['supplier_id']} and name = 'phone'";
			$goods['phone'] = $GLOBALS['db']->getOne($sql);
			$sql = "select value FROM ".$GLOBALS['ecs']->table('supplier_config')." where supplier_id = {$goods['supplier_id']} and name = 'qq'";
			$goods['qq1'] = $GLOBALS['db']->getOne($sql);
			$sql = "select value FROM ".$GLOBALS['ecs']->table('supplier_config')." where supplier_id = {$goods['supplier_id']} and name = 'qq2'";
			$goods['qq2'] = $GLOBALS['db']->getOne($sql);
			if (!$goods['qq2']) {
				$goods['qq2']="";
			}
			if (!$goods['qq1']) {
				$goods['qq1']="";
			}
			if (!$goods['phone']) {
				$goods['phone']="";
			}
			$goods['supplier_name']=!empty($storeinfo['supplier_name']) ? $storeinfo['supplier_name'] : '';
			$goods['company_name']=!empty($storeinfo['company_name']) ? $storeinfo['company_name'] : '' ;
			if(!empty($storeinfo['introduction'])){
				$strnum=mb_strlen($storeinfo['introduction'],'utf-8');
				if($strnum>200){
					$goods['business_sphere']=mb_substr($storeinfo['introduction'],0,200,'utf-8')."...";
				}else{
					$goods['business_sphere']=$storeinfo['introduction'];
				}
			}else{
				$goods['business_sphere']="";
			}
			$goods['supplier_bond']=$storeinfo['supplier_money'] > 0  ? "1" : "0";
			$goods['supplier_logo']=!empty($storeinfo['logo']) ? IMG_HOST.$storeinfo['logo'] : '';
		}
		
		
		$sql=" select add_time,deliver_rank,service_rank,user_id,goods_rank,username,content from ".$GLOBALS['ecs']->table('comment')." where goods_id = ".$good_id."  order by add_time ";
		$comments=$GLOBALS['db']->getRow($sql);
		if ($comments) {
			if ($comments['user_id']) {
				$sql=" select head_pic from ".$GLOBALS['ecs']->table('users')." where user_id = ".$comments['user_id'];
				$cuser=$GLOBALS['db']->getRow($sql);
				$comments['head_pic']=IMG_HOST.$cuser['head_pic'];
				$goods['goods_comments'][]=$comments;
					
			}
		}else{
			$goods['goods_comments']=array();
		}
		
		if ($goods['is_designer']==1) {
			$sql="SELECT  S.qq,S.guimo as wechat,U.head_pic,S.company_type,S.company_name FROM ".$GLOBALS['ecs']->table('users')." as U left join "
					.$GLOBALS['ecs']->table('supplier').	" as S ON  U.user_id = S.user_id where S.supplier_id = ".$goods['supplier_id'];
			$designer=$GLOBALS['db']->getRow($sql);
			
			$goods['supplier_logo']=!empty($designer['head_pic']) ? IMG_HOST.$designer['head_pic'] : '';
			$goods['business_sphere']=$designer['company_type'];
			$goods['area']=$designer['company_name'];
			$goods['qq']=$designer['qq'];
			$goods['wechat']=$designer['wechat'];
			$sql=" select add_time,deliver_rank,service_rank,user_id,goods_rank,username,content from ".$GLOBALS['ecs']->table('comment')." where goods_id = ".$good_id."  order by add_time desc limit 3";
			
			$comments=$GLOBALS['db']->getAll($sql);
			if ($comments) {
				foreach ($comments as $key =>$val){
					if ($val['user_id']) {
						$sql=" select head_pic from ".$GLOBALS['ecs']->table('users')." where user_id = ".$val['user_id'];
						$cuser=$GLOBALS['db']->getRow($sql);
						$goods['comments'][$key]['username']=$comments[$key]['username'];
						$goods['comments'][$key]['content']=$comments[$key]['content'];
						$goods['comments'][$key]['head_pic']=IMG_HOST.$cuser['head_pic'];
						$goods['comments'][$key]['goods_rank']=$comments[$key]['goods_rank'];
						$goods['comments'][$key]['add_time']=$comments[$key]['add_time'];
						$goods['comments'][$key]['deliver_rank']=$comments[$key]['deliver_rank'];
						$goods['comments'][$key]['service_rank']=$comments[$key]['service_rank'];
						$goods['comments'][$key]['img']=array();
					}
				}
			}else{
				$goods['comments']=array();
			}
			
			
		}
		
	}else{
		$goods['supplier_name']="平台自营商品";
		$goods['company_name']="一礼通";
		$goods['business_sphere']="礼至集团是一家生产与销售为一体专注于礼品定制、礼品批发,礼品方案制定,产品研发、品牌形象策划、互联网软件开发与应用、资源平台整合、实业投资、国内外进出口等";
		$goods['supplier_logo']="";
		$goods['supplier_bond']="0";
	}
	
	//获取规格信息
	/* $spros=get_prolist($good_id);
	if(!empty($spros)){
		$goods['pro']=$spros;
	}else{
		$goods['pro']=array();
	} */
	$goods['pro']=array();
	if(!empty($goods['attribute'])){
		$pregRule = "/\/public\//";
		$content = preg_replace_callback($pregRule, function(){
			return IMG_HOST."public/";
		}, $goods['attribute']);
		$goods['goods_remark']=$content;
		//$goods['goods_remark']=get_img_thumb_url($content);
		//$goods['goods_remark']=!empty($goods['attribute']) ? trim($goods['attribute']) : "";
	}else{
		$goods['goods_remark']='<p><img src="http://yilitong.com/public/images/noattr.png" title="暂无规格参数"/></p>';
	}
	
	$goods['original_img']=!empty($goods['original_img']) ? IMG_HOST.$goods['original_img'] :"";
	//评论咱为空
	$goods['now_time']="".time()."";
	
	/****
	 * 商品活动改价格，增加开始时间，结束时间，是否为活动商品，特价
	 * act_type=1 正常购买，可以点提交购物车 act_type=2到时间购买
	 */
	
	$goods['start_time']="";
	$goods['end_time']="";
	$goods['is_activity']='0';
	$goods['spec_price']="";
	$goods['buy_limit']="";
	$goods['act_type']="";
	$goods['act_type']="";
	if ($goods['prom_type'] == 1 || $goods['prom_type'] == 5) {
		if($goods['prom_id']>0){
			$sql="select * from ".$GLOBALS['ecs']->table('panic_buying')." where goods_id={$good_id} AND is_end=0";
		    $row=$GLOBALS['db']->getRow($sql);
			$goods['start_time']=!empty($row['start_time']) ? $row['start_time'] : " ";
			$goods['end_time']=!empty($row['end_time']) ? $row['end_time'] : " ";
			$goods['is_activity']='1';
			$goods['spec_price']=$row['price'];
			$goods['buy_limit']=$row['buy_limit'];
			if($row['buy_type']==5){
				$goods['act_type']='2';
			}else{
				$goods['act_type']="1";
			}
		}
	}
	
	$list[0]['service_description'] = "该商品支持无理由退货、商品自签收起7个自然日内、退货邮费自行承担。";
	$list[0]['service_value'] = '七天无理由退货';
	$list[1]['service_description'] = "品质护航购物无忧";
	$list[1]['service_value'] = '正品保证';
	$list[2]['service_description'] = "商家包邮，指卖家承诺给买家承担首次发货运费，港澳台、海外、国内部分商家发货包邮位置范围外的偏远地区除外。";
	$list[2]['service_value'] = '商家包邮';
	$goods['service_note']=$list;
	$goods['activity_prompt'] = "";
	$goods['activity_type'] = "";
	$goods['minus'] = "0";
	$goods['buy_type'] = ""; //prom_type为2的情况下    buy_type=1为秒杀  =2为折扣
	$goods['discount'] = "";//   buy_type=2的情况下 discount为折扣力度
	$goods['activity_title'] = "";//   活动标题
	$goods['act_id'] = "";//   活动id
	$goods['prompt'] = "";//   活动未开始的提示 例：¥169 / 活动打7折
	if ($goods['prom_type'] == 3) {
		$now_time=time();
		if ($goods['prom_id']!=0) {
			$sql="select * from ".$GLOBALS['ecs']->table('prom_goods')." where id={$goods['prom_id']} and start_time < {$now_time} and end_time>{$now_time} ";
		    $row=$GLOBALS['db']->getRow($sql);
		    if ($row) {
		    	if ($row['type'] == 1) {
		    		$goods['start_time']=!empty($row['start_time']) ? $row['start_time'] : " ";
		    		$goods['end_time']=!empty($row['end_time']) ? $row['end_time'] : " ";
		    		$goods['minus'] = "1";
		    		$goods['activity_type'] = "满减";
		    		$goods['activity_prompt'] = "指定商品满{$row['money']},立减￥{$row['expression']}";
		    	}
		    }
		}
	}else if($goods['prom_type'] == 2) {
		$now_time=time();
		$sql="select B.id,G.activity_market_price,G.activity_price,G.activity_count,B.title,B.start_time,B.end_time,B.buy_type,B.discount from ".$GLOBALS['ecs']->table('discount_goods')." as G left join ".$GLOBALS['ecs']->table('discount_buy')." as B on G.discount_id = B.id where G.goods_id ={$good_id}  and B.end_time >{$now_time} and B.is_start = 1";
		$discount_info= $GLOBALS['db']->getRow($sql);
		if ($discount_info) {
			if ($discount_info['start_time']>$now_time) {//活动未开始
				$goods['act_id'] = $discount_info['id']; //活动id
				$goods['activity_title'] = $discount_info['title']; //活动标题
				$goods['buy_type'] = $discount_info['buy_type']; //buy_type=1为秒杀  =2为折扣
				if ($goods['buy_type']==1) {
					$goods['prompt'] = '¥'.$discount_info['activity_price'];//显示售价
				}elseif ($goods['buy_type']==2){
					$goods['prompt'] = '商品'.$discount_info['discount'].'折优惠';
				}
				$goods['start_time']=!empty($discount_info['start_time']) ? $discount_info['start_time'] : "";
				$goods['end_time']=!empty($discount_info['end_time']) ? $discount_info['end_time'] : "";
			}else{//活动已开始未结束
				$sql=" update ".$GLOBALS['ecs']->table('discount_goods')." set browse_num=browse_num+1 where goods_id='{$good_id}'";
				$GLOBALS['db']->query($sql);
				$goods['activity_price'] = $discount_info['activity_price'];//显示售价
				$goods['activity_title'] = $discount_info['title']; //活动标题
				$goods['act_id'] = $discount_info['id']; //活动id
				$goods['buy_type'] = $discount_info['buy_type']; //buy_type=1为秒杀  =2为折扣
				$goods['discount'] = '商品'.$discount_info['discount'].'折优惠'; // buy_type=2的情况下 discount为折扣力度
				$goods['market_price'] = $discount_info['activity_market_price']; //显示原价
				$goods['shop_price'] = $discount_info['activity_price'];//显示售价
				$goods['store_count'] = $discount_info['activity_count'];//显示活动库存
				$goods['start_time']=!empty($discount_info['start_time']) ? $discount_info['start_time'] : "";
				$goods['end_time']=!empty($discount_info['end_time']) ? $discount_info['end_time'] : "";
			}
		}
	}
	
	$is_coupon = '0';
	$temtime=time();
	$sql = "select * from ".$GLOBALS['ecs']->table('coupon')." where send_start_time<{$temtime} AND send_end_time>{$temtime} AND createnum>0 AND type=2 order by money desc";
	$coupon= $GLOBALS['db']->getRow($sql);
	if (empty($coupon)) {
		$goods['is_coupon'] = '0';
		$goods['coupon_note'] = "";
	}else{
		$goods['is_coupon'] = '1';
		$goods['coupon_note'] = "满".floor($coupon['condition'])."元减".floor($coupon['money'])."元";
	}
	$goods['store_count']=(int)$goods['store_count'];
	$rs=array(
			'result'=>'1',
			'info'=>'请求成功',
			'goods'=>$goods,
	);

	exit($json->json_encode_ex($rs));
}
//获取属性价格和商品
elseif($action=='goods_price'){
	$good_id=$_REQUEST['goods_id'];
	$attr=trim($_REQUEST['attr']);
	$key_value=str_replace(",","_",$attr);
	$sql="select G.goods_id,G.key,G.key_name,G.price,G.store_count from ".$GLOBALS['ecs']->table('goods_price')." as G where G.key='{$key_value}' AND G.goods_id={$good_id}";
	$row=$GLOBALS['db']->getRow($sql);
	if(empty($row['price'])){
		$goods=get_goods_id($good_id);
	}
	$rs=array(
			'result'=>'1',
			'info'=>'请求成功',
			'shop_price'=>!empty($row['price']) ? $row['price'] :$goods['shop_price'],
	);
	exit($json->json_encode_ex($rs));
}

//满减活动列表
elseif($action=="activity_goods_list"){
	$now_time = time();
	
	$sql="select type,app_prom_img,goods_ids,ad_height,ad_width,description as description_note from ".$GLOBALS['ecs']->table('prom_goods')." where start_time < {$now_time} and end_time > {$now_time}";
	$list=$GLOBALS['db']->getALL($sql);
	if (condition) {
		$rs=array();
		
		foreach ($list as $key =>$val){
			$item=array();
			if($val['type'] == 1 )
			{
				$list[$key]['type']="满减";
			}
			
			if ($val['app_prom_img']) {
				$list[$key]['app_prom_img']= IMG_HOST.$val['app_prom_img'];
			}
			$goodsList = explode(",",$val['goods_ids']);
			foreach ( $goodsList as $key1 => $val1 ){
				$sql="select goods_id,goods_name,shop_price,goods_thumb,store_count from ".$GLOBALS['ecs']->table('goods')." where goods_id={$val1}";
				$goodsInfo=$GLOBALS['db']->getRow($sql);
				if ($goodsInfo['goods_thumb']) {
					$goodsInfo['goods_thumb']=IMG_HOST.$goodsInfo['goods_thumb'];
				}
				$sql="select store_count from ".$GLOBALS['ecs']->table('goods_price')." where goods_id={$val1}";
				$goodsAttr=$GLOBALS['db']->getAll($sql);
				$goods_count=0;
				if ($goodsAttr) {
					foreach($goodsAttr as $value){
						$goods_count=$goods_count+$value['store_count'];
					}
				}else{
					$goods_count=$goodsInfo['store_count'];
				}
				
				$goodsInfo['store_count']=$goods_count;
				$goodsInfo['type']=$list[$key]['type'];
				$item[]=$goodsInfo;
			
			}
			unset($list[$key]['type']);
			$list[$key]['goods_ids'] = $item;
			$list[$key]['ad_height'] = $val['ad_height'];
			$list[$key]['ad_width'] = $val['ad_width'];
		}
		$rs=array('result'=>1,'info'  =>'请求成功','item'=>$list);
	}else{
		$rs=array('result'=>0,'info'  =>'暂无活动','item'=>'');
	}
	exit($json->json_encode_ex($rs));

}
//收藏商品接口
elseif($action=="goods_collect"){
	$good_id=$_REQUEST['goods_id'];
	if(!empty($userinfo['userid'])){
		$userid=$userinfo['userid'];
	}
	if(empty($good_id) || empty($userid)){
		$rs=array('result'=>'0','info'=>'缺少必要参数');
		exit($json->json_encode_ex($rs));
	}
	$temtime=time();
	$sql="select count(*) from ".$GLOBALS['ecs']->table('goods_collect')." where goods_id={$good_id} AND user_id={$userid}";
	$one=$GLOBALS['db']->getOne($sql);
	if(!empty($one)){
		$sql="delete from ".$GLOBALS['ecs']->table('goods_collect')." where goods_id={$good_id} AND user_id={$userid}";
		$del=$GLOBALS['db']->query($sql);
		if($del){
			$rs=array('result'=>'1','info'=>'取消成功');
			exit($json->json_encode_ex($rs));
		}
		$rs=array('result'=>'0','info'=>'取消失败');
		exit($json->json_encode_ex($rs));
	}else{
		
		$sql="insert INTO ".$GLOBALS['ecs']->table('goods_collect')."(user_id,goods_id,add_time) values ({$userid},{$good_id},{$temtime})";
		$one=$GLOBALS['db']->query($sql);
		$rs=array('result'=>'1','info'=>'收藏成功');
		exit($json->json_encode_ex($rs));
	}
}
//获取所有三级分类
elseif($action=='cate_list'){
	$list=all_cate_list();
	$rs=array('result'=>'1','info'=>'成功','data_list'=>$list);
	exit($json->json_encode_ex($rs));
}

elseif($action=='get_cate_list'){
	$cid=$_REQUEST['cid'];
	$list=all_cate_listById($cid);
	$rs=array('result'=>'1','info'=>'成功','data_list'=>$list);
	exit($json->json_encode_ex($rs));
}
/*
 * 店铺商品收藏
 */
elseif ($action=="goods_collect_list"){
	$userid=!empty($userinfo['userid']) ? $userinfo['userid'] : "";
	$sql="select  group_concat(goods_id) from ".$GLOBALS['ecs']->table('goods_collect')." where user_id={$userid}";
	$ids=$GLOBALS['db']->getOne($sql);
	$page=!empty($_REQUEST['page']) ? $_REQUEST['page'] : '0';
	$size =10;
	$begin = $page*$size;
	$limit = " LIMIT $begin,$size";
	$list=array();
	if(!empty($ids)){
		$where="where goods_id in ( {$ids} )";
		$file="goods_id,goods_name,shop_price,is_designer,market_price,sales_sum,original_img";
		$sql="select  distinct(goods_id),{$file} from ".$GLOBALS['ecs']->table('goods')." {$where} {$limit}";
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
		
		$sql="SELECT count(goods_id) as count from ".$GLOBALS['ecs']->table('goods')."{$where}";
		$goods_count=$GLOBALS['db']->getOne($sql);
		$allpage=ceil($goods_count/$size);
		if(!empty($list)){
			$rs=array(
					'result'=>'1',
					'info'=>'请求成功',
					'goods_list'=>$list,
					'page'=>$page,
					'size'=>$size,
					'count'=>$allpage
			);
		}else{
			$rs=array(
					'result'=>'1',
					'info'=>'无数据',
					'goods_list'=>$list,
					'page'=>$page,
					'size'=>$size,
					'count'=>$allpage
			);
		}
		exit($json->json_encode_ex($rs));
	}else{
		$rs=array(
				'result'=>'1',
				'info'=>'无数据',
				'goods_list'=>array(),
		);
		exit($json->json_encode_ex($rs));
	}
}


elseif($action=="comment_list"){
	
	$page=!empty($_REQUEST['page']) ? $_REQUEST['page'] : 0 ;
	$size =10;
	$begin = $page*$size;
	$limit = " LIMIT $begin,$size";
	$goods_id=$_REQUEST['goods_id'];
	
	if(empty($goods_id)){
		$results = array(
				'result' => 0,
				'info' =>'缺少参数',	//	商品数据
		);
	  exit($json->json_encode_ex($results));
	}
	
	$sql="SELECT username,content,spec_key_name,add_time,user_id,img,deliver_rank,goods_rank,service_rank from ".$GLOBALS['ecs']->table('comment')." where goods_id={$goods_id} and is_show =1 {$limit}";
	$comments=$GLOBALS['db']->getAll($sql);

	$sql = "SELECT COUNT(goods_id) FROM " .$GLOBALS['ecs']->table('comment')." WHERE  goods_id={$goods_id} and is_show =1 ";
	$goods_count = $GLOBALS['db']->getOne($sql);
	
	$allpage=ceil($goods_count/$size);
	$comm=array();
	if(!empty($comments)){
		foreach ($comments as $key=>$val){
			$temp['username']=$val['username'];
			$temp['content']=$val['content'];
			$temp['add_time']=$val['add_time'];
			$temp['spec_key_name']=$val['spec_key_name'];
			$temp['service_rank']=$val['service_rank'];
			$temp['goods_rank']=$val['goods_rank'];
			$temp['deliver_rank']=$val['deliver_rank'];
			$head_pic=user_head_pic($val['user_id']);
			$temp['head_pic']=!empty($head_pic) ? IMG_HOST.$head_pic : '';
			if(!empty($val['img'])){
				$arr=unserialize($val['img']);
				if(!empty($arr) && is_array($arr)){
					foreach ($arr as $key =>$val){
						if(!empty($val)){
							if(strpos($val,'yilitong.com') !== false){
								$arr[$key]=$val;
							}else{
								$arr[$key]=IMG_HOST.$val;
							}
							
						}
					}
					$temp['img']=$arr;
				}else{
					$temp['img']=array();
				}
			}else{
				$temp['img']=array();
			}
			$comm[]=$temp;
		}
	}
 
	if(!empty($comm)){
		$results = array(
				'result' => 1,
				'info'=>'请求成功',
				'comments' =>$comm,	//	商品数据
				'page' => $page,	//	当前页码
				'count' => $allpage,	//	总页数
				'size' => $size	//	每页取得商品数据条数
		);
	}else{
		$results = array(
				'result' => 1,
				'info'=>'无数据',
				'comments' =>$comm,	//	商品数据
				'page' => $page,	//	当前页码
				'count' => $allpage,	//	总页数
				'size' => $size	//	每页取得商品数据条数
		);
	}
	exit($json->json_encode_ex($results));
}
elseif($action=="download"){
	
	$extension_id =  $_REQUEST['extension_id']; //推荐人ID
	$add = array();
	
	$add['download_ip'] = $_SERVER["REMOTE_ADDR"];
	$add['add_time'] = time();
	$add['system'] = 'Android';
	 
	// if($GLOBALS['db']->getOne("select count(*) from" .$GLOBALS['ecs']->table('extension').
	//" where download_ip = ") == 0){
		
		 //检测当前ID是否已下载过
		$add['extension_id'] = $extension_id;
	 $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('extension'), $add, 'INSERT');

	 //}
}
// 公告信息
elseif($action=="notice_list"){
	
	$sql = "SELECT article_id,title FROM " .$GLOBALS['ecs']->table('article')." WHERE  is_open='1' and cat_id ='4' ORDER BY `cat_id` LIMIT 5";
	$list = $GLOBALS['db']->getAll($sql);
				
	exit($json->json_encode_ex(['result'=>'1','info'=>$list]));
}

/***
 * 获取商品评论信息
 */
function get_comment($goods_id){
	$sql="SELECT username,content,add_time,user_id,img from ".$GLOBALS['ecs']->table('comment')." where goods_id={$goods_id} limit 10";
	return $GLOBALS['db']->getAll($sql);
}

function all_cate_list(){
	$rs=array();
	//$data = read_static_cache('cat_list');
	if ($data == false)
	{
		$sql="select id,mobile_name,parent_id from ".$GLOBALS['ecs']->table('goods_category')." where parent_id=0 AND is_show=1 order by sort_order asc";
		$list=$GLOBALS['db']->getAll($sql);
		if(!empty($list)){
			foreach ($list as $key => $val){
				$temp['mobile_name']=$val['mobile_name'];
				//$temp['parent_id']=$val['parent_id'];
				$temp['parent_id']=$val['parent_id'];

				$list2=get_cates($val['id']);
				$temp['data_list']=$list2;
				if(!empty($list2)){
					foreach ($list2 as $key2=>$val2){	
			//			$temp['data_list'][$key2]['id']=$val2['c_id'];
						$temp['data_list'][$key2]['mobile_name']=$val2['mobile_name'];
						//$temp['data_list'][$key2]['parent_id']=$val2['parent_id'];
						$temp['data_list'][$key2]['parent_id']=$val2['parent_id'];
					}
				}
				$rs[]=$temp;
			}
		}
	//	write_static_cache('cat_list', $rs);
	}else{
		$rs=$data;
	}
	return $rs;
}

//获取一，二，三级所有分类列表
 function all_cate_listById($cid){
				$list2=get_cates($cid);
				if(!empty($list2)){
					foreach ($list2 as $key2=>$val2){
						$temp[$key2]['c_id']=$val2['c_id'];
						$temp[$key2]['mobile_name']=$val2['mobile_name'];
						$temp[$key2]['parent_id']=$val2['parent_id'];
						$temp[$key2]['image']=IMG_HOST.$val2['image'];
					}
				}
			
	return $temp;
}

function get_Ads1($ad_type,$type=""){
	$stemtime=time();
	if($type=="1"){
		$where =" AND A.start_time>= {$stemtime} AND A.end_time<={$stemtime}";
		$sql="SELECT A.ad_name,A.ad_link,A.ad_code,A.act_id,A.cate_id FROM ".$GLOBALS['ecs']->table('ad_position')." as P left join "
				.$GLOBALS['ecs']->table('ad').	" as A ON P.position_id=A.pid where A.enabled=1 AND P.position_id={$ad_type} AND P.is_open=1";
		$center_ad=$GLOBALS['db']->getRow($sql);

		$center_ads=array();
		if(!empty($center_ad)){
			//$imgurl=IMG_HOST."/data/adimg/";
			$center_ads['image']=IMG_HOST.$center_ad['ad_code'];
			$center_ads['ad_name']=$center_ad['ad_name'];
			$center_ads['goods_id']="";
			$center_ads['ad_type']="";
			$center_ads['ad_link']="";
			$center_ads['minus']='';
			$center_ads['cid']=!empty($center_ad['cate_id']) ? $center_ad['cate_id'] : "";
			$center_ads['act_id']=$center_ad['act_id'];

			if(is_numeric($center_ad['ad_link'])){
				$center_ads['goods_id']=$center_ad['ad_link'];
			}elseif(strpos($center_ad['ad_link'],"##") != false){
				$center_ads['ad_type']=substr($val['ad_link'],2);
			}elseif($center_ad['ad_link']=="minus") {
				$center_ads['minus']='1';
			}else{
				if($center_ad['ad_link']=="javascript:void();"){
					$center_ads['ad_link']="";
				}else{
					$center_ads['ad_link']=$center_ad['ad_link'];
				}
			}
			/* //兼容问题处理,
			 if(empty($center_ads['goods_id']) && empty($center_ads['ad_link']) && empty($center_ads['cid']) && !empty($center_ads['act_id']) && $center_ads['ad_type']=='3'){
			$center_ads['ad_link']="http://yilitong.com/Mobile/Index/index";
			}	 */
		}
		return $center_ads;
	}else{
		$where =" AND A.start_time>= {$stemtime} AND A.end_time<={$stemtime}";
		$sql="SELECT A.ad_name,A.ad_link,A.ad_code,A.act_id,A.cate_id FROM ".$GLOBALS['ecs']->table('ad_position')." as P left join "
				.$GLOBALS['ecs']->table('ad').	" as A ON P.position_id=A.pid where A.enabled=1 AND P.position_id={$ad_type} AND P.is_open=1 order by A.orderby DESC";
		$ads=$GLOBALS['db']->getAll($sql);
		$app_ads2=array();
		if(!empty($ads)){
			foreach ($ads as $val){
				$ad_temp2['image']=IMG_HOST.$val['ad_code'];
				$ad_temp2['ad_name']=$val['ad_name'];
				$ad_temp2['goods_id']="";
				$ad_temp2['ad_type']="";
				$ad_temp2['ad_link']="";
				$ad_temp2['minus']='';
				$ad_temp2['act_id']=$val['act_id'];
				$ad_temp2['cid']=!empty($val['cate_id']) ? $val['cate_id'] : "";
				if( is_numeric($val['ad_link'])){
					$ad_temp2['goods_id']=$val['ad_link'];
				}elseif(strpos($val['ad_link'],"##") !== false){
					$ad_temp2['ad_type']=substr($val['ad_link'],2);
				}elseif($val['ad_link']=="minus") {
					$ad_temp2['minus']='1';
				}else{
					if($val['ad_link']=="javascript:void();"){
						$ad_temp2['ad_link']="";
					}else{
						$ad_temp2['ad_link']=$val['ad_link'];
					}
				}

				/* //兼容问题处理,
				 if(empty($val['goods_id']) && empty($ad_temp2['ad_link']) && empty($ad_temp2['cid']) && !empty($ad_temp2['act_id']) && $ad_temp2['ad_type']=='3'){
				$ad_temp2['ad_link']="http://yilitong.com/Mobile/Index/index";
				} */
				$app_ads2[]=$ad_temp2;
			}
		}
		return $app_ads2;
	}
}

//获取广告列表
function get_Ads($ad_type){
	$stemtime=time();
		$where =" AND A.start_time<= {$stemtime} AND A.end_time>={$stemtime}";
		$sql="SELECT A.media_type,A.switch_time,A.ad_name,A.ad_link,A.ad_code as image FROM ".$GLOBALS['ecs']->table('ad_position')." as P left join "
				.$GLOBALS['ecs']->table('ad').	" as A ON P.position_id=A.pid where A.enabled=1 AND P.position_id={$ad_type} AND P.is_open=1 {$where} order by A.orderby DESC";
		$center_ad=$GLOBALS['db']->getAll($sql);
		foreach ($center_ad as $key =>$v){
			$center_ad[$key]['image']=IMG_HOST.$v['image'];
			if ($center_ad[$key]['switch_time']<3 ||$center_ad[$key]['switch_time']>10 ) {
				$center_ad[$key]['switch_time']= '3';
			}
		}
		return $center_ad;
}

//获取首页品牌推荐
function get_recommend_brand(){
	$stemtime=time();
	$where =" AND start_time<= {$stemtime} AND end_time>={$stemtime}";
	$sql = "select * FROM ".$GLOBALS['ecs']->table('recommend_brand')." where is_on_sale = 1 {$where} order by brand_sort desc";
	$list=$GLOBALS['db']->getAll($sql);
	$brand_list=array();
	foreach ($list as $k =>$v){
		$image[0]['image']=IMG_HOST.$v['image1'];
		$image[0]['media_type']=$v['type1'];
		$image[0]['ad_link']=$v['link1'];
		$image[1]['image']=IMG_HOST.$v['image2'];
		$image[1]['media_type']=$v['type2'];
		$image[1]['ad_link']=$v['link2'];
		$image[2]['image']=IMG_HOST.$v['image3'];
		$image[2]['media_type']=$v['type3'];
		$image[2]['ad_link']=$v['link3'];
		$image[3]['image']=IMG_HOST.$v['image4'];
		$image[3]['media_type']=$v['type4'];
		$image[3]['ad_link']=$v['link4'];
		$sql = "select goods_id,goods_name,goods_thumb,goods_price FROM ".$GLOBALS['ecs']->table('recommend_goods')." where is_on_sale = 1 and brand_id={$v['id']} order by sort desc limit 8";
		$goods_list=$GLOBALS['db']->getAll($sql);
		foreach ($goods_list as $key =>$value){
			$goods_list[$key]['goods_thumb']=IMG_HOST.$value['goods_thumb'];
		}
		$brand_list[$k]['brand_name']=$v['brand_name'];
		$brand_list[$k]['image_list']=$image;
		$brand_list[$k]['goods_list']=$goods_list;
	}
	
	return $brand_list;
}



//获取活动，列表
function get_activity_goods($act_id,$count){
	$limit=" limit $count";
	$sql="SELECT  GROUP_CONCAT(B.goods_id) as ids FROM ".$GLOBALS['ecs']->table('activity_cate')." as A left join "
			.$GLOBALS['ecs']->table('activity_goods').	" as B  ON A.id=B.act_id where A.id={$act_id} AND A.is_display=1 ";
	$ids=$GLOBALS['db']->getOne($sql);
	
	$list=array();
	if(!empty($ids)){
		$sql="SELECT goods_name,goods_id,original_img,goods_thumb as image,market_price,shop_price as price FROM ".$GLOBALS['ecs']->table('goods')." where examine=1 AND is_on_sale=1 AND goods_id in ( {$ids} ) $limit";
		$rs=$GLOBALS['db']->getAll($sql);
		if(!empty($rs)){
			foreach ($rs as $key =>$val){
				$list[$key]=$val;
				$list[$key]['image']=!empty($val['image']) ? IMG_HOST.$val['image'] : "";
				$list[$key]['original_img'] = "";
			}
		}
	}
    return $list;
}

//获取广告
function get_cate_Ads($ad_type){
	$stemtime=time();
	$where =" AND A.start_time>= {$stemtime} AND A.end_time<={$stemtime}";
	$sql="SELECT A.ad_id,A.ad_name,A.ad_link,A.ad_code,A.cate_id FROM ".$GLOBALS['ecs']->table('ad_position')." as P left join "
			.$GLOBALS['ecs']->table('ad').	" as A ON P.position_id=A.pid where A.enabled=1 AND P.position_id={$ad_type} AND P.is_open=1 order by A.orderby DESC";
	$ads=$GLOBALS['db']->getAll($sql);
	
	$app_ads2=array();
	if(!empty($ads)){
		foreach ($ads as $val){
			$ad_temp2['image']=IMG_HOST.$val['ad_code'];
			$ad_temp2['catename']=$val['ad_name'];
			$ad_temp2['cid']=!empty($val['cate_id']) ? $val['cate_id'] : "";
			
			$ad_temp2['ad_type']="";
			$ad_temp2['ad_link']="";
			if(strpos($val['ad_link'],"##") !== false){
				$ad_temp2['ad_type']=substr($val['ad_link'],2);
			}else{
				if($val['ad_link']=="javascript:void();"){
					$ad_temp2['ad_link']="";
				}else{
					$ad_temp2['ad_link']=$val['ad_link'];
				}
			}
			if($val['ad_id']=="87"){
				$ad_temp2['cid']="0";
			}
			$app_ads2[]=$ad_temp2;
		}
	}
	return $app_ads2;
}



function get_goods_id($good_id){
	$file="goods_id,prom_id,commission_price,is_designer,prom_type,cat_id,goods_sn,goods_name,brand_id,store_count,market_price,shop_price,supplier_id,add_time,goods_type,spec_type,goods_content,goods_remark,attribute,original_img,examine,is_on_sale,sales_sum,prom_id";
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
	$sql="select supplier_id,user_id,supplier_name,company_name,business_sphere,introduction,logo,supplier_money from ".$GLOBALS['ecs']->table('supplier')." where supplier_id={$id}";
	return $GLOBALS['db']->getRow($sql);
} 
//获取默认属性
function get_goods_defaultpro($good_id){
	$sql="select G.goods_id,G.key,G.key_name,G.price,G.store_count from ".$GLOBALS['ecs']->table('goods_price')." as G where G.goods_id={$good_id} limit 1";
	return $GLOBALS['db']->getRow($sql);
}
//获取商品属性
function get_goods_pro($good_id){
	$sql="select GROUP_CONCAT(G.key) as ids from ".$GLOBALS['ecs']->table('goods_price')." as G where G.goods_id={$good_id} group by G.goods_id";
	$ids=$GLOBALS['db']->getOne($sql);
	
	if(empty($ids)){
		return false;
	}
	$str='';
	if(!empty($ids)){
		$ids=str_replace('_',',',$ids);
		$arr=explode(',',$ids);
		$arr =array_unique($arr);
		$str = implode(',',$arr);
	}
	//获取规格属性分组
	$sql="select S.name,I.spec_id from ".$GLOBALS['ecs']->table('spec')." as S left join ".$GLOBALS['ecs']->table('spec_item')." as I on I.spec_id=S.id where I.id in($str) group by I.spec_id";
	$specgroup=$GLOBALS['db']->getAll($sql);
	
	//获取所以属性
	$sql="select * from ".$GLOBALS['ecs']->table('spec_item')." where id in($str) ";
	$speclist=$GLOBALS['db']->getAll($sql);
	$spec=array();
	if(!empty($speclist)){
			foreach ($specgroup as $val){
				 $spec[$val['spec_id']]['name']=$val['name'];
				 $spec[$val['spec_id']]['spec_id']=$val['spec_id'];
				foreach ($speclist as $key => $val2){
					if($val2['spec_id']==$val['spec_id']){
						$spec[$val['spec_id']]['value'][]=array(
								's_id'=>$val2['id'],
								'item'=>$val2['item']
						);
					}
				}
			}
	}
	
	$temp=array();
	 $i=0;
	if(!empty($spec)){
		foreach ($spec as $key =>$val){
			$temp[$i]['name']=$val['name'];
			$temp[$i]['spec_id']=$val['spec_id'];
			foreach ($val['value'] as $val2){
				$temp[$i]['value'][]=array('s_id'=>$val2['s_id'],'item'=>$val2['item']);
			}
			++$i;
		 }
	}  
	return $temp;
}


//获取商品一级分类
function get_cate_list(){
  $sql="SELECT id,name,mobile_name,image from ".$GLOBALS['ecs']->table('goods_category')." where parent_id=0 AND is_show=1 order by sort_order limit 0,8";
  return $GLOBALS['db']->getAll($sql);
}

//获取商品一级分类
function get_cates($id){
	$sql="SELECT id as c_id,mobile_name,image,parent_id from ".$GLOBALS['ecs']->table('goods_category')." where parent_id={$id} AND is_show=1 order by sort_order";
	$list= $GLOBALS['db']->getAll($sql);
	if(!empty($list)){
		foreach ($list as $key =>$val){
			$list[$key]=$val;
			if(!empty($val['image'])){
				$list[$key]['image']=IMG_HOST.$val['image'];
			}
		}
	}
	return $list;
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

function sql_injection($content)
{
	if (!get_magic_quotes_gpc()) {
		if (is_array($content)) {
			foreach ($content as $key=>$value) {
				$content[$key] = addslashes($value);
			}
		} else {
			addslashes($content);
		}
	}
	return $content;
}
//获取用户头像
function user_head_pic($user_id){
	$sql="SELECT head_pic from ".$GLOBALS['ecs']->table('users')." where user_id=$user_id";
	return $GLOBALS['db']->getOne($sql);
}

function get_img_thumb_url($content="",$suffix="http://")
{
	$pregRule =  $pregRule = "/<[img|IMG].*?src=[\'|\"](.*?(?:[\.jpg|\.jpeg|\.png|\.gif|\.bmp]))[\'|\"].*?[\/]?>/";
	$content = preg_replace($pregRule, '<img src="'.$suffix.'${1}" style="max-width:100%">', $content);
	return $content;
}


?>