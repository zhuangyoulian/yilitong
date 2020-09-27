<?php
namespace ylt\admin\controller;
use think\Page;
use think\Db;
use think\Session;
use think\Request;
class Beviewedhandle extends Base
{
    public function Handle(Request $r)
    {
        if($r->isAjax()){
//            $return_arr=$_GET['submitVal1'];  //上面验证出了错误
//            $this->ajaxReturn($return_arr);DIE();
            $supplierId=$_POST['supplierId'];
//            $this->ajaxReturn($supplierId);DIE();
            $submitVal1=$_GET['submitVal1'];
            $submitVal=$_POST['submitVal'];
            Db::name('purchase')->where('id',$supplierId)->update(['be_viewed'=>$submitVal]);
        }
    }
}