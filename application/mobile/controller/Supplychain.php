<?php
/**
 * Created by PhpStorm.
 * User: yjq
 * Date: 2018/11/29
 * Time: 17:30
 * name:询报价供应链contronller
 */
namespace ylt\mobile\controller;
use ylt\home\logic\SupplierLogic;
use ylt\home\model\UsersLogic;
use think\Db;
use think\Url;
use think\Request;
use think\Session;

class Supplychain extends MobileBase {

	public $user_id = 0;
	public $user = array();

	/*
    * 初始化操作
    */
	public function _initialize()
	{
		parent::_initialize();
		$user = array();
		if (session('?user')) {
			$user = session('user');
			$user = Db::name('users')->where("user_id", $user['user_id'])->find();
			session('user', $user);  //覆盖session 中的 user
			$this->user = $user;
			$this->user_id = $user['user_id'];
			$this->assign('user', $user); //存储用户信息
		}
		if(strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
			$nologin = array(
				'login', 'pop_login', 'do_login', 'logout', 'verify', 'set_pwd', 'finished',
				'verifyHandle', 'send_sms_reg_code', 'find_pwd', 'check_validate_code',
				'forget_pwd', 'check_captcha', 'check_username', 'send_validate_code', 'express',
			);

		}else{
			$nologin = array(
				'login', 'pop_login', 'do_login', 'logout', 'verify', 'set_pwd', 'finished',
				'verifyHandle','reg', 'send_sms_reg_code', 'find_pwd', 'check_validate_code',
				'forget_pwd', 'check_captcha', 'check_username', 'send_validate_code', 'express',
			);

		}

		if (!$this->user_id && !in_array(ACTION_NAME, $nologin)) { //未登录

			// 如果是微信浏览器，则用微信授权登录
			if(strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
				$this->weixin_config = Db::name('wx_user')->find(); //获取微信配置
				$this->assign('wechat_config', $this->weixin_config);
				if(is_array($this->weixin_config) && $this->weixin_config['wait_access'] == 1){
					$wxuser = $this->GetOpenid(); //授权获取openid以及微信用户信息
					// setcookie('subscribe',$wxuser['subscribe']);
					//微信自动登录
					$wxuser['recommend_code'] = input('recommend_code');
					$wxuser['unionid'] = $wxuser['unionid'] ? $wxuser['unionid'] : '';
					$logic = new \ylt\home\model\UsersLogic();
					$data = $logic->thirdLogin($wxuser);

					if($data['status'] == 1){
						session('user',$data['result']);
						setcookie('user_id',$data['result']['user_id'],null,'/');
						$this->user_id = $data['result']['user_id'];
						setcookie('user_name',$data['result']['nickname'],null,'/');
						// 登录后将购物车的商品的 user_id 改为当前登录的id
						Db::name('cart')->where("session_id", $this->session_id)->update(array('user_id'=>$data['result']['user_id']));
					}
				}
			}else{

            	session('login_url',$_SERVER[REQUEST_URI]);
				header("location:" . Url::build('User/login'));
				exit;


			}

		}

		$order_status_coment = array(
			'WAITPAY' => '待付款 ', //订单查询状态 待支付
			'WAITSEND' => '待发货', //订单查询状态 待发货
			'WAITRECEIVE' => '待收货', //订单查询状态 待收货
			'WAITCCOMMENT' => '待评价', //订单查询状态 待评价
		);
		$this->assign('order_status_coment', $order_status_coment);
	}



    /**
     * 询报价---
     */
    public function index(){
		//dump($_SESSION);die();

		if(IS_POST){

			//dump($_POST);die();
		}
        return $this->fetch();
    }

	/**
	 * 询报价---发布采购1
	 */
	public function procurement_one(){
		$supplier_id=$_SESSION["user"]['user_id'];
		//$supplier_id=1;
		$xbjid=$_SESSION['xbjid'];

		$company_name1=Db::name('supplier')->where('user_id='.$supplier_id.' and is_designer=0')->field('supplier_name')->find();
	//	dump($company_name1['supplier_name']);
		$db=Db::name('purchase');

		if($_POST){
			//AjaxReturn($_POST['company']);die();
			if($xbjid){
				$company_name=$_POST['companyName'];
				$add_time=time() ;
				$title=$_POST['inputTitle'];
				$budget=$_POST['money'];
				$sustomized=$_POST['cstmMade'];
				$dead_time = strtotime($_POST['endTime']);
				$expect_time=strtotime($_POST['reciveTime']);
				$description=$_POST['inputDeclare'];
				//存在入驻资料信息
				$sql = [ 'supplier_id' =>$supplier_id,
					'company_name'=>$company_name,
					'add_time'=>$add_time,
					'title'=>$title,
					'budget'=>$budget,
					'sustomized'=>$sustomized,
					'dead_time'=>$dead_time,
					'expect_time'=>$expect_time,
					'description'=>$description,
					'inquiry_time'=>time(),
				];

				$res = $db->where('id='.$xbjid)->update($sql);
				if($res){
					$_SESSION['xbjid']=$xbjid;
					return $_SESSION['xbjid'];
				}else{
					return 0;
				}

			}else{
				$company_name=$_POST['companyName'];
				$add_time=time() ;
				$title=$_POST['inputTitle'];
				$budget=$_POST['money'];
				$sustomized=$_POST['cstmMade'];
				$dead_time = strtotime($_POST['endTime']);
				$expect_time=strtotime($_POST['reciveTime']);
				$description=$_POST['inputDeclare'];
				//存在入驻资料信息
				$sql = [ 'supplier_id' =>$supplier_id,
					'company_name'=>$company_name,
					'add_time'=>$add_time,
					'title'=>$title,
					'budget'=>$budget,
					'sustomized'=>$sustomized,
					'dead_time'=>$dead_time,
					'expect_time'=>$expect_time,
					'description'=>$description,
					'inquiry_time'=>time(),
				];
				$res = $db->insert($sql);
				if($res){
					$_SESSION['xbjid']=$db->getLastInsID();
					return $_SESSION['xbjid'];
				}else{
					return 0;
				}

			}

		}
		$cstmMade='true';
		if($xbjid){
			$procurementlist = $db->where('id='.$xbjid)->find();
			if($procurementlist['sustomized']==1){
				$cstmMade='true';
			}else{
				$cstmMade='false';
			}
			$this->assign('procurementlist', $procurementlist);

			//dump($cstmMade);
		}
        //	dump($_SESSION);die();
        unset($_SESSION['supply_id']);
        unset($_SESSION['supplylist_id']);

        //连表查询查出 purchase id 和 purchase_list purchase_id 关联的数据
        $res=$db->alias('a')->join('purchase_list l','a.id = l.purchase_id')
            ->field('l.id,a.title,a.sustomized,l.goods_img,l.goods_name,l.goods_num,a.budget,a.company_name,a.view,l.p_num,l.t,l.goods_unit')->limit(0,10)->order('a.id desc')
            ->where('status=1')
            ->select();
        // dump($res);
        //$resJson=json_encode($res);
        // die;
        $this->assign('res',$res);
		$this->assign('cstmMade', $cstmMade);
		$this->assign('company_name1', $company_name1);
		return $this->fetch();
	}

	/**
	 * 询报价---发布采购2（礼品详情）
	 */
	public function procurement_two()
	{
		$db=Db::name('purchase_list');
		$xbjid=$_SESSION['xbjid'];
		$cpid=$_SESSION['cpid'];
		if($_POST){
			if($cpid){
				//修改入驻资料信息
				$sql = [
					'purchase_id' =>$xbjid,
					'goods_name'=>$_POST['designation'],
					'goods_norm'=>$_POST['specification'],
					'goods_color'=>$_POST['tinct'],
					'goods_brand'=>$_POST['brand'],
					'goods_unit'=>$_POST['unit'],
					'goods_num'=>$_POST['quantity'],
				];
				$res = $db->where('id='.$cpid)->update($sql);
				if($res){
					$_SESSION['cpid']=$cpid;
					return $_SESSION['cpid'];
				}else{
					return 0;
				}
			}else{
				//存在入驻资料信息
				$sql = [
					'purchase_id' =>$xbjid,
					'goods_name'=>$_POST['designation'],
					'goods_norm'=>$_POST['specification'],
					'goods_color'=>$_POST['tinct'],
					'goods_brand'=>$_POST['brand'],
					'goods_unit'=>$_POST['unit'],
					'goods_num'=>$_POST['quantity'],
					't'=>time(),
				];
				$res = $db->insert($sql);
				if($res){
					$_SESSION['cpid']=$db->getLastInsID();
					return $_SESSION['cpid'];
				}else{
					return 0;
				}
			}
		}

		if($cpid){
			$procurementlist = $db->where('id='.$cpid)->find();
			$this->assign('procurementlist', $procurementlist);
		}


		return $this->fetch();
	}

	/**
	 * 询报价---发布采购3（联系方式）
	 */
	public function procurement_three()
	{
		$xbjid=$_SESSION['xbjid'];
		$cpid=$_SESSION['cpid'];
		// $db=Db::name('purchase');
		if($_POST){
			$sql = [
				'address'=>$_POST['province']."-".$_POST['city']."-".$_POST['area'],
				// 'city' =>$_POST['location'],
				'region'=>$_POST['region'],
				'contacts_name'=>$_POST['contact'],
				'contact'=>$_POST['mobileNo'],
				'tel'=>$_POST['mobileNo'],

			];
        $sql['goods_sn'] = date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
        if(!empty($_POST['province']) && !is_numeric($_POST['province'])){
            $rs=Db::name('region')->where( 'name',"{$_POST['province']}")->find();
            $sql['province']=$rs['id'];
        }
        if(!empty($_POST['city']) && !is_numeric($_POST['city'])){
            $rs=Db::name('region')->where( 'name',"{$_POST['city']}")->find();
            $sql['city']=$rs['id'];
        }
        if(!empty($_POST['area']) && !is_numeric($_POST['area'])){
            $rs=Db::name('region')->where( 'name',"{$_POST['area']}")->find();
            $sql['area']=$rs['id'];
        }
        if(empty($_POST['province']) || empty($_POST['city'])){
            $arr=array('status'=>"-1",'msg'=>"请输入正确地址");
            exit(json_encode($arr));
        }

        if(empty($_POST['region'])){
            $sql['region'] = "全国";
        }

			$res=Db::name('purchase')->where('id='.$xbjid)->update($sql);
			if($res){
		    	return array('status' => 1,'msg' => '发布成功','id'  =>'',);
			}else{
		    	return array('status' => 0,'msg' => '发布失败','id'  =>'',);
			}
		}
		if($xbjid){
			$procurementlist=Db::name('purchase')->where('id='.$xbjid)->find();
			$this->assign('procurementlist', $procurementlist);
		}

		$company_name1=Db::name('users')->where('user_id='.$_SESSION["user"]['user_id'])->field('mobile,nickname')->find();
		//dump($company_name1);//die();//    '.$_SESSION["user"]['user_id'].'
		$this->assign('company_name1', $company_name1);

		return $this->fetch();
	}

	/**
	 * @ 采购单发布成功，清除采购单的id值
	 */
	public function release()
	{
	//	dump($_SESSION);die();
		//采购单发布成功，清除采购单的id值
		unset($_SESSION['cpid']);
		unset($_SESSION['xbjid']);
		return $this->fetch();
	}

	/*
	 * 名企直采- 采购需求列表
	 */

	public function offer_list()
	{
		//	dump($_SESSION);die();
		unset($_SESSION['supply_id']);
		unset($_SESSION['supplylist_id']);

		$db=Db::name('purchase');
		//连表查询查出 purchase id 和 purchase_list purchase_id 关联的数据
		$res=$db->alias('a')->join('purchase_list l','a.id = l.purchase_id')
			->field('l.id,a.title,a.sustomized,l.goods_img,l.goods_name,l.goods_num,a.budget,a.company_name,a.view,l.p_num,l.t,l.goods_unit')->limit(0,10)->order('a.id desc')
			->where('status=1')
			->select();
		// dump($res);
		//$resJson=json_encode($res);
		// die;
		$this->assign('res',$res);
		return $this->fetch();
	}

	/*
	 * 询报价 报价1
	 */

	public function offer_one()
	{
		//	dump($_SESSION);die();
		if($_GET['id']){
			$id=I('get.id');
		}else{
			$id=1;
		}
		$db=Db::name('purchase');
		$res=$db->alias('a')->join('purchase_list l','a.id = l.purchase_id')
			->field('l.id,a.title,a.sustomized,l.goods_img,l.goods_name,l.goods_num,a.budget,a.company_name,a.view,l.p_num,l.t,l.goods_unit,l.goods_norm,l.goods_color,l.goods_brand,a.dead_time,a.expect_time,a.city,a.area,l.goods_remarks,l.purchase_id')->limit(0,5)->order('a.id desc')
			->where('l.id='.$id)
			->find();
		$db->where('id='.$res['purchase_id'])->setInc('view'); //增加查看次数 + 1
		//dump($res);
		//$resJson=json_encode($res);
		$this->assign('res',$res);
		return $this->fetch();
	}


	/*
	 * 询报价 报价2
	 */
	public function offer_two()
	{
		$sessions=$_SESSION;

		if($_GET['id']){
			$id=I('get.id');
		}else{
			$id=1;
		}

		$db=Db::name('purchase_list');
		$res=$db->where('purchase_id='.$id)->find(); //采购商品单

		//$resJson=json_encode($res);
		$cgs=Db::name('purchase')->where('id='.$res['purchase_id'])->find(); //采购单
//        dump($cgs);
//        die();
		if($_POST){
			$sql1=[
				'purchase_id' => $cgs['id'],  //采购purchase_id = 采购单 purchase的 id
				'supplier_id' => $_SESSION['user']['user_id'],
				'title' => $cgs['title'],
				'content' => $cgs['description'],
				't' => time(),
				'state' => 0,
			];
//            dump($sql1);
//            die();

			if($_SESSION['supplylist_id'] && $_SESSION['supply_id'] ){ //存在就更新
				$add1=Db::name('supply')->where('id='.$_SESSION['supply_id'])->update($sql1);
				if($add1){
					$sql2=[
						'supply_id' => $_SESSION['supply_id'], //单独的订单supply_id = 父id
						'purlist_id' => $_POST['id'],
						'good_num' => $_POST['num'],
						'goods_tprice' => $_POST['price'],
						'goods_sprice' => $_POST['totals'],
						'goods_freight' => $_POST['fee'],
						'goods_duration' => $_POST['day'],
						't' => time(),
					];
					$add2=Db::name('supply_list')->where('id='.$_SESSION['supplylist_id'])->update($sql2);
					if($add2){
						return $_SESSION['supplylist_id'];
					}
				}
			}else{	//不存在就添加
				$add1=Db::name('supply')->insert($sql1);
				if($add1){
					$_SESSION['supply_id']=Db::name('supply')->getLastInsID(); //利用session存储id
					$sql2=[
						'supply_id' => $_SESSION['supply_id'], //单独的订单supply_id = 父id
						'purlist_id' => $_POST['id'],
						'good_num' => $_POST['num'],
						'goods_tprice' => $_POST['price'],
						'goods_sprice' => $_POST['totals'],
						'goods_freight' => $_POST['fee'],
						'goods_duration' => $_POST['day'],
						't' => time(),
					];
//                    dump($sql2);
//                    die();
					$add2=Db::name('supply_list')->insert($sql2);
					if($add2){
						$_SESSION['supplylist_id']=Db::name('supply_list')->getLastInsID();
						//添加报价次数
						Db::name('purchase_list')->where('id='.$res['id'])->setInc('p_num');
						Db::name('purchase')->where('id='.$_SESSION['supply_id'])->setInc('offer_num');

						return $_SESSION['supplylist_id'];
					}
				}
			}

		}
//        dump(11);
//        die();
		//查询已经存在的 supply_list 报价单
		if($_SESSION['supplylist_id']){
			$listSupp=Db::name('supply_list')->where('id='.$_SESSION['supplylist_id'])->find();
			$this->assign('listSupp',$listSupp);
			//dump($listSupp);
		}
		$this->assign('res',$res);
		return $this->fetch();
	}


	/*
	 * 询报价 报价3
	 */
	public function offer_three()
	{
		//dump($_SESSION);die();
		if($_SESSION['supply_id']){
			$listSupp=Db::name('supply')->where('id='.$_SESSION['supply_id'])->find();
			$this->assign('listSupp',$listSupp);
			//dump($listSupp);
		}
		//添加报价
		if($_POST){
			$sql=[
				'company_name' => $_POST['firm'],
				'user' => $_POST['contact'],
				'phone' => $_POST['mobileNo'],
				't' => time(),
			];
			$updates=Db::name('supply')->where('id='.$_SESSION['supply_id'])->update($sql);
			if($updates){
				return 1;
			}
		}

		$listuser=Db::name('supplier')->where('user_id='.$_SESSION["user"]['user_id'])->field('company_name,contacts_name,contacts_phone')->find();
		if(!$listuser){
			$listuser=Db::name('users')->where('user_id='.$_SESSION["user"]['user_id'])->field('nickname as contacts_name,mobile as contacts_phone')->find();
		}
		 // dump($listuser);
		$this->assign('listuser',$listuser);

		return $this->fetch();
	}
	public function offer_four()
	{
		//dump($_SESSION);die();
		if($_SESSION['supplylist_id']){  //判断查询条件来源，再做查询
			$supplylist_id=$_SESSION['supplylist_id'];
		}else if($_GET['id']){
			$supplylist_id=$_GET['id'];
		}else{
			$supplylist_id=60;
		}
	//	dump($supplylist_id);
		$db=Db::name('supply');  //连表查询出相对应得数据
		$res=$db->alias('o')->join('supply_list t','o.id = t.supply_id')
			->field('t.purlist_id,t.goods_duration,t.good_num,t.goods_freight,t.goods_tprice,t.goods_sprice,o.company_name,o.user,o.phone')
			->limit(0,5)->order('o.id desc')
			->where('t.id='.$supplylist_id)
			->find();

		//12.15查询 采购信息  太晚了 回去休息了 12.14日宣
		$shoplist=Db::name('purchase_list')->where('id='.$res['purlist_id'])->find();  //查询报价商品对应的采购商品
		$orderlist=Db::name('purchase')->where('id='.$shoplist['purchase_id'])->find(); //查询报价商品对应的采购订单

		$this->assign('shoplist',$shoplist);
		$this->assign('orderlist',$orderlist);
		$this->assign('res',$res);
		return $this->fetch();
	}

	/*
	 *我的采购列表
	 */
	public function myshop_list()
	{

		$id = $_SESSION['user']['user_id'];
		$orderlist=Db::name('purchase')->where('supplier_id='.$id)->select(); //查询对应的采购单
		//dump($orderlist);

		$this->assign('orderlist',$orderlist);
		return $this->fetch();
	}


	public function myshop_one()
	{
		$id = I('get.id');
		$orderlist=Db::name('purchase_list')->where('purchase_id='.$id)->find(); //查询对应的采购商品
		$buylist=Db::name('purchase')->where('id='.$id)->find(); //查询对应的采购单
		//dump($orderlist);
		//dump($buylist);
		if($buylist['sustomized']=0){
			$buylist['sustomized']='否';
		}else{
			$buylist['sustomized']='是';
		}
		$this->assign('orderlist',$orderlist);
		$this->assign('buylist',$buylist);
		return $this->fetch();
	}

	public function myshop_two()
	{

		$id = I('get.purchase_id');
	//	$id = 141;
		//$orderlist=Db::name('purchase_list')->where('purchase_id='.$id)->select(); //查询对应的采购单id
		$buylist=Db::name('supply')->where('purchase_id='.$id)->select(); //查询对应的报价单id

		//这是一个麻烦的查询语句。
		$orderlist=array();
		foreach($buylist as $k=> $v){
			$orderlist[]= Db::name('supply_list')->alias('a')					//每一个报价标题只查了一个报价物品，本应该查所有的
				->join('purchase_list b','a.purlist_id = b.id')
				->field('a.goods_sprice,a.t,b.goods_name,b.goods_num,b.goods_unit,a.id')
				->where('a.supply_id='.$v['id'])
				->find(); //连表查询查出对应的数据
			foreach($orderlist as $a=>$b){
				$orderlist[$a]['company_name']=$v['company_name']; //在报价单里面添加公司名称
			}
		}
		//dump($orderlist);
		$this->assign('orderlist',$orderlist);
		return $this->fetch();
	}

	public function myshop_three()
	{
		$id = I('get.id');
		$supply_list=Db::name('supply_list')->where('id='.$id)->find(); 		//查询对应的报价单
		$orderlist=Db::name('purchase_list')->where('id='.$supply_list['purlist_id'])->find(); //查询对应的采购单
		$buylist=Db::name('supply')->where('id='.$supply_list['supply_id'])->find(); 		//查询对应的报价标题

		$this->assign('supply_list',$supply_list);
		$this->assign('orderlist',$orderlist);
		$this->assign('buylist',$buylist);
		return $this->fetch();
	}

	/*
	 * 我的报价列表
	 */

	public function my_offer_list()
	{
		//dump($_SESSION);
		$id=$_SESSION['user']['user_id'];
		$res1=DB::name('supply')->where('supplier_id='.$id)->select(); //查出登录用户所有的报价标题
		//	dump($res1);

		if($res1){
//查出所有报价单对应的报价商品
			foreach($res1 as $ak1=>$av1){
				$list1[]=Db::name('supply_list')->where('supply_id='.$av1['id'])->select();
			}
			//	dump($list1);

			//去除没有报价 商品的 报价单
			foreach ($list1 as $ak11 => $av11) {
				if (empty($av11)) {
					continue;
				}
				$list2[] = $av11;
			}
			//dump($list);

			//把报价商品的三维数组转变为二维数组
			foreach ($list2 as $ak111 => $av111) {
				$supply_list[] = $av111[0];
			}
			//	dump($supply_list);

			//需要查询的数据表   三张表 连表查询
			$join=[
				['supply b','a.supply_id=b.id'],
				['purchase_list c','a.purlist_id=c.id'],
				['purchase d','c.purchase_id=d.id'],
			];

			//列出需要查询的字段
			$field= 'a.id,
				d.title,
				d.company_name,
				d.tel,
				d.view,
				d.offer_num,
				d.dead_time,
				d.budget,
				c.goods_img,
				c.goods_name,
				c.goods_num,
				c.goods_unit';
			//根据报价商品的条数查出来
			foreach($supply_list as $klist => $vlist){
				$list4[] = Db::name('supply_list')->alias('a')
					->join($join)
					->field($field)
					->where('a.id='.$vlist['id'])
					->find();
			}
			//	dump($list4);
			//去除没有报价 商品的 报价单
			foreach ($list4 as $ak14 => $av14) {
				if (empty($av14)) {
					continue;
				}
				$list[] = $av14;
			}
			//dump($list);
		}

		$this->assign('list',$list);
		return $this->fetch();
	}

	/*
	 * 我的报价详情
	 */

	public function my_offer_one()
	{
		if($_GET['id']){

			//dump($_SESSION);
			$id=$_GET['id'];
			$join=[
				['supply_list d','d.supply_id=a.id'],
				['purchase_list c','d.purlist_id=c.id'],
				['purchase b','c.purchase_id=b.id'],
			]; //需要查询的数据表   四张表 连表查询
			$field= 'b.company_name as company_name1,b.budget,b.sustomized,b.dead_time,b.expect_time,
			b.city,b.area,b.contacts_name,b.tel,b.add_time,b.description,c.goods_name,c.goods_norm,
			c.goods_color,c.goods_brand,c.goods_unit,c.goods_num,d.goods_duration,d.good_num,
			d.goods_freight,d.goods_tprice,d.goods_sprice,a.company_name as company_name2,a.user,a.phone';//需要查询的字段
			$list = Db::name('supply')->alias('a')
				->join($join)
				->field($field)
				->where('d.id='.$id)
				->find();

			//是否定制
			if($list['sustomized']=1){
				$list['sustomized']='是';
			}else{
				$list['sustomized']='否';
				}
			//dump($list);

		}
		$this->assign('list',$list);
		return $this->fetch();
	}




















}