-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 08, 2025 at 10:58 PM
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
-- Database: `hungry_hub`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password`, `created_at`) VALUES
(1, 'mehedi', '123', '2025-09-15 10:48:30');

-- --------------------------------------------------------

--
-- Table structure for table `areas`
--

CREATE TABLE `areas` (
  `id` int(11) NOT NULL,
  `area_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `areas`
--

INSERT INTO `areas` (`id`, `area_name`) VALUES
(1, 'Banani'),
(2, 'Bashundhara'),
(3, 'Dhanmondi'),
(4, 'Gulshan'),
(5, 'Mirpur'),
(6, 'Motijheel'),
(7, 'Old Dhaka'),
(8, 'Ramna'),
(9, 'Tejgaon'),
(10, 'Uttara'),
(11, 'Wari');

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `id` int(11) NOT NULL,
  `coupon_code` varchar(50) NOT NULL,
  `percentage` int(11) NOT NULL CHECK (`percentage` > 0 and `percentage` <= 100),
  `created_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coupons`
--

INSERT INTO `coupons` (`id`, `coupon_code`, `percentage`, `created_date`) VALUES
(3, 'NOOR10', 10, '2025-09-27 21:07:50'),
(4, 'WELCOME20', 20, '2025-09-29 15:45:20'),
(5, 'MD5', 5, '2025-09-29 17:28:57'),
(6, 'NEWUSER15', 15, '2025-10-01 16:12:08'),
(7, 'JOLDI10', 10, '2025-10-01 18:38:56');

-- --------------------------------------------------------

--
-- Table structure for table `food_items`
--

CREATE TABLE `food_items` (
  `id` int(11) NOT NULL,
  `category` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `food_items`
--

INSERT INTO `food_items` (`id`, `category`, `name`, `price`, `description`, `image`, `created_at`) VALUES
(1, 'Salad', 'Ambrosia Salad', 189.00, 'A creamy fruit salad mixed with marshmallows, coconut, and nuts.', 'assets/salad/ambrosia_salad.jpg', '2025-09-17 10:31:57'),
(2, 'Salad', 'Caesar Salad', 199.00, 'Classic romaine lettuce with Caesar dressing, croutons, and parmesan.', 'assets/salad/caesar_salad.jpg', '2025-09-17 10:31:57'),
(3, 'Salad', 'Caprese Salad', 219.00, 'Fresh tomatoes, mozzarella, and basil drizzled with olive oil.', 'assets/salad/caprese_salad.jpg', '2025-09-17 10:31:57'),
(4, 'Salad', 'Cobb Salad', 199.00, 'Hearty salad with chicken, bacon, avocado, eggs, and blue cheese.', 'assets/salad/cobb_salad.jpg', '2025-09-17 10:31:57'),
(5, 'Salad', 'Couscous Salad', 249.00, 'Fluffy couscous tossed with fresh vegetables and herbs.', 'assets/salad/couscous_salad.jpg', '2025-09-17 10:31:57'),
(6, 'Salad', 'Greek Salad', 249.00, 'Crisp cucumbers, tomatoes, olives, and feta with olive oil.', 'assets/salad/greek_salad.jpg', '2025-09-17 10:31:57'),
(7, 'Salad', 'Nicoise Salad', 219.00, 'A French-style salad with tuna, potatoes, olives, and beans.', 'assets/salad/nicoise_salad.jpg', '2025-09-17 10:31:57'),
(8, 'Salad', 'Orzo Salad', 239.00, 'Light pasta salad made with orzo, veggies, and lemon dressing.', 'assets/salad/orzo_salad.jpg', '2025-09-17 10:31:57'),
(9, 'Salad', 'Quinoa Salad', 219.00, 'Protein-packed quinoa mixed with vegetables and herbs.', 'assets/salad/quinoa_salad.jpg', '2025-09-17 10:31:57'),
(10, 'Salad', 'Waldorf Salad', 249.00, 'Apple, celery, walnuts, and grapes in a creamy dressing.', 'assets/salad/waldorf_salad.jpg', '2025-09-17 10:31:57'),
(11, 'Salad', 'Wedge Salad', 199.00, 'Crisp iceberg wedge topped with blue cheese and bacon.', 'assets/salad/wedge_salad.jpg', '2025-09-17 10:31:57'),
(12, 'Rolls', 'Broccoli Florets', 199.00, 'Lightly seasoned broccoli florets, perfect as a side snack.', 'assets/rolls/broccoli_florets.jpg', '2025-09-17 10:31:57'),
(13, 'Rolls', 'Chicken Strips', 219.00, 'Crispy golden chicken strips served with dipping sauce.', 'assets/rolls/chicken_strips.jpg', '2025-09-17 10:31:57'),
(14, 'Rolls', 'Deep Fried Spring Rolls', 189.00, 'Crispy rolls stuffed with vegetables and spices.', 'assets/rolls/deep-fried-spring-rolls.jpg', '2025-09-17 10:31:57'),
(15, 'Rolls', 'Salmon Sticks', 299.00, 'Golden-fried salmon sticks with a light, crispy coating.', 'assets/rolls/salmon_sticks.jpg', '2025-09-17 10:31:57'),
(16, 'Rolls', 'Steamed Green Beans', 249.00, 'Tender green beans steamed to perfection.', 'assets/rolls/steamed_green_beans.jpg', '2025-09-17 10:31:57'),
(17, 'Rolls', 'Sweet Potato Spears', 149.00, 'Crispy baked sweet potato spears with a hint of spice.', 'assets/rolls/sweet_potato_spears.jpg', '2025-09-17 10:31:57'),
(18, 'Rolls', 'Tofu Sticks', 229.00, 'Crispy tofu fingers served with dipping sauce.', 'assets/rolls/tofu_sticks.jpg', '2025-09-17 10:31:57'),
(19, 'Rolls', 'Zucchini Sticks', 199.00, 'Crispy zucchini sticks with light seasoning.', 'assets/rolls/zucchini_sticks.jpg', '2025-09-17 10:31:57'),
(20, 'Desserts', 'Baklava', 149.00, 'Layers of flaky pastry filled with nuts and honey syrup.', 'assets/dessert/baklava.jpg', '2025-09-17 10:31:57'),
(21, 'Desserts', 'Black Forest', 199.00, 'Chocolate cake layered with cherries and whipped cream.', 'assets/dessert/black_forest.jpg', '2025-09-17 10:31:57'),
(22, 'Desserts', 'Brownie', 169.00, 'Rich and fudgy chocolate brownie.', 'assets/dessert/brownie.jpg', '2025-09-17 10:31:57'),
(23, 'Desserts', 'Cheesecake', 139.00, 'Creamy cheesecake on a buttery graham crust.', 'assets/dessert/cheesecake.jpg', '2025-09-17 10:31:57'),
(24, 'Desserts', 'Chocolate Mousse', 119.00, 'Light and airy chocolate mousse with a velvety texture.', 'assets/dessert/chocolate mousse.jpg', '2025-09-17 10:31:57'),
(25, 'Desserts', 'Eclair', 149.00, 'Pastry filled with cream and topped with chocolate.', 'assets/dessert/eclair.jpg', '2025-09-17 10:31:57'),
(26, 'Desserts', 'Molten Chocolate Cake', 129.00, 'Warm chocolate cake with a gooey molten center.', 'assets/dessert/molten_chocolate_cake.jpg', '2025-09-17 10:31:57'),
(27, 'Desserts', 'Sweet Treat Delight', 129.00, 'A delightful mix of sweet treats in one dessert.', 'assets/dessert/sweet-treat-delight-photo.jpg', '2025-09-17 10:31:57'),
(28, 'Desserts', 'Tiramisu', 119.00, 'Coffee-flavored Italian dessert with mascarpone layers.', 'assets/dessert/tiramisu.jpg', '2025-09-17 10:31:57'),
(29, 'Sandwich', 'Club Sandwich', 249.00, 'Triple-layer sandwich with chicken, bacon, and veggies.', 'assets/sandwich/club_sandwish.jpg', '2025-09-17 10:31:57'),
(30, 'Sandwich', 'Croque Monsieur', 239.00, 'French toasted sandwich with ham and melted cheese.', 'assets/sandwich/croque_monieur.jpg', '2025-09-17 10:31:57'),
(31, 'Sandwich', 'Cuban Sandwich', 279.00, 'Pressed sandwich with pork, ham, pickles, and cheese.', 'assets/sandwich/cuban.jpg', '2025-09-17 10:31:57'),
(32, 'Sandwich', 'Falafel Sandwich', 249.00, 'Crispy falafel balls wrapped with veggies and sauce.', 'assets/sandwich/Falafel.jpg', '2025-09-17 10:31:57'),
(33, 'Sandwich', 'French Dip', 259.00, 'Beef sandwich served with a warm au jus dip.', 'assets/sandwich/french_dip.jpg', '2025-09-17 10:31:57'),
(34, 'Sandwich', 'Meatball Sub', 239.00, 'Sub roll filled with juicy meatballs and marinara.', 'assets/sandwich/meatball_sub.jpg', '2025-09-17 10:31:57'),
(35, 'Sandwich', 'Panini', 219.00, 'Grilled Italian sandwich with melted cheese and fillings.', 'assets/sandwich/panini.jpg', '2025-09-17 10:31:57'),
(36, 'Sandwich', 'Patty Melt', 289.00, 'Burger patty served on toasted bread with melted cheese.', 'assets/sandwich/patty_melt.jpg', '2025-09-17 10:31:57'),
(37, 'Sandwich', 'Reuben Sandwich', 299.00, 'Corned beef, sauerkraut, and Swiss cheese on rye.', 'assets/sandwich/reuben.jpg', '2025-09-17 10:31:57'),
(38, 'Sandwich', 'Tandoori Chicken', 279.00, 'Spicy Indian-style chicken sandwich with smoky flavor.', 'assets/sandwich/tandoori_chicken.jpg', '2025-09-17 10:31:57'),
(39, 'Cake', 'Black Forest Cake', 799.00, 'Classic chocolate sponge cake layered with cherries.', 'assets/cake/black_forest.jpg', '2025-09-17 10:31:57'),
(40, 'Cake', 'Chocolate Fudge Cake', 799.00, 'Rich chocolate cake with fudgy layers.', 'assets/cake/chocolate_fudge.jpg', '2025-09-17 10:31:57'),
(41, 'Cake', 'Fruit Cake', 699.00, 'Moist cake packed with dried fruits and nuts.', 'assets/cake/fruitcake.jpg', '2025-09-17 10:31:57'),
(42, 'Cake', 'Tres Leches Cake', 649.00, 'Soft sponge cake soaked in three types of milk.', 'assets/cake/tres_leches.jpg', '2025-09-17 10:31:57'),
(43, 'Cake', 'Vanilla Cake', 749.00, 'Light and fluffy vanilla sponge with frosting.', 'assets/cake/vanilla.jpg', '2025-09-17 10:31:57'),
(44, 'Cake', 'Victoria Sponge Cake', 749.00, 'Classic British sponge cake with jam and cream filling.', 'assets/cake/victoria_sponge.jpg', '2025-09-17 10:31:57'),
(45, 'Pure Veg', 'Caponata', 189.00, 'Sicilian eggplant stew with vegetables and herbs.', 'assets/pureveg/caponata.jpg', '2025-09-17 10:31:57'),
(46, 'Pure Veg', 'Fattoush', 209.00, 'Fresh Middle Eastern salad with crispy pita bread.', 'assets/pureveg/fattoush.jpg', '2025-09-17 10:31:57'),
(47, 'Pure Veg', 'Moussaka', 169.00, 'Layered Mediterranean casserole with eggplant.', 'assets/pureveg/moussaka.jpg', '2025-09-17 10:31:57'),
(48, 'Pure Veg', 'Palak Paneer', 229.00, 'Indian curry with spinach and cottage cheese cubes.', 'assets/pureveg/palak_paneer.jpg', '2025-09-17 10:31:57'),
(49, 'Pure Veg', 'Ratatouille', 199.00, 'French veggie dish with zucchini, eggplant, and peppers.', 'assets/pureveg/ratatouille.jpg', '2025-09-17 10:31:57'),
(50, 'Pure Veg', 'Mushroom Risotto', 249.00, 'Creamy Bengali-style mixed vegetable curry.', 'assets/pureveg/shobji_korma.jpg', '2025-09-17 10:31:57'),
(51, 'Pure Veg', 'Tabbouleh', 199.00, 'Fresh parsley salad with bulgur, lemon, and tomato.', 'assets/pureveg/tabbouleh.jpg', '2025-09-17 10:31:57'),
(52, 'Pasta', 'Cacio E Pepe', 199.00, 'Simple pasta tossed with cheese and black pepper.', 'assets/pasta/cacio_e_pepe.jpg', '2025-09-17 10:31:57'),
(53, 'Pasta', 'Carbonara', 219.00, 'Creamy pasta with bacon, egg, and parmesan.', 'assets/pasta/carbonara.jpg', '2025-09-17 10:31:57'),
(54, 'Pasta', 'Chicken Tikka Pasta', 179.00, 'Fusion pasta with Indian chicken tikka flavors.', 'assets/pasta/chicken_tikka.jpg', '2025-09-17 10:31:57'),
(55, 'Pasta', 'Fettuccine Alfredo', 179.00, 'Creamy pasta with butter, cream, and parmesan sauce.', 'assets/pasta/fettuccine_alfredo.jpg', '2025-09-17 10:31:57'),
(56, 'Pasta', 'Lasagne Al Forno', 199.00, 'Layered baked pasta with meat, sauce, and cheese.', 'assets/pasta/lasagne_al_forno.jpg', '2025-09-17 10:31:57'),
(57, 'Pasta', 'Naga Pasta', 189.00, 'Spicy pasta with a kick of naga chili flavor.', 'assets/pasta/naga_pasta.jpg', '2025-09-17 10:31:57'),
(58, 'Pasta', 'Penne Alla Vodka', 199.00, 'Penne pasta in a creamy tomato-vodka sauce.', 'assets/pasta/penne_alla_vodka.jpg', '2025-09-17 10:31:57'),
(59, 'Pasta', 'Puttanesca', 169.00, 'Tangy pasta with olives, capers, and anchovies.', 'assets/pasta/puttanesca.jpg', '2025-09-17 10:31:57'),
(60, 'Noodles', 'Chicken Vegetable Noodles', 149.00, 'Stir-fried noodles with chicken and fresh veggies.', 'assets/noodles/chicken_vegetable.jpg', '2025-09-17 10:31:57'),
(61, 'Noodles', 'Chow Mein', 169.00, 'Classic Chinese-style stir-fried noodles.', 'assets/noodles/chow_mein.jpg', '2025-09-17 10:31:57'),
(62, 'Noodles', 'Khow Suey', 149.00, 'Burmese noodle dish with coconut curry sauce.', 'assets/noodles/khow_suey.jpg', '2025-09-17 10:31:57'),
(63, 'Noodles', 'Lo Mein', 179.00, 'Soft stir-fried noodles with vegetables and soy sauce.', 'assets/noodles/lo_mein.jpg', '2025-09-17 10:31:57'),
(64, 'Noodles', 'Mie Goreng', 159.00, 'Indonesian-style fried noodles with spices.', 'assets/noodles/mie_goreng.jpg', '2025-09-17 10:31:57'),
(65, 'Noodles', 'Spicy Naga Noodles', 179.00, 'Fiery noodles infused with naga chili heat.', 'assets/noodles/spicy_naga.jpg', '2025-09-17 10:31:57'),
(66, 'Noodles', 'Thukpa', 159.00, 'Tibetan noodle soup with vegetables and broth.', 'assets/noodles/thukpa.jpg', '2025-09-17 10:31:57'),
(67, 'Noodles', 'Yaki Soba', 149.00, 'Japanese stir-fried noodles with soy-based sauce.', 'assets/noodles/yaki_soba.jpg', '2025-09-17 10:31:57'),
(74, 'Pure Veg', 'Rajma Masala', 219.00, 'Protein-packed curry made with red kidney beans and simmered in a flavorful onion-tomato gravy and a few Indian spices', 'assets/pure veg/rajma_masala_1758106905.jpg', '2025-09-17 11:01:49'),
(75, 'Cake', 'Buttermilk Vanilla Cake', 699.00, 'A soft and fluffy vanilla cake made with rich buttermilk, offering a moist texture and a delicate, buttery flavor in every bite', 'assets/cake/buttermilk_vanilla_1758107590.jpg', '2025-09-17 11:13:13'),
(80, 'Burgers', 'Hangry Heifer', 299.00, 'A towering stack of juicy beef, thick-cut bacon, sharp cheddar, and caramelized onions', 'assets/burgers/hangry_heifer_1759160096.jpg', '2025-09-29 12:24:15');

-- --------------------------------------------------------

--
-- Table structure for table `food_menu`
--

CREATE TABLE `food_menu` (
  `id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `food_menu`
--

INSERT INTO `food_menu` (`id`, `category`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Salad', 'Fresh and healthy salad options', '2025-09-24 19:49:20', '2025-09-24 19:49:20'),
(2, 'Desserts', 'Sweet treats and desserts', '2025-09-24 19:49:20', '2025-09-24 19:49:20'),
(3, 'Pasta', 'Italian and fusion pasta dishes', '2025-09-24 19:49:20', '2025-09-24 19:49:20'),
(4, 'Noodles', 'Asian noodle specialties', '2025-09-24 19:49:20', '2025-09-24 19:49:20'),
(5, 'Pure Veg', 'Pure vegetarian dishes', '2025-09-24 19:49:20', '2025-09-24 19:49:20'),
(6, 'Rolls', 'Various roll varieties', '2025-09-24 19:49:20', '2025-09-24 19:49:20'),
(7, 'Sandwich', 'Sandwich and panini options', '2025-09-24 19:49:20', '2025-09-24 19:49:20'),
(8, 'Cake', 'Cake and bakery items', '2025-09-24 19:49:20', '2025-09-24 19:49:20'),
(11, 'Burgers', 'Select your favorite burgers', '2025-09-29 12:22:09', '2025-10-04 15:42:50');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_id` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(100) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `delivery_area` varchar(50) NOT NULL,
  `delivery_street` varchar(100) DEFAULT NULL,
  `delivery_road` varchar(50) DEFAULT NULL,
  `delivery_address1` varchar(200) NOT NULL,
  `delivery_address2` varchar(200) DEFAULT NULL,
  `order_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`order_items`)),
  `subtotal` decimal(10,2) NOT NULL,
  `tax_amount` decimal(10,2) NOT NULL,
  `delivery_fee` decimal(10,2) NOT NULL DEFAULT 50.00,
  `coupon_code` varchar(20) DEFAULT NULL,
  `coupon_discount` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `payment_method` enum('cod','card') NOT NULL,
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `status` enum('pending','processing','completed','cancelled') DEFAULT 'pending',
  `order_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `delivered_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_id`, `user_id`, `customer_name`, `customer_email`, `customer_phone`, `delivery_area`, `delivery_street`, `delivery_road`, `delivery_address1`, `delivery_address2`, `order_items`, `subtotal`, `tax_amount`, `delivery_fee`, `coupon_code`, `coupon_discount`, `total`, `payment_method`, `payment_status`, `status`, `order_notes`, `created_at`, `updated_at`, `delivered_at`) VALUES
(10, 'HH202509280710', 1, 'Mehedi Noor', 'mmd5468@gmail.com', '01301863600', 'Banani', '4', '6', '3', 'ujh', '[{\"item_name\":\"Victoria Sponge Cake\",\"category\":\"Cake\",\"quantity\":1,\"unit_price\":749,\"total_price\":749},{\"item_name\":\"Tres Leches Cake\",\"category\":\"Cake\",\"quantity\":1,\"unit_price\":599,\"total_price\":599}]', 1348.00, 67.40, 50.00, 'NOOR10', 134.80, 1330.60, 'cod', 'pending', 'completed', '', '2025-09-28 19:02:47', '2025-09-29 11:37:49', NULL),
(11, 'HH202509289852', 1, 'Mehedi Noor', 'mmd5468@gmail.com', '01301863600', 'Old Dhaka', '6', '8', '89', 'dfg', '[{\"item_name\":\"Chocolate Fudge Cake\",\"category\":\"Cake\",\"quantity\":1,\"unit_price\":799,\"total_price\":799},{\"item_name\":\"Sweet Potato Spears\",\"category\":\"Rolls\",\"quantity\":1,\"unit_price\":9.99,\"total_price\":9.99},{\"item_name\":\"Greek Salad\",\"category\":\"Salad\",\"quantity\":1,\"unit_price\":249,\"total_price\":249}]', 1057.99, 52.90, 50.00, 'NEWUSER20', 211.60, 949.29, 'card', 'paid', 'processing', 'sdtaey', '2025-09-28 19:06:26', '2025-09-29 10:33:11', NULL),
(12, 'HH202509290208', 1, 'Mehedi Noor', 'mmd5468@gmail.com', '01301863600', 'Mirpur', '10', '7', '45', '3b', '[{\"item_name\":\"Hangry Heifer\",\"category\":\"Burgers\",\"quantity\":2,\"unit_price\":289,\"total_price\":578}]', 578.00, 28.90, 50.00, 'WELCOME20', 115.60, 541.30, 'cod', 'pending', 'pending', '', '2025-09-29 16:20:39', '2025-09-29 16:20:39', NULL),
(15, 'HH202509299667', 1, 'Mehedi Noor', 'mmd5468@gmail.com', '01301863600', 'Ramna', '5', '6', '345', '5e', '[{\"item_name\":\"Hangry Heifer\",\"category\":\"Burgers\",\"quantity\":1,\"unit_price\":289,\"total_price\":289},{\"item_name\":\"Caesar Salad\",\"category\":\"Salad\",\"quantity\":1,\"unit_price\":199,\"total_price\":199},{\"item_name\":\"Quinoa Salad\",\"category\":\"Salad\",\"quantity\":1,\"unit_price\":219,\"total_price\":219}]', 707.00, 35.35, 50.00, 'WELCOME20', 141.40, 650.95, 'card', 'paid', 'pending', 'lift-5', '2025-09-29 16:34:45', '2025-09-29 16:34:45', NULL),
(16, 'HH202509295699', 1, 'Mehedi Noor', 'mmd5468@gmail.com', '01301863600', 'Dhanmondi', '32', '6', '45', '4d', '[{\"item_name\":\"Deep Fried Spring Rolls\",\"category\":\"Rolls\",\"quantity\":1,\"unit_price\":189,\"total_price\":189},{\"item_name\":\"Molten Chocolate Cake\",\"category\":\"Desserts\",\"quantity\":1,\"unit_price\":129,\"total_price\":129}]', 318.00, 15.90, 50.00, 'NOOR10', 31.80, 352.10, 'card', 'paid', 'pending', 'lift-4', '2025-09-29 16:41:14', '2025-09-29 16:41:14', NULL),
(17, 'HH202509297281', 1, 'Mehedi Noor', 'mmd5468@gmail.com', '01301863600', 'Banani', '9', '3', '234', '4d', '[{\"item_name\":\"Cacio E Pepe\",\"category\":\"Pasta\",\"quantity\":1,\"unit_price\":199,\"total_price\":199},{\"item_name\":\"Chicken Tikka Pasta\",\"category\":\"Pasta\",\"quantity\":2,\"unit_price\":179,\"total_price\":358}]', 557.00, 27.85, 50.00, 'WELCOME20', 111.40, 523.45, 'cod', 'pending', 'pending', '5th floor', '2025-09-29 16:45:15', '2025-09-29 16:45:15', NULL),
(18, 'HH202509298341', 1, 'Mehedi Noor', 'mmd5468@gmail.com', '01301863600', 'Banani', '6', '3', '355', '4e', '[{\"item_name\":\"Cacio E Pepe\",\"category\":\"Pasta\",\"quantity\":1,\"unit_price\":199,\"total_price\":199},{\"item_name\":\"Chicken Tikka Pasta\",\"category\":\"Pasta\",\"quantity\":2,\"unit_price\":179,\"total_price\":358}]', 557.00, 27.85, 50.00, 'WELCOME20', 111.40, 523.45, 'cod', 'pending', 'pending', 'fdf', '2025-09-29 16:46:21', '2025-09-29 16:46:21', NULL),
(19, 'HH202509292136', 1, 'Mehedi Noor', 'mmd5468@gmail.com', '01301863600', 'Uttara', '12', '4', '343', '6d', '[{\"item_name\":\"Cacio E Pepe\",\"category\":\"Pasta\",\"quantity\":1,\"unit_price\":199,\"total_price\":199},{\"item_name\":\"Chicken Tikka Pasta\",\"category\":\"Pasta\",\"quantity\":2,\"unit_price\":179,\"total_price\":358}]', 557.00, 27.85, 50.00, 'WELCOME20', 111.40, 523.45, 'cod', 'pending', 'pending', '7th floor', '2025-09-29 16:55:44', '2025-09-29 16:55:44', NULL),
(20, 'HH202509298416', 1, 'Mehedi Noor', 'mmd5468@gmail.com', '01301863600', 'Ramna', '45', '3', '331', '4f', '[{\"item_name\":\"Naga Pasta\",\"category\":\"Pasta\",\"quantity\":1,\"unit_price\":189,\"total_price\":189},{\"item_name\":\"Zucchini Sticks\",\"category\":\"Rolls\",\"quantity\":1,\"unit_price\":199,\"total_price\":199}]', 388.00, 19.40, 50.00, 'NOOR10', 38.80, 418.60, 'card', 'paid', 'pending', '', '2025-09-29 16:58:25', '2025-09-29 16:58:25', NULL),
(21, 'HH202509291379', 1, 'Mehedi Noor', 'mmd5468@gmail.com', '01301863600', 'Ramna', '45', '3', '331', '4f', '[{\"item_name\":\"Naga Pasta\",\"category\":\"Pasta\",\"quantity\":1,\"unit_price\":189,\"total_price\":189},{\"item_name\":\"Zucchini Sticks\",\"category\":\"Rolls\",\"quantity\":1,\"unit_price\":199,\"total_price\":199}]', 388.00, 19.40, 50.00, 'NOOR10', 38.80, 418.60, 'card', 'paid', 'pending', '', '2025-09-29 16:59:03', '2025-09-29 16:59:03', NULL),
(22, 'HH202509296181', 1, 'Mehedi Noor', 'mmd5468@gmail.com', '01301863600', 'Mirpur', '5', '7', '45', '', '[{\"item_name\":\"Chicken Strips\",\"category\":\"Rolls\",\"quantity\":1,\"unit_price\":219,\"total_price\":219},{\"item_name\":\"Orzo Salad\",\"category\":\"Salad\",\"quantity\":1,\"unit_price\":239,\"total_price\":239}]', 458.00, 22.90, 50.00, 'NOOR10', 45.80, 485.10, 'card', 'paid', 'pending', '', '2025-09-29 17:06:30', '2025-09-29 17:06:30', NULL),
(23, 'HH202509297170', 1, 'Mehedi Noor', 'mmd5468@gmail.com', '01301863600', 'Mirpur', '5', '7', '45', '', '[{\"item_name\":\"Chicken Strips\",\"category\":\"Rolls\",\"quantity\":1,\"unit_price\":219,\"total_price\":219},{\"item_name\":\"Orzo Salad\",\"category\":\"Salad\",\"quantity\":1,\"unit_price\":239,\"total_price\":239}]', 458.00, 22.90, 50.00, 'NOOR10', 45.80, 485.10, 'cod', 'pending', 'completed', '', '2025-09-29 17:07:08', '2025-09-30 20:18:23', NULL),
(24, 'HH202509293321', 1, 'Mehedi Noor', 'mmd5468@gmail.com', '01301863600', 'Gulshan', '4', '6', '4', 'f', '[{\"item_name\":\"Chicken Strips\",\"category\":\"Rolls\",\"quantity\":1,\"unit_price\":219,\"total_price\":219},{\"item_name\":\"Hangry Heifer\",\"category\":\"Burgers\",\"quantity\":1,\"unit_price\":289,\"total_price\":289}]', 508.00, 25.40, 50.00, NULL, 0.00, 583.40, 'cod', 'pending', 'pending', '', '2025-09-29 17:15:00', '2025-09-29 17:15:00', NULL),
(25, 'HH202509294893', 1, 'Mehedi Noor', 'mmd5468@gmail.com', '01301863600', 'Old Dhaka', '6', '7', '4', '', '[{\"item_name\":\"Chicken Strips\",\"category\":\"Rolls\",\"quantity\":1,\"unit_price\":219,\"total_price\":219}]', 219.00, 10.95, 50.00, NULL, 0.00, 279.95, 'card', 'paid', 'processing', '', '2025-09-29 17:50:40', '2025-10-01 12:28:52', NULL),
(26, 'HH202509290443', 1, 'Mehedi Noor', 'mmd5468@gmail.com', '01301863600', 'Bashundhara', '6', '5', '3', '', '[{\"item_name\":\"Chicken Strips\",\"category\":\"Rolls\",\"quantity\":1,\"unit_price\":219,\"total_price\":219}]', 219.00, 10.95, 50.00, NULL, 0.00, 279.95, 'card', 'paid', 'cancelled', '', '2025-09-29 18:12:43', '2025-09-30 11:56:52', NULL),
(27, 'HH202509292340', 1, 'Mehedi Noor', 'mmd5468@gmail.com', '01301863600', 'Old Dhaka', '4', '6', '345', '5t', '[{\"item_name\":\"Chicken Strips\",\"category\":\"Rolls\",\"quantity\":2,\"unit_price\":219,\"total_price\":438}]', 438.00, 21.90, 50.00, 'MD5', 21.90, 488.00, 'card', 'paid', 'pending', '', '2025-09-29 19:06:35', '2025-09-29 19:06:35', NULL),
(28, 'HH202509296394', 1, 'Mehedi Noor', 'mmd5468@gmail.com', '01301863600', 'Ramna', '4', '4', '3', '', '[{\"item_name\":\"Victoria Sponge Cake\",\"category\":\"Cake\",\"quantity\":1,\"unit_price\":749,\"total_price\":749}]', 749.00, 37.45, 50.00, NULL, 0.00, 836.45, 'cod', 'pending', 'completed', '', '2025-09-29 19:07:56', '2025-09-29 20:41:02', NULL),
(29, 'HH202510015734', 1, 'Mehedi Noor', 'mmd5468@gmail.com', '01301863600', 'Bashundhara', 'F', '6', 'H-234', 'Flat-3C', '[{\"item_name\":\"Broccoli Florets\",\"category\":\"Rolls\",\"quantity\":1,\"unit_price\":199,\"total_price\":199},{\"item_name\":\"Vanilla Cake\",\"category\":\"Cake\",\"quantity\":1,\"unit_price\":749,\"total_price\":749},{\"item_name\":\"Caprese Salad\",\"category\":\"Salad\",\"quantity\":1,\"unit_price\":219,\"total_price\":219},{\"item_name\":\"Fettuccine Alfredo\",\"category\":\"Pasta\",\"quantity\":1,\"unit_price\":179,\"total_price\":179}]', 1346.00, 67.30, 50.00, 'NEWUSER15', 201.90, 1261.40, 'card', 'paid', 'completed', 'Lift-3', '2025-10-01 16:34:12', '2025-10-01 19:00:28', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `food_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `food_id`, `user_id`, `rating`, `review_text`, `created_at`, `updated_at`) VALUES
(1, 38, 1, 5, 'It was so good...', '2025-09-17 15:16:57', '2025-09-17 15:21:49'),
(2, 49, 1, 5, 'good', '2025-09-18 05:54:24', '2025-09-18 05:54:24'),
(4, 44, 1, 5, 'Nice and sweet cake😋', '2025-09-30 15:09:43', '2025-09-30 15:09:43');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('active','inactive','banned') DEFAULT 'active',
  `orders` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_spent` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `address`, `password`, `status`, `orders`, `total_spent`, `created_at`, `updated_at`) VALUES
(1, 'Mehedi Noor', 'mmd5468@gmail.com', '01301863600', 'Bashundhara R/A', '$2y$10$livIfa7ylytt84U7queTuugH4Y5vcSHxXfZr9ZrdcXpSOeUC92KnK', 'active', 0, 0.00, '2025-09-15 23:03:43', '2025-10-01 14:47:31');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `areas`
--
ALTER TABLE `areas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `area_name` (`area_name`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `coupon_code` (`coupon_code`);

--
-- Indexes for table `food_items`
--
ALTER TABLE `food_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `food_menu`
--
ALTER TABLE `food_menu`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_id` (`order_id`) USING BTREE,
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_food_review` (`user_id`,`food_id`),
  ADD KEY `food_id` (`food_id`);

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
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `areas`
--
ALTER TABLE `areas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `food_items`
--
ALTER TABLE `food_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `food_menu`
--
ALTER TABLE `food_menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`food_id`) REFERENCES `food_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
