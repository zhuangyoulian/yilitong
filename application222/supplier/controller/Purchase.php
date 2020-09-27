<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/7
 * Time: 18:25
 */
namespace ylt\supplier\controller;
use think\Db;
use think\Page;
use think\Url;
class Purchase extends Base {

    public function purchase_list(){

        $p = I('p/d',1);
    	$list = DB::name('purchase')->where('supplier_id = '.session('supplier_id').'')->order('id DESC')->page($p.',20')->select();
    	
    	$count = DB::name('purchase')->where('supplier_id = '.session('supplier_id').'')->count();
    	$Page = new Page($count,20);
    	$show = $Page->show();
		$this->assign('pager',$Page);
		$this->assign('page',$show);
		$this->assign('list',$list);
    	return $this->fetch();
    }
	
	
	public function purchase_info(){
		
		$id = I('id');
        $info = Db::name('purchase')->where('id',$id)->find();
        if(IS_POST){
            $_POST['supplier_id'] = session('supplier_id');
            if($id){
                Db::name('purchase')->where('id',$id)->where('supplier_id',session('supplier_id'))->update($_POST);
            }else{
                Db::name('purchase')->insert($_POST);
            }           
            exit($this->error('操作成功'));
        } 

        if(empty($info['inquiry_time'])){
            $info['inquiry_time']=time();
        }
        if(empty($info['dead_time'])){
            $info['dead_time']=time()+3600*24;
        }
        if(empty($info['expect_time'])){
            $info['expect_time']=time()+3600*48;
        }
       
		$this->assign('info',$info);
		return $this->fetch();
	}
  



    //删除
	public function del_purchase(){
		$id = I('del_id');
        if ($id) {
            Db::name('purchase')->where("id=$id")->delete();
            exit(json_encode(1));
        } else {
            exit(json_encode(0));
        }
    	
    }

    //添加
    public function add_purchase(){
        $data = I('post.');
        $data['supplier_id'] = session('supplier_id');
        $data['inquiry_time'] = strtotime($data['inquiry_time']);
        // $data['inquiry_time'] = time();
        $data['dead_time'] = strtotime($data['dead_time']);
        $data['expect_time'] = strtotime($data['expect_time']);
        $data_now=strtotime("now")-3600*24;
        $data['add_time'] = time();
        if(!(preg_match("/^1[34578]{1}\d{9}$/",$data['tel']))){
            $arr=array('status'=>"-1",'msg'=>"请输入正确的手机号");
            exit(json_encode($arr));
        }
        if($data['goods_count']>9999999999){
            $arr=array('status'=>"-1",'msg'=>"采购商品数量不能超过1亿");
            exit(json_encode($arr));
        }

        if(empty($data['goods_sn'])){
             $data['goods_sn'] = date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
        }

        if(!empty($data['hcity']) && !is_numeric($data['hcity'])){
            $rs=Db::name('region')->where( 'name',"{$data['hcity']}")->find();
            $data['province']=$rs['id'];
        }
        if(!empty($data['hproper']) && !is_numeric($data['hproper'])){
            $rs=Db::name('region')->where( 'name',"{$data['hproper']}")->find();
            $data['city']=$rs['id'];
        }
        if(!empty($data['harea']) && !is_numeric($data['harea'])){
            $rs=Db::name('region')->where( 'name',"{$data['harea']}")->find();
            $data['area']=$rs['id'];
        }
        if(empty($data['province']) || empty($data['city'])){
            $arr=array('status'=>"-1",'msg'=>"请输入正确地址");
            exit(json_encode($arr));
        }

        if(empty($data['region'])){
            $data['region'] = "全国";
        }     
    	

        if($data['inquiry_time']<$data_now){
            $arr=array('status'=>"-1",'msg'=>"请输入正确询价时间!");
            exit(json_encode($arr));
        }
        if($data['dead_time']<$data['inquiry_time']){
            $arr=array('status'=>"-1",'msg'=>"请输入正确截止时间!");
            exit(json_encode($arr));
        }
        if($data['expect_time']>$data['dead_time']){

            if($data['id']){
                $rs= Db::name('Purchase')->where('id',$data['id'])->where('supplier_id',session('supplier_id'))->update($data);
                
            }else{
                // echo Purchase::fetchsql()->where('id',$data['id'])->where('supplier_id',session('supplier_id'))->update($data);
                // die;
                $Purchaseid=Db::name('Purchase')->insertGetId($data);
                if($Purchaseid){
                    $pl = [];
                    for ($x=0; $x<count($data['goods_name']); $x++) {
                          $pl[] =['purchase_id'=>$Purchaseid,
                                    'goods_number' => $data['goods_sn'][$x],'title'=>$data['title'],'goods_brand'=>$data['goods_ask'],'goods_name'=>$data['goods_name'][$x],'goods_num'=>$data['goods_count'],'goods_remarks'=>$data['quote_ask'],'t'=>$data['tel'],'addressxn'=>$data['region'],'add_time'=>time(),'address'=>$data['address']
                                 ];
                    }
                    $result = Db::name('purchase_list')->insertAll($pl);    // 批量添加
                                
                }
              
              
              Db::name('purchase')->where('supplier_id',$data['supplier_id'])->update(['operator'=>2]);
            }

            $rs=array('status'=>'1','msg'=>'编辑成功!');
            exit(json_encode($rs));
        }
        else{
            $rs=array('status'=>'-1','msg'=>'期望收货时间比截止时间晚!');
            exit(json_encode($rs));

        }
    }

    	public function pl_info(){
        $id = I('id');
        $info = Db::name('purchase')->where('id',$id)->find();
        if($info){
            $pl_info = Db::name('purchase_list')->where('purchase_id',$id)->select();
            $supply= DB::name('supply')->alias('S')->join('supplier P','P.supplier_id =S.supplier_id')->where('purchase_id = '.$id)->select();
        }
        //var_dump($pl_info);
        //var_dump($supply);
        //exit();
        $this->assign('info',$info);
        $this->assign('purchase_list',$pl_info);
        $this->assign('supply',$supply);
        return $this->fetch();
    }
  
   public function check_ajax(){
        $supply_id = I('post.id');
        $list=Db::name('supply_list')
            ->field('s.goods_tprice,s.id,s.goods_sprice,p.goods_name,p.goods_norm,p.goods_color,p.goods_unit,p.goods_num,p.goods_brand,s.goods_duration,s.goods_freight')
            ->alias('s')
            ->join('purchase_list p','s.purlist_id = p.id')
            ->where('s.supply_id',$supply_id)
            ->select();
        if($list){
            $this->ajaxReturn(['status'=>1,'list'=>$list]);
        }
    }
   
    public function supply_list(){
        $p = I('p/d',1);
        $field = 'S.*,P.title,P.company_name,P.contacts_name,P.tel,P.dead_time,P.inquiry_time,P.expect_time,P.region,P.status';
        $list = DB::name('supply')->alias('S')->join('purchase P','P.id =S.purchase_id')->where('S.supplier_id = '.session('supplier_id').'')->field($field)->order('S.id DESC')->page($p.',20')->select();
        $count = DB::name('supply')->alias('S')->join('purchase P','P.id =S.purchase_id')->where('S.supplier_id = '.session('supplier_id').'')->order('S.id DESC')->count();

        $Page = new Page($count,20);
        $show = $Page->show();
        $this->assign('pager',$Page);
        $this->assign('page',$show);
        $this->assign('list',$list);
        return $this->fetch();
    }

    public function supply_info(){
        $id = I('id');
        $supply = DB::name('supply')->alias('S')->join('purchase P','P.id =S.purchase_id')->where('S.supplier_id = '.session('supplier_id').' and S.id='.$id)->order('S.id DESC')->find();
        //$pl_list=DB::name('supply')->where('purchase_id = '.$supply['id'])->select();
        if($supply){
            $supply_info=DB::name('supply_list')->alias('S')->join('purchase_list P','P.id =S.purlist_id')->where('S.supply_id = '.$id)->select();
        }
    //  dump($id);
		//dump( $supply_info);
      	//exit();
        $this->assign('supply',$supply);
        $this->assign('supply_info',$supply_info);
        return $this->fetch();
    }
   




}