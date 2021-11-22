-- phpMyAdmin SQL Dump
-- version 5.1.0
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 23-09-2021 a las 17:52:49
-- Versión del servidor: 10.4.18-MariaDB
-- Versión de PHP: 7.4.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `accounting_hussains`
--

-- --------------------------------------------------------

--  Clase Grupo Cuenta Subcuenta
-- Estructura de tabla para la tabla `accounts`
--

-- accountCategorySub -> accountAccount
-- accountCategory -> accountGroup

CREATE TABLE `subaccounts` (
  `accountID` bigint(20) NOT NULL,
  `userID` bigint(20) NOT NULL,
  `accountName` text NOT NULL,
  `accountAccount` int(50) NOT NULL,
  `accountSide` varchar(25) NOT NULL,
  `accountBalance` decimal(10,2) NOT NULL,
  `accountDebit` decimal(10,2) NOT NULL,
  `accountCredit` decimal(10,2) NOT NULL,
  `accountOrder` int(11) NOT NULL,
  `accountStatus` int(1) NOT NULL DEFAULT 1,
  `accountStatement` varchar(50) NOT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `accountCreationDate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `accounts_accounts`
--

CREATE TABLE `accounts_accounts` (
  `accountID` bigint(20) NOT NULL,
  `groupID` bigint(20) NOT NULL,
  `accountName` varchar(50) NOT NULL,
  `accountDescription` text DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `accounts_group`
--

CREATE TABLE `accounts_group` (
  `groupID` bigint(20) NOT NULL,
  `classID` bigint(20) NOT NULL,
  `groupName` varchar(50) NOT NULL,
  `groupDescription` text DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `accounts_class` (
  `classID` bigint(20) NOT NULL,
  `className` varchar(50) NOT NULL,
  `classDescription` text DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `accounts_accounts`
--
ALTER TABLE `subaccounts`
  ADD PRIMARY KEY (`accountID`);

--
-- Indices de la tabla `accounts_accounts`
--
ALTER TABLE `accounts_accounts`
  ADD PRIMARY KEY (`accountID`);

--
-- Indices de la tabla `accounts_group`
--
ALTER TABLE `accounts_group`
  ADD PRIMARY KEY (`groupID`);

  --
-- Indices de la tabla `accounts_class`
--
ALTER TABLE `accounts_class`
  ADD PRIMARY KEY (`classID`);


--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `accounts_accounts`
--
ALTER TABLE `subaccounts`
  MODIFY `accountID` bigint(20) NOT NULL AUTO_INCREMENT;
  
--
-- AUTO_INCREMENT de la tabla `accounts_accounts`
--
ALTER TABLE `accounts_accounts`
  MODIFY `accountID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `accounts_group`
--
ALTER TABLE `accounts_group`
  MODIFY `groupID` bigint(20) NOT NULL AUTO_INCREMENT;

  --
-- AUTO_INCREMENT de la tabla `accounts_class`
--
ALTER TABLE `accounts_class`
  MODIFY `classID` bigint(20) NOT NULL AUTO_INCREMENT;


/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
