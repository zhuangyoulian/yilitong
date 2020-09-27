<?php


namespace ylt\supplier\logic;

use think\Model;
use think\Db;
class OrderLogic extends Model
{
    /**
     * @param array $condition  搜索条件
     * @param string $order   排序方式
     * @param int $start    limit开始行
     * @param int $page_size  获取数量
     */
    public function getOrderList($condition,$order='',$start=0,$page_size=20){
        $res = Db::name('order')->where($condition)->limit("$start,$page_size")->order($order)->select();
        return $res;
    }
    /*
     * 获取订单商品详情
     */
    public function getOrderGoods($order_id){
        $supplier_id = session('supplier_id');
        $sql = "SELECT g.*,o.*,(o.goods_num * o.member_goods_price) AS goods_total FROM __PREFIX__order_goods o ".
            "LEFT JOIN __PREFIX__goods g ON o.goods_id = g.goods_id WHERE o.order_id = $order_id and g.supplier_id = $supplier_id";
        $res = DB::query($sql);
        return $res;
    }

    /*
     * 获取订单信息
     */
    public function getOrderInfo($order_id)
    {
        //  订单总金额查询语句		
        $order = Db::name('order')->where("order_id = $order_id")->find();
        $order['address2'] =  $order['province'] .','. $order['city'] .','. $order['district'];
        $order['address2'] = $order['address2'].$order['address'];		
        return $order;
    }

    /*
     * 根据商品型号获取商品
     */
    public function get_spec_goods($goods_id_arr){
    	if(!is_array($goods_id_arr)) return false;
    		foreach($goods_id_arr as $key => $val)
    		{
    			$arr = array();
    			$goods = Db::name('goods')->where("goods_id = $key")->find();
    			$arr['goods_id'] = $key; // 商品id
    			$arr['goods_name'] = $goods['goods_name'];
    			$arr['goods_sn'] = $goods['goods_sn'];
    			$arr['market_price'] = $goods['market_price'];
    			$arr['goods_price'] = $goods['shop_price'];
    			$arr['cost_price'] = $goods['cost_price'];
    			$arr['member_goods_price'] = $goods['shop_price'];
    			foreach($val as $k => $v)
    			{
    				$arr['goods_num'] = $v['goods_num']; // 购买数量
    				// 如果这商品有规格
    				if($k != 'key')
    				{
    					$arr['spec_key'] = $k;
    					$spec_goods = Db::name('goods_price')->where("goods_id = $key and `key` = '{$k}'")->find();
    					$arr['spec_key_name'] = $spec_goods['key_name'];
    					$arr['member_goods_price'] = $arr['goods_price'] = $spec_goods['price'];
    					$arr['sku'] = $spec_goods['sku']; // 参考 sku  http://www.zhihu.com/question/19841574
    				}
    				$order_goods[] = $arr;
    			}
    		}
    		return $order_goods;	
    }

    /*
     * 订单操作记录
     */
    public function orderActionLog($order_id,$action,$note=''){    	
        $order = Db::name('order')->where(array('order_id'=>$order_id))->find();
        $data['order_id'] = $order_id;
        $data['action_user'] = session('admin_id');
        $data['action_note'] = $note;
        $data['order_status'] = $order['order_status'];
        $data['pay_status'] = $order['pay_status'];
        $data['shipping_status'] = $order['shipping_status'];
        $data['log_time'] = time();
        $data['status_desc'] = $action;        
        return Db::name('order_action')->add($data);//订单操作记录
    }

    /*
     * 获取订单商品总价格
     */
    public function getGoodsAmount($order_id){
        $sql = "SELECT SUM(goods_num * goods_price) AS goods_amount FROM __PREFIX__order_goods WHERE order_id = {$order_id}";
        $res = DB::query($sql);
        return $res[0]['goods_amount'];
    }

    /**
     * 得到发货单流水号
     */
    public function get_delivery_sn()
    {
        /* 选择一个随机的方案 */send_http_status('310');
		mt_srand((double) microtime() * 1000000);
        return date('YmdHi') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    /*
     * 获取当前可操作的按钮
     */
    public function getOrderButton($order){
        /*
         *  操作按钮汇总 ：付款、设为未付款、确认、取消确认、无效、去发货、确认收货、申请退货
         * 
         */
    	$os = $order['order_status'];//订单状态
    	$ss = $order['shipping_status'];//发货状态
    	$ps = $order['pay_status'];//支付状态
        $btn = array();
        if($order['pay_code'] == 'cod') {
        	if($os == 0 && $ss == 0){
        		$btn['confirm'] = '确认';
        	}elseif($os == 1 && $ss == 0 ){
        		$btn['delivery'] = '去发货';
        		$btn['cancel'] = '取消确认';
        	}elseif($ss == 1 && $os == 1 && $ps == 0){
        		// $btn['pay'] = '付款';
        	}elseif($ps == 1 && $ss == 1 && $os == 1){
        		// $btn['pay_cancel'] = '设为未付款';
        	}
        }else{
        	if($ps == 0 && $os == 0 || $ps == 2){
        	//	$btn['pay'] = '付款';
        	}elseif($os == 0 && $ps == 1){
        		// $btn['pay_cancel'] = '设为未付款';
        		$btn['confirm'] = '确认';
        	}elseif($os == 1 && $ps == 1 && $ss==0){
        		$btn['cancel'] = '取消确认';
        		$btn['delivery'] = '去发货';
        	}
        } 
               
        if($ss == 1 && $os == 1 && $ps == 1){
        	$btn['delivery_confirm'] = '确认收货';
        	$btn['refund'] = '申请退货';
        }elseif($os == 2 || $os == 4){
        	$btn['refund'] = '申请退货';
        }elseif($os == 3 || $os == 5){
        	//$btn['remove'] = '移除';
        }
        if($os != 5){
        	$btn['invalid'] = '无效';
        }
        return $btn;
    }

    
    public function orderProcessHandle($order_id,$act,$ext=array()){
    	$updata = array();
    	switch ($act){
    		case 'pay': //付款
               	$order_sn = Db::name('order')->where("order_id = $order_id")->getField("order_sn");
                update_pay_status($order_sn,$ext); // 调用确认收货按钮
    			return true;    			
    		case 'pay_cancel': //取消付款
    			$updata['pay_status'] = 0;
    			$this->order_pay_cancel($order_id);
    			return true;
    		case 'confirm': //确认订单
    			$updata['order_status'] = 1;
    			break;
    		case 'cancel': //取消确认
    			$updata['order_status'] = 0;
    			break;
    		case 'invalid': //作废订单
    			$updata['order_status'] = 5;
    			break;
    		case 'remove': //移除订单
    			$this->delOrder($order_id);
    			break;
    		case 'delivery_confirm'://确认收货
    			confirm_order($order_id); // 调用确认收货按钮
    			return true;
    		default:
    			return true;
    	}
    	return Db::name('order')->where("order_id=$order_id")->update($updata);//改变订单状态
    }
	
	
	    /*
    * 获取当前可操作的按钮
    * 同意、不同意
    */
    public function getbackButton($back){

        $btn = array();
        if($back['status'] == '0') {
            $btn['confirm'] = '确认';
            $btn['refuse'] = '拒绝';

        }
        if($back['type'] == 1){ //同意退款 或 对方已发货
            if($back['status'] == '1' || $back['status'] == '2') {
                $btn['received'] = '收到商品';
                $btn['refund'] = '直接退款';

            }
            if($back['status'] == '3') {
                $btn['refund'] = '退款';

            }
        }else{
            if($back['status'] == '1' || $back['status'] == '2') {
                $btn['return'] = '收到商品';
            }

        }


        return $btn;
    }
    
    
    //管理员取消付款
    function order_pay_cancel($order_id)
    {
    	//如果这笔订单已经取消付款过了
    	$count = Db::name('order')->where("order_id = $order_id and pay_status = 1")->count();   // 看看有没已经处理过这笔订单  支付宝返回不重复处理操作
    	if($count == 0) return false;
    	// 找出对应的订单
    	$order = Db::name('order')->where("order_id = $order_id")->find();
    	// 增加对应商品的库存
        $orderGoodsArr = Db::name('OrderGoods')->where("order_id = $order_id")->select();
    	foreach($orderGoodsArr as $key => $val)
    	{
    		if(!empty($val['spec_key']))// 有选择规格的商品
    		{   // 先到规格表里面增加数量 再重新刷新一个 这件商品的总数量
    			Db::name('GoodsPrice')->where("goods_id = {$val['goods_id']} and `key` = '{$val['spec_key']}'")->setInc('store_count',$val['goods_num']);
    			refresh_stock($val['goods_id']);
    		}else{
    			Db::name('Goods')->where("goods_id = {$val['goods_id']}")->setInc('store_count',$val['goods_num']); // 增加商品总数量
    		}
    		Db::name('Goods')->where("goods_id = {$val['goods_id']}")->setDec('sales_sum',$val['goods_num']); // 减少商品销售量
    		//更新活动商品购买量
    		if($val['prom_type']==1 || $val['prom_type']==2){
    			$prom = get_goods_promotion($val['goods_id']);
    			if($prom['is_end']==0){
    				$tb = $val['prom_type']==1 ? 'panic_buying' : 'group_buy';
    				Db::name($tb)->where("id=".$val['prom_id'])->setDec('buy_num',$val['goods_num']);
    				Db::name($tb)->where("id=".$val['prom_id'])->setDec('order_num');
    			}
    		}
    	}
    	// 根据order表查看消费记录 给他会员等级升级 修改他的折扣 和 总金额
    	Db::name('order')->where("order_id=$order_id")->update(array('pay_status'=>0));

    	// 记录订单操作日志
    	logOrder($order['order_id'],'订单取消付款','付款取消',$order['user_id']);
    }
    
    /**
     *	处理发货单
     * @param array $data  查询数量
     */
    public function deliveryHandle($data){
		$order = $this->getOrderInfo($data['order_id']);
		$orderGoods = $this->getOrderGoods($data['order_id']);
		$selectgoods = $data['goods'];
		$data['invoice_no'] = trim($data['invoice_no']);
		
	switch ($data['shipping_name']){
        case -1:
            $data['shipping_name'] = $shipping_code = $this->ShippingName($data['invoice_no']);
            break;
        case 1:
            $data['shipping_name'] = '门店自提';
            break;
        case 2:
            $data['shipping_name'] = '送货上门';
            break;
        default:
            $shipping_code = Db::name('plugin')->where(array('type'=>'shipping','name'=>$data['shipping_name']))->value('code');
    }
		
		$data['order_sn'] = $order['order_sn'];
		$data['delivery_sn'] = $this->get_delivery_sn();
		$data['zipcode'] = $order['zipcode'];
		$data['user_id'] = $order['user_id'];
		$data['admin_id'] = session('admin_id');
		$data['consignee'] = $order['consignee'];
		$data['mobile'] = $order['mobile'];
		$data['country'] = $order['country'];
		$data['province'] = $order['province'];
		$data['city'] = $order['city'];
		$data['district'] = $order['district'];
		$data['address'] = $order['address'];
		$data['shipping_code'] = $order['shipping_code'];
		$data['shipping_price'] = $order['shipping_price'];
		$data['supplier_id'] = $order['supplier_id'];
		$data['create_time'] = time();
		$did = Db::name('shipping_order')->add($data);
		$is_delivery = 0;
		foreach ($orderGoods as $k=>$v){
			if($v['is_send'] == 1){
				$is_delivery++;
			}			
			if($v['is_send'] == 0 && in_array($v['rec_id'],$selectgoods)){
				$res['is_send'] = 1;
				$res['delivery_id'] = $did;
				$r = Db::name('order_goods')->where("rec_id=".$v['rec_id'])->update($res);//改变订单商品发货状态
				$is_delivery++;
			}
		}
		

		$updata['shipping_time'] = time();
        $updata['shipping_name'] = $data['shipping_name'];
        $updata['shipping_code'] = $data['invoice_no'];
		
		$updata['shipping_status'] = 1;
		
		Db::name('order')->where("order_id=".$data['order_id'])->update($updata);//改变订单状态
		
		if(Db::name('back_order')->where(array('order_id'=>$data['order_id'],'status'=>4))->find())
			Db::name('back_order')->where(array('order_id'=>$data['order_id'],'status'=>4))->update(array('status'=>5)); //退换货发货

        //$code = Db::name('plugin')->where(array('type'=>'shipping','name'=>$data['shipping_name']))->value('code');
        queryExpress($shipping_code,$data['invoice_no']);

		$s = $this->orderActionLog($order['order_id'],'delivery',$data['note']);//操作日志

		return $s;
    }
	
	/**
	 * 获取快递CODE
	 * $code
	 */
	 private function ShippingName($code){
		 
		 $express_key =  Db::name('config')->where(array('name'=>'express_key','inc_type'=>'shipping'))->value('value');
		 
		 $url='http://www.kuaidi100.com/autonumber/auto?num='.$code.'&key='.$express_key.'';
 
		$shipping_code = file_get_contents($url); 

		$code = json_decode($shipping_code,true);
		
		return $code [0]['comCode'];
	 }
	 
	 
	  /**
     * 微信退款
     * @param $out_refund_no 商户内部唯一退款单号
     * @param $out_trade_no 退款订单号
     * @param $refund_fee 退款金额
     * @param $total_fee 订单总金额
     */
    public function refund_for_weixin($out_refund_no,$out_trade_no,$refund_fee,$total_fee,$pay_code)
    {
        $refund_fee = $refund_fee * 100; //以分为单位
        $total_fee = $total_fee * 100;
        if($pay_code == 'weixin')
			$paymentPlugin = Db::name('Plugin')->where("code='weixin' and  type = 'payment' ")->find(); // 找到微信支付插件的配置
		else
			$paymentPlugin = Db::name('Plugin')->where("code='weixinJSAPI' and  type = 'payment' ")->find(); // 找到微信支付插件的配置
		
        $config_value = unserialize($paymentPlugin['config_value']); // 配置反序列化
        $appid = $config_value['appid']; // * APPID：绑定支付的APPID
        $mchid = $config_value['mchid']; // * MCHID：商户号
        $key = $config_value['key']; // KEY：商户支付密钥

        // 微信退款签名
        $ref= strtoupper(md5("appid=$appid&mch_id=$mchid&nonce_str=6&op_user_id=$mchid"
            . "&out_refund_no=$out_refund_no&out_trade_no=$out_trade_no&refund_fee=$refund_fee&total_fee=$total_fee"
            . "&key=$key"));//sign加密MD5

        $refund=array(
            'appid'=>$appid,//应用ID，固定
            'mch_id'=>$mchid,//商户号，固定
            'nonce_str'=>'6',//随机字符串
            'op_user_id'=> $mchid ,//操作员
            'out_refund_no'=>$out_refund_no,//商户内部唯一退款单号
            'out_trade_no'=>$out_trade_no,//商户订单号,pay_sn码 1.1二选一,微信生成的订单号，在支付通知中有返回
            'refund_fee'=>$refund_fee,//退款金额
            'total_fee'=>$total_fee,//总金额
            //'transaction_id'=>$out_trade_no,//微信订单号 1.2二选一,商户侧传给微信的订单号
            'sign'=>$ref//签名
        );

        $url="https://api.mch.weixin.qq.com/secapi/pay/refund";;//微信退款地址，post请求
        $xml=$this->arrayToXml($refund);

        $ch=curl_init();
        //超时时间
        curl_setopt($ch,CURLOPT_TIMEOUT,30);
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_HEADER,false);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);//证书检查
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);//严格校验
        curl_setopt($ch,CURLOPT_SSLCERTTYPE,'pem');
        curl_setopt($ch,CURLOPT_SSLCERT,PLUGIN_PATH .'payment/weixin/cert/apiclient_cert.pem');
        curl_setopt($ch,CURLOPT_SSLCERTTYPE,'pem');
        curl_setopt($ch,CURLOPT_SSLKEY,PLUGIN_PATH .'payment/weixin/cert/apiclient_key.pem');
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$xml);
        $data=curl_exec($ch);

        if($data){ //返回来的是xml格式需要转换成数组再提取值，用来做更新
            curl_close($ch);
            //禁止引用外部xml实体
            $xml =simplexml_load_string($data,'SimpleXMLElement', LIBXML_NOCDATA);

            $data = json_decode(json_encode($xml),TRUE);

            return $data;
        }else{
            $error=curl_errno($ch);
            echo "curl 出错，错误代码：$error"."<br/>";
            curl_close($ch);
            return false;
        }

    }

    /**
     * 微信退款XML格式
     */
    private function arrayToXml($arr){
        $xml = "<root>";
        foreach ($arr as $key=>$val){
            if(is_array($val)){
                $xml.="<".$key.">".arrayToXml($val)."</".$key.">";
            }else{
                $xml.="<".$key.">".$val."</".$key.">";
            }
        }
        $xml.="</root>";
        return $xml ;
    }



    /**
     * 支付宝退款
     * @param $order_sn 订单号
     * @param $money 退款金额
     * @param $out_request_no 退款批次
     * @param $mobile 用户ID
     */
    public function refund_for_alipay($order_sn,$money,$out_request_no = 1,$mobile){


        $url='http://www.yilitong.com/newapp/api/pay.php?act=return_alipay';


        $pwd = encrypt($mobile);
        $data=array('order_sn'=>$order_sn,'money'=>$money,'out_request_no'=>$out_request_no,'pwd'=>$pwd);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'accept:textml,application/xhtml+xml,application/xml;q=0.9,image/webp,*//*;q=0.8',
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



    /**
     * 删除订单
     */
    function delOrder($order_id){
    	$a = Db::name('order')->where(array('order_id'=>$order_id))->delete();
    	$b = Db::name('order_goods')->where(array('order_id'=>$order_id))->delete();
    	return $a && $b;
    }
	
	
	 /**
     * 退款记录
     */
    function refund_log($orderSn,$status){
    	$add['add_time'] = time();
		$add['order_sn'] = $orderSn;
        $add['log'] = serialize($status);
        $add['supplier_id'] = session('supplier_id');
        Db::name('refund_log')->insert($add);
    }
	
	

}