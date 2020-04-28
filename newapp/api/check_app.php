<?php
/**
 * 检查服务器是否处于维护中或版本是否需要升级
 *
 *  author	zhh
 */
define('IN_ECS', true);
require('init.php');
header("Content-Type:text/html;charset=UTF-8");
$action  = $_REQUEST['act'];
//检查是否需要升级
if($action == 'check_ver')
{
	$ver = $_REQUEST['ver'];
	if(empty($ver)){
		$results = array(
			'result' => 1,
			'type'   => 0,
			'msg' => '版本号不能为空',
			'version'=>'',
			'url' =>'',
			);
		exit($json->json_encode_ex($results));
	}
	$token = $_REQUEST['token'];
	$token_info = split_token($token);
	$appid=$token_info['appid'];
    
	 if(!in_array((string)$appid, array('ylt_ios','ylt_android'))){
		$results = array(
			'result' => 1,
			'type'   => 0,
			'msg' => '客户端错误',
			'version'=>'',
			'url' =>'',
			);
		exit($json->json_encode_ex($results));
	}

	$sql = "select * from ".$GLOBALS['ecs']->table('app_version')." where app_name='".$appid."' order by id desc ";
	$check = $GLOBALS['db']->getRow($sql);
	if(empty($check)){

		$results = array(
			'result' => 1,
			'type'   => 0,
			'msg' => '未找到客户端',
			'version'=>'',
			'url' =>'',
			);
		exit($json->json_encode_ex($results));
	}
	if(!empty($check)){
		foreach ($check as $key => $value) {
			if(is_null($value)){
				$check[$key] = '';
			}
		}
	}

	if($check['version_number'] <= $ver){
		$results = array(
			'result' => 1,
			'type'   => 1,
			'msg'    => '已经是最新版',
			'version'=>$check['version_number'],
			'url'    => '',
			);
		exit($json->json_encode_ex($results));
	}
	//检测服务器是否维护中
	if($check['is_upgrade']>0){
		$results = array(
			'result' => 1,
			'type'   => 2,
			'msg'    => $check['upgrade_msg'],
			'version'=> '',
			'url'    => '',
			);
		exit($json->json_encode_ex($results));
	}
	//检测到新版本并且要求强制升级
	if($ver<$check['version_number'] && $check['is_update'] >0){
		$results = array(
			'result' => 1,
			'type'   => 3,
			'msg'    => $check['update_msg'],
			'version'=> $check['version_number'],
			'url'    => $check['url'],
			);
		exit($json->json_encode_ex($results));
	}
	//检测到新版本
	if($ver<$check['version_number'] && $check['is_update'] ==0){
		$results = array(
			'result' => 1,
			'type'   => 4,
			'msg'    => $check['update_msg'],
			'version'=> $check['version_number'],
			'url'    => $check['url'],
			);
		exit($json->json_encode_ex($results));
	}
}
//app 审核状态
if($action == 'examine')
{
	$version_number = $_REQUEST['version_number'];
	$appid = $_REQUEST['app_name'];
	
	$version_number = str_replace('.','',$version_number);
	
	
	$sql = "select * from ".$GLOBALS['ecs']->table('app_version')." where app_name='".$appid."' order by id desc ";
	$check = $GLOBALS['db']->getRow($sql);
	
	
	$number = str_replace('.','',$check['version_number']);
	
	if($version_number > $number){
		
		$results = array(
			'result' => 1,
			'type'   => 1,
			'msg' => '版本未更新',
			'version'=>$check['version_number'],
			'url' =>'',
			);
		exit($json->json_encode_ex($results));
		
		
	}
	
	$results = array(
			'result' => 1,
			'type'   => 0,
			'msg' => $check['update_msg'],
			'version'=>$check['version_number'],
			'url' =>$check['url'],
			);
		exit($json->json_encode_ex($results));
	
	
}

