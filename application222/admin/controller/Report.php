<?php
namespace ylt\admin\controller;
use ylt\admin\logic\GoodsLogic;
use think\Db;
use think\Page;
use think\Request;
use think\Url;
use think\Cache;

class Report extends Base{
	public $begin;
	public $end;
	public function _initialize(){
        parent::_initialize();
		$timegap = I('timegap');
		if($timegap){
			$gap = explode(' - ', $timegap);
			$begin = $gap[0];
			$end = $gap[1];
		}else{
			$lastweek = date('Y-m-d',strtotime("-1 month")); // 1个月的数据
			// $lastweek = date('Y-m-d',strtotime("-1 year")); //1年前
			$begin = I('begin',$lastweek);
			$end =  I('end',date('Y-m-d'));
		}
        // $this->order_url="http://www.szleezen.cn";
        $this->order_url = "http://lzjj.cn";
		$this->assign('start_time',$begin);
		$this->assign('end_time',$end);
		$this->begin = strtotime($begin);
		$this->end = strtotime($end)+86399;
        session('start_time',$this->begin);
        session('end_time',$this->end);
		$this->assign('timegap',date('Y-m-d',$this->begin).' - '.date('Y-m-d',$this->end));
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
     * [index 集团数据]
     * @return [type] [description]
     */
	public function index(){
        $begin = $this->begin;
        $end = $this->end; 
        if($begin && $end){
            $condition['pay_time'] = array('between',"$begin,$end");
        }
		$a_today_amount = Db::name('order')->where("pay_status=1  and order_status in(0,1,2,4)")->where($condition)->sum('order_amount');//一礼通平台销售总额
		$r_today_amount = $today['r_today_amount']  = Db::name('red_order')->where("pay_status=1  and order_status in(2,3)")->where($condition)->sum('order_amount');//红礼供应链销售总额
        $h_today_amount = $this->get_urls("$this->order_url/Home/Api/h_today_amount?begin=$begin&end=$end");//礼至家居平台销售总额
        $h_today_amount = json_decode($h_today_amount,true);
        $today['h_today_amount'] = $h_today_amount['h_today_amount'];
        $today['today_amount'] = $a_today_amount+$h_today_amount['h_today_amount']+$r_today_amount; //销售总额
		$today['p_today_amount'] = Db::name('order')->where("pay_status=1  and order_status in(0,1,2,4)")->where($condition)->where('plate','礼至礼品')->sum('order_amount');//礼至礼品销售总额
		$today['a_today_amount'] = Db::name('order')->where("pay_status=1  and order_status in(0,1,2,4)")->where($condition)->where('plate','一礼通')->sum('order_amount');//一礼通销售总额
		$today['p_today_amount']=$today['p_today_amount']?$today['p_today_amount']:0;
		$today['h_today_amount']=$today['h_today_amount']?$today['h_today_amount']:0;
		$today['r_today_amount']=$today['r_today_amount']?$today['r_today_amount']:0;
		$today['a_today_amount']=$today['a_today_amount']?$today['a_today_amount']:0;
		$today['today_amount']=$today['today_amount']?$today['today_amount']:0;
		$this->assign('today',$today);
		return $this->fetch();
	}
	
	/**
	 * [index_home 礼至家居]
	 * @return [type] [description]
	 */
	public function index_home(){
        $begin = $this->begin;
        $end = $this->end; 
        $items_source = I('items_source');
        if($begin && $end){
            $condition['pay_time'] = array('between',"$begin,$end");
        }
        $h_today_amount = $this->get_urls("$this->order_url/Home/Api/h_today_amount?begin=$begin&end=$end");//礼至家居平台销售总额
        $h_today_amount = json_decode($h_today_amount,true);

		$index_home = $this->get_urls("$this->order_url/Home/Api/index_home?begin=$begin&end=$end");//礼至家居平台销售项目数据
        $index_home = json_decode($index_home,true);
        if ($index_home) {
    		$index_home_list = $this->role_api('3',$index_home['index_home']);
        }else{
        	$index_home_list['其它']=0;
        }

		$this->assign('h_today_amount',$h_today_amount['h_today_amount']);
		$this->assign('index_home',$index_home);
		$this->assign('index_home_list',$index_home_list);
		return $this->fetch();
	}

	public function role_api($plate_id,$index_home){
		$index_home_list['其它']  = 0;
		$role = Db::name('admin_role')->where('plate_id',$plate_id)->where('is_three=1')->select();
		if ($index_home) {
	    	foreach ($index_home as $ke => $val) {
	    		if ($role) {
		    		foreach ($role as $key => $value) {
		        		if ($value['role_name']==$val['items_source'] or "'".$value['role_name']."'"==$val['items_source']) {
							$index_home_list[$value['role_name']] += $val['order_amount'];
		        		}
		    		}
		    		if("'其它'"==$val['items_source'] or '其它'==$val['items_source']) {
						$index_home_list['其它'] += $val['order_amount'];
		    		}
	    		}else{
		    		if("'其它'"==$val['items_source'] or '其它'==$val['items_source']) {
						$index_home_list['其它'] += $val['order_amount'];
		    		}
	    		}
	    	}
		}
		return $index_home_list;
	}	
	/**
	 * [index_gift 礼至礼品]
	 * @return [type] [description]
	 */
	public function index_gift(){
        $begin = $this->begin;
        $end = $this->end; 
        $items_source = I('items_source');
        if($begin && $end){
            $condition['pay_time'] = array('between',"$begin,$end");
        }
		$p_today_amount = Db::name('order')->where("pay_status=1  and order_status in(0,1,2,4)")->where($condition)->where('plate','礼至礼品')->sum('order_amount');//礼至礼品销售总额
        $index_home = Db::name('order')->where("pay_status=1  and order_status in(0,1,2,4)")->where($condition)->where('plate','礼至礼品')->field('order_id,items_source,pay_time,order_status,order_amount')->select();//礼至家居平台销售列表
        if ($index_home) {
    		$index_home_list = $this->role_api('25',$index_home);
        }else{
        	$index_home_list['其它']=0;
        }

		$this->assign('p_today_amount',$p_today_amount);
		$this->assign('index_home_list',$index_home_list);
		return $this->fetch();
	}	
	/**
	 * [index_all 一礼通]
	 * @return [type] [description]
	 */
	public function index_all(){
        $begin = $this->begin;
        $end = $this->end; 
        $items_source = I('items_source');
        if($begin && $end){
            $condition['pay_time'] = array('between',"$begin,$end");
        }
		$a_today_amount = Db::name('order')->where("pay_status=1  and order_status in(0,1,2,4)")->where($condition)->where('plate','一礼通')->sum('order_amount');//一礼通销售总额
        $index_home = Db::name('order')->where("pay_status=1  and order_status in(0,1,2,4)")->where($condition)->where('plate','一礼通')->field('order_id,items_source,pay_time,order_status,order_amount')->select();//一礼通平台销售列表
        if ($index_home) {
    		$index_home_list = $this->role_api('2',$index_home);
        }else{
        	$index_home_list['其它']=0;
        }

		$this->assign('a_today_amount',$a_today_amount);
		$this->assign('index_home_list',$index_home_list);
		return $this->fetch();
	}	
	/**
	 * [index_red 红礼]
	 * @return [type] [description]
	 */
	public function index_red(){
		// 支出为红礼采购的价格，收入为红礼供应到公司的价格
        $begin = $this->begin;
        $end = $this->end; 
        $items_source = I('items_source');
        if($begin && $end){
            $condition['pay_time'] = array('between',"$begin,$end");
        }
		
		// 礼至礼品
		$p_today_amount['order_id'] = Db::name('order')->where("pay_status=1  and order_status in(0,1,2,4)")->where($condition)->where('plate','礼至礼品')->column('order_id');//礼至礼品销售订单ID
		if ($p_today_amount['order_id']) {
			$p_today_amount['goods_price'] = Db::name('order_goods')->where("is_send=1  and order_id in(".  implode(',', $p_today_amount['order_id']).")")->sum('goods_price * goods_num');//给礼至礼品的收入（收入）
			$p_today_amount['cost_price'] = Db::name('order_goods')->where("is_send=1  and order_id in(".  implode(',', $p_today_amount['order_id']).")")->sum('cost_price * goods_num');//给礼至礼品的供应价（供货）
			$p_today_amount['red_cost_price'] = Db::name('order_goods')->where("is_send=1  and order_id in(".  implode(',', $p_today_amount['order_id']).")")->sum('red_cost_price * goods_num');//红礼采购价（采购）
		}
		$p_today_amount['red_cost_price']=$p_today_amount['red_cost_price']?$p_today_amount['red_cost_price']:0;
		$p_today_amount['cost_price']=$p_today_amount['cost_price']?$p_today_amount['cost_price']:0;
		$p_today_amount['goods_price']=$p_today_amount['goods_price']?$p_today_amount['goods_price']:0;

		//一礼通
		$a_today_amount['order_id'] = Db::name('order')->where("pay_status=1  and order_status in(0,1,2,4)")->where($condition)->where('plate','一礼通')->column('order_id');//一礼通销售订单ID
		if ($a_today_amount['order_id']) {
			$a_today_amount['goods_price'] = Db::name('order_goods')->where("is_send=1  and order_id in(".  implode(',', $a_today_amount['order_id']).")")->sum('goods_price * goods_num');//给一礼通的收入（收入）
			$a_today_amount['cost_price'] = Db::name('order_goods')->where("is_send=1  and order_id in(".  implode(',', $a_today_amount['order_id']).")")->sum('cost_price * goods_num');//给一礼通的供应价（供货）
			$a_today_amount['red_cost_price'] = Db::name('order_goods')->where("is_send=1  and order_id in(".  implode(',', $a_today_amount['order_id']).")")->sum('red_cost_price * goods_num');//红礼采购价（采购）
		}
		$a_today_amount['red_cost_price']=$a_today_amount['red_cost_price']?$a_today_amount['red_cost_price']:0;
		$a_today_amount['cost_price']=$a_today_amount['cost_price']?$a_today_amount['cost_price']:0;
		$a_today_amount['goods_price']=$a_today_amount['goods_price']?$a_today_amount['goods_price']:0;

		//礼至家居
        $h_today_amount = $this->get_urls("$this->order_url/Home/Api/h_today_amount?begin=$begin&end=$end");
        $h_today_amount = json_decode($h_today_amount,true);
		$h_today_amount['goods_price']=$h_today_amount['goods_price']?$h_today_amount['goods_price']:0;
		$h_today_amount['red_cost_price']=$h_today_amount['red_cost_price']?$h_today_amount['red_cost_price']:0;
		$h_today_amount['cost_price']=$h_today_amount['cost_price']?$h_today_amount['cost_price']:0;


        //红礼总额供货及采购
		$r_today_amount['red_cost_price']=$p_today_amount['red_cost_price']+$a_today_amount['red_cost_price']+$h_today_amount['red_cost_price'];
		$r_today_amount['cost_price']=$p_today_amount['cost_price']+$a_today_amount['cost_price']+$h_today_amount['cost_price'];
		$r_today_amount['goods_price']=$p_today_amount['goods_price']+$a_today_amount['goods_price']+$h_today_amount['goods_price'];


		$this->assign('r_today_amount',$r_today_amount);
		$this->assign('p_today_amount',$p_today_amount);
		$this->assign('a_today_amount',$a_today_amount);
		$this->assign('h_today_amount',$h_today_amount);
		return $this->fetch();
	}
	
	/**
	 * [order_list 订单列表]
	 * @return [type] [description]
	 */
	public function order_list(){
		if (I('plate') == '礼至家居') {
        	$this->redirect("Admin/RiteHome/inquire_index?keywords=".I('keywords'));
		}
		if (I('plate') == '红礼') {
        	$this->redirect("Admin/RedGift/red_orderIndex");
		}
        $this->redirect("Admin/Order/index?keywords=".I('keywords')."&plate=".I('plate'));
		return $this->fetch();
	}




/********************************************旧*******************************************/

	public function saleTop(){
		$sql = "select g.goods_name,g.goods_sn,sum(g.goods_num) as sale_num,sum(g.goods_num*g.goods_price) as sale_amount,o.supplier_name from __PREFIX__order_goods as g left join __PREFIX__order as o on g.order_id = o.order_id";
		$sql .=" where o.pay_status = 1  group by g.goods_id order by sale_amount DESC limit 50"; 
		$res = DB::cache(true,3600)->query($sql);
		$this->assign('list',$res);
		return $this->fetch();
	}
	
	public function userTop(){
		$p = I('p',1);
		$start = ($p-1)*20;
		$mobile = I('mobile');
		//$email = I('email');
		if($mobile){
			$where =  "and b.mobile='$mobile'";
		}		
		if($email){
			$where = "and b.email='$email'";
		}
		$sql = "select count(a.order_id) as order_num,sum(a.order_amount) as amount,a.user_id,b.mobile,b.nickname from __PREFIX__order as a left join __PREFIX__users as b ";
		$sql .= " on a.user_id = b.user_id where a.add_time>$this->begin and a.add_time<$this->end and a.pay_status=1 $where group by user_id order by amount DESC limit $start,20";
		$res = DB::cache(true)->query($sql);
		$this->assign('list',$res);
		if(empty($where)){
			$count = Db::name('order')->where("add_time>$this->begin and add_time<$this->end and pay_status=1")->group('user_id')->count();
			$Page = new Page($count,20);
			$show = $Page->show();
			$this->assign('count',$count);
			$this->assign('page',$show);
		}
		return $this->fetch();
	}
	
	public function saleList(){
		$p = I('p',1);
		$start = ($p-1)*20;
		$cat_id = I('cat_id',0);
		$brand_id = I('brand_id',0);
		$where = "where b.add_time>$this->begin and b.add_time<$this->end and b.pay_status = 1";
	
		$sql = "select a.goods_name,a.goods_sn,a.goods_num,a.goods_price,a.order_id,b.order_sn,b.supplier_name,b.pay_name,b.add_time from __PREFIX__order_goods as a left join __PREFIX__order as b on a.order_id=b.order_id $where";
	
		$sql .= "  order by add_time desc limit $start,20";
		$res = DB::query($sql);
		$this->assign('list',$res);
		
		$sql2 = "select count(*) as tnum from __PREFIX__order_goods as a left join __PREFIX__order as b on a.order_id=b.order_id $where";
		$total = DB::query($sql2);
		$count =  $total[0]['tnum'];
		$Page = new Page($count,20);
		$show = $Page->show();
		$this->assign('page',$show);
		$this->assign('count',$count);
		
		return $this->fetch();
	}
	
	public function user(){
		$today = strtotime(date('Y-m-d'));
		$month = strtotime(date('Y-m-01'));
		$user['today'] = Db::name('users')->cache(true,3600)->where("reg_time>$today")->count();//今日新增会员
		$user['month'] = Db::name('users')->cache(true,3600)->where("reg_time>$month")->count();//本月新增会员
		$user['total'] = Db::name('users')->count();//会员总数
		$res = Db::name('order')->cache(true)->distinct(true)->field('user_id')->select();
		$user['hasorder'] = count($res);
		$this->assign('user',$user);
		$sql = "SELECT COUNT(*) as num,FROM_UNIXTIME(reg_time,'%Y-%m-%d') as gap from __PREFIX__users where reg_time>$this->begin and reg_time<$this->end group by gap";
		$new = DB::query($sql);//新增会员趋势
		foreach ($new as $val){
			$arr[$val['gap']] = $val['num'];
		}
		
		for($i=$this->begin;$i<=$this->end;$i=$i+24*3600){
			$brr[] = empty($arr[date('Y-m-d',$i)]) ? 0 : $arr[date('Y-m-d',$i)];
			$day[] = date('Y-m-d',$i);
		}		
		$result = array('data'=>$brr,'time'=>$day);
		$this->assign('result',json_encode($result));					
		return $this->fetch();
	}
	
	//财务统计
	public function finance(){
		$sql = "SELECT sum(b.goods_num*b.member_goods_price) as goods_amount,sum(a.shipping_price) as shipping_amount,sum(b.goods_num*b.cost_price) as cost_price,";
		$sql .= "sum(a.coupon_price) as coupon_amount,FROM_UNIXTIME(a.add_time,'%Y-%m-%d') as gap from  __PREFIX__order a left join __PREFIX__order_goods b on a.order_id=b.order_id ";
		$sql .= " where a.add_time>$this->begin and a.add_time<$this->end AND a.pay_status=1 and a.shipping_status=1 and b.is_send=1 group by gap order by a.add_time";
		$res = DB::cache(true)->query($sql);//物流费,交易额,成本价
		
		foreach ($res as $val){
			$arr[$val['gap']] = $val['goods_amount'];
			$brr[$val['gap']] = $val['cost_price'];
			$crr[$val['gap']] = $val['shipping_amount'];
			$drr[$val['gap']] = $val['coupon_amount'];
		}
			
		for($i=$this->begin;$i<=$this->end;$i=$i+24*3600){
			$date = $day[] = date('Y-m-d',$i);
			$tmp_goods_amount = empty($arr[$date]) ? 0 : $arr[$date];
			$tmp_cost_amount = empty($brr[$date]) ? 0 : $brr[$date];
			$tmp_shipping_amount = empty($crr[$date]) ? 0 : $crr[$date];
			$tmp_coupon_amount = empty($drr[$date]) ? 0 : $drr[$date];
			
			$goods_arr[] = $tmp_goods_amount;
			$cost_arr[] = $tmp_cost_amount;
			$shipping_arr[] = $tmp_shipping_amount;
			$coupon_arr[] = $tmp_coupon_amount;
			$list[] = array('day'=>$date,'goods_amount'=>$tmp_goods_amount,'cost_amount'=>$tmp_cost_amount,
					'shipping_amount'=>$tmp_shipping_amount,'coupon_amount'=>$tmp_coupon_amount,'end'=>date('Y-m-d',$i+24*60*60));
		}
		$this->assign('list',$list);
		$result = array('goods_arr'=>$goods_arr,'cost_arr'=>$cost_arr,'shipping_arr'=>$shipping_arr,'coupon_arr'=>$coupon_arr,'time'=>$day);
		$this->assign('result',json_encode($result));
		return $this->fetch();
	}
	/**
	 * 下载推荐统计
	 */
	public function download(){
		
		$model = Db::name("extension_user");
        $keyword = I('keyword');
        $where = $keyword ? " promoter like '%$keyword%' " : "";
        $count = $model->where($where)->count();
        $Page = $pager = new Page($count,15);
        $list = $model->where($where)->order("`add_time` asc")->limit($Page->firstRow.','.$Page->listRows)->select();
		
		//获取注册量
		$sql = "SELECT COUNT(*) as num,extension_id as ext from __PREFIX__users where 1=1 group by ext";
		$res = DB::cache(true)->query($sql);
		
		foreach ($res as $val){
			$user[$val['ext']] = $val['num'];
		}
		
		//获取下载量
		$sql = "SELECT COUNT(*) as num,extension_id as Number from __PREFIX__extension where 1=1 group by Number";
		$row = DB::cache(true)->query($sql);
		
		foreach ($row as $val){
			$extension[$val['Number']] = $val['num'];
		}
		
		 /* 格式化数据 */
		foreach ($list AS $key => $value)
		{
			$list[$key]['Number'] = $extension[$value['u_id']];
			$list[$key]['users'] = $user[$value['u_id']]; 
		}
		
		
        $show  = $Page->show();
        $this->assign('pager',$pager);
        $this->assign('show',$show);
        $this->assign('list',$list);
        return $this->fetch();
	}
	
	
	public function download_statisticss(){
		$today = strtotime(date('Y-m-d'));
		$month = strtotime(date('Y-m-01'));
		$user['today'] = Db::name('extension')->cache(true,7200)->where("add_time>$today")->count();//今日新增会员
		$user['month'] = Db::name('extension')->cache(true,7200)->where("add_time>$month")->count();//本月新增会员
		$user['total'] = Db::name('extension')->cache(true,7200)->count();//会员总数
		$this->assign('user',$user);
		$sql = "SELECT COUNT(*) as num,FROM_UNIXTIME(add_time,'%Y-%m-%d') as gap from __PREFIX__extension where add_time>$this->begin and add_time<$this->end group by gap";
		$new = DB::cache(true)->query($sql);//新增会员趋势
		foreach ($new as $val){
			$arr[$val['gap']] = $val['num'];
		}
		
		for($i=$this->begin;$i<=$this->end;$i=$i+24*3600){
			$brr[] = empty($arr[date('Y-m-d',$i)]) ? 0 : $arr[date('Y-m-d',$i)];
			$day[] = date('Y-m-d',$i);
		}		
		$result = array('data'=>$brr,'time'=>$day);
		$this->assign('result',json_encode($result));					
		return $this->fetch();
	}
	
	
	/**
	 * 添加修改推荐人
	 */
	public function downloaduser(){
		
		
		$add = I('POST.');
		
		if(IS_POST){
			if($add['u_id']){
				Db::name('extension_user')->where('u_id',$add['u_id'])->update($add);
			}else{
				$add['add_time'] =time();
				Db::name('extension_user')->insert($add);
			}
			 $this->success('操作成功',Url::build('Report/download'));
		}
		
		if($add['u_id']){
			$Cache = md5($_SERVER['REQUEST_URI']);
			$html =  Cache::get($Cache);  //读取缓存
			if(!empty($html))
				return $html;
			
		}else{
			$this->fetch();
		}
		
		
		
		
		$info = Db::name('extension_user')->where('u_id',$add['u_id'])->find();
		if($info){
			$begin = strtotime(date('Y-m-d',strtotime("-15 day")));
			
			//新增下载 总量
			$sql = "SELECT COUNT(*) as num,FROM_UNIXTIME(add_time,'%Y-%m-%d') as gap from __PREFIX__extension where add_time>$begin and add_time<$this->end and extension_id = ".$add['u_id']." group by gap";
			$new = DB::query($sql);
			
			foreach ($new as $val){
				$arr[$val['gap']]['num'] = $val['num'];
				$arr[$val['gap']]['gap'] = $val['gap'];
			}
			
			//新增Ios 下载
			$sql = "SELECT COUNT(*) as num,FROM_UNIXTIME(add_time,'%Y-%m-%d') as gap from __PREFIX__extension where add_time>$begin and add_time<$this->end and extension_id = ".$add['u_id']." and system = 'Ios' group by gap";
			$Ios = DB::query($sql);
			
			foreach ($Ios as $val){
				$arr[$val['gap']]['Ios'] = $val['num'];
			}
		
			//新增Android 下载
			$sql = "SELECT COUNT(*) as num,FROM_UNIXTIME(add_time,'%Y-%m-%d') as gap from __PREFIX__extension where add_time>$begin and add_time<$this->end and extension_id = ".$add['u_id']." and system = 'Android' group by gap";
			$Android = DB::query($sql);//新增Android 下载
			
			foreach ($Android as $val){
				$arr[$val['gap']]['Android'] = $val['num'];
			}
			
			//新增用户注册
			$sql = "SELECT COUNT(*) as num,FROM_UNIXTIME(reg_time,'%Y-%m-%d') as gap from __PREFIX__users where reg_time>$begin and reg_time<$this->end and extension_id = ".$add['u_id']."  group by gap";
			$Android = DB::query($sql);//新增Android 下载
			
			foreach ($Android as $val){
				$arr[$val['gap']]['users'] = $val['num'];
			}
			
			$info['Ios'] 	 = Db::name('extension')->where('extension_id',$info['u_id'])->where('system','Ios')->count();
			$info['Android'] = Db::name('extension')->where('extension_id',$info['u_id'])->where('system','Android')->count();
			$info['users']   = Db::name('users')->where('extension_id',$info['u_id'])->count();
			$info['number'] = $info['Ios'] + $info['Android'];
			$this->assign('day',$arr);	
		
		}
		
		
		$this->assign('info',$info);
		
		$html = $this->fetch();
        Cache::set($Cache,$html,3600);
        return $html;
	}
	
	    public function welcome(){
		
		$Cache = md5($_SERVER['REQUEST_URI']);
		$html =  Cache::get($Cache);  //读取缓存
		if(!empty($html))
			return $html;
    
    	$today = strtotime(date('Y-m-d', time()));
    	$count['users'] 		= Db::name('users')->count();//会员总数
    	$count['new_users'] 	= Db::name('users')->where("reg_time>$today")->count();//新增会员
		$count['new_extension'] = Db::name('extension')->where("add_time>$today")->count();//新增会员
		$count['extension'] 	= Db::name('extension')->where("1=1")->count();//新增会员
		$count['order'] 		= Db::name('order')->count(); //总订单
		$count['new_order']		= Db::name('order')->where("add_time>$today and pay_status = 1")->count(); //今日订单
		$count['money']			= Db::name('order')->where("pay_status = 1")->sum('order_amount'); // 交易总金额
		$count['new_money']		= Db::name('order')->where("add_time>$today and pay_status = 1")->sum('order_amount'); // 今日交易金额
    	$this->assign('count',$count);
		$html = $this->fetch();
        Cache::set($Cache,$html,3600);
        return $html;
    }
	
	/**
	* 删除推荐人，但是不删除下载量
	*/
		
	public function deldownloaduser(){
		$id = I('id');
		Db::name('extension_user')->where('u_id',$id)->delete();
		 $this->success('操作成功',Url::build('Report/download'));
	}

}