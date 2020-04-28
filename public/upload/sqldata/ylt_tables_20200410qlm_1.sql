# -----------------------------------------------------------
# Description:备份的数据表[结构]：ylt_activity_goods
# 表的结构 ylt_activity_goods 
DROP TABLE IF EXISTS `ylt_activity_goods`;
CREATE TABLE `ylt_activity_goods` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `goods_id` int(10) DEFAULT '0' COMMENT '商品id',
  `act_id` int(10) DEFAULT '0' COMMENT '活动id',
  `addtime` int(11) DEFAULT '0' COMMENT '时间搓',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=516 DEFAULT CHARSET=utf8 ;

