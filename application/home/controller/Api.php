<?php
namespace ylt\home\controller;
use ylt\home\logic\UsersLogic;
use think\Session;
use think\Controller;
use think\Verify;
use think\Db;
use think\Request;

class Api extends Controller {
    public  $send_scene;
    
    public function _initialize() {
        Session::start();
    }
    /*
     * 获取地区
     */
    public function getRegion(){
        $parent_id = I('get.parent_id/d');
        $selected = I('get.selected',0);
        $data = Db::name('region')->where("parent_id",$parent_id)->select();
        $html = '';
        if($data){
            foreach($data as $h){
                if($h['id'] == $selected){
                    $html .= "<option value='{$h['id']}' selected>{$h['name']}</option>";
                }
                $html .= "<option value='{$h['id']}'>{$h['name']}</option>";
            }
        }
        echo $html;
    }
    

    public function getTwon(){
        $parent_id = I('get.parent_id/d');
        $data = Db::name('region')->where("parent_id",$parent_id)->select();
        $html = '';
        if($data){
            foreach($data as $h){
                $html .= "<option value='{$h['id']}'>{$h['name']}</option>";
            }
        }
        if(empty($html)){
            echo '0';
        }else{
            echo $html;
        }
    }
    
    /*
     * 获取商品分类
     */
    public function get_category(){
        $parent_id = I('get.parent_id/d'); // 商品分类 父id
        $list = Db::name('goods_category')->where("parent_id", $parent_id)->select();
        
        foreach($list as $k => $v)
            $html .= "<option value='{$v['id']}'>{$v['name']}</option>";        
        exit($html);
    } 

    /*
     * 获取合同模板分类
     */
    public function get_classify(){
        $parent_id = I('get.parent_id/d'); // 商品分类 父id
        $list = Db::name('contract_template')->where("p_id", $parent_id)->select();
        foreach($list as $k => $v)
            $html .= "<option value='{$v['id']}'>{$v['name']}</option>";        
        exit($html);
    } 

    /*
     * 获取一创商品分类
     */
    public function agen_get_category(){
        $parent_id = I('get.parent_id/d'); // 商品分类 父id
        $list = Db::name('agen_goods_category')->where("parent_id", $parent_id)->select();
        foreach($list as $k => $v)
            $html .= "<option value='{$v['id']}'>{$v['name']}</option>";        
        exit($html);
    } 
    /*
     * 获取红礼商品分类
     */
    public function red_get_category(){
        $parent_id = I('get.parent_id/d'); // 商品分类 父id
        $list = Db::name('red_goods_category')->where("parent_id", $parent_id)->select();
        foreach($list as $k => $v)
            $html .= "<option value='{$v['id']}'>{$v['name']}</option>";        
        exit($html);
    } 
    /*
     * 获取充值卡分类
     */
    public function refill_class(){
        $parent_id = I('get.parent_id/d'); // 商品分类 父id
        $list = Db::name('top_config')->where("parent_id", $parent_id)->select();
        foreach($list as $k => $v)
            $html .= "<option value='{$v['id']}'>{$v['name']}</option>";        
        exit($html);
    }
    
     /*
     * 获取场景分类
     */
    public function get_scenario_category(){
        $parent_id = I('get.parent_id/d'); // 场景分类 父id
        $list = Db::name('scenario_category')->where("parent_id", $parent_id)->select();

        foreach($list as $k => $v)
            $html .= "<option value='{$v['id']}'>{$v['name']}</option>";
        exit($html);
    }
    
     /*
     * 获取商品分类
     */
    public function get_categorys(){
        $parent_id = I('get.parent_id/d'); // 商品分类 父id
        $supplier_id = I('get.supplier_id/d'); // 商品分类 父id
            $list = Db::name('supplier_goods_category')->where(array('parent_id'=>$parent_id,'supplier_id'=>$supplier_id))->select();
        
        foreach($list as $k => $v)
            $html .= "<option value='{$v['id']}'>{$v['name']}</option>";        
        exit($html);
    }
    
    
    /**
     * 前端发送短信方法: APP/WAP/PC 共用发送方法
     */
    public function send_validate_code(){
         
        $this->send_scene = config('SEND_SCENE');
        
        $type = I('type') ? I('type') : 1;
        $scene = I('scene');    //发送短信验证码使用场景 1为注册 2为登录
        $mobile = input('mobile');
        $sender = I('send');
        $mobile = !empty($mobile) ?  $mobile : $sender ;
        $session_id = I('unique_id' , session_id());

        $verify_code = trim(input('verify_code'));
        // if(!check_mobile($mobile)){
        //     return json(array('status'=>-1,'msg'=>'手机号码格式有误'));
        // }
        $url = $_SERVER['HTTP_REFERER'];
        //图像验证码
        if ($scene == 2) {  
            $scene_code = 'user_login';
        }else{
            $scene_code = 'user_reg';
        }

        if($type != '5'){
            $verify = new Verify();
            if (!$verify->check($verify_code,$scene_code))
            {  
                return json(array('status'=>-12,'msg'=>'图形验证码错误'));exit;
            }
        }

        switch ($type) {
            case 'email':
                break;
            case '1':
                if(Db::name('users')->where('mobile',$mobile)->value('user_id'))
                    return json(array('status'=>-1,'msg'=>'发送失败，用户已注册'));
                break;
            case '2':
                if(Db::name('supplier_user')->where('mobile',$mobile)->value('admin_id'))
                    return json(array('status'=>-2,'msg'=>'发送失败，用户已注册'));
                break;
            case '3':
                if(!Db::name('users')->where('mobile',$mobile)->value('user_id'))
                    return json(array('status'=>-3,'msg'=>'发送失败，该手机号未绑定或未注册'));
                break;
            case '4':
                if(!Db::name('supplier_user')->where('mobile',$mobile)->value('admin_id'))
                    return json(array('status'=>-4,'msg'=>'发送失败，非法来源'));
                break;
            case '5':
                break;
            case '6':
                $user = session('user');
                if (!$user['user_id']) {
                    return json(array('status'=>-5,'msg'=>'发送失败，非平台用户来源'));
                }
                break;
            case '7':
                if(Db::name('redsupplier_user')->where('mobile',$mobile)->value('red_admin_id'))
                    return json(array('status'=>-2,'msg'=>'发送失败，用户已注册'));
                break;
            default:
                    return json(array('status'=>-6,'msg'=>'发送失败，非法来源'));
                break;
        }

        //判断是否存在验证码
        $data = Db::name('sms_record')->where('mobile',$mobile)->order('id DESC')->find();
        $sms_time_out =  60;
        //90秒以内不可重复发送
        if($data && (time() - $data['add_time']) < $sms_time_out){
            $return_arr = array('status'=>-7,'msg'=>$sms_time_out.'秒内不允许重复发送');
            return json($return_arr);
            exit;
        }
        //黑名单
        if($data['status'] == 3){
            return json(array('status'=>-8,'msg'=>'此号码已被列入黑单，无法执行此操作'));
            exit;
        }

        // 一天只能发送6次验证码
        if($data && (strtotime(date('Y-m-d', $data['add_time'])) == strtotime(date('Y-m-d', time())) ) ){
            $today_num = $data['today_num'] + 1;
            if($today_num > 5){
                if($today_num > 15){
                    Db::name('sms_record')->where('mobile',$mobile)->update(['status'=>3]);  //黑名单
                }
                return json(array('status'=>-9,'msg'=>'验证码类每天只能发送5次'));
                exit;
            }elseif($data['add_time'] > (time() - 3600) && $today_num > 3){
                Db::name('sms_record')->where('mobile',$mobile)->update(['today_num'=>$today_num]);
                return json(array('status'=>-10,'msg'=>'验证码类一小时内不能超过3次，请稍后再试'));
                exit;
            }
        }else{
            $today_num = 1;
        }
        // 删除之前的验证码
         Db::name('sms_record')->where("mobile",$mobile)->delete();
                
        //随机一个验证码
        $code =  rand(1000,9999);
        $content = "您的验证码是".$code."(请尽快完成验证)。如非本人操作，请忽略本短信";
        Db::name('sms_record')->insert(array('mobile'=>$mobile,'code'=>$code,'add_time'=>time(),'session_id'=>$session_id , 'status' => 0,'today_num'=>$today_num));


        //发送短信
        $resp = sendCode($mobile , $content);
        if($resp['count'] == 1){
            //发送成功, 修改发送状态位成功
            Db::name('sms_record')->where('mobile',$mobile)->update(array('status' => 1,'today_num'=>$today_num));
            $return_arr = array('status'=>1,'msg'=>'发送成功,请注意查收');
            return json($return_arr);
        }else{
            //发送失败, 修改发送状态
           
            $return_arr = array('status'=>-11,'msg'=>'发送失败'.$resp['msg']);
            return json($return_arr);
        }
        
    }

    /* 
        报价时的短信验证
    */
    public function send_quote_validate_code(){
         
        $this->send_scene = config('SEND_SCENE');
        
        $type = I('type') ? I('type') : 1;
        $scene = I('scene');    //发送短信验证码使用场景
        $mobile = input('mobile');
        $sender = I('send');
        $mobile = !empty($mobile) ?  $mobile : $sender ;
        $session_id = I('unique_id' , session_id());
        
        $verify_code = trim(input('get.verify_code'));
            
        if(!check_mobile($mobile))
            return json(array('status'=>-1,'msg'=>'手机号码格式有误'));

        $url = $_SERVER['HTTP_REFERER'];
        if(!strpos($url,'yilitong.com')){ //验证短信来源
           return json(array('status'=>-1,'msg'=>'发送失败，非法操作'));
        }

        //图像验证码
        if($type != '3'){
            $verify = new Verify();
            if (!$verify->check($verify_code,'user_reg'))
            {  
              $this->error('图形验证码错误');exit;
            }
        }
        //判断是否存在验证码
        $data = Db::name('sms_record')->where('mobile',$mobile)->order('id DESC')->find();
        $sms_time_out =  60;
        //90秒以内不可重复发送
        if($data && (time() - $data['add_time']) < $sms_time_out){
            $return_arr = array('status'=>-1,'msg'=>$sms_time_out.'秒内不允许重复发送');
            return json($return_arr);
            exit;
        }
        //黑名单
        if($data['status'] == 3){
            return json(array('status'=>-1,'msg'=>'此号码已被列入黑单，无法执行此操作'));
            exit;
        }

        // 删除之前的验证码
         Db::name('sms_record')->where("mobile",$mobile)->delete();
                
        //随机一个验证码
        $today_num = 1;//默认参数
        $today_num++;
        $code =  rand(1000,9999);
        $content = "您的验证码是".$code."(请尽快完成验证)。如非本人操作，请忽略本短信";
        Db::name('sms_record')->insert(array('mobile'=>$mobile,'code'=>$code,'add_time'=>time(),'session_id'=>$session_id , 'status' => 0,'today_num'=>$today_num));
        
        //发送短信
        $resp = sendCode($mobile , $content);
        if($resp['count'] == 1){
            //发送成功, 修改发送状态位成功
            Db::name('sms_record')->where('mobile',$mobile)->update(array('status' => 1,'today_num'=>$today_num));
            $return_arr = array('status'=>1,'msg'=>'发送成功,请注意查收');
            return json($return_arr);
        }else{
            //发送失败, 修改发送状态
           
            $return_arr = array('status'=>-1,'msg'=>'发送失败'.$resp['msg']);
            return json($return_arr);
        }
        
    }
  
    
    /* 
        商户找回的短信验证
    */
    public function send_business_validate_code(){
         
        $this->send_scene = config('SEND_SCENE');
        $type = I('type') ? I('type') : 1;
        $scene = I('scene');    //发送短信验证码使用场景
        $mobile = input('mobile');
        $sender = I('send');
        $mobile = !empty($mobile) ?  $mobile : $sender ;
        $session_id = I('unique_id' , session_id());
        
        $verify_code = trim(input('get.verify_code'));
            
        if(!check_mobile($mobile))
            return json(array('status'=>-1,'msg'=>'手机号码格式有误'));

        $url = $_SERVER['HTTP_REFERER'];
        if(!strpos($url,'yilitong.com')){ //验证短信来源
           return json(array('status'=>-1,'msg'=>'发送失败，非法操作'));
        }
        /*
        //图像验证码
        if($type != '3'){
            $verify = new Verify();
            if (!$verify->check($verify_code,'forget'))
            {  
              $this->error('图形验证码错误');exit;
            }
        }
        */
        //判断是否存在验证码
        $data = Db::name('sms_record')->where('mobile',$mobile)->order('id DESC')->find();
        $sms_time_out =  60;
        //90秒以内不可重复发送
        if($data && (time() - $data['add_time']) < $sms_time_out){
            $return_arr = array('status'=>-1,'msg'=>$sms_time_out.'秒内不允许重复发送');
            return json($return_arr);
            exit;
        }
        //黑名单
        if($data['status'] == 3){
            return json(array('status'=>-1,'msg'=>'此号码已被列入黑单，无法执行此操作'));
            exit;
        }

        // 删除之前的验证码
         Db::name('sms_record')->where("mobile",$mobile)->delete();
                
        //随机一个验证码
        $today_num = 1;//默认参数
        $today_num++;
        $code =  rand(1000,9999);
        $content = "您的验证码是".$code."(请尽快完成验证)。如非本人操作，请忽略本短信";
        Db::name('sms_record')->insert(array('mobile'=>$mobile,'code'=>$code,'add_time'=>time(),'session_id'=>$session_id , 'status' => 0,'today_num'=>$today_num));
        
        //发送短信
        $resp = sendCode($mobile , $content);
        if($resp['count'] == 1){
            //发送成功, 修改发送状态位成功
            Db::name('sms_record')->where('mobile',$mobile)->update(array('status' => 1,'today_num'=>$today_num));
            $return_arr = array('status'=>1,'msg'=>'发送成功,请注意查收');
            return json($return_arr);
        }else{
            //发送失败, 修改发送状态
           
            $return_arr = array('status'=>-1,'msg'=>'发送失败'.$resp['msg']);
            return json($return_arr);
        }
        
    }
  
  
    /**
     * 验证短信验证码: APP/WAP/PC 共用发送方法
     */
    public function check_validate_code(){

        $code = I('post.code');
        $mobile = I('mobile');
        $send = I('send');
        $sender = empty($mobile) ? $send : $mobile;
        $type = I('type');
        $session_id = I('unique_id', session_id());
        $scene = I('scene', -1);
        $logic = new UsersLogic();
        $res = $logic->check_validate_code($code, $sender, $type ,$session_id, $scene);
        return json($res);

    }
    
        /**
     * 获取省
     */
    public function getProvince()
    {
        $province = Db::name('region')->field('id,name')->where(array('level' => 1))->cache(true)->select();
        $res = array('status' => 1, 'msg' => '获取成功', 'result' => $province);
        exit(json_encode($res));
    }

    /**
     * 获取市或者区
     */
    public function getRegionByParentId()
    {
        $parent_id = input('parent_id');
        $res = array('status' => 0, 'msg' => '获取失败，参数错误', 'result' => '');
        if($parent_id){
            $region_list = Db::name('region')->field('id,name')->where(['parent_id'=>$parent_id])->select();
            $res = array('status' => 1, 'msg' => '获取成功', 'result' => $region_list);
        }
        exit(json_encode($res));
    }
    

    /**
     * 检测手机号是否已经存在
     */
    public function issetMobile()
    {
      $mobile = input("mobile",'0');
      $users = Db::name('users')->where('mobile',$mobile)->find();
      if($users)
          exit ('1');
      else 
          exit ('0');      
    }


    /**
     * 查询物流
     */
    public function queryExpress()
    {
        $shipping_code = I('shipping_code');
        $invoice_no = I('invoice_no');
        if(empty($shipping_code) || empty($invoice_no)){
            return json(['status'=>0,'message'=>'参数有误','result'=>'']);
        }
        return json(queryExpress($shipping_code,$invoice_no));
    }

    public function test(){
        $scene = session("scene");
        echo ' scene : '.$scene;
    }
    
    /**
     * 检查订单状态
     */
    public function check_order_pay_status()
    {
        $order_id = I('order_id');
        if(empty($order_id)){
            $res = ['message'=>'参数错误','status'=>-1,'result'=>''];
           // $this->AjaxReturn($res);
             exit(json_encode($res));
        }
        $order = Db::name('order')->field('pay_status')->where(['order_id'=>$order_id])->find();
        if($order['pay_status'] != 0){
            $res = ['message'=>'已支付','status'=>1,'result'=>$order];
        }else{
            $res = ['message'=>'未支付','status'=>0,'result'=>$order];
        }
        //$this->AjaxReturn($res);
         exit(json_encode($res));
    }

    /**
     * 广告位js
     */
    public function ad_show()
    {
        $pid = I('pid',1);
        $where = array(
            'pid'=>$pid,
            'enable'=>1,
            'start_time'=>array('lt',strtotime(date('Y-m-d H:00:00'))),
            'end_time'=>array('gt',strtotime(date('Y-m-d H:00:00'))),
        );
        $ad = Db::name("ad")->where($where)->order("orderby desc")->cache(true,YLT_CACHE_TIME)->find();
        $this->assign('ad',$ad);
        $this->display();
    }
    
    
    /**
     * 接收快递回调
     */
       public function receive_shipping(){
           $param=$_POST['param'];
           

            $param=stripslashes($param);

            $de_json = json_decode($param,TRUE);
            $invoice_no = $de_json['lastResult']['nu'];


            try{
                //$param包含了文档指定的信息，...这里保存的快递信息,$param的格式与订阅时指定的格式一致

                Db::name('shipping_order')->where("invoice_no", $invoice_no)->update(array('logistics_information'=>$param));
                echo  '{"result":"true",    "returnCode":"200","message":"成功"}';
                //要返回成功（格式与订阅时指定的格式一致），不返回成功就代表失败，没有这个30分钟以后会重推
            } catch(Exception $e)
                {
                echo  '{"result":"false",   "returnCode":"500","message":"失败"}';
                //保存失败，返回失败信息，30分钟以后会重推
            } 
    }
    
    /**
     * 商品详情页面点击数量
     */
    function click_count($goods_id){

     Db::name('Goods')->where("goods_id", $goods_id)->setInc('click_count'); //统计点击数
    
    }

    /**
     * 清除商品静态页面
     *@param type 商品来源 0pc ,1移动
     */
    function goods_cleanCache($goods_id,$type=0){

        if($type == 1){
            if(unlink("./runtime/html/mobile_Goods_goodsInfo_{$goods_id}.html"))
            {
                // 删除静态文件
                $html_arr = glob("./runtime/html/mobile_goods*.html");
                foreach ($html_arr as $key => $val)
                {
                    strstr($val,"mobile_goods_ajax_consult_{$goods_id}") && unlink($val); // 商品咨询缓存
                    strstr($val,"mobile_goods_ajaxComment_{$goods_id}") && unlink($val); // 商品评论缓存
                }
            }
            
        }else{
            if(unlink("./runtime/html/home_goods_goodsinfo_{$goods_id}.html"))
            {
                // 删除静态文件
                $html_arr = glob("./runtime/html/home_goods*.html");
                foreach ($html_arr as $key => $val)
                {
                    strstr($val,"home_goods_ajax_consult_{$goods_id}") && unlink($val); // 商品咨询缓存
                    strstr($val,"home_goods_ajaxComment_{$goods_id}") && unlink($val); // 商品评论缓存
                }
            }       
        }
        
        
    }

  /*
   * BUG 数据记录
   */
    public function bug_log(){

        $id = trim(input('id'));
        if($id){
             $log = Db::name('bug_log')->where('id',$id)->find();
             $date = unserialize($log['date']);
             echo date('y-m-d h:i',$log['add_time']);
        }
        $add['add_time'] = time();
        $add['date'] = serialize(I('post.'));
        $add['url'] = input('url');
        Db::name('bug_log')->insert($add);
    }
    
    
   /**
     * 清空系统缓存
     */
     public function cleanCache(){              
                              
            $code = input('code');
            if($code == 'admin'){
                delFile(RUNTIME_PATH);
                echo '<h1>清除成功！</h1>';
                exit;
            }else{
                echo '<h1>非法请求！</h1>';
                exit;
            }
                              

            return $this->fetch();
        }
        
        
  /*
   * 退款 数据记录
   */
    public function refund_log(){

        $id = trim(input('id'));
        $code = input('code');
        if($id && $code == 'admin'){
             $log = Db::name('refund_log')->where('id',$id)->find();
             $date = unserialize($log['log']);
             echo date('y-m-d h:i',$log['add_time']);
             echo "&nbsp;order_sn:".$log['order_sn'];
        }
    

    }
    
    
        /**
     * 验证码获取
     */
    public function verify()
    {
        
        ob_end_clean();
        //验证码类型
        $type = I('get.type') ? I('get.type') : 'user_reg';
        $config = array(
            'fontSize' => 40,
            'length' => 4,
            'useCurve' => false,
            'useNoise' => true,
        );
        $Verify = new Verify($config);
        $Verify->entry($type);
    }

}