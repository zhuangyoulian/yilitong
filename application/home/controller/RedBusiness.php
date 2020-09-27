<?php
namespace ylt\home\controller;
use ylt\home\logic\SupplierLogic;
use ylt\home\model\UsersLogic;
use think\Verify;
use think\Db;
use think\Url;
use think\Request;


class RedBusiness extends Base{
    public $user='';
    public $_session="";
    public $InsertId;
    
    public function _initialize() {
    	parent::_initialize();
        // truncate table ylt_redsupplier_user   清空表
    	/*  session_destroy(); */
        // session('redsupplier',Db::name('redsupplier_user')->where("redsupplier_id",133)->find());
    	if(!session('?redsupplier'))
    	{
    		$login = array('BusinessOne','BusinessTwo','upload','BusinessTwoPro','BusinessFour');
    		if(in_array(ACTION_NAME,$login)){
    			$this->redirect('Home/RedBusiness/login');
    			exit;
    		}
    	}else{
	    	$redsupplier = session('redsupplier');
	    	$red_admin_id=$redsupplier['red_admin_id'];
	    	$this->assign("redsupplier",$redsupplier);
	    	//如果登录了，看是否已填写入驻商资料
	    	//dump($redsupplier);exit;
			$row = Db::name('redsupplier_user')->where('red_admin_id',$red_admin_id)->find();
			session('redsupplier',$row);
	    	if(!empty($row) && $row['redsupplier_name'] && $row['status']!=2){
		    	$action = array('BusinessOne','BusinessTwo','BusinessTwoPro','verify','forget_pwd');
	    		if(in_array(ACTION_NAME,$action)){
	    			$this->redirect('Home/RedBusiness/BusinessFour');
	        	   exit;
	    		}
	        }
    	}
    }
  
      /**
     * 退出
     */

    public function logout(){
        setcookie('supplier_name','',time()-3600,'/');
        setcookie('redsupplier_name','',time()-3600,'/');
        setcookie('cn','',time()-3600,'/');
        setcookie('supplier_admin_id','',time()-3600,'/');
        setcookie('redsupplier_red_admin_id','',time()-3600,'/');
        session_unset();
        session_destroy();
        $this->redirect('Home/Index/index');
        exit;

    }
    
    /**
     * @function BusinessIndex() //商家入驻
     * @return mixed
     */
    public function BusinessIndex(){
            $redsupplier = session('redsupplier');
            $this->assign('redsupplier_id',$redsupplier['redsupplier_id']);
    		return $this->fetch();
    	
    }
    /**
     * @function BusinessOne()  商家入驻申请
     * @pmarm null
     * @return null
     */
    public function BusinessOne(){
    	$redsupplier = session('redsupplier');
    	if(!empty($redsupplier['red_admin_id'])){
    		$red_admin_id=$redsupplier['red_admin_id'];
    		//如果登录了，看是否已填写入驻商资料
    		$row=Db::name('redsupplier_user')->where(array('red_admin_id'=>$red_admin_id))->find();
        if ($row['is_complete'] ==1 and $row['status']==0) {
          $this->redirect('Home/RedBusiness/BusinessFour');
        }
    		$this->assign('row',$row);
    	}
    	
        return $this->fetch();
    }

    // /**
    //  * @function BusinessOnePro()  入驻申请处理1
    //  * @pamarm null
    //  * @return max
    //  */
    // public function BusinessOnePro( ){
    //     exit;
    // }
    
    // /**
    //  * @function Testing() 检测手机号
    //  * @param $Contacts_phone
    //  * @return  bool
    //  */
    // public static function Testing( $Contacts_phone ){
    //     if( strlen( $Contacts_phone ) != 11  || !preg_match( "/^1[3|4|5|7|8][0-9]\d{4,8}$/",$Contacts_phone ) ){
    //         echo json_encode(array('rs'=>'1'));exit;
    //     }
    // }

    // /**
    //  * @function TestingEmail() 检测Email
    //  * @param $Email
    //  * @return bool
    //  */
    // public static function TestingEmail( $Email ){
    //     if( !preg_match("/(\S)+[@]{1}(\S)+[.]{1}(\w)+/",$Email ) ){
    //         echo json_encode(array('rs'=>'2'));exit;
    //     }
    // }
    
    // /**
    //  * @function TestingCompany_name() 检测公司名称
    //  * @param $Company_name
    //  * @return array
    //  */
    // public static function TestingCompany_name( $Company_name ){
    //     if( Db::table('ylt_redsupplier_user')->where( 'company_name' ,$Company_name )->find() == true ){
    //        echo json_encode(array('rs'=>'3'));exit;
    //     }
    // }
    // /**
    //  * @function TestingBusiness_licence_number() 检测企业营业执照号
    //  * @param $Business_licence_number
    //  * @return array
    //  */
    // public static function TestingBusiness_licence_number( $Business_licence_number ){
    //     if( Db::table('ylt_redsupplier_user')->where( 'business_licence_number' ,$Business_licence_number )->find() == true ){
    //         echo json_encode(array('rs'=>'4'));exit;
    //     }
    // }
    // /**
    //  * @function TestingOrganization_code() 检测组织机构代码
    //  * @param $Organization_code
    //  * @return array
    //  */
    // public static function TestingOrganization_code( $Organization_code ){
    //     if( Db::table('ylt_redsupplier_user')->where( 'organization_code' ,$Organization_code )->find() == true ){
    //         echo json_encode(array('rs'=>'5'));exit;
    //     }
    // }
    // /**
    //  * @function TsetingIsNull() 检测是否为空
    //  * @param $isNull
    //  * @return  bool
    //  */
    // public static function TsetingIsNull( $isNull ){
    //     if( empty( $isNull ) ){
    //         echo json_encode(array('rs'=>'6'));exit;
    //     }
    // }
    
    /**
     * @function BusinessTwo() 商家入驻申请2
     * @return mixed
     */
    public function BusinessTwo(){
        $redsupplier = session('redsupplier');
    	if(!empty($redsupplier['red_admin_id'])){
    		$red_admin_id=$redsupplier['red_admin_id'];
    		//如果登录了，看是否已填写入驻商资料
    		$row=Db::name('redsupplier_user')->where( 'red_admin_id',$red_admin_id)->find();
    		$this->assign("row",$row);
    		if(!empty($row['zhizhao']) && !empty($row['organization_code_electronic']) && !empty($row['general_taxpayer'])){
    			$this->assign("type",'1');
    		}else{
    			$this->assign("type",'0');
    		}
    	}
        return $this->fetch();
    }

    /**
     * [upload 保存图片]
     * @return [type] [description]
     */
    public function upload(){
    	$f = $_FILES['file'];
    	$dir=$this->set_dir("public/upload/business","{y}/{m}/{d}");
    	$ext=$this->get_file_ext($f['name']);
        //判断文件格式
        $str=strtolower(pathinfo($f['name'],PATHINFO_EXTENSION));
        $image=array('webp','jpg','png','ico','bmp','gif','tif','pcx','tga','bmp','tiff','jpeg','exif','fpx','svg','psd','cdr','pcd');
        if(!in_array($str,$image)){
            exit('3');
        }
    	$filename = $dir.time().rand(10000,99999).".".$ext;
    	if((1024*1024)<$f['size']){
    		exit('2');
    	}
    	if(move_uploaded_file($f['tmp_name'], $filename)){
    		exit("/".$filename);
    	}else{
    		exit('1');
    	}
    }
    public function get_file_ext($filename)
    {
    	return strtolower(substr(strrchr($filename, '.'), 1, 10));
    } 
    public function set_dir($basedir, $file_dir = '')
    {
        $dir = rtrim($basedir,'/').'/';
        if (!empty($file_dir)) {
            $file_dir = str_replace(array('{y}', '{m}', '{d}'), array(date('Y',time()), date('m',time()), date('d',time())), strtolower($file_dir));
            $this->rand_dir = $file_dir;
            $dirs = explode('/', $file_dir);
            foreach ($dirs as $d) {
                !empty($d) && $dir .= $d . '/';
            }
            !is_dir($dir) && @mkdir($dir, 0755, true);
        }
        return $dir;
    }
    
    //修改或者增加入驻商资料
    public function BusinessOne_save(){
    	$red_admin_id=$_SESSION["redsupplier"]['red_admin_id'];
    	$company_name=$_POST['company_name'];
    	$address=$_POST['address'];
    	$phone_number=$_POST['phone_number'];
    	$province=$_POST['province'];
    	$city=$_POST['city'];
        $area=$_POST['area'];
        $logo=$_POST['logo'];
        $operating_name=$_POST['operating_name'];
        $mobile=$_POST['mobile'];
        $contacts_name=$_POST['contacts_name'];
        $contacts_name_mobile=$_POST['contacts_name_mobile'];


    	if(empty($company_name) || empty($address) || empty($phone_number) || empty($province) || empty($city) || empty($area) || empty($operating_name) || empty($mobile) || empty($contacts_name) || empty($contacts_name_mobile))
        {
    	   exit('-1');
    	}
    	$redsupplier = Db::name('redsupplier_user')->where(array('red_admin_id'=>$red_admin_id))->find();

    	if($redsupplier){
    		//存在入驻资料信息
    		$sql = [ 
            'company_name' =>$company_name,
    		'address'=>$address,
    		'phone_number'=>$phone_number,
    		'province'=>$province,
    		'city'=>$city,
            'area'=>$area,
            'operating_name'=>$operating_name,
            'mobile'=>$mobile,
            'contacts_name'=>$contacts_name,
            'contacts_name_mobile'=>$contacts_name_mobile,
    		'logo'=>$logo,
    		];
    		$res = Db::name('redsupplier_user')->where(array('red_admin_id'=>$red_admin_id))->setField($sql);
    	}else{
    		//不存在入驻资料信息
    		$sql = [ 
            'company_name' =>$company_name,
    		'address'=>$address,
    		'add_time'=>time(),
    		'phone_number'=>$phone_number,
    		'province'=>$province,
    		'city'=>$city,
    		'area'=>$area,
            'operating_name'=>$operating_name,
            'mobile'=>$mobile,
            'contacts_name'=>$contacts_name,
            'contacts_name_mobile'=>$contacts_name_mobile,
            'logo'=>$logo,
    		];
    		$res=Db::name('redsupplier_user')->where(array('red_admin_id'=>$red_admin_id))->insert($sql);
    	}
    	if(false!==$res){
    		exit(true);
    	}else{
    		exit(false);
    	}
    }
    
    //保存第二部
    public function BusinessTwo_save(){
    	$red_admin_id=$_SESSION["redsupplier"]['red_admin_id'];
    	$business_licence_number=$_POST['business_licence_number'];
    	$bank_account_number=$_POST['bank_account_number'];
    	$bank_name=$_POST['bank_name'];
    	$bank_branch=$_POST['bank_branch'];
    	$is_three_one=$_POST['is_three_one'];
    	//营业执照片
    	$zhizhao=$_POST['zhizhao'];
    	//组织机构代码证电子
    	$organization_code_electronic=$_POST['organization_code_electronic'];
    	//纳税证明
    	$general_taxpayer=$_POST['general_taxpayer'];
    	//开户许可证
    	$bank_licence_electronic=$_POST['bank_licence_electronic'];
        //经营许可证
        $business_certificate=$_POST['business_certificate'];
        //入驻协议
        $reading_protocol=$_POST['reading_protocol'];
    	// 运营者授权证书
    	// $proxy=$_POST['proxy'];
    	if(empty($business_licence_number) || empty($bank_account_number) || empty($bank_name) || empty($bank_branch) || empty($zhizhao) ){
            	 exit('-1');
            }


    	$sql = [ 'business_licence_number' =>$business_licence_number,
    	'bank_account_number'=>$bank_account_number,
    	'bank_name'=>$bank_name,
    	'bank_account_name'=>$bank_branch,
    	'zhizhao'=>$zhizhao,
    	'organization_code_electronic'=>$organization_code_electronic,
    	'general_taxpayer'=>$general_taxpayer,
    	'bank_licence_electronic'=>$bank_licence_electronic,
        'business_certificate'=>$business_certificate,
    	// 'proxy'=>$proxy,
        'reading_protocol'=>$reading_protocol,
        'is_three_one'=>$is_three_one,
        'is_complete'=>1,
    	'status'=>0,
    	];
    	$res = Db::name('redsupplier_user')->where(array('red_admin_id'=>$red_admin_id))->setField($sql);
    	if(false!==$res){
    		exit(true);
    	}else{
    		exit(false);
    	}
    }
    
    
    //验证公司名称是否被注册
    public function checkAjax(){
    	$company_name=trim($_POST['company_name']);
    	$redsupplier_name=trim($_POST['redsupplier_name']);
    	if(!empty($company_name)){
    		$rows = Db::name('redsupplier_user')->where("company_name","{$company_name}")->find();
    		if(!empty($rows)){
    			echo "1";
    			exit;
    		}else{
    			echo "2";
    			exit;
    		}
    	}
    }
  
    
    /**
     * @function BusinessFour() 商家入驻申请4
     * @return mixed
     */
    public function BusinessFour(){
    	$red_admin_id=$_SESSION['redsupplier']['red_admin_id'];
    	$redsupplier = Db::name('redsupplier_user')->where("red_admin_id",$red_admin_id)->find();
    	if(empty($redsupplier['company_name'])){
    	   $this->redirect('BusinessOne');
    	   exit;
    	}

    	$this->assign('username',$redsupplier['company_name']);
    	$this->assign('status',$redsupplier['status']);
        return $this->fetch();
    }
    
    //商家登录
    public function login(){
	    /*  $referurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : Url::build("/"); */
	     $this->assign('referurl',Url::build("/"));
	     return $this->fetch();
    } 

    public function ajax_login(){
    	$mobile = trim(I('post.mobile'));
    	$password = trim(I('post.password'));
    	$verify_code = I('post.verify_code');
    	
    	$verify = new Verify();
    	if (!$verify->check($verify_code,'cus_login'))
    	{
    		$res = array('status'=>0,'msg'=>'验证码错误');
    		exit(json_encode($res));
    	}
    	$logic = new supplierLogic();
    	$res = $logic->red_user_login($mobile,$password);
    	if($res['status'] == 1){
    		session('redsupplier',$res['result']);
    	    if($res['result']['status'] == 1){
                $condition['red_admin_id'] = $res['result']['red_admin_id'];
                $admin_info = Db::name('redsupplier_user')->where($condition)->find();              
                session('red_admin_id',$admin_info['red_admin_id']);
                // session('redsupplier_id',$admin_info['redsupplier_id']);
                Db::name('redsupplier_user')->where("red_admin_id = ".$admin_info['red_admin_id'])->update(array('last_login'=>time(),'last_ip'=>  getIP()));
                $nickname = empty($res['result']['user_name']) ? $mobile : $res['result']['user_name'];
                setcookie('redsupplier_name',urlencode($nickname),null,'/');
				$this->adminlog($admin_info['user_name'].'后台登陆');
                setcookie('redsupplier_red_admin_id',$res['result']['red_admin_id'],null,'/');
                if(session('loginUrl')){
                    $res['url'] =  Url::build(substr(session('loginUrl'),0,-5));
                    unset($_SESSION['loginUrl']);
                }else{ 
                    $res['url'] =  Url::build('Home/RedBusiness/BusinessIndex');  
                }			
                exit(json_encode($res));
            }else{
                $res['url'] =  Url::build('Home/RedBusiness/BusinessFour');
                $rs=Db::name('redsupplier_user')->where("red_admin_id",$res['result']['red_admin_id'])->find();
                //setcookie('redsupplier_red_admin_id',$rs['redsupplier_id'],null,'/');
                setcookie('redsupplier_red_admin_id',$res['result']['red_admin_id'],null,'/');
                $nickname = empty($res['result']['user_name']) ? $mobile : $res['result']['user_name'];
                setcookie('redsupplier_name',urlencode($nickname),null,'/');
                setcookie('cn',0,time()-3600,'/');
            }
    	}
    	exit(json_encode($res));
    }
    //商家注册
    public function register(){
    	if($_POST['type']=="save"){
			$mobile = trim(I('post.mobile',''));
			$password = trim(I('post.password',''));
			$password2 = trim(I('post.password2',''));
			$code = trim(I('post.code',''));
			$session_id = session_id();
			$verify_code = trim(I('post.verify_code'));
			//验证码
			$userlogic = new UsersLogic();
			$res = $userlogic->check_validate_code($code, $mobile , $session_id , 'mobile');
			if ($res['status'] != 1){
				$this->error($res['msg']);
			}
			$supLogin = new supplierLogic();
			$data = $supLogin->redreg($mobile,$password,$password2,'');
			if($data['status'] != 1){
				$this->error($data['msg']);
				exit;
			}
            $res = $supLogin->red_user_login($mobile,$password);
            if($res){
                $res['url'] =  urldecode(I('post.referurl'));
                session('redsupplier',$res['result']);
                setcookie('redsupplier_red_admin_id',$res['result']['red_admin_id'],null,'/');
                $nickname = empty($res['result']['user_name']) ? $mobile : $res['result']['user_name'];
                setcookie('redsupplier_name',urlencode($nickname),null,'/');
                setcookie('cn',0,time()-3600,'/');
            }
    			$this->success($data['msg'],Url::build('Home/RedBusiness/BusinessIndex'));
    			exit;
    	}
    	return $this->fetch();
    }
	
	
    
    public function issetMobile()
    {
    	$mobile = I("mobile",'0');
    	$users = Db::name('redsupplier_user')->where("mobile",$mobile)->find();
    	if($users)
    		exit ('1');
    	else
    		exit ('0');
    }
	
	
	/*
	 * 忘记密码
	 */
	 public function forget_pwd(){
    	if($this->user_id > 0){
            $this->redirect('Home/RedBusiness/index');
    	}
    	if(IS_POST){
    		$logic = new UsersLogic();
    		$username = I('post.username');
    		$code = I('post.code');
    		$pass = false;
    		//检查是否手机找回
    		if(check_mobile($username)){
    			if(!$user = get_redsupplier_user_info($username,2))
    				$this->error('账号不存在');
                $check_code = $logic->sms_code_verify($username,$code,$this->session_id);
    			if($check_code['status'] != 1)
    				$this->error($check_code['msg']);
    			$pass = true;
    		}
    		if($data['status'] != 1)
    			$this->error($data['msg'] ? $data['msg'] :  '操作失败');
    		$this->success($data['msg'],Url::build('Home/RedBusiness/login'));
    		exit;
    	}
        return $this->fetch();
    }
	
	/**
     * 获取商家用户信息
     * @param $user_id_or_name  用户id 邮箱 手机 第三方id
     * @param int $type  类型 0 user_id查找 1 邮箱查找 2 手机查找 3 第三方唯一标识查找
     * @param string $oauth  第三方来源
     * @return mixed
     */
    function get_redsupplier_user_info($user_id_or_name,$type = 0,$oauth=''){
        $map = array();
        if($type == 0)
            $map['user_id'] = $user_id_or_name;
        if($type == 1)
            $map['email'] = $user_id_or_name;
        if($type == 2)
            $map['mobile'] = $user_id_or_name;

        $user = Db::name('redsupplier_user')->where($map)->find();
        return $user;
    }

	/**
	 * 验证用户是否存在
	 */
    public function check_username(){
    	$username = I('post.username');
    	if(!empty($username)){
    		$count = Db::name('redsupplier_user')->where("mobile", $username)->count();
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
            $this->redirect('Home/RedBusiness/Index');
    	}
    	$username = I('post.username');
    	$userinfo = array();
    	if($username){
    		$userinfo = Db::name('redsupplier_user')->where('mobile', $username)->find();
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
     
	 
	/**
	 * 忘记密码流程 修改密码
	 */
    public function edit_pwd(){
    	if($this->user_id > 0){
            $this->redirect('Home/RedBusiness/Index');
    	}
    	$check = session('validate_code');
    	$logic = new UsersLogic();
    	if(empty($check)){
            $this->redirect('Home/RedBusiness/authentication');
    	}elseif($check['is_check']==0){
    		$this->error('验证码还未验证通过',Url::build('Home/RedBusiness/authentication'));
    	}    	
    	if(IS_POST){
    		$password = I('post.password');
    		$password2 = I('post.password2');
    		if($password2 != $password){
    			$this->error('两次密码不一致',Url::build('Home/RedBusiness/authentication'));
    		}
    		if($check['is_check']==1){
    			//$user = get_user_info($check['sender'],1);
                $user = Db::name('redsupplier_user')->where("mobile", '=', $check['sender'])->find();
    			Db::name('redsupplier_user')->where("red_admin_id", $user['red_admin_id'])->update(array('password'=>encrypt($password)));
    			session('validate_code',null);
                $this->redirect('Home/RedBusiness/finished');
    		}else{
    			$this->error('验证码还未验证通过',Url::build('Home/RedBusiness/authentication'));
    		}
    	}
    	return $this->fetch();
    }
	
	
	/*
	 * 忘记密码流程 结束
	 */
	 public function finished(){
    	if($this->user_id > 0){
            $this->redirect('Home/RedBusiness/Index');
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
	
	/**
	 * 操作记录
	 */
	 
	private function adminLog($log_info){
        $add['log_time'] = time();
        $add['admin_id'] = session('red_admin_id');
        $add['log_info'] = $log_info;
        $add['log_ip'] = getIP();
        $add['log_url'] = request()->baseUrl();
    	// $add['supplier_id'] =  session('redsupplier_id');
        Db::name('redsupplier_admin_log')->insert($add);
	}
    
    /**
     * 退出登录
     * 
     */
	public function login_out(){
        setcookie('supplier_name','',time()-3600,'/');
        setcookie('redsupplier_name','',time()-3600,'/');
        setcookie('cn','',time()-3600,'/');
        setcookie('supplier_admin_id','',time()-3600,'/');
        setcookie('redsupplier_red_admin_id','',time()-3600,'/');
		session('redsupplier',null);
        session_unset();
        session_destroy();
	    $this->redirect('/Home/RedBusiness/login');
      	exit;
	}
	
	
}


