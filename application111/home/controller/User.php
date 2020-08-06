<?php
/**
 * Created by PhpStorm.
 * User: lijiayi
 * Date: 2017/3/27
 * Time: 10:30
 */
namespace ylt\home\controller; 
use ylt\home\logic\UsersLogic;
use ylt\home\model\CartLogic;
use think\Controller;
use think\Url;
use think\Page;
use think\Config;
use think\Verify;
use think\Db;
use think\Request;
use think\Cache;
class User extends Base{
	
	
	public $user_id = 0;
	public $user = array();
	/*
	 * 处理登录后需要的参数
	 */
    public function _initialize() {      
        parent::_initialize();
        if(session('?user'))
        {
        	$user = session('user');
            $user = Db::name('users')->where("user_id", $user['user_id'])->Cache(true,600)->find();
            session('user',$user);  //覆盖session 中的 user               
        	$this->user = $user;
        	$this->user_id = $user['user_id'];
        	$this->assign('user',$user); //存储用户信息
        	$this->assign('user_id',$this->user_id);
        }else{
            session('login_url',$_SERVER[REQUEST_URI]);
        	$nologin = array(
        			'user_login','logout','login','register','verifyHandle',
					'verify','forget_pwd','check_captcha','check_username','authentication',
					'edit_pwd','finished','linkPhone','leaguerRegister','ac_up_pw'
        	);
        	if(!in_array(ACTION_NAME,$nologin)){
                $this->redirect('Home/User/user_login');
        		exit;
        	}
        }
        //用户中心面包屑导航
        $navigate_user = navigate_user();
        $this->assign('navigate_user',$navigate_user);        
    }
	
	
    /*
     * 用户中心首页
     */
    public function index(){
        $logic = new UsersLogic();
        $user = $logic->get_info($this->user_id);
        $user = $user['result'];
        $this->assign('user',$user);
        return $this->fetch();
    }


    /**
     *  用户登录
     */
    public function user_login(){
	
        if($this->user_id > 0){
            $this->redirect('Home/User/index');
        }
        //是否有上级地址
        $referurl = session('login_url') ? session('login_url') : Url::build("User/index");
        // $referurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : Url::build("Home/User/index");
        $this->assign('referurl',$referurl);
        return $this->fetch();
    }
	
	/**
    *   登录处理
    */
    public function login(){
        $mobile = trim(I('post.mobile'));
        $mobile2 = trim(I('post.mobile2'));
        $password = trim(I('post.password'));
        $verify_code = I('post.verify_code');
        $mobile_code = I('post.mobile_code');
        if ($mobile2 and $mobile_code) {
            //验证码
            $logic = new UsersLogic();
            $res = $logic->check_validate_code($mobile_code, $mobile2  , 'mobile');
            if ($res['status'] != 1){
                exit(json_encode($res));
            }
            $user = Db::name('users')->where("mobile",$mobile2)->find();
            if (!$user) {
                exit(json_encode(array('status'=>-1,'msg'=>'账号不存在,请先注册')));
            }
            if ($user['password'] == '') {
                exit(json_encode(array('status'=>2,'msg'=>'需要设置密码')));
            }
            if($res['status'] == 1){
                $res['url'] =  urldecode(I('post.referurl')); //登录的上一页
                session('user',$user);
                setcookie('user_id',$user['user_id'],null,'/');
                $nickname = empty($user['nickname']) ? $mobile : $user['nickname'];
            }
        }else{
            $verify = new Verify();
            if (!$verify->check($verify_code,'user_login'))
            {
                 $res = array('status'=>0,'msg'=>'验证码错误');
                 exit(json_encode($res));
            }
            
            $logic = new UsersLogic();
            $res = $logic->user_login($mobile,$password);
            if($res['status'] == 1){
                $res['url'] =  urldecode(I('post.referurl')); //登录的上一页
                session('user',$res['result']);
                setcookie('user_id',$res['result']['user_id'],null,'/');
                $nickname = empty($res['result']['nickname']) ? $mobile : $res['result']['nickname'];
            }
        }
        setcookie('user_name',urlencode($nickname),null,'/');
        setcookie('cn',0,time()-3600,'/');
        $cartLogic = new CartLogic();
        $cartLogic->login_cart_handle($this->session_id,$res['result']['user_id']);  //用户登录后 需要对购物车 一些操作
        Db::name('users')->where("user_id",$res['result']['user_id'])->update(['activate'=>1]); //登录激活
        exit(json_encode($res));
    }
    
    /*激活账户设置密码*/
    public function ac_up_pw(){
        $mobile = I('post.mobile');
        $password = encrypt(trim(I('post.ac_password')));
        if ($password) {
            $re = Db::name('users')->where("mobile",$mobile)->update(['password'=>$password]);
        }
        if ($re) {
            $user = Db::name('users')->where("mobile",$mobile)->find();
            Db::name('users')->where("user_id",$user['user_id'])->update(['activate'=>1]); //登录激活
            session('user',$user);
            setcookie('user_id',$user['user_id'],null,'/');
            $nickname = empty($user['nickname']) ? $mobile : $user['nickname'];
            setcookie('user_name',urlencode($nickname),null,'/');
            setcookie('cn',0,time()-3600,'/');
            exit(json_encode(array('status'=>1,'msg'=>'密码设置成功！')));
        } 
        exit(json_encode(array('status'=>-1,'msg'=>'密码设置失败！')));
    }
	
	
	/**
	* 用户注册
	*/
	public function register(){
		if($this->user_id > 0){
            $this->redirect('Home/User/index');
        }
		if(IS_POST){
			
            $mobile = trim(I('post.mobile',''));
            $password = trim(I('post.password',''));
            $password2 = trim(I('post.password2',''));
            $code = trim(I('post.code',''));
            $session_id = session_id();
			$verify_code = trim(I('post.verify_code'));
			$recommend_code = I('post.recommend_code');
		
		// $verify = new Verify();
         // if (!$verify->check($verify_code,'user_register'))
         // {  
         //     $this->error('验证码错误');
         // }
		
		$logic = new UsersLogic();
        $res = $logic->check_validate_code($code, $mobile  , 'mobile');
		
		if ($res['status'] != 1){
			 $this->error($res['msg']);
		}
		$data = $logic->reg($mobile,$password,$password2,$recommend_code);
            if($data['status'] != 1){
                $this->error($data['msg']);
            }
		$this->success($data['msg'],Url::build('Home/User/index'));
        exit;
		}
		return $this->fetch();
		
	}
	
	
	/**
	 * 用户退出
	 */
	 
	 public function logout(){
		 
		setcookie('user_name','',time()-3600,'/');
    	setcookie('cn','',time()-3600,'/');
    	setcookie('user_id','',time()-3600,'/');
		session_unset();
        session_destroy();

        $this->redirect('Home/Index/index');
        exit;	 
		 
	 }
	 
	 
    /*
    * 手机验证
    */
    public function mobile_validate(){
        $userLogic = new UsersLogic();
        $user_info = $userLogic->get_info($this->user_id); //获取用户信息
        $user_info = $user_info['result'];
        $config = F('sms','',TEMP_PATH);
        $sms_time_out = 90;
        $step = I('get.step',1);
        if(IS_POST){
            $mobile = I('post.mobile');
            $old_mobile = I('post.old_mobile','');
            $code = I('post.code');
          
            //检查原手机是否正确
            if($user_info['mobile_validated'] == 1 && $old_mobile != $user_info['mobile'])
                $this->error('原手机号码错误');
            //验证手机和验证码
			 $res = $userLogic->check_validate_code($code, $mobile  , 'mobile');
		

            if($res['status'] == 1){
				
				$password = input('password');
				$password2 = input('password2');
				
				if($password != $password2)
					$this->error('两次密码不一致');

                if(!$userLogic->update_email_mobile($mobile,$this->user_id,2,$password))
                    $this->error('手机已存在');
				
				
                $this->success('绑定成功',Url::build('Home/User/index'));
                exit;
            }
            $this->error($res['msg']);
        }
        $this->assign('user_info',$user_info);
        $this->assign('time',$sms_time_out);
        $this->assign('step',$step);
        return $this->fetch();
    }
	 
	 
	 /*
     * 订单列表
     */
    public function order_list(){
        $where = ' user_id=:user_id and is_parent = 0 ';
        $bind['user_id'] = $this->user_id;
        //条件搜索
        if(I('get.type')){
            $where .= config(strtoupper(I('get.type')));
        }
        // 搜索订单 根据商品名称 或者 订单编号
        $search_key = trim(I('search_key'));
        if($search_key)
        {
          $where .= " and (order_sn like :search_key1 or order_id in (select order_id from `".config('database.prefix')."order_goods` where goods_name like :search_key2) ) ";
           $bind['search_key1'] = "%$search_key%";
           $bind['search_key2'] = "%$search_key%";
        }
        
        $count = Db::name('order')->where($where)->bind($bind)->count();
        $Page = new Page($count,10);
        $show = $Page->show();
        $order_str = "order_id DESC";
        $order_list = Db::name('order')->order($order_str)->where($where)->bind($bind)->limit($Page->firstRow.','.$Page->listRows)->select();

        //获取订单商品
        $model = new UsersLogic();
        foreach($order_list as $k=>$v)
        {
            $order_list[$k] = set_btn_order_status($v);  // 添加属性  包括按钮显示属性 和 订单状态显示属性
            //$order_list[$k]['total_fee'] = $v['goods_amount'] + $v['shipping_fee'] - $v['integral_money'] -$v['bonus'] - $v['discount']; //订单总额
            $data = $model->get_order_goods($v['order_id']);
            $order_list[$k]['goods_list'] = $data['result'];
            if($order_list[$k]['order_prom_type'] == 4){
                $pre_sell_item =  Db::name('goods_activity')->where(array('act_id'=>$order_list[$k]['order_prom_id']))->find();
                $pre_sell_item = array_merge($pre_sell_item,unserialize($pre_sell_item['ext_info']));
                $order_list[$k]['pre_sell_is_finished'] = $pre_sell_item['is_finished'];
                $order_list[$k]['pre_sell_retainage_start'] = $pre_sell_item['retainage_start'];
                $order_list[$k]['pre_sell_retainage_end'] = $pre_sell_item['retainage_end'];
            }else{
                $order_list[$k]['pre_sell_is_finished'] = -1;//没有参与预售的订单
            }
        }
        $this->assign('order_status',config('ORDER_STATUS'));
        $this->assign('shipping_status',config('SHIPPING_STATUS'));
        $this->assign('pay_status',config('PAY_STATUS'));
        $this->assign('page',$show);
        $this->assign('lists',$order_list);
        $this->assign('active','order_list');
        $this->assign('active_status',I('get.type'));
        return $this->fetch();
    }
	

    /*
     * 订单详情
     */
    public function order_detail(){
        $id = I('get.id/d');

        $map['order_id'] = $id;
        $map['user_id'] = $this->user_id;
        $order_info = Db::name('order')->where($map)->find();
        $order_info = set_btn_order_status($order_info);  // 添加属性  包括按钮显示属性 和 订单状态显示属性
        
        if(!$order_info){
            $this->error('没有获取到订单信息');
            exit;
        }
        //获取订单商品
        $model = new UsersLogic();
      	if($order_info['is_parent']==1){
           $row = Db::name('order')->where("parent_id={$order_info['order_id']}")->select();
              foreach ($row AS $key => $value) {                         
                $goods_array[$key] = $model->get_order_goods($value['order_id']);  
              	}
          	  foreach($goods_array AS $k => $v){
                foreach($v['result'] AS $ke => $va){
                	$result[]=$va;
                }
              }
          $data['status']=1;
          $data['msg']="";
          $data['result']=$result;
          // $exp=var_export($data,true);
          //var_dump($exp);
          //exit();
        	
        }else{
        	
        	$data = $model->get_order_goods($order_info['order_id']);        
        }        
        //$data = $model->get_order_goods($order_info['order_id']);
        $order_info['goods_list'] = $data['result'];
        if($order_info['order_prom_type'] == 4){
            $pre_sell_item =  Db::name('goods_activity')->where(array('act_id'=>$order_info['order_prom_id']))->find();
            $pre_sell_item = array_merge($pre_sell_item,unserialize($pre_sell_item['ext_info']));
            $order_info['pre_sell_is_finished'] = $pre_sell_item['is_finished'];
            $order_info['pre_sell_retainage_start'] = $pre_sell_item['retainage_start'];
            $order_info['pre_sell_retainage_end'] = $pre_sell_item['retainage_end'];
            $order_info['pre_sell_deliver_goods'] = $pre_sell_item['deliver_goods'];
        }else{
            $order_info['pre_sell_is_finished'] = -1;//没有参与预售的订单
        }
        //获取订单进度条
        // $sql = "SELECT action_id,log_time,status_desc,order_status FROM ((SELECT * FROM __PREFIX__order_action WHERE order_id = :id AND status_desc <>'' ORDER BY action_id) AS a) GROUP BY status_desc ORDER BY action_id";
        // $bind['id'] = $id;
        // $items = DB::query($sql,$bind);
        $region_list = get_region_list();
        if (empty($region_list[$order_info['province']]['name']) && empty($region_list[$order_info['city']]['name'])) {
            $order_info['address2'] = $order_info['province'].','.$order_info['city'].','.$order_info['district'].','.$order_info['address'];      
        }else{
            $order_info['address2'] =  $region_list[$order_info['province']]['name'] .','. $region_list[$order_info['city']]['name'] .','. $region_list[$order_info['district']]['name'];
            $order_info['address2'] = $order_info['address2'].$order_info['address'];      
        }   
        $invoice_no = Db::name('ShippingOrder')->where("order_id", $id)->select();
		
		if($invoice_no){
			foreach ($invoice_no as $key => $value){
				$invoice_no[$key]['exp'] = json_decode($value['logistics_information'],true); 
			}
		}
		$order_info['exp'] = $invoice_no;
        $this->assign('order_status',config('ORDER_STATUS'));
        $this->assign('shipping_status',config('SHIPPING_STATUS'));
        $this->assign('pay_status',config('PAY_STATUS'));
        $this->assign('order_info',$order_info);
        $this->assign('active','order_list');
        return $this->fetch();
    }
	

    /*
     * 取消订单
     */
    public function cancel_order(){
        $id = I('get.id/d');
        //检查是否有积分，余额支付
        $logic = new UsersLogic();
        $data = $logic->cancel_order($this->user_id,$id);
        if($data['status'] < 0){
            $this->error($data['msg']);
        }
        $this->success($data['msg']);
    }
	
	
	/**
	 * 确认收货
	 */
	public function order_confirm(){
        $id = I('get.id/d',0);
                                  
        $data = confirm_order($id,$this->user_id);
        if(!$data['status'])
            $this->error($data['msg']);
	else	
	   $this->success($data['msg']);
    }
    /**
     * 申请退货
     */
    public function back_order()
    {
        $order_id = trim(input('order_id/d',0));
        $order_sn = trim(input('order_sn',0));
        $goods_id = trim(input('goods_id/d',0));
	    $spec_key = trim(input('spec_key'));
		$type	  = trim(input('type',1));
		$send_number = intval(input('send_number'));
		$reason	  = input('reason');
		$imgs 	  = input('imgs');
		
        $order = Db::name('order')->where("order_id", $order_id)->where('user_id', $this->user_id)->find();
        if(!$order['order_id'])
        {
            $this->error('对不起！您没权限针对该商品发起退款/退货及维修');
            exit;
        }         
     
		$return_order =Db::name('back_order')->alias('o')->join('back_goods g','o.id = g.back_id','left')->where(['o.order_id'=>$order_id,'g.goods_id'=>$goods_id,'g.spec_key'=>$spec_key])->find();
		
        if(!empty($return_order))
        {
			$this->redirect('Home/User/back_goods_info',array('id'=>$return_order['id']));

            exit;
        } 
		$goods = Db::name('order_goods')->where(['order_id'=>$order_id,'goods_id'=>$goods_id,'spec_key'=>$spec_key])->find();	
        //生成退款、退货记录	
        if(IS_POST)
        {
            $logic = new UsersLogic();
            $return = $logic->back_order_s($order_id,$goods_id,$spec_key,1,$send_number,$reason,$imgs,$this->user_id);
            if ($return == 1) {
                $this->error('退换货数量上限');
            }elseif($return == "ok"){
                $this->success('申请成功,客服将会及时处理订单',Url::build('Home/User/order_list'));
            }
        }
               

        $this->assign('goods',$goods);
        $this->assign('order',$order);
        $this->assign('goods_id',$goods_id);
		$this->assign('spec_key',$spec_key);
        return $this->fetch();
    }

     /**
     * [return_goods_cancel 取消退换货]
     * @return [type] [description]
     */
    public function return_goods_cancel(){
        $data = I('get.');
        $a = Db::name('back_order')->where('id',$data['id'])->delete();
        $b = Db::name('order_goods')->where(['order_id'=>$data['order_id'],'goods_id'=>$data['goods_id']])->update(['is_service' => 0]);
        if ($a && $b) {
            $this->success("取消成功。");
        // return array('status' =>1,'msg'=>'取消售后成功。');
        }else{
            $this->error("取消失败，订单商品不存在。");
        }
    }
	 
	 /*
     * 个人信息
     */
    public function info(){
        $userLogic = new UsersLogic();
        $user_info = $userLogic->get_info($this->user_id); // 获取用户信息
        $user_info = $user_info['result'];
        if(IS_POST){
            I('post.nickname') ? $post['nickname'] = I('post.nickname') : false; //昵称
            I('post.qq') ? $post['qq'] = I('post.qq') : false;  //QQ号码
            I('post.head_pic') ? $post['head_pic'] = I('post.head_pic') : false; //头像地址
            I('post.sex') ? $post['sex'] = I('post.sex') : $post['sex'] = 0;  // 性别
            I('post.birthday') ? $post['birthday'] = strtotime(I('post.birthday')) : false;  // 生日
            I('post.province') ? $post['province'] = I('post.province') : false;  //省份
            I('post.city') ? $post['city'] = I('post.city') : false;  // 城市
            I('post.district') ? $post['district'] = I('post.district') : false;  //地区
            if(!$userLogic->update_info($this->user_id,$post))
                $this->error("保存失败");
            $this->success("操作成功");
            exit;
        }
        //  获取省份
        $province = Db::name('region')->where(array('parent_id'=>0,'level'=>1))->cache(true)->select();
        //  获取订单城市
        $city =  Db::name('region')->where(array('parent_id'=>$user_info['province'],'level'=>2))->select();
        //获取订单地区
        $area =  Db::name('region')->where(array('parent_id'=>$user_info['city'],'level'=>3))->select();

        $this->assign('province',$province);
        $this->assign('city',$city);
        $this->assign('area',$area);
        $this->assign('user',$user_info);
        $this->assign('sex',config('SEX'));
        $this->assign('active','info');
        return $this->fetch();
    }
	
	
	  /*
     * 用户地址列表
     */
    public function address_list(){
        $address_lists = get_user_address_list($this->user_id);
        $region_list = get_region_list();
        foreach ($address_lists as $key => $value) {
            if (!empty($region_list[$value['province']]['name']) && !empty($region_list[$value['city']]['name'])) {
                $value['province'] =  $region_list[$value['province']]['name'];
                $value['city']     =  $region_list[$value['city']]['name'];
                $value['district'] =  $region_list[$value['district']]['name'];
            } 
            $address_listss[] = $value;
        }
        $this->assign('lists',$address_listss);
        $this->assign('active','address_list');

        return $this->fetch();
    }
	
	
	/*
     * 添加地址
     */
    public function add_address(){
        header("Content-type:text/html;charset=utf-8");
        if(IS_POST){
            $logic = new UsersLogic();
            $data = $logic->add_address($this->user_id,0,I('post.'));
            if($data['status'] != 1)
                exit('<script>alert("'.$data['msg'].'");history.go(-1);</script>');
            $call_back = $_REQUEST['call_back'];
            echo "<script>parent.{$call_back}('success');</script>";
            exit(); // 成功 回调closeWindow方法 并返回新增的id
        }
        $p = Db::name('region')->where(array('parent_id'=>0,'level'=> 1))->select();
        $this->assign('province',$p);
        return $this->fetch('edit_address');

    }
	

    /*
     * 地址编辑
     */
    public function edit_address(){
        header("Content-type:text/html;charset=utf-8");
        $id = I('get.id/d');
        $address = Db::name('user_address')->where(array('address_id'=>$id,'user_id'=> $this->user_id))->find();
        if(IS_POST){
            $logic = new UsersLogic();
            $data = $logic->add_address($this->user_id,$id,I('post.'));
            if($data['status'] != 1)
                exit('<script>alert("'.$data['msg'].'");history.go(-1);</script>');

            $call_back = $_REQUEST['call_back'];
            echo "<script>parent.{$call_back}('success');</script>";
            exit(); // 成功 回调closeWindow方法 并返回新增的id
        }
        //获取省份
        $p = Db::name('region')->where(array('parent_id'=>0,'level'=> 1))->cache(true)->select();
        $c = Db::name('region')->where(array('parent_id'=>$address['province'],'level'=> 2))->select();
        $d = Db::name('region')->where(array('parent_id'=>$address['city'],'level'=> 3))->select();
        if($address['twon']){
        	$e = Db::name('region')->where(array('parent_id'=>$address['district'],'level'=>4))->select();
        	$this->assign('twon',$e);
        }

        $this->assign('province',$p);
        $this->assign('city',$c);
        $this->assign('district',$d);
        $this->assign('address',$address);
        return $this->fetch();
    }
	
	
	/*
     * 设置默认收货地址
     */
    public function set_default(){
        $id = I('get.id/d');
        Db::name('user_address')->where(array('user_id'=>$this->user_id))->update(array('is_default'=>0));
        $row = Db::name('user_address')->where(array('user_id'=>$this->user_id,'address_id'=>$id))->update(array('is_default'=>1));
        if(!$row)
            $this->error('操作失败');
        $this->success("操作成功");
    }
	
    
    /*
     * 地址删除
     */
    public function del_address(){
        $id = I('get.id/d');
        
        $address = Db::name('user_address')->where("address_id", $id)->find();
        $row = Db::name('user_address')->where(array('user_id'=>$this->user_id,'address_id'=>$id))->delete();
        // 如果删除的是默认收货地址 则要把第一个地址设置为默认收货地址
        if($address['is_default'] == 1)
        {
            $address2 = Db::name('user_address')->where("user_id", $this->user_id)->find();
            $address2 && Db::name('user_address')->where("address_id", $address2['address_id'])->update(array('is_default'=>1));
        }        
        if(!$row)
            $this->error('操作失败',Url::build('User/address_list'));
        else
            $this->redirect('User/address_list');
    }
	
	
	/*
     * 密码修改
     */
    public function password(){
        //检查是否第三方登录用户
        $logic = new UsersLogic();
        $data = $logic->get_info($this->user_id);
        $user = $data['result'];
        if(IS_POST){
            $data = $logic->password($this->user_id,I('post.old_password'),I('post.new_password'),I('post.confirm_password')); // 获取用户信息
            if($data['status'] == -1)
                $this->error($data['msg']);
            $this->success($data['msg']);
            exit;
        }
        return $this->fetch();
    }
	
	
	/*
     * 评论晒单
     */
    public function comment(){
        $user_id = $this->user_id;
        $status = I('get.status',-1);
        $logic = new UsersLogic();
        $data = $logic->get_comment($user_id,$status); //获取评论列表
        $this->assign('page',$data['show']);// 赋值分页输出
        $this->assign('comment_list',$data['result']);
        $this->assign('active','comment');
        return $this->fetch();
    }
	
	
	 /*
     *添加评论
     */
    public function add_comment()
    {          
            $user_info = session('user');
            $comment_img = serialize(I('comment_img/a')); // 上传的图片文件
            $add['goods_id'] = I('goods_id/d');
            $add['email'] = $user_info['email'];
            $add['username'] = $user_info['nickname'];
            $add['order_id'] = I('order_id/d');
            $add['service_rank'] = I('service_rank');
            $add['deliver_rank'] = I('deliver_rank');
            $add['goods_rank'] = I('goods_rank');
            $add['content'] = I('content');
            $add['img'] = $comment_img;
            $add['add_time'] = time();
            $add['ip_address'] = $_SERVER['REMOTE_ADDR'];
            $add['user_id'] = $this->user_id;
			$add['supplier_id'] = I('supplier_id');
            $logic = new UsersLogic();
            //添加评论
            $row = $logic->add_comment($add);            
            exit(json_encode($row));        
    }
	
	
	/*
	 * 忘记密码
	 */
	 public function forget_pwd(){
    	if($this->user_id > 0){
            $this->redirect('Home/User/index');
    	}
    	if(IS_POST){
    		$logic = new UsersLogic();
    		$username = I('post.username');
    		$code = I('post.code');
    		$new_password = I('post.new_password');
    		$confirm_password = I('post.confirm_password');
    		$pass = false;
    	
    		//检查是否手机找回
    		if(check_mobile($username)){
    			if(!$user = get_user_info($username,2))
    				$this->error('账号不存在');
    			$check_code = $logic->check_validate_code($username,$code,$this->session_id);
    			if($check_code['status'] != 1)
    				$this->error($check_code['msg']);
    			$pass = true;
    		}
    		//检查是否邮箱
    		if(check_email($username)){
    			if(!$user = get_user_info($username,1))
    				$this->error('账号不存在');
    			$check = session('forget_code');
    			if(empty($check))
    				$this->error('非法操作');
    			if(!$username || !$code || $check['email'] != $username || $check['code'] != $code)
    				$this->error('邮箱验证码不匹配');
    			$pass = true;
    		}
    		if($user['user_id'] > 0 && $pass)
    			$data = $logic->password($user['user_id'],'',$new_password,$confirm_password,false); // 获取用户信息
    		if($data['status'] != 1)
    			$this->error($data['msg'] ? $data['msg'] :  '操作失败');
    		$this->success($data['msg'],Url::build('Home/User/login'));
    		exit;
    	}
        return $this->fetch();
    }
     
	 
	/**
	 * 忘记密码流程 修改密码
	 */
    public function edit_pwd(){
    	if($this->user_id > 0){
            $this->redirect('Home/User/Index');
    	}
    	$check = session('validate_code');
  
    	if(empty($check)){
            $this->redirect('Home/User/forget_pwd');
    	}elseif($check['is_check']==0){
    		$this->error('验证码还未验证通过',Url::build('Home/User/forget_pwd'));
    	}    	
    	if(IS_POST){
    		$password = I('post.password');
    		$password2 = I('post.password2');
    		if($password2 != $password){
    			$this->error('两次密码不一致',Url::build('Home/User/forget_pwd'));
    		}
    		if($check['is_check']==1){
    			//$user = get_user_info($check['sender'],1);
                //$user = Db::name('users')->where("mobile|email", '=', $check['sender'])->find();
              	$user = Db::name('users')->where("mobile", $check['sender'])->find();
    			Db::name('users')->where("user_id", $user['user_id'])->save(array('password'=>encrypt($password)));
    			session('validate_code',null);
                $this->redirect('Home/User/finished');
    		}else{
    			$this->error('验证码还未验证通过',Url::build('Home/User/forget_pwd'));
    		}
    	}
    	return $this->fetch();
    }
	
	
	 /**
	 * 验证用户是否存在
	 */
    public function check_username(){
    	$username = I('post.username');
    	if(!empty($username)){
    		$count = Db::name('users')->where("mobile", $username)->count();
    		exit(json_encode(intval($count)));
    	}else{
    		exit(json_encode(0));
    	}  	
    }
	
	
	/**
	 *  忘记密码流程 用户信息确认页
	 */
	 public function check_captcha(){
    	$verify = new Verify();
    	$type = I('post.type','user_login');
    	if (!$verify->check(I('post.verify_code'), $type)) {
    		exit(json_encode(0));
    	}else{
    		exit(json_encode(1));
    	}
    }
	
	
	/*
	 * 忘记密码流程 确认手机或者邮箱有效性
	 */
	public function authentication(){
    	if($this->user_id > 0){
            $this->redirect('Home/User/Index');
    	}
    	$username = I('post.username');
    	$userinfo = array();
    	if($username){
    		$userinfo = Db::name('users')->where('mobile', $username)->find();
    		$userinfo['username'] = $username;
    		session('userinfo',$userinfo);
    	}else{
    		$this->error('参数有误！！！');
    	} 	
    	if(empty($userinfo)){
    		$this->error('非法请求！！！');
    	}
    	unset($user_info['password']);
    	$this->assign('userinfo',$userinfo);
    	return $this->fetch();
    }
	//第三方账号绑定手机
	public function linkPhone(){
		return $this->fetch();
	}
	//会员注册
	public function leaguerRegister(){
		return $this->fetch();
	}
	
    
    
	/*
	 * 忘记密码流程 结束
	 */
	 public function finished(){
    	if($this->user_id > 0){
            $this->redirect('Home/User/Index');
    	}
    	return $this->fetch();
    }
	
	
	/*
     * 商品收藏
     */
    public function goods_collect(){
        $userLogic = new UsersLogic();
        $data = $userLogic->get_goods_collect($this->user_id);
        $this->assign('page',$data['show']);// 赋值分页输出
        $this->assign('lists',$data['result']);
        $this->assign('active','goods_collect');
        return $this->fetch();
    }

	
    /*
     * 删除一个收藏商品
     */
    public function del_goods_collect(){
        $id = I('get.id');
        if(!$id)
            $this->error("缺少ID参数");
        $row = Db::name('goods_collect')->where(array('collect_id'=>$id,'user_id'=>$this->user_id))->delete();
        if(!$row)
            $this->error("删除失败");
        $this->redirect('Home/User/goods_collect');
    }
	
	
	/*
     * 店铺收藏
     */
    public function supplie_collect(){
        $userLogic = new UsersLogic();
        $data = $userLogic->get_supplie_collect($this->user_id);
        $this->assign('page',$data['show']);// 赋值分页输出
        $this->assign('lists',$data['result']);
        $this->assign('active','supplie_collect');
        return $this->fetch();
    }

	
    /*
     * 删除一个收藏店铺
     */
    public function del_supplier_collect(){
        $id = I('get.id');
        if(!$id)
            $this->error("缺少ID参数");
        $row = Db::name('supplier_collect')->where(array('collect_id'=>$id,'user_id'=>$this->user_id))->delete();
        if(!$row)
            $this->error("删除失败");
        $this->redirect('Home/User/supplie_collect');
    }
	
	
	 /**
     * 退换货列表
     */
    public function back_goods_list()
    {        
        $count = Db::name('back_order')->where("user_id", $this->user_id)->count();
        $page = new Page($count,10);
        $list = Db::name('back_order')->where("user_id", $this->user_id)->order("id desc")->limit("{$page->firstRow},{$page->listRows}")->select();
        $goods_id_arr = get_arr_column($list, 'goods_id');
        if(!empty($goods_id_arr))
            $goodsList = Db::name('goods')->where("goods_id","in", implode(',',$goods_id_arr))->column('goods_id,goods_name');
        $this->assign('goodsList', $goodsList);
        $this->assign('list', $list);
        $this->assign('page', $page->show());// 赋值分页输出
		$this->assign('active','back_goods_list');
        return $this->fetch();
    }
    
    /**
     *  退货详情
     */
    public function back_goods_info()
    {
        $id = I('id/d',0);
        $return_order = Db::name('back_order')->where("id", $id)->where("user_id", $this->user_id)->find();
        if($return_order['imgs'])
            $return_order['imgs'] = explode(',', $return_order['imgs']);     

		 if(!$return_order)
		{
			$this->error('对不起！您没权限查看订单');
			exit;
		} 
		$return_goods = Db::name('back_goods')->where('back_id',$id)->find();
        $goods = Db::name('goods')->where("goods_id", $return_goods['goods_id'])->find();
		if(IS_POST){
			if($shipping_no = input('shipping_no'))
			Db::name('back_order')->where('id',$id)->update(array('status'=>2,'retund_shipping_time'=>time(),'refund_shipping_no'=>$shipping_no));
			
			if($content = input('content'))
			Db::name('back_msg')->insert(['rec_id'=>$id,'add_time'=>time(),'content'=>$content,'user_id'=> $this->user_id]);
		
			exit(json_encode(1));
		}
		$msg = Db::name('back_msg')->where('rec_id',$id)->select();
        $this->assign('goods',$goods);
		$this->assign('msg',$msg);
		$this->assign('return_goods',$return_goods);
        $this->assign('return_order',$return_order);
        return $this->fetch();
    }
    
	  
    /**
     * 验证码验证
     * $id 验证码标示
     */
    private function verifyHandle($id)
    {
        $verify = new Verify();
        $result = $verify->check(I('post.verify_code'), $id ? $id : 'user_login');
        if (!$result) {
            $this->error("图像验证码错误");
        }
    }

    /**
     * 验证码获取
     */
    public function verify()
    {
		
		ob_end_clean();
        //验证码类型
        $type = I('get.type') ? I('get.type') : 'user_login';
        $config = array(
            'fontSize' => 40,
            'length' => 4,
            'useCurve' => false,
            'useNoise' => true,
        );
        $Verify = new Verify($config);
        $Verify->entry($type);
    }

}