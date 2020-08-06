<?php
/**
 * Created by PhpStorm.
 * User: jiayi
 * Date: 2019/5/23
 * Time: 14:16
 */
namespace ylt\mobile\controller;
use ylt\home\logic\UsersLogic;
use ylt\mobile\logic\GoodsLogic;
use ylt\mobile\controller\Goods;
use ylt\home\logic\CartLogic;
use think\Controller;
use think\AjaxPage;
use think\Config;
use think\Page;
use think\Request;
use think\Verify;
use think\Db;
use think\Url;
use think\Cache;

class Distribution extends MobileBase{

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
                    //绑定手机号
                    if (empty($data['result']['mobile'])) {
                        $this->error('请先绑定手机账号',Url::build('User/mobile_validate_two'));
                    }
                }
              }
            }
        }
    }

    /**
     * [bottom 底部导航]
     * @return [type] [description]
     */
    public function bottom(){
    	$id=$this->shop_id();
        $this->assign('id',$id);
        return $this->fetch();
    }
    /**
     * [shop_id 获取当前用户的店主ID]
     * @return [type] [description]
     */
    public function shop_id(){
        $u_id=$this->user_id;
        if (!$u_id) {
            $this->error('请先登陆',Url('User/index'));
        }

        $logic = new UsersLogic();
        $user = $logic->get_info($u_id); //当前登录用户信息
        if (empty($user['result']['mobile'])) {
            $this->error('请先绑定手机账号',Url::build('User/mobile_validate_two'));
        }
        
        $id=Db::name('distribution')->where('u_id',$this->user_id)->field('id')->value('id');
        session('shop_id',$id);
        if (!$id) {
            // $this->error("您还不是店主，请先申请开店",Url::build('/Mobile/Distribution/apply'));
            $content='
            <!DOCTYPE html>
            <html lang="en">
            
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <meta http-equiv="X-UA-Compatible" content="ie=edge">
                <title></title>
                <style>
                    body,
                    html {
                        width: 100%;
                        height: 100%;
                        overflow: hidden;
                    }
                    
                    * {
                        padding: 0;
                        border: 0;
                        margin: 0;
                    }
                    
                    .wrapper {
                        background: #000000;
                        position: fixed;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        width: 100%;
                        height: 100%;
                        opacity: 0.7;
                        z-index: 100;
                    }
                    
                    .cont {
                        z-index: 101;
                        background: #ffffff;
                        position: fixed;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%);
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        width: 200px;
                        border-radius: 14px;
                        padding-left: 15px;
                        padding-right: 15px;
                        padding-top: 20px;
                    }
                    
                    .cont p {
                        line-height: 20px;
                    }
                    
                    .buttona {
                        display: block;
                        text-decoration: none;
                        margin-top: 20px;
                        margin-bottom: 20px;
                        color: #333333;
                        border: 1px solid #e50012;
                        border-radius: 10px;
                        padding: 5px 10px;
                        font-size: 14px;
                    }
                    
                    .buttona:active,
                    .buttona:link {
                        text-decoration: none;
                    }
                </style>
            </head>
            
            <body>
                <div class="wrapper"></div>
                <div class="cont">
                    <p>您还不是店主,请先申请开店</p>
                    <p></p>
                    <a href="" class="buttona">回到首页</a>
                </div>
            
            </body>
            
            </html>
            ';
        
            echo $content;
            die;
            
        }
        return $id;
    }


    /**
     * [apply 申请成为店主]
     * @return [type] [description]
     */
    public function apply(){
        if (!$this->user_id) {
            $this->error('请先登陆',Url('User/index'));
        }
        $storeIndex=$_SERVER['HTTP_REFERER'];
        if (empty($storeIndex)  || strpos($storeIndex,'mp.weixin.qq.com')) {
            $this->assign('storeIndex',-1);
        }
        $l_code = I('get.l_code');
        $referrer_id = I('get.r_id');
        if ($l_code) {          //直接获取分享人的邀请码
            session('l_code',$l_code);
            session('referrer_id',$referrer_id);
        }
        $url = $_SERVER['HTTP_REFERER'];   
        if (!empty($url)  && strpos($url,'goodsInfos')) {   //从详情页进入申请页面的成功后返回详情页
            $this->assign('url',$url);
        }
        // if(strpos($url,'Distribution/index')){  //不是店主中心进去的不需要分享指引
        //     $this->assign('http',1);
        // }

    	if (IS_AJAX) {
            $u_id=$this->user_id;
	    	$data=I('post.');
            //查找用户表中是否有上级ID
            $re=Db::name('users')->where('user_id',$u_id)->field('referrer_id')->value('referrer_id');
	    	if ($data['self']==1) {
                if ($re) {      //有上级并且为店主时自动生成上级的邀请码
                    $c=Db::name('distribution')->where('u_id',$re)->field('l_code,u_id')->find();
                    if (!$c) {  //上级不是店主没有邀请码时生成默认邀请码
                        $c=Db::name('distribution')->where('id',1)->field('l_code,u_id')->find();
                    }
                }else{          //无上级时生成默认邀请码
    	    		$c=Db::name('distribution')->where('id',1)->field('l_code,u_id')->find();
                }
                if ($c) {
                    session('l_code',$c['l_code']);
                    session('referrer_id',$c['u_id']);
                    return array('status' => 2,'msg' => '邀请码生成成功。','id'  =>'',);
                }
	    	}

	    	if ($u_id) {
		    	$u=Db::name('distribution')->where('u_id',$u_id)->field('id')->find();
		    	if ($u) {
		    		return array('status' => 1,'msg' => '您已是店主，将为您跳转至店主中心。','id'  =>$u['id']);
		    	}
	    	}

	    	if (!$data['code']) {
                return array('status' => -2,'msg' => '邀请码不能为空。');
            }else{
                $preg_match = preg_match('/^[0-9a-zA-Z]+$/', $data['code']);
                if (!$preg_match) {
                    return array('status' => -3,'msg' => '非法字符。');
                }
            }
            
	    	$r=Db::name('distribution')->where('l_code',$data['code'])->field('id')->find();
	    	if (!$r) {
                return array('status' => -1,'msg' => '邀请码不存在,请重新输入。','id'  =>'',);
            }

            if ($re) {
                //查询上级ID是否为分销店主
                $r_id = Db::name('distribution')->where(array('u_id'=>$re))->field('id')->value('id');
                if ($r_id) {
                    $data['r_id']=$r_id;    //有用户上级并且是店主的则分销上级为这个用户的店主ID
                }else{
                    $data['r_id']=$r['id']; //有却不是店主则分销上级为给与邀请码的店主ID
                }
            }else{
                $data['r_id']=$r['id'];     //没有用户上级则分销上级为给与邀请码的店主ID
            }

            //店主数据入表
            $data['l_code'] = $this->Do_code();  //生成邀请码
            $data['add_time'] = time();
            $data['u_id'] = $u_id;
            $users=Db::name('users');
            $data['shop_name'] = $users->where(array('user_id'=>$u_id))->field('nickname')->value('nickname');
            $data['images'] = $users->where(array('user_id'=>$u_id))->field('head_pic')->value('head_pic');
            $data['phone'] = $users->where(array('user_id'=>$u_id))->field('mobile')->value('mobile');
            $data['goods_id'] ="0,";
            Db::name('users')->where('user_id',$u_id)->update(['referrer_id'=>$_SESSION['referrer_id']]);
    		$id=Db::name('distribution')->insertgetId($data);
            if ($id) {
                return array('status' => 1,'msg' => '恭喜您成为店主！将为您跳转至店主中心。','id' => $id);
            }
    	}
        //分享微信
        $jssdk = new JSSDKSS("wx218ea80c35624c8a", "77380763d58d20f6bbcb18d469b40f03");
        //$jssdk = new JSSDKSS("wxff94c9ef025ccb79", "08cb16a4467dd7a4c4af53507cc27a42");
        $signPackage = $jssdk->GetSignPackage();
        
        $this->assign('signPackage',$signPackage);
    	return $this->fetch();
    }

    /**
     * [Do_code 生成邀请码]
     * @return [type] [description]
     */
    public function Do_code(){
        for ($i=0; $i < 99999; $i++) { 
            $code = substr(md5("ax".rand(time(),$i)),0,6);//获取随机6位字符串
            $a = Db::name('distribution')->where(array('l_code'=>$code))->find();
            if (empty($a)) {
                return $code;
            }else{
                $i+1;
            }
        }
    }

    /**
     * [Index 店主中心]
     */
    public function Index(){
    	$id=$this->shop_id();
    	$u_id=$this->user_id;
    	$u=Db::name('distribution')->where('u_id',$u_id)->field('id,add_time')->find();
        
        

        //我的店铺信息
    	$distribution=Db::name('distribution')->alias('d')->join('users u','u.user_id=d.u_id')->where('id',$id)->field('u.nickname,u.head_pic,d.shop_name,d.l_code,d.r_id,d.id,d.shop_img,d.alipay,d.images')->find();
        if (empty($distribution['images']) and !empty($distribution['head_pic'])) {
            $distribution['images'] = $distribution['head_pic'];
            Db::name('distribution')->where('id',$id)->update(['images'=>$distribution['head_pic']]);
        }
    	//推荐人的店铺名称
    	$r_id=Db::name('distribution')->where('id',$distribution['r_id'])->field('shop_name')->find();

    	//查询提现列表中已提现和申请中的金额
    	$apply=Db::name('deposit')->where('d_id',$id)->select();
    	foreach ($apply as $key => $val) {
    		if ($val['finish'] == 1) {   		 //已申请（提现尚未完成）
    			$yet_apply+=$val['money'];
    			$yet_apply=sprintf("%.2f",$yet_apply);  			
    		}else if ($val['finish'] == 0) {     //已完成（提现已完成/已提现）
    			$yet_deposit+=$val['money'];
    			$yet_deposit=sprintf("%.2f",$yet_deposit);  //小数点			
    		}
    	}
        Db::name('distribution')->where('id',$id)->update(['yet_deposit'=>$yet_deposit]);

    	//查询订单收益
    	$earnings=Db::name('order_distribution')->where('u_id = '.$u_id.' || '.'r_id='.  $u_id)->where('order_type != 2')->where('add_time','>',$u['add_time'])->select();

    	//查询佣金分成比例
    	$ratio=Db::name('distribution_id')->where('id=1')->field('ratio,top_ratio,bonus')->find();
    	foreach ($earnings as $key => $value) {

            $a=DB::name('distribution')->where('u_id',$value['u_id'])->find();  //查询购买ID是否为店主
    		//计算除失效外的全部收益
    		if ($u_id==$value['u_id']) {
    			$value['moneys']+=$value['rebates']*$ratio['ratio'];            //店主自行购买的70%
    		}else{
                if ($a) {
                    $value['moneys']+=$value['rebates']*$ratio['top_ratio'];    //被推荐人店主购买的20%
                }else{
                    $value['moneys']+=$value['rebates']*$ratio['ratio'];        //被推荐人非店主购买的70%
                }
    		}
            $earnings_s+=sprintf("%.2f",$value['moneys']);      //全部收益
    		//计算冻结中（待审核）收益
    		if ($value['order_type'] == 0) {
    			if ($u_id==$value['u_id']) {
	    			$value['frozen']+=$value['rebates']*$ratio['ratio'];
	    		}else{
                    if ($a) {
                        $value['frozen']+=$value['rebates']*$ratio['top_ratio'];    //被推荐人店主购买的20%
                    }else{
                        $value['frozen']+=$value['rebates']*$ratio['ratio'];        //被推荐人非店主购买的70%
                    }
	    		}
    		$frozen+=sprintf("%.2f",$value['frozen']);           //冻结中
    		}
    		//计算已完成（可提现）收益
    		if ($value['order_type'] == 1) {
    			if ($u_id==$value['u_id']) {
	    			$value['ke_deposit']+=$value['rebates']*$ratio['ratio'];
	    		}else{
                    if ($a) {
                        $value['ke_deposit']+=$value['rebates']*$ratio['top_ratio']; //被推荐人店主购买的20%
                    }else{
                        $value['ke_deposit']+=$value['rebates']*$ratio['ratio'];     //被推荐人非店主购买的70%
                    }
	    		}
                $deposit+= sprintf("%.2f",$value['ke_deposit']);  //订单已完成的收益
    			$deposit = sprintf("%.2f",$deposit);
    		}
    	}
        $earnings_s     +=  $this->three_level($ratio['top_ratio'],5,1);  //全部收益
        $frozen         +=  $this->three_level($ratio['top_ratio'],0,1);  //冻结中
        $deposit        +=  $this->three_level($ratio['top_ratio'],1,1);  //已完成
        $earnings_s = sprintf("%.2f",$earnings_s);
        $frozen = sprintf("%.2f",$frozen);
        //可提现=已完成-已申请-已提现
        $ke_deposit=$deposit-$yet_apply-$yet_deposit; 
        $ke_deposit=sprintf("%.2f",$ke_deposit);
        Db::name('distribution')->where('id',$id)->update(['may_withdraw'=>$ke_deposit]);
    	$frozen 	  =   $frozen      ?  $frozen :      sprintf("%.2f",0);        //冻结中
    	$earnings_s   =   $earnings_s  ?  $earnings_s :  sprintf("%.2f",0);        //全部收益
    	$yet_apply    =   $yet_apply   ?  $yet_apply :   sprintf("%.2f",0);        //已申请
    	$yet_deposit  =   $yet_deposit ?  $yet_deposit : sprintf("%.2f",0);        //已提现

    	//商品数量
    	$where  = "is_on_sale = 1 AND examine = 1 AND is_designer = 0 AND is_distribution = 1";
		$where  .= " AND (commission_price != 0 OR cost_price != 0)";
    	$count = Db::name('goods')->where($where)->count();  //分销商品数量
        // $shops=$this->recommend(1);    //推荐店主的数量
        $shops=$this->myteam(1);          //我的邀请的数量

        $this->award();                   //更新奖励金及可提现的数据
        $may_withdraw = Db::name('distribution')->where('id',$id)->field('may_withdraw')->value('may_withdraw');
        $may_withdraw   =   $may_withdraw  ?  $may_withdraw :  sprintf("%.2f",0);   //可提现

        $url=$_SERVER['REQUEST_URI'];   //获取 http://localhost 后面的值，包括/

        $this->assign('url',$url);
        $this->assign('shops',$shops);
        $this->assign('yet_apply',$yet_apply);
        $this->assign('yet_deposit',$yet_deposit);
        $this->assign('count',$count);
        $this->assign('r_id',$r_id);
        $this->assign('ke_deposit',$may_withdraw);
        $this->assign('frozen',$frozen);
        $this->assign('earnings_s',$earnings_s);
        $this->assign('distribution',$distribution);
        $this->assign('id',$id);
    	return $this->fetch();
    }

    /**
     * [three_level 查询是否获得三级佣金]
     * @return [order_type] [description]
     */
    public function three_level($ratio,$order_type,$path=0){
        $id=$this->shop_id();
        $u_id=$this->user_id;
        //上级是‘我’的店主
        $r_id = DB::name('Distribution')->where('r_id',$id)->select();
        foreach ($r_id as $key => $value) {
            //购买订单的用户上级是我推荐的人
            $o_id = DB::name('Order_distribution')->where('r_id',$value['u_id'])->select();
            if ($o_id) {
                foreach ($o_id as $ke => $valu) {
                    $t_id = DB::name('distribution')->where('u_id',$valu['u_id'])->select();
                    if (!$t_id) {                                //第三级购买用户不是店主，‘我’得佣金的20%
                        if ($order_type == $valu['order_type']) {
                            $valu['shop'] = 1;                   //佣金20%
                            $order_list[] = $valu;
                            $frozen+=$valu['rebates']*$ratio;    //被推荐人店主购买的20% 按订单状态
                        }else if($order_type == 5 and $valu['order_type']!=2){
                            $valu['shop'] = 1;                   //佣金20%
                            $order_list[] = $valu;
                            $frozen+=$valu['rebates']*$ratio;    //被推荐人店主购买的20% 订单全部
                        }
                    }
                }
            }
        }
        if ($path == 2) {
            return $order_list;
        }else{
            return sprintf("%.2f",$frozen);
        }
    }

    /**
     * [award 奖励金]
     * @return [type] [description]
     */
    public function award(){
        $id=$this->shop_id();
        $u_id=$this->user_id;
        $info=Db::name('distribution_id')->where('id',1)->find();
        $r_id = DB::name('Distribution')->alias('d')->join('OrderDistribution o','d.u_id = o.u_id')->where('d.r_id',$id)->where('o.order_type = 1')->field('o.completion,d.u_id')->select();
        if (count($r_id) >= 3) {
            foreach ($r_id as $key => $value) {
                //计算本轮累计的奖励金
                if ($info['last_time'] < $value['completion'] and  $value['completion'] < $info['this_time']) 
                {   
                    //查询订单产生的佣金
                    $rebates += DB::name('OrderDistribution')->where('u_id',$value['u_id'])->field('rebates')->value('rebates');    
                    $rebates_bonus = $rebates * $info['ratio'];         //店主获取的佣金（70%）
                    $bonus = $rebates_bonus * $info['proportion'];      //奖励金为店主获取佣金中的比例（14%）
                }
                //计算本次之前历史累计获取的奖励金
                if($value['completion'] < $info['last_time'])
                {
                    $rebatess += DB::name('OrderDistribution')->where('u_id',$value['u_id'])->field('rebates')->value('rebates');
                    $rebatess_bonus = $rebatess * $info['ratio'];         //店主获取的佣金（70%）
                    $bonus_count = $rebatess_bonus * $info['proportion']; //奖励金为店主获取佣金中的比例（14%）
                }
            }
        }
        //本轮累计获取的奖励金
        $this_bonus = sprintf("%.2f",$bonus)?sprintf("%.2f",$bonus):sprintf("%.2f",0);   
        //本次之前历史累计获取的奖励金
        $bonus = sprintf("%.2f",$bonus_count)?sprintf("%.2f",$bonus_count):sprintf("%.2f",0);   

        if (time() > $info['this_time']) {      //当前时间超过本次开始计算的时间时将奖励金增加至可提现
            $update = [
                'may_withdraw' => ['exp','may_withdraw+'."$this_bonus".''],
                'this_bonus' => 0,
                'bonus' => $bonus,
            ];
            $a = Db::name('Distribution')->where('id',$id)->update($update);
            $c = Db::name('distribution_bonus')->where('shop_id',$id)->where('time',$info['this_time'])->find();
            if ($a and !$c) {                   //修改成功时增加执行日志，先查询避免重复入表
                $d = Db::name('Distribution')->where('id',$id)->field('shop_name,u_id')->find();
                $insert = [
                    'shop_id'       => $id,
                    'shop_name'     => $d['shop_name'],
                    'u_id'          => $d['u_id'],
                    'this_bonus'    => $this_bonus,
                    'time'          => $info['this_time'],
                ];
                Db::name('distribution_bonus')->where('id',$id)->insert($insert);

                $updates = [                     //修改增加奖励金已发放的金额
                    'closed' => ['exp','closed+'."$this_bonus".''],
                ];
                Db::name('distribution_id')->where('id',1)->update($updates);
            }
        }else{
            $update = [
                'this_bonus' => $this_bonus,
                'bonus'      => $bonus,
            ];
            Db::name('Distribution')->where('id',$id)->update($update);
        }
    }

    /**
     * [Deposit 已提现列表]
     */
    public function Deposit(){
    	$id=$this->shop_id();
    	$u_id=$this->user_id;
    	$u=Db::name('distribution')->where('u_id',$u_id)->field('id')->find();
        if(empty($id) || empty($u)){
            $this->error("您还不是店主，请先申请开店",Url::build('/Mobile/Distribution/apply'));
        }
    	$deposit=Db::name('deposit')->where('d_id',$id)->where('finish = 0')->select();
    	// dump($deposit);die;
        $this->assign('deposit',$deposit);
        $this->assign('id',$id);
    	return $this->fetch();
    }

    /**
     * [myShop 我的店铺]
     * @return [type] [description]
     */
    public function myShop(){
    	$id=$this->shop_id();
    	$u_id=$this->user_id;
    	$u=Db::name('distribution')->where('u_id',$u_id)->field('id')->find();
        if(empty($id) || empty($u)){
            $this->error("您还不是店主，请先申请开店",Url::build('/Mobile/Distribution/apply'));
        }
        // 店铺信息
    	$distribution=Db::name('distribution')->alias('d')->join('users u','u.user_id=d.u_id')->where('id',$id)->field('u.nickname,u.head_pic,d.shop_name,d.l_code,d.shop_img,d.goods_id,d.shop_id,d.id,d.shop_brief')->find();

        //加入店铺及查询已加入的相关都停用
        //查询已加入的商品数量
        // if (!empty($distribution['goods_id'])) {
        //     $goodss = Db::name('goods')->where("goods_id", "in", $distribution['goods_id'])->field('goods_name,goods_thumb,shop_price,cost_price,goods_id,is_on_sale')->select();
        //     foreach ($goodss as $key => $value) {
        //         if ($value['is_on_sale'] != 1) {    //将下架产品从收藏中删除 REGEXP 正则表达式 包含
        //             Db::query("update ylt_distribution set goods_id=REPLACE(goods_id,'$value[goods_id],','') where id = '$id'");
        //         }
        //     }
        //     $goods = array_filter($goodss);
        // }   
         
        //显示分销商城所有商品数量
        $goods=Db::name('goods')->where(['is_on_sale'=>1,'examine'=>1,'is_designer'=>0,'is_distribution'=>1])->where('commission_price!=0 || cost_price!=0')->field('goods_name,shop_price,commission_price,cost_price,goods_thumb,goods_id')->order('sort desc,goods_id desc')->select();


        //查询已收藏的店铺数量
    	if (!empty($distribution['shop_id'])) {
    		$shops = Db::name('distribution')->where("id", "in", $distribution['shop_id'])->select();
    		$shop = array_filter($shops);
    	}
    	
    	$num['goods_id'] = count($goods);
    	$num['shop_id'] = count($shop);
        $this->assign('id',$id);
        $this->assign('num',$num);
        $this->assign('distribution',$distribution);
    	return $this->fetch();
    }     

    /**
     * [ajaxGoods 收藏商品]
     * @return [type] [description]
     */
    // public function ajaxGoods(){
    //     $id=Db::name('distribution')->where('u_id',$this->user_id)->field('id')->value('id');
    //     $id=$id?$id:0; 
    //     $shop_id=I('post.shop_id');
    //     if ($shop_id) {
    //         $distribution = Db::name('distribution')->where('id',$shop_id)->find();
    //     }else{
    //         $distribution = Db::name('distribution')->where('id',$id)->find();
    //     }
    //     $keyword = urldecode(trim(I('keyword',''))); // 关键字搜索
    //     //搜索收藏的商品
    //     $array_goods= array();
    //     if(!empty(I('keyword'))){
    //     $cid=I('post.id');
    //         $distribution = Db::name('distribution')->where('id',$cid)->find();
    //         $goods_id=explode(',',$distribution['goods_id']);
    //         $goods_id_s=array_filter($goods_id);
    //         for ($i=1; $i <count($goods_id_s) ; $i++) { 
    //         $goods_k=Db::name('goods')->where("goods_id", $goods_id_s[$i])->where("`goods_name` LIKE '%".$keyword."%'")->field('goods_name,goods_thumb,shop_price,cost_price,goods_id')->cache(true,YLT_CACHE_TIME)->find();
    //             if ($goods_k['cost_price']!=0) {
    //                 $goods_k['distribution_price'] = $goods_k['shop_price'] - $goods_k['cost_price'];
    //             }
    //             if (!empty($goods_k)) {
    //                 $array_goods[]=$goods_k;  
    //             }
    //         }
    //         if (!empty($array_goods)) {
    //             $this->assign('goods',$array_goods);
    //         }
    //     }else{
    //         if (!empty($distribution['goods_id'])) {
    //             $goodss = Db::name('goods')->where("goods_id", "in", $distribution['goods_id'])->where('is_on_sale=1')->field('goods_name,goods_thumb,shop_price,cost_price,goods_id')->select();
    //             $goodss = array_filter($goodss);
    //         }
    //         if ($goodss) {
    //             foreach ($goodss as $key => $value) {
    //                 if ($value['cost_price']!=0) {
    //                     $value['distribution_price'] = $value['shop_price'] - $value['cost_price'];
    //                 }
    //                 $goods[]=$value;
    //             }
    //         }
    //         $this->assign('goods',$goods);
    //     }
    //     $ratio=Db::name('distribution_id')->where('id=1')->field('ratio,top_ratio')->find(); //查询佣金分成比例
    //     $this->assign('ratio',$ratio);
    //     $this->assign('id',$id);
    //     $this->assign('distribution',$distribution);
    //     return $this->fetch();
    // }
    public function ajaxGoods(){
        $p = I('p/d',1);
        $goods=Db::name('goods')->where(['is_on_sale'=>1,'examine'=>1,'is_designer'=>0,'is_distribution'=>1])->where('commission_price!=0 || cost_price!=0')->field('goods_name,shop_price,commission_price,cost_price,goods_thumb,goods_id')->order('sort desc,goods_id desc')->page($p,config('PAGESIZE'))->cache(true,YLT_CACHE_TIME)->select();
        $this->assign('goods',$goods);
        return $this->fetch();
    }

    /**
     * [ajaxShop 收藏店铺]
     * @return [type] [description]
     */
    public function ajaxShop(){
        $id=Db::name('distribution')->where('u_id',$this->user_id)->field('id')->value('id');
        $id=$id?$id:0; 

    	$shop_id=I('post.shop_id');
    	if ($shop_id) {
    		$distribution = Db::name('distribution')->where('id',$shop_id)->field('shop_id,shop_name')->find();
    	}else{
    		$distribution = Db::name('distribution')->where('id',$id)->field('shop_id,shop_name')->find();
    	}
    	if (!empty($distribution['shop_id'])) {
            $shops = Db::name('distribution')->where("id", "in", $distribution['shop_id'])->field('images,shop_name,id')->select();
    		$shop = array_filter($shops);
    	}
        $this->assign('shop',$shop);
        $this->assign('id',$id);
        return $this->fetch();
    }


    /**
     * 我的推广二维码
     */
    public function maxCard(){
    	$id=$this->shop_id();
        $user=session('user');
        $users_id=$user['user_id'];
        $this->qrcode2($users_id);
        $maxCard=Db::name('distribution')->alias('d')->join('users u','u.user_id=d.u_id')->where('u_id',$users_id)->field('u.head_pic,u.nickname,d.shop_name,d.u_id,d.qr_code')->find();
        $this->assign('maxCard',$maxCard);
        $this->assign('id',$id);
        return $this->fetch();
    }

    // 二维码带logo，存进文件夹
    public function qrcode2($users_id=0){
        //带LOGO
        $url = 'http://www.yilitong.com/mobile/Distribution/wxAuthfx/users_id/'.$users_id; //二维码内容
        $errorCorrectionLevel = 'L';//容错级别  
        $matrixPointSize = 9;//生成图片大小  
        //生成二维码图片  
        Vendor('phpqrcode.phpqrcode');
        $object = new \QRcode();
        $ad = 'vendor/phpqrcode/wxcode/'.$users_id.'code.jpg';
        $object->png($url, $ad, $errorCorrectionLevel, $matrixPointSize, 2);  
        $logo = 'http://www.yilitong.com/vendor/phpqrcode/images/logo2.png';//准备好的logo图片 
        $QR = 'vendor/phpqrcode/wxcode/'.$users_id.'code.jpg';//已经生成的原始二维码图  

        if ($logo !== FALSE) {  
          $QR = imagecreatefromstring(file_get_contents($QR));  
          $logo = imagecreatefromstring(file_get_contents($logo));  
          $QR_width = imagesx($QR);//二维码图片宽度  
          $QR_height = imagesy($QR);//二维码图片高度  
          $logo_width = imagesx($logo);//logo图片宽度  
          $logo_height = imagesy($logo);//logo图片高度  
          $logo_qr_width = $QR_width / 5;  
          $scale = $logo_width/$logo_qr_width;  
          $logo_qr_height = $logo_height/$scale;  
          $from_width = ($QR_width - $logo_qr_width) / 2;  
          //重新组合图片并调整大小  
          imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,  
          $logo_qr_height, $logo_width, $logo_height);  
        }
        //输出图片  带logo图片
        imagepng($QR, 'vendor/phpqrcode/wxcode/'.$users_id.'code.png'); 
        $data['qr_code']='/vendor/phpqrcode/wxcode/'.$users_id.'code.png';
        Db::name('distribution')->where('u_id',$users_id)->update($data);
    }

    /*
    *二维码分享，获取上级ID
    */
    public function wxAuthfx(){
        $users_id = input('users_id'); // 分享人id
        $id=Db::name('distribution')->where("u_id", $users_id)->field('id')->value('id');
        $user = array();
        if (session('?user')) {
            $user = session('user');
            $user = Db::name('users')->where("user_id", $user['user_id'])->find();
            session('user', $user);  //覆盖session 中的 user
            if(!empty($users_id)&&$users_id!=$user['user_id']){
                if(empty($user['referrer_id'])){
                    Db::name('users')->where("user_id", $user['user_id'])->update(array('referrer_id'=>$users_id));
                }
            }
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
                    $wxuser['referrer_id'] = $users_id;
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
        $this->redirect(Url::build("/Mobile/Distribution/shopDetails/id/0/shop_id/$id"));
    }

    /**
     * [shopInfo 店铺信息]
     * @return [type] [description]
     */
    public function shopInfo(){
    	$id=$this->shop_id();
    	$distribution=Db::name('distribution')->where('id',$id)->field('shop_name,shop_brief,phone,id')->find();
    	if (IS_POST) {
    		$data=I('');
    		$y=Db::name('distribution')->where('id',$data['id'])->update($data);
		    return array('status' => 1,'msg' => '保存成功','id'  =>'',);
    	}
    	$this->assign('distribution',$distribution);
        $this->assign('id',$id);
        return $this->fetch();
    }
    public function json_shopInfo(){
    	$id=$this->shop_id();
        $distribution=Db::name('distribution')->where('id',$id)->field('shop_name,shop_brief,phone,id')->find();
    	$json_data=json_encode($distribution, JSON_HEX_TAG);
        echo $json_data;
        exit();
    }

    /**
     * [myAttention index关注的店铺]
     * [myteam index 我推荐了的用户名单 我的邀请]
     * @return [type] [description]
     */
    public function myteam($a=''){
    	$id=$this->shop_id();
        $u_id=$this->user_id;
        $referrer_id = Db::name('users')->where('referrer_id',$u_id)->field('user_id,head_pic,nickname')->select();
        foreach ($referrer_id as $key => $value) {
            $d=Db::name('distribution')->where('u_id',$value['user_id'])->field('id')->value('id');
            if ($d) {
                $value['id'] = $d;
            }
            $referrer[] = $value;
        }
        $count=count($referrer);
        if ($a==1) {
            return $count;
        }
        $this->assign('count',$count);
        $this->assign('referrer_id',$referrer);
        return $this->fetch();
    }

    // /**
    //  * [recommend index推荐的店主]
    //  * @return [type] [description]
    //  */
    // public function recommend($type=0){
    //     $id=$this->shop_id();
    //     $u_id=$this->user_id;
    //     $distribution = Db::name('distribution')->where('r_id',$u_id)->field('id,shop_name')->select();
    //     if (!empty($distribution)) {
    //         foreach ($distribution as $key => $value) {
    //             $shops[] = Db::name('distribution')->alias('d')->join('users u','u.user_id=d.u_id')->where("d.id",$value['id'])->field('u.head_pic,u.nickname,d.shop_name,d.id,d.images')->find();
    //             $shop = array_filter($shops);
    //         }
    //     }
    //     if ($type==1) {
    //         $shop=count($shop);
    //         return $shop;
    //     }
    //     $this->assign('id',$id);
    //     $this->assign('shop',$shop);
    //     return $this->fetch();
    // }

    /**
     * [shopDetails TA关注的店铺]
     * @return [type] [description]
     */
    public function shopDetails(){
        $u_id=$this->user_id;
        if (!$u_id) {
            $this->error('请先登陆',Url('User/index'));
        }
        $id=Db::name('distribution')->where('u_id',$u_id)->field('id')->value('id');
        $id=$id?$id:0; 
    	$shop_id=$_GET['shop_id'];
    	$distribution=Db::name('distribution')->alias('d')->join('users u','u.user_id=d.u_id')->where('id',$shop_id)->field('u.nickname,d.images,d.shop_name,d.l_code,d.shop_img,d.goods_id,d.shop_id,d.id,d.shop_brief')->find();

        //商品收藏暂时弃用
        // if (!empty($distribution['goods_id'])) {
        //     $goodss = Db::name('goods')->where("goods_id", "in", $distribution['goods_id'])->field('goods_name,goods_thumb,shop_price,cost_price,goods_id,is_on_sale')->select();
        //     foreach ($goodss as $key => $value) {
        //         if ($value['is_on_sale'] != 1) {    //将下架产品从收藏中删除
        //             Db::query("update ylt_distribution set goods_id=REPLACE(goods_id,'$value[goods_id],','') where id='$shop_id'");
        //         }
        //     }
        //     $goods = array_filter($goodss);
        // }
        
        $goods=Db::name('goods')->where(['is_on_sale'=>1,'examine'=>1,'is_designer'=>0,'is_distribution'=>1])->where('commission_price!=0 || cost_price!=0')->field('goods_name,shop_price,commission_price,cost_price,goods_thumb,goods_id')->order('sort desc,goods_id desc')->select();
        $num['goods_id'] = count($goods);
        
    	if (!empty($distribution['shop_id'])) {
    		$shops = Db::name('distribution')->where("id", "in", $distribution['shop_id'])->select();
    		$shop = array_filter($shops);
    	}
    	$num['shop_id'] = count($shop);
    	if (IS_AJAX) {
		    	$data['id']=I('post.id');
		    	$data['shop_id']=I('post.shop_id');
		    	$data['uid']=I('post.uid');
		    	$u=Db::name('distribution')->where('u_id',$data['uid'])->field('id')->find();
		        if(empty($data['id']) || empty($u)){
		            $this->error("您还不是店主无法关注店铺，请先申请开店",Url::build('/Mobile/Distribution/apply'));
		        }
    		if (I('post.value') == '1') {
		    	//CONCAT 插入字符串
		        Db::query("update ylt_distribution  set shop_id=CONCAT(shop_id,'$data[shop_id],') where u_id='$data[uid]'");
			    return array('code' => 1,'msg' => '关注成功','id'  =>'',);
    		}else if(I('post.value') == '2'){
        		Db::query("update ylt_distribution set shop_id=REPLACE(shop_id,'$data[shop_id],','') where u_id = '$data[uid]'");
			    return array('code' => 2,'msg' => '取消成功','id'  =>'',);
    		}
    	}
    	$uid=$this->user_id;
    	//查询是否存在某段字符串
        $r=Db::query("select * from ylt_distribution where u_id=$uid and FIND_IN_SET('$shop_id', shop_id)");
        $this->assign('r',$r);
        $this->assign('id',$id);
        $this->assign('shop_id',$shop_id);
        $this->assign('num',$num);
        $this->assign('distribution',$distribution);
        return $this->fetch();
    }

    /**
     * [storeIndex 分享商城首页]
     * @return [type] [description]
     */
    public function storeIndex(){
        ignore_user_abort(true);  //客户机断开不会终止脚本的执行
        $id=$this->shop_id();
        $a = time();
        
    	//轮播图
        $brand_roll = Db::name('ad')->where('pid=48 and enabled=1')->where("end_time>'$a'")->order('orderby DESC')->limit('0,5')->select();

        //交易排行榜
        $time_s=Db::name('distribution_id')->where('id=1')->value('list_time'); //某个周一零点的时间戳
        if ($time_s+604800  <= $a) {   //加一周自动更新
            // $list_time = Db::name('distribution_id')->where('id=1')->update(['list_time'=>$a]);
        	$list_time = Db::name('distribution_id')->where('id=1')->update(['list_time'=>$time_s+604800]);
            if ($list_time) {       //时间戳更新后计算上一周金额排行，存进表中以便查询
                $time=date(strtotime("-1 week",strtotime(date('Y-m-d',$time_s))));  //前一周
                $user = Db::name('distribution')->field('u_id')->select();
                //计算订单中店主作为上级ID的合计金额
                foreach ($user as $key => $value) {
                $order[]= Db::name('order_distribution')->where("r_id = ".$value['u_id']." and order_type = 1")->where('payment_time','>',$time)->field('order_money,r_id')->select();
                }
                if ($order) {
                    foreach ($order as $ke => $valu) {
                        foreach ($valu as $k => $val) {
                            $orders[$val['r_id']]['moneys']=0;
                            $orders[$val['r_id']]['r_id']=0;
                            $orders[$val['r_id']][]=$val;
                            for ($i=0; $i < count($orders[$val['r_id']]); $i++) { 
                                $orders[$val['r_id']]['r_id']=$orders[$val['r_id']][0]['r_id'];
                                $orders[$val['r_id']]['moneys']+=$orders[$val['r_id']][$i]['order_money'];
                                $data[$val['r_id']] = $orders[$val['r_id']]['moneys'];
                            }
                        }
                    }
                }
                //计算订单中店主自行购买的合计金额
                foreach ($user as $key => $value) {
                $order_s[]= Db::name('order_distribution')->where("u_id = ".$value['u_id']." and order_type = 1")->where('payment_time','>',$time)->field('order_money,u_id')->select();
                }
                if ($order_s) {
                    foreach ($order_s as $ke => $valu) {
                        foreach ($valu as $k => $val) {
                            $orders_s[$val['u_id']]['moneys']=0;
                            $orders_s[$val['u_id']]['u_id']=0;
                            $orders_s[$val['u_id']][]=$val;
                            for ($i=0; $i < count($orders_s[$val['u_id']]); $i++) { 
                                $orders_s[$val['u_id']]['u_id']=$orders_s[$val['u_id']][0]['u_id'];
                                $orders_s[$val['u_id']]['moneys']+=$orders_s[$val['u_id']][$i]['order_money'];
                                $data_s[$val['u_id']] = $orders_s[$val['u_id']]['moneys'];
                            }
                        }
                    }
                }
                //计算为上级ID和自行购买的订单总金额
                @$data_ss=array_diff_key($data,$data_s);   //比较键名不同的数组
                if ($data_ss) {
                    foreach ($data_ss as $key => $value) { //将只成为上级ID而没有自行购买的订单单独处理进数组
                        $data_s[$key]=$value;
                        unset($data[$key]);                //避免下方重复相加，移除已处理数据
                    }
                }
                if ($data) {
                    foreach ($data as $ke => $val) {       //将成为上级并有自行购买的ID订单额度相加存入数组
                        foreach ($data_s as $k => $v) {
                            if($ke==$k){
                                $data_s[$ke]=$v+$val;
                            }
                        }
                    }
                }
                if ($data_s) {
                    arsort($data_s);   //排序
                }else{
                    $this->redirect('Mobile/Distribution/storeIndex');
                }
                $a = array();
                foreach ($data_s as $key => $val) {
                    $a['r_id'] = $key;
                    $a['moneys'] = sprintf("%.2f",$val);
                    $a['shop_name'] = Db::name('distribution')->where('u_id',$key)->field('shop_name')->value('shop_name');
                    $a['images'] = Db::name('distribution')->where('u_id',$key)->field('images')->value('images');
                    $a['shop_id'] = Db::name('distribution')->where('u_id',$key)->field('id')->value('id');
                    $arsort[]=$a;
                }
                for ($i=0; $i < 10; $i++) {    //只取排名前10的数据
                    $arsorts[]=$arsort[$i];
                }
                if ($arsorts) {
                    Db::execute('TRUNCATE table ylt_rankinglist');  //清空表
                    foreach ($arsorts as $key => $arv) {            //存入表
                        Db::name('rankinglist')->insert($arv);
                    }
                }
            }
        }

        //查询排行
        $rankinglist=Db::name('rankinglist')->order('id asc')->select();
        foreach ($rankinglist as $key => $ran) {
            $ran['moneys'] = sprintf("%.2f",$ran['moneys']);
            $rankinglists[] = $ran;
        }  
        // dump($rankinglists);die;

		//热卖推荐
		$goods_hot=Db::name('goods')->where(['is_on_sale'=>1,'examine'=>1,'is_designer'=>0,'is_hot'=>1,'is_distribution'=>1])->where('commission_price!=0 || cost_price!=0')->field('goods_name,shop_price,commission_price,cost_price,goods_thumb,goods_id')->limit(0,12)->order('sort desc,goods_id desc')->select();
        foreach ($goods_hot as $key => $value) {
            if ($value['cost_price']!=0) {
                $value['distribution_price'] = $value['shop_price'] - $value['cost_price'];
            }
            $goods_hots[]=$value;
        }

        $ratio=Db::name('distribution_id')->where('id=1')->field('ratio,top_ratio')->find(); //查询佣金分成比例

        $this->assign('ratio',$ratio);
        $this->assign('goods_hot',$goods_hots);
        $this->assign('arsort',$rankinglists);
        $this->assign('brand_roll',$brand_roll);
        $this->assign('id',$id);
        return $this->fetch();
    }

    /**
     * [ajaxGoods_rec 好物专区]
     * @return [type] [description]
     */
    public function ajaxGoods_rec(){
    	$p = I('p/d',1);
		$goods_rec=Db::name('goods')->where(['is_on_sale'=>1,'examine'=>1,'is_designer'=>0,'is_recommend'=>1,'is_distribution'=>1])->where('commission_price!=0 || cost_price!=0')->field('goods_name,shop_price,commission_price,cost_price,goods_thumb,goods_id')->order('sort desc,goods_id desc')->page($p,config('PAGESIZE'))->cache(true,YLT_CACHE_TIME)->select();
        foreach ($goods_rec as $key => $value) {
            if ($value['cost_price']!=0) {
                $value['distribution_price'] = $value['shop_price'] - $value['cost_price'];
            }
            $goods_recs[]=$value;
        }

        $ratio=Db::name('distribution_id')->where('id=1')->field('ratio,top_ratio')->find(); //查询佣金分成比例

        $this->assign('ratio',$ratio);
    	$this->assign('goods_rec',$goods_recs);
    	return $this->fetch();
    }

    /**
     * [ajaxSearch 分销商城商品搜索]
     * @return [type] [description]
     */
    public function search(){
        return $this->fetch();
    }
    public function ajaxSearchGoods(){
    	$filter_param = array(); // 帅选数组
        // $sort = I('types'); // 排序
    	$sort = I('types','goods_id'); // 排序
    	$sort_asc = I('sort_asc'); // 排序
        $q = urldecode(trim(I('q',''))); // 关键字搜索
        $q  && ($_GET['q'] = $filter_param['q'] = $q); //加入筛选条件中
		$where  = "is_on_sale = 1 AND examine = 1 AND is_designer = 0 AND is_distribution = 1";
		$where  .= " AND (commission_price != 0 OR cost_price != 0)";
		$keywords = $q;
        if ($sort_asc==0) {
			$sort_asc='desc';
		}else if($sort_asc==1){
			$sort_asc='asc';
		}
        if ($sort) {
            if($sort==2){
                $sort='sales_sum';
            }else if($sort==3){
                $sort='shop_price';
            }else if($sort==4){
                $sort='is_new';
            }
        }
		if(!empty(I('q'))){
			$arr = array();
			if (stristr($keywords, ' AND ') !== false)
			{
				/* 检查关键字中是否有AND，如果存在就是并 */
				$arr        = explode('AND', $keywords);
				$operator   = " AND ";
			}
			elseif (stristr($keywords, ' OR ') !== false)
			{
				/* 检查关键字中是否有OR，如果存在就是或 */
				$arr        = explode('OR', $keywords);
				$operator   = " OR ";
			}
			elseif (stristr($keywords, ' + ') !== false)
			{
				/* 检查关键字中是否有加号，如果存在就是或 */
				$arr        = explode('+', $keywords);
				$operator   = " OR ";
			}
			else
			{
				/* 检查关键字中是否有空格，如果存在就是并 */
				$arr        = explode(' ', $keywords);
				$operator   = " AND ";
			}

			$where .= ' AND (';
			foreach ($arr AS $key => $val)
			{
				if ($key > 0 && $key < count($arr) && count($arr) > 1)
				{
					$where .= $operator;
				}
				
				$where .= " (`goods_name` LIKE '%".$val."%' OR `keywords` LIKE '%".$val."%' OR `goods_sn` LIKE '%".$val."%.' )";
				if(Db::name('keywords')->where('keyword',$val)->value('keyword'))
					DB::name('keywords')->where('keyword',$val)->setInc('count');
				else
					Db::name('keywords')->insert(['date'=>date('Y-m-d'),'searchengine'=>'ylt','keyword'=>$val,'count'=> 1,'source'=>'move']);
			}
			
			$where .= ')';
		}else{
			
			$where .= " AND (`goods_name` LIKE '%".$keywords."%' OR `keywords` LIKE '%".$keywords."%' OR `goods_sn` LIKE '%".$keywords."%.' )";
		}
        
    	$goodsLogic = new \ylt\home\logic\GoodsLogic(); // 前台商品操作逻辑类
    	$filter_goods_id = Db::name('goods')->where($where)->cache(true,YLT_CACHE_TIME)->column("goods_id");
    	$count = count($filter_goods_id);
    	$page = new Page($count,12);
    	if($count > 0)
    	{
    		$goods_list = Db::name('goods')->where("goods_id", "in", implode(',', $filter_goods_id))->order("$sort $sort_asc")->limit($page->firstRow.','.$page->listRows)->select();
    	}
        if ($goods_list) {
            foreach ($goods_list as $key => $value) {
                if ($value['cost_price']!=0) {
                    $value['distribution_price'] = $value['shop_price'] - $value['cost_price'];
                }
                $goods_lists[]=$value;
            }
        }else{
            return array('status' => -1,'msg' => '暂无相关商品','id'  =>'');
        }
        $ratio=Db::name('distribution_id')->where('id=1')->field('ratio,top_ratio')->find(); //查询佣金分成比例

        $this->assign('ratio',$ratio);
    	$this->assign('goods_list',$goods_lists);
    	$this->assign('filter_param',$filter_param); // 帅选条件    	
        $this->assign('page',$page);// 赋值分页输出
        config('TOKEN_ON',false);
        if(input('is_ajax')){
            $this->assign('sort_asc',$sort_asc);// 赋值分页输出
            return $this->fetch('ajaxSearchGoods');
        }else{
            return $this->fetch();
        }
    }

    /**
     * 商品详情页
     */
    public function goodsInfos(){
        $storeIndex=$_SERVER['HTTP_REFERER'];
        if (empty($storeIndex)  || strpos($storeIndex,'mp.weixin.qq.com')) {
            $this->assign('storeIndex',-1);
        }
        if (strpos($storeIndex,'/Mobile/Distribution/storeIndex') || strpos($storeIndex,'/Mobile/Distribution/search')) {
            $this->assign('storeIndex',1);
        }
        // unset($_SESSION);
        $goods_id = I("get.id/d");
       	$uid=$this->user_id;
        $id=Db::name('distribution')->where('u_id',$uid)->field('id')->value('id');
             
        $goodsLogic = new \ylt\home\logic\GoodsLogic();
		
        $goods = Db::name('Goods')->where("goods_id",$goods_id)->cache(true,YLT_CACHE_TIME)->find();
		
        $goods['discount'] = $goods->discount;
        if(empty($goods) || ($goods['is_on_sale'] == 0) || ($goods['is_distribution'] == 0)){
            $this->error('此商品不存在或者已下架');
        }
        if($goods['brand_id']){
            $brnad = Db::name('brand')->where("id", $goods['brand_id'])->find();
            $goods['brand_name'] = $brnad['name'];
        }

        $goods_images_list = Db::name('GoodsImages')->where("goods_id", $goods_id)->order('img_id desc')->select(); // 商品 图册
		$filter_spec = $goodsLogic->get_spec($goods_id);
        $spec_goods_price  = Db::name('goods_price')->where("goods_id", $goods_id)->column("key,price,store_count"); // 规格 对应 价格 库存表
        $this->assign('spec_goods_price', json_encode($spec_goods_price,true)); // 规格 对应 价格 库存表

        //查询销量
      	$goods['sale_num'] = Db::name('order_goods')->where(['goods_id'=>$goods_id,'is_send'=>1])->count(); 

        //商品收藏数
        $logo = Db::name('supplier_config')->where(array('supplier_id'=>$goods['supplier_id'],'name'=>'store_logo'))->value('value'); 

      	//查询当前商品是否加入过店铺
        $r=Db::query("select * from ylt_distribution where u_id=$uid and FIND_IN_SET('$goods_id', goods_id)");

        //处理显示的佣金
        if ($goods['cost_price']!=0) {
            $goods['distribution_price'] = $goods['shop_price'] - $goods['cost_price'];
        }
        //查询佣金分成比例
        $ratio=Db::name('distribution_id')->where('id=1')->field('ratio,top_ratio')->find(); 


        //分享微信
        $jssdk = new JSSDKSS("wx218ea80c35624c8a", "77380763d58d20f6bbcb18d469b40f03");
        //$jssdk = new JSSDKSS("wxff94c9ef025ccb79", "08cb16a4467dd7a4c4af53507cc27a42");
        $signPackage = $jssdk->GetSignPackage();
        
        $this->assign('signPackage',$signPackage);
        $this->assign('ratio',$ratio);
        $this->assign('collect',1);
        $this->assign('filter_spec',$filter_spec);//规格参数
        $this->assign('goods_images_list',$goods_images_list);//商品缩略图
        $this->assign('r',$r);
        $this->assign('id',$id);
        $this->assign('goods',$goods);
        $this->assign('logo',$logo); //商品收藏人数
        return $this->fetch();
    }
    /*
    *分享微信授权登录 获取个人微信的信息
    */
    public function phoneAuthfx()
    {
        $referrer_id = input('referrer_id'); // 分享人id
        $goods_id = input('id'); // 商品id
    
        $user = array();
        if (session('?user')) {
            $user = session('user');
            $user = Db::name('users')->where("user_id", $user['user_id'])->find();
            session('user', $user);  //覆盖session 中的 user
           if(!empty($referrer_id)&&$referrer_id!=$user['user_id']){
                if(empty($user['referrer_id'])){
                    Db::name('users')->where("user_id", $user['user_id'])->update(array('referrer_id'=>$referrer_id));
                }
            }

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
                    $wxuser['referrer_id'] = $referrer_id;
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
        if ($goods_id) {
            $this->redirect(Url::build("/Mobile/Distribution/goodsInfos/id/$goods_id"));
        }else{
            $l_code = Db::name('distribution')->where('u_id',$referrer_id)->value('l_code');
            $this->redirect(Url::build("/Mobile/Distribution/apply/l_code/$l_code/r_id/$referrer_id"));
        }
    }


    // /**
    //  * [collect_goods 收藏商品至我的店铺]
    //  * @return [type] [description]
    //  */
    // public function collect_goods(){
    //     $id=$this->shop_id();
    //     $u_id=$this->user_id;
    //     $goods_id=I('goods_id');
    //     //CONCAT 插入字符串
    //     Db::query("update ylt_distribution  set goods_id=CONCAT(goods_id,'$goods_id,') where u_id='$u_id'");
    //     return array('status' => 1,'msg' => '加入成功','id'  =>'',);
    // }
    // /**
    //  * [collect_goods 取消店铺商品]
    //  * @return [type] [description]
    //  */
    // public function delete_goods(){
    //     $u_id=$this->user_id;
    //     $goods_id=I('goods_id');
    //     //REPLACE 替换字符串
    //     Db::query("update ylt_distribution set goods_id=REPLACE(goods_id,'$goods_id,','') where u_id = '$u_id'");
    //     return array('status' => 1,'msg' => '取消成功','id'  =>'',);
    // }

    /**
     * [returnsDetail 收益的订单明细]
     * @return [type] [description]
     */
    public function returnsDetail(){
        return $this->fetch();
    }
    public function ajaxReturnsDetail(){
    	$id=$this->shop_id();
    	$u_id=$this->user_id;
        $add_time=Db::name('distribution')->where('u_id='.$u_id)->field('add_time')->value('add_time'); //成为店主后的时间
    	$data=I('');
    	if ($data['type']==1) {
            $a = $this->three_level($ratio['top_ratio'],5,2);               //第三级有佣金的订单
    		//查询上级ID或付款为自己的全部订单
            $order_lists = $this->order_type('',$u_id,$add_time);                     //根据订单状态查询列表
            if ($a) {
                $order_lists = array_merge($a,$order_lists);
            }
            $order_list = $this->order_lists($order_lists,$u_id);           //查询被推荐人是否为店主
    	}else if($data['type']==2){
    		//查询待审核状态的订单
            $a = $this->three_level($ratio['top_ratio'],0,2);               //第三级有佣金的订单
            $order_lists = $this->order_type(1,$u_id,$add_time);                      //根据订单状态查询列表
            if ($a) {
                $order_lists = array_merge($a,$order_lists);
            }
            $order_list = $this->order_lists($order_lists,$u_id);           //查询被推荐人是否为店主
    	}else if($data['type']==3){
    		//查询已完成的订单
            $a = $this->three_level($ratio['top_ratio'],1,2);               //第三级有佣金的订单
            $order_lists = $this->order_type(2,$u_id,$add_time);                      //根据订单状态查询列表
            if ($a) {
                $order_lists = array_merge($a,$order_lists);
            }
            $order_list = $this->order_lists($order_lists,$u_id);           //查询被推荐人是否为店主
    	}else if($data['type']==4){
    		//查询无效的订单
            $a = $this->three_level($ratio['top_ratio'],2,2);               //第三级有佣金的订单
            $order_lists = $this->order_type(3,$u_id,$add_time);                      //根据订单状态查询列表
            if ($a) {
                $order_lists = array_merge($a,$order_lists);
            }
            $order_list = $this->order_lists($order_lists,$u_id);           //查询被推荐人是否为店主
    	}
    	//获取订单商品
        $model = new UsersLogic();
        if ($order_list) {
            foreach ($order_list as $k => $v) {
                $order_list[$k] = set_btn_order_status($v);  // 添加属性  包括按钮显示属性 和 订单状态显示属性
                $data = $model->get_order_goods($v['order_id']);
                $data['result'][0]['name']=Db::name('users')->where('user_id',$v['u_id'])->field('nickname')->value('nickname');
                $order_list[$k]['goods_list'] = $data['result'];
            }
        }
    	$ratio=Db::name('distribution_id')->where('id=1')->field('ratio,top_ratio')->find(); //查询佣金分成比例
    	$this->assign('u_id',$u_id);
    	$this->assign('ratio',$ratio);
    	$this->assign('order_list',$order_list);
        return $this->fetch();
    }

    /**
     * [order_type 根据订单状态查询列表]
     * @param  string $order_type [description]
     * @param  [type] $u_id       [description]
     * @return [type]             [description]
     */
    public function order_type($order_type='',$u_id,$add_time){
        $field = "r.*,o.order_id";
        if ($order_type) {
            $order_type=$order_type-1;
            $order_lists=Db::name('order_distribution')->alias('r')->join('order o','r.order_id = o.order_id')->where("r.u_id = $u_id || r.r_id = $u_id")->where(' r.order_type = '.$order_type)->where('r.add_time','>',$add_time)->field($field)->order('r.order_id desc')->select();
        }else{
            $order_lists=Db::name('order_distribution')->alias('r')->join('order o','r.order_id = o.order_id')->where("r.u_id = $u_id || r.r_id = $u_id")->where('r.add_time','>',$add_time)->field($field)->order('r.order_id desc')->select();
        }
        return $order_lists;
    }
    /**
     * [order_lists 查询被推荐人是否为店主 给状态值 前端根据被推荐人是否为店主进行佣金计算]
     * @param  [type] $order_lists [description]
     * @return [type]              [description]
     */
    public function order_lists($order_lists,$u_id){
        foreach ($order_lists as $key => $value) {
            if ($value['r_id'] == $u_id) {
                $a = DB::name('distribution')->where('u_id',$value['u_id'])->find();
                if ($a) {
                    $value['shop'] = 1;     //佣金20%
                }else{
                    $value['shop'] = 0;     //佣金70%
                }
            }
            $order_list[] = $value;
        }
        return $order_list;
    }



    /**
     * [deposit_b1_account 绑定支付宝1/2/3/4]
     * @return [type] [description]
     */
    public function deposit_b1_account(){
    	$id=$this->shop_id();
    	$phone=Db::name('distribution')->where('id',$id)->field('phone')->value('phone');
        $phones = substr_replace($phone,'****',3,4);
    	$this->assign('id',$id);
    	$this->assign('phone',$phone);
    	$this->assign('phones',$phones);
        return $this->fetch();
    }
    public function deposit_b2_identity(){
    	$id=$this->shop_id();
    	if (IS_POST) {
    		$name=I('post.name');
    		$id_card=I('post.id_card');
    		$id=I('post.id');
    		$b2=Db::name('distribution')->where('id',$id)->update(['true_name'=>$name,'id_card'=>$id_card,'save_time'=>time()]);
    		if ($b2) {
			    return array('status' => 1,'msg' => '保存成功','id'  =>'');
    		}else{
			    return array('status' => 2,'msg' => '保存失败','id'  =>'');
    		}
    	}
    	$this->assign('id',$id);
        return $this->fetch();
    }
    public function deposit_b3_alipay(){
    	$id=$this->shop_id();
    	if (IS_POST) {
    		$alipay_name=I('post.alipay_name');
    		$alipay=I('post.alipay');
    		$id=I('post.id');
    		$b3=Db::name('distribution')->where('id',$id)->update(['alipay_name'=>$alipay_name,'alipay'=>$alipay,'save_time'=>time()]);
    		if ($b3) {
			    return array('status' => 1,'msg' => '保存成功','id'  =>'');
    		}else{
			    return array('status' => 2,'msg' => '保存失败','id'  =>'');
    		}
    	}
    	$this->assign('id',$id);
        return $this->fetch();
    }
    public function deposit_b4_setpassword(){
    	$id=$this->shop_id();
    	if (IS_POST) {
            $deposit_pw=encrypt(I('post.deposit_pw'));
            $pSix=encrypt(I('post.pSix'));
            if ($deposit_pw==$pSix) {
                $b4=Db::name('distribution')->where('id',$id)->update(['deposit_pw'=>$deposit_pw,'save_time'=>time()]);
                if ($b4) {
                    return array('status' => 1);
                }else{
                    return array('status' => -1,'msg' => '保存失败，请联系平台管理员');
                }
            }else{
                return array('status' => -2,'msg' => '两次密码输入不一致');
            }
        }
    	$this->assign('id',$id);
        return $this->fetch();
    }

    /**
     * [deposit_test 提现页面]
     * @return [type] [description]
     */
    public function deposit_test(){
    	$id=$this->shop_id();
    	$test=Db::name('distribution')->where('id',$id)->field('alipay,may_withdraw')->find();
        $alipays = substr_replace($test['alipay'],'****',3,4);
        if (IS_POST) {
        	$money=I('post.money');
        	$numArr=implode('',I('post.numArr/a'));
        	$deposit_pw=encrypt($numArr);
        	$apply=Db::name('distribution')->where("id = $id")->field('may_withdraw,deposit_pw,shop_name,alipay_name')->find();
            if ($apply['may_withdraw'] < $money) {
                return array('status'=>-2,'msg'=>'输入金额大于可提现收益');
                exit;
            }else if($money < 10){
                return array('status'=>-3,'msg'=>'提现金额小于10元');
                exit;
            }
        	if ($apply['deposit_pw'] != $deposit_pw) {
           		return array('status'=>-1);
           		exit;
        	}else{
                Db::name('deposit')->insert(['money'=>$money,'into_time'=>time(),'alipay'=>I('post.alipay'),'d_id'=>$id,'shop_name'=>$apply['shop_name'],'alipay_name'=>$apply['alipay_name']]);
                return array('status' => 1,'msg' => '提交成功，请耐心等待');
            }
        }
    	$this->assign('id',$id);
    	$this->assign('test',$test);
    	$this->assign('alipays',$alipays);
        return $this->fetch();
    }

    /**
     * [deposit_ws_set 提现相关设置]
     * @return [type] [description]
     */
    public function deposit_ws_set(){
    	$id=$this->shop_id();
    	$this->assign('id',$id);
        return $this->fetch();
    }
    /**
     * 修改支付宝账号1/2/3
     */
    public function deposit_ma1_account(){
    	$id=$this->shop_id();
    	$phone=Db::name('distribution')->where('id',$id)->field('phone')->value('phone');
        $phones = substr_replace($phone,'****',3,4);
    	$this->assign('id',$id);
    	$this->assign('phone',$phone);
    	$this->assign('phones',$phones);
        return $this->fetch();
    }
    public function deposit_ma2_passwVerify(){
    	$id=$this->shop_id();
    	if (IS_POST) {
    		$data=I('');
    		$password=encrypt($data['password']);
    		$ma2 = Db::name('distribution')->where("id = $data[id]")->field('deposit_pw')->find();
    		if ($ma2['deposit_pw'] == $password) {
                return array('status' => 1);
    		}else{
                return array('status' => -1,'msg' => '密码错误，请重新输入');
            }
    	}
    	$this->assign('id',$id);
        return $this->fetch();
    }
    public function deposit_ma3_alipay(){
        $id=$this->shop_id();
        if (IS_POST) {
            $alipay_name=I('post.alipay_name');
            $alipay=I('post.alipay');
            $id=I('post.id');
            $ma3=Db::name('distribution')->where('id',$id)->update(['alipay_name'=>$alipay_name,'alipay'=>$alipay,'save_time'=>time()]);
            if ($ma3) {
                return array('status' => 1,'msg' => '保存成功','id'  =>'');
            }else{
                return array('status' => 2,'msg' => '保存失败','id'  =>'');
            }
        }
        $this->assign('id',$id);
        return $this->fetch();
    }

    /**
     * [deposit_mw1_account 修改提现密码1/2/3]
     * @return [type] [description]
     */
    public function deposit_mw1_account(){
        $id=$this->shop_id();
        $phone=Db::name('distribution')->where('id',$id)->field('phone')->value('phone');
        $phones = substr_replace($phone,'****',3,4);
        $this->assign('id',$id);
        $this->assign('phone',$phone);
        $this->assign('phones',$phones);
        return $this->fetch();
    }
    public function deposit_mw2_passwVerify(){
       $id=$this->shop_id();
        if (IS_POST) {
            $data=I('');
            $password=encrypt($data['password']);
            $ma2 = Db::name('distribution')->where("id = $data[id]")->field('deposit_pw')->find();
            if ($ma2['deposit_pw'] == $password) {
                return array('status' => 1);
            }else{
                return array('status' => -1,'msg' => '密码错误，请重新输入');
            }
        }
        $this->assign('id',$id);
        return $this->fetch();
    }
    public function deposit_mw3_setpassword(){
       $id=$this->shop_id();
        if (IS_POST) {
            $deposit_pw=encrypt(I('post.deposit_pw'));
            $comNewPassw=encrypt(I('post.comNewPassw'));
            if ($deposit_pw==$comNewPassw) {
                // Db::name('distribution')->where('id',$id)->update(['deposit_pw'=>'']);
                $mw3=Db::name('distribution')->where('id',$id)->update(['deposit_pw'=>$deposit_pw,'save_time'=>time()]);
                if ($mw3) {
                    return array('status' => 1);
                }else{
                    return array('status' => -1,'msg' => '保存失败，请联系平台管理员');
                }
            }else{
                return array('status' => -2,'msg' => '两次密码输入不一致');
            }
        }
        $this->assign('id',$id);
        return $this->fetch();
    }

    /**
     * [deposit_f1_account 忘记提现密码1/2/3]
     * @return [type] [description]
     */
    public function deposit_f1_account(){
        $id=$this->shop_id();
        $phone=Db::name('distribution')->where('id',$id)->field('phone')->value('phone');
        $phones = substr_replace($phone,'****',3,4);
        $this->assign('id',$id);
        $this->assign('phone',$phone);
        $this->assign('phones',$phones);
        return $this->fetch();
    }
    public function deposit_f2_passwVerify(){
        $id=$this->shop_id();
        if (IS_POST) {
            $name=I('post.name');
            $id_card=I('post.id_card');
            $id=I('post.id');
            $f2=Db::name('distribution')->where(['id' => $id])->field('true_name,id_card')->find();
            if ($f2['true_name'] != $name) {
                return array('status' => -1,'msg' => '真实姓名填写错误');
            }else if($f2['id_card'] != $id_card){
                return array('status' => -2,'msg' => '身份证六位数填写错误');
            }else if($f2['id_card'] == $id_card and $f2['true_name']==$name){
                return array('status' => 1);
            }
        }
        $this->assign('id',$id);
        return $this->fetch();
    }
    public function deposit_f3_setpassword(){
        $id=$this->shop_id();
        if (IS_POST) {
            $deposit_pw=encrypt(I('post.deposit_pw'));
            $comNewPassw=encrypt(I('post.comNewPassw'));
            if ($deposit_pw==$comNewPassw) {
                // Db::name('distribution')->where('id',$id)->update(['deposit_pw'=>'']);
                $mw3=Db::name('distribution')->where('id',$id)->update(['deposit_pw'=>$deposit_pw,'save_time'=>time()]);
                if ($mw3) {
                    return array('status' => 1);
                }else{
                    return array('status' => -1,'msg' => '保存失败，请联系平台管理员');
                }
            }else{
                return array('status' => -2,'msg' => '两次密码输入不一致');
            }
        }
        $this->assign('id',$id);
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
