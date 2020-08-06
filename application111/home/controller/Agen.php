<?php
/**
 * Created by PhpStorm.
 * User: lijiayi
 * Date: 2019/9/6
 * Time: 14:32
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

class Agen extends Base {

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
            exit(json_encode(array('result'=>'-11','info'=>'请先登陆','url'=>'/FirstCapital/login')));
        }
        return $user_id;
        // exit(json_encode($user_id));
    }
    /**
    *   登录处理
    */
    public function agen_login(){
        $user_name = trim(I('post.user_name'));
        $password = trim(I('post.password'));
        $res = $this->user_login($user_name,$password);
        if($res['status'] == 1){
            session('yichuang',$res['result']);
            setcookie('yichuang_id',$res['result']['admin_id'],null,'/');
            $user_name = empty($res['result']['user_name']) ? $user_name : $res['result']['user_name'];
            setcookie('user_name',urlencode($user_name),null,'/');
            setcookie('cn',0,time()-3600,'/');
        }
        exit(json_encode($res));
    }
    public function user_login($username,$password){
        $result = array();
        if(!$username || !$password)
           $result= array('status'=>0,'msg'=>'请填写账号或密码');
        $user = Db::name('agen_user')->where("user_name",$username)->find();
        if(!$user){
           $result = array('status'=>-1,'msg'=>'账号不存在!');
        }elseif(encrypt($password) != $user['password']){
           $result = array('status'=>-2,'msg'=>'密码错误!');
        }else{
            // 更新用户的登记记录
            Db::name('agen_user')->where("admin_id", $user['admin_id'])->update(array('last_login'=>time(),'last_ip'=>getIP()));
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
        setcookie('yichuang_id','',time()-3600,'/');
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
        $this->is_login(I('admin_id'));
        //Brand
        $brand_list = Db::name('ad')->where('pid = 50 and enabled=1')->field('ad_code,ad_link')->select();
        //当季热推 按排序查询4条数据
        $is_hot_o = Db::name('agen_goods')->field("goods_id,goods_name,goods_thumb,shop_price")->where('examine = 1 and is_recommend =1 and is_on_sale = 1 and is_designer = 0 and sort = 1 ')->find();
        $is_hot_t = Db::name('agen_goods')->field("goods_id,goods_name,goods_thumb,shop_price")->where('examine = 1 and is_recommend =1 and is_on_sale = 1 and is_designer = 0 and sort = 2 ')->find();
        $is_hot_s = Db::name('agen_goods')->field("goods_id,goods_name,goods_thumb,shop_price")->where('examine = 1 and is_recommend =1 and is_on_sale = 1 and is_designer = 0 and sort = 3 ')->find();
        $is_hot_f = Db::name('agen_goods')->field("goods_id,goods_name,goods_thumb,shop_price")->where('examine = 1 and is_recommend =1 and is_on_sale = 1 and is_designer = 0 and sort = 4 ')->find();

        $rs=array('result'=>'1','info'=>'请求成功','is_hot_o'=>$is_hot_o,'is_hot_t'=>$is_hot_t,'is_hot_s'=>$is_hot_s,'is_hot_f'=>$is_hot_f,'brand_list'=>$brand_list);
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
                $path ='public/upload/yichuang/';
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
            $path ='public/upload/yichuang/';
            if (!file_exists($path)){
                mkdir($path,0777,true);
            }//如果地址不存在，创建地址
            $u=uniqid().date("ymdHis",time()).rand(111,999);
            $picname=$path.$u.$type;
            file_put_contents($picname,$IMG);
            $suggest['txt']=$u.$type;
        }
        $suggest['user_id'] = $data['admin_id'];
        $suggest['username'] = Db::name('agen_user')->where('admin_id',$data['admin_id'])->value('user_name');
        $suggest['content'] = $data['content'];
        $suggest['ip_address'] = $_SERVER['REMOTE_ADDR'];
        $suggest['add_time'] = time();
        $d = Db::name('agen_comment')->insert($suggest);
        if ($d) {
            exit(json_encode(array('result'=>'1','info'=>'提交成功，感谢您的反馈！')));
        }else{
            exit(json_encode(array('result'=>'-1','info'=>'提交失败！')));
        }
    }
    
    /**
     * [Price_search 固定的区间价格搜索]
     */
    public function Price_search(){
        $this->is_login(I('admin_id'));
        $price = I('price');
        if ($price)// 价格查询
        {
            $price = explode('-', $price);
            $where = "shop_price >= :shop_price1 and  shop_price <= :shop_price2 and examine =1 and is_on_sale = 1";
            $bind['shop_price1'] = $price[0];
            $bind['shop_price2'] = $price[1];
            // echo Db::name('agen_goods')->fetchsql()->where($where)->bind($bind)->field("goods_id,goods_name,goods_thumb,shop_price,is_on_sale")->select();
            $goods = Db::name('agen_goods')->where($where)->bind($bind)->field("goods_id,goods_name,goods_thumb,shop_price")->select();
        }
        $rs=array('result'=>'1','info'=>'请求成功','goods'=>$goods);
        exit(json_encode($rs));
    }
    
    /**
     * [search 搜索框关键词搜索]
     * @return [type] [description]
     */
    public function search(){
        $this->is_login(I('admin_id'));
        $keywords = I('keywords');
        if ($keywords) {
            $where = "`goods_name` LIKE '%".$keywords."%' OR `keywords` LIKE '%".$keywords."%' and examine =1 and is_on_sale = 1";
            $goods = Db::name('agen_goods')->where($where)->field("goods_id,goods_name,goods_thumb,shop_price")->select();
        }
        $rs=array('result'=>'1','info'=>'请求成功','goods'=>$goods);
        exit(json_encode($rs));
    }

    /**
     * [goodsInfo 商品详情页]
     * @return [type] [description]
     */
    public function goodsInfo(){
        $user_id = $this->is_login(I('admin_id'));
        $goods_id = I("goods_id/d");
        $goods = Db::name('agen_goods')->where("goods_id",$goods_id)->find();
        if(empty($goods) || ($goods['is_on_sale'] == 0)){
            exit(json_encode(array('result'=>'-11','info'=>'该商品已经下架')));
        }
        $goods_images_list = Db::name('AgenGoodsImages')->where("goods_id", $goods_id)->order('img_id desc')->select(); // 商品 图册
        $filter_spec = $this->agen_get_spec($goods_id);
               
        if ($goods['keywords']=="") {
            $goods['keywords']=$goods['goods_name'];
        }
        if ($goods['title']=="") {
            $goods['title']=$goods['goods_name'];
        }
        if ($goods['description']=="") {
            $goods['description']=$goods['goods_name'];
        }

        $spec_goods_price  = Db::name('agen_goods_price')->where("goods_id", $goods_id)->column("key,price,store_count,quantity"); // 规格 对应 价格 库存表 起订量
        
        $recommend_goods =  Db::name('agen_goods')->field('goods_id,goods_name,goods_thumb,shop_price')->where('is_recommend = 1 and examine =1 and is_on_sale = 1')->order('goods_id desc')->limit(5)->select(); // 推荐
        
        $users_address = Db::name('agen_user')->where('admin_id',$user_id)->find();

        $rs=array('result'=>'1','info'=>'请求成功','spec_goods_price'=>$spec_goods_price,'filter_spec'=>$filter_spec,'goods_images_list'=>$goods_images_list,'goods'=>$goods,'users_address'=>$users_address,'recommend_goods'=>$recommend_goods);
        exit(json_encode($rs));
    }

    /**
     * 获取商品规格
     */
    public function agen_get_spec($goods_id)
    {
        $this->is_login(I('admin_id'));
        //商品规格 价钱 库存表 找出 所有 规格项id
        $keys = Db::name('AgenGoodsPrice')->where("goods_id", $goods_id)->getField("GROUP_CONCAT(`key` SEPARATOR '_') ");
        $filter_spec = array();
        if ($keys) {
            $specImage = Db::name('AgenSpecImage')->where(['goods_id'=>$goods_id,'src'=>['<>','']])->column("spec_image_id,src");// 规格对应的 图片表， 例如颜色
            $keys = str_replace('_', ',', $keys);
            $sql = "SELECT a.name,a.order,b.* FROM __PREFIX__agen_spec AS a INNER JOIN __PREFIX__agen_spec_item AS b ON a.id = b.spec_id WHERE b.id IN($keys) ORDER BY b.id";
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

    /*商品相关结束*/
    /*========================================================================*/
    /*个人中心相关开始*/

    /**
     * [user_index 用户中心-账号与安全]
     * @return [type] [description]
     */
    public function user_index(){
        $user_id = $this->is_login(I('admin_id'));
        $users = Db::name('agen_user')->where('admin_id',$user_id)->find();
        if ($users['head_pic']) {
            $users['head_pic'] = "public/upload/yichuang/head_pic/".$users['head_pic'];
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
            $path ='public/upload/yichuang/head_pic/';
            if (!file_exists($path)){
                mkdir($path,0777,true);
            }//如果地址不存在，创建地址
            $u=uniqid().date("ymdHis",time()).rand(111,999);
            $picname=$path.$u.$type;
            file_put_contents($picname,$IMG);
            $head_pic['head_pic']=$u.$type;
        }
        $head_pic['save_time'] = time();
        $d = Db::name('agen_user')->where('admin_id',$data['admin_id'])->update($head_pic);
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
        $us = Db::name('agen_user')->where(["admin_id" => $user_id,"password" => $password])->find();
        if (!$us) {
            exit(json_encode(array('result'=>'-1','info'=>'原密码输入错误')));
        }
        if(!preg_match('/^[0-9]+$/',$data['password'])){
            exit(json_encode(array('result'=>'-2','info'=>'密码应为纯数字')));
        }
        if ($data['password'] !== $data['password_s']) {
            exit(json_encode(array('result'=>'-3','info'=>'两次密码输入不一致')));
        }
        $paw = Db::name('agen_user')->where('admin_id',$user_id)->update(['password'=>encrypt($data['password']),'save_time'=>time(),'updata_paw'=>1]);
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
        $user_address = Db::name('agen_user')->where('admin_id',$user_id)->find();
        if (IS_POST) {
            $data = I('');
            $data['save_time'] = time();
            $save = Db::name('agen_user')->where('admin_id',$user_id)->update($data);
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
        $count = Db::name('agen_order')->where($where)->where($wheres)->count();
        $page_html = input('page_html')?input('page_html'):1; //前端页码
        $count_html = 5;
        if ($page_html) {
            $firstRow = ($page_html-1) * $count_html;
        }
        $order_str = "order_id DESC";
        $field = "order_id,order_amount,total_amount,order_status,shipping_name,shipping_code,order_sn,pay_time";
        $order_list = Db::name('agen_order')->order($order_str)->where($where)->where($wheres)->field($field)->limit($firstRow.','.$count_html)->select();
        //获取订单商品
        foreach($order_list as $k=>$v)
        {
            $data = $this->agen_get_order_goods($v['order_id']);
            $order_list[$k]['goods_list'] = $data['result'];
        }
        $rs=array('result'=>'1','info'=>'请求成功','order_status'=>config('agen_order_status'),'lists'=>$order_list,'active'=>'order_list','active_status'=>I('get.type'),'count'=>$count);
        exit(json_encode($rs));
    }

    /*
     * 获取订单商品
     */
    public function agen_get_order_goods($order_id){
        $this->is_login(I('admin_id'));
        $sql = "SELECT og.*,g.goods_thumb FROM __PREFIX__agen_order_goods og LEFT JOIN __PREFIX__agen_goods g ON g.goods_id = og.goods_id WHERE order_id = :order_id";
        $bind['order_id'] = $order_id;
        $goods_list = DB::query($sql,$bind);
        $return['status'] = 1;
        $return['msg'] = '';
        $return['result'] = $goods_list;
        return $return;
    }

    /*
     * 取消订单
     */
    public function cancel_order(){
        $user_id = $this->is_login(I('admin_id'));
        $order_id = I('get.order_id/d');
        $order = Db::name('agen_order')->where(array('order_id'=>$order_id,'user_id'=>$user_id))->find();
        //检查是否未支付订单 已支付联系客服处理退款
        if(empty($order)){
            exit(json_encode(array('status'=>-1,'msg'=>'订单不存在','result'=>'')));
        }
        //检查是否未支付的订单
        if($order['pay_status'] > 0 || $order['order_status'] > 0){
            exit(json_encode(array('status'=>-2,'msg'=>'支付状态或订单状态不允许','result'=>'')));
        }
        $row = Db::name('agen_order')->where(array('order_id'=>$order_id,'user_id'=>$user_id))->update(array('order_status'=>4));
        $data['order_id'] = $order_id;
        $data['action_user'] = $user_id;
        $data['action_note'] = '您取消了订单';
        $data['order_status'] = 4;
        $data['pay_status'] = $order['pay_status'];
        $data['shipping_status'] = $order['shipping_status'];
        $data['log_time'] = time();
        $data['status_desc'] = '用户取消订单';        
        Db::name('agen_order_action')->insert($data);//订单操作记录
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
        $del = $orderLogic->agen_delOrder($order_id);
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
        $confirm=Db::name('agen_order')->where('order_id',$order_id)->update(['order_status'=>2]);
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
        $take = Db::name('agen_order')->where('order_id',$order_id)->update(['order_status'=>3]);
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
        $result = $this->agen_addCart($goods_id, $goods_num, $goods_spec,$this->session_id,$user_id,$is_logo); // 将商品加入购物车
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
        // if ($post["goods_num"]) {
        //     dump($post );
        //     dump($post_goods_num );
        //     dump($post_cart_select );
        //     die;
        // }
        $where['session_id'] = $this->session_id;// 默认按照 session_id 查询
        // 如果这个用户已经等了则按照用户id查询
        if($user_id){
            unset($where);
            $where['user_id'] = $user_id;
        }
        $cartList = Db::name('AgenCart')->where($where)->column("id,goods_num,selected,prom_type,prom_id");
        if($post_goods_num)
        {
            // 修改购物车数量 和勾选状态
            foreach($post_goods_num as $key => $val)
            {   
                $data['goods_num'] = $val < 1 ? 1 : $val;
                $data['selected'] = $post_cart_select[$key] ? 1 : 0 ;  
                if(($cartList[$key]['goods_num'] != $data['goods_num']) || ($cartList[$key]['selected'] != $data['selected'])) {                     
                    Db::name('AgenCart')->where("id", $key)->update($data);
                }
            }
        }
        $result = $this->agen_cartList($user_id, $this->session_id,1,1,0); // 选中的商品
        if(empty($result['total_price'])){
            $result['total_price'] = Array( 'total_fee' =>0, 'cut_fee' =>0, 'num' => 0);
        }
        $rs=array('result'=>'1','info'=>'请求成功','cartList'=>$result['cartList'],'total_price'=>$result['total_price']);
        exit(json_encode($rs));
    }
    
    /**
     * ajax 删除购物车的商品
     */
    public function ajaxDelCart()
    {       
        $this->is_login(I('admin_id'));
        $ids = input("cart_id"); // 商品 ids
        $result = Db::name("AgenCart")->where("id", "in", $ids)->delete(); // 删除用户数据
        $return_arr = array('status'=>1,'msg'=>'删除成功','result'=>''); // 返回结果状态       
        exit(json_encode($return_arr));
    }
     /**
     * 购物车第二步确定页面
     */
    public function orderconfirm()
    {   
        $user_id = $this->is_login(I('admin_id'));
        if($this->agen_cart_count($user_id,1) == 0 ){
            exit(json_encode(array('result'=>'-11','info'=>'你的购物车没有选中商品','url'=>"home/Agen/cart")));
        }
        
        $result = $this->agen_cartList($user_id, $this->session_id,1,1,1); // 获取购物车商品
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
        if($this->agen_cart_count($user_id,1) == 0 ) exit(json_encode(array('status'=>-2,'msg'=>'你的购物车没有选中商品','result'=>null))); // 返回结果状态
        
        $order_goods = Db::name('agen_cart')->where(['user_id'=>$user_id,'selected'=>1])->select();
        //calculate_price()计算订单金额
        $result = $this->calculate_price($user_id,$order_goods);
        if($result['status'] < 0){
            exit(json_encode($result));   
        }
        
        $car_price = array(
            'total_amount' => $result['result']['total_amount'], // 订单总价
            'payables'     => $result['result']['order_amount'], // 应付金额
            'goodsFee'     => $result['result']['goods_price'],  // 商品价格            
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
        $user = Db::name('agen_user')->where("admin_id", $user_id)->find();// 找出这个用户
        if (empty($order_goods)){
            return array('status' => -9, 'msg' => '商品列表不能为空', 'result' => '');
        }
        foreach ($order_goods as $key => $val) {
            $order_goods[$key]['goods_fee'] = $val['goods_num'] * $val['member_goods_price'];    // 小计
            $order_goods[$key]['store_count'] = agen_getGoodNum($val['goods_id'], $val['spec_key']); // 最多可购买的库存数量
            if ($order_goods[$key]['store_count'] <= 0){
                return array('status' => -10, 'msg' => $order_goods[$key]['goods_name'] . "库存不足,请重新下单", 'result' => '');
            }
            $goods_price += $order_goods[$key]['goods_fee']; // 商品总价
            $cut_fee += $val['goods_num'] * $val['market_price'] - $val['goods_num'] * $val['goods_price']; // 共节约
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
     * 一创 购物车列表
     * @param type $user 用户
     * @param type $session_id session_id
     * @param type $selected 是否被用户勾选中的 0 为全部 1为选中  一般没有查询不选中的商品情况
     * $mode 0  返回数组形式  1 直接返回result
     * $Choice  提交订单时将selected为1的条件加入查询
     */
    function agen_cartList($user_id, $session_id = '', $selected = 0, $mode = 0, $Choice = 0,$goods_id='')
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

        $cart = Db::name('AgenCart')->where($where)->bind($bind)->order('add_time desc , supplier_id asc ')->limit($limit)->select();  // 获取购物车商品
        if ($cart) {
            // 选中商品所有商品
            $supplier_selected = Db::name('AgenCart')->where($where)->bind($bind)->field('supplier_id')->limit($limit)->group('supplier_id')->select();
            foreach ($supplier_selected as $k => $val) {

                $cart_num = Db::name('AgenCart')->where($where)->where('supplier_id', $val['supplier_id'])->bind($bind)->limit($limit)->count();
                $cart_selected = Db::name('AgenCart')->where($where)->where('supplier_id', $val['supplier_id'])->where('selected', '1')->bind($bind)->limit($limit)->count();
                if ($cart_num == $cart_selected){
                    $su_selected[$val['supplier_id']] = 1;
                }else{
                    $su_selected[$val['supplier_id']] = 0;
                }
            }
            // 获取图片
            foreach ($cart as $k => $val) {
                $val['goods_thumb'] = Db::name('agen_goods')->where('goods_id', $val['goods_id'])->Cache(YLT_CACHE_TIME)->value('goods_thumb');
                $carts[] = $val;
            }
            $shipping_price = $anum = $total_price = $cut_fee = 0;
            $cartList = [];
            foreach ($carts as $k => $val) {
                $cartList[$val['supplier_id']]['supplier_name'] = $val['supplier_name'];
                $cartList[$val['supplier_id']]['supplier_id'] = $val['supplier_id'];
                $cartList[$val['supplier_id']]['is_designer'] = $val['is_designer'];
                $cartList[$val['supplier_id']]['selected'] = $su_selected[$val['supplier_id']];    //   选中状态
                $val['store_count'] = agen_getGoodNum($val['goods_id'], $val['spec_key']);        // 最多可购买的库存数量
                $anum += $val['goods_num'];
                $cartList[$val['supplier_id']]['list'][] = $val;
                // 如果要求只计算购物车选中商品的价格 和数量  并且  当前商品没选择 则跳过
                if ($selected == 1 && $val['selected'] == 0){
                    continue;
                }

                $cartList[$val['supplier_id']]['total_price'] += ($val['goods_num'] * $val['goods_price']); //商铺商品总价

                //市场价不为0时，计算出本店售价比市场价便宜了多少钱
                if ($val['market_price']!=0) {
                    $cut_fee += $val['goods_num'] * $val['market_price'] - $val['goods_num'] * $val['goods_price']; 
                }  
                $total_price += $val['goods_num'] * $val['goods_price'];
            }
        }
        $total_price = array('total_fee' => $total_price, 'cut_fee' => $cut_fee, 'num' => $anum); // 总计
        setcookie('cn', $anum, null, '/');
                
        if ($mode == 1) {
            exit(json_encode(array('cartList' => $cartList, 'total_price' => $total_price))); 
        }
        exit(json_encode(array('status' => 1, 'msg' => '', 'result' => array('cartList' => $cartList, 'total_price' => $total_price)))); 
    }


    /**
     * 一创 查看购物车的商品数量
     * @param type $user_id
     * $mode 0  返回数组形式  1 直接返回result
     */
    public function agen_cart_count($user_id, $mode = 0)
    {
        $this->is_login(I('admin_id'));
        $count = Db::name('AgenCart')->where(['user_id' => $user_id, 'selected' => 1])->count();
        if ($mode == 1) return $count;

        return array('status' => 1, 'msg' => '', 'result' => $count);
    }

    /**
     * 一创 加入购物车方法
     * @param type $goods_id 商品id
     * @param type $goods_num 商品数量
     * @param type $goods_spec 选择规格
     * @param type $user_id 用户id
     */
    function agen_addCart($goods_id, $goods_num, $goods_spec, $session_id, $user_id = 0,$is_logo = 0, $cart_id = 0,$selected = 1)
    {   
        $this->is_login(I('admin_id'));
        $goods = Db::name('AgenGoods')->where("goods_id", $goods_id)->find(); // 找出这个商品
        $specGoodsPriceList = Db::name('agen_goods_price')->where("goods_id", $goods_id)->column("key,key_name,price,store_count,sku,quantity"); // 获取商品对应的规格价钱 库存 条码
        $where = " session_id = :session_id ";
        $bind['session_id'] = $session_id;
        $now_time = time();
        $user_id = $user_id ? $user_id : 0;
        if ($user_id) {
            $where .= "  or user_id= :user_id ";
            $bind['user_id'] = $user_id;
        }
        $catr_count = Db::name('AgenCart')->where($where)->bind($bind)->count(); // 查找购物车商品总数量
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
        if (empty($goods) || $goods['is_on_sale'] == 0){
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
        $catr_goods = Db::name('AgenCart')->where($where)->bind($cart_bind)->find(); // 查找购物车是否已经存在该商品
        $price = $spec_price ? $spec_price : $goods['shop_price']; // 如果商品规格没有指定价格则用商品原始价格

        $data = array(
            'user_id' => $user_id,   // 用户id
            'session_id' => $session_id,   // sessionid
            'goods_id' => $goods_id,   // 商品id
            'goods_sn' => $goods['goods_sn'],   // 商品货号
            'goods_name' => $goods['goods_name'],   // 商品名称
            'market_price' => $goods['market_price'],   // 市场价
            'goods_price' => $price,  // 购买价
            'cost_price' => $goods['cost_price'],  // 成本价
            'member_goods_price' => $price,  // 会员折扣价 默认为 购买价
            'goods_num' => $goods_num, // 购买数量
            'spec_key' => "{$spec_key}", // 规格key
            'spec_key_name' => "{$specGoodsPriceList[$spec_key]['key_name']}", // 规格 key_name
            'sku' => "{$specGoodsPriceList[$spec_key]['sku']}", // 商品条形码
            'add_time' => time(), // 加入购物车时间
            'prom_type' => $goods['prom_type'],   // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
            'prom_id' => $goods['prom_id'],   // 活动id
            'supplier_id' => $goods['supplier_id'],   // 入驻商ID
            'supplier_name' => $goods['supplier_name'],
            'is_designer' => $goods['is_designer'],
            'quantity' => $quantity,         //商品起订量
            'commission_price' => $goods['commission_price'],    //佣金
            'goods_thumb' => $goods['goods_thumb'],   //商品缩略图
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
            $result = Db::name('AgenCart')->where("id", $cart_id)->update($update); 
            $cart_count = agen_cart_goods_num($user_id, $session_id);            // 查找购物车数量
            setcookie('cn', $cart_count, null, '/');
            exit(json_encode(array('status' => 1, 'msg' => '成功加入购物车', 'result' => $cart_count))); 
        } else {
            $insert_id = Db::name('AgenCart')->insert($data);
            if (!empty($cart_id) && $insert_id) {
                Db::name('AgenCart')->where('id', $cart_id)->delete();  //购物车直接修改规格
            }

            $cart_count = agen_cart_goods_num($user_id, $session_id); // 查找购物车数量
            setcookie('cn', $cart_count, null, '/');
            exit(json_encode(array('status' => 1, 'msg' => '成功加入购物车', 'result' => $cart_count))); 
        }
        $cart_count = agen_cart_goods_num($user_id, $session_id); // 查找购物车数量
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
        $order_count = Db::name('AgenOrder')->where("user_id", $user_id)->where('order_sn', 'like', date('Ymd') . "%")->count(); // 查找购物车商品总数量
        if ($order_count >= 30){
            exit(json_encode(array('status' => -9, 'msg' => '为避免刷单，一天只能下30个订单', 'result' => ''))); 
        }
        $cart = Db::name('AgenCart')->where(['user_id' => $user_id, 'selected' => 1])->order('supplier_id asc ')->select();
        $AgenUser = Db::name('AgenUser')->where(['admin_id' => $user_id])->find();
        $cartList = [];

        // 分商铺订单
        foreach ($cart as $k => $val) {
            $val['store_count'] = agen_getGoodNum($val['goods_id'], $val['spec_key']);        // 最多可购买的库存数量
            $cartList[$val['supplier_id']]['total_price'] += ($val['goods_num'] * $val['goods_price']);
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
                'source' => 'PC',
                'is_parent' => '1',
                'supplier_id' => "",
            );
            $new_order_id = Db::name("AgenOrder")->insertGetId($order);
            if (!$new_order_id){
                exit(json_encode(array('status' => -8, 'msg' => '添加订单失败', 'result' => NULL))); 
            }
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
                'source' => 'PC',
                'supplier_id' => 41,
                'supplier_name' => $val['supplier_name'],
                'is_designer' => 0,
            );
            $data['order_id'] = $order_id = Db::name("AgenOrder")->insertGetId($data);
            if (!$order_id){
                exit(json_encode(array('status' => -8, 'msg' => '添加订单失败', 'result' => NULL))); 
            }

            // 1插入order_goods 表
            foreach ($val['list'] as $key => $va) {
                $goods = Db::name('AgenGoods')->where(array('goods_id' => $va['goods_id'], 'is_on_sale' => '1'))->find();
                if ($goods) {
                    $data2['order_id'] = $order_id; // 订单id
                    $data2['goods_id'] = $va['goods_id']; // 商品id
                    $data2['goods_name'] = $va['goods_name']; // 商品名称
                    $data2['goods_sn'] = $va['goods_sn']; // 商品货号
                    $data2['goods_num'] = $va['goods_num']; // 购买数量
                    $data2['market_price'] = $va['market_price']; // 市场价
                    $data2['goods_amount'] = $va['goods_num'] * $va['goods_price'];
                    $data2['goods_price'] = $va['goods_price']; // 商品价
                    $data2['spec_key'] = $va['spec_key']; // 商品规格
                    $data2['spec_key_name'] = $va['spec_key_name']; // 商品规格名称
                    $data2['member_goods_price'] = $va['member_goods_price']; // 会员折扣价
                    $data2['discount_price'] = $goods['cost_price']; // 成本价
                    $data2['give_integral'] = $goods['give_integral']; // 购买商品赠送积分
                    $data2['prom_type'] = $va['prom_type']; // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
                    $data2['prom_id'] = $va['prom_id'];  // 活动id
                    $data2['is_logos'] = $va['is_logo']; // 是否定制LOGO
                    $data2['goods_thumb'] = $va['goods_thumb']; //缩略图
                    Db::name("AgenOrderGoods")->insertGetId($data2);
                    //提交订单后删除购物车
                    Db::name('AgenCart')->where(['user_id' => $user_id,'id' => $va['id']])->delete();
                }else{
                    Db::name('AgenCart')->where(['user_id' => $user_id, 'id' => $va['id']])->delete();
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
            Db::name('agen_order_action')->insertGetId($action_info);

        }
        if ($new_order_id == 0) {
            $new_order_id = $order_id;
        }

        //发送短信
        $config = tpCache('sms'); // 获取缓存中的短信信息
        $tel=$AgenUser['mobile'];       //收货电话
        $name=$AgenUser['consignee'];   //收货人
        $ordername="一个";              //模板订单信息
        //一创科技短信提醒模板
        $data_phone = [
            'apikey' => $config['apikey'],
            'mobile' => "13922852605",//发送的手机号  一创负责人
            // 'tpl_id' => "3245600",
            'tpl_id' => "3254446",
            'tpl_value' =>""
        ];
        $this->sendCodes($data_phone);
        $datas_phone = [
            'apikey' => $config['apikey'],
            'mobile' => "18126091429",//发送的手机号  李贵围
            'tpl_id' => "3254446",
            'tpl_value' =>""
        ]; 
        $this->sendCodes($datas_phone);

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
        $upload_path = ".public/upload/yichaung"; //上传文件的存放路径
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