<?php
define('IN_ECS', true);
require('init.php');
$affiliate = unserialize($GLOBALS['_CFG']['affiliate']);
header("Content-Type:text/html;charset=UTF-8");
$action  = $_REQUEST['act'];
$ticket = $_REQUEST['ticket'];
$userinfo = '';
$msg='';
if(!empty($ticket)){
	$userinfo = split_user_ticket($ticket);
}

//支付宝接口 (暂时未使用，用异步接口)
elseif ($action=="alipay"){
	$result=$_REQUEST['result'];
	//$result = "{\"alipay_trade_app_pay_response\":{\"code\":\"10000\",\"msg\":\"Success\",\"app_id\":\"2016093002016103\",\"auth_app_id\":\"2016093002016103\",\"charset\":\"UTF-8\",\"timestamp\":\"2017-05-04 21:01:11\",\"total_amount\":\"0.02\",\"trade_no\":\"2017050421001004790242928754\",\"seller_id\":\"2088421463111050\",\"out_trade_no\":\"2017042410544\"},\"sign\":\"h/SQQ++nOqfDdIXR2qjogpGj7QuGm68rg7iOv08JFLqVLMKIaMJbwicn11W9whp0J8j4vcEgH1UIDlQW/zUg1S+d22hNjWY+jJRGmBDp8y+BLuBanXhnG8njydqpurOkhjy9aZUQYSO7fHvTmtzAxdZ/Ew89kgahRedm7KqcYqE=\",\"sign_type\":\"RSA\"}";
	$result=json_decode($result,true);
	$response=$result['alipay_trade_app_pay_response'];
	if($response['code']=="10000" && $response['msg']=='Success' && !empty($response['out_trade_no'])){
		//查询订单
		$sql="SELECT order_sn,total_amount,pay_status,order_status from ".$GLOBALS['ecs']->table('order')." where order_sn='{$response[out_trade_no]}' ";
		$order=$GLOBALS['db']->getRow($sql);
		
		if($order['pay_status']=='1'){
			$rs=array('result'=>'1','info'=>'已支付成功');
			exit($json->json_encode_ex($rs));
		}
		if($order['order_sn']==$response['out_trade_no'] && $response['total_amount']==$order['total_amount']){
			//修改状态
			$flag=update_order($order['order_sn'],'alipay');
			if($flag){
				$rs=array('result'=>'1','info'=>'支付成功');
				exit($json->json_encode_ex($rs));
			}else{
				$rs=array('result'=>'0','info'=>'修改订单失败');
				exit($json->json_encode_ex($rs));
			}
		}else{
			$rs=array('result'=>'0','info'=>'失败');
			exit($json->json_encode_ex($rs));
		}
	}else{
		$rs=array('result'=>'0','info'=>'支付失败');
		exit($json->json_encode_ex($rs));
	}
	
}
//获取支付宝订单处理信息
elseif($action=="ali_payData"){
	
	$order_sn=$_REQUEST['order_sn'];
	
	$order_sn=trim($_REQUEST['order_sn']);
	
		$pos = strpos($order_sn,'us');
		if($pos === false){
			$sql="SELECT order_sn,total_amount,order_amount from ".$GLOBALS['ecs']->table('order')." where order_sn='{$order_sn}' AND pay_status=0";
			$order=$GLOBALS['db']->getRow($sql);
		}else{
			$sql="SELECT order_sn,total_amount,order_amount from ".$GLOBALS['ecs']->table('entry_order')." where order_sn='{$order_sn}' AND pay_status=0";
			$order=$GLOBALS['db']->getRow($sql);
			
			$sql=" update ".$GLOBALS['ecs']->table('entry_order')." set pay_code='alipy',pay_name='支付宝支付' where order_sn='{$order_sn}' ";
			$GLOBALS['db']->query($sql);

		}
		
	
	
	if($order){
		require(ROOT_PATH.'includes/alipay/Alipay.php');
		$pay=new Alipay();
		$rs=$pay->payData($order);
		$rs=array('result'=>'1','info'=>'成功','data'=>base64_encode($rs));
		exit(json_encode($rs));
	}else{
		$rs=array('result'=>'0','info'=>'失败');
		exit(json_encode($rs));
	}
}

//微信支付,成功查询订单号(暂无使用，统一用异步)
elseif ($action=="weixin_pay"){
	    $order_sn=$_REQUEST['order_sn'];
		//$order_sn="2017042421260073601493113717";
		$data['order_sn']=$order_sn;
		$data['pay_code']="weixin";
		//http://www.tp5.com/home/payment/get_orderType?pay_code=weixin
		$url=BUSI_SERVER."payment/get_orderType";
		//$url="http://www.tp5.com/home/payment/get_orderType";
		$sql="SELECT count(*) from ".$GLOBALS['ecs']->table('order')." where order_sn='{$order_sn}' AND pay_status=1";
		$count=$GLOBALS['db']->getOne($sql);
		if($count>0){
			$rs=array('result'=>'1','info'=>'支付成功');
			exit($json->json_encode_ex($rs));
		}
		$flag=file_get_contents_curl($url,$data);
		if($flag=='true'){
			update_order($order_sn,'weixin');
			$rs=array('result'=>'1','info'=>'支付成功');
			exit($json->json_encode_ex($rs));
		}else{
			$rs=array('result'=>'0','info'=>'支付失败');
			exit($json->json_encode_ex($rs));
		}
}

//微信生成预支付订单参数
elseif($action=="pay_order"){
	$order_sn=trim($_REQUEST['order_sn']);
	
		$pos = strpos($order_sn,'us');
		if($pos === false){
			$sql="SELECT order_sn,total_amount,order_amount from ".$GLOBALS['ecs']->table('order')." where order_sn='{$order_sn}' AND pay_status=0";
			$order=$GLOBALS['db']->getRow($sql);
			
		}else{
			$sql="SELECT order_sn,total_amount,order_amount from ".$GLOBALS['ecs']->table('entry_order')." where order_sn='{$order_sn}' AND pay_status=0";
			$order=$GLOBALS['db']->getRow($sql);

			$sql=" update ".$GLOBALS['ecs']->table('entry_order')." set pay_code='weixin',pay_name='app微信支付' where order_sn='{$order_sn}' ";
			$GLOBALS['db']->query($sql);
		
		}
	
	$data['order_sn']=$order_sn;
	$data['pay_code']="weixin";
	//$url=BUSI_SERVER."payment/pay_order";
	//$url="http://www.tp5.com/home/payment/pay_order";
	
	if(!empty($order)){
		$data['order_amount']=$order['order_amount'];
		require(ROOT_PATH.'includes/weixin/weixin.class.php');
		$pay=new weixin();
		$rs=$pay->pay_order($data);
		if($rs['return_code']=='FAIL'){
			$rs=array('result'=>'0','info'=>'失败');;
			exit($json->json_encode_ex($rs));
		}else{
			$rs=array('result'=>'1','info'=>'请求成功','data'=>$rs);
			exit($json->json_encode_ex($rs));
		}
	}
	
	$rs=array('result'=>'0','info'=>'失败');
	exit($json->json_encode_ex($rs));
}

//支付宝退款接口
elseif($action=="return_alipay"){
	    $order_sn=$_POST['order_sn'];
	    $money=$_POST['money'];
	    $out_request_no=$_POST['out_request_no'];
	    $pwd=$_POST['pwd'];
	    if(!empty($order_sn)){
	    	$sql="SELECT mobile from ".$GLOBALS['ecs']->table('order')." where order_sn='{$order_sn}' ";
	    	$one=$GLOBALS['db']->getOne($sql);
		    $password=sha1((md5($one).'ylt'));
	    	if(empty($one) || $pwd!=$password){
	    		//$rs=array("result"=>"0","info"=>"参数密码错误");
	    		exit($json->json_encode_ex(false));
	    	}
	    }
	    if(empty($order_sn) || empty($money) || empty($out_request_no)){
	    	//$rs=array("result"=>"0","info"=>"缺少必要参数");
	    	exit($json->json_encode_ex(false));
	    }
	    require(ROOT_PATH.'includes/alipay/Alipay.php');
		$pay=new Alipay();
		$res=$pay->return_order($order_sn,$money,$out_request_no);
		if($res=='1'){
			//$rs=array('result'=>"1","info"=>"退款成功");
			exit($json->json_encode_ex(true));
		}else{
			//$rs=array("result"=>"0","info"=>"退款失败","resultCode"=>"{$res}");
			exit($json->json_encode_ex(false));
		} 
}
//微信退款接口
elseif($action=="return_weixin"){
	    
		$money=$_REQUEST['money'];
    	$order_sn=$_REQUEST['order_sn'];
    	$total_money=$_REQUEST['total_amount'];
    	$pwd=$_POST['pwd'];
    	
    	if(!empty($order_sn)){
    		$sql="SELECT B.mobile from ".$GLOBALS['ecs']->table('order')." as A LEFT JOIN ".$GLOBALS['ecs']->table('users')." as B ON A.user_id=B.user_id where order_sn='{$order_sn}' ";
    		$one=$GLOBALS['db']->getOne($sql);
    		$password=sha1((md5($one).'ylt'));
    		if(empty($one) || $pwd!=$password){
    			$rs=array('result'=>'0','info'=>'参数密码错误');
    			exit($json->json_encode_ex($rs));
    		}
    	}
		require(ROOT_PATH.'includes/weixin/weixin.class.php');
		$pay=new weixin();
		$res=$pay->return_order($data);
		
		if($res['return_code']=='SUCCESS'){
			$rs=array('result'=>'1','info'=>'退款成功');
			exit($json->json_encode_ex($rs));
		}else{
			$rs=array('result'=>'0','info'=>"退款失败",'resultCode'=>$res['err_code']);
			exit($json->json_encode_ex($rs));
		}
	
}


elseif($action=="test_pay"){
	//2017050922271805854
	$arr=array (
  's' => 'home/paynotice/alipay_notify',
  'total_amount' => '0.03',
  'buyer_id' => '2088412642758843',
  'trade_no' => '2017051021001004840247340726',
  'body' => 'app支付',
  'notify_time' => '2017-05-10 16:46:46',
  'subject' => '一礼通',
  'sign_type' => 'RSA',
  'buyer_logon_id' => '137***@163.com',
  'auth_app_id' => '2016093002016103',
  'charset' => 'UTF-8',
  'notify_type' => 'trade_status_sync',
  'invoice_amount' => '0.03',
  'out_trade_no' => '2017051016470765113',
  'trade_status' => 'TRADE_SUCCESS',
  'gmt_payment' => '2017-05-10 16:46:46',
  'version' => '1.0',
  'point_amount' => '0.00',
  'sign' => 'Acqnjwdcl0MaJqJrYnuonS9gg1LflgX3hW/J0hbCU2z7txformDhJHzo5hZ8uW0SGKMihtYK3veG3hHqKcQ0ozAEPq0BnBT27deLJoa/BLujdNFN3djUNVRhFwixHTQ/F7aoMtcOuKXbi+cmv2jO1cUbSPm7TEYFaOOpI/pIZsw=',
  'gmt_create' => '2017-05-10 16:46:45',
  'buyer_pay_amount' => '0.03',
  'receipt_amount' => '0.03',
  'fund_bill_list' => '[{"amount":"0.03","fundChannel":"ALIPAYACCOUNT"}]',
  'app_id' => '2016093002016103',
  'seller_id' => '2088421463111050',
  'notify_id' => '1da0711b62bfbe6091d730d263bbb28mhe',
  'seller_email' => '196888180@qq.com',
);
	//$sign=$arr['sign'];
	//unset($arr['sign']);
	//unset($arr['sign_type']);
	require(ROOT_PATH.'includes/alipay/Alipay.php');
	$aop=new AopClient();
	$aop->gatewayUrl = "https://openapi.alipay.com/gateway.do";
	$aop->appId = "2016093002016103";
	$aop->rsaPrivateKey = 'MIICXgIBAAKBgQDkfQo4U+5lwwKZzDvwV0RwvSfh2WweDo3khUvsNvDv4YgwDPz26Mc/xybXHN47n7i85lpzAbKJltPJAzBdtyXim3mKSxKVc313E5y8i4hvfyAUvDqYSKGl8Qj/Dy5Cl2RoWqNqWfCuRN/pG6I5xsE3AgKMIMaHtiluoF+R0Ah0JwIDAQABAoGBALwejvmNcOxrwIpr8rWQxBKmSl3SqwecKAsMDFRxb7Gw2HXnW6bWRKYoC7x0Uix49prgdXvW2+4YNkp7y6h9EDySQkk82vSw0edEWJ3ESwE++/q2CaCkXZaJkzFw3GWkrx1Vilzpp2q8lAX70/cTBahsiPN5NbYWT8NecLnLuAlBAkEA9fopZwq2VejkQNTVHVfYxg4xQjZaTr+Egj0urLdJDxDk7RBvAKfc4rB0/kmlyOiXj7erMuEsyWb6962rlEb9UQJBAO3McwwI7U1uDF/fDmp0/wPa8xOEKu6XKg/op8M2xUz6spM3yEBMeCGemnqTBnN3daM6wcup+iq0HZp/xy8Km/cCQBQUdejJgRUGTAvW1AbvMu0IH5FOKpUfIUwYfoTu+XHXaTjJDKa7DVccHJDdpkD+a9D5p2oh46wVUguCC+2w1eECQQDFDB9hH5yUBtbWMp1ddalDZpD54RE6N6SxHha12pLPYQXMm/Kh5Tu+kBBt9Zro31ppcezYePdFn47QUYWZ426tAkEA82+z1RXDvnLYza7FdBXGuuKcQSMBkyGrjQAQfmNnLQfwMIrDPoHUjNM7Gxp6EYEytLhn0xFeUzDRSQKDomb1TA==' ;
	$aop->format = "json";
	$aop->charset = "UTF-8";
	$aop->signType = "RSA";
	
	$aop->alipayrsaPublicKey = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDkfQo4U+5lwwKZzDvwV0RwvSfh2WweDo3khUvsNvDv4YgwDPz26Mc/xybXHN47n7i85lpzAbKJltPJAzBdtyXim3mKSxKVc313E5y8i4hvfyAUvDqYSKGl8Qj/Dy5Cl2RoWqNqWfCuRN/pG6I5xsE3AgKMIMaHtiluoF+R0Ah0JwIDAQAB';
	//$publi= 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDkfQo4U+5lwwKZzDvwV0RwvSfh2WweDo3khUvsNvDv4YgwDPz26Mc/xybXHN47n7i85lpzAbKJltPJAzBdtyXim3mKSxKVc313E5y8i4hvfyAUvDqYSKGl8Qj/Dy5Cl2RoWqNqWfCuRN/pG6I5xsE3AgKMIMaHtiluoF+R0Ah0JwIDAQAB';
	
	
	//$aop->alipayrsaPublicKey = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDDI6d306Q8fIfCOaTXyiUeJHkrIvYISRcc73s3vF1ZT7XN8RNPwJxo8pWaJMmvyTn9N4HQ632qJBVHf8sxHi/fEsraprwCtzvzQETrNRwVxLO5jVmRGi60j8Ue1efIlzPXV9je9mkjzOmdssymZkh2QhUrCmZYI/FCEa3/cNMW0QIDAQAB';
	$publi= "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDDI6d306Q8fIfCOaTXyiUeJHkrIvYISRcc73s3vF1ZT7XN8RNPwJxo8pWaJMmvyTn9N4HQ632qJBVHf8sxHi/fEsraprwCtzvzQETrNRwVxLO5jVmRGi60j8Ue1efIlzPXV9je9mkjzOmdssymZkh2QhUrCmZYI/FCEa3/cNMW0QIDAQAB";
	//print_r($str);
	$rs=$aop->rsaCheckV1($arr,$publi,'RSA');
	var_dump($rs);
	
}


//修改订单状态
function update_order($order,$type){
	if($type=="alipay"){
		$sql="SELECT count(*) from ".$GLOBALS['ecs']->table('order')." where order_sn='{$order}' AND order_status=0";
		$count=$GLOBALS['db']->getOne($sql);
		if($count>0){
			$sql=" update ".$GLOBALS['ecs']->table('order')." set order_status=1,pay_status=1,pay_code='alipay',pay_name='app支付宝',source='app_alipay' where order_sn='{$order}'";
			return $GLOBALS['db']->query($sql);
		}
		return false;
	}else{
		$sql="SELECT count(*) from ".$GLOBALS['ecs']->table('order')." where order_sn='{$order}' AND order_status=0";
		$count=$GLOBALS['db']->getOne($sql);
		if($count>0){
			$sql=" update ".$GLOBALS['ecs']->table('order')." set order_status=1,pay_status=1,pay_name='app微信支付',pay_code='weixinpay',source='app_weixinpay' where order_sn='{$order}' ";
			return $GLOBALS['db']->query($sql);
		}
		return false;
	}
	return false;
	
}
function file_get_contents_curl($url, $data)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'accept-language:zh-CN,zh;q=0.8,zh-TW;q=0.6,en;q=0.4',
        'cache-control:max-age=0',
        'upgrade-insecure-requests:1',
        'user-agent:Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.94 Safari/537.36',
    ));

    curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $dxycontent = curl_exec($ch);
    return $dxycontent;
}
