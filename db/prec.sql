USE ofiequipo2;

-- Inserts adaptados para la estructura de Ofi_com.sql
-- Requiere haber ejecutado antes Ofi_com.sql

SET FOREIGN_KEY_CHECKS = 0;

-- 4️⃣ Insertar categorías principales
INSERT IGNORE INTO categoria (id, nombre, parent_id) VALUES
(1, 'Sillería', NULL),
(9, 'Almacenaje', NULL),
(13, 'Línea Italia', NULL),
(17, 'Libreros', NULL),
(18, 'Mesas', NULL),
(19, 'Escritorios', NULL),
(20, 'Mesas de Juntas', NULL),
(21, 'Islas de Trabajo', NULL),
(22, 'Recepción', NULL),
(28,'Metálico',NULL),
(39, 'Líneas', NULL);

-- 5️⃣ Subcategorías de Sillería
INSERT IGNORE INTO categoria (id, nombre, parent_id) VALUES
(2, 'Visita', 1),
(3, 'Operativa', 1),
(4, 'Ejecutiva', 1),
(5, 'Sofás', 1),
-- (6, 'Visitantes', 1), -- ELIMINADA: Los productos de Visitantes ahora están en Visita (id: 2)
(7, 'Bancas de espera', 1),
(8, 'Escolar', 1),
(27,'Linea Eco',1);

-- 6️⃣ Subcategorías de Almacenaje
INSERT IGNORE INTO categoria (id, nombre, parent_id) VALUES
(10, 'Archiveros', 9),
(11, 'Gabinetes', 9),
(12, 'Credenzas', 9);

-- Subcategorías de Metálico
INSERT IGNORE INTO categoria (id, nombre, parent_id) VALUES
(29, 'Archiveros', 28),
(30, 'Anaqueles', 28),
(31, 'Escritorios', 28),
(32, 'Gabinetes', 28),
(33, 'Góndolas', 28),
(34, 'Lockers', 28),
(35, 'Restauranteras', 28),
(36, 'Mesas', 28),
(37, 'Escolar', 28),
(38, 'Línea Económica', 28);

-- 7️⃣ Subcategorías de Línea Italia
INSERT IGNORE INTO categoria (id, nombre, parent_id) VALUES
(14, 'Anzio', 13),
(15, 'iwork & privatt', 13),
(16, 'Italia Solución general', 13);

-- 8️⃣ Subcategorías de Escritorios
INSERT IGNORE INTO categoria (id, nombre, parent_id) VALUES
(23, 'Básicos', 19),
(24, 'Operativos en L', 19),
(26, 'Ejecutivos', 19);

-- 10️⃣ Subcategorías de Líneas
INSERT IGNORE INTO categoria (id, nombre, parent_id) VALUES
(40, 'Euro', 39),
(41, 'Delta', 39),
(42, 'Tempo', 39),
(43, 'Línea Alva', 39),
(44, 'Línea Beta', 39),
(45, 'Línea Ceres', 39),
(46, 'Línea Fiore', 39),
(47, 'Línea Worvik', 39),
(48, 'Línea Yenko', 39);

-- 9️⃣ Encender nuevamente las verificaciones de integridad
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- INSERTAR PRODUCTOS CON CATEGORÍAS CORRECTAS
-- =====================================================

-- ARCHIVEROS (categoria_id: 10 - Subcategoría de Almacenaje)
INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES
('AP 2','-Archivero Vertical de 2 gavetas, gavetas tamaño oficio con correderas de extensión.*Cubierta en 28mm. y costados en 16mm.*Laterales no visibles (Frentes de los cajones cubren costados).*Porta folder para papelera colgante.*Cerradura múltiple.Medidas:47x60x70cm.', 6502.50, 10, 'https://www.zamofi.com/wp-content/uploads/2017/06/AP-2.01.png', 1, 0, 1),
('AP 3','-Archivero Vertical de 3 gavetas, gavetas tamaño oficio con correderas de extensión.*Cubierta en 28mm. y costados en 16mm.*Laterales no visibles (Frentes de los cajones cubren costados).*Porta folder para papelera colgante.*Cerradura múltiple.Medidas:47x60x99cm.', 8737.20, 10, 'https://www.zamofi.com/wp-content/uploads/2017/06/AP-3.01.png', 1, 0, 1),
('AP 4','-Archivero Vertical de 4 gavetas, gavetas tamaño oficio con correderas de extensión.*Cubierta en 28mm. y costados en 16mm.*Laterales no visibles (Frentes de los cajones cubren costados).*Porta folder para papelera colgante.*Cerradura múltiple.Medidas:47x60x128cm.', 10057.50, 10, 'https://www.zamofi.com/wp-content/uploads/2017/06/AP-4.01.png', 1, 0, 1),
('APR 2','-Archivero Vertical reforzado de 2 gavetas, gavetas tamaño oficio con correderas de extensión.*Cubierta y costados en 28mm.*Laterales no visibles (Frentes de los cajones cubren costados).*Porta folder para papelera colgante.*Incluye cerradura múltiple.Medidas:50x60x70cm.', 7273.80, 10, 'https://www.zamofi.com/wp-content/uploads/2017/06/APR-2.01.png', 1, 0, 1),
('APR 3','-Archivero Vertical reforzado de 3 gavetas, gavetas tamaño oficio con correderas de extensión.*Cubierta y costados en 28mm.*Laterales no visibles (Frentes de los cajones cubren costados).*Porta folder para papelera colgante.*Incluye cerradura múltiple.Medidas:50x60x99cm.', 9834.30, 10, 'https://www.zamofi.com/wp-content/uploads/2017/06/APR-3.01.png', 1, 0, 1),
('APR 4','-Archivero Vertical reforzado de 4 gavetas, gavetas tamaño oficio con correderas de extensión.*Cubierta y costados en 28mm.*Laterales no visibles (Frentes de los cajones cubren costados).*Porta folder para papelera colgante.*Incluye cerradura múltiple.Medidas:50x60x128cm.', 11154.60, 10, 'https://www.zamofi.com/wp-content/uploads/2017/06/APR-4.01.png', 1, 0, 1),
('AHP 2','-Archivero Horizontal de 2 gavetas, gavetas tamaño oficio con correderas de extensión.*Cubierta y costados en 28mm.*Laterales no visibles (Frentes de los cajones cubren costados).*Doble corredera de extensión.*Cerradura múltiple.Medidas:80x50x70cm.', 9143.10, 10, 'https://www.zamofi.com/wp-content/uploads/2017/06/AHP-2.01.png', 1, 0, 1),
('AHP 3','-Archivero Horizontal de 3 gavetas, gavetas tamaño oficio con correderas de extensión.*Cubierta y costados en 28mm.*Laterales no visibles (Frentes de los cajones cubren costados).*Doble corredera de extensión.*Cerradura múltiple.Medidas:80x50x99cm.', 11784.60, 10, 'https://www.zamofi.com/wp-content/uploads/2017/06/AHP-3.01.png', 1, 0, 1),
('AHP 4','-Archivero Horizontal de 4 gavetas, gavetas tamaño oficio con correderas de extensión.*Cubierta y costados en 28mm.*Laterales no visibles (Frentes de los cajones cubren costados).*Doble corredera de extensión.*Cerradura múltiple.Medidas:80x50x128cm.', 13816.80, 10, 'https://www.zamofi.com/wp-content/uploads/2017/06/AHP-4.01.png', 1, 0, 1),
('AR 2','-Archivero vertical móvil 1+1 cajón papelero y cajón de archivo carta/oficio, corredera de extensión.*Fabricado en melamina de 16mm.*Cerradura múltiple.*4 Rodajas (2 rodajas frontales con freno).Medidas:40x50x60cm.', 4470.30, 10, 'https://www.zamofi.com/wp-content/uploads/2017/06/AR-2.01.png', 1, 0, 1),
('AC 3 / AO 3','-Archivero vertical 2+1 de 3 gavetas, 2 cajones papeleros y 1 cajón de archivo, corredera de extensión.*Fabricado en melamina de 16mm.*Cerradura múltiple.*Porta folder para carpeta colgante.Medidas:AC 3:40x50x71cm.Tamaño carta/oficio AO 3:47x50x71cm.Tamaño oficio.', 5588.10, 10, 'https://www.zamofi.com/wp-content/uploads/2017/06/AC-3.01.png', 1, 0, 1),
('C 2+1','-Archivero pedestal 2+1, 2 cajones papeleros y 1 cajón de archivo, corredera de extensión.*Fabricado en melamina de 16mm.*Cerradura múltiple.*SIN cubierta.Medidas:40x50x72cm.', 5283.00, 10, 'https://www.zamofi.com/wp-content/uploads/2017/06/C-21.01.png', 1, 0, 1),
('C 1+1+1','-Archivero pedestal 1+1+1, 1 claro organizador, 1 cajón papelero y 1 cajón de archivo tamaño oficio, corredera de extensión.*Fabricado en melamina de 16mm.*Cerradura múltiple.*SIN cubierta.Medidas:40x50x72cm.', 4572.00, 10, 'https://www.zamofi.com/wp-content/uploads/2017/06/C-111.01.png', 1, 0, 1);

-- GABINETES (categoria_id: 11 - Subcategoría de Almacenaje)
INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES
('GC 80','-Gabinete colgante con puerta, sistema de pistones.*Cerradura.*Sistema de instalación oculta sin tornillos.Medidas:80×35×40cm.', 3536.10, 11, 'https://www.zamofi.com/wp-content/uploads/2017/06/GC-80-300x169.jpg', 1, 0, 1),
('GC 100','-Gabinete colgante con puerta, sistema de pistones.*Cerradura.*Sistema de instalación oculta sin tornillos.Medidas:100×35×40cm.', 4165.20, 11, 'https://www.zamofi.com/wp-content/uploads/2017/06/GC-80-300x169.jpg', 1, 0, 1),
('C 1+1','-Cajonera suspendida 1+1, cajón papelero y cajón de archivo carta/oficio, corredera de extensión.*Fabricado en melamina de 16mm.*Cerradura múltiple.*SIN cubierta.Medidas:40x50x40cm.', 3231.00, 11, 'https://www.zamofi.com/wp-content/uploads/2017/06/C-11.01.png', 1, 0, 1);

-- CREDENZAS (categoria_id: 12 - Subcategoría de Almacenaje)
INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES
('CSA 16','-Credenza "Slim III". 2 puertas abatible con entrepaño interno, 1 claro organizador y 1 cajón papelero al centro, estructura metálica tubular cuadrado 1″x1″.*Jaladera 45° integrada en puertas y cajón.*Laterales no visibles (Frentes de puertas y cajón cubren costados).*Cerradura en puertas opcional.Medidas:160x40x75cm.', 10992.60, 12, 'https://www.zamofi.com/wp-content/uploads/2020/05/CZS-16-2.01.png', 1, 0, 1),
('CSP 28','-Credenza "Slim IV". 4 puertas abatible con entrepaño interno, estructura metálica tubular cuadrado 1″x1″.*Jaladera 45° integrada en puertas.*Laterales visibles (Frentes de puertas NO cubren costados).*Cerradura en puertas opcional.Medidas:160x40x75cm.', 11196.00, 12, 'https://www.zamofi.com/wp-content/uploads/2020/05/CZS-28-1.01.png', 1, 0, 1),
('CSC 16','-Credenza "Slim V". 4 puertas abatible, 2 cajones papeleros, estructura metálica tubular cuadrado 1″x1″.*Jaladera 45° integrada en puertas y cajones.*Laterales no visibles (Frentes de puertas cubren costados).*Cerradura en puertas opcional.Medidas:160x40x75cm.', 12577.50, 12, 'https://www.zamofi.com/wp-content/uploads/2020/05/CZS-16-1.01.png', 1, 0, 1),
('CSC 28','-Credenza "Slim VI". 4 puertas abatible, 2 cajones papeleros, estructura metálica tubular cuadrado 1″x1″.*Jaladera 45° integrada en puertas y cajones.*Laterales visibles (Frentes de puertas y cajones NO cubren costados).*Cerradura en puertas opcional.Medidas:160x40x75cm.', 13633.20, 12, 'https://www.zamofi.com/wp-content/uploads/2020/05/CZS-28.01.png', 1, 0, 1),
('CSP 16','-Credenza "Slim 16". 4 puertas abatible con entrepaño interno, estructura metálica tubular cuadrado 1″x1″.*Jaladera 45° integrada en puertas.*Laterales no visibles (Frentes de puertas cubren costados).*Cerradura en puertas opcional.Medidas:160x40x75cm.', 10139.40, 12, 'https://www.zamofi.com/wp-content/uploads/2020/05/CZS-16.01.png', 1, 0, 1),
('CDD','-Credenza Modelo "Delta". 4 puertas abatibles con entrepaño interno.*Jaladera 45° integrada en puertas.*Laterales no visibles (Frentes de puertas cubren costados).*Cerradura en puertas opcional.Medidas:160x40x75cm.', 36775.80, 12, 'https://www.zamofi.com/wp-content/uploads/2017/06/delta-001.png', 1, 0, 1),
('CP 184','-Credenza Ejecutiva. 4 puertas abatibles con entrepaño interno.*Laterales no visibles (Frentes de las puertas cubren costados).*Cerradura opcional.Medidas:180x40x75cm.', 10971.90, 12, 'https://www.zamofi.com/wp-content/uploads/2017/06/CP-185.1.png', 1, 0, 1),
('CP 185','-Credenza Ejecutiva. 4 puertas abatibles con entrepaño interno.*Laterales no visibles (Frentes de las puertas cubren costados).*Cerradura opcional.Medidas:180x50x75cm.', 12393.90, 12, 'https://www.zamofi.com/wp-content/uploads/2017/06/CP-185.2.png', 1, 0, 1),
('CP 164','-Credenza Operativa. 3 puertas abatibles con entrepaño interno.*Laterales no visibles (Frentes de las puertas cubren costados).*Cerradura opcional.Medidas:160x40x75cm.', 9346.50, 12, 'https://www.zamofi.com/wp-content/uploads/2017/06/CP-165.1.png', 1, 0, 1),
('CP 165','-Credenza Operativa. 3 puertas abatibles con entrepaño interno.*Laterales no visibles (Frentes de las puertas cubren costados).*Cerradura opcional.Medidas:160x50x75cm.', 10728.00, 12, 'https://www.zamofi.com/wp-content/uploads/2017/06/CP-165.2.png', 1, 0, 1),
('CP 84','-Credenza Multiusos. 2 puertas abatibles con entrepaño interno.*Laterales no visibles (Frentes de puertas cubren costados).*Cerradura opcional.Medidas:80x40x75cm.', 5547.60, 12, 'https://www.zamofi.com/wp-content/uploads/2017/06/CP-125.1.png', 1, 0, 1),
('CP 85','-Credenza Multiusos. 2 puertas abatibles con entrepaño interno.*Laterales no visibles (Frentes de puertas cubren costados).*Cerradura opcional.Medidas:80x50x75cm.', 7273.80, 12, 'https://www.zamofi.com/wp-content/uploads/2017/06/CP-125.2.png', 1, 0, 1),
('CP 124','-Credenza Multiusos. 2 puertas abatibles con entrepaño interno.*Laterales no visibles (Frentes de puertas cubren costados).*Cerradura opcional.Medidas:120x40x75cm.', 6806.70, 12, 'https://www.zamofi.com/wp-content/uploads/2017/06/CZ-165.1.png', 1, 0, 1),
('CP 125','-Credenza Multiusos. 2 puertas abatibles con entrepaño interno.*Laterales no visibles (Frentes de puertas cubren costados).*Cerradura opcional.Medidas:120x50x75cm.', 7843.50, 12, 'https://www.zamofi.com/wp-content/uploads/2017/06/CZ-165.2.png', 1, 0, 1),
('CZ 164','-Credenza Modelo "Z". 2 puertas abatibles con entrepaño interno, 2 cajones papeleros y 1 cajón de archivo, tamaño oficio.*Laterales no visibles (Frentes de puertas y cajones cubren costados).*Cerradura múltiple en cajones.*Cerradura en puertas opcional.Medidas:160x40x75cm.', 12393.90, 12, 'https://www.zamofi.com/wp-content/uploads/2017/06/CZ-185.1.png', 1, 0, 1),
('CZ 165','-Credenza Modelo "Z". 2 puertas abatibles con entrepaño interno, 2 cajones papeleros y 1 cajón de archivo, tamaño oficio.*Laterales no visibles (Frentes de puertas y cajones cubren costados).*Cerradura múltiple en cajones.*Cerradura en puertas opcional.Medidas:160x50x75cm.', 13776.30, 12, 'https://www.zamofi.com/wp-content/uploads/2017/06/CZ-185.2.png', 1, 0, 1),
('CZ 184','-Credenza Modelo "Z". 2 puertas abatibles con entrepaño interno, doble cajonera con 2 cajones papeleros y 1 cajón de archivo, tamaño oficio.*Laterales no visibles (Frentes de puertas y cajones cubren costados).*Cerradura múltiple en cajones.*Cerradura en puertas opcional.Medidas:180x40x75cm.', 17067.60, 12, 'https://www.zamofi.com/wp-content/uploads/2017/06/tempo-001.png', 1, 0, 1),
('CZ 185','-Credenza Modelo "Z". 2 puertas abatibles con entrepaño interno, doble cajonera con 2 cajones papeleros y 1 cajón de archivo, tamaño oficio.*Laterales no visibles (Frentes de puertas y cajones cubren costados).*Cerradura múltiple en cajones.*Cerradura en puertas opcional.Medidas:180x50x75cm.', 18489.60, 12, 'https://www.zamofi.com/wp-content/uploads/2017/06/tempo-002.png', 1, 0, 1);

-- MESAS (categoria_id: 18)
INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES
('ME 1200','-Mesa de trabajo con estructura metálica cuadrada 1"x1". *Cubierta en melamina 25mm. *Tapas metálicas inferiores en forma de U. *Opcional pasacables o canaleta porta cables. Medidas: 120x60x75cm.', 4633.20, 18, 'https://www.zamofi.com/wp-content/uploads/2018/05/ME-1200-300x169.png', 1, 0, 1),
('MA 120/140','-Mesa de trabajo "Alfa". *Estructura metálica cuadrada 1"x1". *Cubierta en melamina de 25mm. *Faldón metálico inferior. *Opcional pasacables o canaleta porta cables. Medidas: 120x60x75cm y 140x70x75cm.', 4389.30, 18, 'https://www.zamofi.com/wp-content/uploads/2018/05/MA-120-140-300x169.png', 1, 0, 1),
('MTE','-Mesa tipo escritorio "Económica". *Cubierta y costados en melamina 25mm. *Faldón inferior 16mm. *Opcional pasacables o canaleta porta cables. Medidas: 120x60x75cm.', 4653.00, 18, 'https://www.zamofi.com/wp-content/uploads/2018/05/MTE-300x169.png', 1, 0, 1),
('LTM','-Mesa de trabajo "Lite M". *Cubierta en melamina 25mm. *Estructura metálica cuadrada 1"x1". *Tapas metálicas inferiores. *Opcional canaleta porta cables. Medidas: 140x70x75cm.', 7273.80, 18, 'https://www.zamofi.com/wp-content/uploads/2018/05/LTM-300x169.png', 1, 0, 1),
('LT 1','-Mesa de trabajo "Lite". *Cubierta en melamina 25mm. *Estructura metálica cuadrada 1"x1". *Faldón metálico inferior. Medidas: 120x60x75cm.', 5059.80, 18, 'https://www.zamofi.com/wp-content/uploads/2018/05/LT-1-300x169.png', 1, 0, 1),
('MS 1260','-Mesa de trabajo "Sigma". *Estructura metálica cuadrada 1"x1". *Cubierta en melamina 25mm. *Niveladores plásticos. Medidas: 120x60x75cm.', 5100.30, 18, 'https://www.zamofi.com/wp-content/uploads/2018/05/MS-1260-300x169.png', 1, 0, 1),
('MS 1470','-Mesa de trabajo "Sigma". *Estructura metálica cuadrada 1"x1". *Cubierta en melamina 25mm. *Niveladores plásticos. Medidas: 140x70x75cm.', 7254.00, 18, 'https://www.zamofi.com/wp-content/uploads/2018/05/MS-1470-300x169.png', 1, 0, 1),
('MS 1212','-Mesa de trabajo "Sigma". *Estructura metálica cuadrada 1"x1". *Cubierta en melamina 25mm. *Niveladores plásticos. Medidas: 120x120x75cm.', 7030.80, 18, 'https://www.zamofi.com/wp-content/uploads/2018/05/MS-1212-300x169.png', 1, 0, 1),
('MM 140','-Mesa de trabajo "Modular". *Estructura metálica cuadrada 1"x1". *Cubierta en melamina 25mm. *Tapas metálicas laterales removibles. *Opción canaleta porta cables. Medidas: 140x70x75cm.', 7273.80, 18, 'https://www.zamofi.com/wp-content/uploads/2018/05/MM140-2-300x169.png', 1, 0, 1),
('MM 120','-Mesa de trabajo "Modular". *Estructura metálica cuadrada 1"x1". *Cubierta en melamina 25mm. *Tapas metálicas laterales removibles. *Opción canaleta porta cables. Medidas: 120x60x75cm.', 5059.80, 18, 'https://www.zamofi.com/wp-content/uploads/2018/05/MM120-1-300x169.png', 1, 0, 1),
('MMT','-Mesa "Modular T". *Estructura metálica cuadrada 1"x1". *Cubierta en melamina 25mm. *Opción de canaleta porta cables. Medidas: 120x60x75cm.', 5303.70, 18, 'https://www.zamofi.com/wp-content/uploads/2018/05/MMT-1-300x169.png', 1, 0, 1),
('MMT 120','-Mesa "Modular T" 120. *Estructura metálica cuadrada 1"x1". *Cubierta en melamina 25mm. *Opción de canaleta porta cables. Medidas: 120x60x75cm.', 5303.70, 18, 'https://www.zamofi.com/wp-content/uploads/2018/05/MMT120-1-300x169.png', 1, 0, 1),
('MLT 1260','-Mesa "Lite" 1260. *Estructura metálica cuadrada 1"x1". *Cubierta en melamina 25mm. *Tapas metálicas inferiores. *Opción canaleta porta cables. Medidas: 120x60x75cm.', 3962.70, 18, 'https://www.zamofi.com/wp-content/uploads/2018/06/MLT-1260-300x169.png', 1, 0, 1),
('MLT 1880','-Mesa "Lite" 1880. *Estructura metálica cuadrada 1"x1". *Cubierta en melamina 25mm. *Tapas metálicas inferiores. *Opción canaleta porta cables. Medidas: 180x80x75cm.', 7924.50, 18, 'https://www.zamofi.com/wp-content/uploads/2018/06/MLT-1880-300x169.png', 1, 0, 1),
('MCS 16','-Mesa "Cross Slim". *Cubierta flotada en melamina 25mm. *Estructura metálica cuadrada 1"x1". *Niveladores plásticos. *Opción canaleta porta cables. Medidas: 160x70x75cm.', 13125.60, 18, 'https://www.zamofi.com/wp-content/uploads/2018/05/MCS-16-2.03-300x169.png', 1, 0, 1);

-- LIBREROS (categoria_id: 17)
INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES
('LA 120','-Librero a piso abierto. 3 claros organizadores tamaño carta. Costados en 16mm. y entrepaños de 28mm. Medidas: 80x37x120cm.', 6501.60, 17, 'https://www.zamofi.com/wp-content/uploads/2017/06/LA-120-300x169.png', 1, 0, 1),
('LG 120','-Gabinete con puertas completas. 3 claros organizadores tamaño carta. Costados en 16mm y entrepaños de 28mm. Incluye cerradura. Medidas: 80x37x120cm.', 8940.60, 17, 'https://www.zamofi.com/wp-content/uploads/2017/06/LG-120-300x169.png', 1, 0, 1),
('LA 16','-Librero a piso abierto. 5 claros organizadores tamaño carta. Fabricado en 16mm. Sistema armado reforzado. Medidas: 80x35x180cm.', 7111.80, 17, 'https://www.zamofi.com/wp-content/uploads/2017/06/LA-16-300x169.png', 1, 0, 1),
('LA 2816','-Librero a piso abierto. 5 claros organizadores tamaño carta. Costados en 16mm y entrepaños en 28mm. Sistema armado reforzado. Medidas: 80x35x180cm.', 8107.20, 17, 'https://www.zamofi.com/wp-content/uploads/2017/06/LA-2816-300x169.png', 1, 0, 1),
('LA 28','-Librero a piso abierto. 5 claros organizadores tamaño carta. Fabricado en 28mm. Sistema armado reforzado. Medidas: 80x35x180cm.', 11012.40, 17, 'https://www.zamofi.com/wp-content/uploads/2017/06/LA-28-300x169.png', 1, 0, 1),
('LA 289','-Librero a piso abierto. 5 claros organizadores tamaño carta. Fabricado en 28mm. Sistema armado reforzado. Medidas: 90x35x180cm.', 11865.60, 17, 'https://www.zamofi.com/wp-content/uploads/2017/06/LA-28-300x169.png', 1, 0, 1),
('LP 16','-Librero a piso con puertas inferiores. 5 claros organizadores tamaño carta y 2 puertas inferiores abatibles. Fabricado en 16mm. Cerradura en puertas opcional. Medidas: 80x35x180cm.', 8533.80, 17, 'https://www.zamofi.com/wp-content/uploads/2017/06/LP-16-c.png', 1, 0, 1),
('LP 2816','-Librero a piso con puertas inferiores. 5 claros organizadores tamaño carta y 2 puertas inferiores abatibles. Costados en 16mm y entrepaños en 28mm. Cerradura opcional. Medidas: 80x35x180cm.', 9529.20, 17, 'https://www.zamofi.com/wp-content/uploads/2017/06/LP-2816-c.png', 1, 0, 1),
('LP 28','-Librero a piso con puertas inferiores. 5 claros organizadores tamaño carta y 2 puertas inferiores abatibles. Fabricado en 28mm. Cerradura opcional. Medidas: 80x35x180cm.', 12435.30, 17, 'https://www.zamofi.com/wp-content/uploads/2017/06/LP-28-c.png', 1, 0, 1),
('LP 289','-Librero a piso con puertas inferiores. 5 claros organizadores tamaño carta y 2 puertas inferiores abatibles. Fabricado en 28mm. Cerradura opcional. Medidas: 90x35x180cm.', 13491.90, 17, 'https://www.zamofi.com/wp-content/uploads/2017/06/LP-28-c.png', 1, 0, 1),
('GU 16','-Gabinete con puertas completas. 5 claros organizadores tamaño carta y 2 puertas completas abatibles. Fabricado en 16mm. Sistema armado reforzado. Incluye cerradura. Medidas: 80x37x180cm.', 10850.40, 17, 'https://www.zamofi.com/wp-content/uploads/2017/06/GU-16-c.png', 1, 0, 1),
('GU 2816','-Gabinete con puertas completas. 5 claros organizadores tamaño carta y 2 puertas completas abatibles. Costados en 16mm y entrepaños en 28mm. Sistema armado reforzado. Incluye cerradura. Medidas: 80x37x180cm.', 11845.80, 17, 'https://www.zamofi.com/wp-content/uploads/2017/06/GU-2816-c.png', 1, 0, 1),
('GU 28','-Gabinete con puertas completas. 5 claros organizadores tamaño carta y 2 puertas completas abatibles. Fabricado en 28mm. Sistema armado reforzado. Incluye cerradura. Medidas: 80x37x180cm.', 14751.00, 17, 'https://www.zamofi.com/wp-content/uploads/2017/06/GU-28-c.png', 1, 0, 1),
('GU 289','-Gabinete con puertas completas. 5 claros organizadores tamaño carta y 2 puertas completas abatibles. Fabricado en 28mm. Sistema armado reforzado. Incluye cerradura. Medidas: 90x37x180cm.', 15726.60, 17, 'https://www.zamofi.com/wp-content/uploads/2017/06/GU-28-c.png', 1, 0, 1),
('LX 120','-Librero modelo "Expo". Con divisiones, 2 puertas corredizas inferiores y 1 puerta abatible. Fabricado en 16mm. No incluye cerradura. Medidas: 120x30x200cm.', 8026.20, 17, 'https://www.zamofi.com/wp-content/uploads/2017/06/LX-120-c.png', 1, 0, 1),
('LPT 16','-Librero modelo "Tiro". 5 claros organizadores tamaño carta y 2 puertas inferiores abatibles. Fabricado en 16mm. No incluye cerradura. Medidas: 120x35x180cm.', 12293.10, 17, 'https://www.zamofi.com/wp-content/uploads/2017/06/LPT-16-c.png', 1, 0, 1),
('LPT 28','-Librero modelo "Tiro". 5 claros organizadores tamaño carta y 2 puertas inferiores abatibles. Fabricado en 28mm. No incluye cerradura. Medidas: 120x35x180cm.', 17908.00, 17, 'https://www.zamofi.com/wp-content/uploads/2017/06/LPT-28-c.png', 1, 0, 1),
('LS 160','-Librero sobre credenza. 2 puertas superiores y hueco organizador. Costados en 28mm. resto en 16mm. No incluye cerradura. Medidas: 160x32x110cm.', 8026.20, 17, 'https://www.zamofi.com/wp-content/uploads/2017/06/LC-160.png', 1, 0, 1),
('LS 180','-Librero sobre credenza. 2 puertas superiores. Costados en 28mm. resto en 16mm. No incluye cerradura. Medidas: 180x33x110cm.', 10017.00, 17, 'https://www.zamofi.com/wp-content/uploads/2017/06/LC-180.png', 1, 0, 1),
('LSO 28','-Librero sobre credenza. 2 puertas superiores y 5 claros organizadores. Costados en 28mm. resto en 16mm. No incluye cerradura. Medidas: 180x33x110cm.', 12049.20, 17, 'https://www.zamofi.com/wp-content/uploads/2017/06/LCO-180.png', 1, 0, 1);

-- MESAS DE JUNTAS (categoria_id: 20)
INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES
('MJ 120','Mesa de juntas modelo MJ-120 con cubierta circular en melamina de 28 mm y base metálica de patas cruzadas. Ideal para espacios ejecutivos y de reuniones.', 5689.80, 20, 'https://www.zamofi.com/wp-content/uploads/2017/06/MJ-120..01-1.png', 1, 0, 1),
('MJN 240','Mesa de juntas Nova MJN-240 con cubierta tipo bote y estructura en melamina 28 mm, patas rectas y faldón reforzado.', 10098.00, 20, 'https://www.zamofi.com/wp-content/uploads/2017/06/MJN-240.01.png', 1, 0, 1),
('MJN 360','Mesa de juntas Nova MJN-360 con cubierta tipo bote dividida en dos piezas, ideal para juntas grandes.', 20277.90, 20, 'https://www.zamofi.com/wp-content/uploads/2017/06/MJN-360.01.png', 1, 0, 1),
('MJE 120','Mesa de juntas Euro MJE-120 con cubierta circular de 120 cm de diámetro y patas metálicas tipo trineo.', 6299.10, 20, 'https://www.zamofi.com/wp-content/uploads/2017/06/MJE-120.01.png', 1, 0, 1),
('MJE 240','Mesa de juntas Euro MJE-240 con cubierta rectangular en melamina 28 mm y base metálica trineo.', 9895.50, 20, 'https://www.zamofi.com/wp-content/uploads/2017/06/MJE-240.01.png', 1, 0, 1),
('MJE 360','Mesa de juntas Euro MJE-360 con cubierta amplia de tres metros y estructura doble metálica.', 14954.40, 20, 'https://www.zamofi.com/wp-content/uploads/2017/06/MJE-360.01.png', 1, 0, 1),
('MJT 120','Mesa de juntas Tempo MJT-120 circular con cubierta tipo tambor de 7 cm.', 11581.20, 20, 'https://www.zamofi.com/wp-content/uploads/2017/06/MJT-120.01.png', 1, 0, 1),
('MJT 240','Mesa de juntas Tempo MJT-240 rectangular con cubierta tipo tambor y faldón bajo.', 22553.10, 20, 'https://www.zamofi.com/wp-content/uploads/2017/06/MJT-240.01.png', 1, 0, 1),
('MJT 360','Mesa de juntas Tempo MJT-360 con cubierta rectangular dividida, base robusta y faldón lateral.', 37994.40, 20, 'https://www.zamofi.com/wp-content/uploads/2017/06/MJT-360.01.png', 1, 0, 1),
('MJK 240','Mesa de juntas Kris MJK-240 con detalle central de cristal y cubierta en melamina 28 mm.', 19302.30, 20, 'https://www.zamofi.com/wp-content/uploads/2017/06/MJK-240.01.png', 1, 0, 1),
('MJDT 320','Mesa de juntas Delta MJDT-320 con cubierta tipo tambor y cristal central. Diseño moderno para salas ejecutivas.', 36775.80, 20, 'https://www.zamofi.com/wp-content/uploads/2017/06/MJDT-320.01.png', 1, 0, 1);

-- ISLAS DE TRABAJO (categoria_id: 21)
INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES
('MEM 12','Isla recta modelo MEM-12 para 2 usuarios, con cubierta en melamina de 28 mm, estructura metálica y mampara divisoria en melamina 16 mm.', 18083.70, 21, 'https://www.zamofi.com/wp-content/uploads/2018/07/ISLA-RECTA-12-M.-MELAMINA.png', 1, 0, 1),
('MEC 12','Isla recta modelo MEC-12 para 2 usuarios, con cubierta en melamina 28 mm y mampara divisoria en cristal inastillable.', 20927.70, 21, 'https://www.zamofi.com/wp-content/uploads/2018/07/ISLA-RECTA-12-M.-CRISTAL.png', 1, 0, 1),
('MEM 15','Isla recta modelo MEM-15 para 2 usuarios, versión amplia con cubierta de 150x120 cm en melamina 28 mm y mampara en melamina 16 mm.', 19363.50, 21, 'https://www.zamofi.com/wp-content/uploads/2018/07/ISLA-RECTA-M.-MELAMINA.png', 1, 0, 1),
('MEC 15','Isla recta modelo MEC-15 para 2 usuarios, cubierta 150x120 cm en melamina 28 mm y mampara en cristal inastillable.', 22614.30, 21, 'https://www.zamofi.com/wp-content/uploads/2018/07/ISLA-RECTA-M.-CRISTAL.png', 1, 0, 1),
('TEM','Triceta modelo TEM para 3 usuarios, con cubierta central circular en melamina 28 mm y mampara divisoria en melamina 16 mm.', 27632.70, 21, 'https://www.zamofi.com/wp-content/uploads/2018/07/TRICETA-M.-MELAMINA.png', 1, 0, 1),
('TEC','Triceta modelo TEC para 3 usuarios, con mampara divisoria en cristal templado y cubierta en melamina 28 mm.', 22614.30, 21, 'https://www.zamofi.com/wp-content/uploads/2018/07/TRICETA-M.-CRISTAL.png', 1, 0, 1),
('CRM','Cruzeta modelo CRM para 4 usuarios, cubierta cruzada en melamina 28 mm con mampara en melamina 16 mm.', 48946.50, 21, 'https://www.zamofi.com/wp-content/uploads/2018/07/CRUZETA-M.-MELAMINA.png', 1, 0, 1),
('CRC','Cruzeta modelo CRC para 4 usuarios, cubierta cruzada en melamina 28 mm con mampara en cristal inastillable.', 51282.90, 21, 'https://www.zamofi.com/wp-content/uploads/2018/07/CRUZETA-M.-CRISTAL.png', 1, 0, 1);

-- RECEPCIÓN (categoria_id: 22)
INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES
('RQ 180','Recepción modelo Q-dra 180, mostrador con curvas y cubierta melamina 28 mm.', 14121.00, 22, 'https://www.zamofi.com/wp-content/uploads/2017/06/RQ-180.01.png', 1, 0, 1),
('RM 180','Recepción modelo Marvic 180, diseño recto con faldón frontal en melamina.', 28851.30, 22, 'https://www.zamofi.com/wp-content/uploads/2017/06/RM-180.01.png', 1, 0, 1),
('RR 200','Recepción modelo Sigma 200, mostrador amplio con cubierta melamina 28 mm y diseño moderno.', 15238.80, 22, 'https://www.zamofi.com/wp-content/uploads/2017/06/RR-200.01.png', 1, 0, 1),
('RNR 180','Recepción modelo Nova RNR 180, estilo contemporáneo con lineas rectas y cubierta melamina.', 14121.00, 22, 'https://www.zamofi.com/wp-content/uploads/2017/06/RNR-180.01.png', 1, 0, 1);

-- LÍNEA ANZIO (categoria_id: 14 - Subcategoría de Línea Italia)
INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES
('Anzio Operativos','Estaciones de trabajo Anzio Operativos con diseño modular, alta flexibilidad y opciones de cableado integrado. Fabricadas en melamina con estructura metálica reforzada.', 19465.96, 14, 'https://lineaitalia.com.mx/wp-content/uploads/2025/04/Anzio_Operativos_3.webp', 1, 1, 1),
('Anzio Directivos','Escritorios ejecutivos Anzio Directivos con diseño elegante, superficie amplia en melamina y detalles en aluminio anodizado. Perfectos para oficinas de alta dirección.', 21862.52, 14, 'https://lineaitalia.com.mx/wp-content/uploads/2025/04/Anzio_Directivos_1.webp', 1, 1, 1),
('Anzio Conferencias','Mesas de juntas Anzio Conferencias, funcionales y modernas, con sistema de electrificación y acabados premium en melamina.', 29470.96, 14, 'https://lineaitalia.com.mx/wp-content/uploads/2025/04/Juntas_Anzio_1-scaled.jpg', 1, 1, 1),
('Anzio Almacenamiento','Módulos de almacenamiento Anzio Storage con puertas abatibles y compartimientos modulares. Fabricados en melamina de alta resistencia.', 13679.88, 14, 'https://lineaitalia.com.mx/wp-content/uploads/2025/04/Almacenamiento_Anzio_1-scaled.jpg', 1, 1, 1),
('Anzio Recepción','Recepciones Anzio con diseño contemporáneo, estructura sólida y acabados melamínicos, ideales para áreas de atención y bienvenida.', 58033.64, 14, 'https://lineaitalia.com.mx/wp-content/uploads/2025/04/Anzio_Recepcion_1.webp', 1, 1, 1);

-- LÍNEA IWORK & PRIVATT (categoria_id: 15 - Subcategoría de Línea Italia)
INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES
('Privatt','Sistema de estaciones de trabajo Privatt para Call Center, diseño cerrado que ofrece privacidad acústica y funcional. Modular y adaptable con opciones de cableado integrado.', 15598.52, 15, 'https://lineaitalia.com.mx/wp-content/uploads/2025/04/Cerradas_Privatt-scaled.jpg', 1, 1, 1),
('I-Work','Estaciones I-Work abiertas y colaborativas para Call Center, con paneles divisorios bajos, estructura metálica y sistema de electrificación modular.', 19465.96, 15, 'https://lineaitalia.com.mx/wp-content/uploads/2025/04/Cerradas_Iwork-scaled.jpg', 1, 1, 1);

-- LÍNEA ITALIA (categoria_id: 16 - Subcategoría de Línea Italia)
INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES
('Escritorios Operativos Italia','Línea Italia Operativos: estaciones de trabajo modulares, diseño funcional y resistente para oficinas dinámicas.', 16211.00, 16, 'https://lineaitalia.com.mx/wp-content/uploads/2025/04/Operativo_Italia_1-scaled.jpg', 1, 1, 1),
('Escritorios Ejecutivos Italia','Línea Italia Ejecutivos: estilo elegante con estructura reforzada, ideal para oficinas de dirección y espacios premium.', 19489.16, 16, 'https://lineaitalia.com.mx/wp-content/uploads/2025/04/Ejecutivos_Italia_1-scaled.jpg', 1, 1, 1),
('Escritorios Directivos Italia','Línea Italia Directivos: escritorios amplios y sofisticados con acabados de lujo y cubierta de alta densidad.', 21862.52, 16, 'https://lineaitalia.com.mx/wp-content/uploads/2025/04/Directivo_Italia_1-scaled.jpg', 1, 1, 1),
('Mesas de Juntas Italia','Mesas de juntas Italia: superficies amplias, estructura metálica y diseño moderno para salas de reunión ejecutiva.', 29470.96, 16, 'https://lineaitalia.com.mx/wp-content/uploads/2025/04/Juntas_Italia_1-scaled.jpg', 1, 1, 1),
('Almacenamiento Italia','Módulos de almacenamiento Italia: credenzas, archiveros y lockers con acabados resistentes y elegantes.', 13679.88, 16, 'https://lineaitalia.com.mx/wp-content/uploads/2025/04/Almacenamiento_1-scaled.jpg', 1, 1, 1),
('Recepciones Italia','Recepciones Italia: mostradores modernos y funcionales con acabados contemporáneos y diseño corporativo.', 58033.64, 16, 'https://lineaitalia.com.mx/wp-content/uploads/2025/04/Recepcion_1-scaled.jpg', 1, 1, 1);

-- SALAS / SOFÁS (categoria_id: 5 - Subcategoría de Sillería)
INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES
('Ibiza','Sillón curvo tapizado, líneas suaves para lounge.', 5775.60, 5, 'https://grupoalbar.com/wp-content/uploads/2024/06/sillon-ibiza-34-300x300.jpg', 1, 0, 1),
('Larisa','Módulo esquinero compacto, estética moderna.', 6096.50, 5, 'https://grupoalbar.com/wp-content/uploads/2024/06/larisa-34-300x300.jpg', 1, 0, 1),
('Nantes','Sofá 2 plazas con base metálica expuesta.', 6417.30, 5, 'https://grupoalbar.com/wp-content/uploads/2024/06/nantes-34-300x300.jpg', 1, 0, 1),
('Roma','Sillón cúbico de brazos altos, look elegante.', 5653.80, 5, 'https://grupoalbar.com/wp-content/uploads/2024/02/roma-1p-34-300x300.jpg', 1, 0, 1),
('Ottawa','Set de puffs redondos, varios diámetros y alturas.', 1665.00, 5, 'https://grupoalbar.com/wp-content/uploads/2024/02/ottawa-300x300.jpg', 1, 0, 1),
('Kassel','Sofá modular bajo, estilo contemporáneo.', 8346.60, 5, 'https://grupoalbar.com/wp-content/uploads/2024/02/kassel-34-300x300.jpg', 1, 0, 1),
('Seul','Sillón con cojín suelto y patas delgadas.', 7572.60, 5, 'https://grupoalbar.com/wp-content/uploads/2024/02/seul-1p-34-300x300.jpg', 1, 0, 1),
('Oslo','Butaca ligera con brazos abiertos y estructura metálica.', 5841.00, 5, 'https://grupoalbar.com/wp-content/uploads/2024/03/oslo-34-300x300.jpg', 1, 0, 1),
('Argos','Sillón de asiento profundo con base metálica.', 7922.70, 5, 'https://grupoalbar.com/wp-content/uploads/2024/03/argos-1p-34-300x300.jpg', 1, 0, 1),
('Atenas','Butaca con brazos rectos y patas altas.', 7495.20, 5, 'https://grupoalbar.com/wp-content/uploads/2024/03/atenas-1p-34-300x300.jpg', 1, 0, 1),
('Marruecos','Sillón con brazos de madera, estilo cálido.', 10307.70, 5, 'https://grupoalbar.com/wp-content/uploads/2024/03/marruecos-1p-34-300x300.jpg', 1, 0, 1),
('Asturias','Sillón compacto de líneas rectas y estable.', 5253.30, 5, 'https://grupoalbar.com/wp-content/uploads/2024/03/asturias-cb-34-300x300.jpg', 1, 0, 1),
('Lyon','Sillón con patas cónicas de madera, look nórdico.', 7572.60, 5, 'https://grupoalbar.com/wp-content/uploads/2024/03/lyon-1p-34-300x300.jpg', 1, 0, 1),
('Berlin','Sillón con armazón metálico visible, estilo industrial.', 8150.40, 5, 'https://grupoalbar.com/wp-content/uploads/2024/03/berlin-1p-34-300x300.jpg', 1, 0, 1),
('Copenhaguen','Sillón bajo y ancho, líneas minimalistas.', 10374.30, 5, 'https://grupoalbar.com/wp-content/uploads/2024/03/copenhaguen-1p-34-300x300.jpg', 1, 0, 1),
('Arezzo','Sillón con doble aro perimetral tapizado suave.', 1636.20, 5, 'https://grupoalbar.com/wp-content/uploads/2024/03/arezzo-1p-34-1-300x300.jpg', 1, 0, 1),
('Monaco','Sillón ejecutivo acolchado de respaldo envolvente.', 6197.40, 5, 'https://grupoalbar.com/wp-content/uploads/2024/04/monaco-1p-34-300x300.jpg', 1, 0, 1),
('Lutecia','Sillón de brazos gruesos y gran confort.', 7171.20, 5, 'https://grupoalbar.com/wp-content/uploads/2024/04/lutecia-1p-34-300x300.jpg', 1, 0, 1),
('Ankara','Módulo esquinero tapizado, base discreta.', 6956.10, 5, 'https://grupoalbar.com/wp-content/uploads/2024/04/ankara-1p-34-300x300.jpg', 1, 0, 1),
('Parma','Sillón con costuras visibles y estilo clásico.', 7171.20, 5, 'https://grupoalbar.com/wp-content/uploads/2024/04/parma-1p-34-300x300.jpg', 1, 0, 1),
('Dresden','Butaca compacta con patas delgadas y respaldo firme.', 5494.50, 5, 'https://grupoalbar.com/wp-content/uploads/2024/04/dresden-1p-34-300x300.jpg', 1, 0, 1),
('Milan','Sillón monoplaza de líneas rectas y asiento alto.', 5193.00, 5, 'https://grupoalbar.com/wp-content/uploads/2024/04/milan-1p-34-300x300.jpg', 1, 0, 1),
('Amsterdam','Butaca tapizada de respaldo medio, look casual.', 6346.80, 5, 'https://grupoalbar.com/wp-content/uploads/2024/04/amsterdam-1p-34-300x300.jpg', 1, 0, 1),
('Sofia','Silla lounge con patas de madera y asiento amplio.', 6808.50, 5, 'https://grupoalbar.com/wp-content/uploads/2024/04/sofia-1p-34-300x300.jpg', 1, 0, 1),
('Granada & Puff','Butaca curva con puff a juego, set decorativo.', 7379.90, 5, 'https://grupoalbar.com/wp-content/uploads/2024/04/granada-plus-300x300.jpg', 1, 0, 1),
('Chaselonge','Chaise longue tapizada para descanso y lectura.', 8342.50, 5, 'https://grupoalbar.com/wp-content/uploads/2024/04/chaselon-md-300x300.jpg', 1, 0, 1),
('Lounge','Módulo lounge tapizado, aspecto limpio y versátil.', 6417.30, 5, 'https://grupoalbar.com/wp-content/uploads/2024/04/lounge-1p-300x300.jpg', 1, 0, 1);

-- SILLAS EJECUTIVAS (categoria_id: 4 - Subcategoría de Sillería)
INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES
('OHE-805','Silla ejecutiva de respaldo alto tapizada en piel sintética, con mecanismo reclinable y base cromada.', 10955.70, 4, 'https://www.offiho.com/ejecutivos/galeria/OHE-805.jpg', 1, 0, 1),
('OHE-35','Silla ejecutiva de diseño clásico, respaldo medio y brazos fijos tapizados. Ideal para oficina o home office.', 4532.40, 4, 'https://www.offiho.com/ejecutivos/galeria/OHE-35.jpg', 1, 0, 1),
('OHE-605','Silla ejecutiva color negro con soporte lumbar, respaldo medio y ruedas silenciosas.', 4532.40, 4, 'https://www.offiho.com/ejecutivos/galeria/OHE-605negro.jpg', 1, 0, 1),
('OHE-113','Silla ejecutiva en tono gris con estructura ergonómica, ajuste de altura y brazos fijos modernos.', 4532.40, 4, 'https://www.offiho.com/ejecutivos/galeria/OHE-113gris.jpg', 1, 0, 1),
('OHE-195','Silla ejecutiva de respaldo alto tapizada en vinil negro con base metálica y soporte lumbar integrado.', 4532.40, 4, 'https://www.offiho.com/ejecutivos/galeria/OHE-195negro.jpg', 1, 0, 1),
('OHE-185','Silla ejecutiva moderna en color negro, diseño ergonómico y brazos ajustables.', 4532.40, 4, 'https://www.offiho.com/ejecutivos/galeria/OHE-185.jpg', 1, 0, 1),
('OHE-112','Silla ejecutiva blanca con acabado premium, respaldo medio y mecanismo basculante.', 4532.40, 4, 'https://www.offiho.com/ejecutivos/galeria/OHE-112blanco.jpg', 1, 0, 1),
('OHE-705','Silla ejecutiva negra de respaldo alto, base cromada y diseño elegante.', 6295.50, 4, 'https://www.offiho.com/ejecutivos/galeria/OHE-705.jpg', 1, 0, 1),
('OHE-905','Silla ejecutiva negra con mecanismo reclinable avanzado y soporte de cabeza.', 4532.40, 4, 'https://www.offiho.com/ejecutivos/galeria/OHE-905negro.jpg', 1, 0, 1),
('OHE-95','Silla ejecutiva blanca con base de aluminio y sistema de elevación neumática.', 4532.40, 4, 'https://www.offiho.com/ejecutivos/galeria/OHE-95blanco.jpg', 1, 0, 1),
('OHE-133PLUS','Silla ejecutiva ergonómica OHE-133PLUS, malla transpirable y ajuste lumbar avanzado.', 5036.40, 4, 'https://www.offiho.com/ejecutivos/galeria/OHE-133plus.jpg', 1, 0, 1),
('OHE-140','Silla ejecutiva OHE-140 negra, diseño elegante con base de acero cromado.', 4532.40, 4, 'https://www.offiho.com/ejecutivos/galeria/OHE-140negro.jpg', 1, 0, 1),
('OHE-2005','Silla ejecutiva gris de respaldo alto con soporte de cabeza y base metálica robusta.', 6295.50, 4, 'https://www.offiho.com/ejecutivos/galeria/OHE-2005gris.jpg', 1, 0, 1),
('OHE-405','Silla ejecutiva de respaldo alto tapizada en piel negra con base cromada y soporte ergonómico.', 10955.70, 4, 'https://www.offiho.com/direccion/galeria/OHE-405.jpg', 1, 0, 1),
('OHE-305','Silla ejecutiva negra OHE-305 con diseño moderno, base metálica y ajuste de altura neumático.', 4532.40, 4, 'https://www.offiho.com/direccion/galeria/OHE-305negro.jpg', 1, 0, 1),
('OHE-205','Silla ejecutiva OHE-205 con respaldo medio tapizado en vinil negro y estructura cromada.', 4532.40, 4, 'https://www.offiho.com/direccion/galeria/OHE-205negro.jpg', 1, 0, 1),
('OHE-165','Silla ejecutiva OHE-165 con mecanismo basculante, tapizada en piel sintética color negro.', 4532.40, 4, 'https://www.offiho.com/direccion/galeria/OHE-165negro.jpg', 1, 0, 1),
('OHV-78','Silla ejecutiva tipo visita OHV-78 con estructura tubular cromada y asiento tapizado.', 4532.40, 4, 'https://www.offiho.com/direccion/galeria/OHV-78.jpg', 1, 0, 1),
('OHE-295','Silla ejecutiva OHE-295 negra con respaldo alto, base cromada y brazos fijos ergonómicos.', 6295.50, 4, 'https://www.offiho.com/direccion/galeria/OHE-295negro.jpg', 1, 0, 1);


-- SILLAS OPERATIVAS (categoria_id: 3 - Subcategoría de Sillería)
INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES
('OHE-99','Silla operativa ergonómica con respaldo medio, tapizada en malla transpirable y base plástica reforzada.', 1762.20, 3, 'https://www.offiho.com/operativos/galeria/OHE-99.jpg', 1, 0, 1),
('OHE-111','Silla operativa negra con ajuste de altura y mecanismo de reclinación, respaldo medio y brazos fijos.', 2519.10, 3, 'https://www.offiho.com/operativos/galeria/OHE-111negro.jpg', 1, 0, 1),
('OHE-65','Silla operativa color negro, diseño ergonómico con soporte lumbar y ruedas silenciosas.', 2769.30, 3, 'https://www.offiho.com/operativos/galeria/OHE-65negro.jpg', 1, 0, 1),
('OHE-84','Silla operativa color gris con respaldo medio, estructura plástica y tapizado en tela resistente.', 2769.30, 3, 'https://www.offiho.com/operativos/galeria/OHE-84gris.jpg', 1, 0, 1),
('OHE-98','Silla operativa gris, respaldo ergonómico con ventilación en malla y base cromada.', 1762.20, 3, 'https://www.offiho.com/operativos/galeria/OHE-98gris.jpg', 1, 0, 1),
('OHE-175','Silla operativa moderna con base cromada, asiento acolchado y ajuste de altura neumático.', 2769.30, 3, 'https://www.offiho.com/operativos/galeria/OHE-175.jpg', 1, 0, 1),
('OHE-55','Silla operativa negra con brazos fijos y soporte lumbar, base metálica con ruedas.', 2519.10, 3, 'https://www.offiho.com/operativos/galeria/OHE-55negrocr.jpg', 1, 0, 1),
('OHE-94PLUS','Silla operativa OHE-94PLUS con respaldo alto de malla y ajuste lumbar avanzado.', 1762.20, 3, 'https://www.offiho.com/operativos/galeria/OHE-94plus.jpg', 1, 0, 1),
('OHE-403','Silla operativa negra con mecanismo basculante, asiento acolchado y respaldo ventilado.', 2769.30, 3, 'https://www.offiho.com/operativos/galeria/OHE-403negro.jpg', 1, 0, 1),
('OHE-100','Silla operativa blanca con diseño ergonómico, respaldo en malla y base plástica blanca.', 1762.20, 3, 'https://www.offiho.com/operativos/galeria/OHE-100blanca.jpg', 1, 0, 1),
('OHS-41','Silla secretarial operativa negra, base cromada y respaldo bajo con diseño compacto.', 2266.20, 3, 'https://www.offiho.com/operativos/galeria/OHS-41negro.jpg', 1, 0, 1),
('OHE-2003','Silla operativa ergonómica color negro, ajuste de altura y soporte lumbar integrado.', 2769.30, 3, 'https://www.offiho.com/operativos/galeria/OHE-2003negro.jpg', 1, 0, 1),
('OHE-2006','Silla operativa negra con respaldo medio, tapizado en malla y base metálica.', 1762.20, 3, 'https://www.offiho.com/operativos/galeria/OHE-2006negro.jpg', 1, 0, 1),
('OHS-43','Silla secretarial OHS-43 tapizada en tela negra, respaldo bajo y ruedas de nylon.', 2266.20, 3, 'https://www.offiho.com/operativos/galeria/OHS-43.jpg', 1, 0, 1),
('OHS-42','Silla operativa compacta OHS-42 con respaldo tapizado y altura ajustable.', 2769.30, 3, 'https://www.offiho.com/operativos/galeria/OHS-42.jpg', 1, 0, 1),
('OHS-11','Silla operativa OHS-11 con respaldo de malla, brazos fijos y asiento acolchado.', 1762.20, 3, 'https://www.offiho.com/operativos/galeria/OHS-11.jpg', 1, 0, 1),
('OHS-37','Silla secretarial OHS-37 con base cromada, tapizado en tela y altura regulable.', 2266.20, 3, 'https://www.offiho.com/operativos/galeria/OHS-37.jpg', 1, 0, 1),
('OHE-101','Silla operativa negra con soporte lumbar ajustable y mecanismo basculante.', 2769.30, 3, 'https://www.offiho.com/operativos/galeria/OHE-101negro.jpg', 1, 0, 1),
('OHS-47','Silla secretarial OHS-47 con respaldo ergonómico y ruedas de alta durabilidad.', 2266.20, 3, 'https://www.offiho.com/operativos/galeria/OHS-47.jpg', 1, 0, 1),
('OHS-86','Silla operativa OHS-86 con base cromada, asiento acolchado y diseño moderno.', 2769.30, 3, 'https://www.offiho.com/operativos/galeria/OHS-86cr.jpg', 1, 0, 1);

-- LÍNEA ECO (categoria_id: 27 - Subcategoría de Sillería)
INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES
('Módena','Silla ejecutiva económica modelo Módena con respaldo medio y tapizado en tela resistente.', 4430.70, 27, 'https://www.offiho.com/econosillas/galeria/modena.jpg', 1, 0, 1),
('Rimini','Silla ejecutiva Rimini con respaldo ergonómico y base plástica reforzada.', 4532.40, 27, 'https://www.offiho.com/econosillas/galeria/rimini.jpg', 1, 0, 1),
('Eco Gerencial','Silla gerencial económica, diseño moderno con mecanismo de ajuste de altura.', 3172.50, 27, 'https://www.offiho.com/econosillas/galeria/Eco-gerencial.jpg', 1, 0, 1),
('Padua','Silla operativa Padua tapizada en tela, estructura de polipropileno y ruedas giratorias.', 3096.00, 27, 'https://www.offiho.com/econosillas/galeria/Padua.jpg', 1, 0, 1),
('Bari','Silla económica Bari con respaldo curvo y asiento acolchado.', 2391.30, 27, 'https://www.offiho.com/econosillas/galeria/bari.jpg', 1, 0, 1),
('OHS-10','Silla secretarial OHS-10 tapizada en tela, altura ajustable y base plástica.', 1094.40, 27, 'https://www.offiho.com/econosillas/galeria/OHS-10.jpg', 1, 0, 1),
('Eco Chair','Silla económica Eco Chair con diseño ergonómico y estructura plástica.', 1762.20, 27, 'https://www.offiho.com/econosillas/galeria/Eco-chair.jpg', 1, 0, 1),
('Arezzo','Silla económica Arezzo de respaldo medio con base fija cromada.', 1636.20, 27, 'https://www.offiho.com/econosillas/galeria/arezzo.jpg', 1, 0, 1),
('Cantabria','Silla ejecutiva económica Cantabria, tapizada en tela color negro y base cromada.', 4532.40, 27, 'https://www.offiho.com/econosillas/galeria/cantabria.jpg', 1, 0, 1),
('Econo Mallabco','Silla económica Mallabco con respaldo en malla negra y base plástica.', 1094.40, 27, 'https://www.offiho.com/econosillas/galeria/Econo-mallabco.jpg', 1, 0, 1),
('OHV-54','Silla de visita OHV-54 con base metálica tipo trineo y asiento tapizado.', 1094.40, 27, 'https://www.offiho.com/econosillas/galeria/OHV-54.jpg', 1, 0, 1),
('OHV-141','Silla de visita OHV-141 con brazos metálicos y asiento acolchado.', 1094.40, 27, 'https://www.offiho.com/econosillas/galeria/OHV-141.jpg', 1, 0, 1),
('OHV-56','Silla de visita OHV-56 negra, base cromada y respaldo medio.', 2140.20, 27, 'https://www.offiho.com/econosillas/galeria/OHV-56.jpg', 1, 0, 1),
('OHV-57','Silla alta de visita OHV-57 con estructura cromada y diseño elegante.', 2140.20, 27, 'https://www.offiho.com/econosillas/galeria/OHV-57alto.jpg', 1, 0, 1),
('OHV-58','Silla alta OHV-58 con asiento tapizado y base metálica tubular.', 1094.40, 27, 'https://www.offiho.com/econosillas/galeria/OHV-58alto.jpg', 1, 0, 1),
('OHV-121','Silla de visita OHV-121 tapizada en tela con base de acero pintado.', 1094.40, 27, 'https://www.offiho.com/econosillas/galeria/OHV-121.jpg', 1, 0, 1),
('Eco Visita','Silla económica para visita, estructura metálica con asiento acolchado.', 785.70, 27, 'https://www.offiho.com/econosillas/galeria/Eco-visita.jpg', 1, 0, 1),
('Iso sin brazos','Silla ISO sin brazos, ideal para salas de espera o capacitación.', 1188.90, 27, 'https://www.offiho.com/econosillas/galeria/Iso-sin-brazos.jpg', 1, 0, 1),
('Iso con brazos','Silla ISO con brazos y estructura tubular negra, tapizada en tela.', 1430.10, 27, 'https://www.offiho.com/econosillas/galeria/Iso-con-brazos.jpg', 1, 0, 1),
('Novaiso sin brazos','Silla NOVA ISO sin brazos con respaldo ventilado y asiento tapizado.', 943.20, 27, 'https://www.offiho.com/econosillas/galeria/Novaiso-sin-brazos.jpg', 1, 0, 1),
('Banca Elíptico','Banca metálica elíptica de tres plazas, asiento perforado en acero.', 1094.40, 27, 'https://www.offiho.com/econosillas/galeria/banca-ellitico.jpg', 1, 0, 1),
('Novaiso con brazos','Silla NOVA ISO con brazos, asiento tapizado y estructura de acero.', 1188.90, 27, 'https://www.offiho.com/econosillas/galeria/Novaiso-con-brazos.jpg', 1, 0, 1),
('OHV-14','Silla de visita OHV-14 con tapizado de vinil y base metálica.', 1094.40, 27, 'https://www.offiho.com/econosillas/galeria/OHV-14.jpg', 1, 0, 1),
('OHV-67','Silla de visita OHV-67 roja con base cromada, diseño moderno.', 2140.20, 27, 'https://www.offiho.com/econosillas/galeria/OHV-67rojo.jpg', 1, 0, 1),
('Elefante','Silla infantil modelo Elefante, fabricada en plástico resistente.', 629.10, 27, 'https://www.offiho.com/econosillas/galeria/Elefante.jpg', 1, 0, 1),
('OHV-124','Silla de visita OHV-124 con estructura cromada y asiento de vinil.', 1006.20, 27, 'https://www.offiho.com/econosillas/galeria/OHV-124.jpg', 1, 0, 1),
('OHV-7067D','Silla de visita OHV-7067D con tapizado en tela y base metálica.', 1094.40, 27, 'https://www.offiho.com/econosillas/galeria/OHV-7067D.jpg', 1, 0, 1),
('OHV-7067F','Silla de visita OHV-7067F con respaldo medio y asiento acolchado.', 1094.40, 27, 'https://www.offiho.com/econosillas/galeria/OHV-7067F.jpg', 1, 0, 1),
('OHV-61','Silla de visita OHV-61 con estructura metálica color negro.', 1094.40, 27, 'https://www.offiho.com/econosillas/galeria/OHV-61.jpg', 1, 0, 1),
('OHV-62','Silla de visita OHV-62 tapizada en vinil, base cromada.', 1006.20, 27, 'https://www.offiho.com/econosillas/galeria/OHV-62.jpg', 1, 0, 1),
('OHV-64','Silla de visita OHV-64 con tapizado de vinil negro y base metálica.', 1094.40, 27, 'https://www.offiho.com/econosillas/galeria/OHV-64.jpg', 1, 0, 1),
('OHV-69','Silla de visita OHV-69 moderna con asiento de polipropileno.', 1094.40, 27, 'https://www.offiho.com/econosillas/galeria/OHV-69.jpg', 1, 0, 1),
('OHV-117','Silla de visita OHV-117 de diseño ergonómico con respaldo medio.', 1094.40, 27, 'https://www.offiho.com/econosillas/galeria/OHV-117.jpg', 1, 0, 1),
('OHV-119','Silla de visita OHV-119 tapizada en vinil negro, base cromada.', 1006.20, 27, 'https://www.offiho.com/econosillas/galeria/OHV-119.jpg', 1, 0, 1),
('OHV-118','Silla de visita OHV-118 con estructura metálica y asiento tapizado.', 1094.40, 27, 'https://www.offiho.com/econosillas/galeria/OHV-118.jpg', 1, 0, 1),
('Festina','Silla económica Festina con respaldo medio y tapizado duradero.', 1006.20, 27, 'https://www.offiho.com/econosillas/galeria/festina.jpg', 1, 0, 1),
('Versalles','Silla económica Versalles con base fija y tapizado acolchado.', 1006.20, 27, 'https://www.offiho.com/econosillas/galeria/versalles.jpg', 1, 0, 1),
('Kit Cajero','Silla de cajero ajustable, base metálica con aro reposapiés.', 2202.30, 27, 'https://www.offiho.com/econosillas/galeria/kit-cajero.jpg', 1, 0, 1),
('Brazo 40','Brazo metálico tipo 40 compatible con sillas ECO.', 785.70, 27, 'https://www.offiho.com/econosillas/galeria/brazo-40.jpg', 1, 0, 1),
('Brazo 06','Brazo plástico tipo 06 ergonómico, adaptable a sillas económicas.', 1094.40, 27, 'https://www.offiho.com/econosillas/galeria/brazo-06.jpg', 1, 0, 1),
('OHP-2502','Silla alta OHP-2502 para mostrador o laboratorio, tapizada en vinil.', 2202.30, 27, 'https://www.offiho.com/econosillas/galeria/OHP-2502.jpg', 1, 0, 1);

-- BANCAS DE ESPERA (categoria_id: 7 - Subcategoría de Sillería)
INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES
('OHR-310-3P','Banca de espera de 3 plazas modelo Kyos OHR-310, estructura metálica resistente y asiento acolchado.', 5036.40, 7, 'https://www.offiho.com/bancas/galeria/OHR-310-3P.jpg', 1, 0, 1),
('OHR-2700-3P','Banca Innova OHR-2700 de 3 plazas, diseño moderno con respaldo curvo y tapizado de alta resistencia.', 5036.40, 7, 'https://www.offiho.com/bancas/galeria/OHR-2700-3P.jpg', 1, 0, 1),
('OHR-2200-3P','Banca tipo Ellítico OHR-2200 de 3 plazas, asiento continuo con soporte estructural robusto.', 4053.60, 7, 'https://www.offiho.com/bancas/galeria/OHR-2200-3P.jpg', 1, 0, 1),
('OHR-2400-3P','Banca tipo Ellítico Net OHR-2400 de 3 plazas, diseño ventilado y acabado metálico cromado.', 4053.60, 7, 'https://www.offiho.com/bancas/galeria/OHR-2400-3P.jpg', 1, 0, 1),
('OHR-2800-3P','Banca OHR-2800 de 3 plazas con base cromada cruzada, tapicería premium y respaldo envolvente.', 6295.50, 7, 'https://www.offiho.com/bancas/galeria/OHR-2800-3Pcr.jpg', 1, 0, 1);

-- MOBILIARIO ESCOLAR (categoria_id: 8 - Subcategoría de Sillería)
INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES
('OHP-2100','Silla escolar OHP-2100 con asiento y respaldo de polipropileno reforzado, ideal para aulas activas.', 1762.20, 8, 'https://www.offiho.com/escolar/galeria/OHP-2100.jpg', 1, 0, 1),
('OHP-325CR','Silla escolar OHP-325CR con estructura cromada y asiento moldeado para uso prolongado.', 2140.20, 8, 'https://www.offiho.com/escolar/galeria/OHP-325cr.jpg', 1, 0, 1),
('OHP-86CR','Silla tipo estudio OHP-86CR con diseño ergonómico y patas cromadas resistentes.', 2140.20, 8, 'https://www.offiho.com/escolar/galeria/OHP-86cr.jpg', 1, 0, 1),
('OHP-102','Silla escolar OHP-102 con respaldo perforado para ventilación y asiento anatómico.', 1094.40, 8, 'https://www.offiho.com/escolar/galeria/OHP-102.jpg', 1, 0, 1),
('OHP-2320','Silla OHP-2320 de asiento amplio y estructura reforzada para uso escolar rudo.', 1762.20, 8, 'https://www.offiho.com/escolar/galeria/OHP-2320.jpg', 1, 0, 1),
('OHP-2300','Silla escolar OHP-2300 con respaldo en malla y estructura metálica durable.', 2519.10, 8, 'https://www.offiho.com/escolar/galeria/OHP-2300.jpg', 1, 0, 1),
('OHP-2307','Silla escolar OHP-2307 con asiento tapizado y respaldo ergonómico para confort prolongado.', 2769.30, 8, 'https://www.offiho.com/escolar/galeria/OHP-2307.jpg', 1, 0, 1),
('ST-02','Silla escolar ST-02 con estructura metálica tubular y asiento de polipropileno.', 1768.50, 8, 'https://grupoalbar.com/wp-content/uploads/2023/06/st-02-550x550.jpg', 1, 0, 1),
('ST-01','Silla escolar ST-01 apilable, ligera y resistente, ideal para aulas múltiples.', 1768.50, 8, 'https://grupoalbar.com/wp-content/uploads/2023/06/st-01-34-550x550.jpg', 1, 0, 1),
('PL-13','Pupitre escolar PL-13 con superficie de melamina y asiento integrado.', 1231.20, 8, 'https://grupoalbar.com/wp-content/uploads/2023/06/pl-13-34-550x550.jpg', 1, 0, 1),
('PL-15','Pupitre escolar PL-15 color negro con estructura metálica y cubierta plástica.', 1098.00, 8, 'https://grupoalbar.com/wp-content/uploads/2023/06/pl-15-34-ne-550x550.jpg', 1, 0, 1),
('PL-17','Pupitre escolar PL-17 con base cromada y asiento ergonómico.', 593.10, 8, 'https://grupoalbar.com/wp-content/uploads/2023/06/pl-17-34-550x550.jpg', 1, 0, 1),
('LAB-34','Mesa de laboratorio LAB-34 con superficie resistente a químicos y estructura metálica.', 1215.00, 8, 'https://grupoalbar.com/wp-content/uploads/2023/06/lab-34-550x550.jpg', 1, 0, 1),
('PL-18','Pupitre escolar PL-18 con estructura metálica reforzada, color natural.', 576.90, 8, 'https://grupoalbar.com/wp-content/uploads/2024/01/pl-18-34-550x550.jpg', 1, 0, 1),
('SS-30','Silla tipo laboratorio SS-30, diseño compacto con base de acero cromado.', 1593.90, 8, 'https://grupoalbar.com/wp-content/uploads/2024/01/ss-30-34-550x550.jpg', 1, 0, 1),
('SP-20','Silla para prácticas SP-20 con respaldo ergonómico y altura ajustable.', 1593.90, 8, 'https://grupoalbar.com/wp-content/uploads/2024/01/sp-20-34-550x550.jpg', 1, 0, 1),
('SK-10','Banco escolar SK-10 de asiento plástico reforzado y base tubular.', 1208.70, 8, 'https://grupoalbar.com/wp-content/uploads/2024/01/sk-10-34-550x550.jpg', 1, 0, 1),
('AB-807M','Silla escolar AB-807M con estructura cromada y asiento tapizado.', 1050.30, 8, 'https://grupoalbar.com/wp-content/uploads/2024/01/ab-807m-550x550.jpg', 1, 0, 1),
('PL-11','Pupitre escolar PL-11 con paleta lateral abatible y base metálica.', 1846.80, 8, 'https://grupoalbar.com/wp-content/uploads/2024/01/pl-11-34-550x550.jpg', 1, 0, 1),
('PL-820','Pupitre escolar PL-820 con asiento ergonómico de polipropileno.', 2407.50, 8, 'https://grupoalbar.com/wp-content/uploads/2024/01/pl-820-550x550.jpg', 1, 0, 1),
('PL-825','Pupitre escolar PL-825 con cubierta melamínica y estructura cromada.', 1967.40, 8, 'https://grupoalbar.com/wp-content/uploads/2024/01/pl-825-34-550x550.jpg', 1, 0, 1),
('AB-800','Silla escolar AB-800 apilable, ideal para aulas múltiples.', 2143.80, 8, 'https://grupoalbar.com/wp-content/uploads/2024/01/ab-800-550x550.jpg', 1, 0, 1),
('AB-800PT','Silla escolar AB-800PT con patas tubulares y asiento perforado.', 2308.50, 8, 'https://grupoalbar.com/wp-content/uploads/2024/01/ab-800pt-550x550.jpg', 1, 0, 1),
('AB-810M','Silla escolar AB-810M reforzada, tapizado de vinil y estructura metálica.', 2572.20, 8, 'https://grupoalbar.com/wp-content/uploads/2024/01/ab-810m-550x550.jpg', 1, 0, 1),
('AB-800P','Silla escolar AB-800P con asiento plástico y estructura ligera.', 1852.20, 8, 'https://grupoalbar.com/wp-content/uploads/2024/01/ab-800p-550x550.jpg', 1, 0, 1),
('AB-805','Silla escolar AB-805 con base cromada y respaldo perforado.', 2154.60, 8, 'https://grupoalbar.com/wp-content/uploads/2024/01/ab-805-550x550.jpg', 1, 0, 1),
('PS-40','Mesa escolar PS-40 con cubierta de melamina y estructura metálica tubular.', 2176.20, 8, 'https://grupoalbar.com/wp-content/uploads/2024/01/ps-40-550x550.jpg', 1, 0, 1),
('M-210','Mesa escolar M-210 tipo laboratorio con cubierta de resina resistente.', 1962.00, 8, 'https://grupoalbar.com/wp-content/uploads/2024/01/m-210-34-550x550.jpg', 1, 0, 1),
('MR-210','Mesa escolar MR-210 Kolors con cubierta en acabado brillante y patas metálicas.', 2504.70, 8, 'https://grupoalbar.com/wp-content/uploads/2024/01/mr-210-34-kolors-550x550.jpg', 1, 0, 1),
('MT-220','Mesa escolar MT-220 con estructura tubular y cubierta laminada.', 2394.90, 8, 'https://grupoalbar.com/wp-content/uploads/2024/01/mt-220-34-550x550.jpg', 1, 0, 1),
('MT-220 Kolors','Mesa escolar MT-220 Kolors, disponible en colores vibrantes.', 2394.90, 8, 'https://grupoalbar.com/wp-content/uploads/2024/01/mt-220-34-kolors-550x550.jpg', 1, 0, 1),
('MC-200','Mesa escolar MC-200 de acero tubular y cubierta de melamina.', 1962.00, 8, 'https://grupoalbar.com/wp-content/uploads/2024/01/mc-200-34-550x550.jpg', 1, 0, 1),
('MC-200 Kolors','Mesa escolar MC-200 Kolors con cubierta de color brillante.', 1962.00, 8, 'https://grupoalbar.com/wp-content/uploads/2024/01/mc-200-34-kolors-550x550.jpg', 1, 0, 1),
('PL-30','Pupitre escolar PL-30 ergonómico con asiento plástico y base metálica.', 1731.60, 8, 'https://grupoalbar.com/wp-content/uploads/2024/06/pl-30-34-550x550.jpg', 1, 0, 1),
('PL-300','Pupitre escolar PL-300 moderno con cubierta laminada color haya.', 2753.10, 8, 'https://grupoalbar.com/wp-content/uploads/2024/06/pl-300-34-550x550.jpg', 1, 0, 1),
('PL-32','Pupitre escolar PL-32 con base cromada y paleta de escritura abatible.', 2695.50, 8, 'https://grupoalbar.com/wp-content/uploads/2024/06/pl-32-550x550.jpg', 1, 0, 1),
('MS-240','Mesa escolar MS-240 tres cuartos, estructura metálica y cubierta plástica.', 1923.30, 8, 'https://grupoalbar.com/wp-content/uploads/2024/07/ms-240-%C2%B34-550x550.jpg', 1, 0, 1),
('PL-750','Pupitre escolar PL-750 con asiento ergonómico y superficie abatible.', 2066.40, 8, 'https://grupoalbar.com/wp-content/uploads/2025/04/pl-750-34-1-550x550.jpg', 1, 0, 1),
('PL-120','Pupitre escolar PL-120 color negro, asiento plástico y base metálica.', 2363.40, 8, 'https://grupoalbar.com/wp-content/uploads/2025/04/pl-120-ne-34-1-550x550.jpg', 1, 0, 1),
('A-600 NPT','Banco escolar A-600 NPT con aro reposapiés, tapizado en vinil.', 2111.40, 8, 'https://grupoalbar.com/wp-content/uploads/2025/05/a-600-npt-34-550x550.jpg', 1, 0, 1),
('A-600 NP','Banco escolar A-600 NP de estructura metálica y asiento plástico.', 1802.70, 8, 'https://grupoalbar.com/wp-content/uploads/2025/05/a-600-np-34-550x550.jpg', 1, 0, 1);

-- VISITANTES (ahora en Visita - categoria_id: 2)
INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES
('OHV-13','Silla para visitantes modelo OHV-13, diseño moderno con estructura metálica resistente.', 1094.40, 2, 'https://www.offiho.com/visitantes-interior/galeria/OHV-13.jpg', 1, 0, 1),
('OHV-37','Silla para visitantes OHV-37, tapizado ergonómico y base tubular cromada.', 2140.20, 2, 'https://www.offiho.com/visitantes-interior/galeria/OHV-37.jpg', 1, 0, 1),
('OHV-47','Silla visitante OHV-47 con respaldo curvo y asiento acolchado para mayor confort.', 1094.40, 2, 'https://www.offiho.com/visitantes-interior/galeria/OHV-47.jpg', 1, 0, 1),
('OHV-20','Silla de visita OHV-20 con diseño compacto y acabado profesional.', 1094.40, 2, 'https://www.offiho.com/visitantes-interior/galeria/OHV-20.jpg', 1, 0, 1),
('OHV-86CR','Silla para visitantes OHV-86CR con estructura cromada y asiento moldeado.', 2140.20, 2, 'https://www.offiho.com/visitantes-interior/galeria/OHV-86cr.jpg', 1, 0, 1),
('OHV-50','Silla de recepción OHV-50 con respaldo ventilado y base metálica estable.', 1094.40, 2, 'https://www.offiho.com/visitantes-interior/galeria/OHV-50.jpg', 1, 0, 1),
('OHV-7216','Silla OHV-7216 con diseño contemporáneo para áreas de espera y recepción.', 1094.40, 2, 'https://www.offiho.com/visitantes-interior/galeria/OHV-7216.jpg', 1, 0, 1),
('OHV-138','Silla para visitantes OHV-138 con asiento tapizado y base cromada.', 2140.20, 2, 'https://www.offiho.com/visitantes-interior/galeria/OHV-138.jpg', 1, 0, 1),
('OHV-115','Silla OHV-115 en color negro, estructura metálica y respaldo ergonómico.', 1094.40, 2, 'https://www.offiho.com/visitantes-interior/galeria/OHV-115negro.jpg', 1, 0, 1),
('OHV-11','Silla visitante OHV-11 con estructura tubular y diseño funcional.', 1094.40, 2, 'https://www.offiho.com/visitantes-interior/galeria/OHV-11.jpg', 1, 0, 1),
('OHV-315','Silla OHV-315 con respaldo flexible y asiento ergonómico para áreas comunes.', 1094.40, 2, 'https://www.offiho.com/visitantes-interior/galeria/OHV-315.jpg', 1, 0, 1),
('OHV-102','Silla de visita OHV-102 con diseño clásico y base metálica.', 1094.40, 2, 'https://www.offiho.com/visitantes-interior/galeria/OHV-102.jpg', 1, 0, 1),
('OHV-66','Silla visitante OHV-66 de estructura metálica con asiento tapizado.', 1094.40, 2, 'https://www.offiho.com/visitantes-interior/galeria/OHV-66.jpg', 1, 0, 1),
('OHV-2700','Silla OHV-2700 con soporte ergonómico y acabado profesional.', 1094.40, 2, 'https://www.offiho.com/visitantes-interior/galeria/OHV-2700.jpg', 1, 0, 1),
('OHV-3000','Silla visitante OHV-3000 con diseño moderno y patas cromadas.', 2140.20, 2, 'https://www.offiho.com/visitantes-interior/galeria/OHV-3000.jpg', 1, 0, 1),
('OHV-2200','Silla OHV-2200 de líneas contemporáneas y materiales resistentes.', 1094.40, 2, 'https://www.offiho.com/visitantes-interior/galeria/OHV-2200.jpg', 1, 0, 1),
('OHV-2400','Silla visitante OHV-2400 ideal para salas de espera, diseño ergonómico.', 1094.40, 2, 'https://www.offiho.com/visitantes-interior/galeria/OHV-2400.jpg', 1, 0, 1),
('OHV-133','Silla para visitantes OHV-133 con respaldo perforado y base metálica cromada.', 2140.20, 2, 'https://www.offiho.com/visitantes-interior/galeria/OHV-133.jpg', 1, 0, 1),
('OHV-136','Silla OHV-136 con tapizado elegante y diseño contemporáneo para oficinas o recepciones.', 1094.40, 2, 'https://www.offiho.com/visitantes-interior/galeria/OHV-136.jpg', 1, 0, 1);

INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES
('OHV-73','Silla para exteriores OHV-73, resistente a la intemperie y apilable, ideal para visitas al aire libre.', 1006.20, 2, 'https://www.offiho.com/visitantes-exterior/galeria/OHV-73.jpg', 1, 0, 1),
('OHV-71','Silla de visita OHV-71 con diseño ergonómico y estructura plástica reforzada para exteriores.', 1006.20, 2, 'https://www.offiho.com/visitantes-exterior/galeria/OHV-71.jpg', 1, 0, 1),
('OHV-92','Silla OHV-92 con marco metálico pintado y asiento de polipropileno durable, apta para uso en exteriores.', 1006.20, 2, 'https://www.offiho.com/visitantes-exterior/galeria/OHV-92.jpg', 1, 0, 1),
('OHV-97','Silla visitante OHV-97 de exterior con diseño ligero, apilable y fácil de limpiar.', 1006.20, 2, 'https://www.offiho.com/visitantes-exterior/galeria/OHV-97.jpg', 1, 0, 1),
('OHV-91','Silla de exterior OHV-91 fabricada con materiales resistentes al sol y la humedad.', 1006.20, 2, 'https://www.offiho.com/visitantes-exterior/galeria/OHV-91.jpg', 1, 0, 1),
('OHV-7201','Silla OHV-7201 apilable y moderna, ideal para áreas sociales, terrazas y patios.', 1094.40, 2, 'https://www.offiho.com/visitantes-exterior/galeria/OHV-7201.jpg', 1, 0, 1),
('OHV-93','Silla OHV-93 con acabado mate y base estable, perfecta para reuniones o visitas al aire libre.', 1094.40, 2, 'https://www.offiho.com/visitantes-exterior/galeria/OHV-93.jpg', 1, 0, 1),
('OHV-59','Silla OHV-59 con estructura tubular galvanizada y asiento plástico resistente al clima.', 1094.40, 2, 'https://www.offiho.com/visitantes-exterior/galeria/OHV-59.jpg', 1, 0, 1),
('OHV-137','Silla visitante OHV-137 para exterior, diseño moderno con respaldo ventilado.', 1006.20, 2, 'https://www.offiho.com/visitantes-exterior/galeria/OHV-137.jpg', 1, 0, 1),
('OHV-19','Silla OHV-19 de exterior, resistente y funcional, ideal para visitas en espacios abiertos.', 1006.20, 2, 'https://www.offiho.com/visitantes-exterior/galeria/OHV-19.jpg', 1, 0, 1),
('OHV-18','Silla OHV-18 con estructura metálica reforzada y acabado resistente a la intemperie.', 1006.20, 2, 'https://www.offiho.com/visitantes-exterior/galeria/OHV-18.jpg', 1, 0, 1),
('OHV-7220B','Silla OHV-7220B de diseño ergonómico, fabricada con materiales aptos para exteriores.', 1006.20, 2, 'https://www.offiho.com/visitantes-exterior/galeria/OHV-7220B.jpg', 1, 0, 1),
('OHV-7029-CC','Silla OHV-7029-CC con asiento curvo y estructura de aluminio anodizado, ligera y resistente.', 1094.40, 2, 'https://www.offiho.com/visitantes-exterior/galeria/OHV-7029-CC.jpg', 1, 0, 1),
('OHV-7028','Silla visitante OHV-7028, apilable y resistente, ideal para terrazas y jardines.', 1094.40, 2, 'https://www.offiho.com/visitantes-exterior/galeria/OHV-7028.jpg', 1, 0, 1),
('OHV-7203A','Silla OHV-7203A de estilo contemporáneo, diseñada para uso prolongado en exteriores.', 1006.20, 2, 'https://www.offiho.com/visitantes-exterior/galeria/OHV-7203A.jpg', 1, 0, 1),
('OHV-74','Silla OHV-74 con respaldo ergonómico y estructura plástica duradera.', 1094.40, 2, 'https://www.offiho.com/visitantes-exterior/galeria/OHV-74.jpg', 1, 0, 1),
('OHV-81','Silla visitante OHV-81, liviana, apilable y resistente a los rayos UV.', 1006.20, 2, 'https://www.offiho.com/visitantes-exterior/galeria/OHV-81.jpg', 1, 0, 1),
('OHV-7031','Silla OHV-7031 con diseño moderno, respaldo texturizado y estructura robusta.', 1094.40, 2, 'https://www.offiho.com/visitantes-exterior/galeria/OHV-7031.jpg', 1, 0, 1),
('OHV-7030','Silla OHV-7030 con estructura reforzada, ideal para jardines, patios o terrazas.', 1094.40, 2, 'https://www.offiho.com/visitantes-exterior/galeria/OHV-7030.jpg', 1, 0, 1);

-- ESCRITORIOS BÁSICOS (categoria_id: 23 - Subcategoría de Escritorios)
INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES
('ES 120','Escritorio básico modelo ES-120 fabricado en melamina de 28 mm con faldón de 16 mm. Ideal para oficinas compactas.', 4958.10, 23, 'https://www.zamofi.com/wp-content/uploads/2017/06/ES-120-1-300x169.png', 1, 0, 1),
('ES 140','Escritorio básico modelo ES-140 fabricado en melamina de 28 mm con faldón de 16 mm. Diseño funcional y resistente.', 5811.30, 23, 'https://www.zamofi.com/wp-content/uploads/2017/06/ES-140-300x169.png', 1, 0, 1),
('ES 182','Escritorio básico modelo ES-182 en melamina de 28 mm con faldón inferior. Superficie amplia y estructura sólida.', 9753.30, 23, 'https://www.zamofi.com/wp-content/uploads/2017/06/ES-182-1-300x169.png', 1, 0, 1),
('T 001','Escritorio metálico modelo T-001 con base en acero y cubierta en melamina 28 mm. Estilo moderno y duradero.', 5059.80, 23, 'https://www.zamofi.com/wp-content/uploads/2017/06/T-001.1-300x169.png', 1, 0, 1),
('T 001 Alt','Variante del escritorio T-001 con acabado alternativo. Base metálica y cubierta en melamina 28 mm.', 5059.80, 23, 'https://www.zamofi.com/wp-content/uploads/2017/06/T-001-300x169.png', 1, 0, 1),
('ES Grapa','Escritorio modelo Grapa con estructura metálica tipo grapa y cubierta de melamina 28 mm. Ideal para oficinas modernas.', 8188.20, 23, 'https://www.zamofi.com/wp-content/uploads/2017/06/ESCRITORIO-GRAPA-300x169.png', 1, 0, 1),
('E Euro','Escritorio modelo Euro con faldón frontal y cubierta de melamina. Diseño sobrio para espacios ejecutivos básicos.', 9448.20, 23, 'https://www.zamofi.com/wp-content/uploads/2017/06/1-ESCRITORIO-EURO-300x203.jpg', 1, 0, 1),
('E 003','Escritorio modelo E-003 con canaleta para cableado y estructura reforzada. Ideal para trabajo diario.', 5100.30, 23, 'https://www.zamofi.com/wp-content/uploads/2017/06/E-003-300x169.png', 1, 0, 1),
('ETG 1670','Escritorio gerencial modelo ETG-1670 con amplia superficie en melamina y faldón metálico inferior.', 18245.70, 23, 'https://www.zamofi.com/wp-content/uploads/2017/06/ETG-1670-1-300x169.png', 1, 0, 1),
('D 001','Escritorio Delta D-001 con estructura metálica en forma de marco y cubierta de melamina de 28 mm.', 13552.20, 23, 'https://www.zamofi.com/wp-content/uploads/2017/06/D-001.1-300x169.png', 1, 0, 1),
('P 001','Escritorio Prisma P-001 con canaleta porta cables y acabado en melamina de alta resistencia.', 4958.10, 23, 'https://www.zamofi.com/wp-content/uploads/2017/06/P-001.1-300x169.png', 1, 0, 1),
('EM 16','Escritorio modular modelo EM-16, estructura de melamina con diseño funcional y resistente.', 4958.10, 23, 'https://www.zamofi.com/wp-content/uploads/2017/06/EM-16.01-300x169.png', 1, 0, 1),
('LT 1','Escritorio en L de 1 módulo, ideal para espacios reducidos. Estructura metálica y cubierta de melamina.', 5059.80, 23, 'https://www.zamofi.com/wp-content/uploads/2017/06/LT-1-300x169.jpg', 1, 0, 1),
('LT 2','Escritorio en L de 2 módulos, estructura reforzada en melamina. Perfecto para oficinas amplias.', 5607.90, 23, 'https://www.zamofi.com/wp-content/uploads/2017/06/LT-2-300x169.jpg', 1, 0, 1),
('LT 3','Escritorio en L triple, sistema modular adaptable con cubierta en melamina de 28 mm.', 6318.90, 23, 'https://www.zamofi.com/wp-content/uploads/2017/06/LT-3-300x169.jpg', 1, 0, 1),
('LT 4','Escritorio en L cuádruple, diseño corporativo ideal para estaciones de trabajo múltiples.', 19079.10, 23, 'https://www.zamofi.com/wp-content/uploads/2017/06/LT-4-300x169.jpg', 1, 0, 1);

-- ESCRITORIOS OPERATIVOS EN L (categoria_id: 24 - Subcategoría de Escritorios)
INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES
('EURO 120','Estación secretarial EURO 120 en forma de L. Cubierta en melamina de 28 mm y faldón de 16 mm. Diseño compacto y funcional.', 12902.40, 24, 'https://www.zamofi.com/wp-content/uploads/2017/07/EURO-120.png', 1, 0, 1),
('EURO 120 Alt','Variante EURO 120 con acabado alternativo. Ideal para oficinas pequeñas con superficie auxiliar.', 13146.30, 24, 'https://www.zamofi.com/wp-content/uploads/2017/07/EURO-120.1.png', 1, 0, 1),
('EURO 140','Estación secretarial EURO 140 en L. Cubierta en melamina de 28 mm y estructura reforzada. Amplia zona de trabajo.', 14385.60, 24, 'https://www.zamofi.com/wp-content/uploads/2017/07/EURO-140.png', 1, 0, 1),
('EURO 140 Alt','Variante EURO 140 con acabado adicional. Estructura metálica y faldón frontal.', 14607.00, 24, 'https://www.zamofi.com/wp-content/uploads/2017/07/EURO-140.1.png', 1, 0, 1),
('EURO 160','Estación secretarial EURO 160. Cubierta principal y retorno lateral en melamina de 28 mm. Perfecta para áreas operativas.', 17738.10, 24, 'https://www.zamofi.com/wp-content/uploads/2017/07/EURO-160.png', 1, 0, 1),
('EURO 160 Alt','Versión alternativa EURO 160. Mismo diseño en L con variación de tono y acabado.', 19079.10, 24, 'https://www.zamofi.com/wp-content/uploads/2017/07/EURO-160.1.png', 1, 0, 1),
('CSE 160','Estación secretarial CSE 160 en forma de L. Cubierta de melamina y faldón metálico inferior.', 24706.80, 24, 'https://www.zamofi.com/wp-content/uploads/2017/07/CSE-160.png', 1, 0, 1),
('CSE 160 Alt','Variante CSE 160 con acabados premium. Diseño ergonómico con retorno lateral.', 24706.80, 24, 'https://www.zamofi.com/wp-content/uploads/2017/07/CSE-160-1.png', 1, 0, 1),
('ROCCO','Escritorio ROCCO en L. Cubierta flotante en melamina 28 mm y estructura metálica robusta.', 12282.30, 24, 'https://www.zamofi.com/wp-content/uploads/2017/07/ROCCO.png', 1, 0, 1),
('ROCCO Alt','Variante del escritorio ROCCO con color alternativo. Ideal para espacios modernos.', 12282.30, 24, 'https://www.zamofi.com/wp-content/uploads/2017/07/ROCCO.1.png', 1, 0, 1),
('CMF 160','Estación secretarial CMF 160. Estructura modular metálica y cubierta de melamina. Retorno lateral opcional.', 12282.30, 24, 'https://www.zamofi.com/wp-content/uploads/2017/07/CMF-160.1.png', 1, 0, 1),
('CMF 160 Alt','Versión alternativa CMF 160 con acabado adicional en melamina. Ideal para estaciones de trabajo dobles.', 12282.30, 24, 'https://www.zamofi.com/wp-content/uploads/2017/07/CMF-160.2.png', 1, 0, 1),
('P 002','Estación secretarial P-002 en L. Diseño contemporáneo con canaleta para cableado.', 12902.40, 24, 'https://www.zamofi.com/wp-content/uploads/2017/07/P-002.1.png', 1, 0, 1),
('P 003','Estación secretarial P-003 en L. Cubierta amplia y faldón metálico. Ideal para áreas operativas.', 17738.10, 24, 'https://www.zamofi.com/wp-content/uploads/2017/07/P-003.1.png', 1, 0, 1),
('D 004','Estación secretarial D-004. Cubierta en melamina con retorno lateral. Diseño práctico y ergonómico.', 27632.70, 24, 'https://www.zamofi.com/wp-content/uploads/2017/07/D-004.png', 1, 0, 1),
('D 005','Estación secretarial D-005. Diseño en L con estructura metálica y faldón reforzado.', 27632.70, 24, 'https://www.zamofi.com/wp-content/uploads/2017/07/D-005.png', 1, 0, 1),
('COG 160','Estación secretarial COG 160. Superficie amplia, estructura en acero tubular y acabado melamínico.', 12282.30, 24, 'https://www.zamofi.com/wp-content/uploads/2017/07/COG-160.png', 1, 0, 1),
('COG 160 Alt','Variante COG 160 con acabado alternativo. Retorno lateral incluido.', 12282.30, 24, 'https://www.zamofi.com/wp-content/uploads/2017/07/COG-160-1.png', 1, 0, 1);

-- ESCRITORIOS EJECUTIVOS (categoria_id: 26 - Subcategoría de Escritorios)
INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES
('CET 180','Escritorio ejecutivo CET 180. Cubierta en melamina de 28 mm y estructura metálica. Diseño elegante y funcional.', 42057.90, 26, 'https://www.zamofi.com/wp-content/uploads/2017/07/CET-180-3.png', 1, 0, 1),
('MURI','Escritorio ejecutivo MURI. Diseño moderno con cubierta flotante y estructura metálica.', 43744.50, 26, 'https://www.zamofi.com/wp-content/uploads/2017/07/MURI.png', 1, 0, 1),
('MURI Alt 1','Variante del escritorio MURI con acabado alternativo. Estilo contemporáneo y sofisticado.', 43744.50, 26, 'https://www.zamofi.com/wp-content/uploads/2017/07/MURI-1.png', 1, 0, 1),
('MURI Alt 2','Versión adicional MURI con detalles metálicos y retorno lateral.', 43744.50, 26, 'https://www.zamofi.com/wp-content/uploads/2017/07/MURI-2.png', 1, 0, 1),
('CEE 160','Escritorio ejecutivo CEE 160. Amplia cubierta en melamina 28 mm con faldón metálico inferior.', 28242.00, 26, 'https://www.zamofi.com/wp-content/uploads/2017/07/CEE-160.png', 1, 0, 1),
('CEE 160 Alt 1','Variante CEE 160 con acabado alternativo. Ideal para oficinas ejecutivas modernas.', 28242.00, 26, 'https://www.zamofi.com/wp-content/uploads/2017/07/CEE-160-1.png', 1, 0, 1),
('CEE 160 Alt 2','Versión adicional CEE 160 con variación de tono y faldón reforzado.', 28242.00, 26, 'https://www.zamofi.com/wp-content/uploads/2017/07/CEE-160-2.png', 1, 0, 1),
('CEE 161','Escritorio ejecutivo CEE 161 con estructura metálica y superficie en melamina.', 28242.00, 26, 'https://www.zamofi.com/wp-content/uploads/2017/07/CEE-161.png', 1, 0, 1),
('CEE 161 Alt 1','Variante CEE 161 con acabado diferente. Ideal para despachos ejecutivos.', 28242.00, 26, 'https://www.zamofi.com/wp-content/uploads/2017/07/CEE-161-1.png', 1, 0, 1),
('CEE 161 Alt 2','Versión adicional CEE 161 con tono alternativo y retorno lateral.', 28242.00, 26, 'https://www.zamofi.com/wp-content/uploads/2017/07/CEE-161-2.png', 1, 0, 1),
('CE 28','Escritorio ejecutivo CE 28. Diseño con faldón inferior y estructura reforzada.', 17859.60, 26, 'https://www.zamofi.com/wp-content/uploads/2017/07/CE-28.1.png', 1, 0, 1),
('CE 28 Alt 1','Variante del CE 28 con acabado alternativo. Superficie amplia y elegante.', 17859.60, 26, 'https://www.zamofi.com/wp-content/uploads/2017/07/CE-28.2.png', 1, 0, 1),
('CE 28 Alt 2','Versión adicional CE 28 con detalles modernos en melamina.', 17859.60, 26, 'https://www.zamofi.com/wp-content/uploads/2017/07/CE-28.3.png', 1, 0, 1),
('CE 180','Escritorio ejecutivo CE 180. Superficie amplia y faldón metálico. Ideal para oficinas de dirección.', 26820.00, 26, 'https://www.zamofi.com/wp-content/uploads/2017/07/CE-180.1.png', 1, 0, 1),
('CE 180 Alt 1','Variante CE 180 con acabado premium. Estructura metálica reforzada.', 26820.00, 26, 'https://www.zamofi.com/wp-content/uploads/2017/07/CE-180.2.png', 1, 0, 1),
('CE 180 Alt 2','Versión adicional CE 180 con tono alternativo y diseño elegante.', 26820.00, 26, 'https://www.zamofi.com/wp-content/uploads/2017/07/CE-180.3.png', 1, 0, 1),
('CD1','Escritorio ejecutivo CD1. Cubierta en melamina 28 mm con faldón frontal. Estilo corporativo clásico.', 37121.40, 26, 'https://www.zamofi.com/wp-content/uploads/2017/07/CD1.png', 1, 0, 1),
('CD1 Alt 1','Variante del escritorio CD1 con acabado alternativo y detalles refinados.', 37121.40, 26, 'https://www.zamofi.com/wp-content/uploads/2017/07/CD1-1.png', 1, 0, 1),
('CD1 Alt 2','Versión adicional CD1 con variación de color y superficie texturizada.', 37121.40, 26, 'https://www.zamofi.com/wp-content/uploads/2017/07/CD1-2.png', 1, 0, 1);

-- =====================================================
-- PRODUCTOS METÁLICOS
-- =====================================================

-- ARCHIVEROS METÁLICOS (categoria_id: 29 - Subcategoría de Metálico)
INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES
('A 1+1','Archivero metálico 1+1, 1 cajón papelero y 1 cajón de archivo, estructura metálica resistente.', 3850.00, 29, 'Uploads/METALICO2022/ARCHIVEROS METALICOS/a 1+1.jpg', 1, 0, 1),
('A 2+1','Archivero metálico 2+1, 2 cajones papeleros y 1 cajón de archivo, correderas de extensión.', 4500.00, 29, 'Uploads/METALICO2022/ARCHIVEROS METALICOS/a 2+1.jpg', 1, 0, 1),
('AC2','Archivero metálico AC2 vertical de 2 gavetas, estructura metálica reforzada.', 4880.00, 29, 'Uploads/METALICO2022/ARCHIVEROS METALICOS/AC2.jpg', 1, 0, 1),
('AC3','Archivero metálico AC3 vertical de 3 gavetas, correderas de extensión.', 6570.00, 29, 'Uploads/METALICO2022/ARCHIVEROS METALICOS/AC3.jpg', 1, 0, 1),
('AC4','Archivero metálico AC4 vertical de 4 gavetas, estructura metálica robusta.', 7540.00, 29, 'Uploads/METALICO2022/ARCHIVEROS METALICOS/AC4.jpg', 1, 0, 1),
('ACF2','Archivero metálico ACF2 con cerradura, 2 gavetas tamaño oficio.', 5290.00, 29, 'Uploads/METALICO2022/ARCHIVEROS METALICOS/ACF2.jpg', 1, 0, 1),
('ACF3','Archivero metálico ACF3 con cerradura, 3 gavetas tamaño oficio.', 6650.00, 29, 'Uploads/METALICO2022/ARCHIVEROS METALICOS/ACF3.jpg', 1, 0, 1),
('ACF4','Archivero metálico ACF4 con cerradura, 4 gavetas tamaño oficio.', 8590.00, 29, 'Uploads/METALICO2022/ARCHIVEROS METALICOS/ACF4.jpg', 1, 0, 1),
('ACYB2','Archivero metálico ACYB2 con yugo y brazos, 2 gavetas.', 5680.00, 29, 'Uploads/METALICO2022/ARCHIVEROS METALICOS/ACYB2.jpg', 1, 0, 1),
('ACYB3G','Archivero metálico ACYB3G con yugo y brazos, 3 gavetas, modelo G.', 7230.00, 29, 'Uploads/METALICO2022/ARCHIVEROS METALICOS/ACYB3G.jpg', 1, 0, 1),
('ACYB4','Archivero metálico ACYB4 con yugo y brazos, 4 gavetas.', 8460.00, 29, 'Uploads/METALICO2022/ARCHIVEROS METALICOS/ACYB4.jpg', 1, 0, 1),
('AH2','Archivero metálico horizontal AH2 de 2 gavetas, diseño compacto.', 6860.00, 29, 'Uploads/METALICO2022/ARCHIVEROS METALICOS/AH2.jpg', 1, 0, 1),
('AH3','Archivero metálico horizontal AH3 de 3 gavetas, estructura metálica reforzada.', 8840.00, 29, 'Uploads/METALICO2022/ARCHIVEROS METALICOS/AH3.jpg', 1, 0, 1),
('AH4','Archivero metálico horizontal AH4 de 4 gavetas, amplia capacidad de almacenamiento.', 10360.00, 29, 'Uploads/METALICO2022/ARCHIVEROS METALICOS/AH4.jpg', 1, 0, 1),
('AS2','Archivero metálico AS2 vertical de 2 gavetas, correderas de extensión.', 4880.00, 29, 'Uploads/METALICO2022/ARCHIVEROS METALICOS/AS2.jpg', 1, 0, 1),
('AS3','Archivero metálico AS3 vertical de 3 gavetas, estructura metálica resistente.', 6570.00, 29, 'Uploads/METALICO2022/ARCHIVEROS METALICOS/AS3.jpg', 1, 0, 1),
('AS4','Archivero metálico AS4 vertical de 4 gavetas, diseño funcional.', 7540.00, 29, 'Uploads/METALICO2022/ARCHIVEROS METALICOS/AS4.jpg', 1, 0, 1),
('AV2','Archivero metálico vertical AV2 de 2 gavetas, estructura metálica robusta.', 4880.00, 29, 'Uploads/METALICO2022/ARCHIVEROS METALICOS/AV2.jpg', 1, 0, 1),
('AV3','Archivero metálico vertical AV3 de 3 gavetas, correderas de extensión.', 6570.00, 29, 'Uploads/METALICO2022/ARCHIVEROS METALICOS/AV3.jpg', 1, 0, 1),
('AV4','Archivero metálico vertical AV4 de 4 gavetas, amplia capacidad de almacenamiento.', 7540.00, 29, 'Uploads/METALICO2022/ARCHIVEROS METALICOS/AV4.jpg', 1, 0, 1),
('ECO 1+1','Archivero metálico económico ECO 1+1, 1 cajón papelero y 1 cajón de archivo.', 3200.00, 29, 'Uploads/METALICO2022/ARCHIVEROS METALICOS/ECO 1+1.jpg', 1, 0, 1),
('ECO 2+1','Archivero metálico económico ECO 2+1, 2 cajones papeleros y 1 cajón de archivo.', 3850.00, 29, 'Uploads/METALICO2022/ARCHIVEROS METALICOS/ECO 2+1.jpg', 1, 0, 1),
('ECO H2','Archivero metálico económico horizontal ECO H2 de 2 gavetas.', 5140.00, 29, 'Uploads/METALICO2022/ARCHIVEROS METALICOS/ECO H2.jpg', 1, 0, 1),
('ECO H3','Archivero metálico económico horizontal ECO H3 de 3 gavetas.', 6630.00, 29, 'Uploads/METALICO2022/ARCHIVEROS METALICOS/ECO H3.jpg', 1, 0, 1),
('ECO H4','Archivero metálico económico horizontal ECO H4 de 4 gavetas.', 7630.00, 29, 'Uploads/METALICO2022/ARCHIVEROS METALICOS/ECO H4.jpg', 1, 0, 1),
('ECO2','Archivero metálico económico ECO2 vertical de 2 gavetas.', 3660.00, 29, 'Uploads/METALICO2022/ARCHIVEROS METALICOS/ECO2.jpg', 1, 0, 1),
('ECO3','Archivero metálico económico ECO3 vertical de 3 gavetas.', 4930.00, 29, 'Uploads/METALICO2022/ARCHIVEROS METALICOS/ECO3.jpg', 1, 0, 1),
('ECO4','Archivero metálico económico ECO4 vertical de 4 gavetas.', 5660.00, 29, 'Uploads/METALICO2022/ARCHIVEROS METALICOS/ECO4.jpg', 1, 0, 1);

-- ANAQUELES (categoria_id: 30 - Subcategoría de Metálico)
INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES
('ANAQUEL 0.30X0.85X2.20','Anaquel metálico de 0.30x0.85x2.20 mts, poste calibre 14, entrepaño calibre 22.', 2980.00, 30, 'Uploads/METALICO2022/ANAQUELES/ANAQUEL DE .30X.85X2.20MTS POSTE CALIBRE 14, ENTREPAÑO CALIBRE 22.jpg', 1, 0, 1),
('ANAQUEL 0.30X0.92X2.20','Anaquel metálico de 0.30x0.92x2.20 mts, poste calibre 14, entrepaño calibre 22.', 3150.00, 30, 'Uploads/METALICO2022/ANAQUELES/ANAQUEL DE .30X.92X2.20MTS POSTE CALIBRE 14, ENTREPAÑO CALIBRE 22.jpg', 1, 0, 1),
('ANAQUEL 0.45X0.85X2.20','Anaquel metálico de 0.45x0.85x2.20 mts, poste calibre 14, entrepaño calibre 22.', 3380.00, 30, 'Uploads/METALICO2022/ANAQUELES/ANAQUEL DE .45X.85X2.20MTS POSTE CALIBRE 14, ENTREPAÑO CALIBRE 22.jpg', 1, 0, 1),
('ANAQUEL 0.45X0.92X2.20','Anaquel metálico de 0.45x0.92x2.20 mts, poste calibre 14, entrepaño calibre 22.', 3560.00, 30, 'Uploads/METALICO2022/ANAQUELES/ANAQUEL DE .45X.92X2.20MTS POSTE CALIBRE 14, ENTREPAÑO CALIBRE 22.jpg', 1, 0, 1),
('ANAQUEL 0.60X0.85X2.20','Anaquel metálico de 0.60x0.85x2.20 mts, poste calibre 14, entrepaño calibre 22.', 3780.00, 30, 'Uploads/METALICO2022/ANAQUELES/ANAQUEL DE .60X.85X2.20MTS POSTE CALIBRE 14, ENTREPAÑO CALIBRE 22.jpg', 1, 0, 1),
('ANAQUEL 0.60X0.92X2.20','Anaquel metálico de 0.60x0.92x2.20 mts, poste calibre 14, entrepaño calibre 22.', 3990.00, 30, 'Uploads/METALICO2022/ANAQUELES/ANAQUEL DE .60X.92X2.20MTS POSTE CALIBRE 14, ENTREPAÑO CALIBRE 22.jpg', 1, 0, 1);

-- ESCRITORIOS METÁLICOS (categoria_id: 31 - Subcategoría de Metálico)
INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES
('ECO1270 ECO1675','Escritorio metálico económico ECO1270 y ECO1675, estructura metálica resistente.', 4100.00, 31, 'Uploads/METALICO2022/ESCRITORIOS METALICOS/ECO1270 ECO1675.jpg', 1, 0, 1),
('ECO2160 ECO2180','Escritorio metálico económico ECO2160 y ECO2180, diseño funcional.', 4580.00, 31, 'Uploads/METALICO2022/ESCRITORIOS METALICOS/ECO2160 ECO2180.jpg', 1, 0, 1),
('EE160 EE180','Escritorio metálico EE160 y EE180, estructura metálica robusta.', 5320.00, 31, 'Uploads/METALICO2022/ESCRITORIOS METALICOS/EE160 EE180.jpg', 1, 0, 1),
('EM120 EM140','Escritorio metálico EM120 y EM140, diseño compacto y funcional.', 4100.00, 31, 'Uploads/METALICO2022/ESCRITORIOS METALICOS/EM120 EM140.jpg', 1, 0, 1),
('EMC120','Escritorio metálico EMC120 con cubierta, estructura metálica reforzada.', 4350.00, 31, 'Uploads/METALICO2022/ESCRITORIOS METALICOS/EMC120.jpg', 1, 0, 1),
('EMC150','Escritorio metálico EMC150 con cubierta, amplia superficie de trabajo.', 4820.00, 31, 'Uploads/METALICO2022/ESCRITORIOS METALICOS/EMC150.jpg', 1, 0, 1),
('EMN120','Escritorio metálico EMN120, estructura metálica resistente.', 3850.00, 31, 'Uploads/METALICO2022/ESCRITORIOS METALICOS/EMN120.jpg', 1, 0, 1),
('EMN150','Escritorio metálico EMN150, diseño funcional y robusto.', 4220.00, 31, 'Uploads/METALICO2022/ESCRITORIOS METALICOS/EMN150.jpg', 1, 0, 1),
('ES160','Escritorio metálico ES160, estructura metálica reforzada.', 4760.00, 31, 'Uploads/METALICO2022/ESCRITORIOS METALICOS/ES160 .jpg', 1, 0, 1),
('ES70','Escritorio metálico ES70, diseño compacto ideal para espacios reducidos.', 3100.00, 31, 'Uploads/METALICO2022/ESCRITORIOS METALICOS/ES70.jpg', 1, 0, 1),
('MEP120 MEP140','Escritorio metálico MEP120 y MEP140, estructura metálica robusta.', 4572.00, 31, 'Uploads/METALICO2022/ESCRITORIOS METALICOS/MEP120 MEP140.jpg', 1, 0, 1);

-- GABINETES METÁLICOS (categoria_id: 32 - Subcategoría de Metálico)
INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES
('GBPRATICO','Gabinete metálico práctico GBPRATICO, estructura metálica resistente.', 3560.00, 32, 'Uploads/METALICO2022/GABINETES/GBPRATICO.jpg', 1, 0, 1),
('GC80','Gabinete metálico colgante GC80, sistema de instalación oculta.', 3150.00, 32, 'Uploads/METALICO2022/GABINETES/GC80.jpg', 1, 0, 1),
('LM3','Gabinete metálico LM3 de 3 puertas, estructura metálica reforzada.', 5420.00, 32, 'Uploads/METALICO2022/GABINETES/LM3.jpg', 1, 0, 1),
('LM4','Gabinete metálico LM4 de 4 puertas, amplia capacidad de almacenamiento.', 6380.00, 32, 'Uploads/METALICO2022/GABINETES/LM4.jpg', 1, 0, 1);

-- GÓNDOLAS (categoria_id: 33 - Subcategoría de Metálico)
INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES
('GD160','Góndola metálica GD160, estructura metálica resistente ideal para almacenamiento.', 4850.00, 33, 'Uploads/METALICO2022/GONDOLAS/GD160.jpg', 1, 0, 1),
('GD210','Góndola metálica GD210, diseño funcional y robusto.', 5960.00, 33, 'Uploads/METALICO2022/GONDOLAS/GD210.jpg', 1, 0, 1),
('GS160','Góndola metálica GS160, estructura metálica reforzada.', 5240.00, 33, 'Uploads/METALICO2022/GONDOLAS/GS160.jpg', 1, 0, 1),
('GS210','Góndola metálica GS210, amplia capacidad de almacenamiento.', 6420.00, 33, 'Uploads/METALICO2022/GONDOLAS/GS210.jpg', 1, 0, 1);

-- LOCKERS (categoria_id: 34 - Subcategoría de Metálico)
INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES
('L2P','Locker metálico L2P de 2 puertas, estructura metálica robusta.', 3780.00, 34, 'Uploads/METALICO2022/LOCKERS/L2P.jpg', 1, 0, 1),
('L2P MALLA','Locker metálico L2P con puertas de malla, 2 puertas, diseño ventilado.', 3980.00, 34, 'Uploads/METALICO2022/LOCKERS/L2P MALLA.jpg', 1, 0, 1),
('L3P','Locker metálico L3P de 3 puertas, estructura metálica resistente.', 4760.00, 34, 'Uploads/METALICO2022/LOCKERS/L3P.jpg', 1, 0, 1),
('L3P MALLA','Locker metálico L3P con puertas de malla, 3 puertas, diseño ventilado.', 4980.00, 34, 'Uploads/METALICO2022/LOCKERS/L3P MALLA.jpg', 1, 0, 1),
('L4P','Locker metálico L4P de 4 puertas, estructura metálica reforzada.', 5620.00, 34, 'Uploads/METALICO2022/LOCKERS/L4P.jpg', 1, 0, 1),
('L4P MALLA','Locker metálico L4P con puertas de malla, 4 puertas, diseño ventilado.', 5860.00, 34, 'Uploads/METALICO2022/LOCKERS/L4P MALLA.jpg', 1, 0, 1),
('L5P','Locker metálico L5P de 5 puertas, amplia capacidad de almacenamiento.', 6480.00, 34, 'Uploads/METALICO2022/LOCKERS/L5P.jpg', 1, 0, 1),
('L5P MALLA','Locker metálico L5P con puertas de malla, 5 puertas, diseño ventilado.', 6720.00, 34, 'Uploads/METALICO2022/LOCKERS/L5P MALLA.jpg', 1, 0, 1);

-- MESAS RESTAURANTERAS (categoria_id: 35 - Subcategoría de Metálico)
INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES
('B4P 80 B4P100','Mesa restaurantera B4P 80 y B4P100, estructura metálica robusta.', 3400.00, 35, 'Uploads/METALICO2022/MESAS RESTAURANTERAS/B4P 80 B4P100.jpg', 1, 0, 1),
('BC80 BC100 BC120','Mesa restaurantera BC80, BC100 y BC120, diseño funcional y resistente.', 3560.00, 35, 'Uploads/METALICO2022/MESAS RESTAURANTERAS/BC80 BC100 BC120.jpg', 1, 0, 1),
('BE60 BE80','Mesa restaurantera BE60 y BE80, estructura metálica reforzada.', 2650.00, 35, 'Uploads/METALICO2022/MESAS RESTAURANTERAS/BE60 BE80.jpg', 1, 0, 1),
('BED60 BED80','Mesa restaurantera BED60 y BED80, diseño compacto y funcional.', 2980.00, 35, 'Uploads/METALICO2022/MESAS RESTAURANTERAS/BED60 BED80.jpg', 1, 0, 1),
('BR60 BR80 BR100','Mesa restaurantera BR60, BR80 y BR100, estructura metálica resistente.', 3760.00, 35, 'Uploads/METALICO2022/MESAS RESTAURANTERAS/BR60 BR80 BR100.jpg', 1, 0, 1),
('BRD60 BRD80 BRD100','Mesa restaurantera BRD60, BRD80 y BRD100, diseño robusto.', 3980.00, 35, 'Uploads/METALICO2022/MESAS RESTAURANTERAS/BRD60 BRD80 BRD100.jpg', 1, 0, 1),
('BT80 BT100','Mesa restaurantera BT80 y BT100, estructura metálica reforzada.', 3840.00, 35, 'Uploads/METALICO2022/MESAS RESTAURANTERAS/BT80 BT100.jpg', 1, 0, 1);

-- MESAS METÁLICAS (categoria_id: 36 - Subcategoría de Metálico)
INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES
('MJC240 MJC200','Mesa de juntas metálica MJC240 y MJC200, estructura metálica robusta.', 5800.00, 36, 'Uploads/METALICO2022/MESAS/MESAS DE JUNTAS/MJC240 MJC200.jpg', 1, 0, 1),
('MJE120','Mesa de juntas metálica MJE120, diseño funcional y resistente.', 4200.00, 36, 'Uploads/METALICO2022/MESAS/MESAS DE JUNTAS/MJE120.jpg', 1, 0, 1),
('MJM240','Mesa de juntas metálica MJM240, estructura metálica reforzada.', 5400.00, 36, 'Uploads/METALICO2022/MESAS/MESAS DE JUNTAS/MJM240.jpg', 1, 0, 1),
('MFT','Mesa de trabajo metálica MFT, estructura metálica resistente.', 3800.00, 36, 'Uploads/METALICO2022/MESAS/MESAS DE TRABAJO/MFT.jpg', 1, 0, 1),
('MFU','Mesa de trabajo metálica MFU, diseño funcional y robusto.', 4100.00, 36, 'Uploads/METALICO2022/MESAS/MESAS DE TRABAJO/MFU.jpg', 1, 0, 1),
('MG120','Mesa de trabajo metálica MG120, estructura metálica reforzada.', 4400.00, 36, 'Uploads/METALICO2022/MESAS/MESAS DE TRABAJO/MG120.jpg', 1, 0, 1),
('MLT1240 MLT1260','Mesa de trabajo metálica MLT1240 y MLT1260, diseño compacto y funcional.', 3650.00, 36, 'Uploads/METALICO2022/MESAS/MESAS DE TRABAJO/MLT1240 MLT1260.jpg', 1, 0, 1),
('MLT1880','Mesa de trabajo metálica MLT1880, amplia superficie de trabajo.', 5800.00, 36, 'Uploads/METALICO2022/MESAS/MESAS DE TRABAJO/MLT1880.jpg', 1, 0, 1),
('MS','Mesa de trabajo metálica MS, estructura metálica resistente.', 4200.00, 36, 'Uploads/METALICO2022/MESAS/MESAS DE TRABAJO/MS.jpg', 1, 0, 1),
('MT','Mesa de trabajo metálica MT, diseño funcional y robusto.', 4400.00, 36, 'Uploads/METALICO2022/MESAS/MESAS DE TRABAJO/MT.jpg', 1, 0, 1),
('MTC','Mesa de trabajo metálica MTC, estructura metálica reforzada.', 4600.00, 36, 'Uploads/METALICO2022/MESAS/MESAS DE TRABAJO/MTC.jpg', 1, 0, 1),
('MTT','Mesa de trabajo metálica MTT, diseño compacto y funcional.', 4800.00, 36, 'Uploads/METALICO2022/MESAS/MESAS DE TRABAJO/MTT.jpg', 1, 0, 1),
('MLT6040 MLT8050','Mesa multiusos metálica MLT6040 y MLT8050, estructura metálica resistente.', 2800.00, 36, 'Uploads/METALICO2022/MESAS/MESAS MULTIUSOS/MLT6040 MLT8050.jpg', 1, 0, 1),
('MLT6041 MLT8051','Mesa multiusos metálica MLT6041 y MLT8051, diseño funcional y robusto.', 2900.00, 36, 'Uploads/METALICO2022/MESAS/MESAS MULTIUSOS/MLT6041 MLT8051.jpg', 1, 0, 1);

-- ESCOLAR METÁLICO (categoria_id: 37 - Subcategoría de Metálico)
INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES
('AB100','Silla escolar metálica AB100, estructura metálica resistente.', 2439.00, 37, 'Uploads/METALICO2022/ESCOLAR/AB100.jpg', 1, 0, 1),
('AB800','Silla escolar metálica AB800, diseño funcional y robusto.', 2143.80, 37, 'Uploads/METALICO2022/ESCOLAR/AB800.jpg', 1, 0, 1),
('AB800P','Silla escolar metálica AB800P, estructura metálica reforzada.', 1852.20, 37, 'Uploads/METALICO2022/ESCOLAR/AB800P.jpg', 1, 0, 1),
('AB800PT','Silla escolar metálica AB800PT, diseño compacto y funcional.', 2308.50, 37, 'Uploads/METALICO2022/ESCOLAR/AB800PT.jpg', 1, 0, 1),
('AB807M','Silla escolar metálica AB807M, estructura metálica resistente.', 1050.30, 37, 'Uploads/METALICO2022/ESCOLAR/AB807M.jpg', 1, 0, 1),
('AB810M','Silla escolar metálica AB810M, diseño funcional y robusto.', 2572.20, 37, 'Uploads/METALICO2022/ESCOLAR/AB810M.jpg', 1, 0, 1),
('APRENDISTA INFANTIL','Pupitre escolar metálico Aprendista Infantil, estructura metálica reforzada.', 1636.20, 37, 'Uploads/METALICO2022/ESCOLAR/APRENDISTA INFANTIL.jpg', 1, 0, 1),
('MEO60 MEO80','Mesa escolar metálica MEO60 y MEO80, diseño funcional y resistente.', 1850.00, 37, 'Uploads/METALICO2022/ESCOLAR/MEO60 MEO80.jpg', 1, 0, 1),
('MEP120 MEP140','Mesa escolar metálica MEP120 y MEP140, estructura metálica robusta.', 4572.00, 37, 'Uploads/METALICO2022/ESCOLAR/MEP120 MEP140.jpg', 1, 0, 1),
('MESA ESCOLAR MA120 MA140','Mesa escolar metálica MA120 y MA140, estructura metálica reforzada.', 3922.20, 37, 'Uploads/METALICO2022/ESCOLAR/MESA ESCOLAR MA120 MA140.jpg', 1, 0, 1),
('MESA ESCOLAR MP75','Mesa escolar metálica MP75, diseño compacto y funcional.', 2400.00, 37, 'Uploads/METALICO2022/ESCOLAR/MESA ESCOLAR MP75.jpg', 1, 0, 1),
('MESA ESCOLAR MPT-C MPT-G','Mesa escolar metálica MPT-C y MPT-G, estructura metálica resistente.', 2800.00, 37, 'Uploads/METALICO2022/ESCOLAR/MESA ESCOLAR MPT-C MPT-G.jpg', 1, 0, 1),
('MESA INFANTIL MP55','Mesa escolar metálica infantil MP55, diseño funcional y robusto.', 1980.00, 37, 'Uploads/METALICO2022/ESCOLAR/MESA INFANTIL MP55.jpg', 1, 0, 1),
('MESA TRAPEZOIDAL MTN-G MTN-C','Mesa escolar metálica trapezoidal MTN-G y MTN-C, estructura metálica reforzada.', 3200.00, 37, 'Uploads/METALICO2022/ESCOLAR/MESA TRAPEZOIDAL MTN-G MTN-C.jpg', 1, 0, 1),
('PL100','Pupitre escolar metálico PL100, diseño compacto y funcional.', 2478.60, 37, 'Uploads/METALICO2022/ESCOLAR/PL100.jpg', 1, 0, 1),
('PL11','Pupitre escolar metálico PL11, estructura metálica resistente.', 1846.80, 37, 'Uploads/METALICO2022/ESCOLAR/PL11.jpg', 1, 0, 1),
('PL13','Pupitre escolar metálico PL13, diseño funcional y robusto.', 1231.20, 37, 'Uploads/METALICO2022/ESCOLAR/PL13.jpg', 1, 0, 1),
('PL15','Pupitre escolar metálico PL15, estructura metálica reforzada.', 1098.00, 37, 'Uploads/METALICO2022/ESCOLAR/PL15.jpg', 1, 0, 1),
('PL820','Pupitre escolar metálico PL820, diseño compacto y funcional.', 2407.50, 37, 'Uploads/METALICO2022/ESCOLAR/PL820.jpg', 1, 0, 1),
('PL825','Pupitre escolar metálico PL825, estructura metálica resistente.', 1967.40, 37, 'Uploads/METALICO2022/ESCOLAR/PL825.jpg', 1, 0, 1),
('PM2','Pupitre escolar metálico PM2, diseño funcional y robusto.', 2695.50, 37, 'Uploads/METALICO2022/ESCOLAR/PM2.jpg', 1, 0, 1),
('PPI-2 PPS-2','Pupitre escolar metálico PPI-2 y PPS-2, estructura metálica reforzada.', 2400.00, 37, 'Uploads/METALICO2022/ESCOLAR/PPI-2 PPS-2.jpg', 1, 0, 1),
('PS40','Mesa escolar metálica PS40, diseño compacto y funcional.', 2176.20, 37, 'Uploads/METALICO2022/ESCOLAR/PS40.jpg', 1, 0, 1),
('PSC-1 PKC-1','Pupitre escolar metálico PSC-1 y PKC-1, estructura metálica resistente.', 2600.00, 37, 'Uploads/METALICO2022/ESCOLAR/PSC-1 PKC-1.jpg', 1, 0, 1),
('PU4 ISO','Pupitre escolar metálico PU4 ISO, diseño funcional y robusto.', 1430.10, 37, 'Uploads/METALICO2022/ESCOLAR/PU4 ISO.jpg', 1, 0, 1),
('PUPITRE SOLUTION','Pupitre escolar metálico Solution, estructura metálica reforzada.', 2200.00, 37, 'Uploads/METALICO2022/ESCOLAR/PUPITRE SOLUTION.jpg', 1, 0, 1),
('SC-C SC-G','Pupitre escolar metálico SC-C y SC-G, diseño compacto y funcional.', 1850.00, 37, 'Uploads/METALICO2022/ESCOLAR/SC-C SC-G.jpg', 1, 0, 1),
('SK-10','Banco escolar metálico SK-10, estructura metálica resistente.', 1208.70, 37, 'Uploads/METALICO2022/ESCOLAR/SK-10.jpg', 1, 0, 1),
('SP20','Silla escolar metálica SP20, diseño funcional y robusto.', 1593.90, 37, 'Uploads/METALICO2022/ESCOLAR/SP20.jpg', 1, 0, 1),
('SS30','Silla escolar metálica SS30, estructura metálica reforzada.', 1593.90, 37, 'Uploads/METALICO2022/ESCOLAR/SS30.jpg', 1, 0, 1),
('ST-P ST-S','Pupitre escolar metálico ST-P y ST-S, diseño compacto y funcional.', 1768.50, 37, 'Uploads/METALICO2022/ESCOLAR/ST-P ST-S.jpg', 1, 0, 1),
('ST01','Pupitre escolar metálico ST01, estructura metálica resistente.', 1768.50, 37, 'Uploads/METALICO2022/ESCOLAR/ST01.jpg', 1, 0, 1),
('ST03','Pupitre escolar metálico ST03, diseño funcional y robusto.', 1768.50, 37, 'Uploads/METALICO2022/ESCOLAR/ST03.jpg', 1, 0, 1);

-- LÍNEA ECONÓMICA METÁLICO (categoria_id: 38 - Subcategoría de Metálico)
INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES
('ECO 1+1','Archivero metálico económico ECO 1+1, 1 cajón papelero y 1 cajón de archivo, estructura metálica resistente.', 3200.00, 38, 'Uploads/METALICO2022/LINEA ECONOMICA/ECO 1+1.jpg', 1, 0, 1),
('ECO 2+1','Archivero metálico económico ECO 2+1, 2 cajones papeleros y 1 cajón de archivo, diseño funcional.', 3850.00, 38, 'Uploads/METALICO2022/LINEA ECONOMICA/ECO 2+1.jpg', 1, 0, 1),
('ECO H2','Archivero metálico económico horizontal ECO H2 de 2 gavetas, estructura metálica reforzada.', 5140.00, 38, 'Uploads/METALICO2022/LINEA ECONOMICA/ECO H2.jpg', 1, 0, 1),
('ECO H3','Archivero metálico económico horizontal ECO H3 de 3 gavetas, diseño compacto y funcional.', 6630.00, 38, 'Uploads/METALICO2022/LINEA ECONOMICA/ECO H3.jpg', 1, 0, 1),
('ECO H4','Archivero metálico económico horizontal ECO H4 de 4 gavetas, estructura metálica resistente.', 7630.00, 38, 'Uploads/METALICO2022/LINEA ECONOMICA/ECO H4.jpg', 1, 0, 1),
('ECO1270 ECO1675','Escritorio metálico económico ECO1270 y ECO1675, estructura metálica robusta.', 4100.00, 38, 'Uploads/METALICO2022/LINEA ECONOMICA/ECO1270 ECO1675.jpg', 1, 0, 1),
('ECO2','Archivero metálico económico ECO2 vertical de 2 gavetas, diseño funcional y robusto.', 3660.00, 38, 'Uploads/METALICO2022/LINEA ECONOMICA/ECO2.jpg', 1, 0, 1),
('ECO2160 ECO2180','Escritorio metálico económico ECO2160 y ECO2180, estructura metálica reforzada.', 4580.00, 38, 'Uploads/METALICO2022/LINEA ECONOMICA/ECO2160 ECO2180.jpg', 1, 0, 1),
('ECO3','Archivero metálico económico ECO3 vertical de 3 gavetas, diseño compacto y funcional.', 4930.00, 38, 'Uploads/METALICO2022/LINEA ECONOMICA/ECO3.jpg', 1, 0, 1),
('ECO4','Archivero metálico económico ECO4 vertical de 4 gavetas, estructura metálica resistente.', 5660.00, 38, 'Uploads/METALICO2022/LINEA ECONOMICA/ECO4.jpg', 1, 0, 1);

-- LÍNEA EURO (categoria_id: 40 - Subcategoría de Líneas)
INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES
('Escritorio Euro','Escritorio ejecutivo de la línea Euro, diseño moderno y funcional.', 9448.20, 40, 'https://www.zamofi.com/wp-content/uploads/2025/05/1-ESCRITORIO-EURO.jpg', 1, 0, 1),
('Conjunto Secretarial Euro I','Conjunto secretarial operativo con escritorio y retorno.', 12902.40, 40, 'https://www.zamofi.com/wp-content/uploads/2025/05/1-CONJUNTO-SECRETARIAL-EURO-I.jpg', 1, 0, 1),
('Conjunto Secretarial Euro III','Conjunto secretarial completo con archivero integrado.', 17738.10, 40, 'https://www.zamofi.com/wp-content/uploads/2025/05/1-CONJUNTO-SECRETARIAL-EURO-III.jpg', 1, 0, 1),
('Conjunto Ejecutivo CEE Euro','Conjunto ejecutivo premium con acabados de alta calidad.', 28242.00, 40, 'https://www.zamofi.com/wp-content/uploads/2025/05/1-CONJUNTO-EJECUTIVO-CEE-EURO.jpg', 1, 0, 1),
('Conjunto Ejecutivo Euro','Conjunto ejecutivo estándar línea Euro.', 19079.10, 40, 'https://www.zamofi.com/wp-content/uploads/2025/05/1-CONJUNTO-EJECUTIVO-EURO.jpg', 1, 0, 1),
('Isla Recta Doble Euro I','Isla de trabajo recta para dos usuarios, configuración I.', 18083.70, 40, 'https://www.zamofi.com/wp-content/uploads/2025/05/1-ISLA-RECTA-DOBLE-EURO-I.jpg', 1, 0, 1),
('Estación de Trabajo Doble Euro','Estación de trabajo doble operativa.', 24473.70, 40, 'https://www.zamofi.com/wp-content/uploads/2025/05/1-ESTACION-DE-TRABAJO-DOBLE-EURO.jpg', 1, 0, 1),
('Isla Recta Doble Euro','Isla recta doble básica.', 18083.70, 40, 'https://www.zamofi.com/wp-content/uploads/2025/05/1-ISLA-RECTA-DOBLE-EURO.jpg', 1, 0, 1),
('Cruceta 4 Personas Euro DEC-240','Estación de trabajo tipo cruceta para 4 personas, modelo DEC-240.', 48946.50, 40, 'https://www.zamofi.com/wp-content/uploads/2025/05/CRUCETA-4-PERSONAS-EURO-DEC-240-ROMERO-MALTA.jpg', 1, 0, 1),
('Cruceta 4 Personas Euro','Cruceta operativa para 4 personas.', 48946.50, 40, 'https://www.zamofi.com/wp-content/uploads/2025/05/1-CRUCETA-4-PERSONAS-EURO.jpg', 1, 0, 1),
('Cruceta 4 Personas Euro II','Cruceta operativa variante II.', 51282.90, 40, 'https://www.zamofi.com/wp-content/uploads/2025/05/1-CRUCETA-4-PERSONAS-EURO-1.jpg', 1, 0, 1),
('Triceta 3 Personas Euro','Estación de trabajo tipo triceta para 3 usuarios.', 27632.70, 40, 'https://www.zamofi.com/wp-content/uploads/2025/05/1-TRICETA-3-PERSONAS-EURO.jpg', 1, 0, 1),
('Triceta 3 Personas Euro (PNG)','Estación de trabajo tipo triceta, versión imagen PNG.', 27632.70, 40, 'https://www.zamofi.com/wp-content/uploads/2025/05/1-TRICETA-3-PERSONAS-EURO.png', 1, 0, 1),
('Conjunto Secretarial Euro Eco','Conjunto secretarial línea económica Euro.', 12902.40, 40, 'https://www.zamofi.com/wp-content/uploads/2025/05/1-CONJUNTO-SECRETARIAL-EURO-ECO.jpg', 1, 0, 1),
('Mesa de Juntas Euro MJE-1216','Mesa de juntas modelo MJE-1216, acabado Blanco/Rioja.', 5832.00, 40, 'https://www.zamofi.com/wp-content/uploads/2025/05/MESA-DE-JUNTAS-EURO-MJE-1216-BLANCO-RIOJA.jpg', 1, 0, 1),
('Mesa de Juntas Rectangular Euro','Mesa de juntas rectangular diseño Euro.', 9895.50, 40, 'https://www.zamofi.com/wp-content/uploads/2025/05/1-MESA-DE-JUNTAS-RECTANGULAR-EURO.jpg', 1, 0, 1),
('Mesa de Juntas Euro 300','Mesa de juntas amplia modelo Euro 300.', 13369.50, 40, 'https://www.zamofi.com/wp-content/uploads/2025/05/1-MESA-DE-JUNTAS-EURO-300.jpg', 1, 0, 1);

-- LÍNEA DELTA (categoria_id: 41 - Subcategoría de Líneas)
INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES
('D-001 Alt 1','Escritorio ejecutivo Delta D-001, variante con acabado alternativo.', 13552.20, 41, 'https://www.zamofi.com/wp-content/uploads/2025/04/D-001.1.png', 1, 0, 1),
('D-001','Escritorio ejecutivo Delta D-001, diseño moderno.', 13552.20, 41, 'https://www.zamofi.com/wp-content/uploads/2025/04/D-001.png', 1, 0, 1),
('D-002 Alt 1','Escritorio Delta D-002, configuración alternativa.', 13552.20, 41, 'https://www.zamofi.com/wp-content/uploads/2025/04/D-002.1.png', 1, 0, 1),
('D-003 Alt 1','Módulo operativo Delta D-003, variante.', 13206.60, 41, 'https://www.zamofi.com/wp-content/uploads/2025/04/D-003.1.png', 1, 0, 1),
('D-002','Escritorio Delta D-002 clásico.', 13552.20, 41, 'https://www.zamofi.com/wp-content/uploads/2025/04/D-002.png', 1, 0, 1),
('D-003','Módulo operativo Delta D-003.', 13206.60, 41, 'https://www.zamofi.com/wp-content/uploads/2025/04/D-003.png', 1, 0, 1),
('D-004 Alt 1','Estación de trabajo Delta D-004, acabado especial.', 27632.70, 41, 'https://www.zamofi.com/wp-content/uploads/2025/04/D-004.1.png', 1, 0, 1),
('D-005 Alt 1','Estación Delta D-005, variante.', 27632.70, 41, 'https://www.zamofi.com/wp-content/uploads/2025/04/D-005.1.png', 1, 0, 1),
('D-004','Estación de trabajo Delta D-004.', 27632.70, 41, 'https://www.zamofi.com/wp-content/uploads/2025/04/D-004.png', 1, 0, 1),
('D-005','Estación Delta D-005 estándar.', 27632.70, 41, 'https://www.zamofi.com/wp-content/uploads/2025/04/D-005.png', 1, 0, 1),
('D-006 Alt 1','Conjunto ejecutivo Delta D-006, variante.', 37121.40, 41, 'https://www.zamofi.com/wp-content/uploads/2025/04/D-006.1.png', 1, 0, 1),
('D-007 Alt 1','Conjunto Delta D-007, variante.', 37121.40, 41, 'https://www.zamofi.com/wp-content/uploads/2025/04/D-007.1.png', 1, 0, 1),
('D-006','Conjunto ejecutivo Delta D-006.', 37121.40, 41, 'https://www.zamofi.com/wp-content/uploads/2025/04/D-006.png', 1, 0, 1),
('D-007','Conjunto Delta D-007 estándar.', 37121.40, 41, 'https://www.zamofi.com/wp-content/uploads/2025/04/D-007.png', 1, 0, 1),
('CDD Alt 1','Credenza Delta CDD, variante.', 36775.80, 41, 'https://www.zamofi.com/wp-content/uploads/2025/04/CDD-1.png', 1, 0, 1),
('CDD','Credenza Delta CDD clásica.', 36775.80, 41, 'https://www.zamofi.com/wp-content/uploads/2025/04/CDD.png', 1, 0, 1),
('D-008 Alt 1','Escritorio Delta D-008.', 18083.70, 41, 'https://www.zamofi.com/wp-content/uploads/2025/04/D-008.1.png', 1, 0, 1),
('D-009 Alt 1','Escritorio Delta D-009.', 18083.70, 41, 'https://www.zamofi.com/wp-content/uploads/2025/04/D-009.1.png', 1, 0, 1),
('D-010 Alt 1','Escritorio Delta D-010.', 18083.70, 41, 'https://www.zamofi.com/wp-content/uploads/2025/04/D-010.1.png', 1, 0, 1),
('Mesa Juntas MJDD','Mesa de juntas Delta MJDD.', 36775.80, 41, 'https://www.zamofi.com/wp-content/uploads/2025/04/MJDD.png', 1, 0, 1),
('Mesa Juntas MJD','Mesa de juntas Delta MJD.', 11581.20, 41, 'https://www.zamofi.com/wp-content/uploads/2025/04/MJD.png', 1, 0, 1),
('Mesa Juntas MJDDD','Mesa de juntas Delta MJDDD.', 40270.50, 41, 'https://www.zamofi.com/wp-content/uploads/2025/04/MJDDD.png', 1, 0, 1);

-- LÍNEA TEMPO (categoria_id: 42 - Subcategoría de Líneas)
INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES
('CET-180 Alt 1','Escritorio ejecutivo Tempo CET-180, variante con acabado alternativo.', 42057.90, 42, 'https://www.zamofi.com/wp-content/uploads/2025/07/CET-180-1.png', 1, 0, 1),
('CET-180 Alt 2','Escritorio ejecutivo Tempo CET-180, segunda variante.', 42057.90, 42, 'https://www.zamofi.com/wp-content/uploads/2025/07/CET-180-2.png', 1, 0, 1),
('CSE-160','Estación secretarial Tempo CSE-160 en L.', 24706.80, 42, 'https://www.zamofi.com/wp-content/uploads/2025/07/CSE-160.png', 1, 0, 1),
('CSE-160 Alt 1','Estación secretarial Tempo CSE-160, variante de color.', 24706.80, 42, 'https://www.zamofi.com/wp-content/uploads/2025/07/CSE-160-1.png', 1, 0, 1),
('ETG-1670','Escritorio gerencial Tempo ETG-1670.', 18245.70, 42, 'https://www.zamofi.com/wp-content/uploads/2025/07/ETG-1670.png', 1, 0, 1),
('ETG-1670 Alt 1','Escritorio gerencial Tempo ETG-1670, variante.', 18245.70, 42, 'https://www.zamofi.com/wp-content/uploads/2025/07/ETG-1670-1.png', 1, 0, 1),
('CT-180 Alt 1','Credenza Tempo CT-180, detalle o variante.', 17676.90, 42, 'https://www.zamofi.com/wp-content/uploads/2025/07/CT-180-1.png', 1, 0, 1),
('CT-180','Credenza Tempo CT-180 ejecutiva.', 17676.90, 42, 'https://www.zamofi.com/wp-content/uploads/2025/07/CT-180.png', 1, 0, 1),
('Línea Tambor EG-1240','Escritorio tipo Tambor EG-1240.', 7924.50, 42, 'https://www.zamofi.com/wp-content/uploads/2025/07/LINEA-TAMBOR-EG-1240.jpg', 1, 0, 1),
('Línea Tambor EG-1670','Escritorio tipo Tambor EG-1670 amplio.', 18245.70, 42, 'https://www.zamofi.com/wp-content/uploads/2025/07/LINEA-TAMBOR-EG-1670.jpg', 1, 0, 1),
('Línea Tambor EG-1241','Escritorio tipo Tambor EG-1241.', 13206.60, 42, 'https://www.zamofi.com/wp-content/uploads/2025/07/LINEA-TAMBOR-EG-1241.jpg', 1, 0, 1),
('Mesa Juntas MJT-120','Mesa de juntas Tempo MJT-120 circular.', 11581.20, 42, 'https://www.zamofi.com/wp-content/uploads/2025/07/LINEA-TAMBOR-MJT-120.jpg', 1, 0, 1),
('Mesa Juntas MJT-240','Mesa de juntas Tempo MJT-240 rectangular.', 22553.10, 42, 'https://www.zamofi.com/wp-content/uploads/2025/07/LINEA-TAMBOR-MJT-240.jpg', 1, 0, 1),
('Mesa Juntas MJT-360','Mesa de juntas Tempo MJT-360 gran formato.', 37994.40, 42, 'https://www.zamofi.com/wp-content/uploads/2025/07/LINEA-TAMBOR-MJT-360.jpg', 1, 0, 1),
('MET-15 Alt 1','Mesa de trabajo MET-15, variante.', 27632.70, 42, 'https://www.zamofi.com/wp-content/uploads/2025/07/MET-15-1.png', 1, 0, 1),
('MET-15','Mesa de trabajo MET-15 estándar.', 27632.70, 42, 'https://www.zamofi.com/wp-content/uploads/2025/07/MET-15.png', 1, 0, 1),
('RM-180','Recepción RM-180 Tempo.', 28851.30, 42, 'https://www.zamofi.com/wp-content/uploads/2025/07/RM-180.png', 1, 0, 1),
('RM-180 Alt 1','Recepción RM-180 Tempo, variante.', 28851.30, 42, 'https://www.zamofi.com/wp-content/uploads/2025/07/RM-180-1.png', 1, 0, 1);

-- LÍNEA A (categoria_id: 43 - Subcategoría de Líneas)
INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES
('A1-01','Escritorio A1-01 diseño moderno.', 12150.90, 43, 'https://www.zamofi.com/wp-content/uploads/2025/06/A1-01.png', 1, 0, 1),
('A1-01 Alt 1','Escritorio A1-01 variante 1.', 14364.90, 43, 'https://www.zamofi.com/wp-content/uploads/2025/06/A1-01-1.png', 1, 0, 1),
('A1-01 Alt 2','Escritorio A1-01 variante 2.', 12150.90, 43, 'https://www.zamofi.com/wp-content/uploads/2025/06/A1-01-2.png', 1, 0, 1),
('A1-01-180 Alt 1','Escritorio A1-01 180cm variante 1.', 15767.10, 43, 'https://www.zamofi.com/wp-content/uploads/2025/06/A1-01-180-1.png', 1, 0, 1),
('A1-01-180','Escritorio A1-01 180cm diseño amplio.', 13796.10, 43, 'https://www.zamofi.com/wp-content/uploads/2025/06/A1-01-180.png', 1, 0, 1),
('A1-01-180 Alt 2','Escritorio A1-01 180cm variante 2.', 13796.10, 43, 'https://www.zamofi.com/wp-content/uploads/2025/06/A1-01-180-2.png', 1, 0, 1),
('A1-02-CAG2','Archivero A1-02-CAG2.', 4673.70, 43, 'https://www.zamofi.com/wp-content/uploads/2025/06/A1-02-CAG2.png', 1, 0, 1),
('A1-02-CBG2','Archivero A1-02-CBG2.', 4673.70, 43, 'https://www.zamofi.com/wp-content/uploads/2025/06/A1-02-CBG2.png', 1, 0, 1),
('A1-02-CACH2','Archivero bajo A1-02-CACH2.', 4673.70, 43, 'https://www.zamofi.com/wp-content/uploads/2025/06/A1-02-CACH2.png', 1, 0, 1),
('A1-02-CBCH2','Archivero bajo A1-02-CBCH2.', 4673.70, 43, 'https://www.zamofi.com/wp-content/uploads/2025/06/A1-02-CBCH2.png', 1, 0, 1),
('A1-04-MB','Mesa de trabajo A1-04-MB.', 15117.30, 43, 'https://www.zamofi.com/wp-content/uploads/2025/06/A1-04-MB.png', 1, 0, 1),
('A1-04-MB Alt 1','Mesa de trabajo A1-04-MB variante 1.', 12577.50, 43, 'https://www.zamofi.com/wp-content/uploads/2025/06/A1-04-MB-1.png', 1, 0, 1),
('A1-04-MB Alt 2','Mesa de trabajo A1-04-MB variante 2.', 15117.30, 43, 'https://www.zamofi.com/wp-content/uploads/2025/06/A1-04-MB-2.png', 1, 0, 1),
('A1-03-Cajonera 2','Cajonera A1-03 modelo 2.', 4673.70, 43, 'https://www.zamofi.com/wp-content/uploads/2025/06/A1-03-cajonera-2.png', 1, 0, 1),
('A1-03-Cajonera 1','Cajonera A1-03 modelo 1.', 4673.70, 43, 'https://www.zamofi.com/wp-content/uploads/2025/06/A1-03-cajonera-1-1.png', 1, 0, 1),
('A1-03-Cajonera','Cajonera A1-03 estándar.', 4673.70, 43, 'https://www.zamofi.com/wp-content/uploads/2025/06/A1-03-cajonera.png', 1, 0, 1),
('A1-03-Credenza','Credenza A1-03.', 13004.10, 43, 'https://www.zamofi.com/wp-content/uploads/2025/06/A1-03-credenza.png', 1, 0, 1),
('A1-03-Credenza Alt 1','Credenza A1-03 variante 1.', 11378.70, 43, 'https://www.zamofi.com/wp-content/uploads/2025/06/A1-03-credenza-1.png', 1, 0, 1),
('A1-03-Credenza Alt 2','Credenza A1-03 variante 2.', 13004.10, 43, 'https://www.zamofi.com/wp-content/uploads/2025/06/A1-03-credenza-2.png', 1, 0, 1),
('A1-05-MJ 1','Mesa de juntas A1-05-MJ.', 19688.40, 43, 'https://www.zamofi.com/wp-content/uploads/2025/06/A1-05-MJ-1.png', 1, 0, 1),
('A1-05-MJ1 1','Mesa de juntas A1-05-MJ1.', 19688.40, 43, 'https://www.zamofi.com/wp-content/uploads/2025/06/A1-05-MJ1-1.png', 1, 0, 1),
('A1-05-MJ2 1','Mesa de juntas A1-05-MJ2.', 19688.40, 43, 'https://www.zamofi.com/wp-content/uploads/2025/06/A1-05-MJ2-1.png', 1, 0, 1),
('A1-07-MJ','Mesa de juntas A1-07-MJ.', 29339.10, 43, 'https://www.zamofi.com/wp-content/uploads/2025/06/A1-07-MJ.png', 1, 0, 1),
('A1-07-MJ2','Mesa de juntas A1-07-MJ2.', 29339.10, 43, 'https://www.zamofi.com/wp-content/uploads/2025/06/A1-07-MJ2.png', 1, 0, 1),
('A1-07-MJ1','Mesa de juntas A1-07-MJ1.', 29339.10, 43, 'https://www.zamofi.com/wp-content/uploads/2025/06/A1-07-MJ1.png', 1, 0, 1),
('A1-05-MBC','Mesa baja A1-05-MBC.', 26992.80, 43, 'https://www.zamofi.com/wp-content/uploads/2025/06/A1-05-MBC.png', 1, 0, 1),
('A1-05-MBC 1','Mesa baja A1-05-MBC variante 1.', 21913.20, 43, 'https://www.zamofi.com/wp-content/uploads/2025/06/A1-05-MBC1.png', 1, 0, 1),
('A1-05-MBC 2','Mesa baja A1-05-MBC variante 2.', 26992.80, 43, 'https://www.zamofi.com/wp-content/uploads/2025/06/A1-05-MBC2.png', 1, 0, 1),
('A1-07-MBC','Mesa baja A1-07-MBC.', 39193.20, 43, 'https://www.zamofi.com/wp-content/uploads/2025/06/A1-07-MBC.png', 1, 0, 1),
('A1-07-MBC 1','Mesa baja A1-07-MBC variante 1.', 31574.70, 43, 'https://www.zamofi.com/wp-content/uploads/2025/06/A1-07-MBC1.png', 1, 0, 1),
('A1-07-MBM2','Mesa baja A1-07-MBM2.', 31574.70, 43, 'https://www.zamofi.com/wp-content/uploads/2025/06/A1-07-MBM2.png', 1, 0, 1);

-- LÍNEA BETA (categoria_id: 44 - Subcategoría de Líneas)
INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES
('BTJ-01','Escritorio ejecutivo BTJ-01.', 13207.50, 44, 'https://www.zamofi.com/wp-content/uploads/2025/06/BTJ-01.png', 1, 0, 1),
('BTJ-01 Alt 1','Escritorio ejecutivo BTJ-01 variante 1.', 15421.50, 44, 'https://www.zamofi.com/wp-content/uploads/2025/06/BTJ-01-1.png', 1, 0, 1),
('BTJ-01 Alt 2','Escritorio ejecutivo BTJ-01 variante 2.', 13207.50, 44, 'https://www.zamofi.com/wp-content/uploads/2025/06/BTJ-01-2.png', 1, 0, 1),
('BTJ-01-180x90','Escritorio BTJ-01 180x90cm.', 14568.30, 44, 'https://www.zamofi.com/wp-content/uploads/2025/06/BTJ-01-180x90.png', 1, 0, 1),
('BTJ-01-180x90 Alt 1','Escritorio BTJ-01 180x90cm variante 1.', 16539.30, 44, 'https://www.zamofi.com/wp-content/uploads/2025/06/BTJ-01-180x90-1.png', 1, 0, 1),
('BTJ-01-180x90 Alt 2','Escritorio BTJ-01 180x90cm variante 2.', 14568.30, 44, 'https://www.zamofi.com/wp-content/uploads/2025/06/BTJ-01-180x90-2.png', 1, 0, 1),
('BTJ-02-CAG2','Archivero BTJ-02-CAG2.', 4673.70, 44, 'https://www.zamofi.com/wp-content/uploads/2025/06/BTJ-02-CAG2.png', 1, 0, 1),
('BTJ-02-CBG2','Archivero BTJ-02-CBG2.', 4673.70, 44, 'https://www.zamofi.com/wp-content/uploads/2025/06/BTJ-02-CBG2.png', 1, 0, 1),
('BTJ-02-CBCH2 1','Archivero bajo BTJ-02-CBCH2 variante 1.', 4673.70, 44, 'https://www.zamofi.com/wp-content/uploads/2025/06/BTJ-02-CBCH2-1.png', 1, 0, 1),
('BTJ-02-CACH2 1','Archivero bajo BTJ-02-CACH2 variante 1.', 4673.70, 44, 'https://www.zamofi.com/wp-content/uploads/2025/06/BTJ-02-CACH2-1.png', 1, 0, 1),
('BTJ-03','Mesa de trabajo BTJ-03.', 16762.50, 44, 'https://www.zamofi.com/wp-content/uploads/2025/06/BTJ-03.png', 1, 0, 1),
('BTJ-03 Alt 1','Mesa de trabajo BTJ-03 variante 1.', 14212.80, 44, 'https://www.zamofi.com/wp-content/uploads/2025/06/BTJ-03-1.png', 1, 0, 1),
('BTJ-03 Alt 2','Mesa de trabajo BTJ-03 variante 2.', 16762.50, 44, 'https://www.zamofi.com/wp-content/uploads/2025/06/BTJ-03-2.png', 1, 0, 1),
('BTJ-04-CE 3','Conjunto ejecutivo BTJ-04-CE modelo 3.', 18530.10, 44, 'https://www.zamofi.com/wp-content/uploads/2025/06/BTJ-04-CE-3.png', 1, 0, 1),
('BTJ-04-CE 1 1','Conjunto ejecutivo BTJ-04-CE modelo 1 variante 1.', 20765.70, 44, 'https://www.zamofi.com/wp-content/uploads/2025/06/BTJ-04-CE-1-1.png', 1, 0, 1),
('BTJ-04-CE 2 1','Conjunto ejecutivo BTJ-04-CE modelo 2 variante 1.', 18530.10, 44, 'https://www.zamofi.com/wp-content/uploads/2025/06/BTJ-04-CE-2-1.png', 1, 0, 1),
('BTJ-04-CB 1','Conjunto bajo BTJ-04-CB modelo 1.', 19972.80, 44, 'https://www.zamofi.com/wp-content/uploads/2025/06/BTJ-04-CB-1.png', 1, 0, 1),
('BTJ-04-CB1 1','Conjunto bajo BTJ-04-CB1 variante 1.', 22187.70, 44, 'https://www.zamofi.com/wp-content/uploads/2025/06/BTJ-04-CB1-1.png', 1, 0, 1),
('BTJ-04-CB2 1','Conjunto bajo BTJ-04-CB2 variante 1.', 19972.80, 44, 'https://www.zamofi.com/wp-content/uploads/2025/06/BTJ-04-CB2-1.png', 1, 0, 1),
('BTJ-05-MJ','Mesa de juntas BTJ-05-MJ.', 21171.60, 44, 'https://www.zamofi.com/wp-content/uploads/2025/06/BTJ-05-MJ.png', 1, 0, 1),
('BTJ-05-MJ2','Mesa de juntas BTJ-05-MJ2.', 21171.60, 44, 'https://www.zamofi.com/wp-content/uploads/2025/06/BTJ-05-MJ2.png', 1, 0, 1),
('BTJ-05-MJ1','Mesa de juntas BTJ-05-MJ1.', 21171.60, 44, 'https://www.zamofi.com/wp-content/uploads/2025/06/BTJ-05-MJ1.png', 1, 0, 1),
('BTJ-07-MJ','Mesa de juntas BTJ-07-MJ.', 31005.90, 44, 'https://www.zamofi.com/wp-content/uploads/2025/06/BTJ-07-MJ.png', 1, 0, 1),
('BTJ-07-MJ2','Mesa de juntas BTJ-07-MJ2.', 31005.90, 44, 'https://www.zamofi.com/wp-content/uploads/2025/06/BTJ-07-MJ2.png', 1, 0, 1),
('BTJ-07-MJ1','Mesa de juntas BTJ-07-MJ1.', 31005.90, 44, 'https://www.zamofi.com/wp-content/uploads/2025/06/BTJ-07-MJ1.png', 1, 0, 1),
('BTJ-05-MBM','Mesa baja BTJ-05-MBM.', 28485.90, 44, 'https://www.zamofi.com/wp-content/uploads/2025/06/BTJ-05-MBM.png', 1, 0, 1),
('BTJ-05-MBM1','Mesa baja BTJ-05-MBM1.', 23406.30, 44, 'https://www.zamofi.com/wp-content/uploads/2025/06/BTJ-05-MBM1.png', 1, 0, 1),
('BTJ-05-MBM2','Mesa baja BTJ-05-MBM2.', 28485.90, 44, 'https://www.zamofi.com/wp-content/uploads/2025/06/BTJ-05-MBM2.png', 1, 0, 1),
('BTJ-07-MB','Mesa baja BTJ-07-MB.', 40860.00, 44, 'https://www.zamofi.com/wp-content/uploads/2025/06/BTJ-07-MB.png', 1, 0, 1),
('BTJ-07-MB2','Mesa baja BTJ-07-MB2.', 33240.60, 44, 'https://www.zamofi.com/wp-content/uploads/2025/06/BTJ-07-MB2.png', 1, 0, 1),
('BTJ-07-MB1','Mesa baja BTJ-07-MB1.', 40860.00, 44, 'https://www.zamofi.com/wp-content/uploads/2025/06/BTJ-07-MB1.png', 1, 0, 1);

-- LÍNEA CERES (categoria_id: 45 - Subcategoría de Líneas)
INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES
('C50-01','Escritorio ejecutivo C50-01.', 9753.30, 45, 'https://www.zamofi.com/wp-content/uploads/2025/06/C50-01.png', 1, 0, 1),
('C50-01 Alt 1','Escritorio ejecutivo C50-01 variante 1.', 11967.30, 45, 'https://www.zamofi.com/wp-content/uploads/2025/06/C50-01-1.png', 1, 0, 1),
('C50-01 Alt 2','Escritorio ejecutivo C50-01 variante 2.', 9753.30, 45, 'https://www.zamofi.com/wp-content/uploads/2025/06/C-50-01-2.png', 1, 0, 1),
('C50-01-180','Escritorio C50-01 180cm.', 11134.80, 45, 'https://www.zamofi.com/wp-content/uploads/2025/06/C50-01-180.png', 1, 0, 1),
('C50-01-180 Alt 1','Escritorio C50-01 180cm variante 1.', 13105.80, 45, 'https://www.zamofi.com/wp-content/uploads/2025/06/C50-01-180-1.png', 1, 0, 1),
('C50-01-180 Alt 2','Escritorio C50-01 180cm variante 2.', 11134.80, 45, 'https://www.zamofi.com/wp-content/uploads/2025/06/C50-01-180-2.png', 1, 0, 1),
('C50-02-CAG2','Archivero C50-02-CAG2.', 4673.70, 45, 'https://www.zamofi.com/wp-content/uploads/2025/06/C50-02-CAG2.png', 1, 0, 1),
('C50-02-CBG2','Archivero C50-02-CBG2.', 4673.70, 45, 'https://www.zamofi.com/wp-content/uploads/2025/06/C50-02-CBG2.png', 1, 0, 1),
('C50-02-CACH2 1','Archivero bajo C50-02-CACH2 variante 1.', 4673.70, 45, 'https://www.zamofi.com/wp-content/uploads/2025/06/C50-02-CACH2-1.png', 1, 0, 1),
('C50-02-CBCH2 1','Archivero bajo C50-02-CBCH2 variante 1.', 4673.70, 45, 'https://www.zamofi.com/wp-content/uploads/2025/06/C50-02-CBCH2-1.png', 1, 0, 1),
('C50-03','Mesa de trabajo C50-03.', 12679.20, 45, 'https://www.zamofi.com/wp-content/uploads/2025/06/C50-03.png', 1, 0, 1),
('C50-03 Alt 1','Mesa de trabajo C50-03 variante 1.', 12679.20, 45, 'https://www.zamofi.com/wp-content/uploads/2025/06/C50-03-1.png', 1, 0, 1),
('C50-03 Alt 2','Mesa de trabajo C50-03 variante 2.', 12679.20, 45, 'https://www.zamofi.com/wp-content/uploads/2025/06/C50-03-2.png', 1, 0, 1),
('C50-04-CE 1','Conjunto ejecutivo C50-04-CE modelo 1.', 18997.20, 45, 'https://www.zamofi.com/wp-content/uploads/2025/06/C50-04-CE-1.png', 1, 0, 1),
('C50-04-CE2 2','Conjunto ejecutivo C50-04-CE2 variante 2.', 20420.10, 45, 'https://www.zamofi.com/wp-content/uploads/2025/06/C50-04-CE2-2.png', 1, 0, 1),
('C50-04-CE1 1','Conjunto ejecutivo C50-04-CE1 variante 1.', 21212.10, 45, 'https://www.zamofi.com/wp-content/uploads/2025/06/C50-04-CE1-1.png', 1, 0, 1),
('C50-04-CC 1','Conjunto bajo C50-04-CC modelo 1.', 18997.20, 45, 'https://www.zamofi.com/wp-content/uploads/2025/06/C50-04-CC-1.png', 1, 0, 1),
('C50-04-CC1 1','Conjunto bajo C50-04-CC1 variante 1.', 21212.10, 45, 'https://www.zamofi.com/wp-content/uploads/2025/06/C50-04-CC1-1.png', 1, 0, 1),
('C50-04-CC2 1','Conjunto bajo C50-04-CC2 variante 1.', 22634.10, 45, 'https://www.zamofi.com/wp-content/uploads/2025/06/C50-04-CC2-1.png', 1, 0, 1),
('C50-05-MJ','Mesa de juntas C50-05-MJ.', 16986.60, 45, 'https://www.zamofi.com/wp-content/uploads/2025/06/C50-05-MJ.png', 1, 0, 1),
('C50-05-MJ1','Mesa de juntas C50-05-MJ1.', 16986.60, 45, 'https://www.zamofi.com/wp-content/uploads/2025/06/C50-05-MJ1.png', 1, 0, 1),
('C50-05-MJ2','Mesa de juntas C50-05-MJ2.', 16986.60, 45, 'https://www.zamofi.com/wp-content/uploads/2025/06/C50-05-MJ2.png', 1, 0, 1),
('C50-07-MJ','Mesa de juntas C50-07-MJ.', 16986.60, 45, 'https://www.zamofi.com/wp-content/uploads/2025/06/C50-07-MJ.png', 1, 0, 1),
('C50-07-MJ1','Mesa de juntas C50-07-MJ1.', 16986.60, 45, 'https://www.zamofi.com/wp-content/uploads/2025/06/C50-07-MJ1.png', 1, 0, 1),
('C50-07-MJ2','Mesa de juntas C50-07-MJ2.', 16986.60, 45, 'https://www.zamofi.com/wp-content/uploads/2025/06/C50-07-MJ2.png', 1, 0, 1),
('C50-05-MB','Mesa baja C50-05-MB.', 24300.90, 45, 'https://www.zamofi.com/wp-content/uploads/2025/06/C50-05-MB.png', 1, 0, 1),
('C50-05-MB1','Mesa baja C50-05-MB1.', 24300.90, 45, 'https://www.zamofi.com/wp-content/uploads/2025/06/C50-05-MB1.png', 1, 0, 1),
('C50-05-MB2','Mesa baja C50-05-MB2.', 24300.90, 45, 'https://www.zamofi.com/wp-content/uploads/2025/06/C50-05-MB2.png', 1, 0, 1),
('C50-07-MB','Mesa baja C50-07-MB.', 24300.90, 45, 'https://www.zamofi.com/wp-content/uploads/2025/06/C50-07-MBpng.png', 1, 0, 1),
('C50-07-MB1','Mesa baja C50-07-MB1.', 24300.90, 45, 'https://www.zamofi.com/wp-content/uploads/2025/06/C50-07-MB1png.png', 1, 0, 1),
('C50-07-MB2','Mesa baja C50-07-MB2.', 24300.90, 45, 'https://www.zamofi.com/wp-content/uploads/2025/06/C50-07-MB2png.png', 1, 0, 1);

-- LÍNEA FIORE (categoria_id: 46 - Subcategoría de Líneas)
INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES
('F-3060B-160x80','Escritorio F-3060B 160x80cm.', 10890.90, 46, 'https://www.zamofi.com/wp-content/uploads/2025/06/F-3060B-160x80.png', 1, 0, 1),
('F-3060B 1 160x80','Escritorio F-3060B 160x80cm variante 1.', 13105.80, 46, 'https://www.zamofi.com/wp-content/uploads/2025/06/F-3060B-1-160x80.png', 1, 0, 1),
('F-3060B 2 160x80','Escritorio F-3060B 160x80cm variante 2.', 10890.90, 46, 'https://www.zamofi.com/wp-content/uploads/2025/06/F-3060B-2160x80.png', 1, 0, 1),
('F-3060B-180x90','Escritorio F-3060B 180x90cm.', 12109.50, 46, 'https://www.zamofi.com/wp-content/uploads/2025/06/F-3060B-180x90.png', 1, 0, 1),
('F-3060B 1 180x90','Escritorio F-3060B 180x90cm variante 1.', 14568.30, 46, 'https://www.zamofi.com/wp-content/uploads/2025/06/F-3060B-1-180x90.png', 1, 0, 1),
('F-3060B 2 180x90','Escritorio F-3060B 180x90cm variante 2.', 12109.50, 46, 'https://www.zamofi.com/wp-content/uploads/2025/06/F-3060B-2-180x90.png', 1, 0, 1),
('F-3060B-02-CA2','Archivero F-3060B-02-CA2.', 4673.70, 46, 'https://www.zamofi.com/wp-content/uploads/2025/06/F-3060B-02-CA2.png', 1, 0, 1),
('F-3060B-02-CB2','Archivero F-3060B-02-CB2.', 4673.70, 46, 'https://www.zamofi.com/wp-content/uploads/2025/06/F-3060B-02-CB2.png', 1, 0, 1),
('F-3060B-02-CACH2 1','Archivero bajo F-3060B-02-CACH2.', 4673.70, 46, 'https://www.zamofi.com/wp-content/uploads/2025/06/F-3060B-02-CACH2-1.png', 1, 0, 1),
('F-3060B-02-CBCH2 1','Archivero bajo F-3060B-02-CBCH2.', 4673.70, 46, 'https://www.zamofi.com/wp-content/uploads/2025/06/F-3060B-02-CBCH2-1.png', 1, 0, 1),
('F-3060B-03','Mesa de trabajo F-3060B-03.', 14263.20, 46, 'https://www.zamofi.com/wp-content/uploads/2025/06/F-3060B-03.png', 1, 0, 1),
('F-3060B-03 2','Mesa de trabajo F-3060B-03 variante 2.', 11723.40, 46, 'https://www.zamofi.com/wp-content/uploads/2025/06/F-3060B-03-2.png', 1, 0, 1),
('F-3060B-03 1','Mesa de trabajo F-3060B-03 variante 1.', 14263.20, 46, 'https://www.zamofi.com/wp-content/uploads/2025/06/F-3060B-03-1.png', 1, 0, 1),
('F-3060B-04-CE 1','Conjunto ejecutivo F-3060B-04-CE modelo 1.', 19363.50, 46, 'https://www.zamofi.com/wp-content/uploads/2025/06/F-3060B-04-CE-1.png', 1, 0, 1),
('F-3060B-04-CE1 2','Conjunto ejecutivo F-3060B-04-CE1 variante 2.', 21578.40, 46, 'https://www.zamofi.com/wp-content/uploads/2025/06/F-3060B-04-CE1-2.png', 1, 0, 1),
('F-3060B-04-CE2 1','Conjunto ejecutivo F-3060B-04-CE2 variante 1.', 19363.50, 46, 'https://www.zamofi.com/wp-content/uploads/2025/06/F-3060B-04-CE2-1.png', 1, 0, 1),
('F-3060B-04-CA 1','Conjunto bajo F-3060B-04-CA modelo 1.', 19363.50, 46, 'https://www.zamofi.com/wp-content/uploads/2025/06/F-3060B-04-CA-1.png', 1, 0, 1),
('F-3060B-04-CA1 1','Conjunto bajo F-3060B-04-CA1 variante 1.', 21578.40, 46, 'https://www.zamofi.com/wp-content/uploads/2025/06/F-3060B-04-CA1-1.png', 1, 0, 1),
('F-3060B-04-CA2 1','Conjunto bajo F-3060B-04-CA2 variante 1.', 23000.40, 46, 'https://www.zamofi.com/wp-content/uploads/2025/06/F-3060B-04-CA2-1.png', 1, 0, 1),
('F-3060B-05-MJ','Mesa de juntas F-3060B-05-MJ.', 28120.50, 46, 'https://www.zamofi.com/wp-content/uploads/2025/06/F-3060B-05-MJ.png', 1, 0, 1),
('F-3060B-05-MJ2','Mesa de juntas F-3060B-05-MJ2.', 28120.50, 46, 'https://www.zamofi.com/wp-content/uploads/2025/06/F-3060B-05-MJ2.png', 1, 0, 1),
('F-3060B-05-MJ1','Mesa de juntas F-3060B-05-MJ1.', 28120.50, 46, 'https://www.zamofi.com/wp-content/uploads/2025/06/F-3060B-05-MJ1.png', 1, 0, 1),
('F-3060B-07-MJ','Mesa de juntas F-3060B-07-MJ.', 18774.00, 46, 'https://www.zamofi.com/wp-content/uploads/2025/06/F-3060B-07-MJ.png', 1, 0, 1),
('F-3060B-07-MJ1','Mesa de juntas F-3060B-07-MJ1.', 18774.00, 46, 'https://www.zamofi.com/wp-content/uploads/2025/06/F-3060B-07-MJ1.png', 1, 0, 1),
('F-3060B-07-MJ2','Mesa de juntas F-3060B-07-MJ2.', 18774.00, 46, 'https://www.zamofi.com/wp-content/uploads/2025/06/F-3060B-07-MJ2.png', 1, 0, 1),
('F-3060B-05-MB','Mesa baja F-3060B-05-MB.', 26088.30, 46, 'https://www.zamofi.com/wp-content/uploads/2025/06/F-3060B-05-MB.png', 1, 0, 1),
('F-3060B-05-MB1','Mesa baja F-3060B-05-MB1.', 21008.70, 46, 'https://www.zamofi.com/wp-content/uploads/2025/06/F-3060B-05-MB1.png', 1, 0, 1),
('F-3060B-05-MB2','Mesa baja F-3060B-05-MB2.', 26088.30, 46, 'https://www.zamofi.com/wp-content/uploads/2025/06/F-3060B-05-MB2.png', 1, 0, 1),
('F-3060B-07-MB','Mesa baja F-3060B-07-MB.', 26088.30, 46, 'https://www.zamofi.com/wp-content/uploads/2025/06/F-3060B-07-MB.png', 1, 0, 1),
('F-3060B-07-MB1','Mesa baja F-3060B-07-MB1.', 21008.70, 46, 'https://www.zamofi.com/wp-content/uploads/2025/06/F-3060B-07-MB1.png', 1, 0, 1),
('F-3060B-07-MB2','Mesa baja F-3060B-07-MB2.', 26088.30, 46, 'https://www.zamofi.com/wp-content/uploads/2025/06/F-3060B-07-MB2.png', 1, 0, 1);

-- LÍNEA WORVIK (categoria_id: 47 - Subcategoría de Líneas)
INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES
('W3060-01-160x80','Escritorio W3060-01 160x80cm.', 11256.30, 47, 'https://www.zamofi.com/wp-content/uploads/2025/06/W3060-01-160x80.png', 1, 0, 1),
('W3060-01 1 160x80','Escritorio W3060-01 160x80cm variante 1.', 13471.20, 47, 'https://www.zamofi.com/wp-content/uploads/2025/06/W3060-01-1-160x80.png', 1, 0, 1),
('W3060-01 2 160x80','Escritorio W3060-01 160x80cm variante 2.', 11256.30, 47, 'https://www.zamofi.com/wp-content/uploads/2025/06/W3060-01-2-160x80.png', 1, 0, 1),
('W3060-01-180x90','Escritorio W3060-01 180x90cm.', 12841.20, 47, 'https://www.zamofi.com/wp-content/uploads/2025/06/W3060-01-180x90.png', 1, 0, 1),
('W3060-01 1 180x90','Escritorio W3060-01 180x90cm variante 1.', 14812.20, 47, 'https://www.zamofi.com/wp-content/uploads/2025/06/W3060-01-1-180x90.png', 1, 0, 1),
('W3060-01 2 180x90','Escritorio W3060-01 180x90cm variante 2.', 12841.20, 47, 'https://www.zamofi.com/wp-content/uploads/2025/06/W3060-01-2-180x90.png', 1, 0, 1),
('W3060-02-CAG2','Archivero W3060-02-CAG2.', 4673.70, 47, 'https://www.zamofi.com/wp-content/uploads/2025/06/W3060-02-CAG2.png', 1, 0, 1),
('W3060-02-CBG2','Archivero W3060-02-CBG2.', 4673.70, 47, 'https://www.zamofi.com/wp-content/uploads/2025/06/W3060-02-CBG2.png', 1, 0, 1),
('W3060-02-CACH2 1','Archivero bajo W3060-02-CACH2.', 4673.70, 47, 'https://www.zamofi.com/wp-content/uploads/2025/06/W3060-02-CACH2-1.png', 1, 0, 1),
('W3060-02-CBCH2 1','Archivero bajo W3060-02-CBCH2.', 4673.70, 47, 'https://www.zamofi.com/wp-content/uploads/2025/06/W3060-02-CBCH2-1.png', 1, 0, 1),
('W3060-03','Mesa de trabajo W3060-03.', 14629.50, 47, 'https://www.zamofi.com/wp-content/uploads/2025/06/W3060-03.png', 1, 0, 1),
('W3060-03 1','Mesa de trabajo W3060-03 variante 1.', 12089.70, 47, 'https://www.zamofi.com/wp-content/uploads/2025/06/W3060-03-1.png', 1, 0, 1),
('W3060-03 2','Mesa de trabajo W3060-03 variante 2.', 14629.50, 47, 'https://www.zamofi.com/wp-content/uploads/2025/06/W3060-03-2.png', 1, 0, 1),
('W3060-04-CE 1','Conjunto ejecutivo W3060-04-CE modelo 1.', 18814.50, 47, 'https://www.zamofi.com/wp-content/uploads/2025/06/W3060-04-CE-1.png', 1, 0, 1),
('W3060-04-CE1 1','Conjunto ejecutivo W3060-04-CE1 variante 1.', 21029.40, 47, 'https://www.zamofi.com/wp-content/uploads/2025/06/W3060-04-CE1-1.png', 1, 0, 1),
('W3060-04-CE2 1','Conjunto ejecutivo W3060-04-CE2 variante 1.', 18814.50, 47, 'https://www.zamofi.com/wp-content/uploads/2025/06/W3060-04-CE2-1.png', 1, 0, 1),
('W3060-04-CB 1','Conjunto bajo W3060-04-CB modelo 1.', 20237.40, 47, 'https://www.zamofi.com/wp-content/uploads/2025/06/W3060-04-CB-1.png', 1, 0, 1),
('W3060-04-CB1 1','Conjunto bajo W3060-04-CB1 variante 1.', 22451.40, 47, 'https://www.zamofi.com/wp-content/uploads/2025/06/W3060-04-CB1-1.png', 1, 0, 1),
('W3060-04-CB2 1','Conjunto bajo W3060-04-CB2 variante 1.', 20237.40, 47, 'https://www.zamofi.com/wp-content/uploads/2025/06/W3060-04-CB2-1.png', 1, 0, 1),
('W3060-07-MJ','Mesa de juntas W3060-07-MJ.', 18997.20, 47, 'https://www.zamofi.com/wp-content/uploads/2025/06/W3060-07-MJ.png', 1, 0, 1),
('W3060-07-MJ1','Mesa de juntas W3060-07-MJ1.', 18997.20, 47, 'https://www.zamofi.com/wp-content/uploads/2025/06/W3060-07-MJ1.png', 1, 0, 1),
('W3060-07-MJ2','Mesa de juntas W3060-07-MJ2.', 18997.20, 47, 'https://www.zamofi.com/wp-content/uploads/2025/06/W3060-07-MJ2.png', 1, 0, 1),
('W3060-05-MJ','Mesa de juntas W3060-05-MJ.', 28424.70, 47, 'https://www.zamofi.com/wp-content/uploads/2025/06/W3060-05-MJ.png', 1, 0, 1),
('W3060-05-MJ1','Mesa de juntas W3060-05-MJ1.', 28424.70, 47, 'https://www.zamofi.com/wp-content/uploads/2025/06/W3060-05-MJ1.png', 1, 0, 1),
('W3060-05-MJ2','Mesa de juntas W3060-05-MJ2.', 28424.70, 47, 'https://www.zamofi.com/wp-content/uploads/2025/06/W3060-05-MJ2.png', 1, 0, 1),
('W3060-07-MB','Mesa baja W3060-07-MB.', 26312.40, 47, 'https://www.zamofi.com/wp-content/uploads/2025/06/W3060-07-MB.png', 1, 0, 1),
('W3060-07-MB2','Mesa baja W3060-07-MB2.', 21232.80, 47, 'https://www.zamofi.com/wp-content/uploads/2025/06/W3060-07-MB2.png', 1, 0, 1),
('W3060-07-MB1','Mesa baja W3060-07-MB1.', 26312.40, 47, 'https://www.zamofi.com/wp-content/uploads/2025/06/W3060-07-MB1.png', 1, 0, 1),
('W3060-05-MB','Mesa baja W3060-05-MB.', 26312.40, 47, 'https://www.zamofi.com/wp-content/uploads/2025/06/W3060-05-MB.png', 1, 0, 1),
('W3060-05-MB1','Mesa baja W3060-05-MB1.', 21232.80, 47, 'https://www.zamofi.com/wp-content/uploads/2025/06/W3060-05-MB1.png', 1, 0, 1),
('W3060-05-MB2','Mesa baja W3060-05-MB2.', 26312.40, 47, 'https://www.zamofi.com/wp-content/uploads/2025/06/W3060-05-MB2.png', 1, 0, 1);

-- LÍNEA YENKO (categoria_id: 48 - Subcategoría de Líneas)
INSERT INTO producto (nombre, descripcion, precio, categoria_id, imagen, stock, destacado, activo) VALUES
('Y1-01','Escritorio Y1-01.', 13816.80, 48, 'https://www.zamofi.com/wp-content/uploads/2025/06/Y1-01.png', 1, 0, 1),
('Y1-01 Alt 1','Escritorio Y1-01 variante 1.', 16030.80, 48, 'https://www.zamofi.com/wp-content/uploads/2025/06/Y1-01-1.png', 1, 0, 1),
('Y1-01 Alt 2','Escritorio Y1-01 variante 2.', 13816.80, 48, 'https://www.zamofi.com/wp-content/uploads/2025/06/Y1-01-2.png', 1, 0, 1),
('Y1-01-180','Escritorio Y1-01 180cm.', 15198.30, 48, 'https://www.zamofi.com/wp-content/uploads/2025/06/Y1-01-180.png', 1, 0, 1),
('Y1-01-180 Alt 1','Escritorio Y1-01 180cm variante 1.', 17169.30, 48, 'https://www.zamofi.com/wp-content/uploads/2025/06/Y1-01-180-1.png', 1, 0, 1),
('Y1-01-180 Alt 2','Escritorio Y1-01 180cm variante 2.', 15198.30, 48, 'https://www.zamofi.com/wp-content/uploads/2025/06/Y1-01-180-2.png', 1, 0, 1),
('Y1-02-CAG2 1','Archivero Y1-02-CAG2.', 4673.70, 48, 'https://www.zamofi.com/wp-content/uploads/2025/06/Y1-02-CAG2-1.png', 1, 0, 1),
('Y1-02-CBG2','Archivero Y1-02-CBG2.', 4673.70, 48, 'https://www.zamofi.com/wp-content/uploads/2025/06/Y1-02-CBG2.png', 1, 0, 1),
('Y1-02-CACH2 1','Archivero bajo Y1-02-CACH2.', 4673.70, 48, 'https://www.zamofi.com/wp-content/uploads/2025/06/Y1-02-CACH2-1.png', 1, 0, 1),
('Y1-02-CBCH2 1','Archivero bajo Y1-02-CBCH2.', 4673.70, 48, 'https://www.zamofi.com/wp-content/uploads/2025/06/Y1-02-CBCH2-1.png', 1, 0, 1),
('Y1-03-M','Mesa de trabajo Y1-03-M.', 16274.70, 48, 'https://www.zamofi.com/wp-content/uploads/2025/06/Y1-03-M.png', 1, 0, 1),
('Y1-03-M1','Mesa de trabajo Y1-03-M1.', 13734.90, 48, 'https://www.zamofi.com/wp-content/uploads/2025/06/Y1-03-M1.png', 1, 0, 1),
('Y1-03-M2','Mesa de trabajo Y1-03-M2.', 16274.70, 48, 'https://www.zamofi.com/wp-content/uploads/2025/06/Y1-03-M2.png', 1, 0, 1),
('Y1-04-Cajonera 3','Cajonera Y1-04 modelo 3.', 4673.70, 48, 'https://www.zamofi.com/wp-content/uploads/2025/06/Y1-04-cajonera-3.png', 1, 0, 1),
('Y1-04-Cajonera 1 1','Cajonera Y1-04 modelo 1 variante 1.', 4673.70, 48, 'https://www.zamofi.com/wp-content/uploads/2025/06/Y1-04-cajonera-1-1.png', 1, 0, 1),
('Y1-04-Cajonera 2 1','Cajonera Y1-04 modelo 2 variante 1.', 4673.70, 48, 'https://www.zamofi.com/wp-content/uploads/2025/06/Y1-04-cajonera-2-1.png', 1, 0, 1),
('Y1-04-Credenza 1 1','Credenza Y1-04 modelo 1 variante 1.', 11378.70, 48, 'https://www.zamofi.com/wp-content/uploads/2025/06/Y1-04-credenza-1-1.png', 1, 0, 1),
('Y1-04-Credenza 2 1','Credenza Y1-04 modelo 2 variante 1.', 11378.70, 48, 'https://www.zamofi.com/wp-content/uploads/2025/06/Y1-04-credenza-2-1.png', 1, 0, 1),
('Y1-04-Credenza 3','Credenza Y1-04 modelo 3.', 11378.70, 48, 'https://www.zamofi.com/wp-content/uploads/2025/06/Y1-04-credenza-3.png', 1, 0, 1),
('Y1-05-MJ','Mesa de juntas Y1-05-MJ.', 21110.40, 48, 'https://www.zamofi.com/wp-content/uploads/2025/06/Y1-05-MJ.png', 1, 0, 1),
('Y1-05-MJ2','Mesa de juntas Y1-05-MJ2.', 21110.40, 48, 'https://www.zamofi.com/wp-content/uploads/2025/06/Y1-05-MJ2.png', 1, 0, 1),
('Y1-05-MJ1','Mesa de juntas Y1-05-MJ1.', 21110.40, 48, 'https://www.zamofi.com/wp-content/uploads/2025/06/Y1-05-MJ1.png', 1, 0, 1),
('Y1-07-MJ','Mesa de juntas Y1-07-MJ.', 30883.50, 48, 'https://www.zamofi.com/wp-content/uploads/2025/06/Y1-07-MJ.png', 1, 0, 1),
('Y1-07-MJ2','Mesa de juntas Y1-07-MJ2.', 30883.50, 48, 'https://www.zamofi.com/wp-content/uploads/2025/06/Y1-07-MJ2.png', 1, 0, 1),
('Y1-07-MJ1','Mesa de juntas Y1-07-MJ1.', 30883.50, 48, 'https://www.zamofi.com/wp-content/uploads/2025/06/Y1-07-MJ1.png', 1, 0, 1),
('Y1-05-MBM','Mesa baja Y1-05-MBM.', 27815.40, 48, 'https://www.zamofi.com/wp-content/uploads/2025/06/Y1-05-MBM.png', 1, 0, 1),
('Y1-05-MBM1','Mesa baja Y1-05-MBM1.', 23346.00, 48, 'https://www.zamofi.com/wp-content/uploads/2025/06/Y1-05-MBM1.png', 1, 0, 1),
('Y1-05-MBM2','Mesa baja Y1-05-MBM2.', 27815.40, 48, 'https://www.zamofi.com/wp-content/uploads/2025/06/Y1-05-MBM2.png', 1, 0, 1),
('Y1-07-MBM','Mesa baja Y1-07-MBM.', 27815.40, 48, 'https://www.zamofi.com/wp-content/uploads/2025/06/Y1-07-MBM.png', 1, 0, 1),
('Y1-07-MBM1','Mesa baja Y1-07-MBM1.', 23346.00, 48, 'https://www.zamofi.com/wp-content/uploads/2025/06/Y1-07-MBM1.png', 1, 0, 1),
('Y1-07-MBM2','Mesa baja Y1-07-MBM2.', 27815.40, 48, 'https://www.zamofi.com/wp-content/uploads/2025/06/Y1-07-MBM2.png', 1, 0, 1);

-- Poblar catálogo extendido `productos` a partir de `producto`
INSERT INTO productos (producto_base_id, nombre, descripcion, precio, stock, activo)
SELECT
    p.id,
    p.nombre,
    p.descripcion,
    COALESCE(p.precio, 0.00),
    COALESCE(p.stock, 0),
    COALESCE(p.activo, 1)
FROM producto p
LEFT JOIN productos px ON px.producto_base_id = p.id
WHERE px.id IS NULL;

SET FOREIGN_KEY_CHECKS = 1;