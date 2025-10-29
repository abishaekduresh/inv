-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               9.1.0 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.11.0.7065
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Dumping structure for table invoices
CREATE TABLE IF NOT EXISTS `invoices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `invoice_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `invoice_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `invoice_date` date DEFAULT NULL,
  `invoice_number` int NOT NULL,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '0',
  `phone` bigint DEFAULT '0',
  `dob` date DEFAULT NULL,
  `place` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '0',
  `frame` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '0',
  `lence` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '-',
  `r_sph` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '-',
  `r_cyl` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '-',
  `r_axis` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '-',
  `r_via` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '-',
  `r_add` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '-',
  `r_pd` char(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `l_sph` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '-',
  `l_cyl` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '-',
  `l_axis` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '-',
  `l_via` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '-',
  `l_add` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '-',
  `l_pd` char(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `amount` decimal(20,2) DEFAULT '0.00',
  `offer` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `claim` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `remark` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `payment_mode` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `invoice_status` enum('active','inactive','deleted') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` bigint NOT NULL DEFAULT (0),
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_id` (`invoice_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2053 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table req_res_logs
CREATE TABLE IF NOT EXISTS `req_res_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(100) NOT NULL,
  `method` varchar(10) NOT NULL,
  `uri` text NOT NULL,
  `ip` varchar(45) NOT NULL,
  `user_agent` text,
  `request_body` json DEFAULT NULL,
  `response_body` json DEFAULT NULL,
  `created_at` bigint NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` bigint NOT NULL,
  `role` char(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','inactive','deleted') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` bigint NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
