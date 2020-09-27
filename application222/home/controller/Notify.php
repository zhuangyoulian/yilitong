<?php
/**
 * Created by PhpStorm.
 * User: lijiayi
 * Date: 2017/3/27
 * Time: 10:30
 */
namespace ylt\home\controller; 
use think\Db;
use think\Url;

class Notify{
    public function APItype(){
        $appKey         =   'YLTHC1TESTKEY001';         //第三方提供
        $channelId      =   'YLTHC1';                   //第三方提供
        $channelId_s    = $_GET['channelId'];
        $appKey_s       = $_GET['appKey'];
        $orderId        = $_GET['orderId'];
        $retcode        = $_GET['retcode'];
        $msg            = $_GET['msg'];
        $sign           = $_GET['sign'];
        $add_time       = time();
        if ($appKey==$appKey_s) {
            if ($channelId==$channelId_s) {
                //修改订单状态
                Db::name('order')->where('order_id',$orderId)->update(['shipping_status'=>1,'order_status'=>4]);
                //增加充值记录
                Db::name('top_cart_type')->insert(['type'=>1,'msg'=>'充值发货成功','add_time'=>$add_time,'retcode'=>$retcode,'order_id'=>$orderId]);
            }else{
                Db::name('top_cart_type')->insert(['type'=>-222,'msg'=>'返回channelId不匹配','order_id'=>$orderId]);
            }
        }else{
            Db::name('top_cart_type')->insert(['type'=>-111,'msg'=>'返回appKey不匹配','order_id'=>$orderId]);
        }
        echo "success";
    }

    /**
     * [inquire_lottery 查询礼至家居过来的中奖记录]
     * @return [type] [description]
     */
    public function inquire_lottery($unionid){
        if (!$unionid and I('unionid')) {
            $unionid = I('unionid');
        }
        if (!$unionid) {
            return array('result'=>'-1','info'=>"unionid参数不可为空");
        }
        $data['uid'] = Db::name('users')->where('unionid',$unionid)->value('user_id');
        $type = $this->get_urls("http://www.szleezen.cn/Mobile/Goods/inquire_lottery?unionid=$unionid");
        $type = json_decode($type,true);
        if ($type) {
          foreach ($type as $key => $value) {
            switch ($value['code']) {
                case '1':
                $dsf =Db::name('coupon_list')->where('uid',$data['uid'])->where("cid = 61")->find();
                if (!$dsf) {
                   $id = 61;
                }
                    break;

                case '2':
                $dsf =Db::name('coupon_list')->where('uid',$data['uid'])->where("cid = 62")->find();
                if (!$dsf) {
                   $id = 62;
                }
                    break;
                
                case '3':
                $dsf =Db::name('coupon_list')->where('uid',$data['uid'])->where("cid = 63")->find();
                if (!$dsf) {
                   $id = 63;
                }
                    break;
                
                case '4':
                $dsf =Db::name('coupon_list')->where('uid',$data['uid'])->where("cid = 64")->find();
                if (!$dsf) {
                   $id = 64;
                }
                    break;
                
                case '5':
                $dsf =Db::name('coupon_list')->where('uid',$data['uid'])->where("cid = 65")->find();
                if (!$dsf) {
                   $id = 65;
                }
                    break;
                
                case '6':
                $dsf =Db::name('coupon_list')->where('uid',$data['uid'])->where("cid = 66")->find();
                if (!$dsf) {
                   $id = 66;
                }
                    break;
                
                default:
                    break;
            }
            if ($id) {
                $couponss=Db::name('coupon')->where('id',$id)->find();
                if ($couponss['use_end_time']>$value['add_time']+7*24*60*60) {
                    $data['use_end_time'] = $value['add_time']+7*24*60*60;
                }else{
                    $data['use_end_time'] = $couponss['use_end_time'];
                }
                if ($couponss['use_start_time']<$value['add_time']) {
                    $data['send_time']  = $value['add_time'];
                }else{
                    $data['send_time']  = $couponss['use_start_time'];
                }
                $data['source']     = 1;
                $update = Db::name('coupon_list')->where('cid ='.$id)->where('uid',0)->limit(1)->order("id desc")->update($data);
                if ($update) {
                    return "同步成功";
                }else{
                    return "同步失败";
                }
            }
          }
        }
        return "没有可同步的数据";
    }

    /**
     *  通过URL获取页面信息
     * @param $url  地址
     * @return mixed  返回页面信息
     */
    public function get_urls($url)
    {   
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);  //设置访问的url地址
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//不输出内容
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
  
     /**
     * [lzjj_coupon 礼至家居查询可使用优惠券接口]
     * @return [type] [description]
     */
      public function lzjj_coupon(){
          //$now_time = time();
          $now_time = 1;
          $unionid    =  I('unionid');      //中奖unionid ormqRwZDTUlKMSA8VvXqH8TWYJms
          $user_id = Db::name('users')->where('unionid',$unionid)->value('user_id');
          $id = Db::name('coupon_list')
              ->where("uid=$user_id and use_time=0")
              ->field('cid')
              ->select();
          $count = 0;
          if(!empty($id)){
              $cids = array_column($id, 'cid');
              $unsed_l = Db::name('coupon')
                  ->alias('c')
                  ->join('coupon_list l','l.cid=c.id')
                  ->join('goods g','g.goods_id=c.goods_id')
                  ->where('c.id','in',$cids)
                  ->where("l.use_end_time>$now_time")
                  ->where("l.uid",$user_id)
                  ->where("l.source",1)
                  ->field('g.goods_id,g.goods_name,c.id,c.goods_id,c.money,l.use_end_time,l.send_time,l.code,c.coupon_type,c.condition,l.use_time')
                  ->select();
              $count = count($unsed_l);
          }
              exit(json_encode(array('result'=>'1','info'=>$unsed_l,'count'=>$count)));
      }

      /**
       * [lzjj_coupon_chou 礼至家居查询抽奖盘优惠券接口]
       * @return [type] [description]
       */
      public function lzjj_coupon_chou(){
        $where="id=61 or id=62 or id=63 or id=64 or id=65 or id=66";
        // $where="id=50 or id=51 or id=52 or id=53 or id=54 or id=58";
        $coupon = Db::name('coupon')->where($where)->select();
        exit(json_encode(array('result'=>'1','coupon'=>$coupon)));
      }
  }