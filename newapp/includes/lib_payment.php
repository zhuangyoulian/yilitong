<?php

/**
 * ECSHOP 支付接口函数库
 * ============================================================================
 * 版权所有 2005-2010 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: yehuaixiao $
 * $Id: lib_payment.php 17218 2011-01-24 04:10:41Z yehuaixiao $
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

/**
 * 取得返回信息地址
 * @param   string  $code   支付方式代码
 */
function return_url($code)
{
    return $GLOBALS['ecs']->url() . 'respond.php?code=' . $code;
}

/**
 *  取得某支付方式信息
 *  @param  string  $code   支付方式代码
 */
function get_payment($code)
{
    $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('payment').
           " WHERE pay_code = '$code' AND enabled = '1'";
    $payment = $GLOBALS['db']->getRow($sql);

    if ($payment)
    {
        $config_list = unserialize($payment['pay_config']);

        foreach ($config_list AS $config)
        {
            $payment[$config['name']] = $config['value'];
        }
    }

    return $payment;
}

/**
 *  通过订单sn取得订单ID
 *  @param  string  $order_sn   订单sn
 *  @param  blob    $voucher    是否为会员充值
 */
function get_order_id_by_sn($order_sn, $voucher = 'false')
{
    if ($voucher == 'true')
    {
        if(is_numeric($order_sn))
        {
              return $GLOBALS['db']->getOne("SELECT log_id FROM " . $GLOBALS['ecs']->table('pay_log') . " WHERE order_id= '" . $order_sn . "' AND order_type=1");
        }
        else
        {
            return "";
        }
    }
    else
    {
        if(is_numeric($order_sn))
        {
            $sql = 'SELECT order_id FROM ' . $GLOBALS['ecs']->table('order_info'). " WHERE order_sn = '$order_sn'";
            $order_id = $GLOBALS['db']->getOne($sql);
        }
        if (!empty($order_id))
        {
            $pay_log_id = $GLOBALS['db']->getOne("SELECT log_id FROM " . $GLOBALS['ecs']->table('pay_log') . " WHERE order_id='" . $order_id . "'");
            return $pay_log_id;
        }
        else
        {
            return "";
        }
    }
}

/**
 *  通过订单ID取得订单商品名称
 *  @param  string  $order_id   订单ID
 */
function get_goods_name_by_id($order_id)
{
    $sql = 'SELECT goods_name FROM ' . $GLOBALS['ecs']->table('order_goods'). " WHERE order_id = '$order_id'";
    $goods_name = $GLOBALS['db']->getCol($sql);
    return implode(',', $goods_name);
}

/**
 * 检查支付的金额是否与订单相符
 *
 * @access  public
 * @param   string   $log_id      支付编号
 * @param   float    $money       支付接口返回的金额
 * @return  true
 */
function check_money($log_id, $money)
{
    if(is_numeric($log_id))
    {
        $sql = 'SELECT order_amount FROM ' . $GLOBALS['ecs']->table('pay_log') .
              " WHERE log_id = '$log_id'";
        $amount = $GLOBALS['db']->getOne($sql);
    }
    else
    {
        return false;
    }
    if ($money == $amount)
    {
        return true;
    }
    else
    {
        return false;
    }
}

/**
 * 修改订单的支付状态
 *
 * @access  public
 * @param   string  $log_id     支付编号
 * @param   integer $pay_status 状态
 * @param   string  $note       备注
 * @return  void
 */
function order_paid($log_id, $pay_status = PS_PAYED, $note = '')
{
    /* 取得支付编号 */
    $log_id = intval($log_id);
    if ($log_id > 0)
    {
        /* 取得要修改的支付记录信息 */
        $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('pay_log') .
                " WHERE log_id = '$log_id'";
        $pay_log = $GLOBALS['db']->getRow($sql);
        if ($pay_log && $pay_log['is_paid'] == 0)
        {
            /* 修改此次支付操作的状态为已付款 */
            $sql = 'UPDATE ' . $GLOBALS['ecs']->table('pay_log') .
                    " SET is_paid = '1' WHERE log_id = '$log_id'";
            $GLOBALS['db']->query($sql);

            /* 根据记录类型做相应处理 */
            if ($pay_log['order_type'] == PAY_ORDER)
            {
                /* 取得订单信息 */
                $sql = 'SELECT order_id, user_id, order_sn, consignee, address, tel, shipping_id, extension_code, extension_id, goods_amount ' .
                        'FROM ' . $GLOBALS['ecs']->table('order_info') .
                       " WHERE order_id = '$pay_log[order_id]'";
                $order    = $GLOBALS['db']->getRow($sql);
                $order_id = $order['order_id'];
                $order_sn = $order['order_sn'];

                /* 修改订单状态为已付款 */
                $sql = 'UPDATE ' . $GLOBALS['ecs']->table('order_info') .
                            " SET order_status = '" . OS_CONFIRMED . "', " .
                                " confirm_time = '" . gmtime() . "', " .
                                " pay_status = '$pay_status', " .
                                " pay_time = '".gmtime()."', " .
                                " money_paid = order_amount," .
                                " order_amount = 0 ".
                       "WHERE order_id = '$order_id'";
                $GLOBALS['db']->query($sql);

                /* 记录订单操作记录 */
                order_action($order_sn, OS_CONFIRMED, SS_UNSHIPPED, $pay_status, $note, $GLOBALS['_LANG']['buyer']);

                /* 如果需要，发短信 */
                if ($GLOBALS['_CFG']['sms_order_payed'] == '1' && $GLOBALS['_CFG']['sms_shop_mobile'] != '')
                {
                    include_once(ROOT_PATH.'includes/cls_sms.php');
                    $sms = new sms();
                    $sms->send($GLOBALS['_CFG']['sms_shop_mobile'],
                        sprintf($GLOBALS['_LANG']['order_payed_sms'], $order_sn, $order['consignee'], $order['tel']),'', 13,1);
                }

                /* 对虚拟商品的支持 */
                $virtual_goods = get_virtual_goods($order_id);
                if (!empty($virtual_goods))
                {
                    $msg = '';
                    if (!virtual_goods_ship($virtual_goods, $msg, $order_sn, true))
                    {
                        $GLOBALS['_LANG']['pay_success'] .= '<div style="color:red;">'.$msg.'</div>'.$GLOBALS['_LANG']['virtual_goods_ship_fail'];
                    }

                    /* 如果订单没有配送方式，自动完成发货操作 */
                    if ($order['shipping_id'] == -1)
                    {
                        /* 将订单标识为已发货状态，并记录发货记录 */
                        $sql = 'UPDATE ' . $GLOBALS['ecs']->table('order_info') .
                               " SET shipping_status = '" . SS_SHIPPED . "', shipping_time = '" . gmtime() . "'" .
                               " WHERE order_id = '$order_id'";
                        $GLOBALS['db']->query($sql);

                         /* 记录订单操作记录 */
                        order_action($order_sn, OS_CONFIRMED, SS_SHIPPED, $pay_status, $note, $GLOBALS['_LANG']['buyer']);
                        $integral = integral_to_give($order);
                        log_account_change($order['user_id'], 0, 0, intval($integral['rank_points']), intval($integral['custom_points']), sprintf($GLOBALS['_LANG']['order_gift_integral'], $order['order_sn']));
                    }
                }

            }
            elseif ($pay_log['order_type'] == PAY_SURPLUS)
            {
                $sql = 'SELECT `id` FROM ' . $GLOBALS['ecs']->table('user_account') .  " WHERE `id` = '$pay_log[order_id]' AND `is_paid` = 1  LIMIT 1";
                $res_id=$GLOBALS['db']->getOne($sql);
                if(empty($res_id))
                {
                    /* 更新会员预付款的到款状态 */
                    $sql = 'UPDATE ' . $GLOBALS['ecs']->table('user_account') .
                           " SET paid_time = '" .gmtime(). "', is_paid = 1" .
                           " WHERE id = '$pay_log[order_id]' LIMIT 1";
                    $GLOBALS['db']->query($sql);

                    /* 取得添加预付款的用户以及金额 */
                    $sql = "SELECT user_id, amount FROM " . $GLOBALS['ecs']->table('user_account') .
                            " WHERE id = '$pay_log[order_id]'";
                    $arr = $GLOBALS['db']->getRow($sql);

                    /* 修改会员帐户金额 */
                    $_LANG = array();
                    include_once(ROOT_PATH . 'languages/' . $GLOBALS['_CFG']['lang'] . '/user.php');
                    log_account_change($arr['user_id'], $arr['amount'], 0, 0, 0, $_LANG['surplus_type_0'], ACT_SAVING);
                }
            }
        }
        else
        {
            /* 取得已发货的虚拟商品信息 */
            $post_virtual_goods = get_virtual_goods($pay_log['order_id'], true);

            /* 有已发货的虚拟商品 */
            if (!empty($post_virtual_goods))
            {
                $msg = '';
                /* 检查两次刷新时间有无超过12小时 */
                $sql = 'SELECT pay_time, order_sn FROM ' . $GLOBALS['ecs']->table('order_info') . " WHERE order_id = '$pay_log[order_id]'";
                $row = $GLOBALS['db']->getRow($sql);
                $intval_time = gmtime() - $row['pay_time'];
                if ($intval_time >= 0 && $intval_time < 3600 * 12)
                {
                    $virtual_card = array();
                    foreach ($post_virtual_goods as $code => $goods_list)
                    {
                        /* 只处理虚拟卡 */
                        if ($code == 'virtual_card')
                        {
                            foreach ($goods_list as $goods)
                            {
                                if ($info = virtual_card_result($row['order_sn'], $goods))
                                {
                                    $virtual_card[] = array('goods_id'=>$goods['goods_id'], 'goods_name'=>$goods['goods_name'], 'info'=>$info);
                                }
                            }

                            $GLOBALS['smarty']->assign('virtual_card',      $virtual_card);
                        }
                    }
                }
                else
                {
                    $msg = '<div>' .  $GLOBALS['_LANG']['please_view_order_detail'] . '</div>';
                }

                $GLOBALS['_LANG']['pay_success'] .= $msg;
            }

           /* 取得未发货虚拟商品 */
           $virtual_goods = get_virtual_goods($pay_log['order_id'], false);
           if (!empty($virtual_goods))
           {
               $GLOBALS['_LANG']['pay_success'] .= '<br />' . $GLOBALS['_LANG']['virtual_goods_ship_fail'];
           }
        }
    }
}

/**
 * 修改总订单的支付状态，支付时间，支付方式，以及推送订单
 *
 * @access  public
 * @param   string  $order_sn   订单号
 * @aurthor	liujiyuan	2015-12-25
 */
function order_paid_status($order_sn_no,$payname='',$paylog='')
{
	if(empty($payname)){
		$payname='alipay';
	}
    $order_sn = $order_sn_no;
    $sql = "SELECT order_status,order_id,fx_member_id,fx_money,user_id FROM " .$GLOBALS['ecs']->table('order_info'). " WHERE order_sn = '" .$order_sn. "'";
    $order_status = $GLOBALS['db']->getRow($sql);

    if (!empty($order_status) && is_numeric($order_status['order_status']) && $order_status['order_status'] == 0){
        $pay_time = time();
        $str_data = array();

        //  支付成功后，送营销优惠券给予客户     start   liujiyuan   2016-4-7

        $sql = "SELECT cpns_id FROM " .$GLOBALS['ecs']->table('coupon_list'). " WHERE for_order_id = " .$order_status['order_id'];
        $check_cpns_id = $GLOBALS['db']->getOne($sql);

        //  该订单使用了优惠券
        if (!empty($check_cpns_id) && is_numeric($check_cpns_id) && $check_cpns_id > 0){
            $sql = "SELECT A.relation_cpns_id, A.cpns_number, B.cpns_prefix  FROM " .$GLOBALS['ecs']->table('sale_coupon'). " AS A LEFT JOIN "
                .$GLOBALS['ecs']->table('coupons'). " AS B ON B.cpns_id = A.relation_cpns_id WHERE
                        A.cpns_id = " .$check_cpns_id. " AND A.send_status = 1 AND A.start_time < unix_timestamp(now())
                        AND unix_timestamp(now()) < A.end_time AND B.cpns_status = 1 AND B.cpns_gen_quantity < B.online_num";
//            $sql = "SELECT A.relation_cpns_id, A.cpns_number, B.cpns_prefix  FROM " .$GLOBALS['ecs']->table('sale_coupon'). " AS A LEFT JOIN "
//                .$GLOBALS['ecs']->table('coupons'). " AS B ON B.cpns_id = A.relation_cpns_id WHERE
//                        A.cpns_id = " .$check_cpns_id. " AND A.send_status = 1 AND B.start_time < unix_timestamp(now())
//                        AND unix_timestamp(now()) < B.end_time AND B.cpns_status = 1 AND B.cpns_gen_quantity < B.online_num";
            $relation_cpns_id_arr = $GLOBALS['db']->getAll($sql);

            //  要赠送的优惠券不为空
            if (!empty($relation_cpns_id_arr)){
                //  循环便利查询用户是否领过赠送券
                foreach ($relation_cpns_id_arr as $k => $v){
                    $sql = "SELECT id FROM " .$GLOBALS['ecs']->table('coupon_list'). " WHERE for_user_id = "
                        .$_SESSION['user_id']. " AND cpns_id = " .$v['relation_cpns_id'];
                    $check_id = $GLOBALS['db']->getOne($sql);
                    if (empty($check_id)){
                        //  给用户赠送优惠券
//                        try{
//                            $coupon = new \lib\logic\Coupons();
//                            $coupon->gift($_SESSION['user_id'],$v['relation_cpns_id'], $v['cpns_number']);
//                        }catch (\Exception $e){
//
//                        }
                        $uuid = uuid($v['cpns_prefix']);
                        $time = time();
                        $sql = "INSERT INTO " .$GLOBALS['ecs']->table('coupon_list').
                            "(cpns_id,sn,status,active_at,create_at,for_user_id)
                            VALUES(" .$v['relation_cpns_id']. ",'" .$uuid. "','1',$time,$time," .$order_status[user_id]. ")";
                        $GLOBALS['db']->query($sql);

                        //  记录优惠券配送数量
                        $sql = "UPDATE " .$GLOBALS['ecs']->table('coupons'). " SET cpns_gen_quantity = cpns_gen_quantity+1
                                    WHERE cpns_id = " .$v['relation_cpns_id'];
                        $GLOBALS['db']->query($sql);
                    } else{
                        break;
                    }
                }
            }
        }
        //  end


        $sql = "UPDATE " .$GLOBALS['ecs']->table('order_info'). " SET order_status = 1, pay_time= '$pay_time',pay_log='$paylog', pay_name = '$payname'
                        WHERE order_sn = '" .$order_sn. "' OR parent_id = '" .$order_status['order_id']. "'";
        $update_order_info = $GLOBALS['db']->query($sql);
        if (!$update_order_info){
            show_msg('您的订单信息有误，请重新选择!', '回到上一页', 'flow.php?step=checkout', 'error');
        }

        //  找出会员的上一级会员  liujiyuan   2016-1-30   START
        $fx_member_id = empty($order_status['fx_member_id']) ? 0 : $order_status['fx_member_id'];
        $fx_money = empty($order_status['fx_money']) ? 0 : $order_status['fx_money'];

        // 如果上一级会员存在
        $two_fx_member_id = 0;
        if ($fx_member_id){
            //  找出会员的上上级会员  liujiyuan   2016-1-30
            $sql = "SELECT up_member_id FROM" .$GLOBALS['ecs']->table('fx_members').
                " WHERE member_id = " .$fx_member_id;
            $two_fx_member_id = $GLOBALS['db']->getOne($sql);
            $two_fx_member_id = empty($two_fx_member_id) ? 0 : $two_fx_member_id;
        }

        /*   给上级会员和上上级会员加佣金     liujiyuan   2016-1-28     */
        if ($fx_member_id > 0){
            $fx_member_id_money = $fx_money*0.8;    //上级会员佣金
            $fx_member_id_money = empty($fx_member_id_money) ? 0 : round($fx_member_id_money,3);
            //  给上一级会员加佣金
            $sql = "UPDATE " .$GLOBALS['ecs']->table('shop'). " SET
                                    cashing_money = cashing_money+'" .$fx_member_id_money. "'
                                    ,total_financial = total_financial+'" .$fx_member_id_money. "'
                                    WHERE user_id = '" .$fx_member_id. "'";
            $GLOBALS['db']->query($sql);

            if ($two_fx_member_id){
                $tow_fx_member_id_money = $fx_money*0.2;    //上上级会员佣金
                $tow_fx_member_id_money = empty($tow_fx_member_id_money) ? 0 : round($tow_fx_member_id_money,3);
                //  给上一级会员加佣金
                $sql = "UPDATE " .$GLOBALS['ecs']->table('shop'). " SET
                                    cashing_money = cashing_money+'" .$tow_fx_member_id_money. "'
                                    ,total_financial = total_financial+'" .$tow_fx_member_id_money. "'
                                    WHERE user_id = '" .$two_fx_member_id. "'";
                $GLOBALS['db']->query($sql);
            }
        }
        //      END

        //  取出子订单的父ID和用户ID
        $sql = "SELECT order_id,user_id,pay_time FROM " .$GLOBALS['ecs']->table('order_info'). " WHERE order_sn = '" .$order_sn. "'";
        $order_arr = $GLOBALS['db']->getRow($sql);

        //  取出用户的idcard
        $sql = "SELECT idcard FROM " .$GLOBALS['ecs']->table('users'). " WHERE user_id = " .$order_arr['user_id'];
        $users_arr = $GLOBALS['db']->getRow($sql);

        $sql = "SELECT order_sn,order_amount,order_total,coupon_money,tax,shipping_fee,consignee,ship_area,address,mobile,add_time,pay_time,tel,wms_id  FROM "
            .$GLOBALS['ecs']->table('order_info'). " WHERE parent_id = " .$order_arr['order_id'];
        $son_data = $GLOBALS['db']->getAll($sql);
        //  如果没有子订单,就取总单
        if (empty($son_data)){
            $sql = "SELECT order_sn,order_amount,order_total,coupon_money,tax,shipping_fee,consignee,ship_area,address,mobile,add_time,pay_time,tel,wms_id  FROM "
                .$GLOBALS['ecs']->table('order_info'). " WHERE order_sn = '" .$order_sn. "'";
            $son_data = $GLOBALS['db']->getAll($sql);
        }

        $goods_information = array();
        foreach ($son_data as $k => $v){
            $sql = "SELECT A.rec_id,A.order_no,A.goods_id,A.goods_sn,A.goods_name,A.goods_price,A.market_price,
              (A.goods_price * A.goods_number) AS goods_amount, A.goods_number,B.unit,B.tax_number,C.cat_name,D.brand_name FROM "
                .$GLOBALS['ecs']->table('order_goods'). " AS A LEFT JOIN " .$GLOBALS['ecs']->table('goods').
                " AS B ON A.goods_id = B.goods_id LEFT JOIN " .$GLOBALS['ecs']->table('category'). " AS C ON
               C.cat_id = B.cat_id LEFT JOIN " .$GLOBALS['ecs']->table('brand'). " AS D ON D.brand_id = B.brand_id
               WHERE A.order_no = '" .$v['order_sn']. "'";

            $order_goods = $GLOBALS['db']->getAll($sql);
            foreach ($order_goods as $k1 => $v1){
                $tax_rate = get_taxrate_by_goods_id($v1['goods_id']);
                $goods_information[$k1] = array(
                    'tax_rate' => !empty($tax_rate) ? $tax_rate : 0,
                    'item_id' => $v1['rec_id'],
                    'order_id' => $v1['order_no'],
                    'goods_id' => $v1['goods_id'],
                    'bn' => $v1['goods_sn'],
                    'name' => formatpoststr($v1['goods_name']),
                    'price' => $v1['goods_price'],
                    'g_price' => $v1['market_price'],
                    'amount' => $v1['goods_amount'],
                    'nums' => $v1['goods_number'],
                    'unit' => $v1['unit'],
                    'typename' => formatpoststr($v1['cat_name']),
                    'brand_name' => formatpoststr($v1['brand_name']),
                    'taxnumber' => $v1['tax_number'],
                    'obj_id' => '0',
                    'product_id' => '0',
                    'type_id' => '0',
                    'cost' => '0',
                    'score' => '0',
                    'weight' => '0',
                    'sendnum' => '0',
                    'addon' => '0',
                    'item_type' => '0',
                    'spec_info' => '0',
                    'customs_price' => '0'
                );
            }

            $str_data['order'] = array(
                'order_id' => $v['order_sn'],
                'wms_id' => $v['wms_id'],
                'store_id' => '82',
                'payed' => $v['order_amount'],
                'ship_name' => $v['consignee'],
                'ship_area' => $v['ship_area'],
                'ship_addr' => $v['address'],
                'ship_zip' => '',
                'ship_tel' => $v['tel'],
                'ship_mobile' => $v['mobile'],
                'idcard' => $users_arr['idcard'],
                'idcardname' => $v['consignee'],
                'create_time' => date('Y-m-d H:i:s', $v['add_time']),
                't_confirm' => date('Y-m-d H:i:s', $order_arr['pay_time']),
                'api_key' => wms_key(),
                'discountvalue' => $v['coupon_money'],
                'order_total' => $v['order_total'],
                'tax' => $v['tax'],
                'shipping_fee' => $v['shipping_fee'],
                'cost_item' => "0",
                'pmtgoods' => "0",
                'pmtorder' => "0",
                'pay_status' => "0",
                'shipping' => "0",
                'member_id' => "0",
                'memo' => "0",
                'mark_text' => "0",
                'currency' => "0",
                'uname' => "0",
                'address' => "0",
                'phone' => "0",
                'mobile' => "0",
                'tel' => "0",
                'email' => "0",
                'item' => $goods_information,
            );
            $strJson = json_encode($str_data);
            $data_url = "strJson=" . $strJson;

            post_order(ORDER_API,$data_url);
            unset($goods_information);
            unset($strJson);
            unset($data_url);
        }
    }
}

function repost()
{
	
		

		$sql = "SELECT order_sn,order_amount,consignee,ship_area,address,mobile,add_time,pay_time,tel,wms_id  FROM "
							.$GLOBALS['ecs']->table('order_info'). " WHERE order_sn in('201602143606157843',
'201602142853197432',
'201602144591827146',
'201602142875055247',
'201602140061031566',
'201602148296400411',
'201602142742152754',
'201602148282978502',
'201602143494552905',
'201602146934832418',
'201602142286010455',
'201602145518961800',
'201602144115208709',
'201602144778800616',
'201602148515417655',
'201602144808621211',
'201602149781321715',
'201602149475474250',
'201602145987103907',
'201602143100149916',
'201602144122528085',
'201602145776910740',
'201602146970848287',
'201602141653084229',
'201602148886276427',
'201602145372861153',
'201602148116476978')";
		$son_data = $GLOBALS['db']->getAll($sql);
							
				$goods_information = array();
				foreach ($son_data as $k => $v){
					$sql = "SELECT A.rec_id,A.order_no,A.goods_id,A.goods_sn,A.goods_name,A.goods_price,A.market_price,
              (A.goods_price * A.goods_number) AS goods_amount, A.goods_number,B.unit,B.tax_number,C.cat_name,D.brand_name FROM "
							.$GLOBALS['ecs']->table('order_goods'). " AS A LEFT JOIN " .$GLOBALS['ecs']->table('goods').
							" AS B ON A.goods_id = B.goods_id LEFT JOIN " .$GLOBALS['ecs']->table('category'). " AS C ON
               C.cat_id = B.cat_id LEFT JOIN " .$GLOBALS['ecs']->table('brand'). " AS D ON D.brand_id = B.brand_id
               WHERE A.order_no = '" .$v['order_sn']. "'";

							$order_goods = $GLOBALS['db']->getAll($sql);
							foreach ($order_goods as $k1 => $v1){
								$tax_rate = get_taxrate_by_goods_id($v1['goods_id']);
								$goods_information[$k1] = array(
										'tax_rate' => !empty($tax_rate) ? $tax_rate : 0,
										'item_id' => $v1['rec_id'],
										'order_id' => $v1['order_no'],
										'goods_id' => $v1['goods_id'],
										'bn' => $v1['goods_sn'],
										'name' => $v1['goods_name'],
										'price' => $v1['goods_price'],
										'g_price' => $v1['market_price'],
										'amount' => $v1['goods_amount'],
										'nums' => $v1['goods_number'],
										'unit' => $v1['unit'],
										'typename' => $v1['cat_name'],
										'brand_name' => $v1['brand_name'],
										'taxnumber' => $v1['tax_number'],
										'obj_id' => '0',
										'product_id' => '0',
										'type_id' => '0',
										'cost' => '0',
										'score' => '0',
										'weight' => '0',
										'sendnum' => '0',
										'addon' => '0',
										'item_type' => '0',
										'spec_info' => '0',
										'customs_price' => '0'
								);
							}

							$str_data['order'] = array(
									'order_id' => $v['order_sn'],
									'wms_id' => $v['wms_id'],
									'store_id' => '82',
									'payed' => $v['order_amount'],
									'ship_name' => $v['consignee'],
									'ship_area' => $v['ship_area'],
									'ship_addr' => $v['address'],
									'ship_zip' => '',
									'ship_tel' => $v['tel'],
									'ship_mobile' => $v['mobile'],
									'idcard' => $users_arr['idcard'],
									'idcardname' => $v['consignee'],
									'create_time' => date('Y-m-d H:i:s', $v['add_time']),
									't_confirm' => date('Y-m-d H:i:s', $order_arr['pay_time']),
									'api_key' => wms_key(),
									'discountvalue' => "0",
									'cost_item' => "0",
									'pmtgoods' => "0",
									'pmtorder' => "0",
									'pay_status' => "0",
									'shipping' => "0",
									'member_id' => "0",
									'memo' => "0",
									'mark_text' => "0",
									'currency' => "0",
									'uname' => "0",
									'address' => "0",
									'phone' => "0",
									'mobile' => "0",
									'tel' => "0",
									'email' => "0",
									'item' => $goods_information,
							);
							$strJson = json_encode($str_data);
							$data_url = "strJson=" . $strJson;
							echo $data_url."<br>";
							//post_order(ORDER_API,$data_url);
							unset($goods_information);
							unset($strJson);
							unset($data_url);
				}

}

function push_order_only($order_sn_no)
{
	
	$order_sn = $order_sn_no;


		$pay_time = time();
		$str_data = array();



		//  取出子订单的父ID和用户ID
		$sql = "SELECT order_id,user_id,pay_time FROM " .$GLOBALS['ecs']->table('order_info'). " WHERE order_sn = '" .$order_sn. "'";
		$order_arr = $GLOBALS['db']->getRow($sql);

		//  取出用户的idcard
		$sql = "SELECT idcard FROM " .$GLOBALS['ecs']->table('users'). " WHERE user_id = " .$order_arr['user_id'];
		$users_arr = $GLOBALS['db']->getRow($sql);

		$sql = "SELECT order_sn,order_amount,order_total,coupon_money,tax,shipping_fee,consignee,ship_area,address,mobile,add_time,pay_time,tel,wms_id  FROM "
				.$GLOBALS['ecs']->table('order_info'). " WHERE parent_id = " .$order_arr['order_id'];
				$son_data = $GLOBALS['db']->getAll($sql);
				//  如果没有子订单,就取总单
				if (empty($son_data)){
					$sql = "SELECT order_sn,order_amount,order_total,coupon_money,tax,shipping_fee,consignee,ship_area,address,mobile,add_time,pay_time,tel,wms_id  FROM "
							.$GLOBALS['ecs']->table('order_info'). " WHERE order_sn = '" .$order_sn. "'";
							$son_data = $GLOBALS['db']->getAll($sql);
				}

				$goods_information = array();
				foreach ($son_data as $k => $v){
					echo $v['order_sn']."<br>";
					$sql = "SELECT A.rec_id,A.order_no,A.goods_id,A.goods_sn,A.goods_name,A.goods_price,A.market_price,
              (A.goods_price * A.goods_number) AS goods_amount, A.goods_number,B.unit,B.tax_number,C.cat_name,D.brand_name FROM "
							.$GLOBALS['ecs']->table('order_goods'). " AS A LEFT JOIN " .$GLOBALS['ecs']->table('goods').
							" AS B ON A.goods_id = B.goods_id LEFT JOIN " .$GLOBALS['ecs']->table('category'). " AS C ON
               C.cat_id = B.cat_id LEFT JOIN " .$GLOBALS['ecs']->table('brand'). " AS D ON D.brand_id = B.brand_id
               WHERE A.order_no = '" .$v['order_sn']. "'";

							$order_goods = $GLOBALS['db']->getAll($sql);
							foreach ($order_goods as $k1 => $v1){
								$tax_rate = get_taxrate_by_goods_id($v1['goods_id']);
								$goods_information[$k1] = array(
										'tax_rate' => !empty($tax_rate) ? $tax_rate : 0,
										'item_id' => $v1['rec_id'],
										'order_id' => $v1['order_no'],
										'goods_id' => $v1['goods_id'],
										'bn' => $v1['goods_sn'],
										'name' => formatpoststr($v1['goods_name']),
										'price' => $v1['goods_price'],
										'g_price' => $v1['market_price'],
										'amount' => $v1['goods_amount'],
										'nums' => $v1['goods_number'],
										'unit' => $v1['unit'],
										'typename' => formatpoststr($v1['cat_name']),
										'brand_name' => formatpoststr($v1['brand_name']),
										'taxnumber' => $v1['tax_number'],
										'obj_id' => '0',
										'product_id' => '0',
										'type_id' => '0',
										'cost' => '0',
										'score' => '0',
										'weight' => '0',
										'sendnum' => '0',
										'addon' => '0',
										'item_type' => '0',
										'spec_info' => '0',
										'customs_price' => '0'
								);
							}

							$str_data['order'] = array(
									'order_id' => $v['order_sn'],
									'wms_id' => $v['wms_id'],
									'store_id' => '82',
									'payed' => $v['order_amount'],
									'ship_name' => $v['consignee'],
									'ship_area' => $v['ship_area'],
									'ship_addr' => $v['address'],
									'ship_zip' => '',
									'ship_tel' => $v['tel'],
									'ship_mobile' => $v['mobile'],
									'idcard' => $users_arr['idcard'],
									'idcardname' => $v['consignee'],
									'create_time' => date('Y-m-d H:i:s', $v['add_time']),
									't_confirm' => date('Y-m-d H:i:s', $order_arr['pay_time']),
									'api_key' => wms_key(),
                                    'discountvalue' => $v['coupon_money'],
                                    'order_total' => $v['order_total'],
                                    'tax' => $v['tax'],
                                    'shipping_fee' => $v['shipping_fee'],
									'cost_item' => "0",
									'pmtgoods' => "0",
									'pmtorder' => "0",
									'pay_status' => "0",
									'shipping' => "0",
									'member_id' => "0",
									'memo' => "0",
									'mark_text' => "0",
									'currency' => "0",
									'uname' => "0",
									'address' => "0",
									'phone' => "0",
									'mobile' => "0",
									'tel' => "0",
									'email' => "0",
									'item' => $goods_information,
							);
							$strJson = json_encode($str_data);
							$data_url = "strJson=" . $strJson;
							//echo $data_url."<br>"."==============================================================================";

							post_order(ORDER_API,$data_url);
							unset($goods_information);
							unset($strJson);
							unset($data_url);
				}

}

function formatpoststr($str){
	
	$str=str_replace("“", " ", $str);
	$str=str_replace("”", " ", $str);
	$str=str_replace("&", " ",$str);
	$str=str_replace("-", " ",$str);
	$str=str_replace("/", " ",$str);
	
	return $str;
}

//  生成优惠券的券码    wangwenzhang    2016-4-11   12:09
function uuid($cpns_prefix, $hyphen = '-', $serial = null)
{
    $serial = null;
    $flag = is_null($serial) ? $cpns_prefix : $cpns_prefix.$serial;

    $charid = md5(uniqid($flag, true));
    $uuid = substr($charid, 0, 8).$hyphen
        .substr($charid, 8, 4).$hyphen
        .substr($charid,12, 4).$hyphen
        .substr($charid,16, 4).$hyphen
        .substr($charid,20,12);

    return $uuid;
}


?>