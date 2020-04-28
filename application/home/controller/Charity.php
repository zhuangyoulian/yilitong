<?php
/**
 * Created by PhpStorm.
 * User: lijiayi
 * Date: 2017/3/24
 * Time: 14:45
 */
namespace ylt\home\controller; 
use think\Controller;
use think\Url;
use think\Config;
use think\Page;
use think\Verify;
use think\Db;
use think\Request;
use think\Cache;
use ylt\home\logic\GoodsLogic;
use ylt\home\logic\SupplierLogic;
use ylt\home\model\UsersLogic;

class Charity extends Base {

    public $user_id = 0;
    public $user = array();
    /*
     * 处理登录后需要的参数
     */
    public function _initialize() {      
        parent::_initialize();
        if(session('?user'))
        {
            $user = session('user');
            $user = Db::name('users')->where("user_id", $user['user_id'])->Cache(true,600)->find();
            session('user',$user);  //覆盖session 中的 user               
            $this->user = $user;
            $this->user_id = $user['user_id'];
            $this->assign('user',$user); //存储用户信息
            $this->assign('user_id',$this->user_id);
        }
    }

    public function charity_array(){
        $array=[
            0=>[
                0=>'N95口罩',
                1=>'500-1000',
                2=>'1001-5000',
                3=>'5001-10000',
                4=>'10001-50000',
                5=>'50000个以上',
            ],
            1=>[
                0=>'一次性使用医用口罩',
                1=>'500-1000',
                2=>'1001-5000',
                3=>'5001-10000',
                4=>'10001-50000',
                5=>'50000个以上',
            ],
            2=>[
                0=>'速干手消液',
                1=>'500-1000',
                2=>'1001-5000',
                3=>'5001-10000',
                4=>'10001-50000',
                5=>'50000个以上',
            ],
            3=>[
                0=>'医用消毒液',
                1=>'500-1000',
                2=>'1001-5000',
                3=>'5001-10000',
                4=>'10001-50000',
                5=>'50000个以上',
            ],
            4=>[
                0=>'测温枪',
                1=>'500-1000',
                2=>'1001-5000',
                3=>'5001-10000',
                4=>'10001-50000',
                5=>'50000个以上',
            ],
            5=>[
                0=>'护目镜',
                1=>'500-1000',
                2=>'1001-5000',
                3=>'5001-10000',
                4=>'10001-50000',
                5=>'50000个以上',
            ],
            6=>[
                0=>'自动感应消毒机',
                1=>'500-1000',
                2=>'1001-5000',
                3=>'5001-10000',
                4=>'10001-50000',
                5=>'50000个以上',
            ],
        ];
        return $array;
    }
    public function index(){
        $array = $this->charity_array();
        $charity = Db::name('MedicalCharity')->where('user_id',$this->user_id)->order('do_id desc')->find();
        if ($charity['supply_goods']) {
            $supply_goods = explode(';',$charity['supply_goods']);
            foreach ($supply_goods as $key => $value) {
                $supply_goods_list[] = explode(',',$value);
            }
            foreach($supply_goods_list as $k=>$v){
                if($v[0]==''){
                    unset($supply_goods_list[$k]);
                }
            }
        }
        if ($charity['materials']) {
            $materials = explode(',',$charity['materials']);
            foreach ($materials as $key => $value) {
                $materials_list[] = explode(':',$value);
            }
            foreach($materials_list as $k=>$v){
                if($v[0]==''){
                    unset($materials_list[$k]);
                }
            }
            foreach ($array as $k => $va) {
                foreach($materials_list as $k=>$v){
                    if ($va[0] == $v[0]) {
                        $va[6] = 1;
                        $va[7] = $v[1];
                    }
                }
                    $array_materials[] = $va;
            }
        }else{
            $array_materials = $array;
        }

        if ($charity['materials_s']) {
            $materials_s = explode(',',$charity['materials_s']);
            foreach ($materials_s as $key => $value) {
                $materials_s_list[] = explode(':',$value);
            }
            foreach($materials_s_list as $k=>$v){
                if($v[0]==''){
                    unset($materials_s_list[$k]);
                }
            }
            foreach ($array as $k => $va) {
                foreach($materials_s_list as $k=>$v){
                    if ($va[0] == $v[0]) {
                        $va[6] = 1;
                        $va[7] = $v[1];
                    }
                }
                    $array_materials_s[] = $va;
            }
        }else{
            $array_materials_s = $array;
        }
        //受捐方
        $charity_assign_list = Db::name('MedicalCharity')->where(['is_purchase'=>3,'status'=>2])->field('do_company')->select(); 
        $this->assign('charity_assign_list',$charity_assign_list);
        $this->assign('signPackage',$signPackage);
        $this->assign('supply_goods_list',$supply_goods_list);
        $this->assign('materials_list',$materials_list);
        $this->assign('charity',$charity);
        $this->assign('array_materials',$array_materials);
        $this->assign('array_materials_s',$array_materials_s);
        
        return $this->fetch();
    }

    //问卷提交
    public function add_charity(){
        $data = I('');
        $data['materials'] = str_replace(array("[","{","]","}",'"'),"",$data['materials']); 
        $data['materials_s'] = str_replace(array("[","{","]","}",'"'),"",$data['materials_s']); 
        if ($data['is_purchase']==1) {  //购买
            $add['user_id'] = $this->user_id;
            $add['is_purchase'] = $data['is_purchase'];
            if ($data['is_donate'] == 1) {
                $add['is_donate'] = '爱心捐赠';
            }else if($data['is_donate'] == 2){
                $add['is_donate'] = '企业自用';
            }else if($data['is_donate'] == 3){
                $add['is_donate'] = '爱心捐赠,企业自用';
            }
            // $add['is_donate'] = $data['is_donate'];
            $add['budget'] = $data['budget'];
            $add['do_name'] = $data['do_name'];
            $add['do_phone'] = $data['do_phone'];
            $add['do_company'] = $data['do_company'];
            $add['do_address'] = $data['do_address'];
            $add['comment'] = $data['comment'];
            $add['materials'] = $data['materials'];
            $add['supply_goods']    = '/';
            if ($data['is_donate'] == 2) {
                $add['beneficiaries'] = '/';
            }else{
                $add['beneficiaries'] = $data['beneficiaries'];
            }
            $add['add_time'] = time();
            if (Db::name('MedicalCharity')->where('user_id',$this->user_id)->where(['is_purchase'=>1,'is_donate'=>'企业自用'])->find()) {
                Db::name('MedicalCharity')->where('user_id',$this->user_id)->where(['is_purchase'=>1,'is_donate'=>'企业自用'])->delete();
                $arr=Db::name('MedicalCharity')->insert($add);
            }else{
                $arr=Db::name('MedicalCharity')->insert($add);
            }
            if ($arr) {
                return array('status'=>1);
            }
        }elseif ($data['is_purchase']==2){                          //供货
            $add['user_id'] = $this->user_id;
            $add['is_purchase'] = $data['is_purchase'];
            $add['do_company']  = $data['do_company'];
            $add['do_phone']    = $data['do_phone'];
            $add['beneficiaries'] = '/';
            $add['is_donate'] = '/';
            $add['budget'] = '/';
            $add['materials'] = '/';
            $add['do_name'] = '/';
            $add['do_address'] = '/';
            $add['comment'] = '/';
            $add['add_time']    = time();
            for ($i=0; $i <count($data['supply_goods_0']) ; $i++) { 
                $supply_goods[] = $data['supply_goods_0'][$i].','.$data['supply_goods_1'][$i].','.$data['supply_goods_2'][$i].','.$data['supply_goods_3'][$i];
            }
            if ($supply_goods) {
                $supply_goods = implode(';',$supply_goods);
            }
            $add['supply_goods']    = $supply_goods;
            if (Db::name('MedicalCharity')->where('user_id',$this->user_id)->where('is_purchase',2)->find()) {
                Db::name('MedicalCharity')->where('user_id',$this->user_id)->where('is_purchase',2)->delete();
                $arr=Db::name('MedicalCharity')->insert($add);
            }else{
                $arr=Db::name('MedicalCharity')->insert($add);
            }
            if ($arr) {
                return array('status'=>1);
            }
        }elseif ($data['is_purchase']==3){                          //求助
            $add['user_id'] = $this->user_id;
            $add['is_purchase'] = $data['is_purchase'];
            $add['do_company']  = $data['do_company'];
            $add['do_name']     = $data['do_name_s'];
            $add['do_phone']    = $data['do_phone'];
            $add['do_address']  = $data['do_address_s'];
            $add['materials_s']   = $data['materials_s'];
            $add['comment']     = $data['comment_s'];
            $add['beneficiaries'] = '/';
            $add['is_donate'] = '/';
            $add['budget'] = '/';
            $add['supply_goods']    = '/';
            $add['add_time']    = time();
            if (Db::name('MedicalCharity')->where('user_id',$this->user_id)->where('is_purchase',3)->find()) {
                Db::name('MedicalCharity')->where('user_id',$this->user_id)->where('is_purchase',3)->delete();
                $arr = Db::name('MedicalCharity')->insert($add);
            }else{
                $arr = Db::name('MedicalCharity')->insert($add);
            }
            if ($arr) {
                return array('status'=>1);
            }
        }
    }

    public function donationlove(){
        $this->index();
        
        return $this->fetch();
    }
}