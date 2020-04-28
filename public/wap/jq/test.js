$(function(){
$(".footul>li").click(function() {
    $(this).siblings().find("div:eq(0)").css("display","block");
    $(this).siblings().find("div:eq(1)").css("display","none");
    $(this).siblings().find("span").css("color","#000");
    $(this).find("div:eq(0)").css("display","none");
    $(this).find("div:eq(1)").css("display","block");
    $(this).find("span").css("color","#E6002D");

});
$(".titlediv>span").click(function () {
    $(this).css("color","#f00");
    $(this).siblings().css("color","");
})
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

