-- phpMyAdmin SQL Dump
-- version 4.0.6
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Sep 09, 2013 at 11:39 PM
-- Server version: 5.5.32
-- PHP Version: 5.3.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `p_shared_dev`
--

-- --------------------------------------------------------

--
-- Table structure for table `application`
--

CREATE TABLE IF NOT EXISTS `application` (
  `application_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `application_nm` varchar(200) CHARACTER SET latin1 NOT NULL,
  `application_desc` varchar(1000) CHARACTER SET latin1 DEFAULT NULL,
  `subdomain` varchar(1024) CHARACTER SET latin1 DEFAULT NULL,
  `crea_usr_id` varchar(128) CHARACTER SET latin1 DEFAULT NULL,
  `crea_dtm` timestamp NULL DEFAULT NULL,
  `updt_usr_id` varchar(128) CHARACTER SET latin1 DEFAULT NULL,
  `updt_dtm` timestamp NULL DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`application_id`),
  UNIQUE KEY `application_application_nm_idx` (`application_nm`),
  KEY `application_deleted_idx` (`deleted`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT AUTO_INCREMENT=2 ;

--
-- Dumping data for table `application`
--

INSERT INTO `application` (`application_id`, `application_nm`, `application_desc`, `subdomain`, `crea_usr_id`, `crea_dtm`, `updt_usr_id`, `updt_dtm`, `deleted`) VALUES
(1, 'PHPSlickgrid', 'PHPSlickgrid Demo', 'phpslickgrid', NULL, NULL, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `user_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_nm` varchar(255) NOT NULL,
  `password` varchar(32) DEFAULT NULL,
  `user_abbr` varchar(32) NOT NULL,
  `salt` varchar(50) DEFAULT NULL,
  `user_full_nm` varchar(255) DEFAULT NULL,
  `onetimepad` varchar(200) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `crea_usr_id` varchar(128) CHARACTER SET latin1 DEFAULT NULL,
  `crea_dtm` timestamp NULL DEFAULT NULL,
  `updt_usr_id` varchar(128) CHARACTER SET latin1 DEFAULT NULL,
  `updt_dtm` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_user_nm_idx` (`user_nm`),
  UNIQUE KEY `user_user_abbr_idx` (`user_abbr`),
  KEY `user_deleted_idx` (`deleted`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT AUTO_INCREMENT=258 ;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `user_nm`, `password`, `user_abbr`, `salt`, `user_full_nm`, `onetimepad`, `last_login`, `crea_usr_id`, `crea_dtm`, `updt_usr_id`, `updt_dtm`, `deleted`) VALUES
(1, 'dummy@stormes.net', 'acd2124c54a266066f1d0b00aea4555f', 'Guest', '4cc0aa8b-1690-4072-912f-d72f403753fe', 'Guest User', '60cd2599-3710-4d96-b8f3-a2876df253ba', '0000-00-00 00:00:00', NULL, NULL, NULL, '2013-09-10 04:02:26', 0);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
