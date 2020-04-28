<?php
/**获取购物车的数量**/
$cart_num=get_CartsNum();

/***
 * 首页商品分类
 * 四模块 
 */
//母婴用品
//1 奶粉
$cateid1=1307;
$cateid2=1306;
$cateid3=1586;
$cateid4=1496;
$files="cat_id,cat_name,parent_id";
$catehead_1=get_cate_list($cateid1,$files,'10');

//纸尿裤
$catehead_2=get_cate_list($cateid2,$files,'10');
//宝宝用品
$catehead_3=get_cate_list($cateid3,$files,'10');
//宝宝食品
$catehead_4=get_cate_list($cateid4,$files,'10');

$cate_top=get_parent_list('0',$files,'6');

//美妆个护----------
//护肤 995
$m_cate1=get_cate_list('995',$files,'10');
//面膜 982
$m_cate2=get_cate_list('982',$files,'10');
//美发造型
$m_cate3=get_cate_list('988',$files,'10');

//彩妆908
$m_cate4=get_cate_list('1013',$files,'10');

//食品保健-----

//冲调饮品   //保健食品1024
$s_cate1=get_cate_list('1044',$files,'10');

//零食小吃 //膳食营养补充食品 1043
$s_cate2=get_cate_list('1043',$files,'10');
//美容瘦身 //美容养颜 5598
$s_cate3=get_cate_list('5598',$files,'10');
//营养保健 //家庭保健  5532
$s_cate4=get_cate_list('5532',$files,'10');


//生活家居-----

//洗发沐浴 //日用家纺592
$h_cate1=get_cate_list('592',$files,'10');
//口腔护理  //洗护清洁/纸/香薰 804
$h_cate2=get_cate_list('804',$files,'10');
//环境清洁 //家庭/个人清洁工具 2157
$h_cate3=get_cate_list('2157',$files,'10');
// 饰品首饰 5559
$h_cate4=get_cate_list('5559',$files,'10');
$smarty->assign('m_cate1', $m_cate1);
$smarty->assign('m_cate2', $m_cate2);
$smarty->assign('m_cate3', $m_cate3);
$smarty->assign('m_cate4', $m_cate4);


$smarty->assign('s_cate1', $s_cate1);
$smarty->assign('s_cate2', $s_cate2);
$smarty->assign('s_cate3', $s_cate2);
$smarty->assign('s_cate4', $s_cate4);


$smarty->assign('h_cate1', $h_cate1);
$smarty->assign('h_cate2', $h_cate2);
$smarty->assign('h_cate3', $h_cate3);
$smarty->assign('h_cate4', $h_cate4);

  




$cid="241";
$cid2="981";
$cid3="1962";
$cid4="1041";

//F1层 母婴专区
$f1=get_cate_list($cid,$files,'11');

//F2层  美妆个护
$f2=get_cate_list($cid2,$files,'11');

//F3层 生活家居
$f3=get_cate_list($cid3,$files,'11');

//F4层 食品保健
$f4=get_cate_list($cid4,$files,'11');

//	H5
$smarty->assign('f1', $f1);
$smarty->assign('f2', $f2);
$smarty->assign('f3', $f3);
$smarty->assign('f4', $f4);

if($catehead_1){
	$smarty->assign('catehead_1', $catehead_1);
}
if($catehead_2){
  $smarty->assign('catehead_2', $catehead_2);
}
if($catehead_3){
  $smarty->assign('catehead_3', $catehead_3);
}
if($catehead_4){
 $smarty->assign('catehead_4', $catehead_4);
}
if($cate_top){
 $smarty->assign('cate_top', $cate_top);
}

if(!empty($cart_num)){
  $smarty->assign('cart_num', $cart_num);
}

//广告小图片logo
$minlogo=get_ad_list2("20","img,url",'1');
if($minlogo){
//	$img=IMG_HOST."/data/adimg/".$minlogo[0]['img'];
	$img=IMG_HOST."/data/showimg/".$minlogo[0]['img'];
	$minlogo[0]['img']=$img;
	$smarty->assign("minlogo",$minlogo);
}
/*
 * 获取广告列表
*
*/
function get_ad_list2($aid,$files='',$limit='5',$order=''){
	$data = read_static_cache('ad_'.$aid);
	//var_dump($data);
	if ($data === false){
		$where=" 1=1 AND ad_type=".$aid;
		$order=empty($order) ? ' porder ' : $order;
		$files=empty($files) ? ' * ' :$files;
		$sql = "SELECT $files ".
				"FROM ".$GLOBALS['ecs']->table('ads') .
				" WHERE $where" .
				" ORDER BY $order" .
				" LIMIT $limit";
		$list = $GLOBALS['db']->getALL($sql);
		write_static_cache('ad_'.$aid, $list);
	}else{
		$list = $data;
	}

	return $list;
}

/*****
 * 获取分类信息
 * 
 */
function get_cate_list($cateid,$files='',$limit=''){
	$data = read_static_cache('floor_cata_'.$cateid);
	if ($data === false){
		
		$limit=empty($limit) ? ' 15  ' : $limit;
		$order=empty($order) ? ' cat_id  ' : $order;
		$files=empty($files) ? ' * ' :$files;
		$cateids=get_children($cateid);
		if($cateids){
			$sql = "SELECT $files ".
					"FROM ".$GLOBALS['ecs']->table('category') .
					" g where $cateids" .
					" ORDER BY $order" .
					" LIMIT $limit";
		}
		
		$list = $GLOBALS['db']->getALL($sql);
		write_static_cache('floor_cata_'.$cateid, $list);
	}
	else{
		$list = $data;
	}
	
	return $list;
}

/*****
 * 获取 分类 parent_id=0
*
*/
function get_parent_list($parent_id,$files='',$limit=''){
	$data = read_static_cache('big_cata');
	if ($data === false){
		$limit=empty($limit) ? ' 15  ' : $limit;
		$order=empty($order) ? ' cat_id  ' : $order;
		$files=empty($files) ? ' * ' :$files;
		
		$sql = "SELECT $files ".
				"FROM ".$GLOBALS['ecs']->table('category') .
				" g where parent_id=$parent_id" .
				" ORDER BY $order" .
				" LIMIT $limit";
		
		$list = $GLOBALS['db']->getALL($sql);
		write_static_cache('big_cata', $list);
	}
	else{
		$list = $data;
	}
	
	
	return $list;
}

?>