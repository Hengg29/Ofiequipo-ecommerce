-- =====================================================
-- MIGRACIÓN: Cotizaciones ligadas al usuario + pago del total fijado por el admin
-- No borra datos. Hacer backup antes:
--   mysqldump -u user -p db cotizaciones cotizacion_items > cotizaciones_backup.sql
-- =====================================================

ALTER TABLE cotizaciones
    ADD COLUMN usuario_id      BIGINT        DEFAULT NULL AFTER folio,
    ADD COLUMN subtotal        DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER envio_costo,
    ADD COLUMN iva             DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER subtotal,
    ADD COLUMN total           DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER iva,
    ADD COLUMN pago_enviado_en DATETIME      DEFAULT NULL AFTER total,
    ADD COLUMN pagada_en       DATETIME      DEFAULT NULL AFTER pago_enviado_en,
    ADD COLUMN pago_metodo     VARCHAR(20)   DEFAULT NULL AFTER pagada_en,
    ADD COLUMN pago_referencia VARCHAR(100)  DEFAULT NULL AFTER pago_metodo,
    ADD COLUMN pedido_id       BIGINT        DEFAULT NULL AFTER pago_referencia,
    MODIFY COLUMN status ENUM('pendiente','en_proceso','cotizada','pagada','rechazada')
        NOT NULL DEFAULT 'pendiente',
    ADD KEY idx_cot_usuario (usuario_id),
    ADD UNIQUE KEY uq_cot_pago_ref (pago_referencia);

-- cotizacion_items.precio ya existe: a partir de ahora solo lo escribe el admin
-- (precio unitario negociado). El sitio público nunca lo envía ni lo lee.
