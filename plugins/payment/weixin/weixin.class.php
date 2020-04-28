<?php
use think\Model;
use think\Db;
use think\Url;
/**
 * 支付 逻辑定义
 * Class 
 * @package Home\Payment
 */

class weixin extends Model
{    
    public $tableName = 'plugin'; // 插件表        
    public $alipay_config = array();// 支付宝支付配置参数
    
    /**
     * 析构流函数
     */
    public function  __construct() {   
        parent::__construct();
                
        require_once("lib/WxPay.Api.php"); // 微信扫码支付demo 中的文件         
        require_once("example/WxPay.NativePay.php");
        require_once("example/WxPay.JsApiPay.php");
		
		$paymentPlugin = Db::name('Plugin')->where("code='weixin' and  type = 'payment' ")->find(); // 找到微信支付插件的配置 
        $config_value = unserialize($paymentPlugin['config_value']); // 配置反序列化        
        WxPayConfig::$appid = $config_value['appid']; // * APPID：绑定支付的APPID（必须配置，开户邮件中可查看）
        WxPayConfig::$mchid = $config_value['mchid']; // * MCHID：商户号（必须配置，开户邮件中可查看）
        WxPayConfig::$key = $config_value['key']; // KEY：商户支付密钥，参考开户邮件设置（必须配置，登录商户平台自行设置）
        WxPayConfig::$appsecret = $config_value['appsecret']; // 公众帐号secert（仅JSAPI支付的时候需要配置)，                                      
    }    
    /**
     * 生成支付代码
     * @param   array   $order      订单信息
     * @param   array   $config_value    支付方式信息
     */
    function get_code($order, $config_value)
    {    
			$notify = new NativePay();
            $notify_url = SITE_URL.'/index.php/Home/Payment/notifyUrl/pay_code/weixin'; // 接收微信支付异步通知回调地址，通知url必须为直接可访问的url，不能携带参数。
            $input = new WxPayUnifiedOrder();
			$input->SetAttach("weixin"); // 附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据
            $input->SetBody("一礼通商城订单"); // 商品描述
            $input->SetOut_trade_no($order['order_sn']); // 商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号
            $input->SetTotal_fee($order['order_amount']*100); // 订单总金额，单位为分，详见支付金额
            $input->SetNotify_url($notify_url); // 接收微信支付异步通知回调地址，通知url必须为直接可访问的url，不能携带参数。
            $input->SetTrade_type("NATIVE"); // 交易类型   取值如下：JSAPI，NATIVE，APP，详细说明见参数规定    NATIVE--原生扫码支付
            $input->SetProduct_id("ylt"); // 商品ID trade_type=NATIVE，此参数必传。此id为二维码中包含的商品ID，商户自行定义。
            $result = $notify->GetPayUrl($input); // 获取生成二维码的地址
			
            $url2 = $result["code_url"];
            return '<img alt="扫码支付" src="/index.php?m=Home&c=Index&a=qr_code&data='.urlencode($url2).'" style="width:200px;height:200px;"/>';
    }    
    /**
     * 服务器点对点响应操作给支付接口方调用
     * 
     */
    function response()
    {                        
        require_once("example/notify.php");  
        $notify = new PayNotifyCallBack();
        $notify->Handle(false);       
    }
    
    /**
     * 页面跳转响应操作给支付接口方调用
     */
    function respond2()
    {
        // 微信扫码支付这里没有页面返回
    }

    function getJSAPI($order){
		
    	if(stripos($order['order_sn'],'recharge') !== false){
    		$go_url = Url::build('Mobile/User/points',array('type'=>'recharge'));
    		$back_url = Url::build('Mobile/User/recharge',array('order_id'=>$order['order_id']));
    	}else{
    		$go_url = Url::build('Mobile/User/order_detail',array('id'=>$order['order_id']));
    		$back_url = Url::build('Mobile/Cart/payment',array('order_id'=>$order['order_id']));
    	}
        //①、获取用户openid
        $tools = new JsApiPay();
        //$openId = $tools->GetOpenid();
		
        $openId = $_SESSION['openid'];
        //②、统一下单
        $input = new WxPayUnifiedOrder();
        $input->SetBody("支付订单：".$order['order_sn']);
        $input->SetAttach("weixin");
        $input->SetOut_trade_no($order['order_sn'].time());
        $input->SetTotal_fee($order['order_amount']*100);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag("一礼通商城订单");
        $input->SetNotify_url('http://yilitong.com/index.php/Home/Payment/notifyUrl/pay_code/weixin');
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openId);
        $order2 = WxPayApi::unifiedOrder($input);
       //  dump($input);exit;
        $jsApiParameters = $tools->GetJsApiParameters($order2);
	
        $html = <<<EOF
	<script type="text/javascript">
	//调用微信JS api 支付
	function jsApiCall()
	{
		WeixinJSBridge.invoke(
			'getBrandWCPayRequest',$jsApiParameters,
			function(res){
				//WeixinJSBridge.log(res.err_msg);
				 if(res.err_msg == "get_brand_wcpay_request:ok") {
				    location.href='$go_url';
				 }else{
				 	alert(res.err_code+res.err_desc+res.err_msg);
				    location.href='$back_url';
				 }
			}
		);
	}

	function callpay()
	{
		if (typeof WeixinJSBridge == "undefined"){
		    if( document.addEventListener ){
		        document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
		    }else if (document.attachEvent){
		        document.attachEvent('WeixinJSBridgeReady', jsApiCall);
		        document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
		    }
		}else{
		    jsApiCall();
		}
	}
	callpay();
	</script>
EOF;
        
    return $html;

    }
    
    //查询微信支付订单
    function getOrderType($order_sn='',$transaction_id=''){
    	//商品订单号查询
    	if(!empty($order_sn)){
    		$input = new WxPayOrderQuery();
    		$input->SetOut_trade_no($order_sn);
    		return WxPayApi::orderQuery($input);
    	}//微信订单号
    	else if(!empty($transaction_id)){
    		$input = new WxPayOrderQuery();
    		$input->SetTransaction_id($transaction_id);
    		return WxPayApi::orderQuery($input);
    	}
    }
    //获取APP生成预订订单
    function pay_order($order){
    	
    	$input= new WxPayUnifiedOrder();
        $input->SetAttach("weixin");
        $input->SetSign();
        $input->SetBody("APP-一礼通：".$order['order_sn']);
        $input->SetTime_start(date("YmdHis"));//订单生成时间
        $input->SetTime_expire(date("YmdHis", time() + 600));//订单失效时间
        $input->SetOut_trade_no($order['order_sn']);
        $input->SetTotal_fee($order['total_amount']*100);
        $input->SetTrade_type("APP");
        $input->SetNotify_url(SITE_URL.'/index.php/Home/Paynotice/weixin_notify');
        $rs = WxPayApi::unifiedOrder($input);
    	return $rs;
    	
    }
	
	
		/**
	 * 微信支付退款
	 * out_trade_no： 订单号 
	 * total_fee 退款金额
	 */
	function refund_for_weixin($out_trade_no, $total_fee){
		$rt = false;
		
		try {
			
			$total_fee =  $total_fee * 100;
			$input = new \WxPayRefund();
			$input->SetOut_trade_no($out_trade_no);
			$input->SetTotal_fee($total_fee);
			$input->SetRefund_fee($total_fee);
			$input->SetOut_refund_no($out_trade_no);
			$input->SetOp_user_id('1376794402');
			
			//$input->SetRefund_account('REFUND_SOURCE_RECHARGE_FUNDS');
			$rt = WxPayApi::refund($input);
			if (empty($rt['err_code'])) {
				$rt = true;
			}else{
				//file_put_contents('/logerror.txt',var_export($rt,true));
			}
		} catch (\Exception $ex) {
			echo $ex;exit;
			//file_put_contents('/logerror.txt',var_export($rt,true));
		}

	
		return $rt;
	}
    
   
    

}