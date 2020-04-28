<?php
use think\Model; 
use think\Request;
use think\Db;
class weixin extends Model{
	//回调地址
	public $return_url;
	public $app_id;
	public $app_secret;
	public function __construct($config){
		
		if ($_SERVER["REQUEST_URI"]="/home/LoginApi/login/oauth/weixin.html") {
			$this->return_url = "http://".$_SERVER['HTTP_HOST']."/index.php/Home/LoginApi/callback/oauth/weixin";	
		}else{
			$this->return_url = "http://".$_SERVER['HTTP_HOST']."/index.php/Mobile/LoginApi/callback/oauth/weixin";	
		}
      
		 //$wx_user = Db::name('wx_user')->find();
		 //$this->app_id = $wx_user['appid'];			 //wx218ea80c35624c8a
		 //$this->app_secret = $wx_user['appsecret'];	 //77380763d58d20f6bbcb18d469b40f03
		 $this->app_id = $config['app_id'];		 //wxff94c9ef025ccb79
		 $this->app_secret = $config['app_secret'];//08cb16a4467dd7a4c4af53507cc27a42

	}
	//构造要请求的参数数组，无需改动
	public function login(){
		 
		$_SESSION['state'] = md5(uniqid(rand(), TRUE));
		//session('state',$_SESSION['state']);
		//拼接URL
		$dialog_url = 'https://open.weixin.qq.com/connect/qrconnect?appid='. $this->app_id . '&redirect_uri='.urlencode($this->return_url).'&response_type=code&scope=snsapi_login&state='.$_SESSION['state'];

      	// dump($dialog_url);die;
  		header("refresh:0;url={$dialog_url}"); 
		//echo("<script> top.location.href='" . $dialog_url . "'</script>");
		exit;
	}

	public function respon(){
		if($_REQUEST['state'] == $_SESSION['state'])
		{
			$code = $_REQUEST["code"];
			//拼接URL
			
			$token_url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='
		. $this->app_id . '&secret='. $this->app_secret .'&code='. $code.'&grant_type=authorization_code';
   

			$response = $this->get_contents($token_url);
			
			$user = json_decode($response,true);
			if (strpos($response, "callback") !== false)
			{
				$lpos = strpos($response, "(");
				$rpos = strrpos($response, ")");
				$response  = substr($response, $lpos + 1, $rpos - $lpos -1);
				$user = json_decode($response); 
				if (isset($user->error))
				{
					echo "<h3>error:</h3>" . $user->errcode;
					echo "<h3>msg  :</h3>" . $user->errmsg;
					exit;
				}
			}
			
			if($user['expires_in'] != '7200'){
				    echo "<h3>error:</h3>" . $user['errcode'];
					echo "<h3>msg  :</h3>" . $user['errmsg'];
					exit;
			}
			
			//获取到openid
			$openid = $user['unionid']; //APP 与通用
			$unionid = $user['unionid']; //APP 与通用
			$_SESSION['state'] = null; // 验证SESSION
			return array(
				'openid'=>$openid,//openid
			//	'unionid'=>$unionid,
				'oauth'=>'weixin',
				'nickname'=>'weixin用户',
			);
		}else{
			//echo $_SESSION['state'];exit;
			return false;
		}
	}


	public function get_contents($url){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_URL, $url);
		$response =  curl_exec($ch);
		curl_close($ch);

		//-------请求为空
		if(empty($response)){
			exit("50001");
		}

		return $response;
	}

}


?>
