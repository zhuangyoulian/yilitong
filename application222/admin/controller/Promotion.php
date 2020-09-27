<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/22
 * Time: 14:59
 */
namespace ylt\admin\controller;

use ylt\admin\model\GoodsActivity;
use think\AjaxPage;
use think\Page;
use ylt\admin\logic\GoodsLogic;
use think\Loader;
use think\Db;
use think\Url;
use think\Cache;

class Promotion extends Base
{

    public function index()
    {
        return $this->fetch();
    }


    //限时抢购
    public function panic_buying()
    {
        $condition = array();
        $model = Db::name('panic_buying');
        $count = $model->where($condition)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $prom_list = $model->where($condition)->order("id desc")->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('prom_list', $prom_list);
        $this->assign('page', $show);// 赋值分页输出
        $this->assign('pager', $Page);
        return $this->fetch();
    }


    // 抢购详情
    public function panic_buying_info()
    {
        $id = I('id');
        if (IS_POST) {
            $data = I('post.');
            $data['start_time'] = strtotime($data['start_time']);
            $data['end_time'] = strtotime($data['end_time']);
            $flashSaleValidate = Loader::validate('PanicBuying');
            if (!$flashSaleValidate->batch()->check($data)) {
                $return = ['status' => 0, 'msg' => '操作失败', 'result' => $flashSaleValidate->getError()];
                $this->ajaxReturn($return);
            }
            if (empty($data['id'])) {
                $insert_id = Db::name('panic_buying')->insertGetId($data);
                $r = Db::name('goods')->where('goods_id', $data['goods_id'])->update(['prom_id' => $insert_id, 'prom_type' => $data['buy_type']]);
                adminLog("管理员添加抢购活动 " . $data['name']);
            } else {
                $r = Db::name('panic_buying')->where("id=" . $data['id'])->update($data);
                 Db::name('goods')->where("(prom_type=1 or prom_type = 5) and prom_id=" . $data['id'])->update(array('prom_id' => 0, 'prom_type' => 0));
                 Db::name('goods')->where("goods_id=" . $data['goods_id'])->update(array('prom_id' => $data['id'], 'prom_type' => $data['buy_type']));
            }
            if ($r !== false) {
                $return = ['status' => 1, 'msg' => '编辑抢购活动成功', 'result' => ''];
            } else {
                $return = ['status' => 0, 'msg' => '编辑抢购活动失败', 'result' => ''];
            }
            $this->ajaxReturn($return);
        }
        $now_time = date('H');
        if ($now_time % 2 == 0) {
            $flash_now_time = $now_time;
        } else {
            $flash_now_time = $now_time - 1;
        }
        $flash_sale_time = strtotime(date('Y-m-d') . " " . $flash_now_time . ":00:00");
        $info['start_time'] = date("Y-m-d H:i:s", $flash_sale_time);
        $info['end_time'] = date("Y-m-d H:i:s", $flash_sale_time + 7200);
        if ($id > 0) {
            $info = Db::name('panic_buying')->where("id=$id")->find();
            $info['start_time'] = date('Y-m-d H:i', $info['start_time']);
            $info['end_time'] = date('Y-m-d H:i', $info['end_time']);
        }
        $this->assign('info', $info);
        $this->assign('min_date', date('Y-m-d'));
        return $this->fetch();
    }

    // 获取商品
    public function search_goods()
    {
        $GoodsLogic = new GoodsLogic;
        $brandList = $GoodsLogic->getSortBrands();
        $this->assign('brandList', $brandList);
        $categoryList = $GoodsLogic->getSortCategory();
        $this->assign('categoryList', $categoryList);

        $goods_id = I('goods_id');
        $where = ' is_on_sale = 1 and prom_type=0 and store_count>0 and examine = 1';//搜索条件
        if (!empty($goods_id)) {
            $where .= " and goods_id not in ($goods_id) ";
        }
        I('intro') && $where = "$where and " . I('intro') . " = 1";
        if (I('cat_id')) {
            $this->assign('cat_id', I('cat_id'));
            $grandson_ids = getCatGrandson(I('cat_id'));
            $where = " $where  and cat_id in(" . implode(',', $grandson_ids) . ") "; // 初始化搜索条件
        }
        if (I('brand_id')) {
            $this->assign('brand_id', I('brand_id'));
            $where = "$where and brand_id = " . I('brand_id');
        }
        if (!empty($_REQUEST['keywords'])) {
            $this->assign('keywords', I('keywords'));
            $where = "$where and (goods_name like '%" . I('keywords') . "%' or keywords like '%" . I('keywords') . "%')";
        }
        $count = Db::name('goods')->where($where)->count();
        $Page = new Page($count, 10);
        $goodsList = Db::name('goods')->where($where)->order('goods_id DESC')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        // dump($goodsList);die;
        $show = $Page->show();//分页显示输出
        $this->assign('page', $show);//赋值分页输出
        $this->assign('goodsList', $goodsList);
        $this->assign('pager', $Page);//赋值分页输出
        $tpl = I('get.tpl', 'search_goods');

        return $this->fetch($tpl);
    }


    // 删除活动
    public function panic_buying_del()
    {
        $id = I('del_id');
        if ($id) {
            Db::name('panic_buying')->where("id=$id")->delete();
            Db::name('goods')->where("(prom_type=1 or prom_type =5)and prom_id=$id")->update(array('prom_id' => 0, 'prom_type' => 0));
            exit(json_encode(1));
        } else {
            exit(json_encode(0));
        }
    }
    
    
    //折扣活动类型管理
    public function cate_list(){
    	$condition = array();
    	$model = Db::name('activity_cate');
    	$count = $model->where($condition)->count();
    	$Page = new Page($count, 10);
    	$show = $Page->show();
    	$prom_list = $model->where($condition)->order("id desc")->limit($Page->firstRow . ',' . $Page->listRows)->select();
    	$this->assign('cate_list', $prom_list);
    	$this->assign('page', $show);// 赋值分页输出
    	$this->assign('pager', $Page);
    	return $this->fetch();
    }
	
	
    //增加活动类型
    public function cate_add(){
    	$id=I('id');
    	if($_POST['save']=="save"){
    		$data['name'] = I('post.name');
    		$data['is_display']=I('post.is_display');
    		$data['addtime']=time();
    	    $insert_id = Db::name('activity_cate')->insertGetId($data);
    	    if($insert_id>0){
    	    	adminLog("管理员添加折扣活动类型 " . $data['name']);
    	    	$return = ['status' => 1, 'msg' => '操作成功', 'result' => ''];
    	    }else{
    	    	$return = ['status' => 0, 'msg' => '操作失败', 'result' => ''];
    	    }
    	   $this->ajaxReturn($return);
    	}
    	//$info = Db::name('activity_cate')->where("id=$id")->find();
    	//$this->assign('info',$info);
    	return $this->fetch();
    }
	
	
    //删除活动类型
    public function cate_del(){
    	$id = I('id');
    	if ($id) {
    		Db::name('activity_cate')->where("id=$id")->delete();
    		exit(json_encode(1));
    	} else {
    		exit(json_encode(0));
    	}
    }
	
    
    //活动列表
    public function activity_list(){
    	$condition = array();
    	$type=I('act_type');
    	if(!empty($type)){
    		$condition['act_id']=$type;
    	} 
    	$model = Db::name('activity_goods');
    	$count = $model->where($condition)->count();
    	$Page = new Page($count, 10);
    	$show = $Page->show();
    	$field="A.id,A.goods_id,A.act_id,A.addtime,B.goods_name,B.store_count,B.shop_price,C.name,C.is_display";
    	$prom_list = DB::name('activity_goods')->alias('A')->join('goods B','A.goods_id =B.goods_id')->join('activity_cate C','A.act_id = C.id')->where($condition)->field($field)->order('A.id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
    	
    	$act_type=Db::name('activity_cate')->where(array('is_display'=>'1'))->select();
    	$this->assign('act_type', $act_type);
    	$this->assign('act_list', $prom_list);
    	$this->assign('act_id', $type);
    	$this->assign('page', $show);// 赋值分页输出
    	$this->assign('pager', $Page);
    	return $this->fetch();
    }

    //添加活动
    public function activity_add(){
    	$id=I('id');
    	if($_POST['save']=="save"){
    		$data['act_id']=I('post.act_id');
    		$data['goods_id']=I('post.goods_id');
    		if(empty($data['act_id']) || empty($data['goods_id'])){
    			$return = ['status' => 0, 'msg' => '操作失败', 'result' => ''];
    			$this->ajaxReturn($return);
    		}
    		$data['addtime']=time();
    		$insert_id = Db::name('activity_goods')->insertGetId($data);
    		if($insert_id>0){
    			adminLog("管理员添加活动商品： " . $data['name']);
    			$return = ['status' => 1, 'msg' => '操作成功', 'result' => ''];
    		}else{
    			$return = ['status' => 0, 'msg' => '操作失败', 'result' => ''];
    		}
    		$this->ajaxReturn($return);
    	}
    	$act_type=Db::name('activity_cate')->where(array('is_display'=>'1'))->select();
    	$this->assign('act_type', $act_type);
    	return $this->fetch();
    }
    
    //删除活动
    public  function activity_del(){
    	$id = I('id');
    	if ($id) {
    		Db::name('activity_goods')->where("id=$id")->delete();
    		exit(json_encode(1));
    	} else {
    		exit(json_encode(0));
    	}
    }
	
	
	/**
	* 促销活动
	*/
	public function prom_goods_list(){
		
		$parse_type = array('0' => '直接打折', '1' => '单品满减优惠', '2' => '固定金额出售');

        $this->assign("parse_type", $parse_type);

        $count = Db::name('prom_goods')->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $res = Db::name('prom_goods')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        if ($res) {
            foreach ($res as $val) {
                if (!empty($val['group']) && !empty($lv)) {
                    $val['group'] = explode(',', $val['group']);
                    foreach ($val['group'] as $v) {
                        $val['group_name'] .= $lv[$v] . ',';
                    }
                }
                $prom_list[] = $val;
            }
        }
        $this->assign('pager',$Page);
        $this->assign('page', $show);// 赋值分页输出
        $this->assign('prom_list', $prom_list);
        return $this->fetch();
		
		
		
	}
	
	
	/**
	 * 促销活动详情
	 */	
	public function prom_goods_info()
    {

        $prom_id = I('id');
        $info['start_time'] = date('Y-m-d H:i');
        $info['end_time'] = date('Y-m-d H:i', time() + 3600 * 60 * 24);
        if ($prom_id > 0) {
            $info = Db::name('prom_goods')->where("id=$prom_id")->find();
            $info['start_time'] = date('Y-m-d H:i', $info['start_time']);
            $info['end_time'] = date('Y-m-d H:i', $info['end_time']);
            $prom_goods = Db::name('goods')->where("prom_id=$prom_id and prom_type=3")->select();
            $this->assign('prom_goods', $prom_goods);
        }
        $this->assign('info', $info);
        $this->assign('min_date', date('Y-m-d'));
        
        return $this->fetch();
    }

	
	/**
	 * 促销活动添加、修改
	 */	
    public function prom_goods_save()
    {
        $prom_id = I('id');
        $data = I('post.');
        $data['start_time'] = strtotime($data['start_time']);
        $data['end_time'] = strtotime($data['end_time']);
        if($data['start_time']>=$data['end_time']){
            $this->error('开始时间不能大于结束时间', Url::build('Promotion/prom_goods_list'));
        }
        $data['group'] = $data['group'] ? implode(',', $data['group']) : '';
        $data['goods_ids'] = $data['goods_id'] ? implode(',', $data['goods_id']) : '';
        // dump($data);die;
        if ($prom_id) {
            Db::name('prom_goods')->where("id", $prom_id)->save($data);
            $goods['prom_id']=$prom_id;
            // $last_id = $prom_id;
            adminLog("管理员修改了商品促销 " . I('name'));
        } else {
            // $last_id = Db::name('prom_goods')->add($data);
            Db::name('prom_goods')->add($data);
            $good= Db::name('prom_goods')->field('id')->order('id','desc')->find();
            $goods['prom_id']=$good['id'];
            adminLog("管理员添加了商品促销 " . I('name'));
        }

        if (is_array($data['goods_id'])) {
            $goods_id = implode(',', $data['goods_id']);
            $where=$data['goods_id'];
            if ($prom_id > 0) {
                Db::query("update ylt_goods set prom_type=REPLACE(prom_type,',3','') where prom_id REGEXP ',$prom_id'");
                Db::query("update ylt_goods set prom_id=REPLACE(prom_id,',$prom_id','') where prom_id REGEXP ',$prom_id'");
                // Db::name("goods")->where("prom_id=$prom_id and prom_type=3")->save(array('prom_id' => 0, 'prom_type' => 0));
            }
            for ($i=0; $i < count($where); $i++) { 
                    //CONCAT 方法在字段内的后方加入字符串
                Db::query("update ylt_goods  set prom_id=CONCAT(prom_id,',$goods[prom_id]') where goods_id='$where[$i]'");
                Db::query("update ylt_goods  set prom_type=CONCAT(prom_type,',3') where goods_id='$where[$i]'");
                }
            // Db::name("goods")->where("goods_id in($goods_id)")->save(array('prom_id' => $last_id, 'prom_type' => 3));
        }
        $this->success('编辑促销活动成功', Url::build('Promotion/prom_goods_list'));
    }

	
	/**
	 * 促销活动删除
	 */	
    public function prom_goods_del()
    {
        $prom_id = I('id');
        $order_goods = Db::name('order_goods')->where("prom_type = 3 and prom_id = $prom_id")->find();
        if (!empty($order_goods)) {
            $this->error("该活动有订单参与不能删除!");
        }
        Db::query("update ylt_goods set prom_type=REPLACE(prom_type,',3','') where prom_id REGEXP ',$prom_id'");
        Db::query("update ylt_goods set prom_id=REPLACE(prom_id,',$prom_id','') where prom_id REGEXP ',$prom_id'");
        // Db::name("goods")->where("prom_id=$prom_id and prom_type=3")->save(array('prom_id' => 0, 'prom_type' => 0));
        Db::name('prom_goods')->where("id=$prom_id")->delete();
        $this->success('删除活动成功', Url::build('Promotion/prom_goods_list'));
    }
	
	
	/**
	 * 促销活动商品
	 */	
	public function get_goods()
    {
        $prom_id = I('id');
        $promGoods = Db::name('prom_goods')->where(['id'=>$prom_id])->find();
        $goodsList = Db::name('goods')->where('goods_id','in',$promGoods['goods_ids'])->select();
        $this->assign('goodsList', $goodsList);
        return $this->fetch();
    }
	
	
	/**
	 * 促销活动数据
	 */	
    public function prom_data()
    {
        $prom_id = I('id');
		$code = I('code');
        $prom = Db::name('prom_goods')->where('id',$prom_id)->find();
		
		$count = Db::name('goods')->where('goods_id','in',$prom['goods_ids'])->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
	
		if($code == 'export'){
			$goods_list = Db::name('goods')->where('goods_id','in',$prom['goods_ids'])->select();
		}else{
			$goods_list = Db::name('goods')->where('goods_id','in',$prom['goods_ids'])->limit($Page->firstRow . ',' . $Page->listRows)->select();
		}
			
		// 获取活动数据
		 foreach ($goods_list as $k=>$val){
			$val['order_num'] = Db::name('order_goods')->where(['prom_type'=>3,'prom_id'=>$prom['id'],'goods_id'=>$val['goods_id']])->group('order_id')->Cache(true,3600)->count();
			
			$val['pay_order_num'] = Db::name('order')->alias('og')->join('order_goods o','og.order_id =o.order_id','left')->where(['o.prom_type'=>3,'o.prom_id'=>$prom['id'],'o.goods_id'=>$val['goods_id'],'og.pay_status'=>1])->Cache(true,3600)->count();
			
			$val['order_amount'] = Db::name('order_goods')->alias('og')->join('order o','og.order_id =o.order_id','left')->where(['og.prom_type'=>3,'og.prom_id'=>$prom['id'],'og.goods_id'=>$val['goods_id'],'o.pay_status'=>1])->Cache(true,3600)->sum('og.goods_price * og.goods_num');
			
			$val['order_amount'] = $val['order_amount'] ? $val['order_amount'] : 0;
			
			$val['order_prom_amount'] = Db::name('order')->alias('og')->join('order_goods o','og.order_id =o.order_id','left')->where(['o.prom_type'=>3,'o.prom_id'=>$prom['id'],'o.goods_id'=>$val['goods_id'],'og.pay_status'=>1])->Cache(true,3600)->sum('og.order_prom_amount');
			$val['order_prom_amount'] = $val['order_prom_amount'] ? $val['order_prom_amount'] : 0;
			
			$val['back_num'] = Db::name('back_order')->where(['prom_type'=>3,'prom_id'=>$prom['id'],'goods_id'=>$val['goods_id']])->Cache(true,3600)->count();
			
			$val['back_amount'] = Db::name('back_order')->where(['prom_type'=>3,'prom_id'=>$prom['id'],'goods_id'=>$val['goods_id']])->Cache(true,3600)->sum('shop_price');;
			$val['back_amount'] = $val['back_amount'] ? $val['back_amount'] : 0;
			$goods_lists[] = $val;
		 }
		 
		if($code == 'export'){
		// 数据导出

    	$strTable ='<table width="500" border="1">';
    	$strTable .= '<tr>';
    	$strTable .= '<td style="text-align:center;font-size:12px;width:100px;">商品id</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="300">商品名称</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="200">商铺名称</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">销售单价</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">订单数量</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">已支付订单</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">订单金额</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">减免费用</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">退货数量</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">退货金额</td>';
    	$strTable .= '</tr>';
	    if(is_array($goods_lists)){
	   
	    	foreach($goods_lists as $k=>$val){
	    		$strTable .= '<tr>';
	    		$strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['goods_id'].'</td>';
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['goods_name'].' </td>';	    		
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['supplier_name'].'</td>';
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['shop_price'].'</td>';
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['order_num'].'</td>';
				$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['pay_order_num'].'</td>';
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['order_amount'].' </td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['order_prom_amount'].' </td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['back_num'].' </td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['back_amount'].' </td>';
	    		$strTable .= '</tr>';
                
	    	}
	    }
    	$strTable .='</table>';
    	unset($orderList);
    	downloadExcel($strTable,'促销活动数据');
    	exit();
		}
	
		$this->assign('pager',$Page);
        $this->assign('page', $show);// 赋值分页输出
        $this->assign('prom', $prom);
        $this->assign('goods_list', $goods_lists);
		return $this->fetch();
    }
        /**
         * [add_brand_goods 促销活动商品添加]
         */
   public function add_brand_goods(){
	   	$GoodsLogic = new GoodsLogic;
	   	$brandList = $GoodsLogic->getSortBrands();
	   	$this->assign('brandList', $brandList);
	   	$categoryList = $GoodsLogic->getSortCategory();
	   	$this->assign('categoryList', $categoryList);
	   	
	   	$goods_id = I('goods_id');
	   	$where = ' is_on_sale = 1 and prom_type=0 and store_count>0 and examine = 1';//搜索条件
	   	if (!empty($goods_id)) {
	   		$where .= " and goods_id not in ($goods_id) ";
	   	}
	   	I('intro') && $where = "$where and " . I('intro') . " = 1";
	   	if (I('cat_id')) {
	   		$this->assign('cat_id', I('cat_id'));
	   		$grandson_ids = getCatGrandson(I('cat_id'));
	   		$where = " $where  and cat_id in(" . implode(',', $grandson_ids) . ") "; // 初始化搜索条件
	   	}
	   	if (I('brand_id')) {
	   		$this->assign('brand_id', I('brand_id'));
	   		$where = "$where and brand_id = " . I('brand_id');
	   	}
	   	if (!empty($_REQUEST['keywords'])) {
	   		$this->assign('keywords', I('keywords'));
	   		$where = "$where and (goods_name like '%" . I('keywords') . "%' or keywords like '%" . I('keywords') . "%')";
	   	}
	   	$count = Db::name('goods')->where($where)->count();
	   	$Page = new Page($count, 10);
	   	$goodsList = Db::name('goods')->where($where)->order('goods_id DESC')->limit($Page->firstRow . ',' . $Page->listRows)->select();
	   	$show = $Page->show();//分页显示输出
	   	$this->assign('page', $show);//赋值分页输出
	   	$this->assign('goodsList', $goodsList);
	   	$this->assign('pager', $Page);//赋值分页输出
	   	$tpl = I('get.tpl', 'search_brand_goods');
	   	
	   	return $this->fetch($tpl);
    }
	
	
	
	/**
	* 折扣/秒杀活动
	*/
	public function discount_list(){
        $count = Db::name('discount_buy')->where('buy_type = 1 || buy_type = 2')->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $discount_list = Db::name('discount_buy')->where('buy_type = 1 || buy_type = 2')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('pager',$Page);
        $this->assign('page', $show);// 赋值分页输出
        $this->assign('discount_list', $discount_list);
        return $this->fetch();
	}
	
	
	/**
	 * 折扣/秒杀活动详情
	 */	
	public function discount_info()
    {

        $discount_id = I('id');
        $info['start_time'] = date('Y-m-d H:i');
        $info['end_time'] = date('Y-m-d H:i', time() + 3600 * 60 * 24);
        if ($discount_id > 0) {
            $info = Db::name('discount_buy')->where("id=$discount_id")->find();
            $info['start_time'] = date('Y-m-d H:i', $info['start_time']);
            $info['end_time'] = date('Y-m-d H:i', $info['end_time']);
            $discount_goods = Db::name('discount_goods')->where("discount_id=$discount_id ")->order('sort')->select();
            $this->assign('discount_goods', $discount_goods);
        }
        $this->assign('info', $info);
        $this->assign('min_date', date('Y-m-d'));
        
        return $this->fetch();
    }

	
	/**
	 * 折扣/秒杀活动添加、修改
	 */	
    public function discount_save()
    {
        $discount_id = I('id');
        $data = I('post.');
        $data['start_time'] = strtotime($data['start_time']);
        $data['end_time'] = strtotime($data['end_time']);
        if($data['start_time']>=$data['end_time']){
            $this->error('开始时间不能大于结束时间');
        }
        
       // $data['goods_ids'] = $data['goods_id'] ? implode(',', $data['goods_id']) : '';.
	   
		$goods_ids		= $data['goods_id'];
		$goods_thumb 	= $data['goods_thumb'];
		$goods_name		= $data['goods_name'];
		$market_price	= $data['market_price'];
		$aMarketPrice	= $data['activity_market_price'];
		$activity_price	= $data['activity_price'];
		$store_count	= $data['store_count'];
		$activity_count	= $data['activity_count'];
		$sort			= $data['sort'];
		$browse_num		= $data['browse_num'];
		$order_num		= $data['order_num'];
		$buy_num		= $data['buy_num'];
			
	
        if ($discount_id) {
			 Db::name('discount_buy')->where("id", $discount_id)->update($data);
			 Db::name('discount_goods')->where("discount_id", $discount_id)->delete();
			 adminLog("管理员修改了折扣/秒杀活动 " . I('title'));
		}else{
			$data['is_start'] = 1;
			$discount_id = Db::name('discount_buy')->insertGetId($data);
			 adminLog("管理员添加了折扣/秒杀活动 " . I('title'));
		}
           
		if ($goods_ids) {
			foreach($goods_ids as $key => $v){
				if($store_count[$key] < $activity_count[$key])
					$this->error('活动库存不能大于原库存', Url::build('Promotion/prom_goods_list'));
				$discount_goods['discount_id']	=$discount_id;
				$discount_goods['goods_id']		=$goods_ids[$key];
				$discount_goods['goods_name']	=$goods_name[$key];
				$discount_goods['goods_thumb']	=$goods_thumb[$key];
				$discount_goods['market_price']	=$market_price[$key];
				$discount_goods['activity_market_price']=$aMarketPrice[$key];
				$discount_goods['activity_price']=$activity_price[$key];
				$discount_goods['store_count']	=$store_count[$key];
				$discount_goods['activity_count']=$activity_count[$key];
				$discount_goods['sort']			=$sort[$key];
				$discount_goods['browse_num']	=$browse_num[$key];
				$discount_goods['order_num']	=$order_num[$key];
				$discount_goods['buy_num']		=$buy_num[$key];
				$r = Db::name('discount_goods')->insert($discount_goods);
			}
		}
         
        if (is_array($data['goods_id'])) {
            $goods_id = implode(',', $data['goods_id']);
            if ($discount_id > 0) {
                Db::name("goods")->where("prom_id=$discount_id and prom_type=2")->update(array('prom_id' => 0, 'prom_type' => 0));
            }
            Db::name("goods")->where("goods_id in($goods_id)")->update(array('prom_id' => $discount_id, 'prom_type' => 2));
        }
        $this->success('编辑促销活动成功', Url::build('Promotion/discount_list'));
    }

	
	/**
	 * 折扣/秒杀活动删除
	 */	
    public function discount_del()
    {
        $discount_buy = I('id');
        $order_goods = Db::name('order_goods')->where("prom_type = 2 and prom_id = $discount_buy")->find();
        if (!empty($order_goods)) {
            $this->error("该活动有订单参与不能删除!");
        }
        Db::name("goods")->where("prom_id=$discount_buy and prom_type=2")->update(array('prom_id' => 0, 'prom_type' => 0));
		Db::name('cart')->where("prom_id = $discount_buy and prom_type = 2")->delete();
		Db::name('discount_buy')->where("id",$discount_buy)->update(array('is_start' => 0));
        //Db::name('discount_goods')->where("discount_id=$discount_buy")->delete();
		//Db::name('discount_buy')->where("id=$discount_buy")->delete();
        $this->success('删除活动成功', Url::build('Promotion/prom_goods_list'));
    }
	
	
	/**
	 * 折扣/秒杀活动商品选择
	 */	
	public function add_discount_goods(){
	   	$GoodsLogic = new GoodsLogic;
	   	$brandList = $GoodsLogic->getSortBrands();
	   	$this->assign('brandList', $brandList);
	   	$categoryList = $GoodsLogic->getSortCategory();
	   	$this->assign('categoryList', $categoryList);
	   	
	   	$goods_id = I('goods_id');
		if (!empty(input('discount'))){
			$discount = input('discount') / 10;
			$this->assign('discount',$discount);
		}
	   	$where = ' is_on_sale = 1 and prom_type in(0,1,2,3,5) and store_count>0 and examine = 1';//搜索条件
	   	if (!empty($goods_id)) {
	   		$where .= " and goods_id not in ($goods_id) ";
	   	}
	   	I('intro') && $where = "$where and " . I('intro') . " = 1";
	   	if (I('cat_id')) {
	   		$this->assign('cat_id', I('cat_id'));
	   		$grandson_ids = getCatGrandson(I('cat_id'));
	   		$where = " $where  and cat_id in(" . implode(',', $grandson_ids) . ") "; // 初始化搜索条件
	   	}
	   	if (I('brand_id')) {
	   		$this->assign('brand_id', I('brand_id'));
	   		$where = "$where and brand_id = " . I('brand_id');
	   	}
	   	if (!empty($_REQUEST['keywords'])) {
	   		$this->assign('keywords', I('keywords'));
	   		$where = "$where and (goods_name like '%" . I('keywords') . "%' or keywords like '%" . I('keywords') . "%')";
	   	}

	   	$count = Db::name('goods')->where($where)->count();
	   	$Page = new Page($count, 10);
	   	$goodsList = Db::name('goods')->where($where)->order('goods_id DESC')->limit($Page->firstRow . ',' . $Page->listRows)->select();
	   	$show = $Page->show();//分页显示输出
	   	$this->assign('page', $show);//赋值分页输出
	   	$this->assign('goodsList', $goodsList);
	   	$this->assign('pager', $Page);//赋值分页输
		
	   	$tpl = I('get.tpl', 'add_discount_goods');
	   	
	   	return $this->fetch($tpl);
    }

    /**
     * [appointment 预约活动]
     * @return [type] [description]
     */
    public function appointment_list(){
        $count = Db::name('discount_buy')->where('is_start',1)->where('buy_type = 6')->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $discount_list = Db::name('discount_buy')->where('is_start',1)->where('buy_type = 6')->limit($Page->firstRow . ',' . $Page->listRows)->select();
   
        $this->assign('pager',$Page);
        $this->assign('page', $show);// 赋值分页输出
        $this->assign('discount_list', $discount_list);
        return $this->fetch();
    }

    /**
     * 预约活动详情
     */ 
    public function appointment_info()
    {

        $discount_id = I('id');
        $info['make_go'] = date('Y-m-d H:i');
        $info['make_in'] = date('Y-m-d H:i', time() + 3600 * 24 *1 );
        $info['purchase_go'] = date('Y-m-d H:i', time() + 3600 * 24  * 2);
        $info['purchase_in'] = date('Y-m-d H:i', time() + 3600 *  24 * 3);
        $info['express_go'] = date('Y-m-d H:i', time() + 3600 *  24 * 4);
        if ($discount_id > 0) {
            $info = Db::name('discount_buy')->where("id=$discount_id")->find();
            $info['make_go'] = date('Y-m-d H:i', $info['make_go']);
            $info['make_in'] = date('Y-m-d H:i', $info['make_in']);
            $info['purchase_go'] = date('Y-m-d H:i', $info['purchase_go']);
            $info['purchase_in'] = date('Y-m-d H:i', $info['purchase_in']);
            $info['express_go'] = date('Y-m-d H:i', $info['express_go']);
            $discount_goods = Db::name('discount_goods')->where("discount_id=$discount_id ")->order('sort')->select();
            $this->assign('discount_goods', $discount_goods);
        }
        $this->assign('info', $info);
        $this->assign('min_date', date('Y-m-d'));
        
        return $this->fetch();
    }

    /**
     * 预约活动添加、修改
     */ 
    public function appointment_save()
    {
        $discount_id = I('id');
        $data = I('post.');
        $data['start_time'] = $data['make_go'] = strtotime($data['make_go']);
        $data['make_in'] = strtotime($data['make_in']);
        $data['purchase_go'] = strtotime($data['purchase_go']);
        $data['end_time'] = $data['purchase_in'] = strtotime($data['purchase_in']);
        $data['express_go'] = strtotime($data['express_go']);
        if($data['make_go']>=$data['make_in']){
            $this->error('预约开始时间不能大于预约结束时间');
        }
        if($data['make_in']>=$data['purchase_go']){
            $this->error('预约结束时间不能大于抢购开始时间');
        }
        if($data['purchase_go']>=$data['purchase_in']){
            $this->error('抢购开始时间不能大于抢购结束时间');
        }
        if($data['purchase_in']>=$data['express_go']){
            $this->error('抢购结束时间不能大于预计发货时间');
        }
       // $data['goods_ids'] = $data['goods_id'] ? implode(',', $data['goods_id']) : '';.
       
        $goods_ids      = $data['goods_id'];
        $goods_thumb    = $data['goods_thumb'];
        $goods_name     = $data['goods_name'];
        $market_price   = $data['market_price'];
        $aMarketPrice   = $data['activity_market_price'];
        $activity_price = $data['activity_price'];
        $store_count    = $data['store_count'];
        $activity_count = $data['activity_count'];
        $sort           = $data['sort'];
        $browse_num     = $data['browse_num'];
        $order_num      = $data['order_num'];
        $buy_num        = $data['buy_num'];
            
    
        if ($discount_id) {
             Db::name('discount_buy')->where("id", $discount_id)->update($data);
             Db::name('discount_goods')->where("discount_id", $discount_id)->delete();
             adminLog("管理员修改了预约活动 " . I('title'));
        }else{
            $data['is_start'] = 1;
            $discount_id = Db::name('discount_buy')->insertGetId($data);
             adminLog("管理员添加了预约活动 " . I('title'));
        }
           
        if ($goods_ids) {
            foreach($goods_ids as $key => $v){
                if($store_count[$key] < $activity_count[$key])
                    $this->error('活动库存不能大于原库存');
                $discount_goods['discount_id']  =$discount_id;
                $discount_goods['goods_id']     =$goods_ids[$key];
                $discount_goods['goods_name']   =$goods_name[$key];
                $discount_goods['goods_thumb']  =$goods_thumb[$key];
                $discount_goods['market_price'] =$market_price[$key];
                $discount_goods['activity_market_price']=$aMarketPrice[$key];
                $discount_goods['activity_price']=$activity_price[$key];
                $discount_goods['store_count']  =$store_count[$key];
                $discount_goods['activity_count']=$activity_count[$key];
                $discount_goods['sort']         =$sort[$key];
                $discount_goods['browse_num']   =$browse_num[$key];
                $discount_goods['order_num']    =$order_num[$key];
                $discount_goods['buy_num']      =$buy_num[$key];
                $r = Db::name('discount_goods')->insert($discount_goods);
            }
        }
         
        if (is_array($data['goods_id'])) {
            $goods_id = implode(',', $data['goods_id']);
            if ($discount_id > 0) {
                Db::name("goods")->where("prom_id=$discount_id and prom_type=6")->update(array('prom_id' => 0, 'prom_type' => 0));
            }
            Db::name("goods")->where("goods_id in($goods_id)")->update(array('prom_id' => $discount_id, 'prom_type' => 6));
        }
        $this->success('编辑预约活动成功', Url::build('Promotion/appointment_list'));
    }

    /**
     * 预约活动删除
     */ 
    public function appointment_del()
    {
        $discount_buy = I('id');
        $order_goods = Db::name('order_goods')->where("prom_type = 6 and prom_id = $discount_buy")->find();
        if (!empty($order_goods)) {
            $this->error("该活动有订单参与不能删除!");
        }
        Db::name("goods")->where("prom_id=$discount_buy and prom_type=6")->update(array('prom_id' => 0, 'prom_type' => 0));
        Db::name('cart')->where("prom_id = $discount_buy and prom_type = 6")->delete();
        Db::name('discount_buy')->where("id",$discount_buy)->update(array('is_start' => 0));
        //Db::name('discount_goods')->where("discount_id=$discount_buy")->delete();
        //Db::name('discount_buy')->where("id=$discount_buy")->delete();
        $this->success('删除活动成功', Url::build('Promotion/appointment_list'));
    }


    /**
     * [share_the_bill 拼单活动]
     * @return [type] [description]
     */
    public function share_the_bill_list(){
        $count = Db::name('discount_buy')->where('is_start',1)->where('buy_type = 7')->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $discount_list = Db::name('discount_buy')->where('is_start',1)->where('buy_type = 7')->limit($Page->firstRow . ',' . $Page->listRows)->select();
   
        $this->assign('pager',$Page);
        $this->assign('page', $show);// 赋值分页输出
        $this->assign('discount_list', $discount_list);
        return $this->fetch();
    }
    public function share_the_bill_info(){
        $discount_id = I('id');
        $info['start_time'] = date('Y-m-d H:i');
        $info['end_time']   = date('Y-m-d H:i', time() + 3600 * 24 *1 );
        if ($discount_id > 0) {
            $info = Db::name('discount_buy')->where("id=$discount_id")->find();
            $info['start_time'] = date('Y-m-d H:i', $info['start_time']);
            $info['end_time']   = date('Y-m-d H:i', $info['end_time']);
            $discount_goods = Db::name('discount_goods')->where("discount_id=$discount_id ")->order('sort')->select();
            $this->assign('discount_goods', $discount_goods);
        }
        $this->assign('info', $info);
        $this->assign('min_date', date('Y-m-d'));
        
        return $this->fetch();
    }

    /**
     * 拼单活动添加、修改
     */ 
    public function share_the_bill_save()
    {
        $discount_id = I('id');
        $data = I('post.');
        $data['start_time'] =  strtotime($data['start_time']);
        $data['end_time']   =  strtotime($data['end_time']);
        if($data['start_time']>=$data['end_time']){
            $this->error('拼单开始时间不能大于拼单结束时间');
        }
       
        $goods_ids      = $data['goods_id'];
        $goods_thumb    = $data['goods_thumb'];
        $goods_name     = $data['goods_name'];
        $market_price   = $data['market_price'];
        $aMarketPrice   = $data['activity_market_price'];
        $activity_price = $data['activity_price'];
        $store_count    = $data['store_count'];
        $activity_count = $data['activity_count'];
        $sort           = $data['sort'];
        $browse_num     = $data['browse_num'];
        $order_num      = $data['order_num'];
        $buy_num        = $data['buy_num'];
            
    
        if ($discount_id) {
             Db::name('discount_buy')->where("id", $discount_id)->update($data);
             Db::name('discount_goods')->where("discount_id", $discount_id)->delete();
             adminLog("管理员修改了拼单活动 " . I('title'));
        }else{
            $data['is_start'] = 1;
            $discount_id = Db::name('discount_buy')->insertGetId($data);
             adminLog("管理员添加了拼单活动 " . I('title'));
        }
           
        if ($goods_ids) {
            foreach($goods_ids as $key => $v){
                if($store_count[$key] < $activity_count[$key])
                    $this->error('活动库存不能大于原库存');
                $discount_goods['discount_id']  =$discount_id;
                $discount_goods['goods_id']     =$goods_ids[$key];
                $discount_goods['goods_name']   =$goods_name[$key];
                $discount_goods['goods_thumb']  =$goods_thumb[$key];
                $discount_goods['market_price'] =$market_price[$key];
                $discount_goods['activity_market_price']=$aMarketPrice[$key];
                $discount_goods['activity_price']=$activity_price[$key];
                $discount_goods['store_count']  =$store_count[$key];
                $discount_goods['activity_count']=$activity_count[$key];
                $discount_goods['sort']         =$sort[$key];
                $discount_goods['browse_num']   =$browse_num[$key];
                $discount_goods['order_num']    =$order_num[$key];
                $discount_goods['buy_num']      =$buy_num[$key];
                $r = Db::name('discount_goods')->insert($discount_goods);
            }
        }
         
        if (is_array($data['goods_id'])) {
            $goods_id = implode(',', $data['goods_id']);
            if ($discount_id > 0) {
                Db::name("goods")->where("prom_id=$discount_id and prom_type=7")->update(array('prom_id' => 0, 'prom_type' => 0));
            }
            Db::name("goods")->where("goods_id in($goods_id)")->update(array('prom_id' => $discount_id, 'prom_type' => 7));
            Db::name("share_the_bill")->where(["prom_id"=>$discount_id,"type"=>3])->update(array('type' => 1));
        }
        $this->success('编辑拼单活动成功', Url::build('Promotion/share_the_bill_list'));
    }
    /**
     * 拼单活动删除
     */ 
    public function share_the_bill_del()
    {
        $discount_buy = I('id');
        $order_goods = Db::name('order_goods')->where("prom_type = 7 and prom_id = $discount_buy")->find();
        if (!empty($order_goods)) {
            $this->error("该活动有订单参与不能删除!");
        }
        Db::name("goods")->where("prom_id=$discount_buy and prom_type=7")->update(array('prom_id' => 0, 'prom_type' => 0));
        Db::name('cart')->where("prom_id = $discount_buy and prom_type = 7")->delete();
        Db::name('discount_buy')->where("id",$discount_buy)->update(array('is_start' => 0));
        //Db::name('discount_goods')->where("discount_id=$discount_buy")->delete();
        //Db::name('discount_buy')->where("id=$discount_buy")->delete();
        $this->success('删除活动成功', Url::build('Promotion/share_the_bill_list'));
    }

    /**
     * [share_apply_list 拼单申请列表]
     * @return [type] [description]
     */
    public function share_apply_list(){
        $id=$_GET['prom_id'];
        $this->assign('id',$id);
        return $this->fetch();
    }
    /*
     *Ajax拼单申请列表
     */
    public function ajax_share_apply_list(){
        $condition = array();
        // 关键词搜索
        $key_word = I('key_word') ? trim(I('key_word')) : '';
        $prom_id = I('prom_id');
        $where = " prom_id = $prom_id";
        if($key_word)
        {
            $where .= " and (nickname like '%$key_word%' or p_id like '%$key_word%')" ;
        }
        $ress = Db::name('share_the_bill')->alias('s')->join('users u','s.u_id = u.user_id')->join('discount_goods g','s.goods_id = g.goods_id')->where($where)->field('s.*,u.nickname,u.head_pic,g.goods_name,g.goods_thumb,g.activity_price,g.market_price')->select();
        $count =count($ress);
        $Page  = new AjaxPage($count,20);
        $show = $Page->show();
        $res = Db::name('share_the_bill')->alias('s')->join('users u','s.u_id = u.user_id')->join('discount_goods g','s.goods_id = g.goods_id')->where($where)->order('id desc')->limit($Page->firstRow,$Page->listRows)->field('s.*,u.nickname,u.head_pic,g.goods_name,g.goods_thumb,g.activity_price,g.market_price')->select();

        $this->assign('prom_count',$res);
        $this->assign('page',$show);       // 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
    }
    public function share_apply_list_del(){
        $id = $_GET['id'];
        if ($id) {
            $del = Db::name("share_the_bill")->where('id',$id)->delete();
            if ($del) {
                $this->success('删除记录成功');
            }else{
                $this->error('删除记录失败');
            }
        }
    }
}