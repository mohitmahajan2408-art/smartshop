-- ============================================================================
-- ShopSmart India — Complete Database Schema & Seed Data
-- BCA Semester V Field Project
-- Author: ShopSmart Development Team
-- RDBMS: MySQL / MariaDB (Compatible with XAMPP MySQL 10.4+)
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `shopsmart_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `shopsmart_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- Table: users
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(120) NOT NULL UNIQUE,
  `phone` VARCHAR(15) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `avatar` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_role` (`role`),
  INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: remember_tokens
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `remember_tokens`;
CREATE TABLE `remember_tokens` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `token_hash` VARCHAR(64) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: password_resets
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(120) NOT NULL,
  `token` VARCHAR(64) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_resets_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: categories
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL,
  `image_url` VARCHAR(255) DEFAULT NULL,
  `icon` VARCHAR(50) DEFAULT 'bi-tag',
  `display_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: brands
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `brands`;
CREATE TABLE `brands` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `logo_url` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: products
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `sku` VARCHAR(50) NOT NULL UNIQUE,
  `short_description` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  `brand_id` INT UNSIGNED DEFAULT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `original_price` DECIMAL(10,2) NOT NULL,
  `stock_quantity` INT NOT NULL DEFAULT 10,
  `image_url` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `is_deal` TINYINT(1) NOT NULL DEFAULT 0,
  `deal_ends_at` DATETIME DEFAULT NULL,
  `warranty_months` INT NOT NULL DEFAULT 12,
  `best_value_score` INT NOT NULL DEFAULT 85,
  `eco_friendly_score` INT NOT NULL DEFAULT 70,
  `specifications` JSON DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL,
  INDEX `idx_products_category` (`category_id`),
  INDEX `idx_products_price` (`price`),
  INDEX `idx_products_active` (`is_active`),
  INDEX `idx_products_best_val` (`best_value_score`),
  INDEX `idx_products_eco` (`eco_friendly_score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: product_images
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `product_images`;
CREATE TABLE `product_images` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT UNSIGNED NOT NULL,
  `image_url` VARCHAR(255) NOT NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `display_order` INT NOT NULL DEFAULT 0,
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: addresses
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `addresses`;
CREATE TABLE `addresses` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(15) NOT NULL,
  `flat_house` VARCHAR(150) NOT NULL,
  `street_area` VARCHAR(150) NOT NULL,
  `landmark` VARCHAR(100) DEFAULT NULL,
  `city` VARCHAR(80) NOT NULL,
  `state` VARCHAR(80) NOT NULL,
  `pincode` VARCHAR(6) NOT NULL,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: cart_items
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `cart_items`;
CREATE TABLE `cart_items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_user_product_cart` (`user_id`, `product_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: wishlist_items
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `wishlist_items`;
CREATE TABLE `wishlist_items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_user_product_wishlist` (`user_id`, `product_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: saved_products (Buy Later with Reason)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `saved_products`;
CREATE TABLE `saved_products` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `reason` VARCHAR(255) DEFAULT 'Saving for future purchase',
  `target_price` DECIMAL(10,2) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: orders
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_number` VARCHAR(32) NOT NULL UNIQUE,
  `user_id` INT UNSIGNED NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  `delivery_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `payment_method` ENUM('upi', 'card', 'netbanking', 'cod') NOT NULL DEFAULT 'upi',
  `payment_status` ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
  `order_status` ENUM('Pending', 'Confirmed', 'Processing', 'Packed', 'Shipped', 'Out for Delivery', 'Delivered', 'Cancelled', 'Return Requested', 'Returned', 'Refunded') NOT NULL DEFAULT 'Confirmed',
  `delivery_address_json` JSON NOT NULL,
  `tracking_number` VARCHAR(50) DEFAULT NULL,
  `courier_name` VARCHAR(80) DEFAULT 'ShopSmart Express',
  `estimated_delivery` DATE DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  INDEX `idx_orders_status` (`order_status`),
  INDEX `idx_orders_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: order_items
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `total_price` DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: order_status_history
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `order_status_history`;
CREATE TABLE `order_status_history` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT UNSIGNED NOT NULL,
  `status` VARCHAR(50) NOT NULL,
  `notes` VARCHAR(255) DEFAULT NULL,
  `updated_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: inventory_movements
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `inventory_movements`;
CREATE TABLE `inventory_movements` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT UNSIGNED NOT NULL,
  `change_qty` INT NOT NULL,
  `reason` ENUM('order_sale', 'admin_restock', 'return_restock', 'damage_writeoff') NOT NULL,
  `reference_id` VARCHAR(50) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: reviews
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `reviews`;
CREATE TABLE `reviews` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `rating` TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `headline` VARCHAR(150) NOT NULL,
  `comment` TEXT NOT NULL,
  `is_verified_purchase` TINYINT(1) NOT NULL DEFAULT 1,
  `status` ENUM('approved', 'pending', 'rejected') NOT NULL DEFAULT 'approved',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_reviews_product` (`product_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: customer_feedback (Customer Happiness Dashboard)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `customer_feedback`;
CREATE TABLE `customer_feedback` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `overall_satisfaction` TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `product_quality` TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `delivery_experience` TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `pricing_satisfaction` TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `website_usability` TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `customer_support` TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `comments` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: price_alerts
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `price_alerts`;
CREATE TABLE `price_alerts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `target_price` DECIMAL(10,2) NOT NULL,
  `is_triggered` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: user_preferences (Personalized Profile & Expense Tracker)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `user_preferences`;
CREATE TABLE `user_preferences` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL UNIQUE,
  `monthly_budget` DECIMAL(10,2) NOT NULL DEFAULT 15000.00,
  `preferred_categories_json` JSON DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: return_requests (Return Reason Analysis)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `return_requests`;
CREATE TABLE `return_requests` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `return_reason` ENUM('Defective Product', 'Different from Description', 'Size/Fit Issue', 'Delayed Delivery', 'Changed Mind', 'Better Price Found Elsewhere') NOT NULL,
  `details` TEXT DEFAULT NULL,
  `status` ENUM('pending', 'approved', 'rejected', 'refunded') NOT NULL DEFAULT 'pending',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: admin_audit_logs
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `admin_audit_logs`;
CREATE TABLE `admin_audit_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `details` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) NOT NULL DEFAULT '127.0.0.1',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- SEED DATA
-- ============================================================================

-- Users: 1 Admin and 2 Customers
-- Passwords:
-- Admin: Admin@ShopSmart2026 -> $2y$10$010KfHkdeFMQJS2BRuOWlOxmr2xpJD30mn3UoZzMoG3G3GHn5U7dy
-- Customer: Customer@123 -> $2y$10$pEf37XaHy7u9qIeeyylS3.eLLGaBL5guAfxyz0V58Q6.5pCwG6/P2
INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password_hash`, `role`, `status`) VALUES
(1, 'ShopSmart System Admin', 'admin@shopsmart.in', '9876543210', '$2y$10$010KfHkdeFMQJS2BRuOWlOxmr2xpJD30mn3UoZzMoG3G3GHn5U7dy', 'admin', 'active'),
(2, 'Rohit Sharma', 'customer@shopsmart.in', '9123456789', '$2y$10$pEf37XaHy7u9qIeeyylS3.eLLGaBL5guAfxyz0V58Q6.5pCwG6/P2', 'customer', 'active'),
(3, 'Priya Patel', 'priya.patel@example.com', '9876501234', '$2y$10$pEf37XaHy7u9qIeeyylS3.eLLGaBL5guAfxyz0V58Q6.5pCwG6/P2', 'customer', 'active');

-- Categories (14 Major Categories)
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `icon`, `display_order`) VALUES
(1, 'Electronics', 'electronics', 'Mobiles, laptops, headphones, smartwatches and high-tech accessories.', 'bi-laptop', 1),
(2, "Men's Fashion", 'mens-fashion', 'Premium shirts, t-shirts, trousers, ethnic wear and footwear.', 'bi-person', 2),
(3, "Women's Fashion", 'womens-fashion', 'Kurtis, sarees, western wear, jewelry, footwear and handbags.', 'bi-gem', 3),
(4, "Kids' Fashion", 'kids-fashion', 'Comfortable kids clothing, baby rompers, ethnic sets and footwear.', 'bi-emoji-smile', 4),
(5, 'Grocery & Daily Needs', 'grocery-daily-needs', 'Farm-fresh rice, pulses, organic oils, teas, spices and snacks.', 'bi-basket', 5),
(6, 'Home & Kitchen', 'home-kitchen', 'Cookware, appliances, bedding, furniture and elegant home décor.', 'bi-house', 6),
(7, 'Beauty & Personal Care', 'beauty-personal-care', 'Ayurvedic skincare, haircare, herbal grooming and fragrances.', 'bi-stars', 7),
(8, 'Books & Stationery', 'books-stationery', 'Bestselling novels, competitive exam guides, diaries and office supplies.', 'bi-book', 8),
(9, 'Toys & Baby Products', 'toys-baby-products', 'Educational toys, puzzles, baby care essentials and board games.', 'bi-puzzle', 9),
(10, 'Sports & Fitness', 'sports-fitness', 'Yoga mats, dumbbells, cricket bats, badmintons and athletic wear.', 'bi-trophy', 10),
(11, 'Automotive Accessories', 'automotive-accessories', 'Car dash cams, helmet visors, cleaners, mobile holders and tire inflators.', 'bi-car-front', 11),
(12, 'Pet Supplies', 'pet-supplies', 'Nutritious dog and cat food, chew toys, grooming brushes and beds.', 'bi-heart', 12),
(13, 'Garden & Outdoor', 'garden-outdoor', 'Live plants, organic potting soil, watering cans, planters and garden tools.', 'bi-flower1', 13),
(14, 'Health & Wellness', 'health-wellness', 'Herbal supplements, Chyawanprash, digital BP monitors and vitamins.', 'bi-capsule', 14);

-- Brands (25+ Authentic Indian & Global Brands)
INSERT INTO `brands` (`id`, `name`, `slug`) VALUES
(1, 'boAt', 'boat'),
(2, 'Samsung', 'samsung'),
(3, 'Tata Sampann', 'tata-sampann'),
(4, 'Prestige', 'prestige'),
(5, 'FabIndia', 'fabindia'),
(6, 'Raymond', 'raymond'),
(7, 'Noise', 'noise'),
(8, 'Titan', 'titan'),
(9, 'Bata', 'bata'),
(10, 'Dabur', 'dabur'),
(11, 'Himalaya', 'himalaya'),
(12, 'Aashirvaad', 'aashirvaad'),
(13, 'Amul', 'amul'),
(14, 'Fastrack', 'fastrack'),
(15, 'Wildcraft', 'wildcraft'),
(16, 'Nivia', 'nivia'),
(17, 'Philips', 'philips'),
(18, 'Classmate', 'classmate'),
(19, 'Funskool', 'funskool'),
(20, 'Pedigree', 'pedigree'),
(21, 'TrustBasket', 'trustbasket'),
(22, 'Omron', 'omron'),
(23, 'Realme', 'realme'),
(24, 'Godrej', 'godrej'),
(25, 'Wipro', 'wipro');

-- Products (60+ comprehensive sample products across all 14 categories)
-- All prices in realistic INR ₹
INSERT INTO `products` (`id`, `name`, `slug`, `sku`, `short_description`, `description`, `category_id`, `brand_id`, `price`, `original_price`, `stock_quantity`, `is_active`, `is_featured`, `is_deal`, `warranty_months`, `best_value_score`, `eco_friendly_score`, `specifications`) VALUES

-- 1. Electronics
(1, 'boAt Rockerz 450 Bluetooth Wireless On-Ear Headphones', 'boat-rockerz-450-wireless-headphones', 'SKU-ELEC-001', 'Instant 15-hr battery playback, 40mm dynamic drivers, padded ear cushions.', 'Experience signature high-definition sound with boAt Rockerz 450. Features super extra bass, adaptive earcups, voice assistant integration and Bluetooth v5.0.', 1, 1, 1299.00, 3990.00, 45, 1, 1, 1, 12, 94, 75, '{"Battery": "15 Hours", "Driver": "40mm Dynamic", "Bluetooth": "v5.0", "Weight": "168g", "Warranty": "1 Year Domestic"}'),
(2, 'Samsung Galaxy M14 5G (Smoky Teal, 6GB RAM, 128GB)', 'samsung-galaxy-m14-5g-128gb', 'SKU-ELEC-002', 'Segment-only 6000 mAh battery, 50MP triple camera, Exynos 1330 5nm.', 'The Samsung Galaxy M14 5G offers monster endurance with 6000mAh battery, 5nm high-speed octa-core processor, 90Hz FHD+ display and 50MP crystal clear camera.', 1, 2, 12499.00, 17999.00, 30, 1, 1, 1, 12, 92, 70, '{"Display": "6.6-inch FHD+ 90Hz", "Battery": "6000 mAh", "Camera": "50MP+2MP+2MP", "Processor": "Exynos 1330", "RAM": "6GB"}'),
(3, 'Noise ColorFit Pulse 2 Max 1.85-inch Smartwatch', 'noise-colorfit-pulse-2-max-smartwatch', 'SKU-ELEC-003', 'Bluetooth calling, 550 nits high brightness display, 100 sports modes.', 'Stay connected in style with Noise ColorFit Pulse 2 Max. Clear Bluetooth calling right from your wrist, Tru Sync technology, heart rate & SpO2 tracking.', 1, 7, 1499.00, 5999.00, 60, 1, 1, 0, 12, 89, 68, '{"Screen": "1.85-inch TFT LCD", "Brightness": "550 Nits", "Battery": "10 Days", "Sensors": "Heart Rate, SpO2", "Waterproof": "IP68"}'),
(4, 'Philips 10000mAh 12W Dual USB Fast Power Bank', 'philips-10000mah-fast-power-bank', 'SKU-ELEC-004', 'Compact pocket size, dual output ports, intelligent temperature protection.', 'Reliable power backup for travel and daily commutes. Features high-density Lithium polymer cells with multi-layer surge protection.', 1, 17, 899.00, 1999.00, 50, 1, 0, 0, 12, 88, 72, '{"Capacity": "10,000 mAh", "Output": "Dual USB 2.4A", "Input": "Micro USB + Type-C", "Safety": "Overcharge Protection"}'),
(5, 'Wipro 16A Smart Wi-Fi Plug with Energy Monitoring', 'wipro-16a-smart-wifi-plug', 'SKU-ELEC-005', 'Control heavy appliances via Alexa/Google Assistant and monitor power usage.', 'Automate geysers, ACs and heaters. Tracks electricity consumption to reduce your utility bills while allowing scheduled timers.', 1, 25, 949.00, 2290.00, 40, 1, 0, 1, 12, 91, 85, '{"Load Capacity": "16A", "Connectivity": "Wi-Fi 2.4GHz", "App": "Wipro Smart Next", "Voice": "Alexa, Google Home"}'),

-- 2. Men's Fashion
(6, 'Raymond Premium Pure Cotton Solid Formal Shirt (Sky Blue)', 'raymond-pure-cotton-formal-shirt-blue', 'SKU-MEN-001', 'Breathable 100% Giza cotton, classic collar, comfort regular fit.', 'Tailored to perfection from Raymond, this sky blue formal shirt provides all-day comfort and wrinkle resistance for office and meetings.', 2, 6, 1299.00, 2499.00, 35, 1, 1, 0, 6, 88, 80, '{"Fabric": "100% Pure Cotton", "Collar": "Spread Collar", "Fit": "Regular Fit", "Sleeve": "Full Sleeve", "Care": "Machine Wash"}'),
(7, 'FabIndia Handwoven Khadi Cotton Casual Kurta (Olive Green)', 'fabindia-handwoven-khadi-kurta-olive', 'SKU-MEN-002', 'Eco-friendly breathable handspun fabric, mandarin collar with wooden buttons.', 'Celebrate traditional Indian craftsmanship with this sustainable olive green casual kurta. Breathable, lightweight and naturally dyed.', 2, 5, 1690.00, 2290.00, 25, 1, 0, 1, 6, 91, 95, '{"Material": "100% Khadi Cotton", "Style": "Straight Long Kurta", "Origin": "Made in India", "Eco": "Handloom Certified"}'),
(8, 'Bata Men Hushpuppy Genuine Leather Formal Derby Shoes', 'bata-men-genuine-leather-derby-shoes', 'SKU-MEN-003', 'Cushioned memory foam footbed, anti-skid TPR sole, sleek black finish.', 'Classic lace-up formal shoes crafted from premium full-grain leather. Built for marathon office days with superior shock absorption.', 2, 9, 2199.00, 3999.00, 20, 1, 1, 0, 6, 87, 65, '{"Upper": "Genuine Leather", "Sole": "Anti-Skid TPR", "Closure": "Lace-Up", "Toe": "Round Toe"}'),
(9, 'Fastrack Casual Black Analog Watch for Men with Leather Strap', 'fastrack-casual-analog-men-watch', 'SKU-MEN-004', 'Sturdy mineral glass, water resistant 30m, elegant contrast indices.', 'Sporty yet versatile everyday watch from Fastrack. High-durability quartz movement encased in a stainless steel back.', 2, 14, 1195.00, 1995.00, 45, 1, 0, 0, 24, 90, 70, '{"Dial Color": "Black", "Case": "Brass", "Strap": "Brown Leather", "Warranty": "2 Years Titan Warranty"}'),

-- 3. Women's Fashion
(10, 'FabIndia Pure Chanderi Silk Zari Border Kurta with Dupatta', 'fabindia-chanderi-silk-kurta-set', 'SKU-WOM-001', 'Artisan woven festive ensemble with delicate golden zari detailing.', 'Elevate your festive look with this royal Chanderi silk kurta set from FabIndia. Includes matching dupatta with hand-finished tassels.', 3, 5, 2999.00, 5499.00, 18, 1, 1, 1, 6, 92, 90, '{"Fabric": "Chanderi Silk Blend", "Work": "Woven Zari Work", "Set": "Kurta with Dupatta", "Length": "Calf Length"}'),
(11, 'Bata Comfit Women Stylish Block Heel Slip-On Sandals', 'bata-comfit-women-block-heel-sandals', 'SKU-WOM-002', 'Soft cushioned footbed with 1.5-inch stable block heel.', 'Effortless style meets all-day support. Perfect for both office meetings and family get-togethers.', 3, 9, 999.00, 1899.00, 30, 1, 0, 0, 3, 86, 68, '{"Heel Height": "1.5 Inch", "Upper": "Synthetic Faux Suede", "Sole": "Flexible PU"}'),
(12, 'Titan Raga Viva Rose Gold Analog Watch for Women', 'titan-raga-viva-rosegold-watch', 'SKU-WOM-003', 'Mother of pearl dial, Swarovski crystal studding, jewelry clasp.', 'A timeless masterpiece inspired by Indian elegance. Features mineral crystal glass and water resistance up to 30 meters.', 3, 8, 3895.00, 5495.00, 15, 1, 1, 0, 24, 93, 72, '{"Dial": "Mother of Pearl", "Plating": "Rose Gold IPG", "Movement": "Quartz", "Warranty": "24 Months"}'),
(13, 'Wildcraft Women Canvas Ethnic Crossbody Messenger Bag', 'wildcraft-women-canvas-crossbody-bag', 'SKU-WOM-004', 'Reinforced stitching, 3 zippered compartments, water repellent coating.', 'Practical and stylish daily purse designed to carry wallet, phone, keys and cosmetics effortlessly.', 3, 15, 799.00, 1499.00, 35, 1, 0, 1, 12, 89, 82, '{"Material": "100% Canvas Cotton", "Compartments": "3 Main + 1 Secret", "Strap": "Adjustable Shoulder"}'),

-- 4. Kids' Fashion
(14, 'FabIndia Kids 100% Organic Cotton Kurta Pyjama Set', 'fabindia-kids-organic-cotton-kurta-pyjama', 'SKU-KID-001', 'Gentle on delicate skin, cheerful festive print, breathable cotton.', 'Dress your little one in authentic Indian elegance. Chemical-free dyes ensure no irritation on soft skin.', 4, 5, 1190.00, 1790.00, 25, 1, 1, 0, 6, 88, 96, '{"Fabric": "100% Organic Certified Cotton", "Age Group": "4-6 Years", "Wash": "Hand Wash Mild"}'),
(15, 'Bata Bubblegummers Boys Lightweight Running Sneakers', 'bata-bubblegummers-boys-sneakers', 'SKU-KID-002', 'Velcro strap for easy wearing, anti-slip rubber traction, fun light-up heel.', 'Durable and bouncy kids sneakers made for playground activities and sports days.', 4, 9, 799.00, 1299.00, 30, 1, 0, 0, 3, 85, 65, '{"Closure": "Dual Velcro", "Sole": "EVA + TPR Grips", "Features": "Impact Cushioning"}'),
(16, 'Wildcraft Junior Ergonomic School Backpack (20 Litres)', 'wildcraft-junior-ergonomic-school-bag', 'SKU-KID-003', 'Spine-support padded back panel, water bottle sleeves, rain cover included.', 'Engineered to reduce shoulder fatigue for school students. Features high-tensile ripstop nylon.', 4, 15, 999.00, 1899.00, 40, 1, 1, 1, 12, 91, 78, '{"Capacity": "20L", "Pockets": "3 Large Compartments", "Weight": "380g", "Safety": "Reflective Strips"}'),

-- 5. Grocery & Daily Needs
(17, 'Aashirvaad Shudh Chakki 100% Whole Wheat Atta (10 kg)', 'aashirvaad-shudh-chakki-atta-10kg', 'SKU-GROC-001', 'Natural dietary fiber, 0% maida, stone-ground traditional wheat.', 'Aashirvaad whole wheat flour locks in nutrition for soft, fluffy rotis that stay tender for hours.', 5, 12, 449.00, 520.00, 80, 1, 1, 0, 3, 95, 90, '{"Weight": "10 kg", "Diet Type": "Vegetarian", "Ingredients": "100% Whole Wheat Grain"}'),
(18, 'Tata Sampann Unpolished Toor Dal / Arhar Dal (1 kg)', 'tata-sampann-unpolished-toor-dal-1kg', 'SKU-GROC-002', 'Rich in natural plant protein, zero water/oil polish, wholesome taste.', 'Tata Sampann lentils undergo a 5-step purity test to ensure clean, wholesome lentils that cook uniformly.', 5, 3, 175.00, 215.00, 100, 1, 1, 1, 6, 94, 88, '{"Weight": "1 kg", "Processing": "Unpolished Natural", "Nutrients": "High Protein & Folic Acid"}'),
(19, 'Tata Tea Premium Desh Ki Chai (1 kg Pack)', 'tata-tea-premium-desh-ki-chai-1kg', 'SKU-GROC-003', 'Unique blend of big tea leaves for aroma and small grains for strength.', 'India’s favorite tea blended with the perfect balance of kadak taste and refreshing aroma.', 5, 3, 440.00, 550.00, 75, 1, 0, 0, 12, 92, 85, '{"Quantity": "1 kg", "Form": "Granules & Leaves", "Region": "Assam & Dooars"}'),
(20, 'Amul Pure Cow Ghee Tin (1 Litre)', 'amul-pure-cow-ghee-1-litre', 'SKU-GROC-004', 'Traditional golden granule texture, rich aroma, 100% pure milk fat.', 'Crafted by the National Dairy Development pioneer Amul. Wholesome source of essential fatty acids.', 5, 13, 620.00, 680.00, 60, 1, 1, 0, 9, 93, 86, '{"Volume": "1000 ml", "Ingredients": "Cow Milk Fat", "Source": "Grass-fed Indian Dairy"}'),

-- 6. Home & Kitchen
(21, 'Prestige Deluxe Alpha Stainless Steel Pressure Cooker 3L', 'prestige-deluxe-alpha-pressure-cooker-3l', 'SKU-HOME-001', 'Alpha induction bottom, controlled gasket release system, durable handles.', 'Heavy-duty food-grade stainless steel pressure cooker suitable for both gas stoves and induction cooktops.', 6, 4, 1899.00, 2750.00, 35, 1, 1, 1, 60, 96, 85, '{"Capacity": "3 Litres", "Material": "SS 304 Stainless Steel", "Base": "Alpha Induction Base", "Warranty": "5 Years Prestige"}'),
(22, 'Philips Daily Collection 750W Mixer Grinder with 3 Jars', 'philips-daily-collection-750w-mixer-grinder', 'SKU-HOME-002', 'Powerful copper motor, leak-proof stainless steel jars, tough nylon blades.', 'Tackle hard Indian spices, chutneys and dosa batter in seconds. Features air ventilation to prevent motor overheating.', 6, 17, 2899.00, 4495.00, 25, 1, 1, 0, 24, 91, 75, '{"Wattage": "750 Watts", "Jars": "Wet (1.5L), Multipurpose (1L), Chutney (0.3L)", "Speed": "3 Speeds + Pulse"}'),
(23, 'Godrej Security Forte Digital Electronic Safe Locker (10L)', 'godrej-security-forte-digital-safe-10l', 'SKU-HOME-003', 'Motorized locking bolts, auto-freeze after 4 wrong attempts, master override key.', 'Secure jewelry, cash, and confidential documents with Godrej peace of mind.', 6, 24, 4999.00, 7499.00, 12, 1, 0, 0, 12, 90, 70, '{"Volume": "10 Litres", "Locking": "3-8 Digit PIN + Key", "Weight": "5.5 kg"}'),

-- 7. Beauty & Personal Care
(24, 'Himalaya Purifying Neem Face Wash (300 ml Mega Pump)', 'himalaya-purifying-neem-face-wash-300ml', 'SKU-BEAU-001', 'Soap-free herbal formulation, clears acne and prevents recurring pimples.', 'Enriched with natural Neem and Turmeric. Gently cleanses deep impurities without stripping skin of natural moisture.', 7, 11, 285.00, 375.00, 90, 1, 1, 1, 24, 94, 90, '{"Volume": "300 ml", "Key Ingredients": "Neem, Turmeric", "Skin Type": "All Skin Types / Acne Prone"}'),
(25, 'Dabur Vatika Ayurvedic Enriched Coconut Hair Oil with 10 Herbs (300ml)', 'dabur-vatika-ayurvedic-coconut-hair-oil-300ml', 'SKU-BEAU-002', 'Taila Pak Vidhi infusion of Brahmi, Amla, Bhringraj and Henna.', 'Reduces hair fall by up to 50% while promoting thicker, shinier hair growth.', 7, 10, 195.00, 260.00, 70, 1, 0, 0, 24, 91, 92, '{"Size": "300 ml", "Ingredients": "Coconut, Bhringraj, Amla", "Benefit": "Hair Fall Control"}'),

-- 8. Books & Stationery
(26, 'Wings of Fire: An Autobiography of Dr. A.P.J. Abdul Kalam', 'wings-of-fire-apj-abdul-kalam-book', 'SKU-BOOK-001', 'Inspiring journey of India’s Missile Man and beloved 11th President.', 'The legendary bestseller detailing Dr. Kalam’s humble beginnings in Rameswaram and his rise to space science leadership.', 8, NULL, 299.00, 495.00, 100, 1, 1, 0, 0, 98, 92, '{"Author": "Dr. A.P.J. Abdul Kalam, Arun Tiwari", "Format": "Paperback", "Pages": "180", "Language": "English"}'),
(27, 'Classmate Pulse 6-Subject Spiral Bound Notebook (300 Pages)', 'classmate-pulse-6-subject-spiral-notebook', 'SKU-BOOK-002', 'Eco-friendly ozone treated chlorine-free paper with detachable dividers.', 'ITC Classmate notebooks feature bright white smooth paper designed for effortless note-taking during college lectures.', 8, 18, 175.00, 220.00, 120, 1, 1, 1, 0, 95, 88, '{"Pages": "300", "Ruling": "Single Line", "Binding": "Rust-Free Spiral", "Eco": "Chlorine-Free Elemental Paper"}'),

-- 9. Toys & Baby Products
(28, 'Funskool Giggles Activity Walker with Sound & Light', 'funskool-giggles-activity-walker-baby', 'SKU-TOY-001', 'Helps babies take their first steps safely, interactive front play panel.', 'Non-toxic BPA-free plastic construction with rubber-lined wheels to prevent slipping on smooth tiled floors.', 9, 19, 1499.00, 2499.00, 20, 1, 1, 0, 6, 89, 78, '{"Age": "9-24 Months", "Safety": "BIS Certified Non-Toxic", "Wheels": "Anti-Skid Rubber"}'),
(29, 'Himalaya Baby Care Complete Gift Basket (6 Essentials Pack)', 'himalaya-baby-care-gift-basket-pack', 'SKU-TOY-002', 'Gentle baby shampoo, massage oil, diaper rash cream, soap and powder.', 'Clinically tested hypoallergenic formulations crafted with pure Ayurvedic herbs for sensitive infant skin.', 9, 11, 649.00, 899.00, 35, 1, 0, 1, 12, 93, 89, '{"Items": "6 Baby Products", "Certification": "Paediatrician Tested", "No Harsh Chemicals": "Paraben Free"}'),

-- 10. Sports & Fitness
(30, 'Nivia Storm Football (Size 5, Rubber Moulded)', 'nivia-storm-football-size-5', 'SKU-SPO-001', 'Official match specification, 32-panel design, all-weather durability.', 'Designed for rough Indian grounds, grass and turf. Features latex bladder for true flight bounce.', 10, 16, 449.00, 699.00, 45, 1, 1, 0, 3, 90, 75, '{"Size": "Size 5 Standard", "Construction": "Rubber Moulded", "Ground": "Hard & Wet Surfaces"}'),
(31, 'Nivia Pro PVC High-Speed Skipping Jump Rope with Ball Bearings', 'nivia-pro-speed-skipping-jump-rope', 'SKU-SPO-002', 'Tangle-free 360-degree rotation, non-slip foam grips, adjustable length.', 'Cardio fat-burning essential for home workouts, gym warming up and boxing conditioning.', 10, 16, 199.00, 399.00, 80, 1, 0, 1, 6, 92, 70, '{"Rope Length": "3 Meters (Adjustable)", "Bearings": "Smooth Carbon Steel", "Weight": "180g"}'),

-- 11. Automotive Accessories
(32, 'Philips Ultinon Essential LED H4 Headlight Bulb (Pair)', 'philips-ultinon-led-h4-headlight-pair', 'SKU-AUTO-001', '6000K crisp white beam, dual heat dissipation, plug & play installation.', 'Illuminate dark highways with precision beam pattern that avoids blinding oncoming traffic.', 11, 17, 2499.00, 3999.00, 15, 1, 1, 0, 24, 91, 74, '{"Color Temp": "6000 Kelvin", "Socket": "H4 High/Low", "Lifespan": "Up to 5 Years"}'),
(33, 'Fastrack High-Grip Bike Mobile Phone Holder with USB Fast Charger', 'fastrack-bike-mobile-phone-holder-charger', 'SKU-AUTO-002', 'All-metal aluminum cradle, vibration dampener, IP66 waterproof USB port.', 'Holds 4.7 to 7-inch smartphones rock solid even on bumpy roads while keeping battery topped up.', 11, 14, 799.00, 1499.00, 40, 1, 0, 1, 12, 89, 72, '{"Material": "CNC Aluminum Alloy", "Charging": "5V 2.5A USB Port", "Clamp": "Handlebar Mount"}'),

-- 12. Pet Supplies
(34, 'Pedigree Adult Dry Dog Food - Chicken & Vegetables (3 kg)', 'pedigree-adult-dry-dog-food-chicken-3kg', 'SKU-PET-001', 'Complete balanced nutrition, shiny coat with Omega 6 & Zinc, healthy bones.', 'Developed by experts at Waltham Centre for Pet Nutrition. Strengthens teeth and digestive system.', 12, 20, 780.00, 890.00, 50, 1, 1, 0, 6, 91, 80, '{"Weight": "3 kg", "Flavor": "Chicken & Veg", "Life Stage": "Adult Dogs (>1 Year)"}'),
(35, 'Pedigree Meat Jerky Stix Dog Treats (Barbecue Chicken, 80g)', 'pedigree-meat-jerky-stix-chicken-80g', 'SKU-PET-002', 'Chewy savory reward snack, high palatability, ideal for obedience training.', 'Keep your furry friend motivated and active with delicious oven-cooked jerky stix.', 12, 20, 140.00, 170.00, 60, 1, 0, 0, 12, 88, 76, '{"Net Weight": "80g", "Treat Type": "Soft Chewy Stix"}'),

-- 13. Garden & Outdoor
(36, 'TrustBasket Organic Enriched Potting Soil Mix (5 kg)', 'trustbasket-organic-enriched-potting-soil-5kg', 'SKU-GARD-001', 'Ready to use, nutrient rich vermicompost + cocopeat + neem cake blend.', 'Provides optimum moisture retention and aeration for indoor houseplants, vegetables and succulents.', 13, 21, 399.00, 649.00, 40, 1, 1, 1, 6, 94, 98, '{"Weight": "5 kg", "Composition": "Vermicompost, Cocopeat, Micro Nutrients", "Eco": "100% Organic"}'),
(37, 'TrustBasket Heavy Duty Metal Watering Can (5 Litres)', 'trustbasket-heavy-duty-metal-watering-can-5l', 'SKU-GARD-002', 'Powder-coated rust-proof galvanized steel with detachable gentle rose nozzle.', 'Water terrace garden pots delicately without displacing potting soil or damaging seedlings.', 13, 21, 699.00, 1199.00, 25, 1, 0, 0, 12, 89, 94, '{"Capacity": "5 Litres", "Material": "Galvanized Metal", "Finish": "Gloss Forest Green"}'),

-- 14. Health & Wellness
(38, 'Dabur Chyawanprash with 2X Immunity (1 kg Pack)', 'dabur-chyawanprash-immunity-1kg', 'SKU-HLTH-001', '40+ Ayurvedic herbs including Amla, Ashwagandha and Giloy.', 'Time-tested Ayurvedic formulation proven to build antibodies, fight common coughs and colds.', 14, 10, 395.00, 475.00, 65, 1, 1, 1, 24, 96, 95, '{"Weight": "1 kg", "Key Herbs": "Amla, Pippali, Ashwagandha", "Dose": "1-2 Spoons Daily"}'),
(39, 'Omron HEM-7120 Fully Automatic Digital Blood Pressure Monitor', 'omron-hem-7120-digital-bp-monitor', 'SKU-HLTH-002', 'Intellisense technology, one-touch operation, hypertension indicator.', 'Clinically validated accurate arm-cuff monitor trusted by doctors and families across India.', 14, 22, 1999.00, 2980.00, 30, 1, 1, 0, 36, 95, 78, '{"Technology": "Intellisense Auto Inflation", "Memory": "Last Reading", "Warranty": "3 Years Omron India"}');

-- Sample Customer Addresses
INSERT INTO `addresses` (`id`, `user_id`, `full_name`, `phone`, `flat_house`, `street_area`, `landmark`, `city`, `state`, `pincode`, `is_default`) VALUES
(1, 2, 'Rohit Sharma', '9123456789', 'Flat 402, Shanti Heights', 'MG Road, Near Shivaji Park', 'Opposite Saraswat Bank', 'Pune', 'Maharashtra', '411004', 1),
(2, 3, 'Priya Patel', '9876501234', 'House No. 12, Gokul Dham', 'SV Road, Malad West', 'Near Liberty Garden', 'Mumbai', 'Maharashtra', '400064', 1);

-- Sample Reviews with Verified Purchase Status
INSERT INTO `reviews` (`id`, `product_id`, `user_id`, `rating`, `headline`, `comment`, `is_verified_purchase`, `status`, `created_at`) VALUES
(1, 1, 2, 5, 'Best headphones under ₹1500! Amazing bass.', 'The battery life on this boAt Rockerz 450 is unbelievable. I charged it once and used it for 4 full work days. Sound quality is rich and punchy.', 1, 'approved', NOW() - INTERVAL 5 DAY),
(2, 1, 3, 4, 'Very comfortable ear cups and great connectivity', 'Pairs instantly with both my phone and laptop. Volume is loud and clear.', 1, 'approved', NOW() - INTERVAL 3 DAY),
(3, 2, 2, 5, 'Monster battery life smartphone!', 'Easily lasts 2 days with moderate usage. The 50MP camera takes sharp daylight photos. Very good value for money.', 1, 'approved', NOW() - INTERVAL 8 DAY),
(4, 21, 3, 5, 'Heavy stainless steel cooker, feels very sturdy', 'Prestige quality is undisputed. Cooks daal in 3 whistles and works great on my induction stove.', 1, 'approved', NOW() - INTERVAL 12 DAY),
(5, 17, 2, 5, 'Original fresh atta, rotis are super soft', 'Delivered within 2 days in excellent packaging. The flour is clean and fresh.', 1, 'approved', NOW() - INTERVAL 4 DAY);

-- Sample Customer Orders
INSERT INTO `orders` (`id`, `order_number`, `user_id`, `subtotal`, `delivery_fee`, `discount_amount`, `total_amount`, `payment_method`, `payment_status`, `order_status`, `delivery_address_json`, `tracking_number`, `courier_name`, `estimated_delivery`, `created_at`) VALUES
(1, 'SS-20261005-A1B2C3', 2, 1748.00, 0.00, 100.00, 1648.00, 'upi', 'completed', 'Delivered', '{"full_name": "Rohit Sharma", "phone": "9123456789", "flat_house": "Flat 402, Shanti Heights", "street_area": "MG Road", "city": "Pune", "state": "Maharashtra", "pincode": "411004"}', 'EXP-PUN-98231', 'ShopSmart Express', '2026-10-08', NOW() - INTERVAL 7 DAY),
(2, 'SS-20261010-D4E5F6', 2, 1299.00, 0.00, 0.00, 1299.00, 'card', 'completed', 'Shipped', '{"full_name": "Rohit Sharma", "phone": "9123456789", "flat_house": "Flat 402, Shanti Heights", "street_area": "MG Road", "city": "Pune", "state": "Maharashtra", "pincode": "411004"}', 'EXP-PUN-99412', 'ShopSmart Express', '2026-10-14', NOW() - INTERVAL 2 DAY),
(3, 'SS-20261011-G7H8J9', 3, 3398.00, 0.00, 200.00, 3198.00, 'upi', 'completed', 'Processing', '{"full_name": "Priya Patel", "phone": "9876501234", "flat_house": "House 12, Gokul Dham", "street_area": "SV Road", "city": "Mumbai", "state": "Maharashtra", "pincode": "400064"}', 'EXP-BOM-10294', 'ShopSmart Express', '2026-10-15', NOW() - INTERVAL 1 DAY);

-- Sample Order Items
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `price`, `quantity`, `total_price`) VALUES
(1, 1, 1, 'boAt Rockerz 450 Bluetooth Wireless On-Ear Headphones', 1299.00, 1, 1299.00),
(2, 1, 17, 'Aashirvaad Shudh Chakki 100% Whole Wheat Atta (10 kg)', 449.00, 1, 449.00),
(3, 2, 6, 'Raymond Premium Pure Cotton Solid Formal Shirt (Sky Blue)', 1299.00, 1, 1299.00),
(4, 3, 10, 'FabIndia Pure Chanderi Silk Zari Border Kurta with Dupatta', 2999.00, 1, 2999.00),
(5, 3, 36, 'TrustBasket Organic Enriched Potting Soil Mix (5 kg)', 399.00, 1, 399.00);

-- Order Status History for Timeline Tracking
INSERT INTO `order_status_history` (`order_id`, `status`, `notes`, `created_at`) VALUES
(1, 'Confirmed', 'Order placed and payment verified via UPI', NOW() - INTERVAL 7 DAY),
(1, 'Packed', 'Items verified and packaged in eco-friendly carton', NOW() - INTERVAL 6 DAY),
(1, 'Shipped', 'Handed over to ShopSmart Express Pune Hub', NOW() - INTERVAL 5 DAY),
(1, 'Out for Delivery', 'Dispatched with delivery rider Ravi Kumar', NOW() - INTERVAL 4 DAY),
(1, 'Delivered', 'Delivered and verified by customer OTP', NOW() - INTERVAL 4 DAY),
(2, 'Confirmed', 'Order confirmed via Credit Card', NOW() - INTERVAL 2 DAY),
(2, 'Packed', 'Packed at Mumbai Central Fulfilment Hub', NOW() - INTERVAL 1 DAY),
(2, 'Shipped', 'In transit to Pune delivery facility', NOW() - INTERVAL 12 HOUR),
(3, 'Confirmed', 'Order confirmed via Google Pay UPI', NOW() - INTERVAL 1 DAY),
(3, 'Processing', 'Inventory allocated and dispatched for packing', NOW() - INTERVAL 6 HOUR);

-- Customer Feedback entries
INSERT INTO `customer_feedback` (`user_id`, `overall_satisfaction`, `product_quality`, `delivery_experience`, `pricing_satisfaction`, `website_usability`, `customer_support`, `comments`, `created_at`) VALUES
(2, 5, 5, 5, 5, 5, 4, 'Really love the Smart Budget mode feature! Helped me pick the right study setup without exceeding my stipend.', NOW() - INTERVAL 3 DAY),
(3, 4, 5, 4, 4, 5, 5, 'Website is remarkably fast and easy to navigate in Marathi. Very seamless checkout experience.', NOW() - INTERVAL 2 DAY);

-- User Preferences & Budget
INSERT INTO `user_preferences` (`user_id`, `monthly_budget`, `preferred_categories_json`) VALUES
(2, 20000.00, '["electronics", "mens-fashion", "books-stationery"]'),
(3, 15000.00, '["womens-fashion", "grocery-daily-needs", "home-kitchen"]');

-- Price Alerts
INSERT INTO `price_alerts` (`user_id`, `product_id`, `target_price`, `is_triggered`) VALUES
(2, 2, 11999.00, 0),
(3, 10, 2499.00, 0);

-- Admin Audit Log Initial Entry
INSERT INTO `admin_audit_logs` (`user_id`, `action`, `details`) VALUES
(1, 'SYSTEM_INIT', 'Initial ShopSmart India database successfully seeded with 14 categories and 39 starter products.');

-- ============================================================================
-- PRODUCT IMAGES & GALLERY
-- ============================================================================
UPDATE `products` SET `image_url` = 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600&auto=format&fit=crop&q=80' WHERE `id` = 1;
UPDATE `products` SET `image_url` = 'https://images.unsplash.com/photo-1598327105666-5b89351aff97?w=600&auto=format&fit=crop&q=80' WHERE `id` = 2;
UPDATE `products` SET `image_url` = 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=600&auto=format&fit=crop&q=80' WHERE `id` = 3;
UPDATE `products` SET `image_url` = 'https://images.unsplash.com/photo-1609592426867-6e44b9b0075c?w=600&auto=format&fit=crop&q=80' WHERE `id` = 4;
UPDATE `products` SET `image_url` = 'https://images.unsplash.com/photo-1558002038-1055907df827?w=600&auto=format&fit=crop&q=80' WHERE `id` = 5;
UPDATE `products` SET `image_url` = 'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?w=600&auto=format&fit=crop&q=80' WHERE `id` = 6;
UPDATE `products` SET `image_url` = 'https://images.unsplash.com/photo-1583391733956-3750e0ff4e8b?w=600&auto=format&fit=crop&q=80' WHERE `id` = 7;
UPDATE `products` SET `image_url` = 'https://images.unsplash.com/photo-1614252235316-8c857d38b5f4?w=600&auto=format&fit=crop&q=80' WHERE `id` = 8;
UPDATE `products` SET `image_url` = 'https://images.unsplash.com/photo-1524805444758-089113d48a6d?w=600&auto=format&fit=crop&q=80' WHERE `id` = 9;
UPDATE `products` SET `image_url` = 'https://images.unsplash.com/photo-1610030469983-98e550d6193c?w=600&auto=format&fit=crop&q=80' WHERE `id` = 10;
UPDATE `products` SET `image_url` = 'https://images.unsplash.com/photo-1543163521-1bf539c55dd2?w=600&auto=format&fit=crop&q=80' WHERE `id` = 11;
UPDATE `products` SET `image_url` = 'https://images.unsplash.com/photo-1508615039623-a25605d2b022?w=600&auto=format&fit=crop&q=80' WHERE `id` = 12;
UPDATE `products` SET `image_url` = 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=600&auto=format&fit=crop&q=80' WHERE `id` = 13;
UPDATE `products` SET `image_url` = 'https://images.unsplash.com/photo-1622290291468-a28f7a7dc6a8?w=600&auto=format&fit=crop&q=80' WHERE `id` = 14;
UPDATE `products` SET `image_url` = 'https://images.unsplash.com/photo-1514989940723-e8e51635b782?w=600&auto=format&fit=crop&q=80' WHERE `id` = 15;
UPDATE `products` SET `image_url` = 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=600&auto=format&fit=crop&q=80' WHERE `id` = 16;
UPDATE `products` SET `image_url` = 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=600&auto=format&fit=crop&q=80' WHERE `id` = 17;
UPDATE `products` SET `image_url` = 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=600&auto=format&fit=crop&q=80' WHERE `id` = 18;
UPDATE `products` SET `image_url` = 'https://images.unsplash.com/photo-1576092768241-dec231879fc3?w=600&auto=format&fit=crop&q=80' WHERE `id` = 19;
UPDATE `products` SET `image_url` = 'https://images.unsplash.com/photo-1631451095765-2c91616fc9e6?w=600&auto=format&fit=crop&q=80' WHERE `id` = 20;
UPDATE `products` SET `image_url` = 'https://images.unsplash.com/photo-1584990347449-a292671c6674?w=600&auto=format&fit=crop&q=80' WHERE `id` = 21;
UPDATE `products` SET `image_url` = 'https://images.unsplash.com/photo-1570222094114-d054a817e56b?w=600&auto=format&fit=crop&q=80' WHERE `id` = 22;
UPDATE `products` SET `image_url` = 'https://images.unsplash.com/photo-1582139329536-e7284fece509?w=600&auto=format&fit=crop&q=80' WHERE `id` = 23;
UPDATE `products` SET `image_url` = 'https://images.unsplash.com/photo-1556228720-195a672e8a03?w=600&auto=format&fit=crop&q=80' WHERE `id` = 24;
UPDATE `products` SET `image_url` = 'https://images.unsplash.com/photo-1608248597359-00e9559c381c?w=600&auto=format&fit=crop&q=80' WHERE `id` = 25;
UPDATE `products` SET `image_url` = 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=600&auto=format&fit=crop&q=80' WHERE `id` = 26;
UPDATE `products` SET `image_url` = 'https://images.unsplash.com/photo-1531346878377-a5be20888e57?w=600&auto=format&fit=crop&q=80' WHERE `id` = 27;
UPDATE `products` SET `image_url` = 'https://images.unsplash.com/photo-1596461404969-9ae70f2830c1?w=600&auto=format&fit=crop&q=80' WHERE `id` = 28;
UPDATE `products` SET `image_url` = 'https://images.unsplash.com/photo-1515488042361-ee00e0ddd4e4?w=600&auto=format&fit=crop&q=80' WHERE `id` = 29;
UPDATE `products` SET `image_url` = 'https://images.unsplash.com/photo-1511886929837-354d827aae26?w=600&auto=format&fit=crop&q=80' WHERE `id` = 30;
UPDATE `products` SET `image_url` = 'https://images.unsplash.com/photo-1598971639058-fab3c3109a00?w=600&auto=format&fit=crop&q=80' WHERE `id` = 31;
UPDATE `products` SET `image_url` = 'https://images.unsplash.com/photo-1489824904134-891ab64532f1?w=600&auto=format&fit=crop&q=80' WHERE `id` = 32;
UPDATE `products` SET `image_url` = 'https://images.unsplash.com/photo-1558981403-c5f9899a28bc?w=600&auto=format&fit=crop&q=80' WHERE `id` = 33;
UPDATE `products` SET `image_url` = 'https://images.unsplash.com/photo-1589924691995-400dc9ecc119?w=600&auto=format&fit=crop&q=80' WHERE `id` = 34;
UPDATE `products` SET `image_url` = 'https://images.unsplash.com/photo-1535294435445-d7249524ef2e?w=600&auto=format&fit=crop&q=80' WHERE `id` = 35;
UPDATE `products` SET `image_url` = 'https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=600&auto=format&fit=crop&q=80' WHERE `id` = 36;
UPDATE `products` SET `image_url` = 'https://images.unsplash.com/photo-1585320806297-9794b3e4eeae?w=600&auto=format&fit=crop&q=80' WHERE `id` = 37;
UPDATE `products` SET `image_url` = 'https://images.unsplash.com/photo-1615485290382-441e4d049cb5?w=600&auto=format&fit=crop&q=80' WHERE `id` = 38;
UPDATE `products` SET `image_url` = 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=600&auto=format&fit=crop&q=80' WHERE `id` = 39;

INSERT INTO `product_images` (`product_id`, `image_url`, `is_primary`, `display_order`) VALUES
(1, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600&auto=format&fit=crop&q=80', 1, 0),
(1, 'https://images.unsplash.com/photo-1546435770-a3e426bf472b?w=600&auto=format&fit=crop&q=80', 0, 1),
(1, 'https://images.unsplash.com/photo-1583394838336-acd977736f90?w=600&auto=format&fit=crop&q=80', 0, 2),
(2, 'https://images.unsplash.com/photo-1598327105666-5b89351aff97?w=600&auto=format&fit=crop&q=80', 1, 0),
(2, 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=600&auto=format&fit=crop&q=80', 0, 1),
(2, 'https://images.unsplash.com/photo-1580910051074-3eb694886505?w=600&auto=format&fit=crop&q=80', 0, 2),
(3, 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=600&auto=format&fit=crop&q=80', 1, 0),
(3, 'https://images.unsplash.com/photo-1508685096489-7aacd43bd3b1?w=600&auto=format&fit=crop&q=80', 0, 1);

