<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/25
 * Time: 14:18
 */
namespace ylt\program\controller;
use ylt\admin\model\GroupBuy;
use ylt\home\logic\CartLogic;
use ylt\home\logic\OrderLogic;
use think\Db;
use think\Url;
use ylt\home\logic\GoodsLogic;
use think\Controller;
use think\AjaxPage;
use think\Config;
use think\Page;
use think\Request;
use think\Verify;
use think\Cache;


class Cart extends ProgramBase {

    public $cartLogic; // 购物车逻辑操作类
    public $user_id = 0;
    public $user = array();
    /**
     * 析构流函数
     */
    public function  __construct() {
        parent::__construct();
        $this->cartLogic = new \ylt\home\logic\CartLogic();
        if (I('user_id')) {
            $user = session('user');
            $user = Db::name('users')->where("user_id",I('user_id'))->find();
            session('user', $user);  //覆盖session 中的 user
            $this->user = $user;
            $this->user_id = $user['user_id'];
        }else{
            exit(json_encode(array('status' => -100, 'msg' => '请先登录！')));
        }
    }

    public function cart(){
        return $this->fetch();
    }

    /*
    * ajax 请求获取购物车列表
    */
    public function ajaxCartList()
    { 
        //删除未支付的selected=2商品
        Db::name('Cart')->where(['selected'=>2,'user_id'=>$this->user_id])->delete();  
        
        $post = input('');
        $post_goods_num       = json_decode($post["goods_num"],true);           // goods_num 购物车商品数量
        $post_cart_select     = json_decode($post["cart_select"],true);         // 购物车选中状态
        $post_supplier_select = json_decode($post["supplier"],true);            // 商铺选中状态
        $post_supplier_cancel = json_decode($post["supplier_cancel"],true);     // 取消店铺全部商品
        $where['session_id']  = $this->session_id;               // 默认按照 session_id 查询
        
        // 如果这个用户已经登录则按照用户id查询
        if($this->user_id){
            unset($where);
            $where['user_id'] = $this->user_id;
        }
        $cartList = Db::name('Cart')->where($where)->column("id,goods_num,selected,prom_type,prom_id");

		//选中店铺状态
		if($post_supplier_select){
			foreach($post_supplier_select as $key => $val){
                Db::name('Cart')->where($where)->where('supplier_id',$val)->update(['selected'=>1]);
			}
		}

		// 取消店铺全部商品
		if($post_supplier_cancel){
			 Db::name('Cart')->where($where)->where('supplier_id',$post_supplier_cancel)->update(['selected'=>0]);
		}

        if($post_goods_num)
        {
            // 修改购物车数量 和勾选状态
            foreach($post_goods_num as $key => $val)
            {   
                $key = (int)$key;
                $data['goods_num'] = $val < 1 ? 1 : $val;
                if($cartList[$key]['prom_type'] == 1 || $cartList[$key]['prom_type'] == 5) //限时抢购 不能超过购买数量
                {
                    $flash_sale = Db::name('panic_buying')->where("id", $cartList[$key]['prom_id'])->find();
                    $data['goods_num'] = $data['goods_num'] > $flash_sale['buy_limit'] ? $flash_sale['buy_limit'] : $data['goods_num'];
                    $data['goods_num'] = $data['goods_num'] > ($flash_sale['goods_num'] - $flash_sale['buy_num']) ? ($flash_sale['goods_num'] - $flash_sale['buy_num']) : $data['goods_num'];
                }
                $data['selected'] = $post_cart_select[$key] ? 1 : 0 ;
                if(($cartList[$key]['goods_num'] != $data['goods_num']) || ($cartList[$key]['selected'] != $data['selected'])){
                    Db::name('Cart')->where("id", $key)->update($data);
                }
            }
            $this->assign('select_all', I('post.select_all')); // 全选框
        }
        $result = $this->cartLogic->cartList($this->user, $this->session_id,1,1,0); // 选中的商品
        // dump($result);exit;
        if(empty($result['total_price'])){
            $result['total_price'] = Array( 'total_fee' =>0, 'cut_fee' =>0, 'num' => 0); 
        }

        $rs=array('result'=>'1','info'=>'请求成功','cartList'=>$result['cartList'],'total_price'=>$result['total_price']);
        exit(json_encode($rs));
    }


    /**
     * 购物车获取商品规格
     */
    public function ajax_cart_spec(){
        $goods_id = intval(input('goods_id/d'));  //商品ID
        $cart_id = intval(input('cart_id/d'));    //购物表中的ID
        $goodsLogic = new \ylt\home\logic\GoodsLogic();
        
        $spec_goods_price  = Db::name('goods_price')->where("goods_id", $goods_id)->column("key,price,store_count");
        $filter_spec = $goodsLogic->get_spec($goods_id);
        
        $goods_info = Db::name('goods')->field('goods_id,goods_name,original_img,shop_price,store_count')->where('goods_id',$goods_id)->find();
        
        // 查看商品是否有活动
         if($goods_info['prom_type'] > 0){
            $goods_info['flash_sale'] = get_goods_promotion($goods_id);
        }

        $goods_info['cart_id'] = $cart_id;

        $return_arr = array('status'=>1,'filter_spec'=>$filter_spec,'goods'=>$goods_info,'spec_goods_price'=>$spec_goods_price); // 返回结果状态
        exit(json_encode($return_arr, JSON_HEX_TAG)); 
    }

    /**
     * ajax 将商品加入购物车
     */
    function ajaxAddCart()
    {
        $brokerage = input("brokerage") ? input("brokerage"):0; // 分销商城过来的1为有佣金 
        $goods_id = input("goods_id"); // 商品id
        $goods_num = input("goods_num");// 商品数量
        $goods_spec = input("goods_spec/a",array()); // 商品规格
        // if ($goods_id == 5916 OR $goods_id ==5913) {
        //     dump($goods_spec);die;
        // }
        if (empty($goods_spec) && !empty(I('spec_id'))) {
            $goods_key = I('goods_key'); // 商品规格(键名)
            $goods_val = I('goods_val'); // 商品ID
            $spec_id = I('spec_id');     // 商品规格拼接ID
            $goods_spec=array($goods_key=>$goods_val);
        }
        $cart_id = input('cart_id') ? intval(input('cart_id')) : 0 ; //添加商品类型 0为商品详情页面添加，>1为购物车修改
        if (input('selected') == 2) {                       //详情页立即购买时传参selected：2
            $selected=2;
        }else{
            $selected=1;
        }

        //判断是否预约后的抢购商品
        if (I('is_make')==1) {
            if (empty(I('make_type'))) {
                exit(json_encode(array('status' => -1, 'msg' => 'make_type值为空')));
            }
            if (Db::name('Order')->alias('o')->join('order_goods g','o.order_id = g.order_id')->where(['goods_id'=>$goods_id,'user_id'=>$this->user_id,'pay_status'=>0])->where('o.order_status != 3 && o.order_status != 5 ')->find()) {
                exit(json_encode(array('status' => -1, 'msg' => '已有订单记录，请前往待付款订单中支付')));
            }
            if ($goods_num > 1) {
                exit(json_encode(array('status' => -1, 'msg' => '商品限购1份')));
            }
            if (!Db::name('goods_consult')->where(['goods_id'=>$goods_id,'user_id'=>$this->user_id,'make_type'=>I('make_type')])->find()) {
                exit(json_encode(array('status' => -1, 'msg' => '无预约记录，请等待下次预约')));
            }
            if (Db::name('goods_consult')->where(['goods_id'=>$goods_id,'user_id'=>$this->user_id,'make_type'=>I('make_type'),'is_use'=>1])->find()) {
                exit(json_encode(array('status' => -1, 'msg' => '已有购买记录，此活动商品限购1份')));
            }
            if (Db::name('goods_consult')->where(['goods_id'=>$goods_id,'user_id'=>$this->user_id,'make_type'=>I('make_type')])->where('is_win != 1')->find()) {
                exit(json_encode(array('status' => -1, 'msg' => '此账号未中签，请等待下次预约')));
            }
        }
        //判断是否预约后的抢购商品 结束

        //判断是否发起拼单
        if (I('prom')) {
            $array = Db::name('discount_buy')->alias('b')->join('discount_goods g','b.id = g.discount_id')->where(['goods_id'=>$goods_id])->find();
            if (Db::name('share_the_bill')->where(['goods_id'=>$goods_id,'u_id'=>$this->user_id,'type'=>1,'is_initiate'=>1])->find()) {
                exit(json_encode(array('status' => -1, 'msg' => '您有该商品的发起拼单尚未结束，请邀请好友参与拼单')));
            }elseif (Db::name('order')->alias('o')->join('order_goods g','o.order_id = g.order_id')->where(['o.is_share'=>1,'g.goods_id'=>$goods_id,'o.user_id'=>$this->user_id,'o.pay_status'=>0])->field('o.order_id')->find()) {
                exit(json_encode(array('status' => -18, 'msg' => '已有发起记录，请前往订单列表查看详情','ps'=>"直接跳到订单列表")));
            }elseif (I('prom') !=1  and Db::name('order')->where(['is_share'=>I('prom'),'user_id'=>$this->user_id])->field('order_id')->find()) {
                exit(json_encode(array('status' => -18, 'msg' => '已有拼单记录，请前往订单列表查看详情','ps'=>"直接跳到订单列表")));
            }
            

            if (!empty($array['buy_type_purchase_num'])) {  //设置起购量时判断
                if ($array['buy_type_purchase_num'] > 0 &&  $array['buy_type_purchase_num'] > $goods_num) {
                    exit(json_encode(array('status' => -35, 'msg' => '该商品起购'.$array['buy_type_purchase_num'].'件','num'=>$array['buy_type_purchase_num'])));
                }
            }
            if (!empty($array['buy_type_purchase_num_s'])) { //设置限购量时判断
                if($array['buy_type_purchase_num_s'] > 0 &&  $array['buy_type_purchase_num_s'] < $goods_num){
                    exit(json_encode(array('status' => -35, 'msg' => '该商品限购'.$array['buy_type_purchase_num_s'].'件','num'=>$array['buy_type_purchase_num_s'])));
                }
                //限购时查询用户已参加的拼单商品数量，限制购买总数量
                $share_goods_num = Db::name('order')->alias('o')->join('order_goods g','o.order_id = g.order_id')->where('o.is_share > 0')->where(['o.user_id'=>$this->user_id,'pay_status'=>1,'g.goods_id'=>$goods_id])->field('o.order_id,g.goods_num')->sum('goods_num');
                if (($share_goods_num+$goods_num ) > $array['buy_type_purchase_num_s']) {
                    $num_s = $array['buy_type_purchase_num_s']-$share_goods_num;
                    exit(json_encode(array('status' => -1, 'msg' => '该商品达购买上限，剩余可购买'.$num_s.'件')));
                }
            }
            $is_share = I('prom');
        }
        //判断是否发起拼单 结束
        // dump($goods_spec);
        // dump($spec_id);
        // die;
        if (empty($spec_id)) {
            $result = $this->cartLogic->addCart($goods_id, $goods_num, $goods_spec,$this->session_id,$this->user_id,$cart_id,$brokerage,$selected,$is_share); // 将商品加入购物车
        }else{
            $result = $this->cartLogic->addCartFU($goods_id, $goods_num, $spec_id,$this->session_id,$this->user_id,$cart_id,$brokerage,$selected,$is_share); // 将商品加入购物车
        }
        exit(json_encode($result));
    }

    /*
     * ajax 获取用户收货地址 用于购物车确认订单页面
     */
    // public function ajaxAddress(){
    //     $regionList = get_region_list();
    //     $address_list = Db::name('UserAddress')->where("user_id", $this->user_id)->select();
    //     // 看看有没默认收货地址
    //     $c = Db::name('UserAddress')->where("user_id = {$this->user_id} and is_default = 1")->count();
    //     // 如果没有设置默认收货地址, 则第一条设置为默认收货地址
    //     if((count($address_list) > 0) && ($c == 0)){
    //         $address_list[0]['is_default'] = 1;
    //     } 
    //     $rs=array('result'=>'1','info'=>'请求成功','regionList'=>$regionList,'address_list'=>$address_list);
    //     exit(json_encode($rs));
    //     // $this->assign('regionList', $regionList);
    //     // $this->assign('address_list', $address_list);
    //     // return $this->fetch('ajax_address');
    // }



    /**
     * ajax 删除购物车的商品
     */
    public function ajaxDelCart()
    {
        $ids = input("cart_id"); // 商品 ids
        $result = Db::name("Cart")->where("id","in",$ids)->delete();         // 删除
        $return_arr = array('status'=>1,'msg'=>'删除成功','result'=>'');     // 返回结果状态
        exit(json_encode($return_arr));
    }
	
	
	 /**
     * 购物车第二步确定页面
     */
    public function orderconfirm()
    {   
        $custom_id  =session('custom_id');    //定制ID
        $address_id = I('address_id/d');      //地址ID
        $goods_id   = I('goods_id/d');        //立即购买的商品ID
        $selected   = I('selected/d');        //详情页立即购买
        $user_id    = $this->user_id;
        if($this->user_id == 0){
            $this->error('请先登陆',Url('User/index'));
        }
        if($address_id){        //查询收货地址
            $address = Db::name('user_address')->where("address_id", $address_id)->find();
        } else {
            $address = Db::name('user_address')->where(['user_id'=>$this->user_id,'is_default'=>1])->find();
        }
        if(!empty($address)){
            if (is_numeric($address['province'])) {
                //地址省市区缓存
                $region_list = get_region_list();
                $address['addres'] = $region_list[$address['province']]['name'].$region_list[$address['city']]['name'].$region_list[$address['district']]['name'].$region_list[$address['twon']]['name'];
            }else{
                $address['addres'] = $address['province'].$address['city'].$address['district'];
            }
        }
        if ($selected == 2) {    //详情页立即购买
            $result = $this->cartLogic->cartList($this->user, $this->session_id,2,1,1,$goods_id); // 获取购物车商品
        }else{                   //购物车提交选择商品
            if($this->cartLogic->cart_count($this->user_id,1) == 0 ) {
                exit(json_encode(array('status'=>-1,'msg'=>'你的购物车没有选中商品','Url'=>'Cart/cart')));
            }
            $result = $this->cartLogic->cartList($this->user, $this->session_id,1,1,1); // 获取购物车商品
        }

        //确定订单页面礼品卡信息
        $code_uid=Db::name('code_list')->alias('l')->join('code c','l.cid=c.id')->where(array('l.uid'=>$user_id,'l.use_time'=>0,'l.order_id'=>0))->where("use_end_time>".time()." && use_start_time<".time())->field('c.id,c.use_end_time,l.binding_time,c.money,l.number')->select();
        if (empty(count($code_uid))) {
            unset($_SESSION["CodeCode"]);  
        }else{
            $getCode['code_count']=count($code_uid);
        }
        $code=input("code/a",array());
        if ($code) {
            $code_s=$code['code'];
            $this->ajaxCode($code_s);
            session('CodeCode',$code['code']);
        }
        $getCode['CodeCode']=session('CodeCode');
        $getCode['CodeMoney']=session('CodeMoney') ? session('CodeMoney'):0;
        //订单页面礼品卡信息结束

        //查询是否有定制信息
        $custom=Db::name('custom')->where('id',$custom_id)->find();
        $this->assign('custom', $custom);  
        //查询是否有定制信息结束

        exit(json_encode(array('status'=>1,'msg'=>'请求成功','cartList'=>$result['cartList'],'total_price'=>$result['total_price'],'address'=>$address,'getCode'=>$getCode,)));
    }

    /**
     * [ajaxCode 查询礼品卡能否使用]
     * @param  [type] $code [description]
     * @return [type]       [description]
     */
    public function ajaxCode($code){
        if($this->user_id == 0){
            exit(json_encode(array('status'=>-1,'msg'=>'请先登陆')));
        }
        if($this->cartLogic->cart_count($this->user_id,1) == 0  ){ 
            exit(json_encode(array('status'=>-1,'msg'=>'你的购物车没有选中商品')));
        }
        if ($code) {
            $return_arr=$this->cartLogic->getCode($code,$this->user);
            session('CodeCode',$code);
            // session('code',$code);
        }
        return $return_arr;
    }
	 
	
	/**
     * ajax 获取订单商品价格 或者提交 订单
     */
    public function cart3(){
        if($this->user_id == 0){
            exit(json_encode(array('status'=>-100,'msg'=>"登录超时请重新登录!",'result'=>null))); // 返回结果状态
        }
        $address_id = input("address_id");          // 收货地址id
        $supplier_id = I('POST.supplier_id');       // 商家ID 
        $condition = I('POST.condition');           // 留言
        $selected =I('selected');                   // 立即购买
        $couponCode     =  I('CodeCode') ? I('CodeCode'):''; //  礼品卡代码
        $key = "user_note_".$supplier_id;           // user_note_拼接商家ID 留言
        $user_note=array($key=>$condition,'act'=>I('post.act'),'source'=>'小程序');         
        $user_money = 0;                
        $shipping_price = 0 ;                       //运费
        
        if(!$address_id){ 
            exit(json_encode(array('status'=>-3,'msg'=>'请先填写收货人信息','result'=>null))); // 返回结果状态
        }
		
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
        $result = calculate_price($this->user_id,$order_goods,$shipping_code,$shipping_price,$address[province],$address[city],$address[district],$pay_points,$user_money,$coupon_id,$couponCode);
        
		if($result['status'] < 0){
			exit(json_encode($result));   
        }
	    // 订单满额优惠活动	
        $order_prom=get_order_promotion($result['result']);
        $result['result']['order_amount'] = $order_prom['order_amount'] ;
        $result['result']['order_prom_id'] = $order_prom['order_prom_id'] ;
        $result['result']['order_prom_amount'] = $order_prom['order_prom_amount'] ;

        // //端午活动
        // foreach ($result["result"]["order_goods"] as $key => $value) {
        //     if ($value['spec_key']==5454) {  //端午活动规格ID
        //         $order_prom_amount+=($value['member_goods_price']-$value['member_goods_price']*1)*$value['goods_num'];   //端午活动折扣参数
        //     }
        // }
        for ($i=0; $i <count($result['result']['order_goods']) ; $i++) { 
            $car_price[] = array(
                'code_goods_id'=> $result['result']['code_goods_id'],   // 礼品卡优惠商品
                'postFee'      => $result['result']['shipping_price'], // 物流费
                'couponFee'    => $result['result']['coupon_price'], // 礼品卡            
                'codeFee'      => $result['result']['code_price'], // 优惠券            
                'pointsFee'    => $result['result']['integral_money'], // 积分支付            
                'payables'     => number_format($result['result']['order_amount'], 2, '.', '')-$order_prom_amount, // 应付金额
                'goodsFee'     => $result['result']['goods_price'],// 商品价格            
                'order_prom_id' => $result['result']['order_prom_id'], // 订单满减活动id
                'order_prom_id_s' => $result['result']['order_goods'][$i]['prom_id'], // 订单用券活动id
                'order_prom_amount' => $result['result']['order_prom_amount'], // 订单优惠活动优惠了多少钱
            );
        }
        $order_prom_id_s=$result['result']['coupon_Yprice'];
        // 提交订单      
        if($_REQUEST['act'] == 'submit_order')
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
        $type = I('type/d');
        $order_id = I('order_id/d');
        if ($type == 6)     //线下支付 修改订单的支付状态即可
        {
            Db::name('Order')->where("order_id", $order_id)->update(['pay_code'=>$type,'pay_name'=>'线下支付']);
            exit;
        }
        
        $order = Db::name('Order')->where("order_id", $order_id)->find();
        // 如果已经支付过的订单直接到订单详情页面. 不再进入支付页面
        if($order['pay_status'] == 1){
            $order_detail_url = Url::build("User/order_detail",array('id'=>$order_id));
            header("Location: $order_detail_url");
            exit;
        }
        $payment_where['type'] = 'payment';
        if(strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
            //微信浏览器
            $payment_where['code'] = 'weixinJSAPI';
        }else{
            $payment_where['code'] = 'alipayMobile';
        }
        if($order['order_prom_type'] != 4){
            $userlogic = new \ylt\home\logic\UsersLogic();
            $res = $userlogic->abolishOrder($order['user_id'],$order['order_id'],$order['add_time']);  //检测是否超时没支付
            if($res['status']==1){
                exit(json_encode(array('status'=>-3,'msg'=>'订单超时未支付已自动取消','order_id'=>$order_id))); // 返回结果状态
            }
        }

        $payment_where['status'] = 1;

        $paymentList = Db::name('Plugin')->where($payment_where)->select();
        $paymentList = convert_arr_key($paymentList, 'code');

        foreach($paymentList as $key => $val)
        {
            $val['config_value'] = unserialize($val['config_value']);
            if($val['config_value']['is_bank'] == 2)
            {
                $bankCodeList[$val['code']] = unserialize($val['bank_code']);
            }
            //判断当前浏览器显示支付方式
            if(($key == 'weixinJSAPI' && !is_weixin()) || ($key == 'alipayMobile' && is_weixin())){
                unset($paymentList[$key]);
            }
        }
        $pay_date=date('Y-m-d', strtotime("+1 day"));


        $return_arr = array('status'=>1,'paymentList'=>$paymentList,'order'=>$order,'bankCodeList'=>$bankCodeList,'pay_date'=>$pay_date); // 返回结果状态
        exit(json_encode($return_arr)); 
    }

	

    /**
     * [boundCodes 订单页面绑定/增加礼品卡]
     * @return [type] [description]
     */
    public function boundCodes(){
        $data=I('post.');
        if ($data) {
            $codes=Db::name('code_list')->where(array('code'=>$data['code'],'uid'=>0))->find();
            if ($codes) {
                Db::name('code_list')->where(array('code'=>$data['code']))->update(array('uid'=>$this->user_id,'binding_time'=>time()));
                exit(json_encode(array('status'=>1,'msg'=>'添加成功！')));
            }else{
                exit(json_encode(array('status'=>-1,'msg'=>'添加失败，密码不正确或已绑定')));
            }
        }
    }


    /**
     * [electCodes 订单页面可用/不可用礼品卡]
     * @return [type] [description]
     */
    public function electCodes(){
        $uid = $this->user_id;
        if (!$uid) {
            exit(json_encode(array('status'=>-100,'msg'=>"登录超时请重新登录!",'result'=>null))); // 返回结果状态
        }
        //可用查询
        $code_uid=Db::name('code_list')->alias('l')->join('code c','l.cid=c.id')->where(array('l.uid'=>$uid,'l.use_time'=>0,'l.order_id'=>0))->where("use_end_time>".time()." && use_start_time<".time())->field('c.id,c.use_end_time,l.binding_time,c.money,l.code,l.number,c.name')->select();
        if ($code_uid) {
            foreach ($code_uid as $key => $value) {
                $value['showSelect']='1';
                $value['binding_time']=date('Y-m-d',$value['binding_time']);
                $value['use_end_time']=date('Y-m-d',$value['use_end_time']);
                $code[]=$value;
            }
        }
        $json_data=json_encode($code, JSON_HEX_TAG);
        echo $json_data;
        exit();
    }
    /**
     * [electCodes 订单页面可用/不可用礼品卡]
     * @return [type] [description]
     */
    public function onctCodes(){
        $uid = $this->user_id;
        if (!$uid) {
            exit(json_encode(array('status'=>-100,'msg'=>"登录超时请重新登录!",'result'=>null))); // 返回结果状态
        }
        //不可用查询
        $code_uid_no=Db::name('code_list')->alias('l')->join('code c','l.cid=c.id')->where(array('l.uid'=>$uid,'l.use_time'=>0,'l.order_id'=>0))->where("use_end_time<".time()." || use_start_time>".time())->field('c.use_end_time,l.binding_time,c.money,l.number,c.name')->select();
        if ($code_uid_no) {
            foreach ($code_uid_no as $key => $value) {
                $value['showSelect']='0';
                $value['binding_time']=date('Y-m-d',$value['binding_time']);
                $value['use_end_time']=date('Y-m-d',$value['use_end_time']);
                $code[]=$value;
            }
        }
        exit(json_encode(array('status'=>-1,'msg'=>'礼品卡不在可使用的时间范围内','json_data'=>$code)));
    }

    /**
     * [cleanCache 清除系统缓存]
     * @return [type] [description]
     */
    public function cleanCache(){  
        if (I('is_end_text')==1) {
            exit(json_encode(array('status'=>2)));
        }           
        delFile(RUNTIME_PATH .'/cache');
        delFile(RUNTIME_PATH .'/html');
        delFile(RUNTIME_PATH .'/temp');
        exit(json_encode(array('status'=>1)));
    }
}