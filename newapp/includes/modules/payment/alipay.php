<?php


if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

$payment_lang = ROOT_PATH . 'languages/' .$GLOBALS['_CFG']['lang']. '/payment/alipay.php';

if (file_exists($payment_lang))
{
    global $_LANG;

    include_once($payment_lang);
}




/* 模块的基本信息 */
if (isset($set_modules) && $set_modules == TRUE)
{
    $i = isset($modules) ? count($modules) : 0;

    /* 代码 */
    $modules[$i]['code']    = basename(__FILE__, '.php');

    /* 描述对应的语言项 */
    $modules[$i]['desc']    = 'alipay_desc';

    /* 是否支持货到付款 */
    $modules[$i]['is_cod']  = '0';

    /* 是否支持在线支付 */
    $modules[$i]['is_online']  = '1';

    /* 作者 */
    $modules[$i]['author']  = 'ECSHOP TEAM';

    /* 网址 */
    $modules[$i]['website'] = 'http://www.alipay.com';

    /* 版本号 */
    $modules[$i]['version'] = '1.0.2';

    /* 配置信息 */
    $modules[$i]['config']  = array(
        array('name' => 'alipay_account',           'type' => 'text',   'value' => ''),
        array('name' => 'alipay_key',               'type' => 'text',   'value' => ''),
        array('name' => 'alipay_partner',           'type' => 'text',   'value' => ''),
        array('name' => 'alipay_pay_method',        'type' => 'select', 'value' => '')
    );

    return;
}

/**
 * 类
 */
class alipay
{

    /**
     * 构造函数
     *
     * @access  public
     * @param
     *
     * @return void
     */

	
    function alipay()
    {
    }

    function __construct()
    {
        $this->alipay();
    }

    /**
     * 生成支付代码
     * @param   array   $order      订单信息
     * @param   array   $payment    支付方式信息
     */
/*     function get_code($order, $payment)
    {
        if (!defined('EC_CHARSET'))
        {
            $charset = 'utf-8';
        }
        else
        {
            $charset = EC_CHARSET;
        }

        $real_method = $payment['alipay_pay_method'];

        switch ($real_method){
            case '0':
                $service = 'trade_create_by_buyer';
                break;
            case '1':
                $service = 'create_partner_trade_by_buyer';
                break;
            case '2':
                $service = 'create_direct_pay_by_user';
                break;
        }

        $extend_param = 'isv^sh22';

        $parameter = array(
            'extend_param'      => $extend_param,
            'service'           => $service,
            'partner'           => $payment['alipay_partner'],
            //'partner'           => ALIPAY_ID,
            '_input_charset'    => $charset,
            'notify_url'        => return_url(basename(__FILE__, '.php')),
            'return_url'        => return_url(basename(__FILE__, '.php')),
            //业务参数
            'subject'           => $order['order_sn'],
            'out_trade_no'      => $order['order_sn'] . $order['log_id'],
            'price'             => $order['order_amount'],
            'quantity'          => 1,
            'payment_type'      => 1,
            //物流参数
            'logistics_type'    => 'EXPRESS',
            'logistics_fee'     => 0,
            'logistics_payment' => 'BUYER_PAY_AFTER_RECEIVE',
            //买卖双方信息
            'seller_email'      => $payment['alipay_account']
        );

        ksort($parameter);
        reset($parameter);

        $param = '';
        $sign  = '';

        foreach ($parameter AS $key => $val)
        {
            $param .= "$key=" .urlencode($val). "&";
            $sign  .= "$key=$val&";
        }

        $param = substr($param, 0, -1);
        $sign  = substr($sign, 0, -1). $payment['alipay_key'];
        //$sign  = substr($sign, 0, -1). ALIPAY_AUTH;

        $button = '<div style="text-align:center"><input type="button" onclick="window.open(\'https://mapi.alipay.com/gateway.do?'.$param. '&sign='.md5($sign).'&sign_type=MD5\')" value="' .$GLOBALS['_LANG']['pay_button']. '" /></div>';

        return $button;
    } */
    
    function get_code($order){
    	
    	require_once("alipay.config.php");
    	require_once("alipay_submit.class.php");

    	
    	/**************************请求参数**************************/
    	
    	//支付类型
    	$payment_type = "1";
    	//必填，不能修改
    	//服务器异步通知页面路径
    	$notify_url = $GLOBALS['ecs']->url() . 'backrespond.php';//更换支付服务器
    	//$notify_url = 'http://pay.youkastation.com/backrespond.php';
    	//需http://格式的完整路径，不能加?id=123这类自定义参数
    	
    	//页面跳转同步通知页面路径
    	//$return_url = $GLOBALS['ecs']->url() . 'respond.php';
    	$return_url = $GLOBALS['ecs']->url() . 'user.php';
    	//需http://格式的完整路径，不能加?id=123这类自定义参数，不能写成http://localhost/
    	
    	//商户订单号
    	$out_trade_no = $order['order_sn'] . $order['log_id'];
    	//商户网站订单系统中唯一订单号，必填
    	
    	//订单名称
    	$subject = $order['order_sn'];
    	//必填
    	
    	//付款金额
    	$total_fee = $order['order_amount'];
    	//必填
    	
    	//默认支付方式
    	$paymethod = "bankPay";
    	//必填
    	//默认网银
    	$defaultbank = 'CMB';
    	//必填，银行简码请参考接口技术文档    	
    	
    	
    	/************************************************************/
    	
    	//构造要请求的参数数组，无需改动
    	$parameter = array(
    			"service" => "create_direct_pay_by_user",
    			"partner" => trim($alipay_config['partner']),
    			"seller_email" => trim($alipay_config['seller_email']),
    			"payment_type"	=> $payment_type,
    			"notify_url"	=> $notify_url,
    			"return_url"	=> $return_url,
    			"out_trade_no"	=> $out_trade_no,
    			"subject"	=> $subject,
    			"total_fee"	=> $total_fee,
    			"paymethod"	=> $paymethod,
    			"defaultbank"	=> $defaultbank,
    			"extra_common_param"	=> 'alipay',
    			"_input_charset"	=> trim(strtolower($alipay_config['input_charset']))
    	);
    	
    	//建立请求
    	$alipaySubmit = new AlipaySubmit($alipay_config);
    	$html_text = $alipaySubmit->buildRequestForm($parameter,"get", "确认");
    	return $html_text;
    	
    }
    
 function get_mobile_code($order){
    	 
    	require_once("alipay.mobile.config.php");
    	require_once("alipay_submit.class.php");
    
    	 
    	/**************************请求参数**************************/
    	 
    	//支付类型
    	$payment_type = "1";
    	//必填，不能修改
    	//服务器异步通知页面路径
    	$notify_url = $GLOBALS['ecs']->url() . 'malirespond.php';//更换支付服务器
    	//$notify_url = 'http://pay.youkastation.com/malirespond.php';
    	//需http://格式的完整路径，不能加?id=123这类自定义参数
    	 
    	//页面跳转同步通知页面路径
    	$return_url = $GLOBALS['ecs']->url() . 'respond.php';
    	//$return_url = '';
    	//需http://格式的完整路径，不能加?id=123这类自定义参数，不能写成http://localhost/
    	 
    	//商户订单号
    	$out_trade_no = $order['order_sn'] . $order['log_id'];
    	//商户网站订单系统中唯一订单号，必填
    	 
    	//订单名称
    	$subject = $order['order_sn'];
    	//必填
    	 
    	//付款金额
    	$total_fee = $order['order_amount'];
    	//必填
    	 
    	//默认支付方式
    	/* $paymethod = "bankPay";
    	//必填
    	//默认网银
    	$defaultbank = 'CMB'; */
    	
    	//必填，银行简码请参考接口技术文档
    	$show_url = '';
    	   
    	/************************************************************/
    	 
    	//构造要请求的参数数组，无需改动
    	$parameter = array(
    			"service" => "alipay.wap.create.direct.pay.by.user",
    			"partner" => trim($alipay_config['partner']),
    			"seller_id" => trim($alipay_config['seller_id']),
    			"payment_type"	=> $payment_type,
    			"notify_url"	=> $notify_url,
    			"return_url"	=> $return_url,
    			"out_trade_no"	=> $out_trade_no,
    			"subject"	=> $subject,
    			"total_fee"	=> $total_fee,
    			"show_url"	=> $show_url,
    			"body"	=> 'alipay',
    			"_input_charset"	=> trim(strtolower($alipay_config['input_charset']))
    	);
    	 
    	//建立请求
    	$alipaySubmit = new AlipaySubmit($alipay_config);
    	$html_text = $alipaySubmit->buildRequestForm($parameter,"get", "确认");
    	return $html_text;
    	 
    }

    /**
     * 响应操作
     */
    function respond1()
    {
    	require_once("alipay.config.php");
    	require_once("alipay_notify.class.php");
    	
    	if (!empty($_POST))
        {
            foreach($_POST as $key => $data)
            {
                $_GET[$key] = $data;
            }
        }
        $payment  = get_payment($_GET['code']);
        $seller_email = rawurldecode($_GET['seller_email']);
        $order_sn = trim(addslashes($_GET['out_trade_no'])) ;
        $order_sn = trim(addslashes($order_sn)) ;

        /* 检查数字签名是否正确 */
        ksort($_GET);
        reset($_GET);

        $sign = '';
        foreach ($_GET AS $key=>$val)
        {
            if ($key != 'sign' && $key != 'sign_type' && $key != 'code')
            {
                $sign .= "$key=$val&";
            }
        }

        $sign = substr($sign, 0, -1) . $payment['alipay_key'];
        //$sign = substr($sign, 0, -1) . ALIPAY_AUTH;
        if (md5($sign) != $_GET['sign'])
        {
            return false;
        }

        /* 检查支付的金额是否相符 */
        if (!check_money($order_sn, $_GET['total_fee']))
        {
            return false;
        }

        if ($_GET['trade_status'] == 'WAIT_SELLER_SEND_GOODS')
        {
            /* 改变订单状态 */
            order_paid($order_sn, 2);

            return true;
        }
        elseif ($_GET['trade_status'] == 'TRADE_FINISHED')
        {
            /* 改变订单状态 */
            order_paid($order_sn);

            return true;
        }
        elseif ($_GET['trade_status'] == 'TRADE_SUCCESS')
        {
            /* 改变订单状态 */
            order_paid($order_sn, 2);

            return true;
        }
        else
        {
            return false;
        }
    }
    
    function respond(){
    	
    	if($_REQUEST['sign_type']=='MD5'){
    		require_once("alipay.config.php");
    		require_once("alipay_notify.class.php");
    	}
    	else{
    		require_once("alipay.mobile.config.php");
    		require_once("alipay_notify.class.php");
    	}
    	
    	
    	//计算得出通知验证结果
    	$alipayNotify = new AlipayNotify($alipay_config);
    	$verify_result = $alipayNotify->verifyReturn();
    	$notify_verify_result = $alipayNotify->verifyNotify();
    	if($verify_result | $notify_verify_result) {//验证成功
    		/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    		//请在这里加上商户的业务逻辑程序代码
    	
    		//——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
    		//获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表
    	
    		//商户订单号
    	
    		$out_trade_no = $_REQUEST['out_trade_no'];
    	
    		//支付宝交易号
    	
    		$trade_no = $_REQUEST['trade_no'];
    	
    		//交易状态
    		$trade_status = $_REQUEST['trade_status'];
    		
    		$order_sn = $_REQUEST['subject'];
    	
    	
    	
    		if($_REQUEST['trade_status'] == 'TRADE_FINISHED' || $_REQUEST['trade_status'] == 'TRADE_SUCCESS') {
    			order_paid_status($order_sn,'alipay',json_encode($_REQUEST));
    			return true;
    		}
			
    	
    		//——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
    	
    		/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    	}
    	else {
    		return false;
    	}
    }
    
    function respond2(){
    	 
    	require_once("alipay.mobile.config.php");
    	require_once("alipay_notify.class.php");
    	 
    	 
    	//计算得出通知验证结果
    	$alipayNotify = new AlipayNotify($alipay_config);
    	$verify_result = $alipayNotify->verifyReturn();
    	$notify_verify_result = $alipayNotify->verifyNotify();
    	if($verify_result | $notify_verify_result) {//验证成功
    		/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    		//请在这里加上商户的业务逻辑程序代码
    		 
    		//——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
    		//获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表
    		 
    		//商户订单号
    		 
    		$out_trade_no = $_REQUEST['out_trade_no'];
    		 
    		//支付宝交易号
    		 
    		$trade_no = $_REQUEST['trade_no'];
    		 
    		//交易状态
    		$trade_status = $_REQUEST['trade_status'];
    
    		$order_sn = $_REQUEST['subject'];
    
    	
    		 
    		 
    		if($_REQUEST['trade_status'] == 'TRADE_FINISHED' || $_REQUEST['trade_status'] == 'TRADE_SUCCESS') {
    			order_paid_status($order_sn,'malipay',json_encode($_REQUEST));
    			return true;
    		}
    			
    		 
    		//——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
    		 
    		/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    	}
    	else {
    		return false;
    	}
    }
    
    function logpaywrite($word='') {
    	$fp = fopen("temp/paywrong.txt","a");
    	flock($fp, LOCK_EX) ;
    	fwrite($fp,"执行日期：".strftime("%Y%m%d%H%M%S",time())."\n".$word."\n");
    	flock($fp, LOCK_UN);
    	fclose($fp);
    }
    
}

?>