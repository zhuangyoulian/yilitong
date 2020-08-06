<?php

namespace ylt\home\model;
use think\Model;
use think\Db;
/**
 * 购物车 逻辑定义
 */
class CartLogic extends Model
{

    
    /**
     * 加入购物车方法
     * @param type $goods_id  商品id
     * @param type $goods_num   商品数量
     * @param type $goods_spec  选择规格 
     * @param type $user_id 用户id
     */
    function addCart($goods_id,$goods_num,$goods_spec,$session_id,$user_id = 0)
    {       
        
        $goods = Db::name('Goods')->where("goods_id", $goods_id)->cache(true,YLT_CACHE_TIME)->find(); // 找出这个商品
        $specGoodsPriceList = Db::name('GoodsPrice')->where("goods_id", $goods_id)->cache(true,YLT_CACHE_TIME)->getField("key,key_name,price,store_count,sku"); // 获取商品对应的规格价钱 库存 条码
	    $where = " session_id = :session_id ";
        $bind['session_id'] = $session_id;
        $user_id = $user_id ? $user_id : 0;
	    if($user_id){
            $where .= "  or user_id= :user_id ";
            $bind['user_id'] = $user_id;
        }
        $catr_count = Db::name('Cart')->where($where)->bind($bind)->count(); // 查找购物车商品总数量
        if($catr_count >= 20) 
            return array('status'=>-9,'msg'=>'购物车最多只能放20种商品','result'=>'');            
        
        if(!empty($specGoodsPriceList) && empty($goods_spec)) // 有商品规格 但是前台没有传递过来
            return array('status'=>-1,'msg'=>'必须传递商品规格','result'=>'');                        
        if($goods_num <= 0) 
            return array('status'=>-2,'msg'=>'购买商品数量不能为0','result'=>'');            
        if(empty($goods))
            return array('status'=>-3,'msg'=>'购买商品不存在','result'=>'');            
        if(($goods['store_count'] < $goods_num))
            return array('status'=>-4,'msg'=>'商品库存不足','result'=>'');        
        if($goods['prom_type'] > 0 && $user_id == 0)
            return array('status'=>-101,'msg'=>'购买活动商品必须先登录','result'=>'');
        
        //限时抢购 不能超过购买数量        
        if($goods['prom_type'] == 1) 
        {
            $flash_sale = Db::name('panic_buying')->where(['id'=>$goods['prom_id'],'start_time'=>['<',time()],'end_time'=>['>',time()],'goods_num'=>['>','buy_num']])->find(); // 限时抢购活动
            if($flash_sale){
                $cart_goods_num = Db::name('Cart')->where($where)->where("goods_id", $goods['goods_id'])->bind($bind)->getField('goods_num');
                // 如果购买数量 大于每人限购数量
                if(($goods_num + $cart_goods_num) > $flash_sale['buy_limit'])
                {  
                    $cart_goods_num && $error_msg = "你当前购物车已有 $cart_goods_num 件!";
                    return array('status'=>-4,'msg'=>"每人限购 {$flash_sale['buy_limit']}件 $error_msg",'result'=>'');
                }                        
                // 如果剩余数量 不足 限购数量, 就只能买剩余数量
                if(($flash_sale['goods_num'] - $flash_sale['buy_num']) < $flash_sale['buy_limit'])
                    return array('status'=>-4,'msg'=>"库存不够,你只能买".($flash_sale['goods_num'] - $flash_sale['buy_num'])."件了.",'result'=>'');                    
            }
        }                
        
        foreach($goods_spec as $key => $val) // 处理商品规格
            $spec_item[] = $val; // 所选择的规格项                            
        if(!empty($spec_item)) // 有选择商品规格
        {
            sort($spec_item);
            $spec_key = implode('_', $spec_item);
            if($specGoodsPriceList[$spec_key]['store_count'] < $goods_num) 
                return array('status'=>-4,'msg'=>'商品库存不足','result'=>'');
            $spec_price = $specGoodsPriceList[$spec_key]['price']; // 获取规格指定的价格
        }
                
        $where = " goods_id = :goods_id and spec_key = :spec_key"; // 查询购物车是否已经存在这商品
        if($spec_key){
            $cart_bind['spec_key'] = $spec_key;
        }else{
            $cart_bind['spec_key'] = '';
        }
        $cart_bind['goods_id'] = $goods_id;
        if($user_id > 0){
            $where .= " and (session_id = :session_id or user_id = :user_id) ";
            $cart_bind['session_id'] = $session_id;
            $cart_bind['user_id'] = $user_id;
        } else{
            $where .= " and  session_id = :session_id ";
            $cart_bind['session_id'] = $session_id;
        }

        $catr_goods = Db::name('Cart')->where($where)->bind($cart_bind)->find(); // 查找购物车是否已经存在该商品
        $price = $spec_price ? $spec_price : $goods['shop_price']; // 如果商品规格没有指定价格则用商品原始价格
        
        // 商品参与促销
        if($goods['prom_type'] > 0)
        {            
            $prom = get_goods_promotion($goods_id,$user_id);
            $price = $prom['price'];
            $goods['prom_type'] = $prom['prom_type'];
            $goods['prom_id']   = $prom['prom_id'];
        }
        
        $data = array(                    
                    'user_id'         => $user_id,   // 用户id
                    'session_id'      => $session_id,   // sessionid
                    'goods_id'        => $goods_id,   // 商品id
                    'goods_sn'        => $goods['goods_sn'],   // 商品货号
                    'goods_name'      => $goods['goods_name'],   // 商品名称
                    'market_price'    => $goods['market_price'],   // 市场价
                    'goods_price'     => $price,  // 购买价
                    'member_goods_price' => $price,  // 会员折扣价 默认为 购买价
                    'goods_num'       => $goods_num, // 购买数量
                    'spec_key'        => "{$spec_key}", // 规格key
                    'spec_key_name'   => "{$specGoodsPriceList[$spec_key]['key_name']}", // 规格 key_name
                    'sku'        => "{$specGoodsPriceList[$spec_key]['sku']}", // 商品条形码                    
                    'add_time'        => time(), // 加入购物车时间
                    'prom_type'       => $goods['prom_type'],   // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
                    'prom_id'         => $goods['prom_id'],   // 活动id    
					'supplier_id'     => $goods['supplier_id'],   // 入驻商ID
					'supplier_name'	  => $goods['supplier_name'],
					'is_designer'	  => $goods['is_designer'],					
        );                

       // 如果商品购物车已经存在 
       if($catr_goods) 
       {          
           // 如果购物车的已有数量加上 这次要购买的数量  大于  库存输  则不再增加数量
            if(($catr_goods['goods_num'] + $goods_num) > $goods['store_count'])
                $goods_num = 0;
            $result = Db::name('Cart')->where("id", $catr_goods['id'])->save(  array("goods_num"=> ($catr_goods['goods_num'] + $goods_num)) ); // 数量相加
            $cart_count = cart_goods_num($user_id,$session_id); // 查找购物车数量 
            setcookie('cn',$cart_count,null,'/');
            return array('status'=>1,'msg'=>'成功加入购物车','result'=>$cart_count);
       }
       else
       {         
             $insert_id = Db::name('Cart')->add($data);
             $cart_count = cart_goods_num($user_id,$session_id); // 查找购物车数量
             setcookie('cn',$cart_count,null,'/');
             return array('status'=>1,'msg'=>'成功加入购物车','result'=>$cart_count);
       }     
            $cart_count = cart_goods_num($user_id,$session_id); // 查找购物车数量 
            return array('status'=>-5,'msg'=>'加入购物车失败','result'=>$cart_count);        
    }
    
    /**
     * 购物车列表 
     * @param type $user   用户
     * @param type $session_id  session_id
     * @param type $selected  是否被用户勾选中的 0 为全部 1为选中  一般没有查询不选中的商品情况
     * $mode 0  返回数组形式  1 直接返回result
     */
    function cartList($user = array() , $session_id = '', $selected = 0,$mode =0)
    {                   
        
        $where = " 1 = 1 ";
        //if($selected != NULL)
        //    $where = " selected = $selected "; // 购物车选中状态
        $bind = array();
        if($user[user_id])// 如果用户已经登录则按照用户id查询
        {
             $where .= " and user_id = $user[user_id] ";
             // 给用户计算会员价 登录前后不一样             
        }           
        else
        {
            $where .= " and session_id = :session_id";
            $bind['session_id'] = $session_id;
            $user[user_id] = 0;
        }
                                
        $cartList = Db::name('Cart')->where($where)->bind($bind)->select();  // 获取购物车商品
        $anum = $total_price =  $cut_fee = 0;

        foreach ($cartList as $k=>$val){
        	$cartList[$k]['goods_fee'] = $val['goods_num'] * $val['member_goods_price'];
        	$cartList[$k]['store_count']  = getGoodNum($val['goods_id'],$val['spec_key']); // 最多可购买的库存数量        	
                $anum += $val['goods_num'];
                
                // 如果要求只计算购物车选中商品的价格 和数量  并且  当前商品没选择 则跳过
                if($selected == 1 && $val['selected'] == 0)
                    continue;
                
                $cut_fee += $val['goods_num'] * $val['market_price'] - $val['goods_num'] * $val['member_goods_price'];                
        	$total_price += $val['goods_num'] * $val['goods_price'];
        }

        $total_price = array('total_fee' =>$total_price , 'cut_fee' => $cut_fee,'num'=> $anum,); // 总计        
        setcookie('cn',$anum,null,'/');
        if($mode == 1) return array('cartList' => $cartList, 'total_price' => $total_price);
        return array('status'=>1,'msg'=>'','result'=>array('cartList' =>$cartList, 'total_price' => $total_price));
    }

    /**
     * 计算商品的的运费
     * @param type $shipping_code 物流 编号
     * @param type $province 省份
     * @param type $city 市
     * @param type $district 区
     * @return int
     */
    function cart_freight2($shipping_code, $province, $city, $district, $weight)
    {

        if ($weight == 0) return 0; // 商品没有重量
        if ($shipping_code == '') return 0;
        // 先根据 镇 县 区找 shipping_area_id
        $shipping_area_id = Db::name('AreaRegion')->where("shipping_area_id in (select shipping_area_id from  " . config('database.prefix') . "shipping_area where shipping_code = :shipping_code) and region_id = :region_id")->bind(['shipping_code'=>$shipping_code,'region_id'=>$district])->getField('shipping_area_id');
        // 先根据市区找 shipping_area_id
        if ($shipping_area_id == false)
            $shipping_area_id = Db::name('AreaRegion')->where("shipping_area_id in (select shipping_area_id from  " . config('database.prefix') . "shipping_area where shipping_code = :shipping_code) and region_id = :region_id")->bind(['shipping_code'=>$shipping_code,'region_id'=>$city])->getField('shipping_area_id');

        // 市区找不到 根据省份找shipping_area_id
        if ($shipping_area_id == false)
            $shipping_area_id = Db::name('AreaRegion')->where("shipping_area_id in (select shipping_area_id from  " . config('database.prefix') . "shipping_area where shipping_code = :shipping_code) and region_id = :region_id")->bind(['shipping_code'=>$shipping_code,'region_id'=>$province])->getField('shipping_area_id');

        // 省份找不到 找默认配置全国的物流费
        if ($shipping_area_id == false) {
            // 如果市和省份都没查到, 就查询 tp_shipping_area 表 is_default = 1 的  表示全国的  select * from `tp_plugin`  select * from  `tp_shipping_area` select * from  `tp_area_region`
            $shipping_area_id = Db::name("ShippingArea")->where(['shipping_code'=>$shipping_code,'is_default'=>1])->getField('shipping_area_id');
        }
        if ($shipping_area_id == false)
            return 0;
        /// 找到了 shipping_area_id  找config
        $shipping_config = Db::name('ShippingArea')->where("shipping_area_id", $shipping_area_id)->getField('config');
        $shipping_config = unserialize($shipping_config);
        $shipping_config['money'] = $shipping_config['money'] ? $shipping_config['money'] : 0;

        // 1000 克以内的 只算个首重费
        if ($weight < $shipping_config['first_weight']) {
            return $shipping_config['money'];
        }
        // 超过 1000 克的计算方法
        $weight = $weight - $shipping_config['first_weight']; // 续重
        $weight = ceil($weight / $shipping_config['second_weight']); // 续重不够取整
        $freight = $shipping_config['money'] + $weight * $shipping_config['add_money']; // 首重 + 续重 * 续重费

        return $freight;
    }
  
    /**
     * 获取用户可以使用的优惠券
     * @param type $user_id  用户id 
     * @param type $coupon_id 优惠券id
     * $mode 0  返回数组形式  1 直接返回result
     */
    public function getCouponMoney($user_id, $coupon_id,$mode)
    {
        if($coupon_id == 0)
        {
            if($mode == 1) return 0;    
            return array('status'=>1,'msg'=>'','result'=>0);            
        }        
        $couponlist = Db::name('CouponList')->where("uid", $user_id)->where('id', $coupon_id)->find(); // 获取用户的优惠券
        if(empty($couponlist)) {
            if($mode == 1) return 0;    
            return array('status'=>1,'msg'=>'','result'=>0);
        }            
        
        $coupon = Db::name('Coupon')->where("id", $couponlist['cid'])->find(); // 获取 优惠券类型表
        $coupon['money'] = $coupon['money'] ? $coupon['money'] : 0;
       
        if($mode == 1) return $coupon['money'];
        return array('status'=>1,'msg'=>'','result'=>$coupon['money']);        
    }
    
    /**
     * 根据优惠券代码获取优惠券金额
     * @param type $couponCode 优惠券代码
     * @param type $order_momey Description 订单金额
     * return -1 优惠券不存在 -2 优惠券已过期 -3 订单金额没达到使用券条件
     */
    public function getCouponMoneyByCode($couponCode,$order_momey)
    {
        $couponlist = Db::name('CouponList')->where("code", $couponCode)->find(); // 获取用户的优惠券
        if(empty($couponlist)) 
            return array('status'=>-9,'msg'=>'优惠券码不存在','result'=>'');
        if($couponlist['order_id'] > 0){
            return array('status'=>-20,'msg'=>'该优惠券已被使用','result'=>'');
        }
        $coupon = Db::name('Coupon')->where("id", $couponlist['cid'])->find(); // 获取优惠券类型表
        if(time() > $coupon['use_end_time'])  
            return array('status'=>-10,'msg'=>'优惠券已经过期','result'=>'');
        if($order_momey < $coupon['condition'])
            return array('status'=>-11,'msg'=>'金额没达到优惠券使用条件','result'=>'');
        if($couponlist['order_id'] > 0)
            return array('status'=>-12,'msg'=>'优惠券已被使用','result'=>'');
        
        return array('status'=>1,'msg'=>'','result'=>$coupon['money']);
    }
    
    /**
     *  添加一个订单
     * @param type $user_id  用户id     
     * @param type $address_id 地址id
     * @param type $shipping_code 物流编号
     * @param type $invoice_title 发票
     * @param type $coupon_id 优惠券id
     * @param type $car_price 各种价格
     * @param type $user_note 用户备注
     * @return type $order_id 返回新增的订单id
     */
      public function addOrder($user_id,$address_id,$invoice_title,$coupon_id = 0,$car_price,$user_note='')
    {

        $order_count = Db::name('Order')->where("user_id",$user_id)->where('order_sn', 'like', date('Ymd')."%")->count(); // 查找购物车商品总数量
        if($order_count >= 30) 
            return array('status'=>-9,'msg'=>'为避免刷单，一天只能下30个订单','result'=>'');            
        
         // 0插入订单 order
        $address = Db::name('UserAddress')->where("address_id", $address_id)->find();

		//选中的商品
	   $cart = Db::name('Cart')->where(['user_id'=>$user_id,'selected'=>1])->order('supplier_id asc ')->select();

	   // 活动商品数量过滤
        foreach ($cart AS $key => $value) {
            if($value['prom_type']==5){
                $prom = Db::name('panic_buying')->where('id',$value['prom_id'])->find();
                if($prom['start_time'] > time() || $prom['end_time'] < time()){
                    Db::name('Cart')->where('goods_id',$value['goods_id'])->delete();
                    return array('status'=>-9,'msg'=>'活动已结束','result'=>-1);
                }

                if($prom['buy_num'] >= $prom['goods_num']){
                    Db::name('Cart')->where('goods_id',$value['goods_id'])->delete();
                    return array('status'=>-9,'msg'=>'已售馨,本期活动结束','result'=>-1);
                }

            }
        }
		$cartList = [];
		// 分商铺订单
        foreach ($cart as $k=>$val){
			if(!isset($cartList[$val['supplier_id']]['supplier_id']))
			$cartList[$val['supplier_id']]['supplier_name'] = $val['supplier_name'];
			$cartList[$val['supplier_id']]['supplier_id'] = $val['supplier_id'];	
			$val['store_count'] = getGoodNum($val['goods_id'],$val['spec_key']);    	// 最多可购买的库存数量
			$cartList[$val['supplier_id']]['total_price'] += ($val['goods_num'] * $val['goods_price']);
			$cartList[$val['supplier_id']]['shipping_price'] = $this->ShippingPrice($val['supplier_id'],$user_id);
			$cartList[$val['supplier_id']]['list'][] = $val;
                     	
        }
  		// 分单生成订单
        foreach ($cartList as $k=>$val){
			
			  $data = array(
                'order_sn'         => date('YmdHis').rand(1000,9999), // 订单编号
                'user_id'          =>$user_id, // 用户id
                'consignee'        =>$address['consignee'], // 收货人
                'province'         =>$address['province'],//'省份id',
                'city'             =>$address['city'],//'城市id',
                'district'         =>$address['district'],//'县',
                'twon'             =>$address['twon'],// '街道',
                'address'          =>$address['address'],//'详细地址',
                'mobile'           =>$address['mobile'],//'手机',
                'zipcode'          =>$address['zipcode'],//'邮编',            
                'email'            =>$address['email'],//'邮箱',
                'invoice_title'    =>$invoice_title, //'发票抬头',                
                'goods_price'      =>$val['total_price'],//'商品总价格',
				'shipping_price'   =>$val['shipping_price'], //邮费
                'coupon_price'     =>$car_price['couponFee'],//'使用优惠券',                        
                'integral'         =>($car_price['pointsFee'] * tpCache('shopping.point_rate')), //'使用积分',
                'integral_money'   =>$car_price['pointsFee'],//'使用积分抵多少钱',
                'total_amount'     =>($val['total_price'] + $val['shipping_price']),// 订单总额
                'order_amount'     =>($val['total_price'] + $val['shipping_price']),//'应付款金额',
                'add_time'         =>time(), // 下单时间
                'order_prom_type'    =>$car_price['order_prom_type'],//'订单优惠活动id',
                'order_prom_id'    =>$car_price['order_prom_id'],//'订单优惠活动id',
                'order_prom_amount'=>$car_price['order_prom_amount'],//'订单优惠活动优惠了多少钱',
                'user_note'        =>$user_note['user_note_'.$val['supplier_id'].''], // 用户下单备注
                'source'           =>$user_note['source'],
				'supplier_id'	   => $val['supplier_id'],
				'supplier_name'	   => $val['supplier_name']
        );
        $data['order_id'] = $order_id = Db::name("Order")->insertGetId($data);
        $order = $data; 
        if(!$order_id)
            return array('status'=>-8,'msg'=>'添加订单失败','result'=>NULL);

			   // 1插入order_goods 表
			foreach($val['list'] as $key => $va)
			{ 
			   $goods = Db::name('Goods')->where(array('goods_id'=>$va['goods_id'],'is_on_sale'=>'1'))->find();
			   if($goods){
				   $data2['order_id']           = $order_id; // 订单id
				   $data2['goods_id']           = $va['goods_id']; // 商品id
				   $data2['goods_name']         = $va['goods_name']; // 商品名称
				   $data2['goods_sn']           = $va['goods_sn']; // 商品货号
				   $data2['goods_num']          = $va['goods_num']; // 购买数量
				   $data2['market_price']       = $va['market_price']; // 市场价
				   $data2['goods_price']        = $va['goods_price']; // 商品价
				   $data2['spec_key']           = $va['spec_key']; // 商品规格
				   $data2['spec_key_name']      = $va['spec_key_name']; // 商品规格名称
				   $data2['member_goods_price'] = $va['member_goods_price']; // 会员折扣价
				   $data2['cost_price']         = $goods['cost_price']; // 成本价
				   $data2['give_integral']      = $goods['give_integral']; // 购买商品赠送积分         
				   $data2['prom_type']          = $va['prom_type']; // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
				   $data2['prom_id']            = $va['prom_id']; // 活动id
				   $order_goods_id              = Db::name("OrderGoods")->insertGetId($data2);

                   $prom_type = 0;
                   if($va['prom_type'] ==1 || $va['prom_type'] == 2 || $va['prom_type'] == 5){
                       $prom_type = $va['prom_type'];
                       Db::name('order')->where('order_id',$order_id)->update(['order_prom_type'=>$va['prom_type'],'order_prom_id'=>$va['prom_id']]);
                   }

				   Db::name('Cart')->where(['user_id' => $user_id,'id' => $va['id']])->delete();
			   }else{
				   Db::name('Cart')->where(['user_id' => $user_id,'id' => $va['id']])->delete();
				   return array('status'=>8,'msg'=>'提交订单失败，部分商品已下架','result'=>$order_id); // 返回新增的订单id        
			   }    
			}
            // 活动订单即时减库存
            if($prom_type > 0)
                minus_stock($order_id);
		      // 记录订单操作日志
        $action_info = array(
            'order_id'        =>$order_id,
            'action_user'     =>$user_id,            
            'action_note'     => '您提交了订单，请等待系统确认',
            'status_desc'     =>'提交订单', //''
            'log_time'        =>time(),
			'supplier_id'        =>$val['supplier_id']
        );
        Db::name('order_action')->insertGetId($action_info);
                
        }
	
        
        // 如果应付金额为0  可能是余额支付 + 积分 + 优惠券 这里订单支付状态直接变成已支付 
        if($data['order_amount'] == 0)
        {                        
            update_pay_status($order['order_sn']);
        }           
        
        // 2修改优惠券状态  
        if($coupon_id > 0){
        	$data3['uid'] = $user_id;
        	$data3['order_id'] = $order_id;
        	$data3['use_time'] = time();
        	Db::name('CouponList')->where("id", $coupon_id)->update($data3);
                $cid = Db::name('CouponList')->where("id", $coupon_id)->getField('cid');
                Db::name('Coupon')->where("id", $cid)->setInc('use_num'); // 优惠券的使用数量加一
        }

        return array('status'=>1,'msg'=>'提交订单成功','result'=>$order_id); // 返回新增的订单id        
    }
    
    /**
     * 查看购物车的商品数量
     * @param type $user_id
     * $mode 0  返回数组形式  1 直接返回result
     */
    public function cart_count($user_id,$mode = 0){
        $count = Db::name('Cart')->where(['user_id' => $user_id , 'selected' => 1])->count();
        if($mode == 1) return  $count;
        
        return array('status'=>1,'msg'=>'','result'=>$count);         
    }
        
   /**
    * 获取商品团购价
    * 如果商品没有团购活动 则返回 0
    * @param type $attr_id
    * $mode 0  返回数组形式  1 直接返回result
    */
   public function get_group_buy_price($goods_id,$mode=0)
   {
       $group_buy = Db::name('GroupBuy')->where(['goods_id' => $goods_id,'start_time'=>['<=',time()],'end_time'=>['>=',time()]])->find(); // 找出这个商品
       if(empty($group_buy))       
            return 0;
       
        if($mode == 1) return $group_buy['groupbuy_price'];
        return array('status'=>1,'msg'=>'','result'=>$group_buy['groupbuy_price']);       
   }  
   
   /**
    * 用户登录后 需要对购物车 一些操作
    * @param type $session_id
    * @param type $user_id
    */
   public function login_cart_handle($session_id,$user_id)
   {
	   if(empty($session_id) || empty($user_id))
	     return false;
        // 登录后将购物车的商品的 user_id 改为当前登录的id            
        Db::name('cart')->where("session_id", $session_id)->save(array('user_id'=>$user_id));
                
        // 查找购物车两件完全相同的商品
        $cart_id_arr = DB::query("select id from `__PREFIX__cart` where user_id = $user_id group by  goods_id,spec_key having count(goods_id) > 1");
        if(!empty($cart_id_arr))
        {
            $cart_id_arr = get_arr_column($cart_id_arr, 'id');
            $cart_id_str = implode(',', $cart_id_arr);
            Db::name('cart')->delete($cart_id_str); // 删除购物车完全相同的商品
        }
   }
    /**
     * 添加预售商品订单
     * @param $user_id
     * @param $address_id
     * @param $shipping_code
     * @param $invoice_title
     * @param $act_id
     * @param $pre_sell_price
     * @return array
     */
    public function addPreSellOrder($user_id,$address_id,$shipping_code,$invoice_title,$act_id,$pre_sell_price)
    {
        // 仿制灌水 1天只能下 50 单
        $order_count = Db::name('Order')->where("user_id= $user_id and order_sn like '".date('Ymd')."%'")->count(); // 查找购物车商品总数量
        if($order_count >= 50){
            return array('status'=>-9,'msg'=>'一天只能下50个订单','result'=>'');
        }
        $address = Db::name('UserAddress')->where(array('address_id' => $address_id))->find();
        $shipping = Db::name('Plugin')->where(array('code' => $shipping_code))->find();
        $data = array(
            'order_sn'         => date('YmdHis').rand(1000,9999), // 订单编号
            'user_id'          =>$user_id, // 用户id
            'consignee'        =>$address['consignee'], // 收货人
            'province'         =>$address['province'],//'省份id',
            'city'             =>$address['city'],//'城市id',
            'district'         =>$address['district'],//'县',
            'twon'             =>$address['twon'],// '街道',
            'address'          =>$address['address'],//'详细地址',
            'mobile'           =>$address['mobile'],//'手机',
            'zipcode'          =>$address['zipcode'],//'邮编',
            'email'            =>$address['email'],//'邮箱',
            'shipping_code'    =>$shipping_code,//'物流编号',
            'shipping_name'    =>$shipping['name'], //'物流名称',
            'invoice_title'    =>$invoice_title, //'发票抬头',
            'goods_price'      =>$pre_sell_price['cut_price'] * $pre_sell_price['goods_num'],//'商品价格',
            'total_amount'     =>$pre_sell_price['cut_price'] * $pre_sell_price['goods_num'],// 订单总额
            'add_time'         =>time(), // 下单时间
            'order_prom_type'  => 4,
            'order_prom_id'    => $act_id
        );
        if($pre_sell_price['deposit_price'] == 0){
            //无定金
            $data['order_amount'] = $pre_sell_price['cut_price'] * $pre_sell_price['goods_num'];//'应付款金额',
        }else{
            //有定金
            $data['order_amount'] = $pre_sell_price['deposit_price'] * $pre_sell_price['goods_num'];//'应付款金额',
        }
        $order_id = Db::name('order')->insertGetId($data);
//        Db::name('goods_activity')->where(array('act_id'=>$act_id))->setInc('act_count',$pre_sell_price['goods_num']);
        if($order_id === false){
            return array('status'=>-8,'msg'=>'添加订单失败','result'=>NULL);
        }
        logOrder($order_id,'您提交了订单，请等待系统确认','提交订单',$user_id);
        $order = Db::name('Order')->where("order_id = $order_id")->find();
        $goods_activity = Db::name('goods_activity')->where(array('act_id'=>$act_id))->find();
        $goods = Db::name('goods')->where(array('goods_id'=>$goods_activity['goods_id']))->find();
        $data2['order_id']           = $order_id; // 订单id
        $data2['goods_id']           = $goods['goods_id']; // 商品id
        $data2['goods_name']         = $goods['goods_name']; // 商品名称
        $data2['goods_sn']           = $goods['goods_sn']; // 商品货号
        $data2['goods_num']          = $pre_sell_price['goods_num']; // 购买数量
        $data2['market_price']       = $goods['market_price']; // 市场价
        $data2['goods_price']        = $goods['shop_price']; // 商品团价
        $data2['cost_price']         = $goods['cost_price']; // 成本价
        $data2['member_goods_price'] = $pre_sell_price['cut_price']; //预售价钱
        $data2['give_integral']      = $goods_activity['integral']; // 购买商品赠送积分
        $data2['prom_type']          = 4; // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠 ,4 预售商品
        $data2['prom_id']    = $goods_activity['act_id'];
        Db::name('order_goods')->insert($data2);
        // 如果有微信公众号 则推送一条消息到微信
        $user = Db::name('users')->where("user_id = $user_id")->find();
        if($user['oauth']== 'weixin')
        {
            $wx_user = Db::name('wx_user')->find();
            $jssdk = new \app\mobile\logic\Jssdk($wx_user['appid'],$wx_user['appsecret']);
            $wx_content = "你刚刚下了一笔预售订单:{$order['order_sn']} 尽快支付,过期失效!";
            $jssdk->push_msg($user['openid'],$wx_content);
        }
        return array('status'=>1,'msg'=>'提交订单成功','result'=>$order_id); // 返回新增的订单id
    }


    // //话费充值蜂助手第三方接口
    // public function topUpAPI($order_id,$phone){
    //     $url            =   'http://test.www.phone580.com:8000/fzs-open-api/buy/api/sendgoods';
    //     //$url            =   'https://orderapi.phone580.com/fzs-open-api/buy/api/sendgoods';
    //     $appKey         =   'YLTHC1TESTKEY001';
    //     $appSecret      =   '24f724601a9139b9cb';
    //     $channelId      =   'YLTHC1';
    //     $num            =   '1';
    //     $orderId        =   $order_id;
    //     $returl         =   'www.yilitong.com/Mobile/Refillcard/APItype';
    //     // $returl         =   'tp.cn/Mobile/Refillcard/APItype';
    //     $shipInfo       =   "{'phoneNum':$phone,'account':'12312'}";
    //     $skuCode        =   'mgtv_zk';
    //     $srvType        =   'recharge';
    //     $timestamp      =   time()*1000;

    //     $datas=$appSecret.'appKey='.$appKey.'channelId='.$channelId.'num='.$num.'orderId='.$orderId.'returl='.$returl.'shipInfo='.$shipInfo.'skuCode='.$skuCode.'srvType='.$srvType.'timestamp='.$timestamp.$appSecret;

    //     $shipInfo=urlencode($shipInfo);
    //     $data='appKey='.$appKey.'&channelId='.$channelId.'&num='.$num.'&orderId='.$orderId.'&returl='.$returl.'&shipInfo='.$shipInfo.'&skuCode='.$skuCode.'&srvType='.$srvType.'&timestamp='.$timestamp;

    //     $sign=strtoupper(sha1($datas));
    //     $parameter = $url.'?'.$data.'&sign='.$sign;
    //     $hou=array("","","","","");
    //     $qian=array(" ","　","\t","\n","\r");
    //     $parameter = str_replace($qian,$hou,$parameter);
    //     //$parameter = "https://orderapi.phone580.com/fzs-open-api/buy/api/sendgoods";
    //    // print_r($parameter);die;
    //     $type = $this->GetOpenidFromMp($parameter);
    //     // dump($type);die;
    //     return $type;
    // }

    // public function APItype(){
    //     require(ROOT_PATH . 'newapp/includes/weixin/example/log.php');
    //     //初始化日志
    //     $logHandler = new \CLogFileHandler('pay-' . date("Y-m-d") . '.log');
    //     \Log::Init($logHandler, 15);
    //     \Log::DEBUG("测试数据111:" );
    // }

    // /**
    //  *
    //  * 通过code从工作平台获取openid机器access_token
    //  * @param string $code 微信跳转回来带上的code
    //  *
    //  * @return openid
    //  */
    // public function GetOpenidFromMp($url)
    // {
    //     //通过code获取网页授权access_token 和 openid 。网页授权access_token是一次性的，而基础支持的access_token的是有时间限制的：7200s。
    //     //1、微信网页授权是通过OAuth2.0机制实现的，在用户授权给公众号后，公众号可以获取到一个网页授权特有的接口调用凭证（网页授权access_token），通过网页授权access_token可以进行授权后接口调用，如获取用户基本信息；
    //     //2、其他微信接口，需要通过基础支持中的“获取access_token”接口来获取到的普通access_token调用。
    //     // $url = $this->__CreateOauthUrlForOpenid($code);  
    //     $ch = curl_init();//初始化curl        
    //     curl_setopt($ch, CURLOPT_TIMEOUT, 300);//设置超时
    //     curl_setopt($ch, CURLOPT_URL, $url);
    //     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
    //     curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
    //     curl_setopt($ch, CURLOPT_HEADER, FALSE);
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);         
    //     $res = curl_exec($ch);//运行curl，结果以jason形式返回            
    //     $data = json_decode($res,true);         
    //     curl_close($ch);
    //     return $data;
    // }
}