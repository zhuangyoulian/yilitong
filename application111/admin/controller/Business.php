<?php
namespace ylt\admin\controller;
use think\Page;
use think\AjaxPage;
use think\Verify;
use think\Db;
use think\Url;
use think\Request;
use ylt\admin\logic\BusinessLogic;

class Business extends Base{
	
	
	/**
	 * 业务等级列表
	 */
	public function rankList(){
		
		$Ad =  Db::name('busines_rank');
        $p = $this->request->param('p');
    	$res = $Ad->where('1=1')->order('rank_id')->page($p.',10')->select();
    	if($res){
    		foreach ($res as $val){
    			$list[] = $val;
    		}
    	}
    	$this->assign('list',$list);
    	$count = $Ad->where('1=1')->count();
    	$Page = new Page($count,10);
    	$show = $Page->show();
    	$this->assign('page',$show);
    	return $this->fetch();
    }
	
	
	/**
	 * 业务等级编辑
	 */
	public function rank(){
    	$act = I('get.act','add');
    	$this->assign('act',$act);
    	$rank_id = I('get.rank_id');
    	$level_info = array();
		
		$Logic = new BusinessLogic();
		 
    	if($rank_id){
			
    		$rank_info = $Logic->rank_info($rank_id);
    		$this->assign('info',$rank_info);
    	}
		
		$rank_list = $Logic->rankList();
		$this->assign('rank_list',$rank_list);
    	return $this->fetch();
    }
	
	
    /**
	 * 业务等级编辑
	 */
   public function levelHandle(){
    	$data = I('post.');
		
		$Logic = new BusinessLogic();
		
    	if($data['act'] == 'add'){
    		$r = $Logic->levelHandle('0',$data);
    	}
    	if($data['act'] == 'edit'){
    		$r = $Logic->levelHandle('1',$data);
    	}
    	 
    	 
    	if($r){
    		$this->success("操作成功",Url::build('Admin/Business/rankList'));
    	}else{
    		$this->error("操作失败",Url::build('Admin/User/rankList'));
    	}
    }
	
	/**
	 * 城市代理人
	 */
	public function cityAgent(){
        return $this->fetch();
    }
	
	/**
	 * 城市代理人列表
	 */
	public function ajaxcityAgent(){
		 
		 // 搜索条件
        $condition = array();
        I('mobile') ? $condition['mobile'] = I('mobile') : false;
		
		$where = array();
        I('mobile') ? $where['mobile'] = array('like','%'.I('mobile').'%') : false;
		 
		$where['agent_rank'] = '1';
        $sort_order = I('order_by','FUid').' '.I('sort','desc');
  
        $model = Db::name('busines_agent');
        $count = $model->where($where)->count();
        $Page  = new AjaxPage($count,15);
        //  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            $Page->parameter[$key]   =   urlencode($val);
        }
		
		$Logic = new BusinessLogic();
		
		$userList = $Logic->businesList($model,$where,$sort_order,$Page->firstRow.','.$Page->listRows);
       
                    
        $show = $Page->show();
        $this->assign('userList',$userList);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();

    }
	
	
	/** 
	 * 市代详细信息
	 */
	public function agent_detail(){
		 
		$FUid = input('get.FUid');
		 
		 $Logic = new BusinessLogic();
		 
		 if($FUid)
			$agentInfo = $Logic->agentInfo($FUid);
		 
		 if(IS_GET){
		
			//  获取省份
			$province = $Logic->getRegion('0');
			//  获取城市
			if($agentInfo['province'])
			    $city = $Logic->getRegion($agentInfo['province']);
			//获取地区
			if($agentInfo['city'])
			    $area =  $Logic->getRegion($agentInfo['city']);

			$this->assign('province',$province);
			$this->assign('city',$city);
			$this->assign('area',$area);
			
			$this->assign('su',$agentInfo);
			
		}
		
		if(IS_POST){
			$data = input('post.');

			if(input('post.FUid')){
				$userInfo =$Logic->getUserInfo($data['mobile']);
			
				//修改入住信息
				$Logic->updateAgent('1','1',$data);
			}else{
				if(!check_mobile($data['mobile']))
					 exit($this->error('输入正确信息'));
				//获取用户信息
				$userInfo =$Logic->getUserInfo($data['mobile']);
				 if(!$userInfo)
					 exit($this->error('用户未注册为会员'));
				 if($userInfo['business_level'] > 0)
					 exit($this->error('用户已注册成为业务员'));
				 //插入入驻信息
		
				$data['FUid'] = $userInfo['user_id'];
				$data['recommend_code'] = $userInfo['recommend_code'];
				$data['agent_rank']	= '1';
				$data['reg_time'] = time();
				$data['parent_id'] = $userInfo['parent_id'];

				$Logic->updateAgent('0','1',$data);
				sendCode($data['mobile'],'尊敬的城市代理，您好！您提交的申请成为城市代理审核通过，通过一礼通APP可以管理团队.');
			}
				
				
			exit($this->success('操作成功'));
		}

		return $this->fetch();
	 }
	
	/**
	 * 区/县代理人
	 */
	public function districtAgent(){
        return $this->fetch();
    }
	
	/**
	 * 区/县代理人列表
	 */
	public function ajaxdistrictAgent(){
		  // 搜索条件
        $condition = array();
        I('mobile') ? $condition['mobile'] = I('mobile') : false;
		
		$where = array();
        I('mobile') ? $where['mobile'] = array('like','%'.I('mobile').'%') : false;
		 
		$where['agent_rank'] = '2';
        $sort_order = I('order_by','FUid').' '.I('sort','desc');
  
        $model = Db::name('busines_agent');
        $count = $model->where($where)->count();
        $Page  = new AjaxPage($count,15);
        //  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            $Page->parameter[$key]   =   urlencode($val);
        }
        
        $Logic = new BusinessLogic();
		
		$userList = $Logic->businesList($model,$where,$sort_order,$Page->firstRow.','.$Page->listRows);
                
                              
        $show = $Page->show();
        $this->assign('userList',$userList);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
		
    }
	
	
	/**
	 * 区/县代理人详情
	 */
	public function district_detail(){
        $FUid = input('get.FUid');
		 
		 $Logic = new BusinessLogic();
		 
		 if($FUid)
			$agentInfo = $Logic->agentInfo($FUid);
		 
		 if(IS_GET){
			
			//  获取省份
			$province = $Logic->getRegion('0');
			//  获取城市
			if($agentInfo['province'])
			    $city = $Logic->getRegion($agentInfo['province']);
			//获取地区
			if($agentInfo['city'])
			    $area =  $Logic->getRegion($agentInfo['city']);

			$this->assign('province',$province);
			$this->assign('city',$city);
			$this->assign('area',$area);
			
			$this->assign('su',$agentInfo);
			
		}
		
		if(IS_POST){
			$data = input('post.');

			if(input('post.FUid')){
				//修改入住信息
				$Logic->updateAgent('1','2',$data);
			}else{
				 if(!check_mobile($data['mobile']))
					 exit($this->error('输入正确信息'));
				//获取用户信息
				$userInfo =$Logic->getUserInfo($data['mobile']);
				 if(!$userInfo)
					 exit($this->error('用户未注册为会员'));
				 if($userInfo['business_level'] > 0)
					 exit($this->error('用户已注册成为业务员'));
				 
				//插入入驻信息
				$data['FUid'] = $userInfo['user_id'];
				$data['recommend_code'] = $userInfo['recommend_code'];
				$data['agent_rank']	= '2';
				$data['reg_time'] = time();
				$data['parent_id'] = $userInfo['parent_id'];
				$Logic->updateAgent('0','2',$data);
	
			
			}
				
			//sendCode($data['mobile'],'尊敬的区域代理，您好！您提交的申请成为城市代理人审核通过，通过一礼通APP可以管理团队.');

			exit($this->success('操作成功'));
		}

		return $this->fetch();
    }
	
	
	
	/**
	 * 业务顾问
	 */
	public function bsConsultant(){
        return $this->fetch();
    }
	
	/**
	 * 业务顾问
	 */
	public function ajaxbsConsultant(){
         // 搜索条件
        $condition = array();
        I('mobile') ? $condition['mobile'] = I('mobile') : false;
		
		$where = array();
        I('mobile') ? $where['mobile'] = array('like','%'.I('mobile').'%') : false;
		 
		$where['agent_rank'] = '4';
        $sort_order = I('order_by','FUid').' '.I('sort','desc');
  
        $model = Db::name('busines_manager');
        $count = $model->where($where)->count();
        $Page  = new AjaxPage($count,15);
        //  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            $Page->parameter[$key]   =   urlencode($val);
        }
        
        $Logic = new BusinessLogic();
		
		$userList = $Logic->businesList($model,$where,$sort_order,$Page->firstRow.','.$Page->listRows);
                
                              
        $show = $Page->show();
        $this->assign('userList',$userList);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
    }
	
	/**
	 * 微店主
	 */
	public function shopkeeper(){
        return $this->fetch();
    }
	
	/**
	 * 微店主
	 */
	public function ajaxshopkeeper(){
         // 搜索条件
        $condition = array();
        I('mobile') ? $condition['mobile'] = I('mobile') : false;
		
		$where = array();
        I('mobile') ? $where['mobile'] = array('like','%'.I('mobile').'%') : false;
		 
		$where['agent_rank'] = '5';
        $sort_order = I('order_by','FUid').' '.I('sort','desc');
  
        $model = Db::name('busines_manager');
        $count = $model->where($where)->count();
        $Page  = new AjaxPage($count,15);
        //  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            $Page->parameter[$key]   =   urlencode($val);
        }
        
        $Logic = new BusinessLogic();
		
		$userList = $Logic->businesList($model,$where,$sort_order,$Page->firstRow.','.$Page->listRows);
                
                              
        $show = $Page->show();
        $this->assign('userList',$userList);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
    }
	
	
	/**
	 * 业务顾问、微店主详情
	 */
	public function manager_detail(){
        $FUid = input('get.FUid');
		 
		 $Logic = new BusinessLogic();
		 
		 if($FUid)
			$agentInfo = $Logic->managerInfo($FUid);
		
		//$data['transaction_id'] = '';
		//$Logic->managerDividedInfo('us15216844973862',$data);
		//修改信息
		if(IS_POST){
			$data = input('post.');
	
			if(input('post.FUid')){
				//修改入住信息
				$Logic->updateAgent('1','4',$data);
			}else{
				//获取用户信息
				$userInfo =$Logic->getUserInfo($data['mobile']);
				 if(!$userInfo)
					 exit($this->error('用户未注册为会员'));
				 if($userInfo['business_level'] > 0)
					 exit($this->error('用户已注册成为业务员'));
				 
				//插入入驻信息
				$data['Fuid'] = $userInfo['user_id'];
				$data['recommend_code'] = $userInfo['recommend_code'];
				$data['agent_rank']	= '2';
				$data['add_time'] = time();
				$Logic->updateAgent('0','4',$data);

			}
				
			//sendCode($data['mobile'],'尊敬的区域代理，您好！您提交的申请成为城市代理人审核通过，通过一礼通APP可以管理团队.');

			exit($this->success('操作成功'));
		}
		
		 
		 if(IS_GET){
			
			//  获取省份
			$province = $Logic->getRegion('0');
			//  获取城市
			if($agentInfo['province'])
			    $city = $Logic->getRegion($agentInfo['province']);
			//获取地区
			if($agentInfo['city'])
			    $area =  $Logic->getRegion($agentInfo['city']);

			$this->assign('province',$province);
			$this->assign('city',$city);
			$this->assign('area',$area);
			
			$this->assign('su',$agentInfo);
			
		}
		

		return $this->fetch();
    }
	
		
	/**
	 * 市代，区代分润
	 */
	public function dividedInfo(){
		 
		 $FUid = input('FUid');
		 
		 $Logic = new BusinessLogic();
		 
		 $agentInfo = $Logic->agentInfo($FUid);
		if(!$agentInfo)
			$this->error("操作失败",Url::build('Admin/Business/cityAgent'));
		
		if($agentInfo['divided_status'] == '1')
			$this->error("操作失败",Url::build('Admin/Business/cityAgent'));
		//分润
		$row = $Logic->agentDividedInfo($agentInfo);
				
		if($row)
			$this->success("操作成功",Url::build('Admin/Business/cityAgent'));
		else
			$this->error("操作失败",Url::build('Admin/Business/cityAgent'));
    }
	
	
	/**
	 * 订单分成记录列表
	 */
	public function orderDividedList(){
		return $this->fetch();
	 }
	 
	 /**
	 * 订单分成记录
	 */
	public function ajaxorderDividedList(){
		 // 搜索条件
        $condition = array();
        input('mobile') ? $condition['mobile'] = input('mobile') : false;
		
		$where = array();
        input('mobile') ? $where['mobile'] = array('like','%'.input('mobile').'%') : false;
		 
		
        $sort_order = input('order_by','FUid').' '.input('sort','desc');
  
        $model = Db::name('order_divided_log');
        $count = $model->where($where)->count();
        $Page  = new AjaxPage($count,15);
        //  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            $Page->parameter[$key]   =   urlencode($val);
        }
        
        $dividedList = $model->where($where)->order($sort_order)->limit($Page->firstRow.','.$Page->listRows)->select();
                
                              
        $show = $Page->show();
        $this->assign('dividedList',$dividedList);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
	 }
	 
	 
	 /**
	 * 订单分成详情
	 */
	public function dividedDetails(){
		 
		$logId = input('log_id');
		 
		$Logic = new BusinessLogic();
		 
		$info = $Logic->dividedDetails($logId);
		 
		$this->assign('info',$info);
        return $this->fetch();

	 }
	 
	 /**
	 * 订单分成数据处理
	 */
	public function orderDivided(){
		 
		$logId = input('log_id');
		 
		$Logic = new BusinessLogic();
		 
		$row = $Logic->orderDivided($logId);
		 
		if($row)
			$this->success("操作成功",Url::build('Admin/Business/orderDividedList'));
		else
			$this->error("操作失败",Url::build('Admin/Business/orderDividedList'));

	}
	 
	  /**
	  * 礼豆列表
	  */
	public function beanGiftList(){
		  return $this->fetch();
	}
	  
	  
	  /**
	   * 礼豆列表
	   */
	public function ajaxBeanGiftList(){
		    // 搜索条件
        $condition = array();
        input('mobile') ? $condition['mobile'] = input('mobile') : false;
		
		$where = array();
        input('mobile') ? $where['mobile'] = array('like','%'.input('mobile').'%') : false;
		 
		
        $sort_order = input('order_by','log_id').' '.input('sort','desc');
  
        $model = Db::name('bean_gift_log');
        $count = $model->where($where)->count();
        $Page  = new AjaxPage($count,15);
        //  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            $Page->parameter[$key]   =   urlencode($val);
        }
        
        $dividedList = $model->where($where)->order($sort_order)->limit($Page->firstRow.','.$Page->listRows)->select();
                
                              
        $show = $Page->show();
        $this->assign('cashGift',$dividedList);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
	}
	 
	 /**
	  * 礼金列表
	  */
	public function cashGiftList(){
		  return $this->fetch();
	}
	  
	  
	  /**
	   * 礼金列表
	   */
	public function ajaxCashGiftList(){
		    // 搜索条件
        $condition = array();
        input('mobile') ? $condition['mobile'] = input('mobile') : false;
		
		$where = array();
        input('mobile') ? $where['mobile'] = array('like','%'.input('mobile').'%') : false;
		 
		
        $sort_order = input('order_by','log_id').' '.input('sort','desc');
  
        $model = Db::name('cash_gift_log');
        $count = $model->where($where)->count();
        $Page  = new AjaxPage($count,15);
        //  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            $Page->parameter[$key]   =   urlencode($val);
        }
        
        $dividedList = $model->where($where)->order($sort_order)->limit($Page->firstRow.','.$Page->listRows)->select();
                
                              
        $show = $Page->show();
        $this->assign('cashGift',$dividedList);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
	}
	   
    /**
    * 销售奖列表
    */
	public function saleGiftList(){
	  return $this->fetch();
    }
  
  
   /**
   * 销售奖列表
   */
	public function ajaxSaleGiftList(){
	   	    // 搜索条件
        $condition = array();
        input('mobile') ? $condition['mobile'] = input('mobile') : false;
		
		$where = array();
        input('mobile') ? $where['mobile'] = array('like','%'.input('mobile').'%') : false;
		 
		
        $sort_order = input('order_by','log_id').' '.input('sort','desc');
  
        $model = Db::name('sale_gift_log');
        $count = $model->where($where)->count();
        $Page  = new AjaxPage($count,15);
        //  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            $Page->parameter[$key]   =   urlencode($val);
        }
        
        $dividedList = $model->where($where)->order($sort_order)->limit($Page->firstRow.','.$Page->listRows)->select();
                
                              
        $show = $Page->show();
        $this->assign('saleGift',$dividedList);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
   }
   
   /**
  * 开发奖列表
  */
	public function openGiftList(){
	  return $this->fetch();
   }
  
   /**
   * 开发奖列表
   */
	public function ajaxOpenGiftList(){
	      	    // 搜索条件
        $condition = array();
        input('mobile') ? $condition['mobile'] = input('mobile') : false;
		
		$where = array();
        input('mobile') ? $where['mobile'] = array('like','%'.input('mobile').'%') : false;
		 
		
        $sort_order = input('order_by','log_id').' '.input('sort','desc');
  
        $model = Db::name('open_gift_log');
        $count = $model->where($where)->count();
        $Page  = new AjaxPage($count,15);
        //  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            $Page->parameter[$key]   =   urlencode($val);
        }
        
        $dividedList = $model->where($where)->order($sort_order)->limit($Page->firstRow.','.$Page->listRows)->select();
                
                              
        $show = $Page->show();
        $this->assign('openGift',$dividedList);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
    }
	
	
	/**
	 * 提现申请列表
	 */
	public function bsWithdrawals(){
		 
		 
		 return $this->fetch();
	}
	 
	 /**
	  * ajax 提现申请列表
	  */
	public function ajaxbsWithdrawals(){
		
		    // 搜索条件
        $condition = array();
        input('mobile') ? $condition['mobile'] = input('mobile') : false;
		
		$where = array();
        input('mobile') ? $where['mobile'] = array('like','%'.input('mobile').'%') : false;
		 
		$type = array('','礼豆','礼金','销售奖','开发奖');
        $sort_order = input('order_by','trading_id').' '.input('sort','desc');
  
        $model = Db::name('busines_withdrawals');
        $count = $model->where($where)->count();
        $Page  = new AjaxPage($count,15);
        //  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            $Page->parameter[$key]   =   urlencode($val);
        }
        
        $withdrawals = $model->where($where)->order($sort_order)->limit($Page->firstRow.','.$Page->listRows)->select();
                
                              
        $show = $Page->show();
        $this->assign('withdrawals',$withdrawals);
		$this->assign('type',$type);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
		return $this->fetch();
	}
	
	/**
	 *提现审核
	 */
	public function examineWithdrawals(){
		 
		$trading_id = trim(input('trading_id'));
		
		$Logic = new BusinessLogic();
		 
		$row = $Logic->examineWdr('1',$trading_id);
		 
		if($row)
			$this->success("操作成功",Url::build('Admin/Business/bsWithdrawals'));
		else
			$this->error("操作失败",Url::build('Admin/Business/bsWithdrawals'));
		
	}
	 
	 /**
	  * 提现详情
	  */
	public function bsWdrDetail(){
		 
		 $trading_id = trim(input('id'));
		 
		 $Logic = new BusinessLogic();
		 
		 $info = $Logic->bsWdrDetail($trading_id);
		 
		 $this->assign('info',$info);
		 return $this->fetch();
	}
	 
	  /**
	  * 提现付款
	  */
	public function bsWdrPayment(){
		 
		$trading_id = trim(input('trading_id'));
		$data = input('post.');
		
		$Logic = new BusinessLogic();
		 
		$info = $Logic->examineWdr('2',$trading_id,$data);
		 
		if($info['status'] == '1')
			$this->success("操作成功",Url::build('Admin/Business/bsWithdrawals'));
		else
			$this->error("操作失败".$info['msg'],Url::build('Admin/Business/bsWithdrawals'));
		 
		 return $this->fetch();
	}
	
	
	/**
     * 获取城市
     */
    public function getBusinessCity(){
        $parent_id = I('get.parent_id/d');
        $selected = I('get.selected',0);
		$level = I('get.level',0);
		
		 $Logic = new BusinessLogic();
		
		if($level == 1){
			$businessCity = Db::name('busines_agent')->where(['agent_rank'=>1])->column('city');
		}

        $data = $Logic->getRegion($parent_id);
        $html = '';
        if($data){
			
            foreach($data as $h){
				
				if($businessCity){
					if(in_array($h['id'],$businessCity))
					continue;
				}

            	if($h['id'] == $selected){
            		$html .= "<option value='{$h['id']}' selected>{$h['name']}</option>";
            	}
                $html .= "<option value='{$h['id']}'>{$h['name']}</option>";
            }
        }
        echo $html;
    }
	
	
	/**
     * 获取地区
     */
    public function getBusinessArea(){
		
        $parent_id = I('get.parent_id/d');
        $selected = I('get.selected',0);
		$level = I('get.level',0);
		$Logic = new BusinessLogic();
		
		
		if($level == 2){
			$businessDistrict = Db::name('busines_agent')->where(['agent_rank'=>2])->column('district');
		}
        $data = $Logic->getRegion($parent_id);
        $html = '';
        if($data){
            foreach($data as $h){
				if($businessDistrict){
					if(in_array($h['id'],$businessDistrict))
					continue;
				}
            	if($h['id'] == $selected){
            		$html .= "<option value='{$h['id']}' selected>{$h['name']}</option>";
            	}
                $html .= "<option value='{$h['id']}'>{$h['name']}</option>";
            }
        }
        echo $html;
    }
	

	
}
 