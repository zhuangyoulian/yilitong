<?php
define('IN_ECS', true);
require('init.php');

$affiliate = unserialize($GLOBALS['_CFG']['affiliate']);
header("Content-Type:text/html;charset=UTF-8");
$action  = $_REQUEST['act'];
$ticket = $_REQUEST['ticket'];
$userinfo = '';
if(!empty($ticket)){
	$userinfo = split_user_ticket($ticket);
}
//图片上传  file：回传过来的文件，带文件名  foloer：选择上传的文件夹，无值默认common（选填）
if ($action=="upload_img"){
	$file=$_FILES['name'];
	$folder=!empty($_REQUEST['folder']) ? $_REQUEST['folder'] : 'common';
	if(!empty($file))
	{
		$img_desc =$file;
		$upload_path = parse_path('public','upload',$folder,'image');
		include_once ROOT_PATH.'includes/Upload.php';
		$up = new \includes\Upload();
		$result = $up ->set_dir(dirname(ROOT_PATH)."/".$upload_path, "{y}/{m}")->execute();
		$result=$result['name'];
		if(!empty($result)){
			 $imgarr="/public/upload/".$folder."/image/".$result['name'];
			 $path=IMG_HOST.$imgarr;
			 if($result['flag']=='-1'){
			 	$results = array('result' => 0,'info' => '文件类型不允许');
			 	exit($json->json_encode_ex($results));
			 }
			 elseif($result['flag']=='-2'){
			 	$results = array('result' => 0,'info' => '文件过大');
			 	exit($json->json_encode_ex($results));
			 }
			elseif($result['flag']=='1'){
				$results = array('result' => 1,'info' => '上传成功','img_url'=>$imgarr,'path'=>$path);
				exit($json->json_encode_ex($results));
			}else{
				$results = array('result' => 0,'info' => '上传失败');
				exit($json->json_encode_ex($results));
			}
		}else{
			$results = array('result' => 0,'info' => '上传失败');
			exit($json->json_encode_ex($results));
		}
	}

}

//多张图片解析
function reArrayFiles($file)
{
	$file_ary = array();
	$file_count = count($file['name']);
	$file_key = array_keys($file);

	for($i=0;$i<$file_count;$i++)
	{
		foreach($file_key as $val)
		{
			$file_ary[$i][$val] = $file[$val][$i];
		}
	}
	return $file_ary;
}

function parse_path($array)
{
	if (!is_array($array)) {
		$array = func_get_args();
	}
	if ('\\' == DIRECTORY_SEPARATOR) {
		return implode('\\', $array);
	} else {
		return implode('/', $array);
	}
}
?>