<?php

namespace includes;
//-------------------------------------
// 文件说明：文件上传处理类
// 文件作者：alan.wang@youkastation.com
//-------------------------------------
class Upload
{
    private $dir; //附件存放物理目录
    private $time; //自定义文件上传时间
    private $allow_types; //允许上传附件类型
    private $field; //上传控件名称
    private $maxsize; //最大允许文件大小，单位为KB
    private $thumb_width; //缩略图宽度
    private $thumb_height; //缩略图高度
    private $watermark_file; //水印图片地址
    private $watermark_pos; //水印位置
    private $watermark_trans;//水印透明度
    private $rand_dir; //随机目录


    /**
     * upload constructor.
     * @param string $types 允许上传的文件类型
     * @param int $maxsize 允许大小
     * @param string $field 上传控件名称
     * @param string $time 自定义上传时间
     */
    public function __construct($types = 'jpg|png', $maxsize = 1024, $field = 'attach', $time = '')
    {
        $this->allow_types = explode('|', $types);
        $this->maxsize = $maxsize * 5120;
        $this->field = $field;
        $this->time = $time ? $time : time();
    }

    /**
     * 设置并创建文件具体存放的目录
     * @param string $basedir 基目录，必须为物理路径
     * @param string $file_dir 自定义子目录，可用参数{y}、{m}、{d}
     * @return $this
     */
    public function set_dir($basedir, $file_dir = '')
    {
        $dir = rtrim($basedir,'/').'/';
        if (!empty($file_dir)) {
            $file_dir = str_replace(array('{y}', '{m}', '{d}'), array(date('Y', $this->time), date('m', $this->time), date('d', $this->time)), strtolower($file_dir));
            $this->rand_dir = $file_dir;
            $dirs = explode('/', $file_dir);
            foreach ($dirs as $d) {
                !empty($d) && $dir .= $d . '/';
            }
            !is_dir($dir) && @mkdir($dir, 0755, true);
        }
        $this->dir = $dir;
        return $this;
    }

    /**
     *图片缩略图设置，如果不生成缩略图则不用设置
     * @param int $width 缩略图宽度
     * @param int $height 缩略图高度
     * @return $this
     */
    public function set_thumb($width = 0, $height = 0)
    {
        $this->thumb_width = $width;
        $this->thumb_height = $height;
        return $this;
    }

    /**
     * 图片水印设置，如果不生成添加水印则不用设置
     * @param string $file 水印图片
     * @param int $pos 水印位置
     * @param int $trans 水印透明度
     * @return $this
     */
    public function set_watermark($file, $pos = 6, $trans = 80)
    {
        $this->watermark_file = $file;
        $this->watermark_pos = $pos;
        $this->watermark_trans = $trans;
        return $this;
    }


    /**
     * 执行文件上传，处理完返回一个包含上传成功或失败的文件信息数组，
     * name 为文件名，上传成功时是上传到服务器上的文件名，上传失败则是本地的文件名
     * dir 为服务器上存放该附件的物理路径，上传失败不存在该值
     * size 为附件大小，上传失败不存在该值
     * flag 为状态标识，1表示成功，-1表示文件类型不允许，-2表示文件大小超出
     * @return array
     */
    public function execute()
    {
    	
        if (empty($_FILES)) return;
        $files = array(); //成功上传的文件信息
        foreach ($_FILES as $key => $file_info) {
        	if (!$file_info) continue;
            $file_ext = $this->get_file_ext($file_info['name']); //获取文件扩展名
           
            $file_name = $this->time . mt_rand(100, 999) . '.' . $file_ext; //生成文件名
            $file_dir = $this->dir; //附件实际存放目录
            $file_size = $file_info['size']; //文件大小

            //文件类型不允许
            if (!in_array($file_ext, $this->allow_types)) {
                $files[$key]['name'] = $_FILES[$key]['name'];
                $files[$key]['flag'] = -1;
                continue;
            }
            //文件大小超出
            if ($file_size > $this->maxsize) {
                $files[$key]['name'] = $file_info['name'];
                $files[$key]['flag'] = -2;
                continue;
            }
            $files[$key]['name'] = $this->rand_dir.'/'.$file_name;
            $files[$key]['dir'] = $file_dir;
            $files[$key]['size'] = $file_size;
            //保存上传文件并删除临时文件
            if (is_uploaded_file($file_info['tmp_name'])) {
            	
               $flag= move_uploaded_file($file_info['tmp_name'], $file_dir . $file_name);
                @unlink($_FILES[$key]['tmp_name']);
                $files[$key]['flag'] = 1;
                //对图片进行加水印和生成缩略图
                if (in_array($file_ext, array('jpg', 'png', 'gif'))) {
                    if ($this->thumb_width) {
                        if ($this->create_thumb($file_dir . $file_name, $file_dir . 'thumb_' . $file_name)) {
                            $files[$key]['thumb'] = 'thumb_' . $file_name; //缩略图文件名
                        }
                    }
                    $this->create_watermark($file_dir . $file_name);
                }
            }
        }
        return $files;
    }

	//处理多张图片  
    public function  upload_exe($img_desc){
    	$files = array(); //成功上传的文件信息
    	foreach ($img_desc as $key => $file_info) {
    		 
    		if (!$file_info['name']) continue;
    		$file_ext = $this->get_file_ext($file_info['name']); //获取文件扩展名
    		$file_name = $this->time . mt_rand(100, 999) . '.' . $file_ext; //生成文件名
    		$file_dir = $this->dir; //附件实际存放目录
    		$file_size = $file_info['size']; //文件大小
    		//文件类型不允许
    		if (!in_array($file_ext, $this->allow_types)) {
    			$files[$key]['name'] = $_FILES[$key]['name'];
    			$files[$key]['flag'] = -1;
    			continue;
    		}
    		
    		//文件大小超出
    		if ($file_size > $this->maxsize) {
    			$files[$key]['name'] = $file_info['name'];
    			$files[$key]['flag'] = -2;
    			continue;
    		}
    		$files[$key]['name'] = $this->rand_dir.'/'.$file_name;
    		$files[$key]['dir'] = $file_dir;
    		$files[$key]['size'] = $file_size;
    		//保存上传文件并删除临时文件
    		if (is_uploaded_file($file_info['tmp_name'])) {
    			
    			move_uploaded_file($file_info['tmp_name'], $file_dir . $file_name);
				@chmod($file_dir . $file_name,0755);
    			@unlink($_FILES[$key]['tmp_name']);
    			$files[$key]['flag'] = 1;
    			//对图片进行加水印和生成缩略图
    			if (in_array($file_ext, array('jpg', 'png', 'gif'))) {
    				if ($this->thumb_width) {
    					if ($this->create_thumb($file_dir . $file_name, $file_dir . 'thumb_' . $file_name)) {
    						$files[$key]['thumb'] = 'thumb_' . $file_name; //缩略图文件名
    					}
    				}
    				$this->create_watermark($file_dir . $file_name);
    			}
    		}
    	}
    	return $files;
    }
    
    
    /**
     * 创建缩略图,以相同的扩展名生成缩略图
     * @param string $source_file 来源图像路径
     * @param string $thumb_file 缩略图路径
     * @return bool
     */
    private function create_thumb($source_file, $thumb_file)
    {
        $t_width = $this->thumb_width;
        $t_height = $this->thumb_height;
        if (!file_exists($source_file)) return false;
        $source_file_size = getImageSize($source_file);
        //如果来源图像小于或等于缩略图则拷贝源图像作为缩略图
        if ($source_file_size[0] <= $t_width && $source_file_size[1] <= $t_height) {
            if (!copy($source_file, $thumb_file)) {
                return false;
            }

            return true;
        }
        //按比例计算缩略图大小
        if ($source_file_size[0] - $t_width > $source_file_size[1] - $t_height) {
            $t_height = ($t_width / $source_file_size[0]) * $source_file_size[1];
        } else {
            $t_width = ($t_height / $source_file_size[1]) * $source_file_size[0];
        }
        //取得文件扩展名
        $file_ext = $this->get_file_ext($source_file);
        switch ($file_ext) {
            case 'jpg' :
                $tmp_img = ImageCreateFromJPEG($source_file);
                break;
            case 'png' :
                $tmp_img = ImageCreateFromPNG($source_file);
                break;
            case 'gif' :
                $tmp_img = ImageCreateFromGIF($source_file);
                break;
            default:
                $tmp_img = ImageCreateFromJPEG($source_file);

        }
        //创建一个真彩色的缩略图像
        $thumb_img = @ImageCreateTrueColor($t_width, $t_height);
        //ImageCopyResampled函数拷贝的图像平滑度较好，优先考虑
        if (function_exists('imagecopyresampled')) {
            @ImageCopyResampled($thumb_img, $tmp_img, 0, 0, 0, 0, $t_width, $t_height, $source_file_size[0], $source_file_size[1]);
        } else {
            @ImageCopyResized($thumb_img, $tmp_img, 0, 0, 0, 0, $t_width, $t_height, $source_file_size[0], $source_file_size[1]);
        }
        //生成缩略图
        switch ($file_ext) {
            case 'jpg' :
                ImageJPEG($thumb_img, $thumb_file);
                break;
            case 'gif' :
                ImageGIF($thumb_img, $thumb_file);
                break;
            case 'png' :
                ImagePNG($thumb_img, $thumb_file);
                break;
        }
        //销毁临时图像
        @ImageDestroy($tmp_img);
        @ImageDestroy($thumb_img);
        return true;
    }

    /**
     * 为图片添加水印
     * @param string $file 要添加水印的文件
     */
    private function create_watermark($file)
    {
        //文件不存在则返回
        if (!file_exists($this->watermark_file) || !file_exists($file)) return;
        if (!function_exists('getImageSize')) return;

        //检查GD支持的文件类型
        $gd_allow_types = array();
        if (function_exists('ImageCreateFromGIF')) $gd_allow_types['image/gif'] = 'ImageCreateFromGIF';
        if (function_exists('ImageCreateFromPNG')) $gd_allow_types['image/png'] = 'ImageCreateFromPNG';
        if (function_exists('ImageCreateFromJPEG')) $gd_allow_types['image/jpeg'] = 'ImageCreateFromJPEG';
        //获取文件信息
        $file_info = getImageSize($file);
        $wm_info = getImageSize($this->watermark_file);
        if ($file_info[0] < $wm_info[0] || $file_info[1] < $wm_info[1]) return;
        if (array_key_exists($file_info['mime'], $gd_allow_types)) {
            if (array_key_exists($wm_info['mime'], $gd_allow_types)) {

                //从文件创建图像
                $temp = $gd_allow_types[$file_info['mime']]($file);
                $temp_wm = $gd_allow_types[$wm_info['mime']]($this->watermark_file);
                //水印位置
                switch ($this->watermark_pos) {
                    case 1 : //顶部居左
                        $dst_x = 0;
                        $dst_y = 0;
                        break;
                    case 2 : //顶部居中
                        $dst_x = ($file_info[0] - $wm_info[0]) / 2;
                        $dst_y = 0;
                        break;
                    case 3 : //顶部居右
                        $dst_x = $file_info[0];
                        $dst_y = 0;
                        break;
                    case 4 : //底部居左
                        $dst_x = 0;
                        $dst_y = $file_info[1];
                        break;
                    case 5 : //底部居中
                        $dst_x = ($file_info[0] - $wm_info[0]) / 2;
                        $dst_y = $file_info[1];
                        break;
                    case 6 : //底部居右
                        $dst_x = $file_info[0] - $wm_info[0];
                        $dst_y = $file_info[1] - $wm_info[1];
                        break;
                    default : //随机
                        $dst_x = mt_rand(0, $file_info[0] - $wm_info[0]);
                        $dst_y = mt_rand(0, $file_info[1] - $wm_info[1]);
                }
                if (function_exists('ImageAlphaBlending')) ImageAlphaBlending($temp_wm, True); //设定图像的混色模式
                if (function_exists('ImageSaveAlpha')) ImageSaveAlpha($temp_wm, True); //保存完整的 alpha 通道信息
                //为图像添加水印
                if (function_exists('imageCopyMerge')) {
                    ImageCopyMerge($temp, $temp_wm, $dst_x, $dst_y, 0, 0, $wm_info[0], $wm_info[1], $this->watermark_trans);
                } else {
                    ImageCopyMerge($temp, $temp_wm, $dst_x, $dst_y, 0, 0, $wm_info[0], $wm_info[1]);
                }
                //保存图片
                switch ($file_info['mime']) {
                    case 'image/jpeg' :
                        @imageJPEG($temp, $file);
                        break;
                    case 'image/png' :
                        @imagePNG($temp, $file);
                        break;
                    case 'image/gif' :
                        @imageGIF($temp, $file);
                        break;
                }
                //销毁零时图像
                @imageDestroy($temp);
                @imageDestroy($temp_wm);
            }
        }
    }

    /**
     * 获取文件扩展名
     * @param $filename
     * @return string
     */
    private function get_file_ext($filename)
    {
        return strtolower(substr(strrchr($filename, '.'), 1, 10));
    }
}

