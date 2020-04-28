<?php
namespace ylt\home\logic;
use think\Model;
use think\Page;
use think\Db;
/**
 * 分类逻辑定义
 * 
 */
class SupplierLogic extends Model
{
    /*
     * 商家入驻登陆
     */
    public function user_login($username,$password){
        $result = array();
        if(!$username || !$password)
           $result= array('status'=>0,'msg'=>'请填写账号或密码');
        $user = Db::name('supplier_user')->alias('u')->join('supplier s','s.supplier_id = u.supplier_id','left')->where("u.mobile",$username)->find();
        if(!$user){
           $result = array('status'=>-1,'msg'=>'账号不存在!');
        }elseif(encrypt($password) != $user['password']){
           $result = array('status'=>-2,'msg'=>'密码错误!');
        }else{
           $result = array('status'=>1,'msg'=>'登陆成功','result'=>$user);
        }
        return $result;
    }
    
    
    //商家入驻注册
    /*
     * * @param $mobile int 手机账号
     * * @param $password int 密码
     * * @param $user_name int 用户名称
     * * @param $company_name int 店铺名称
     * * @param $上级地区parent_id fol 推荐人
     * @return array() 找到返回数组
     */
    public function reg($mobile,$password,$password2,$user_name='',$company_name='',$parent_id = ''){
        
            $is_validated = 1;
            $map['mobile_validated'] = 1;
            $map['user_name'] = $user_name ? $user_name : $mobile ; //手机注册
            $map['mobile'] = $mobile;
            $map['company_name'] = $company_name;
        
        /* if($is_validated != 1)
         return array('status'=>-1,'msg'=>'请用手机号或邮箱注册');*/
    
        if(!$mobile || !$password)
            return array('status'=>-1,'msg'=>'请输入用户名或密码');
    
        //验证两次密码是否匹配
        if($password2 != $password)
            return array('status'=>-1,'msg'=>'两次输入密码不一致');
        //验证账号是否存在
        if(!empty($mobile)){
            $user = Db::name('supplier_user')->where("mobile",$mobile)->find();
            if($user){
                return array('status'=>-1,'msg'=>'账号已存在');
            }
        }
        
        if($parent_id){
            if(check_mobile($parent_id)){
                $panentInfo = Db::name('users')->field('user_id,recommend_code,business_level')->where('mobile',$parent_id)->find(); // 推荐人信息
            }else{
                $panentInfo = Db::name('users')->field('recommend_code,business_level')->where('recommend_code',$parent_id)->find(); // 推荐人信息

            }
            
            if($panentInfo){
                $map['parent_id'] = $panentInfo['recommend_code'];
                
                if($panentInfo['business_level'] == '4')
                    $map['FManagerId'] = $panentInfo['user_id'];
            }else{
                    return array('status'=>-1,'msg'=>'推荐人不存在');
            }
                
        }
        
        
        $map['password'] = encrypt($password);
        $map['add_time'] = time();
        
        $user_id = Db::name('supplier_user')->insertGetId($map);
        if($user_id === false){
            return array('status'=>-1,'msg'=>'注册失败');
        }
        $supplier_user = Db::name('supplier_user')->where("admin_id", $user_id)->find();
        return array('status'=>1,'msg'=>'注册成功','result'=>$supplier_user);
    }
    
    //红礼商家入驻注册
    /*
     * * @param $mobile int 手机账号
     * * @param $password int 密码
     * * @param $user_name int 用户名称
     * * @param $上级地区parent_id fol 推荐人
     * @return array() 找到返回数组
     */
    public function redreg($mobile,$password,$password2,$user_name=''){
        
            $is_validated = 1;
            $map['mobile_validated'] = 1;
            $map['user_name'] = $user_name ? $user_name : $mobile ; //手机注册
            $map['mobile'] = $mobile;
        
        if(!$mobile || !$password)
            return array('status'=>-1,'msg'=>'请输入用户名或密码');
    
        //验证两次密码是否匹配
        if($password2 != $password)
            return array('status'=>-1,'msg'=>'两次输入密码不一致');
        //验证账号是否存在
        if(!empty($mobile)){
            $user = Db::name('redsupplier_user')->where("mobile",$mobile)->find();
            if($user){
                return array('status'=>-1,'msg'=>'账号已存在');
            }
        }
        
        $map['password'] = encrypt($password);
        $map['add_time'] = time();
        
        $user_id = Db::name('redsupplier_user')->insertGetId($map);
        if($user_id === false){
            return array('status'=>-1,'msg'=>'注册失败');
        }
        $redsupplier_user = Db::name('redsupplier_user')->where("red_admin_id", $user_id)->find();
        return array('status'=>1,'msg'=>'注册成功','result'=>$redsupplier_user);
    }

    /*
     * 红礼商家入驻登陆
     */
    public function red_user_login($username,$password){
        $result = array();
        if(!$username || !$password)
           $result= array('status'=>0,'msg'=>'请填写账号或密码');
        $user = Db::name('redsupplier_user')->where("mobile",$username)->find();
        if(!$user){
           $result = array('status'=>-1,'msg'=>'账号不存在!');
        }elseif(encrypt($password) != $user['password']){
           $result = array('status'=>-2,'msg'=>'密码错误!');
        }else{
           $result = array('status'=>1,'msg'=>'登陆成功','result'=>$user);
        }
        return $result;
    }
    
}