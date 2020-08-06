<?php
/**
 * Created by PhpStorm.
 * User: lijiayi
 * Date: 2017/3/29
 * Time: 16:45
 */
namespace ylt\admin\controller;
use ylt\admin\logic\GoodsLogic;
use think\AjaxPage;
use think\Page;
use think\Db;
use think\Url;
use think\Request;


class Visitor extends Base {

    /**
     *  商品列表
     */
    public function visitor_goods(){
        $GoodsLogic = new GoodsLogic();
        $brandList = $GoodsLogic->getSortBrands();
        $categoryList = $GoodsLogic->getSortCategory();
        $scenario = $GoodsLogic->getSortScenario();
        $this->assign('scenario',$scenario);
        $this->assign('categoryList',$categoryList);
        $this->assign('brandList',$brandList);
        return $this->fetch();
    }

    /**
     *  商品列表
     */
    public function ajaxVisitor_goods(){
        
        $where = 'supplier_id > 0 and is_delete = 0 and is_designer = 0'; // 搜索条件
        I('intro')    && $where = "$where and ".I('intro')." = 1" ;
        I('brand_id') && $where = "$where and brand_id = ".I('brand_id') ;
        (I('is_on_sale') !== '') && $where = "$where and is_on_sale = ".I('is_on_sale') ;
        $cat_id = I('cat_id');
        $extend_cat_id = I('extend_cat_id');
        // 关键词搜索
        $key_word = I('key_word') ? trim(I('key_word')) : '';
        if($key_word)
        {
            $where = "$where and (goods_name like '%$key_word%' or goods_sn like '%$key_word%' or supplier_name like '%$key_word%')" ;
        }

        if($cat_id > 0)
        {
            $grandson_ids = getCatGrandson($cat_id);
            $where .= " and cat_id in(".  implode(',', $grandson_ids).") "; // 初始化搜索条件
        }

        if($extend_cat_id > 0)
        {
            $grandson_ids = getScenarioCatGrandson($extend_cat_id);
            $where .= " and extend_cat_id in(".  implode(',', $grandson_ids).") "; // 初始化搜索条件
        }
        $model = Db::name('Goods');
        $count = $model->where($where)->count();
        $Page  = new AjaxPage($count,10);
        $show = $Page->show();
        $order_str = "`{$_POST['orderby1']}` {$_POST['orderby2']}";
        $goodsList = $model->where($where)->order($order_str)->limit($Page->firstRow.','.$Page->listRows)->select();
        $catList = Db::name('goods_category')->select();
        $catList = convert_arr_key($catList, 'id');
        $this->assign('catList',$catList);
        $this->assign('goodsList',$goodsList);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('count',$count);
        return $this->fetch();
    }

}
