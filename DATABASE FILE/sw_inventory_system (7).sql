-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 11, 2025 at 09:32 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sw_inventory_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_notifications`
--

CREATE TABLE `admin_notifications` (
  `id` int(11) UNSIGNED NOT NULL,
  `order_id` int(11) UNSIGNED DEFAULT NULL,
  `type` enum('new_order','order_update','payment_received','low_stock','system','customer_registration') NOT NULL DEFAULT 'system',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `read_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `admin_notifications`
--

INSERT INTO `admin_notifications` (`id`, `order_id`, `type`, `title`, `message`, `is_read`, `created_at`, `read_at`) VALUES
(1, 1, 'new_order', 'New Order Received', 'A new order #ORD-2025-24446 has been placed by Harish Ahamed (harishahamed2607@gmail.com). Total amount: LKR 144,000.00. Please review and process the order.', 0, '2025-10-19 10:26:18', NULL),
(2, 2, 'new_order', 'New Order Received', 'A new order #ORD-2025-82719 has been placed by Harish Ahamed (harishahamed2607@gmail.com). Total amount: LKR 8,000.00. Please review and process the order.', 0, '2025-10-19 10:37:10', NULL),
(3, 3, 'new_order', 'New Order Received', 'A new order #ORD-2025-64409 has been placed by Harish Ahamed (harishahamed2607@gmail.com). Total amount: LKR 96,000.00. Please review and process the order.', 0, '2025-10-19 12:52:29', NULL),
(4, 4, 'new_order', 'New Order Received', 'A new order #ORD-2025-53959 has been placed by Harish Ahamed (harishahamed2607@gmail.com). Total amount: LKR 72,000.00. Please review and process the order.', 0, '2025-10-19 13:02:46', NULL),
(5, 5, 'new_order', 'New Order Received', 'A new order #ORD-2025-46320 has been placed by Harish Ahamed (harishahamed2607@gmail.com). Total amount: LKR 15,200.00. Please review and process the order.', 0, '2025-10-24 18:44:23', NULL),
(6, 6, 'new_order', 'New Order Received', 'A new order #ORD-2025-33456 has been placed by Harish Ahamed (harishahamed2607@gmail.com). Total amount: LKR 96,000.00. Please review and process the order.', 0, '2025-10-25 11:35:07', NULL),
(7, 7, 'new_order', 'New Order Received', 'A new order #ORD-2025-80306 has been placed by Harish Ahamed (harishahamed2607@gmail.com). Total amount: LKR 125,550.00. Please review and process the order.', 0, '2025-10-27 12:43:17', NULL),
(9, 9, 'new_order', 'New Order Received', 'A new order #ORD-2025-09883 has been placed by Harish Ahamed (harishahamed2607@gmail.com). Total amount: LKR 144,000.00. Please review and process the order.', 0, '2025-11-20 12:50:42', NULL),
(10, 10, 'new_order', 'New Order Received', 'A new order #ORD-2025-29493 has been placed by Harish Ahamed (harishahamed2607@gmail.com). Total amount: LKR 72,000.00. Please review and process the order.', 0, '2025-11-20 14:38:05', NULL),
(11, 11, 'new_order', 'New Order Received', 'A new order #ORD-2025-37044 has been placed by George Harry (georgeharyy86@gmail.com). Total amount: LKR 96,000.00. Please review and process the order.', 0, '2025-11-21 19:38:41', NULL),
(12, 12, 'new_order', 'New Order Received', 'A new order #ORD-2025-78759 has been placed by George Harry (georgeharyy86@gmail.com). Total amount: LKR 28,500.00. Please review and process the order.', 0, '2025-11-21 20:32:21', NULL),
(13, 13, 'new_order', 'New Order Received', 'A new order #ORD-2025-08221 has been placed by George Harry (georgeharyy86@gmail.com). Total amount: LKR 8,000.00. Please review and process the order.', 0, '2025-11-23 19:56:56', NULL),
(14, 14, 'new_order', 'New Order Received', 'A new order #ORD-2025-73720 has been placed by George Harry (georgeharyy86@gmail.com). Total amount: LKR 8,000.00. Please review and process the order.', 0, '2025-11-23 20:07:53', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) UNSIGNED NOT NULL,
  `customer_id` int(11) UNSIGNED NOT NULL,
  `product_id` int(11) UNSIGNED NOT NULL,
  `quantity` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(9, 'Bed Room'),
(12, 'Dining '),
(11, 'Kitchen '),
(10, 'Living Room'),
(13, 'Office Supplies'),
(14, 'Other');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `registration_type` enum('online','walkin') NOT NULL DEFAULT 'online',
  `created_at` datetime NOT NULL,
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `email`, `password`, `phone`, `address`, `status`, `registration_type`, `created_at`, `last_login`) VALUES
(1, 'Harish Ahamed', 'harishahamed2607@gmail.com', '$2y$10$cAnglE4RU2/W2YnOFMn7Y.nF/VcVw8rybnwhw.tIIJhQyd8sMKhXS', '0761321604', '300/A,\r\nDehipagoda, Muruthagahamula', 1, 'online', '2025-10-17 15:32:02', '2025-11-20 14:45:18'),
(3, 'Harish Ahamed', 'harishahamed@gmail.com', '', '0761321605', '', 1, 'walkin', '2025-10-19 13:12:14', NULL),
(7, 'Lishani', 'Lish@gmail.com', '', '0775871111', '', 1, 'walkin', '2025-10-24 18:36:04', NULL),
(12, 'Ahamed', 'ahamed@gmail.com', '', '0761321650', '', 1, 'walkin', '2025-11-18 14:00:18', NULL),
(13, 'George Harry', 'georgeharyy86@gmail.com', '$2y$10$pPnO/BmbjxIxwZwHZfLkX.FI2qmWLuDc.yD0HiLLzQZwibLTpNbLK', '0753614324', '12, Main street, Gampola', 1, 'online', '2025-11-21 13:42:42', '2025-11-26 21:26:56'),
(14, 'Zaid', 'zaid@gmail.com', '', '0772119010', '', 1, 'walkin', '2025-11-21 15:36:03', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) UNSIGNED NOT NULL,
  `customer_id` int(11) UNSIGNED NOT NULL,
  `order_id` int(11) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('order_update','promotion','system','payment','shipping') NOT NULL DEFAULT 'system',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `read_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `customer_id`, `order_id`, `title`, `message`, `type`, `is_read`, `created_at`, `read_at`) VALUES
(1, 1, 1, 'Order Placed Successfully', 'Your order #ORD-2025-24446 has been placed successfully. Total amount: LKR 144,000.00. We will process your order shortly.', '', 0, '2025-10-19 10:26:18', NULL),
(2, 1, 2, 'Order Placed Successfully', 'Your order #ORD-2025-82719 has been placed successfully. Total amount: LKR 8,000.00. We will process your order shortly.', '', 0, '2025-10-19 10:37:10', NULL),
(3, 1, 3, 'Order Placed Successfully', 'Your order #ORD-2025-64409 has been placed successfully. Total amount: LKR 96,000.00. We will process your order shortly.', '', 0, '2025-10-19 12:52:29', NULL),
(4, 1, 4, 'Order Placed Successfully', 'Your order #ORD-2025-53959 has been placed successfully. Total amount: LKR 72,000.00. We will process your order shortly.', '', 0, '2025-10-19 13:02:46', NULL),
(5, 1, 4, 'Order Accepted', 'Great news! Your order ORD-2025-53959 has been accepted and is now being processed. Estimated delivery date: Oct 26, 2025.', '', 0, '2025-10-19 13:03:53', NULL),
(6, 1, 3, 'Order Rejected', 'Your order ORD-2025-64409 has been rejected by our team. Reason: test', '', 0, '2025-10-19 13:04:23', NULL),
(7, 1, 3, 'Payment Status Updated', 'Payment for your order ORD-2025-64409 has failed. Please contact us for assistance.', '', 0, '2025-10-19 13:04:28', NULL),
(8, 1, 4, 'Order Status Updated', 'Your order ORD-2025-53959 has been shipped and is on its way to you!', '', 0, '2025-10-19 13:05:07', NULL),
(9, 1, 4, 'Order Status Updated', 'Your order ORD-2025-53959 has been delivered successfully. Thank you for your purchase!', '', 0, '2025-10-19 13:05:50', NULL),
(10, 1, 4, 'Payment Status Updated', 'Payment for your order ORD-2025-53959 has been confirmed. Thank you!', '', 0, '2025-10-19 13:05:55', NULL),
(11, 1, 5, 'Order Placed Successfully', 'Your order #ORD-2025-46320 has been placed successfully. Total amount: LKR 15,200.00. We will process your order shortly.', '', 0, '2025-10-24 18:44:23', NULL),
(12, 1, 5, 'Order Accepted', 'Great news! Your order ORD-2025-46320 has been accepted and is now being processed. Estimated delivery date: Oct 31, 2025.', '', 0, '2025-10-24 18:46:41', NULL),
(13, 1, 5, 'Order Status Updated', 'Your order ORD-2025-46320 has been shipped and is on its way to you!', '', 0, '2025-10-24 18:48:57', NULL),
(14, 1, 5, 'Order Status Updated', 'Your order ORD-2025-46320 has been delivered successfully. Thank you for your purchase!', '', 0, '2025-10-24 18:50:20', NULL),
(15, 1, 5, 'Payment Status Updated', 'Payment for your order ORD-2025-46320 has been confirmed. Thank you!', '', 0, '2025-10-24 18:50:41', NULL),
(16, 1, 6, 'Order Placed Successfully', 'Your order #ORD-2025-33456 has been placed successfully. Total amount: LKR 96,000.00. We will process your order shortly.', '', 0, '2025-10-25 11:35:07', NULL),
(17, 1, 6, 'Order Accepted', 'Great news! Your order ORD-2025-33456 has been accepted and is now being processed. Estimated delivery date: Nov 01, 2025.', '', 0, '2025-10-25 11:35:48', NULL),
(18, 1, 6, 'Order Status Updated', 'Your order ORD-2025-33456 has been delivered successfully. Thank you for your purchase!', '', 0, '2025-10-25 11:38:53', NULL),
(19, 1, 6, 'Payment Status Updated', 'Payment for your order ORD-2025-33456 has been confirmed. Thank you!', '', 0, '2025-10-25 11:38:59', NULL),
(20, 1, 7, 'Order Placed Successfully', 'Your order #ORD-2025-80306 has been placed successfully. Total amount: LKR 125,550.00. We will process your order shortly.', '', 0, '2025-10-27 12:43:17', NULL),
(21, 1, 7, 'Order Accepted', 'Great news! Your order ORD-2025-80306 has been accepted and is now being processed. Estimated delivery date: Nov 03, 2025.', '', 0, '2025-10-27 21:18:03', NULL),
(24, 1, 9, 'Order Placed Successfully', 'Your order #ORD-2025-09883 has been placed successfully. Total amount: LKR 144,000.00. We will process your order shortly.', '', 0, '2025-11-20 12:50:42', NULL),
(25, 1, 10, 'Order Placed Successfully', 'Your order #ORD-2025-29493 has been placed successfully. Total amount: LKR 72,000.00. We will process your order shortly.', '', 0, '2025-11-20 14:38:05', NULL),
(26, 13, 11, 'Order Placed Successfully', 'Your order #ORD-2025-37044 has been placed successfully. Total amount: LKR 96,000.00. We will process your order shortly.', '', 0, '2025-11-21 19:38:41', NULL),
(27, 13, 11, 'Order Accepted', 'Great news! Your order ORD-2025-37044 has been accepted and is now being processed. Estimated delivery date: Nov 28, 2025.', '', 0, '2025-11-21 19:48:11', NULL),
(28, 13, 11, 'Order Status Updated', 'Your order ORD-2025-37044 has been delivered successfully. Thank you for your purchase!', '', 0, '2025-11-21 19:53:43', NULL),
(29, 13, 11, 'Payment Status Updated', 'Payment for your order ORD-2025-37044 has been confirmed. Thank you!', '', 0, '2025-11-21 19:53:49', NULL),
(30, 13, 12, 'Order Placed Successfully', 'Your order #ORD-2025-78759 has been placed successfully. Total amount: LKR 28,500.00. We will process your order shortly.', '', 0, '2025-11-21 20:32:21', NULL),
(31, 13, 12, 'Order Accepted', 'Great news! Your order ORD-2025-78759 has been accepted and is now being processed. Estimated delivery date: Nov 28, 2025.', '', 0, '2025-11-21 20:36:27', NULL),
(32, 13, 12, 'Order Status Updated', 'Your order ORD-2025-78759 has been delivered successfully. Thank you for your purchase!', '', 0, '2025-11-21 20:39:30', NULL),
(33, 13, 12, 'Payment Status Updated', 'Payment for your order ORD-2025-78759 has been confirmed. Thank you!', '', 0, '2025-11-21 20:39:37', NULL),
(34, 13, 13, 'Order Placed Successfully', 'Your order #ORD-2025-08221 has been placed successfully. Total amount: LKR 8,000.00. We will process your order shortly.', '', 0, '2025-11-23 19:56:56', NULL),
(35, 13, 13, 'Order Rejected', 'Your order ORD-2025-08221 has been rejected by our team. Reason: Rejected', '', 0, '2025-11-23 20:00:05', NULL),
(36, 13, 13, 'Payment Status Updated', 'Payment for your order ORD-2025-08221 has failed. Please contact us for assistance.', '', 0, '2025-11-23 20:00:13', NULL),
(37, 13, 14, 'Order Placed Successfully', 'Your order #ORD-2025-73720 has been placed successfully. Total amount: LKR 8,000.00. We will process your order shortly.', '', 0, '2025-11-23 20:07:53', NULL),
(38, 13, 14, 'Order Rejected', 'Your order ORD-2025-73720 has been rejected by our team. Reason: Order rejected due to some personal reasons', '', 0, '2025-11-23 20:12:08', NULL),
(39, 13, 14, 'Payment Status Updated', 'Payment for your order ORD-2025-73720 has failed. Please contact us for assistance.', '', 0, '2025-11-23 20:12:16', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) UNSIGNED NOT NULL,
  `customer_id` int(11) UNSIGNED NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `total_amount` decimal(25,2) NOT NULL,
  `discount_amount` decimal(25,2) DEFAULT 0.00,
  `status` enum('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `payment_status` enum('pending','paid','failed') NOT NULL DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `shipping_method` varchar(50) DEFAULT NULL,
  `shipping_cost` decimal(10,2) DEFAULT 0.00,
  `phone_number` varchar(20) DEFAULT NULL,
  `shipping_address` text NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_id`, `order_number`, `total_amount`, `discount_amount`, `status`, `payment_status`, `payment_method`, `shipping_method`, `shipping_cost`, `phone_number`, `shipping_address`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 'ORD-2025-24446', 144000.00, 36000.00, 'pending', 'pending', 'cash_on_delivery', 'standard', 0.00, '0761321604', '300/A,\r\nDehipagoda, Muruthagahamula', NULL, '2025-10-19 10:26:18', '2025-10-19 10:26:18'),
(2, 1, 'ORD-2025-82719', 8000.00, 0.00, 'pending', 'pending', 'cash_on_delivery', 'standard', 0.00, '0761321604', '300/A,\r\nDehipagoda, Muruthagahamula', NULL, '2025-10-19 10:37:10', '2025-10-19 10:37:10'),
(3, 1, 'ORD-2025-64409', 96000.00, 24000.00, 'cancelled', 'failed', 'cash_on_delivery', 'standard', 0.00, '0761321604', '300/A,\r\nDehipagoda, Muruthagahamula', '\n[2025-10-19 09:34:23] Order rejected: test', '2025-10-19 12:52:29', '2025-10-19 13:04:23'),
(4, 1, 'ORD-2025-53959', 72000.00, 18000.00, 'delivered', 'paid', 'cash_on_delivery', 'standard', 0.00, '0761321604', '300/A,\r\nDehipagoda, Muruthagahamula', '\n[2025-10-19 09:33:53] Order accepted: test\n[2025-10-19 09:35:50] test', '2025-10-19 13:02:46', '2025-10-19 13:05:50'),
(5, 1, 'ORD-2025-46320', 15200.00, 3800.00, 'delivered', 'paid', 'cash_on_delivery', 'standard', 0.00, '0761321604', '300/A,\r\nDehipagoda, Muruthagahamula', '\n[2025-10-24 15:16:41] Order accepted: Accepted\n[2025-10-24 15:18:57] Shipped\n[2025-10-24 15:20:20] test', '2025-10-24 18:44:23', '2025-10-24 18:50:20'),
(6, 1, 'ORD-2025-33456', 96000.00, 24000.00, 'delivered', 'paid', 'cash_on_delivery', 'standard', 0.00, '0761321604', '300/A,\r\nDehipagoda, Muruthagahamula', '\n[2025-10-25 11:35:48] Order accepted: test\n[2025-10-25 11:38:53] test', '2025-10-25 11:35:07', '2025-10-25 11:38:53'),
(7, 1, 'ORD-2025-80306', 125550.00, 0.00, 'processing', 'pending', 'cash_on_delivery', 'standard', 0.00, '0761321604', '300/A,\r\nDehipagoda, Muruthagahamula', '\n[2025-10-27 21:18:03] Order accepted: test', '2025-10-27 12:43:17', '2025-10-27 21:18:03'),
(9, 1, 'ORD-2025-09883', 144000.00, 36000.00, 'pending', 'pending', 'cash_on_delivery', 'standard', 0.00, '0761321604', '300/A,\r\nDehipagoda, Muruthagahamula', NULL, '2025-11-20 12:50:42', '2025-11-20 12:50:42'),
(10, 1, 'ORD-2025-29493', 72000.00, 18000.00, 'pending', 'pending', 'cash_on_delivery', 'standard', 0.00, '0761321604', '300/A,\r\nDehipagoda, Muruthagahamula', NULL, '2025-11-20 14:38:05', '2025-11-20 14:38:05'),
(11, 13, 'ORD-2025-37044', 96000.00, 24000.00, 'delivered', 'paid', 'cash_on_delivery', 'standard', 0.00, '0753614324', '12, Main street, Gampola', '\n[2025-11-21 19:48:11] Order accepted: Order accepted', '2025-11-21 19:38:41', '2025-11-21 19:53:43'),
(12, 13, 'ORD-2025-78759', 28500.00, 0.00, 'delivered', 'paid', 'cash_on_delivery', 'standard', 0.00, '0753614324', '12, Main street, Gampola', '\n[2025-11-21 20:36:27] Order accepted: Order accepted\n[2025-11-21 20:39:30] Order delivered', '2025-11-21 20:32:21', '2025-11-21 20:39:30'),
(13, 13, 'ORD-2025-08221', 8000.00, 0.00, 'cancelled', 'failed', 'cash_on_delivery', 'standard', 0.00, '0753614324', '12, Main street, Gampola', '\n[2025-11-23 20:00:05] Order rejected: Rejected', '2025-11-23 19:56:56', '2025-11-23 20:00:05'),
(14, 13, 'ORD-2025-73720', 8000.00, 0.00, 'cancelled', 'failed', 'cash_on_delivery', 'standard', 0.00, '0753614324', '12, Main street, Gampola', '\n[2025-11-23 20:12:08] Order rejected: Order rejected due to some personal reasons', '2025-11-23 20:07:53', '2025-11-23 20:12:08');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) UNSIGNED NOT NULL,
  `order_id` int(11) UNSIGNED NOT NULL,
  `product_id` int(11) UNSIGNED NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(25,2) NOT NULL,
  `subtotal` decimal(25,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`, `subtotal`) VALUES
(1, 1, 38, 2, 72000.00, 0.00),
(2, 2, 28, 1, 8000.00, 0.00),
(3, 3, 23, 1, 96000.00, 0.00),
(4, 4, 38, 1, 72000.00, 0.00),
(5, 5, 26, 1, 15200.00, 0.00),
(6, 6, 23, 1, 96000.00, 0.00),
(7, 7, 40, 1, 125550.00, 0.00),
(9, 9, 38, 2, 72000.00, 0.00),
(10, 10, 38, 1, 72000.00, 0.00),
(11, 11, 23, 1, 96000.00, 0.00),
(12, 12, 35, 1, 28500.00, 0.00),
(13, 13, 28, 1, 8000.00, 0.00),
(14, 14, 28, 1, 8000.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_status_history`
--

CREATE TABLE `order_status_history` (
  `id` int(11) UNSIGNED NOT NULL,
  `order_id` int(11) UNSIGNED NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `payment_status` enum('pending','paid','failed') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `order_status_history`
--

INSERT INTO `order_status_history` (`id`, `order_id`, `status`, `payment_status`, `notes`, `created_at`) VALUES
(1, 1, 'pending', 'pending', 'Order placed via online checkout', '2025-10-19 10:26:18'),
(2, 2, 'pending', 'pending', 'Order placed via online checkout', '2025-10-19 10:37:10'),
(3, 3, 'pending', 'pending', 'Order placed via online checkout', '2025-10-19 12:52:29'),
(4, 4, 'pending', 'pending', 'Order placed via online checkout', '2025-10-19 13:02:46'),
(5, 4, 'processing', 'pending', 'Order accepted: test', '2025-10-19 13:03:53'),
(6, 3, 'cancelled', 'failed', 'Order rejected: test', '2025-10-19 13:04:23'),
(7, 4, 'shipped', 'pending', '', '2025-10-19 13:05:07'),
(8, 4, 'delivered', 'paid', 'test', '2025-10-19 13:05:50'),
(9, 5, 'pending', 'pending', 'Order placed via online checkout', '2025-10-24 18:44:23'),
(10, 5, 'processing', 'pending', 'Order accepted: Accepted', '2025-10-24 18:46:41'),
(11, 5, 'shipped', 'pending', 'Shipped', '2025-10-24 18:48:57'),
(12, 5, 'delivered', 'paid', 'test', '2025-10-24 18:50:20'),
(13, 6, 'pending', 'pending', 'Order placed via online checkout', '2025-10-25 11:35:07'),
(14, 6, 'processing', 'pending', 'Order accepted: test', '2025-10-25 11:35:48'),
(15, 6, 'delivered', 'paid', 'test', '2025-10-25 11:38:53'),
(16, 7, 'pending', 'pending', 'Order placed via online checkout', '2025-10-27 12:43:17'),
(17, 7, 'processing', 'pending', 'Order accepted: test', '2025-10-27 21:18:03'),
(20, 9, 'pending', 'pending', 'Order placed via online checkout', '2025-11-20 12:50:42'),
(21, 10, 'pending', 'pending', 'Order placed via online checkout', '2025-11-20 14:38:05'),
(22, 11, 'pending', 'pending', 'Order placed via online checkout', '2025-11-21 19:38:41'),
(23, 11, 'processing', 'pending', 'Order accepted: Order accepted', '2025-11-21 19:48:11'),
(24, 11, 'delivered', 'paid', '', '2025-11-21 19:53:43'),
(25, 12, 'pending', 'pending', 'Order placed via online checkout', '2025-11-21 20:32:21'),
(26, 12, 'processing', 'pending', 'Order accepted: Order accepted', '2025-11-21 20:36:27'),
(27, 12, 'delivered', 'paid', 'Order delivered', '2025-11-21 20:39:30'),
(28, 13, 'pending', 'pending', 'Order placed via online checkout', '2025-11-23 19:56:56'),
(29, 13, 'cancelled', 'failed', 'Order rejected: Rejected', '2025-11-23 20:00:05'),
(30, 14, 'pending', 'pending', 'Order placed via online checkout', '2025-11-23 20:07:53'),
(31, 14, 'cancelled', 'failed', 'Order rejected: Order rejected due to some personal reasons', '2025-11-23 20:12:08');

-- --------------------------------------------------------

--
-- Table structure for table `order_tracking`
--

CREATE TABLE `order_tracking` (
  `id` int(11) UNSIGNED NOT NULL,
  `order_id` int(11) UNSIGNED NOT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `carrier` varchar(100) DEFAULT NULL,
  `estimated_delivery` date DEFAULT NULL,
  `status` enum('pending','in_transit','out_for_delivery','delivered','exception') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `order_tracking`
--

INSERT INTO `order_tracking` (`id`, `order_id`, `tracking_number`, `carrier`, `estimated_delivery`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 4, '123', 'DHL', '2025-10-26', 'pending', NULL, '2025-10-19 13:03:53', '2025-10-19 13:05:50'),
(2, 5, 'ABC 123', 'Other', '2025-10-31', 'pending', NULL, '2025-10-24 18:46:41', '2025-10-24 18:48:57'),
(3, 6, 'test', 'DHL', '2025-11-01', 'pending', NULL, '2025-10-25 11:35:48', '2025-10-25 11:38:53'),
(4, 7, NULL, NULL, '2025-11-03', 'pending', NULL, '2025-10-27 21:18:03', '2025-10-27 21:18:03'),
(6, 11, NULL, 'DHL', '2025-11-28', 'pending', NULL, '2025-11-21 19:48:11', '2025-11-21 19:53:43'),
(7, 12, 'TR 1245', 'Domex', '2025-11-28', 'pending', NULL, '2025-11-21 20:36:27', '2025-11-21 20:39:30');

-- --------------------------------------------------------

--
-- Table structure for table `physical_sales`
--

CREATE TABLE `physical_sales` (
  `id` int(11) UNSIGNED NOT NULL,
  `sale_number` varchar(50) NOT NULL,
  `cashier_id` int(11) UNSIGNED NOT NULL,
  `customer_id` int(11) UNSIGNED DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `subtotal` decimal(25,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(25,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(25,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `cash_amount` decimal(25,2) DEFAULT 0.00,
  `card_amount` decimal(25,2) DEFAULT 0.00,
  `change_given` decimal(25,2) DEFAULT 0.00,
  `status` enum('completed','refunded','partially_refunded') NOT NULL DEFAULT 'completed',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `physical_sales`
--

INSERT INTO `physical_sales` (`id`, `sale_number`, `cashier_id`, `customer_id`, `customer_name`, `customer_phone`, `customer_email`, `subtotal`, `discount_amount`, `total_amount`, `payment_method`, `cash_amount`, `card_amount`, `change_given`, `status`, `created_at`, `updated_at`) VALUES
(2, 'PS202510190292', 6, 3, 'Harish Ahamed', '0761321605', 'harishahamed@gmail.com', 130000.00, 0.00, 130000.00, 'cash', 130000.00, NULL, 0.00, 'completed', '2025-10-19 13:12:14', '2025-10-19 13:12:14'),
(3, 'PS202510240500', 6, 7, 'Lishani', '0775871111', 'Lish@gmail.com', 9000.00, 0.00, 9000.00, '0', 9000.00, NULL, 0.00, 'completed', '2025-10-24 18:36:04', '2025-10-24 18:36:04'),
(8, 'PS202510271032', 6, 1, 'Harish Ahamed', '0761321604', 'harishahamed2607@gmail.com', 72000.00, 0.00, 72000.00, 'cash', 75000.00, NULL, 3000.00, 'completed', '2025-10-27 13:01:17', '2025-10-27 13:01:17'),
(9, 'PS202510275353', 6, 1, 'Harish Ahamed', '0761321604', 'harishahamed2607@gmail.com', 90000.00, 18000.00, 72000.00, '0', 75000.00, 0.00, 3000.00, 'completed', '2025-10-27 14:12:36', '2025-10-27 14:12:36'),
(10, 'PS202511184178', 6, 12, 'Ahamed', '0761321650', 'ahamed@gmail.com', 90000.00, 18000.00, 72000.00, '0', 75000.00, 0.00, 3000.00, 'completed', '2025-11-18 14:00:18', '2025-11-18 14:00:18'),
(11, 'PS202511212522', 6, 14, 'Zaid', '0772119010', 'zaid@gmail.com', 115000.00, 0.00, 115000.00, '0', 120000.00, 0.00, 5000.00, 'completed', '2025-11-21 15:36:03', '2025-11-21 15:36:03');

-- --------------------------------------------------------

--
-- Table structure for table `physical_sales_cart`
--

CREATE TABLE `physical_sales_cart` (
  `id` int(11) UNSIGNED NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `product_id` int(11) UNSIGNED NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(25,2) NOT NULL,
  `discount_amount` decimal(25,2) DEFAULT 0.00,
  `total_price` decimal(25,2) NOT NULL,
  `promotion_id` int(11) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `physical_sales_cart`
--

INSERT INTO `physical_sales_cart` (`id`, `session_id`, `product_id`, `quantity`, `unit_price`, `discount_amount`, `total_price`, `promotion_id`, `created_at`, `updated_at`) VALUES
(46, 'g968ksdor3rjto59d2tqpnd1cq', 38, 1, 72000.00, 0.00, 72000.00, 3, '2025-10-25 11:39:24', '2025-10-25 11:39:24'),
(67, '2pkqqev09fdgl1utc8vtanhcr8', 24, 1, 130000.00, 0.00, 130000.00, NULL, '2025-10-27 21:34:44', '2025-10-27 21:34:44'),
(70, 'ttroqteanki7jjko8u2uil453i', 38, 1, 72000.00, 18000.00, 72000.00, 3, '2025-11-20 12:59:40', '2025-11-20 12:59:40');

-- --------------------------------------------------------

--
-- Table structure for table `physical_sales_items`
--

CREATE TABLE `physical_sales_items` (
  `id` int(11) UNSIGNED NOT NULL,
  `sale_id` int(11) UNSIGNED NOT NULL,
  `product_id` int(11) UNSIGNED NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(25,2) NOT NULL,
  `discount_amount` decimal(25,2) DEFAULT 0.00,
  `total_price` decimal(25,2) NOT NULL,
  `promotion_id` int(11) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `physical_sales_items`
--

INSERT INTO `physical_sales_items` (`id`, `sale_id`, `product_id`, `quantity`, `unit_price`, `discount_amount`, `total_price`, `promotion_id`, `created_at`) VALUES
(2, 2, 24, 1, 130000.00, 0.00, 130000.00, NULL, '2025-10-19 13:12:14'),
(3, 3, 22, 1, 9000.00, 0.00, 9000.00, NULL, '2025-10-24 18:36:04'),
(8, 8, 38, 1, 72000.00, 0.00, 72000.00, NULL, '2025-10-27 13:01:17'),
(9, 9, 38, 1, 72000.00, 18000.00, 72000.00, 3, '2025-10-27 14:12:36'),
(10, 10, 38, 1, 72000.00, 18000.00, 72000.00, 3, '2025-11-18 14:00:18'),
(11, 11, 17, 1, 115000.00, 0.00, 115000.00, NULL, '2025-11-21 15:36:03');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` varchar(50) DEFAULT NULL,
  `buy_price` decimal(25,2) DEFAULT NULL,
  `sale_price` decimal(25,2) NOT NULL,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images`)),
  `primary_image_index` int(11) DEFAULT 0,
  `categorie_id` int(11) UNSIGNED NOT NULL,
  `supplier_id` int(11) UNSIGNED DEFAULT NULL,
  `date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `quantity`, `buy_price`, `sale_price`, `images`, `primary_image_index`, `categorie_id`, `supplier_id`, `date`) VALUES
(14, 'L-PLAIN WOODEN TOP TABLE WITH L-COSMO DINING CHAIRS (WITH STUDS)', 'Contemporary dining set featuring a solid wooden top table and studded chairs.\r\nSize (Table): 72” L x 36” W x 30” H', '100', 65000.00, 78500.00, '[\"68f35c741fe45_1760779380_0.png\",\"68f35c74209e9_1760779380_1.png\",\"68f35c7420fae_1760779380_2.png\"]', 2, 12, 1, '2025-10-18 11:23:00'),
(15, 'S DESIGN CHESS BOX TABLE WITH FULL LENGTH DESIGN CHAIRS', 'Elegant dining set with a chess-box patterned table and matching full-length chairs.\r\nSize (Table): 72” L x 36” W x 30” H', '100', 75000.00, 90000.00, '[\"68f35cb47b6e9_1760779444_0.jpg\",\"68f35cb47b9e2_1760779444_1.jpg\",\"68f35cb47bbb2_1760779444_2.jpg\"]', 2, 12, 1, '2025-10-18 11:24:04'),
(16, 'V STYLE WOODEN TOP TABLE WITH HALF LENGTH CHUNKY CHAIRS', 'Chic dining table with V-style legs and half-length chunky wooden chairs.\r\nSize (Table): 72” L x 36” W x 30” H', '100', 115000.00, 125550.00, '[\"68f35d267388f_1760779558_0.png\",\"68f35d2673ae3_1760779558_1.png\",\"68f35d2673caf_1760779558_2.png\"]', 1, 12, 1, '2025-10-18 11:25:58'),
(17, 'ATLANTA SOFA WITH FLAT WICKER DESIGN COFFEE TABLE', 'A modern sofa set paired with a matching wicker-style coffee table for a cozy living space.\r\nSize (Sofa): 84” L x 34” W x 36” H\r\nSize (Table): 40” L x 20” W x 18” H', '98', 90000.00, 115000.00, '[\"68f35ddada6db_1760779738_0.jpg\",\"68f35ddadaa1f_1760779738_1.jpg\",\"68f35ddadae13_1760779738_2.jpg\"]', 1, 10, 2, '2025-10-18 11:28:58'),
(18, 'PRIME SOFA WITH CHESS BOX DESIGN 2 TIER COFFEE TABLE', 'Stylish sofa set with unique chess-box patterned table for a bold living room look.\r\nSize (Sofa): 84” L x 34” W x 36” H\r\nSize (Table): 40” L x 20” W x 18” H', '100', 125000.00, 135000.00, '[\"68f35e1311859_1760779795_0.jpg\",\"68f35e1312077_1760779795_1.jpg\"]', 0, 10, 2, '2025-10-18 11:29:55'),
(19, 'L SHAPED SOFA-TUFTED DESIGN WITH C DESIGN COFFEE TABLE', 'Luxurious L-shaped sofa with tufted upholstery and matching wooden coffee table.\r\nSize (Sofa): 96” L x 60” W x 36” H\r\nSize (Table): 42” L x 24” W x 18” H', '100', 105000.00, 120000.00, '[\"68f35e34b9a80_1760779828_0.png\",\"68f35e34b9cf1_1760779828_1.png\"]', 1, 10, 2, '2025-10-18 11:30:28'),
(20, 'LOUNGE SOFA', 'Relax in style with this plush lounge sofa, perfect for cozy corners and living areas.\r\nSize: 84” L x 36” W x 36” H', '1000', 65000.00, 73500.00, '[\"68f35e6556ec6_1760779877_0.png\",\"68f35e655821b_1760779877_1.png\",\"68f35e6558c6a_1760779877_2.png\"]', 0, 10, 2, '2025-10-18 11:31:17'),
(21, 'STRIPE VERANDAH CHAIR', 'Comfortable verandah chair with striped wooden design for outdoor relaxation.\r\nSize: 24” L x 22” W x 36” H', '100', 6500.00, 8000.00, '[\"68f35ecd63943_1760779981_0.jpg\",\"68f35ecd63c94_1760779981_1.jpg\"]', 0, 10, 2, '2025-10-18 11:33:01'),
(22, 'CUSHIONED BUCKET CHAIR', 'Comfortable bucket chair with soft cushioning and ergonomic design.', '99', 7500.00, 9000.00, '[\"68f35ef5926cb_1760780021_0.jpg\",\"68f35ef59297d_1760780021_1.jpg\"]', 0, 10, 1, '2025-10-18 11:33:41'),
(23, '2 COMPARTMENT WARDROBE', 'A sleek dual-compartment wardrobe offering smart organization for clothes and accessories.\r\nSize: 48” L x 22” W x 72” H', '97', 95000.00, 120000.00, '[\"68f35f3e323e4_1760780094_0.png\",\"68f35f3e326e1_1760780094_1.png\",\"68f35f3e32879_1760780094_2.png\"]', 2, 9, 3, '2025-10-18 11:34:54'),
(24, 'ARALIYA DESIGN 3 DOOR WARDROBE ', 'A beautifully crafted 3-door wardrobe with elegant design and spacious storage compartments.', '96', 115000.00, 130000.00, '[\"68f35f6b213ac_1760780139_0.png\",\"68f35f6b2166a_1760780139_1.png\"]', 0, 12, 1, '2025-10-18 11:35:39'),
(25, 'SINGLE DOOR WARDROBE', 'Compact single-door wardrobe ideal for small bedrooms or office spaces.\r\nSize: 24” L x 22” W x 72” H', '100', 25500.00, 37500.00, '[\"68f35f8ccb211_1760780172_0.png\",\"68f35f8ccb4b1_1760780172_1.png\",\"68f35f8ccb780_1760780172_2.png\"]', 0, 9, 2, '2025-10-18 11:36:12'),
(26, 'BUDGET DRESSING TABLE', 'Minimalist dressing table with mirror and single drawer, perfect for tight spaces.\r\nSize: 30” L x 16” W x 60” H', '99', 17500.00, 19000.00, '[\"68f35fcd6f28c_1760780237_0.png\",\"68f35fcd6f569_1760780237_1.png\"]', 0, 9, 3, '2025-10-18 11:37:17'),
(27, 'NF DESIGN DRESSING TABLE', 'Elegant dressing table with designer mirror frame and storage drawers.\r\nSize: 36” L x 16” W x 66” H', '100', 13000.00, 15500.00, '[\"68f35ff0d31bc_1760780272_0.png\",\"68f35ff0d3421_1760780272_1.png\"]', 1, 9, 2, '2025-10-18 11:37:52'),
(28, 'BOOK RACK – SMALL', 'Compact wooden book rack ideal for home offices or study corners.\r\nSize: 24” L x 12” W x 48” H', '97', 6500.00, 8000.00, '[\"68f3604270b28_1760780354_0.png\",\"68f3604270f0c_1760780354_1.png\"]', 0, 14, 4, '2025-10-18 11:39:14'),
(29, 'BUFFET TABLE', 'Classic buffet table perfect for dining room storage and serving.', '100', 35000.00, 38500.00, '[\"68f36068d3a27_1760780392_0.png\",\"68f36068d3c5c_1760780392_1.png\"]', 0, 14, 5, '2025-10-18 11:39:52'),
(30, 'FLOWER POT AND STAND', 'Charming pot and stand combo perfect for adding a natural touch indoors.\r\nSize: 14” L x 14” W x 28” H', '100', 11000.00, 13500.00, '[\"68f360886d729_1760780424_0.png\"]', 0, 14, 5, '2025-10-18 11:40:24'),
(31, 'IRONING TABLE WITH SHOE CUPBOARD', 'Multi-purpose unit combining an ironing surface with a handy shoe storage compartment.\r\nSize: 48” L x 16” W x 36” H', '100', 43500.00, 55000.00, '[\"68f360b2888d9_1760780466_0.jpg\",\"68f360b288b92_1760780466_1.jpg\",\"68f360b288d9b_1760780466_2.jpg\",\"68f360b288f74_1760780466_3.jpg\"]', 3, 14, 4, '2025-10-18 11:41:06'),
(32, 'OFFICE TABLE', 'Spacious office table with smooth wooden finish and built-in drawers for convenience.\r\nSize: 60” L x 30” W x 30” H', '100', 13000.00, 17000.00, '[\"68f3614c66bce_1760780620_0.jpg\",\"68f3614c671e8_1760780620_1.jpg\"]', 0, 13, 1, '2025-10-18 11:43:40'),
(33, 'FILLING RACK', 'Tall and durable wooden filing rack ideal for offices and storage rooms.\r\nSize: 36” L x 15” W x 72” H', '100', 19000.00, 22550.00, '[\"68f3617924d5b_1760780665_0.jpg\",\"68f3617925087_1760780665_1.jpg\"]', 0, 13, 3, '2025-10-18 11:44:25'),
(34, 'FILLING RACK - SMALL', 'Compact version of our filing rack for limited spaces without sacrificing storage.\r\nSize: 30” L x 15” W x 54” H', '100', 6500.00, 9000.00, '[\"68f361af65e41_1760780719_0.jpg\"]', 0, 13, 3, '2025-10-18 11:45:19'),
(35, 'ELEGANT TV STAND', 'Modern TV stand with open shelves and sleek finish for a stylish entertainment setup.\r\nSize: 60” L x 16” W x 24” H', '99', 23000.00, 28500.00, '[\"68f361e473161_1760780772_0.jpg\",\"68f361e473bb6_1760780772_1.jpg\",\"68f361e473e42_1760780772_2.jpg\"]', 2, 14, 5, '2025-10-18 11:46:12'),
(36, 'REGENT BED', 'Premium wooden bed with refined headboard design and durable build.\r\nSize: 78” L x 60” W x 38” H', '1000', 45000.00, 65550.00, '[\"68f36231464e5_1760780849_0.jpg\",\"68f36231467a0_1760780849_1.jpg\",\"68f3623146a5e_1760780849_2.jpg\"]', 2, 9, 4, '2025-10-18 11:47:29'),
(38, 'ARALIYA BED', 'A beautifully crafted wooden bed with a sturdy frame and smooth polished finish.\r\nSize: 78” L x 60” W x 36” H', '90', 75550.00, 90000.00, '[\"68f362b1786c4_1760780977_0.png\",\"68f362b17890f_1760780977_1.png\",\"68f362b178a99_1760780977_2.png\"]', 2, 9, 5, '2025-10-18 11:49:37'),
(39, 'ASHTON PANTRY', 'Spacious wooden pantry unit with adjustable shelves and stylish handles.\r\nSize: 48” L x 18” W x 78” H', '100', 135000.00, 170000.00, '[\"68f362f758009_1760781047_0.webp\",\"68f362f758494_1760781047_1.webp\",\"68f362f7586a8_1760781047_2.webp\",\"68f362f758874_1760781047_3.webp\"]', 3, 11, 2, '2025-10-18 11:50:47'),
(40, 'BERKLY PANTRY', 'Modern pantry unit with multiple shelves and doors for organized kitchen storage.', '99', 115000.00, 125550.00, '[\"68f36335c4da6_1760781109_0.jpg\",\"68f36335c500c_1760781109_1.jpg\",\"68f36335c51e0_1760781109_2.webp\",\"68f36335c53ab_1760781109_3.webp\"]', 3, 11, 4, '2025-10-18 11:51:49'),
(41, 'MODULAR KITCHEN PANTRY', 'Modern modular pantry system with multiple cabinets for a neat, functional kitchen.\r\nSize: 84” L x 20” W x 84” H', '100', 95000.00, 110000.00, '[\"68f36384198e8_1760781188_0.webp\",\"68f3638419c5c_1760781188_1.webp\",\"68f3638419ffd_1760781188_2.jpg\"]', 0, 11, 1, '2025-10-18 11:53:08'),
(45, 'KALISTO BED', 'Stylish and sturdy wooden bed designed for ultimate comfort and elegance.\r\nSize: 78” L x 60” W x 40” H', '100', 55000.00, 75000.00, '[\"692032dc94733_1763717852_0.jpg\",\"692032dc95252_1763717852_1.jpg\"]', 0, 9, 1, '2025-11-21 15:07:32');

-- --------------------------------------------------------

--
-- Table structure for table `promotions`
--

CREATE TABLE `promotions` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `promo_code` varchar(50) DEFAULT NULL,
  `discount_type` enum('percentage','fixed_amount') NOT NULL DEFAULT 'percentage',
  `discount_value` decimal(10,2) NOT NULL,
  `min_order_amount` decimal(25,2) DEFAULT 0.00,
  `max_discount_amount` decimal(25,2) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `applies_to` enum('all_products','specific_products','specific_categories') NOT NULL DEFAULT 'all_products',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `promotions`
--

INSERT INTO `promotions` (`id`, `name`, `description`, `promo_code`, `discount_type`, `discount_value`, `min_order_amount`, `max_discount_amount`, `usage_limit`, `start_date`, `end_date`, `applies_to`, `is_active`, `created_at`, `updated_at`) VALUES
(3, 'Bedroom Bliss Offer', '20% discount on all bedroom sets.', 'BED20', 'percentage', 20.00, 1.00, NULL, NULL, '2025-10-01 16:30:00', '2026-01-31 16:30:00', 'specific_categories', 1, '2025-10-18 16:30:27', '2025-10-18 16:30:27'),
(5, 'Halloween Deals', '10% discount for selected products', 'H1234', 'percentage', 10.00, 1.00, NULL, NULL, '2025-10-01 00:00:00', '2025-10-31 23:59:00', 'specific_products', 0, '2025-10-25 11:11:25', '2025-11-21 16:23:15'),
(6, 'Year End Sale', '15% off on all kitchen items', 'YE001', 'percentage', 15.00, 1.00, NULL, NULL, '2025-11-01 23:59:00', '2025-12-31 23:59:00', 'specific_categories', 1, '2025-11-21 16:30:13', '2025-11-21 16:30:13');

-- --------------------------------------------------------

--
-- Table structure for table `promotion_categories`
--

CREATE TABLE `promotion_categories` (
  `id` int(11) UNSIGNED NOT NULL,
  `promotion_id` int(11) UNSIGNED NOT NULL,
  `category_id` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `promotion_categories`
--

INSERT INTO `promotion_categories` (`id`, `promotion_id`, `category_id`) VALUES
(2, 3, 9),
(6, 6, 11);

-- --------------------------------------------------------

--
-- Table structure for table `promotion_products`
--

CREATE TABLE `promotion_products` (
  `id` int(11) UNSIGNED NOT NULL,
  `promotion_id` int(11) UNSIGNED NOT NULL,
  `product_id` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `promotion_products`
--

INSERT INTO `promotion_products` (`id`, `promotion_id`, `product_id`) VALUES
(8, 5, 16),
(9, 5, 21),
(10, 5, 22),
(11, 5, 30),
(12, 5, 33);

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact_person` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `contact_person`, `phone`, `email`, `address`, `status`, `date`) VALUES
(1, 'Global Furniture and Decor Suppliers', 'Sampath', '0761321560', 'gf@gmail.com', 'Kandy', 1, '2025-10-17 17:07:25'),
(2, 'Wood Craft Supplies Ltd.', 'Ashen', '0775871122', 'woodcrafts@gmail.com', 'Gelioya', 1, '2025-10-17 17:08:05'),
(3, 'Classic Timber and Furniture Traders', 'Ahamed', '0774686066', 'ctft@gmail.com', 'Colombo', 1, '2025-10-17 17:08:26'),
(4, 'Modern Living Interiors (Pvt) Ltd.', 'Rilwan', '0771321221', 'modern@gmail.com', 'Gampola', 1, '2025-10-17 17:08:56'),
(5, 'Elegant Home Furnishings Co.', 'Peter', '0772119010', 'eleganthome@gmail.com', 'Nawalapitiya', 1, '2025-10-17 17:09:24');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(60) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_level` int(11) NOT NULL,
  `image` varchar(255) DEFAULT 'no_image.jpg',
  `status` int(1) NOT NULL,
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `password`, `user_level`, `image`, `status`, `last_login`) VALUES
(1, 'Mohamed Nazeem', 'Admin', 'd033e22ae348aeb5660fc2140aec35850c4da997', 1, 'no_image.png', 1, '2025-10-17 11:33:18'),
(6, 'Harish Nazeem', 'Harish', 'c0b137fe2d792459f26ff763cce44574a5b5ab03', 1, 'oifmtnec6.png', 1, '2025-11-26 21:27:02'),
(7, 'Shadir Mazeen', 'shad@gmail.com', 'c0b137fe2d792459f26ff763cce44574a5b5ab03', 1, 'no_image.jpg', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_groups`
--

CREATE TABLE `user_groups` (
  `id` int(11) NOT NULL,
  `group_name` varchar(150) NOT NULL,
  `group_level` int(11) NOT NULL,
  `group_status` int(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `user_groups`
--

INSERT INTO `user_groups` (`id`, `group_name`, `group_level`, `group_status`) VALUES
(1, 'Admin', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) UNSIGNED NOT NULL,
  `customer_id` int(11) UNSIGNED NOT NULL,
  `product_id` int(11) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`id`, `customer_id`, `product_id`, `created_at`) VALUES
(6, 1, 23, '0000-00-00 00:00:00'),
(7, 1, 39, '2025-11-20 14:45:27'),
(8, 1, 28, '2025-11-20 14:45:29');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `type` (`type`),
  ADD KEY `is_read` (`is_read`),
  ADD KEY `idx_admin_notifications_order` (`order_id`),
  ADD KEY `idx_admin_notifications_type` (`type`),
  ADD KEY `idx_admin_notifications_read` (`is_read`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_cart_customer` (`customer_id`),
  ADD KEY `idx_cart_product` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `is_read` (`is_read`),
  ADD KEY `type` (`type`),
  ADD KEY `idx_notifications_customer` (`customer_id`),
  ADD KEY `idx_notifications_order` (`order_id`),
  ADD KEY `idx_notifications_read` (`is_read`),
  ADD KEY `idx_notifications_type` (`type`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `idx_orders_customer` (`customer_id`),
  ADD KEY `idx_orders_status` (`status`),
  ADD KEY `idx_orders_payment_status` (`payment_status`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_order_items_order` (`order_id`),
  ADD KEY `idx_order_items_product` (`product_id`);

--
-- Indexes for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `status` (`status`),
  ADD KEY `payment_status` (`payment_status`),
  ADD KEY `idx_order_status_history_order` (`order_id`),
  ADD KEY `idx_order_status_history_status` (`status`),
  ADD KEY `idx_order_status_history_payment` (`payment_status`);

--
-- Indexes for table `order_tracking`
--
ALTER TABLE `order_tracking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `tracking_number` (`tracking_number`),
  ADD KEY `idx_order_tracking_order` (`order_id`),
  ADD KEY `idx_order_tracking_number` (`tracking_number`);

--
-- Indexes for table `physical_sales`
--
ALTER TABLE `physical_sales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sale_number` (`sale_number`),
  ADD KEY `cashier_id` (`cashier_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `idx_physical_sales_cashier` (`cashier_id`),
  ADD KEY `idx_physical_sales_customer` (`customer_id`),
  ADD KEY `idx_physical_sales_date` (`created_at`);

--
-- Indexes for table `physical_sales_cart`
--
ALTER TABLE `physical_sales_cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `promotion_id` (`promotion_id`),
  ADD KEY `idx_physical_sales_cart_session` (`session_id`),
  ADD KEY `idx_physical_sales_cart_product` (`product_id`),
  ADD KEY `idx_physical_sales_cart_promotion` (`promotion_id`);

--
-- Indexes for table `physical_sales_items`
--
ALTER TABLE `physical_sales_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `promotion_id` (`promotion_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `categorie_id` (`categorie_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `idx_products_category` (`categorie_id`),
  ADD KEY `idx_products_supplier` (`supplier_id`);

--
-- Indexes for table `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `promo_code` (`promo_code`),
  ADD KEY `is_active` (`is_active`),
  ADD KEY `start_date` (`start_date`),
  ADD KEY `end_date` (`end_date`),
  ADD KEY `idx_promotions_active` (`is_active`,`start_date`,`end_date`);

--
-- Indexes for table `promotion_categories`
--
ALTER TABLE `promotion_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `promotion_category` (`promotion_id`,`category_id`),
  ADD KEY `promotion_id` (`promotion_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `promotion_products`
--
ALTER TABLE `promotion_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `promotion_product` (`promotion_id`,`product_id`),
  ADD KEY `promotion_id` (`promotion_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `user_level` (`user_level`);

--
-- Indexes for table `user_groups`
--
ALTER TABLE `user_groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `group_level` (`group_level`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customer_product` (`customer_id`,`product_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_wishlist_customer` (`customer_id`),
  ADD KEY `idx_wishlist_product` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `order_tracking`
--
ALTER TABLE `order_tracking`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `physical_sales`
--
ALTER TABLE `physical_sales`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `physical_sales_cart`
--
ALTER TABLE `physical_sales_cart`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `physical_sales_items`
--
ALTER TABLE `physical_sales_items`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `promotions`
--
ALTER TABLE `promotions`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `promotion_categories`
--
ALTER TABLE `promotion_categories`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `promotion_products`
--
ALTER TABLE `promotion_products`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user_groups`
--
ALTER TABLE `user_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD CONSTRAINT `admin_notifications_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD CONSTRAINT `order_status_history_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_tracking`
--
ALTER TABLE `order_tracking`
  ADD CONSTRAINT `order_tracking_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `physical_sales`
--
ALTER TABLE `physical_sales`
  ADD CONSTRAINT `physical_sales_ibfk_1` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `physical_sales_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `physical_sales_cart`
--
ALTER TABLE `physical_sales_cart`
  ADD CONSTRAINT `physical_sales_cart_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `physical_sales_cart_ibfk_2` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `physical_sales_items`
--
ALTER TABLE `physical_sales_items`
  ADD CONSTRAINT `physical_sales_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `physical_sales` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `physical_sales_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `physical_sales_items_ibfk_3` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `FK_products` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_products_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `promotion_categories`
--
ALTER TABLE `promotion_categories`
  ADD CONSTRAINT `promotion_categories_ibfk_1` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `promotion_categories_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `promotion_products`
--
ALTER TABLE `promotion_products`
  ADD CONSTRAINT `promotion_products_ibfk_1` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `promotion_products_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `FK_user` FOREIGN KEY (`user_level`) REFERENCES `user_groups` (`group_level`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
