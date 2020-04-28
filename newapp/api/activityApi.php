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
//秒杀、折扣活动列表
if($action=="kill_activity"){
	$act_id=$_REQUEST['act_id'];
	$time = time();
	$page=!empty($_REQUEST['page']) ? $_REQUEST['page'] : 0;
	$size = 10;
	$begin = $page*$size;
	$limit = " LIMIT $begin,$size";
	
	$sql="SELECT id as act_id,title,discount,description as act_description,start_time,end_time,buy_type,app_img FROM ".$GLOBALS['ecs']->table('discount_buy')." where id='{$act_id}' and start_time < {$time} and end_time >{$time} and is_start = 1";
	$info=$GLOBALS['db']->getRow($sql);
	if ($info['app_img']) {
		$info['app_img'] = IMG_HOST.$info['app_img'];
	}
	if ($info) {
		$info['now_time']=$time;
		$sql=" update ".$GLOBALS['ecs']->table('discount_buy')." set browse_num=browse_num+1 where id='{$act_id}'";
		$GLOBALS['db']->query($sql);
		$sql="SELECT goods_id,goods_name,goods_thumb,activity_market_price,activity_price,activity_count from " .$GLOBALS['ecs']->table('discount_goods')." where  discount_id={$act_id}  ORDER BY sort DESC  {$limit}";
		$goods_list=$GLOBALS['db']->getAll($sql);
		foreach ($goods_list as $key =>$val){
			if ($val['goods_thumb']) {
				$goods_list[$key]['goods_thumb'] = IMG_HOST.$val['goods_thumb'];
			}
		}
		$sql="select count(*) from " .$GLOBALS['ecs']->table('discount_goods')." where  discount_id={$act_id} ";
		$count=$db->getOne($sql);
		$rs=array('result'=>'1','info'=>'请求成功','act_info'=>$info,'goods_list'=>$goods_list,'page'=>$page,'size'=>$size,'count'=>$count);
		exit($json->json_encode_ex($rs));
	}else{
		$rs=array('result'=>'0','info'=>'活动不存在','act_info'=>array(),'goods_list'=>array(),'page'=>'','size'=>'','count'=>'');
		exit($json->json_encode_ex($rs));
	}
	
}
//送礼攻略列表
if($action=="raiders_list"){
	
	$act_id=$_REQUEST['act_id'];
	
	$sql="SELECT article_id,cat_id,title,description,click,thumb FROM ".$GLOBALS['ecs']->table('article')." where cat_id='{$act_id}' ";
	$info_list=$GLOBALS['db']->getAll($sql);
	
	$sql="SELECT * FROM ".$GLOBALS['ecs']->table('article_cat')." where parent_id='96' ";
	$act_list=$GLOBALS['db']->getAll($sql);
	
	if($info_list){
		$rs=array('result'=>'1','info'=>'请求成功','act_list'=>$act_list,'info_list'=>$info_list);
		exit($json->json_encode_ex($rs));
		
	}else{
		$rs=array('result'=>'0','info'=>'暂无推荐','act_list'=>'','info_list'=>'');
		exit($json->json_encode_ex($rs));
	}
	
	
}
//送礼攻略详情
if($action=="raiders_detail"){
	
	$act_id=$_REQUEST['act_id'];
	
	$sql="SELECT article_id,cat_id,title,author,content,add_time,click,thumb,goods_img,goods_id FROM ".$GLOBALS['ecs']->table('article')." where article_id='{$act_id}' ";
	$info=$GLOBALS['db']->getRow($sql);
	if($info){
		$rs=array('result'=>'1','info'=>'请求成功','act_info'=>$info);
		exit($json->json_encode_ex($rs));
		
	}else{
		$rs=array('result'=>'0','info'=>'文章已被删除','act_info'=>'');
		exit($json->json_encode_ex($rs));
	}
	
	
	
}

// 公告信息
elseif($action=="notice_list"){
	
	$page=!empty($_REQUEST['page']) ? $_REQUEST['page'] : 0;
	$size =10;
	$begin = $page*$size;
	$limit = " LIMIT $begin,$size";
	$sql = "SELECT article_id,title,description as explains,publish_time,thumb FROM " .$GLOBALS['ecs']->table('article')." WHERE  is_open='1' and cat_id ='4' ORDER BY `article_id` desc {$limit}";
	
	$list = $GLOBALS['db']->getAll($sql);
	
	if(!empty($list)){
		foreach ($list as $key =>$val){
			
			if(!empty($val['thumb'])){
				$list[$key]['thumb']=IMG_HOST.$val['thumb'];
			}else{
				$list[$key]['thumb']='';
			}
		}
	}
				
	exit($json->json_encode_ex(['result'=>'1','info'=>$list]));
}
// 公告信息详情
elseif($action=="notice_info"){
	
	$article_id=!empty($_REQUEST['article_id']) ? $_REQUEST['article_id'] : 0;
	$sql = "SELECT * FROM " .$GLOBALS['ecs']->table('article')." WHERE  article_id = {$article_id} and is_open='1' and cat_id ='4'";
	$info = $GLOBALS['db']->getRow($sql);
	
	if(!empty($info['content'])){		
		$pregRule = '/<img src=\"\/public\//';
		$rep_content = preg_replace_callback($pregRule, function(){
			return '<img src="'.IMG_HOST."public/";
		}, $info['content']);
		$info['content'] = "<style>p{ margin:0px; padding:0px;}</style>";
		$info['content'] .=$rep_content;
	}
				
	exit($json->json_encode_ex(['result'=>'1','info'=>$info]));
}
// 个人信息
elseif($action=="information_list"){
	
	$userId = $userinfo['userid'];
	$page=!empty($_REQUEST['page']) ? $_REQUEST['page'] : 0;
	$size =10;
	$begin = $page*$size;
	$limit = " LIMIT $begin,$size";
	$sql = "SELECT log_id,content,add_time,is_read FROM " .$GLOBALS['ecs']->table('system_information')." WHERE  user_id = {$userId} ORDER BY `log_id` {$limit}";
	$list = $GLOBALS['db']->getAll($sql);
				
	exit($json->json_encode_ex(['result'=>'1','info'=>$list]));
}
// 阅读个人信息
elseif($action=="information_info"){
	
	$userId = $userinfo['userid'];
	$log_id=!empty($_REQUEST['log_id']) ? $_REQUEST['log_id'] : 0;
	
	$sql="UPDATE ".$GLOBALS['ecs']->table('system_information')." SET is_read='1' where log_id = {$log_id} ";
	$res=$GLOBALS['db']->query($sql);
				
	exit($json->json_encode_ex(['result'=>'1','info'=>$res]));
}
