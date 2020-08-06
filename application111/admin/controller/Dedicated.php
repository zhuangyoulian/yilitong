<?php
namespace ylt\admin\controller;
use think\AjaxPage;
use think\Page;
use think\Db;
use think\Loader;
use think\Request;
use think\Url;
use ylt\admin\logic\GoodsLogic;
use ylt\admin\model\GoodsActivity;


class Dedicated extends Base {
    
    
    /*
     * 活动专区类型列表
     */
    public function index(){
        //获取活动专区列表
        
        $count =  Db::name('Dedicated')->count();
        $Page = new Page($count,10);
        $show = $Page->show();
        $lists = Db::name('Dedicated')->order('add_time desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        // dump($lists);die;
        $this->assign('lists',$lists);
        $this->assign('pager',$Page);// 赋值分页输出
        $this->assign('page',$show);// 赋值分页输出   
        $this->assign('Dedicateds',C('COUPON_TYPE'));
        return $this->fetch();
    }

    /*
     * 添加编辑一个活动专区类型
     */
    public function dedicated_info(){
        if(IS_POST){
            $data = I('post.');
            if (!$data['logo'] || !$data['brand'] || !$data['name'] || !$data['remark']) {
                $this->ajaxReturn(['status' => -1, 'msg' => '内容不可为空', 'result' => '']);
            }
            if ($data['goods_id']) {
                $data['goods_id'] = implode(',', $data['goods_id']);
            }
            if(empty($data['id'])){
                $data['add_time'] = time();
                $row = Db::name('Dedicated')->insert($data);
            }else{
                $row=Db::name('Dedicated')->where(array('id'=>$data['id']))->update($data);
            }
            if($row !== false){
                $this->ajaxReturn(['status' => 1, 'msg' => '编辑成功', 'result' => '']);
            }else{
                $this->ajaxReturn(['status' => 0, 'msg' => '编辑失败', 'result' => '']);
            }
        }
        $cid = I('get.id/d');
        if($cid){
            $Dedicated = Db::name('Dedicated')->where(array('id'=>$cid))->find();
            if ($Dedicated['goods_id']) {
                $prom_goods = Db::name('goods')->where("goods_id in ($Dedicated[goods_id])")->select();
                $this->assign('prom_goods',$prom_goods);
            }
            $this->assign('Dedicated',$Dedicated);
        }    
        return $this->fetch();
    }
    /*
    *添加商品
     */
    public function search_goods()
    {
        $GoodsLogic = new GoodsLogic;
        $brandList = $GoodsLogic->getSortBrands();
        $this->assign('brandList', $brandList);
        $categoryList = $GoodsLogic->getSortCategory();
        $this->assign('categoryList', $categoryList);

        $goods_id = I('goods_id');
        $where = ' is_on_sale = 1 and store_count>0 and examine = 1';//搜索条件
        if (!empty($goods_id)) {
            $where .= " and goods_id not in ($goods_id) ";
        }
        I('intro') && $where = "$where and " . I('intro') . " = 1";
        if (I('cat_id')) {
            $this->assign('cat_id', I('cat_id'));
            $grandson_ids = getCatGrandson(I('cat_id'));
            $where = " $where  and cat_id in(" . implode(',', $grandson_ids) . ") "; // 初始化搜索条件
        }
        if (I('brand_id')) {
            $this->assign('brand_id', I('brand_id'));
            $where = "$where and brand_id = " . I('brand_id');
        }
        if (!empty($_REQUEST['keywords'])) {
            $this->assign('keywords', I('keywords'));
            $where = "$where and (goods_name like '%" . I('keywords') . "%' or keywords like '%" . I('keywords') . "%')";
        }
        $count = Db::name('goods')->where($where)->count();
        $Page = new Page($count, 10);
        $goodsList = Db::name('goods')->where($where)->order('goods_id DESC')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $show = $Page->show();//分页显示输出
        $this->assign('page', $show);//赋值分页输出
        $this->assign('goodsList', $goodsList);
        $this->assign('pager', $Page);//赋值分页输出
        $tpl = I('get.tpl', 'search_goods');

        return $this->fetch($tpl);
    }

    /*
     * 删除活动专区类型
     */
    public function del_Dedicated(){
        //获取活动专区ID
        $cid = I('get.id/d');
        //查询是否存在活动专区
        $row = Db::name('Dedicated')->where(array('id'=>$cid))->delete();
        if ($row) {
            $this->success("删除成功");
        }else{
            $this->success("删除失败");
        }
    }


    /*
     * 活动专区详细商品查看
     */
    public function dedicated_list(){
        //获取活动专区ID
        $cid = I('get.id/d');
        $Dedicated = Db::name('Dedicated')->where(array('id'=>$cid))->find();
        if ($Dedicated['goods_id']) {
            $prom_goods = Db::name('goods')->where("goods_id in ($Dedicated[goods_id])")->select();
            $count = count($prom_goods);
            $Page = new Page($count, 10);
            $goodsList = Db::name('goods')->where("goods_id in ($Dedicated[goods_id])")->limit($Page->firstRow.','.$Page->listRows)->select();
            $show = $Page->show();//分页显示输出
            $this->assign('page', $show);//赋值分页输出
            $this->assign('pager', $Page);//赋值分页输出
            $this->assign('goodsList',$goodsList);
        }
        return $this->fetch();
    }
}