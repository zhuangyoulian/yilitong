<?php
namespace ylt\admin\controller;
use think\Page;
use think\AjaxPage;
use think\Verify;
use think\Db;
use think\Url;
use think\Request;

class Sales extends Base{
	public  $order_type;
    /*
      * 初始化操作
      */
    public function _initialize() {
        parent::_initialize();
        config('TOKEN_ON',false); // 关闭表单令牌验证
        $this->order_type = config('ORDER_TYPE');
        $this->finishs = config('FINISHS');
        // 订单状态
        $this->assign('finishs',$this->finishs);
        $this->assign('order_type',$this->order_type);
    }

	public function shopkeeper(){
    	return $this->fetch();
	}

	/**
	 * 店主管理
	 */
	public function ajaxShopkeeper(){
        $begin = strtotime(input('add_time_begin'));
        $end = strtotime(input('add_time_end')); 
		//搜索条件
        $condition = array();
        I('phone') ? $condition['phone'] = I('phone') : false;
        $where = array();
        if (I('phone')) {
            I('phone') && $where = " phone like '%".I('phone')."%' or shop_name like '%".I('phone')."%' ";
            // I('phone') ? $where['phone'] = array('like','%'.I('phone').'%') : false;
        }

		if($begin && $end){
            $condition['add_time'] = array('between',"$begin,$end");
            $where['add_time'] = array('between',"$begin,$end");
        }
        $sort_order = I('order_by','id').' '.I('sort','desc');

        $model = Db::name('distribution');
        $count = $model->where($where)->count();
        $Page  = new AjaxPage($count,15);
        //  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            if($key == 'add_time'){
                $between_time = explode(',',$val[1]);
                $parameter_add_time = date('Y/m/d',$between_time[0]) . '-' . date('Y/m/d',$between_time[1]);
                $Page->parameter['timegap'] = $parameter_add_time;
            }else{
                $Page->parameter[$key]   =  urlencode($val);
            }
        }
        $data = $model->where($where)->order($sort_order)->limit($Page->firstRow.','.$Page->listRows)->select();
        foreach ($data as $key => $value) {   //查询推荐店主的店铺名称
           $value['r_name'] = $model->where('id',$value['r_id'])->value('shop_name');
           $ress[] = $value;
        }

        $show = $Page->show();
        $this->assign('userList',$ress);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
    }

    /**
     * [manager_detail 店主信息查看]
     * @return [type] [description]
     */
    public function manager_detail(){
    	$id=$_GET['id'];
    	$su=Db::name('distribution')->where('id',$id)->find();
    	$ss=Db::name('distribution')->where('u_id',$su['r_id'])->field('id')->find();
    	$sd=Db::name('distribution')->where('r_id',$su['u_id'])->field('id')->select();
    	foreach ($sd as $key => $value) {
    		$sd[$key]=$value['id'];
    	}
    	$dd=implode(',',$sd);
        $this->assign('ss',$ss);
        $this->assign('dd',$dd);
        $this->assign('su',$su);
        return $this->fetch();
    }

    /**
     * [orders 订单列表]
     * @return [type] [description]
     */
    public function orders(){
        return $this->fetch();
    }
    public function ajaxOrders(){
    	// $orderLogic = new OrderLogic();
        $begin = strtotime(input('add_time_begin'));
        $end = strtotime(input('add_time_end')); 
        
        // 搜索条件
        $condition = array();
        $keyType = 'order_id';
        $keywords = I('keywords','','trim');

		$keywords =  $keywords ? $keywords : false;
        $keywords ? $condition[''.$keyType.''] = trim($keywords) : false;


        if($begin && $end){
            $condition['add_time'] = array('between',"$begin,$end");
        }

        input('order_type') != '' ? $condition['order_type'] = input('order_type') : false;
        input('user_id') ? $condition['user_id'] = trim(input('user_id')) : false;

        $sort_order = I('order_by','id').' '.I('sort','desc');
        // $sort_order = I('order_by','DESC').' '.I('sort');
        $count = Db::name('order_distribution')->where($condition)->count();
        $Page  = new AjaxPage($count,20);
        //  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            if($key == 'add_time'){
                $between_time = explode(',',$val[1]);
                $parameter_add_time = date('Y/m/d',$between_time[0]) . '-' . date('Y/m/d',$between_time[1]);
                $Page->parameter['timegap'] = $parameter_add_time;
            }else{
                $Page->parameter[$key]   =  urlencode($val);
            }
        }
        $show = $Page->show();

        //获取订单列表
        $res = Db::name('order_distribution')->alias('o')->join('users u','o.u_id=u.user_id')->where($condition)->field('o.*,u.nickname')->limit($Page->firstRow,$Page->listRows)->order($sort_order)->select();
        foreach ($res as $key => $value) {   //查询推荐人的名称
           $value['r_name'] = Db::name('users')->where('user_id',$value['r_id'])->value('nickname');
          $ress[] = $value;
        }
        $this->assign('orderList',$ress);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
    }

    /**
	 * 分销订单导出数据
	 */
	 public function export_order()
    {
    	//搜索条件
		//dump(input('POST.'));
		$where = 'where 1 = 1 ';
		$id =  input('id');
		if($id){
			$where .= " AND id = '$id' ";
		}
        if(input('order_type')){
            $where .= " AND order_type = ".input('order_type');
        }
		if(input('add_time_begin')){
			$where .= " AND payment_time > ". strtotime(input('add_time_begin'));
		}
		if(input('add_time_end')){
			$where .= " AND payment_time < ". strtotime(input('add_time_end'));
		}
  
		$sql = "select *,FROM_UNIXTIME(payment_time,'%Y-%m-%d') as payment_time from __PREFIX__order_distribution  $where order by id"; //echo $sql;exit;
    	$orderList = DB::query($sql);
    	$strTable ='<table width="500" border="1">';
    	$strTable .= '<tr>';
    	$strTable .= '<td style="text-align:center;font-size:16px;" width:"120px;">分销订单id</td>';
    	$strTable .= '<td style="text-align:center;font-size:16px;" width="100">一礼通订单id</td>';
    	$strTable .= '<td style="text-align:center;font-size:16px;" width="*">用户ID</td>';
    	$strTable .= '<td style="text-align:center;font-size:16px;" width="*">推荐人ID</td>';
    	$strTable .= '<td style="text-align:center;font-size:16px;" width="*">订单状态</td>';
    	$strTable .= '<td style="text-align:center;font-size:16px;" width="*">订单总额</td>';
    	$strTable .= '<td style="text-align:center;font-size:16px;" width="*">订单佣金</td>';
    	$strTable .= '<td style="text-align:center;font-size:16px;" width="*">支付时间</td>';
    	$strTable .= '</tr>';
	    if(is_array($orderList)){
	    	foreach($orderList as $k=>$val){
	    		$strTable .= '<tr>';
	    		$strTable .= '<td style="text-align:center;font-size:16px;">&nbsp;'.$val['id'].'</td>';
	    		$strTable .= '<td style="text-align:left;font-size:16px;">'.$val['order_id'].' </td>';
	    		$strTable .= '<td style="text-align:left;font-size:16px;">'.$val['u_id'].'</td>';
	    		$strTable .= '<td style="text-align:left;font-size:16px;">'.$val['r_id'].'</td>';
	    		$strTable .= '<td style="text-align:left;font-size:16px;">'.$this->order_type[$val['order_type']].'</td>';
                $strTable .= '<td style="text-align:left;font-size:16px;">'.$val['order_money'].' </td>';
                $strTable .= '<td style="text-align:left;font-size:16px;">'.$val['rebates'].' </td>';
                $strTable .= '<td style="text-align:left;font-size:16px;">'.$val['payment_time'].' </td>';
	    		$strTable .= '</tr>';
	    	}
	    }
    	$strTable .='</table>';
    	unset($orderList);
    	downloadExcel($strTable,'order');
    	exit();
    }

    /**
     * 订单删除
     * @param int $id 订单id
     */
    public function delete_order($id){
        // dump($id);die;
        $del = Db::name('order_distribution')->where('id',$id)->delete();
        if($del){
            $this->success('删除订单成功');
        }else{
        	$this->error('订单删除失败');
        }
    }

    /**
     * [withdraw 提现列表]
     * @return [type] [description]
     */
    public function withdraw(){
    	return $this->fetch();
    }
    public function ajaxwithdraw(){
    	// 搜索条件
        $condition = array();
        $keyType = 'alipay';
        $keywords = I('keywords','','trim');

		$keywords =  $keywords ? $keywords : false;
        $keywords ? $condition[''.$keyType.''] = trim($keywords) : false;

        input('finishs') != '' ? $condition['finish'] = input('finishs') : false;

        $sort_order = input('order_by','d_id').' '.input('sort','desc');
        $model = Db::name('deposit');
        $count = $model->where($condition)->count();
        $Page  = new AjaxPage($count,15);
        //  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            $Page->parameter[$key]   =   urlencode($val);
        }
        $dividedList = $model->where($condition)->order($sort_order)->limit($Page->firstRow.','.$Page->listRows)->select();

        $show = $Page->show();
        $this->assign('withdraw',$dividedList);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
    }
    /**
     * [finish 提现完成按钮]
     * @return [type] [description]
     */
    public function finish(){
    	$finish=Db::name('deposit')->where('id',I('post.id'))->update(['finish'=>0]);
    	if ($finish) {
    		return array('status' => 1,'msg' => '操作成功！');
    	}else{
    		return array('status' => 0,'msg' => '操作失败！');
    	}
    }

    /**
	 * 分销提现列表导出数据
	 */
	 public function export_withdraw()
    {
    	//搜索条件
		//dump(input('POST.'));
		$where = 'where 1 = 1 ';
		$id =  input('id');
		if($id){
			$where .= " AND id = '$id' ";
		}
		if(input('d_id')){
            $where .= " AND d_id = ".input('d_id');
		}
        if(input('money')){
            $where .= " AND money = ".input('money');
        }
		if(input('into_time')){
			$where .= " AND into_time > ". strtotime(input('into_time'));
		}
        if(input('finishs')){
            $where .= " AND finish = ".input('finishs');
        }
  
		$sql = "select *,FROM_UNIXTIME(into_time,'%Y-%m-%d') as into_time from __PREFIX__deposit  $where order by id"; //echo $sql;exit;
    	$withdrawList = DB::query($sql);
        // dump($withdrawList);die;
    	$strTable ='<table width="500" border="1">';
    	$strTable .= '<tr>';
    	$strTable .= '<td style="text-align:center;font-size:16px;" width:"120px;">id</td>';
        $strTable .= '<td style="text-align:center;font-size:16px;" width="100">店铺ID</td>';
    	$strTable .= '<td style="text-align:center;font-size:16px;" width="100">店铺名称</td>';
    	$strTable .= '<td style="text-align:center;font-size:16px;" width="*">提现金额</td>';
    	$strTable .= '<td style="text-align:center;font-size:16px;" width="*">支付宝账号</td>';
    	$strTable .= '<td style="text-align:center;font-size:16px;" width="*">申请时间</td>';
    	$strTable .= '<td style="text-align:center;font-size:16px;" width="*">提现状态</td>';
    	$strTable .= '</tr>';
	    if(is_array($withdrawList)){
	    	foreach($withdrawList as $k=>$val){
	    		$strTable .= '<tr>';
	    		$strTable .= '<td style="text-align:center;font-size:16px;">&nbsp;'.$val['id'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:16px;">'.$val['d_id'].' </td>';
	    		$strTable .= '<td style="text-align:left;font-size:16px;">'.$val['shop_name'].' </td>';
	    		$strTable .= '<td style="text-align:left;font-size:16px;">'.$val['money'].'</td>';
	    		$strTable .= '<td style="text-align:left;font-size:16px;">'.$val['alipay'].'</td>';
	    		$strTable .= '<td style="text-align:left;font-size:16px;">'.$val['into_time'].'</td>';
	    		$strTable .= '<td style="text-align:left;font-size:16px;">'.$this->finishs[$val['finish']].'</td>';
	    		$strTable .= '</tr>';
	    	}
	    }
    	$strTable .='</table>';
    	unset($withdrawList);
    	downloadExcel($strTable,'order');
    	exit();
    }


    /**
     * [bonus_list 奖励金发放列表]
     * @return [type] [description]
     */
    public function bonus_list(){
        return $this->fetch();
    }
    public function ajaxbonus(){
        $begin = strtotime(input('add_time_begin'));
        $end = strtotime(input('add_time_end')); 

        // 搜索条件
        $condition = array();
        I('keywords') ? $condition['shop_name'] = I('keywords') : false;
        
        $where = array();
        I('keywords') ? $where['shop_name'] = array('like','%'.I('keywords').'%') : false;

        if($begin && $end){
            $condition['time'] = array('between',"$begin,$end");
            $where['time'] = array('between',"$begin,$end");
        }
        
        $sort_order = input('order_by','id').' '.input('sort','desc');
        $model = Db::name('distribution_bonus');
        $count = $model->where($where)->count();
        $Page  = new AjaxPage($count,15);
        //  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            if($key == 'time'){
                $between_time = explode(',',$val[1]);
                $parameter_add_time = date('Y/m/d',$between_time[0]) . '-' . date('Y/m/d',$between_time[1]);
                $Page->parameter['timegap'] = $parameter_add_time;
            }else{
                $Page->parameter[$key]   =  urlencode($val);
            }
        }
        $bonus_list = $model->where($where)->order($sort_order)->limit($Page->firstRow.','.$Page->listRows)->select();

        $show = $Page->show();
        $this->assign('bonus_list',$bonus_list);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
    }

    /**
     * [set 佣金设置]
     */
    public function set(){
    	$info=Db::name('distribution_id')->where('id',1)->find();
        //查询已完成的订单收益
        $earnings=Db::name('order_distribution')->where('order_type = 1')->field('rebates,u_id')->select();
        foreach ($earnings as $key => $value) {
            $a = Db::name('distribution')->where('u_id',$value['u_id'])->find();
            if ($a['id'] == 1) {
                $info['rebates'] = $info['bonus']+$info['top_ratio'];  
                $bonuss = $value['rebates']*$info['rebates'];          //原始第一级佣金30%给平台奖金池
            }else{
                $bonuss = $value['rebates']*$info['bonus'];            //普通订单佣金10%给平台奖金池
            }
            $bonus  += $bonuss;             //按照已完成订单计算出的平台奖金池
        }
        $bonus=$bonus-$info['closed'];      //减去已执行发放的奖金
        $bonus=$bonus?$bonus:0;

        $this->assign('bonuss',$bonus);
        $this->assign('info',$info);
        if (IS_POST) {
        	$data=I('');
            if ($data['bonus'] + $data['ratio'] + $data['top_ratio'] > 1) {
                $this->error("修改失败，分成总比不可大于1",Url::build('Admin/sales/set'));
            }
        	$r=Db::name('distribution_id')->where('id',1)->update($data);
			if($r){
                $info=Db::name('distribution_id')->where('id',1)->find();
                $next_time=strtotime("+".$info['stockdater']." months",$info['this_time']);
                if ($next_time < time()) {
                    $this->error("修改失败，下次开始时间不可早于当前时间",Url::build('Admin/sales/set'));
                }
                Db::name('distribution_id')->where('id',1)->update(['next_time'=>$next_time]);
	    		$this->success("操作成功",Url::build('Admin/sales/set'));
	    	}else{
	    		$this->error("修改失败，内容无变化",Url::build('Admin/sales/set'));
	    	}
        }
    	return $this->fetch();
    }
}