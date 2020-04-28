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
use think\Page;
use think\Verify;
use think\Db;
use think\Request;
use think\Cache;
use ylt\home\logic\GoodsLogic;
use ylt\home\logic\SupplierLogic;
use ylt\home\model\UsersLogic;

class Index extends Base {
    
    /**
     * [mall 旗舰商城]
     * @return [type] [description]
     */
    public function mall(){
		$is_hot = Db::name('goods')->where('examine = 1 and is_recommend =1 and is_on_sale = 1 and is_designer = 0')->order('sort')->limit(1,15)->select();
        $this->assign('is_hot',$is_hot);
        return $this->fetch();
    }

    /**
     * [index 首页]
     * @return [type] [description]
     */
  	public function index(){
        $gifts = Db::name('gifts_category')->Cache(true,YLT_CACHE_TIME)->order('sort_order DESC')->select();//获取对应场景分类数据
        $purchase_lists = Db::name('purchase')->where('status',1)->where('dead_time','>',time())->order('id desc')->limit(0,20)->cache(true,3600)->select();//获取采购信息
      	$supplier_id = Db::name('supplier')->field('supplier_id')->where('is_designer=0 and status=1')->order('add_time desc')->cache(true,3600)->select();//获取所有供应商id
        $arr=[];//将供应商表里所有供应商id装在这里
        foreach($supplier_id as $value){
            foreach($value as $key){
                $arr[]=$key;
            }
        }
        $haveShopSupplier=Db::name('goods')//查询所有有商品的供应商
            ->field('supplier_id')
            ->where("supplier_id in (".implode(',',$arr).") and is_on_sale=1 and examine=1 and is_recommend=1")
            ->group('supplier_id')
            ->order('supplier_id desc')
            ->select();
        $supplierArr=[];//将商品表所有有商品的供应商id装在这里
        foreach($haveShopSupplier as $value){
            foreach($value as $key){
                $supplierArr[]=$key;
            }
        }
        $supplier_list=Db::name('supplier')//查询所有有商品的供应商
            ->where("supplier_id in (".implode(',',$supplierArr).")")
            ->where("is_designer=0 and status=1 and logo!=''")
            ->field('supplier_id,supplier_name,logo')
            ->order('add_time desc')
            ->limit(0,30)
            ->select();
      	$supplier_lists = Db::name('supplier')->where('is_designer=0 and status=1')->order('add_time desc')->limit(0,30)->cache(true,3600)->select();//获取供应商信息

        //品牌
        $brand_list = $this->new_brand();
        
        $is_hot = Db::name('goods')->field("goods_id,goods_name,goods_thumb,sales_sum,prom_type,shop_price")->where('examine = 1 and is_recommend =1 and is_on_sale = 1 and is_designer = 0')->order('sort')->limit(0,8)->select();//获取推荐商品信息

        //新代码
        $res = Db::name("purchase")
            ->where("status=1")
            ->where('dead_time','>',time())
            ->order("id desc")
            ->limit(0,8)
            ->cache(true,3600)
            ->select();
        foreach($res as &$v){
            $v['count'] = Db::name("purchase_list")->where("purchase_id = $v[id]")->count();
            $v['content']= Db::name("purchase_list")->where("purchase_id = $v[id]")->select();
            $v['budget'] = $v['budget'] / 10000;
        }
        $purchase_count= Db::name("purchase")->where("status=1")->count();/*采购信息的总数*/
        //新代码结束
        $region_list = get_region_list();
        $brands=array();
        for($i=0;$i<count($brand_list);$i++){
            if($i%2==0){
                $brands[$i][0]=$brand_list[$i];
                $brands[$i][1]=$brand_list[$i+1];
            }
        }

      	$suppliers=array();
        for($i=0;$i<count($supplier_list);$i++){
            if($i%2==0){
                $suppliers[$i][0]=$supplier_list[$i];
                $suppliers[$i][1]=$supplier_list[$i+1];
            }
        }
        //首页活动资讯
        $article['arry'] = Db::name('article')->where("thumb != ' ' and is_open != 0 and is_ecommend != 0")->order("publish_time desc")->limit(2)->Field("thumb as image,title,article_id")->select();
        $article['arr'] = Db::name('article')->where("cat_id = 3 and is_open = 1")->order("publish_time desc")->limit(8)->Field("publish_time as time,title,article_id")->select();

        $this->assign("arrry",$article['arry']);
        $this->assign("arr",$article['arr']);

      	/* 获取登录商户的信息  可能有漏洞*/
     	$supplier = session('supplier');
     	$admin_id=$supplier['admin_id'];
      	$this->assign("supplier",$supplier);
		$row = Db::name('supplier_user')->alias('u')->join('supplier s', array('s.supplier_id=u.supplier_id'),'left')->where('u.admin_id',$admin_id)->find();//重新检查商户的状态
		session('supplier',$row);

        $this->assign('images',$goods_images_list);
        $this->assign('map_list',$res);
        $this->assign('purchase_count',$purchase_count);
        $this->assign('is_hot',$is_hot);
        $this->assign('brands',$brands);
        $this->assign('suppliers',$suppliers);
        $this->assign('supplier_lists',$supplier_lists);
        $this->assign('gifts',$gifts);
        $this->assign('supplier_id',$supplier_id);
        $this->assign('region_list',$region_list);
        return $this->fetch();
    }


    /**
     * [spike 首页活动/限时折扣/秒杀 产品查询]
     * @return [type] [description]
     */
    public  function spike(){
        $data=Db::name("discount_goods")
            ->where('discount_id',1)
            ->field('activity_count,activity_market_price,goods_name,goods_thumb,order_num,activity_price')
            ->limit(0,8)
            ->select();
        foreach ($data as $key => $value) {
            $data[$key]['order_num']=round($value['order_num']/$value['activity_count']*100,2)."％";//处理为百分比数据
        }
        // dump($data);die;
        echo json_encode($data, JSON_HEX_TAG);
    }


    /**
     * [new_brand 首页知名品牌 查询]
     * @return [type] [description]
     */
    public  function new_brand(){
        $list   = 30;
        if(IS_AJAX){
            $data = Db::name("brand")
                ->alias('b')
                ->join('goods g','b.id=g.brand_id')
                ->where("b.logo != '' and b.is_hot = 1 and g.examine = 1 and is_on_sale = 1 and g.is_hot = 1")
                //条件为logo不为空、商家推荐、产品审核通过、产品上架、产品热门
                ->field("b.id,b.name,b.logo")
                ->group('b.id')
                ->order('rand()')
                ->limit($list)
                ->select();
            $brands=array();
            for($i=0;$i<count($data);$i++){
                if($i%2==0){
                    $brands['list'][$i]["row"][0]=$data[$i];
                    $brands['list'][$i]["row"][1]=$data[$i+1];
                }
            }
            return json($brands);
        }else{
            $data = Db::name("brand")
                ->alias('b')
                ->join('goods g','b.id=g.brand_id')
                ->where("b.logo != '' and b.is_hot = 1 and g.examine = 1 and is_on_sale = 1 ")
                ->field("b.id,b.name,b.logo")
                ->group('b.id')
                ->order('rand()')
                ->limit($list)
                ->select();
            return $data;
        }
    }
 
    /**
     *  公告详情页
     */
    public function notice(){
        return $this->fetch();
    }
    
 
    
    /**
     * [verify 验证码]
     * @return [type] [description]
     */
    public function verify()
    {
        //验证码类型
        $type = I('get.type') ? I('get.type') : '';
        $fontSize = I('get.fontSize') ? I('get.fontSize') : '40';
        $length = I('get.length') ? I('get.length') : '4';
        
        $config = array(
            'fontSize' => $fontSize,
            'length' => $length,
            'useCurve' => true,
            'useNoise' => false,
        );
        $Verify = new Verify($config);
        $Verify->entry($type);        
    }
    
    /**
     * [promoteList 促销活动页面]
     * @return [type] [description]
     */
    public function promoteList()
    {
        $goodsList = DB::query("select * from __PREFIX__goods as g inner join __PREFIX__panic_buying as f on g.goods_id = f.goods_id   where ".time()." > start_time  and ".time()." < end_time");
        $brandList = Db::name('brand')->column("id,name,logo");
        $this->assign('brandList',$brandList);
        $this->assign('goodsList',$goodsList);
        return $this->fetch();
    }
    
    function truncate_tables (){
        $tables = DB::query("show tables");
        $table = array('tp_admin','tp_config','tp_region','tp_system_module','tp_admin_role','tp_system_menu','tp_article_cat');
        foreach($tables as $key => $val)
        {                                    
            if(!in_array($val['tables_in_tpshop'], $table))                             
                echo "truncate table ".$val['tables_in_tpshop'].' ; ';
                echo "<br/>";         
        }                
    }
	
	    // PC端微信支付二维码
    public function qr_code(){   
        vendor('phpqrcode.phpqrcode'); 
        error_reporting(E_ERROR);            
        $url = urldecode($_GET["data"]);
        \QRcode::png($url);
        exit; 
    }
	

    /**
     * 猜你喜欢
     * @author lxl
     * @time 17-3-23
     */
    public function ajax_favorite(){
        $p = I('p/d',1);
        $i = I('i',5); //显示条数
        $favourite_goods = Db::name('goods')->where("is_recommend=1 and is_on_sale=1 and is_hot")->order('goods_id DESC')->page($p,$i)->cache(true,YLT_CACHE_TIME)->select();//首页推荐商品
        $this->assign('favourite_goods',$favourite_goods);
        return $this->fetch();
    }

    /**
     * [supplierList 更多供应商(内页)]
     * @return [type] [description]
     */
    public function supplierList(){
        $sort = I('get.sort','add_time'); // 排序
        $sort_asc = I('get.sort_asc','desc'); // 排序
        $supplier_id = Db::name('supplier')->field('supplier_id')->where('is_designer=0 and status=1')->order('add_time desc')->cache(true,3600)->select();//获取所有供应商id
        $arr=[];//将供应商表里所有供应商id装在这里
        foreach($supplier_id as $value){
            foreach($value as $key){
                $arr[]=$key;
            }
        }
        $haveShopSupplier=Db::name('goods')//查询所有有商品的供应商
        ->field('supplier_id')
            ->where("supplier_id in (".implode(',',$arr).") and is_on_sale=1 and examine=1")
            ->group('supplier_id')
            ->select();
        $supplierArr=[];//将商品表所有有商品的供应商id装在这里
        foreach($haveShopSupplier as $value){
            foreach($value as $key){
                $supplierArr[]=$key;
            }
        }
      	$page = new Page(count($supplierArr),12);
        if(count($supplierArr) > 0)
        {
            $supplier_newList = Db::name('supplier')->where('is_designer=0 and status=1')->where("supplier_id in (".implode(',',$supplierArr).")")->order("$sort $sort_asc")->limit($page->firstRow.','.$page->listRows)->select();
        }
        $this->assign('supplier_newList',$supplier_newList);
        $this->assign('page',$page);
        return $this->fetch();
    }
  	
    /**
     * [brandList 更多品牌]
     * @return [type] [description]
     */
    public function brandList(){
      	$brandList = Db::name("brand")
            ->alias('b')
            ->join('goods g','b.id=g.brand_id')
            ->where("b.logo != '' and b.is_hot = 1 and g.examine = 1 and is_on_sale = 1")
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
        $this->assign('brandList',$brand_list);
        return $this->fetch();

    }
}