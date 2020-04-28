<?php
/**
 * Created by PhpStorm.
 * User: jiayi
 * Date: 2017/3/21
 * Time: 19:51
 */
use think\Db;
use think\Cache;
use think\Url;

function getAdUrl($type,$id){
    switch ($type) {
        //商品详情页
        case 0:
            return Url::build("Goods/goodsInfo",array('id'=>$id));
            break;
        case 1:           
            break;
        case 2:
            return Url::build("Goods/categoryList",array('id'=>$id));
            break;
        case 3:
            return Url::build("Goods/goodsList_S",array('id'=>$id));
            break;
        case 4:
            return Url::build("Supplier/index",array('id'=>$id));
            break;
        case 5:
            return Url::build("Caseindex/full_cut");
            break;
        case 6;
            return Url::build("Activity/discount",array('id'=>$id));
            break;
        case 7;
            return Url::build("Cart/cart",array('id'=>$id));
            break;
        case 8;
            return Url::build("User/login",array('id'=>$id));
            break;
        case 9;
            return Url::build("Supplier/recsupplier",array('id'=>$id));
            break;
        case 10;
            return Url::build("Works/WorksList",array('id'=>$id));
            break;
        case 11;
            return Url::build("Activity/discount",array('id'=>$id));
            break;
        case 12;
            return Url::build();
            break;
		case 13;
            return Url::build("Goods/giftStrategy",array('id'=>$id));
            break;    
		case 14;
            return Url::build();
            break;    			
        default:
            return "javascript:void();";
            break;
    }
}
