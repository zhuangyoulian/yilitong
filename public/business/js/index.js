/// <reference path="jquery-1.10.2.min.js" />

var i = 0;//全局变量
var timer;
$(function () {//页面加载之后
    $(".qig").eq(0).show().siblings().hide();//第一张图片显示，其余的图片隐藏
    A();
    $(".qtab").hover(function () {
        i = $(this).index();
        Show();
        clearInterval(timer);
    }, function () {
        A();
    });
    $("#qlunbo").hover(function () {
        $(".qbtn").show();
    }, function () {
        $(".qbtn").hide();
    });
    $(".qbtn1").click(function () {
        clearInterval(timer);
        i--;
        if (i == -1)
        {
            i = 4;
        }
        Show();
        A();
    });
    $(".qbtn2").click(function () {
        clearInterval(timer);
        i++;
        if (i ==5) {
            i = 0;
        }
        Show();
        A();
    });
});

function Show()
{
    $(".qig").eq(i).fadeIn(300).siblings().fadeOut(300);
    $(".qtab").eq(i).addClass("bg1").siblings().removeClass("bg1");
}
function A()
{
    timer = setInterval(function () {//间隔4s图片轮播一次
        i++;
        if (i == 5) {
            i = 0;
        }
        Show();
    }, 4000);
}

 $(function(){
	setTimeout("takeCount()", 1000);
	//首页Tab标签卡滑门切换
    $(".tabs-nav > li > h3").bind('mouseover', (function(e) {
    	if (e.target == this) {
    		var tabs = $(this).parent().parent().children("li");
    		var panels = $(this).parent().parent().parent().children(".tabs-panel");
    		var index = $.inArray(this, $(this).parent().parent().find("h3"));
    		if (panels.eq(index)[0]) {
    			tabs.removeClass("tabs-selected").eq(index).addClass("tabs-selected");
    			panels.addClass("tabs-hide").eq(index).removeClass("tabs-hide");
    		}
    	}
    }));

	$('.jfocus-trigeminy > ul > li > a').jfade({
		start_opacity: "1",
		high_opacity: "1",
		low_opacity: ".5",
		timing: "200"
	});
	$('.fade-img > a').jfade({
		start_opacity: "1",
		high_opacity: "1",
		low_opacity: ".5",
		timing: "500"
	});
	$('.middle-goods-list > ul > li').jfade({
		start_opacity: "0.9",
		high_opacity: "1",
		low_opacity: ".25",
		timing: "500"
	});
	$('.recommend-brand > ul > li').jfade({
		start_opacity: "1",
		high_opacity: "1",
		low_opacity: ".5",
		timing: "500"
	});
	$(".full-screen-slides").fullScreen();
	$(".jfocus-trigeminy").jfocus();
	$(".right-side-focus").jfocus();
	$(".groupbuy").jfocus({time:8000});
	$("#saleDiscount").jfocus({time:8000});
})
 

