-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 04, 2025 at 01:30 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hatrox_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `product_id`, `quantity`) VALUES
(66, 12, 42, 3),
(67, 12, 43, 1),
(82, 14, 24, 1),
(88, 11, 43, 3),
(90, 11, 40, 4),
(91, 11, 36, 2);

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `comment_text` text NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_approved` tinyint(1) NOT NULL DEFAULT 0,
  `is_hidden` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `user_id`, `user_name`, `email`, `comment_text`, `rating`, `created_at`, `is_approved`, `is_hidden`) VALUES
(2, 4, 'rudra', 'rudra@hatrox.com', 'This all above product are very beautiful', 5, '2025-09-12 17:41:45', 1, 0),
(4, 4, 'ronak jikadra', 'ronak@hatrox.com', 'amzingg!!!', 3, '2025-09-12 17:43:53', 1, 0),
(5, 4, 'harsh', 'harsh@hatrox.com', 'wahhhh su web site che', 5, '2025-09-12 17:44:19', 1, 1),
(7, NULL, 'Rutvik Kaklotar', 'rutvik@hatrox.com', 'ખૂબ સરસ', 5, '2025-11-25 10:52:44', 1, 0),
(13, 12, 'chamkadar', 'chamkadar@hatrox.com', 'astonishing and incredible website ........', 5, '2025-11-26 13:02:02', 1, 0),
(14, 15, 'chuuby cheeks', 'chuuby@hatrox.com', 'માતાજી સુખી રાખે ત્રણેય ને હો 🤓🤓', 3, '2025-11-26 21:20:03', 1, 1),
(15, 15, 'chuuby cheeks', 'chuuby@hatrox.com', 'very nice personality you have 😃', 5, '2025-11-26 21:22:19', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `status`, `created_at`) VALUES
(1, 2, 13093.00, 'completed', '2025-09-12 16:23:23'),
(2, 1, 20.00, 'cancelled', '2025-09-12 16:43:55'),
(3, 1, 433.00, 'completed', '2025-09-12 16:48:43'),
(4, 5, 503.00, 'completed', '2025-09-12 22:17:42'),
(5, 5, 200.00, 'completed', '2025-09-12 22:36:07'),
(6, 2, 404.00, 'completed', '2025-09-13 10:36:59'),
(7, 5, 2340.00, 'completed', '2025-09-23 08:47:56'),
(8, 5, 200.00, 'completed', '2025-09-23 08:48:32'),
(10, 2, 400.00, 'completed', '2025-09-23 08:52:57'),
(12, 8, 1100.00, 'completed', '2025-09-25 16:39:09'),
(13, 8, 140.00, 'completed', '2025-09-25 16:41:55'),
(14, 8, 300.00, 'completed', '2025-09-25 16:44:46'),
(15, 4, 27591.00, 'completed', '2025-09-25 18:36:48'),
(16, 2, 15394.00, 'completed', '2025-09-25 18:40:44'),
(17, 2, 200.00, 'completed', '2025-09-25 18:42:11'),
(18, 5, 42888.00, 'completed', '2025-09-25 18:44:12'),
(19, 9, 12871.00, 'completed', '2025-09-25 18:49:37'),
(20, 10, 3699.00, 'completed', '2025-11-25 10:55:49'),
(21, 10, 5698.00, 'completed', '2025-11-25 10:58:04'),
(22, 11, 1560.00, 'completed', '2025-11-25 15:02:52'),
(23, 11, 1999.00, 'completed', '2025-11-25 15:10:44'),
(24, 12, 148.75, 'cancelled', '2025-11-25 17:46:18'),
(25, 12, 1601.11, 'completed', '2025-11-25 17:56:49'),
(26, 10, 158243.22, 'cancelled', '2025-11-25 22:57:40'),
(27, 12, 2293.38, 'cancelled', '2025-11-26 13:05:49'),
(28, 12, 1999.00, 'cancelled', '2025-11-26 13:08:44'),
(29, 12, 3674.79, 'processing', '2025-11-26 13:51:29'),
(30, 14, 9780.00, 'completed', '2025-11-26 20:40:24'),
(31, 14, 4890.00, 'cancelled', '2025-11-26 20:42:21'),
(32, 14, 1216.80, 'completed', '2025-11-26 20:58:02'),
(33, 15, 90.00, 'completed', '2025-11-26 21:17:15'),
(34, 11, 8981.58, 'processing', '2025-12-04 17:27:10');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_at_time` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price_at_time`) VALUES
(10, 7, 22, 3, 780.00),
(11, 8, 24, 2, 100.00),
(14, 10, 24, 1, 100.00),
(15, 10, 25, 2, 150.00),
(18, 12, 30, 5, 100.00),
(19, 12, 29, 2, 300.00),
(20, 13, 27, 2, 70.00),
(21, 14, 24, 3, 100.00),
(22, 15, 39, 6, 3699.00),
(23, 15, 34, 3, 1799.00),
(24, 16, 40, 4, 1999.00),
(25, 16, 39, 2, 3699.00),
(26, 17, 30, 2, 100.00),
(27, 18, 38, 7, 3099.00),
(28, 18, 27, 3, 70.00),
(29, 18, 35, 15, 1399.00),
(30, 19, 29, 2, 300.00),
(31, 19, 39, 2, 3699.00),
(32, 19, 40, 2, 1999.00),
(33, 19, 26, 3, 175.00),
(34, 19, 27, 5, 70.00),
(35, 20, 39, 1, 3699.00),
(36, 21, 40, 1, 1999.00),
(37, 21, 39, 1, 3699.00),
(38, 22, 22, 2, 780.00),
(39, 23, 40, 1, 1999.00),
(40, 24, 26, 1, 148.75),
(41, 25, 34, 1, 1601.11),
(42, 26, 39, 69, 2293.38),
(43, 27, 39, 1, 2293.38),
(44, 28, 40, 1, 1999.00),
(45, 29, 35, 2, 685.51),
(46, 29, 36, 1, 2155.02),
(47, 29, 26, 1, 148.75),
(48, 30, 43, 2, 4890.00),
(49, 31, 42, 1, 4890.00),
(50, 32, 22, 2, 608.40),
(51, 33, 30, 1, 90.00),
(52, 34, 36, 2, 2155.02),
(53, 34, 37, 3, 1557.18);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `discount_percent` int(11) NOT NULL DEFAULT 0,
  `image_url` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `sold_count` int(11) NOT NULL DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `status` enum('active','pending') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `discount_percent`, `image_url`, `category`, `stock_quantity`, `sold_count`, `created_by`, `status`, `created_at`) VALUES
(22, 'Celestial Star Necklace', 'Silver necklace featuring a shimmering star pendant, perfect for evenings and special occasions.', 780.00, 22, '/hatrox-project/assets/uploads/a78b5d510769b737.jpg', 'Necklace', 3, 8, 1, 'active', '2025-09-23 08:32:32'),
(24, 'Midnight Moon Ring', 'Classic gold band with a polished black enamel moon design suitable for daily wear.', 100.00, 3, '/hatrox-project/assets/uploads/0cd2b6cbf2c1efed.jpg', 'Ring', 7, 6, 1, 'active', '2025-09-23 08:35:20'),
(25, 'Ocean Wave Bracelet', 'Sterling silver bracelet inspired by the ocean’s waves, adorned with blue topaz gems.', 150.00, 8, '/hatrox-project/assets/uploads/1898e7ee9c376027.jpg', 'Bracelet', 6, 2, 1, 'active', '2025-09-23 08:36:41'),
(26, 'Blossom Charm Pendant', 'Gold pendant shaped like a blooming flower with pink enamel accents, perfect for spring styles.', 175.00, 15, '/hatrox-project/assets/uploads/517773e6b3c3f1d0.jpg', 'Pendant', 3, 5, 1, 'active', '2025-09-23 08:37:55'),
(27, 'Radiant Heart Bracelet', 'Gold heart-shaped locket engraved with intricate patterns, a charming gift idea.', 70.00, 25, '/hatrox-project/assets/uploads/cbe3c232872f8930.jpg', 'Bracelet', 3, 10, 1, 'active', '2025-09-23 08:42:45'),
(28, 'Celeste Drop Earrings', 'Delicate drop earrings with cascading crystal droplets for refined elegance.', 143.00, 69, '/hatrox-project/assets/uploads/7e0650bd371c2f19.jpg', 'Earrings', 12, 4, 1, 'active', '2025-09-23 08:44:03'),
(29, 'Rose Quartz Bracelet', 'Soft pink quartz beads strung on elastic, great for everyday wear and wellness.', 300.00, 21, '/hatrox-project/assets/uploads/9a4cb7b97c0d798f.jpg', 'Bracelet', 8, 4, 1, 'active', '2025-09-23 08:45:27'),
(30, 'Rose Quartz Bracelet', 'gfdhfgh', 100.00, 10, '/hatrox-project/assets/uploads/2f1c1374e2ebffcd.jpg', 'Bracelet', 2, 8, 1, 'active', '2025-09-23 09:22:31'),
(34, 'Radiant Heart Pendant', 'Beautifully sculpted gold heart pendant bordered with shimmering white stones, a perfect gift for loved ones and special occasions.', 1799.00, 11, '/hatrox-project/assets/uploads/e9aa3530add9502c.jpg', 'Necklace', 15, 4, 1, 'active', '2025-09-25 17:37:33'),
(35, 'Regal Pearl Drop Earrings', 'Ornate gold-tone drop earrings feature delicate pearl accents and intricate leaf swirl detailing, perfect for elegant occasions or gifting.', 1399.00, 51, '/hatrox-project/assets/uploads/62df8bea3b75974e.jpg', 'Earrings', 22, 17, 1, 'active', '2025-09-25 17:46:48'),
(36, 'Aurora Modern Diamond Band', 'Sleek rose gold designer ring featuring a contemporary angular band with a row of delicate white diamonds—perfect for modern style statements.', 2199.00, 2, '/hatrox-project/assets/uploads/8d20271f3488b0a8.jpg', 'Ring', 17, 3, 1, 'active', '2025-09-25 17:48:42'),
(37, 'Willow Diamond Leaf Pendant', 'Elegant gold pendant designed as a cascading branch with diamond-studded leaf motifs, offering a graceful and organic statement for any occasion.', 1899.00, 18, '/hatrox-project/assets/uploads/038c3e5922e7a686.jpg', 'Necklace', 28, 3, 1, 'active', '2025-09-25 17:50:11'),
(38, 'Ruby Halo Statement Ring', 'Unique rose gold ring with a bold geometric teardrop shape, vibrant ruby inlays, and dazzling diamond halo — an unforgettable centerpiece accessory.', 3099.00, 24, '/hatrox-project/assets/uploads/866742bd06517771.jpg', 'Ring', 27, 7, 1, 'active', '2025-09-25 17:52:27'),
(39, 'Imperial Ruby Chandbali Earrings', 'Traditional chandbali-style earrings adorned with vibrant ruby stones and sparkling white kundan, crafted for festive occasions and bridal glamour.', 3699.00, 27, '/hatrox-project/assets/uploads/a17d28b6c12508bc.jpg', 'Earrings', 21, 12, 1, 'active', '2025-09-25 17:53:46'),
(40, 'Emerald Leaf Bangle', 'Delicate gold bangle featuring twin leaf-shaped motifs with emerald green enamel and artistically set diamonds, adding a fresh and vibrant touch to any look.', 1999.00, 20, '/hatrox-project/assets/uploads/64f06a9d3d5ed7b1.jpg', 'Bracelet', 19, 8, 1, 'active', '2025-09-25 17:59:17'),
(42, 'Twinkle Dome Diamond Jhumka Earrings', 'These stunning drop earrings feature a modern jhumki-inspired dome design crafted in elegant rose gold. The dome is beautifully accented with sparkling white stones, giving it a luxurious and graceful look. The stone-studded top bar adds extra shine, making these earrings perfect for both festive occasions and everyday elegance.', 6520.00, 25, '/hatrox-project/assets/uploads/9d9f3166db07349c.jpg', 'Jhumka', 12, 0, 1, 'active', '2025-11-26 14:33:27'),
(43, 'Majestic Aura Diamond Jhumka Earrings', 'Elevate your traditional look with these exquisitely crafted royal jhumka earrings, adorned with rich ruby-red stones, sparkling white CZ diamonds, and delicate hanging pearls. The intricate dome design paired with the ornate circular top creates a truly luxurious, heritage-inspired piece.', 8150.00, 40, '/hatrox-project/assets/uploads/f013748919087157.jpg', 'Jhumka', 6, 2, 1, 'active', '2025-11-26 14:40:31');

-- --------------------------------------------------------

--
-- Table structure for table `product_ratings`
--

CREATE TABLE `product_ratings` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_ratings`
--

INSERT INTO `product_ratings` (`id`, `product_id`, `user_id`, `rating`, `comment`, `created_at`) VALUES
(2, 22, 5, 5, 'very nice experience !!', '2025-09-23 08:47:50'),
(3, 24, 5, 4, 'looks good !!', '2025-09-23 08:48:24'),
(4, 28, NULL, 4, 'too expensive but worth it !!', '2025-09-23 08:50:25'),
(5, 26, NULL, 3, 'nice colour !!', '2025-09-23 08:50:59'),
(6, 24, 2, 4, 'really tooo toooo good design !!!', '2025-09-23 08:52:15'),
(7, 25, 2, 3, 'time set karine aapo bhai !!', '2025-09-23 08:52:52'),
(8, 39, 4, 5, 'Absolutely stunning piece! The quality and craftsmanship exceeded my expectations.', '2025-09-25 18:38:34'),
(9, 34, 4, 4, 'I love this product! It’s elegant and perfect for everyday wear.', '2025-09-25 18:38:55'),
(10, 36, 4, 3, 'cool !!', '2025-09-25 18:39:42'),
(11, 27, 4, 5, 'Exceeded my expectations in every way. Truly a five-star product.', '2025-09-25 18:40:04'),
(12, 40, 2, 5, 'Perfect gift for a loved one. The packaging was gorgeous too.', '2025-09-25 18:41:11'),
(13, 39, 2, 4, 'Comfortable to wear and looks even better in person.', '2025-09-25 18:41:27'),
(14, 22, 2, 4, 'Affordable luxury! Great value for the price.', '2025-09-25 18:42:06'),
(15, 38, 5, 5, 'wowwwwww', '2025-09-25 18:42:39'),
(16, 38, 5, 5, 'i liked it !!', '2025-09-25 18:42:51'),
(17, 27, 5, 5, 'The sparkle and finish are incredible. I’m very happy with this purchase.', '2025-09-25 18:43:19'),
(18, 35, 5, 5, 'niceeeeeeeeeeeeeeee', '2025-09-25 18:43:51'),
(19, 35, 5, 5, 'The workmanship is outstanding, and it looks gorgeous on my wrist.', '2025-09-25 18:44:05'),
(20, 40, 9, 4, 'Fits perfectly and the color matches everything in my wardrobe', '2025-09-25 18:47:28'),
(21, 39, 9, 5, 'A must-have accessory! I get compliments every time I wear it.', '2025-09-25 18:47:46'),
(22, 36, 9, 2, 'good', '2025-09-25 18:48:02'),
(23, 30, 9, 5, 'The detail on this piece is incredible for the price.', '2025-09-25 18:48:35'),
(24, 30, 9, 4, 'The detail on this piece is incredible for the price', '2025-09-25 18:48:46'),
(25, 27, 9, 3, 'Received so many questions about it—it\'s a real conversation starter!', '2025-09-25 18:49:24'),
(26, 43, 14, 5, 'અરે વાહ મને ગમ્યું  🤗🤗', '2025-11-26 20:43:55'),
(27, 30, 15, 5, 'helooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooo', '2025-11-26 21:18:09');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `remember_token` varchar(255) DEFAULT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `is_blocked` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `created_at`, `remember_token`, `is_admin`, `is_blocked`) VALUES
(1, 'Administrator', 'error@hatrox.com', '$2y$10$Eg24gh0Lti.azHy2zMsebObwzGIz0cgSsGNpqX6/NRy5mjs/Q8XyC', '2025-09-12 16:17:57', NULL, 1, 0),
(2, 'harsh chauhan', 'harsh@hatrox.com', '$2y$10$WhFTxT12f6i2ZxJCLjEpAuGnH8O/arLm2jfDWYfwM1lpl3QvN6.EC', '2025-09-12 16:20:44', NULL, 0, 0),
(4, 'rudra', 'rudra@hatrox.com', '$2y$10$97GTzcw9ePFPis3VxB9QmeaGusEbSejm120gpgw2lI1aUWV.//Z92', '2025-09-12 17:39:40', NULL, 0, 0),
(5, 'sonal saini', 'sonal@saini.com', '$2y$10$c3vW0tA6UGSmmWCCYR3tBOcNsx4yBBRX3Tr.WFVPtPW3U8J2wmCh2', '2025-09-12 21:49:39', NULL, 0, 0),
(6, 'ronak', 'ronak@hatrox.com', '$2y$10$564OstoVlCoWvbAE5jDw1eIsrErJO/Ce.SavRQ41zSrVx/NyvrA1q', '2025-09-22 22:53:45', NULL, 0, 0),
(8, 'tushar kaklotar', 'tushar@hatrox.com', '$2y$10$RC6Cpw08HHyGw1c/P0VXHuE9UYZOVEBtz1u84znEHGltt68Y6Uq8K', '2025-09-25 16:37:51', NULL, 0, 0),
(9, 'nirav kaklotar', 'nirav@hatrox.com', '$2y$10$bQgVIlLsoufNT685sNPb5.rOp9MK0syfz1CjirLGwN5Wr1E3VWegK', '2025-09-25 18:46:30', NULL, 0, 0),
(10, 'Rutvik Kaklotar', 'rutvik@hatrox.com', '$2y$10$p1/DsD5N7LVa0XtKfIYiPO7nnwqRSRZuo/msvhX4PK7mL5mBIpcuy', '2025-11-25 10:55:18', NULL, 0, 0),
(11, 'chiu chiu', 'chakli@hatrox.com', '$2y$10$BVzmzPIPpaUEIbT8BOYrf.u7mhResa0FXpHoL3FVUIMktD5K6E7fG', '2025-11-25 14:43:58', NULL, 0, 0),
(12, 'chamkadar', 'chamkadar@hatrox.com', '$2y$10$XbYURFOlzUMWN90Va5wWi.0.TTfIMfytw.AfNimRJkPFm7SVPQHJe', '2025-11-25 17:42:51', NULL, 0, 0),
(14, 'priya prajapati', 'priya@hatrox.com', '$2y$10$tM8eykb0vmkbjhoD3Df0A.C4q.WzUMeONCZxkZBrPwBB6WVfOoOze', '2025-11-26 20:36:14', NULL, 0, 0),
(15, 'chuuby cheeks', 'chuuby@hatrox.com', '$2y$10$F1qcw.qwz9BoAhfGnuJkJubnUjmbAy3BUkKUlQB3N4Xp4admB7PgO', '2025-11-26 21:15:01', NULL, 0, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_product` (`user_id`,`product_id`),
  ADD KEY `fk_cart_product` (`product_id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_comments_user` (`user_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_orders_user` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_order_items_order` (`order_id`),
  ADD KEY `fk_order_items_product` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_products_user` (`created_by`);

--
-- Indexes for table `product_ratings`
--
ALTER TABLE `product_ratings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ratings_product` (`product_id`),
  ADD KEY `fk_ratings_user` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `product_ratings`
--
ALTER TABLE `product_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `fk_comments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_ratings`
--
ALTER TABLE `product_ratings`
  ADD CONSTRAINT `fk_ratings_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ratings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
