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


class Coupon extends Base {
    
    
    /*
     * 优惠券类型列表
     */
    public function index(){
        //获取优惠券列表
        $count =  Db::name('coupon')->count();
        $Page = new Page($count,10);
        $show = $Page->show();
        $lists_ = Db::name('coupon')->order('add_time desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        foreach ($lists_ as $key => $value) {
            $value['supplier_name']=DB::name('coupon')->alias('c')->join('supplier s','s.supplier_id=c.supplier_id')->where('c.supplier_id',$value['supplier_id'])->field('s.supplier_name')->find();
            $value['prom_type']=4;
            $value['goods_id']=explode(',',$value['goods_id']);
            $lists[]=$value;
        }
        $this->assign('lists',$lists);
        $this->assign('pager',$Page);// 赋值分页输出
        $this->assign('page',$show);// 赋值分页输出   
        $this->assign('coupons',C('COUPON_TYPE'));
        return $this->fetch();
    }

    /*
     * 添加编辑一个优惠券类型
     */
    public function coupon_info(){
        $cid = I('get.id/d');
        if($cid){
            $coupon = Db::name('coupon')->where(array('id'=>$cid))->find();
            if ($coupon['supplier_id']) {
                $prom_supplier = Db::name('supplier')->where("supplier_id in ($coupon[supplier_id])")->select();
                $this->assign('prom_supplier',$prom_supplier);
            }
            if ($coupon['goods_id']) {
                $prom_goods = Db::name('goods')->field('goods_id,goods_name,shop_price,store_count')->where("goods_id in ($coupon[goods_id])")->select();
                $this->assign('prom_goods',$prom_goods);
            }
            $this->assign('coupon',$coupon);
        }else{
            $def['send_start_time'] = strtotime("+1 day");
            $def['send_end_time'] = strtotime("+1 month");
            $def['use_start_time'] = strtotime("+1 day");
            $def['use_end_time'] = strtotime("+2 month");
            $this->assign('coupon',$def);
        }     

        if(IS_POST){
            $data = I('post.');
            $where=$data['goods_id'];
            if ($data['coupon_type']==0) {
                $data['supplier_id']=41;
            }
            if (count($data['goods_id'])>1) {
                $data['coupon_type']=1;
            }elseif(count($data['goods_id'])==1) {
                $data['coupon_type']=0;
            }
            $data['send_start_time'] = strtotime($data['send_start_time']);
            $data['send_end_time'] = strtotime($data['send_end_time']);
            $data['use_end_time'] = strtotime($data['use_end_time']);
            $data['use_start_time'] = strtotime($data['use_start_time']);
            $data['createnum'] = $data['createnumssss']-$data['shenum'];

            if ($data['goods_id']) {
                $data['goods_id'] = implode(',', $data['goods_id']);
                $goods['prom_type']=4;
            }
            if (is_array($data['supplier_id'])) {
                $data['supplier_id'] = implode(',', $data['supplier_id']);
            }
            if ($data['renewal']==1) {
                $data['renewaltime']=$data['renewaltime'];
            }else if($data['renewal']==0){
                $data['renewaltime']=0;
            }

            $couponValidate = \think\Loader::validate('Coupon');

            if (!$couponValidate->batch()->check($data)) {
                $this->ajaxReturn(['status' => 0, 'msg' => '操作失败', 'result' => $couponValidate->getError()]);
            }
            if(empty($data['id'])){
                $data['add_time'] = time();
                $goods['prom_id'] = Db::name('coupon')->insertgetId($data);
            }else{
                $createnums = Db::name('coupon_list')->where('cid',$data['id'])->count();
                if($createnums > $data['createnum']){
                    $this->ajaxReturn(['status' => -11, 'msg' => '发放数量不可减少', 'result' => '']);
                }
                $row = Db::name('coupon')->where(array('id'=>$data['id']))->update($data);
                $goods['prom_id']=$data['id'];
                //替换/删除字段内的某个字符串
                Db::query("update ylt_goods set prom_type =concat(SUBSTRING(prom_type ,1,position(',4' in prom_type )-1),'',substring(prom_type ,position(',4' in prom_type )+length(',4'))) where prom_id like '%,$cid%'");
                Db::query("update ylt_goods set prom_id =concat(SUBSTRING(prom_id ,1,position(',$cid' in prom_id )-1),'',substring(prom_id ,position(',$cid' in prom_id )+length(',$cid'))) where prom_id like '%,$cid%'");
                Db::query("update ylt_supplier set coupon_su =concat(SUBSTRING(coupon_su ,1,position(',$$goods[prom_id]' in coupon_su )-1),'',substring(coupon_su ,position(',$goods[prom_id]' in coupon_su )+length(',$goods[prom_id]'))) where coupon_su like '%,$goods[prom_id]%'");
            }
            if($row !== false){
                if ($data['goods_id']) {
                    for ($i=0; $i < count($where); $i++) { 
                        //CONCAT 方法在字段内的后方加入字符串
                    Db::query("update ylt_goods  set prom_id=CONCAT(prom_id,',$goods[prom_id]') where goods_id='$where[$i]'");
                    Db::query("update ylt_goods  set prom_type=CONCAT(prom_type,',$goods[prom_type]') where goods_id='$where[$i]'");
                    }
                }
                Db::query("update ylt_supplier set coupon_su=CONCAT(coupon_su,',$goods[prom_id]') where supplier_id='$data[supplier_id]'");
                $this->ajaxReturn(['status' => 1, 'msg' => '编辑代金券成功', 'result' => '']);
            }else{
                $this->ajaxReturn(['status' => 0, 'msg' => '编辑代金券失败', 'result' => '']);
            }
        }
        
        return $this->fetch();
    }
    /*
    *添加一礼通自营商品
     */
    public function search_goods()
    {
        // $GoodsLogic = new GoodsLogic;
        // $brandList = $GoodsLogic->getSortBrands();
        // $this->assign('brandList', $brandList);
        // $categoryList = $GoodsLogic->getSortCategory();
        // $this->assign('categoryList', $categoryList);
        $goods_id = I('goods_id');
        $where = ' is_on_sale = 1 and store_count>0 and examine = 1 and supplier_id = 41';//搜索条件
        if (!empty($goods_id)) {
            $where .= " and goods_id not in ($goods_id) ";
        }
        I('intro') && $where = "$where and " . I('intro') . " = 1";
        // if (I('cat_id')) {
        //     $this->assign('cat_id', I('cat_id'));
        //     $grandson_ids = getCatGrandson(I('cat_id'));
        //     $where = " $where  and cat_id in(" . implode(',', $grandson_ids) . ") "; // 初始化搜索条件
        // }
        // if (I('brand_id')) {
        //     $this->assign('brand_id', I('brand_id'));
        //     $where = "$where and brand_id = " . I('brand_id');
        // }
        if (!empty($_REQUEST['keywords'])) {
            $this->assign('keywords', I('keywords'));
            $where = "$where and (goods_name like '%" . I('keywords') . "%' or keywords like '%" . I('keywords') . "%')";
        }
        if (!empty($_REQUEST['keywords_ss'])) {
            $this->assign('keywords_ss', I('keywords_ss'));
            $where = "$where and (supplier_name like '%" . I('keywords_ss') . "%' or keywords like '%" . I('keywords_ss') . "%')";
        }
        $count = Db::name('goods')->where($where)->count();
        // dump($count);die;
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

    /*
    *添加店铺
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
    * 优惠券发放
    */
    public function make_coupon(){
        //获取优惠券ID
        $cid = I('get.id/d');
        $data = Db::name('coupon')->where(array('id'=>$cid))->find();
        $type = $data['type'];
        //查询是否存在优惠券
        $remain = $data['createnum'] - $data['send_num'];//剩余派发量
        if($remain<=0) $this->error($data['name'].'已经发放完了');
        if(!$data) $this->error("优惠券类型不存在");
        if($type != 3) $this->error("该优惠券类型不支持发放");
        $num  = $data['createnum'];
        if($num<$remain) $this->error($data['name'].'发放量不够了');
        if(!$num > 0) $this->error("发放数量不能小于0");
        $add['cid'] = $cid;
        $add['type'] = $type;
        $add['send_time'] = time();
        for($i=0;$i<$num-$data['send_num']; $i++){
            do{
                $code = get_rand_str(8,0,1);//获取随机8位字符串
                $check_exist = Db::name('coupon_list')->where(array('code'=>$code))->find();
            }while($check_exist);
            $add['code'] = $code;
            Db::name('coupon_list')->insert($add);
        }
        Db::name('coupon')->where("id",$cid)->setInc('send_num',$num-$data['send_num']);
        adminLog("发放".$num.'张'.$data['name']);
        $this->success("发放成功",Url::build('Admin/Coupon/index'));
        exit;
        $this->assign('coupon',$data);
        return $this->fetch();
    }
    


    // public function ajax_get_user(){
    //     //搜索条件
    //     $condition = array();
    //     I('mobile') ? $condition['mobile'] = I('mobile') : false;
    //     I('email') ? $condition['email'] = I('email') : false;
    //     $nickname = I('nickname');
    //     if(!empty($nickname)){
    //         $condition['nickname'] = array('like',"%$nickname%");
    //     }
    //     $model = Db::name('users');
    //     $count = $model->where($condition)->count();
    //     $Page  = new AjaxPage($count,10);
    //     $show = $Page->show();
    //     $userList = $model->where($condition)->order("user_id desc")->limit($Page->firstRow.','.$Page->listRows)->select();

    //     $user_level = Db::name('user_level')->getField('level_id,level_name',true);       
    //     $this->assign('user_level',$user_level);
    //     $this->assign('userList',$userList);
    //     $this->assign('page',$show);
    //     $this->assign('pager',$Page);
    //     return $this->fetch();
    // }
    /**
     * 优惠卷--按用户发放
     * @return [type] [description]
     */
    // public function send_coupon(){
    //     $cid = I('cid/d');
    //     if(IS_POST){
    //         $level_id = I('level_id');
    //         $user_id = I('user_id/a');
    //         $insert = '';
    //         $coupon = Db::name('coupon')->where("id",$cid)->find();
    //         if($coupon['createnum']>0){
    //             $remain = $coupon['createnum'] - $coupon['send_num'];//剩余派发量
    //             if($remain<=0) $this->error($coupon['name'].'已经发放完了');
    //         }
    //         if(empty($user_id) && $level_id>=0){
    //             if($level_id==0){
    //                 $user = Db::name('users')->where("is_lock",0)->select();
    //             }else{
    //                 $user = Db::name('users')->where("is_lock",0)->where('level', $level_id)->select();
    //             }
    //             if($user){
    //                 $able = count($user);//本次发送量
    //                 if($coupon['createnum']>0 && $remain<$able){
    //                     $this->error($coupon['name'].'派发量只剩'.$remain.'张');
    //                 }
    //                 foreach ($user as $k=>$val){
    //                     $time = time();
    //                     $insert[] = ['cid' => $cid, 'type' => 1, 'uid' => $val['user_id'], 'send_time' => $time];
    //                 }
    //             }
    //         }else{
    //             $able = count($user_id);//本次发送量
    //             if($coupon['createnum']>0 && $remain<$able){
    //                 $this->error($coupon['name'].'派发量只剩'.$remain.'张');
    //             }
    //             foreach ($user_id as $k=>$v){
    //                 $time = time();
    //                 $insert[] = ['cid' => $cid, 'type' => 1, 'uid' => $v, 'send_time' => $time];
    //             }
    //         }
    //         DB::name('coupon_list')->insertAll($insert);
    //         Db::name('coupon')->where("id",$cid)->setInc('send_num',$able);
    //         adminLog("发放".$able.'张'.$coupon['name']);
    //         $this->success("发放成功");
    //         exit;
    //     }
    //     $level = Db::name('user_level')->select();
    //     $this->assign('level',$level);
    //     $this->assign('cid',$cid);
    //     return $this->fetch();
    // }
    

    /*
     * 删除优惠券类型
     */
    public function del_coupon(){
        //获取优惠券ID
        $cid = I('get.id/d');
        if(!$cid)
            $this->error("缺少参数值");
        //查询是否存在优惠券
        $row = Db::name('coupon')->where(array('id'=>$cid))->delete();
        Db::query("update ylt_goods set prom_type =concat(SUBSTRING(prom_type ,1,position(',4' in prom_type )-1),'',substring(prom_type ,position(',4' in prom_type )+length(',4'))) where prom_id like '%,$cid%'");
        Db::query("update ylt_goods set prom_id =concat(SUBSTRING(prom_id ,1,position(',$cid' in prom_id )-1),'',substring(prom_id ,position(',$cid' in prom_id )+length(',$cid'))) where prom_id like '%,$cid%'");
        Db::query("update ylt_supplier set coupon_su =concat(SUBSTRING(coupon_su ,1,position(',$cid' in coupon_su )-1),'',substring(coupon_su ,position(',$cid' in coupon_su )+length(',$cid'))) where coupon_su like '%,$cid%'");
        // Db::query("update ylt_goods set prom_id=REPLACE(prom_id,',$cid','') where prom_id = ',$cid'");
        // Db::query("update ylt_supplier set coupon_su=REPLACE(coupon_su,',$cid','') where coupon_su = ',$cid'");
        // Db::query("update ylt_goods set prom_type=REPLACE(prom_type,',$cid','') where prom_id REGEXP ',$cid'");
        if($row){
            //删除此类型下的优惠券
            Db::name('coupon_list')->where(array('cid'=>$cid))->delete();
            $this->success("删除成功");
        }else{
            $this->error("删除失败");
        }
    }



    /*
     * 优惠券详细查看
     */
    public function coupon_list(){
        //获取优惠券ID
        $cid = I('get.id/d');
        //查询是否存在优惠券
        $check_coupon = Db::name('coupon')->field('id,type')->where(array('id'=>$cid))->find();
        if(!$check_coupon['id'] > 0)
            $this->error('不存在该类型优惠券');
       
        //查询该优惠券的列表的数量
        $sql = "SELECT count(1) as c FROM __PREFIX__coupon_list  l ".
                "LEFT JOIN __PREFIX__coupon c ON c.id = l.cid ". //联合优惠券表查询名称
                "LEFT JOIN __PREFIX__order o ON o.order_id = l.order_id ".     //联合订单表查询订单编号
                "LEFT JOIN __PREFIX__users u ON u.user_id = l.uid WHERE l.cid = :cid";    //联合用户表去查询用户名
        
        $count = DB::query($sql,['cid' => $cid]);
        $count = $count[0]['c'];
        $Page = new Page($count,10);
        $show = $Page->show();
        
        //查询该优惠券的列表
        $sql = "SELECT l.*,c.name,o.order_sn,u.nickname FROM __PREFIX__coupon_list  l ".
                "LEFT JOIN __PREFIX__coupon c ON c.id = l.cid ". //联合优惠券表查询名称
                "LEFT JOIN __PREFIX__order o ON o.order_id = l.order_id ".     //联合订单表查询订单编号
                "LEFT JOIN __PREFIX__users u ON u.user_id = l.uid WHERE l.cid = :cid".    //联合用户表去查询用户名
                " limit {$Page->firstRow} , {$Page->listRows}";
        $coupon_list = DB::query($sql,['cid' => $cid]);
        $this->assign('coupon_type',C('COUPON_TYPE'));
        $this->assign('type',$check_coupon['type']);       
        $this->assign('lists',$coupon_list);                
        $this->assign('page',$show);
        $this->assign('pager',$Page);
        return $this->fetch();
    }
    
    /*
     * 删除一张优惠券
     */
    public function coupon_list_del(){
        //获取优惠券ID
        $cid = I('get.id');
        // $cid = 56;
        if(!$cid)
            $this->error("缺少参数值");
        //查询是否存在优惠券
        $row = M('coupon_list')->where(array('id'=>$cid))->delete();
        if(!$row)
            $this->error('删除失败');
        $this->success('删除成功');
    }
}