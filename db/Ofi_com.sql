-- =============================================================================
-- BASE DE DATOS: ofiequipo  –  v3 FINAL
-- Convenciones: nombres en snake_case minúsculas, FK con sufijo _id estándar.
-- Ejecutar ESTE archivo primero, luego inserts_corregidos.sql
-- =============================================================================

CREATE DATABASE IF NOT EXISTS ofiequipo2
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE ofiequipo2;

-- -----------------------------------------------------------------------------
-- TABLAS BASE
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS categoria (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  nombre    VARCHAR(120) NOT NULL,
  parent_id INT DEFAULT NULL,
  CONSTRAINT fk_categoria_parent
    FOREIGN KEY (parent_id) REFERENCES categoria(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS producto (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  categoria_id INT DEFAULT NULL,
  nombre       VARCHAR(255) NOT NULL,
  descripcion  TEXT,
  precio       DECIMAL(10,2) NOT NULL DEFAULT 0,
  imagen       VARCHAR(500) DEFAULT NULL,
  stock        INT NOT NULL DEFAULT 0,
  destacado    TINYINT(1) NOT NULL DEFAULT 0,
  activo       TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_producto_categoria
    FOREIGN KEY (categoria_id) REFERENCES categoria(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- ROLES Y USUARIOS DE LA TIENDA / APP
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS roles (
  id     BIGINT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO roles (id, nombre) VALUES
  (1, 'administrador'),
  (2, 'cliente'),
  (3, 'vendedor');

CREATE TABLE IF NOT EXISTS usuarios (
  id                  BIGINT AUTO_INCREMENT PRIMARY KEY,
  email               VARCHAR(190) NOT NULL UNIQUE,
  nombre              VARCHAR(120) DEFAULT NULL,
  contrasena_hash     VARCHAR(255) NOT NULL,
  email_verificado    TINYINT(1) NOT NULL DEFAULT 0,
  verificacion_token  VARCHAR(255) DEFAULT NULL,
  token_expira        DATETIME DEFAULT NULL,
  rol_id              BIGINT DEFAULT NULL,
  creado_en           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_usuario_rol
    FOREIGN KEY (rol_id) REFERENCES roles(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- CLIENTES Y DIRECCIONES
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS clientes (
  id           BIGINT AUTO_INCREMENT PRIMARY KEY,
  usuario_id   BIGINT DEFAULT NULL,
  rfc          VARCHAR(20) DEFAULT NULL,
  razon_social VARCHAR(190) DEFAULT NULL,
  uso_cfdi     VARCHAR(80) DEFAULT NULL,
  metodo_pago  VARCHAR(40) NOT NULL DEFAULT 'efectivo',
  CONSTRAINT chk_clientes_metodo_pago CHECK (
    metodo_pago IN ('efectivo','tarjeta_credito','tarjeta_debito','transferencia','paypal')
  ),
  CONSTRAINT fk_cliente_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS direcciones (
  id            BIGINT AUTO_INCREMENT PRIMARY KEY,
  usuario_id    BIGINT DEFAULT NULL,
  calle         VARCHAR(255) NOT NULL,
  ciudad        VARCHAR(120) NOT NULL,
  estado        VARCHAR(120) NOT NULL,
  pais          VARCHAR(120) NOT NULL,
  codigo_postal VARCHAR(20) NOT NULL,
  CONSTRAINT fk_direccion_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  INDEX idx_direcciones_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- CATÁLOGO EXTENDIDO (tienda online)
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS productos (
  id               BIGINT AUTO_INCREMENT PRIMARY KEY,
  producto_base_id INT DEFAULT NULL,
  nombre           VARCHAR(255) NOT NULL,
  descripcion      TEXT,
  precio           DECIMAL(10,2) NOT NULL,
  stock            INT NOT NULL DEFAULT 0,
  activo           TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_productos_producto_base
    FOREIGN KEY (producto_base_id) REFERENCES producto(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- PEDIDOS Y PAGOS
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS pedidos (
  id               BIGINT AUTO_INCREMENT PRIMARY KEY,
  cliente_id       BIGINT DEFAULT NULL,
  fecha_pedido     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  monto_total      DECIMAL(10,2) NOT NULL,
  requiere_factura TINYINT(1) NOT NULL DEFAULT 0,
  estado           VARCHAR(32) NOT NULL DEFAULT 'pendiente',
  CONSTRAINT fk_pedido_cliente
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL,
  INDEX idx_pedidos_fecha_pedido (fecha_pedido),
  INDEX idx_pedidos_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS detalle_pedidos (
  id          BIGINT AUTO_INCREMENT PRIMARY KEY,
  pedido_id   BIGINT NOT NULL,
  producto_id BIGINT NOT NULL,
  cantidad    INT NOT NULL,
  precio      DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_detalle_pedido
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
  CONSTRAINT fk_detalle_producto
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT,
  INDEX idx_detalle_pedidos_producto (producto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pagos (
  id          BIGINT AUTO_INCREMENT PRIMARY KEY,
  pedido_id   BIGINT NOT NULL,
  monto       DECIMAL(10,2) NOT NULL,
  metodo_pago VARCHAR(40) NOT NULL,
  fecha_pago  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pago_pedido
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
  INDEX idx_pagos_fecha_pago (fecha_pago)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- PANEL ADMINISTRATIVO
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS admin_roles (
  id     INT AUTO_INCREMENT PRIMARY KEY,
  slug   VARCHAR(32) NOT NULL UNIQUE,
  nombre VARCHAR(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO admin_roles (id, slug, nombre) VALUES
  (1, 'administrador', 'Administrador'),
  (2, 'vendedor',      'Vendedor'),
  (3, 'almacen',       'Almacén'),
  (4, 'repartidor',    'Repartidor');

CREATE TABLE IF NOT EXISTS admin_usuarios (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  email         VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  nombre        VARCHAR(120) NOT NULL,
  rol_id        INT NOT NULL,
  activo        TINYINT(1) NOT NULL DEFAULT 1,
  creado_en     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (rol_id) REFERENCES admin_roles(id),
  INDEX idx_admin_usuarios_rol (rol_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Contraseña inicial: OfiequipoAdmin2026 (cámbiala al ingresar)
INSERT IGNORE INTO admin_usuarios (id, email, password_hash, nombre, rol_id) VALUES
  (1, 'admin@ofiequipo.local',
   '$2y$12$6dckJub/imy.BWz.CLDvue/UTjorq7tTnVe5.X4hmxegI9vR44LCW',
   'Administrador principal', 1);

CREATE TABLE IF NOT EXISTS admin_clientes (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  nombre    VARCHAR(120) NOT NULL,
  apellido  VARCHAR(120) DEFAULT '',
  email     VARCHAR(190) NOT NULL,
  telefono  VARCHAR(40) DEFAULT '',
  notas     TEXT,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_admin_clientes_email  (email),
  INDEX idx_admin_clientes_creado (creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_pedidos (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  numero_pedido     VARCHAR(32) NOT NULL UNIQUE,
  cliente_id        INT DEFAULT NULL,
  nombre_contacto   VARCHAR(200) NOT NULL DEFAULT '',
  email_contacto    VARCHAR(190) NOT NULL DEFAULT '',
  telefono_contacto VARCHAR(40) NOT NULL DEFAULT '',
  estado            VARCHAR(32) NOT NULL DEFAULT 'pendiente',
  subtotal          DECIMAL(12,2) NOT NULL DEFAULT 0,
  impuestos         DECIMAL(12,2) NOT NULL DEFAULT 0,
  costo_envio       DECIMAL(12,2) NOT NULL DEFAULT 0,
  total             DECIMAL(12,2) NOT NULL DEFAULT 0,
  metodo_pago       VARCHAR(64) NOT NULL DEFAULT 'pendiente',
  notas             TEXT,
  creado_en         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (cliente_id) REFERENCES admin_clientes(id) ON DELETE SET NULL,
  INDEX idx_admin_pedidos_estado  (estado),
  INDEX idx_admin_pedidos_creado  (creado_en),
  INDEX idx_admin_pedidos_cliente (cliente_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_detalle_pedido (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  pedido_id       INT NOT NULL,
  producto_id     INT DEFAULT NULL,
  nombre_producto VARCHAR(255) NOT NULL,
  cantidad        INT NOT NULL DEFAULT 1,
  precio_unitario DECIMAL(10,2) NOT NULL DEFAULT 0,
  subtotal_linea  DECIMAL(12,2) NOT NULL DEFAULT 0,
  FOREIGN KEY (pedido_id)   REFERENCES admin_pedidos(id) ON DELETE CASCADE,
  FOREIGN KEY (producto_id) REFERENCES producto(id) ON DELETE SET NULL,
  INDEX idx_admin_detalle_pedido_pedido (pedido_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_envios (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  pedido_id      INT NOT NULL UNIQUE,
  estado         VARCHAR(32) NOT NULL DEFAULT 'pendiente',
  guia_rastreo   VARCHAR(120) DEFAULT '',
  transportista  VARCHAR(120) DEFAULT '',
  fecha_estimada DATE DEFAULT NULL,
  notas_internas TEXT,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (pedido_id) REFERENCES admin_pedidos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_inventario_mov (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  producto_id   INT NOT NULL,
  tipo          VARCHAR(24) NOT NULL,
  cantidad      INT NOT NULL,
  stock_despues INT DEFAULT NULL,
  referencia    VARCHAR(80) DEFAULT '',
  pedido_id     INT DEFAULT NULL,
  nota          VARCHAR(500) DEFAULT '',
  usuario_id    INT DEFAULT NULL,
  creado_en     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (producto_id) REFERENCES producto(id) ON DELETE CASCADE,
  FOREIGN KEY (pedido_id)   REFERENCES admin_pedidos(id) ON DELETE SET NULL,
  FOREIGN KEY (usuario_id)  REFERENCES admin_usuarios(id) ON DELETE SET NULL,
  INDEX idx_admin_inventario_producto (producto_id),
  INDEX idx_admin_inventario_fecha    (creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_promociones (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  nombre          VARCHAR(160) NOT NULL,
  alcance         VARCHAR(24) NOT NULL DEFAULT 'global',
  producto_id     INT DEFAULT NULL,
  categoria_id    INT DEFAULT NULL,
  descuento_pct   DECIMAL(5,2) DEFAULT NULL,
  descuento_monto DECIMAL(10,2) DEFAULT NULL,
  fecha_inicio    DATE DEFAULT NULL,
  fecha_fin       DATE DEFAULT NULL,
  activo          TINYINT(1) NOT NULL DEFAULT 1,
  creado_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (producto_id)  REFERENCES producto(id)  ON DELETE CASCADE,
  FOREIGN KEY (categoria_id) REFERENCES categoria(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_cupones (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  codigo          VARCHAR(40) NOT NULL UNIQUE,
  descuento_pct   DECIMAL(5,2) DEFAULT NULL,
  descuento_monto DECIMAL(10,2) DEFAULT NULL,
  max_usos        INT DEFAULT NULL,
  usos_actuales   INT NOT NULL DEFAULT 0,
  fecha_inicio    DATE DEFAULT NULL,
  fecha_fin       DATE DEFAULT NULL,
  activo          TINYINT(1) NOT NULL DEFAULT 1,
  creado_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_config (
  clave          VARCHAR(80) PRIMARY KEY,
  valor          TEXT,
  grupo          VARCHAR(40) NOT NULL DEFAULT 'general',
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO admin_config (clave, valor, grupo) VALUES
  ('tienda_nombre',          'Ofiequipo de Tampico',          'tienda'),
  ('tienda_email',           '',                              'tienda'),
  ('tienda_telefono',        '',                              'tienda'),
  ('envio_costo_base',       '150',                           'envio'),
  ('envio_gratis_desde',     '5000',                          'envio'),
  ('impuesto_iva_pct',       '16',                            'impuestos'),
  ('pago_metodos',           'transferencia,paypal,efectivo', 'pagos'),
  ('notif_email_pedido',     '1',                             'notificaciones'),
  ('notif_email_inventario', '1',                             'notificaciones');

CREATE TABLE IF NOT EXISTS admin_auditoria (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT DEFAULT NULL,
  accion     VARCHAR(80) NOT NULL,
  entidad    VARCHAR(80) NOT NULL,
  entidad_id INT DEFAULT NULL,
  detalle    TEXT,
  ip         VARCHAR(45) DEFAULT '',
  creado_en  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES admin_usuarios(id) ON DELETE SET NULL,
  INDEX idx_admin_auditoria_fecha   (creado_en),
  INDEX idx_admin_auditoria_entidad (entidad, entidad_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Rate limiting de autenticación ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS login_attempts (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  ip           VARCHAR(45)  NOT NULL,
  context      VARCHAR(64)  NOT NULL,
  success      TINYINT(1)   NOT NULL DEFAULT 0,
  attempted_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_login_attempts_lookup (ip, context, attempted_at, success)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- FIN — Ejecutar inserts_corregidos.sql a continuación
-- =============================================================================