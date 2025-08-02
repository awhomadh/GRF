--
-- Database: `requisition_db`
--
-- This script will drop the database if it already exists to ensure a clean setup.
-- Be cautious running this on a database that contains important data.
--

DROP DATABASE IF EXISTS `requisition_db`;

CREATE DATABASE IF NOT EXISTS `requisition_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `requisition_db`;

-- --------------------------------------------------------

--
-- Table structure for table `requisitions`
--
-- This table stores the main information for each requisition form.
--

CREATE TABLE `requisitions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `office_name` VARCHAR(255) NOT NULL,
  `section_unit` VARCHAR(255) NOT NULL,
  `grf_number` VARCHAR(100) NOT NULL,
  `requisition_date` DATE NOT NULL,
  `requested_by` VARCHAR(255) NOT NULL,
  `requested_by_signature` TEXT NOT NULL,
  `requested_by_date` DATE NOT NULL,
  `authorised_by` VARCHAR(255) NOT NULL,
  `authorised_by_signature` TEXT NOT NULL,
  `authorised_by_date` DATE NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `grf_number_unique` (`grf_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `requisition_items`
--
-- This table stores the individual items associated with a requisition.
-- It has a foreign key relationship to the `requisitions` table.
--

CREATE TABLE `requisition_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `requisition_id` INT UNSIGNED NOT NULL,
  `quantity` INT NOT NULL,
  `issued_qty` INT NOT NULL DEFAULT 0,
  `description` TEXT NOT NULL,
  `request_date` DATE DEFAULT NULL,
  `received_by` VARCHAR(255) DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_requisition_id` (`requisition_id`),
  CONSTRAINT `fk_items_to_requisition`
    FOREIGN KEY (`requisition_id`)
    REFERENCES `requisitions` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
