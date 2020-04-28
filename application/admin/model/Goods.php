<?php
namespace ylt\admin\model;
use think\Model;
use think\Db;
use think\Request;
class Goods extends Model {
   
    /**
     * 后置操作方法
     * 自定义的一个函数 用于数据保存后做的相应处理操作, 使用时手动调用
     * @param int $goods_id 商品id
     */
    public function afterSave($goods_id)
    {            
         // 商品货号
         $goods_sn = "YLT".str_pad($goods_id,7,"0",STR_PAD_LEFT);   
         $this->where("goods_id = $goods_id and goods_sn = ''")->save(array("goods_sn"=>$goods_sn)); // 根据条件更新记录
                 
         // 商品图片相册  图册
         $goods_images = I('goods_images/a');
         if(count($goods_images) > 1)
         {                          
             array_pop($goods_images); // 弹出最后一个             
             $goodsImagesArr = Db::name('GoodsImages')->where("goods_id = $goods_id")->column('img_id,image_url'); // 查出所有已经存在的图片
             
             // 删除图片
             foreach($goodsImagesArr as $key => $val)
             {
                 if(!in_array($val, $goods_images))
                     Db::name('GoodsImages')->where("img_id = {$key}")->delete(); //
             }
             // 添加图片
             foreach($goods_images as $key => $val)
             {
                 if($val == null)  continue;                                  
                 if(!in_array($val, $goodsImagesArr))
                 {                 
                        $data = array(
                            'goods_id' => $goods_id,
                            'image_url' => $val,
                        );
                        Db::name("GoodsImages")->insert($data); // 实例化User对象
                 }
             }
         }
         // 查看主图是否已经存在相册中
         $original_img = I('original_img');
         $c = Db::name('GoodsImages')->where("goods_id = $goods_id and image_url = '{$original_img}'")->count();
         if($c == 0 && $original_img)
         {
             Db::name("GoodsImages")->add(array('goods_id'=>$goods_id,'image_url'=>$original_img));
         }
      //   delFile("./public/upload/goods/thumb/$goods_id"); // 删除缩略图
         
         // 商品规格价钱处理        
         Db::name("GoodsPrice")->where('goods_id = '.$goods_id)->delete(); // 删除原有的价格规格对象
         if(I('item/a'))
         {
             $spec = Db::name('Spec')->column('id,name'); // 规格表
             $specItem = Db::name('SpecItem')->column('id,item');//规格项
                          
             foreach(I('item/a') as $k => $v)
             {
                   // 批量添加数据
                   $v['price'] = trim($v['price']);
                   $store_count = $v['store_count'] = trim($v['store_count']); // 记录商品总库存
                   $v['sku'] = trim($v['sku']);
                   $dataList[] = ['goods_id'=>$goods_id,'key'=>$k,'key_name'=>$v['key_name'],'price'=>$v['price'],'store_count'=>$v['store_count'],'sku'=>$v['sku'],'quantity'=>$v['quantity']];
                    // 修改商品后购物车的商品价格也修改一下
                    Db::name('cart')->where("goods_id = $goods_id and spec_key = '$k'")->save(array(
                            'market_price'=>$v['price'], //市场价
                            'goods_price'=>$v['price'], // 本店价
                            'member_goods_price'=>$v['price'], // 会员折扣价                        
                            ));                   
             }            
             Db::name("GoodsPrice")->insertAll($dataList);
             
         }   
         
         // 商品规格图片处理
         if(I('item_img/a'))
         {    
             Db::name('SpecImage')->where("goods_id = $goods_id")->delete(); // 把原来是删除再重新插入
             foreach (I('item_img/a') as $key => $val)
             {                 
                 Db::name('SpecImage')->insert(array('goods_id'=>$goods_id ,'spec_image_id'=>$key,'src'=>$val));
             }                                                    
         }
         refresh_stock($goods_id); // 刷新商品库存
    }
}
