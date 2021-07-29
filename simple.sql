/*
MySQL Data Transfer
Source Host: localhost
Source Database: simple
Target Host: localhost
Target Database: simple
Date: 29/07/2021 17:06:40
*/

SET FOREIGN_KEY_CHECKS=0;
-- ----------------------------
-- Table structure for pengguna
-- ----------------------------
DROP TABLE IF EXISTS `pengguna`;
CREATE TABLE `pengguna` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usernama` varchar(75) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `pass` varchar(5) DEFAULT NULL,
  `token_app` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records 
-- ----------------------------
