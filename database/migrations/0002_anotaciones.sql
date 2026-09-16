-- ============================================================
-- SIGEP - Migración: Resaltados (anotaciones) sobre documentos
-- Permite marcar fragmentos de texto en la vista previa y que
-- queden resaltados (amarillo) ligados a una observación.
-- ============================================================

CREATE TABLE IF NOT EXISTS anotaciones (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    documento_id INT UNSIGNED NOT NULL,
    observacion_id INT UNSIGNED NULL,
    usuario_id INT UNSIGNED NOT NULL,
    pagina INT UNSIGNED NOT NULL,
    rects TEXT NOT NULL,
    texto TEXT NULL,
    color VARCHAR(20) NOT NULL DEFAULT 'amarillo',
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_anot_documento (documento_id),
    KEY idx_anot_observacion (observacion_id),
    CONSTRAINT fk_anot_documento FOREIGN KEY (documento_id) REFERENCES documentos (id) ON DELETE CASCADE,
    CONSTRAINT fk_anot_observacion FOREIGN KEY (observacion_id) REFERENCES observaciones (id) ON DELETE CASCADE,
    CONSTRAINT fk_anot_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
