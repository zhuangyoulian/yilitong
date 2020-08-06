<?php
/**
 * Created by PhpStorm.
 * User: jiayi
 * Date: 2017/4/5
 * Time: 19:51
 */
namespace ylt\home\controller;
use ylt\home\logic\GoodsLogic;
use think\AjaxPage;
use think\Controller;
use think\Url;
use think\Config;
use think\Page;
use think\Db;
use think\Request;
use think\Cache;
class Goods extends Base {
	
    public function index(){

        return $this->fetch();
    }
	
  
	/**
     * 商品列表页
     */
    public function goodsList(){ 
        $key = md5($_SERVER['REQUEST_URI'].I('start_price').'_'.I('end_price'));
        $html =  Cache::get($key);  //读取缓存
        if(!empty($html))
        {
            return $html;
        }
        
        $filter_param = array(); // 筛选数组                        
        $id = I('get.id/d',1); // 当前分类id
        $brand_id = I('get.brand_id',0);
        $spec = I('get.spec',0); // 规格
        $attr = I('get.attr',''); // 属性
        $sort = I('get.sort','sort'); // 排序字段
        $sort_asc = I('get.sort_asc','desc'); // 排序方式
        
        $price = I('get.price',''); // 价钱
        $start_price = trim(I('post.start_price','0')); // 输入框价钱
        $end_price = trim(I('post.end_price','0')); // 输入框价钱
        if($start_price && $end_price) $price = $start_price.'-'.$end_price; // 如果输入框有价钱 则使用输入框的价钱
        
        $filter_param['id'] = $id; //加入筛选条件中                       
        $brand_id  && ($filter_param['brand_id'] = $brand_id); //加入筛选条件中
        $spec  && ($filter_param['spec'] = $spec); //加入筛选条件中
        $attr  && ($filter_param['attr'] = $attr); //加入筛选条件中
        $price  && ($filter_param['price'] = $price); //加入筛选条件中 
        
        $goodsLogic = new GoodsLogic(); // 前台商品操作逻辑类
        
        // 分类菜单显示
        $goodsCate = Db::name('GoodsCategory')->where("id", $id)->find();// 当前分类
        $cateArr = $goodsLogic->get_goods_cate($goodsCate);

        
        // 筛选 品牌 规格 属性 价格
        $cat_id_arr = getCatGrandson ($id);
        $filter_goods_id = Db::name('goods')->where(['is_on_sale'=>1,'examine'=>1,'is_designer'=>0,'is_delete'=>0,'cat_id'=>['in',implode(',', $cat_id_arr)]])->cache(true)->column("goods_id");
        // 过滤筛选的结果集里面找商品        
        if($brand_id || $price)// 品牌或者价格
        {
            $goods_id_1 = $goodsLogic->getGoodsIdByBrandPrice($brand_id,$price); // 根据 品牌 或者 价格范围 查找所有商品id    
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_1); // 获取多个筛选条件的结果 的交集
        }
        if($spec)// 规格
        {
            $goods_id_2 = $goodsLogic->getGoodsIdBySpec($spec); // 根据 规格 查找当所有商品id
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_2); // 获取多个筛选条件的结果 的交集
        }
        if($attr)// 属性
        {
            $goods_id_3 = $goodsLogic->getGoodsIdByAttr($attr); // 根据 规格 查找当所有商品id
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_3); // 获取多个筛选条件的结果 的交集
        }        
           
        $filter_menu  = $goodsLogic->get_filter_menu($filter_param,'goodsList'); // 获取显示的筛选菜单
        $filter_price = $goodsLogic->get_filter_price($filter_goods_id,$filter_param,'goodsList'); // 筛选的价格期间         
        $filter_brand = $goodsLogic->get_filter_brand($filter_goods_id,$filter_param,'goodsList',1); // 获取指定分类下的筛选品牌        

                                
        $count = count($filter_goods_id);
        $page = new Page($count,12);
        if($count > 0)
        {
            $field = "goods_id,cat_id,goods_name,goods_thumb,shop_price,market_price,goods_remark,comment_count";
            $goods_list = Db::name('goods')->where("goods_id","in", implode(',', $filter_goods_id))->order("$sort $sort_asc")->field($field)->limit($page->firstRow.','.$page->listRows)->select();
            $filter_goods_id2 = get_arr_column($goods_list, 'goods_id');
        }
        
        $goods_category = Db::name('goods_category')->where('is_show=1')->cache(true)->column('id,name,parent_id,level,title,keywords,description'); // 键值分类数组

        $navigate_cat = navigate_goods($id); // 面包屑导航   

        $rs=array('status'=>'1','info'=>'请求成功','goods_list'=>$goods_list,'navigate_cat'=>$navigate_cat,'goods_category'=>$goods_category,'filter_menu'=>$filter_menu,'filter_brand'=>$filter_brand,'filter_spec'=>'','filter_price'=>$filter_price,'goodsCate'=>$goodsCate,'cateArr'=>$cateArr,'filter_param'=>$filter_param,'cat_id'=>$id,'page'=>$page,'goods_category_tree'=>$this->goods_category_tree,'scenario_category_tree'=>$this->scenario_category_tree);
        exit(json_encode($rs));
    }
  
  	/**
     * 首页品牌内页展示列表页
     */
    public function brandList(){

        $key = md5($_SERVER['REQUEST_URI'].I('start_price').'_'.I('end_price'));
        $html =  Cache::get($key);  //读取缓存
        if(!empty($html))
        {
            return $html;
        }

        $filter_param = array(); // 筛选数组
        $id = I('get.id/d',1); // 当前分类id
        $brand_id = I('get.brand_id',0);
        $spec = I('get.spec',0); // 规格
        $attr = I('get.attr',''); // 属性
        $sort = I('get.sort','goods_id'); // 排序
        $sort_asc = I('get.sort_asc','desc'); // 排序

        $price = I('get.price',''); // 价钱
        $start_price = trim(I('post.start_price','0')); // 输入框价钱
        $end_price = trim(I('post.end_price','0')); // 输入框价钱
        if($start_price && $end_price) $price = $start_price.'-'.$end_price; // 如果输入框有价钱 则使用输入框的价钱

        $filter_param['id'] = $id; //加入筛选条件中
        $brand_id  && ($filter_param['brand_id'] = $brand_id); //加入筛选条件中
        $spec  && ($filter_param['spec'] = $spec); //加入筛选条件中
        $attr  && ($filter_param['attr'] = $attr); //加入筛选条件中
        $price  && ($filter_param['price'] = $price); //加入筛选条件中

        $goodsLogic = new GoodsLogic(); // 前台商品操作逻辑类

        // 分类菜单显示
        $goodsCate = Db::name('GoodsCategory')->where("id", $id)->find();// 当前分类
        $cateArr = $goodsLogic->get_goods_cate($goodsCate);


        // 筛选 品牌 规格 属性 价格
        $cat_id_arr = getCatGrandson ($id);

        $filter_goods_id = Db::name('goods')->where(['is_on_sale'=>1,'examine'=>1,'is_designer'=>0,'is_delete'=>0,'brand_id'=>$brand_id])->cache(true)->column("goods_id");

        // 过滤筛选的结果集里面找商品
        if($brand_id || $price)// 品牌或者价格
        {
            $goods_id_1 = $goodsLogic->getGoodsIdByBrandPrice($brand_id,$price); // 根据 品牌 或者 价格范围 查找所有商品id
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_1); // 获取多个筛选条件的结果 的交集
        }
        if($spec)// 规格
        {
            $goods_id_2 = $goodsLogic->getGoodsIdBySpec($spec); // 根据 规格 查找当所有商品id
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_2); // 获取多个筛选条件的结果 的交集
        }
        if($attr)// 属性
        {
            $goods_id_3 = $goodsLogic->getGoodsIdByAttr($attr); // 根据 规格 查找当所有商品id
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_3); // 获取多个筛选条件的结果 的交集
        }

        $filter_menu  = $goodsLogic->get_filter_menu($filter_param,'goodsList'); // 获取显示的筛选菜单
        $filter_price = $goodsLogic->get_filter_price($filter_goods_id,$filter_param,'goodsList'); // 筛选的价格期间
        $filter_brand = $goodsLogic->get_filter_brand($filter_goods_id,$filter_param,'goodsList',1); // 获取指定分类下的筛选品牌


        $count = count($filter_goods_id);
        $page = new Page($count,12);
        if($count > 0)
        {
            $goods_list = Db::name('goods')->where("goods_id","in", implode(',', $filter_goods_id))->order("$sort $sort_asc")->limit($page->firstRow.','.$page->listRows)->select();
            $filter_goods_id2 = get_arr_column($goods_list, 'goods_id');
        }
        $goods_category = Db::name('goods_category')->where('is_show=1')->cache(true)->column('id,name,parent_id,level,title,keywords,description'); // 键值分类数组

        $navigate_cat = navigate_goods($id); // 面包屑导航

        $rs=array('status'=>'1','info'=>'请求成功','goods_list'=>$goods_list,'navigate_cat'=>$navigate_cat,'goods_category'=>$goods_category,'filter_menu'=>$filter_menu,'filter_brand'=>$filter_brand,'filter_spec'=>'','filter_price'=>$filter_price,'goodsCate'=>$goodsCate,'cateArr'=>$cateArr,'filter_param'=>$filter_param,'cat_id'=>$id,'page'=>$page,'goods_category_tree'=>$this->goods_category_tree,'scenario_category_tree'=>$this->scenario_category_tree);
        exit(json_encode($rs));
    }
	
	/**
     * 场景列表页
     */
    public function scenarioList(){

        $key = md5($_SERVER['REQUEST_URI'].I('start_price').'_'.I('end_price'));
        $html =  Cache::get($key);  //读取缓存
        if(!empty($html))
        {
            return $html;
        }

        $filter_param = array(); // 筛选数组
        $id = I('get.id/d',1); // 当前分类id
        $brand_id = I('get.brand_id',0);
        $spec = I('get.spec',0); // 规格
        $attr = I('get.attr',''); // 属性
        $sort = I('get.sort','goods_id'); // 排序
        $sort_asc = I('get.sort_asc','desc'); // 排序
        $price = I('get.price',''); // 价钱
        $start_price = trim(I('post.start_price','0')); // 输入框价钱
        $end_price = trim(I('post.end_price','0')); // 输入框价钱
        if($start_price && $end_price) $price = $start_price.'-'.$end_price; // 如果输入框有价钱 则使用输入框的价钱

        $filter_param['id'] = $id; //加入筛选条件中
        $brand_id  && ($filter_param['brand_id'] = $brand_id); //加入筛选条件中
        $spec  && ($filter_param['spec'] = $spec); //加入筛选条件中
        $attr  && ($filter_param['attr'] = $attr); //加入筛选条件中
        $price  && ($filter_param['price'] = $price); //加入筛选条件中

        $goodsLogic = new GoodsLogic(); // 前台商品操作逻辑类
        // 分类菜单显示
        $scenarioCate = Db::name('ScenarioCategory')->where("id", $id)->find();// 当前分类
        $cateArr = $goodsLogic->get_scenario_cate($scenarioCate);


        // 筛选 品牌 规格 属性 价格
        $cat_id_arr = getScenarioCatGrandson ($id);
        $filter_goods_id = Db::name('goods')->where(['is_on_sale'=>1,'examine'=>1,'is_designer'=>0,'is_delete'=>0,'extend_cat_id'=>['in',implode(',', $cat_id_arr)]])->cache(true)->column("goods_id");
        // 过滤筛选的结果集里面找商品
        if($brand_id || $price)// 品牌或者价格
        {
            $goods_id_1 = $goodsLogic->getGoodsIdByBrandPrice($brand_id,$price); // 根据 品牌 或者 价格范围 查找所有商品id
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_1); // 获取多个筛选条件的结果 的交集
        }

        if($spec)// 规格
        {
            $goods_id_2 = $goodsLogic->getGoodsIdBySpec($spec); // 根据 规格 查找当所有商品id
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_2); // 获取多个筛选条件的结果 的交集
        }
        if($attr)// 属性
        {
            $goods_id_3 = $goodsLogic->getGoodsIdByAttr($attr); // 根据 规格 查找当所有商品id
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_3); // 获取多个筛选条件的结果 的交集
        }

        $filter_menu  = $goodsLogic->get_filter_menu($filter_param,'scenarioList'); // 获取显示的筛选菜单
        $filter_price = $goodsLogic->get_filter_price($filter_goods_id,$filter_param,'scenarioList'); // 筛选的价格期间
        $filter_brand = $goodsLogic->get_filter_brand($filter_goods_id,$filter_param,'scenarioList',1); // 获取指定分类下的筛选品牌

        $count = count($filter_goods_id);
        $page = new Page($count,12);
        if($count > 0)
        {
            $scenario_list = Db::name('goods')->where("goods_id","in", implode(',', $filter_goods_id))->order("$sort $sort_asc")->limit($page->firstRow.','.$page->listRows)->select();
            $filter_goods_id2 = get_arr_column($scenario_list, 'goods_id');
        }
        $scenario_category = Db::name('scenario_category')->where('is_show=1')->cache(true)->column('id,name,parent_id,level,title,keywords,description'); // 键值分类数组

        $navigate_cat = navigate_scenario($id); // 面包屑导航

        $rs=array('status'=>'1','info'=>'请求成功','goods_list'=>$scenario_list,'navigate_cat'=>$navigate_cat,'scenario_category'=>$scenario_category,'filter_menu'=>$filter_menu,'filter_brand'=>$filter_brand,'filter_spec'=>'','filter_price'=>$filter_price,'scenarioCate'=>$scenarioCate,'cateArr'=>$cateArr,'filter_param'=>$filter_param,'cat_id'=>$id,'page'=>$page,'goods_category_tree'=>$this->goods_category_tree,'scenario_category_tree'=>$this->scenario_category_tree);
        exit(json_encode($rs));
    }  
  

    /**
     * [More 首页-更多商品列表]
     */
    public function More(){
        $key = md5($_SERVER['REQUEST_URI'].I('start_price').'_'.I('end_price'));
        $html =  Cache::get($key);  //读取缓存
        if(!empty($html))
        {
            return $html;
        }

        $brand_id = I('get.brand_id',0);
        $filter_param = array(); // 筛选数组
        $sort = I('get.sort','goods_id'); // 排序
        $sort_asc = I('get.sort_asc','desc'); // 排序
        $price = I('get.price',''); // 价钱
        $start_price = trim(I('post.start_price','0')); // 输入框价钱
        $end_price = trim(I('post.end_price','0')); // 输入框价钱
        if($start_price && $end_price) $price = $start_price.'-'.$end_price; // 如果输入框有价钱 

        $brand_id  && ($filter_param['brand_id'] = $brand_id); //加入筛选条件中
        $price  && ($filter_param['price'] = $price); //加入筛选条件中

        // 筛选 品牌 规格 属性 价格
        $filter_goods_id = Db::name('goods')->where(['is_on_sale'=>1,'examine'=>1,'is_designer'=>0,'is_delete'=>0])->cache(true)->column("goods_id");
        // 过滤筛选的结果集里面找商品
         
        $goodsLogic = new GoodsLogic(); // 前台商品操作逻辑类
        if($brand_id || $price)// 品牌或者价格
        {
            $goods_id_1 = $goodsLogic->getGoodsIdByBrandPrice($brand_id,$price); // 根据 品牌 或者 价格范围 查找所有商品id
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_1); // 获取多个筛选条件的结果 的交集
        }
        $filter_menu  = $goodsLogic->get_filter_menu($filter_param,'more'); // 获取显示的筛选菜单
        $filter_price = $goodsLogic->get_filter_price($filter_goods_id,$filter_param,'more',100); // 筛选的价格期间

        $filter_brand = $goodsLogic->get_filter_brand($filter_goods_id,$filter_param,'more',1); // 获取指定分类下的筛选品牌

        $count = count($filter_goods_id);
        // dump($count);die;
        $page = new Page($count,12);
        if($count > 0)
        {
            $scenario_list = Db::name('goods')->where("goods_id","in", implode(',', $filter_goods_id))->order("$sort $sort_asc")->limit($page->firstRow.','.$page->listRows)->select();
        }

        $rs=array('status'=>'1','info'=>'请求成功','goods_list'=>$scenario_list,'filter_menu'=>$filter_menu,'filter_brand'=>$filter_brand,'filter_spec'=>'','filter_price'=>$filter_price,'filter_param'=>$filter_param,'page'=>$page,'goods_category_tree'=>$this->goods_category_tree,'scenario_category_tree'=>$this->scenario_category_tree);
        exit(json_encode($rs));

    }

	
	/**
    * 商品详情页
    */ 
    public function goodsInfo(){
        $user_id = I('user_id');

        //  form表单提交      
        $goodsLogic = new \ylt\home\logic\GoodsLogic();
        $goods_id = I("get.id/d");
        $goods = Db::name('Goods')->where("goods_id",$goods_id)->find();
        $goods['comment_count']=count(DB::name('comment')->where('goods_id',$goods_id)->where('is_show=1')->select()) ;

        if(empty($goods) || ($goods['is_on_sale'] == 0)){
            exit(json_encode(array('status'=>-11,'msg'=>'该商品已经下架','link'=>'Index/index')));
        }
    
        if($goods['brand_id']){
            $brnad = Db::name('brand')->where("id",$goods['brand_id'])->find();
            $goods['brand_name'] = $brnad['name'];
        }  
        $goods_images_list = Db::name('GoodsImages')->where("goods_id", $goods_id)->order('img_id desc')->select(); // 商品 图册
	    $filter_spec = $goodsLogic->get_spec($goods_id);
		$goods['logo'] = Db::name('supplier_config')->where(['supplier_id'=>$goods['supplier_id'],'name'=>'store_logo'])->value('value');
        //商品是否正在促销中        
        if($goods['prom_type'] > 0)
        {
            $goods['flash_sale'] = get_goods_promotion($goods['goods_id']);
			if($goods['flash_sale'] == 1){
				$goods['prom_type'] = 0; // 活动结束
            }
            if ($goods['prom_type'] == 6) {
                //预约    2020.02.28
                $make_time  = make_time($goods,$_SESSION['user']['user_id']);  //预约信息
                $set_time['make_go']        = date('m月d日 H:i',$goods['flash_sale']['make_go']);
                $set_time['make_in']        = date('m月d日 H:i',$goods['flash_sale']['make_in']);
                $set_time['purchase_go']    = '短信通知抽签成功';
                $set_time['purchase_in']    = '24小时内下单支付';
                $set_time['express_go']     = date('m月d日 H:i',$goods['flash_sale']['express_go']);
                //预约
            }

        }
        if ($goods['keywords']=="") {
        	$goods['keywords']=$goods['goods_name'];
        }
        if ($goods['title']=="") {
        	$goods['title']=$goods['goods_name'];
        }
        if ($goods['description']=="") {
        	$goods['description']=$goods['goods_name'];
        }

        $spec_goods_price  = Db::name('goods_price')->where("goods_id", $goods_id)->column("key,price,store_count,quantity"); // 规格 对应 价格 库存表
		
		$recommend_goods =  Db::name('goods')->field('goods_id,goods_name,goods_thumb,shop_price')->where('is_recommend = 1 and examine =1 and is_on_sale = 1')->order('goods_id desc')->limit(10)->select(); // 推荐

        $freight_free  = Db::name('supplier_config')->where(["supplier_id" => $goods['supplier_id'],"name"=>"is_free_shipping"])->value('value');       // 商店设置是否包邮
        $commentStatistics = $goodsLogic->commentStatistics($goods_id);// 获取某个商品的评论统计
        $point_rate = tpCache('shopping.point_rate');
        
        //商品是否已收藏
        if ($user_id) {
            $GoodsCollect = Db::name('GoodsCollect')->where(['goods_id'=>$goods_id,'user_id'=>$user_id])->count();
        }

        $rs=array('status'=>'1','info'=>'请求成功','spec_goods_price'=>$spec_goods_price,'navigate_goods'=>navigate_goods($goods_id,1),'commentStatistics'=>$commentStatistics,'filter_spec'=>$filter_spec,'goods_images_list'=>$goods_images_list,'siblings_cate'=>$goodsLogic->get_siblings_cate($goods['cat_id']),'recommend_goods'=>$recommend_goods,'goods'=>$goods,'point_rate'=>$point_rate,'freight_free'=>$freight_free,'make_time'=>$make_time,'set_time'=>$set_time,'GoodsCollect'=>$GoodsCollect);
        exit(json_encode($rs));
  
    }
	
    /**
     * [custom_made 定制页面]
     * @return [type] [description]
     */
    public function custom_made(){

        $data = I('post.');
        $uid = session('user');
        $users_id=$uid['user_id'];
        $goods_id=I('post.goods_id');
        $where['session_id']=$this->session_id;
        $data['session_id'] = $this->session_id;
        $users_id = $users_id ? $users_id : 0;
        if($users_id){
             $where['users_id']=$users_id;
            $data['users_id'] = $users_id;
        }
           $data['goods_id'] = $goods_id;
            $where['goods_id']=$goods_id;

        if(IS_POST && I('post.linkman') !='' && I('post.phone') !='' && I('post.logoImages') !='')
        {          
            $goods=Db::name('goods')->where('goods_id',$goods_id)->field('supplier_id,goods_name')->find();
            $data['supplier_id']=$goods['supplier_id'];
            $data['goods_name']=$goods['goods_name'];
            if(preg_match("/0?(13|14|15|18)[0-9]{9}/",$data['phone'])){ 
                  $arrid=Db::name("custom")->where($where)->find();
                if($arrid){
                    $data['save_time']=time();
                    Db::name("custom")->where(array('users_id'=>$uid['user_id'],'goods_id'=>$data['goods_id']))->update($data);
                    $id=$arrid['id'];
                }else{
                    $data['add_time']=time();
                    $data['save_time']=time();
                    $id=Db::name("custom")->insertGetId($data);
                }
                session('custom_id',$id);
                exit(json_encode(array('status'=>1,'msg'=>'添加成功','result'=>'')));
            }else{
                exit(json_encode(array('status'=>-1,'msg'=>'手机格式不正确','result'=>'')));
            }
        }
              exit(json_encode(array('status'=>-1,'msg'=>'信息不完整','result'=>'')));
    }
    
	 /**
     * 获取可发货地址
     */
    public function getRegion()
    {
        $goodsLogic = new GoodsLogic();
        $region_list = $goodsLogic->getRegionList();//获取配送地址列表
        $region_list['status'] = 1;
        $this->ajaxReturn($region_list);
    }
	
	
	/**
     * 商品评论ajax分页
     */
    public function ajaxComment(){        
        $goods_id = input("goods_id",'0');
        $commentType = I('commentType','1'); // 1 全部 2好评 3 中评 4差评
        $where = ['is_show'=>1,'goods_id'=>$goods_id,'parent_id'=>0];
        if($commentType==5){
            $where['img'] = ['<>',''];
        }else{
        	$typeArr = array('1'=>'0,1,2,3,4,5','2'=>'4,5','3'=>'3','4'=>'0,1,2');
            $where['ceil((deliver_rank + goods_rank + service_rank) / 3)'] = ['in',$typeArr[$commentType]];
        }
        $count = Db::name('Comment')->where($where)->count();
        
        $page = new AjaxPage($count,5);
        $show = $page->show();   
       
        $list = Db::name('Comment')->where($where)->order("add_time desc")->limit($page->firstRow.','.$page->listRows)->select();
         
        $replyList = Db::name('Comment')->where(['is_show'=>1,'goods_id'=>$goods_id,'parent_id'=>['>',0]])->order("add_time desc")->select();
        
        foreach($list as $k => $v){
            $list[$k]['img'] = unserialize($v['img']); // 晒单图片      
		    $list[$k]['username'] = mb_substr($list[$k]['username'], 0, 1, 'utf-8').'**'. mb_substr($list[$k]['username'], -2, 2, 'utf-8');; // 晒单图片       
        }

        $rs=array('status'=>'1','info'=>'请求成功','commentlist'=>$list,'replyList'=>$replyList,'page'=>$show);
        exit(json_encode($rs));  
    }
	
	// /**
 //     * 商品咨询ajax分页
 //     */
 //    public function ajax_consult(){        
 //        $goods_id = input("goods_id/d", 0);
 //        $consult_type = I('consult_type','0'); // 0全部咨询  1 商品咨询 2 支付咨询 3 配送 4 售后
                 
 //        $where  = ['parent_id'=>0,'goods_id'=>$goods_id];
 //        if($consult_type > 0){
 //            $where['consult_type'] = $consult_type;
 //        }
 //        $count = Db::name('GoodsConsult')->where($where)->count();
 //        $page = new AjaxPage($count,5);
 //        $show = $page->show();        
 //        $list = Db::name('GoodsConsult')->where($where)->order("id desc")->limit($page->firstRow.','.$page->listRows)->select();
 //        $replyList = Db::name('GoodsConsult')->where("parent_id > 0")->order("id desc")->select();
        
 //        $rs=array('status'=>'1','info'=>'请求成功','consultCount'=>$count,'consultList'=>$list,'replyList'=>$replyList,'page'=>$show);
 //        exit(json_encode($rs));
    
 //    }
	
	/**
     * 用户收藏商品
     * @param type $goods_id
     */
    public function collect_goods()
    {
        $goods_id = I('goods_id/d');
        $user_id = I('user_id/d');
        $goodsLogic = new GoodsLogic();        
        $result = $goodsLogic->collect_goods($user_id,$goods_id);
        exit(json_encode($result));
    }
	
	// /**
 //     * 加入购物车弹出
 //     */
 //    public function open_add_cart()
 //    {        
 //         return $this->fetch();
 //    }



	/**
     * 商品搜索列表页
     */
    public function search()
    {
        
        $filter_param = array(); // 筛选数组                        
        $id = I('get.id',0); // 当前分类id
        $brand_id = I('brand_id',0);
        $sort = I('sort','goods_id'); // 排序 goods_id
        $sort_asc = I('sort_asc','desc'); // 排序 asc
        $price = I('price',''); // 价钱
        $start_price = trim(I('start_price','0')); // 输入框价钱
        $end_price = trim(I('end_price','0')); // 输入框价钱
        if($start_price && $end_price) $price = $start_price.'-'.$end_price; // 如果输入框有价钱 则使用输入框的价钱
        $keywords = urldecode(trim(I('keywords',''))); // 关键字搜索
        $q = I('q');
        if (!empty($q) ) {
            $keywords=$q;
        }
        empty($keywords) && $keywords = '礼品';
        $id && ($filter_param['id'] = $id); //加入筛选条件中                       
        $brand_id  && ($filter_param['brand_id'] = $brand_id); //加入筛选条件中        
        $price  && ($filter_param['price'] = $price); //加入筛选条件中
        $keywords  && ($_GET['keywords'] = $filter_param['keywords'] = $keywords); //加入筛选条件中
        

        $goodsLogic = new GoodsLogic(); // 前台商品操作逻辑类
               
        $where  = "is_on_sale = 1 AND examine = 1";
		
		if(!empty(I('keywords'))){
			
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
				
				$where .= " (`goods_name` LIKE '%".$val."%' OR `keywords` LIKE '%".$val."%' OR `goods_sn` LIKE '%".$val."%.' )";
				
				if(Db::name('keywords')->where('keyword',$val)->value('keyword'))
					DB::name('keywords')->where('keyword',$val)->setInc('count');
				
			}
			
			$where .= ')';

		}else{
			
			$where .= " AND (`goods_name` LIKE '%".$keywords."%' OR `keywords` LIKE '%".$keywords."%' OR `goods_sn` LIKE '%".$keywords."%.' )";
		}

        
        if($id)
        {
            $cat_id_arr = getCatGrandson ($id);
            $where .= "AND cat_id in(".implode(',',$cat_id_arr).")";
        }
        
        $search_goods = Db::name('goods')->where($where)->column('goods_id,cat_id');
        $filter_goods_id = array_keys($search_goods);
        $filter_cat_id = array_unique($search_goods); // 分类需要去重
        if($filter_cat_id)        
        {
            $cateArr = Db::name('goods_category')->where("id","in",implode(',', $filter_cat_id))->limit(20)->select();
            $tmp = $filter_param;
            foreach($cateArr as $k => $v)            
            {
                $tmp['id'] = $v['id'];
                $cateArr[$k]['href'] = Url::build("/Home/Goods/search",$tmp);
            }                
        }                        
        // 过滤筛选的结果集里面找商品        
        if($brand_id || $price)// 品牌或者价格
        {
            $goods_id_1 = $goodsLogic->getGoodsIdByBrandPrice($brand_id,$price); // 根据 品牌 或者 价格范围 查找所有商品id    
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_1); // 获取多个筛选条件的结果 的交集
        }
        
        $filter_menu  = $goodsLogic->get_filter_menu($filter_param,'search'); // 获取显示的筛选菜单
        $filter_price = $goodsLogic->get_filter_price($filter_goods_id,$filter_param,'search'); // 筛选的价格期间         
        $filter_brand = $goodsLogic->get_filter_brand($filter_goods_id,$filter_param,'search',1); // 获取指定分类下的筛选品牌        
                                
        $count = count($filter_goods_id);
        $page = new Page($count,20);
        if($count > 0)
        {
            $goods_list = Db::name('goods')->where(['is_on_sale'=>1,'goods_id'=>['in',implode(',', $filter_goods_id)]])->order("$sort $sort_asc")->limit($page->firstRow.','.$page->listRows)->select();
            $filter_goods_id2 = get_arr_column($goods_list, 'goods_id');
        }   

        $rs=array('status'=>'1','info'=>'请求成功','goods_list'=>$goods_list,'filter_menu'=>$filter_menu,'filter_brand'=>$filter_brand,'filter_price'=>$filter_price,'cateArr'=>$cateArr,'filter_param'=>$filter_param,'page'=>$page,'cat_id'=>$id,'keywords'=>I('keywords'));
        exit(json_encode($rs));

    }
    
    /**
     * [goodsFestival 2019端午专区]
     * @return [type] [description]
     */
	public function goodsFestival(){
        $goods_d=Db::name('goods')->where(array('shop_price'=>168,'cat_id'=>22))->field('goods_id,cat_id,goods_name,original_img,shop_price,market_price,goods_remark')->select();
        $goods_s_d=Db::name('goods')->where(array('shop_price'=>268,'cat_id'=>22))->field('goods_id,cat_id,goods_name,original_img,shop_price,market_price,goods_remark')->select();
        $goods_ss_d=Db::name('goods')->where(array('shop_price'=>368,'cat_id'=>22))->field('goods_id,cat_id,goods_name,original_img,shop_price,market_price,goods_remark')->select();
        $goods_sss_d=Db::name('goods')->where(array('shop_price'=>568,'cat_id'=>22))->field('goods_id,cat_id,goods_name,original_img,shop_price,market_price,goods_remark')->select();
        $goods_get=Db::name('goods')->where(array('extend_cat_id'=>1082,'cat_id'=>1038))->field('goods_id,cat_id,goods_name,original_img,shop_price,market_price,goods_remark')->select();


        $rs=array('status'=>'1','info'=>'请求成功','goods_get'=>$goods_get,'goods_sss_d'=>$goods_sss_d,'goods_ss_d'=>$goods_ss_d,'goods_s_d'=>$goods_s_d,'goods_d'=>$goods_d);
        exit(json_encode($rs));

    }

    /**
     * [goodsFestival_index 2020端午专区]
     * @return [type] [description]
     */
    public function goodsFestival_index(){
        $goods_a=Db::name('goods')->where("goods_id in (5989,5959,5962)")->field('goods_id,cat_id,goods_name,original_img,shop_price,market_price,goods_remark')->select();
        $goods_b=Db::name('goods')->where("goods_id in (5963,5966,5965,5964,5961,5960,5952,5950)")->field('goods_id,cat_id,goods_name,original_img,shop_price,market_price,goods_remark')->select();
        $goods_c=Db::name('goods')->where("goods_id in (5986,5984,5985)")->field('goods_id,cat_id,goods_name,original_img,shop_price,market_price,goods_remark')->select();
        $goods_d=Db::name('goods')->where("goods_id in (5975,5971,5957,5980)")->field('goods_id,cat_id,goods_name,original_img,shop_price,market_price,goods_remark')->select();
        $goods_e=Db::name('goods')->where("goods_id in (5262,5265,5268)")->field('goods_id,cat_id,goods_name,original_img,shop_price,market_price,goods_remark')->select();
        $goods_f=Db::name('goods')->where("goods_id in (5263,5266,5269,5272)")->field('goods_id,cat_id,goods_name,original_img,shop_price,market_price,goods_remark')->select();
        
        $rs=array('status'=>'1','info'=>'请求成功','goods_a'=>$goods_a,'goods_b'=>$goods_b,'goods_c'=>$goods_c,'goods_d'=>$goods_d,'goods_e'=>$goods_e,'goods_f'=>$goods_f);
        exit(json_encode($rs));
    }
	
}