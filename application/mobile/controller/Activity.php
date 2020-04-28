<?php
/**
 * Created by PhpStorm.
 * User: lijiayi
 * Date: 2017/3/24
 * Time: 14:45
 */
namespace ylt\mobile\controller;
use think\Controller;
use think\Url;
use think\Page;
use think\Db;
class Activity extends MobileBase {


    /**
     * 中秋---月中活动
     */
     public function mid_month(){

        return $this->fetch();
    }


    /**
     * 折扣专区
     */
     public function discount(){
		$id = input('id',0);
		$discountInfo = Db::name('discount_buy')->where('id',$id)->where('is_start',1)->find();
		if(empty($discountInfo)){
			$this->error('活动已下架');
        }
		$this->assign('info',$discountInfo);
        return $this->fetch();
    }

    /**
     * 适用产品优惠券
     */
     public function couponList(){
        $filter_param = array();
        $goods_id = I('goods_id',0);
        $filter_param['goods_id']=$goods_id;
        $id = explode(',', $goods_id); 
        $sort = I('sort','goods_id'); // 排序
        $sort_asc = I('sort_asc','desc'); // 排序
         // dump($sort);die;
        $filter_goods_id = Db::name('goods')->where("is_on_sale=1 and examine=1 and is_designer = 0")->where('goods_id','in',$id)->cache(true,YLT_CACHE_TIME)->column("goods_id");

        $count = count($filter_goods_id);
        $page = new Page($count,config('PAGESIZE'));
        if($count > 0)
        {
            $goods_list = Db::name('goods')->where("goods_id","in", implode(',', $filter_goods_id))->order("$sort $sort_asc")->limit($page->firstRow.','.$page->listRows)->select();
            $filter_goods_id2 = get_arr_column($goods_list, 'goods_id');
            if($filter_goods_id2)
                $goods_images = Db::name('goods_images')->where("goods_id", "in", implode(',', $filter_goods_id2))->cache(true)->select();
        }
        $this->assign('filter_param',$filter_param);
        $this->assign('goods_list',$goods_list);
        $this->assign('page',$page);// 赋值分页输出
        $this->assign('sort_asc', $sort_asc == 'asc' ? 'desc' : 'asc');
        config('TOKEN_ON',false);
        if(input('is_ajax'))
            return $this->fetch('ajaxCouponList');
        else
            return $this->fetch();
    }

     /**
     * 适用产品优惠券
     */
     public function ajaxCouponList(){
       

        return $this->fetch();
    }


    /**
     * 折扣专区 分页加载
     */
	public function ajaxDiscount(){
		$id = input('id',0);
		$p = I('p/d',1);
		$goodsList=Db::name('discount_goods')->alias('d')->join('goods g','d.goods_id=g.goods_id')->where('discount_id',$id)->where('g.is_on_sale = 1 and g.examine =1')->order('d.goods_id desc')->field('d.goods_id,d.goods_name,d.goods_thumb,d.activity_price,d.activity_market_price')->page($p,config('PAGESIZE'))->cache(true,YLT_CACHE_TIME)->select();
		$this->assign('goodsList',$goodsList);
        return $this->fetch();
    }
	
	public function officialNotice(){
		
		$p = I('p/d',1);
		
		$list = Db::name('article')->field('article_id,title,description,publish_time,thumb')->where("is_open='1' and cat_id ='4'")->page($p,config('PAGESIZE'))->select();
		
		$this->assign('list',$list);
		return $this->fetch();
    }
	
	public function noticeDetails(){
		
		$id = input('id',0);
		
		$info = Db::name('article')->where('article_id',$id)->find();
        $click = $info['click'] + 1;
        $res = Db::name('article')->where('article_id',$id)->update(['click'=>$click]);		
		$this->assign('info',$info);
		return $this->fetch();
    }

    
    
}
