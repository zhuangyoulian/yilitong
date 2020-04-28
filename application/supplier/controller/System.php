<?php

namespace ylt\supplier\controller;
use think\db;
use think\Url;
use think\Request;
class System extends Base{
	
	
	
	/*
	 * 配置入口
	 */
	public function index()
	{          
		/*配置列表*/
		$supplier_id = session('supplier_id');
		$group_list = array('shop_info'=>'商店信息','basic'=>'基本设置','shopping'=>'购物流程设置');		
		$this->assign('group_list',$group_list);
		$inc_type =  I('get.inc_type','shop_info');
		$this->assign('inc_type',$inc_type);
		$config = supplierCache($supplier_id,$inc_type);
		if($inc_type == 'shop_info'){
			if(empty($config)){
				$config = array('province'=>'28240');
			}
			/*$province = Db::name('region')->where(array('parent_id'=>0))->select();
			$city =  Db::name('region')->where(array('parent_id'=>$config['province']))->select();
			$area =  Db::name('region')->where(array('parent_id'=>$config['city']))->select();
			$this->assign('province',$province);
			$this->assign('city',$city);
			$this->assign('area',$area);*/
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
        // dump($param);die;
		$supplier_id = session('supplier_id');
		$inc_type = $param['inc_type'];
		//unset($param['__hash__']);
		unset($param['inc_type']);
		supplierCache($supplier_id,$inc_type,$param);
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
        $model = D("Navigation");
        if (IS_POST) {
            if (I('id')){
                $model->update(I('post.'));
            }else{
                $model->add(I('post.'));
            }
            $this->success("操作成功!!!", Url::build('Supplier/System/navigationList'));
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

			delFile(RUNTIME_PATH);
            $this->success("操作完成!!!",Url::build('Supplier/Admin/index'));
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
                    strstr($val,"home_goods_ajaxcomment_{$goods_id}") && unlink($val); // 商品评论缓存
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
            delFile(UPLOAD_PATH."goods/thumb/".$goods_id); // 删除缩略图
			//rmdir(UPLOAD_PATH."goods/thumb/".$goods_id);	//删除文件夹
            $json_arr = array('status'=>1,'msg'=>'清除成功,请清除对应的静态页面','result'=>'');
            $json_str = json_encode($json_arr);            
            exit($json_str);            
      } 
	  
    /**
     * 清空 文章静态页面缓存
     */
      public function ClearAritcleHtml(){
            $article_id = I('article_id');
            unlink("./runtime/html/index_article_detail_{$article_id}.html"); // 清除文章静态缓存
            unlink("./runtime/html/doc_index_article_{$article_id}_api.html"); // 清除文章静态缓存
            unlink("./runtime/html/doc_index_article_{$article_id}_phper.html"); // 清除文章静态缓存
            unlink("./runtime/html/doc_index_article_{$article_id}_android.html"); // 清除文章静态缓存
            unlink("./runtime/html/doc_index_article_{$article_id}_ios.html"); // 清除文章静态缓存
            $json_arr = array('status'=>1,'msg'=>'操作完成','result'=>'' );                                                          
            $json_str = json_encode($json_arr);            
            exit($json_str);            
      }
      
	//发送测试邮件
	public function send_email(){
		$param = I('post.');
		tpCache($param['inc_type'],$param);
		if(send_email($param['test_eamil'],'后台验证','验证码:'.mt_rand(1000,9999))){
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
        //Db::name("cart")->where("user_id = 0 and  add_time < $time")->delete();

     }
     
     function ajax_get_action()
     {
     	$control = I('controller');
     	$advContrl = get_class_methods("ylt\\supplier\\controller\\".str_replace('.php','',$control));
     	//dump($advContrl);
     	$baseContrl = get_class_methods('ylt\supplier\controller\Base');
     	$diffArray  = array_diff($advContrl,$baseContrl);
     	$html = '';
     	foreach ($diffArray as $val){
     		$html .= "<option value='".$val."'>".$val."</option>";
     	}
     	exit($html);
     }
     
     function right_list(){
     	$group = array('system'=>'系统设置','content'=>'内容管理','goods'=>'商品中心','member'=>'会员中心',
     			'order'=>'订单中心','marketing'=>'营销推广','tools'=>'插件工具','count'=>'统计报表'
     	);
     	$right_list = Db::name('supplier_menu')->select();
     	$this->assign('right_list',$right_list);
     	$this->assign('group',$group);
     	return $this->fetch();
     }
     
     public function edit_right(){
     	if(IS_POST){
     		$data = I('post.');
     		$data['right'] = implode(',',$data['right']);
     		if(!empty($data['id'])){
     			Db::name('supplier_menu')->where(array('id'=>$data['id']))->update($data);
     		}else{
     			if(Db::name('supplier_menu')->where(array('name'=>$data['name']))->count()>0){
     				$this->error('该权限名称已添加，请检查',Url::build('System/right_list'));
     			}
     			unset($data['id']);
     			Db::name('supplier_menu')->insert($data);
     		}
     		$this->success('操作成功',Url::build('System/right_list'));
     		exit;
     	}
     	$id = I('id');
     	if($id){
     		$info = Db::name('supplier_menu')->where(array('id'=>$id))->find();
     		$info['right'] = explode(',', $info['right']);
     		$this->assign('info',$info);
     	}
     	$group = array('system'=>'系统设置','content'=>'内容管理','goods'=>'商品中心',
     			'order'=>'订单中心','marketing'=>'营销推广','count'=>'统计报表'
     	);
     	$planPath = APP_PATH.'supplier/controller';
     	$planList = array();
     	$dirRes   = opendir($planPath);
     	while($dir = readdir($dirRes))
     	{
     		if(!in_array($dir,array('.','..','.svn')))
     		{
     			$planList[] = basename($dir,'.class.php');
     		}
     	}
     	$this->assign('planList',$planList);
     	$this->assign('group',$group);
        return $this->fetch();
     }
     
     public function right_del(){
     	$id = I('del_id');
     	if(is_array($id)){
     		$id = implode(',', $id); 
     	}
     	if(!empty($id)){
     		$r = Db::name('supplier_menu')->where("id in ($id)")->delete();
     		if($r){
     			respose(1);
     		}else{
     			respose('删除失败');
     		}
     	}else{
     		respose('参数有误');
     	}
     }
	 
	 /**
	 * 入驻信息
	 */
	 
	 public function supplier_info(){
		 
		$info = Db::name('supplier')->where('supplier_id',session('supplier_id'))->find();
		$region_list = get_region_list();
        $this->assign('region_list',$region_list);
		$this->assign('su',$info);
     	return $this->fetch();
	 }
	 
	 
	 
	
}