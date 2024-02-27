-- phpMyAdmin SQL Dump
-- version 5.1.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 16, 2022 at 04:53 PM
-- Server version: 10.4.24-MariaDB
-- PHP Version: 7.4.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `secureappdev`
--

-- --------------------------------------------------------

--
-- Table structure for table `failedlogins`
--

CREATE TABLE `failedlogins` (
  `event_id` int(11) NOT NULL,
  `ip` varchar(128) NOT NULL,
  `timeStamp` datetime NOT NULL,
  `failedLoginCount` int(11) NOT NULL,
  `lockOutCount` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `loginevents`
--

CREATE TABLE `loginevents` (
  `event_id` int(11) NOT NULL,
  `ip` varchar(128) NOT NULL,
  `timeStamp` datetime NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `outcome` varchar(7) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `sapusers`
--

CREATE TABLE `sapusers` (
  `user_id` int(11) NOT NULL,
  `user_uid` varchar(256) NOT NULL,
  `user_pwd` varchar(256) NOT NULL,
  `user_admin` int(2) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `sapusers`
--

INSERT INTO `sapusers` (`user_id`, `user_uid`, `user_pwd`, `user_admin`) VALUES
(1, 'admin', 'AdminPass1!', 1),
(2, 'Tom', 'Password1!', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `failedlogins`
--
ALTER TABLE `failedlogins`
  ADD PRIMARY KEY (`event_id`);

--
-- Indexes for table `loginevents`
--
ALTER TABLE `loginevents`
  ADD PRIMARY KEY (`event_id`);

--
-- Indexes for table `sapusers`
--
ALTER TABLE `sapusers`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `failedlogins`
--
ALTER TABLE `failedlogins`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loginevents`
--
ALTER TABLE `loginevents`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sapusers`
--
ALTER TABLE `sapusers`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
