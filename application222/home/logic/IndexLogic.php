<?php
namespace ylt\home\logic;
use think\Model;
use think\Db;


class IndexLogic extends Model
{
	/**
	 * [famousBrand 上市新品接口]
	 * @param  [type] $goods_thumb [主图]
	 * @param  [type] $image_url   [相册多图]
	 * @param  [type] $shop_price  [价格]
	 * @param  [type] $ales_sum    [销量]
	 * @param  [type] $goods_name  [商品名称]
	 * @param  [type] $prom_type   [活动状态]
	 * @return [type]              [description]
	 */
	
	// public function famousBrand($goods_thumb,$image_url,$shop_price,$ales_sum,$goods_name,$prom_type)
	public function famousBrand($data)
		{
			$opts = [
            CURLOPT_TIMEOUT        => 30,   		    //url超时设置
            CURLOPT_RETURNTRANSFER => 1,				//返回的内容作为变量储存
            CURLOPT_SSL_VERIFYPEER => false,			//禁止 cURL 验证对等证书
            CURLOPT_SSL_VERIFYHOST => false,			//不检查ssl证书
            CURLOPT_HTTPHEADER     => $header
        	];

			$is_hot = Db::name('goods')->where('examine = 1 and is_recommend =1 and is_on_sale = 1 and is_designer = 0')->order('sort')->limit(0,8)->select();//获取推荐商品信息  
        	$goods_images_list = Db::name('GoodsImages')//查询图片相册
	        	// echo Db::name('GoodsImages')
	             // ->fetchSql()
	             ->order("goods_id desc")
	             ->select(); // 商品 图册
			   echo json_encode($data);
		       // dump($data);die;
		    // return array('msg'=>'1', 'token'=>$token, 'user'=>$superUserModel);
		}
}