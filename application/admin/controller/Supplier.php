<?php
namespace ylt\admin\controller;
use think\Page;
use think\Db;
use think\Session;
use think\Request;
class Supplier extends Base{
	
	/**
	 * 入驻商家列表
	 */
	
    public function BusinessList(){
		
		if(I('export')){ // 导出数据
			$this->export_supplier(I('post.'));
		}

        $p = I('p/d',1);
		$keyword = input('keyword');
        $where = $keyword ? " (supplier_name like '%$keyword%' or company_name like '%$keyword%') and status=1 and is_designer = 0 " : "status=1 and is_designer = 0";
		$field = 'supplier_id,company_name,supplier_name,address,company_type,guimo,contacts_name,contacts_phone,email,business_sphere,add_time,status';
        $res= Db::name('supplier')->where($where)->field($field)->order('supplier_id desc')->page($p.',20')->select();
		
		if($res){
			//$supplier_ids = Db::name('supplier')->where($where)->column("supplier_id");
			//$goods_num = Db::name('goods')->where("supplier_id","in", implode(',', $supplier_ids))->column("goods_id,supplier_id");
		}
		
		$count = DB::name('supplier')->where($where)->count();
    	$Page = new Page($count,20);
    	$show = $Page->show();
		$this->assign('pager',$Page);
		$this->assign('page',$show);
        $this->assign( 'arr',$res );
        return $this->fetch();
    }
	
	
	/**
	 * 待审核、审核不通过列表
	 */
    public function BusinessExamine(){
		$p = I('p/d',1);
		$keyword = input('keyword');
		$code =  input('code');
        $where = $keyword ? " (supplier_name like '%$keyword%' or company_name like '%$keyword%') and status != 1 and is_designer = 0 and is_complete = 1 " : "status !=1  and is_designer = 0 and is_complete = 1 ";
		$field = 'supplier_id,company_name,supplier_name,address,company_type,guimo,contacts_name,contacts_phone,email,business_sphere,add_time,status';
		
		if(I('export')){
			
					// 数据导出
		$supplier_users = Db::name('supplier_user')->where('supplier_id',0)->order('admin_id','desc')->select();
    	$strTable ='<table width="500" border="1">';
    	$strTable .= '<tr>';
    	$strTable .= '<td style="text-align:center;font-size:12px;width:100px;">id</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="200">用户名称</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="200">公司名称</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">手机号码</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="200">注册账号时间</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">是否已注册</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">是否注册完成</td>';

    	$strTable .= '</tr>';
	    if(is_array($supplier_users)){
	   
	    	foreach($supplier_users as $k=>$val){
	    		$strTable .= '<tr>';
	    		$strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['admin_id'].'</td>';
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['user_name'].' </td>';	    		
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['company_name'].'</td>';
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['mobile'].'</td>';
				$strTable .= '<td style="text-align:left;font-size:12px;">'.date('Y-m-d h:i',$val['add_time']).'</td>';
				$reg = $val['supplier_id'] > 0  ? '已注册' :'未注册';
				$strTable .= '<td style="text-align:left;font-size:12px;">'.$reg.'</td>';
				$state = $val['state'] > 0 ? '已完成' : '未完成';
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$state.'</td>';
	    		$strTable .= '</tr>';
	    	}
	    }
    	$strTable .='</table>';
    	unset($supplier_users);
    	downloadExcel($strTable,'招商快速注册');
    	exit();
		}
		
        $res= Db::name('supplier')->field($field)->where($where)->order('supplier_id desc')->page($p.',20')->select();
		$count = DB::name('supplier')->where($where)->count();
    	$Page = new Page($count,20);
    	$show = $Page->show();
		$this->assign('pager',$Page);
		$this->assign('page',$show);
        $this->assign( 'arr',$res );
        return $this->fetch();
    }


    /**
	 * 企业采集待审核、审核不通过列表
	 */
    public function PurchaseExamine(){
    	$p = I('p/d',1);

        $keyword = input('keyword');
        $where = $keyword ? " (contacts_name like '%$keyword%' or company_name like '%$keyword%') " : '1=1';

        $field = 'id,supplier_id,company_name,title,description,contacts_name,goods_count,goods_name,tel,address,sustomized,inquiry_time,expect_time,sustomized,lnvoice_title,quote_ask,goods_ask,status,dead_time';
    	$list = Db::name('purchase')->field($field)->where($where)->order('add_time desc')->page($p.',20')->select();
    	
    	$count = DB::name('purchase')->where($where)->count();
    	$Page = new Page($count,20);
    	$show = $Page->show();
		$this->assign('pager',$Page);
		$this->assign('page',$show);
		$this->assign('list',$list);
    	return $this->fetch();
		
	}
  
	/*
		询报价管理
	 */

    public function PurchaseExamine2(){
    	$p = I('p/d',1);
        $keyword = input('keyword');
        $where = $keyword ? " (contacts_name like '%$keyword%' or company_name like '%$keyword%') " : '1=1';

        $field = 'id,supplier_id,company_name,title,description,contacts_name,tel,address,sustomized,inquiry_time,expect_time,sustomized,lnvoice_title,quote_ask,goods_ask,status,dead_time,operator';
    	$list = Db::name('purchase')->field($field)->where($where)->order('add_time desc')->page($p.',20')->select();
    	
    	$count = DB::name('purchase')->where($where)->count();
    	$Page = new Page($count,20);
    	$show = $Page->show();
      	if(IS_AJAX){
			$purchase_id=$_POST['purchase_id'];
			$data=Db::name('purchase')->field('operator')->where('id',$purchase_id)->find();
			$operatorstatus="";//操作者状态
			foreach($data as $value){
				$operatorstatus.=$value;
			}
			if($operatorstatus==2){
				DB::name('purchase')->where('id',$purchase_id)->delete();
				DB::name('purchase_list')->where('purchase_id',$purchase_id)->delete();
				return '删除成功';
			}else{
				return '只能删除平台操作';
			}
		}
		$this->assign('pager',$Page);
		$this->assign('page',$show);
		$this->assign('list',$list);
    	return $this->fetch();
		
	}


    /**
     * 企业采集详细信息查看
     */
    public function PurchaseDetail(){
        $id = I('get.id');

        $purchase = Db::name('purchase')->where('id',$id)->find();
        if(!$purchase)
            exit($this->error('采购商品不存在'));
		if(IS_POST){
			$status = I('status');

			$row = Db::name('purchase')->where(array('id'=>$id))->update($_POST);

			if($purchase['status'] != $status && $status == 1){
				 //sendCode($purchase['contacts_phone'],'尊敬的商户，您好！您提交的企业采集信息已通过审核.');
			}elseif($status == '2'){
				//sendCode($purchase['contacts_phone'],'尊敬的商户，您好！您提交的企业采集信息审核未通过，请按要求正确填写.');
			}elseif($status == '-1'){
				Db::name('purchase')->where('id',$id)->update(array('status'=>'-1'));
				
				//sendCode($purchase['contacts_phone'],'尊敬的商户，您好，您所提交的企业采集：'.$purchase['title'].' 已经关闭');
				adminLog('关闭企业采集'.$purchase['title'].'');
				exit($this->success('关闭企业采集'));
			}

				adminLog('企业采集审核 '.$purchase['title'].'');
			exit($this->success('审核成功'));
		}
        //地址省市区缓存
		$region_list = get_region_list();
        $this->assign('region_list',$region_list);

        $this->assign('su',$purchase);
        return $this->fetch();
    }
	
    /*
    	后台采购详情页
     */
    public function PurchaseDetail2(){
        $id = I('get.id');
		$goodsNum=Db::name('purchase_list')->field('id')->where('purchase_id',$id)->find();//获取purchase_list表的id
		// dump($goodsNum);dump($id);die;
		$purchase_list_id="";
		foreach($goodsNum as $key =>$value){
			$purchase_list_id.=$value;
		}
        $purchase = Db::name('purchase')->where('id',$id)->find();
        $list     =Db::name('purchase_list')->where('purchase_id',$id)->select();
      	$getOneData=Db::name('purchase_list')->where(['purchase_id'=>$id,'id'=>$purchase_list_id])->select();//获取一个数据即可
		$wxnum="";
		foreach($getOneData as $value){
			$wxnum.=$value['wxnum'];//获取一个微信
		}
		$qqnum="";
		foreach($getOneData as $value){
			$qqnum.=$value['qqnum'];//获取一个qq
		}
		$email="";
		foreach($getOneData as $value){
			$email.=$value['email'];//获取一个电子邮箱
		}
		$company_phone="";
		foreach($getOneData as $value){
			$company_phone.=$value['company_phone'];//获取一个座机号
		}
        if(!$purchase)
            exit($this->error('采购商品不存在'));
		if(IS_POST){
			$status = I('status');

			$row = Db::name('purchase')->where(array('id'=>$id))->update($_POST);

			if($purchase['status'] != $status && $status == 1){
				 //sendCode($purchase['contacts_phone'],'尊敬的商户，您好！您提交的企业采集信息已通过审核.');
			}elseif($status == '2'){
				//sendCode($purchase['contacts_phone'],'尊敬的商户，您好！您提交的企业采集信息审核未通过，请按要求正确填写.');
			}elseif($status == '-1'){
				Db::name('purchase')->where('id',$id)->update(array('status'=>'-1'));
				
				//sendCode($purchase['contacts_phone'],'尊敬的商户，您好，您所提交的企业采集：'.$purchase['title'].' 已经关闭');
				adminLog('关闭企业采集'.$purchase['title'].'');
				exit($this->success('关闭企业采集'));
			}

				adminLog('企业采集审核 '.$purchase['title'].'');
			exit($this->success('审核成功'));
		}
		// dump($list);
		// die;
        //地址省市区缓存
		$region_list = get_region_list();
        $this->assign('region_list',$region_list);

        $this->assign('su',$purchase);
        $this->assign('list',$list);
      	$this->assign('wxnum',$wxnum);
      	$this->assign('qqnum',$qqnum);
      	$this->assign('email',$email);
		$this->assign('company_phone',$company_phone);
        return $this->fetch();
    }
  
  	/*后台采购信息修改*/
  	public function editPurchaseDetail2()
	{
		if(IS_AJAX){
			$purchase_id=$_POST['purchase_id'];//purchaes_id
			$title=$_POST['title'];//采购标题位于purchase表
			$company_name=$_POST['company_name'];//公司名称位于purchase表
			$contacts_name=$_POST['contacts_name'];//联系人位于purchase表
			$tel=$_POST['tel'];//联系电话位于purchase表
			$budget=$_POST['budget'];//总预算
          	$wxnum=$_POST['wxnum'];//微信号
			$qqnum=$_POST['qqnum'];//QQ号
			$email=$_POST['email'];//电子邮箱
			$company_phone=$_POST['company_phone'];//座机
			$goods_name=$_POST['goods_name'];//产品名称位于purchase_list表
			$goods_norm=$_POST['goods_norm'];//规格位于purchase_list表
			$goods_color=$_POST['goods_color'];//颜色位于purchase_list表
			$goods_brand=$_POST['goods_brand'];//品牌位于purchase_list表
			$goods_unit=$_POST['goods_unit'];//单位位于purchase_list表
			$goods_num=$_POST['goods_num'];//数量位于purchase_list表
			$goods_sn=$_POST['goods_sn'];//产品编号位于purchase表
			$address=$_POST['address'];//地址位于purchase表
			$region=$_POST['region'];//供应商所在地位于purchase表
			$quote_ask=$_POST['quote_ask'];//报价要求位于purchase表
			$status=$_POST['status'];//审核状态位于purchase表
			$reply=$_POST['reply'];//审核回复(审核不通过时)位于purchase表

			Db::name('purchase')->where('id',$purchase_id)->update([
				'title'=>$title,
				'company_name'=>$company_name,
				'contacts_name'=>$contacts_name,
				'tel'=>$tel,
				'budget'=>$budget,
				'goods_sn'=>$goods_sn,
				'address'=>$address,
				'region'=>$region,
				'quote_ask'=>$quote_ask,
				'status'=>$status,
				'reply'=>$reply
			]);
			$goodsNum=Db::name('purchase_list')->field('id')->where('purchase_id',$purchase_id)->select();//获取purchase_list表的id
          	Db::name('purchase_list')->where('purchase_id',$purchase_id)->update(['wxnum'=>$wxnum,'qqnum'=>$qqnum,'email'=>$email,'company_phone'=>$company_phone]);
          
			foreach($goodsNum as $key =>$value){
				foreach($value as $item){
					Db::name('purchase_list')->where(['purchase_id'=>$purchase_id,'id'=>$item])->update([
                        'goods_name'=>$goods_name[$key],
                        'goods_norm'=>$goods_norm[$key],
                        'goods_color'=>$goods_color[$key],
                        'goods_brand'=>$goods_brand[$key],
                        'goods_unit'=>$goods_unit[$key],
                        'goods_num'=>$goods_num[$key]
                    ]);
				}
			}
		}
	}
	
	/**
     * 入驻商详细信息查看
     */
    public function detail(){

        $supplier_id = I('get.id');
        $supplier = Db::name('supplier')->where(array('supplier_id'=>$supplier_id))->find();
        if(!$supplier){
            exit($this->error('入驻商家不存在'));
        }
        $supplier['reading_protocol'] = explode(',',$supplier['reading_protocol']);
		if(IS_POST){
			$status = I('status');
			$row = Db::name('supplier')->where(array('supplier_id'=>$supplier_id))->update($_POST);
			//同步修改商铺设置的LOGO
			if($supplier['logo'] != input('logo')){
				Db::name('supplier_config')->where(['supplier_id'=>$supplier['supplier_id'],'name'=>'store_logo'])->update(['value'=>input('logo')]);
			}

			if($status == '1'){
				$_POST['enter_time']=time();
				Db::name('supplier')->where(array('supplier_id'=>$supplier_id))->update($_POST);
				$db = Db::name('supplier_role');
				if(!$count = $db->where("supplier_id", $supplier_id)->count()){
					$sql['role_name'] = '管理员';
					$sql['act_list'] = 'all';
					$sql['supplier_id'] = $supplier_id;
					$res = $db->insert($sql);
					$id = $db->getLastInsID($res);
				}
				if ($id) {
					Db::name('supplier_user')->where(array('supplier_id'=>$supplier_id))->update(array('state'=>1,'role_id'=>$id));
				}else{
					Db::name('supplier_user')->where(array('supplier_id'=>$supplier_id))->update(array('state'=>1));
				}
				if($supplier['status'] != $status){
					sendCode($supplier['contacts_phone'],'尊敬的商户，您好！您提交的商铺信息已通过审核，可正常上传商品');
				}
			}elseif($status == '2'){
				Db::name('supplier')->where('supplier_id',$supplier_id)->update(array('is_complete'=>0,'status'=>2));
				Db::name('supplier_user')->where('supplier_id',$supplier_id)->update(array('state'=>'0'));
				sendCode($supplier['contacts_phone'],'尊敬的商户，您好！您提交的商铺信息审核失败，请按要求正确填写.');
			}elseif($status == '-1'){
				Db::name('supplier')->where('supplier_id',$supplier_id)->update(array('status'=>'-1'));
				Db::name('supplier_user')->where('supplier_id',$supplier_id)->update(array('state'=>'0'));
				Db::name('goods')->where('supplier_id',$supplier_id)->update(array('is_on_sale'=>'0','examine'=>'0','is_delete'=>'1'));
				sendCode($supplier['contacts_phone'],'尊敬的商户，您好，你的一礼通店铺：'.$supplier['supplier_name'].' 已经关闭');
				adminLog('关闭店铺'.$supplier['supplier_name'].'');
				exit($this->error('关闭店铺'));
			}
				adminLog('入驻商家审核 '.$supplier['supplier_name'].'');
				exit($this->success('操作成功'));
		}

		$region_list = get_region_list();
        $this->assign('region_list',$region_list);

        $this->assign('su',$supplier);
        return $this->fetch();
    }
	
	
	/**
	 * 入驻商操作日志
	 */
	public function supplier_log(){
		
	
    	$p = I('p/d',1);
		$where = " 1=1 ";
		$keywords = input('keywords');
		input('keywords') ? $where .= " and s.supplier_name like '%$keywords%'" : false;
		
		$field = 'l.*,s.supplier_name,a.user_name';
    	$logs = DB::name('supplier_admin_log')->alias('l')->join('supplier_user a','a.admin_id =l.admin_id')->join('supplier s','l.supplier_id = s.supplier_id')->field($field)->where($where)->order('log_time DESC')->page($p.',20')->select();

    	$this->assign('list',$logs);
    	$count = DB::name('supplier_admin_log')->alias('l')->join('supplier s','l.supplier_id = s.supplier_id')->where($where)->count();
    	$Page = new Page($count,20);
    	$show = $Page->show();
		$this->assign('pager',$Page);
		$this->assign('page',$show);
    	return $this->fetch();
    }
	
	/**
	 * 删除店铺
	 */
	 public function delSupplier(){
		 
		$supplier_id = $_GET['id'];
		 
		Db::name("supplier")->where('supplier_id',$supplier_id)->delete();  //商家信息表
		Db::name("supplier_user")->where('supplier_id',$supplier_id)->delete(); // 商家用户表
		Db::name("supplier_role")->where('supplier_id',$supplier_id)->delete(); // 商家角色表
		Db::name("supplier_admin_log")->where('supplier_id',$supplier_id)->delete(); //入驻商操作日志
		Db::name("supplier_config")->where('supplier_id',$supplier_id)->delete(); //入驻商家商店设置
		 
		$filter_goods_id = Db::name('goods')->where(['supplier_id'=>$supplier_id])->column("goods_id");
		 
		 // 删除此商品        
        Db::name("Goods")->where("goods_id","in", implode(',', $filter_goods_id))->delete();  //商品表
        Db::name("cart")->where("goods_id","in", implode(',', $filter_goods_id))->delete();  // 购物车
        Db::name("comment")->where("goods_id","in", implode(',', $filter_goods_id))->delete();  //商品评论
        Db::name("goods_consult")->where("goods_id","in", implode(',', $filter_goods_id))->delete();  //商品咨询
        Db::name("goods_images")->where("goods_id","in", implode(',', $filter_goods_id))->delete();  //商品相册
        Db::name("goods_price")->where("goods_id","in", implode(',', $filter_goods_id))->delete();  //商品规格
        Db::name("spec_image")->where("goods_id","in", implode(',', $filter_goods_id))->delete();  //商品规格图片
        Db::name("goods_collect")->where("goods_id","in", implode(',', $filter_goods_id))->delete();  //商品收藏
        delFile(RUNTIME_PATH);    
			exit(json_encode(1));
       
	 }

	 /**
      *  入驻商家结算列表
      */
	 public function supplier_settlement_list(){

		$p = I('p/d');
		$keyword = input('keyword');
		$where = $keyword ?  "j.is_pay_ok = 0  and (s.supplier_name like '%$keyword%' or s.company_name like '%$keyword%')" : "j.is_pay_ok = 0";

	    $settlement = Db::name('supplier_settlement')->alias('j')->join('supplier s','j.supplier_id = s.supplier_id')->where($where)->field('j.*,s.supplier_name,s.company_name')->order('j.settlement_id','desc')->page($p.',15')->select();
		 
    	$count = DB::name('supplier_settlement')->alias('j')->join('supplier s','j.supplier_id = s.supplier_id')->where($where)->count();
    	$Page = new Page($count,15);
    	$show = $Page->show();

		$this->assign('pager',$Page);
		$this->assign('page',$show);
	    $this->assign('settlement',$settlement);
	    return $this->fetch();
     }
	 
	 /**
	 *  入驻商家结算详情
	 */
	 public function settlement_detail(){
		$id = input('id');
		$info = Db::name('supplier_settlement')->alias('j')->join('supplier s','j.supplier_id = s.supplier_id')->where("j.settlement_id = $id")->column('j.*,s.supplier_name,s.company_name');
		if(IS_POST){
			$edit['rebate_money'] = input('rebate_money');
			$edit['status'] = input('status');
			$edit['payable_price'] = $info['settlement_all'] - $edit['rebate_money'];
		
			Db::name('supplier_settlement')->where('settlement_id',$id)->update($edit);
			adminLog('入驻商家月结'.$info['suppplier_name']);
			exit($this->success('操作成功'));
		}
		$info = $info[$id];
		$this->assign('info',$info);
		return $this->fetch();
	 }
  	
    /*
    	询报价大厅
     */
    public function PurchaseHall(){
        $supplier_id = I('get.id');
        $id = I('get.id');
        $purchase 		  = Db::name('purchase')->where('id',$id)->find();
        $purchase_list    = Db::name('purchase_list')->where('purchase_id',$id)->select();
        if(!$purchase)
            exit($this->error('采购商品不存在'));

	    $supply=Db::name('supply')
			    ->field('s.title,s.id,s.phone,s.t,u.supplier_name,u.address,u.contacts_name,u.contacts_phone,u.address') 
			    ->alias('s')
				->join('supplier u','s.supplier_id = u.supplier_id')
			    ->where('s.purchase_id',$id)
			    ->select();
		$region_list = get_region_list();
        $this->assign('region_list',$region_list);
        $this->assign('purchase',$purchase);
        $this->assign('purchase_list',$purchase_list);
        $this->assign('supply',$supply);
        return $this->fetch();
    }
	public function check_ajax(){
        $supply_id = I('post.id');
	    $list=Db::name('supply_list')
			    ->field('s.goods_tprice,s.id,s.goods_freight,s.goods_duration,s.goods_sprice,p.goods_name,p.goods_norm,p.goods_color,p.goods_unit,p.goods_num,p.goods_brand')
			    ->alias('s')
				->join('purchase_list p','s.purlist_id = p.id')
			    ->where('s.supply_id',$supply_id)
			    ->select();
        if($list){
			$this->ajaxReturn(['status'=>1,'list'=>$list]);
        }
	}
	 
	 /**
	 * 结算订单
	 * @订单结算模块由支付回调执行，但目前不稳定，所以需要额外的结算操作
	 */
	 public function supplier_settlement(){
	
		 // 获取未成功结算的订单
		 $order_list = Db::name('order')->where("pay_status = 1 and is_distribut = 0")->select();
		 // 获取结算期间，进行订单结算
		 foreach($order_list AS $key => $val){
			 // 获取结算时间
			 $year = date('Y',$val['add_time']);
			 $month = date('m',$val['add_time']);
			 $add['settlement_paytime_start'] = $start = strtotime(date(''.$year.'-'.$month.'-01 00:00:00'));
			 $add['settlement_paytime_end'] = $end = strtotime(date(''.$year.'-'.$month.'-t 23:59:59',$val['add_time']));
			$settlement = Db::name('supplier_settlement')->where("settlement_paytime_start = $start and settlement_paytime_end <= $end and supplier_id = $val[supplier_id]")->find();
			if($settlement){
				$update = [
					'order_money_all' => ['exp','order_money_all+'.$val['total_amount'].''],
					'settlement_all' => ['exp','settlement_all+'.$val['order_amount'].''],
				];
				 Db::name('supplier_settlement')->where('settlement_id',$settlement['settlement_id'])->update($update);
			}else{
				$add['supplier_id'] = $val['supplier_id'];
				$add['order_money_all']  = $val['total_amount'];
				$add['settlement_all']  =$val['order_amount'];
				$settlement['settlement_id'] = Db::name('supplier_settlement')->insertGetId($add);
				
			}
			Db::name('order')->where('order_id',$val['order_id'])->update(['is_distribut'=>$settlement['settlement_id']]);
		 }
		 
		 exit(json_encode(1));
	 }
	 
	 
	 /**
	 * 商家兑换记录列表
	 */
	 public function exchange_goods(){
		 
		
		 
        $count = Db::name('exchange_goods')->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
		$list = Db::name('exchange_goods')->order('id')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('page', $show);// 赋值分页输出
        $this->assign('pager', $Page);
		$this->assign('list',$list);
		return $this->fetch();	 
	 }

	 
	 /**
	 * 商家兑换商品详情
	 */
	 public function exchange_info(){
		 
		$id = I('id/d',0);
		$data = I('post.');
		 
        $info = Db::name('exchange_goods')->where('id',$id)->find();
		
		if(IS_POST){
			if(empty($data['goods_id']) || empty($data['goods_num']))
				$this->ajaxReturn(['status'=>0,'msg'=>'请输入完整信息']);
			if(empty($id)){
				$data['add_time'] = time();
				Db::name('exchange_goods')->insert($data);
			}else{
				Db::name('exchange_goods')->where('id',$id)->update($data);
			}
			
			
			 $this->ajaxReturn(['status'=>1,'msg'=>'OK']);
		}

		$this->assign('info',$infp);
		return $this->fetch();	 
	 }
	 
	 	 
	 // 删除活动商品
    public function exchange_del()
    {
        $id = I('del_id');
		$goods_name = I('goods_name');
        if ($id) {
            Db::name('exchange_goods')->where("id=$id")->delete();
			adminLog('删除入驻商兑换商品 '.$goods_name.'');
            exit(json_encode(1));
        } else {
            exit(json_encode(0));
        }
    }
	 
	 /**
	 * 商家兑换记录列表
	 */
	 public function exchange_log(){
		// dump(input('post.'));exit;
		$count = Db::name('exchange_log')->where('goods_id','neq','0')->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
		$list = Db::name('exchange_log')->where('goods_id','neq','0')->order('shipping_code')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('page', $show);// 赋值分页输出
        $this->assign('pager', $Page);
		$this->assign('list',$list);
		return $this->fetch();
	 }
	 
	 
	 /**
	 * 商家兑换详情
	 */
	 public function exchange_detail(){
		 
		$id = I('id/d',0);
		$info = Db::name('exchange_log')->where('id',$id)->find();
		if(empty($info))
			exit($this->error('信息错误'));
		
		if(IS_POST){
			$shipping_code = input('shipping_code');
			Db::name('exchange_log')->where('id',$id)->update(['shipping_code'=>$shipping_code]);
			
			exit(json_encode(['status'=>1,'msg'=>'操作成功']));
			
		}
		 
		
	
		$this->assign('info',$info);
		return $this->fetch();
	 }
	 
	 
	 /**
	 * 导出入驻商家数据
	 */
	 public function export_supplier($data)
    {
    	//搜索条件
		

		$keyword = $data['keyword'];
        $where = $keyword ? " (supplier_name like '%$keyword%' or company_name like '%$keyword%') and status=1 and is_designer = 0" : "status=1 and is_designer = 0";
        $orderList= Db::name('supplier')->where($where)->order('supplier_id')->select();
	
  
    	$strTable ='<table width="500" border="1">';
    	$strTable .= '<tr>';
    	$strTable .= '<td style="text-align:center;font-size:12px;width:100px;">商铺编码</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="100">商铺名称</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">公司名称</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">公司地址</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">公司简介</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">公司规模</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">电子邮箱</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">入驻时间</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">联系人电话</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">公司座机</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">营业执照号</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">开户名称</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">支行名称</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">运营者姓名</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">保证金</td>';
		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">审核状态</td>';
    	$strTable .= '</tr>';
	    if(is_array($orderList)){
	    	$region	= Db::name('region')->column('id,name'); 
	    	foreach($orderList as $k=>$val){
	    		$strTable .= '<tr>';
	    		$strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['supplier_id'].'</td>';
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['supplier_name'].' </td>';	    		
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['company_name'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'."{$region[$val['province']]},{$region[$val['city']]},{$region[$val['area']]},{$val['address']}".' </td>';
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['introduction'].'</td>';
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['guimo'].'</td>';
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['email'].'</td>';
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.date('Y-m-d H-i',$val['add_time']).'</td>';
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['contacts_phone'].' </td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['phone_number'].' </td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['business_licence_number'].' </td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['bank_name'].' </td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['bank_branch'].' </td>';
				$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['operating_name'].' </td>';
				$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['supplier_money'].' </td>';
				$strTable .= '<td style="text-align:left;font-size:12px;">审核通过</td>';
	    		$strTable .= '</tr>';
                
	    	}
	    }
    	$strTable .='</table>';
    	unset($orderList);
    	downloadExcel($strTable,'入驻商家信息');
    	exit();
    }
	

	 
}