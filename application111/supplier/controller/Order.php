<?php
/**
 * Created by PhpStorm.
 * User: jiayi
 * Date: 2017/4/18
 * Time: 10:59
 */
namespace ylt\supplier\controller;
use ylt\supplier\logic\OrderLogic;
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
        // 订单 支付 发货 结算状态
        $this->assign('order_status', $this->order_status);
        $this->assign('pay_status', $this->pay_status);
        $this->assign('shipping_status', $this->shipping_status);
    }


    /*
    *订单首页
    */
    public function index(){
        // DB::name('goods')->where('supplier_id = 41')->update(['is_on_sale'=>1,'last_update'=>time()]);

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
        $condition['supplier_id'] = session('supplier_id');
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
        $count = Db::name('order')->where($condition)->count();
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
        $orderList = $orderLogic->getOrderList($condition,$sort_order,$Page->firstRow,$Page->listRows);
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
        if($order['supplier_id'] != session('supplier_id'))
           $this->error('非法操作',Url::build('Supplier/Admin/logout'));
        $orderGoods = $orderLogic->getOrderGoods($order_id);
        $button = $orderLogic->getOrderButton($order);
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
		$shipping = Db::name('shipping_order')->where('order_id',$order_id)->select();
        $this->assign('users',$users);
        $this->assign('order',$order);
        $this->assign('action_log',$action_log); 
        $this->assign('orderGoods',$orderGoods);
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
            $a = $orderLogic->orderProcessHandle($order_id,$action,array('note'=>I('note'),'admin_id'=>0));
            if( $a !== false){
                if ($action == 'remove') {
                    exit(json_encode(array('status' => 1, 'msg' => '操作成功', 'data' => array('url' => Url::build('Supplier/order/index')))));
                }
                exit(json_encode(array('status' => 1,'msg' => '操作成功')));
            }else{
                if ($action == 'remove') {
                    exit(json_encode(array('status' => 0, 'msg' => '操作失败', 'data' => array('url' => Url::build('Supplier/order/index')))));
                }
                exit(json_encode(array('status' => 0,'msg' => '操作失败')));
            }
        }else{
            $this->error('参数错误',Url::build('Supplier/Order/detail',array('order_id'=>$order_id)));
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
        	$admin_id = session('admin_id');
            if(empty($admin_id) && $order['supplier_id'] !=session('supplier_id')){
                $this->error('非法操作');
                exit;
            }
            $update['discount'] = I('post.discount');
            $update['shipping_price'] = I('post.shipping_price');
			$update['order_amount'] = $order['goods_price'] + $update['shipping_price'] - $update['discount'] - $order['user_money'] - $order['integral_money'] - $order['coupon_price'];
            $row = Db::name('order')->where(array('order_id'=>$order_id))->update($update);
            if(!$row){
                $this->success('没有更新数据',Url::build('Supplier/Order/editprice',array('order_id'=>$order_id)));
            }else{
                $this->success('操作成功',Url::build('Supplier/Order/detail',array('order_id'=>$order_id)));
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
		if($order['supplier_id'] != session('supplier_id'))
           $this->error('非法操作',Url::build('Supplier/Admin/logout'));
        $orderGoods = $orderLogic->getOrderGoods($order_id);
        $delivery_record = Db::name('shipping_order')->alias('d')->join('__SUPPLIER_USER__ a','a.admin_id = d.admin_id')->where('d.order_id='.$order_id)->select();
        if($delivery_record){
            $order['invoice_no'] = $delivery_record[count($delivery_record)-1]['invoice_no'];
        }
        $shipping = Db::name('plugin')->where('type','shipping')->select();
        // dump($shipping);die;
        $this->assign('shipping',$shipping);
        $this->assign('order',$order);
        $this->assign('orderGoods',$orderGoods);
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
            $this->success('操作成功',Url::build('Supplier/Order/delivery_info',array('order_id'=>$data['order_id'])));
        }else{
            $this->success('操作失败',Url::build('Supplier/Order/delivery_info',array('order_id'=>$data['order_id'])));
        }
    }
	



    /**
     * 发货单
     */
    public function delivery_list(){

        return $this->fetch();
    }


    public function order_log(){
        $timegap = I('timegap');
        if($timegap){
            $gap = explode('-', $timegap);
            $begin = strtotime($gap[0]);
            $end = strtotime($gap[1]);
        }else{
            //@new 兼容新模板
            $begin = strtotime(I('timegap_begin'));
            $end = strtotime(I('timegap_end'));
        }
        $condition = array();
        $condition['supplier_id'] = session('supplier_id');
        $log =  Db::name('order_action');
        if($begin && $end){
            $condition['log_time'] = array('between',"$begin,$end");
        }
        $admin_id = I('admin_id');
        if($admin_id >0 ){
            $condition['action_user'] = $admin_id;
        }
        $count = $log->where($condition)->count();
        $Page = new Page($count,20);
        foreach($condition as $key=>$val) {
            $Page->parameter[$key] = urlencode($val);
        }
        $show = $Page->show();
        $list = $log->where($condition)->order('action_id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('list',$list);
        $this->assign('pager',$Page);
        $this->assign('page',$show);
        $admin = Db::name('admin_user')->getField('admin_id,user_name');
        $this->assign('admin_user',$admin);
        return $this->fetch();
    }


    /*
     * ajax 发货订单列表
    */
    public function ajaxdelivery(){
        $orderLogic = new OrderLogic();
        $condition = array();
        I('consignee') ? $condition['consignee'] = trim(I('consignee')) : false;
        I('order_sn') != '' ? $condition['order_sn'] = trim(I('order_sn')) : false;
        $shipping_status = I('shipping_status');
        $condition['shipping_status'] = empty($shipping_status) ? array('neq',1) : $shipping_status;
        $condition['order_status'] = array('in','1,2,4');
		$condition['supplier_id'] = session('supplier_id');
        $count = Db::name('order')->where($condition)->count();
        $Page  = new AjaxPage($count,10);
        //搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            if(!is_array($val)){
                $Page->parameter[$key]   =   urlencode($val);
            }
        }
        $show = $Page->show();
        $orderList = Db::name('order')->where($condition)->limit($Page->firstRow.','.$Page->listRows)->order('add_time DESC')->select();
        $this->assign('orderList',$orderList);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
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


    /**
     * 退货单列表
     */
    public function back_order_list(){
        return $this->fetch();
    }


    /*
    * ajax 退货订单列表
    */
    public function ajax_return_list(){
        // 搜索条件
        $order_sn =  trim(I('order_sn'));
        $order_by = I('order_by') ? I('order_by') : 'id';
        $sort_order = I('sort_order') ? I('sort_order') : 'desc';
        $status =  I('status');

        $where = " supplier_id = ".session('supplier_id')." ";
        $order_sn && $where.= " and order_sn like '%$order_sn%' ";
        $status != '' ? $where.= " and status = '$status' " : '';
        $count = Db::name('back_order')->where($where)->count();
        $Page  = new AjaxPage($count,13);
        $show = $Page->show();
        $list = Db::name('back_order')->where($where)->order("$order_by $sort_order")->limit("{$Page->firstRow},{$Page->listRows}")->select();
        $goods_id_arr = get_arr_column($list, 'goods_id');
        if(!empty($goods_id_arr)){
            $goods_list = Db::name('goods')->where("goods_id in (".implode(',', $goods_id_arr).")")->column('goods_id,goods_name');
        }
        $this->assign('goods_list',$goods_list);
        $this->assign('list',$list);
        $this->assign('pager',$Page);
        $this->assign('page',$show);// 赋值分页输出
        return $this->fetch();
    }


      /**
     * 退换货操作
     */
    public function return_info()
     {
        $id = I('id');
        $return_order = Db::name('back_order')->where(array('id'=>$id,'supplier_id'=>session('supplier_id')))->find();
		if(empty($return_order))
			$this->error('订单不存在');
		
        if($return_order['imgs'])
            $return_order['imgs'] = explode(',', $return_order['imgs']);
		
		$msg = Db::name('back_msg')->where("rec_id",$id)->select();
		$goods = Db::name('back_goods')->where(['back_id'=>$return_order['id']])->find();		
		
		$orderLogic = new OrderLogic();
        $order = $orderLogic->getOrderInfo($return_order['order_id']);
		$button = $orderLogic->getbackButton($return_order);
        $type_msg = array('退换','换货');
        $status_msg = array('未处理','处理中','已完成','收到寄回商品','收到寄回商品');
		
        if(IS_POST)
        {
			 // -1拒绝退换 0申请中 1客服理中 2寄回商品 3待退款 4收到寄回商品 5寄出换货 6完成
			$type = input('type'); 
			$content = input('content');
			
			// 回复商家
			if($content && $type ==''){
				Db::name('back_msg')->insert(['rec_id'=>$id,'add_time'=>time(),'content'=>$content,'supplier_id'=> session('supplier_id')]);
				exit(json_encode(array('status' => 0,'msg' =>  '回复成功')));  
			}
			
		
			if($type == 'confirm'){
				$data['status'] = '1'; //同意申请
				$data['remark'] = input('content'); // 回复
				$type = ($return_order['type'] == 2) ? 2 : 3; //0未发货，1已发货，2换货，3退货
                $where = " order_id = ".$return_order['order_id']." and goods_id=".$return_order['goods_id'];
                Db::name('order_goods')->where($where)->update(array('is_send'=>$type));//更改商品状态
				
			}
			if($type == 'refuse'){
				$data['remark'] = input('content');
				$data['status'] = '-1'; // 拒绝申请
			}
			if($type == 'received'){
				$data['status'] = '3'; // 收到商品，待退款
			}
			if($type == 'return'){
				$data['status'] = '4'; // 收到寄回商品 ，无需退款
			}
			if($type == 'refund'){	// 完成退款
				$data['status'] = '6'; 
				$data['refund_time'] = time();
				$data['is_refund'] = 1;
				$Success = 0;
				
				// 获取退款原订单信息
				$order = Db::name('order')->where('order_id',$return_order['order_id'])->find();
				 if($order['parent_id'] > 0)
					 $order = Db::name('order')->where('order_id',$order['parent_id'])->find();
				 
				if($order['pay_code'] == 'weixin' || $order['pay_code'] == 'weixinJSAPI'){ //微信退款
				$status = $orderLogic->refund_for_weixin($return_order['id'],$order['order_sn'],$return_order['shop_price'],$return_order['total_amount'],$order['pay_code']);
				
					if($status['out_trade_no'] == $order['order_sn']){ //退款成功
						$Success = 1;
						adminLog('微信退款 订单号'.$order['order_sn'].'');
					}
					$orderLogic->refund_log($order['order_sn'],$status);					
				}		
				if($order['pay_code'] == 'alipay' || $order['pay_code'] == 'alipayMobile'){ //支付宝退款
				
					$status = $orderLogic->refund_for_alipay($order['order_sn'],$return_order['total_amount'],1,$order['mobile']); 
						
					if($status == true){
						$Success = 1;
						adminLog('支付宝退款 订单号'.$order['order_sn'].'');
					}
				}
				
				if($Success == 1){ //退款后数据处理
					$where = " order_id = ".$return_order['order_id']." and goods_id=".$return_order['goods_id'];
					$note ="{$order[pay_code]} 退款";
							 
					Db::name('order_goods')->where($where)->update(array('is_send'=>3));//更改商品状态
					
					$settlement = $return_order['shop_price']; // 商家结算金额修改	   
					Db::name('supplier_settlement')->where("supplier_id = '".$order['supplier_id']."' and settlement_paytime_start  <= '".$order['pay_time']."' and settlement_paytime_end >= '".$order['pay_time']."' ")->update(['settlement_all' => ['exp','settlement_all-'.$settlement.''],'back_money' => ['exp','back_money+'.$settlement.'']]);
					
					Db::name('back_order')->where("id= $id")->update($data);
					
					$orderLogic->orderActionLog($return_order[order_id],'退款',$note);
					
					exit(json_encode(array('status' => 1,'msg' => '退款成功')));
					adminLog($note);
				}				
				exit(json_encode(array('status' => 0,'msg' =>  '退款失败')));  
					
			}
           
			if($type != '' )
				$result = Db::name('back_order')->where("id= $id")->update($data);
            if($result)
            {
			 if($data['status'] != '-1'){
				$note ="退换货:{$type_msg[$return_order['type']]}, 状态:{$status_msg[$data['status']]},处理备注：{$data['remark']}";
			 }else{
				$note ="退换货:{$type_msg[$return_order['type']]}, 状态:拒绝退换货,处理备注：{$data['remark']}";
			 }
				
   
                $log = $orderLogic->orderActionLog($return_order[order_id],'退换货操作',$note);
				adminLog($note);
					 
				exit(json_encode(array('status' => 1,'msg' => '操作成功')));
            }
        }

        $this->assign('id',$id); // 用户
        $this->assign('msg',$msg); // 回复信息
		$this->assign('order',$order); // 订单
        $this->assign('goods',$goods);// 商品
		$this->assign('button',$button); // 按钮
        $this->assign('return_order',$return_order);// 退换货
        return $this->fetch();
    }
	

	
	    /**
     * 订单打印
     * @param int $id 订单id
     */
    public function order_print(){
    	$order_id = I('order_id');
        $orderLogic = new OrderLogic();
        $order = $orderLogic->getOrderInfo($order_id);
        $order['province'] = getRegionName($order['province']);
        $order['city'] = getRegionName($order['city']);
        $order['district'] = getRegionName($order['district']);
        $order['full_address'] = $order['province'].' '.$order['city'].' '.$order['district'].' '. $order['address'];
        $orderGoods = $orderLogic->getOrderGoods($order_id);
        $shop = tpCache('shop_info');
        $this->assign('order',$order);
        $this->assign('shop',$shop);
        $this->assign('orderGoods',$orderGoods);
        $template = I('template','print');
        return $this->fetch($template);
    }
	
	
	/**
	 * 导出数据订单
	 */
	 public function export_order()
    {
    	//搜索条件
		$supplier_id = session('supplier_id');
		$where = 'where supplier_id = '.$supplier_id.' ';
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
		    
		$sql = "select *,FROM_UNIXTIME(add_time,'%Y-%m-%d') as create_time from __PREFIX__order $where order by order_id";
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
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$this->close[$val['close']].'</td>';
	    		$orderGoods = Db::name('order_goods')->where('order_id='.$val['order_id'])->field('goods_sn,goods_name,spec_key_name')->select();
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
        $order_amount = Db::name('order')->cache(true,1800)->where(array('order_status'=>0,'pay_status'=>1,'supplier_id'=>session('supplier_id')))->count();
        echo $order_amount;
    }


}