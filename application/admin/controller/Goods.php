<?php
/**
 * Created by PhpStorm.
 * User: lijiayi
 * Date: 2017/3/29
 * Time: 16:45
 */
namespace ylt\admin\controller;
use ylt\admin\logic\GoodsLogic;
use think\AjaxPage;
use think\Page;
use think\Db;
use think\Url;
use think\Request;


class Goods extends Base {
    /*
    * 初始化操作
    */
    public function _initialize() {
        parent::_initialize();
        //获取当前账号所属板块权限
        $plate_authority = Db::name('admin_user')->alias('a')->join('plate_menu p','a.plate_id=p.id')->field('p.name,p.id,a.role_id')->where('a.admin_id',session('admin_id'))->find();
        $admin_user = Db::name('admin_user')->where('admin_id',session('admin_id'))->find();
        if ($plate_authority) {
            $this->plate_authority  = $plate_authority;
            $this->assign('plate_authority',$plate_authority);
        }
        if ($admin_user) {
            $this->admin_user  = $admin_user;
            $this->assign('admin_user',$admin_user);
        }

    }

    /**
     * @return 品牌列表
     */

    public function brandList(){

        $model = Db::name("Brand");
        $keyword = I('keyword');
        $where = $keyword ? " name like '%$keyword%' " : "";
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
            if($id){
				Db::name("Brand")->update($data);
				adminLog('编辑品牌 '.input('name').'');
			}else{
			  if(Db::name('Brand')->where('name',$data['name'])->count() !=0)
				  $this->success("品牌已经存在!!!",Url::build('Admin/Goods/brandList',array('p'=>I('p'))));
			  else
				 Db::name("Brand")->insert($data);
			 adminLog('添加品牌 '.input('name').'');
			}
				

            $this->success("操作成功!!!",Url::build('Admin/Goods/brandList',array('p'=>I('p'))));
            exit;
        }
        $cat_list = Db::name('goods_category')->where("parent_id = 0")->select(); // 已经改成联动菜单
        $this->assign('cat_list',$cat_list);
        $brand = Db::name("Brand")->find($id);
        $this->assign('brand',$brand);
        return $this->fetch('addbrand');
    }
	
	
	  /**
     * 删除品牌
     */
    public function delBrand()
    {        
        // 判断此品牌是否有商品在使用
        $goods_count = Db::name('Goods')->where("brand_id = {$_GET['id']}")->count('1');
        if($goods_count)
        {
            $return_arr = array('status' => -1,'msg' => '此品牌有商品在用不得删除!','data'  =>'',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);        
            $this->ajaxReturn($return_arr);
        }
        
        $model = Db::name("Brand");
        // $model->where("logo = ''")->delete(); 
        $model->where('id ='.$_GET['id'])->delete(); 
        $return_arr = array('status' => 1,'msg' => '操作成功','data'  =>'',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);        
        $this->ajaxReturn($return_arr);
    }  

    /**
     * 添加修改商品分类
     * 
     */
    public function addEditCategory(){
        $GoodsLogic = new GoodsLogic();
        if(IS_GET)
        {
            $goods_category_info = Db::name('GoodsCategory')->where('id='.I('GET.id',0))->find();
            $level_cat = $GoodsLogic->find_parent_cat($goods_category_info['id']); // 获取分类默认选中的下拉框
            $cat_list = Db::name('goods_category')->where("parent_id = 0")->select(); // 已经改成联动菜单
            //dump($cat_list);die;
            $this->assign('level_cat',$level_cat);
            $this->assign('cat_list',$cat_list);
            $this->assign('goods_category_info',$goods_category_info);
            return $this->fetch('category');
            exit;
        }

        $GoodsCategory = D('GoodsCategory'); //

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
                //编辑判断
       

                if($GoodsCategory->id > 0 && $GoodsCategory->parent_id == $GoodsCategory->id)
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
                    $GoodsCategory->isUpdate(true)->save(); // 写入数据到数据库
                    $GoodsLogic->refresh_cat(I('id'));
					adminLog('编辑分类 '.input('name').'');
                }
                else
                {
                    $GoodsCategory->save(); // 写入数据到数据库
                    $insert_id = $GoodsCategory->getLastInsID();
                    $GoodsLogic->refresh_cat($insert_id);
					adminLog('添加分类 '.input('name').'');
                }
                $return_arr = array(
                    'status' => 1,
                    'msg'   => '操作成功',
                    'data'  => array('url'=>Url::build('Admin/Goods/categoryList')),
                );
                $this->ajaxReturn($return_arr);

            }
        }

    }
  
  	/**
     * 添加修改场景分类
     *
     */
    public function addEditScenarioCategory(){

        $GoodsLogic = new GoodsLogic();
        if(IS_GET)
        {
            $scenario_category_info = Db::name('ScenarioCategory')->where('id='.I('GET.id',0))->find();
            $level_cat = $GoodsLogic->find_parent_cat2($scenario_category_info['id']); // 获取分类默认选中的下拉框
            $cat_list = Db::name('scenario_category')->where("parent_id = 0")->select(); // 已经改成联动菜单
            // dump($scenario_category_info);die;
            $this->assign('level_cat',$level_cat);
            $this->assign('cat_list',$cat_list);
            $this->assign('scenario_category_info',$scenario_category_info);
            return $this->fetch('scenarioCategory');
            exit;
        }


        $ScenarioCategory = D('ScenarioCategory'); //

        $type = I('id') > 0 ? 2 : 1; // 标识自动验证时的 场景 1 表示插入 2 表示更新
        //ajax提交验证
        if(I('is_ajax') == 1)
        {

            // 数据验证
            $validate = \think\Loader::validate('ScenarioCategory');

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

                $ScenarioCategory->data(I('post.'),true); // 收集数据

                $ScenarioCategory->parent_id = I('parent_id_1');
                I('parent_id_2') && ($ScenarioCategory->parent_id = I('parent_id_2'));
                //  dump($GoodsCategory->id);die;
                //编辑判断
                if($ScenarioCategory->id > 0 && $ScenarioCategory->parent_id == $ScenarioCategory->id)
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
                    $ScenarioCategory->isUpdate(true)->save(); // 写入数据到数据库
                    $GoodsLogic->refresh_cat2(I('id'));
                    adminLog('编辑分类 '.input('name').'');
                }
                else
                {
                    $ScenarioCategory->save(); // 写入数据到数据库
                    $insert_id = $ScenarioCategory->getLastInsID();
                    $GoodsLogic->refresh_cat2($insert_id);
                    adminLog('添加分类 '.input('name').'');
                }
                $return_arr = array(
                    'status' => 1,
                    'msg'   => '操作成功',
                    'data'  => array('url'=>Url::build('Admin/Goods/scenarioCategoryList')),
                );
                $this->ajaxReturn($return_arr);

            }
        }
    }

    // //同步商品的分类ID  临时代码
    // public function ceshi(){
    //     // Db::name('RedGoods')->where(['cat_id'=>1090])->update(['cat_id'=>1118]);
    //     // Db::name('Goods')->where(['cat_id'=>1090])->update(['cat_id'=>1118]);die;
    //     // Db::name('RedGoods')->where(['supplier_name'=>'一礼通——一创自营'])->update(['supplier_name'=>'红礼供应链']);die;
    //     // Db::name('red_goods')->where('goods_id != 0')->update(['is_on_sale'=>0,'is_recommend'=>0,'is_hot'=>0,'is_new'=>0]);die;
    //     $RedGoodsCategory = Db::name('RedGoodsCategory')->field('name,id')->select();
    //     foreach ($RedGoodsCategory as $key => $value) {
    //         $GoodsCategory = Db::name('GoodsCategory')->where(" name = '$value[name]'")->find();
    //         if ($GoodsCategory) {
    //             $data['cat_id'] = $GoodsCategory['id'];
    //             $data['is_catid_replace'] = 1;
    //             Db::name('RedGoods')->where(['cat_id'=>$value['id']])->update($data);
    //             Db::name('Goods')->where(['cat_id'=>$value['id']])->where('red_goods_id != 0')->update($data);
    //             Db::name('RedGoodsCategory')->where(['is_catid_replace'=>0,'id'=>$value['id']])->update(['is_catid_replace'=>1]);
    //         }
    //     }
    // }

    /**
     *  商品分类列表
     */
    public function categoryList(){
        $GoodsLogic = new GoodsLogic();
        $cat_list = $GoodsLogic->goods_cat_list();
        $this->assign('cat_list',$cat_list);
        return $this->fetch();
    }
  
  	/**
     * 场景分类列表
     */
    public function scenarioCategoryList(){
        $GoodsLogic = new GoodsLogic();
        $cat_list = $GoodsLogic->scenario_cat_list();
        $this->assign('cat_list',$cat_list);
        return $this->fetch();
    }
	
	 /**
     * 删除商品分类
     */
    public function delGoodsCategory(){
        $id = $this->request->param('id');
        // 判断子分类
        $GoodsCategory = Db::name("goods_category");
        $count = $GoodsCategory->where("parent_id = {$id}")->count("id");
        $count > 0 && $this->error('该分类下还有分类不得删除!',Url::build('Admin/Goods/categoryList'));
        // 判断是否存在商品
        $goods_count = Db::name('Goods')->where("cat_id = {$id}")->count('1');
        $goods_count > 0 && $this->error('该分类下有商品不得删除!',Url::build('Admin/Goods/categoryList'));
        // 删除分类
        DB::name('goods_category')->where('id',$id)->delete();
        $this->success("操作成功!!!",Url::build('Admin/Goods/categoryList'));
    }
  
  	/**
     * 删除场景分类
     */
    public function delScenarioCategory(){
        $id = $this->request->param('id');
        // 判断子分类
        $GoodsCategory = Db::name("scenario_category");
        $count = $GoodsCategory->where("parent_id = {$id}")->count("id");
        $count > 0 && $this->error('该分类下还有分类不得删除!',Url::build('Admin/Goods/scenarioCategoryList'));
        // 判断是否存在商品
        $goods_count = Db::name('Goods')->where("cat_id = {$id}")->count('1');
        $goods_count > 0 && $this->error('该分类下有商品不得删除!',Url::build('Admin/Goods/scenarioCategoryList'));
        // 删除分类
        DB::name('scenario_category')->where('id',$id)->delete();
        $this->success("操作成功!!!",Url::build('Admin/Goods/scenarioCategoryList'));
    }

    /**
     * 商品类型  用于设置商品的属性
     */
    public function goodsTypeList(){
        $model = Db::name("GoodsType");
		$keyword = I('keyword');
        $where = $keyword ? " name like '%$keyword%' " : "";
        $count = $model->where($where)->count();
        $Page = $pager = new Page($count,14);
        $show  = $Page->show();
        $goodsTypeList = $model->where($where)->order("id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('pager',$pager);
        $this->assign('show',$show);
        $this->assign('goodsTypeList',$goodsTypeList);
        return $this->fetch('goodsTypeList');
    }

    /**
     * 添加修改编辑  商品属性类型
     */
    public function addEditGoodsType()
    {
        $id = $this->request->param('id', 0);
        $model = Db::name("GoodsType");
        if (IS_POST) {
            $data = $this->request->post();
            //var_dump($data);die;
            if ($id){
				 DB::name('GoodsType')->update($data);
			} 
            else{
				if(DB::name('GoodsType')->where('name',$data['name'])->find())
					$this->success("模型已经存在，请勿重复添加!", Url::build('Admin/Goods/goodsTypeList'));
				else
				 DB::name('GoodsType')->insert($data);
			}
               

            $this->success("操作成功!!!", Url::build('Admin/Goods/goodsTypeList'));
            exit;
        }
        $goodsType = $model->find($id);
        $this->assign('goodsType', $goodsType);
        return $this->fetch('addEditGoodsType');
    }


    /**
     *  商品列表
     */
    public function goodsList(){
        $GoodsLogic = new GoodsLogic();
        $brandList = $GoodsLogic->getSortBrands();
        $categoryList = $GoodsLogic->getSortCategory();
        $scenario = $GoodsLogic->getSortScenario();
        $this->assign('scenario',$scenario);
        $this->assign('categoryList',$categoryList);
        $this->assign('brandList',$brandList);
        return $this->fetch();
    }

	/**
     * 修改产品库存刷新时间
     */
    public function RefreshGoods()
    {
        $supplier_id = session('supplier_id') ? session('supplier_id'):41 ;
        $where = 'supplier_id = '.$supplier_id .' and is_delete = 0 and is_on_sale = 1'; // 搜索条件
        $model = Db::name('Goods');
        Db::name('Goods')->where($where)->update(array('last_update' => time()));
        $this->success('一键刷新成功',Url::build('Admin/Goods/goodsList'));
    }

    /**
     *  商品列表
     */
    public function ajaxGoodsList(){
        $where = 'supplier_id = 41 and is_delete = 0'; // 搜索条件
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
        $model = Db::name('Goods');
        $count = $model->where($where)->count();
        $Page  = new AjaxPage($count,10);
        // 搜索条件下 分页赋值
        //foreach($condition as $key=>$val) {
        //$Page->parameter[$key]   =   urlencode($val);
        //}
         
        $show = $Page->show();
        $order_str = "`{$_POST['orderby1']}` {$_POST['orderby2']}";
        $goodsList = $model->where($where)->order($order_str)->limit($Page->firstRow.','.$Page->listRows)->select();
        // echo $model->fetchsql()->where($where)->order($order_str)->limit($Page->firstRow.','.$Page->listRows)->select();die;
        
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
     *  入驻商家商品列表
     */
    public function suppliergoods(){
        $GoodsLogic = new GoodsLogic();
        $brandList = $GoodsLogic->getSortBrands();
        $categoryList = $GoodsLogic->getSortCategory();
        $scenario = $GoodsLogic->getSortScenario();
        $this->assign('scenario',$scenario);
        $this->assign('categoryList',$categoryList);
        $this->assign('brandList',$brandList);
        return $this->fetch();
    }
	
	
	/**
     *  入驻商家商品列表
     */
    public function ajaxSupplierGoods(){

        $where = 'supplier_id > 0 and is_delete = 0 and is_designer = 0'; // 搜索条件
        I('intro')    && $where = "$where and ".I('intro')." = 1" ;
        I('brand_id') && $where = "$where and brand_id = ".I('brand_id') ;
        (I('is_on_sale') !== '') && $where = "$where and is_on_sale = ".I('is_on_sale') ;
        $cat_id = I('cat_id');
        $extend_cat_id = I('extend_cat_id');
        // 关键词搜索
        $key_word = I('key_word') ? trim(I('key_word')) : '';
        if($key_word)
        {
            $where = "$where and (goods_name like '%$key_word%' or goods_sn like '%$key_word%' or supplier_name like '%$key_word%')" ;
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
		$this->assign('count',$count);
        return $this->fetch();
    }
	
    public function goods_out(){
        $where = 'is_delete = 0 and is_on_sale = 1'; // 搜索条件
        $goodsList_s = Db::name('Goods')->where($where)->field('last_update,goods_id')->select();
        //判断表内时间戳是否90天未更新,下架超时产品
        foreach ($goodsList_s as $key => $value) {
            if (time()-$value['last_update'] > 90*24*3600) {
                Db::name('Goods')->where('goods_id',$value['goods_id'])->update(array('is_on_sale' => 0 , 'is_hot' => 0 , 'is_new' => 0 ,'is_recommend' => 0 ,'last_update' => time()));
            }
        }
        $this->success('一键下架成功',Url::build('Admin/Goods/suppliergoods'));
    }
	
	/**
     *  入驻设计师商品列表
     */
    public function DesignerGoods(){
        $GoodsLogic = new GoodsLogic();
        $brandList = $GoodsLogic->getSortBrands();
        $categoryList = $GoodsLogic->getSortCategory();
        $scenario = $GoodsLogic->getSortScenario();
        $this->assign('scenario',$scenario);
        $this->assign('categoryList',$categoryList);
        $this->assign('brandList',$brandList);
        return $this->fetch();
    }
	
	
	/**
     *  入驻设计师商品列表
     */
    public function ajaxDesignerGoods(){

        $where = 'supplier_id > 0 and is_delete = 0 and is_designer = 1'; // 搜索条件
        I('intro')    && $where = "$where and ".I('intro')." = 1" ;
        I('brand_id') && $where = "$where and brand_id = ".I('brand_id') ;
        (I('is_on_sale') !== '') && $where = "$where and is_on_sale = ".I('is_on_sale') ;
        $cat_id = I('cat_id');
        $extend_cat_id = I('extend_cat_id');
        // 关键词搜索
        $key_word = I('key_word') ? trim(I('key_word')) : '';
        if($key_word)
        {
            $where = "$where and (goods_name like '%$key_word%' or goods_sn like '%$key_word%' or supplier_name like '%$key_word%')" ;
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
		$this->assign('count',$count);
        return $this->fetch();
    }
	
	
	
	/**
     *  商品回收站列表
     */
    public function goodsDelete(){
        $GoodsLogic = new GoodsLogic();
        $brandList = $GoodsLogic->getSortBrands();
        $categoryList = $GoodsLogic->getSortCategory();
        $scenario = $GoodsLogic->getSortScenario();
        $this->assign('scenario',$scenario);
        $this->assign('categoryList',$categoryList);
        $this->assign('brandList',$brandList);
        return $this->fetch();
    }
	

    /**
     *  商品回收站列表
     */
    public function ajaxGoodsDelete(){

        $where = 'is_delete = 1'; // 搜索条件
        I('intro')    && $where = "$where and ".I('intro')." = 1" ;
        I('brand_id') && $where = "$where and brand_id = ".I('brand_id') ;
        (I('is_on_sale') !== '') && $where = "$where and is_on_sale = ".I('is_on_sale') ;
        $cat_id = I('cat_id');
        $extend_cat_id = I('extend_cat_id');
        // 关键词搜索
        $key_word = I('key_word') ? trim(I('key_word')) : '';
        if($key_word)
        {
            $where = "$where and (goods_name like '%$key_word%' or goods_sn like '%$key_word%' or supplier_name like '%$key_word%')" ;
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

        $catList = D('goods_category')->select();
        $catList = convert_arr_key($catList, 'id');
        $this->assign('catList',$catList);
        $this->assign('goodsList',$goodsList);
        $this->assign('page',$show);// 赋值分页输出
		$this->assign('count',$count);
        return $this->fetch();
    }
	
    /**
     * 添加修改商品
     */
    public function addEditGoods()
    {

        $GoodsLogic = new GoodsLogic();
        $Goods = new \ylt\admin\model\Goods(); //
        $type = I('goods_id') > 0 ? 2 : 1; // 标识自动验证时的 场景 1 表示插入 2 表示更新
        //ajax提交验证
        if ((I('is_ajax') == 1) && IS_POST) {

            // 数据验证
            $validate = \think\Loader::validate('Goods');
            // dump($validate);
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
                    $Goods->last_update = time(); // 更新时间
                    $Goods->supplier_id = 41; // 入驻商ID
                    $Goods->supplier_name = '一礼通自营';
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
                    'data' => array('url' => Url::build('admin/Goods/goodsList')),
                );
                $this->ajaxReturn($return_arr);
            }
        }

        $goodsInfo = Db::name('Goods')->where('goods_id=' . I('GET.id', 0))->find();
        //$cat_list = $GoodsLogic->goods_cat_list(); // 已经改成联动菜单
        $level_cat = $GoodsLogic->find_parent_cat($goodsInfo['cat_id']); // 获取分类默认选中的下拉框
        $level_cat2 = $GoodsLogic->find_parent_cat2($goodsInfo['extend_cat_id']); // 获取分类默认选中的下拉框
        $ylt=Db::name('goods_category')->field('mobile_name')->where('id',1038)->select();
        $cat_list = Db::name('goods_category')->where("parent_id = 0 and is_show = 1 or mobile_name='{$ylt[0]['mobile_name']}'")->select(); // 已经改商品成联动菜单
        $cat_scenario_list = Db::name('scenario_category')->where("parent_id = 0 and is_show = 1")->select(); // 已经改成场景联动菜单
        $brandList = $GoodsLogic->getSortBrands();  //查询品牌
        $goodsType = $GoodsLogic->goodsType();
        $goods_shipping_area_ids = explode(',',$goodsInfo['shipping_area_ids']);
        if($goodsInfo){
            $supplier_id=$goodsInfo[supplier_id];
        }else{
            $supplier_id=41;  
        }
        $supplier_cat_list = Db::name('supplier_goods_category')->field("id,name")->where("supplier_id =$supplier_id and is_show = 1")->select(); // 店铺分类菜单

        $this->assign('supplier_cat_list', $supplier_cat_list);
        $this->assign('goods_shipping_area_ids',$goods_shipping_area_ids);
        $this->assign('level_cat', $level_cat);
        $this->assign('level_cat2', $level_cat2);
        $this->assign('cat_list', $cat_list);
        $this->assign('cat_scenario_list',$cat_scenario_list);
        $this->assign('brandList', $brandList);
        $this->assign('goodsType', $goodsType);
        $this->assign('goodsInfo', $goodsInfo);  // 商品详情
        $goodsImages = Db::name("GoodsImages")->where('goods_id =' . I('GET.id', 0))->select();
        $this->assign('goodsImages', $goodsImages);  // 商品相册
        $this->initEditor(); // 编辑器
        return $this->fetch('goods');
    }
	
	
	/**
	 * 重新上架
	 */
	public function saleGoods()
	{
		$goods_id = $_GET['id'];
		Db::name('goods')->where('goods_id',$goods_id)->update(array('is_delete'=>'0','is_on_sale'=>'1'));
		delFile(RUNTIME_PATH);
		$return_arr = array('status' => 1,'msg' => '操作成功','data'  =>'',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);        
        $this->ajaxReturn($return_arr);
		
	}
	
	
	/**
     * 删除商品(修改状态放入回收站)
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
     * 删除商品 包括图片文件 "彻底删除"
     */
    public function deleteGoods()
    {
        $id = input('id');
        $error = '';
        $time = strtotime("-1 year");   //查询一年前的订单商品
        $goods_id = explode(',',$id);
        if (!empty($goods_id && is_array($goods_id))) {
            foreach ($goods_id as $key => $value) {
                // 判断此商品是否有订单
                $c1 = Db::name('OrderGoods')->alias('g')->join('order o','o.order_id = g.order_id')->where("g.goods_id = $value")->where("o.confirm_time > $time")->field('goods_id')->find();
                $c1 && $error .= "商品ID：".$c1['goods_id']." 有近一年的订单记录,不得删除! <br/>";
                

                 // 商品退货记录
                $c1 = Db::name('back_order')->alias('g')->join('order o','o.order_id = g.order_id')->where("g.goods_id = $value")->where("o.confirm_time > $time")->field('goods_id')->find();
                $c1 && $error .= "商品ID：".$c1['goods_id']." 有近一年的退货记录,不得删除! <br/>";


                if($error)
                {
                    $return_arr = array('status' => -1,'msg' =>$error,'data'  =>'',);   
                    $this->ajaxReturn($return_arr);
                }

                $goods_content=Db::name("Goods")->where('goods_id ='.$value)->field('goods_content')->value('goods_content');
                if ($goods_content) {
                    $v = remove_content_img($goods_content);//去除编辑器图片
                }
                $image_url=Db::name("goods_images")->where('goods_id ='.$value)->field('image_url')->select();
                if ($image_url) {
                    foreach ($image_url as $ke => $valu) {
                        @unlink('.'.$valu['image_url']);       //删除图片文件
                        Db::name("goods_images")->where('goods_id ='.$value)->delete();  //删除商品相册数据库内容
                    }
                }

                $src=Db::name("spec_image")->where('goods_id ='.$value)->field('src')->select();
                if ($src) {
                    foreach ($src as $k => $val) {
                        @unlink('.'.$val['src']);              //删除图片文件
                        Db::name("spec_image")->where('goods_id ='.$value)->delete();  //删除商品规格图片数据库内容
                    }
                }
                //删除此商品相关内容      
                Db::name("Goods")->where('goods_id ='.$value)->delete();  //商品表
                Db::name("cart")->where('goods_id ='.$value)->delete();  // 购物车
                Db::name("comment")->where('goods_id ='.$value)->delete();  //商品评论
                Db::name("goods_consult")->where('goods_id ='.$value)->delete();  //商品咨询
                Db::name("goods_price")->where('goods_id ='.$value)->delete();  //商品规格
                Db::name("goods_collect")->where('goods_id ='.$value)->delete();  //商品收藏
                delFile(RUNTIME_PATH);     
            }
        }
        $return_arr = array('status' => 1,'msg' => '删除成功','data'  =>'',);   
        $this->ajaxReturn($return_arr);
    }


	/**
     * 删除商品类型 
     */
    public function delGoodsType()
    {
        // 判断 商品规格
        $id = $this->request->param('id');
        $count = Db::name("Spec")->where("type_id = {$id}")->count("1");
        $count > 0 && $this->error('该类型下有商品规格不得删除!',Url::build('Admin/Goods/goodsTypeList'));
        // 删除分类
        Db::name('GoodsType')->where("id = {$id}")->delete();
        $this->success("操作成功!!!",Url::build('Admin/Goods/goodsTypeList'));
    } 
	
	 /**
     * 商品规格列表    
     */
    public function specList(){       
        $goodsTypeList = Db::name("GoodsType")->select();
        $this->assign('goodsTypeList',$goodsTypeList);
        return $this->fetch();
    }
    
    
    /**
     *  商品规格列表
     */
    public function ajaxSpecList(){ 
        //ob_start('ob_gzhandler'); // 页面压缩输出
        $where = ' 1 = 1 '; // 搜索条件                        
        I('type_id')   && $where = "$where and type_id = ".I('type_id') ;
        // 关键词搜索               
        $model = D('spec');
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
        $goodsTypeList = Db::name("GoodsType")->select(); // 规格分类
        $goodsTypeList = convert_arr_key($goodsTypeList, 'id');
        $this->assign('goodsTypeList',$goodsTypeList);        
        return $this->fetch();
    }  

	/**
     * 添加修改编辑  商品规格
     */
    public  function addEditSpec(){
                        
            $model = D("spec");                      
            $type = I('id') > 0 ? 2 : 1; // 标识自动验证时的 场景 1 表示插入 2 表示更新
            if((I('is_ajax') == 1) && IS_POST)//ajax提交验证
            {        
            // alert(123);        
                // 数据验证
                $validate = \think\Loader::validate('Spec');
                $post_data = I('post.');
                if ($type == 2) {
                    //更新数据
                    $check = $validate->scene('edit')->batch()->check($post_data);
                } else {
                    //插入数据
                    $check = $validate->batch()->check($post_data);
                }
                if (!$check) {
                    $error = $validate->getError();
                    $error_msg = array_values($error);
                    $return_arr = array(
                        'status' => -1,
                        'msg' => $error_msg[0],
                        'data' => $error,
                    );
                    $this->ajaxReturn($return_arr);
                }
                $model->data($post_data, true); // 收集数据
                if ($type == 2) {
                    $model->isUpdate(true)->save(); // 写入数据到数据库
                    $model->afterSave(I('id'));
                } else {
					
					$count = Db::name('spec')->where('type_id',I('type_id'))->count();
					if($count > 1 ){
						$return_arr = array(
						'status' => -1,
						'msg' => '同一模型不允许添加超过2种规格分类！',
						'data' => array('url' => Url::build('Admin/Goods/specList')),
					);
					$this->ajaxReturn($return_arr);
					
						
					}
                    $model->save(); // 写入数据到数据库
                    $insert_id = $model->getLastInsID();
                    $model->afterSave($insert_id);
                }
                $return_arr = array(
                    'status' => 1,
                    'msg' => '操作成功',
                    'data' => array('url' => Url::build('Admin/Goods/specList')),
                );
                $this->ajaxReturn($return_arr);
            }                
           // 点击过来编辑时                 
           $id = I('id/d',0);
           $spec = $model->find($id);
           $GoodsLogic = new GoodsLogic();  
           $items = $GoodsLogic->getSpecItem($id);
           $spec[items] = implode(PHP_EOL, $items); 
           $this->assign('spec',$spec);
           
           $goodsTypeList = Db::name("GoodsType")->select();
           $this->assign('goodsTypeList',$goodsTypeList);           
           return $this->fetch('addspec');
    } 


	/**
     * 自定义商品规格   
     */
    public function addSpecItem(){  
		$spec_id = input('spec_id');
		$item = input('item');
		if($item == '')
			 exit(json_encode(0)); 
		$data = ['spec_id' => $spec_id, 'item' => $item ,'supplier_id' => 0];
		if(Db::name('spec_item')->where($data)->find()){
			$add_id = 0;
		}else{
			Db::name('spec_item')->insert($data);
			$add_id =  Db::name('spec')->where('id',$spec_id)->value('type_id');
		}
        exit(json_encode($add_id)); 
    }	
	
	
	/**
     * 删除商品规格
     */
    public function delGoodsSpec()
    {
        $id = I('id');
        // 判断 商品规格项
        $count = Db::name("SpecItem")->where("spec_id = {$id}")->count("1");
       // $count > 0 && $this->error('清空规格项后才可以删除!',Url::build('Admin/Goods/specList'));
        // 删除分类
        Db::name('Spec')->where("id = {$id}")->delete();
        $this->redirect('Admin/Goods/specList');
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
        $goods_id = I('get.goods_id/d') ? I('get.goods_id/d') : 0;
        $GoodsLogic = new GoodsLogic();
    
        //查询商品规格
        $specList = Db::name('Spec')->where("type_id = ".I('get.spec_type/d'))->order('`order` desc')->select();
        foreach($specList as $k => $v)
            $specList[$k]['spec_item'] = Db::name('SpecItem')->where(array('spec_id' => $v['id']))->order('id')->column('id,item'); // 查询商品规格的参数获取规格项

        $items_id = Db::name('GoodsPrice')->where('goods_id = '.$goods_id)->getField("GROUP_CONCAT(`key` SEPARATOR '_') AS items_id");//查询商品价格
        // echo Db::name('GoodsPrice')->fetchsql()->where('goods_id = '.$goods_id)->getField("GROUP_CONCAT(`key` SEPARATOR '_') AS items_id");//查询商品价格
        // dump($items_id);die;
        $items_ids = explode('_', $items_id);

        // 获取商品规格图片
        if($goods_id)
        {
            $specImageList = Db::name('SpecImage')->where("goods_id = $goods_id")->column('spec_image_id,src');
        }
        // dump($specList);die;
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
         exit($str);   
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