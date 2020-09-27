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
     * @param type $goods_id 商品id
     * @param type $goods_num 商品数量
     * @param type $goods_spec 选择规格
     * @param type $user_id 用户id
     * @param type $brokerage 是否有佣金
     */
    function addCart($goods_id, $goods_num, $goods_spec, $session_id, $user_id = 0, $cart_id = 0,$brokerage = 0,$selected = 1,$is_share = 0)
    {   
        // // 抗疫活动免费领取判断
        // if ($goods_spec['抗疫救灾物资']==5937 ||$goods_spec['抗疫救灾物资']==5938 ||$goods_spec['抗疫救灾物资']==5939 ) {      //领取10个口罩
        //     if ($goods_spec['抗疫救灾物资']==5937) {      //领取10个口罩
        //         $find = Db::name('goods_apply_list')->where(['user_id'=>$user_id,'type'=>2])->order('id desc')->find();             //查询申请记录
        //         if ($goods_num > 1 || Db::name('cart')->where('user_id',$user_id)->where(['goods_id'=>$goods_id,'goods_price'=>0,'goods_num'=>1])->find()) {
        //             exit(json_encode(array('status'=>-1,'msg'=>"活动限领一件,请查看购物车或订单")));
        //         }
        //     }elseif($goods_spec['抗疫救灾物资']==5938){  //领取3个口罩
        //         $find = Db::name('goods_apply_list')->where(['user_id'=>$user_id,'type'=>1])->order('id desc')->find();             //查询申请记录
        //         if ($goods_num > 1  || Db::name('cart')->where('user_id',$user_id)->where(['goods_id'=>$goods_id,'goods_price'=>0,'goods_num'=>1])->find()) {
        //             exit(json_encode(array('status'=>-1,'msg'=>"活动限领一件,请查看购物车或订单")));
        //         }
        //     }elseif($goods_spec['抗疫救灾物资']==5939){  //领取1个测温枪 
        //         //查询申请记录
        //         $find = Db::name('goods_apply_list')->where(['user_id'=>$user_id,'type'=>3])->find();
        //         $count = Db::name('goods_apply_list')->where(['user_id'=>$user_id,'type'=>3,'is_check'=>1,'is_get'=>2])->count();
        //         $cart_count = Db::name('cart')->where('user_id',$user_id)->where(['goods_id'=>$goods_id,'goods_price'=>0])->field('goods_num')->find();
        //         $arr = $count - $cart_count['goods_num'];
        //         if ($goods_num > $arr) {
        //             if ($arr>0) {
        //                 exit(json_encode(array('status'=>-1,'msg'=>"剩余领取件数为".$arr."件")));
        //             }else{
        //                 exit(json_encode(array('status'=>-1,'msg'=>"剩余领取件数为0件,请查看购物车或订单")));
        //             }
        //         }
        //     }
        //     if (empty($find)) {
        //         exit(json_encode(array('status'=>-1,'msg'=>"没有申请记录")));
        //     }elseif($find['is_check']!=1){
        //         exit(json_encode(array('status'=>-1,'msg'=>"申请审核中，请耐心等待")));
        //     }elseif($find['is_get'] ==1){
        //         exit(json_encode(array('status'=>-1,'msg'=>"已有领取记录，请等待收货")));
        //     }
        // }
        // // 抗疫活动结束可删
        
        Db::name('Cart')->where(['selected'=>2,'user_id'=>$user_id])->delete();  //删除未支付的selected=2商品
        $goods = Db::name('Goods')->where("goods_id", $goods_id)->find(); // 找出这个商品
        $specGoodsPriceList = Db::name('goods_price')->where("goods_id", $goods_id)->column("key,key_name,price,store_count,sku,quantity"); // 获取商品对应的规格价钱 库存 条码
        $where = " session_id = :session_id ";
        $bind['session_id'] = $session_id;
        $now_time = time();
        $user_id = $user_id ? $user_id : 0;
        if ($user_id) {
            $where .= "  or user_id= :user_id ";
            $bind['user_id'] = $user_id;
        }
        $catr_count = Db::name('Cart')->where($where)->bind($bind)->count(); // 查找购物车商品总数量
        if ($catr_count >= 18){
            exit(json_encode(array('status' => -9, 'msg' => '购物车最多只能放18种商品')));
        }
        if (!empty($specGoodsPriceList) && empty($goods_spec)){// 有商品规格 但是前台没有传递过来
            exit(json_encode(array('status' => -1, 'msg' => '必须传递商品规格')));
        } 
        if ($goods_num <= 0){
            exit(json_encode(array('status' => -2, 'msg' => '购买商品数量不能为0')));
        }
        if (empty($goods) || $goods['is_on_sale'] == 0){
            exit(json_encode(array('status' => -3, 'msg' => '商品已下架')));
        }
        if (($goods['store_count'] < $goods_num)){
            exit(json_encode(array('status' => -4, 'msg' => '商品库存不足')));
        }
        if ($goods['prom_type'] > 0 && $user_id == 0){
            exit(json_encode(array('status' => -101, 'msg' => '购买活动商品必须先登录')));
        }

        // 活动数据判断
        if ($goods['prom_type'] == 1 || $goods['prom_type'] == 5) {
            $result = $this->addPromCart($goods_id, $goods_num, $goods['prom_type'], $goods['prom_id'], $user_id);
            if ($result['status'] != 1)
                exit(json_encode(array('status' => $result['status'], 'msg' => $result['msg'])));
        }
        if ($goods_spec) {
            foreach ($goods_spec as $key => $val){ // 处理商品规格
                $spec_item[] = $val; // 所选择的规格项  
            }
        }
        $quantity = 1; //起售量
        if (!empty($spec_item)) {
            sort($spec_item);
            $spec_key = implode('_', $spec_item);
            if ($specGoodsPriceList[$spec_key]['store_count'] < $goods_num){
                exit(json_encode(array('status' => -5, 'msg' => '该规格库存不足，请选择其他规格')));
            }
                $spec_price = $specGoodsPriceList[$spec_key]['price']; // 获取规格指定的价格
                $quantity = $specGoodsPriceList[$spec_key]['quantity']; // 获取规格指定的起售量
        }
        $where = " goods_id = :goods_id and spec_key = :spec_key"; // 查询购物车是否已经存在这商品
        if ($spec_key) {
            $cart_bind['spec_key'] = $spec_key;
        } else {
            $cart_bind['spec_key'] = '';
        }
        $cart_bind['goods_id'] = $goods_id;
        if ($user_id > 0) {
            $where .= " and (session_id = :session_id or user_id = :user_id) ";
            $cart_bind['session_id'] = $session_id;
            $cart_bind['user_id'] = $user_id;
        } else {
            $where .= " and  session_id = :session_id ";
            $cart_bind['session_id'] = $session_id;
        }
        $catr_goods = Db::name('Cart')->where($where)->bind($cart_bind)->find(); // 查找购物车是否已经存在该商品
        $price = $spec_price ? $spec_price : $goods['shop_price']; // 如果商品规格没有指定价格则用商品原始价格

        // 商品参与促销
        if ($goods['prom_type'] > 0) {
            $prom = get_goods_promotion($goods_id, $user_id);
            if ($prom != 1) { // 活动中
                if ($prom['start_time'] > $now_time || $prom['end_time'] < $now_time) {
                    $goods['prom_type'] = 0;
                    $goods['prom_id'] = 0;
                } else {
                    if ($goods['prom_type'] == 7 && $is_share == 0) {   
                        $price = $price;        //拼单活动的商品非拼单购买时不享受活动价格
                    }else{
                        $price = $prom['price'];
                    }
                    $goods['prom_type'] = $prom['prom_type'];
                    $goods['prom_id'] = $prom['prom_id'];
                }
            }
        }

        $data = array(
            'user_id' => $user_id,   // 用户id
            'session_id' => $session_id,   // sessionid
            'goods_id' => $goods_id,   // 商品id
            'goods_sn' => $goods['goods_sn'],   // 商品货号
            'goods_name' => $goods['goods_name'],   // 商品名称
            'market_price' => $goods['market_price'],   // 市场价
            'goods_price' => $price,  // 购买价
            'cost_price' => $goods['cost_price'],  // 成本价
            'member_goods_price' => $price,  // 会员折扣价 默认为 购买价
            'goods_num' => $goods_num, // 购买数量
            'spec_key' => "{$spec_key}", // 规格key
            'spec_key_name' => "{$specGoodsPriceList[$spec_key]['key_name']}", // 规格 key_name
            'sku' => "{$specGoodsPriceList[$spec_key]['sku']}", // 商品条形码
            'add_time' => time(), // 加入购物车时间
            'prom_type' => $goods['prom_type'],   // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
            'prom_id' => $goods['prom_id'],   // 活动id
            'supplier_id' => $goods['supplier_id'],   // 入驻商ID
            'supplier_name' => $goods['supplier_name'],
            'is_designer' => $goods['is_designer'],
            'quantity' => $quantity,         //商品起订量
            'commission_price' => $goods['commission_price'],    //佣金
            'goods_thumb' => $goods['goods_thumb'],   //商品缩略图
            'red_goods_id' => $goods['red_goods_id'],         //红礼ID
            'red_supplier_id' => $goods['red_supplier_id'],   //红礼商家ID
            'is_group' => $goods['is_group'],                 //是否红礼组合礼包
            'red_cost_price' => $goods['red_cost_price'],     //商家供货价/红礼采购单价
            'selected' => $selected,   //选择状态2是详情页立即购买
            'is_free_shipping' => $goods['is_free_shipping'],   //商品是否包邮
            'is_share' => $is_share,   //商品发起拼单
        );  
        if ($brokerage == 1) {
            $data['brokerage'] = 1;
        }

        // 如果商品购物车已经存在
        if ($catr_goods and $data['selected']!=2) {
            // //判断端午商品规格数量
            // if($spec_key==5454){
            //     if (($goods_num)< 100){
            //         return array('status' => -1, 'msg' => '商品规格数量小于100套', 'result' => '');
            //     }
            // }
            // 如果购物车的已有数量加上 这次要购买的数量  大于  库存输  则不再增加数量
            if (($catr_goods['goods_num'] + $goods_num) > $goods['store_count']){
                $goods_num = 0;
            }
            if ($goods['prom_type'] != 6) {
                $update = [
                    'goods_num' => ['exp','goods_num+'."$goods_num".''],        // 数量相加
                ];
            }else{
                exit(json_encode(array('status' => -1, 'msg' => '活动商品限购数量，请进入购物车处理订单')));
            }
            $result = Db::name('Cart')->where("id", $catr_goods['id'])->update($update); 
            $cart_count = cart_goods_num($user_id, $session_id);            // 查找购物车数量
            setcookie('cn', $cart_count, null, '/');
            exit(json_encode(array('status' => 1, 'msg' => '成功加入购物车', 'result' => $cart_count)));
        }else{
            // //判断端午商品规格数量
            // if($spec_key==5454){
            //     if (($goods_num)< 100){
            //         return array('status' => -1, 'msg' => '商品规格数量小于100套', 'result' => '');
            //     }
            // }
            $insert_id = Db::name('Cart')->insert($data);
            if (!empty($cart_id) && $insert_id) {
                Db::name('Cart')->where('id', $cart_id)->delete();  //购物车直接修改规格
            }

            $cart_count = cart_goods_num($user_id, $session_id); // 查找购物车数量
            setcookie('cn', $cart_count, null, '/');
            exit(json_encode(array('status' => 1, 'msg' => '成功加入购物车', 'result' => $cart_count)));
        }
        $cart_count = cart_goods_num($user_id, $session_id); // 查找购物车数量
        exit(json_encode(array('status' => -5, 'msg' => '加入购物车失败', 'result' => $cart_count)));
    }

    /**
     * 从列表页面直接加入购物车方法
     * @param type $goods_id 商品id
     * @param type $goods_num 商品数量
     * @param type $goods_spec 选择规格
     * @param type $user_id 用户id
     * @param type $brokerage 是否有佣金
     */
    function addCartFu($goods_id, $goods_num, $spec_id, $session_id, $user_id = 0, $cart_id = 0,$brokerage = 0,$selected=1,$is_share=0)
    {
        Db::name('Cart')->where(['selected'=>2,'user_id'=>$user_id])->where('add_time','<',time()-600)->delete();  //删除未支付的selected=2商品
        $goods = Db::name('Goods')->where("goods_id", $goods_id)->find(); // 找出这个商品
        $specGoodsPriceList = Db::name('goods_price')->where("goods_id", $goods_id)->column("key,key_name,price,store_count,sku"); // 获取商品对应的规格价钱 库存 条码
        $where = " session_id = :session_id ";
        $bind['session_id'] = $session_id;
        $now_time = time();
        $user_id = $user_id ? $user_id : 0;
        if ($user_id) {
            $where .= "  or user_id= :user_id ";
            $bind['user_id'] = $user_id;
        }
        $catr_count = Db::name('Cart')->where($where)->bind($bind)->count(); // 查找购物车商品总数量
        if ($catr_count >= 18){
            exit(json_encode(array('status' => -9, 'msg' => '购物车最多只能放18种商品')));
        }
        if (!empty($specGoodsPriceList) && empty($spec_id)){// 有商品规格 但是前台没有传递过来
            exit(json_encode(array('status' => -1, 'msg' => '必须传递商品规格')));
        } 
        if ($goods_num <= 0){
            exit(json_encode(array('status' => -2, 'msg' => '购买商品数量不能为0')));
        }
        if (empty($goods) || $goods['is_on_sale'] == 0){
            exit(json_encode(array('status' => -3, 'msg' => '商品已下架')));
        }
        if (($goods['store_count'] < $goods_num)){
            exit(json_encode(array('status' => -4, 'msg' => '商品库存不足')));
        }
        if ($goods['prom_type'] > 0 && $user_id == 0){
            exit(json_encode(array('status' => -101, 'msg' => '购买活动商品必须先登录')));
        }

        // 活动数据判断
        if ($goods['prom_type'] == 1 || $goods['prom_type'] == 5) {
            $result = $this->addPromCart($goods_id, $goods_num, $goods['prom_type'], $goods['prom_id'], $user_id);
            if ($result['status'] != 1){
                exit(json_encode(array('status' => $result['status'], 'msg' => $result['msg'])));
            }
        }
        $quantity = 1; //起售量
        //判断是否有规格ID
        if (!empty($spec_id)) {
            if ($specGoodsPriceList[$spec_id]['store_count'] < $goods_num){
                exit(json_encode(array('status' => -5, 'msg' => '商品库存不足')));
                $spec_price = $specGoodsPriceList[$spec_id]['price']; // 获取规格指定的价格
                $quantity = $specGoodsPriceList[$spec_id]['quantity']; // 获取规格指定的起售量
            }
        }
        $spec_key=$spec_id;
        $where = " goods_id = :goods_id and spec_key = :spec_key"; // 查询购物车是否已经存在这商品
        if ($spec_id) {
            $cart_bind['spec_key'] = $spec_id;
        } else {
            $cart_bind['spec_key'] = '';
        }
        $cart_bind['goods_id'] = $goods_id;
        if ($user_id > 0) {
            $where .= " and (session_id = :session_id or user_id = :user_id) ";
            $cart_bind['session_id'] = $session_id;
            $cart_bind['user_id'] = $user_id;
        } else {
            $where .= " and  session_id = :session_id ";
            $cart_bind['session_id'] = $session_id;
        }
        $catr_goods = Db::name('Cart')->where($where)->bind($cart_bind)->find(); // 查找购物车是否已经存在该商品
        $price = $spec_price ? $spec_price : $goods['shop_price']; // 如果商品规格没有指定价格则用商品原始价格

        // 商品参与促销
        if ($goods['prom_type'] > 0) {
            $prom = get_goods_promotion($goods_id, $user_id);
            if ($prom != 1) { // 活动中
                if ($prom['start_time'] > $now_time || $prom['end_time'] < $now_time) {
                    $goods['prom_type'] = 0;
                    $goods['prom_id'] = 0;
                } else {
                    $price = $prom['price'];
                    $goods['prom_type'] = $prom['prom_type'];
                    $goods['prom_id'] = $prom['prom_id'];
                }

            }

        }

        $data = array(
            'user_id' => $user_id,   // 用户id
            'session_id' => $session_id,   // sessionid
            'goods_id' => $goods_id,   // 商品id
            'goods_sn' => $goods['goods_sn'],   // 商品货号
            'goods_name' => $goods['goods_name'],   // 商品名称
            'market_price' => $goods['market_price'],   // 市场价
            'goods_price' => $price,  // 购买价
            'member_goods_price' => $price,  // 会员折扣价 默认为 购买价
            'goods_num' => $goods_num, // 购买数量
            'spec_key' => "{$spec_id}", // 规格key
            'spec_key_name' => "{$specGoodsPriceList[$spec_id]['key_name']}", // 规格 key_name
            'sku' => "{$specGoodsPriceList[$spec_id]['sku']}", // 商品条形码
            'add_time' => time(), // 加入购物车时间
            'prom_type' => $goods['prom_type'],   // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
            'prom_id' => $goods['prom_id'],   // 活动id
            'supplier_id' => $goods['supplier_id'],   // 入驻商ID
            'supplier_name' => $goods['supplier_name'],
            'is_designer' => $goods['is_designer'],
            'quantity' => $quantity,        //商品起售量
            'commission_price' => $goods['commission_price'], //佣金
            'goods_thumb' => $goods['goods_thumb'],   //商品缩略图
            'red_goods_id' => $goods['red_goods_id'],         //红礼ID
            'red_supplier_id' => $goods['red_supplier_id'],   //红礼商家ID
            'is_group' => $goods['is_group'],                 //是否红礼组合礼包
            'red_cost_price' => $goods['red_cost_price'],     //商家供货价/红礼采购单价
            'selected' => $selected,   //选择状态2是详情页立即购买
            'is_free_shipping' => $goods['is_free_shipping'],   //商品是否包邮
            'is_share' => $is_share,   //商品发起拼单
        );
        if ($brokerage == 1) {
            $data['brokerage'] = 1;
        }
        // 如果商品购物车已经存在
        if ($catr_goods and $data['selected']!=2) {
            // //判断端午商品规格数量
            // if($spec_id==5454){
            //     if (($goods_num)< 100){
            //         return array('status' => -1, 'msg' => '商品规格数量小于100套', 'result' => '');
            //     }
            // }
            // 如果购物车的已有数量加上 这次要购买的数量  大于  库存输  则不再增加数量
            if (($catr_goods['goods_num'] + $goods_num) > $goods['store_count'])
                $goods_num = 0;
            $update = [
                'goods_num' => ['exp','goods_num+'."$goods_num".''],        // 数量相加
            ];
            $result = Db::name('Cart')->where("id", $catr_goods['id'])->update($update); 
            $cart_count = cart_goods_num($user_id, $session_id); // 查找购物车数量
            setcookie('cn', $cart_count, null, '/');
            exit(json_encode(array('status' => 1, 'msg' => '成功加入购物车', 'result' => $cart_count)));
        } else {
            // //判断端午商品规格数量
            // if($spec_id==5454){
            //     if (($goods_num)< 100){
            //         return array('status' => -1, 'msg' => '商品规格数量小于100套', 'result' => '');
            //     }
            // }
            Db::name('Cart')->where(['user_id'=>$user_id,'selected'=>2])->delete();  //删除之前的立即购买
            $insert_id = Db::name('Cart')->insert($data);
            if (!empty($cart_id) && $insert_id) {
                Db::name('Cart')->where('id', $cart_id)->delete();  //购物车直接删除旧规格
            }

            $cart_count = cart_goods_num($user_id, $session_id); // 查找购物车数量
            setcookie('cn', $cart_count, null, '/');
            exit(json_encode(array('status' => 1, 'msg' => '成功加入购物车', 'result' => $cart_count)));
        }
        $cart_count = cart_goods_num($user_id, $session_id); // 查找购物车数量
        exit(json_encode(array('status' => -11, 'msg' => '加入购物车失败', 'result' => $cart_count)));
    }

    
    /**
     * 购物车列表
     * @param type $user 用户
     * @param type $goods_id 商品ID
     * @param type $type 1,5
     * $mode 0  返回数组形式  1 直接返回result
     */
    private function addPromCart($goods_id, $goods_num, $prom_type, $prom_id, $user_id)
    {

        //限量团购 不能超过购买数量

        if ($prom_type == 5) {
            $panic_buying = Db::name('panic_buying')->where(['id' => $prom_id])->find();

            if ($panic_buying['start_time'] > time())
                exit(json_encode(array('status' => -4, 'msg' => '抢购尚未开始')));

            if ($panic_buying['end_time'] < time())
                exit(json_encode(array('status' => -4, 'msg' => '活动已结束')));

            if ($panic_buying['buy_num'] >= $panic_buying['goods_num']) {
                Db::name('Cart')->where('goods_id', $goods_id)->delete();
                exit(json_encode(array('status' => -4, 'msg' => '已售馨,本期活动结束')));
            }

            $panic_order = Db::name('order_goods')->alias('g')->join('order o', 'g.order_id = o.order_id')->where("g.prom_type = 5 and g.prom_id = " . $prom_id . " and o.user_id = $user_id")->value('o.order_id');
            if ($panic_order)
                exit(json_encode(array('status' => -4, 'msg' => '活动限购，您已经参加过本次活动！')));
        }
        if ($prom_type == 1 || $prom_type == 5) {

            // 活动期限购
            $flash_sale = Db::name('panic_buying')->where(['id' => $prom_id, 'start_time' => ['<', time()], 'end_time' => ['>', time()], 'goods_num' => ['>', 'buy_num']])->find(); // 限时抢购活动
            if ($flash_sale) {
                $cart_goods_num = Db::name('Cart')->where($where)->where("goods_id", $goods['goods_id'])->bind($bind)->value('goods_num');
                // 如果购买数量 大于每人限购数量
                if (($goods_num + $cart_goods_num) > $flash_sale['buy_limit']) {
                    $cart_goods_num && $error_msg = "你当前购物车已有 $cart_goods_num 件!";
                    exit(json_encode(array('status' => -4, 'msg' => "每人限购 {$flash_sale['buy_limit']}件 $error_msg")));
                }
                // 如果剩余数量 不足 限购数量, 就只能买剩余数量
                if (($flash_sale['goods_num'] - $flash_sale['buy_num']) < $goods_num)
                    exit(json_encode(array('status' => -4, 'msg' => "库存不够,你只能买" . ($flash_sale['goods_num'] - $flash_sale['buy_num']) . "件了.")));
            }

        }
        exit(json_encode(array('status' => 1, 'msg' => '添加成功')));
    }

    /**
     * 查询优惠券券码
     * @param  [type] $code [description]
     * @param  array $user [description]
     * @return [type]       [description]
     */
    public function getCoupon($code = array(), $user = array())
    {   
        //注释的代码需要orderconfirm使用新页面（带有可选优惠券功能）时启用
        $code_price = 0;
        $CouponList = Db::name('CouponList')->where('code', $code)->select();//查询优惠卷码
        foreach ($CouponList as $key => $value) {
            // if (empty($value)) {
            //     return array('status' => -9, 'msg' => '优惠券码不存在', 'result' => '');
            // }
            // if ($value['order_id'] > 0) {
            //     return array('status' => -20, 'msg' => '该优惠券已被使用', 'result' => '');
            // }
            $Coupon = Db::name('Coupon')->where("id", $value['cid'])->find(); // 获取优惠券类型表
            $goddsId = explode(',', $Coupon['goods_id']);
            $cid = $value['cid'];
            $coupon_id = $value['id'];
        }
        // if (time() > $Coupon['use_end_time'])
            // return array('status' => -10, 'msg' => '优惠券已经过期', 'result' => '');
        // if (time() < $Coupon['use_start_time'])
            // return array('status' => -11, 'msg' => '活动还未开启,开启时间' . date('Y-m-d H:i:s', $Coupon['use_start_time']), 'result' => '');
        $where = " selected = 1 and user_id = $user[user_id] ";
        $wheres = " selected = 2 and user_id = $user[user_id] ";
        $cart = Db::name('Cart')->where($wheres)->order('add_time desc , supplier_id asc ')->select();  // 获取购物车商品
        if (empty($cart)) {
            $cart = Db::name('Cart')->where($where)->order('add_time desc , supplier_id asc ')->select();  // 获取购物车商品
        }
        //根据各种条件出来价格数据
        foreach ($cart as $k => $val) {
            foreach ($goddsId as $key => $v) {
                if ($cid == $Coupon['id'] and $v == $val['goods_id']) {
                    $prom_id = explode(',', $val['prom_id']);
                    for ($i = 0; $i < count($prom_id); $i++) {
                        $goods_code_price = $this->prom_total_price($prom_id[$i], $user, $session_id, 1, 1, 1);
                    }
                    $total_price += $val['goods_num'] * $val['goods_price'];//商品总价
                    // 判断金额不为空，且该类品总额大于规定额度
                    if (!empty($goods_code_price['result'])) {
                        if ($goods_code_price['result'] > 0) {
                            if ($goods_code_price['result'] >= $Coupon['condition']) {  //4.26改动
                                $total_price = $total_price - $goods_code_price['result'] + ($goods_code_price['result'] - $Coupon['money']);//待支付价格
                                $code_price = $Coupon['money'];//优惠价格
                            }
                        } else {
                            exit(json_encode(array('status' => -10, 'msg' => '该购物车中没有优惠商品')));
                        }
                        $result['coupon_id']    = $coupon_id;
                        $result['id']           = $Coupon['id'];
                        $result['code_price']   = $code_price;//优惠价格
                        $result['total_price']  = $total_price;//待支付价格
                        return array('status' => 1, 'msg' => '', 'result' => $result);
                    }
                }
            }
        }
    }

    /**
     * [prom_total_price 计算当前优惠券内产品的总额]
     * @param  [type] $prom_id [description]
     * @return [type]          [description]
     */
    public function prom_total_price($prom_id, $user = array(), $session_id = '', $selected = 0, $mode = 0, $Choice = 0)
    {
        if ($prom_id!=0) {
            
            $where = " 1 = 1 ";
            $wheres = " 1 = 1 ";
            if ($Choice == 1){
                $where .= " and selected = 1";
                $wheres .= " and selected = 2";
            }
            $bind = array();
            if ($user[user_id])// 如果用户已经登录则按照用户id查询
            {
                $where .= " and user_id = $user[user_id] ";
                $wheres .= " and user_id = $user[user_id] ";
                // 给用户计算会员价 登录前后不一样
            } else {
                $where .= " and session_id = :session_id";
                $wheres .= " and session_id = :session_id";
                $bind['session_id'] = $session_id;
                $user[user_id] = 0;
            }

            $cart = Db::name('Cart')->where($wheres)->where('find_in_set(:prom_id,prom_id)', ['prom_id' => $prom_id])->bind($bind)->order('add_time desc , supplier_id asc ')->select();  // 获取购物车商品
            if (empty($cart)) {
                $cart = Db::name('Cart')->where($where)->where('find_in_set(:prom_id,prom_id)', ['prom_id' => $prom_id])->bind($bind)->order('add_time desc , supplier_id asc ')->select();  // 获取购物车商品
            }
            for ($i = 0; $i < count($cart); $i++) {
                $prom_total_price += $cart[$i]['goods_num'] * $cart[$i]['member_goods_price'];
            }

        }
        return array('status' => 1, 'msg' => '', 'result' => $prom_total_price);
    }

    /**
     * 查询优惠卡卡码
     * @param  [type] $code [description]
     * @param  array $user [description]
     * @return [type]       [description]
     */
    public function getCode($code, $user = array())
    {
        $code_price = 0;
        $codelist = Db::name('CodeList')->where('code', $code)->find();//查询优惠卡码
        if (empty($codelist))
            exit(json_encode(array('status' => -9, 'msg' => '礼品卡码不存在')));
        if ($codelist['order_id'] > 0 || $codelist['use_time'] > 0) {
            exit(json_encode(array('status' => -20, 'msg' => '该礼品卡已被使用')));
        }

        $code = Db::name('Code')->where("id", $codelist['cid'])->find(); // 获取优惠券类型表
        if (time() > $code['use_end_time'])
            exit(json_encode(array('status' => -10, 'msg' => '礼品卡已经过期')));
        if (time() < $code['use_start_time'])
            exit(json_encode(array('status' => -11, 'msg' => '活动还未开启,开启时间' . date('Y-m-d H:i:s', $code['use_start_time']),)));
        $wheres = " selected = 2 and user_id = $user[user_id] ";
        $where = " selected = 1 and user_id = $user[user_id] ";
        $cart = Db::name('Cart')->where($wheres)->order('add_time desc , supplier_id asc ')->select();  // 获取购物车商品
        if (empty($cart)) {  //先查询是否为立即购买的商品
            $cart = Db::name('Cart')->where($where)->order('add_time desc , supplier_id asc ')->select();  // 获取购物车商品
        }
        $goods_code_price = 0;
        $total_price = 0;
        if ($code['coupon_type'] == 0) { //优惠商品
            $goddsId = explode(',', $code['goods_id']);
            foreach ($cart as $k => $val) {
                foreach ($goddsId as $key => $v) {
                    if ($v == $val['goods_id']) {
                        $goods_code_price += $val['goods_num'] * $val['goods_price'];//优惠商品总价
                    }
                }
                $total_price += $val['goods_num'] * $val['goods_price'];//商品总价
            }
        } else {//优惠店铺
            foreach ($cart as $k => $val) {
                if ($code['supplier_id'] == $val['supplier_id']) {
                    $goods_code_price += $val['goods_num'] * $val['goods_price'];//优惠商品总价
                }
                $total_price += $val['goods_num'] * $val['goods_price'];//商品总价
            }
        }
        if ($goods_code_price > 0) {
            if ($goods_code_price > $code['money']) {
                $total_price = $total_price - $goods_code_price + ($goods_code_price - $code['money']);//待支付价格
                $code_price = $code['money'];//优惠价格
            }else{
                // $goods_code_price=$code['money']-$goods_code_price;
                $code_price = $goods_code_price;//优惠价格
                $total_price = $total_price - $goods_code_price;//待支付价格
            }
        } else {
            exit(json_encode(array('status' => -10, 'msg' => '该购物车中没有礼品卡优惠商品')));
        }
        $result['code_price']   = $code_price;//优惠价格
        $result['total_price']  = $total_price;//待支付价格
        $result['goods_id']     = $goddsId;//优惠商品
        session('CodeMoney',$code_price);
        return array('status' => 1, 'msg' => '', 'result' => $result);
    }

    /**
     * 购物车列表
     * @param type $user 用户
     * @param type $session_id session_id
     * @param type $selected 是否被用户勾选中的 0 为全部 1为选中  一般没有查询不选中的商品情况
     * $mode 0  返回数组形式  1 直接返回result
     * $Choice  提交订单时将selected为1的条件加入查询
     */
    function cartList($user = array(), $session_id = '', $selected = 0, $mode = 0, $Choice = 0,$goods_id='')
    {
        $now_time = time();
        $where = " 1 = 1 ";
        if ($Choice == 1){
            if ($selected == 2 and !empty($goods_id)) {
                $where .= " and selected = 2 and goods_id = $goods_id";   //立即购买的商品ID 只查询一条
                $limit ='1';
            }else{
                $where .= " and selected = 1";
            }
        }
        $bind = array();
        if ($user[user_id])// 如果用户已经登录则按照用户id查询
        {
            $where .= " and user_id = $user[user_id] ";
            // 给用户计算会员价 登录前后不一样
        } else {
            $where .= " and session_id = :session_id";
            $bind['session_id'] = $session_id;
            $user[user_id] = 0;
        }

        $cart = Db::name('Cart')->where($where)->bind($bind)->order('add_time desc , supplier_id asc ')->limit($limit)->select();  // 获取购物车商品
        $coupon_list = Db::name('coupon_list')->where('uid', $user[user_id])->where('order_id = 0')->select();

        if ($cart) {
            // 选中商品所有商品
            $supplier_selected = Db::name('Cart')->where($where)->bind($bind)->field('supplier_id')->limit($limit)->group('supplier_id')->select();
            foreach ($supplier_selected as $k => $val) {
                $cart_num = Db::name('Cart')->where($where)->where('supplier_id', $val['supplier_id'])->bind($bind)->limit($limit)->count();
                $cart_selected = Db::name('Cart')->where($where)->where('supplier_id', $val['supplier_id'])->where('selected', '1')->bind($bind)->limit($limit)->count();
                if ($cart_num == $cart_selected){
                    $su_selected[$val['supplier_id']] = 1;
                }else{
                    $su_selected[$val['supplier_id']] = 0;
                }
            }
            // 获取图片
            foreach ($cart as $k => $val) {
                $val['goods_thumb'] = Db::name('goods')->where('goods_id', $val['goods_id'])->Cache(YLT_CACHE_TIME)->value('goods_thumb');
                if ($val['prom_type'] == 3) {
                    $val['prom'] = Db::name('prom_goods')->where('id', $val['prom_id'])->find();
                }
                //成本价不为零时获取与售价的差价--可作为分销佣金
                if ($val['cost_price']!=0) {
                    $val['distribution_price'] = $val['goods_price'] - $val['cost_price'];
                }
                $carts[] = $val;
            }

        
            $shipping_price = $anum = $total_price = $cut_fee = 0;
            $cartList = [];

            foreach ($carts as $k => $val) {


                if (!isset($cartList[$val['supplier_id']]['supplier_id']))

                $cartList[$val['supplier_id']]['supplier_name'] = $val['supplier_name'];
                $cartList[$val['supplier_id']]['supplier_id'] = $val['supplier_id'];
                $cartList[$val['supplier_id']]['is_designer'] = $val['is_designer'];
                $cartList[$val['supplier_id']]['selected'] = $su_selected[$val['supplier_id']];    //   选中状态
                $val['store_count'] = getGoodNum($val['goods_id'], $val['spec_key']);        // 最多可购买的库存数量
                $val['prom_id'] = explode(',', $val['prom_id']);               //活动id为数组
                $val['prom_type'] = explode(',', $val['prom_type']);               //活动id为数组
                $anum += $val['goods_num'];
                //判断是否包邮的商品，原则上店铺里有包邮的商品则店铺全部包邮
                if ($val['is_free_shipping'] == 1) {
                    $cartList[$val['supplier_id']]['is_free_shipping'] = 1;
                }
                $cartList[$val['supplier_id']]['list'][] = $val;
                //判断是否包邮的商品

                // 如果要求只计算购物车选中商品的价格 和数量  并且  当前商品没选择 则跳过
                if ($selected == 1 && $val['selected'] == 0){
                    continue;
                }
                
                $cartList[$val['supplier_id']]['total_price'] += ($val['goods_num'] * $val['goods_price']); //商铺商品总价

                //商铺商品运费
                $is_free_shipping  = Db::name('supplier_config')->where(["supplier_id" => $cartList[$val['supplier_id']]["supplier_id"],"name"=>"is_free_shipping"])->value('value');// 商店设置是否包邮
                $cartList[$val['supplier_id']]['shipping_price'] = 0; //商铺商品运费
                if (!empty($is_free_shipping)) { //is_free_shipping 字段有数据的情况下判断商品金额是否符合包邮
                    if ($is_free_shipping>$cartList[$val['supplier_id']]['total_price']) {
                        $cartList[$val['supplier_id']]['shipping_price'] = Db::name('supplier_config')->where(["supplier_id" => $cartList[$val['supplier_id']]["supplier_id"],"name"=>"free_shipping"])->value('value');//商铺商品运费
                    }
                }

                // 商品活动减免
                for ($i=0; $i <count($val['prom_id']) ; $i++) {
                    if ($val['prom_type'][$i] == 3 && $val['prom_id'][$i] > 0) {
                        $prom_amount = Db::name('prom_goods')->where('id', $val['prom_id'][$i])->find();
                        if ($prom_amount['type'] == 1) { //单品满减活动
                            $prom_total_price = $val['goods_num'] * $val['goods_price'];
                            if ($prom_total_price >= $prom_amount['money']) {
                                $order_prom_amount = $prom_amount['expression'];
                            }
                        }
                        if ($prom_amount['end_time'] < $now_time || $prom_amount['start_time'] > $now_time){
                            $order_prom_amount = 0;
                        }
                    }
                }
                $order_prom_amount_s += $order_prom_amount ? $order_prom_amount : 0;//促销满减金额
                    // 领券商品活动减免-多优惠券新代码
                    // 订单页面优惠券改为可选择模式，这段自动扣除的代码停止使用
                    $order_prom_amount_l += $order_prom_amount_l ? $order_prom_amount_l : 0;//优惠券金额
                    for ($i = 0; $i < count($val['prom_id']); $i++) {
                        if ($val['prom_type'][$i] == 4 && $val['prom_id'][$i] > 0) {
                            $prom_amount = Db::name('coupon')->alias('p')->join('coupon_list l','p.id=l.cid')->where('p.id', $val['prom_id'][$i])->where('p.use_end_time','>',$now_time)->where('l.order_id = 0')->where('l.uid',$user[user_id])->find();
                            if ($prom_amount['use_end_time'] < $now_time){
                                Db::name('cart')->where(['prom_type' => 4, 'prom_id' => $val['prom_id'][$i]])->delete();
                            }
                            if ($prom_amount['type'] == 3) { //邀请发放-领券
                                $prom_total_price = $this->prom_total_price($val['prom_id'][$i], $user, $session_id, 1, 1, 1);
                                if ($prom_total_price['result'] >= $prom_amount['condition']) {
                                    foreach ($coupon_list as $key => $va) {
                                        if ($va['cid'] == $val['prom_id'][$i]) {
                                            $prom_id[] = $val['prom_id'][$i];
                                            $code[] = $va['code'];
                                            $lll = array_unique($code);
                                            $idd = array_unique($prom_id);
                                            foreach ($lll as $key => $v) {
                                                $a = $this->coupon_price($v);
                                                $c[] = $a;
                                                $d = array_slice($c, -count($lll), count($lll));
                                                for ($j = 0; $j < count($lll); $j++) {
                                                    $d[$j + 1]['result'] += $d[$j]['result'] ? $d[$j]['result'] : 0;
                                                }
                                                $order_prom_amount_l = $d[count($lll)]['result'];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                //非可选择列表的旧代码结束
                
                if ($val['spec_key'] == 5454) {  //端午活动规格ID
                    $order_prom_amount_c += ($val['member_goods_price'] - $val['member_goods_price'] * 1) * $val['goods_num'];   //端午活动折扣参数
                }else{
                    $extend_cat_id= Db::name('goods')->where('goods_id', $val['goods_id'])->Cache(YLT_CACHE_TIME)->value('extend_cat_id');
                    if($extend_cat_id==1082){ //商品场景分类id
                        if($val['goods_num']>=100){
                            $order_prom_amount_d += ($val['member_goods_price'] - $val['member_goods_price'] * 1) * $val['goods_num'];   //端午活动折扣参数
                        }
                    }
                }

                $cartList[$val['supplier_id']]['order_prom_amount_l'] = $order_prom_amount_l ? $order_prom_amount_l : 0; //优惠券金额
                $cartList[$val['supplier_id']]['order_prom_amount'] = $order_prom_amount_s + $order_prom_amount_c+$order_prom_amount_d;//活动优惠金额

                //市场价不为0时，计算出本店售价比市场价便宜了多少钱
                if ($val['market_price']!=0) {
                    $cut_fee += $val['goods_num'] * $val['market_price'] - $val['goods_num'] * $val['goods_price']; 
                }
                //总价  
                $total_price += $val['goods_num'] * $val['goods_price'];
            }
        }
        if ($cartList) {
            //获取总订单的运费
            foreach ($cartList as $key => $value) {
                $shipping_price+=$cartList[$value['supplier_id']]['shipping_price'];
            }
        }

        //获取佣金总额
        foreach ($cart as $key => $br) {
            if ($br['brokerage'] == 1 && $br['commission_price'] !=0) {
                $br['commission_price'] = $br['commission_price']*$br['goods_num'];
                $commission_price += $br['commission_price'];
            }
            if ($br['brokerage'] == 1 && $br['commission_price'] ==0 && $br['cost_price'] != 0) {
                $a = $br['goods_price'] - $br['cost_price'];
                $commission_price += $a * $br['goods_num'];
                // $commission_price += $br['goods_price'] - $br['cost_price'];
            }
        }
        $ratio=Db::name('distribution_id')->where('id=1')->field('ratio')->value('ratio'); //查询佣金分成比例
        $commission_price = $commission_price * $ratio;
        $commission_price = $commission_price ? $commission_price:0;

        $order_prom_amount = $cartList[$val['supplier_id']]['order_prom_amount'];
        $total_price = array('total_fee' => $total_price, 'cut_fee' => $cut_fee, 'num' => $anum, 'order_prom_amount' => $order_prom_amount,'order_prom_amount_l' => $cartList[$val['supplier_id']]['order_prom_amount_l'],'commission_price' => $commission_price,'shipping_price' => $shipping_price); // 总计
        setcookie('cn', $anum, null, '/');

        if ($mode == 1) {
            return array('cartList' => $cartList, 'total_price' => $total_price);
        }
        return array('status' => 1, 'msg' => '', 'result' => array('cartList' => $cartList, 'total_price' => $total_price));

    }

    
    /**
     * [coupon_price 获取优惠券码的金额]
     * @param  [type] $code [description]
     * @return [type]       [description]
     */
    public function coupon_price($code)
    {
        $coupon = Db::name('coupon')->alias('c')->join('coupon_list l', 'c.id=l.cid')->where('l.code', $code)->find();
        return array('status' => 1, 'msg' => '', 'result' => $coupon['money']);
    }

    /**
     * 获取用户可以使用的优惠券
     * @param type $user_id 用户id
     * @param type $coupon_id 优惠券id
     * $mode 0  返回数组形式  1 直接返回result
     */
    public function getCouponMoney($user_id, $coupon_id, $mode)
    {
        if ($coupon_id == 0) {
            if ($mode == 1) return 0;
            return array('status' => 1, 'msg' => '', 'result' => 0);
        }
        $couponlist = Db::name('CouponList')->where("uid", $user_id)->where('cid', $coupon_id)->find(); // 获取用户的优惠券
        if (empty($couponlist)) {
            if ($mode == 1) return 0;
            return array('status' => 1, 'msg' => '', 'result' => 0);
        }

        $coupon = Db::name('Coupon')->where("id", $couponlist['cid'])->find(); // 获取 优惠券类型表
        $coupon['money'] = $coupon['money'] ? $coupon['money'] : 0;

        if ($mode == 1) return $coupon['money'];
        return array('status' => 1, 'msg' => $coupon['id'], 'result' => $coupon['money']);
    }

    /**
     * 根据优惠券代码获取优惠券金额
     * @param type $couponCode 优惠券代码
     * @param type $order_momey Description 订单金额
     * return -1 优惠券不存在 -2 优惠券已过期 -3 订单金额没达到使用券条件
     */
    public function getCouponMoneyByCode($couponCode, $order_momey)
    {
        $couponlist = Db::name('CouponList')->where("code", $couponCode)->find(); // 获取用户的优惠券
        if (empty($couponlist))
            return array('status' => -9, 'msg' => '优惠券码不存在', 'result' => '');
        if ($couponlist['order_id'] > 0) {
            return array('status' => -20, 'msg' => '该优惠券已被使用', 'result' => '');
        }
        $coupon = Db::name('Coupon')->where("id", $couponlist['cid'])->find(); // 获取优惠券类型表
        if (time() > $coupon['use_end_time'])
            return array('status' => -10, 'msg' => '优惠券已经过期', 'result' => '');
        if ($order_momey < $coupon['condition'])
            return array('status' => -11, 'msg' => '金额没达到优惠券使用条件', 'result' => '');
        if ($couponlist['order_id'] > 0)
            return array('status' => -12, 'msg' => '优惠券已被使用', 'result' => '');

        return array('status' => 1, 'msg' => '', 'result' => $coupon['money']);
    }

    /**
     *  添加一个订单
     * @param type $user_id 用户id
     * @param type $address_id 地址id
     * @param type $shipping_code 物流编号
     * @param type $invoice_title 发票
     * @param type $coupon_id 礼品卡id
     * @param type $car_price 各种价格
     * @param type $user_note 用户备注
     * @param type $recommend_code 推荐人
     * @param type $order_prom_id_s 优惠券金额与id
     * @param type $selected    立即购买的状态 2
     * @return type $order_id 返回新增的订单id
     */
    public function addOrder($user_id, $address_id, $invoice_title, $coupon_id = 0, $car_price, $user_note = '', $recommend_code = '', $order_prom_id_s = array(),$selected=0)
    {   
        $new_order_id = 0;//父ID
        $gift_good_id = array('4795', '4796', '4797');//赠送商品id
        $gift_good_num = 0;//赠送商品数量
        $order_count = Db::name('Order')->where("user_id", $user_id)->where('order_sn', 'like', date('Ymd') . "%")->count(); // 查找购物车商品总数量
        if ($order_count >= 30){
            return array('status' => -9, 'msg' => '为避免刷单，一天只能下30个订单', 'result' => '');
        }

        // 0插入订单 order
        $address = Db::name('UserAddress')->where("address_id", $address_id)->find();
        if ($selected ==2) {
            //选中的商品(立即购买)
            $cart = Db::name('Cart')->where(['user_id' => $user_id, 'selected' => 2])->order('id desc')->limit(1)->select();
        }else{
            //选中的商品(购物车选中)
            $cart = Db::name('Cart')->where(['user_id' => $user_id, 'selected' => 1])->order('supplier_id asc ')->select();
        }
            

        // 活动商品数量过滤
        foreach ($cart as $key => $value) {
            //优惠券改动
            $value['prom_type']=explode(",",$value['prom_type']);
            $value['prom_id']=explode(",",$value['prom_id']);
            for ($i=0; $i <count($value); $i++) { 

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
                if($value['prom_type'][$i]==1 || $value['prom_type'][$i]==5){ 
                    $prom = Db::name('panic_buying')->where('id',$value['prom_id'][$i])->find();
                    if($prom['buy_num'] >= $prom['goods_num']){
                        Db::name('Cart')->where('prom_type',$value['prom_type'][$i])->where('prom_id',$value['prom_id'][$i])->delete();
                        return array('status'=>-9,'msg'=>'已售馨,本期活动结束','result'=>-1);
                    }
                }
                //折扣、秒杀
                if($value['prom_type'][$i]==2){
                    $prom = Db::name('discount_buy')->where('id',$value['prom_id'][$i])->where('is_start',1)->find();
                }
                //满减的商品超过活动时间
                if($value['prom_type'][$i]==3){
                    $prom = Db::name('prom_goods')->where('id',$value['prom_id'][$i])->find();
                }
                if($value['prom_type'][$i] > 0 and $value['prom_type'][$i]!=4){
                  if(!empty($prom) and $prom['start_time'] > time() || $prom['end_time'] < time()){
                    $cid=$value[prom_id][$i];
                    Db::query("update ylt_cart set prom_type =concat(SUBSTRING(prom_type ,1,position(',3' in prom_type )-1),'',substring(prom_type ,position(',3' in prom_type )+length(',3'))) where prom_id like '%,$cid%'");
                    Db::query("update ylt_cart set prom_id =concat(SUBSTRING(prom_id ,1,position(',$cid' in prom_id )-1),'',substring(prom_id ,position(',$cid' in prom_id )+length(',$cid'))) where prom_id like '%,$cid%'");
                    // Db::name('Cart')->where('prom_type',$value['prom_type'][$i])->where('prom_id',$value['prom_id'][$i])->delete();
                    // return array('status'=>-19,'msg'=>'活动已结束','result'=>-19);
                  }
                }
                //领券的商品超过活动时间
                if($value['prom_type'][$i]==4 ){
                  $prom = Db::name('coupon')->where('id',$value['prom_id'][$i])->find();
                  if(!empty($prom) and $prom['use_start_time'] > time() || $prom['use_end_time'] < time()){
                    $cid=$value[prom_id][$i];
                    Db::query("update ylt_cart set prom_type =concat(SUBSTRING(prom_type ,1,position(',3' in prom_type )-1),'',substring(prom_type ,position(',3' in prom_type )+length(',3'))) where prom_id like '%,$cid%'");
                    Db::query("update ylt_cart set prom_id =concat(SUBSTRING(prom_id ,1,position(',$cid' in prom_id )-1),'',substring(prom_id ,position(',$cid' in prom_id )+length(',$cid'))) where prom_id like '%,$cid%'");
                    // Db::name('Cart')->where('prom_type',$value['prom_type'][$i])->where('prom_id',$value['prom_id'][$i])->delete();
                    // return array('status'=>-9,'msg'=>'活动已结束','result'=>-1);
                  }
                }
            } //for
        }   //foreach
        $cartList = [];

        // 分商铺订单
        foreach ($cart as $k => $val) {
            if (!isset($cartList[$val['supplier_id']]['supplier_id'])){
                $cartList[$val['supplier_id']]['supplier_name'] = $val['supplier_name'];
            }
            $cartList[$val['supplier_id']]['supplier_id'] = $val['supplier_id'];
            $cartList[$val['supplier_id']]['is_designer'] = $val['is_designer'];
            $cartList[$val['supplier_id']]['spec_key'] = $val['spec_key'];
            $val['store_count'] = getGoodNum($val['goods_id'], $val['spec_key']);        // 最多可购买的库存数量
            $cartList[$val['supplier_id']]['total_price'] += ($val['goods_num'] * $val['goods_price']);

            //查询商家在红礼组合礼包拥有的商品，计算供货价总额
            if ($val['is_group'] != 1) {  //不是礼包则记入单品价格，是礼包先不记入，后面选择拥有的商品再计算
                $cartList[$val['supplier_id']]['red_total_price'] += ($val['goods_num'] * $val['red_cost_price']);
            }
            $red_goods = DB::name('red_goods')->where('goods_id',$val['red_goods_id'])->select();
            $orderGoods_group[] = get_group_goods($red_goods); //查询是否拥有红礼组合礼包商品
            //查询商家在红礼组合礼包拥有的商品，计算供货价总额
            
            //商铺商品运费
            $cartList[$val['supplier_id']]['shipping_price'] = 0;
            $is_free_shipping  = Db::name('supplier_config')->where(["supplier_id" => $cartList[$val['supplier_id']]["supplier_id"],"name"=>"is_free_shipping"])->value('value');// 商店设置是否包邮
            $cartList[$val['supplier_id']]['shipping_price'] = 0; //商铺商品运费
            if (!empty($is_free_shipping)) { //is_free_shipping 字段有数据的情况下判断商品金额是否符合包邮
                if ($is_free_shipping>$cartList[$val['supplier_id']]['total_price']) {
                    $cartList[$val['supplier_id']]['shipping_price'] = Db::name('supplier_config')->where(["supplier_id" => $cartList[$val['supplier_id']]["supplier_id"],"name"=>"free_shipping"])->value('value');//商铺商品运费
                }
            }
            $cartList[$val['supplier_id']]['order_prom_id'] = 0;            //优惠类id
            $cartList[$val['supplier_id']]['order_prom_type'] = 0;          //优惠类型
            // $cartList[$val['supplier_id']]['red_goods_id'] = $val['red_goods_id'];      //红礼商品ID
            $cartList[$val['supplier_id']]['red_supplier_id'] = $val['red_supplier_id'];//红礼商家ID
            // $cartList[$val['supplier_id']]['is_group'] = $val['is_group'];    //是否红礼组合礼包
            $cartList[$val['supplier_id']]['list'][] = $val;
            // 商品活动减免
            if ($val['prom_type'] == 3 && $val['prom_id'] > 0) {
                $prom_amount = Db::name('prom_goods')->where('id', $val['prom_id'])->find();
                if ($prom_amount['type'] == 1) { //单品满减活动
                    $prom_total_price = $val['goods_num'] * $val['goods_price'];
                    if ($prom_total_price >= $prom_amount['money']) {
                        $order_prom_amount = $prom_amount['expression'];
                        // $$val['prom_id'] ? $$val['prom_id'] : 0;
                        $cartList[$val['supplier_id']]['order_prom_type'] = $$val['prom_type'] ? $$val['prom_type'] : 0;
                        $cartList[$val['supplier_id']]['order_prom_id'] = $val['prom_id'] ? $val['prom_id'] : 0;
                    }
                }
            }

            // //抗疫活动领取后改变申请状态和删除分享记录
            // if ($val['spec_key']==5938) {         ////领取3个口罩
            //     Db::name('goods_share_list')->where('user_id',$user_id)->where("goods_id = 5862 || goods_id = 5897")->limit(1)->delete();
            //     Db::name('goods_apply_list')->where(['user_id'=>$user_id,'type'=>1,'is_check'=>1])->order('id  desc')->limit(1)->update(['is_get'=>1]);
            // }elseif($val['spec_key']==5937){      ////领取10个口罩
            //     Db::name('goods_share_list')->where('user_id',$user_id)->where("goods_id = 5862 || goods_id = 5897")->limit(20)->delete();
            //     Db::name('goods_apply_list')->where(['user_id'=>$user_id,'type'=>2,'is_check'=>1])->order('id  desc')->limit(1)->update(['is_get'=>1]);
            // }elseif($val['spec_key']==5939){      ////领取1个测温枪
            //     Db::name('goods_apply_list')->where(['user_id'=>$user_id,'type'=>3,'is_check'=>1])->update(['is_get'=>1]);
            // }
            // //抗疫商品非0元成交后修改字段，方便支付后自动生成推荐人申请记录
            // if ($val['goods_id']==5862 || $val['goods_id']==5897) {  
            //     if ($val['spec_key']!=5938 && $val['spec_key']!=5937 && $val['spec_key']!=5939 ) {
            //         $cartList[$val['supplier_id']]['admin_note'] = 'share_record';
            //     }
            // }
            // //抗疫活动后可删
            
            //端午活动
            // if ($val['spec_key'] == 5454) {  //端午活动规格ID
            //     $order_prom_amountsss += ($val['member_goods_price'] - $val['member_goods_price'] * 1) * $val['goods_num'];   //端午活动折扣参数
            //     // $result['order_prom_amount']=$order_prom_amountsss;
            // } else {
            //     $extend_cat_id= Db::name('goods')->where('goods_id', $val['goods_id'])->Cache(YLT_CACHE_TIME)->value('extend_cat_id');
            //     if($extend_cat_id==1082){ //商品场景分类id

            //         if($val['goods_num']>=100){
            //             $order_prom_amountsss += ($val['member_goods_price'] - $val['member_goods_price'] * 1) * $val['goods_num'];   //端午活动折扣参数
            //         }
            //     }else{
                    $order_prom_amountsss_s = 0;
            //     }
            // }
            // 
            $order_prom_amountsss = 0;
            $cartList[$val['supplier_id']]['order_prom_amount'] += $order_prom_amount ? $order_prom_amount : 0;
            $cartList[$val['supplier_id']]['order_prom_amount_s'] = $order_prom_amountsss + $order_prom_amountsss_s;
            $order_prom_amount = 0;
            $couponFee[] = $val['prom_id'];
        }

        //查询商家在红礼组合礼包拥有的商品，计算供货价总额
        $is_group = 0;
        if ($orderGoods_group) {
            foreach ($orderGoods_group as $ke => $group) {
                if ($group) {
                foreach ($group as $ke => $group_v) {
                    $red_total_price += ($group_v['goods_num'] * $group_v['red_cost_price']);
                }
                }
            }
            $is_group = 1;
        }
        //查询商家在红礼组合礼包拥有的商品，计算供货价总额结束
        
        if (count($cartList) > 1) {
            //生成父单,多个订单
            $order = array(
                'order_sn' => date('YmdHis') . rand(1000, 9999), // 订单编号
                'user_id' => $user_id,
                'order_status' => 0,
                'pay_status' => 0,
                'consignee' => $address['consignee'],
                'province' => $address['province'],
                'city' => $address['city'],
                'district' => $address['district'],
                'address' => $address['address'],
                'mobile' => $address['mobile'],
                'goods_price' => $car_price[0]['goodsFee'],
                'order_amount' => $car_price[0]['payables'],
                'total_amount' => $car_price[0]['goodsFee'],
                'order_prom_type' => '0',
                'order_prom_id' => $car_price[0]['order_prom_id'] ? $car_price[0]['order_prom_id'] : 0,
                'order_prom_amount' => $car_price[0]['order_prom_amount'] ? $car_price[0]['order_prom_amount'] : 0,
                'order_prom_amount_s' => $order_prom_amountsss ? $order_prom_amountsss : 0,
                'shipping_price' => $car_price[0]['postFee'],
                'add_time' => time(),
                'source' => $user_note['source'],
                'is_parent' => '1',
                'supplier_id' => "",
                'recommend_code' => $recommend_code,   //绑定的上级ID
                'source_id' => Db::name('users')->where('user_id',$user_id)->value('source_id'),                //本次订单的临时推荐人（商品的分享用户）
                'items_source'  => Db::name('users')->where('user_id',$user_id)->value('items_source'),
                'plate'  => Db::name('users')->where('user_id',$user_id)->value('plate'),
            );
            if (empty($order['recommend_code'])) {
                $order['recommend_code'] = Db::name('users')->where('user_id',$user_id)->value('referrer_id');
            }
            $order['order_id'] = $new_order_id = Db::name("Order")->insertGetId($order);
            if (!$new_order_id){
                return array('status' => -8, 'msg' => '添加订单失败', 'result' => NULL);
            }

            //红礼商家父订单复制同步
            if ($cartList[$val['supplier_id']] == 686 ) {
                Db::name("RedOrder")->insertGetId($order);
            }else{ //新增一礼通订单，然后删除，同步两个表的自增ID数 
                $order['order_id'] = $new_order_id;
                Db::name("RedOrder")->insertGetId($order);
                Db::name("RedOrder")->where('order_id',$new_order_id)->delete();
            }
            //红礼商家父订单复制同步结束
        }
        // 按店铺分单生成订单
        foreach ($cartList as $k => $val) {
            $data = array(
                'order_sn' => date('YmdHis') . rand(1000, 9999), // 订单编号
                'user_id' => $user_id, // 用户id
                'consignee' => $address['consignee'], // 收货人
                'province' => $address['province'],//'省份id',
                'city' => $address['city'],//'城市id',
                'district' => $address['district'],//'县',
                'twon' => $address['twon'],// '街道',
                'address' => $address['address'],//'详细地址',
                'mobile' => $address['mobile'],//'手机',
                'zipcode' => $address['zipcode'],//'邮编',
                'email' => $address['email'],//'邮箱',
                'invoice_title' => $invoice_title, //'发票抬头',
                'goods_price' => $val['total_price'],//'商品总价格',
                'shipping_price' => $val['shipping_price'], //邮费
                'integral' => ($car_price[0]['pointsFee'] * tpCache('shopping.point_rate')), //'使用积分',
                'integral_money' => $car_price[0]['pointsFee'],//'使用积分抵多少钱',
                'total_amount' => ($val['total_price'] + $val['shipping_price']),// 订单总额
                'add_time' => time(), // order_amount下单时间
                'is_parent' => '0',
                'parent_id' => $new_order_id,
                'order_prom_type' => $val['order_prom_type'],//'订单优惠活动id',
                'order_prom_id' => $val['order_prom_id'],//'订单优惠活动id',
                'order_prom_amount' => $val['order_prom_amount'],//'订单优惠活动优惠了多少钱',
                'user_note' => $user_note['user_note_' . $val['supplier_id'] . ''], // 用户下单备注
                'source' => $user_note['source'],
                'supplier_id' => $val['supplier_id'],
                'supplier_name' => $val['supplier_name'],
                'is_designer' => $val['is_designer'],
                'recommend_code' => $recommend_code, //绑定的上级ID
                'source_id' => Db::name('users')->where('user_id',$user_id)->field('source_id')->value('source_id'),                //本次订单的临时推荐人（商品的分享用户）
                'items_source'  => Db::name('users')->where('user_id',$user_id)->value('items_source'),
                'plate'  => Db::name('users')->where('user_id',$user_id)->value('plate'),
                'red_supplier_id' => $val['red_supplier_id'],
                'red_total_price' => $val['red_total_price']+$red_total_price,         //红礼采购总价
                'red_order_amount' => $val['red_total_price']+$red_total_price,        //红礼用户应付总额
                'is_group' => $is_group,
                'admin_note' => $val['admin_note'],
                'is_share' => $val['list'][0]['is_share'],   //商品发起拼单,立即购买时只有一个商品列表
            );
            if (empty($data['recommend_code'])) {
                $data['recommend_code'] = Db::name('users')->where('user_id',$user_id)->field('referrer_id')->value('referrer_id');
            }

            //子单入表时分别写入相关优惠金额（用券/端午活动）
            for ($i = 0; $i < count($val['list']); $i++) {
                for ($j = 0; $j < count($order_prom_id_s); $j++) {
                    $prom_id = explode(',', $val['list'][$i]['prom_id']);
                    if ($val['list'][$i]['spec_key'] == 5454 and $val["supplier_id"] == 41) {
                        $data['order_prom_amount_s'] = $order_prom_amountsss;   //端午活动专区
                    } else {
                        $extend_cat_id= Db::name('goods')->where('goods_id', $val['list'][$i]['goods_id'])->Cache(YLT_CACHE_TIME)->value('extend_cat_id');
                        if($extend_cat_id==1082){ //商品场景分类id
                            if($val['list'][$i]['goods_num']>=100){
                                $data['order_prom_amount_s'] = $order_prom_amountsss;   //端午活动专区
                            }else{
                                $data['order_prom_amount_s'] = 0;   //端午活动专区
                            }

                        }else{
                            $data['order_prom_amount_s'] = 0;   //端午活动专区
                        }
                    }
                    for ($l = 0; $l < count($prom_id); $l++) {
                        if ($prom_id[$l] == $order_prom_id_s[$j]['cid']) {
                            // $order_prom_cid['cid']=$order_prom_id_s[$j]['cid'];
                            // $order_prom_cid['money']=$order_prom_id_s[$j]['money'];
                            // $order_prom_money[]=$order_prom_cid;
                            // if (count($order_prom_money)!=count(assoc_unique($order_prom_money,'cid'))) {
                            //     $order_prom_money_s=assoc_unique($order_prom_money,'cid');
                            // 剔除重复的优惠券;
                            //     $data['coupon_price']=$this->coupon_money($order_prom_money_s);
                            // }else{
                                $data['coupon_price'] = $order_prom_id_s[$j]['money'];//'使用优惠券',
                                $couponid[]=$order_prom_id_s[$j]['couponid'];   //优惠券ID
                            // }
                        }
                    }
                }
            }
            
            if (!empty($car_price[0]['codeFee'])) {         //匹配礼品卡优惠的商品，分订单时准确计算金额
                foreach ($car_price[0]['code_goods_id'] as $key => $value_id) {
                    if ($value_id == $val['list'][$key]['goods_id']) {
                        $data['code_money']  = $car_price[0]['codeFee'];
                    }
                }
            }

            //'应付款金额'
            $data['order_amount'] = ($val['total_price'] - $val['order_prom_amount'] - $data['coupon_price']- $data['code_money'] + $data['shipping_price']);

            if (session('custom_id')) {
                $custom=Db::name('custom')->where('id',session('custom_id'))->find();
                $data['custom_id']=$custom['id'];
            }


            $data['order_id'] = $order_id = Db::name("Order")->insertGetId($data);

            //红礼商家子订单复制同步
            if ($data['supplier_id'] == 686 ) {
                $data['source'] ='一礼通';
                Db::name("RedOrder")->insertGetId($data);
            }else{ //新增一礼通订单，然后删除，同步两个表的自增ID数 
                Db::name("RedOrder")->insertGetId($data);
                Db::name("RedOrder")->where('order_id',$order_id)->delete();
            }
            //红礼商家子订单复制同步结束


            if (!$order_id){
                return array('status' => -8, 'msg' => '添加订单失败', 'result' => NULL);
            }


            // 1插入order_goods 表
            foreach ($val['list'] as $key => $va) {
                $goods = Db::name('Goods')->where(array('goods_id' => $va['goods_id'], 'is_on_sale' => '1'))->find();
                if ($goods) {
                    $data2['order_id'] = $order_id; // 订单id
                    $data2['goods_id'] = $va['goods_id']; // 商品id
                    $data2['goods_name'] = $va['goods_name']; // 商品名称
                    $data2['goods_sn'] = $va['goods_sn']; // 商品货号
                    $data2['goods_num'] = $va['goods_num']; // 购买数量
                    $data2['market_price'] = $va['market_price']; // 市场价
                    $data2['goods_amount'] = $va['goods_num'] * $va['goods_price'];
                    $data2['goods_price'] = $va['goods_price']; // 商品价
                    $data2['spec_key'] = $va['spec_key']; // 商品规格
                    $data2['spec_key_name'] = $va['spec_key_name']; // 商品规格名称
                    $data2['member_goods_price'] = $va['member_goods_price']; // 会员折扣价
                    $data2['cost_price'] = $goods['cost_price']; // 平台成本价
                    $data2['red_cost_price'] = $goods['red_cost_price']; // 红礼成本价
                    $data2['give_integral'] = $goods['give_integral']; // 购买商品赠送积分
                    $data2['prom_type'] = $va['prom_type']; // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
                    $data2['prom_id'] = $va['prom_id']; // 活动id
                    $data2['commission_price'] = $va['commission_price']; //推广佣金
                    $data2['goods_thumb'] = $va['goods_thumb']; //缩略图
                    $order_goods_id = Db::name("OrderGoods")->insertGetId($data2);

                    //红礼商家订单商品复制同步
                    if ($data['supplier_id'] == 686 ) {
                        $data2['goods_id'] = $va['red_goods_id'];
                        $data2['order_goods_rid'] = $order_goods_id;
                        Db::name("RedOrderGoods")->insertGetId($data2);
                    }
                    //红礼商家订单商品复制同步结束
                    
                    $prom_type = 0;
                    if ($va['prom_type'] == 1 || $va['prom_type'] == 2 || $va['prom_type'] == 5) {
                        $prom_type = $va['prom_type'];
                        Db::name('order')->where('order_id', $order_id)->update(['order_prom_type' => $va['prom_type'], 'order_prom_id' => $va['prom_id']]);
                    }

                    //端午活动
                    // if ($va['spec_key'] == 5454) {
                    //     Db::name('order')->where('order_id', $order_id)->update(['order_prom_amount_s' => $order_prom_amountsss, 'order_amount' => $val['total_price'] - $val['order_prom_amount'] - $data['coupon_price'] - $data['code_money'] - $order_prom_amountsss]);
                    //     // $val['total_price'] - $val['order_prom_amount']-$data['coupon_price']-$data['order_prom_amount_s']
                    // }else{
                    //     $extend_cat_id= Db::name('goods')->where('goods_id', $va['goods_id'])->Cache(YLT_CACHE_TIME)->value('extend_cat_id');

                    //     if($extend_cat_id==1082){ //商品场景分类id
                    //         if($va['goods_num']>=100) {
                    //             Db::name('order')->where('order_id', $order_id)->update(['order_prom_amount_s' => $order_prom_amountsss, 'order_amount' => $val['total_price'] - $val['order_prom_amount'] - $data['coupon_price'] - $data['code_money'] - $order_prom_amountsss]);
                    //         }
                    //     }
                    // }
                    //端午活动结束
                    
                    
                    //提交订单后删除购物车
                    Db::name('Cart')->where(['user_id' => $user_id,'id' => $va['id']])->delete();
                }else{
                    Db::name('Cart')->where(['user_id' => $user_id, 'id' => $va['id']])->delete();
                    return array('status' => 8, 'msg' => '提交订单失败，部分商品已下架', 'result' => $order_id); // 返回新增的订单id
                }

                //分销商城的商品订单多存一个表
                if ($va['brokerage'] == 1) {
                    $date4['order_id'] = $data['order_id'];
                    $date4['u_id'] = $user_id;
                    $distribution_id=Db::name('distribution')->where('u_id',$user_id)->field('r_id')->value('r_id');
                    $date4['r_id'] = Db::name('distribution')->where('id',$distribution_id)->field('u_id')->value('u_id');
                    if (empty($date4['r_id'])) {
                        $date4['r_id'] = Db::name('users')->where('user_id',$user_id)->field('referrer_id')->value('referrer_id');
                    }
                    $date4['order_type'] = 0;
                    $date4['order_money'] = $data['total_amount'];
                    $date4['order_sn'] = $data['order_sn'];
                    $date4['add_time'] = time();
                    if ($va['commission_price'] != 0) {
                        $va['commission_price'] = $va['commission_price']*$va['goods_num'];
                        $rebates = $va['commission_price'];
                    }elseif($va['commission_price'] == 0 && $va['cost_price'] != 0){
                        $a = $va['goods_price'] - $va['cost_price'];
                        $rebates = $a * $va['goods_num'];
                    }
                    $date4['rebates'] +=$rebates;
                }
            }
            if ($date4) {
                Db::name('order_distribution')->insert($date4);//存分销订单表
            }

            //清除优惠券和礼品卡的session
            unset($_SESSION["CouponMoney"]);  
            unset($_SESSION["CodeMoney"]);  
            unset($_SESSION["CodeCode"]);  
            unset($_SESSION["get_coupon_id"]);
            // 活动订单即时减库存
            if ($prom_type > 0){
                minus_stock($order_id);
            }
            // 记录订单操作日志
            $action_info = array(
                'order_id' => $order_id,
                'action_user' => $user_id,
                'action_note' => '您提交了订单，请等待系统确认',
                'status_desc' => '提交订单', //''
                'log_time' => time(),
                'supplier_id' => $val['supplier_id']
            );
            Db::name('order_action')->insertGetId($action_info);

        }
        // 如果应付金额为0  可能是余额支付 + 积分 + 优惠券 这里订单支付状态直接变成已支付 
        if ($data['order_amount'] == 0) {
            update_pay_status($data['order_sn']);
        }
        if ($new_order_id == 0) {
            $new_order_id = $order_id;
        }

        // 修改礼品卡状态  
        if ($coupon_id > 0) {
            $data3['uid'] = $user_id;
            $data3['order_id'] = $order_id;
            $data3['use_time'] = time();
            Db::name('CodeList')->where("id", $coupon_id)->update($data3);// 优惠券码
            $cid = Db::name('CodeList')->where("id", $coupon_id)->getField('cid');// 优惠券码
            Db::name('Code')->where("id", $cid)->setInc('use_num'); // 优惠券码的使用数量加一
        }  
        // 修改优惠券状态
        if (!empty($couponid)) {
            $data3['uid'] = $user_id;
            $data3['order_id'] = $order_id;
            $data3['use_time'] = time();
            foreach ($couponid as $key => $value) {
                Db::name('CouponList')->where("id", $value)->update($data3);// 优惠券
                $cid = Db::name('CouponList')->where("id",$value)->getField('cid');// 优惠券
                Db::name('Coupon')->where("id", $cid)->setInc('use_num'); // 优惠券的使用数量加一
            }    
        }
        

        return array('status' => 1, 'msg' => '提交订单成功', 'result' => $new_order_id); // 返回新增的订单id
    }
    /**
     * [coupon_money 剔除重复优惠券]
     * @param  [type] $order_prom_money [description]
     * @return [type]                   [description]
     */
    public function coupon_money($order_prom_money){
        // assoc_unique 去除重复二维数组/二维数组去重
        $key = 'cid';
        $coupon_ss=assoc_unique($order_prom_money, $key);
        foreach ($coupon_ss as $coupon_key => $coupon_value) {
            $data['coupon_price']+=$coupon_value['money'];
        }
        return $data['coupon_price'];
    }

    /**
     * 查看购物车的商品数量
     * @param type $user_id
     * $mode 0  返回数组形式  1 直接返回result
     */
    public function cart_count($user_id, $mode = 0)
    {
        $count = Db::name('Cart')->where(['user_id' => $user_id, 'selected' => 2])->count();
        if (empty($count)) {
            $count = Db::name('Cart')->where(['user_id' => $user_id, 'selected' => 1])->count();
        }
        if ($mode == 1) return $count;

        return array('status' => 1, 'msg' => '', 'result' => $count);
    }
    /**
     * 获取商品团购价
     * 如果商品没有团购活动 则返回 0
     * @param type $attr_id
     * $mode 0  返回数组形式  1 直接返回result
     */
    public function get_group_buy_price($goods_id, $mode = 0)
    {
        $group_buy = Db::name('GroupBuy')->where(['goods_id' => $goods_id, 'start_time' => ['<=', time()], 'end_time' => ['>=', time()]])->find(); // 找出这个商品
        if (empty($group_buy))
            return 0;

        if ($mode == 1) return $group_buy['groupbuy_price'];
        return array('status' => 1, 'msg' => '', 'result' => $group_buy['groupbuy_price']);
    }

    /**
     * 用户登录后 需要对购物车 一些操作
     * @param type $session_id
     * @param type $user_id
     */
    public function login_cart_handle($session_id, $user_id)
    {
        if (empty($session_id) || empty($user_id))
            return false;
        // 登录后将购物车的商品的 user_id 改为当前登录的id            
        Db::name('cart')->where("session_id", $session_id)->update(array('user_id' => $user_id));

        // 查找购物车两件完全相同的商品
        $cart_id_arr = DB::query("select id from `__PREFIX__cart` where user_id = $user_id group by  goods_id,spec_key having count(goods_id) > 1");
        if (!empty($cart_id_arr)) {
            $cart_id_arr = get_arr_column($cart_id_arr, 'id');
            $cart_id_str = implode(',', $cart_id_arr);
            Db::name('cart')->delete($cart_id_str); // 删除购物车完全相同的商品
        }
    }

    /**
     * 设置用户ID
     * @param $user_id
     */
    public function setUserId($user_id)
    {
        $user_id = $user_id;
    }

    /**
     * 获取店铺商品运费
     * @param type $goods_id 商品id
     * @param type $key 库存 key
     */

    private function ShippingPrice($supplier_id, $user_id = 0)
    {

        $sql = "SELECT sum(g.is_free_shipping) as is_free_shipping FROM ylt_cart AS c INNER JOIN ylt_goods AS g ON g.goods_id = c.goods_id WHERE c.supplier_id =$supplier_id and c.user_id = $user_id and c.selected = 1";
        $res = \think\Db::query($sql);

        if ($res[0]['is_free_shipping'] > 0)
            return '0'; //包邮
        else
            $sql = "SELECT g.shipping_price FROM ylt_cart AS c INNER JOIN ylt_goods AS g ON g.goods_id = c.goods_id WHERE c.supplier_id =$supplier_id and c.user_id = $user_id and c.selected = 1 order by g.shipping_price asc limit 0,1";
        $res = \think\Db::query($sql);
        return $res[0]['shipping_price'];
    }


    /**
     * [CouponList 订单优惠列表]
     */
    public function CouponList($cartList,$user){
        $time=time();
        // 查询商品是否拥有促销活动、优惠券、优惠卡
        foreach ($cartList as $key => $value) {
            $goods_id=array();
            $prom_type=array();
            $prom_id=array();
            //将数据整合为数组
            $goods_id = array_merge($goods_id,array_column($value['list'],'goods_id'));
            $prom_type = array_merge($prom_type,array_column($value['list'],'prom_type'));
            $prom_id = array_merge($prom_id,array_column($value['list'],'prom_id'));
            // if (count($prom_id)>1) {
                for ($i=0; $i < count($prom_type); $i++) { 
                    $sale[$i]=[
                        prom_type =>  $prom_type[$i],
                        prom_id   =>  $prom_id[$i],
                        goods_id  =>  $goods_id[$i],
                        ];
                    for ($j=0; $j <count($sale[$i][prom_type]) ; $j++) {
                        if ($sale[$i][prom_type][$j]==4) {
                            $prom_id_s[]=$sale[$i][prom_id][$j];
                            //array_unique 去除重复一维数组/一维数组去重
                            $prom_id_ss=array_unique($prom_id_s);
                        } 
                    }
                }
                foreach ($value as $ky => $vue) {
                    if (is_array($vue)) {
                        $order[]=$vue;
                    }
                }
            // }
        }
        foreach ($order as $ke => $valu) {
            //将商品的活动状态与活动ID处理为一个数组
            foreach ($valu as $k => $val) {
                for ($pr=0; $pr <count($val['prom_type']) ; $pr++) { 
                    $prom_[]=[
                        'prom_type'=>$val['prom_type'][$pr],
                        'prom_id'=>$val['prom_id'][$pr],
                    ];
                // assoc_unique 去除重复二维数组/二维数组去重 
                $key = 'prom_id'; //条件
                $prom_coupon=assoc_unique($prom_,$key);
                }
            }

            //查询拥有的优惠券id
            if ($prom_coupon) {
                foreach ($prom_coupon as $prom => $coupon) {
                    if ($coupon['prom_type']==4) {
                        $coupon_sss[]=Db::name('coupon')->alias('c')->join('coupon_list l','c.id=l.cid')->where('c.id',$coupon['prom_id'])->where('l.uid',$user['user_id'])->field('l.cid,c.coupon_type')->find();
                    }
                }
            }
            // assoc_unique 去除重复二维数组/二维数组去重
            $key = 'cid';
            $coupon_ss=assoc_unique($coupon_sss, $key);
            if ($coupon_ss) {
                foreach ($coupon_ss as $e => $value_e) {
                    foreach ($valu as $s => $value_s) {
                        $prom_coupon_id[]=$value_s['prom_id'];
                    }
                }
            }
            if ($prom_coupon_id) {
                foreach ($prom_coupon_id as $prom_coupon_key => $prom_coupon_value) {
                    for ($io=0; $io <count($prom_coupon_value) ; $io++) { 
                        //计算同个优惠券的商品的总额
                        $goods_code_price = $this->prom_total_price($prom_coupon_value[$io],$user,$session_id, 1, 1, 1);
                        //根据cid以及优惠商品总金额查询是否符合优惠条件
                        $coupon_s[]=Db::name('coupon')->alias('c')->join('coupon_list l','c.id=l.cid')->where('c.id',$prom_coupon_value[$io])->where("c.condition",'<=',$goods_code_price["result"])->where("l.send_time < $time and l.use_end_time > $time")->where('l.uid',$user['user_id'])->field('c.money,c.coupon_type,c.goods_id,c.name,c.condition,l.use_end_time,l.send_time,l.id,l.cid')->find();
                    }
                }
            }
        }
        $key = 'cid'; //条件
        $coupon_coupon=assoc_unique($coupon_s,$key);
        // 处理前端默认选中的优惠券数据
        if ($coupon_coupon) {
            foreach ($coupon_coupon as $coupon_key => $coupon_value) {
                if (!empty($coupon_value)) {
                    if ($coupon_value['coupon_type']==0 ){
                        $coupon_value['priority']=0;        //默认优先字段，0为可默认
                        $goods_id=$coupon_value['goods_id']; 
                        $goods_id_two[]=$goods_id;
                    }else{
                        $coupon_value['goods_id']= explode(',',$coupon_value['goods_id']);
                    }
                    if (!empty($coupon_value) and is_array($coupon_value['goods_id'])) {
                        // $b=array_slice($coupon_value['goods_id'],-count($coupon_value['goods_id']));
                        for ($wi=0; $wi <count($coupon_value['goods_id']) ; $wi++) { 
                            for ($ei=0; $ei <count($goods_id_two) ; $ei++) { 
                                if (array_search($goods_id_two[$ei],$coupon_value['goods_id'])) {
                                    if ($coupon_value['coupon_type']!=0) {
                                        $coupon_value['priority']=1;  //默认优先字段，0为可默认
                                    }
                                }
                                if (empty($coupon_value['priority'])) {
                                    $coupon_value['priority']=0; //默认优先字段，0为可默认
                                }
                            }
                        }
                        $coupon_value['goods_id']= implode(',',$coupon_value['goods_id']);
                    }
                }
                $coupons_coupon[]=$coupon_value;
            }
        }
        return $coupons_coupon;
    }


    /**
     * [zhongqiuCode 2019.09.06 中秋活动，结束后可删]
     * @return [type] [description]
     */
    public function zhongqiuCode($code,$cartList){
        $count = 0;
        $goods_num = 0;
        $codelist  = Db::name('CodeList')->where('code', $code)->find();//查询优惠卡码
        if ($codelist['cid'] == 54 || $codelist['cid'] == 55 ) {           //固定的卡ID，需要修改
            foreach ($cartList['41']['list'] as $key => $value) {
                if ($value['goods_id'] == 5632 || $value['goods_id'] == 5633 || $value['goods_id'] == 5634) {
                    $goods_num += $value['goods_num'];
                    $count += 1;
                }
            }
            if ($goods_num>1 || $count>1) {
                return  array('status' => -1, 'msg' => '使用该优惠券时只可购买一份商品') ;
            }
        }
    }
    
    /**
     * [zhongqiuCode 2019.10.14 电器兑换活动，结束后可删]
     * @return [type] [description]
     */
    public function electricCode($code,$cartList){
        $count = 0;
        $goods_num = 0;
        $codelist  = Db::name('CodeList')->where('code', $code)->find();//查询优惠卡码
        if ($codelist['cid'] == 58) {           //固定的卡ID，需要修改
            foreach ($cartList['41']['list'] as $key => $value) {
                if ($value['goods_id'] == 5657 || $value['goods_id'] == 5656 || $value['goods_id'] == 5655) {
                    $goods_num += $value['goods_num'];
                    $count += 1;
                }
            }
            if ($goods_num>1 || $count>1) {
                return array('status' => -1, 'msg' => '使用该优惠券时只可购买一份商品') ;
            }
        }
    }
}