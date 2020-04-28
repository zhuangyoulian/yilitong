<?php
define('IN_ECS', true);
require('init.php');
$affiliate = unserialize($GLOBALS['_CFG']['affiliate']);
header("Content-Type:text/html;charset=UTF-8");
$action  = $_REQUEST['act'];
$ticket = $_REQUEST['ticket'];
$userInfo = '';
if(!empty($ticket)){
	$userInfo = split_user_ticket($ticket);
}else{
	$rs=array('result'=>'0','info'=>'请您先登录');
		exit($json->json_encode_ex($rs));
}
// 查询用户状态 是否显示业务列表
if($action=='user_status'){
	
	$data = array();
	
	$user_id = $userInfo['userid'];
	$app_name = $_REQUEST['app_name'] ? $_REQUEST['app_name'] : '';
	$version_number = $_REQUEST['version_number'] ? $_REQUEST['version_number'] : '';
	
	$sql="select business_level from ".$GLOBALS['ecs']->table('users')." where user_id={$userInfo['userid']}";
	$business_level = $GLOBALS['db']->getOne($sql);
	//不是业务员
	if(!$business_level || $business_level =='0'){
		$data['bank_status'] = '0';
		$data['rank_4'] = '1';
		$data['rank_5'] = '1';
		$data['team_status'] = '0';
		$data['shop_status'] = '0';
		
	}else{
		//是业务员
		if($business_level > '0'){
			$data['bank_status'] = '1';
			$data['rank_4'] = '0';
			$data['rank_5'] = '0';
			$data['team_status'] = '1';
			$data['shop_status'] = '0';
			if($business_level == 5){
				$data['team_status'] = '0';
				$data['shop_status'] = '1';
			}
			
		}
		
	}
	//IOS审核期间
	if($app_name == 'ylt_ios'){
		$sql = "select * from ".$GLOBALS['ecs']->table('app_version')." where app_name='".$app_name."'order by id desc ";
		$check = $GLOBALS['db']->getRow($sql);
		$number = str_replace('.','',$check['version_number']);
		$version_number = str_replace('.','',$version_number);
		if($version_number > $number){
			
			$data['user_Moneys'] = '0';
			$data['bank_status'] = '0';
			$data['rank_4'] = '0';
			$data['rank_5'] = '0';
			$data['team_status'] = '0';
			$data['shop_status'] = '0';
			
		}

	}
	

	if($user)
		exit($json->json_encode_ex(['result'=>'1','info'=>$data]));
	
	
}
// 查询用户余额
elseif($action=='user_balance'){
	
	$user_id = $userInfo['userid'];
	
	$sql="select user_id,bean_gift,cash_gift,sale_gift,open_gift from ".$GLOBALS['ecs']->table('users')." where user_id={$userInfo['userid']}";
	$users = $GLOBALS['db']->getRow($sql);
	
	
	exit($json->json_encode_ex(['result'=>'1','info'=>$users]));
	
	
}
// 查询入驻状态
elseif($action=='ettled_status'){
	
	$agent_rank = $_REQUEST['agent_rank'] ? intval($_REQUEST['agent_rank']) : 5; //业务等级 4,业务经理，5,礼品店主
	
	$sql="select mobile,parent_id,agent_rank,bond_number,status from ".$GLOBALS['ecs']->table('busines_manager')." where FUid='{$userInfo['userid']}' and agent_rank='{$agent_rank}' ";
	$user = $GLOBALS['db']->getRow($sql);
	if($user)
		exit($json->json_encode_ex(['result'=>'1','info'=>'1','bond_number'=>$user['bond_number']]));
	else
		exit($json->json_encode_ex(['result'=>'1','info'=>'0']));
	
}
//入驻信息填写
elseif($action=='busines_ettled'){
	

	$agent_rank = intval($_REQUEST['agent_rank']); //业务等级 4,业务经理，5,礼品店主
	$province = get_regin_nameByID($_REQUEST['province']);
	$city = get_regin_nameByID($_REQUEST['city']);
	$district = get_regin_nameByID($_REQUEST['district']);
	$address   = $_REQUEST['address'];	//详细地址
	$operating_name   = $_REQUEST['operating_name'];	//负责人姓名
	$operating_id   = trim($_REQUEST['operating_id']);	//负责人身份证号码
	$identity_positive   = $_REQUEST['identity_positive'];	//身份证正面
	$identity_opposite   = $_REQUEST['identity_opposite'];	//身份证反面

	
	$sql="select FUid from ".$GLOBALS['ecs']->table('busines_manager')." where FUid={$userInfo['userid']}";
	$rew = $GLOBALS['db']->getOne($sql);
	
	if($rew)
		exit($json->json_encode_ex(['result'=>'0','info'=>'请勿重复申请']));
		//$GLOBALS['db']->query("delete from ".$GLOBALS['ecs']->table('busines_manager')." where FUid={$userInfo['userid']}");
	
	$sql="select mobile,parent_id,business_level from ".$GLOBALS['ecs']->table('users')." where user_id={$userInfo['userid']}";
	$user = $GLOBALS['db']->getRow($sql);
	
	if($user['business_level'] > 0)
		exit($json->json_encode_ex(['result'=>'0','info'=>'该账户已在业务体系之内']));
		
	$sql="select bond_number from ".$GLOBALS['ecs']->table('busines_rank')." where rank_id={$agent_rank}";
	$bond_number = $GLOBALS['db']->getOne($sql);//保证金
		
	
	$parent_id = $user['parent_id'];	//推荐人
	
	$data=array(
					'FUid'=>$userInfo['userid'],
					'mobile'=>$user['mobile'],
					'recommend_code'=>'us'.$userInfo['userid'],
					'agent_rank'=>$agent_rank,
					'province'=>$province,
					'city'=>$city,
					'district'=>$district,
					'operating_name'=>$operating_name,
					'operating_id'=>$operating_id,
					'address'=>$address,
					'identity_positive'=>$identity_positive,
					'identity_opposite'=>$identity_opposite,
					'reg_time'=>time(),
					'bond_number'=>$bond_number,
					'parent_id'=>$parent_id,
						
			);
			$recommend_code ='us'.$userInfo['userid'];
			$time = time();
	/* 插入入驻数据 */
	//$row = $GLOBALS['db']->autoExecute($ecs->table('busines_manager'),$data,'insert');
	//$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('busines_manager'),$data,'insert');

		/* 插入订单商品 */
	$sql = "INSERT INTO " . $ecs->table('busines_manager') . "( " .
			"FUid, mobile, recommend_code, agent_rank, province, city,district,operating_name,operating_id,address,identity_positive,identity_opposite,".
			"reg_time,bond_number,parent_id) values('{$userInfo['userid']}', '{$user['mobile']}', '{$recommend_code}', '{$agent_rank}', '{$province}', '{$city}','{$district}','{$operating_name}','{$operating_id}','{$address}','{$identity_positive}','{$identity_opposite}','{$time}','{$bond_number}','{$parent_id}')";

	$row=$GLOBALS['db']->query($sql);
	
	if($row)
		exit($json->json_encode_ex(['result'=>'1','info'=>'申请成功','bond_number'=>$bond_number]));
	else
		exit($json->json_encode_ex(['result'=>'1','info'=>'请稍后提交']));
}
// 支付保证金数据
elseif($action=='payment'){
	

	$sql="select mobile,parent_id,agent_rank,bond_number from ".$GLOBALS['ecs']->table('busines_manager')." where FUid={$userInfo['userid']}";
	$user = $GLOBALS['db']->getRow($sql);
	$order_sn = 'us'.time().substr($user['mobile'],7);
	
	if(empty($user)){
		$rs=array('result'=>'1','info'=>'数据错误');
		exit($json->json_encode_ex($rs));
	}
	if($user['agent_rank'] == '4')
		$transaction_name = '业务经理入驻一礼通';
	else
		$transaction_name = '礼品店主入驻一礼通';
	

	$data=array(
					'order_sn'=>$order_sn,
					'user_id'=>$userInfo['userid'],
					'mobile'=>$userInfo['mobile'],
					'transaction_name'=>$transaction_name,
					'pay_code'=>'',
					'pay_name'=>'',
					'total_amount'=>$user['bond_number'],
					'order_amount'=>$user['bond_number'],
					'add_time'=>time(),
					'agent_rank'=>$user['agent_rank'],		
			);
	$time = time();
	
		/* 插入订单数据 */
	$sql = "INSERT INTO " . $ecs->table('entry_order') . "( " .
			"order_sn, user_id, mobile, total_amount,order_amount, add_time, agent_rank) values('{$order_sn}','{$userInfo['userid']}','{$userInfo['mobile']}','{$user['bond_number']}','{$user['bond_number']}','{$time}','{$user['agent_rank']}')";

	$row=$GLOBALS['db']->query($sql);
	
	if($row)
		exit($json->json_encode_ex(['result'=>'1','info'=>$data]));
	else
		exit($json->json_encode_ex(['result'=>'1','info'=>'请求失败']));
	
}
//1礼豆，2礼金，3销售奖，4开发奖
elseif($action == 'gift_info'){
	$data = array();
	
	$type=!empty($_REQUEST['type']) ? intval($_REQUEST['type']) : 1 ;

	$sql="select bean_gift,cash_gift,sale_gift,open_gift from ".$GLOBALS['ecs']->table('users')." where user_id={$userInfo['userid']} ";
	$userGift = $GLOBALS['db']->getRow($sql);
	
	switch ($type)
	{
	case '1':
		$data['surplus'] = $userGift['bean_gift'];
		$total = "select SUM(bean_gift) as total from ".$GLOBALS['ecs']->table('bean_gift_log')." where change_type = 1 and user_id ={$userInfo['userid']}";
		$consumption = "select SUM(bean_gift) as total from ".$GLOBALS['ecs']->table('bean_gift_log')." where change_type = 2 and user_id ={$userInfo['userid']}";
		break;
	case '2':
		$data['surplus'] = $userGift['cash_gift'];
		$total = "select SUM(cash_gift) as total from ".$GLOBALS['ecs']->table('cash_gift_log')." where change_type = 1 and user_id ={$userInfo['userid']}";
		$consumption = "select SUM(cash_gift) as total from ".$GLOBALS['ecs']->table('cash_gift_log')." where change_type = 2 and user_id ={$userInfo['userid']}";
		break;
	case '3':
		$data['surplus'] = $userGift['sale_gift'];
		$total = "select SUM(sale_gift) as total from ".$GLOBALS['ecs']->table('sale_gift_log')." where change_type = 1 and user_id ={$userInfo['userid']}";
		$consumption = "select SUM(sale_gift) as total from ".$GLOBALS['ecs']->table('sale_gift_log')." where change_type = 2 and user_id ={$userInfo['userid']}";
		break;
	case '4':
		$data['surplus'] = $userGift['open_gift']; 
		$total = "select SUM(open_gift) as total from ".$GLOBALS['ecs']->table('open_gift_log')." where change_type = 1 and user_id ={$userInfo['userid']}";
		$consumption = "select SUM(open_gift) as total from ".$GLOBALS['ecs']->table('open_gift_log')." where change_type = 2 and user_id ={$userInfo['userid']}";
		break;
	default:
		
	}
	$tatal = $GLOBALS['db']->getOne($total);
	$consumption = $GLOBALS['db']->getOne($consumption);
	$data['total'] = $tatal ? $tatal : '0.00';	//总额
	$data['consumption'] = $consumption ? $consumption : '0.00'; //消费额度
	
	
	if($data)
		exit($json->json_encode_ex(['result'=>'1','info'=>$data]));
	else
		exit($json->json_encode_ex(['result'=>'1','info'=>'请求失败']));
	
	
}
//1礼豆，2礼金，3销售奖，4开发奖 列表
elseif($action == 'gift_list'){
	
	$page=!empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 0 ;
	$type=!empty($_REQUEST['type']) ? intval($_REQUEST['type']) : 1 ;
	$change_type=!empty($_REQUEST['status']) ? intval($_REQUEST['status']) : '' ; //''全部 ，1正数 2负数
	$size =10;
	$begin = $page*$size;
	$limit = " LIMIT $begin,$size";

	switch ($type)
	{
	case '1':
		if($change_type)
			$change_type = " and change_type = {$change_type}";
		$total = "select * from ".$GLOBALS['ecs']->table('bean_gift_log')." where user_id ={$userInfo['userid']} {$change_type} order by log_id desc {$limit}";
		break;
	case '2':
		if($change_type)
			$change_type = " and change_type = {$change_type}";
		$total = "select * from ".$GLOBALS['ecs']->table('cash_gift_log')." where user_id ={$userInfo['userid']} {$change_type} order by log_id desc {$limit}";
		break;
	case '3':
		if($change_type)
			$change_type = " and change_type = {$change_type}";
		$total = "select * from ".$GLOBALS['ecs']->table('sale_gift_log')." where user_id ={$userInfo['userid']} {$change_type} order by log_id desc {$limit}";
		break;
	case '4':
		if($change_type)
			$change_type = " and change_type = {$change_type}";
		$total = "select * from ".$GLOBALS['ecs']->table('open_gift_log')." where user_id ={$userInfo['userid']} {$change_type} order by log_id desc {$limit}";
		break;
	default:
	}
	
	$gift_list = $GLOBALS['db']->getAll($total);


	
	//if($gift_list)
		exit($json->json_encode_ex(['result'=>'1','info'=>$gift_list]));
	//else
	//	exit($json->json_encode_ex(['result'=>'1','info'=>'']));
	
	
}
// 添加银行卡
elseif($action == 'add_bank'){
	
	$user_id = $userInfo['userid'];
	$bank_account_name = trim($_REQUEST['bank_account_name']);	//开户名称
	$card_number = trim($_REQUEST['card_number']);	//银行卡号
	$bank_name = trim($_REQUEST['bank_name']);	//银行名称
	$branch_name = trim($_REQUEST['branch_name']); //支行名称

	$province = get_regin_nameByID($_REQUEST['province']);
	$city = get_regin_nameByID($_REQUEST['city']);
	$district = get_regin_nameByID($_REQUEST['district']);
	
	$sql = "UPDATE ".$GLOBALS['ecs']->table('bank_card')." SET `is_default`='0' WHERE user_id={$user_id}";
	$row=$GLOBALS['db']->query($sql); 
		/* 插入订单数据 */
	$sql = "INSERT INTO " . $ecs->table('bank_card') . "(user_id, bank_account_name, card_number, bank_name,branch_name, province, city, district, is_default) values ".
	"('{$user_id}','{$bank_account_name}','{$card_number}','{$bank_name}','{$branch_name}','{$province}','{$city}','{$district}','1')";

	$row=$GLOBALS['db']->query($sql);
	
	if($row)
		exit($json->json_encode_ex(['result'=>'1','info'=>'添加成功']));
	else
		exit($json->json_encode_ex(['result'=>'0','info'=>'添加失败']));

}
// 默认银行卡
elseif($action == 'bank_default'){
	
	$user_id = $userInfo['userid'];
	$type = 1;

	$sql="select bank_id,user_id,bank_account_name,card_number,bank_name,is_default from ".$GLOBALS['ecs']->table('bank_card')." where user_id={$user_id} and is_default = 1";
	$bankInfo = $GLOBALS['db']->getRow($sql);
	
	if(!$bankInfo){
		$bankInfo = $GLOBALS['db']->getRow("select bank_id,user_id,bank_account_name,card_number,bank_name,is_default from ".$GLOBALS['ecs']->table('bank_card')." where user_id={$user_id} ");
	}
	if(!$bankInfo){
		$type = 0;
	}else{
		$bankInfo['card_number'] = substr_replace($bankInfo['card_number'],'***********',4,11);
	}
		
	
	exit($json->json_encode_ex(['result'=>'1','info'=>$bankInfo,'type'=>$type]));
	
}
// 选择默认银行卡
elseif($action == 'is_default_bank'){
	
	$user_id = $userInfo['userid'];
	
	$bank_id = intval($_REQUEST['bank_id']) ? intval($_REQUEST['bank_id']) : '';
	
	$sql = "UPDATE ".$GLOBALS['ecs']->table('bank_card')." SET `is_default`='0' WHERE user_id={$user_id}";
	$row=$GLOBALS['db']->query($sql);
	$sql = "UPDATE ".$GLOBALS['ecs']->table('bank_card')." SET `is_default`='1' WHERE user_id={$user_id} and bank_id = {$bank_id}";
	$row=$GLOBALS['db']->query($sql);
	
	$sql="select bank_id,user_id,bank_account_name,card_number,bank_name,is_default from ".$GLOBALS['ecs']->table('bank_card')." where user_id={$user_id} ";
	$bank_list = $GLOBALS['db']->getAll($sql);
	
	exit($json->json_encode_ex(['result'=>'1','info'=>$bank_list]));
	

	
}
// 解绑银行卡
elseif($action == 'delete_bank'){
	
	$user_id = $userInfo['userid'];
	
	$bank_id = intval($_REQUEST['bank_id']) ? intval($_REQUEST['bank_id']) : '';

	$sql = "DELETE FROM".$GLOBALS['ecs']->table('bank_card')."  WHERE user_id={$user_id} and bank_id = {$bank_id}";
	$row=$GLOBALS['db']->query($sql);
	
	$sql="select bank_id,user_id,bank_account_name,card_number,bank_name,is_default from ".$GLOBALS['ecs']->table('bank_card')." where user_id={$user_id} ";
	$bank_list = $GLOBALS['db']->getAll($sql);
	
	exit($json->json_encode_ex(['result'=>'1','info'=>$bank_list]));
	
}
// 银行卡列表
elseif($action == 'bank_list'){
	
	$user_id = $userInfo['userid'];
	
	$sql="select bank_id,user_id,bank_account_name,card_number,bank_name,is_default from ".$GLOBALS['ecs']->table('bank_card')." where user_id={$user_id} ";
	
	$bank_list = $GLOBALS['db']->getAll($sql);
	
	foreach($bank_list as $k => $v) {
		$bank_list[$k]['card_number'] = substr_replace($v['card_number'],'***********',4,11);

	}
	
	exit($json->json_encode_ex(['result'=>'1','info'=>$bank_list]));
	
}
//业务提现 
elseif($action == 'busines_withdrawals'){
	
	$user_id = $userInfo['userid'];
	
	$total_amount = $_REQUEST['total_amount'] ? trim($_REQUEST['total_amount']) : '';	//提现金额
	$mobile = $_REQUEST['mobile'] ? trim($_REQUEST['mobile']) : '';	//手机号
	$code = $_REQUEST['code'] ? trim($_REQUEST['code']) : '';	//短信验证码
	$type = $_REQUEST['type'] ? intval($_REQUEST['type']) : 1 ; //1礼豆，2礼金，3销售奖，4开发奖
	$bank_id = intval($_REQUEST['bank_id']) ? intval($_REQUEST['bank_id']) : ''; //银行卡ID
	
	if(!$total_amount || !$mobile || !$code || !$bank_id)
		exit($json->json_encode_ex(['result'=>'1','info'=>'请输入完整信息']));
	
	if($total_amount < '108')
		exit($json->json_encode_ex(['result'=>'1','info'=>'提现金额没有达到最低提现值']));
	
	
	include_once('includes/cls_sms1.php');
	$sms = new sms1();
	// 验证短信验证码
	$Verification = $sms->send_Verification($mobile,$code);
	if($Verification['status'] != '1')
		exit($json->json_encode_ex(['result'=>'0','info'=>$Verification['info']]));
	
	$sql="select bank_account_name,branch_name,card_number from ".$GLOBALS['ecs']->table('bank_card')." where user_id={$user_id} and bank_id = {$bank_id} ";
	$bank = $GLOBALS['db']->getRow($sql);
	
	if(!$bank)
		exit($json->json_encode_ex(['result'=>'1','info'=>'请选择银行卡']));
	
	//获取用户余额
	$sql="select user_id,bean_gift,cash_gift,sale_gift,open_gift from ".$GLOBALS['ecs']->table('users')." where user_id={$userInfo['userid']}";
	$users = $GLOBALS['db']->getRow($sql);
	
	$card_number = substr_replace($bank['card_number'],'***********',4,11);
	$payee = $bank['bank_account_name'].$card_number;
	$actual_amount = sprintf('%.2f',($total_amount / 1.08)); //实际金额
	$service_amout = ($total_amount - $actual_amount);	//手续费
	$time = time();
	$business_type = '提现';
	$desc = '提现'.$total_amount.',手续费'.$service_amout.',实际数量为'.$actual_amount;
	
	//1礼豆，2礼金，3销售奖，4开发奖
	switch ($type)
	{
	case '1':
		if($users['bean_gift'] < $total_amount){
			exit($json->json_encode_ex(['result'=>'1','info'=>'余额不足']));
		}
		break;
	case '2':
		if($users['cash_gift'] < $total_amount){
			exit($json->json_encode_ex(['result'=>'1','info'=>'余额不足']));
		}
		break;
	case '3':
		if($users['sale_gift'] < $total_amount){
			exit($json->json_encode_ex(['result'=>'1','info'=>'余额不足']));
		}
		break;
	case '4':
		if($users['open_gift'] < $total_amount){
			exit($json->json_encode_ex(['result'=>'1','info'=>'余额不足']));
		}
		break;
	default:
	}
	
		
	/* 插入订单数据 */
	$sql = "INSERT INTO " . $ecs->table('busines_withdrawals') . "(total_amount, actual_amount, service_amout, type,user_id, payee, card_number, branch_name,add_time, mobile, remarks) values ".
	"('{$total_amount}','{$actual_amount}','{$service_amout}','{$type}','{$user_id}','{$bank[bank_account_name]}','{$bank[card_number]}','{$bank[branch_name]}','{$time}','{$mobile}','{$desc}')";

	$row=$GLOBALS['db']->query($sql);
	
	$trading_id =  $GLOBALS['db']->getOne("select trading_id from ".$GLOBALS['ecs']->table('busines_withdrawals')." where user_id = '{$user_id}' and add_time={$time}");
	//1礼豆，2礼金，3销售奖，4开发奖
	switch ($type)
	{
	case '1':
		$row = $GLOBALS['db']->query("UPDATE ".$GLOBALS['ecs']->table('users')." SET bean_gift = bean_gift - $total_amount WHERE user_id={$user_id}");
		if($row){
			/* 插入订单数据 */
			$sql = "INSERT INTO " . $ecs->table('bean_gift_log') . "(user_id, bean_gift, change_time, change_type, business_type,`desc`,trading_id) values ('{$user_id}','-{$total_amount}','{$time}','2','{$business_type}','{$desc}','{$trading_id}')";

			$row=$GLOBALS['db']->query($sql);
			
		}
		
		break;
	case '2':
		$row = $GLOBALS['db']->query("UPDATE ".$GLOBALS['ecs']->table('users')." SET cash_gift = cash_gift - $total_amount WHERE user_id={$user_id}");
		
		if($row){
			/* 插入订单数据 */
			$sql = "INSERT INTO " . $ecs->table('cash_gift_log') . "(user_id, cash_gift, change_time, change_type, actual_amount, business_type, payee,`desc`,trading_id) values ('{$user_id}','-{$total_amount}','{$time}','2','{$actual_amount}','{$business_type}','{$payee}','{$desc}','{$trading_id}')";

			$row=$GLOBALS['db']->query($sql);
		}
		
		break;
	case '3':
		$row = $GLOBALS['db']->query("UPDATE ".$GLOBALS['ecs']->table('users')." SET sale_gift = sale_gift - $total_amount WHERE user_id={$user_id}");
		
		if($row){
			/* 插入订单数据 */
			$sql = "INSERT INTO " . $ecs->table('sale_gift_log') . "(user_id, sale_gift, change_time, change_type, business_type, payee, `desc`, trading_id) values ('{$user_id}','-{$total_amount}','{$time}','2','{$business_type}','{$payee}','{$desc}','{$trading_id}')";

			$row=$GLOBALS['db']->query($sql);
		}
		
		break;
	case '4':
		$row = $GLOBALS['db']->query("UPDATE ".$GLOBALS['ecs']->table('users')." SET open_gift = open_gift - $total_amount WHERE user_id={$user_id}");
		
		if($row){
			/* 插入订单数据 */
			$sql = "INSERT INTO " . $ecs->table('open_gift_log') . "(user_id, open_gift, change_time, change_type, business_type, payee, `desc`,trading_id) values ('{$user_id}','-{$total_amount}','{$time}','2','{$business_type}','{$payee}','{$desc}','{$trading_id}')";

			$row=$GLOBALS['db']->query($sql);
		}
		
		break;
	default:
	}

	
	if($row)
		exit($json->json_encode_ex(['result'=>'1','info'=>'提现申请已提交']));
	
		
}
//团队管理
elseif($action == 'team_ement'){
	

	$user_id = $userInfo['userid'];
	$parent_id = 'us'.$user_id;
	
	$sql="select business_level from ".$GLOBALS['ecs']->table('users')." where user_id={$userInfo['userid']}";
	$business_level = $GLOBALS['db']->getOne($sql);

	//市代
	if($business_level == '1'){
		$agent_data = array();
		$agent_data['name'] = '区/县代理'; 
		$agent_data['type'] = '2';
		$sql="select city from ".$GLOBALS['ecs']->table('busines_agent')." where FUid={$user_id}";
		$city = $GLOBALS['db']->getOne($sql);
		$agent_data['number'] = $GLOBALS['db']->getOne("select count(*) from ".$GLOBALS['ecs']->table('busines_agent')." where city = '{$city}' and agent_rank=2 and status = 1");

	}
	
	//区代
	if($business_level == '2' || $business_level == '1'){
		$manager_data =array();
		$manager_data['name'] = '业务经理';
		$manager_data['type'] = '4';
		$sql="select district from ".$GLOBALS['ecs']->table('busines_agent')." where FUid={$user_id}";
		$district = $GLOBALS['db']->getOne($sql);
		$manager_data['number'] = $GLOBALS['db']->getOne("select count(*) from ".$GLOBALS['ecs']->table('busines_manager')." where district = '{$district}' and agent_rank =4 and status = 1");
		
	}
	
	//业务经理
	if($business_level == '4' || $business_level == '2' || $business_level == '1'){
		$supplier_data = array();
		$supplier_data['name'] = '商家';
		$supplier_data['type'] = '3';
		$supplier_data['number'] = $GLOBALS['db']->getOne("select count(*) from ".$GLOBALS['ecs']->table('supplier')." where parent_id = '{$parent_id}' and status = 1");
		
	}
	
	//礼品店主
	$shopkeeper_data = array();
	$users_data = array();
	
	$shopkeeper_data['name'] ='礼品店主';
	$shopkeeper_data['type'] ='5';
	$shopkeeper_data['number'] = $GLOBALS['db']->getOne("select count(*) from ".$GLOBALS['ecs']->table('busines_manager')." where parent_id = '{$parent_id}' and agent_rank =5 and status = 1");
	$users_data['name'] = '推荐会员';
	$users_data['type'] = '6';
	$users_data['number'] = $GLOBALS['db']->getOne("select count(*) from ".$GLOBALS['ecs']->table('users')." where parent_id = '{$parent_id}' and business_level =0");
	
	//组合成数组、忘了怎么写了
	if($business_level == '1')
		$data = array($agent_data,$manager_data,$supplier_data,$shopkeeper_data,$users_data);
	elseif($business_level == '2')
		$data = array($manager_data,$supplier_data,$shopkeeper_data,$users_data);
	elseif($business_level == '4')
		$data = array($supplier_data,$shopkeeper_data,$users_data);
	else
		$data = array($shopkeeper_data,$users_data);
	

	
	exit($json->json_encode_ex(['result'=>'1','info'=>$data]));
	
}
//团队列表
elseif($action == 'team_list'){
	
	$type = $_REQUEST['type'] ? trim($_REQUEST['type']) : '6';	//等级
	$page=!empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 0 ;
	$size =10;
	$begin = $page*$size;
	$limit = " LIMIT $begin,$size";
	$user_id = $userInfo['userid'];
	$parent_id = 'us'.$user_id;
	
	switch ($type)
	{
	case '2':
		$sql="select city from ".$GLOBALS['ecs']->table('busines_agent')." where FUid={$user_id}";
		$city = $GLOBALS['db']->getOne($sql);
		$total = "select count(*) from ".$GLOBALS['ecs']->table('busines_agent')." where city = '{$city}' and agent_rank=2 and status = 1";
		$list = "select recommend_code,sales_amount,mobile,operating_name as name,reg_time from ".$GLOBALS['ecs']->table('busines_agent')." where city = '{$city}' and agent_rank=2 and status = 1 order by FUid desc {$limit}";
		break;
	case '3':
		$total = "select count(*) from ".$GLOBALS['ecs']->table('supplier')." where parent_id='{$parent_id}' and status = 1 ";
		$list = "select recommend_code,sales_amount,supplier_name as name,add_time as reg_time from ".$GLOBALS['ecs']->table('supplier')." where  parent_id='{$parent_id}' and status = 1 order by supplier_id desc {$limit}";
		break;
	case '4':
		$sql="select district from ".$GLOBALS['ecs']->table('busines_agent')." where FUid={$user_id}";
		$district = $GLOBALS['db']->getOne($sql);
		$total = "select count(*) from ".$GLOBALS['ecs']->table('busines_manager')." where district = '{$district}' and agent_rank =4 and status = 1";
		$list = "select recommend_code,sales_amount,mobile,operating_name as name,reg_time from ".$GLOBALS['ecs']->table('busines_manager')." where  district = '{$district}' and agent_rank =4 and status = 1 order by FUid desc {$limit}";
		break;
	case '5':
		$total = "select count(*) from ".$GLOBALS['ecs']->table('busines_manager')." where parent_id = '{$parent_id}' and agent_rank =5 and status = 1";
		$list = "select recommend_code,sales_amount,mobile,operating_name as name,reg_time from ".$GLOBALS['ecs']->table('busines_manager')." where  parent_id = '{$parent_id}' and agent_rank =5 and status = 1 order by FUid desc {$limit}";
		break;
	case '6':
		$total = "select count(*) from ".$GLOBALS['ecs']->table('users')." where parent_id = '{$parent_id}' and business_level =0 ";
		$list = "select recommend_code,sale_gift as sales_amount,mobile,nickname as name,reg_time from ".$GLOBALS['ecs']->table('users')." where  parent_id = '{$parent_id}' and business_level =0 order by user_id desc {$limit}";
		break;
	default:
	}


	$total = $GLOBALS['db']->getOne($total);
	$list = $GLOBALS['db']->getAll($list);
	
	exit($json->json_encode_ex(['result'=>'1','total'=>$total,'info'=>$list,'type'=>$type]));
	
	
	
	
}
//活动及规则
elseif($action == 'rule'){
	
	$article_id = $_REQUEST['article_id'];
	$article = $GLOBALS['db']->getRow("select article_id,title,content from ".$GLOBALS['ecs']->table('article')." where article_id = '{$article_id}'");
	exit($json->json_encode_ex(['result'=>'1','info'=>$article]));
}
//用户实名认证状态
elseif($action == 'authen_status'){
	
	$user_id = $userInfo['userid'];
	$sql = "select real_name,status from ".$GLOBALS['ecs']->table('users_authen')." where user_id = '{$user_id}'";
	$userAuthen = $GLOBALS['db']->getRow($sql);
	if($userAuthen)
		exit($json->json_encode_ex(['result'=>'1','info'=>'1']));
	else
		exit($json->json_encode_ex(['result'=>'1','info'=>'0']));
	
}
//添加或修改实名认证
elseif($action == 'user_authen'){
	
	$user_id = $userInfo['userid'];
	$reg_time = time();
	$real_name = $_REQUEST['real_name']; //真实姓名
	$operating_id = $_REQUEST['operating_id']; //身份证号码
	$address = $_REQUEST['address']; //居住地址
	$positive = $_REQUEST['identity_positive'];	//身份证正面
	$opposite = $_REQUEST['identity_opposite'];	//身份证反面
	
	$sql = "INSERT INTO ".$ecs->table('users_authen')."(`real_name`,`operating_id`,`identity_positive`,`identity_opposite`,`reg_time`,`status`,`address`,`user_id`) values "
	."('{$real_name}','{$operating_id}','{$positive}','{$opposite}','{$reg_time}','1','{$address}','{$user_id}')";
	
	$row=$GLOBALS['db']->query($sql);
	
	if($row)
		exit($json->json_encode_ex(['result'=>'1','info'=>'申请成功']));
	else
		exit($json->json_encode_ex(['result'=>'0','info'=>'申请失败']));
}


function get_regin_nameByID($name){
	$name=trim($name);
	$sql="select id from ".$GLOBALS['ecs']->table('region')." where name='{$name}'";
	return $GLOBALS['db']->getOne($sql);
}
