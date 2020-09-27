<?php
namespace ylt\admin\validate;
use think\Validate;
class GoodsAttribute extends Validate
{       
    

    
    
    // 验证规则
    protected $rule = [
        ['attr_name','require','属性名称必须填写'],
        ['type_id', 'require', '所属商品类型必须选择'],
        ['attr_values','checkAttrValues','可选值列表不能为空'],
    ];
      
    /**
     *  自定义函数 判断 用户选择 从下面的列表中选择 可选值列表：不能为空
     * @param type $attr_values
     * @return boolean
     */
    protected function checkAttrValues($attr_values,$rule)
    {                
        if((trim($attr_values) == '') && (I('attr_input_type') == '1'))
            return '可选值列表不能为空';
        else
            return true;
     }    
}