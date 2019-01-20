CREATE TABLE `example` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增主键',
  `canonical_country_code` char(8) NOT NULL DEFAULT '' COMMENT '订单的国家码',
  `utc_offset` int(11) NOT NULL DEFAULT '0' COMMENT '订单城市的时区偏移',
  `is_del` tinyint(4) NOT NULL DEFAULT '0' COMMENT '作弊状态',
  `_create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `_modify_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
  PRIMARY KEY (`id`),
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COMMENT='样例表';

