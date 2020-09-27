<?php
namespace ylt\admin\controller;
use ylt\admin\logic\OrderLogic;
use ylt\admin\logic\GoodsLogic;
use think\Db;
use think\AjaxPage;
use think\Page;
use think\Request;
use think\Url;
use think\Cache;

class Refillcard extends Base{

    public  $order_status;
    public  $pay_status;
    public  $shipping_status;
    public  $close;

    /*
      * 初始化操作
      */
    public function _initialize() {
        parent::_initialize();
        config('TOKEN_ON',false); // 关闭表单令牌验证
        $this->order_status = config('ORDER_STATUS');
        $this->pay_status = config('PAY_STATUS');
        $this->shipping_status = config('SHIPPING_STATUS');
        $this->close = config('CLOSE');

        // 订单 支付 发货 结算状态
        $this->assign('close',$this->close);
        $this->assign('order_status',$this->order_status);
        $this->assign('pay_status',$this->pay_status);
        $this->assign('shipping_status',$this->shipping_status);
    }
	/**
	 * [refill_list 充值配置列表]
	 * @return [type] [description]
	 */
	public function refill_lists(){
		$list = array();
        $keywords = I('keywords/s');

        if(empty($keywords)){
            $res = Db::name('top_config_card')->alias('c')->join('top_config t','c.cat_id_2=t.id')->field('c.*,t.name as t_name')->order('c.id')->select();
        }else{
            $res = DB::name('top_config_card')->alias('c')->join('top_config t','c.cat_id_2=t.id')->field('c.*,t.name as t_name')->where('c.name','like','%'.$keywords.'%')->whereOR('t.name','like','%'.$keywords.'%')->order('c.id')->select();
        }
        $count = count($res);
        $Page  = new Page($count,20);

        $ress = Db::name('top_config_card')->alias('c')->join('top_config t','c.cat_id_2=t.id')->field('c.*,t.name as t_name')->limit($Page->firstRow.','.$Page->listRows)->order('c.id')->select();

        foreach ($ress as $key => $value) {
            $top = Db::name('top_config')->where('id',$value['cat_id_4'])->find();
            $value['s_name'] = $top['name'];
            if (empty($value['s_name'])) {
                $value['s_name'] = $value['t_name'];
            }
            $resss[] = $value;
        }
        $show = $Page->show();
        $this->assign('list',$resss);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
		return $this->fetch();
	}

	/**
	 * [refill_card 新增/修改配置]
	 * @return [type] [description]
	 */
	public function refill_card(){
        $cat_list = Db::name('top_config')->where("parent_id = 0 and is_show = 1 ")->select(); // 已经改商品成联动菜单
        $this->assign('cat_list',$cat_list);

        $id = I('get.id/d',0);
        if($id){
            $info = Db::name('top_config_card')->where("id", $id)->find();
        	$GoodsLogic = new GoodsLogic();
        	$level_cat = $GoodsLogic->refill_find_parent_cat($info['cat_id_2']); // 获取分类默认选中的下拉框
            $this->assign('level_cat',$level_cat);
            $this->assign('info',$info);
        }
        $act = empty($id) ? 'add' : 'edit';
        $this->assign('act',$act);
		return $this->fetch();
	}
	public function refill_card_add(){
		$data = I('');
		if (IS_POST && $data['act']=='add') {
            $data['cat_id_2'] = I('cat_id_2');
            if (I('cat_id_3')) {
                $data['cat_id_2'] = I('cat_id_3');
                $data['cat_id_4'] = I('cat_id_3');
            }
            $data['cat_id_4'] = I('cat_id_2');
			$id = Db::name('top_config_card')->insertgetID($data);
			if ($id) {
				$return_arr = array(
                    'status' => 1,
                    'msg'    => '添加成功',
                );
                $this->ajaxReturn($return_arr);
        		adminLog('添加充值卡配置规格 '.$data['name'].'');
			}else{
				$return_arr = array(
                    'status' => -1,
                    'msg'    => '添加失败',
                );
                $this->ajaxReturn($return_arr);
			}
		}else if(IS_POST && $data['act']=='edit'){

            $data['cat_id_2'] = I('cat_id_2');
            if (I('cat_id_3')) {
                $data['cat_id_2'] = I('cat_id_3');
                $data['cat_id_4'] = I('cat_id_3');
            }
            $data['cat_id_4'] = I('cat_id_2');
			$id = Db::name('top_config_card')->where("id", $data['id'])->update($data);
			if ($id) {
				$return_arr = array(
                    'status' => 1,
                    'msg'    => '修改成功',
                );
                $this->ajaxReturn($return_arr);
        		adminLog('修改充值卡配置规格 '.$data['name'].'');
			}else{
				$return_arr = array(
                    'status' => -1,
                    'msg'    => '修改失败',
                );
                $this->ajaxReturn($return_arr);
			}
		}else if(IS_POST && $data['act']=='del'){
			$r = Db::name('top_config_card')->where('id', $data['id'])->delete();
	        adminLog('删除充值卡配置规格 '.$data['id'].'');
	        if($r){
	            $this->success("操作成功",Url::build('Admin/Refillcard/refill_lists'));
	        }else{
	            $this->error("操作失败",Url::build('Admin/Refillcard/refill_lists'));
	        }
		}
		return $this->fetch();
	}

	/**
	 * [del_refill_card 删除配置]
	 * @return [type] [description]
	 */
	public function del_refill_card(){
		$data = I('');
        $r = Db::name('top_config_card')->where('id', $data['id'])->delete();
        adminLog('删除充值卡配置规格 '.$data['id'].'');
        if($r){
            $this->success("操作成功",Url::build('Admin/Refillcard/refill_lists'));
        }else{
            $this->error("操作失败",Url::build('Admin/Refillcard/refill_lists'));
        }
	}

	/**
	 * [refill_addclass 增加/修改配置分类]
	 * @return [type] [description]
	 */
	public function refill_addclass(){
		$GoodsLogic = new GoodsLogic();
        if(IS_GET)
        {
            $top_config = Db::name('top_config')->where('id='.I('GET.id',0))->find();
            $level_cat = $GoodsLogic->refill_class_cat($top_config['id']); // 获取分类默认选中的下拉框
            $cat_list = Db::name('top_config')->where("parent_id = 0")->select(); // 已经改成联动菜单
            $this->assign('level_cat',$level_cat);
            $this->assign('cat_list',$cat_list);
            $this->assign('top_config',$top_config);
            return $this->fetch();
            exit;
        }

        $top_config =  Db::name('top_config'); //

        $type = I('id') > 0 ? 2 : 1; // 标识自动验证时的 场景 1 表示插入 2 表示更新
        //ajax提交验证
        if(I('is_ajax') == 1)
        {	
        	$data = I('post.');
            if (!empty($data['id'])) {
                $name = $top_config->where('name',$data['name'])->where("id !=".$data['id'])->find();
            }else{
                $name = $top_config->where('name',$data['name'])->find();
            }
        	if ($name) {
        		$return_arr = array(
                    'status' => -2,
                    'msg'   => '分类名称已存在',
                );
                $this->ajaxReturn($return_arr);
        	}
        	$data['parent_id'] = I('parent_id_1');
        	I('parent_id_2') && ($data['parent_id'] = I('parent_id_2'));
            if($data['id'] > 0 && $data['parent_id'] == $data['id'])
            {
                //  编辑
                $return_arr = array(
                    'status' => -1,
                    'msg'   => '上级分类不能为自己',
                    'data'  => '',
                );
                $this->ajaxReturn($return_arr);
            }

            if ($type == 2)
            {	
                $top_config->update($data); // 写入数据到数据库
                $GoodsLogic->refill_refresh_cat(I('id'));
				adminLog('编辑分类 '.input('name').'');
            }
            else
            {
                $top_config->update($data); // 写入数据到数据库
                $insert_id = $top_config->insertgetID($data);
                $GoodsLogic->refill_refresh_cat($insert_id);
				adminLog('添加分类 '.input('name').'');
            }
            $return_arr = array(
                'status' => 1,
                'msg'   => '操作成功',
                'data'  => array('url'=>Url::build('Admin/Refillcard/refill_class')),
            );
            $this->ajaxReturn($return_arr);

        }
	}

	/**
	 * [refill_class 配置分类列表]
	 * @return [type] [description]
	 */
	public function refill_class(){
		$GoodsLogic = new GoodsLogic();
        $cat_list = $GoodsLogic->refill_class_cat_list();
        $this->assign('cat_list',$cat_list);
        return $this->fetch();
	}

	/**
     * [delRefillClass 删除商品分类]
     * @return [type] [description]
     */
    public function delRefillClass(){
        $id = $this->request->param('id');
        // 判断子分类
        $top_config = Db::name("top_config");
        $count = $top_config->where("parent_id = {$id}")->count("id");
        $count > 0 && $this->error('该分类下还有分类不得删除!',Url::build('Admin/Refillcard/refill_class'));
        // 判断是否存在商品
        $goods_count = Db::name('top_config_card')->where("cat_id_2 = {$id}")->count('1');
        $goods_count > 0 && $this->error('该分类下有商品不得删除!',Url::build('Admin/Refillcard/refill_class'));
        // 删除分类
        DB::name('top_config')->where('id',$id)->delete();
        adminLog('删除分类'.$id.'');
        $this->success("操作成功!!!",Url::build('Admin/Refillcard/refill_class'));
    }

    /**
     * [conversion 兑换列表]
     * @return [type] [description]
     */
    public function conversionLise(){
        //获取优惠券列表
        $count =  Db::name('code')->where('type',5)->count();
        $Page = new Page($count,10);
        $show = $Page->show();
        $lists_ = Db::name('code')->where('type',5)->order('add_time desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('lists',$lists_);
        $this->assign('pager',$Page);// 赋值分页输出
        $this->assign('page',$show);// 赋值分页输出   
        $this->assign('codes',C('COUPON_TYPE'));
        return $this->fetch();
    }

    /**
     * [conversionInfo 新增/修改兑换详情]
     * @return [type] [description]
     */
    public function conversionInfo(){
        $cid = I('get.id/d');
        if($cid){
            $code = Db::name('code')->where(array('id'=>$cid))->find();
            $this->assign('code',$code);
        }else{
            $def['use_start_time'] = strtotime("+1 day");
            $def['use_end_time'] = strtotime("+2 month");
            $this->assign('code',$def);
        }   
        if(IS_POST){
            $data = I('post.');
            $data['use_end_time'] = strtotime($data['use_end_time']);
            $data['use_start_time'] = strtotime($data['use_start_time']);
            if(empty($data['id'])){
                $data['add_time'] = time();
                $row = Db::name('code')->insert($data);
                //礼品卡提交后自动生成规定数量的线下礼品卡编号及秘钥
                if (I('post.type')==5) {
                    $createnum=I('post.createnum');
                    $data=array();
                    $id=Db::name('code')->order('id','desc')->find();
                    for ($i=0; $i <$createnum ; $i++) { 
                        $data[$i]['cid']=$id['id'];
                        $data[$i]['type']=$id['type'];
                        $data[$i]['number']="ylt".date('Ym',time()).str_pad($i+1,4,0,STR_PAD_LEFT);
                        $code = substr(md5("ax".rand(time(),$i)),0,8);//获取随机8位字符串
                        $a = Db::name('code_list')->where(array('code'=>$code))->find();
                        if (empty($a)) {
                            $data[$i]['code'] = $code;
                        }
                        Db::name('code_list')->insert($data[$i]);
                    }
                }
            }else{
                $createnums = Db::name('code_list')->where('cid',$data['id'])->count('id');
                $createnum=$data['createnum'];
                //发放数量增加时增加优惠秘钥的生成
                if ($createnum >= $createnums) {
                    $createnum = $createnum - $createnums;
                }else if($createnum < $createnums){
                    $this->ajaxReturn(['status' => -11, 'msg' => '发放数量不可减少', 'result' => '']);
                }
                $id=Db::name('code')->where('id',$data['id'])->find();
                $datas=array();
                for ($i=0; $i <$createnum ; $i++) { 
                    $datas[$i]['cid']=$id['id'];
                    $datas[$i]['type']=$id['type'];
                    $datas[$i]['number']="ylt".date('Ym',time()).str_pad($createnums+$i+1,4,0,STR_PAD_LEFT);
                    $code = substr(md5("ax".rand(time(),$i)),0,8);//获取随机8位字符串
                    $a = Db::name('code_list')->where(array('code'=>$code))->find();
                    if (empty($a)) {
                        $datas[$i]['code'] = $code;
                    }
                    Db::name('code_list')->insert($datas[$i]);
                }
                $row=Db::name('code')->where(array('id'=>$data['id']))->update($data);
            }
            if($row !== false){
                $this->ajaxReturn(['status' => 1, 'msg' => '编辑代金券成功', 'result' => '']);
            }else{
                $this->ajaxReturn(['status' => 0, 'msg' => '编辑代金券失败', 'result' => '']);
            }
            
            
        }  
        return $this->fetch();
    }

    /**
     * [order_lists 订单列表]
     * @return [type] [description]
     */
    /*
     *订单首页
     */
    public function orderlist(){
        header('Content-Type: application/json; charset=utf-8');
        $begin = date('Y-m-d',strtotime("-1 year"));//30天前
        $end = date('Y/m/d',strtotime('+1 days'));
        $this->assign('timegap',$begin.'-'.$end);
        return $this->fetch();
    }

    /*
     *Ajax首页数据
     */
    public function ajaxorderindex(){
        $orderLogic = new OrderLogic();

        $begin = strtotime(input('add_time_begin'));
        $end = strtotime(input('add_time_end')); 
        
        // 搜索条件
        $condition = array();
        $keyType = input("keytype");
        $keywords = I('keywords','','trim');
        //$condition['supplier_id'] = 0;
        //$condition['is_parent'] = 0;

        $keywords =  $keywords ? $keywords : false;
        $keywords ? $condition[''.$keyType.''] = trim($keywords) : false;


        if($begin && $end){
            $condition['add_time'] = array('between',"$begin,$end");
        }

        input('order_status') != '' ? $condition['order_status'] = input('order_status') : false;
        input('pay_status') != '' ? $condition['pay_status'] = input('pay_status') : false;
        input('pay_code') != '' ? $condition['pay_code'] = input('pay_code') : false;
        input('shipping_status') != '' ? $condition['shipping_status'] = input('shipping_status') : false;
        input('close') != '' ? $condition['close'] = input('close') : false;
        input('user_id') ? $condition['user_id'] = trim(input('user_id')) : false;
        $sort_order = I('order_by','DESC').' '.I('sort');
        $count = Db::name('order')->where($condition)->where("phone != ''")->count();
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
        $orderList = $orderLogic->getOrderList($condition,$sort_order,$Page->firstRow,$Page->listRows,1);
        $orderList_s = Db::name('order')->where($condition)->where("phone != ''")->order('order_id')->field('order_amount')->select();
        //计算当前列表的订单总额
        $money = 0;
        foreach ($orderList_s as $key => $value) {
            $money += $value['order_amount'];
        }
        $this->assign('money',$money);
        $this->assign('orderList',$orderList);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
    }
    /**
     * 导出数据订单
     */
     public function export_order()
    {
        //搜索条件
        //dump(input('POST.'));
        $where = 'where 1 = 1 ';
        $where .= 'AND is_topup = 1 ';
        $consignee = input('consignee');
        if($consignee){
            $where .= " AND consignee like '%$consignee%' ";
        }
        $order_sn =  input('order_sn');
        if($order_sn){
            $where .= " AND order_sn = '$order_sn' ";
        }
        if(input('order_status')){
            $where .= " AND order_status = ".input('order_status');
        }
        if(input('close')){
            $where .= " AND close = ".input('close');
        }
        if(input('pay_status')){
            $where .= " AND pay_status  = ".input('pay_status');
        }
        if(input('pay_code')){
            if(input('pay_code') == 'alipay')
                $where .= " AND (pay_code = 'alipay' or pay_code = 'alipayMobile')";
            else
                $where .= " AND pay_code = ".input('pay_code');
        }
        if(input('shipping_status')){
            $where .= " AND shipping_status = ".input('shipping_status');
        }
        if(input('add_time_begin')){
            $where .= " AND add_time > ". strtotime(input('add_time_begin'));
        }
        if(input('add_time_end')){
            $where .= " AND add_time < ". strtotime(input('add_time_end'));
        }
        if(input('supplier_id')){
            $where .= " AND supplier_id = " . input('supplier_id');
        }
        if(input('is_distribut')){
            $where .= " AND is_distribut = ".input('is_distribut');
        }
  
        $sql = "select *,FROM_UNIXTIME(add_time,'%Y-%m-%d') as create_time from __PREFIX__order $where order by order_id"; //echo $sql;exit;
        $orderList = DB::query($sql);
        $strTable ='<table width="500" border="1">';
        $strTable .= '<tr>';
        $strTable .= '<td style="text-align:center;font-size:12px;width:120px;">订单编号</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="100">收货日期</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">收货人/充值账号</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">订单金额</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">实际支付</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">支付方式</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">支付状态</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">发货状态</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">结算状态</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">店铺名称</td>';
        $strTable .= '</tr>';
        if(is_array($orderList)){
            $region = Db::name('region')->column('id,name'); 
            foreach($orderList as $k=>$val){
                $strTable .= '<tr>';
                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['order_sn'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['create_time'].' </td>';               
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['phone'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['goods_price'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['order_amount'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['pay_name'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$this->pay_status[$val['pay_status']].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$this->shipping_status[$val['shipping_status']].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$this->close[$val['close']].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['supplier_name'].' </td>';
                $strTable .= '</tr>';
                unset($supplier);
            }
        }
        $strTable .='</table>';
        unset($orderList);
        downloadExcel($strTable,'order');
        exit();
    }
}