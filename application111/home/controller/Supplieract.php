<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/11/9
 * Time: 9:50
 */
namespace ylt\home\controller;
use think\Db;
use think\Url;
use think\Page;
class Supplieract extends Base{

    public $user_id = 0;
    public $user = array();
    /*
     * 处理登录后需要的参数
     */
    public function _initialize() {
        parent::_initialize();
        if(session('?user'))
        {
            $user = session('user');
            $user = Db::name('users')->where("user_id", $user['user_id'])->find();
            session('user',$user);  //覆盖session 中的 user
            $this->user = $user;
            $this->user_id = $user['user_id'];
            $this->assign('user',$user); //存储用户信息
            $this->assign('user_id',$this->user_id);
			if($user['exchange_points'] <= '0'){
				$this->redirect('User/index');
                exit;
			}
				
        }else{
            $nologin = array(
                'user_login','logout','login'

            );
            if(!in_array(ACTION_NAME,$nologin)){

                $this->redirect('User/user_login');
                exit;
            }
        }
        //用户中心面包屑导航
        $navigate_user = navigate_user();
        $this->assign('navigate_user',$navigate_user);
    }

    /*
     * 兑换专区
     */
    public function exchange(){
		$user = session('user');
		$p = I('p/d',1);

		unset($user['password']);
		unset($user['user_money']);
		unset($user['frozen_money']);
		
		$exchange_log = Db::name('exchange_log')->where('user_id',$this->user_id)->select();
		
		$count = Db::name('exchange_goods')->count();

		$goods_id = Db::name('exchange_goods')->order('sort')->page($p.',20')->column('goods_id');
		$goods_id = implode(',', $goods_id);
		$exchange = Db::name('exchange_goods')->order('sort')->group('goods_id')->page($p.',20')->select();
		
		$goods_list = Db::name('goods')->where('goods_id','in',$goods_id)->select();
		
		
		
		$Page = new Page($count,20);
        $show = $Page->show();
        $this->assign('pager',$Page);
        $this->assign('page',$show);
		
		$this->assign('exchange',$exchange);
		$this->assign('goods_list',$goods_list);
		$this->assign('exchange_log',$exchange_log);
		$this->assign('user',$user);
        return $this->fetch();
    }
	
	
	/**
    * 商品详情页
    */ 
    public function goodsInfo(){
        
        //  form表单提交      
        $goodsLogic = new \ylt\home\logic\GoodsLogic();
        $goods_id = I("get.id/d");
        $goods = Db::name('Goods')->where("goods_id",$goods_id)->find();
        
        if(empty($goods) || ($goods['is_on_sale'] == 0)){
        	$this->error('该商品已经下架',Url::build('Index/index'));
        }
    
        if($goods['brand_id']){
            $brnad = Db::name('brand')->where("id",$goods['brand_id'])->find();
            $goods['brand_name'] = $brnad['name'];
        }  
        $goods_images_list = Db::name('GoodsImages')->where("goods_id", $goods_id)->order('img_id desc')->select(); // 商品 图册
	    $filter_spec = $goodsLogic->get_spec($goods_id);
		$goods['logo'] = Db::name('supplier_config')->where(['supplier_id'=>$goods['supplier_id'],'name'=>'store_logo'])->value('value');
               

        if ($goods['keywords']=="") {
        	$goods['keywords']=$goods['goods_name'];
        }
        if ($goods['title']=="") {
        	$goods['title']=$goods['goods_name'];
        }
        if ($goods['description']=="") {
        	$goods['description']=$goods['goods_name'];
        }
		
		$exchange = Db::name('exchange_goods')->where('goods_id',$goods_id)->find();
		$goods['shop_price'] = $exchange['price'];
        $spec_goods_price  = Db::name('goods_price')->where("goods_id", $goods_id)->column("key,price,store_count"); // 规格 对应 价格 库存表
		
        $commentStatistics = $goodsLogic->commentStatistics($goods_id);// 获取某个商品的评论统计
        $point_rate = tpCache('shopping.point_rate');
        $this->assign('freight_free', $freight_free);// 全场满多少免运费
        $this->assign('spec_goods_price', json_encode($spec_goods_price,true)); // 规格 对应 价格 库存表
        $this->assign('navigate_goods',navigate_goods($goods_id,1));// 面包屑导航
        $this->assign('commentStatistics',$commentStatistics);//评论概览
        $this->assign('filter_spec',$filter_spec);//规格参数
        $this->assign('goods_images_list',$goods_images_list);//商品缩略图
        $this->assign('siblings_cate',$goodsLogic->get_siblings_cate($goods['cat_id']));//相关分类
        //$this->assign('look_see',$goodsLogic->get_look_see($goods));//看了又看      
        $this->assign('goods',$goods);

        $this->assign('point_rate',$point_rate);        
        return $this->fetch();        
    }
	
    /**
    * 商品详情页
    */
    public function ex_success(){
        return $this->fetch();
    }
	
	public function confirmExchange(){
		
		 if($this->user_id == 0)
            $this->error('请先登陆',Url::build('Home/User/login'));
		
		
		$goods_id =  input('goods_id');
		$spec_key = input('key');
		$goods_num = intval(input('goods_num',1));
		$address_id = input('address_id');
		
		$goods = Db::name('goods')->where('goods_id = '.$goods_id.' and examine = 1 and is_on_sale = 1')->find();
		if(empty($goods))
			$this->error('该商品已售馨',Url::build('Supplieract/exchange'));
		
		$exchange = Db::name('exchange_goods')->where('goods_id',$goods_id)->find();
		if(empty($exchange))
			$this->error('该商品已售馨',Url::build('Supplieract/exchange'));
		
		if(($exchange['goods_num'] - $exchange['buy_num']) < $goods_num)
			$this->error('该商品可兑换数量不足',Url::build('Supplieract/exchange'));
		
	
		if(IS_POST){
	
			$user = session('user');
			$exchange_points = ($exchange['price'] * $goods_num);
			if($exchange_points > $user['exchange_points'])
				$this->error('您的兑换点数不足以兑换商品',Url::build('Supplieract/exchange'));
			
			$address = Db::name('user_address')->where('address_id',$address_id)->find();
			if(empty($address))
			  $this->error('请选择收货地址',Url::build('Supplieract/exchange'));
		  
			$region = get_region_list();
			
			$data['user_id'] 		= $this->user_id;
			$data['use_points']		= -$exchange_points; //使用积分数量
			$data['exchange_points']= ($user['exchange_points'] - $exchange_points); //剩余积分数量
			$data['describe']		= '兑换商品';
			$data['goods_id'] 		= $goods_id;
			$data['goods_name'] 	= $goods['goods_name'];
			$data['spec_key_name'] 	= $spec_key;
			$data['add_time']		= time();
			$data['goods_num']		= $goods_num;
			$data['user_note']		= input('user_note');
			$data['address']		= $region[$address['province']]['name']. $region[$address['city']]['name'] .$region[$address['district']]['name'] .$region[$address['twon']]['name']. $address['address'];
			$data['consignee']		= $address['consignee'];
			$data['mobile']			= $address['mobile'];
			
			
			$res = Db::name('exchange_log')->insert($data);
			
			Db::name('users')->where('user_id',$this->user_id)->setDec('exchange_points',$exchange_points);
			Db::name('exchange_goods')->where('goods_id',$goods_id)->setInc('buy_num',$goods_num);
			
			if($res)
              exit(json_encode(['status'=>1,'msg'=>'兑换成功','result'=>$res]));   
		  exit(json_encode(['status'=>-1,'msg'=>'兑换失败，请稍后重试','result'=>$res])); 
		}
		
		if(!empty($spec_key))
			$spec_key_name = Db::name('goods_price')->where("goods_id = ".$goods_id." and `key` = '".$spec_key."'")->value('key_name');
		$goods['goods_price'] = $exchange['price'];
		$goods['goods_num'] = $goods_num;
		$goods['spec_key_name'] = $spec_key_name;
		$this->assign('exchange',$exchange); // 兑换的商品 
		$this->assign('goods', $goods); // 兑换的商品                
        $this->assign('total_price', $result['total_price']); // 总计      
		return $this->fetch();
	}




}