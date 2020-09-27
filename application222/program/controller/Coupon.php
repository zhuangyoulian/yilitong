<?php
/**
 * Created by PhpStorm.
 * User: jiayi
 * Date: 2019/3/4
 * Time: 17:51
 */
namespace ylt\program\controller;
use ylt\home\logic\UsersLogic;
use ylt\mobile\logic\GoodsLogic;
use ylt\home\logic\CartLogic;
use think\Page;
use think\Request;
use think\Verify;
use think\Db;
use think\Url;
use think\Cache;

class Coupon extends ProgramBase{

    public $user_id = 0;
    public $user = array();
    public $cartLogic; // 购物车逻辑操作类

    
    /**
     * 析构流函数
     */
    public function  __construct() {
        parent::__construct();
        $this->cartLogic = new \ylt\home\logic\CartLogic();
        if (I('user_id')) {
            $user = session('user');
            $user = Db::name('users')->where("user_id",I('user_id'))->find();
            session('user', $user);  //覆盖session 中的 user
            $this->user = $user;
            $this->user_id = $user['user_id'];
        }else{
            exit(json_encode(array('status' => -100, 'msg' => '请先登录！')));
        }
    }

    //首页优惠券列表--一礼通单品优惠券列表及领取
    public function coupon_list(){
        $time = time();
        $coupon=Db::name('coupon')->where('is_display',1)->where('send_start_time','<=',$time)->where('send_num != 0')->order('id desc')->select();
        foreach ($coupon as $key => $value) {
            if ($value['renewaltime']!=0) {   //判断是否自动续期/进行时间修改 
                if ($value['use_end_time'] < $time ) {
                    $link['use_start_time']=$value['use_start_time']+$value['renewaltime']*86400;
                    $link['use_end_time']=$value['use_end_time']+$value['renewaltime']*86400;
                    $link['send_end_time']=$value['send_end_time']+$value['renewaltime']*86400;
                    $link['send_start_time']=$value['send_start_time']+$value['renewaltime']*86400;
                    Db::name('coupon')->where("use_end_time" , '<' ,$time)->where('id',$value['id'])->update($link);
                }
            }
            //下架商品自动隐藏优惠卷类型
            $yes = DB::name('goods')->where("is_on_sale = 0")->where(['goods_id'=>$value['goods_id']])->find();
            if ($yes) {
                Db::name('coupon')->where(['goods_id'=>$value['goods_id']])->update(['is_display'=>0]);
            }
            $num=$value['createnumssss']-($value['ling_num']+$value['shenum']);
            @$value['ling_num']=round($num/$value['createnumssss']*100,2)."％";
            if (count(explode(',',$value['goods_id'])) ==1 and $value['type']==3 and $value['is_display']==1) {
                $goods=Db::name('goods')
                    ->alias('g')
                    ->join('coupon c','g.goods_id=c.goods_id')
                    ->where('g.goods_id',$value['goods_id'])
                    ->cache(true,YLT_CACHE_TIME)
                    ->field('g.goods_id,g.goods_name,g.goods_thumb,c.money,c.condition,c.ling_num,c.createnum,c.id,c.use_end_time,c.use_start_time')
                    ->select();
                for ($i=0; $i < count($goods); $i++) {
                $goods[$i]['ling_num']=$value['ling_num'];
                if ($goods[$i]['use_end_time'] < $time OR $goods[$i]['use_start_time'] > $time) {
                    $goods[$i]['use_time_type'] = 2; //不在活动时间内
                }
                $goods_s[]=$goods[$i]; 
                }
            }
        }
        if (IS_POST) {
            $data['uid'] = $this->user_id;
            // $id=I('post.id');
            $cid=I('post.cid');
            foreach ($goods_s as $ke => $val) {
                if ($val['id'] == $cid) {
                $data['use_end_time'] = $val['use_end_time'];
                $data['send_time'] = $val['use_start_time'];
                    if ($val['use_end_time'] < $time OR $val['use_start_time'] > $time) {
                        exit(json_encode(array('status'=>-1,'msg'=>'不在活动时间内')));
                    }
                }
            }
            $coupon = Db::name('coupon')->where('id',$cid)->field('limitget')->find();     //查询限领数量
            $query  = Db::name('coupon_list')->where('uid=0')->where('cid',$cid)->find();    //查询是否还有优惠卷
            $repeat = Db::name('coupon_list')->where(['uid' => $data['uid'],'cid' => $cid,'use_time'=>0])->field('uid,use_end_time')->select();  //查询当前账号是否已领取
            if ($query) {
                if (count($repeat)>=$coupon['limitget']) {
                    foreach ($repeat as $key => $value) {
                        if($value['use_end_time'] < $time){
                            Db::name('coupon_list')->where('cid ='.$cid)->where('uid',$data['uid'])->limit(1)->update(['use_end_time' => $data['use_end_time']]);
                            exit(json_encode(array('status'=>1,'msg'=>'领券成功！正在进入产品中心!')));
                        }else{
                            exit(json_encode(array('status'=>-1,'msg'=>'请勿重复领券!')));
                        }
                    }
                }else{
                    Db::name('coupon_list')->where('cid ='.$cid)->where('uid',0)->limit(1)->update($data);
                    $ling=Db::name('coupon_list')->where('cid ='.$cid)->where('uid!=0')->select();
                    $lin['ling_num']=count($ling);
                    Db::name('coupon')->where('id ='.$cid)->limit(1)->update($lin);
                    exit(json_encode(array('status'=>1,'msg'=>'领券成功！正在进入产品中心')));
                }
            }else{
                exit(json_encode(array('status'=>-1,'msg'=>'优惠卷已派完')));
            }
        }
        exit(json_encode(array('status'=>1,'msg'=>'连接成功！','goods_s'=>$goods_s)));
    }

}
