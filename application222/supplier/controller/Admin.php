<?php

/**
 * Created by PhpStorm.
 * User: lijiayi
 * Date: 2017/3/30
 * Time: 16:15
 */

namespace ylt\supplier\controller;
use think\Page;
use think\Verify;
use think\Db;
use think\Session;
use think\Url;
use think\Request;

class Admin extends Base {

    public function index(){
    	$list = array();
		$supplier_id =session('supplier_id');
    	$keywords = I('keywords/s');
    	if(empty($keywords)){
    		$res = Db::name('supplier_user')->where('supplier_id',$supplier_id)->select();
    	}else{
			$res = DB::name('supplier_user')->where('user_name','like','%'.$keywords.'%')->where('supplier_id',$supplier_id)->order('admin_id')->select();
    	}
    	$role = Db::name('supplier_role')->column('role_id,role_name');
    	if($res && $role){
    		foreach ($res as $val){
    			$val['role'] =  $role[$val['role_id']];
    			$val['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
    			$list[] = $val;
    		}
    	}
    	$this->assign('list',$list);
        return $this->fetch();
    }
    
    /**
     * 修改管理员密码
     * @return \think\mixed
     */
    public function modify_pwd(){
        $admin_id = I('admin_id/d',0);
        $oldPwd = I('old_pw/s');
        $newPwd = I('new_pw/s');
        $new2Pwd = I('new_pw2/s');
       
        if($admin_id){
            $info = Db::anem('supplier_user')->where("admin_id", $admin_id)->find();
            $info['password'] =  "";
            $this->assign('info',$info);
        }
        
         if(IS_POST){
            //修改密码
            $enOldPwd = encrypt($oldPwd);
            $enNewPwd = encrypt($newPwd);
            $admin = DB::name('supplier_user')->where('admin_id' , $admin_id)->find();
            if(!$admin || $admin['password'] != $enOldPwd){
                exit(json_encode(array('status'=>-1,'msg'=>'旧密码不正确')));
            }else if($newPwd != $new2Pwd){
                exit(json_encode(array('status'=>-1,'msg'=>'两次密码不一致')));
            }else{
                $row = DB::name('supplier_user')->where('admin_id' , $admin_id)->update(array('password' => $enNewPwd));
                if($row){
                    exit(json_encode(array('status'=>1,'msg'=>'修改成功')));
                }else{
                    exit(json_encode(array('status'=>-1,'msg'=>'修改失败')));
                }
            }
        }
        return $this->fetch();
    }
    
    public function admin_info(){
    	$admin_id = I('get.admin_id/d',0);
    	if($admin_id){
    		$info = D('supplier_user')->where("admin_id", $admin_id)->find();
			$info['password'] =  "";
    		$this->assign('info',$info);
    	}
    	$act = empty($admin_id) ? 'add' : 'edit';
    	$this->assign('act',$act);
    	$role = Db::name('supplier_role')->where('supplier_id',session('supplier_id'))->select();
    	$this->assign('role',$role);
    	return $this->fetch();
    }
    
    public function adminHandle(){
    	$data = I('post.');
		$data['supplier_id'] = session('supplier_id');
    	if(empty($data['password'])){
    		unset($data['password']);
    	}else{
    		$data['password'] = encrypt($data['password']);
    	}
    	if($data['act'] == 'add'){
    		unset($data['admin_id']); 
			$data['supplier_id'] = session('supplier_id');
    		$data['add_time'] = time();
			$data['state'] = 1;
    		if(Db::name('supplier_user')->where(array('mobile'=>$data['mobile']))->count()){
    			$this->error("手机号已注册，请更换",Url::build('Supplier/Admin/admin_info'));
    		}else{
    			$r = Db::name('supplier_user')->insert($data);
				adminLog('添加管理员 '.$data['user_name'].'');
    		}
    	}
    	
    	if($data['act'] == 'edit'){
    		$r = Db::name('supplier_user')->where('admin_id', $data['admin_id'])->update($data);
			  adminLog('修改管理员信息 '.$data['user_name'].'');
    	}
    	
        if($data['act'] == 'del' && $data['admin_id']>1){
    		$r = Db::name('supplier_user')->where('admin_id', $data['admin_id'])->delete();
			adminLog('删除管理员'.$data['admin_id'].'');
    		exit(json_encode(1));
    	}
    	
    	if($r){
    		$this->success("操作成功",Url::build('Supplier/Admin/index'));
    	}else{
    		$this->error("操作失败",Url::build('Supplier/Admin/index'));
    	}
    }
    
    
    /*
     * 管理员登陆
     */
    public function login(){
        if(session('?admin_id') && session('admin_id')>0 && session('supplier_id')>0){
             $this->error("您已登录",Url::build('Supplier/Index/index'));
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
               	$admin_info = Db::name('supplier_user')->alias('s')->join('supplier_role r', array('s.role_id=r.role_id','s.supplier_id=r.supplier_id'),'INNER')->where($condition)->find();
                if(is_array($admin_info)){
					if($admin_info['state'] != '1') 
						 exit(json_encode(array('status'=>0,'msg'=>'账号未审核')));
                    session('admin_id',$admin_info['admin_id']);
					session('supplier_id',$admin_info['supplier_id']);
                    session('act_list',$admin_info['act_list']);
                    session('supplier_name',$admin_info['supplier_name']);
                    Db::name('supplier_user')->where("admin_id = ".$admin_info['admin_id'])->update(array('last_login'=>time(),'last_ip'=>  getIP()));
                    //session('last_login_time',$admin_info['last_login']);
                    //session('last_login_ip',$admin_info['last_ip']);
                    adminLog('后台登录');
                    $url =Url::build('Supplier/Index/index');
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
        $this->success("退出成功",Url::build('Home/business/login'));
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
    
    public function role(){
		$supplier_id = session('supplier_id');
    	$list = Db::name('supplier_role')->where("supplier_id = $supplier_id and act_list != 'all'")->order('role_id desc')->select();
    	$this->assign('list',$list);
    	return $this->fetch();
    }
    
    public function role_info(){
    	$role_id = I('get.role_id/d');
    	$detail = array();
    	if($role_id){
    		$detail = DB::name('supplier_role')->where("role_id",$role_id)->find();
    		$detail['act_list'] = explode(',', $detail['act_list']);
    		$this->assign('detail',$detail);
    	}
		$right = DB::name('supplier_menu')->order('id')->select();
		foreach ($right as $val){
			if(!empty($detail)){
				$val['enable'] = in_array($val['id'], $detail['act_list']);
			}
			$modules[$val['group']][] = $val;
		}
		//权限组
		$group = array('system'=>'系统设置','content'=>'内容管理','goods'=>'商品中心','member'=>'会员中心',
				'order'=>'订单中心','marketing'=>'营销推广','tools'=>'插件工具','count'=>'统计报表'
		);
		$this->assign('group',$group);
		$this->assign('modules',$modules);
    	return $this->fetch();
    }
    
    public function roleSave(){
    	$data = I('post.');
    	$res = $data['data'];
		$res['supplier_id'] = session('supplier_id');
    	$res['act_list'] = is_array($data['right']) ? implode(',', $data['right']) : '';
    	if(empty($data['role_id'])){
    		$r = Db::name('supplier_role')->insert($res);
			adminLog('添加角色 '.input('role_name').'');
    	}else{
    		$r = Db::name('supplier_role')->where('role_id', $data['role_id'])->update($res);
			adminLog('编辑角色权限 '.input('role_name').'');
    	}
		if($r){
			adminLog('管理角色');
			$this->success("操作成功!",Url::build('Supplier/Admin/role_info',array('role_id'=>$data['role_id'])));
		}else{
			$this->error("操作失败!",Url::build('Supplier/Admin/role'));
		}
    }
    
    public function roleDel(){
    	$role_id = I('post.role_id/d');
    	$admin = Db::name('supplier_user')->where('role_id',$role_id)->find();
    	if($admin){
    		exit(json_encode("请先清空所属该角色的管理员"));
    	}else{
    		$d = DB::name('supplier_role')->where("role_id", $role_id)->delete();
    		if($d){
    			exit(json_encode(1));
    		}else{
    			exit(json_encode("删除失败"));
    		}
    	}
    }
    
    public function log(){
    	$p = I('p/d',1);
    	$logs = DB::name('supplier_admin_log')->alias('l')->join('supplier_user a','a.admin_id =l.admin_id')->where('l.supplier_id ='.session('supplier_id').'')->order('log_time DESC')->page($p.',20')->select();
    	$this->assign('list',$logs);
    	$count = DB::name('supplier_admin_log')->where('supplier_id = '.session('supplier_id').'')->count();
    	$Page = new Page($count,20);
    	$show = $Page->show();
		$this->assign('pager',$Page);
		$this->assign('page',$show);
    	return $this->fetch();
    }



}