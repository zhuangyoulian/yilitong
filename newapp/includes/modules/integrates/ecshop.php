<?php

/**
 * ECSHOP 会员数据处理类
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com
 * ----------------------------------------------------------------------------
 * 这是一个免费开源的软件；这意味着您可以在不用于商业目的的前提下对程序代码
 * 进行修改、使用和再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: ecshop.php 17217 2011-01-19 06:29:08Z liubo $
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

/* 模块的基本信息 */
if (isset($set_modules) && $set_modules == TRUE)
{
    $i = (isset($modules)) ? count($modules) : 0;

    /* 会员数据整合插件的代码必须和文件名保持一致 */
    $modules[$i]['code']    = 'ecshop';

    /* 被整合的第三方程序的名称 */
    $modules[$i]['name']    = 'ECSHOP';

    /* 被整合的第三方程序的版本 */
    $modules[$i]['version'] = '2.0';

    /* 插件的作者 */
    $modules[$i]['author']  = 'ECSHOP R&D TEAM';

    /* 插件作者的官方网站 */
    $modules[$i]['website'] = 'http://www.ecshop.com';

    return;
}

require_once(ROOT_PATH . 'includes/modules/integrates/integrate.php');
class ecshop extends integrate
{
    var $is_ecshop = 1;

    function __construct($cfg)
    {
        $this->ecshop($cfg);
    }

    /**
     *
     *
     * @access  public
     * @param
     *
     * @return void
     */
    function ecshop($cfg)
    {
        parent::integrate(array());
        $this->user_table = 'users';
        $this->field_id = 'user_id';
        $this->ec_salt = 'ec_salt';
        $this->field_name = 'user_name';
        $this->field_pass = 'password';
        $this->field_email = 'email';
        $this->field_gender = 'sex';
        $this->field_bday = 'birthday';
        $this->field_reg_date = 'reg_time';
        $this->need_sync = false;
        $this->is_ecshop = 1;
    }


    /**
     *  检查指定用户是否存在及密码是否正确(重载基类check_user函数，支持zc加密方法)
     *
     * @access  public
     * @param   string  $username   用户名
     *
     * @return  int
     */
    function check_user($username, $password = null)
    {
        if ($this->charset != 'UTF8')
        {
            $post_username = ecs_iconv('UTF8', $this->charset, $username);
        }
        else
        {
            $post_username = $username;
        }

        if ($password === null)
        {
            $sql = "SELECT " . $this->field_id .
                   " FROM " . $this->table($this->user_table).
                   " WHERE " . $this->field_name . "='" . $post_username . "'";

            return $this->db->getOne($sql);
        }
        else
        {
            $sql = "SELECT user_id, password, salt,ec_salt,reg_time " .
                   " FROM " . $this->table($this->user_table).
                   " WHERE user_name='$post_username'";

            $row = $this->db->getRow($sql);
            
            if (empty($row))
            {
            	//return 0;
            	return $this->other_check($username, $password);
            }
            
			$ec_salt=$row['ec_salt'];
			$reg_time = $row['reg_time'];
            

            if (empty($row['salt']))
            {
                
            	if ($row['password'] != $this->compile_password(array('password'=>$password,'ec_salt'=>$ec_salt,'user_name'=>$post_username,'reg_time'=>$reg_time)))
                {
                    return 0;
                    //用户名唯一，使用用户名方式验证密码不成功，再次使用手机和email方式进行验证（email和手机在数据库中非唯一）
                	//return $this->other_check($username, $password);
                	
                    
                }
                else
                {
					/* if(empty($ec_salt))
				    {
						$ec_salt=rand(1,9999);
						$new_password=md5(md5($password).$ec_salt);
					    $sql = "UPDATE ".$this->table($this->user_table)."SET password= '" .$new_password."',ec_salt='".$ec_salt."'".
                   " WHERE user_name='$post_username'";
                         $this->db->query($sql);

					} */
                    return $row['user_id'];
                }
            }
            else
            {
                /* 如果salt存在，使用salt方式加密验证，验证通过洗白用户密码 */
                $encrypt_type = substr($row['salt'], 0, 1);
                $encrypt_salt = substr($row['salt'], 1);

                /* 计算加密后密码 */
                $encrypt_password = '';
                switch ($encrypt_type)
                {
                    case ENCRYPT_ZC :
                        $encrypt_password = md5($encrypt_salt.$password);
                        break;
                    /* 如果还有其他加密方式添加到这里  */
                    //case other :
                    //  ----------------------------------
                    //  break;
                    case ENCRYPT_UC :
                        $encrypt_password = md5(md5($password).$encrypt_salt);
                        break;

                    default:
                        $encrypt_password = '';

                }

                if ($row['password'] != $encrypt_password)
                {
                    return 0;
                }

                $sql = "UPDATE " . $this->table($this->user_table) .
                       " SET password = '".  $this->compile_password(array('password'=>$password)) . "', salt=''".
                       " WHERE user_id = '$row[user_id]'";
                $this->db->query($sql);

                return $row['user_id'];
            }
        }
    }
    
    
    public function other_check($post_username, $password = null){
    	/**********验证手机登录**************/
    	$sql = "SELECT user_id, password, salt,ec_salt,reg_time,user_name " .
    			" FROM " . $this->table($this->user_table).
    			" WHERE mobile_phone='$post_username'";
 
    	$row = $this->db->getAll($sql);
    	foreach ($row as $r){
    		if ($r['password'] == $this->compile_password(array('password'=>$password,'ec_salt'=>$r['ec_salt'],'user_name'=>$r['user_name'],'reg_time'=>$r['reg_time']))){
    			return $r['user_id'];
    			break;
    			
    		}
    	}
    	/**********验证手机登录**************/
    	  
    	/**********验证email登录**************/
    	$sql = "SELECT user_id, password, salt,ec_salt,reg_time,user_name " .
    			" FROM " . $this->table($this->user_table).
    			" WHERE email='$post_username'";
    	
    	$row = $this->db->getAll($sql);
    	foreach ($row as $r){
    		if ($r['password'] == $this->compile_password(array('password'=>$password,'ec_salt'=>$r['ec_salt'],'user_name'=>$r['user_name'],'reg_time'=>$r['reg_time']))){
    			return $r['user_id'];
    			break;
    			
    		}
    	}
    	 
    	return 0;
    	/**********验证email登录**************/
    }


}

?>