<?php
/**
 * Created by PhpStorm.
 * User: lijiayi
 * Date: 2017/3/27
 * Time: 10:30
 */
namespace ylt\home\controller;

use think\Request;
use think\Db;
class Viewedauto extends Base
{
    public function Handle(Request $r)
    {
        if($r->isAjax()){
//            $return_arr=$_GET['submitVal1'];  //上面验证出了错误
//            $this->ajaxReturn($return_arr);DIE();
            $intCount=$_POST['intCount'];
            $supplierId=$_POST['supplierId'];
            $result=Db::name('purchase')->field('be_viewed')->where('id',$supplierId)->find();
            foreach($result as $value){
                $value+=$intCount;
                Db::name('purchase')->where('id',$supplierId)->update(['be_viewed'=>$value]);
            }
        }
    }
  	public function HandleTwo(Request $r)
    {
        if($r->isAjax()){
//            $return_arr=$_GET['submitVal1'];  //上面验证出了错误
//            $this->ajaxReturn($return_arr);DIE();
            $intCount=$_POST['intCount'];
            $supplierId=$_POST['supplierId'];
            $result=Db::name('purchase')->field('be_viewed')->where('id',$supplierId)->find();
            foreach($result as $value){
                $value+=$intCount;
                Db::name('purchase')->where('id',$supplierId)->update(['be_viewed'=>$value]);
            }
        }
    }
}