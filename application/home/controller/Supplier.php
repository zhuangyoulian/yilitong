<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/26
 * Time: 15:04
 */
namespace ylt\home\controller;
use think\Controller;
use think\Url;
use think\Config;
use think\Page;
use think\Verify;
use think\Db;
use think\Request;
use think\Cache;

class Supplier extends Base {
	
	public function _initialize() {
		parent::_initialize();
		$id = I('post.supplier_id');
      	if($id){
		 $info = Db::name('supplier')->field('supplier_id,supplier_name,introduction,business_sphere,supplier_money,add_time,contacts_name,contacts_phone,province,city,area,address,operating_name')->where('supplier_id = '.$id.' and is_designer = 0 and status = 1')->cache(true,600)->find();
		 if(!$info){
	        exit(json_encode(array('status'=>'-1','info'=>'该店铺不存在或已关闭！')));  
		 }
		 $config_info = Db::name('supplier_config')->where('supplier_id',$id)->cache(true,600)->column('name,value');
      	}
      	 $info['contacts_phone'] = substr_replace($info['contacts_phone'],'****',3,4);
		 $this->info = $info;
		 $this->config_info = $config_info;
	}

    public function index(){
        $rs=array('status'=>'1','info'=>'请求成功','config_info'=>$this->config_info);
        exit(json_encode($rs));  
    }


    /**
     * @function businessStoreHome() //商家店铺-首页
     * @return mixed
     */
     public function StoreHome(){
		 $id = I('post.supplier_id');
         $field = "goods_id,goods_name,goods_thumb,shop_price";
		 $recommend = Db::name('goods')->where('is_recommend = 1 and supplier_id = '.$id.' and is_on_sale = 1 and examine = 1')->field($field)->order('goods_id desc')->limit(0,16)->cache(true,600)->select();
		 if(!$recommend){
			 $recommend = Db::name('goods')->where('supplier_id = '.$id.' and is_on_sale = 1 and examine = 1')->limit(0,16)->field($field)->order('goods_id desc')->cache(true,600)->select();
		 }
		 foreach ($recommend as $key => $value) {
        	$goodsLogic = new \ylt\home\logic\GoodsLogic(); // 前台商品操作逻辑类
		 	$goods_spec = $goodsLogic->get_spec_s($value['goods_id']);
            $value['goods_spec']=$goods_spec;
            $recommends[] = $value;
		 }
        // Db::name('goods')->getLastSql();

        $rs=array('status'=>'1','info'=>'请求成功','recommend'=>$recommends);
        exit(json_encode($rs));  
     }

     /**
      * @function businessStoreCategory() //商家店铺-全部商品
      * @return mixed
      */
      public function StoreCategory(){
		   $id = I('post.supplier_id'); //店铺ID
		   $filter_param = array();
		   
		   $filter_param['id'] = $id;  
		   $where['supplier_id'] = $id; //加入筛选条件中 
		   $where['examine'] = 1;
		   $where['is_on_sale'] = 1;
           $sort = I('post.sort','goods_id'); // 排序
           $sort_asc = I('post.sort_asc','desc'); // 排序
           $sup_cat_id=I('extend_cat_id','');//店铺分类
           if($sup_cat_id){
              $where['sup_cat_id'] = $sup_cat_id;
           }
		   $where = array_merge($where,$filter_param);
		   unset($where['id']);
	   
		   $count = Db::name('goods')->where($where)->count();
		   $page = new Page($count,24);
        
         	$feild = "goods_id,goods_name,goods_thumb,shop_price";
		    $category = Db::name('supplier_goods_category')->where('supplier_id',$id)->cache(true,600)->select();
		    $goods_list = Db::name('goods')->where($where)->field($feild)->order("$sort $sort_asc")->limit($page->firstRow.','.$page->listRows)->cache(true,600)->select();
	
			foreach ($goods_list as $key => $value) {
	        	$goodsLogic = new \ylt\home\logic\GoodsLogic(); // 前台商品操作逻辑类
			 	$goods_spec = $goodsLogic->get_spec_s($value['goods_id']);
	            $value['goods_spec']=$goods_spec;
	            $recommends[] = $value;
			}

        	$rs=array('status'=>'1','info'=>'请求成功','goods_list'=>$recommends,'filter_param'=>$filter_param,'category'=>$category,'page'=>$page);
        	exit(json_encode($rs));  
      }

      /**
        * @function businessStoreInfos() //商家店铺-店铺简介
        * @return mixed
        */
        public function StoreInfos(){
			$id = I('supplier_id/d',41);
			//查询是否已关注
			$collect = Db::name('supplier_collect')->where(['user_id'=>I('user_id'),'supplier_id'=>I('supplier_id/d',41)])->find();

			$region_list = get_region_list();
            if (!empty($region_list[$this->info['province']]['name']) && !empty($region_list[$this->info['city']]['name'])) {
				$address = $region_list[$this->info['province']]['name'] . $region_list[$this->info['city']]['name'] . $region_list[$this->info['area']]['name'] . $this->info['address'];
            }else{
				$address = $this->info['province'] . $this->info['city'] . $this->info['area'] . $this->info['address'];
            }
			$info = $this->info;
			$config_info = $this->config_info;
			$info['store_logo'] = $config_info['store_logo'];
			$info['address'] = $address;
        	$rs=array('status'=>'1','config_info'=>$info,'collect'=>$collect);
        	exit(json_encode($rs));  
        }
		
		
		/**
        * @function collect_stores() //店铺收藏
        * @return mixed
        */
		public function collect_stores(){
			$id = I('supplier_id');
				
			if(empty(I('user_id'))){
				exit(json_encode(['status'=>-1,'msg'=>'请先登录！']));
			}
			
			$add['user_id'] = I('user_id');
			$add['supplier_id'] = $id;
			if(Db::name('supplier_collect')->where($add)->find()){
				exit(json_encode(['status'=>-1,'msg'=>'已收藏！']));
			}
			
			$add['add_time'] = time();
			Db::name('supplier_collect')->insert($add);
			
			exit(json_encode(['status'=>1,'msg'=>'收藏成功！']));
			
		}

		public function search(){
			$sort = I('post.sort','add_time'); // 排序
			$sort_asc = I('post.sort_asc','desc'); // 排序
            $company_name=I('keywords'); //公司名称

          	$supplierCount=Db::name('supplier')->where('is_designer=0 and status=1')->where('company_name','like','%'.$company_name.'%')->count();
			$page = new Page($supplierCount,12);
			$supplier_id = Db::name('supplier')->field('supplier_id')->where('is_designer=0 and status=1')->order('add_time desc')->cache(true,3600)->select();//获取所有供应商id
			$arr=[];//将供应商表里所有供应商id装在这里
			foreach($supplier_id as $value){
				foreach($value as $key){
					$arr[]=$key;
				}
			}
			$haveShopSupplier=Db::name('goods')//查询所有有商品的供应商
			->field('supplier_id')
				->where("supplier_id in (".implode(',',$arr).") and is_on_sale=1 and examine=1 and is_hot=1")
				->group('supplier_id')
				->select();
			$supplierArr=[];//将商品表所有有商品的供应商id装在这里
			foreach($haveShopSupplier as $value){
				foreach($value as $key){
					$supplierArr[]=$key;
				}
			}
			if($supplierCount > 0)
			{
              $supplier_newList = Db::name('supplier')->where('is_designer=0 and status=1')->where('company_name','like','%'.$company_name.'%')->order("$sort $sort_asc")->limit($page->firstRow.','.$page->listRows)->select();
            }

        	$rs=array('status'=>'1','info'=>'请求成功','supplier_newList'=>$supplier_newList,'supplierCount'=>$supplierCount,'page'=>$page);
        	exit(json_encode($rs));  

        }

    
     


}