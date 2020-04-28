<?php
/**
 * Created by PhpStorm.
 * User: jiayi
 * Date: 2017/3/21
 * Time: 19:51
 */
 use think\Db;
 use think\Cache;
 use ylt\home\logic\UsersLogic;
/**
 * 检验登陆
 * @param
 * @return bool
 */
function is_login(){
    if(isset($_SESSION['admin_id']) && $_SESSION['admin_id'] > 0){
        return $_SESSION['admin_id'];
    }else{
        return false;
    }
}
/**
 * 获取用户信息
 * @param $user_id_or_name  用户id 邮箱      第三方id
 * @param int $type  类型 0 user_id查找 1 邮箱查找 2 手机查找 3 第三方唯一标识查找
 * @param string $oauth  第三方来源
 * @return mixed
 */
function get_user_info($user_id_or_name,$type = 0,$oauth=''){
    $map = array();
    if($type == 0)
        $map['user_id'] = $user_id_or_name;
    if($type == 1)
        $map['email'] = $user_id_or_name;
    if($type == 2)
        $map['mobile'] = $user_id_or_name;
    if($type == 3){
        $map['openid'] = $user_id_or_name;
        //$map['oauth'] = $oauth;
    }
    if($type == 4){
    	$map['unionid'] = $user_id_or_name;
    	//$map['oauth'] = $oauth;
    }
    $user = Db::name('users')->where($map)->find();
    return $user;
}

/**
 * 把返回的数据集转换成Tree
 * @param array $items 要转换的数据集
 * @param string $id 自增字段（栏目id）
 * @param string $pid parent标记字段
 * @return array
 */
function makeTree($items, $id="id", $pid="pid", $son = 'children')
{
    $tree = array();
    $tmpMap = array();

    foreach ($items as $item) {
        $tmpMap[$item[$id]] = $item;
    }

    foreach ($items as $item) {
        if (isset($tmpMap[$item[$pid]])) {
            $tmpMap[$item[$pid]][$son][] = &$tmpMap[$item[$id]];
        } else {
            $tree[] = &$tmpMap[$item[$id]];
        }
    }
    return $tree;
}



/**
 * 获取商家用户信息
 * @param $user_id_or_name  用户id 邮箱 手机 第三方id
 * @param int $type  类型 0 user_id查找 1 邮箱查找 2 手机查找 3 第三方唯一标识查找
 * @param string $oauth  第三方来源
 * @return mixed
 */
function get_supplier_user_info($user_id_or_name,$type = 0,$oauth=''){
    $map = array();
    if($type == 0)
        $map['user_id'] = $user_id_or_name;
    if($type == 1)
        $map['email'] = $user_id_or_name;
    if($type == 2)
        $map['mobile'] = $user_id_or_name;

    $user = Db::name('supplier_user')->where($map)->find();
    return $user;
}

/**
 * 更新会员等级,折扣，消费总额
 * @param $user_id  用户ID
 * @return boolean
 */
function update_user_level($user_id){
    $level_info = Db::name('user_rank')->order('level_id')->select();
    $total_amount = Db::name('order')->where("user_id=:user_id AND pay_status=1 and order_status not in (3,5)")->bind(['user_id'=>$user_id])->sum('order_amount');
    if($level_info){
    	foreach($level_info as $k=>$v){
    		if($total_amount >= $v['amount']){
    			$level = $level_info[$k]['level_id'];
    			$discount = $level_info[$k]['discount']/100;
    		}
    	}
    	$user = session('user');
    	$updata['total_amount'] = $total_amount;//更新累计修复额度
    	//累计额度达到新等级，更新会员折扣
    	if(isset($level) && $level>$user['level']){
    		$updata['level'] = $level;
    		$updata['discount'] = $discount;	
    	}
        Db::name('users')->where("user_id", $user_id)->update($updata);
    }
}

/**
 *  商品缩略图 给于标签调用 拿出商品表的 original_img 原始图来裁切出来的
 * @param type $goods_id  商品id
 * @param type $width     生成缩略图的宽度
 * @param type $height    生成缩略图的高度
 */
function goods_thum_images($goods_id,$width,$height){

     if(empty($goods_id))
		 return '';
    //判断缩略图是否存在
    $path = "public/upload/goods/thumb/$goods_id/";
    $goods_thumb_name ="goods_thumb_{$goods_id}_{$width}_{$height}";

    //生成缩略图
    if(!is_dir($path)){
        mkdir($path,0777,true);
    }
    //chmod($path, 0777);


    // 这个商品 已经生成过这个比例的图片就直接返回了
    if(file_exists($path.$goods_thumb_name.'.jpg'))  return '/'.$path.$goods_thumb_name.'.jpg'; 
    if(file_exists($path.$goods_thumb_name.'.jpeg')) return '/'.$path.$goods_thumb_name.'.jpeg'; 
    if(file_exists($path.$goods_thumb_name.'.gif'))  return '/'.$path.$goods_thumb_name.'.gif'; 
    if(file_exists($path.$goods_thumb_name.'.png'))  return '/'.$path.$goods_thumb_name.'.png'; 
        
    $original_img =  Db::name('Goods')->where("goods_id", $goods_id)->value('original_img');
    if(empty($original_img)) return '';
    
    $original_img = '.'.$original_img; // 相对路径
    if(!file_exists($original_img)) return '';

    //$image = new \think\Image();
    $image = \think\Image::open($original_img);
        
    $goods_thumb_name = $goods_thumb_name. '.'.$image->type();
    if($width == 200){
        $goods_thumb = '/'.$path.$goods_thumb_name;
        Db::name('goods')->where('goods_id',$goods_id)->update(['goods_thumb'=>$goods_thumb]);
    }
    
    //参考文章 http://www.mb5u.com/biancheng/php/php_84533.html  改动参考 http://www.thinkphp.cn/topic/13542.html
    $image->thumb($width, $height,2)->save($path.$goods_thumb_name,NULL,100); //按照原图的比例生成一个最大为$width*$height的缩略图并保存
    

    return '/'.$path.$goods_thumb_name;
}

/**
 * 商品相册缩略图
 */
function get_sub_images($sub_img,$goods_id,$width,$height){
	//判断缩略图是否存在
	$path = "public/upload/goods/thumb/$goods_id/";
	// 生成缩略图
	if(!is_dir($path))
		mkdir($path,777,true);
	$goods_thumb_name ="goods_sub_thumb_{$sub_img['img_id']}_{$width}_{$height}";
	//这个缩略图 已经生成过这个比例的图片就直接返回了
	if(file_exists($path.$goods_thumb_name.'.jpg'))  return '/'.$path.$goods_thumb_name.'.jpg';
	if(file_exists($path.$goods_thumb_name.'.jpeg')) return '/'.$path.$goods_thumb_name.'.jpeg';
	if(file_exists($path.$goods_thumb_name.'.gif'))  return '/'.$path.$goods_thumb_name.'.gif';
	if(file_exists($path.$goods_thumb_name.'.png'))  return '/'.$path.$goods_thumb_name.'.png';
	
	$original_img = '.'.$sub_img['image_url']; //相对路径
	if(!file_exists($original_img)) return '';
	
	//$image = new \think\Image();
	//$image->open($original_img);
        $image = \think\Image::open($original_img);
	
	$goods_thumb_name = $goods_thumb_name. '.'.$image->type();
	
	$image->thumb($width, $height,2)->save($path.$goods_thumb_name,NULL,100); //按照原图的比例生成一个最大为$width*$height的缩略图并保存
	return '/'.$path.$goods_thumb_name;
}

/**
 * 刷新商品库存, 如果商品有设置规格库存, 则商品总库存 等于 所有规格库存相加
 * @param type $goods_id  商品id
 */
function refresh_stock($goods_id){
    $count =  Db::name("GoodsPrice")->where("goods_id", $goods_id)->count();
    if($count == 0) return false; // 没有使用规格方式 没必要更改总库存

    $store_count =  Db::name("GoodsPrice")->where("goods_id", $goods_id)->sum('store_count');
     Db::name("Goods")->where("goods_id", $goods_id)->update(array('store_count'=>$store_count)); // 更新商品的总库存
}
/*一创*/
function agen_refresh_stock($goods_id){
    $count =  Db::name("AgenGoodsPrice")->where("goods_id", $goods_id)->count();
    if($count == 0) return false; // 没有使用规格方式 没必要更改总库存

    $store_count =  Db::name("AgenGoodsPrice")->where("goods_id", $goods_id)->sum('store_count');
     Db::name("AgenGoods")->where("goods_id", $goods_id)->update(array('store_count'=>$store_count)); // 更新商品的总库存
}
/*红礼*/
function red_refresh_stock($goods_id){
    $count =  Db::name("RedGoodsPrice")->where("goods_id", $goods_id)->count();
    if($count == 0) return false; // 没有使用规格方式 没必要更改总库存

    $store_count =  Db::name("RedGoodsPrice")->where("goods_id", $goods_id)->sum('store_count');
     Db::name("RedGoods")->where("goods_id", $goods_id)->update(array('store_count'=>$store_count)); // 更新商品的总库存
}

/**
 * 根据 order_goods 表扣除商品库存
 * @param type $order_id  订单id
 */
function minus_stock($order_id){
    $orderGoodsArr =  Db::name('OrderGoods')->where("order_id", $order_id)->select();
    foreach($orderGoodsArr as $key => $val)
    {
        // 有选择规格的商品
        if(!empty($val['spec_key']))
        {   // 先到规格表里面扣除数量 再重新刷新一个 这件商品的总数量
            Db::name('GoodsPrice')->where("goods_id = :goods_id and `key` = :key")->bind(['goods_id'=>$val['goods_id'],'key'=>$val['spec_key']])->setDec('store_count',$val['goods_num']);
            refresh_stock($val['goods_id']);
        }else{
            Db::name('Goods')->where("goods_id", $val['goods_id'])->setDec('store_count',$val['goods_num']); // 直接扣除商品总数量
        }
        Db::name('Goods')->where("goods_id", $val['goods_id'])->setInc('sales_sum',$val['goods_num']); // 增加商品销售量
        //更新活动商品购买量
        if($val['prom_type']==1 || $val['prom_type']==2 || $val['prom_type']==5){
        	$prom = get_goods_promotion($val['goods_id']);
        	if($prom['is_end']==0){
        		$tb = $val['prom_type']==1 || $val['prom_type']==5 ? 'panic_buying' : 'group_buy';
                Db::name($tb)->where("id", $val['prom_id'])->setInc('buy_num',$val['goods_num']);
                Db::name($tb)->where("id", $val['prom_id'])->setInc('order_num');
        	}
        }
    }
}

/**
 *  入驻商家结算佣金
 * @param order 订单信息
 */
function supplier_settlement($order=array()){
    $add = array();
    $add['settlement_paytime_start'] = strtotime(date('Y-m-01 00:00:00'));
    $add['settlement_paytime_end'] = strtotime(date('Y-m-t 23:59:59'));
    $add['supplier_id'] = $order['supplier_id'];
    $add['order_money_all'] = $toatl_anmunt = $order['total_amount'];
    $add['settlement_all'] = $order_anmunt =$order['order_amount'];
    $time = strtotime(date("Y-m-d",time()));

    $update = [
        'order_money_all' => ['exp','order_money_all+'.$toatl_anmunt.''],
        'settlement_all' => ['exp','settlement_all+'.$order_anmunt.''],
    ];

    $settlement = Db::name('supplier_settlement')->where("supplier_id = '".$order['supplier_id']."' ")->order('settlement_id desc')->find();

     if($settlement && $settlement['settlement_paytime_start']  <= $time && $settlement['settlement_paytime_end'] >= $time){

         Db::name('supplier_settlement')->where('settlement_id',$settlement['settlement_id'])->update($update);

     }else{
         $settlement['settlement_id'] =  Db::name('supplier_settlement')->insertGetId($add);
     }


    Db::name('order')->where('order_id',$order['order_id'])->update(['is_distribut'=>$settlement['settlement_id']]);


}


/**
 * 邮件发送
 * @param $to    接收人
 * @param string $subject   邮件标题
 * @param string $content   邮件内容(html模板渲染后的内容)
 * @throws Exception
 * @throws phpmailerException
 */
function send_email($to,$subject='',$content=''){    
    vendor('phpmailer.PHPMailerAutoload'); ////require_once vendor/phpmailer/PHPMailerAutoload.php';
    $mail = new PHPMailer;
    $config = tpCache('smtp');
	$mail->CharSet  = 'UTF-8'; //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
    $mail->isSMTP();
    //Enable SMTP debugging
    // 0 = off (for production use)
    // 1 = client messages
    // 2 = client and server messages
    $mail->SMTPDebug = 0;
    //调试输出格式
	//$mail->Debugoutput = 'html';
    //smtp服务器
    $mail->Host = $config['smtp_server'];
    //端口 - likely to be 25, 465 or 587
    $mail->Port = $config['smtp_port'];
	
	if($mail->Port === 465) $mail->SMTPSecure = 'ssl';// 使用安全协议
    //Whether to use SMTP authentication
    $mail->SMTPAuth = true;
    //用户名
    $mail->Username = $config['smtp_user'];
    //密码
    $mail->Password = $config['smtp_pwd'];
    //Set who the message is to be sent from
    $mail->setFrom($config['smtp_user']);
    //回复地址
    //$mail->addReplyTo('replyto@example.com', 'First Last');
    //接收邮件方
    if(is_array($to)){
    	foreach ($to as $v){
    		$mail->addAddress($v);
    	}
    }else{
    	$mail->addAddress($to);
    }

    $mail->isHTML(true);// send as HTML
    //标题
    $mail->Subject = $subject;
    //HTML内容转换
    $mail->msgHTML($content);
    //Replace the plain text body with one created manually
    //$mail->AltBody = 'This is a plain-text message body';
    //添加附件
    //$mail->addAttachment('images/phpmailer_mini.png');
    //send the message, check for errors
    return $mail->send();
}

/**
 * 发送短信
 * @param $mobile  手机号码
 * @param $content  内容
 * @return bool
 */
function sendCode($mobile,$content)
{

    $config = tpCache('sms'); // 获取缓存中的短信信息
    
    $http = $config['sms_url'];
	$apikey = $config['apikey'];

	// 发送短信 v2 版本写法
	$data=array('text'=>$content,'apikey'=>$apikey,'mobile'=>$mobile);
	
	$ch = curl_init();

	// 设置验证方式 

	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept:text/plain;charset=utf-8', 'Content-Type:application/x-www-form-urlencoded','charset=utf-8'));

	// 设置返回结果为流 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	// 设置超时时间
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);

	// 设置通信方式 
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	
	$json_data = send($ch,$data,$http);
	$array = json_decode($json_data,true);
	return $array;
}

function send($ch,$data,$http){
    curl_setopt ($ch, CURLOPT_URL, $http);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    return curl_exec($ch);
}
 



/**
 * 查询快递
 * @param $postcom  快递公司编码
 * @param $getNu  快递单号
 * @return array  物流跟踪信息数组
 */
function queryExpress($postcom , $getNu) {


		$express_key =  Db::name('config')->where(array('name'=>'express_key','inc_type'=>'shipping'))->cache(true)->value('value');
		$post_data = array();
        $post_data["schema"] = 'json' ;
		$notify_url = SITE_URL.'/index.php/Home/Api/receive_shipping'; 
        //callbackurl请参考callback.php实现，key经常会变，请与快递100联系获取最新key
        $post_data["param"] = '{"company":"'.$postcom.'", "number":"'.$getNu.'","from":"", "to":"", "key":"'.$express_key.'", "parameters":{"callbackurl":"'.$notify_url.'"}}';
		
        $url='http://www.kuaidi100.com/poll';
        $o=""; 
        foreach ($post_data as $k=>$v)
        {
            $o.= "$k=".urlencode($v)."&";       //默认UTF-8编码格式
        }

        $post_data=substr($o,0,-1);

        $ch = curl_init();
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_URL,$url);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
			
			//Tell curl to write the response to a variable  

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  

			// The maximum number of seconds to allow cURL functions to execute.  

			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60); 
			
			$curl_exec = curl_exec($ch);       //返回提交结果，格式与指定的格式一致（result=true代表成功）
			
			// 关闭cURL资源，并且释放系统资源
			curl_close($ch);
    return $curl_exec;
}

/**
 * 获取某个商品分类的 下级 的 id
 * @param type $cat_id
 */
function getCatGrandson ($cat_id)
{
    $GLOBALS['catGrandson'] = array();
    $GLOBALS['category_id_arr'] = array();
    // 先把自己的id 保存起来
    $GLOBALS['catGrandson'][] = $cat_id;
    // 把整张表找出来
    $GLOBALS['category_id_arr'] =  Db::name('GoodsCategory')->cache(true,YLT_CACHE_TIME)->column('id,parent_id');
    // 先把所有下级找出来
    $son_id_arr =  Db::name('GoodsCategory')->where("parent_id", $cat_id)->cache(true,YLT_CACHE_TIME)->column('id');
    foreach($son_id_arr as $k => $v)
    {
        getCatGrandson2($v);
    }
    return $GLOBALS['catGrandson'];
}

/**
 * 获取某个场景分类的 下级 的 id
 * @param type $cat_id
 */
function getScenarioCatGrandson ($cat_id)
{
    $GLOBALS['catGrandson'] = array();
    $GLOBALS['category_id_arr'] = array();
    // 先把自己的id 保存起来
    $GLOBALS['catGrandson'][] = $cat_id;
    // 把整张表找出来
    $GLOBALS['category_id_arr'] =  Db::name('ScenarioCategory')->cache(true,YLT_CACHE_TIME)->column('id,parent_id');
    // 先把所有下级找出来
    $son_id_arr =  Db::name('ScenarioCategory')->where("parent_id", $cat_id)->cache(true,YLT_CACHE_TIME)->column('id');
    foreach($son_id_arr as $k => $v)
    {
        getCatGrandson2($v);
    }
    return $GLOBALS['catGrandson'];
}

/**
 * 获取某个送礼分类的 下级 的 id
 * @param type $cat_id
 */
function getGiftGrandson ($cat_id)
{
    $GLOBALS['giftGrandson'] = array();
    $GLOBALS['gift_id_arr'] = array();
    // 先把自己的id 保存起来
    $GLOBALS['giftGrandson'][] = $cat_id;
    // 把整张表找出来
    $GLOBALS['gift_id_arr'] =  Db::name('GiftsCategory')->cache(true,YLT_CACHE_TIME)->column('id,parent_id');
    // 先把所有下级找出来
    $son_id_arr =  Db::name('GiftsCategory')->where("parent_id", $cat_id)->cache(true,YLT_CACHE_TIME)->column('id');
    foreach($son_id_arr as $k => $v)
    {
        getGiftGrandson2($v);
    }
    return $GLOBALS['giftGrandson'];
}

/**
 * 获取某个文章分类的 下级 的 id
 * @param type $cat_id
 */
function getArticleCatGrandson ($cat_id)
{
    $GLOBALS['ArticleCatGrandson'] = array();
    $GLOBALS['cat_id_arr'] = array();
    // 先把自己的id 保存起来
    $GLOBALS['ArticleCatGrandson'][] = $cat_id;
    // 把整张表找出来
    $GLOBALS['cat_id_arr'] =  Db::name('ArticleCat')->column('cat_id,parent_id');
    // 先把所有下级找出来
    $son_id_arr =  Db::name('ArticleCat')->where("parent_id", $cat_id)->column('cat_id');
    foreach($son_id_arr as $k => $v)
    {
        getArticleCatGrandson2($v);
    }
    return $GLOBALS['ArticleCatGrandson'];
}

/**
 * 递归调用找到 下下级
 * @param type $cat_id
 */
function getCatGrandson2($cat_id)
{
    $GLOBALS['catGrandson'][] = $cat_id;
    foreach($GLOBALS['category_id_arr'] as $k => $v)
    {
        
        if($v == $cat_id)
        {
            getCatGrandson2($k); // 继续找下级
        }
    }
}

/**
 * 递归调用找到 下下级
 * @param type $cat_id
 */
function getGiftGrandson2($cat_id)
{
    $GLOBALS['giftGrandson'][] = $cat_id;
    foreach($GLOBALS['gift_id_arr'] as $k => $v)
    {
        
        if($v == $cat_id)
        {
            getGiftGrandson2($k); // 继续找下级
        }
    }
}

/**
 * 递归调用找到 下下级
 * @param type $cat_id
 */
function getArticleCatGrandson2($cat_id)
{
    $GLOBALS['ArticleCatGrandson'][] = $cat_id;
    foreach($GLOBALS['cat_id_arr'] as $k => $v)
    {
       
        if($v == $cat_id)
        {
            getArticleCatGrandson2($k); // 继续找下级
        }
    }
}

/**
 * 查看某个用户购物车中商品的数量
 * @param type $user_id
 * @param type $session_id
 * @return type 购买数量
 */
function cart_goods_num($user_id = 0,$session_id = '')
{
    $where = " session_id = :session_id ";
    $bind['session_id'] = $session_id;
    if($user_id){
        $where .= " or user_id = :user_id ";
        $bind['user_id'] = $user_id;
    }
    // 查找购物车数量
    $cart_count =  Db::name('Cart')->where($where)->bind($bind)->sum('goods_num');
    $cart_count = $cart_count ? $cart_count : 0;
    return $cart_count;
}

/**
 * 一创 查看某个用户购物车中商品的数量
 * @param type $user_id
 * @param type $session_id
 * @return type 购买数量
 */
function agen_cart_goods_num($user_id = 0,$session_id = '')
{
    $where = " session_id = :session_id ";
    $bind['session_id'] = $session_id;
    if($user_id){
        $where .= " or user_id = :user_id ";
        $bind['user_id'] = $user_id;
    }
    // 查找购物车数量
    $cart_count =  Db::name('AgenCart')->where($where)->bind($bind)->sum('goods_num');
    $cart_count = $cart_count ? $cart_count : 0;
    return $cart_count;
}
/**
 * 红礼  查看某个用户购物车中商品的数量
 * @param type $user_id
 * @param type $session_id
 * @return type 购买数量
 */
function red_cart_goods_num($user_id = 0,$session_id = '')
{
    $where = " session_id = :session_id ";
    $bind['session_id'] = $session_id;
    if($user_id){
        $where .= " or user_id = :user_id ";
        $bind['user_id'] = $user_id;
    }
    // 查找购物车数量
    $cart_count =  Db::name('RedCart')->where($where)->bind($bind)->sum('goods_num');
    $cart_count = $cart_count ? $cart_count : 0;
    return $cart_count;
}
/**
 * 获取商品库存
 * @param type $goods_id 商品id
 * @param type $key  库存 key
 */
function getGoodNum($goods_id,$key)
{
    if(!empty($key))
        return  Db::name("GoodsPrice")->where(['goods_id' => $goods_id, 'key' => $key])->column('store_count');
    else
        return  Db::name("Goods")->where("goods_id", $goods_id)->column('store_count');
}
/**
 * 一创 获取商品库存
 * @param type $goods_id 商品id
 * @param type $key  库存 key
 */
function agen_getGoodNum($goods_id,$key)
{
    if(!empty($key))
        return  Db::name("AgenGoodsPrice")->where(['goods_id' => $goods_id, 'key' => $key])->column('store_count');
    else
        return  Db::name("AgenGoods")->where("goods_id", $goods_id)->column('store_count');
}
/**
 * 红礼 获取商品库存
 * @param type $goods_id 商品id
 * @param type $key  库存 key
 */
function red_getGoodNum($goods_id,$key)
{
    if(!empty($key))
        return  Db::name("RedGoodsPrice")->where(['goods_id' => $goods_id, 'key' => $key])->column('store_count');
    else
        return  Db::name("RedGoods")->where("goods_id", $goods_id)->column('store_count');
}

 
/**
 * 获取缓存或者更新缓存
 * @param string $config_key 缓存文件名称
 * @param array $data 缓存数据  array('k1'=>'v1','k2'=>'v3')
 * @return array or string or bool
 */
function tpCache($config_key,$data = array()){
    $param = explode('.', $config_key);
    if(empty($data)){
        //如$config_key=shop_info则获取网站信息数组
        //如$config_key=shop_info.logo则获取网站logo字符串
        $config = Cache::get($param[0],'',TEMP_PATH); //直接获取缓存文件

        if(empty($config)){
            //缓存文件不存在就读取数据库
            $res = Db::name('config')->where("inc_type",$param[0])->select();
            if($res){
                foreach($res as $k=>$val){
                    $config[$val['name']] = $val['value'];
                }
                Cache::set($param[0],$config,TEMP_PATH);
            }
        }
        if(count($param)>1){
            return $config[$param[1]];
        }else{
            return $config;
        }
    }else{
        //更新缓存
        $result =  Db::name('config')->where("inc_type", $param[0])->select();
        if($result){
            foreach($result as $val){
                $temp[$val['name']] = $val['value'];
            }
            foreach ($data as $k=>$v){
                $newArr = array('name'=>$k,'value'=>trim($v),'inc_type'=>$param[0]);
                if(!isset($temp[$k])){
                    Db::name('config')->insert($newArr);//新key数据插入数据库
                }else{
                    if($v!=$temp[$k])
                        Db::name('config')->where("name", $k)->update($newArr);//缓存key存在且值有变更新此项
                }
            }
            //更新后的数据库记录
            $newRes = Db::name('config')->where("inc_type", $param[0])->select();
            foreach ($newRes as $rs){
                $newData[$rs['name']] = $rs['value'];
            }
        }else{
            foreach($data as $k=>$v){
                $newArr[] = array('name'=>$k,'value'=>trim($v),'inc_type'=>$param[0]);
            }
            Db::name('config')->insertAll($newArr);
            $newData = $data;
        }
        return Cache::set($param[0],$newData,TEMP_PATH);
    }
}

function supplierCache($supplier_id,$config_key,$data = array()){
    $param = explode('.', $config_key);

        //更新数据
        $result =  Db::name('supplier_config')->where(array('inc_type'=>$param[0],'supplier_id'=>$supplier_id))->select();
        if($result){
            foreach($result as $val){
                $temp[$val['name']] = $val['value'];
            }
            foreach ($data as $k=>$v){
                $newArr = array('name'=>$k,'value'=>trim($v),'inc_type'=>$param[0],'supplier_id'=>$supplier_id);
                if(!isset($temp[$k])){
                    Db::name('supplier_config')->insert($newArr);//新key数据插入数据库
                }else{
                    if($v!=$temp[$k])
                        Db::name('supplier_config')->where(array('name'=>$k,'supplier_id'=>$supplier_id))->update($newArr);//缓存key存在且值有变更新此项
                }
            }
            //更新后的数据库记录
            $newRes = Db::name('supplier_config')->where(array('inc_type'=>$param[0],'supplier_id'=>$supplier_id))->select();
            foreach ($newRes as $rs){
                $newData[$rs['name']] = $rs['value'];
            }
        }else{
        	if(!empty($data)){
	            foreach($data as $k=>$v){
	                $newArr[] = array('name'=>$k,'value'=>trim($v),'inc_type'=>$param[0],'supplier_id'=>$supplier_id);
	            }
        	}
            if(!empty($newArr)){
            	Db::name('supplier_config')->insertAll($newArr);
            }
            $newData = $data;
        }
        return $newData;

}

/**
 * 记录帐户变动
 * @param   int     $user_id        用户id
 * @param   float   $user_money     可用余额变动
 * @param   int     $pay_points     消费积分变动
 * @param   int     $frozen_money   冻结资金
 * @param   string  $desc           变动说明
 * @param   string  $order_sn       订单编码
 * @param   int     $order_id       订单ID
 * @return  bool
 */
function accountLog($user_id, $user_money = 0,$pay_points = 0, $desc = '', $frozen_money = 0,$order_sn = '', $order_id = ''){
    /* 插入帐户变动记录 */
    $account_log = array(
        'user_id'       => $user_id,
        'user_money'    => $user_money,
        'frozen_money'  => $frozen_money,
        'pay_points'    => $pay_points,
        'change_time'   => time(),
        'desc'          => $desc,
        'order_sn'      => $order_sn,
        'order_id'      => $order_id,
    );
    /* 更新用户信息 */
    // $sql = "UPDATE __PREFIX__users SET user_money = user_money + $user_money ,frozen_money = frozen_money + $frozen_money" .
    $sql = "UPDATE ylt_users SET pay_points = pay_points + $pay_points ,user_money = user_money + $user_money ,frozen_money = frozen_money + $frozen_money" .
        " WHERE user_id = '$user_id'";
    if( DB::execute($sql)){
    	$log = Db::name('account_log')->insertGetId($account_log);
        return $log;
    }else{
        return false;
    }
}


/**
 * 礼豆帐户变动
 * @param   int     $user_id        用户id
 * @param   float   $bean_gift     	礼豆余额变动
 * @param	string 	$business_type	业务类型
 * @param   string  $desc           变动说明
 * @param   string  $order_sn       订单编码
 * @param   string  $supplier_id    商家推荐
 * @return  bool
 */
function beanGiftLog($user_id = 0, $bean_gift = 0,$business_type = '', $desc = '', $order_sn = '',$supplier_id = ''){
    /* 插入帐户变动记录 */
	$change_type = $bean_gift > 0 ? 1 : 2; // 1为收入 2为支出
    $account_log = array(
        'user_id'       => $user_id,
        'bean_gift'     => $bean_gift,
        'change_time'   => time(),
		'business_type'	=> $business_type,
        'desc'          => $desc,
        'order_sn'      => $order_sn,
		'supplier_id'   => $supplier_id,
		'change_type'	=> $change_type
    );
    /* 更新用户信息 */
	if($supplier_id){
		$sql = "UPDATE __PREFIX__supplier SET bean_gift = bean_gift + $bean_gift " .
        " WHERE supplier_id = '$supplier_id'";
		
	}else{
		$sql = "UPDATE __PREFIX__users SET bean_gift = bean_gift + $bean_gift " .
        " WHERE user_id = '$user_id'";
	}
    
    if( DB::execute($sql)){
    	$log = Db::name('bean_gift_log')->insertGetId($account_log);
        return $log;
    }else{
        return false;
    }
}

/**
 * 礼金帐户变动
 * @param   int     $user_id        用户id
 * @param   float   $cash_gift     	礼金余额变动
 * @param	string 	$business_type	业务类型
 * @param   string  $desc           变动说明
 * @param   string  $order_sn       订单编码
 * @param   string  $supplier_id    商家推荐
 * @return  bool
 */
function cashGiftLog($user_id = 0, $cash_gift = 0,$business_type = '', $desc = '', $order_sn = '',$supplier_id = ''){
    /* 插入帐户变动记录 */
	$change_type = $cash_gift > 0 ? 1 : 2; // 1为收入 2为支出
    $account_log = array(
        'user_id'       => $user_id,
        'cash_gift'     => $cash_gift,
        'change_time'   => time(),
		'business_type'	=> $business_type,
        'desc'          => $desc,
        'order_sn'      => $order_sn,
		'supplier_id'   => $supplier_id,
		'change_type'	=> $change_type
    );
    /* 更新用户信息 */
	if($supplier_id){
		$sql = "UPDATE __PREFIX__supplier SET cash_gift = cash_gift + $cash_gift " .
        " WHERE supplier_id = '$supplier_id'";
		
	}else{
		$sql = "UPDATE __PREFIX__users SET cash_gift = cash_gift + $cash_gift " .
        " WHERE user_id = '$user_id'";
	}
    
    if( DB::execute($sql)){
    	$log = Db::name('cash_gift_log')->insertGetId($account_log);
        return $log;
    }else{
        return false;
    }
}


/**
 * 销售奖帐户变动
 * @param   int     $user_id        用户id
 * @param   float   $sale_gift     	销售奖余额变动
 * @param   string  $desc           变动说明
 * @param   string  $order_sn       订单编码
 * @param   string  $supplier_id    商家推荐
 * @return  bool
 */
function saleGiftLog($user_id, $sale_gift = 0, $business_type = '',$desc = '', $order_sn = '',$supplier_id = '',$none_portion = 0,$order = array()){
    /* 插入帐户变动记录 */
	$change_type = $sale_gift > 0 ? 1 : 2;
    $account_log = array(
        'user_id'       => $user_id,
        'sale_gift'     => $sale_gift,
        'change_time'   => time(),
		'business_type'	=> $business_type,
        'desc'          => $desc,
        'order_sn'      => $order_sn,
		'supplier_id'   => $supplier_id,
		'change_type'	=> $change_type,
		'none_portion'	=> $none_portion,
		'supplier_name' => $order['supplier_name'],
		'total_amount' => $order['total_amount'],
    );
    /* 更新用户信息 */
	if($supplier_id){
		$sql = "UPDATE __PREFIX__supplier SET sale_gift = sale_gift + $sale_gift " .
        " WHERE supplier_id = '$supplier_id'";
		
	}else{
		//记录销售额度
		if($change_type == '1'){
			//业务等级
			$sql = "UPDATE __PREFIX__busines_agent SET sales_amount = sales_amount + $sale_gift " .
        " WHERE FUid = '$user_id'";
		$row = DB::execute($sql);
			 if(!$row){
				 $sql = "UPDATE __PREFIX__busines_manager SET sales_amount = sales_amount + $sale_gift " .
			" WHERE FUid = '$user_id'";
				 DB::execute($sql);
			 }
		}
		
	
		$sql = "UPDATE __PREFIX__users SET sale_gift = sale_gift + $sale_gift " .
        " WHERE user_id = '$user_id'";
	}
    
    if( DB::execute($sql)){
    	$log = Db::name('sale_gift_log')->insertGetId($account_log);
        return $log;
    }else{
        return false;
    }
}


/**
 * 开发奖帐户变动
 * @param   int     $user_id        用户id
 * @param   float   $open_gift     	开拓奖余额变动
 * @param   string  $desc           变动说明
 * @param   string  $order_sn       订单编码
 * @param   string  $supplier_id    商家推荐
 * @return  bool
 */
function openGiftLog($user_id, $open_gift = 0, $business_type = '',$desc = '', $order_sn = '',$supplier_id = '',$none_portion = 0,$order = array()){
    /* 插入帐户变动记录 */
	$change_type = $open_gift > 0 ? 1 : 2;
    $account_log = array(
        'user_id'       => $user_id,
        'open_gift'     => $open_gift,
        'change_time'   => time(),
		'business_type'	=> $business_type,
        'desc'          => $desc,
        'order_sn'      => $order_sn,
		'supplier_id'   => $supplier_id,
		'change_type'	=> $change_type,
		'none_portion'	=> $none_portion,
		'supplier_name'	=> $order['supplier_name'],
		'total_amount'	=> $order['total_amount'],
		
    );
    /* 更新用户信息 */
	if($supplier_id){
		$sql = "UPDATE __PREFIX__supplier SET open_gift = open_gift + $open_gift " .
        " WHERE supplier_id = '$supplier_id'";
		
	}else{
		$sql = "UPDATE __PREFIX__users SET open_gift = open_gift + $open_gift " .
        " WHERE user_id = '$user_id'";
	}
    
    if( DB::execute($sql)){
    	$log = Db::name('open_gift_log')->insertGetId($account_log);
        return $log;
    }else{
        return false;
    }
}

/**
 * 订单操作日志
 * 参数示例
 * @param type $order_id  订单id
 * @param type $action_note 操作备注
 * @param type $status_desc 操作状态  提交订单, 付款成功, 取消, 等待收货, 完成
 * @param type $user_id  用户id 默认为管理员
 * @return boolean
 */
function logOrder($order_id,$action_note,$status_desc,$user_id = 0)
{
    $status_desc_arr = array('提交订单', '付款成功', '取消', '等待收货', '完成','退货');
    // if(!in_array($status_desc, $status_desc_arr))
    // return false;

    $order = Db::name('order')->where("order_id", $order_id)->find();
    $action_info = array(
        'order_id'        =>$order_id,
        'action_user'     =>$user_id,
        'order_status'    =>$order['order_status'],
        'shipping_status' =>$order['shipping_status'],
        'pay_status'      =>$order['pay_status'],
        'action_note'     => $action_note,
        'status_desc'     =>$status_desc, //''
        'log_time'        =>time(),
    );
    return Db::name('order_action')->insert($action_info);
}
/*一创*/
function agen_logOrder($order_id,$action_note,$status_desc,$user_id = 0)
{
    $status_desc_arr = array('提交订单', '付款成功', '取消', '等待收货', '完成','退货');
    // if(!in_array($status_desc, $status_desc_arr))
    // return false;

    $order = Db::name('agen_order')->where("order_id", $order_id)->find();
    $action_info = array(
        'order_id'        =>$order_id,
        'action_user'     =>$user_id,
        'order_status'    =>$order['order_status'],
        'shipping_status' =>$order['shipping_status'],
        'pay_status'      =>$order['pay_status'],
        'action_note'     => $action_note,
        'status_desc'     =>$status_desc, //''
        'log_time'        =>time(),
    );
    return Db::name('agen_order_action')->insert($action_info);
}
/*红礼*/
function red_logOrder($order_id,$action_note,$status_desc,$user_id = 0)
{
    $status_desc_arr = array('提交订单', '付款成功', '取消', '等待收货', '完成','退货');
    // if(!in_array($status_desc, $status_desc_arr))
    // return false;

    $order = Db::name('red_order')->where("order_id", $order_id)->find();
    $action_info = array(
        'order_id'        =>$order_id,
        'action_user'     =>$user_id,
        'order_status'    =>$order['order_status'],
        'shipping_status' =>$order['shipping_status'],
        'pay_status'      =>$order['pay_status'],
        'action_note'     => $action_note,
        'status_desc'     =>$status_desc, //''
        'log_time'        =>time(),
    );
    return Db::name('red_order_action')->insert($action_info);
}
/*
 * 获取地区列表
 */
function get_region_list(){
    //获取地址列表 缓存读取
    if(!Cache::get('region_list')){
        $region_list = Db::name('region')->select();
        $region_list = convert_arr_key($region_list,'id');
        Cache::set('region_list',$region_list);
    }

    return $region_list ? $region_list : Cache::get('region_list');
}
/*
 * 获取用户地址列表
 */
function get_user_address_list($user_id){
    $lists = Db::name('user_address')->where(array('user_id'=>$user_id))->order('is_default desc')->select();
    return $lists;
}

/*
 * 获取指定地址信息
 */
function get_user_address_info($user_id,$address_id){
    $data = Db::name('user_address')->where(array('user_id'=>$user_id,'address_id'=>$address_id))->find();
    return $data;
}
/*
 * 获取用户默认收货地址
 */
function get_user_default_address($user_id){
    $data = Db::name('user_address')->where(array('user_id'=>$user_id,'is_default'=>1))->find();
    return $data;
}
/**
 * 获取订单状态的 中文描述名称
 * @param type $order_id  订单id
 * @param type $order     订单数组
 * @return string
 */
function orderStatusDesc($order_id = 0, $order = array())
{
    if(empty($order))
        $order = Db::name('Order')->where("order_id", $order_id)->find();


    if($order['pay_status'] == 0 && $order['order_status'] == 0)
        return 'WAITPAY'; //'待支付',
    if($order['pay_status'] == 1 &&  in_array($order['order_status'],array(0,1)) && $order['shipping_status'] != 1)
        return 'WAITSEND'; //'待发货',
    if(($order['shipping_status'] == 1) && ($order['order_status'] == 1))
        return 'WAITRECEIVE'; //'待收货',
    if($order['order_status'] == 2)
        return 'WAITCCOMMENT'; //'待评价',
    if($order['order_status'] == 3)
        return 'CANCEL'; //'已取消',
    if($order['order_status'] == 4)
        return 'FINISH'; //'已完成',
    if($order['order_status'] == 5)
    	return 'CANCELLED'; //'已作废',
    return 'OTHER';
}

/**
 * 获取订单状态的 显示按钮
 * @param type $order_id  订单id
 * @param type $order     订单数组
 * @return array()
 */
function orderBtn($order_id = 0, $order = array())
{
    if(empty($order))
        $order = Db::name('Order')->where("order_id", $order_id)->find();
    /**
     *  订单用户端显示按钮
    去支付     AND pay_status=0 AND order_status=0 AND pay_code ! ="cod"
    取消按钮  AND pay_status=0 AND shipping_status=0 AND order_status=0
    确认收货  AND shipping_status=1 AND order_status=0
    评价      AND order_status=1
    查看物流  if(!empty(物流单号))
     */
    $btn_arr = array(
        'pay_btn' => 0, // 去支付按钮
        'cancel_btn' => 0, // 取消按钮
        'receive_btn' => 0, // 确认收货
        'comment_btn' => 0, // 评价按钮
        'shipping_btn' => 0, // 查看物流
        'return_btn' => 0, // 退货按钮 (联系客服)
    );


    if($order['pay_status'] == 0 && $order['order_status'] == 0) // 待支付
    {
        $btn_arr['pay_btn'] = 1; // 去支付按钮
        $btn_arr['cancel_btn'] = 1; // 取消按钮
    }
    if($order['pay_status'] == 1 && in_array($order['order_status'],array(0,1)) && $order['shipping_status'] == 0) // 待发货
    {
        $btn_arr['return_btn'] = 1; // 退货按钮 (联系客服)
    }
    if($order['pay_status'] == 1 && $order['order_status'] == 1  && $order['shipping_status'] == 1) //待收货
    {
        $btn_arr['receive_btn'] = 1;  // 确认收货
        $btn_arr['return_btn'] = 1; // 退货按钮 (联系客服)
    }

    if($order['order_status'] == 2)
    {
        $btn_arr['comment_btn'] = 1;  // 评价按钮
        $btn_arr['return_btn'] = 1; // 退货按钮 (联系客服)
    }
    if($order['shipping_status'] != 0)
    {
        $btn_arr['shipping_btn'] = 1; // 查看物流
    }
    if($order['shipping_status'] == 2 && $order['order_status'] == 1) // 部分发货
    {            
        $btn_arr['return_btn'] = 1; // 退货按钮 (联系客服)
    }

    return $btn_arr;
}

/**
 * 给订单数组添加属性  包括按钮显示属性 和 订单状态显示属性
 * @param type $order
 */
function set_btn_order_status($order)
{
    $order_status_arr = config('ORDER_STATUS_DESC');
    $order['order_status_code'] = $order_status_code = orderStatusDesc(0, $order); // 订单状态显示给用户看的
    $order['order_status_desc'] = $order_status_arr[$order_status_code];
    $orderBtnArr = orderBtn(0, $order);
    return array_merge($order,$orderBtnArr); // 订单该显示的按钮
}


/**
 * 支付完成修改订单
 * @param $order_sn 订单号
 * @param array $ext 额外参数
 * @return bool|void
 */
function update_pay_status($order_sn,$ext=array())
{
		$businesLogin = new ylt\admin\logic\BusinessLogic();
		//区域经理、微店主入驻支付回调
		$pos = strpos($order_sn,'us');
		if($pos === false){
			//Db::name('entry_order')->insert(['order_sn'=>'4444']);
		}else{
			//Db::name('entry_order')->insert(['order_sn'=>'ssss']);
			$row = $businesLogin->managerDividedInfo($order_sn,$ext);
			
			if($row['status'] ==1)
				return true;
			else
				return false;
		}
		

		// 如果这笔订单已经处理过了
		$count = Db::name('order')->where("order_sn = :order_sn and pay_status = 0 OR pay_status = 2")->bind(['order_sn'=>$order_sn])->count();   // 看看有没已经处理过这笔订单  支付宝返回不重复处理操作
		if($count == 0) return false;
		// 找出对应的订单
        $order = Db::name('order')->where("order_sn",$order_sn)->find();
  		$word=var_export($order,true);
		$fp = fopen("update_pay_status.txt","a");
              			flock($fp, LOCK_EX) ;
    					fwrite($fp,"执行日期：".strftime("%Y%m%d%H%M%S",time())."\n".$word."\n");
    					flock($fp, LOCK_UN);
    					fclose($fp);
		//生成销售、推荐分成记录
		//$businesLogin->orderDividedInto($order,$ext);
        $fp = fopen("orderDividedInto.txt","a");
              			flock($fp, LOCK_EX) ;
    					fwrite($fp,"执行日期：".strftime("%Y%m%d%H%M%S",time())."\n发发发\n");
    					flock($fp, LOCK_UN);
    					fclose($fp);
        // 修改支付状态  已支付
        Db::name('order')->where("order_sn", $order_sn)->update(array('pay_status'=>1,'pay_time'=>time(),'transaction_id'=>$ext['transaction_id']));
        //红礼同步修改状态
        Db::name('red_order')->where("order_sn", $order_sn)->update(array('pay_status'=>1,'pay_time'=>time(),'transaction_id'=>$ext['transaction_id']));
        //红礼同步修改状态结束
        //修改分销订单支付时间
        $a=Db::name('order_distribution')->where("order_sn", $order_sn)->find();
        if ($a) {
            Db::name('order_distribution')->where("order_sn", $order_sn)->update(array('payment_time'=>time()));
        }
        //修改分销订单支付时间结束
        
        // //抗疫订单支付后自动生成推荐人申请记录
        // if ($order['admin_note']=='share_record') {
        //     $users = Db::name('users')->where('user_id',$order['user_id'])->find();
        //     if ($users['source_id'] || $users['referrer_id'] ) {
        //         if ($users['source_id']) {
        //             $source['user_id']  = $users['source_id'];
        //         }elseif($users['referrer_id']){
        //             $source['user_id']  = $users['referrer_id'];
        //         }
        //         $source['phone']    = $users['mobile'];
        //         $source['add_time'] = time();
        //         $source['ip'] = getIP();
        //         $source['type'] = 3;
        //         Db::name('goods_apply_list')->insert($source);
        //     }
        // }
        // //抗疫活动结束后可删
        
        //预约商品完成后修改预约记录的字段
        $order_goods_select = Db::name('order_goods')->where("order_id", $order['order_id'])->select();
        foreach ($order_goods_select as $key => $value) {
            Db::name('goods_consult')->where(['user_id'=>$order['user_id'],'goods_id'=>$value['goods_id']])->update(['is_use'=>1]);
        }
        //结束


        //将用户的临时推荐人清零
        Db::name('users')->where("user_id", $order['user_id'])->update(array('source_id'=>0));
        //将用户的临时推荐人清零结束

		 $fp = fopen("update_status.txt","a");
              			flock($fp, LOCK_EX) ;
    					fwrite($fp,"执行日期：".strftime("%Y%m%d%H%M%S",time())."\n进来了\n");
    					flock($fp, LOCK_UN);
    					fclose($fp);
		// 减少对应商品的库存
        if($order['order_prom_type'] == 0)
             minus_stock($order['order_id']);

		// 入驻商家、设计师佣金
        if($order['is_designer'] == 0){
            supplier_settlement($order);
        }else{

            $desc ='订单 ：'. Db::name('order_goods')->where('order_id',$order['order_id'])->value('goods_name');
            $user_id = Db::name('supplier')->where(['supplier_id'=>$order['supplier_id'],'is_designer'=>1])->value('user_id');
            $log = accountLog($user_id,0,$order['order_amount'],0,$desc,$order['order_sn'],$order['order_id']);
            Db::name('order')->where('order_id',$order['order_id'])->update(['is_distribut'=>$log]);
        }

		// 记录订单操作日志
            logOrder($order['order_id'],'订单付款成功','付款成功',$order['user_id']);
       // 每天第一条支付订单给予短信提醒
        $day_time = strtotime(date("Y-m-d"));
        if(1 == Db::name('order')->where(['pay_status'=>1,'supplier_id'=>$order['supplier_id']])->where('add_time','>',$day_time)->count()){
            $supplier_mobile = Db::name('supplier_user')->where('supplier_id',$order['supplier_id'])->order('admin_id')->value('mobile');
            sendCode($supplier_mobile,'您的店铺有一条新的订单待处理。');
        }
        
        //拼单商品，拼单的订单生成拼单记录
        if ($order['is_share'] == 1) {              //发起记录
            $prom['goods_id'] = Db::name('order_goods')->where("order_id", $order['order_id'])->value('goods_id');
            $prom['prom_id']  = Db::name('goods')->where("goods_id", $prom['goods_id'])->value('prom_id');
            $prom['prom_type']= 7;
            $prom['u_id']     = $order['user_id'];
            $prom['add_time'] = $prom['join_time'] = time();
            $prom['is_initiate'] = 1;
            $prom['type'] = 1;
            $prom['order_id'] = $order['order_id'];
            $prom['phone'] = Db::name('users')->where("user_id", $order['user_id'])->value('mobile');
            $prom['quantity'] = Db::name('order_goods')->where("order_id", $order['order_id'])->value('goods_num');
            $array_id = Db::name('share_the_bill')->insertGetId($prom);
            if ($array_id) {
                Db::name('share_the_bill')->where("id", $array_id)->update(['p_id'=>$array_id]);
                Db::name('order')->where("order_id",$order['order_id'])->update(['is_share'=>$array_id]);
            }
        }elseif($order['is_share'] > 1){            //加入拼单的ID和加入记录
            $prom['goods_id'] = Db::name('order_goods')->where("order_id", $order['order_id'])->value('goods_id');
            $prom['prom_id']  = Db::name('goods')->where("goods_id", $prom['goods_id'])->value('prom_id');
            $prom['prom_type']= 7;
            $prom['u_id']     = $order['user_id'];
            $prom['join_time'] = time();
            $prom['is_initiate'] = 0;
            $prom['type'] = 1;
            $prom['order_id'] = $order['order_id'];
            $prom['phone'] = Db::name('users')->where("user_id", $order['user_id'])->value('mobile');
            $prom['p_id'] = $order['is_share'];
            $prom['quantity'] = Db::name('order_goods')->where("order_id", $order['order_id'])->value('goods_num');
            $array_id = Db::name('share_the_bill')->insertGetId($prom);
        }
        //查询是否已完成拼单，修改拼单状态
        if ($array_id) {        
            $count = Db::name('share_the_bill')->where(['p_id'=>$order['is_share']])->count();
            $quantity = Db::name('share_the_bill')->where(['p_id'=>$order['is_share']])->sum('quantity');
            $discount_buy = Db::name('discount_buy')->alias('d')->join('share_the_bill s','d.id = s.prom_id')->where(['s.prom_type'=>7,'s.id'=>$order['is_share']])->find();
            if ($discount_buy['buy_type_rule']== 1) {      //1用户 
                if (($discount_buy['buy_type_rule_num'] - $count) <= 0) {
                    Db::name('share_the_bill')->where("p_id", $order['is_share'])->update(['type'=>2]);
                    Db::name('order')->where(["is_share"=>$order['is_share'],'pay_status'=>0])->update(['order_status'=>3]);  //取消已完成外的其它未支付的该拼单订单
                }
            }elseif ($discount_buy['buy_type_rule']== 2) {      //2数量 
                if (($discount_buy['buy_type_rule_num'] - $quantity ) <= 0) {
                    Db::name('share_the_bill')->where("p_id", $order['is_share'])->update(['type'=>2]);
                    Db::name('order')->where(["is_share"=>$order['is_share'],'pay_status'=>0])->update(['order_status'=>3]);  //取消已完成外的其它未支付的该拼单订单
                }
            }
            //短信提示
            $share_list = Db::name('share_the_bill')->where(["p_id"=>$order['is_share'],'type'=>2])->select();
            if ($share_list) {
                foreach ($share_list as $key => $value) {
                    sendCode($value['phone'],'恭喜您拼单成功，请耐心等待发货。');   //发给客户
                }
                $mobile = Db::name('config')->where("name",'mobile')->value('value');
                sendCode($mobile,'您有一个拼单成功订单，请及时处理发货。');   //发给后台
                // sendCode('13728740390','您有一个拼单成功订单，请及时处理发货。');   //发给后台
            }
        }
        //拼单 结束
        
		if(!$res || $res['status'] !=1) return ;

}

    /**
     * 订单确认收货
     * @param $id   订单id
     */
    function confirm_order($id,$user_id = 0){
        $where = "order_id = :id";
        $bind['id'] = $id;
        $user_id && $where .= " and user_id = $user_id ";

        $order = Db::name('order')->where($where)->bind($bind)->find();
        if($order['order_status'] != 1){
            return array('status'=>-1,'msg'=>'该订单不能收货确认');
        }
        
        $data['order_status'] = 2; // 已收货        
        $data['close'] = 2;        // 可结算        
        $data['pay_status'] = 1; // 已付款        
        $data['confirm_time'] = time(); // 收货确认时间
        if($order['pay_code'] == 'cod'){
        	$data['pay_time'] = time();
        }
        $row = Db::name('order')->where(array('order_id'=>$id))->update($data);
        if (!empty($order['red_supplier_id'])) {
            Db::name('red_order')->where("order_id=$id")->update($data);
        }
        if(!$row){      
            return array('status'=>-3,'msg'=>'操作失败');
        }  
        $allow_time = 3600 * 24 *7 ;
        $log['allow_time'] = time() + $allow_time;
        $log['sign_status'] = 1;
        Db::name('account_log')->where('order_id',$id)->update($log);
               
        return array('status'=>1,'msg'=>'操作成功');
    }
    /*一创*/
    function agen_confirm_order($id,$user_id = 0){
        
        $where = "order_id = :id";
        $bind['id'] = $id;
        $user_id && $where .= " and user_id = $user_id ";

        $order = Db::name('agen_order')->where($where)->bind($bind)->find();
        if($order['order_status'] != 1)
            return array('status'=>-1,'msg'=>'该订单不能收货确认');
        
        $data['order_status'] = 3; // 已收货        
        $data['close'] = 2;        // 可结算        
        $data['pay_status'] = 1; // 已付款        
        $data['confirm_time'] = time(); // 收货确认时间
        if($order['pay_code'] == 'cod'){
            $data['pay_time'] = time();
        }
        $row = Db::name('agen_order')->where(array('order_id'=>$id))->update($data);
        if(!$row){      
            return array('status'=>-3,'msg'=>'操作失败');
        }  
        return array('status'=>1,'msg'=>'操作成功');
    }
    /*红礼*/
    function red_confirm_order($id,$user_id = 0){
        
        $where = "order_id = :id";
        $bind['id'] = $id;
        $user_id && $where .= " and user_id = $user_id ";

        $order = Db::name('red_order')->where($where)->bind($bind)->find();
        if($order['order_status'] != 1)
            return array('status'=>-1,'msg'=>'该订单不能收货确认');
        
        $data['order_status'] = 3; // 已收货        
        $data['close'] = 2;        // 可结算        
        $data['pay_status'] = 1; // 已付款        
        $data['confirm_time'] = time(); // 收货确认时间
        if($order['pay_code'] == 'cod'){
            $data['pay_time'] = time();
        }
        $row = Db::name('red_order')->where(array('order_id'=>$id))->update($data);
        if(!$row){      
            return array('status'=>-3,'msg'=>'操作失败');
        }  
        return array('status'=>1,'msg'=>'操作成功');
    }
    /**
     * 订单确认完成
     * @param $id   订单id
     */
    function notarize($id,$user_id = 0){
        $where = "order_id = :id";
        $bind['id'] = $id;
        $user_id && $where .= " and user_id = $user_id ";

        $order = Db::name('order')->where($where)->bind($bind)->find();
        if($order['order_status'] != 2){
            return array('status'=>-1,'msg'=>'该订单不能被确认完成');
        }
        
        $data['order_status'] = 4; // 已收货        
        $data['close'] = 0;        // 可结算        
        if($order['pay_code'] == 'cod'){
            $data['pay_time'] = time();
        }
        $row = Db::name('order')->where(array('order_id'=>$id))->update($data);
        if(!$row){       
            return array('status'=>-3,'msg'=>'操作失败');
        }else{
            //订单完成同步处理分销订单表的状态
            Db::name('order_distribution')->where(array('order_id'=>$id))->update(['order_type'=>1,'completion'=>time()]);
        }
        return array('status'=>1,'msg'=>'操作成功');
    }

/**
 * 取消活动订单
 * @param $id   订单id
 */
function cancel_activity_order($id){

    $where = "order_id = $id";

    $order= Db::name('order')->where($where)->find();

    if($order['order_status'] == 0 && $order['pay_time'] == 0 && $order['order_prom_id'] > 0){

        Db::name('order')->where($where)->update(['order_status'=>3,'close'=>2]);
        $sql= "update __PREFIX__panic_buying set order_num = order_num -1 ,buy_num = buy_num -1 where id = ".$order['order_prom_id']."";
        Db::execute($sql);

    }
    if(!$order)
        return array('status'=>-3,'msg'=>'操作失败');

    return array('status'=>1,'msg'=>'操作成功');
}




/**
 * 查看商品是否有活动
 * @param goods_id 商品ID
 */

function get_goods_promotion($goods_id,$user_id=0){
	$now = time();
	$goods = Db::name('goods')->where("goods_id", $goods_id)->find();
    if ($goods['prom_type'] == 6) {
        $where = [
            'make_go' => ['gt', $now],
            'purchase_in' => ['lt', $now],
            'id' => $goods['prom_id'],
        ];
    }else{
        $where = [
            'end_time' => ['gt', $now],
            'start_time' => ['lt', $now],
            'id' => $goods['prom_id'],
        ];
    }
	
	$prom['price'] = $goods['shop_price'];
	$prom['prom_type'] = $goods['prom_type'];
	$prom['prom_id'] = $goods['prom_id'];
	$prom['is_end'] = 0;
	
	if($goods['prom_type'] == 1 || $goods['prom_type'] == 5){//抢购 秒杀
        if($goods['prom_type'] == 1)
		    $prominfo = Db::name('panic_buying')->where($where)->find();
        else
            $prominfo = Db::name('panic_buying')->where('id',$goods['prom_id'])->find();
		if(!empty($prominfo)){
			if($prominfo['goods_num'] == $prominfo['buy_num']){
				$prom['is_end'] = 2;//已售馨
			}else{
				//核查用户购买数量
				$where = "user_id = :user_id and order_status!=3 and  add_time>".$prominfo['start_time']." and add_time<".$prominfo['end_time'];
				$order_id_arr = Db::name('order')->where($where)->bind(['user_id'=>$user_id])->column('order_id');
				if($order_id_arr){
					$goods_num = Db::name('order_goods')->where("prom_id={$goods['prom_id']} and prom_type={$goods['prom_type']} and order_id in (".implode(',', $order_id_arr).")")->sum('goods_num');
					if($goods_num < $prominfo['buy_limit']){
						$prom['price'] = $prominfo['price'];
					}
				}else{
					$prom['price'] = $prominfo['price'];
				}
			} 				
		}
		
		$prom['description'] = $prominfo['description'];
		$prom['buy_limit'] = $prominfo['buy_limit'];
	}

    //秒杀/抢购 、预约、拼单 新数据
	if($goods['prom_type'] == 2 || $goods['prom_type'] == 6 || $goods['prom_type'] == 7 ){ 
		$prominfo = Db::name('discount_buy')->where('id',$goods['prom_id'])->find();
		$goodsprom = Db::name('discount_goods')->where('goods_id',$goods_id)->where('discount_id',$goods['prom_id'])->find();
		$prom['title'] = $prominfo['title'];	//活动标题
		$prom['price'] = $goodsprom['activity_price'];	//活动价
		$prom['activity_market_price'] = $goodsprom['activity_market_price']; //活动原价
		$prom['goods_num'] = $goodsprom['activity_count']; //活动库存
		$prom['buy_num']	= $goodsprom['order_num'];	//已购商品数量
        if ($goods['prom_type'] == 7) {         //修改超时的拼单状态
            $select = Db::name('share_the_bill')->where(['goods_id'=>$goods_id,'type'=>1])->select();
            foreach ($select as $key => $value) {
                $rule_time = time() - $prominfo['buy_type_rule_time']*3600;
                $the_bill  = Db::name('share_the_bill')->where(['goods_id'=>$goods_id,'type'=>1,'is_initiate'=>1])->where("add_time < $rule_time")->select();
                foreach ($the_bill as $ke => $val) {
                    Db::name('share_the_bill')->where(['p_id'=>$val['id'],'type'=>1])->update(['type'=>3]);
                    $order = Db::name('order_goods')->where(['goods_id'=>$goods_id])->select();
                    foreach ($order as $key => $valu) {
                        //取消超时订单
                        Db::name('order')->where(['order_id'=>$valu['order_id'],'is_share'=>$val['id']])->update(['order_status'=>3]);
                    }

                    //超时订单自动生成退款申请
                    $is_apply = Db::name('share_the_bill')->where(['p_id'=>$val['id'],'type'=>3,'is_apply'=>0])->select();
                    foreach ($is_apply as $key => $va) {
                        $logic = new UsersLogic();
                        $return = $logic->back_order_s($va['order_id'],$goods_id,'',1,$va['quantity'],'拼单超时的退款申请','',$va['u_id']);
                        if ($return ='ok') {
                            Db::name('share_the_bill')->where(['order_id'=>$va['order_id'],'type'=>3,'is_apply'=>0])->update(['is_apply'=>1]);
                            //短信提示
                            $mobile = Db::name('config')->where("name",'mobile')->value('value');
                            $order_sn = Db::name('order')->where(['order_id'=>$va['order_id']])->value('order_sn');
                            sendCode($va['phone'],"抱歉， 您的订单编号：$order_sn,拼单失败，请耐心等待退款。您可以重新参与拼单或单独购买。");   //发给客户
                            sendCode($mobile,"订单编号为：$order_sn,申请了退款，请处理。");   //发给后台
                            // sendCode('13728740390',"订单编号为：$order_sn,申请了退款，请处理。");   //发给后台
                        }
                    }
                }
            }
        }
	}
	
	if($goods['prom_type'] == 3){//优惠促销
		$parse_type = array('0'=>'直接打折','1'=>'单品满减优惠','2'=>'固定金额出售','3'=>'买就赠优惠券');
		$prominfo = Db::name('prom_goods')->where('id',$goods['prom_id'])->find();
		if(!empty($prominfo)){
			if($prominfo['type'] == 0){
				$prom['price'] = $goods['shop_price']*$prominfo['expression']/100;//打折优惠
			}elseif($prominfo['type'] == 1){
				$prom['price'] = $goods['shop_price'];//单品满减优惠
			}elseif($prominfo['type']==2){
				$prom['price'] = $prominfo['expression'];//固定金额优惠
			}
		}
		
		$prom['type'] = $prominfo['type'];
		$prom['money'] = $prominfo['money'];
		$prom['expression'] = $prominfo['expression'];
	}

    // if ($goods['prom_type'] == 4) {//领券用券
    //     $parse_type = array('0'=>'模板','1'=>'按用户发放','2'=>'注册发放','3'=>'邀请发放','4'=>'线下发放');
    //     $coupon = Db::name('coupon')->where('id',$goods['prom_id'])->find();
    //     if(!empty($coupon)){
    //         if($coupon['type'] == 3){
    //             $prom['type'] = $prominfo['type'];
    //             $prom['money'] = $prominfo['condition'];
    //             $prom['condition'] = $prominfo['money'];
    //     }
    // }
    
 
	
	if(!empty($prominfo) && $prominfo['buy_type'] == 6){
        $prom['make_go'] = $prominfo['make_go'];
        $prom['make_in'] = $prominfo['make_in'];
        $prom['purchase_go'] = $prominfo['purchase_go'];
        $prom['purchase_in'] = $prominfo['purchase_in'];
        $prom['express_go'] = $prominfo['express_go'];
        $prom['start_time'] = $prominfo['make_go'];
        $prom['end_time'] = $prominfo['purchase_in'];
    }elseif(!empty($prominfo) && $prominfo['buy_type'] == 7){
        $prom['start_time'] = $prominfo['start_time'];
        $prom['end_time'] = $prominfo['end_time'];
        $prom['buy_type_rule'] = $prominfo['buy_type_rule'];            //拼单活动规则，1用户 2数量
        $prom['buy_type_rule_num'] = $prominfo['buy_type_rule_num'];    //活动规定的数量
        $prom['buy_type_rule_time'] = $prominfo['buy_type_rule_time'];  //拼单发起时效
        $prom['buy_type_purchase'] = $prominfo['buy_type_purchase'];    //是否限购  1是 2否
        $prom['buy_type_purchase_num'] = $prominfo['buy_type_purchase_num'];    //限购的起购量
        $prom['buy_type_purchase_num_s'] = $prominfo['buy_type_purchase_num_s'];//限购的限购量
	}elseif(!empty($prominfo) && $prominfo['buy_type'] != 0){
        $prom['start_time'] = $prominfo['start_time'];
        $prom['end_time'] = $prominfo['end_time'];
    }else{
		$prom['prom_type'] = $prom['prom_id'] = 0 ;//活动已过期
		$prom['is_end'] = 1;//已结束
	}
    // if($prominfo['buy_type'] != 6 && $prominfo['buy_type'] != 7 && $prominfo['end_time'] < $now){
	if($prominfo['end_time'] < $now){
		if($goods['prom_type'] > 0 && $prom['prom_id'] > 0){
			Db::name('goods')->where("prom_type", $goods['prom_type'])->where('prom_id',$prom['prom_id'])->update(['prom_type'=>0,'prom_id'=>0]);
			Db::name('cart')->where("prom_type", $goods['prom_type'])->where('prom_id',$prom['prom_id'])->delete();
		}
		return 1;//已结束
	}
	return $prom;
}

    /**
     * 查看订单是否满足条件参加活动
     * @param result 订单详情
     */
    function get_order_promotion($result){
    	$parse_type = array('0'=>'满额打折','1'=>'满额优惠金额','2'=>'满额送倍数积分','3'=>'满额送优惠券','4'=>'满额免运费');
        $order_amount=$result['order_amount'];
        $now = time();
        foreach ($result as $key => $value) {
            for ($i=0; $i <count($value) ; $i++) { 
                if (!empty($value[$i]) && is_array($value[$i])) {
                    $val[]=$value[$i];
                }
            }
        }            
        foreach ($val as $ke => $ve) {
            $prom_type=explode(',', $ve['prom_type']);
            $prom_id=explode(',', $ve['prom_id']);
            for ($i=0; $i < count($prom_type); $i++) { 
                    $sale[$i]=[
                        prom_type =>  $prom_type[$i],
                        prom_id   =>  $prom_id[$i],
                        ];
                        if ($sale[$i][prom_type]==3) {
                            $prom_id_s[]=$sale[$i][prom_id];
                            $prom_id_ss=array_unique($prom_id_s);
                        } 
                }
                $proms = Db::name('prom_goods')->where('id',$prom_id_ss[0])->where("type<2 and end_time>$now and start_time<$now and money<=$order_amount")->order('money desc')->find();
                $prom[]=$proms;
                $res = array('order_amount'=>$order_amount,'order_prom_id'=>0,'order_prom_amount'=>0);
            for ($l=0; $l < count($prom); $l++) { 
                if($prom[$l]){
                    if($prom[$l]['type'] == 0){
                        $res['order_amount']  = round($order_amount*$prom[$l]['expression']/100,2);//满额打折
                        $res['order_prom_amount'] = $order_amount - $res['order_amount'] ;
                        $res['order_prom_id'] = $prom[$l]['id'];
                    }elseif($prom[$l]['type'] == 1){
                        $res['order_prom_amount'] += $prom[$l]['expression'];
                        $res['order_amount'] = $order_amount- $res['order_prom_amount'];//满额优惠金额
                        $res['order_prom_id'] = $prom[$l]['id'];
                    }
                }
            }
        }
    	return $res;		
    }


/**
 * 计算订单金额
 * @param type $user_id  用户id
 * @param type $order_goods  购买的商品
 * @param type $shipping_code  物流编号
 * @param type $shipping_price 不包邮的物流费用
 * @param type $province  省份
 * @param type $city 城市
 * @param type $district 县
 * @param type $pay_points 积分
 * @param type $user_money 余额
 * @param type $coupon_id  优惠券
 * @param type $codeCode  礼品卡优惠码
 */

function calculate_price($user_id = 0, $order_goods, $shipping_code = '', $shipping_price = 0, $province = 0, $city = 0, $district = 0, $pay_points = 0, $user_money = 0, $coupon_id = 0, $codeCode = '')
{   
    $cartLogic = new ylt\home\logic\CartLogic();
    $user = Db::name('users')->where("user_id", $user_id)->find();// 找出这个用户

    if (empty($order_goods)){
        return array('status' => -9, 'msg' => '商品列表不能为空', 'result' => '');
    }

    $goods_id_arr = get_arr_column($order_goods, 'goods_id');
    $goods_arr = Db::name('goods')->where("goods_id in(" . implode(',', $goods_id_arr) . ")")->cache(true,YLT_CACHE_TIME)->column('goods_id,weight,market_price,is_free_shipping'); // 商品id 和重量对应的键值对
    foreach ($order_goods as $key => $val) {
        // 如果传递过来的商品列表没有定义会员价
        if (!array_key_exists('member_goods_price', $val)) {
            $user['discount'] = $user['discount'] ? $user['discount'] : 1; // 会员折扣 不能为 0
            $order_goods[$key]['member_goods_price'] = $val['member_goods_price'] = $val['goods_price'] * $user['discount'];
        }
        //如果商品不是包邮的
        if ($goods_arr[$val['goods_id']]['is_free_shipping'] == 0){
            $goods_weight += $goods_arr[$val['goods_id']]['weight'] * $val['goods_num']; //累积商品重量 每种商品的重量 * 数量
        }

        $order_goods[$key]['goods_fee'] = $val['goods_num'] * $val['member_goods_price'];    // 小计
        $order_goods[$key]['store_count'] = getGoodNum($val['goods_id'], $val['spec_key']); // 最多可购买的库存数量
        if ($order_goods[$key]['store_count'] <= 0)
            return array('status' => -10, 'msg' => $order_goods[$key]['goods_name'] . "库存不足,请重新下单", 'result' => '');

        $goods_price += $order_goods[$key]['goods_fee']; // 商品总价
        $cut_fee += $val['goods_num'] * $val['market_price'] - $val['goods_num'] * $val['member_goods_price']; // 共节约
        $anum += $val['goods_num']; // 购买数量
    }

        // // 领券优惠处理操作,多券使用无冲突
        // //订单页面改用用户选择优惠券提交,获取session储存的金额字段即可,此代码暂停使用
        $coupon_price = 0;
        $coupon=Db::name('coupon_list')//判断当前用户是否拥有优惠券
            ->alias('l')
            ->join('coupon c','l.cid=c.id')
            ->where('uid',$user_id)
            ->select();
        if ($coupon) {
            $a=count($coupon);      //拥有优惠券的数量
            $coupon_list_s=array();
            $goodsId_s=array();
            for ($i=0; $i < $a; $i++) {
                    $coupon_list=Db::name('coupon_list')//判断当前用户拥有的优惠券是否为购物车产品
                    ->alias('l')
                    ->join('coupon c','l.cid=c.id')
                    ->where('uid',$user_id)
                    ->where('c.type',3)
                    ->where('goods_id',$coupon[$i]['goods_id'])
                    ->select();
                    $coupon_list_s[]=$coupon_list[0];  //符合优惠券使用的产品
            $goodsId_s=array_merge(explode(',',$coupon_list_s[$i]['goods_id']),$goodsId_s);  //优惠券适用产品合并为一个数组
            }
            $couponprice=array();
            $coupon_price=0;
            foreach($order_goods as $k=>$val){
                foreach($goodsId_s as $key=>$v){
                    if($v==$val['goods_id'] ){ 
                        for ($i=0; $i < count($coupon_list_s); $i++) { 
                            if ($coupon_list_s[$i]['code']) {
                                $coupon_result = $cartLogic->getCoupon($coupon_list_s[$i]['code'], $user);
                            }
                            if (!empty($coupon_result)) {
                                    if($coupon_result['status'] != 1)
                                    return $coupon_result;
                                $couponprice[]= $coupon_result['result']['code_price'];
                                $cid[]= $coupon_result['result']['id'];
                                $couponid[]= $coupon_result['result']['coupon_id'];
                            }
                        }
                    }else{
                        $coupon_price=0;
                    }
                }
            }
            //array_unique()去除重复
            if ($cid) {     //2019.4.18更新
                for ($i=0; $i <count(array_unique($cid)) ; $i++) { 
                    $coupon_price=$couponprice[$i]+$coupon_price;
                    $coupon_Yprice[$i]['money']=$couponprice[$i];
                    $coupon_Yprice[$i]['cid']=$cid[$i];
                    $coupon_Yprice[$i]['couponid']=$couponid[$i];
                }  
            }
        }  
        //非可选择列表的旧代码结束
        
        //可选择列表新代码
        // $coupon_Yprice=session('get_coupon_id');
        // $coupon_price=session('CouponMoney') ? session('CouponMoney'):0;

  		if($codeCode && $user){
    	    $code_result = $code_result = $cartLogic->getCode($codeCode, $user);
            if($code_result['status'] != 1){return $code_result;}
            $code_price    = $code_result['result']['code_price'];
            $code_goods_id = $code_result['result']['goods_id'];
        }
        // if ($coupon_id && $user_id) {
        //    $coupon_price = $cartLogic->getCouponMoney($user_id, $coupon_id, 1); // 下拉框方式选择优惠券
        // }
        // if ($couponCode && $user_id) {
        //    $coupon_result = $cartLogic->getCouponMoneyByCode($couponCode, $goods_price); // 根据 优惠券 号码获取的优惠券
        //    if ($coupon_result['status'] < 0)
        //        return $coupon_result;
        //    $coupon_price = $coupon_result['result'];
        // }
    if ($pay_points && ($pay_points > $user['pay_points']))
        return array('status' => -5, 'msg' => "你的账户可用积分为:" . $user['pay_points'], 'result' => ''); // 返回结果状态
    if ($user_money && ($user_money > $user['user_money']))
        return array('status' => -6, 'msg' => "你的账户可用余额为:" . $user['user_money'], 'result' => ''); // 返回结果状态
    $order_amount = $goods_price + $shipping_price - $coupon_price - $code_price; // 应付金额 = 商品价格 + 物流费 - 优惠券 - 礼品卡

    $user_money = ($user_money > $order_amount) ? $order_amount : $user_money;  // 余额支付原理等同于积分
    $order_amount = $order_amount - $user_money; //  余额支付抵应付金额
    
    /*判断能否使用积分
     1..积分低于多少时,不可使用
     2.在不使用积分的情况下, 计算商品应付金额
     3.原则上, 积分支付不能超过商品应付金额的50%, 该值可在平台设置
     @{ */
    $point_rate = tpCache('shopping.point_rate'); //兑换比例: 如果拥有的积分小于该值, 不可使用
    $min_use_limit_point = tpCache('shopping.point_min_limit'); //最低使用额度: 如果拥有的积分小于该值, 不可使用
    $use_percent_point = tpCache('shopping.point_use_percent');     //最大使用限制: 最大使用积分比例, 例如: 为50时, 未50% , 那么积分支付抵扣金额不能超过应付金额的50%
    if($min_use_limit_point > 0 && $pay_points > 0 && $pay_points < $min_use_limit_point){
        return array('status'=>-1,'msg'=>"您使用的积分必须大于{$min_use_limit_point}才可以使用",'result'=>''); // 返回结果状态
    }
    // 计算该笔订单最多使用多少积分
    $limit = $order_amount * ($use_percent_point / 100) * $point_rate;
    if(($use_percent_point !=100 ) && $pay_points > $limit) {
        return array('status'=>-1,'msg'=>"该笔订单, 您使用的积分不能大于{$limit}",'result'=>'积分'); // 返回结果状态
    }
    // }
     
    $pay_points = ($pay_points / tpCache('shopping.point_rate')); // 积分支付 100 积分等于 1块钱
    $pay_points = ($pay_points > $order_amount) ? $order_amount : $pay_points; // 假设应付 1块钱 而用户输入了 200 积分 2块钱, 那么就让 $pay_points = 1块钱 等同于强制让用户输入1块钱
    $order_amount = $order_amount - $pay_points; //  积分抵消应付金额
  
    $total_amount = $goods_price + $shipping_price;
    //订单总价  应付金额  物流费  商品总价 节约金额 共多少件商品 积分  余额  优惠券
    $result = array(
        'code_goods_id' => $code_goods_id,      // 礼品卡优惠商品
        'total_amount' => $total_amount,        // 订单总价
        'order_amount' => $order_amount,        // 应付金额
        'shipping_price' => $shipping_price,    // 物流费
        'goods_price' => $goods_price,          // 商品总价
        'cut_fee' => $cut_fee,                  // 共节约多少钱
        'anum' => $anum,                        // 商品总共数量
        'integral_money' => $pay_points,        // 积分抵消金额
        'user_money' => $user_money,            // 使用余额
        'code_price' => $code_price,            // 礼品卡抵消金额
        'coupon_price' => $coupon_price,        // 优惠券抵消金额
        'order_goods' => $order_goods,          // 商品列表 多加几个字段原样返回
        'coupon_Yprice' => $coupon_Yprice,      // 用券优惠ID与金额数组
    );
    return array('status' => 1, 'msg' => "计算价钱成功", 'result' => $result); // 返回结果状态
}



/**
 * 获取商品一二三级分类
 * @return type
 */
function get_goods_category_tree(){

	$arr = $result = array();
	$cat_list = Db::name('goods_category')->where("is_show = 1 and id!=921 and id!=1010 and id!=957 and id!=43 and id!=70 and id!=1011")->order('sort_order')->cache(true)->select();//所有分类

	foreach ($cat_list as $val){
		if($val['level'] == 2){
			$arr[$val['parent_id']][] = $val;
		}
		if($val['level'] == 3){
			$crr[$val['parent_id']][] = $val;
		}
		if($val['level'] == 1){
			$tree[] = $val;
		}
	}

	foreach ($arr as $k=>$v){
		foreach ($v as $kk=>$vv){
			$arr[$k][$kk]['sub_menu'] = empty($crr[$vv['id']]) ? array() : $crr[$vv['id']];
		}
	}
	
	foreach ($tree as $val){
		$val['tmenu'] = empty($arr[$val['id']]) ? array() : $arr[$val['id']];
		$result[$val['id']] = $val;
	}
	return $result;
}

/**
 * 获取场景一二三级分类
 * @return type
 */
function get_scenario_category_tree(){
    $arr = $result = array();
    $cat_list = Db::name('scenario_category')->where("is_show = 1")->order('sort_order')->cache(true)->select();//所有分类

    foreach ($cat_list as $val){
        if($val['level'] == 2){
            $arr[$val['parent_id']][] = $val;
        }
        if($val['level'] == 3){
            $crr[$val['parent_id']][] = $val;
        }
        if($val['level'] == 1){
            $tree[] = $val;
        }
    }

    foreach ($arr as $k=>$v){
        foreach ($v as $kk=>$vv){
            $arr[$k][$kk]['sub_menu'] = empty($crr[$vv['id']]) ? array() : $crr[$vv['id']];
        }
    }

    foreach ($tree as $val){
        $val['tmenu'] = empty($arr[$val['id']]) ? array() : $arr[$val['id']];
        $result[$val['id']] = $val;
    }
    return $result;
}

/**
 * 写入静态页面缓存
 */
function write_html_cache($html){
    $html_cache_arr = config('HTML_CACHE_ARR');
    $request = think\Request::instance();
    $m_c_a_str = $request->module().'_'.$request->controller().'_'.$request->action(); // 模块_控制器_方法
    $m_c_a_str = strtolower($m_c_a_str);
    //exit('write_html_cache写入缓存<br/>');
    foreach($html_cache_arr as $key=>$val)
    {
        $val['mca'] = strtolower($val['mca']);
        if($val['mca'] != $m_c_a_str) //不是当前 模块 控制器 方法 直接跳过
            continue;
        
        if(!is_dir(RUNTIME_PATH.'html'))
                mkdir(RUNTIME_PATH.'html');
        $filename =  RUNTIME_PATH.'html'.DIRECTORY_SEPARATOR.$m_c_a_str;
        // 组合参数  
        if(isset($val['p']))
        {                    
            foreach($val['p'] as $k=>$v)        
                $filename.='_'.$_GET[$v];
        } 
        $filename.= '.html';        
        file_put_contents($filename, $html);
    }    
}

/**
 * 读取静态页面缓存
 */
function read_html_cache(){    
    $html_cache_arr = config('HTML_CACHE_ARR');
    $request = think\Request::instance();
    $m_c_a_str = $request->module().'_'.$request->controller().'_'.$request->action(); // 模块_控制器_方法
    $m_c_a_str = strtolower($m_c_a_str);
    //exit('read_html_cache读取缓存<br/>');
    foreach($html_cache_arr as $key=>$val)
    {
        $val['mca'] = strtolower($val['mca']);
        if($val['mca'] != $m_c_a_str) //不是当前 模块 控制器 方法 直接跳过
            continue;
          
        $filename =  RUNTIME_PATH.'html'.DIRECTORY_SEPARATOR.$m_c_a_str;
        // 组合参数        
        if(isset($val['p']))
        {                    
            foreach($val['p'] as $k=>$v)        
                $filename.='_'.$_GET[$v];
        } 
        $filename.= '.html';
        if(file_exists($filename))
        {
            echo file_get_contents($filename);           
            exit();           
        }
    }    
}


/**
 * 统计访问信息
 *
 * @access  public
 * @return  void
 */
function visit_stats()
{
   
    /* 检查客户端是否存在访问统计的cookie */
    $visit_times = (!empty($_COOKIE['YLT']['visit_times'])) ? intval($_COOKIE['YLT']['visit_times']) + 1 : 1;
    cookie('YLT[visit_times]', $visit_times, $time + 86400 * 30, '/');
	
	$add['access_time'] 		= time();
	$add['ip_address']  = getIP();
	$add['visit_times'] = $visit_times;
    $add['browser']  	= $_SERVER['HTTP_USER_AGENT'];
	$add['access_url'] = request()->baseUrl() ;
    

    /* 来源 */
    if (!empty($_SERVER['HTTP_REFERER']) && strlen($_SERVER['HTTP_REFERER']) > 9)
    {
        $pos = strpos($_SERVER['HTTP_REFERER'], '/', 9);
        if ($pos !== false)
        {
            $add['referer_domain'] = substr($_SERVER['HTTP_REFERER'], 0, $pos);
            $add['referer_path']   = substr($_SERVER['HTTP_REFERER'], $pos);

        }
        else
        {
            $add['referer_domain'] = $add['referer_path'] = '';
        }
    }
    else
    {
        $add['referer_domain'] = $add['referer_path'] = '';
    }
	
	Db::name('stats')->insert($add);
	
}

/**
 * 获得浏览器名称和版本
 *
 * @access  public
 * @return  string
 */
function get_user_browser()
{
    if (empty($_SERVER['HTTP_USER_AGENT']))
    {
        return '';
    }

    $agent       = $_SERVER['HTTP_USER_AGENT'];
    $browser     = '';
    $browser_ver = '';

    if (preg_match('/MSIE\s([^\s|;]+)/i', $agent, $regs))
    {
        $browser     = 'Internet Explorer';
        $browser_ver = $regs[1];
    }
    elseif (preg_match('/FireFox\/([^\s]+)/i', $agent, $regs))
    {
        $browser     = 'FireFox';
        $browser_ver = $regs[1];
    }
    elseif (preg_match('/Maxthon/i', $agent, $regs))
    {
        $browser     = '(Internet Explorer ' .$browser_ver. ') Maxthon';
        $browser_ver = '';
    }
    elseif (preg_match('/Opera[\s|\/]([^\s]+)/i', $agent, $regs))
    {
        $browser     = 'Opera';
        $browser_ver = $regs[1];
    }
    elseif (preg_match('/OmniWeb\/(v*)([^\s|;]+)/i', $agent, $regs))
    {
        $browser     = 'OmniWeb';
        $browser_ver = $regs[2];
    }
    elseif (preg_match('/Netscape([\d]*)\/([^\s]+)/i', $agent, $regs))
    {
        $browser     = 'Netscape';
        $browser_ver = $regs[2];
    }
    elseif (preg_match('/safari\/([^\s]+)/i', $agent, $regs))
    {
        $browser     = 'Safari';
        $browser_ver = $regs[1];
    }
    elseif (preg_match('/NetCaptor\s([^\s|;]+)/i', $agent, $regs))
    {
        $browser     = '(Internet Explorer ' .$browser_ver. ') NetCaptor';
        $browser_ver = $regs[1];
    }
    elseif (preg_match('/Lynx\/([^\s]+)/i', $agent, $regs))
    {
        $browser     = 'Lynx';
        $browser_ver = $regs[1];
    }

    if (!empty($browser))
    {
       return addslashes($browser . ' ' . $browser_ver);
    }
    else
    {
        return 'Unknow browser';
    }
}
//打印数组函数
function dy(Array $str)
{
    echo '<pre>';
    print_r($str);
    echo '</pre>';
}

    /**
     * [assoc_unique 去除重复二维数组,获取拥有字段条件指定字段]
     * @param  [type] $arr [数组]
     * @param  [type] $key [判定重复的键名]
     * @return [type]      [description]
     */
    function assoc_unique($arr, $key) {
        if (!empty($arr) && !empty($key)) {
            $tmp_arr = array();
            foreach ($arr as $k => $v) {
                if (in_array($v[$key], $tmp_arr)) {//搜索$v[$key]是否在$tmp_arr数组中存在，若存在返回true
                    unset($arr[$k]);
                } else {
                    $tmp_arr[] = $v[$key];
                }
            }
            sort($arr); //sort函数对数组进行排序
        }
        return $arr;
    }

    /**
     * [assoc_unique 去除重复二维数组,获取数组]
     * @param  [type] $arr [数组]
     * @return [type]      [description]
     */
    function assoc_unique_arr($arr) {
        $tmp_arr = array();
        foreach ($arr as $k => $v) {
            if (in_array($v, $tmp_arr)) {//搜索$v是否在$tmp_arr数组中存在，若存在返回true
                unset($arr[$k]);
            } else {
                $tmp_arr[] = $v;
            }
        }
        sort($arr); //sort函数对数组进行排序
        return $arr;
    }

    
    /*删除文章内容图片（也就是删除编辑器上传的图片）*/
    function remove_content_img($content){
        //匹配并删除图片
        $imgreg = "/<img.*src=\"([^\"]+)\"/U";
        $matches = array();
        preg_match_all($imgreg, $content, $matches);
        foreach($matches[1] as $img_url){
            if(strpos($img_url, 'emoticons')===false){
                $web_root = 'http://' . $_SERVER['HTTP_HOST'] . '/';
                $filepath = str_replace($web_root,'',$img_url);
                if($filepath == $img_url) $filepath = substr($img_url, 1);
                @unlink($filepath);
                $filedir  = dirname($filepath);
                @$files = scandir($filedir);
                if(count($files)<=2)@rmdir($filedir);//如果只剩下./和../,就删除文件夹
            }
        }
        unset($matches);
    }

    /**
     * [get_group_goods 红礼 获取红礼组合礼包中拥有的商品]
     * @param  [type] $orderGoods [description]
     * @return [type]             [description]
     */
    function get_group_goods($orderGoods){
        foreach ($orderGoods as $key => $value) {       //查询是否为红礼组合礼包商品
            if ($value['is_group'] !=0 and !empty($value['goods_group'])) {
                $red_goods_group[] = Db::name('red_goods')->where("goods_id in ($value[goods_group])")->field('goods_id,goods_name,shop_price,goods_thumb,red_supplier_id,red_cost_price')->select();
                foreach ($red_goods_group as $ke => $valu) {
                    $valu[$ke]['goods_num'] = $value['goods_num'];
                    $red_goods_group = $valu;
                }
            }else{
                $orderGoods_group[]=$value;
            }
        }
        if ($red_goods_group) {
            foreach ($red_goods_group as $ke => $val) {
                if ($val['red_supplier_id'] == session('red_admin_id')) {
                    $val['is_group'] = 1;
                    $val['goods_num'] = 1;
                    $orderGoods_group[] = $val;
                }
            }
        }
        return $orderGoods_group;
    }

    /**
     * [get_isgoods_category 只显示有商品的分类]
     * @param  [type] $get_category [description]
     * @param  [type] $table        [所有分类]
     * @return [type]               [查询商品的表名]
     */
    function get_isgoods_category($get_category,$table){
        //三级分类商品查询
        $goods_category = Db::name($get_category)->where('level',3)->select();
        foreach ($goods_category as $key => $value) {
            $goods_car = Db::name($table)->where(['examine'=>1,'is_delete'=>0,'cat_id'=>$value['id']])->column("goods_id");
            //查询有商品的分类,获取有商品的分类
            if ($goods_car) {
                $parent_id_path[] = explode('_',$value["parent_id_path"]);
            }
        }
        //查询所有一级及有商品的分类的二三级
        if ($parent_id_path) {
            $one = Db::name('goods_category')->where(['level'=>1,'is_show'=>1])->select();
            foreach ($parent_id_path as $key => $value) {
                $two[] = Db::name('goods_category')->where('id',$value[2])->find();
                if ($value[3]) {
                    $three[] = Db::name('goods_category')->where('id',$value[3])->find();
                }
            }
        }
        //去除查询带来的二维重复数组
        $one_arr = assoc_unique_arr($one);
        $two_arr = assoc_unique_arr($two);
        //将三级分类重新组合
        for ($i=0; $i <count($two_arr) ; $i++) { 
            for ($j=0; $j <count($three) ; $j++) { 
                if (strstr($three[$j]['parent_id_path'],$two_arr[$i]['parent_id_path'])) {
                    $two_arr[$i]['sub_menu'][]= $three[$j];
                }
            }
        }
        for ($i=0; $i <count($one_arr) ; $i++) { 
            for ($j=0; $j <count($two_arr) ; $j++) { 
                if (strstr($two_arr[$j]['parent_id_path'],$one_arr[$i]['parent_id_path'])) {
                    $one_arr[$i]['tmenu'][]= $two_arr[$j];
                }
            }
        }
        return $one_arr;
    }

    /**
     * [pdf_file PDF文档生成（样式兼容不理想）]
     * @return [type] [description]
     */
    function pdf_file($str1,$contract_num){
        vendor('TCPDF.tcpdf'); 
        //实例化 
        $pdf = new \tcpdf('P', 'mm', 'A4', true, 'UTF-8', false); 
        // 设置文档信息 
        $pdf->SetCreator('Helloweba');                  //代码来源
        $pdf->SetAuthor('chris_shier');                 //作者
        $pdf->SetTitle('Welcome to helloweba.com!');    //标题
        $pdf->SetSubject('TCPDF Tutorial');             //类型
        $pdf->SetKeywords('TCPDF, PDF, PHP');           //关键字
        // 设置页眉和页脚信息 
        $pdf->SetHeaderData('logo2.png',1,'','',array(0,64,255), array(0,64,128)); 
        $pdf->setFooterData(array(0,64,0), array(0,64,128)); 
        // 设置页眉和页脚字体 
        $pdf->setHeaderFont(Array('stsongstdlight', '', '10')); 
        $pdf->setFooterFont(Array('helvetica', '', '8')); 
        // 设置默认等宽字体 
        $pdf->SetDefaultMonospacedFont('courier'); 
        // 设置间距 
        $pdf->SetMargins(10, 10, 10); 
        $pdf->SetHeaderMargin(2); 
        $pdf->SetFooterMargin(2); 
        // 设置分页 
        $pdf->SetAutoPageBreak(TRUE, 25); 
        // set image scale factor 
        $pdf->setImageScale(1.25); 
        // set default font subsetting mode 
        $pdf->setFontSubsetting(true); 
        //设置字体 
        $pdf->SetFont('stsongstdlight', '', 14); 
        $pdf->AddPage(); 
        // $str1 = '欢迎来到Helloweba.com';            //PDF内容
        // $pdf->Write(0,$str1,'', 0, 'L', true, 0, false, false, 0); 
        $pdf->writeHTML($str1);//HTML生成PDF
        //服务器存档模式
        ob_clean();
        //PDF输出   I：在浏览器中打开，D：下载，F：在服务器生成pdf ，S：只返回pdf的字符串
        $pdf->Output($_SERVER['DOCUMENT_ROOT'].'/public/pdf/contract/'.$contract_num.'.pdf', 'F'); 

        return '/public/pdf/contract/'.$contract_num.'.pdf';
    }
    /**
     * [wkhtmltopdf PDF文档生成(宋体乱码)] 
     * @return [type] [description]
     */
    function wkhtmltopdf($str1,$filename){
        //转成pdf
        $html=$str1;
        //Turn on output buffering
        ob_start();
        $html='
        <link rel="stylesheet" href="css/common.css">
        <link rel="stylesheet" href="css/myCenter.css">
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'.$html;
        //这儿可以引入生成的Html的样式表  路径可以是绝对路径也可以是相对路径，也可以把样式表文件复制到临时html文件的目录下 即这儿的demo文件目录下（默认） 也可以直接把样式写在html页面中直接传递过来
        //$html = ob_get_contents();
        //$html=$html1.$html;

        $path ='public/pdf/contract/'.date("Ymd",time()).'/';
        if (!file_exists($path)){
            mkdir($path,0777,true);
        }//如果地址不存在，创建地址
        $picname=$path.$filename.'.html';
        $picnames=$path.$filename.'.pdf';

        //save the html page in tmp folder  保存的html临时文件位置 可以是相对路径也是可以是绝对路径 下面用相对路径
        file_put_contents("{$picname}", $html);

        //Clean the output buffer and turn off output buffering
        ob_end_clean();

        //convert HTML to PDF
        shell_exec("wkhtmltopdf -q {$picname} {$picnames}  &&  echo 'success' ");

        if(file_exists("{$picnames}")){
            header("Content-type:application/pdf");
            header("Content-Disposition:attachment;filename={$filename}.pdf");
            file_get_contents("{$picnames}");
            //echo "{$filename}.pdf";
            return  "{$picnames}";
        }else{
            return  "{$picnames}";
            exit;
        }
    }