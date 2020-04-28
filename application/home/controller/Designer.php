<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/10
 * Time: 16:10
 */
namespace ylt\home\controller;
use ylt\home\model\UsersLogic;
use ylt\home\logic\OrderLogic;
use think\Controller;
use think\Url;
use think\Config;
use think\Page;
use think\Db;
use think\Validate;
header("Content-type: text/html; charset=utf-8");
class Designer extends Base {

    public $user_id = 0;
    public $user = array();
    /*
     * 处理登录后需要的参数
     */
    public function _initialize() {
        parent::_initialize();
        if(session('?user'))
        {
            $user = session('user');
            $user = Db::name('users')->where("user_id", $user['user_id'])->Cache(true,600)->find();
            //$user = Db::name('users')->where("user_id", $user['user_id'])->find();
            session('user',$user);  //覆盖session 中的 user
            $this->user = $user;
            $this->user_id = $user['user_id'];
            $this->assign('user',$user); //存储用户信息
            $this->assign('user_id',$this->user_id);
				//不是正式设计师
			if($user['is_designer'] != 1){
				
				$user = Db::name('users')->where("user_id", $user['user_id'])->find();
				session('user',$user);  //覆盖session 中的 user
			
				 $noDesigner = array(
                'Index','designerAuth_1','designerAuth_2','designerAuth_3','designerAuth_4','add_designerAuth_1',
                'add_designerAuth_2','add_designerAuth_3','upload','get_file_ext','set_dir','Back_order'
				);
			
				if(!in_array(ACTION_NAME,$noDesigner)){
					$this->redirect('Home/User/user_login');
					exit;
				}
				
			}
        }else{
			$this->redirect('Home/User/user_login');
			exit;
        }
		if($user['is_designer'] == 1){
      //$supplier = Db::name('supplier')->field('supplier_id,supplier_name,company_name,company_type')->where(['user_id'=>$user['user_id'],'is_designer'=>1])->Cache(true,7200)->find();
			$supplier = Db::name('supplier')->field('supplier_id,supplier_name,company_name,province,city,company_type')->where(['user_id'=>$user['user_id'],'is_designer'=>1])->Cache(true,7200)->find();
			$supplier['collect'] = Db::name('supplier_collect')->where('supplier_id',$supplier['supplier_id'])->Cache(7200)->count();

      $region_list = get_region_list();
      $this->assign('region_list',$region_list);

			$this->assign('supplier',$supplier);
			$this->supplier_id = $supplier['supplier_id'];
		}

  }

     //设计师入口是否审核通过
    public function Index(){

        $user = session('user');
        
        // '0.未申请，1通过，2不通过，3审核中,-1关闭',
        if($user['is_designer'] == 1){
         
           return $this->Account();
   
        }
        if($user['is_designer'] == 0){  

           return $this->designerAuth_1();          
        }else{
           return $this->designerAuth_4();
       
        }
        
    }


    /**
     * @function designerAuth_1() //设计师入驻  流程 ---1
     * @return mixed
     */
    public function designerAuth_1(){
		 
		
  		$user =  $this->user;
  		 if(!$user['mobile'])
  			 $this->error('请先绑定手机','User/mobile_validate');
  		 if($user['is_designer']==1)
  			 return $this->Index();
			 

        $data = Db::name('Supplier')->where('user_id', $this->user_id)->where('is_designer',1)->find();

        $parent = Db::name('designer_category')->where('parent_id',0)->select();
		
		
        
    
        if(empty($data)){
           $data['user_id'] = $user;
        }

        $this->assign('data',$data);
        $this->assign('parent',$parent);

        return $this->fetch('designerAuth_1');

    }

    public function add_designerAuth_1(){
  
        $data = I('post.');
		    $user = session('user');

        if(!empty($data['hcity']) && !is_numeric($data['hcity'])){
            $rs=Db::name('region')->where( 'name',"{$data['hcity']}")->find();
            $data['province']=$rs['id'];
        }
        if(!empty($data['hproper']) && !is_numeric($data['hproper'])){
            $rs=Db::name('region')->where( 'name',"{$data['hproper']}")->find();
            $data['city']=$rs['id'];
        }
        if(!empty($data['harea']) && !is_numeric($data['harea'])){
            $rs=Db::name('region')->where( 'name',"{$data['harea']}")->find();
            $data['area']=$rs['id'];
        }
		
    		$rule =[
    			'contacts_name'  => 'require',
    			'business_licence_number' => 'length:15,18',
    			'address'	=> 'require',
    			'company_type'	=> 'require',
    			'province'	=> 'require'
    		];
    		
    		$rules = [
    			'contacts_name'  => $data['contacts_name'],
    			'business_licence_number' => $data['business_licence_number'],
    			'address'	=> $data['address'],
    			'company_type'	=> $data['company_type'],
    			'province' 		=> $data['province'],
    		];
    		$validate = new Validate($rule);
    		if (!$validate->check($rules)) {
    			$arr=array('status'=>"-1",'msg'=>$validate->getError());
                exit(json_encode($arr));
    		}

        if(empty($data['guimo']) || empty($data['qq'])){
             $rs=array('status'=>'-1','msg'=>'必填项不能为空!');
              exit(json_encode($rs));
        }
       
        
         if(Db::name('supplier')->where(['user_id'=>$user['user_id'],'is_designer'=>1])->value('user_id')){
            $rs= Db::name('Supplier')->where('user_id',$user['user_id'])->where('is_designer',1)->update($data);
         }else{
  			$data['user_id'] = $user['user_id'];
  			$data['contacts_phone'] = $user['mobile'];
  			$data['add_time'] = time();
            $data['is_designer'] = 1;
			$data['logo'] = $user['head_pic'];
            $rs= Db::name('Supplier')->insert($data);
         }
            $rs=array('status'=>'1','msg'=>'编辑成功!');
            exit(json_encode($rs));
                 
    }


     /**
      * @function designerAuth_2() //设计师入驻  流程 ---2
      * @return mixed
      */
      public function designerAuth_2(){
  		  $user =  $this->user;
  		  if($user['is_designer']==1)
  			 return $this->Index();

          $data = Db::name('Supplier')->where('user_id',$this->user_id)->where('is_designer',1)->find();
          
          $this->assign('data',$data);
          return $this->fetch();

      }

      public function add_designerAuth_2(){

        $user_id=$_POST['user_id'];
        //身份证正面
        $organization_code_electronic=$_POST['organization_code_electronic'];
        //身份证反面
        $general_taxpayer=$_POST['general_taxpayer'];
        //相关证明材料1
        $bank_licence_electronic=$_POST['bank_licence_electronic'];
        //相关证明材料2
        $proxy=$_POST['proxy'];
        //学历证明
        $reading_protocol=$_POST['reading_protocol'];

        if(empty($organization_code_electronic) || empty($general_taxpayer) || empty($bank_licence_electronic) || empty($proxy)){
             $rs=array('status'=>'-1','msg'=>'必填项不能为空!');
              exit(json_encode($rs));
          }

        $sql = [ 'user_id' =>$user_id,
        'organization_code_electronic'=>$organization_code_electronic,
        'general_taxpayer'=>$general_taxpayer,
        'bank_licence_electronic'=>$bank_licence_electronic,
        'proxy'=>$proxy,
        'reading_protocol'=>$reading_protocol
        ];
                   
        $rs= Db::name('Supplier')->where(array('user_id'=>$user_id,'is_designer'=>1))->setField($sql);
        $rs=array('status'=>'1','msg'=>'编辑成功!');
        exit(json_encode($rs));
      
        
      }
      
      /**
       * @function designerAuth_3() //设计师入驻  流程 ---3
       * @return mixed
       */
      public function designerAuth_3(){
  		  $user =  $this->user;
  		  if($user['is_designer']==1)
  			 return $this->Index();

          $data = Db::name('Supplier')->where('user_id',$this->user_id)->where('is_designer',1)->find();
          
          $user_id = $data['user_id'];
          $introduction = $data['introduction'];
          $this->assign('user_id',$data['user_id']);
          $this->assign('introduction',$data['introduction']);

          return $this->fetch();

       }

      public function add_designerAuth_3(){

          $introduction = I('post.introduction');
          $user_id = I('post.user_id');

          //表是utf8结构
          if(isset($introduction{300})){
              $rs=array('status'=>'-1','msg'=>'个人简介不能超过100字!');
              exit(json_encode($rs));
          }

          if(!empty($introduction)){
              $sql = ['introduction' => $introduction,
                      'user_id' => $user_id,
                      'is_complete' => 1,
					  'status' => 0
                    ];
              $rs= Db::name('Supplier')->where('user_id',$user_id)->where('is_designer',1)->setField($sql);

              $rss= Db::name('Users')->where('user_id',$user_id)->update(['is_designer'=>3]);
              

              $rs=array('status'=>'1','msg'=>'编辑成功!');
              exit(json_encode($rs));
          }else{
              $rs=array('status'=>'-1','msg'=>'个人简介不能为空!');
              exit(json_encode($rs));
          }   
        
      }


      /**
      * @function designerAuth_4() //设计师入驻  流程 ---4
      * @return mixed
      */
      public function designerAuth_4(){

        $user = Db::name('Users')->where('user_id',$this->user_id)->find();
           //$user['status'] 是判定users表中判定是否审核通过
            $user['status'] = $user['is_designer'];
            $user['user_id'] = $user['user_id'];

            if(empty($user['user_id'])){
               $this->redirect('designerAuth_1');
               exit;
            }
            
            //$user['status'],'0.未申请, 1.通过, 2.不通过, 3.审核中, -1.关闭',
            if($user['status'] == 0){
               $this->redirect('designerAuth_1');
               exit;
            }

          $this->assign('user_id',$user['user_id']);
          $this->assign('status',$user['status']);
          return $this->fetch('designerAuth_4');
      }



    /**
     * @function designsList() //设计师--后台首页
     * @return mixed
    */
    public function WorksList(){
        $p = I('p/d',1);
        $user =  $this->user;
        $examine = I('get.examine');
        $cat_id = I('get.cat_id');

        $where['user_id'] = $user['user_id'];
        //$where 判断语句相当与if,有值传递就显示
        $examine && $where['examine'] = $examine;
        $cat_id && $where['cat_id'] = $cat_id;
        //$cat_id 不同分类
        if(!empty($cat_id)){
           //designs_list_id =>works表中的所有
          $designs_list_id = Db::name('works')->where(array('cat_id'=>$cat_id,'user_id'=>$user['user_id']))->page($p.',9')->select();
          //$count_id显示数量
          $count_id = Db::name('works')->where(array('cat_id'=>$cat_id,'user_id'=>$user['user_id']))->count();

        }

        //$examine 是否状态
        if($examine==0&&$examine==""){ 
            $designs_list = Db::name('works')->where('user_id',$user['user_id'])->page($p.',9')->select();
         
            $count = Db::name('works')->where('user_id',$user['user_id'])->count();
        }else{
           if($examine==1){   
              $examine = $examine;
           }elseif($examine==0&&$examine!=""){
            
              $examine = $examine;
           }elseif($examine==2){
            
              $examine = $examine;
           }

            $designs_list = Db::name('works')->where(array('examine'=>$examine,'user_id'=>$user['user_id']))->page($p.',9')->select();
               
            $count = Db::name('works')->where(array('examine'=>$examine,'user_id'=>$user['user_id']))->count();

        }
        //判定入口是在分类还是状态
        if(!empty($cat_id)){
           $designs_list = $designs_list_id;
           $count = $count_id;
        }else{
           $designs_list = $designs_list;
           $count = $count;
        }
        
        //作品分类ID数组,分组之后去除重复值    
        $cat_list = Db::name('works')->where('user_id',$user['user_id'])->limit(10)->group('cat_id')->select();    
  
        $Page = new Page($count,9);
        $show = $Page->show();

        $this->assign('count',$count);
        $this->assign('pager',$Page);
        $this->assign('page',$show); 
        $this->assign('cat_list',$cat_list); 
        $this->assign('designs_list',$designs_list);
		$this->assign('active','WorksList');
        return $this->fetch();

    }



    /**
    * @function designsPreview() //设计师--作品详情预览--暂时不做
    * @return mixed
    */
    public function DesignsPreview(){
      
        return $this->fetch('DesignsPreview');

    }

    /**
    * @function designsUpload() //设计师--上传作品
    * @return mixed
    */
    public function DesignsUpload(){
		
        $user_id = $this->user_id;
        $id = I('works_id/d,0');
         
        $works_cat = Db::name('works_category')->where('1=1')->select();
        if($id){

          $info = Db::name('works')->where(array('works_id'=>$id,'user_id'=>$user_id))->find();
            if(empty($info)){
              $rs=array('status'=>'-1','msg'=>'非法操作!');
              exit(json_encode($rs));
            }
        }else{
           $info['user_id'] = $user_id;
        }
   
        $this->assign('works_cat',$works_cat);
        $this->assign('info',$info);
        return $this->fetch();
        
        
    }

    public function AddDesignsUpload(){

        $data = I('post.');
       
        if(empty($data['editorValue'])){
            $rs=array('status'=>'-1','msg'=>'作品详情不能为空!');
            exit(json_encode($rs));
        }
        
        //表是utf8结构
        if(isset($data['works_name']{120})){
            $rs=array('status'=>'-1','msg'=>'作品名称不能超过40字!');
            exit(json_encode($rs));
        }

        if(empty($data['user_id'])){
            $rs=array('status'=>'-1','msg'=>'非法登陆!');
            exit(json_encode($rs));
        }

        $works_category = Db::name('works_category')->where('id',$data['cat_id'])->find();

        $sql = ['user_id' => $data['user_id'],
                'works_id' => $data['works_id'],
                'works_name' => $data['works_name'],
                'cat_id' => $data['cat_id'],
                'cat_name' => $works_category['name'],
                'works_content' => $data['editorValue'],
                'works_img' => $data['works_img'],
				'examine'	=> '0'
             ];

        if(empty($sql['works_id'])){
            //取设计师的昵称
            $supplier = Db::name('supplier')->where(array('is_designer'=>1,'user_id'=>$data['user_id']))->find();
            $sql['designer_name'] = $supplier['supplier_name'];
            $sql['supplier_id'] = $supplier['supplier_id'];
            $sql['add_time'] = time();
            //是否显示,目前没有用到
            $sql['is_show'] = 1;

            $rss = Db::name('Works')->insert($sql);

            $rs=array('status'=>'1','msg'=>'发布成功!');
            exit(json_encode($rs));
        }else{
            $sql['last_update'] = time();

            $rss= Db::name('Works')->where('works_id',$sql['works_id'])->setField($sql);

            $rs=array('status'=>'1','msg'=>'编辑成功!');
            exit(json_encode($rs));
        } 

    }

    /**
     * 删除上传作品
     */
    public function DelDesignsList(){
        $works_id = I('works_id/d,0');
        $user_id = $this->user_id;

        if(empty($works_id) || empty($user_id)){
          
            $rs=array('status'=>'-1','msg'=>'操作失败!');
            exit(json_encode($rs));
        }

        $where['user_id'] = $user_id;

        $works_id && $where['works_id'] = $works_id;
        
        $rs=DB::name('Works')->where($where)->delete();

        if($rs){

            $rs=array('status'=>'1','msg'=>'删除成功!');
            exit(json_encode($rs));
          }else{

            $rs=array('status'=>'-1','msg'=>'操作失败!');
            exit(json_encode($rs));
        }
         
    }



    /**
    * @function worksList() //设计师--在售商品
    * @return mixed
    */
    public function DesignsList(){
		

      $user =  $this->user;
      $examine = I('get.ex'); 	//审核状态
      $cat_id = I('get.cat');	//分类
	    $sale = I('get.sale');		//上架

		  $supplier_id = $this->supplier_id;
		  
		  //$where 判断语句相当与if,有值传递就显示
      $where['supplier_id'] = $supplier_id;
      $examine != '' && $where['examine'] = $examine;
	    $sale != '' && $where['is_on_sale'] = $sale;
      $cat_id && $where['cat_id'] = $cat_id;
		
  		$count = Db::name('goods')->where($where)->count();
  	  
  		$page = new Page($count,12);

  		$field = "goods_id,goods_name,shop_price,goods_thumb,cat_id,sales_sum,original_img,is_on_sale";
  		
  		$goods_list = Db::name('goods')->field($field)->where($where)->limit($page->firstRow.','.$page->listRows)->select();
  		
  		//获取有商品的分类
  		$cat_id = Db::name('goods')->where('supplier_id',$supplier_id)->group('cat_id')->column("cat_id");

      $cat_list = Db::name('goods_category')->where("id","in", implode(',', $cat_id))->limit(10)->select();
      //获取商品的分类名称
  		$category_list = Db::name('goods_category')->where("id","in", implode(',', $cat_id))->limit(10)->column('id,name'); 
  		
  	  $this->assign('count',$count);
      $this->assign('cat_list',$cat_list); 
      $this->assign('category_list',$category_list); //分类显示输出
      $this->assign('goods_list',$goods_list);
  	  $this->assign('page',$page);// 赋值分页输出
	    $this->assign('active','DesignsList');
      return $this->fetch();
    }

    /**
    * @function worksList() //设计师--在售商品上下架
    * @return mixed
    */
    public function SaleDesignsList(){
      $goods_id = I('goods_id/d,0');
      $is_on_sale = I('get.is_on_sale');

      $user_id = $this->user_id;
  
      if(empty($goods_id) || empty($user_id)){
        
          $rs=array('status'=>'-1','msg'=>'操作失败!');
          exit(json_encode($rs));
      }
      
      if($is_on_sale == 1){
         $is_on_sale = 0;
      }else{
         $is_on_sale = 1;
      }

      $sql = ['goods_id' => $goods_id,
              'is_on_sale' => $is_on_sale
             ];

      if(!empty($goods_id)){

          $rs=DB::name('Goods')->where('goods_id',$goods_id)->setField($sql);
          if($sql['is_on_sale'] == 0){
            $rs=array('status'=>'1','msg'=>'下架成功!');
            exit(json_encode($rs));
          }
            $rs=array('status'=>'1','msg'=>'上架成功!');
            exit(json_encode($rs));
        }else{

          $rs=array('status'=>'-1','msg'=>'操作失败!');
          exit(json_encode($rs));
   
      }


    }


    /**
    * @function worksUpload() //设计师--上传商品
    * @return mixed
    */
    public function WorksUpload(){
		
		  $goods_id = I('get.id');
		
		  $supplier_id = $this->supplier_id;
		
    	
    	$cat_list = Db::name('goods_category')->field('id,name')->where('parent_id = 11')->select();
		
  		if($goods_id){
  			$info = Db::name('goods')->where(['goods_id'=>$goods_id,'is_designer'=>1,'supplier_id'=>$supplier_id])->find();
  			if(!$info)
  				$this->error('非法操作',Url::build('Designer/index'));
  			
  			$img_list = Db::name('goods_images')->where('goods_id',$goods_id)->select();
  		}
	
		  $this->assign('info',$info);
		  $this->assign('img_list',$img_list);
    	$this->assign('cat_list',$cat_list);
        return $this->fetch();

    }
    
    public function AddEditWorks(){
		
		  $goods_id = trim(I('goods_id'));
		
		  $type = I('goods_id') > 0 ? 2 : 1; // 标识自动验证时的 场景 1 表示插入 2 表示更新
		 
    	$designer = Db::name('supplier')->field('supplier_id,supplier_name') ->  where('user_id', $_SESSION['user']['user_id']) ->  where('is_designer', 1) ->find();
		
		  if(!$designer)
			  $this->error('非法操作',Url::build('Designer/index'));
		
		
      	$data = [
      			 'cat_id' 	      => $_POST['cat_id'],
      			 'goods_name'     => $_POST['worksName'],
      			 'shop_price' 	  => $_POST['worksPrice'],
      			 'cost_price' 	  => $_POST['worksCost'],
      			 'shipping_price' => 0,
      			 'store_count'    => $_POST['worksStock'],
      			 'keywords' 	  => $_POST['worksKeyWords'],
      			 'last_update'    => time(),
      			 'is_designer' 	  => 1,
      			 'original_img'   => $_POST['worksBookImg1'],
      			 'goods_thumb'	  => $_POST['worksBookThumb1'],
  				 'goods_content'  => $_POST['goods_content']
      	];

      	if($type == 1){
  			$data['add_time'] = time();
  			$data['supplier_id'] = $designer['supplier_id'];
        
  			$data['supplier_name'] = $designer['supplier_name'];

  			$goods_id = Db::name('goods')->add($data);
  		}else{
  			Db::name('goods_images')->where('goods_id',$goods_id)->delete();
        //不能取名$goods_id会重复,值自动为1,不能对应相应的缩略图
        //$goods_id = Db::name('goods')->where('goods_id',$goods_id)->update($data);
  			$goods_ids = Db::name('goods')->where('goods_id',$goods_id)->update($data);
  		}
      	
  	    for ($x=2; $x<=4; $x++) {
  	    	$imgCode = 'worksBookImg'.$x;
  			$thumb = 'worksBookThumb2'.$x;
  	    	
  	    	if ($_POST[$imgCode]){
  	    		$imgData = [
      			 'goods_id' => $goods_id,
      			 'image_url' => $_POST[$imgCode]
      			];
  	    		Db::name('goods_images')->insert($imgData);	
  				// 删除缩略图
  				 if(file_exists($thumb)){
  						unlink($thumb);
  					}	
  	    	}
  		}
  		
		
            exit(json_encode(['status'=>'1','msg'=>'操作成功!']));
		
	
    }



   public function upload(){
      $f = $_FILES['file'];
      $dir=$this->set_dir("public/upload/designer","{y}/{m}/{d}");
      $ext=$this->get_file_ext($f['name']);
       
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




   /**
   * @function account() //设计师--我的账户
   * @return mixed
   */
   public function Account(){
	   
	   $account_list = Db::name('withdrawals_log')->where('user_id',$this->user_id)->limit(3)->order('id desc')->select();
	   
	   $user_money = Db::name('users')->where('user_id',$this->user_id)->value('user_money');
	   
	   $this->assign('user_money',$user_money);
	   $this->assign('account_list',$account_list);
	   $this->assign('active','Account');
       return $this->fetch('Account');

   }




   /**
   * @function trade() //设计师--我的交易
   * @return mixed
   */
   public function Trade(){
	   
	  $begin = strtotime(input('begin'));
      $end = strtotime(input('end'));
	  
	  $supplier_id =  $this->supplier_id;
	  
	  $where = " supplier_id = $supplier_id and is_designer = 1 ";
 
	  input('cons') != '' ? $where .= " and (consignee LIKE '%".input('cons')."%') ": false;
	  input('sn') != '' ? $where .= " and (order_sn LIKE '%".input('sn')."%') ": false;
	  input('type') != '' ? $where .= config(strtoupper(input('type'))) : false;
	  
	 
	  if($begin && $end){
            $where .= " and add_time > $begin and add_time < $end ";
        }
	  
	  $count = Db::name('order')->where($where)->count();
	  
	  $page = new Page($count,15);

	  $order_list = Db::name('order')->where($where)->order("add_time desc")->limit($page->firstRow.','.$page->listRows)->select();

	  // 订单数据统计
	  $count_where = "supplier_id = $supplier_id and is_designer = 1 ";
	  $total = Db::name('order')->where($count_where) ->Cache(true,600)->count();
	  $Number = ['count'=>$count,
			'total'=> $total,
			'WAITPAY'=> $total ? Db::name('order')->where($count_where .config(strtoupper('WAITPAY')))->Cache(true,600)->count() : 0,
			'WAITSEND'=> $total ? Db::name('order')->where($count_where .config(strtoupper('WAITSEND')))->Cache(true,600)->count() : 0,
			'WAITRECEIVE'=> $total ? Db::name('order')->where($count_where .config(strtoupper('WAITRECEIVE')))->Cache(true,600)->count() : 0,
			'FINISH'=> $total ? Db::name('order')->where($count_where .config(strtoupper('FINISH')))->Cache(true,600)->count() : 0,
	  ];
	  //订单状态
	  $order_status = config('ORDER_STATUS');
		
	  $this->assign('order_list',$order_list);
	  $this->assign('order_status',$order_status); 
	  $this->assign('page',$page);// 赋值分页输出
	  $this->assign('Number',$Number);
	  $this->assign('active','Trade');
      return $this->fetch();

   }



   /**
   * @function OrderInfos() //设计师--订单信息
   * @return mixed
   */
   public function OrderInfos(){
	    $order_id = I('id/d,0');
	    $order_id = Db::name('order')->where(['order_id'=>$order_id,'supplier_id'=>$this->supplier_id])->value('order_id');
	    if(!$order_id)
            $this->error('非法操作',Url::build('Designer/DesignsList'));
		
		if(IS_POST){
			$note = I('note');
			Db::name('order')->where(['order_id'=>$order_id,'supplier_id'=>$this->supplier_id])->update(['admin_note'=>$note]);
			
		}

	    $orderLogic = new OrderLogic();
        $order = $orderLogic->getOrderInfo($order_id);
        $orderGoods = Db::name('order_goods')->where('order_id',$order_id)->select();
        // 获取操作记录
        $action_log = Db::name('order_action')->where(array('order_id'=>$order_id))->order('log_time desc')->select();

		$shipping = Db::name('shipping_order')->where('order_id',$order_id)->select();

        $split = count($orderGoods) >1 ? 1 : 0;
        foreach ($orderGoods as $val){
            if($val['goods_num']>1){
                $split = 1;
            }
        }
		if($shipping){
			foreach ($shipping as $key => $value){
				$shipping[$key]['exp'] = json_decode($value['logistics_information'],true); 
			}		
		}
		 //订单状态
		$order_status = config('ORDER_STATUS');
		// 物流列表
		$shipping_list = Db::name('plugin')->where('type','shipping')->cache(true)->select();
		

        $this->assign('order',$order);
        $this->assign('action_log',$action_log);
        $this->assign('orderGoods',$orderGoods);
	    $this->assign('order_status',$order_status);
		$this->assign('shipping',$shipping_list);
        $this->assign('split',$split);
		$this->assign('shipping',$shipping);
        return $this->fetch();
		

   }
   
   
    /**
     * 生成发货单
     */
    public function deliveryHandle(){
		
		 $data = I('post.');
		 $data['user_id'] = $this->user_id;
		 $data['note'] = '';
		   $order_id = Db::name('order')->where(['order_id'=>$data['order_id'],'supplier_id'=>$this->supplier_id])->value('order_id');
	    if(!$order_id)
            $this->error('非法操作',Url::build('Designer/DesignsList'));

        $orderLogic = new OrderLogic();
       
        $res = $orderLogic->deliveryHandle($data);
		
        if($res){
			exit(json_encode(['status'=>'1','msg'=>'操作成功!']));
        }else{
           exit(json_encode(['status'=>'-1','msg'=>'操作失败!']));
        }
    }


  /**
   * @function back() //设计师--订单退款 
   * @return mixed
   */
   public function Back_order(){
	   
	   // 搜索条件        
        $order_sn =  trim(I('order_sn'));
        $order_by = I('order_by') ? I('order_by') : 'id';
        $sort_order = I('sort_order') ? I('sort_order') : 'desc';
        $status =  I('status');
        $where = " supplier_id = $this->supplier_id ";
        $order_sn && $where.= " and order_sn like '%$order_sn%' ";
        $status != '' ? $where.= " and status = '$status' " : ''; 

	   
	   $count = Db::name('back_order')->where($where)->count();
	  
	   
	   $page = new Page($count,15);

	   $back_list = Db::name('back_order')->where($where)->order("addtime desc")->limit($page->firstRow.','.$page->listRows)->select();
	   
	    $goods_id_arr = get_arr_column($back_list, 'goods_id');
        if(!empty($goods_id_arr)){
            $goods_list = Db::name('goods')->where("goods_id in (".implode(',', $goods_id_arr).")")->column('goods_id,goods_name');
        }

	   $this->assign('goods_list',$goods_list);
	   $this->assign('back_list',$back_list);
	   $this->assign('page',$page);// 赋值分页输出
       return $this->fetch();

   }

   /**
   * @function return_info() //设计师--订单换货
   * @return mixed
   */
   public function return_info(){
	   
	   $id = I('id/d,0');
	   $back_order = Db::name('back_order')->where(['id'=>$id,'supplier_id'=>$this->supplier_id])->find();
	    if(!$back_order)
            $this->error('非法操作',Url::build('Designer/Back_order'));
		
	   if($back_order['imgs'])
          $back_order['imgs'] = explode(',', $back_order['imgs']);
	  
	  if(IS_POST){
		  $note = I('note');
		  $status = I('status');
		  
		  if($status == '0'){ //回复信息
			  Db::name('back_msg')->insert(['rec_id'=>$id,'add_time'=>time(),'content'=>$note,'supplier_id'=> $this->supplier_id]);
			   exit(json_encode(array('status' => '1','msg' => '回复成功','url'=>Url::build('Designer/return_info',array('id'=>$id)))));
		  }
		  //退换货操作
		  Db::name('back_order')->where(['id'=>$id,'supplier_id'=>$this->supplier_id])->update(['remark'=>$note,'status'=>$status]);
		  exit(json_encode(array('status' => '1','msg' => '操作成功','url'=>Url::build('Designer/return_info',array('id'=>$id)))));
	  }
	   
	   $orderLogic = new OrderLogic();
       $order = $orderLogic->getOrderInfo($back_order['order_id']);
	   
	   $good = Db::name('order_goods')->where(['order_id'=>$back_order['order_id'],'goods_id'=>$back_order['goods_id']])->find();
	   $msg = Db::name('back_msg')->where("rec_id",$id)->select();
	   
	   $this->assign('good',$good);
	   $this->assign('msg',$msg);
	   $this->assign('back_order',$back_order);
	   $this->assign('order',$order);
       return $this->fetch();

   }
   
    /**
   * @function get_refund() //设计师--退款
   * @return mixed
   */
   public function get_refund(){
	   
	   $id = I('id/d,0');
	   $back_order = Db::name('back_order')->where(['id'=>$id,'supplier_id'=>$this->supplier_id])->find();
	    if(!$back_order)
            $this->error('非法操作',Url::build('Designer/Back_order'));

	   $orderLogic = new OrderLogic();
	  
	   $order = $orderLogic->getOrderInfo($back_order['order_id']);
	   
	   $Success = 0;
	    if($order['pay_code'] == 'alipay' || $order['pay_code'] == 'alipayMobile'){ //退款成功
				
			$status = $orderLogic->refund_for_alipay($order['order_sn'],$back_order['total_amount'],1,$order['mobile']); 
			if($status == true){
				$Success = 1;
				
				$this->adminLog('支付宝退款 订单号'.$order['order_sn'].'');
				
				}
		}

		if($order['pay_code'] == 'weixin' || $order['pay_code'] == 'weixinJSAPI'){
				$status = $orderLogic->refund_for_weixin($back_order['id'],$order['order_sn'],$back_order['shop_price'],$back_order['total_amount'],$order['pay_code']);
				if($status['out_trade_no'] == $order['order_sn']){ //退款成功
					$Success = 1;
					   
					$this->adminLog('微信退款 订单号'.$order['order_sn'].'');
					   
				}   
		 }
		 if($Success == 1){
			 		
			 $where = " order_id = ".$back_order['order_id']." and goods_id=".$back_order['goods_id'];
			 Db::name('order_goods')->where($where)->update(array('is_send'=>3));//更改商品状态
			 Db::name('back_order')->where("id= $id")->update(['status'=>6,'refund_time'=>time(),'is_refund'=>1]);
			 $frozen_money = $back_order['shop_price']; // 结算金额修改	   
			 Db::name('account_log')->where('order_id',$back_order['order_id'])->update(['frozen_money' => ['exp','frozen_money-'.$frozen_money.'']]);
			 Db::name('users')->where('user_id',$back_order['user_id'])->update(['frozen_money' => ['exp','frozen_money-'.$frozen_money.'']]);
			 
			 exit(json_encode(array('status' => 1,'msg' => '退款成功','url'=>Url::build('Designer/return_info',array('id'=>$id)))));
		 }

          exit(json_encode(['status'=>'-1','msg'=>'退款失败!']));

   }


   /**
   * @function Income() //设计师--我的收入
   * @return mixed
   */
   public function Income(){
	   
	  $where = "user_id = $this->user_id";
	  
	  $count = Db::name('account_log')->where($where)->count();
	  
	  $page = new Page($count,15);

	  $account_log = Db::name('account_log')->where($where)->order("log_id desc")->limit($page->firstRow.','.$page->listRows)->select();
		
	  $this->assign('account_log',$account_log);
	  $this->assign('page',$page);// 赋值分页输出
	  
     return $this->fetch();

   }


   /**
   * @function Pay() //设计师--我的支出
   * @return mixed
   */
   public function Pay(){
      return $this->fetch();

   }



   /**
   * @function DrawCash() //设计师--我的支出
   * @return mixed
   * @param string $account  账号
   */
   public function DrawCash(){
	   
	   $info = Db::name('users')->where('user_id',$this->user_id)->find();
	   
	   
	   if(IS_POST){
		  $add = array();
		  $add['account'] = trim(I('account')); 
		  $add['real_name'] = trim(I('real_name'));
		  $add['money_num'] = trim(I('num'));
		  $code = trim(I('code'));
		  
		  if($add['money_num'] > $info['user_money'])
			  exit(json_encode(['status'=>'-1','msg'=>'提现金额不能大于余额']));
		  if($add['money_num'] < 10 && $add['money_num'] != intval($add['money_num']))
			  exit(json_encode(['status'=>'-1','msg'=>'一次提现不能低于￥10']));
		  if(!$code)
			  exit(json_encode(['status'=>'-1','msg'=>'验证码不能为空']));
		
		  $logic = new UsersLogic();
		  $res = $logic->check_validate_code($code, $info['mobile']  , 'mobile');
			
		  if ($res['status'] != 1){
			exit(json_encode(['status'=>'-1','msg'=>$res['msg']]));
		  }
		  
		  $add['add_time'] = time();
		  $add['user_id'] = $info['user_id'];
		  
		  if(Db::name('users')->where('user_id',$info['user_id'])->setDec('user_money', $add['money_num'])){
			 Db::name('withdrawals_log')->insert($add);

			
			 exit(json_encode(['status'=>'1','msg'=>'操作成功!']));
		  }
		  
		  	 exit(json_encode(['status'=>'-1','msg'=>'操作失败!']));  
		 
	   }
	   
	   $this->assign('info',$info);
	   
      return $this->fetch();

   }




   /**
   * @function CashList() //设计师--我的支出
   * @return mixed
   */
   public function CashList(){
	   
  	  $where = "user_id = $this->user_id";
  	  
  	  $count = Db::name('withdrawals_log')->where($where)->count();
  	  
  	  $page = new Page($count,10);

  	  $account_list = Db::name('withdrawals_log')->where($where)->order("id desc")->limit($page->firstRow.','.$page->listRows)->select();
  		
  	  $this->assign('account_list',$account_list);
  	  $this->assign('page',$page);// 赋值分页输出
	  
	   
	   
	   
      return $this->fetch();

   }



    /**
    * @function personalInfos() //设计师后台--个人资料
    * @return mixed
    */
    public function personalInfos(){
      $supplier_id = I('id/d,0');
     
      $supplierList = Db::name('supplier')->where(array('supplier_id'=>$supplier_id,'is_designer'=>1))->find();
      
      $userList = Db::name('users')->where(array('user_id'=>$supplierList['user_id'],'is_designer'=>1))->find();

      $region_list = get_region_list();

      $this->assign('region_list',$region_list);

      $this->assign('su',$supplierList);
      $this->assign('user',$userList);
      return $this->fetch();

    }
	  
	  private function adminLog(){
		  
		  $add['log_time'] = time();
			$add['admin_id'] = $this->user_id;
			$add['log_info'] = $log_info;
			$add['log_ip'] = getIP();
			$add['log_url'] = request()->baseUrl() ;
			$add['supplier_id'] = $this->supplier_id;
			
			Db::name('designer_log')->insert($add);
		  
		  
	  }
	  
	  
	  /**
	   * 设计师提到余额
	   */
	  public function extract_frozen_money(){
		   
		$log_id = I('log_id');
		$user_id = $this->user_id;
		if(!empty($user_id)&&$log_id){
			   
			$log = Db::name('account_log')->where(['user_id'=>$user_id,'log_id'=>$log_id,'status'=>0])->find();
			$time = time();
	
			if($log['allow_time'] < $time && $log['allow_time'] == 0){
				
				exit(json_encode(['status'=>'-1','msg'=>'还未到允许提现时间!']));  
			
			}
			  if($log){
				  
				$sql = "UPDATE __PREFIX__users SET user_money = user_money + $log[frozen_money] ,frozen_money = frozen_money - $log[frozen_money] WHERE user_id = '$user_id'";
				if(DB::execute($sql)){
					
				  Db::name('account_log')->where(['user_id'=>$user_id,'log_id'=>$log_id])->update(['frozen_money'=>0,'user_money'=>$log['frozen_money'],'status'=>1,'cash_time'=>time()]);	
						
				}
			  
			 exit(json_encode(['status'=>'1','msg'=>'已提取至余额!','url'=>Url::build('Designer/Income')]));  
				
		
			  }else{


				exit(json_encode(['status'=>'-1','msg'=>'已提取至余额，请刷新!']));  

					}
		}else{

			exit(json_encode(['status'=>'-1','msg'=>'参数有误!']));  

		}
		
		
	   }




}