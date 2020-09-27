<?php
/**
 * Created by PhpStorm.
 * User: jiayi
 * Date: 2017/4/8
 * Time: 10:45
 */

namespace ylt\home\controller;
use ylt\home\logic\CartLogic;
use ylt\home\model\Pickup;
use ylt\home\model\UserAddress;
use think\Controller;
use think\Db;
use think\Url;
use think\Request;
class Cart extends Base {

    public $cartLogic; // 购物车逻辑操作类
    public $user_id = 0;
    public $user = array();
    /**
     * 初始化函数
     */
    public function _initialize() {
        parent::_initialize();
        $this->cartLogic = new CartLogic();
        if(I('user_id'))
        {
            $user = Db::name('users')->field('user_id,mobile,mobile_validated,parent_id')->where("user_id", I('user_id'))->find();
            session('user',$user);  //覆盖session 中的 user
            $this->user = $user;
            $this->user_id = $user['user_id'];
        }else{
			
			$nologin = array(
        			'cart','index','ajaxCartList','ajaxAddCart','header_cart_list',
        	);
		}
    }

  
    public function ajaxCode(){
        $result = $this->cartLogic->cartList($this->user, $this->session_id,1,1,1); // 获取购物车商品
    	if($this->user_id == 0){
            exit(json_encode(array('status'=>'-11','info'=>'请先登陆')));
        }
        if($this->cartLogic->cart_count($this->user_id,1) == 0 ){
            exit(json_encode(array('status'=>'-12','info'=>'你的购物车没有选中商品')));
        } 
      	$code=input("code");//优惠卷码
        if ($code) {
            $aa = $this->cartLogic->zhongqiuCode($code,$result['cartList']);   //2019.09.06 中秋活动，结束后可删
            $bb = $this->cartLogic->electricCode($code,$result['cartList']);   //2019.10.14 电器活动，结束后可删
            if ($aa['status']=="-1" OR $bb['status']=="-1") {
                exit(json_encode(array('status'=>'-13','info'=>'使用该优惠券时只可购买一份商品')));
            }
            $return_arr=$this->cartLogic->getCode($code,$this->user);
            session('code',$code);
        }
        exit(json_encode($return_arr));
    }
  
  
    // public function cart(){
    //     return $this->fetch();
    // }

    // public function index(){
    //     return $this->fetch('cart');
    // }


    // public function cart_login(){
    //     $user_id =$this->user_id;
    //     if($this->user_id == 0){
    //         return array('status' => 1, 'msg' => '请先登录', 'result' => '');
    //     }else{
    //         return array('status' => 2, 'msg' => '已登录', 'result' => '');
    //     }
    // }
    
    /**
     * ajax 将商品加入购物车
     */
    function ajaxAddCart()
    {   
        $goods_id = input("goods_id"); // 商品id
        $goods_num = input("goods_num");// 商品数量
        $goods_spec = input("goods_spec/a",array()); // 商品规格
        if (empty($goods_spec) && !empty(I('goods_key'))) {
            $goods_key = I('goods_key'); // 商品规格
            $goods_val = I('goods_val'); // 商品规格
            $goods_spec=array($goods_key=>$goods_val);
        }
        if (input('to_catr') == 1) {                       //详情页立即购买时传参to_catr：1
            $selected=2;
        }else{
            $selected=1;
        }
        $result = $this->cartLogic->addCart($goods_id, $goods_num, $goods_spec,$this->session_id,$this->user_id,0,0,$selected); // 将商品加入购物车
        exit(json_encode($result));
    }

    //ajax 头部"我的购物车"请求列表
    public function header_cart_list()
    {
        $cart_result = $this->cartLogic->cartList($this->user, $this->session_id,0,1,0);
        if(empty($cart_result['total_price'])){
            $cart_result['total_price'] = Array( 'total_fee' =>0, 'cut_fee' =>0, 'num' => 0);
        }

        $rs=array('status'=>'1','info'=>'请求成功','cartList'=>$cart_result['cartList'],'cart_total_price'=>$cart_result['total_price']);
        exit(json_encode($rs));

    }
	
	/*
     * ajax 请求获取购物车列表
     */
    public function ajaxCartList()
    {
        if($this->user_id == 0){
            exit(json_encode(array('status'=>'-11','info'=>'请先登陆')));
        }
        //删除未支付的selected=2商品
        Db::name('Cart')->where(['selected'=>2,'user_id'=>$this->user_id])->delete();  

        $post_goods_num = input("goods_num/a",array()); // goods_num 购物车商品数量
        $post_cart_select = input("cart_select/a",array()); // 购物车选中状态
        $where['session_id'] = $this->session_id;// 默认按照 session_id 查询
        // 如果这个用户已经等了则按照用户id查询
        if($this->user_id){
            unset($where);
            $where['user_id'] = $this->user_id;
        }
        $cartList = Db::name('Cart')->where($where)->column("id,goods_num,selected,prom_type,prom_id");
        if($post_goods_num)
        {
            // 修改购物车数量 和勾选状态
            foreach($post_goods_num as $key => $val)
            {   

                $data['goods_num'] = $val < 1 ? 1 : $val;
                
                if($cartList[$key]['prom_type'] == 1 || $cartList[$key]['prom_type'] == 5) //限时抢购 不能超过购买数量
                {
                    $flash_sale = Db::name('panic_buying')->where("id", $cartList[$key]['prom_id'])->find();
                    $data['goods_num'] = $data['goods_num'] > $flash_sale['buy_limit'] ? $flash_sale['buy_limit'] : $data['goods_num'];
                    $data['goods_num'] = $data['goods_num'] > ($flash_sale['goods_num'] - $flash_sale['buy_num']) ? ($flash_sale['goods_num'] - $flash_sale['buy_num']) : $data['goods_num'];
                }
				 
                $data['selected'] = $post_cart_select[$key] ? 1 : 0 ;                               
                if(($cartList[$key]['goods_num'] != $data['goods_num']) || ($cartList[$key]['selected'] != $data['selected'])) {
                    Db::name('Cart')->where("id", $key)->update($data);
                }
            }
        }

        $result = $this->cartLogic->cartList($this->user, $this->session_id,1,1,0); // 选中的商品
        
        $rs=array('status'=>'1','info'=>'请求成功','prom_goods'=>$cartList,'cartList'=>$result['cartList'],'total_price'=>$result['total_price']);
        exit(json_encode($rs));
		
    }
	
	/**
     * ajax 删除购物车的商品
     */
    public function ajaxDelCart()
    {       
        $ids = input("cart_id"); // 商品 ids
        $result = Db::name("Cart")->where("id", "in", $ids)->delete(); // 删除用户数据
        $return_arr = array('status'=>1,'msg'=>'删除成功','result'=>''); // 返回结果状态       
        exit(json_encode($return_arr));
    }
	
	 /**
     * 购物车第二步确定页面
     */
    public function orderconfirm()
    {   
        unset($_SESSION['shipping_price']);  
        $custom_id=session('custom_id');
        $goods_id = I('goods_id/d');        //立即购买的商品ID
        $selected  = I('selected/d');       //详情页立即购买
        if($this->user_id == 0){
            exit(json_encode(array('status'=>'-11','info'=>'请先登陆','url'=>'Home/User/user_login')));
        }
        
        if ($selected == 2) {    //详情页立即购买
            $result = $this->cartLogic->cartList($this->user, $this->session_id,2,1,1,$goods_id); // 获取购物车商品
        }else{                   //购物车提交选择商品
            if($this->cartLogic->cart_count($this->user_id,1) == 0 ) {
                exit(json_encode(array('status'=>'-17','info'=>'你的购物车没有选中商品','url'=>'Home/Cart/cart')));
            }
            $result = $this->cartLogic->cartList($this->user, $this->session_id,1,1,1); // 获取购物车商品
        }
        if (!$result['cartList']) {
            exit(json_encode(array('status'=>'-18','url'=>'Home/User/order_list')));
        }
        session('shipping_price',$result['total_price']['shipping_price']);  //保存订单邮费
        $custom=Db::name('custom')->where(array('id'=>$custom_id))->find();

        $rs=array('status'=>'1','info'=>'请求成功','custom'=>$custom,'selected'=>$selected,'cartList'=>$result['cartList'],'total_price'=>$result['total_price'],'shipping_price'=>$result['total_price']['shipping_price']);
        exit(json_encode($rs));

    }
	
	/*
     * ajax 获取用户收货地址 用于购物车确认订单页面
     */
    public function ajaxAddress(){
        $address_list = Db::name('UserAddress')->where(['user_id'=>$this->user_id,'is_pickup'=>0])->select();
        if($this->user_id == 0){
            exit(json_encode(array('status'=>'-11','info'=>'请先登陆','url'=>'Home/User/user_login')));
        }
        if($address_list){
        	$area_id = array();
        	foreach ($address_list as $val){
        		$area_id[] = $val['province'];
                        $area_id[] = $val['city'];
                        $area_id[] = $val['district'];
                        $area_id[] = $val['twon'];                        
        	}    
            $area_id = array_filter($area_id);
        	$area_id = implode(',', $area_id);
        }
        $address_where['is_default'] = 1;
        $c = Db::name('UserAddress')->where(['user_id'=>$this->user_id,'is_default'=>1,'is_pickup'=>0])->count(); // 看看有没默认收货地址
        if((count($address_list) > 0) && ($c == 0)) // 如果没有设置默认收货地址, 则第一条设置为默认收货地址
            $address_list[0]['is_default'] = 1;
        $region_list = get_region_list();
        foreach ($address_list as $key => $value) {
            if (!empty($region_list[$value['province']]['name']) && !empty($region_list[$value['city']]['name'])) {
                $value['province'] =  $region_list[$value['province']]['name'];
                $value['city']     =  $region_list[$value['city']]['name'];
                $value['district'] =  $region_list[$value['district']]['name'];
            } 
            $address_listss[] = $value;
        } 

        $rs=array('status'=>'1','info'=>'请求成功','address_list'=>$address_listss);
        exit(json_encode($rs));

    }
	
    /**
    * ajax 获取订单商品价格 或者提交 订单
    */

    public function cart3(){
        $cartsum=I('POST.cartsumsss');

         if($this->user_id == 0){
            exit(json_encode(array('status'=>-100,'msg'=>"登录超时请重新登录!",'result'=>null))); // 返回结果状态
        }
        $address_id     =  input("address_id");     //  收货地址id
        $shipping_code  =  input("shipping_code");  //  物流编号
        $invoice_title  =  input("invoice_title");  //  发票
        $coupon_id      =  input("coupon_id");      //  优惠券id(PC端暂无)
        $codeCode       =  input("codeCode");       //  礼品卡卡码
        $pay_points     =  input("pay_points",0);   //  使用积分
        $recommend_code =  input("recommend_code"); //  推荐人
        $user_money     =  0;
        $user_note      =  input('POST.');          // 留言
        $user_note['source']      =  "PC";          // 订单来源
        $shipping_price =  $_SESSION['shipping_price'];// 物流费
        $selected       =  input('selected');       // 立即购买

        if(!$address_id){ exit(json_encode(array('status'=>-3,'msg'=>'请先填写收货人信息','result'=>null)));}// 返回结果状态
        $address = Db::name('UserAddress')->where("address_id", $address_id)->find();
        if ($selected == 2) {
            $order_goods = Db::name('cart')->where(['user_id'=>$this->user_id,'selected'=>2])->order('id desc')->limit(1)->select();
        }else{
            if($this->cartLogic->cart_count($this->user_id,1) == 0 ){ 
                exit(json_encode(array('status'=>-2,'msg'=>'你的购物车没有选中商品','result'=>null))); // 返回结果状态
            }
            $order_goods = Db::name('cart')->where(['user_id'=>$this->user_id,'selected'=>1])->select();
        }
        //calculate_price()查询是否有活动或优惠卷等，计算订单金额
        $result = calculate_price($this->user_id,$order_goods,$shipping_code,$shipping_price,$address[province],$address[city],$address[district],$pay_points,$user_money,$coupon_id,$codeCode);
        if($result['status'] < 0){
            exit(json_encode($result));   
        }
        // 订单满额优惠活动 
        $order_prom = get_order_promotion($result['result']);
        $result['result']['order_amount'] = $order_prom['order_amount'] ;
        $result['result']['order_prom_id'] = $order_prom['order_prom_id'] ;
        $result['result']['order_prom_amount'] = $order_prom['order_prom_amount'] ;

        //端午活动
        // foreach ($result["result"]["order_goods"] as $key => $value) {
        //     if ($value['spec_key']==5454) {  //端午活动规格ID
        //         $order_prom_amount+=($value['member_goods_price']-$value['member_goods_price']*0.92)*$value['goods_num'];   //端午活动折扣参数
        //     // $car_price['order_prom_amount_p']=$order_prom_amount;
        //       }
        // }
        for ($i=0; $i <count($result['result']['order_goods']) ; $i++) { 
            $car_price[] = array(
                'postFee'      => $result['result']['shipping_price'],  // 物流费
                'couponFee'    => $result['result']['coupon_price'],    // 优惠券            
                'codeFee'      => $result['result']['code_price'],      // 礼品卡            
                'pointsFee'    => $result['result']['integral_money'],  // 积分支付            
                'payables'     => number_format($result['result']['order_amount'], 2, '.', '')-$order_prom_amount, // 应付金额
                'goodsFee'     => $result['result']['goods_price'],// 商品价格            
                'order_prom_id' => $result['result']['order_prom_id'], // 订单满减活动id
                'order_prom_id_s' => $result['result']['order_goods'][$i]['prom_id'], // 订单用券活动id
                'order_prom_amount' => $result['result']['order_prom_amount'], // 订单优惠活动优惠了多少钱
            );
            $order_prom_id_s[]=$result['result']['coupon_Yprice'][$i];
        }
        // 提交订单        
        if(input('act') == 'submit_order')
        {  
            if(empty($coupon_id) && !empty($couponCode)){
               $coupon_id_s = Db::name('CodeList')->where("code", $couponCode)->value('id');//新需求使用优惠卷码
            }
            $result = $this->cartLogic->addOrder($this->user_id,$address_id,$invoice_title,$coupon_id_s,$car_price,$user_note,$recommend_code,$order_prom_id_s,$selected); // 添加订单
            exit(json_encode($result));            
        }
            $return_arr = array('status'=>1,'msg'=>'计算成功','result'=>$car_price); // 返回结果状态
            exit(json_encode($return_arr));        
    }
   
    /*
     * 订单支付页面
     */
    public function payment(){
        
        $order_id = I('order_id/d');
        $order = Db::name('Order')->where("order_id", $order_id)->find();
        
        // 如果已经支付过的订单直接到订单详情页面. 不再进入支付页面
        if($order['pay_status'] == 1){            
            $order_detail_url = Url::build("Home/User/order_detail",array('id'=>$order_id));
            header("Location: $order_detail_url");
            exit;
        }
        //如果是预售订单，支付尾款
        if($order['pay_status'] == 2 && $order['order_prom_type'] == 4){
            $pre_sell_info = Db::name('goods_activity')->where(array('act_id'=>$order['order_prom_id']))->find();
            $pre_sell_info = array_merge($pre_sell_info,unserialize($pre_sell_info['ext_info']));
            if($pre_sell_info['retainage_start'] > time()){
                exit(json_encode(array('status'=>'-19','info'=>'还未到支付尾款时间'.date('Y-m-d H:i:s',$pre_sell_info['retainage_start']))));
            }
            if($pre_sell_info['retainage_end'] < time()){
                exit(json_encode(array('status'=>'-20','info'=>'对不起，该预售商品已过尾款支付时间'.date('Y-m-d H:i:s',$pre_sell_info['retainage_start']))));
            }
        }
		
		
        // 如果是手机则只有手机端支付宝支付
        if(true == isMobile()){
			
             $payment_where = array(
            'type'=>'payment',
            'status'=>1,
            'scene'=>array('in',array(0,1))
			);
			
        }else{
			 $payment_where = array(
            'type'=>'payment',
            'status'=>1,
            'scene'=>array('in',array(0,2))
			);
			
		}

        $paymentList = Db::name('Plugin')->where($payment_where)->select();
        $paymentList = convert_arr_key($paymentList, 'code');
        
        foreach($paymentList as $key => $val)
        {
            $val['config_value'] = unserialize($val['config_value']);            
            if($val['config_value']['is_bank'] == 2)
            {
                $bankCodeList[$val['code']] = unserialize($val['bank_code']);        
            }                
        }                
        
        $rs=array('status'=>'1','info'=>'请求成功','paymentList'=>$paymentList,'order'=>$order,'bankCodeList'=>$bankCodeList,'pay_date'=>date('Y-m-d', strtotime("+1 day")));
        exit(json_encode($rs));

    }	


}