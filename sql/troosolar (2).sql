-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 03, 2025 at 04:35 PM
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
-- Database: `troosolar`
--

-- --------------------------------------------------------

--
-- Table structure for table `banners`
--

CREATE TABLE `banners` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `banners`
--

INSERT INTO `banners` (`id`, `image`, `created_at`, `updated_at`) VALUES
(1, 'banners/R81Tz3vuMacEzQmpcLQkRMIboS4EOXvNpJxGqvK0.jpg', '2025-07-25 05:59:00', '2025-07-25 05:59:00');

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`id`, `title`, `icon`, `category_id`, `created_at`, `updated_at`) VALUES
(1, 'Smart TV', NULL, 1, '2025-07-22 02:55:33', '2025-07-22 02:55:33'),
(2, 'Smart TV', NULL, 1, '2025-07-22 02:55:37', '2025-07-22 02:55:37'),
(3, 'Smart TV', NULL, 1, '2025-07-22 02:55:39', '2025-07-22 02:55:39'),
(4, 'Smart TV', NULL, 1, '2025-07-25 05:54:07', '2025-07-25 05:54:07'),
(5, 'Smart TV', NULL, 1, '2025-07-25 05:54:09', '2025-07-25 05:54:09'),
(6, 'Smart TV', NULL, 1, '2025-07-25 05:54:11', '2025-07-25 05:54:11'),
(7, 'LED', NULL, 1, '2025-09-02 05:26:21', '2025-09-02 05:26:21');

-- --------------------------------------------------------

--
-- Table structure for table `bundles`
--

CREATE TABLE `bundles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `bundle_type` varchar(255) DEFAULT NULL,
  `total_price` double NOT NULL DEFAULT 0,
  `discount_price` double NOT NULL DEFAULT 0,
  `discount_end_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bundles`
--

INSERT INTO `bundles` (`id`, `title`, `featured_image`, `bundle_type`, `total_price`, `discount_price`, `discount_end_date`, `created_at`, `updated_at`) VALUES
(1, 'Example Bundle', NULL, NULL, 999.99, 899.99, '2025-08-01', '2025-07-22 02:58:16', '2025-07-22 02:58:16'),
(2, 'Example Bundle', NULL, NULL, 999.99, 899.99, '2025-08-01', '2025-07-22 02:58:19', '2025-07-22 02:58:19'),
(3, 'Example Bundle', NULL, NULL, 999.99, 899.99, '2025-08-01', '2025-07-24 05:36:36', '2025-07-24 05:36:36'),
(4, 'Example Bundle', NULL, NULL, 0, 0, NULL, '2025-07-24 14:20:19', '2025-07-24 14:20:19'),
(5, 'Example Bundle', NULL, NULL, 5000, 0, NULL, '2025-07-24 14:27:46', '2025-07-24 14:27:46'),
(6, 'Example Bundle', NULL, NULL, 5125, 899.99, '2025-08-01', '2025-07-24 14:28:15', '2025-07-24 14:28:15'),
(7, 'Example Bundle', NULL, NULL, 5000, 0, NULL, '2025-07-24 14:31:00', '2025-07-24 14:31:00'),
(8, 'Example Bundle', NULL, NULL, 4000, 0, NULL, '2025-07-24 14:31:11', '2025-07-24 14:31:11'),
(9, 'Example Bundle', NULL, NULL, 4000, 3200, NULL, '2025-07-24 14:35:04', '2025-07-24 14:35:04'),
(10, 'Example Bundle', NULL, NULL, 4000, 3200, NULL, '2025-07-24 14:35:49', '2025-07-24 14:35:49'),
(11, 'Example Bundle', NULL, NULL, 5125, 4100, '2025-08-01', '2025-07-24 14:36:04', '2025-07-24 14:36:04'),
(12, 'Example Bundle', NULL, NULL, 5125, 899.99, '2025-08-01', '2025-07-24 14:38:50', '2025-07-24 14:38:50'),
(13, 'Example Bundle', NULL, NULL, 999.99, 899.99, '2025-08-01', '2025-07-24 14:41:50', '2025-07-24 14:41:50'),
(14, 'Example Bundle', NULL, NULL, 899.99, 799.99, '2025-08-01', '2025-07-24 14:42:07', '2025-07-24 14:42:07'),
(15, 'Example Bundle', NULL, NULL, 4000, 3200, NULL, '2025-07-24 14:42:19', '2025-07-24 14:42:19'),
(16, 'Example Bundle', NULL, NULL, 5000, 4000, NULL, '2025-07-24 14:42:32', '2025-07-24 14:42:32'),
(17, 'Example Bundle', NULL, NULL, 5000, 4000, NULL, '2025-07-25 01:24:03', '2025-07-25 01:24:03'),
(18, 'Example Bundle', NULL, NULL, 5000, 3000, NULL, '2025-07-25 01:25:57', '2025-07-25 01:25:57'),
(19, 'Example Bundle', NULL, NULL, 5000, 4500, NULL, '2025-07-25 01:26:12', '2025-07-25 01:26:12'),
(20, 'Example Bundle', NULL, NULL, 899.99, 799.99, '2025-08-01', '2025-07-25 01:27:08', '2025-07-25 01:27:08'),
(21, 'Example Bundle', NULL, NULL, 5000, 4500, NULL, '2025-07-25 01:34:00', '2025-07-25 01:34:00'),
(22, 'Example Bundle', NULL, NULL, 5000, 4500, NULL, '2025-07-25 05:21:36', '2025-07-25 05:21:36'),
(23, 'Example Bundle', NULL, NULL, 999.99, 899.99, '2025-08-01', '2025-07-25 06:07:49', '2025-07-25 06:07:49'),
(24, 'Example Bundle', NULL, NULL, 999.99, 899.99, '2025-08-01', '2025-07-25 06:07:53', '2025-07-25 06:07:53'),
(25, 'Example Bundle', NULL, NULL, 999.99, 899.99, '2025-08-01', '2025-07-25 06:07:55', '2025-07-25 06:07:55'),
(26, 'Summer Saver Bundle', 'bundles/QpMMiI1TbmCzMDc5E3WiG73PGPd6fw9Ui9seg51B.jpg', NULL, 5000, 4500, '2025-08-01', '2025-09-02 08:08:29', '2025-09-02 08:08:29');

-- --------------------------------------------------------

--
-- Table structure for table `bundle_items`
--

CREATE TABLE `bundle_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `bundle_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bundle_items`
--

INSERT INTO `bundle_items` (`id`, `product_id`, `bundle_id`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2025-07-22 02:58:16', '2025-07-22 02:58:16'),
(2, 2, 1, '2025-07-22 02:58:16', '2025-07-22 02:58:16'),
(3, 3, 1, '2025-07-22 02:58:16', '2025-07-22 02:58:16'),
(4, 4, 1, '2025-07-22 02:58:16', '2025-07-22 02:58:16'),
(5, 5, 1, '2025-07-22 02:58:16', '2025-07-22 02:58:16'),
(6, 1, 2, '2025-07-22 02:58:19', '2025-07-22 02:58:19'),
(7, 2, 2, '2025-07-22 02:58:19', '2025-07-22 02:58:19'),
(8, 3, 2, '2025-07-22 02:58:19', '2025-07-22 02:58:19'),
(9, 4, 2, '2025-07-22 02:58:19', '2025-07-22 02:58:19'),
(10, 5, 2, '2025-07-22 02:58:19', '2025-07-22 02:58:19'),
(11, 1, 3, '2025-07-24 05:36:36', '2025-07-24 05:36:36'),
(12, 2, 3, '2025-07-24 05:36:36', '2025-07-24 05:36:36'),
(13, 3, 3, '2025-07-24 05:36:36', '2025-07-24 05:36:36'),
(14, 4, 3, '2025-07-24 05:36:36', '2025-07-24 05:36:36'),
(15, 5, 3, '2025-07-24 05:36:36', '2025-07-24 05:36:36'),
(16, 1, 4, '2025-07-24 14:20:19', '2025-07-24 14:20:19'),
(17, 2, 4, '2025-07-24 14:20:19', '2025-07-24 14:20:19'),
(18, 3, 4, '2025-07-24 14:20:19', '2025-07-24 14:20:19'),
(19, 4, 4, '2025-07-24 14:20:19', '2025-07-24 14:20:19'),
(20, 5, 4, '2025-07-24 14:20:19', '2025-07-24 14:20:19'),
(21, 1, 5, '2025-07-24 14:27:46', '2025-07-24 14:27:46'),
(22, 2, 5, '2025-07-24 14:27:46', '2025-07-24 14:27:46'),
(23, 3, 5, '2025-07-24 14:27:46', '2025-07-24 14:27:46'),
(24, 4, 5, '2025-07-24 14:27:46', '2025-07-24 14:27:46'),
(25, 5, 5, '2025-07-24 14:27:46', '2025-07-24 14:27:46'),
(26, 1, 6, '2025-07-24 14:28:15', '2025-07-24 14:28:15'),
(27, 2, 6, '2025-07-24 14:28:15', '2025-07-24 14:28:15'),
(28, 3, 6, '2025-07-24 14:28:15', '2025-07-24 14:28:15'),
(29, 4, 6, '2025-07-24 14:28:15', '2025-07-24 14:28:15'),
(30, 5, 6, '2025-07-24 14:28:15', '2025-07-24 14:28:15'),
(31, 1, 7, '2025-07-24 14:31:00', '2025-07-24 14:31:00'),
(32, 2, 7, '2025-07-24 14:31:00', '2025-07-24 14:31:00'),
(33, 3, 7, '2025-07-24 14:31:00', '2025-07-24 14:31:00'),
(34, 4, 7, '2025-07-24 14:31:00', '2025-07-24 14:31:00'),
(35, 5, 7, '2025-07-24 14:31:00', '2025-07-24 14:31:00'),
(36, 1, 8, '2025-07-24 14:31:11', '2025-07-24 14:31:11'),
(37, 2, 8, '2025-07-24 14:31:11', '2025-07-24 14:31:11'),
(38, 3, 8, '2025-07-24 14:31:11', '2025-07-24 14:31:11'),
(39, 4, 8, '2025-07-24 14:31:11', '2025-07-24 14:31:11'),
(40, 1, 9, '2025-07-24 14:35:04', '2025-07-24 14:35:04'),
(41, 2, 9, '2025-07-24 14:35:04', '2025-07-24 14:35:04'),
(42, 3, 9, '2025-07-24 14:35:04', '2025-07-24 14:35:04'),
(43, 4, 9, '2025-07-24 14:35:04', '2025-07-24 14:35:04'),
(44, 1, 10, '2025-07-24 14:35:49', '2025-07-24 14:35:49'),
(45, 2, 10, '2025-07-24 14:35:49', '2025-07-24 14:35:49'),
(46, 3, 10, '2025-07-24 14:35:49', '2025-07-24 14:35:49'),
(47, 4, 10, '2025-07-24 14:35:49', '2025-07-24 14:35:49'),
(48, 1, 11, '2025-07-24 14:36:04', '2025-07-24 14:36:04'),
(49, 2, 11, '2025-07-24 14:36:04', '2025-07-24 14:36:04'),
(50, 3, 11, '2025-07-24 14:36:04', '2025-07-24 14:36:04'),
(51, 4, 11, '2025-07-24 14:36:04', '2025-07-24 14:36:04'),
(52, 5, 11, '2025-07-24 14:36:04', '2025-07-24 14:36:04'),
(53, 1, 12, '2025-07-24 14:38:50', '2025-07-24 14:38:50'),
(54, 2, 12, '2025-07-24 14:38:50', '2025-07-24 14:38:50'),
(55, 3, 12, '2025-07-24 14:38:50', '2025-07-24 14:38:50'),
(56, 4, 12, '2025-07-24 14:38:50', '2025-07-24 14:38:50'),
(57, 5, 12, '2025-07-24 14:38:50', '2025-07-24 14:38:50'),
(58, 1, 13, '2025-07-24 14:41:50', '2025-07-24 14:41:50'),
(59, 2, 13, '2025-07-24 14:41:50', '2025-07-24 14:41:50'),
(60, 3, 13, '2025-07-24 14:41:50', '2025-07-24 14:41:50'),
(61, 4, 13, '2025-07-24 14:41:50', '2025-07-24 14:41:50'),
(62, 5, 13, '2025-07-24 14:41:50', '2025-07-24 14:41:50'),
(63, 1, 14, '2025-07-24 14:42:07', '2025-07-24 14:42:07'),
(64, 2, 14, '2025-07-24 14:42:07', '2025-07-24 14:42:07'),
(65, 3, 14, '2025-07-24 14:42:07', '2025-07-24 14:42:07'),
(66, 4, 14, '2025-07-24 14:42:07', '2025-07-24 14:42:07'),
(67, 5, 14, '2025-07-24 14:42:07', '2025-07-24 14:42:07'),
(68, 1, 15, '2025-07-24 14:42:19', '2025-07-24 14:42:19'),
(69, 2, 15, '2025-07-24 14:42:19', '2025-07-24 14:42:19'),
(70, 3, 15, '2025-07-24 14:42:19', '2025-07-24 14:42:19'),
(71, 4, 15, '2025-07-24 14:42:19', '2025-07-24 14:42:19'),
(72, 1, 16, '2025-07-24 14:42:32', '2025-07-24 14:42:32'),
(73, 2, 16, '2025-07-24 14:42:32', '2025-07-24 14:42:32'),
(74, 3, 16, '2025-07-24 14:42:32', '2025-07-24 14:42:32'),
(75, 4, 16, '2025-07-24 14:42:32', '2025-07-24 14:42:32'),
(76, 5, 16, '2025-07-24 14:42:32', '2025-07-24 14:42:32'),
(77, 1, 17, '2025-07-25 01:24:03', '2025-07-25 01:24:03'),
(78, 2, 17, '2025-07-25 01:24:03', '2025-07-25 01:24:03'),
(79, 3, 17, '2025-07-25 01:24:03', '2025-07-25 01:24:03'),
(80, 4, 17, '2025-07-25 01:24:03', '2025-07-25 01:24:03'),
(81, 5, 17, '2025-07-25 01:24:03', '2025-07-25 01:24:03'),
(82, 1, 18, '2025-07-25 01:25:57', '2025-07-25 01:25:57'),
(83, 2, 18, '2025-07-25 01:25:57', '2025-07-25 01:25:57'),
(84, 3, 18, '2025-07-25 01:25:57', '2025-07-25 01:25:57'),
(85, 4, 18, '2025-07-25 01:25:57', '2025-07-25 01:25:57'),
(86, 5, 18, '2025-07-25 01:25:57', '2025-07-25 01:25:57'),
(87, 1, 19, '2025-07-25 01:26:12', '2025-07-25 01:26:12'),
(88, 2, 19, '2025-07-25 01:26:12', '2025-07-25 01:26:12'),
(89, 3, 19, '2025-07-25 01:26:12', '2025-07-25 01:26:12'),
(90, 4, 19, '2025-07-25 01:26:12', '2025-07-25 01:26:12'),
(91, 5, 19, '2025-07-25 01:26:12', '2025-07-25 01:26:12'),
(92, 1, 20, '2025-07-25 01:27:08', '2025-07-25 01:27:08'),
(93, 2, 20, '2025-07-25 01:27:08', '2025-07-25 01:27:08'),
(94, 3, 20, '2025-07-25 01:27:08', '2025-07-25 01:27:08'),
(95, 4, 20, '2025-07-25 01:27:08', '2025-07-25 01:27:08'),
(96, 5, 20, '2025-07-25 01:27:08', '2025-07-25 01:27:08'),
(97, 1, 21, '2025-07-25 01:34:00', '2025-07-25 01:34:00'),
(98, 2, 21, '2025-07-25 01:34:00', '2025-07-25 01:34:00'),
(99, 3, 21, '2025-07-25 01:34:00', '2025-07-25 01:34:00'),
(100, 4, 21, '2025-07-25 01:34:00', '2025-07-25 01:34:00'),
(101, 5, 21, '2025-07-25 01:34:00', '2025-07-25 01:34:00'),
(102, 1, 22, '2025-07-25 05:21:36', '2025-07-25 05:21:36'),
(103, 2, 22, '2025-07-25 05:21:36', '2025-07-25 05:21:36'),
(104, 3, 22, '2025-07-25 05:21:36', '2025-07-25 05:21:36'),
(105, 4, 22, '2025-07-25 05:21:36', '2025-07-25 05:21:36'),
(106, 5, 22, '2025-07-25 05:21:36', '2025-07-25 05:21:36'),
(107, 1, 23, '2025-07-25 06:07:49', '2025-07-25 06:07:49'),
(108, 2, 23, '2025-07-25 06:07:49', '2025-07-25 06:07:49'),
(109, 3, 23, '2025-07-25 06:07:49', '2025-07-25 06:07:49'),
(110, 4, 23, '2025-07-25 06:07:49', '2025-07-25 06:07:49'),
(111, 5, 23, '2025-07-25 06:07:49', '2025-07-25 06:07:49'),
(112, 1, 24, '2025-07-25 06:07:53', '2025-07-25 06:07:53'),
(113, 2, 24, '2025-07-25 06:07:53', '2025-07-25 06:07:53'),
(114, 3, 24, '2025-07-25 06:07:53', '2025-07-25 06:07:53'),
(115, 4, 24, '2025-07-25 06:07:53', '2025-07-25 06:07:53'),
(116, 5, 24, '2025-07-25 06:07:53', '2025-07-25 06:07:53'),
(117, 1, 25, '2025-07-25 06:07:55', '2025-07-25 06:07:55'),
(118, 2, 25, '2025-07-25 06:07:55', '2025-07-25 06:07:55'),
(119, 3, 25, '2025-07-25 06:07:55', '2025-07-25 06:07:55'),
(120, 4, 25, '2025-07-25 06:07:55', '2025-07-25 06:07:55'),
(121, 5, 25, '2025-07-25 06:07:55', '2025-07-25 06:07:55'),
(122, 1, 26, '2025-09-02 08:08:29', '2025-09-02 08:08:29'),
(123, 2, 26, '2025-09-02 08:08:29', '2025-09-02 08:08:29');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `itemable_type` varchar(255) DEFAULT NULL,
  `itemable_id` bigint(20) UNSIGNED DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `unit_price` decimal(10,2) DEFAULT 0.00,
  `subtotal` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`id`, `user_id`, `itemable_type`, `itemable_id`, `quantity`, `unit_price`, `subtotal`, `created_at`, `updated_at`) VALUES
(17, 4, 'App\\Models\\Product', 3, 4, 100.00, 400.00, '2025-07-25 06:21:10', '2025-07-25 06:21:10'),
(18, 4, 'App\\Models\\Product', 2, 4, 100.00, 400.00, '2025-07-25 07:32:21', '2025-07-25 07:32:21'),
(19, 6, 'App\\Models\\Product', 2, 2, 100.00, 200.00, '2025-09-02 09:05:44', '2025-09-02 09:24:10');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `title`, `icon`, `created_at`, `updated_at`) VALUES
(1, 'Electronics', NULL, '2025-07-22 02:55:06', '2025-07-22 02:55:06'),
(2, 'Electronics', NULL, '2025-07-22 02:55:09', '2025-07-22 02:55:09'),
(3, 'Electronics', NULL, '2025-07-22 02:55:11', '2025-07-22 02:55:11'),
(4, 'Electronics', NULL, '2025-07-25 05:51:58', '2025-07-25 05:51:58'),
(5, 'Electronics', NULL, '2025-07-25 05:52:00', '2025-07-25 05:52:00'),
(6, 'Electronics', NULL, '2025-07-25 05:52:02', '2025-07-25 05:52:02'),
(7, 'Electronics', NULL, '2025-07-25 05:52:04', '2025-07-25 05:52:04'),
(8, 'Batteries', '/storage/icons/63f6adda-9ea8-40cf-9783-d71fad1f09ed.jpeg', '2025-09-02 04:09:46', '2025-09-02 04:09:46');

-- --------------------------------------------------------

--
-- Table structure for table `credit_data`
--

CREATE TABLE `credit_data` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tottal_income` double NOT NULL DEFAULT 0,
  `monthly_income` double NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `custom_services`
--

CREATE TABLE `custom_services` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `service_amount` double NOT NULL DEFAULT 0,
  `bundle_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `custom_services`
--

INSERT INTO `custom_services` (`id`, `title`, `service_amount`, `bundle_id`, `created_at`, `updated_at`) VALUES
(1, 'Installation Service', 50, 1, '2025-07-22 02:58:16', '2025-07-22 02:58:16'),
(2, 'Extended Warranty', 75, 1, '2025-07-22 02:58:16', '2025-07-22 02:58:16'),
(3, 'Installation Service', 50, 2, '2025-07-22 02:58:19', '2025-07-22 02:58:19'),
(4, 'Extended Warranty', 75, 2, '2025-07-22 02:58:19', '2025-07-22 02:58:19'),
(5, 'Installation Service', 50, 3, '2025-07-24 05:36:36', '2025-07-24 05:36:36'),
(6, 'Extended Warranty', 75, 3, '2025-07-24 05:36:36', '2025-07-24 05:36:36'),
(7, 'Installation Service', 50, 6, '2025-07-24 14:28:15', '2025-07-24 14:28:15'),
(8, 'Extended Warranty', 75, 6, '2025-07-24 14:28:15', '2025-07-24 14:28:15'),
(9, 'Installation Service', 50, 11, '2025-07-24 14:36:04', '2025-07-24 14:36:04'),
(10, 'Extended Warranty', 75, 11, '2025-07-24 14:36:04', '2025-07-24 14:36:04'),
(11, 'Installation Service', 50, 12, '2025-07-24 14:38:50', '2025-07-24 14:38:50'),
(12, 'Extended Warranty', 75, 12, '2025-07-24 14:38:50', '2025-07-24 14:38:50'),
(13, 'Installation Service', 50, 13, '2025-07-24 14:41:50', '2025-07-24 14:41:50'),
(14, 'Extended Warranty', 75, 13, '2025-07-24 14:41:50', '2025-07-24 14:41:50'),
(15, 'Installation Service', 50, 14, '2025-07-24 14:42:07', '2025-07-24 14:42:07'),
(16, 'Extended Warranty', 75, 14, '2025-07-24 14:42:07', '2025-07-24 14:42:07'),
(17, 'Installation Service', 50, 20, '2025-07-25 01:27:08', '2025-07-25 01:27:08'),
(18, 'Extended Warranty', 75, 20, '2025-07-25 01:27:08', '2025-07-25 01:27:08'),
(19, 'Installation Service', 50, 23, '2025-07-25 06:07:49', '2025-07-25 06:07:49'),
(20, 'Extended Warranty', 75, 23, '2025-07-25 06:07:49', '2025-07-25 06:07:49'),
(21, 'Installation Service', 50, 24, '2025-07-25 06:07:53', '2025-07-25 06:07:53'),
(22, 'Extended Warranty', 75, 24, '2025-07-25 06:07:53', '2025-07-25 06:07:53'),
(23, 'Installation Service', 50, 25, '2025-07-25 06:07:55', '2025-07-25 06:07:55'),
(24, 'Extended Warranty', 75, 25, '2025-07-25 06:07:55', '2025-07-25 06:07:55'),
(25, 'xyz', 500, 26, '2025-09-02 08:08:29', '2025-09-02 08:08:29'),
(26, 'abc', 400, 26, '2025-09-02 08:08:29', '2025-09-02 08:08:29');

-- --------------------------------------------------------

--
-- Table structure for table `debt_statuses`
--

CREATE TABLE `debt_statuses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `debt_status` varchar(255) DEFAULT NULL,
  `total_owned` double NOT NULL DEFAULT 0,
  `account_statement` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `delivery_addresses`
--

CREATE TABLE `delivery_addresses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `phone_number` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `delivery_addresses`
--

INSERT INTO `delivery_addresses` (`id`, `user_id`, `phone_number`, `title`, `address`, `state`, `created_at`, `updated_at`) VALUES
(4, 4, '03001234567', 'Home', 'House 123, Street 4, Islamabad', 'Punjab', '2025-07-25 05:18:01', '2025-07-25 05:18:01'),
(5, 6, '03206440155', 'office', 'main bakar mandi road, Faisalabad', 'Punjab', '2025-09-03 05:35:37', '2025-09-03 05:35:37');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `interest_percentages`
--

CREATE TABLE `interest_percentages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `interest_percentage` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `interest_percentages`
--

INSERT INTO `interest_percentages` (`id`, `interest_percentage`, `created_at`, `updated_at`) VALUES
(1, 10, '2025-07-22 03:06:57', '2025-07-22 03:06:57'),
(2, 10, '2025-07-23 04:21:55', '2025-07-23 04:21:55'),
(3, 10, '2025-07-25 06:09:56', '2025-07-25 06:09:56'),
(4, 10, '2025-07-25 09:31:12', '2025-07-25 09:31:12');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`id`, `queue`, `payload`, `attempts`, `reserved_at`, `available_at`, `created_at`) VALUES
(1, 'default', '{\"uuid\":\"e6ba618b-f133-4e18-9abb-ff642f5e603a\",\"displayName\":\"App\\\\Jobs\\\\SendUserLoanInfoToPartnerJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendUserLoanInfoToPartnerJob\",\"command\":\"O:37:\\\"App\\\\Jobs\\\\SendUserLoanInfoToPartnerJob\\\":0:{}\"}}', 0, NULL, 1753440022, 1753440022),
(2, 'default', '{\"uuid\":\"6ba73d76-4331-4036-ba5b-e28e8abd4972\",\"displayName\":\"App\\\\Jobs\\\\SendUserLoanInfoToPartnerJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendUserLoanInfoToPartnerJob\",\"command\":\"O:37:\\\"App\\\\Jobs\\\\SendUserLoanInfoToPartnerJob\\\":0:{}\"}}', 0, NULL, 1753440029, 1753440029);

-- --------------------------------------------------------

--
-- Table structure for table `link_accounts`
--

CREATE TABLE `link_accounts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `account_name` varchar(255) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `link_accounts`
--

INSERT INTO `link_accounts` (`id`, `account_number`, `account_name`, `bank_name`, `status`, `user_id`, `created_at`, `updated_at`) VALUES
(3, '12334', 'saving', 'allied', NULL, 4, '2025-07-25 06:43:54', '2025-07-25 06:43:54'),
(4, '12334', 'saving', 'allied', NULL, 4, '2025-07-25 09:31:40', '2025-07-25 09:31:40'),
(5, '12334', 'saving', 'allied', NULL, 4, '2025-07-25 09:33:48', '2025-07-25 09:33:48'),
(6, '4321', 'savingtest', 'sadapay', NULL, 6, '2025-09-03 05:53:58', '2025-09-03 05:53:58');

-- --------------------------------------------------------

--
-- Table structure for table `loan_applications`
--

CREATE TABLE `loan_applications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title_document` varchar(255) DEFAULT NULL,
  `upload_document` varchar(255) DEFAULT NULL,
  `beneficiary_name` varchar(255) DEFAULT NULL,
  `beneficiary_email` varchar(255) DEFAULT NULL,
  `beneficiary_relationship` varchar(255) DEFAULT NULL,
  `beneficiary_phone` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `mono_loan_calculation` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `loan_amount` double(10,2) DEFAULT NULL,
  `repayment_duration` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `loan_applications`
--

INSERT INTO `loan_applications` (`id`, `title_document`, `upload_document`, `beneficiary_name`, `beneficiary_email`, `beneficiary_relationship`, `beneficiary_phone`, `status`, `mono_loan_calculation`, `user_id`, `created_at`, `updated_at`, `loan_amount`, `repayment_duration`) VALUES
(11, 'Passport', 'loan_applications/1753444203.jpg', 'Ali', 'user@gmail.com', 'friend', '34234234', 'active', 12, 4, '2025-07-25 06:50:03', '2025-07-25 09:34:51', 2222.00, 5),
(12, 'Passport', 'loan_applications/1753453934.jpg', NULL, NULL, NULL, NULL, NULL, 12, 4, '2025-07-25 09:32:14', '2025-07-25 09:34:51', 2222.00, 5),
(13, 'Passport', 'loan_applications/1753454078.jpg', NULL, NULL, NULL, NULL, NULL, 12, 4, '2025-07-25 09:34:38', '2025-07-25 09:34:51', 2222.00, 5),
(14, 'Passport', 'loan_applications/1756898246.jpg', 'Bilal', 'bilal@gmail.com', 'close-friend', '8787888', NULL, 16, 6, '2025-09-03 06:17:26', '2025-09-03 06:28:03', 230000.00, 12);

-- --------------------------------------------------------

--
-- Table structure for table `loan_calculations`
--

CREATE TABLE `loan_calculations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `loan_amount` double(10,2) NOT NULL DEFAULT 0.00,
  `repayment_duration` int(11) NOT NULL,
  `status` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `repayment_date` date DEFAULT NULL,
  `product_amount` double(10,2) NOT NULL DEFAULT 0.00,
  `monthly_payment` double(10,2) NOT NULL DEFAULT 0.00,
  `interest_percentage` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `loan_calculations`
--

INSERT INTO `loan_calculations` (`id`, `loan_amount`, `repayment_duration`, `status`, `user_id`, `created_at`, `updated_at`, `repayment_date`, `product_amount`, `monthly_payment`, `interest_percentage`) VALUES
(3, 230000.00, 12, NULL, 4, '2025-07-25 06:44:09', '2025-07-25 06:44:09', '2025-08-25', 230000.00, 19166.67, 10),
(4, 230000.00, 12, NULL, 4, '2025-07-25 09:31:53', '2025-07-25 09:31:53', '2025-08-25', 230000.00, 19166.67, 10),
(5, 230000.00, 12, NULL, 4, '2025-07-25 09:34:03', '2025-07-25 09:34:03', '2025-08-25', 230000.00, 19166.67, 10),
(6, 230000.00, 12, NULL, 6, '2025-09-03 05:56:00', '2025-09-03 05:56:00', '2025-10-03', 830000.00, 19166.67, 10);

-- --------------------------------------------------------

--
-- Table structure for table `loan_distributeds`
--

CREATE TABLE `loan_distributeds` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `distribute_amount` double(10,2) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `reject_reason` varchar(255) DEFAULT NULL,
  `loan_calculation_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loan_distributes`
--

CREATE TABLE `loan_distributes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `distribute_amount` double(10,2) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `reject_reason` varchar(255) DEFAULT NULL,
  `loan_application_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `loan_distributes`
--

INSERT INTO `loan_distributes` (`id`, `distribute_amount`, `status`, `reject_reason`, `loan_application_id`, `created_at`, `updated_at`) VALUES
(1, 2000.00, 'active', NULL, 11, '2025-07-25 07:00:07', '2025-07-25 07:00:07');

-- --------------------------------------------------------

--
-- Table structure for table `loan_histories`
--

CREATE TABLE `loan_histories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `loan_application_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loan_installments`
--

CREATE TABLE `loan_installments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `status` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `mono_calculation_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `remaining_duration` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `loan_installments`
--

INSERT INTO `loan_installments` (`id`, `status`, `user_id`, `mono_calculation_id`, `created_at`, `updated_at`, `amount`, `remaining_duration`) VALUES
(7, 'paid', 4, 12, '2025-07-25 07:03:52', '2025-07-25 07:03:52', 120.00, -1),
(8, 'paid', 4, 12, '2025-07-25 07:04:43', '2025-07-25 07:04:43', 120.00, -2),
(9, NULL, 4, 12, '2025-07-25 07:12:30', '2025-07-25 07:12:30', 0.00, 5),
(10, NULL, 4, 12, '2025-07-25 07:14:51', '2025-07-25 07:14:51', 0.00, 5),
(11, NULL, 4, 12, '2025-07-25 07:16:32', '2025-07-25 07:16:32', 0.00, 5),
(12, NULL, 4, 12, '2025-07-25 09:32:15', '2025-07-25 09:32:15', 0.00, 5),
(13, 'paid', 4, 12, '2025-07-25 09:33:13', '2025-07-25 09:33:13', 120.00, 4),
(14, NULL, 4, 12, '2025-07-25 09:34:51', '2025-07-25 09:34:51', 0.00, 5),
(15, 'Active', 4, 12, '2025-07-26 06:18:05', '2025-07-26 06:18:05', 0.00, 0),
(16, NULL, 6, 16, '2025-09-03 06:28:04', '2025-09-03 06:28:04', 0.00, 12);

-- --------------------------------------------------------

--
-- Table structure for table `loan_repayments`
--

CREATE TABLE `loan_repayments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `amount` double(10,2) NOT NULL DEFAULT 0.00,
  `status` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `mono_calculation_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `loan_repayments`
--

INSERT INTO `loan_repayments` (`id`, `amount`, `status`, `user_id`, `mono_calculation_id`, `created_at`, `updated_at`) VALUES
(7, 120.00, NULL, 4, 12, '2025-07-25 07:03:52', '2025-07-25 07:03:52'),
(8, 120.00, NULL, 4, 12, '2025-07-25 07:04:43', '2025-07-25 07:04:43'),
(9, 120.00, NULL, 4, 12, '2025-07-25 09:33:13', '2025-07-25 09:33:13');

-- --------------------------------------------------------

--
-- Table structure for table `loan_statuses`
--

CREATE TABLE `loan_statuses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `send_status` varchar(255) NOT NULL DEFAULT 'pending',
  `send_date` date DEFAULT NULL,
  `approval_status` varchar(255) NOT NULL DEFAULT 'pending',
  `approval_date` date DEFAULT NULL,
  `disbursement_status` varchar(255) NOT NULL DEFAULT 'pending',
  `disbursement_date` date DEFAULT NULL,
  `loan_application_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `loan_statuses`
--

INSERT INTO `loan_statuses` (`id`, `send_status`, `send_date`, `approval_status`, `approval_date`, `disbursement_status`, `disbursement_date`, `loan_application_id`, `created_at`, `updated_at`) VALUES
(1, 'pending', NULL, 'pending', NULL, 'active', '2025-07-25', 11, '2025-07-25 06:55:33', '2025-07-25 07:00:07'),
(2, 'pending', NULL, 'pending', NULL, 'pending', NULL, 11, '2025-07-25 07:12:30', '2025-07-25 07:12:30'),
(3, 'pending', NULL, 'pending', NULL, 'pending', NULL, 11, '2025-07-25 07:14:51', '2025-07-25 07:14:51'),
(4, 'pending', NULL, 'pending', NULL, 'pending', NULL, 11, '2025-07-25 07:16:32', '2025-07-25 07:16:32'),
(5, 'pending', NULL, 'pending', NULL, 'pending', NULL, 11, '2025-07-25 09:32:15', '2025-07-25 09:32:15'),
(6, 'pending', NULL, 'pending', NULL, 'pending', NULL, 11, '2025-07-25 09:34:51', '2025-07-25 09:34:51'),
(7, 'pending', NULL, 'pending', NULL, 'pending', NULL, 14, '2025-09-03 06:28:04', '2025-09-03 06:28:04');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_reset_tokens_table', 1),
(3, '2019_08_19_000000_create_failed_jobs_table', 1),
(4, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(5, '2025_07_17_120151_create_wallets_table', 1),
(6, '2025_07_17_120257_create_categories_table', 1),
(7, '2025_07_17_120423_create_brands_table', 1),
(8, '2025_07_17_120822_create_products_table', 1),
(9, '2025_07_17_121211_create_product_details_table', 1),
(10, '2025_07_17_121453_create_product_images_table', 1),
(11, '2025_07_17_121608_create_product_reveiews_table', 1),
(12, '2025_07_17_132703_create_bundles_table', 1),
(13, '2025_07_17_132816_create_bundle_items_table', 1),
(14, '2025_07_17_182957_create_delivery_addresses_table', 1),
(15, '2025_07_18_072233_create_custom_services_table', 1),
(16, '2025_07_19_102848_create_terms_table', 1),
(17, '2025_07_19_103706_create_link_accounts_table', 1),
(18, '2025_07_19_104543_create_interest_percentages_table', 1),
(19, '2025_07_19_121945_create_loan_calculations_table', 1),
(20, '2025_07_19_122443_create_mono_loan_calculations_table', 1),
(21, '2025_07_19_122536_create_loan_applications_table', 1),
(22, '2025_07_19_122655_create_loan_histories_table', 1),
(23, '2025_07_19_123033_create_loan_installments_table', 1),
(24, '2025_07_19_123217_create_loan_repayments_table', 1),
(25, '2025_07_21_081330_update_loan_calculations_table', 1),
(26, '2025_07_21_103914_update_terms_table', 1),
(27, '2025_07_21_105509_update_loan_calculations_table', 1),
(28, '2025_07_21_113614_remove_interest_percentage_id_from_loan_calculations', 1),
(29, '2025_07_21_113925_update_loan_calculations_table', 1),
(30, '2025_07_21_124336_create_loan_distributeds_table', 1),
(31, '2025_07_21_132914_update_loan_application', 1),
(32, '2025_07_22_092207_add_loan_application_id_to_mono_loan_calculations_table', 2),
(34, '2025_07_22_102910_add_due_date_to_mono_loan_calculations_table', 3),
(38, '2025_07_22_184849_create_orders_table', 4),
(39, '2025_07_22_184903_create_order_items_table', 4),
(40, '2025_07_22_184913_create_cart_items_table', 4),
(41, '2025_07_23_090907_add_installation_price_to_orders_table', 5),
(42, '2025_07_23_135427_create_transactions_table', 6),
(43, '2025_07_22_175423_create_partners_table', 7),
(44, '2025_07_23_061920_create_notifications_table', 7),
(45, '2025_07_23_064216_create_tickets_table', 7),
(46, '2025_07_23_070421_create_ticket_messages_table', 7),
(47, '2025_07_23_075824_create_loan_statuses_table', 7),
(48, '2025_07_23_104821_update_mono_loan_calculaitions-table', 7),
(49, '2025_07_23_111932_create_credit_data_table', 7),
(50, '2025_07_23_113835_create_debt_statuses_table', 7),
(51, '2025_07_23_131650_create_jobs_table', 7),
(52, '2025_07_23_174607_create_loan_distributes_table', 7),
(53, '2025_07_23_181644_update_mono_loan_calculations_table', 7),
(54, '2025_07_23_192153_update_loan_installments_table', 7),
(55, '2025_07_24_092217_create_banners_table', 7),
(57, '2025_09_02_123337_update_bundles_table', 8);

-- --------------------------------------------------------

--
-- Table structure for table `mono_loan_calculations`
--

CREATE TABLE `mono_loan_calculations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `loan_application_id` bigint(20) UNSIGNED DEFAULT NULL,
  `down_payment` double(10,2) NOT NULL DEFAULT 0.00,
  `loan_calculation_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `loan_limit` double NOT NULL DEFAULT 0,
  `credit_score` varchar(255) DEFAULT NULL,
  `transcations` varchar(255) DEFAULT NULL,
  `loan_amount` double NOT NULL DEFAULT 0,
  `repayment_duration` int(11) NOT NULL DEFAULT 0,
  `status` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `mono_loan_calculations`
--

INSERT INTO `mono_loan_calculations` (`id`, `loan_application_id`, `down_payment`, `loan_calculation_id`, `created_at`, `updated_at`, `loan_limit`, `credit_score`, `transcations`, `loan_amount`, `repayment_duration`, `status`) VALUES
(12, 11, 1200.00, 3, '2025-07-25 06:45:13', '2025-07-25 09:34:51', 0, NULL, NULL, 1640, 12, 'active'),
(13, NULL, 1200.00, 3, '2025-07-25 09:32:02', '2025-07-25 09:32:02', 0, NULL, NULL, 230000, 12, NULL),
(14, NULL, 1200.00, 3, '2025-07-25 09:34:20', '2025-07-25 09:34:20', 0, NULL, NULL, 230000, 12, NULL),
(15, NULL, 12000.00, 3, '2025-09-03 06:01:52', '2025-09-03 06:01:52', 0, NULL, NULL, 230000, 12, NULL),
(16, 14, 12000.00, 6, '2025-09-03 06:13:02', '2025-09-03 06:28:04', 0, NULL, NULL, 230000, 12, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `message` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `message`, `created_at`, `updated_at`) VALUES
(1, 'The new sale is comminig', '2025-07-25 05:57:07', '2025-07-25 05:57:07');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `delivery_address_id` bigint(20) UNSIGNED DEFAULT NULL,
  `order_number` varchar(255) DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT 0.00,
  `payment_method` varchar(255) DEFAULT NULL,
  `payment_status` varchar(255) DEFAULT 'pending',
  `order_status` varchar(255) DEFAULT 'processing',
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `installation_price` decimal(10,2) DEFAULT 0.00,
  `mono_calculation_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `delivery_address_id`, `order_number`, `total_price`, `payment_method`, `payment_status`, `order_status`, `note`, `created_at`, `updated_at`, `installation_price`, `mono_calculation_id`) VALUES
(64, 4, 4, 'NQ4RGORFFC', 3599.96, 'direct', 'pending', 'processing', NULL, '2025-07-25 05:25:56', '2025-07-25 05:25:56', 0.00, NULL),
(65, 4, 4, '6S6IXDITEV', 3599.96, 'direct', 'pending', 'processing', NULL, '2025-07-25 06:19:46', '2025-07-25 06:19:46', 0.00, NULL),
(66, 4, 4, '56MD5BLMIU', 3599.96, 'direct', 'pending', 'processing', NULL, '2025-07-25 07:07:02', '2025-07-25 07:07:02', 0.00, NULL),
(67, 4, 4, 'S5RTLDNGKV', 3599.96, 'direct', 'pending', 'processing', NULL, '2025-07-25 10:26:36', '2025-07-25 10:26:36', 0.00, NULL),
(68, 4, 4, 'IFDSCFHUPJ', 3599.96, 'direct', 'pending', 'processing', NULL, '2025-07-25 10:26:39', '2025-07-25 10:26:39', 0.00, NULL),
(69, 4, 4, 'ZHDXHT8ZDL', 3599.96, 'direct', 'pending', 'processing', NULL, '2025-07-25 10:30:19', '2025-07-25 10:30:19', 0.00, NULL),
(70, 4, 4, 'PWRMGTARWM', 3599.96, 'direct', 'pending', 'processing', NULL, '2025-07-25 10:30:21', '2025-07-25 10:30:22', 0.00, NULL),
(71, 4, 4, '3DHDXP2XMO', 3599.96, 'direct', 'paid', 'processing', NULL, '2025-07-26 02:42:34', '2025-07-26 02:42:34', 0.00, NULL),
(72, 4, 4, 'UCJ7OPZRT1', 3599.96, 'direct', 'paid', 'processing', NULL, '2025-07-26 03:16:38', '2025-07-26 03:16:38', 0.00, NULL),
(73, 4, 4, 'MHJEF7UDZN', 3599.96, 'direct', 'paid', 'processing', NULL, '2025-07-26 03:17:21', '2025-07-26 03:17:21', 0.00, NULL),
(74, 4, 4, 'KLVJ172266', 3599.96, 'direct', 'paid', 'pending', NULL, '2025-07-26 03:44:40', '2025-07-26 03:44:40', 0.00, NULL),
(75, 4, 4, 'I7YT2YGCKU', 3599.96, 'withdrawal', 'paid', 'pending', NULL, '2025-07-26 04:46:35', '2025-07-26 04:46:35', 0.00, NULL),
(76, 6, 4, 'UUWNRIGNYH', 7199.92, 'direct', 'paid', 'pending', NULL, '2025-09-02 09:34:29', '2025-09-02 09:34:29', 0.00, NULL),
(77, 6, 4, 'SJT6GVVKUK', 10799.88, 'direct', 'paid', 'pending', NULL, '2025-09-02 09:36:30', '2025-09-02 09:36:31', 0.00, NULL),
(78, 4, 4, 'W00WTYDNXR', 3599.96, 'direct', 'paid', 'pending', NULL, '2025-09-02 11:49:33', '2025-09-02 11:49:34', 0.00, NULL),
(79, 4, 4, '32K86XPUYM', 3599.96, 'direct', 'paid', 'pending', NULL, '2025-09-02 11:50:55', '2025-09-02 11:50:56', 0.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `itemable_type` varchar(255) DEFAULT NULL,
  `itemable_id` bigint(20) UNSIGNED DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `unit_price` decimal(10,2) DEFAULT 0.00,
  `subtotal` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `itemable_type`, `itemable_id`, `quantity`, `unit_price`, `subtotal`, `created_at`, `updated_at`) VALUES
(64, 64, 'App\\Models\\Bundles', 1, 4, 899.99, 3599.96, '2025-07-25 05:25:56', '2025-07-25 05:25:56'),
(65, 65, 'App\\Models\\Bundles', 1, 4, 899.99, 3599.96, '2025-07-25 06:19:46', '2025-07-25 06:19:46'),
(66, 66, 'App\\Models\\Bundles', 1, 4, 899.99, 3599.96, '2025-07-25 07:07:02', '2025-07-25 07:07:02'),
(67, 67, 'App\\Models\\Bundles', 1, 4, 899.99, 3599.96, '2025-07-25 10:26:36', '2025-07-25 10:26:36'),
(68, 68, 'App\\Models\\Bundles', 1, 4, 899.99, 3599.96, '2025-07-25 10:26:39', '2025-07-25 10:26:39'),
(69, 69, 'App\\Models\\Bundles', 1, 4, 899.99, 3599.96, '2025-07-25 10:30:19', '2025-07-25 10:30:19'),
(70, 70, 'App\\Models\\Bundles', 1, 4, 899.99, 3599.96, '2025-07-25 10:30:21', '2025-07-25 10:30:21'),
(71, 71, 'App\\Models\\Bundles', 1, 4, 899.99, 3599.96, '2025-07-26 02:42:34', '2025-07-26 02:42:34'),
(72, 72, 'App\\Models\\Bundles', 1, 4, 899.99, 3599.96, '2025-07-26 03:16:38', '2025-07-26 03:16:38'),
(73, 73, 'App\\Models\\Bundles', 1, 4, 899.99, 3599.96, '2025-07-26 03:17:21', '2025-07-26 03:17:21'),
(74, 74, 'App\\Models\\Bundles', 1, 4, 899.99, 3599.96, '2025-07-26 03:44:40', '2025-07-26 03:44:40'),
(75, 75, 'App\\Models\\Bundles', 1, 4, 899.99, 3599.96, '2025-07-26 04:46:35', '2025-07-26 04:46:35'),
(76, 76, 'App\\Models\\Bundles', 1, 8, 899.99, 7199.92, '2025-09-02 09:34:29', '2025-09-02 09:34:29'),
(77, 77, 'App\\Models\\Bundles', 1, 8, 899.99, 7199.92, '2025-09-02 09:36:30', '2025-09-02 09:36:30'),
(78, 77, 'App\\Models\\Bundles', 1, 4, 899.99, 3599.96, '2025-09-02 09:36:31', '2025-09-02 09:36:31'),
(79, 78, 'App\\Models\\Bundles', 1, 4, 899.99, 3599.96, '2025-09-02 11:49:34', '2025-09-02 11:49:34'),
(80, 79, 'App\\Models\\Bundles', 1, 4, 899.99, 3599.96, '2025-09-02 11:50:56', '2025-09-02 11:50:56');

-- --------------------------------------------------------

--
-- Table structure for table `partners`
--

CREATE TABLE `partners` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `no_of_loans` int(11) DEFAULT NULL,
  `amount` double(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 'App\\Models\\User', 1, 'API Token', '9188e1bf44136a9f09ebc83af6072aa6e3d1cd28a6822ba2e112bb94ffa11755', '[\"*\"]', '2025-07-24 07:48:56', NULL, '2025-07-22 02:54:41', '2025-07-24 07:48:56'),
(2, 'App\\Models\\User', 3, 'API Token', '99f5246985f7d2cbdd7012812639a5b37edcf69844bf158af6b0a5a479e5c673', '[\"*\"]', NULL, NULL, '2025-07-24 12:37:45', '2025-07-24 12:37:45'),
(3, 'App\\Models\\User', 3, 'API Token', '627da7103c342e9f309fb7c589b1c8d65231b9e6da9738a0e96150083ae209cf', '[\"*\"]', '2025-07-24 14:18:53', NULL, '2025-07-24 12:37:49', '2025-07-24 14:18:53'),
(4, 'App\\Models\\User', 4, 'API Token', 'bc45f5f6eea9db712c1bac45a4f8a6da95944f34e495af160b73a9aa1089957f', '[\"*\"]', '2025-09-02 11:47:41', NULL, '2025-07-25 05:13:42', '2025-09-02 11:47:41'),
(5, 'App\\Models\\User', 4, 'API Token', '703968cdc542d5f920fd659312d76ce7b814dc8104d00fec7bd87936762a8fbf', '[\"*\"]', NULL, NULL, '2025-07-25 09:11:00', '2025-07-25 09:11:00'),
(6, 'App\\Models\\User', 4, 'API Token', '748f65dae264d82cea8ff3f2977cee4542b0d43738c348ba2d0ac04b1b2e03bb', '[\"*\"]', '2025-09-02 11:50:55', NULL, '2025-07-25 09:12:28', '2025-09-02 11:50:55'),
(7, 'App\\Models\\User', 6, 'API Token', 'bc6d1261ef52c4966db408d41f8fd64404d0c83620c9a108c9a67e426bf4abfa', '[\"*\"]', '2025-09-03 06:40:55', NULL, '2025-09-01 09:57:32', '2025-09-03 06:40:55');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `brand_id` bigint(20) UNSIGNED DEFAULT NULL,
  `price` double NOT NULL DEFAULT 0,
  `discount_price` double NOT NULL DEFAULT 0,
  `discount_end_date` date DEFAULT NULL,
  `stock` varchar(255) DEFAULT NULL,
  `installation_price` double DEFAULT NULL,
  `top_deal` tinyint(1) NOT NULL DEFAULT 0,
  `installation_compulsory` tinyint(1) NOT NULL DEFAULT 0,
  `featured_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `title`, `category_id`, `brand_id`, `price`, `discount_price`, `discount_end_date`, `stock`, `installation_price`, `top_deal`, `installation_compulsory`, `featured_image`, `created_at`, `updated_at`) VALUES
(1, 'fridge', 1, 7, 1000, 100, '2025-07-20', '19', NULL, 0, 0, NULL, '2025-07-22 02:55:56', '2025-07-22 02:55:56'),
(2, 'fridge', 1, 1, 1000, 100, '2025-07-20', '19', NULL, 0, 0, NULL, '2025-07-22 02:56:08', '2025-07-22 02:56:08'),
(3, 'fridge', 1, 1, 1000, 100, '2025-07-20', '19', NULL, 0, 0, NULL, '2025-07-22 02:56:10', '2025-07-22 02:56:10'),
(4, 'fridge', 1, 1, 1000, 100, '2025-07-20', '19', NULL, 0, 0, NULL, '2025-07-22 02:58:02', '2025-07-22 02:58:02'),
(5, 'fridge', 1, 1, 1000, 100, '2025-07-20', '19', NULL, 0, 0, NULL, '2025-07-22 02:58:05', '2025-07-22 02:58:05'),
(6, 'fridge', 1, 1, 1000, 100, '2025-07-20', '19', NULL, 0, 0, NULL, '2025-07-25 06:03:39', '2025-07-25 06:03:39'),
(7, 'fridge', 1, 1, 1000, 100, '2025-07-20', '19', NULL, 0, 0, NULL, '2025-07-25 06:03:41', '2025-07-25 06:03:41'),
(8, 'fridge', 1, 1, 1000, 100, '2025-07-20', '19', NULL, 0, 0, NULL, '2025-07-25 06:03:42', '2025-07-25 06:03:42'),
(9, 'fridge', 1, 1, 1000, 100, '2025-07-20', '19', NULL, 0, 0, NULL, '2025-07-25 06:03:44', '2025-07-25 06:03:44'),
(10, 'fridge', 1, 1, 1000, 100, '2025-07-20', '19', NULL, 0, 0, NULL, '2025-07-25 06:03:46', '2025-07-25 06:03:46'),
(11, 'fridge', 1, 1, 1000, 100, '2025-07-20', '19', NULL, 0, 0, NULL, '2025-07-25 06:03:47', '2025-07-25 06:03:47'),
(12, 'fridge', 1, 1, 1000, 100, '2025-07-20', '19', NULL, 0, 0, NULL, '2025-07-25 06:03:51', '2025-07-25 06:03:51');

-- --------------------------------------------------------

--
-- Table structure for table `product_details`
--

CREATE TABLE `product_details` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `detail` varchar(255) DEFAULT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_reveiews`
--

CREATE TABLE `product_reveiews` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `review` text NOT NULL,
  `rating` varchar(255) NOT NULL DEFAULT '5',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_reveiews`
--

INSERT INTO `product_reveiews` (`id`, `product_id`, `user_id`, `review`, `rating`, `created_at`, `updated_at`) VALUES
(4, 1, 4, 'This is amazing!', '5', '2025-07-25 05:22:50', '2025-07-25 05:22:50');

-- --------------------------------------------------------

--
-- Table structure for table `terms`
--

CREATE TABLE `terms` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `content` longtext DEFAULT NULL,
  `check` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `terms`
--

INSERT INTO `terms` (`id`, `content`, `check`, `created_at`, `updated_at`, `type`) VALUES
(1, 'hey', 0, '2025-07-25 06:12:21', '2025-07-25 06:12:21', 'proceed'),
(2, 'hey', 0, '2025-07-25 06:12:28', '2025-07-25 06:12:28', 'second'),
(3, 'hey', 0, '2025-07-25 06:12:34', '2025-07-25 06:12:34', 'third');

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`id`, `user_id`, `subject`, `status`, `created_at`, `updated_at`) VALUES
(1, 4, 'Login issue', 'pending', '2025-07-25 08:03:31', '2025-07-25 08:03:31');

-- --------------------------------------------------------

--
-- Table structure for table `ticket_messages`
--

CREATE TABLE `ticket_messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ticket_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `message` text NOT NULL,
  `sender` enum('user','admin') NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ticket_messages`
--

INSERT INTO `ticket_messages` (`id`, `ticket_id`, `user_id`, `message`, `sender`, `created_at`, `updated_at`) VALUES
(1, 1, 4, 'Teh login is not working', 'user', '2025-07-25 08:03:31', '2025-07-25 08:03:31'),
(2, 1, NULL, 'Your message has been received. Our support team will get back to you shortly.', 'admin', '2025-07-25 08:03:31', '2025-07-25 08:03:31');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `status` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `method` varchar(255) DEFAULT NULL,
  `transacted_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `title`, `amount`, `status`, `type`, `method`, `transacted_at`, `user_id`, `created_at`, `updated_at`) VALUES
(172, 'Order #3DHDXP2XMO', 3599.96, 'paid', 'deposit', 'direct', '2025-07-26 02:42:34', 4, '2025-07-26 02:42:54', '2025-07-26 02:42:54'),
(173, 'Order #UCJ7OPZRT1', 3599.96, 'paid', 'deposit', 'direct', '2025-07-26 03:16:38', 4, '2025-07-26 03:16:50', '2025-07-26 03:16:50'),
(174, 'Order #MHJEF7UDZN', 3599.96, 'paid', 'deposit', 'direct', '2025-07-26 03:17:21', 4, '2025-07-26 03:20:32', '2025-07-26 03:20:32'),
(175, 'Order #KLVJ172266', 3599.96, 'paid', 'deposit', 'direct', '2025-07-26 03:44:40', 4, '2025-07-26 07:50:35', '2025-07-26 07:50:35'),
(176, 'Order #I7YT2YGCKU', 3599.96, 'paid', 'deposit', 'withdrawal', '2025-07-26 04:46:35', 4, '2025-07-26 07:50:35', '2025-07-26 07:50:35');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `sur_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `refferal_code` varchar(255) DEFAULT NULL,
  `user_code` varchar(255) DEFAULT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'user',
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `otp` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `sur_name`, `email`, `email_verified_at`, `password`, `phone`, `profile_picture`, `refferal_code`, `user_code`, `role`, `is_verified`, `is_active`, `otp`, `remember_token`, `created_at`, `updated_at`) VALUES
(4, 'bilal', 'hafiz', 'hamzahafiz306@gmail.com', NULL, '$2y$12$Qcv9GcKmOOWuTcZW1ruIpeOTCBKINJhVZq2onOOVqGrQDdQzTC0bK', '1234567890', NULL, 'ref1123', 'hamza348', 'Admin', 0, 1, '6441', NULL, '2025-07-25 05:13:29', '2025-07-25 05:14:52'),
(5, 'hamqza', 'hafiz', 'malik34@gmail.com', NULL, '$2y$12$IzgV5kiNWn6dWphLlE1oU.5pKVBmQtdYNjdZqSTs4BCfRXNsDzAtu', '1234567890', NULL, 'ref1123', 'hamqza873', 'Admin', 0, 1, '1207', NULL, '2025-07-25 10:08:41', '2025-07-25 10:08:41'),
(6, 'bilal', 'shahbaz', 'hoyaxa4776@besaies.com', NULL, '$2y$12$2Y3jDb5xfmAnPGGZlrSOp.z76Debf08DHpaaZOEgpqssA8p8.xwWa', '03206440155', NULL, 'reff222', 'bilal133', 'Admin', 0, 1, '4411', NULL, '2025-09-01 09:54:08', '2025-09-01 09:54:08');

-- --------------------------------------------------------

--
-- Table structure for table `wallets`
--

CREATE TABLE `wallets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `loan_balance` varchar(255) DEFAULT NULL,
  `shop_balance` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wallets`
--

INSERT INTO `wallets` (`id`, `status`, `loan_balance`, `shop_balance`, `user_id`, `created_at`, `updated_at`) VALUES
(3, 'active', '2000', NULL, 4, '2025-07-25 05:13:29', '2025-07-25 07:00:07'),
(4, 'active', NULL, NULL, 5, '2025-07-25 10:08:41', '2025-07-25 10:08:41'),
(5, 'active', NULL, NULL, 6, '2025-09-01 09:54:08', '2025-09-01 09:54:08');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`),
  ADD KEY `brands_category_id_foreign` (`category_id`);

--
-- Indexes for table `bundles`
--
ALTER TABLE `bundles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bundle_items`
--
ALTER TABLE `bundle_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bundle_items_product_id_foreign` (`product_id`),
  ADD KEY `bundle_items_bundle_id_foreign` (`bundle_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cart_items_user_id_foreign` (`user_id`),
  ADD KEY `cart_items_itemable_type_itemable_id_index` (`itemable_type`,`itemable_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `credit_data`
--
ALTER TABLE `credit_data`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `custom_services`
--
ALTER TABLE `custom_services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `custom_services_bundle_id_foreign` (`bundle_id`);

--
-- Indexes for table `debt_statuses`
--
ALTER TABLE `debt_statuses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `delivery_addresses`
--
ALTER TABLE `delivery_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `delivery_addresses_user_id_foreign` (`user_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `interest_percentages`
--
ALTER TABLE `interest_percentages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `link_accounts`
--
ALTER TABLE `link_accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `link_accounts_user_id_foreign` (`user_id`);

--
-- Indexes for table `loan_applications`
--
ALTER TABLE `loan_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_applications_mono_loan_calculation_foreign` (`mono_loan_calculation`),
  ADD KEY `loan_applications_user_id_foreign` (`user_id`);

--
-- Indexes for table `loan_calculations`
--
ALTER TABLE `loan_calculations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_calculations_user_id_foreign` (`user_id`);

--
-- Indexes for table `loan_distributeds`
--
ALTER TABLE `loan_distributeds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_distributeds_loan_calculation_id_foreign` (`loan_calculation_id`);

--
-- Indexes for table `loan_distributes`
--
ALTER TABLE `loan_distributes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_distributes_loan_application_id_foreign` (`loan_application_id`);

--
-- Indexes for table `loan_histories`
--
ALTER TABLE `loan_histories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_histories_user_id_foreign` (`user_id`),
  ADD KEY `loan_histories_loan_application_id_foreign` (`loan_application_id`);

--
-- Indexes for table `loan_installments`
--
ALTER TABLE `loan_installments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_installments_user_id_foreign` (`user_id`),
  ADD KEY `loan_installments_mono_calculation_id_foreign` (`mono_calculation_id`);

--
-- Indexes for table `loan_repayments`
--
ALTER TABLE `loan_repayments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_repayments_user_id_foreign` (`user_id`),
  ADD KEY `loan_repayments_mono_calculation_id_foreign` (`mono_calculation_id`);

--
-- Indexes for table `loan_statuses`
--
ALTER TABLE `loan_statuses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_statuses_loan_application_id_foreign` (`loan_application_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mono_loan_calculations`
--
ALTER TABLE `mono_loan_calculations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mono_loan_calculations_loan_calculation_id_foreign` (`loan_calculation_id`),
  ADD KEY `mono_loan_calculations_loan_application_id_foreign` (`loan_application_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `orders_order_number_unique` (`order_number`),
  ADD KEY `orders_user_id_foreign` (`user_id`),
  ADD KEY `orders_delivery_address_id_foreign` (`delivery_address_id`),
  ADD KEY `orders_mono_calculation_id_foreign` (`mono_calculation_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_items_order_id_foreign` (`order_id`),
  ADD KEY `order_items_itemable_type_itemable_id_index` (`itemable_type`,`itemable_id`);

--
-- Indexes for table `partners`
--
ALTER TABLE `partners`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `partners_email_unique` (`email`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `products_category_id_foreign` (`category_id`),
  ADD KEY `products_brand_id_foreign` (`brand_id`);

--
-- Indexes for table `product_details`
--
ALTER TABLE `product_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_details_product_id_foreign` (`product_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_images_product_id_foreign` (`product_id`);

--
-- Indexes for table `product_reveiews`
--
ALTER TABLE `product_reveiews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_reveiews_product_id_foreign` (`product_id`),
  ADD KEY `product_reveiews_user_id_foreign` (`user_id`);

--
-- Indexes for table `terms`
--
ALTER TABLE `terms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tickets_user_id_foreign` (`user_id`);

--
-- Indexes for table `ticket_messages`
--
ALTER TABLE `ticket_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_messages_ticket_id_foreign` (`ticket_id`),
  ADD KEY `ticket_messages_user_id_foreign` (`user_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transactions_user_id_foreign` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Indexes for table `wallets`
--
ALTER TABLE `wallets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wallets_user_id_foreign` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `banners`
--
ALTER TABLE `banners`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `bundles`
--
ALTER TABLE `bundles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `bundle_items`
--
ALTER TABLE `bundle_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=124;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `credit_data`
--
ALTER TABLE `credit_data`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `custom_services`
--
ALTER TABLE `custom_services`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `debt_statuses`
--
ALTER TABLE `debt_statuses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `delivery_addresses`
--
ALTER TABLE `delivery_addresses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `interest_percentages`
--
ALTER TABLE `interest_percentages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `link_accounts`
--
ALTER TABLE `link_accounts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `loan_applications`
--
ALTER TABLE `loan_applications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `loan_calculations`
--
ALTER TABLE `loan_calculations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `loan_distributeds`
--
ALTER TABLE `loan_distributeds`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `loan_distributes`
--
ALTER TABLE `loan_distributes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `loan_histories`
--
ALTER TABLE `loan_histories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `loan_installments`
--
ALTER TABLE `loan_installments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `loan_repayments`
--
ALTER TABLE `loan_repayments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `loan_statuses`
--
ALTER TABLE `loan_statuses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `mono_loan_calculations`
--
ALTER TABLE `mono_loan_calculations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `partners`
--
ALTER TABLE `partners`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `product_details`
--
ALTER TABLE `product_details`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_reveiews`
--
ALTER TABLE `product_reveiews`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `terms`
--
ALTER TABLE `terms`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ticket_messages`
--
ALTER TABLE `ticket_messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=177;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `wallets`
--
ALTER TABLE `wallets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `brands`
--
ALTER TABLE `brands`
  ADD CONSTRAINT `brands_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bundle_items`
--
ALTER TABLE `bundle_items`
  ADD CONSTRAINT `bundle_items_bundle_id_foreign` FOREIGN KEY (`bundle_id`) REFERENCES `bundles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bundle_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `custom_services`
--
ALTER TABLE `custom_services`
  ADD CONSTRAINT `custom_services_bundle_id_foreign` FOREIGN KEY (`bundle_id`) REFERENCES `bundles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `delivery_addresses`
--
ALTER TABLE `delivery_addresses`
  ADD CONSTRAINT `delivery_addresses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `link_accounts`
--
ALTER TABLE `link_accounts`
  ADD CONSTRAINT `link_accounts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loan_applications`
--
ALTER TABLE `loan_applications`
  ADD CONSTRAINT `loan_applications_mono_loan_calculation_foreign` FOREIGN KEY (`mono_loan_calculation`) REFERENCES `mono_loan_calculations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `loan_applications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loan_calculations`
--
ALTER TABLE `loan_calculations`
  ADD CONSTRAINT `loan_calculations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loan_distributeds`
--
ALTER TABLE `loan_distributeds`
  ADD CONSTRAINT `loan_distributeds_loan_calculation_id_foreign` FOREIGN KEY (`loan_calculation_id`) REFERENCES `loan_calculations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loan_distributes`
--
ALTER TABLE `loan_distributes`
  ADD CONSTRAINT `loan_distributes_loan_application_id_foreign` FOREIGN KEY (`loan_application_id`) REFERENCES `loan_applications` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loan_histories`
--
ALTER TABLE `loan_histories`
  ADD CONSTRAINT `loan_histories_loan_application_id_foreign` FOREIGN KEY (`loan_application_id`) REFERENCES `loan_applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `loan_histories_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loan_installments`
--
ALTER TABLE `loan_installments`
  ADD CONSTRAINT `loan_installments_mono_calculation_id_foreign` FOREIGN KEY (`mono_calculation_id`) REFERENCES `mono_loan_calculations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `loan_installments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loan_repayments`
--
ALTER TABLE `loan_repayments`
  ADD CONSTRAINT `loan_repayments_mono_calculation_id_foreign` FOREIGN KEY (`mono_calculation_id`) REFERENCES `mono_loan_calculations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `loan_repayments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loan_statuses`
--
ALTER TABLE `loan_statuses`
  ADD CONSTRAINT `loan_statuses_loan_application_id_foreign` FOREIGN KEY (`loan_application_id`) REFERENCES `loan_applications` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mono_loan_calculations`
--
ALTER TABLE `mono_loan_calculations`
  ADD CONSTRAINT `mono_loan_calculations_loan_application_id_foreign` FOREIGN KEY (`loan_application_id`) REFERENCES `loan_applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mono_loan_calculations_loan_calculation_id_foreign` FOREIGN KEY (`loan_calculation_id`) REFERENCES `loan_calculations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_delivery_address_id_foreign` FOREIGN KEY (`delivery_address_id`) REFERENCES `delivery_addresses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_mono_calculation_id_foreign` FOREIGN KEY (`mono_calculation_id`) REFERENCES `mono_loan_calculations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_details`
--
ALTER TABLE `product_details`
  ADD CONSTRAINT `product_details_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_reveiews`
--
ALTER TABLE `product_reveiews`
  ADD CONSTRAINT `product_reveiews_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_reveiews_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `ticket_messages`
--
ALTER TABLE `ticket_messages`
  ADD CONSTRAINT `ticket_messages_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ticket_messages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wallets`
--
ALTER TABLE `wallets`
  ADD CONSTRAINT `wallets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
