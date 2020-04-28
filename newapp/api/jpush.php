<?php
//define('IN_ECS', true);
//require('init.php');
$message=$_POST['message'];
$type=$_POST['type'];
$area=$_POST['area'];
$badge=!empty($_POST['badge']) ? $_POST['badge'] : 0;
$url_ad=$_POST['url_ad'];
$remark=$_POST['remark'];
if(empty($message) || empty($type) || "ylt_jpush"!=$remark){
	exit(false);
}
include_once '../includes/jpush/examples/push.php';
$jpush=new Push();
/* $message="测试极光推送5555";
$type="2";
$area="iosb7fcc3680acc24de7643ca3b9d24011f";
$badge='1';
$url_ad='56'; */
//type=1 跳转到首页，type=2商品详情页，type=3活动详情页
$rs=$jpush->sendJpush($message,$type,$area,$badge,$url_ad);
if($rs=='200'){
	exit(true);
}
exit(false);

