<?php
/**
 * Created by PhpStorm.
 * User: jiayi
 * Date: 2017/7/4
 * Time: 17:51
 */
namespace ylt\program\controller;
use ylt\home\logic\UsersLogic;
use think\Page;
use think\Request;
use think\Verify;
use think\Db;
use think\Url;
use think\Cache;

class User extends ProgramBase{

    public $user_id = 0;
    public $user = array();

    /*
    * 初始化操作
    */
    public function _initialize()
    {
        parent::_initialize();
		$user = array();
        $user_id = I('user_id');
        // $user_id = 164789;
        if ($user_id) {
            $user = session('user');
            $user = Db::name('users')->where("user_id",$user_id)->find();
            session('user', $user);  //覆盖session 中的 user
            $this->user = $user;
            $this->user_id = $user['user_id'];
            //联系客服手机号
            $phone = Db::name('config')->where("id",56)->field('value')->value('value');
            $this->phone = $phone;
            $this->assign('phone', $phone); 
        }else{
            exit(json_encode(array('result'=>'-1','info'=>'请先登录')));
        }
    }

    /*
     * 用户中心首页
     */
    public function index()
    {
        $user_id =$this->user_id;
        $logic = new UsersLogic();
        $user = $logic->get_info($user_id); //当前登录用户信息
        // dump($user['result']);die;
        $users['nickname'] = $user['result']['nickname'];
        $users['head_pic'] = $user['result']['head_pic'];
        $users['user_id']  = $user['result']['user_id'];
        $users['waitPay']  = $user['result']['waitPay'];
        $users['waitSend']  = $user['result']['waitSend'];
        $users['waitReceive']  = $user['result']['waitReceive'];
        $rs=array('result'=>'1','info'=>'请求成功','user'=>$users);
        exit(json_encode($rs));
    }

	/**
     *  登录
     */
    public function login()
    {
        if ($this->user_id > 0) {
            header("Location: " . Url::build('User/index'));
        }
        $referurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : Url::build("User/index");
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
            //验证码检验
           // $this->verifyHandle('user_reg');
            $username = I('post.mobile', '');
            $password = I('post.password', '');
            $password2 = I('post.password2', '');
            //是否开启注册验证码机制
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
     *图片上传，base64位压缩，点击即可上传 头像
     */

    public function userCustom(){
        $base64 = $_POST['logoImages'];

        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64, $result)){
            $type = $result[2]; //jpeg
            $IMG = base64_decode(str_replace($result[1], '', $base64)); //返回文件流
        }
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
        $rs=array('result'=>'1','info'=>'请求成功','page'=>$show,'lists'=>$order_list,'active'=>'order_list');
        exit(json_encode($rs));

    }

	 /*
     * 订单详情
     */
    public function order_detail()
    {
        $id = I('order_id/d');      //订单id
        $map['order_id'] = $id;
        $map['user_id'] = $this->user_id;
        $order_info = Db::name('order')->where($map)->find();
        $order_info = set_btn_order_status($order_info);  // 添加属性  包括按钮显示属性 和 订单状态显示属性
        if (!$order_info) {
            exit(json_encode(array('result'=>'-1','info'=>'没有获取到订单信息')));
        } 
        //获取订单商品
        $model = new UsersLogic();
        $data = $model->get_order_goods($order_info['order_id']);
        $order_info['goods_list'] = $data['result'];

        //拼单内容
        if ($order_info['is_share'] >= 1) {
            //拼单的用户数量
            $share_the = Db::name('share_the_bill')->alias('s')->join('users u','s.u_id = u.user_id')->where('p_id',$order_info['is_share'])->field('u.head_pic')->select();  
            $share_the['count'] = count($share_the);
            $share = Db::name('share_the_bill')->alias('s')->join('discount_buy b','s.prom_id = b.id')->where('s.id',$order_info['is_share'])->find();
            if ($order_info['pay_status'] == 1) {
                $the_bill  = Db::name('share_the_bill')->where(['p_id'=>$order_info['is_share'],'u_id'=>$map['user_id'],'prom_id'=>$share['prom_id']])->find();
            }
        }
        
        //获取订单操作记录
        $order_action = Db::name('order_action')->where(array('order_id' => $id))->select();

        $rs=array('result'=>'1','info'=>'请求成功','order_info'=>$order_info,'the_bill'=>$the_bill,'share_the'=>$share_the,'order_action'=>$order_action);
        exit(json_encode($rs));
    }


	/**
     * 查看物流
     */
    public function expres()
    {
        $order_id = I('get.order_id/d');
        $delivery = Db::name('shipping_order')->where("order_id", $order_id)->find();
        $delivery['logistics_information'] = json_decode($delivery['logistics_information'],true);
        $rs=array('result'=>'-1','delivery'=>$delivery);
        exit(json_encode($rs));
    }

    /**
     * 确定收货成功
     */
    public function order_confirm()
    {
        $id = I('get.order_id/d', 0);
        $data = confirm_order($id, $this->user_id);
        if ($data['status'] != 1) {
            $rs=array('result'=>'-1','info'=>$data['msg']);
        } else {
            $model = new UsersLogic();
            $order_goods = $model->get_order_goods($id);
            $rs=array('result'=>'1','info'=>'收货成功','order_goods'=>$order_goods);
        }
        exit(json_encode($rs));
    }



    /*
     * 取消订单
     */
    public function cancel_order()
    {
        $id = I('get.order_id/d');
        $logic = new UsersLogic();
        $data = $logic->cancel_order($this->user_id, $id);
        $rs=array('result'=>'1','info'=>'取消成功','data'=>$data);
        exit(json_encode($rs));
    }
    /*
     * 删除订单
     */
    public function del_order()
    {
        $id = I('get.order_id/d');
        $where = "order_id == $id && (order_status == 3 OR order_status == 5) ";
        $del = Db::name('order')->where($where)->delete();
        if ($del) {
            $rs=array('result'=>'1','info'=>'删除成功');
        }else{
            $rs=array('result'=>'-1','info'=>'删除失败');
        }
        exit(json_encode($rs));
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
        $rs=array('result'=>'1','info'=>'请求成功','lists'=>$address_listss);
        exit(json_encode($rs));
    }

    /*
     * 添加地址
     */
    public function add_address()
    {
        if (IS_POST) {
            $datas =array();
            $datas = I('post.');
            $logic = new UsersLogic();
            $data = $logic->add_address($this->user_id, 0, $datas);
            if ($data['status'] != 1){
                $rs = array('result'=>'-1','msg'=>'添加失败');
                exit(json_encode($rs));
            }elseif (I('post.source') == 'orderconfirm') {
                $rs = array('result'=>'5','msg'=>'添加成功');   //orderconfirm
                exit(json_encode($rs));
            }
            $rs = array('result'=>'6','msg'=>'添加成功');       //address_list
            exit(json_encode($rs));
        }
    }

    /*
     * 地址编辑
     */
    public function edit_address()
    {
        $id = I('address_id/d');
        $address = Db::name('user_address')->where(array('address_id' => $id, 'user_id' => $this->user_id))->find();
        if (IS_POST) {
            $logic = new UsersLogic();
            $data = $logic->add_address($this->user_id, $id, I('post.'));
            if ($_POST['source'] == 'orderconfirm') {
                exit(json_encode(array('result'=>'1','info'=>$data['msg'])));
            }else{
                exit(json_encode(array('result'=>'2','info'=>$data['msg'])));
            }
        }
        exit(json_encode(array('result'=>'1','info'=>'请求成功','address'=>$address)));
    }

    // /*
    //  * 设置默认收货地址
    //  */
    // public function set_default()
    // {
    //     $id = I('get.id/d');
    //     $source = I('get.source');
    //     Db::name('user_address')->where(array('user_id' => $this->user_id))->update(array('is_default' => 0));
    //     $row = Db::name('user_address')->where(array('user_id' => $this->user_id, 'address_id' => $id))->update(array('is_default' => 1));
    //     if ($source == 'cart2') {
    //         header("Location:" . Url::build('Cart/cart2'));
    //         exit;
    //     } else {
    //         header("Location:" . Url::build('User/address_list'));
    //     }
    // }

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
        if (!$row){
            exit(json_encode(array('result'=>'-1','info'=>'操作失败')));
        }else{
            exit(json_encode(array('result'=>'1','info'=>'操作成功')));
        }
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
        $list = Db::name('back_order')->where("user_id", $this->user_id)->order("id desc")->field('addtime,goods_id,id,imgs,order_id')->limit("{$page->firstRow},{$page->listRows}")->select();
        $goods_id_arr = get_arr_column($list, 'goods_id');  //获取商品ID
        if (!empty($goods_id_arr)){
            $goodsList = Db::name('goods')->where("goods_id", "in", implode(',', $goods_id_arr))->getField('goods_id,goods_name');
        }
        $state = C('REFUND_STATUS');
        $rs=array('result'=>'1','info'=>'请求成功','goodsList'=>$goodsList,'list'=>$list,'state'=>$state,'page'=>$page->show());
        exit(json_encode($rs));
    }


    /**
     *  退换货详情
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
        $rs=array('result'=>'1','info'=>'请求成功','state'=>$state,'goods'=>$goods,'return_goods'=>$return_goods);
        exit(json_encode($rs));
    }


	 /**
     * 用户收藏列表
     */
    public function collect_list()
    {
        $userLogic = new UsersLogic();
        $data = $userLogic->get_goods_collect($this->user_id);
        $rs=array('result'=>'1','info'=>'请求成功','page'=>$data['show'],'goods_list'=>$data['result']);
        exit(json_encode($rs));
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
     *图片上传，base64位压缩，点击即可上传  定制
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
     * [myCustom 我的定制方案 / 可选择的方案]
     * @return [type] [description]
     */
    public function myCustom(){
        $user_id = $this->user_id;
        $field = "id,company,province,city,district,site,budget,linkman,phone,company_num";
        $custom = Db::name('custom')->where('users_id',$user_id)->where('type',"2019中秋方案")->field($field)->select();
        foreach ($custom as $key => $value) {
            $custom_goods['custom_goods'] =  Db::name('custom_goods')->where('customs_id',$value['id'])->select();
            if ($custom_goods) {
                foreach ($custom_goods['custom_goods'] as $key => $valu) {
                    $value['num'] += $valu['num'];                  //总数量
                    $value['goods_price'] += $valu['goods_price'] * $valu['num'];  //总价格
                }
                $customs[] = array_merge($value,$custom_goods);
            }
        }
        exit(json_encode(array('status'=>1,'msg'=>'请求成功','customs'=>$customs)));
    }

    /**
     * [customDetails 方案详情]
     * @return [type] [description]
     */
    public function customDetails(){
        $id = I('post.customs_id');
        $user_id = $this->user_id;
        $field = "id,company,province,city,district,site,budget,linkman,phone,company_num,add_time";
        $custom = Db::name('custom')->where('id',$id)->where('type',"2019中秋方案")->field($field)->select();
        foreach ($custom as $key => $value) {
            if ($id) {
                $custom_goods =  Db::name('custom_goods')->where('customs_id',$id)->select();
                foreach ($custom_goods as $key => $val) {
                    $goods_id[] = $val['goods_id'];
                    $spec_id[] = $val['spec_id'];
                    $spec[] = $val['spec'];
                    $num[] = $val['num'];
                }
                foreach ($goods_id as $key => $va) {
                    $goods_thumb[] = Db::name('goods')->where('goods_id',$va)->field("goods_thumb")->value('goods_thumb');
                    $price[] = Db::name('goods')->where('goods_id',$va)->field("shop_price")->value('shop_price');
                    $goods_name[] = Db::name('goods')->where('goods_id',$va)->field("goods_name")->value('goods_name');
                }
                for ($i=0; $i <count($goods_id) ; $i++) { 
                    $goods['goods_id'] = $goods_id[$i];        //商品ID
                    $goods['goods_name'] = $goods_name[$i];    //商品名称
                    $goods['goods_thumb'] = $goods_thumb[$i];  //商品缩略图
                    $goods['price'] = $price[$i];              //商品价格
                    $goods['num'] = $num[$i];                  //数量
                    $goods['spec_id'] = $spec_id[$i];          //规格ID
                    $goods['spec'] = $spec[$i];                //规格名称
                    $goods['total_price'] += $goods['price'] * $goods['num']; 
                    $goodss['goods'][] = $goods;
                }
                foreach ($goodss['goods'] as $ke => $valu) {    //总价
                    $value['total_price'] = $valu['total_price'];
                }
                $customs=array_merge($value,$goodss);
            }
        }
        // dump($customs);die;
        if ($customs) {
            exit(json_encode(array('status'=>1,'msg'=>'请求成功','customs_id'=>$customs)));
        }else{
            exit(json_encode(array('status'=>-1,'msg'=>'请求失败，数据不存在')));
        }
    }


    /**
     * [vote 发起投票]
     * @return [type] [description]
     */
    public function vote(){
        $data = I('');
        $data['add_time'] = time();
        $data['into_time'] = strtotime(I('into_time'));
        $data['end_time'] = strtotime(I('end_time'));
        $data['u_id'] = $this->user_id;
        if (empty($data['into_time']) || empty($data['end_time']) || empty($data['customs_id'])) {
            exit(json_encode(array('status'=>-2,'msg'=>'发起失败,发起内容及时间不可为空')));
        }
        $vote_id = Db::name('custom_obtain')->insertgetId($data);
        if ($vote_id) {
            exit(json_encode(array('status'=>1,'msg'=>'发起成功','vote_id'=>$vote_id)));
        }else{
            exit(json_encode(array('status'=>-1,'msg'=>'发起失败')));
        }
    }


    /**
     * [voteDetails 我发起的投票列表]
     * @return [type] [description]
     */
    public function voteList(){
        $user_id = $this->user_id;
        $obtain = Db::name('custom_obtain')->field('id,title,customs_id,into_time,end_time')->where('u_id',$user_id)->select();
        if($obtain){
            foreach ($obtain as $key => $value) {
            $customs_id = explode(',',$value['customs_id']);
            $custom[] = Db::name('custom')->field('id,company,poll,customs_num')->where('id', 'in', $customs_id)->select();
            }
            foreach ($custom as $ke => $va) {
                foreach ($obtain as $key => $valu) {
                    if ($ke == $key) {
                        $val['title'] = $valu['title'];
                        $val['oid'] = $valu['id'];
                        // $val['company'] = $va[0]['company'];
                        $val['into_time'] = $valu['into_time'];
                        $val['end_time'] = $valu['end_time'];
                        $v_val[] = $val;
                    }
                }
                foreach ($va as $k => $v) {       //统计票数存入字段
                    if (!empty($v['id'])) {
                        $count = Db::name('custom_staff')->where(['obtaim_id'=>$val['oid'],'custom_id'=>$v['id']])->count();
                    }
                    if (!empty($count)) {
                        Db::name('custom')->where('id', $va['id'])->update(['poll'=>$count]);
                    }
                }
                $list[] = $va;
            }
            for ($i=0; $i <count($list) ; $i++) { 
                $lists[$i]['list'] =  $list[$i];
                $customs[] = array_merge($lists[$i],$v_val[$i]);
            }
            exit(json_encode(array('status'=>1,'msg'=>'请求成功','customs' => $customs)));
        }else{
            exit(json_encode(array('status'=>-1,'msg'=>'请求失败，数据不存在')));
        }
    }

    /**
     * [voteDetails 查看投票人员信息]
     * @return [type] [description]
     */
    public function voteDetails(){
        $obtaim_id = I('obtaim_id');   //发起投票的ID
        $custom_id = I('custom_id');   //方案ID
        $staff = Db::name('custom_staff')->where(['obtaim_id' => $obtaim_id,'custom_id' => $custom_id])->select();
        exit(json_encode(array('status'=>1,'msg'=>'请求成功','staff' => $staff)));
    }

    /**
     * [voteWed 投票页面]
     * @return [type] [description]
     */
    public function voteWed(){
        $obtaim_id = I('obtaim_id');   //发起投票的ID
        $user_id = $this->user_id;
        $staff = Db::name('custom_staff')->where(['u_id'=>$user_id,'obtaim_id'=>$obtaim_id])->field('custom_id')->select();         //查找当前用户是否有过投票记录
        $obtain = Db::name('custom_obtain')->where('id',$obtaim_id)->find();    
        $customs_id = explode(',',$obtain['customs_id']);
        $custom[] = Db::name('custom')->field('id,company,poll,customs_num,budget')->where('id', 'in', $customs_id)->select();
        foreach ($custom as $ke => $value) {
            //投票信息
            if ($ke == $key) {
                $value['title'] = $obtain['title'];
                $value['oid'] = $obtain['id'];
                $value['hide_type'] = $obtain['hide_type'];         //是否隐藏预算    0否 1是
                $value['obtain_type'] = $obtain['obtain_type'];     //是否获取投票人信息 0否 1是
                if ($value['obtain_type'] == 1) {
                    $value['name'] = $obtain['name'];
                    $value['phone'] = $obtain['phone'];
                    $value['department'] = $obtain['department'];
                    $value['number'] = $obtain['number'];
                }
                $value['selection'] = $obtain['selection'];         //是否支持多选
                $value['company'] = $value[0]['company'];
                //获取商品
                foreach ($value as $key => $va) {
                    if (!empty($va['id'])) {
                    $custom_goods[] =  Db::name('custom_goods')->where('customs_id',$va['id'])->field("goods_id,num,goods_thumb,goods_name")->select();
                    }
                }
            $customs = $value;
            }
        }
        //投过的方案ID
        foreach ($staff as $s => $v) {
            foreach ($v as $y => $lue) {
                $obtain_goods[$y][] = $lue;
            }
        }
        //处理数据
        foreach ($customs as $ke => $valu) {
            foreach ($custom_goods as $k => $val) {
                if ($ke == $k) {
                    if (is_array($valu)) {
                        $valu[] = $val;
                        $obtain_goods['custom'][] = $valu;
                    }else{
                        $obtain_goods[$ke]= $valu;
                    }
                }
            }
        }
        exit(json_encode(array('status'=>1,'msg'=>'请求成功','obtain_goods' => $obtain_goods)));
    }

    /**
     * [voteClick 点击投票]
     * @return [type] [description]
     */
    public function voteClick(){
        $data = I('');                  //各种ID及投票人填写的信息
        // $data['obtaim_id'] = 1;         //发起投票的ID
        // $data['custom_id'] = 6;         //获取投票的方案ID
        $data['u_id'] = $this->user_id;
        $obtain = Db::name('custom_obtain')->where('id',$data['obtaim_id'])->find();    //发起投票的设置
        if ($obtain['obtain_type'] == 1) {
            if ($obtain['name'] == 1) {
                if (empty($data['name'])) {
                    exit(json_encode(array('status'=>-4,'msg'=>'姓名不可为空')));
                }
            }
            if ($obtain['phone'] == 1) {
                $check_mobile = check_mobile($data['phone']);
                if (empty($check_mobile)) {
                    exit(json_encode(array('status'=>-3,'msg'=>'手机号码格式有误')));
                }
            }
            if ($obtain['department'] == 1) {
                if (empty($data['department'])) {
                    exit(json_encode(array('status'=>-5,'msg'=>'部门不可为空')));
                }
            }
            if ($obtain['number'] == 1) {
                if (empty($data['number'])) {
                    exit(json_encode(array('status'=>-6,'msg'=>'工号不可为空')));
                }
            }
        }
        $array = Db::name('custom_staff')->where(['u_id'=>$data['u_id'],'obtaim_id'=>$data['obtaim_id']])->select();
        $selection = Db::name('custom_obtain')->field('selection')->where('id',$data['obtaim_id'])->value('selection');
        if ($array and $selection == 0) {
            exit(json_encode(array('status'=>-1,'msg'=>'您已经投过票了')));
        }
        if ($array and $selection==1) {   //支持多选
            foreach ($array as $key => $value) {
                if ($value['custom_id'] == $data['custom_id']) {
                    exit(json_encode(array('status'=>-2,'msg'=>'您已为该方案投过票了')));
                }
            }
        }
        $obtain = Db::name('custom_staff')->insert($data);
        if ($obtain) {
            $update = [
                'poll' => ['exp','poll+'."1".''],
            ];
            Db::name('custom')->where('id',$data['custom_id'])->update($update);
            exit(json_encode(array('status'=>1,'msg'=>'投票成功')));
        }else{
            exit(json_encode(array('status'=>-1,'msg'=>'投票失败')));
        }
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
                exit(json_encode(array('status'=>1,'msg'=>'留言成功！稍后公司相关人员将与您联系。')));
            }
        }
        exit(json_encode(array('status'=>1,'phone'=>$this->phone)));
    }
}
