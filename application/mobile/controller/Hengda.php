<?php
/**
 * Created by PhpStorm.
 * User: zyl
 * Date: 2019/06/21
 * Time: 17:30
 * name:恒大表单
 */
namespace ylt\mobile\controller;
use think\Controller;
use ylt\home\logic\CartLogic;
use think\Url;
use think\Page;
use think\Db;

class Hengda extends MobileBase {
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
            }else{   
                    session('login_url',$_SERVER[REQUEST_URI]);
				    header("location:" . Url::build('User/login'));
                    exit;
			}
        }
    }

	public function account(){
        return $this->fetch();
	}
	/**
	 * [hengda 恒大首次送货+安装]
	 * @return [type] [description]
	 */
	public function hengda(){
		if (IS_AJAX && IS_POST) {
			$data=I('');

			$time=$this->time();
			// if(!array_search($data['deliveryTime'],$time[0])){
			// 	return array('status' => 3,'msg' => '预约的时间不在可送货时间范围内',);
			// }
			// if(!array_search($data['installDate'],$time[1])){
			// 	return array('status' => 4,'msg' => '预约的时间不在可安装时间范围内',);
			// }
			if($data['installDate'] <= $data['deliveryTime']){
				return array('status' => 5,'msg' => '先送货才可安装',);
			}
			$data['deliveryTime']=strtotime($data['deliveryTime']);
			$data['installDate']=strtotime($data['installDate']);
			$data['add_time']=time();
			$d_count=Db::name('hengda')->where('deliveryTime',$data['deliveryTime'])->count();
			$i_count=Db::name('hengda')->where('installDate',$data['installDate'])->count();
			if ($d_count >= 40) {
				return array('status' => 6,'msg' => '预约送货人数已满，请换个日期预约',);
			}
			if ($i_count >= 25) {
				return array('status' => 6,'msg' => '预约安装人数已满，请换个日期预约',);
			}
			$save = Db::name('hengda')->where('tel',$data['tel'])->update($data);
            
			if ($save == false) {
				$hengda=Db::name('hengda')->insert($data);
			}else{
				return array('status' => 2,'msg' => '恭喜您，已修改成功',);
			}
			if ($hengda) {
				return array('status' => 1,'msg' => '恭喜您，已预约成功',);
			}else{
				return array('status' => -1,'msg' => '提交失败',);
			}
		}
        return $this->fetch();
	}

	/**
	 * [hengda_up 恒大首次补货]
	 * @return [type] [description]
	 */
	public function hengda_up(){
		if ($_GET['mobile']) {
			$phone = $_GET['mobile'];
		}else{
      		$phone = input('tel');
		}
		$find=Db::name('hengda')->where('tel',$phone)->find();
		if (empty($find)) {
			$this->error('该手机号没有预约记录');
		}
    	$this->assign('find',$find);

		if (IS_AJAX && IS_POST) {
			$data=I('');
			$times=$this->times();
			// if(!array_search($data['replenishmentTime'],$times[0])){
			// 	return array('status' => 3,'msg' => '预约的时间不在集中配送时间范围内',);
			// }
			$data['replenishmentTime']=strtotime($data['replenishmentTime']);
			$data['add_time']=time();
			$save = Db::name('hengda')->where('tel',$data['tel'])->update($data);
			if ($save) {
            	return array('status' => 2,'msg' => '恭喜您，已修改成功',);
			}
		}
        return $this->fetch();
	}

	public function time(){
		$deliveryTime = array();
		$installDate = array();
		$deliveryTime=[
			'2019-08-15',
			'2019-08-16',
			'2019-08-17',
			'2019-08-18',
			'2019-09-14',
			'2019-09-15',
			'2019-09-16',
			'2019-10-18',
			'2019-10-19',
			'2019-10-20',
			'2019-11-15',
			'2019-11-16',
			'2019-11-17',
			'2019-12-14',
			'2019-12-15',
			'2019-12-16',
		];
		$installDate=[
			'2019-08-15',
			'2019-08-16',
			'2019-08-17',
			'2019-08-18',
			'2019-08-19',
			'2019-08-20',
			'2019-09-14',
			'2019-09-15',
			'2019-09-16',
			'2019-09-17',
			'2019-09-18',
			'2019-10-18',
			'2019-10-19',
			'2019-10-20',
			'2019-10-21',
			'2019-10-22',
			'2019-11-15',
			'2019-11-16',
			'2019-11-17',
			'2019-11-18',
			'2019-11-19',
			'2019-12-14',
			'2019-12-15',
			'2019-12-16',
			'2019-12-17',
			'2019-12-18',
		];
		return array($deliveryTime,$installDate);
	}

	public function times(){
		$replenishmentTime = array();
		$replenishmentTime=[
			'2019-08-22',
			'2019-08-23',
			'2019-08-24',
		];
		return array($replenishmentTime);
	}

	public function tanch2(){
        return $this->fetch();
	}

}