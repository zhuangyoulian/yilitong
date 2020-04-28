<?php

use think\Model; 
use think\Request;
use think\Db;
use think\Url;

/**
 * 支付 逻辑定义
 * Class AlipayPayment
 * @package Home\Payment
 */

class alipay extends Model
{    
    public $tableName = 'plugin'; // 插件表        
    public $alipay_config = array();// 支付宝支付配置参数
    
    /**
     * 析构流函数
     */
    public function  __construct() {           
        parent::__construct();     
        unset($_GET['pay_code']);   // 删除掉 以免被进入签名
        unset($_REQUEST['pay_code']);// 删除掉 以免被进入签名
        
        $paymentPlugin = Db::name('Plugin')->where("code='alipay' and  type = 'payment' ")->find(); // 找到支付插件的配置
        $config_value = unserialize($paymentPlugin['config_value']); // 配置反序列化        
        $this->alipay_config['alipay_pay_method']= $config_value['alipay_pay_method']; // 1 使用担保交易接口  2 使用即时到帐交易接口s
        $this->alipay_config['partner']       = $config_value['alipay_partner'];//合作身份者id，以2088开头的16位纯数字
        $this->alipay_config['seller_email']  = $config_value['alipay_account'];//收款支付宝账号，一般情况下收款账号就是签约账号
        $this->alipay_config['key']	      = $config_value['alipay_key'];//安全检验码，以数字和字母组成的32位字符
        $this->alipay_config['sign_type']     = strtoupper('MD5');//签名方式 不需修改
        $this->alipay_config['input_charset'] = strtolower('utf-8');//字符编码格式 目前支持 gbk 或 utf-8
        $this->alipay_config['cacert']        = getcwd().'\\cacert.pem'; //ca证书路径地址，用于curl中ssl校验 //请保证cacert.pem文件在当前文件夹目录中
        $this->alipay_config['transport']     = 'http';//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
        
    }    
    /**
     * 生成支付代码
     * @param   array   $order      订单信息
     * @param   array   $config_value    支付方式信息
     */
    function get_code($order, $config_value)
    {         
             // 接口类型
            $service = array(             
                 1 => 'create_partner_trade_by_buyer', //使用担保交易接口
                 2 => 'create_direct_pay_by_user', //使用即时到帐交易接口
                 );
            //构造要请求的参数数组，无需改动
            $parameter = array(
                        "service" => $service[$this->alipay_config['alipay_pay_method']],   // 1 使用担保交易接口  2 使用即时到帐交易接口 
                        "partner" => trim($this->alipay_config['partner']),
                        "seller_email" => trim($this->alipay_config['seller_email']),
                        "payment_type"	=> 1, // 默认值为：1（商品购买）。
                        "notify_url"	=> SITE_URL.Url::build('Payment/notifyUrl',array('pay_code'=>'alipay')) , //服务器异步通知页面路径 //必填，不能修改
                        "return_url"	=> SITE_URL.Url::build('Payment/returnUrl',array('pay_code'=>'alipay')),  //页面跳转同步通知页面路径
                        "out_trade_no"	=> $order['order_sn'], //商户订单号                        
                        "subject"	=> '一礼通商城订单', //订单名称
                        "total_fee"	=> $order['order_amount'], //付款金额
                        "_input_charset"=> trim(strtolower($this->alipay_config['input_charset'])) //字符编码格式 目前支持 gbk 或 utf-8
                    );
            //  如果是支付宝网银支付    
            if(!empty($config_value['bank_code']))
            {            
                $parameter["paymethod"] = 'bankPay'; // 若要使用纯网关，取值必须是bankPay（网银支付）。如果不设置，默认为directPay（余额支付）。
                $parameter["defaultbank"] = $config_value['bank_code'];
                $parameter["service"] = 'create_direct_pay_by_user';
            }        
            //建立请求
            require_once("lib/alipay_submit.class.php");            
            $alipaySubmit = new AlipaySubmit($this->alipay_config);
            $html_text = $alipaySubmit->buildRequestForm($parameter,"get", "确认");
            return $html_text;         
    }
    
    /**
     * 服务器点对点响应操作给支付接口方调用
     * 
     */
    function response()
    {                
        require_once("lib/alipay_notify.class.php");  // 请求返回
        //计算得出通知验证结果
        $alipayNotify = new AlipayNotify($this->alipay_config); // 使用支付宝原生自带的类 和方法 这里只是引用了一下 而已
        $verify_result = $alipayNotify->verifyNotify();        
            if($verify_result) //验证成功
            {
                    $order_sn = $out_trade_no = $_POST['out_trade_no']; //商户订单号
                    $_POST['transaction_id']=  $_POST['trade_no'];
                    $trade_no = $_POST['trade_no']; //支付宝交易号                   
                    $trade_status = $_POST['trade_status']; //交易状态
             		$total_amount=$_POST['total_amount']; //订单金额
              		if(!empty($_POST)){
                    	$word=var_export($_POST,true);
              			$fp = fopen("home_paylog.txt","a");
              			flock($fp, LOCK_EX) ;
    					fwrite($fp,"执行日期：".strftime("%Y%m%d%H%M%S",time())."\n".$word."\n");
    					flock($fp, LOCK_UN);
    					fclose($fp);
                    }
              		 //查询订单
                     $order=Db::name('order')->where(array('order_sn'=>$order_sn))->find();
                     if(!empty($order) && $order['order_amount']==$total_amount && $order['order_status']=='0'){
                         $updata['pay_code']='alipay';
                         $updata['pay_name']='pc支付宝';
                         $updata['transaction_id'] = $_POST['trade_no'];
                         $flag=Db::name('order')->where("order_sn='".$out_trade_no."'")->update($updata);
                     }	
                            
                    // 支付宝解释: 交易成功且结束，即不可再做任何操作。
                    if($_POST['trade_status'] == 'TRADE_FINISHED') 
                    {   
                      
                      	if($order['is_parent']=='1'){//判断是不是父单
                            Db::name('order')->where("parent_id={$order['order_id']}")->update($updata);

                            $row = Db::name('order')->where("parent_id={$order['order_id']}")->select();

                            foreach ($row AS $key => $value) {
                                update_pay_status($value['order_sn'],$_POST); // 修改订单支付状态
                                //$row[$key]['exp'] = update_pay_status($value['order_sn'],$updata);
                            }

                        }else{
                            update_pay_status($order_sn,$_POST); // 修改订单支付状态
                        }
                      
                      
                          //update_pay_status($order_sn,$_POST); // 修改订单支付状态
                    }
                    //支付宝解释: 交易成功，且可对该交易做操作，如：多级分润、退款等。
                    elseif ($_POST['trade_status'] == 'TRADE_SUCCESS') 
                    { 
                      
                      if($order['is_parent']=='1'){//判断是不是父单
                            Db::name('order')->where("parent_id={$order['order_id']}")->update($updata);

                            $row = Db::name('order')->where("parent_id={$order['order_id']}")->select();

                            foreach ($row AS $key => $value) {
                                update_pay_status($$value['order_sn'],$_POST); // 修改订单支付状态
                                //$row[$key]['exp'] = update_pay_status($value['order_sn'],$updata);
                            }

                        }else{
                            update_pay_status($order_sn,$_POST); // 修改订单支付状态
                        }
                            //update_pay_status($order_sn,$_POST); // 修改订单支付状态
                    }
                    echo "success"; // 告诉支付宝处理成功
            }
            else 
            {                
                echo "fail"; //验证失败                                
            }
    }
    
    /**
     * 页面跳转响应操作给支付接口方调用
     */
    function respond2()
    {
        require_once("lib/alipay_notify.class.php");  // 请求返回
        //计算得出通知验证结果
        $alipayNotify = new AlipayNotify($this->alipay_config);
        $verify_result = $alipayNotify->verifyReturn();
        
            if($verify_result) //验证成功
            {
              $order_sn = $out_trade_no = $_GET['out_trade_no']; //商户订单号
              $trade_no = $_GET['trade_no']; //支付宝交易号                   
              $trade_status = $_GET['trade_status']; //交易状态
              $total_amount=$_GET['total_fee']; //订单金额
              if(!empty($_GET)){
                  $word=var_export($_GET,true);
                  $fp = fopen("home_paylog.txt","a");
                  flock($fp, LOCK_EX) ;
                  fwrite($fp,"执行日期：".strftime("%Y%m%d%H%M%S",time())."\n".$word."\n");
                  flock($fp, LOCK_UN);
                  fclose($fp);
              }

              //查询订单
               $order=Db::name('order')->where(array('order_sn'=>$order_sn))->find();

              if(!empty($order) && $order['order_amount']==$total_amount && $order['order_status']=='0'){
                   $updata['pay_code']='alipay';
                   $updata['pay_name']='pc支付宝';
                   $updata['transaction_id'] = $_GET['trade_no'];
                   $flag=Db::name('order')->where("order_sn='".$out_trade_no."'")->update($updata);
              }  
              if($_GET['trade_status'] == 'TRADE_FINISHED' || $_GET['trade_status'] == 'TRADE_SUCCESS') 
              {  
                 if($order['is_parent']=='1'){//判断是不是父单
                      Db::name('order')->where("parent_id={$order['order_id']}")->update($updata);

                      $row = Db::name('order')->where("parent_id={$order['order_id']}")->select();

                      foreach ($row AS $key => $value) {
                          update_pay_status($value['order_sn'],$_POST); // 修改订单支付状态
                      }

                  }else{
                      update_pay_status($order_sn,$_POST); // 修改订单支付状态
                  }                         
                 return array('status'=>1,'order_sn'=>$order_sn);//跳转至成功页面
              }
              else {                        
                 return array('status'=>0,'order_sn'=>$order_sn); //跳转至失败页面
              }
            }
            else 
            {                     
                return array('status'=>0,'order_sn'=>$_GET['out_trade_no']);//跳转至失败页面
            }
    }
    
}