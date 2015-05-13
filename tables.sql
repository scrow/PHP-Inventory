# ************************************************************
# Sequel Pro SQL dump
# Version 4096
#
# http://www.sequelpro.com/
# http://code.google.com/p/sequel-pro/
#
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table attachments
# ------------------------------------------------------------

DROP TABLE IF EXISTS `attachments`;

CREATE TABLE `attachments` (
  `id` bigint(11) unsigned NOT NULL AUTO_INCREMENT,
  `item` bigint(11) DEFAULT NULL,
  `isImg` tinyint(1) DEFAULT NULL,
  `imgType` int(11) DEFAULT NULL,
  `imgWidth` int(11) DEFAULT NULL,
  `imgHeight` int(11) DEFAULT NULL,
  `mime` varchar(255) DEFAULT NULL,
  `sha1` varchar(40) DEFAULT '',
  `originalExt` varchar(6) DEFAULT NULL,
  `shortName` varchar(64) DEFAULT NULL,
  `notes` mediumtext,
  `hasThumb` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table groups
# ------------------------------------------------------------

DROP TABLE IF EXISTS `groups`;

CREATE TABLE `groups` (
  `id` bigint(11) unsigned NOT NULL AUTO_INCREMENT,
  `shortName` varchar(64) DEFAULT NULL,
  `notes` mediumtext,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table items
# ------------------------------------------------------------

DROP TABLE IF EXISTS `items`;

CREATE TABLE `items` (
  `id` bigint(11) unsigned NOT NULL AUTO_INCREMENT,
  `shortName` varchar(64) DEFAULT NULL,
  `make` varchar(32) DEFAULT NULL,
  `model` varchar(32) DEFAULT NULL,
  `serial` varchar(32) DEFAULT NULL,
  `upc` varchar(13) DEFAULT NULL,
  `purchaseDate` date DEFAULT NULL,
  `purchasePrice` float DEFAULT NULL,
  `warrantyExp` date DEFAULT NULL,
  `replacementValue` float DEFAULT NULL,
  `saleValue` float DEFAULT NULL,
  `valueDate` date DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `amazonASIN` varchar(10) DEFAULT NULL,
  `notes` mediumtext,
  `location` bigint(11) DEFAULT NULL,
  `group` bigint(11) DEFAULT NULL,
  `receiptImg` bigint(20) DEFAULT NULL,
  `itemImg` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table locations
# ------------------------------------------------------------

DROP TABLE IF EXISTS `locations`;

CREATE TABLE `locations` (
  `id` bigint(11) unsigned NOT NULL AUTO_INCREMENT,
  `shortName` varchar(64) DEFAULT NULL,
  `notes` mediumtext,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;




/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
