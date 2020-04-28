<?php

/**
 * Created by PhpStorm.
 * User: lijiayi
 * Date: 2017/3/30
 * Time: 16:15
 */

namespace ylt\redsupplier\controller;
use think\Page;
use think\Verify;
use think\Db;
use think\Session;
use think\Url;
use think\Request;

class Admin extends Base {

    public function index(){
    	$list = array();
      $red_admin_id = session('red_admin_id');
		  // $redsupplier_id =session('redsupplier_id');
    	$keywords = I('keywords/s');
    	if(empty($keywords)){
    		$res = Db::name('redsupplier_user')->where('red_admin_id',$red_admin_id)->select();
    	}else{
			$res = DB::name('redsupplier_user')->where('user_name','like','%'.$keywords.'%')->where('red_admin_id',$red_admin_id)->order('red_admin_id')->select();
    	}
    	// $role = Db::name('redsupplier_role')->column('role_id,role_name');
    	// if($res && $role){
    		foreach ($res as $val){
    	// 		$val['role'] =  $role[$val['role_id']];
    			$val['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
    			$list[] = $val;
    		}
    	// }
    	$this->assign('list',$list);
        return $this->fetch();
    }
    
    // /**
    //  * 修改管理员密码
    //  * @return \think\mixed
    //  */
    // public function modify_pwd(){
    //     $red_admin_id = I('red_admin_id/d',0);
    //     $oldPwd = I('old_pw/s');
    //     $newPwd = I('new_pw/s');
    //     $new2Pwd = I('new_pw2/s');
       
    //     if($red_admin_id){
    //         $info = Db::anem('redsupplier_user')->where("red_admin_id", $red_admin_id)->find();
    //         $info['password'] =  "";
    //         $this->assign('info',$info);
    //     }
        
    //      if(IS_POST){
    //         //修改密码
    //         $enOldPwd = encrypt($oldPwd);
    //         $enNewPwd = encrypt($newPwd);
    //         $admin = DB::name('redsupplier_user')->where('red_admin_id' , $red_admin_id)->find();
    //         if(!$admin || $admin['password'] != $enOldPwd){
    //             exit(json_encode(array('status'=>-1,'msg'=>'旧密码不正确')));
    //         }else if($newPwd != $new2Pwd){
    //             exit(json_encode(array('status'=>-1,'msg'=>'两次密码不一致')));
    //         }else{
    //             $row = DB::name('redsupplier_user')->where('red_admin_id' , $red_admin_id)->update(array('password' => $enNewPwd));
    //             if($row){
    //                 exit(json_encode(array('status'=>1,'msg'=>'修改成功')));
    //             }else{
    //                 exit(json_encode(array('status'=>-1,'msg'=>'修改失败')));
    //             }
    //         }
    //     }
    //     return $this->fetch();
    // }
    
   //  public function admin_info(){
   //  	$red_admin_id = I('get.red_admin_id/d',0);
   //  	if($red_admin_id){
   //  		$info = D('redsupplier_user')->where("red_admin_id", $red_admin_id)->find();
			//   $info['password'] =  "";
   //  		$this->assign('info',$info);
   //  	}
   //  	$act = empty($red_admin_id) ? 'add' : 'edit';
   //  	$this->assign('act',$act);
   //  	// $role = Db::name('redsupplier_role')->where('redsupplier_id',session('redsupplier_id'))->select();
   //  	// $this->assign('role',$role);
   //  	return $this->fetch();
   //  }
    
   //  public function adminHandle(){
   //  	$data = I('post.');
		 //  $data['red_admin_id'] = session('red_admin_id');
   //  	if(empty($data['password'])){
   //  		unset($data['password']);
   //  	}else{
   //  		$data['password'] = encrypt($data['password']);
   //  	}
   //  	if($data['act'] == 'add'){
   //  		unset($data['red_admin_id']); 
			//   $data['red_admin_id'] = session('red_admin_id');
   //  		$data['add_time'] = time();
			//   $data['state'] = 1;
   //  		if(Db::name('redsupplier_user')->where(array('mobile'=>$data['mobile']))->count()){
   //  			$this->error("手机号已注册，请更换",Url::build('redsupplier/Admin/admin_info'));
   //  		}else{
   //  			$r = Db::name('redsupplier_user')->insert($data);
			// 	adminLog('添加管理员 '.$data['user_name'].'');
   //  		}
   //  	}
    	
   //  	if($data['act'] == 'edit'){
   //  		$r = Db::name('redsupplier_user')->where('red_admin_id', $data['red_admin_id'])->update($data);
			//   adminLog('修改管理员信息 '.$data['user_name'].'');
   //  	}
    	
   //      if($data['act'] == 'del' && $data['red_admin_id']>1){
   //  		$r = Db::name('redsupplier_user')->where('red_admin_id', $data['red_admin_id'])->delete();
			// adminLog('删除管理员'.$data['red_admin_id'].'');
   //  		exit(json_encode(1));
   //  	}
    	
   //  	if($r){
   //  		$this->success("操作成功",Url::build('redsupplier/Admin/index'));
   //  	}else{
   //  		$this->error("操作失败",Url::build('redsupplier/Admin/index'));
   //  	}
   //  }
    
    
    /*
     * 管理员登陆
     */
    public function login(){
        if(session('?red_admin_id') && session('red_admin_id')>0 && session('redsupplier_id')>0){
             $this->error("您已登录",Url::build('redsupplier/Index/index'));
        }
    
        if(IS_POST){
            $verify = new Verify();
			      if (!$verify->check(I('post.vertify'), "admin_login")) {
            	exit(json_encode(array('status'=>0,'msg'=>'验证码错误')));
            }
            $condition['mobile'] = I('post.username/s');
            $condition['password'] = I('post.password/s');
			      //$condition['state'] = '1'; 
            if(!empty($condition['mobile']) && !empty($condition['password'])){
                $condition['password'] = encrypt($condition['password']);
               	$admin_info = Db::name('redsupplier_user')->where($condition)->find();
                if(is_array($admin_info)){
					        if($admin_info['state'] != '1') 
						        exit(json_encode(array('status'=>0,'msg'=>'账号未审核')));
                    session('red_admin_id',$admin_info['red_admin_id']);
					          // session('redsupplier_id',$admin_info['redsupplier_id']);
                    // session('act_list',$admin_info['act_list']);
                    session('company_name',$admin_info['company_name']);
                    Db::name('redsupplier_user')->where("red_admin_id = ".$admin_info['red_admin_id'])->update(array('last_login'=>time(),'last_ip'=>  getIP()));
                    session('last_login_time',$admin_info['last_login']);
                    session('last_login_ip',$admin_info['last_ip']);
                    adminLog('后台登录');
                    $url =Url::build('redsupplier/Index/index');
                    exit(json_encode(array('status'=>1,'url'=>$url)));
                }else{
                    exit(json_encode(array('status'=>0,'msg'=>'账号密码不正确')));
                }
            }else{
                exit(json_encode(array('status'=>0,'msg'=>'请填写账号密码')));
            }
        }
        
       return $this->fetch();
    }
    
    /**
     * 退出登陆
     */
    public function logout(){
        session_unset();
        session_destroy();
		    session::clear();
        $this->success("退出成功",Url::build('Home/RedBusiness/login'));
    }
    
     /**
     * 验证码获取
     */
    public function vertify()
    {
        $config = array(
            'fontSize' => 50,
            'length' => 4,
            'useCurve' => true,
            'useNoise' => false,
        	'reset' => false
        );    
        $Verify = new Verify($config);
        $Verify->entry("admin_login");
    }
    
  //   public function role(){
		// $redsupplier_id = session('redsupplier_id');
  //   	$list = Db::name('redsupplier_role')->where("redsupplier_id = $redsupplier_id and act_list != 'all'")->order('role_id desc')->select();
  //   	$this->assign('list',$list);
  //   	return $this->fetch();
  //   }
    
  //   public function role_info(){
  //   	$role_id = I('get.role_id/d');
  //   	$detail = array();
  //   	if($role_id){
  //   		$detail = DB::name('redsupplier_role')->where("role_id",$role_id)->find();
  //   		$detail['act_list'] = explode(',', $detail['act_list']);
  //   		$this->assign('detail',$detail);
  //   	}
		// $right = DB::name('redsupplier_menu')->order('id')->select();
		// foreach ($right as $val){
		// 	if(!empty($detail)){
		// 		$val['enable'] = in_array($val['id'], $detail['act_list']);
		// 	}
		// 	$modules[$val['group']][] = $val;
		// }
		// //权限组
		// $group = array('system'=>'系统设置','content'=>'内容管理','goods'=>'商品中心','member'=>'会员中心',
		// 		'order'=>'订单中心','marketing'=>'营销推广','tools'=>'插件工具','count'=>'统计报表'
		// );
		// $this->assign('group',$group);
		// $this->assign('modules',$modules);
  //   	return $this->fetch();
  //   }
    
  //   public function roleSave(){
  //   	$data = I('post.');
  //   	$res = $data['data'];
		// $res['redsupplier_id'] = session('redsupplier_id');
  //   	$res['act_list'] = is_array($data['right']) ? implode(',', $data['right']) : '';
  //   	if(empty($data['role_id'])){
  //   		$r = Db::name('redsupplier_role')->insert($res);
		// 	adminLog('添加角色 '.input('role_name').'');
  //   	}else{
  //   		$r = Db::name('redsupplier_role')->where('role_id', $data['role_id'])->update($res);
		// 	adminLog('编辑角色权限 '.input('role_name').'');
  //   	}
		// if($r){
		// 	adminLog('管理角色');
		// 	$this->success("操作成功!",Url::build('redsupplier/Admin/role_info',array('role_id'=>$data['role_id'])));
		// }else{
		// 	$this->error("操作失败!",Url::build('redsupplier/Admin/role'));
		// }
  //   }
    
  //   public function roleDel(){
  //   	$role_id = I('post.role_id/d');
  //   	$admin = Db::name('redsupplier_user')->where('role_id',$role_id)->find();
  //   	if($admin){
  //   		exit(json_encode("请先清空所属该角色的管理员"));
  //   	}else{
  //   		$d = DB::name('redsupplier_role')->where("role_id", $role_id)->delete();
  //   		if($d){
  //   			exit(json_encode(1));
  //   		}else{
  //   			exit(json_encode("删除失败"));
  //   		}
  //   	}
  //   }
    
    public function log(){
    	$p = I('p/d',1);
    	$logs = DB::name('redsupplier_admin_log')->alias('l')->join('redsupplier_user a','a.red_admin_id =l.red_admin_id')->where('l.red_admin_id ='.session('red_admin_id').'')->order('log_time DESC')->page($p.',20')->select();
    	$this->assign('list',$logs);
    	$count = DB::name('redsupplier_admin_log')->where('red_admin_id = '.session('red_admin_id').'')->count();
    	$Page = new Page($count,20);
    	$show = $Page->show();
		  $this->assign('pager',$Page);
		  $this->assign('page',$show);
    	return $this->fetch();
    }



}