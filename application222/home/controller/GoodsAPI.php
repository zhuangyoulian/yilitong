<?php
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
class GoodsAPI extends Base {
    public function var_json(){
        //上市新品API数据返回接口
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST');
        header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
        // 秘钥 8d0b6604701b9a30c2547ffb8ee6a3c1
        // $jsoncallback = htmlspecialchars($_REQUEST ['jsoncallback']);//把预定义的字符转换为 HTML 实体。
        if (!isset($_GET['token'])||empty($_GET['token'])) {
            exit("秘钥不能为空");
        }
        $token=md5("famous");

        if ($token==$_GET['token']) {
            //            上市新品api地址:http://www.tptest.com//home/GoodsAPI/var_json?token=8d0b6604701b9a30c2547ffb8ee6a3c1
            // //查询数据//获取推荐商品信息
            //where条件查询参数
            //examine   审核通过
            //is_new    是否新品
            //is_on_sale    是否上架
            //g.is_delete   是否删除
            //s.province    省份
            //s.city    城市
            //s.area    区域
            $is_hot = Db::name('goods')
            // echo Db::name('goods')->fetchsql()
                ->alias('g')
                ->join('supplier s','g.supplier_id=s.supplier_id')
                ->field("g.goods_id,g.goods_name,g.sales_sum,g.prom_type,g.shop_price,s.province,s.city,s.area,s.company_name")
                ->where("examine = 1 and is_new =1 and is_on_sale = 1 and g.is_delete = 0 and s.province !='' and s.city !='' and s.area !='' ")
                ->order(['g.sort'=>'DESC','g.goods_id'=>'DESC'])
                ->limit(0,30)
                ->select();
                // dump($is_hot);die;
            //查询表B的字段内容嵌入到表A的某个字段
            foreach ($is_hot as $key => $value) {                
                $is_hot[$key]['image_url']=Db::name('GoodsImages')->where('goods_id',$value['goods_id'])->field('image_url')->order('img_id','desc')->select();
            }  
            // dump($is_hot);die;
            $data=array();
            $region_list = get_region_list();//获取省市区域

            foreach ($is_hot as $key => $value) {
                if (count($value['image_url'])>4) {
                    $data[$key]['goods_id']=$value['goods_id'];
                    $data[$key]['goods_name']=$value['goods_name'];
                    $data[$key]['sales_sum']=$value['sales_sum'];
                    $data[$key]['prom_type']=$value['prom_type'];
                    $data[$key]['shop_price']=$value['shop_price'];
                    $data[$key]['company_name']=$value['company_name'];
                    $data[$key]['province']=$region_list[$value['province']]['name'];
                    $data[$key]['city']=$region_list[$value['city']]['name'];
                    $data[$key]['area']=$region_list[$value['area']]['name'];
                    $data[$key]['image_url']=$value['image_url'];
                }
            }
            // dump($data);die;
            $json_data=json_encode($data, JSON_HEX_TAG);
            // echo $jsoncallback . "(" . $json_data . ")";
            echo $json_data;
            exit();
        }else{
            exit("秘钥错误");
        }
    }

    public function popular_json(){
        //热门礼品API数据返回接口
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST');
        header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
        // 秘钥 5fa3852b08951fcdc4d2e60f89a85bfe
        // $jsoncallback = htmlspecialchars($_REQUEST ['jsoncallback']);//把预定义的字符转换为 HTML 实体。
        if (!isset($_GET['token'])||empty($_GET['token'])) {
            exit("秘钥不能为空");
        }
        $token=md5("popular");
        // dump($token);die;
        if ($token==$_GET['token']) {
            //            热门礼品新品api地址:http://www.tptest.com//home/GoodsAPI/popular_json?token=5fa3852b08951fcdc4d2e60f89a85bfe
            // //查询数据//获取推荐商品信息
            //where条件查询参数
            //examine   审核通过
            //is_hot    是否热卖
            //is_on_sale    是否上架
            //g.is_delete   是否删除
            //s.province    省份
            //s.city    城市
            //s.area    区域
            $is_hot = Db::name('goods')
                ->alias('g')
                ->join('supplier s','g.supplier_id=s.supplier_id')
                ->field("g.goods_id,g.goods_name,g.sales_sum,g.prom_type,g.shop_price,s.province,s.city,s.area,s.company_name")
                ->where("examine = 1 and is_hot =1 and is_on_sale = 1 and g.is_delete = 0 and s.province !='' and s.city !='' and s.area !='' ")
                ->order(['g.sort'=>'DESC','g.goods_id'=>'DESC'])
                ->limit(0,30)
                ->select();
            //查询表B的字段内容嵌入到表A的某个字段
            foreach ($is_hot as $key => $value) {                
                $is_hot[$key]['image_url']=Db::name('GoodsImages')->where('goods_id',$value['goods_id'])->field('image_url')->order('img_id','desc')->select();
            }  
            $data=array();
            $region_list = get_region_list();//获取省市区域
            foreach ($is_hot as $key => $value) {
                if (count($value['image_url'])>4) {
                    $data[$key]['goods_id']=$value['goods_id'];
                    $data[$key]['goods_name']=$value['goods_name'];
                    $data[$key]['sales_sum']=$value['sales_sum'];
                    $data[$key]['prom_type']=$value['prom_type'];
                    $data[$key]['shop_price']=$value['shop_price'];
                    $data[$key]['company_name']=$value['company_name'];
                    $data[$key]['province']=$region_list[$value['province']]['name'];
                    $data[$key]['city']=$region_list[$value['city']]['name'];
                    $data[$key]['area']=$region_list[$value['area']]['name'];
                    $data[$key]['image_url']=$value['image_url'];
                }
            }
            // dump($data);die;
            $json_data=json_encode($data, JSON_HEX_TAG);
            // echo $jsoncallback . "(" . $json_data . ")";
            echo $json_data;
            exit();
        }else{
            exit("秘钥错误");
        }
    }
}


