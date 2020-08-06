<?php
/**
 * Created by PhpStorm.
 * User: lijiayi
 * Date: 2017/3/24
 * Time: 14:45
 */
namespace ylt\mobile\controller;
use think\Controller;
use think\Url;
use think\Db;
class Caseindex extends controller {

	
	/**
	 *  安居
	 */
	 public function Housing(){
       
        return $this->fetch();
    }
	
	/**
	 * 旅行
	 */
	 public function travel(){
       
        return $this->fetch();
    }

    /**
     * 文创
     */
	 public function winchance(){
      
        return $this->fetch();
    }

    /**
     * 学堂
     */
	 public function school(){
       
        return $this->fetch();
    }

    /**
     * 生活
     */
	 public function life(){
       
        return $this->fetch();
    }

    /**
     * 金融
     */
	 public function finance(){
      
        return $this->fetch();
    }

    /**
     * 海淘
     */
	 public function seaAmoy(){
      
        return $this->fetch();
    }



    /**
     * 中秋活动专题
     */
     public function mid_autumn(){
		 $app = $_GET['app'];
		$this->assign('app',$app);
        return $this->fetch();
    }



    /**
     * 中秋---月中活动
     */
     public function mid_month(){

     $start_time = strtotime(date('Y-m-d', time()));
     $end_time ='' ;

        return $this->fetch();
    }



    /**
     * 满减
     */
    public function full_cut(){

        return $this->fetch();
    }

    /**
     * 城市代理人加盟
     */
    public function jiameng(){

        return $this->fetch();
    }


	

	
}