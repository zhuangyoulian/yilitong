<?php
/**
 * Created by PhpStorm.
 * User: zyl
 * Date: 2019/06/21
 * Time: 17:30
 * name:恒大表单
 */
namespace ylt\mobile\controller;
use think\Controller;
use ylt\home\logic\CartLogic;
use think\Url;
use think\Page;
use think\Db;

class Hdyujing  extends MobileBase {

	/**
	 * [hdappoint 预约列表]
	 * @return [type] [description]
	 */
	public function hdappoint(){
        return $this->fetch();
	}

	/**
	 * [hdaccount 手机号验证页面]
	 * @return [type] [description]
	 */
	public function hdaccount(){
        return $this->fetch();
	}
	/**
	 * [hdyujing 预约页面]
	 * @return [type] [description]
	 */
	public function hdyujing(){
		if ($_GET['mobile']) {
			$phone = $_GET['mobile'];
		}else{
      		$phone = input('tel');
		}
		$find=Db::name('hd_yujing')->where('tel',$phone)->find();
		if (empty($find)) {
			$this->error('该手机号没有登记记录');
		}
		if ($find['deliveryTime']) {
			$find['deliveryTime'] = date('Y-m-d',$find['deliveryTime']);
		}else{
			$find['deliveryTime'] = '';
		}
		if ($find['installDate']) {
			$find['installDate'] = date('Y-m-d',$find['installDate']);
		}else{
			$find['installDate'] ='';
		}
    	$this->assign('find',$find);

		if (IS_AJAX && IS_POST) {
			$data=I('');
			$data['deliveryTime']=strtotime($data['deliveryTime']);
			$data['installDate']=strtotime($data['installDate']);
			if($data['installDate'] < $data['deliveryTime']){
				return array('status' => 5,'msg' => '先送货才可安装',);
			}
			$d_count=Db::name('hd_yujing')->where('deliveryTime',$data['deliveryTime'])->count();
			$i_count=Db::name('hd_yujing')->where('installDate',$data['installDate'])->count();
			if ($d_count >= 25) {
				return array('status' => 6,'msg' => '预约送货人数已满，请换个日期预约',);
			}
			if ($i_count >= 25) {
				return array('status' => 6,'msg' => '预约安装人数已满，请换个日期预约',);
			}

			$data['add_time']=time();
			Db::name('order')->where(['mobile'=>$data['tel'],'plate'=>'礼至礼品','items_source'=>'恒大御景半岛'])->update(['add_time'=>$data['add_time'],'pay_time'=>$data['add_time'],'confirm_time'=>$data['installDate'],'shipping_time'=>$data['deliveryTime']]);
			Db::name('users')->where('mobile',$data['tel'])->update(['activate'=>1,'plate'=>'礼至礼品','items_source'=>'恒大御景半岛']);
            $save = Db::name('hd_yujing')->where('tel',$data['tel'])->update($data);
			if ($save) {
            	return array('status' => 2,'msg' => '恭喜您，已预约成功',);
			}
		}
        return $this->fetch();
	}
}