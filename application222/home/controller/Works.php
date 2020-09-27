<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/10
 * Time: 16:10
 */
namespace ylt\home\controller;
use think\Controller;
use think\Url;
use think\Page;
use think\Db;

class Works extends Base {


    /**
    * @function index() //设计师--所有作品商品首页
    * @return mixed
    */
    public function index(){

		$WCategory = Db::name('works_category')->order("sort_order")->limit(10)->select();
		$GoodsCategory = Db::name('GoodsCategory')->where('parent_id',11)->limit(10)->select();
		$worksList = Db::name('Works')->where("examine",1)->order("add_time desc")->limit(10)->select();
		$goods_list = Db::name('goods')->where("is_designer = 1 and examine = 1 and is_on_sale = 1")->order("sales_sum")->limit(10)->select();

        $rs=array('status'=>'1','info'=>'请求成功','worksList'=>$worksList,'goods_list'=>$goods_list,'GoodsCategory'=>'','WCategory'=>'','action'=>'index');
        exit(json_encode($rs));  
    }


    // /**
    // * @function designsDetails() //设计师--作品详情页
    // * @return mixed
    // */
    // public function designsDetails(){
    //        return $this->fetch();
    // }



    /**
    * @function worksDetails() //设计师--商品详情页
    * @return mixed
    */
    public function WorksDetails(){
    
    	$works_id = I('id/d,0');

        $click_count = Db::name('works')->where("works_id", $works_id)->setInc('click_count'); //统计点击数
    	
    	$works_list = Db::name('works')->where(array('works_id'=>$works_id,'examine'=>1))->find();
        if(empty($works_list['last_update'])){
            $works_list['last_update'] = $works_list['add_time'];
        }

    	$supplier_list = Db::name('supplier')->where(array('supplier_id'=>$works_list['supplier_id'],'user_id'=>$works_list['user_id']))->find();

    	$comment_list = Db::name('works_comment')->where('works_id',$works_list['works_id'])->group('add_time desc')->limit(10)->select();

        $rs=array('status'=>'1','info'=>'请求成功','works_list'=>$works_list,'supplier_list'=>$supplier_list,'comment_list'=>$comment_list,'click_count'=>$click_count);
        exit(json_encode($rs));  
        // $region_list = get_region_list();
        // $this->assign('region_list',$region_list);
        // $this->assign('works_list',$works_list);
        // $this->assign('supplier_list',$supplier_list);
        // $this->assign('comment_list',$comment_list);
        // return $this->fetch();
    }


    //作品评论
    public function WorksComment(){
    	$user_id = I('user_id');   
        $user = Db::name('users')->where('user_id',$user_id)->find();
    	if(empty($user)){
    		$rs=array('status'=>'-1','msg'=>'请登陆后再评论!!!');
            exit(json_encode($rs));
    	}
    	
    	$data = I('post.');
    	
        if(empty($data['content'])){
            $rs=array('status'=>'-1','msg'=>'无内容提交评论失败,请重试!!!');
            exit(json_encode($rs));
        }

        $sql = ['user_id' => $user_id,
                'works_id' => $data['works_id'],
                'user_name' => $user['nickname'],
                'head_pic' => $user['head_pic'],
                'content' => $data['content']
               ];

        $sql['add_time'] = time();
        $sql['parent_id'] = 0;
        $sql['collect_count'] = 1;

        $comment = Db::name('works_comment')->where(array('works_id'=>$sql['works_id'],'user_id'=>$sql['user_id']))->find();
        
        if(!empty($comment)){
            $rs=array('status'=>'-1','msg'=>'只能评论一次!!!');
            exit(json_encode($rs));
        }

    	$comment_list = Db::name('works_comment')->where(array('works_id'=>$sql['works_id'],'user_id'=>$sql['user_id']))->insert($sql);

    	$count = Db::name('works_comment')->where(array('works_id'=>$sql['works_id'],'parent_id'=>0))->count();
    	$works['comment_count'] = $count;
    	//更新评论数
    	$works_list = Db::name('works')->where('works_id',$sql['works_id'])->setField($works);
    	
        $rs=array('status'=>'1','msg'=>'评论成功!!!');
        exit(json_encode($rs));
    	
    }
    
    //作品回复--暂时取消
    // public function WorksReply(){
    //     $user_id = I('user_id');   
    //     $user = Db::name('users')->where('user_id',$user_id)->find();
    // 	if(empty($user)){
    // 		$rs=array('status'=>'-1','msg'=>'请登陆后再评论!!!');
    //         exit(json_encode($rs));
    // 	}

    // 	$data = I('post.');
   
    // 	if(empty($data['content'])){
    //         $rs=array('status'=>'-1','msg'=>'回复失败,请重试!!!');
    //         exit(json_encode($rs));
    //     }

    //     $sql = ['user_id' => $user['user_id'],
    //             'works_id' => $data['works_id'],
    //             'user_name' => $user['nickname'],
    //             'content' => $data['content']
    //          ];

    //     $sql['add_time'] = time();
    //     $sql['parent_id'] = 0;
    //     $sql['collect_count'] = 1;

    // 	$comment_list = Db::name('works_comment')->where(array('works_id'=>$sql['works_id'],'user_id'=>$sql['user_id']))->insert($sql);

    // 	$count = Db::name('works_comment')->where(array('works_id'=>$sql['works_id'],'collect_count'=>1))->count();
    // 	$works['comment_count'] = $count;
    // 	//更新回复数
    // 	$works_list = Db::name('works')->where('works_id',$sql['works_id'])->setField($works);
    	
    //     $rs=array('status'=>'1','msg'=>'回复成功!!!');
    //     exit(json_encode($rs));

    // }
    
    //作品收藏数
    public function collect_works(){
        $user_id = I('user_id');   
        $user = Db::name('users')->where('user_id',$user_id)->find();
    	if(empty($user)){
    		$rs=array('status'=>'-1','msg'=>'必须登录后才能收藏!!!');
            exit(json_encode($rs));
    	}
    	$data = I('post.');

        $sql = ['user_id' => $user['user_id'],
                'works_id' => $data['works_id']
             ];
        $sql['add_time'] =time();
		
		$rs =Db::name('works_collect')->where(array('works_id'=>$sql['works_id'],'user_id'=>$sql['user_id']))->find();
		if(!empty($rs)){
			$rs=array('status'=>'-1','msg'=>'已收藏,个人中心查看!');
			exit(json_encode($rs));
		}

    	$collect_list = Db::name('works_collect')->where(array('works_id'=>$sql['works_id'],'user_id'=>$sql['user_id']))->insert($sql);
    	
    	$count = Db::name('works_collect')->where('works_id',$sql['works_id'])->count();
    	$works['collect_count'] = $count;
    	//更新收藏数
    	$works_list = Db::name('works')->where('works_id',$sql['works_id'])->setField($works);

    	$rs=array('status'=>'1','msg'=>'收藏成功!!!');
        exit(json_encode($rs));
    }




    /**
    * @function personalIndex() //设计师--个人首页--个人作品
    * @return mixed
    */
    public function personalIndex(){
        $p = I('p/d',1);
        $supplier_id = I('id/d,0');
    
        $supplierList = Db::name('supplier')->where(array('supplier_id'=>$supplier_id,'is_designer'=>1))->find();

        $worksList = Db::name('works')->where(array('supplier_id'=>$supplier_id,'examine'=>1))->page($p.',20')->order("$sort")->select();

        $count = Db::name('works')->where(array('supplier_id'=>$supplier_id,'examine'=>1))->count();
        $Page = new Page($count,20);
        $show = $Page->show();


        $rs=array('status'=>'1','msg'=>'收藏成功!!!','supplierList'=>$supplierList,'worksList'=>$worksList,'count'=>$count,'Page'=>$Page,'page'=>$show);
        exit(json_encode($rs));

        // $region_list = get_region_list();
        // $this->assign('region_list',$region_list);
        // $this->assign('su',$supplierList);
        // $this->assign('work',$worksList);
        // $this->assign('count',$count);
        // $this->assign('page',$Page);// 赋值分页输出
        // $this->assign('page',$show);
        // return $this->fetch();

    }



    /**
    * @function personalSelling() //设计师--个人首页--在售作品
    * @return mixed
    */
    public function personalSelling(){
        $p = I('p/d',1);
        $supplier_id = I('id/d,0');

        $where = " examine = 1 and is_designer = 1 and is_on_sale = 1 ";

        $supplier_id && ($where.= " and supplier_id = '".$supplier_id."'");

        $supplierList = Db::name('supplier')->where(array('supplier_id'=>$supplier_id,'is_designer'=>1))->find();

        $goodsList = Db::name('goods')->where($where)->page($p.',20')->order("$sort")->select();

        $count = Db::name('goods')->where($where)->count();

        $Page = new Page($count,20);
        $show = $Page->show();

        $rs=array('status'=>'1','msg'=>'收藏成功!!!','supplierList'=>$supplierList,'goodsList'=>$goodsList,'count'=>$count,'Page'=>$Page,'page'=>$show);
        exit(json_encode($rs));

        // $region_list = get_region_list();
        // $this->assign('region_list',$region_list);
        // $this->assign('su',$supplierList);
        // $this->assign('good',$goodsList);
        // $this->assign('count',$count);
        // $this->assign('page',$Page);// 赋值分页输出
        // $this->assign('page',$show);
        // return $this->fetch();

    }

    /**
    * @function Search()  //设计师---关注
    * @return mixed
    */
    public function collect_supplier(){
        $supplier_id = I('id/d');
        if(!I('user_id')){
            exit(json_encode(['status'=>-1,'msg'=>'请先登录！']));
        }

        $user_id = I('user_id');
        $add['user_id'] = intval($user_id);
        $add['supplier_id'] = $supplier_id;

        $rss = Db::name('supplier_collect')->where(array('supplier_id'=>$add['supplier_id'],'user_id'=>$add['user_id'] ))->find();
        
        if(!empty($rss)){
            $rs = Db::name('supplier_collect')->where(array('supplier_id'=>$add['supplier_id'],'user_id'=>$add['user_id'] ))->delete();
            $rs = array('status'=>-1,'msg'=>'取消关注！');
            exit(json_encode($rs));
        }
        
        $add['add_time'] = time();
        Db::name('supplier_collect')->insert($add);

        $rs = array('status'=>1,'msg'=>'关注成功！');
        exit(json_encode($rs));
    }



    /**
    * @function personalInfos() //设计师--个人首页--个人信息
    * @return mixed
    */
    public function personalInfos(){
        $supplier_id = I('id/d,0');
     
        $supplierList = Db::name('supplier')->where(array('supplier_id'=>$supplier_id,'is_designer'=>1))->find();

        $userList = Db::name('users')->where(array('user_id'=>$supplierList['user_id'],'is_designer'=>1))->find();

        $rs=array('status'=>'1','msg'=>'收藏成功!!!','supplierList'=>$supplierList,'userList'=>$userList);
        exit(json_encode($rs));

        // $region_list = get_region_list();
        // $this->assign('region_list',$region_list);
        // $this->assign('su',$supplierList);
        // $this->assign('user',$userList);
        // return $this->fetch();

    }



  //   /**
  //   * @function designsList() //设计师--在售商品--更多
  //   * @return mixed
  //   */
  //   public function designsList(){
		
		// $keywords = urldecode(trim(I('keywords','')));
		// $cat = I('get.cat',0);
		// $where = " examine = 1 and is_designer = 1 and is_on_sale ";
		// $cat && ($where.= " and cat_id = '".$cat."'");
		
		// if(!empty($keywords)){
		// 	$arr = array();
		// 	if (stristr($keywords, ' AND ') !== false)
		// 	{
		// 		/* 检查关键字中是否有AND，如果存在就是并 */
		// 		$arr        = explode('AND', $keywords);
		// 		$operator   = " AND ";
		// 	}
		// 	elseif (stristr($keywords, ' OR ') !== false)
		// 	{
		// 		/* 检查关键字中是否有OR，如果存在就是或 */
		// 		$arr        = explode('OR', $keywords);
		// 		$operator   = " OR ";
		// 	}
		// 	elseif (stristr($keywords, ' + ') !== false)
		// 	{
		// 		/* 检查关键字中是否有加号，如果存在就是或 */
		// 		$arr        = explode('+', $keywords);
		// 		$operator   = " OR ";
		// 	}
		// 	else
		// 	{
		// 		/* 检查关键字中是否有空格，如果存在就是并 */
		// 		$arr        = explode(' ', $keywords);
		// 		$operator   = " AND ";
		// 	}

		// 	$where .= ' AND (';
		// 	foreach ($arr AS $key => $val)
		// 	{
		// 		if ($key > 0 && $key < count($arr) && count($arr) > 1)
		// 		{
		// 			$where .= $operator;
		// 		}
		// 		$where .= " (`goods_name` LIKE '%".$val."%' OR `keywords` LIKE '%".$val."%' OR `goods_sn` LIKE '%".$val."%.' )";

		// 		if(Db::name('keywords')->where('keyword',$val)->value('keyword'))
		// 			DB::name('keywords')->where('keyword',$val)->setInc('count');
		// 		else
		// 			Db::name('keywords')->insert(['date'=>date('Y-m-d'),'searchengine'=>'ylt','keyword'=>$val,'count'=> 1,'source'=>'pc']);
		// 	}
			
		// 	$where .= ')';

		// }else{
		// 	if($keywords)
		// 	$where .= " AND (`goods_name` LIKE '%".$keywords."%' OR `keywords` LIKE '%".$keywords."%' OR `goods_sn` LIKE '%".$keywords."%.' )";
		// }
		
		
		// $count = Db::name('goods')->where($where)->count();
		// $page = new Page($count,20);
		// $goods_list = Db::name('goods')->where($where)->order("$sort")->limit($page->firstRow.','.$page->listRows)->select();
 
		
		// $GoodsCategory = Db::name('GoodsCategory')->where('parent_id',11)->select();
		
		// $this->assign('GoodsCategory',$GoodsCategory);
		// $this->assign('goods_list',$goods_list);
  //       $this->assign('count',$count);
  //       $this->assign('action','designsList');
		// $this->assign('page',$page);// 赋值分页输出
  //       return $this->fetch();

  //   }



  //   /**
  //   * @function worksList() //设计师--作品列表--更多
  //   * @return mixed
  //   */
  //   public function worksList(){
		
		// $sort = I('get.sort','sort'); // 排序
		// $cat = I('get.cat',0);
		// $keywords = urldecode(trim(I('keywords','')));
		// $where = " examine = 1 ";
		 
		// $cat && ($where.= " and cat_id = '".$cat."'");
		 
		// if(!empty($keywords)){
			
		// 	$arr = array();
		// 	if (stristr($keywords, ' AND ') !== false)
		// 	{
		// 		/* 检查关键字中是否有AND，如果存在就是并 */
		// 		$arr        = explode('AND', $keywords);
		// 		$operator   = " AND ";
		// 	}
		// 	elseif (stristr($keywords, ' OR ') !== false)
		// 	{
		// 		/* 检查关键字中是否有OR，如果存在就是或 */
		// 		$arr        = explode('OR', $keywords);
		// 		$operator   = " OR ";
		// 	}
		// 	elseif (stristr($keywords, ' + ') !== false)
		// 	{
		// 		/* 检查关键字中是否有加号，如果存在就是或 */
		// 		$arr        = explode('+', $keywords);
		// 		$operator   = " OR ";
		// 	}
		// 	else
		// 	{
		// 		/* 检查关键字中是否有空格，如果存在就是并 */
		// 		$arr        = explode(' ', $keywords);
		// 		$operator   = " AND ";
		// 	}

		// 	$where .= ' AND (';
		// 	foreach ($arr AS $key => $val)
		// 	{
		// 		if ($key > 0 && $key < count($arr) && count($arr) > 1)
		// 		{
		// 			$where .= $operator;
		// 		}
		// 		$where .= " (`works_name` LIKE '%".$val."%')";
		// 	}
			
		// 	$where .= ')';

		// }else{
		// 	if($keywords)
		// 	$where .= " AND (`works_name` LIKE '%".$keywords."%' )";
		// }
		
		// $count = Db::name('Works')->where($where)->count();
		// $page = new Page($count,20);
		// $worksList = Db::name('Works')->where($where)->order("$sort")->limit($page->firstRow.','.$page->listRows)->select();
		
		// $WCategory = Db::name('works_category')->where("is_show= 1")->order("sort_order")->limit(10)->select();
		
		
		// $this->assign('WCategory',$WCategory);
		// $this->assign('page',$page);// 赋值分页输出
		// $this->assign('worksList',$worksList);
		//  $this->assign('action','worksList');
  //       return $this->fetch();

  //   }



}