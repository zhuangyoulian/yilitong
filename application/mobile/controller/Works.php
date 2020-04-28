<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/4
 * Time: 11:48
 */
namespace ylt\mobile\controller;
use think\Controller;
use think\Url;
use think\Page;
use think\Db;
header("Content-type: text/html; charset=utf-8");

class Works extends MobileBase {


    /**
    * @function DesignerCenter1()  //设计师---设计师中心
    * @return mixed
    */
    public function DesignerCenter1(){
        $p = I('p/d',0);
        $supplier_id = I('id/d,0');     

        $supplierList = Db::name('supplier')->where(array('supplier_id'=>$supplier_id,'is_designer'=>1))->find();

        $userList = Db::name('users')->where('user_id',$supplierList['user_id'])->find();
        //更多他的作品
        $count = Db::name('works')->where(array('user_id'=>$supplierList['user_id'],'examine'=>1))->count();
        $page = new Page($count,5);

        $worksList = Db::name('works')->where(array('user_id'=>$supplierList['user_id'],'examine'=>1))->order('add_time desc')->limit($page->firstRow.','.$page->listRows)->select();

        $this->assign('supplierList',$supplierList);
        $this->assign('worksList',$worksList);
        $this->assign('userList',$userList);
        $this->assign('count',$count);
        $this->assign('page',$page);
        
        if($p){
            return $this->fetch('ajax_works_center');
        }

        return $this->fetch();
    }



    /**
    * @function DesignerCenter2()  //设计师---设计师中心
    * @return mixed
    */
    public function DesignerCenter2(){
        $p = I('p/d',0);
        $supplier_id = I('id/d,0');

        $where = " supplier_id = $supplier_id and examine = 1 and is_designer = 1 and is_on_sale =1 ";

        $supplierList = Db::name('supplier')->where(array('supplier_id'=>$supplier_id,'is_designer'=>1 ))->find();
        
        $userList = Db::name('users')->where('user_id',$supplierList['user_id'])->find();
        
        //粉丝值       
        //$countCollect = Db::name('supplier_collect')->where(array('supplier_id'=>$supplier_id,'user_id'=>$supplierList['user_id']))->count();
        
        $count = Db::name('goods')->where($where)->count();
        $page = new Page($count,5);
        $goodsList = Db::name('goods')->where($where)->order("$sort")->limit($page->firstRow.','.$page->listRows)->select();
        
        
        $this->assign('supplierList',$supplierList);
        $this->assign('userList',$userList);
        $this->assign('goodsList',$goodsList);
        //$this->assign('countCollect',$countCollect);
        $this->assign('count',$count);
        $this->assign('page',$page);// 赋值分页输出

        if($p){
            return $this->fetch('ajax_goods_center');
        }

        return $this->fetch();
    }


    /**
    * @function Search()  //设计师---关注
    * @return mixed
    */
    public function collect_supplier(){
        $supplier_id = I('id/d');
        if(!cookie('user_id'))
            exit(json_encode(['status'=>-1,'msg'=>'请先登录！']));

        $user_id = cookie('user_id');
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
    * @function Search()  //设计师---搜索
    * @return mixed
    */
    public function Search(){
           return $this->fetch();
    }



    /**
    * @function WorksIndex()  //设计师---找设计--设计作品
    * @return mixed
    */
    public function WorksIndex(){
           return $this->fetch();
    }



    /**
    * @function GoodsIndex()  //设计师---找设计--在售作品
    * @return mixed
    */
    public function GoodsIndex(){
           return $this->fetch();
    }



    /**
    * @function WorksList()  //设计师---设计作品列表
    * @return mixed
    */
    public function WorksList(){
       // $p = I('p');
        $p = I('p/d',0);
       
        $sort = I('get.sort','sort'); // 排序
        $cat = I('get.cat',0);
        $keywords = urldecode(trim(I('keywords','')));
        $where = " examine = 1 ";
         
        $cat && ($where.= " and cat_id = '".$cat."'");
         
        if(!empty($keywords)){
            
            $arr = array();
            if (stristr($keywords, ' AND ') !== false)
            {
                /* 检查关键字中是否有AND，如果存在就是并 */
                $arr        = explode('AND', $keywords);
                $operator   = " AND ";
            }
            elseif (stristr($keywords, ' OR ') !== false)
            {
                /* 检查关键字中是否有OR，如果存在就是或 */
                $arr        = explode('OR', $keywords);
                $operator   = " OR ";
            }
            elseif (stristr($keywords, ' + ') !== false)
            {
                /* 检查关键字中是否有加号，如果存在就是或 */
                $arr        = explode('+', $keywords);
                $operator   = " OR ";
            }
            else
            {
                /* 检查关键字中是否有空格，如果存在就是并 */
                $arr        = explode(' ', $keywords);
                $operator   = " AND ";
            }

            $where .= ' AND (';
            foreach ($arr AS $key => $val)
            {
                if ($key > 0 && $key < count($arr) && count($arr) > 1)
                {
                    $where .= $operator;
                }
                $where .= " (`works_name` LIKE '%".$val."%')";
            }
            
            $where .= ')';

        }else{
            if($keywords)
            $where .= " AND (`works_name` LIKE '%".$keywords."%' )";
        }
        
       
        $count = Db::name('Works')->where($where)->count();
        $page = new Page($count,5);
        $worksList = Db::name('Works')->where($where)->order("$sort")->limit($page->firstRow.','.$page->listRows)->select();


        $this->assign('page',$page);// 赋值分页输出
        $this->assign('worksList',$worksList);
        if($p){
            return $this->fetch('ajax_works_list');
        }
        return $this->fetch();

    }



    /**
    * @function GoodsList()  //设计师---在售作品列表
    * @return mixed
    */
    public function GoodsList(){
     
        $p = I('p/d',0);

        $keywords = urldecode(trim(I('keywords','')));
       
        $where = " examine = 1 and is_designer = 1 and is_on_sale ";
        
        
        if(!empty($keywords)){
            $arr = array();
            if (stristr($keywords, ' AND ') !== false)
            {
                /* 检查关键字中是否有AND，如果存在就是并 */
                $arr        = explode('AND', $keywords);
                $operator   = " AND ";
            }
            elseif (stristr($keywords, ' OR ') !== false)
            {
                /* 检查关键字中是否有OR，如果存在就是或 */
                $arr        = explode('OR', $keywords);
                $operator   = " OR ";
            }
            elseif (stristr($keywords, ' + ') !== false)
            {
                /* 检查关键字中是否有加号，如果存在就是或 */
                $arr        = explode('+', $keywords);
                $operator   = " OR ";
            }
            else
            {
                /* 检查关键字中是否有空格，如果存在就是并 */
                $arr        = explode(' ', $keywords);
                $operator   = " AND ";
            }

            $where .= ' AND (';
            foreach ($arr AS $key => $val)
            {
                if ($key > 0 && $key < count($arr) && count($arr) > 1)
                {
                    $where .= $operator;
                }
                $where .= " (`goods_name` LIKE '%".$val."%' OR `keywords` LIKE '%".$val."%' OR `goods_sn` LIKE '%".$val."%.' )";

                if(Db::name('keywords')->where('keyword',$val)->value('keyword'))
                    DB::name('keywords')->where('keyword',$val)->setInc('count');
                else
                    Db::name('keywords')->insert(['date'=>date('Y-m-d'),'searchengine'=>'ylt','keyword'=>$val,'count'=> 1,'source'=>'pc']);
            }
            
            $where .= ')';

        }else{
            if($keywords)
            $where .= " AND (`goods_name` LIKE '%".$keywords."%' OR `keywords` LIKE '%".$keywords."%' OR `goods_sn` LIKE '%".$keywords."%.' )";
        }
        
        
        $count = Db::name('goods')->where($where)->count();
        $page = new Page($count,5);
        $goods_list = Db::name('goods')->where($where)->order("$sort")->limit($page->firstRow.','.$page->listRows)->select();
        
        //$GoodsCategory = Db::name('GoodsCategory')->where('parent_id',11)->select();
  
       // $this->assign('GoodsCategory',$GoodsCategory);
        $this->assign('goods_list',$goods_list);
        $this->assign('page',$page);// 赋值分页输出

        if($p){
            return $this->fetch('ajax_goods_list');
        }
        return $this->fetch();

    }



    /**
    * @function WorksDetail()  //设计师---设计作品详情页
    * @return mixed
    */
    public function WorksDetail(){

        $works_id = I('id/d,0');
        $uid = I('uid/d');
        

        $works_list = Db::name('works')->where(array('works_id'=>$works_id,'examine'=>1))->find();

        $supplier_list = Db::name('supplier')->where(array('supplier_id'=>$works_list['supplier_id'],'user_id'=>$works_list['user_id']))->find();

        $user_list = Db::name('users')->where('user_id',$works_list['user_id'])->find();

        
        //更多他的作品
        if(empty($uid)){

            $works_more = Db::name('works')->where(array('user_id'=>$works_list['user_id'],'examine'=>1))->order('add_time desc')->limit(3)->select();
            
        }else{
            $works_more = Db::name('works')->where(array('user_id'=>$uid,'examine'=>1))->order('add_time desc')->select();
        }

        $this->assign('works_list',$works_list);
        $this->assign('supplier_list',$supplier_list);
        $this->assign('user_list',$user_list);
        $this->assign('works_more',$works_more);

        return $this->fetch();
    }



    /**
    * @function GoodsDetail()  //设计师---在售作品详情页
    * @return mixed
    */
    public function GoodsDetail(){
             

        return $this->fetch();
    }



    /**
    * @function Comment()  //设计师---设计作品评论
    * @return mixed
    */
    public function Comment(){
        $works_id = I('id/d,0');
        
        $comment_list = Db::name('works_comment')->where('works_id',$works_id)->group('add_time desc')->limit(10)->select();

        $this->assign('comment_list',$comment_list);
        return $this->fetch();
    }

    /**
    * @function Comment()  //设计师---在售作品评论
    * @return mixed
    */
    public function goodsComment(){
        $works_id = I('id/d,0');
        
        $comment_list = Db::name('works_comment')->where('works_id',$works_id)->group('add_time desc')->limit(10)->select();

        $this->assign('comment_list',$comment_list);
        return $this->fetch();
    }



    /**
    * @function GoodsInfo()  //设计师---在售作品详情页
    * @return mixed
    */
    public function GoodsInfo(){

        //  form表单提交      
        $goodsLogic = new \ylt\home\logic\GoodsLogic();
        $goods_id = I("get.id/d");
        $goods = Db::name('Goods')->where("goods_id",$goods_id)->find();

        
        if(empty($goods) || ($goods['is_on_sale'] == 0)){
            $this->error('该商品已经下架',Url::build('Index/index'));
        }
    
        if($goods['brand_id']){
            $brnad = Db::name('brand')->where("id",$goods['brand_id'])->find();
            $goods['brand_name'] = $brnad['name'];
        }  

        $supplier = Db::name('Supplier')->where('supplier_id',$goods['supplier_id'])->find();

        $user_list = Db::name('users')->where('user_id',$supplier['user_id'])->find();

        $goods_images = Db::name('goods_images')->where('goods_id',$goods['goods_id'])->select();

        $comment = Db::name('comment')->where(array('goods_id'=>$goods['goods_id'],'is_show'=>1))->order('add_time desc')->select();
        
        $goods_images_list = Db::name('GoodsImages')->where("goods_id", $goods_id)->order('img_id desc')->select(); // 商品 图册
    
               
        //商品是否正在促销中        
        if($goods['prom_type'] == 1 || $goods['prom_type'] == 5)
        {
            $goods['flash_sale'] = get_goods_promotion($goods['goods_id']);
            $flash_sale = Db::name('panic_buying')->where("id", $goods['prom_id'])->find();
            $this->assign('flash_sale',$flash_sale);
        }

        $this->assign('commentStatistics',$commentStatistics);//评论概览
        $this->assign('filter_spec',$filter_spec);//规格参数
        $this->assign('goods_images_list',$goods_images_list);//商品缩略图
        $this->assign('siblings_cate',$goodsLogic->get_siblings_cate($goods['cat_id']));//相关分类
        //$this->assign('look_see',$goodsLogic->get_look_see($goods));//看了又看      
        $this->assign('goods',$goods);
        $this->assign('supplier',$supplier);
        $this->assign('goods_images',$goods_images);
        $this->assign('comment',$comment);      //评论
        $this->assign('user_list',$user_list);  

        return $this->fetch();
    }


    /**
     * 用户收藏作品
     * @param type $goods_id
     */
    public function collect_works()
    {
        $works_id = I('id/d');
        $user = session('user');
        $user_id = $user['user_id'];
        //dump($works_id);die;
        if (!is_numeric($user_id) || $user_id <= 0) return array('status' => -1, 'msg' => '必须登录后才能收藏', 'result' => array());
        $count = Db::name('WorksCollect')->where("user_id",$user_id)->where("works_id", $works_id)->count();
        if ($count > 0) return array('status' => -4, 'msg' => '商品已收藏', 'result' => array());
        Db::name('WorksCollect')->insert(array('works_id' => $works_id, 'user_id' => $user_id, 'add_time' => time()));
        return array('status' => 1, 'msg' => '收藏成功!请到个人中心查看', 'result' => array());

        exit(json_encode($result));
    }







}