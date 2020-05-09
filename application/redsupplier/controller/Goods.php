<?php
/**
 * Created by PhpStorm.
 * User: lijiayi
 * Date: 2017/3/31
 * Time: 19:15
 */
namespace ylt\redsupplier\controller;
use ylt\redsupplier\logic\GoodsLogic;
use think\AjaxPage;
use think\Page;
use think\Db;
use think\Url;
use think\Request;


class Goods extends Base {



        /**
     * @return 品牌列表
     */

    public function brandList(){

        $model = Db::name("Brand");
        $keyword = I('keyword');
        $where = $keyword  ? " name like '%$keyword%'": "";
        $count = $model->where($where)->count();
        $Page = $pager = new Page($count,15);
        $brandList = $model->where($where)->order("`id` desc")->limit($Page->firstRow.','.$Page->listRows)->select();
        $show  = $Page->show();
        $cat_list = Db::name('goods_category')->where("parent_id = 0")->column('id,name'); // 已经改成联动菜单
        $this->assign('cat_list',$cat_list);
        $this->assign('pager',$pager);
        $this->assign('show',$show);
        $this->assign('brandList',$brandList);
        return $this->fetch();
    }

    /**
     * @return 添加修改编辑  商品品牌
     */
    public  function addBrand(){

        $id = I('id');
        if(IS_POST)
        {   
            if (empty(I('post.logo'))) {
                $this->error("logo不能为空!!!");
            }
            if (empty(I('post.parent_cat_id')) || empty(I('post.cat_id'))) {
                $this->error("商品分类不能为空!!!");
            }
            $data = I('post.');
            // dump($data);die;
            if($id){
                Db::name("Brand")->update($data);
                adminLog('编辑品牌 '.input('name').'');
            }else{
              if(Db::name('Brand')->where('name',$data['name'])->count() !=0)
                  $this->success("品牌已经存在!!!",Url::build('Supplier/Goods/brandList',array('p'=>I('p'))));
              else
                 Db::name("Brand")->insert($data);
             adminLog('添加品牌 '.input('name').'');
            }
            $this->success("操作成功,请等待平台审核!!!",Url::build('Supplier/Goods/brandList',array('p'=>I('p'))));
            exit;
        }
        $cat_list = Db::name('goods_category')->where("parent_id = 0")->select(); // 已经改成联动菜单
        $this->assign('cat_list',$cat_list);
        $brand = Db::name("Brand")->find($id);
        $this->assign('brand',$brand);
        return $this->fetch('addbrand');
    }

     /**
     *  商品列表
     */
    public function goodsList(){
        $GoodsLogic = new GoodsLogic();
        // $brandList = $GoodsLogic->getSortBrands();
        $categoryList = $GoodsLogic->getSortCategory();
        $model = Db::name('Goods');
        $this->assign('categoryList',$categoryList);
        // $this->assign('brandList',$brandList);
        return $this->fetch();
    }

    /**
     * 修改产品库存刷新时间
     */
    public function RefreshGoods()
    {
        $red_admin_id = session('red_admin_id');
        $where = 'red_supplier_id = '.$red_admin_id .' and is_delete = 0 and examine = 1'; // 搜索条件
        Db::name('Goods')->where(['red_supplier_id'=>$red_admin_id,'is_delete'=>'1','is_on_sale'=>'1'])->update(array('last_update' => time()));
        Db::name('RedGoods')->where($where)->update(array('last_update' => time()));
        $this->success('一键刷新成功',Url::build('redsupplier/Goods/goodsList'));
    }
    /**
     *  商品列表
     */
    public function ajaxGoodsList(){

        $red_admin_id = session('red_admin_id');
        $where = 'red_supplier_id = '.$red_admin_id .' and is_delete = 0' ; // 搜索条件
        I('intro')    && $where = "$where and ".I('intro')." = 1" ;
        // I('brand_id') && $where = "$where and brand_id = ".I('brand_id') ;
        (I('is_on_sale') !== '') && $where = "$where and is_on_sale = ".I('is_on_sale') ;
        $cat_id = I('cat_id');
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


        $model = Db::name('RedGoods');
        $count = $model->where($where)->count();
        $Page  = new AjaxPage($count,10);

        /**  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
        $Page->parameter[$key]   =   urlencode($val);
        }
         */
        $show = $Page->show();
        $order_str = "`{$_POST['orderby1']}` {$_POST['orderby2']}";
        $goodsList_a = $model->where($where)->order($order_str)->limit($Page->firstRow.','.$Page->listRows)->select();
        //格式化时间戳
        foreach ($goodsList_a as $key => $value) {
            $value['last_update']=date('Y-m-d H:i:s',$value['last_update']);
            $goodsList[]=$value;
        }
        // //判断表内时间戳是否90天未更新
        // $goodsList_s = $model->where($where)->order($order_str)->select();
        // foreach ($goodsList_s as $key => $value) {
        //     if (time()-$value['last_update'] > 90*24*3600) {
        //         Db::name('RedGoods')->where('goods_id',$value['goods_id'])->update(array('is_on_sale' => 0 , 'is_hot' => 0 , 'is_new' => 0 , 'examine' => 0 , 'is_delete' => 1 ));
        //         Db::name('Goods')->where('red_goods_id',$value['goods_id'])->update(array('is_on_sale' => 0 , 'is_hot' => 0 , 'is_new' => 0 , 'examine' => 0 ));
        //     }
        // }
        
        $catList = D('goods_category')->select();
        $catList = convert_arr_key($catList, 'id');
        
        $this->assign('catList',$catList);
        $this->assign('goodsList',$goodsList);
        $this->assign('page',$show);// 赋值分页输出
        return $this->fetch();
    }
    
    
     /**
     *  回收站商品列表
     */
    public function goodsDelete(){
        $GoodsLogic = new GoodsLogic();
        $categoryList = $GoodsLogic->getSortCategory();
        $this->assign('categoryList',$categoryList);
        return $this->fetch();
    }
    public function ajaxGoodsDelete(){

        $red_admin_id = session('red_admin_id');
        $where = 'red_supplier_id = '.$red_admin_id .' and is_delete = 1'; // 搜索条件
        I('intro')    && $where = "$where and ".I('intro')." = 1" ;
        (I('is_on_sale') !== '') && $where = "$where and is_on_sale = ".I('is_on_sale') ;
        $cat_id = I('cat_id');
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
        $model = Db::name('RedGoods');
        $count = $model->where($where)->count();
        $Page  = new AjaxPage($count,10);
        $show = $Page->show();
        $order_str = "`{$_POST['orderby1']}` {$_POST['orderby2']}";
        $goodsList = $model->where($where)->order($order_str)->limit($Page->firstRow.','.$Page->listRows)->select();
        $catList = Db::name('goods_category')->select();
        $catList = convert_arr_key($catList, 'id');
        $this->assign('catList',$catList);
        $this->assign('goodsList',$goodsList);
        $this->assign('page',$show);// 赋值分页输出
        return $this->fetch();
    }
    
    
    /**
     * 添加修改商品
     */
    public function addEditGoods()
    {   

        $supplier_id = session('red_admin_id'); 
        $GoodsLogic = new GoodsLogic();
        $Goods = new \ylt\admin\model\RedGoods(); 
        $type = I('goods_id') > 0 ? 2 : 1; // 标识自动验证时的 场景 1 表示插入 2 表示更新
        //ajax提交验证
        if ((I('is_ajax') == 1) && IS_POST) {
            $data = I('post.');
            if ($type == 2) {
                $data['goods_sn'] = '';
            }
            // 数据验证
            $validate = \think\Loader::validate('Goods');
            if(!$validate->batch()->check($data))
            {
                $error = $validate->getError();
                $error_msg = array_values($error);
                $return_arr = array(
                    'status' => -1,
                    'msg' => $error_msg[0],
                    'data' => $error,
                );
                $this->ajaxReturn($return_arr);
            } else {
                // $data=I('post.');
                if (count($data['goods_images']) < 5 ||count($data['goods_images']) >8)  {
                    $return_arr = array(
                        'status' => -5,
                        'msg' => '商铺相册需大于5张小于8张',
                    );
                    $this->ajaxReturn($return_arr);
                }

                $Goods->data(I('post.'),true); // 收集数据
                $Goods->red_supplier_id = $supplier_id; // 供货商商ID
                $Goods->supplier_id = 686;              // 红礼ID
                $Goods->supplier_name = Db::name('redsupplier_user')->where('red_admin_id',$supplier_id)->value('company_name');
                //$Goods->cat_id = $_POST['cat_id_1'];
                I('cat_id_2') && ($Goods->cat_id = I('cat_id_2'));
                I('cat_id_3') && ($Goods->cat_id = I('cat_id_3'));

                I('extend_cat_id_2') && ($Goods->extend_cat_id = I('extend_cat_id_2'));
                I('extend_cat_id_3') && ($Goods->extend_cat_id = I('extend_cat_id_3'));
                $Goods->shipping_area_ids = implode(',',I('shipping_area_ids/a',[]));
                $Goods->shipping_area_ids = $Goods->shipping_area_ids ? $Goods->shipping_area_ids : '';
                // $Goods->spec_type = $Goods->goods_type;
                

                if ($type == 2) {
                    $Goods->last_update  = time();  // 更新时间
                    $goods_id = I('goods_id');
                    $Goods->examine = 0;
                    $Goods->is_on_sale = 0;
                    $Goods->isUpdate(true)->save(); // 写入数据到数据库
                    // 修改商品后购物车的商品价格也修改一下
                    // Db::name('red_cart')->where("goods_id = $goods_id and spec_key = ''")->save(array(
                    //     'market_price'=>I('market_price'), //市场价
                    //     'goods_price'=>I('shop_price'), // 本店价
                    //     'member_goods_price'=>I('shop_price'), // 会员折扣价
                    // ));
                     //清理缓存和图片
                   if(file_exists("./runtime/html/home_goods_goodsinfo_{$goods_id}.html")){
                        unlink("./runtime/html/home_goods_goodsinfo_{$goods_id}.html");
                        //delFile(UPLOAD_PATH."goods/thumb/".$goods_id); // 删除缩略图
                        //rmdir(UPLOAD_PATH."goods/thumb/".$goods_id);
                    }
                    adminLog('编辑商品 '.input('goods_name').'');
                } else {
                    $Goods->last_update = $Goods->add_time = time();  // 上架时间
                    $Goods->save();             // 写入数据到数据库
                    $Goods->is_on_sale = 0;
                    $Goods->examine = 0;
                    $goods_id = $insert_id = $Goods->getLastInsID();
                    adminLog('添加商品 '.input('goods_name').'');
                }
                $Goods->afterSave($goods_id);
               // $GoodsLogic->saveGoodsAttr($goods_id,I('goods_type')); // 处理商品 属性
              
                
                $return_arr = array(
                    'status' => 1,
                    'msg' => '操作成功',
                    'data' => array('url' => Url::build('redsupplier/Goods/goodsList')),
                );
                $this->ajaxReturn($return_arr);
            }
        }
        $goodsInfo = Db::name('RedGoods')->where('goods_id=' . I('GET.id', 0))->find();
        $level_cat = $GoodsLogic->find_parent_cat($goodsInfo['cat_id']); // 获取分类默认选中的下拉框
        $level_cat2 = $GoodsLogic->find_parent_cat2($goodsInfo['extend_cat_id']); // 获取场景分类默认选中的下拉框
        $cat_list = Db::name('goods_category')->where("parent_id = 0 and is_show = 1")->select(); // 已经改成联动菜单
        //      $extend_cat_list = Db::name('redsupplier_goods_category')->where(array('level' => 1,'supplier_id' => $supplier_id))->select(); // 已经改成联动菜单
        $cat_scenario_list = Db::name('scenario_category')->where("parent_id = 0 and is_show = 1")->select(); // 已经改成场景联动菜单
        $brandList = $GoodsLogic->getSortBrands();
        $goodsType = $GoodsLogic->goodsType();
        $goods_shipping_area_ids = explode(',',$goodsInfo['shipping_area_ids']);
        $company_name = Db::name('redsupplier_user')->where('red_admin_id',$supplier_id)->value('company_name');
        $this->assign('company_name',$company_name);
        $this->assign('goods_shipping_area_ids',$goods_shipping_area_ids);
        $this->assign('level_cat', $level_cat);
        $this->assign('level_cat2', $level_cat2);
        $this->assign('cat_list', $cat_list);
        //      $this->assign('extend_cat_list', $extend_cat_list);
        $this->assign('cat_scenario_list', $cat_scenario_list);
        $this->assign('brandList', $brandList);
        $this->assign('goodsType', $goodsType);
        $this->assign('goodsInfo', $goodsInfo);  // 商品详情
        $goodsImages = Db::name("RedGoodsImages")->where('goods_id =' . I('GET.id', 0))->select();
        $this->assign('goodsImages', $goodsImages);  // 商品相册
        $this->initEditor(); // 编辑器
        return $this->fetch('goods');
    }
    
    /**
     * 商品回收站-设置-重新上架
     */
    public function saleGoods()  
    {
        $goods_id = $_GET['id'];
        Db::name('red_goods')->where('goods_id',$goods_id)->update(array('is_delete'=>'0','is_on_sale'=>'0','last_update' => time()));
        Db::name('goods')->where('red_goods_id',$goods_id)->update(array('is_delete'=>'0','is_on_sale'=>'0','is_recommend'=>'0','is_new'=>'0','is_hot'=>'0'));
        delFile(RUNTIME_PATH);
        $return_arr = array('status' => 1,'msg' => '操作成功','data'  =>'',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);        
        $this->ajaxReturn($return_arr);
        
    }
    
    
    /**
     * 删除商品 回收站
     */
    
    public function delGoods()
    {
        $goods_id = $_GET['id'];
        $error = '';
        Db::name('RedGoods')->where('goods_id',$goods_id)->update(array('is_delete'=>'1','is_on_sale'=>'0','is_recommend'=>'0','is_new'=>'0','is_hot'=>'0','examine'=>'0'));
        Db::name('red_cart')->where('goods_id',$goods_id)->delete();

        //一礼通同步
        $re = Db::name('goods')->where('red_goods_id',$goods_id)->value('goods_id');
        if ($re) {
            Db::name('cart')->where('goods_id',$re)->delete();
            Db::name('goods_collect')->where('goods_id',$re)->delete();
        }
        Db::name('goods')->where('red_goods_id',$goods_id)->update(array('is_delete'=>'1','is_on_sale'=>'0','is_recommend'=>'0','is_new'=>'0','is_hot'=>'0','examine'=>'0'));
        delFile(RUNTIME_PATH);
                     
        $return_arr = array('status' => 1,'msg' => '操作成功','data'  =>'',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);        
        $this->ajaxReturn($return_arr);
    }
    
    
    /**
     * 删除商品 彻底删除
     */
    public function deleteGoods()
    {
        $goods_id = $_GET['id'];
        $error = '';
        // $re = Db::name('red_goods')->where('red_goods_id',$goods_id)->value('goods_id');
        if ($goods_id) {
            // 判断此商品是否属于入驻商
            $red_admin_id = session('red_admin_id');
            $c1 = Db::name('RedGoods')->where("goods_id = $goods_id and red_supplier_id = $red_admin_id")->count('1');
            !$c1 && $error .= '非法操作! <br/>';
            
            // 判断此商品是否有订单
            $c1 = Db::name('RedOrderGoods')->where("goods_id = $goods_id")->count('1');
            $c1 && $error .= '此商品有订单,不得删除! <br/>';
            
             // 商品退货记录
            $c1 = Db::name('red_back_order')->where("goods_id = $goods_id")->count('1');
            $c1 && $error .= '此商品有退货记录,不得删除! <br/>';
            if($error)
            {
                $return_arr = array('status' => -1,'msg' =>$error,'data'  =>'',);   
                $this->ajaxReturn($return_arr);
            }

            $goods_content=Db::name("RedGoods")->where('goods_id ='.$goods_id)->field('goods_content')->value('goods_content');
            if ($goods_content) {
                $v = remove_content_img($goods_content);//去除编辑器图片
            }
            $image_url=Db::name("red_goods_images")->where('goods_id ='.$goods_id)->field('image_url')->select();
            if ($image_url) {
                foreach ($image_url as $ke => $valu) {
                    @unlink ('.'.$valu['image_url']);       //删除图片文件
                    Db::name("red_goods_images")->where('goods_id ='.$goods_id)->delete();  //删除商品相册数据库内容
                }
            }
            $src=Db::name("red_spec_image")->where('goods_id ='.$goods_id)->field('src')->select();
            if ($src) {
                foreach ($src as $k => $val) {
                    @unlink ('.'.$val['src']);              //删除图片文件
                    Db::name("red_spec_image")->where('goods_id ='.$goods_id)->delete();  //删除商品规格图片数据库内容
                }
            }

            // 删除此商品        
            Db::name("RedGoods")->where('goods_id ='.$goods_id)->delete();          //红礼商品表
            Db::name("red_goods_images")->where('goods_id ='.$goods_id)->delete();  //红礼商品相册
            Db::name("red_goods_price")->where('goods_id ='.$goods_id)->delete();   //红礼商品规格
            Db::name("red_spec_image")->where('goods_id ='.$goods_id)->delete();    //红礼商品规格图片
            Db::name("red_cart")->where('goods_id ='.$goods_id)->delete();          //红礼购物车
            Db::name("Goods")->where('ren_goods_id ='.$goods_id)->delete();  //商品表
            Db::name("cart")->where('ren_goods_id ='.$goods_id)->delete();  // 购物车
            Db::name("goods_images")->where('ren_goods_id ='.$goods_id)->delete();  //商品相册
            delFile(RUNTIME_PATH);    
        }         
            $return_arr = array('status' => 1,'msg' => '操作成功','data'  =>'',);   
            $this->ajaxReturn($return_arr);
    }
    
    

    
    
     /**
     * 动态获取商品属性输入框 根据不同的数据返回不同的输入框类型
     */
    public function ajaxGetAttrInput(){
        $GoodsLogic = new GoodsLogic();
        $str = $GoodsLogic->getAttrInput($_REQUEST['goods_id'],$_REQUEST['type_id']);
        exit($str);
    }


    /**
     * 动态获取商品规格选择框 根据不同的数据返回不同的选择框
     */
    public function ajaxGetSpecSelect(){
        $supplier_id = session('supplier_id');
        $goods_id = I('get.goods_id/d') ? I('get.goods_id/d') : 0;
        $GoodsLogic = new GoodsLogic();
        $specList = Db::name('Spec')->where("type_id = ".I('get.spec_type/d'))->order('`order` desc')->select();
        foreach($specList as $k => $v)
            $specList[$k]['spec_item'] = Db::name('SpecItem')->where('spec_id',$v['id'])->where('supplier_id',in,'0,'.$supplier_id.'')->order('id')->column('id,item'); // 获取规格项

        $items_id = Db::name('GoodsPrice')->where('goods_id = '.$goods_id)->getField("GROUP_CONCAT(`key` SEPARATOR '_') AS items_id");
        $items_ids = explode('_', $items_id);
        

        // 获取商品规格图片
        if($goods_id)
        {
            $specImageList = Db::name('SpecImage')->where("goods_id = $goods_id")->column('spec_image_id,src');
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
         $str = $GoodsLogic->getSpecInput($goods_id ,I('post.spec_arr/a',[[]]));
         // dump($str);die;
         exit($str);   
    }
    
    
    /**
     * 自定义商品规格   
     */
    public function addSpecItem(){  
        $spec_id = input('spec_id');
        $item = input('item');
        $supplier_id = session('supplier_id');
        if($item == '')
             exit(json_encode(0)); 
        $data = ['spec_id' => $spec_id, 'item' => $item ,'supplier_id' => $supplier_id];
        if(Db::name('spec_item')->where($data)->find()){
            $add_id = 0;
        }else{
            Db::name('spec_item')->insert($data);
            $add_id =  Db::name('spec')->where('id',$spec_id)->value('type_id');
        }
        exit(json_encode($add_id)); 
    }   
        
    
     /**
     * 初始化编辑器链接
     * 本编辑器参考 地址 http://fex.baidu.com/ueditor/
     */
    private function initEditor()
    {
        $this->assign("URL_upload", Url::build('redsupplier/Ueditor/imageUp',array('savepath'=>'goods'))); // 图片上传目录
        $this->assign("URL_imageUp", Url::build('redsupplier/Ueditor/imageUp',array('savepath'=>'article'))); //  不知道啥图片
        $this->assign("URL_fileUp", Url::build('redsupplier/Ueditor/fileUp',array('savepath'=>'article'))); // 文件上传s
        $this->assign("URL_scrawlUp", Url::build('redsupplier/Ueditor/scrawlUp',array('savepath'=>'article')));  //  图片流
        $this->assign("URL_getRemoteImage", Url::build('redsupplier/Ueditor/getRemoteImage',array('savepath'=>'article'))); // 远程图片管理
        $this->assign("URL_imageManager", Url::build('redsupplier/Ueditor/imageManager',array('savepath'=>'article'))); // 图片管理
        $this->assign("URL_getMovie", Url::build('redsupplier/Ueditor/getMovie',array('savepath'=>'article'))); // 视频上传
        $this->assign("URL_Home", "");
    }
    
 
    /**
     * 删除商品相册图
     */
    public function del_goods_images()
    {
        $path = I('filename','');
        M('goods_images')->where("image_url = '$path'")->delete();
    }
    
    
    
}