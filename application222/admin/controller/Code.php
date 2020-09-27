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


class Code extends Base {
    
	
    /*
     * 礼品卡类型列表
     */
    public function index(){
        //获取礼品卡列表
        
    	$count =  Db::name('code')->where('type!=5')->count();
    	$Page = new Page($count,10);
        $show = $Page->show();
        $lists = Db::name('code')->where('type!=5')->order('add_time desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        // dump($lists);die;
        $this->assign('lists',$lists);
        $this->assign('pager',$Page);// 赋值分页输出
        $this->assign('page',$show);// 赋值分页输出   
        $this->assign('codes',C('COUPON_TYPE'));
        return $this->fetch();
    }

    /*
     * 添加编辑一个礼品卡类型
     */
    public function code_info(){
        $cid = I('get.id/d');
        if($cid){
            $code = Db::name('code')->where(array('id'=>$cid))->find();
            if ($code['supplier_id']) {
                $prom_supplier = Db::name('supplier')->where("supplier_id in ($code[supplier_id])")->select();
                $this->assign('prom_supplier',$prom_supplier);
            }
            if ($code['goods_id']) {
                $prom_goods = Db::name('goods')->field('goods_id,goods_name,shop_price,store_count')->where("goods_id in ($code[goods_id])")->select();
                $this->assign('prom_goods',$prom_goods);
            }
            $this->assign('code',$code);
        }else{
            // $def['send_start_time'] = strtotime("+1 day");
            // $def['send_end_time'] = strtotime("+1 month");
            $def['use_start_time'] = strtotime("+1 day");
            $def['use_end_time'] = strtotime("+2 month");
            $this->assign('code',$def);
        }   
        if(IS_POST){
        	$data = I('post.');
            // $data['send_start_time'] = strtotime($data['send_start_time']);
            // $data['send_end_time'] = strtotime($data['send_end_time']);
            $data['use_end_time'] = strtotime($data['use_end_time']);
            $data['use_start_time'] = strtotime($data['use_start_time']);
            if ($data['goods_id']) {
                $data['goods_id'] = implode(',', $data['goods_id']);
            }
            if ($data['supplier_id']) {
                $data['supplier_id'] = implode(',', $data['supplier_id']);
            }
            if(empty($data['id'])){
                $data['add_time'] = time();
                $row = Db::name('code')->insert($data);
                //礼品卡提交后自动生成规定数量的线下礼品卡编号及秘钥
                if (I('post.type')==4) {
                    $createnum=I('post.createnum');
                    $data=array();
                    $id=Db::name('code')->order('id','desc')->find();
                    for ($i=0; $i <$createnum ; $i++) { 
                        $data[$i]['cid']=$id['id'];
                        $data[$i]['type']=$id['type'];
                        $data[$i]['number']="ylt".$id['id'].date('Ym',time()).str_pad($i+1,4,0,STR_PAD_LEFT);
                        $code = "y".substr(md5("ax".rand(time(),$i)),0,7);//获取随机8位字符串
                        $a = Db::name('code_list')->where(array('code'=>$code))->find();
                        if (empty($a)) {
                            $data[$i]['code'] = $code;
                        }
                        Db::name('code_list')->insert($data[$i]);
                    }
                }
            }else{
                $createnums = Db::name('code_list')->where('cid',$data['id'])->count('id');
                $createnum=$data['createnum'];
                //发放数量增加时增加优惠秘钥的生成
                if ($createnum >= $createnums) {
                    $createnum = $createnum - $createnums;
                }else if($createnum < $createnums){
                    $this->ajaxReturn(['status' => -11, 'msg' => '发放数量不可减少', 'result' => '']);
                }
                $id=Db::name('code')->where('id',$data['id'])->find();
                $datas=array();
                for ($i=0; $i <$createnum ; $i++) { 
                    $datas[$i]['cid']=$id['id'];
                    $datas[$i]['type']=$id['type'];
                    $datas[$i]['number']="ylt".$id['id'].date('Ym',time()).str_pad($createnums+$i+1,4,0,STR_PAD_LEFT);
                    $code = "y".substr(md5("ax".rand(time(),$i)),0,7);//获取随机8位字符串
                    $a = Db::name('code_list')->where(array('code'=>$code))->find();
                    if (empty($a)) {
                        $datas[$i]['code'] = $code;
                    }
                    Db::name('code_list')->insert($datas[$i]);
                }
                $row=Db::name('code')->where(array('id'=>$data['id']))->update($data);
            }
            if($row !== false){
                $this->ajaxReturn(['status' => 1, 'msg' => '编辑礼品卡类型成功', 'result' => '']);
            }else{
                $this->ajaxReturn(['status' => 0, 'msg' => '编辑礼品卡类型失败', 'result' => '']);
            }
            
            
        }  
        return $this->fetch();
    }
    /*
    *添加商品
     */
    public function search_goods()
    {
        $goods_id = I('goods_id');
        $where = ' is_on_sale = 1 and store_count>0 and examine = 1';//搜索条件
        if (!empty($goods_id)) {
            $where .= " and goods_id not in ($goods_id) ";
        }
        I('intro') && $where = "$where and " . I('intro') . " = 1";
        if (!empty($_REQUEST['keywords'])) {
            $this->assign('keywords', I('keywords'));
            $where = "$where and (goods_name like '%" . I('keywords') . "%' or keywords like '%" . I('keywords') . "%')";
        }
        $count = Db::name('goods')->where($where)->where('supplier_name','一礼通自营')->count();
        $Page = new Page($count, 10);
        $goodsList = Db::name('goods')->where($where)->where('supplier_name','一礼通自营')->order('goods_id DESC')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $show = $Page->show();//分页显示输出
        $this->assign('page', $show);//赋值分页输出
        $this->assign('goodsList', $goodsList);
        $this->assign('pager', $Page);//赋值分页输出
        $tpl = I('get.tpl', 'search_goods');

        return $this->fetch($tpl);
    }

    /*
    *添加/选择店铺
     */
    public function search_supplier()
    {
        $supplier_id = I('supplier_id');
        $where = ' is_designer = 0 and status = 1';//搜索条件
        if (!empty($supplier_id)) {
            $where .= " and supplier_id not in ($supplier_id) ";
        }
        if (!empty($_REQUEST['keywords'])) {
            $this->assign('keywords', I('keywords'));
            $where = "$where and (supplier_name like '%" . I('keywords') . "%')";
        }
        $count = Db::name('supplier')->where($where)->count();
        $Page = new Page($count, 10);
        $supplierList = Db::name('supplier')->where($where)->order('supplier_id DESC')->field('supplier_name,company_name,coupon_su,supplier_id')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $show = $Page->show();//分页显示输出
        $this->assign('page', $show);//赋值分页输出
        $this->assign('supplierList', $supplierList);
        $this->assign('pager', $Page);//赋值分页输出
        $tpl = I('get.tpl', 'search_supplier');
        return $this->fetch($tpl);
    }
    /*
    *选择店铺后添加商品
     */
    public function search_goods_Two()
    {   
        $GoodsLogic = new GoodsLogic;
        $goods_id = I('goods_id');
        $supplier_id = I('id');
        $where = ' is_on_sale = 1 and store_count>0 and examine = 1 and supplier_id ='.$supplier_id;//搜索条件
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
        if (!empty($_REQUEST['keywords_ss'])) {
            $this->assign('keywords_ss', I('keywords_ss'));
            $where = "$where and (supplier_name like '%" . I('keywords_ss') . "%' or keywords like '%" . I('keywords_ss') . "%')";
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
    
    // /*
    // * 礼品卡发放
    // */
    // public function make_code(){
    //     //获取礼品卡ID
    //     $cid = I('get.id/d');
    //     $type = I('get.type');
    //     //查询是否存在礼品卡
    //     $data = Db::name('code')->where(array('id'=>$cid))->find();
    //     $remain = $data['createnum'] - $data['send_num'];//剩余派发量
    // 	if($remain<=0) $this->error($data['name'].'已经发放完了');
    //     if(!$data) $this->error("礼品卡类型不存在");
    //     if($type != 3) $this->error("该礼品卡类型不支持发放");
    //     if(IS_POST){
    //         $num  = I('post.num/d');
    //         if($num<$remain) $this->error($data['name'].'发放量不够了');
    //         if(!$num > 0) $this->error("发放数量不能小于0");
    //         $add['cid'] = $cid;
    //         $add['type'] = $type;
    //         $add['send_time'] = time();
    //         for($i=0;$i<$num-$data['send_num']; $i++){
    //             do{
    //                 $code = get_rand_str(8,0,1);//获取随机8位字符串
    //                 $check_exist = Db::name('code_list')->where(array('code'=>$code))->find();
    //             }while($check_exist);
    //             $add['code'] = $code;
    //             Db::name('code_list')->insert($add);
    //         }
    //         Db::name('code')->where("id",$cid)->setInc('send_num',$num-$data['send_num']);
    //         adminLog("发放".$num.'张'.$data['name']);
    //         $this->success("发放成功",U('Admin/code/index'));
    //         exit;
    //     }
    //     $this->assign('code',$data);
    //     return $this->fetch();
    // }
    
    // public function ajax_get_user(){
    // 	//搜索条件
    // 	$condition = array();
    // 	I('mobile') ? $condition['mobile'] = I('mobile') : false;
    // 	I('email') ? $condition['email'] = I('email') : false;
    // 	$nickname = I('nickname');
    // 	if(!empty($nickname)){
    // 		$condition['nickname'] = array('like',"%$nickname%");
    // 	}
    // 	$model = Db::name('users');
    // 	$count = $model->where($condition)->count();
    // 	$Page  = new AjaxPage($count,10);
    // 	$show = $Page->show();
    // 	$userList = $model->where($condition)->order("user_id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
    //     $user_level = Db::name('user_level')->getField('level_id,level_name',true);       
    //     $this->assign('user_level',$user_level);
    // 	$this->assign('userList',$userList);
    // 	$this->assign('page',$show);
    //     $this->assign('pager',$Page);
    // 	return $this->fetch();
    // }
   //  public function send_code(){
   //  	$cid = I('cid/d');
   //  	if(IS_POST){
   //  		$level_id = I('level_id');
   //  		$user_id = I('user_id/a');
   //  		$insert = '';
   //  		$code = Db::name('code')->where("id",$cid)->find();
   //  		if($code['createnum']>0){
   //  			$remain = $code['createnum'] - $code['send_num'];//剩余派发量
   //  			if($remain<=0) $this->error($code['name'].'已经发放完了');
   //  		}
   //  		if(empty($user_id) && $level_id>=0){
   //  			if($level_id==0){
   //  				$user = Db::name('users')->where("is_lock",0)->select();
   //  			}else{
   //  				$user = Db::name('users')->where("is_lock",0)->where('level', $level_id)->select();
   //  			}
   //  			if($user){
   //  				$able = count($user);//本次发送量
   //  				if($code['createnum']>0 && $remain<$able){
   //  					$this->error($code['name'].'派发量只剩'.$remain.'张');
   //  				}
   //  				foreach ($user as $k=>$val){
   //  					$time = time();
   //                      $insert[] = ['cid' => $cid, 'type' => 1, 'uid' => $val['user_id'], 'send_time' => $time];
   //  				}
   //  			}
   //  		}else{
   //  			$able = count($user_id);//本次发送量
   //  			if($code['createnum']>0 && $remain<$able){
   //  				$this->error($code['name'].'派发量只剩'.$remain.'张');
   //  			}
   //  			foreach ($user_id as $k=>$v){
   //  				$time = time();
   //                  $insert[] = ['cid' => $cid, 'type' => 1, 'uid' => $v, 'send_time' => $time];
   //  			}
   //  		}
			// DB::name('code_list')->insertAll($insert);
			// Db::name('code')->where("id",$cid)->setInc('send_num',$able);
			// adminLog("发放".$able.'张'.$code['name']);
			// $this->success("发放成功");
			// exit;
   //  	}
   //  	$level = Db::name('user_level')->select();
   //  	$this->assign('level',$level);
   //  	$this->assign('cid',$cid);
   //  	return $this->fetch();
   //  }
    

    /*
     * 删除礼品卡类型
     */
    public function del_code(){
        //获取礼品卡ID
        $cid = I('get.id/d');
        //查询是否存在礼品卡
        $row = Db::name('code')->where(array('id'=>$cid))->delete();
        if($row){
            //删除此类型下的礼品卡
            Db::name('code_list')->where(array('cid'=>$cid))->delete();
            $this->success("删除成功");
        }else{
            $this->error("删除失败");
        }
    }


    /*
     * 礼品卡详细查看
     */
    public function code_list(){
        //获取礼品卡ID
        $cid = I('get.id/d');
        //查询是否存在礼品卡
        $check_code = Db::name('code')->field('id,type')->where(array('id'=>$cid))->find();
        if(!$check_code['id'] > 0)
            $this->error('不存在该类型礼品卡');
       
        //查询该礼品卡的列表的数量
        $sql = "SELECT count(1) as c FROM __PREFIX__code_list  l ".
                "LEFT JOIN __PREFIX__code c ON c.id = l.cid ". //联合礼品卡表查询名称
                "LEFT JOIN __PREFIX__order o ON o.order_id = l.order_id ".     //联合订单表查询订单编号
                "LEFT JOIN __PREFIX__users u ON u.user_id = l.uid WHERE l.cid = :cid";    //联合用户表去查询用户名
        
        $count = DB::query($sql,['cid' => $cid]);
        $count = $count[0]['c'];
    	$Page = new Page($count,10);
    	$show = $Page->show();
        
        //查询该礼品卡的列表
        $sql = "SELECT l.*,c.name,o.order_sn,u.nickname FROM __PREFIX__code_list  l ".
                "LEFT JOIN __PREFIX__code c ON c.id = l.cid ". //联合礼品卡表查询名称
                "LEFT JOIN __PREFIX__order o ON o.order_id = l.order_id ".     //联合订单表查询订单编号
                // "LEFT JOIN __PREFIX__users u ON u.user_id = l.uid WHERE l.cid = :cid and l.uid != 0 ".    //添加条件只查看使用过的卡号内容
                "LEFT JOIN __PREFIX__users u ON u.user_id = l.uid WHERE l.cid = :cid ".    //联合用户表去查询用户名
                " limit {$Page->firstRow} , {$Page->listRows}";
        $code_list = DB::query($sql,['cid' => $cid]);
        // dump($code_list);die;
        $this->assign('code_type',C('COUPON_TYPE'));
        $this->assign('type',$check_code['type']);       
        $this->assign('lists',$code_list);            	
    	$this->assign('page',$show);
        $this->assign('pager',$Page);
        return $this->fetch();
    }
    
    /*
     * 删除一张礼品卡
     */
    public function code_list_del(){
        //获取礼品卡ID
        $cid = I('get.id');
        if(!$cid)
            $this->error("缺少参数值");
        //查询是否存在礼品卡
         $row = M('code_list')->where(array('id'=>$cid))->delete();
        if(!$row)
            $this->error('删除失败');
        $this->success('删除成功');
    }


    /**
     * [export_code 礼品卡详情列表导出表格]
     * @return [type] [description]
     */
    public function export_code(){
        $p = I('p/d',1);
        $where=$_GET['id'];
        $sql = "SELECT l.*,c.name,o.order_sn,u.nickname FROM ylt_code_list  l ".
                "LEFT JOIN ylt_code c ON c.id = l.cid ". //联合礼品卡表查询名称
                "LEFT JOIN ylt_order o ON o.order_id = l.order_id ".     //联合订单表查询订单编号
                "LEFT JOIN ylt_users u ON u.user_id = l.uid WHERE l.cid = $where";  //联合用户表去查询用户名
        // echo $sql;die;
        if($_GET['id']){
            // echo 123;die;
        $orderList = DB::query($sql);
        $strTable ='<table width="500" border="1">';
        $strTable .= '<tr>';
        $strTable .= '<td style="text-align:center;font-size:18px;width:200px;">礼品卡名称</td>';
        $strTable .= '<td style="text-align:center;font-size:18px;" width="120">礼品卡编码</td>';
        $strTable .= '<td style="text-align:center;font-size:18px;" width="150">礼品卡秘钥</td>';
        $strTable .= '</tr>';
        if(is_array($orderList)){
            foreach($orderList as $k=>$val){
                $strTable .= '<tr>';
                $strTable .= '<td style="text-align:center;font-size:18px;">&nbsp;'.$val['name'].'</td>';
                $strTable .= '<td style="text-align:center;font-size:18px;">'.$val['number'].' </td>';
                $strTable .= '<td style="text-align:center;font-size:18px;">'.$val['code'].'</td>';
                $strTable .= '</tr>';
            }
        }
        $strTable .='</table>';
        unset($orderList);
        downloadExcel($strTable,'code_list');
        exit();
        }
    }

}