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
//轮播
window.onload = function(){

    lunbo();
}


var id;
var index = 0;
function lunbo(){
    var lengthNum=$(".carousel").children().length;
    var indexNum=0;
    id = setInterval(function(){

        $(".carousel>li:eq("+indexNum+")").css("display","none");
        indexNum=indexNum+1;
        if(indexNum==lengthNum){
            indexNum=0;
            $(".carousel>li").css("display","block");
        }
         },3000);
    };

function stop()
    {
    clearInterval(id);
    };


function autoScroll(obj) {
    $("#content ul").animate({
        marginTop: "-34px"
    }, 500, function () {
        $(this).css({ marginTop: "0px" }).find("li:first").appendTo(this);
    })
    $("#content1 ul").animate({
        marginTop: "-34px"
    }, 500, function () {
        $(this).css({ marginTop: "0px" }).find("li:first").appendTo(this);
    })
}
$(function () {
    if ($("#content ul li").length > 2) {
        setInterval('autoScroll("#content")', 2000);
    }
})

$(function () {
    if ($("#content1 ul li").length > 2) {
        setInterval('autoScroll("#content1")', 2000);
    }
})



