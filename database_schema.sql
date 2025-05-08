-- Database schema for Restaurant Management System

-- Create Users table for login
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `admin` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create Tables table
CREATE TABLE IF NOT EXISTS `Tables` (
  `table_id` int(11) NOT NULL AUTO_INCREMENT,
  `capacity` int(11) NOT NULL,
  `status` enum('available','reserved','occupied') NOT NULL DEFAULT 'available',
  `location` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`table_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create Customers table
CREATE TABLE IF NOT EXISTS `Customers` (
  `customer_id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `registration_date` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create EmployeePositions table
CREATE TABLE IF NOT EXISTS `EmployeePositions` (
  `position_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(50) NOT NULL,
  PRIMARY KEY (`position_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create Employees table
CREATE TABLE IF NOT EXISTS `Employees` (
  `employee_id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `position_id` int(11) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `hourly_rate` decimal(10,2) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  PRIMARY KEY (`employee_id`),
  FOREIGN KEY (`position_id`) REFERENCES `EmployeePositions` (`position_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create ShiftTypes table
CREATE TABLE IF NOT EXISTS `ShiftTypes` (
  `shift_type_id` int(11) NOT NULL AUTO_INCREMENT,
  `shift_name` varchar(50) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`shift_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create EmployeeShifts table
CREATE TABLE IF NOT EXISTS `EmployeeShifts` (
  `shift_id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `shift_type_id` int(11) NOT NULL,
  `shift_date` date NOT NULL,
  PRIMARY KEY (`shift_id`),
  FOREIGN KEY (`employee_id`) REFERENCES `Employees` (`employee_id`) ON DELETE CASCADE,
  FOREIGN KEY (`shift_type_id`) REFERENCES `ShiftTypes` (`shift_type_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create Ingredients table
CREATE TABLE IF NOT EXISTS `Ingredients` (
  `ingredient_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `unit` varchar(20) NOT NULL,
  `quantity_in_stock` decimal(10,2) DEFAULT 0,
  `unit_price` decimal(10,2) DEFAULT 0,
  PRIMARY KEY (`ingredient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create MenuItems table
CREATE TABLE IF NOT EXISTS `MenuItems` (
  `menu_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`menu_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create MenuItemIngredients junction table
CREATE TABLE IF NOT EXISTS `MenuItemIngredients` (
  `menu_item_id` int(11) NOT NULL,
  `ingredient_id` int(11) NOT NULL,
  `quantity_required` decimal(10,2) NOT NULL,
  PRIMARY KEY (`menu_item_id`, `ingredient_id`),
  FOREIGN KEY (`menu_item_id`) REFERENCES `MenuItems` (`menu_item_id`) ON DELETE CASCADE,
  FOREIGN KEY (`ingredient_id`) REFERENCES `Ingredients` (`ingredient_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create Orders table
CREATE TABLE IF NOT EXISTS `Orders` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `table_id` int(11) DEFAULT NULL,
  `order_date` timestamp DEFAULT CURRENT_TIMESTAMP,
  `total_amount` decimal(10,2) DEFAULT 0,
  `order_status` enum('new','preparing','ready','delivered','paid','cancelled') NOT NULL DEFAULT 'new',
  PRIMARY KEY (`order_id`),
  FOREIGN KEY (`customer_id`) REFERENCES `Customers` (`customer_id`) ON DELETE SET NULL,
  FOREIGN KEY (`employee_id`) REFERENCES `Employees` (`employee_id`) ON DELETE SET NULL,
  FOREIGN KEY (`table_id`) REFERENCES `Tables` (`table_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create OrderDetails table
CREATE TABLE IF NOT EXISTS `OrderDetails` (
  `order_detail_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `menu_item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL,
  `special_instructions` text DEFAULT NULL,
  PRIMARY KEY (`order_detail_id`),
  FOREIGN KEY (`order_id`) REFERENCES `Orders` (`order_id`) ON DELETE CASCADE,
  FOREIGN KEY (`menu_item_id`) REFERENCES `MenuItems` (`menu_item_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create Payments table
CREATE TABLE IF NOT EXISTS `Payments` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `payment_method` enum('cash','credit_card','debit_card') NOT NULL,
  `payment_date` timestamp DEFAULT CURRENT_TIMESTAMP,
  `total_amount` decimal(10,2) NOT NULL,
  `tip_amount` decimal(10,2) DEFAULT 0,
  PRIMARY KEY (`payment_id`),
  FOREIGN KEY (`order_id`) REFERENCES `Orders` (`order_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create Reservations table
CREATE TABLE IF NOT EXISTS `Reservations` (
  `reservation_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `table_id` int(11) NOT NULL,
  `reservation_datetime` datetime NOT NULL,
  `guest_count` int(11) DEFAULT 2,
  `reservation_status` enum('reserved','available') NOT NULL DEFAULT 'reserved',
  PRIMARY KEY (`reservation_id`),
  FOREIGN KEY (`customer_id`) REFERENCES `Customers` (`customer_id`) ON DELETE CASCADE,
  FOREIGN KEY (`table_id`) REFERENCES `Tables` (`table_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 