<?php
/** 
 * 退货退款公共方法
 */
if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}
/**
 * 根据订单号获取退款信息
 * @param  string $order_no
 * @return
 */
function get_return_info($order_no,$user_id,$return_type='',$return_info_id=''){
    if(empty($field)){
        $field[] ='id';
        $field[] ='return_no';
        $field[] ='order_no';
        $field[] ='return_type';
        $field[] ='description';
        $field[] ='refund_reason';
        $field[] ='status';
        $field[] ='remark';
        $field[] ='remark1';
        $field[] ='logistics_com';
        $field[] ='logistics_no';
        $field[] ='logistics_money';
        $field[] ='return_money';
        $field[] ='img_logistics';
        $field[] ='img_url';
        $field[] ='logistics_description';
        $field[] ='FROM_UNIXTIME(logistics_time,"%Y-%m-%d %H:%i:%s") as logistics_time';
        $field[] ='FROM_UNIXTIME(create_time,"%Y-%m-%d %H:%i:%s") as create_time';
        $field[] ='FROM_UNIXTIME(confirm_time,"%Y-%m-%d %H:%i:%s") as confirm_time';
        $field[] ='FROM_UNIXTIME(finish_time,"%Y-%m-%d %H:%i:%s") as finish_time';
        $field[] ='FROM_UNIXTIME(cancel_time,"%Y-%m-%d %H:%i:%s") as cancel_time';
        $field[] ='is_upload';
        $field = implode(',',$field);
    }
    $where = '';
    if(!empty($return_type)){
        $where .= " AND return_type=".$return_type;
    }
    if(!empty($return_info_id)){
        $where .= " AND id=".$return_info_id;
    }
    $sql = "SELECT $field  FROM ".$GLOBALS['ecs']->table('return_info')." WHERE order_no='".$order_no."' AND user_id=".$user_id." {$where} ORDER BY id asc";

    $row = $GLOBALS['db']->getRow($sql);
    $list = array();
    if(!empty($row)){
        $row['status_zh'] = status_zh($row['return_type'],$row['status']);
        $list['row'] = $row;
        $list['list'] = return_list($row['return_type'],$row['status'],$row);
        $list['max_status'] = dis_css($row['return_type'],$row['status'],$row); 
        $list['last_list'] = last_list($row['return_type'],$row['status'],$row);
        $list['logis_list'] = logis_com($row);
    }
    return $list;
}
//退货进度最后的状态
function last_list($return_type,$status,$row){
    if($return_type==1){
        //退货
        switch ($status) {
            case '1':
                $r['time'] = $row['create_time'];
                $r['title'] = '等待洋货栈审核退货申请';
                $r['info'] = '请您保持手机畅通，我们的客服人员会尽快联系您处理。如果审核通过，退款申请将达成并退款到您的支付账户';
                $r['other_info'] = '<p><a class="form-btn small default" href="javascript:cancelRefund(\''.$row['order_no'].'\',\''.$row['id'].'\')">取消退货申请</a></p>';
                $r['css_class']='icon-time';
                $r['css_class_3']='c-green';
                break;
            case '0':
                $r['time'] = $row['cancel_time'];
                $r['title'] = '您已取消退货申请';
                $r['info'] = '感谢您对洋货栈的支持！';
                //<a class="form-btn small" style="margin-left:10px;" href="user.php?act=delete_return&order_sn='.$row['order_no'].'&return_info_id='.$row['id'].'">重新申请</a>
                $r['other_info'] = '<p><a class="form-btn small" href="/">去逛逛</a></p>';
                $r['css_class']='icon-ok-sign';
                $r['css_class_3']='c-green';
                break;
            case '2':
                $r['css_class']='icon-ok-sign';
                if(empty($row['logistics_no'])){
                    $r['time'] = $row['confirm_time'];
                    $r['title'] = '洋货栈已同意您退货申请，请尽快将商品寄回并填写物流信息';
                    $r['info'] = '';
                    $r['other_info'] = '<p>退货地址：深圳市宝安区福永福围中路32号百事威物流园FW109/209</p>
                                          <p>收件人：郁耕</p>
                                          <p>电话：0755-29350919</p>
                                          <p></p>
                                          <p></p>
                                          <p>
                                            <div class="pull-left"><a class="form-btn small" href="javascript:logistics()">填写物流信息</a></div>
                                            <!--<div class="pull-left ml10">剩余<span class="countdown" data-countdown="'.date('Y-m-d H:i:s',$row['pay_time']).'"></span><br>逾期未填写将自动关闭退货申请</div> -->
                                          </p>'; 
                    $r['css_class_3']='c-green';   
                }else{
                    $r['time'] = $row['logistics_time'];
                    $r['title'] = '等待洋货栈收货并检查商品';
                    $r['info'] = '';
                    $r['other_info'] = '<p>寄出快递单号：'.$row['logistics_no'].'</p>
                                      <p>快递公司：'.$row['logistics_com'].'</p>
                                      <p><a class="form-btn small" href="javascript:logistics()">修改寄回物流信息</a></p>';
                    $r['css_class_3']='c-green';
                }
                
                break;
            case '3':
                $r['time'] = $row['confirm_time'];
                $r['title'] = '洋货栈已拒绝您的退货申请';
                $r['info'] = '拒绝原因：'.$row['remark1'];
                $r['other_info'] = '';
                $r['css_class']='icon-remove-sign';
                $r['css_class_3']='c-main';
                break;
            case '4':
                $r['time'] = $row['finish_time'];
                $r['title'] = '商品检查合格，我们已经为您操作退款。';
                $r['info'] = '<p>洋货栈已经收货检查并为您操作退款</p><p>退款会在3-5个工作日退到您支付的账户，请您留意查收。</p><p>如果超过时限未收到退款，请您联系客服处理。</p>';
                $r['other_info'] = '<p><a class="form-btn small default" href="javascript:NTKF.im_openInPageChat()">联系客服</a></p>';
                $r['css_class']='icon-ok-sign';
                $r['css_class_3']='c-green';
                break;
            case '5':
                $r['time'] = $row['finish_time'];
                $r['title'] = '拒绝您退货申请';
                $r['info'] = '感谢您对洋货栈的支持！';
                $r['other_info'] = '<p><a class="form-btn small" href="index.html">去逛逛</a></p>';
                $r['css_class']='icon-remove-sign';
                $r['css_class_3']='c-main';
                break;
        }
    }else{
        //退款
        switch ($status) {
            case '2':
                $r['time'] = $row['create_time'];
                $r['title'] = '等待洋货栈受理退款申请';
                $r['info'] = '客服将会在1~2个工作日内受理您的退款申请，请耐心等待。';
                $r['other_info'] = '';
                $r['css_class']='icon-time';
                $r['css_class_3']='c-green';
                break;
            case '2':
                $r['time'] = $row['confirm_time'];
                $r['title'] = '洋货栈已同意您的退款申请';
                $r['info'] = '我们会尽快为您操作退款，请您注意查收';
                $r['other_info'] = '';
                $r['css_class']='icon-ok-sign';
                $r['css_class_3']='c-green';
                
                break;
            case '3':
                $r['time'] = $row['confirm_time'];
                $r['title'] = '洋货栈已拒绝您的退款申请';
                $r['info'] = '拒绝原因：'.$row['remark1'];
                $r['other_info'] = '';
                $r['css_class']='icon-remove-sign';
                $r['css_class_3']='c-main';
                break;
            case '4':
                $r['time'] = $row['finish_time'];
                $r['title'] = '洋货栈已为您操作退款';
                $r['info'] = '退款会在3-5个工作日退到您支付的账户，请您留意查收。如果超过时限未收到退款，请您联系客服处理。';
                $r['other_info'] = '<p><a class="form-btn small default" href="javascript:NTKF.im_openInPageChat()">联系客服</a></p>';
                $r['css_class']='icon-ok-sign';
                $r['css_class_3']='c-green';
                break;
            case '5':
                $r['time'] = $row['finish_time'];
                $r['title'] = '洋货栈已拒绝您的退款申请';
                $r['info'] = '感谢您对洋货栈的支持！';
                $r['other_info'] = '<p><a class="form-btn small" href="index.html">去逛逛</a></p>';
                $r['css_class']='icon-remove-sign';
                $r['css_class_3']='c-main';
                break;
        }
    }
    return $r;
}
function  status_zh($return_type,$status){
    if($return_type==2){
        $tmp = '退款';
    }else{
        $tmp = '退货';
    }
    switch ($status) {
        case '1':
            $r = '等待受理';
            break;
        case '2':
            if($return_type==2){
                $r = '等待受理';
            }else{
                $r = '受理通过';    
            }
            
            break;
        case '3':
            $r = '受理未通过';
            break;
        case '4':
            $r = $tmp.'通过';
            break;
        case '5':
            $r = $tmp.'不通过';
            break;
        case '0':
            $r = '已取消';
            break;
    }
    return $r;
}
/**
 * 根据退款或退货类型和状态返回状态流程列表
 * @param  int $return_type 1:退货 2：退款
 * @param  int $status      状态 1,等待受理 2,受理通过 3,受理未通过 4,退款通过 5,退款不通过
 * @return
 */
function return_list($return_type,$status,$row){
    if($return_type==1){
        //退货
        return return_list2($status,$row);
    }else{
        //退款
        return return_list1($status,$row);
    }
}
/**
 * 退款进度列表
 * @param  int $status 状态
 * @param  array $row
 * @return
 */
function return_list1($status,$row){
    $list = array(
        1=>array(
            'title'=>'等待洋货栈受理退款申请',
            'time'=>$row['create_time'],
            'info'=>'请您保持手机畅通，我们的客服人员会尽快联系您处理。如果审核通过，退款申请将达成并退款到您的支付账户',
            'css_class' => 'icon-time',
            'css_class_2' => 'icon-time',
            'css_class_3'=>'c-green',
            ),
        2=>array(
            'title'=>'洋货栈已同意您的退款申请',
            'time'=>$row['finish_time'],
            'info'=>'我们会尽快为您操作退款，请您注意查收',
            'css_class' => 'icon-ok-sign',
            'css_class_2' => 'icon-time',
            'css_class_3'=>'c-green',
            ),
        3=>array(
            'title'=>'洋货栈已拒绝您的退款申请',
            'time'=>$row['confirm_time'],
            'info'=>'拒绝原因：'.$row['remark1'],
            'css_class' => 'icon-remove-sign',
            'css_class_2' => 'icon-time',
            'css_class_3'=>'c-main',
            ),
        4=>array(
            'title'=>'洋货栈已为您操作退款',
            'time'=>$row['finish_time'],
            'info'=>'退款会在7-15个工作日退到您的账户，请您留意查收。如果超过时限未收到退款，请您联系客服处理。',
            'css_class' => 'icon-ok-sign',
            'css_class_2' => 'icon-time',
            'css_class_3'=>'c-green',
            ),
        5=>array(
            'title'=>'洋货栈已拒绝您的退款申请',
            'time'=>$row['finish_time'],
            'info'=>'拒绝原因：'.$row['remark1'],
            'css_class' => 'icon-remove-sign',
            'css_class_2' => 'icon-time',
            'css_class_3'=>'c-main',
            ),
        );
    $re = array();
    switch ($status) {
        case '1':
            //array_push($re, $list[1]);
            break;
        case '2':
            //array_push($re, $list[1]);
            //array_push($re, $list[2]);
            break;
        case '3':
            array_push($re, $list[1]);
            //array_push($re, $list[3]);
            break;
        case '4':
            array_push($re, $list[1]);
            array_push($re, $list[2]);
            //array_push($re, $list[4]);
            break;
        case '5':
            array_push($re, $list[1]);
            //array_push($re, $list[2]);
            //array_push($re, $list[5]);
            break;
        default:
            array_push($re, $list[1]);
            break;
    }
    return $re;
}
//退货
function return_list2($status,$row){
    $list = array(
        1=>array(
            'title'=>'等待洋货栈审核退货申请',
            'time'=>$row['create_time'],
            'info'=>'请您保持手机畅通，我们的客服人员会尽快联系您处理如果您不想退货了，您可以：',
            'step'=>1,
            'css_class' => 'icon-time',
            'css_class_2' => 'icon-time',
            'css_class_3'=>'c-green',
            'other_info'=>'<p><a class="form-btn small default" href="javascript:cancelRefund()">取消退货申请</a></p>',
            ),
        2=>array(
            'title'=>'您已经取消退货申请',
            'time'=>$row['create_time'],
            'info'=>'感谢您对洋货栈的支持！',
            'step'=>2,
            'css_class' => 'icon-ok-sign',
            'css_class_2' => 'icon-time',
            'css_class_3'=>'c-green',
            'other_info'=>'<p><a class="form-btn small" href="/">去逛逛</a></p>',
            ),
        3=>array(
            'title'=>'洋货栈已同意您退货申请',
            'time'=>$row['confirm_time'],
            'step'=>3,
            'info'=>'',
            'css_class' => 'icon-ok-sign',
            'css_class_2' => 'icon-time',
            'css_class_3'=>'c-green',
            'other_info'=>'<p>退货地址：深圳市宝安区福永福围中路32号百事威物流园FW109/209</p>
                  <p>收件人：郁耕</p>
                  <p>电话：0755-29350919</p>
                  <p></p>
                  <p></p>
                  <p>
                    <div class="pull-left"><a class="form-btn small" href="javascript:logistics()">填写物流信息</a></div>
                    <div class="pull-left ml10">剩余<span class="countdown" data-countdown="'.date('Y-m-d H:i:s',$row['pay_time']).'"></span><br>逾期未填写将自动关闭退货申请</div>
                  </p>',
            ),
        4=>array(
            'title'=>'洋货栈已拒绝您的退货申请',
            'time'=>$row['confirm_time'],
            'info'=>'拒绝原因：'.$row['remark1'],
            'css_class' => 'icon-remove-sign',
            'css_class_2' => 'icon-time',
            'css_class_3'=>'c-main',
            'other_info'=>'',
            ),
        5=>array(
            'title'=>'等待洋货栈收货并检查商品',
            'step'=>4,
            'time'=>$row['logistics_time'],
            'info'=>'退款会在7-15个工作日退到您的账户，请您留意查收。如果超过时限未收到退款，请您联系客服处理。',
            'css_class' => 'icon-ok-sign',
            'css_class_2' => 'icon-time',
            'css_class_3'=>'c-green',
            'other_info'=>'<p><a class="form-btn small" href="javascript:logistics()">修改寄回物流信息</a></p>',
            ),
        6=>array(
            'title'=>'商品检查合格，我们正在为您操作退款',
            'time'=>$row['finish_time'],
            'info'=>'',
            'css_class' => 'icon-ok-sign',
            'css_class_2' => 'icon-time',
            'css_class_3'=>'c-green',
            'other_info'=>'<p><a class="form-btn small default" href="javascript:NTKF.im_openInPageChat()">联系客服</a></p>',
            ),
        7=>array(
            'title'=>'您已寄回商品并填写物流信息',
            'time'=>$row['logistics_time'],
            'info'=>'',
            'css_class' => 'icon-ok-sign',
            'css_class_2' => 'icon-time',
            'css_class_3'=>'c-green',
            ),
        8=>array(
            'title'=>'洋货栈已收到商品',
            'time'=>$row['finish_time'],
            'info'=>'',
            'css_class' => 'icon-ok-sign',
            'css_class_2' => 'icon-time',
            'css_class_3'=>'c-green',
            ),
        );
    $re = array();
    switch ($status) {
        case '1':
            //array_push($re, $list[1]);
            break;
        case '2':
            array_push($re, $list[1]);
            //array_push($re, $list[3]);
            if(!empty($row['logistics_no'])){
                array_push($re, $list[7]);
            }
            break;
        case '3':
            array_push($re, $list[1]);
            //array_push($re, $list[4]);
            break;
        case '4':
            array_push($re, $list[1]);
            array_push($re, $list[3]);
            array_push($re, $list[7]);
            array_push($re, $list[5]);
            array_push($re, $list[8]);
            //array_push($re, $list[6]);
            break;
        case '5':
            array_push($re, $list[1]);
            array_push($re, $list[3]);
            array_push($re, $list[5]);
            break;
        default:
            array_push($re, $list[1]);
            break;
    }
    return $re;
}
/**
 * 根据状态选择进度样式
 * @param  int $status
 * @return
 */
function dis_css($type,$status,$row){
    if($type==2){
        //退款
        switch ($status) {
            case '1':
            case '2':
                $css = 'two';
                break;
            case '3':
            case '5':
                $css = 'three';
                break;
            case '4':
                $css = 'four';
                break;
            default:
               $css = 'one';
                break;
        }    
    }else{
        //退货
        switch ($status) {
            case '0':
            case '1':
            case '3':
                $css = 'two';
                break;
            case '2':
                if(!empty($row['logistics_no'])){
                    $css = 'four';
                }else{
                    $css = 'three';    
                }
                
                break;
            case '5':
                $css = 'four';
                break;
            case '4':
                $css = 'five';
                break;
            default:
               $css = 'one';
                break;
        }
    }
    
    return $css;
}
function get_return_by_id($id,$user_id){
    $sql = "SELECT id  FROM ".$GLOBALS['ecs']->table('return_info')." WHERE id='".$id."' AND user_id=".$user_id;
    return $GLOBALS['db']->getOne($sql);
}
/**
 * 添加退款退货申请
 * @param array $data array[$field]=$val
 * @return int id
 */
function add_return_info($data){
    if(empty($data)){
        return false;
    }
    foreach($data as $field=>$val){
        $insert[] = '`'.$field.'`=\''.$val.'\'';
    }
    $insert_sql = "INSERT INTO ".$GLOBALS['ecs']->table('return_info')." SET ".implode(',', $insert);
    $ins = $GLOBALS['db']->query($insert_sql);
    if($ins){
        $id = $GLOBALS['db']->insert_id();
    }else{
        $id = false;   
    }
    return $id;
}
function update_return_info($data,$where){
    if(empty($where)){
        return false;
    }
    foreach($data as $field=>$v){
        $field_data[] = $field.'=\''.$v.'\'';
    }
    $sql = "update ".$GLOBALS['ecs']->table('return_info')." set ".implode(',', $field_data)."where ".$where." limit 1";
    return $GLOBALS['db']->query($sql);

}
/**
 * 添加退货商品
 * @param array  $goods_list 
 * @param string $order_no
 * @param string $add_id
 */
function add_return_goods($goods_list,$order_no,$add_id){
    if(empty($goods_list) || empty($order_no) || empty($add_id)){
        return -3;
    }
    if(is_array($order_no)){
        $order_no = "('".implode("','", $order_no)."')";
    }else{
        $order_no = "('".$order_no."')";
    }
    foreach($goods_list as $goods_id=>$v){
        $sql = "SELECT goods_id,product_id,goods_price,goods_number,goods_name,order_no 
                FROM ".$GLOBALS['ecs']->table('order_goods')."
                WHERE order_no in ".$order_no." AND goods_id=".$goods_id." group by goods_id";
        $row = $GLOBALS['db']->getRow($sql);

        if($row&&$row['goods_number']<$goods_list[$row['goods_id']]){
            return -1;
        }elseif($row&&$row['goods_number']>=$goods_list[$row['goods_id']]){
            unset($data);
            $data[] = "return_no ='".$row['order_no']."'";
            $data[] = "return_info_id = ".$add_id;
            $data[] = "goods_id = ".$row['goods_id'];
            $data[] = "product_id = ".$row['product_id'];
            $data[] = "goods_price = ".$row['goods_price'];
            $data[] = "goods_num = ".$goods_list[$row['goods_id']];
            $data[] = "goods_name = '".addslashes($row['goods_name'])."'";
            $add_sql = "INSERT INTO ".$GLOBALS['ecs']->table('return_goods')."
                        SET ".implode(',', $data);
            $GLOBALS['db']->query($add_sql);
        }else{
            return -2;
        }
    }
    return 1;
}
function get_return_goods($return_id){
    $sql = "SELECT rg.goods_name,rg.goods_id,rg.goods_price,rg.goods_num,
            (rg.goods_price*rg.goods_num) AS total_price, 
            concat('".IMG_HOST."','/',g.goods_thumb) as goods_img
            FROM ".$GLOBALS['ecs']->table('return_goods')." rg 
            INNER JOIN ".$GLOBALS['ecs']->table('goods')." g 
            ON rg.goods_id=g.goods_id
            WHERE rg.return_info_id=".$return_id;
    $goods = $GLOBALS['db']->getAll($sql);
    return $goods;
}
function get_by_type($order_sn,$user_id,$type,$return_info_id=''){
    if(!empty($return_info_id)){
        $where = " AND id=".$return_info_id;
    }
    $sql = "SELECT id  FROM ".$GLOBALS['ecs']->table('return_info')." WHERE order_no='".$order_sn."' AND user_id=".$user_id." AND return_type={$type} {$where} ORDER BY id asc";
    return $GLOBALS['db']->getOne($sql);
}
function get_field($order_sn,$user_id,$type,$field){
    $sql = "SELECT $field  FROM ".$GLOBALS['ecs']->table('return_info')." WHERE order_no='".$order_sn."' AND user_id=".$user_id." AND return_type={$type}  ORDER BY id asc";
    return $GLOBALS['db']->getRow($sql);
}
//检查订单是否满足退货条件
function check_pay_time($order_sn,$user_id){
    $sql = "select order_id,pay_time,order_status from ".$GLOBALS['ecs']->table('order_info')." where order_sn='".$order_sn."' and user_id=".$user_id;
    $order = $GLOBALS['db']->getRow($sql);

    //只有已发货和已收货能退货
    if(!in_array((string)$order['order_status'], array('3'))){
        return -1;
    }else{
        return 1;
    }
}
function logis_com($row){
    $list = array('申通快递','圆通快递','天天快递','百世汇通',/*'顺丰快递',*/'中通快递','宅急送','韵达快递','德邦快递','全峰快递','EMS','其他');
    $option = array();
    foreach($list as $v){
        if(!empty($row['logistics_com'])){
            if($row['logistics_com']==$v){
                $selected = $v;
            }elseif(!in_array($row['logistics_com'], $list)){
                $selected = '其他';
            }    
        }else{
            $selected = '';
        }
        
        array_push($option, array('name'=>$v,'selected'=>$selected));
    }
    if($selected=='其他'){
        $re['show'] = 1;
    }else{
        $re['show'] = 0;
    }
    $re['list'] = $option;
    return $re;
}
/**
 * 删除退款退货
 * @param  int $id
 * @param  int $user_id
 * @return
 */
function delete_return_info($id,$user_id){
    $sql = "delete from ".$GLOBALS['ecs']->table('return_info')." where id='".$id."' and user_id=".$user_id." limit 1";
    $GLOBALS['db']->query($sql);
    $sql = "delete from ".$GLOBALS['ecs']->table('return_goods')." where return_info_id=".$id;
    $GLOBALS['db']->query($sql);
}
/**
 * 查看订单已申请退款的商品和数量
 * @param  [sting] $order_no 
 * @param  [int] $user_id
 * @return
 */
function get_return_num_by_order($order_no,$user_id){
    $sql = "select sum(rg.goods_num) as goods_num,rg.goods_id from ".$GLOBALS['ecs']->table('return_goods')." rg
            inner join ".$GLOBALS['ecs']->table('return_info')." ri
            on ri.id=rg.return_info_id  
            where ri.order_no='".$order_no."' and ri.user_id=".$user_id."
            group by rg.goods_id";
    $data = $GLOBALS['db']->getAll($sql); 
    $list = array();
    foreach($data as $k=>$v){
        $list[$v['goods_id']] = $v['goods_num'];
    }
    return $list;
}
function update_order_return_status($order_sn,$user_id,$return_status){
    $sql = "SELECT order_id FROM ".$GLOBALS['ecs']->table('order_info')." WHERE order_sn='".$order_sn."' AND user_id=".$user_id;
    $order_id = $GLOBALS['db']->getOne($sql);
    if(!empty($order_id)){
        if($return_status==2){
            //30分钟退款时修改订单状态改成4
            //$tmp = ",order_status=4,cancle_reson='30分钟内退款'";
            $tmp = "";
        }
        $sql = "update ".$GLOBALS['ecs']->table('order_info')." set return_status={$return_status} {$tmp} where (order_id=".$order_id." or parent_id =".$order_id.") and user_id=".$user_id;
        $GLOBALS['db']->query($sql);
    }
}
?>
