<?php
define('IN_ECS', true);
require('init.php');
header("Content-Type:text/html;charset=UTF-8");
$action  = $_REQUEST['act'];
$ticket = $_REQUEST['ticket'];
$userinfo = '';
if(!empty($ticket)){
	$userinfo = split_user_ticket($ticket);
}
//获取优惠劵，和已领取优惠劵列表
if($action=="coupon_list"){
	$temptime=time();
	
	$where=" send_start_time <= {$temptime} AND send_end_time>={$temptime}";
	$sql = "SELECT * ".
			" FROM " .$GLOBALS['ecs']->table('coupon').
			" WHERE is_display = 1 AND {$where}";
	$res = $GLOBALS['db']->getAll($sql);
	
	$temp=array();
	if(!empty($res)){
		foreach ($res as $val){
			$temp[$key]=$val;
			$temp[$key]['cid']=$val['id'];
			$temp[$key]['note']="满{$val[condition]}元可用";
			$temp[$key]['use_start_time']=!empty($val['use_start_time']) ? date('Y-m-d',$val['use_start_time']) :'';
			$temp[$key]['use_end_time']=!empty($val['use_end_time']) ? date('Y-m-d',$val['use_end_time']) : '';
		}
	}
	if(!empty($temp)){
		$results = array(
				'result' => 1,
				'info' =>'请求成功',
				'list' =>$temp//	商品数据
		);
		exit($json->json_encode_ex($results));
	}
	$results = array(
			'result' =>1,
			'info'=>'无数据',
			'list' =>$temp
	);
	exit($json->json_encode_ex($results));
}

//领取优惠劵
elseif($action=="get_coupon"){
	$userid=!empty($userinfo['userid']) ? $userinfo['userid'] : '';
	$cid=!empty($_REQUEST['cid']) ? $_REQUEST['cid'] :'';
	if(empty($userid) || empty($cid)){
		$results = array(
				'result' =>0,
				'info'=>'缺少参数',
		);
		exit($json->json_encode_ex($results));
	}
	//查看优惠劵是否可用领取
	$temptime=time();
	$where=" send_start_time <= {$temptime} AND send_end_time>={$temptime}";
	$sql = "SELECT * ".
			" FROM " .$GLOBALS['ecs']->table('coupon').
			" WHERE is_display = 1 AND {$where}";
	
	$rs = $GLOBALS['db']->getRow($sql);
	if(empty($rs)){
		$results = array(
				'result' =>0,
				'info'=>'优惠劵暂不能领用',
		);
		exit($json->json_encode_ex($results));
	}

	//查看是否领取优惠劵
	$sql="select id from ".$GLOBALS['ecs']->table('coupon_list')." where cid={$cid} AND uid={$userid}";
	$row=$GLOBALS['db']->getRow($sql);
	if(!empty($row)){
		$results = array(
				'result' =>0,
				'info'=>'此优惠劵已领取',
		);
		exit($json->json_encode_ex($results));
	}
	//领取优惠劵
	$sql="insert INTO ".$GLOBALS['ecs']->table('coupon_list')."(uid,type,cid,send_time) values ({$userid},'{$rs[type]}',{$cid},'{$temptime}')";
	$one=$GLOBALS['db']->query($sql);
	if($one){
		$results = array(
				'result' =>1,
				'info'=>'优惠劵领取成功',
		);
	}else{
		$results = array(
				'result' =>0,
				'info'=>'优惠劵领取失败',
		);
	}
	exit($json->json_encode_ex($results));
}

//使用优惠劵码
else if($action=='put_user_code'){
  
    $userid=!empty($userinfo['userid']) ? $userinfo['userid'] : '';
    $code=!empty($_REQUEST['code']) ? $_REQUEST['code'] :'';
  //选中的购物车里边的id
	$ids=trim($_REQUEST['cart_ids']);
   	$code_price=0;
  
    if(empty($userid) || empty($code)){
        $results = array(
            'result' =>0,
            'info'=>'缺少参数',
        );
        exit($json->json_encode_ex($results));
    }
    $sql="select * from ".$GLOBALS['ecs']->table('code_list')." where code={$code}";
    $row=$GLOBALS['db']->getRow($sql);
  
  	if(empty($row)){
        $results = array(
            'result' =>0,
            'info'=>'优惠券码不存在',
        );
        exit($json->json_encode_ex($results));
    }
  
   if($row['order_id'] > 0||$row['uid'] > 0){
            $results =array(
              'result'=>0,
              'info'=>'该优惠券已被使用',
            );
    	exit($json->json_encode_ex($results));
    }
  	
  	$sql="select * from ".$GLOBALS['ecs']->table('code')." where id={$row['cid']}";
   	$code=$GLOBALS['db']->getRow($sql);
  
   if(time() > $code['use_end_time']){
   	$results =array(
              'result'=>0,
              'info'=>'优惠券已经过期',
            );
    	exit($json->json_encode_ex($results));
   }         
   
  if(time() < $code['use_start_time']){
           $results =array(
              'result'=>0,
              'info'=>'活动还未开启,开启时间'.date('Y-m-d h:i:s',$code['use_start_time']),
            );
    	exit($json->json_encode_ex($results));
    }
  	
  	$sql="select * from ".$GLOBALS['ecs']->table('cart')." where id in ({$ids}) and user_id ={$userid} order by add_time desc , supplier_id asc ";
  
  	$cart=$GLOBALS['db']->getAll($sql);
  	if(empty($cart)){
        $results = array(
            'result' =>0,
            'info'=>'购物车中没有选中的商品',
        );
        exit($json->json_encode_ex($results));
    }
     if($code['coupon_type']==0){ //优惠商品
            $goddsId=explode(',',$code['goods_id']);
            foreach($cart as $k=>$val){
                foreach($goddsId as $key=>$v){
                    if($v==$val['goods_id']){                   
                        $goods_code_price+=$val['goods_num'] * $val['goods_price'];//优惠商品总价
                    }
                }
                $total_price+=$val['goods_num'] * $val['goods_price'];//商品总价
            }
        }else{//优惠店铺
            foreach($cart as $k=>$val){
                    if($code['supplier_id']==$val['supplier_id']){                     
                        $goods_code_price+=val['goods_num'] * $val['goods_price'];//优惠商品总价
                    }
                $total_price+=$val['goods_num'] * $val['goods_price'];//商品总价
            }
        }
  if($goods_code_price>0){
          if($goods_code_price>$code['money']){
           		 $total_price=$total_price-$goods_code_price+($goods_code_price-$code['money']);//待支付价格
           		 $code_price=$code['money'];//优惠价格
        	}else{
           		// $goods_code_price=$code['money']-$goods_code_price;
            	 $code_price=$goods_code_price;//优惠价格
            	 $total_price=$total_price-$goods_code_price;//待支付价格
        	}
        }else{
    		$results = array(
        	    'result' =>0,
        	    'info'=>'该购物车中没有优惠商品',
        	);
        	exit($json->json_encode_ex($results));
        }        
        $result['code_price']=$code_price;//优惠价格
        $result['total_price']=$total_price;//待支付价格
  	  $results = array(
            'result' =>1,
            'info'=>'使用成功',
      		'data'=>$result,
        );
        exit($json->json_encode_ex($results));
    
}

//获取用户已领取的优惠劵
else if($action=='user_coupon_list'){
	$userid=!empty($userinfo['userid']) ? $userinfo['userid'] : '';
	$temptime=time();
	$page=!empty($_REQUEST['page']) ? $_REQUEST['page'] : '0';
	$size =10;
	$begin = $page*$size;
	$limit = " LIMIT $begin,$size";
	//优惠劵列表1
	$file="A.id,A.cid,A.type,A.uid,A.order_id,A.use_time,A.send_time,B.money,B.condition,B.use_start_time,B.use_end_time,B.name as coupon_name";
	$sql="select {$file} from ".$GLOBALS['ecs']->table('coupon_list')." as A LEFT JOIN ".$GLOBALS['ecs']->table('coupon')." as B ON A.cid=B.id where uid={$userid} AND B.is_display=1 $limit";
	$rs=$GLOBALS['db']->getAll($sql); 
	$arr=array();
	if(!empty($rs)){
		foreach ($rs as $key => $val){
			$arr[$key]['money']=$val['money'];
			$arr[$key]['note']="满{$val[condition]}可用";
			$arr[$key]['coupon_name']=$val['coupon_name'];
			$arr[$key]['use_start_time']=!empty(date("Y-m-d",$val['use_start_time'])) ? date("Y-m-d",$val['use_start_time']) : '';
			$arr[$key]['use_end_time']=!empty(date("Y-m-d",$val['use_end_time'])) ? date("Y-m-d",$val['use_end_time']) : '';
			if($val['use_end_time']>=$temptime && $val['use_start_time'] <=$temptime){
				//已经过期
				$arr[$key]['status']=2;
			}else if(!empty($val['order_id']) && !empty($val['use_time'])){
				//已使用
				$arr[$key]['status']=1;
			}else{
				//未使用
				$arr[$key]['status']=0;
			}
		}
	}	
	
	$sql="select count(*) from ".$GLOBALS['ecs']->table('coupon_list')." as A LEFT JOIN ".$GLOBALS['ecs']->table('coupon')." as B ON A.cid=B.id where uid={$userid} AND B.is_display=1 ";
	$goods_count=$GLOBALS['db']->getOne($sql); 
	$allpage=ceil($goods_count/$size);
	if(!empty($arr)){
		$results = array(
				'result' =>1,
				'info'=>'成功',
				'list'=>$arr,
				'page'=>$page,
				'count'=>$allpage,
				'size'=>$size
		);
	}else{
		$results = array(
				'result' =>1,
				'info'=>'无数据',
				'list'=>$arr,
				'page'=>$page,
				'count'=>$allpage,
				'size'=>$size
		);
	}
	exit($json->json_encode_ex($results));
}

//用户提现
else if($action=="cash_money"){
	//增加提现记录，提现用户金额减去-提现金额（user_money-money），冻结金额加上+提现金额，(frozen_money+moeny)，
	//减去用户金额
	//添加日志记录信息
	
}





