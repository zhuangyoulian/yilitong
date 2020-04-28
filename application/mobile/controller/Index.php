<?php

namespace ylt\mobile\controller;
use ylt\home\logic\UsersLogic;
use think\Db;
use think\Url;


class Index extends MobileBase
{
    public function index()
    {
        //轮播广告
        $time = getdate();
        $a = $time[0];
        //轮播brand  APP 12  移动端 49 充值中心 54
        $brand_roll = Db::name('ad')->where('pid=49 and enabled=1')->where("end_time>'$a'")->order('orderby DESC')->limit('0,5')->select();
        // dump($brand_roll);die;
        //金刚区 APP 27  移动端 47  充值中心 55
        $four_list = Db::name('ad')->where('pid=47 and enabled=1')->order('orderby DESC')->limit('0,10')->select();  
        //单张广告
        $single_ad = Db::name('ad')->where('pid=39 and enabled=1')->order('orderby DESC')->select();  
        // dump($single_ad);
        //品牌推荐
        $brand_sup = Db::name('recommend_brand')->where('is_on_sale=1')->order('brand_sort DESC')->select();
		
		    // 首页公告
		    $article_list = Db::name('article')->where('is_open=1 and cat_id =4')->order('cat_id DESC')->limit('5')->select();
	
        
        $brand_goods = Db::name('recommend_goods')->where('is_on_sale=1')->order('sort DESC')->select();
        foreach ($brand_goods as $key => $value) {
            $goods[$value['brand_id']][] = $value;
        }

        $this->assign('brand_goods',$goods);
        $this->assign('brand_roll',$brand_roll);
        $this->assign('brand_sup',$brand_sup);
        $this->assign('four_list',$four_list);
        $this->assign('single_ad',$single_ad);
		    $this->assign('article_list',$article_list);
        return $this->fetch();
    }
  
   public function test1_1(){
      //  $appid = "wx569dfd3bfda44a02";
      //  $appsecret = "143923579ea2fc964818509ca5a44dcc";

        //生成-获取 access_token
        $url_token = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$appsecret";
        $jsoninfo = json_decode($this->httpGet($url_token),true);
        $access_token = $jsoninfo["access_token"];

        //生成-获取 jsapi_ticket
        $ticket_url="https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=$access_token&type=jsapi";
        $jsonjsapi = json_decode($this->httpGet($ticket_url),true);
        $jsapi_ticket = $jsonjsapi['ticket'];



        //获取签名
        // 注意 URL 一定要动态获取，不能 hardcode.
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

        $timestamp = time();
        $noncestr = $this->createNonceStr();

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapi_ticket&noncestr=$noncestr&timestamp=$timestamp&url=$url";

        $signature = sha1($string);

        $signPackage = array(
            "appId"     => $appid,
            "noncestr"  => $noncestr,
            "timestamp" => $timestamp,
            "url"       => $url,
            "signature" => $signature,
            "rawString" => $string
        );
        //dump($signPackage);die();
        $this->assign('signPackage',$signPackage);

   	return $this->fetch();    
   }
   public function test(){
     
    $purchase_list = Db::name('purchase')->where('status',1)->order('id desc')->limit(0,2)->cache(true,3600)->select();//获取采购信息
    $brand_list = Db::name('brand')->where('is_hot=1')->field("id,logo")->order('id desc')->limit(0,3)->cache(true,3600)->select(); //获取品牌信息
    $supplier_list = Db::name('supplier')->where('is_designer=0 and status=1')->field("supplier_id,logo")->order('add_time desc')->limit(0,4)->cache(true,3600)->select();
    $is_hot = Db::name('goods')->where('examine = 1 and is_recommend =1 and is_on_sale = 1 and is_designer = 0')->field("goods_thumb,goods_name,shop_price,goods_id")->order('sort')->limit(0,4)->select();//获取推荐商品信息
    $this->assign('is_hot',$is_hot);
    //dump($brand_list);
    $this->assign('supplier_list',$supplier_list);
    $this->assign('brand_list',$brand_list);
    $this->assign('purchase_list',$purchase_list);
  	return $this->fetch();
   }

    /**
     * [ajaxGetMore 礼品新品]
     * @return [type] [description]
     */
	  public function ajaxGetMore(){
    	$p = I('p/d',1);
    	$favourite_goods = Db::name('goods')->where('is_on_sale=1 and examine=1 and is_designer=0 and is_delete=0')->order('add_time desc,sort desc')->page($p,config('PAGESIZE'))->cache(true,YLT_CACHE_TIME)->select();//首页新品商品
    	$this->assign('favourite_goods',$favourite_goods);
    	return $this->fetch();
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
  
  	//测试移动版首页2
	public function index_11_20(){
    	 //金刚区
       $four_list = Db::name('ad')->where('pid=27 and enabled=1')->order('orderby DESC')->limit('0,8')->select();
      //秒杀专区
	   $single_ad = Db::name('discount_goods')->where('discount_id',1)->order('sort asc')->limit(4)->select();
      
      //优选礼品
       $single_pinpai = Db::name('Goods')->where('is_hot=1 and is_recommend=1')->order('cat_id ASC')->limit(4)->select();
       //品牌推荐
       $brand_sup = Db::name('recommend_brand')->where('is_on_sale=1')->order('brand_sort DESC')->limit(8)->select();


       $brand_goods = Db::name('recommend_goods')->where('is_on_sale=1')->Cache(true,YLT_CACHE_TIME)->order('sort DESC')->select();
       foreach ($brand_goods as $key => $value) {
           $goods[$value['brand_id']][] = $value;
       }
       //获取品牌信息
       $brand_list = Db::name('brand')->where('is_hot=1')->order('id desc')->limit(8)->cache(true,3600)->select(); 
       //获取供应商信息
       $supplier_list = Db::name('supplier')->where('is_designer=0 and status=1')->order('add_time desc')->limit(8)->cache(true,3600)->select();
      
       $a = $time[0];
       $brand_roll = Db::name('ad')->where('pid=12 and enabled=1')->where("end_time>'$a'")->order('orderby DESC')->limit('0,5')->select();
	   $article_list = Db::name('article')->field('article_id,title,description,publish_time,thumb')->where("is_open='1' and cat_id ='4'")->limit(3)->select();
       $this->assign('brand_list',$brand_list);
       $this->assign('single_pinpai',$single_pinpai);
       $this->assign('brand_roll',$brand_roll);
       $this->assign('four_list',$four_list);
       $this->assign('single_ad',$single_ad);
       $this->assign('article_list',$article_list);
       $this->assign("supplier_list",$supplier_list);
       return $this->fetch();
    }
  
  	//测试移动版首页2
	public function test2(){
    	 //金刚区
       $four_list = Db::name('ad')->where('pid=27 and enabled=1')->order('orderby DESC')->limit('0,8')->select();
       //单张广告
       $single_ad = Db::name('ad')->where('pid=39 and enabled=1')->order('orderby DESC')->select();
       //品牌推荐
       $brand_sup = Db::name('recommend_brand')->where('is_on_sale=1')->order('brand_sort DESC')->limit(8)->select();

       // 首页公告
       $article_list = Db::name('article')->where('is_open=1 and cat_id =4')->order('cat_id DESC')->limit('5')->select();


       $brand_goods = Db::name('recommend_goods')->where('is_on_sale=1 and goods_id!=4681')->Cache(true,YLT_CACHE_TIME)->order('sort DESC')->select();
       $brand_list = Db::name('brand')->where('is_hot=1')->order('id desc')->limit(0,10)->cache(true,3600)->select(); //获取品牌信息
       $supplier_list = Db::name('supplier')->where('is_designer=0 and status=1')->order('add_time desc')->limit(0,10)->cache(true,3600)->select();//获取供应商信息
       $a = $time[0];
       $brand_roll = Db::name('ad')->where('pid=12 and enabled=1')->where("end_time>'$a'")->order('orderby DESC')->limit('0,5')->select();
       $this->assign('brand_list',$brand_list);
       $this->assign('brand_roll',$brand_roll);
       $this->assign('four_list',$four_list);
       $this->assign('article_list',$article_list);
       $this->assign("supplier_list",$supplier_list);
       return $this->fetch();
    }
  	
  	public function jrfw(){
    	return $this->fetch();
    }

    //资讯页面
    public function zixun(){
      return $this->fetch();
    }
  
}
