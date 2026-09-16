-- ============================================================
-- SIGEP - Migración: Repositorio institucional
-- 1. Cédula del estudiante
-- 2. Periodos académicos
-- 3. periodo_id en proyectos
-- 4. Tabla repositorio
-- ============================================================

-- 1) Cédula del estudiante (búsqueda por número de cédula)
ALTER TABLE estudiantes ADD COLUMN cedula VARCHAR(20) NULL AFTER codigo;
ALTER TABLE estudiantes ADD UNIQUE INDEX uq_estudiantes_cedula (cedula);

-- 2) Periodos académicos
CREATE TABLE IF NOT EXISTS periodos_academicos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(60) NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_periodo_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) Periodo del proyecto (asignación manual por el admin)
ALTER TABLE proyectos ADD COLUMN periodo_id INT UNSIGNED NULL AFTER tipo_proyecto_id;
ALTER TABLE proyectos ADD INDEX idx_proyectos_periodo (periodo_id);
ALTER TABLE proyectos ADD CONSTRAINT fk_proyectos_periodo
    FOREIGN KEY (periodo_id) REFERENCES periodos_academicos (id) ON DELETE SET NULL;

-- 4) Repositorio institucional
CREATE TABLE IF NOT EXISTS repositorio (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    proyecto_id INT UNSIGNED NULL,
    periodo_id INT UNSIGNED NULL,
    estudiante_id INT UNSIGNED NULL,
    grupo CHAR(36) NULL,
    tipo ENUM('documento_final','entregable_tecnico','anexo','otro') NOT NULL DEFAULT 'anexo',
    titulo VARCHAR(200) NOT NULL,
    nombre_original VARCHAR(255) NOT NULL,
    ruta VARCHAR(500) NOT NULL,
    formato VARCHAR(20) NOT NULL DEFAULT '',
    tamanio BIGINT UNSIGNED NOT NULL DEFAULT 0,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    subido_por INT UNSIGNED NOT NULL,
    estado ENUM('activo','reemplazado','eliminado') NOT NULL DEFAULT 'activo',
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_repo_proyecto (proyecto_id),
    KEY idx_repo_periodo (periodo_id),
    KEY idx_repo_estudiante (estudiante_id),
    KEY idx_repo_tipo (tipo),
    KEY idx_repo_estado (estado),
    CONSTRAINT fk_repo_proyecto FOREIGN KEY (proyecto_id) REFERENCES proyectos (id) ON DELETE SET NULL,
    CONSTRAINT fk_repo_periodo FOREIGN KEY (periodo_id) REFERENCES periodos_academicos (id) ON DELETE SET NULL,
    CONSTRAINT fk_repo_estudiante FOREIGN KEY (estudiante_id) REFERENCES estudiantes (id) ON DELETE SET NULL,
    CONSTRAINT fk_repo_usuario FOREIGN KEY (subido_por) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;