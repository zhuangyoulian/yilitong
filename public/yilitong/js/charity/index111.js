$(function() {
//     $('.navlist >.nav').click(function() {
//         $(this).addClass('active').siblings().removeClass('active');
//         var ind = $(this).index();
//         console.log(ind);
//         if (ind == 0) {
//             $('section.kuai1').removeClass('hidden');
//             $('section.kuai2').addClass('hidden');
//             $('section.kuai3').addClass('hidden');
//         } else if (ind == 1) {
//             $('section.kuai2').removeClass('hidden');
//             $('section.kuai1').addClass('hidden');
//             $('section.kuai3').addClass('hidden');
//         } else if (ind == 2) {
//             $('section.kuai3').removeClass('hidden');
//             $('section.kuai1').addClass('hidden');
//             $('section.kuai2').addClass('hidden');
//         }
//     });
$(document).ready(function(){
    login();
});
$('.navlist >.nav').click(function() {
    $(this).addClass('active').siblings().removeClass('active');
    var ind = $(this).index();
    console.log(ind);
    $("input[name='is_purchase']").val(ind);
    if (ind == 1) {
        $('section.kuai1').removeClass('hidden');
        $('section.kuai2').addClass('hidden');
        $('section.kuai3').addClass('hidden');
    } else if (ind == 2) {
        $('section.kuai2').removeClass('hidden');
        $('section.kuai1').addClass('hidden');
        $('section.kuai3').addClass('hidden');
    } else if (ind == 3) {
        $('section.kuai3').removeClass('hidden');
        $('section.kuai1').addClass('hidden');
        $('section.kuai2').addClass('hidden');
    }
});

// 物资用途控制受捐方的显示
$('input[name="materialUse"]').change(function() {
    var chek = $('input[name="materialUse"]:checked').val();
    console.log(chek);
    if (chek == 1 || chek == 3) {
        $('#receipthelp').removeClass('hidden');
    } else {
        $('#receipthelp').addClass('hidden');
    }
});



isSelected();

function isSelected() {
    $('input[name^="recipient"]:checked').each(function() {
        $(this).nextAll('.select_num').removeClass('hidden');
    });
    $('input[name^="needhelp"]:checked').each(function() {
        $(this).nextAll('.select_num').removeClass('hidden');
    });
    var chek = $('input[name="materialUse"]:checked').val();

    if (chek == 1 || chek == 3) {
        $('#receipthelp').removeClass('hidden');
    } else {
        $('#receipthelp').addClass('hidden');
    }

}


// 捐赠物资的下面数量的显示与隐藏
$('input[name^="recipient"]').click(function() {
    if ($(this).attr('checked')) {
        $(this).nextAll('.select_num').addClass('hidden');
        $(this).attr('checked', false);
    } else {
        $(this).nextAll('.select_num').removeClass('hidden');
        $(this).attr('checked', true);
    }
});

    function login(){
        $.ajax({
                type : 'post',
                url : '/index.php?m=Home&c=Cart&a=cart_login',
                dataType : 'json',
                success : function(res){
                    if(res.status == 1){
                        var o = $('#orderconfirm');
                        console.log(o);
                        if (o.css('display')=="none") {
                            o.show();
                            $(this).html("hide");
                            //直接按回车登录
                            document.onkeydown = function(e){
                                var ev = document.all ? window.event : e;
                                if(o.css('display')!="none" && ev.keyCode==13 ) {
                                    checkSubmit();
                                }
                            };
                        }else{
                            o.hide();
                            $(this).html("show");
                        }
                        return false; 
                    }
                },
                error : function(XMLHttpRequest, textStatus, errorThrown) {
                    alert('网络失败，请刷新页面后重试');
                }
            })
    }
    $('#checkSubmit_login').click(function() {
        checkSubmit();
    })


    function checkSubmit()
    {
        var mobile = $('#mobile').val();
        var password = $('#password').val();
        var referurl = $('#referurl').val();
        var verify_code = $('#verify_code').val();
        if(mobile == ''){
            alert('用户名不能为空');
            return false;
        }
        if(!checkMobile(mobile) && !checkEmail(mobile)){

            alert('账号格式不匹配!');
            return false;
        }
        if(password == ''){
            alert('密码不能为空!');
            return false;
        }
        $.ajax({
            type : 'post',
            url : '/index.php?m=Home&c=User&a=login&t='+Math.random(),
            data : {mobile:mobile,password:password,referurl:referurl,verify_code:verify_code},
            dataType : 'json',
            success : function(res){
                if(res.status == 1){
                    $('#orderconfirm').hide();
                    window.location.href = res.url;
                    // window.location.reload()
                    return false; 

                }else{
                    alert(res.msg);
                    verify();
                }
            },
            error : function(XMLHttpRequest, textStatus, errorThrown) {
            layer.alert('网络失败，请刷新页面后重试', {icon: 2});
            }
        })

    }
    function checkMobile(tel) {
        var reg = /(^1[3|4|5|6|7|8|9][0-9]{9}$)/;
        if (reg.test(tel)) {
            return true;
        }else{
            return false;
        };
    }
    function checkEmail(str){
        var reg = /^[a-z0-9]([a-z0-9\\.]*[-_]{0,4}?[a-z0-9-_\\.]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+([\.][\w_-]+){1,5}$/i;
        if(reg.test(str)){
            return true;
        }else{
            return false;
        }
    }
    
// 提交捐赠或自用表单
$('#submitJuan').click(function() {
    login();
    if ($("input[name='gethelp']:checked").val() == '4') {
        var beneficiaries= '向公众捐赠';
    }else if($("input[name='gethelp']:checked").val() == '5'){
        var beneficiaries= $("input[name='beneficiaries']").val();
    }else{
        var beneficiaries= $('input[name^="hostical"]:checked').val();
    }
    var value={
        is_purchase:$("input[name='is_purchase']").val(),
        is_donate:$("input[name='materialUse']").val(),
        budget:$("input[name='budget']").val(),
        do_name:$("input[name='do_name']").val(),
        do_phone:$("input[name='do_phone']").val(),
        do_company:$("input[name='do_company']").val(),
        do_address:$("input[name='do_address']").val(),
        comment:$("#comment").val(),
        materials:$("input[name='materials']").val(),
        beneficiaries:beneficiaries,
    }
    console.log(value);
    if (value.is_donate != '' && value.materials != '' && value.budget != '' && value.do_name != '' && value.do_phone != '') {
        if (value.is_purchase == 1 && (((value.is_donate == 2 || value.is_donate == 3 ) && value.beneficiaries != '' ) || value.is_donate == 1)) {
            $.ajax({
                type: "POST",
                url: "/Home/Charity/add_charity",
                data:value,
                dataType: "json",
                success: function (data) {
                  if (data.status == 1) {
                      $('.wrap').removeClass('hidden');
                      $('.successBox').removeClass('hidden');
                      // layer.msg(data.msg, {icon: 1});
                      // location.href = "javascript:history.back(-1);";
                  }else{
                      alert('提交失败，请联系管理员！');
                  }
                },
                error: function () {
                      alert("服务器繁忙, 请联系管理员!");
                },
            });
        }
    }
    var juanz_username = $('#juanz_username').val().trim();
    var juanz_tel = $('#juanz_tel').val().trim();
    var checked_recipient = [];
    $('input[name^="recipient"]:checked').each(v => {
        checked_recipient.push(v);
    });
    if (juanz_username == '') {
        $('#submitJuan .error').html('请输入联系人姓名');
    } else if (juanz_tel == "") {
        $('#submitJuan .error').html('请输入联系方式');
    } else if (checked_recipient.length == 0) {
        $('#submitJuan .error').html('请输入捐赠/自用物资');
    // } else {
    //     $('.wrap').removeClass('hidden');
    //     $('.successBox').removeClass('hidden');
    }
    console.log(checked_recipient);

});
$('#submitGonghuo').click(function() {
    var supply_goods_0=[];
    var supply_goods_1=[];
    var supply_goods_2=[];
    var supply_goods_3=[];
    for(var j=0;j<$(".form_three").length-1;j++){
        if($(".form_three").eq(j).find("input[name='supply_goods_0[]']").val()!=''){
            supply_goods_0[j]=$(".form_three").eq(j).find("input[name='supply_goods_0[]']").val();
        }
        if($(".form_three").eq(j).find("input[name='supply_goods_1[]']").val()!=''){
            supply_goods_1[j]=$(".form_three").eq(j).find("input[name='supply_goods_1[]']").val();
        }
        if($(".form_three").eq(j).find("input[name='supply_goods_2[]']").val()!=''){
            supply_goods_2[j]=$(".form_three").eq(j).find("input[name='supply_goods_2[]']").val();
        }
        if($(".form_three").eq(j).find("input[name='supply_goods_3[]']").val()!=''){
            supply_goods_3[j]=$(".form_three").eq(j).find("input[name='supply_goods_3[]']").val();
        }
    }
    var value={
        is_purchase:$("input[name='is_purchase']").val(),
        do_phone:$("input[name='do_phone_s']").val(),
        do_company:$("input[name='do_company_s']").val(),
        supply_goods_0:supply_goods_0,
        supply_goods_1:supply_goods_1,
        supply_goods_2:supply_goods_2,
        supply_goods_3:supply_goods_3,
    }
    if(value.is_purchase == 2 && value.do_phone != '' ){
        $.ajax({
            type: "POST",
            url: "/Home/Charity/add_charity",
            data:value,
            dataType: "json",
            success: function (data) {
              if (data.status == 1) {
                  $('.wrap').removeClass('hidden');
                  $('.successBox').removeClass('hidden');
                  // layer.msg(data.msg, {icon: 1});
                  // location.href = "javascript:history.back(-1);";
              }else{
                  alert('提交失败，请联系管理员！');
              }
            },
            error: function () {
                  alert("服务器繁忙, 请联系管理员!");
            },
        });
    }
    var gonghuo_tel = $('#gonghuo_tel').val().trim();
    if (gonghuo_tel == "") {
        $('#submitGonghuo .error').html('请输入联系方式');
    // } else {
    //     $('.wrap').removeClass('hidden');
    //     $('.successBox').removeClass('hidden');
    }
});
// 求助物资的下面数量的显示与隐藏
$('input[name^="needhelp"]').click(function() {
    if ($(this).attr('checked')) {
        $(this).nextAll('.select_num').addClass('hidden');
        $(this).attr('checked', false);
    } else {
        $(this).nextAll('.select_num').removeClass('hidden');
        $(this).attr('checked', true);
    }
});

$('#submitQiuzhu').click(function() {
    var value={
        is_purchase:$("input[name='is_purchase']").val(),
        do_name_s:$("input[name='do_name_s']").val(),
        do_phone:$("input[name='do_phone_ss']").val(),
        do_company:$("input[name='do_company_ss']").val(),
        do_address_s:$("input[name='do_address_s']").val(),
        comment_s:$("#comment_s").val(),
        materials_s:$("input[name='materials_s']").val(),
    }
    console.log(value);
    if (value.materials_s != '' && value.do_company != '' && value.is_purchase != '' && value.do_name_s != '' && value.do_phone != '') {
        $.ajax({
            type: "POST",
            url: "/Home/Charity/add_charity",
            data:value,
            dataType: "json",
            success: function (data) {
                console.log(data);
                console.log(data.status);
              if (data.status == 1) {
                  $('.wrap').removeClass('hidden');
                  $('.successBox').removeClass('hidden');
                  // layer.msg(data.msg, {icon: 1});
                  // location.href = "javascript:history.back(-1);";
              }else{
                  alert('提交失败，请联系管理员！');
              }
            },
            error: function () {
                  alert("服务器繁忙, 请联系管理员!");
            },
        });
    }
    
    var qiuzhu_name = $('#qiuzhu_name').val().trim();
    var qiuzhu_username = $('#qiuzhu_username').val().trim();
    var qiuzhu_tel = $('#qiuzhu_tel').val().trim();
    var checked_recipient = [];
    $('input[name^="needhelp"]:checked').each(v => {
        checked_recipient.push(v);
    });
    if (qiuzhu_name == '') {
        $('#submitQiuzhu .error').html('请输入单位名称');
    } else if (qiuzhu_username == "") {
        $('#submitQiuzhu .error').html('请输入联系人姓名');
    } else if (qiuzhu_tel == "") {
        $('#submitQiuzhu .error').html('请输入联系方式');
    } else if (checked_recipient.length == 0) {
        $('#submitQiuzhu .error').html('请输入捐赠/自用物资');
    // } else {
    //     $('.wrap').removeClass('hidden');
    //     $('.successBox').removeClass('hidden');
    }
    console.log(checked_recipient);

});
// 新增物资
$('.wz_add_box').click(function() {
    $('.xinzeng ').append($('.form_three.hidden ').clone());
    $('.form_three.hidden').eq(0).removeClass('hidden');
});
// 存储物资

cuncu();
cuncu2();
$('input[name^="recipient"]').change(function() {
    cuncu();
});
$('input[name^="needhelp"]').change(function() {
    cuncu2();
});

function cuncu() {
    var selected_one = [];
    $('input[name^="recipient"]:checked').each(function() {
        var obj = {};
        var a = $(this).val();
        var $b = $(this).nextAll('.select_num');
        var b = $b.find('input[name^="selectnum"]:checked').val();
        obj[a] = b;
        selected_one.push(obj);
    });
    var result = JSON.stringify(selected_one);
    $("input[name='materials']").val(result);
}

function cuncu2() {
    var selected_two = [];
    $('input[name^="needhelp"]:checked').each(function() {
        var obj = {};
        var a = $(this).val();
        var $b = $(this).nextAll('.select_num');
        var b = $b.find('input[name^="selectnum"]:checked').val();
        console.log(b);
        obj[a] = b;
        selected_two.push(obj);
    });
    var result = JSON.stringify(selected_two);
    $("input[name='materials_s']").val(result);
}

});