<?php
/**
 * 面包屑导航  用于前台用户中心
 * 根据当前的控制器名称 和 action 方法
 */
use think\Db;
use think\Url;
function navigate_user()
{    
    $navigate = include APP_PATH.'home/navigate.php';    
    $location = strtolower('Home/'.CONTROLLER_NAME);
    $arr = array(
        '首页'=>'/',
        $navigate[$location]['name']=>Url::build('/Home/'.CONTROLLER_NAME),
        $navigate[$location]['action'][ACTION_NAME]=>'javascript:void();',
    );
    return $arr;
}

/**
*  面包屑导航  用于前台商品
 * @param type $id 商品id  或者是 商品分类id
 * @param type $type 默认0是传递商品分类id  id 也可以传递 商品id type则为1
 */
function navigate_goods($id,$type = 0)
{
    $cat_id = $id; //
    // 如果传递过来的是
    if($type == 1){
        $cat_id = Db::name('goods')->where("goods_id", $id)->getField('cat_id');
    }
    $categoryList = Db::name('GoodsCategory')->getField("id,name,parent_id");

    // 第一个先装起来
    $arr[$cat_id] = $categoryList[$cat_id]['name'];
    while (true)
    {
        $cat_id = $categoryList[$cat_id]['parent_id'];
        if($cat_id > 0)
            $arr[$cat_id] = $categoryList[$cat_id]['name'];
        else
            break;
    }
    $arr = array_reverse($arr,true);
    return $arr;
}

function navigate_scenario($id,$type = 0)
{
    $cat_id = $id; //
    // 如果传递过来的是
    if($type == 1){
        $cat_id = Db::name('goods')->where("goods_id", $id)->getField('extend_cat_id');
    }
    $categoryList = Db::name('ScenarioCategory')->getField("id,name,parent_id");

    // 第一个先装起来
    $arr[$cat_id] = $categoryList[$cat_id]['name'];
    while (true)
    {
        $cat_id = $categoryList[$cat_id]['parent_id'];
        if($cat_id > 0)
            $arr[$cat_id] = $categoryList[$cat_id]['name'];
        else
            break;
    }
    $arr = array_reverse($arr,true);
    return $arr;
}

/**
 * 转换字节
 * @param $bytes 传入字节数值
 * @param int $decimals
 * @return string BKMGTP
 */
function human_filesize($bytes, $decimals = 2) {
	$sz = 'BKMGTP';
	$factor = floor((strlen($bytes) - 1) / 3);
	return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
}
/**
 * 删除该目录以及该目录下面的所有文件和文件夹
 * @param $dir 目录
 * @return bool
 */
function removeDir($dirName) {
	//判断传入参数是否目录，如不是执行删除文件
	if (!is_dir($dirName)) {
		//删除文件
		@unlink($dirName);
	}
	//如果传入是目录，使用@opendir将该目录打开，将返回的句柄赋值给$handle
	$handle = @opendir($dirName);
	//这里明确地测试返回值是否全等于（值和类型都相同）FALSE
	//否则任何目录项的名称求值为 FALSE 的都会导致循环停止（例如一个目录名为“0”）
	while (($file = @readdir($handle)) !== false) {
		//在文件结构中，都会包含形如“.”和“..”的向上结构
		//但是它们不是文件或者文件夹
		if ($file != '.' && $file != '..') {
			//当前文件$dir为文件目录+文件
			$dir = $dirName . '/' .$file;
			//判断$dir是否为目录，如果是目录则递归调用reMoveDir($dirName)函数
			//将其中的文件和目录都删除；如果不是目录，则删除该文件
			is_dir($dir) ? removeDir($dir) : @unlink($dir);
		}
	}
	closedir($handle);
	return rmdir($dirName);
}


 