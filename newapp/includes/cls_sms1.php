<?php
if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

//define('USERID', 1183);
define('ACCOUNT', 'yilitong');
define('PASSWORD', 'lizhi88');
//define('SMSSIGN', '【洋货栈】');
define('URL', 'http://sms.106jiekou.com/utf8/sms.aspx');

/* 短信模块主类 */
class sms1
{
   
    /**
     * 存放MYSQL对象
     *
     * @access  private
     * @var     object      $db
     */
    var $db         = null;

    /**
     * 存放ECS对象
     *
     * @access  private
     * @var     object      $ecs
     */
    var $ecs        = null;


    /**
     * 构造函数
     *
     * @access  public
     * @return  void
     */
    function __construct()
    {
        $this->sms1();
    }

    /**
     * 构造函数
     *
     * @access  public
     * @return  void
     */
    function sms1()
    {
        /* 由于要包含init.php，所以这两个对象一定是存在的，因此直接赋值 */
        $this->db = $GLOBALS['db'];
        $this->ecs = $GLOBALS['ecs'];
    }
   
    /**
     * 检测手机号码是否正确
     *
     */
    function is_moblie($moblie)
    {
       return  preg_match("/^0?1((3|8|7)[0-9]|5[0-35-9]|4[57])\d{8}$/", $moblie);
    }
    
    function http_curl($url,$post_data){
    	$ch = curl_init();
    	curl_setopt($ch, CURLOPT_POST, 1);
    	curl_setopt($ch, CURLOPT_HEADER, 0);
    	curl_setopt($ch, CURLOPT_URL,$url);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //如果需要将结果直接返回到变量里，那加上这句。
    	$result = curl_exec($ch);
    	//echo $result;
    	
    	return $result;
    }
    
    function send($mobile,$content,$sendtime=''){
    	
    	/* $today = date("Y-m-d 00:00:00");
    	$today_unix_time = strtotime($today);
    	$sql = "select count(1) total from ylt_sms_log where mobile='$mobile' and send_at>=$today_unix_time group by mobile";
    	$total = $this->db->getOne($sql);
    	if($total>=5){
    		return true;
    		exit;
    	} */
    	$target = "http://sms.106jiekou.com/utf8/sms.aspx";
    	//替换成自己的测试账号,参数顺序和wenservice对应
    	$post_data = "account=yilitong&password=lizhi88&mobile=".$mobile."&content=".rawurlencode("您的验证码是：".$content."。请不要把验证码泄露给其他人。如非本人操作，可不用理会！");
    	
    	$PHPcode['result_send'] = $this->Post($post_data, $target);
    	$PHPcode['result_code']='ok';
    	$PHPcode['result_msg']=$_SESSION["telcode"];
		if($PHPcode['result_code']=='ok'){
			//$now = time();
			//$this->db->query("insert into ylt_sms_record (mobile,add_time,code) values('$mobile','$now','{$content}')");
			return true;
		}else{
			return false;
		}
    	
    }

    /**
     * 保存验证码验证log
     * @param  array $param
     * @return
     */
    public function save_verify_log($param){
        $data['app_name']   = $param['app_name'];
        $data['toke']       = $param['toke'];
        $data['type']       = $param['type'];
        $data['addtime']    = time();
        $data['ip']         = $_SERVER['REMOTE_ADDR'];
        $data['get_header'] = $_SERVER['HTTP_USER_AGENT'];
        $param = array_filter($param);
        if(empty($param)){
            return false;
        }else{
            $tmp = array();
            foreach($data as $field=>$val){
                $tmp[] = '`'.$field."` ='".$val."'";
            }
            $ins = implode(',', $tmp);
            if(empty($ins)){
                return false;
            }
            switch ($param['type']) {
                case '1':
                    //sms
                    $where = time()-60*20;
                    break;
                case '2':
                    //jpg
                    $where = time()-60;
                    break;
                default:
                    break;
            }
            if(empty($where)){
                return false;
            }
            $sql = "delete from ".$GLOBALS['ecs']->table('app_vcode')." where `type`='".$data['type']."' and addtime<".$where;
            $GLOBALS['db']->query($sql);

            $sql = "insert into ".$GLOBALS['ecs']->table('app_vcode')." set ".$ins;
            return $GLOBALS['db']->query($sql);
        }
    }
    
  public  function Post($curlPost,$url){
    	$curl = curl_init();
    	curl_setopt($curl, CURLOPT_URL, $url);
    	curl_setopt($curl, CURLOPT_HEADER, false);
    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    	curl_setopt($curl, CURLOPT_NOBODY, true);
    	curl_setopt($curl, CURLOPT_POST, true);
    	curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);
    	$return_str = curl_exec($curl);
    	curl_close($curl);
    	return $return_str;
    }
    
    
    
    function sendSMS($mobile,$content,$code = '')
    {
    	
    	$today = date("Y-m-d 00:00:00");
    	$today_unix_time = strtotime($today);
    	$sql = "select count(1) total from ylt_sms_log where mobile='$mobile' and add_time>=$today_unix_time group by mobile";
    	$total = $this->db->getOne($sql);
    	if($total>=5){
    		return array('code'=>'-1');
    		exit;
    	}
    	
    	$http = "https://sms.yunpian.com/v2/sms/single_send.json";
    	$apikey = "4e3a9e9908418e1c9026c47e63979d69";
    
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
    
    	$json_data = $this->send_url($ch,$data,$http);
    	$array = json_decode($json_data,true);
    	if($array['code']=='0'){
    		$now = time();
    		$this->db->query("insert into ylt_sms_log (mobile,add_time,code,status,verification_code) values('$mobile','$now','$content','0','$code')");
    	}
    	return $array;
    }
    
    function send_url($ch,$data,$http){
    	curl_setopt ($ch, CURLOPT_URL, $http);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    	return curl_exec($ch);
    }
	
	
    
    function send_Verification($mobile,$code){
		
		$sql = "select * from ylt_sms_log where mobile='$mobile' and verification_code = '$code'";
    	$smsInfo = $this->db->getRow($sql);
		
		if($smsInfo){
			
			if($smsInfo['status'] == '1')
				return array('status'=>'-1','info'=>'短信验证码已使用');
			
			$time = time();
			$add_time = ($smsInfo['add_time'] + 600);
			if($time > $add_time)
				return array('status'=>'-1','info'=>'短信验证码已过期');
			
			$this->db->query("UPDATE `ylt_sms_log` SET `status`='1' WHERE (mobile='$mobile' and verification_code = '$code')");
			return array('status'=>'1','info'=>'验证成功');
			
		}else{
			
			return array('status'=>'-1','info'=>'短信验证码不正确');
    		exit;
			
		}
		
    }
    
    
}

?>