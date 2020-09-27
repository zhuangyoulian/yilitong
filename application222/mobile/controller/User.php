<?php
/**
 * Created by PhpStorm.
 * User: jiayi
 * Date: 2017/7/4
 * Time: 17:51
 */
namespace ylt\mobile\controller;
use ylt\home\logic\UsersLogic;
use ylt\home\controller\Notify;
use think\Page;
use think\Request;
use think\Verify;
use think\Db;
use think\Url;
use think\Cache;

class User extends MobileBase{

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
            //联系客服手机号
            $phone = Db::name('config')->where("id",56)->field('value')->value('value');
            $this->phone = $phone;
            $this->assign('phone', $phone); 
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
            'forget_pwd', 'check_captcha', 'check_username', 'send_validate_code', 'express','getBackPassword',
                 'checkmobilecode','newpassw','jiyan','jiyan_yz',
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

                        $Notify = new \ylt\home\controller\Notify();
                        //查询是否有礼至家居过来的中奖记录
                        $sdf = $Notify->inquire_lottery($wxuser['unionid']);
                        //绑定手机号
                        if (empty($data['result']['mobile'])) {
                            session('login_url',$_SERVER[REQUEST_URI]);
                            // session('login_url',$_SERVER[PHP_SELF]);
                            $this->error('请先绑定手机账号',Url::build('User/mobile_validate_two'));
                        }
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

    /*
 * 用户中心首页
 */
    public function index()
    {
        $user_id =$this->user_id;
        $logic = new UsersLogic();
        $user = $logic->get_info($user_id); //当前登录用户信息
        if (empty($user['result']['mobile'])) {
            $this->error('请先绑定手机账号',Url::build('User/mobile_validate_two'));
        }
        $comment_count = Db::name('comment')->where("user_id", $user_id)->count();   // 我的评论数
        $cart = Db::name('cart')->where("user_id", $user_id)->count();   // 我的购物车数
        $time=time();
        $coupon = Db::name('coupon_list')->where(array("uid"=>$user_id,"use_time"=>0))->where("use_end_time" ,'>', $time)->count();   // 我的优惠券数
        // dump($time);die;
        $goods_id = Db::name('goods_collect')->where("user_id",$user_id)->getField('goods_id',true);
         if($goods_id){
               $goods_collect = Db::name('goods')->where('goods_id','in',$goods_id)->Cache(true,YLT_CACHE_TIME)->limit('0,4')->select();

               $this->assign('goods_collect',$goods_collect);
               // dump($goods_collect);
          }
          //查询是否为店主
        $c=Db::name('distribution')->where('u_id',$user_id)->field('id')->find();
        if ($c) {
            $this->assign('c', $c);
        }
        $this->assign('comment_count', $comment_count);
        $this->assign('cart', $cart);
        $this->assign('coupon', $coupon);
        $this->assign('user',$user['result']);
        return $this->fetch();
    }

	   /**
     *  登录
     */
    public function login()
    {
        if ($this->user_id > 0) {
            header("Location: " . Url::build('User/index'));
        }
        //是否有上级地址
        $referurl = session('login_url') ? session('login_url') : Url::build("User/index");
        //是否充值中心
        if (I('login_url') == 1) {
            $referurl = 'http://tp.cn/static/index.html';
        }
        $this->assign('referurl', $referurl);
        return $this->fetch();
    }


	/**
     *  注册
     */
    public function reg()
    {

        if($this->user_id > 0) {
            $this->redirect(Url::build('Index/index'));
        }
        $reg_sms_enable = tpCache('sms.regis_sms_enable');

        if (IS_POST) {
            $logic = new UsersLogic();
            $username = I('post.mobile', '');
            $password = I('post.password', '');
            $password2 = I('post.password2', '');
            $code = I('post.mobile_code', '');
            $scene = I('post.scene', 1);
			$recommend_code = I('post.recommend_code');
			$FManagerId = input('FManagerId');

            $session_id = session_id();

            //是否开启注册验证码机制
            if(check_mobile($username)){
                    //手机验证码
                    $check_code = $logic->check_validate_code($code, $username, 'phone', $session_id, $scene);
                    if($check_code['status'] != 1){
                        return json($check_code);
                    }
            }else{

                return json(array('status'=>-1,'msg'=>'请输入正确手机号码'));
                exit;
            }

            $data = $logic->reg($username, $password, $password2,$recommend_code);
            if ($data['status'] != 1)
                return json($data);
            session('user', $data['result']);
            setcookie('user_id', $data['result']['user_id'], null, '/');
            $cartLogic = new \ylt\home\logic\CartLogic();
            $cartLogic->setUserId($data['result']['user_id']);
            $cartLogic->login_cart_handle($this->session_id, $data['result']['user_id']);  //用户登录后 需要对购物车 一些操作
			return json($data);
            exit;
        }
        $this->assign('regis_sms_enable',$reg_sms_enable); // 注册启用短信：
        $this->assign('regis_smtp_enable',$reg_smtp_enable); // 注册启用邮箱：
        $sms_time_out = tpCache('sms.sms_time_out')>0 ? tpCache('sms.sms_time_out') : 120;
        $this->assign('sms_time_out', $sms_time_out); // 手机短信超时时间
        return $this->fetch();
    }


	 /*
     * 个人信息
     */
    public function userinfo()
    {
        $userLogic = new UsersLogic();
        $user_info = $userLogic->get_info($this->user_id); // 获取用户信息
        $user_info = $user_info['result'];
        // dump($user_info);die;
        if (IS_POST) {
        	if ($_FILES['head_pic']['tmp_name']) {
        		$file = $this->request->file('head_pic');
        		$validate = ['size'=>1024 * 1024 * 3,'ext'=>'jpg,png,gif,jpeg'];
        		$dir = 'public/upload/head_pic/';
        		if (!($_exists = file_exists($dir))){
        			$isMk = mkdir($dir);
        		}
        		$parentDir = date('Ymd');
        		$info = $file->validate($validate)->move($dir, true);
        		if($info){
        			$post['head_pic'] = '/'.$dir.$parentDir.'/'.$info->getFilename();
        		}else{
        			$this->error($info->getError());//上传错误提示错误信息
        		}
        	}
            I('post.nickname') ? $post['nickname'] = I('post.nickname') : false; //昵称
            I('post.qq') ? $post['qq'] = I('post.qq') : false;  //QQ号码
            I('post.logoImages') ? $post['head_pic'] = I('post.logoImages') : false; //头像地址
            I('post.sex') ? $post['sex'] = I('post.sex') : $post['sex'] = 0;  // 性别
            I('post.birthday') ? $post['birthday'] = strtotime(I('post.birthday')) : false;  // 生日
            I('post.province') ? $post['province'] = I('post.province') : false;  //省份
            I('post.city') ? $post['city'] = I('post.city') : false;  // 城市
            I('post.district') ? $post['district'] = I('post.district') : false;  //地区
            I('post.email') ? $post['email'] = I('post.email') : false; //邮箱
            I('post.mobile') ? $post['mobile'] = I('post.mobile') : false; //手机

            $mobile = trim(I('post.mobile'));
            $code = I('post.mobile_code', '');
            $scene = I('post.scene', 6);

            if (!empty($mobile)) {
				$oauth_user = Db::name('users')->where(['mobile' => $mobile])->find();

				if($user_info['mobile'] || $user_info['mobile_validated'])
					$this->error("非法操作");

                if (!$code)
                    $this->error('请输入验证码');
                $check_code = $userLogic->check_validate_code($code, $mobile, 'phone', $this->session_id, $scene);
                if ($check_code['status'] != 1)
                    $this->error($check_code['msg']);

				if (!input('post.password'))
                    $this->error('请输密码');

				$post['password'] =  encrypt(trim(input('post.password')));
				$post['mobile_validated'] = 1;

				if($oauth_user){
					if($post['password'] != $oauth_user['password'])
						 $this->error('密码不正确');

					$update['mobile_validated'] = 1;
					$update['oauth'] = 'weixin';
					$update['openid'] = $user_info['openid'];
					Db::name('users')->where('mobile',$mobile)->update($update); //将用户的第三方登录信息更改绑定手机号码
					Db::name('cart')->where('user_id',$this->user_id)->update(['user_id'=>$oauth_user['user_id']]);
					Db::name('users')->where('user_id',$this->user_id)->delete();
					$user = Db::name('users')->cache(true,600)->where("user_id", $oauth_user['user_id'])->find();
					session('user', $user);  //覆盖session 中的 user
					$this->success("操作成功",Url::build('Cart/cart'));
					exit;
				}

            }

            if (!$userLogic->update_info($this->user_id, $post))
                $this->error("保存失败");
            setcookie('user_name',urlencode($post['nickname']),null,'/');
            $this->success("操作成功");
            exit;
        }
        //  获取省份
        $province = Db::name('region')->where(array('parent_id' => 0, 'level' => 1))->select();
        //  获取订单城市
        $city = Db::name('region')->where(array('parent_id' => $user_info['province'], 'level' => 2))->select();
        //  获取订单地区
        $area = Db::name('region')->where(array('parent_id' => $user_info['city'], 'level' => 3))->select();
        $this->assign('province', $province);
        $this->assign('city', $city);
        $this->assign('area', $area);
        $this->assign('user', $user_info);
        $this->assign('sex', config('SEX'));
        //从哪个修改用户信息页面进来，
        $dispaly = I('action');
        if ($dispaly != '') {
            return $this->fetch("$dispaly");
            exit;
        }
        return $this->fetch();
    }

    

    /**
     *图片上传，base64位压缩，点击即可上传
     */

    public function userCustom(){
        $base64 = $_POST['logoImages'];

        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64, $result)){
            $type = $result[2]; //jpeg
            $IMG = base64_decode(str_replace($result[1], '', $base64)); //返回文件流
        }
                // dump($base64);die;
        //$IMG = base64_decode($base64);   //base64位转码，还原图片
        $path ='public/upload/head_pic/pic/';
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
     * 登录
     */
    public function do_login()
    {
        $username = trim(I('post.username'));
        $password = trim(I('post.password'));
        //验证码验证
        // $verify_code = I('post.verify_code');
        // $verify = new Verify();
        // if (!$verify->check($verify_code, 'user_login')) {
        //     $res = array('status' => 0, 'msg' => '验证码错误');
        //     exit(json_encode($res));
        // }
        $logic = new UsersLogic();
        $res = $logic->user_login($username, $password);

        if ($res['status'] == 1) {
            $res['url'] = urldecode(I('post.referurl'));
            if(strpos($res['url'],"newpassw") !== false){
                $res['url'] = Url::build("User/index");
            }
            session('user', $res['result']);
            setcookie('user_id', $res['result']['user_id'], null, '/');
            // $this->user_id = $res['result']['user_id'];
            $nickname = empty($res['result']['nickname']) ? $username : $res['result']['nickname'];
            setcookie('user_name', urlencode($nickname), null, '/');
            $cartLogic = new \ylt\home\logic\CartLogic();
            $cartLogic->setUserId($res['result']['user_id']);
    		$cartLogic->login_cart_handle($this->session_id,$res['result']['user_id']);  //用户登录后 需要对购物车 一些操作

        }
        exit(json_encode($res));
    }


	/**
	 * 退出
	 */
	public function logout()
    {
        session_unset();
        session_destroy();
        setcookie('user_name','',time()-3600,'/');
        setcookie('user_id','',time()-3600,'/');
        setcookie('PHPSESSID','',time()-3600,'/');
        //清除临时推荐人
        Db::name('users')->where("user_id", $this->user_id)->update(array('source_id'=>0));
        header("Location:" . Url::build('user/login'));
        exit();
    }


	/*
     * 订单列表
     */
    public function order_list()
    {
        $where = ' user_id=' . $this->user_id;
        //条件搜索
       if(I('get.type')){
            $where .= config(strtoupper(I('get.type')));
       }
        $count = Db::name('order')->where($where)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $order_str = "order_id DESC";
        $field="order_id,supplier_name,order_amount,order_status";
        $order_list = Db::name('order')->order($order_str)->where($where)->where('is_parent!=1')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        //获取订单商品
        $model = new UsersLogic();
        foreach ($order_list as $k => $v) {
            $order_list[$k] = set_btn_order_status($v);  // 添加属性  包括按钮显示属性 和 订单状态显示属性
            $data = $model->get_order_goods($v['order_id']);
            $order_list[$k]['goods_list'] = $data['result'];
        }
        //统计订单商品数量
        foreach ($order_list as $key => $value) {
            $count_goods_num = '';
            foreach ($value['goods_list'] as $kk => $vv) {
                $count_goods_num += $vv['goods_num'];
            }
            $order_list[$key]['count_goods_num'] = $count_goods_num;
        }

        // dump($order_list);die;
        $this->assign('page', $show);
        $this->assign('lists', $order_list);
        $this->assign('active', 'order_list');
        $this->assign('active_status', I('get.type'));
        if ($_GET['is_ajax']) {
            return $this->fetch('ajax_order_list');
            exit;
        }
        return $this->fetch();
    }

	 /*
     * 订单详情
     */
    public function order_detail()
    {
        $id = I('get.id/d');
        $user_id = $this->user_id;
        $order_info = Db::name('order')->where("order_id = ".$id." and (user_id = ".$user_id." || recommend_code = ".$user_id.")")->find();
        if (!$order_info) {
            $this->error('没有获取到订单信息');
            exit;
        }
        $order_info = set_btn_order_status($order_info);  // 添加属性  包括按钮显示属性 和 订单状态显示属性
        //获取订单商品
        $model = new UsersLogic();
        $data = $model->get_order_goods($order_info['order_id']);
        $order_info['goods_list'] = $data['result'];
        $region_list = get_region_list();
        if (empty($region_list[$order_info['province']]['name']) && empty($region_list[$order_info['city']]['name'])) {
            $order_info['address2'] = $order_info['province'].','.$order_info['city'].','.$order_info['district'].','.$order_info['address'];      
        }else{
            $order_info['address2'] =  $region_list[$order_info['province']]['name'] .','. $region_list[$order_info['city']]['name'] .','. $region_list[$order_info['district']]['name'];
            $order_info['address2'] = $order_info['address2'].$order_info['address'];      
        }   
        //获取订单操作记录
        $order_action = Db::name('order_action')->where(array('order_id' => $id))->select();

        //拼单内容
        if ($order_info['is_share'] >= 1) {
            //拼单的用户数量
            $share_the = Db::name('share_the_bill')->alias('s')->join('users u','s.u_id = u.user_id')->where('p_id',$order_info['is_share'])->field('u.head_pic')->select();  
            $share_the['count'] = count($share_the);
            $share = Db::name('share_the_bill')->alias('s')->join('discount_buy b','s.prom_id = b.id')->where('s.id',$order_info['is_share'])->find();
            if ($order_info['pay_status'] == 1) {
                $the_bill  = Db::name('share_the_bill')->where(['p_id'=>$order_info['is_share'],'u_id'=>$user_id,'prom_id'=>$share['prom_id']])->find();
                $this->assign('the_bill', $the_bill);
            }
        }
        // dump($share_the);die;

        $this->assign('order_status', config('ORDER_STATUS'));
        $this->assign('shipping_status', config('SHIPPING_STATUS'));
        $this->assign('pay_status', config('PAY_STATUS'));
        $this->assign('order_info', $order_info);
        $this->assign('share_the', $share_the);
        $this->assign('order_action', $order_action);

        if (I('waitreceive')) {  //待收货详情
            return $this->fetch('wait_receive_detail');
        }
        return $this->fetch();
    }


	/**
	 * 查看物流
	 */
	public function expres()
    {
        $order_id = I('get.order_id/d');

        $delivery = Db::name('shipping_order')->where("order_id", $order_id)->find();
		$delivery['logistics_information'] = json_decode($delivery['logistics_information'],true);
        $this->assign('delivery', $delivery);
        return $this->fetch();
    }

	/**
     * 确定收货成功
     */
    public function order_confirm()
    {
        $id = I('get.id/d', 0);
        $data = confirm_order($id, $this->user_id);
        if ($data['status'] != 1) {
            $this->error($data['msg'],Url::build('User/order_list'));
        } else {
            $model = new UsersLogic();
            $order_goods = $model->get_order_goods($id);
            $this->assign('order_goods', $order_goods);
            return $this->fetch();
            exit;
        }
    }



	  /*
     * 取消订单
     */
    public function cancel_order()
    {
        $id = I('get.id/d');
        //检查是否有积分，余额支付
        $logic = new UsersLogic();
        $data = $logic->cancel_order($this->user_id, $id);
        return $this->ajaxReturn($data);

    }

    /*
     * 用户地址列表
     */
    public function address_list()
    {
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
        $this->assign('lists', $address_listss);
        return $this->fetch();
    }

    /*
     * 添加地址
     */
    public function add_address()
    {
        if (IS_POST) {
            $datas = I('post.');
            // dump($datas);die;
            $logic = new UsersLogic();
            $data = $logic->add_address($this->user_id, 0, $datas);
            if ($data['status'] != 1){
                $this->error($data['msg']);
            }elseif (I('post.source') == 'orderconfirm') {
				$this->success($data['msg'],Url::build('Cart/orderconfirm'));
                exit;
            }

            $this->success($data['msg'], Url::build('User/address_list'));
            exit();
        }
        $p = Db::name('region')->where(array('parent_id' => 0, 'level' => 1))->select();
        $this->assign('province', $p);

        return $this->fetch();

    }

    /*
     * 地址编辑
     */
    public function edit_address()
    {
        $id = I('id/d');
        $address = Db::name('user_address')->where(array('address_id' => $id, 'user_id' => $this->user_id))->find();
        if (IS_POST) {
            $logic = new UsersLogic();
            $data = $logic->add_address($this->user_id, $id, I('post.'));
            if ($_POST['source'] == 'orderconfirm') {
                //header('Location:' . Url::build('/Mobile/Cart/cart2', array('address_id' => $id)));
				$this->success($data['msg'],Url::build('Cart/orderconfirm'));
                exit;
            } else
                $this->success($data['msg'], Url::build('User/address_list'));
            exit();
        }
        //获取省份
        $p = Db::name('region')->where(array('parent_id' => 0, 'level' => 1))->select();
        $c = Db::name('region')->where(array('parent_id' => $address['province'], 'level' => 2))->select();
        $d = Db::name('region')->where(array('parent_id' => $address['city'], 'level' => 3))->select();
        if ($address['twon']) {
            $e = Db::name('region')->where(array('parent_id' => $address['district'], 'level' => 4))->select();
            $this->assign('twon', $e);
        }
        $this->assign('province', $p);
        $this->assign('city', $c);
        $this->assign('district', $d);
        $this->assign('address', $address);
        return $this->fetch();
    }

    /*
     * 设置默认收货地址
     */
    public function set_default()
    {
        $id = I('get.id/d');
        $source = I('get.source');
        Db::name('user_address')->where(array('user_id' => $this->user_id))->update(array('is_default' => 0));
        $row = Db::name('user_address')->where(array('user_id' => $this->user_id, 'address_id' => $id))->update(array('is_default' => 1));
        if ($source == 'cart2') {
            header("Location:" . Url::build('Cart/cart2'));
            exit;
        } else {
            header("Location:" . Url::build('User/address_list'));
        }
    }

    /*
     * 地址删除
     */
    public function del_address()
    {
        $id = I('get.id/d');

        $address = Db::name('user_address')->where("address_id", $id)->find();
        $row = Db::name('user_address')->where(array('user_id' => $this->user_id, 'address_id' => $id))->delete();
        // 如果删除的是默认收货地址 则要把第一个地址设置为默认收货地址
        if ($address['is_default'] == 1) {
            $address2 = Db::name('user_address')->where("user_id", $this->user_id)->find();
            $address2 && Db::name('user_address')->where("address_id", $address2['address_id'])->update(array('is_default' => 1));
        }
        if (!$row)
            $this->error('操作失败', Url::build('User/address_list'));
        else
            $this->success("操作成功", Url::build('User/address_list'));
    }


	 /*
     * 评论晒单
     */
    public function comment()
    {
        $user_id = $this->user_id;
        $status = I('get.status');
        $logic = new UsersLogic();
        $result = $logic->get_comment($user_id, $status); //获取评论列表
        $this->assign('comment_list', $result['result']);
        if ($_GET['is_ajax']) {
            return $this->fetch('ajax_comment_list');
            exit;
        }
        return $this->fetch();
    }

    /*
     *添加评论
     */
    public function add_comment()
    {
        if (IS_POST) {
            // 晒图片
            $files = request()->file('comment_img_file');
            $save_url = 'public/upload/comment/' . date('Y', time()) . '/' . date('m-d', time());
            foreach ($files as $file) {
                // 移动到框架应用根目录/public/uploads/ 目录下
                $info = $file->rule('uniqid')->validate(['size' => 1024 * 1024 * 2, 'ext' => 'jpg,png,jpeg'])->move($save_url);
                if ($info) {
                    // 成功上传后 获取上传信息
                    // 输出 jpg
                    $comment_img[] = '/'.$save_url . '/' . $info->getFilename();
                } else {
                    // 上传失败获取错误信息
                    $this->error($file->getError());
                }
            }
            if (!empty($comment_img)) {
                $add['img'] = serialize($comment_img);
            }

            $user_info = session('user');
            $logic = new UsersLogic();
            $add['goods_id'] = I('goods_id/d');
            $add['email'] = $user_info['email'];
            $hide_username = I('hide_username');
            if (empty($hide_username)) {
                $add['username'] = $user_info['nickname'];
            }
            $add['order_id'] = I('order_id/d');
            $add['service_rank'] = I('service_rank');
            $add['deliver_rank'] = I('deliver_rank');
            $add['goods_rank'] = I('goods_rank');
            $add['is'] = I('goods_rank');
            $add['content'] = I('content');
            $add['add_time'] = time();
            $add['ip_address'] = getIP();
            $add['user_id'] = $this->user_id;

            //添加评论
            $row = $logic->add_comment($add);
            if ($row['status'] == 1) {
                $this->success('评论成功', Url::build('User/comment', array('status'=>1)));
                exit();
            } else {
                $this->error($row['msg']);
            }
        }
        $rec_id = I('rec_id/d');
        $order_goods = Db::name('order_goods')->where("rec_id", $rec_id)->find();
        $this->assign('order_goods', $order_goods);
        return $this->fetch();
    }


	/**
     * 申请退货
     */
    public function return_goods()
    {
        $order_id = I('order_id/d', 0);
        $order_sn = I('order_sn', 0);
        $goods_id = I('goods_id/d', 0);
        $good_number = I('good_number/d', 0); //申请数量
        $spec_key = I('spec_key');
        $order=Db::name('order')->where(["order_id"=>$order_id,'user_id'=>$this->user_id])->find();  //检查是否有这个订单
        if(empty($order))
        {
            $this->error('非法操作');
            exit;
        }

        $order_goods_where = ['order_id'=>$order_id,'goods_id'=>$goods_id,'spec_key'=>$spec_key];
        $return_goods = Db::name('back_order')->where($order_goods_where)->find();
        if (!empty($return_goods)) {
            $this->success('已经提交过退货申请!', Url::build('User/return_goods_info', array('id' => $return_goods['id'])));
            exit;
        }
        if (IS_POST) {
            // 晒图片
            if (count($_FILES['return_imgs']['tmp_name'])>0) {
                $files = request()->file('return_imgs');
                $save_url = 'public/upload/return_goods/' . date('Y', time()) . '/' . date('m-d', time());
                foreach ($files as $file) {
                    // 移动到框架应用根目录/public/uploads/ 目录下
                    $info = $file->rule('uniqid')->validate(['size' => 1024 * 1024 * 2, 'ext' => 'jpg,png,jpeg'])->move($save_url);
                    if ($info) {
                        // 成功上传后 获取上传信息
                        $return_imgs[] = '/'.$save_url . '/' . $info->getFilename();
                    } else {
                        // 上传失败获取错误信息
                        $this->error($file->getError());
                    }
                }
                if (!empty($return_imgs)) {
                    $data['imgs'] = implode(',', $return_imgs);
                }
            }
			if($order['parent_id'] > 0)
				$order['order_amount'] = Db::name('order')->where('order_id',$order['parent_id'])->value('order_amount');

			$goods = Db::name('order_goods')->where($order_goods_where)->find();
            if (empty($data['imgs'])) {
                $data['imgs'] = $goods['goods_thumb'];
            }
            $data['order_id'] = $order_id;
            $data['order_sn'] = $order_sn;
            $data['goods_id'] = $goods_id;
            $data['addtime'] = time();
            $data['user_id'] = $this->user_id;
            $data['type'] = I('type'); // 服务类型  退货 或者 换货
            $data['reason'] = I('reason'); // 问题描述
            $data['spec_key'] = I('spec_key'); // 商品规格
			$data['shop_price'] = ($goods['goods_price'] * $goods['goods_num']); // 商品价格
			$data['total_amount'] = $order['order_amount']; // 付款总金额
			$data['supplier_id'] = $order['supplier_id']; // 商铺ID
            $res = Db::name('back_order')->insert($data);
            $data['return_id'] = $res;  //退换货id
            $this->assign('data',$data);
			Db::name('order_goods')->where($order_goods_where)->update(['is_service'=>I('type')]);

            return $this->fetch('return_good_success'); //申请成功

            exit;
        }

        $region_id[] = tpCache('shop_info.province');
        $region_id[] = tpCache('shop_info.city');
        $region_id[] = tpCache('shop_info.district');
        $region_id[] = 0;
        $return_address = Db::name('region')->where("id in (".implode(',', $region_id).")")->getField('id,name');
        $this->assign('return_address', $return_address);

        $order_goods = Db::name('order_goods')->where($order_goods_where)->find();  //找到这个订单商品
        $order_info = array_merge($order,$order_goods);  //合并数组
        //查找订单收货地址
        $region = Db::name('order')->field('consignee,country,province,city,district,twon,address,mobile')->where("order_id = $order_id")->find();
        $region_list = get_region_list();
        $this->assign('region_list', $region_list);
        $this->assign('region', $region);
        $this->assign('goods', $order_info);
        $this->assign('order_id', $order_id);
        $this->assign('order_sn', $order_sn);
        $this->assign('goods_id', $goods_id);

        return $this->fetch();
    }


	/**
     * 退换货列表
     */
    public function back_goods_list()
    {
        //退换货商品信息
        $count = Db::name('back_order')->where("user_id", $this->user_id)->count();
        $pagesize = C('PAGESIZE');
        $page = new Page($count, $pagesize);
        $list = Db::name('back_order')->where("user_id", $this->user_id)->order("id desc")->limit("{$page->firstRow},{$page->listRows}")->select();
        $goods_id_arr = get_arr_column($list, 'goods_id');  //获取商品ID
        if (!empty($goods_id_arr)){
            $goodsList = Db::name('goods')->where("goods_id", "in", implode(',', $goods_id_arr))->getField('goods_id,goods_name');
        }
        $state = C('REFUND_STATUS');
        $this->assign('goodsList', $goodsList);
        $this->assign('list', $list);
        $this->assign('state',$state);
        $this->assign('page', $page->show());// 赋值分页输出
        if (I('is_ajax')) {
            return $this->fetch('ajax_back_goods_list');
            exit;
        }
        return $this->fetch();
    }


    /**
     *  退货详情
     */
    public function back_goods_info()
    {
        $id = I('id/d', 0);
        $return_goods = Db::name('back_order')->where("id = $id")->find();
        $return_goods['seller_delivery'] = unserialize($return_goods['seller_delivery']);  //订单的物流信息，服务类型为换货会显示
        if ($return_goods['imgs'])
            $return_goods['imgs'] = explode(',', $return_goods['imgs']);
        $goods = Db::name('goods')->where("goods_id = {$return_goods['goods_id']} ")->find();
        $state = C('REFUND_STATUS');
        $this->assign('state',$state);
        $this->assign('goods', $goods);
        $this->assign('return_goods', $return_goods);
        return $this->fetch();
    }

    /**
     * [return_goods_cancel 取消退换货]
     * @return [type] [description]
     */
    public function return_goods_cancel(){
        $data = I('get.');
        // $a = Db::name('back_order')->where('id',$data['id'])->delete();
        $a = Db::name('back_order')->where('id',$data['id'])->update(['status'=>-2]);
        $b = Db::name('order_goods')->where(['order_id'=>$data['order_id'],'goods_id'=>$data['goods_id']])->update(['is_service' => 0]);
        if ($a && $b) {
            $this->success("取消成功。", Url::build('User/back_goods_list'));
        // return array('status' =>1,'msg'=>'取消售后成功。');
        }else{
            $this->error("取消失败，订单商品不存在。");
        }
    }

	 /**
     * 用户收藏列表
     */
    public function collect_list()
    {
        $userLogic = new UsersLogic();
        $data = $userLogic->get_goods_collect($this->user_id);
        $this->assign('page', $data['show']);// 赋值分页输出
        $this->assign('goods_list', $data['result']);
        if (IS_AJAX) {      //ajax加载更多
            return $this->fetch('ajax_collect_list');
            exit;
        }
        return $this->fetch();
    }

    /*
     *取消收藏
     */
    public function cancel_collect()
    {
        $collect_id = I('collect_id/d');
        $user_id = $this->user_id;
        if (Db::name('goods_collect')->where(['collect_id' => $collect_id, 'user_id' => $user_id])->delete()) {
            $this->success("取消收藏成功", Url::build('User/collect_list'));
        } else {
            $this->error("取消收藏失败", Url::build('User/collect_list'));
        }
    }

    /*
    * 手机验证
    */
    public function mobile_validate()
    {
        $userLogic = new UsersLogic();
        $user_info = $userLogic->get_info($this->user_id); // 获取用户信息
        $user_info = $user_info['result'];
        $step = I('get.step', 1);
        //验证是否未绑定过
        if ($user_info['mobile_validated'] == 0)
            $step = 2;
        //原手机验证是否通过
        if ($user_info['mobile_validated'] == 1 && session('mobile_step1') == 1)
            $step = 2;
        if ($user_info['mobile_validated'] == 1 && session('mobile_step1') != 1)
            $step = 1;
        if (IS_POST) {
            $mobile = I('post.mobile');
            $code = I('post.code');
            $info = session('mobile_code');
            if (!$info)
                $this->error('非法操作');
            if ($info['email'] == $mobile || $info['code'] == $code) {
                if ($user_info['email_validated'] == 0 || session('email_step1') == 1) {
                    session('mobile_code', null);
                    session('mobile_step1', null);
                    if (!$userLogic->update_email_mobile($mobile, $this->user_id, 2))
                        $this->error('手机已存在');
                    $this->success('绑定成功', Url::build('User/index'));
                } else {
                    session('mobile_code', null);
                    session('email_step1', 1);
                    redirect(Url::build('User/mobile_validate', array('step' => 2)));
                }
                exit;
            }
            $this->error('验证码手机不匹配');
        }
        $this->assign('step', $step);
        return $this->fetch();
    }
    
    /**
     * [mobile_validate_two 手机绑定 2019.08]
     * @return [type] [description]
     */
    public function mobile_validate_two()
    {   
        // $url = $_SERVER['HTTP_REFERER'];
        $userLogic = new UsersLogic();
        $user_info = $userLogic->get_info($this->user_id); // 获取用户信息
        $user_info = $user_info['result'];
        if (IS_POST) {
            $mobile = I('post.mobile');
            $password = I('post.password');
            if (empty($mobile) || empty($password)) {
                $this->error('手机和密码不能为空');
            }
            if ($user_info['mobile_validated'] == 0) {
                $da = $userLogic->update_email_mobile($mobile, $this->user_id, 2 ,$password);
                if (!$da){
                    $this->error('手机已存在');
                }
                if (!empty(session('login_url'))) {
                    $this->success('绑定成功', Url::build(session('login_url')));
                }else{
                    $this->success('绑定成功', Url::build('User/index'));
                }
            }else{
                $this->error('账号已绑定过手机');
            }
        }
        return $this->fetch();
    }




	   /**
     * 验证码验证
     * $id 验证码标示
     */
    private function verifyHandle($id)
    {
        $verify = new Verify();
        if (!$verify->check(input('post.verify_code'), $id ? $id : 'user_login')) {
            $this->error("验证码错误");
        }
    }

    /**
     * 验证码获取
     */
    public function verify()
    {
        //验证码类型
        $type = input('get.type') ? input('get.type') : 'user_login';
        $config = array(
            'fontSize' => 50,
            'length' => 2,
            'useCurve' => true,
            'useNoise' => false,
        );
        $Verify = new Verify($config);
        $Verify->entry($type);
		exit();
    }

    /**
     * 客户服务
     */
    public function customer_service()
    {
        return $this->fetch();
    }

    /**
     * 意见反馈
     */
    public function suggestions()
    {

		if(IS_POST){
			$data['addtime'] = time();
			$data['user_id'] = $this->user_id;
			$data['comments'] = input('comments');
			if(empty($data['comments']))
				exit(json_encode(['status'=>'-1','msg'=>'请正确描述您的宝贵意见.']));

			Db::name('app_opinion')->insert($data);
			exit(json_encode(['status'=>'1','msg'=>'感谢您提供的意见，我们会及时处理.']));

		}
        return $this->fetch();
    }

    /**
     * 优惠券
     */
     public function coupon()
    {
        $now_time = time();
        $user_id = $this->user_id;
        // dump($user_id);die;
        //未使用
        $id = Db::name('coupon_list')
            ->where("uid=$user_id and use_time=0")
            ->field('cid')
            ->select();
        if(!empty($id)){
        $cids = array_column($id, 'cid');
        // echo Db::name('coupon')
            // ->fetchsql()
        
        $unsed_l = Db::name('coupon')
            ->alias('c')
            ->join('coupon_list l','l.cid=c.id')
            ->join('goods g','g.goods_id=c.goods_id')
            ->where('c.id','in',$cids)
            ->where("l.use_end_time>$now_time")
            ->where("l.uid",$user_id)
            ->where("c.supplier_id",0)
            ->field('g.goods_id,g.goods_name,c.id,c.goods_id,c.money,l.use_end_time,l.send_time,l.code,c.coupon_type,c.condition')
            ->select();
        // dump($unsed_l);
        // die;
        $unsed_s = Db::name('coupon')
            ->alias('c')
            ->join('coupon_list l','l.cid=c.id')
            ->join('supplier s','s.supplier_id=c.supplier_id')
            ->where('c.id','in',$cids)
            ->where("l.use_end_time>$now_time")
            ->where("l.uid",$user_id)
            ->field('s.supplier_name,c.supplier_id,c.id,c.goods_id,c.money,l.use_end_time,l.send_time,l.code,c.coupon_type,c.condition,s.supplier_name,s.logo')
            ->select();
        $unsed =array_merge( $unsed_l,$unsed_s);
        
        //已过期
         $expired = Db::name('coupon')->alias('c')->join('coupon_list l','l.cid=c.id')->where('c.id','in',$cids)->where('l.uid',$user_id)->where("l.use_end_time<$now_time || c.use_end_time<$now_time" )->select();

        //已使用
        $d = Db::name('coupon_list')->where("uid=$user_id and use_time>0")->field('cid')->select();
        if(!empty($d)){
        $cids = array_column($d, 'cid');
        $used = Db::name('coupon')->where('id','in',$cids)->select();
        
        $this->assign('use_list',$used);
        }
        
        $this->assign('end_list',$expired);
       
        $this->assign('list',$unsed);
    }
        return $this->fetch();
    }
	

	/**
	 * 店铺收藏列表
	 */
	public function store_attention(){
		
		$userLogic = new UsersLogic();
        $data = $userLogic->get_supplie_collect($this->user_id);
        $this->assign('page', $data['show']);// 赋值分页输出
        $this->assign('store_list', $data['result']);
        if (IS_AJAX) {      //ajax加载更多
            return $this->fetch('ajax_store_attention');
            exit;
        }
        return $this->fetch();
	}

    /**
     * 文章收藏
     */
     public function collect_article()
     {
         return $this->fetch();
     }

    /**
     * 手机找回密码
     * @return mixed
     */
    public function getBackPassword(){
        if(IS_AJAX){
            $type=  I('type') ? I('type') : 1;
            $mobile=input('mobile');
            $scene = I('scene');
            $sender = I('send');
            $mobile = !empty($mobile) ?  $mobile : $sender ;
            $session_id =  I('unique_id' , session_id());
            $data = send_mobile_code($type,$mobile,$session_id);
            return $data;
        }
        return $this->fetch();
    }

    /**
     * 检查手机验证码是否正确
     * @return \think\response\Json
     */
    public function checkmobilecode(){
        if(IS_AJAX){
            $mobile = I("mobile");
            $mobilecode = I("mobilecode");
            $data = Db::name('sms_record')->where('mobile',$mobile)->order('id DESC')->find();
            $sms_time_out =  60;
            //90秒以内不可重复发送
            if(empty($data)) {
                $return_arr = array('status' => -1, 'msg' => '请重新获取验证码');
                return json($return_arr);
//            }elseif((time() - $data['add_time']) > $sms_time_out){
//                $return_arr = array('status'=>-1,'msg'=>'验证码已经超时');
//                return json($return_arr);
//            }
            }elseif( $data['code'] !== $mobilecode){
                $return_arr = array('status'=>-1,'msg'=>'请输入正确的验证码');
                return json($return_arr);
            }else{
                $return_arr = array('status'=>1,'msg'=>'验证码正确','mobile'=>$mobile);
                return json($return_arr);
            }
        }
    }

    /**
     * 更新密码
     * @return mixed|\think\response\Json
     * @throws \think\Exception
     */
    public function newpassw(){
        $mobile = I("mobile");
        $this->assign("mobile",$mobile);
        if(IS_AJAX){
            $mobile = I("mobile");
            $password = encrypt(I("newpassw"));
            $data = Db::name('users')->where('mobile',$mobile)->setField('password',$password);
            if($data){
                $return = array('status'=>1,'msg'=>"修改成功");
            }else{
                $return = array('status'=>-1,'msg'=>"修改失败");
            }
            return json($return);
        }
        return $this->fetch();
    }

    /***
     * 极验初始化
     */
    public function jiyan(){
        if(IS_AJAX){
            $user = "test";
            $type = "h5";
            $ip   = I("ip");
            $data = jiyan_one($user,$type,$ip);
            return $data;
        }
    }

    /**
     * 极验二次验证
     */
    public function jiyan_yz(){
        if(IS_AJAX){
            $ip = I("ip");
            $data = jiyan_two("h5",$ip);
            return $data;
        }
    }

    /**
     *图片上传，base64位压缩，点击即可上传
     */

    public function HeadPic(){
        $base64 = $_POST['head_pic'];
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64, $result)){
            $type = $result[2]; //jpeg
            $IMG = base64_decode(str_replace($result[1], '', $base64)); //返回文件流
        }
        $path ='public/upload/logo/custom/';
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
     * [charity_list 抗疫行动记录列表]
     * @return [type] [description]
     */
    public function charity_list(){
        // $charity_list = Db::name('MedicalCharity')->where('user_id',$this->user_id)->where(['is_purchase'=>1])->where("is_donate like '%爱心捐赠%'")->find();
        $charity_list1 = Db::name('MedicalCharity')->where('user_id',$this->user_id)->where("is_donate",'企业自用')->find();                    //企业自用
        $charity_list2 = Db::name('MedicalCharity')->where('user_id',$this->user_id)->where("is_donate", '爱心捐赠')->select();   //爱心捐赠
        $charity_list3 = Db::name('MedicalCharity')->where('user_id',$this->user_id)->where(['is_purchase'=>2])->find();                //供货
        $charity_list4 = Db::name('MedicalCharity')->where('user_id',$this->user_id)->where(['is_purchase'=>3])->find();                //求助
        if (I('type')==1) {
            $arr = Db::name('MedicalCharity')->where('user_id',$this->user_id)->where("do_id",I('do_id'))->update(['status'=>4]);                    //取消
            if ($arr) {
                return array('status'=>1,'msg'=>'取消成功');
            }else{
                return array('status'=>-1,'msg'=>'取消失败');
            }
        }
        $this->assign('charity_list1',$charity_list1);
        $this->assign('charity_list2',$charity_list2);
        $this->assign('charity_list3',$charity_list3);
        $this->assign('charity_list4',$charity_list4);
        
        return $this->fetch();
    }

    /**
     * [charity_info 抗疫行动详情]
     * @return [type] [description]
     */
    public function charity_info(){
        $do_id = I('do_id');
        $charity_info = Db::name('MedicalCharity')->where('user_id',$this->user_id)->where(['do_id'=>$do_id])->find();               
        if ($charity_info['supply_goods']) {
            $supply = explode(';',$charity_info['supply_goods']);
            foreach ($supply as $key => $value) {
                $supply_goods[] = explode(',',$value);
            }
        }
        if ($charity_info['materials']) {
            $mate = explode(',',$charity_info['materials']);
            foreach ($mate as $key => $value) {
                $materials[] = explode(':',$value);
            }
        }
        if ($charity_info['materials_s']) {
            $mate_s = explode(',',$charity_info['materials_s']);
            foreach ($mate_s as $key => $value) {
                $materials_s[] = explode(':',$value);
            }
        }
        $this->assign('charity_info',$charity_info);
        $this->assign('supply_goods',$supply_goods);
        $this->assign('materials',$materials);
        $this->assign('materials_s',$materials_s);

        return $this->fetch();
    }

    /**
     * [consult 端午资讯页面]
     * @return [type] [description]
     */
    public function consult(){
        if(IS_POST){
            $data = I('');
            $user = $this->user;
            $data['username'] = $user['nickname'];
            $data['user_id']  = $user['user_id'];
            $data['add_time'] = time();
            $Earr = Db::name('goods_consult')->where(['user_id'=>$data['user_id'],'consult_type'=>$data['consult_type']])->whereTime('add_time', 'd')->find();
            if ($Earr) {   //今天的重复留言只修改
                $arr = Db::name('goods_consult')->where(['user_id'=>$data['user_id'],'consult_type'=>$data['consult_type']])->update($data);
            }else{
                $arr = Db::name('goods_consult')->insert($data);
            }
            if ($arr) {
                // sendCode(13728740390,"您好，有一个定制礼品的咨询留言，请登录一礼通后台查看并及时回复。");
                sendCode($this->phone,"您好，有一个定制礼品的咨询留言，请登录一礼通后台查看并及时回复。");
                return array('status'=>1,'msg'=>'留言成功！稍后公司相关人员将与您联系。');
            }
        }
        return $this->fetch();
    }
}
