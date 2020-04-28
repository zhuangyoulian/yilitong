<?php
/**
 * Created by PhpStorm.
 * User: yjq
 * Date: 2018/11/29
 * Time: 17:30
 * name:询报价供应链contronller
 */
namespace ylt\mobile\controller;
use ylt\home\logic\SupplierLogic;
use ylt\home\model\UsersLogic;
use think\Db;
use think\Url;
use think\Request;
use think\Session;

class Supplychainview extends MobileBase {


	/*  不用登陆就可以查看报价列表和商品详情
	 * 名企直采- 采购需求列表
	 */

	public function offer_list()
	{
//			p($_SESSION);die();
		unset($_SESSION['supply_id']);
		unset($_SESSION['supplylist_id']);
        $res = Db::name("purchase")
            ->where("status=1")
            ->order("id desc")
            ->limit(0,10)
            ->select();
        foreach($res as &$v){
            $v['content']= Db::name("purchase_list")->where("purchase_id = $v[id]")->select();
            $v['budget'] = $v['budget'] / 10000;
        }
//        p($res);exit();
//		$db=Db::name('purchase');
//		//连表查询查出 purchase id 和 purchase_list purchase_id 关联的数据
//		$res=$db->alias('a')->join('purchase_list l','a.id = l.purchase_id')
//			->field('l.add_time,a.id as pid,a.be_viewed,l.id,a.title,a.sustomized,l.goods_img,l.goods_name,l.goods_num,a.budget,a.company_name,a.view,l.p_num,l.t,l.goods_unit')
//            ->limit(0,10)
//            ->order('a.id desc')
//            ->group("l.add_time")
//			->where('status=1')
//			->select();
		//$resJson=json_encode($res);
		$this->assign('res',$res);
		return $this->fetch();
	}

	/*
	 * 询报价 报价1
	 */

	public function offer_one()
	{
		if($_GET['id']){
			$id=I('get.id');
		}else{
			$id=1;
		}
        $db = Db::name("purchase_list");
        $res = $db->where('purchase_id='.$id)->select();
        $data = Db::name("purchase")->where('id='.$id)->find();
//        p($res);exit();
//		$db=Db::name('purchase');
//		$res=$db->alias('a')->join('purchase_list l','a.id = l.purchase_id')
//			->field('l.id,a.title,a.sustomized,l.goods_img,l.goods_name,l.goods_num,a.budget,a.company_name,a.view,l.p_num,l.t,l.goods_unit,l.goods_norm,l.goods_color,l.goods_brand,a.dead_time,a.expect_time,a.city,a.area,l.goods_remarks,l.purchase_id')->limit(0,5)->order('a.id desc')
//			->where('l.id='.$id)
//			->find();
//        $goods= Db::name("purchase_list")->where("t","=",$res['t'])->field("goods_name")->select();
//        $res['goods_name']=$goods;
//        p($res);exit();
		$db->where('id='.$id)->setInc('view'); //增加查看次数 + 1
		// dump($data);die;
		$this->assign('res',$res);
		$this->assign('data',$data);
		return $this->fetch();
	}



}