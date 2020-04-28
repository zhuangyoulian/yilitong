<?php

namespace ylt\program\Controller;

use think\Controller;

class Pay
{


//微信支付
    public function getpay()
    {

        //限购时查询用户已参加的拼单商品数量，限制购买总数量
        $share_goods = db('order_goods')->where(['order_id'=>I("post.order_id")])->find();
        $array = db('discount_buy')->alias('b')->join('discount_goods g','b.id = g.discount_id')->where(['goods_id'=>$share_goods['goods_id'],'b.buy_type'=>7])->find();
        if ($array) {
            $share_goods_num = db('order')->alias('o')->join('order_goods g','o.order_id = g.order_id')->where('o.is_share > 0')->where(['o.user_id'=>I("post.user_id"),'pay_status'=>1,'g.goods_id'=>$share_goods['goods_id']])->field('o.order_id,g.goods_num')->sum('goods_num');
            if (($share_goods_num+$share_goods['goods_num'] ) > $array['buy_type_purchase_num_s']) {
                $num_s = $array['buy_type_purchase_num_s']-$share_goods_num;
                exit(json_encode(array('status'=>-3,'msg'=>'该商品达购买上限，剩余可购买'.$num_s.'件','order_id'=>I("post.order_id")))); // 返回结果状态
            }
        }
        
        
        //获取openid
        if (I("post.code")&&I("post.user_id")&&I("post.order_id")) {   //用code获取openid
            $code = I("post.code");
            $appid = 'wxbfb485ef166f598d';//appid.如果是公众号 就是公众号的appid
            $WX_SECRET = '5b959ca1e8235ae5f0338fdf1d4d5b61';//AppSecret
            $url = "https://api.weixin.qq.com/sns/jscode2session?appid=" . $appid . "&secret=" . $WX_SECRET . "&js_code=" . $code . "&grant_type=authorization_code";
//       $infos = json_decode(file_get_contents($url));
            $infos = $this->GetOpenidFromMp($url);
            $openid = $infos['openid'];
        }else{
            exit(json_encode(array('status'=>-1,'msg'=>'参数错误')));
        }

        //$appid = 'wxbfb485ef166f598d';//appid.如果是公众号 就是公众号的appid
        $mch_id = '1473157102';//商户号 1473157102   1376794402

        $where = array();
        $where['order_id'] = I("post.order_id");
        $where['user_id'] = I("post.user_id");
        $orderlist = db('order')->field("order_id,order_status,order_sn,order_amount,total_amount,supplier_id,add_time")->where($where)->find();
//        // print_json(1,$where,$orderlist);
        if (!$orderlist['order_amount']) {
            exit(json_encode(array('status'=>-1,'msg'=>'订单不存在')));

        }
        $fee = $orderlist['order_amount'];//订单总金额
       // $fee = 0.01;//举例支付0.01
        $body = '订单支付';
        $nonce_str = $this->nonce_str();//随机字符串
        // print_json(0, "请求成功111", $nonce_str);
        $notify_url = $_SERVER['SERVER_NAME'] . '/program/pay/weixin_notify'; //回调的url【自己填写】
        // $openid = $openid;
         $out_trade_no = $orderlist['order_sn'];//商户订单号
        //$out_trade_no = "20191025".rand(1111,9999);//商户订单号
        // $out_trade_no = $this->order_number($openid);//商户订单号

        $spbill_create_ip = $_SERVER['REMOTE_ADDR'];//服务器的ip【自己填写】;
        $total_fee = $fee * 100;// 微信支付单位是分，所以这里需要*100
        $trade_type = 'JSAPI';//交易类型 默认


//这里是按照顺序的 因为下面的签名是按照顺序 排序错误 肯定出错
        $post['appid'] = $appid;
        $post['body'] = $body;
        $post['mch_id'] = $mch_id;
        $post['nonce_str'] = $nonce_str;//随机字符串
        $post['notify_url'] = $notify_url;
        $post['openid'] = $openid;
        $post['out_trade_no'] = $out_trade_no;
        $post['spbill_create_ip'] = $spbill_create_ip;//终端的ip
        $post['total_fee'] = $total_fee;//总金额 
        $post['trade_type'] = $trade_type;
        $sign = $this->sign($post);//签名
        $post_xml = '<xml>
   <appid>' . $appid . '</appid>
   <body>' . $body . '</body>
   <mch_id>' . $mch_id . '</mch_id>
   <nonce_str>' . $nonce_str . '</nonce_str>
   <notify_url>' . $notify_url . '</notify_url>
   <openid>' . $openid . '</openid>
   <out_trade_no>' . $out_trade_no . '</out_trade_no>
   <spbill_create_ip>' . $spbill_create_ip . '</spbill_create_ip>
   <total_fee>' . $total_fee . '</total_fee>
   <trade_type>' . $trade_type . '</trade_type>
   <sign>' . $sign . '</sign>
</xml> ';

        //print_json(0, "请求成功111", $openid);
//print_r($post_xml);die;
//统一接口prepay_id
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $xml = $this->http_request($url, $post_xml);


        $array = $this->xml($xml);//全要大写



        if ($array['RETURN_CODE'] == 'SUCCESS' && $array['RESULT_CODE'] == 'SUCCESS') {
            $time = time();
            $tmp = '';//临时数组用于签名
            $tmp['appId'] = $appid;
            $tmp['nonceStr'] = $nonce_str;
            $tmp['package'] = 'prepay_id=' . $array['PREPAY_ID'];
            $tmp['signType'] = 'MD5';
            $tmp['timeStamp'] = "$time";


            $data['state'] = 200;
            $data['timeStamp'] = "$time";//时间戳
            $data['nonceStr'] = $nonce_str;//随机字符串
            $data['signType'] = 'MD5';//签名算法，暂支持 MD5
            $data['package'] = 'prepay_id=' . $array['PREPAY_ID'];//统一下单接口返回的 prepay_id 参数值，提交格式如：prepay_id=*
            $data['paySign'] = $this->sign($tmp);//签名,具体签名方案参见微信公众号支付帮助文档;
            $data['out_trade_no'] = $out_trade_no;

            exit(json_encode($data));

        } else {
            $data['state'] = 0;
            $data['text'] = "错误";
            $data['RETURN_CODE'] = $array['RETURN_CODE'];
            $data['RETURN_MSG'] = $array['RETURN_MSG'];
        }
        exit(json_encode($data));
//        dump( $data);die;
//        json_encode( $data);
    }


//随机32位字符串
    private function nonce_str()
    {
        $result = '';
        $str = 'QWERTYUIOPASDFGHJKLZXVBNMqwertyuioplkjhgfdsamnbvcxz';
        for ($i = 0; $i < 32; $i++) {
            $result .= $str[rand(0, 48)];
        }
        return $result;
    }


//生成订单号
    private function order_number($openid)
    {
//date('Ymd',time()).time().rand(10,99);//18位
        return md5($openid . time() . rand(10, 99));//32位
    }


//签名 $data要先排好顺序
    private function sign($data)
    {
        $stringA = '';
        foreach ($data as $key => $value) {
            if (!$value) continue;
            if ($stringA) $stringA .= '&' . $key . "=" . $value;
            else $stringA = $key . "=" . $value;
        }
        $wx_key = '190b6549eb6615bd40f1a03d4b9f5a6b';//申请支付后有给予一个商户账号和密码，登陆后自己设置的key
        $stringSignTemp = $stringA . '&key=' . $wx_key;
        return strtoupper(md5($stringSignTemp));
    }


    /*

    * @return openid

    */

    public function GetOpenidFromMp($url)

    {

        //通过code获取网页授权access_token 和 openid 。网页授权access_token是一次性的，而基础支持的access_token的是有时间限制的：7200s。

        //1、微信网页授权是通过OAuth2.0机制实现的，在用户授权给公众号后，公众号可以获取到一个网页授权特有的接口调用凭证（网页授权access_token），通过网页授权access_token可以进行授权后接口调用，如获取用户基本信息；

        //2、其他微信接口，需要通过基础支持中的“获取access_token”接口来获取到的普通access_token调用。

        // $url = $this->__CreateOauthUrlForOpenid($code);

        $ch = curl_init();//初始化curl

        curl_setopt($ch, CURLOPT_TIMEOUT, 300);//设置超时

        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);

        curl_setopt($ch, CURLOPT_HEADER, FALSE);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        $res = curl_exec($ch);//运行curl，结果以jason形式返回

        $data = json_decode($res,true);

        curl_close($ch);

        return $data;

    }



//curl请求
    public function http_request($url, $data = null, $headers = array())
    {
        $curl = curl_init();
        if (count($headers) >= 1) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($curl, CURLOPT_URL, $url);


        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);


        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }


//获取xml
    private function xml($xml)
    {
        $p = xml_parser_create();
        xml_parse_into_struct($p, $xml, $vals, $index);
        xml_parser_free($p);
        $data = "";
        foreach ($index as $key => $value) {
            if ($key == 'xml' || $key == 'XML') continue;
            $tag = $vals[$value[0]]['tag'];
            $value = $vals[$value[0]]['value'];
            $data[$tag] = $value;
        }
        return $data;
    }


    /**
     * 微信支付成功异步接口
     */
    public function weixin_notify()
    {

        require(ROOT_PATH . 'newapp/includes/weixin/example/log.php');
        //初始化日志
        $logHandler = new \CLogFileHandler('pay-' . date("Y-m-d") . '.log');
        \Log::Init($logHandler, 15);
        \Log::DEBUG("测试数据111:weixin_notify" );
//        dump(11);die;

        //$sucess_xml='<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
        //$fail_xml="<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[FAIL]]></return_msg></xml>";

        $sucess_xml = "SUCCESS";
        $fail_xml = "FAIL";

        $arr = array();
        $postStr = file_get_contents('php://input');
        $arr = (array)simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        \Log::DEBUG("测试数据112:".json_encode($arr) );
        if (!empty($arr)) {
            //微信支付日志记录
            $word = var_export($arr, true);
            $fp = fopen(ROOT_PATH . "newapp/temp/paylog.txt", "a");
            flock($fp, LOCK_EX);
            fwrite($fp, "微信支付执行日期：" . strftime("%Y%m%d%H%M%S", time()) . "\n" . $word . "\n");
            flock($fp, LOCK_UN);
            fclose($fp);
        }
         \Log::DEBUG($arr['total_fee']."测试数据1888:".$arr['out_trade_no'] );
//        echo $sucess_xml;
//        exit;
        if (!empty($arr)) {
            //返回成功
            \Log::DEBUG("测试数据115:" );
            if (!empty($arr['out_trade_no']) && !empty($arr['total_fee'])) {
//                \Log::DEBUG($arr['total_fee']."测试数据1112:".$arr['out_trade_no'] );
                $order = db('order')->where(array('order_sn' => $arr['out_trade_no']))->find();
//                \Log::DEBUG($arr['order_status']."测试数据1116:".$order['order_id'] );
                if ($order['order_status'] == '1') {
                    echo $sucess_xml;
                    exit;
                }

                $order_sn = trim($arr['out_trade_no']);
                $money = $arr['total_fee'] / 100;
                //订单查询
                $order = db('order')->where("order_sn", $order_sn)->find();

                \Log::DEBUG("测试数据113:".$order );
                $pos = strpos($order_sn, 'us');
                if ($pos === false) {

                } else {
                    $order = array();
                    $order['order_amount'] = $money;
                    $order['order_status'] = '0';
                    $order['is_parent'] = '0';
                }
                \Log::DEBUG("测试数据1155:" );
                if ($order['order_status'] == '0' && $money == $order['order_amount'] && !empty($order)) {


                    $updata['pay_code'] = 'weixin';
                    $updata['pay_name'] = '微信支付';
                    $updata['transaction_id'] = $arr['transaction_id'];
                    \Log::DEBUG("测试数据11444:".json_encode($updata) );
                    $flag = db('order')->where("order_sn='" . $arr['out_trade_no'] . "'")->update($updata);
                    $row = update_pay_status($arr['out_trade_no'], $updata);

                    if ($flag || $row) {
                        echo $sucess_xml;
                        exit;
                    } else {
                        echo $fail_xml;
                        exit;
                    }
                } else {
                    echo $fail_xml;
                    exit;
                }
            } else {
                echo $fail_xml;
                exit;
            }
        }
   \Log::DEBUG("测试数据6666:" );

    }


    /**
     * Curl版本
     * 使用方法：
     * $post_string = "app=request&version=beta";
     * request_by_curl('//www.jb51.net/restServer.php', $post_string);
     */
    function request_by_curl($remote_server, $post_string)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $remote_server);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'mypost=' . $post_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "panmili.com's CURL Example beta");
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
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


}

