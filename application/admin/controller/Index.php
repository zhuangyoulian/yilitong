<?php
/**
 * Created by PhpStorm.
 * User: lijiayi
 * Date: 2017/3/21
 * Time: 10:45
 */
namespace ylt\admin\controller; 
use think\AjaxPage;
use think\Controller;
use think\Url;
use think\Config;
use think\Page;
use think\Verify;
use think\Db;
use think\Request;
class Index extends Base {

    public function index(){
        $act_list = session('act_list');
        $menu_list = getMenuList($act_list); 
        $this->assign('menu_list',$menu_list);
        $admin_info = getAdminInfo(session('admin_id'));
        $order_amount = DB::name('order')->where("order_status=0 and pay_status=1 and supplier_id = 0")->count();
        $this->assign('order_amount',$order_amount);
        $this->assign('admin_info',$admin_info);
        $day = strtotime("-2 day"); 
        $this->assign('menu',getMenuArr());
        return $this->fetch();
    }
   
    public function welcome(){
    	/*$this->assign('sys_info',$this->get_sys_info());
    	$today = strtotime(date('Y-m-d', time()));
    	$count['users'] = DB::name('users')->cache(true,3600)->where("1=1")->count();//会员总数
    	$count['new_users'] = DB::name('users')->cache(true,3600)->where("reg_time>$today")->count();//新增会员
		$count['new_extension'] = DB::name('extension')->cache(true,3600)->where("add_time>$today")->count();//新增会员
		$count['extension'] = DB::name('extension')->cache(true,3600)->where("1=1")->count();//新增会员
    	$this->assign('count',$count);*/
        return $this->fetch();
    }
    
    public function get_sys_info(){
		$sys_info['os']             = PHP_OS;
		$sys_info['zlib']           = function_exists('gzclose') ? 'YES' : 'NO';//zlib
		$sys_info['safe_mode']      = (boolean) ini_get('safe_mode') ? 'YES' : 'NO';//safe_mode = Off		
		$sys_info['timezone']       = function_exists("date_default_timezone_get") ? date_default_timezone_get() : "no_timezone";
		$sys_info['curl']			= function_exists('curl_init') ? 'YES' : 'NO';	
		$sys_info['web_server']     = $_SERVER['SERVER_SOFTWARE'];
		$sys_info['phpv']           = phpversion();
		$sys_info['ip'] 			= GetHostByName($_SERVER['SERVER_NAME']);
		$sys_info['fileupload']     = @ini_get('file_uploads') ? ini_get('upload_max_filesize') :'unknown';
		$sys_info['max_ex_time'] 	= @ini_get("max_execution_time").'s'; //脚本最大执行时间
		$sys_info['set_time_limit'] = function_exists("set_time_limit") ? true : false;
		$sys_info['domain'] 		= $_SERVER['HTTP_HOST'];
		$sys_info['memory_limit']   = ini_get('memory_limit');	                                
        $sys_info['version']   	    = 1.0;
		$mysqlinfo = Db::query("SELECT VERSION() as version");
		$sys_info['mysql_version']  = $mysqlinfo[0]['version'];
		if(function_exists("gd_info")){
			$gd = gd_info();
			$sys_info['gdinfo'] 	= $gd['GD Version'];
		}else {
			$sys_info['gdinfo'] 	= "未知";
		}
		return $sys_info;
    }
    
 
    
    /**
     * ajax 修改指定表数据字段  一般修改状态 比如 是否推荐 是否开启 等 图标切换的
     * table,id_name,id_value,field,value
     */
    public function changeTableVal(){  
        $table      = I('table'); // 表名
        $id_name    = I('id_name'); // 表主键id名
        $id_value   = I('id_value'); // 表主键id值
        $field      = I('field'); // 修改哪个字段
        $value      = I('value'); // 修改字段值
        if ($field == 'is_distribution' and $value == 1) {   //分销的字段需要先判断该商品是否有佣金或成本价
            $a = Db::name($table)->where("$id_name = $id_value")->where('commission_price != 0 OR cost_price != 0')->find();
            if (!$a) {
                return array('status'=>5,'msg'=>'该商品没有设置佣金和成本价，无法分销');
            }
        }
        $b=Db::name($table)->where("$id_name = $id_value")->update(array($field=>$value,'last_update' => time())); // 根据条件保存修改的数据
        //红礼上架商品复制至一礼通
        if ($table == 'red_goods' and( $field == 'is_on_sale' OR  $field == 'examine' )and $value == 1) { 
            $goods = Db::name('red_goods')->where('goods_id',$id_value)->find();    //查询商品详情
            $goods_images = Db::name('red_goods_images')->where('goods_id',$id_value)->select();  //查询商品相册
            $yilitong_goodsid = Db::name('goods')->where('red_goods_id',$goods['goods_id'])->value('goods_id'); //查询一礼通是否存在该商品
            //商品必须先在红礼上架，才能上架一礼通
            if (empty($yilitong_goodsid) and $goods['is_on_sale']==1 and  $goods['examine']==1) {
                if ($goods['shop_price']<=0) {
                    Db::name($table)->where("$id_name = $id_value")->update(array($field=>0,'last_update' => time())); 
                    return array('status'=>5,'msg'=>'一件代发价不可为0');
                }
                if (count($goods_images) < 5) {
                    Db::name($table)->where("$id_name = $id_value")->update(array($field=>0,'last_update' => time())); 
                    return array('status'=>5,'msg'=>'上架一礼通的商品相册不可少于5张');
                }
                $goods['red_goods_id'] = $goods['goods_id'];
                $goods['goods_id'] = $yilitong_goodsid;
                $goods['is_on_sale']   = 1;     //上架
                $goods['is_recommend'] = 0;     //推荐
                $red = Db::name('goods')->insertGetId($goods);
                if ($goods_images ) {
                    foreach ($goods_images as $key => $value) {
                        $value['red_goods_id'] = $goods['red_goods_id'];
                        $value['goods_id'] = $red;
                        unset($value['img_id']);
                        DB::name('goods_images')->insert($value);
                    }
                }
            }else if(!empty($yilitong_goodsid) and $goods['is_on_sale']==1 and  $goods['examine']==1){
                if ($goods['shop_price']<=0) {
                    Db::name($table)->where("$id_name = $id_value")->update(array($field=>0,'last_update' => time())); 
                    return array('status'=>5,'msg'=>'一件代发价不可为0');
                }
                if (count($goods_images) < 5) {
                    Db::name($table)->where("$id_name = $id_value")->update(array($field=>0,'last_update' => time())); 
                    return array('status'=>5,'msg'=>'上架一礼通的商品相册不可少于5张');
                }
                $goods['red_goods_id'] = $goods['goods_id'];
                $goods['goods_id'] = $yilitong_goodsid;
                $goods['last_update'] = time();
                $goods['is_on_sale']   = 1;     //上架
                $red = Db::name('goods')->where('red_goods_id',$goods['red_goods_id'])->update($goods);
                if ($goods_images) {
                    Db::name('goods_images')->where('red_goods_id',$goods['red_goods_id'])->delete();
                    foreach ($goods_images as $key => $value) {
                        $value['red_goods_id'] = $goods['red_goods_id'];
                        $value['goods_id'] = $yilitong_goodsid;
                        unset($value['img_id']);
                        DB::name('goods_images')->insert($value);
                    }
                }
            }elseif($goods['examine']== 0){
                $red = Db::name('goods')->where("red_goods_id = $id_value")->update(array('is_on_sale'=>0,'last_update' => time())); 
                $red = Db::name('red_goods')->where('goods_id',$id_value)->update(array('is_on_sale'=>0)); 
                return array('status'=>5,'msg'=>'商品未在红礼上架');
            }
        }else if($table == 'red_goods' and( $field == 'is_on_sale' OR  $field == 'examine' )and $value == 0){
            $red = Db::name('goods')->where("red_goods_id = $id_value")->update(array('is_on_sale'=>0,'last_update' => time())); 
        }
        //红礼同步结束
        
        if ($red and $b) {
            return array('status'=>1,'msg'=>'上架同步成功');
        }elseif ($b) {
            return array('status'=>1,'msg'=>'更新成功');
        }
    }	

    /**
     * ajax 修改指定表数据字段  一般修改状态 比如 是否推荐 是否开启 等 图标切换的 修改时间戳
     * table,id_name,id_value,field,value
     */
    public function changeTableVal_s(){  
        $table      = I('table'); // 表名
        $id_name    = I('id_name'); // 表主键id名
        $id_value   = I('id_value'); // 表主键id值
        $field      = I('field'); // 修改哪个字段
        $value      = strtotime(I('value')); // 修改字段值
        Db::name($table)->where("$id_name = $id_value")->update(array($field=>$value,'last_update' => time())); // 根据条件保存修改的数据
    }
    
}