<?php
/**
 * Created by PhpStorm.
 * User: jiayi
 * Date: 2017/4/18
 * Time: 10:59
 */
namespace ylt\redsupplier\controller;
use ylt\redsupplier\logic\OrderLogic;
use think\AjaxPage;
use think\Page;
use think\Db;
use think\Url;
use think\Request;

class Order extends Base
{
    public $order_status;
    public $pay_status;
    public $shipping_status;

    /*
     * 初始化操作
     */
    public function _initialize()
    {
        parent::_initialize();
        config('TOKEN_ON', false); // 关闭表单令牌验证
        $this->order_status = config('ORDER_STATUS');
        $this->pay_status = config('PAY_STATUS');
        $this->shipping_status = config('SHIPPING_STATUS');
        // $this->close = config('CLOSE');
        // 订单 支付 发货 结算状态
        // $this->assign('close', $this->close);
        $this->assign('order_status', $this->order_status);
        $this->assign('pay_status', $this->pay_status);
        $this->assign('shipping_status', $this->shipping_status);
    }


    /*
    *订单首页
    */
    public function index(){
        $begin = date('Y-m-d',strtotime("-1 year"));//30天前
        $end = date('Y/m/d',strtotime('+1 days'));
        $this->assign('timegap',$begin.'-'.$end);
        return $this->fetch();
    }


    /*
     *Ajax首页数据
     */
    public function ajaxindex(){
        $orderLogic = new OrderLogic();
        $timegap = I('timegap');
        if($timegap){
            $gap = explode('-', $timegap);
            $begin = strtotime($gap[0]);
            $end = strtotime($gap[1]);
        }else{
            //@new 新后台UI参数
            $begin = strtotime(I('add_time_begin'));
            $end = strtotime(I('add_time_end'));
        }
        // 搜索条件
        $condition = array();
        $keyType = input("keytype");
        $keywords = I('keywords','','trim');
        // $condition['red_supplier_id'] = session('red_admin_id');
        $where = " red_supplier_id =".session('red_admin_id')."  OR is_group =1";
		$condition['is_parent'] = 0;

        $keywords =  $keywords ? $keywords : false;
        $keywords ? $condition[''.$keyType.''] = trim($keywords) : false;


        if($begin && $end){
            $condition['add_time'] = array('between',"$begin,$end");
        }


        I('order_status') != '' ? $condition['order_status'] = I('order_status') : false;
        I('pay_status') != '' ? $condition['pay_status'] = I('pay_status') : false;
        I('pay_code') != '' ? $condition['pay_code'] = I('pay_code') : false;
        I('shipping_status') != '' ? $condition['shipping_status'] = I('shipping_status') : false;
        I('close') != '' ? $condition['close'] = I('close') : false;
        I('user_id') ? $condition['user_id'] = trim(I('user_id')) : false;
        $sort_order = I('order_by','DESC').' '.I('sort');
        $count = Db::name('red_order')->where($condition)->count();
        $Page  = new AjaxPage($count,20);
        //  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            if($key == 'add_time'){
                $between_time = explode(',',$val[1]);
                $parameter_add_time = date('Y/m/d',$between_time[0]) . '-' . date('Y/m/d',$between_time[1]);
                $Page->parameter['timegap'] = $parameter_add_time;
            }else{
                $Page->parameter[$key]   =  urlencode($val);
            }
        }
        $show = $Page->show();
        //获取订单列表
        $orderList = $orderLogic->getOrderList($condition,$sort_order,$Page->firstRow,$Page->listRows,$where);
        $this->assign('orderList',$orderList);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
    }


    /**
     * 订单详情
     * @param int $id 订单id
     */
    public function detail($order_id){ 
		 
        $orderLogic = new OrderLogic();
        $order = $orderLogic->getOrderInfo($order_id);
        if($order['red_supplier_id'] != session('red_admin_id') and $order['is_group'] != 1 ){
           $this->error('非法操作',Url::build('redsupplier/Admin/logout'));
        }
        $orderGoods = $orderLogic->getOrderGoods($order_id);
        $button = $orderLogic->getOrderButton($order);
        $orderGoods_group = get_group_goods($orderGoods); //查询是否为红礼组合礼包商品

        // 获取操作记录
        $action_log = Db::name('order_action')->where(array('order_id'=>$order_id))->order('log_time desc')->select();
        $userIds = array();
        //查找用户昵称
        foreach ($action_log as $k => $v){
            $userIds[$k] = $v['action_user'];
        }
        if($userIds && count($userIds) > 0){
            $users = Db::name("users")->where("user_id in (".implode(",",$userIds).")")->column("user_id , nickname");
        }
		$shipping = Db::name('red_shipping_order')->where('order_id',$order_id)->select();
        $this->assign('users',$users);
        $this->assign('order',$order);
        $this->assign('action_log',$action_log); 
        $this->assign('orderGoods',$orderGoods_group);
        $split = count($orderGoods) >1 ? 1 : 0;
        if ($orderGoods) {
            foreach ($orderGoods as $val){
                if($val['goods_num']>1){
                    $split = 1;
                }
            }
        }
		if($shipping){
			foreach ($shipping as $key => $value){
				$shipping[$key]['exp'] = json_decode($value['logistics_information'],true); 
			}
					
		}
		$this->assign('shipping',$shipping);
        $this->assign('split',$split);
        $this->assign('button',$button);
        return $this->fetch();
    }
	
	
	 /**
     * 订单操作
     * @param $id
     */
    public function order_action(){
        $orderLogic = new OrderLogic();
        $action = I('get.type');
        $order_id = I('get.order_id');
        if($action && $order_id){
            if($action !=='pay'){
                // $orderLogic->orderActionLog($order_id,$action,I('note'));
            }
            $a = $orderLogic->orderProcessHandle($order_id,$action,array('note'=>I('note'),'red_admin_id'=>0));
            if( $a !== false){
                if ($action == 'remove') {
                    exit(json_encode(array('status' => 1, 'msg' => '操作成功', 'data' => array('url' => Url::build('redsupplier/order/index')))));
                }
                exit(json_encode(array('status' => 1,'msg' => '操作成功')));
            }else{
                if ($action == 'remove') {
                    exit(json_encode(array('status' => 0, 'msg' => '操作失败', 'data' => array('url' => Url::build('redsupplier/order/index')))));
                }
                exit(json_encode(array('status' => 0,'msg' => '操作失败')));
            }
        }else{
            $this->error('参数错误',Url::build('redsupplier/Order/detail',array('order_id'=>$order_id)));
        }
    }
	
	
	/*
     * 价钱修改
     */
    public function editprice($order_id){
        $orderLogic = new OrderLogic();
        $order = $orderLogic->getOrderInfo($order_id);
        $this->editable($order);
        if(IS_POST){
        	$red_admin_id = session('red_admin_id');
            if(empty($red_admin_id) && $order['red_supplier_id'] !=session('red_admin_id')){
                $this->error('非法操作');
                exit;
            }
            $update['discount'] = I('post.discount');
            $update['shipping_price'] = I('post.shipping_price');
			$update['red_order_amount'] = $order['red_total_price'] + $update['shipping_price'] - $update['discount'] - $order['user_money'] - $order['integral_money'] - $order['coupon_price'];
            $row = Db::name('red_order')->where(array('order_id'=>$order_id))->update($update);
            if(!$row){
                $this->success('数据没有更新',Url::build('redsupplier/Order/editprice',array('order_id'=>$order_id)));
            }else{
                $this->success('操作成功',Url::build('redsupplier/Order/detail',array('order_id'=>$order_id)));
            }
            exit;
        }
        $this->assign('order',$order);
        return $this->fetch();
    }
	
	
	/**
     * 检测订单是否可以编辑
     * @param $order
     */
    private function editable($order){
        if($order['shipping_status'] != 0){
            $this->error('已发货订单不允许编辑');
            exit;
        }
        return;
    }
	
	
	  /**
     * @return 发货
     */
    public function delivery_info(){
        $order_id = I('order_id');
        $orderLogic = new OrderLogic();
        $order = $orderLogic->getOrderInfo($order_id);
		if($order['red_supplier_id'] != session('red_admin_id')){
           $this->error('非法操作',Url::build('redsupplier/Admin/logout'));
        }
        $orderGoods = $orderLogic->getOrderGoods($order_id);
        $orderGoods_group = get_group_goods($orderGoods); //查询是否为红礼组合礼包商品
        $delivery_record = Db::name('red_shipping_order')->alias('d')->join('redsupplier_user a','a.red_admin_id = d.admin_id')->where('d.order_id='.$order_id)->select();
        if($delivery_record){
            $order['invoice_no'] = $delivery_record[count($delivery_record)-1]['invoice_no'];
        }
        $shipping = Db::name('plugin')->where('type','shipping')->select();
        $this->assign('shipping',$shipping);
        $this->assign('order',$order);
        $this->assign('orderGoods',$orderGoods_group);
        $this->assign('delivery_record',$delivery_record);//发货记录
        return $this->fetch();
    }


    /**
     * 生成发货单
     */
    public function deliveryHandle(){
        $orderLogic = new OrderLogic();
        $data = I('post.');
        $res = $orderLogic->deliveryHandle($data);
        if($res){
            $this->success('操作成功',Url::build('redsupplier/Order/detail',array('order_id'=>$data['order_id'])));
        }else{
            $this->success('操作失败',Url::build('redsupplier/Order/delivery_info',array('order_id'=>$data['order_id'])));
        }
    }
	


    /**
     * 订单删除
     * @param int $id 订单id
     */
    public function delete_order($order_id){
        $orderLogic = new OrderLogic();
        $del = $orderLogic->delOrder($order_id);
        if($del){
            $this->success('删除订单成功');
        }else{
            $this->error('订单删除失败');
        }
    }


	
	// /**
 //     * 订单打印
 //     * @param int $id 订单id
 //     */
 //    public function order_print(){
 //    	$order_id = I('order_id');
 //        $orderLogic = new OrderLogic();
 //        $order = $orderLogic->getOrderInfo($order_id);
 //        $order['full_address'] = $order['province'].' '.$order['city'].' '.$order['district'].' '. $order['address'];
 //        $orderGoods = $orderLogic->getOrderGoods($order_id);
 //        $shop = tpCache('shop_info');
 //        $this->assign('order',$order);
 //        $this->assign('shop',$shop);
 //        $this->assign('orderGoods',$orderGoods);
 //        $template = I('template','print');
 //        return $this->fetch($template);
 //    }
	
	
	/**
	 * 导出数据订单
	 */
	 public function export_order()
    {
    	//搜索条件
		$red_supplier_id = session('red_admin_id');
		$where = 'where red_supplier_id = '.$red_supplier_id.' ';
		$consignee = I('consignee');
		if($consignee){
			$where .= " AND consignee like '%$consignee%' ";
		}
		$order_sn =  I('order_sn');
		if($order_sn){
			$where .= " AND order_sn = '$order_sn' ";
		}
		if(I('order_status')){
			$where .= " AND order_status = ".I('order_status');
		}
		if(I('pay_status')){
			$where .= " AND pay_status  = ".I('pay_status');
		}
		if(I('pay_code')){
			if(I('pay_code') == 'alipay')
				$where .= " AND (pay_code = 'alipay' or pay_code = 'alipayMobile')";
			else
				$where .= " AND pay_code = ".I('pay_code');
		}
        if(I('shipping_status')){
            $where .= " AND shipping_status = ".I('shipping_status');
        }
        if(I('close')){
            $where .= " AND close = ".I('close');
        }
		if(I('add_time_begin')){
			$where .= " AND add_time > ". strtotime(I('add_time_begin'));
		}
		if(I('add_time_end')){
			$where .= " AND add_time < ". strtotime(I('add_time_end'));
		}
		    
		$sql = "select *,FROM_UNIXTIME(add_time,'%Y-%m-%d') as create_time from __PREFIX__red_order $where order by order_id";
    	$orderList = DB::query($sql);
    	$strTable ='<table width="500" border="1">';
    	$strTable .= '<tr>';
    	$strTable .= '<td style="text-align:center;font-size:12px;width:120px;">订单编号</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="100">日期</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">收货人</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">收货地址</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">电话</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">订单金额</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">实际支付</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">支付方式</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">支付状态</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">发货状态</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">结算状态</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">商品信息</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">交易流水单号</td>';
    	$strTable .= '</tr>';
	    if(is_array($orderList)){
	    	$region	= Db::name('region')->column('id,name');
	    	foreach($orderList as $k=>$val){
	    		$strTable .= '<tr>';
	    		$strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['order_sn'].'</td>';
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['create_time'].' </td>';	    		
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['consignee'].'</td>';
                        $strTable .= '<td style="text-align:left;font-size:12px;">'."{$region[$val['province']]},{$region[$val['city']]},{$region[$val['district']]},{$region[$val['twon']]}{$val['address']}".' </td>';                        
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['mobile'].'</td>';
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['goods_price'].'</td>';
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['order_amount'].'</td>';
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['pay_name'].'</td>';
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$this->pay_status[$val['pay_status']].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$this->shipping_status[$val['shipping_status']].'</td>';
	    		if($val['close']==1){$strTable .= '<td style="text-align:left;font-size:12px;">已结算</td>';}else{$strTable .= '<td style="text-align:left;font-size:12px;">未结算</td>';} 
	    		$orderGoods = Db::name('red_order_goods')->where('order_id='.$val['order_id'])->field('goods_sn,goods_name,spec_key_name')->select();
	    		$strGoods="";
	    		foreach($orderGoods as $goods){
	    			$strGoods .= "商品编号：".$goods['goods_sn']." 商品名称：".$goods['goods_name'];
	    			if ($goods['spec_key_name'] != '') $strGoods .= " 规格：".$goods['spec_key_name'];
	    			$strGoods .= "<br />";
	    		}
	    		unset($orderGoods);
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$strGoods.' </td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['transaction_id'].' </td>';
	    		$strTable .= '</tr>';
	    	}
	    }
    	$strTable .='</table>';
    	unset($orderList);
    	downloadExcel($strTable,'order');
    	exit();
    }
	
	

	
	/**
	 * 新订单提醒
	 */
	public function ajaxOrderNotice(){
        $order_amount = Db::name('red_order')->cache(true,1800)->where(array('order_status'=>0,'pay_status'=>1,'red_supplier_id'=>session('red_admin_id')))->count();
        echo $order_amount;
    }


}