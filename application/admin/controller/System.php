<?php
namespace ylt\admin\controller;
use ylt\admin\logic\GoodsLogic;
use think\AjaxPage;
use think\Page;
use think\db;
use think\Url;
use think\Request;

class System extends Base{
    public  $plate_name;
    /*
    * 初始化操作
    */
    public function _initialize() {
        parent::_initialize();
        //获取当前账号所属板块权限
        $admic_user = Db::name('admin_user')->where('admin_id',session('admin_id'))->find();
        $plate_authority = Db::name('admin_user')->alias('a')->join('plate_menu p','a.plate_id=p.id')->field('p.name,p.id')->where('a.admin_id',session('admin_id'))->find();
        $this->admic_user  = $admic_user;
        if ($plate_authority) {
            $this->plate_authority  = $plate_authority;
        }
    }
	
	/*
	 * 配置入口
	 */
	public function index()
	{          
		/*配置列表*/
		$group_list = array('shop_info'=>'网站信息','basic'=>'基本设置','sms'=>'短信设置','shopping'=>'购物流程设置','smtp'=>'邮件设置');		
		$this->assign('group_list',$group_list);
		$inc_type =  trim(I('get.inc_type','shop_info'));
		$this->assign('inc_type',$inc_type);
		$config = tpCache($inc_type);
		if($inc_type == 'shop_info'){
			$province = Db::name('region')->where(array('parent_id'=>0))->select();
			$city =  Db::name('region')->where(array('parent_id'=>$config['province']))->select();
			$area =  Db::name('region')->where(array('parent_id'=>$config['city']))->select();
			$this->assign('province',$province);
			$this->assign('city',$city);
			$this->assign('area',$area);
		}
		$this->assign('config',$config);//当前配置项
                //config('TOKEN_ON',false);
		return $this->fetch($inc_type);
	}
	
	/*
	 * 新增修改配置
	 */
	public function handle()
	{
		$param = I('post.');
		$inc_type = $param['inc_type'];
		//unset($param['__hash__']);
		unset($param['inc_type']);
		tpCache($inc_type,$param);
		adminLog('修改系统设置');
		$this->success("操作成功",Url::build('System/index',array('inc_type'=>$inc_type)));
	}        
        
       /**
        * 自定义导航
        */
    public function navigationList(){
           $model = Db::name("Navigation");
           $navigationList = $model->order("id desc")->select();            
           $this->assign('navigationList',$navigationList);
           return $this->fetch('navigationList');
     }

    /**
     * 添加修改编辑 前台导航
     */
    public function addEditNav()
    {
        $model = Db::name("Navigation");
        if (IS_POST) {
            if (I('id')){
                $model->update(I('post.'));
            }else{
                $model->insert(I('post.'));
            }
            $this->success("操作成功!!!", Url::build('Admin/System/navigationList'));
            exit;
        }
        // 点击过来编辑时
        $id = I('id',0);
        $navigation = DB::name('navigation')->where('id',$id)->find();
        // 系统菜单
        $GoodsLogic = new GoodsLogic();
        $cat_list = $GoodsLogic->goods_cat_list();
        $select_option = array();
        foreach ($cat_list AS $key => $value) {
            $strpad_count = $value['level'] * 4;
            $select_val = Url::build("/Home/Goods/goodsList", array('id' => $key));
            $select_option[$select_val] = str_pad('', $strpad_count, "-", STR_PAD_LEFT) . $value['name'];
        }

        $this->assign('system_nav', $select_option);

        $this->assign('navigation', $navigation);
        return $this->fetch('_navigation');
    }   
    
    /**
     * 删除前台 自定义 导航
     */
    public function delNav()
    {     
        // 删除导航
        Db::name('Navigation')->where("id",I('id'))->delete();
        $this->success("操作成功!!!",Url::build('Admin/System/navigationList'));
    }
	
	public function refreshMenu(){
		$pmenu = $arr = array();
		$rs = Db::name('system_module')->where('level>1 AND visible=1')->order('mod_id ASC')->select();
		foreach($rs as $row){
			if($row['level'] == 2){
				$pmenu[$row['mod_id']] = $row['title'];//父菜单
			}
		}

		foreach ($rs as $val){
			if($row['level']==2){
				$arr[$val['mod_id']] = $val['title'];
			}
			if($row['level']==3){
				$arr[$val['mod_id']] = $pmenu[$val['parent_id']].'/'.$val['title'];
			}
		}
		return $arr;
	}

        
	/**
	 * 清空系统缓存
	 */
	public function cleanCache(){              
						  
		//delFile(RUNTIME_PATH);
        delFile(RUNTIME_PATH .'/cache');
		delFile(RUNTIME_PATH .'/html');
		delFile(RUNTIME_PATH .'/temp');
		$this->success("操作完成!!!",Url::build('Admin/Admin/index'));
		//$this->redirect('Admin/Index/welcome');
		exit();
		return $this->fetch();
	}
	
	    
    /**
     * 清空静态商品页面缓存
     */
      public function ClearGoodsHtml(){
            $goods_id = I('goods_id');
            if(unlink("./runtime/html/home_goods_goodsinfo_{$goods_id}.html"))
            {
                // 删除静态文件                
                $html_arr = glob("./runtime/html/home_goods*.html");
                foreach ($html_arr as $key => $val)
                {            
                    strstr($val,"home_goods_ajax_consult_{$goods_id}") && unlink($val); // 商品咨询缓存
                    strstr($val,"home_goods_ajaxComment_{$goods_id}") && unlink($val); // 商品评论缓存
                }
                $json_arr = array('status'=>1,'msg'=>'清除成功','result'=>'');
            }
            else 
            {
                $json_arr = array('status'=>-1,'msg'=>'未能清除缓存','result'=>'' );
            }                                                    
            $json_str = json_encode($json_arr);            
            exit($json_str);            
      } 
	  
	  
    /**
     * 商品静态页面缓存清理
     */
      public function ClearGoodsThumb(){
            $goods_id = I('goods_id');
			chmod(UPLOAD_PATH."goods/thumb/".$goods_id, 0777);
            //delFile(UPLOAD_PATH."goods/thumb/".$goods_id); // 删除缩略图
			//rmdir(UPLOAD_PATH."goods/thumb/".$goods_id);
            $json_arr = array('status'=>1,'msg'=>'清除成功,请清除对应的静态页面','result'=>'');
            $json_str = json_encode($json_arr);            
            exit($json_str);            
      } 
	  
	  
    /**
     * 清空 文章静态页面缓存
     */
      public function ClearAritcleHtml(){
            $article_id = I('article_id');
            unlink("./Runtime/Html/Index_Article_detail_{$article_id}.html"); // 清除文章静态缓存
            unlink("./Runtime/Html/Doc_Index_article_{$article_id}_api.html"); // 清除文章静态缓存
            unlink("./Runtime/Html/Doc_Index_article_{$article_id}_phper.html"); // 清除文章静态缓存
            unlink("./Runtime/Html/Doc_Index_article_{$article_id}_android.html"); // 清除文章静态缓存
            unlink("./Runtime/Html/Doc_Index_article_{$article_id}_ios.html"); // 清除文章静态缓存
            $json_arr = array('status'=>1,'msg'=>'操作完成','result'=>'' );                                                          
            $json_str = json_encode($json_arr);            
            exit($json_str);            
      }
	  
      
	//发送测试邮件
	public function send_email(){
		$param = I('post.');
		tpCache($param['inc_type'],$param);
		if(send_email($param['test_eamil'],'后台测试','测试发送验证码:'.mt_rand(1000,9999))){
			exit(json_encode(1));
		}else{
			exit(json_encode(0));
		}
	}
	
    
    /**
     *  管理员登录后 处理相关操作
     */        
     public function login_task()
     {
         
        /*** 随机清空购物车的垃圾数据*/                     
        $time = time() - 3600; // 删除购物车数据  1小时以前的
        Db::name("Cart")->where("user_id = 0 and  add_time < $time")->delete();
        $today_time = time();
        
        // 发货后满多少天自动收货确认
        $auto_confirm_date = tpCache('shopping.auto_confirm_date');
        $auto_confirm_date = $auto_confirm_date * (60 * 60 * 24); // 可设置时间戳        
        $order_id_arr = Db::name('order')->where("order_status = 1 and shipping_status = 1 and pay_status = 1 and ($today_time - shipping_time) >  $auto_confirm_date")->column('order_id');
        foreach($order_id_arr as $k => $v)
        {
            confirm_order($v);
        }

        //收货7天后自动完成订单
        $seven = 7 * (60 * 60 * 24); // 7天的时间戳        
        $order_id = Db::name('order')->where("order_status = 2 and shipping_status = 1 and pay_status = 1 and ($today_time - confirm_time) >  $seven")->column('order_id');
        foreach($order_id as $k => $iv)
        {
            notarize($iv);
        }
        
		// 普通订单未确认 取消订单
		$cancel_confirm_date = tpCache('shopping.cancel_confirm_date') * (60 * 60);
		$cancel_time = $today_time - $cancel_confirm_date;
		Db::name('order')->where("add_time < '$cancel_time' and order_status = '0' and pay_status = '0'")->update(['order_status'=>3]);

		// 活动订单未确认 取消订单
         $activity_time = $today_time - 1800;
         $activity_order = Db::name('order')->where("add_time < $activity_time and order_status ='0' and pay_status ='0' and order_prom_id > 0 ")->column('order_id');
         foreach($activity_order as $k =>$v){
             cancel_activity_order($v);
         }

         //分销奖励金相关时间的更新
         $info=Db::name('distribution_id')->where('id',1)->find();
         if ($info['next_time'] < time()) {             //当前时间超过下次计算时间时将三个阶段的时间更新
            Db::name('distribution_id')->where('id',1)->update(['last_time'=>$info['this_time'],'this_time'=>$info['next_time']]);
            $next_time=strtotime("+".$info['stockdater']." months",$info['next_time']);
            Db::name('distribution_id')->where('id',1)->update(['next_time'=>$next_time]);
         }
     }
	 
	 
     
     function ajax_get_action()
     {
     	$control = trim(I('controller'));
     	$advContrl = get_class_methods("ylt\\admin\\controller\\".str_replace('.php','',$control));
     	//dump($advContrl);
     	$baseContrl = get_class_methods('ylt\admin\controller\Base');
     	$diffArray  = array_diff($advContrl,$baseContrl);
     	$html = '';
     	foreach ($diffArray as $val){
     		$html .= "<option value='".$val."'>".$val."</option>";
     	}
     	exit($html);
     }
	 
    /**
     * [right_list 权限资源列表]
     * @return [type] [description]
     */
    function right_list(){
        $plate_menu = Db::name('plate_menu')->where('groups != 0')->select();
        foreach ($plate_menu as $key => $value) {
            $array[]= array($value['right']=>$value['name']);
        }
        $group = array_reduce($array, 'array_merge', array());

     	$right_list = Db::name('system_menu')->alias('s')->join('plate_menu p','s.plate_id=p.id')->field('s.*,p.name as plate_name')->order('s.plate_id','asc')->select();
        $Page  = new Page(count($right_list),20);
        $show = $Page->show();
        $right_list_s = Db::name('system_menu')->alias('s')->join('plate_menu p','s.plate_id=p.id')->field('s.*,p.name as plate_name')->order('s.plate_id','asc')->limit($Page->firstRow,$Page->listRows)->select();
     	$this->assign('right_list',$right_list_s);
     	$this->assign('group',$group);
        $this->assign('pager',$Page);
        $this->assign('page',$show);
     	return $this->fetch();
     }

     /**
      * [plate_list 板块列表及增修]
      * @return [type] [description]
      */
	public function plate_list(){
        $plate_list = Db::name('plate_menu')->order('groups','asc')->select();
        foreach ($plate_list as $key => $value) {
            $value['plate_name'] = Db::name('plate_menu')->where('id',$value['groups'])->value('name');
            $plate_list_s[] = $value;
        }
        $this->assign('plate_list',$plate_list_s);
        return $this->fetch();
    }
    public function edit_plate(){
        $plate_menu = Db::name('plate_menu')->where('groups',0)->where('is_del!=1')->select();
     	if(IS_POST){
            $data = I('post.');
            $data['name'] = trim(I('post.name'));
     		$data['right'] = trim(I('post.right'));
            if ($data['groups'] != 0) {
                if (empty($data['right'])){
                    $this->error('控制器代码不可为空');
                }
            }
            if(!empty($data['id'])){
                Db::name('plate_menu')->where(array('id'=>$data['id']))->update($data);
                adminLog('修改板块/分组 '.input('name').'');
            }else{
                if(Db::name('plate_menu')->where(array('name'=>$data['name']))->count()>0){
                    $this->error('该板块/分组名称已添加，请检查',Url::build('System/plate_list'));
                }
                unset($data['id']);
                Db::name('plate_menu')->insert($data);
                adminLog('添加板块/分组 '.input('name').'');
            }
     		$this->success('操作成功',Url::build('System/plate_list'));
     		exit;
     	}
     	$id = I('id');
     	if($id){
            $info = Db::name('plate_menu')->where(array('id'=>$id))->find();
            $this->assign('info',$info);
     	}
        $this->assign('plate_menu',$plate_menu);
        return $this->fetch();
    }
    

     
     /**
      * [edit_right 权限资源添加及修改管理]
      * @return [type] [description]
      */
     public function edit_right(){
        if(IS_POST){
            $data = I('post.');
            $data['name'] = trim(I('post.name'));
            $data['right'] = implode(',',$data['right']);
            if(!empty($data['id'])){
                Db::name('system_menu')->where(array('id'=>$data['id']))->update($data);
                adminLog('修改权限 '.input('name').'');
            }else{
                if(Db::name('system_menu')->where(array('name'=>$data['name']))->count()>0){
                    $this->error('该权限名称已添加，请检查',Url::build('System/right_list'));
                }
                unset($data['id']);
                Db::name('system_menu')->insert($data);
                adminLog('添加权限 '.input('name').'');
            }
            $this->success('操作成功',Url::build('System/right_list'));
            exit;
        }
        $id = I('id')?I('id'):0;
        if($id){
            $info = Db::name('system_menu')->where(array('id'=>$id))->find();
            $info['right'] = explode(',', $info['right']);
            if (!$info['plate_id']) {
                $info['plate_id'] = 2;
            }
        }else{
            $info['plate_id'] = 2;
        }
        $plate_menu = Db::name('plate_menu')->where('groups != 0')->select();
        foreach ($plate_menu as $key => $value) {
            $array[]= array($value['right']=>$value['name']);
        }
        $group = array_reduce($array, 'array_merge', array());

        $planPath = APP_PATH.'admin/controller';
        $planList = array();
        $dirRes   = opendir($planPath);
        while($dir = readdir($dirRes))
        {
            if(!in_array($dir,array('.','..','.svn')))
            {    
                $dir=str_replace(".php",'',$dir);
                $planList[] = basename($dir,'.class.php');
            }
        }
        $plate_menu = Db::name('plate_menu')->where('groups',0)->where('is_del!=1')->select();
        $this->assign('plate_menu',$plate_menu);
        $this->assign('planList',$planList);
        $this->assign('group',$group);
        $this->assign('id',$id);
        $this->assign('info',$info);
        return $this->fetch();
     }
     public function ajax_edit_right(){
        $id = I('id')?I('id'):0;
        $plate_authority = $this->plate_authority;
        $info = Db::name('system_menu')->where(array('id'=>$id))->find();
        if ($plate_authority) {
            $info['plate_id'] = $plate_id = $plate_authority['id'];
        }else{
            $info['plate_id'] = $plate_id = I('plate_id',2);
        }
        $groups = Db::name('plate_menu')->where('groups',$plate_id)->where('1=1')->select();
        $this->assign('info',$info);
        $this->assign('groups',$groups);
        return $this->fetch('ajax_edit_right');
     }
	 

     public function plate_del(){
        $id = I('del_id');
        if(is_array($id)){
            $id = implode(',', $id); 
        }
        if(!empty($id)){
            $r = Db::name('plate_menu')->where("id in ($id)")->delete();
            if($r){
                respose(1);
            }else{
                respose('删除失败');
            }
        }else{
            respose('参数有误');
        }
     }
     public function right_del(){
        $id = I('del_id');
        if(is_array($id)){
            $id = implode(',', $id); 
        }
        if(!empty($id)){
            $r = Db::name('system_menu')->where("id in ($id)")->delete();
            if($r){
                respose(1);
            }else{
                respose('删除失败');
            }
        }else{
            respose('参数有误');
        }
     }
	 
	 
	  public function pay(){

        $plugin_list = Db::name('plugin')->select();
        $plugin_list = group_same_key($plugin_list,'type');
        $local_list = $this->scanPlugin();
        $this->assign('payment',$plugin_list['payment']);
        $this->assign('login',$plugin_list['login']);
        $this->assign('function',$plugin_list['function']);
        $this->assign('type',trim(I('type')));
        return $this->fetch();
    }
	
	
	    /**
     * 插件目录扫描
     * @return array 返回目录数组
     */
    private function scanPlugin(){
        $plugin_list = array();
        $plugin_list['payment'] = $this->dirscan(config('PAYMENT_PLUGIN_PATH'));
        $plugin_list['login'] = $this->dirscan(config('LOGIN_PLUGIN_PATH'));
        
        foreach($plugin_list as $k=>$v){
            foreach($v as $k2=>$v2){
 
                if(!file_exists(PLUGIN_PATH.$k.'/'.$v2.'/config.php'))
                    unset($plugin_list[$k][$k2]);
                else
                {
                    $plugin_list[$k][$v2] = include(PLUGIN_PATH.$k.'/'.$v2.'/config.php');
                    unset($plugin_list[$k][$k2]);                    
                }
            }
        }
        return $plugin_list;
    }
	
	
	    /**
     * 获取插件目录列表
     * @param $dir
     * @return array
     */
    private function dirscan($dir){
        $dirArray = array();
        if (false != ($handle = opendir ( $dir ))) {
            $i=0;
            while ( false !== ($file = readdir ( $handle )) ) {
                //去掉"“.”、“..”以及带“.xxx”后缀的文件
                if ($file != "." && $file != ".."&&!strpos($file,".")) {
                    $dirArray[$i]=$file;
                    $i++;
                }
            }
            //关闭句柄
            closedir ( $handle );
        }
        return $dirArray;
    }
	
	  /*
     * 插件信息配置
     */
    public function setting(){

        $condition['type'] = trim(I('get.type'));
        $condition['code'] = trim(I('get.code'));
        $model = Db::name('plugin');
        $row = $model->where($condition)->find();
        if(!$row){
            exit($this->error("不存在该插件"));
        }

        $row['config'] = unserialize($row['config']);

        if(IS_POST){
			if(session('admin_id') != 1)
				exit($this->error("没有修改权限"));
            $config = trim(I('post.config/a'));
            //空格过滤
            $config = trim_array_element($config);
            if($config){
                $config = serialize($config);
            }
            $row = $model->where($condition)->update(array('config_value'=>$config));
            if($row){
                exit($this->success("操作成功"));
            }
            exit($this->error("操作失败"));
        }

        $this->assign('plugin',$row);
        $this->assign('config_value',unserialize($row['config_value']));

        return $this->fetch();
    }
    
    //极光推送，如果area=all为，全部推送，area=某个标识，单个推送
    public function jpush(){
    	$data['message']=trim($_POST['message']);
    	$data['type']=$_POST['type'];
    	$data['url_ad']=$_POST['url_ad'];
    	$data['badge']=$_POST['badge'];
    	$data['remark']="ylt_jpush";
    	$data['area']=$_POST['area'];
    	$act=$_POST['act'];
    	
    	if($act=="save"){
    		if(empty($data['message'])){
    			exit($this->error("请填写推送内容"));
    		}
    		$url=$_SERVER['SERVER_NAME']."/newapp/api/jpush.php";
    		$rs=$this->file_get_contents_curl($url,$data);
    		if($rs==true){
    			adminLog("极光推送:{$data[message]}");
    			exit($this->success("操作成功"));
    		}else{
    			exit($this->error("操作失败"));
    		}
    	}
    	return $this->fetch();
    }
    
    
   public  function file_get_contents_curl($url, $data)
    {
    	$ch = curl_init();
    	curl_setopt($ch, CURLOPT_URL, $url);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    
    	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    	'accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
    	'accept-language:zh-CN,zh;q=0.8,zh-TW;q=0.6,en;q=0.4',
    	'cache-control:max-age=0',
    	'upgrade-insecure-requests:1',
    	'user-agent:Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.94 Safari/537.36',
    	));
    
    	curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    	$dxycontent = curl_exec($ch);
    	return $dxycontent;
    }

    /**
     * [hengda 恒大表单]
     * @return [type] [description]
     */
    public function hengda(){
        return $this->fetch();
    }
    /*
     *Ajax恒大表单数据
     */
    public function ajaxhengda(){
        $d_begin = strtotime(input('deliveryTime_begin'));
        $d_end   = strtotime(input('deliveryTime_end')); 
        $i_begin = strtotime(input('installDate_begin'));
        $i_end   = strtotime(input('installDate_end')); 
        $r_begin = strtotime(input('replenishmentTime_begin'));
        $r_end   = strtotime(input('replenishmentTime_end')); 
        
        $condition = array();
        // 关键词搜索
        $key_word = I('key_word') ? trim(I('key_word')) : '';
        if($key_word)
        {
            $where .= "customerName like '%$key_word%' or tel like '%$key_word%'" ;
        }
        //获取表单列表
        $floorInput = input('floorInput');
        if($floorInput){
            $where['floorInput'] = "$floorInput";
        }
        if($d_begin && $d_end){
            $where['deliveryTime'] = $condition['deliveryTime'] = array('between',"$d_begin,$d_end");
        }
        if($i_begin && $i_end){
            $where['installDate'] = $condition['installDate'] = array('between',"$i_begin,$i_end");
        }
        if($r_begin && $r_end){
            $where['replenishmentTime'] = $condition['installDate'] = array('between',"$r_begin,$r_end");
        }
        $ress = Db::name('hengda')->where($where)->select();
        $count =count($ress);
        $Page  = new AjaxPage($count,20);
        //  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            if($key == 'deliveryTime'){
                $between_time = explode(',',$val[1]);
                $parameter_add_time = date('Y/m/d',$between_time[0]) . '-' . date('Y/m/d',$between_time[1]);
                $Page->parameter['timegap'] = $parameter_add_time;
            }else if($key == 'installDate') {
                $between_time = explode(',',$val[1]);
                $parameter_add_time = date('Y/m/d',$between_time[0]) . '-' . date('Y/m/d',$between_time[1]);
                $Page->parameter['timegap'] = $parameter_add_time;
            }else if($key == 'replenishmentTime') {
                $between_time = explode(',',$val[1]);
                $parameter_add_time = date('Y/m/d',$between_time[0]) . '-' . date('Y/m/d',$between_time[1]);
                $Page->parameter['timegap'] = $parameter_add_time;
            }else{
                $Page->parameter[$key]   =  urlencode($val);
            }
        }
        $show = $Page->show();

        $res = Db::name('hengda')->where($where)->order('id desc')->limit($Page->firstRow,$Page->listRows)->select();
        $this->assign('res',$res);
        $this->assign('page',$show);       // 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
    }

    public function hengdasave(){
        $id=$_GET['id'];
        $hengda=Db::name('hengda')->where('id',$id)->find();
        if (IS_AJAX) {
            $data=I('');
            $data['deliveryTime']=strtotime($data['deliveryTime']);
            $data['installDate']=strtotime($data['installDate']);
            $data['replenishmentTime']=strtotime($data['replenishmentTime']);
            $save=Db::name('hengda')->where('id',$data['id'])->update($data);
            if ($save) {
                return array( 'status' => 1,'msg' => '修改成功',);
            }
        }
        $this->assign('hengda',$hengda);
        return $this->fetch();
    }
    /**
     * 导出数据订单
     */
     public function export_order()
    {   
        $where = "id !=''";
        $floorInput = input('floorInput');
        if($floorInput){        //楼栋
            $where .= " AND floorInput  =  '$floorInput'";
        }
        if(input('deliveryTime_begin')){   //送货时间
            $where .= " AND deliveryTime >= ". strtotime(input('deliveryTime_begin'));
        }
        if(input('deliveryTime_end')){
            $where .= " AND deliveryTime <= ". strtotime(input('deliveryTime_end'));
        }
        if(input('installDate_begin')){    //安装时间
            $where .= " AND installDate >= ". strtotime(input('installDate_begin'));
        }
        if(input('installDate_end')){
            $where .= " AND installDate <= ". strtotime(input('installDate_end'));
        }
        if(input('replenishmentTime_begin')){   //补货+安装
            $where .= " AND replenishmentTime >= ". strtotime(input('replenishmentTime_begin'));
        }
        if(input('replenishmentTime_end')){
            $where .= " AND replenishmentTime <= ". strtotime(input('replenishmentTime_end'));
        }
        $sql = Db::name('hengda')->where($where)->select();
        $orderList = $sql;
        $strTable ='<table width="500" border="1">';
        $strTable .= '<tr>';
        $strTable .= '<td style="text-align:center;font-size:12px;width:120px;">编号</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="100">楼栋名称</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">房间号码</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">客户姓名</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">联系电话</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">预约送货日期</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">预约安装时间</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">补货+安装时间</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">表单提交时间</td>';
        $strTable .= '</tr>';
        if(is_array($orderList)){
            foreach($orderList as $k=>$val){
                $strTable .= '<tr>';
                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['id'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['floorInput'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['roomNum'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['customerName'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['tel'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.date('Y-m-d',$val['deliveryTime']).' </td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.date('Y-m-d',$val['installDate']).' </td>';
                if (empty($val['replenishmentTime'])) {
                    $strTable .= '<td style="text-align:left;font-size:12px;">'.'/'.'</td>';
                }else{
                    $strTable .= '<td style="text-align:left;font-size:12px;">'.date('Y-m-d',$val['replenishmentTime']).'</td>';
                }
                $strTable .= '<td style="text-align:left;font-size:12px;">'.date('Y-m-d',$val['add_time']).' </td>';
                $strTable .= '</tr>';
            }
        }
        $strTable .='</table>';
        unset($orderList);
        downloadExcel($strTable,'恒大表单');
        exit();
    }




    /**
     * [hengda 恒大御景表单]
     * @return [type] [description]
     */
    public function hdyujing(){
        return $this->fetch();
    }
    /*
     *Ajax恒大御景表单数据
     */
    public function ajaxhdyujing(){
        $d_begin = strtotime(input('deliveryTime_begin'));
        $d_end   = strtotime(input('deliveryTime_end')); 
        $i_begin = strtotime(input('installDate_begin'));
        $i_end   = strtotime(input('installDate_end')); 
        $condition = array();
        // 关键词搜索
        $key_word = I('key_word') ? trim(I('key_word')) : '';
        if($key_word)
        {
            $where .= "customerName like '%$key_word%' or tel like '%$key_word%'" ;
        }
        //获取表单列表
        $floorInput = input('floorInput');
        if($floorInput){
            $where['floorInput'] = "$floorInput";
        }
        if($d_begin && $d_end){
            $where['deliveryTime'] = $condition['deliveryTime'] = array('between',"$d_begin,$d_end");
        }
        if($i_begin && $i_end){
            $where['installDate'] = $condition['installDate'] = array('between',"$i_begin,$i_end");
        }
        if (I('is_make')==1) {
            $where['add_time']  = array('between',"1,2000000000");
        }else if (I('is_make')==2){
            $where['add_time']  = array('between',"0,0");
        }
        $ress = Db::name('hd_yujing')->where($where)->select();
        $count =count($ress);
        $Page  = new AjaxPage($count,20);
        //  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            if($key == 'deliveryTime'){
                $between_time = explode(',',$val[1]);
                $parameter_add_time = date('Y/m/d',$between_time[0]) . '-' . date('Y/m/d',$between_time[1]);
                $Page->parameter['timegap'] = $parameter_add_time;
            }else if($key == 'installDate') {
                $between_time = explode(',',$val[1]);
                $parameter_add_time = date('Y/m/d',$between_time[0]) . '-' . date('Y/m/d',$between_time[1]);
                $Page->parameter['timegap'] = $parameter_add_time;
            }else{
                $Page->parameter[$key]   =  urlencode($val);
            }
        }
        $show = $Page->show();

        $res = Db::name('hd_yujing')->where($where)->order('id desc')->limit($Page->firstRow,$Page->listRows)->select();
        $this->assign('res',$res);
        $this->assign('page',$show);       // 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
    }

    public function hdyujingsave(){
        $id=$_GET['id'];
        $hengda=Db::name('hd_yujing')->where('id',$id)->find();
        if (IS_AJAX) {
            $data=I('');
            $data['deliveryTime']=strtotime($data['deliveryTime']);
            $data['installDate']=strtotime($data['installDate']);
            $data['replenishmentTime']=strtotime($data['replenishmentTime']);
            $save=Db::name('hd_yujing')->where('id',$data['id'])->update($data);
            if ($save) {
                return array( 'status' => 1,'msg' => '修改成功',);
            }
        }
        $this->assign('hengda',$hengda);
        return $this->fetch();
    }
    /**
     * 导出数据订单
     */
     public function export_order_hdyujing()
    {   
        $where = "id !=''";
        $floorInput = input('floorInput');
        if($floorInput){        //楼栋
            $where .= " AND floorInput  =  '$floorInput'";
        }
        if(input('deliveryTime_begin')){   //送货时间
            $where .= " AND deliveryTime >= ". strtotime(input('deliveryTime_begin'));
        }
        if(input('deliveryTime_end')){
            $where .= " AND deliveryTime <= ". strtotime(input('deliveryTime_end'));
        }
        if(input('installDate_begin')){    //安装时间
            $where .= " AND installDate >= ". strtotime(input('installDate_begin'));
        }
        if(input('installDate_end')){
            $where .= " AND installDate <= ". strtotime(input('installDate_end'));
        }
        if (I('is_make')==1) {
            $where .= " AND add_time >= 1";
        }else if (I('is_make')==2){
            $where .= " AND add_time <= 0";
        }
        $sql = Db::name('hd_yujing')->where($where)->select();
        $orderList = $sql;
        $strTable ='<table width="500" border="1">';
        $strTable .= '<tr>';
        $strTable .= '<td style="text-align:center;font-size:12px;width:120px;">编号</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="100">楼栋名称</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">房间号码</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">客户姓名</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">联系电话</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">是否预约</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">预约送货日期</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">预约安装时间</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">表单提交时间</td>';
        $strTable .= '</tr>';
        if(is_array($orderList)){
            foreach($orderList as $k=>$val){
                $strTable .= '<tr>';
                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['id'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['floorInput'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['roomNum'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['customerName'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['tel'].'</td>';
                if ($val['add_time']) {
                   $strTable .= '<td style="text-align:left;font-size:12px;"> 是 </td>';
                }else{
                   $strTable .= '<td style="text-align:left;font-size:12px;"> 否 </td>';
                }
                if ($val['deliveryTime']) {
                   $strTable .= '<td style="text-align:left;font-size:12px;">'.date('Y-m-d',$val['deliveryTime']).' </td>';
                }else{
                   $strTable .= '<td style="text-align:left;font-size:12px;">/ </td>';
                }
                if ($val['installDate']) {
                    $strTable .= '<td style="text-align:left;font-size:12px;">'.date('Y-m-d',$val['installDate']).' </td>';
                }else{
                    $strTable .= '<td style="text-align:left;font-size:12px;">/</td>';
                }
                if ($val['add_time']) {
                    $strTable .= '<td style="text-align:left;font-size:12px;">'.date('Y-m-d',$val['add_time']).' </td>';
                }else{
                    $strTable .= '<td style="text-align:left;font-size:12px;">/</td>';
                }
                $strTable .= '</tr>';
            }
        }
        $strTable .='</table>';
        unset($orderList);
        downloadExcel($strTable,'恒大御景半岛表单');
        exit();
    }
    


    /**
     * [charity 抗疫
     * @return [type] [description]
     */
    public function charity(){
        return $this->fetch();
    }
    /*
     *Ajax抗疫数据
     */
    public function ajaxcharity(){
        $d_begin = strtotime(input('add_time_begin'));
        $d_end   = strtotime(input('add_time_end')); 
        
        $condition = array();
        // 关键词搜索
        $key_word = I('key_word') ? trim(I('key_word')) : '';
        if($key_word)
        {
            $where .= "do_name like '%$key_word%' or do_phone like '%$key_word%'" ;
        }
        //获取表单列表
        $is_purchase = input('is_purchase');
        if($is_purchase){   //购买/供货
            $where['is_purchase'] = "$is_purchase";
        }
        $is_donate = input('is_donate');
        if($is_donate){     //捐赠/自用
            $where['is_donate'] = "$is_donate";
        }
        $status = input('status');
        if($status){     //审核
            $where['status'] = "$status";
        }
        if($d_begin && $d_end){  //填表时间
            $where['add_time'] = $condition['add_time'] = array('between',"$d_begin,$d_end");
        }
        $ress = Db::name('medical_charity')->where($where)->select();
        $count =count($ress);
        $Page  = new AjaxPage($count,20);
        //  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            if($key == 'add_time'){
                $between_time = explode(',',$val[1]);
                $parameter_add_time = date('Y/m/d',$between_time[0]) . '-' . date('Y/m/d',$between_time[1]);
                $Page->parameter['timegap'] = $parameter_add_time;
            }else{
                $Page->parameter[$key]   =  urlencode($val);
            }
        }
        $show = $Page->show();

        $res = Db::name('medical_charity')->where($where)->order('do_id desc')->limit($Page->firstRow,$Page->listRows)->select();
        $this->assign('res',$res);
        $this->assign('page',$show);       // 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
    }

    public function charitysave(){
        $id=$_GET['id'];
        $charity=Db::name('medical_charity')->where('do_id',$id)->find();
        if ($charity['supply_goods']) {
            $supply_goods = explode(';',$charity['supply_goods']);
        }
        if ($charity['materials']) {
            $materials = explode(',',$charity['materials']);
        }
        if ($charity['materials_s']) {
            $materials_s = explode(',',$charity['materials_s']);
        }
        if (IS_AJAX) {
            $data=I('');
            if ($data['supply_goods']) {
                $data['supply_goods'] = implode(';',$data['supply_goods']);
            }
            if ($data['materials']) {
                $data['materials'] = implode(',',$data['materials']);
            }
            if ($data['materials_s']) {
                $data['materials_s'] = implode(',',$data['materials_s']);
            }
            if ($data['is_purchase']=='购买') {
                $data['is_purchase']=1;
            }else if($data['is_purchase']=='供货'){
                $data['is_purchase']=2;
            }else{
                $data['is_purchase']=3;
            }
            $save=Db::name('medical_charity')->where('do_id',$data['do_id'])->update($data);
            if ($save) {
                return array( 'status' => 1,'msg' => '修改成功',);
            }
        }
        $this->assign('charity',$charity);
        $this->assign('supply_goods',$supply_goods);
        $this->assign('materials',$materials);
        $this->assign('materials_s',$materials_s);
        return $this->fetch();
    }
    /**
     * 导出数据订单
     */
     public function export_order_charity()
    {   
        $where = "do_id !=''";
        $is_purchase = input('is_purchase');
        if($is_purchase){        //购买/供货
            $where .= " AND is_purchase  =  '$is_purchase'";
        }
        $is_donate = input('is_donate');
        if($is_donate){          //捐赠/自用
            $where .= " AND is_donate  =  '$is_donate'";
        }
        $status = input('status');
        if($status){     //审核
            $where .= " AND status  =  '$status'";
        }
        if(input('deliveryTime_begin')){   //填表日期
            $where .= " AND add_time >= ". strtotime(input('deliveryTime_begin'));
        }
        if(input('deliveryTime_end')){
            $where .= " AND add_time <= ". strtotime(input('deliveryTime_end'));
        }
        $sql = Db::name('medical_charity')->where($where)->select();
        $orderList = $sql;
        $strTable ='<table width="500" border="1">';
        $strTable .= '<tr>';
        $strTable .= '<td style="text-align:center;font-size:12px;width:120px;">编号</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="100">购买/供货</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">捐赠/自用</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">预算</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">购买物资</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">联系人</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">联系电话</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">公司名称</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">公司地址</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">供货物资</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">备注</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">提交时间</td>';
        $strTable .= '</tr>';
        if(is_array($orderList)){
            foreach($orderList as $k=>$val){
                $strTable .= '<tr>';
                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['do_id'].'</td>';
                if($val['is_purchase'] == 1) {
                    $strTable .= '<td style="text-align:left;font-size:12px;">购买</td>';
                }else{
                    $strTable .= '<td style="text-align:left;font-size:12px;">供货</td>';
                }
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['is_donate'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['budget'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['materials'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['do_name'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['do_phone'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['do_company'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['do_address'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['supply_goods'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['comment'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.date('Y-m-d',$val['add_time']).' </td>';
                $strTable .= '</tr>';
            }
        }
        $strTable .='</table>';
        unset($orderList);
        downloadExcel($strTable,'抗疫行动');
        exit();
    }


    /**
     * [appointment 预约活动
     * @return [type] [description]
     */
    public function appointment(){
        return $this->fetch();
    }
    /*
     *Ajax预约数据
     */
    public function ajaxappointment(){
        $d_begin = strtotime(input('add_time_begin'));
        $d_end   = strtotime(input('add_time_end')); 
        
        $condition = array();
        $where['a.consult_type'] = 5;   //预约
        // 关键词搜索
        $key_word = I('key_word') ? trim(I('key_word')) : '';
        if($key_word)
        {
            $where .= "nickname like '%$key_word%' or mobile like '%$key_word%'" ;
        }
        //获取条件列表
        $goods_id = input('goods_id');
        if($goods_id){       //是否审核
            $where['a.goods_id'] = "$goods_id";
        }
        $is_win = input('is_win');
        if($is_win){       //是否中签
            $where['a.is_win'] = "$is_win";
        }
        $make_type = input('make_type');
        if($make_type){       //活动批次
            $where['a.make_type'] = "$make_type";
        }
        if($d_begin && $d_end){  //填表时间
            $where['a.add_time'] = $condition['add_time'] = array('between',"$d_begin,$d_end");
        }
        $ress = Db::name('goods_consult')->alias('a')->join('users u','u.user_id=a.user_id')->join('goods g','g.goods_id=a.goods_id')->where($where)->field('a.*,u.nickname,u.mobile')->select();
        $count =count($ress);
        $Page  = new AjaxPage($count,20);
        //  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            if($key == 'add_time'){
                $between_time = explode(',',$val[1]);
                $parameter_add_time = date('Y/m/d',$between_time[0]) . '-' . date('Y/m/d',$between_time[1]);
                $Page->parameter['timegap'] = $parameter_add_time;
            }else{
                $Page->parameter[$key]   =  urlencode($val);
            }
        }
        $show = $Page->show();

        $res = Db::name('goods_consult')->alias('a')->join('users u','u.user_id=a.user_id')->join('goods g','g.goods_id=a.goods_id')->where($where)->limit($Page->firstRow,$Page->listRows)->order('id desc')->field('a.*,u.nickname,u.mobile,g.goods_name')->select();
        $this->assign('res',$res);
        $this->assign('page',$show);       // 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
    }

    /**
     * 导出数据订单
     */
     public function export_order_appointment()
    {   
        $where = "id !=''";
        $goods_id = input('goods_id');
        if($goods_id){       //是否审核
            $where .= " AND goods_id  =  '$goods_id'";
        }
        $is_win = input('is_win');
        if($is_win){       //是否中签
            $where .= " AND is_win  =  '$is_win'";
        }
        $make_type = input('make_type');
        if($make_type){       //活动批次
            $where .= " AND make_type  =  '$make_type'";
        }
        if(input('deliveryTime_begin')){   //填表日期
            $where .= " AND add_time >= ". strtotime(input('deliveryTime_begin'));
        }
        if(input('deliveryTime_end')){
            $where .= " AND add_time <= ". strtotime(input('deliveryTime_end'));
        }
        $sql = Db::name('goods_consult')->alias('a')->join('users u','u.user_id=a.user_id')->join('goods g','g.goods_id=a.goods_id')->where($where)->order('id desc')->field('a.*,u.nickname,u.mobile,g.goods_name')->select();
        $orderList = $sql;
        $strTable ='<table width="500" border="1">';
        $strTable .= '<tr>';
        $strTable .= '<td style="text-align:center;font-size:12px;width:120px;">ID</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">用户ID</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">用户名称</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">绑定手机号</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">预约商品</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">预约批次</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">预约时间</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">是否中签</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">是否购买</td>';
        $strTable .= '</tr>';
        if(is_array($orderList)){
            foreach($orderList as $k=>$val){
                $strTable .= '<tr>';
                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['id'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['user_id'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['nickname'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['mobile'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['goods_name'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['make_type'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.date('Y-m-d',$val['add_time']).' </td>';
                if($val['is_win'] == 1) {
                    $strTable .= '<td style="text-align:left;font-size:12px;">是</td>';
                }else{
                    $strTable .= '<td style="text-align:left;font-size:12px;">否</td>';
                }
                if($val['is_use'] == 1) {
                    $strTable .= '<td style="text-align:left;font-size:12px;">是</td>';
                }else{
                    $strTable .= '<td style="text-align:left;font-size:12px;">否</td>';
                }
                $strTable .= '</tr>';
            }
        }
        $strTable .='</table>';
        unset($orderList);
        downloadExcel($strTable,'预约活动');
        exit();
    }


    /**
     * [respirator 抗疫活动申请口罩
     * @return [type] [description]
     */
    public function respirator(){
        return $this->fetch();
    }
    /*
     *Ajax抗疫数据
     */
    public function ajaxrespirator(){
        $d_begin = strtotime(input('add_time_begin'));
        $d_end   = strtotime(input('add_time_end')); 
        
        $condition = array();
        // 关键词搜索
        $key_word = I('key_word') ? trim(I('key_word')) : '';
        if($key_word)
        {
            $where .= "nickname like '%$key_word%' or phone like '%$key_word%'" ;
        }
        //获取条件列表
        $is_check = input('is_check');
        if($is_check){       //是否审核
            $where['is_check'] = "$is_check";
        }
        $is_deliver = input('is_deliver');
        if($is_deliver){     //是否发货
            $where['is_deliver'] = "$is_deliver";
        }
        // $is_get = input('is_get');
        // if($is_get){     //是否领取
        //     $where['is_get'] = "$is_get";
        // }
        if($d_begin && $d_end){  //填表时间
            $where['add_time'] = $condition['add_time'] = array('between',"$d_begin,$d_end");
        }
        $ress = Db::name('goods_apply_list')->alias('a')->join('users u','u.user_id=a.user_id')->where($where)->field('a.*,u.nickname')->select();
        $count =count($ress);
        $Page  = new AjaxPage($count,20);
        //  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            if($key == 'add_time'){
                $between_time = explode(',',$val[1]);
                $parameter_add_time = date('Y/m/d',$between_time[0]) . '-' . date('Y/m/d',$between_time[1]);
                $Page->parameter['timegap'] = $parameter_add_time;
            }else{
                $Page->parameter[$key]   =  urlencode($val);
            }
        }
        $show = $Page->show();

        // $res = Db::name('goods_apply_list')->where($where)->limit($Page->firstRow,$Page->listRows)->select();
        $res = Db::name('goods_apply_list')->alias('a')->join('users u','u.user_id=a.user_id')->where($where)->limit($Page->firstRow,$Page->listRows)->order('id desc')->field('a.*,u.nickname')->select();
        $this->assign('res',$res);
        $this->assign('page',$show);       // 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
    }

    /**
     * 导出数据订单
     */
     public function export_order_respirator()
    {   
        $where = "id !=''";
        $is_check = input('is_check');
        if($is_check){       //是否审核
            $where .= " AND is_check  =  '$is_check'";
        }
        $is_deliver = input('is_deliver');
        if($is_deliver){     //是否发货
            $where .= " AND is_deliver  =  '$is_deliver'";
        }
        // $is_get = input('is_get');
        // if($is_get){     //是否领取
            // $where .= " AND is_get  =  '$is_get'";
        // }
        if(input('deliveryTime_begin')){   //填表日期
            $where .= " AND add_time >= ". strtotime(input('deliveryTime_begin'));
        }
        if(input('deliveryTime_end')){
            $where .= " AND add_time <= ". strtotime(input('deliveryTime_end'));
        }
        $sql = Db::name('goods_apply_list')->alias('a')->join('users u','u.user_id=a.user_id')->where($where)->field('a.*,u.nickname')->select();
        // $sql = Db::name('goods_apply_list')->where($where)->select();
        $orderList = $sql;
        $strTable ='<table width="500" border="1">';
        $strTable .= '<tr>';
        $strTable .= '<td style="text-align:center;font-size:12px;width:120px;">编号</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">申请用户</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">联系电话</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">申请类型</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">申请时间</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">IP地址</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="100">是否审核</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">是否发货</td>';
        $strTable .= '</tr>';
        if(is_array($orderList)){
            foreach($orderList as $k=>$val){
                $strTable .= '<tr>';
                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['id'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['nickname'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['phone'].'</td>';
                if($val['type'] == 1) {
                    $strTable .= '<td style="text-align:left;font-size:12px;">3个口罩</td>';
                }elseif($val['type'] == 2){
                    $strTable .= '<td style="text-align:left;font-size:12px;">10个口罩</td>';
                }elseif($val['type'] == 3){
                    $strTable .= '<td style="text-align:left;font-size:12px;">1个测温枪</td>';
                }
                $strTable .= '<td style="text-align:left;font-size:12px;">'.date('Y-m-d',$val['add_time']).' </td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['ip'].'</td>';
                if($val['is_check'] == 1) {
                    $strTable .= '<td style="text-align:left;font-size:12px;">是</td>';
                }elseif($val['is_check'] == 2){
                    $strTable .= '<td style="text-align:left;font-size:12px;">否</td>';
                }
                if($val['is_deliver'] == 1) {
                    $strTable .= '<td style="text-align:left;font-size:12px;">是</td>';
                }elseif($val['is_deliver'] == 2){
                    $strTable .= '<td style="text-align:left;font-size:12px;">否</td>';
                }
                $strTable .= '</tr>';
            }
        }
        $strTable .='</table>';
        unset($orderList);
        downloadExcel($strTable,'抗疫行动');
        exit();
    }


    


    /**
     * [respirator 线上合同
     * @return [type] [description]
     */
    public function contract_list(){
        return $this->fetch();
    }
    /*
     *Ajax线上合同
     */
    public function ajaxcontract_list(){
        $d_begin = strtotime(input('add_time_begin'));
        $d_end   = strtotime(input('add_time_end')); 
        
        $condition = array();
        // 关键词搜索
        $key_word = I('key_word') ? trim(I('key_word')) : '';
        if($key_word)
        {
            $where .= "contract_num like '%$key_word%' or lx_username like '%$key_word%' or lx_tel like '%$key_word%'" ;
        }
        if($d_begin && $d_end){  //填表时间
            $where['add_time'] = $condition['add_time'] = array('between',"$d_begin,$d_end");
        }
        $ress = Db::name('contract_list')->alias('a')->join('users u','u.user_id=a.user_id')->where($where)->field('a.*,u.nickname')->select();
        $count =count($ress);
        $Page  = new AjaxPage($count,20);
        //  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            if($key == 'add_time'){
                $between_time = explode(',',$val[1]);
                $parameter_add_time = date('Y/m/d',$between_time[0]) . '-' . date('Y/m/d',$between_time[1]);
                $Page->parameter['timegap'] = $parameter_add_time;
            }else{
                $Page->parameter[$key]   =  urlencode($val);
            }
        }
        $show = $Page->show();
        $res = Db::name('contract_list')->alias('a')->join('users u','u.user_id=a.user_id')->where($where)->limit($Page->firstRow,$Page->listRows)->order('id desc')->field('a.*,u.nickname')->select();
        $this->assign('res',$res);
        $this->assign('page',$show);       // 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
    }

    public function contract_list_del(){
        $id = I('id');
        if ($id) {
            $del = Db::name("contract_list")->where('id',$id)->delete();
            if ($del) {
                $this->success('删除成功');
            }else{
                $this->error('删除成功');
            }
        }
    }
    

    /**
     * [add_contract_form 增加合同模板]
     */
    public function add_contract_form(){
        $admic_user = $this->admic_user;
        if (IS_AJAX) {
            $data = I('');
            $p_id = $data['p_id'];
            if (!$p_id) {
                $return_arr = array('status' => -1,'msg'   => '合同类型不可为空',);
                $this->ajaxReturn($return_arr);
            }
            $data['preview_images'] = implode(';',$data['preview_images']);
            if (!$data['preview_images']) {
                $return_arr = array('status' => -1,'msg'   => '预览图片不可为空',);
                $this->ajaxReturn($return_arr);
            }
            $data['company'] =Db::name('contract_template')->where('id',$data['d_id'])->value('name');
            $data['contract_type'] =Db::name('contract_template')->where('id',$data['p_id'])->value('name');
            if($p_id == 8 or $p_id == 14 or $p_id == 20 or $p_id == 26 or $p_id == 32 or $p_id == 38){   //采购表单
                if (!$data['excel_url']) {
                    $return_arr = array('status' => -1,'msg'   => '文件不可为空',);
                    $this->ajaxReturn($return_arr);
                }
                if (!$data['contract_name'] || !$data['company'] || !$data['contract_type'] || !$data['image'] || !$data['describe'] ) {
                    $return_arr = array('status' => -1,'msg'   => '内容不可为空',);
                    $this->ajaxReturn($return_arr);
                }
            }elseif ($p_id == 10 or $p_id == 16 or $p_id == 22 or $p_id == 28 or $p_id == 34 or $p_id == 40) {    //委托生产表单
                if (!$data['product_name'] || !$data['product_type'] || !$data['product_num'] || !$data['product_danwei'] || !$data['product_scope'] || !$data['product_mate'] || !$data['product_need'] ) {
                    $return_arr = array('status' => -1,'msg'   => '代工商品信息不可为空',);
                    $this->ajaxReturn($return_arr);
                }
            }

            if (!empty($data['id'])) {
                $update = Db::name('contract_form_list')->update($data);
            }else{
                $insert = Db::name('contract_form_list')->insert($data);
            }
            if ($insert) {
                $return_arr = array(
                    'status' => 1,
                    'msg'   => '新增成功',
                    'data'  => array('url'=>Url::build('Admin/System/contract_form_list')),
                );
            }elseif($update){
                $return_arr = array(
                    'status' => 1,
                    'msg'   => '修改成功',
                    'data'  => array('url'=>Url::build('Admin/System/contract_form_list')),
                );
            }else{
                $return_arr = array(
                    'status' => -1,
                    'msg'   => '编辑失败',
                );
            }
            $this->ajaxReturn($return_arr);
        }
        $id = $_GET['id'];
        $form_find = Db::name('contract_form_list')->where('id',$id)->find();
        $form_find['preview_images'] = array_filter(explode(';',$form_find['preview_images']));
        $cat_list = Db::name('contract_template')->where('p_id',0)->select();
        $this->assign('admic_user',$admic_user);
        $this->assign('cat_list',$cat_list);
        $this->assign('form_find',$form_find);
        return $this->fetch();
    }
    /*合同类型内容编辑*/
    public function ajax_add_contract_form_1(){
        $p_id = I('p_id');
        if (I('id')) {
            $id = I('id');
            $form_find = Db::name('contract_form_list')->where('id',$id)->find();
            $form_find['preview_images'] = array_filter(explode(';',$form_find['preview_images']));
            $this->assign('form_find',$form_find);
        }
        if($p_id == 8 or $p_id == 14 or $p_id == 20 or $p_id == 26 or $p_id == 32 or $p_id == 38){   //采购表单
            $this->assign('type',2);
        }elseif ($p_id == 10 or $p_id == 16 or $p_id == 22 or $p_id == 28 or $p_id == 34 or $p_id == 40) {    //委托生产表单
            $this->assign('type',4);
        }
        $this->assign('p_id',$p_id);
        return $this->fetch('ajax_add_contract_form_1');
    }
    /*合同公司信息查询*/
    public function ajax_add_contract_form_2(){
        $d_id = I('d_id');
        $corporate = Db::name('contract_corporate')->where('id',$d_id)->find();
        $this->assign('d_id',$d_id);
        $this->assign('corporate',$corporate);
        return $this->fetch('ajax_add_contract_form_2');
    }

    /**
     * [contract_form_list 合同模板设置]
     * @return [type] [description]
     */
    public function contract_form_list(){
        return $this->fetch();
    }
    public function ajaxcontract_form_list(){
        $condition = array();
        // 关键词搜索
        $key_word = I('key_word') ? trim(I('key_word')) : '';
        if($key_word)
        {
            $where .= "contract_name like '%$key_word%'" ;
        }
        $ress = Db::name('contract_form_list')->where($where)->select();
        $count =count($ress);
        $Page  = new AjaxPage($count,20);
        //  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            if($key == 'add_time'){
                $between_time = explode(',',$val[1]);
                $parameter_add_time = date('Y/m/d',$between_time[0]) . '-' . date('Y/m/d',$between_time[1]);
                $Page->parameter['timegap'] = $parameter_add_time;
            }else{
                $Page->parameter[$key]   =  urlencode($val);
            }
        }
        $show = $Page->show();
        $res = Db::name('contract_form_list')->where($where)->limit($Page->firstRow,$Page->listRows)->order('id desc')->select();
        $this->assign('res',$res);
        $this->assign('page',$show);       // 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
    }



    /**
     * [random_draw 抽签代码-口罩抽签]
     * @return [type] [description]
     */
    public function random_draw(){
        echo Db::name('goods')->fetchsql()->where('cat_id',1118)->where('goods_id != 5926')->update(['is_free_shipping'=>0]);
        die;
        if (IS_AJAX) {
            $select = Db::name('goods_consult')->alias('a')->join('users u','u.user_id=a.user_id')->join('goods g','g.goods_id=a.goods_id')->where("make_type != 'one' and make_type != 'two' ")->where(['is_win'=>0])->field('a.*,u.nickname,u.mobile,g.goods_name')->select();
            foreach ($select as $key => $value) {
                $prize_arr = array(
                    '0' => array('id' => 1, 'title' => '口罩中签', 'v' => 50),
                    '1' => array('id' => 2, 'title' => '谢谢，继续加油哦！', 'v' => 50),
                );
                  
                foreach ($prize_arr as $key => $val) {
                    $arr[$val['id']] = $val['v'];
                }
                $prize_id = $this->getRand($arr); //根据概率获取奖品id
                $data['msg'] = ($prize_id == 2) ? 0 : 1; //如果为0则没中 
                $data['prize_title'] = $prize_arr[$prize_id - 1]['title']; //中奖奖品
                $data['user_id'] = $value['user_id']; //中奖ID
                $array[]=$data;
                if ($data['msg'] == 1) {
                    $update = Db::name('goods_consult')->where("make_type != 'one' and make_type != 'two' ")->where(['user_id'=>$value['user_id'],'is_win'=>0])->update(['is_win'=>1]);
                    if ($update) {
                         $da = sendCode($value['mobile'],'恭喜您今天预约的一次性医用口罩10个中签！请点击 http://t.cn/A6zWGZ7m 进入商城，请在24小时内下单完成支付，一礼通温馨提醒您注意安全防护。');
                    }
                    $num[]=$data;
                }else{
                    $update = Db::name('goods_consult')->where("make_type != 'one' and make_type != 'two' ")->where(['user_id'=>$value['user_id'],'is_win'=>0])->update(['is_win'=>2]);
                    $nums[]=$data;
                }
            }
        }
        if ($num || $nums) {
            return array('status'=>'1','msg'=>'抽签成功，中签:'.count($num).'个,未中签:'.count($nums).'个,中签短信已发送');
        }else if( $num && $da['msg']!="发送成功"){
            return array('status'=>'-1','msg'=>'短信发送失败');
        }else{
            return array('status'=>'-1','msg'=>'抽签失败,没有可抽签的记录');
        }
    }

    public function getRand($proArr) { //计算中奖概率
        $rs = ''; //z中奖结果
        $proSum = array_sum($proArr); //概率数组的总概率精度
        //概率数组循环
        foreach ($proArr as $key => $proCur) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $proCur) {
                $rs = $key;
                break;
            } else {
                $proSum -= $proCur;
            }
        }
        unset($proArr);
        return $rs;
    }
    

}