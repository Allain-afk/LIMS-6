-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 04, 2025 at 12:57 PM
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
-- Database: `lims`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `performed_by` varchar(100) NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'completed',
  `related_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `username`, `password`, `FirstName`, `LastName`, `email`, `created_at`) VALUES
(1, 'allain', '123', 'Allain', 'Legaspi', 'allain@test.com', '2025-03-24 07:30:46'),
(2, 'ayham', '123', 'Ayham', 'Kalsam', 'ayham@test.com', '2025-03-24 07:30:46');

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `book_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(100) NOT NULL,
  `language` varchar(50) NOT NULL,
  `type` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `availability` varchar(20) DEFAULT 'Available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`book_id`, `title`, `author`, `language`, `type`, `created_at`, `availability`) VALUES
(1, 'The Art of Computer Programming', 'Donald Knuth', 'English', 'Technical', '2025-03-23 23:35:33', 'Available'),
(2, 'Pride and Prejudice', 'Jane Austen', 'English', 'Literature', '2025-03-23 23:35:33', 'Available'),
(3, 'One Hundred Years of Solitude', 'Gabriel García Márquez', 'English', 'Fiction', '2025-03-23 23:35:33', 'Available'),
(4, 'Introduction to Algorithms', 'Thomas H. Cormen', 'English', 'Educational', '2025-03-23 23:35:33', 'Available'),
(5, 'The Great Gatsby', 'F. Scott Fitzgerald', 'English', 'Literature', '2025-03-23 23:35:33', 'Available'),
(6, 'Hibernate - Core', 'Allain', 'English', 'Educational', '2025-03-26 03:59:11', 'Available'),
(7, 'Java Programming', 'Allain', 'English', 'Educational', '2025-03-26 03:59:11', 'Available'),
(8, 'Web Development', 'Allain', 'English', 'Educational', '2025-03-26 03:59:11', 'Available'),
(9, 'Database Design', 'Allain', 'English', 'Educational', '2025-03-26 03:59:11', 'Available'),
(10, 'Python Basics', 'Allain', 'English', 'Educational', '2025-03-26 03:59:11', 'Available'),
(11, 'Data Structures', 'Allain', 'English', 'Educational', '2025-03-26 03:59:11', 'Available'),
(12, 'Algorithms', 'Allain', 'English', 'Magazine', '2025-03-26 03:59:11', 'Available'),
(13, 'Superman', 'Ayham', 'English', 'Fiction', '2025-03-28 10:31:43', 'Available'),
(14, 'Spiderman', 'Ayham', 'English', 'Fiction', '2025-03-28 10:32:10', 'Available'),
(15, 'Mr Kupido', 'Ayham', 'English', 'Fiction', '2025-03-28 10:34:04', 'Available'),
(16, 'Gabi ng Lagim', 'France', 'Filipino', 'Fiction', '2025-03-28 10:55:13', 'Available'),
(17, 'The Good Samaritan', 'Ayham', 'English', 'Fiction', '2025-03-30 18:20:58', 'Available'),
(18, 'Libro ni Hanz', 'Hanz Magbal', 'Arabic', 'Fiction', '2025-03-30 19:18:11', 'Available'),
(19, 'Bookers', 'Ayham', 'Arabic', 'Drama', '2025-03-30 19:21:43', 'Available'),
(20, 'Fifty Shades of Gray', 'Bolambao', 'English', 'Romance', '2025-03-31 03:25:31', 'Available'),
(21, 'How to play as an assassin in Mobile Legends', 'Norman', 'English', 'Educational', '2025-03-31 03:28:37', 'Available');

-- --------------------------------------------------------

--
-- Table structure for table `borrowed_books`
--

CREATE TABLE `borrowed_books` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `borrow_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `due_date` timestamp NOT NULL DEFAULT (current_timestamp() + interval 7 day),
  `return_date` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `branch_id` int(11) NOT NULL,
  `branch_name` varchar(100) NOT NULL,
  `branch_location` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`branch_id`, `branch_name`, `branch_location`) VALUES
(1, 'Ayham Bookstore', 'Cebu City'),
(2, 'Allain Library', 'Carcar City'),
(3, 'Good Stuff Books', 'Lapu Lapu City');

-- --------------------------------------------------------

--
-- Table structure for table `otp`
--

CREATE TABLE `otp` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `otp` varchar(6) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `FirstName` varchar(155) DEFAULT NULL,
  `LastName` varchar(155) DEFAULT NULL,
  `contactNo` varchar(155) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT 'default.jpg'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`);

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`book_id`);

--
-- Indexes for table `borrowed_books`
--
ALTER TABLE `borrowed_books`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`branch_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `book_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `borrowed_books`
--
ALTER TABLE `borrowed_books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `branch_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
-- Database schema for LIMS
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE users MODIFY COLUMN password VARCHAR(255) NOT NULL;

ALTER TABLE branches
ADD COLUMN contact_number VARCHAR(11) NULL;
-- Create branches table if it doesn't exist
CREATE TABLE IF NOT EXISTS branches (
    branch_id INT AUTO_INCREMENT PRIMARY KEY,
    branch_name VARCHAR(255) NOT NULL,
    branch_location VARCHAR(255) NOT NULL,
    contact_number VARCHAR(11) NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE books ADD COLUMN cover_image_url TEXT;
ALTER TABLE users
ADD COLUMN IF NOT EXISTS first_name VARCHAR(50) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS last_name VARCHAR(50) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS phone VARCHAR(15) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1;