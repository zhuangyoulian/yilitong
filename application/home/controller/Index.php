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
        $field = 'goods_id,goods_name,shop_price,goods_thumb,cat_id';
        $fields = 'ad_code,ad_link,ad_name';
        // banner
        $ad_banner = Db::name('ad')->where('pid = 59 and enabled =1')->field($fields)->order('orderby desc')->find();
        //今日推荐
        $ad_hot = Db::name('ad')->where('pid = 56 and enabled =1')->field($fields)->order('orderby asc')->limit(5)->select();
        //礼品专区
        $hot_cat_id_arr = getCatGrandson (1070);// 过滤筛选的结果集里面找商品 
        $code_hot['goods'] = Db::name('goods')->where(['is_on_sale'=>1,'examine'=>1,'is_designer'=>0,'is_delete'=>0,'cat_id'=>['in',implode(',', $hot_cat_id_arr)]])->cache(true)->order('goods_id desc')->limit(6)->field($field)->select();
        $code_hot['ad'] = Db::name('ad')->where('pid = 57 and enabled =1')->field($fields)->order('orderby desc')->limit(2)->select();
        $code_hot['ad_link'] = "/Home/Goods/goodsList/id/1070";

        //健康养生等  
        $type = I('type',2); //（健2办4数5家6户7汽8工9）
        $cat_id_arr = getCatGrandson ($type);// 过滤筛选的结果集里面找商品 
        $classify_goods['goods'] = Db::name('goods')->where(['is_on_sale'=>1,'examine'=>1,'is_designer'=>0,'is_delete'=>0,'cat_id'=>['in',implode(',', $cat_id_arr)]])->cache(true)->order('goods_id desc')->limit(6)->field($field)->select();
        $classify_goods['ad'] = Db::name('ad')->where('pid = 58 and enabled =1')->where('orderby',$type)->field($fields)->find();
        $classify_goods['ad_link'] = "/Home/Goods/goodsList/id/".$type;

        //热卖+推荐
        $is_hot = Db::name('goods')->where('examine = 1 and is_recommend =1 and is_on_sale = 1 and is_designer = 0 and is_delete = 0 and is_hot = 1')->field($field)->order('goods_id desc')->limit(10)->select();

        $rs=array('result'=>'1','info'=>'请求成功','ad_banner'=>$ad_banner,'ad_hot'=>$ad_hot,'code_hot'=>$code_hot,'classify_goods'=>$classify_goods,'is_hot'=>$is_hot,'goods_category_tree'=>$this->goods_category_tree,'scenario_category_tree'=>$this->scenario_category_tree);
        exit(json_encode($rs));
    }

    /**
     * [index 首页]
     * @return [type] [description]
     */
  	public function index(){
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

        //品牌
        $brand_list = $this->new_brand();

        //供应商
        $supplier_list['list']=Db::name('supplier')//查询所有有商品的供应商
            ->where("supplier_id in (".implode(',',$supplierArr).")")
            ->where("is_designer=0 and status=1 and logo!=''")
            ->field('supplier_id,supplier_name,logo')
            ->order('add_time desc')
            ->limit(0,30)
            ->select();
        $supplier_list['count']=Db::name('supplier')//查询所有有商品的供应商
            ->where("supplier_id in (".implode(',',$supplierArr).")")
            ->where("is_designer=0 and status=1 and logo!=''")
            ->field('supplier_id,supplier_name,logo')
            ->order('add_time desc')
            ->count();

        
        //为您推荐
        $is_hot = Db::name('goods')->field("goods_id,goods_name,goods_thumb,shop_price,supplier_id")->where('examine = 1 and is_recommend =1 and is_on_sale = 1 and is_designer = 0')->order('sort')->limit(0,6)->select();//获取推荐商品信息

        //采购信息新代码
        $res['count']= Db::name("purchase")->where("status=1")->count();/*采购信息的总数*/
        $res['list'] = Db::name("purchase")
            ->where("status=1")
            ->where('dead_time','>',time())
            ->order("id desc")
            ->limit(0,8)
            ->cache(true,3600)
            ->select();
        foreach($res['list'] as &$v){
            $v['count'] = Db::name("purchase_list")->where("purchase_id = $v[id]")->count();
            $v['content']= Db::name("purchase_list")->where("purchase_id = $v[id]")->select();
            $v['budget'] = $v['budget'] / 10000;
        }
        //新代码结束

        //礼品方案
        $scheme['list'] = Db::name('article')->where("thumb != ' ' and is_open = 1 and is_ecommend = 1 and cat_id = 98")->order("publish_time desc")->limit(3)->Field("thumb as image,title,article_id")->select();
        $scheme['count'] = Db::name('article')->where("thumb != ' ' and is_open = 1 and is_ecommend = 1 and cat_id = 98")->order("publish_time desc")->Field("article_id")->count();
        
        
        //首页活动资讯
        $article = Db::name('article')->where("thumb != ' ' and is_open = 1 and is_ecommend = 1 and cat_id != 98")->order("publish_time desc")->limit(4)->Field("thumb as image,title,article_id,add_time,click")->select();

        $rs=array('result'=>'1','info'=>'请求成功','res'=>$res,'is_hot'=>$is_hot,'brand_list'=>$brand_list,'supplier_list'=>$supplier_list,'goods_category_tree'=>$this->goods_category_tree,'scenario_category_tree'=>$this->scenario_category_tree,'scheme'=>$scheme,'article'=>$article);
        exit(json_encode($rs));

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
            $data['count'] = Db::name("brand")
                ->alias('b')
                ->join('goods g','b.id=g.brand_id')
                ->where("b.logo != '' and b.is_hot = 1 and g.examine = 1 and is_on_sale = 1 ")
                ->field("b.id,b.name,b.logo")
                ->group('b.id')
                ->count();
            $data['list'] = Db::name("brand")
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

        $rs=array('result'=>'1','info'=>'请求成功','favourite_goods'=>$favourite_goods);
        exit(json_encode($rs));
    }

    /**
     * [supplierList 更多供应商(内页)]
     * @return [type] [description]
     */
    public function supplierList(){
        $sort       = I('get.sort','add_time'); // 排序
        $sort_asc   = I('get.sort_asc','desc'); // 排序
        $p          = I('p/d',1);
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
        $count = count($haveShopSupplier);
        $supplierArr=[];//将商品表所有有商品的供应商id装在这里
        foreach($haveShopSupplier as $value){
            foreach($value as $key){
                $supplierArr[]=$key;
            }
        }
        if(count($supplierArr) > 0)
        {
            $supplier_newList = Db::name('supplier')->where('is_designer=0 and status=1')->where("supplier_id in (".implode(',',$supplierArr).")")->order("$sort $sort_asc")->page($p,12)->select();
        }

        $rs=array('result'=>'1','info'=>'请求成功','supplier_newList'=>$supplier_newList,'count'=>$count);
        exit(json_encode($rs));

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
            ->field('b.logo,b.id,b.name')
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
        $rs=array('result'=>'1','info'=>'请求成功','brand_list'=>$brand_list);
        exit(json_encode($rs));
    }
}