<?php

define('IN_ECS', true);

require('init.php');

$affiliate = unserialize($GLOBALS['_CFG']['affiliate']);

header("Content-Type:text/html;charset=UTF-8");

$action  = $_REQUEST['act'];

$ticket = $_REQUEST['ticket'];

$apptoken = $_REQUEST['token'];

$userinfo = '';

if(!empty($ticket)){

	$userinfo = split_user_ticket($ticket);

}



/***注册接口    手机号码phone、手机验证码codeyzm、密码password

 * 

 * http://www.tp5/newapp/api/user.php?act=register&phone=137984912339&yzcode=213123&password=sa123123

 * ***/

if($action == 'register'){

	$results =array();

	$phone=trim($_REQUEST['phone']);

	$password=trim($_REQUEST['password']);

    $extension_id=trim($_REQUEST['extension_id']);

	$yzm=trim($_REQUEST['codeyzm']); 

	$parent_id=trim($_REQUEST['parent_id']); 

	if(empty($phone) || empty($password)){

		$results=array(

				'result' => '0',

				'info' => '缺少必填参数'

		);

		exit($json->json_encode_ex($results));

	}

// 	if($yzm!=$_COOKIE['yzcode']){

// 		$results=array(

// 				'result' => '0',

// 				'info' => '验证码错误'

// 		);

// 		exit($json->json_encode_ex($results));

// 	}

	

	$sql="SELECT user_id FROM ".$GLOBALS['ecs']->table('users')." where mobile ='".$phone."' ";

	$user_id= $db->getOne($sql);

	include_once(ROOT_PATH . 'includes/lib_passport.php');

	if(mobile_registered($phone)){

		

		$results=array(

				'result' => '0',

				'info' => '手机已经注册'

		);

		exit($json->json_encode_ex($results));

	}

	// 获取下载推荐人

	if(!$extension_id){

		$ip =  $_SERVER["REMOTE_ADDR"];

		$extension_id = $db->getOne("SELECT extension_id FROM ".$GLOBALS['ecs']->table('extension')." where download_ip='".$ip."' and register = 0");

	    $GLOBALS['db']->query("UPDATE ".$GLOBALS['ecs']->table('extension')." SET register= '1' where download_ip='".$ip."'");

		

	}

	//注册推荐人

	if($parent_id && $parent_id != '9999'){

		$parent_id = $db->getOne("SELECT recommend_code FROM ".$GLOBALS['ecs']->table('users')." where mobile='{$parent_id}' or recommend_code = '{$parent_id}'");

	}

	

	//检查用户名是否重复

	$username = $phone;

	$user=array(

		'nickname'	=>"$username",

		'password'	=>"$password",

		'phone'	=>"$phone",

        'extension_id' => $extension_id,

		'parent_id' => $parent_id,

	);

	



    $app_type=!empty($_REQUEST['app_type']) ? trim($_REQUEST['app_type']):"";



    if($app_type=='1'){

        $device_token='ios'.md5('ylt|'.$phone);

    }else{

        $device_token='android'.md5('ylt|'.$phone);

    }

	

	if (saveuser($user) === true){

		$sql="SELECT user_id FROM ".$GLOBALS['ecs']->table('users')." where mobile ='".$phone."' ";

		$user_id= $db->getOne($sql);

		$is_coupon = 0;

		$sql = "select * from ".$GLOBALS['ecs']->table('coupon_list')." where use_time = 0 and uid= {$user_id} ";

		$coupon= $db->getRow($sql);

		if ($coupon) {

			$is_coupon = 1;

		}

		$ticket = gen_user_ticket($phone,$user_id,$device_token);

		$results=array(

				'result' => "1",

				'info' => "注册成功",

				'ticket' =>"$ticket",

				'user_id'=>$user_id,

				'is_coupon'=>$is_coupon,

		);

		exit($json->json_encode_ex($results));

	}

	

	$results=array(

			'result' => '0',

			'info' => '注册失败'

	);

	exit($json->json_encode_ex($results));

}





/**

 * 新发送短信发送短信验证码接口

 * 参数phone

 */

elseif ($action=='new_send_code'){

	$phone=$_REQUEST['phone'];

	$type=$_REQUEST['type'];



	if(empty($phone)){

		$results = array(

				'result'=>0,

				'info' => '手机号不能为空'

		);

		exit($json->json_encode_ex($results));

	}

	//过滤头部

	$agent = $_SERVER['HTTP_USER_AGENT'];

	$img_key = $_REQUEST['img_key'];

	$word  = $_REQUEST['word'];

	

	

	$rs=check_image_key($img_key,$word,$agent);

	//校验图形验证码

	if($rs['result']=='0'){

	//	exit($json->json_encode_ex($rs));

	}

   

	$code=gen_strnum();

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

	

	//用户已注册

	if($type=="register"){

		$falg=check_is_register($phone);

		if($falg>0){

			$results = array(

					'result'=>0,

					'info' => '手机号已注册'

			);

			exit($json->json_encode_ex($results));

		}

	}

	

	//$flag=$sms->send($phone,$code);

	$content = "您的验证码是".$code."(请尽快完成验证)。如非本人操作，请忽略本短信";

	$flag=$sms->sendSMS($phone,$content,$code);

	if($flag['code']=='0'){

		$codeyzm=gen_checkcode($code);

		setcookie("yzcode",$codeyzm,time()+60*20);

		$results = array(

				'result'=>1,

				'code' =>$code,

				'codeyzm'=>"$codeyzm",

				'info' => '发送成功'

		);

		exit($json->json_encode_ex($results));

	}elseif($flag['code']=='-1'){

		$codeyzm=gen_checkcode($code);

		setcookie("yzcode",$codeyzm,time()+60*20);

		$results = array(

// 				'result'=>0,

// 				'info' => '验证码超过次数'

				'result'=>1,

				'code' =>$code,

				'codeyzm'=>"$codeyzm",

				'info' => '发送成功'

		);

		exit($json->json_encode_ex($results));

	}

	$results = array(

			'result'=>0,

			'info' => '发送失败'

	);

	exit($json->json_encode_ex($results));

}



/**

 *旧版本用这个，后面弃用

 */

elseif ($action=='send_code'){

	$phone=$_REQUEST['phone'];

	$type=$_REQUEST['type'];

	if(empty($phone)){

		$results = array(

				'result'=>0,

				'info' => '手机号不能为空'

		);

		exit($json->json_encode_ex($results));

	}

	$code=gen_strnum();

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

	

	//用户已注册

	if($type=="register"){

		$falg=check_is_register($phone);

		if($falg>0){

			$results = array(

					'result'=>0,

					'info' => '手机号已注册'

			);

			exit($json->json_encode_ex($results));

		}

	}

	//$flag=$sms->send($phone,$code);

	$content = "您的验证码是".$code."(请尽快完成验证)。如非本人操作，请忽略本短信";

	$flag=$sms->sendSMS($phone,$content);

	if($flag['code']=='0'){

		$codeyzm=gen_checkcode($code);

		setcookie("yzcode",$codeyzm,time()+60*20);

		$results = array(

				'result'=>1,

				'code' =>$code,

				'codeyzm'=>"$codeyzm",

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

	$results = array(

			'result'=>0,

			'info' => '发送失败'

	);

	exit($json->json_encode_ex($results));

}



/***

 * 校验验证码

 * 参数code,codeyzm

 */

elseif($action=='check_code'){

	$phone=$_REQUEST['phone'];

	$code=$_REQUEST['code'];

	$codeyzm=$_REQUEST['codeyzm'];

	if(empty($code) || empty($codeyzm)){

		$results = array(

				'result'=>0,

				'info' => '缺少必备参数'

		);

		exit($json->json_encode_ex($results));

	}

	$arr=split_checkcode_str($codeyzm);

	//---------start

	$check_yzm = check_yzm($codeyzm);

	$temptime=time();

	

	if($temptime>$arr['expir_time']){

		$results = array(

				'result'=>0,

				'info' => '有效时间已过期'

		);

		exit($json->json_encode_ex($results));

	

	}elseif (!empty($check_yzm)) {

		$results = array(

				'result'=>0,

				'info' => '验证码已使用'

		);

		exit($json->json_encode_ex($results));

	}elseif ($code!=$arr['checkcode']) {

		$results = array(

				'result'=>0,

				'info' => '短信验证码不正确'

		);

		//保存验证成功的请求

		$param['app_name']   = '一礼通app';

		$param['toke']       = $codeyzm;

		$param['type']       = '1';

		include_once('includes/cls_sms1.php');

		$sms = new sms1();

		$sms->save_verify_log($param);

		exit($json->json_encode_ex($results));

	

	}elseif ($code==$arr['checkcode']){

		$results = array(

				'result'=>1,

				'info' => '验证成功'

		);

		//保存验证成功的请求

		$param['app_name']   = '一礼通app';

		$param['toke']       = $codeyzm;

		$param['type']       = '1';

		include_once('includes/cls_sms1.php');

		$sms = new sms1();

		$sms->save_verify_log($param);

		

		exit($json->json_encode_ex($results));

	}	

	

	$results = array(

			'result'=>0,

			'info' => '验证失败'

	);

	exit($json->json_encode_ex($results));

}

elseif($action=='get_mobile'){

	if(!empty($userinfo['userid'])){

		$user_id = $userinfo['userid'];

		$sql = "select mobile from".$GLOBALS['ecs']->table('users')." where user_id = {$user_id}  ";

		$mobile= $GLOBALS['db']->getOne($sql);

		$results = array(

				'result'=>1,

				'info' => '成功',

				'mobile' =>$mobile

		);

	}else{

		$results = array(

				'result'=>0,

				'info' => '请登录'

		);

	}

	

	exit($json->json_encode_ex($results));

}

/*

 * 用户登录接口

 * 请求参数phone,password

 * 返回参数 result,info,ticket

 */

elseif($action=='login'){

	$phone=$_REQUEST['phone'];

	if(!preg_match("/^1[34578]{1}\d{9}$/",$phone)){

		$results = array(

				'result'=>0,

				'info' => '手机格式不正确'

		);

		exit($json->json_encode_ex($results));

	}

	$password=$_REQUEST['password'];

	//$device_token=!empty($_REQUEST['device_token']) ? trim($_REQUEST['device_token']) : '';

	$app_type=!empty($_REQUEST['app_type']) ? trim($_REQUEST['app_type']):"";

	$login = true;

	$user_data='';

	//	用户名密码不为空

	if (!empty($phone) && !empty($password)){

		$sql = "SELECT business_level,user_id,password,nickname,head_pic,device_token,app_type,recommend_code,FManagerId FROM " .$GLOBALS['ecs']->table('users')." where mobile={$phone}";

		$user_data = $GLOBALS['db']->getRow($sql);

		//密码错误

		if ($user_data){

			//验证的登陆密码

			$password=md5pwd($password);

			if($password!=$user_data['password']){

				$login=false;

			}

		}		

	}

	

	$users['user_id']=$user_data['user_id'];

	$users['business_level']=!empty($user_data['business_level']) ? $user_data['business_level'] : "0";

	$users['nickname']=$user_data['nickname'];

	$users['recommend_code']=$user_data['recommend_code'];

	$users['FManagerId']= $user_data['FManagerId'] ? $user_data['FManagerId'] : '';

	if(strpos($user_data['head_pic'], 'http')==true){

		$users['head_pic']=$user_data['head_pic'];

	}else{

		$users['head_pic']=IMG_HOST.$user_data['head_pic'];

	}

	

   if($app_type=='1'){

		$device_token='ios'.md5('ylt|'.$phone);

	}else{

		$device_token='android'.md5('ylt|'.$phone);

	}

	

	if($login && !empty($device_token)){

		if($device_token!=$user_data['device_token']){

			//修改推送标识

			$sql="UPDATE ".$GLOBALS['ecs']->table('users')." SET app_type= '{$app_type}', device_token='{$device_token}' where mobile={$phone}";

	        $rs2=$GLOBALS['db']->query($sql);

		}

	}

	

	//	验证登录

	if($login && !empty($user_data)){

		$sql="UPDATE ".$GLOBALS['ecs']->table('users')." SET last_login= ".time() ." where user_id = {$user_data['user_id']} ";

		$rs3=$GLOBALS['db']->query($sql);

		$ticket = gen_user_ticket($phone,$user_data['user_id'],$device_token);

			$results = array(

					'result'=>1,

					'info' => '登录成功',

					'ticket'=>$ticket,

					'device_token'=>$device_token,

					'user'=>$users

			);

	}else{

		$results = array(

				'result'=>0,

				'ticket'=>'',

				'info' => '手机号或密码错误'

		);

	}

	exit($json->json_encode_ex($results));

}



//微信或者qq绑定接口

elseif($action=="user_binding"){

	

	$openid=trim($_REQUEST['openid']);

	$app_type=trim($_REQUEST['app_type']);

	//是1qq登录，2微信登录

	$status=$_REQUEST['status'];

	$head_pic=$_REQUEST['head_pic'];

	$nickname=$_REQUEST['nickname'];

	

	if(empty($openid) || empty($app_type) || empty($head_pic) || empty($nickname) || empty($status)){

		$rs = array(

				'result'=>0,

				'info' => '缺少必备参数'

		);

		exit($json->json_encode_ex($rs));

	}

	//判断验证码是否有误

	$where=" where openid='{$openid}' "; //OR　mobile_phone='{$mobile_phone}'

	//判断是否查询用户

	$sql = " SELECT user_id,mobile,device_token,app_type,openid,oauth,nickname,head_pic FROM " .$GLOBALS['ecs']->table('users').$where;

	$users= $GLOBALS['db']->getRow($sql);

	$users['head_pic']=!empty($head_pic) ? $head_pic : $users['head_pic'];

	$users['nickname']=!empty($nickname) ? $nickname : $user['nickname'];

	$user_id=$users['user_id'];

	if($app_type=='1'){

		$device_token='ios'.md5('ylt|'.$users['mobile']);

	}else{

		$device_token='android'.md5('ylt|'.$users['mobile']);

	}

	//微信登录已绑定的用户 或者有手机 直接跳到首页

	if(!empty($users['mobile'])){

		//登录成功修改状态

		if(!empty($device_token) && $device_token!=$users['device_token']){

			$sql = "UPDATE " .$GLOBALS['ecs']->table('users'). " SET app_type='{$app_type}',device_token='{$device_token}' where user_id={$user_id}";

			$GLOBALS['db']->query($sql);

		}

		//没有openid

		if(!empty($users['openid'])){

			if($status=='1'){

				//qq登录

				$oauth='qq';

			}elseif($status=='2'){

				//微信登录

				$oauth='weixin';

			}

		    //修改

			if($oauth!=$users['oauth'] || $users['nickname']!=$nickname){

				$sql = "UPDATE " .$GLOBALS['ecs']->table('users'). " SET oauth='{$oauth}',head_pic='{$head_pic}',nickname='{$nickname}' where user_id={$user_id}";

				$GLOBALS['db']->query($sql);

			}

		}

		$ticket = gen_user_ticket($users['mobile'],$user_id,$device_token);

		

		$results = array(

					'result'=>1,

				    'type'=>'0',

					'info' => '登录成功',

					'ticket'=>$ticket,

				    'device_token'=>$device_token,

				    'user'=>$users

		);

		exit($json->json_encode_ex($results));

	}else{

		$results = array(

				'result'=>1,

				'type'=>'1',

				'info' => '绑定手机',

				'ticket'=>''

		);

		exit($json->json_encode_ex($results));

	}

}

//检测是否已经绑定

elseif($action=="check_binding"){

	$phone=$_REQUEST['phone'];

	$where=" where mobile ='{$phone}'";

	$sql = "SELECT user_id FROM " .$GLOBALS['ecs']->table('users')." $where ";

	$user = $GLOBALS['db']->getOne($sql);

	if(!empty($user)){

		$results = array(

				'result'=>1,

				'type'=>'0',

				'info' => '不需填写密码',

		);

		exit($json->json_encode_ex($results));

	}else{

		$results = array(

				'result'=>1,

				'type'=>'1',

				'info' => '需要填写密码',

		);

		exit($json->json_encode_ex($results));

	}

}



//微信或qq已绑定输入手机号码

elseif($action=='phone_binding'){

	$openid=trim($_REQUEST['openid']);

	$phone=$_REQUEST['phone'];

	$password=$_REQUEST['password'];

	//$device_token=!empty($_REQUEST['device_token']) ? trim($_REQUEST['device_token']) : '';

	$app_type=!empty($_REQUEST['app_type']) ? trim($_REQUEST['app_type']):"";

	$head_pic=$_REQUEST['head_pic'];

	$nickname=$_REQUEST['nickname'];

	//是1qq登录，2微信登录

	$status=$_REQUEST['status'];

	if($status=='1'){

		$oauth='qq';

	}elseif($status=='2'){

		$oauth='weixin';

	}

	if(empty($openid) || empty($app_type)|| empty($app_type) || empty($phone)  || empty($head_pic) || empty($nickname)){ //|| empty($password)

		$rs = array(

				'result'=>0,

				'info' => '缺少必备参数'

		);

		exit($json->json_encode_ex($rs));

	}

	$login = true;

	$where=" where mobile ='{$phone}' or $openid='{$openid}' ";

	$sql = "SELECT user_id,password,nickname,head_pic,device_token,app_type,openid,oauth FROM " .$GLOBALS['ecs']->table('users')." $where ";

	$user_data = $GLOBALS['db']->getRow($sql);

	

	$users['user_id']=!empty($user_data['user_id']) ? $user_data['user_id'] : " ";

	$users['nickname']=!empty($nickname) ? $nickname : $user_data['nickname'];

	$users['head_pic']=!empty($head_pic) ? $head_pic :IMG_HOST.$user_data['head_pic'];

   if($app_type=='1'){

		$device_token='ios'.md5('ylt|'.$phone);

	}else{

		$device_token='android'.md5('ylt|'.$phone); 

	}

	//没有绑定账号信息就直接注册

	if(empty($user_data)){

			//检查用户名是否重复

			$username = "ylt_".time().rand(100, 999);

			$data=array(

					'nickname'	=>"$username",

					'password'	=>"$password",

					'phone'	=>"$phone",

					'device_token'=>$device_token,

					'app_type'=>$app_type,

					'nickname'=>$users['nickname'],

					'head_pic'=>$users['head_pic'],

					'openid'=>$openid,

					'oauth'=>$oauth

			);

			if (band_saveuser($data) === true){

				$sql="SELECT user_id FROM ".$GLOBALS['ecs']->table('users')." where mobile ='".$phone."' ";

				$user_id= $db->getOne($sql);

				$ticket = gen_user_ticket($phone,$user_id,$device_token);

				$results=array(

						'result' => "1",

						'info' => "绑定成功",

						'ticket' =>"$ticket",

						'user_id'=>$user_id,

						'user'=>$users

				);

				exit($json->json_encode_ex($results));

			}else{

				$results=array(

						'result' => "0",

						'info' => "绑定失败",

						'ticket' =>"",

						'user_id'=>"",

						'user'=>array()

				);

				exit($json->json_encode_ex($results));

			}

			

		}else{

			//校验密码是否正确

			$password=md5pwd($password);

			//去掉校验密码

			/* if($password!=$user_data['password']){

				$login=false;

			} */

			

			//看是否绑定

			if(!empty($user_data['oauth']) && !empty($user_data['openid'])){

				if($user_data['oauth']=='qq'){

					$mesage="您已绑定qq账号,请用qq登录";

				}elseif($user_data['oauth']=='weixin'){

					$mesage="您已绑定微信账号,请用微信登录";

				}

				$results = array(

						'result'=>0,

						'info' =>$mesage,

						'ticket'=>"",

						'device_token'=>"",

						'user'=>$users

				);

				exit($json->json_encode_ex($results));

			}

			//修改用户信息

			$flag2="";

			if($login){

				$sql="UPDATE ".$GLOBALS['ecs']->table('users')." SET nickname='{$nickname}',head_pic='{$head_pic}',openid='{$openid}',mobile='{$phone}',oauth='{$oauth}',app_type='{$app_type}',device_token='{$device_token}' where user_id='{$user_data[user_id]}'";

				$flag2=$GLOBALS['db']->query($sql);

			}

			//	验证登录

			if($login && $flag2){

				$ticket = gen_user_ticket($phone,$user_data['user_id'],$device_token);

				$results = array(

						'result'=>1,

						'info' => '绑定成功',

						'ticket'=>$ticket,

						'device_token'=>$device_token,

						'user'=>$users

				);

			}else{

				$results = array(

						'result'=>0,

						'ticket'=>'',

						'info' => '手机号或密码错误'

				);

			}

			exit($json->json_encode_ex($results));

		}	

}





/**

 *重置密码接口

 *参数phone,password,rpassword

 *

 */

elseif($action=='reset_pwd'){

	$phone=trim($_REQUEST['phone']);

	$pwd1=trim($_REQUEST['password']);

	$pwd2=trim($_REQUEST['rpassword']);

	$results=array();

	if(empty($pwd1) || empty($phone) || empty($pwd2)){

		$results=array(

				'result'=>'0',

				'info' =>'缺少参数'

		);

		exit($json->json_encode_ex($results));

	}

	if($pwd1 != $pwd2){

		$results=array(

				'result'=>'0',

				'info' =>'输入的密码不一致'

		);

		exit($json->json_encode_ex($results));

	}

	

	$sql="SELECT user_id FROM ".$GLOBALS['ecs']->table('users')." where mobile={$phone}";

	$row=$GLOBALS['db']->getRow($sql);

	

	if(empty($row)){

		$results=array(

				'result'=>'0',

				'info' =>'手机号码不存在'

		);

		exit($json->json_encode_ex($results));

	}

	

	$flag=setPwd_by_phone($phone,$pwd1);

	if($flag){

		$results=array(

				'result'=>'1',

				'info' =>'修改成功'

		);

		exit($json->json_encode_ex($results));

	}

	

	$results=array(

			'result'=>'1',

			'info' =>'修改失败'

	);

	exit($json->json_encode_ex($results));

}



/***

 * 增加修改收货地址

 * 

 */

elseif($action=='add_address'){



	$data['user_id']=$userinfo['userid'];

	$data['consignee']=$_REQUEST['consignee'];

	$data['mobile']=$_REQUEST['mobile'];

	$type=$_REQUEST['type'];

	$address_id=$_REQUEST['address_id'];

	if(!empty($_REQUEST['zipcode'])){

		$data['zipcode']=$_REQUEST['zipcode'];

	}

	$data['address']=$_REQUEST['address'];

	if(empty($data['consignee']) || empty($data['mobile']) || empty($data['address'])){

		$results=array(

				'result'=>'0',

				'info' =>'缺少参数'

		);

		exit($json->json_encode_ex($results));

	}

	$data['province']=get_regin_nameByID($_REQUEST['province']);

	$data['city']=get_regin_nameByID($_REQUEST['city']);

	$data['district']=get_regin_nameByID($_REQUEST['district']);

	if(empty($data['province']) || empty($data['city']) || empty($data['district'])){

		$results=array(

				'result'=>'0',

				'info' =>'请选择省市县'

		);

		exit($json->json_encode_ex($results));

	}

	

	//修改地址

	if($type=='save'){

		if(update_address($address_id,$data)){

			$results=array(

					'result'=>'1',

					'info' =>'地址修改成功',

					'data'=>$data

			);

			exit($json->json_encode_ex($results));

		}

	}else{

		if(add_address($data)){

			$results=array(

					'result'=>'1',

					'info' =>'地址添加成功',

					'data'=>$data,

			);

			exit($json->json_encode_ex($results));

		}

	}

	$results=array(

			'result'=>'0',

			'info' =>'操作失败'

	);

	exit($json->json_encode_ex($results));

	

}

/***

 * 删除默认地址

 * 

 */

elseif($action=="del_address"){

	$address_id=$_REQUEST['address_id'];

	$userid=!empty($userinfo['userid']) ? $userinfo['userid'] : " ";

	if(empty($address_id) || empty($userid)){

		$results=array(

				'result'=>'0',

				'info' =>'参数错误'

		);

		exit($json->json_encode_ex($results));

	}

	$sql="SELECT count(*) from ".$GLOBALS['ecs']->table('user_address')." where user_id ={$userid} AND address_id={$address_id}";

	$rs=$GLOBALS['db']->getOne($sql);

	if($rs){

		$sql="DELETE from ". $ecs->table('user_address') ." WHERE user_id ={$userid} AND address_id={$address_id} ";

		$GLOBALS['db']->query($sql);

		$results=array(

				'result'=>'1',

				'info' =>'删除成功'

		);

		exit($json->json_encode_ex($results));

	}else{

		$results=array(

				'result'=>'0',

				'info' =>'操作失败'

		);

		exit($json->json_encode_ex($results));

	}

}



//修改默认地址

elseif($action=='address_default'){

	$address_id=$_REQUEST['address_id'];

	$userid=$userinfo['userid'];

	

	$sql="SELECT count(*) from ".$GLOBALS['ecs']->table('user_address')." where user_id ={$userid} AND address_id={$address_id}";

	$rs=$GLOBALS['db']->getOne($sql);

	if(empty($rs)){

		$results=array(

				'result'=>'0',

				'info' =>'操作失败'

		);

		exit($json->json_encode_ex($results));

	}

	$sql="UPDATE ".$GLOBALS['ecs']->table('user_address')." SET is_default= 0 where user_id ={$userid}  AND is_default=1";

	$rs2=$GLOBALS['db']->query($sql);

	

	$sql="UPDATE ".$GLOBALS['ecs']->table('user_address')." SET is_default= 1 where user_id ={$userid} AND address_id={$address_id} ";

	$rs1=$GLOBALS['db']->query($sql);

	

	

	if($rs1 && $rs2){

		$results=array(

				'result'=>'1',

				'info' =>'操作成功'

		);

		exit($json->json_encode_ex($results));

	}else{

		$results=array(

				'result'=>'0',

				'info' =>'操作失败'

		);

		exit($json->json_encode_ex($results));

	}

	

}

//获取用户地址信息

elseif($action=='get_user_address'){

	$userid=$userinfo['userid'];

	$address_id=$_REQUEST['address_id'];

	$sql="SELECT address_id,consignee,province,city,district,address,mobile,is_default,zipcode from ".$GLOBALS['ecs']->table('user_address')." where user_id={$userid} AND address_id={$address_id}";

	$address=$GLOBALS['db']->getRow($sql);

	

	$address['province_list']= get_regions(0);

	$address['city_list']= get_regions($address['province']);

	$address['district_list']= get_regions($address['city']);

	

	if($address){

		$results=array(

				'result' => '1',

				'info' => '操作成功',

				'data'=>$address,

		);

	}else{

		$results=array(

				'result' => '0',

				'info' => '操作失败',

				'data'=>array(),

		);

	}

	exit($json->json_encode_ex($results));

}

//获取所有的用户收货地址

elseif($action=="get_all_address"){

	$userid=$userinfo['userid'];

	$sql="SELECT address_id,consignee,province,city,district,address,mobile,is_default,zipcode from ".$GLOBALS['ecs']->table('user_address')." where user_id={$userid}";

	$list=$GLOBALS['db']->getAll($sql);

	$temp=array();

	if(!empty($list)){

		foreach ($list as $key =>$val){

			$temp[$key]=$val;

			$temp[$key]['province_name']=get_regin_name($val['province']);

			$temp[$key]['city_name']=get_regin_name($val['city']);

			$temp[$key]['district_name']=get_regin_name($val['district']);

		}

	}

	if(!empty($temp)){

		$results=array('result'=>'1','info'=>'请求成功','list'=>$temp);

	}else{

		$results=array('result'=>'1','info'=>'无数据','list'=>$temp);

	}

	exit($json->json_encode_ex($results));

}







//获取用户中心接口

elseif($action=="get_user_info"){

	$userid=$userinfo['userid'];

	$sql="select user_id,sex,birthday,mobile,nickname,head_pic,parent_id,business_level,recommend_code,FManagerId,parent_id from ".$GLOBALS['ecs']->table('users')." where user_id={$userid} ";

	$user=$GLOBALS['db']->getRow($sql);

	if(!empty($user['head_pic'])){

		$user['path']=$user['head_pic'];

		$user['head_pic'] = strstr($user['head_pic'],"http") ? $user['head_pic'] : IMG_HOST.$user['head_pic'];

		

	}else{

		$user['head_pic'] = '';

	}

	$user['parent_id'] = $user['parent_id'] ? $user['parent_id'] : '';

	/*if($user['business_level']){

		$sql="select business_name from ".$GLOBALS['ecs']->table('busines_rank')." where rank_id='{$user['business_level']}' ";

		$business_name = $GLOBALS['db']->getOne($sql);

		$user['business_name'] = $business_name.' | 编码:'.$user['recommend_code'];

	}else{

		

		$user['business_name'] = '普通用户 | 编码:'.$user['recommend_code'];

	}*/

		

		

	if($user){

		$results=array(

				'result' => '1',

				'info' => '请求成功',

				'data'=>$user,

		);

	}else{

		$results=array(

				'result' => '0',

				'info' => '请求失败',

		);

	}

	exit($json->json_encode_ex($results));

}

//增加或者修改用户信息接口

elseif($action=='save_user'){

	$userid=$userinfo['userid'];

    $users['sex']=$_REQUEST['sex'];

    $users['birthday']=$_REQUEST['birthday'];

    $users['nickname']=$_REQUEST['nickname'];

    $users['head_pic']=$_REQUEST['head_pic'];

    if ($users['head_pic']) {

    	$sql = "update ".$GLOBALS['ecs']->table('supplier')." set logo =  '{$users['head_pic']}' where user_id ={$userid} and is_designer=1";

    	$GLOBALS['db']->query($sql);

    }

    /* if($_REQUEST['type']=='1'){

    	$sql="UPDATE ".$GLOBALS['ecs']->table('users')." SET head_pic={head_pic} where user_id ={$userid} ";

    	$flag= $GLOBALS['db']->query($sql);

    }else{

		$sql="UPDATE ".$GLOBALS['ecs']->table('users')." SET sex={$sex},birthday='{$birthday}',nickname='{$nickname}' where user_id ={$userid} ";

		$flag= $GLOBALS['db']->query($sql);

    } */

    $sql="select count(*) from ".$GLOBALS['ecs']->table('users')." where user_id ={$userid} ";

    $count=$db->getOne($sql);

    if(empty($count)){

    	$results=array(

    			'result' => '0',

    			'info' => '失败'

    	);

    	exit($json->json_encode_ex($results));

    }

    $flag=$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('users'), $users, 'UPDATE',"user_id ={$userid}");

   /*  $sql="UPDATE ".$GLOBALS['ecs']->table('users')." SET sex={$sex},birthday='{$birthday}',nickname='{$nickname}',head_pic='{$head_pic}' where user_id ={$userid} ";

    $flag= $GLOBALS['db']->query($sql); */

    if(strpos($users['head_pic'], 'http')==true){

    	$image_url=$users['head_pic'];

    }else{

    	$image_url=IMG_HOST.$users['head_pic'];

    }

	if($flag){

		$results=array(

				'result' => '1',

				'info' => '请求成功',

				'nickname'=>$users['nickname'],

				'head_pic'=>$image_url,

				'head_img'=>$users['head_pic'],

				'user_id' =>$userid

		);

	}else{

		$results=array(

				'result' => '0',

				'info' => '请求失败',

		);

	}

	exit($json->json_encode_ex($results));

}

//修改密码接口

elseif($action=="save_pwd"){

	$userid=$userinfo['userid'];

	$password=trim($_REQUEST['old_password']);

	$password2=trim($_REQUEST['new_password']);

	$password=md5pwd($password);

	$password2=md5pwd($password2);

	$sql="select password from ".$GLOBALS['ecs']->table('users')." where user_id ={$userid} ";

	$pwd=$db->getOne($sql);

	if($password!=$pwd){

		$results=array(

				'result' => '0',

				'info' => '旧密码不正确'

		);

		exit($json->json_encode_ex($results));

	}

	

	$sql="UPDATE ".$GLOBALS['ecs']->table('users')." SET password='{$password2}' where user_id ={$userid} ";

	$flag= $GLOBALS['db']->query($sql);

	

	if($flag){

		$results=array(

				'result' => '1',

				'info' => '请求成功'

		);

	}else{

		$results=array(

				'result' => '0',

				'info' => '请求失败'

		);

	}

	exit($json->json_encode_ex($results));

}



//微信qq登录注册

elseif($action=="new_register"){

    

	$phone=!empty($_REQUEST['phone']) ? $_REQUEST['phone'] : '';

	$password=$_REQUEST['password'];

	//$device_token=!empty($_REQUEST['device_token']) ? trim($_REQUEST['device_token']) : '';

	$app_type=!empty($_REQUEST['app_type']) ? trim($_REQUEST['app_type']):"";

	$head_pic=$_REQUEST['head_pic'];

	$nickname=$_REQUEST['nickname'];

	//是1qq登录，2微信登录

	$status=$_REQUEST['status'];

	$yes='';

	if($status=='1'){

		$oauth='qq';

		$openid=trim($_REQUEST['openid']);

		$yes=1;

		$where=" where openid='{$openid}'";

	}elseif($status=='2'){

		$oauth='weixin';

		$openid=trim($_REQUEST['openid']);

		$yes=1;

		$where=" where unionid='{$openid}'";

	}

	if( empty($yes)|| empty($app_type)||   empty($head_pic) || empty($nickname)){ //|| empty($password)

		$rs = array(

				'result'=>0,

				'info' => '缺少必备参数'

		);

		exit($json->json_encode_ex($rs));

	}

	

	

	$sql = "SELECT user_id,mobile,password,nickname,head_pic,device_token,app_type,openid,oauth,business_level,recommend_code FROM " .$GLOBALS['ecs']->table('users')." $where ";



	$user_data = $GLOBALS['db']->getRow($sql);

		

	$users['mobile']=!empty($phone) ? $phone : $user_data['mobile'];



	$type="0";

	$message="无绑定手机号";

	if(!empty($user_data['mobile'])){

		$type="1";

		$message="已经绑定手机号成功";

	}

	



//	$users['head_pic'] = strstr($user_data['head_pic'],"http") ? $user_data['head_pic'] : IMG_HOST.$user_data['head_pic'];

	if ($user_data['head_pic']) {

		if(strpos($user_data['head_pic'],'http') !== false){

			$users['head_pic'] =  $user_data['head_pic'];

		}else{

			$users['head_pic'] =  IMG_HOST.$user_data['head_pic'];

		}

	}else{

		$users['head_pic']=$head_pic;

	}

	

	

	$users['user_id']=!empty($user_data['user_id']) ? $user_data['user_id'] : " ";

	$users['nickname']=!empty($user_data['nickname']) ? $user_data['nickname'] : $nickname ;

//	$users['head_pic']=!empty($head_pic) ? $head_pic : $users['head_pic'];

	

	$token = !empty($phone) ? $phone : $openid;

	if($app_type=='1'){

		$device_token='ios'.md5('ylt|'.$token);

	}else{

		$device_token='android'.md5('ylt|'.$token);

	}

	if(!empty($user_data)){

		$users['device_token']=$device_token;

		$users['business_level'] = $user_data['business_level'];

		$users['recommend_code'] = $user_data['recommend_code'];

		$ticket = gen_user_ticket($phone,$user_data['user_id'],$device_token);

		$results=array(

				'result' => "1",

				'type'=>$type,

				'info' => $message,

				'ticket' =>"$ticket",

				'user_id'=>$users['user_id'],

				'user'=>$users

		);

		exit($json->json_encode_ex($results));

	}

   

	//没有绑定账号信息就直接注册

	if(empty($user_data)){

			//检查用户名是否重复

			$username = "ylt_".time().rand(100, 999);

			

			if($status=='1'){

				$data=array(

					'nickname'=>"$username",

					'password'=>"$password",

					'phone'	=>"$phone",

					'device_token'=>$device_token,

					'app_type'=>$app_type,

					'nickname'=>$users['nickname'],

					'head_pic'=>$users['head_pic'],

					'openid'=>$openid,

					'unionid'=>'',

					'oauth'=>$oauth,

				);

			}else{

				$data=array(

						'nickname'=>"$username",

						'password'=>"$password",

						'phone'	=>"$phone",

						'device_token'=>$device_token,

						'app_type'=>$app_type,

						'nickname'=>$users['nickname'],

						'head_pic'=>$users['head_pic'],

						'openid'=>'',

						'unionid'=>$openid,

						'oauth'=>$oauth,

				);

			}

			$is_coupon=0;

			$rs=band_saveuser($data);

			if($rs=='1'){

				//已绑定手机号

				$type=1;

			}

			if ($rs != false){

				if ($rs=3) {//判断是否领了券s

					$is_coupon=1;

				}

				if($status=='1'){

					$sql="SELECT user_id FROM ".$GLOBALS['ecs']->table('users')." where openid ='".$openid."' ";

				}else{

					$sql="SELECT user_id FROM ".$GLOBALS['ecs']->table('users')." where unionid ='".$openid."' ";

				}

				

				$user_id= $db->getOne($sql);

				

				//获得 recommend_code

				$recommend_code = 'us'.$user_id;

				$sql="UPDATE ".$GLOBALS['ecs']->table('users')." SET recommend_code= '{$recommend_code}' where user_id={$user_id}";

				$GLOBALS['db']->query($sql);

	

				$ticket = gen_user_ticket($phone,$user_id,$device_token);

				$results=array(

						'result' => "1",

						'type'=>$type,

						'info' => "绑定成功",

						'ticket' =>"$ticket",

						'user_id'=>$user_id,

						'user'=>$users,

						'is_coupon'=>$is_coupon,

				);

				exit($json->json_encode_ex($results));

			}else{

				$results=array(

						'result' => "0",

						'type'=>$type,

						'info' => "绑定失败",

						'ticket' =>"",

						'user_id'=>"",

						'user'=>array(),

						'is_coupon'=>$is_coupon,

				);

				exit($json->json_encode_ex($results));

			}

			

		}

}

//新绑定手机号码

elseif($action=="binding_phone"){

	$phone=$_REQUEST['phone'];

	$userid=!empty($userinfo['userid']) ? $userinfo['userid'] : "";

	$password=trim($_REQUEST['password']);

	$password2=trim($_REQUEST['password2']);

	$extension_id = trim($_REQUEST['extension_id']);

	$is_coupon=0;

	if($password!=$password2){

		$results=array(

				'result' => "0",

				'info' => "请检查密码是否填写一致",

		);

		exit($json->json_encode_ex($results));

	}

	$userid=$userinfo['userid'];

	if(empty($phone) || empty($password)){

		$results=array(

				'result' => "0",

				'info' => "缺少参数",

		);

		exit($json->json_encode_ex($results));

	}

	$sql="SELECT user_id FROM ".$GLOBALS['ecs']->table('users')." where mobile={$phone}";

	$row=$GLOBALS['db']->getOne($sql);



	if(!empty($row)){

		$psw=md5pwd($password);

		$sql="SELECT unionid,openid FROM ".$GLOBALS['ecs']->table('users')." where user_id={$userid}";

		$info=$GLOBALS['db']->getRow($sql);

		if ($info['unionid']) {

			$sql="UPDATE ".$GLOBALS['ecs']->table('users')." SET unionid='" . $info['unionid'] ."' ,  password='$psw' where user_id={$row}";

		}else{

			$sql="UPDATE ".$GLOBALS['ecs']->table('users')." SET openid='" . $info['openid'] ."' , password='$psw'  where user_id={$row}";

		}

		

	

		$rs=$GLOBALS['db']->query($sql);

		

		if ($rs) {

	

			$sql = "select * from ".$GLOBALS['ecs']->table('coupon_list')." where uid ={$row} and use_time=0";

			

			$coupon = $GLOBALS['db']->getRow($sql);

			

			if ($coupon) {

				$is_coupon=1;

			}

			$sql="DELETE from ". $ecs->table('users') ." WHERE user_id ={$userid} and password =''";

			$GLOBALS['db']->query($sql);

			$results=array(

				'result' => "1",

				'type'=>"100",

				'info' => "绑定旧手机账号成功",

				'is_coupon'=>$is_coupon,

			);

			exit($json->json_encode_ex($results));

		}

	}



	$sql="SELECT mobile FROM ".$GLOBALS['ecs']->table('users')." where user_id={$userid}";

	$rs=$GLOBALS['db']->getOne($sql);

	if(!empty($rs)){

		$results=array(

				'result' => "1",

				'type'=>"1",

				'info' => "手机号已绑定,请绑定新手机号，或用已绑定的手机号登录。",

				'is_coupon'=>$is_coupon,

		);

		exit($json->json_encode_ex($results));

	}

	// 获取推荐人

	if(!$extension_id){

		$ip =  $_SERVER["REMOTE_ADDR"];

		$extension_id = $db->getOne("SELECT extension_id FROM ".$GLOBALS['ecs']->table('extension')." where download_ip='".$ip."' and register = 0");

		$GLOBALS['db']->query("UPDATE ".$GLOBALS['ecs']->table('extension')." SET register= '1' where download_ip='".$ip."'");



	}

	$password=md5pwd($pwd);

	$sql="UPDATE ".$GLOBALS['ecs']->table('users')." SET password='$password',mobile={$phone},extension_id='$extension_id' where user_id={$userid}";

	$rs=$GLOBALS['db']->query($sql);

	if($rs){

		$temptime=time();

		$sql = "select * from ".$GLOBALS['ecs']->table('coupon_list')." where uid ={$userid} and use_time=0";



		$coupon = $GLOBALS['db']->getRow($sql);

		if ($coupon) {

			$is_coupon=1;

		}

		$results=array(

				'result' => "1",

				'type'=>"0",

				'info' => "手机号绑定成功",

				'is_coupon'=>$is_coupon,

		);

		exit($json->json_encode_ex($results));

	}else{

		$results=array(

				'result' => "0",

				'info' => "绑定失败",

		);

		exit($json->json_encode_ex($results));

	}



}

//旧绑定手机号码

elseif($action=="add_phone"){

	$phone=$_REQUEST['phone'];

	$userid=!empty($userinfo['userid']) ? $userinfo['userid'] : "";

	$password=trim($_REQUEST['password']);

	$password2=trim($_REQUEST['password2']);

	$extension_id = trim($_REQUEST['extension_id']);

	if($password!=$password2){

		$results=array(

				'result' => "0",

				'info' => "请检查密码是否填写一致",

		);

		exit($json->json_encode_ex($results));

	}

	$userid=$userinfo['userid'];

	if(empty($phone) || empty($password)){

		$results=array(

				'result' => "0",

				'info' => "缺少参数",

		);

		exit($json->json_encode_ex($results));

	}

	$sql="SELECT user_id FROM ".$GLOBALS['ecs']->table('users')." where mobile={$phone}";

	$row=$GLOBALS['db']->getOne($sql);



	if(!empty($row)){

		$results=array(

				'result' => "1",

				'type'=>"1",

				'info' => "手机号已绑定,请绑定新手机号，或用已绑定的手机号登录。",

		);

		exit($json->json_encode_ex($results));

	}

	

	$sql="SELECT mobile FROM ".$GLOBALS['ecs']->table('users')." where user_id={$userid}";

	$rs=$GLOBALS['db']->getOne($sql);

	if(!empty($rs)){

		$results=array(

				'result' => "1",

				'type'=>"1",

				'info' => "手机号已绑定,请绑定新手机号，或用已绑定的手机号登录。",

		);

		exit($json->json_encode_ex($results));

	}

	// 获取推荐人

	if(!$extension_id){

		$ip =  $_SERVER["REMOTE_ADDR"];

		$extension_id = $db->getOne("SELECT extension_id FROM ".$GLOBALS['ecs']->table('extension')." where download_ip='".$ip."' and register = 0");

	    $GLOBALS['db']->query("UPDATE ".$GLOBALS['ecs']->table('extension')." SET register= '1' where download_ip='".$ip."'");

		

	}

	$password=md5pwd($pwd);

	$sql="UPDATE ".$GLOBALS['ecs']->table('users')." SET password='$password',mobile={$phone},extension_id='$extension_id' where user_id={$userid}";

	$rs=$GLOBALS['db']->query($sql);

	if($rs){

		$results=array(

				'result' => "1",

				'type'=>"0",

				'info' => "手机号绑定成功",

		);

		exit($json->json_encode_ex($results));

	}else{

		$results=array(

				'result' => "0",

				'info' => "绑定失败",

		);

		exit($json->json_encode_ex($results));

	}

	

}

//检测用户是否有绑定手机号码

elseif($action=="check_users"){

	$userid=!empty($userinfo['userid']) ? $userinfo['userid'] : "";



	if(empty($userid)){

		$results=array(

				'result' => "0",

				'info' => "缺少参数",

		);

		exit($json->json_encode_ex($results));

	}

	$sql="SELECT password,mobile FROM ".$GLOBALS['ecs']->table('users')." where user_id={$userid}";

	$row=$GLOBALS['db']->getRow($sql);

	

	if(!empty($row['mobile'])){

		$results=array(

				'result' => "1",

				'type'=>'0',

				'info' => "手机号已存在",

		);

		exit($json->json_encode_ex($results));

	}else{

		$results=array(

				'result' => "1",

				'type'=>'1',

				'info' => "无手机号码",

		);

		exit($json->json_encode_ex($results));

	}

}





//获取省市县信息

elseif($action=='get_region'){

	$parent_id=!empty($_REQUEST['region_id']) ? $_REQUEST['region_id'] : 0 ;

	$list = get_regions($parent_id);

	$results=array(

			'result' => '1',

			'info' => '操作成功',

			'data'=>$list

	);

	exit($json->json_encode_ex($results));

}



//获取图形验证码

elseif ($action == 'get_img_code'){

	require('../includes/cls_captcha.php');

    $img = new captcha('../includes/captcha/', $_CFG['captcha_width'], $_CFG['captcha_height']);

    @ob_end_clean(); //清除之前出现的多余输入

    $res = $img->api_generate_image();

    

	if ($res['status']){

		$img_key = encrypt($res['word']."|".$_SERVER['REMOTE_ADDR']."|".(time()+60), 'chenx');

		$results = array('result'=>1,'img_key'=>$img_key,'img'=>$res['img'],'info'=>'请求成功');

	}else{

		$results = array('result'=>0,'info'=>'请求失败');

	}

	exit($json->json_encode_ex($results));

}



//增加用户收货地址

function add_address($data){

	$userid=$data['user_id'];

	$sql="select count(*) from ".$GLOBALS['ecs']->table('user_address')." where user_id={$userid} ";

	$rs=$GLOBALS['db']->getOne($sql);

	if(empty($rs)){

		$is_default=1;

	}else{

		$is_default=0;

	}

	$data['consignee']=$data['consignee'];

	$data['mobile']=$data['mobile'];

	$data['province']=$data['province'];

	$data['city']=$data['city'];

	$data['district']=$data['district'];

	$data['address']=$data['address'];

	$sql = "INSERT INTO " .$GLOBALS['ecs']->table('user_address'). "(`user_id`,`consignee`, `mobile`,`province`,`city`,`district`,`address`,`is_default`,`zipcode`)

	VALUES('$userid', '{$data[consignee]}', '{$data[mobile]}','{$data[province]}', '{$data[city]}','{$data[district]}','{$data[address]}',$is_default,'{$data[zipcode]}')";

	return $GLOBALS['db']->query($sql);

}



//修改收货地址

function update_address($address_id,$data){

	$userid=$data['user_id'];

	$sql="select count(*) from ".$GLOBALS['ecs']->table('user_address')." where user_id={$userid} AND address_id={$address_id}";

	$rs=$GLOBALS['db']->getOne($sql);

	if(empty($rs)){

		return false;

	}

	$sql="UPDATE ".$GLOBALS['ecs']->table('user_address')." SET consignee='{$data[consignee]}',mobile='{$data[mobile]}',province='{$data[province]}',city='{$data[city]}',district='{$data['district']}',address='{$data[address]}',zipcode='{$data[zipcode]}' where user_id ={$userid} AND address_id={$address_id} ";

	return $GLOBALS['db']->query($sql);

}







//修改密码

function setPwd_by_phone($phone,$pwd){

	$sql="SELECT user_id,mobile,reg_time FROM ".$GLOBALS['ecs']->table('users')." where mobile={$phone}";

	$row=$GLOBALS['db']->getRow($sql);

	if(empty($row)){

		return false;

	}

	$password=md5pwd($pwd);

	$sql="UPDATE ".$GLOBALS['ecs']->table('users')." SET password='$password' where mobile ={$phone} AND user_id={$row[user_id]} ";

	$rs=$GLOBALS['db']->query($sql);

	if($rs){

		return true;

	}

	return false;

}





//修改用户信息

function saveFile($userid,$file){

	$sql="SELECT user_id FROM ".$GLOBALS['ecs']->table('users')." where user_id=$userid";

	$row=$GLOBALS['db']->getRow($sql);

	if(empty($row)){

		return false;

	}

	$sql="UPDATE ".$GLOBALS['ecs']->table('users')." SET $file where user_id =$userid ";

	$rs=$GLOBALS['db']->query($sql);

	if($rs){

		return true;

	}

	return false;

}



function check_yzm($yzcode){

	$sql="SELECT id FROM ".$GLOBALS['ecs']->table('app_vcode')." where toke='".$yzcode."' and type=1 ";

	$row=$GLOBALS['db']->getOne($sql);

	if(empty($row)){

		return false;

	}else{

		return true;

	}

}

function md5pwd($pwd){

	return sha1((md5($pwd).'ylt'));

}





function saveuser($user){

	if(empty($user)){

		return false;

	}

	$temptime=time();

	$password=md5pwd($user['password']);

	$sql = "INSERT INTO " .$GLOBALS['ecs']->table('users'). "(`nickname`,`password`, `reg_time`,`mobile`,`mobile_validated`,`extension_id`,`parent_id`)

	VALUES('$user[nickname]', '$password', '$temptime','$user[phone]','1','$user[extension_id]','$user[parent_id]')";

	$rs=$GLOBALS['db']->query($sql);

	

	//获得 recommend_code

	$sql="SELECT user_id FROM ".$GLOBALS['ecs']->table('users')." where mobile='".$user['phone']."' ";

	$user_id=$GLOBALS['db']->getOne($sql);

	$recommend_code = 'us'.$user_id;

	$sql="UPDATE ".$GLOBALS['ecs']->table('users')." SET recommend_code= '{$recommend_code}' where user_id={$user_id}";

	$GLOBALS['db']->query($sql);

	beanGiftLog($user_id,'20','新用户注册赠送');

	

	if($user['parent_id']){

		if($user['parent_id'] != '9999'){

			

			$sql="SELECT user_id,business_level FROM ".$GLOBALS['ecs']->table('users')." where mobile ='".$user['parent_id']."' ";

			$parentId= $GLOBALS['db']->getRow($sql);

			if(!$parentId){

				$sql="SELECT user_id,business_level FROM ".$GLOBALS['ecs']->table('users')." where recommend_code ='".$user['parent_id']."' ";

				$parentId= $GLOBALS['db']->getRow($sql);

				

			}

			if($parentId){

				beanGiftLog($parentId['user_id'],'10','推荐新用户 us'.$user_id);

				

				if($parentId['business_level'] == '4')

					$GLOBALS['db']->query("UPDATE ".$GLOBALS['ecs']->table('users')." SET FManagerId= '{$parentId['user_id']}' where user_id={$user_id}");

			}



		}

		

	}

	

	

	if($rs){

		$user_id = mysql_insert_id();

		$coupons=issuing_coupons();

		if ($coupons) {

			$temptime=time();

			foreach ($coupons as $k => $v){

				$code = get_rand_str(8,0,1);//获取随机8位字符串

				$sql = "INSERT INTO " .$GLOBALS['ecs']->table('coupon_list'). "(`cid`,`type`, `uid`,`send_time`,`supplier_id`,`use`,`code`)

				VALUES('$v[id]', '$v[type]', '$user_id','$temptime','$v[supplier_id]','$v[use]','$code')";

				$rs1=$GLOBALS['db']->query($sql);

			}

		}

		return true;

	}

	return false;

}



//赠送礼豆

function beanGiftLog($user_id = 0, $bean_gift = 0,$business_type = '', $desc = ''){

	

	   /* 插入帐户变动记录 */

	$change_type = $bean_gift > 0 ? 1 : 2; // 1为收入 2为支出

    $account_log = array(

        'user_id'       => $user_id,

        'bean_gift'     => $bean_gift,

        'change_time'   => time(),

		'business_type'	=> $business_type,

        'desc'          => $desc,

		'change_type'	=> $change_type

    );

	$change_time = time();

    /* 更新用户信息 */



	$sql = "UPDATE " .$GLOBALS['ecs']->table('users'). " SET bean_gift = bean_gift + '{$bean_gift}'  WHERE user_id = {$user_id}";





    if($GLOBALS['db']->query($sql)){

		$sql = "INSERT INTO " .$GLOBALS['ecs']->table('bean_gift_log'). "(`user_id`,`bean_gift`, `change_time`,`business_type`,`desc`,`change_type`)

				VALUES('$user_id', '$bean_gift', '$change_time','$business_type','$desc','$change_type')";

				$rs1=$GLOBALS['db']->query($sql);

        

    }else{

        return false;

    }

	

	

}

//绑定注册

function band_saveuser($user){

	if(empty($user)){

		return false;

	}

	$temptime=time();

	if(!empty($user['password'])){

		$password=md5pwd($user['password']);

	}else{

		$password="";

	}

	if($user['phone']){

		//查询电话，看是否修改和绑定

		$sql="SELECT user_id,openid,oauth FROM ".$GLOBALS['ecs']->table('users')." where mobile ='".$user['phone']."'";

		$row= $GLOBALS['db']->getRow($sql);

		

		if(!empty($row) && $row['openid'] !=$user['openid'] && empty($row['openid'])){

			//存在手机号码，就绑定到以前的手机号码

			$sql="UPDATE ".$GLOBALS['ecs']->table('users')." SET app_type= '{$user[app_type]}', device_token='{$user[device_token]}',head_pic='{$user[head_pic]}',openid='{$user[openid]}',oauth='{$user[oauth]}' where user_id={$row[user_id]}";

			$rs2=$GLOBALS['db']->query($sql);

			if($rs2){

				return "1";

			}

		}

	}

	

 	$sql = "INSERT INTO " .$GLOBALS['ecs']->table('users'). "(`nickname`,`password`, `reg_time`,`mobile`,`mobile_validated`,`app_type`,`device_token`,`head_pic`,`openid`,`unionid`,`oauth`)

 	VALUES('$user[nickname]', '$password', '$temptime','$user[phone]','1','$user[app_type]','$user[device_token]','$user[head_pic]','$user[openid]','$user[unionid]','$user[oauth]')";

	

 	$rs=$GLOBALS['db']->query($sql);



	

	//$rs=1;

	if($rs){

		$user_id = mysql_insert_id();

		$coupons=issuing_coupons();

		

		$is_coupons=0;

		if ($coupons) {

			$temptime=time();

			foreach ($coupons as $k => $v){

				$code = get_rand_str(8,0,1);//获取随机8位字符串

				$sql = "INSERT INTO " .$GLOBALS['ecs']->table('coupon_list'). "(`cid`,`type`, `uid`,`send_time`,`supplier_id`,`use`,`code`)

			 	VALUES('$v[id]', '$v[type]', '$user_id','$temptime','$v[supplier_id]','$v[use]','$code')";

				$rs1=$GLOBALS['db']->query($sql);

				if ($rs1) {

					$is_coupons=1;

				}

			}

		}

		if ($is_coupons==1) {

			return "3";

		}

		return "2";

	}

	return false;

}

/**

 * 获取随机字符串

 * @param int $randLength  长度

 * @param int $addtime  是否加入当前时间戳

 * @param int $includenumber   是否包含数字

 * @return string

 */

function get_rand_str($randLength=6,$addtime=1,$includenumber=0){

	if ($includenumber){

		$chars='abcdefghijklmnopqrstuvwxyzABCDEFGHJKLMNPQEST123456789';

	}else {

		$chars='abcdefghijklmnopqrstuvwxyz';

	}

	$len=strlen($chars);

	$randStr='';

	for ($i=0;$i<$randLength;$i++){

		$randStr.=$chars[rand(0,$len-1)];

	}

	$tokenvalue=$randStr;

	if ($addtime){

		$tokenvalue=$randStr.time();

	}

	return $tokenvalue;

}

function issuing_coupons(){

	$temptime=time();

	$where=" send_start_time <= {$temptime} AND send_end_time>={$temptime} AND `use` <> 1 ";

	$sql = "SELECT * ".

			" FROM " .$GLOBALS['ecs']->table('coupon').

			" WHERE {$where} and type = 2 and createnum >0";



	return $rs = $GLOBALS['db']->getAll($sql);

}







function get_regin_name($id){

	$sql="select name from ".$GLOBALS['ecs']->table('region')." where id={$id}";

	return $GLOBALS['db']->getOne($sql);

}



function get_regin_nameByID($name){

	$name=trim($name);

	$sql="select id from ".$GLOBALS['ecs']->table('region')." where name='{$name}'";

	return $GLOBALS['db']->getOne($sql);

}



function check_is_register($mobile){

	$sql="select count(*) from ".$GLOBALS['ecs']->table('users')." where mobile='{$mobile}'";

	return $GLOBALS['db']->getOne($sql);

}



//检测验证码

function check_image_key($img_key,$word,$agent){

	if(empty($img_key)||empty($word)){

		$results = array(

				'result'=>0,

				'info' => '缺少图形验证码信息'

		);

		return $results;

	}

	$img = decrypt($img_key, 'chenx');

	$arr = explode ('|', $img);

	$code_arr = array(

			'word'=>$arr[0],

			'ip'=>$arr[1],

			'time'=>$arr[2],

	);

	$ip = $_SERVER['REMOTE_ADDR'];

	if ($ip!=$code_arr['ip']){

		$results = array(

				'result'=>0,

				'info' => 'IP信息错误'

		);

		return $results;

	}

	$now = time();

	if ($now>$code_arr['time']){

		$results = array(

				'result'=>0,

				'info' => '图形验证码超时'

		);

		return $results;

	} 

	$sql="SELECT id FROM ".$GLOBALS['ecs']->table('app_vcode')." where toke='{$img_key}' AND type=2 ";

	$id=$GLOBALS['db']->getOne($sql);



	if($id){

		$results = array(

				'result'=>0,

				'info' => '图形验证码，请勿重复提交'

		);

		return $results;

	}

	//保存请求

	$param['app_name']   = '一礼通app';

	$param['toke']       = $img_key;

	$param['type']       = '2';

	include_once('includes/cls_sms1.php');

	$sms = new sms1();

	$sms->save_verify_log($param);

	if ($code_arr['word']!=strtoupper($word)){

		$results = array(

				'result'=>0,

				'info' => '图形验证码错误'

		);

		return $results;

	}



}



?>