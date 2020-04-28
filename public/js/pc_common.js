 /**
 * addcart 将商品加入购物车
 * @goods_id  商品id
 * @num   商品数量
 * @form_id  商品详情页所在的 form表单
 * @to_catr 加入购物车后再跳转到 购物车页面 默认不跳转 1 为跳转
 */
$(document).ready(function(){
			    
			    var  url=$('.Finddiv2li1 .onli').attr('name');
			        $("#sourch_form").attr("action",url);
			    $(".Finddiv2li1>li").click(function(){
			        $(this).siblings().removeClass("onli");
			        $(this).addClass("onli");
			        var  url=$('.Finddiv2li1 .onli').attr('name');
			        $("#sourch_form").attr("action",url);
			    });
			    $(".site-navli>li").click(function(){
			        $(this).siblings().css("background","");
			        $(this).css("background","#b30c2d");
			    });
			    $(".classdiv1>div").click(function(){
			        var index=$(this).index();
			        $(this).siblings().removeClass("ondiv");
			        $(this).addClass("ondiv");
			        if(index==0){
			            $(".classdiv21").css("display","block");
			            $(".classdiv22").css("display","none");
			        };
			        if(index==1){
			            $(".classdiv21").css("display","none");
			            $(".classdiv22").css("display","block");
			        };
			
			    });
			    $(".li1").click(function(){
			        $(".li1").css({"border-bottom":"2px solid #E6002D","color":"#E6002D"});
			        $(".li2").css({"border-bottom":"2px solid #000","color":"#000"});
			        $(".li1div").css("display","block");
			        $(".li2div").css("display","none");
			    });
			    $(".li2").click(function(){
			        $(".li2").css({"border-bottom":"2px solid #E6002D","color":"#E6002D"});
			        $(".li1").css({"border-bottom":"2px solid #000","color":"#000"});
			        $(".li2div").css("display","block");
			        $(".li1div").css("display","none");
			    });
			    $(".trans2>p").mouseenter(function() {
			        $(this).next().css("display","block");
			    });
			    $(".trans2>p").mouseleave(function() {
			        $(this).next().css("display","none");
			    });
			    $(".and").mouseenter(function() {
			        $(".findimgdivand").css("display","block")
			    });
			    $(".and").mouseleave(function() {
			        $(".findimgdivand").css("display","none")
			    });
			    $(".ios").mouseenter(function() {
			        $(".findimgdivios").css("display","block")
			    });
			    $(".ios").mouseleave(function() {
			        $(".findimgdivios").css("display","none")
			    });
			
			});



function AjaxAddCart(goods_id,num,to_catr)
{	
        // 如果有商品规格 说明是商品详情页提交
        if($("#buy_goods_form").length > 0){
                $.ajax({
                        type : "POST",
                        url:"/Home/Cart/ajaxAddCart/to_catr/"+to_catr,
                        data : $('#buy_goods_form').serialize(),// 你的formid 搜索表单 序列化提交                        
						dataType:'json',
                        success: function(data){	
						
								if(data.status < 0)
								{
									layer.alert(data.msg, {icon: 2});
									return false;
								}
							   // 加入购物车后再跳转到 购物车页面
							   if(to_catr == 1)  //直接购买 到确认订单页面
							   {
								   // location.href = "/Home/Cart/cart";
								   location.href = "/Home/Cart/orderconfirm/selected/2/goods_id/"+goods_id;
							   }
							   else
							   {
								    cart_num = parseInt($('#cart_quantity').html())+parseInt($('input[name="goods_num"]').val());
								    $('#cart_quantity').html(cart_num)
									layer.open({
										  type: 2,
										  title: '温馨提示',
										  skin: 'layui-layer-rim', //加上边框
										  area: ['490px', '386px'], //宽高
                                          content:["/Home/Goods/open_add_cart","no"],
                                          success: function(layero, index) {
                                                layer.iframeAuto(index);
                                        }
									});
                                    
							   }
                        }
                });     
        }else{ // 否则可能是商品列表页 收藏页 等点击加入购物车的
                $.ajax({
                        type : "POST",
                        url:"/Home/Cart/ajaxAddCart",
                        data :{goods_id:goods_id,goods_num:num} ,
						dataType:'json',
                        success: function(data){
							   if(data.status == -1)
							   {
                                 	alert(data.msg);
									//location.href = "/Home/Goods/goodsInfo/id/"+goods_id;
							   }
							   else
							   {
								    // 加入购物车有误
								    if(data.status < 0)
									{
										layer.alert(data.msg, {icon: 2});
										return false;
									}
								    cart_num = parseInt($('#cart_quantity').html())+parseInt(num);
								    $('#cart_quantity').html(cart_num)
									layer.open({
										  type: 2,
										  title: '温馨提示',
										  skin: 'layui-layer-rim', //加上边框
										  area: ['490px', '386px'], //宽高
										  content:"/Home/Goods/open_add_cart"
									});							   
							   }							   							   
                        }
                });            
        }
}

// 点击收藏商品
function collect_goods(goods_id){
	$.ajax({
		type : "GET",
		dataType: "json",
		url:"/Home/goods/collect_goods/goods_id/"+goods_id,//+tab,
		success: function(data){
            layer.msg(data.msg, {icon: 1});
			//alert(data.msg);
		}
	});
}

 // 点击收藏店铺
 function collect_stores(stores_id){
	 $.ajax({
		 type : "GET",
		 dataType: "json",
		 url:"/Home/Supplier/collect_stores/supplier_id/"+stores_id,//+tab,
		 success: function(data){
			 layer.msg(data.msg, {icon: 1});
			 //alert(data.msg);
		 }
	 });
 }