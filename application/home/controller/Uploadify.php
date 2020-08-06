<?php
namespace ylt\home\controller;
use think\Url;
use think\Request;

class Uploadify extends Base{

	public function upload(){
		$func = I('func');
		$path = I('path','temp');
		$info = array(
			'num'=> I('num'),
			'title' => '',
			'upload' =>Url::build('Home/Ueditor/imageUp',array('savepath'=>$path,'pictitle'=>'banner','dir'=>'logo')),
			'size' => '1.5M',
			'type' =>'jpg,png,jpeg',
			'input' => I('input'),
			'func' => empty($func) ? 'undefined' : $func,
		);
		$this->assign('info',$info);
		return $this->fetch();
	}
	
	/*
	 删除上传的图片
	*/
	public function delupload(){
		$action = I('action');
        $filename= I('filename');
		$filename= str_replace('../','',$filename);
		$filename= trim($filename,'.');
		$filename= trim($filename,'/');
		if($action=='del' && !empty($filename)){
			$size = getimagesize($filename);
			$filetype = explode('/',$size['mime']);
			if($filetype[0]!='image'){
				return false;
				exit;
			}
			unlink($filename);
			exit;
		}
	}    
}