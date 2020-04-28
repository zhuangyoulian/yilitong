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
//获取设计师后台列表
if ($action=="designer_background_list"){
	if(!empty($userinfo['userid'])){
		$user_id = $userinfo['userid'];
		$sql="select logo,supplier_name,supplier_id from ".$GLOBALS['ecs']->table('supplier')." where user_id={$user_id} AND is_designer = 1 ";
		$info = $GLOBALS['db']->getRow($sql);
	
		if ($info) {
			$sql = "SELECT COUNT(collect_id) FROM " .$GLOBALS['ecs']->table('supplier_collect') ." where supplier_id = ".$info['supplier_id'];
			$collect_count = $GLOBALS['db']->getOne($sql);
			$item = array();
			
			$item['supplier_id'] = $info['supplier_id'];
			$item['supplier_name'] = $info['supplier_name'];
			if ($info['logo']) {
				$item['head_pic'] = IMG_HOST.$info['logo'];
			}else{
				$item['head_pic'] = "";
			}
			$item['fans_count'] = $collect_count;
			$list = array();
			$list[0]['image'] = IMG_HOST."/public/images/my_production@2x.png";
			$list[0]['value'] = '我的作品';
			$list[1]['image'] = IMG_HOST."/public/images/sales_work@2x.png";
			$list[1]['value'] = '在售作品';
			$list[2]['image'] = IMG_HOST."/public/images/clipboard-w--tick@2x.png";
			$list[2]['value'] = '我的订单';
			$list[3]['image'] = IMG_HOST."/public/images/return@2x.png";
			$list[3]['value'] = '退货单';
// 			$list[4]['image'] = IMG_HOST."/public/images/income@2x.png";
// 			$list[4]['value'] = '余额';
// 			$list[5]['image'] = IMG_HOST."/public/images/accumulated-income_mine@2x.png";
// 			$list[5]['value'] = '累计收益';
		
			$item['items'] = $list;
			$results=array(
				'result' => '1',
				'info' => '请求成功',
				'list' => $item
			);
			
		}else{
			$results=array(
				'result' => '0',
				'info' => '设计师不存在'
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
//设计师个人信息
elseif ($action=="designer_info"){
	if(!empty($userinfo['userid'])){
		$user_id = $userinfo['userid'];
	}else{
		$user_id = $_REQUEST['user_id'];
	}
	
	if ($user_id) {
		//users表{ nickname:昵称|qq：qq号|head_pic:头像 }
		$file="S.supplier_name as nickname,U.sex,U.mobile,U.head_pic,";
		//supplier表{ guimo：微信号|qq：qq号 |company_name：省-市-区|address:详细地址|company_type:设计师类型|introduction:个人简介 }
		$file.="S.organization_code_electronic,S.guimo as wechat,S.qq,S.business_licence_number as id_card,S.contacts_name as true_name,S.contacts_name as true_name,S.general_taxpayer,S.bank_licence_electronic,S.proxy,S.reading_protocol,S.supplier_id,S.guimo,S.qq,S.company_name,S.address,S.company_type,S.introduction";
		$sql="select {$file} from ".$GLOBALS['ecs']->table('users')." as U left join ".$GLOBALS['ecs']->table('supplier')." as S ON U.user_id=S.user_id where U.user_id={$user_id} AND S.is_designer = 1 ";
		$info = $GLOBALS['db']->getRow($sql);
		if (!$info) {
			$results=array(
					'result' => '0',
					'info' => '设计师不存在'
			);
			exit($json->json_encode_ex($results));
		}
		
		$sql = "SELECT COUNT(collect_id) FROM " .$GLOBALS['ecs']->table('supplier_collect') ."where supplier_id = ".$info['supplier_id'];
		$collect_count = $GLOBALS['db']->getOne($sql);
		
		$provinces = explode('-', $info['company_name']);
		switch ($info['sex']) {
			case '0':
				$info['gender'] = "保密";
				break;
			case '1':
				$info['gender'] = "男";
				break;
			case '2':
				$info['gender'] = "女";
				break;
		}
		if ($info['reading_protocol']){
			$info['reading_protocol']=IMG_HOST.$info['reading_protocol'];
		}
		if ($info) {
			$rs=array(
					'nickname' => $info['nickname'],	//用户昵称
					'truename' => $info['true_name'],	//用户昵称
					'sex' => $info['gender'],	 //性别
					'mobile' => $info['mobile'],	 //手机
					'head_pic' => IMG_HOST.$info['head_pic'],	 //头像
					'wechat' => $info['guimo'],	 //微信号
					'qq' => $info['qq'],	 //qq号
					'province' => $provinces[0],	 //省
					'city' => $provinces[1],	 //市
					'area' => $provinces[2],	 //区
					'address' => $info['address'],	 //详细地址
					'type' => $info['company_type'],	 //设计师类型
					'introduction' => $info['introduction'],	 //个人简介
					'fans' => $collect_count,	 //粉丝数
					'id_card_positive'=>IMG_HOST.$info['organization_code_electronic'],
					'id_card_opposite'=>IMG_HOST.$info['general_taxpayer'],
					'material1'=>IMG_HOST.$info['bank_licence_electronic'],
					'material2'=>IMG_HOST.$info['proxy'],
					'education'=>$info['reading_protocol'],
					'id_card' => $info['id_card'],	//身份证
						
			);
			$results=array(
					'result' => '1',     //成功状态 1
					'info' => '请求成功',	 //提示
					'designer_info'=>$rs,
					
			);
			exit($json->json_encode_ex($results));
			
		}else{
			
			$results=array(
					'result' => '0',
					'info' => '设计师不存在'
			);
			exit($json->json_encode_ex($results));
		}
	}else{
		$results=array(
				'result' => '0',
				'info' => '缺少参数userId'
		);
		exit($json->json_encode_ex($results));
	}

}
//检查是否是设计师,回传设计师状态 0未申请  1 审核通过  2审核不通过 -1注销设计师 3审核中
elseif($action=="is_designer"){
	if(!empty($userinfo['userid'])){
		$user_id = $userinfo['userid'];
	}
	if ($user_id) {
		$sql = "select is_designer,user_id from ".$GLOBALS['ecs']->table('users')." where user_id=".$user_id ;
		
		$status = $GLOBALS['db']->getRow($sql);
		if ($status['user_id']){
			if ((int)$status['is_designer'] == 2) {
				$results=array(
					'result' => '1',
					'info' => '请求成功',
					'status'=> (int)$status['is_designer'],
					'reason' => '资料有误,请上传真实信息'
				);
			}else{
				$results=array(
					'result' => '1',
					'info' => '请求成功',
					'status'=> (int)$status['is_designer'],
					'reason' => ''
				);
			}
			
		}else{
			$results=array(
					'result' => '0',
					'info' => '用户不存在',
					'status'=> (int)$status['is_designer']
			);
		}
		exit($json->json_encode_ex($results));
	}else{
		$results=array(
			'result' => '0',
			'info' => '缺少参数userId'
		);
		exit($json->json_encode_ex($results));
	}
	
}
//申请成为设计师
elseif ($action=="register"){
	
	if(!empty($userinfo['userid'])){
		$designer['user_id'] = $userinfo['userid'];
	}else{
		$results=array(
			'result' => '0',
			'info' => '用户未登录'
		);
		exit($json->json_encode_ex($results));
	}
	$designer['supplier_name'] = $_REQUEST['nick_name'];	//真实姓名
	$designer['contacts_name'] = $_REQUEST['true_name'];	//真实姓名
	$designer['business_licence_number'] = $_REQUEST['id_card'];	//身份证号
	$designer['guimo'] = $_REQUEST['wechat'];  //微信号
	$designer['qq'] = $_REQUEST['qq'];	//qq号
	$designer['company_name'] = $_REQUEST['province'] . "-" . $_REQUEST['city'] . "-" . $_REQUEST['area'];
	$designer['province'] = get_address_id($_REQUEST['province']);	//省
	$designer['city'] = get_address_id($_REQUEST['city']);	//市
	$designer['area'] = get_address_id($_REQUEST['area']);	//区
	$designer['address'] = $_REQUEST['address'];	//详细地址
	$designer['company_type'] = $_REQUEST['type'];	//设计师类型
	$designer['introduction'] = $_REQUEST['introduction'];	//个人简介
	$designer['organization_code_electronic'] = str_replace("http://www.yilitong.com/", '', $_REQUEST['id_card_positive']);	//身份证正面
	$designer['general_taxpayer'] =  str_replace("http://www.yilitong.com/", '', $_REQUEST['id_card_opposite']);	//身份证反面
	$designer['reading_protocol'] =  str_replace("http://www.yilitong.com/", '', $_REQUEST['education']);	//学历证明
	$designer['bank_licence_electronic'] = str_replace("http://www.yilitong.com/", '', $_REQUEST['material1']);	//相关材料1
	$designer['proxy'] =  str_replace("http://www.yilitong.com/", '', $_REQUEST['material2']);	//相关材料2
	$designer['is_designer'] = 1;	//是否设计师  0为否  1为是
	$designer['is_complete'] = 1;	//资料是否填写齐全  0为否  1为是
	$designer['add_time'] = time(); //注册时间

	$sql = "select is_designer,user_id,head_pic,mobile from ".$GLOBALS['ecs']->table('users')." where user_id=".$designer['user_id'] ;
	$status = $GLOBALS['db']->getRow($sql);
	
	$designer['contacts_phone'] = $status['mobile']; //注册时间
	
	$sql = "select supplier_id from ".$GLOBALS['ecs']->table('supplier')." where user_id = ".$designer['user_id'] ." and is_designer = 1";
	$supplier_id = $GLOBALS['db']->getOne($sql);
	if ($supplier_id) {
		$sql=" UPDATE " . $GLOBALS['ecs']->table('supplier') . " 
		set logo ='".$status['head_pic']."', status=0,  contacts_name = '".$designer['contacts_name']."',supplier_name = '".$designer['supplier_name']."',business_licence_number = '".$designer['business_licence_number']."',
		guimo = '".$designer['guimo']."',qq = '".$designer['qq']."',company_name = '".$designer['company_name']."', contacts_phone = '".$designer['contacts_phone']."',
		province = '".$designer['province']."',city = '".$designer['city']."',area = '".$designer['area']."',
		address = '".$designer['address']."',company_type = '".$designer['company_type']."',introduction = '".$designer['introduction']."',
		organization_code_electronic = '".$designer['organization_code_electronic']."',general_taxpayer = '".$designer['general_taxpayer']."',
		reading_protocol = '".$designer['reading_protocol']."',bank_licence_electronic = '".$designer['bank_licence_electronic']."',
		proxy = '".$designer['proxy']."',is_designer = '".$designer['is_designer']."',
		is_complete = '".$designer['is_complete']."',add_time = '".$designer['add_time']."'
		where supplier_id = {$supplier_id}" ;
	
		$success=$GLOBALS['db']->query($sql);
		$sql=" UPDATE " . $GLOBALS['ecs']->table('users') . " set  is_designer = 3 where user_id = " . $designer['user_id'];
		$success=$GLOBALS['db']->query($sql);
		if ($success) {
			$results=array(
				'result' => '1',
				'info' => '修改成功,进入审核期！'
			);
		}else{
			$results=array(
				'result' => '0',
				'info' => '更新失败'
			);
		}
	}
	elseif ($status) {
		if ($status['is_designer']== 0) {
			$data_field = " (logo,user_id,supplier_name,contacts_name,business_licence_number,guimo,qq,company_name,contacts_phone,province,city,area,address,
			company_type,introduction,organization_code_electronic,general_taxpayer,reading_protocol,
			bank_licence_electronic,proxy,is_designer,is_complete,add_time)"; //数据库字段
			
			$sql="insert INTO ".$GLOBALS['ecs']->table('supplier'). $data_field." values ('{$status['head_pic']}','{$designer['user_id']}','{$designer['supplier_name']}','{$designer['contacts_name']}',
			'{$designer['business_licence_number']}','{$designer['guimo']}','{$designer['qq']}','{$designer['company_name']}','{$designer['contacts_phone']}','{$designer['province']}',
			'{$designer['city']}','{$designer['area']}','{$designer['address']}','{$designer['company_type']}','{$designer['introduction']}',
			'{$designer['organization_code_electronic']}','{$designer['general_taxpayer']}','{$designer['reading_protocol']}',
			'{$designer['bank_licence_electronic']}','{$designer['proxy']}','{$designer['is_designer']}','{$designer['is_complete']}','{$designer['add_time']}')";
			//  /newapp/api/designer.php?act=register&token=Ul1VWkpRUFNfTR9ZUF5MUVtQVklWFBwCDDwBCh0&province=广东省&city=深圳市&area=龙岗区&true_name=蓝家鑫&id_card=440582199411013333&wechat=wx13410872872&qq=357099568&address=丹竹头岭背西3巷1号&type=高级设计师&introduction=个人简介&id_card_positive=id_card_positive.jpg&id_card_opposite=id_card_opposite.jpg&education=education.jpg&material1=material1.jpg&material2=material2.jpg
			//  示例
			$one=$GLOBALS['db']->query($sql);
			$sql=" UPDATE " . $GLOBALS['ecs']->table('users') . " set  is_designer = 3 where user_id = " . $designer['user_id'];
			$success=$GLOBALS['db']->query($sql);
			if ($one&&$success) {
				$results=array(
					'result' => '1',
					'info' => '申请设计师成功,进入审核期'
				);
			}else{
				$results=array(
					'result' => '0',
					'info' => '参数有误'
				);
			}
		}
		else{
			$results=array(
				'result' => '0',
				'info' => '请勿重复申请成为设计师'
			);
		}
	}else{
		$results=array(
			'result' => '0',
			'info' => '用户不存在'
		);
	}
	
	exit($json->json_encode_ex($results));
	
	
}

/**
 * 	设计师信息更新
 *	$_REQUEST['user_id']; 用户id
 *  $_REQUEST['field'];  需要修改的字段
 *  $_REQUEST['data'];   需要修改字段的值
 */
elseif ($action=="update"){

	if(!empty($userinfo['userid'])){
		$userid=$userinfo['userid'];
		$guimo = $_REQUEST['wechat'];
		$qq = $_REQUEST['qq'];
		$company_name = $_REQUEST['province'];
		$address = $_REQUEST['address'];
		$company_type = $_REQUEST['type'];
		$introduction = $_REQUEST['introduction'];
		$province = explode('-', $_REQUEST['province']);
		$pro=get_address_id($province[0]);
		$city=get_address_id($province[1]);
		$area=get_address_id($province[2]);
		
		$sql = "UPDATE " . $GLOBALS['ecs']->table('supplier') . "
				 set guimo = '{$guimo}', province = '{$pro}' , city = '{$city}' , area = '{$area}', introduction = '{$introduction}',
				 qq = '{$qq}', company_name = '{$company_name}' , address = '{$address}' , company_type = '{$company_type}'
		 where user_id=".$userid ." and is_designer =1";
	
		$success=$GLOBALS['db']->query($sql);
		if ($success) {
			$results=array(
					'result' => '1',
					'info' => '修改成功'
			);
		}else {
			$results=array(
					'result' => '0',
					'info' => '修改失败'
			);
		}
	}else{
		$results = array(
			'result' => 0,
			'info' => '请先登录！'
		);
	}
	exit($json->json_encode_ex($results));
}

/**
 * 获取设计师商品列表
 * page 提供页面请求设计师商品数据 默认为0
 * type 数据库字段查询,提供条件进行排序  （add_time 发布时间）（sort 后台设置的产品排序） （sales_sum 销量排序） 
 */
elseif ($action=="getGoodsList"){
	$page=!empty($_REQUEST['page']) ? $_REQUEST['page'] : 0 ;  //页码
	$size=!empty($_REQUEST['size']) ? $_REQUEST['size'] : 10;  //一页显示商品个数
	$type=!empty($_REQUEST['type']) ? $_REQUEST['type'] : add_time ;  //排序条件
//	$category = $_REQUEST['category'];	//商品分类 为空默认所有分类
//	$recommend = $_REQUEST['recommend'];	//设计师首页推荐  值为1推荐
	$arr = array('add_time','sort','sales_sum'); //允许排序的条件
	$isin = in_array($type,$arr);
	if($isin){
		if ($type == 'sort') {
			$type = 'g.'.$type .' asc'; //后台填写排序 排序又小到大排序
		}else{
			$type = 'g.'.$type .' desc'; //时间、销量 又大到小排序
		}
		/* 查询设计师商品 */
		$page=!empty($page) ? $page : 0;
		$begin = $page*$size;
		$limit = " LIMIT $begin,$size";
		$where="  g.is_on_sale = 1 and g.is_designer = 1 AND g.examine = 1 "; //上架   设计师   审核通过
// 		if ($category) {  //如果分类有值 插入条件查询
// 			$where = $where.' and g.cat_id = '.$category;
// 		}
// 		if ($recommend) { //如果推荐有值 插入条件查询
// 			$where = $where.' and g.is_recommend = '.$recommend;
// 		}
		$sql = "SELECT g.supplier_name,g.goods_id, g.goods_name, g.market_price, g.shop_price,g.original_img,is_designer".
				" FROM " .$GLOBALS['ecs']->table('goods'). " AS g ".
				" WHERE {$where} order by {$type}  {$limit}";
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
				$temp['shop_price']=$val['shop_price'];
				$rs[]=$temp;
			}
		}
		//搜索的总商品数量
		$sql = "SELECT COUNT(goods_id) FROM " .$GLOBALS['ecs']->table('goods').
		"  WHERE is_on_sale = 1 AND is_recommend = 1 AND is_designer = 1";
		$goods_count = $GLOBALS['db']->getOne($sql);
		
		$allpage=ceil($goods_count/$size);
		
		$results = array(
			'result' => 1,
			'goods_list' =>$rs,	//	商品数据
			'page' => $page,	//	当前页码
			'count' => $allpage,	//	总页数
			'size' => $size	//	每页取得商品数据条数
		);
	}else{
		$results = array(
			'result' => 0,
			'info' => 'type查询排序条件有误'
		);
		
	}
	exit($json->json_encode_ex($results));
}

/**
 * 获取设计师作品列表
 * page 提供页面请求设计师商品数据 默认为0
 * type 数据库字段查询,提供条件进行排序  （add_time 发布时间）（sort 后台设置的产品排序） （sales_sum 销量排序）
 */
elseif ($action=="getWorksList"){
	$page=!empty($_REQUEST['page']) ? $_REQUEST['page'] : 0 ;  //页码
	$size=!empty($_REQUEST['size']) ? $_REQUEST['size'] : 10;  //一页显示商品个数
	$begin = $page*$size;
	$limit = " LIMIT $begin,$size";
	$sql="select W.works_id,W.cat_name,W.works_name,W.click_count,W.designer_name,W.collect_count,W.works_img,S.company_type,S.supplier_id,S.logo,S.company_name  from ".$GLOBALS['ecs']->table('works')." as W left join ".$GLOBALS['ecs']->table('supplier')." as S on W.supplier_id=S.supplier_id where W.is_show = 1 and W.examine = 1 order by W.add_time desc {$limit}";
	$works =  $GLOBALS['db']->getAll($sql);
	$sql="select count(works_id) from ".$GLOBALS['ecs']->table('works')." where is_show = 1 and examine = 1 ";
	$works_count=$GLOBALS['db']->getOne($sql);
	$allpage=ceil($works_count/$size);
	$rs=array();
	if(!empty($works)){
		foreach ($works as $key =>$val){
			if(!empty($val['works_img'])){
				$temp['works_img']=IMG_HOST.$val['works_img'];
			}else{
				$temp['works_img']='';
			}
			if(!empty($val['logo'])){
				$temp['head_pic']=IMG_HOST.$val['logo'];
			}else{
				$temp['head_pic']='';
			}
			$temp['works_id']=$val['works_id'];
			$temp['supplier_id']=$val['supplier_id'];
			$temp['cat_name']=$val['cat_name'];
			$temp['works_name']=$val['works_name'];
			$temp['designer_name']=$val['designer_name'];
			$temp['designer_type']=$val['company_type'];
			$temp['click_count']=$val['click_count'];
			$temp['collect_count']=$val['collect_count'];
			$province = explode('-', $val['company_name']);
			$temp['province']=$province[0].'-'.$province[1];
			$rs[]=$temp;
		}
		$results = array(
				'result' => 1,
				'info' => '请求成功',
				'page'=>$page,
				'size'=>$size,
				'count'=>$allpage,
				'works_list'=> $rs,
		);
	}else{
		$results = array(
			'result' => 0,
			'info' => '没数据',
		);
	}
	exit($json->json_encode_ex($results));
}


/**
 * 获取我的设计师商品列表
 * page 提供页面请求设计师商品数据 默认为0
 */
elseif ($action=="getMyGoodsList"){
	if(!empty($userinfo['userid'])){
		$userid=$userinfo['userid'];
		$page=!empty($_REQUEST['page']) ? $_REQUEST['page'] : 0 ;  //页码
		$size=!empty($_REQUEST['size']) ? $_REQUEST['size'] : 10;  //一页显示商品个数
		$examine=!empty($_REQUEST['examine']) ? $_REQUEST['examine'] : 1 ;  // 1销售中 2已下架 3审核中
		$begin = $page*$size;
		$limit = " LIMIT $begin,$size";
		$sql="select supplier_id from ".$GLOBALS['ecs']->table('supplier')." where user_id =".$userid." and is_designer = 1";
		
		$supplierId =  $GLOBALS['db']->getOne($sql);
		
		if ($examine==3) {
			$sql="select goods_id,examine,goods_thumb,goods_name,shop_price,sales_sum from ".$GLOBALS['ecs']->table('goods')." where supplier_id =".$supplierId." and is_designer=1 and examine !=1  order by goods_id desc  {$limit}";
			$sql1="select count(goods_id) from ".$GLOBALS['ecs']->table('goods')." where supplier_id =".$supplierId." and is_designer=1 and examine != 1 ";
		}elseif ($examine==2) {
			$sql="select goods_id,examine,goods_thumb,goods_name,shop_price,sales_sum from ".$GLOBALS['ecs']->table('goods')." where supplier_id =".$supplierId." and is_on_sale = 0 and is_designer=1 and examine = 1  order by goods_id desc  {$limit}";
			$sql1="select count(goods_id) from ".$GLOBALS['ecs']->table('goods')." where supplier_id =".$supplierId." and is_on_sale = 0 and is_designer=1 and examine = 1  ";
		}else{
			$sql="select goods_id,examine,goods_thumb,goods_name,shop_price,sales_sum from ".$GLOBALS['ecs']->table('goods')." where supplier_id =".$supplierId." and is_on_sale = 1 and is_designer=1 and examine = 1 order by goods_id desc  {$limit}";
			$sql1="select count(goods_id) from ".$GLOBALS['ecs']->table('goods')." where supplier_id =".$supplierId." and is_on_sale = 1 and is_designer=1 and examine = 1 ";
		}
		$goods = $GLOBALS['db']->getAll($sql);
		$goods_count=$GLOBALS['db']->getOne($sql1);
		$allpage=ceil($goods_count/$size);
		$rs=array();
		if(!empty($goods)){
			foreach ($goods as $key =>$val){
				if(!empty($val['goods_thumb'])){
					$temp['image']=IMG_HOST.$val['goods_thumb'];
				}else{
					$temp['image']='';
				}
				$temp['goods_id']=$val['goods_id'];
				$temp['goods_name']=$val['goods_name'];
				$temp['shop_price']=$val['shop_price'];
				$temp['sales_sum']=$val['sales_sum'];
			
				if ($val['examine'] == 0) {
					$temp['examine'] ="审核中";
				}elseif ($val['examine'] == 1){
					$temp['examine'] ="审核通过";
				}else{
					$temp['examine'] ="审核不通过";
				}
				$rs[]=$temp;
			}
		}
		$results = array(
			'result' => 1,
			'info' => '请求成功',
			'goods_list' =>$rs,
			'page' =>$page,
			'size' => $size,
			'count'=>$allpage
		);
	}else{
		$results = array(
			'result' => 0,
			'info' => '请先登录！',
			'goods_list' => array(),
				
		);
	}
	exit($json->json_encode_ex($results));
}

/**
 * 获取我的作品列表
 */
elseif ($action=="getMyWorksList"){
	if(!empty($userinfo['userid'])){
		$userid=$userinfo['userid'];
		$page=!empty($_REQUEST['page']) ? $_REQUEST['page'] : 0 ;  //页码
		$size=!empty($_REQUEST['size']) ? $_REQUEST['size'] : 10;  //一页显示商品个数
		$examine=!empty($_REQUEST['examine']) ? $_REQUEST['examine'] : 1 ;  //  1为全部 2为未审核
		$begin = $page*$size;
		$limit = " LIMIT $begin,$size";
		
		if ($examine==2) {
			$sql="select works_id,works_name,works_img,click_count,collect_count,cat_name from ".$GLOBALS['ecs']->table('works')."  where examine=0 and user_id = ".$userid." order by add_time desc  {$limit}";
			$sql1 =" select count(works_id) from ".$GLOBALS['ecs']->table('works')." where examine = 0 and user_id = ".$userid;
		}else{
			$sql="select works_id,works_name,works_img,click_count,collect_count,cat_name from ".$GLOBALS['ecs']->table('works')."  where user_id = ".$userid." order by add_time desc  {$limit}";
			$sql1 =" select count(works_id) from ".$GLOBALS['ecs']->table('works')." where examine = 1 and user_id = ".$userid;
		}
		
		$works = $GLOBALS['db']->getAll($sql);
	
		$works_count=$GLOBALS['db']->getOne($sql1);
		$allpage=ceil($works_count/$size);
		$rs=array();
		if(!empty($works)){
			foreach ($works as $key =>$val){
				if(!empty($val['works_img'])){
					$temp['works_img']=IMG_HOST.$val['works_img'];
				}else{
					$temp['works_img']='';
				}
				$temp['works_id']=$val['works_id'];
				$temp['works_name']=$val['works_name'];
				$temp['click_count']=$val['click_count'];
				$temp['collect_count']=$val['collect_count'];
				$temp['cat_name']=$val['cat_name'];
				$rs[]=$temp;
			}
		}
		$results = array(
			'result' => 1,
			'info' => '请求成功',
			'works_list' => $rs,
			'page' =>$page,
			'size' => $size,
			'count'=>$allpage
		);
	}else{
		$results = array(
			'result' => 0,
			'info' => '请先登录！',
			'works_list' => array(),
		);
	}
	exit($json->json_encode_ex($results));
}

/**
 * 获取作品详情
 */
elseif ($action=="works_detail"){

	if(!empty($userinfo['userid'])){
		$userid=$userinfo['userid'];
	}
	$works_id =  $_REQUEST['works_id']   ;
	$works = get_works($works_id);
	
	if(empty($works) || empty($works_id)){
		$rs=array(
			'result'=>'0',
			'info'=>'请求失败',
			'works'=>array(),
		);
		exit($json->json_encode_ex($rs));
	}
	$sql="update " .$GLOBALS['ecs']->table('works')	." set click_count = click_count+1 where works_id = {$works_id} ";
	$GLOBALS['db']->query($sql);

	$sql = "SELECT company_name,company_type,introduction,logo FROM " .$GLOBALS['ecs']->table('supplier')	." where supplier_id = {$works['supplier_id']}";
	$supplier = $GLOBALS['db']->getRow($sql);
	$sql = "SELECT works_id,cat_name,works_name,works_img,collect_count,click_count	FROM " .$GLOBALS['ecs']->table('works')	." where examine=1 and user_id = {$works['user_id']} and works_id !=  ".$_REQUEST['works_id'] ." order by add_time desc limit 5 ";
	$more = $GLOBALS['db']->getAll($sql);
	foreach ($more as $key =>$val){
		if(!empty($val['works_img'])){
			$more[$key]['works_img']=IMG_HOST.$val['works_img'];
		}else{
			$more[$key]['works_img']='';
		}
	}
	$province = explode('-', $supplier['company_name']);
	$works['works_img'] = IMG_HOST.$works['works_img'];
	$works['head_pic'] = IMG_HOST.$supplier['logo'];
	$works['introduction'] = $supplier['introduction'];
	$works['province'] = $province[0] .'-'. $province[1];
	$works['designer_type'] = $supplier['company_type'];
	$works['works_id'] = $works_id;
	$works['more'] = $more;
	$works['collect']=0;
	if(!empty($works['works_content'])){
		$pregRule = "/\/public\//";
		$rep_content = preg_replace_callback($pregRule, function(){
			return IMG_HOST."public/";
		}, $works['works_content']);
	
			//$rep_content=get_img_thumb_url($rep_content);
			$works['works_content'] = "<style>p{ margin:0px; padding:0px;}</style>";
			$works['works_content'] .=$rep_content;
	}
	unset($works['user_id']);
	if ($userid) {
		$sql = "SELECT id	FROM " .$GLOBALS['ecs']->table('works_collect')	." where user_id = {$userid} and works_id =  ".$_REQUEST['works_id'] ;
		$collect = $GLOBALS['db']->getRow($sql);
		if ($collect) {
			$works['collect']=1;
		}
	}
	$results = array(
		'result' => 1,
		'info' => '请求成功',
		'works'=>$works,
	);
	
	exit($json->json_encode_ex($results));
}

/**
 * 收藏作品接口
 */
elseif ($action=="works_collect"){
	
	if(!empty($userinfo['userid'])&&!empty($_REQUEST['works_id'])){
		$userid=$userinfo['userid'];
		$works_id =  $_REQUEST['works_id']   ;
		$sql = "SELECT id	FROM " .$GLOBALS['ecs']->table('works_collect')	." where user_id = {$userid} and works_id =  ".$_REQUEST['works_id'] ;
		$collect = $GLOBALS['db']->getRow($sql);
		
		if ($collect) {
			$sql="delete from ".$GLOBALS['ecs']->table('works_collect'). " where user_id = {$userid} and works_id ={$works_id}";
			$one=$GLOBALS['db']->query($sql);
			$sql="update  ".$GLOBALS['ecs']->table('works'). " set collect_count = collect_count - 1 where works_id = {$works_id}";
			$one=$GLOBALS['db']->query($sql);
			$results = array(
				'result' => 1,
				'info' => '取消关注成功！',
				'collect' => '0'
			);
		}else{
			$sql="insert INTO ".$GLOBALS['ecs']->table('works_collect'). "( works_id,user_id ) value ({$works_id},{$userid})";
			$one=$GLOBALS['db']->query($sql);
			$sql="update  ".$GLOBALS['ecs']->table('works'). " set collect_count = collect_count + 1 where works_id = {$works_id}";
			$one=$GLOBALS['db']->query($sql);
			$results = array(
				'result' => 1,
				'info' => '关注成功！',
				'collect' => '1'
			);
		}
	}else{
		$results = array(
			'result' => 0,
			'info' => '请先登录！'
		);
	}
	exit($json->json_encode_ex($results));
}


/**
 * 获取设计师分类
 */
elseif ($action=="getDesignerType"){
	$sql = "SELECT name	FROM " .$GLOBALS['ecs']->table('designer_category')	;
	$res = $GLOBALS['db']->getAll($sql);
	
	$results = array(
		'result' => 1,
		'type_list' =>$res,	//	设计师分类
	);

	exit($json->json_encode_ex($results));
}
/**
 * 搜索设计师商品
 */
elseif ($action=="search_goods"){
	$keywords  = !empty($_REQUEST['keywords'])   ? htmlspecialchars(trim($_REQUEST['keywords']))  : '';
	$arr = explode(' ', $keywords);
	if(!empty($keywords)){
		if (!empty($_COOKIE['goods_near_search']))
		{
			$history = explode(',', $_COOKIE['goods_near_search']);
			array_unshift($history, $keywords);
			$history = array_unique($history);
			while (count($history) > 5)
			{
				array_pop($history);
			}
			setcookie('goods_near_search', implode(',', $history), gmtime() + 3600 * 24 * 30);
		}
		else
		{
			setcookie('goods_near_search', $keywords, gmtime() + 3600 * 24 * 30);
		}
	}
	$where=" WHERE is_on_sale=1 AND examine = 1 AND is_designer = 1 AND 1=1";
	if(!empty($arr)){
		$where.=" AND ( ";
		foreach ($arr as $val){
		//	$where.=" goods_name like '%".$val."%' OR keywords like '%".$val."%' OR";
			$where.=" goods_name like '%".$val."%' OR";
		}
		$where=substr($where, 1, -2);
		$where.=" ) ";
	}
	
	$page=!empty($_REQUEST['page']) ? $_REQUEST['page'] : 0;
	$size = !empty($_REQUEST['size']) ? $_REQUEST['size'] : 10;
	$begin = $page*$size;
	$limit = " LIMIT $begin,$size";
	//按条件获取商品列表
	$sql="SELECT goods_id,goods_name,shop_price,original_img as image,keywords from ".$GLOBALS['ecs']->table('goods')." {$where} order by add_time desc {$limit}";
	
	$list=$GLOBALS['db']->getAll($sql);
	foreach ($list as $key =>$val){
		if(!empty($val['image'])){
			$list[$key]['image']=IMG_HOST.$val['image'];
		}else{
			$list[$key]['image']	='';
		}
	}

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
	);
	exit($json->json_encode_ex($rs));

}
/**
 * 搜索设计师作品
 */
elseif ($action=="search_works"){
	$keywords  = !empty($_REQUEST['keywords'])   ? htmlspecialchars(trim($_REQUEST['keywords']))  : '';
	$arr = explode(' ', $keywords);
	if(!empty($keywords)){
		if (!empty($_COOKIE['works_near_search']))
		{
			$history = explode(',', $_COOKIE['works_near_search']);
			array_unshift($history, $keywords);
			$history = array_unique($history);
			while (count($history) > 5)
			{
				array_pop($history);
			}
			setcookie('works_near_search', implode(',', $history), gmtime() + 3600 * 24 * 30);
		}
		else
		{
			setcookie('works_near_search', $keywords, gmtime() + 3600 * 24 * 30);
		}
	}
	$where=" WHERE W.is_show=1 AND W.examine = 1  AND 1=1";
	$where1=" WHERE is_show=1 AND examine = 1  AND 1=1";
	if(!empty($arr)){
		$where.=" AND ( ";
		$where1.=" AND ( ";
		foreach ($arr as $val){
			$where.=" W.works_name like '%".$val."%' OR W.keywords like '%".$val."%' OR";
			$where1.=" works_name like '%".$val."%' OR keywords like '%".$val."%' OR";
		}
		$where=substr($where, 1, -2);
		$where.=" ) ";
		$where1=substr($where1, 1, -2);
		$where1.=" ) ";
	}
	$page=!empty($_REQUEST['page']) ? $_REQUEST['page'] : 0;
	$size =!empty($_REQUEST['size']) ? $_REQUEST['size'] : 10;
	$begin = $page*$size;
	$limit = " LIMIT $begin,$size";
	//按条件获取商品列表
	$file =  " W.works_img,W.cat_name,W.collect_count,W.click_count,W.works_name,W.works_id,S.company_name as province,S.logo as head_pic,S.supplier_name as designer_name";
	$sql="SELECT {$file} from  ".$GLOBALS['ecs']->table('works')." as W left join ".$GLOBALS['ecs']->table('supplier')." as S on W.supplier_id = S.supplier_id {$where} order by W.add_time desc {$limit}";

	$list=$GLOBALS['db']->getAll($sql);
	foreach ($list as $key =>$val){
		if(!empty($val['works_img'])){
			$list[$key]['works_img']=IMG_HOST.$val['works_img'];
		}else{
			$list[$key]['works_img']	='';
		}
		if(!empty($val['head_pic'])){
			$list[$key]['head_pic']=IMG_HOST.$val['head_pic'];
		}else{
			$list[$key]['head_pic']	='';
		}
		if(!empty($val['province'])){
			$province = explode('-', $val['province']);
			$list[$key]['province']=$province[0].'-'.$province[1];
		}else{
			$list[$key]['province']	='';
		}
		
	}

	$sql="SELECT count(works_id) as count from ".$GLOBALS['ecs']->table('works')."{$where1}";
	$works_count=$GLOBALS['db']->getOne($sql);
	$allpage=ceil($works_count/$size);
	$rs=array(
			'result'=>'1',
			'info'=>'请求成功',
			'works_list'=>$list,
			'page'=>$page,
			'size'=>$size,
			'count'=>$allpage,
	);
	exit($json->json_encode_ex($rs));
	
}
/**
 * 获取作品关键词、最近搜索接口
 */
elseif ($action=="works_search_keywords"){
	$serachkeywords=$_CFG['works_search_keywords'];
	$rs['hot_search']=explode(',',$serachkeywords);
	$rs['near_search']=explode(',',$_COOKIE['works_near_search']);
	if ($rs['near_search']) {
		if ($rs['near_search'][0]=="") {
			$rs['near_search']=array();
		}
	
	}
	
	$results = array(
		'result' => 1,
		'list' =>$rs,	// 作品最近搜索，关键词搜索
		'info' => '请求成功'
	);
	exit($json->json_encode_ex($results));
}
/**
 * 获取商品关键词、最近搜索接口
 */
elseif ($action=="goods_search_keywords"){
	$serachkeywords=$_CFG['goods_search_keywords'];
	$rs['hot_search']=explode(',',$serachkeywords);
	$rs['near_search']=explode(',',$_COOKIE['goods_near_search']);
	$results = array(
			'result' => 1,
			'list' =>$rs,	// 商品最近搜索，关键词搜索
			'info' => '请求成功'
	);
	exit($json->json_encode_ex($results));
}
/**
 * 设计师详情+作品
 */
elseif ($action=="designer_info_works_list"){
	
	$page=!empty($_REQUEST['page']) ? $_REQUEST['page'] : 0 ;  //页码
	$size=!empty($_REQUEST['size']) ? $_REQUEST['size'] : 10;  //一页显示商品个数
	$supplier_id= $_REQUEST['supplier_id'] ;
	$begin = $page*$size;
	$limit = " LIMIT $begin,$size";
	if ($supplier_id) {
		$sql = "SELECT supplier_id,supplier_name as designer_name,company_type as designer_type,introduction,logo as head_pic FROM " .$GLOBALS['ecs']->table('supplier')	." where supplier_id = {$supplier_id} and is_designer=1";
		$supplier = $GLOBALS['db']->getRow($sql);
		if(!empty($supplier['head_pic'])){
			$supplier['head_pic']=IMG_HOST.$supplier['head_pic'];
		}else{
			$supplier['head_pic']['head_pic']='';
		}
		if (!$supplier) {
			$results = array(
				'result' => 0,
				'info' => '设计师不存在'
			);
			exit($json->json_encode_ex($results));
		}
		
		$supplier['collect'] = 0;
		if(!empty($userinfo['userid'])){
			$user_id = $userinfo['userid'];
			$sql = "SELECT collect_id FROM  " .$GLOBALS['ecs']->table('supplier_collect')	." where user_id = {$user_id} and supplier_id = {$supplier_id}";
			$collect = $GLOBALS['db']->getOne($sql);
			if ($collect) {
				$supplier['collect'] = 1;
			}
		}
		
		$sql = "SELECT works_id,cat_name,works_name,click_count,collect_count,works_img FROM  " .$GLOBALS['ecs']->table('works')	." where is_show = 1 and examine = 1 and supplier_id = {$supplier_id} order by add_time desc {$limit}";
		$works_list = $GLOBALS['db']->getAll($sql);
		foreach ($works_list as $key =>$val){
			if(!empty($val['works_img'])){
				$works_list[$key]['works_img']=IMG_HOST.$val['works_img'];
			}else{
				$works_list[$key]['works_img']='';
			}
		}
		
		$sql = "SELECT count(works_id) FROM  " .$GLOBALS['ecs']->table('works')	." where is_show = 1 and examine = 1  and supplier_id = {$supplier_id}";
		$works_count=$GLOBALS['db']->getOne($sql);
		$allpage=ceil($works_count/$size);
		
		$results = array(
			'result' => 1,
			'info' => '请求成功',
			'designer_info' => $supplier,
			'works_list' => $works_list,
			'page'=>$page,
			'count'=>$allpage,
			'size'=>$size,
			'works_count'=>$works_count
		);
		
	}else{
		$results = array(
			'result' => 0,
			'info' => '缺少设计师id'
		);
	}
	exit($json->json_encode_ex($results));
}

/**
 * 设计师详情+商品
 */
elseif ($action=="designer_info_goods_list"){
	$page=!empty($_REQUEST['page']) ? $_REQUEST['page'] : 0 ;  //页码
	$size=!empty($_REQUEST['size']) ? $_REQUEST['size'] : 10;  //一页显示商品个数
	$supplier_id= $_REQUEST['supplier_id'] ;
	$begin = $page*$size;
	$limit = " LIMIT $begin,$size";
	if ($supplier_id) {
		$sql = "SELECT supplier_id,supplier_name as designer_name,company_type as designer_type,introduction,logo as head_pic FROM " .$GLOBALS['ecs']->table('supplier')	." where supplier_id = {$supplier_id} and is_designer=1";
		$supplier = $GLOBALS['db']->getRow($sql);
		if(!empty($supplier['head_pic'])){
			$supplier['head_pic']=IMG_HOST.$supplier['head_pic'];
		}else{
			$supplier['head_pic']['head_pic']='';
		}
		if (!$supplier) {
			$results = array(
				'result' => 0,
				'info' => '设计师不存在'
			);
			exit($json->json_encode_ex($results));
		}
		
		$supplier['collect'] = 0;
		if(!empty($userinfo['userid'])){
			$user_id = $userinfo['userid'];
			$sql = "SELECT collect_id FROM  " .$GLOBALS['ecs']->table('supplier_collect')	." where user_id = {$user_id} and supplier_id = {$supplier_id}";
			$collect = $GLOBALS['db']->getOne($sql);
			if ($collect) {
				$supplier['collect'] = 1;
			}
		}
		
		$sql = "SELECT goods_id,shop_price,original_img as image,goods_name FROM  " .$GLOBALS['ecs']->table('goods') ." where is_on_sale = 1 and examine = 1 and supplier_id = {$supplier_id} order by add_time desc {$limit}";
		$goods_list = $GLOBALS['db']->getAll($sql);
		foreach ($goods_list as $key =>$val){
			if(!empty($val['image'])){
				$goods_list[$key]['image']=IMG_HOST.$val['image'];
			}else{
				$goods_list[$key]['image']='';
			}
		}
		
		$sql = "SELECT  count(goods_id) FROM  " .$GLOBALS['ecs']->table('goods')	." where is_on_sale = 1 and examine = 1 and supplier_id = {$supplier_id}";
		$goods_count=$GLOBALS['db']->getOne($sql);
		$allpage=ceil($goods_count/$size);
		
		$results = array(
				'result' => 1,
				'info' => '请求成功',
				'designer_info' => $supplier,
				'goods_list' => $goods_list,
				'page'=>$page,
				'count'=>$allpage,
				'size'=>$size,
				'goods_count' => $goods_count
		);
	}else{
		$results = array(
			'result' => 0,
			'info' => '缺少设计师id'
		);
	}
	exit($json->json_encode_ex($results));
}

/**
 * 设计师商品上下架
 */
elseif ($action=="goods_frame"){
	$goods_id=!empty($_REQUEST['goods_id']) ? $_REQUEST['goods_id'] : 0 ; //商品id
	if(!empty($userinfo['userid'])){
		$user_id = $userinfo['userid'];
	}
	$supplier_id = get_supplier_id($user_id);
	
	if ($supplier_id&&$goods_id) {
		$sql = "select is_on_sale from ".$GLOBALS['ecs']->table('goods')." where goods_id='".$goods_id."' and supplier_id = {$supplier_id}";
		$type = $GLOBALS['db']->getOne($sql);

		if ($type == 1) {
			$sql=" UPDATE " . $GLOBALS['ecs']->table('goods') . " set  is_on_sale  = 0 where goods_id = " . $goods_id ." and supplier_id = {$supplier_id}";
			$success=$GLOBALS['db']->query($sql);
			if ($success) {
				$results = array(
					'result' => 1,
					'info' => '下架成功！'
				);
			}
		}else{
			$sql=" UPDATE " . $GLOBALS['ecs']->table('goods') . " set  is_on_sale = 1 where goods_id = " . $goods_id." and supplier_id = {$supplier_id}";
			$success=$GLOBALS['db']->query($sql);
			if ($success) {
				$results = array(
					'result' => 1,
					'info' => '上架成功！'
				);
			}
		}
		
	}else{
		$results = array(
			'result' => 0,
			'info' => '未登录或商品信息有误！'
		);
	}
	exit($json->json_encode_ex($results));
}

/**
 * 设计师作品发布评论
 */
elseif ($action=="post_comment"){
	if(!empty($userinfo['userid'])){
		$user_id = $userinfo['userid'];
	}
	$works_id = $_REQUEST['works_id'] ; //作品id
	$parent_id = !empty($_REQUEST['parent_id']) ? $_REQUEST['parent_id'] : 0 ; //上级id
	$content = $_REQUEST['content'] ; //内容
	$sql = "select value from ".$GLOBALS['ecs']->table('works_comment_sensitive');
	$sensitives=$GLOBALS['db']->getAll($sql);
	foreach ($sensitives as $key =>$val){
		$pos = strpos($content, $val['value']);
		if ($pos !== false)
		{
	    	$results = array(
				'result' => 0,
				'info' => '评论内容里包含违禁词:'.$val['value']
			);
			exit($json->json_encode_ex($results));
	    }
	}
	if($user_id&&$works_id&&content){
		if ($parent_id == 0) {
			$parent_id=0;;
		}
		$sql = "select add_time,content from ".$GLOBALS['ecs']->table('works_comment')." where user_id = {$user_id} and works_id ={$works_id} order by comment_id desc";
		$comments=$GLOBALS['db']->getAll($sql);
		if(!empty($comments)){
			foreach ($comments as $key =>$val){
				if ($val['add_time'] + 10 > time() ) {
					$results = array(
						'result' => 0,
						'info' => '请10秒后再发布！'
					);
					exit($json->json_encode_ex($results));
				}
				if ($content == $val['content']) {
					$results = array(
						'result' => 0,
						'info' => '请勿重复发布相同的内容！'
					);
					exit($json->json_encode_ex($results));
				}
			}
		}
		
		$sql = "select nickname as user_name ,head_pic from ".$GLOBALS['ecs']->table('users')." where user_id = {$user_id}";
		$user = $GLOBALS['db']->getRow($sql);
		$head_pic = $user['head_pic'];
		$user_name = $user['user_name'];
		if(!empty($_REQUEST['parent_user_id'] )){
			$parent_user_id=$_REQUEST['parent_user_id'];
			$sql = "select nickname as user_name from ".$GLOBALS['ecs']->table('users')." where user_id = ".$parent_user_id;
			$parent_user_name = $GLOBALS['db']->getOne($sql);
			$sql="insert INTO ".$GLOBALS['ecs']->table('works_comment') ." (works_id,user_id,user_name,head_pic,parent_id,parent_user_id,parent_user_name,content,add_time) values ('{$works_id}','{$user_id}','{$user_name}','{$head_pic}','{$parent_id}','{$parent_user_id}','{$parent_user_name}',
			'{$content}','".time()."')";
		}else{
			$sql="insert INTO ".$GLOBALS['ecs']->table('works_comment') ." (works_id,user_id,user_name,head_pic,parent_id,content,add_time) values ('{$works_id}','{$user_id}','{$user_name}','{$head_pic}','{$parent_id}',
			'{$content}','".time()."')";
		}
		
		$success=$GLOBALS['db']->query($sql);
		$results = array(
			'result' => 1,
			'info' => '发布成功！',
		);
		
	}else{
		$results = array(
			'result' => 0,
			'info' => '参数有误！'
		);
	}
	exit($json->json_encode_ex($results));
}

/**
 * 获取作品一级评价
 */
elseif ($action=="get_comments"){
	$works_id = $_REQUEST['works_id'] ; //作品id
	$page=!empty($_REQUEST['page']) ? $_REQUEST['page'] : 0 ;  //页码
	$size=!empty($_REQUEST['size']) ? $_REQUEST['size'] : 10;  //一页显示商品个数
	$begin = $page*$size;
	$limit = " LIMIT $begin,$size";
	if ($works_id) {
		$sql = "select works_id,comment_id,user_id,user_name,head_pic,collect_count,add_time as time,content from ".$GLOBALS['ecs']->table('works_comment') ." where works_id = {$works_id} and parent_id = 0 order by add_time desc {$limit}";
		
		$comments=$GLOBALS['db']->getAll($sql);
	
		$sql = "select count(comment_id) from ".$GLOBALS['ecs']->table('works_comment') ." where works_id = {$works_id} and parent_id = 0 ";
		$comments_count = $GLOBALS['db']->getOne($sql);
		
		$allpage=ceil($comments_count/$size);
		if(!empty($comments)){
			foreach ($comments as $key =>$val){
				$today_day = date("m-d",time()); 
				$add_time_day = date("m-d",$val['time']);
				$today_year = date("Y",time()); 
				$add_time_year = date("Y",$val['time']);
				
				if ($val['head_pic'] ) {
					$comments[$key]['head_pic'] =IMG_HOST.$val['head_pic'];
				}else{
					$comments[$key]['head_pic'] ="";
				}
				
				if ($today_day == $add_time_day) {
					$comments[$key]['time'] = date("H:i",$val['time']);
				}else{
					if ($today_year == $add_time_year) {
						$comments[$key]['time'] = $add_time_year;
					}else{
						$comments[$key]['time'] = date("Y-m-d H:i",$val['time']);
					}
				}
				$sql = "select count(comment_id)  from ".$GLOBALS['ecs']->table('works_comment') ." where works_id = {$works_id} and parent_id =  ".$val['comment_id'];
				$comments[$key]['comment_count']=$GLOBALS['db']->getOne($sql);
				$comments[$key]['collect'] = 0;
				
				if(!empty($userinfo['userid'])){
					$user_id = $userinfo['userid'];
					$sql = "select id  from ".$GLOBALS['ecs']->table('works_comment_collect') ." where user_id = {$user_id} and comment_id =".$val['comment_id'];
					$collect=$GLOBALS['db']->getOne($sql);
					
					if ($collect) {
						$comments[$key]['collect'] = 1;
					}
				}
			}
			
			$results = array(
				'result' => 1,
				'info' => '请求成功！',
				'comments' => $comments,
				'page' => $page,
				'size' => $size,
				'count' => $allpage,
			);
		}else{
			$results = array(
				'result' => 1,
				'info' => '暂无评论！',
				'comments' => array(),
			);
		}
	}else{
		$results = array(
				'result' => 0,
				'info' => '作品id为空！'
		);
	}
	exit($json->json_encode_ex($results));
}
/**
 * 获取作品二级评价
 */
elseif ($action=="get_second_comments"){
	$works_id = $_REQUEST['works_id'] ; //作品id
	$parent_id = $_REQUEST['parent_id'] ; //一级评论id
	$page=!empty($_REQUEST['page']) ? $_REQUEST['page'] : 0 ;  //页码
	$size=!empty($_REQUEST['size']) ? $_REQUEST['size'] : 10;  //一页显示商品个数
	$begin = $page*$size;
	
	$limit = " LIMIT $begin,$size";
	if ($works_id) {
		$sql = "select comment_id,user_id,user_name,head_pic,collect_count,add_time as time,content from ".$GLOBALS['ecs']->table('works_comment') ." where comment_id = {$parent_id} ";
		$parent_comment = $GLOBALS['db']->getRow($sql);
		if(!empty($parent_comment['head_pic'])){
			$parent_comment['head_pic']=IMG_HOST.$parent_comment['head_pic'];
		}else{
			$parent_comment['head_pic']='';
		}
	
		$today_day = date("m-d",time());
		$add_time_day = date("m-d",$parent_comment['time']);
		$today_year = date("Y",time());
		$add_time_year = date("Y",$parent_comment['time']);
		if ($today_day == $add_time_day) {
			$parent_comment['time'] = date("H:i",$parent_comment['time']);
		}else{
			if ($today_year == $add_time_year) {
				$parent_comment['time'] = $add_time_year;
			}else{
				$parent_comment['time'] = date("Y-m-d H:i",$parent_comment['time']);
			}
		}
		$parent_comment['collect'] = 0;
		if(!empty($userinfo['userid'])){
			$user_id = $userinfo['userid'];
			$sql = "select id  from ".$GLOBALS['ecs']->table('works_comment_collect') ." where user_id = {$user_id} and comment_id =".$parent_comment['comment_id'];
			$collect=$GLOBALS['db']->getOne($sql);
			if ($collect) {
				$parent_comment['collect'] = 1;
			}
		}
		
		$sql = "select parent_user_id,parent_user_name,comment_id,user_id,user_name,head_pic,collect_count,add_time as time,content from ".$GLOBALS['ecs']->table('works_comment') ." where works_id = {$works_id} and parent_id = {$parent_id} order by add_time desc {$limit}";
		$comments=$GLOBALS['db']->getAll($sql);
		$sql = "select count(comment_id) from ".$GLOBALS['ecs']->table('works_comment') ." where works_id = {$works_id} and parent_id = {$parent_id}";
		$comments_count = $GLOBALS['db']->getOne($sql);
		$allpage=ceil($comments_count/$size);
		if(!empty($comments)){
			foreach ($comments as $key =>$val){
				$today_day = date("m-d",time());
				$add_time_day = date("m-d",$val['time']);
				$today_year = date("Y",time());
				$add_time_year = date("Y",$val['time']);
				
				if ($today_day == $add_time_day) {
					$comments[$key]['time'] = date("H:i",$val['time']);
				}else{
					if ($today_year == $add_time_year) {
						$comments[$key]['time'] = $add_time_year;
					}else{
						$comments[$key]['time'] = date("Y-m-d H:i",$val['time']);
					}
				}
				if(!empty($val['head_pic'])){
					$comments[$key]['head_pic']=IMG_HOST.$val['head_pic'];
				}else{
					$comments[$key]['head_pic']='';
				}
				$comments[$key]['collect'] = 0;
				if(!empty($userinfo['userid'])){
					$user_id = $userinfo['userid'];
					$sql = "select id  from ".$GLOBALS['ecs']->table('works_comment_collect') ." where user_id = {$user_id} and comment_id =".$val['comment_id'];
					$collect=$GLOBALS['db']->getOne($sql);
					if ($collect) {
						$comments[$key]['collect'] = 1;
					}
				}
			}
			$results = array(
				'result' => 1,
				'info' => '请求成功！',
				'parent_comment' => $parent_comment,
				'comments' => $comments,
				'page' => $page,
				'size' => $size,
				'count' => $allpage,
			);
		}else{
			$results = array(
					'result' => 1,
					'info' => '暂无评论！',
					'comments' => array(),
			);
		}
	}else{
		$results = array(
				'result' => 0,
				'info' => '作品id为空！'
		);
	}
	exit($json->json_encode_ex($results));
}
/**
 * 点赞评论
 */
elseif ($action=="comments_collect"){
	$comment_id = $_REQUEST['comment_id'] ; //作品id
	if(!empty($userinfo['userid'])){
		$user_id = $userinfo['userid'];
	}
	if ($user_id) {
		if ($comment_id) {
			$sql = "select comment_id  from ".$GLOBALS['ecs']->table('works_comment') ." where comment_id ={$comment_id}";
			$comment=$GLOBALS['db']->getOne($sql);
			if ($comment) {
				$sql = "select id  from ".$GLOBALS['ecs']->table('works_comment_collect') ." where user_id = {$user_id} and comment_id ={$comment_id}";
				$collect=$GLOBALS['db']->getOne($sql);
				if ($collect) {
					$results = array(
						'result' => 0,
						'info' => '您已赞过！'
					);
				}else{
					$sql="insert INTO ".$GLOBALS['ecs']->table('works_comment_collect') ." (user_id,comment_id) values ('{$user_id}','{$comment_id}')";
					$success=$GLOBALS['db']->query($sql);
					$sql=" update ".$GLOBALS['ecs']->table('works_comment')." set collect_count= collect_count+1 where comment_id={$comment_id}";
					$GLOBALS['db']->query($sql);
					$results = array(
						'result' => 1,
						'info' => '点赞成功！'
					);
				}
			}
			else{
				$results = array(
						'result' => 0,
						'info' => '找不到该评论！'
				);
			}
		}else{
			$results = array(
				'result' => 0,
				'info' => '找不到该评论！'
			);
		}
	}else{
		$results = array(
			'result' => 0,
			'info' => '请先登录！'
		);
	}
	exit($json->json_encode_ex($results));
}

/**
 * 设计师后台查看订单
 */
elseif ($action=="designer_order_list"){
	$userid=$userinfo['userid'];
	$sql = "select supplier_id from ".$GLOBALS['ecs']->table('supplier')." where user_id ={$userid} and is_designer = 1";
	$supplier_id = $GLOBALS['db']->getOne($sql);
	
	$type=!empty($_REQUEST['type']) ? $_REQUEST['type'] : '0';
	$page=!empty($_REQUEST['page']) ? $_REQUEST['page'] : 0 ;
	$size=6;
	//检测未支付订单是否超过支付时间，活动订单三十分钟，其他订单2天
	check_nopay_order($supplier_id);
	$list=get_supplier_order_list($supplier_id,$type,$page,$size);
	$order_count=!empty($rs['goods_count']) ? $rs['goods_count'] : '0';
	$number=$list['count'];
	$goods_num=$list['goods_num'];
	$all_price=$list['all_price'];
	unset($list['goods_num']);
	unset($list['goods_count']);
	unset($list['count']);
	unset($list['all_price']);
	
	if(!empty($list['list'])){
		$rs=array('result'=>'1','info'=>'请求成功','list'=>$list['list'],'page'=>$page,'size'=>$size,'count'=>$number,'goods_num'=>$goods_num,'all_price'=>$all_price,'order_count'=>$order_count);
		exit($json->json_encode_ex($rs));
	}else{
		$rs=array('result'=>'1','info'=>'无数据');
		exit($json->json_encode_ex($rs));
	}
}
/**
 * 设计师后台查看订单详情
 */
elseif ($action=="designer_order_info"){
	$order_id=$_REQUEST['order_id'];
	$userid=$userinfo['userid'];
	$sql = "select supplier_id from ".$GLOBALS['ecs']->table('supplier')." where user_id ={$userid} and is_designer = 1";
	$supplier_id = $GLOBALS['db']->getOne($sql);

	$list=get_order_info2($supplier_id,$order_id);
	
	//$order=get_order_info($userid,$order_id);
	if(!empty($list['goods_list'])){
		$rs=array('result'=>'1','info'=>'请求成功','order'=>$list);
		exit($json->json_encode_ex($rs));
	}else{
		$rs=array('result'=>'1','info'=>'无数据','order'=>array());
		exit($json->json_encode_ex($rs));
	}
}

/**
 * 设计师后台填写发货信息
 */
elseif ($action=="designer_express_delivery"){
	$order_id=$_REQUEST['order_id'];
	$userid=$userinfo['userid'];
	$shipping_name=$_REQUEST['shipping_name'];
	$shipping_code=$_REQUEST['shipping_code'];
	$action_note = $shipping_name.":".$shipping_code;
	$now_time =time();
	$sql = "select supplier_id from ".$GLOBALS['ecs']->table('supplier')." where user_id ={$userid} and is_designer = 1";
	$supplier_id = $GLOBALS['db']->getOne($sql);
	$sql = "select order_sn,user_id,consignee,country,province,city,district,address,zipcode,mobile,shipping_price from ".$GLOBALS['ecs']->table('order')."  where order_id = {$order_id} and pay_status = 1 and supplier_id ={$supplier_id}";
	$order = $GLOBALS['db']->getRow($sql);
	
	if ($order&&$shipping_name&&$shipping_code) {
		$sql=" update ".$GLOBALS['ecs']->table('order')." set order_status=1,shipping_status=1 where order_id={$order_id}";
		$GLOBALS['db']->query($sql);
		$data_field= "(order_id,order_sn,user_id,admin_id,consignee,zipcode,mobile,country,province,city,district,address,shipping_name,shipping_price,invoice_no,create_time,supplier_id)";
		$sql="insert INTO ".$GLOBALS['ecs']->table('shipping_order'). $data_field." values ({$order_id},{$order['order_sn']},{$order['user_id']},{$userid},'".$order['consignee']."','".$order['zipcode']."',{$order['mobile']},{$order['country']},{$order['province']},{$order['city']},{$order['district']},'".$order['address']."','{$shipping_name}',{$order['shipping_price']},'{$shipping_code}','{$now_time}','{$supplier_id}')";
		$GLOBALS['db']->query($sql);
		$data_field= "(order_id,action_user,order_status,shipping_status,pay_status,action_note,log_time,status_desc,supplier_id)";
		$sql="insert INTO ".$GLOBALS['ecs']->table('order_action'). $data_field." values ({$order_id},{$userid},1,1,1,'{$action_note}',{$now_time},'delivery',{$supplier_id})";
		$GLOBALS['db']->query($sql);
		$data['shipping_name']  = ShippingName($shipping_code);
		queryExpress($data['shipping_name'],$shipping_code);
		$rs=array('result'=>'1','info'=>'发货成功');
		exit($json->json_encode_ex($rs));
	}else{
		$rs=array('result'=>'0','info'=>'参数有误');
		exit($json->json_encode_ex($rs));
	}
}
/**
 * 设计师发送验证码
 */
elseif ($action=="send_code"){
	$phone=$_REQUEST['phone'];
	$code = rand ( 10000, 99999);
	include_once('includes/cls_sms1.php');
	$sms = new sms1();
	$checkphone=$sms->is_moblie($phone);
	if($checkphone==false){
		$results = array(
				'result'=>0,
				'info' => '手机号码格式不正确'
		);
		exit($json->json_encode_ex($results));
	}
	if(!empty($userinfo['userid'])){
		$user_id = $userinfo['userid'];
	}else{
		$results = array(
			'result'=>0,
			'info' => '请重新登录账号'
		);
		exit($json->json_encode_ex($results));
	}
	$sql = "select supplier_id from ".$GLOBALS['ecs']->table('supplier')." where user_id=".$user_id." and is_designer = 1";
	$supplier_id= $GLOBALS['db']->getOne($sql);
	if ($supplier_id) {
		$content = "您的验证码是".$code."(请尽快完成验证)。如非本人操作，请忽略本短信";
		$flag=$sms->sendSMS($phone,$content);
		if($flag['code']=='0'){
			setcookie("yzmcode",$code,time()+60*10);
			$results = array(
					'result'=>1,
					'code' =>$code,
					'info' => '发送成功'
			);
			exit($json->json_encode_ex($results));
		}elseif($flag['code']=='-1'){
			$results = array(
					'result'=>0,
					'info' => '验证码超过次数'
			);
			exit($json->json_encode_ex($results));
		}
	}
	else{
		$results = array(
			'result'=>0,
			'info' => '设计师不存在'
		);
		exit($json->json_encode_ex($results));
	}
	
}
/**
 * 设计师发送验证码
 */
elseif ($action=="check_code"){
	$code=$_REQUEST['code'];
	if(!empty($userinfo['userid'])){
		$user_id = $userinfo['userid'];
	}else{
		$results = array(
			'result'=>0,
			'info' => '请重新登录账号'
		);
		exit($json->json_encode_ex($results));
	}
	$sql = "select supplier_id from ".$GLOBALS['ecs']->table('supplier')." where user_id=".$user_id." and is_designer = 1";
	$supplier_id= $GLOBALS['db']->getOne($sql);
	if ($supplier_id) {
		if ($_COOKIE['yzmcode']) {
			if ($_COOKIE['yzmcode'] == $code) {
				$results = array(
					'result'=>1,
					'info' => '验证成功'
				);
				setcookie("yzmcode",'');
			}else{
				$results = array(
					'result'=>0,
					'info' => '验证码错误'
				);
			}
		}else{
			$results = array(
				'result'=>0,
				'info' => '验证码已失效，请重新发送'
			);
		}
	}else{
		$results = array(
			'result'=>0,
			'info' => '设计师不存在'
		);
	}

	exit($json->json_encode_ex($results));
}
/**
 * 设计师查看余额
 */
elseif ($action=="designer_money"){
	if(!empty($userinfo['userid'])){
		$user_id = $userinfo['userid'];
		$sql = "select user_money from".$GLOBALS['ecs']->table('users')." where user_id = {$user_id} and is_designer=1";
		
		$user_money= $GLOBALS['db']->getOne($sql);
		$results = array(
			'result'=>1,
			'info' => '请求成功',
			'money'=>$user_money
		);
	}else{
		$results = array(
			'result'=>0,
			'info' => '请登录'
		);
	}
	exit($json->json_encode_ex($results));
}

/**
 * 设计师提现
 */
elseif ($action=="designer_withdrawals"){
	if(!empty($userinfo['userid'])){
		$user_id = $userinfo['userid'];
	}
	$number = $_REQUEST['number'];
	$price = $_REQUEST['price'];
	$name = $_REQUEST['true_name'];
	$now_time = time();
	if ($user_id&&$number&&$name) {
		if ($price>=10) {
			$sql = "select user_money from".$GLOBALS['ecs']->table('users')." where user_id = {$user_id} and is_designer=1";
			$user_money= $GLOBALS['db']->getOne($sql);
			if ($price>$user_money) {
				$results = array(
					'result'=>0,
					'info' => '提现金额不得大于账户余额('.$user_money.'元)'
				);
			}else{
				$desc = $name ."提现".$price."元至".$number;
				
				$surplus_money = $user_money - $price;
				$sql = "update ".$GLOBALS['ecs']->table('users')." set user_money={$surplus_money} where user_id = {$user_id}";

				$success= $GLOBALS['db']->query($sql);
				if ($success) {
					$sql = "insert into ".$GLOBALS['ecs']->table('withdrawals_log')." (user_id,money_num,account,real_name,add_time) values ({$user_id},{$price},'{$number}','{$name}',{$now_time})";
					$success= $GLOBALS['db']->query($sql);
					$sql = "insert into ".$GLOBALS['ecs']->table('account_log')." (user_id,user_money,cash_time,change_time,`desc`) values ({$user_id},-{$price},{$now_time},{$now_time},'{$desc}')";
					$success= $GLOBALS['db']->query($sql);
					$results = array(
							'result'=>1,
							'info' => '提现成功'
					);
				}else{
					$results = array(
						'result'=>0,
						'info' => '提现失败'
					);
				}
			}
			
		}else{
			$results = array(
				'result'=>0,
				'info' => '提现金额必须大于10元'
			);
		}
	}else{
		$results = array(
			'result'=>0,
			'info' => '未登录或支付宝信息为空'
		);
	}
	exit($json->json_encode_ex($results));
}

/**
 * 设计师冻结资金允许提取到余额列表
 */
elseif ($action=="allow_extract"){
	if(!empty($userinfo['userid'])){
		$page=!empty($_REQUEST['page']) ? $_REQUEST['page'] : 0 ;  //页码
		$size=!empty($_REQUEST['size']) ? $_REQUEST['size'] : 10;  //一页显示商品个数
		$begin = $page*$size;
		$limit = " LIMIT $begin,$size";
		$user_id = $userinfo['userid'];
		$now_time = time();
		$sql = "select log_id,frozen_money,user_money,status,allow_time as sign_time,order_sn from".$GLOBALS['ecs']->table('account_log')." where order_sn !='' and user_id = {$user_id} and allow_time<={$now_time} and allow_time!=0 order by status ,allow_time asc {$limit} ";
		$log_list= $GLOBALS['db']->getAll($sql);
		
		foreach ($log_list as $k=>$v)
		{
			$log_list[$k]['sign_time'] = date("Y-m-d H:i",$v['sign_time']-604800); 
			if ($v['status']==1) {
				$log_list[$k]['frozen_money'] = $log_list[$k]['user_money'];
			}
			unset($log_list[$k]['user_money']);
		}
		$sql = "select count(log_id) from".$GLOBALS['ecs']->table('account_log')." where user_id = {$user_id} and allow_time<={$now_time} and allow_time!=0  ";
		$log_count = $GLOBALS['db']->getOne($sql);
		$allpage=ceil($log_count/$size);
		$sql = "select sum(frozen_money) from".$GLOBALS['ecs']->table('account_log')." where user_id = {$user_id} and allow_time<={$now_time} and allow_time!=0 and status=0 ";
		$money_sum = $GLOBALS['db']->getOne($sql);
		$item = array();
		if (!$money_sum) {
			$money_sum="0.00";
		}
		$item['money_sum'] = $money_sum;
		
		$item['log_list'] = $log_list;
		$results = array(
			'result'=>1,
			'info' => '请求成功',
			'size' =>$size,
			'page' => $page,
			'count' =>$allpage,
			'items' =>$item
		);
		
	}else{
		$results = array(
			'result'=>0,
			'info' => '未登录'
		);
	}
	exit($json->json_encode_ex($results));
}
/**
 * 设计师冻结资金不允许提取到余额列表
 */
elseif ($action=="not_allow_extract"){
	if(!empty($userinfo['userid'])){
		$page=!empty($_REQUEST['page']) ? $_REQUEST['page'] : 0 ;  //页码
		$size=!empty($_REQUEST['size']) ? $_REQUEST['size'] : 10;  //一页显示商品个数
		$begin = $page*$size;
		$limit = " LIMIT $begin,$size";
		$user_id = $userinfo['userid'];
		$now_time = time();
		$sql = "select log_id,frozen_money,status,allow_time as sign_time,order_sn from".$GLOBALS['ecs']->table('account_log')." where order_sn !='' and user_id = {$user_id} and ( allow_time>={$now_time} or allow_time =0 )  order by sign_status desc,allow_time asc {$limit} ";
		
		$log_list= $GLOBALS['db']->getAll($sql);
		
		foreach ($log_list as $k=>$v)
		{
			if ($v['sign_time']) {
				$log_list[$k]['sign_time'] = date("Y-m-d H:i",$v['sign_time']-604800);
			}else{
				$log_list[$k]['sign_time'] = "未签收";
			}
			unset($log_list[$k]['status']);
		}
		$sql = "select count(user_id) from ".$GLOBALS['ecs']->table('account_log')." where user_id = {$user_id} and ( allow_time>={$now_time} or allow_time =0 )  ";
		$log_count = $GLOBALS['db']->getOne($sql);
		$allpage=ceil($log_count/$size);
		$sql = "select sum(frozen_money) from ".$GLOBALS['ecs']->table('account_log')." where user_id = {$user_id} and ( allow_time>={$now_time}  or allow_time =0 ) and status=0 ";
		$money_sum = $GLOBALS['db']->getOne($sql);
		$item = array();
		if (!$money_sum) {
			$item['money_sum'] = '0.00';
		}else{
			$item['money_sum'] = $money_sum;
		}
		
		
		$item['log_list'] = $log_list;
		$results = array(
			'result'=>1,
			'info' => '请求成功',
			'size' =>$size,
			'page' => $page,
			'count' =>$allpage,
			'items' =>$item
		);
		
	}else{
		$results = array(
			'result'=>0,
			'info' => '未登录'
		);
	}
	exit($json->json_encode_ex($results));
}
/**
 * 设计师一键提取冻结资金
 */
elseif ($action=="one_extract_frozen_money"){
	if(!empty($userinfo['userid'])){
		$user_id = $userinfo['userid'];
		$now_time =time();
		$sql = "select * from ".$GLOBALS['ecs']->table('account_log')." where user_id = {$user_id} and allow_time < {$now_time} and status=0 and sign_status=1";
		
		$list = $GLOBALS['db']->getAll($sql);
	
		if ($list) {
			foreach ($list as $key => $v){
				$sql=" update ".$GLOBALS['ecs']->table('account_log')." set frozen_money=0 , user_money= {$v['frozen_money']} , status=1 , cash_time ={$now_time} where log_id=".$v['log_id'];
				$GLOBALS['db']->query($sql);
				$sql=" update ".$GLOBALS['ecs']->table('users')." set frozen_money = frozen_money - {$v['frozen_money']} , user_money= user_money + {$v['frozen_money']}  where user_id={$user_id}";
				$GLOBALS['db']->query($sql);
			}
			$results=array(
					'result' => '1',
					'info' => '提取成功',
					'status' => '1'
			);
		}else{
			$results=array(
					'result' => '0',
					'info' => '无可提取冻结资金'
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
/**
 * 设计师提取冻结资金
 */
elseif ($action=="extract_frozen_money"){
	$log_id= $_REQUEST['log_id'] ;  
	if(!empty($userinfo['userid'])&&$log_id){
		$user_id = $userinfo['userid'];
		$sql = "select frozen_money,user_money from ".$GLOBALS['ecs']->table('account_log')." where user_id = {$user_id} and log_id={$log_id} and status = 0";
		$log =  $GLOBALS['db']->getRow($sql);
		$cash_time = time();
		if ($log) {
			$sql=" update ".$GLOBALS['ecs']->table('account_log')." set frozen_money=0 , user_money= {$log['frozen_money']} , status=1 , cash_time ={$cash_time} where log_id={$log_id}";
			$GLOBALS['db']->query($sql);
			$sql=" update ".$GLOBALS['ecs']->table('users')." set frozen_money = frozen_money - {$log['frozen_money']} , user_money= user_money + {$log['frozen_money']}  where user_id={$user_id}";
			$GLOBALS['db']->query($sql);
			$results=array(
					'result' => '1',
					'info' => '提取至余额成功'
			);
		}else{
			$results=array(
				'result' => '0',
				'info' => '已提取至余额，请刷新'
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
/**
 * 设计师退换货列表
 */
elseif ($action=="back_order_list"){
	if(!empty($userinfo['userid'])){
	
		$page=!empty($_REQUEST['page']) ? $_REQUEST['page'] : 0 ;  //页码
		$size=!empty($_REQUEST['size']) ? $_REQUEST['size'] : 10;  //一页显示商品个数
		$begin = $page*$size;
		$limit = " LIMIT $begin,$size";
		$user_id = $userinfo['userid'];
		
		$sql = "select supplier_id from ".$GLOBALS['ecs']->table('supplier')." where user_id = {$user_id} and is_designer = 1";
		$supplier_id = $GLOBALS['db']->getOne($sql);
		
		if ($supplier_id){
			$sql = "select type,status,id as back_id,addtime as add_time,order_id,order_sn,goods_id  from ".$GLOBALS['ecs']->table('back_order')." where supplier_id = {$supplier_id} order by status asc {$limit} ";
			$back_order_list = $GLOBALS['db']->getAll($sql);
			foreach ($back_order_list as $key=>$v){
				$back_order_list[$key]['add_time']=date("Y-m-d H:i",$v['add_time']);
			}
		
			$sql = "select count(supplier_id) from ".$GLOBALS['ecs']->table('back_order')." where supplier_id = {$supplier_id} ";
			$list_count =  $GLOBALS['db']->getOne($sql);
			$allpage=ceil($list_count/$size);
			foreach ($back_order_list as $key=>$v)
			{
				
				$sql = "select O.goods_num,O.goods_id,O.goods_name,O.goods_price,G.original_img from  ".$GLOBALS['ecs']->table('order_goods')." as O left join ".$GLOBALS['ecs']->table('goods')." as G on O.goods_id = G.goods_id where O.order_id = ".$v['order_id'] ." and O.goods_id = ".$v['goods_id'];
				
				$goods_list = $GLOBALS['db']->getAll($sql);
				foreach ($goods_list as $key1=>$v)
				{
					
					if ($v['original_img']) {
						$goods_list[$key1]['original_img']=IMG_HOST.$v['original_img'];
					}else{
						$goods_list[$key1]['original_img']="";
					}
					$back_order_list[$key]['goods_list'] = $goods_list;
					
				}
				unset($back_order_list[$key]['goods_id']);
			}
			
			$results=array(
				'result' => '1',
				'info' => '请求成功',
				'page' => $page,	//	当前页码
				'count' => $allpage,	//	总页数
				'size' => $size,	//	每页取得商品数据条数
				'back_order_list' =>$back_order_list
			);
		}else{
			$results=array(
				'result' => '0',
				'info' => '设计师不存在'
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
/**
 * 设计师退换货单详情
 */
elseif ($action=="back_order_info"){
	$back_id= $_REQUEST['back_id'] ;
	if(!empty($userinfo['userid'])&&!empty($back_id)){
		$user_id = $userinfo['userid'];
		$sql = "select supplier_id from ".$GLOBALS['ecs']->table('supplier')." where user_id = {$user_id} and is_designer = 1";
		$supplier_id = $GLOBALS['db']->getOne($sql);
		$sql = "select status,id as back_id,addtime as add_time,type,remark,total_amount,reason,imgs,order_sn,order_id,goods_id from ".$GLOBALS['ecs']->table('back_order')." where supplier_id = {$supplier_id} and id ={$back_id}";
		$info = $GLOBALS['db']->getRow($sql);
		$sql = "select O.goods_num,O.goods_id,O.goods_name,O.goods_price,G.original_img from  ".$GLOBALS['ecs']->table('order_goods')." as O left join ".$GLOBALS['ecs']->table('goods')." as G on O.goods_id = G.goods_id where O.order_id = ".$info['order_id'] ." and O.goods_id = ".$info['goods_id'];
		$goods_list = $GLOBALS['db']->getAll($sql);
		if (!$info['remark']) {
			$info['remark']="";
		}
		foreach ($goods_list as $key1=>$v)
		{
			if ($v['original_img']) {
				$goods_list[$key1]['original_img']=IMG_HOST.$v['original_img'];
			}else{
				$goods_list[$key1]['original_img']="";
			}
		}
		$info['goods_list']=$goods_list;
		$info['add_time']=date("Y-m-d H:i",$info['add_time']);
		if ($info['imgs']) {
			$imgs = explode(',',$info['imgs']); 
			foreach ($imgs as $key=>$v)
			{
				$images[$key]=IMG_HOST.$v;
			}
			$info['imgs']=$images;
		}else{
			$info['imgs']=array();
		}
		$sql = "select invoice_no,shipping_price from ".$GLOBALS['ecs']->table('shipping_order')." where order_id = {$info['order_id']}";

		$shipping = $GLOBALS['db']->getRow($sql);

		if (!$shipping['invoice_no']) {
			$info['type'] = "3";  //  1退货 2换货 3退款
		}
		$info['shipping_price'] = $shipping['shipping_price'];
		$results=array(
			'result' => '1',
			'info' => '请求成功',
			'order_info'=>$info
		);
	}else{
		$results=array(
			'result' => '0',
			'info' => '未登录或退货单不存在'
		);
	}
	exit($json->json_encode_ex($results));
}

/**
 * 设计师处理退换货
 */
elseif ($action=="handle_black_order"){
	$back_id= $_REQUEST['back_id'] ;
	$type= $_REQUEST['type'] ;  // 1退货  2换货
	$status = $_REQUEST['status'] ;   //1 同意  -1 拒绝
	$remark= $_REQUEST['remark'] ;  // 卖家同意或拒绝的原因
	if(!empty($userinfo['userid'])&&!empty($back_id)&&!empty($remark)&&!empty($type)&&!empty($status)){
		$user_id = $userinfo['userid'];
		$sql = "select supplier_id from ".$GLOBALS['ecs']->table('supplier')." where user_id = {$user_id} and is_designer = 1";
		$supplier_id = $GLOBALS['db']->getOne($sql);
		$sql = "select status,id,addtime as add_time,type,total_amount,reason,imgs,order_sn,order_id,goods_id from ".$GLOBALS['ecs']->table('back_order')." where supplier_id = {$supplier_id} and id ={$back_id} and status = 0";

		$info = $GLOBALS['db']->getRow($sql);
		if ($info) {
			//if ($type ==1) { //退货
				if ($status == 1) {
					$sql=" update ".$GLOBALS['ecs']->table('back_order')." set status=1 , remark='{$remark}' where id={$info['id']}";
					$GLOBALS['db']->query($sql);
					$results=array(
						'result' => '1',
						'info' => '同意退货',
						'status' => '1'
					);
				}elseif($status == -1){
					$sql=" update ".$GLOBALS['ecs']->table('back_order')." set status=-1 , remark='{$remark}' where id={$info['id']}";
					$GLOBALS['db']->query($sql);
					$results=array(
						'result' => '1',
						'info' => '拒绝退货',
						'status' => '-1'
					);
				}else{
					$results=array(
						'result' => '0',
						'info' => '处理状态有误'
					);
				}
		//	}elseif ($type ==2) { //换货
				
		//	}
		}else{
			$results=array(
				'result' => '0',
				'info' => '非法操作'
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

/**
 * 设计师查看退货单物流信息
 */
elseif ($action=="back_order_shipping"){
	$back_id= $_REQUEST['back_id'] ;
	if(!empty($userinfo['userid'])&&!empty($back_id)){
		$user_id = $userinfo['userid'];
		$sql = "select supplier_id from ".$GLOBALS['ecs']->table('supplier')." where user_id = {$user_id} and is_designer = 1";
		$supplier_id = $GLOBALS['db']->getOne($sql);
		$sql = "select * from ".$GLOBALS['ecs']->table('back_order')." where supplier_id = {$supplier_id} and id = {$back_id} ";
		$info = $GLOBALS['db']->getRow($sql);
		if ($info) {
			$sql = "select mobile from ".$GLOBALS['ecs']->table('users')." where  user_id = ".$info['user_id'] ;
			$mobile = $GLOBALS['db']->getOne($sql);
			$re['back_id'] = $info['id'];
			$re['shipping_no']=$info['refund_shipping_no'];
			$re['shipping_time']=date("Y-m-d H:i",$info['retund_shipping_time']);
			$re['mobile']=$mobile;
			$re['reason']=$info['reason'];
			if (!$re['shipping_no']) {
				$re['shipping_no'] ="";
			}
			if (!$re['reason']) {
				$re['reason']="";
			}
			$results=array(
				'result' => '1',
				'info' => '请求成功',
				'shipping_info' =>$re
			);
		}else{
			$results=array(
				'result'  => '0',
				'info' => '不是您的退货单'
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

/**
 * 设计师处理退货、退款
 */
elseif ($action=="order_refund"){
	$back_id= $_REQUEST['back_id'] ;
	$agree =$_REQUEST['agree'] ;
	$remark = $_REQUEST['remark'] ;
	if(!empty($userinfo['userid'])&&!empty($back_id)&&$agree){
		if ($agree == 1) {
			$user_id = $userinfo['userid'];
			$sql = "select supplier_id from ".$GLOBALS['ecs']->table('supplier')." where user_id = {$user_id} and is_designer = 1";
			$supplier_id = $GLOBALS['db']->getOne($sql);
			$sql = "select * from ".$GLOBALS['ecs']->table('back_order')." where supplier_id = {$supplier_id} and id = {$back_id} ";
			$info = $GLOBALS['db']->getRow($sql);
			
			if ($info) {
				$sql = "select * from ".$GLOBALS['ecs']->table('order')." where order_id = {$info['order_id']}";
		
				$item= $GLOBALS['db']->getRow($sql);
			
				if ($item["parent_id"]) {
					$sql = "select order_sn,order_amount from ".$GLOBALS['ecs']->table('order')." where order_id = {$item["parent_id"]}";
					$order = $GLOBALS['db']->getRow($sql);
					
					$info['order_sn']=$order['order_sn'];
					$info['order_amount']=$order['order_amount'];
				}else{
					$info['order_amount']=$info['total_amount'];
				}
				
				$sql = "select  count(id) from ".$GLOBALS['ecs']->table('back_order')." where order_sn = {$info['order_sn']}";			
				$number = $GLOBALS['db']->getOne($sql);
				
				if ($item['pay_code'] == "alipay") {
					require(ROOT_PATH.'includes/alipay/Alipay.php');
					$pay=new Alipay();
					$res=$pay->return_order($info['order_sn'],$info['total_amount'],$number);
					if($res=='1'){
						$sql=" update ".$GLOBALS['ecs']->table('back_order')." set status=6 , remark1='{$remark}'  where id={$info['id']}";
						$GLOBALS['db']->query($sql);
						$sql=" update ".$GLOBALS['ecs']->table('users')." set frozen_money = frozen_money - {$info['total_amount']}  where user_id={$user_id}";
						$GLOBALS['db']->query($sql);
						$sql=" update ".$GLOBALS['ecs']->table('account_log')." set frozen_money = frozen_money - {$info['total_amount']}  where order_sn='{$info['order_sn']}'";
						$GLOBALS['db']->query($sql);
						$sql=" update ".$GLOBALS['ecs']->table('order')." set order_status = 4  where order_sn='{$info['order_sn']}'";
						$GLOBALS['db']->query($sql);
						$results=array(
								'result' => '1',
								'info' => '退款成功',
								'status' => '6'
						);
					}else{
						$results=array(
								'result' => '0',
								'info' => '退款失败',
								'status' => '-1'
						);
					} 
				}else{
					$status=refund_for_weixin($info['id'],$info['order_sn'],$info['total_amount'],$info['order_amount'],'weixin');
					if($status['out_trade_no'] == $info['order_sn']){
						$sql=" update ".$GLOBALS['ecs']->table('back_order')." set status=6 , remark1='{$remark}'  where id={$info['id']}";
						$GLOBALS['db']->query($sql);
						$sql=" update ".$GLOBALS['ecs']->table('users')." set frozen_money = frozen_money - {$info['total_amount']}  where user_id={$user_id}";
						$GLOBALS['db']->query($sql);
						$sql=" update ".$GLOBALS['ecs']->table('account_log')." set frozen_money = frozen_money - {$info['total_amount']}  where order_sn='{$info['order_sn']}'";
						$GLOBALS['db']->query($sql);
						$sql=" update ".$GLOBALS['ecs']->table('order')." set order_status = 4  where order_sn='{$info['order_sn']}'";
						$GLOBALS['db']->query($sql);
						$results=array(
								'result' => '1',
								'info' => '退款成功',
								'status' => '6'
						);
					}else{
						$results=array(
								'result' => '0',
								'info' => '退款失败',
								'status' => '4'
						);
					}
				}
			}else{
						$results=array(
								'result' => '0',
								'info' => '不是您的退货单'
						);
					}
				
		}else{
			$user_id = $userinfo['userid'];
			$sql = "select supplier_id from ".$GLOBALS['ecs']->table('supplier')." where user_id = {$user_id} and is_designer = 1";
			$supplier_id = $GLOBALS['db']->getOne($sql);
			$sql = "select * from ".$GLOBALS['ecs']->table('back_order')." where supplier_id = {$supplier_id} and id = {$back_id} ";
			$info = $GLOBALS['db']->getRow($sql);
			if ($info) {
				$sql=" update ".$GLOBALS['ecs']->table('back_order')." set status=-1 , remark1='{$remark}'  where id={$info['id']}";
				$GLOBALS['db']->query($sql);
				
				$results=array(
						'result' => '1',
						'info' => '拒绝退款',
						'status' => '-1'
				);
			}else{
				$results=array(
						'result' => '0',
						'info' => '不是您的退货单'
				);
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
/**
 * 设计师收入明细
 */
elseif ($action=="income_breakdown"){
	if(!empty($userinfo['userid'])){
		$user_id = $userinfo['userid'];
		$page=!empty($_REQUEST['page']) ? $_REQUEST['page'] : 0 ;  //页码
		$size=!empty($_REQUEST['size']) ? $_REQUEST['size'] : 10 ;  //一页显示商品个数
		$begin = $page*$size;
		$limit = " LIMIT $begin,$size";
		$sql = "select cash_time,`desc`,user_money,order_sn from ".$GLOBALS['ecs']->table('account_log')." where user_id = {$user_id} and user_money!=0 order by cash_time desc {$limit}";
		$money_list = $GLOBALS['db']->getAll($sql);
		foreach ( $money_list as $key => $v){
			$money_list[$key]['cash_time'] =  "时间：".date("Y-m-d H:i",$v['cash_time']);
			if ($v['user_money']<0) {
				$money_list[$key]['Prompt'] = "账户支出";
				$money_list[$key]['desc'] = "去向：".$v['desc'];
			}
			if ($v['user_money']>0) {
				$money_list[$key]['user_money'] = '+'.$v['user_money'];
				$money_list[$key]['Prompt'] = "账户收入";
				$money_list[$key]['desc'] = "来源：订单编号 ".$v['order_sn'];
			}
			unset($money_list[$key]['order_sn']);
		}
		$sql = "select count(log_id) from ".$GLOBALS['ecs']->table('account_log')." where user_id = {$user_id} and user_money!=0";
		$list_count = $GLOBALS['db']->getOne($sql);
		$allpage=ceil($list_count/$size);
		$results=array(
				'result' => '1',
				'info' => '请求成功',
				'page' => $page,	//	当前页码
				'count' => $allpage,	//	总页数
				'size' => $size,	//	每页取得商品数据条数
				'income_breakdown' =>$money_list
		);
	}else{
		$results=array(
				'result' => '0',
				'info' => '请登录'
		);
	}
	exit($json->json_encode_ex($results));
}

/**
 * 设计师收到货确定按钮
 */
elseif ($action=="goods_receipt"){
	$user_id = $userinfo['userid'];
	$back_id= $_REQUEST['back_id'];
	if(!empty($user_id)&&!empty($back_id)){
		$sql = "select supplier_id from ".$GLOBALS['ecs']->table('supplier')." where user_id = {$user_id} and is_designer = 1";
		$supplier_id = $GLOBALS['db']->getOne($sql);
		$sql = "select * from ".$GLOBALS['ecs']->table('back_order')." where supplier_id = {$supplier_id} and id = {$back_id} ";
		$info = $GLOBALS['db']->getRow($sql);
		if ($info) {
			$sql=" update ".$GLOBALS['ecs']->table('back_order')." set status=4  where id={$info['id']}";
			$GLOBALS['db']->query($sql);
			$results=array(
					'result' => '1',
					'info' => '确定收货',
					'status' => '4'
			);
		}else{
			$results=array(
					'result' => '0',
					'info' => '不是您的退货单'
			);
		}
	}
	exit($json->json_encode_ex($results));
}

/**
 * 设计师重新发货操作
 */
elseif ($action=="re-dispatched"){
	$back_id=$_REQUEST['back_id'];
	$userid=$userinfo['userid'];
	$shipping_name=$_REQUEST['shipping_name'];
	$shipping_code=$_REQUEST['shipping_code'];
	$action_note = $shipping_name.":".$shipping_code;
	$now_time =time();
	$sql = "select supplier_id from ".$GLOBALS['ecs']->table('supplier')." where user_id ={$userid} and is_designer = 1";
	$supplier_id = $GLOBALS['db']->getOne($sql);
	$sql = "select order_id from ".$GLOBALS['ecs']->table('back_order')."  where id = {$back_id} ";
	$order_id = $GLOBALS['db']->getOne($sql);
	$sql = "select order_sn,user_id,consignee,country,province,city,district,address,zipcode,mobile,shipping_price from ".$GLOBALS['ecs']->table('order')."  where order_id = {$order_id} and pay_status = 1 and supplier_id ={$supplier_id}";
	$order = $GLOBALS['db']->getRow($sql);
	
	if ($order&&$shipping_name&&$shipping_code) {
		$data_field= "(order_id,order_sn,user_id,admin_id,consignee,zipcode,mobile,country,province,city,district,address,shipping_name,shipping_price,invoice_no,create_time,supplier_id)";
		$sql="insert INTO ".$GLOBALS['ecs']->table('shipping_order'). $data_field." values ({$order_id},{$order['order_sn']},{$order['user_id']},{$userid},'".$order['consignee']."','".$order['zipcode']."',{$order['mobile']},{$order['country']},{$order['province']},{$order['city']},{$order['district']},'".$order['address']."','{$shipping_name}',{$order['shipping_price']},'{$shipping_code}','{$now_time}','{$supplier_id}')";
		$GLOBALS['db']->query($sql);
		$sql=" update ".$GLOBALS['ecs']->table('back_order')." set status=5  where id={$back_id}";
		$GLOBALS['db']->query($sql);
		$data['shipping_name']  = ShippingName($shipping_code);
		queryExpress($data['shipping_name'],$shipping_code);
		$rs=array('result'=>'1','info'=>'重新发货成功');
		exit($json->json_encode_ex($rs));
	}else{
		$rs=array('result'=>'0','info'=>'参数有误');
		exit($json->json_encode_ex($rs));
	}
}
/**
 * 用户确认退货单收货
 */
elseif ($action=="confirm_receipt"){
	$back_id=$_REQUEST['back_id'];
	$userid=$userinfo['userid'];
	$sql = "select id from ".$GLOBALS['ecs']->table('back_order')."  where id = {$back_id} and user_id = {$userid} and status = 5";

	$id = $GLOBALS['db']->getOne($sql);
	if ($id) {
		$sql=" update ".$GLOBALS['ecs']->table('back_order')." set status=6  where id={$back_id}";
		$GLOBALS['db']->query($sql);
		$rs=array('result'=>'1','info'=>'收货成功');
		exit($json->json_encode_ex($rs));
	}else{
		$rs=array('result'=>'0','info'=>'参数有误');
		exit($json->json_encode_ex($rs));
	}	
}
/**
 * 测试
 */
elseif ($action=="asdf"){
	
}

 function ShippingName($code){
 	
 	$sql = "select value from ".$GLOBALS['ecs']->table('config')." where name ='express_key' and inc_type = 'shipping'";
 	$express_key = $GLOBALS['db']->getOne($sql);
 	
	$url='http://www.kuaidi100.com/autonumber/auto?num='.$code.'&key='.$express_key.'';

	$shipping_code = file_get_contents($url);

	$code = json_decode($shipping_code,true);

	return $code [0]['comCode'];
}

function queryExpress($postcom , $getNu) {


	$sql = "select value from ".$GLOBALS['ecs']->table('config')." where name ='express_key' and inc_type = 'shipping'";
 	$express_key = $GLOBALS['db']->getOne($sql);
 	$post_data = array();
	$post_data["schema"] = 'json' ;
	$notify_url = 'http://yilitong.com/index.php/Home/Api/receive_shipping';
	//callbackurl请参考callback.php实现，key经常会变，请与快递100联系获取最新key
	$post_data["param"] = '{"company":"'.$postcom.'", "number":"'.$getNu.'","from":"", "to":"", "key":"'.$express_key.'", "parameters":{"callbackurl":"'.$notify_url.'"}}';

	$url='http://www.kuaidi100.com/poll';
	$o="";
	foreach ($post_data as $k=>$v)
	{
		$o.= "$k=".urlencode($v)."&";       //默认UTF-8编码格式
	}

	$post_data=substr($o,0,-1);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		
	//Tell curl to write the response to a variable

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	// The maximum number of seconds to allow cURL functions to execute.

	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
		
	$curl_exec = curl_exec($ch);       //返回提交结果，格式与指定的格式一致（result=true代表成功）
		
	// 关闭cURL资源，并且释放系统资源
	curl_close($ch);
	return $curl_exec;
}

//获取省市县名称
function get_regin_name($id){
	$sql="select name from ".$GLOBALS['ecs']->table('region')." where id={$id}";
	return $GLOBALS['db']->getOne($sql);
}
//获取地区id
function get_address_id($name){
	$sql = "select id from ".$GLOBALS['ecs']->table('region')." where name='".$name."'";
	$id = $GLOBALS['db']->getOne($sql);
	return $id;
}
//获取supplier_id 
function get_supplier_id($user_id){
	$sql = "select supplier_id from ".$GLOBALS['ecs']->table('supplier')." where user_id=".$user_id." and is_designer = 1";
	$id = $GLOBALS['db']->getOne($sql);
	return $id;
}
function get_works($works_id){
	$sql="select collect_count,click_count,designer_name,works_img,works_name,cat_name,user_id,works_content,supplier_id from ".$GLOBALS['ecs']->table('works')." where works_id={$works_id}";
	return $GLOBALS['db']->getRow($sql);
}
//将2天内为付款的设计师订单改为失效订单状态
function check_nopay_order($supplier_id){
	$sql="select A.order_id,A.order_status,A.pay_status,A.add_time,B.prom_type,B.prom_id from ".$GLOBALS['ecs']->table('order')." as A LEFT JOIN ".$GLOBALS['ecs']->table('order_goods')." as B ON A.order_id=B.order_id where A.supplier_id={$supplier_id} AND A.order_status=0 AND A.pay_status=0";
	$list=$GLOBALS['db']->getAll($sql);
	$temptime=time();
	if(!empty($list)){
		foreach ($list as $order){
			if($temptime>$order['add_time']+48*3600){
				//修改状态
				$sql=" update ".$GLOBALS['ecs']->table('order')." set order_status=3 where order_id={$order['order_id']}";
				$GLOBALS['db']->query($sql);
			}
		}
	}
}

//设计师后台订单列表
function get_supplier_order_list($supplier_id,$type,$page=0,$size=10){
	$rs=array();
	$begin = $page*$size;
	$limit = " LIMIT $begin,$size";
	//$type状态 0 为待付款，1待发货，2为待收货，3为待评价，4为已完成，5为已取消，
	$where=" where O.supplier_id={$supplier_id} AND O.is_parent !=1 AND ";
	switch ($type)
	{
		case 0:
	  		$where.=" O.order_status=0 AND O.pay_status = 0 ";
	  		break;
		case 1:
			$where.=" O.pay_status=1 AND O.shipping_status !=1 AND O.order_status in(0,1)";
	  	break;
		case 2:
	 	 	$where.=" O.order_status=1 AND O.shipping_status=1";
	  		break;
		case 3:
	  		$where.=" O.order_status=2 ";
	  		break;
	 	case 4:
		 	$where.=" O.order_status=4 ";
		 	break;
	 	case 5:
		 	$where.=" O.order_status=3 ";
		 	break;
	 	default:
	  		$where.=" O.order_status in (0,1,2,3,4) ";
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
			$rs=get_order_goodByID($val['order_id'],$val['user_id'],$list[$key]['order_status']);
			$goods_num+=$rs['goods_num'];
			//增加订单的数量
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
function get_comment_type($order_id,$user_id,$good_id){
	$sql="SELECT count(*) from ".$GLOBALS['ecs']->table('comment')." where order_id={$order_id}  AND user_id={$user_id} AND goods_id={$good_id}";
	return $GLOBALS['db']->getOne($sql);
}
//获取退货，退钱订单状态
function get_back_order($order_id,$goods_id,$user_id,$spec_key=''){
	if(!empty($spec_key)){
		//待发货状态
		$sql="select status from ".$GLOBALS['ecs']->table('back_order')." where order_id={$order_id} AND goods_id={$goods_id} AND user_id={$user_id}  AND spec_key='{$spec_key}' ";
	}else{
		$sql="select status from ".$GLOBALS['ecs']->table('back_order')." where order_id={$order_id} AND goods_id={$goods_id} AND user_id={$user_id} ";
	}
	return $GLOBALS['db']->getOne($sql);
}

//获取订单详情信息
function get_order_info2($supplier_id,$order_id){
	$file="O.order_id,O.user_note,O.order_sn,O.user_id,O.order_status,O.shipping_status,O.pay_status,O.consignee,O.province,O.city,O.district,O.address,O.mobile,O.shipping_code,O.goods_price,O.shipping_price,O.order_amount,O.add_time";
	$sql="select $file from ".$GLOBALS['ecs']->table('order')." as O where O.order_id={$order_id} AND supplier_id={$supplier_id}";
	$order=$GLOBALS['db']->getRow($sql);
	$sql = "select shipping_name,invoice_no from " .$GLOBALS['ecs']->table('shipping_order')." where order_id = {$order["order_id"]}";
	$shipping =$GLOBALS['db']->getRow($sql);

	if(!empty($order['province'])){
		$order['province_name']=get_regin_name($order['province']);
	}
	if(!empty($order['city'])){
		$order['city_name']=get_regin_name($order['city']);
	}
	if(!empty($order['district'])){
		$order['district_name']=get_regin_name($order['district']);
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
	$order['goods_list']=get_order_goods2($order_id,$order['user_id'],$order['order_status']);
	$order['number']=$order['goods_list']['goods_num'];
	$order['add_time']=date("Y-m-d H:i",$order['add_time']);
	if($order['goods_list'][0]['supplier_id']=='18' || empty($order['goods_list'][0]['supplier_mobile'])){
		$supplier_mobile='400-089-7879';
	}else{
		$supplier_mobile=$order['goods_list'][0]['supplier_mobile'];
	}
	$sql = "select user_id from ".$GLOBALS['ecs']->table('supplier')." where supplier_id = {$supplier_id}";
	$user_id=$GLOBALS['db']->getOne($sql);
	$sql = "select mobile from ".$GLOBALS['ecs']->table('users')." where user_id = {$user_id}";
	$supplier_mobile=$GLOBALS['db']->getOne($sql);
	$order['customer_phone']=$supplier_mobile;
	unset($order['goods_list']['goods_num']);
	if ($shipping['shipping_name']) {
		$order['shipping_name'] = $shipping['shipping_name'];
	}else{
		$order['shipping_name']="";
	}if ($shipping['invoice_no']) {
		$order['shipping_code'] = $shipping['invoice_no'];
	}else{
		$order['shipping_code'] = "";
	}

	return $order;

}

function get_order_goods2($id,$user_id="",$order_type=""){
	$file="A.goods_id,A.goods_name,A.goods_sn,A.goods_num,A.market_price,A.goods_price,A.spec_key,A.spec_key_name,A.is_send,A.delivery_id,A.send_number,B.supplier_id,B.original_img";
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
			$number+=$val['goods_num'];
			if(!empty($val['supplier_id'])){
				if(!empty($val['supplier_id'])){
					$row=get_supplier($val['supplier_id']);
					$arr[$val['supplier_id']]['supplier_id']=$val['supplier_id'];
					$arr[$val['supplier_id']]['supplier_name']=$row['supplier_name'];
					$arr[$val['supplier_id']]['company_name']=$row['supplier_name'];
					$arr[$val['supplier_id']]['supplier_mobile']=$row['mobile'];
				}else{
					$arr[$val['supplier_id']]['supplier_id']="";
					$arr[$val['supplier_id']]['supplier_name']="";
					$arr[$val['supplier_id']]['company_name']="";
					$arr[$val['supplier_id']]['supplier_mobile']="";
				}



				if($order_type=="1" || $order_type=="2" || $order_type=="3"){
					$status=get_back_order($id,$val['goods_id'],$user_id,$val['spec_key']);
					$val['status']=$status;
				
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
				$val['status']="6";
				}

				$arr[$val['supplier_id']]['list'][$key]=$val;
				$arr[$val['supplier_id']]['list'][$key]['original_img']=!empty($val['original_img']) ? IMG_HOST.$val['original_img'] : '';
			}else{
				$rs[$key]['supplier_id']="";
				$rs[$key]['supplier_name']="";
				$rs[$key]['company_name']="";
				if(empty($val['supplier_id'])){
					$val['supplier_id']="";
				}
				if(!empty($val['original_img'])){
					$val['original_img']=IMG_HOST.$val['original_img'];
				}else{
					$val['original_img']="";
				}
				if($order_type=='1' || $order_type=='2' || $order_type=='3'){
					$status=get_back_order($id,$val['goods_id'],$user_id,$val['spec_key']);
					$val['status']=$status;//!empty($status) ? $status : "0";
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
				$val['status']="";
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

//设计师信息
function get_supplier($id,$files=""){
	$file=!empty($files) ? $files : "A.supplier_id,A.user_id,A.supplier_name,A.company_name,A.business_sphere,B.mobile";
	$sql="select {$file} from ".$GLOBALS['ecs']->table('supplier')." as A left join ".$GLOBALS['ecs']->table('supplier_user')." AS B on B.admin_id=A.supplier_id  where A.supplier_id={$id}";
	return $GLOBALS['db']->getRow($sql);
}

//检测是否存在活动订单
function check_active_order($order_id){
	$sql="select count(*) from ".$GLOBALS['ecs']->table('order_goods')." where order_id={$order_id} AND prom_id !=0 AND prom_type !=0";
	return $GLOBALS['db']->getOne($sql);
}

/**
 * 微信退款
 * out_refund_no 商户内部唯一退款单号  back_id
 * out_trade_no 退款订单号	order_sn
 * refund_fee 退款金额	  shop_price
 * total_fee 订单总金额		total_amount
 */
function refund_for_weixin($out_refund_no,$out_trade_no,$refund_fee,$total_fee,$code)
{
	$refund_fee = $refund_fee * 100; //以分为单位
	$total_fee = $total_fee * 100;
	$sql="select * from ".$GLOBALS['ecs']->table('plugin')." where code='weixin' AND type = 'payment' ";

	$paymentPlugin=  $GLOBALS['db']->getRow($sql);
	$config_value = unserialize($paymentPlugin['config_value']); // 配置反序列化
	$appid = $config_value['appid']; // * APPID：绑定支付的APPID
	$mchid = $config_value['mchid']; // * MCHID：商户号
	$key = $config_value['key']; // KEY：商户支付密钥
	
	// 微信退款签名
	$ref= strtoupper(md5("appid=$appid&mch_id=$mchid&nonce_str=6&op_user_id=$mchid"
			. "&out_refund_no=$out_refund_no&out_trade_no=$out_trade_no&refund_fee=$refund_fee&total_fee=$total_fee"
			. "&key=$key"));//sign加密MD5

	$refund=array(
	'appid'=>$appid,//应用ID，固定
	'mch_id'=>$mchid,//商户号，固定
	'nonce_str'=>'6',//随机字符串
	'op_user_id'=> $mchid ,//操作员
	'out_refund_no'=>$out_refund_no,//商户内部唯一退款单号
	'out_trade_no'=>$out_trade_no,//商户订单号,pay_sn码 1.1二选一,微信生成的订单号，在支付通知中有返回
	'refund_fee'=>$refund_fee,//退款金额
		  'total_fee'=>$total_fee,//总金额
		  //'transaction_id'=>$out_trade_no,//微信订单号 1.2二选一,商户侧传给微信的订单号
		  'sign'=>$ref//签名
	);
	
	$url="https://api.mch.weixin.qq.com/secapi/pay/refund";;//微信退款地址，post请求
	   $xml=arrayToXml($refund);

	$ch=curl_init();
	//超时时间
	curl_setopt($ch,CURLOPT_TIMEOUT,30);
	curl_setopt($ch,CURLOPT_URL,$url);
	curl_setopt($ch,CURLOPT_HEADER,false);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,1);//证书检查
	// curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,1);//严格校验
	curl_setopt($ch,CURLOPT_SSLCERTTYPE,'pem');
	curl_setopt($ch,CURLOPT_SSLCERT,dirname(__FILE__).'/cert/apiclient_cert.pem');
	curl_setopt($ch,CURLOPT_SSLCERTTYPE,'pem');
	   curl_setopt($ch,CURLOPT_SSLKEY,dirname(__FILE__).'/cert/apiclient_key.pem');
	   curl_setopt($ch,CURLOPT_POST,1);
	   curl_setopt($ch,CURLOPT_POSTFIELDS,$xml);
	   $data=curl_exec($ch);

	   if($data){ //返回来的是xml格式需要转换成数组再提取值，用来做更新
	 
		  curl_close($ch);
		  //禁止引用外部xml实体
		  $xml =simplexml_load_string($data,'SimpleXMLElement', LIBXML_NOCDATA);

		  $data = json_decode(json_encode($xml),TRUE);

		  		return $data;
	   }else{
	  
		  $error=curl_errno($ch);
		  echo "curl 出错，错误代码：$error"."<br/>";
		  curl_close($ch);
		  return false;
	   }
	}
    function arrayToXml($arr){
		$xml = "<xml>";
		foreach ($arr as $key=>$val){
			if(is_array($val)){
				$xml.="<".$key.">".arrayToXml($val)."</".$key.">";
			}else{
				$xml.="<".$key.">".$val."</".$key.">";
			}
		}
		$xml.="</xml>";
	
		return $xml ;
		 
	}
?>