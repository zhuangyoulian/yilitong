# -----------------------------------------------------------
# Description:备份的数据表[结构]：ylt_account_log
# 表的结构 ylt_account_log 
DROP TABLE IF EXISTS `ylt_account_log`;
CREATE TABLE `ylt_account_log` (
  `log_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '日志id',
  `user_id` mediumint(8) unsigned NOT NULL COMMENT '用户id',
  `user_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '用户金额',
  `frozen_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '冻结金额',
  `pay_points` mediumint(9) NOT NULL DEFAULT '0' COMMENT '支付积分',
  `change_time` int(10) unsigned NOT NULL COMMENT '变动时间',
  `desc` varchar(255) NOT NULL COMMENT '描述',
  `order_sn` varchar(50) DEFAULT NULL COMMENT '订单编号',
  `order_id` int(10) DEFAULT NULL COMMENT '订单id',
  `allow_time` int(10) DEFAULT '0' COMMENT '允许提到余额时间',
  `cash_time` int(10) DEFAULT '0' COMMENT '现金到账时间',
  `status` tinyint(1) DEFAULT '0' COMMENT '是否已提现',
  `sign_status` tinyint(1) DEFAULT '0' COMMENT '0未签收 1已签收',
  PRIMARY KEY (`log_id`),
  KEY `user_id` (`user_id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=167 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC ;

