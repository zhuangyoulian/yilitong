<?php
use think\Route;

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

return [
    '__pattern__' => [
        'name' => '\w+',
    ],
    '[hello]'     => [
        ':id'   => ['index/hello', ['method' => 'get'], ['id' => '\d+']],
        ':name' => ['index/hello', ['method' => 'post']],
    ],
    
    'trade/:id' =>['home/Article/tradeList'],
    
    'wanke' =>['home/caseindex/wanke'],
    'pinan' =>['home/caseindex/pinan'],
    'muwu' =>['home/caseindex/muwu'],
    'cuntian' =>['home/caseindex/cuntian'],
    'jiameng' =>['home/caseindex/jiameng'],
     'mall' => ['home/Index/mall'],
 	'goodsInfo/:id' =>['home/Goods/goodsInfo'],
	//'goodsInfo/:id' =>['/home/Goods/goodsInfo'],

 	// url('/goodsInfo','id=5');
	'goodsList/:id' =>['home/Goods/goodsList'],
	//'goodsList/:id' =>['/home/Goods/goodsList'],
	
	// url('/goodsList','id=5');
	'article/:article_id' =>['home/Article/detail'],
	//'article/:article_id' =>['/home/Article/detail'],
	// url('/article','article_id=5');
	
	'newProduct' =>['home/Goods/newProduct'],
  	'activity' =>['home/Goods/activity'],
	//'newProduct' =>['/home/Goods/newProduct'],
	// url('/article','article_id=5');
	
	'businessPurchase' =>['home/Article/businessPurchase'],
	//'businessPurchase' =>['/home/Article/businessPurchase'],
	// url('/article','article_id=5');
	
    '__domain__'=>[
		
  		// 泛域名规则建议在最后定�?
    	
    	],
   
];
