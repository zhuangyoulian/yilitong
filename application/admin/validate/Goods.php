<?php
namespace ylt\admin\validate;
use think\Validate;
class Goods extends Validate
{
    
    // 验证规则
    protected $rule = [
        ['goods_name','require','商品名称必填'],
        ['original_img','require','商品图片必填'],
        ['cat_id', 'number|gt:0', '商品分类必须填写|商品分类必须选择'],
        ['goods_sn', 'unique:goods', '商品货号重复'], // 更多 内置规则 
        ['shop_price','regex:\d{1,10}(\.\d{1,2})?$','本店售价格式不对。'],
        // ['shop_price','gt:0','商品价格不能为O。'],
        ['market_price','regex:\d{1,10}(\.\d{1,2})?$','市场价格式不对。'],
        ['exchange_integral','checkExchangeIntegral','积分抵扣金额不能超过商品总额']
    ];
     
    
     
}