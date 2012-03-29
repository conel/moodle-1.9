/*
SQLyog Community Edition- MySQL GUI v8.12 
MySQL - 5.1.36-community : Database - moodle
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

CREATE DATABASE /*!32312 IF NOT EXISTS*/`moodle` /*!40100 DEFAULT CHARACTER SET utf8 */;

USE `moodle`;

/*Table structure for table `mdl_terms` */

DROP TABLE IF EXISTS `mdl_terms`;

CREATE TABLE `mdl_terms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `term_code` varchar(1) DEFAULT NULL,
  `ac_year_code` varchar(4) DEFAULT NULL,
  `term_name` varchar(30) DEFAULT NULL,
  `term_start_date` bigint(10) unsigned NOT NULL,
  `term_end_date` bigint(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;

/*Data for the table `mdl_terms` */

insert  into `mdl_terms`(`id`,`term_code`,`ac_year_code`,`term_name`,`term_start_date`,`term_end_date`) values (1,'1','1011','Term 1',1283731200,1292198400),(2,'2','1011','Term 2',1294012800,1301875200),(3,'3','1011','Term 3',1303689600,1310342400),(4,'1','0910','Term 1',1252195200,1260662400),(5,'2','0910','Term 2',1262476800,1270339200),(6,'3','0910','Term 3',1272153600,1278806400),(7,'1','0809','Term 1',1220659200,1229126400),(8,'2','0809','Term 2',1230940800,1238803200),(9,'3','0809','Term 3',1240617600,1247270400);

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
