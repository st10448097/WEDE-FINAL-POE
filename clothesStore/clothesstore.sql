-- phpMyAdmin SQL Dump
-- version 4.9.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jun 18, 2026 at 05:16 PM
-- Server version: 10.4.10-MariaDB
-- PHP Version: 7.3.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `clothesstore`
--

-- --------------------------------------------------------

--
-- Table structure for table `address`
--

DROP TABLE IF EXISTS `address`;
CREATE TABLE IF NOT EXISTS `address` (
  `address_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `street` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`address_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
CREATE TABLE IF NOT EXISTS `admin` (
  `admin_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `name`, `email`, `password`, `created_at`) VALUES
(6, 'Admin User', 'admin@example.com', '0192023a7bbd73250516f069df18b500', '2026-05-27 21:51:27');

-- --------------------------------------------------------

--
-- Table structure for table `clothing`
--

DROP TABLE IF EXISTS `clothing`;
CREATE TABLE IF NOT EXISTS `clothing` (
  `clothing_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `category` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `description` text DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`clothing_id`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `clothing`
--

INSERT INTO `clothing` (`clothing_id`, `name`, `category`, `price`, `stock`, `description`, `image_url`, `created_at`) VALUES
(4, 'Blazer', 'women', '499.99', 3, 'Pink Blazer', '', '2026-06-18 16:52:44'),
(5, 'Skirt', 'women', '179.99', 1, 'Scotch skirt', 'uploads/seller_items/1781802119_6a342487dc43e.png', '2026-06-18 17:03:01'),
(6, 'Shoes', 'kids', '349.99', 1, 'pink nike foams', 'uploads/seller_items/1781802066_6a3424520059b.png', '2026-06-18 17:03:03'),
(7, 'Shirt', 'men', '149.99', 1, 'Brand new Mr Price shirt, worn once', 'uploads/seller_items/1781802027_6a34242bdda4d.png', '2026-06-18 17:03:05'),
(8, 'Pants', 'men', '199.99', 1, 'Blue Replay Jeans', 'uploads/seller_items/1781801963_6a3423eb3f972.png', '2026-06-18 17:03:07'),
(9, 'Jacket', 'kids', '599.99', 1, 'Black nike tech jacket', 'uploads/seller_items/1781801920_6a3423c02d4c4.png', '2026-06-18 17:03:09'),
(10, 'Hoodie', 'women', '249.99', 1, 'Pink Hoodie from truworths', 'uploads/seller_items/1781801860_6a342384af97e.png', '2026-06-18 17:03:10'),
(11, 'Handbag', 'women', '299.99', 1, 'Pink Handbag', 'uploads/seller_items/1781801806_6a34234e7280c.png', '2026-06-18 17:03:12');

-- --------------------------------------------------------

--
-- Table structure for table `message`
--

DROP TABLE IF EXISTS `message`;
CREATE TABLE IF NOT EXISTS `message` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message_text` text DEFAULT NULL,
  `time_sent` datetime DEFAULT NULL,
  `is_read` int(11) DEFAULT 0,
  PRIMARY KEY (`message_id`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `message`
--

INSERT INTO `message` (`message_id`, `sender_id`, `receiver_id`, `message_text`, `time_sent`, `is_read`) VALUES
(1, 9, 1, 'dfsfsdfsd', '2026-06-04 07:19:18', 0),
(2, 11, 1, 'kujushdkjhadad', '2026-06-17 22:13:56', 0),
(3, 6, 11, 'might reject', '2026-06-17 22:15:36', 0),
(4, 6, 11, 'Your request to sell \'shirt\' has been APPROVED! The item has been added to our store.', '2026-06-17 22:15:45', 0),
(5, 6, 11, 'Your request to sell \'Skirt\' has been APPROVED! The item has been added to our store.', '2026-06-18 19:03:01', 0),
(6, 6, 11, 'Your request to sell \'Shoes\' has been APPROVED! The item has been added to our store.', '2026-06-18 19:03:03', 0),
(7, 6, 11, 'Your request to sell \'Shirt\' has been APPROVED! The item has been added to our store.', '2026-06-18 19:03:05', 0),
(8, 6, 11, 'Your request to sell \'Pants\' has been APPROVED! The item has been added to our store.', '2026-06-18 19:03:07', 0),
(9, 6, 11, 'Your request to sell \'Jacket\' has been APPROVED! The item has been added to our store.', '2026-06-18 19:03:09', 0),
(10, 6, 11, 'Your request to sell \'Hoodie\' has been APPROVED! The item has been added to our store.', '2026-06-18 19:03:10', 0),
(11, 6, 11, 'Your request to sell \'Handbag\' has been APPROVED! The item has been added to our store.', '2026-06-18 19:03:12', 0),
(12, 6, 11, 'Your Blazer will not be approved', '2026-06-18 19:03:44', 0);

-- --------------------------------------------------------

--
-- Table structure for table `order`
--

DROP TABLE IF EXISTS `order`;
CREATE TABLE IF NOT EXISTS `order` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `buyer_id` int(11) NOT NULL,
  `order_date` datetime DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`order_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `buyer_id` int(11) DEFAULT NULL,
  `order_date` datetime DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`order_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
CREATE TABLE IF NOT EXISTS `reviews` (
  `review_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`review_id`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `seller`
--

DROP TABLE IF EXISTS `seller`;
CREATE TABLE IF NOT EXISTS `seller` (
  `seller_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `seller_name` varchar(100) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`seller_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `seller_clothing`
--

DROP TABLE IF EXISTS `seller_clothing`;
CREATE TABLE IF NOT EXISTS `seller_clothing` (
  `clothing_id` int(11) NOT NULL AUTO_INCREMENT,
  `seller_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `brand` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','approved','rejected','sold') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`clothing_id`),
  KEY `seller_id` (`seller_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `seller_requests`
--

DROP TABLE IF EXISTS `seller_requests`;
CREATE TABLE IF NOT EXISTS `seller_requests` (
  `request_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `clothing_name` varchar(200) NOT NULL,
  `brand` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `condition_status` varchar(50) DEFAULT 'good',
  PRIMARY KEY (`request_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `seller_requests`
--

INSERT INTO `seller_requests` (`request_id`, `user_id`, `clothing_name`, `brand`, `description`, `image_url`, `price`, `category`, `status`, `admin_notes`, `created_at`, `condition_status`) VALUES
(1, 9, 'jean', 'gucci', 'asdsadsad', 'uploads/seller_items/1780550332_6a210abcc652b.jpg', '50.00', 'men', 'pending', NULL, '2026-06-04 05:18:52', 'new'),
(2, 11, 'shirt', 'nike', 'black nike tech', 'uploads/seller_items/1781727290_6a33003a95c79.png', '100.00', 'men', 'approved', NULL, '2026-06-17 20:14:50', 'like_new'),
(3, 11, 'Blazer', 'Pick n Pay Clothing', 'Pink Blazer', 'uploads/seller_items/1781801760_6a342320270a4.png', '499.99', 'women', 'pending', NULL, '2026-06-18 16:56:00', 'like_new'),
(4, 11, 'Handbag', 'Zara', 'Pink Handbag', 'uploads/seller_items/1781801806_6a34234e7280c.png', '299.99', 'women', 'approved', NULL, '2026-06-18 16:56:46', 'good'),
(5, 11, 'Hoodie', 'Truworths', 'Pink Hoodie from truworths', 'uploads/seller_items/1781801860_6a342384af97e.png', '249.99', 'women', 'approved', NULL, '2026-06-18 16:57:40', 'like_new'),
(6, 11, 'Jacket', 'Nike', 'Black nike tech jacket', 'uploads/seller_items/1781801920_6a3423c02d4c4.png', '599.99', 'kids', 'approved', NULL, '2026-06-18 16:58:40', 'fair'),
(7, 11, 'Pants', 'Replay', 'Blue Replay Jeans', 'uploads/seller_items/1781801963_6a3423eb3f972.png', '199.99', 'men', 'approved', NULL, '2026-06-18 16:59:23', 'like_new'),
(8, 11, 'Shirt', 'Mr Price', 'Brand new Mr Price shirt, worn once', 'uploads/seller_items/1781802027_6a34242bdda4d.png', '149.99', 'men', 'approved', NULL, '2026-06-18 17:00:27', 'new'),
(9, 11, 'Shoes', 'Nike', 'pink nike foams', 'uploads/seller_items/1781802066_6a3424520059b.png', '349.99', 'kids', 'approved', NULL, '2026-06-18 17:01:06', 'like_new'),
(10, 11, 'Skirt', 'Factory', 'Scotch skirt', 'uploads/seller_items/1781802119_6a342487dc43e.png', '179.99', 'women', 'approved', NULL, '2026-06-18 17:01:59', 'fair');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE IF NOT EXISTS `user` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(10) DEFAULT 'buyer',
  `phone` varchar(20) DEFAULT NULL,
  `verified` int(11) DEFAULT 0,
  `is_seller` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `name`, `email`, `password`, `role`, `phone`, `verified`, `is_seller`) VALUES
(10, 'Oratile Ntebele', 'ntebeleorie@gmail.com', '9e1518bc5b26608434dd90a5e47978fa', 'buyer', '0662493767', 1, 0),
(11, 'Mbalenhle Zwane', 'mba@example.com', 'b6ef6a50899a2750e63a4ff094f9aa47', 'seller', '0768231866', 1, 0);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
