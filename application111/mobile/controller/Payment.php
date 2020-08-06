<?php
namespace ylt\mobile\controller;
use ylt\home\model\CartLogic;
use think\Request;
use think\Db;
use think\Url;
class Payment extends MobileBase {
    
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

            $this->pay_code = I('get.pay_code');
            // $this->pay_code = "weixinJSAPI";
            unset($_GET['pay_code']); // 用完之后删除, 以免进入签名判断里面去 导致错误
        }                        
        //获取通知的数据
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];    
        if(empty($this->pay_code))
            exit('pay_code 不能为空');        
        // 导入具体的支付类文件                
        include_once  "plugins/payment/{$this->pay_code}/{$this->pay_code}.class.php"; // www\plugins\payment\alipay\alipayPayment.class.php
        $code = '\\'.$this->pay_code; // \alipay
        $this->payment = new $code();
    }
   
    /**
     *  提交支付方式
     */
    public function getCode(){   
        header("Content-type:text/html;charset=utf-8");            
        $order_id = I('order_id/d'); // 订单id
        // 修改订单的支付方式
        $payment_arr = Db::name('Plugin')->where("`type` = 'payment'")->column("code,name");
        Db::name('order')->where("order_id", $order_id)->update(array('pay_code'=>$this->pay_code,'pay_name'=>$payment_arr[$this->pay_code]));
        $order = Db::name('order')->where("order_id", $order_id)->find();
        if($order['pay_status'] == 1){
        	$this->error('此订单，已完成支付!');
        }
        //订单支付提交
        $pay_radio = $_REQUEST['pay_radio'];
        $config_value = parse_url_param($pay_radio); // 类似于 pay_code=alipay&bank_code=CCB-DEBIT 参数
        // dump(strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger'));exit;
        //微信JS支付
        
        if($this->pay_code == 'weixinJSAPI'  && strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
           $code_str = $this->payment->getJSAPI($order,$config_value);
           exit($code_str);
        }else{
       	    $code_str = $this->payment->get_code($order,$config_value);
        }
        $this->assign('code_str', $code_str); 
        $this->assign('order_id', $order_id); 
        return $this->fetch('payment');  // 分跳转 和不 跳转
    }



        // 服务器点对点 // http://yilitong.com/index.php/Home/Payment/notifyUrl
        public function notifyUrl(){            
            $this->payment->response();            
            exit();
        }

        // 页面跳转 // http://yilitong.com/index.php/Home/Payment/returnUrl
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
            if ($order['is_share'] > 1) {
                $share_the_bill = Db::name('share_the_bill')->where('id',$order['is_share'])->find();
                $this->assign('share_the_bill', $share_the_bill);
            }
            $this->assign('order', $order);
            if($result['status'] == 1){
                
                return $this->fetch('success');
            }else{
                return $this->fetch('error');
            }
        }




    
}
