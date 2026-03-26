-- phpMyAdmin SQL Dump
-- version 5.1.0
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 22-08-2022 a las 02:35:32
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
-- Base de datos: `mamdb`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `catalogues`
--

CREATE TABLE `catalogues` (
  `idCatalogue` int(11) NOT NULL,
  `clientId` int(11) DEFAULT NULL,
  `vendorId` varchar(100) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `storeId` int(11) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `deleted_by` varchar(100) DEFAULT NULL,
  `deleted` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `catalogue_details`
--

CREATE TABLE `catalogue_details` (
  `catalogueId` int(11) DEFAULT NULL,
  `productId` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `catalogues`
--
ALTER TABLE `catalogues`
  ADD PRIMARY KEY (`idCatalogue`),
  ADD KEY `fk_invoice_vendor` (`vendorId`),
  ADD KEY `fk_invoice_store` (`storeId`),
  ADD KEY `fk_invoice_client` (`clientId`);

--
-- Indices de la tabla `catalogue_details`
--
ALTER TABLE `catalogue_details`
  ADD KEY `fk_catalogue_cataloguesid` (`catalogueId`),
  ADD KEY `fk_catalogue_product` (`productId`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `catalogues`
--
ALTER TABLE `catalogues`
  MODIFY `idCatalogue` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `catalogue_details`
--
ALTER TABLE `catalogue_details`
  ADD CONSTRAINT `fk_catalogue_cataloguesid` FOREIGN KEY (`catalogueId`) REFERENCES `catalogues` (`idCatalogue`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_catalogue_product` FOREIGN KEY (`productId`) REFERENCES `products` (`idProduct`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
