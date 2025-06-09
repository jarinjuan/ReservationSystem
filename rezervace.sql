-- phpMyAdmin SQL Dump
-- version 4.7.4
-- https://www.phpmyadmin.net/
--
-- Počítač: 127.0.0.1
-- Vytvořeno: Úte 03. čen 2025, 08:23
-- Verze serveru: 10.1.28-MariaDB
-- Verze PHP: 7.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Databáze: `rezervace`
--

-- --------------------------------------------------------

--
-- Struktura tabulky `classrooms`
--

CREATE DATABASE IF NOT EXISTS rezervace;
USE rezervace;



CREATE TABLE `classrooms` (
  `id` int(11) NOT NULL,
  `description` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `classroom_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `time_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `time_started` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `time_ended` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `status` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `role` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

--
-- Vypisuji data pro tabulku `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES
(1, 'a', '$2y$10$uOl5CnxFXc5QXlaq8TC.ZOLQY3cRy.nV5y5T1VTtuQj4gFd0aWZVi', 'admin'),
(2, 'pan pes', '$2y$10$E5YpDC0Cr/4QyyJBP4alG./yWcJutaLe3hJLkfQklgM33HTIM4ac2', 'adminek'),
(3, 'blazen', '$2y$10$o.ZrakDdnU3Uf.N9leQyD.6dGZ3PTSWTZuVvh4FSPhXpHfKsQxnuC', 'Admin'),
(4, '4', '$2y$10$jSlrn7uL1W3QsPJHWeWdpuPLXIRKywszQKSH25t1KPTRJHy1vf3hq', 'Reader'),
(5, '7', '$2y$10$FiN1Ve.KdMcl8CCaOVbCSuHzX865sleY6uc43iUGVN8muzK3SOSpu', 'Customer'),
(6, '10', '$2y$10$pwWFCyIEcrB.Q5q5V2/4OuGVOiJu6gY/Z6F8DAK3esGg9cybxkrvy', 'Approver'),
(7, 'xdd', '$2y$10$IZtPGWWP7MOCRCmA0Uml3uXLLzAvE09bnBUEqz/iV7UMo0bDqAryW', 'Approver');

--
-- Klíče pro exportované tabulky
--

--
-- Klíče pro tabulku `classrooms`
--
ALTER TABLE `classrooms`
  ADD PRIMARY KEY (`id`);

--
-- Klíče pro tabulku `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user` (`user_id`);

--
-- Klíče pro tabulku `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pro tabulky
--

--
-- AUTO_INCREMENT pro tabulku `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Omezení pro exportované tabulky
--

--
-- Omezení pro tabulku `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
