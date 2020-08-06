<?php
/**
 * Created by PhpStorm.
 * User: jiayi
 * Date: 2017/5/2
 * Time: 15:56
 */

namespace ylt\home\controller;
use think\Request;
use think\Db;
class Paynotice extends Base
{

  
    /**
     * 析构流函数
     */
    public function  __construct() {
        parent::__construct();
    }

    /**
     * 微信支付成功异步接口
     */
    public function weixin_notify(){
    	
    	//$sucess_xml='<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
    	//$fail_xml="<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[FAIL]]></return_msg></xml>";
    	
    	$sucess_xml="SUCCESS";
    	$fail_xml="FAIL";
    	
    	$arr = array();
    	$postStr = file_get_contents('php://input');
    	$arr = (array)simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
    	if(!empty($arr)){
    		//微信支付日志记录
    		$word=var_export($arr,true);
    		$fp = fopen(ROOT_PATH ."newapp/temp/paylog.txt","a");
    		flock($fp, LOCK_EX) ;
    		fwrite($fp,"微信支付执行日期：".strftime("%Y%m%d%H%M%S",time())."\n".$word."\n");
    		flock($fp, LOCK_UN);
    		fclose($fp);
    	}
		
			
    	if(!empty($arr)){
    		//返回成功
    		if(!empty($arr['out_trade_no']) && !empty($arr['total_fee'])){
    			
    			$order=Db::name('order')->where(array('order_sn'=>$arr['out_trade_no']))->find();
    			if($order['order_status']=='1'){
    				echo $sucess_xml;
    				exit;
    			}
				
    			$order_sn=trim($arr['out_trade_no']);
    			$money=$arr['total_fee']/100;
    			//订单查询
    			$order = Db::name('order')->where("order_sn", $order_sn)->find();
				
								
				$pos = strpos($order_sn,'us');
				if($pos === false){
					
				}else{
					$order = array();
					$order['order_amount']=$money;
					$order['order_status']='0';
					$order['is_parent']='0';
				}
			
    			if($order['order_status']=='0' && $money==$order['order_amount'] && !empty($order)){
    		
    				
    				$updata['pay_code']='weixin';
    				$updata['pay_name']='微信支付';
    				$updata['transaction_id']=$arr['transaction_id'];
    				$flag=Db::name('order')->where("order_sn='".$arr['out_trade_no']."'")->update($updata);
					
					
    				//修改子订单的状态
    				if($order['is_parent']=='1'){
						DB::name('order')->where("parent_id={$order['order_id']}")->update($updata);
    					$row = Db::name('order')->where("parent_id={$order['order_id']}")->select();
						foreach ($row AS $key => $value) {
							$row[$key]['exp'] = update_pay_status($value['order_sn'],$updata);
						}
						
    				}else{
						
						$row = update_pay_status($arr['out_trade_no'],$updata);
					}
    				if($flag || $row){
    					echo $sucess_xml;
    					exit;
    				}else{
    					echo  $fail_xml;
    					exit;
    				}
    			}else{
    				echo  $fail_xml;
    				exit;
    			}
    		}else{
    			echo  $fail_xml;
    			exit;
    		}
    	}
    	
    }
    
    
    //支付宝异步调用
    public function alipay_notify(){
    	if(!empty($_POST)){
    		$word=var_export($_REQUEST,true);
    		$fp = fopen(ROOT_PATH ."newapp/temp/paylog.txt","a");
    		flock($fp, LOCK_EX) ;
    		fwrite($fp,"执行日期：".strftime("%Y%m%d%H%M%S",time())."\n".$word."\n");
    		flock($fp, LOCK_UN);
    		fclose($fp);
    	}
    	$sign_type=$_POST['sign_type'];
    	$sign=$_POST['sign'];
    	$trade_no=$_POST['trade_no'];
    	$out_trade_no=$_POST['out_trade_no'];
    	$total_amount=$_POST['total_amount'];
    	$trade_status=$_POST['trade_status'];
		
    	
    	if($trade_status=="TRADE_SUCCESS" && !empty($out_trade_no) && !empty($total_amount) && !empty($sign_type) && !empty($sign) && !empty($trade_no)){
    		//查询订单
    		$order=Db::name('order')->where(array('order_sn'=>$out_trade_no))->find();
    		if($order['order_status']=='1'){
    			echo "success";
    			exit;
    		}
			
			$pos = strpos($out_trade_no,'us');
			if($pos === false){
				
			}else{
				$order = array();
				$order['order_amount']=$total_amount;
				$order['order_status']='0';
				$order['is_parent']='0';
			}
    		if(!empty($order) && $order['order_amount']==$total_amount && $order['order_status']=='0'){
    			//修改订单状态
    			
    			$updata['pay_code']='alipay';
    			$updata['pay_name']='app支付宝';
    			
				$updata['transaction_id'] = $_POST['trade_no'];
    			$flag=Db::name('order')->where("order_sn='".$out_trade_no."'")->update($updata);
				
				
    			//修改子订单的状态
    			if($order['is_parent']=='1'){
					Db::name('order')->where("parent_id={$order['order_id']}")->update($updata);
					
    				$row = Db::name('order')->where("parent_id={$order['order_id']}")->select();
					
					foreach ($row AS $key => $value) {
							$row[$key]['exp'] = update_pay_status($value['order_sn'],$updata);
						}
						
    			}else{
					$row = update_pay_status($out_trade_no,$updata);
				}
    			
    			if($flag || $row){
    				echo "success";
    				exit;
    			}else{
    				echo "failure";
    				exit;
    			}
    		}
    		echo "failure";
    		exit;
    		
    	}else{
    		echo "failure";
    		exit;
    	}
    	
    }
   
}