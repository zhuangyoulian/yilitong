<?php
/**
 * Created by PhpStorm.
 * User: jiayi
 * Date: 2019/3/4
 * Time: 17:51
 */
namespace ylt\mobile\controller;
use ylt\home\logic\UsersLogic;
use ylt\mobile\logic\GoodsLogic;
use ylt\home\logic\CartLogic;
use think\Page;
use think\Request;
use think\Verify;
use think\Db;
use think\Url;
use think\Cache;

class Coupon extends MobileBase{

    public $user_id = 0;
    public $user = array();
    public $cartLogic; // 购物车逻辑操作类

    /*
    * 初始化操作
    */
    public function _initialize()
    {
        parent::_initialize();
        $user = array();
        $this->cartLogic = new \ylt\home\logic\CartLogic();
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
                }
              }
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
     * [coupon_goodsList 后台点击链接获取领取/展示列表]
     * @return [type] [description]
     */
    public function Coupon_GoodsList(){
        $goodsLogic = new \ylt\home\logic\GoodsLogic(); // 前台商品操作逻辑类

        $cid = $_GET['id'];
        $couponss=Db::name('coupon')->where('id',$cid)->find();
        $goods_id_s=0;
        $link_s=array();
        $couponss['use_start_time']=date('Y/m/d',$couponss['use_start_time']);
        $couponss['use_end_time']=date('Y/m/d',$couponss['use_end_time']);
        $couponss['time']=time();
        @$num=$couponss['createnumssss']-($couponss['ling_num']+$couponss['shenum']);
        @$couponss['ling_num']=round($num/$couponss['createnumssss']*100,2)."％";
        if ($couponss['type']==3 and $couponss['is_display']==1 and $couponss['id']==$cid and $couponss['send_num']!=0) {
            $goods_id=(explode(',',$couponss['goods_id']));
            for ($i=0; $i < count($goods_id); $i++) {
                $goods_id_s=$goods_id[$i];
                $link=Db::name('goods')->alias('g')->join('supplier s','g.supplier_id=s.supplier_id')->where('goods_id',$goods_id_s)->field('g.goods_id,g.goods_type,g.supplier_id,g.sales_sum,g.goods_name,g.shop_price,g.goods_thumb,s.supplier_name')->find();
                $link_s[]=$link;
                $link_s[$i]['price']=$link['shop_price']-$couponss['money'];  //券后价
                $link_s[$i]['money']=$couponss['money'];  
                $link_s[$i]['condition']=$couponss['condition']; 
                $goods_spec = $goodsLogic->get_spec_s($link_s[$i]['goods_id']);
                $link_s[$i]['goods_spec']=$goods_spec; 
            }
        }
        $count = count($link_s);        
        $this->assign('couponss',$couponss);
        $this->assign('link_s',$link_s);
        $this->assign('count',$count);

        if($this->user_id == 0){
            session('login_url',$_SERVER[REQUEST_URI]);
            $this->error("请登录",Url::build('/Mobile/user/login'));
        }
        if (IS_POST && IS_AJAX) {
            $id = I('post.id');
            $data['uid'] = $this->user_id;
            $data['use_end_time'] = strtotime($couponss['use_end_time']);
            $data['send_time'] = strtotime($couponss['use_start_time']);
            //查询是否还有优惠卷
            $query  = Db::name('coupon_list')->where('uid=0')->where('cid',$id)->find();   
            //查询当前账号是否已领取 
            $repeat = Db::name('coupon_list')->where(['uid' => $data['uid'],'cid' => $id,'use_time' => 0])->select();  
            if ($query) {
                if (!empty($repeat)) {
                    foreach ($repeat as $key => $val) {
                        if (count($repeat)>=$couponss['limitget'] and $val['use_end_time'] > time()) {
                        $this->error("请勿重复领券");
                        }else if(count($repeat)>=$couponss['limitget'] and $val['use_end_time'] < time() and $val['use_time'] ==0){
                            Db::name('coupon_list')->where('cid ='.$id)->where('uid',$this->user_id)->limit(1)->update($data);
                            $this->success("领券成功",Url::build('/Mobile/coupon/coupon_list'));
                            exit;
                        }
                    }
                }else{
                    Db::name('coupon_list')->where('cid ='.$id)->where('uid',0)->limit(1)->update($data);
                    $ling=Db::name('coupon_list')->where('cid ='.$id)->where('uid!=0')->select();
                    $lin['ling_num']=count($ling);
                    Db::name('coupon')->where('id ='.$id)->limit(1)->update($lin);
                    $this->success("领券成功！",Url::build('/Mobile/coupon/coupon_list'));
                    exit;
                }
            }else{
                    $this->error("该优惠卷已派完，现进入领券中心",Url::build('/Mobile/coupon/coupon_list'));
            exit;
            }
        }
        return $this->fetch();
    }

    //首页优惠券列表--一礼通单品优惠
    public function Coupon_List(){
        $coupon=Db::name('coupon')->where('is_display',1)->where('send_start_time','<=',time())->where('send_num != 0')->order('id desc')->select();
        foreach ($coupon as $key => $value) {
            if ($value['renewaltime']!=0) {   //判断是否自动续期/进行时间修改 
                if ($value['use_end_time'] < time() ) {
                    $link['use_start_time']=$value['use_start_time']+$value['renewaltime']*86400;
                    $link['use_end_time']=$value['use_end_time']+$value['renewaltime']*86400;
                    $link['send_end_time']=$value['send_end_time']+$value['renewaltime']*86400;
                    $link['send_start_time']=$value['send_start_time']+$value['renewaltime']*86400;
                    Db::name('coupon')->where("use_end_time" , '<' ,time())->where('id',$value['id'])->update($link);
                }
            }
            //下架商品自动隐藏优惠卷类型
            $yes = DB::name('goods')->where("is_on_sale = 0")->where(['goods_id'=>$value['goods_id']])->find();
            if ($yes) {
                Db::name('coupon')->where(['goods_id'=>$value['goods_id']])->update(['is_display'=>0]);
            }

            $num=$value['createnumssss']-($value['ling_num']+$value['shenum']);
            @$value['ling_num']=round($num/$value['createnumssss']*100,2)."％";
            if (count(explode(',',$value['goods_id'])) ==1 and $value['type']==3 and $value['is_display']==1) {
                $goods=Db::name('goods')
                    ->alias('g')
                    ->join('coupon c','g.goods_id=c.goods_id')
                    ->where('g.goods_id',$value['goods_id'])
                    ->cache(true,YLT_CACHE_TIME)
                    ->field('g.goods_id,g.goods_name,g.goods_thumb,c.money,c.condition,c.ling_num,c.createnum,c.id,c.use_end_time,c.use_start_time')
                    ->select();
                for ($i=0; $i < count($goods); $i++) {
                $goods[$i]['ling_num']=$value['ling_num'];
                $goods_s[]=$goods[$i]; 
                }
            }
        }
        // dump($goods_s);die;
        $this->assign('goods_s',$goods_s);

        if($this->user_id == 0){
            session('login_url',$_SERVER[REQUEST_URI]);
            $this->error('请先登录',Url('/Mobile/user/login'));
        }
        if (IS_POST && IS_AJAX) {
            $data['uid'] = $this->user_id;
            $id=I('post.id');
            $cid=I('post.cid');
            foreach ($goods_s as $ke => $val) {
                if ($val['id'] == $cid) {
                $data['use_end_time'] = $val['use_end_time'];
                $data['send_time'] = $val['use_start_time'];
                }
            }
            $coupon = Db::name('coupon')->where('id',$cid)->field('limitget')->find();     //查询限领数量
            $query  = Db::name('coupon_list')->where('uid=0')->where('cid',$cid)->find();    //查询是否还有优惠卷
            $repeat = Db::name('coupon_list')->where(['uid' => $data['uid'],'cid' => $cid,'use_time'=>0])->field('uid,use_end_time')->select();  //查询当前账号是否已领取
            if ($query) {
                if (count($repeat)>=$coupon['limitget']) {
                    foreach ($repeat as $key => $value) {
                        if($value['use_end_time'] < time()){
                            Db::name('coupon_list')->where('cid ='.$cid)->where('uid',$data['uid'])->limit(1)->update(['use_end_time' => $data['use_end_time']]);
                            return array('status' => 1, 'msg' => '领券成功！正在进入产品中心!', 'result' => '');
                            exit;
                        }else{
                            return array('status' => -1, 'msg' => '请勿重复领券!', 'result' => '');
                            exit;
                        }
                    }
                }else{
                    Db::name('coupon_list')->where('cid ='.$cid)->where('uid',0)->limit(1)->update($data);
                    $ling=Db::name('coupon_list')->where('cid ='.$cid)->where('uid!=0')->select();
                    $lin['ling_num']=count($ling);
                    Db::name('coupon')->where('id ='.$cid)->limit(1)->update($lin);
                    return array('status' => 1, 'msg' => '领券成功！正在进入产品中心!', 'result' => '');
                    exit;
                }
            }else{
                return array('status' => -1, 'msg' => '优惠卷已派完!', 'result' => '');
                exit;
            }
        }
        return $this->fetch();

    }

        //首页优惠券列表--一礼通多品优惠
    public function Coupon_List_More(){
        $coupon=Db::name('coupon')->where('is_display=1 and coupon_type=1 and send_start_time <='.time().' and send_num != 0 and supplier_id=41')->select();
        $goods_l=array();
        foreach ($coupon as $key => $value) {
            if ($value['renewaltime']!=0) {   //判断是否自动续期/进行时间修改 
                if ($value['use_end_time'] < time() ) {
                    $link['use_start_time']=$value['use_start_time']+$value['renewaltime']*86400;
                    $link['use_end_time']=$value['use_end_time']+$value['renewaltime']*86400;
                    $link['send_end_time']=$value['send_end_time']+$value['renewaltime']*86400;
                    $link['send_start_time']=$value['send_start_time']+$value['renewaltime']*86400;
                    Db::name('coupon')->where("use_end_time" , '<' ,time())->update($link);
                }
            }
            $num=$value['createnumssss']-($value['ling_num']+$value['shenum']);
            @$value['ling_num']=round($num/$value['createnumssss']*100,2)."％";
            if (count(explode(',',$value['goods_id'])) >1 and $value['type']==3 and $value['is_display']==1) {
                $type_id=$value['id'];
                $goods=Db::name('goods')
                    ->alias('g')
                    ->join('coupon c','g.goods_id=c.goods_id')
                    ->where('c.goods_id',$value['goods_id'])
                    ->field('g.goods_thumb,g.goods_id,c.money,c.condition,c.ling_num,c.createnum,c.id,c.use_end_time,c.use_start_time')
                    ->select();
                for ($i=0; $i < count($goods); $i++) {
                $goods[$i]['ling_num']=$value['ling_num'];
                $goods_l[]=$goods[$i]; 
                }
            }
            // dump($type_id);
        }
        // dump($goods_l);
        // die;
        $this->assign('type_id',$type_id);
        $this->assign('goods_l',$goods_l);

        if($this->user_id == 0){
            // return array('code' => -1, 'msg' => '请先登录', 'url' => '/Mobile/user/login');
            session('login_url',$_SERVER[REQUEST_URI]);
            $this->error('请先登录',Url('/Mobile/user/login'));
        }
        if (IS_POST && IS_AJAX) {
            $data['uid'] = $this->user_id;
            $cid=I('post.cid');
            foreach ($goods_l as $ke => $val) {
                if ($cid==$val['id']) {
                $data['use_end_time'] = $val['use_end_time'];
                $data['send_time'] = $val['use_start_time'];
                }
            }
            $coupon = Db::name('coupon')->where('id',$cid)->field('limitget')->find();       //查询限领数量
            $query  = Db::name('coupon_list')->where('uid=0')->where('cid',$cid)->find();    //查询是否还有优惠卷
            $repeat = Db::name('coupon_list')->where('uid',$data['uid'])->where('cid',$cid)->field('uid,use_end_time,use_time')->select();  //查询当前账号是否已领取
            if ($query) {
                if (!empty($repeat)) {
                    foreach ($repeat as $key => $val) {
                        if (count($repeat)>=$coupon['limitget'] and $val['use_end_time'] > time()) {
                            $this->error("请勿重复领券",Url::build('/mobile/Coupon/coupon_goodsList/id/'.$cid));
                        }else if(count($repeat)>=$coupon['limitget'] and $val['use_end_time'] < time() and $val['use_time'] ==0){
                            Db::name('coupon_list')->where('cid ='.$cid)->where('uid',$data['uid'])->limit(1)->update($data);
                            $this->success("领券成功！正在进入商品列表",Url::build('/mobile/Coupon/coupon_goodsList/id/'.$cid));
                            // $this->success("领券成功");
                            exit;
                        }
                    }
                }else{
                    Db::name('coupon_list')->where('cid ='.$cid)->where('uid',0)->limit(1)->update($data);
                    $ling=Db::name('coupon_list')->where('cid ='.$cid)->where('uid!=0')->select();
                    $lin['ling_num']=count($ling);
                    Db::name('coupon')->where('id ='.$cid)->limit(1)->update($lin);
                    $this->success("领券成功！正在进入商品列表",Url::build('/mobile/Coupon/coupon_goodsList/id/'.$cid));
                    exit;
                }
            }else{
            $this->error("优惠卷已派完",Url::build('/mobile/Coupon/coupon_goodsList/id/'.$cid));
            exit;
            }
        }
        return $this->fetch();
    }


    //首页优惠券列表--店铺优惠
    public function Coupon_List_Supplier(){
        $coupon=Db::name('coupon')->where('is_display=1 and coupon_type=1 and send_start_time <='.time().' and send_num != 0 and supplier_id!=41')->select();
        // dump($coupon);
        $goods_l=array();
        foreach ($coupon as $key => $value) {
            if ($value['renewaltime']!=0) {   //判断是否自动续期/进行时间修改 
                if ($value['use_end_time'] < time() ) {
                    $link['use_start_time']=$value['use_start_time']+$value['renewaltime']*86400;
                    $link['use_end_time']=$value['use_end_time']+$value['renewaltime']*86400;
                    $link['send_end_time']=$value['send_end_time']+$value['renewaltime']*86400;
                    $link['send_start_time']=$value['send_start_time']+$value['renewaltime']*86400;
                    Db::name('coupon')->where("use_end_time" , '<' ,time())->update($link);
                }
            }
            $num=$value['createnumssss']-($value['ling_num']+$value['shenum']);
            @$value['ling_num']=round($num/$value['createnumssss']*100,2)."％";
            if ($value['type']==3 and $value['is_display']==1 ) {
                $supplier=Db::name('supplier')
                    ->alias('s')
                    ->join('coupon c','s.supplier_id=c.supplier_id')
                    ->where('c.supplier_id',$value['supplier_id'])
                    ->field('s.logo,s.supplier_id,s.supplier_name,c.money,c.condition,c.ling_num,c.createnum,c.id,c.use_end_time,c.use_start_time')
                    ->select();
                // dump($supplier);
                for ($i=0; $i < count($supplier); $i++) {
                $supplier[$i]['ling_num']=$value['ling_num'];
                $supplier_l[]=$supplier[$i]; 
                }
            }
        }
        $supplier_ls=assoc_unique($supplier_l,'id');
        // dump($supplier_ls);
        // die;
        $this->assign('supplier_l',$supplier_ls);

        if($this->user_id == 0){
            // return array('code' => -1, 'msg' => '请先登录',  'url' => '/Mobile/user/login');
            session('login_url',$_SERVER[REQUEST_URI]);
            $this->error('请先登录',Url('/Mobile/user/login'));
        }
        if (IS_POST && IS_AJAX) {
            $data['uid'] = $this->user_id;
            $cid=I('post.cid');
            foreach ($supplier_l as $ke => $val) {
                if ($cid==$val['id']) {
                    $data['use_end_time'] = $val['use_end_time'];
                    $data['send_time'] = $val['use_start_time'];
                    $supplier_id = $val['supplier_id'];
                }
            }
            $coupon = Db::name('coupon')->where('id',$cid)->field('limitget')->find();       //查询限领数量
            $query  = Db::name('coupon_list')->where('uid=0')->where('cid',$cid)->find();    //查询是否还有优惠卷
            $repeat = Db::name('coupon_list')->where('uid',$data['uid'])->where('cid',$cid)->field('uid,use_end_time,use_time')->select();  //查询当前账号是否已领取
            if ($query) {
                if (!empty($repeat)) {
                    foreach ($repeat as $key => $val) {
                        if (count($repeat)>=$coupon['limitget'] and $val['use_end_time'] > time()) {
                        $this->error("请勿重复领券",Url::build('/mobile/Coupon/coupon_goodsList/id/'.$cid));
                        }else if(count($repeat)>=$coupon['limitget'] and $val['use_end_time'] < time() and $val['use_time'] ==0){
                            Db::name('coupon_list')->where('cid ='.$cid)->where('uid',$this->user_id)->limit(1)->update($data);
                            $this->success("领券成功！正在进入产品中心",Url::build('/mobile/Coupon/coupon_goodsList/id/'.$cid));
                            // $this->success("领券成功");
                            exit;
                        }
                    }
                }else{
                    Db::name('coupon_list')->where('cid ='.$cid)->where('uid',0)->limit(1)->update($data);
                    $ling=Db::name('coupon_list')->where('cid ='.$cid)->where('uid!=0')->select();
                    $lin['ling_num']=count($ling);
                    Db::name('coupon')->where('id ='.$cid)->limit(1)->update($lin);
                    $this->success("领券成功！正在进入产品中心",Url::build('/mobile/Coupon/coupon_goodsList/id/'.$cid));
                    exit;
                }
            }else{
            $this->error("优惠卷已派完");
            exit;
            }
        }
        return $this->fetch();
    }


    /**
     * [electCoupons 订单页面可用优惠券列表]
     * @return [type] [description]
     */
    public function electCoupons(){ 
        return $this->fetch();
    }

    /**
     * [ctCoupons 订单页面可用/不可用优惠券]
     * @return [type] [description]
     */
    public function ctCoupons(){
        if (!empty($this->user_id)) {
            //可用优惠券处理
            $result = $this->cartLogic->cartList($this->user, $this->session_id,1,1,1); // 获取购物车商品
            $cartList=$result['cartList'];
            // 查coupon_list表中是否拥有当前商品的优惠券
            $coupon_s=$this->cartLogic->CouponList($cartList,$this->user);//获取订单可用优惠券列表
            // dump($coupon_s);die;
            if ($coupon_s) {
                foreach ($coupon_s as $key => $value) {
                    if (!empty($value)) {
                        // $goods_id[]=explode(',', $value['goods_id']);
                        $value_s[]=$value;
                        $coupon_s_ss=count($value_s);
                        $value['showSelect']=1;
                        $value['coupon_s_ss']=$coupon_s_ss;
                        $coupon_ss[]=$value;
                    }
                }
            }
            // dump($coupon_ss);die;
            $json_data=json_encode($coupon_ss, JSON_HEX_TAG);
            echo $json_data;
            //结束
            exit();
            }else{
                session('login_url',$_SERVER[REQUEST_URI]);
                $this->error("请登录",Url::build('/Mobile/user/login'));
        }
    }

    /**
     * [NoCoupons 订单页面不可用优惠券]
     */
    public function NoCoupons(){
        if (!empty($this->user_id)) {
            $result = $this->cartLogic->cartList($this->user, $this->session_id,1,1,1); // 获取购物车商品
            $cartList=$result['cartList'];
            // 查coupon_list表中是否拥有当前商品的优惠券
            $coupon_s=$this->cartLogic->CouponList($cartList,$this->user);//获取订单可用优惠券列表
            $cid=Db::name('coupon_list')->where('uid',$this->user_id)->field('cid')->select();
            foreach ($coupon_s as $key => $value) {
                if (!empty($value['cid'])) {
                    $yes_cid[]=$value['cid'];
                }
            }
            foreach ($cid as $cid_key => $cid_value) {
                if (!empty($cid_value['cid'])) {
                    $old_cid[]=$cid_value['cid'];
                }
            }
            $str =array_merge($yes_cid,$old_cid);    //合并为一个数组
            $arr = array_count_values($str);         //对数组中的值进行计数，无重复值为1
            foreach ($arr as $arr_key => $arr_value) {
                if($arr_value == 1){
                    $left[] = $arr_key;
                }
            }
            for ($i=0; $i <count($left) ; $i++) { 
                $left_cid[]=Db::name('coupon')->where("id in ($left[$i])")->find();
            }
            // dump($left_cid);
            $json_data_no=json_encode($left_cid, JSON_HEX_TAG);
            echo $json_data_no;
            exit();
            }else{
                session('login_url',$_SERVER[REQUEST_URI]);
                $this->error("请登录",Url::build('/Mobile/user/login'));
        }
    }

    /**
     * [boundCodes 订单页面绑定/增加礼品卡]
     * @return [type] [description]
     */
    public function BoundCodes(){
        $data=I('post.');
        if (IS_AJAX) {
        $codes=Db::name('code_list')->where(array('code'=>$data['code'],'uid'=>0))->find();
            if ($codes) {
                Db::name('code_list')->where(array('code'=>$data['code']))->update(array('uid'=>$data['uid'],'binding_time'=>time()));
                return array('status' => 1, 'msg' => '添加成功！', 'result' => '');
            }else{
                return array('status' => -1, 'msg' => '添加失败，密码不正确或已绑定', 'result' => '');
            }
        }
        return $this->fetch();
    }

    public function ElectCodes(){ 
        return $this->fetch();
    }
    /**
     * [electCodes 订单页面可用/不可用礼品卡]
     * @return [type] [description]
     */
    public function CtCodes(){
        $uid=$_GET['uid'];
        //可用查询
        $code_uid=Db::name('code_list')->alias('l')->join('code c','l.cid=c.id')->where(array('l.uid'=>$uid,'l.use_time'=>0,'l.order_id'=>0))->where("use_end_time>".time()." && use_start_time<".time())->field('c.id,c.use_end_time,l.binding_time,c.money,l.code,l.number')->select();
        if ($code_uid) {
            foreach ($code_uid as $key => $value) {
                $value['showSelect']='1';
                $value['binding_time']=date('Y-m-d',$value['binding_time']);
                $value['use_end_time']=date('Y-m-d',$value['use_end_time']);
                $code[]=$value;
            }
        }
        $json_data=json_encode($code, JSON_HEX_TAG);
        echo $json_data;
        exit();
    }

    /**
     * [electCodes 订单页面可用/不可用礼品卡]
     * @return [type] [description]
     */
    public function OnctCodes(){
        $uid=$_GET['uid'];
        //不可用查询
        $code_uid_no=Db::name('code_list')->alias('l')->join('code c','l.cid=c.id')->where(array('l.uid'=>$uid,'l.use_time'=>0,'l.order_id'=>0))->where("use_end_time<".time()." || use_start_time>".time())->field('c.use_end_time,l.binding_time,c.money,l.number')->select();
        exit(json_encode(array('status'=>-1,'msg'=>'礼品卡不在可使用的时间范围内','json_data'=>$code_uid_no)));
    }
}
