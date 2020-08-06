<?php
/**
 * Created by PhpStorm.
 * User: lijiayi
 * Date: 2019/9/6
 * Time: 14:32
 */
namespace ylt\home\controller; 
use ylt\admin\logic\OrderLogic;
use ylt\home\logic\GoodsLogic;
use think\Controller;
use think\Url;
use think\Config;
use think\Page;
use think\Verify;
use think\Db;
use think\Request;
use think\Cache;

class RedGift extends Base {

    public $user_id = 0;
    public $user = array();
    /*
     * 处理登录后需要的参数
     */
    public function _initialize() {      
        parent::_initialize();
        $this->agen_order_status = config('agen_order_status');
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST,GET,OPTIONS,PUT,DELETE');
        header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
    }
    /**
     * [is_login 判断是否登录]
     * @return boolean [description]
     */
    public function is_login($admin_id = 0){
        if (I('admin_id')) {
            $user_id = I('admin_id');
        }else{
            $user_id = $admin_id;
        }
        if (empty($user_id)) {
            exit(json_encode(array('result'=>'-11','info'=>'请先登陆')));
        }
        return $user_id;
        // exit(json_encode(array('user_id'=>$user_id,'user'=>$user)));
    }
    /**
    *   登录处理
    */
    public function red_login(){
        $user_name = trim(I('post.user_name'));
        $password = trim(I('post.password'));
        $res = $this->user_login($user_name,$password);
        if($res['status'] == 1){
            session('hongli',$res['result']);
            setcookie('hongli_id',$res['result']['admin_id'],null,'/');
            $user_name = empty($res['result']['user_name']) ? $user_name : $res['result']['user_name'];
            setcookie('user_name',urlencode($user_name),null,'/');
            setcookie('cn',0,time()-3600,'/');
        }
        exit(json_encode($res));
    }
    public function user_login($username,$password){
        $result = array();
        if(!$username || !$password){
           $result= array('status'=>0,'msg'=>'请填写账号或密码');
        }
        $user = Db::name('red_user')->where("user_name",$username)->find();
        if(!$user){
           $result = array('status'=>-1,'msg'=>'账号不存在!');
        }elseif(encrypt($password) != $user['password']){
           $result = array('status'=>-2,'msg'=>'密码错误!');
        }else{
            // 更新用户的登记记录
            Db::name('red_user')->where("admin_id", $user['admin_id'])->update(array('last_login'=>time(),'last_ip'=>getIP()));
           $result = array('status'=>1,'msg'=>'登陆成功','result'=>$user);
        }
        return $result;
    }
    /**
     * [logout 用户退出登录]
     * @return [type] [description]
     */
    public function logout(){
        setcookie('user_name','',time()-3600,'/');
        setcookie('cn','',time()-3600,'/');
        setcookie('hongli_id','',time()-3600,'/');
        session_unset();
        session_destroy();
        exit(json_encode(array('result'=>'12','info'=>'退出成功')));
    }

    /*登录相关结束*/
    /*========================================================================*/
    /*首页相关开始*/


    /**
     * [index 首页]
     * @return [type] [description]
     */
    public function index(){
        $user_id = $this->is_login(I('admin_id'));
        $user = Db::name('red_user')->where('admin_id',$user_id)->field('admin_id,user_name,head_pic')->find();
        //购物车数量
        $cart_count = Db::name('red_cart')->where('user_id',$user_id)->count();
        //Brand
        $brand_list = Db::name('ad')->where('pid = 53 and enabled=1')->field('ad_code,ad_link')->select();
        $field = "goods_id,goods_name,goods_thumb,shop_price,market_price,group_price,red_supplier_id,brand_id";
        //最新商品
        $goods_list = Db::name('red_goods')->field($field)->where('examine = 1 and is_recommend =1 and is_delete = 0')->order('goods_id desc')->limit('20')->select();

        //品牌折扣区
        $goods_quality_list = Db::name('red_goods')->field($field)->where('examine = 1 and is_recommend =1 and is_delete = 0 and cat_id = 1126')->order('goods_id desc')->limit('3')->select();
        foreach ($goods_quality_list as $key => $value) {
            if (!empty($value['red_supplier_id'])) {
                $value['is_quality'] = Db::name('redsupplier_user')->where("red_admin_id",$value['red_supplier_id'])->value('is_quality');
            }else{
                $value['is_quality'] = 0;
            }
            if (!empty($value['brand_id'])) {
                $value['brand_name'] = Db::name('brand')->where("id",$value['brand_id'])->value('name');
            }else{
                $value['brand_name'] = '';
            }
            $goods_quality_lists[]=$value;
        }


        $rs=array('result'=>'1','info'=>'请求成功','user'=>$user,'cart_count'=>$cart_count,'goods_list'=>$goods_list,'brand_list'=>$brand_list,'goods_quality_list'=>$goods_quality_lists);
        exit(json_encode($rs));
    }

    /**
     * [suggest 首页建议提交]
     * @return [type] [description]
     */
    public function suggest(){
        $data = I('');
        if ($data['images']) {      //图片上传
            foreach ($data['images'] as $key => $value) {
                $type = $data['images_type'][$key]; 
                $base64 = $value;
                $IMG = base64_decode($base64);   //base64位转码，还原图片
                $path ='public/upload/hongli/';
                if (!file_exists($path)){
                    mkdir($path,0777,true);
                }//如果地址不存在，创建地址
                $u=uniqid().date("ymdHis",time()).rand(111,999);
                $picname=$path.$u.$type;
                file_put_contents($picname,$IMG);
                $picname2[]=$u.$type;
            }
            $suggest['images'] = implode(',',$picname2);
        }
        if ($data['txt']) {         //文件上传
            $type = $data['txt_type']; 
            $base64 = $data['txt'];
            $IMG = base64_decode($base64);   //base64位转码
            $path ='public/upload/hongli/';
            if (!file_exists($path)){
                mkdir($path,0777,true);
            }//如果地址不存在，创建地址
            $u=uniqid().date("ymdHis",time()).rand(111,999);
            $picname=$path.$u.$type;
            file_put_contents($picname,$IMG);
            $suggest['txt']=$u.$type;
        }
        $suggest['user_id'] = $data['admin_id'];
        $suggest['username'] = Db::name('red_user')->where('admin_id',$data['admin_id'])->value('user_name');
        $suggest['content'] = $data['content'];
        $suggest['ip_address'] = $_SERVER['REMOTE_ADDR'];
        $suggest['add_time'] = time();
        $d = Db::name('red_comment')->insert($suggest);
        if ($d) {
            exit(json_encode(array('result'=>'1','info'=>'提交成功，感谢您的反馈！')));
        }else{
            exit(json_encode(array('result'=>'-1','info'=>'提交失败！')));
        }
    }
    
    // /**
    //  * [Price_search 固定的区间价格搜索]
    //  */
    // public function Price_search(){
    //     $this->is_login(I('admin_id'));
    //     $price = I('price');
    //     if ($price)// 价格查询
    //     {
    //         $price = explode('-', $price);
    //         $where = "market_price >= :market_price1 and  market_price <= :market_price2 and examine =1 and is_on_sale = 1";
    //         $bind['market_price1'] = $price[0];
    //         $bind['market_price2'] = $price[1];
    //         $goods = Db::name('red_goods')->where($where)->bind($bind)->field("goods_id,goods_name,goods_thumb,shop_price,market_price")->select();
    //     }
    //     $rs=array('result'=>'1','info'=>'请求成功','goods'=>$goods);
    //     exit(json_encode($rs));
    // }
    /**
     * 获取红礼商品一二三级分类
     * @return type
     */
    public function get_category(){
        $arr = $result = array();
        // $cat_list = Db::name('red_goods_category')->where("is_show = 1")->order('id')->cache(true)->select();//所有分类
        $cat_list = Db::name('goods_category')->where("id = 1123 or parent_id = 1123 or is_show = 1")->order('id')->cache(true)->select();//所有分类
        foreach ($cat_list as $val){
            if($val['level'] == 2){
                $arr[$val['parent_id']][] = $val;
            }
            if($val['level'] == 3){
                $crr[$val['parent_id']][] = $val;
            }
            if($val['level'] == 1){ 
                $tree[] = $val;
            }
        }
        foreach ($arr as $k=>$v){
            foreach ($v as $kk=>$vv){
                $arr[$k][$kk]['sub_menu'] = empty($crr[$vv['id']]) ? array() : $crr[$vv['id']];
            }
        }
        foreach ($tree as $val){
            $val['tmenu'] = empty($arr[$val['id']]) ? array() : $arr[$val['id']];
            $result[$val['id']] = $val;
        }
        return $result;
    }
    /**
     * 传入当前分类 如果当前是 2级 找一级
     * 如果当前是 3级 找2 级 和 一级
     * @param  $goodsCate
     */
    function get_goods_cate(&$goodsCate)
    {
        if (empty($goodsCate)) return array();
        $cateAll = $this->get_category();
        if ($goodsCate['level'] == 1) {
            $cateArr = $cateAll[$goodsCate['id']]['tmenu'];
            $goodsCate['parent_name'] = $goodsCate['name'];
            $goodsCate['select_id'] = 0;
        } elseif ($goodsCate['level'] == 2) {
            $cateArr = $cateAll[$goodsCate['parent_id']]['tmenu'];
            $goodsCate['parent_name'] = $cateAll[$goodsCate['parent_id']]['name'];//顶级分类名称
            $goodsCate['open_id'] = $goodsCate['id'];//默认展开分类
            $goodsCate['select_id'] = 0;
        } else {
            $parent = Db::name('goods_category')->where("id", $goodsCate['parent_id'])->order('`sort_order` desc')->find();//父类
            $cateArr = $cateAll[$parent['parent_id']]['tmenu'];
            $goodsCate['parent_name'] = $cateAll[$parent['parent_id']]['name'];//顶级分类名称
            $goodsCate['open_id'] = $parent['id'];
            $goodsCate['select_id'] = $goodsCate['id'];//默认选中分类
        }
        return $cateArr;
    }
    /**
     * 获取某个商品分类的 下级 的 id
     * @param type $cat_id
     */
    function getCatGrandsons ($cat_id)
    {
        $GLOBALS['catGrandson'] = array();
        $GLOBALS['category_id_arr'] = array();
        // 先把自己的id 保存起来
        $GLOBALS['catGrandson'][] = $cat_id;
        // 把整张表找出来
        $GLOBALS['category_id_arr'] =  Db::name('goods_category')->cache(true,YLT_CACHE_TIME)->column('id,parent_id');
        // $GLOBALS['category_id_arr'] =  Db::name('red_goods_category')->cache(true,YLT_CACHE_TIME)->column('id,parent_id');
        // 先把所有下级找出来
        // $son_id_arr =  Db::name('red_goods_category')->where("parent_id", $cat_id)->cache(true,YLT_CACHE_TIME)->column('id');
        $son_id_arr =  Db::name('goods_category')->where("parent_id", $cat_id)->cache(true,YLT_CACHE_TIME)->column('id');
        foreach($son_id_arr as $k => $v)
        {
            getCatGrandson2($v);
        }
        return $GLOBALS['catGrandson'];
    }

    /**
     * @param  $brand_id 帅选品牌id
     * @param  $price 帅选价格
     * @return array|mixed
     */
    function getGoodsIdByBrandPrices($brand_id, $price)
    {
        if (empty($brand_id) && empty($price))
            return array();

        $where = " 1 = 1 ";
        $where .= " and examine = 1";
        $bind = array();
        if ($brand_id) // 品牌查询
        {
            $brand_id_arr = explode('_', $brand_id);
            $where .= " and brand_id in(:brand_id_arr)";
            $bind['brand_id_arr'] = implode(',', $brand_id_arr);
        }
        if ($price)// 价格查询
        {
            $price = explode('-', $price);
            $where .= " and group_price >= :shop_price1 and  group_price <= :shop_price2 ";
            $bind['shop_price1'] = $price[0];
            $bind['shop_price2'] = $price[1];
        }
        $arr = Db::name('red_goods')->where($where)->bind($bind)->column('goods_id');
        return $arr ? $arr : array();
    }

    /**
     * * 筛选的价格期间
     * @param $goods_id_arr 帅选的分类id
     * @param $filter_param
     * @param $action
     * @param int $c 分几段 默认分5 段
     * @return array
     */
    function get_filter_prices($goods_id_arr, $filter_param, $action, $c = 10)
    {
        if (!empty($filter_param['price'])){
            return array();
        }
        $goods_id_str = implode(',', $goods_id_arr);
        $goods_id_str = $goods_id_str ? $goods_id_str : '0';
        $priceList = Db::name('red_goods')->where("goods_id", "in", $goods_id_str)->column('group_price');  
        rsort($priceList);
        $max_price = (int)$priceList[0];

        $psize = ceil($max_price / $c); // 每一段累积的价钱
        $parr = array();
        for ($i = 0; $i < $c; $i++) {
            $start = $i * $psize;
            $end = $start + $psize;

            // 如果没有这个价格范围的商品则不列出来
            $in = false;
            foreach ($priceList as $k => $v) {
                if ($v > $start && $v <= $end)
                    $in = true;
            }
            if ($in == false)
                continue;

            $filter_param['price'] = "{$start}-{$end}";
            if ($i == 0){
                $parr[] = array('value' => "0-{$end}元", 'href' => urldecode(Url::build("Goods/$action", $filter_param, '')));
            }else{
                $parr[] = array('value' => "{$start}-{$end}元", 'href' => urldecode(Url::build("Goods/$action", $filter_param, '')));
            }
        }
        return $parr;
    }

    /**
     * @param $goods_id_arr
     * @param $filter_param
     * @param $action
     * @param int $mode 0  返回数组形式  1 直接返回result
     * @return array|mixed 这里状态一般都为1 result 不是返回数据 就是空
     * 获取 商品列表页帅选品牌
     */
    public function get_filter_brands($goods_id_arr, $filter_param, $action, $mode = 0)
    {
        if (!empty($filter_param['brand_id']))
            return array();;
        $goods_id_str = implode(',', $goods_id_arr);
        $goods_id_str = $goods_id_str ? $goods_id_str : '0';
        $list_brand = Db::query("SELECT * FROM `ylt_brand` WHERE ( id IN ( SELECT brand_id FROM ylt_red_goods WHERE brand_id > 0 AND goods_id IN ($goods_id_str)))  AND is_hot = 1 LIMIT 30 ;");
        foreach ($list_brand as $k => $v) {
            // 帅选参数
            $filter_param['brand_id'] = $v['id'];
            // $list_brand[$k]['href'] = urldecode(Url::build("Goods/$action", $filter_param, ''));
        }
        if ($mode == 1) return $list_brand;
        return array('status' => 1, 'msg' => '', 'result' => $list_brand,'filter_param' => $filter_param);
    }


    /**
     * 商品分类列表页
     */
    public function goodsList(){ 
        // $get_category = $this->get_category();
        //只显示有商品的二级和三级分类 两个参数 分类表名及商品表名
        $get_isgoods_category = get_isgoods_category('goods_category','red_goods');

        $filter_param = array();                    // 筛选数组                        
        $id = I('get.cat_id/d');                    // 当前分类id
        $sort = I('get.sort','sort');               // 排序
        $sort_asc = I('get.sort_asc','desc');       // 排序
        $brand_id = I('get.brand_id/d',0);          // 品牌
        $price = I('get.price','');                 // 价钱

        $start_price = trim(I('post.start_price','0')); // 输入框价钱
        $end_price = trim(I('post.end_price','0')); // 输入框价钱
        if($start_price && $end_price) $price = $start_price.'-'.$end_price.'元'; // 如果输入框有价钱 则使用输入框的价钱

        $filter_param['id'] = $id;                              //加入筛选条件中          
        $brand_id  && ($filter_param['brand_id'] = $brand_id);  //加入筛选条件中
        $price  && ($filter_param['price'] = $price);           //加入筛选条件中 
        $goods_list = array();

        // 分类商品
        $cat_id_arr = $this->getCatGrandsons ($id);
        $filter_goods_id = Db::name('red_goods')->where(['examine'=>1,'is_delete'=>0,'cat_id'=>['in',implode(',', $cat_id_arr)]])->cache(true)->column("goods_id");

        // 过滤筛选的结果集里面找商品        
        if($brand_id || $price)// 品牌或者价格
        {
            $goods_id_1 = $this->getGoodsIdByBrandPrices($brand_id,$price); // 根据 品牌 或者 价格范围 查找所有商品id    
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_1); // 获取多个筛选条件的结果 的交集
        }


        $filter_price = $this->get_filter_prices($filter_goods_id,$filter_param,'goodsList'); // 筛选的价格期间         
        $filter_brand = $this->get_filter_brands($filter_goods_id,$filter_param,'goodsList',1); // 获取指定分类下的筛选品牌    

        $count = count($filter_goods_id);
        $page_html = input('page_html')?input('page_html'):1; //前端页码
        $count_html = 20;
        if ($page_html) {
            $firstRow = ($page_html-1) * $count_html;
        }
        $field = "goods_id,cat_id,goods_name,goods_thumb,shop_price,market_price,goods_remark,comment_count,group_price,red_supplier_id";
        if($count > 0)
        {
            $goods_list = Db::name('red_goods')->where("goods_id","in", implode(',', $filter_goods_id))->order("$sort $sort_asc")->field($field)->limit($firstRow.','.$count_html)->select();
            //获取一级分类，判断是否为家居分类
            foreach ($goods_list as $key => $value) {
                if (!empty($value['red_supplier_id'])) {
                    $value['is_quality'] = Db::name('redsupplier_user')->where("red_admin_id",$value['red_supplier_id'])->value('is_quality');
                }else{
                    $value['is_quality'] = 0;
                }
                $parent_path= Db::name('goods_category')->where('id', $value['cat_id'])->value('parent_id_path');
                $value['parent_id_path']=substr($parent_path,4);
                $goods_lists[]=$value;
            }
        }else if (!$id) {
            $filter_goods_id = Db::name('red_goods')->where(['examine'=>1,'is_delete'=>0])->order("$sort $sort_asc")->field($field)->column("goods_id");
            $count =count($filter_goods_id);
            $goods_list = Db::name('red_goods')->where("goods_id","in", implode(',', $filter_goods_id))->order("$sort $sort_asc")->field($field)->limit($firstRow.','.$count_html)->select();
            //获取一级分类，判断是否为家居分类
            foreach ($goods_list as $key => $value) {
                if (!empty($value['red_supplier_id'])) {
                    $value['is_quality'] = Db::name('redsupplier_user')->where("red_admin_id",$value['red_supplier_id'])->value('is_quality');
                }else{
                    $value['is_quality'] = 0;
                }
                $parent_path= Db::name('goods_category')->where('id', $value['cat_id'])->value('parent_id_path');
                $value['parent_id_path']=substr($parent_path,4);
                $goods_lists[]=$value;
            }
        }
        $rs=array('result'=>'1','info'=>'请求成功','get_category'=>$get_isgoods_category,'goods_list'=>$goods_lists,'cat_id'=>$id,'count'=>$count,'filter_price'=>$filter_price,'filter_brand'=>$filter_brand);
        exit(json_encode($rs));
    }


    /**
     * [search 搜索框关键词搜索]
     * @return [type] [description]
     */
    // public function search(){
    //     $this->is_login(I('admin_id'));
    //     $keywords = I('keywords');
    //     if ($keywords) {
    //         $where = "`goods_name` LIKE '%".$keywords."%' OR `keywords` LIKE '%".$keywords."%' and examine =1 and is_delete = 0";
    //         $goods = Db::name('red_goods')->where($where)->field("goods_id,goods_name,goods_thumb,shop_price,market_price,group_price")->select();
    //     }
    //     $rs=array('result'=>'1','info'=>'请求成功','goods'=>$goods);
    //     exit(json_encode($rs));
    // }
    
    /**
     * 商品搜索列表页
     */
    public function search()
    {
        $this->is_login(I('admin_id'));
        
        $filter_param = array(); // 筛选数组                        
        $id = I('get.id',0); // 当前分类id
        $brand_id = I('brand_id',0);
        $sort = I('sort','goods_id'); // 排序 goods_id
        $sort_asc = I('sort_asc','desc'); // 排序 asc
        $price = I('price',''); // 价钱
        $start_price = trim(I('post.start_price','0')); // 输入框价钱
        $end_price = trim(I('post.end_price','0')); // 输入框价钱
        if($start_price && $end_price) $price = $start_price.'-'.$end_price.'元'; // 如果输入框有价钱 则使用输入框的价钱
        $keywords = urldecode(trim(I('keywords',''))); // 关键字搜索
        empty($keywords) && $keywords = '电饭煲';

        $id && ($filter_param['id'] = $id); //加入筛选条件中                       
        $brand_id  && ($filter_param['brand_id'] = $brand_id); //加入筛选条件中        
        $price  && ($filter_param['price'] = $price); //加入筛选条件中
        $keywords  && ($_GET['keywords'] = $filter_param['keywords'] = $keywords); //加入筛选条件中
               
        $where  = " examine = 1 and is_delete = 0";
        
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
                
                // if(Db::name('keywords')->where('keyword',$val)->value('keyword'))
                //     DB::name('keywords')->where('keyword',$val)->setInc('count');
                
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
        
        $search_goods = Db::name('red_goods')->where($where)->column('goods_id,cat_id');
        $filter_goods_id = array_keys($search_goods);
        $filter_cat_id = array_unique($search_goods); // 分类需要去重
        if($filter_cat_id)        
        {
            $cateArr = Db::name('goods_category')->where("id","in",implode(',', $filter_cat_id))->limit(20)->select();
            $tmp = $filter_param;
            foreach($cateArr as $k => $v)            
            {
                $tmp['id'] = $v['id'];
            }                
        }                        
        // 过滤筛选的结果集里面找商品        
        if($brand_id || $price)// 品牌或者价格
        {
            $goods_id_1 = $this->getGoodsIdByBrandPrices($brand_id,$price); // 根据 品牌 或者 价格范围 查找所有商品id    
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_1); // 获取多个筛选条件的结果 的交集
        }
        
        $filter_price = $this->get_filter_prices($filter_goods_id,$filter_param,'search'); // 筛选的价格期间         
        $filter_brand = $this->get_filter_brands($filter_goods_id,$filter_param,'search',1); // 获取指定分类下的筛选品牌        
                         
        $count = count($filter_goods_id);
        $page_html = input('page_html')?input('page_html'):1; //前端页码
        $count_html = 20;
        if ($page_html) {
            $firstRow = ($page_html-1) * $count_html;
        }
        if($count > 0)
        {
            $goods_list = Db::name('red_goods')->where(['examine'=>1,'is_delete' => 0,'goods_id'=>['in',implode(',', $filter_goods_id)]])->order("$sort $sort_asc")->limit($firstRow.','.$count_html)->select();
            foreach ($goods_list as $key => $value) {
                if (!empty($value['red_supplier_id'])) {
                    $value['is_quality'] = Db::name('redsupplier_user')->where("red_admin_id",$value['red_supplier_id'])->value('is_quality');
                }else{
                    $value['is_quality'] = 0;
                }
                $goods_lists[]=$value;
            }

            $filter_goods_id2 = get_arr_column($goods_list, 'goods_id');
        }    

        $rs=array('result'=>'1','info'=>'请求成功','goods_list'=>$goods_lists,'filter_brand'=>$filter_brand,'filter_price'=>$filter_price,'cateArr'=>$cateArr,'filter_param'=>$filter_param,'cat_id'=>$id,'count'=>$count);
        exit(json_encode($rs));
    }

    /**
     * [goodsInfo 商品详情页]
     * @return [type] [description]
     */
    public function goodsInfo(){
        $user_id = $this->is_login(I('admin_id'));
        $goods_id = I("goods_id/d");
        $goods = Db::name('red_goods')->alias('g')->join('redsupplier_user u','g.red_supplier_id = u.red_admin_id')->join('brand b','g.brand_id = b.id')->where("g.goods_id",$goods_id)->field('g.*,u.is_quality,b.name as brand_name')->find();
        if (empty($goods)) {
            $goods = Db::name('red_goods')->where("goods_id",$goods_id)->find();
        }

        if(empty($goods) || ($goods['examine'] == 0)){
            exit(json_encode(array('result'=>'-11','info'=>'该商品已经下架')));
        }

        $goodsCate = Db::name('GoodsCategory')->where("id", $goods['cat_id'])->find();// 当前分类
        $cateArr = $this->get_goods_cate($goodsCate);
        if ($cateArr[0]['id'] == 1124) {   //上级分类是品牌折扣区
            $goods['is_discount'] = 1;
        }

        $goods_images_list = Db::name('RedGoodsImages')->where("goods_id", $goods_id)->order('img_id desc')->select(); // 商品 图册
        $filter_spec = $this->red_get_spec($goods_id);
        // $filter_spec = $this->get_spec($goods_id);
               
        if ($goods['keywords']=="") {
            $goods['keywords']=$goods['goods_name'];
        }
        if ($goods['title']=="") {
            $goods['title']=$goods['goods_name'];
        }
        if ($goods['description']=="") {
            $goods['description']=$goods['goods_name'];
        }
        //判断是否为家居分类
        // $parent_path= Db::name('red_goods_category')->where('id', $goods['cat_id'])->value('parent_id_path');
        $parent_path= Db::name('goods_category')->where('id', $goods['cat_id'])->value('parent_id_path');
        // $goods['parent_id_path'] = substr($parent_path,2,2);
        $goods['parent_id_path']=substr($parent_path,4);

        // $spec_goods_price  = Db::name('red_goods_price')->where("goods_id", $goods_id)->column("key,price,store_count,quantity"); // 规格 对应 价格 库存表 起订量
        $spec_goods_price  = Db::name('goods_price')->where("goods_id", $goods_id)->column("key,price,store_count,quantity"); // 规格 对应 价格 库存表 起订量
        
        $recommend_goods =  Db::name('red_goods')->field('goods_id,goods_name,goods_thumb,shop_price,market_price,cat_id,group_price')->where('is_recommend = 1 and examine =1 and is_delete = 0')->order('goods_id desc')->limit(5)->select(); // 推荐
        foreach ($recommend_goods as $key => $value) {
            // $parent_path= Db::name('red_goods_category')->where('id', $value['cat_id'])->value('parent_id_path');
            $parent_path= Db::name('goods_category')->where('id', $value['cat_id'])->value('parent_id_path');
            $value['parent_id_path']=substr($parent_path,2,2);
            $recommend_goods_[]=$value;
        }
        $users_address = Db::name('red_user')->where('admin_id',$user_id)->find();

        $rs=array('result'=>'1','info'=>'请求成功','spec_goods_price'=>$spec_goods_price,'filter_spec'=>$filter_spec,'goods_images_list'=>$goods_images_list,'goods'=>$goods,'users_address'=>$users_address,'recommend_goods'=>$recommend_goods_);
        exit(json_encode($rs));
    }

    /**
     * 获取商品规格
     */
    public function red_get_spec($goods_id)
    {
        $this->is_login(I('admin_id'));
        //商品规格 价钱 库存表 找出 所有 规格项id
        $keys = Db::name('RedGoodsPrice')->where("goods_id", $goods_id)->getField("GROUP_CONCAT(`key` SEPARATOR '_') ");
        $filter_spec = array();
        if ($keys) {
            $specImage = Db::name('RedSpecImage')->where(['goods_id'=>$goods_id,'src'=>['<>','']])->column("spec_image_id,src");// 规格对应的 图片表， 例如颜色
            $keys = str_replace('_', ',', $keys);
            // $sql = "SELECT a.name,a.order,b.* FROM __PREFIX__red_spec AS a INNER JOIN __PREFIX__red_spec_item AS b ON a.id = b.spec_id WHERE b.id IN($keys) ORDER BY b.id";
            $sql = "SELECT a.name,a.order,b.* FROM __PREFIX__spec AS a INNER JOIN __PREFIX__spec_item AS b ON a.id = b.spec_id WHERE b.id IN($keys) ORDER BY b.id";
            $filter_spec2 = Db::query($sql);
            foreach ($filter_spec2 as $key => $val) {
                $filter_spec[$val['name']][] = array(
                    'item_id' => $val['id'],
                    'item' => $val['item'],
                    'src' => $specImage[$val['id']],
                );
            }
        }
        return $filter_spec;
    }

    /**
     * [brandList 品牌列表 更多品牌]
     * @return [type] [description]
     */
    public function brandList(){
        $brandList = Db::name("brand")
            ->alias('b')
            ->join('red_goods g','b.id=g.brand_id')
            ->where("b.logo != '' and b.is_hot = 1 and g.examine = 1")
            ->group('b.id')
            ->order('b.sort desc')
            ->select();
        $nameList = array();
        foreach($brandList as $k => $v)
        {
            $name = getFirstCharter($v['name']) .'  --'. $v['name']; // 前面加上拼音首字母
            $nameList[] = $v['name'] = $name;
            $brandList[$k] = $v;
        }
        array_multisort($nameList,SORT_STRING,SORT_ASC,$brandList);

        $brand_list=[];
        foreach ($brandList as $key => $value) {
            switch (mb_substr($value['name'],0,1,'utf-8')) {
                case 'A':
                    $value['name']=mb_substr($value['name'],strpos($value['name'],"--",0)+2,NULL,'UTF-8');
                    $brand_list[0][]=$value;
                    break;
                case 'B':
                    $value['name']=mb_substr($value['name'],strpos($value['name'],"--",0)+2,NULL,'UTF-8');
                    $brand_list[1][]=$value;
                    break;
                case 'C':
                    $value['name']=mb_substr($value['name'],strpos($value['name'],"--",0)+2,NULL,'UTF-8');
                    $brand_list[2][]=$value;
                    break;
                case 'D':
                    $value['name']=mb_substr($value['name'],strpos($value['name'],"--",0)+2,NULL,'UTF-8');
                    $brand_list[3][]=$value;
                    break;
                case 'E':
                    $value['name']=mb_substr($value['name'],strpos($value['name'],"--",0)+2,NULL,'UTF-8');
                    $brand_list[4][]=$value;
                    break;
                case 'F':
                    $value['name']=mb_substr($value['name'],strpos($value['name'],"--",0)+2,NULL,'UTF-8');
                    $brand_list[5][]=$value;
                    break;
                case 'G':
                    $value['name']=mb_substr($value['name'],strpos($value['name'],"--",0)+2,NULL,'UTF-8');
                    $brand_list[6][]=$value;
                    break;
                case 'H':
                    $value['name']=mb_substr($value['name'],strpos($value['name'],"--",0)+2,NULL,'UTF-8');
                    $brand_list[7][]=$value;
                    break;
                case 'I':
                    $value['name']=mb_substr($value['name'],strpos($value['name'],"--",0)+2,NULL,'UTF-8');
                    $brand_list[8][]=$value;
                    break;
                case 'J':
                    $value['name']=mb_substr($value['name'],strpos($value['name'],"--",0)+2,NULL,'UTF-8');
                    $brand_list[9][]=$value;
                    break;
                case 'K':
                    $value['name']=mb_substr($value['name'],strpos($value['name'],"--",0)+2,NULL,'UTF-8');
                    $brand_list[10][]=$value;
                    break;
                case 'L':
                    $value['name']=mb_substr($value['name'],strpos($value['name'],"--",0)+2,NULL,'UTF-8');
                    $brand_list[11][]=$value;
                    break;
                case 'M':
                    $value['name']=mb_substr($value['name'],strpos($value['name'],"--",0)+2,NULL,'UTF-8');
                    $brand_list[12][]=$value;
                    break;
                case 'N':
                    $value['name']=mb_substr($value['name'],strpos($value['name'],"--",0)+2,NULL,'UTF-8');
                    $brand_list[13][]=$value;
                    break;
                case 'O':
                    $value['name']=mb_substr($value['name'],strpos($value['name'],"--",0)+2,NULL,'UTF-8');
                    $brand_list[14][]=$value;
                    break;
                case 'P':
                    $value['name']=mb_substr($value['name'],strpos($value['name'],"--",0)+2,NULL,'UTF-8');
                    $brand_list[15][]=$value;
                    break;
                case 'Q':
                    $value['name']=mb_substr($value['name'],strpos($value['name'],"--",0)+2,NULL,'UTF-8');
                    $brand_list[16][]=$value;
                    break;
                case 'R':
                    $value['name']=mb_substr($value['name'],strpos($value['name'],"--",0)+2,NULL,'UTF-8');
                    $brand_list[17][]=$value;
                    break;
                case 'S':
                    $value['name']=mb_substr($value['name'],strpos($value['name'],"--",0)+2,NULL,'UTF-8');
                    $brand_list[18][]=$value;
                    break;
                case 'T':
                    $value['name']=mb_substr($value['name'],strpos($value['name'],"--",0)+2,NULL,'UTF-8');
                    $brand_list[19][]=$value;
                    break;
                case 'U':
                    $value['name']=mb_substr($value['name'],strpos($value['name'],"--",0)+2,NULL,'UTF-8');
                    $brand_list[20][]=$value;
                    break;
                case 'V':
                    $value['name']=mb_substr($value['name'],strpos($value['name'],"--",0)+2,NULL,'UTF-8');
                    $brand_list[21][]=$value;
                    break;
                case 'W':
                    $value['name']=mb_substr($value['name'],strpos($value['name'],"--",0)+2,NULL,'UTF-8');
                    $brand_list[22][]=$value;
                    break;
                case 'X':
                    $value['name']=mb_substr($value['name'],strpos($value['name'],"--",0)+2,NULL,'UTF-8');
                    $brand_list[23][]=$value;
                    break;
                case 'Y':
                    $value['name']=mb_substr($value['name'],strpos($value['name'],"--",0)+2,NULL,'UTF-8');
                    $brand_list[24][]=$value;
                    break;
                case 'Z':
                    $value['name']=mb_substr($value['name'],strpos($value['name'],"--",0)+2,NULL,'UTF-8');
                    $brand_list[25][]=$value;
                    break;
                case ' ':
                    $value['name']=mb_substr($value['name'],strpos($value['name'],"--",0)+2,NULL,'UTF-8');
                    $brand_list[26][]=$value;
                    break;
            }
        }
        exit(json_encode(array('result'=>'1','info'=>'请求成功','brand_list'=>$brand_list)));
    }
    /**
     * 首页品牌内页展示列表页
     */
    public function brandList_goods(){
        $key = md5($_SERVER['REQUEST_URI'].I('start_price').'_'.I('end_price'));
        $html =  Cache::get($key);  //读取缓存
        if(!empty($html))
        {
            return $html;
        }

        $filter_param = array(); // 筛选数组
        $brand_id = I('get.brand_id/d',0);
        $sort = I('get.sort','goods_id'); // 排序
        $sort_asc = I('get.sort_asc','desc'); // 排序
        $brand_id  && ($filter_param['brand_id'] = $brand_id); //加入筛选条件中
        $goodsLogic = new GoodsLogic(); // 前台商品操作逻辑类
        $filter_goods_id = Db::name('red_goods')->where(['examine'=>1,'is_designer'=>0,'brand_id'=>$brand_id])->cache(true)->column("goods_id");
        // 过滤筛选的结果集里面找商品
        if($brand_id)// 品牌或者价格
        {
            $goods_id_1 = $goodsLogic->getGoodsIdByBrandPrice_red($brand_id,$price); // 根据 品牌 或者 价格范围 查找所有商品id
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_1); // 获取多个筛选条件的结果 的交集
        }
        $count = count($filter_goods_id);
        $page_html = input('page_html')?input('page_html'):1; //前端页码
        $count_html = 12;
        if ($page_html) {
            $firstRow = ($page_html-1) * $count_html;
        }

        // $count = count($filter_goods_id);
        // $page = new Page($count,12);
        if($count > 0)
        {
            $goods_list = Db::name('red_goods')->where("goods_id","in", implode(',', $filter_goods_id))->order("$sort $sort_asc")->limit($firstRow.','.$count_html)->select();
            $filter_goods_id2 = get_arr_column($goods_list, 'goods_id');
        }
        exit(json_encode(array('result'=>'1','info'=>'请求成功','goods_list'=>$goods_list,'count'=>$count)));
    }


    /*商品相关结束*/
    /*========================================================================*/
    /*个人中心相关开始*/

    /**
     * [user_index 用户中心-账号与安全]
     * @return [type] [description]
     */
    public function user_index(){
        $user_id = $this->is_login(I('admin_id'));
        $users = Db::name('red_user')->where('admin_id',$user_id)->find();
        if ($users['head_pic']) {
            $users['head_pic'] = "public/upload/hongli/head_pic/".$users['head_pic'];
        }
        $rs=array('result'=>'1','info'=>'请求成功','users'=>$users);
        exit(json_encode($rs));
    }
    /**
     * [head_pic 头像上传]
     * @return [type] [description]
     */
    public function head_pic(){
        $data = I('');
        if ($data['head_pic']) {         //头像上传
            $type = $data['head_pic_type']; 
            $base64 = $data['head_pic'];
            $IMG = base64_decode($base64);   //base64位转码
            $path ='public/upload/hongli/head_pic/';
            if (!file_exists($path)){
                mkdir($path,0777,true);
            }//如果地址不存在，创建地址
            $u=uniqid().date("ymdHis",time()).rand(111,999);
            $picname=$path.$u.$type;
            file_put_contents($picname,$IMG);
            $head_pic['head_pic']=$u.$type;
        }
        $head_pic['save_time'] = time();
        $d = Db::name('red_user')->where('admin_id',$data['admin_id'])->update($head_pic);
        if ($d) {
            exit(json_encode(array('result'=>'1','info'=>'头像修改成功！')));
        }else{
            exit(json_encode(array('result'=>'-1','info'=>'头像修改失败！')));
        }
    }
    /**
     * [user_paw 密码修改]
     * @return [type] [description]
     */
    public function user_paw(){
        $user_id = $this->is_login(I('admin_id'));
        $data = I('');
        $password = encrypt($data['o_password']);
        $us = Db::name('red_user')->where(["admin_id" => $user_id,"password" => $password])->find();
        if (!$us) {
            exit(json_encode(array('result'=>'-1','info'=>'原密码输入错误')));
        }
        if(!preg_match('/^[0-9]+$/',$data['password'])){
            exit(json_encode(array('result'=>'-2','info'=>'密码应为纯数字')));
        }
        if ($data['password'] !== $data['password_s']) {
            exit(json_encode(array('result'=>'-3','info'=>'两次密码输入不一致')));
        }
        $paw = Db::name('red_user')->where('admin_id',$user_id)->update(['password'=>encrypt($data['password']),'save_time'=>time(),'updata_paw'=>1]);
        if ($paw) {
            exit(json_encode(array('result'=>'1','info'=>'密码修改成功')));
        }else{
            exit(json_encode(array('result'=>'-3','info'=>'密码修改失败')));
        }
    }



    /**
     * [user_address 编辑收货地址]
     * @return [type] [description]
     */
    public function user_address(){
        $user_id = $this->is_login(I('admin_id'));
        $user_address = Db::name('red_user')->where('admin_id',$user_id)->find();
        if (IS_POST) {
            $data = I('');
            $data['save_time'] = time();
            $save = Db::name('red_user')->where('admin_id',$user_id)->update($data);
            if ($save) {
                exit(json_encode(array('result'=>'1','info'=>'收货地址修改成功')));
            }else{
                exit(json_encode(array('result'=>'-3','info'=>'收货地址修改失败')));
            }
        }
        $rs=array('result'=>'1','info'=>'请求成功','user_address'=>$user_address);
        exit(json_encode($rs));
    }

    /**
     * [order_list 订单列表]
     * @return [type] [description]
     */
    public function order_list(){
        $user_id = $this->is_login(I('admin_id'));
        $where = ' user_id= '.$user_id.' and is_parent = 0 ';
        //状态搜索
        input('order_status') != '' ? $wheres['order_status'] = input('order_status') : false;
        
        $begin = strtotime(input('add_time_begin'));
        $end = strtotime(input('add_time_end')); 
        if($begin && $end){
            $wheres['add_time'] = array('between',"$begin,$end");
        }
        //分页
        $count = Db::name('red_order')->where($where)->where($wheres)->count();
        $page_html = input('page_html')?input('page_html'):1; //前端页码
        $count_html = 5;
        if ($page_html) {
            $firstRow = ($page_html-1) * $count_html;
        }
        $order_str = "order_id DESC";
        $field = "order_id,order_amount,total_amount,order_status,shipping_name,shipping_code,order_sn,pay_time";
        $order_list = Db::name('red_order')->order($order_str)->where($where)->where($wheres)->field($field)->limit($firstRow.','.$count_html)->select();
        //获取订单商品
        foreach($order_list as $k=>$v)
        {
            $data = $this->red_get_order_goods($v['order_id']);
            $order_list[$k]['goods_list'] = $data['result'];
        }
        $rs=array('result'=>'1','info'=>'请求成功','order_status'=>config('agen_order_status'),'lists'=>$order_list,'active'=>'order_list','active_status'=>I('get.type'),'count'=>$count);
        exit(json_encode($rs));
    }

    /**
     * [red_get_order_goods 获取订单商品]
     * @param  [type] $order_id [description]
     * @return [type]           [description]
     */
    public function red_get_order_goods($order_id){
        $this->is_login(I('admin_id'));
        $sql = "SELECT og.*,g.goods_thumb FROM __PREFIX__red_order_goods og LEFT JOIN __PREFIX__red_goods g ON g.goods_id = og.goods_id WHERE order_id = :order_id";
        $bind['order_id'] = $order_id;
        $goods_list = DB::query($sql,$bind);
        $return['status'] = 1;
        $return['msg'] = '';
        $return['result'] = $goods_list;
        return $return;
    }

    /**
     * [red_order_info 订单详情]
     * @return [type] [description]
     */
    public function red_order_info(){
        $user_id = $this->is_login(I('admin_id'));
        $order_id = I('order_id');
        $field = "order_id,order_amount,total_amount,order_status,shipping_name,shipping_code,order_sn,pay_time,add_time,consignee,province,city,district,address,mobile";
        $order_list = Db::name('red_order')->where('order_id',$order_id)->field($field)->find();
        //获取订单商品
        $data = $this->red_get_order_goods($order_list['order_id']);
        $order_list['goods_list'] = $data['result'];
        $rs=array('result'=>'1','info'=>'请求成功','order_status'=>config('agen_order_status'),'lists'=>$order_list,'active'=>'order_list','active_status'=>I('get.type'),'count'=>$count);
        exit(json_encode($rs));

    }
    /*
     * 取消订单
     */
    public function cancel_order(){
        $user_id = $this->is_login(I('admin_id'));
        $order_id = I('get.order_id/d');
        $order = Db::name('red_order')->where(array('order_id'=>$order_id,'user_id'=>$user_id))->find();
        //检查是否未支付订单 已支付联系客服处理退款
        if(empty($order)){
            exit(json_encode(array('status'=>-1,'msg'=>'订单不存在','result'=>'')));
        }
        //检查是否未支付的订单
        if($order['pay_status'] > 0 || $order['order_status'] > 0){
            exit(json_encode(array('status'=>-2,'msg'=>'支付状态或订单状态不允许','result'=>'')));
        }
        $row = Db::name('red_order')->where(array('order_id'=>$order_id,'user_id'=>$user_id))->update(array('order_status'=>4));
        $data['order_id'] = $order_id;
        $data['action_user'] = $user_id;
        $data['action_note'] = '您取消了订单';
        $data['order_status'] = 4;
        $data['pay_status'] = $order['pay_status'];
        $data['shipping_status'] = $order['shipping_status'];
        $data['log_time'] = time();
        $data['status_desc'] = '用户取消订单';        
        Db::name('red_order_action')->insert($data);//订单操作记录
        if(!$row){
            exit(json_encode(array('status'=>-1,'msg'=>'操作失败','result'=>'')));
        }
        exit(json_encode(array('status'=>1,'msg'=>'操作成功','result'=>'')));
    }

    /**
     * [delete_order 删除订单]
     * @param  [type] $order_id [description]
     * @return [type]           [description]
     */
    public function delete_order($order_id){
        $orderLogic = new OrderLogic();
        $del = $orderLogic->red_delOrder($order_id);
        if($del){
            $this->success('删除订单成功');
        }else{
            $this->error('订单删除失败');
        }
    }

    /**
     * [delete_order 确认订单]
     * @param  [type] $order_id [description]
     * @return [type]           [description]
     */
    public function confirm_order($order_id){
        $confirm=Db::name('red_order')->where('order_id',$order_id)->update(['order_status'=>2]);
        if($confirm){
            $this->success('订单确认成功');
        }else{
            $this->error('订单确认失败');
        }
    }

    /**
     * [delete_order 确认收货]
     * @param  [type] $order_id [description]
     * @return [type]           [description]
     */
    public function take_order($order_id){
        $take = Db::name('red_order')->where('order_id',$order_id)->update(['order_status'=>3,'confirm_time'=>time()]);
        if($take){
            $this->success('确认收货成功');
        }else{
            $this->error('确认收货失败');
        }
    }     

    /*个人中心相关结束*/
    /*========================================================================*/
    /*购物车及订单提交相关开始*/

    /**
     * ajax 将商品加入购物车
     */
    function ajaxAddCart()
    {
        $user_id = $this->is_login(I('admin_id'));
        $goods_id = input("goods_id"); // 商品id
        $goods_num = input("goods_num");// 商品数量
        $is_logo = input("is_logo","0");    // 是否定制logo
        $goods_spec = input("goods_spec/a",array()); // 商品规格
        if (empty($goods_spec) && !empty(I('goods_key'))) {
            $goods_key = I('goods_key'); // 商品规格
            $goods_val = I('goods_val'); // 商品规格
            $goods_spec=array($goods_key=>$goods_val);
        }
        $result = $this->red_addCart($goods_id, $goods_num, $goods_spec,$this->session_id,$user_id,$is_logo); // 将商品加入购物车
        exit(json_encode($result));
    }

    /*
     * ajax 请求获取购物车列表
     */
    public function ajaxCartList()
    {
        $user_id = $this->is_login(I('admin_id'));
        $post = input('');
        $post_goods_num       = json_decode($post["goods_num"],true);           // goods_num 购物车商品数量
        $post_cart_select     = json_decode($post["cart_select"],true);         // 购物车选中状态
        $where['session_id'] = $this->session_id;// 默认按照 session_id 查询
        // 如果这个用户已经等了则按照用户id查询
        if($user_id){
            unset($where);
            $where['user_id'] = $user_id;
        }
        $cartList = Db::name('RedCart')->where($where)->column("id,goods_num,selected,prom_type,prom_id");
        if($post_goods_num)
        {
            // 修改购物车数量 和勾选状态
            foreach($post_goods_num as $key => $val)
            {   
                $data['goods_num'] = $val < 1 ? 1 : $val;
                $data['selected'] = $post_cart_select[$key] ? 1 : 0 ;  
                if(($cartList[$key]['goods_num'] != $data['goods_num']) || ($cartList[$key]['selected'] != $data['selected'])) {                     
                    Db::name('RedCart')->where("id", $key)->update($data);
                }
            }
        }

        $result = $this->red_cartList($user_id, $this->session_id,1,1,0); // 选中的商品

        //为您推荐
        $field = "goods_id,cat_id,goods_name,goods_thumb,shop_price,market_price,goods_remark,comment_count,group_price,red_supplier_id,brand_id";

        $goods_quality_list = Db::name('red_goods')->field($field)->where('examine = 1 and is_recommend =1 and is_delete = 0 and cat_id = 1126')->order('goods_id desc')->limit('4')->select();
        foreach ($goods_quality_list as $key => $value) {
            if (!empty($value['red_supplier_id'])) {
                $value['is_quality'] = Db::name('redsupplier_user')->where("red_admin_id",$value['red_supplier_id'])->value('is_quality');
            }else{
                $value['is_quality'] = 0;
            }
            if (!empty($value['brand_id'])) {
                $value['brand_name'] = Db::name('brand')->where("id",$value['brand_id'])->value('name');
            }else{
                $value['brand_name'] = '';
            }
            $goods_quality_lists[]=$value;
        }


        if(empty($result['total_price'])){
            $result['total_price'] = Array( 'total_fee' =>0, 'cut_fee' =>0, 'num' => 0);
        }
        $rs=array('result'=>'1','info'=>'请求成功','cartList'=>$result['cartList'],'total_price'=>$result['total_price'],'goods_quality_list'=>$goods_quality_lists);
        exit(json_encode($rs));
    }
    /**
     * [update_member_goods_price 修改购物车单价]
     * @return [type] [description]
     */
    public function update_member_goods_price(){
        $user_id = $this->is_login(I('admin_id'));
        $post = I('member_goods_price/a');
        $member_goods_price = json_decode($post[0],true); // member_goods_price 修改会员购买价格
        if ($member_goods_price) {
            // 修改会员购买价格
            foreach($member_goods_price as $key => $val)
            {   
                $type = Db::name('RedCart')->where(["id"=>$key,'user_id'=>$user_id])->update(['member_goods_price'=>$val]);
                if ($type) {
                    exit(json_encode(array('result'=>'1','info'=>'单价修改成功')));
                }
            }
        }else{
            exit(json_encode(array('result'=>'-1','info'=>'缺少必要参数')));
        }
    }
    
    /**
     * ajax 删除购物车的商品
     */
    public function ajaxDelCart()
    {       
        $this->is_login(I('admin_id'));
        $ids = input("cart_id"); // 商品 ids
        $result = Db::name("RedCart")->where("id", "in", $ids)->delete(); // 删除用户数据
        $return_arr = array('status'=>1,'msg'=>'删除成功','result'=>''); // 返回结果状态       
        exit(json_encode($return_arr));
    }
     /**
     * 购物车第二步确定页面
     */
    public function orderconfirm()
    {   
        $user_id = $this->is_login(I('admin_id'));
        if($this->red_cart_count($user_id,1) == 0 ){
            exit(json_encode(array('result'=>'-11','info'=>'你的购物车没有选中商品','url'=>"home/RedGift/cart")));
        }
        
        $result = $this->red_cartList($user_id, $this->session_id,1,1,1); // 获取购物车商品
        $rs=array('result'=>'1','info'=>'请求成功','cartList'=>$result['cartList'],'total_price'=>$result['total_price']);
        exit(json_encode($rs));
    }

    /**
    * ajax 获取订单商品价格 或者提交 订单
    */
    public function cart3(){

        $user_id = $this->is_login(I('admin_id'));
        if($user_id == 0){
            exit(json_encode(array('status'=>-100,'msg'=>"登录超时请重新登录!",'result'=>null))); // 返回结果状态
        }
        if($this->red_cart_count($user_id,1) == 0 ) exit(json_encode(array('status'=>-2,'msg'=>'你的购物车没有选中商品','result'=>null))); // 返回结果状态
        
        $order_goods = Db::name('red_cart')->where(['user_id'=>$user_id,'selected'=>1])->select();
        //calculate_price()计算订单金额
        $result = $this->calculate_price($user_id,$order_goods);
        if($result['status'] < 0){
            exit(json_encode($result));   
        }
        
        $car_price = array(
            'total_amount' => $result['result']['total_amount'], // 订单总价
            'payables'     => $result['result']['order_amount'], // 应付金额
            'goodsFee'     => $result['result']['goods_price'],  // 商品价格            
            // 'goodsFee'     => $result['result']['member_goods_price'],  // 商品价格            
        );
        // 提交订单        
        if($_REQUEST['act'] == 'submit_order')
        {  
            $result = $this->addOrder($user_id,$car_price); // 添加订单
            exit(json_encode($result));            
        }

        $return_arr = array('status'=>1,'msg'=>'计算成功','result'=>$car_price); // 返回结果状态
        exit(json_encode($return_arr));        
    }
    
    /**
     * [calculate_price 计算订单金额]
     * @param  integer $user_id        [description]
     * @param  [type]  $order_goods    [description]
     */
    function calculate_price($user_id = 0, $order_goods)
    {
        $this->is_login(I('admin_id'));
        $user = Db::name('red_user')->where("admin_id", $user_id)->find();// 找出这个用户
        if (empty($order_goods)){
            return array('status' => -9, 'msg' => '商品列表不能为空', 'result' => '');
        }
        foreach ($order_goods as $key => $val) {
            $order_goods[$key]['goods_fee'] = $val['goods_num'] * $val['member_goods_price'];    // 小计
            $order_goods[$key]['store_count'] = red_getGoodNum($val['goods_id'], $val['spec_key']); // 最多可购买的库存数量
            if ($order_goods[$key]['store_count'] <= 0){
                return array('status' => -10, 'msg' => $order_goods[$key]['goods_name'] . "库存不足,请重新下单", 'result' => '');
            }
            $goods_price += $order_goods[$key]['goods_fee']; // 商品总价
            // $cut_fee += $val['goods_num'] * $val['market_price'] - $val['goods_num'] * $val['goods_price']; // 共节约
            $cut_fee += $val['goods_num'] * $val['market_price'] - $val['goods_num'] * $val['member_goods_price']; // 共节约
            $anum += $val['goods_num']; // 购买数量
        }
        $order_amount = $goods_price;
        $total_amount = $goods_price;
        //订单总价  应付金额  物流费  商品总价 节约金额 共多少件商品 积分  余额  优惠券
        $result = array(
            'total_amount'  => $total_amount,   // 订单总价
            'order_amount'  => $order_amount,   // 应付金额
            'goods_price'   => $goods_price,    // 商品总价
            'cut_fee'       => $cut_fee,        // 共节约多少钱
            'anum'          => $anum,           // 商品总共数量
            'order_goods'   => $order_goods,    // 商品列表 多加几个字段原样返回
        );
        return array('status' => 1, 'msg' => "计算价钱成功", 'result' => $result); // 返回结果状态
    }
    /**
     * 红礼 购物车列表
     * @param type $user 用户
     * @param type $session_id session_id
     * @param type $selected 是否被用户勾选中的 0 为全部 1为选中  一般没有查询不选中的商品情况
     * $mode 0  返回数组形式  1 直接返回result
     * $Choice  提交订单时将selected为1的条件加入查询
     */
    function red_cartList($user_id, $session_id = '', $selected = 0, $mode = 0, $Choice = 0,$goods_id='')
    {
        $this->is_login(I('admin_id'));
        $now_time = time();
        $where = " 1 = 1 ";
        if ($Choice == 1){
            $where .= " and selected = 1";
        }
        $bind = array();
        if ($user_id)// 如果用户已经登录则按照用户id查询
        {
            $where .= " and user_id = $user_id ";
        } else {
            $where .= " and session_id = :session_id";
            $bind['session_id'] = $session_id;
            $user_id = 0;
        }

        $cart = Db::name('RedCart')->where($where)->bind($bind)->order('add_time desc , supplier_id asc ')->limit($limit)->select();  // 获取购物车商品
        if ($cart) {
            // 选中商品所有商品
            $supplier_selected = Db::name('RedCart')->where($where)->bind($bind)->field('supplier_id')->limit($limit)->group('supplier_id')->select();
            foreach ($supplier_selected as $k => $val) {

                $cart_num = Db::name('RedCart')->where($where)->where('supplier_id', $val['supplier_id'])->bind($bind)->limit($limit)->count();
                $cart_selected = Db::name('RedCart')->where($where)->where('supplier_id', $val['supplier_id'])->where('selected', '1')->bind($bind)->limit($limit)->count();
                if ($cart_num == $cart_selected){
                    $su_selected[$val['supplier_id']] = 1;
                }else{
                    $su_selected[$val['supplier_id']] = 0;
                }
            }
            // 获取图片
            foreach ($cart as $k => $val) {
                $val['goods_thumb'] = Db::name('red_goods')->where('goods_id', $val['goods_id'])->Cache(YLT_CACHE_TIME)->value('goods_thumb');
                //判断是否为家居分类
                $goods_cat_id= Db::name('red_goods')->where('goods_id', $val['goods_id'])->value('cat_id');
                // $parent_path= Db::name('red_goods_category')->where('id', $goods_cat_id)->value('parent_id_path');
                $parent_path= Db::name('goods_category')->where('id', $goods_cat_id)->value('parent_id_path');
                // $val['parent_id_path'] = substr($parent_path,2,2);
                $val['parent_id_path']=substr($parent_path,4);
                $carts[] = $val;
            }
            $shipping_price = $anum = $total_price = $cut_fee = 0;
            $cartList = [];
            foreach ($carts as $k => $val) {
                $cartList[$val['supplier_id']]['supplier_name'] = $val['supplier_name'];
                $cartList[$val['supplier_id']]['supplier_id'] = $val['supplier_id'];
                $cartList[$val['supplier_id']]['is_designer'] = $val['is_designer'];
                $cartList[$val['supplier_id']]['selected'] = $su_selected[$val['supplier_id']];    //   选中状态
                $val['store_count'] = red_getGoodNum($val['goods_id'], $val['spec_key']);        // 最多可购买的库存数量
                $anum += $val['goods_num'];
                $cartList[$val['supplier_id']]['list'][] = $val;
                // 如果要求只计算购物车选中商品的价格 和数量  并且  当前商品没选择 则跳过
                if ($selected == 1 && $val['selected'] == 0){
                    continue;
                }

                // $cartList[$val['supplier_id']]['total_price'] += ($val['goods_num'] * $val['goods_price']); //商铺商品总价
                $cartList[$val['supplier_id']]['total_price'] += ($val['goods_num'] * $val['member_goods_price']); //商铺商品总价

                //市场价不为0时，计算出本店售价比市场价便宜了多少钱
                if ($val['market_price']!=0) {
                    // $cut_fee += $val['goods_num'] * $val['market_price'] - $val['goods_num'] * $val['goods_price']; 
                    $cut_fee += $val['goods_num'] * $val['market_price'] - $val['goods_num'] * $val['member_goods_price']; 
                }  
                // $total_price += $val['goods_num'] * $val['goods_price'];
                $total_price += $val['goods_num'] * $val['member_goods_price'];
            }
        }
        $total_price = array('total_fee' => $total_price, 'cut_fee' => $cut_fee, 'num' => $anum); // 总计
        setcookie('cn', $anum, null, '/');
                
        if ($mode == 1) {
            return array('cartList' => $cartList, 'total_price' => $total_price);
            // exit(json_encode(array('cartList' => $cartList, 'total_price' => $total_price))); 
        }
            return array('result' => array('cartList' => $cartList, 'total_price' => $total_price));
        // exit(json_encode(array('status' => 1, 'msg' => '', 'result' => array('cartList' => $cartList, 'total_price' => $total_price)))); 
    }


    /**
     * 红礼 查看购物车的商品数量
     * @param type $user_id
     * $mode 0  返回数组形式  1 直接返回result
     */
    public function red_cart_count($user_id, $mode = 0)
    {
        $this->is_login(I('admin_id'));
        $count = Db::name('RedCart')->where(['user_id' => $user_id, 'selected' => 1])->count();
        if ($mode == 1) return $count;

        return array('status' => 1, 'msg' => '', 'result' => $count);
    }

    /**
     * 红礼 加入购物车方法
     * @param type $goods_id 商品id
     * @param type $goods_num 商品数量
     * @param type $goods_spec 选择规格
     * @param type $user_id 用户id
     */
    function red_addCart($goods_id, $goods_num, $goods_spec, $session_id, $user_id = 0,$is_logo = 0, $cart_id = 0,$selected = 1)
    {   
        $this->is_login(I('admin_id'));
        $goods = Db::name('RedGoods')->where("goods_id", $goods_id)->find(); // 找出这个商品
        //获取一级分类，判断是否为家居分类
        $parent_path= Db::name('red_goods_category')->where('id', $goods['cat_id'])->value('parent_id_path');
        // $goods['parent_id_path'] = substr($parent_path,2,2);
        $goods['parent_id_path'] = substr($parent_path,4);

        $specGoodsPriceList = Db::name('red_goods_price')->where("goods_id", $goods_id)->column("key,key_name,price,store_count,sku,quantity"); // 获取商品对应的规格价钱 库存 条码
        $where = " session_id = :session_id ";
        $bind['session_id'] = $session_id;
        $now_time = time();
        $user_id = $user_id ? $user_id : 0;
        if ($user_id) {
            $where .= "  or user_id= :user_id ";
            $bind['user_id'] = $user_id;
        }
        $catr_count = Db::name('RedCart')->where($where)->bind($bind)->count(); // 查找购物车商品总数量
        if ($catr_count >= 18){
            exit(json_encode(array('status' => -9, 'msg' => '购物车最多只能放18种商品', 'result' => ''))); 
        }

        if ($goods_spec) {
            foreach ($goods_spec as $key => $val){ // 处理商品规格
                $spec_item[] = $val; // 所选择的规格项  
            }
        }
        $quantity = 1; //起售量
        if (!empty($spec_item)) {
            sort($spec_item);
            $spec_key = implode('_', $spec_item);
            if ($specGoodsPriceList) {
                if ($specGoodsPriceList[$spec_key]['store_count'] < $goods_num){
                    exit(json_encode(array('status' => -5, 'msg' => '该规格库存不足，剩余库存为'.$specGoodsPriceList[$spec_key]['store_count'], 'result' => ''))); 
                }
                $spec_price = $specGoodsPriceList[$spec_key]['price']; // 获取规格指定的价格
                $quantity = $specGoodsPriceList[$spec_key]['quantity']; // 获取规格指定的起售量
            }
        }

        if (!empty($specGoodsPriceList) && empty($goods_spec)){// 有商品规格 但是前台没有传递过来
            exit(json_encode(array('status' => -1, 'msg' => '必须传递商品规格', 'result' => ''))); 
        } 
        if ($goods_num < $quantity){
            exit(json_encode(array('status' => -2, 'msg' => '购买商品数量不能低于'.$quantity."件", 'result' => ''))); 
        }
        if (empty($goods) || $goods['examine'] == 0){
            exit(json_encode(array('status' => -3, 'msg' => '商品已下架', 'result' => ''))); 
        }
        if (($goods['store_count'] < $goods_num)){
            exit(json_encode(array('status' => -4, 'msg' => '商品库存不足，当前库存为'.$goods['store_count'], 'result' => ''))); 
        }
        if ($goods['prom_type'] > 0 && $user_id == 0){
            exit(json_encode(array('status' => -101, 'msg' => '购买活动商品必须先登录', 'result' => ''))); 
        }
        $where = " goods_id = :goods_id and spec_key = :spec_key"; // 查询购物车是否已经存在这商品
        if ($spec_key) {
            $cart_bind['spec_key'] = $spec_key;
        } else {
            $cart_bind['spec_key'] = '';
        }
        $cart_bind['goods_id'] = $goods_id;
        if ($user_id > 0) {
            $where .= " and (session_id = :session_id or user_id = :user_id) ";
            $cart_bind['session_id'] = $session_id;
            $cart_bind['user_id'] = $user_id;
        } else {
            $where .= " and  session_id = :session_id ";
            $cart_bind['session_id'] = $session_id;
        }
        $catr_goods = Db::name('RedCart')->where($where)->bind($cart_bind)->find(); // 查找购物车是否已经存在该商品
        $price = $spec_price ? $spec_price : $goods['group_price']; // 如果商品规格没有指定价格则用商品原始价格
        // if ($goods['parent_id_path']!=28) {     //礼至家居分类的商品默认为市场价
        if ($goods['parent_id_path']!=1089) {     //礼至家居分类的商品默认为市场价
            $member_goods_price = $spec_price ? $spec_price : $goods['group_price']; 
        }else{
            $member_goods_price = $spec_price ? $spec_price : $goods['market_price']; 
        }

        $data = array(
            'user_id' => $user_id,   // 用户id
            'session_id' => $session_id,   // sessionid
            'goods_id' => $goods_id,   // 商品id
            'goods_sn' => $goods['goods_sn'],   // 商品货号
            'goods_name' => $goods['goods_name'],   // 商品名称
            'market_price' => $goods['market_price'],   // 市场价
            'goods_price' => $price,  // 购买价/本店价
            'cost_price' => $goods['cost_price'],  // 成本价
            'member_goods_price' => $member_goods_price,  // 会员折扣价
            'goods_num' => $goods_num,   // 购买数量
            'spec_key' => "{$spec_key}", // 规格key
            'spec_key_name' => "{$specGoodsPriceList[$spec_key]['key_name']}", // 规格 key_name
            'sku' => "{$specGoodsPriceList[$spec_key]['sku']}", // 商品条形码
            'add_time' => time(), // 加入购物车时间
            'prom_type' => $goods['prom_type'],   // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
            'prom_id' => $goods['prom_id'],   // 活动id
            'supplier_id' => 686,   //红礼ID
            'supplier_name' => $goods['supplier_name'],
            'is_designer' => $goods['is_designer'],
            'quantity' => $quantity,         //商品起订量
            'commission_price' => $goods['commission_price'],    //佣金
            'goods_thumb' => $goods['goods_thumb'],   //商品缩略图
            'red_supplier_id' => $goods['red_supplier_id'],   //红礼商品ID
            'red_cost_price' => $goods['red_cost_price'],     //商家供货价/红礼采购单价
            'selected' => $selected,   //选择状态2是详情页立即购买 
            'is_logo' => $is_logo,      //是否定制logo
        );

        // 如果商品购物车已经存在
        if ($catr_goods and $data['selected']!=2) {
            // 如果购物车的已有数量加上 这次要购买的数量  大于  库存输  则不再增加数量
            if (($catr_goods['goods_num'] + $goods_num) > $goods['store_count']){
                $goods_num = 0;
            }
            $update = [
                'goods_num' => ['exp','goods_num+'."$goods_num".''],        // 数量相加
            ];
            $result = Db::name('RedCart')->where("id", $cart_id)->update($update); 
            $cart_count = red_cart_goods_num($user_id, $session_id);            // 查找购物车数量
            setcookie('cn', $cart_count, null, '/');
            exit(json_encode(array('status' => 1, 'msg' => '成功加入购物车', 'result' => $cart_count))); 
        } else {
            $insert_id = Db::name('RedCart')->insert($data);
            if (!empty($cart_id) && $insert_id) {
                Db::name('RedCart')->where('id', $cart_id)->delete();  //购物车直接修改规格
            }

            $cart_count = red_cart_goods_num($user_id, $session_id); // 查找购物车数量
            setcookie('cn', $cart_count, null, '/');
            exit(json_encode(array('status' => 1, 'msg' => '成功加入购物车', 'result' => $cart_count))); 
        }
        $cart_count = red_cart_goods_num($user_id, $session_id); // 查找购物车数量
        exit(json_encode(array('status' => -5, 'msg' => '加入购物车失败', 'result' => $cart_count))); 
    }

    /**
     * [addOrder 添加订单]
     * @param [type]  $user_id   [description]
     * @param [type]  $car_price [description]
     */
    public function addOrder($user_id,$car_price)
    {   
        $this->is_login(I('admin_id'));
        $new_order_id = 0;//父ID
        $order_count = Db::name('RedOrder')->where("user_id", $user_id)->where('order_sn', 'like', date('Ymd') . "%")->count(); // 查找购物车商品总数量
        if ($order_count >= 30){
            exit(json_encode(array('status' => -9, 'msg' => '为避免刷单，一天只能下30个订单', 'result' => ''))); 
        }
        $cart = Db::name('RedCart')->where(['user_id' => $user_id, 'selected' => 1])->order('supplier_id asc ')->select();
        $AgenUser = Db::name('RedUser')->where(['admin_id' => $user_id])->find();
        $cartList = [];

        // 分商铺订单
        foreach ($cart as $k => $val) {
            $val['store_count'] = red_getGoodNum($val['goods_id'], $val['spec_key']);        // 最多可购买的库存数量
            $cartList[$val['supplier_id']]['total_price'] += ($val['goods_num'] * $val['member_goods_price']);
            $cartList[$val['supplier_id']]['red_total_price'] += ($val['goods_num'] * $val['red_cost_price']);
            $cartList[$val['supplier_id']]['supplier_id'] = $val['supplier_id'];
            $cartList[$val['supplier_id']]['red_supplier_id'] = $val['red_supplier_id'];
            $cartList[$val['supplier_id']]['supplier_name'] = $val['supplier_name'];
            $cartList[$val['supplier_id']]['list'][] = $val;
            $order_prom_amount = 0;
        }
        if (count($cartList) > 1) {
            //生成父单,多个订单
            $order = array(
                'order_sn' => date('YmdHis') . rand(1000, 9999), // 订单编号
                'user_id' => $user_id,
                'order_status' => 0,
                'pay_status' => 0,
                'consignee' => $AgenUser['consignee'], // 收货人
                'province' => $AgenUser['province'],
                'city' => $AgenUser['city'],
                'district' => $AgenUser['district'],
                'address' => $AgenUser['address'],
                'mobile' => $AgenUser['mobile'],
                'goods_price' => $car_price['goodsFee'],
                'order_amount' => $car_price['payables'],
                'total_amount' => $car_price['total_amount'],
                'add_time' => time(),
                'is_parent' => '1',
                'source' => '红礼商城',
            );
            $new_order_id = Db::name("RedOrder")->insertGetId($order);
            if (!$new_order_id){
                exit(json_encode(array('status' => -8, 'msg' => '添加订单失败', 'result' => NULL))); 
            }
            //新增一礼通订单，然后删除，同步两个表的自增ID数 
            Db::name("Order")->insertGetId($order);
            Db::name("Order")->where('order_id',$new_order_id)->delete();
            //新增一礼通订单，然后删除，同步两个表的自增ID数
        }

        // 分单生成订单
        foreach ($cartList as $k => $val) {
            $data = array(
                'order_sn' => date('YmdHis') . rand(1000, 9999), // 订单编号
                'user_id' => $user_id, // 用户id
                'consignee' => $AgenUser['consignee'], // 收货人
                'province' => $AgenUser['province'],//'省份id',
                'city' => $AgenUser['city'],//'城市id',
                'district' => $AgenUser['district'],//'县',
                'address' => $AgenUser['address'],//'详细地址',
                'mobile' => $AgenUser['mobile'],//'手机',
                'goods_price' => $val['total_price'],//'商品总价格',
                'total_amount' => $val['total_price'],// 订单总额
                'order_amount' => $val['total_price'],// 应付总额
                'add_time' => time(), // order_amount下单时间
                'is_parent' => '0',
                'parent_id' => $new_order_id,
                'source' => '红礼商城',
                'supplier_id' => $val['supplier_id'],               
                'red_supplier_id' => $val['red_supplier_id'],          //红礼商家ID
                'supplier_name' => $val['supplier_name'],
                'red_total_price' => $val['red_total_price'],         //红礼采购总价
                'red_order_amount' => $val['red_total_price'],        //红礼用户应付总额
                'is_designer' => 0,
            );
            $data['order_id'] = $order_id = Db::name("RedOrder")->insertGetId($data);
            if (!$order_id){
                exit(json_encode(array('status' => -8, 'msg' => '添加订单失败', 'result' => NULL))); 
            }
            //新增一礼通订单，然后删除，同步两个表的自增ID数 
            Db::name("Order")->insertGetId($data);
            Db::name("Order")->where('order_id',$order_id)->delete();
            //新增一礼通订单，然后删除，同步两个表的自增ID数 

            // 1插入order_goods 表
            foreach ($val['list'] as $key => $va) {
                $goods = Db::name('RedGoods')->where(array('goods_id' => $va['goods_id'], 'is_delete' => '0', 'examine' => '1'))->find();
                if ($goods) {
                    $data2['order_id'] = $order_id; // 订单id
                    $data2['goods_id'] = $va['goods_id']; // 商品id
                    $data2['goods_name'] = $va['goods_name']; // 商品名称
                    $data2['goods_sn'] = $va['goods_sn']; // 商品货号
                    $data2['goods_num'] = $va['goods_num']; // 购买数量
                    $data2['market_price'] = $va['market_price']; // 市场价
                    // $data2['goods_amount'] = $va['goods_num'] * $va['goods_price'];
                    $data2['goods_amount'] = $va['goods_num'] * $va['member_goods_price'];
                    // $data2['goods_price'] = $va['goods_price']; // 商品价
                    $data2['goods_price'] = $va['member_goods_price']; // 商品价
                    $data2['spec_key'] = $va['spec_key']; // 商品规格
                    $data2['spec_key_name'] = $va['spec_key_name']; // 商品规格名称
                    $data2['member_goods_price'] = $va['member_goods_price']; // 会员折扣价
                    $data2['cost_price'] = $goods['cost_price']; // 成本价
                    $data2['give_integral'] = $goods['give_integral']; // 购买商品赠送积分
                    $data2['prom_type'] = $va['prom_type']; // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
                    $data2['prom_id'] = $va['prom_id'];  // 活动id
                    $data2['is_logos'] = $va['is_logo']; // 是否定制LOGO
                    $data2['goods_thumb'] = $va['goods_thumb']; //缩略图
                    Db::name("RedOrderGoods")->insertGetId($data2);
                    //提交订单后删除购物车
                    Db::name('RedCart')->where(['user_id' => $user_id,'id' => $va['id']])->delete();
 
                }else{
                    Db::name('RedCart')->where(['user_id' => $user_id, 'id' => $va['id']])->delete();
                    exit(json_encode(array('status' => 8, 'msg' => '提交订单失败，部分商品已下架', 'result' => $order_id)));  // 返回新增的订单id
                }
            }
            // 记录订单操作日志
            $action_info = array(
                'order_id' => $order_id,
                'action_user' => $user_id,
                'action_note' => '您提交了订单，请等待系统确认',
                'status_desc' => '提交订单', //''
                'log_time' => time(),
                'supplier_id' => $val['supplier_id']
            );
            Db::name('red_order_action')->insertGetId($action_info);

        }
        if ($new_order_id == 0) {
            $new_order_id = $order_id;
        }

        //发送短信
        $config = tpCache('sms'); // 获取缓存中的短信信息
        $tel=$AgenUser['mobile'];       //收货电话
        $name=$AgenUser['consignee'];   //收货人
        $ordername="一个";              //模板订单信息
        //红礼短信提醒模板
        $data_phone = [
            'apikey' => $config['apikey'],
            'mobile' => "18033077619",//发送的手机号  刘星
            // 'tpl_id' => "3245600",
            'tpl_id' => "3254446",
            'tpl_value' =>""
        ];
        $this->sendCodes($data_phone);

        exit(json_encode(array('status' => 1, 'msg' => '提交订单成功', 'order_id' => $new_order_id)));  // 返回新增的订单id
    }


    /*购物车及订单提交相关结束*/
    /*========================================================================*/
    /*开始*/
    
    public function file(){
        $file = $_FILES['file'];//得到传输的数据
        //得到文件名称
        $name = $file['name'];
        $type = strtolower(substr($name,strrpos($name,'.')+1)); //得到文件类型，并且都转化成小写
        $allow_type = array('jpg','jpeg','gif','png'); //定义允许上传的类型
        //判断文件类型是否被允许上传
        if(!in_array($type, $allow_type)){
            //如果不被允许，则直接停止程序运行
            return ;
        }
        //判断是否是通过HTTP POST上传的
        if(!is_uploaded_file($file['tmp_name'])){
            //如果不是通过HTTP POST上传的
            return ;
        }
        $upload_path = ".public/upload/hongli"; //上传文件的存放路径
        //开始移动文件到相应的文件夹
        if(move_uploaded_file($file['tmp_name'],$upload_path.$file['name'])){
            echo "Successfully!";
        }else{
            echo "Failed!";
        }
    }
    public function goodSavetest(){
        $base64 = $_POST['formFile'];
        $IMG = base64_decode($base64);   //base64位转码，还原图片
        $path ='Uploads/Admin/Activedoc/';
        if (!file_exists($path)){
            mkdir($path,0777,true);
        }//如果地址不存在，创建地址
        $u=uniqid().date("ymdHis",time());
        $picname=$path.$u.'.jpg';
        file_put_contents($picname,$IMG);
        $picname2=$u.'.jpg';
        $this->ajaxReturn(array('status' => 1,'pic_id' =>$picname2,'pic_path'=>$u.'.jpg'));
    }

    function sendCodes($data)
    {
        $http = "https://sms.yunpian.com/v2/sms/tpl_single_send.json";
        // 发送短信 v2 版本写法
        //$data=array('text'=>$content,'apikey'=>$apikey,'mobile'=>$mobile);

        $ch = curl_init();

        // 设置验证方式

        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept:text/plain;charset=utf-8', 'Content-Type:application/x-www-form-urlencoded','charset=utf-8'));

        // 设置返回结果为流
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // 设置超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        // 设置通信方式
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $json_data = send($ch,$data,$http);
        $array = json_decode($json_data,true);
        return $array;
    }

    function send($ch,$data,$http){
        curl_setopt ($ch, CURLOPT_URL, $http);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        return curl_exec($ch);
    }
}