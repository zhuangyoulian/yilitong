$(function() {
    var inde1 = 0;
    var inde2 = 1;
    var inde3 = 2;
    $('.select_li li').click(function() {
        $(this).addClass('active').siblings().removeClass('active');
        var c = $(this).parent().parent().parent().find('.select_li li');
        var i = c.index(this);

        var paren = $(this).parent().parent().find('.three_productlist li');
        paren.eq(i).removeClass('hidden').siblings().addClass('hidden');
    });
    setLunbo1();
    setLunbo2();
    setLunbo3();

    function setLunbo1() {
        var i = inde1;
        $('.section1 .select_li li').eq(i).addClass('active').siblings().removeClass('active');
        $('.section1 .three_productlist li').eq(i).removeClass('hidden').siblings().addClass('hidden');
        setTimeout(function() {
            i++;
            if (i > 2) {
                i = 0
            }
            inde1 = i;
            setLunbo1();
        }, 4000);
    }

    function setLunbo2() {
        var i = inde2;
        $('.section2 .select_li li').eq(i).addClass('active').siblings().removeClass('active');
        $('.section2 .three_productlist li').eq(i).removeClass('hidden').siblings().addClass('hidden');
        setTimeout(function() {
            i++;
            if (i > 2) {
                i = 0
            }
            inde2 = i;
            setLunbo2();
        }, 4000);
    }

    function setLunbo3() {
        var i = inde3;
        $('.section3 .select_li li').eq(i).addClass('active').siblings().removeClass('active');
        $('.section3 .three_productlist li').eq(i).removeClass('hidden').siblings().addClass('hidden');
        setTimeout(function() {
            i++;
            if (i > 2) {
                i = 0
            }
            inde3 = i;
            setLunbo3();
        }, 4000);
    }
});