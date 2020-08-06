<?php
namespace ylt\admin\logic;
use think\Model;
use think\Db;

class BusinessLogic extends Model
{    
  
	  /**
     * 获取等级列表
     * @return mixed 找到返回数组
     */
    public function rankList()
    {
        $list =   Db::name('busines_rank')->cache(true)->select();;
        return $list;
    }
	
	  /**
     * 获取等级详情
     * @return mixed 找到返回数组
     */
    public function rank_info($rank_id)
    {
        $row =    Db::name('busines_rank')->cache(true)->where('rank_id',$rank_id)->find();
        return $row;
    }
	
	 /**
     * 编辑业务等级
	 * @param $type int 0 添加 ，1编辑
	 * @param $parent_id int 上级地区
     * @return bool 
     */
    public function levelHandle($type,$data)
    {
		if($type == '0'){
			$row = Db::name('busines_rank')->insert($data);
		}else{
			$row = Db::name('busines_rank')->where('rank_id='.$data['rank_id'])->update($data);
		}
		
       
        return $row;
    }
	
    /**
     * 获取指定用户信息
     * @param $uid int 用户UID
     * @param bool $relation 是否关联查询
     * @return mixed 找到返回数组
     */
    public function agentInfo($FUid)
    {
        $user = Db::name('busines_agent')->where(array('FUid'=>$FUid))->find();
		if(!$user)
			$user = Db::name('busines_manager')->where(array('FUid'=>$FUid))->find();
        return $user;
    }
	
	 /**
     * 获取指定用户信息
     * @param $uid int 用户UID
     * @param bool $relation 是否关联查询
     * @return mixed 找到返回数组
     */
    public function managerInfo($FUid)
    {
        $user = Db::name('busines_manager')->where(array('FUid'=>$FUid))->find();
        return $user;
    }
	
	
	 /**
     * 获取地区信息
     * @param $parent_id int 上级地区
     * @return mixed 找到返回数组
     */
    public function getRegion($parent_id)
    {
        $list = Db::name('region')->cache(true)->where(array('parent_id'=>$parent_id))->select();
        return $list;
    }
	
	 /**
     * 获取提现信息详情
	 * @param $trading_id int 主表键
     * @return mixed 找到返回数组
     */
    public function bsWdrDetail($trading_id)
    {
        $row =    Db::name('busines_withdrawals')->where('trading_id',$trading_id)->find();
        return $row;
    }
	
	
	/**
     * 获取业务列表
     * @param $parent_id int 上级地区
     * @return array() 找到返回数组
     */
    public function businesList($model,$where,$sort_order,$pages)
    {
		
		$data = $model->where($where)->order($sort_order)->limit($pages)->select();
		
		 foreach($data as $key=>$val) {
            $usersId .= $val['FUid'].',';
			}
		$usersId = trim($usersId,',');

        $list = Db::name('users')->field('user_id,bean_gift,cash_gift,sale_gift,open_gift')->cache(true)->where('user_id','in',$usersId)->select();
		
		$tempArr = array();
		foreach($list as $item){
			$tempArr[$item['user_id']]['bean_gift'] = $item['bean_gift'];
			$tempArr[$item['user_id']]['cash_gift'] = $item['cash_gift'];
			$tempArr[$item['user_id']]['sale_gift'] = $item['sale_gift'];
			$tempArr[$item['user_id']]['open_gift'] = $item['open_gift'];
			
		}
			
		foreach($data as $k => $v) {
			if(array_key_exists($v['FUid'], $tempArr)) { 
				$data[$k]['bean_gift'] = $tempArr[$v['FUid']]['bean_gift'];
				$data[$k]['cash_gift'] = $tempArr[$v['FUid']]['cash_gift'];
				$data[$k]['sale_gift'] = $tempArr[$v['FUid']]['sale_gift'];
				$data[$k]['open_gift'] = $tempArr[$v['FUid']]['open_gift'];
			} else {
				$data[$k]['bean_gift'] = '';
				$data[$k]['cash_gift'] = '';
				$data[$k]['sale_gift'] = '';
				$data[$k]['open_gift'] = '';
			}
		}

        return $data;
    }
	
	 /**
     * 修改入驻信息
	 * @param $type 0 插入，1修改
	 * @param $busines_rank 业务等级 1市代，2区代，4.业务经理，5礼品店主
     * @param $where array()
	 * @param $data array()
     * @return mixed 找到返回数组
     */
    public function updateAgent($type='0',$busines_rank = '1',$data)
    {
		if($busines_rank == '1' || $busines_rank =='2'){
			if($type== '0'){
				$row = Db::name('busines_agent')->insert($data);
				
				//修改用户表信息
				Db::name('users')->where('user_id',$data['FUid'])->update(['FManagerId'=>$data['FUid'],'business_level'=>$data['agent_rank']]);
				
			}else{
				$row = Db::name('busines_agent')->where(['FUid'=>$data['FUid']])->update($data);
			}
		}
		
		if($busines_rank == '4' || $busines_rank =='5'){
			if($type== '0'){
				$row = Db::name('busines_manager')->insert($data);
				
				//修改用户表信息
				Db::name('users')->where('user_id',$data['FUid'])->update(['FManagerId'=>$data['FUid'],'business_level'=>$data['agent_rank']]);
				
			}else{
				$row = Db::name('busines_manager')->where(['FUid'=>$data['FUid']])->update($data);
			}
		}
			
        return $row;
    }
	
	
	 /**
     * 会员入驻信息
     * @param $mobile 用户手机号码
     * @return mixed 找到返回数组
     */
    public function getUserInfo($mobile)
    {
		
        $row =  Db::name('users')->field('user_id,parent_id,business_level,recommend_code,FFactoryId')->where('mobile',$mobile)->find();
        return $row;
    }
	
	
	/**
     * 市代，区代分润
	 * @param $data 代理入驻信息
     * @return mixed 找到返回数组
     */
    public function agentDividedInfo($data)
    {
		if($data['divided_status'] == '1')
			return false;
		$userInfo = $this->getUserInfo($data['mobile']);
		//市代数据
		$gaent_info = $this->rank_info('1');
		//城市代理人
		if($data['agent_rank'] == 1){
			// 有推荐人
			if($userInfo['parent_id']){
				
			//获取推荐人信息
			 $parentInfo = Db::name('users')->field('user_id,business_level')->where('recommend_code',$userInfo['parent_id'])->find();
			 // 对方是市代才有推荐奖励
			 if($parentInfo['business_level'] == '1'){
				 
				 $divided = (($data['bond_number'] * $gaent_info['identical_profit']) / 100);
				 // 账户添加资金
				 $desc = '推荐市级代理'.$data['company_name'];
				$row = cashGiftLog($parentInfo['user_id'],$divided,$desc,'',$userInfo['recommend_code'],'');
				 
			 }
	
			}
		}
		//区域代理人
		if($data['agent_rank'] == 2){
			// 无推荐人，则分润给本市市代
			if(!$userInfo['parent_id']){
				$agentInfo = Db::name('busines_agent')->where(['city'=>$data['city'],'agent_rank'=>'1'])->find();
				if($agentInfo){
					//分润数量
					$divided = (($data['bond_number'] * $gaent_info['recommend_profit']) / 100);
					// 账户添加资金
					$desc = '推荐区域代理'.$data['company_name'];
					$row = cashGiftLog($agentInfo['FUid'],$divided,$desc,'',$userInfo['recommend_code']);
				}
				
			}else{
			 //获取推荐人信息
			 //$parentInfo = Db::name('users')->field('user_id,business_level')->where('recommend_code',$userInfo['parent_id'])->find();
			 $parentInfo = Db::name('busines_agent')->field('FUid,agent_rank,city')->where('recommend_code',$userInfo['parent_id'])->find();
			  // 推荐人是市代
			 if($parentInfo['agent_rank'] == '1'){

				 $divided = (($data['bond_number'] * $gaent_info['recommend_profit']) / 100);
				 // 账户添加资金
				 $desc = '推荐区域代理'.$data['company_name'];
				$row = cashGiftLog($parentInfo['FUid'],$divided,$desc,'',$userInfo['recommend_code']); 
			 }
			  // 推荐人区代
			 if($parentInfo['agent_rank'] == '2'){
				 $districtInfo = $this->rank_info('2');
				 //同级直推
				 $divided = (($data['bond_number'] * $districtInfo['identical_profit']) / 100);
				 // 账户添加资金
				 $desc = '推荐区域代理'.$data['company_name'];
				 $row = cashGiftLog($parentInfo['FUid'],$divided,$desc,'',$userInfo['recommend_code']); 
				 
				 //间推
				 $indirectParent = Db::name('busines_agent')->where(['city'=>$parentInfo['city'],'agent_rank'=>'1'])->find();
				 if($indirectParent){
					  $dividedParent = (($data['bond_number'] * $districtInfo['indirect_profit']) / 100);
					  $row = cashGiftLog($indirectParent['FUid'],$dividedParent,$desc,'',$userInfo['recommend_code']); 
				 } 
			 }		
			}
		}
		$remarks = '公司业务提成';
		$dividedParent = (($data['bond_number'] * 10) / 100); //10%
		cashGiftLog('21',$dividedParent,$desc,$remarks,$userInfo['recommend_code']);  //综合管理部 18038195536
		cashGiftLog('163435',$dividedParent,$desc,$remarks,$userInfo['recommend_code']);  //市场部 13902465187
		cashGiftLog('1',$dividedParent,$desc,$remarks,$userInfo['recommend_code']); //运营技术部 13829208060
		
		$row = Db::name('busines_agent')->where(['FUid'=>$data['FUid']])->update(['divided_status'=>'1','status'=>'1']);

        return $row;
    }
	
	
	/**
     * 商家入驻分润
     * @param $supplier_id 入驻商家ID
     * @return mixed 找到返回数组
     */
    public function supplierDividedInfo($supplier_id)
    {
		
        $supplier =  Db::name('supplier')->where('supplier',$supplier_id)->find();
		if($supplier['divided_status'] == '0'){
			
			$desc = '推荐商家入驻'.$supplier['company_name'];
			$pos = strpos($supplier['parent_id'],'su');
			
			//业务员 或者是 商家推荐 直推分润
			if($pos === false){
				$parentInfo = Db::name('users')->where('recommend_code',$supplier['parent_id'])->find();
			
				// 市代、区代、区域经理
				 if($parentInfo['business_level'] == '1'|| $parentInfo['business_level'] == '2' || $parentInfo['business_level'] == '4'){
					 
					 //市代数据
					$gaent_info = $this->rank_info('4');
			
					 $divided = (($supplier['supplier_money'] * $gaent_info['recommend_profit']) / 100);
					 // 账户添加资金
					$row = cashGiftLog($parentInfo['user_id'],$divided,$desc,'',$supplier['recommend_code']);
					 
				 }
				}else{
					//业务员数据
					if($supplier['parent_id']){
						$parentInfo = Db::name('supplier')->where('recommend_code',$supplier['parent_id'])->find();
						// 直推
						if($parentInfo){
							
						//商家数据
						$gaent_info = $this->rank_info('3');
				
						 $divided = (($supplier['supplier_money'] * $gaent_info['identical_profit']) / 100);
						 // 账户添加资金
						 $row = cashGiftLog('0',$divided,$desc,'',$supplier['recommend_code'],$parentInfo['supplier_id']);
		
						}	
					}
					
					//间推分润 先分区域经理，区域经理为空，则分区代，市代
					if($supplier['FManagerId']){
						//商家数据
						$gaent_info = $this->rank_info('3');
						
						$divided = (($supplier['supplier_money'] * $gaent_info['indirect_profit']) / 100);
						// 账户添加资金
						$row = cashGiftLog($supplier['FManagerId'],$divided,$desc,'',$supplier['recommend_code'],'');

					}else{
						//商家数据
						$gaent_info = $this->rank_info('3');
						
						$divided = (($supplier['supplier_money'] * $gaent_info['indirect_profit']) / 100);
						// 先区代，区代不存在，则市代，否则不分
						$indirectParent = Db::name('busines_agent')->where(['district'=>$supplier['district'],'agent_rank'=>'2'])->find();
						if($indirectParent){
							
							$row = cashGiftLog($indirectParent['user_id'],$divided,$desc,'',$supplier['recommend_code']); 
						}else{
							$CityAgent = Db::name('busines_agent')->where(['city'=>$supplier['city'],'agent_rank'=>'1'])->find();
							if($CityAgent){
								$row = cashGiftLog($CityAgent['user_id'],$divided,$desc,'',$supplier['recommend_code']); 
							}
						}
					}
			}
			
			$remarks = '公司业务提成';
			$dividedParent = (($supplier['supplier_money'] * 10) / 100); //10%
			cashGiftLog('21',$dividedParent,$desc,$remarks,$supplier['recommend_code']);  //综合管理部 18038195536
			cashGiftLog('163435',$dividedParent,$desc,$remarks,$supplier['recommend_code']);  //市场部 13902465187
			cashGiftLog('1',$dividedParent,$desc,$remarks,$supplier['recommend_code']); //运营技术部 13829208060

			
		 // 修改商家入驻信息
		 $row = Db::name('supplier')->where('supplier_id',$supplier_id)->update(['divided_status'=>1]);
			
		}
        return $row;
    }
	
	
    /**
     * 区域经理、礼品店主入驻分润
     * @param $order_sn 支付订单编码
	 * @param $data array()
     * @return mixed 找到返回数组
     */
	public function managerDividedInfo($order_sn,$data=array())
    {
		
        $orderInfo =  Db::name('entry_order')->where('order_sn',$order_sn)->find();
		
		$parent_id = 'us'.$orderInfo['user_id'];
		
		//订单分润
		if($orderInfo && $orderInfo['divided_status'] == '0'){
			//获取自身信息
			$userInfo = Db::name('busines_manager')->where('FUid',$orderInfo['user_id'])->find();
			//获取推荐人信息
			$parentInfo = Db::name('users')->where('recommend_code',$userInfo['parent_id'])->find();
			
			//区域经理入驻分润
			if($userInfo['agent_rank'] == '4'){
				
				$desc = '推荐区域经理:'.$userInfo['operating_name'];
				//推荐人是市代
				if($parentInfo['business_level'] == '1'){
					//市代数据
					$gaent_info = $this->rank_info('1');
					$divided = (($orderInfo['total_amount'] * $gaent_info['recommend_profit']) / 100);
					
					if($divided)
					$row = cashGiftLog($parentInfo['user_id'],$divided,$desc,'',$order_sn); 
				}
				//推荐人是区代
				if($parentInfo['business_level'] == '2'){
					//区代数据
					$gaent_info = $this->rank_info('2');
					$divided = (($orderInfo['total_amount'] * $gaent_info['recommend_profit']) / 100);
			
					if($divided)
					   $row = cashGiftLog($parentInfo['user_id'],$divided,$desc,'',$order_sn); 
				}
				//推荐人是区域经理
				if($parentInfo['business_level'] == '4'){
					// 直推分润
					$gaent_info = $this->rank_info('4');
					$divided = (($orderInfo['total_amount'] * $gaent_info['identical_profit']) / 100);
					
					if($divided)
						$row = cashGiftLog($parentInfo['user_id'],$divided,$desc,'',$order_sn); 
					// 间推分润 区代不空，为区代。区代空，为时代。否则不分
					$dividedParent = (($orderInfo['total_amount'] * $gaent_info['indirect_profit']) / 100);
					
					$indirectParent = Db::name('busines_agent')->where(['district'=>$userInfo['district'],'agent_rank'=>'2'])->find();
					if($indirectParent && $dividedParent){
						
						$row = cashGiftLog($indirectParent['user_id'],$dividedParent,$desc,'',$order_sn); 
					}else{
						$CityAgent = Db::name('busines_agent')->where(['city'=>$userInfo['city'],'agent_rank'=>'1'])->find();
						if($CityAgent && $dividedParent){
							$row = cashGiftLog($CityAgent['user_id'],$dividedParent,$desc,'',$order_sn); 
						}
						
					}

				}
				Db::name('users')->where(['parent_id'=>$parent_id,'business_level'=>'0'])->update(['FManagerId'=>$orderInfo['user_id']]); 	//带走用户
				Db::name('supplier')->where('parent_id',$parent_id)->update(['FManagerId'=>$orderInfo['user_id']]);	//带走商家
				Db::name('users')->where('user_id',$orderInfo['user_id'])->update(['FManagerId'=>$orderInfo['user_id'],'business_level'=>$userInfo['agent_rank']]); //修改自身信息

			}
			
			//礼品店主入驻分润
			if($userInfo['agent_rank'] == '5'){
				
				$desc = '推荐礼品店主:'.$userInfo['operating_name'];
				
				//推荐人是市代、区县代、区域经理
				if($parentInfo['business_level'] == '1' || $parentInfo['business_level'] == '2' || $parentInfo['business_level'] == '3' || $parentInfo['business_level'] == '4'){
					//礼品店主数据
					$gaent_info = $this->rank_info('5');
					$divided = (($orderInfo['total_amount'] * $gaent_info['recommend_profit']) / 100);
					
					if($divided)
					   $row = cashGiftLog($parentInfo['user_id'],$divided,$desc,'',$order_sn); 
				}
				//推荐人礼品店主
				if($parentInfo['business_level'] == '5' ){
					//礼品店主数据
					$gaent_info = $this->rank_info('5');
					$divided = (($orderInfo['total_amount'] * $gaent_info['identical_profit']) / 100);
					$dividedParent = (($orderInfo['total_amount'] * $gaent_info['indirect_profit']) / 100);
					
					if($divided)
						$row = cashGiftLog($parentInfo['user_id'],$divided,$desc,'',$order_sn); 
					//间推分润 商家、区域经理、区代、市代
					if($parentInfo['FFactoryId'] && $divided){
						
						$row = cashGiftLog(0,$divided,$desc,'',$order_sn,$parentInfo['FFactoryId']); 
						
						//区域经理
					}elseif($parentInfo['FManagerId'] && $dividedParent){
						
						if($parentInfo['FManagerId'] != $parentInfo['user_id'])
						  $row = cashGiftLog($parentInfo['FManagerId'],$dividedParent,$desc,'',$order_sn); 
						
						//区代、市代
					}else{
						$districtAgent = Db::name('busines_agent')->where(['district'=>$userInfo['district'],'agent_rank'=>2])->find();
						if($districtAgent && $dividedParent){
							$row = cashGiftLog($districtAgent['FUid'],$dividedParent,$desc,'',$order_sn); 
						}else{
							$CityAgent = Db::name('busines_agent')->where(['city'=>$userInfo['city'],'agent_rank'=>1])->find();
							if($CityAgent && $dividedParent){
								$row = cashGiftLog($CityAgent['FUid'],$dividedParent,$desc,'',$order_sn); 
							}
						}
					}
				}
				
				Db::name('shop')->insert(['user_id'=>$orderInfo['user_id'],'shop_name'=>'一礼通_YLT'.$orderInfo['user_id'],'shop_image'=>'/public/static/images/tou2.png','shop_background'=>'/public/static/images/bg002.png']);
				
				//修改入驻信息
				Db::name('users')->where('user_id',$orderInfo['user_id'])->update(['FManagerId'=>$parentInfo['FManagerId'],'business_level'=>$userInfo['agent_rank']]);
			}
			
			//修改订单状态
		 $res = Db::name('entry_order')->where('order_sn',$order_sn)->update(['divided_status'=>1,'transaction_id'=>$data['transaction_id']]);
		
		//修改入驻信息
		$res = Db::name('busines_manager')->where('FUid',$orderInfo['user_id'])->update(['divided_status'=>1,'status'=>1]);
		//Db::name('users')->where('user_id',$orderInfo['user_id'])->update(['FManagerId'=>$orderInfo['user_id'],'business_level'=>$userInfo['agent_rank']]);
		
		$remarks = '公司业务提成';
		$dividedParent = (($supplier['supplier_money'] * 10) / 100); //支付金额的 10%
		cashGiftLog('21',$dividedParent,$desc,$remarks,$supplier['recommend_code']);  //综合管理部 18038195536
		cashGiftLog('163435',$dividedParent,$desc,$remarks,$supplier['recommend_code']);  //市场部 13902465187
		cashGiftLog('1',$dividedParent,$desc,$remarks,$supplier['recommend_code']); //运营技术部 13829208060

		}
		
		
		return array('status'=>1,'msg'=>'接收成功');
    }
	
	
	 /**
     * 订单销售分润数据记录
     * @param $order array() 订单数据
     * @return mixed 找到返回数组
     */
    public function orderDividedInto($order=array(),$ext=array())
    {
		
		if($order['is_divided_into'] == '0'){
			
			$data = array();

			$userInfo = Db::name('users')->field('user_id,parent_id')->where('user_id',$order['user_id'])->find();
			
			//直推 市场开发奖 自营店铺
		
			if($userInfo['parent_id']){
				// 会员及业务员直推
				$pos = strpos($userInfo['parent_id'],'su');
				if($pos === false){
					$parent_id = intval(str_replace("us","",$userInfo['parent_id']));
					$parentLevel = Db::name('users')->where('user_id',$parent_id)->value('business_level');
					//推荐分成比例 会员或者是业务员
					if($parentLevel == 0){
						$parentInfo = $this->rank_info('6');
					}else{
						$parentInfo = $this->rank_info($parentLevel);
					}
					
					
				}else{
					//商家直推
					$parent_id = intval(str_replace("su","",$userInfo['parent_id']));
					
					$parentInfo = $this->rank_info('3');
					
				}
				
					//佣金总金额 //自营算法和普通商户算法不同
				if($order['supplier_id'] == '41'){
					$commissionPrice = Db::name('order_goods')->where('order_id',$order['order_id'])->sum('goods_num * commission_price');
					$divided = (($commissionPrice * $parentInfo['platform_open_gift']) / 100);
					$data['commission_portion']	= $parentInfo['platform_open_gift']; //佣金比例
				}else{
					$commissionPrice = $order['total_amount'];
					$divided = (($commissionPrice * $parentInfo['open_gift']) * 0.0006);
					$data['commission_portion']	= $parentInfo['open_gift'];
				}
				$data['recommend_code'] = $userInfo['parent_id'];
				$data['commission_price'] = $commissionPrice;	//佣金总额
				$data['commission_divided'] = sprintf('%.2f',$divided);	//佣金分成
				
			}
				
			
			//销售奖 ：↓
			
			$supplierInfo = Db::name('supplier')->field('province,city,area,FManagerId')->where('supplier_id',$order['supplier_id'])->find();
			
			//市代
			$cityAgent = Db::name('busines_agent')->where("agent_rank = '1' and city = $supplierInfo[city] and status = '1'")->value('FUid');
			if($cityAgent && $order['supplier_id'] != '41'){
				$rankInfo = $this->rank_info('1');
				$divided = (($order['total_amount'] * $rankInfo['sale_gift']) * 0.0006);
				$data['city_agent_id'] = $cityAgent;
				$data['city_divided'] = sprintf('%.2f',$divided);
				
			}
			//区代
			$areaAgent = Db::name('busines_agent')->where("agent_rank = '2' and district = $supplierInfo[area] and status = '1'")->value('FUid');
			if($areaAgent && $order['supplier_id'] != '41'){
				$rankInfo = $this->rank_info('2');
				$divided = (($order['total_amount'] * $rankInfo['sale_gift']) * 0.0006);
				$data['area_agent_id'] = $areaAgent;
				$data['area_divided'] = sprintf('%.2f',$divided);
				
			}
			// 业务员
			if($supplierInfo['FManagerId'] && $order['supplier_id'] != '41'){
				
				$rankInfo = $this->rank_info('4');
				$divided = (($order['total_amount'] * $rankInfo['sale_gift']) * 0.0006);
				$data['FManagerId'] = $supplierInfo['FManagerId'];
				$data['manager_divided'] = sprintf('%.2f',$divided);
				
			}
			// 店主分享 或者 无店主分享
			if($order['recommend_code']){
				
				$pos = strpos($order['recommend_code'],'su');
				if($pos === false){
					$parent_id = intval(str_replace("us","",$order['recommend_code']));
					// 礼品店主才分红 会员无分红
					$parentInfo = Db::name('users')->where("user_id = $parent_id and business_level = 5")->value('user_id');
					if($parentInfo){
						
						$rankInfo = $this->rank_info('5');
						
						if($order['supplier_id'] == '41'){ //自营店铺 算佣金，其他店铺算抽成
								$commissionPrice = Db::name('order_goods')->where('order_id',$order['order_id'])->sum('goods_num * commission_price');
								$divided = (($commissionPrice * $rankInfo['platform_sale_gift']) / 100);
								$data['shopkeeper_portion'] = $rankInfo['platform_sale_gift'];
								$extra_divided = 0;
							}else{
								$extra_divided = Db::name('order_goods')->where('order_id',$order['order_id'])->sum('goods_num * commission_price');
								$commissionPrice = $order['total_amount'];
								$divided = (($commissionPrice * $rankInfo['sale_gift']) * 0.0006) + $extra_divided;
								$data['shopkeeper_portion'] = $rankInfo['sale_gift'];
							}
						$data['extra_divided'] = $extra_divided; //商家给予的佣金
						$data['shopkeeper_id'] = $parentInfo;
						$data['shopkeeper_divided'] = sprintf('%.2f',$divided);
						
					}
				
				}else{
					$rankInfo = $this->rank_info('3');
					$divided = (($order['total_amount'] * $rankInfo['sale_gift']) * 0.0006);
					$data['supplier_divided'] = sprintf('%.2f',$divided);
					
				}
				
			}else{
				$rankInfo = $this->rank_info('3');
				$divided = (($order['total_amount'] * $rankInfo['sale_gift']) * 0.0006);
				$data['supplier_divided'] = sprintf('%.2f',$divided);
				
			}
			
			
			$data['order_id'] 	=  $order['order_id'];	//订单ID
			$data['order_sn']	=  $order['order_sn'];	//订单sn
			//$data['recommend_code'] 	=  $order['recommend_code'];	//推荐人
			$data['total_amount'] =  $order['total_amount'];	//订单总金额
			$data['province'] 	=  $supplierInfo['province'];	
			$data['city']		=  $supplierInfo['city'];	//代理城市
			$data['area']		=  $supplierInfo['area'];	//代理区域
			$data['add_time'] 	=  time();	
			$data['supplier_id']=  $order['supplier_id'];	//商家ID
			$data['supplier_name']=  $order['supplier_name'];	//商家ID
			$data['pay_code']	=	$order['pay_name'];	//方式
			$data['transaction_id']=$ext['transaction_id']; //支付凭证
			$data['operate_divided']= (($order['total_amount'] * 15) * 0.0006); //公司业务提成
			
			$row = Db::name('order_divided_log')->insertGetId($data);
			$res = Db::name('order')->where('order_id',$order['order_id'])->update(['is_divided_into'=>1]);
		}
		
		
		return $row;
	}
	
	
	/**
	* 订单销售分润
	* @param $log_id int() 分润记录编码
	* @return mixed 找到返回数组
	*/
	public function orderDivided($log_id)
	{
	 $orderInfo = Db::name('order_divided_log')->where("log_id = $log_id and status = 0")->find();
	 
	 
	 $desc = '商品销售奖励';
	 // 推荐开发奖
	 if($orderInfo['recommend_code'] && $orderInfo['commission_divided']){
		 
		 $pos = strpos($orderInfo['recommend_code'],'su');
			if($pos === false){
				// 用户体系
				$parent_id = intval(str_replace("us","",$orderInfo['recommend_code']));
				$row = openGiftLog($parent_id,$orderInfo['commission_divided'],$desc,'',$orderInfo['order_id'],'',$orderInfo['commission_portion'],$orderInfo); 
			}else{
				// 商户体系
				$parent_id = intval(str_replace("su","",$orderInfo['recommend_code']));
				$row = openGiftLog('0',$orderInfo['commission_divided'],$desc,'',$orderInfo['order_id'],$parent_id,$orderInfo['commission_portion'],$orderInfo); 
			}
		 
	 }
	 
	 //销售奖 市代
	 if($orderInfo['city_agent_id'] && $orderInfo['city_divided']){
		 
		 $row = saleGiftLog($orderInfo['city_agent_id'],$orderInfo['city_divided'],$desc,'',$orderInfo['order_id'],'',$orderInfo['city_portion'],$orderInfo); 
	 }
	 
	 //销售奖 区代
	 if($orderInfo['area_agent_id'] && $orderInfo['area_divided']){
		 
		 $row = saleGiftLog($orderInfo['area_agent_id'],$orderInfo['area_divided'],$desc,'',$orderInfo['order_id'],'',$orderInfo['area_portion'],$orderInfo); 
	 }
	 
	 //销售奖 业务经理
	 if($orderInfo['FManagerId'] && $orderInfo['manager_divided']){
		 
		 $row = saleGiftLog($orderInfo['FManagerId'],$orderInfo['manager_divided'],$desc,'',$orderInfo['order_id'],'',$orderInfo['manager_portion'],$orderInfo); 
	 }
	 
	 //销售奖 商家分成
	 if($orderInfo['supplier_id'] && $orderInfo['supplier_divided']){
		 
		 $row = saleGiftLog('',$orderInfo['supplier_divided'],$desc,'',$orderInfo['order_id'],$orderInfo['supplier_id'],$orderInfo['supplier_portion'],$orderInfo); 
	 }
	 
	  //销售奖 礼品店主
	 if($orderInfo['shopkeeper_id'] && $orderInfo['shopkeeper_divided']){
		
		 $row = saleGiftLog($orderInfo['shopkeeper_id'],$orderInfo['shopkeeper_divided'],$desc,'',$orderInfo['order_id'],'',$orderInfo['shopkeeper_portion'],$orderInfo); 
	 }
	 
	 //销售奖 公司业务提成
	 if($orderInfo['operate_divided']){
		 $remarks = '公司业务提成';
		 $dividedParent = ($orderInfo['operate_divided'] / 3); //三部门平分 5%
		 $row = saleGiftLog('21',$dividedParent,$desc,$remarks,$orderInfo['order_id'],'','5',$orderInfo);  //综合管理部 18038195536
		 $row = saleGiftLog('163435',$dividedParent,$desc,$remarks,$orderInfo['order_id'],'','5',$orderInfo);   //市场部 13902465187
		 $row = saleGiftLog('1',$dividedParent,$desc,$remarks,$orderInfo['order_id'],'','5',$orderInfo);  //运营技术部 13829208060

	 }
	 
	 $row = Db::name('order_divided_log')->where('log_id',$log_id)->update(['status'=>1,'sign_time'=>time()]);
	 
	 return $row;
	 

	}
	 
	 	 
	/**
	 * 订单分成详情
	 * @param $log_id int() 表主键
	 * @return mixed 找到返回数组
	 */
	public function dividedDetails($log_id)
	{

		$res = Db::name('order_divided_log')->where('log_id',$log_id)->find();
		return $res;
	}
	 
	 
	/**
     * 提现审核/付款记录
	 * @param $type 1.提现审核 2.提现付款
     * @param $trading_id int() 提现记录编码
     * @return mixed 找到返回数组
     */
	public function examineWdr($type,$trading_id,$data=array())
	{
		 $bWU = array();
		 $gLU = array();
		 
		 $res = Db::name('busines_withdrawals')->where('trading_id',$trading_id)->find();
		 
		 if($type == '1'){
			$bWU['examine_time'] = time();
			$bWU['status'] = 1;
			
			$gLU['sign_status'] = 1;
			$gLU['sign_time'] = time();
		 }
		 
		 if($type == '2'){
			$bWU['pay_time'] = time();
			$bWU['pay_status'] = 1;
			$bWU['transaction_id'] = $data['transaction_id'];
			
			$gLU['pay_stauts'] = 1;
			$gLU['pay_time'] = time();
			
			if($res['status'] != '1')
				return array('status'=>'-1','msg'=>'提现未审核或审核不通过');
		 }
		
		//1礼豆，2礼金，3销售奖，4开发奖
		switch ($res['type'])
		{
		case '1':
			
			Db::name('bean_gift_log')->where('trading_id',$trading_id)->update($gLU);
			break;
		case '2':
			
			Db::name('cash_gift_log')->where('trading_id',$trading_id)->update($gLU);
			break;
		case '3':
			
			Db::name('sale_gift_log')->where('trading_id',$trading_id)->update($gLU);
			break;
		case '4':
			
			Db::name('open_gift_log')->where('trading_id',$trading_id)->update($gLU);
			break;
		default:
		}
		
		$res = Db::name('busines_withdrawals')->where('trading_id',$trading_id)->update($bWU);
		return array('status'=>1,'msg'=>'成功');
	}
	
  

}