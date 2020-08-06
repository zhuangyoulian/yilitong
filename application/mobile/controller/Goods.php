<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/2
 * Time: 11:34
 */
namespace ylt\mobile\controller;
use ylt\home\logic\UsersLogic;
use ylt\home\logic\GoodsLogic;
use think\AjaxPage;
use think\Controller;
use think\Url;
use think\Config;
use think\Page;
use think\Db;
use think\Request;
use think\Cache;
use org\JSSDK;

class Goods extends MobileBase {

   /*
    * 初始化操作
    */
    public function wxPhoneLogin($type='')
    // public function _initialize()
    {
        $this->cleanCache();
        // parent::_initialize();
        $user = array();
        if (session('?user')) {
            $user = session('user');
            $user = Db::name('users')->where("user_id", $user['user_id'])->find();
            session('user', $user);  //覆盖session 中的 user
            $this->user = $user;
            $this->user_id = $user['user_id'];
            $this->assign('user', $user); //存储用户信息
        }
        if ($type =='logo') {
            return array('status'=>311);
        }
        if (!$this->user_id) { //未登录
            // 如果是微信浏览器，则用微信授权登录
            if(strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
                $this->weixin_config = Db::name('wx_user')->find(); //获取微信配置
                $this->assign('wechat_config', $this->weixin_config);
                if(is_array($this->weixin_config) && $this->weixin_config['wait_access'] == 1){
                    $wxuser = $this->GetOpenid(); //授权获取openid以及微信用户信息
                   // setcookie('subscribe',$wxuser['subscribe']);
                    //微信自动登录
                    $wxuser['recommend_code'] = input('recommend_code');
                    $wxuser['unionid'] = $wxuser['unionid'] ? $wxuser['unionid'] : '';
                    $logic = new \ylt\home\model\UsersLogic();
                    $data = $logic->thirdLogin($wxuser);

                    if($data['status'] == 1){
                        session('user',$data['result']);
                        setcookie('user_id',$data['result']['user_id'],null,'/');
                        $this->user_id = $data['result']['user_id'];
                        setcookie('user_name',$data['result']['nickname'],null,'/');
                        // 登录后将购物车的商品的 user_id 改为当前登录的id
                        Db::name('cart')->where("session_id", $this->session_id)->update(array('user_id'=>$data['result']['user_id']));
                        //绑定手机号
                        if (empty($data['result']['mobile'])) {
                            session('login_url',$_SERVER[REQUEST_URI]);
                            $this->error('请先绑定手机账号',Url::build('User/mobile_validate_two'));
                        }
                    }
                }
            }else{
                session('login_url',$_SERVER[REQUEST_URI]);
                header("location:" . Url::build('User/login'));
                exit;
            }
        }
    }
  
    public function ajaxlogin(){
        $this-> wxPhoneLogin();
        // dump(  $user = session('user'));die;
        $this->redirect(Url::build('/Mobile/Cart/cart'));
    }
  
    public function index(){

        return $this->fetch();
    }


     /**
     * 礼品推荐
     */
    public function gift(){
        $data = get_scenario_category_tree();//获取对应数据
        // $data = Db::name('gifts_category')->Cache(true,YLT_CACHE_TIME)->order('sort_order DESC')->select();//获取对应数据
            //将数据打包成二维数组对象
            // dump($data);
        foreach( $data as $k=>$v){
            if ($v['parent_id']==0){
                $tmp[$v['id']]=$v;
                }
            }
        foreach($data as $k=>$v){
            foreach($tmp as $b=>$a){
                if ($v['parent_id']==$b){
                    $tmp[$b]['tmenu'][]=$v;
                    }
                }

            }
   
            // dump($data);die;
            $this->assign('tmp',$tmp);
                // return view('',['tmp'=>$tmp]);
            return $this->fetch();
        
    }
    /**
     * 商品分类列表显示
     */
    public function categoryList(){
        $data = get_goods_category_tree();//获取对应数据
        // $data = Db::name('gifts_category')->Cache(true,YLT_CACHE_TIME)->order('sort_order DESC')->select();//获取对应数据
            //将数据打包成二维数组对象
            // dump($data);
        foreach( $data as $k=>$v){
            if ($v['parent_id']==0){
                $tmp[$v['id']]=$v;
                }
            }
        foreach($data as $k=>$v){
            foreach($tmp as $b=>$a){
                if ($v['parent_id']==$b){
                    $tmp[$b]['tmenu'][]=$v;
                    }
                }

            }
            $this->assign('tmp',$tmp);
                // return view('',['tmp'=>$tmp]);
            return $this->fetch();
        
    }


    /**
     * 送礼攻略商品列表页
     */
    public function goodsList(){
        // $key = md5($_SERVER['REQUEST_URI'].I('start_price').'_'.I('end_price'));
        // $html =  Cache::get($key);  //读取缓存
        // if(!empty($html))
        // {
        //     return $html;
        // }

        $filter_param = array(); // 筛选数组
        $id = I('get.gift_id/d',1); // 当前分类id
        $brand_id = I('get.brand_id/d',0);
        $spec = I('get.spec',0); // 规格
        $attr = I('get.attr',''); // 属性
        $sort = I('get.sort','goods_id'); // 排序
        $sort_asc = I('get.sort_asc','desc'); // 排序
        $price = I('get.price',''); // 价钱
        $start_price = trim(I('post.start_price','0')); // 输入框价钱
        $end_price = trim(I('post.end_price','0')); // 输入框价钱
        if($start_price && $end_price) $price = $start_price.'-'.$end_price; // 如果输入框有价钱 则使用输入框的价钱
        // echo 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];die;
        $filter_param['gift_id'] = $id; //加入筛选条件中
        $brand_id  && ($filter_param['brand_id'] = $brand_id); //加入筛选条件中
        $spec  && ($filter_param['spec'] = $spec); //加入筛选条件中
        $attr  && ($filter_param['attr'] = $attr); //加入筛选条件中
        $price  && ($filter_param['price'] = $price); //加入筛选条件中

        $goodsLogic = new \ylt\home\logic\GoodsLogic(); // 前台商品操作逻辑类
        // 分类菜单显示
        $scenarioCate = Db::name('ScenarioCategory')->where("id", $id)->find();// 当前分类
        // dump($scenarioCate);die;

        //($goodsCate['level'] == 1) && header('Location:'.Url::build('Home/Channel/index',array('cat_id'=>$id))); //一级分类跳转至大分类馆
        $cateArr = $goodsLogic->get_scenario_cate($scenarioCate);


        // 筛选 品牌 规格 属性 价格
        $cat_id_arr = getScenarioCatGrandson ($id);
        $filter_goods_id = Db::name('goods')->where(['is_on_sale'=>1,'examine'=>1,'is_designer'=>0,'extend_cat_id'=>['in',implode(',', $cat_id_arr)]])->cache(true)->column("goods_id");
        // dump($filter_goods_id);die;
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
        $page = new Page($count,config('PAGESIZE'));
        if($count > 0)
        {
            $field = "goods_id,cat_id,goods_name,goods_thumb,shop_price,market_price,goods_remark,comment_count";
            $scenario_list = Db::name('goods')->where("goods_id","in", implode(',', $filter_goods_id))->field($field)->order("$sort $sort_asc")->limit($page->firstRow.','.$page->listRows)->select();
            // $filter_goods_id2 = get_arr_column($scenario_list, 'goods_id');
            //  if($filter_goods_id2)
            //   $goods_images = Db::name('goods_images')->where("goods_id", "in", implode(',', $filter_goods_id2))->cache(true)->select();
        }
        // print_r($filter_menu);
        $scenario_category = Db::name('scenario_category')->where('is_show=1')->cache(true)->column('id,name,parent_id,level,title,keywords,description'); // 键值分类数组
        

        $this->assign('goods_list',$scenario_list);
        $this->assign('scenario_category',$scenario_category); //商品分类
        // $this->assign('goods_images',$goods_images);  // 相册图片
        $this->assign('filter_menu',$filter_menu);  // 筛选菜单
        $this->assign('filter_spec','');  // 筛选规格
        $this->assign('filter_brand',$filter_brand);  // 列表页筛选属性 - 商品品牌
        $this->assign('filter_price',$filter_price);// 筛选的价格期间
        $this->assign('scenarioCate',$scenarioCate);
        $this->assign('cateArr',$cateArr);
        $this->assign('filter_param',$filter_param); // 筛选条件
        $this->assign('cat_id',$id);
        $this->assign('gift_id',$gift_id);
        $this->assign('page',$page);// 赋值分页输出
        $this->assign('sort_asc', $sort_asc == 'asc' ? 'desc' : 'asc');
        config('TOKEN_ON',false);
        if(input('is_ajax'))
            return $this->fetch('ajaxGoodsList');
        else
            return $this->fetch();
    }
    
    /**
     * 商品分类商品列表页
     */
    public function goodsList_S(){
        // $key = md5($_SERVER['REQUEST_URI'].I('start_price').'_'.I('end_price'));
        // $html =  Cache::get($key);  //读取缓存
        // if(!empty($html))
        // {
        //     return $html;
        // }

        $filter_param = array(); // 筛选数组
        $id = I('get.id/d',1); // 当前分类imailed
        $brand_id = I('get.brand_id/d',0);
        $spec = I('get.spec',0); // 规格
        $attr = I('get.attr',''); // 属性
        $sort = I('get.sort','goods_id'); // 排序
        $sort_asc = I('get.sort_asc','desc'); // 排序
        $price = I('get.price',''); // 价钱
        $start_price = trim(I('post.start_price','0')); // 输入框价钱
        $end_price = trim(I('post.end_price','0')); // 输入框价钱
        if($start_price && $end_price) $price = $start_price.'-'.$end_price; // 如果输入框有价钱 则使用输入框的价钱
        // echo 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];die;
        $filter_param['id'] = $id; //加入筛选条件中
        $brand_id  && ($filter_param['brand_id'] = $brand_id); //加入筛选条件中
        $spec  && ($filter_param['spec'] = $spec); //加入筛选条件中
        $attr  && ($filter_param['attr'] = $attr); //加入筛选条件中
        $price  && ($filter_param['price'] = $price); //加入筛选条件中

        $goodsLogic = new \ylt\home\logic\GoodsLogic(); // 前台商品操作逻辑类
        // 分类菜单显示
        $scenarioCate = Db::name('ScenarioCategory')->where("id", $id)->find();// 当前分类
        // dump($scenarioCate);die;

        //($goodsCate['level'] == 1) && header('Location:'.Url::build('Home/Channel/index',array('cat_id'=>$id))); //一级分类跳转至大分类馆
        $cateArr = $goodsLogic->get_scenario_cate($scenarioCate);


        // 筛选 品牌 规格 属性 价格
        $cat_id_arr = getCatGrandson ($id);
        $filter_goods_id = Db::name('goods')->where(['is_on_sale'=>1,'examine'=>1,'is_designer'=>0,'cat_id'=>['in',implode(',', $cat_id_arr)]])->cache(true)->column("goods_id");

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
        $page = new Page($count,config('PAGESIZE'));
        if($count > 0)
        {
            $scenario_list = Db::name('goods')->where("goods_id","in", implode(',', $filter_goods_id))->order("$sort $sort_asc")->limit($page->firstRow.','.$page->listRows)->select();
            
            // $filter_goods_id2 = get_arr_column($scenario_list, 'goods_id');
            //  if($filter_goods_id2)
            //   $goods_images = Db::name('goods_images')->where("goods_id", "in", implode(',', $filter_goods_id2))->cache(true)->select();
        }
        // print_r($filter_menu);
        $scenario_category = Db::name('scenario_category')->where('is_show=1')->cache(true)->column('id,name,parent_id,level,title,keywords,description'); // 键值分类数组


        $this->assign('goods_list',$scenario_list);
        $this->assign('scenario_category',$scenario_category); //商品分类
        // $this->assign('goods_images',$goods_images);  // 相册图片
        $this->assign('filter_menu',$filter_menu);  // 筛选菜单
        $this->assign('filter_spec','');  // 筛选规格
        $this->assign('filter_brand',$filter_brand);  // 列表页筛选属性 - 商品品牌
        $this->assign('filter_price',$filter_price);// 筛选的价格期间
        $this->assign('scenarioCate',$scenarioCate);
        $this->assign('cateArr',$cateArr);
        $this->assign('filter_param',$filter_param); // 筛选条件
        $this->assign('cat_id',$id);
        $this->assign('gift_id',$gift_id);
        $this->assign('page',$page);// 赋值分页输出
        $this->assign('sort_asc', $sort_asc == 'asc' ? 'desc' : 'asc');
        config('TOKEN_ON',false);
        if(input('is_ajax'))
            return $this->fetch('ajaxGoodsList');
        else
            return $this->fetch();
    }
    
    /**
     * 商品详情页
     */
    public function goodsInfo(){
        $this->wxPhoneLogin();
        $user_id =$this->user_id;
        $logic = new UsersLogic();
        $user = $logic->get_info($user_id); //当前登录用户信息
        if (empty($user['result']['mobile'])) {
            $this->error('请先绑定手机账号',Url::build('User/mobile_validate_two'));
        }
        if (empty($user_id)) {
            session('login_url',$_SERVER[REQUEST_URI]);
            header("location:" . Url::build('User/login'));
            exit;
        }
        
        $goods_id = I("get.id/d");
        $storeIndex=$_SERVER['HTTP_HOST'];
        if (empty($storeIndex) || !strpos($storeIndex,'yilitong.com')) {
            $this->assign('storeIndex',-1);
        }
        if (strpos($storeIndex,'/Mobile/Distribution/storeIndex') || strpos($storeIndex,'/Mobile/Distribution/search')) {
            $this->assign('storeIndex',1);
        }
        

        $goodsLogic = new \ylt\home\logic\GoodsLogic();
        
        // $goods = Db::name('Goods')->where("goods_id",$goods_id)->cache(true,YLT_CACHE_TIME)->find();
        $goods = Db::name('Goods')->where("goods_id",$goods_id)->find();
        $goods['discount'] = $goods->discount;
    
        if(empty($goods) || ($goods['is_on_sale'] == 0)){
            $this->error('此商品不存在或者已下架');
        }
        if($goods['brand_id']){
            $brnad = Db::name('brand')->where("id", $goods['brand_id'])->find();
            $goods['brand_name'] = $brnad['name'];
        }

        //商品是否正在促销中
        $prom_count = $this->get_prom_type($goods,$user);
        if($prom_count['prom_type'] == 0){
            $goods['flash_sale'] = '';
            $goods['prom_type'] = 0;
        }
        $goods['flash_sale'] = $prom_count['flash_sale'];
        if ($prom_count['prom_type'] == 6) {
            $this->assign('goods_make',$prom_count['goods_make']);
            $this->assign('make_time',$prom_count['make_time']);
            $this->assign('set_time',$prom_count['set_time']);
        }elseif ($prom_count['prom_type'] == 7) {
            $this->assign('prom_count',$prom_count);
        }
        $goods_images_list = Db::name('GoodsImages')->where("goods_id", $goods_id)->order('img_id desc')->select(); // 商品 图册
        $filter_spec = $goodsLogic->get_spec($goods_id);
        $spec_goods_price  = Db::name('goods_price')->where("goods_id", $goods_id)->column("key,price,store_count,quantity"); // 规格 对应 价格 库存表
        $commentStatistics = $goodsLogic->commentStatistics($goods_id);// 获取某个商品的评论统计
        $this->assign('spec_goods_price', json_encode($spec_goods_price,true)); // 规格 对应 价格 库存表
        $goods['sale_num'] = Db::name('order_goods')->where(['goods_id'=>$goods_id,'is_send'=>1])->count();
        //当前用户收藏
        
        $freight_free  = Db::name('supplier_config')->where(["supplier_id" => $goods['supplier_id'],"name"=>"is_free_shipping"])->value('value');       // 商店设置是否包邮
        if (!empty($freight_free)) {
            $this->assign('freight_free', $freight_free);   // 全场满多少免运费
        }
        $logo = Db::name('supplier_config')->where(array('supplier_id'=>$goods['supplier_id'],'name'=>'store_logo'))->value('value'); //商品收藏数
    
        //分享微信
        $jssdk = new JSSDKSS("wx218ea80c35624c8a", "77380763d58d20f6bbcb18d469b40f03");
        //$jssdk = new JSSDKSS("wxff94c9ef025ccb79", "08cb16a4467dd7a4c4af53507cc27a42"); PC
        $signPackage = $jssdk->GetSignPackage();
        $this->assign('signPackage',$signPackage);
        //分享微信
        if ($filter_spec) {     //规格图片
            foreach ($filter_spec as $key => $value) {
                foreach ($value as $keys => $val) {
                    $filter_specs[$val['item_id']] = array(
                        'item_id'   => $val['item_id'],
                        'item'      => $val['item'],
                        'src'       => $val['src'],
                    );
                }
            }
        }



        $this->assign('collect',1);
        $this->assign('commentStatistics',$commentStatistics);//评论概览    
        $this->assign('filter_spec',$filter_spec);//规格参数
        $this->assign('filter_specs', json_encode($filter_specs,true)); // 规格图片
        $this->assign('goods_images_list',$goods_images_list);//商品缩略图
        $this->assign('goods',$goods);
        $this->assign('logo',$logo); //商品收藏人数
        return $this->fetch();
    }

    /**
     * [goodsGroup 拼单页面]
     * @return [type] [description]
     */
    public function goodsGroup(){
        $this->wxPhoneLogin();
        $id             = $_GET['group_id'];        //拼单ID
        $goods_id       = $_GET['goods_id'];        //商品ID
        $discount_id    = $_GET['prom_id'];         //活动ID
        $time           = time();
        //拼单活动信息
        $prom_count = Db::name('share_the_bill')->alias('s')->join('users u','s.u_id = u.user_id')->join('discount_goods g','s.goods_id = g.goods_id')->join('goods o','g.goods_id = o.goods_id')->where(['s.goods_id'=>$goods_id,'s.is_initiate'=>1,'s.id'=>$id,'g.discount_id'=>$discount_id])->field('s.*,u.nickname,u.head_pic,g.goods_name,g.goods_thumb,g.activity_price,g.market_price,o.goods_remark')->find();
        //商品的活动状态
        if(empty($prom_count)){
            $this->error('拼单活动不存在');
        }
        $prom_count['flash_sale'] = get_goods_promotion($goods_id);
        if($prom_count['flash_sale'] == 1){
            $this->error('拼单活动已结束');
        }

        if ($prom_count['flash_sale']['buy_type_rule'] == 1) {      //1用户 
            //剩余可拼单数量
            $prom_count['s_count'] = $prom_count['flash_sale']['buy_type_rule_num'] - Db::name('share_the_bill')->where('p_id',$id)->count();
        }elseif ($prom_count['flash_sale']['buy_type_rule'] == 2){  //2数量
            //剩余可拼单数量
            $prom_count['s_count']=$prom_count['flash_sale']['buy_type_rule_num'] - Db::name('share_the_bill')->where('p_id',$id)->sum('quantity');
        }
        //已拼
        $prom_count['sum'] = Db::name('share_the_bill')->where(['goods_id'=>$goods_id])->sum('quantity');
        $prom_count['sum'] = $prom_count['sum']?$prom_count['sum']:0;

        //拼单结束时间
        $prom_count['buy_end_time'] = $prom_count['flash_sale']['buy_type_rule_time']*3600 + $prom_count['add_time'];
        if ($time > $prom_count['buy_end_time']) {
            $prom_count['is_end'] = 1;
        }
        
        //是否已经参加这个拼单  
        $prom_count['is_participation'] = Db::name('share_the_bill')->where(['goods_id'=>$goods_id,'p_id'=>$id,'u_id'=>$this->user_id])->find();  
        //已参加的用户列表
        $prom_count['select'] = Db::name('share_the_bill')->alias('s')->join('users u','s.u_id = u.user_id')->where(['goods_id'=>$goods_id,'p_id'=>$id])->field('s.*,u.nickname,u.head_pic')->order('id asc')->select();  

        //拼单页面推荐商品
        $prom_goods = Db::name('discount_buy')->alias('d')->join('discount_goods g','d.id = g.discount_id')->where(['d.buy_type'=>7,'d.is_start'=>1])->where("d.end_time > $time")->where("g.goods_id != $goods_id")->limit(10)->field('g.goods_id,g.goods_name,g.goods_thumb,g.activity_price')->select();

        //分享微信
        $jssdk = new JSSDKSS("wx218ea80c35624c8a", "77380763d58d20f6bbcb18d469b40f03");
        //$jssdk = new JSSDKSS("wxff94c9ef025ccb79", "08cb16a4467dd7a4c4af53507cc27a42"); PC
        $signPackage = $jssdk->GetSignPackage();
        $this->assign('signPackage',$signPackage);
        // //分享微信
        // dump($prom_count);
        // die;


        $this->assign('prom_goods',$prom_goods);
        $this->assign('prom_count',$prom_count);
        return $this->fetch();
    }

    /*
    //商品是否正在促销中 获取商品的活动状态
     */
    public function get_prom_type($goods,$user){
        if($goods['prom_type'] > 0)
        {
            $prom_count['flash_sale'] = get_goods_promotion($goods['goods_id']);
            if($prom_count['flash_sale'] == 1){
                $prom_count['prom_type'] = 0; // 活动结束
                return $prom_count;
            }
            if ($goods['prom_type'] == 6) {
                //预约    2020.02.28
                // $make_time = $this->make_time($goods_id,$user_id);
                $make_time = make_time($goods,$this->user_id);
                $goods_make = Db::name('goods')->where('goods_id=5926 || goods_id=5925 || goods_id=5862')->field('goods_id,goods_name,shop_price,goods_thumb')->select();  //商品推荐
                $set_time['make_go']        = date('m月d日 H:i',$prom_count['flash_sale']['make_go']);
                $set_time['make_in']        = date('m月d日 H:i',$prom_count['flash_sale']['make_in']);
                $set_time['purchase_go']    = '短信通知抽签成功';
                $set_time['purchase_in']    = '24小时内下单支付';
                $set_time['express_go']     = date('m月d日 H:i',$prom_count['flash_sale']['express_go']);
                $prom_count['goods_make']    = $goods_make;
                $prom_count['make_time']     = $make_time;
                $prom_count['set_time']      = $set_time;
                $prom_count['prom_type']     = 6;
                //预约
            }else if ($goods['prom_type'] == 7) {
                //有效的拼单数量
                $prom_count['list'] = Db::name('share_the_bill')->alias('s')->join('users u','s.u_id = u.user_id')->where(['goods_id'=>$goods['goods_id'],'type'=>1,'is_initiate'=>1])->field('s.*,u.nickname,u.head_pic')->select();
                if ($prom_count['list']) {
                    foreach ($prom_count['list'] as $key => $value) {
                        if ($value['u_id'] == $user['result']['source_id']) {
                            $prom_count['list'][$key]['is_source_id'] = 1;
                        }
                        //加入该拼单的数量
                        $count = Db::name('share_the_bill')->where('p_id',$value['id'])->count(); 
                        //剩余可拼单数量
                        $prom_count['list'][$key]['s_count']=$prom_count['flash_sale']['buy_type_rule_num'] - $count;
                        $prom_count['list'][$key]['buy_end_time'] = $prom_count['flash_sale']['buy_type_rule_time']*3600 + $value['add_time'];
                    }
                }
                $prom_count['one'] = count($prom_count['list']);  
                $prom_count['sum'] = Db::name('share_the_bill')->where(['goods_id'=>$goods['goods_id']])->sum('quantity');  
                $prom_count['sum'] = $prom_count['sum']?$prom_count['sum']:0;
                $prom_count['prom_type']     = 7;
            }
            return $prom_count;
        }
    }

    /**
     * [AjaxMake 点击预约]
     */
    public function AjaxMake(){
        if (IS_POST) {
            $data['goods_id']   = I('goods_id');
            $data['make_type']  = I('type');
            $data['add_time']   = time();
            $data['user_id']    = $_SESSION['user']['user_id'];
            $data['consult_type']    = 5;
            if (empty($data['user_id'])) {
                $this->wxPhoneLogin('logo');
                return array('status'=>311,'msg'=>'网络拥堵，请刷新一下~');
            }
            if (empty($data['make_type'])) {
                return array('status'=>-3,'msg'=>'状态为空');
            }
            if (empty($data['goods_id'])) {
                return array('status'=>-3,'msg'=>'商品ID为空');
            }
            if (!Db::name('goods_share_list')->where(['user_id'=>$data['user_id'],'goods_id'=>$data['goods_id'],'type'=>$data['make_type']])->find()) {
                return array('status'=>322,'msg'=>'距本次预约还差一次朋友圈的分享~');
            }
            $logic = new UsersLogic();
            $user = $logic->get_info($data['user_id']); //当前登录用户信息
            if (empty($user['result']['mobile'])) {
                $this->error('请先绑定手机账号',Url::build('User/mobile_validate_two'));
            }
            $activity_count = Db::name('discount_goods')->where(['goods_id'=>$data['goods_id'],'discount_id'=>$data['make_type']])->value('activity_count');  //活动库存、限制的活动总量
            $goods_count = Db::name('goods_consult')->where(['goods_id'=>$data['goods_id'],'make_type'=>$data['make_type']])->count(); //预约的数量
            $count = $activity_count - $goods_count;  //活动剩余数量
            if ($count <= 0) {
                return array('status'=>3,'msg'=>'预约人数已满，请等待下轮预约！');
            }
            if (Db::name('goods_consult')->where(['goods_id'=>$data['goods_id'],'user_id'=>$data['user_id'],'make_type'=>$data['make_type']])->find()) {
                return array('status'=>2,'msg'=>'请勿重复预约');
            }
            $arr = Db::name('goods_consult')->insert($data);
            if ($arr) {
                return array('status'=>1,'msg'=>'恭喜您预约成功！');
            }
        }
    }

    /**
     * [goodsCustomization 定制页面]
     * @return [type] [description]
     */
    public function goodsCustomization(){
        $data = I('post.');
        $uid = session('user');
        $users_id=$uid['user_id'];
        $goods_id=I('get.goods_id');
        $where['session_id']=$this->session_id;
        $data['session_id'] = $this->session_id;
        $users_id = $users_id ? $users_id : 0;
        if($users_id){
            $where['users_id']= $users_id;
            $data['users_id'] = $users_id;
        }
           $data['goods_id'] = $goods_id;
           $where['goods_id']= $goods_id;
        if(IS_POST)
        {          
            $goods=Db::name('goods')->where('goods_id',$goods_id)->field('supplier_id,goods_name')->find();
            $data['supplier_id']=$goods['supplier_id'];
            $data['goods_name']=$goods['goods_name'];
            $data['type']="2019端午礼包";
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
        $this->assign('goods_id',$goods_id);
        return $this->fetch();
    }
  
     /**
     *图片上传，base64位压缩，点击即可上传
     */

    public function goodsCustom(){
        $base64 = $_POST['logoImages'];
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64, $result)){
            $type = $result[2]; //jpeg
            $IMG = base64_decode(str_replace($result[1], '', $base64)); //返回文件流
        }
                // dump($base64);die;
        //$IMG = base64_decode($base64);   //base64位转码，还原图片
        $path ='public/upload/logo/custom/';
        if (!file_exists($path)){
            mkdir($path,0777,true);
        }//如果地址不存在，创建地址
        $u=uniqid().date("ymdHis",time());
        $picname=$path.$u.'.jpg';
        $picname_s="/".$picname;
        file_put_contents($picname,$IMG);
        $picname2=$u.'.jpg';
        $this->ajaxReturn(array('status' => 1,'pic_id' =>$picname2,'pic_path'=>$picname_s));
    }
  
 
    /*
     * ajax获取商品评论
     */
    public function ajaxComment()
    {
        $p = I('p') ? I('p') : 1 ;
        $goods_id = I("goods_id/d", 0);
        $commentType = I('commentType', '1'); // 1 全部 2好评 3 中评 4差评
        if ($commentType == 5) {
            $wheres = array(
                'goods_id' => $goods_id, 'parent_id' => 0, 'img' => ['<>', ''],'is_show'=>1
            );
        } else {
            $typeArr = array('1' => '0,1,2,3,4,5', '2' => '4,5', '3' => '3', '4' => '0,1,2');
            $wheres = array('is_show'=>1,'goods_id' => $goods_id, 'parent_id' => 0, 'ceil((deliver_rank + goods_rank + service_rank) / 3)' => ['in', $typeArr[$commentType]]);
        }
        $count = Db::name('Comment')->where($wheres)->count();
        if ($commentType == 5) {
            $where = array(
                'goods_id' => $goods_id, 'c.parent_id' => 0, 'img' => ['<>', ''],'is_show'=>1
            );
        } else {
            $typeArr = array('1' => '0,1,2,3,4,5', '2' => '4,5', '3' => '3', '4' => '0,1,2');
            $where = array('is_show'=>1,'goods_id' => $goods_id, 'c.parent_id' => 0, 'ceil((deliver_rank + goods_rank + service_rank) / 3)' => ['in', $typeArr[$commentType]]);
        }
        $page_count = config('PAGESIZE');
        $page = new AjaxPage($count, $page_count);
        $list = Db::name('Comment')
            ->alias('c')
            ->join('__USERS__ u', 'u.user_id = c.user_id', 'LEFT')
            ->where($where)
            ->order("add_time desc")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        $replyList = Db::name('Comment')->where(['goods_id' => $goods_id, 'parent_id' => ['>', 0]])->order("add_time desc")->select();
        foreach ($list as $k => $v) {
            $list[$k]['img'] = unserialize($v['img']); // 晒单图片
            $replyList[$v['comment_id']] = Db::name('Comment')->where(['is_show' => 1, 'goods_id' => $goods_id, 'parent_id' => $v['comment_id']])->order("add_time desc")->select();
        }
        $this->assign('goods_id', $goods_id);//商品id
        $this->assign('commentlist', $list);// 商品评论
        $this->assign('commentType', $commentType);// 1 全部 2好评 3 中评 4差评 5晒图
        $this->assign('replyList', $replyList); // 管理员回复
        $this->assign('count', $count);//总条数
        $this->assign('page_count', $page_count);//页数
        $this->assign('current_count', $page_count * $p);//当前条
        $this->assign('p', $p);//页数
        return $this->fetch();
    }
    
    
    /**
     * 商品搜索列表页
     */
    public function ajaxSearch()
    {
        $hot_list=Db::name('goods')
            ->alias('g')
            ->join('supplier s','g.supplier_id=s.supplier_id')
            ->where('g.is_on_sale = 1 and g.examine = 1 and g.is_hot = 1 and s.status =1 and g.is_designer = 0' )
            ->order('g.last_update desc')
            ->order('s.supplier_id desc') 
            ->group('s.supplier_name')
            ->limit(0,8)
            ->select();
        $hot_count=count($hot_list);
        $this->assign('hot_count',$hot_count);
        $this->assign('hot_list',$hot_list);
        return $this->fetch();
    }
    
    
    /**
     * 商品搜索列表页
     */
    public function search(){
        $filter_param = array(); // 帅选数组
        $id = I('get.id/d',0); // 当前分类id
        $brand_id = I('brand_id/d',0);              
        $sort = I('sort','goods_id'); // 排序
        $sort_asc = I('sort_asc','desc'); // 排序
        $price = I('price',''); // 价钱
        $start_price = trim(I('start_price','0')); // 输入框价钱
        $end_price = trim(I('end_price','0')); // 输入框价钱
        if($start_price && $end_price) $price = $start_price.'-'.$end_price; // 如果输入框有价钱 则使用输入框的价钱       
        $filter_param['id'] = $id; //加入帅选条件中
        $brand_id  && ($filter_param['brand_id'] = $brand_id); //加入帅选条件中                
        $price  && ($filter_param['price'] = $price); //加入帅选条件中
        $q = urldecode(trim(I('q',''))); // 关键字搜索
        $q  && ($_GET['q'] = $filter_param['q'] = $q); //加入帅选条件中
        $qtype = I('qtype','');
        $where  = "is_on_sale = 1 AND examine = 1 AND is_designer = 0";
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
                
                $where .= " (`goods_name` LIKE '%".$val."%' OR `keywords` LIKE '%".$val."%' OR `goods_sn` LIKE '%".$val."%.' )";
                
                if(Db::name('keywords')->where('keyword',$val)->value('keyword'))
                    DB::name('keywords')->where('keyword',$val)->setInc('count');
                else
                    Db::name('keywords')->insert(['date'=>date('Y-m-d'),'searchengine'=>'ylt','keyword'=>$val,'count'=> 1,'source'=>'move']);
            }
            
            $where .= ')';

        }else{
            
            $where .= " AND (`goods_name` LIKE '%".$keywords."%' OR `keywords` LIKE '%".$keywords."%' OR `goods_sn` LIKE '%".$keywords."%.' )";
        }
        
        $goodsLogic = new \ylt\home\logic\GoodsLogic(); // 前台商品操作逻辑类
        $filter_goods_id = Db::name('goods')->where($where)->cache(true,YLT_CACHE_TIME)->column("goods_id");

        // 过滤帅选的结果集里面找商品
        if($brand_id || $price)// 品牌或者价格
        {
            $goods_id_1 = $goodsLogic->getGoodsIdByBrandPrice($brand_id,$price); // 根据 品牌 或者 价格范围 查找所有商品id
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_1); // 获取多个帅选条件的结果 的交集
        }

        //筛选网站自营,入驻商家,货到付款,仅看有货,促销商品
        $sel = I('sel');
        if($sel)
        {
            $goods_id_4 = $goodsLogic->getFilterSelected($sel);
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_4);
        }

        $filter_menu  = $goodsLogic->get_filter_menu($filter_param,'search'); // 获取显示的帅选菜单
        $filter_price = $goodsLogic->get_filter_price($filter_goods_id,$filter_param,'search'); // 帅选的价格期间
        
        $filter_brand = $goodsLogic->get_filter_brand($filter_goods_id,$filter_param,'search',1); // 获取指定分类下的帅选品牌        

        $count = count($filter_goods_id);
        $page = new Page($count,12);
        if($count > 0)
        {
            $goods_list = Db::name('goods')->where("goods_id", "in", implode(',', $filter_goods_id))->order("$sort $sort_asc")->limit($page->firstRow.','.$page->listRows)->select();
            $filter_goods_id2 = get_arr_column($goods_list, 'goods_id');
            if($filter_goods_id2)
                $goods_images = Db::name('goods_images')->where("goods_id", "in", implode(',', $filter_goods_id2))->cache(true)->select();
        }
        $goods_category = Db::name('goods_category')->where('is_show=1')->cache(true)->column('id,name,parent_id,level'); // 键值分类数组
        $this->assign('goods_list',$goods_list);
        $this->assign('goods_category',$goods_category);
        $this->assign('goods_images',$goods_images);  // 相册图片
        $this->assign('filter_menu',$filter_menu);  // 帅选菜单     
        $this->assign('filter_brand',$filter_brand);// 列表页帅选属性 - 商品品牌
        $this->assign('filter_price',$filter_price);// 帅选的价格期间      
        $this->assign('filter_param',$filter_param); // 帅选条件        
        $this->assign('page',$page);// 赋值分页输出
        $this->assign('sort_asc', $sort_asc == 'asc' ? 'desc' : 'asc');
        config('TOKEN_ON',false);
        if(input('is_ajax'))
            return $this->fetch('ajaxGoodsList');
        else
            return $this->fetch();
    }
    
    /**
     * @param Request $r
     * return goods_is
     * desc 通过关键字跳转链接函数
     */
    public function getLink(Request $r)
    {
        if($r->isAjax()){
            $goods_name=$_POST['q'];
            $result=Db::name('goods')->field('goods_id')->where('goods_name',$goods_name)->select();
            $goods_id=null;
            foreach($result as $value){
                $goods_id=$value;
            }
            // dump($goods_id);die;
        }
        return $goods_id;
    }
    
    /**
     * 用户收藏商品
     * @param type $goods_id
     */
    public function collect_goods()
    {
        $goods_id = I('goods_id/d');
        $goodsLogic = new \ylt\home\logic\GoodsLogic();     
        $result = $goodsLogic->collect_goods(cookie('user_id'),$goods_id);
        exit(json_encode($result));
    }
    
    
    /**
     * 用户搜索关键词
     * @param type $goods_id
     */
    public function keywords_goods()
    {
        $key = trim(input('keyword'));
        if(preg_match("/[\x7f-\xff]/", $key)){
            $result = Db::name('keywords')->field('keyword')->where('keyword','like',$key.'%')->order('count','desc')->limit(10)->select();
        }

        $arr=[];
        foreach($result as $key =>$value){
            foreach($value as $key){
                $arr[]=$key;
            }
        }
        exit(json_encode($arr));
    }


    /**
     * 新二级分类
     */
    public function giftStrategy()
    {
        
        $categoryId=input('id');
    

        $category= Db::name('goods_category')->field('id as g_id,mobile_name,image')->where('id',$categoryId)->cache(true,YLT_CACHE_TIME)->find();
        if($category){
            
            $list = Db::name('goods_category')->field('id as g_id,mobile_name,image,cat_jump')->where('parent_id',$categoryId)->order('sort_order desc')->cache(true,YLT_CACHE_TIME)->select();
            
            foreach ($list as $key =>$value){
                $list[$key]['g_id'] = $value['cat_jump'] ? $value['cat_jump'] : $value['g_id'];
            }
            
        }
    
        $this->assign('list',$list);
        $this->assign('category',$category);
    
        return $this->fetch();
    }

    /**
     * 送礼攻略--文章内容
     */
    public function articleInfo()
    {
        return $this->fetch();
    }

    /**
     * 送礼攻略--文章内容--文章评论列表
     */
    public function art_comment()
    {
        return $this->fetch();
    }
    
    /**
     * [goodsFestival 2019节日活动内容]
     * @return [type] [description]
     */
    public function goodsFestival(){

        $goods_d=Db::name('goods')->where(array('extend_cat_id'=>1084,'is_on_sale'=>1,'examine'=>1,'is_designer'=>0))->field('goods_id,cat_id,goods_name,original_img,shop_price,market_price,goods_remark')->order('sort desc')->select();

         //分享微信
        $jssdk = new JSSDKSS("wx218ea80c35624c8a", "77380763d58d20f6bbcb18d469b40f03");
        //$jssdk = new JSSDKSS("wxff94c9ef025ccb79", "08cb16a4467dd7a4c4af53507cc27a42");
        $signPackage = $jssdk->GetSignPackage();
        //dump($goods_images_list);die;
      
        $this->assign('signPackage',$signPackage);
        $this->assign('goods_d',$goods_d);
        return $this->fetch();
    }


  
    /**
     * [goodsFestival 2019节日端午活动内容]
     * @return [type] [description]
     */
    public function goodsFestivaldw(){

        $goods=Db::name('goods')->where(array('shop_price'=>168,'cat_id'=>22,'goods_id'=>5263))->field('goods_id,cat_id,goods_name,original_img,shop_price,market_price,goods_remark')->find();

        $goods_d=Db::name('goods')->where(array('shop_price'=>168,'cat_id'=>22))->where('goods_id!=5263')->field('goods_id,cat_id,goods_name,original_img,shop_price,market_price,goods_remark')->select();

        $goods_s=Db::name('goods')->where(array('shop_price'=>268,'cat_id'=>22,'goods_id'=>5267))->field('goods_id,cat_id,goods_name,original_img,shop_price,market_price,goods_remark')->find();

        $goods_s_d=Db::name('goods')->where(array('shop_price'=>268,'cat_id'=>22))->where('goods_id!=5267')->field('goods_id,cat_id,goods_name,original_img,shop_price,market_price,goods_remark')->select();

        $goods_ss=Db::name('goods')->where(array('shop_price'=>368,'cat_id'=>22,'goods_id'=>5269))->field('goods_id,cat_id,goods_name,original_img,shop_price,market_price,goods_remark')->find();

        $goods_ss_d=Db::name('goods')->where(array('shop_price'=>368,'cat_id'=>22))->where('goods_id!=5269')->field('goods_id,cat_id,goods_name,original_img,shop_price,market_price,goods_remark')->select();

        $goods_sss=Db::name('goods')->where(array('shop_price'=>568,'cat_id'=>22,'goods_id'=>5273))->field('goods_id,cat_id,goods_name,original_img,shop_price,market_price,goods_remark')->find();
        $goods_sss_d=Db::name('goods')->where(array('shop_price'=>568,'cat_id'=>22))->where('goods_id!=5273')->field('goods_id,cat_id,goods_name,original_img,shop_price,market_price,goods_remark')->select();
        $goods_get=Db::name('goods')->where(array('extend_cat_id'=>1082,'cat_id'=>1038))->field('goods_id,cat_id,goods_name,original_img,shop_price,market_price,goods_remark')->select();

        //分享微信
        $jssdk = new JSSDKSS("wx218ea80c35624c8a", "77380763d58d20f6bbcb18d469b40f03");
        //$jssdk = new JSSDKSS("wxff94c9ef025ccb79", "08cb16a4467dd7a4c4af53507cc27a42");
        $signPackage = $jssdk->GetSignPackage();
        //dump($goods_images_list);die;

        $this->assign('signPackage',$signPackage);
        $this->assign('goods_get',$goods_get);
        $this->assign('goods_sss',$goods_sss);
        $this->assign('goods_sss_d',$goods_sss_d);
        $this->assign('goods_ss',$goods_ss);
        $this->assign('goods_ss_d',$goods_ss_d);
        $this->assign('goods_s',$goods_s);
        $this->assign('goods_s_d',$goods_s_d);
        $this->assign('goods',$goods);
        $this->assign('goods_d',$goods_d);
        return $this->fetch();
    }

    /**
     * [goodsFestival_index 2020端午专区]
     * @return [type] [description]
     */
    public function goodsFestival_index(){
        $goods_a=Db::name('goods')->where("goods_id = 5989")->field('goods_id,cat_id,goods_name,original_img,shop_price,market_price,goods_remark')->find();
        $goods_b=Db::name('goods')->where("goods_id in (5963,5966,5965,5964)")->field('goods_id,cat_id,goods_name,original_img,shop_price,market_price,goods_remark')->select();
        $goods_c=Db::name('goods')->where("goods_id = 5986")->field('goods_id,cat_id,goods_name,original_img,shop_price,market_price,goods_remark')->find();
        $goods_d=Db::name('goods')->where("goods_id in (5975,5971,5957,5980)")->field('goods_id,cat_id,goods_name,original_img,shop_price,market_price,goods_remark')->select();
        $goods_e=Db::name('goods')->where("goods_id in (5263,5266,5269,5272)")->field('goods_id,cat_id,goods_name,original_img,shop_price,market_price,goods_remark')->select();

        $this->assign('goods_a',$goods_a);
        $this->assign('goods_b',$goods_b);
        $this->assign('goods_c',$goods_c);
        $this->assign('goods_d',$goods_d);
        $this->assign('goods_e',$goods_e);
        return $this->fetch();
    }

    /**
     * [goodsHengda 2019恒大专区内容]
     * @return [type] [description]
     */
    public function goodsHengda(){
        $goods_d=Db::name('goods')->where(array('extend_cat_id'=>1214,'is_on_sale'=>1,'examine'=>1,'is_designer'=>0))->field('goods_id,cat_id,goods_name,original_img,shop_price,market_price,goods_remark')->order('sort desc')->select();

         //分享微信
        $jssdk = new JSSDKSS("wx218ea80c35624c8a", "77380763d58d20f6bbcb18d469b40f03");
        //$jssdk = new JSSDKSS("wxff94c9ef025ccb79", "08cb16a4467dd7a4c4af53507cc27a42");
        $signPackage = $jssdk->GetSignPackage();
        //dump($goods_images_list);die;
      
        $this->assign('signPackage',$signPackage);
        $this->assign('goods_d',$goods_d);
        return $this->fetch();
    }

    
    /**
     * [dedicated 2019活动专区通用列表]
     * @return [type] [description]
     */
    public function dedicated_list(){
        return $this->fetch();
    }
    public function ajax_dedicated_list(){
        $p = I('p/d',1);
        $dedicated =  DB::name('dedicated');
        $list = $dedicated->Cache(true,YLT_CACHE_TIME)->where('is_show = 1')->order(['add_time'=>'DESC'])->page($p,config('PAGESIZE'))->select();
        $this->assign('list',$list);
        return $this->fetch();

    }
    /**
     * [goodsDedicated 专区通用页面]
     * @return [type] [description]
     */
    public function goodsDedicated(){
        $id = I('id/d');
        if ($id == 58) {
            $this->redirect(Url::build("/Mobile/Goods/goodsCommon"));
        }elseif (empty($id)){
            $this->error('活动id不可为空！');
        }
        $dedicated=Db::name('dedicated')->where('id',$id)->find();
        $goods = Db::name('goods')->where('goods_id','in',$dedicated['goods_id'])->order('sort desc,goods_id desc')->field('goods_id,goods_name,goods_thumb,shop_price,market_price,original_img')->select();
        $this->assign('goods',$goods);
        $this->assign('dedicated',$dedicated);
        return $this->fetch();
    }
    /**
     * [goodsCommon 2019中秋兑换专区]
     * @return [type] [description]
     */
    public function goodsCommon(){
        return $this->fetch();
    }

    /**
     * [electric 2019电器兑换专区]
     * @return [type] [description]
     */
    public function goodsElectric(){
        return $this->fetch();
    }

   /*
    *分享微信授权登录 获取个人微信的信息
    */
    public function phoneAuthfx()
    {
        $referrer_id = input('referrer_id'); // 分享人id（绑定上下级关系）
        $source_id = input('source_id');     // 临时推荐人（用于获取当前订单的分享来源，不同于绑定，可修改）
        $goods_id = input('id'); // 商品id
        
        $this-> wxPhoneLogin();

        if (session('?user')) {
            $user = array();
            $user = session('user');
            if(!empty($referrer_id) && $referrer_id!=$user['user_id']){
                if(empty($user['referrer_id'])){
                    Db::name('users')->where("user_id", $user['user_id'])->update(array('referrer_id'=>$referrer_id));
                }
            }
            //临时推荐人，点击不同用户分享的链接都会改变该字段
            if ($source_id) {
                if ($source_id!=$user['user_id']) {
                    Db::name('users')->where("user_id", $user['user_id'])->update(array('source_id'=>$source_id));
                }
            }else{
                Db::name('users')->where("user_id", $user['user_id'])->update(array('source_id'=>0));
            }
        }
        if (input('is_group') == 1) {
            $prom_id = input('prom_id');    
            $group_id = input('group_id');      
            $goods_id = input('id');    
            $this->redirect(Url::build("/Mobile/Goods/goodsGroup/goods_id/$goods_id/group_id/$group_id/prom_id/$prom_id"));
        }else{
            $this->redirect(Url::build("/Mobile/Goods/goodsInfo/id/$goods_id"));
        }
      
    }

    /*
    *节日专区分享微信授权登录 获取个人微信的信息
    */
    public function phoneAuthfx_sss()
    {
        $referrer_id = input('referrer_id'); // 分享人id
        $goods_id = input('id'); // 商品id

        $this-> wxPhoneLogin();

        if (session('?user')) {
            $user = array();
            $user = session('user');
           if(!empty($referrer_id)&&$referrer_id!=$user['user_id']){
                if(empty($user['referrer_id'])){
                    Db::name('users')->where("user_id", $user['user_id'])->update(array('referrer_id'=>$referrer_id));
                }
            }
        }
        $this->redirect(Url::build("/Mobile/Goods/goodsFestival"));
    }
  
   /*
    *二维码分享，获取上级ID
    */
    public function wxAuthfx()
    {
        $users_id = input('users_id'); // 分享人id

        $this-> wxPhoneLogin();

        if (session('?user')) {
            $user = array();
            $user = session('user');
            if(!empty($users_id)&&$users_id!=$user['user_id']){
                if(empty($user['referrer_id'])){
                    Db::name('users')->where("user_id", $user['user_id'])->update(array('referrer_id'=>$users_id));
                }
            }
        }
        $this->redirect(Url::build("/Mobile/Goods/goodsFestival"));
    }
  
   /**
     * 我的推广二维码
     */
    public function maxCard(){
        $user=session('user');
        $users_id=$user['user_id'];
        $this->qrcode2($users_id);
        $maxCard=Db::name('users')->where('user_id',$users_id)->find();
        $this->assign('maxCard',$maxCard);
        return $this->fetch();
    }

    // 二维码带logo，存进文件夹
    public function qrcode2($users_id=0){
        //带LOGO
        $url = 'http://www.yilitong.com/mobile/Goods/wxAuthfx/users_id/'.$users_id; //二维码内容 
        $errorCorrectionLevel = 'L';//容错级别  
        $matrixPointSize = 9;//生成图片大小  
        //生成二维码图片  
        Vendor('phpqrcode.phpqrcode');
        $object = new \QRcode();
        $ad = 'vendor/phpqrcode/wxcode/'.$users_id.'code.jpg';
        $object->png($url, $ad, $errorCorrectionLevel, $matrixPointSize, 2);  
        $logo = 'http://www.yilitong.com/vendor/phpqrcode/images/logo1.png';//准备好的logo图片 
        $QR = 'vendor/phpqrcode/wxcode/'.$users_id.'code.jpg';//已经生成的原始二维码图  

        if ($logo !== FALSE) {  
          $QR = imagecreatefromstring(file_get_contents($QR));  
          $logo = imagecreatefromstring(file_get_contents($logo));  
          $QR_width = imagesx($QR);//二维码图片宽度  
          $QR_height = imagesy($QR);//二维码图片高度  
          $logo_width = imagesx($logo);//logo图片宽度  
          $logo_height = imagesy($logo);//logo图片高度  
          $logo_qr_width = $QR_width / 5;  
          $scale = $logo_width/$logo_qr_width;  
          $logo_qr_height = $logo_height/$scale;  
          $from_width = ($QR_width - $logo_qr_width) / 2;  
          //重新组合图片并调整大小  
          imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,  
          $logo_qr_height, $logo_width, $logo_height);  
        }
        //输出图片  带logo图片
        imagepng($QR, 'vendor/phpqrcode/wxcode/'.$users_id.'code.png'); 
        $data['maxCard']='/vendor/phpqrcode/wxcode/'.$users_id.'code.png';
        Db::name('users')->where('user_id',$users_id)->update($data);
    }

    /**
     * [share_record 微信分享记录]
     * @return [type] [description]
     */
    public function share_record(){
        if (IS_POST) {
            $time = time();
            $end  = $time-24*60*60*3;
            $add_time['add_time'] = array('between',"$end,$time");
            $data['user_id']  = $_POST['user_id'];
            $data['goods_id'] = $_POST['goods_id'];
            $data['type'] = $_POST['type'];
            $data['add_time'] = time();
            $data['ip'] = getIP();
            if (empty($data['user_id'])) {
                $this->wxPhoneLogin();
                return array('status'=>-3,'msg'=>'用户ID为空');
            }
            if (empty($data['type'])) {
                return array('status'=>-3,'msg'=>'状态为空');
            }
            if (empty($data['goods_id'])) {
                return array('status'=>-3,'msg'=>'商品ID为空');
            }
            $arr = Db::name('goods_share_list')->insert($data);
            
            //分享规定商品领取三个口罩等活动
            $count = Db::name('goods_share_list')->where('user_id',$data['user_id'])->where("goods_id = 5862 || goods_id = 5916")->where($add_time)->count();
            if ($count) {
                $array = 10-$count;
            }
            if ($arr) {
                if ($array) {
                    if ($array != 0) {
                        return array('status'=>1,'msg'=>"恭喜您分享成功！再分享".$array."次，可申请3个医用口罩免单政策～");
                    }else{
                        return array('status'=>1,'msg'=>"恭喜您分享成功！");
                    }
                }else{
                    return array('status'=>1,'msg'=>"恭喜您分享成功！");
                }
            }else{
                return array('status'=>2,'msg'=>"分享失败");
            }
        }
    }

    /**
     * [shaer_apply 分享奖励口罩的申请记录]
     * @return [type] [description]
     */
    public function shaer_apply(){
        if (IS_POST) {
            $data['user_id']  = $_SESSION['user']['user_id'];
            $data['phone']  = $_SESSION['user']['mobile'];
            $data['add_time'] = time();
            $time = time();
            $end  = $time-24*60*60*3;
            $add_time['add_time'] = array('between',"$end,$time");
            $data['ip'] = getIP();
            $count = Db::name('goods_share_list')->where('user_id',$data['user_id'])->where("goods_id = 5862 || goods_id = 5916")->where($add_time)->count();
            if ($count >= 10 ) {
                $find = Db::name('goods_apply_list')->where(['user_id'=>$data['user_id'],'type'=>1])->find();
                if ($find) {
                    return array('status'=>1,'msg'=>"转发奖励已有申请记录");
                }
                $data['type'] = 1;
                Db::name('goods_apply_list')->insert($data);
                return array('status'=>1,'msg'=>"恭喜您！3个口罩申请成功！请耐心等待客服与您联系");
            // }elseif($count>=20){
                // $data['type'] = 2;
                // Db::name('goods_apply_list')->insert($data);
                // return array('status'=>1,'msg'=>"恭喜您！10个口罩申请成功！请耐心等待客服与您联系");
            }else{
                return array('status'=>1,'msg'=>"申请失败，未满足转发条件");
            }
        }
    }
    /**
     * [cleanCache 清除系统缓存]
     * @return [type] [description]
     */
    public function cleanCache(){  
        if (I('is_end_text')==1) {
            return array('status'=>2);
        }           
        delFile(RUNTIME_PATH .'/cache');
        delFile(RUNTIME_PATH .'/html');
        delFile(RUNTIME_PATH .'/temp');
        return array('status'=>1);
    }

    /**
     * 礼卡兑换专区
     */

    // public function liCard(){
    //     $map=array();
    //     $map['id']= array(array('neq',60),array('neq',56)); // 不等于条件
    //     //        $map['id']= array('neq',56); // 不等于条件
    //     $map['is_show']= 1; // 0不显示 1显示
    //     $lists = Db::name('Dedicated')->order('add_time desc')->where($map)->select();//兑换专区列表
    //     $j=1;
    //     foreach ($lists as $key=>&$value) {
    //         $value['num']=$j;
    //         $value['total_num']=count($lists);
    //         $j++;
    //     }
    //     $where=array();
    //     $where['id']=56;
    //     $Dedicated= Db::name('Dedicated')->order('add_time desc')->where($where)->find();//赠品商品
    //     $goods = Db::name('goods')->where('goods_id','in',$Dedicated['goods_id'])->field('goods_id,goods_name,goods_thumb,shop_price,market_price,original_img')->select();
    //     // dump($goods);die;
    //     $this->assign('goods',$goods);
    //     $this->assign('lists',$lists);
    //     return $this->fetch();

    // }



}











 class JSSDKSS {
  private $appId;
  private $appSecret;

  public function __construct($appId, $appSecret) {
    $this->appId = $appId;
    $this->appSecret = $appSecret;
  }

  public function getSignPackage() {
    $jsapiTicket = $this->getJsApiTicket();

    // 注意 URL 一定要动态获取，不能 hardcode.
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    //$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "http://" : "https://";
    $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $timestamp = time();
    $nonceStr = $this->createNonceStr();

    // 这里参数的顺序要按照 key 值 ASCII 码升序排序
    $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

    $signature = sha1($string);

    $signPackage = array(
      "appId"     => $this->appId,
      "nonceStr"  => $nonceStr,
      "timestamp" => $timestamp,
      "url"       => $url,
      "signature" => $signature,
      "rawString" => $string
    );
    ///dump($signPackage);die;
    return $signPackage; 
  }

  private function createNonceStr($length = 16) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $str = "";
    for ($i = 0; $i < $length; $i++) {
      $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
  }

  private function getJsApiTicket() {
    // jsapi_ticket 应该全局存储与更新，以下代码以写入到文件中做示例
    $data = json_decode(file_get_contents("jsapi_ticket.json"));
    if ($data->expire_time < time()) {
      $accessToken = $this->getAccessToken();
      // 如果是企业号用以下 URL 获取 ticket
      // $url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=$accessToken";
      $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
      //dump($url);
      $res = json_decode($this->httpGet($url));
      $ticket = $res->ticket;
        // dump($res);
   
      if ($ticket) {
        $data->expire_time = time() + 7000;
        $data->jsapi_ticket = $ticket;
        $fp = fopen("jsapi_ticket.json", "w");
        fwrite($fp, json_encode($data));
        fclose($fp);
      }
    } else {
      $ticket = $data->jsapi_ticket;
    }
  //dump($ticket);
    return $ticket;
  }

  private function getAccessToken() {
    // access_token 应该全局存储与更新，以下代码以写入到文件中做示例
    $data = json_decode(file_get_contents("access_token.json"));
    if ($data->expire_time < time()) {
      // 如果是企业号用以下URL获取access_token
      // $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=$this->appId&corpsecret=$this->appSecret";
      $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
      $res = json_decode($this->httpGet($url));
      $access_token = $res->access_token;
      if ($access_token) {
        $data->expire_time = time() + 7000;
        $data->access_token = $access_token;
        $fp = fopen("access_token.json", "w");
        fwrite($fp, json_encode($data));
        fclose($fp);
      }
    } else {
      $access_token = $data->access_token;
    }
    return $access_token;
  }

  private function httpGet($url) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 500);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_URL, $url);

    $res = curl_exec($curl);
    curl_close($curl);

    return $res;
  }

}
