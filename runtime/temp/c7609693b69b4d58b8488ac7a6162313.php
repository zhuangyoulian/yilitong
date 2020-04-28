<?php if (!defined('THINK_PATH')) exit(); /*a:3:{s:41:"./application/mobile/view/goods\gift.html";i:1587973417;s:44:"./application/mobile/view/public\header.html";i:1585272597;s:44:"./application/mobile/view/public\footer.html";i:1586162561;}*/ ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <meta name=”viewport” content=”width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no”>
    <title>所有分类</title>
    <link rel="stylesheet" href="__STATIC__/css/bootstrap.min.css">
    <link rel="stylesheet" href="__STATIC__/mobile/css/style.css">
    <script src="__PUBLIC__/js/jquery-3.1.1.min.js" type="text/javascript" charset="utf-8"></script>
    <script src="__PUBLIC__/js/style.js" type="text/javascript" charset="utf-8"></script>
    <script src="__PUBLIC__/js/mobile-util.js" type="text/javascript" charset="utf-8"></script>
    <script src="__PUBLIC__/js/global.js"></script>
    <script src="__PUBLIC__/js/gt.js"></script>
    <script src="__MOBILE__/js/mobile_layer.js"  type="text/javascript" ></script>
    <link rel="stylesheet" href="__MOBILE__/css/mobile_layer.css">
    <script src="__PUBLIC__/js/swipeSlide.min.js" type="text/javascript" charset="utf-8"></script>
</head>
<body class="[body]">

<link rel="stylesheet" href="__MOBILE__/css/mobile.css">
<link rel="stylesheet" href="__MOBILE__/css/iconfont.css">
<style>
    body{
        background: #fff;
        padding-top: 4rem;
    }
    .topAS{
        position: fixed;
        left: 50%;
        top: 0;
        transform: translateX(-50%);
        -webkit-transform: translateX(-50%);
        -moz-transform: translateX(-50%);
        -o-transform: translateX(-50%);
        -ms-transform: translateX(-50%);
        z-index:99999;
    }
  	.catalogTab a{
        text-decoration: none;
    }
    .tp-class-list a{
        text-decoration: none;
    }
    .classlist .category1::-webkit-scrollbar {
        display: none;
    }
    .classlist .category1 li a{
        display: block;
        width: 100%;
        height: 100%;
        border-bottom: 1px solid #efefef;
        border-right: 1px solid #efefef;
    }
    .classlist .category1 li:last-of-type a{
        border-bottom: none;
    }
</style>
<div class="topAS">
    <div class="classreturn">
        <div class="content">
            <div class="ds-in-bl return">
                <a href="/"><img src="__STATIC__/images/logo.png" alt="一礼通"></a>
            </div>
            <div class="ds-in-bl search" style="margin-left:15%;">
                <form action="" method="post">
                    <div class="sear-input">
                        <a href="<?php echo Url('Goods/ajaxSearch'); ?>">
                            <input type="text" value="" placeholder="搜索礼品/商铺/设计师">
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <ul class="catalogTab clearfix">
        <li><a href="<?php echo url('Goods/gift'); ?>" class="catalogTabAct">送礼攻略</a><span></span></li>
        <li><a href="<?php echo url('Goods/categoryList'); ?>">商品分类</a></li>
        <li><a href="<?php echo url('Supplier/recsupplier'); ?>">店铺精选</a></li>
    </ul>
</div>
<div>
    <div class="catalogTab-list" style="margin-bottom:200px; ">
        <div class="flool classlist catalogTab-col" style="background:#fff;">
            <div class="fl category1">
                <ul>
                    <?php $m = '0'; if(is_array($scenario_category_tree) || $scenario_category_tree instanceof \think\Collection): if( count($scenario_category_tree)==0 ) : echo "" ;else: foreach($scenario_category_tree as $k=>$vo): if($vo[level] == 1): ?>
                            <li>
                                <a href="javascript:void(0);" <?php if($m == 0): endif; ?> data-id="<?php echo $m++; ?>"><?php echo getSubstr($vo['name'],0,12); ?></a>
                            </li>
                        <?php endif; endforeach; endif; else: echo "" ;endif; ?>
                </ul>
            </div>
            <div class="fr category2">
                <?php $j = '0'; ?>
                <!-- <foreach name="scenario_category_tree" key="kk" item="vo"> -->
                <?php if(is_array($tmp) || $tmp instanceof \think\Collection): if( count($tmp)==0 ) : echo "" ;else: foreach($tmp as $kk=>$vo): ?>
                    <div class="branchList">
                        <!--广告图-s-->
                        <!--<div class="tp-bann"  data-id="<?php echo $j++; ?>">-->
                            <!--<?php $where ="pid =  400";$ad_position = M("ad_position")->cache(true,YLT_CACHE_TIME)->column("position_id,position_name,ad_width,ad_height","position_id");$result = M("ad")->where("$where and enabled = 1  ")->order("orderby desc")->cache(true,YLT_CACHE_TIME)->limit("1")->select();
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
    ?>-->
                                <!--<a href="<?php echo $v['ad_link']; ?>" <?php if($v['target'] == 1): ?>target="_blank"<?php endif; ?> >-->
                                    <!--<img src="<?php echo (isset($v[ad_code]) && ($v[ad_code] !== '')?$v[ad_code]:'__MOBILE__/images/zy.png'); ?>" title="<?php echo $v[title]; ?>">-->
                                <!--</a>-->
                            <!--<?php endforeach; ?>-->
                        <!--</div>-->
                        <!--广告图-e-->
                        <!--分类-s-->
                        <div class="tp-class-list">
                            <?php if(is_array($vo['tmenu']) || $vo['tmenu'] instanceof \think\Collection): if( count($vo['tmenu'])==0 ) : echo "" ;else: foreach($vo['tmenu'] as $k2=>$v2): ?>
                            <h4><i></i><span><a href="<?php echo Url('Goods/goodsList',array('gift_id'=>$v2[id])); ?>"><?php echo $v2['name']; ?></a></span><i></i></h4>
                                <?php if(is_array($v2['sub_menu']) || $v2['sub_menu'] instanceof \think\Collection): if( count($v2['sub_menu'])==0 ) : echo "" ;else: foreach($v2['sub_menu'] as $k3=>$v3): ?>
                                    
                                <ul>
                                    <li>
                                        <a href="<?php echo Url('Goods/goodsList',array('gift_id'=>$v3[id])); ?>">
                                            <!-- <div class="<?php echo $v3['icon']; ?>"> -->

                                            <svg style="width: 4em;height: 4em;">
                                                <use xlink:href="<?php echo $v3['icon']; ?>"></use>
                                            </svg>
                                           
                                            <p><?php echo $v3['name']; ?></p>
                                        </a>
                                    </li>
                                </ul>

                                <?php endforeach; endif; else: echo "" ;endif; ?>
                             <div style="clear:both;"></div>
                            <?php endforeach; endif; else: echo "" ;endif; ?>

                        </div>
                        <!--分类-e-->
                    </div>
                <?php endforeach; endif; else: echo "" ;endif; ?>
            </div>
        </div>
    </div>
</div>



  <!--   <div class="catalogTab-list">
        <div class="catalogTab-col">
            <?php if(is_array($tmp) || $tmp instanceof \think\Collection): $i = 0; $__LIST__ = $tmp;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v): $mod = ($i % 2 );++$i;?>
                <div class="catalog-list-col">
                    <h6 class="catalog-title"><?php echo $v['name']; ?></h6>

                    <ul class="catalog-list">
                        <?php if(is_array($v['tmenu']) || $v['tmenu'] instanceof \think\Collection): $i = 0; $__LIST__ = $v['tmenu'];if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v2): $mod = ($i % 2 );++$i;?>
                        <li>
                            <a href="<?php echo Url('Goods/goodsList',array('gift_id'=>$v2[id])); ?>">
                                <img src="<?php echo $v2['image']; ?>" alt="">
                            </a>
                        </li>
                        <?php endforeach; endif; else: echo "" ;endif; ?>
                    </ul>

                </div>
            <?php endforeach; endif; else: echo "" ;endif; ?>
        </div>
    </div> -->

    <!--底部-start-->
        <style>
        .footer-new {
            position: fixed;
            background: #fff;
            width: 100%;
            bottom: 0;
            overflow: auto;
            text-align: center;
            display: flex;
            flex-direction: row;
        }
        .footer-div {
          flex: 1;
         
            /*width: 15%;*/
            margin: 0.3rem 0.4rem;
           /* float: left;*/
            bottom: 0;
        }
        .footer-a {
            text-decoration: none;
            color: #000;
            font-family: "微软雅黑";
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .footer-a:visited{
            text-decoration: blink;
        }
        .footer-div >.footer-a> img {
            width: 40%;
            margin: auto;
            display: block;
        }
        .footer-div >.footer-a> span {
            text-align: center;
            display: block;
            font-size: 0.5rem;
        }
        .footer-div >.footer-a> img{
            /* margin: 0 0 0.15rem 0.8rem; */
            margin-bottom: 0.15rem;
        }
    </style>

    <div class="footer-new">
        <div class="footer-div">
            <a class="footer-a" href="<?php echo Url('Index/index'); ?>">
         <?php
            $url=$_SERVER['REQUEST_URI'];
            if(strrchr($url,"Index")){
                echo '<img src="__MOBILE__/images/b_home_selected@2x.png"> <span style="color: red;">首页</span>';
            }else{
                echo '<img src="__MOBILE__/images/b_home@2x.png"> <span>首页</span>';
                }
        ?>
            </a>
        </div>
        <div class="footer-div">
            <a class="footer-a" href="<?php echo Url('Goods/categoryList'); ?>">
        <?php
            if(strrchr($url,"Goods")){
                echo '<img src="__MOBILE__/images/b_nav_selected@2x.png"> <span style="color: red;">分类</span>';
                }else{
                echo ' <img src="__MOBILE__/images/b_nav@2x.png"> <span>分类</span>';
            }
        ?>
            </a>
        </div>
        <div class="footer-div">
            <a class="footer-a" href="<?php echo Url('Index/zixun'); ?>" ><img src="__MOBILE__/images/b_consult@2x.png"> <span>咨询</span></a>
        </div>
        <div class="footer-div">
            <a class="footer-a" href="<?php echo Url('User/index'); ?>">
                <?php
                
            if(strrchr($url,"User")){
                echo '<img src="__MOBILE__/images/b_me_selected@2x.png"> <span style="color: red;">我的</span>';
                }else{
                echo ' <img src="__MOBILE__/images/b_me@2x.png"> <span>我的</span>';
                }
                ?>
            </a>
        </div>
    </div>
    <!--底部-end-->

<script src="__MOBILE__/js/iconfont.js"></script>
<script src="//at.alicdn.com/t/font_1350285_6s0cowarqq7.js"></script>
<script>
    $(function () {
        //点击切换2 3级分类
        var array=new Array();
        $('.category1 li').each(function(){
            array.push($(this).position().top-0);
        });
        $('.branchList').eq(0).show().siblings().hide();
        $('.category1 li').click(function() {
            var index = $(this).index() ;
            $('.category1').delay(200).animate({scrollTop:array[index]},300);
            $(this).addClass('cur').siblings().removeClass();
            $('.branchList').eq(index).show().siblings().hide();
            $('.category2').scrollTop(0);
        });
    });
</script>
	</body>
</html>
