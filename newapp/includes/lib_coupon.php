<?php

/**
 * ECSHOP 购物流程函数库
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: tao.wang 2016-03-31 18:10:28 $
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

/**
 * 获取优惠券数目 tao.wang 2016-04-01 11:30:12
 * @param unknown $array
 * @return unknown
 */
function getConponCount($array=array()){
    $sql = "SELECT count(*) as count FROM  " .$GLOBALS['ecs']->table('coupon_list'). " a LEFT JOIN " .$GLOBALS['ecs']->table('coupons'). " b on a.cpns_id = b.cpns_id WHERE 1=1 ";
    $sqladd = _getSql($array);
    $sql.=$sqladd;
//     print_r($sql);
    $count = $GLOBALS['db']->getOne($sql);
    return $count;
}

/**
 * 获取优惠券列表 tao.wang 2016-04-01 11:30:12
 * @param unknown $array
 * @return unknown
 */
function getConponList($array=array(),$file='*',$limit=''){
    $sql = "SELECT $file FROM  " .$GLOBALS['ecs']->table('coupon_list'). " a LEFT JOIN " .$GLOBALS['ecs']->table('coupons'). " b on a.cpns_id = b.cpns_id WHERE 1=1 ";
    $sqladd = _getSql($array);
    $sql.=$sqladd;
    $sql.=$limit;
//     print_r($sql);
    $res = $GLOBALS['db']->getAll($sql);
    return $res;
}

function _getSql($array){
    $sql = '';
    if (!empty($array['user_id'])) {
        $sql.=' AND a.for_user_id='.$array['user_id'];
    }
    if (!empty($array['coupon_status'])) {
        switch ($array['coupon_status']){
            case 1://未使用的
                $sql.= ' AND a.status = 1  AND b.cpns_status = 1 AND a.for_order_id =0 AND b.end_time > '.time().' AND ( a.limit_at >'.time().' OR b.limit_data = 0 )';
//                $sql.= ' AND a.status = 1  AND a.for_order_id =0 AND b.start_time <'.time().' AND b.end_time > '.time().' AND ( a.limit_at >'.time().' OR b.limit_data = 0 )';
                break;
            case 2://已使用的
                $sql.= ' AND a.status = 1 AND b.cpns_status = 1 AND a.for_order_id !=0 ';
                break;
            case 3://已过期的
            default :
                $sql.= ' AND a.status = 1 AND b.cpns_status = 1 AND a.for_order_id =0 AND (b.end_time  <'.time().' OR ( a.limit_at <'.time().' AND a.limit_at != 0 ))';
                break;
        }
    }
    return $sql;
}

/**
 * 注册送券
 * @return void
 */
function register_coupon($user_id){
    if(empty($user_id)){
        return false;
    }
    $now = time();
    $check_where[] = 's.send_status = 1';
    $check_where[] = 's.cpns_id = 0';
    $check_where[] = 's.start_time<='.$now;
    $check_where[] = 's.end_time>='.$now;
    $where = implode(' AND ', $check_where);
    $check_sql = "SELECT s.relation_cpns_id,s.cpns_number,c.cpns_prefix,c.limit_data FROM ".$GLOBALS['ecs']->table('sale_coupon')." s
                  inner join ".$GLOBALS['ecs']->table('coupons')." c
                  on s.relation_cpns_id=c.cpns_id
                  WHERE ".$where;
    $sale_coupon = $GLOBALS['db']->getAll($check_sql);
    if(!empty($sale_coupon)){
        foreach($sale_coupon as $val){
            $limit_at = ($val['limit_data']=='0') ? 0 : ($val['limit_data']*24*60*60+strtotime(date('Y-m-d').' 23:59:59'));
            for($a=0;$a<$val['cpns_number'];$a++){
                //送劵
                $sn = uuid($val['cpns_prefix']);
                $add_sql = "INSERT INTO ".$GLOBALS['ecs']->table('coupon_list')." SET cpns_id=".$val['relation_cpns_id'].",create_at=".$now." ,active_at=" .$now." ,limit_at=".$limit_at.",for_user_id=".$user_id.", sn='".$sn."'";
                $GLOBALS['db']->query($add_sql);
                //送券数量+1
                $update_sql = "UPDATE ".$GLOBALS['ecs']->table('coupons')." SET cpns_gen_quantity=cpns_gen_quantity+1 WHERE cpns_id=".$val['relation_cpns_id'];
                $GLOBALS['db']->query($update_sql);    
            }
        }
    }
}
/**
 * 优惠劵sn生成方式
 * @param  string $cpns_prefix 前缀
 * @param  string $hyphen
 * @param  string $serial
 * @return
 */
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

/**
 * 获取用户可用的优惠券
 * @param  string $user_id 用户id
 * @return array 二维数组
 * @author liujiyuan    2016-4-20   14:21
 */
function get_user_coupon($user_id)
{
    if(empty($user_id)){
        return false;
    }

    $sql = "SELECT A.cpns_id,A.for_user_id,B.cpns_name,B.cpns_name,B.condition,B.processing
        FROM " .$GLOBALS['ecs']->table('coupon_list'). " AS A LEFT JOIN " .$GLOBALS['ecs']->table('coupons').
        "AS B ON B.cpns_id = A.cpns_id WHERE A.for_user_id = '" .$user_id. "' AND A.for_order_id = 0 AND A.status = 1
        AND B.cpns_status = 1 AND B.start_time < unix_timestamp(now()) AND B.end_time > unix_timestamp(now())" ;
    $result = $GLOBALS['db']->getAll($sql);
    return $result;
}

/**
 * 获取优惠券指定优惠的商品
 * @param  string $cpns_id 优惠券id
 * @return array 一维数组的字符串
 * @author liujiyuan    2016-4-20   14:21
 */
function get_coupon_goods($cpns_id)
{
    if(empty($cpns_id)){
        return false;
    }

    $sql = "SELECT Group_concat(discount_id) AS goods_id FROM " .$GLOBALS['ecs']->table('coupon_scene'). " WHERE cpns_id = '" .$cpns_id. "'";
    $result = $GLOBALS['db']->getRow($sql);
    return $result;
}


?>
