<?php
/**
 * Created by PhpStorm.
 * User: lijiayi
 * Date: 2017/3/27
 * Time: 10:30
 */
namespace ylt\mobile\controller; 
use ylt\admin\logic\GoodsLogic;
use ylt\home\logic\UsersLogic;
use ylt\home\model\CartLogic;
// use ylt\mobile\controller\payment;
use think\Controller;
use think\Url;
use think\Page;
use think\Config;
use think\Verify;
use think\Db;
use think\Request;
use think\Cache;

class Refillcard extends MobileBase{
    public $user_id = 0;
    public $user = array();

    // /*
    // * 初始化操作
    // */
    public function _initialize()
    {
        parent::_initialize();
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST,GET,OPTIONS,PUT,DELETE');
        header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
        $user = array();
            // $this->user_id = 164850;
        
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
        $nologin = array(
            'login', 'pop_login', 'do_login', 'logout', 'verify', 'set_pwd', 'finished',
            'verifyHandle','reg', 'send_sms_reg_code', 'find_pwd', 'check_validate_code',
            'forget_pwd', 'check_captcha', 'check_username', 'send_validate_code', 'express','getBackPassword',
                 'checkmobilecode','newpassw','jiyan','jiyan_yz',
        );
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
                    // //绑定手机号
                    // if (empty($data['result']['mobile'])) {
                    //     $this->error('请先绑定手机账号',Url::build('User/mobile_validate_two'));
                    // }
                }
              }
            }else{
                session('login_url',$_SERVER[REQUEST_URI]);
                header("location:" . Url::build('User/login'));
                exit;
            }
        }
    }
    
    public function index(){
        //轮播brand 充值中心 52
        $brand_roll = Db::name('ad')->where('pid=52 and enabled=1')->order('orderby DESC')->find();  

        //金刚区    充值中心 51
        $four_list = Db::name('ad')->where('pid=51 and enabled=1')->field('ad_code,ad_link,ad_name')->order('orderby DESC')->limit('0,4')->select();  
        //首页推荐商品
        $goods = Db::name('goods')->where('is_on_sale=1 and examine=1 and is_designer=0 and is_recommend=1 and is_delete=0')->field('goods_id,goods_thumb,goods_name,shop_price')->order('add_time desc')->limit('0,10')->cache(true,YLT_CACHE_TIME)->select();
        $this->assign('goods', $goods);
        $this->assign('brand_roll', $brand_roll);
        $this->assign('four_list', $four_list);
        return $this->fetch();
    }

    public function tel(){
        return $this->fetch();
    }
    public function oil(){
        return $this->fetch();
    }
    public function yule(){
        return $this->fetch();
    }

    /**
     * [top_phone 充值中心]
     * @return [type] [description]
     */
    public function ajaxtel(){
        $data['pid'] = I('pid');
        $data['cid'] = I('cid');
        $data['tid'] = I('tid');
        $data['did'] = I('did');
        $top_config = $this->topclass();
        $specification = Db::name('top_config')->where('parent_id',$data['cid'])->select();
        if ($specification[0] and empty(I('tid'))) {
            $data['tid'] = $specification[0]['id'];
        }

        $top_config_card = Db::name('top_config_card')->where('cat_id_2',$data['tid'])->select();
        if ($top_config_card[0] and empty(I('did'))) {
            $data['did'] = $top_config_card[0]['id'];
        }
        $price = Db::name('top_config_card')->where('id',$data['did'])->find();
        $this->assign('did', $data['did']);
        $this->assign('tid', $data['tid']);
        $this->assign('pid', $data['pid']);
        $this->assign('cid', $data['cid']);
        $this->assign('price', $price['price']);
        $this->assign('top_config', $top_config);
        $this->assign('specification', $specification);
        $this->assign('top_config_card', $top_config_card);
        return $this->fetch();
    }

    /**
     * [topclass 充值 一、二、三级分类]
     * @return [type] [description]
     */
    public function topclass(){
        //充值 一、二、三级分类
        $cat_list = Db::name('top_config')->where("is_show = 1 ")->select(); 
        foreach ($cat_list as $key => $value) {
            $cat_list_s = Db::name('top_config')->where('parent_id',$value['id'])->where(" is_show = 1")->select();
            foreach ($cat_list_s as $ke => $val) {
                $cat_list_t = Db::name('top_config')->where('parent_id',$val['id'])->where(" is_show = 1")->select();
                foreach ($cat_list_t as $k => $va) {
                        $val['lists'][] = $va;
                }
                $value['list'][] = $val;
            }
            if ($value['parent_id'] == null ) {
                $config[] = $value;
            }
        }
        return $config;
    }

    /**
     * [affirm_order 兑换/支付 提交订单]
     * @return [type] [description]
     */
    public function affirm_order(){
        if($this->user_id == 0){
            session('login_url',$_SERVER[REQUEST_URI]);
            header("location:" . Url::build('User/login'));
            exit;
        }

        $data = I('');
        
        if (empty($data['phone'])) {
            exit(json_encode(array('result'=>'-5','info'=>'充值账号不能为空')));
        }
        if ($data['radio1'] == 1) { //选择兑换
            if (empty($data['code'])) {
                exit(json_encode(array('result'=>'-1','info'=>'兑换码不可为空。')));
            }
            $code_list = Db::name('code_list')->alias('l')->join('code c','l.cid = c.id')->where('l.code',$data['code'])->find();
            if (!empty($code_list['uid']) or !empty($code_list['use_time']) or !empty($code_list['order_id'])) {
                exit(json_encode(array('result'=>'-3','info'=>'兑换券不可重复使用')));
            }
            if ($code_list['use_start_time'] > time() or $code_list['use_end_time'] < time()) {
                exit(json_encode(array('result'=>'-4','info'=>'兑换券不在使用期限内')));
            }
            

            //生成订单
            $order_id = $this->order_info($data['id'],$data['phone'],$data['code'],1,1,4,$data['encoding']);

            //第三方充值接口
            if ($data['pid'] == 7 || $data['pid'] == 8) {
                $this->topUpAPI_phone($order_id,$data['phone'],$data['encoding']);
            }else if($data['pid'] == 9){
                $this->topUpAPI($order_id,$data['phone']);
            }

            //修改兑换码状态
            if ($order_id) {
                Db::name('code_list')->where('code',$data['code'])->update(['uid'=>$this->user_id,'use_time'=>time(),'order_id'=>$order_id]);
            }

            $rs=array('result'=>'1','info'=>'充值成功');
            exit(json_encode($rs));
        }else if($data['radio1'] == 2){        //选择支付
            $order_id = $this->order_info($data['id'],$data['phone'],'',0,0,0);
            if ($order_id) {
                $rs=array('result'=>'2','info'=>'生成订单成功，跳转支付页面','order_id'=>$order_id);
                exit(json_encode($rs));
            }
        }
    }
    //生成订单
    public function order_info($id,$phone,$code='',$tpye,$tpyes,$tpyest,$encoding){
        if (!empty($id) and !empty($phone)) {
            $top_config = Db::name('top_config')->alias('t')->join('top_config_card c','t.id = c.cat_id_2')->field('c.*,t.name as t_name,t.card_img')->where('c.id',$id)->find();
            if (!$top_config) {
                $this->error('产品不存在');
            }
            $data['user_id']        = $this->user_id;           //用户ID
            $data['phone']          = $phone;                   //手机号
            $data['card_id']        = $id;                      //商品ID
            $data['code']           = $code;                    //商品ID
            $data['card_name']      = $top_config['name'];      //规格名称
            $data['card_name_t']    = $top_config['t_name'];    //分类名称
            $data['goods_price']    = $top_config['price'];     //商品价格
            $data['total_amount']   = $top_config['price'];     //商品价格
            $data['pay_status']     = $tpye;                    //支付状态
            $data['shipping_status']= $tpyes;                   //发货状态
            $data['order_status']   = $tpyest;                  //订单状态
            $data['card_img']       = $top_config['card_img'];  //分类图标
            $data['add_time']       = time();                   //订单生成时间
            $data['order_sn']       = "cz".date('YmdHis') . rand(1000, 9999); // 订单编号
            $data['order_amount']   = $top_config['price'];     //应付价格
            $data['supplier_name']  = "一礼通充值中心";         //店铺名称
            $data['supplier_id']    = 41;                       //店铺ID
            $data['is_topup']       = 1;                        //充值订单
            $data['encoding']       = $encoding;                //充值订单
            if (!empty($code)) {
                $data['order_amount'] = 0;                      //应付价格
            }
            $order_id = Db::name("Order")->insertGetId($data);

            return $order_id;
        }
    }

    
    /**
     * [topUpAPI 影视会员]
     * @param  [type] $order_id [description]
     * @param  [type] $phone    [description]
     * @return [type]           [description]
     */
    public function topUpAPI($order_id,$phone,$skuCodes){
        // $url            =   'http://test.www.phone580.com:8000/fzs-open-api/buy/api/sendgoods';
        $url            =   'https://orderapi.phone580.com/fzs-open-api/buy/api/sendgoods';
        $appKey         =   'YLTHC1TESTKEY001';
        $appSecret      =   '24f724601a9139b9cb';
        $channelId      =   'YLTHC1';
        $num            =   '1';
        $orderId        =   $order_id;
        $returl         =   'http://www.yilitong.com/Home/Notify/APItype';
        // $returl         =   'tp.cn/Mobile/Refillcard/APItype';
        $shipInfo       =   "{'phoneNum':$phone,'account':'12312'}";
        $skuCode        =   $skuCodes;
        $srvType        =   'recharge';
        $timestamp      =   time()*1000;

        $datas=$appSecret.'appKey='.$appKey.'channelId='.$channelId.'num='.$num.'orderId='.$orderId.'returl='.$returl.'shipInfo='.$shipInfo.'skuCode='.$skuCode.'srvType='.$srvType.'timestamp='.$timestamp.$appSecret;

        $shipInfo=urlencode($shipInfo);
        $data='appKey='.$appKey.'&channelId='.$channelId.'&num='.$num.'&orderId='.$orderId.'&returl='.$returl.'&shipInfo='.$shipInfo.'&skuCode='.$skuCode.'&srvType='.$srvType.'&timestamp='.$timestamp;

        $sign=strtoupper(sha1($datas));
        $parameter = $url.'?'.$data.'&sign='.$sign;
        $hou=array("","","","","");
        $qian=array(" ","　","\t","\n","\r");
        $parameter = str_replace($qian,$hou,$parameter);
        //$parameter = "https://orderapi.phone580.com/fzs-open-api/buy/api/sendgoods";
        $type = $this->get_urls($parameter);
        // print_r($parameter);
        // print_r($parameter);
        // print_r($type);
        // print_r($orderId);die;
        return $type;
    }

    /**
     * [topUpAPI_phone 话费和油卡]
     * @param  [type] $order_id [description]
     * @param  [type] $phone    [description]
     * @return [type]           [description]
     */
    public function topUpAPI_phone($order_id,$phone,$skuCodes){
        // $url            =   'http://test.www.phone580.com:8000/fzs-open-api/buy/api/sendgoods';
        $url            =   'https://orderapi.phone580.com/fzs-open-api/buy/api/sendgoods';
        $appKey         =   'YLTHC2TESTKEY001';
        $appSecret      =   'f4a6424a9bc79cf';
        $channelId      =   'YLTHC2';
        $num            =   '1';
        $orderId        =   $order_id;
        $returl         =   'http://www.yilitong.com/Home/Notify/APItype';
        // $returl         =   'tp.cn/Mobile/Refillcard/APItype';
        $shipInfo       =   "{'phoneNum':$phone,'account':'12312'}";
        $skuCode        =   $skuCodes;
        $srvType        =   'recharge';
        $timestamp      =   time()*1000;

        $datas=$appSecret.'appKey='.$appKey.'channelId='.$channelId.'num='.$num.'orderId='.$orderId.'returl='.$returl.'shipInfo='.$shipInfo.'skuCode='.$skuCode.'srvType='.$srvType.'timestamp='.$timestamp.$appSecret;

        $shipInfo=urlencode($shipInfo);
        $data='appKey='.$appKey.'&channelId='.$channelId.'&num='.$num.'&orderId='.$orderId.'&returl='.$returl.'&shipInfo='.$shipInfo.'&skuCode='.$skuCode.'&srvType='.$srvType.'&timestamp='.$timestamp;

        $sign=strtoupper(sha1($datas));
        $parameter = $url.'?'.$data.'&sign='.$sign;
        $hou=array("","","","","");
        $qian=array(" ","　","\t","\n","\r");
        $parameter = str_replace($qian,$hou,$parameter);
        //$parameter = "https://orderapi.phone580.com/fzs-open-api/buy/api/sendgoods";
        $type = $this->get_urls($parameter);
        // print_r($parameter);
        // print_r($parameter);
        // print_r($type);
        // print_r($orderId);die;
        return $type;
    }

    /**
     * [topUpAPI_inquire 充值订单查询]
     * @param  [type] $order_id [description]
     * @return [type]           [description]
     */
    public function topUpAPI_inquire($order_id){
         // $url            =   'http://test.www.phone580.com:8000/fzs-open-api/buy/api/queryorder';
        $url            =   'https://orderapi.phone580.com/fzs-open-api/buy/api/sendgoods';
        $appKey         =   'YLTHC1TESTKEY001';
        $appSecret      =   '24f724601a9139b9cb';
        $channelId      =   'YLTHC1';
        $timestamp      =   time()*1000;
        $orderId       =   $order_id;

    
        //sign签名
        $datas=$appSecret.'appKey='.$appKey.'channelId='.$channelId.'orderId='.$orderId.'timestamp='.$timestamp.$appSecret;
      
        $data='appKey='.$appKey.'&channelId='.$channelId.'&orderId='.$orderId.'&timestamp='.$timestamp;

        $sign=strtoupper(sha1($datas));
        $parameter = $url.'?'.$data.'&sign='.$sign;
        $hou=array("","","","","");
        $qian=array(" ","　","\t","\n","\r");
        $parameter = str_replace($qian,$hou,$parameter);
        $type = $this->get_urls($parameter); 
        return $type;
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
}