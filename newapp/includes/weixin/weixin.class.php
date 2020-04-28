<?php
/**
 * 支付 逻辑定义
 * Class 
 * @package Home\Payment
 */
class weixin {    
  
    /**
     * 析构流函数
     */
    public function  __construct() {   
        require_once("lib/WxPay.Api.php"); // 微信扫码支付demo 中的文件         
        require_once("example/WxPay.NativePay.php");
        require_once("example/WxPay.JsApiPay.php");
    }    
   
    //获取APP生成预订订单
    function pay_order($order){
    	//开放平台配置信息
        WxPayConfig::$appid = "wx06a25f184eb235f1"; // * APPID：绑定支付的APPID（必须配置，开户邮件中可查看）
    	WxPayConfig::$mchid = "1473157102"; // * MCHID：商户号（必须配置，开户邮件中可查看）
    	WxPayConfig::$key = "190b6549eb6615bd40f1a03d4b9f5a6b"; // KEY：商户支付密钥，参考开户邮件设置（必须配置，登录商户平台自行设置）
    	$input= new WxPayUnifiedOrder();
        $input->SetAttach("weixin");
        $input->SetSign();
        $input->SetBody("一礼通：".$order['order_sn']);
        $input->SetTime_start(date("YmdHis"));//订单生成时间
        $input->SetTime_expire(date("YmdHis", time() + 600));//订单失效时间
        $input->SetOut_trade_no($order['order_sn']);
        $input->SetTotal_fee($order['order_amount']*100);
        $input->SetTrade_type("APP");
        $input->SetNotify_url('http://www.yilitong.com/index.php/Home/Paynotice/weixin_notify');
        $rs = WxPayApi::unifiedOrder($input);
    	return $rs;
    }
	
}