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
use think\Db;
use think\Cache;
class Activity extends Base {
	
    /**
     * 满减活动页面
     */
  //   public function full_cut(){

		// $Cache = md5($_SERVER['REQUEST_URI']);
		// $html =  Cache::get($Cache);  //读取缓存
		// if(!empty($html))
		// 	return $html;

		// $time = time();

		// $prom = Db::name('prom_goods')->where('end_time','>',$time)->select();

		//  foreach($prom as $k=>$v)
  //       {
  //           $prom[$k]['goods_list'] = Db::name('goods')->where('goods_id','in',$v['goods_ids'])->select();
		// }

		// $this->assign('prom',$prom);  // 筛选菜单

		// $html = $this->fetch();
  //       Cache::set($Cache,$html,3600);
  //       return $html;
  //   }


    /**
     *  招商活动页面
     */
    // public function zhaoshang(){

    //     return $this->fetch();
    // }
}