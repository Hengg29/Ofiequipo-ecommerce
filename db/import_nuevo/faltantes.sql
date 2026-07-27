USE `ofiequipo2`;

-- Agrega el segundo usuario admin que existe en el dump pero no en esta BD.
-- No se toca el id=1 (ya coincide exactamente con el dump).
INSERT INTO `admin_usuarios` (`id`,`email`,`password_hash`,`nombre`,`rol_id`,`activo`,`creado_en`)
SELECT 2,'admin@ofiequipo.com','$2y$12$6dckJub/imy.BWz.CLDvue/UTjorq7tTnVe5.X4hmxegI9vR44LCW','Administrador',1,1,'2026-05-26 20:36:17'
WHERE NOT EXISTS (SELECT 1 FROM `admin_usuarios` WHERE `id`=2);

-- Crea la tabla cotizaciones (no existe en esta BD).
DROP TABLE IF EXISTS `cotizaciones`;
CREATE TABLE `cotizaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `folio` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `empresa` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mensaje` text COLLATE utf8mb4_unicode_ci,
  `dir_calle` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dir_colonia` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dir_municipio` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dir_cp` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dir_refs` text COLLATE utf8mb4_unicode_ci,
  `envio_tipo` enum('gratis','con_costo','por_definir') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'por_definir',
  `envio_costo` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` enum('pendiente','en_proceso','cotizada','rechazada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cotizaciones` VALUES (1,'COT-2026-00001','Zurisabdai Núñez Velázquez','IEST Anahuac','zurisabdai.nunez@iest.edu.mx','+528331494203','',NULL,NULL,NULL,NULL,NULL,'por_definir',0.00,'cotizada','2026-07-01 05:28:11');

-- Crea la tabla cotizacion_items (no existe en esta BD).
DROP TABLE IF EXISTS `cotizacion_items`;
CREATE TABLE `cotizacion_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cotizacion_id` int NOT NULL,
  `producto_id` int DEFAULT NULL,
  `nombre_producto` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad` int NOT NULL DEFAULT '1',
  `precio` decimal(10,2) NOT NULL DEFAULT '0.00',
  `imagen` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_cot_items` (`cotizacion_id`),
  CONSTRAINT `fk_cot_items` FOREIGN KEY (`cotizacion_id`) REFERENCES `cotizaciones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cotizacion_items` VALUES (1,1,53,'Ofi LA 8016',4,0.00,'image.php?path=Uploads/src/img/10_8cc1b6.jpg');
