<?php
namespace ylt\home\controller;
use think\AjaxPage;
use think\Controller;
use think\Url;
use think\Page;
use think\Verify;
use think\Db;
use think\Cache;
use think\Request;

class Article extends Base {
    
    public function index(){       
      $article_id = I('article_id/d',38);
    	$article = Db::name('article')->where("article_id", $article_id)->find();
    	$this->assign('article',$article);
        return $this->fetch();
    }
 
    /**
     * 文章内列表页
     */
    public function articleList(){
        $article_cat = Db::name('ArticleCat')->where("parent_id  = 0")->select();
        $this->assign('article_cat',$article_cat);
        return $this->fetch();
    }    
	
	
    /**
     * 文章内容页
     */
    public function detail(){
		
    $article_id = I('article_id/d',1);

		$key = md5($_SERVER['REQUEST_URI'].I('article_id/d',1));
		$html =  Cache::get($key);  //读取缓存
		
		Db::name('article')->where("article_id", $article_id)->setInc('click'); //统计点击数
		
		if(!empty($html))
		{
			return $html;
		}
			
    	$article = Db::name('article')->where("article_id", $article_id)->find();
		 
    	if($article){
    		$parent = Db::name('article_cat')->where("cat_id",$article['cat_id'])->find();
    		$this->assign('cat_name',$parent['cat_name']);
    		$this->assign('article',$article);
    	}
		
		//常见问题分类
		$classification = Db::name('article_cat')->where("cat_id",in,"1,2,3,4,7")->select();
		//分类文章
		$classifiedArticle = Db::name('article')->where('cat_id',in,'1,2,3,4,7')->where('is_open',1)->order('add_time','desc')->limit(20)->select();
		
		$this->assign('classification',$classification);
		$this->assign('classifiedArticle',$classifiedArticle);
		
        $html = $this->fetch();
		Cache::set($key,$html);
		return $html;
    } 

    /**
     * @function businessPurchase() //企业采集
     * @return mixed
     */
    public function businessPurchase(){

        $purchase_list = Db::name('purchase')->where('status',1)->where('dead_time','>',time())->order('inquiry_time desc')->limit(0,12)->cache(true,3600)->select();
		    $purchase_lists = Db::name('purchase')->where('status',1)->where('dead_time','>',time())->order('inquiry_time desc')->limit(0,20)->cache(true,3600)->select();//获取采购信息
		    if(!$purchase_list){
    			$purchase_list = Db::name('purchase')->where('status',1)->where('dead_time','>',time())->order('inquiry_time desc')->limit(0,12)->cache(true,3600)->select();
        }

        $this->assign('purchase_list',$purchase_list);
		    $this->assign('purchase_lists',$purchase_lists);
        return $this->fetch();
    }
  
      public function purchase_info(){
      	  if(session('user')){
              $this->error('商家账号才能发布采购，请用商家账号登录！！',Url::build('Home/Business/login'));
          }
        if(session('supplier')){      
          $supplier=session('supplier');
          if( $supplier['state'] == 0){
          		$this->error('您还未完善商家信息，请完善！！',Url::build('Home/Business/BusinessOne'));
          }else if( $supplier['status'] == 0){
          		$this->error('商家信息审核中请您耐心等待！！',Url::build('Home/Business/BusinessIndex'));
          }else if($supplier['status'] == 2){
                $this->error('您提交商家信息审核未通过！！',Url::build('Home/Business/BusinessFour'));
          }else if($supplier['status'] == -1){
                $this->error('您的商家店铺已关闭！！',Url::build('Home/Business/BusinessFour'));
          }
          
 		}
        
         if(!session('supplier_id')){
          	session('loginUrl',$_SERVER['REQUEST_URI']);
        	$this->error('请先登录！！',Url::build('Home/Business/login'));
        }
     	
        $this->assign('supplier',$supplier);
        return $this->fetch();
    }

  //添加
   public function add_purchase(){
        $data = I('post.');
        $data['supplier_id'] = session('supplier_id') ? session('supplier_id') :41;
        // dump($data['supplier_id']);die;
        $data['inquiry_time'] = strtotime($data['inquiry_time']);
        $data['dead_time'] = strtotime($data['dead_time']);
        $data['expect_time'] = strtotime($data['expect_time']);
        $data_now=strtotime("now")-3600*24;
        $data['add_time'] = time();
        $data['sustomized']=I('sustomized');
//        $result=111;
//        $this->ajaxReturn($result);die();
        if(!(preg_match("/^1[345789]{1}\d{9}$/",$data['tel']))){
            $arr=array('status'=>"-1",'msg'=>"请输入正确的手机号");
            exit(json_encode($arr));
        }

        /*if($data['goods_count']>9999999999){
            $arr=array('status'=>"-1",'msg'=>"采购商品数量不能超过1亿");
            exit(json_encode($arr));
        }*/

        if(empty($data['goods_sn'])){
            $data['goods_sn'] = date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
        }

        if(!empty($data['hcity']) && !is_numeric($data['hcity'])){
            $rs=Db::name('region')->where( 'name',"{$data['hcity']}")->find();
            $data['province']=$rs['id'];
        }
        if(!empty($data['hproper']) && !is_numeric($data['hproper'])){
            $rs=Db::name('region')->where( 'name',"{$data['hproper']}")->find();
            $data['city']=$rs['id'];
        }
        if(!empty($data['harea']) && !is_numeric($data['harea'])){
            $rs=Db::name('region')->where( 'name',"{$data['harea']}")->find();
            $data['area']=$rs['id'];
        }
        if(empty($data['province']) || empty($data['city'])){
            $arr=array('status'=>"-1",'msg'=>"请输入正确地址");
            exit(json_encode($arr));
        }

        if(empty($data['region'])){
            $data['region'] = "全国";
        }


        if($data['inquiry_time']<$data_now){
            $arr=array('status'=>"-1",'msg'=>"请输入正确询价时间!");
            exit(json_encode($arr));
        }
        if($data['dead_time']<$data['inquiry_time']){
            $arr=array('status'=>"-1",'msg'=>"请输入正确截止时间!");
            exit(json_encode($arr));
        }
        if($data['expect_time']>$data['dead_time']){

            if($data['id']){
                $rs= Db::name('Purchase')->where('id',$data['id'])->where('supplier_id',session('supplier_id'))->update($data);
            }else{
                $Purchaseid=Db::name('Purchase')->insertGetId($data);
                if($Purchaseid){
                    $pl = [];
                    for ($x=0; $x<count($data['goods_name']); $x++) {
                          $pl[] =['purchase_id'=>$Purchaseid,'goods_number'=>$data['goods_number'][$x],'goods_name'=>$data['goods_name'][$x],
                                  'goods_norm'=>$data['goods_norm'][$x],'goods_color'=>$data['goods_color'][$x],'goods_brand'=>$data['goods_brand'][$x],
                                  'goods_unit'=>$data['goods_unit'][$x],'goods_num'=>$data['goods_num'][$x],'goods_img'=>$data['goods_img'][$x],
                                  'goods_remarks'=>$data['goods_remarks'][$x],'t'=>time(),'company_phone'=>$data['company_phone'],'email'=>$data['email'],
                             	  'addressxn'=>$data['addressxn'],'add_time'=>time(),'wxnum'=>$data['wx'],'qqnum'=>$data['qq']
                                 ];
                    }
                
                    $result = Db::name('purchase_list')->insertAll($pl);    // 批量添加
                                
                }
              
              
              Db::name('purchase')->where('supplier_id',$data['supplier_id'])->update(['operator'=>2]);
            }

            $rs=array('status'=>'1','msg'=>'编辑成功!');
            exit(json_encode($rs));
        }
        else{
            $rs=array('status'=>'-1','msg'=>'期望收货时间比截止时间晚!');
            exit(json_encode($rs));

        }


    }


    /**
     * @function moreBusinessTrade() //企业采集 -->  更多商机
     * @return mixed
     */
    public function moreBusinessTrade(){
        $p = I('p/d',1);
        $time = time();

       
        $count = DB::name('purchase')->where('status',1)->count();
		    $purchase_list = Db::name('purchase')->where('status',1)->where('inquiry_time','egt',$time)->order('inquiry_time desc')->page($p.',20')->cache(true,3600)->select();

        $page = new Page($count,20);
        if(!$purchase_list){
            $purchase_list =  Db::name('purchase')->where('status',1)->order('inquiry_time desc')->limit($page->firstRow.','.$page->listRows)->cache(true,3600)->select();
        }

        $this->assign('page',$page);
        $this->assign('purchase_list',$purchase_list);
        return $this->fetch();
    }

    /**
     * @function moreBusinessTrade() //企业采集 -->  即将到期
     * @return mixed
     */
    public function moreBusinessTrade2(){
        $p = I('p/d',1);
        $purchase_time=strtotime("now")+3600*24*7;
        $time = time();

        //$purchase_list = Db::name('purchase')->where('status',1)->where('dead_time','egt',$time)->where('dead_time','elt',$purchase_time)->order('dead_time')->page($p.',20')->cache(true,3600)->select();
		$purchase_list = Db::name('purchase')->where('status',1)->order('inquiry_time')->page($p.',20')->cache(true,3600)->select();
		 if(!$purchase_list){
			 $purchase_list =  Db::name('purchase')->where('status',1)->order('inquiry_time desc')->page('0,20')->cache(true,3600)->select();
		 }
        //$count = DB::name('purchase')->where('status',1)->where('dead_time','egt',$time)->where('dead_time','elt',$purchase_time)->count();
		$count = DB::name('purchase')->where('status',1)->count();
        $Page = new Page($count,20);
        $show = $Page->show();
        $this->assign('pager',$Page);
        $this->assign('page',$show);
        $this->assign('purchase_list',$purchase_list);
        return $this->fetch();
    }

     /**
     * @function tradeList() //企业采集 --> 企业采集单
     * @return mixed
     */
     public function tradeList(){
        $id = I('id');
        $goodsNum1=Db::name('purchase')->field('id')->where('id',$_GET['id'])->find();//查询采购是否存在
       	$goodsNum=Db::name('purchase_list')->field('id')->where('purchase_id',$id)->find();//获取purchase_list表的id
        if (!$goodsNum1 or !$goodsNum) {
           $this->error('该条采购信息已删除',Url::build('/businessPurchase'));
        }
         $purchase_list_id="";
         foreach($goodsNum as $key =>$value){
             $purchase_list_id.=$value;
         }
        $info = Db::name('purchase')->where('id',$id)->find();
        if(empty($info['sustomized']) || $info['sustomized']=='0'){
            $info['sustomized']='否';
        }else if($info['sustomized']=='1'){
            $info['sustomized']='定制';           
        }

        if(empty($info['goods_ask'])){
            $info['goods_ask']='无';
        }
        if(empty($info['lnvoice_title'])){
            $info['lnvoice_title']='否';
        }
        if(empty($info['quote_ask'])){
            $info['quote_ask']='无';
        }

       //采购商信息
        $supplier_info = Db::name('supplier')->where('supplier_id',$info['supplier_id'])->field('supplier_name,add_time,supplier_money,company_name,contacts_name,phone_number,email,introduction,add_time,logo,province,city,area,address')->find();
        $purchase_list = Db::name('purchase_list')->where('purchase_id',$info['id'])->select();
       	$getOneData=Db::name('purchase_list')->where(['purchase_id'=>$id,'id'=>$purchase_list_id])->select();//获取一个数据即可
       //----------------------------<start>添加虚拟数据----------------------------------
        $xnsj="";//获取虚拟的添加报价时间
        $company_phone="";//获取虚拟的座机号码
        $email="";//获取虚拟的电子邮箱
         $addressxn="";//获取虚拟的地址
         $wxnum="";//获取虚拟的微信
         $qqnum="";//获取虚拟的QQ
        foreach($getOneData as $value){
            $xnsj.=$value['add_time'];
            $company_phone.=$value['company_phone'];
            $email.=$value['email'];
            $addressxn.=$value['addressxn'];
            $wxnum.=$value['wxnum'];
            $qqnum.=$value['qqnum'];
        }
         $xncompany_name="";//获取虚拟的公司名字
         $xncontact_name="";//获取虚拟的联系人名字
         $purchaseData=Db::name('purchase')->field('company_name,contacts_name')->where('id',$id)->select();//获取purchase里的指定数据
         foreach ($purchaseData as $value) {
             $xncompany_name.=$value['company_name'];
             $xncontact_name.=$value['contacts_name'];
        }
//----------------------------</end>添加虚拟数据----------------------------------
        $region_list = get_region_list();
       
        $purchase=Db::name('purchase')
        			->field('p.title,p.id,p.expect_time,p.province,p.dead_time,s.logo,p.inquiry_time,p.be_viewed,p.quoted,p.budget,p.operator')
                    ->alias('p')
                    ->join('supplier s','p.supplier_id=s.supplier_id')
                    ->where('p.status=1 and p.id!='.$id)
                    ->order('p.id desc')
                    ->limit(0,6)
                    ->cache(true,3600)
                    ->select();//发现更多商机
        foreach($purchase as &$v){
             $v['budget'] = $v['budget'] / 10000;
         }
        $map=array();
        foreach ($purchase as $key=> $value) {
           $goods_list=Db::name('purchase_list')
                    ->field('goods_name,goods_img,goods_num,goods_unit')
                    ->where('purchase_id',$value['id'])
                    ->select();
            $value['list']= $goods_list; 
            $map[]=$value;      
        } 
         $supplier_info['address'] = $region_list[$supplier_info['province']]['name'] . $region_list[$supplier_info['city']]['name'] . $region_list[$supplier_info['area']]['name'] . $supplier_info['address'];
         $plist = Db::name('purchase')->alias('p')->join('purchase_list l','p.id = l.purchase_id')->where('p.status=1 and p.id!='.$id.' and  p.supplier_id="'.$info['supplier_id'].'"')->order('inquiry_time desc')->field('p.*')->limit(0,6)->cache(true,3600)->select();//获取采购信息
       	if(!session('?supplier_id') and !session('?user')){
          $info['tel'] = substr_replace($info['tel'],'****',3,4);
          $wxnum = substr_replace($wxnum,'****',2,4);
          $qqnum = substr_replace($qqnum,'****',3,4);
          $email = substr_replace($email,'****',3,4);
        }
        $this->assign('supplier',$supplier_info);
        $this->assign('purchase_list',$purchase_list);
        $this->assign('purchase',$purchase);
        $this->assign('map_list',$map);
        $this->assign('plist',$plist);
        $this->assign('info',$info);
       	//虚拟数据渲染start-----------------
        $this->assign('xnsj',$xnsj);
        $this->assign('xncompany_name',$xncompany_name);
        $this->assign('xncontact_name',$xncontact_name);
        $this->assign('company_phone',$company_phone);
        $this->assign('email',$email);
        $this->assign('address',$addressxn);
        $this->assign('wxnum',$wxnum);
        $this->assign('qqnum',$qqnum);
        //虚拟数据渲染end-----------------
        return $this->fetch();
     }

     public function search(){
       $keywords=I('keywords','');
       $where='status=1';
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
                
                $where .= " (`title` LIKE '%".$val."%' OR `company_name` LIKE '%".$val."%')";
            }
            
            $where .= ')';

        }else{
            
            $where .= " AND (`title` LIKE '%".$keywords."%' OR `company_name` LIKE '%".$keywords."%')";
        }
        $search_list = Db::name('purchase')->where($where)->order('inquiry_time desc')->limit(0,12)->cache(true,3600)->select();
        $search_lists = Db::name('purchase')->where($where)->order('inquiry_time desc')->limit(0,20)->cache(true,3600)->select();//获取采购信息

        $this->assign('search_list',$search_list);
        $this->assign('search_lists',$search_lists);
        return $this->fetch();
    }
     //立即报价
        public function quoteNow(){
         if(session('supplier')){      
          $supplier=session('supplier');
          if( $supplier['state'] == 0){
                $this->error('您还未完善商家信息，请完善！！',Url::build('Home/Business/BusinessOne'));
          }else if( $supplier['status'] == 0){
                $this->error('商家信息审核中请您耐心等待！！',Url::build('Home/Business/BusinessIndex'));
          }else if($supplier['status'] == 2){
                $this->error('您提交商家信息审核未通过！！',Url::build('Home/Business/BusinessFour'));
          }else if($supplier['status'] == -1){
                $this->error('您的商家店铺已关闭！！',Url::build('Home/Business/BusinessFour'));
          }
          
        }
         if(!session('supplier_id')){
            session('loginUrl',$_SERVER['REQUEST_URI']);
            $this->error('请先登录！！',Url::build('Home/Business/login'));
        }
        $id  = I('get.id');
        $purchase          = Db::name('purchase')->where('id',$id)->find();
        $purchase_list     =Db::name('purchase_list')->where('purchase_id',$id)->select();
        //dump($purchase);

        if(IS_POST){ 
            $data = I('post.');
          	$mobile=$data['phone'];
          	$code=$data['code'];
            $supply['title']=$data['title'];
            $supply['content']=$data['content'];
            $supply['phone']=$data['phone'];
            $supply['an_time']=strtotime($data['an_time']);
            $supply['purchase_id']=$data['purchase_id'];
            $supply['title']=$data['title'];
            $supply['end_time']=strtotime($data['end_time']);
            $supply['supplier_id'] = session('supplier_id');
            if(!(preg_match("/^1[34578]{1}\d{9}$/",$data['phone']))){
                $arr=array('status'=>"-1",'msg'=>"请输入正确的手机号");
                exit(json_encode($arr));
            }
            $supply['t']=time();
            $res=Db::name('supply')->insertGetId($supply);
            $goods_duration     =$data['goods_duration'];
            $goods_tprice       =$data['goods_tprice'];
            $goods_sprice       =$data['goods_sprice'];
            $goods_freight      =$data['goods_freight'];
            $purlist_id         =$data['purlist_id'];
            if($res){
                $supply_list=array();
                for($i=0;$i<count($goods_tprice);$i++){
                    $supply_list[$i]['goods_duration']=$goods_duration[$i];
                    $supply_list[$i]['goods_tprice']=$goods_tprice[$i];
                    $supply_list[$i]['goods_sprice']=$goods_sprice[$i];
                    $supply_list[$i]['goods_freight']=$goods_freight[$i];
                    $supply_list[$i]['purlist_id']=$purlist_id[$i];
                    $supply_list[$i]['t']=time();
                    $supply_list[$i]['supply_id']=$res;
                }
                $result=Db::name('supply_list')->insertAll($supply_list);
                if($result){
                    $content=array('status'=>'1','msg'=>'报价成功!');
                    exit(json_encode($content));
                }else{

                    $content=array('status'=>'2','msg'=>'报价出错，请重试!');
                    exit(json_encode($content));
                }

            }
        }
          
        if(!$purchase){
            exit($this->error('采购商品不存在'));
        }
         
        $this->assign('purchase_list',$purchase_list); 
        $this->assign('purchase',$purchase); 
        return $this->fetch();
    }

}