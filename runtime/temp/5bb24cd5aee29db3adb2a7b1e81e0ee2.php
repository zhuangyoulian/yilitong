<?php if (!defined('THINK_PATH')) exit(); /*a:1:{s:58:"./application/admin/view/rite_home\inquire_ajax_index.html";i:1577944112;}*/ ?>
<table>
  <tbody>
  <?php if(empty($orderList) == true): ?>
    <tr data-id="0">
          <td class="no-data" align="center" axis="col0" colspan="50">
            <i class="fa fa-exclamation-circle"></i>没有符合条件的记录
          </td>
       </tr>
  <?php else: if(is_array($orderList) || $orderList instanceof \think\Collection): $i = 0; $__LIST__ = $orderList;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$list): $mod = ($i % 2 );++$i;?>
    <tr>
        <td class="sign" axis="col0">
          <div style="width: 24px;"><i class="ico-check"></i></div>
        </td>
        <td align="left" abbr="order_sn" axis="col3" class="">
          <div style="text-align: left; width: 160px;" class=""><?php echo $list['order_id']; ?> / <?php echo $list['order_sn']; ?></div>
        </td>
        <td align="left" abbr="consignee" axis="col4" class="">
          <div style="text-align: left; width: 150px;" class=""><?php echo $list['consignee']; ?>:<?php echo (isset($list['mobile']) && ($list['mobile'] !== '')?$list['mobile']:$list['phone']); ?></div>
        </td>
        <td align="center" abbr="article_show" axis="col5" class="">
          <div style="text-align: center; width: 60px;" class=""><?php echo $list['total_amount']; ?></div>
        </td>
        <td align="center" abbr="article_time" axis="col6" class="">
          <div style="text-align: center; width: 60px;" class=""><?php echo $list['order_amount']; ?></div>
        </td>
    
        <td align="center" abbr="article_time" axis="col6" class="">
      <?php if(($list['order_status'] == 3) or ($list['order_status'] == 5)): ?> <div style="text-align: center; width: 80px;" class="">  
      <?php else: ?> <div style="text-align: center; width: 50px;" class=""> 
    <?php endif; ?>
    <?php echo $order_status[$list[order_status]]; if($list['is_cod'] == '1'): ?><span style="color: red">(货到付款)</span><?php endif; ?></div>
        </td>
    
        <td align="center" abbr="article_time" axis="col6" class="">
          <div style="text-align: center; width: 60px;" class=""><?php echo $pay_status[$list[pay_status]]; ?></div>
        </td>
        <td align="center" abbr="article_time" axis="col6" class="">
          <div style="text-align: center; width: 60px;" class=""><?php echo $shipping_status[$list[shipping_status]]; ?></div>
        </td>
        <?php if($list['code']): ?>
          <td align="center" abbr="article_time" axis="col6" class="">
            <div style="text-align: center; width: 100px;" class="">充值券：<?php echo $list['code']; ?></div>
          </td>
        <?php else: ?>
          <td align="center" abbr="article_time" axis="col6" class="">
            <div style="text-align: center; width: 100px;" class=""><?php echo (isset($list['pay_name']) && ($list['pay_name'] !== '')?$list['pay_name']:'其他方式'); ?></div>
          </td>
        <?php endif; if($list['phone']): ?>
          <td align="center" abbr="article_time" axis="col6" class="">
            <div style="text-align: center; width: 60px;" class="">线上到账</div>
          </td>
        <?php else: ?>
          <td align="center" abbr="article_time" axis="col6" class="">
            <div style="text-align: center; width: 60px;" class=""><?php echo $list['shipping_name']; ?></div>
          </td>
        <?php endif; ?>
        <td align="center" abbr="article_time" axis="col6" class="">
          <div style="text-align: center; width: 60px;" class=""><?php echo $list['source']; ?></div>
        </td>
        <td align="center" abbr="article_time" axis="col6" class="">
          <div style="text-align: center; width: 120px;" class=""><?php echo date('Y-m-d H:i',$list['add_time']); ?></div>
        </td>
        <td align="center" abbr="article_time" axis="col6" class="">
          <div style="text-align: center; width: 140px;" class=""><?php if($list['confirm_time'] != 0): ?><?php echo date('Y-m-d H:i',$list['confirm_time']); else: ?>N<?php endif; ?></div>
        </td>
        <td align="center" abbr="article_time" axis="col6" class="">
          <div style="text-align: center; width: 80px;" class=""><?php echo $list[items_source]; ?></div>
        </td>
        <td align="left" axis="col1" class="handle" align="center">
            <div style="text-align: left; ">
              <a class="btn green" href="<?php echo Url('Admin/RiteHome/detail',array('order_id'=>$list['order_id'])); ?>"><i class="fa fa-list-alt"></i>查看</a>
            <?php if($act_list != 1): if(($list['order_status'] == 3) or ($list['order_status'] == 5)): ?>
                <a class="btn red" onclick="del(<?php echo $list['order_id']; ?>)"><i class="fa fa-trash-o"></i>删除</a>
              <?php endif; endif; ?>
              <!-- <?php if(($list['order_status'] == 4) and ($list['close'] == 0)): ?>
                <a class="btn red" href="<?php echo Url('Admin/order/close_order',array('order_id'=>$list['order_id'])); ?>" onclick="close(this)" style="color: red"><i class="fa fa-jpy"></i>可结算</a>
              <?php endif; if($list['close'] == 1): ?>
                <a class="btn"><i class="fa fa-jpy"></i>已结算</a>
              <?php endif; ?> -->
            </div>
         </td>
         <td align="" class="" style="width: 100%;">
            <div>&nbsp;</div>
          </td>
      </tr>
      <?php endforeach; endif; else: echo "" ;endif; endif; ?>
    </tbody>
</table>
<div class="row">
    <div class="col-sm-6 text-left"></div>
    <div class="col-sm-6 text-right"><?php echo $page; ?></div>
</div>
<script>
    $(".pagination  a").click(function(){
        var page = $(this).data('p');
        console.log(page);
        ajax_get_table('search-form2',page);
    });
    
    $('.ftitle>h5').empty().html("(共<?php echo $pager->totalRows; ?>条记录)");
    $('.money>h5').empty().html("当前列表应付/已付总额 : <?php echo $money; ?> 元");
</script>