-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 28, 2026 at 07:21 PM
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
-- Database: `printing_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `gsm_master`
--

CREATE TABLE `gsm_master` (
  `id` int(11) NOT NULL,
  `gsm_value` varchar(50) NOT NULL,
  `gsm_type` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gsm_master`
--

INSERT INTO `gsm_master` (`id`, `gsm_value`, `gsm_type`, `is_active`) VALUES
(1, '300ART', 'art', 1),
(2, '250GSM', 'standard', 1),
(3, '200GSM', 'standard', 1),
(4, '350ART', 'art', 1),
(8, '212', 'art', 1);

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `invoice_no` varchar(50) NOT NULL,
  `job_id` int(11) DEFAULT NULL,
  `invoice_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL,
  `gst_amount` decimal(10,2) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `payment_status` enum('pending','partial','paid') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lamination`
--

CREATE TABLE `lamination` (
  `id` int(11) NOT NULL,
  `job_id` int(11) DEFAULT NULL,
  `size_id` int(11) DEFAULT NULL,
  `gsm_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `lamination_type` varchar(100) DEFAULT NULL,
  `rate` decimal(10,2) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `created_date` date DEFAULT NULL,
  `status` enum('pending','in_progress','completed') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `paper_sizes`
--

CREATE TABLE `paper_sizes` (
  `id` int(11) NOT NULL,
  `size_name` varchar(100) NOT NULL,
  `width_cm` decimal(10,2) DEFAULT NULL,
  `height_cm` decimal(10,2) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `paper_sizes`
--

INSERT INTO `paper_sizes` (`id`, `size_name`, `width_cm`, `height_cm`, `is_active`) VALUES
(1, 'A4', 21.00, 29.70, 1),
(2, 'A3', 29.70, 42.00, 1),
(3, '4 605x760', 60.50, 76.00, 1),
(4, '16.5x23', 16.50, 23.00, 1),
(5, 'Legal', 21.59, 35.56, 1);

-- --------------------------------------------------------

--
-- Table structure for table `paper_stock_in`
--

CREATE TABLE `paper_stock_in` (
  `id` int(11) NOT NULL,
  `party_id` int(11) DEFAULT NULL,
  `size_id` int(11) DEFAULT NULL,
  `gsm_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `weight_kg` decimal(10,2) DEFAULT NULL,
  `rate_per_unit` decimal(10,2) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `invoice_no` varchar(100) DEFAULT NULL,
  `receipt_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `paper_stock_out`
--

CREATE TABLE `paper_stock_out` (
  `id` int(11) NOT NULL,
  `size_id` int(11) DEFAULT NULL,
  `gsm_id` int(11) DEFAULT NULL,
  `quantity_used` int(11) DEFAULT NULL,
  `remaining_stock` int(11) DEFAULT NULL,
  `job_id` int(11) DEFAULT NULL,
  `usage_date` date DEFAULT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `paper_stock_out`
--

INSERT INTO `paper_stock_out` (`id`, `size_id`, `gsm_id`, `quantity_used`, `remaining_stock`, `job_id`, `usage_date`, `purpose`, `created_at`) VALUES
(1, 4, 3, 1030, -1030, 1, '2026-07-28', 'Printing Job JOB-20260728-2815', '2026-07-28 15:41:48');

-- --------------------------------------------------------

--
-- Table structure for table `parties`
--

CREATE TABLE `parties` (
  `id` int(11) NOT NULL,
  `party_name` varchar(255) NOT NULL,
  `party_type` enum('customer','supplier','both') DEFAULT 'customer',
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `gst_no` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parties`
--

INSERT INTO `parties` (`id`, `party_name`, `party_type`, `phone`, `email`, `address`, `gst_no`, `is_active`, `created_at`) VALUES
(1, 'SRG Traders', 'customer', '9876543210', 'srg@example.com', NULL, NULL, 1, '2026-07-28 15:23:57'),
(2, 'ABC Suppliers', 'supplier', '9876543211', 'abc@example.com', NULL, NULL, 1, '2026-07-28 15:23:57'),
(3, 'XYZ Printers', 'both', '9876543212', 'xyz@example.com', NULL, NULL, 1, '2026-07-28 15:23:57'),
(7, 'test', 'supplier', '8279944015', 'vivek.semantics@gmail.com', 'noida', 'fdsfad242342', 1, '2026-07-28 16:57:25');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `payment_mode` enum('cash','cheque','bank_transfer','other') DEFAULT NULL,
  `cheque_no` varchar(50) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `plate_making`
--

CREATE TABLE `plate_making` (
  `id` int(11) NOT NULL,
  `job_id` int(11) DEFAULT NULL,
  `plate_type` varchar(100) DEFAULT NULL,
  `plate_size` varchar(50) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `rate` decimal(10,2) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `created_date` date DEFAULT NULL,
  `status` enum('pending','in_progress','completed') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `printing_details`
--

CREATE TABLE `printing_details` (
  `id` int(11) NOT NULL,
  `job_id` int(11) DEFAULT NULL,
  `size_id` int(11) DEFAULT NULL,
  `gsm_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `min_cap` int(11) DEFAULT NULL,
  `plate_count` int(11) DEFAULT NULL,
  `plate_size` varchar(50) DEFAULT NULL,
  `plate_rate` decimal(10,2) DEFAULT NULL,
  `plate_total` decimal(10,2) DEFAULT NULL,
  `title_print_qty` int(11) DEFAULT NULL,
  `title_rate` decimal(10,2) DEFAULT NULL,
  `title_total` decimal(10,2) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `gst_percentage` decimal(5,2) DEFAULT 5.00,
  `gst_amount` decimal(10,2) DEFAULT NULL,
  `net_amount` decimal(10,2) DEFAULT NULL,
  `tat_required` tinyint(1) DEFAULT 0,
  `waste_sheets` int(11) DEFAULT NULL,
  `total_sheets_consumed` int(11) DEFAULT NULL,
  `back_flap_apply` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `printing_details`
--

INSERT INTO `printing_details` (`id`, `job_id`, `size_id`, `gsm_id`, `quantity`, `min_cap`, `plate_count`, `plate_size`, `plate_rate`, `plate_total`, `title_print_qty`, `title_rate`, `title_total`, `total_amount`, `gst_percentage`, `gst_amount`, `net_amount`, `tat_required`, `waste_sheets`, `total_sheets_consumed`, `back_flap_apply`) VALUES
(1, 1, 4, 3, 2000, 2000, 8, '4 605x760', 370.00, 2960.00, 2000, 0.50, 1000.00, 3960.00, 5.00, 198.00, 4158.00, 0, 30, 1030, 0);

-- --------------------------------------------------------

--
-- Table structure for table `printing_jobs`
--

CREATE TABLE `printing_jobs` (
  `id` int(11) NOT NULL,
  `job_no` varchar(50) NOT NULL,
  `po_no` varchar(100) DEFAULT NULL,
  `booking_date` date DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `job_type` enum('new','reprint') DEFAULT 'new',
  `book_name` varchar(255) DEFAULT NULL,
  `total_quantity` int(11) DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `delivery_to` varchar(255) DEFAULT NULL,
  `status` enum('pending','plate_making','printing','uv_printing','lamination','completed') DEFAULT 'pending',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `printing_jobs`
--

INSERT INTO `printing_jobs` (`id`, `job_no`, `po_no`, `booking_date`, `customer_id`, `job_type`, `book_name`, `total_quantity`, `delivery_date`, `delivery_to`, `status`, `created_by`, `created_at`) VALUES
(1, 'JOB-20260728-2815', 'PO26-1125-B', '2026-07-28', 1, 'new', 'ICSE Book Paper 2', 2008, '0000-00-00', 'SRG traders', 'pending', 2, '2026-07-28 15:41:48');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `role` enum('admin','production','store','accountant') DEFAULT 'production',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `role`, `is_active`, `created_at`) VALUES
(2, 'admin', '$2y$10$SeASZa8hSsdEUzNZxixcRupQTRLCGwQDo8fcPC.SAJ6yztCOVy.S.', 'Administrator', 'admin', 1, '2026-07-28 15:37:34');

-- --------------------------------------------------------

--
-- Table structure for table `uv_printing`
--

CREATE TABLE `uv_printing` (
  `id` int(11) NOT NULL,
  `job_id` int(11) DEFAULT NULL,
  `size_id` int(11) DEFAULT NULL,
  `gsm_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `uv_type` varchar(100) DEFAULT NULL,
  `rate` decimal(10,2) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `created_date` date DEFAULT NULL,
  `status` enum('pending','in_progress','completed') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_stock_balance`
-- (See below for the actual view)
--
CREATE TABLE `v_stock_balance` (
`size_id` int(11)
,`gsm_id` int(11)
,`size_name` varchar(100)
,`gsm_value` varchar(50)
,`total_in` decimal(32,0)
,`total_out` decimal(32,0)
,`current_balance` decimal(33,0)
);

-- --------------------------------------------------------

--
-- Structure for view `v_stock_balance`
--
DROP TABLE IF EXISTS `v_stock_balance`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_stock_balance`  AS SELECT `ps`.`size_id` AS `size_id`, `ps`.`gsm_id` AS `gsm_id`, `pz`.`size_name` AS `size_name`, `g`.`gsm_value` AS `gsm_value`, sum(`ps`.`quantity`) AS `total_in`, coalesce(sum(`po`.`quantity_used`),0) AS `total_out`, sum(`ps`.`quantity`) - coalesce(sum(`po`.`quantity_used`),0) AS `current_balance` FROM (((`paper_stock_in` `ps` left join `paper_stock_out` `po` on(`ps`.`size_id` = `po`.`size_id` and `ps`.`gsm_id` = `po`.`gsm_id`)) left join `paper_sizes` `pz` on(`ps`.`size_id` = `pz`.`id`)) left join `gsm_master` `g` on(`ps`.`gsm_id` = `g`.`id`)) GROUP BY `ps`.`size_id`, `ps`.`gsm_id` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `gsm_master`
--
ALTER TABLE `gsm_master`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_no` (`invoice_no`),
  ADD KEY `job_id` (`job_id`);

--
-- Indexes for table `lamination`
--
ALTER TABLE `lamination`
  ADD PRIMARY KEY (`id`),
  ADD KEY `job_id` (`job_id`),
  ADD KEY `size_id` (`size_id`),
  ADD KEY `gsm_id` (`gsm_id`);

--
-- Indexes for table `paper_sizes`
--
ALTER TABLE `paper_sizes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `paper_stock_in`
--
ALTER TABLE `paper_stock_in`
  ADD PRIMARY KEY (`id`),
  ADD KEY `party_id` (`party_id`),
  ADD KEY `size_id` (`size_id`),
  ADD KEY `gsm_id` (`gsm_id`);

--
-- Indexes for table `paper_stock_out`
--
ALTER TABLE `paper_stock_out`
  ADD PRIMARY KEY (`id`),
  ADD KEY `size_id` (`size_id`),
  ADD KEY `gsm_id` (`gsm_id`);

--
-- Indexes for table `parties`
--
ALTER TABLE `parties`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `plate_making`
--
ALTER TABLE `plate_making`
  ADD PRIMARY KEY (`id`),
  ADD KEY `job_id` (`job_id`);

--
-- Indexes for table `printing_details`
--
ALTER TABLE `printing_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `job_id` (`job_id`),
  ADD KEY `size_id` (`size_id`),
  ADD KEY `gsm_id` (`gsm_id`);

--
-- Indexes for table `printing_jobs`
--
ALTER TABLE `printing_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `job_no` (`job_no`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `uv_printing`
--
ALTER TABLE `uv_printing`
  ADD PRIMARY KEY (`id`),
  ADD KEY `job_id` (`job_id`),
  ADD KEY `size_id` (`size_id`),
  ADD KEY `gsm_id` (`gsm_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `gsm_master`
--
ALTER TABLE `gsm_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `lamination`
--
ALTER TABLE `lamination`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `paper_sizes`
--
ALTER TABLE `paper_sizes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `paper_stock_in`
--
ALTER TABLE `paper_stock_in`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `paper_stock_out`
--
ALTER TABLE `paper_stock_out`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `parties`
--
ALTER TABLE `parties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `plate_making`
--
ALTER TABLE `plate_making`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `printing_details`
--
ALTER TABLE `printing_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `printing_jobs`
--
ALTER TABLE `printing_jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `uv_printing`
--
ALTER TABLE `uv_printing`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `printing_jobs` (`id`);

--
-- Constraints for table `lamination`
--
ALTER TABLE `lamination`
  ADD CONSTRAINT `lamination_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `printing_jobs` (`id`),
  ADD CONSTRAINT `lamination_ibfk_2` FOREIGN KEY (`size_id`) REFERENCES `paper_sizes` (`id`),
  ADD CONSTRAINT `lamination_ibfk_3` FOREIGN KEY (`gsm_id`) REFERENCES `gsm_master` (`id`);

--
-- Constraints for table `paper_stock_in`
--
ALTER TABLE `paper_stock_in`
  ADD CONSTRAINT `paper_stock_in_ibfk_1` FOREIGN KEY (`party_id`) REFERENCES `parties` (`id`),
  ADD CONSTRAINT `paper_stock_in_ibfk_2` FOREIGN KEY (`size_id`) REFERENCES `paper_sizes` (`id`),
  ADD CONSTRAINT `paper_stock_in_ibfk_3` FOREIGN KEY (`gsm_id`) REFERENCES `gsm_master` (`id`);

--
-- Constraints for table `paper_stock_out`
--
ALTER TABLE `paper_stock_out`
  ADD CONSTRAINT `paper_stock_out_ibfk_1` FOREIGN KEY (`size_id`) REFERENCES `paper_sizes` (`id`),
  ADD CONSTRAINT `paper_stock_out_ibfk_2` FOREIGN KEY (`gsm_id`) REFERENCES `gsm_master` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`);

--
-- Constraints for table `plate_making`
--
ALTER TABLE `plate_making`
  ADD CONSTRAINT `plate_making_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `printing_jobs` (`id`);

--
-- Constraints for table `printing_details`
--
ALTER TABLE `printing_details`
  ADD CONSTRAINT `printing_details_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `printing_jobs` (`id`),
  ADD CONSTRAINT `printing_details_ibfk_2` FOREIGN KEY (`size_id`) REFERENCES `paper_sizes` (`id`),
  ADD CONSTRAINT `printing_details_ibfk_3` FOREIGN KEY (`gsm_id`) REFERENCES `gsm_master` (`id`);

--
-- Constraints for table `printing_jobs`
--
ALTER TABLE `printing_jobs`
  ADD CONSTRAINT `printing_jobs_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `parties` (`id`),
  ADD CONSTRAINT `printing_jobs_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `uv_printing`
--
ALTER TABLE `uv_printing`
  ADD CONSTRAINT `uv_printing_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `printing_jobs` (`id`),
  ADD CONSTRAINT `uv_printing_ibfk_2` FOREIGN KEY (`size_id`) REFERENCES `paper_sizes` (`id`),
  ADD CONSTRAINT `uv_printing_ibfk_3` FOREIGN KEY (`gsm_id`) REFERENCES `gsm_master` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
