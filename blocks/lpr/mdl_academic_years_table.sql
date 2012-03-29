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

/*Table structure for table `mdl_academic_years` */

DROP TABLE IF EXISTS `mdl_academic_years`;

CREATE TABLE `mdl_academic_years` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ac_year_code` varchar(4) DEFAULT NULL,
  `ac_year_name` varchar(30) DEFAULT NULL,
  `ac_year_start_date` bigint(10) unsigned NOT NULL,
  `ac_year_end_date` bigint(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

/*Data for the table `mdl_academic_years` */

insert  into `mdl_academic_years`(`id`,`ac_year_code`,`ac_year_name`,`ac_year_start_date`,`ac_year_end_date`) values (1,'0809','Academic Year 2008/2009',1220659200,1247270400),(2,'0910','Academic Year 2009/2010',1252195200,1278806400),(3,'1011','Academic Year 2010/2011',1283731200,1310342400);

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
