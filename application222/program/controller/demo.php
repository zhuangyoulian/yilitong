<?php

include_once "wxBizDataCrypt.php";


$appid = 'wx4f4bc4dec97d474b';
$sessionKey = 'tiihtNczf5v6AKRyjwEUhQ==';

$encryptedData="CiyLU1Aw2KjvrjMdj8YKliAjtP4gsMZM
                QmRzooG2xrDcvSnxIMXFufNstNGTyaGS
                9uT5geRa0W4oTOb1WT7fJlAC+oNPdbB+
                3hVbJSRgv+4lGOETKUQz6OYStslQ142d
                NCuabNPGBzlooOmB231qMM85d2/fV6Ch
                evvXvQP8Hkue1poOFtnEtpyxVLW1zAo6
                /1Xx1COxFvrc2d7UL/lmHInNlxuacJXw
                u0fjpXfz/YqYzBIBzD6WUfTIF9GRHpOn
                /Hz7saL8xz+W//FRAUid1OksQaQx4CMs
                8LOddcQhULW4ucetDf96JcR3g0gfRK4P
                C7E/r7Z6xNrXd2UIeorGj5Ef7b1pJAYB
                6Y5anaHqZ9J6nKEBvB4DnNLIVWSgARns
                /8wR2SiRS7MNACwTyrGvt9ts8p12PKFd
                lqYTopNHR1Vf7XjfhQlVsAJdNiKdYmYV
                oKlaRv85IfVunYzO0IKXsyl7JCUjCpoG
                20f0a04COwfneQAGGwd5oa+T8yO5hzuy
                Db/XcxxmK01EpqOyuxINew==";

$iv = 'r7BXXKkLb8qrSNn05n0qiA==';

$pc = new WXBizDataCrypt($appid, $sessionKey);
$errCode = $pc->decryptData($encryptedData, $iv, $data );

if ($errCode == 0) {
    print($data . "\n");
} else {
    print($errCode . "\n");
}


   /**
   * api接口开发
   * 获取详情的接口
   * @param $uid 用户编号
   * @param $iv 向量
   * @param $encryptedData 微信加密的数据
   * @param $rawData 判断是否为今天
   * @param $signature 签名
   * @return array
   * code ：微信登陆接口返回的登陆凭证，用户获取session_key
     iv ：  微信小程序登陆接口返回的向量，用于数据解密
    encryptedData : 微信获取用户信息接口的返回的用户加密数据，用于后端的接口解析
    signature : 加密数据
   */
  public static function authorization($appid,$appsecret,$code,$iv,$encryptedData,$rawData,$signature){
    $result = self::decodeWxData($appid,$appsecret,$code,$iv,$encryptedData);
    if($result['errcode'] != 200){
      return $result;
    }
    //处理微信授权的逻辑
    $wxUserData = $result['data'];
    error_log("authorization data=============>");
    error_log(json_encode($wxUserData));
    $uid = WxaUserService::regWxaUser($wxUserData);
    $data['uid'] = $uid['uid'];
    $data['unionid'] = $uid['unionid'];
    $result['data'] = $data;
    return $result;
  }
   
  /**
   * 解密微信的数据
   * @param $code wx.login接口返回的code
   * @param $iv wx.getUserInfo接口或者wx.getWeRunData返回的iv
   * @param $encryptedData wx.getUserInfo接口或者wx.getWeRunData返回的加密数据
   * @return array
   */
  public static function decodeWxData($appid,$appsecret,$code,$iv,$encryptedData){
    $sessionKeyUrl = sprintf('%s?appid=%s&secret=%s&js_code=%s&grant_type=authorization_code',config('param.wxa_user_info_session_key_url'),$appid,$appsecret,$code);
    $rtnJson = curlRequest($sessionKeyUrl);
    $data = json_decode($rtnJson,true);
    error_log('authorization wx return data========>');
    error_log($rtnJson);
    if(isset($data['errcode'])){
      return $data;
    }
    $sessionKey = $data['session_key'];
    $wxHelper = new WxBizDataHelper($appid,$sessionKey,$encryptedData,$iv);
    $data['errcode'] = 200;
    $data['data'] = [];
    if(!$wxData = $wxHelper->getData()){
      $data['errcode'] = -1;
    }else{
      error_log('current wx return data is =========>'.json_encode($wxData));
      $data['data'] = $wxData;
    }
    return $data;
  }

  /**
  * 保存用户信息的方法
  * @param $wxaUserData
  * @param $regFromGh 表示是否从公众号进行注册
  */
 public function regWxaUser($wxaUserData,$regFromGh = false)
 {
   $value = $wxaUserData['unionId'];
   $key = getCacheKey('redis_key.cache_key.zset_list.lock') . $value;
   $newExpire = RedisHelper::getLock($key);
   $data = $this->storeWxaUser($wxaUserData,$regFromGh);
   RedisHelper::releaseLock($key, $newExpire);
   return $data;
 }
  
 /**
  * 保存信息
  * @param $wxaUserData
  * @return mixed
  */
 public function storeWxaUser($wxaUserData,$regFromGh = false)
 {
   $wxUnionId = $wxaUserData['unionId'];
   if (!$user = $this->getByWxUnionId($wxUnionId)) {
     $getAccountDataStartTime = time();
     //这里是因为需要统一账户获取uid，所以这个是用户中心的接口，如果没有这个流程，则直接使用数据
     if($accountData = AccountCenterHelper::regWxaUser($wxaUserData)){
       $getAccountDataEndTime = time();
       $accountRegTime = $getAccountDataEndTime - $getAccountDataStartTime;
       error_log("reg user spend time is ===================>" . $accountRegTime);
       $user = [
         'uid' => $accountData['uid'],
         'user_name' => $accountData['user_name'],
         'nick_name' => $wxaUserData['nickName'],
         'sex' => $accountData['sex'],
         'wx_union_id' => $accountData['wx_union_id'],
         'avatar' => isset($accountData['avatar'])?$accountData['avatar']:"",
         'from_appid' => $accountData['from_appid'],
         'province' => $wxaUserData['province'],
         'city' => $wxaUserData['city'],
         'country' => $wxaUserData['country'],
         'openid' => $wxaUserData['openId'],
         'wx_header' => isset($wxaUserData['avatarUrl'])?$wxaUserData['avatarUrl']:"",
         'gh_openid' => $regFromGh?$wxaUserData['openId']:"",
       ];
       error_log("insert data=============>" . json_encode($user));
       $user = $this->store($user);
       $regApiUserEndTime = time();
       error_log(" reg api user spend time================>" . ($regApiUserEndTime - $getAccountDataEndTime));
       error_log(" after insert data=============>" . json_encode($user));
     }
   }else{
     if(!$user['wx_header']){
       $updateData = [
         'id' => $user['id'],
         'uid' => $user['uid'],
         'wx_header' => $wxaUserData['avatarUrl'],
       ];
       $this->update($updateData);
     }
     //同步用户的openid
     if($wxaUserData['openId'] != $user['openid']){
       $updateData = [
         'id' => $user['id'],
         'uid' => $user['uid'],
         'openid' => $wxaUserData['openId'],
       ];
       $this->update($updateData);
     }
   }
   $data['uid'] = $user['uid'];
   $data['unionid'] = $wxUnionId;
   return $data;
 }

 /**
 * 根据unionid获取用户信息
 */
public function getByWxUnionId($unionId)
{
  $cacheKey = getCacheKey('redis_key.cache_key.wxa_user.info') . $unionId;
  $value = $this->remember($cacheKey, function () use ($unionId) {
    $userInfo = WxaUser::where('wx_union_id', $unionId)->first();
    $userInfo = $this->compactUserInfo($userInfo);
    return $userInfo;
  });
  return $value;
}