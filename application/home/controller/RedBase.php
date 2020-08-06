<?php
namespace ylt\home\controller;
use think\Controller;
use think\Db;
use think\response\Json;
use think\Session;
use think\Cookie;
use think\Url;

class RedBase extends Controller {
    public $session_id;
    public $cateTrre = array();
    /*
     * 初始化操作
     */
    public function _initialize() {
        Session::start();
        header("Cache-control: private");  // history.back返回后输入框值丢失问题 
    	$this->session_id = session_id(); // 当前的 session_id
        define('SESSION_ID',$this->session_id); //将当前的session_id保存为常量，供其它方法调用
		// // 判断当前用户是否手机                
  //       if(isMobile())
  //           cookie('is_mobile','1',3600); 
  //       else 
  //           cookie('is_mobile','0',3600);
		
		//   // 如果是手机跳转到 手机模块
  //       if(isMobile() == true){
  //           header("Location: ".Url::build('Mobile/Index/index'));
  //           exit;
  //       }
        

        $this->public_assign(); 
		//$this->doCookieArea();
    }
    /**
     * 保存公告变量到 smarty中  
     */
    public function public_assign()
    {
        
        $config = array();
        $tp_config = Db::name('config')->cache(true,YLT_CACHE_TIME)->select();
        foreach($tp_config as $k => $v)
        {
       	    if($v['name'] == 'hot_keywords'){
       	  	    $config['hot_keywords'] = explode('|', $v['value']);
       	    }       	  
            $config[$v['inc_type'].'_'.$v['name']] = $v['value'];
        }                        
        //商品分类
        $this->goods_category_tree = get_goods_category_tree();    
       
      	//场景分类
        $this->scenario_category_tree = get_scenario_category_tree();
        

        $this->brand_list = Db::name('brand')->cache(true,YLT_CACHE_TIME)->field('id,parent_cat_id,logo,is_hot')->where("parent_cat_id>0")->select();
      
        $this->search_url=array(
                        array('name'=>'礼品','url'=>'Home/Goods/search','k'=>1),
                        array('name'=>'供应商','url'=>'Home/Supplier/search','k'=>2),
                        array('name'=>'采购','url'=>'Home/Article/search','k'=>3)
                        );
        $this->controller=request()->controller(); 

    }
    /*
     * 
     */
    public function ajaxReturn($data){                        
            exit(json_encode($data));
    }
	
	 /**
     * 获取的地区来设置地区缓存
     */
    private function doCookieArea()
    {

        $cookie_province_id = Cookie::get('province_id');
        $cookie_city_id = Cookie::get('city_id');
        $cookie_district_id = Cookie::get('district_id');
        if(empty($cookie_province_id) || empty($cookie_city_id) || empty($cookie_district_id)){
            $address = GetIpLookup();
            if(empty($address['province'])){
                $this->setCookieArea();
                return;
            }
            $province_id = Db::name('region')->where(['level' => 1, 'name' => ['like', '%' . $address['province'] . '%']])->limit('1')->value('id');
            if(empty($province_id)){
                $this->setCookieArea();
                return;
            }
            if (empty($address['city'])) {
                $city_id = Db::name('region')->where(['level' => 2, 'parent_id' => $province_id])->limit('1')->order('id')->value('id');
            } else {
                $city_id = Db::name('region')->where(['level' => 2, 'parent_id' => $province_id, 'name' => ['like', '%' . $address['city'] . '%']])->limit('1')->value('id');
            }
            if (empty($address['district'])) {
                $district_id = Db::name('region')->where(['level' => 3, 'parent_id' => $city_id])->limit('1')->order('id')->value('id');
            } else {
                $district_id = Db::name('region')->where(['level' => 3, 'parent_id' => $city_id, 'name' => ['like', '%' . $address['district'] . '%']])->limit('1')->value('id');
            }
            $this->setCookieArea($province_id, $city_id, $district_id);
        }
    }

    /**
     * 设置地区缓存
     * @param $province_id
     * @param $city_id
     * @param $district_id
     */
    private function setCookieArea($province_id = 1, $city_id = 2, $district_id = 3)
    {
        Cookie::set('province_id', $province_id);
        Cookie::set('city_id', $city_id);
        Cookie::set('district_id', $district_id);
    }
    
  
}