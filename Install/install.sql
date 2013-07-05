delimiter $$

CREATE TABLE `account` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `type` enum('cny','btc') DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_account_user1_idx` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COMMENT='账户'$$

delimiter $$

CREATE TABLE `account_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('btc','cny') NOT NULL COMMENT 'BTC or 人民币\n',
  `money` decimal(20,8) NOT NULL COMMENT '进账或入账\n用正负表示',
  `account_id` int(10) unsigned NOT NULL,
  `remain` decimal(20,8) NOT NULL COMMENT '余额',
  `is_affect` tinyint(4) NOT NULL COMMENT '是否生效',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `affected` timestamp NULL DEFAULT NULL COMMENT '生效时间',
  PRIMARY KEY (`id`),
  KEY `fk_account_log_account1_idx` (`account_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COMMENT='账户记录 充值/买入/卖出'$$

delimiter $$

CREATE TABLE `config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(45) NOT NULL,
  `value` varchar(45) NOT NULL,
  `remark` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='配置表，需要做缓存'$$

delimiter $$

CREATE TABLE `trade` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('sell','buy') NOT NULL COMMENT '是卖单还是买单\n',
  `user_id` int(10) unsigned NOT NULL,
  `price` decimal(10,2) unsigned NOT NULL COMMENT '单价',
  `quantity` decimal(16,8) unsigned NOT NULL COMMENT '数量BTC',
  `is_trade` tinyint(3) unsigned NOT NULL COMMENT '是否已经卖出',
  `is_cancel` tinyint(3) unsigned NOT NULL COMMENT '是否已经取消',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建单子的时间',
  `traded` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_to_trade_user_idx` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='挂单，成交记录'$$

delimiter $$

CREATE TABLE `user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(45) NOT NULL,
  `password` char(128) NOT NULL,
  `money_pass` char(128) DEFAULT NULL,
  `email` varchar(105) DEFAULT NULL,
  `role` enum('user','admin') NOT NULL COMMENT '用户角色：\n普通用户\n管理员\n',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip` char(18) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8 COMMENT='用户'$$

