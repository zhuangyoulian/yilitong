<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/25
 * Time: 11:34
 */
namespace ylt\program\controller;
use ylt\home\logic\GoodsLogic;
use ylt\home\logic\CartLogic;
use think\AjaxPage;
use think\Controller;
use think\Url;
use think\Config;
use think\Page;
use think\Db;
use think\Request;
use think\Cache;
use org\JSSDK;

class Goods extends ProgramBase {
    public $user_id = 0;
    public $user = array();
    /**
     * 析构流函数
     */
    public function  __construct() {
        parent::__construct();
        $this->cartLogic = new \ylt\home\logic\CartLogic();
        if (I('user_id')) {
            $user = session('user');
            $user = Db::name('users')->where("user_id",I('user_id'))->find();
            session('user', $user);  //覆盖session 中的 user
            $this->user = $user;
            $this->user_id = $user['user_id'];
        }
    }

	/**
     * 商品详情页
     */
    public function goodsInfo(){
        $filter_spec = $spec_goods_price = $filter_spec_img = array();
        //http://www.yilitong.com//program/Goods/goodsInfo?token=f8b874f09905a13a6d3ff19c75a50e9a
        header("Content-Type:text/html;charset=UTF-8");
        if (!isset($_GET['token'])||empty($_GET['token'])) {
            exit("秘钥不能为空");
        }
        $token=md5("goodsInfo");
        if ($token==$_GET['token']) {
            $goods_id = I("get.goods_id/d");
            $user = session('user');
            $goodsLogic = new \ylt\home\logic\GoodsLogic();
    		
            $goods = Db::name('Goods')->where("goods_id",$goods_id)->cache(true,YLT_CACHE_TIME)->find();

            $goods['discount'] = $goods->discount;
            if(empty($goods) || $goods['is_on_sale'] == 0){
                $rs=array('result'=>'-1','info'=>'此商品不存在或者已下架');
                exit(json_encode($rs));
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

            $goods_images_list = Db::name('GoodsImages')->where("goods_id", $goods_id)->order('img_id desc')->select(); // 商品 图册
            
            //查询商品是否已收藏
            $collect = $goodsLogic->collect_goods_cha($user['user_id'],$goods_id);
            $filter_spec = $goodsLogic->get_spec($goods_id); //规格和规格图片
            $spec_goods_price  = Db::name('goods_price')->where("goods_id", $goods_id)->column("key,price,store_count,quantity");            // 规格对应的价格和库存表
            if ($filter_spec) {     //规格图片
                foreach ($filter_spec as $key => $value) {
                    foreach ($value as $keys => $val) {
                        $filter_spec_img[$val['item_id']] = array(
                            'item_id'   => $val['item_id'],
                            'item'      => $val['item'],
                            'src'       => $val['src'],
                        );
                    }
                }
            }
            
            $freight_free  = Db::name('supplier_config')->where(["supplier_id" => $goods['supplier_id'],"name"=>"is_free_shipping"])->value('value');       // 商店设置是否包邮
            
            $rs=array('result'=>'1','info'=>'请求成功','goods_images_list'=>$goods_images_list,'goods'=>$goods,'collect'=>$collect,'spec_goods_price'=>$spec_goods_price,'filter_spec'=>$filter_spec,'filter_spec_img'=>$filter_spec_img,'goods_make'=>$prom_count['goods_make'],'make_time'=>$prom_count['make_time'],'set_time'=>$prom_count['set_time'],'prom_count'=>$prom_count,'freight_free'=>$freight_free);
            exit(json_encode($rs));
        }
    }

    /**
     * [goodsGroup 拼单页面]
     * @return [type] [description]
     */
    public function goodsGroup(){
        $id             = $_GET['group_id'];        //拼单ID
        $goods_id       = $_GET['goods_id'];        //商品ID
        $discount_id    = $_GET['prom_id'];         //活动ID
        $time           = time();
        //拼单活动信息
        $prom_count = Db::name('share_the_bill')->alias('s')->join('users u','s.u_id = u.user_id')->join('discount_goods g','s.goods_id = g.goods_id')->join('goods o','g.goods_id = o.goods_id')->where(['s.goods_id'=>$goods_id,'s.is_initiate'=>1,'s.id'=>$id,'g.discount_id'=>$discount_id])->field('s.*,u.nickname,u.head_pic,g.goods_name,g.goods_thumb,g.activity_price,g.market_price,o.goods_remark')->find();
        //商品的活动状态
        if(empty($prom_count)){
            exit(json_encode(array('result'=>'-1','info'=>'拼单活动不存在')));
        }
        $prom_count['flash_sale'] = get_goods_promotion($goods_id);
        if($prom_count['flash_sale'] == 1){
            exit(json_encode(array('result'=>'-1','info'=>'拼单活动已结束')));
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
        if (Db::name('share_the_bill')->where(['goods_id'=>$goods_id,'p_id'=>$id,'u_id'=>$this->user_id])->find()) {
            $prom_count['is_participation'] = 1;
        }else{
            $prom_count['is_participation'] = 0;
        }
        //已参加的用户列表
        $prom_count['select'] = Db::name('share_the_bill')->alias('s')->join('users u','s.u_id = u.user_id')->where(['goods_id'=>$goods_id,'p_id'=>$id])->field('s.*,u.nickname,u.head_pic')->order('id asc')->select();  

        //拼单页面推荐商品
        $prom_goods = Db::name('discount_buy')->alias('d')->join('discount_goods g','d.id = g.discount_id')->where(['d.buy_type'=>7,'d.is_start'=>1])->where("d.end_time > $time")->where("g.goods_id != $goods_id")->limit(10)->field('g.goods_id,g.goods_name,g.goods_thumb,g.activity_price')->select();

        $rs=array('result'=>'1','info'=>'请求成功','prom_goods'=>$prom_goods,'prom_count'=>$prom_count);
        exit(json_encode($rs));
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
            $data['make_type']  = I('make_type');
            $data['add_time']   = time();
            $data['user_id']    = $this->user_id;
            $data['consult_type']    = 5;
            if (!Db::name('goods_share_list')->where(['user_id'=>$data['user_id'],'goods_id'=>$data['goods_id'],'type'=>$data['make_type']])->find()) {
                exit(json_encode(array('status'=>322,'msg'=>'距本次预约还差一次朋友圈的分享~')));
            }
            if (empty($data['user_id'])) {
                exit(json_encode(array('status'=>-3,'msg'=>'网络拥堵，请刷新一下~')));
            }
            if (empty($data['make_type'])) {
                exit(json_encode(array('status'=>-3,'msg'=>'状态为空~')));
            }
            if (empty($data['goods_id'])) {
                exit(json_encode(array('status'=>-3,'msg'=>'商品ID为空~')));
            }
            $activity_count = Db::name('discount_goods')->where(['goods_id'=>$data['goods_id'],'discount_id'=>$data['make_type']])->value('activity_count');  //活动库存、限制的活动总量
            $goods_count = Db::name('goods_consult')->where(['goods_id'=>$data['goods_id'],'make_type'=>$data['make_type']])->count(); //预约的数量
            $count = $activity_count - $goods_count;  //活动剩余数量
            if ($count <= 0) {
                exit(json_encode(array('status'=>3,'msg'=>'预约人数已满，请等待下轮预约！')));
            }
            if (Db::name('goods_consult')->where(['goods_id'=>$data['goods_id'],'user_id'=>$data['user_id'],'make_type'=>$data['make_type']])->find()) {
                exit(json_encode(array('status'=>2,'msg'=>'请勿重复预约')));
            }
            $arr = Db::name('goods_consult')->insert($data);
            if ($arr) {
                exit(json_encode(array('status'=>1,'msg'=>'恭喜您预约成功！')));
            }
        }
    }


    /**
     * [goodsCustomization 方案定制需求填写页面]
     * @return [type] [description]
     */
    public function goodsCustomization(){
        $data = I('');
        $id = I('get.customs_id'); //定制ID
        $uid = session('user');
        $users_id=$uid['user_id'];
        $data['session_id'] = $this->session_id;
        $users_id = $users_id ? $users_id : 0;
        if($users_id){
            $data['users_id'] = $users_id;
        }
        if ($id and IS_POST) {
            $data['type']="2019中秋方案";
            if(preg_match("/0?(13|14|15|18)[0-9]{9}/",$data['phone'])){
                $data['save_time']=time();
                Db::name("custom")->where(array('id'=>$id,'type'=>"2019中秋方案"))->update($data);
                exit(json_encode(array('status'=>1,'msg'=>'修改成功','customs_id'=>$id)));
            }else{
                exit(json_encode(array('status'=>-1,'msg'=>'手机格式不正确','result'=>'')));
            }
        }elseif(empty($id) and IS_POST){
        // }elseif(empty($id)){
            $data['type']="2019中秋方案";
            if(preg_match("/0?(13|14|15|18)[0-9]{9}/",$data['phone'])){
                    $data['add_time']=time();
                    $data['save_time']=time();
                    $data['customs_num'] = Db::name('custom')->where('users_id',$users_id)->count()+1;
                    $id=Db::name("custom")->insertGetId($data);
                exit(json_encode(array('status'=>1,'msg'=>'添加成功','customs_id'=>$id)));
            }else{
                exit(json_encode(array('status'=>-1,'msg'=>'手机格式不正确','result'=>'')));
            }
        }
        // return $this->fetch();
    }

    /**
     * 方案定制选择列表页
     */
    public function goodsList(){
        // $customs_id=$_SESSION['customs_id'];     // 方案定制ID
        $filter_param = array();                    // 筛选数组
        $customs_id=I('post.customs_id/d');         // 方案定制ID
        $id = I('post.gift_id/d',1207);             // 当前分类id
        $price = I('post.price','');                // 价钱
        if ($customs_id) {   //获取定制需求信息
            $field = "company,province,city,district,site,budget,linkman,phone,company_num";
            $customs = Db::name("custom")->where(array('id'=>$customs_id,'type'=>"2019中秋方案"))->field($field)->find();
        }

        $filter_param['gift_id'] = $id; //加入筛选条件中
        $price  && ($filter_param['price'] = $price); //加入筛选条件中

        $goodsLogic = new \ylt\home\logic\GoodsLogic(); // 前台商品操作逻辑类
        // 分类菜单显示
        $scenarioCate = Db::name('ScenarioCategory')->where("id", $id)->find(); // 当前分类
        $cateArr = $goodsLogic->get_scenario_cate($scenarioCate);               //查询下级分类

        // 筛选 品牌 规格 属性 价格
        $cat_id_arr = getScenarioCatGrandson ($id);
        $filter_goods_id = Db::name('goods')->where(['is_on_sale'=>1,'examine'=>1,'is_designer'=>0,'extend_cat_id'=>['in',implode(',', $cat_id_arr)]])->cache(true)->column("goods_id");

        // 过滤筛选的结果集里面找商品
        if($price)// 品牌或者价格
        {
            $goods_id_1 = $goodsLogic->getGoodsIdByBrandPrice(0,$price); // 根据 品牌 或者 价格范围 查找所有商品id
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_1); // 获取多个筛选条件的结果 的交集
        }

        $filter_price = $goodsLogic->get_filter_price($filter_goods_id,$filter_param,'goodsList'); // 筛选的价格期间

        $count = count($filter_goods_id);
        $page = new Page($count,config('PAGESIZE'));

        if($count > 0)
        {
            $field = "goods_id,cat_id,goods_name,goods_thumb,shop_price,market_price";
            $scenario_list = Db::name('goods')->where("goods_id","in", implode(',', $filter_goods_id))->field($field)->select();
        }

        for ($i=0; $i <count($scenario_list) ; $i++) {    //处理规格
            foreach ($filter_goods_id as $key => $value) {
                if ($scenario_list[$i]['goods_id'] == $value) {
                    $scenario_list[$i]['filter_spec'] = $goodsLogic->get_spec($value);
                }
            }
        }
        $rs=array('status'=>1,'msg'=>'列表请求成功','customs'=>$customs,'goods_list'=>$scenario_list,'filter_price'=>$filter_price,'cateArr'=>$cateArr,'gift_id'=>$id,'page'=>$page);
        exit(json_encode($rs));
    }

    /**
     * [planSubmit 方案提交]
     * @return [type] [description]
     */
    public function planSubmit(){
        $data['goods'] = json_decode(I('goods'),true);
        $data['customs_id'] = json_decode(I('post.customs_id'),true);
        // dump($data);die;
        
        $uid = session('user');
        $users_id=$uid['user_id'];
        $r = Db::name('custom')->where('id',$data['customs_id'])->find();
        if ($r) {
            if ($data['goods']) {
                foreach ($data['goods'] as $ke => $valu) {
                    $price+=$valu['goods_num']*$valu['goods_price'];
                }
                if ($price > $r['budget']) {
                    exit(json_encode(array('status'=>-4,'msg'=>'商品合计大于单份预算')));
                }
                Db::name('custom_goods')->where('customs_id',$data['customs_id'])->delete();
                foreach ($data['goods'] as $key => $value) {
                    $specGoodsPriceList = Db::name('goods_price')->where("goods_id",$value['goods_id'])->column("key,key_name,price,store_count,sku"); // 获取商品对应的规格价钱 库存 条码
                    $custom['goods_id']    = $value['goods_id'];         //商品id    
                    $custom['num']         = $value['goods_num'];        //数量
                    $custom['spec_id']     = $value['goods_spec'];       //商品规格id
                    $custom['spec']        = $specGoodsPriceList[$value['goods_spec']]["key_name"]; //商品规格名称
                    $custom['u_id']        = $users_id;                  //用户id
                    $custom['customs_id']  = $data['customs_id'];        //方案id
                    $custom['goods_thumb'] = Db::name('goods')->where('goods_id',$value['goods_id'])->field('goods_thumb')->value('goods_thumb');             //商品图
                    $custom['goods_price'] = $value['goods_price'];      //商品价格
                    $custom['goods_name']  = $value['goods_name'];       //商品名称
                    $a = Db::name('custom_goods')->insertGetId($custom);
                }
                if ($a) {
                    // session('customs_id',$id);
                    exit(json_encode(array('status'=>1,'msg'=>'提交成功','customs_id'=>$data['customs_id'],'customs_num'=>$r['customs_num'])));
                }else{
                    exit(json_encode(array('status'=>-1,'msg'=>'提交失败','customs_id'=>$data['customs_id'])));
                }
            }else{
                exit(json_encode(array('status'=>-3,'msg'=>'提交失败，没有选中的商品')));
            }
        }else{
            exit(json_encode(array('status'=>-2,'msg'=>'提交失败，方案不存在')));
        }
    }
	

    /**
     * [buy 立即购买]
     * @return [type] [description]
     */
    public function buy(){
        $customs_id = I('post.customs_id');
        $uid = session('user');
        $users_id=$uid['user_id'];
        $r = Db::name('custom_goods')->where('customs_id',$customs_id)->select();
        for ($i=0; $i < count($r); $i++) { 
            $goods_id[] = $r[$i]['goods_id'];
            $goods_num[] = $r[$i]['num'];
            $goods_spec[] = $r[$i]['spec_id'];
        }
        $cartLogic = new \ylt\home\logic\CartLogic();
        for ($e=0; $e <count($goods_id) ; $e++) { 
            $result = $cartLogic->addCartFU($goods_id[$e], $goods_num[$e], $goods_spec[$e],$this->session_id,$this->user_id); // 将商品加入购物车
        }
        exit(json_encode($result));
    }


    /**
     * 用户收藏商品
     * @param type $goods_id
     */
    public function collect_goods()
    {
        $goods_id = I('goods_id/d');
        $goodsLogic = new \ylt\home\logic\GoodsLogic();     
        $result = $goodsLogic->collect_goods($this->user_id,$goods_id);
        exit(json_encode($result));
    }
    /**
     * [cancel_collect_s 取消收藏]
     * @return [type] [description]
     */
    public function cancel_collect_s(){
        $goods_id = I('goods_id/d');
        $goodsLogic = new \ylt\home\logic\GoodsLogic();     
        $result = $goodsLogic->cancel_collect_s($this->user_id,$goods_id);
        exit(json_encode($result));
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
     * [cleanCache 清除系统缓存]
     * @return [type] [description]
     */
    public function cleanCache(){  
        if (I('is_end_text')==1) {
            exit(json_encode(array('status'=>2)));
        }           
        delFile(RUNTIME_PATH .'/cache');
        delFile(RUNTIME_PATH .'/html');
        delFile(RUNTIME_PATH .'/temp');
        exit(json_encode(array('status'=>1)));
    }

        /**
     * [share_record 微信分享记录]
     * @return [type] [description]
     */
    public function share_record(){
        if (IS_POST) {
            $data['user_id']  = $_POST['user_id'];
            $data['goods_id'] = $_POST['goods_id'];
            $data['type'] = $_POST['type'];
            $data['add_time'] = time();
            $data['ip'] = getIP();
            if (empty($data['user_id'])) {
                exit(json_encode(array('status'=>-3,'msg'=>"用户ID为空")));
            }
            if (empty($data['type'])) {
                exit(json_encode(array('status'=>-3,'msg'=>"状态为空")));
            }
            if (empty($data['goods_id'])) {
                exit(json_encode(array('status'=>-3,'msg'=>"商品ID为空")));
            }
            $arr = Db::name('goods_share_list')->insert($data);
            //分享规定商品可预约商品的活动
            if ($arr) {
                exit(json_encode(array('status'=>1,'msg'=>"恭喜您分享成功！")));
            }else{
                exit(json_encode(array('status'=>2,'msg'=>"分享失败")));
            }
        }
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

        $rs=array('status'=>1,'msg'=>'列表请求成功','goods_a'=>$goods_a,'goods_b'=>$goods_b,'goods_c'=>$goods_c,'goods_d'=>$goods_d,'goods_e'=>$goods_e);
        exit(json_encode($rs));
    }
    
    /**
     * [goodsDedicated 专区通用页面]
     * @return [type] [description]
     */
    public function goodsDedicated(){
        $id = I('id/d');
        if (!$id){
            exit(json_encode(array('status'=>-1,'msg'=>"活动ID不可为空！")));
        }
        $dedicated=Db::name('dedicated')->where('id',$id)->find();
        $goods = Db::name('goods')->where('goods_id','in',$dedicated['goods_id'])->order('sort desc,goods_id desc')->field('goods_id,goods_name,goods_thumb,shop_price,market_price,original_img')->select();

        $rs=array('status'=>1,'msg'=>'列表请求成功','goods'=>$goods,'dedicated'=>$dedicated);
        exit(json_encode($rs));
        return $this->fetch();
    }
}




