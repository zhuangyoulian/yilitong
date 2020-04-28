<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/4
 * Time: 14:18
 */
namespace ylt\mobile\controller;
use ylt\admin\model\GroupBuy;
use ylt\home\logic\CartLogic;
use ylt\home\logic\OrderLogic;
use ylt\home\logic\UsersLogic;
use think\Db;
use think\Url;
use ylt\home\logic\GoodsLogic;
class Cart extends MobileBase {

    public $cartLogic; // 购物车逻辑操作类
    public $user_id = 0;
    public $user = array();
    /**
     * 析构流函数
     */
    public function  __construct() {
        parent::__construct();
        $this->cartLogic = new \ylt\home\logic\CartLogic();
        if (session('?user')) {
            $user = session('user');
            $user = Db::name('users')->field('user_id,mobile,mobile_validated,parent_id')->where("user_id", $user['user_id'])->find();
            session('user', $user);  //覆盖session 中的 user
            $this->user = $user;
            $this->user_id = $user['user_id'];
            $this->assign('user', $user); //存储用户信息

        }
    }

    public function cart(){
        unset($_SESSION["CouponMoney"]);  
        unset($_SESSION["CodeMoney"]);  
        unset($_SESSION["CodeCode"]);  
        unset($_SESSION["get_coupon_id"]);  
        unset($_SESSION["Distribution"]);  
        $Distribution=$_SERVER[REQUEST_URI];
        if (strpos($Distribution,'/Mobile/Distribution')) {
            session('Distribution',1);
            $Distribution=session('Distribution');
            $this->assign('Distribution',$Distribution);
        }
        $hot_goods = Db::name('Goods')->where("is_recommend=1 and is_on_sale=1 and examine=1")->order('sort asc')->limit(20)->cache(true,YLT_CACHE_TIME)->select();
        $this->assign('hot_goods',$hot_goods);
        return $this->fetch();
    }

    /*
   * ajax 请求获取购物车列表
   */
    public function ajaxCartList()
    {   
        //删除未支付的selected=2商品
        Db::name('Cart')->where(['selected'=>2,'user_id'=>$this->user_id])->delete();  

        $post_goods_num = input("goods_num/a",array());       // goods_num 购物车商品数量
        $post_cart_select = input("cart_select/a",array());   // 购物车选中状态
		$post_supplier_select = input("supplier/a",array());  // 商铺选中状态
		$post_supplier_cancel = input("supplier_cancel");     //取消店铺全部商品
        $where['session_id'] = $this->session_id;// 默认按照 session_id 查询
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
        if(empty($result['total_price'])){
            $result['total_price'] = Array( 'total_fee' =>0, 'cut_fee' =>0, 'num' => 0); 
        }
        $ratio=Db::name('distribution_id')->where('id=1')->field('ratio')->value('ratio'); //查询佣金分成比例

        $this->assign('ratio',$ratio);
        $this->assign('cartList', $result['cartList']); // 购物车的商品
        $this->assign('total_price', $result['total_price']); // 总计
        return $this->fetch('ajax_cart_list');
    }

    /*
 * ajax 获取用户收货地址 用于购物车确认订单页面
 */
    public function ajaxAddress(){
        $regionList = get_region_list();
        $address_list = Db::name('UserAddress')->where("user_id", $this->user_id)->select();
        $c = Db::name('UserAddress')->where("user_id = {$this->user_id} and is_default = 1")->count(); // 看看有没默认收货地址
        if((count($address_list) > 0) && ($c == 0)) // 如果没有设置默认收货地址, 则第一条设置为默认收货地址
            $address_list[0]['is_default'] = 1;

        $this->assign('regionList', $regionList);
        $this->assign('address_list', $address_list);
        return $this->fetch('ajax_address');
    }

    /**
     * ajax 删除购物车的商品
     */
    public function ajaxDelCart()
    {
        $ids = input("ids"); // 商品 ids
        $result = Db::name("Cart")->where("id","in",$ids)->delete(); // 删除id为5的用户数据
        $return_arr = array('status'=>1,'msg'=>'删除成功','result'=>''); // 返回结果状态
        exit(json_encode($return_arr));
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
        if (empty($goods_spec) && !empty(I('spec_id'))) {
            $goods_key = I('goods_key'); // 商品规格(键名)
            $goods_val = I('goods_val'); // 商品ID
            $spec_id = I('spec_id');     // 商品规格拼接ID
            $goods_spec=array($goods_key=>$goods_val);
        }
        //添加商品类型0为商品详情页面添加，1为购物车修改
		$cart_id = input('cart_id') ? intval(input('cart_id')) : 0 ; 
        unset($_SESSION['selected_goods_id']);              //删除之前的立即购买记录
        unset($_SESSION['selected']); 

        if (input('to_catr') == 1) {                       //详情页立即购买时传参to_catr：1
            $selected=2;
        }else{
            $selected=1;
        }

        //判断是否预约后的抢购商品
        if (I('is_make')==1) {
            if (!$this->user_id) {
                return array('status' => 311);
            }
            if (Db::name('cart')->where(['goods_id'=>$goods_id,'user_id'=>$this->user_id])->find()) {
                return array('status' => 1);
            }
            if (Db::name('Order')->alias('o')->join('order_goods g','o.order_id = g.order_id')->where(['goods_id'=>$goods_id,'user_id'=>$this->user_id,'pay_status'=>0])->where('o.order_status != 3 && o.order_status != 5 ')->find()) {
                return array('status' => -1, 'msg' => '已有订单记录，请前往待付款订单中支付');
            }
            if ($goods_num > 1) {
                return array('status' => -1, 'msg' => '商品限购1份');
            }
            if (!Db::name('goods_consult')->where(['goods_id'=>$goods_id,'user_id'=>$this->user_id,'make_type'=>I('make_type')])->find()) {
                return array('status' => -1, 'msg' => '无预约记录，请等待下次预约');
            }
            if (Db::name('goods_consult')->where(['goods_id'=>$goods_id,'user_id'=>$this->user_id,'make_type'=>I('make_type'),'is_use'=>1])->find()) {
                return array('status' => -1, 'msg' => '已有购买记录，此活动商品限购1份');
            }
            if (Db::name('goods_consult')->where(['goods_id'=>$goods_id,'user_id'=>$this->user_id,'make_type'=>I('make_type')])->where('is_win != 1')->find()) {
                return array('status' => -1, 'msg' => '此账号未中签，请等待下次预约');
            }
        }
        //判断是否预约后的抢购商品 结束

        //判断是否发起拼单
        if (I('prom')) {
            $array = Db::name('discount_buy')->alias('b')->join('discount_goods g','b.id = g.discount_id')->where(['goods_id'=>$goods_id])->find();
            if (Db::name('share_the_bill')->where(['goods_id'=>$goods_id,'u_id'=>$this->user_id,'type'=>1,'is_initiate'=>1])->find()) {
                return array('status' => -1, 'msg' => '您有该商品的发起拼单尚未结束，请邀请好友参与拼单');
            }elseif (Db::name('order')->alias('o')->join('order_goods g','o.order_id = g.order_id')->where(['o.is_share'=>1,'g.goods_id'=>$goods_id,'o.user_id'=>$this->user_id,'o.pay_status'=>0])->field('o.order_id')->find()) {
                return array('status' => -18, 'msg' => '已有发起记录，请前往订单列表查看详情');
            }elseif (I('prom') !=1  and Db::name('order')->where(['is_share'=>I('prom'),'user_id'=>$this->user_id])->field('order_id')->find()) {
                return array('status' => -18, 'msg' => '已有拼单记录，请前往订单列表查看详情');
            }

            if (!empty($array['buy_type_purchase_num'])) {  //设置起购量时判断
                if ($array['buy_type_purchase_num'] > 0 &&  $array['buy_type_purchase_num'] > $goods_num) {
                    return array('status' => -35, 'msg' => '该商品起购'.$array['buy_type_purchase_num'].'件','num'=>$array['buy_type_purchase_num']);
                }
            }
            if (!empty($array['buy_type_purchase_num_s'])) { //设置限购量时判断
                if($array['buy_type_purchase_num_s'] > 0 &&  $array['buy_type_purchase_num_s'] < $goods_num){
                    return array('status' => -35, 'msg' => '该商品限购'.$array['buy_type_purchase_num_s'].'件','num'=>$array['buy_type_purchase_num_s']);
                }
                //限购时查询用户已参加的拼单商品数量，限制购买总数量
                $share_goods_num = Db::name('order')->alias('o')->join('order_goods g','o.order_id = g.order_id')->where('o.is_share > 0')->where(['o.user_id'=>$this->user_id,'pay_status'=>1,'g.goods_id'=>$goods_id])->field('o.order_id,g.goods_num')->sum('goods_num');
                if (($share_goods_num+$goods_num ) > $array['buy_type_purchase_num_s']) {
                    $num_s = $array['buy_type_purchase_num_s']-$share_goods_num;
                    return array('status' => -1, 'msg' => '该商品达购买上限，剩余可购买'.$num_s.'件');
                }
            }
            $is_share = I('prom');
        }
        //判断是否发起拼单 结束
        
        if (empty($spec_id)) {
            $result = $this->cartLogic->addCart($goods_id, $goods_num, $goods_spec,$this->session_id,$this->user_id,$cart_id,$brokerage,$selected,$is_share); // 将商品加入购物车
        }else{
            $result = $this->cartLogic->addCartFU($goods_id, $goods_num, $spec_id,$this->session_id,$this->user_id,$cart_id,$brokerage,$selected,$is_share); // 将商品加入购物车
        }
        exit(json_encode($result));
    }
	
	
	 /**
     * 购物车第二步确定页面
     */
    public function orderconfirm()
    {   

        if (session('selected_goods_id') and session('selected')) {
            $goods_id = session('selected_goods_id');
            $selected = session('selected');
        }
        unset($_SESSION['shipping_price']); 
        if (I('goods_id/d') and I('selected/d')) {
            $goods_id = I('goods_id/d');        //立即购买的商品ID
            $selected  = I('selected/d');       //详情页立即购买
            session('selected',$selected);
            session('selected_goods_id',$goods_id);
        }

        $custom_id=session('custom_id');
        $address_id = I('address_id/d');
        $user_id =$this->user_id;
        $logic = new UsersLogic();
        $user = $logic->get_info($user_id); //当前登录用户信息
        if($this->user_id == 0){
            $this->error('请先登陆',Url('User/index'));
        }
        if (empty($user['result']['mobile'])) {
            $this->error('请先绑定手机账号',Url::build('User/mobile_validate_two'));
        }
        
        if($address_id){
            $address = Db::name('user_address')->where("address_id", $address_id)->find();
        } else {
            $address = Db::name('user_address')->where(['user_id'=>$this->user_id,'is_default'=>1])->find();
        }
        if(empty($address)){
        	header("Location: ".Url('User/add_address',array('source'=>'orderconfirm')));
            exit;
        }else{
			if (is_numeric($address['province'])) {
                //地址省市区缓存
                $region_list = get_region_list();
                $address['addres'] = $region_list[$address['province']]['name'].$region_list[$address['city']]['name'].$region_list[$address['district']]['name'].$region_list[$address['twon']]['name'];
            }else{
                $address['addres'] = $address['province'].$address['city'].$address['district'];
            }
            $this->assign('address',$address);
        }
        
        if ($selected == 2) {    //详情页立即购买
            $result = $this->cartLogic->cartList($this->user, $this->session_id,2,1,1,$goods_id); // 获取购物车商品
        }else{                   //购物车提交选择商品
            if($this->cartLogic->cart_count($this->user_id,1) == 0 ) {
                $this->error('你的购物车没有选中商品',Url::build('Cart/cart'));
            }
            $result = $this->cartLogic->cartList($this->user, $this->session_id,1,1,1); // 获取购物车商品
        }
        if (!$result['cartList']) {
            $this->redirect('User/order_list');
        }
        session('shipping_price',$result['total_price']['shipping_price']);  //保存订单邮费


        // //订单页面优惠券信息
        // $cartList=$result['cartList'];
        // $coupon_s=$this->cartLogic->CouponList($cartList,$this->user);//获取订单优惠券列表
        // if ($coupon_s) {
        //     foreach ($coupon_s as $key => $value) {
        //         if (!empty($value)) {
        //             $value_s[]=$value;
        //             $coupon_s_ss=count($value_s);
        //         }
        //     }
        // }
        // //获取用户选定的优惠券数量及金额
        // $get_coupon_id=input('get_coupon_id/a');
        // if (!empty($get_coupon_id)) {
        //     if (empty(count($get_coupon_id)) ) {
        //         unset($_SESSION["get_coupon_id"]);  
        //         unset($_SESSION["CouponNum"]);  
        //         unset($_SESSION["CouponMoney"]);  
        //     }else{
        //         session('get_coupon_id',$get_coupon_id);
        //         $get_coupon_id['count']=count($get_coupon_id);
        //     }
        //     session('CouponNum',$get_coupon_id['count']);
        //     $CouponMoney=0;
        //     foreach ($get_coupon_id as $key => $value) {
        //         $CouponMoney+=$value['money'];
        //         session('CouponMoney',$CouponMoney);
        //     }
        // }
        // $getCoupon['CouponNum']=session('CouponNum');
        // $getCoupon['CouponMoney']=session('CouponMoney') ? session('CouponMoney'):0;
        // //获取选定的优惠券结束
        // $this->assign('coupon_s_ss', $coupon_s_ss); 
        // $this->assign('getCoupon',$getCoupon); 
        // $this->assign('coupon_s', $coupon_s); 
        // //订单页面优惠券信息结束


        //订单页面礼品卡信息
        $code_uid=Db::name('code_list')->alias('l')->join('code c','l.cid=c.id')->where(array('l.uid'=>$user_id,'l.use_time'=>0,'l.order_id'=>0))->where("use_end_time>".time()." && use_start_time<".time())->field('c.id,c.use_end_time,l.binding_time,c.money,l.number')->select();
        if (empty(count($code_uid))) {
            unset($_SESSION["CodeCode"]);  
        }else{
            $code_uid_count=count($code_uid);
        }
        $this->assign('code_uid_count', $code_uid_count); 
        $code=input("code/a",array());
        if ($code) {
            $code_s=$code[0]['code'];
            $this->ajaxCode($code_s);
        }
        //订单页面礼品卡信息结束


        //查询是否有定制信息
        $custom=Db::name('custom')->where('id',$custom_id)->find();
        $this->assign('custom', $custom);  
        //查询是否有定制信息结束


        // $goods_id=array();
        // foreach ($result['cartList'] as $key => $value) {
        //     $goods_id = array_merge($goods_id,array_column($value['list'],'goods_id'));
        // }
        // $now_time = time();
        // $pay_money=$result['total_price']['total_fee'];
        // $unsed=Db::name('coupon_list')->alias('a')->join('coupon b', 'a.cid=b.id', 'LEFT')->where("a.uid=".$this->user_id." and a.use_time=0 and b.use_start_time<$now_time and b.use_end_time>$now_time and b.condition<=$pay_money and b.coupon_type=0")->field('b.*,a.id as coupon_id,a.code as couponCode')->select();

        // foreach ($unsed as $key => $value) {
        //     $coupon_goods=explode(',', $value['goods_id']);
        //     if (empty(array_intersect($goods_id,$coupon_goods))) {
        //         unset($unsed[$key]);
        //     }
        // $this->assign('list',$unsed);
        // } 

        //抗疫行动需要，判断是否需要弹框推荐
        $shaer = 0;
        $shaer_goods = 0;
        foreach ($result['cartList'] as $key => $value) {
            if ($key==41) {
                foreach ($value['list'] as $k => $val) {
                    if ($val['goods_id']==5862 || $val['goods_id']==5897 ) {
                        $shaer += 1;
                        $shaer_goods = $val['goods_id'];
                    }
                }
            }
        }
        $this->assign('shaer_goods',$shaer_goods);
        $this->assign('shaer',$shaer);
        //抗疫行动结束可删
        
        $this->assign('selected',$selected);
        $this->assign('user_id',$user_id);
        $this->assign('cartList', $result['cartList']); // 购物车的商品                                 
        $this->assign('total_price', $result['total_price']); // 总计      

        return $this->fetch();
    }
  

    /**
     * [ajaxCode 礼品卡]
     * @param  [type] $code [description]
     * @return [type]       [description]
     */
  	public function ajaxCode($code){
        $result = $this->cartLogic->cartList($this->user, $this->session_id,1,1,1); // 获取购物车商品
    	if($this->user_id == 0){
            session('login_url',$_SERVER[REQUEST_URI]);
            $this->error('请先登陆',Url::build('User/login'));
        }
        if($this->cartLogic->cart_count($this->user_id,1) == 0 ){ 
            $this->error ('你的购物车没有选中商品','Cart/cart');
        }
        if ($code) {
            $aa = $this->cartLogic->zhongqiuCode($code,$result['cartList']);   //2019.09.06 中秋活动，结束后可删
            $bb = $this->cartLogic->electricCode($code,$result['cartList']);   //2019.10.14 电器活动，结束后可删
            if ($aa['status']=="-1" OR $bb['status']=="-1") {
                $this->error('使用该优惠券时只可购买一份商品');
            }
            $return_arr=$this->cartLogic->getCode($code,$this->user);
            session('CodeCode',$code);
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

        $shipping_code  =  input("shipping_code"); //  物流编号
        $address_id     =  input("address_id"); //  收货地址id
        $invoice_title  =  input("invoice_title"); // 发票
        $coupon_id      =  input("coupon_id"); //  优惠券id
        $couponCode     =  session('CodeCode') ? session('CodeCode'):''; //  礼品卡代码
        $pay_points     =  input("pay_points",0); //  使用积分
		$recommend_code =  input("recommend_code"); //推荐人
        $user_money     =  0;
		$user_note      =  I('POST.'); //	留言
        $shipping_price =  $_SESSION['shipping_price'];
        $selected       =  I('selected');                   // 立即购买

        if(!$address_id) exit(json_encode(array('status'=>-3,'msg'=>'请先填写收货人信息','result'=>null))); // 返回结果状态
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
                'postFee'      => $result['result']['shipping_price'],  // 物流费
                'couponFee'    => $result['result']['coupon_price'],    // 优惠券          
                'codeFee'      => $result['result']['code_price'],      // 礼品卡           
                'pointsFee'    => $result['result']['integral_money'],  // 积分支付            
                'payables'     => number_format($result['result']['order_amount'], 2, '.', '')-$order_prom_amount, // 应付金额
                'goodsFee'     => $result['result']['goods_price'],     // 商品价格            
                'order_prom_id' => $result['result']['order_prom_id'],  // 订单满减活动id
                'order_prom_id_s' => $result['result']['order_goods'][$i]['prom_id'], // 订单用券活动id
                'order_prom_amount' => $result['result']['order_prom_amount'], // 订单优惠活动优惠了多少钱
            );
        }
        $order_prom_id_s=$result['result']['coupon_Yprice'];
         
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

        $order_id = I('order_id/d');
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
            if($res['status']==1)
                $this->error('订单超时未支付已自动取消',Url::build("User/order_detail",array('id'=>$order_id)));
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

        //限购时查询用户已参加的拼单商品数量，限制购买总数量
        $share_goods = Db::name('order_goods')->where(['order_id'=>$order_id])->find();
        $array = Db::name('discount_buy')->alias('b')->join('discount_goods g','b.id = g.discount_id')->where(['goods_id'=>$share_goods['goods_id'],'b.buy_type'=>7])->find();
        if ($array) {
            $share_goods_num = Db::name('order')->alias('o')->join('order_goods g','o.order_id = g.order_id')->where('o.is_share > 0')->where(['o.user_id'=>$order['user_id'],'pay_status'=>1,'g.goods_id'=>$share_goods['goods_id']])->field('o.order_id,g.goods_num')->sum('goods_num');
            if (($share_goods_num+$share_goods['goods_num'] ) > $array['buy_type_purchase_num_s']) {
                $num_s = $array['buy_type_purchase_num_s']-$share_goods_num;
                $this->error('该商品达购买上限，剩余可购买'.$num_s.'件',Url::build("User/order_detail",array('id'=>$order_id)));
            }
        }
        

        $this->assign('paymentList',$paymentList);
        $this->assign('order',$order);
        $this->assign('bankCodeList',$bankCodeList);
        $this->assign('pay_date',date('Y-m-d', strtotime("+1 day")));
        return $this->fetch();
    }

	/**
	 * 购物车获取商品规格
	 */
	public function ajax_cart_spec(){
		$goods_id = intval(input('goods_id/d'));
		$cart_id = intval(input('cart_id/d'));
		
		$goodsLogic = new \ylt\home\logic\GoodsLogic();
		
		$spec_goods_price  = Db::name('goods_price')->where("goods_id", $goods_id)->column("key,price,store_count,quantity");
		$filter_spec = $goodsLogic->get_spec($goods_id);
		
		$goods_info = Db::name('goods')->field('goods_id,goods_name,original_img,shop_price,store_count')->where('goods_id',$goods_id)->find();
		
		if($goods_info['prom_type'] > 0){
			$goods_info['flash_sale'] = get_goods_promotion($goods_id);
        }
		
		$goods_info['cart_id'] = $cart_id;
		$this->assign('filter_spec',$filter_spec);//规格参数
		$this->assign('goods',$goods_info);
		$this->assign('spec_goods_price',json_encode($spec_goods_price,true));
		return $this->fetch(); 
	}
		
		


}