-- =====================================================
-- SCRIPT DE MIGRACIÓN DE BASE DE DATOS
-- Actualización de estructura de categorías y productos
-- =====================================================

-- Crear la base de datos si no existe
CREATE DATABASE IF NOT EXISTS ofiequipo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ofiequipo;

-- Eliminar tablas si existen (para recrear desde cero)
DROP TABLE IF EXISTS cotizaciones;
DROP TABLE IF EXISTS producto;
DROP TABLE IF EXISTS categoria;

-- =====================================================
-- TABLA: categoria (con soporte para jerarquías)
-- =====================================================
CREATE TABLE categoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    parent_id INT DEFAULT NULL,
    FOREIGN KEY (parent_id) REFERENCES categoria(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- INSERTAR CATEGORÍAS PRINCIPALES Y SUBCATEGORÍAS
-- =====================================================

-- Categoría principal: Sillería
INSERT INTO categoria (nombre, parent_id) VALUES ('Sillería', NULL);

-- Subcategorías de Sillería
INSERT INTO categoria (nombre, parent_id) 
SELECT 'Visita', id FROM categoria WHERE nombre = 'Sillería' AND parent_id IS NULL
UNION ALL SELECT 'Operativa', id FROM categoria WHERE nombre = 'Sillería' AND parent_id IS NULL
UNION ALL SELECT 'Ejecutiva', id FROM categoria WHERE nombre = 'Sillería' AND parent_id IS NULL
UNION ALL SELECT 'Sofás', id FROM categoria WHERE nombre = 'Sillería' AND parent_id IS NULL
UNION ALL SELECT 'Visitantes', id FROM categoria WHERE nombre = 'Sillería' AND parent_id IS NULL
UNION ALL SELECT 'Bancas de espera', id FROM categoria WHERE nombre = 'Sillería' AND parent_id IS NULL
UNION ALL SELECT 'Escolar', id FROM categoria WHERE nombre = 'Sillería' AND parent_id IS NULL;

-- Categoría principal: Almacenaje
INSERT INTO categoria (nombre, parent_id) VALUES ('Almacenaje', NULL);

-- Subcategorías de Almacenaje
INSERT INTO categoria (nombre, parent_id)
SELECT 'Archiveros', id FROM categoria WHERE nombre = 'Almacenaje' AND parent_id IS NULL
UNION ALL SELECT 'Gabinetes', id FROM categoria WHERE nombre = 'Almacenaje' AND parent_id IS NULL
UNION ALL SELECT 'Credenzas', id FROM categoria WHERE nombre = 'Almacenaje' AND parent_id IS NULL;

-- Categoría principal: Línea Italia
INSERT INTO categoria (nombre, parent_id) VALUES ('Línea Italia', NULL);

-- Subcategorías de Línea Italia
INSERT INTO categoria (nombre, parent_id)
SELECT 'Anzio', id FROM categoria WHERE nombre = 'Línea Italia' AND parent_id IS NULL
UNION ALL SELECT 'iwork & privatt', id FROM categoria WHERE nombre = 'Línea Italia' AND parent_id IS NULL
UNION ALL SELECT 'Italia Solución general', id FROM categoria WHERE nombre = 'Línea Italia' AND parent_id IS NULL;

-- Categorías adicionales (sin subcategorías)
INSERT INTO categoria (nombre, parent_id) VALUES 
('Libreros', NULL),
('Mesas', NULL),
('Escritorios', NULL),
('Mesas de Juntas', NULL),
('Islas de Trabajo', NULL),
('Recepción', NULL);

-- =====================================================
-- TABLA: producto (con campo stock)
-- =====================================================
CREATE TABLE producto (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    imagen VARCHAR(500),
    id_categoria INT NOT NULL,
    stock INT NOT NULL DEFAULT 1,
    Destacado INT NOT NULL DEFAULT 0,
    FOREIGN KEY (id_categoria) REFERENCES categoria(id) ON DELETE CASCADE,
    INDEX idx_categoria (id_categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- INSERTAR PRODUCTOS CON CATEGORÍAS CORRECTAS
-- =====================================================

-- ARCHIVEROS (id_categoria: 10 - Subcategoría de Almacenaje)
INSERT INTO producto (nombre, descripcion, id_categoria, imagen, stock, Destacado) VALUES
('AP 2','-Archivero Vertical de 2 gavetas, gavetas tamaño oficio con correderas de extensión.*Cubierta en 28mm. y costados en 16mm.*Laterales no visibles (Frentes de los cajones cubren costados).*Porta folder para papelera colgante.*Cerradura múltiple.Medidas:47x60x70cm.', 10, 'https://www.zamofi.com/wp-content/uploads/2017/06/AP-2.01.png', 1, 0),
('AP 3','-Archivero Vertical de 3 gavetas, gavetas tamaño oficio con correderas de extensión.*Cubierta en 28mm. y costados en 16mm.*Laterales no visibles (Frentes de los cajones cubren costados).*Porta folder para papelera colgante.*Cerradura múltiple.Medidas:47x60x99cm.', 10, 'https://www.zamofi.com/wp-content/uploads/2017/06/AP-3.01.png', 1, 0),
('AP 4','-Archivero Vertical de 4 gavetas, gavetas tamaño oficio con correderas de extensión.*Cubierta en 28mm. y costados en 16mm.*Laterales no visibles (Frentes de los cajones cubren costados).*Porta folder para papelera colgante.*Cerradura múltiple.Medidas:47x60x128cm.', 10, 'https://www.zamofi.com/wp-content/uploads/2017/06/AP-4.01.png', 1, 0),
('APR 2','-Archivero Vertical reforzado de 2 gavetas, gavetas tamaño oficio con correderas de extensión.*Cubierta y costados en 28mm.*Laterales no visibles (Frentes de los cajones cubren costados).*Porta folder para papelera colgante.*Incluye cerradura múltiple.Medidas:50x60x70cm.', 10, 'https://www.zamofi.com/wp-content/uploads/2017/06/APR-2.01.png', 1, 0),
('APR 3','-Archivero Vertical reforzado de 3 gavetas, gavetas tamaño oficio con correderas de extensión.*Cubierta y costados en 28mm.*Laterales no visibles (Frentes de los cajones cubren costados).*Porta folder para papelera colgante.*Incluye cerradura múltiple.Medidas:50x60x99cm.', 10, 'https://www.zamofi.com/wp-content/uploads/2017/06/APR-3.01.png', 1, 0),
('APR 4','-Archivero Vertical reforzado de 4 gavetas, gavetas tamaño oficio con correderas de extensión.*Cubierta y costados en 28mm.*Laterales no visibles (Frentes de los cajones cubren costados).*Porta folder para papelera colgante.*Incluye cerradura múltiple.Medidas:50x60x128cm.', 10, 'https://www.zamofi.com/wp-content/uploads/2017/06/APR-4.01.png', 1, 0),
('AHP 2','-Archivero Horizontal de 2 gavetas, gavetas tamaño oficio con correderas de extensión.*Cubierta y costados en 28mm.*Laterales no visibles (Frentes de los cajones cubren costados).*Doble corredera de extensión.*Cerradura múltiple.Medidas:80x50x70cm.', 10, 'https://www.zamofi.com/wp-content/uploads/2017/06/AHP-2.01.png', 1, 0),
('AHP 3','-Archivero Horizontal de 3 gavetas, gavetas tamaño oficio con correderas de extensión.*Cubierta y costados en 28mm.*Laterales no visibles (Frentes de los cajones cubren costados).*Doble corredera de extensión.*Cerradura múltiple.Medidas:80x50x99cm.', 10, 'https://www.zamofi.com/wp-content/uploads/2017/06/AHP-3.01.png', 1, 0),
('AHP 4','-Archivero Horizontal de 4 gavetas, gavetas tamaño oficio con correderas de extensión.*Cubierta y costados en 28mm.*Laterales no visibles (Frentes de los cajones cubren costados).*Doble corredera de extensión.*Cerradura múltiple.Medidas:80x50x128cm.', 10, 'https://www.zamofi.com/wp-content/uploads/2017/06/AHP-4.01.png', 1, 0),
('AR 2','-Archivero vertical móvil 1+1 cajón papelero y cajón de archivo carta/oficio, corredera de extensión.*Fabricado en melamina de 16mm.*Cerradura múltiple.*4 Rodajas (2 rodajas frontales con freno).Medidas:40x50x60cm.', 10, 'https://www.zamofi.com/wp-content/uploads/2017/06/AR-2.01.png', 1, 0),
('AC 3 / AO 3','-Archivero vertical 2+1 de 3 gavetas, 2 cajones papeleros y 1 cajón de archivo, corredera de extensión.*Fabricado en melamina de 16mm.*Cerradura múltiple.*Porta folder para carpeta colgante.Medidas:AC 3:40x50x71cm.Tamaño carta/oficio AO 3:47x50x71cm.Tamaño oficio.', 10, 'https://www.zamofi.com/wp-content/uploads/2017/06/AC-3.01.png', 1, 0),
('C 2+1','-Archivero pedestal 2+1, 2 cajones papeleros y 1 cajón de archivo, corredera de extensión.*Fabricado en melamina de 16mm.*Cerradura múltiple.*SIN cubierta.Medidas:40x50x72cm.', 10, 'https://www.zamofi.com/wp-content/uploads/2017/06/C-21.01.png', 1, 0),
('C 1+1+1','-Archivero pedestal 1+1+1, 1 claro organizador, 1 cajón papelero y 1 cajón de archivo tamaño oficio, corredera de extensión.*Fabricado en melamina de 16mm.*Cerradura múltiple.*SIN cubierta.Medidas:40x50x72cm.', 10, 'https://www.zamofi.com/wp-content/uploads/2017/06/C-111.01.png', 1, 0);

-- GABINETES (id_categoria: 11 - Subcategoría de Almacenaje)
INSERT INTO producto (nombre, descripcion, id_categoria, imagen, stock, Destacado) VALUES
('GC 80','-Gabinete colgante con puerta, sistema de pistones.*Cerradura.*Sistema de instalación oculta sin tornillos.Medidas:80×35×40cm.', 11, 'https://www.zamofi.com/wp-content/uploads/2017/06/GC-80-300x169.jpg', 1, 0),
('GC 100','-Gabinete colgante con puerta, sistema de pistones.*Cerradura.*Sistema de instalación oculta sin tornillos.Medidas:100×35×40cm.', 11, 'https://www.zamofi.com/wp-content/uploads/2017/06/GC-80-300x169.jpg', 1, 0),
('C 1+1','-Cajonera suspendida 1+1, cajón papelero y cajón de archivo carta/oficio, corredera de extensión.*Fabricado en melamina de 16mm.*Cerradura múltiple.*SIN cubierta.Medidas:40x50x40cm.', 11, 'https://www.zamofi.com/wp-content/uploads/2017/06/C-11.01.png', 1, 0);

-- CREDENZAS (id_categoria: 12 - Subcategoría de Almacenaje)
INSERT INTO producto (nombre, descripcion, id_categoria, imagen, stock, Destacado) VALUES
('CSA 16','-Credenza "Slim III". 2 puertas abatible con entrepaño interno, 1 claro organizador y 1 cajón papelero al centro, estructura metálica tubular cuadrado 1″x1″.*Jaladera 45° integrada en puertas y cajón.*Laterales no visibles (Frentes de puertas y cajón cubren costados).*Cerradura en puertas opcional.Medidas:160x40x75cm.', 12, 'https://www.zamofi.com/wp-content/uploads/2020/05/CZS-16-2.01.png', 1, 0),
('CSP 28','-Credenza "Slim IV". 4 puertas abatible con entrepaño interno, estructura metálica tubular cuadrado 1″x1″.*Jaladera 45° integrada en puertas.*Laterales visibles (Frentes de puertas NO cubren costados).*Cerradura en puertas opcional.Medidas:160x40x75cm.', 12, 'https://www.zamofi.com/wp-content/uploads/2020/05/CZS-28-1.01.png', 1, 0),
('CSC 16','-Credenza "Slim V". 4 puertas abatible, 2 cajones papeleros, estructura metálica tubular cuadrado 1″x1″.*Jaladera 45° integrada en puertas y cajones.*Laterales no visibles (Frentes de puertas cubren costados).*Cerradura en puertas opcional.Medidas:160x40x75cm.', 12, 'https://www.zamofi.com/wp-content/uploads/2020/05/CZS-16-1.01.png', 1, 0),
('CSC 28','-Credenza "Slim VI". 4 puertas abatible, 2 cajones papeleros, estructura metálica tubular cuadrado 1″x1″.*Jaladera 45° integrada en puertas y cajones.*Laterales visibles (Frentes de puertas y cajones NO cubren costados).*Cerradura en puertas opcional.Medidas:160x40x75cm.', 12, 'https://www.zamofi.com/wp-content/uploads/2020/05/CZS-28.01.png', 1, 0),
('CSP 16','-Credenza "Slim 16". 4 puertas abatible con entrepaño interno, estructura metálica tubular cuadrado 1″x1″.*Jaladera 45° integrada en puertas.*Laterales no visibles (Frentes de puertas cubren costados).*Cerradura en puertas opcional.Medidas:160x40x75cm.', 12, 'https://www.zamofi.com/wp-content/uploads/2020/05/CZS-16.01.png', 1, 0),
('CDD','-Credenza Modelo "Delta". 4 puertas abatibles con entrepaño interno.*Jaladera 45° integrada en puertas.*Laterales no visibles (Frentes de puertas cubren costados).*Cerradura en puertas opcional.Medidas:160x40x75cm.', 12, 'https://www.zamofi.com/wp-content/uploads/2017/06/delta-001.png', 1, 0),
('CP 184','-Credenza Ejecutiva. 4 puertas abatibles con entrepaño interno.*Laterales no visibles (Frentes de las puertas cubren costados).*Cerradura opcional.Medidas:180x40x75cm.', 12, 'https://www.zamofi.com/wp-content/uploads/2017/06/CP-185.1.png', 1, 0),
('CP 185','-Credenza Ejecutiva. 4 puertas abatibles con entrepaño interno.*Laterales no visibles (Frentes de las puertas cubren costados).*Cerradura opcional.Medidas:180x50x75cm.', 12, 'https://www.zamofi.com/wp-content/uploads/2017/06/CP-185.2.png', 1, 0),
('CP 164','-Credenza Operativa. 3 puertas abatibles con entrepaño interno.*Laterales no visibles (Frentes de las puertas cubren costados).*Cerradura opcional.Medidas:160x40x75cm.', 12, 'https://www.zamofi.com/wp-content/uploads/2017/06/CP-165.1.png', 1, 0),
('CP 165','-Credenza Operativa. 3 puertas abatibles con entrepaño interno.*Laterales no visibles (Frentes de las puertas cubren costados).*Cerradura opcional.Medidas:160x50x75cm.', 12, 'https://www.zamofi.com/wp-content/uploads/2017/06/CP-165.2.png', 1, 0),
('CP 84','-Credenza Multiusos. 2 puertas abatibles con entrepaño interno.*Laterales no visibles (Frentes de puertas cubren costados).*Cerradura opcional.Medidas:80x40x75cm.', 12, 'https://www.zamofi.com/wp-content/uploads/2017/06/CP-125.1.png', 1, 0),
('CP 85','-Credenza Multiusos. 2 puertas abatibles con entrepaño interno.*Laterales no visibles (Frentes de puertas cubren costados).*Cerradura opcional.Medidas:80x50x75cm.', 12, 'https://www.zamofi.com/wp-content/uploads/2017/06/CP-125.2.png', 1, 0),
('CP 124','-Credenza Multiusos. 2 puertas abatibles con entrepaño interno.*Laterales no visibles (Frentes de puertas cubren costados).*Cerradura opcional.Medidas:120x40x75cm.', 12, 'https://www.zamofi.com/wp-content/uploads/2017/06/CZ-165.1.png', 1, 0),
('CP 125','-Credenza Multiusos. 2 puertas abatibles con entrepaño interno.*Laterales no visibles (Frentes de puertas cubren costados).*Cerradura opcional.Medidas:120x50x75cm.', 12, 'https://www.zamofi.com/wp-content/uploads/2017/06/CZ-165.2.png', 1, 0),
('CZ 164','-Credenza Modelo "Z". 2 puertas abatibles con entrepaño interno, 2 cajones papeleros y 1 cajón de archivo, tamaño oficio.*Laterales no visibles (Frentes de puertas y cajones cubren costados).*Cerradura múltiple en cajones.*Cerradura en puertas opcional.Medidas:160x40x75cm.', 12, 'https://www.zamofi.com/wp-content/uploads/2017/06/CZ-185.1.png', 1, 0),
('CZ 165','-Credenza Modelo "Z". 2 puertas abatibles con entrepaño interno, 2 cajones papeleros y 1 cajón de archivo, tamaño oficio.*Laterales no visibles (Frentes de puertas y cajones cubren costados).*Cerradura múltiple en cajones.*Cerradura en puertas opcional.Medidas:160x50x75cm.', 12, 'https://www.zamofi.com/wp-content/uploads/2017/06/CZ-185.2.png', 1, 0),
('CZ 184','-Credenza Modelo "Z". 2 puertas abatibles con entrepaño interno, doble cajonera con 2 cajones papeleros y 1 cajón de archivo, tamaño oficio.*Laterales no visibles (Frentes de puertas y cajones cubren costados).*Cerradura múltiple en cajones.*Cerradura en puertas opcional.Medidas:180x40x75cm.', 12, 'https://www.zamofi.com/wp-content/uploads/2017/06/tempo-001.png', 1, 0),
('CZ 185','-Credenza Modelo "Z". 2 puertas abatibles con entrepaño interno, doble cajonera con 2 cajones papeleros y 1 cajón de archivo, tamaño oficio.*Laterales no visibles (Frentes de puertas y cajones cubren costados).*Cerradura múltiple en cajones.*Cerradura en puertas opcional.Medidas:180x50x75cm.', 12, 'https://www.zamofi.com/wp-content/uploads/2017/06/tempo-002.png', 1, 0);

-- MESAS (id_categoria: 18)
INSERT INTO producto (nombre, descripcion, id_categoria, imagen, stock, Destacado) VALUES
('ME 1200','-Mesa de trabajo con estructura metálica cuadrada 1"x1". *Cubierta en melamina 25mm. *Tapas metálicas inferiores en forma de U. *Opcional pasacables o canaleta porta cables. Medidas: 120x60x75cm.', 18, 'https://www.zamofi.com/wp-content/uploads/2018/05/ME-1200-300x169.png', 1, 0),
('MA 120/140','-Mesa de trabajo "Alfa". *Estructura metálica cuadrada 1"x1". *Cubierta en melamina de 25mm. *Faldón metálico inferior. *Opcional pasacables o canaleta porta cables. Medidas: 120x60x75cm y 140x70x75cm.', 18, 'https://www.zamofi.com/wp-content/uploads/2018/05/MA-120-140-300x169.png', 1, 0),
('MTE','-Mesa tipo escritorio "Económica". *Cubierta y costados en melamina 25mm. *Faldón inferior 16mm. *Opcional pasacables o canaleta porta cables. Medidas: 120x60x75cm.', 18, 'https://www.zamofi.com/wp-content/uploads/2018/05/MTE-300x169.png', 1, 0),
('LTM','-Mesa de trabajo "Lite M". *Cubierta en melamina 25mm. *Estructura metálica cuadrada 1"x1". *Tapas metálicas inferiores. *Opcional canaleta porta cables. Medidas: 140x70x75cm.', 18, 'https://www.zamofi.com/wp-content/uploads/2018/05/LTM-300x169.png', 1, 0),
('LT 1','-Mesa de trabajo "Lite". *Cubierta en melamina 25mm. *Estructura metálica cuadrada 1"x1". *Faldón metálico inferior. Medidas: 120x60x75cm.', 18, 'https://www.zamofi.com/wp-content/uploads/2018/05/LT-1-300x169.png', 1, 0),
('MS 1260','-Mesa de trabajo "Sigma". *Estructura metálica cuadrada 1"x1". *Cubierta en melamina 25mm. *Niveladores plásticos. Medidas: 120x60x75cm.', 18, 'https://www.zamofi.com/wp-content/uploads/2018/05/MS-1260-300x169.png', 1, 0),
('MS 1470','-Mesa de trabajo "Sigma". *Estructura metálica cuadrada 1"x1". *Cubierta en melamina 25mm. *Niveladores plásticos. Medidas: 140x70x75cm.', 18, 'https://www.zamofi.com/wp-content/uploads/2018/05/MS-1470-300x169.png', 1, 0),
('MS 1212','-Mesa de trabajo "Sigma". *Estructura metálica cuadrada 1"x1". *Cubierta en melamina 25mm. *Niveladores plásticos. Medidas: 120x120x75cm.', 18, 'https://www.zamofi.com/wp-content/uploads/2018/05/MS-1212-300x169.png', 1, 0),
('MM 140','-Mesa de trabajo "Modular". *Estructura metálica cuadrada 1"x1". *Cubierta en melamina 25mm. *Tapas metálicas laterales removibles. *Opción canaleta porta cables. Medidas: 140x70x75cm.', 18, 'https://www.zamofi.com/wp-content/uploads/2018/05/MM140-2-300x169.png', 1, 0),
('MM 120','-Mesa de trabajo "Modular". *Estructura metálica cuadrada 1"x1". *Cubierta en melamina 25mm. *Tapas metálicas laterales removibles. *Opción canaleta porta cables. Medidas: 120x60x75cm.', 18, 'https://www.zamofi.com/wp-content/uploads/2018/05/MM120-1-300x169.png', 1, 0),
('MMT','-Mesa "Modular T". *Estructura metálica cuadrada 1"x1". *Cubierta en melamina 25mm. *Opción de canaleta porta cables. Medidas: 120x60x75cm.', 18, 'https://www.zamofi.com/wp-content/uploads/2018/05/MMT-1-300x169.png', 1, 0),
('MMT 120','-Mesa "Modular T" 120. *Estructura metálica cuadrada 1"x1". *Cubierta en melamina 25mm. *Opción de canaleta porta cables. Medidas: 120x60x75cm.', 18, 'https://www.zamofi.com/wp-content/uploads/2018/05/MMT120-1-300x169.png', 1, 0),
('MLT 1260','-Mesa "Lite" 1260. *Estructura metálica cuadrada 1"x1". *Cubierta en melamina 25mm. *Tapas metálicas inferiores. *Opción canaleta porta cables. Medidas: 120x60x75cm.', 18, 'https://www.zamofi.com/wp-content/uploads/2018/06/MLT-1260-300x169.png', 1, 0),
('MLT 1880','-Mesa "Lite" 1880. *Estructura metálica cuadrada 1"x1". *Cubierta en melamina 25mm. *Tapas metálicas inferiores. *Opción canaleta porta cables. Medidas: 180x80x75cm.', 18, 'https://www.zamofi.com/wp-content/uploads/2018/06/MLT-1880-300x169.png', 1, 0),
('MCS 16','-Mesa "Cross Slim". *Cubierta flotada en melamina 25mm. *Estructura metálica cuadrada 1"x1". *Niveladores plásticos. *Opción canaleta porta cables. Medidas: 160x70x75cm.', 18, 'https://www.zamofi.com/wp-content/uploads/2018/05/MCS-16-2.03-300x169.png', 1, 0);

-- LIBREROS (id_categoria: 17)
INSERT INTO producto (nombre, descripcion, id_categoria, imagen, stock, Destacado) VALUES
('LA 120','-Librero a piso abierto. 3 claros organizadores tamaño carta. Costados en 16mm. y entrepaños de 28mm. Medidas: 80x37x120cm.', 17, 'https://www.zamofi.com/wp-content/uploads/2017/06/LA-120-300x169.png', 1, 0),
('LG 120','-Gabinete con puertas completas. 3 claros organizadores tamaño carta. Costados en 16mm y entrepaños de 28mm. Incluye cerradura. Medidas: 80x37x120cm.', 17, 'https://www.zamofi.com/wp-content/uploads/2017/06/LG-120-300x169.png', 1, 0),
('LA 16','-Librero a piso abierto. 5 claros organizadores tamaño carta. Fabricado en 16mm. Sistema armado reforzado. Medidas: 80x35x180cm.', 17, 'https://www.zamofi.com/wp-content/uploads/2017/06/LA-16-300x169.png', 1, 0),
('LA 2816','-Librero a piso abierto. 5 claros organizadores tamaño carta. Costados en 16mm y entrepaños en 28mm. Sistema armado reforzado. Medidas: 80x35x180cm.', 17, 'https://www.zamofi.com/wp-content/uploads/2017/06/LA-2816-300x169.png', 1, 0),
('LA 28','-Librero a piso abierto. 5 claros organizadores tamaño carta. Fabricado en 28mm. Sistema armado reforzado. Medidas: 80x35x180cm.', 17, 'https://www.zamofi.com/wp-content/uploads/2017/06/LA-28-300x169.png', 1, 0),
('LA 289','-Librero a piso abierto. 5 claros organizadores tamaño carta. Fabricado en 28mm. Sistema armado reforzado. Medidas: 90x35x180cm.', 17, 'https://www.zamofi.com/wp-content/uploads/2017/06/LA-28-300x169.png', 1, 0),
('LP 16','-Librero a piso con puertas inferiores. 5 claros organizadores tamaño carta y 2 puertas inferiores abatibles. Fabricado en 16mm. Cerradura en puertas opcional. Medidas: 80x35x180cm.', 17, 'https://www.zamofi.com/wp-content/uploads/2017/06/LP-16-c.png', 1, 0),
('LP 2816','-Librero a piso con puertas inferiores. 5 claros organizadores tamaño carta y 2 puertas inferiores abatibles. Costados en 16mm y entrepaños en 28mm. Cerradura opcional. Medidas: 80x35x180cm.', 17, 'https://www.zamofi.com/wp-content/uploads/2017/06/LP-2816-c.png', 1, 0),
('LP 28','-Librero a piso con puertas inferiores. 5 claros organizadores tamaño carta y 2 puertas inferiores abatibles. Fabricado en 28mm. Cerradura opcional. Medidas: 80x35x180cm.', 17, 'https://www.zamofi.com/wp-content/uploads/2017/06/LP-28-c.png', 1, 0),
('LP 289','-Librero a piso con puertas inferiores. 5 claros organizadores tamaño carta y 2 puertas inferiores abatibles. Fabricado en 28mm. Cerradura opcional. Medidas: 90x35x180cm.', 17, 'https://www.zamofi.com/wp-content/uploads/2017/06/LP-28-c.png', 1, 0),
('GU 16','-Gabinete con puertas completas. 5 claros organizadores tamaño carta y 2 puertas completas abatibles. Fabricado en 16mm. Sistema armado reforzado. Incluye cerradura. Medidas: 80x37x180cm.', 17, 'https://www.zamofi.com/wp-content/uploads/2017/06/GU-16-c.png', 1, 0),
('GU 2816','-Gabinete con puertas completas. 5 claros organizadores tamaño carta y 2 puertas completas abatibles. Costados en 16mm y entrepaños en 28mm. Sistema armado reforzado. Incluye cerradura. Medidas: 80x37x180cm.', 16, 'https://www.zamofi.com/wp-content/uploads/2017/06/GU-2816-c.png', 1, 0),
('GU 28','-Gabinete con puertas completas. 5 claros organizadores tamaño carta y 2 puertas completas abatibles. Fabricado en 28mm. Sistema armado reforzado. Incluye cerradura. Medidas: 80x37x180cm.', 17, 'https://www.zamofi.com/wp-content/uploads/2017/06/GU-28-c.png', 1, 0),
('GU 289','-Gabinete con puertas completas. 5 claros organizadores tamaño carta y 2 puertas completas abatibles. Fabricado en 28mm. Sistema armado reforzado. Incluye cerradura. Medidas: 90x37x180cm.', 17, 'https://www.zamofi.com/wp-content/uploads/2017/06/GU-28-c.png', 1, 0),
('LX 120','-Librero modelo "Expo". Con divisiones, 2 puertas corredizas inferiores y 1 puerta abatible. Fabricado en 16mm. No incluye cerradura. Medidas: 120x30x200cm.', 17, 'https://www.zamofi.com/wp-content/uploads/2017/06/LX-120-c.png', 1, 0),
('LPT 16','-Librero modelo "Tiro". 5 claros organizadores tamaño carta y 2 puertas inferiores abatibles. Fabricado en 16mm. No incluye cerradura. Medidas: 120x35x180cm.', 17, 'https://www.zamofi.com/wp-content/uploads/2017/06/LPT-16-c.png', 1, 0),
('LPT 28','-Librero modelo "Tiro". 5 claros organizadores tamaño carta y 2 puertas inferiores abatibles. Fabricado en 28mm. No incluye cerradura. Medidas: 120x35x180cm.', 17, 'https://www.zamofi.com/wp-content/uploads/2017/06/LPT-28-c.png', 1, 0),
('LS 160','-Librero sobre credenza. 2 puertas superiores y hueco organizador. Costados en 28mm. resto en 16mm. No incluye cerradura. Medidas: 160x32x110cm.', 17, 'https://www.zamofi.com/wp-content/uploads/2017/06/LC-160.png', 1, 0),
('LS 180','-Librero sobre credenza. 2 puertas superiores. Costados en 28mm. resto en 16mm. No incluye cerradura. Medidas: 180x33x110cm.', 17, 'https://www.zamofi.com/wp-content/uploads/2017/06/LC-180.png', 1, 0),
('LSO 28','-Librero sobre credenza. 2 puertas superiores y 5 claros organizadores. Costados en 28mm. resto en 16mm. No incluye cerradura. Medidas: 180x33x110cm.', 17, 'https://www.zamofi.com/wp-content/uploads/2017/06/LCO-180.png', 1, 0);

-- ESCRITORIOS (id_categoria: 19)
INSERT INTO producto (nombre, descripcion, id_categoria, imagen, stock, Destacado) VALUES
('ES 120','Escritorio básico melamina 28 mm con faldón 16 mm.', 19, 'https://www.zamofi.com/wp-content/uploads/2017/06/ES-120-1-300x169.png', 1, 0),
('ES 140','Escritorio básico melamina 28 mm con faldón 16 mm.', 19, 'https://www.zamofi.com/wp-content/uploads/2017/06/ES-140-300x169.png', 1, 0),
('ES 182','Escritorio básico melamina 28 mm con faldón 16 mm.', 19, 'https://www.zamofi.com/wp-content/uploads/2017/06/ES-182-1-300x169.png', 1, 0),
('T 001','Escritorio tipo T con base metálica.', 19, 'https://www.zamofi.com/wp-content/uploads/2017/06/T-001-300x169.png', 1, 0),
('ES Grapa','Escritorio Grapa con estructura metálica.', 19, 'https://www.zamofi.com/wp-content/uploads/2017/06/ESCRITORIO-GRAPA-300x169.png', 1, 0),
('E Euro','Escritorio Euro con faldón frontal.', 19, 'https://www.zamofi.com/wp-content/uploads/2017/06/1-ESCRITORIO-EURO-300x203.jpg', 1, 0),
('E 003','Escritorio E-003 con canaleta.', 19, 'https://www.zamofi.com/wp-content/uploads/2017/06/E-003-300x169.png', 1, 0),
('ETG 1670','Escritorio gerencial ETG 1670.', 19, 'https://www.zamofi.com/wp-content/uploads/2017/06/ETG-1670-1-300x169.png', 1, 0),
('D 001','Escritorio Delta con estructura metálica.', 19, 'https://www.zamofi.com/wp-content/uploads/2017/06/D-001.1-300x169.png', 1, 0),
('P 001','Escritorio Prisma con canaleta.', 19, 'https://www.zamofi.com/wp-content/uploads/2017/06/P-001.1-300x169.png', 1, 0),
('EM 16','Escritorio modular EM 16.', 19, 'https://www.zamofi.com/wp-content/uploads/2017/06/EM-16.01-300x169.png', 1, 0),
('LT 1','Escritorio en L de 1 módulo.', 19, 'https://www.zamofi.com/wp-content/uploads/2017/06/LT-1-300x169.jpg', 1, 0),
('LT 2','Escritorio en L de 2 módulos.', 19, 'https://www.zamofi.com/wp-content/uploads/2017/06/LT-2-300x169.jpg', 1, 0),
('LT 3','Escritorio en L triple.', 19, 'https://www.zamofi.com/wp-content/uploads/2017/06/LT-3-300x169.jpg', 1, 0),
('LT 4','Escritorio en L cuádruple.', 19, 'https://www.zamofi.com/wp-content/uploads/2017/06/LT-4-300x169.jpg', 1, 0);

-- MESAS DE JUNTAS (id_categoria: 20)
INSERT INTO producto (nombre, descripcion, id_categoria, imagen, stock, Destacado) VALUES
('MJ 120','Mesa de juntas modelo MJ-120 con cubierta circular en melamina de 28 mm y base metálica de patas cruzadas. Ideal para espacios ejecutivos y de reuniones.', 19, 'https://www.zamofi.com/wp-content/uploads/2017/06/MJ-120..01-1.png', 1, 0),
('MJN 240','Mesa de juntas Nova MJN-240 con cubierta tipo bote y estructura en melamina 28 mm, patas rectas y faldón reforzado.', 20, 'https://www.zamofi.com/wp-content/uploads/2017/06/MJN-240.01.png', 1, 0),
('MJN 360','Mesa de juntas Nova MJN-360 con cubierta tipo bote dividida en dos piezas, ideal para juntas grandes.', 20, 'https://www.zamofi.com/wp-content/uploads/2017/06/MJN-360.01.png', 1, 0),
('MJE 120','Mesa de juntas Euro MJE-120 con cubierta circular de 120 cm de diámetro y patas metálicas tipo trineo.', 20, 'https://www.zamofi.com/wp-content/uploads/2017/06/MJE-120.01.png', 1, 0),
('MJE 240','Mesa de juntas Euro MJE-240 con cubierta rectangular en melamina 28 mm y base metálica trineo.', 20, 'https://www.zamofi.com/wp-content/uploads/2017/06/MJE-240.01.png', 1, 0),
('MJE 360','Mesa de juntas Euro MJE-360 con cubierta amplia de tres metros y estructura doble metálica.', 20, 'https://www.zamofi.com/wp-content/uploads/2017/06/MJE-360.01.png', 1, 0),
('MJT 120','Mesa de juntas Tempo MJT-120 circular con cubierta tipo tambor de 7 cm.', 20, 'https://www.zamofi.com/wp-content/uploads/2017/06/MJT-120.01.png', 1, 0),
('MJT 240','Mesa de juntas Tempo MJT-240 rectangular con cubierta tipo tambor y faldón bajo.', 20, 'https://www.zamofi.com/wp-content/uploads/2017/06/MJT-240.01.png', 1, 0),
('MJT 360','Mesa de juntas Tempo MJT-360 con cubierta rectangular dividida, base robusta y faldón lateral.', 20, 'https://www.zamofi.com/wp-content/uploads/2017/06/MJT-360.01.png', 1, 0),
('MJK 240','Mesa de juntas Kris MJK-240 con detalle central de cristal y cubierta en melamina 28 mm.', 20, 'https://www.zamofi.com/wp-content/uploads/2017/06/MJK-240.01.png', 1, 0),
('MJDT 320','Mesa de juntas Delta MJDT-320 con cubierta tipo tambor y cristal central. Diseño moderno para salas ejecutivas.', 20, 'https://www.zamofi.com/wp-content/uploads/2017/06/MJDT-320.01.png', 1, 0);

-- ISLAS DE TRABAJO (id_categoria: 21)
INSERT INTO producto (nombre, descripcion, id_categoria, imagen, stock, Destacado) VALUES
('MEM 12','Isla recta modelo MEM-12 para 2 usuarios, con cubierta en melamina de 28 mm, estructura metálica y mampara divisoria en melamina 16 mm.', 21, 'https://www.zamofi.com/wp-content/uploads/2018/07/ISLA-RECTA-12-M.-MELAMINA.png', 1, 0),
('MEC 12','Isla recta modelo MEC-12 para 2 usuarios, con cubierta en melamina 28 mm y mampara divisoria en cristal inastillable.', 21, 'https://www.zamofi.com/wp-content/uploads/2018/07/ISLA-RECTA-12-M.-CRISTAL.png', 1, 0),
('MEM 15','Isla recta modelo MEM-15 para 2 usuarios, versión amplia con cubierta de 150x120 cm en melamina 28 mm y mampara en melamina 16 mm.', 21, 'https://www.zamofi.com/wp-content/uploads/2018/07/ISLA-RECTA-M.-MELAMINA.png', 1, 0),
('MEC 15','Isla recta modelo MEC-15 para 2 usuarios, cubierta 150x120 cm en melamina 28 mm y mampara en cristal inastillable.', 21, 'https://www.zamofi.com/wp-content/uploads/2018/07/ISLA-RECTA-M.-CRISTAL.png', 1, 0),
('TEM','Triceta modelo TEM para 3 usuarios, con cubierta central circular en melamina 28 mm y mampara divisoria en melamina 16 mm.', 21, 'https://www.zamofi.com/wp-content/uploads/2018/07/TRICETA-M.-MELAMINA.png', 1, 0),
('TEC','Triceta modelo TEC para 3 usuarios, con mampara divisoria en cristal templado y cubierta en melamina 28 mm.', 21, 'https://www.zamofi.com/wp-content/uploads/2018/07/TRICETA-M.-CRISTAL.png', 1, 0),
('CRM','Cruzeta modelo CRM para 4 usuarios, cubierta cruzada en melamina 28 mm con mampara en melamina 16 mm.', 21, 'https://www.zamofi.com/wp-content/uploads/2018/07/CRUZETA-M.-MELAMINA.png', 1, 0),
('CRC','Cruzeta modelo CRC para 4 usuarios, cubierta cruzada en melamina 28 mm con mampara en cristal inastillable.', 21, 'https://www.zamofi.com/wp-content/uploads/2018/07/CRUZETA-M.-CRISTAL.png', 1, 0);

-- RECEPCIÓN (id_categoria: 22)
INSERT INTO producto (nombre, descripcion, id_categoria, imagen, stock, Destacado) VALUES
('RQ 180','Recepción modelo Q-dra 180, mostrador con curvas y cubierta melamina 28 mm.', 22, 'https://www.zamofi.com/wp-content/uploads/2017/06/RQ-180.01.png', 1, 0),
('RM 180','Recepción modelo Marvic 180, diseño recto con faldón frontal en melamina.', 22, 'https://www.zamofi.com/wp-content/uploads/2017/06/RM-180.01.png', 1, 0),
('RR 200','Recepción modelo Sigma 200, mostrador amplio con cubierta melamina 28 mm y diseño moderno.', 22, 'https://www.zamofi.com/wp-content/uploads/2017/06/RR-200.01.png', 1, 0),
('RNR 180','Recepción modelo Nova RNR 180, estilo contemporáneo con lineas rectas y cubierta melamina.', 22, 'https://www.zamofi.com/wp-content/uploads/2017/06/RNR-180.01.png', 1, 0);

-- LÍNEA ANZIO (id_categoria: 14 - Subcategoría de Línea Italia)
INSERT INTO producto (nombre, descripcion, id_categoria, imagen, stock, Destacado) VALUES
('Anzio Operativos','Estaciones de trabajo Anzio Operativos con diseño modular, alta flexibilidad y opciones de cableado integrado. Fabricadas en melamina con estructura metálica reforzada.', 14, 'https://lineaitalia.com.mx/wp-content/uploads/2025/04/Anzio_Operativos_3.webp', 1, 1),
('Anzio Directivos','Escritorios ejecutivos Anzio Directivos con diseño elegante, superficie amplia en melamina y detalles en aluminio anodizado. Perfectos para oficinas de alta dirección.', 14, 'https://lineaitalia.com.mx/wp-content/uploads/2025/04/Anzio_Directivos_1.webp', 1, 1),
('Anzio Conferencias','Mesas de juntas Anzio Conferencias, funcionales y modernas, con sistema de electrificación y acabados premium en melamina.', 14, 'https://lineaitalia.com.mx/wp-content/uploads/2025/04/Juntas_Anzio_1-scaled.jpg', 1, 1),
('Anzio Almacenamiento','Módulos de almacenamiento Anzio Storage con puertas abatibles y compartimientos modulares. Fabricados en melamina de alta resistencia.', 14, 'https://lineaitalia.com.mx/wp-content/uploads/2025/04/Almacenamiento_Anzio_1-scaled.jpg', 1, 1),
('Anzio Recepción','Recepciones Anzio con diseño contemporáneo, estructura sólida y acabados melamínicos, ideales para áreas de atención y bienvenida.', 14, 'https://lineaitalia.com.mx/wp-content/uploads/2025/04/Anzio_Recepcion_1.webp', 1, 1);

-- LÍNEA IWORK & PRIVATT (id_categoria: 15 - Subcategoría de Línea Italia)
INSERT INTO producto (nombre, descripcion, id_categoria, imagen, stock, Destacado) VALUES
('Privatt','Sistema de estaciones de trabajo Privatt para Call Center, diseño cerrado que ofrece privacidad acústica y funcional. Modular y adaptable con opciones de cableado integrado.', 15, 'https://lineaitalia.com.mx/wp-content/uploads/2025/04/Cerradas_Privatt-scaled.jpg', 1, 1),
('I-Work','Estaciones I-Work abiertas y colaborativas para Call Center, con paneles divisorios bajos, estructura metálica y sistema de electrificación modular.', 15, 'https://lineaitalia.com.mx/wp-content/uploads/2025/04/Cerradas_Iwork-scaled.jpg', 1, 1);

-- LÍNEA ITALIA (id_categoria: 16 - Subcategoría de Línea Italia)
INSERT INTO producto (nombre, descripcion, id_categoria, imagen, stock, Destacado) VALUES
('Escritorios Operativos Italia','Línea Italia Operativos: estaciones de trabajo modulares, diseño funcional y resistente para oficinas dinámicas.', 16, 'https://lineaitalia.com.mx/wp-content/uploads/2025/04/Operativo_Italia_1-scaled.jpg', 1, 1),
('Escritorios Ejecutivos Italia','Línea Italia Ejecutivos: estilo elegante con estructura reforzada, ideal para oficinas de dirección y espacios premium.', 16, 'https://lineaitalia.com.mx/wp-content/uploads/2025/04/Ejecutivos_Italia_1-scaled.jpg', 1, 1),
('Escritorios Directivos Italia','Línea Italia Directivos: escritorios amplios y sofisticados con acabados de lujo y cubierta de alta densidad.', 16, 'https://lineaitalia.com.mx/wp-content/uploads/2025/04/Directivo_Italia_1-scaled.jpg', 1, 1),
('Mesas de Juntas Italia','Mesas de juntas Italia: superficies amplias, estructura metálica y diseño moderno para salas de reunión ejecutiva.', 16, 'https://lineaitalia.com.mx/wp-content/uploads/2025/04/Juntas_Italia_1-scaled.jpg', 1, 1),
('Almacenamiento Italia','Módulos de almacenamiento Italia: credenzas, archiveros y lockers con acabados resistentes y elegantes.', 16, 'https://lineaitalia.com.mx/wp-content/uploads/2025/04/Almacenamiento_1-scaled.jpg', 1, 1),
('Recepciones Italia','Recepciones Italia: mostradores modernos y funcionales con acabados contemporáneos y diseño corporativo.', 16, 'https://lineaitalia.com.mx/wp-content/uploads/2025/04/Recepcion_1-scaled.jpg', 1, 1);

-- SALAS / SOFÁS (id_categoria: 5 - Subcategoría de Sillería)
INSERT INTO producto (nombre, descripcion, id_categoria, imagen, stock, Destacado) VALUES
('Ibiza','Sillón curvo tapizado, líneas suaves para lounge.', 5, 'https://grupoalbar.com/wp-content/uploads/2024/06/sillon-ibiza-34-300x300.jpg', 1, 0),
('Larisa','Módulo esquinero compacto, estética moderna.', 5, 'https://grupoalbar.com/wp-content/uploads/2024/06/larisa-34-300x300.jpg', 1, 0),
('Nantes','Sofá 2 plazas con base metálica expuesta.', 5, 'https://grupoalbar.com/wp-content/uploads/2024/06/nantes-34-300x300.jpg', 1, 0),
('Roma','Sillón cúbico de brazos altos, look elegante.', 5, 'https://grupoalbar.com/wp-content/uploads/2024/02/roma-1p-34-300x300.jpg', 1, 0),
('Ottawa','Set de puffs redondos, varios diámetros y alturas.', 5, 'https://grupoalbar.com/wp-content/uploads/2024/02/ottawa-300x300.jpg', 1, 0),
('Kassel','Sofá modular bajo, estilo contemporáneo.', 5, 'https://grupoalbar.com/wp-content/uploads/2024/02/kassel-34-300x300.jpg', 1, 0),
('Seul','Sillón con cojín suelto y patas delgadas.', 5, 'https://grupoalbar.com/wp-content/uploads/2024/02/seul-1p-34-300x300.jpg', 1, 0),
('Oslo','Butaca ligera con brazos abiertos y estructura metálica.', 5, 'https://grupoalbar.com/wp-content/uploads/2024/03/oslo-34-300x300.jpg', 1, 0),
('Argos','Sillón de asiento profundo con base metálica.', 5, 'https://grupoalbar.com/wp-content/uploads/2024/03/argos-1p-34-300x300.jpg', 1, 0),
('Atenas','Butaca con brazos rectos y patas altas.', 5, 'https://grupoalbar.com/wp-content/uploads/2024/03/atenas-1p-34-300x300.jpg', 1, 0),
('Marruecos','Sillón con brazos de madera, estilo cálido.', 5, 'https://grupoalbar.com/wp-content/uploads/2024/03/marruecos-1p-34-300x300.jpg', 1, 0),
('Asturias','Sillón compacto de líneas rectas y estable.', 5, 'https://grupoalbar.com/wp-content/uploads/2024/03/asturias-cb-34-300x300.jpg', 1, 0),
('Lyon','Sillón con patas cónicas de madera, look nórdico.', 5, 'https://grupoalbar.com/wp-content/uploads/2024/03/lyon-1p-34-300x300.jpg', 1, 0),
('Berlin','Sillón con armazón metálico visible, estilo industrial.', 5, 'https://grupoalbar.com/wp-content/uploads/2024/03/berlin-1p-34-300x300.jpg', 1, 0),
('Copenhaguen','Sillón bajo y ancho, líneas minimalistas.', 5, 'https://grupoalbar.com/wp-content/uploads/2024/03/copenhaguen-1p-34-300x300.jpg', 1, 0),
('Arezzo','Sillón con doble aro perimetral tapizado suave.', 5, 'https://grupoalbar.com/wp-content/uploads/2024/03/arezzo-1p-34-1-300x300.jpg', 1, 0),
('Monaco','Sillón ejecutivo acolchado de respaldo envolvente.', 5, 'https://grupoalbar.com/wp-content/uploads/2024/04/monaco-1p-34-300x300.jpg', 1, 0),
('Lutecia','Sillón de brazos gruesos y gran confort.', 5, 'https://grupoalbar.com/wp-content/uploads/2024/04/lutecia-1p-34-300x300.jpg', 1, 0),
('Ankara','Módulo esquinero tapizado, base discreta.', 5, 'https://grupoalbar.com/wp-content/uploads/2024/04/ankara-1p-34-300x300.jpg', 1, 0),
('Parma','Sillón con costuras visibles y estilo clásico.', 5, 'https://grupoalbar.com/wp-content/uploads/2024/04/parma-1p-34-300x300.jpg', 1, 0),
('Dresden','Butaca compacta con patas delgadas y respaldo firme.', 5, 'https://grupoalbar.com/wp-content/uploads/2024/04/dresden-1p-34-300x300.jpg', 1, 0),
('Milan','Sillón monoplaza de líneas rectas y asiento alto.', 5, 'https://grupoalbar.com/wp-content/uploads/2024/04/milan-1p-34-300x300.jpg', 1, 0),
('Amsterdam','Butaca tapizada de respaldo medio, look casual.', 5, 'https://grupoalbar.com/wp-content/uploads/2024/04/amsterdam-1p-34-300x300.jpg', 1, 0),
('Florencia','Silla lounge giratoria de respaldo envolvente.', 5, 'https://grupoalbar.com/wp-content/uploads/2024/04/florencia-300x300.jpg', 1, 0),
('Sofia','Silla lounge con patas de madera y asiento amplio.', 5, 'https://grupoalbar.com/wp-content/uploads/2024/04/sofia-1p-34-300x300.jpg', 1, 0),
('Granada & Puff','Butaca curva con puff a juego, set decorativo.', 5, 'https://grupoalbar.com/wp-content/uploads/2024/04/granada-plus-300x300.jpg', 1, 0),
('Chaselonge','Chaise longue tapizada para descanso y lectura.', 5, 'https://grupoalbar.com/wp-content/uploads/2024/04/chaselon-md-300x300.jpg', 1, 0),
('Lounge','Módulo lounge tapizado, aspecto limpio y versátil.', 5, 'https://grupoalbar.com/wp-content/uploads/2024/04/lounge-1p-300x300.jpg', 1, 0);

-- SILLAS EJECUTIVAS (id_categoria: 4 - Subcategoría de Sillería)
INSERT INTO producto (nombre, descripcion, id_categoria, imagen, stock, Destacado) VALUES
('OHE-805','Silla ejecutiva de respaldo alto tapizada en piel sintética, con mecanismo reclinable y base cromada.', 4, 'https://www.offiho.com/ejecutivos/galeria/OHE-805.jpg', 1, 0),
('OHE-35','Silla ejecutiva de diseño clásico, respaldo medio y brazos fijos tapizados. Ideal para oficina o home office.', 4, 'https://www.offiho.com/ejecutivos/galeria/OHE-35.jpg', 1, 0),
('OHE-605','Silla ejecutiva color negro con soporte lumbar, respaldo medio y ruedas silenciosas.', 4, 'https://www.offiho.com/ejecutivos/galeria/OHE-605negro.jpg', 1, 0),
('OHE-113','Silla ejecutiva en tono gris con estructura ergonómica, ajuste de altura y brazos fijos modernos.', 4, 'https://www.offiho.com/ejecutivos/galeria/OHE-113gris.jpg', 1, 0);

-- SILLAS OPERATIVAS (id_categoria: 3 - Subcategoría de Sillería)
INSERT INTO producto (nombre, descripcion, id_categoria, imagen, stock, Destacado) VALUES
('OHE-99','Silla operativa con respaldo de malla y soporte lumbar; ajuste de altura y base de 5 puntas.', 3, 'https://www.offiho.com/operativos/galeria/OHE-99.jpg', 1, 0),
('OHE-111','Silla operativa negra con respaldo medio, asiento acolchado y brazos fijos.', 3, 'https://www.offiho.com/operativos/galeria/OHE-111negro.jpg', 1, 0),
('OHE-65','Silla operativa negra, respaldo tapizado y mecanismo basculante.', 3, 'https://www.offiho.com/operativos/galeria/OHE-65negro.jpg', 1, 0),
('OHE-84','Silla operativa gris con respaldo en malla transpirable y ruedas silenciosas.', 3, 'https://www.offiho.com/operativos/galeria/OHE-84gris.jpg', 1, 0),
('OHE-98','Silla operativa gris con diseño ergonómico, ajuste de altura y brazos fijos.', 3, 'https://www.offiho.com/operativos/galeria/OHE-98gris.jpg', 1, 0),
('OHE-175','Silla operativa con respaldo alto, soporte lumbar y asiento de alta densidad.', 3, 'https://www.offiho.com/operativos/galeria/OHE-175.jpg', 1, 0),
('OHE-55','Silla operativa negra con base cromada, respaldo medio y mecanismo basculante.', 3, 'https://www.offiho.com/operativos/galeria/OHE-55negrocr.jpg', 1, 0),
('OHE-94 Plus','Silla operativa con respaldo en malla y soporte lumbar, versión Plus con mayor acolchado.', 3, 'https://www.offiho.com/operativos/galeria/OHE-94plus.jpg', 1, 0);

-- BANCAS DE ESPERA (id_categoria: 7 - Subcategoría de Sillería)
INSERT INTO producto (nombre, descripcion, id_categoria, imagen, stock, Destacado) VALUES
('OHR-310-3P','Banca de espera de 3 plazas modelo Kyos OHR-310, estructura metálica resistente y asiento acolchado.', 7, 'https://www.offiho.com/bancas/galeria/OHR-310-3P.jpg', 1, 0),
('OHR-2700-3P','Banca Innova OHR-2700 de 3 plazas, diseño moderno con respaldo curvo y tapizado de alta resistencia.', 7, 'https://www.offiho.com/bancas/galeria/OHR-2700-3P.jpg', 1, 0),
('OHR-2200-3P','Banca tipo Ellítico OHR-2200 de 3 plazas, asiento continuo con soporte estructural robusto.', 7, 'https://www.offiho.com/bancas/galeria/OHR-2200-3P.jpg', 1, 0),
('OHR-2400-3P','Banca tipo Ellítico Net OHR-2400 de 3 plazas, diseño ventilado y acabado metálico cromado.', 7, 'https://www.offiho.com/bancas/galeria/OHR-2400-3P.jpg', 1, 0),
('OHR-2800-3P','Banca OHR-2800 de 3 plazas con base cromada cruzada, tapicería premium y respaldo envolvente.', 7, 'https://www.offiho.com/bancas/galeria/OHR-2800-3Pcr.jpg', 1, 0);

-- MOBILIARIO ESCOLAR (id_categoria: 8 - Subcategoría de Sillería)
INSERT INTO producto (nombre, descripcion, id_categoria, imagen, stock, Destacado) VALUES
('OHP-2100','Silla escolar OHP-2100 con asiento y respaldo de polipropileno reforzado, ideal para aulas activas.', 8, 'https://www.offiho.com/escolar/galeria/OHP-2100.jpg', 1, 0),
('OHP-325CR','Silla escolar OHP-325CR con estructura cromada y asiento moldeado para uso prolongado en escuelas.', 8, 'https://www.offiho.com/escolar/galeria/OHP-325cr.jpg', 1, 0),
('OHP-86CR','Silla tipo estudio OHP-86CR con diseño ergonómico y patas cromadas resistentes.', 8, 'https://www.offiho.com/escolar/galeria/OHP-86cr.jpg', 1, 0),
('OHP-102','Silla escolar OHP-102 con respaldo perforado para ventilación y asiento anatómico.', 8, 'https://www.offiho.com/escolar/galeria/OHP-102.jpg', 1, 0),
('OHP-2320','Silla OHP-2320 de asiento amplio y estructura reforzada para uso escolar rudo.', 8, 'https://www.offiho.com/escolar/galeria/OHP-2320.jpg', 1, 0),
('OHP-2300','Silla escolar OHP-2300 con respaldo en malla y estructura metálica durable.', 8, 'https://www.offiho.com/escolar/galeria/OHP-2300.jpg', 1, 0),
('OHP-2307','Silla escolar OHP-2307 con asiento tapizado y respaldo ergonómico para confort prolongado.', 8, 'https://www.offiho.com/escolar/galeria/OHP-2307.jpg', 1, 0);

-- =====================================================
-- TABLA: cotizaciones
-- =====================================================
CREATE TABLE cotizaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto VARCHAR(255) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    empresa VARCHAR(100),
    email VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    cantidad INT NOT NULL DEFAULT 1,
    mensaje TEXT,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- VERIFICACIÓN FINAL DE CATEGORÍAS
-- =====================================================

-- Mostrar todas las categorías principales y sus subcategorías
SELECT 
    p.nombre as 'Categoría Principal',
    p.id as 'ID Principal',
    COUNT(c.id) as 'Cantidad Subcategorías',
    GROUP_CONCAT(c.nombre SEPARATOR ', ') as 'Subcategorías'
FROM categoria p
LEFT JOIN categoria c ON p.id = c.parent_id
WHERE p.parent_id IS NULL
GROUP BY p.id, p.nombre
ORDER BY p.nombre;

-- =====================================================
-- FIN DEL SCRIPT
-- =====================================================
