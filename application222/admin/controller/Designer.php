<?php
namespace ylt\admin\controller;
use ylt\admin\logic\DesignerLogic;
use think\Page;
use think\Db;
use think\Session;
use think\Request;
use think\AjaxPage;
use think\Url;
class Designer extends Base{
	
	/**
	 * 设计师列表
	 */
	
    public function DesignerList(){
		if(I('export')){ // 导出数据
			$this->export_designer(I('post.'));
		}

        $p = I('p/d',1);
		$keyword = input('keyword');
        $where = $keyword ? " (supplier_name like '%$keyword%' or company_name like '%$keyword%') and (status=1 or status = -1) and is_designer = 1 " : "(status=1 or status = -1) and is_designer = 1";

        $res= Db::name('supplier')->where($where)->order('supplier_id')->page($p.',20')->select();
		
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
    public function DesignerExamine(){
		$p = I('p/d',1);
		$keyword = input('keyword');
        $where = $keyword ? " (supplier_name like '%$keyword%' or company_name like '%$keyword%') and (status=0 or status = 2) and is_designer = 1 and is_complete = 1 " : "(status=0 or status = 2) and is_designer = 1 and is_complete = 1 ";

        $res= Db::name('supplier')->where($where)->order('supplier_id')->page($p.',20')->select();
		$count = DB::name('supplier')->where($where)->count();
    	$Page = new Page($count,20);
    	$show = $Page->show();
		$this->assign('pager',$Page);
		$this->assign('page',$show);
        $this->assign( 'arr',$res );
        return $this->fetch();
    }
	
	
	/**
     * 入驻商详细信息查看
     */
    public function Detail(){
        $supplier_id = I('get.id');
        $supplier = Db::name('supplier')->where(array('supplier_id'=>$supplier_id,'is_designer'=>1))->find();
        if(!$supplier)
            exit($this->success('设计师不存在'));
		if(IS_POST){
			$status = I('status');

			$row = Db::name('supplier')->where(array('supplier_id'=>$supplier_id,'is_designer'=>1))->update($_POST);

			if($supplier['status'] != $status && $status == 1){
				sendCode($supplier['contacts_phone'],'尊敬的设计师，您好！您提交的设计师入驻信息已通过审核.');
			}elseif($status == '2'){
				sendCode($supplier['contacts_phone'],'尊敬的设计师，您好！您提交的设计师入驻信息审核未通过，请按要求正确填写.');
			}elseif($status == '-1'){
				Db::name('supplier')->where('supplier_id',$supplier_id)->update(array('status'=>'-1'));
				Db::name('goods')->where('supplier_id',$supplier_id)->update(array('is_on_sale'=>'0','examine'=>'0','is_delete'=>'1'));
				sendCode($supplier['contacts_phone'],'尊敬的设计师，您好，您所开通的一礼通设计师：'.$supplier['supplier_name'].' 已经关闭');
				adminLog('关闭店铺'.$supplier['supplier_name'].'');
				exit($this->success('关闭店铺'));
			}
				Db::name('users')->where('user_id',$supplier['user_id'])->update(array('is_designer'=>$status));
				adminLog('设计师审核 '.$supplier['supplier_name'].'');
			exit($this->success('审核成功'));
		}
	
		$region_list = get_region_list();
        $this->assign('region_list',$region_list);

        $this->assign('su',$supplier);
        return $this->fetch();
    }
	
	
	/**
	 * 入驻商操作日志
	 */
	public function DesignerLog(){
		
	
    	$p = I('p/d',1);
		$where = " 1=1 ";
		$keywords = input('keywords');
		input('keywords') ? $where .= " and s.supplier_name like '%$keywords%'" : false;
		
		$field = 'l.*,s.supplier_name,a.user_name';
		
    	$logs = DB::name('designer_log')->alias('l')->join('supplier_user a','a.admin_id =l.admin_id')->join('supplier s','l.supplier_id = s.supplier_id')->field($field)->where($where)->order('log_time DESC')->page($p.',20')->select();

    	$this->assign('list',$logs);
    	$count = DB::name('designer_log')->alias('l')->join('supplier s','l.supplier_id = s.supplier_id')->where($where)->count();
    	$Page = new Page($count,20);
    	$show = $Page->show();
		$this->assign('pager',$Page);
		$this->assign('page',$show);
    	return $this->fetch();
    }
	


	 /**
      *  入驻商家结算列表
      */
	public function supplier_settlement_list(){

		$p = I('p/d');
		$keyword = input('keyword');
		$where = $keyword ?  "j.is_pay_ok = 0  and (s.supplier_name like '%$keyword%' or s.company_name like '%$keyword%')" : "j.is_pay_ok = 0";

	    $settlement = Db::name('supplier_settlement')->alias('j')->join('supplier s','j.supplier_id = s.supplier_id')->where($where)->order('j.settlement_id','desc')->page($p.',15')->select();
		 
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
	 * 导出入驻商家数据
	 */
	 public function export_designer($data)
    {
    	//搜索条件
		
		
		$keyword = $data['keyword'];
        $where = $keyword ? " (supplier_name like '%$keyword%' or company_name like '%$keyword%') and (status=1 or status = -1)  and is_designer = 1" : "status=1 and is_designer = 1";
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
    	downloadExcel($strTable,'设计师信息');
    	exit();
    }

    /**
     * 作品分类列表
     */
    public function WorksCategoryList(){
    	$p = I('p/d',1);
    	$worksList = DB::name('works_category')->where('1=1')->order('id ASC')->page($p.',10')->select();
    	
    	$count = DB::name('works_category')->where('1=1')->count();
    	
    	$Page = new Page($count,10);
    	$show = $Page->show();
    
		$this->assign('pager',$Page);
		$this->assign('page',$show);
		$this->assign('worksList',$worksList);
    	return $this->fetch();

    }

    /**
     * 添加修改作品分类
     * 
     */
    public function WorksCategoryAdd(){
        $id =  I('id/d',0);

         $model = Db::name("works_category")->where('id',$id)->find();

         $works_cat = Db::name('works_category')->where('1=1')->select();

         if (IS_POST) {

         // 数据验证
            $validate = \think\Loader::validate('WorksCategory');
            if(!$validate->batch()->check(I('post.')))
            {
                $error = $validate->getError();
                $error_msg = array_values($error);
                $return_arr = array(
                    'status' => -1,
                    'msg' => $error_msg[0],
                    'data' => $error,
                );

                exit($this->error($error_msg[0]));
            } 
        	$info = [
	        	'name' => $_POST['name'],
	        	'mobile_name' => $_POST['name'],
	        	'parent_id' => $_POST['parent_id'],
	        	'sort_order' => $_POST['sort_order'],
	        	'is_hot' => $_POST['is_hot'],
	        	'is_show' => $_POST['is_show']
        	];

            if ($id){
				 DB::name('works_category')->where('id',$id)->update($info);
			} 
            else{
				if(DB::name('works_category')->where('name',$info['name'])->find())
					$this->success("该作品类型已经存在，请勿重复添加!", Url::build('Admin/Designer/WorksCategoryList'));
				else
				 DB::name('works_category')->insert($info);
			}
               
            $this->success("操作成功!!!", Url::build('Admin/Designer/WorksCategoryList'));
            exit;
        }

        $this->assign('works_cat',$works_cat);
        $this->assign('info', $model);
        return $this->fetch('WorksCategoryAdd'); 
    			
    }

    /**
     * 删除作品分类
     */
    public function DelWorksCategory(){
    	$id = $this->request->param('id');
    	//$id = I('post.id');
    	//var_dump($id);die;
        
        DB::name('works_category')->where('id',$id)->delete();
        $this->success("操作成功!!!",Url::build('Admin/Designer/WorksCategoryList'));
    }


    /**
     * 添加修改设计师分类
     * categoryAdd
     */
    public function CategoryAdd(){
    	//id/d判断进来的值是否为数值,若不是则为0
         $id =  I('id/d',0);
         //var_dump($id);die;
         $model = Db::name("designer_category")->where('id',$id)->find();
        if (IS_POST) {

        	// 数据验证 和WorksCategoryAdd使用同一个WorksCategory类
            $validate = \think\Loader::validate('WorksCategory');
            if(!$validate->batch()->check(I('post.')))
            {
            	//var_dump(123);die;
                $error = $validate->getError();
                $error_msg = array_values($error);
                $return_arr = array(
                    'status' => -1,
                    'msg' => $error_msg[0],
                    'data' => $error,
                );

                exit($this->error($error_msg[0]));
            }
        	
        	$info = [
	        	'name' => $_POST['name'],
	        	'mobile_name' => $_POST['name'],
	        	'parent_id' => $_POST['parent_id'],
	        	'sort_order' => $_POST['sort_order'],
	        	'is_hot' => $_POST['is_hot'],
	        	'is_show' => $_POST['is_show']
        	];
            if ($id){
				 DB::name('designer_category')->where('id',$id)->update($info);
			} 
            else{
				if(DB::name('designer_category')->where('name',$info['name'])->find())
					$this->success("该设计师类型已经存在，请勿重复添加!", Url::build('Admin/Designer/CategoryList'));
				else
				 DB::name('designer_category')->insert($info);
			}
               

            $this->success("操作成功!!!", Url::build('Admin/Designer/CategoryList'));
            exit;
        }
      
        $this->assign('info', $model);
        return $this->fetch('CategoryAdd');   

    }

    /**
     *  设计师分类列表
     */
    public function CategoryList(){
        
        $p = I('p/d',1);
    	$categoryList = DB::name('designer_category')->where('1=1')->order('sort_order ASC')->page($p.',10')->select();
    	
    	
    	$count = DB::name('designer_category')->where('1=1')->count();
    	
    	$Page = new Page($count,10);
    	$show = $Page->show();
    
		$this->assign('pager',$Page);
		$this->assign('page',$show);
		$this->assign('categoryList',$categoryList);
    	return $this->fetch();
    }
	
	
	/**
	* 设计师作品列表
	*/
	public function WorksList(){
		$p = I('p/d',1);
    	$list = DB::name('works')->where('1=1')->order('add_time desc')->page($p.',15')->select();
    	
    	
    	$count = DB::name('works')->where('1=1')->count();
    	
    	$Page = new Page($count,15);
    	$show = $Page->show();
    
		$this->assign('pager',$Page);
		$this->assign('page',$show);
		$this->assign('list',$list);
    	return $this->fetch();
	}
	
	/**
     * 删除设计师分类
     */
    public function DelCategory(){
    	$id = $this->request->param('id');
        
        DB::name('designer_category')->where('id',$id)->delete();
        $this->success("操作成功!!!",Url::build('Admin/Designer/categoryList'));
    }
	
	
	/**
	 * 设计师提现列表
	 */
	public function WithdrawalsLog(){
		
		$p = I('p/d',1);
		$keyword = trim(I('keyword'));
		$status = I('status');
		$where = " 1=1 ";
		$keyword != '' ? $where .= " and real_name like '%".$keyword."%' " : false ;
		$status != '' ? $where .= " and status = '$status' " : false ;
		
    	$List = DB::name('withdrawals_log')->where($where)->order('add_time')->page($p.',10')->select();
    	
    	$count = DB::name('withdrawals_log')->where($where)->count();
    	
    	$Page = new Page($count,10);
    	$show = $Page->show();
    
		$this->assign('pager',$Page);
		$this->assign('page',$show);
		$this->assign('List',$List);
    	return $this->fetch();
		
		
	}

	

	 
}