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
-- Database: `p_slickgrid_dev`
--

-- --------------------------------------------------------

--
-- Table structure for table `log`
--

CREATE TABLE IF NOT EXISTS `log` (
  `log_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `message` text NOT NULL,
  `priority` int(11) NOT NULL,
  `timestamp` datetime NOT NULL,
  `priorityName` varchar(255) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `request_uri` varchar(1024) NOT NULL,
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=8 ;

--
-- Dumping data for table `log`
--

INSERT INTO `log` (`log_id`, `message`, `priority`, `timestamp`, `priorityName`, `user_id`, `request_uri`) VALUES
(1, 'this is a info log test', 6, '2013-09-12 23:08:40', 'INFO', 0, '/'),
(2, 'this is a notice log test', 5, '2013-09-12 23:08:40', 'NOTICE', 0, '/'),
(3, 'this is a warn log test', 4, '2013-09-12 23:08:40', 'WARN', 0, '/'),
(4, 'this is a err log test', 3, '2013-09-12 23:08:40', 'ERR', 0, '/'),
(5, 'this is a crit log test', 2, '2013-09-12 23:08:40', 'CRIT', 0, '/'),
(6, 'this is a alert log test', 1, '2013-09-12 23:08:40', 'ALERT', 0, '/'),
(7, 'this is a emerg log test', 0, '2013-09-12 23:08:40', 'EMERG', 0, '/');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
