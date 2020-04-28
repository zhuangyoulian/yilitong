<?php
class  Push{
	 function  sendJpush($message,$type,$area,$badge='',$url_ad=''){
	 	require 'config.php';
		$response="";
		if($type == 1){
			//type=1 跳转到首页，type=2商品详情页，type=3活动详情页
			$other = array(
					"badge" => "{$badge}",
					"url_ad" => "",
					"type" =>"{$type}",
					"goods_id"=>"",
			);
		}elseif($type == 2){
			$other = array(
					"badge" => "{$badge}",
					"type" =>"{$type}",
					"goods_id"=>"{$url_ad}",
					"url_ad"=>""
			);
		}elseif($type == 3){
			$other = array(
					"badge" => "{$badge}",
					"type" =>"{$type}",
					"goods_id"=>"",
					"url_ad"=>"{$url_ad}"
				);
		}
		
		if($area=="all"){
			$response = $client->push()
			->setPlatform('all')
			->addAllAudience()
			->setNotificationAlert("{$message}")
			->message('{$message}', array(
					'title' => "{$message}",
					'extras' =>$other
			))
			->send();
		}else{
			$response = $client->push()
			->setPlatform(array('ios', 'android'))
			->addAlias("{$area}")
			->setNotificationAlert("{$message}")
			->iosNotification("{$message}", array(
					'sound' => '',
					'category' => 'jiguang',
					'extras' =>$other,
			))
			->androidNotification("{$message}", array(
					'title' => "{$message}",
					'extras' =>$other,
			))
			->message("{$message}", array(
					'title' => "{$message}",
					'extras' =>$other,
			))
			->options(array('apns_production' => true,))
			->send();
		}
		return $response['http_code'];
	}
}

