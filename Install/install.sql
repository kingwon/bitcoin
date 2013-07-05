SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

CREATE SCHEMA IF NOT EXISTS `mydb` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
USE `mydb` ;

-- -----------------------------------------------------
-- Table `mydb`.`account`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `mydb`.`account` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `user_id` INT UNSIGNED NOT NULL ,
  `btc` DECIMAL(20,8) NOT NULL COMMENT 'BTC 余额？？' ,
  `cny` DECIMAL(14,2) NOT NULL COMMENT '人民币 余额？？' ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_account_user1_idx` (`user_id` ASC) ,
  CONSTRAINT `fk_account_user1`
    FOREIGN KEY (`user_id` )
    REFERENCES `mydb`.`user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = '账户';


-- -----------------------------------------------------
-- Table `mydb`.`user`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `mydb`.`user` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(45) NOT NULL ,
  `password` VARCHAR(45) NOT NULL ,
  `role` VARCHAR(45) NOT NULL ,
  `account_id` INT UNSIGNED NOT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `name_UNIQUE` (`name` ASC) ,
  INDEX `fk_user_account1_idx` (`account_id` ASC) ,
  CONSTRAINT `fk_user_account1`
    FOREIGN KEY (`account_id` )
    REFERENCES `mydb`.`account` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = '用户';


-- -----------------------------------------------------
-- Table `mydb`.`post`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `mydb`.`post` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `type` TINYINT NOT NULL COMMENT '是卖单还是买单\n  1  2' ,
  `user_id` INT UNSIGNED NOT NULL ,
  `price` DECIMAL(10,2) UNSIGNED NOT NULL COMMENT '单价' ,
  `quantity` DECIMAL(16,8) UNSIGNED NOT NULL COMMENT '数量BTC' ,
  `is_trade` TINYINT UNSIGNED NOT NULL COMMENT '是否已经卖出' ,
  `is_cancel` TINYINT UNSIGNED NOT NULL COMMENT '是否已经取消' ,
  `created` TIMESTAMP NOT NULL COMMENT '创建单子的时间' ,
  `traded` TIMESTAMP NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_to_trade_user_idx` (`user_id` ASC) ,
  CONSTRAINT `fk_to_trade_user`
    FOREIGN KEY (`user_id` )
    REFERENCES `mydb`.`user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = '挂单，成交记录';


-- -----------------------------------------------------
-- Table `mydb`.`config`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `mydb`.`config` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `key` VARCHAR(45) NOT NULL ,
  `value` VARCHAR(45) NOT NULL ,
  `remark` VARCHAR(45) NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
COMMENT = '配置表，需要做缓存';


-- -----------------------------------------------------
-- Table `mydb`.`account_log`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `mydb`.`account_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `type` TINYINT NOT NULL COMMENT 'BTC or 人民币\n1 2' ,
  `money` DECIMAL(20,8) NOT NULL COMMENT '进账或入账\n用正负表示' ,
  `account_id` INT UNSIGNED NOT NULL ,
  `remain` DECIMAL(20,8) NOT NULL COMMENT '余额' ,
  `is_affect` TINYINT NOT NULL COMMENT '是否生效' ,
  `created` TIMESTAMP NOT NULL COMMENT '创建时间' ,
  `affected` TIMESTAMP NULL COMMENT '生效时间' ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_account_log_account1_idx` (`account_id` ASC) ,
  CONSTRAINT `fk_account_log_account1`
    FOREIGN KEY (`account_id` )
    REFERENCES `mydb`.`account` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = '账户记录 充值/买入/卖出';

USE `mydb` ;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
