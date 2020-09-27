<?php
/**
 * Created by PhpStorm.
 * User: lijiayi
 * Date: 2017/3/24
 * Time: 14:45
 */
namespace ylt\home\controller; 
use think\Controller;
use think\Url;
use think\Config;
use think\Db;
class Caseindex extends controller {
	
	
	 public function _initialize() {
		 
		 $config = array();
       $tp_config = Db::name('config')->cache(true,YLT_CACHE_TIME)->select();
       foreach($tp_config as $k => $v)
       {
       	  if($v['name'] == 'hot_keywords'){
       	  	 $config['hot_keywords'] = explode('|', $v['value']);
       	  }       	  
          $config[$v['inc_type'].'_'.$v['name']] = $v['value'];
       }                        
       
       $goods_category_tree = get_goods_category_tree();    
       $this->cateTrre = $goods_category_tree;
       $this->assign('goods_category_tree', $goods_category_tree);                     
       $brand_list = Db::name('brand')->cache(true,YLT_CACHE_TIME)->field('id,parent_cat_id,logo,is_hot')->where("parent_cat_id>0")->select();
       $this->assign('brand_list', $brand_list);
       $this->assign('config', $config);

    }

	
	 public function wanke(){
       
        return $this->fetch();
    }
	
	
	 public function pinan(){
       
        return $this->fetch();
    }
	
	
	 public function muwu(){
      
        return $this->fetch();
    }
	
	 public function cuntian(){
       
        return $this->fetch();
    }
	
	 public function event_draw(){
       
        return $this->fetch();
    }
	
	 public function apply_details(){
       
        return $this->fetch();
    }
	
	public function h5Link(){
       
        return $this->fetch();
    }
	
	public function recruitBusiness(){
       
        return $this->fetch();
    }


    public function newShare(){

        return $this->fetch();
    }


    public function newShareComfirm(){

        return $this->fetch();
    }

	
	/**
	 * Android下载页面
	 */
	public function download(){
		visit_stats();
        $this->assign('extension_id',I('id'));// 下载推荐链接
		
        return $this->fetch();
    }
	
	
	/**
	 * 下载统计
	 */
	public function extension_pc(){
		visit_stats();

       
	 $extension_id =  I('extension_id'); //推荐人ID
	 $system = I('system');
	 
	 $add['download_ip'] = getIP();
	 $add['add_time'] 	 = time();		
	 $add['system'] 	 = $system;
	 $add['browser']  	 = $_SERVER['HTTP_USER_AGENT'];
	 
	 if(DB::name('extension')->where('download_ip',getIP())->count() == 0){
		 //检测当前ID是否已下载过
		 if($extension_id){
			 $add['extension_id'] = $extension_id;
			Db::name('extension')->insert($add);
			
		 }else{
			$add['extension_id'] = 1;
			Db::name('extension')->insert($add);
			
		 }
	 }
	  exit(json_encode(1));
	    
        
    }
	
	

	

	
	/**
	 * 渠道下载统计
	 */
	public function extension(){
		
		if(empty(I('str'))){
		 $key = rand(100000,999999).time();
		 $str =  base64_encode($key);
		 $add['key']  = substr($key , 2 , 14); 
		 $add['time'] = time();
		 $add['ip']   = getIP();
		Db::name('app_activation')->insert($add);
		
	  exit(json_encode(array('key'=>$str),true));
	  
		}
	

		 visit_stats();
				
		 $str = base64_decode(I('str')); //输出解码后的内容
		   
		 $system = I('system');
		 
		 $add['download_ip'] = getIP();
		 $add['add_time'] 	 = time();		
		 $add['system'] 	 = $system;
		 $add['browser']  	 = $_SERVER['HTTP_USER_AGENT'];
		 
		 if(Db::name('app_activation')->where('key',$str)->count() != 0){
			 $extension_id = I('extension_id'); //推荐人ID
			 Db::name('app_activation')->where('key',$str)->delete();
		 }else{
			 $extension_id = 1 ;
			 Db::name('app_activation')->where('time > '.(time()-180).'')->delete();
		 }
		 
		// if(DB::name('extension')->where('download_ip',getIP())->count() == 0){
			 //检测当前ID是否已下载过
				 $add['extension_id'] = $extension_id;
				$id = Db::name('extension')->insertGetId($add);
		// }
		 
		   exit(json_encode(array('id'=>$id)));
		 
			
		}




	/**
     * 中秋活动页面
     */
	public function mid_autumn(){

        return $this->fetch();
    }


    /**
     * 中秋活动页面活动规则
     */
    public function mid_autumn_rules(){

        return $this->fetch();
    }



    /**
     * 月中活动页面
     */
    public function mid_month(){

        return $this->fetch();
    }



    /**
     * 满减活动页面
     */
    public function full_cut(){

        return $this->fetch();
    }


    /**
     * 城市代理人
     */
    public function jiameng(){

        return $this->fetch();
    }


	
}