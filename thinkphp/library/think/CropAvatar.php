<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2017 http://www.sycit.cn
// +----------------------------------------------------------------------
// | Date:   2017/3/14
// +----------------------------------------------------------------------
// | Author: Peter.Zhang  <hyzwd@outlook.com>
// +----------------------------------------------------------------------
// | Title:  CropAvatar.php
// +----------------------------------------------------------------------
namespace Think;

class CropAvatar
{
    //图片地址
    private $src;
    //传入的JSON参数
    private $data;
    //保存裁剪目录
    private $dst;
    private $type;
    private $extension;
    private $msg;

    public function __construct($img_type,$src, $data, $file) {
    	
    	if($img_type=="works"){
	    	$width = 560;
	    	$height = 360;
	    	$minwidth = 280;
	    	$minheight = 180;
    	}else{
    		$width = 800;
    		$height = 800;
    		$minwidth = 300;
    		$minheight = 300;
    	}
    	
        $this -> setSrc($img_type,$src);
        $this -> setData($data);
        $this -> setFile($file);
        $this -> crop($this -> src, $this -> dst, $this -> data, $width, $height);
        $this -> crop($this -> src, $this -> dst1, $this -> data, $minwidth, $minheight);
    }

    private function setSrc($img_type,$src) {
    	
    	if($img_type=="goods"){
    		$this -> folder = "./public/upload/goods/images";
    		$this -> folder1 = "./public/upload/goods/thumbs";
    	}
   		elseif($img_type=="works"){
    		$this -> folder = "./public/upload/works/images";
    		$this -> folder1 = "./public/upload/works/thumbs";
    	}else{
    		$this -> folder = "./public/upload/images";
    		$this -> folder1 = "./public/upload/thumbs";
    	}
        if (!empty($src)) {
            $type = exif_imagetype($src);
            if ($type) {
                $this -> src = $src;
                $this -> type = $type;
                $this -> extension = image_type_to_extension($type);
                $this -> setDst();
            }
        }
    }

    private function setData($data) {
        if (!empty($data)) {
            $this -> data = json_decode(stripslashes($data));
        }
    }

    /**
     * 检查目录是否可写
     * @param  string   $path    目录
     * @return boolean
     */
    protected function checkPath($path)
    {
        if (is_dir($path)) {
            return true;
        }

        if (mkdir($path, 0755, true)) {
            return true;
        } else {
            $this->msg = "目录 {$path} 创建失败！";
            return false;
        }
    }

    private function setFile($file) {
        $errorCode = $file['error'];

        if ($errorCode === UPLOAD_ERR_OK) {
            //判断图片大小
            if (filesize($file['tmp_name']) > Config('syc_images.size')) {
                $this->msg = '上传图片过大 限制在' . human_filesize(Config('syc_images.size'));
            }
            //判断一个图像的类型
            if(!function_exists('exif_imagetype')){
                function exif_imagetype($filename){
                    if((list($width,$height,$type,$attr) = getimagesize($filename)) !== false ){
                                return $type;
                            }
                            return false;
                    }
            }
            $type = exif_imagetype($file['tmp_name']);
            //$type = exif_imagetype($file['tmp_name']);
            if ($type) {
                //获取图片格式
                $extension = image_type_to_extension($type);
                //原图保存位置
                $filename = Config('syc_images.original');
                $src = $filename . '/' . date('YmdHis') . $this->createRandomCode(6) . $extension;
                // 检测目录
                if (false === $this->checkPath(dirname($src))) {
                    return false;
                }
                //判断图片类型
                if ($type == IMAGETYPE_GIF || $type == IMAGETYPE_JPEG || $type == IMAGETYPE_PNG) {
                    if (file_exists($src)) {
                        unlink($src);
                    }
                    //移动保存图片
                    $result = move_uploaded_file($file['tmp_name'], $src);
                    //p($src);
                   // exit;
                    if ($result) {
                        $this -> src = $src;
                        $this -> type = $type;
                        $this -> extension = $extension;
                        $this -> setDst();                                                                                                      
                    } else {
                        $this -> msg = '无法保存文件';
                    }
                } else {
                    $this -> msg = '请与以下类型上传图像: JPG, PNG, GIF';
                }
            } else {
                $this -> msg = '请上传图像文件';
            }
        } else {
            $this -> msg = $this -> codeToMessage($errorCode);
        }
    }

    //裁剪图片目录
    private function setDst() {
      
        $this -> dst = $this -> folder . '/' . date('Ymd') . '/' . time() . $this->createRandomCode(6) . '.jpg';
      
        $this -> dst1 = $this -> folder1 . '/' . date('Ymd') . '/' . time() . $this->createRandomCode(6) . '.jpg';
        // 检测目录
        if (false === $this->checkPath(dirname($this -> dst))) {
            return false;
        }
        if (false === $this->checkPath(dirname($this -> dst1))) {
        	return false;
        }
    }

    //开始裁剪图片
    private function crop($src, $dst, $data,$width,$height) {
		
        if (!empty($src) && !empty($dst) && !empty($data)) {
            switch ($this -> type) {
                case IMAGETYPE_GIF:
                    $src_img = imagecreatefromgif($src);
                    break;

                case IMAGETYPE_JPEG:
                    $src_img = imagecreatefromjpeg($src);
                    break;

                case IMAGETYPE_PNG:
                    $src_img = imagecreatefrompng($src);
                    break;
            }

            if (!$src_img) {
                $this -> msg = "无法读取图像文件";
                return;
            }

            $size = getimagesize($src);
            $size_w = $size[0]; // natural width
            $size_h = $size[1]; // natural height

            $src_img_w = $size_w;
            $src_img_h = $size_h;

            $degrees = $data -> rotate;

            // Rotate the source image
            if (is_numeric($degrees) && $degrees != 0) {
                // PHP's degrees is opposite to CSS's degrees
                $new_img = imagerotate( $src_img, -$degrees, imagecolorallocatealpha($src_img, 0, 0, 0, 127) );

                imagedestroy($src_img);
                $src_img = $new_img;

                $deg = abs($degrees) % 180;
                $arc = ($deg > 90 ? (180 - $deg) : $deg) * M_PI / 180;

                $src_img_w = $size_w * cos($arc) + $size_h * sin($arc);
                $src_img_h = $size_w * sin($arc) + $size_h * cos($arc);

                // Fix rotated image miss 1px issue when degrees < 0
                $src_img_w -= 1;
                $src_img_h -= 1;
            }

            $tmp_img_w = $data -> width;
            $tmp_img_h = $data -> height;
            $dst_img_w = $width;
            $dst_img_h = $height;

            $src_x = $data -> x;
            $src_y = $data -> y;

            if ($src_x <= -$tmp_img_w || $src_x > $src_img_w) {
                $src_x = $src_w = $dst_x = $dst_w = 0;
            } else if ($src_x <= 0) {
                $dst_x = -$src_x;
                $src_x = 0;
                $src_w = $dst_w = min($src_img_w, $tmp_img_w + $src_x);
            } else if ($src_x <= $src_img_w) {
                $dst_x = 0;
                $src_w = $dst_w = min($tmp_img_w, $src_img_w - $src_x);
            }

            if ($src_w <= 0 || $src_y <= -$tmp_img_h || $src_y > $src_img_h) {
                $src_y = $src_h = $dst_y = $dst_h = 0;
            } else if ($src_y <= 0) {
                $dst_y = -$src_y;
                $src_y = 0;
                $src_h = $dst_h = min($src_img_h, $tmp_img_h + $src_y);
            } else if ($src_y <= $src_img_h) {
                $dst_y = 0;
                $src_h = $dst_h = min($tmp_img_h, $src_img_h - $src_y);
            }

            // Scale to destination position and size
            $ratio = $tmp_img_w / $dst_img_w;
            $dst_x /= $ratio;
            $dst_y /= $ratio;
            $dst_w /= $ratio;
            $dst_h /= $ratio;

            $dst_img = imagecreatetruecolor($dst_img_w, $dst_img_h);
            // Add transparent background to destination image
            imagefill($dst_img, 0, 0, imagecolorallocatealpha($dst_img, 0, 0, 0, 127));
            imagesavealpha($dst_img, true);

            $result = imagecopyresampled($dst_img, $src_img, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);

            if ($result) {
                if (!imagepng($dst_img, $dst)) {
                    $this -> msg = "未能保存裁剪的图像文件";
                }
            } else {
                $this -> msg = "无法裁剪图像文件";
            }

            imagedestroy($src_img);
            imagedestroy($dst_img);
            
            $this -> msg = '上传成功';
        }
    }

    //返回的PHP原型错误
    private function codeToMessage($code) {
        $errors = array(
            UPLOAD_ERR_INI_SIZE =>'上传的文件在php.ini中超过upload_max_filesize指令',
            UPLOAD_ERR_FORM_SIZE =>'上传的文件超过max_file_size指令是在HTML表单中指定的',
            UPLOAD_ERR_PARTIAL =>'上传的文件只是部分上传',
            UPLOAD_ERR_NO_FILE =>'没有上传文件',
            UPLOAD_ERR_NO_TMP_DIR =>'缺少临时文件夹',
            UPLOAD_ERR_CANT_WRITE =>'无法将文件写入磁盘',
            UPLOAD_ERR_EXTENSION =>'文件上传扩展停止',
        );

        if (array_key_exists($code, $errors)) {
            return $errors[$code];
        }

        return '未知上传错误';
    }

    //输出裁剪过的图片路径，删除字符串第一个字符 ltrim($this -> dst, ".")
    public function getResult() {
        return !empty($this -> data) ? ltrim($this -> dst, ".") : ltrim($this -> src, ".");
    }
    public function getResult1() {
    	return !empty($this -> data) ? ltrim($this -> dst1, ".") : ltrim($this -> src, ".");
    }

    //返回信息
    public function getMsg() {
        return $this -> msg;
    }
    
    function createRandomCode($len)
    {
    	$chars = array(
    			"a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
    			"l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
    			"w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G",
    			"H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
    			"S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2",
    			"3", "4", "5", "6", "7", "8", "9"
    	);
    	$charsLen = count($chars) - 1;
    	shuffle($chars);
    	$output = "";
    	for ($i=0; $i<$len; $i++)
    	{
    		$output .= $chars[mt_rand(0, $charsLen)];
    	}
    	return $output;
    }

}
//用法
// $crop = new CropAvatar(
//    isset($_POST['avatar_src']) ? $_POST['avatar_src'] : null,
//    isset($_POST['avatar_data']) ? $_POST['avatar_data'] : null,
//    isset($_FILES['avatar_file']) ? $_FILES['avatar_file'] : null
// );

// $response = array(
//    'state'  => 200,
//    'message' => $crop -> getMsg(),
//    'result' => $crop -> getResult()
// );

// echo json_encode($response); die(12);