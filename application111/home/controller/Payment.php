<?php
/**
 * Created by PhpStorm.
 * User: jiayi
 * Date: 2017/4/24
 * Time: 15:56
 */

namespace ylt\home\controller;
use think\Request;
use think\Db;
use think\Url;
class Payment extends Base
{

       public $payment; //  具体的支付类
    public $pay_code; //  具体的支付code


    /**
     * 析构流函数
     */
    public function  __construct() {
        parent::__construct();

        // 订单支付提交
        $pay_radio = $_REQUEST['pay_radio'];
        if(!empty($pay_radio))
        {
            $pay_radio = parse_url_param($pay_radio);
            $this->pay_code = $pay_radio['pay_code']; // 支付 code
        }
        else // 第三方 支付商返回
        {
            //file_put_contents('./a.html',$_GET,FILE_APPEND);
            $this->pay_code = I('get.pay_code');
            unset($_GET['pay_code']); // 用完之后删除, 以免进入签名判断里面去 导致错误
        }
        //获取通知的数据
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];

        if(empty($this->pay_code))
            $this->error("参数错误");

        // 导入具体的支付类文件
        include_once  "plugins/payment/{$this->pay_code}/{$this->pay_code}.class.php"; 
        // E:\phpStudy\WWW\tp5\plugins\payment\alipay\alipay.class.php
        $code = '\\'.$this->pay_code; // \alipay
        $this->payment = new $code();
    }

    /**
     *  提交支付方式
     */
    public function getCode(){
        //config('TOKEN_ON',false); // 关闭 TOKEN_ON
        header("Content-type:text/html;charset=utf-8");
        $order_id = I('order_id/d'); // 订单id
        session('order_id',$order_id); // 最近支付的一笔订单 id
        // 修改订单的支付方式
        $payment_arr = Db::name('Plugin')->where("`type` = 'payment'")->column("code,name");
        Db::name('order')->where("order_id",$order_id)->save(array('pay_code'=>$this->pay_code,'pay_name'=>$payment_arr[$this->pay_code]));

        $order = Db::name('order')->where("order_id", $order_id)->find();
        if($order['pay_status'] == 1){
            $this->error('此订单，已完成支付!');
        }
        //  订单支付提交
        $pay_radio = $_REQUEST['pay_radio'];
        $config_value = parse_url_param($pay_radio); // 类似于 pay_code=alipay&bank_code=CCB-DEBIT 参数

        //微信JS支付
        if($this->pay_code == 'weixin' && $_SESSION['openid'] && strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
            $code_str = $this->payment->getJSAPI($order,$config_value);
            exit($code_str);
        }else{
            $code_str = $this->payment->get_code($order,$config_value);
        }
        $this->assign('code_str', $code_str);
        $this->assign('order_id', $order_id);
        return $this->fetch('payment');  // 分跳转 和不 跳转
    }
    
    /**
     * [returnUrl 微信公众号付款回调]
     * @return [type] [description]
     */
    public function notifyUrl(){
        $testxml = file_get_contents("php://input");

        $jsonxml = json_encode(simplexml_load_string($testxml, 'SimpleXMLElement', LIBXML_NOCDATA));
        $result = json_decode($jsonxml, true);//转成数组，

        if ($result) {
            //如果成功返回了
            $out_trade_no = $result['out_trade_no'];
            if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
                //执行业务逻辑
                $order_sn = substr($result['out_trade_no'],0,-10);
                update_pay_status($order_sn,$result); // 修改订单支付状态
                // 蜂助手第三方接口
                if (substr($order_sn,0,2) == "cz") {
                    $order = Db::name('order')->where("order_sn", $order_sn)->find();
                    //付款成功调用充值第三方接口
                    //第三方充值接口
                    if ($data['pid'] == 7 || $data['pid'] == 8) {
                        $this->topUpAPI_phone($order['order_id'],$order['phone'],$order['encoding']);
                    }else if($data['pid'] == 9){
                        $this->topUpAPIs($order['order_id'],$order['phone'],$order['encoding']);
                    }
                }
            }
            echo "SUCCESS";
        }

        

        $this->payment->response();
        exit();
    }


    
    public function returnUrl(){
        $result = $this->payment->respond2();
        
        if(stripos($result['order_sn'],'recharge') !== false)
        {
            $order = Db::name('recharge')->where("order_sn", $result['order_sn'])->find();
            $this->assign('order', $order);
            if($result['status'] == 1)
                return $this->fetch('recharge_success');
            else
                return $this->fetch('recharge_error');
            exit();
        }

        $order = Db::name('order')->where("order_sn", $result['order_sn'])->find();
        if(empty($order)) // order_sn 找不到 根据 order_id 去找
        {
            $order_id = session('order_id'); // 最近支付的一笔订单 id
            $order = Db::name('order')->where("order_id", $order_id)->find();
        }

        $this->assign('order', $order);
        if($result['status'] == 1)
            return $this->fetch('success');
        else
            return $this->fetch('error');
    }
    
    
    /**
     * by cx
     * app 支付宝微信查询接口
     */
    public function get_orderType(){
        $type=$_REQUEST['pay_code'];
        $order_sn=$_REQUEST['order_sn'];

        if($type=="alipay"){
            //$this->pay_code="alipay";
            //$type=$this->payment->getOrderType($order_sn,$trade_no='');
        }elseif($type=="weixin"){
            $this->pay_code="weixin";
            $rs=$this->payment->getOrderType($order_sn,$trade_no='');
            if($rs['return_code']=="SUCCESS" && $rs['trade_state']=="SUCCESS"){
                exit(json_encode(true));
            }
        }
        exit(json_encode(false));
    }
    
    
    /**
     * 微信支付退款
     * out_trade_no： 订单号 
     * total_fee 退款金额
     */
    function refund_for_weixin_($out_trade_no, $total_fee){
        $rt = false;
        try {
            require_once dirname(__FILE__) . '/wxpayapi/lib/WxPay.Notify.php';
            $input = new \WxPayRefund();
            $total_fee =  $total_fee * 100;
            $input->SetRefund_fee($total_fee);
            $input->SetTotal_fee($total_fee);
            $input->SetOp_user_id('1376794402');
            $input->SetOut_refund_no($out_trade_no);
            $input->SetOut_trade_no($out_trade_no);
            $input->SetRefund_account('REFUND_SOURCE_RECHARGE_FUNDS');
            $rt = \WxPayApi::refund($input);
            if (empty($rt['err_code'])) {
                $rt = true;
            }else{
                file_put_contents('/home/tmp/logerror.txt',var_export($rt,true));
            }
        } catch (\Exception $ex) {
            file_put_contents('/home/tmp/logerror.txt',var_export($rt,true));
        }
        return $rt;
    }
    
        /**
     * 支付宝支付退款
     * out_trade_no： 订单号 
     * total_fee 退款金额
     */

    function refund_for_alipay_($out_trade_no, $money){
        require_once dirname(__FILE__) . '/Alipay/AopClient.php';
        require_once dirname(__FILE__) . '/Alipay/request/AlipayTradeRefundRequest.php';
        require_once dirname(__FILE__) . '/Alipay/SignData.php';
        $aop = new \AopClient ();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = '2088421463111050';
        $aop->rsaPrivateKeyFilePath =dirname(__FILE__) . '/Alipay/cert/rsa_private_key.pem';
        $aop->alipayPublicKey = dirname(__FILE__) . '/Alipay/cert/rsa_public_key.pem';
        $aop->apiVersion = '1.0';
        $aop->postCharset = 'UTF-8';
        $aop->format = 'json';
        $request = new \AlipayTradeRefundRequest();
        $bizContent = json_encode([
            'out_trade_no' => $out_trade_no,
            'refund_amount' => $money,
        ]);
        $request->setBizContent($bizContent);
        try {
            $result = $aop->execute($request);
            $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
            $resultCode = $result->$responseNode->code;
            if (!empty($resultCode) && $resultCode == 10000) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $ex) {
            return false;
        }

    }

    
    /***
     * 查询预订单信息
     * 
     */
    public  function pay_order(){
        $order['order_sn']=$_REQUEST['order_sn'];
        $order['total_amount']=$_REQUEST['total_amount'];
        $this->pay_code="weixin";
        $rs=$this->payment->pay_order($order);
        if($rs['return_code']=='FAIL'){
            exit(json_encode(false));
        }else{
            exit(json_encode($rs));
        }
        
    }



    
    /**
     * [topUpAPI 蜂助手第三方接口 充值入口 影视会员]
     * @param  [type] $order_id [description]
     * @param  [type] $phone    [description]
     * @return [type]           [description]
     */
    public function topUpAPIs($order_id,$phone,$skuCodes){
        // $url            =   'http://test.www.phone580.com:8000/fzs-open-api/buy/api/sendgoods';
        $url            =   'https://orderapi.phone580.com/fzs-open-api/buy/api/sendgoods';
        $appKey         =   'YLTHC1TESTKEY001';
        $appSecret      =   '24f724601a9139b9cb';
        $channelId      =   'YLTHC1';
        $num            =   '1';
        $orderId        =   $order_id;
        $returl         =   'http://www.yilitong.com/Home/Notify/APItype';
        // $returl         =   'tp.cn/Mobile/Refillcard/APItype';
        $shipInfo       =   "{'phoneNum':$phone,'account':'12312'}";
        $skuCode        =   $skuCodes;
        $srvType        =   'recharge';
        $timestamp      =   time()*1000;

        $datas=$appSecret.'appKey='.$appKey.'channelId='.$channelId.'num='.$num.'orderId='.$orderId.'returl='.$returl.'shipInfo='.$shipInfo.'skuCode='.$skuCode.'srvType='.$srvType.'timestamp='.$timestamp.$appSecret;

        $shipInfo=urlencode($shipInfo);
        $data='appKey='.$appKey.'&channelId='.$channelId.'&num='.$num.'&orderId='.$orderId.'&returl='.$returl.'&shipInfo='.$shipInfo.'&skuCode='.$skuCode.'&srvType='.$srvType.'&timestamp='.$timestamp;

        $sign=strtoupper(sha1($datas));
        $parameter = $url.'?'.$data.'&sign='.$sign;
        $hou=array("","","","","");
        $qian=array(" ","　","\t","\n","\r");
        $parameter = str_replace($qian,$hou,$parameter);
        //$parameter = "https://orderapi.phone580.com/fzs-open-api/buy/api/sendgoods";
        $type = $this->get_urls($parameter);
        // print_r($parameter);
        // print_r($parameter);
        // print_r($type);
        // print_r($orderId);die;
        return $type;
    }

    /**
     * [topUpAPI_phone 话费和油卡]
     * @param  [type] $order_id [description]
     * @param  [type] $phone    [description]
     * @return [type]           [description]
     */
    public function topUpAPI_phone($order_id,$phone,$skuCodes){
        // $url            =   'http://test.www.phone580.com:8000/fzs-open-api/buy/api/sendgoods';
        $url            =   'https://orderapi.phone580.com/fzs-open-api/buy/api/sendgoods';
        $appKey         =   'YLTHC2TESTKEY001';
        $appSecret      =   'f4a6424a9bc79cf';
        $channelId      =   'YLTHC2';
        $num            =   '1';
        $orderId        =   $order_id;
        $returl         =   'http://www.yilitong.com/Home/Notify/APItype';
        // $returl         =   'tp.cn/Mobile/Refillcard/APItype';
        $shipInfo       =   "{'phoneNum':$phone,'account':'12312'}";
        $skuCode        =   $skuCodes;
        $srvType        =   'recharge';
        $timestamp      =   time()*1000;

        $datas=$appSecret.'appKey='.$appKey.'channelId='.$channelId.'num='.$num.'orderId='.$orderId.'returl='.$returl.'shipInfo='.$shipInfo.'skuCode='.$skuCode.'srvType='.$srvType.'timestamp='.$timestamp.$appSecret;

        $shipInfo=urlencode($shipInfo);
        $data='appKey='.$appKey.'&channelId='.$channelId.'&num='.$num.'&orderId='.$orderId.'&returl='.$returl.'&shipInfo='.$shipInfo.'&skuCode='.$skuCode.'&srvType='.$srvType.'&timestamp='.$timestamp;

        $sign=strtoupper(sha1($datas));
        $parameter = $url.'?'.$data.'&sign='.$sign;
        $hou=array("","","","","");
        $qian=array(" ","　","\t","\n","\r");
        $parameter = str_replace($qian,$hou,$parameter);
        //$parameter = "https://orderapi.phone580.com/fzs-open-api/buy/api/sendgoods";
        $type = $this->get_urls($parameter);
        // print_r($parameter);
        // print_r($parameter);
        // print_r($type);
        // print_r($orderId);die;
        return $type;
    }

    
  
  
    /**
     *  通过URL获取页面信息
     * @param $url  地址
     * @return mixed  返回页面信息
     */
   public function get_urls($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);  //设置访问的url地址
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//不输出内容
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }



}