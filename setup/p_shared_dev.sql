-- phpMyAdmin SQL Dump
-- version 4.0.6
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Sep 13, 2013 at 12:12 AM
-- Server version: 5.5.33
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
-- Table structure for table `app`
--

CREATE TABLE IF NOT EXISTS `app` (
  `app_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Internal used for applicaitons.  Must match application_id in the *.ini file.',
  `app_nm` varchar(200) NOT NULL COMMENT 'Application Name used for navigation menus.  Should match the application_name in the *.ini file.',
  `app_sub_domain` varchar(1024) NOT NULL COMMENT 'Subdomain used for generating the link to the applicaiton.  This is prepended to the current base domain name.',
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`app_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='Contains a list of all apps and their subdomin''s.' AUTO_INCREMENT=2 ;

--
-- Dumping data for table `app`
--

INSERT INTO `app` (`app_id`, `app_nm`, `app_sub_domain`, `deleted`) VALUES
(1, 'PHPSlickgrid', 'phpslickgrid', 0);

-- --------------------------------------------------------

--
-- Table structure for table `role`
--

CREATE TABLE IF NOT EXISTS `role` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Internal Id used for roles.',
  `parent_role_id` int(11) DEFAULT NULL COMMENT 'Parent role of this role.  Used to represent the  hierarchy.',
  `app_id` int(11) NOT NULL COMMENT 'Application ID this role belongs to.',
  `role_nm` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Name of the role, must match names from role in *.ini.',
  PRIMARY KEY (`role_id`),
  KEY `app_id` (`app_id`),
  KEY `parent_role_id` (`parent_role_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Contins role  hierarchies and maps role_id to name.' AUTO_INCREMENT=2 ;

--
-- Dumping data for table `role`
--

INSERT INTO `role` (`role_id`, `parent_role_id`, `app_id`, `role_nm`) VALUES
(1, NULL, 1, 'view');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Internal ID for the user.',
  `user_nm` varchar(255) NOT NULL COMMENT 'User''s login name.  Currently must be a vlid email address.',
  `password` varchar(32) DEFAULT NULL COMMENT 'Salted MD5 of the password text MD5(password_text+salt)',
  `salt` varchar(50) DEFAULT NULL COMMENT 'Random string used to salt the password.',
  `pad` varchar(50) DEFAULT NULL COMMENT 'Random string used for cookies and password resets web links.',
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_user_nm_idx` (`user_nm`),
  KEY `user_deleted_idx` (`deleted`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='Contains all information required to log in a user.' AUTO_INCREMENT=2 ;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `user_nm`, `password`, `salt`, `pad`, `deleted`) VALUES
(1, 'guest@stormes.net', 'd11f8dd287b6444c9e15b4e376c2a438', 'e43e1c62-8953-48f3-830e-d6ee80631e1b', '1c69917c-b966-40d4-9435-18907a671ed0', 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_app_role`
--

CREATE TABLE IF NOT EXISTS `user_app_role` (
  `user_app_role_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique ID for mapping.',
  `user_id` int(11) NOT NULL COMMENT 'User ID from user table.',
  `app_id` int(11) NOT NULL COMMENT 'Application ID from app table.',
  `role_id` int(11) NOT NULL COMMENT 'Role Id from role table.',
  PRIMARY KEY (`user_app_role_id`),
  KEY `user_id` (`user_id`),
  KEY `role_id` (`role_id`),
  KEY `app_id` (`app_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Maps the user to applications and roles' AUTO_INCREMENT=2 ;

--
-- Dumping data for table `user_app_role`
--

INSERT INTO `user_app_role` (`user_app_role_id`, `user_id`, `app_id`, `role_id`) VALUES
(1, 1, 1, 1);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `role`
--
ALTER TABLE `role`
  ADD CONSTRAINT `role_ibfk_1` FOREIGN KEY (`parent_role_id`) REFERENCES `role` (`role_id`),
  ADD CONSTRAINT `role_ibfk_2` FOREIGN KEY (`app_id`) REFERENCES `app` (`app_id`);

--
-- Constraints for table `user_app_role`
--
ALTER TABLE `user_app_role`
  ADD CONSTRAINT `user_app_role_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`),
  ADD CONSTRAINT `user_app_role_ibfk_2` FOREIGN KEY (`app_id`) REFERENCES `app` (`app_id`),
  ADD CONSTRAINT `user_app_role_ibfk_3` FOREIGN KEY (`role_id`) REFERENCES `role` (`role_id`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
