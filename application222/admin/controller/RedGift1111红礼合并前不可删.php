<?php
namespace ylt\admin\controller;
use think\AjaxPage;
use think\Page;
use think\Verify;
use think\Db;
use think\Session;
use think\Url;
use think\Request;
use ylt\admin\logic\GoodsLogic;
use ylt\admin\logic\OrderLogic;

class RedGift extends Base{
    /**
     * [a_red_list 商品列表]
     * @return [type] [description]
     */
    public function a_red_list(){
        //     truncate table ylt_red_cart   清空表
        $GoodsLogic = new GoodsLogic();
        $categoryList = $GoodsLogic->red_getSortCategory();
        $this->assign('categoryList',$categoryList);
        return $this->fetch();
    }
    /**
     * [ajaxGoodsList ajax数据]
     * @return [type] [description]
     */
    public function ajaxGoodsList(){
		
        $where = 'is_delete = 0'; // 搜索条件
        I('intro')    && $where = "$where and ".I('intro')." = 1" ;
        I('brand_id') && $where = "$where and brand_id = ".I('brand_id') ;
        (I('is_on_sale') !== '') && $where = "$where and is_on_sale = ".I('is_on_sale') ;
        $cat_id = I('cat_id');
        $extend_cat_id = I('extend_cat_id');
        // 关键词搜索
        $key_word = I('key_word') ? trim(I('key_word')) : '';
        if($key_word)
        {
            $where = "$where and (goods_name like '%$key_word%' or goods_sn like '%$key_word%')" ;
        }

        if($cat_id > 0)
        {
            $grandson_ids = getCatGrandson($cat_id);
            $where .= " and cat_id in(".  implode(',', $grandson_ids).") "; // 初始化搜索条件
        }

        if($extend_cat_id > 0)
        {
            $grandson_ids = getScenarioCatGrandson($extend_cat_id);
            $where .= " and extend_cat_id in(".  implode(',', $grandson_ids).") "; // 初始化搜索条件
        }
        $model = Db::name('red_goods');
        $count = $model->where($where)->count();
        $Page  = new AjaxPage($count,10);
        $show = $Page->show();
        $order_str = "`{$_POST['orderby1']}` {$_POST['orderby2']}";
        $goodsList = $model->where($where)->order($order_str)->limit($Page->firstRow.','.$Page->listRows)->select();

        //判断表内时间戳是否90天未更新
        $goodsList_s = $model->where($where)->order($order_str)->select();
        foreach ($goodsList_s as $key => $value) {
            if (time()-$value['last_update'] > 90*24*3600) {
                Db::name('red_goods')->where('goods_id',$value['goods_id'])->update(array('is_on_sale' => 0 , 'is_hot' => 0 , 'is_new' => 0 ));
            }
        }

        $catList = D('red_goods_category')->select();
        $catList = convert_arr_key($catList, 'id');
        $this->assign('catList',$catList);
        $this->assign('goodsList',$goodsList);
        $this->assign('page',$show);// 赋值分页输出
        return $this->fetch();
    }  

    /**
     * [addEditGoods 添加/修改商品数据]
     */
    public function addEditGoods()
    {

        $GoodsLogic = new GoodsLogic();
        $Goods = new \ylt\admin\model\RedGoods(); //
        $type = I('goods_id') > 0 ? 2 : 1; // 标识自动验证时的 场景 1 表示插入 2 表示更新
        //ajax提交验证
        if ((I('is_ajax') == 1) && IS_POST) {
            $data=I('post.');
            if (count($data['goods_images']) < 1 ||count($data['goods_images']) >8)  {
                $return_arr = array(
                    'status' => -5,
                    'msg' => '商铺相册需大于1张小于8张',
                );
                $this->ajaxReturn($return_arr);
            }

            $Goods->data(I('post.'),true); // 收集数据

            I('cat_id_2') && ($Goods->cat_id = I('cat_id_2'));
            I('cat_id_3') && ($Goods->cat_id = I('cat_id_3'));

            I('extend_cat_id_2') && ($Goods->extend_cat_id = I('extend_cat_id_2'));
            I('extend_cat_id_3') && ($Goods->extend_cat_id = I('extend_cat_id_3'));
            $Goods->shipping_area_ids = implode(',',I('shipping_area_ids/a',[]));
            $Goods->shipping_area_ids = $Goods->shipping_area_ids ? $Goods->shipping_area_ids : '';
            $Goods->spec_type = $Goods->goods_type;

            if ($type == 2) {
                $goods_id = I('goods_id');
                $Goods->isUpdate(true)->save(); // 写入数据到数据库
                // 修改商品后购物车的商品价格也修改一下
                Db::name('cart')->where("goods_id = $goods_id and spec_key = ''")->save(array(
                    'market_price'=>I('market_price'), //市场价
                    'goods_price'=>I('shop_price'), // 本店价
                    'member_goods_price'=>I('shop_price'), // 会员折扣价
                ));
                
                
                 //清理缓存和图片
               if(file_exists("./runtime/html/home_goods_goodsinfo_{$goods_id}.html")){
                    unlink("./runtime/html/home_goods_goodsinfo_{$goods_id}.html");
                    //delFile(UPLOAD_PATH."goods/thumb/".$goods_id); // 删除缩略图
                    //rmdir(UPLOAD_PATH."goods/thumb/".$goods_id);
                }
                adminLog('编辑商品 '.input('goods_name').'');
            } else {
                $Goods->add_time = time(); // 上架时间
                $Goods->last_update = time(); // 更新时间
                $Goods->supplier_id = 41; // 入驻商ID
                $Goods->supplier_name = '一礼通——一创自营';
                $Goods->examine = 1;
                $Goods->save(); // 写入数据到数据库
                $goods_id = $insert_id = $Goods->getLastInsID();
                adminLog('添加商品 '.input('goods_name').'');
            }
            $Goods->afterSave($goods_id);
           // $GoodsLogic->saveGoodsAttr($goods_id,I('goods_type')); // 处理商品 属性
          
            
            $return_arr = array(
                'status' => 1,
                'msg' => '操作成功',
                'data' => array('url' => Url::build('admin/RedGift/a_red_list')),
            );
            $this->ajaxReturn($return_arr);
        }

        $goodsInfo = Db::name('red_goods')->where('goods_id=' . I('GET.id', 0))->find();
        $level_cat = $GoodsLogic->red_find_parent_cat($goodsInfo['cat_id']); // 获取分类默认选中的下拉框
        $cat_list = Db::name('red_goods_category')->where("parent_id = 0 and is_show = 1 ")->select(); // 已经改商品成联动菜单
        $goodsType = $GoodsLogic->red_goodsType();
        $goods_shipping_area_ids = explode(',',$goodsInfo['shipping_area_ids']);
        

        $this->assign('goods_shipping_area_ids',$goods_shipping_area_ids);
        $this->assign('level_cat', $level_cat);
        $this->assign('cat_list', $cat_list);
        $this->assign('goodsType', $goodsType);
        $this->assign('goodsInfo', $goodsInfo);  // 商品详情
        $goodsImages = Db::name("red_goodsImages")->where('goods_id =' . I('GET.id', 0))->select();
        $this->assign('goodsImages', $goodsImages);  // 商品相册
        $this->initEditor(); // 编辑器
        return $this->fetch();
    }

    /**
     * 删除商品 包括图片文件 "彻底删除"
     */
    public function delGoods()
    {
        $id = $_GET['id'];
        $error = '';
        $time = strtotime("-1 year");   //查询一年前的订单商品
        $goods_id = explode(',',$id);
        if (!empty($goods_id && is_array($goods_id))) {
            foreach ($goods_id as $key => $value) {
            // 判断此商品是否有订单
            $c1 = Db::name('RedOrderGoods')->alias('g')->join('order o','o.order_id = g.order_id')->where("g.goods_id = $value")->where("o.confirm_time > $time")->field('goods_id')->find();
            $c1 && $error .= "商品ID：".$c1['goods_id']." 有近一年的订单记录,不得删除! <br/>";
            

             // 商品退货记录
            $c1 = Db::name('red_back_order')->alias('g')->join('order o','o.order_id = g.order_id')->where("g.goods_id = $value")->where("o.confirm_time > $time")->field('goods_id')->find();
            $c1 && $error .= "商品ID：".$c1['goods_id']." 有近一年的退货记录,不得删除! <br/>";


            if($error)
            {
                $return_arr = array('status' => -1,'msg' =>$error,'data'  =>'',);   
                $this->ajaxReturn($return_arr);
            }

            $goods_content=Db::name("RedGoods")->where('goods_id ='.$value)->field('goods_content')->value('goods_content');
            if ($goods_content) {
                $v = $this->remove_content_img($goods_content);
            }
            $image_url=Db::name("red_goods_images")->where('goods_id ='.$value)->field('image_url')->select();
            if ($image_url) {
                foreach ($image_url as $ke => $valu) {
                    @unlink ('.'.$valu['image_url']);       //删除图片文件
                    Db::name("red_goods_images")->where('goods_id ='.$value)->delete();  //删除商品相册数据库内容
                }
            }

            $src=Db::name("red_spec_image")->where('goods_id ='.$value)->field('src')->select();
            if ($src) {
                foreach ($src as $k => $val) {
                    @unlink ('.'.$val['src']);              //删除图片文件
                    Db::name("red_spec_image")->where('goods_id ='.$value)->delete();  //删除商品规格图片数据库内容
                }
            }
            //删除此商品相关内容      
            Db::name("RedGoods")->where('goods_id ='.$value)->delete();  //商品表
            Db::name("red_cart")->where('goods_id ='.$value)->delete();  // 购物车
            Db::name("red_goods_price")->where('goods_id ='.$value)->delete();  //商品规格
            delFile(RUNTIME_PATH);     
            }
        }
        $return_arr = array('status' => 1,'msg' => '删除成功','data'  =>'',);   
        $this->ajaxReturn($return_arr);
    }
    /*删除文章内容图片（也就是删除编辑器上传的图片）*/
    public function remove_content_img($content){
        //匹配并删除图片
        $imgreg = "/<img.*src=\"([^\"]+)\"/U";
        $matches = array();
        preg_match_all($imgreg, $content, $matches);
        foreach($matches[1] as $img_url){
            if(strpos($img_url, 'emoticons')===false){
                $web_root = 'http://' . $_SERVER['HTTP_HOST'] . '/';
                $filepath = str_replace($web_root,'',$img_url);
                if($filepath == $img_url) $filepath = substr($img_url, 1);
                @unlink($filepath);
                $filedir  = dirname($filepath);
                @$files = scandir($filedir);
                if(count($files)<=2)@rmdir($filedir);//如果只剩下./和../,就删除文件夹
            }
        }
        unset($matches);
    }

    /**
     * [RefreshGoods 修改产品库存刷新时间]
     */
    public function RefreshGoods()
    {
        $supplier_id = session('supplier_id') ? session('supplier_id'):41 ;
        $where = 'supplier_id = '.$supplier_id .' and is_delete = 0 and is_on_sale = 1'; // 搜索条件
        $model = Db::name('RedGoods');
        Db::name('RedGoods')->where($where)->update(array('last_update' => time()));
        $this->success('一键刷新成功',Url::build('Admin/RedGift/a_red_list'));
    }

    /**
     * [red_categoryList 商品分类列表]
     * @return [type] [description]
     */
    public function red_categoryList(){
        $GoodsLogic = new GoodsLogic();
        $cat_list = $GoodsLogic->red_goods_cat_list();
        $this->assign('cat_list',$cat_list);
        return $this->fetch();
    } 

    /**
     * [addEditCategory 添加修改商品分类]
     */
    public function addEditCategory(){

        $GoodsLogic = new GoodsLogic();
        if(IS_GET)
        {
            $goods_category_info = Db::name('red_goodsCategory')->where('id='.I('GET.id',0))->find();
            $level_cat = $GoodsLogic->red_find_parent_cat($goods_category_info['id']); // 获取分类默认选中的下拉框
            $cat_list = Db::name('red_goods_category')->where("parent_id = 0")->select(); // 已经改成联动菜单
            $this->assign('level_cat',$level_cat);
            $this->assign('cat_list',$cat_list);
            $this->assign('goods_category_info',$goods_category_info);
            return $this->fetch();
            exit;
        }

        $GoodsCategory =  Db::name('red_goods_category'); //

        $type = I('id') > 0 ? 2 : 1; // 标识自动验证时的 场景 1 表示插入 2 表示更新
        //ajax提交验证
        if(I('is_ajax') == 1)
        {	
        	$data = I('post.');
        	$data['parent_id'] = I('parent_id_1');
        	I('parent_id_2') && ($data['parent_id'] = I('parent_id_2'));
            if($data['id'] > 0 && $data['parent_id'] == $data['id'])
            {
                //  编辑
                $return_arr = array(
                    'status' => -1,
                    'msg'   => '上级分类不能为自己',
                    'data'  => '',
                );
                $this->ajaxReturn($return_arr);
            }

            if ($type == 2)
            {
                $GoodsCategory->update($data); // 写入数据到数据库
                $GoodsLogic->red_refresh_cat(I('id'));
				adminLog('编辑分类 '.input('name').'');
            }
            else
            {
                $GoodsCategory->update($data); // 写入数据到数据库
                $insert_id = $GoodsCategory->insertgetID($data);
                $GoodsLogic->red_refresh_cat($insert_id);
				adminLog('添加分类 '.input('name').'');
            }
            $return_arr = array(
                'status' => 1,
                'msg'   => '操作成功',
                'data'  => array('url'=>Url::build('Admin/RedGift/red_categoryList')),
            );
            $this->ajaxReturn($return_arr);

        }
    }

    /**
     * [delGoodsCategory 删除商品分类]
     * @return [type] [description]
     */
    public function delGoodsCategory(){
        $id = $this->request->param('id');
        // 判断子分类
        $GoodsCategory = Db::name("red_goods_category");
        $count = $GoodsCategory->where("parent_id = {$id}")->count("id");
        $count > 0 && $this->error('该分类下还有分类不得删除!',Url::build('Admin/RedGift/red_categoryList'));
        // 判断是否存在商品
        $goods_count = Db::name('red_goods')->where("cat_id = {$id}")->count('1');
        $goods_count > 0 && $this->error('该分类下有商品不得删除!',Url::build('Admin/RedGift/red_categoryList'));
        // 删除分类
        DB::name('red_goods_category')->where('id',$id)->delete();
        $this->success("操作成功!!!",Url::build('Admin/RedGift/red_categoryList'));
    }

    /**
     * [red_goodsTypeList 商品类型  用于设置商品的属性]
     * @return [type] [description]
     */
    public function red_goodsTypeList(){
        $model = Db::name("RedGoodsType");
		$keyword = I('keyword');
        $where = $keyword ? " name like '%$keyword%' " : "";
        $count = $model->where($where)->count();
        $Page = $pager = new Page($count,14);
        $show  = $Page->show();
        $goodsTypeList = $model->where($where)->order("id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('pager',$pager);
        $this->assign('show',$show);
        $this->assign('goodsTypeList',$goodsTypeList);
        return $this->fetch('red_goodsTypeList');
    }

    /**
     * [addEditGoodsType 添加修改编辑  商品属性类型]
     */
    public function addEditGoodsType()
    {
        $id = $this->request->param('id', 0);
        $model = Db::name("RedGoodsType");
        if (IS_POST) {
            $data = $this->request->post();
            if ($id){
				 DB::name('RedGoodsType')->update($data);
			} 
            else{
				if(DB::name('RedGoodsType')->where('name',$data['name'])->find())
					$this->success("模型已经存在，请勿重复添加!", Url::build('Admin/RedGift/red_goodsTypeList'));
				else
				 DB::name('RedGoodsType')->insert($data);
			}
            $this->success("操作成功!!!", Url::build('Admin/RedGift/red_goodsTypeList'));
            exit;
        }
        $goodsType = $model->find($id);
        $this->assign('goodsType', $goodsType);
        return $this->fetch('addEditGoodsType');
    }

    /**
     * [delGoodsType 删除商品类型]
     * @return [type] [description]
     */
    public function delGoodsType()
    {
        // 判断 商品规格
        $id = $this->request->param('id');
        $count = Db::name("RedSpec")->where("type_id = {$id}")->count("1");
        $count > 0 && $this->error('该类型下有商品规格不得删除!',Url::build('Admin/RedGift/red_goodsTypeList'));
        // 删除分类
        Db::name('RedGoodsType')->where("id = {$id}")->delete();
        $this->success("操作成功!!!",Url::build('Admin/RedGift/red_goodsTypeList'));
    } 

    /**
     * [specList 商品规格列表]
     * @return [type] [description]
     */
    public function specList(){       
        $goodsTypeList = Db::name("RedGoodsType")->select();
        $this->assign('goodsTypeList',$goodsTypeList);
        return $this->fetch();
    }
    
    /**
     * [ajaxSpecList 商品规格列表]
     * @return [type] [description]
     */
    public function ajaxSpecList(){ 
        //ob_start('ob_gzhandler'); // 页面压缩输出
        $where = ' 1 = 1 '; // 搜索条件                        
        I('type_id')   && $where = "$where and type_id = ".I('type_id') ;
        // 关键词搜索               
        $model = D('red_spec');
        $count = $model->where($where)->count();
        $Page       = new AjaxPage($count,13);
        $show = $Page->show();
        $specList = $model->where($where)->order('`type_id` desc')->limit($Page->firstRow.','.$Page->listRows)->select();   
        $GoodsLogic = new GoodsLogic();        
        foreach($specList as $k => $v)
        {       // 获取规格项     
                $arr = $GoodsLogic->getSpecItem($v['id']);
                $specList[$k]['spec_item'] = implode(' , ', $arr);
        }
        
        $this->assign('specList',$specList);
        $this->assign('page',$show);// 赋值分页输出
        $goodsTypeList = Db::name("RedGoodsType")->select(); // 规格分类
        $goodsTypeList = convert_arr_key($goodsTypeList, 'id');
        $this->assign('goodsTypeList',$goodsTypeList);        
        return $this->fetch();
    }  

    /**
     * [addEditSpec 添加修改编辑  商品规格]
     */
    public  function addEditSpec(){
    	$RedSpec = new \ylt\admin\model\RedSpec(); 
        $type = I('id') > 0 ? 2 : 1; // 标识自动验证时的 场景 1 表示插入 2 表示更新
        if((I('is_ajax') == 1) && IS_POST)//ajax提交验证
        {        
            $post_data = I('post.');
            if ($type == 2) {
                Db::name('red_spec')->update($post_data); // 写入数据到数据库
                $RedSpec->afterSave(I('id'));
            } else {
				$count = Db::name('red_spec')->where('type_id',I('type_id'))->count();
				if($count > 1 ){
					$return_arr = array(
					'status' => -1,
					'msg' => '同一模型不允许添加超过2种规格分类！',
					'data' => array('url' => Url::build('/Admin/RedGift/specList')),
				);
				$this->ajaxReturn($return_arr);
				}
                $insert_id = Db::name('red_spec')->insertgetID($post_data);
                $RedSpec->afterSave($insert_id);
            }
            $return_arr = array(
                'status' => 1,
                'msg' => '操作成功',
                'data' => array('url' => Url::build('/Admin/RedGift/specList')),
            );
            $this->ajaxReturn($return_arr);
        }                
       // 点击过来编辑时                 
       $id = I('id/d',0);
       $spec = Db::name('red_spec')->find($id);
       $GoodsLogic = new GoodsLogic();  
       $items = $GoodsLogic->red_getSpecItem($id);
       $spec[items] = implode(PHP_EOL, $items); 
       $this->assign('spec',$spec);
       
       $goodsTypeList = Db::name("RedGoodsType")->select();
       $this->assign('goodsTypeList',$goodsTypeList);           
       return $this->fetch('addSpec');
    }

    /**
     * [addSpecItem 自定义商品规格]
     */
    public function addSpecItem(){  
		$spec_id = input('spec_id');
		$item = input('item');
		if($item == '')
			 exit(json_encode(0)); 
		$data = ['spec_id' => $spec_id, 'item' => $item ,'supplier_id' => 0];
		if(Db::name('red_spec_item')->where($data)->find()){
			$add_id = 0;
		}else{
			Db::name('red_spec_item')->insert($data);
			$add_id =  Db::name('red_spec')->where('id',$spec_id)->value('type_id');
		}
        exit(json_encode($add_id)); 
    }

    /**
     * [delGoodsSpec 删除商品规格]
     * @return [type] [description]
     */
    public function delGoodsSpec()
    {
        $id = I('id');
        // 判断 商品规格项
        $count = Db::name("RedSpecItem")->where("spec_id = {$id}")->count("1");
        // 删除分类
        Db::name('RedSpec')->where("id = {$id}")->delete();
        $this->redirect('Admin/RedGift/specList');
    } 

    /**
     * 动态获取商品规格选择框 根据不同的数据返回不同的选择框
     */
    public function ajaxGetSpecSelect(){
        $goods_id = I('get.goods_id/d') ? I('get.goods_id/d') : 0;
        //查询商品规格
        $specList = Db::name('RedSpec')->where("type_id = ".I('get.spec_type/d'))->order('`order` desc')->select();
        foreach($specList as $k => $v)
            $specList[$k]['spec_item'] = Db::name('RedSpecItem')->where(array('spec_id' => $v['id']))->order('id')->column('id,item'); // 查询商品规格的参数获取规格项

        $items_id = Db::name('RedGoodsPrice')->where('goods_id = '.$goods_id)->getField("GROUP_CONCAT(`key` SEPARATOR '_') AS items_id");//查询商品价格
        $items_ids = explode('_', $items_id);

        // 获取商品规格图片
        if($goods_id)
        {
            $specImageList = Db::name('RedSpecImage')->where("goods_id = $goods_id")->column('spec_image_id,src');
        }
        $this->assign('specImageList',$specImageList);
        $this->assign('items_ids',$items_ids);
        $this->assign('specList',$specList);
        return $this->fetch('ajax_spec_select');
    }

     /**
     * 动态获取商品规格输入框 根据不同的数据返回不同的输入框
     */    
    public function ajaxGetSpecInput(){     
         $GoodsLogic = new GoodsLogic();
         $goods_id = I('goods_id/d') ? I('goods_id/d') : 0;
         $str = $GoodsLogic->red_getSpecInput($goods_id ,I('post.spec_arr/a',[[]]));
         exit($str);   
    }


     /**
     * 初始化编辑器链接
     * 本编辑器参考 地址 http://fex.baidu.com/ueditor/
     */
    private function initEditor()
    {
        $this->assign("URL_upload", Url::build('admin/Ueditor/imageUp',array('savepath'=>'goods'))); // 图片上传目录
        $this->assign("URL_imageUp", Url::build('admin/Ueditor/imageUp',array('savepath'=>'article'))); //  不知道啥图片
        $this->assign("URL_fileUp", Url::build('admin/Ueditor/fileUp',array('savepath'=>'article'))); // 文件上传s
        $this->assign("URL_scrawlUp", Url::build('admin/Ueditor/scrawlUp',array('savepath'=>'article')));  //  图片流
        $this->assign("URL_getRemoteImage", Url::build('admin/Ueditor/getRemoteImage',array('savepath'=>'article'))); // 远程图片管理
        $this->assign("URL_imageManager", Url::build('admin/Ueditor/imageManager',array('savepath'=>'article'))); // 图片管理
        $this->assign("URL_getMovie", Url::build('admin/Ueditor/getMovie',array('savepath'=>'article'))); // 视频上传
        $this->assign("URL_Home", "");
    } 

    /*商品相关结束*/
    /*==========================================================================================*/
    /*订单相关开始*/

    public  $order_status;
    public  $pay_status;
    public  $shipping_status;
    public  $close;
    public  $palte_list;

    /*
      * 初始化操作
      */
    public function _initialize() {
        parent::_initialize();
        config('TOKEN_ON',false); // 关闭表单令牌验证
        $this->order_status = config('agen_ORDER_STATUS');
        $this->pay_status = config('PAY_STATUS');
        $this->shipping_status = config('SHIPPING_STATUS');
        $this->close = config('CLOSE');
        $act_list = Db::name('admin_user')->alias('a')->join('admin_role r','a.role_id=r.role_id')->field('r.act_list,r.role_name,a.admin_id')->where('a.admin_id',session('admin_id'))->find();
        if (stripos($act_list['act_list'],'105') != false and $act_list['is_three']==1) {
            $this->act_list  = $act_list;
        }
        $palte_list = Db::name('admin_role')->where('plate_id = 2 and is_three = 1')->select();
        $this->assign('palte_list',$palte_list);

        // 订单 支付 发货 结算状态
        $this->assign('close',$this->close);
        $this->assign('order_status',$this->order_status);
        $this->assign('pay_status',$this->pay_status);
        $this->assign('shipping_status',$this->shipping_status);
    }

    /**
     * [red_orderIndex 订单列表]
     * @return [type] [description]
     */
    public function red_orderIndex()
    {   
        $begin = date('Y-m-d',strtotime("-1 year"));//30天前
        $end = date('Y/m/d',strtotime('+1 days'));
        $this->assign('timegap',$begin.'-'.$end);
        // $this->assign('keywords',I('keywords'));
        // $this->assign('plate',I('plate'));
        return $this->fetch();
    }
    /**
     * [ajaxindex ajax数据]
     * @return [type] [description]
     */
    public function ajaxOrderIndex(){
        $orderLogic = new OrderLogic();

        $begin = strtotime(input('add_time_begin'));
        $end = strtotime(input('add_time_end')); 
        
        // 搜索条件
        $condition = array();
        $keyType = input("keytype");
        $keywords = I('keywords','','trim');
        $keywords =  $keywords ? $keywords : false;
        $keywords ? $condition[''.$keyType.''] = trim($keywords) : false;

        if($begin && $end){
            $condition['add_time'] = array('between',"$begin,$end");
        }

        input('order_status') != '' ? $condition['order_status'] = input('order_status') : false;
        input('pay_status') != '' ? $condition['pay_status'] = input('pay_status') : false;
        input('pay_code') != '' ? $condition['pay_code'] = input('pay_code') : false;
        input('shipping_status') != '' ? $condition['shipping_status'] = input('shipping_status') : false;
        input('close') != '' ? $condition['close'] = input('close') : false;
        input('user_id') ? $condition['user_id'] = trim(input('user_id')) : false;
        input('plate') ? $condition['plate'] = trim(input('plate')) : false;
        if ($this->act_list) {    //判断是否礼至家居的三级项目负责人
            $act_list = $this->act_list;
            $condition['items_source'] = $act_list['role_name'];
            $this->assign('act_list',1);
        }else{
            input('items_source') != '' ? $condition['items_source'] = input('items_source') : false;
        }
        $sort_order = I('order_by','DESC').' '.I('sort');
        $count = Db::name('red_order')->where($condition)->count();
        $Page  = new AjaxPage($count,20);
        //  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            if($key == 'add_time'){
                $between_time = explode(',',$val[1]);
                $parameter_add_time = date('Y/m/d',$between_time[0]) . '-' . date('Y/m/d',$between_time[1]);
                $Page->parameter['timegap'] = $parameter_add_time;
            }else{
                $Page->parameter[$key]   =  urlencode($val);
            }
        }
        $show = $Page->show();
        //获取订单列表
        $orderList = $orderLogic->red_getOrderList($condition,$sort_order,$Page->firstRow,$Page->listRows);
        $orderList_s = Db::name('red_order')->where($condition)->order('order_id')->field('order_amount')->select();
        foreach ($orderList as $key => $value) {
            $value['user_name'] = Db::name('red_user')->where('admin_id',$value['user_id'])->value('user_name');
            $orderLists[] = $value;
        }
        //计算当前列表的订单总额
        $money = 0;
        foreach ($orderList_s as $key => $value) {
            $money += $value['order_amount'];
        }
        $this->assign('money',$money);
        $this->assign('orderList',$orderLists);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
    }
    /**
     * [orderDetail 订单详情]
     * @param  [type] $order_id [description]
     * @return [type]           [description]
     */
    public function orderDetail($order_id){
        $orderLogic = new OrderLogic();
        $order = $orderLogic->red_getOrderInfo($order_id);
        $orderGoods = $orderLogic->red_getOrderGoods($order_id);
        $button = $orderLogic->agen_getOrderButton($order);  //一创和红礼共用按钮
        // 获取操作记录
        $action_log = Db::name('red_order_action')->where(array('order_id'=>$order_id))->order('log_time desc')->select();
        $userIds = array();
        //查找用户昵称
        foreach ($action_log as $k => $v){
            $userIds[$k] = $v['action_user'];
        }
        //发货单
        $shipping = Db::name('red_shipping_order')->where('order_id',$order_id)->select();

        $this->assign('order',$order);
        $this->assign('action_log',$action_log);
        $this->assign('orderGoods',$orderGoods);
        $split = count($orderGoods) >1 ? 1 : 0;
        foreach ($orderGoods as $val){
            if($val['goods_num']>1){
                $split = 1;
            }
        }
        if($shipping){
            foreach ($shipping as $key => $value){
                $shipping[$key]['exp'] = json_decode($value['logistics_information'],true); 
            }       
        }
        $this->assign('split',$split);
        $this->assign('shipping',$shipping);
        $this->assign('button',$button);
        return $this->fetch();
    }

    /**
     * [update_pr 完成订单详情中的价格修改]
     * @return [type] [description]
     */
    public function update_pr(){
        $order_id = I('order_id');
        $shipping = Db::name('red_order')->where('order_id',$order_id)->update(['order_status'=>1]);
        if ($shipping) {
            $this->success('修改成功');
        }
    }
    /**
     * [update_pr 修改已支付状态]
     * @return [type] [description]
     */
    public function update_pa(){
        $order_id = I('order_id');
        if (Db::name('red_order')->where('order_id',$order_id)->where('order_status = 1')->find()) {
            $this->error('修改失败，该订单客户尚未确认');
        }
        $shipping = Db::name('red_order')->where('order_id',$order_id)->update(['pay_status'=>1,'pay_time'=>time()]);
        if ($shipping) {
            $this->success('修改成功');
        }
    }
    /**
     * [delete_order 删除订单]
     * @param  [type] $order_id [description]
     * @return [type]           [description]
     */
    public function delete_order($order_id){
        $orderLogic = new OrderLogic();
        $del = $orderLogic->red_delOrder($order_id);
        if($del){
            $this->success('删除订单成功');
        }else{
            $this->error('订单删除失败');
        }
    }
    /**
     * [order_print 打印订单]
     * @return [type] [description]
     */
    public function order_print(){
        $order_id = I('order_id');
        $orderLogic = new OrderLogic();
        $order = $orderLogic->red_getOrderInfo($order_id);
        $order['province'] = getRegionName($order['province']);
        $order['city'] = getRegionName($order['city']);
        $order['district'] = getRegionName($order['district']);
        $order['full_address'] = $order['province'].' '.$order['city'].' '.$order['district'].' '. $order['address'];
        $orderGoods = $orderLogic->red_getOrderGoods($order_id);
        $shop = tpCache('shop_info');
        $this->assign('order',$order);
        $this->assign('shop',$shop);
        $this->assign('orderGoods',$orderGoods);
        $template = I('template','print');
        return $this->fetch($template);
    }

    /**
     * [order_action 订单操作]
     * @return [type] [description]
     */
    public function order_action(){
        $orderLogic = new OrderLogic();
        $action = I('get.type');
        $order_id = I('get.order_id');
        if($action && $order_id){
            if($action !=='pay'){
                adminLog('订单操作'.$order_id.I('note').'');
            }
            $a = $orderLogic->red_orderProcessHandle($order_id,$action,array('note'=>I('note'),'admin_id'=>0));
            if($a !== false){
                if ($action == 'remove') {
                    exit(json_encode(array('status' => 1, 'msg' => '操作成功', 'data' => array('url' => Url::build('admin/RedGift/red_orderIndex')))));
                }
                exit(json_encode(array('status' => 1,'msg' => '操作成功')));
            }else{
                if ($action == 'remove') {
                    exit(json_encode(array('status' => 0, 'msg' => '操作失败', 'data' => array('url' => Url::build('admin/RedGift/red_orderIndex')))));
                }
                exit(json_encode(array('status' => 0,'msg' => '操作失败')));
            }
        }else{
            $this->error('参数错误',Url::build('Admin/RedGift/orderDetail',array('order_id'=>$order_id)));
        }
    }
    /**
     * [delivery_info 去发货]
     * @return [type] [description]
     */
    public function delivery_info(){
        $order_id = I('order_id');
        $orderLogic = new OrderLogic();
        $order = $orderLogic->red_getOrderInfo($order_id);
        $orderGoods = $orderLogic->red_getOrderGoods($order_id);
        $delivery_record = Db::name('red_shipping_order')->alias('d')->join('__ADMIN_USER__ a','a.admin_id = d.admin_id')->where('d.order_id='.$order_id)->select();
        if($delivery_record){
            $order['invoice_no'] = $delivery_record[count($delivery_record)-1]['invoice_no'];
        }
        $shipping = Db::name('plugin')->where('type','shipping')->cache(true)->select();
        $this->assign('shipping',$shipping);
        $this->assign('order',$order);
        $this->assign('orderGoods',$orderGoods);
        $this->assign('delivery_record',$delivery_record);//发货记录
        return $this->fetch();
    }
    /**
     * [deliveryHandle 生成发货单]
     * @return [type] [description]
     */
    public function deliveryHandle(){
        $orderLogic = new OrderLogic();
        $data = I('post.');
        $res = $orderLogic->red_deliveryHandle($data);
        
        if($res){
            $this->success('操作成功',Url::build('Admin/RedGift/orderDetail',array('order_id'=>$data['order_id'])));
        }else{
            $this->success('操作失败',Url::build('Admin/RedGift/delivery_info',array('order_id'=>$data['order_id'])));
        }
    }

    /**
     * [editprice 修改价格]
     * @param  [type] $order_id [description]
     * @return [type]           [description]
     */
    public function editprice($order_id){
        $orderLogic = new OrderLogic();
        $order = $orderLogic->red_getOrderInfo($order_id);
        $this->editable($order);
        if(IS_POST){
            $admin_id = session('admin_id');
            if(empty($admin_id)){
                $this->error('非法操作');
                exit;
            }
            $update['discount'] = I('post.discount');
            $update['shipping_price'] = I('post.shipping_price');
            $update['order_tax'] = I('post.order_tax');
            $update['order_amount'] = $order['goods_price'] + $update['shipping_price'] - $update['discount'] - $order['user_money'] - $order['integral_money'] - $order['coupon_price'] ;
            $row = Db::name('red_order')->where(array('order_id'=>$order_id))->update($update);
            if(!$row){
                $this->success('没有更新数据',Url::build('Admin/RedGift/editprice',array('order_id'=>$order_id)));
            }else{
                // $email = Db::name('red_user')->where(array('admin_id'=>$order['user_id']))->value('email');
                // // //修改价格后提醒客户
                // if ($email) {
                //     send_email($email,'一创科技订单价格修改成功','您好！一创科技订单价格已修改成功，请及时处理。');
                // }
                adminLog("修改订单价格：应付金额改为：".$update['order_amount']."");
                $this->success('操作成功',Url::build('Admin/RedGift/orderDetail',array('order_id'=>$order_id)));
            }
            exit;
        }
        $this->assign('order',$order);
        return $this->fetch();
    }

    /**
     * [editable 检测订单是否可以编辑]
     * @param  [type] $order [description]
     * @return [type]        [description]
     */
    private function editable($order){
        if($order['shipping_status'] != 0 ){
            $this->error('已发货订单不允许编辑');
            exit;
        }
        return;
    }

    /**
     * [export_order 导出数据]
     * @return [type] [description]
     */
     public function export_order()
    {
        //搜索条件
        $where = 'where 1 = 1 ';
        $consignee = input('consignee');
        if($consignee){
            $where .= " AND consignee like '%$consignee%' ";
        }
        $order_sn =  input('order_sn');
        if($order_sn){
            $where .= " AND order_sn = '$order_sn' ";
        }
        if(input('order_status')){
            $where .= " AND order_status = ".input('order_status');
        }
        if(input('close')){
            $where .= " AND close = ".input('close');
        }
        if(input('pay_status')){
            $where .= " AND pay_status  = ".input('pay_status');
        }
        if(input('pay_code')){
            if(input('pay_code') == 'alipay')
                $where .= " AND (pay_code = 'alipay' or pay_code = 'alipayMobile')";
            else
                $where .= " AND pay_code = ".input('pay_code');
        }
        if(input('shipping_status')){
            $where .= " AND shipping_status = ".input('shipping_status');
        }
        if(input('add_time_begin')){
            $where .= " AND add_time > ". strtotime(input('add_time_begin'));
        }
        if(input('add_time_end')){
            $where .= " AND add_time < ". strtotime(input('add_time_end'));
        }
        if(input('supplier_id')){
            $where .= " AND supplier_id = " . input('supplier_id');
        }
        if(input('is_distribut')){
            $where .= " AND is_distribut = ".input('is_distribut');
        }
  
        $sql = "select *,FROM_UNIXTIME(add_time,'%Y-%m-%d') as create_time from __PREFIX__red_order $where order by order_id"; //echo $sql;exit;
        $orderList = DB::query($sql);
        $strTable ='<table width="500" border="1">';
        $strTable .= '<tr>';
        $strTable .= '<td style="text-align:center;font-size:12px;width:120px;">订单编号</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="100">日期</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">收货人</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">收货地址</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">电话</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">订单金额</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">实际支付</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">支付方式</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">支付状态</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">发货状态</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">结算状态</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">商品信息</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">交易流水单号</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">店铺名称</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">公司名称</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">交易账号</td>';
        $strTable .= '</tr>';
        if(is_array($orderList)){
            $region = Db::name('region')->column('id,name'); 
            foreach($orderList as $k=>$val){
                $strTable .= '<tr>';
                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['order_sn'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['create_time'].' </td>';               
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['consignee'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'."{$region[$val['province']]},{$region[$val['city']]},{$region[$val['district']]},{$region[$val['twon']]}{$val['address']}".' </td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['mobile'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['goods_price'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['order_amount'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['pay_name'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$this->pay_status[$val['pay_status']].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$this->shipping_status[$val['shipping_status']].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$this->close[$val['close']].'</td>';
                $orderGoods = Db::name('order_goods')->where('order_id='.$val['order_id'])->field('goods_sn,goods_name,spec_key_name')->select();
                $strGoods="";
                foreach($orderGoods as $goods){
                    $strGoods .= "商品编号：".$goods['goods_sn']." 商品名称：".$goods['goods_name'];
                    if ($goods['spec_key_name'] != '') $strGoods .= " 规格：".$goods['spec_key_name'];
                    $strGoods .= "<br />";
                }
                unset($orderGoods);
                $supplier = Db::name('supplier')->where('supplier_id='.$val['supplier_id'])->find();
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$strGoods.' </td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['transaction_id'].' </td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$supplier['supplier_name'].' </td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$supplier['company_name'].' </td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$supplier['bank_name'].'-'.$supplier['bank_account_name'].'-'. $supplier['bank_account_number'].' </td>';
                $strTable .= '</tr>';
                unset($supplier);
            }
        }
        $strTable .='</table>';
        unset($orderList);
        downloadExcel($strTable,'order');
        exit();
    }

    /*订单相关结束*/
    /*=============================================================================*/
    /*反馈及建议开始*/

    /**
     * [suggest 反馈列表]
     * @return [type] [description]
     */
    public function red_suggestList(){
        return $this->fetch();
    }
    public function ajaxSuggestlist(){
        $username = I('nickname','','trim');
        $content = I('content','','trim');
        $where['parent_id'] = 0;
        if($username){
            $where['username'] = $username;
        }
        if ($content) {
            $where['content'] = ['like', '%' . $content . '%'];
        }
        $count = Db::name('red_comment')->where($where)->count();
        $Page = $pager = new AjaxPage($count,15);
        $show = $Page->show();
                
        $comment_list = Db::name('red_comment')->where($where)->order('add_time DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
        
        $this->assign('comment_list',$comment_list);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$pager);// 赋值分页输出
        return $this->fetch();
    }
    /**
     * [detail 查看留言]
     * @return [type] [description]
     */
    public function suggesDetail(){
        $id = I('get.id/d');
        $res = Db::name('red_comment')->where(array('comment_id'=>$id))->find();
        if ($res['txt']) {
            $res['txt'] = "/public/upload/hongli/".$res['txt'];
        }
        if ($res['images']) {
            $image = explode(',',$res['images']);
            foreach ($image as $key => $value) {
                $images[] = "/public/upload/hongli/".$value;
            }
        }
        if(!$res){
            exit($this->error('不存在该评论'));
        }
        $reply = Db::name('comment')->where(array('parent_id'=>$id))->select(); // 评论回复列表
        $this->assign('images',$images);
        $this->assign('comment',$res);
        $this->assign('reply',$reply);
        return $this->fetch();
    }
    /**
     * [del 删除反馈内容]
     * @return [type] [description]
     */
    public function suggesDel(){
        $id = I('get.id/d');
        $row = Db::name('red_comment')->where(array('comment_id'=>$id))->delete();
        if($row){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }

    /*反馈相关结束*/
    /*=============================================================================*/
    /*用户管理开始*/

    /**
     * [red_userList 会员列表]
     * @return [type] [description]
     */
    public function red_userList(){
        // Db::execute('TRUNCATE table ylt_red_log');  //清空表
        $list = array();
        $keywords = I('keywords/s');
        if(empty($keywords)){
            $res = Db::name('red_user')->order('admin_id')->select();
        }else{
            $res = DB::name('red_user')->where('user_name','like','%'.$keywords.'%')->order('admin_id')->select();
        }
        if($res){
            foreach ($res as $val){
                $val['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
                $list[] = $val;
            }
        }
        $this->assign('list',$list);
        return $this->fetch();
    }
    /**
     * [admin_info 添加会员]
     * @return [type] [description]
     */
    public function user_info(){
        $admin_id = I('get.admin_id/d',0);
        if($admin_id){
            $info = Db::name('red_user')->where("admin_id", $admin_id)->find();
            $info['password'] =  "";
            $this->assign('info',$info);
        }else{
            $info['password'] =  "123456";
            $this->assign('info',$info);
        }
        $act = empty($admin_id) ? 'add' : 'edit';
        $this->assign('act',$act);
        $role = Db::name('red_user')->where('1=1')->select();
        $this->assign('role',$role);
        return $this->fetch();
    }
    /**
     * [adminHandle 增加修改删除会员资料]
     * @return [type] [description]
     */
    public function adminHandle(){
        $data = I('post.');
        $data['password'] = encrypt($data['password']);
        $data['user_name'] = trim($data['user_name']);
        if($data['act'] == 'add'){
            if(empty($data['password']) || empty($data['user_name'])){
                $this->error("账号或密码不能为空");
            }
            unset($data['admin_id']);           
            $data['add_time'] = time();
            if(Db::name('red_user')->where("user_name", $data['user_name'])->count()){
                $this->error("此用户名已被注册，请更换",Url::build('Admin/RedGift/user_info'));
            }else{
                $r = Db::name('red_user')->insert($data);
                adminLog('添加用户'.$data['user_name'].'');
                red_adminLog('添加用户'.$data['user_name'].'');
            }
        }
        
        if($data['act'] == 'edit'){
            if(empty($data['password']) || empty($data['user_name'])){
                $this->error("账号或密码不能为空");
            }
            $r = Db::name('red_user')->where('admin_id', $data['admin_id'])->save($data);
            adminLog('修改用户信息'.$data['user_name'].'');
            red_adminLog('修改用户信息'.$data['user_name'].'');
        }
        if($data['act'] == 'del' && $data['admin_id']>1){
            $r = Db::name('red_user')->where('admin_id', $data['admin_id'])->delete();
            adminLog('删除用户 '.$data['admin_id'].'');
            red_adminLog('删除用户 '.$data['admin_id'].'');
            exit(json_encode(1));
        }
        
        if($r){
            $this->success("操作成功",Url::build('Admin/RedGift/red_userList'));
        }else{
            $this->error("操作失败",Url::build('Admin/RedGift/red_userList'));
        }
    }

    /**
     * [password 重置密码]
     * @return [type] [description]
     */
    public function r_password(){
        $admin_id = I('admin_id');
        $p = str_pad(mt_rand(0,999999),6);
        $data['password'] = encrypt($p);
        $update = Db::name('red_user')->where('admin_id', $admin_id)->update($data);
        if ($update) {
             return array('status' => 1,'msg' => '操作成功','paw'  =>$p,);
        }
    }


    /**
     * [operation 操作日志]
     * @return [type] [description]
     */
    public function red_operation(){
        $p = I('p/d',1);
        $logs = DB::name('red_log')->alias('l')->join('red_user a','a.admin_id =l.admin_id')->order('log_time DESC')->page($p.',20')->select();
        $this->assign('list',$logs);
        $count = DB::name('red_log')->where('1=1')->count();
        $Page = new Page($count,20);
        $show = $Page->show();
        $this->assign('pager',$Page);
        $this->assign('page',$show);
        return $this->fetch();
    }


    /*****************************************红礼商家信息*******************************************/

    /**
     * 入驻商家列表
     */
    
    public function BusinessList(){
        if(I('export')){ // 导出数据
            $this->export_supplier(I('post.'));
        }
        $p = I('p/d',1);
        $keyword = input('keyword');
        if ($keyword) {
            $where = "company_name like '%$keyword%'";
        }
        $field = 'red_admin_id,company_name,address,operating_name,mobile,add_time,status,province,city,area,phone_number';
        $res= Db::name('redsupplier_user')->where($where)->field($field)->order('red_admin_id desc')->page($p.',20')->select();
        $count = DB::name('redsupplier_user')->where($where)->count();
        $Page = new Page($count,20);
        $show = $Page->show();
        $this->assign('pager',$Page);
        $this->assign('page',$show);
        $this->assign( 'arr',$res );
        return $this->fetch();
    }


    /**
     * 入驻商详细信息查看
     */
    public function BusinessDetail(){
        $admin_id = I('get.id');
        $supplier = Db::name('redsupplier_user')->where(array('red_admin_id'=>$admin_id))->find();
        if(!$supplier)
            exit($this->error('入驻商家不存在'));
        if(IS_POST){
            $status = I('status');
            Db::name('redsupplier_user')->where(array('red_admin_id'=>$admin_id))->update($_POST);
            if($status == '1'){     //审核通过
                sendCode($supplier['mobile'],'尊敬的商户，您好！您提交的商铺信息已通过审核，可正常上传商品');
            }elseif($status == '2'){    //审核不通过
                sendCode($supplier['mobile'],'尊敬的商户，您好！您提交的商铺信息审核失败，请按要求正确填写.');
            }elseif($status == '-1'){   //关闭供应商，同步下架红礼及一礼通商品
                Db::name('red_goods')->where('red_supplier_id',$admin_id)->update(array('is_on_sale'=>'0','examine'=>'0','is_delete'=>'1'));    //红礼商品下架
                Db::name('goods')->where('red_supplier_id',$admin_id)->update(array('is_on_sale'=>'0','examine'=>'0','is_delete'=>'1')); //一礼通商品同步下架
                sendCode($supplier['mobile'],'尊敬的商户，您好，你在红礼的供应后台：'.$supplier['company_name'].' 已经关闭');
                adminLog('关闭红礼供应商'.$supplier['company_name'].'');
                red_adminLog('关闭供应商'.$supplier['company_name'].'');
                exit($this->success('关闭供应商'));
            }
                adminLog('红礼供应商审核 '.$supplier['company_name'].'');
                red_adminLog('关闭供应商'.$supplier['company_name'].'');
                exit($this->success('操作成功'));
        }
        $this->assign('su',$supplier);
        return $this->fetch();
    }
    
    
    /**
     * 入驻商操作日志
     */
    public function BusinessLog(){
        
    
        $p = I('p/d',1);
        $where = " 1=1 ";
        $keywords = input('keywords');
        input('keywords') ? $where .= " and s.company_name like '%$keywords%'" : false;
        
        $field = 'l.*,a.company_name,a.user_name';
        $logs = DB::name('redsupplier_admin_log')->alias('l')->join('redsupplier_user a','a.red_admin_id =l.admin_id')->field($field)->where($where)->order('log_time DESC')->page($p.',20')->select();

        $this->assign('list',$logs);
        $count = DB::name('redsupplier_admin_log')->alias('l')->join('redsupplier_user a','a.red_admin_id =l.admin_id')->field($field)->where($where)->count();
        $Page = new Page($count,20);
        $show = $Page->show();
        $this->assign('pager',$Page);
        $this->assign('page',$show);
        return $this->fetch();
    }
     /**
     * 导出入驻商家数据
     */
     public function export_supplier($data)
    {
        //搜索条件
        

        $keyword = $data['keyword'];
        $where = $keyword ? " (supplier_name like '%$keyword%' or company_name like '%$keyword%') and status=1 and is_designer = 0" : "status=1 and is_designer = 0";
        $orderList= Db::name('supplier')->where($where)->order('supplier_id')->select();
    
  
        $strTable ='<table width="500" border="1">';
        $strTable .= '<tr>';
        $strTable .= '<td style="text-align:center;font-size:12px;width:100px;">商铺编码</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="100">商铺名称</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">公司名称</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">公司地址</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">公司简介</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">公司规模</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">电子邮箱</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">入驻时间</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">联系人电话</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">公司座机</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">营业执照号</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">开户名称</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">支行名称</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">运营者姓名</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">保证金</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">审核状态</td>';
        $strTable .= '</tr>';
        if(is_array($orderList)){
            $region = Db::name('region')->column('id,name'); 
            foreach($orderList as $k=>$val){
                $strTable .= '<tr>';
                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['supplier_id'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['supplier_name'].' </td>';             
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['company_name'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'."{$region[$val['province']]},{$region[$val['city']]},{$region[$val['area']]},{$val['address']}".' </td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['introduction'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['guimo'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['email'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.date('Y-m-d H-i',$val['add_time']).'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['contacts_phone'].' </td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['phone_number'].' </td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['business_licence_number'].' </td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['bank_name'].' </td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['bank_branch'].' </td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['operating_name'].' </td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['supplier_money'].' </td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">审核通过</td>';
                $strTable .= '</tr>';
                
            }
        }
        $strTable .='</table>';
        unset($orderList);
        downloadExcel($strTable,'入驻商家信息');
        exit();
    }
}