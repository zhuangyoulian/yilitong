<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:53:"./application/admin/view/rite_home\inquire_index.html";i:1577944108;s:43:"./application/admin/view/public\layout.html";i:1586428862;}*/ ?>
<!doctype html>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<!-- Apple devices fullscreen -->
<meta name="apple-mobile-web-app-capable" content="yes">
<!-- Apple devices fullscreen -->
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<link href="__PUBLIC__/static/css/main.css" rel="stylesheet" type="text/css">
<link href="__PUBLIC__/static/css/page.css" rel="stylesheet" type="text/css">
<link href="__PUBLIC__/static/font/css/font-awesome.min.css" rel="stylesheet" />
<!--[if IE 7]>
  <link rel="stylesheet" href="__PUBLIC__/static/font/css/font-awesome-ie7.min.css">
<![endif]-->
<link href="__PUBLIC__/static/js/jquery-ui/jquery-ui.min.css" rel="stylesheet" type="text/css"/>
<link href="__PUBLIC__/static/js/perfect-scrollbar.min.css" rel="stylesheet" type="text/css"/>
<style type="text/css">html, body { overflow: visible;}</style>
<script type="text/javascript" src="__PUBLIC__/static/js/jquery.js"></script>
<script type="text/javascript" src="__PUBLIC__/static/js/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript" src="__PUBLIC__/static/js/layer/layer.js"></script><!-- 弹窗js 参考文档 http://layer.layui.com/-->
<script type="text/javascript" src="__PUBLIC__/static/js/admin.js"></script>
<script type="text/javascript" src="__PUBLIC__/static/js/flexigrid.js"></script>
<script type="text/javascript" src="__PUBLIC__/static/js/jquery.validation.min.js"></script>
<script type="text/javascript" src="__PUBLIC__/static/js/common.js"></script>
<script type="text/javascript" src="__PUBLIC__/static/js/perfect-scrollbar.min.js"></script>
<script type="text/javascript" src="__PUBLIC__/static/js/jquery.mousewheel.js"></script>
<script src="__PUBLIC__/js/myFormValidate.js"></script>
<script src="__PUBLIC__/js/myAjax2.js"></script>
<script src="__PUBLIC__/js/global.js"></script>
    <script type="text/javascript">
    function delfun(obj){
        layer.confirm('确认删除？', {
                btn: ['确定','取消'] //按钮
            }, function(){
                // 确定
                $.ajax({
                    type : 'post',
                    url : $(obj).attr('data-url'),
                    dataType : 'json',
                    success : function(data){
                        layer.closeAll();
                        if(data){
                            $(obj).parent().parent().parent().remove();
                            layer.alert('删除成功', {icon: 1});  //alert('删除失败');
                        }else{
                            layer.alert('删除失败', {icon: 2});  //alert('删除失败');
                        }
                    }
                })
            }, function(index){
                layer.close(index);
            }
        );
    } 
    function delfunc(obj){
        layer.confirm('确认删除？', {
                btn: ['确定','取消'] //按钮
            }, function(){
                // 确定
                $.ajax({
                    type : 'post',
                    url : $(obj).attr('data-url'),
                    dataType : 'json',
                    success : function(data){
                        layer.closeAll();
                        if(data){
                            ajax_get_table('search-form2',cur_page);             
                            layer.alert('删除成功', {icon: 1});  //alert('删除失败');
                        }else{
                            layer.alert('删除失败', {icon: 2});  //alert('删除失败');
                        }
                    }
                })
            }, function(index){
                layer.close(index);
            }
        );
    }  
    function selectAll(name,obj){
    	$('input[name*='+name+']').prop('checked', $(obj).checked);
    }   
    
    function get_help(obj){
        layer.open({
            type: 2,
            title: '帮助手册',
            shadeClose: true,
            shade: 0.3,
            area: ['70%', '80%'],
            content: $(obj).attr('data-url'), 
        });
    }
    
    function delAll(obj,name){
    	var a = [];
    	$('input[name*='+name+']').each(function(i,o){
    		if($(o).is(':checked')){
    			a.push($(o).val());
    		}
    	})
    	if(a.length == 0){
    		layer.alert('请选择删除项', {icon: 2});
    		return;
    	}
    	layer.confirm('确认删除？', {btn: ['确定','取消'] }, function(){
    			$.ajax({
    				type : 'get',
    				url : $(obj).attr('data-url'),
    				data : {act:'del',del_id:a},
    				dataType : 'json',
    				success : function(data){
    					if(data == 1){
    						layer.msg('操作成功', {icon: 1});
    						$('input[name*='+name+']').each(function(i,o){
    							if($(o).is(':checked')){
    								$(o).parent().parent().remove();
    							}
    						})
    					}else{
    						layer.msg(data, {icon: 2,time: 2000});
    					}
    					layer.closeAll();
    				}
    			})
    		}, function(index){
    			layer.close(index);
    			return false;// 取消
    		}
    	);	
    }
</script>  

</head>
<script type="text/javascript" src="__ROOT__/public/static/js/layer/laydate/laydate.js"></script>

<body style="background-color: rgb(255, 255, 255); overflow: auto; cursor: default; -moz-user-select: inherit;">
<style>
.money{
    color: #999;
    background-color: #FFF;
    width: 24px;
    height: 24px;
    float: left;
    text-align: center;
    line-height: 24px;
    margin: 0 0 0 20px;
    position: relative;
    z-index: 1;
}
</style>
<div id="append_parent"></div>
<div id="ajaxwaitid"></div>
<div class="page">
  <div class="fixed-bar">
    <div class="item-title">
      <div class="subject">
        <h3>礼至家居商品订单</h3>
        <h5>商城实物商品交易订单查询及管理</h5>
      </div>
    </div>
  </div>
  <!-- 操作说明 -->
  <div id="explanation" class="explanation" style=" width: 99%; height: 100%;">
    <div id="checkZoom" class="title"><i class="fa fa-lightbulb-o"></i>
      <h4 title="提示相关设置操作时应注意的要点">操作提示</h4>
      <span title="收起提示" id="explanationZoom" style="display: block;"></span>
    </div>
     <ul>
      <li>查看操作可以查看订单详情, 包括支付费用, 商品详情等</li>
      <li>未支付的订单可以取消</li>
      <li>用户收货后, 如果没有点击"确认收货",系统自动根据设置的时间跟商家结算.</li>
    </ul>
  </div>
  <div class="flexigrid">
    <div class="mDiv">
      <div class="ftitle">
        <h3>礼至家居订单列表</h3>
        <h5>(共<?php echo $page->totalRows; ?>条记录)</h5>
      </div>
      <div title="刷新数据" class="pReload"><i class="fa fa-refresh"></i></div>
      <div class="money" style="color: #999;line-height: 24px;"><h5>当前列表应付/已付总额:0</h5></div>
    <form class="navbar-form form-inline"  method="post" action="<?php echo Url('Admin/RiteHome/export_order'); ?>"  name="search-form2" id="search-form2">
            <input type="hidden" name="order_by" value="order_id">
            <input type="hidden" name="sort" value="desc">
            <input type="hidden" name="user_id" value="<?php echo \think\Request::instance()->param('user_id'); ?>">
            <!--用于查看结算统计 包含了哪些订单-->
            <input type="hidden" value="<?php echo $_GET['order_statis_id']; ?>" name="order_statis_id" />
                                    
      <div class="sDiv">
        <div class="sDiv2">
          <input type="text" size="30" id="add_time_begin" name="add_time_begin" value="" class="qsbox"  placeholder="下单开始时间"  autocomplete="off">
        </div>
        <div class="sDiv2">
          <input type="text" size="30" id="add_time_end" name="add_time_end" value="" class="qsbox"  placeholder="下单结束时间"  autocomplete="off">
        </div>
        <?php if($act_list != 1): ?>
          <div class="sDiv2">    
              <select name="items_source" class="select" style="width:100px;margin-right:5px;margin-left:5px">
                  <option value="">项目查询</option>
                  <?php if(is_array($palte_list) || $palte_list instanceof \think\Collection): $k = 0; $__LIST__ = $palte_list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v): $mod = ($k % 2 );++$k;?>
                      <option value="<?php echo $v['role_name']; ?>"<?php if($keywords == $v['role_name']): ?> selected="selected"<?php endif; ?>><?php echo $v['role_name']; ?></option>
                  <?php endforeach; endif; else: echo "" ;endif; ?>
                      <option value="其它" <?php if($keywords == '其它'): ?> selected="selected"<?php endif; ?>>其它</option>
               </select>
           </div>
        <?php endif; ?>
        <div class="sDiv2">  
          <select name="pay_status" class="select" style="width:100px;margin-right:5px;margin-left:5px">
                    <option value="">支付状态</option>
                    <option value="0">未支付</option>
              <option value="1">已支付</option>
            </select>
        </div>
        <div class="sDiv2">    
            <select name="pay_code" class="select" style="width:100px;margin-right:5px;margin-left:5px">
                <option value="">支付方式</option>
                <option value="alipayMobile">移动端支付宝</option>
                <option value="alipay">支付宝支付</option>
                <option value="weixin">微信支付</option>
                <option value="weixinJSAPI">移动端微信付款</option>
                <option value="6">线下支付</option>
             </select>
         </div>
         <div class="sDiv2">   
             <select name="shipping_status" class="select" style="width:100px;">
                <option value="">发货状态</option>
                <option value="0">未发货</option>
                <option value="1">已发货</option>
                <option value="2">部分发货</option>
             </select>
          </div>
          <div class="sDiv2">  
             <select name="order_status" class="select" style="width:100px;">
                  <option value="">订单状态</option>
                  <?php if(is_array($order_status) || $order_status instanceof \think\Collection): $k = 0; $__LIST__ = $order_status;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v): $mod = ($k % 2 );++$k;?>
                      <option value="<?php echo $k-1; ?>"><?php echo $v; ?></option>
                  <?php endforeach; endif; else: echo "" ;endif; ?>
              </select>       
         </div>
         <div class="sDiv2">                  
          <select  name="keytype" class="select">
            <option value="consignee">收货人</option>
            <option value="mobile">手机号</option>
            <option value="order_sn">订单编号</option>
          </select>
         </div>
         <div class="sDiv2">   
          <input type="text" size="30" name="keywords" class="qsbox" placeholder="搜索相关数据...">
        </div>
        <div class="sDiv2">  
          <input type="button" onclick="ajax_get_table('search-form2',1)"  class="btn" value="搜索">
        </div>
      </div>
     </form>
    </div>
    <div class="hDiv">
      <div class="hDivBox" id="ajax_return">
        <table cellspacing="0" cellpadding="0">
          <thead>
            <tr>
                <th class="sign" axis="col0">
                  <div style="width: 24px;"><i class="ico-check"></i></div>
                </th>
                <th align="left" abbr="order_sn" axis="col3" class="">
                  <div style="text-align: left; width: 160px;" class="">id/订单编号</div>
                </th>
                <th align="left" abbr="consignee" axis="col4" class="">
                  <div style="text-align: left; width: 150px;" class="">收货人</div>
                </th>
                <th align="center" abbr="article_show" axis="col5" class="">
                  <div style="text-align: center; width: 60px;" class="">总金额</div>
                </th>
                <th align="center" abbr="article_time" axis="col6" class="">
                  <div style="text-align: center; width: 60px;" class="">应付金额</div>
                </th>
                <th align="center" abbr="article_time" axis="col6" class="">
                  <div style="text-align: center; width: 60px;" class="">订单状态</div>
                </th>
                <th align="center" abbr="article_time" axis="col6" class="">
                  <div style="text-align: center; width: 60px;" class="">支付状态</div>
                </th>
                <th align="center" abbr="article_time" axis="col6" class="">
                  <div style="text-align: center; width: 70px;" class="">发货状态</div>
                </th>
                <th align="center" abbr="article_time" axis="col6" class="">
                  <div style="text-align: center; width: 70px;" class="">支付方式</div>
                </th>
                <th align="center" abbr="article_time" axis="col6" class="">
                  <div style="text-align: center; width: 70px;" class="">配送方式</div>
                </th>
                <th align="center" abbr="article_time" axis="col6" class="">
                  <div style="text-align: center; width: 60px;" class="">订单来源</div>
                </th>
                <th align="center" abbr="article_time" axis="col6" class="">
                  <div style="text-align: center; width: 120px;" class="">下单时间</div>
                </th>
                <th align="center" abbr="article_time" axis="col6" class="">
                  <div style="text-align: center; width: 120px;" class="">收货时间</div>
                </th>
                <th align="center" abbr="article_time" axis="col6" class="">
                  <div style="text-align: center; width: 120px;" class="">所属项目</div>
                </th>
                <th align="left" axis="col1" class="handle">
                  <div style="text-align: left; width: 120px;">操作</div>
                </th>
                <th style="width:100%" axis="col7">
                  <div></div>
                </th>
              </tr>
            </thead>
        </table>
      </div>
    </div>
    <div class="tDiv">
      <div class="tDiv2">
        <div class="fbutton"> 
          <a href="javascript:exportReport()">
              <div class="add" title="选定行数据导出excel文件,如果不选中行，将导出列表所有数据">
                <span><i class="fa fa-plus"></i>导出数据</span>
              </div>
            </a> 
          </div>
          <!-- <div class="fbutton"> 
          <a href="/index.php?m=Admin&c=Order&a=add_order">
              div class="add" title="添加订单">
                <span><i class="fa fa-plus"></i>添加订单</span>
              </div 
            </a> 
          </div> -->
      </div>
      <div style="clear:both"></div>
    </div>
    <div class="bDiv" style="height: auto;">
      <div id="flexigrid" cellpadding="0" cellspacing="0" border="0">
      </div>
      <div class="iDiv" style="display: none;"></div>
    </div>
    <!--分页位置--> 
    </div>
</div>
<script type="text/javascript">

   
    $(document).ready(function(){ 
     
      $('#add_time_begin').layDate(); 
      $('#add_time_end').layDate();
      
    // 点击刷新数据
    $('.fa-refresh').click(function(){
      location.href = location.href;
    });
    
    ajax_get_table('search-form2',1);
    
    $('.ico-check ' , '.hDivBox').click(function(){
      $('tr' ,'.hDivBox').toggleClass('trSelected' , function(index,currentclass){
          var hasClass = $(this).hasClass('trSelected');
          $('tr' , '#flexigrid').each(function(){
            if(hasClass){
              $(this).addClass('trSelected');
            }else{
              $(this).removeClass('trSelected');
            }
          });  
        });
    });
     
  });
    
    
    //ajax 抓取页面
    function ajax_get_table(tab,page){
        cur_page = page; //当前页面 保存为全局变量
        $.ajax({
            type : "POST",
            url:"/index.php/Admin/RiteHome/inquire_ajax_index/p/"+page,//+tab,
            data : $('#'+tab).serialize(),// 你的formid
            success: function(data){
                $("#flexigrid").html('');
                $("#flexigrid").append(data);
                
              // 表格行点击选中切换
              $('#flexigrid > table>tbody >tr').click(function(){
                $(this).toggleClass('trSelected');
            });
               
            }
        });
    }
  
 // 点击排序
    function sort(field){
        $("input[name='order_by']").val(field);
        var v = $("input[name='sort']").val() == 'desc' ? 'asc' : 'desc';
        $("input[name='sort']").val(v);
        ajax_get_table('search-form2',cur_page);
    }
  
  function exportReport(){
    $('#search-form2').submit();
  }

  function del(id)
  {
    if(!confirm('确定要删除吗?'))
        return false;
    $.ajax({
      url:"/index.php?m=Admin&c=RiteHome&a=delete_order&order_id="+id,
      success: function(v){ 
        if(v.code == 1){
                layer.msg(v.msg, {icon: 1,time: 1000}); //alert(v.msg);
                ajax_get_table('search-form2',cur_page);                                                      
        }else{
                layer.msg(v.msg, {icon: 2,time: 1000}); //alert(v.msg);
                ajax_get_table('search-form2',cur_page);                                                      
        }
      }
    }); 
    return false;
  } 
</script>
</body>
</html>