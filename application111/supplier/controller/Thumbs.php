<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2017 http://www.sycit.cn
// +----------------------------------------------------------------------
// | Date:   2017/3/3
// +----------------------------------------------------------------------
// | Author: Peter.Zhang  <hyzwd@outlook.com>
// +----------------------------------------------------------------------
// | Title:  Thumbs.php
// +----------------------------------------------------------------------
namespace ylt\supplier\controller;

use think\Request;
use think\CropAvatar;

class Thumbs extends Base
{
    public function index() {
        //
  
        $request = Request::instance();
        if ($request->isPost()) {
        	
            //上传前先判断文件是否有错误
            if ($_FILES['avatar_file']['error'] !== 0) {
                $response = array('state' => 200,'message' => '文件过大或格式不对');
            } else {
                $options = $request->param('options');
                if ($options == 'cope') {
                    //裁剪操作，传入参数顺序不能乱
                    $crop = new CropAvatar(
                    	isset($_POST['img_type']) ? $_POST['img_type'] : 'goods',
                        isset($_POST['avatar_src']) ? $_POST['avatar_src'] : null,
                        isset($_POST['avatar_data']) ? $_POST['avatar_data'] : null,
                        isset($_FILES['avatar_file']) ? $_FILES['avatar_file'] : null
                    );
                    //返回结果
                    $response = array(
                        'state'  => 23,
                        'message' => $crop -> getMsg(),
                        'result' => $crop -> getResult(),
                    	'result1' => $crop -> getResult1()
                    );
                    //删除裁剪的原图目录文件
                    removeDir(Config('syc_images.original') . '/');
                } elseif ($options == 'not_cut') {
                    //不裁剪操作
                    $file = $request->file('avatar_file');
                    $filename = Config('syc_images.original');
                    //验证
                    $info = $file->validate(['size'=>Config('syc_images.size'),'ext'=>'jpg,png,gif'])->move($filename);
                    if ($info) {
                        $msg = '上传成功';
                        $result = ltrim($filename, ".") . '/' . date('Ymd') . '/' . $info->getFilename();
                    } else {
                        $msg = '原图片过大或格式不对';
                        $result = '';
                    }
                    $response = array(
                        'state'  => 32,
                        'message' => $msg,
                        'result' => $result
                    );
                }
            }
            //输出
            echo json_encode($response);  
        } else {
            return json('No data found!');
        }
    }
}
