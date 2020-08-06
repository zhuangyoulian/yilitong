<?php
namespace ylt\home\controller;
use ylt\home\logic\SupplierLogic;
use ylt\home\model\UsersLogic;
use think\Verify;
use think\Db;
use think\Url;
use think\Request;


class Business extends Base{
    public $user='';
    public $_session="";
    public $InsertId;
    
    public function _initialize() {
    	parent::_initialize();
        if(!I('supplier_admin_id'))
    	{
    		$login = array('BusinessOne','BusinessTwo','upload','BusinessTwoPro','BusinessThreePro','BusinessThree','BusinessFour');
    		if(in_array(ACTION_NAME,$login)){
                exit(json_encode(array('status'=>-1,'msg'=>'请先登录')));
    		}
    	}else{
	    	$supplier_admin_id = I('supplier_admin_id');
	    	//如果登录了，看是否已填写入驻商资料
			$row = Db::name('supplier_user')->alias('u')->join('supplier s', array('s.supplier_id=u.supplier_id'),'left')->where('u.admin_id',$supplier_admin_id)->find();
	    	if(!empty($row) && $row['supplier_name'] && $row['status']!=2){
		    	$action = array('BusinessOne','BusinessTwo','BusinessTwoPro','BusinessThreePro','BusinessThree','verify','forget_pwd');
	    		if(in_array(ACTION_NAME,$action)){
                    exit(json_encode(array('status'=>-2,'msg'=>'等到审核中，跳转审核结果页面')));
	    		}
	        }
            $this->supplier = $row;
            exit(json_encode(array('status'=>1,'msg'=>'请求成功','supplier'=>$row)));
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

        exit(json_encode(array('status'=>1,'msg'=>'退出成功。')));
        // $this->redirect('Home/Index/index');
        // exit;
    }
    
  //   /**
  //    * @function BusinessIndex() //商家入驻
  //    * @return mixed
  //    */
  //   public function BusinessIndex(){
  //       $supplier = session('supplier');
  //       $this->assign('supplier_id',$supplier['supplier_id']);
		// return $this->fetch();
  //   }

    /**
     * @function BusinessOne()  商家入驻申请
     * @pmarm null
     * @return null
     */
    public function BusinessOne(){
    	$supplier = $this->supplier;
    	if(!empty($supplier['admin_id'])){
    		$admin_id=$supplier['admin_id'];
    		//如果登录了，看是否已填写入驻商资料
    		$row=Db::name('supplier')->where(array('user_id'=>$admin_id,'is_designer'=>0))->find();
			 if(!empty($row['province'])){
				$rs=Db::name('region')->where( 'id',$row['province'])->find();
				$row['address1']=$rs['name'];
				if(!empty($row['city'])){
					$rs=Db::name('region')->where( 'id',$row['city'])->find();
					$row['address1'].="-".$rs['name'];
				}
				if(!empty($row['area'])){
					$rs=Db::name('region')->where( 'id',$row['area'])->find();
					$row['address1'].="-".$rs['name'];
				}
			}	
            exit(json_encode(array('status'=>1,'msg'=>'请求成功','supplier'=>$row)));
    	}else{
            exit(json_encode(array('status'=>2,'msg'=>'请求成功','supplier'=>'')));
        }
    }
    // /**
    //  * @function BusinessOnePro()  入驻申请处理1
    //  * @pamarm null
    //  * @return max
    //  */
    // public function BusinessOnePro( ){
    //     exit;
    // }
    /**
     * @function Testing() 检测手机号
     * @param $Contacts_phone
     * @return  bool
     */
    public static function Testing( $Contacts_phone ){
        if( strlen( $Contacts_phone ) != 11  || !preg_match( "/^1[3|4|5|7|8][0-9]\d{4,8}$/",$Contacts_phone ) ){
            exit(json_encode(array('status'=>1)));
        }
    }
    /**
     * @function TestingEmail() 检测Email
     * @param $Email
     * @return bool
     */
    public static function TestingEmail( $Email ){
        if( !preg_match("/(\S)+[@]{1}(\S)+[.]{1}(\w)+/",$Email ) ){
            exit(json_encode(array('status'=>2)));
        }
    }
    /**
     * @function TestingCompany_name() 检测公司名称
     * @param $Company_name
     * @return array
     */
    public static function TestingCompany_name( $Company_name ){
        if( Db::table('ylt_supplier')->where( 'company_name' ,$Company_name )->find() == true ){
            exit(json_encode(array('status'=>3)));
        }
    }
    /**
     * @function TestingBusiness_licence_number() 检测企业营业执照号
     * @param $Business_licence_number
     * @return array
     */
    public static function TestingBusiness_licence_number( $Business_licence_number ){
        if( Db::table('ylt_supplier')->where( 'business_licence_number' ,$Business_licence_number )->find() == true ){
            exit(json_encode(array('status'=>4)));
        }
    }
    /**
     * @function TestingOrganization_code() 检测组织机构代码
     * @param $Organization_code
     * @return array
     */
    public static function TestingOrganization_code( $Organization_code ){
        if( Db::table('ylt_supplier')->where( 'organization_code' ,$Organization_code )->find() == true ){
            exit(json_encode(array('status'=>5)));
        }
    }
    /**
     * @function TsetingIsNull() 检测是否为空
     * @param $isNull
     * @return  bool
     */
    public static function TsetingIsNull( $isNull ){
        if( empty( $isNull ) ){
            exit(json_encode(array('status'=>6)));
        }
    }
    /**
     * @function BusinessTwo() 商家入驻申请2
     * @return mixed
     */
    public function BusinessTwo(){
        $supplier = $this->supplier;
    	if(!empty($supplier['admin_id'])){
    		$admin_id=$supplier['admin_id'];
    		//如果登录了，看是否已填写入驻商资料
    		$row=Db::name('supplier')->where( 'user_id',$admin_id)->find();
    		// if(!empty($row['zhizhao']) && !empty($row['organization_code_electronic']) && !empty($row['general_taxpayer'])){
      //           exit(json_encode(array('status'=>1,'type'=>1)));
    		// }else{
      //           exit(json_encode(array('status'=>1,'type'=>0)));
    		// }
            exit(json_encode(array('status'=>1,'supplier'=>$row)));
    	}
    }
    
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
        $supplier = $this->supplier;
    	$admin_id=$supplier['admin_id'];
        $data=I('');
    	$company_name=$data['company_name'];
    	$guimo=$data['guimo'];
        $business_sphere=$data['business_sphere'];
    	$business_describe=$data['business_describe'];
    	$address=$data['address2'];
    	$operating_name=$data['operating_name'];
    	$duties=$data['duties'];
    	$contacts_phone=$data['contacts_phone'];
    	$phone_number=$data['phone_number'];
    	$qq=$data['qq'];
    	$province=$data['province'];
    	$city=$data['city'];
    	$area=$data['area'];
    	$email=$data['email'];
    	if(empty($company_name) || empty($guimo) || empty($business_sphere) || empty($business_describe) || empty($data['address1']) || empty($operating_name) || empty($duties) || empty($contacts_phone) || empty($qq) || empty($email) ){
    	    exit('-1');
    	}

    	if(!empty($province) && !is_numeric($province)){
    		$rs=Db::name('region')->where( 'name',"{$province}")->find();
    		$province=$rs['id'];
    	}
    	if(!empty($city) && !is_numeric($city)){
    		$rs=Db::name('region')->where( 'name',"{$city}")->find();
    		$city=$rs['id'];
    	}
    	if(!empty($area) && !is_numeric($area)){
    		$rs=Db::name('region')->where('name',"{$area}")->find();
    		$area=$rs['id'];
    	}
    	
    	$supplier = Db::name('supplier')->where(array('user_id'=>$admin_id,'is_designer'=>0))->find();
    	if($supplier){
			
    		//存在入驻资料信息
    		$sql = [ 'company_name' =>$company_name,
    		'address'=>$address,
    		'guimo'=>$guimo,
    		'contacts_phone'=>$contacts_phone,
            'business_sphere'=>$business_sphere,
    		'introduction'=>$business_describe,
    		'operating_name'=>$operating_name,
    		'add_time'=>time(),
    		'duties'=>$duties,
    		'phone_number'=>$phone_number,
    		'qq'=>$qq,
    		'province'=>$province,
    		'city'=>$city,
    		'area'=>$area,
    		'email'=>$email
    		];

    		$res = Db::name('supplier')->where(array('user_id'=>$admin_id,'is_designer'=>0))->setField($sql);
    	}else{
    		//不存在入驻资料信息
    		$sql = [ 'company_name' =>$company_name,
    		'address'=>$address,
    		'guimo'=>$guimo,
    		'contacts_phone'=>$contacts_phone,
    		'business_sphere'=>$business_sphere,
            'introduction'=>$business_describe,
    		'operating_name'=>$operating_name,
    		'add_time'=>time(),
    		'duties'=>$duties,
    		'phone_number'=>$phone_number,
    		'qq'=>$qq,
    		'user_id'=>$admin_id,
    		'province'=>$province,
    		'city'=>$city,
    		'area'=>$area,
    		'email'=>$email
    		];

    		$db=Db::name( 'supplier' );
    		$res =  $db->insert($sql);
    		$id=$db->getLastInsID($res);
    		$ll=Db::name('supplier_user')->where(array('admin_id'=>$admin_id))->update(array('supplier_id'=>$id));
    	}
    	if(false!==$res){
    		exit(true);
    	}else{
    		exit(false);
    	}
    }
    
    //保存第二部
    public function BusinessTwo_save(){
        $supplier = $this->supplier;
        $supplier_id=$supplier['admin_id'];
        $data=I('');
    	//$supplier_id=$_COOKIE['supplier_admin_id'];
    	//$supplier_id=$_SESSION["supplier"]['admin_id'];
    	$business_licence_number=$data['business_licence_number'];
    	$bank_account_number=$data['bank_account_number'];
    	$bank_name=$data['bank_name'];
    	$bank_branch=$data['bank_branch'];
    	$is_three_one=$data['is_three_one'];
    	//营业执照片
    	$zhizhao=$data['zhizhao'];
    	//组织机构代码证电子
    	$organization_code_electronic=$data['organization_code_electronic'];
    	//纳税证明
    	$general_taxpayer=$data['general_taxpayer'];
    	//开户许可证
        $bank_licence_electronic=$data['bank_licence_electronic'];
        //经营许可证
    	$business_certificate=$data['business_certificate'];
    	//运营者授权证书
    	$proxy=$data['proxy'];

    	if(empty($business_licence_number) || empty($bank_account_number) || empty($bank_name) || empty($bank_branch) || empty($zhizhao) || empty($proxy) ){
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
    	'proxy'=>$proxy,
    	'is_three_one'=>$is_three_one
    	];
    	
    	$res = Db::name('supplier')->where(array('user_id'=>$supplier_id,'is_designer'=>0))->setField($sql);
    	if(false!==$res){
    		exit(true);
    	}else{
    		exit(false);
    	}
    }
    
    //保存第三部
    public function BusinessThree_save(){
        $supplier = $this->supplier;
        $supplier_id=$supplier['admin_id'];
        $data=I('');
    	// $admin_id=$_COOKIE['supplier_admin_id'];
    	//$supplier_id=$_SESSION["supplier"]['admin_id'];
    	$supplier_name=$data['supplier_name'];
    	$logo=$data['logo'];
    	$reading_protocol=$data['reading_protocol'];
    	$type=$data['type'];

    	if(empty($supplier_name) || empty($logo)){
                    	 exit('-1');
          }
    	if(!empty($supplier_name) && $type !='1'){
    		$row=Db::name('supplier')->where('supplier_name',"{$supplier_name}")->find();
    		if(!empty($row)){
    			echo '1';
    			exit;
    		}
    	}
    	$sql = [ 'supplier_name' =>$supplier_name,
    	'logo'=>$logo,
    	'reading_protocol'=>$reading_protocol,
    	'status'=>0,
		'is_complete'=>1
    	];
    	$res = Db::name('supplier')->where(array('user_id'=>$admin_id,'is_designer'=>0))->setField($sql);
		//同步修改商铺设置的LOGO
		$supplier = Db::name('supplier')->where(array('user_id'=>$admin_id,'is_designer'=>0))->find();
		if(Db::name('supplier_config')->where(['supplier_id'=>$supplier['supplier_id'],'name'=>'store_logo'])->value('value'))
			Db::name('supplier_config')->where(['supplier_id'=>$supplier['supplier_id'],'name'=>'store_logo'])->update(['supplier_id'=>$supplier['supplier_id'],'name'=>'store_logo','value'=>$logo]);
		else
			Db::name('supplier_config')->insert(['supplier_id'=>$supplier['supplier_id'],'name'=>'store_logo','value'=>$logo]);
		
		$supplierUser = Db::name('supplier_user')->field('parent_id,FManagerId')->where(array('admin_id'=>$admin_id))->find();
		
		//修改推荐信息
		$recommend_code = 'su'.$supplier['supplier_id'];
		Db::name('supplier')->where('supplier_id',$supplier['supplier_id'])->update(['recommend_code'=>$recommend_code,'parent_id'=>$supplierUser['parent_id'],'FManagerId'=>$supplierUser['FManagerId']]);
		
    	if($res==true){
    		echo '2';
    		exit;
    	}else{
    		exit(false);
    	}
    	
    }
    
    public function checkAjax(){
        $data=I('');
    	$company_name=trim($data['company_name']);
    	$supplier_name=trim($data['supplier_name']);
    	if(!empty($company_name)){
    		$rows = Db::name('supplier')->where("company_name","{$company_name}")->find();
    		if(!empty($rows)){
    			echo "1";
    			exit;
    		}else{
    			echo "2";
    			exit;
    		}
    	}elseif(!empty($supplier_name)){
    		$rows = Db::name('supplier')->where("supplier_name","{$supplier_name}")->find();
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
     * @function Application() 入驻申请表单
     */
    public function BusinessTwoPro(){
        $data=I('');
        $Company_name               =    htmlspecialchars( trim( $data['company_name'] ) )   ;
        self::TestingCompany_name(  self::TsetingIsNull( $Company_name )  );
        $Address                    =    htmlspecialchars( trim( $data['address'] ) );
        self::TsetingIsNull( $Address );
        $Company_type               =    htmlspecialchars( trim( $data['company_type'] ) );
        self::TsetingIsNull( $Company_type );
        $Guimo                      =    htmlspecialchars( trim( $data['guimo'] ) );
        self::TsetingIsNull( $Guimo );
        $Contacts_name              =    htmlspecialchars( trim( $data['contacts_name'] ) );
        self::TsetingIsNull( $Contacts_name );
        $Contacts_phone             =    htmlspecialchars( trim( $data['contacts_phone'] ) )   ;
        self::TsetingIsNull( $Contacts_phone ) ;
        self::Testing($Contacts_phone);
        $Email   =    htmlspecialchars( trim( $data['email'] ) )  ;
        self::TsetingIsNull($Email);
        self::TestingEmail($Email);
        
        $Business_licence_number    =    htmlspecialchars( trim( $data['business_licence_number'] )  ) ;
        self::TsetingIsNull( $Business_licence_number );
        //self::TestingCompany_name(  );
        $Three                      =    htmlspecialchars( trim( $data['business'] ) );
        $Business                   =    htmlspecialchars( trim( $data['business'] ) );
        if( self::TsetingIsNull( $Three !==1 || self::TsetingIsNull( $Business ) !==1 ) ){return false;exit;}
        $ZhizhaoName                =  trim($data['zhizhao']);//营业执照
        
        $Organization_code          =    htmlspecialchars( trim( $data['organization_code'] ) )  ;
		if($Three!=1){
			self::TsetingIsNull( $Organization_code );
			//self::TestingCompany_name( );
		}
        $Organization_code_electronicName   =trim($data['organization_code_electronic']);
        $Taxpayer  =    htmlspecialchars( trim( $data['taxpayer'] ) );
        if($Three!=1){
        	self::TsetingIsNull( $Taxpayer );
        }
        
        $General_taxpayerName  = $data['general_taxpayer'];//纳税人
        $supplier = $this->supplier;
        $admin_id=$supplier['admin_id'];
        // $admin_id=$_SESSION["supplier"]['admin_id'];
		$business_sphere = trim(I('business_sphere'));
		$bank_account_number = trim($data['bank_account_number']);
		$bank_name 		= trim($data['bank_name']);
		$bank_code 		= trim($data['bank_code']);
		$phone_number =trim($data['phone_number']);
		$qq=trim($data['qq']);
        $supplier = Db::name('supplier')->where("user_id",$admin_id)->find();
        if($supplier){
        	
        	//存在入驻资料信息
        	$sql = [ 'company_name'     =>      $Company_name,
        	'address'           =>      $Address,
        	'company_type'     =>      $Company_type,
        	'guimo'             =>      $Guimo,
        	'contacts_name'    =>      $Contacts_name,
        	'contacts_phone'   =>      $Contacts_phone,
        	'email'             =>       $Email  ,
        	'business_licence_number'  =>  $Business_licence_number,
        	'three'             =>        $Three ,
        	'business'          =>        $Business,
        	'zhizhao'           =>        $ZhizhaoName ,
        	'organization_code' =>       $Organization_code  ,
        	'organization_code_electronic' =>  $Organization_code_electronicName,
        	'taxpayer'          =>        $Taxpayer ,
        	'general_taxpayer' =>        $General_taxpayerName,
			'business_sphere'	=>		$business_sphere,
			'bank_account_number'	=>	$bank_account_number,
			'bank_name'		=>		$bank_name,
			'bank_code'		=>		$bank_code,
        	'add_time'=>time(),
        	'phone_number'=>$phone_number,
        	'qq'=>$qq,
        	];
        	
        	$res = Db::name('supplier')->where("user_id", $admin_id)->setField($sql);
        }else{
        	//存在入驻资料信息
        	$sql = [ 'company_name'     =>      $Company_name,
        	'address'           =>      $Address,
        	'company_type'     =>      $Company_type,
        	'guimo'             =>      $Guimo,
        	'contacts_name'    =>      $Contacts_name,
        	'contacts_phone'   =>      $Contacts_phone,
        	'email'             =>       $Email  ,
        	'business_licence_number'  =>  $Business_licence_number,
        	'three'             =>        $Three ,
        	'business'          =>        $Business,
        	'zhizhao'           =>        $ZhizhaoName ,
        	'organization_code' =>       $Organization_code  ,
        	'organization_code_electronic' =>  $Organization_code_electronicName,
        	'taxpayer'          =>        $Taxpayer ,
        	'general_taxpayer' =>        $General_taxpayerName,
			'business_sphere'	=>		$business_sphere,
			'bank_account_number'	=>	$bank_account_number,
			'bank_name'		=>		$bank_name,
			'bank_code'		=>		$bank_code,
        	'user_id' =>  $admin_id,
        	'add_time'=>time(),
        	];
        	$db=Db::name( 'supplier' );
        	$res =  $db->insert($sql);
        	$id=$db->getLastInsID( $res );
			$rows = Db::name('supplier_user')->where(array('admin_id'=>$admin_id))->update(array('supplier_id'=>$id));
        }
        
        if($res){
            echo json_encode(array('rs'=>7,'lastid'=>$id));
            exit;
        }
    }
    /**
     * @function BusinessThree()  商家入驻申请3
     * @return mixed
     */
    public function BusinessThree(){
        $this->assign('id',$_GET['id']);
        $supplier = session('supplier');
    	if(!empty($supplier['admin_id'])){
    		$admin_id=$supplier['admin_id'];
    		//如果登录了，看是否已填写入驻商资料
    		$row=Db::name('supplier')->where( 'user_id',$admin_id)->find();
            $row['reading_protocol'] = explode(',',$row['reading_protocol']);
    		$this->assign("row",$row);
    	}
        return $this->fetch();
    }
    public function BusinessThreePro(){
        $Id                =   $_SESSION['supplier']['admin_id'];
       $_POST['status']=0;
       $db=Db::name('supplier')->where("user_id",$Id)->setField($_POST);
       if($db){
       	 return $this->redirect( BusinessFour );
       }else{
       	 $this->error('提交信息失败');
       	 exit;
       }
    }
    /**
     * @function  TestingUrlId()
     * @param $Id
     */
    public static function TestingUrlId( $Id ){
        if( Db::name('supplier')->where( 'supplier_id' )->find() !== $Id ){
            echo 21;
        }
    }
    /**
     * @function BusinessFour() 商家入驻申请4
     * @return mixed
     */
    public function BusinessFour(){
    	$admin_id=$_SESSION['supplier']['admin_id'];
    	$supplier = Db::name('supplier')->where("user_id",$admin_id)->find();
    	if(empty($supplier['supplier_name'])){
    	   $this->redirect('BusinessOne');
    	   exit;
    	}

    	$this->assign('username',$supplier['supplier_name']);
    	$this->assign('status',$supplier['status']);
        return $this->fetch();
    }
    
    //商家登录
    public function login(){
        setcookie('user_name','',time()-3600,'/');
        setcookie('cn','',time()-3600,'/');
        setcookie('user_id','',time()-3600,'/');
        
	    $this->assign('referurl',Url::build("/"));
	    return $this->fetch();
    }

    public function ajax_login(){
        setcookie('user_name','',time()-3600,'/');
        setcookie('cn','',time()-3600,'/');
        setcookie('user_id','',time()-3600,'/');
        
    	$mobile = trim(I('post.mobile'));
    	$password = trim(I('post.password'));
    	$verify_code = I('post.verify_code');
    	$verify = new Verify();
    	if (!$verify->check($verify_code,'cus_login'))
    	{
    		$res = array('status'=>0,'msg'=>'验证码错误');
    		exit(json_encode($res));
    	}
    	$logic = new SupplierLogic();
    	$res = $logic->user_login($mobile,$password);

    	if($res['status'] == 1){
    		session('supplier',$res['result']);
    	    if($res['result']['state'] == 1){
                $condition['admin_id'] = $res['result']['admin_id'];
                $admin_info = Db::name('supplier_user')->alias('s')->join('supplier_role r', array('s.role_id=r.role_id','s.supplier_id=r.supplier_id'),'left')->where($condition)->find();
                session('admin_id',$admin_info['admin_id']);
                session('supplier_id',$admin_info['supplier_id']);
                session('act_list',$admin_info['act_list']);
                Db::name('supplier_user')->where("admin_id = ".$admin_info['admin_id'])->update(array('last_login'=>time(),'last_ip'=>  getIP()));
                $nickname = empty($res['result']['user_name']) ? $mobile : $res['result']['user_name'];
                setcookie('supplier_name',urlencode($nickname),null,'/');
				$this->adminlog($admin_info['user_name'].'后台登陆');
                setcookie('supplier_admin_id',$res['result']['admin_id'],null,'/');
              if(session('loginUrl')){
                $res['url'] =  Url::build(substr(session('loginUrl'),0,-5));
                unset($_SESSION['loginUrl']);
              }else{ 
               $res['url'] =  Url::build('Home/Business/BusinessIndex');  
              }			
                exit(json_encode($res));
            }else{
                $res['url'] =  Url::build('Home/Business/BusinessIndex');
                $rs=Db::name('supplier')->where("user_id",$res['result']['admin_id'])->find();
                setcookie('supplier_admin_id',$res['result']['admin_id'],null,'/');
                $nickname = empty($res['result']['user_name']) ? $mobile : $res['result']['user_name'];
                setcookie('supplier_name',urlencode($nickname),null,'/');
                setcookie('cn',0,time()-3600,'/');
            }
    	}
    	exit(json_encode($res));
    }
    
    //商家注册
    public function register(){
		$mobile = trim(I('post.mobile',''));
		$password = trim(I('post.password',''));
		$password2 = trim(I('post.password2',''));
		$code = trim(I('post.code',''));
        $verify_code = trim(I('post.verify_code'));
		// $parent_id = trim(I('post.parent_id',''));
		$session_id = session_id();
		
		//验证码
		$userlogic = new UsersLogic();
		$res = $userlogic->check_validate_code($code, $mobile , $session_id , 'mobile');
		if ($res['status'] != 1){
			$this->error($res['msg']);
		}
		

		$supLogin=new SupplierLogic();
		$data = $supLogin->reg($mobile,$password,$password2,'','',$parent_id);
		// dump($data);exit;
		if($data['status'] != 1){
			$this->error($data['msg']);
			exit;
		}
        $res = $supLogin->user_login($mobile,$password);
        if($res){
            $res['url'] =  urldecode(I('post.referurl'));
            session('supplier',$res['result']);
            setcookie('supplier_admin_id',$res['result']['admin_id'],null,'/');
            $nickname = empty($res['result']['user_name']) ? $mobile : $res['result']['user_name'];
            setcookie('supplier_name',urlencode($nickname),null,'/');
            setcookie('cn',0,time()-3600,'/');
        }
		$this->success($data['msg'],Url::build('Home/business/BusinessIndex'));
		exit;
    }
	
	
    
    public function issetMobile()
    {
    	$mobile = I("mobile",'0');
    	$users = Db::name('supplier_user')->where("mobile",$mobile)->find();
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
            $this->redirect('Home/Business/index');
    	}
    	if(IS_POST){
    		$logic = new UsersLogic();
    		$username = I('post.username');
    		$code = I('post.code');
    		$pass = false;
    	
    		//检查是否手机找回
    		if(check_mobile($username)){
    			if(!$user = get_supplier_user_info($username,2))
    				$this->error('账号不存在');
    			$check_code = $logic->sms_code_verify($username,$code,$this->session_id);
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
    		if($data['status'] != 1)
    			$this->error($data['msg'] ? $data['msg'] :  '操作失败');
    		$this->success($data['msg'],Url::build('Home/Business/login'));
    		exit;
    	}
        return $this->fetch();
    }
	
	
	   /**
	 * 验证用户是否存在
	 */
    public function check_username(){
    	$username = I('post.username');
    	if(!empty($username)){
    		$count = Db::name('supplier_user')->where("mobile", $username)->count();
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
            $this->redirect('Home/Business/Index');
    	}
    	$username = I('post.username');
    	$userinfo = array();
    	if($username){
    		$userinfo = Db::name('supplier_user')->where('mobile', $username)->find();
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
            $this->redirect('Home/Business/Index');
    	}
    	$check = session('validate_code');
    	$logic = new UsersLogic();
    	if(empty($check)){
            $this->redirect('Home/Business/authentication');
    	}elseif($check['is_check']==0){
    		$this->error('验证码还未验证通过',Url::build('Home/Business/authentication'));
    	}    	
    	if(IS_POST){
    		$password = I('post.password');
    		$password2 = I('post.password2');
    		if($password2 != $password){
    			$this->error('两次密码不一致',Url::build('Home/Business/authentication'));
    		}
    		if($check['is_check']==1){
    			//$user = get_user_info($check['sender'],1);
                $user = Db::name('supplier_user')->where("mobile|email", '=', $check['sender'])->find();
    			Db::name('supplier_user')->where("admin_id", $user['admin_id'])->update(array('password'=>encrypt($password)));
    			session('validate_code',null);
                $this->redirect('Home/Business/finished');
    		}else{
    			$this->error('验证码还未验证通过',Url::build('Home/Business/authentication'));
    		}
    	}
    	return $this->fetch();
    }
	
	
	/*
	 * 忘记密码流程 结束
	 */
	 public function finished(){
    	if($this->user_id > 0){
            $this->redirect('Home/Business/Index');
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
    $add['admin_id'] = session('admin_id');
    $add['log_info'] = $log_info;
    $add['log_ip'] = getIP();
    $add['log_url'] = request()->baseUrl();
	$add['supplier_id'] =  session('supplier_id');
    Db::name('supplier_admin_log')->insert($add);
	
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
		session('supplier',null);
        session_unset();
        session_destroy();
		$this->redirect('Home/Business/login');
      	exit;
	}
	
	
}


