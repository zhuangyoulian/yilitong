<?php
/**
 * Created by PhpStorm.
 * User: lijiayi
 * Date: 2017/3/31
 * Time: 19:15
 */
namespace ylt\supplier\controller;
use ylt\supplier\logic\GoodsLogic;
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
        $cat_list = Db::name('goods_category')->where("parent_id = 0 and is_show = 1")->column('id,name'); // 已经改成联动菜单
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
        $cat_list = Db::name('goods_category')->where("parent_id = 0 and is_show = 1")->select(); // 已经改成联动菜单
        $this->assign('cat_list',$cat_list);
        $brand = Db::name("Brand")->find($id);
        $this->assign('brand',$brand);
        return $this->fetch('addbrand');
    }
    
     /**
     *  商品分类列表
     */
    public function categoryList(){
        $supplier_id = session('supplier_id');
        $GoodsLogic = new GoodsLogic();
        $cat_list = $GoodsLogic->goods_cat_list($supplier_id);
        $this->assign('cat_list',$cat_list);
        return $this->fetch();
    }
    
     /**
     * 添加修改商品分类
     * 正则 ([\u4e00-\u9fa5/\w]+)  ('393','$1'),
     */
    public function addEditCategory(){

        $GoodsLogic = new GoodsLogic();
        $supplier_id = session('supplier_id');
        if(IS_GET)
        {
            $goods_category_info = Db::name('SupplierGoodsCategory')->where('id='.I('GET.id',0))->find();
            $level_cat = $GoodsLogic->find_parent_cat($goods_category_info['id']); // 获取分类默认选中的下拉框

            $cat_list = Db::name('supplier_goods_category')->where(array('parent_id'=>'0','supplier_id'=>$supplier_id))->select(); // 已经改成联动菜单
            $this->assign('level_cat',$level_cat);
            $this->assign('cat_list',$cat_list);
            $this->assign('supplier_id',$supplier_id);
            $this->assign('goods_category_info',$goods_category_info);
            return $this->fetch('category');
            exit;
        }

        $GoodsCategory = D('SupplierGoodsCategory'); //
        $type = I('id') > 0 ? 2 : 1; // 标识自动验证时的 场景 1 表示插入 2 表示更新
        //ajax提交验证
        if(I('is_ajax') == 1)
        {
            // 数据验证
            $validate = \think\Loader::validate('GoodsCategory');
            if(!$validate->batch()->check(I('post.')))
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
                $GoodsCategory->data(I('post.'),true); // 收集数据
                $GoodsCategory->parent_id = I('parent_id_1');
                I('parent_id_2') && ($GoodsCategory->parent_id = I('parent_id_2'));

                if ($type == 2)
                {
                    $GoodsCategory->isUpdate(true)->save(); // 写入数据到数据库
                    $GoodsLogic->refresh_cat(I('id'));
                }
                else
                {
                    //编辑判断
                    $children = Db::name('supplier_goods_category')->where('supplier_id',$supplier_id)->count();
                    if($children > 9)
                        $this->ajaxReturn(array('status' => -1, 'msg'   => '商品分类最多10个', 'data'  => '',));

                    $GoodsCategory->save(); // 写入数据到数据库
                    $insert_id = $GoodsCategory->getLastInsID();
                    $GoodsLogic->refresh_cat($insert_id);
                }
                $return_arr = array(
                    'status' => 1,
                    'msg'   => '操作成功',
                    'data'  => array('url'=>Url::build('Supplier/Goods/categoryList')),
                );
                $this->ajaxReturn($return_arr);

            }
        }

    }
    
        /**
     * 删除分类
     */
    public function delGoodsCategory(){
        $id = $this->request->param('id');
        // 判断子分类
        $GoodsCategory = Db::name("supplier_goods_category");
        $count = $GoodsCategory->where("parent_id = {$id}")->count("id");
        $count > 0 && $this->error('该分类下还有分类不得删除!',Url::build('Supplier/Goods/categoryList'));
        // 判断是否存在商品
        $goods_count = Db::name('Goods')->where("cat_id = {$id}")->count('1');
        $goods_count > 0 && $this->error('该分类下有商品不得删除!',Url::build('Supplier/Goods/categoryList'));
        // 删除分类
        DB::name('supplier_goods_category')->where('id',$id)->delete();
        $this->success("操作成功!!!",Url::build('Supplier/Goods/categoryList'));
    }
    
    
     /**
     *  商品列表
     */
    public function goodsList(){
        $GoodsLogic = new GoodsLogic();
        $brandList = $GoodsLogic->getSortBrands();
        $categoryList = $GoodsLogic->getSortCategory();
        $model = Db::name('Goods');
        $this->assign('categoryList',$categoryList);
        $this->assign('brandList',$brandList);
        return $this->fetch();
    }

    /**
     * 修改产品库存刷新时间
     */
    public function RefreshGoods()
    {
        $supplier_id = session('supplier_id');
        $where = 'supplier_id = '.$supplier_id .' and is_delete = 0 and is_on_sale = 1'; // 搜索条件
        $model = Db::name('Goods');
        // $count = $model->where($where)->count();
        Db::name('Goods')->where($where)->update(array('last_update' => time()));
        $this->success('一键刷新成功',Url::build('Supplier/Goods/goodsList'));
    }
    /**
     *  商品列表
     */
    public function ajaxGoodsList(){

        $supplier_id = session('supplier_id');
        $where = 'supplier_id = '.$supplier_id .' and is_delete = 0' ; // 搜索条件
        I('intro')    && $where = "$where and ".I('intro')." = 1" ;
        I('brand_id') && $where = "$where and brand_id = ".I('brand_id') ;
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


        $model = Db::name('Goods');
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
        //判断表内时间戳是否90天未更新
        $goodsList_s = $model->where($where)->order($order_str)->select();
        foreach ($goodsList_s as $key => $value) {
            if (time()-$value['last_update'] > 90*24*3600) {
                Db::name('Goods')->where('goods_id',$value['goods_id'])->update(array('is_on_sale' => 0 , 'is_hot' => 0 , 'is_new' => 0 ));
            }
        }
        
        $catList = D('goods_category')->select();
        $catList = convert_arr_key($catList, 'id');
        
        $this->assign('catList',$catList);
        $this->assign('goodsList',$goodsList);
        $this->assign('page',$show);// 赋值分页输出
        return $this->fetch();
    }
    
    
     /**
     *  商品列表
     */
    public function goodsDelete(){
        $GoodsLogic = new GoodsLogic();
        $brandList = $GoodsLogic->getSortBrands();
        $categoryList = $GoodsLogic->getSortCategory();
        $this->assign('categoryList',$categoryList);
        $this->assign('brandList',$brandList);
        return $this->fetch();
    }

    /**
     *  删除商品列表
     */
    public function ajaxGoodsDelete(){

        $supplier_id = session('supplier_id');
        $where = 'supplier_id = '.$supplier_id .' and is_delete = 1'; // 搜索条件
        I('intro')    && $where = "$where and ".I('intro')." = 1" ;
        I('brand_id') && $where = "$where and brand_id = ".I('brand_id') ;
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


        $model = Db::name('Goods');
        $count = $model->where($where)->count();
        $Page  = new AjaxPage($count,10);
        /**  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
        $Page->parameter[$key]   =   urlencode($val);
        }
         */
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
        $supplier_id = session('supplier_id'); 
        $GoodsLogic = new GoodsLogic();
        $Goods = new \ylt\admin\model\Goods(); //
        $type = I('goods_id') > 0 ? 2 : 1; // 标识自动验证时的 场景 1 表示插入 2 表示更新
        //ajax提交验证
        if ((I('is_ajax') == 1) && IS_POST) {

            if((input('shop_price') * 9 / 10) < input('commission_price')){
                $this->ajaxReturn(['status'=>0,'msg'=>'佣金设置过多']);
            }

            // 数据验证
            $validate = \think\Loader::validate('Goods');
            if(!$validate->batch()->check(I('post.')))
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
                $data=I('post.');
                if (count($data['goods_images']) < 5 ||count($data['goods_images']) >8)  {
                    $return_arr = array(
                        'status' => -5,
                        'msg' => '商铺相册需大于5张小于8张',
                    );
                    $this->ajaxReturn($return_arr);
                }

                $Goods->data(I('post.'),true); // 收集数据
                if((input('shop_price') * 0.9) < input('commission_price')){
                    $this->ajaxReturn(['status'=>-1,'msg'=>'佣金设置过多']);
                }
                $Goods->supplier_id = $supplier_id; // 入驻商ID
                $Goods->supplier_name = Db::name('supplier')->where('supplier_id',$supplier_id)->value('supplier_name');
               // dump($Goods);die;
                //$Goods->cat_id = $_POST['cat_id_1'];
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
                    // $Goods->supplier_id = $supplier_id; // 入驻商ID
                    // $Goods->supplier_id = 41; // 入驻商ID
                    // $Goods->supplier_name = '一礼通自营';
                    // $Goods->examine = 0;
                    $Goods->save(); // 写入数据到数据库
                    $goods_id = $insert_id = $Goods->getLastInsID();
                    adminLog('添加商品 '.input('goods_name').'');
                }
                $Goods->afterSave($goods_id);
               // $GoodsLogic->saveGoodsAttr($goods_id,I('goods_type')); // 处理商品 属性
              
                
                $return_arr = array(
                    'status' => 1,
                    'msg' => '操作成功',
                    'data' => array('url' => Url::build('Supplier/Goods/goodsList')),
                );
                $this->ajaxReturn($return_arr);
            }
        }
        $goodsInfo = Db::name('Goods')->where('goods_id=' . I('GET.id', 0))->find();
        $level_cat = $GoodsLogic->find_parent_cat($goodsInfo['cat_id']); // 获取分类默认选中的下拉框
        $level_cat2 = $GoodsLogic->find_parent_cat2($goodsInfo['extend_cat_id']); // 获取场景分类默认选中的下拉框
        $cat_list = Db::name('goods_category')->where("parent_id = 0 and is_show = 1")->select(); // 已经改成联动菜单
        //      $extend_cat_list = Db::name('supplier_goods_category')->where(array('level' => 1,'supplier_id' => $supplier_id))->select(); // 已经改成联动菜单
        $cat_scenario_list = Db::name('scenario_category')->where("parent_id = 0 and is_show = 1")->select(); // 已经改成场景联动菜单
        $brandList = $GoodsLogic->getSortBrands();
        $goodsType = $GoodsLogic->goodsType();
        $goods_shipping_area_ids = explode(',',$goodsInfo['shipping_area_ids']);
        $supplier_cat_list = Db::name('supplier_goods_category')->field("id,name")->where("supplier_id =$supplier_id and is_show = 1")->select(); // 店铺分类菜单
        $this->assign('supplier_cat_list', $supplier_cat_list);
        $this->assign('goods_shipping_area_ids',$goods_shipping_area_ids);
        $this->assign('level_cat', $level_cat);
        $this->assign('level_cat2', $level_cat2);
        $this->assign('cat_list', $cat_list);
        //      $this->assign('extend_cat_list', $extend_cat_list);
        $this->assign('cat_scenario_list', $cat_scenario_list);
        $this->assign('brandList', $brandList);
        $this->assign('goodsType', $goodsType);
        $this->assign('goodsInfo', $goodsInfo);  // 商品详情
        $goodsImages = Db::name("GoodsImages")->where('goods_id =' . I('GET.id', 0))->select();
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
        Db::name('goods')->where('goods_id',$goods_id)->update(array('is_delete'=>'0','is_on_sale'=>'1','last_update' => time()));
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
        
        Db::name('goods')->where('goods_id',$goods_id)->update(array('is_delete'=>'1','is_on_sale'=>'0','is_recommend'=>'0','is_new'=>'0','is_hot'=>'0'));
        Db::name('cart')->where('goods_id',$goods_id)->delete();
        Db::name('goods_collect')->where('goods_id',$goods_id)->delete();
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
        
        // 判断此商品是否属于入驻商
        $supplier_id = session('supplier_id');
        $c1 = Db::name('Goods')->where("goods_id = $goods_id and supplier_id = $supplier_id")->count('1');
        !$c1 && $error .= '非法操作! <br/>';
        
        // 判断此商品是否有订单
        $c1 = Db::name('OrderGoods')->where("goods_id = $goods_id")->count('1');
        $c1 && $error .= '此商品有订单,不得删除! <br/>';
        
        
         // 商品退货记录
        $c1 = Db::name('back_order')->where("goods_id = $goods_id")->count('1');
        $c1 && $error .= '此商品有退货记录,不得删除! <br/>';
        
        if($error)
        {
            $return_arr = array('status' => -1,'msg' =>$error,'data'  =>'',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);        
            $this->ajaxReturn($return_arr);
        }
        
        // 删除此商品        
        Db::name("Goods")->where('goods_id ='.$goods_id)->delete();  //商品表
        Db::name("cart")->where('goods_id ='.$goods_id)->delete();  // 购物车
        Db::name("comment")->where('goods_id ='.$goods_id)->delete();  //商品评论
        Db::name("goods_consult")->where('goods_id ='.$goods_id)->delete();  //商品咨询
        Db::name("goods_images")->where('goods_id ='.$goods_id)->delete();  //商品相册
        Db::name("goods_price")->where('goods_id ='.$goods_id)->delete();  //商品规格
        Db::name("spec_image")->where('goods_id ='.$goods_id)->delete();  //商品规格图片
        Db::name("goods_collect")->where('goods_id ='.$goods_id)->delete();  //商品收藏
        delFile(RUNTIME_PATH);             
        $return_arr = array('status' => 1,'msg' => '操作成功','data'  =>'',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);        
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
        $this->assign("URL_upload", Url::build('supplier/Ueditor/imageUp',array('savepath'=>'goods'))); // 图片上传目录
        $this->assign("URL_imageUp", Url::build('supplier/Ueditor/imageUp',array('savepath'=>'article'))); //  不知道啥图片
        $this->assign("URL_fileUp", Url::build('supplier/Ueditor/fileUp',array('savepath'=>'article'))); // 文件上传s
        $this->assign("URL_scrawlUp", Url::build('supplier/Ueditor/scrawlUp',array('savepath'=>'article')));  //  图片流
        $this->assign("URL_getRemoteImage", Url::build('supplier/Ueditor/getRemoteImage',array('savepath'=>'article'))); // 远程图片管理
        $this->assign("URL_imageManager", Url::build('supplier/Ueditor/imageManager',array('savepath'=>'article'))); // 图片管理
        $this->assign("URL_getMovie", Url::build('supplier/Ueditor/getMovie',array('savepath'=>'article'))); // 视频上传
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