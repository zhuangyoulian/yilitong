<?php
/**
 * Created by PhpStorm.
 * Charity: jiayi
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

class Charity extends MobileBase{

    public $user_id = 0;
    public $user = array();

    /*
    * 初始化操作
    */
    public function _initialize()
    {
        parent::_initialize();
		$user = array();
        if (I('u_id')) {
            $this->user_id = I('u_id');
        }else{
    		if (session('?user')) {
                $user = session('user');
                $user = Db::name('users')->where("user_id", $user['user_id'])->find();
                session('user', $user);  //覆盖session 中的 user
                $this->user = $user;
                $this->user_id = $user['user_id'];
                $this->assign('user', $user); //存储用户信息
                //联系客服手机号
                $phone = Db::name('config')->where("id",56)->field('value')->value('value');
                $this->assign('phone', $phone); 
            }
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

    /**
     * [MedicalCharity 问卷]
     */
    public function MedicalCharity(){
        //分享微信
        $jssdk = new JSSDKSS("wx218ea80c35624c8a", "77380763d58d20f6bbcb18d469b40f03");
        $signPackage = $jssdk->GetSignPackage();

        $charity = Db::name('MedicalCharity')->where('user_id',$this->user_id)->order('do_id desc')->find();
        if ($charity['supply_goods']) {
            $supply_goods = explode(';',$charity['supply_goods']);
            foreach ($supply_goods as $key => $value) {
                $supply_goods_list[] = explode(',',$value);
            }
            foreach($supply_goods_list as $k=>$v){
                if($v[0]==''){
                    unset($supply_goods_list[$k]);
                }
            }
        }
        //受捐方
        $charity_assign_list = Db::name('MedicalCharity')->where(['is_purchase'=>3,'status'=>2])->field('do_company')->select(); 
        $this->assign('charity_assign_list',$charity_assign_list);
        $this->assign('signPackage',$signPackage);
        $this->assign('supply_goods_list',$supply_goods_list);
        $this->assign('charity',$charity);
        
        return $this->fetch();
    }
    
    /**
     * [add_charity 问卷提交]
     */
    public function add_charity(){
        $data = I('');
        if ($data['is_purchase']==1) {  //购买
            $add['user_id'] = $this->user_id;
            $add['is_purchase'] = $data['is_purchase'];
            $add['is_donate'] = $data['is_donate'];
            $add['budget'] = $data['budget'];
            $add['do_name'] = $data['do_name'];
            $add['do_phone'] = $data['do_phone'];
            $add['do_company'] = $data['do_company'];
            $add['do_address'] = $data['do_address'];
            $add['comment'] = $data['comment'];
            $add['materials'] = $data['materials'];
            $add['supply_goods']    = '/';
            if ($data['is_donate'] == '企业自用') {
                $add['beneficiaries'] = '/';
            }else{
                $add['beneficiaries'] = $data['beneficiaries'];
            }
            $add['add_time'] = time();
            if (Db::name('MedicalCharity')->where('user_id',$this->user_id)->where(['is_purchase'=>1,'is_donate'=>'企业自用'])->find()) {
                Db::name('MedicalCharity')->where('user_id',$this->user_id)->where(['is_purchase'=>1,'is_donate'=>'企业自用'])->delete();
                Db::name('MedicalCharity')->insert($add);
            }else{
                Db::name('MedicalCharity')->insert($add);
            }
        }elseif ($data['is_purchase']==2){                          //供货
            $add['user_id'] = $this->user_id;
            $add['is_purchase'] = $data['is_purchase'];
            $add['do_company']  = $data['do_company'];
            $add['do_phone']    = $data['do_phone'];
            $add['beneficiaries'] = '/';
            $add['is_donate'] = '/';
            $add['budget'] = '/';
            $add['materials'] = '/';
            $add['do_name'] = '/';
            $add['do_address'] = '/';
            $add['comment'] = '/';
            $add['add_time']    = time();
            for ($i=0; $i <count($data['supply_goods_0']) ; $i++) { 
                $supply_goods[] = $data['supply_goods_0'][$i].','.$data['supply_goods_1'][$i].','.$data['supply_goods_2'][$i].','.$data['supply_goods_3'][$i];
            }
            if ($supply_goods) {
                $supply_goods = implode(';',$supply_goods);
            }
            $add['supply_goods']    = $supply_goods;
            if (Db::name('MedicalCharity')->where('user_id',$this->user_id)->where('is_purchase',2)->find()) {
                Db::name('MedicalCharity')->where('user_id',$this->user_id)->where('is_purchase',2)->delete();
                Db::name('MedicalCharity')->insert($add);
            }else{
                Db::name('MedicalCharity')->insert($add);
            }
        }elseif ($data['is_purchase']==3){                          //求助
            $add['user_id'] = $this->user_id;
            $add['is_purchase'] = $data['is_purchase'];
            $add['do_company']  = $data['do_company'];
            $add['do_name']     = $data['do_name_s'];
            $add['do_phone']    = $data['do_phone'];
            $add['do_address']  = $data['do_address_s'];
            $add['materials_s']   = $data['materials_s'];
            $add['comment']     = $data['comment_s'];
            $add['beneficiaries'] = '/';
            $add['is_donate'] = '/';
            $add['budget'] = '/';
            $add['supply_goods']    = '/';
            $add['add_time']    = time();
            if (Db::name('MedicalCharity')->where('user_id',$this->user_id)->where('is_purchase',3)->find()) {
                Db::name('MedicalCharity')->where('user_id',$this->user_id)->where('is_purchase',3)->delete();
                Db::name('MedicalCharity')->insert($add);
            }else{
                Db::name('MedicalCharity')->insert($add);
            }
        }
    }

    /**
     * [zhuanqu 专区]
     * @return [type] [description]
     */
    public function zhuanqu(){
        //捐赠企业的数量
        $count = Db::name('MedicalCharity')->where("is_donate like '%爱心捐赠%'")->where('status',2)->count();  
        //捐赠展示
        $charity = Db::name('MedicalCharity')->where("is_donate",'爱心捐赠')->order('do_id desc')->where('status',2)->limit(5)->select();  
        //点赞记录
        foreach ($charity as $key => $value) {
            $arr = DB::name('medical_like_log')->where('user_id',$this->user_id)->where('do_id',$value['do_id'])->find();
            if ($arr) {
                $value['like_log'] = 1;
            }else{
                $value['like_log'] = 0;
            }
            $charity_s[] = $value;
        }
        //专区首页推荐的4个商品
        $goods = Db::name('Goods')->where(['cat_id'=>1118,'is_on_sale'=>1,'is_delete'=>0,'examine'=>1])->field('goods_id,goods_name,shop_price,goods_thumb')->order('sort desc,goods_id desc')->limit(4)->select();
        //滚动求助
        $purchase = Db::name('MedicalCharity')->where(["is_purchase"=>3,'status'=>2])->order('do_id desc')->limit(3)->select();
        foreach ($purchase as $ke => $valu) {
            if ($valu['materials_s']) {
                $mate_s = explode(',',$valu['materials_s']);
                foreach ($mate_s as $key => $value) {
                    if ($key<=1) {
                        $materials_s[] = explode(':',$value);
                    }else{
                        $materials_ss[] = explode(':',$value);
                    }
                }
            }
        }
        if ($materials_s) {
           $materials_s = assoc_unique_arr($materials_s);
        }
        if ($materials_ss) {
           $materials_ss = assoc_unique_arr($materials_ss);
        }
        //推荐商品
        $brand_roll = Db::name('ad')->where('pid=55 and enabled=1')->where("end_time>'$a'")->order('orderby DESC')->limit('0,3')->select();

        $this->assign('count',$count);
        $this->assign('goods',$goods);
        $this->assign('charity',$charity_s);
        $this->assign('purchase',$purchase);
        $this->assign('materials_s',$materials_s);
        $this->assign('materials_ss',$materials_ss);
        $this->assign('like_log',$like_log);
        $this->assign('brand_roll',$brand_roll);
        return $this->fetch();
    }

    /**
     * [like_log 更新点赞]
     * @return [type] [description]
     */
    public function like_log(){
        if (I('is_like')==1) {
            Db::name('MedicalCharity')->where('do_id',I('do_id'))->update(['like'=>I('like_log')]);
            $add['do_id']   = I('do_id');
            $add['user_id'] = $this->user_id;
            $add['add_time']= time();
            if (DB::name('medical_like_log')->where(['do_id'=>I('do_id'),'user_id'=>$this->user_id])->find()) {
                 $arr = DB::name('medical_like_log')->where(['do_id'=>I('do_id'),'user_id'=>$this->user_id])->delete();
                if ($arr) {
                    return array( 'status' => 1,'msg' => '点赞取消成功',);
                }
            }else{
                $arr = DB::name('medical_like_log')->insert($add);
                if ($arr) {
                    return array( 'status' => 1,'msg' => '点赞成功',);
                }
            }
        }
    }

    /**
     * [charity_assign 定向捐赠]
     * @return [type] [description]
     */
    public function charity_assign(){
        $do_id = I('do_id');
        $charity_assign_list = Db::name('MedicalCharity')->where(['is_purchase'=>3,'status'=>2])->field('do_company')->select();               
        $charity_assign = Db::name('MedicalCharity')->where(['do_id'=>$do_id,'is_purchase'=>3,'status'=>2])->find();               
        if (IS_POST) {
            $add = I('');
            $add['user_id'] = $this->user_id;
            $add['add_time'] = time();
            $arr = Db::name('MedicalCharity')->insert($add);
            if ($arr) {
                return array('status'=>1,'msg'=>"申请成功");
            }
        }
        $this->assign('charity_assign_list',$charity_assign_list);
        $this->assign('charity_assign',$charity_assign);
        return $this->fetch();
    }

    /**
     * [charity_list 捐赠企业展示列表页]
     * @return [type] [description]
     */
    public function charity_list(){
        $charity_list = Db::name('MedicalCharity')->where("is_donate",'爱心捐赠')->where('status',2)->order('do_id desc')->select(); 
        foreach ($charity_list as $key => $value) {
            $arr = DB::name('medical_like_log')->where('user_id',$this->user_id)->where('do_id',$value['do_id'])->find();
            if ($arr) {
                $value['like_log'] = 1;
            }else{
                $value['like_log'] = 0;
            }
            $charity_s[] = $value;
        } 
        $this->assign('charity_list',$charity_s);
        return $this->fetch();
    }

    /**
     * [charity_help 求助列表页面]
     * @return [type] [description]
     */
    public function charity_help(){
        //滚动求助
        $purchase = Db::name('MedicalCharity')->where("is_purchase",3)->where('status',2)->order('do_id desc')->select();
        foreach ($purchase as $ke => $valu) {
            if ($valu['materials_s']) {
                $mate_s = explode(',',$valu['materials_s']);
                foreach ($mate_s as $key => $value) {
                    if ($key<=1) {
                        $materials_s[] = explode(':',$value);
                    }else{
                        $materials_ss[] = explode(':',$value);
                    }
                }
            }
        }
        if ($materials_s) {
           $materials_s = assoc_unique_arr($materials_s);
        }
        if ($materials_ss) {
           $materials_ss = assoc_unique_arr($materials_ss);
        }
        $this->assign('purchase',$purchase);
        $this->assign('materials_s',$materials_s);
        $this->assign('materials_ss',$materials_ss);
        return $this->fetch();
    }


    /**
     * [contract_shouye 合同列表首页]
     * @return [type] [description]
     */
    public function contract_shouye(){
        return $this->fetch();
    }
    public function ajax_contract_shouye(){
        $id = I('id');
        $contract_form_list = Db::name('contract_form_list')->where('d_id',$id)->select();
        $contract_template = Db::name('contract_template')->where('p_id',$id)->select();
        foreach ($contract_template as $key => $value) {
            foreach ($contract_form_list as $ke => $valu) {
                if ($value['name'] == $valu['contract_type']) {
                    $data[$key]['d_id'] = $valu['d_id'];
                    $data[$key]['p_id'] = $valu['p_id'];
                    $data[$key]['company'] = $valu['company'];
                    $data[$key]['contract_type'] = $valu['contract_type'];
                    $data[$key]['data'][$ke]['contract_name'] = $valu['contract_name'];
                    $data[$key]['data'][$ke]['id'] = $valu['id'];
                    $data[$key]['data'][$ke]['is_show'] = $valu['is_show'];
                    $data[$key]['data'][$ke]['image'] = $valu['image'];
                    $data[$key]['data'][$ke]['excel_url'] = $valu['excel_url'];
                    $data[$key]['data'][$ke]['describe'] = $valu['describe'];
                    $data[$key]['data'][$ke]['excel_name'] = $valu['excel_name'];
                }
            }
        }
        $this->assign('id',$id);
        $this->assign('contract',$data);
        return $this->fetch();
    }

    /**
     * [contract_search 合同搜索]
     * @return [type] [description]
     */
    public function contract_search(){
        return $this->fetch();
    }
    public function ajax_contract_search(){
        if(IS_POST){
            $keyword = I('keyword');
            $select = Db::name('contract_form_list')->where('contract_name','like','%'.$keyword.'%')->select();
            $this->assign('select',$select);
        }
        return $this->fetch();
    }

    /**
     * [contract_me 我的合同中心]
     * @return [type] [description]
     */
    public function contract_me(){
        return $this->fetch();
    }
    /**
     * [contract_totalhetong 合同列表]
     * @return [type] [description]
     */
    public function contract_totalhetong(){
        $type = $_GET['type']?$_GET['type']:0;

        if (empty($type)) {
            $totalhetong = Db::name('contract_list')->where(['user_id'=>$this->user_id])->order('id desc')->select();
        }else{
            if ($type==3) {
                $totalhetong = Db::name('contract_list')->where(['user_id'=>$this->user_id])->order('id desc')->where('type=3 or type=4')->select();
            }else{
                $totalhetong = Db::name('contract_list')->where(['user_id'=>$this->user_id,'type'=>$type])->order('id desc')->select();
            }
        }
        // dump($totalhetong);die;
        $this->assign('type',$type);
        $this->assign('totalhetong',$totalhetong);
        return $this->fetch();
    }

    /**
     * [contract_preview 合同预览公用方法]
     * @return [type] [description]
     */
    public function contract_preview(){
        $id = $_GET['c_id'];
        $find = Db::name('contract_form_list')->where('id',$id)->find();
        $find['preview_images'] = array_reverse(array_filter(explode(';',$find['preview_images'])));
        $this->assign('find',$find);
        return $this->fetch();
    }

    /**
     * [contract_htong 医用防护合同模板]
     * @return [type] [description]
     */
    public function contract_htong(){
        return $this->fetch();
    }
    /**
     * [contract_update 医用防护合同编辑页]
     * @return [type] [description]
     */
    public function contract_update(){
        $id    = $_GET['id'];
        $u_id  = $_GET['u_id'];
        if ($id) {
            $find = Db::name('contract_list')->where('id',$id)->where('user_id',$this->user_id)->find();
            $productlist1   =  array_filter(explode(',',$find['productlist1']));         //产品信息1
            $productlist2   =  array_filter(explode(',',$find['productlist2']));         //产品信息2
            $sendgoodslist1 =  array_filter(explode(',',$find['sendgoodslist1']));       //发货排期1
            $sendgoodslist2 =  array_filter(explode(',',$find['sendgoodslist2']));       //发货排期2
            $a_address      =  array_filter(explode(',',$find['a_address']));             //住所地
            $lx_totaladdress=  array_filter(explode(',',$find['lx_totaladdress']));       //收货地址
            $th_totaladdress=  array_filter(explode(',',$find['th_totaladdress']));       //提货地址
            $this->assign('id',$id);
            $this->assign('find',$find);
            $this->assign('productlist1',$productlist1);
            $this->assign('productlist2',$productlist2);
            $this->assign('sendgoodslist1',$sendgoodslist1);
            $this->assign('sendgoodslist2',$sendgoodslist2);
            $this->assign('a_address',$a_address);
            $this->assign('lx_totaladdress',$lx_totaladdress);
            $this->assign('th_totaladdress',$th_totaladdress);
            $this->assign('contract_num',$find['contract_num']);
        }else{
            $today = strtotime(date("Y-m-d"),time());
            $time  = date("Ymd", $today);
            $count = Db::name('contract_list')->where("add_time > $today")->count()+1;
            $contract_num = 'HL'.$time.'-'.$count.chr(rand(65, 90));
            $list = Db::name('contract_list')->where(['user_id'=>$this->user_id,'data_type'=>'医疗防护代采合同'])->order('id desc')->find();
            if (!empty($list) && $list['is_accomplish'] == 0) {
                $this->assign('id',$id);
                $this->assign('is_accomplish',$list['id']);
            }
            $this->assign('contract_num',$contract_num);
        }
        $this->assign('id',$id);
        $this->assign('u_id',$u_id);
        if (IS_POST) {
            $data = I('');
            // if (!empty(I('strstr'))) {
            //     $data['img_url']        =  $this->GetPDF(I('strstr'),I('hetongID'));
            // }
            if (!empty($data['id'])) {
                $array =  Db::name('contract_list')->where('id',$data['id'])->where('user_id',$this->user_id)->find();
                $data['contract_num']   =  $array['contract_num'];               //合同编号
                if (I('contract')) {
                    @unlink ('.'.$array['img_url']);                            //删除PDF文件
                }
            }
            if (!empty(I('contract'))) {
                $data['img_url']        =  '/'.wkhtmltopdf(I('contract'),I('hetongID'));  
            }
            $data['user_id']        =  $this->user_id;
            $data['data_type']      =  "医疗防护代采合同";
            $data['project_name']   =  "医疗防护代采合同";
            $data['add_time']       =  time();
            if ($data['productlist']) {
                //产品信息1 2
                $data['productlist1']   =  $data['productlist'][0]?implode(',',$data['productlist'][0]):'';
                $data['productlist2']   =  $data['productlist'][1]?implode(',',$data['productlist'][1]):'';
            }  
            if ($data['sendgoodslist']) {
                //发货排期1 2
                $data['sendgoodslist1'] =  $data['sendgoodslist'][0]?implode(',',$data['sendgoodslist'][0]):'';
                $data['sendgoodslist2'] =  $data['sendgoodslist'][1]?implode(',',$data['sendgoodslist'][1]):'';
            }       
            // $data['img_url']        =  pdf_file(I('contract'),I('hetongID'));  //保存路径pdf_file(HTML,合同编号)
            if (!empty($data['id'])) {
                $sql = Db::name('contract_list')->where('id',$data['id'])->where('user_id',$this->user_id)->update($data);
                if (!$sql) {
                    return array('status'=>-1,'msg'=>"修改失败");
                }else{
                    return array('status'=>1,'img_url'=>$data['img_url'],'id'=>$data['id']);
                }
            }else{
                $data['contract_num']   =  I('hetongID');               //合同编号
                $sql = Db::name('contract_list')->insertGetid($data);
                if (!$sql) {
                    return array('status'=>-1,'msg'=>"添加失败");
                }else{
                    return array('status'=>1,'img_url'=>$data['img_url'],'id'=>$sql);
                }
            }
        }
        return $this->fetch();
    }
    // //  PDF文件保存
    // public function GetPDF($base64,$title){
    //     $PDF = explode(',',$base64);
    //     $IMG = base64_decode($PDF[1]);   //base64位转码，还原图片
    //     $path ='public/pdf/contract/'.date("Ymd",time()).'/';
    //     if (!file_exists($path)){
    //         mkdir($path,0777,true);
    //     }//如果地址不存在，创建地址
    //     $picname=$path.$title.'.pdf';
    //     $picname_s="/".$picname;
    //     file_put_contents($picname,$IMG);
    //     $picname2=$title.'.pdf';
    //     return $picname_s;
    // }
    //  Excel文件保存
    public function GetExcel($base64,$title){
        $PDF = explode(',',$base64);
        $IMG = base64_decode($PDF[1]);   //base64位转码，还原图片
        $path ='public/pdf/contract/'.date("Ymd",time()).'/xlsx/';
        if (!file_exists($path)){
            mkdir($path,0777,true);
        }//如果地址不存在，创建地址
        $picname=$path.$title.'.xlsx';
        $picname_s="/".$picname;
        file_put_contents($picname,$IMG);
        $picname2=$title.'.xlsx';
        return $picname_s;
    }

    /**
     * [contract_cghtong 采购合同]
     * @return [type] [description]
     */
    // public function contract_cghtong(){
    //     return $this->fetch();
    // }
    public function contract_newpdf(){
        if (IS_POST) {
            $data = I('');
            if ($data['cashMethod'] == '一次性收全款') {
                $data['cash_time2'] = '';
                $data['cash_time3'] = '';
            }elseif($data['cashMethod'] == '分期收款'){
                $data['cash_time1'] = '';
            }
            // if (!empty(I('strstr'))) {
            //     $data['img_url']        =  $this->GetPDF(I('strstr'),I('hetongID'));
            // }
            if (!empty($data['id'])) {
                $array =  Db::name('contract_list')->where('id',$data['id'])->where('user_id',$this->user_id)->find();
                $data['contract_num']   =  $array['contract_num'];               //合同编号
                if (I('contract')) {
                    @unlink ('.'.$array['img_url']);                        //删除PDF文件
                }
                if (I('attachment')) {
                    @unlink ('.'.$array['attachment']);                     //删除xlsx文件
                }else{
                    if ($array['attachment']) {
                        $data['attachment'] = $array['attachment'];
                    }
                }
            }
            if (!empty(I('contract'))) {
                $data['img_url']        =  '/'.wkhtmltopdf(I('contract'),I('hetongID'));    //生成并保存PDF文件
            }
            if (!empty(I('attachment'))) {
                $data['attachment']     =  $this->GetExcel(I('attachment'),I('hetongID'));  //保存xlsx文件
            }
            $data['user_id']        =  $this->user_id;
            $data['data_type']      =  "采购合同";
            $data['add_time']       =  time();
            if (!empty($data['id'])) {
                $sql = Db::name('contract_list')->where('id',$data['id'])->where('user_id',$this->user_id)->update($data);
                if (!$sql) {
                    return array('status'=>-1,'msg'=>"修改失败");
                }else{
                    return array('status'=>1,'img_url'=>$data['img_url'],'id'=>$data['id']);
                }
            }else{
                $data['contract_num']   =  I('hetongID');               //合同编号
                $sql = Db::name('contract_list')->insertGetid($data);
                if (!$sql) {
                    return array('status'=>-1,'msg'=>"添加失败");
                }else{
                    return array('status'=>1,'img_url'=>$data['img_url'],'id'=>$sql);
                }
            }
        }
        $id['id']    = $_GET['id'];
        $id['d_id']  = $_GET['d_id'];
        $id['p_id']  = $_GET['p_id'];
        $id['c_id']  = $_GET['c_id'];
        $id['u_id']  = $_GET['u_id'];
        if ($id['id']) {
            $find = Db::name('contract_list')->where('id',$id['id'])->where('user_id',$this->user_id)->find();
            $company_totaladdress =  array_filter(explode(',',$find['company_totaladdress']));    //公司地址
            $goods_totaladdress   =  array_filter(explode(',',$find['goods_totaladdress']));      //收货地址
            $find['attachment'] = substr($find['attachment'],35);   //截取文件名
            $this->assign('contract_num',$find['contract_num']);
            $this->assign('company_totaladdress',$company_totaladdress);
            $this->assign('goods_totaladdress',$goods_totaladdress);
            $this->assign('id',$id);
            $this->assign('find',$find);
        }else{
            $today = strtotime(date("Y-m-d"),time());
            $time  = date("Ymd", $today);
            $count = Db::name('contract_list')->where("add_time > $today")->count()+1;
            $contract_num = 'HL'.$time.'-'.$count.chr(rand(65, 90));
            $list = Db::name('contract_list')->where(['user_id'=>$this->user_id,'data_type'=>'采购合同'])->order('id desc')->find();
            if (!empty($list) && $list['is_accomplish'] == 0) {
                $this->assign('id',$id);
                $this->assign('is_accomplish',$list['id']);
            }
            $this->assign('contract_num',$contract_num);
        }
        $contract_form = Db::name('contract_form_list')->where('id',$id['c_id'])->find();   //合同模板后台信息
        $contract_corporate = Db::name('contract_corporate')->where('id',$id['d_id'])->find();   //合同公司信息
        $this->assign('id',$id);
        $this->assign('contract_corporate',$contract_corporate);
        $this->assign('contract_form',$contract_form);
        return $this->fetch();
    }        

    /**
     * [contract_transfer 表单链接中转]
     * @return [type] [description]
     */
    public function contract_transfer(){
        if ($_GET['id']) {
            if (!$_GET['Lo_id']) {      //医护表单
            $this->redirect('/Mobile/Charity/contract_update/d_id/'.$_GET['d_id'].'/p_id/'.$_GET['p_id'].'/c_id/'.$_GET['c_id'].'/id/'.$_GET['id'].'/u_id/'.$_GET['u_id']);
            }elseif($_GET['Lo_id'] == 8 or $_GET['Lo_id'] == 14 or $_GET['Lo_id'] == 20 or $_GET['Lo_id'] == 26 or $_GET['Lo_id'] == 32 or $_GET['Lo_id'] == 38){   //采购表单
                $this->redirect('/Mobile/Charity/contract_newpdf/d_id/'.$_GET['d_id'].'/p_id/'.$_GET['p_id'].'/c_id/'.$_GET['c_id'].'/id/'.$_GET['id'].'/u_id/'.$_GET['u_id']);
            }elseif ($_GET['Lo_id'] == 10 or $_GET['Lo_id'] == 16 or $_GET['Lo_id'] == 22 or $_GET['Lo_id'] == 28 or $_GET['Lo_id'] == 34 or $_GET['Lo_id'] == 40) {    //委托生产表单
                $this->redirect('/Mobile/Charity/contract_entrust/d_id/'.$_GET['d_id'].'/p_id/'.$_GET['p_id'].'/c_id/'.$_GET['c_id'].'/id/'.$_GET['id'].'/u_id/'.$_GET['u_id']);
            }
        }else{
            if (!$_GET['Lo_id']) {      //医护表单
            $this->redirect('/Mobile/Charity/contract_update/d_id/'.$_GET['d_id'].'/p_id/'.$_GET['p_id'].'/c_id/'.$_GET['c_id']);
            }elseif($_GET['Lo_id'] == 8 or $_GET['Lo_id'] == 14 or $_GET['Lo_id'] == 20 or $_GET['Lo_id'] == 26 or $_GET['Lo_id'] == 32 or $_GET['Lo_id'] == 38){   //采购表单
                $this->redirect('/Mobile/Charity/contract_newpdf/d_id/'.$_GET['d_id'].'/p_id/'.$_GET['p_id'].'/c_id/'.$_GET['c_id']);
            }elseif ($_GET['Lo_id'] == 10 or $_GET['Lo_id'] == 16 or $_GET['Lo_id'] == 22 or $_GET['Lo_id'] == 28 or $_GET['Lo_id'] == 34 or $_GET['Lo_id'] == 40) {    //委托生产表单
                $this->redirect('/Mobile/Charity/contract_entrust/d_id/'.$_GET['d_id'].'/p_id/'.$_GET['p_id'].'/c_id/'.$_GET['c_id']);
            }
        }
    }
    /**
     * [contract_entrust 委托合同表单页面]
     * @return [type] [description]
     */
    public function contract_entrust(){
        if (IS_POST) {
            $data = I('');
            if ($data['cashMethod'] == '一次性收全款') {
                $data['cash_time2'] = '';
                $data['cash_time3'] = '';
            }elseif($data['cashMethod'] == '分期收款'){
                $data['cash_time1'] = '';
            }
            // if (!empty(I('strstr'))) {
            //     $data['img_url']        =  $this->GetPDF(I('strstr'),I('hetongID'));
            // }
            if (!empty($data['id'])) {
                $array =  Db::name('contract_list')->where('id',$data['id'])->where('user_id',$this->user_id)->find();
                $data['contract_num']   =  $array['contract_num'];               //合同编号
                if (I('contract')) {
                    @unlink ('.'.$array['img_url'] );                     //删除PDF文件
                }
            }

            if (!empty(I('contract'))) {
                $data['img_url']        =  '/'.wkhtmltopdf(I('contract'),I('hetongID'));  
            }
            if (!empty(I('attachment'))) {
                $data['attachment']     =  $this->GetExcel(I('attachment'),I('hetongID'));
            }
            if ($data['sendgoodslist']) {
                //发货排期1 2
                $data['sendgoodslist1'] =  $data['sendgoodslist'][0]?implode(',',$data['sendgoodslist'][0]):'';
                $data['sendgoodslist2'] =  $data['sendgoodslist'][1]?implode(',',$data['sendgoodslist'][1]):'';
            }       
            $data['user_id']        =  $this->user_id;
            $data['data_type']      =  "委托生产合同";
            $data['add_time']       =  time();
            if (!empty($data['id'])) {
                $sql = Db::name('contract_list')->where('id',$data['id'])->where('user_id',$this->user_id)->update($data);
                if (!$sql) {
                    return array('status'=>-1,'msg'=>"修改失败");
                }else{
                    return array('status'=>1,'img_url'=>$data['img_url'],'id'=>$data['id']);
                }
            }else{
                $data['contract_num']   =  I('hetongID');               //合同编号
                $sql = Db::name('contract_list')->insertGetid($data);
                if (!$sql) {
                    return array('status'=>-1,'msg'=>"添加失败");
                }else{
                    return array('status'=>1,'img_url'=>$data['img_url'],'id'=>$sql);
                }
            }
        }
        $id['id']    = $_GET['id'];
        $id['d_id']  = $_GET['d_id'];
        $id['p_id']  = $_GET['p_id'];
        $id['c_id']  = $_GET['c_id'];
        $id['u_id']  = $_GET['u_id'];
        if ($id['id']) {
            $find = Db::name('contract_list')->where('id',$id['id'])->where('user_id',$this->user_id)->find();
            $sendgoodslist1 =  array_filter(explode(',',$find['sendgoodslist1']));       //发货排期1
            $sendgoodslist2 =  array_filter(explode(',',$find['sendgoodslist2']));       //发货排期2
            $this->assign('contract_num',$find['contract_num']);
            $this->assign('sendgoodslist1',$sendgoodslist1);
            $this->assign('sendgoodslist2',$sendgoodslist2);
            $this->assign('id',$id);
            $this->assign('find',$find);
        }else{
            $today = strtotime(date("Y-m-d"),time());
            $time  = date("Ymd", $today);
            $count = Db::name('contract_list')->where("add_time > $today")->count()+1;
            $contract_num = 'HL'.$time.'-'.$count.chr(rand(65, 90));
            $list = Db::name('contract_list')->where(['user_id'=>$this->user_id,'data_type'=>'委托生产合同'])->order('id desc')->find();
            if (!empty($list) && $list['is_accomplish'] == 0) {
                $this->assign('id',$id);
                $this->assign('is_accomplish',$list['id']);
            }
            $this->assign('contract_num',$contract_num);
        }
        $contract_form = Db::name('contract_form_list')->where('id',$id['c_id'])->find();   //合同模板后台信息
        $contract_corporate = Db::name('contract_corporate')->where('id',$id['d_id'])->find();   //合同公司信息

        $this->assign('contract_corporate',$contract_corporate);
        $this->assign('contract_form',$contract_form);
        $this->assign('id',$id);
        return $this->fetch();
    }        

    public function contract_delete(){
        $id = $_POST['id'];
        $del = Db::name('contract_list')->where('id',$id)->delete();
        if ($id) {
            return array('status'=>1,'msg'=>"删除成功");
        }else{
            return array('status'=>-1,'msg'=>"删除失败");
        }
        return $this->fetch();
    }


}











 class JSSDKSS {
  private $appId;
  private $appSecret;

  public function __construct($appId, $appSecret) {
    $this->appId = $appId;
    $this->appSecret = $appSecret;
  }

  public function getSignPackage() {
    $jsapiTicket = $this->getJsApiTicket();

    // 注意 URL 一定要动态获取，不能 hardcode.
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    //$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "http://" : "https://";
    $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $timestamp = time();
    $nonceStr = $this->createNonceStr();

    // 这里参数的顺序要按照 key 值 ASCII 码升序排序
    $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

    $signature = sha1($string);

    $signPackage = array(
      "appId"     => $this->appId,
      "nonceStr"  => $nonceStr,
      "timestamp" => $timestamp,
      "url"       => $url,
      "signature" => $signature,
      "rawString" => $string
    );
    ///dump($signPackage);die;
    return $signPackage; 
  }

  private function createNonceStr($length = 16) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $str = "";
    for ($i = 0; $i < $length; $i++) {
      $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
  }

  private function getJsApiTicket() {
    // jsapi_ticket 应该全局存储与更新，以下代码以写入到文件中做示例
    $data = json_decode(file_get_contents("jsapi_ticket.json"));
    if ($data->expire_time < time()) {
      $accessToken = $this->getAccessToken();
      // 如果是企业号用以下 URL 获取 ticket
      // $url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=$accessToken";
      $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
      //dump($url);
      $res = json_decode($this->httpGet($url));
      $ticket = $res->ticket;
        // dump($res);
   
      if ($ticket) {
        $data->expire_time = time() + 7000;
        $data->jsapi_ticket = $ticket;
        $fp = fopen("jsapi_ticket.json", "w");
        fwrite($fp, json_encode($data));
        fclose($fp);
      }
    } else {
      $ticket = $data->jsapi_ticket;
    }
  //dump($ticket);
    return $ticket;
  }

  private function getAccessToken() {
    // access_token 应该全局存储与更新，以下代码以写入到文件中做示例
    $data = json_decode(file_get_contents("access_token.json"));
    if ($data->expire_time < time()) {
      // 如果是企业号用以下URL获取access_token
      // $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=$this->appId&corpsecret=$this->appSecret";
      $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
      $res = json_decode($this->httpGet($url));
      $access_token = $res->access_token;
      if ($access_token) {
        $data->expire_time = time() + 7000;
        $data->access_token = $access_token;
        $fp = fopen("access_token.json", "w");
        fwrite($fp, json_encode($data));
        fclose($fp);
      }
    } else {
      $access_token = $data->access_token;
    }
    return $access_token;
  }

  private function httpGet($url) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 500);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_URL, $url);

    $res = curl_exec($curl);
    curl_close($curl);

    return $res;
  }
}