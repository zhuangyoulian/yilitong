<?php

/**
 * ECSHOP 加密解密类
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: lib_code.php 17217 2011-01-19 06:29:08Z liubo $
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}
include_once('lib_code.php');


function gen_token($appid,$appsecert){
	$time = time();
	$expir_time = $time+7200;
	$arr = array(
		'token'=>encrypt($time."|".$expir_time."|".$appid, 'chenx'),
		'expir_time'=>$expir_time
	);
	return $arr;
}

function check_token($token){
	$token_info = split_token($token);
	if(empty($token_info)){
		return false;
	}
	if($token_info['appid']=='ylt_android'){
		logapi(var_export($_REQUEST,true),1);
	}
	if($token_info['appid']=='ylt_ios'){
		logapi(var_export($_REQUEST,true),2);
	}
	if($token_info['appid']=='wms'){
		logapi(var_export($_REQUEST,true),3);
	}
	if(time()>$token_info['expir_time']){
		return false;
	}
	else{
		return true;
	}
}

function split_token($token){
	$strtoken = decrypt($token, 'chenx');
	$arr = explode ('|', $strtoken);
	
	$return_arr = array(
		'gen_time'=>$arr[0],
		'expir_time'=>$arr[1],
		'appid'=>$arr[2],
	);
	
	return $return_arr;

}

function get_appclient_list(){
	$data = read_static_cache('app_client_list');
	if ($data === false){
		$sql = "SELECT * from ".$GLOBALS['ecs']->table('app_auth');
		
		$list = $GLOBALS['db']->getALL($sql);
		write_static_cache('app_client_list', $list);
	}
	else{
		$list = $data;
	}
	
	
	return $list;
}

function gen_user_ticket($username,$userid,$token=''){
	return encrypt($username."|".$userid."|".time()."|".$token, 'chenx');
}

function split_user_ticket($ticket){
	$strtoken = decrypt($ticket, 'chenx');
	$arr = explode ('|', $strtoken);
	
	$return_arr = array(
		'username'=>$arr[0],
		'userid'=>$arr[1],
		'gen_time'=>$arr[2],
        'device_token'=>$arr[3],
	);

	return $return_arr;
}

function gen_checkcode($str){
	
	$time = time();
	$expir_time = $time+1200;//20分钟有效
	return encrypt($str."|".$time."|".$expir_time, 'chenx');
}

function split_checkcode_str($checkcode_str){
	$strtoken = decrypt($checkcode_str, 'chenx');
	$arr = explode ('|', $strtoken);
	
	$return_arr = array(
		'checkcode'=>$arr[0],
		'gen_time'=>$arr[1],
		'expir_time'=>$arr[2],
	);
	
	return $return_arr;
}

function gen_strnum(){
	$str = rand ( 10000, 99999);
	return $str;
}
?>