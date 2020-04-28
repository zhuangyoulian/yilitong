<?php
/**
 * Alipay.com Inc.
 * Copyright (c) 2004-2014 All Rights Reserved.
 */



include('AopClient.php');
include('request/AlipayTradeAppPayRequest.php');
include('request/AlipayTradeRefundRequest.php');
header("Content-type: text/html; charset=UTF-8");
date_default_timezone_set("PRC");

/**
 *
 * @author wangYuanWai
 * @version $Id: Test.hp, v 0.1 Aug 6, 2014 4:20:17 PM yikai.hu Exp $
 */
class Alipay{

	function __construct(){

	}

	public function payData($order) {
		$aop = new AopClient;
		$aop->gatewayUrl = "https://openapi.alipay.com/gateway.do";
		$aop->appId = "2016093002016103";
		$aop->rsaPrivateKey = 'MIICXgIBAAKBgQDkfQo4U+5lwwKZzDvwV0RwvSfh2WweDo3khUvsNvDv4YgwDPz26Mc/xybXHN47n7i85lpzAbKJltPJAzBdtyXim3mKSxKVc313E5y8i4hvfyAUvDqYSKGl8Qj/Dy5Cl2RoWqNqWfCuRN/pG6I5xsE3AgKMIMaHtiluoF+R0Ah0JwIDAQABAoGBALwejvmNcOxrwIpr8rWQxBKmSl3SqwecKAsMDFRxb7Gw2HXnW6bWRKYoC7x0Uix49prgdXvW2+4YNkp7y6h9EDySQkk82vSw0edEWJ3ESwE++/q2CaCkXZaJkzFw3GWkrx1Vilzpp2q8lAX70/cTBahsiPN5NbYWT8NecLnLuAlBAkEA9fopZwq2VejkQNTVHVfYxg4xQjZaTr+Egj0urLdJDxDk7RBvAKfc4rB0/kmlyOiXj7erMuEsyWb6962rlEb9UQJBAO3McwwI7U1uDF/fDmp0/wPa8xOEKu6XKg/op8M2xUz6spM3yEBMeCGemnqTBnN3daM6wcup+iq0HZp/xy8Km/cCQBQUdejJgRUGTAvW1AbvMu0IH5FOKpUfIUwYfoTu+XHXaTjJDKa7DVccHJDdpkD+a9D5p2oh46wVUguCC+2w1eECQQDFDB9hH5yUBtbWMp1ddalDZpD54RE6N6SxHha12pLPYQXMm/Kh5Tu+kBBt9Zro31ppcezYePdFn47QUYWZ426tAkEA82+z1RXDvnLYza7FdBXGuuKcQSMBkyGrjQAQfmNnLQfwMIrDPoHUjNM7Gxp6EYEytLhn0xFeUzDRSQKDomb1TA==' ;
		$aop->format = "json";
		$aop->charset = "UTF-8";
		$aop->signType = "RSA";
		//$aop->alipayrsaPublicKey = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDkfQo4U+5lwwKZzDvwV0RwvSfh2WweDo3khUvsNvDv4YgwDPz26Mc/xybXHN47n7i85lpzAbKJltPJAzBdtyXim3mKSxKVc313E5y8i4hvfyAUvDqYSKGl8Qj/Dy5Cl2RoWqNqWfCuRN/pG6I5xsE3AgKMIMaHtiluoF+R0Ah0JwIDAQAB';
		
		$aop->alipayrsaPublicKey ="MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDDI6d306Q8fIfCOaTXyiUeJHkrIvYISRcc73s3vF1ZT7XN8RNPwJxo8pWaJMmvyTn9N4HQ632qJBVHf8sxHi/fEsraprwCtzvzQETrNRwVxLO5jVmRGi60j8Ue1efIlzPXV9je9mkjzOmdssymZkh2QhUrCmZYI/FCEa3/cNMW0QIDAQAB";
		
		//实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.app.pay
		$request = new AlipayTradeAppPayRequest();
		$order_sn=$order['order_sn'];
		$total_amount=$order['order_amount'];
		//SDK已经封装掉了公共参数，这里只需要传入业务参数
		$bizcontent = "{\"body\":\"app支付\","
				. "\"subject\": \"一礼通\","
				. "\"out_trade_no\": \"{$order_sn}\","
				. "\"timeout_express\": \"30m\","
				. "\"total_amount\": \"{$total_amount}\","
				. "\"product_code\":\"QUICK_MSECURITY_PAY\""
				. "}";
	
		$request->setNotifyUrl("http://www.yilitong.com/home/paynotice/alipay_notify");
		$request->setBizContent($bizcontent);
		//这里和普通的接口调用不同，使用的是sdkExecute
		$response = $aop->sdkExecute($request);
		//htmlspecialchars是为了输出到页面时防止被浏览器将关键参数html转义，实际打印到日志以及http传输不会有这个问题
		return $response;//就是orderString 可以直接给客户端请求，无需再做处理。
	}
	
	//退款订单
	public function return_order($order_sn,$money,$str){
		$aop = new AopClient ();
		$aop->gatewayUrl = "https://openapi.alipay.com/gateway.do";
		$aop->appId = "2016093002016103";
		$aop->rsaPrivateKey = 'MIICXgIBAAKBgQDkfQo4U+5lwwKZzDvwV0RwvSfh2WweDo3khUvsNvDv4YgwDPz26Mc/xybXHN47n7i85lpzAbKJltPJAzBdtyXim3mKSxKVc313E5y8i4hvfyAUvDqYSKGl8Qj/Dy5Cl2RoWqNqWfCuRN/pG6I5xsE3AgKMIMaHtiluoF+R0Ah0JwIDAQABAoGBALwejvmNcOxrwIpr8rWQxBKmSl3SqwecKAsMDFRxb7Gw2HXnW6bWRKYoC7x0Uix49prgdXvW2+4YNkp7y6h9EDySQkk82vSw0edEWJ3ESwE++/q2CaCkXZaJkzFw3GWkrx1Vilzpp2q8lAX70/cTBahsiPN5NbYWT8NecLnLuAlBAkEA9fopZwq2VejkQNTVHVfYxg4xQjZaTr+Egj0urLdJDxDk7RBvAKfc4rB0/kmlyOiXj7erMuEsyWb6962rlEb9UQJBAO3McwwI7U1uDF/fDmp0/wPa8xOEKu6XKg/op8M2xUz6spM3yEBMeCGemnqTBnN3daM6wcup+iq0HZp/xy8Km/cCQBQUdejJgRUGTAvW1AbvMu0IH5FOKpUfIUwYfoTu+XHXaTjJDKa7DVccHJDdpkD+a9D5p2oh46wVUguCC+2w1eECQQDFDB9hH5yUBtbWMp1ddalDZpD54RE6N6SxHha12pLPYQXMm/Kh5Tu+kBBt9Zro31ppcezYePdFn47QUYWZ426tAkEA82+z1RXDvnLYza7FdBXGuuKcQSMBkyGrjQAQfmNnLQfwMIrDPoHUjNM7Gxp6EYEytLhn0xFeUzDRSQKDomb1TA==' ;
		$aop->format = "json";
		$aop->charset = "UTF-8";
		$aop->signType = "RSA";
		$aop->format='json';
		$aop->alipayrsaPublicKey ="MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDDI6d306Q8fIfCOaTXyiUeJHkrIvYISRcc73s3vF1ZT7XN8RNPwJxo8pWaJMmvyTn9N4HQ632qJBVHf8sxHi/fEsraprwCtzvzQETrNRwVxLO5jVmRGi60j8Ue1efIlzPXV9je9mkjzOmdssymZkh2QhUrCmZYI/FCEa3/cNMW0QIDAQAB";
		
		$request = new AlipayTradeRefundRequest();
		$request->setBizContent("{" .
				"    \"out_trade_no\":\"{$order_sn}\"," .
				"    \"trade_no\":\"\"," .
				"    \"refund_amount\":{$money}," .
				"    \"refund_reason\":\"正常退款\"," .
				"    \"out_request_no\":\"{$str}\"" .
				"  }");
		$result = $aop->execute ( $request);
		$responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
		$resultCode = $result->$responseNode->code;
		if(!empty($resultCode)&&$resultCode == 10000){
			return '1';
		} else {
			return $resultCode;
		}
	}
}

