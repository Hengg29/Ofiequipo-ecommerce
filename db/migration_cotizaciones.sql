-- =====================================================
-- MIGRACIÓN: Sistema de Cotizaciones
-- Ejecutar en orden. Hacer backup antes:
--   mysqldump -u user -p db cotizaciones > cotizaciones_backup.sql
-- =====================================================

DROP TABLE IF EXISTS cotizacion_items;
DROP TABLE IF EXISTS cotizaciones;

CREATE TABLE cotizaciones (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    folio     VARCHAR(20) NOT NULL DEFAULT '',
    nombre    VARCHAR(150) NOT NULL,
    empresa   VARCHAR(150) DEFAULT NULL,
    email     VARCHAR(150) NOT NULL,
    telefono  VARCHAR(25) NOT NULL,
    mensaje   TEXT DEFAULT NULL,
    status    ENUM('pendiente','en_proceso','cotizada','rechazada') NOT NULL DEFAULT 'pendiente',
    fecha     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cotizacion_items (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    cotizacion_id   INT NOT NULL,
    producto_id     INT DEFAULT NULL,
    nombre_producto VARCHAR(255) NOT NULL,
    cantidad        INT NOT NULL DEFAULT 1,
    CONSTRAINT fk_cot_items
        FOREIGN KEY (cotizacion_id) REFERENCES cotizaciones(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
