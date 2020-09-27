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
        if(I('user_id'))
        {
            $user = Db::name('users')->where("user_id",I('user_id'))->Cache(true,600)->find();
            session('user',$user);  //覆盖session 中的 user               
        	$this->user = $user;
        	$this->user_id = $user['user_id'];
        }else{
            session('login_url',$_SERVER[REQUEST_URI]);
        	$nologin = array(
        			'user_login','logout','login','register','verifyHandle',
					'verify','forget_pwd','check_captcha','check_username','authentication',
					'edit_pwd','finished','linkPhone','leaguerRegister','ac_up_pw'
        	);
        	if(!in_array(ACTION_NAME,$nologin)){
                exit(json_encode(array('status'=>'-11','info'=>'请先登录')));
        	}
        }
        //用户中心面包屑导航
        $this->navigate_user = navigate_user();
    }
	
	
    /*
     * 用户中心首页
     */
    public function index(){
        $logic = new UsersLogic();
        $user = $logic->get_info($this->user_id);
        $user = $user['result'];

        $rs=array('status'=>'1','info'=>'请求成功','user'=>$user);
        exit(json_encode($rs));
    }

	
	/**
    *   用户登录处理
    */
    public function login(){
        if($this->user_id > 0){
            exit(json_encode(array('status'=>'-10','info'=>'请勿重复登录')));
        }

        setcookie('supplier_name','',time()-3600,'/');
        setcookie('redsupplier_name','',time()-3600,'/');
        setcookie('cn','',time()-3600,'/');
        setcookie('supplier_admin_id','',time()-3600,'/');
        setcookie('redsupplier_red_admin_id','',time()-3600,'/');
        
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
                $nickname = empty($user['nickname']) ? $mobile2 : $user['nickname'];
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
            exit(json_encode(array('status'=>-1,'msg'=>'请勿重复登录')));
        }
		if(IS_POST){
            $mobile = trim(I('post.mobile',''));
            $password = trim(I('post.password',''));
            $password2 = trim(I('post.password2',''));
            $code = trim(I('post.code',''));
            $session_id = session_id();
			$verify_code = trim(I('post.verify_code'));
			$recommend_code = I('post.recommend_code');
    		$logic = new UsersLogic();
            $res = $logic->check_validate_code($code, $mobile  , 'mobile');
    		
    		if ($res['status'] != 1){
                exit(json_encode(array('status'=>-111,'msg'=>$res['msg'])));
    		}
    		$data = $logic->reg($mobile,$password,$password2,$recommend_code);
                if($data['status'] != 1){
                    exit(json_encode(array('status'=>-111,'msg'=>$res['msg'])));
                }
            exit(json_encode(array('status'=>1,'msg'=>$res['msg'])));
		}
	}
	
	
	/**
	 * 用户退出
	 */
	 
	public function logout(){
		setcookie('user_name','',time()-3600,'/');
    	setcookie('cn','',time()-3600,'/');
    	setcookie('user_id','',time()-3600,'/');
	}
	 
	 
    /*
    * 手机验证
    */
    // public function mobile_validate(){
    //     $userLogic = new UsersLogic();
    //     $user_info = $userLogic->get_info($this->user_id); //获取用户信息
    //     $user_info = $user_info['result'];
    //     $config = F('sms','',TEMP_PATH);
    //     $sms_time_out = 90;
    //     $step = I('get.step',1);
    //     if(IS_POST){
    //         $mobile = I('post.mobile');
    //         $old_mobile = I('post.old_mobile','');
    //         $code = I('post.code');
          
    //         //检查原手机是否正确
    //         if($user_info['mobile_validated'] == 1 && $old_mobile != $user_info['mobile']){
    //             exit(json_encode(array('status'=>-1,'msg'=>'原手机号码错误')));
    //         }
    //         //验证手机和验证码
			 // $res = $userLogic->check_validate_code($code, $mobile  , 'mobile');
		

    //         if($res['status'] == 1){
				
				// $password = input('password');
				// $password2 = input('password2');
				
				// if($password != $password2){
    //                 exit(json_encode(array('status'=>-1,'msg'=>'两次密码不一致')));
    //             }

    //             if(!$userLogic->update_email_mobile($mobile,$this->user_id,2,$password)){
    //                 exit(json_encode(array('status'=>-1,'msg'=>'手机已存在')));
    //             }
				
				
    //             exit(json_encode(array('status'=>1,'msg'=>'绑定成功')));
    //         }
    //         exit(json_encode(array('status'=>-1,'msg'=>$res['msg'])));
    //     }
    //     $rs=array('status'=>'1','info'=>'请求成功','user_info'=>$user_info,'sms_time_out'=>$sms_time_out,'step'=>$step);
    //     exit(json_encode($rs));
    // }
	 
	 
	 /*
     * 订单列表
     */
    public function order_list(){
        $where = ' user_id=:user_id and is_parent = 0 ';
        $bind['user_id'] = $this->user_id;
        //订单状态条件搜索
        if(I('type')){
            $where .= config(strtoupper(I('type')));
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
            $data = $model->get_order_goods($v['order_id']);
            $goods_lists = $data['result'];
            if($order_list[$k]['order_prom_type'] == 4){
                $pre_sell_item =  Db::name('goods_activity')->where(array('act_id'=>$order_list[$k]['order_prom_id']))->find();
                $pre_sell_item = array_merge($pre_sell_item,unserialize($pre_sell_item['ext_info']));
                $order_list[$k]['pre_sell_is_finished'] = $pre_sell_item['is_finished'];
                $order_list[$k]['pre_sell_retainage_start'] = $pre_sell_item['retainage_start'];
                $order_list[$k]['pre_sell_retainage_end'] = $pre_sell_item['retainage_end'];
            }else{
                $order_list[$k]['pre_sell_is_finished'] = -1;//没有参与预售的订单
            }
            //是否有退换货的进度
            foreach ($goods_lists as $key => $value) {
                $value['back_order_id'] = Db::name('back_order')->where(["user_id"=>$this->user_id,'order_id'=>$value['order_id'],'goods_id'=>$value['goods_id']])->value('id');
                $order_list[$k]['goods_list'][] = $value;
            }
        }


        $rs=array('status'=>'1','info'=>'请求成功','order_status'=>config('ORDER_STATUS'),'shipping_status'=>config('SHIPPING_STATUS'),'pay_status'=>config('PAY_STATUS'),'active_status'=>I('type'),'active'=>'order_list','lists'=>$order_list,'page'=>$show,'count'=>$count);
        exit(json_encode($rs));
    }
	

    /*
     * 订单详情
     */
    public function order_detail(){
        $id = I('get.order_id/d');

        $map['order_id'] = $id;
        $map['user_id'] = $this->user_id;
        $order_info = Db::name('order')->where($map)->find();
        $order_info = set_btn_order_status($order_info);  // 添加属性  包括按钮显示属性 和 订单状态显示属性
        
        if(!$order_info){
            exit(json_encode(array('status'=>-1,'msg'=>'没有获取到订单信息')));
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
        	
        }else{
        	
        	$data = $model->get_order_goods($order_info['order_id']);        
        }        
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

        $rs=array('status'=>'1','info'=>'请求成功','order_status'=>config('ORDER_STATUS'),'shipping_status'=>config('SHIPPING_STATUS'),'pay_status'=>config('PAY_STATUS'),'order_info'=>$order_info,'active'=>'order_list');
        exit(json_encode($rs));
    }
	

    /*
     * 取消订单
     */
    public function cancel_order(){
        $id = I('get.order_id/d');
        //检查是否有积分，余额支付
        $logic = new UsersLogic();
        $data = $logic->cancel_order($this->user_id,$id);
        if($data['status'] < 0){
            exit(json_encode(array('status'=>-1,'msg'=>$data['msg'])));
        }
        exit(json_encode(array('status'=>1,'msg'=>$data['msg'])));
    }
	
	
	/**
	 * 确认收货
	 */
	public function order_confirm(){
        $id = I('get.order_id   /d',0);
                                  
        $data = confirm_order($id,$this->user_id);
        if(!$data['status']){
            exit(json_encode(array('status'=>-1,'msg'=>$data['msg'])));
        }else{
            exit(json_encode(array('status'=>1,'msg'=>$data['msg'])));
        }
    }
    /**
     * 售后服务--申请退货
     */
    public function back_order()
    {   
        $data = input('');
        $order_id = trim(input('order_id/d',0));
        $goods_id = trim(input('goods_id/d',0));
	    $spec_key = trim(input('spec_key'));
		$send_number = intval(input('send_number'));
		$reason	  = input('reason');
        if($data['imgs']){
            $imgs = implode(',', $data['imgs']);     
        }
        $order = Db::name('order')->where("order_id", $order_id)->where('user_id', $this->user_id)->field('add_time,order_sn,order_id')->find();

        if(!$order['order_id'])
        {
            exit(json_encode(array('status'=>-1,'msg'=>'对不起！您没权限针对该商品发起退款/退货及维修')));
        }         
     
		$return_order =Db::name('back_order')->alias('o')->join('back_goods g','o.id = g.back_id','left')->where(['o.order_id'=>$order_id,'g.goods_id'=>$goods_id,'g.spec_key'=>$spec_key])->find();
		
        if(!empty($return_order))
        {
            exit(json_encode(array('status'=>-111,'id'=>$return_order['id'],'msg'=>'申请失败,已有申请记录')));
        } 
		$goods = Db::name('order_goods')->where(['order_id'=>$order_id,'goods_id'=>$goods_id,'spec_key'=>$spec_key])->find();	

        //生成退款、退货记录	
        if(IS_POST)
        {
            $logic = new UsersLogic();
            $return = $logic->back_order_s($order_id,$goods_id,$spec_key,1,$send_number,$reason,$imgs,$this->user_id);
            if ($return == 1) {
                exit(json_encode(array('status'=>-1,'msg'=>'退换货数量上限')));
            }elseif($return == "ok"){
                exit(json_encode(array('status'=>1,'msg'=>'申请成功,客服将会及时处理订单')));
            }
        }

        $rs=array('status'=>'1','info'=>'请求成功','goods'=>$goods,'order'=>$order,'goods_id'=>$goods_id,'spec_key'=>$spec_key);
        exit(json_encode($rs));

    }

    /**
     *图片上传，base64位压缩，点击即可上传
     */
    public function logoImages(){
        $base64 = I('post.logoImages');
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64, $result)){
            $type = $result[2]; //jpeg
            $IMG = base64_decode(str_replace($result[1], '', $base64)); //返回文件流
        }
        $path ='public/upload/back_order/';
        if (!file_exists($path)){
            mkdir($path,0777,true);
        }//如果地址不存在，创建地址
        $u=uniqid().date("ymdHis",time());
        $picname=$path.$u.'.jpg';
        $picname_s="/".$picname;
        file_put_contents($picname,$IMG);
        $picname2=$u.'.jpg';
        $this->ajaxReturn(array('status' => 1,'pic_id' =>$picname2,'pic_path'=>$picname_s));
    }


    
     /**
     * 退换货列表
     */
    public function back_goods_list()
    {        
        $count = Db::name('back_order')->where("user_id", $this->user_id)->count();
        $page = new Page($count,10);
        $list = Db::name('back_order')->where("user_id", $this->user_id)->order("id desc")->limit("{$page->firstRow},{$page->listRows}")->field('id,goods_id,order_sn,order_id,addtime,status,total_amount')->select();
        foreach ($list as $key => $value) {
            $value['goodsList'] = Db::name('back_goods')->where(['goods_id'=>$value['goods_id'],'back_id'=>$value['id']])->field('goods_id,spec_key_name,goods_name')->find();
            $value['goodsList']['total_amount'] = $value['total_amount'];
            $value['goodsList']['goods_thumb'] = Db::name('goods')->where(['goods_id'=>$value['goods_id']])->value('goods_thumb');
            $lists[] = $value;
        }
        $rs=array('status'=>'1','info'=>'请求成功','list'=>$lists,'page'=>$page->show(),'active'=>'back_goods_list');
        exit(json_encode($rs));
    }
    
    /**
     *  退货详情
     */
    public function back_goods_info()
    {
        $id = I('id/d',0);
        $return_order = Db::name('back_order')->where("id", $id)->where("user_id", $this->user_id)->find();
        if($return_order['imgs']){
            $return_order['imgs'] = explode(',', $return_order['imgs']);     
        }

        if(!$return_order)
        {
            exit(json_encode(array('status'=>-1,'msg'=>'对不起！您没权限查看订单')));
        } 
        $return_goods = Db::name('back_goods')->where('back_id',$id)->find();
        $goods_thumb = Db::name('goods')->where("goods_id", $return_goods['goods_id'])->value('goods_thumb');
        $return_goods['goods_thumb'] = $goods_thumb;
        $return_goods['order_id'] = $return_order['order_id'];
        $return_goods['order_sn'] = $return_order['order_sn'];
        $return_goods['addtime']  = $return_order['addtime'];
        $return_goods['is_refund']  = $return_order['is_refund'];       //是否完成退换/退款
        //-1拒绝退换0申请中1客服理中2寄回商品3待退款4收到寄回商品5寄出换货6完成
        $return_goods['status']  = $return_order['status']; 
        $return_goods['reason']  = $return_order['reason'];             //原因
        $return_goods['imgs']    = $return_order['imgs'];               //反馈图片
        if(IS_POST){
            if($shipping_no = input('shipping_no')){
                Db::name('back_order')->where('id',$id)->update(array('status'=>2,'retund_shipping_time'=>time(),'refund_shipping_no'=>$shipping_no));
            }
            if($content = input('content')){
                Db::name('back_msg')->insert(['rec_id'=>$id,'add_time'=>time(),'content'=>$content,'user_id'=> $this->user_id]);
            }
            exit(json_encode(array('status'=>1,'msg'=>'编辑成功')));
        }
        $msg = Db::name('back_msg')->where('rec_id',$id)->select();

        $rs=array('status'=>'1','info'=>'请求成功','msg'=>$msg,'return_goods'=>$return_goods);
        exit(json_encode($rs));
    }
    
     /**
     * [return_goods_cancel 取消退换货]
     * @return [type] [description]
     */
    public function return_goods_cancel(){
        $data = I('post.');
        $a = Db::name('back_order')->where('id',$data['id'])->delete();
        $b = Db::name('order_goods')->where(['order_id'=>$data['order_id'],'goods_id'=>$data['goods_id']])->update(['is_service' => 0]);
        if ($a==1 && $b==1) {
            exit(json_encode(array('status'=>1,'msg'=>'取消成功。')));
        }else{
            exit(json_encode(array('status'=>-1,'msg'=>'取消失败，订单商品不存在。')));
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
            if(!$userLogic->update_info($this->user_id,$post)){
                exit(json_encode(array('status'=>-1,'msg'=>'保存失败')));
            }
            exit(json_encode(array('status'=>1,'msg'=>'操作成功')));
        }
        if (!preg_match('/[\x{4e00}-\x{9fa5}]/u', $user_info['province'])) {   //非中文，转换旧的地址资料
            $user_info['province'] =  Db::name('region')->where(array('id'=>$user_info['province']))->value('name');
            $user_info['city'] =  Db::name('region')->where(array('id'=>$user_info['city']))->value('name');
            $user_info['district'] =  Db::name('region')->where(array('id'=>$user_info['district']))->value('name');
        }
        $rs=array('status'=>'1','info'=>'请求成功','user'=>$user_info,'sex'=>config('SEX'),'active'=>'info');
        exit(json_encode($rs));
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
        $rs=array('status'=>'1','info'=>'请求成功','lists'=>$address_listss,'active'=>'address_list');
        exit(json_encode($rs));
    }
	
	
	// /*
 //     * 添加地址
 //     */
 //    public function add_address(){
 //        header("Content-type:text/html;charset=utf-8");
 //        if(IS_POST){
 //            $logic = new UsersLogic();
 //            $data = $logic->add_address($this->user_id,0,I('post.'));
 //            if($data['status'] != 1){
 //                exit(json_encode(array('status'=>-1,'msg'=>$data['msg'])));
 //            }
 //            $call_back = $_REQUEST['call_back'];
 //            exit(json_encode(array('status'=>1,'msg'=>'新增成功','新增ID'=>$call_back)));
 //        }
 //        $p = Db::name('region')->where(array('parent_id'=>0,'level'=> 1))->select();
 //        $rs=array('status'=>'1','info'=>'请求成功','province'=>$p);
 //        exit(json_encode($rs));
 //    }
	

    /*
     * 地址添加/编辑
     */
    public function edit_address(){
        header("Content-type:text/html;charset=utf-8");
        $id = I('post.id/d');
        $address = Db::name('user_address')->where(array('address_id'=>$id,'user_id'=> $this->user_id))->find();
        if(IS_POST){
            $logic = new UsersLogic();
            if (I('post.is_default') == 1) {
                Db::name('user_address')->where(array('user_id'=>$this->user_id))->update(array('is_default'=>0));
            }
            $data = $logic->add_address($this->user_id,$id,I('post.'));
            if($data['status'] != 1){
                exit(json_encode(array('status'=>-1,'msg'=>$data['msg'])));
            }
            $call_back = $_REQUEST['call_back'];
            exit(json_encode(array('status'=>1,'msg'=>'新增成功','新增ID'=>$call_back)));
        }
        //获取省份
        $p = Db::name('region')->where(array('parent_id'=>0,'level'=> 1))->cache(true)->select();
        $c = Db::name('region')->where(array('parent_id'=>$address['province'],'level'=> 2))->select();
        $d = Db::name('region')->where(array('parent_id'=>$address['city'],'level'=> 3))->select();
        if($address['twon']){
        	$e = Db::name('region')->where(array('parent_id'=>$address['district'],'level'=>4))->select();
        }

        $rs=array('status'=>'1','info'=>'请求成功','twon'=>$e,'province'=>$p,'city'=>$c,'district'=>$d,'address'=>$address);
        exit(json_encode($rs));
    }
	
	
	/*
     * 设置默认收货地址
     */
    public function set_default(){
        $id = I('get.id/d');
        Db::name('user_address')->where(array('user_id'=>$this->user_id))->update(array('is_default'=>0));
        $row = Db::name('user_address')->where(array('user_id'=>$this->user_id,'address_id'=>$id))->update(array('is_default'=>1));
        if(!$row){
            exit(json_encode(array('status'=>-1,'msg'=>'操作失败')));
        }
        exit(json_encode(array('status'=>1,'msg'=>'操作成功')));
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
        if(!$row){
            exit(json_encode(array('status'=>-1,'msg'=>'操作失败')));
        }else{
            exit(json_encode(array('status'=>1,'msg'=>'操作成功')));
        }
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
            if($data['status'] == -1){
                exit(json_encode(array('status'=>-1,'msg'=>$data['msg'])));
            }
            exit(json_encode(array('status'=>1,'msg'=>$data['msg'])));
        }
        return $this->fetch();
    }
	
	
	/*
     * 评论列表晒单/详情
     */
    public function comment(){
        $user_id = $this->user_id;
        $status = I('status',-1);
        //详情条件
        if (I('order_id') && I('goods_id') && I('status')==1 ) {
            $comment['order_id'] = I('order_id');
            $comment['goods_id'] = I('goods_id');
        }
        $logic = new UsersLogic();
        $data = $logic->get_comment($user_id,$status,$comment); //获取评论列表
        foreach ($data['result'] as $key => $value) {
            $value['img'] = unserialize($value['img']); // 晒单图片      
            $value['username'] = mb_substr($value['username'], 0, 1, 'utf-8').'**'. mb_substr($value['username'], -2, 2, 'utf-8');; // 晒单图片   
            $datas[] = $value;
        }
        $rs=array('status'=>'1','info'=>'请求成功','page'=>$data['show'],'comment_list'=>$datas,'active'=>'comment');
        exit(json_encode($rs));
    }
	
	
	 /*
     *添加评论
     */
    public function add_comment()
    {   
        if (IS_POST) 
        {
            $user_info = $this->user;
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
        }       

        $rs=array('status'=>'1','info'=>'请求成功','row'=>$row);
        exit(json_encode($rs));        
    }

	
	/*
	 * 忘记密码
	 */
	// public function forget_pwd(){
 //    	if($this->user_id > 0){
 //            exit(json_encode(array('status'=>-1,'msg'=>'已登录')));
 //    	}
 //    	if(IS_POST){
 //    		$logic = new UsersLogic();
 //    		$mobile = I('post.mobile');
 //    		$code = I('post.code');
 //    		$new_password = I('post.new_password');
 //    		$confirm_password = I('post.confirm_password');
 //    		$pass = false;
    	
 //    		//检查是否手机找回
 //    		if(check_mobile($mobile)){
 //    			if(!$user = get_user_info($mobile,2)){
 //                    exit(json_encode(array('status'=>-1,'msg'=>'账号不存在')));
 //                }
 //    			$check_code = $logic->check_validate_code($mobile,$code,$this->session_id);
 //    			if($check_code['status'] != 1){
 //                    exit(json_encode(array('status'=>-1,'msg'=>$check_code['msg'])));
 //                }
 //    			$pass = true;
 //    		}
 //    		//检查是否邮箱
 //    		if(check_email($mobile)){
 //    			if(!$user = get_user_info($mobile,1)){
 //                    exit(json_encode(array('status'=>-1,'msg'=>'账号不存在')));
 //                }
 //    			$check = session('forget_code');
 //    			if(empty($check)){
 //                    exit(json_encode(array('status'=>-1,'msg'=>'非法操作')));
 //                }
 //    			if(!$mobile || !$code || $check['email'] != $mobile || $check['code'] != $code){
 //                    exit(json_encode(array('status'=>-1,'msg'=>'邮箱验证码不匹配')));
 //                }
 //    			$pass = true;
 //    		}
 //    		if($user['user_id'] > 0 && $pass)
 //    			$data = $logic->password($user['user_id'],'',$new_password,$confirm_password,false); // 获取用户信息
 //    		if($data['status'] != 1){
 //                exit(json_encode(array('status'=>-1,'msg'=>$data['msg'] ? $data['msg'] :  '操作失败')));
 //            }
 //            exit(json_encode(array('status'=>1,'msg'=>$data['msg'])));
 //    	}
 //        return $this->fetch();
 //    }

     /*
     * 忘记密码流程 确认手机或者邮箱有效性
     */
    // public function authentication(){
    //     if($this->user_id > 0){
    //         exit(json_encode(array('status'=>-1,'msg'=>'已登录')));
    //     }
    //     $username = I('post.username');
    //     $userinfo = array();
    //     if($username){
    //         $userinfo = Db::name('users')->where('mobile', $username)->find();
    //         $userinfo['username'] = $username;
    //         session('userinfo',$userinfo);
    //     }else{
    //         exit(json_encode(array('status'=>-1,'msg'=>'参数有误！！！')));
    //     }   
    //     if(empty($userinfo)){
    //         exit(json_encode(array('status'=>-1,'msg'=>'非法请求！！！')));
    //     }
    //     unset($user_info['password']);

    //     $rs=array('status'=>'1','info'=>'请求成功','userinfo'=>$userinfo);
    //     exit(json_encode($rs));
    // }
	 
	/**
	 * 忘记密码流程 提交修改密码
	 */
    public function edit_pwd(){
    	if($this->user_id > 0){
            exit(json_encode(array('status'=>-1,'msg'=>'已登录')));
    	}
    	$check = session('validate_code');
    	if(empty($check)){
            exit(json_encode(array('status'=>-1,'msg'=>'验证为空')));
    	}elseif($check['is_check']==0){
            exit(json_encode(array('status'=>-1,'msg'=>'验证码还未验证通过')));
    	}    	
    	if(IS_POST){
    		$password = I('post.password');
    		$password2 = I('post.password2');
    		if($password2 != $password){
                exit(json_encode(array('status'=>-1,'msg'=>'两次密码不一致')));
    		}
    		if($check['is_check']==1){
              	$user = Db::name('users')->where("mobile", $check['sender'])->find();
    			Db::name('users')->where("user_id", $user['user_id'])->save(array('password'=>encrypt($password)));
    			session('validate_code',null);
                exit(json_encode(array('status'=>1,'msg'=>'修改成功')));
    		}else{
                exit(json_encode(array('status'=>-1,'msg'=>'验证码还未验证通过')));
    		}
    	}
    }
	
	
	 /**
	 * 忘记密码流程 第一页验证用户是否存在
	 */
    public function check_username(){
    	$mobile = I('post.mobile');
    	if(!empty($mobile)){
    		$count = Db::name('users')->where("mobile", $mobile)->count();
    		exit(json_encode(intval($count)));
    	}else{
    		exit(json_encode(0));
    	}  	
    }
	
	
	/**
	 *  忘记密码流程 第一页验证码验证
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
	
	
	

	// //第三方账号绑定手机
	// public function linkPhone(){
	// 	return $this->fetch();
	// }
	// //会员注册
	// public function leaguerRegister(){
	// 	return $this->fetch();
	// }
	
    
    
	// /*
	//  * 忘记密码流程 结束
	//  */
	//  public function finished(){
 //    	if($this->user_id > 0){
 //            $this->redirect('Home/User/Index');
 //    	}
 //    	return $this->fetch();
 //    }
	
	
	/*
     * 商品收藏列表
     */
    public function goods_collect(){
        $userLogic = new UsersLogic();
        $data = $userLogic->get_goods_collect($this->user_id);

        $rs=array('status'=>'1','info'=>'请求成功','page'=>$data['show'],'lists'=>$data['result'],'active'=>'goods_collect');
        exit(json_encode($rs));
    }

	
    /*
     * 删除一个收藏商品
     */
    public function del_goods_collect(){
        $id = I('get.collect_id');
        if(!$id){
            exit(json_encode(array('status'=>-1,'msg'=>'缺少ID参数')));
        }
        $row = Db::name('goods_collect')->where(array('collect_id'=>$id,'user_id'=>$this->user_id))->delete();
        if(!$row){
            exit(json_encode(array('status'=>-1,'msg'=>'删除失败')));
        }
        exit(json_encode(array('status'=>1,'msg'=>'删除成功')));
    }
	
	
	/*
     * 店铺收藏列表
     */
    public function supplie_collect(){
        $userLogic = new UsersLogic();
        $data = $userLogic->get_supplie_collect($this->user_id);
        foreach ($data['result'] as $key => $value) {
            $value['supplier_goods'] = Db::name('goods')->where('supplier_id',$value['supplier_id'])->limit(4)->field('goods_thumb,goods_id,goods_name,shop_price')->select();
            $data['results'][] = $value;
        }
        $rs=array('status'=>'1','info'=>'请求成功','page'=>$data['show'],'lists'=>$data['results'],'active'=>'supplie_collect');
        exit(json_encode($rs));
    }

	
    /*
     * 删除一个收藏店铺
     */
    public function del_supplier_collect(){
        $id = I('get.collect_id');
        if(!$id){
            exit(json_encode(array('status'=>-1,'msg'=>'缺少ID参数')));
        }
        $row = Db::name('supplier_collect')->where(array('collect_id'=>$id,'user_id'=>$this->user_id))->delete();
        if(!$row){
            exit(json_encode(array('status'=>-1,'msg'=>'删除失败')));
        }
        exit(json_encode(array('status'=>1,'msg'=>'删除成功')));
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
            exit(json_encode(array('status'=>-1,'msg'=>'图像验证码错误')));
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