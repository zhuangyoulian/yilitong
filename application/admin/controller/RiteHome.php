<?php
namespace ylt\admin\controller;
use think\AjaxPage;
use think\Page;
use think\Verify;
use think\Db;
use think\Session;
use think\Url;
use think\Request;
use ylt\admin\logic\GoodsLogic;
use ylt\admin\logic\OrderLogic;

class RiteHome extends Base{
    public  $order_url;
    public  $act_list;
    public  $palte_list;
    /*
    * 初始化操作
    */
    public function _initialize() {
        parent::_initialize();
        $act_list = Db::name('admin_user')->alias('a')->join('admin_role r','a.role_id=r.role_id')->field('r.act_list,r.role_name,r.is_three,a.admin_id')->where('a.admin_id',session('admin_id'))->find();
        // if (stripos($act_list['act_list'],'103') != false and stripos($act_list['act_list'],'97') == false) {
        if (stripos($act_list['act_list'],'113') != false and stripos($act_list['act_list'],'110') == false and $act_list['is_three']==1) {
            $this->act_list  = $act_list;
        }
        $palte_list = Db::name('admin_role')->where('plate_id = 3 and role_id != 4')->select();
        $this->assign('palte_list',$palte_list);
        // $this->order_url = "http://www.szleezen.cn";
        $this->order_url = "http://lzjj.cn";
        $this->assign('order_url',$this->order_url);
    }
	/*
     *礼至家居订单首页
     */
    public function inquire_index(){
        if ($this->act_list) {    //判断是否礼至家居的三级项目负责人
            $this->assign('act_list',1);
        }
        // dump(I('keywords'));die;
        $type = $this->get_urls("$this->order_url/Home/Api/order_status");
        $type = json_decode($type,true);
        // truncate table ylt_order   清空表
        $begin = date('Y-m-d',strtotime("-1 year"));//30天前
        $end = date('Y/m/d',strtotime('+1 days'));
        $this->assign('timegap',$begin.'-'.$end);
        $this->assign('keywords',I('keywords'));
        $this->assign('order_status',$type['order_status']);
        return $this->fetch();
    }

	/**
     * [inquire_ajax_index 查询礼至订单]
     * @return [type] [description]
     */
    public function inquire_ajax_index(){
        input('p') != '' ? $p = input('p') : false;
        $begin = strtotime(input('add_time_begin'));
        $end = strtotime(input('add_time_end')); 
        // 搜索条件
        // $condition = array();
        $keytype = input("keytype");  //查询条件
        $keywords = trim(I('keywords','','trim')); //查询关键字
        $keywords =  $keywords ? $keywords : false;
        $keywords ? $condition[''.$keytype.''] = trim($keywords) : false;
        input('order_status') != '' ? $order_status = input('order_status') : false;
        input('pay_status') != '' ? $pay_status = input('pay_status') : false;
        input('pay_code') != '' ? $pay_code = input('pay_code') : false;
        input('shipping_status') != '' ? $shipping_status = input('shipping_status') : false;
        if ($this->act_list) {    //判断是否礼至家居的三级项目负责人
            $act_list = $this->act_list;
            $items_source = $act_list['role_name'];
            $this->assign('act_list',1);
        }else{
            input('items_source') != '' ? $items_source = input('items_source') : false;
        }
        $type = $this->get_urls("$this->order_url/Home/Api/ajax_index?keytype=$keytype&keywords=$keywords&begin=$begin&end=$end&order_status=$order_status&pay_status=$pay_status&pay_code=$pay_code&shipping_status=$shipping_status&p=$p&items_source=$items_source");
        $type = json_decode($type,true);
        $Page  = new AjaxPage($type['count'],20);
        $this->assign('money',$type['money']);
        $this->assign('orderList',$type['orderList']);
        $this->assign('page',$type['show']);// 赋值分页输出
        $this->assign('pager',$Page);
        // 订单 支付 发货状态
        $this->assign('order_status',$type['order_status']);
        $this->assign('pay_status',$type['pay_status']);
        $this->assign('shipping_status',$type['shipping_status']);
        return $this->fetch();
    }
    
    /**
     *  通过URL获取页面信息
     * @param $url  地址
     * @return mixed  返回页面信息
     */
    public function get_urls($url)
    {   
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);  //设置访问的url地址
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//不输出内容
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    /**
     * 订单删除
     * @param int $id 订单id
     */

    public function delete_order($order_id){
        $type = $this->get_urls("$this->order_url/Home/Api/delete_order?order_id=$order_id");
        $type = json_decode($type,true);
        return $type;
    }

    
    /**
     * 订单详情
     * @param int $id 订单id
     */
    public function detail(){
        $order_id = I('order_id');
        $type = $this->get_urls("$this->order_url/Home/Api/detail?order_id=$order_id");
        $type = json_decode($type,true);
        $this->assign('users',$type['users']);
        $this->assign('order',$type['order']);
        $this->assign('action_log',$type['action_log']);
        $this->assign('orderGoods',$type['orderGoods']);
        $this->assign('split',$type['split']);
        $this->assign('shipping',$type['shipping']);
        $this->assign('button',$type['button']);
        return $this->fetch();
    }
    /*
     * 价钱修改
     */
    public function editprice(){
        $order_id = I('order_id');
        $type = $this->get_urls("$this->order_url/Home/Api/editprice?order_id=$order_id");
        $type = json_decode($type,true);
        if ($type['result']==-1) {
            $this->error('已发货订单不允许编辑');
        }
        $this->assign('order',$type['order']);
        return $this->fetch();
    }
    public function editprice_up(){
        $order_id = I('order_id');
        $discount = trim(I('post.discount'));
        $shipping_price = trim(I('post.shipping_price'));
        $type = $this->get_urls("$this->order_url/Home/Api/editprice_up?order_id=$order_id&type=post&discount=$discount&shipping_price=$shipping_price");
        $type = json_decode($type,true);
        if ($type['result']==1) {
            adminLog("修改礼至家居ID".$type["order_id"]."订单价格：应付金额改为：".$type['order_amount']."");
            $this->success('操作成功',Url::build('Admin/RiteHome/editprice',array('order_id'=>$type["order_id"])));
        }
        return $type;
    }
    

    /**
     * @return 发货
     */
    public function delivery_info(){
        $order_id = I('order_id');
        $type = $this->get_urls("$this->order_url/Home/Api/delivery_info?order_id=$order_id");
        $type = json_decode($type,true);
        $this->assign('shipping',$type['shipping']);
        $this->assign('order',$type['order']);
        $this->assign('orderGoods',$type['orderGoods']);
        $this->assign('delivery_record',$type['delivery_record']);//发货记录
        return $this->fetch();

    }
    /**
     * 生成发货单
     */
    public function deliveryHandle(){
        $order_id = I('post.order_id');
        $shipping_name = trim(I('post.shipping_name'));
        $invoice_no = trim(I('post.invoice_no'));
        $note = trim(I('post.note'));
        $goods = json_encode(I('post.goods/a'));
        $type = $this->get_urls("$this->order_url/Home/Api/deliveryHandle?order_id=$order_id&shipping_name=$shipping_name&invoice_no=$invoice_no&note=$note&goods=$goods");
        $type = json_decode($type,true);
        if ($type['result'] == 11) {
            $this->success('操作成功',Url::build('Admin/RiteHome/detail',array('order_id'=>$type['order_id'])));
        }else{
            $this->success('操作失败',Url::build('Admin/RiteHome/detail',array('order_id'=>$type['order_id'])));
        }
    }

    /**
     * 订单打印
     * @param int $id 订单id
     */
    public function order_print(){
        $order_id = I('order_id');
        $type = $this->get_urls("$this->order_url/Home/Api/order_print?order_id=$order_id");
        $type = json_decode($type,true);
        $this->assign('order',$type['order']);
        $this->assign('shop',$type['shop']);
        $this->assign('orderGoods',$type['orderGoods']);
        $template = I('template','print');
        return $this->fetch($template);
    }

    /**
	 * 导出数据订单
	 */
	public function export_order(){
        //搜索条件
        $consignee = input('consignee');
        $order_sn =  input('order_sn');
        $order_status =  input('order_status');
        $items_source =  input('items_source');
        $pay_status =  input('pay_status');
        $pay_code =  input('pay_code');
        $shipping_status =  input('shipping_status');
        $add_time_begin =  input('add_time_begin');
        $add_time_end =  input('add_time_end');
        $supplier_id =  input('supplier_id');
        $is_distribut =  input('is_distribut');
        $type = "$this->order_url/Home/Api/export_order?consignee=$consignee&order_sn=$order_sn&order_status=$order_status&items_source=$items_source&pay_status=$pay_status&pay_code=$pay_code&shipping_status=$shipping_status&add_time_begin=$add_time_begin&add_time_end=$add_time_end&supplier_id=$supplier_id&is_distribut=$is_distribut";
        $this->redirect($type);
    }


    /**
     * 订单操作
     * @param $id
     */
    public function order_action(){
        $action = trim(I('get.type'));
        $order_id = I('get.order_id');
        $note = trim(I('note'));
        $type = $this->get_urls("$this->order_url/Home/Api/order_action?note=$note&type=$action&order_id=$order_id");
        $type = json_decode($type,true);
        if ($type['status']==-1) {
            $this->error('参数错误',Url::build('Admin/RiteHome/detail',array('order_id'=>$order_id)));
        }elseif ($type['status']==0){
            exit(json_encode(array('status' => 0, 'msg' => '操作失败', 'data' => array('url' => Url::build('admin/RiteHome/inquire_index')))));
        }elseif ($type['status']==1 ){
            if($action !=='pay'){
                adminLog('礼至订单操作'.$order_id.trim(I('note')).'');
            }
            exit(json_encode(array('status' => 1, 'msg' => '操作成功', 'data' => array('url' => Url::build('admin/RiteHome/inquire_index')))));
        }elseif ($type['status']==2) {
            if($action !=='pay'){
                adminLog('礼至订单操作'.$order_id.trim(I('note')).'');
            }
            exit(json_encode(array('status' => 1, 'msg' => '操作成功', 'data' => array('url' => Url::build('Admin/RiteHome/detail',array('order_id'=>$order_id))))));
        }elseif ($type['status']==3) {
            exit(json_encode(array('status' => 0,'msg' => '操作失败')));
        }
    }


    /*************************************礼至家居用户相关*****************************************/

    /**
     * [user_index 用户列表]
     * @return [type] [description]
     */
    public function  user_index(){
        if ($this->act_list) {    //判断是否礼至家居的三级项目负责人
            $this->assign('act_list',1);
        }
        return $this->fetch();
    }
    public function  ajax_user_index(){
        I('mobile') ? $mobile = trim(I('mobile')) : false;
        $order_by = I('order_by','user_id');
        $sort = I('sort','desc');
        input('activate') != '' ? $activate = input('activate') : false;
        if ($this->act_list) {    //判断是否礼至家居的三级项目负责人
            $act_list = $this->act_list;
            $items_source = $act_list['role_name'];
        }else{
            input('items_source') != '' ? $items_source = input('items_source') : false;
        }
        $type = $this->get_urls("$this->order_url/Home/Api/ajax_user_index?mobile=$mobile&order_by=$order_by&sort=$sort&activate=$activate&items_source=$items_source");
        $type = json_decode($type,true);
        $this->assign('userList',$type['userList']);
        $this->assign('level',$type['level']);
        $this->assign('page',$type['show']);// 赋值分页输出
        $this->assign('pager',$type['Page']);
        return $this->fetch();
    }
    /**
     * 会员详细信息查看
     */
    public function user_detail(){
        $uid = I('get.id');
        $type = $this->get_urls("$this->order_url/Home/Api/user_detail?uid=$uid");
        $type = json_decode($type,true);
        $user = $type['user'];
        if(!$type['user']){
            exit($this->error('会员不存在'));
        }
        $this->assign('user',$user);
        if (IS_POST) {
            $uid = I('get.id');
            $exchange_points = input('exchange_points');
            $mobile = input('mobile');
            $sex = input('sex');
            $qq = input('qq');
            $is_lock = input('is_lock');
            $items_source = input('items_source');
            $type = $this->get_urls("$this->order_url/Home/Api/user_detail?type=POST&uid=$uid&exchange_points=$exchange_points&mobile=$mobile&sex=$sex&qq=$qq&is_lock=$is_lock&items_source=$items_source");
            $type = json_decode($type,true);
            if ($type['result']==1) {
                if ($type['data']) {
                adminLog("修改会员：".$user['nickname']."的兑换积分：".$exchange_points."，用户手机号码:".$user['mobile']."");
                }
                $this->success('操作成功');
            }else{
                $this->error('操作失败');
            }
        }
        return $this->fetch();
    }


}