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
//请求其他平台的数据
if($action=="index"){
	$type=$_REQUEST['type'];
	if($type=='2'){
		//海淘商品
		$list=get_goods();
		if($list){
			$rs=array('result'=>'1','info'=>'请求成功',"data"=>$list);
			exit($json->json_encode_ex($rs));
		}else{
			$rs=array('result'=>'1','info'=>'无数据',"data"=>array());
			exit($json->json_encode_ex($rs));
		}
	}elseif($type=="1"){
		//礼金融
		$rs=array('result'=>'1','info'=>'暂无数据',"data"=>array());
		exit($json->json_encode_ex($rs));
	}
	$rs=array('result'=>'0','info'=>'失败',"data"=>array());
	exit($json->json_encode_ex($rs));
	
}elseif($action=='gift_giving'){
    //活动商品列表
    $sql="SELECT goods_id,shop_price,commission_price,supplier_id,goods_name,original_img FROM ".$GLOBALS['ecs']->table('goods').	"  where  examine=1 AND is_on_sale=1 AND cat_id='1038'";
    $rs=$GLOBALS['db']->getAll($sql);
    $list=array();
    if(!empty($rs)){
        foreach ($rs as $key =>$val){
            $list[$key]=$val;
            $list[$key]['image']=!empty($val['image']) ? IMG_HOST.$val['image'] : "";
        }
    }
    $rs=array('result'=>'1','info'=>'成功','list'=>$list);
    exit($json->json_encode_ex($rs));
}elseif($action=='activity'){
	//活动类型,限时活动
	$type=!empty($_REQUEST['type']) ? $_REQUEST['type'] : "1";
	// 1秒杀 2抢购
	if($type=="1" || $type=="2"){
		//广告列表
		$sql="SELECT A.ad_name,A.ad_link,A.ad_code FROM ".$GLOBALS['ecs']->table('ad_position')." as P left join ".$GLOBALS['ecs']->table('ad').	" as A ON P.position_id=A.pid where A.enabled=1 AND P.position_id=26 AND P.is_open=1 order by A.orderby DESC";
		$ads=$GLOBALS['db']->getAll($sql);
		$temp=array();
		if(!empty($ads)){
			foreach ($ads as $key=>$val){
				$temp[$key]['image']=IMG_HOST.$val['ad_code'];
				$temp[$key]['ad_name']=$val['ad_name'];
				if(is_numeric($val['ad_link'])){
					$temp[$key]['goods_id']=$val['ad_link'];
				}elseif(strpos($val['ad_link'],"##") == true){
					$temp['ad_type']=strstr($val['ad_link'],'##');
				}else{
					if($val['ad_link']=="javascript:void();"){
						$temp[$key]['ad_link']="";
					}else{
						$temp[$key]['ad_link']=$val['ad_link'];
					}
				}
				$temp[$key]['ad_link']=!empty($temp[$key]['ad_link']) ? $temp[$key]['ad_link'] : "";
				$temp[$key]['ad_type']=!empty($temp[$key]['ad_type']) ? $temp[$key]['ad_type'] : "";
				$temp[$key]['goods_id']=!empty($temp[$key]['goods_id']) ? $temp[$key]['goods_id'] : "";
			}
		}
		
		if($type==1){ // 1为
			$buy_type=5;
		}else{
			$buy_type=1;
		}
		//活动商品列表
		$sql="SELECT A.goods_id,A.price,A.buy_limit,A.start_time,A.end_time,A.is_end,A.goods_name,A.buy_type,B.original_img as image,B.market_price FROM ".$GLOBALS['ecs']->table('panic_buying')." as A left join ".$GLOBALS['ecs']->table('goods').	" as B ON A.goods_id=B.goods_id where  B.examine=1 AND B.is_on_sale=1 AND is_end=0 AND A.buy_type={$buy_type} order by A.start_time DESC";
		$rs=$GLOBALS['db']->getAll($sql);
		$list=array();
		if(!empty($rs)){
			foreach ($rs as $key =>$val){
				$list[$key]=$val;
				$list[$key]['image']=!empty($val['image']) ? IMG_HOST.$val['image'] : "";
			}
		}
		$sql="SELECT A.goods_id,A.price,A.buy_limit,A.start_time,A.end_time,A.is_end,A.goods_name,A.buy_type,B.original_img as image,B.market_price FROM ".$GLOBALS['ecs']->table('panic_buying')." as A left join ".$GLOBALS['ecs']->table('goods').	" as B ON A.goods_id=B.goods_id where  B.examine=1 AND B.is_on_sale=1 AND is_end=0 AND A.buy_type={$buy_type} order by A.start_time DESC";
		$one=$GLOBALS['db']->getRow($sql);
		$one['start_time']=!empty($one['start_time']) ? $one['start_time'] : '';
		$one['end_time']=!empty($one['end_time']) ? $one['end_time'] :'';
		$rs=array('result'=>'1','info'=>'成功',"ads"=>$temp,'list'=>$list,'start_time'=>$one['start_time'],'end_time'=>$one['end_time'],'act_name'=>"限时抢购活动");
		exit($json->json_encode_ex($rs));
	}elseif($type==3){
		//其他多各活动，
		$act_id=$_REQUEST['act_id'];
		$page=!empty($_REQUEST['page']) ? $_REQUEST['page'] : 0;
		if(!empty($act_id)){
			$sql="SELECT name FROM ".$GLOBALS['ecs']->table('activity_cate')." where id={$act_id}";
		    $act_name=$GLOBALS['db']->getOne($sql);
		}else{
			$act_name="折扣活动";
		}
		if(empty($act_id)){
			$rs=array('result'=>'0','info'=>'缺少参数',"ads"=>array(),'list'=>array(),'start_time'=>'','end_time'=>'','act_name'=>"",'page'=>'','count'=>'');
			exit($json->json_encode_ex($rs));
		}
		$list=get_activity_goods($act_id,$page);
		$count=$list['count'];
		unset($list['count']);
		$rs=array('result'=>'1','info'=>'成功',"ads"=>array(),'list'=>$list,'start_time'=>'','end_time'=>'','act_name'=>"{$act_name}",'page'=>$page,'count'=>$count);
		exit($json->json_encode_ex($rs));
	}
}
/* //等待接口
if($action=="wait"){
	$str=rand(15,25)*1000;
	sleep($str);
} */

//获取活动，列表
function get_activity_goods($act_id,$page){
	$size =20;
	$begin = $page*$size;
	$limit = " LIMIT $begin,$size";
	//按条件获取商品列表
	$sql="SELECT goods_id,goods_name,shop_price,market_price,sales_sum,original_img from ".$GLOBALS['ecs']->table('goods')." {$where} order by {$sort} {$order} {$limit}";
	
	$sql="SELECT  GROUP_CONCAT(distinct(B.goods_id)) as ids FROM ".$GLOBALS['ecs']->table('activity_cate')." as A left join "
			.$GLOBALS['ecs']->table('activity_goods').	" as B  ON A.id=B.act_id where A.id={$act_id} AND A.is_display=1 ";
	$ids=$GLOBALS['db']->getOne($sql);
	$count=0;
	$list=array();
	if(!empty($ids)){
		$sql="SELECT goods_name,goods_id,original_img as image,market_price,shop_price as price,sales_sum FROM ".$GLOBALS['ecs']->table('goods')." where examine=1 AND is_on_sale=1 AND goods_id in ( {$ids} ) $limit";
		$rs=$GLOBALS['db']->getAll($sql);
		if(!empty($rs)){
			foreach ($rs as $key =>$val){
				$list[$key]=$val;
				$list[$key]['image']=!empty($val['image']) ? IMG_HOST.$val['image'] : "";
			}
		}
		
		$sql="SELECT count(*) FROM ".$GLOBALS['ecs']->table('goods')." where examine=1 AND is_on_sale=1 AND goods_id in ( {$ids} ) ";
		$count=$GLOBALS['db']->getOne($sql);
		$allpage=ceil($count/$size);
	}
	$list['count']=$allpage;
	return $list;
}

//获取海淘数据
function get_goods(){
	$arr = read_static_cache('other_goods_list');
	$rs=array();
	if ($arr === false)
	{
		//获取商品demo
		$data['hash_code'] = '31693422540744c0a6b6da635b7a5a93';
		$data['action'] = 'goods_list';
		$data['auth'] = 'hysjg';
		$data['verify'] = md5($data['hash_code'] . $data['action'] . $data['auth']);
		$data['record_number'] = 10;//可选，默认30,超过30最多取30
		$data['page_number'] = 0;//可选，默认从第0页开始
		$url = 'http://www.hysjg.com/api/goods.php';
		$goods_list = file_get_contents_curl($url, $data);
		$goods_list = json_decode($goods_list, true);
		$rs=write_static_cache('other_goods_list', $goods_list['data']);
	}else{
		$rs=$arr;
	}
	return $rs;
}

function file_get_contents_curl($url, $data)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'accept-language:zh-CN,zh;q=0.8,zh-TW;q=0.6,en;q=0.4',
        'cache-control:max-age=0',
        'upgrade-insecure-requests:1',
        'user-agent:Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.94 Safari/537.36',
    ));

    curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $dxycontent = curl_exec($ch);
    return $dxycontent;
}