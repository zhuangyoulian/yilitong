<?php
namespace ylt\home\logic;
use think\Model;
use think\Db;
/**
 * 购物车 逻辑定义
 * Class CatsLogic
 * @package Home\Logic
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
    function addCart($goods_id,$goods_num,$goods_spec,$session_id,$user_id = 0,$cart_id = 0,$brokerage = 0)
    {

        $goods = Db::name('Goods')->where("goods_id", $goods_id)->find(); // 找出这个商品
        $specGoodsPriceList = Db::name('goods_price')->where("goods_id", $goods_id)->column("key,key_name,price,store_count,sku"); // 获取商品对应的规格价钱 库存 条码
	    $where = " session_id = :session_id ";
        $bind['session_id'] = $session_id;
        $now_time = time();
        $user_id = $user_id ? $user_id : 0;
	    if($user_id){
            $where .= "  or user_id= :user_id ";
            $bind['user_id'] = $user_id;
        }
        $catr_count = Db::name('Cart')->where($where)->bind($bind)->count(); // 查找购物车商品总数量
        if($catr_count >= 18)
            return array('status'=>-9,'msg'=>'购物车最多只能放18种商品','result'=>'');
        
        if(!empty($specGoodsPriceList) && empty($goods_spec)) // 有商品规格 但是前台没有传递过来
            return array('status'=>-1,'msg'=>'必须传递商品规格','result'=>'');                        
        if($goods_num <= 0) 
            return array('status'=>-2,'msg'=>'购买商品数量不能为0','result'=>'');            
        if(empty($goods) || $goods['is_on_sale'] == 0)
            return array('status'=>-3,'msg'=>'商品已下架','result'=>'');
        if(($goods['store_count'] < $goods_num))
            return array('status'=>-4,'msg'=>'商品库存不足','result'=>'');        
        if($goods['prom_type'] > 0 && $user_id == 0)
            return array('status'=>-101,'msg'=>'购买活动商品必须先登录','result'=>'');
		
		// 活动数据判断
		if($goods['prom_type'] ==1 || $goods['prom_type']==5){
			$result = $this->addPromCart($goods_id,$goods_num,$goods['prom_type'],$goods['prom_id'],$user_id);
			if($result['status'] != 1)
				return array('status'=>$result['status'],'msg'=>$result['msg'],'result'=>'');
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
            // dump($prom);die;
			if($prom != 1){ // 活动中
                if($prom['start_time'] > $now_time || $prom['end_time'] < $now_time){
                        $goods['prom_type'] = 0;
                        $goods['prom_id']   = 0;
               }else{
                    $price = $prom['price'];
                    $goods['prom_type'] = $prom['prom_type'];
                    $goods['prom_id']   = $prom['prom_id'];
                }

			}
            
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
                    'sku'        	  => "{$specGoodsPriceList[$spec_key]['sku']}", // 商品条形码                    
                    'add_time'        => time(), // 加入购物车时间
                    'prom_type'       => $goods['prom_type'],   // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
                    'prom_id'         => $goods['prom_id'],   // 活动id
                    'supplier_id'     => $goods['supplier_id'],   // 入驻商ID
					'supplier_name'	  => $goods['supplier_name'],
					'is_designer'	  => $goods['is_designer'],
					'commission_price'	  => $goods['commission_price'],
        );                

       // 如果商品购物车已经存在 
       if($catr_goods) 
       {          
           // 如果购物车的已有数量加上 这次要购买的数量  大于  库存输  则不再增加数量
            if(($catr_goods['goods_num'] + $goods_num) > $goods['store_count'])
                $goods_num = 0;
            $result = Db::name('Cart')->where("id", $catr_goods['id'])->update(  array("goods_num"=> ($catr_goods['goods_num'] + $goods_num)) ); // 数量相加
            $cart_count = cart_goods_num($user_id,$session_id); // 查找购物车数量 
            setcookie('cn',$cart_count,null,'/');
            return array('status'=>1,'msg'=>'成功加入购物车','result'=>$cart_count);
       }
       else
       {         
             $insert_id = Db::name('Cart')->insert($data);
			 if(!empty($cart_id) && $insert_id){
				  Db::name('Cart')->where('id',$cart_id)->delete();  //购物车直接修改规格
			 }
				 
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
     * @param type $goods_id  商品ID
     * @param type $type  1,5
     * $mode 0  返回数组形式  1 直接返回result
     */
	private function addPromCart($goods_id,$goods_num,$prom_type,$prom_id,$user_id){
		
		        //限量团购 不能超过购买数量

       if($prom_type == 5){
            $panic_buying = Db::name('panic_buying')->where(['id'=>$prom_id])->find();

            if($panic_buying['start_time'] > time())
                return array('status'=>-4,'msg'=>'抢购尚未开始');

            if($panic_buying['end_time'] < time())
                return array('status'=>-4,'msg'=>'活动已结束');

            if($panic_buying['buy_num'] >= $panic_buying['goods_num']){
                Db::name('Cart')->where('goods_id',$goods_id)->delete();
                return array('status'=>-4,'msg'=>'已售馨,本期活动结束');
            }

            $panic_order = Db::name('order_goods')->alias('g')->join('order o','g.order_id = o.order_id')->where("g.prom_type = 5 and g.prom_id = ".$prom_id." and o.user_id = $user_id")->value('o.order_id');
            if($panic_order)
                return array('status'=>-4,'msg'=>'活动限购，您已经参加过本次活动！');
        }
        if($prom_type == 1 || $prom_type == 5)
        {

            // 活动期限购
            $flash_sale = Db::name('panic_buying')->where(['id'=>$prom_id,'start_time'=>['<',time()],'end_time'=>['>',time()],'goods_num'=>['>','buy_num']])->find(); // 限时抢购活动
            if($flash_sale){
                $cart_goods_num = Db::name('Cart')->where($where)->where("goods_id", $goods['goods_id'])->bind($bind)->value('goods_num');
                // 如果购买数量 大于每人限购数量
                if(($goods_num + $cart_goods_num) > $flash_sale['buy_limit'])
                {  
                    $cart_goods_num && $error_msg = "你当前购物车已有 $cart_goods_num 件!";
                    return array('status'=>-4,'msg'=>"每人限购 {$flash_sale['buy_limit']}件 $error_msg",'result'=>'');
                }                        
                // 如果剩余数量 不足 限购数量, 就只能买剩余数量
                if(($flash_sale['goods_num'] - $flash_sale['buy_num']) < $goods_num)
                    return array('status'=>-4,'msg'=>"库存不够,你只能买".($flash_sale['goods_num'] - $flash_sale['buy_num'])."件了.",'result'=>'');
            }

        }
		return array('status'=>1,'msg'=>'添加成功');		
		
	}
  
  /**
   * 查询优惠券券码
   * @param  [type] $code [description]
   * @param  array  $user [description]
   * @return [type]       [description]
   */
  public function getCoupon($code= array(),$user = array()){
    	 $code_price=0;
  		 $codelist=Db::name('CouponList')->where('code',$code)->select();//查询优惠卷码
      foreach ($codelist as $key => $value) {
          if(empty($value)){
              return array('status'=>-9,'msg'=>'优惠券码不存在','result'=>'');
          }
          if($value['order_id'] > 0){
              return array('status'=>-20,'msg'=>'该优惠券已被使用','result'=>'');
          }
          $code = Db::name('Coupon')->where("id", $value['cid'])->find(); // 获取优惠券类型表
          $goddsId=explode(',',$code['goods_id']);
          $cid=$value['cid'];
      }

        if(time() > $code['use_end_time'])
            return array('status'=>-10,'msg'=>'优惠券已经过期','result'=>'');
        if(time() < $code['use_start_time'])
            return array('status'=>-11,'msg'=>'活动还未开启,开启时间'.date('Y-m-d H:i:s',$code['use_start_time']),'result'=>'');
        $where = " selected = 1 and user_id = $user[user_id] ";
        $cart = Db::name('Cart')->where($where)->order('add_time desc , supplier_id asc ')->select();  // 获取购物车商品

        //根据各种条件出来价格数据
        foreach ($cart as $k => $val) {
          foreach($goddsId as $key=>$v){
            if ($cid==$code['id'] and $v==$val['goods_id']) {
              if($code['coupon_type']==0){ //优惠商品
                    if($v==$val['goods_id']){    
                        $goods_code_price =$this->prom_total_price($val['prom_id']);
                    }
                    $total_price+=$val['goods_num'] * $val['goods_price'];//商品总价
                }else{//优惠店铺
                    if($code['supplier_id']==$val['supplier_id']){     

                        $goods_code_price+=$val['goods_num'] * $val['goods_price'];//优惠商品总价
                    }
                    $total_price+=$val['goods_num'] * $val['goods_price'];//商品总价
                }
                  // 判断金额不为空，且该类品总额大于规定额度
                if (!empty($goods_code_price['result'])) {
                  if($goods_code_price['result'] > 0){
                    if($goods_code_price['result']>$code['condition']){
                         $total_price=$total_price-$goods_code_price['result']+($goods_code_price['result']-$code['money']);//待支付价格
                         $code_price=$code['money'];//优惠价格
                    }
                  }else{
                      return array('status'=>-10,'msg'=>'该购物车中没有优惠商品','result'=>'');
                  }

                $result['id']=$code['id'];
                $result['code_price']=$code_price;//优惠价格
                $result['total_price']=$total_price;//待支付价格
                // dump($result);
                return array('status'=>1,'msg'=>'','result'=>$result);
                }
              }
            }
          } 
        }
        

/**
 * 查询优惠卡卡码
 * @param  [type] $code [description]
 * @param  array  $user [description]
 * @return [type]       [description]
 */
    public function getCode($code,$user = array()){
         $code_price=0;
         $codelist=Db::name('CodeList')->where('code',$code)->find();//查询优惠卡码
        if(empty($codelist))
            return array('status'=>-9,'msg'=>'优惠卡码不存在','result'=>'');
        if($codelist['order_id'] > 0||$codelist['uid'] > 0){
            return array('status'=>-20,'msg'=>'该优惠券已被使用','result'=>'');
        }

        $code = Db::name('Code')->where("id", $codelist['cid'])->find(); // 获取优惠券类型表
        if(time() > $code['use_end_time'])
            return array('status'=>-10,'msg'=>'优惠券已经过期','result'=>'');
        if(time() < $code['use_start_time'])
            return array('status'=>-11,'msg'=>'活动还未开启,开启时间'.date('Y-m-d H:i:s',$code['use_start_time']),'result'=>'');
        $where = " selected = 1 and user_id = $user[user_id] ";
        $cart = Db::name('Cart')->where($where)->order('add_time desc , supplier_id asc ')->select();  // 获取购物车商品
      $goods_code_price = 0;
      $total_price = 0;
      if($code['coupon_type']==0){ //优惠商品
            $goddsId=explode(',',$code['goods_id']);
            foreach($cart as $k=>$val){
                foreach($goddsId as $key=>$v){
                    if($v==$val['goods_id']){                   
                        $goods_code_price+=$val['goods_num'] * $val['goods_price'];//优惠商品总价
                    }
                }
                $total_price+=$val['goods_num'] * $val['goods_price'];//商品总价
            }
        // dump($goddsId);die;
        }else{//优惠店铺
            foreach($cart as $k=>$val){
                    if($code['supplier_id']==$val['supplier_id']){                     
                        $goods_code_price+=$val['goods_num'] * $val['goods_price'];//优惠商品总价
                    }
                $total_price+=$val['goods_num'] * $val['goods_price'];//商品总价
            }
        }
        if($goods_code_price>0){
          if($goods_code_price>$code['money']){
                 $total_price=$total_price-$goods_code_price+($goods_code_price-$code['money']);//待支付价格
                 $code_price=$code['money'];//优惠价格
            }else{
                // $goods_code_price=$code['money']-$goods_code_price;
                 $code_price=$goods_code_price;//优惠价格
                 $total_price=$total_price-$goods_code_price;//待支付价格
            }
        }else{
                return array('status'=>-10,'msg'=>'该购物车中没有优惠商品','result'=>'');        
        }        
        $result['code_price']=$code_price;//优惠价格
        $result['total_price']=$total_price;//待支付价格
        return array('status'=>1,'msg'=>'','result'=>$result);      
        
  
  }
    /**
     * 购物车列表 
     * @param type $user   用户
     * @param type $session_id  session_id
     * @param type $selected  是否被用户勾选中的 0 为全部 1为选中  一般没有查询不选中的商品情况
     * $mode 0  返回数组形式  1 直接返回result
     */
    function cartList($user = array() , $session_id = '', $selected = 0,$mode =0,$Choice =0)
    {
        $now_time = time();
        $where = " 1 = 1 ";
        if($Choice == 1)
            $where .= " and selected = 1";
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
                                
        $cart = Db::name('Cart')->where($where)->bind($bind)->order('add_time desc , supplier_id asc ')->select();  // 获取购物车商品
        // $prom_type = Db::name('Cart')->alias('c')->join('goods g','c.goods_id=g.goods_id')->field('g.prom_id,g.prom_type')->where($where)->bind($bind)->select(); // 连表购物车商品的活动状态ID
         // dump($prom_type);
        $coupon_list = Db::name('coupon_list')->where('uid',$user[user_id])->select();

      
		if($cart){
		    // 选中商品所有商品
            $supplier_selected = Db::name('Cart')->where($where)->bind($bind)->field('supplier_id')->group('supplier_id')->select();
            foreach($supplier_selected as $k=> $val){

                $cart_num = Db::name('Cart')->where($where)->where('supplier_id',$val['supplier_id'])->bind($bind)->count();
                $cart_selected = Db::name('Cart')->where($where)->where('supplier_id',$val['supplier_id'])->where('selected','1')->bind($bind)->count();
                if($cart_num == $cart_selected)
                    $su_selected[$val['supplier_id']] = 1;
                else
                    $su_selected[$val['supplier_id']] = 0;

            }
		// 获取图片
		 foreach ($cart as $k=>$val){
			$val['goods_thumb'] = Db::name('goods')->where('goods_id',$val['goods_id'])->Cache(YLT_CACHE_TIME)->value('goods_thumb');
			if($val['prom_type'] == 3){
                $val['prom'] = Db::name('prom_goods')->where('id',$val['prom_id'])->find();
            }
			$carts[] = $val;
		 }
         $anum = $total_price =  $cut_fee = 0;
		 $cartList = [];
    foreach ($carts as $k=>$val){
			
			if(!isset($cartList[$val['supplier_id']]['supplier_id']))

			$cartList[$val['supplier_id']]['supplier_name'] = $val['supplier_name'];
			$cartList[$val['supplier_id']]['supplier_id'] = $val['supplier_id'];
			$cartList[$val['supplier_id']]['is_designer'] = $val['is_designer'];
			$cartList[$val['supplier_id']]['selected'] = $su_selected[$val['supplier_id']];	//	选中状态
			$val['store_count'] = getGoodNum($val['goods_id'],$val['spec_key']);    	// 最多可购买的库存数量
            $cartList[$val['supplier_id']]['shipping_price'] = 0; //商铺商品运费
            $anum += $val['goods_num'];
            $cartList[$val['supplier_id']]['list'][] = $val;

              // 如果要求只计算购物车选中商品的价格 和数量  并且  当前商品没选择 则跳过
              if($selected == 1 && $val['selected'] == 0)
                  continue;
              $cartList[$val['supplier_id']]['total_price'] += ($val['goods_num'] * $val['goods_price']); //商铺商品总价
             
             // 商品活动减免
             if($val['prom_type'] ==3 && $val['prom_id'] > 0){
                 $prom_amount = Db::name('prom_goods')->where('id',$val['prom_id'])->find();
                 if($prom_amount['end_time'] < $now_time)
                        Db::name('cart')->where(['prom_type'=>3,'prom_id'=>$val['prom_id']])->delete();
                 if($prom_amount['type'] == 1){ //单品满减活动
                     $prom_total_price = $val['goods_num'] * $val['goods_price'];
                     if($prom_total_price >= $prom_amount['money']){
                         $order_prom_amount = $prom_amount['expression'];
                     }
                 }
             }
              $order_prom_amount_s += $order_prom_amount ? $order_prom_amount :0;//活动优惠金额
             // 领券商品活动减免
            // if($val['prom_type'] ==4 && $val['prom_id'] > 0){
             //     $prom_amount = Db::name('coupon')->where('id',$val['prom_id'])->find();
             //     if($prom_amount['use_end_time'] < $now_time)
             //        Db::name('cart')->where(['prom_type'=>4,'prom_id'=>$val['prom_id']])->delete();
             //     if($prom_amount['type'] == 3){ //邀请发放-领券
             //         $prom_total_price = $val['goods_num'] * $val['goods_price'];
             //         if($prom_total_price >= $prom_amount['condition']){
             //             $order_prom_amount = $prom_amount['money'];
             //              $i=0;
             //              foreach ($prom_type as $key => $value) {
             //                  if ($val['prom_id']==$value['prom_id']) {
             //                    $i=$i+1;
             //                  }
             //              }
             //              if ($i>1) {
             //                $order_prom_amount_l = $order_prom_amount ? $order_prom_amount :0;//活动优惠金额
             //              }else{
             //                $order_prom_amount_r += $order_prom_amount ? $order_prom_amount :0;//活动优惠金额
             //              }
             //         }
             //     }
            // }
            
             // 领券商品活动减免-新代码
              if($val['prom_type'] ==4 && $val['prom_id'] > 0){
                $prom_amount = Db::name('coupon')->where('id',$val['prom_id'])->find();
                if($prom_amount['use_end_time'] < $now_time)
                Db::name('cart')->where(['prom_type'=>4,'prom_id'=>$val['prom_id']])->delete();
                  if($prom_amount['type'] == 3){ //邀请发放-领券

                  $prom_total_price =$this->prom_total_price($val['prom_id']);
                    if($prom_total_price['result'] >= $prom_amount['condition']){
                      foreach ($coupon_list as $key => $va) {
                        if ($va['cid']==$val['prom_id'] ) {
                        $prom_id[]=$val['prom_id'];
                        $code[]=$va['code'];
                        $lll=array_unique($code);
                        $idd=array_unique($prom_id);
                          foreach ($lll as $key => $v) {
                          $a=$this->coupon_price($v);
                          $c[]=$a;
                          $d=array_slice($c,-count($lll),count($lll));
                            for ($i=0; $i < count($lll); $i++) { 
                              $d[$i+1]['result']+=$d[$i]['result'] ? $d[$i]['result']:0;
                            }
                            $order_prom_amount_l=$d[count($lll)]['result'];
                          }
                        }
                      }
                    }
                  }
                }
        $cartList[$val['supplier_id']]['order_prom_amount'] = $order_prom_amount_s + $order_prom_amount_l + $order_prom_amount_r;//活动优惠金额
			  $order_prom_amount = 0;
        $cut_fee += $val['goods_num'] * $val['market_price'] - $val['goods_num'] * $val['goods_price'];                
        $total_price += $val['goods_num'] * $val['goods_price'];
         }

		}
    // die;
        $total_price = array('total_fee' =>$total_price , 'cut_fee' => $cut_fee,'num'=> $anum,); // 总计        
        setcookie('cn',$anum,null,'/');
        if($mode == 1) return array('cartList' => $cartList, 'total_price' => $total_price);
        return array('status'=>1,'msg'=>'','result'=>array('cartList' =>$cartList, 'total_price' => $total_price));
		

    }

    /**
     * [coupon_price 获取优惠券码的金额]
     * @param  [type] $code [description]
     * @return [type]       [description]
     */
    public function coupon_price($code){
      $coupon=Db::name('coupon')->alias('c')->join('coupon_list l','c.id=l.cid')->where('l.code',$code)->find();
      return array('status'=>1,'msg'=>'','result'=>$coupon['money']);        
    }
    /**
     * [prom_total_price 计算当前优惠券内产品的总额]
     * @param  [type] $prom_id [description]
     * @return [type]          [description]
     */
    public function prom_total_price($prom_id){
      $goods_id=Db::name('Cart')->where('prom_id',$prom_id)->select();
          for ($i=0; $i < count($goods_id); $i++) { 
            $prom_total_price +=$goods_id[$i]['goods_num'] * $goods_id[$i]['member_goods_price'];
          }
      // dump($prom_total_price);
      return array('status'=>1,'msg'=>'','result'=>$prom_total_price);        
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
        $couponlist = Db::name('CouponList')->where("uid", $user_id)->where('cid', $coupon_id)->find(); // 获取用户的优惠券
        if(empty($couponlist)) {
            if($mode == 1) return 0;    
            return array('status'=>1,'msg'=>'','result'=>0);
        }            
        
        $coupon = Db::name('Coupon')->where("id", $couponlist['cid'])->find(); // 获取 优惠券类型表
        $coupon['money'] = $coupon['money'] ? $coupon['money'] : 0;
       
        if($mode == 1) return $coupon['money'];
        return array('status'=>1,'msg'=>$coupon['id'],'result'=>$coupon['money']);        
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
    public function addOrder($user_id,$address_id,$invoice_title,$coupon_id = 0,$car_price,$user_note='',$recommend_code='')
    {
		$new_order_id=0;//父ID
      	$gift_good_id=array('4795','4796','4797');//赠送商品id
      	$gift_good_num=0;//赠送商品数量
        $order_count = Db::name('Order')->where("user_id",$user_id)->where('order_sn', 'like', date('Ymd')."%")->count(); // 查找购物车商品总数量
        if($order_count >= 30) 
            return array('status'=>-9,'msg'=>'为避免刷单，一天只能下30个订单','result'=>'');            
        
         // 0插入订单 order
        $address = Db::name('UserAddress')->where("address_id", $address_id)->find();

		//选中的商品
	   $cart = Db::name('Cart')->where(['user_id'=>$user_id,'selected'=>1])->order('supplier_id asc ')->select();

	   // 活动商品数量过滤
        foreach ($cart AS $key => $value) {
          
          	if($coupon_id<=0){
                    foreach($gift_good_id as $k => $v){
                      if($v==$value['goods_id']){
                       return array('status'=>-9,'msg'=>'赠送商品,需要该活动的优惠卷码','result'=>-1);
                      }                      
                    }
          	}else{
          		  foreach($gift_good_id as $k => $v){
                  	 if($v==$value['goods_id']){
                       $gift_good_num+=$value['goods_num'];
                      }   
                  }            	            
          }
          		
			//团购
            if($value['prom_type']==1 || $value['prom_type']==5){ 
                $prom = Db::name('panic_buying')->where('id',$value['prom_id'])->find();
                if($prom['buy_num'] >= $prom['goods_num']){
                    Db::name('Cart')->where('prom_type',$value['prom_type'])->where('prom_id',$value['prom_id'])->delete();
                    return array('status'=>-9,'msg'=>'已售馨,本期活动结束','result'=>-1);
                }
            }
			//折扣、秒杀
			if($value['prom_type']==2){
                $prom = Db::name('discount_buy')->where('id',$value['prom_id'])->where('is_start',1)->find();
            }
			//满减
			if($value['prom_type']==3){
                $prom = Db::name('prom_goods')->where('id',$value['prom_id'])->find();
            }
      //领券
      if($value['prom_type']==4 ){
        $prom = Db::name('coupon')->where('id',$value['prom_id'])->find();
        if($prom['use_start_time'] > time() || $prom['use_end_time'] < time()){
            Db::name('Cart')->where('prom_type',$value['prom_type'])->where('prom_id',$value['prom_id'])->delete();
            return array('status'=>-9,'msg'=>'活动已结束','result'=>-1);
        }
      }

        if($value['prom_type'] > 0 and $value['prom_type']!=4){
          if($prom['start_time'] > time() || $prom['end_time'] < time()){
              Db::name('Cart')->where('prom_type',$value['prom_type'])->where('prom_id',$value['prom_id'])->delete();
              return array('status'=>-9,'msg'=>'活动已结束','result'=>-1);
            }
        }
			
    }
		$cartList = [];
		// 分商铺订单
        foreach ($cart as $k=>$val){
			if(!isset($cartList[$val['supplier_id']]['supplier_id']))
			$cartList[$val['supplier_id']]['supplier_name'] = $val['supplier_name'];
			$cartList[$val['supplier_id']]['supplier_id'] = $val['supplier_id'];
			$cartList[$val['supplier_id']]['is_designer'] = $val['is_designer'];			
			$val['store_count'] = getGoodNum($val['goods_id'],$val['spec_key']);    	// 最多可购买的库存数量
			$cartList[$val['supplier_id']]['total_price'] += ($val['goods_num'] * $val['goods_price']);
			$cartList[$val['supplier_id']]['shipping_price'] = 0;
			$cartList[$val['supplier_id']]['order_prom_id'] = 0;
			$cartList[$val['supplier_id']]['order_prom_type'] = 0;
			$cartList[$val['supplier_id']]['list'][] = $val;

            // 商品活动减免
            if($val['prom_type'] ==3 && $val['prom_id'] > 0){
                $prom_amount = Db::name('prom_goods')->where('id',$val['prom_id'])->find();
                if($prom_amount['type'] == 1){ //单品满减活动
                    $prom_total_price = $val['goods_num'] * $val['goods_price'];
                    if($prom_total_price >= $prom_amount['money']){
                        $order_prom_amount = $prom_amount['expression'];
						$cartList[$val['supplier_id']]['order_prom_type'] = $val['prom_type'];
						$cartList[$val['supplier_id']]['order_prom_id'] = $val['prom_id'];
                    }
                }
            }
			$cartList[$val['supplier_id']]['order_prom_amount'] += $order_prom_amount ? $order_prom_amount :0;
			$order_prom_amount = 0;

                     	
        }
      if(count($cartList)>1){
          	//生成父单,多个订单
		$order=array(
				'order_sn'=>date('YmdHis').rand(1000,9999), // 订单编号
				'user_id'=>$user_id,
				'order_status'=>0,
				'pay_status'=>0,
				'consignee'=>$address['consignee'],
				'province'=>$address['province'],
				'city'=>$address['city'],
				'district'=>$address['district'],
				'address'=>$address['address'],
				'mobile'=>$address['mobile'],
				'goods_price'=>$car_price['goodsFee'],
				'order_amount'=>$car_price['payables'],
				'total_amount'=>$car_price['payables'],
				'order_prom_type'=>'0',
				'order_prom_id'=>$car_price['order_prom_id'],
				'order_prom_amount'=>$car_price['order_prom_amount'],
				'shipping_price'=>$car_price['postFee'],
				'add_time'=>time(),
				'source'=>$user_note['source'],
				'is_parent'=>'1',
				'supplier_id'=>"",
				'coupon_id'=>$coupon_id,
				'coupon_price'=>$car_price['couponFee'],
				'recommend_code'=>$recommend_code
		);
          $new_order_id =Db::name("Order")->insertGetId($order);
          if(!$new_order_id)
            return array('status'=>-8,'msg'=>'添加订单失败','result'=>NULL);
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
                'order_amount'     =>($val['total_price'] - $val['order_prom_amount']-$car_price['couponFee']),//'应付款金额',
                'add_time'         =>time(), // 下单时间
                'is_parent'=>'0',
				'parent_id'=>$new_order_id,
                'order_prom_type'  =>$val['order_prom_type'],//'订单优惠活动id',
                'order_prom_id'    =>$val['order_prom_id'],//'订单优惠活动id',
                'order_prom_amount'=>$val['order_prom_amount'],//'订单优惠活动优惠了多少钱',
                'user_note'        =>$user_note['user_note_'.$val['supplier_id'].''], // 用户下单备注
                'source'           =>$user_note['source'],
				'supplier_id'	   => $val['supplier_id'],
				'supplier_name'	   => $val['supplier_name'],
				'is_designer'	   => $val['is_designer'],
				'recommend_code'   => $recommend_code //订单推荐人
        );
        $data['order_id'] = $order_id = Db::name("Order")->insertGetId($data);

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
				   $data2['goods_amount']		= $va['goods_num'] * $va['goods_price'];
				   $data2['goods_price']        = $va['goods_price']; // 商品价
				   $data2['spec_key']           = $va['spec_key']; // 商品规格
				   $data2['spec_key_name']      = $va['spec_key_name']; // 商品规格名称
				   $data2['member_goods_price'] = $va['member_goods_price']; // 会员折扣价
				   $data2['discount_price']         = $goods['cost_price']; // 成本价
				   $data2['give_integral']      = $goods['give_integral']; // 购买商品赠送积分         
				   $data2['prom_type']          = $va['prom_type']; // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
				   $data2['prom_id']            = $va['prom_id']; // 活动id
				   $data2['commission_price']	= $va['commission_price']; //推广佣金
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
            update_pay_status($data['order_sn']);
        }      
      
      if($new_order_id==0){
          $new_order_id=$order_id;
        }
        
        // 2修改优惠券状态  
        if($coupon_id > 0){
        	$data3['uid'] = $user_id;
        	$data3['order_id'] = $order_id;
        	$data3['use_time'] = time();
        	//Db::name('CouponList')->where("id", $coupon_id)->update($data3);// 优惠券
               // $cid = Db::name('CouponList')->where("id", $coupon_id)->getField('cid');// 优惠券
                //Db::name('Coupon')->where("id", $cid)->setInc('use_num'); // 优惠券的使用数量加一
          Db::name('CodeList')->where("id", $coupon_id)->update($data3);// 优惠券码
                $cid = Db::name('CodeList')->where("id", $coupon_id)->getField('cid');// 优惠券码
                Db::name('Code')->where("id", $cid)->setInc('use_num'); // 优惠券码的使用数量加一
        }

        return array('status'=>1,'msg'=>'提交订单成功','result'=>$new_order_id); // 返回新增的订单id        
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
        Db::name('cart')->where("session_id", $session_id)->update(array('user_id'=>$user_id));
                
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
     * 获取店铺商品运费
     * @param type $goods_id 商品id
     * @param type $key  库存 key
     */

    private function ShippingPrice($supplier_id,$user_id=0){

        $sql = "SELECT sum(g.is_free_shipping) as is_free_shipping FROM ylt_cart AS c INNER JOIN ylt_goods AS g ON g.goods_id = c.goods_id WHERE c.supplier_id =$supplier_id and c.user_id = $user_id and c.selected = 1";
        $res =  \think\Db::query($sql);

        if($res[0]['is_free_shipping'] > 0 )
            return '0'; //包邮
        else
            $sql = "SELECT g.shipping_price FROM ylt_cart AS c INNER JOIN ylt_goods AS g ON g.goods_id = c.goods_id WHERE c.supplier_id =$supplier_id and c.user_id = $user_id and c.selected = 1 order by g.shipping_price asc limit 0,1";
            $res = \think\Db::query($sql);
        return  $res[0]['shipping_price'];
    }


    /**
     * 设置用户ID
     * @param $user_id
     */
    public function setUserId($user_id)
    {
        $this->user_id = $user_id;
    }

    

}