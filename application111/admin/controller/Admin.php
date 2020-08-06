<?php
/**
 * Created by PhpStorm.
 * User: lijiayi
 * Date: 2017/3/22
 * Time: 16:15
 */
namespace ylt\admin\controller;

use think\Page;
use think\Verify;
use think\Db;
use think\Session;
use think\Url;
use think\Request;

class Admin extends Base {
    public  $plate_name;
    /*
    * 初始化操作
    */
    public function _initialize() {
        parent::_initialize();
        //获取当前账号所属板块权限
        $plate_authority = Db::name('admin_user')->alias('a')->join('plate_menu p','a.plate_id=p.id')->field('p.name,p.id,a.role_id')->where('a.admin_id',session('admin_id'))->find();
        if ($plate_authority) {
            $this->plate_authority  = $plate_authority;
        }
    }

    public function index(){
        $plate_authority = $this->plate_authority;
    	$list = array();
    	$keywords = trim(I('keywords/s'));
    	if(empty($keywords)){
            $res = Db::name('admin_user')->order('admin_id')->select();
    	}else{
			$res = DB::name('admin_user')->where('user_name','like','%'.$keywords.'%')->order('admin_id')->select();
    	}
        $role = Db::name('admin_role')->column('role_id,role_name');
    	$plate = Db::name('plate_menu')->column('id,name');
    	if($res && $role && $plate){
    		foreach ($res as $val){
                if ($plate_authority){    //有板块分类时只显示该板块的管理员
                    if ($val['plate_id'] == $plate_authority['id']) {
                        $val['role'] =  $role[$val['role_id']];
                        $val['plate'] =  $plate[$val['plate_id']];
                        $val['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
                        $list[] = $val;
                    }
                }else{
                    $val['role'] =  $role[$val['role_id']];
                    $val['plate'] =  $plate[$val['plate_id']];
                    $val['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
                    $list[] = $val;
                }
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
        $oldPwd = trim(I('old_pw/s'));
        $newPwd = trim(I('new_pw/s'));
        $new2Pwd = trim(I('new_pw2/s'));
       
        if($admin_id){
            $info = Db::name('admin_user')->where("admin_id", $admin_id)->find();
            $info['password'] =  "";
            $this->assign('info',$info);
        }
        
         if(IS_POST){
            //修改密码
            $enOldPwd = encrypt($oldPwd);
            $enNewPwd = encrypt($newPwd);
            $admin = DB::name('admin_user')->where('admin_id' , $admin_id)->find();
            if(!$admin || $admin['password'] != $enOldPwd){
                exit(json_encode(array('status'=>-1,'msg'=>'旧密码不正确')));
            }else if($newPwd != $new2Pwd){
                exit(json_encode(array('status'=>-1,'msg'=>'两次密码不一致')));
            }else{
                $row = DB::name('admin_user')->where('admin_id' , $admin_id)->save(array('password' => $enNewPwd));
                if($row){
                    exit(json_encode(array('status'=>1,'msg'=>'修改成功')));
                }else{
                    exit(json_encode(array('status'=>-1,'msg'=>'修改失败')));
                }
            }
        }
        return $this->fetch();
    }
    
    /**
     * [admin_info 添加修改管理员]
     * @return [type] [description]
     */
    public function admin_info(){
        $plate_authority = $this->plate_authority;
    	$admin_id = I('get.admin_id/d',0);
    	if($admin_id){
    		$info = Db::name('admin_user')->where("admin_id", $admin_id)->find();
			$info['password'] =  "";
    		$this->assign('info',$info);
    	}
    	$act = empty($admin_id) ? 'add' : 'edit';
    	$this->assign('act',$act);
        if ($plate_authority) {
            $plate_menu = Db::name('plate_menu')->where('id',$plate_authority['id'])->where('is_del!=1')->select();
        }else{
            $plate_menu = Db::name('plate_menu')->where('groups',0)->where('is_del!=1')->select();
        }
        $this->assign('plate_menu',$plate_menu);
        $this->assign('id',$admin_id);
    	return $this->fetch();
    }
    public function ajax_role(){
        $plate_authority = $this->plate_authority;
        $id = I('id/d',0);
        if($id){
            $info = Db::name('admin_user')->where("admin_id", $id)->find();
            $info['password'] =  "";
        }   
        if ($plate_authority) {
            $plate_id = $plate_authority['id'];
            $role = Db::name('admin_role')->where('plate_id',$plate_id)->where("role_id != $plate_authority[role_id]")->select();
        }else{
            $plate_id = $info['plate_id']?$info['plate_id']:2;
            $role = Db::name('admin_role')->where('plate_id',$plate_id)->select();
        }
        if (I('plate_id')) {
            $plate_id = I('plate_id');
            $role = Db::name('admin_role')->where('plate_id',$plate_id)->select();
        }

        $this->assign('info',$info);
        $this->assign('role',$role);
        return $this->fetch('ajax_role');
    }
    
    public function adminHandle(){
        $data = I('post.');
        $data['user_name'] = trim(I('post.user_name'));
        $data['mobile'] = trim(I('post.mobile'));
        $data['email'] = trim(I('post.email'));
    	$data['password'] = trim(I('post.password'));
        
    	if(empty($data['password'])){
    		unset($data['password']);
    	}else{
    		$data['password'] = encrypt($data['password']);
    	}
    	if($data['act'] == 'add'){
    		unset($data['admin_id']);    		
    		$data['add_time'] = time();
    		if(Db::name('admin_user')->where("user_name", $data['user_name'])->count()){
    			$this->error("此用户名已被注册，请更换",Url::build('Admin/Admin/admin_info'));
    		}else{
    			$r = Db::name('admin_user')->insert($data);
				adminLog('添加管理员'.$data['user_name'].'');
    		}
    	}
    	
    	if($data['act'] == 'edit'){
			if($data['admin_id'] == 1 && session('admin_id') != 1)
				$this->error("无权限操作",Url::build('Admin/Admin/index'));
			else
    		 $r = Db::name('admin_user')->where('admin_id', $data['admin_id'])->save($data);
			adminLog('修改管理员信息'.$data['user_name'].'');
    	}
    	
        if($data['act'] == 'del' && $data['admin_id']>1){
    		$r = Db::name('admin_user')->where('admin_id', $data['admin_id'])->delete();
			adminLog('删除管理员 '.$data['admin_id'].'');
    		exit(json_encode(1));
    	}
    	
    	if($r){
    		$this->success("操作成功",Url::build('Admin/Admin/index'));
    	}else{
    		$this->error("操作失败",Url::build('Admin/Admin/index'));
    	}
    }
    
    
    /*
     * 管理员登陆
     */
    public function login(){
        if(session('?admin_id') && session('admin_id')>0 && !session('supplier_id')){
             $this->error("您已登录",Url::build('Admin/Index/index'));
        }
		
		$url = $_SERVER['HTTP_REFERER'];
        if(!strpos($url,'admin.yilitong.com')){ //验证短信来源

			//$this->redirect('Home/Index/index');
        }
    	
        if(IS_POST){
            $verify = new Verify();
            if (!$verify->check(trim(I('post.vertify')), "admin_login")) {
            	exit(json_encode(array('status'=>0,'msg'=>'验证码错误')));
            }
            $condition['user_name'] = trim(I('post.username/s'));
            $condition['password'] = trim(I('post.password/s'));
            if(!empty($condition['user_name']) && !empty($condition['password'])){
                $condition['password'] = encrypt($condition['password']);
               	$admin_info = Db::name('admin_user')->join(PREFIX.'admin_role', PREFIX.'admin_user.role_id='.PREFIX.'admin_role.role_id','INNER')->where($condition)->find();
     
                if(is_array($admin_info)){
                    session('admin_id',$admin_info['admin_id']);
                    session('act_list',$admin_info['act_list']);
               
                    Db::name('admin_user')->where("admin_id = ".$admin_info['admin_id'])->save(array('last_login'=>time(),'last_ip'=>  getIP()));
                    adminLog('后台登录');
              
                    //$url = session('from_url') ? session('from_url') : Url::build('Admin/Index/index');
                    $url = session('from_url') ? session('from_url') : Url::build('Admin/Index/index');
                                  
                
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
        $this->success("退出成功",Url::build('Admin/Admin/login'));
    }
    
     /**
     * 验证码获取
     */
    public function vertify()
    {
		//ob_clean();
		ob_end_clean();
        $config = array(
            'fontSize' => 35,
            'length' => 4,
            'useCurve' => true,
            'useNoise' => false,
        	'reset' => false
        );    
        $Verify = new Verify($config);
        $Verify->entry("admin_login");
    }
    
    /**
     * [role 角色列表]
     * @return [type] [description]
     */
    public function role(){
        $plate_authority = $this->plate_authority;
        if ($plate_authority) {
            $list = Db::name('admin_role')->alias('r')->join('plate_menu p','r.plate_id = p.id ')->order('role_id desc')->where('plate_id',$plate_authority['id'])->select();
        }else{
            $list = Db::name('admin_role')->alias('r')->join('plate_menu p','r.plate_id = p.id ')->order('role_id desc')->select();
        }
    	$this->assign('list',$list);
    	return $this->fetch();
    }
    /**
     * [role_info 角色增加及修改页面]
     * @return [type] [description]
     */
    public function role_info(){
        $role_id = I('get.role_id/d')?I('get.role_id/d'):0;
    	$detail = array();
    	if($role_id){
    		$detail = DB::name('admin_role')->where("role_id",$role_id)->find();
    		$detail['act_list'] = explode(',', $detail['act_list']);
            if (!$detail['role_id']) {
                $detail['role_id'] = 0;
            }
    	}else{
            $detail['role_id'] = 0;
        }
        $plate_authority = $this->plate_authority;
        if ($plate_authority) {
            $plate_menu = Db::name('plate_menu')->where('id',$plate_authority['id'])->where('is_del!=1')->select();
        }else{
            $plate_menu = Db::name('plate_menu')->where('groups = 0')->where('is_del!=1')->select();
        }
        $this->assign('detail',$detail);
        $this->assign('plate_menu',$plate_menu);
    	return $this->fetch();
    }
    public function ajax_role_info(){
        $role_id = I('get.role_id/d')?I('get.role_id/d'):0;
        $plate_id = I('get.plate_id/d');
        $detail = array();
        if($role_id){
            $detail = DB::name('admin_role')->where("role_id",$role_id)->find();
            $detail['act_list'] = explode(',', $detail['act_list']);
        }
        $plate_authority = $this->plate_authority;
        if ($plate_authority) {
            $plate_id = $plate_authority['id'];
            $right = DB::name('system_menu')->where('plate_id ='.$plate_id.' AND id !=97')->order('id')->select();
        }else{
            $plate_id = $detail['plate_id']?$detail['plate_id']:2;
            $right = DB::name('system_menu')->where('plate_id ='.$plate_id.' or plate_id =22')->order('id')->select();
        }
        if (I('plate_id')) {
            $plate_id = I('plate_id');
            $right = DB::name('system_menu')->where('plate_id ='.$plate_id.' or plate_id =22')->order('id')->select();
        }
        foreach ($right as $val){
            if(!empty($detail)){
                $val['enable'] = in_array($val['id'], $detail['act_list']);
            }
            $modules[$val['group']][] = $val;
        }

        $plate_menu = Db::name('plate_menu')->where('groups != 0')->select();
        foreach ($plate_menu as $key => $value) {
            $array[]= array($value['right']=>$value['name']);
        }
        $group = array_reduce($array, 'array_merge', array());
        $role = Db::name('admin_role')->where('plate_id',$plate_id)->where('1=1')->select();
        $this->assign('detail',$detail);
        $this->assign('group',$group);
        $this->assign('modules',$modules);
        $this->assign('role',$role);
        return $this->fetch('ajax_role_info');
    }
    /**
     * [roleSave 添加及修改角色的提交]
     * @return [type] [description]
     */
    public function roleSave(){
    	$data = I('post.');
        $res = $data['data'];
        $res['role_name'] = trim($res['role_name']);
        $res['role_desc'] = trim($res['role_desc']);
        $res['plate_id'] = $data['plate_id'];
    	$res['is_three'] = $data['is_three'];
    	$res['act_list'] = is_array($data['right']) ? implode(',', $data['right']) : '';
    	if(empty($data['role_id'])){
    		$r = Db::name('admin_role')->insert($res);
			adminLog('添加角色 '.input('role_name').'');
    	}else{
    		$r = Db::name('admin_role')->where('role_id', $data['role_id'])->update($res);
			adminLog('编辑角色权限 '.input('role_name').'');
    	}
		if($r){
			$this->success("操作成功!",Url::build('Admin/Admin/role',array('role_id'=>$data['role_id'])));
		}else{
			$this->error("操作失败!",Url::build('Admin/Admin/role'));
		}
    }
    
    public function roleDel(){
    	$role_id = I('post.role_id/d');
    	$admin = Db::name('admin_user')->where('role_id',$role_id)->find();
    	if($admin){
    		exit(json_encode("请先清空所属该角色的管理员"));
    	}else{
    		$d = DB::name('admin_role')->where("role_id", $role_id)->delete();
    		if($d){
    			exit(json_encode(1));
    		}else{
    			exit(json_encode("删除失败"));
    		}
    	}
    }
    
	
	/**
	 * 管理员日志
	 */
    public function log(){
    	$p = I('p/d',1);
    	$logs = DB::name('admin_log')->alias('l')->join('admin_user a','a.admin_id =l.admin_id')->order('log_time DESC')->page($p.',20')->select();
    	$this->assign('list',$logs);
    	$count = DB::name('admin_log')->where('1=1')->count();
    	$Page = new Page($count,20);
    	$show = $Page->show();
		$this->assign('pager',$Page);
		$this->assign('page',$show);
    	return $this->fetch();
    }


	/**
	 * 供应商列表
	 */
	public function supplier()
	{
		$supplier_count = DB::name('suppliers')->count();
		$page = new Page($supplier_count, 10);
		$show = $page->show();
		$supplier_list = DB::name('suppliers')
				->alias('s')
				->field('s.*,a.admin_id,a.user_name')
				->join('__ADMIN__ a','a.suppliers_id = s.suppliers_id','LEFT')
				->limit($page->firstRow, $page->listRows)
				->select();
		$this->assign('list', $supplier_list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	/**
	 * 供应商资料
	 */
	public function supplier_info()
	{
		$suppliers_id = I('get.suppliers_id/d', 0);
		if ($suppliers_id) {
			$info = DB::name('suppliers')
					->alias('s')
					->field('s.*,a.admin_id,a.user_name')
					->join('__ADMIN__ a','a.suppliers_id = s.suppliers_id','LEFT')
					->where(array('s.suppliers_id' => $suppliers_id))
					->find();
			$this->assign('info', $info);
		}
		$act = empty($suppliers_id) ? 'add' : 'edit';
		$this->assign('act', $act);
		$admin = Db::name('admin')->field('admin_id,user_name')->where('1=1')->select();
		$this->assign('admin', $admin);
		return $this->fetch();
	}

	/**
	 * 供应商增删改
	 */
	public function supplierHandle()
	{
		$data = I('post.');
		$suppliers_model = DB::name('suppliers');
		//增
		if ($data['act'] == 'add') {
			unset($data['suppliers_id']);
			$count = $suppliers_model->where("suppliers_name", $data['suppliers_name'])->count();
			if ($count) {
				$this->error("此供应商名称已被注册，请更换", Url::build('Admin/Admin/supplier_info'));
			} else {
				$r = $suppliers_model->insertGetId($data);
				if (!empty($data['admin_id'])) {
					$admin_data['suppliers_id'] = $r;
					Db::name('admin')->where(array('suppliers_id' => $admin_data['suppliers_id']))->save(array('suppliers_id' => 0));
					Db::name('admin')->where(array('admin_id' => $data['admin_id']))->save($admin_data);
				}
			}
		}
		//改
		if ($data['act'] == 'edit' && $data['suppliers_id'] > 0) {
			$r = $suppliers_model->where('suppliers_id',$data['suppliers_id'])->save($data);
			if (!empty($data['admin_id'])) {
				$admin_data['suppliers_id'] = $data['suppliers_id'];
				DB::name('admin')->where(array('suppliers_id' => $admin_data['suppliers_id']))->save(array('suppliers_id' => 0));
				DB::name('admin')->where(array('admin_id' => $data['admin_id']))->save($admin_data);
			}
		}
		//删
		if ($data['act'] == 'del' && $data['suppliers_id'] > 0) {
			$r = $suppliers_model->where('suppliers_id', $data['suppliers_id'])->delete();
			DB::name('admin')->where(array('suppliers_id' => $data['suppliers_id']))->save(array('suppliers_id' => 0));
		}

		if ($r !== false) {
			$this->success("操作成功", Url::build('Admin/Admin/supplier'));
		} else {
			$this->error("操作失败", Url::build('Admin/Admin/supplier'));
		}
	}
}