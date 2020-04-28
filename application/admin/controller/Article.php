<?php
namespace ylt\admin\controller;
use ylt\admin\logic\ArticleCatLogic;
use think\Page;
use think\Db;
use think\Url;
use think\Request;
class Article extends Base {

    private $article_system_id = array(1, 2, 3, 4, 5,96);//系统默认的文章分类id，不可删除
    private $article_main_system_id = array(1, 2);//系统保留分类，不允许在该分类添加文章
    private $article_top_system_id = array(1);//系统分类，不允许在该分类添加新的分类
    private $article_able_id = array(1);//系统预定义文章id，不能删除。此文章用于商品详情售后服务

    public function _initialize()
    {
        parent::_initialize();
        $this->assign('article_top_system_id', $this->article_top_system_id);
        $this->assign('article_system_id', $this->article_system_id);
        $this->assign('article_main_system_id', $this->article_main_system_id);
        $this->assign('article_able_id',$this->article_able_id);
    }

    public function categoryList(){
        $ArticleCat = new ArticleCatLogic(); 
        $cat_list = $ArticleCat->article_cat_list(0, 0, false);
        $type_arr = array('系统默认','系统帮助','系统公告');
        $this->assign('type_arr',$type_arr);
        $this->assign('cat_list',$cat_list);
        return $this->fetch('categoryList');
    }
    
    public function category(){
        $ArticleCat = new ArticleCatLogic();  
 	$act = I('get.act','add');
        $this->assign('act',$act);
        $cat_id = I('get.cat_id/d');
        $cat_info = array();
        if($cat_id){
            $cat_info = Db::name('article_cat')->where('cat_id',$cat_id)->find();
            $this->assign('cat_info',$cat_info);
        }
        $cats = $ArticleCat->article_cat_list(0,$cat_info['parent_id'],true);
        $this->assign('cat_select',$cats);
        return $this->fetch();
    }
    
    public function articleList(){
        $Article =  Db::name('Article a');
        $list = array();
        $p = I('p/d', 1);
        $size = I('size/d', 20);
        $where = array();
        $keywords = trim(I('keywords'));
        $keywords && $where['a.title'] = array('like', '%' . $keywords . '%');
        $cat_id = I('cat_id/d',0);
        $cat_id && $where['a.cat_id'] = $cat_id;

        $join[] = array('ylt_article_comment b','a.article_id=b.article_id','left');
        $res = $Article->field ('a.*,count(b.article_id)comment')->join($join)->where($where)->group('a.article_id')->order('a.article_id desc')->page("$p,$size")->select();
       
        $count = $Article->where($where)->count();// 查询满足要求的总记录数
        $pager = new Page($count,$size);// 实例化分页类 传入总记录数和每页显示的记录数
        $page = $pager->show();//分页显示输出

        $ArticleCat = new ArticleCatLogic();
        $cats = $ArticleCat->article_cat_list(0,0,false);
        if($res){
        	foreach ($res as $val){
        		$val['category'] = $cats[$val['cat_id']]['cat_name'];
        		$val['add_time'] = date('Y-m-d H:i:s',$val['add_time']);        		
        		$list[] = $val;
        	}
        }
        $this->assign('cats',$cats);
        $this->assign('cat_id',$cat_id);
        $this->assign('list',$list);// 赋值数据集
        $this->assign('page',$page);// 赋值分页输出
        $this->assign('pager',$pager);
        return $this->fetch('articleList');
    }
    
    public function article(){
        $ArticleCat = new ArticleCatLogic();
        $act = I('get.act','add');
        $info = array();
        $info['publish_time'] = time();
        $article_id = I('get.article_id/d');
        if($article_id){
           $info = Db::name('article')->where('article_id', $article_id)->find();
        }
        $cats = $ArticleCat->article_cat_list(0,$info['cat_id']);    //第一个分类
        $cats2 = $ArticleCat->article_cat_list(0,$info['cat_id2']); //第二个分类
        
        $this->assign('cat_select',$cats);
        $this->assign('cat_select2',$cats2);
        
        $this->assign('act',$act);
        $this->assign('info',$info);
        $this->initEditor();
        return $this->fetch();
    }
    
     
    /**
     * 初始化编辑器链接
     * @param $post_id post_id
     */
    private function initEditor()
    {
        $this->assign("URL_upload", Url::build('Admin/Ueditor/imageUp',array('savepath'=>'article')));
        $this->assign("URL_fileUp", Url::build('Admin/Ueditor/fileUp',array('savepath'=>'article')));
        $this->assign("URL_scrawlUp", Url::build('Admin/Ueditor/scrawlUp',array('savepath'=>'article')));
        $this->assign("URL_getRemoteImage", Url::build('Admin/Ueditor/getRemoteImage',array('savepath'=>'article')));
        $this->assign("URL_imageManager", Url::build('Admin/Ueditor/imageManager',array('savepath'=>'article')));
        $this->assign("URL_imageUp", Url::build('Admin/Ueditor/imageUp',array('savepath'=>'article')));
        $this->assign("URL_getMovie", Url::build('Admin/Ueditor/getMovie',array('savepath'=>'article')));
        $this->assign("URL_Home", "");
    }
    
    
    public function categoryHandle(){
    	$data = I('post.');
        if($data['act'] == 'add'){           
            $d = Db::name('article_cat')->insert($data);
			adminLog('添加文章 '.input('title').'');
        }
        
        if($data['act'] == 'edit')
        {
        	if ($data['cat_id'] == $data['parent_id']) 
			{
        		$this->error("所选分类的上级分类不能是当前分类",Url::build('Admin/Article/category',array('cat_id'=>$data['cat_id'])));
        	}
        	$ArticleCat = new ArticleCatLogic();
        	$children = array_keys($ArticleCat->article_cat_list($data['cat_id'], 0, false)); // 获得当前分类的所有下级分类
        	if (in_array($data['parent_id'], $children))
        	{
        		$this->error("所选分类的上级分类不能是当前分类的子分类",Url::build('Admin/Article/category',array('cat_id'=>$data['cat_id'])));
        	}
        	$d = Db::name('article_cat')->where("cat_id",$data['cat_id'])->update($data);
			adminLog('编辑文章 '.input('title').'');
        }
        
        if($data['act'] == 'del'){
            if(array_key_exists($data['cat_id'],$this->article_system_id)){
                exit(json_encode('系统预定义的分类不能删除'));
            }
        	$res = Db::name('article_cat')->where('parent_id', $data['cat_id'])->select();
        	if ($res)
        	{
        		exit(json_encode('还有子分类，不能删除'));
        	}
        	$res = Db::name('article')->where('cat_id', $data['cat_id'])->select();
        	if ($res)
        	{
        		exit(json_encode('该分类下有文章，不允许删除，请先删除该分类下的文章.'));
        	}      	
        	$r = Db::name('article_cat')->where('cat_id', $data['cat_id'])->delete();
        	if($r) exit(json_encode(1));
        }
        if($d){
        	$this->success("操作成功",Url::build('Admin/Article/categoryList'));
        }else{
        	$this->error("操作失败",Url::build('Admin/Article/categoryList'));
        }
    }
    
    public function aticleHandle(){
        $data = I('post.');
        $data['content'] = I('content'); // 文章内容单独过滤
        $data['publish_time'] = strtotime($data['publish_time']);
        $url = $this->request->server('HTTP_REFERER');
        $referurl = !empty($url) ? $url : Url::build('Admin/Article/articleList');
        //$data['content'] = htmlspecialchars(stripslashes($_POST['content']));
        if($data['act'] == 'add'){
            if(array_key_exists($data['cat_id'],$this->article_main_system_id)){
             //   $this->error("不能在系统保留分类下添加文章,操作失败",$referurl);
            }
            // $data['click'] = mt_rand(1000,3300);
        	$data['add_time'] = time(); 
            $r = Db::name('article')->insert($data);
        }
        if ($data['publish_time'] < 1262275200) {  //小于2010年1月1号
            $data['publish_time'] = time();
        }
        if($data['act'] == 'edit'){
            $r = Db::name('article')->where('article_id', $data['article_id'])->update($data);
        }
        
        if($data['act'] == 'del'){
            if(array_key_exists($data['article_id'],$this->article_able_id)){
                exit(json_encode('系统预定义的文章不能删除'));
            }
        	$r = Db::name('article')->where('article_id', $data['article_id'])->delete();
        	if($r) exit(json_encode(1));       	
        }
        if($r){
            $this->success("操作成功",$referurl);
        }else{
            $this->error("操作失败",$referurl);
        }
    }
    
    
    public function link(){
    	$act = I('get.act','add');
    	$this->assign('act',$act);
    	$link_id = I('get.link_id/d');
    	$link_info = array();
    	if($link_id){
    		$link_info = Db::name('friend_link')->where('link_id', $link_id)->find();
    	}
        $this->assign('info',$link_info);
    	return $this->fetch();
    }
    
    public function linkList(){
    	$Ad =  Db::name('friend_link');
        $p = $this->request->param('p');
    	$res = $Ad->where('1=1')->order('orderby')->page($p.',10')->select();
    	if($res){
    		foreach ($res as $val){
    			$val['target'] = $val['target']>0 ? '开启' : '关闭';
    			$list[] = $val;
    		}
    	}
    	$this->assign('list',$list);// 赋值数据集
    	$count = $Ad->where('1=1')->count();// 查询满足要求的总记录数
    	$Page = new Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数
    	$show = $Page->show();// 分页显示输出
        $this->assign('pager',$Page);// 赋值分页输出
    	$this->assign('page',$show);// 赋值分页输出
    	return $this->fetch();
    }
    
    public function linkHandle(){
        $data = I('post.');
    	if($data['act'] == 'add'){
    		stream_context_set_default(array('http'=>array('timeout' =>2)));
//           send_http_status('311');
    		$r = Db::name('friend_link')->insert($data);
    	}
    	if($data['act'] == 'edit'){
    		$r = Db::name('friend_link')->where('link_id', $data['link_id'])->update($data);
    	}
    	
    	if($data['act'] == 'del'){
    		$r = Db::name('friend_link')->where('link_id', $data['link_id'])->delete();
    		if($r) exit(json_encode(1));
    	}
    	
    	if($r){
    		$this->success("操作成功",Url::build('Admin/Article/linkList'));
    	}else{
    		$this->error("操作失败",Url::build('Admin/Article/linkList'));
    	}
    }  

    public function changeIndex(){
        $Article =  Db::name('Article');
        $article_id = I('get.article_id/d');
        $is_ecommend = I('get.is_ecommend/d',0);

        $count = $Article->where('is_ecommend>0')->count();
        if ($count>=3&&$is_ecommend==0) {
            $data['res']=0;
            exit(json_encode($data));
        }
        $data['is_ecommend'] = $is_ecommend?0:time();
        $Article->where('article_id', $article_id)->update($data);
        $data['res']=1;
        $data['is_ecommend']=$data['is_ecommend'];
        exit(json_encode($data));
    }
    public function changeTop(){
        $Article =  Db::name('Article');
        $article_id = I('get.article_id/d');
        $is_top = I('get.is_top/d',0);

        $count = $Article->where('is_top>0')->count();
        if ($count>=3&&$is_top==0) {
            $data['res']=0;
            exit(json_encode($data));
        }
        $data['is_top'] = $is_top?0:time();
        $Article->where('article_id', $article_id)->update($data);
        $data['res']=1;
        $data['is_top']=$data['is_top'];
        exit(json_encode($data));
    }
    public function commentList(){
        $Comment =  Db::name('Article_comment a');
        $list = array();
        $p = I('p/d', 1);
        $size = I('size/d', 20);
        $where = array();
        $keywords = trim(I('keywords'));
        $keywords && $where['a.content'] = array('like', '%' . $keywords . '%');
        $cat_id = I('cat_id/d',0);
        $cat_id && $where['a.comment_id'] = $cat_id;
        //$res = $Comment->where($where)->order('article_id desc')->page("$p,$size")->select();
        $join[] = array('ylt_article b','a.article_id=b.article_id','left');
        $res = $Comment->field ('a.*,b.title,b.article_type,c.head_pic')->join($join)->join('ylt_users c','a.user_id=c.user_id','left')->where($where)->group('a.comment_id')->order('a.add_time desc')->page("$p,$size")->select();
        $count = $Comment->where($where)->count();// 查询满足要求的总记录数
        $pager = new Page($count,$size);// 实例化分页类 传入总记录数和每页显示的记录数
        $page = $pager->show();//分页显示输出

        $ArticleCat = new ArticleCatLogic();
        $cats = $ArticleCat->article_cat_list(0,0,false);
        if($res){
            foreach ($res as $val){
               
                $val['add_time'] = date('Y-m-d H:i:s',$val['add_time']);                
                $list[] = $val;
            }
        }
        $this->assign('cats',$cats);
        $this->assign('cat_id',$cat_id);
        $this->assign('list',$list);// 赋值数据集
        $this->assign('page',$page);// 赋值分页输出
        $this->assign('pager',$pager);
         return $this->fetch();
        
    }
    public function commentHandle(){
        $data = I('post.');

        if($data['act'] == 'del'){
            $r = Db::name('article_comment')->where('comment_id', $data['comment_id'])->delete();
            if($r) exit(json_encode(1));
        }
        
        if($r){
            $this->success("操作成功",Url::build('Admin/Article/commentList'));
        }else{
            $this->error("操作失败",Url::build('Admin/Article/commentList'));
        }
    
    }
  
  	public function purchase_info(){
        return $this->fetch();
    }
}