<?php
namespace ylt\admin\controller;
use think\Page;
use think\Verify;
use think\Db;
use think\Session;
use think\Url;
use think\Request;
use ylt\admin\logic\GoodsLogic;

header( 'Content-Type:text/html;charset=utf-8 ');
class Ad extends Base{
    public function ad(){       
        $act = I('get.act','add');
        $ad_id = I('get.ad_id/d');
        $ad_info = array();
        $ad_info['pid'] = $this->request->param('pid');
        $banner = 0;
        if($ad_id){
            $ad_info = Db::name('ad')->where('ad_id',$ad_id)->find();
            $ad_info['start_time'] = date('Y-m-d H:i:s',$ad_info['start_time']);
            $ad_info['end_time'] = date('Y-m-d H:i:s',$ad_info['end_time']);    
        }
        if($act == 'add'){
        	
        	$position = Db::name('ad_position')->where('position_id',$ad_info['pid'])->select();
        }else{
        	$position = Db::name('ad_position')->where('1=1')->select();
        }
        if($ad_info['pid']==12){
        	$banner = 1;
        }

        $this->assign('banner',$banner);
        $this->assign('info',$ad_info);
        $this->assign('act',$act);
        $this->assign('position',$position);
        return $this->fetch();
    }
    /**
     * [brandList 广告列表]
     * @return [type] [description]
     */
    public function brandList(){
    	delFile(RUNTIME_PATH.'html'); // 先清除缓存, 否则不好预览
        $Ad =  DB::name('recommend_brand');         
        $is_on_sale = I('is_on_sale',3);
        $keywords = I('keywords/s',false,'trim');
        if($keywords){
        	 is_numeric ($keywords) ?   $where['id'] =$keywords:$where['brand_name'] = array('like','%'.$keywords.'%');
        }
        if ($is_on_sale==0||$is_on_sale==1) {
        	$where['is_on_sale'] = $is_on_sale;
        }
        $count = $Ad->where($where)->count();// 查询满足要求的总记录数
        $Page = $pager = new Page($count,15);// 实例化分页类 传入总记录数和每页显示的记录数
        $res = $Ad->where($where)->order('is_on_sale desc,id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $list = array();
        if($res){
        	foreach ($res as $val){
        		$val['start_time'] = date('Y-m-d H:i:s',$val['start_time']);
        		$val['end_time'] =   date('Y-m-d H:i:s',$val['end_time']);
        		$list[] = $val;
        	}
        }
        $show = $Page->show();// 分页显示输出
        $this->assign('list',$list);// 赋值数据集
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$pager);        
        return $this->fetch();
    }

    public function deleteBrand(){
    	$id = I('post.id/d');
    	$r = Db::name('recommend_brand')->where('id',$id)->delete();
    	adminLog('删除品牌推荐 '.$id.'');
    	if($r) exit(json_encode(1));
    }

    /**
     * [preferredBrand 移动四个区域的广告详情]
     * @return [type] [description]
     */
    public function preferredBrand(){
     	$act = I('post.act');
        $id = I('get.id/d');
        $ad_info = array();
        $ad_info['pid'] = $this->request->param('pid');
        $banner = 0;
        
        if($act == 'add'){	//添加品牌推荐
        	$data = I('post.');
        	$goods_ids=$data['goods_id'];
        	$goods_thumb=$data['goods_thumb'];
        	$goods_name=$data['goods_name'];
        	$shop_price=$data['shop_price'];
        	$store_count=$data['store_count'];
        	$show=$data['show'];
        	$sort=$data['sort'];
        	$data['start_time']=strtotime($data['start_time']);
        	$data['end_time']=strtotime($data['end_time']);;
        	$r = Db::name('recommend_brand')->insert($data);
        	$brandId = Db::name('recommend_brand')->getLastInsID();
        	if ($brandId && $goods_ids) {
	        	foreach($goods_ids as $key => $v){
	        		$recommend_goods['brand_id']=$brandId;
	        		$recommend_goods['goods_id']=$goods_ids[$key];
	        		$recommend_goods['goods_name']=$goods_name[$key];
	        		$recommend_goods['goods_thumb']=$goods_thumb[$key];
	        		$recommend_goods['goods_price']=$shop_price[$key];
	        		$recommend_goods['store_count']=$store_count[$key];
	        		$recommend_goods['is_on_sale']=$show[$key];
	        		$recommend_goods['sort']=$sort[$key];
	        		$r = Db::name('recommend_goods')->insert($recommend_goods);
	        	}
        	}else{
        		$this->error("添加失败");
				exit;
        	}
        	$this->redirect('Admin/Ad/brandList',array('id'=>$data['pid']));
        }
   		 elseif($act == 'update' && $id){//修改品牌推荐
   		 	$data = I('post.');
   		 	$r = Db::name('recommend_goods')->where('brand_id', $id)->delete();
   		 	adminLog('更新推荐品牌 '.$id.'');
   		 	$goods_ids=$data['goods_id'];
   		 	$goods_thumb=$data['goods_thumb'];
   		 	$goods_name=$data['goods_name'];
   		 	$shop_price=$data['shop_price'];
   		 	$store_count=$data['store_count'];
   		 	$show=$data['show'];
   		 	$sort=$data['sort'];
   		 	$data['start_time']=strtotime($data['start_time']);
   		 	$data['end_time']=strtotime($data['end_time']);;
   		 	$r = Db::name('recommend_brand')->where('id', $id)->update($data);
   		 	//echo Db::name('recommend_brand')->getlastsql(); die;
   		 	adminLog('编辑广告 '.input('ad_name').'');
   		 	if ($goods_ids) {
	   		 	foreach( $goods_ids as $key => $v){
	   		 		$recommend_goods['brand_id']=$id;
	   		 		$recommend_goods['goods_id']=$goods_ids[$key];
	   		 		$recommend_goods['goods_name']=$goods_name[$key];
	   		 		$recommend_goods['goods_thumb']=$goods_thumb[$key];
	   		 		$recommend_goods['goods_price']=$shop_price[$key];
	   		 		$recommend_goods['store_count']=$store_count[$key];
	   		 		$recommend_goods['is_on_sale']=$show[$key];
	   		 		$recommend_goods['sort']=$sort[$key];
	   		 		$r = Db::name('recommend_goods')->insert($recommend_goods);
	   		 	}
   		 	}
   		 	$this->redirect('Admin/Ad/brandList',array('id'=>$data['pid']));
        }elseif($id){//品牌详情
            $brand_info = Db::name('recommend_brand')->where('id',$id)->find();
            $brand_info['start_time'] = date('Y-m-d H:i:s',$brand_info['start_time']);
            $brand_info['end_time'] = date('Y-m-d H:i:s',$brand_info['end_time']);   
            $brand_goods = Db::name('recommend_goods')->where('brand_id',$id)->order('is_on_sale desc,id desc')->select();
            $act = 'update';
        }else{
        	$act = 'add';
        }
       
        if($ad_info['pid']==12){
        	$banner = 1;
        }
        $this->assign('info',$brand_info);
        $this->assign('brand_goods',$brand_goods);
        $this->assign('act',$act);
        return $this->fetch();
    }

    /**
     * [adList 广告列表]
     * @return [type] [description]
     */
    public function adList(){
        
        delFile(RUNTIME_PATH.'html'); // 先清除缓存, 否则不好预览
        
       
        $Ad =  DB::name('ad');         
        $pid = I('pid',0);
        if($pid){
            $where['pid'] = $pid;
        	$this->assign('pid',I('pid'));
        }
        if ($_GET['id']) {
        	$where['pid'] = $_GET['id'];
        	$this->assign('pid',$_GET['id']);
        }
        $enabled = I('enabled',5);
        $keywords = I('keywords/s',false,'trim');
        if($keywords){
        	 is_numeric ($keywords) ?   $where['ad_id'] =$keywords:$where['ad_name'] = array('like','%'.$keywords.'%');
        }
        if ($enabled==0||$enabled==1) {
        	$where['enabled'] = $enabled;
        }

        $count = $Ad->where($where)->count();// 查询满足要求的总记录数
        $Page = $pager = new Page($count,15);// 实例化分页类 传入总记录数和每页显示的记录数
        $res = $Ad->where($where)->order('enabled desc,ad_id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $list = array();
       
        if($res){
        	$media = array('商品','链接','分类导航页','分类商品页','店铺','满减','折扣','购物车','个人中心','店铺列表','设计师首页','秒杀','抢购','二级分类');
        	foreach ($res as $val){
        		$val['start_time'] = date('Y-m-d H:i:s',$val['start_time']);
        		$val['end_time'] =   date('Y-m-d H:i:s',$val['end_time']);
        		$val['media_type'] = $media[$val['media_type']];        		
        		$list[] = $val;
        	}
        }
        $ad_position_list = DB::name('AdPosition')->column("position_id,position_name,is_open");
        
        $this->assign('ad_position_list',$ad_position_list);//广告位 
        $show = $Page->show();// 分页显示输出
        $this->assign('list',$list);// 赋值数据集
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$pager);        
        return $this->fetch();
    }
    /**
     * [position 广告详情]
     * @return [type] [description]
     */
    public function position(){
        $act = I('get.act','add');
        $position_id = I('get.position_id/d');
        $info = array();
        if($position_id){
            $info = Db::name('ad_position')->where('position_id',$position_id)->find();
        }
        $this->assign('info',$info);
        $this->assign('act',$act);
        return $this->fetch();
    }
    /**
     * [positionList 广告位列表]
     * @return [type] [description]
     */
    public function positionList(){
        $Position =  DB::name('ad_position');
        $count = $Position->where('1=1')->count();// 查询满足要求的总记录数
        $Page = $pager = new Page($count,15);// 实例化分页类 传入总记录数和每页显示的记录数
        $list = $Position->order('position_id DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
        
        $this->assign('list',$list);// 赋值数据集                
        $show = $Page->show();// 分页显示输出
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$pager); 
        return $this->fetch();
    }
    
    
    public function adHandle(){
    	$data = I('post.');
    
    	$data['start_time'] = strtotime($data['begin']);
    	$data['end_time'] = strtotime($data['end']);
    	
    	if($data['act'] == 'add'){
			$count =Db::name('ad')->where('pid',$data['pid'])->count();
			if($count > 100){
				$this->error("单个广告位添加广告过多，请先删除之前的广告，或者直接修改之前广告",Url::build('Admin/Ad/adList',array('id'=>$data['pid'])));
				exit;
			}
				
    		$r = Db::name('ad')->insert($data);
			adminLog('添加广告 '.input('ad_name').'');
    	}
    	if($data['act'] == 'edit'){
    		$r = Db::name('ad')->where('ad_id', $data['ad_id'])->update($data);
			adminLog('编辑广告 '.input('ad_name').'');
    	}
    	
    	if($data['act'] == 'del'){
            $r = Db::name('ad')->where('ad_id', $data['del_id'])->delete();
			adminLog('删除广告 '.$data['del_id'].'');
    		if($r) exit(json_encode(1));
    	}
    	$referurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : Url::build('Admin/Ad/adList',array('id'=>$data['pid']));
        // 不管是添加还是修改广告 都清除一下缓存
        delFile(RUNTIME_PATH.'html'); // 先清除缓存, 否则不好预览
        
    	if($r){
			 $this->redirect('Admin/Ad/adList',array('id'=>$data['pid']));
    	}else{
    		$this->error("操作失败",$referurl);
    	}
    }
    
    public function positionHandle(){
        $data = I('post.');
        if($data['act'] == 'add'){
            $r = DB::name('ad_position')->insert($data);
			adminLog('添加广告位置 '.input('position_name').'');
        }
        
        if($data['act'] == 'edit'){
        	$r = DB::name('ad_position')->where('position_id',$data['position_id'])->save($data);
			adminLog('编辑广告位置 '.input('position_name').'');
        }
        
        if($data['act'] == 'del'){
        	if(DB::name('ad')->where('pid',$data['position_id'])->count()>0){
        		$this->error("此广告位下还有广告，请先清除",Url::build('Admin/Ad/positionList'));
        	}else{
        		$r = DB::name('ad_position')->where('position_id', $data['position_id'])->delete();
				adminLog('删除广告位置 '.$data['position_id'].'');
        		if($r) exit(json_encode(1));
        	}
        }
        $referurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : Url::build('Admin/Ad/positionList');
        if($r){
        	
			 $this->redirect('Admin/Ad/positionList');
        }else{
        	$this->error("操作失败",$referurl);
        }
    }
    
    

    public function changeAdField(){
        $field = $this->request->request('field');
    	$data[$field] = I('get.value');
    	$data['ad_id'] = I('get.ad_id');
    	DB::name('ad')->save($data); // 根据条件保存修改的数据
    }


    /*****************************
    * 精选商铺管理
    * 2017-11-2
    *****************************/
    /**
     * [supplierRecommend 添加与编辑]
     * @return [type] [description]
     */
    public function supplierRecommend(){
        $act = I('get.act','add');
        $id = I('get.id/d');
        $info = array();
        if($id){
            $info = Db::name('supplier_recommend')->where('id',$id)->find();
        }
        $supplier_info=Db::name('supplier')->field("supplier_id,supplier_name,logo,introduction")->where('status=1 and is_designer=0')->select();
        $this->assign('info',$info);
        $this->assign('act',$act);
        $this->assign('supplier_info',$supplier_info);
        return $this->fetch();
    }
    
    /**
     * [supplierRecommendList 精选商铺列表]
     * @return [type] [description]
     */
    public function supplierRecommendList(){
        $Recommend =  DB::name('supplier_recommend');
        $count = $Recommend->where('1=1')->count();// 查询满足要求的总记录数
        $Page = $pager = new Page($count,15);// 实例化分页类 传入总记录数和每页显示的记录数
        $list = $Recommend->order('sort DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
        
        $this->assign('list',$list);// 赋值数据集                
        $show = $Page->show();// 分页显示输出
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$pager); 
        return $this->fetch();
    }

    public function supplierRecommendHandle(){
        $data = I('post.');
        $i=Db::name('supplier_recommend')->where('supplier_name',$data['supplier_name'])->find();
        if ($i and empty($data['id'])) {
            $this->error("店铺已存在");
            exit;
        }
        $data['add_time'] = mktime($data['add_time']);
        $data['start_time'] = strtotime($data['start_time']);
        $data['end_time'] = strtotime($data['end_time']);
        if($data['act'] == 'add'){
            $r = DB::name('supplier_recommend')->insert($data);
            adminLog('添加精选商铺 '.input('supplier_name').'');
        }
        // dump($data['add_time']);
        
        if($data['act'] == 'edit'){
            $r = DB::name('supplier_recommend')->where('id',$data['id'])->save($data);
            adminLog('编辑精选商铺 '.input('supplier_name').'');
        }
        
        if($data['act'] == 'del'){
            $r = DB::name('supplier_recommend')->where('id', $data['id'])->delete();
            adminLog('删除精选商铺 '.$data['id'].'');
            if($r) exit(json_encode(1));
        }
        $referurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : Url::build('Admin/Ad/supplierRecommendList');
        if($r){
             $this->redirect('Admin/Ad/supplierRecommendList');
        }else{
            $this->error("操作失败",$referurl);
        }
    }


    /**
     * 添加修改送礼攻略分类
     * 
     */
    public function giftsCategory(){

        $GoodsLogic = new GoodsLogic();
        if(IS_GET)
        {
            $gifts_category_info = Db::name('GiftsCategory')->where('id='.I('GET.id',0))->find();
            $cat_list = Db::name('gifts_category')->where("parent_id = 0")->select(); // 已经改成联动菜单
            I('parent_id') && ($gifts_category_info['parent_id'] = I('parent_id'));
            $this->assign('cat_list',$cat_list);
            $this->assign('gifts_category_info',$gifts_category_info);
            return $this->fetch();
            exit;
        }

        $GiftsCategory = D('GiftsCategory'); //

        $type = I('id') > 0 ? 2 : 1; // 标识自动验证时的 场景 1 表示插入 2 表示更新
        //ajax提交验证
        if(I('is_ajax') == 1)
        {
            // 数据验证
            $validate = \think\Loader::validate('GiftsCategory');
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

                $GiftsCategory->data(I('post.'),true); // 收集数据
                $GiftsCategory->parent_id = I('parent_id');

                if($GiftsCategory->id > 0 && $GiftsCategory->parent_id == $GiftsCategory->id)
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
                    $GiftsCategory->isUpdate(true)->save(); // 写入数据到数据库
                   // $GoodsLogic->refresh_gift_cat(I('id'));
                    adminLog('编辑分类 '.input('name').'');
                }
                else
                {
                    $GiftsCategory->save(); // 写入数据到数据库
                    $insert_id = $GiftsCategory->getLastInsID();
                    //$GoodsLogic->refresh_gift_cat($insert_id);
                    adminLog('添加分类 '.input('name').'');
                }
                $return_arr = array(
                    'status' => 1,
                    'msg'   => '操作成功',
                    'data'  => array('url'=>Url::build('Admin/Ad/giftsCategoryList')),
                );
                $this->ajaxReturn($return_arr);

            }
        }

    }

    /**
     *  送礼攻略分类列表
     */
    public function giftsCategoryList(){
        if (I("parent_id")) {
            $parent_id=I("parent_id");
            // $sql = "SELECT a.*,COUNT(b.goods_id)num FROM ylt_gifts_category as a  LEFT JOIN ylt_goods as b on b.gift_cat_id=a.id WHERE a.parent_id=$parent_id GROUP BY a.id";
            $sql = "SELECT a.* FROM ylt_gifts_category as a WHERE a.parent_id=$parent_id GROUP BY a.id order by sort_order Desc";
            $cat_list = DB::query($sql);
            $this->assign('parent_id',$parent_id);
            $this->assign('cat_list',$cat_list);
            return $this->fetch("giftsChildList");
        }
        else{
            // $sql = "SELECT a.*,COUNT(c.goods_id)num FROM ylt_gifts_category as a LEFT JOIN ylt_gifts_category as b on a.id=b.parent_id LEFT JOIN ylt_goods as c on c.gift_cat_id=b.id WHERE a.parent_id=0 GROUP BY a.id";
            $sql = "SELECT a.*,SUM(b.goods_num)goods_num FROM ylt_gifts_category as a LEFT JOIN ylt_gifts_category as b on a.id=b.parent_id  WHERE a.parent_id=0 GROUP BY a.id order by sort_order Desc ";
            $cat_list = DB::query($sql);
            $this->assign('cat_list',$cat_list);
            return $this->fetch();
        }
        
    }
    
    /**
     * 删除送礼攻略分类
     */
    public function delGiftCategory(){
        $id = $this->request->param('id');
        // 判断子分类
        $GiftsCategory = Db::name("gifts_category");
        $count = $GiftsCategory->where("parent_id = {$id}")->count("id");
        $count > 0 && $this->error('该分类下还有分类不得删除!',Url::build('Admin/Ad/giftsCategoryList'));
        // 判断是否存在商品
        $goods_count = Db::name('Goods')->where("gift_cat_id = {$id}")->count('1');
        $goods_count > 0 && $this->error('该分类下有商品不得删除!',Url::build('Admin/Ad/giftsCategoryList'));
        // 删除分类
        DB::name('gifts_category')->where('id',$id)->delete();
        $this->success("操作成功!!!",Url::build('Admin/Ad/giftsCategoryList'));
    }

    // 添加商品弹出层
    public function search_gift_goods()
    {
        $GoodsLogic = new GoodsLogic;
        $cat_id=I("parent_id_1",0);
        $cat_id=I("parent_id_2")?I("parent_id_2"):$cat_id;
        $level_cat = $GoodsLogic->find_parent_cat($cat_id); // 获取分类默认选中的下拉框
        $cat_list = Db::name('goods_category')->where("parent_id = 0")->select(); // 已经改成联动菜单

        
        $gift_cat_id = I('gift_cat_id');
        $goods_id = Db::name('gifts_category')->where("id = {$gift_cat_id}")->getField("goods_id");
        $where = ' is_on_sale = 1 and examine=1 and is_designer = 0 and store_count>0 ';//搜索条件
        if (!empty($goods_id)) {
            $where .= " and goods_id not in ($goods_id) ";
        }
        I('intro') && $where = "$where and " . I('intro') . " = 1";
        if ($cat_id) {
            $this->assign('cat_id', $cat_id);
            $grandson_ids = getCatGrandson($cat_id);
            $where = " $where  and cat_id in(" . implode(',', $grandson_ids) . ") "; // 初始化搜索条件
        }
        if (I('brand_id')) {
            $this->assign('brand_id', I('brand_id'));
            $where = "$where and brand_id = " . I('brand_id');
        }
        if (!empty($_REQUEST['keywords'])) {
            $this->assign('keywords', I('keywords'));
            $where = "$where and (goods_name like '%" . I('keywords') . "%' or keywords like '%" . I('keywords') . "%')";
        }
        $count = Db::name('goods')->where($where)->count();
        $Page = new Page($count, 10);
        $goodsList = Db::name('goods')->where($where)->order('goods_id DESC')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $show = $Page->show();//分页显示输出
        $this->assign('page', $show);//赋值分页输出
        $this->assign('goodsList', $goodsList);
        $this->assign('cat_list', $cat_list);
        $this->assign('level_cat', $level_cat);
        $this->assign('gift_cat_id', $gift_cat_id);
        $this->assign('pager', $Page);//赋值分页输出
        $tpl = I('get.tpl', 'search_gift_goods');
        return $this->fetch($tpl);
    }

    // 添加送礼商品
    public function addGiftGoods()
    {   
        $gift_cat_id = I('gift_cat_id');
        $new_goods_id = I("post.goods_id/a");
        $now_goods_id = Db::name('gifts_category')->where("id = $gift_cat_id")->getField('goods_id');
        $now_goods_id&&$new_goods_id = array_merge(explode(",", $now_goods_id),$new_goods_id);
        $goods_num = count($new_goods_id);
        $new_goods_id = implode(",", $new_goods_id);
        Db::execute("UPDATE __PREFIX__gifts_category set  goods_id = '$new_goods_id',goods_num=$goods_num where id = $gift_cat_id");
        $this->success("操作成功!!!",Url::build('Admin/Ad/giftsCategoryList'));
    }
    
    //商品设置
    public function giftGoodsSet()
    {
        $GoodsLogic = new GoodsLogic;
        $cat_id=I("parent_id_1",0);
        $cat_id=I("parent_id_2")?I("parent_id_2"):$cat_id;
        $level_cat = $GoodsLogic->find_gift_parent($cat_id); // 获取分类默认选中的下拉框
        $cat_list = Db::name('gifts_category')->where("parent_id = 0")->select(); // 已经改成联动菜单

        $where = ' 1 = 1';//搜索条件
        
        if ($cat_id) {
            //$grandson_ids = getGiftGrandson($cat_id);
            //$where = " $where  and gift_cat_id in(" . implode(',', $grandson_ids) . ") "; // 初始化搜索条件
            $goods_id = Db::name('gifts_category')->where("id = $cat_id")->getField('goods_id');
            $goods_id = $goods_id?$goods_id:0;
            $where = " $where and goods_id in($goods_id)";
        }
        
        if (!empty($_REQUEST['keywords'])) {
            $this->assign('keywords', I('keywords'));
            $where = "$where and (goods_name like '%" . I('keywords') . "%' or keywords like '%" . I('keywords') . "%')";
        }
        $count = Db::name('goods')->where($where)->count();
        $Page = new Page($count, 10);
        $goodsList = Db::name('goods')->where($where)->order('goods_id DESC')->limit($Page->firstRow . ',' . $Page->listRows)->select(); 
        $show = $Page->show();//分页显示输出
        $this->assign('page', $show);//赋值分页输出
        $this->assign('goodsList', $goodsList);
        $this->assign('cat_list', $cat_list);
        $this->assign('cat_id', $cat_id);
        $this->assign('level_cat', $level_cat);
        $this->assign('pager', $Page);//赋值分页输出
        return $this->fetch();
    }

    /*
     * 获取送礼分类
     */
    public function get_gift_category(){
        $parent_id = I('get.parent_id/d'); // 商品分类 父id
            $list = Db::name('gifts_category')->where("parent_id", $parent_id)->select();
        foreach($list as $k => $v)
            $html .= "<option value='{$v['id']}'>{$v['name']}</option>";        
        exit($html);
    }

    // 删除送礼商品
    public function DelGiftGoods()
    {   
        $gift_cat_id = I('gift_cat_id');
        $del_goods_id = I("post.goods_id/a");
        $now_goods_id = Db::name('gifts_category')->where("id = $gift_cat_id")->getField('goods_id');
        $now_goods_id&&$now_goods_id = array_diff(explode(",", $now_goods_id),$del_goods_id);
        $goods_num = count($now_goods_id);
        $now_goods_id = implode(",", $now_goods_id);
        Db::execute("UPDATE __PREFIX__gifts_category set  goods_id = '$now_goods_id',goods_num=$goods_num where id = $gift_cat_id");
        $this->success("操作成功!!!",Url::build('Admin/Ad/giftsCategoryList'));
    }
}
 