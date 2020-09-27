<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/4
 * Time: 14:23
 */
namespace ylt\mobile\controller;
use ylt\mobile\logic\GoodsLogic;
use ylt\home\logic\SupplierLogic;
use ylt\home\model\UsersLogic;
use think\Db;
use think\Url;

class Supplier extends MobileBase {

    /**
     * 店铺首页
     */
    public function index(){
		
		 $id = I('get.id/d',41);
		 
		 $config = Db::name('supplier_config')->where('supplier_id',$id)->cache(true,600)->column('name,value'); //店铺设置
		 $logo_img = Db::name('supplier_recommend')->where('supplier_id',$id)->field('logo_img')->value('logo_img'); //精选店铺LOGO
		 $category = Db::name('supplier_goods_category')->where('supplier_id',$id)->cache(true,600)->select(); // 店铺分类
		 $info = Db::name('supplier')->field('supplier_id,supplier_name,introduction,supplier_money,add_time,phone_number,business_sphere,province,city,area,address,contacts_phone,logo')->where('supplier_id = '.$id.' and is_designer = 0 and status = 1')->cache(true,600)->find(); //入驻信息
		 
		 $region_list = get_region_list(); //地址列表
		 $address = $region_list[$info['province']]['name'] . $region_list[$info['city']]['name'] . $region_list[$info['area']]['name'] . $info['address'];
		 
		 
		 $this->assign('info',$info);
		 $this->assign('logo_img',$logo_img);
		 $this->assign('address',$address);
		 $this->assign('category',$category); // 分类
		 $this->assign('config_info',$config);
    
        return $this->fetch();
    }
  
  public function activity(){
     return $this->fetch();
  }

    /**
     * 店铺产品分类列表页
     */
    public function goodsList(){
		$id = I('get.id/d',41);
		
		
		$category = Db::name('supplier_goods_category')->where('supplier_id',$id)->cache(true,600)->select(); // 店铺分类
		$this->assign('category',$category); // 分类
        return $this->fetch();
    }

    /**
     * 店铺关注
     */
    public function likeStore(){

        return $this->fetch();
    }
	
	 public function ajaxGetMore(){
		
    	$p = I('p/d',1);
		$id = I('get.id/d',41);
		$cat = I('cat');
		if($cat){
			$where = "extend_cat_id = ".$cat." and is_on_sale=1 and examine=1";
		}else{
			$where = "supplier_id = ".$id." and is_on_sale=1 and examine=1";
		}
		
    	$favourite_goods = Db::name('goods')->where($where)->order('add_time desc')->page($p,config('PAGESIZE'))->cache(true,YLT_CACHE_TIME)->select();//首页推荐商品
		if(!$favourite_goods)
			$favourite_goods = Db::name('goods')->where('supplier_id = '.$id.' and is_on_sale=1 and examine=1')->order('sort asc')->page($p,config('PAGESIZE'))->cache(true,YLT_CACHE_TIME)->select();//首页推荐商品
		
    	$this->assign('favourite_goods',$favourite_goods);
    	return $this->fetch();
    }
  
  	public function ajaxGetActivity(){
    	$p = I('p/d',1);
      	$cat=I('cat/d',1038);
      	$where = "cat_id = ".$cat." and is_on_sale=1 and examine=1";
      	$favourite_goods = Db::name('goods')->where($where)->order('sort asc')->page($p,config('PAGESIZE'))->select();//首页推荐商品
    	$this->assign('favourite_goods',$favourite_goods);
    	return $this->fetch();
    
    }	
		/**
        * @function collect_stores() //店铺收藏
        * @return mixed
        */
		public function collect_stores(){
			$id = I('supplier_id');
				
			if(!cookie('user_id'))
				exit(json_encode(['status'=>-1,'msg'=>'请先登录！']));
			
			$add['user_id'] = cookie('user_id');
			$add['supplier_id'] = $id;
			if(Db::name('supplier_collect')->where($add)->find())
				exit(json_encode(['status'=>-1,'msg'=>'已关注！']));
			
			$add['add_time'] = time();
			Db::name('supplier_collect')->insert($add);
			
			exit(json_encode(['status'=>1,'msg'=>'关注成功！']));
			
		}
		
		
    /**
    *  商家注册
    */
    public function reg(){
		
		if(IS_POST){
    			$mobile = trim(I('post.mobile',''));
    			$password = trim(I('post.password',''));
    			$password2 = $password;
				$user_name = trim(I('post.user_name',''));
				$company_name = trim(I('post.company_name'));
				$parent_id = trim(I('post.parent_id',''));
    			$code = trim(I('post.code',''));
    			$session_id = session_id();
    			$verify_code = trim(I('post.verify_code'));
    			
    			
    			$userlogic = new UsersLogic();
    			$res = $userlogic->check_validate_code($code, $mobile , $session_id , 'mobile');
    			if ($res['status'] != 1){
    				$this->error($res['msg']);
    			}
				
		
    			$supLogin=new SupplierLogic();
    			$data = $supLogin->reg($mobile,$password,$password2,$user_name,$company_name,$parent_id);
    			
    			if($data['status'] != 1){

					exit(json_encode(['status'=>-1,'msg'=>$data['msg']]));
    			}


    			exit(json_encode(['status'=>1,'msg'=>'注册成功！']));
    			exit;
    		
    		
    	}
        return $this->fetch();
    }


    /**
     * 快速注册成功
     */
    public function reg_success(){

        return $this->fetch();
    }

	
    /**
     * 店铺搜索列表
     */
    public function supplierList(){
		
		$q = urldecode(trim(I('q',''))); // 关键字搜索

		$where  = "status = 1 AND is_designer = 0";
		$keywords = $q;
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
				
				$where .= " (`company_name` LIKE '%".$val."%' OR `supplier_name` LIKE '%".$val."%' )";
				
			}
			
			$where .= ')';

		}else{
			
			$where .= " AND (`company_name` LIKE '%".$keywords."%' OR `supplier_name` LIKE '%".$keywords."%'  )";
		}
		
		$field = "supplier_id,supplier_name,introduction,logo";
		$supplier_list = Db::name('supplier')->field($field)->where($where)->limit(20)->select();
		
		$this->assign('supplier_list',$supplier_list);
        return $this->fetch();
    }
	
		
	 /**
     * 推荐店铺列表
     */
    public function recsupplier(){
        return $this->fetch();
    }

    /**
     * 店铺精选加载
     */
    public function ajax_recsupplier_list(){
        $p = I('p/d',1);
        $Recommend =  DB::name('supplier_recommend');
        $list = $Recommend->where('is_show',1)->Cache(true,YLT_CACHE_TIME)->order(['number'=>'DESC','add_time'=>'DESC'])->page($p,config('PAGESIZE'))->select();
        $this->assign('list',$list);
        return $this->fetch();

    }



}