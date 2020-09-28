<?php if (!defined('THINK_PATH')) exit(); /*a:4:{s:40:"./application/home/view/index\index.html";i:1595995262;s:42:"./application/home/view/public\header.html";i:1594029927;s:46:"./application/home/view/public\siteTopbar.html";i:1597649658;s:43:"./application/home/view/public\service.html";i:1539315244;}*/ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="edge" />
    <link rel="shortcut icon" type="image/x-icon" href="__PUBLIC__/images/favicon.ico" media="screen"/>
    <title>一礼通 | 企业礼品解决方案一站式服务平台</title>
    <meta name="description" content="深圳市一礼通互创科技有限公司 | 专注礼品服务，打造礼品行业B2B平台，为广大礼品供应商、礼品公司、礼品采购企业提供：商务礼品定制、深圳礼品定制、创意礼品、企业礼品、办公礼品、节日礼品、公司礼品、员工福利礼品等礼品，提供礼品采购、礼品设计、礼品方案优化、礼品营销等一站式服务。">
    <meta name="keywords" content="礼品网站|商务礼品|礼品企业|深圳礼品|创意礼品定制|礼品设计|员工福利礼品|礼品采购|礼品供应商|礼品批发|节日礼品|礼品采购|慰问品|企业福利解决方案">
</head>
<script>
    (function(){
        var src = (document.location.protocol == "http:") ? "http://js.passport.qihucdn.com/11.0.1.js?c12971e4ce93c84e8b9463d0e90d57b1":"https://jspassport.ssl.qhimg.com/11.0.1.js?c12971e4ce93c84e8b9463d0e90d57b1";
        document.write('<script src="' + src + '" id="sozz"><\/script>');
    })();
</script>
<link rel="stylesheet" href="/public/yilitong/css/style.css" type="text/css">
<link rel="stylesheet" href="/public/yilitong/css/index.css" type="text/css">
<link rel="stylesheet" href="/public/yilitong/css/procurement.css" type="text/css">
<link rel="stylesheet" href="/public/yilitong/css/carousel.css">
<link rel="stylesheet" href="/public/yilitong/css/famousBrand.css">

<script src="/public/yilitong/js/jquery-3.4.1.min.js"></script>
<script src="/public/yilitong/js/index.js"></script>
<script src="/public/yilitong/js/axios.min.js"></script>
<script src="/public/layui/layui.all.js"></script>
<style>
    /* .subitems{width:400px;height:400px;background: #f00;postion:absolute;z-index:99999999;}*/
    *{overflow-x:visible}
    .purchase-col {
        width: 251px;
        height: auto;
        padding: 20px;
        background: #fff;
        margin-right: 12px;
        float: left;
        margin-bottom: 20px;
        position: relative;
    }
    .classdivdiv000 {
        width: 760px;
        margin-right: 5px;
        margin-left: 200px;
        position: absolute;
        height:400px;
        background: rgba(128, 124, 124, 0.1);
        z-index:999999;
        display:none;
        top:250px;
    }
    .classdivdiv001 {
        width: 760px;
        margin-right: 5px;
        margin-left: 200px;

        position: absolute;
        height:400px;
        background: rgba(128, 124, 124, 0.1);
        z-index:999999;
        display:none;
        top:250px;
    }
    .purchase-col .line {
        width: 0;
        height: 4px;
        background: yellowgreen;
        position: absolute;
        left: 50%;
        top: 0;
        transition: all 0.3s ease 0.1s;
    }
    .purchase-title {
        font-size: 14px;
        font-weight: bold;
        height: 40px;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }
    .purchase-time-num {
        font-size: 12px;
        margin-top: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid #ccc;
    }
    .purchase-time-num .color-1 {
        color: #cdae8a;
    }
    .purchase-time-num .marginTop30 {
        margin-top: 35px;
    }
    .purchase-container .purchase-col .company {
        height: 20px;
        line-height: 20px;
        white-space: nowrap;
        text-overflow: ellipsis;
        overflow: hidden;
    }
    .purchase-price {
        display: block;
        font-size: 14px;
        font-weight: bold;
        text-align: center;
        padding-top: 10px;
    }

    /* 品牌换一批样式*/
    #iconWall{
        /*width: 732px;
        padding-top: 1px;
        padding-left: 1px;*/
        background-color: #f0f0f0;
        margin: 0 auto;
        overflow: hidden;
        list-style: none;
        width:750px;
    }

    #next_brand:hover{
        transition: 0s;
    }
    #iconWall li{
        width: 140px;
        height: 120px;
        border: 1px #f2f2f2 solid;
        /*
        background-color: #fff;
        margin-right: 1px;
        margin-bottom: 1px;
        */
        float: left;
        position: relative;
        padding:4px;
    }

    /*二、3D反转区域处理 */
    #iconWall  .img-back,#iconWall .img-front{
        position: absolute;
        left: 0;
        top: 0;
        background-color: #fff;
        width: 140px;
        height: 120px;
        text-align: center;
        line-height: 180px;
    }



    .img-3d{
        transform-style: preserve-3d;
    }

    #iconWall .img-back{
        transform: rotateY(180deg);
    }


    /*四、处理浮层*/
    #iconWall  .mask{
        position: absolute;
        left: 5px;
        top: 5px;
        width: 140px;
        height: 120px;
        text-align: center;
        background-color: rgba(0, 0, 0, .7);
        opacity: 0;
    }

    #iconWall  .mask p{
        font-size: 12px;
        color: #fff;
        width:140px;
        margin: 30px auto;
        text-align: center;
    }
    #iconWall  .mask a{
        color: #fff;
        text-decoration: none;
        font-size: 12px;
        background-color: red;
        width: 80px;
        display: block;
        margin: -35px auto;
        text-align: center;
        height: 20px;
        line-height: 20px;
        border-radius: 10px;
    }

    #iconWall li:hover .mask{
        opacity: 1;
        transition: .3s linear;
    }
    /* 供应商换一批样式*/
    #Wall{
        background-color: #f0f0f0;
        margin: 0 auto;
        overflow: hidden;
        list-style: none;
    }

    #next_supplier:hover{
        transition: 0s;
    }



    #Wall li{
        width: 107px;
        height: 85px;
        border: 1px #f2f2f2 solid;
        float: left;
        position: relative;
        margin-bottom:13px;
    }

    /*二、3D反转区域处理 */
    #Wall .img-back,#Wall .img-front{
        position: absolute;
        left: 0;
        top: 0;
        background-color: #fff;
        width: 108px;
        height: 67px;
        text-align: center;
        line-height: 100px;
    }

    #Wall li img{
        vertical-align: middle;
    }

    #Wall .img-back{
        transform: rotateY(180deg);
    }
    /*测试代码
    #iconWall li:hover .img-3d{
        transition: .3s linear;
        transform: rotateY(180deg);
    }
    */


    /*四、处理浮层*/
    #Wall  .mask{
        position: absolute;
        left:0px;
        top: 17px;
        width: 108px;
        height: 68px;
        text-align: center;
        background-color: rgba(0, 0, 0, .7);
        opacity: 0;
    }

    #Wall .mask a{
        color: #fff;
        text-decoration: none;
        font-size: 12px;
        background-color: red;
        width: 70px;
        display: block;
        margin: 40px auto 0px;
        text-align: center;
        height: 20px;
        line-height: 20px;
        border-radius: 10px;
    }

    #Wall li:hover .mask{
        opacity: 1;
        transition: .3s linear;
    }
    .listimg{
        width: 545px;
        padding: 10px;
        float: right;
    }
    .bj_01{
        background: url(public/images/spring.png) no-repeat 0px 30px;
        background-size:100%;height:auto;
        position: relative;
    }
    .fireworks1{
        position: absolute;
        left: -80px;
        top:0px;
    }
    .fireworks2{
        position: absolute;
        right: 0;
        top:30px;
    }
    .wrapadver{
        position: fixed;
        bottom: 0;
        right: 0;
        width: 306px;
        height: 200px;
        box-shadow: -1px -1px 10px 0px rgba(0,0,0,0.2);
        z-index: 99999999 !important;
        box-sizing: border-box;
        background: #fff;
    }
    .advertising_ch{
        width: 258px;
        margin: 0 auto;
    }
    .advertising_ch .border{
        border-bottom: 1px solid #e5e5e5;
        margin-bottom: 12px;
    }
    .advertising_ch .title{
        font-size: 16px;
        margin: 12px auto;
    }
    .advertising_ch>table>tr{
        margin-top: 24px;
    }
    .advertising_ch td{
        font-size: 14px;
    }
    .advertising_ch>.title>img{
        width: 16px;height: 16px;
        position: relative;
        top: 3px;left: 28px;
    }
    table{
        border-collapse: inherit !important;
    }
    .telstyle{
        color: #E6002d;
    }


</style>
<body>

<script src="__PUBLIC__/js/jquery-1.10.2.min.js"></script>
<script src="__PUBLIC__/js/global.js"></script>
<style>
.text{text-indent:10px;}
.logo{text-align:left;}
.navlist li:hover a{text-decoration:none !important;}
  .Finddiv2li1 li{
    width:50px;height: 25px;float: left; text-align: center;line-height: 25px;font-size:14px !important;
}
  .onli{
    background: #E6002D;
    color: #fff;
}
  .allgoods2 h2 {
    line-height: 1px;
    height: 38px;
    text-align: center;
    color: #fff;
    font-size: 14px;
    font-weight: bold;
}
</style>
<!--最顶部-->
<link rel="stylesheet" href="__STATIC__/css/index.css" type="text/css">
<link rel="stylesheet" href="__STATIC__/css/service.css" type="text/css">

<div class="site-topbar">

    <div class="layout">
        <div class="t1-l">
            <ul class="t1-l-ul">
                <li class="t1font nologin" style="color:#8c8c8c">一礼通平台欢迎商家入驻，咨询采购中秋节礼品！</li>
				<li class="t1img">&nbsp;</li> 
				<li class="t1font nologin"><a href="<?php echo Url('Home/User/user_login'); ?>" rel="nofollow" style="color:red">个人登录</a></li>
				<li class="t1img">&nbsp;</li> 
				<li class="t1font nologin">&nbsp;<em style="color:#8c8c8c">|</em>&nbsp;</li>
				<li class="t1img">&nbsp;</li> 				
				<li class="t1font nologin"><a href="<?php echo Url('Home/User/register'); ?>" rel="nofollow">注册</a></li>
				<li class="t1img">&nbsp;</li> 
            </ul>
        </div>
        <div class="t1-r" style="width:90%;margin-left:10%;">
            <ul class="t1-r-ul islogin" style="display:none;float:left;width:55%;margin-left: -110px;" id="t1-r-ul">
                <li class="t1font" style="color:#8c8c8c">一礼通平台欢迎商家入驻，咨询采购中秋节礼品！</li>
                <li class="t1font"> <a href="<?php echo Url('Home/User/index'); ?>" class="logon userinfo" rel="nofollow"></a></li>
                <li class="t1img"></li>				
                <li class="t1font font_t1"><a href="<?php echo Url('Home/User/order_list'); ?>" rel="nofollow">我的一礼通</a>
					<ul class="t2font">
						<li class="t1font"><a href="<?php echo Url('Home/User/index'); ?>"  style="margin-left:15px;" rel="nofollow">个人中心</a></li>
						<li class="t1img"></li>
						<li class="t1font"><a href="<?php echo Url('Home/User/order_list'); ?>" style="margin-left:15px;" rel="nofollow">我的订单</a></li>
						<li class="t1img"></li>
						<li class="t1font"><a href="<?php echo Url('Home/User/address_list'); ?>" style="margin-left:15px;" rel="nofollow">收货地址</a></li>
						<li class="t1img"></li>
					</ul>
				</li> 
				<li class="t1font"><a href="<?php echo Url('Home/user/logout'); ?>" style="margin-left:15px;" rel="nofollow">安全退出</a></li>
				<?php if($user['exchange_points'] > '0'): ?>
				<li class="t1font"><a href="<?php echo Url('Home/Supplieract/exchange'); ?>" style="margin-left:15px;color:red;" rel="nofollow">兑换专区</a></li><?php endif; ?>
				
				<li class="t1img"></li>						
            </ul>
            <ul class="t1-r-ul isSuplogin" id ="t1-r-ul1" style="display;float:left;margin-left: -110px;">
                <li class="t1font" style="color:#8c8c8c">一礼通平台欢迎商家入驻，咨询采购中秋节礼品！</li>
                <li class="t1font"> <a href="<?php echo Url('Home/Business/BusinessIndex'); ?>" class="logon supinfo" rel="nofollow"></a></li>
                <li class="t1img">&nbsp;</li>			
				<li class="t1font"><a href="<?php echo Url('Home/Business/logout'); ?>" style="margin-left:15px;" rel="nofollow">安全退出</a></li>
				<?php if($user['exchange_points'] > '0'): ?>
				<li class="t1font"><a href="<?php echo Url('Home/Supplieract/exchange'); ?>" style="margin-left:15px;color:red;" rel="nofollow">兑换专区</a></li><?php endif; ?>
				<li class="t1img"></li>						
            </ul>
			<ul class="t1-r" id="tl-r" style="width:50%;float:right;">
				<li>
					<p class="contactImg"><?php echo $config['shop_info_phone']; ?></p>
					<p class="contactImg"><?php echo $config['shop_info_mobile']; ?></p>
				</li>
				<li class="nologin t1">
					<em style="color:#8c8c8c;padding:0 5px;">|</em>
					<span class="t3font"><a href="javascript:;">商家服务</a>
						<ul class="t3" style="display:none;z-index:999999;">
							<li><a href="<?php echo Url('Home/Business/login'); ?>" rel="nofollow">商家登录</a></li>
							<li></li>
							<li><a href="<?php echo Url('Home/Business/BusinessIndex'); ?>" rel="nofollow">入驻申请</a></li>
							<li></li>
							<!--<li><a href="">入驻指南</a></li>-->
							<li></li>
							<li><a href="http://yilitong.com/Article/43.html">商家规则</a></li>
							<li></li>
						</ul>
					</span>
				</li>
				<li class="r ylt_dow">
					<span style="color:#8c8c8c;position:relative;">
						<a href="javascript:;" id="downLoadCode">
							掌上一礼通
							<div id="codeImg" style="width:150px;height:150px;overflow:hidden;position:absolute;left:-44px;top:22px;display:none;z-index:99999999999;">
								<img src="__STATIC__/images/downLoadCode.png" alt="下载一礼通APP" style="width:100%;height:100%;">
							</div>
						</a>
					</span>
				</li>
			</ul>
       </div>
    </div>
  

</div>
<script src="__PUBLIC__/js/jquery-1.10.2.min.js"></script>

<!-- 首页js集合 -->
<script src="__STATIC__/js/pc_index.js"></script>

<script>
	$(function(){

		var active_li = '<?php echo $active; ?>';
		if(active_li){
			$('li').remove('curr-res');
			$('#'+active_li).addClass('curr-res');
		}

	})
</script>



 <!--------在线客服-------------->

<!-- 代码部分begin -->
<style>
  .qqserver1 .qqserver_fold1 {
    position: absolute;
    right: 0;
    cursor: pointer;
    border-top-left-radius: 4px;
    border-bottom-left-radius: 4px;
    background: #e63547;
}
  .qqserver1 {
    position: fixed;
    top: 50%;
    right: 0;
    margin-top: -104px;
     height: 209px; 
 
    z-index: 999;
}
  .qqserver_fold1 div {
    width: 40px;
    height: 178px;
    background-image: url(/public/yilitong/images/zaixian1.png);
    background-position: 0 0;
}
  .qqserver-body1{
  height:178px;
    width:142px;
   background-image: url(/public/yilitong/images/zaixian2.png);
    
  }
</style>
<div class="qqserver">
  <div class="qqserver_fold">
    <div></div>
  </div>
  <div class="qqserver-body" style="display: block;">
    <div class="qqserver-header" style="cursor:pointer;">
        <div></div>
        <span class="qqserver_arrow"></span> </div>
		<ul>
			<li> <a title="点击这里给我发消息" href="http://wpa.qq.com/msgrd?v=3&uin=<?php echo $config['shop_info_qq']; ?>&site=qq&menu=yes" target="_blank" rel="nofollow">
				<div>在线客服</div>
				<span>客服</span> </a> </li>
			<li> <a title="点击这里给我发消息" href="http://wpa.qq.com/msgrd?v=3&uin=<?php echo $config['shop_info_qq2']; ?>&site=qq&menu=yes" target="_blank" rel="nofollow">
				<div>在线客服</div>
				<span>资讯</span> </a> </li>
			<li> <a title="点击这里给我发消息" href="http://wpa.qq.com/msgrd?v=3&uin=<?php echo $config['shop_info_qq3']; ?>&site=qq&menu=yes" target="_blank" rel="nofollow">
				<div>售后客服</div>
				<span>售后</span> </a>
			</li>
		</ul>
    
    <!--div class="qqserver-footer"><span class="qqserver_icon-alert"></span><a class="text-primary" href="javascript:;">大家都在说</a> </div-->
  </div>
</div>

<div class="qqserver1" style="top:650px;">
  <div class="qqserver_fold1">
    <div></div>
  </div>
  <div class="qqserver-body1" style="display: none;">
   
		
    
    <!--div class="qqserver-footer"><span class="qqserver_icon-alert"></span><a class="text-primary" href="javascript:;">大家都在说</a> </div-->
  </div>
</div>

<script>
$(function(){
	var $qqServer = $('.qqserver');
	var $qqserverFold = $('.qqserver_fold');
	var $qqserverUnfold = $('.qqserver-header');
	$qqserverFold.click(function(){
		$qqserverFold.hide();
		$qqServer.addClass('unfold');
	});
	$qqserverUnfold.click(function(){
		$qqServer.removeClass('unfold');
		$qqserverFold.show();
	});
	//窗体宽度小于1024像素时不显示客服QQ
	function resizeQQserver(){
		$qqServer[document.documentElement.clientWidth < 1024 ? 'hide':'show']();
	}
	$(window).bind("load resize",function(){
		resizeQQserver();
	});
   $(".qqserver_fold1").mouseenter(function(){
     $(".qqserver-body1").css("display","block");
   })
   $(".qqserver_fold1").mouseleave(function(){
     $(".qqserver-body1").css("display","none");
   })
});
</script>
<!-- 代码部分end -->
 <!--------在线客服-------------->

<header style="height:130px;">
    <div class="layout">
    <!--logo开始-->
     <!--   <h1 class="logo"><a href="/" title="一礼通"><img src="<?php echo $config['shop_info_store_logo']; ?>" alt="【一礼通商城】礼品商城_礼品采购_高端创意礼品_大型礼品网站" height="90px" ></a></h1>-->
        <h1 class="logo"><a href="/" title="礼品">礼品</a></h1>
     
    <!--logo结束-->
    <!-- 搜索开始-->
        
        <?php if($controller=='Index'){$controller='Goods'; }?>
        <div class="searchBar" style="padding-top:0px">
          <div style="height:25px;margin-top:30px;">
            <ul class="Finddiv2li1">
                <?php if(is_array($search_url) || $search_url instanceof \think\Collection): $k = 0; $__LIST__ = $search_url;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$item): $mod = ($k % 2 );++$k;?> 
                  <li <?php if(in_array(($controller), is_array(explode('/',$item['url']))?explode('/',$item['url']):explode(',',explode('/',$item['url'])))): ?>  class="onli" <?php endif; ?> name='<?php echo Url($item['url']); ?>' onchange="getsearch(<?php echo $k; ?>)" ><?php echo $item['name']; ?></li>
                <?php endforeach; endif; else: echo "" ;endif; ?> 
            </ul>
          </div>
            <div class="searchBar-form">
                <form name="sourch_form" id="sourch_form" method="post" action="<?php echo Url('/Home/Goods/search'); ?>">
                    <!--<select id="search-select" style="float:left;height:34px;outline:none;">-->
                        <!--<option value ="商品">商品</option>-->
                        <!--<option value ="店铺">店铺</option>-->
                        <!--<option value ="店铺">设计师</option>-->
                    <!--</select>-->
                    <input type="text" class="text" style="width:500px;line-height:33px;height:33px;border-radius: 5px;" name="keywords" id="keywords" value="<?php echo input('keywords'); ?>"  placeholder="搜索关键字" rel="nofollow" style="display:inline-block;height:34px;padding:0;font-size:14px;"/>
                    <input type="button" class="button" value="搜索" onclick="if($.trim($('#keywords').val()) != '') $('#sourch_form').submit();"/>
                </form>
            </div>
            <div class="searchBar-hot">
                <b>热门搜索</b>
                <?php if(is_array($config['hot_keywords']) || $config['hot_keywords'] instanceof \think\Collection): if( count($config['hot_keywords'])==0 ) : echo "" ;else: foreach($config['hot_keywords'] as $k=>$wd): ?>
                    <a target="_blank" href="<?php echo Url('Home/Goods/search',array('q'=>$wd)); ?>" rel="nofollow" <?php if($k == 0): ?>class="ht"<?php endif; ?>><?php echo $wd; ?></a>
                <?php endforeach; endif; else: echo "" ;endif; ?>
            </div>
        </div>
        <!-- 搜索结束-->
        <div class="ri-mall" style="margin-top: 55px;border:0;">
            <div class="my-mall" id="header_cart_list">
                <!---购物车-开始 -->
                <div class="micart" style="float:left;">
                    <div class="le les"  style="float:left;margin-right:10px;margin-left:10px;text-align: center;">                 
                        <a href="<?php echo Url('Home/Cart/cart'); ?>" rel="nofollow">我的购物车
                            <em id="cart_quantity" style="color:#fff;"></em>
                        </a>                       
                    </div>
                    <div class="ri ris">
                    <!-- 购物车隐藏列表 -->
                    </div>
                </div>
                <!---购物车-结束 -->
              <a href="<?php echo Url('Article/purchase_info'); ?>" rel="nofollow">
                <div class="le les" style="float:left;margin-right:10px;text-align:center;">
                        发布采购单      
                </div>
              </a>      
            </div>
        </div>
    </div>
</header>
<head>
<script>
var _hmt = _hmt || [];
(function() {
  var hm = document.createElement("script");
  hm.src = "https://hm.baidu.com/hm.js?7fb90411d45975d31698b327c980e88b";
  var s = document.getElementsByTagName("script")[0]; 
  s.parentNode.insertBefore(hm, s);
})();
</script>
</head>

<!-- 导航 - 开始 -->
<div class="navigation" style="background:#E6002D;">
<div class="layout" >
<!--全部商品-开始-->
<div class="allgoods">
  <a href="/Home/Goods/more" style="text-decoration:none;"><div class="allgoods2"><i class="trinagle"></i><h2 class="fa fa-tasks">全部商品</h2></div></a>
</div> 
<!--全部商品-结束-->
<div class="ongoods">
    <ul class="navlist">
      <li id="li1"  class="homepage <?php if($action == 'index' || 'double_eleven'): ?> navCur <?php endif; ?> "  style="width:70px;margin-left:25px;margin-right:25px;text-align: center;"><a href="/"  style="color:#fff;">首页</a></li>
      <li  id="li2" style="width:100px;margin-left:25px;margin-right:25px;text-align: center;"><a href="<?php echo Url('home/Article/businessPurchase'); ?>" style="color:#fff;">名企直采</a></li>
      <li id="li3" style="width:100px;margin-left:25px;margin-right:25px; text-align: center;"><a href="<?php echo Url('Index/mall'); ?>" style="color:#fff;">旗舰商城</a></li>
      <li id="li4"  style="width:100px;margin-left:25px;margin-right:25px; text-align: center;"><a href="<?php echo Url('Home/Works/index'); ?>" style="color:#fff;">自主设计</a></li>
      <!-- <li id="li5"  style="width:100px;margin-left:25px;margin-right:25px; text-align: center;"><a href="<?php echo Url('Home/Business/BusinessIndex'); ?>" style="color:#fff;">商家入驻</a></li> -->
      <li id="li6"  style="width:100px;margin-left:25px;margin-right:25px; text-align: center;"><a href="<?php echo Url('/goodsList/1118'); ?>" style="color:#fff;">医用专区</a></li>
      <!-- <li id="li7"  style="width:100px;margin-left:25px;margin-right:25px; text-align: center;"><a href="<?php echo Url('Home/Charity/donationlove'); ?>" style="color:#fff;">申请爱心捐赠</a></li> -->
      <!-- <li id="li7"  style="width:100px;margin-left:25px;margin-right:25px; text-align: center;"><a href="<?php echo Url('Home/Goods/goodsFestival_index'); ?>" style="color:#fff;">端午方案</a></li> -->
    </ul>
</div>
</div>
</div>
<!-- 导航-结束-->
<script>
    function get_cart_num(){
      var cart_cn = getCookie('cn');
      if(cart_cn == ''){
        $.ajax({
            type : "GET",
            url:"/index.php?m=Home&c=Cart&a=header_cart_list",//+tab,
            success: function(data){
                cart_cn = getCookie('cn');
                $('#cart_quantity').html(cart_cn);
            }
        });
      }
      $('#cart_quantity').html(cart_cn);
    }

    get_cart_num();
</script>
<div class="layout classification" >
    <div class="classdiv" >
        <div class="classdiv1">
            <div  class="classdiv11  ondiv dhzt">送礼导航</div>
            <div  class="classdiv12 dhzt" style="display:block;">商品分类</div>
        </div>
        <!--场景分类 -->
        <!--场景分类 -->
        <div class="classdiv2">
            <div class="classdiv21">
                <ul class="list_ul2" 　style="opacity:1;">
                    <?php if(is_array($scenario_category_tree) || $scenario_category_tree instanceof \think\Collection): $_5f713b35a3398 = is_array($scenario_category_tree) ? array_slice($scenario_category_tree,0,9, true) : $scenario_category_tree->slice(0,9, true); if( count($_5f713b35a3398)==0 ) : echo "" ;else: foreach($_5f713b35a3398 as $k=>$v): if($v[level] == 1): ?>
                            <li class="list-li list-li_<?php echo $v['id']; ?>">
                                <div class="list_a2">
                                    <h3>
                                        <a href="<?php echo Url('Home/Goods/scenarioList',array('id'=>$v[id])); ?>" class="title">
                                            <span style="font-size: 14px;float: left;width: 77px;margin-left: -30px;margin-top: 3px;" class="zt"><i class="list_ico2"></i><?php echo $v['name']; ?></span>
                                        </a>
                                        <?php if(is_array($v[tmenu]) || $v[tmenu] instanceof \think\Collection): $_5f713b35a3398 = is_array($v[tmenu]) ? array_slice($v[tmenu],0,4, true) : $v[tmenu]->slice(0,4, true); if( count($_5f713b35a3398)==0 ) : echo "" ;else: foreach($_5f713b35a3398 as $k2=>$v2): if($v2[parent_id] == $v[id]): ?>
                                                <div style="display: flex;flex-direction: row;float:left;width:72px;">
                                                    <div style="flex:1; padding-bottom: 5px;" class="two"><a href="<?php echo Url('Home/Goods/scenarioList',array('id'=>$v2[id])); ?>" class="zt"><?php echo $v2['name']; ?></a></div>
                                                </div>
                                            <?php endif; endforeach; endif; else: echo "" ;endif; ?>
                                    </h3>
                                </div>
                                <div class="list_b" style="left: 214px;top:46px;height:400px">
                                    <div class="subitems">
                                        <?php if(is_array($v[tmenu]) || $v[tmenu] instanceof \think\Collection): if( count($v[tmenu])==0 ) : echo "" ;else: foreach($v[tmenu] as $k2=>$v2): if($v2[parent_id] == $v[id]): ?>
                                                <dl class="ma-to-20 cl-bo">
                                                    <dt class="bigheader wh-sp"><a href="<?php echo Url('Home/Goods/scenarioList',array('id'=>$v2[id])); ?>"><?php echo $v2['name']; ?></a><i>＞</i></dt>
                                                    <dd class="ma-le-100">
                                                        <?php if(is_array($v2[sub_menu]) || $v2[sub_menu] instanceof \think\Collection): if( count($v2[sub_menu])==0 ) : echo "" ;else: foreach($v2[sub_menu] as $k3=>$v3): if($v3[parent_id] == $v2[id]): ?>
                                                                <a class="hover-r ma-le-10 ma-bo-10 pa-le-10 bo-le-hui fl wh-sp" href="<?php echo Url('Home/Goods/scenarioList',array('id'=>$v3[id])); ?>"><?php echo $v3['name']; ?></a>
                                                            <?php endif; endforeach; endif; else: echo "" ;endif; ?>
                                                    </dd>
                                                </dl>
                                            <?php endif; endforeach; endif; else: echo "" ;endif; ?>
                                    </div>
                                </div>
                                <i class="list_img"></i>
                            </li>
                        <?php endif; endforeach; endif; else: echo "" ;endif; ?>
                </ul>
            </div>
            <!--    商品分类 -->
            <div class="classdiv22" style="display:none">
                <ul class="list_ul"　style="opacity:1;">
                    <?php if(is_array($goods_category_tree) || $goods_category_tree instanceof \think\Collection): $_5f713b35a2fb0 = is_array($goods_category_tree) ? array_slice($goods_category_tree,0,9, true) : $goods_category_tree->slice(0,9, true); if( count($_5f713b35a2fb0)==0 ) : echo "" ;else: foreach($_5f713b35a2fb0 as $k=>$v): if($v[level] == 1): ?>
                            <li class="list-li list-li_<?php echo $v['id']; ?> ">
                                <div class="list_a">
                                    <h3><a href="<?php echo Url('Home/Goods/goodsList',array('id'=>$v[id])); ?>" class="two"><i class="list_ico"></i><span class="ys zt"><?php echo $v['name']; ?></span></a></h3>
                                </div>
                                <div class="list_b" style="left:  214px;top:46px;height:400px">
                                    <div class="subitems">
                                        <?php if(is_array($v[tmenu]) || $v[tmenu] instanceof \think\Collection): if( count($v[tmenu])==0 ) : echo "" ;else: foreach($v[tmenu] as $k2=>$v2): if($v2[parent_id] == $v[id]): ?>
                                                <dl class="ma-to-20 cl-bo">
                                                    <dt class="bigheader wh-sp"><a href="<?php echo Url('Home/Goods/goodsList',array('id'=>$v2[id])); ?>"><?php echo $v2['name']; ?></a><i>＞</i></dt>
                                                    <dd class="ma-le-100">
                                                        <?php if(is_array($v2[sub_menu]) || $v2[sub_menu] instanceof \think\Collection): if( count($v2[sub_menu])==0 ) : echo "" ;else: foreach($v2[sub_menu] as $k3=>$v3): if($v3[parent_id] == $v2[id]): ?>
                                                                <a class="hover-r ma-le-10 ma-bo-10 pa-le-10 bo-le-hui fl wh-sp" href="<?php echo Url('Home/Goods/goodsList',array('id'=>$v3[id])); ?>"><?php echo $v3['name']; ?></a>
                                                            <?php endif; endforeach; endif; else: echo "" ;endif; ?>
                                                    </dd>
                                                </dl>
                                            <?php endif; endforeach; endif; else: echo "" ;endif; ?>
                                    </div>
                                </div>
                                <i class="list_img"></i>
                            </li>
                        <?php endif; endforeach; endif; else: echo "" ;endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="classdivdiv" style="margin-left: 12px; margin-right: 0;">

        <div class="container">
            <div class="wrap1" style="left:-760px;">
                <?php $where ="pid =  43";$ad_position = M("ad_position")->cache(true,YLT_CACHE_TIME)->column("position_id,position_name,ad_width,ad_height","position_id");$result = M("ad")->where("$where and enabled = 1  ")->order("orderby desc")->cache(true,YLT_CACHE_TIME)->limit("1")->select();
if(!in_array($pid,array_keys($ad_position)) && $pid)
{
  M("ad_position")->insert(array(
         "position_id"=>$pid,
         "position_name"=>CONTROLLER_NAME."页面自动增加广告位 $pid ",
         "is_open"=>1,
         "position_desc"=>CONTROLLER_NAME."页面",
  ));
  delFile(RUNTIME_PATH); // 删除缓存  
}


$c = 1- count($result); //  如果要求数量 和实际数量不一样 并且编辑模式
if($c > 0 && I("get.edit_ad"))
{
    for($i = 0; $i < $c; $i++) // 还没有添加广告的时候
    {
      $result[] = array(
          "ad_code" => "/public/images/not_adv.jpg",
          "ad_link" => "/index.php?m=Admin&c=Ad&a=ad&pid=$pid",
          "title"   =>"暂无广告图片",
          "not_adv" => 1,
          "target" => 0,
      );  
    }
}
foreach($result as $key=>$v):       
    
    $v[position] = $ad_position[$v[pid]]; 
    if(I("get.edit_ad") && $v[not_adv] == 0 )
    {
        $v[style] = "filter:alpha(opacity=50); -moz-opacity:0.5; -khtml-opacity: 0.5; opacity: 0.5"; // 广告半透明的样式
        $v[ad_link] = "/index.php?m=Admin&c=Ad&a=ad&act=edit&ad_id=$v[ad_id]";        
        $v[title] = $ad_position[$v[pid]][position_name]."===".$v[ad_name];
        $v[target] = 0;
    }
    ?>
                    <li>
                        <a href="<?php echo $v['ad_link']; ?>" target="_blank">
                            <img src="<?php echo $v[ad_code]; ?>" width="760" height="270"/>
                        </a>
                    </li>
                <?php endforeach; $where ="pid =  43";$ad_position = M("ad_position")->cache(true,YLT_CACHE_TIME)->column("position_id,position_name,ad_width,ad_height","position_id");$result = M("ad")->where("$where and enabled = 1  ")->order("orderby desc")->cache(true,YLT_CACHE_TIME)->limit("5")->select();
if(!in_array($pid,array_keys($ad_position)) && $pid)
{
  M("ad_position")->insert(array(
         "position_id"=>$pid,
         "position_name"=>CONTROLLER_NAME."页面自动增加广告位 $pid ",
         "is_open"=>1,
         "position_desc"=>CONTROLLER_NAME."页面",
  ));
  delFile(RUNTIME_PATH); // 删除缓存  
}


$c = 5- count($result); //  如果要求数量 和实际数量不一样 并且编辑模式
if($c > 0 && I("get.edit_ad"))
{
    for($i = 0; $i < $c; $i++) // 还没有添加广告的时候
    {
      $result[] = array(
          "ad_code" => "/public/images/not_adv.jpg",
          "ad_link" => "/index.php?m=Admin&c=Ad&a=ad&pid=$pid",
          "title"   =>"暂无广告图片",
          "not_adv" => 1,
          "target" => 0,
      );  
    }
}
foreach($result as $key=>$v):       
    
    $v[position] = $ad_position[$v[pid]]; 
    if(I("get.edit_ad") && $v[not_adv] == 0 )
    {
        $v[style] = "filter:alpha(opacity=50); -moz-opacity:0.5; -khtml-opacity: 0.5; opacity: 0.5"; // 广告半透明的样式
        $v[ad_link] = "/index.php?m=Admin&c=Ad&a=ad&act=edit&ad_id=$v[ad_id]";        
        $v[title] = $ad_position[$v[pid]][position_name]."===".$v[ad_name];
        $v[target] = 0;
    }
    ?>
                    <li>
                        <a href="<?php echo $v['ad_link']; ?>" target="_blank">
                            <img src="<?php echo $v[ad_code]; ?>" width="760" height="270"/>
                        </a>
                    </li>
                <?php endforeach; $where ="pid =  43";$ad_position = M("ad_position")->cache(true,YLT_CACHE_TIME)->column("position_id,position_name,ad_width,ad_height","position_id");$result = M("ad")->where("$where and enabled = 1  ")->order("orderby desc")->cache(true,YLT_CACHE_TIME)->limit("1")->select();
if(!in_array($pid,array_keys($ad_position)) && $pid)
{
  M("ad_position")->insert(array(
         "position_id"=>$pid,
         "position_name"=>CONTROLLER_NAME."页面自动增加广告位 $pid ",
         "is_open"=>1,
         "position_desc"=>CONTROLLER_NAME."页面",
  ));
  delFile(RUNTIME_PATH); // 删除缓存  
}


$c = 1- count($result); //  如果要求数量 和实际数量不一样 并且编辑模式
if($c > 0 && I("get.edit_ad"))
{
    for($i = 0; $i < $c; $i++) // 还没有添加广告的时候
    {
      $result[] = array(
          "ad_code" => "/public/images/not_adv.jpg",
          "ad_link" => "/index.php?m=Admin&c=Ad&a=ad&pid=$pid",
          "title"   =>"暂无广告图片",
          "not_adv" => 1,
          "target" => 0,
      );  
    }
}
foreach($result as $key=>$v):       
    
    $v[position] = $ad_position[$v[pid]]; 
    if(I("get.edit_ad") && $v[not_adv] == 0 )
    {
        $v[style] = "filter:alpha(opacity=50); -moz-opacity:0.5; -khtml-opacity: 0.5; opacity: 0.5"; // 广告半透明的样式
        $v[ad_link] = "/index.php?m=Admin&c=Ad&a=ad&act=edit&ad_id=$v[ad_id]";        
        $v[title] = $ad_position[$v[pid]][position_name]."===".$v[ad_name];
        $v[target] = 0;
    }
    ?>
                    <li>
                        <a href="<?php echo $v['ad_link']; ?>" target="_blank">
                            <img src="<?php echo $v[ad_code]; ?>" width="760" height="270"/>
                        </a>
                    </li>
                <?php endforeach; ?>
            </div>
            <div  id="uniqueness" class="buttons">
                <span></span>
                <span></span>
                <span></span>
                <span></span>
                <span></span>
            </div>
            <div class="arrow arrow_left"><img src="/public/upload/images/20170914/back.png"></div>
            <div class="arrow arrow_right"><img src="/public/upload/images/20170914/next.png"></div>
            <div class="advertising">广告</div>
        </div>
        <div class="classdivdiv2">
            <div>
                <?php $where ="ad_id = 179";$ad_position = M("ad_position")->cache(true,YLT_CACHE_TIME)->column("position_id,position_name,ad_width,ad_height","position_id");$result = M("ad")->where("$where and enabled = 1  ")->order("orderby desc")->cache(true,YLT_CACHE_TIME)->limit("1")->select();
if(!in_array($pid,array_keys($ad_position)) && $pid)
{
  M("ad_position")->insert(array(
         "position_id"=>$pid,
         "position_name"=>CONTROLLER_NAME."页面自动增加广告位 $pid ",
         "is_open"=>1,
         "position_desc"=>CONTROLLER_NAME."页面",
  ));
  delFile(RUNTIME_PATH); // 删除缓存  
}


$c = 1- count($result); //  如果要求数量 和实际数量不一样 并且编辑模式
if($c > 0 && I("get.edit_ad"))
{
    for($i = 0; $i < $c; $i++) // 还没有添加广告的时候
    {
      $result[] = array(
          "ad_code" => "/public/images/not_adv.jpg",
          "ad_link" => "/index.php?m=Admin&c=Ad&a=ad&pid=$pid",
          "title"   =>"暂无广告图片",
          "not_adv" => 1,
          "target" => 0,
      );  
    }
}
foreach($result as $key=>$v):       
    
    $v[position] = $ad_position[$v[pid]]; 
    if(I("get.edit_ad") && $v[not_adv] == 0 )
    {
        $v[style] = "filter:alpha(opacity=50); -moz-opacity:0.5; -khtml-opacity: 0.5; opacity: 0.5"; // 广告半透明的样式
        $v[ad_link] = "/index.php?m=Admin&c=Ad&a=ad&act=edit&ad_id=$v[ad_id]";        
        $v[title] = $ad_position[$v[pid]][position_name]."===".$v[ad_name];
        $v[target] = 0;
    }
    ?>
                    <li><a href="<?php echo $v[ad_link]; ?>"><img src="<?php echo $v[ad_code]; ?>" width="378" height="180"></a></li>
                <?php endforeach; ?>
            </div>
            <div>
                <?php $where ="ad_id = 178";$ad_position = M("ad_position")->cache(true,YLT_CACHE_TIME)->column("position_id,position_name,ad_width,ad_height","position_id");$result = M("ad")->where("$where and enabled = 1  ")->order("orderby desc")->cache(true,YLT_CACHE_TIME)->limit("1")->select();
if(!in_array($pid,array_keys($ad_position)) && $pid)
{
  M("ad_position")->insert(array(
         "position_id"=>$pid,
         "position_name"=>CONTROLLER_NAME."页面自动增加广告位 $pid ",
         "is_open"=>1,
         "position_desc"=>CONTROLLER_NAME."页面",
  ));
  delFile(RUNTIME_PATH); // 删除缓存  
}


$c = 1- count($result); //  如果要求数量 和实际数量不一样 并且编辑模式
if($c > 0 && I("get.edit_ad"))
{
    for($i = 0; $i < $c; $i++) // 还没有添加广告的时候
    {
      $result[] = array(
          "ad_code" => "/public/images/not_adv.jpg",
          "ad_link" => "/index.php?m=Admin&c=Ad&a=ad&pid=$pid",
          "title"   =>"暂无广告图片",
          "not_adv" => 1,
          "target" => 0,
      );  
    }
}
foreach($result as $key=>$v):       
    
    $v[position] = $ad_position[$v[pid]]; 
    if(I("get.edit_ad") && $v[not_adv] == 0 )
    {
        $v[style] = "filter:alpha(opacity=50); -moz-opacity:0.5; -khtml-opacity: 0.5; opacity: 0.5"; // 广告半透明的样式
        $v[ad_link] = "/index.php?m=Admin&c=Ad&a=ad&act=edit&ad_id=$v[ad_id]";        
        $v[title] = $ad_position[$v[pid]][position_name]."===".$v[ad_name];
        $v[target] = 0;
    }
    ?>
                    <li><a href="<?php echo $v[ad_link]; ?>"><img src="<?php echo $v[ad_code]; ?>" width="378" height="180"></a></li>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="classlogn">
        <div class="bs-ri">
            <div class="ris-notice">
                <div class="notice_x">
                    <div class="notice-img" >
                        <div class="v-img l" style="float:left;"><img src="/public/static/images/tou2.png"></div>
                        <div class=""><p>Hi 您好!<br>欢迎来到一礼通</p></div>
                    </div>
                    <div class="c"></div>
                    <div class="login_btn">
                        <div class="mem_login ">
                            <a class="nologin" href="/Home/User/user_login.html" rel="nofollow">个人登录</a>
                            <a class="islogin" href="<?php echo Url('Home/User/index'); ?>" rel="nofollow" style="display:none">个人中心</a>

                            <?php if($supplier['status'] == 1): ?>
                                <a class="isSuplogin" href="<?php echo Url('Home/Supplier/StoreHome',array('id'=>\think\Session::get('supplier_id'))); ?>" rel="nofollow"  style="display:none">店铺中心</a>
                                <?php else: ?>
                                <a class="isSuplogin" href="<?php echo Url('Home/Business/BusinessIndex'); ?>" rel="nofollow"  style="display:none">店铺中心</a>
                            <?php endif; ?>
                        </div>
                        <div class="shops_login ">
                            <a class="nologin" href="/Home/Business/login.html" rel="nofollow">商家登录</a>
                            <a class="islogin" href="<?php echo Url('Home/User/order_list'); ?>" rel="nofollow" style="display:none">我的订单</a>
                            <?php if($supplier['status'] == 1): ?>
                                <a class="isSuplogin" href="<?php echo Url('Supplier/Index/index'); ?>" rel="nofollow" style="display:none">管理中心</a>
                                <?php else: ?>
                                <a class="isSuplogin" href="<?php echo Url('Home/Business/BusinessIndex'); ?>" rel="nofollow" style="display:none">管理中心</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="c"></div>
                    <div class="notice-res">
                        <div class="login1 nologin" style="float:left;width:45%;margin:0;text-align: center;"><a href="/Home/User/register.html" rel="nofollow">注册会员</a></div>
                        <div class="login2 nologin" style="width:45%;margin:0;text-align: center;float:left;"><a href="/Home/Business/register.html" rel="nofollow">注册商家</a></div>
                    </div>
                </div>
                <div class="c"></div>
                <div class="notice_t">
                    <ul>
                        <li id="not_col" style="margin-left:10px;"><a href="javascript:void(0);">公告</a></li>
                        <li style="width:15px;"><em>|</em></li>
                        <li id="not_new"><a href="javascript:void(0);">新闻</a></li>
                    </ul>
                </div>
                <div class="notice_b">
                    <ul class="nob1">

                        <li><a href="/article/163.html">礼至集团发起“抗疫救灾物资全球调拨行动”</a></li>

                        <li><a href="/article/161.html">分销商城最强攻略（礼店主必看）</a></li>

                        <li><a href="/article/160.html">“礼店主”招募令！！！</a></li>

                        <li><a href="/article/158.html">商 家 入 驻 相 关 操 作 流 程</a></li>

                        <li><a href="/article/148.html">礼至期刊·人物（四）——礼品事业部刘...</a></li>

                        <!-- <li><a href="/article/141.html">市 代 理 收 益</a></li> -->

                        <!-- <li><a href="/article/140.html">区 /县代理 收 益</a></li> -->

                    </ul>
                    <ul class="nob2" style="display: none;">
                        <li><a href="/article/147.html">平台精选一礼通母亲节礼品！</a></li>
                        <li><a href="/article/146.html">上门拜访或送贵客选什么礼品好？</a></li>
                        <li><a href="/article/144.html">礼至一礼通与万商生态云盟达成战略合作...</a></li>
                        <li><a href="/article/143.html">母情节送这些礼物，妈妈肯定很疼你！</a></li>
                    </ul>
                </div>
                <!-- 图片暂时注释 -->
                <!--<div class="jiayuan_bg"><img src="/public/static/images/homeBg.png"></div>-->
                <!-- 图片暂时注释 -->
            </div>
            <!--公告下方广告位-->
            <div class="ris-as">
            </div>
        </div>
        <div class="business2">
            <?php $where ="ad_id = 180";$ad_position = M("ad_position")->cache(true,YLT_CACHE_TIME)->column("position_id,position_name,ad_width,ad_height","position_id");$result = M("ad")->where("$where and enabled = 1  ")->order("orderby desc")->cache(true,YLT_CACHE_TIME)->limit("1")->select();
if(!in_array($pid,array_keys($ad_position)) && $pid)
{
  M("ad_position")->insert(array(
         "position_id"=>$pid,
         "position_name"=>CONTROLLER_NAME."页面自动增加广告位 $pid ",
         "is_open"=>1,
         "position_desc"=>CONTROLLER_NAME."页面",
  ));
  delFile(RUNTIME_PATH); // 删除缓存  
}


$c = 1- count($result); //  如果要求数量 和实际数量不一样 并且编辑模式
if($c > 0 && I("get.edit_ad"))
{
    for($i = 0; $i < $c; $i++) // 还没有添加广告的时候
    {
      $result[] = array(
          "ad_code" => "/public/images/not_adv.jpg",
          "ad_link" => "/index.php?m=Admin&c=Ad&a=ad&pid=$pid",
          "title"   =>"暂无广告图片",
          "not_adv" => 1,
          "target" => 0,
      );  
    }
}
foreach($result as $key=>$v):       
    
    $v[position] = $ad_position[$v[pid]]; 
    if(I("get.edit_ad") && $v[not_adv] == 0 )
    {
        $v[style] = "filter:alpha(opacity=50); -moz-opacity:0.5; -khtml-opacity: 0.5; opacity: 0.5"; // 广告半透明的样式
        $v[ad_link] = "/index.php?m=Admin&c=Ad&a=ad&act=edit&ad_id=$v[ad_id]";        
        $v[title] = $ad_position[$v[pid]][position_name]."===".$v[ad_name];
        $v[target] = 0;
    }
    ?>
                <li><a href="<?php echo $v[ad_link]; ?>"><img src="<?php echo $v[ad_code]; ?>" width="190" height="140"></a></li>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<div id="app" class="layout distance">
    <div class="procurement_box">
        <div class="hearind">采购需求 Procurement</div>
        <a rel="nofollow" href="<?php echo Url('home/Article/businessPurchase'); ?>" > <div class="viewMore">查看更多</div></a>
        <div class="gather">已收集到<span style="color: red;"><?php echo $purchase_count + 57500; ?></span>条采购信息</div>
    </div>

    <div class="hearinddiv">
        <?php if(is_array($map_list) || $map_list instanceof \think\Collection): $i = 0; $__LIST__ = $map_list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$item): $mod = ($i % 2 );++$i;?>
            <!--<template v-for="(item, idx) in arrProduct">-->
            <div class="product">
                <!-- <div class="line"></div> -->
                <?php if(!(empty($item['sustomized']) || ($item['sustomized'] instanceof \think\Collection && $item['sustomized']->isEmpty()))): ?>
                    <div class="ima"><img src="/public/yilitong/images/customization.png"></div>
                <?php endif; ?>

                <div class="title_box">
                    <span class="title_pro">产品</span><span class="procurement_title"><?php echo $item['title']; ?></span>
                </div>
                <div class="pro_offer">
                    <div class="pro_box">采购数量<span class="pro_num"><?php echo $item['count']; ?></span>种</div>
                    <div class="offer_box">已有报价<span class="pro_num"><?php echo $item['offer_num'] + $item['quoted']; ?></span>条</div>
                </div>

                <ul class="name_num_unit_box">
                    <?php if(is_array($item['content']) || $item['content'] instanceof \think\Collection): $key = 0;$__LIST__ = is_array($item['content']) ? array_slice($item['content'],0,2, true) : $item['content']->slice(0,2, true); if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($key % 2 );++$key;?>
                        <li>
                         <span class="name_num_unit">
                            <span><?php echo $vo['goods_name']; ?></span><span><?php echo $vo['goods_num']; ?></span><span><?php echo $vo['goods_unit']; ?></span>
                         </span>
                        </li>
                    <?php endforeach; endif; else: echo "" ;endif; ?>
                </ul>

                <div class="budget_pa">
                    <div class="budget"><span class="budget_num">
                         <?php if(empty($item['budget']) || ($item['budget'] instanceof \think\Collection && $item['budget']->isEmpty())): ?>
                         0.00
                         <?php else: ?>
                         ￥<?php echo $item['budget']; endif; ?>
                     </span>万元/总预算</div>
                    <div class="time_num">
                        <div>
                            发布时间：<span><?php echo date('Y-m-d',$item['inquiry_time']); ?></span>
                        </div>
                        <div class="endTime">
                            截止日期：<span><?php echo date('Y-m-d',$item['dead_time']); ?></span>
                        </div>
                        <div>
                            被查看数：<span><?php echo $item['be_viewed'] + $item['view']; ?></span>
                        </div>
                    </div>
                </div>
                <div class="company_offer">
                    <div class="company_name" title="深圳市礼至礼品集团有限公司"><?php echo $item['company_name']; ?></div>
                    <a class="see" data-id="<?php echo $item['id']; ?>" href="<?php echo Url('Home/Article/tradeList',array('id'=>$item[id])); ?>"> <div class="promptly1"> 立即报价</div></a>

                </div>
            </div>
            <!--</template>-->
        <?php endforeach; endif; else: echo "" ;endif; ?>

    </div>
</div>

</div>

<div id="pc2" class="layout">
    <div class="famousBrand_box">
        <div class="famousBrand_Title">知名品牌 &nbsp; Famous brand</div>
        <a rel="nofollow" href="<?php echo Url('Home/Index/brandList'); ?>" ><div class="viewMore1">查看更多</div></a>
        <div class="enterBrand">已入驻<span style="color: red;">232</span>个知名品牌</div>
    </div>
    <div class="brandInformation" >
        <?php if(is_array($brands) || $brands instanceof \think\Collection): $i = 0; $__LIST__ = $brands;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$item1): $mod = ($i % 2 );++$i;if(is_array($item1) || $item1 instanceof \think\Collection): $key = 0; $__LIST__ = $item1;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($key % 2 );++$key;?>
                <div class="logo_box">
                    <div class="logoImg">
                        <img src="<?php echo $vo['logo']; ?>"/>
                    </div>
                    <div class="logoName">
                        <p>
                            <?php echo $vo[name]; ?>
                        </p>
                        <a href="<?php echo Url('Home/Goods/brandList',array('brand_id'=>$vo[id])); ?>">点击进入</a >
                    </div>
                </div>
            <?php endforeach; endif; else: echo "" ;endif; endforeach; endif; else: echo "" ;endif; ?>
    </div>
</div>

<div id="pc2" class="layout">
    <div class="famousBrand_box">
        <div class="famousBrand_Title">优质供应商 &nbsp; Quality supplier</div>
        <a rel="nofollow" href="<?php echo Url('Home/Index/supplierList'); ?>" ><div class="viewMore1">查看更多</div></a>
        <div class="enterBrand">已入驻<span style="color: red;">1203</span>个优质供应商</div>
    </div>
    <div class="brandInformation" >
        <?php if(is_array($suppliers) || $suppliers instanceof \think\Collection): $i = 0; $__LIST__ = $suppliers;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$item1): $mod = ($i % 2 );++$i;if(is_array($item1) || $item1 instanceof \think\Collection): $key = 0; $__LIST__ = $item1;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($key % 2 );++$key;?>
                <div class="logo_box">
                    <div class="logoImg">
                        <img src="<?php echo $vo['logo']; ?>"/>
                    </div>
                    <div class="logoName">
                        <p>
                            <?php echo $vo[name]; ?>
                        </p>
                        <a href="<?php echo Url('Home/Supplier/StoreHome',array('id'=>$vo[supplier_id])); ?>">点击进入</a >
                    </div>
                </div>
            <?php endforeach; endif; else: echo "" ;endif; endforeach; endif; else: echo "" ;endif; ?>
    </div>
</div>


<div id="qualityNewProducts" class="layout">
    <div class="famousBrand_box">
        <div class="famousBrand_Title">上市新品 &nbsp; Quality new products</div>
        <a rel="nofollow" href="/Home/Goods/more" style="text-decoration:none;"><div class="viewMore1">查看更多</div></a>
        <div class="enterBrand"></div>
    </div>
    <div class="HotGift">
        <div class="newProducts" :id="'prod'+idx" v-for="(product,idx) in products">
            <div class="commodityImg" @click="clickCommodityImg(product.goods_id)">
                <!-- @mouseenter="enter(idx)" @mouseleave="leave(idx)" -->
                <img class="bigImg" :src="product.image_url[0].image_url" >
            </div>
            <div class="productBox">
                <div class="imgbox">
                    <div class="onBack" @click="clickBack(idx)"><img src="/public/yilitong/images2/back1.png"></div>
                    <div class="imgsbox">
                        <div v-show="i<5" class="Photos" :class="['imgbox'+i,{'br2':i<1}]" v-for="(imgsrc,i) in product.image_url" @click="clickImg(idx,i,imgsrc)"><img
                                :src="imgsrc.image_url" /></div>
                    </div>
                    <div class="onNext" @click="clickNext(idx)"><img src="/public/yilitong/images2/next1.png"></div>
                </div>
                <div class="tradeName" @click="clickTradeName(product.goods_id)">{{product.goods_name}}</div>
                <div class="pricebox">
                    <div class="price1">
                        <div class="num">￥<span>
                {{product.shop_price}}
                </span></div>
                        <div class="addUp">累计销售<span>{{product.sales_sum}}</span>件</div>
                    </div>
                    <div class="company">
                        <div class="companyName">{{product.company_name}}</div>
                        <!-- <div class="melt">销</div>
                        <div class="year">1年</div> -->
                    </div>
                    <div class="activity">
                        <div class="firm" :title="product.province+product.city+product.area"><span>{{product.province}}</span><span>{{product.city}}</span><span>{{product.area}}</span></div>
                        <div class="seckillFullMinus">
                            <div class="fullMinus" v-show="product.prom_type==3">满减</div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<div id="hotGift" class="layout">
    <div class="famousBrand_box">
        <div class="famousBrand_Title">热门礼品 &nbsp; Hot gift</div>
        <a rel="nofollow" href="/Home/Goods/more" style="text-decoration:none;"><div class="viewMore1">查看更多</div></a>
        <div class="enterBrand"></div>
    </div>
    <div class="HotGift">
        <div class="newProducts" :id="'prods'+idx1" v-for="(product1,idx1) in product1s">
            <div class="commodityImg"  @click="clickCommodityImg1(product1.goods_id)">
                <!-- @mouseenter="enter(idx1)" @mouseleave="leave(idx1)" -->
                <img class="bigImg" :src="product1.image_url[0].image_url" >
            </div>
            <div class="productBox">
                <div class="imgbox">
                    <div class="onBack" @click="clickBack1(idx1)"><img src="/public/yilitong/images2/back1.png"></div>
                    <div class="imgsbox">
                        <div v-show="i<5" class="Photos" :class="['imgbox'+i,{'br2':i<1}]" v-for="(imgsSrc,i) in product1.image_url" @click="clickImgs(idx1,i,imgsSrc)"><img
                                :src="imgsSrc.image_url" />
                        </div>
                    </div>
                    <div class="onNext" @click="clickNext1(idx1)"><img src="/public/yilitong/images2/next1.png"></div>
                </div>
                <div class="tradeName" @click="clickTradeName1(product1.goods_id)">{{product1.goods_name}}</div>
                <div class="pricebox">
                    <div class="price1">
                        <div class="num">￥<span>{{product1.shop_price}}</span></div>
                        <div class="addUp">累计销售<span>{{product1.sales_sum}}</span>件</div>
                    </div>
                    <div class="company">
                        <div class="companyName">{{product1.company_name}}</div>
                        <!-- <div class="melt">销</div>
                        <div class="year">1年</div> -->
                    </div>
                    <div class="activity">
                        <div class="firm" :title="product1.province+product1.city+product1.area"><span>{{product1.province}}</span><span>{{product1.city}}</span><span>{{product1.area}}</span></div>
                        <div class="seckillFullMinus">
                            <div class="seckill" v-show="product1.prom_type==2">秒杀</div>
                            <div class="fullMinus" v-show="product1.prom_type==3">满减</div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>




<div id="pc" class="layout">
    <div class="information_1">
        <p class="hearind" style="flex: 1">活动资讯 &nbsp; Activity information</p>
        <!--<p class="viewMore">查看更多</p>-->
    </div>

    <div class="hearinddiv_1">
        <?php if(is_array($arrry) || $arrry instanceof \think\Collection): $i = 0;$__LIST__ = is_array($arrry) ? array_slice($arrry,0,1, true) : $arrry->slice(0,1, true); if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?>
            <div class="activity_1">
                <div class="imag">
                    <div class="images">
                        <a href="<?php echo Url('/Article/'.$vo['article_id']); ?>">
                            <img class="image" src="<?php echo $vo['image']; ?>" width="">
                        </a>
                    </div>
                </div>
                <div class="informationName">
                    <a href="<?php echo Url('/Article/'.$vo['article_id']); ?>"> <?php echo $vo['title']; ?></a>
                </div>
                <?php if(is_array($arr) || $arr instanceof \think\Collection): $i = 0;$__LIST__ = is_array($arr) ? array_slice($arr,0,4, true) : $arr->slice(0,4, true); if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$voo): $mod = ($i % 2 );++$i;?>
                    <div class="information_box">
                        <div class="informationTitle">
                            <a href="<?php echo Url('/Article/'.$voo['article_id']); ?>"> <?php echo $voo['title']; ?></a>
                        </div>
                        <div class="informationTime"><?php echo date('Y-m-d',$voo['time']); ?></div>
                    </div>
                <?php endforeach; endif; else: echo "" ;endif; ?>
            </div>
        <?php endforeach; endif; else: echo "" ;endif; if(is_array($arrry) || $arrry instanceof \think\Collection): $i = 0;$__LIST__ = is_array($arrry) ? array_slice($arrry,1,1, true) : $arrry->slice(1,1, true); if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?>
            <div class="activity_1">
                <div class="imag">
                    <div class="images">
                        <a href="<?php echo Url('/Article/'.$vo['article_id']); ?>">
                            <img class="image" src="<?php echo $vo['image']; ?>" width="">
                        </a>
                    </div>
                </div>
                <div class="informationName">
                    <a href="<?php echo Url('/Article/'.$vo['article_id']); ?>"> <?php echo $vo['title']; ?></a>
                </div>
                <?php if(is_array($arr) || $arr instanceof \think\Collection): $i = 0;$__LIST__ = is_array($arr) ? array_slice($arr,4,4, true) : $arr->slice(4,4, true); if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$voo): $mod = ($i % 2 );++$i;?>
                    <div class="information_box">
                        <div class="informationTitle">
                            <a href="<?php echo Url('/Article/'.$voo['article_id']); ?>"> <?php echo $voo['title']; ?></a>
                        </div>
                        <div class="informationTime"><?php echo date('Y-m-d',$voo['time']); ?></div>
                    </div>
                <?php endforeach; endif; else: echo "" ;endif; ?>
            </div>
        <?php endforeach; endif; else: echo "" ;endif; ?>
    </div>
</div>

<div class="alldiv" style="background: #d8d8d8;">
    <div class="layout">
        <ul class="footli">
            <li>
                <img src="/public/yilitong/images/footer-icon-1.png"/>
                <div>
                    <p class="footp">质量保证</p>
                    <p>正品行货 放心购物</p>
                </div>
            </li>
            <li>
                <img src="/public/yilitong/images/footer-icon-2.png"/>
                <div>
                    <p class="footp">物流无忧</p>
                    <p>下单闪电发货包邮</p>
                </div>
            </li>
            <li>
                <img src="/public/yilitong/images/footer-icon-3.png"/>
                <div>
                    <p class="footp">售后服务</p>
                    <p>正品行货 放心购物</p>
                </div>
            </li>
            <li>
                <img src="/public/yilitong/images/footer-icon-4.png"/>
                <div>
                    <p class="footp">退换无忧</p>
                    <p>正品行货 放心购物</p>
                </div>
            </li>
        </ul>
        <br><br>
        <hr style="margin-top:40px">
        <div class="helpful layout">
            <?php
                                   
                                $md5_key = md5("select * from `__PREFIX__article_cat` where parent_id = 2");
                                $result_name = $sql_result_v = S("sql_".$md5_key);
                                if(empty($sql_result_v))
                                {                            
                                    $result_name = $sql_result_v = \think\Db::query("select * from `__PREFIX__article_cat` where parent_id = 2"); 
                                    S("sql_".$md5_key,$sql_result_v,604800);
                                }    
                              foreach($sql_result_v as $k=>$v): ?>
                <dl <?php if($k != 0): ?>class="jszc"<?php endif; ?> >
                <dt><?php echo $v[cat_name]; ?></dt>
                <dd>
                    <ol>
                        <?php
                                   
                                $md5_key = md5("select * from `__PREFIX__article` where cat_id = $v[cat_id] and is_open=1");
                                $result_name = $sql_result_v2 = S("sql_".$md5_key);
                                if(empty($sql_result_v2))
                                {                            
                                    $result_name = $sql_result_v2 = \think\Db::query("select * from `__PREFIX__article` where cat_id = $v[cat_id] and is_open=1"); 
                                    S("sql_".$md5_key,$sql_result_v2,604800);
                                }    
                              foreach($sql_result_v2 as $k2=>$v2): ?>
                            <li><a rel="nofollow" href="<?php echo Url('Home/Article/detail',array('article_id'=>$v2[article_id])); ?>" target="_blank"><?php echo $v2[title]; ?></a></li>
                        <?php endforeach; ?>
                    </ol>
                </dd>
                </dl>
            <?php endforeach; ?>
        </div>

        <hr >
        <div style="text-align: center;line-height: 40px;">
            <span><a rel="nofollow" href="javascript:void(0);">我们的团队</a></span>
            <span> | </span>
            <span><a rel="nofollow" href="javascript:void(0);">网站联盟</a></span>
            <span> | </span>
            <span><a rel="nofollow" href="javascript:void(0);">热火搜索</a></span>
            <span> | </span>
            <span><a rel="nofollow" href="javascript:void(0);">诚征英才</a></span>
            <span> | </span>
            <span><a rel="nofollow" href="javascript:void(0);">友情链接</a></span>
            <span> | </span>
            <span><a rel="nofollow" href="javascript:void(0);">一礼通新品</a></span>
            <span> | </span>
            <span>开放平台</span>
            <span> | </span><span><a href="/public/yilitong/images/yingyezizhi.png" rel="nofollow" target="_blank" >营业执照|食品许可证|第二类医疗机械备案凭证</a></span>
            <span> | </span><span><a href="/public/yilitong/images/yaopingzhengshu.pdf" rel="nofollow" target="_blank" >《互联网药品信息服务资格证书》</a></span>
            <br>
         
            <p>Copyright © 2017-2027 一礼通 (www.yilitong.com)-互联网生活应用平台 版权所有 保留一切权利 <a href="http://www.beian.miit.gov.cn" rel="nofollow" target="_blank" >备案号:<?php echo $config['shop_info_record_no']; ?></a></p>
            <script type="text/javascript">
                //数据专家统计数据
                var cnzz_protocol = (("https:" == document.location.protocol) ? " https://" : " http://");
                document.write(unescape("%3Cspan id='cnzz_stat_icon_1275398921'%3E%3C/span%3E%3Cscript src='" + cnzz_protocol + "s5.cnzz.com/z_stat.php%3Fid%3D1275398921%26show%3Dpic' type='text/javascript'%3E%3C/script%3E"));
            </script>
            <br>

            <div style="margin-left: 400px;margin-top: -15px;">
                <ul class="guarantee">
                    <li>
                        <a href="http://www.itrust.org.cn/Home/Index/wx_certifi/wm/WX2017052402.html" rel="nofollow">
                            <img src="/public/yilitong/images2/anquan.png">
                        </a>
                    </li>

                    <li>
                        <!--可信网站图片LOGO安装开始-->
                        <img src="http://rr.knet.cn/static/images/logo/cnnic.png">
                    </li>
                    <li>
                        <img src="/public/yilitong/images/weixin_cnnic.png">
                    </li>
                    <li>
                        <img src="/public/yilitong/images/zhifubao_cnnic.png">
                    </li>
                   <li>
                         <script id="ebsgovicon" src="https://szcert.ebs.org.cn/govicons.js?id=451b976b-ae2f-494f-a54d-d6230d676226&width=91&height=37&type=2" type="text/javascript" charset="utf-8"></script>
                    </li>
                 
                </ul>
            
            </div><br>
            <!-- <span>友情链接 </span><span>礼品公司 </span><span>北京礼品卡 </span><span>玉林批发网 </span><span>礼品网站 </span><span>仓库管理系统 </span><span>礼品册 </span><span>商务礼品 </span><span>节日礼品册 </span><span>礼品商城 </span><span>生日礼物 </span><span>礼品批发 </span><span>礼品采购 </span><span>广州礼品定制 </span>-->
            <div style="text-align: center;width:1200px;margin:0 auto;">
                <ul class="friendLink" id="friendLink" style="display: inline-block;margin-top:10px;">
                    <li><span style="color: #999;">友情链接</span></li>
                    <?php
                                   
                                $md5_key = md5("select * from `__PREFIX__friend_link` where is_show = 1 order by orderby asc");
                                $result_name = $sql_result_vo = S("sql_".$md5_key);
                                if(empty($sql_result_vo))
                                {                            
                                    $result_name = $sql_result_vo = \think\Db::query("select * from `__PREFIX__friend_link` where is_show = 1 order by orderby asc"); 
                                    S("sql_".$md5_key,$sql_result_vo,604800);
                                }    
                              foreach($sql_result_vo as $k2=>$vo): ?>
                        <li><a style="color: #999;" href="<?php echo $vo['link_url']; ?>" <?php if($vo['nofollow'] == 1): ?>rel="nofollow"<?php endif; if($vo['target'] == 1): ?> target="_blank"<?php endif; ?> ><?php echo $vo[link_name]; ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<script src="/public/yilitong/js/carousel.js"></script>
<script src="https://cdn.bootcss.com/vue/2.5.21/vue.min.js"></script>
<script src="/public/yilitong/js/vue-resource.js"></script>
<script src="/public/yilitong/js/config.js"></script>
<script type="text/javascript">
    var wrapAdver = document.querySelector('.wrapadver');
    var closeAdver = document.querySelector('.advertising_ch>.title>img');
    closeAdver.onclick = function(){
        wrapAdver.style.display = 'none';
    }
</script>
<script>
    var listed = new Vue({
        el: "#qualityNewProducts",
        data: {
            products: []
        },
        created: function() { // 进入
            // 获取用户填的内容

        },
        mounted: function() { // 挂载后
            this.getData();
        },
        methods: {
            clickBack: function(idx) {
                // 如果是第1个，指定imgbox4加边框
                var curImg = $("#prod" + idx).find('.br2'); // 找到带边框的活动元素
                var curIdx = curImg.index(); // 当前图片索引
                var presrc = ""; // 上一张图片src
                console.log("(上一张)产品:" + idx + ",当前红框:" + curIdx);
                $("#prod" + idx).find(".Photos").removeClass('br2');
                if (curIdx < 1) {
                    $("#prod" + idx).find(".imgbox4").addClass('br2');
                    presrc = $("#prod" + idx).find(".imgbox4 img").attr('src');
                } else {
                    $("#prod" + idx).find(".imgbox" + (curIdx - 1)).addClass('br2');
                    presrc = $("#prod" + idx).find(".imgbox" + (curIdx - 1) + " img").attr('src');
                }
                $("#prod" + idx).find(".bigImg").attr("src", presrc);
            },
            clickNext: function(idx) {
                // 如果是最后个，指定imgbox0加边框
                var curImg = $("#prod" + idx).find('.br2'); // 找到带边框的活动元素
                var curIdx = curImg.index(); // 当前图片索引
                var nextsrc = ""; // 下一张图片src
                console.log("(下一张)产品:" + idx + ",当前红框:" + curIdx);
                $("#prod" + idx).find(".Photos").removeClass('br2');
                if (curIdx >= 4) {
                    $("#prod" + idx).find(".imgbox0").addClass('br2');
                    nextsrc = $("#prod" + idx).find(".imgbox0 img").attr('src');
                } else {
                    $("#prod" + idx).find(".imgbox" + (curIdx + 1)).addClass('br2');
                    nextsrc = $("#prod" + idx).find(".imgbox" + (curIdx + 1) + " img").attr('src');
                }
                $("#prod" + idx).find(".bigImg").attr("src", nextsrc);
            },
            clickImg: function(idx, i, imgsrc) { // 点击小图
                console.log("产品:" + idx + ",图片:" + i + ",src：" + imgsrc);
                $("#prod" + idx).find(".bigImg").attr("src", imgsrc.image_url);
                $("#prod" + idx).find(".Photos").removeClass('br2');
                $("#prod" + idx).find(".imgbox" + i).addClass('br2');
            },
            clickCommodityImg: function(g_id){
                console.log("g_id"+g_id);
                var url = "/goodsInfo/"+g_id+".html";
                console.log("url:"+url)
                location.href = url;
            },
            clickTradeName: function(g_id){
                console.log("g_id"+g_id);
                var url = "/goodsInfo/"+g_id+".html";
                console.log("url:"+url)
                location.href = url;
            },
            getData: function() {
                var that = this;
                var cururl=window.location.href.substring(0,6);

                if(cururl.indexOf('https')==-1){

                    var productUrl = baseUrl+"/home/GoodsAPI/var_json?token=8d0b6604701b9a30c2547ffb8ee6a3c1";
                }else{

                    var productUrl = baseUrl2+"/home/GoodsAPI/var_json?token=8d0b6604701b9a30c2547ffb8ee6a3c1";
                }

                // productUrl = "http://api.douban.com/v2/movie/top250";

                console.log("请求url:" + productUrl);
                this.$http({
                    url: productUrl,
                    method: 'GET',
                    // 传后端的数据
                    data: {

                    },
                    // 请求头
                    headers: {
                        'Content-Type': 'json'
                    }

                }).then(function(response) {
                    console.log("请求成功回调：");
                    // console.log(response)
                    var quality = response.bodyText;
                    quality = JSON.parse(quality);
                    // 对后台返回的数据做处理
                    this.products = quality;
                }, function(response) {
                    console.log("请求失败回调");
                    console.log(response)
                });
            },


        }
    })
</script>
<script>
    var hot = new Vue({
        el: "#hotGift",
        data: {
            product1s: []
        },
        created: function() { // 进入
            // 获取用户填的内容

        },
        mounted: function() { // 挂载后
            this.getData();
        },
        methods: {
            clickBack1: function(idx1) {
                // 如果是第1个，指定imgbox4加边框
                var curImg = $("#prods" + idx1).find('.br2'); // 找到带边框的活动元素
                var curidx1 = curImg.index(); // 当前图片索引
                var presrc = ""; // 上一张图片src
                console.log("(上一张)产品:" + idx1 + ",当前红框:" + curidx1);
                $("#prods" + idx1).find(".Photos").removeClass('br2');
                if (curidx1 < 1) {
                    $("#prods" + idx1).find(".imgbox4").addClass('br2');
                    presrc = $("#prods" + idx1).find(".imgbox4 img").attr('src');
                } else {
                    $("#prods" + idx1).find(".imgbox" + (curidx1 - 1)).addClass('br2');
                    presrc = $("#prods" + idx1).find(".imgbox" + (curidx1 - 1) + " img").attr('src');
                }
                $("#prods" + idx1).find(".bigImg").attr("src", presrc);
            },
            clickNext1: function(idx1) {
                // 如果是最后个，指定imgbox0加边框
                var curImg = $("#prods" + idx1).find('.br2'); // 找到带边框的活动元素
                var curidx1 = curImg.index(); // 当前图片索引
                var nextsrc = ""; // 下一张图片src
                console.log("(下一张)产品:" + idx1 + ",当前红框:" + curidx1);
                $("#prods" + idx1).find(".Photos").removeClass('br2');
                if (curidx1 >= 4) {
                    $("#prods" + idx1).find(".imgbox0").addClass('br2');
                    nextsrc = $("#prods" + idx1).find(".imgbox0 img").attr('src');
                } else {
                    $("#prods" + idx1).find(".imgbox" + (curidx1 + 1)).addClass('br2');
                    nextsrc = $("#prods" + idx1).find(".imgbox" + (curidx1 + 1) + " img").attr('src');
                }
                $("#prods" + idx1).find(".bigImg").attr("src", nextsrc);
            },
            clickImgs: function(idx1, i, imgsSrc) { // 点击小图
                console.log("产品:" + idx1 + ",图片:" + i + ",src：" + imgsSrc);
                $("#prods" + idx1).find(".bigImg").attr("src", imgsSrc.image_url);
                $("#prods" + idx1).find(".Photos").removeClass('br2');
                $("#prods" + idx1).find(".imgbox" + i).addClass('br2');
            },
            clickCommodityImg1: function(goodsId){
                console.log("goodsId"+goodsId);
                var url = "/goodsInfo/"+goodsId+".html";
                console.log("url:"+url)
                location.href = url;
            },
            clickTradeName1: function(goodsId){
                console.log("goodsId"+goodsId);
                var url = "/goodsInfo/"+goodsId+".html";
                console.log("url:"+url)
                location.href = url;
            },
            getData: function() {
                var that = this;
                var cururl=window.location.href.substring(0,6);

                if(cururl.indexOf('https')==-1){

                    var product1Url = baseUrl+"/home/GoodsAPI/popular_json?token=5fa3852b08951fcdc4d2e60f89a85bfe";
                }else{

                    var product1Url = baseUrl2+"/home/GoodsAPI/popular_json?token=5fa3852b08951fcdc4d2e60f89a85bfe";
                }

                // product1Url = "http://api.douban.com/v2/movie/top250";

                console.log("请求url:" + product1Url);
                // return;
                this.$http({
                    url: product1Url,
                    method: 'GET',
                    // 传后端的数据
                    data: {

                    },
                    // 请求头
                    headers: {
                        'Content-Type': 'json'
                    }

                }).then(function(response) {
                    console.log("请求成功回调：");
                    // console.log(response)
                    var hot = response.bodyText;
                    hot = JSON.parse(hot);
                    // 对后台返回的数据做处理
                    this.product1s = hot;
                }, function(response) {
                    console.log("请求失败回调");
                    console.log(response)
                });
            },


        }
    })
</script>

<script>
    function getCookie(c_name)
    {
        if (document.cookie.length > 0)
        {
            c_start = document.cookie.indexOf(c_name + "=");
            if (c_start != -1)
            {
                c_start = c_start + c_name.length + 1;
                c_end = document.cookie.indexOf(";",c_start);
                if (c_end == -1) {
                    c_end = document.cookie.length
                }
                return unescape(document.cookie.substring(c_start,c_end))
            }
        } else {
            return ""
        }
    }
    var uname = getCookie('user_name');
    var supname =getCookie('supplier_name');


    if((uname == '' || uname == undefined)&&(supname == '' || supname == undefined)){
        $('.islogin').css("display","none");
        $('.isSuplogin').css("display","none");;
        $('.nologin').css("display","block");
    }else if((uname != '' || uname != undefined)&&(supname == '' || supname == undefined)){
        $('.nologin').css("display","none");
        $('.isSuplogin').css("display","none");
        $('.islogin').css("display","block");
        $('.userinfo').html(decodeURIComponent(uname));
    }
    if((uname == '' || uname == undefined)&&(supname == '' || supname == undefined)){
        $('.islogin').css("display","none");
        $('.isSuplogin').css("display","none");;
        $('.nologin').css("display","block");
    }else if((uname == '' || uname == undefined)&&(supname != '' || supname != undefined)){
        $('.nologin').css("display","none");
        $('.isSuplogin').css("display","block");
        $('.islogin').css("display","none");
        $('.supinfo').html(decodeURIComponent(supname));
    }

    $(function(){
        $("#li1").css("background","#8a1313");
        $("#li2").css("background","");
        $("#li3").css("background","");
        $("#li4").css("background","");
        $("#tl-r").css("margin-top","-28px");
        $("#t1-r-ul").css("margin-top","-28px");
        $("#t1-r-ul1").css("margin-top","-28px");
        $("#ulli1>li").mouseover(function(){
            $(this).find(".classdivdiv000").css("display","block");
        })
        $("#ulli1>li").mouseout(function(){
            $(this).find(".classdivdiv000").css("display","none");
        })
        $("#ulli2>li").mouseover(function(){
            $(this).find(".classdivdiv001").css("display","block");
        })
        $("#ulli2>li").mouseout(function(){
            $(this).find(".classdivdiv001").css("display","none");
        })
        $("#ser1").mouseover(function(){
            $("#ser1>div").css("display","block");
        })
        $("#ser1").mouseout(function(){
            $("#ser1>div").css("display","none");
        })
        $("#ser5").mouseover(function(){
            $("#ser5>div").css("display","block");
        })
        $("#ser5").mouseout(function(){
            $("#ser5>div").css("display","none");
        })
        $("#ser2").mouseover(function(){
            $("#ser2>div").css("display","block");
        })
        $("#ser2").mouseout(function(){
            $("#ser2>div").css("display","none");
        })
        $("#ser3").mouseover(function(){
            $("#ser3>div").css("display","block");
        })
        $("#ser3").mouseout(function(){
            $("#ser3>div").css("display","none");
        })
        $("#ser4").mouseover(function(){
            $("#ser4>div").css("display","block");
        })
        $("#ser4").mouseout(function(){
            $("#ser4>div").css("display","none");
        })
    });
    $('.see').click(function(){
        var intCount=1;
        var supplierId=$(this).data('id');
        $.ajax({
            url :  "<?php echo url('home/Viewedauto/Handle'); ?>",
            type : "POST",
            async : false,
            data : "intCount="+intCount+"&supplierId="+supplierId,
            success : function(v){
                console.log(v);
            }
        });
    });

    $(".promptly1").mousemove(function(){
        $(this).removeClass("promptly1")
        $(this).addClass("promptly2")
    }).mouseout(function(){
        $(this).removeClass("promptly2")
        $(this).addClass("promptly1")
    })
    $(".viewMore").mousemove(function(){
        $(this).removeClass("viewMore")
        $(this).addClass("viewMore2")
    }).mouseout(function(){
        $(this).removeClass("viewMore2")
        $(this).addClass("viewMore")
    })

</script>
<!--//品牌更换数据模板-->
<script id="brand" type="text/html">
    {{# layui.each(d.list,function(b,item){  }}
    <li>
        <div class="img-3d">
            {{# layui.each(item.row,function(a,vo){ }}
            <div class="{{ a }} {{#  if(a == 1){ }} img-back {{# }else{ }} img-front {{# } }} ">
                <img src="{{ vo.logo }}"  style="height: 120px;width:140px;"/>
            </div>
            {{# }) }}
        </div>
        <div class="mask">
            <input type="hidden" class="back" name="{{ item['row']['1']['name'] }}" atc="Home/Goods/brandList?brand_id={{ item['row']['1']['id'] }}">
            <input type="hidden" class="front" name="{{ item['row']['0']['name'] }}" atc="Home/Goods/brandList?brand_id={{ item['row']['0']['id'] }}">
            <p>{{ item['row']['0']['name'] }}</p >
            <a href="Home/Goods/brandList?brand_id={{ item['row']['0']['id'] }}">点击进入</a >
        </div>
    </li>
    {{# }) }}
</script>

</body>
</html>