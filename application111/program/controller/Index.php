<?php

namespace ylt\program\controller;
use ylt\home\logic\UsersLogic;
use ylt\home\logic\GoodsLogic;
use think\Page;
use think\Db;
use think\Url;
use think\Cache;


class Index extends ProgramBase
{

    public $user_id = 0;
    public $user = array();

    /*
    * 初始化操作
    */
    public function _initialize()
    {
        parent::_initialize();
        $user = array();
        // $user = [
        //     user_id => 164850,
        //     mobile =>13728740390,
        //     mobile_validated =>1,
        //     parent_id =>0,
        // ];
        if (I('user_id')) {
        // if (session('?user')) {
            $user = session('user');
            $user = Db::name('users')->where("user_id",I('user_id'))->find();
            // $user = Db::name('users')->where("user_id",$user['user_id'])->find();
            session('user', $user);  //覆盖session 中的 user
            $this->user = $user;
            $this->user_id = $user['user_id'];
        }
    }

    /**
     * [index 首页]
     * @return [type] [description]
     */
    public function index(){
        header("Content-Type:text/html;charset=UTF-8");
        $wheress="is_on_sale=1 and examine=1 and is_hot=1";
        $wheress.=" and cat_id != 1118";
        $field = "goods_id,cat_id,extend_cat_id,goods_name,goods_thumb,shop_price,market_price,goods_remark";

        //轮播图
        $brand_list = Db::name('ad')->where('pid = 52 and enabled =1')->field('ad_code,ad_link')->select();

        //热卖
        $hot_list = Db::name('goods')->where($wheress)->field($field)->order('goods_id DESC')->limit(0,9)->select();

        //中间广告
        $ad = Db::name('ad')->where('pid = 54 and enabled =1')->field('ad_code,ad_link')->order('ad_id DESC')->find();

        //分类
        $goods_category_tree = get_goods_category_tree();
        foreach ($goods_category_tree as $key => $value) {
            $data['id'] = $value['id'];
            $data['name'] = $value['name'];
            $category[] = $data;
        } 

        $rs=array('result'=>'1','info'=>'请求成功','hot_list'=>$hot_list,'brand_list'=>$brand_list,'ad'=>$ad,'category'=>$category);
        exit(json_encode($rs));
    }

    /**
     * [classify 首页商品分类]
     * @return [type] [description]
     */
    public function goodsList(){ 
        $id = I('id/d',0); // 当前分类id
        $goodsLogic = new GoodsLogic(); // 前台商品操作逻辑类
        //查询一级分类下的所有商品
        $cat_id_arr = getCatGrandson ($id);
        $filter_goods_id = Db::name('goods')->where(['is_on_sale'=>1,'examine'=>1,'is_designer'=>0,'cat_id'=>['in',implode(',', $cat_id_arr)]])->where('cat_id != 1118')->cache(true)->column("goods_id");   
                                
        $count = count($filter_goods_id);
        $page_html = input('page_html')?input('page_html'):1; //分页页码
        if($count > 0)
        {
            $field = "goods_id,goods_name,goods_thumb,shop_price";
            $goods_list = Db::name('goods')->where("goods_id","in", implode(',', $filter_goods_id))->order("goods_id desc")->field($field)->page($page_html,config('PAGESIZE'))->select();
        }

        $rs=array('result'=>'1','info'=>'请求成功','goods_list'=>$goods_list,'id'=>$id,'count'=>$count);
        exit(json_encode($rs));   
    }


    /**
     * [search 首页关键字搜索]
     * @return [type] [description]
     */
    public function ajaxSearchGoods(){
        $filter_param = array();        // 帅选数组
        $sort = I('types','goods_id');  // 排序
        $sort_asc = I('sort_asc');      // 排序
        $q = urldecode(trim(I('q',''))); // 关键字搜索
        $q  && ($_GET['q'] = $filter_param['q'] = $q); //加入筛选条件中
        $where  = "is_on_sale = 1 AND examine = 1 AND is_designer = 0";
        $where.=" and cat_id != 1118";
        $keywords = $q;
        if ($sort_asc==0) {
            $sort_asc='desc';
        }else if($sort_asc==1){
            $sort_asc='asc';
        }
        if ($sort) {
            if($sort==2){
                $sort='sales_sum';
            }else if($sort==3){
                $sort='shop_price';
            }else if($sort==4){
                $sort='is_new';
            }
        }
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
                if(Db::name('keywords')->where('keyword',$val)->value('keyword')){
                    DB::name('keywords')->where('keyword',$val)->setInc('count');
                }else{
                    Db::name('keywords')->insert(['date'=>date('Y-m-d'),'searchengine'=>'ylt','keyword'=>$val,'count'=> 1,'source'=>'move']);
                }
            }
            
            $where .= ')';
        }else{
            
            $where .= " AND (`goods_name` LIKE '%".$keywords."%' OR `keywords` LIKE '%".$keywords."%' OR `goods_sn` LIKE '%".$keywords."%.' )";
        }
        
        $goodsLogic = new \ylt\home\logic\GoodsLogic(); // 前台商品操作逻辑类
        $filter_goods_id = Db::name('goods')->where($where)->cache(true,YLT_CACHE_TIME)->column("goods_id");

        $count = count($filter_goods_id);
        $page_html = input('page_html')?input('page_html'):1; //分页页码
        if($count > 0)
        {
            $field = "goods_id,goods_name,goods_thumb,shop_price";
            $goods_list = Db::name('goods')->where("goods_id", "in", implode(',', $filter_goods_id))->order("$sort $sort_asc")->page($page_html,config('PAGESIZE'))->field($field)->select();
        }
        $rs=array('result'=>'1','info'=>'请求成功','goods_list'=>$goods_list,'filter_param'=>$filter_param,'count'=>$count);
        exit(json_encode($rs));   
    }
  
    // 根据url调回参数 微信需要的参数都可以用这个方法
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
    //创建随机数
    private function createNonceStr($length = 16) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
    
    /**
     * 会员注册api
     */
    public function register()
    {
        include_once "wxBizDataCrypt.php";

        $data = array();

        $code = I('post.code');//请求code
        $avatarUrl = I('post.avatarUrl');//头像
        $nickName = I('post.nickName');//昵称
        $sex= I('post.sex');//性别
        $appid = 'wxbfb485ef166f598d';//小程序唯一标识   (在微信小程序管理后台获取)
        $appsecret = '5b959ca1e8235ae5f0338fdf1d4d5b61';//小程序的 app secret (在微信小程序管理后台获取)
        $grant_type = "authorization_code"; //授权（必填）

        $params = "appid=" . $appid . "&secret=" . $appsecret . "&js_code=" . $code . "&grant_type=" . $grant_type;
        $url = "https://api.weixin.qq.com/sns/jscode2session?" . $params;

        $res = json_decode($this->httpGet($url), true);
        $oauth="xiaochenxu";
        $openid=$res['openid'];
        $unionid=$res['unionid'];
        //获取用户信息
        if(isset($res['unionid'])){
            $map['unionid'] = $res['unionid'];

            $user = get_user_info($res['unionid'],4,$oauth);
        }else{
            $user = get_user_info($openid,5,$oauth);
        }
    
        if(!$user) {
            //账户不存在 注册一个
            $map['password'] = '';
            $map['openid_cx'] = $openid;
            $map['nickname'] = $nickName;
            $map['reg_time'] = time();
            $map['oauth'] = $oauth;
            $map['unionid'] = $unionid;
            $map['head_pic'] =$avatarUrl;
            $map['sex'] = empty($sex) ? 0 : $sex;
            $map['token'] = md5(time() . mt_rand(1, 99999));
            $map['referrer_id'] = $data['referrer_id'];//判断是否是分享用户
            $map['first_leader'] = 0;// 微信授权登录返回时 get 带着参数的
            $row_id = Db::name('users')->insertGetId($map);

            $recommend_code = 'us'.$row_id;
            Db::name('users')->where('user_id', $row_id)->update(['recommend_code'=>$recommend_code]);

            $user = Db::name('users')->where("user_id", $row_id)->find();

            //注册赠送礼豆
            beanGiftLog($row_id,'20','新用户注册赠送');
        }else{
            $user = Db::name('users')->where("user_id", $user['user_id'])->find();
            $user['token'] = md5(time().mt_rand(1,529918237));
            if(empty($user['referrer_id'])&&$user['user_id']!=$data['referrer_id']){
                Db::name('users')->where("user_id", $user['user_id'])->save(array('openid_cx'=>$openid,'token'=>$user['token'],'referrer_id'=>$data['referrer_id'],'last_login'=>time()));

            }else{
                Db::name('users')->where("user_id", $user['user_id'])->save(array('openid_cx'=>$openid,'token'=>$user['token'],'last_login'=>time()));
            }
        }
        session('user', $user);  //覆盖session 中的 user
        $data= array('status'=>1,'msg'=>'登陆成功','result'=>$user);
        return $this->ajaxReturn($data);
    }
}
