-- ============================================================
-- SIGEP - Esquema de base de datos
-- Base: MySQL / InnoDB / utf8mb4
-- ============================================================

-- ------------------------------------------------------------
-- 1. roles
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS roles (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    descripcion VARCHAR(255) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_roles_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. usuarios
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    rol_id INT UNSIGNED NOT NULL,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password VARCHAR(255) NOT NULL,
    telefono VARCHAR(20) NULL,
    estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_email (email),
    KEY idx_usuarios_rol (rol_id),
    CONSTRAINT fk_usuarios_rol FOREIGN KEY (rol_id) REFERENCES roles (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. estudiantes
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS estudiantes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id INT UNSIGNED NOT NULL,
    codigo VARCHAR(20) NOT NULL,
    cedula VARCHAR(20) NULL,
    carrera VARCHAR(120) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_estudiantes_usuario (usuario_id),
    UNIQUE KEY uq_estudiantes_codigo (codigo),
    UNIQUE KEY uq_estudiantes_cedula (cedula),
    CONSTRAINT fk_estudiantes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. docentes
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS docentes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id INT UNSIGNED NOT NULL,
    titulo VARCHAR(120) NULL,
    especialidad VARCHAR(120) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_docentes_usuario (usuario_id),
    CONSTRAINT fk_docentes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5. tipos_proyecto
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tipos_proyecto (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(120) NOT NULL,
    descripcion VARCHAR(255) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_tipos_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 6. etapas
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS etapas (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tipo_proyecto_id INT UNSIGNED NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    descripcion VARCHAR(255) NULL,
    orden INT UNSIGNED NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_etapas_tipo_orden (tipo_proyecto_id, orden),
    CONSTRAINT fk_etapas_tipo FOREIGN KEY (tipo_proyecto_id) REFERENCES tipos_proyecto (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 6.5 periodos_academicos
-- ------------------------------------------------------------
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

-- ------------------------------------------------------------
-- 7. proyectos
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS proyectos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(30) NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT NULL,
    tipo_proyecto_id INT UNSIGNED NOT NULL,
    periodo_id INT UNSIGNED NULL,
    estudiante_id INT UNSIGNED NOT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_inicio DATE NULL,
    fecha_limite DATE NULL,
    estado ENUM('borrador','enviado','en_revision','con_observaciones','en_correccion','reenviado','aprobado','finalizado','vencido') NOT NULL DEFAULT 'borrador',
    etapa_actual_id INT UNSIGNED NULL,
    porcentaje_avance DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    ultima_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_proyectos_codigo (codigo),
    KEY idx_proyectos_tipo (tipo_proyecto_id),
    KEY idx_proyectos_estudiante (estudiante_id),
    KEY idx_proyectos_estado (estado),
    KEY idx_proyectos_etapa_actual (etapa_actual_id),
    KEY idx_proyectos_periodo (periodo_id),
    CONSTRAINT fk_proyectos_tipo FOREIGN KEY (tipo_proyecto_id) REFERENCES tipos_proyecto (id),
    CONSTRAINT fk_proyectos_estudiante FOREIGN KEY (estudiante_id) REFERENCES estudiantes (id),
    CONSTRAINT fk_proyectos_etapa_actual FOREIGN KEY (etapa_actual_id) REFERENCES etapas (id),
    CONSTRAINT fk_proyectos_periodo FOREIGN KEY (periodo_id) REFERENCES periodos_academicos (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 8. asignaciones (tutor de proyecto)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS asignaciones (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    proyecto_id INT UNSIGNED NOT NULL,
    docente_id INT UNSIGNED NOT NULL,
    fecha_asignacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('activa','finalizada') NOT NULL DEFAULT 'activa',
    PRIMARY KEY (id),
    UNIQUE KEY uq_asignaciones_proyecto (proyecto_id),
    CONSTRAINT fk_asignaciones_proyecto FOREIGN KEY (proyecto_id) REFERENCES proyectos (id) ON DELETE CASCADE,
    CONSTRAINT fk_asignaciones_docente FOREIGN KEY (docente_id) REFERENCES docentes (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 9. documentos
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS documentos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    proyecto_id INT UNSIGNED NOT NULL,
    etapa_id INT UNSIGNED NOT NULL,
    tipo ENUM('trabajo','final') NOT NULL DEFAULT 'trabajo',
    nombre_original VARCHAR(255) NOT NULL,
    ruta VARCHAR(500) NOT NULL,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    subido_por INT UNSIGNED NOT NULL,
    estado ENUM('enviado','en_revision','con_observaciones','en_correccion','aprobado','final') NOT NULL DEFAULT 'enviado',
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_documentos_proyecto (proyecto_id),
    KEY idx_documentos_etapa (etapa_id),
    CONSTRAINT fk_documentos_proyecto FOREIGN KEY (proyecto_id) REFERENCES proyectos (id) ON DELETE CASCADE,
    CONSTRAINT fk_documentos_etapa FOREIGN KEY (etapa_id) REFERENCES etapas (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 10. observaciones
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS observaciones (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    documento_id INT UNSIGNED NOT NULL,
    proyecto_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    texto_seleccionado TEXT NULL,
    comentario TEXT NOT NULL,
    posicion_inicio INT UNSIGNED NULL,
    posicion_fin INT UNSIGNED NULL,
    estado ENUM('pendiente','en_correccion','corregida','aprobada') NOT NULL DEFAULT 'pendiente',
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_obs_documento (documento_id),
    KEY idx_obs_proyecto (proyecto_id),
    CONSTRAINT fk_obs_documento FOREIGN KEY (documento_id) REFERENCES documentos (id) ON DELETE CASCADE,
    CONSTRAINT fk_obs_proyecto FOREIGN KEY (proyecto_id) REFERENCES proyectos (id) ON DELETE CASCADE,
    CONSTRAINT fk_obs_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 11. respuestas_observaciones
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS respuestas_observaciones (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    observacion_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    mensaje TEXT NOT NULL,
    tipo ENUM('respuesta','correccion') NOT NULL DEFAULT 'respuesta',
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_rsp_observacion (observacion_id),
    CONSTRAINT fk_rsp_observacion FOREIGN KEY (observacion_id) REFERENCES observaciones (id) ON DELETE CASCADE,
    CONSTRAINT fk_rsp_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 12. actividades
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS actividades (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    proyecto_id INT UNSIGNED NOT NULL,
    etapa_id INT UNSIGNED NULL,
    tipo ENUM('entrega','revision','correccion','aprobacion') NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    responsable_id INT UNSIGNED NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_limite DATE NOT NULL,
    estado ENUM('pendiente','en_curso','completada','vencida') NOT NULL DEFAULT 'pendiente',
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_act_proyecto (proyecto_id),
    KEY idx_act_responsable (responsable_id),
    CONSTRAINT fk_act_proyecto FOREIGN KEY (proyecto_id) REFERENCES proyectos (id) ON DELETE CASCADE,
    CONSTRAINT fk_act_etapa FOREIGN KEY (etapa_id) REFERENCES etapas (id) ON DELETE SET NULL,
    CONSTRAINT fk_act_responsable FOREIGN KEY (responsable_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 13. plazos
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS plazos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    proyecto_id INT UNSIGNED NOT NULL,
    actividad_id INT UNSIGNED NULL,
    descripcion VARCHAR(255) NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_limite DATE NOT NULL,
    estado ENUM('activo','completado','vencido') NOT NULL DEFAULT 'activo',
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_plazos_proyecto (proyecto_id),
    CONSTRAINT fk_plazos_proyecto FOREIGN KEY (proyecto_id) REFERENCES proyectos (id) ON DELETE CASCADE,
    CONSTRAINT fk_plazos_actividad FOREIGN KEY (actividad_id) REFERENCES actividades (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 14. calendario
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS calendario (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    proyecto_id INT UNSIGNED NOT NULL,
    etapa_id INT UNSIGNED NULL,
    usuario_id INT UNSIGNED NULL,
    titulo VARCHAR(200) NOT NULL,
    descripcion VARCHAR(500) NULL,
    fecha_evento DATETIME NOT NULL,
    tipo ENUM('entrega','revision','correccion','limite','otro') NOT NULL DEFAULT 'otro',
    estado ENUM('pendiente','completado','vencido') NOT NULL DEFAULT 'pendiente',
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_cal_proyecto (proyecto_id),
    KEY idx_cal_usuario (usuario_id),
    CONSTRAINT fk_cal_proyecto FOREIGN KEY (proyecto_id) REFERENCES proyectos (id) ON DELETE CASCADE,
    CONSTRAINT fk_cal_etapa FOREIGN KEY (etapa_id) REFERENCES etapas (id) ON DELETE SET NULL,
    CONSTRAINT fk_cal_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 15. notificaciones
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notificaciones (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id INT UNSIGNED NOT NULL,
    proyecto_id INT UNSIGNED NULL,
    titulo VARCHAR(200) NOT NULL,
    mensaje VARCHAR(500) NOT NULL,
    tipo VARCHAR(50) NOT NULL DEFAULT 'info',
    leida TINYINT(1) NOT NULL DEFAULT 0,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_notif_usuario (usuario_id, leida),
    CONSTRAINT fk_notif_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_notif_proyecto FOREIGN KEY (proyecto_id) REFERENCES proyectos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 16. historial_acciones
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS historial_acciones (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id INT UNSIGNED NOT NULL,
    accion VARCHAR(120) NOT NULL,
    descripcion VARCHAR(500) NULL,
    proyecto_id INT UNSIGNED NULL,
    etapa_id INT UNSIGNED NULL,
    documento_id INT UNSIGNED NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_hist_proyecto (proyecto_id),
    KEY idx_hist_usuario (usuario_id),
    CONSTRAINT fk_hist_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id),
    CONSTRAINT fk_hist_proyecto FOREIGN KEY (proyecto_id) REFERENCES proyectos (id) ON DELETE SET NULL,
    CONSTRAINT fk_hist_etapa FOREIGN KEY (etapa_id) REFERENCES etapas (id) ON DELETE SET NULL,
    CONSTRAINT fk_hist_documento FOREIGN KEY (documento_id) REFERENCES documentos (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 16.5 repositorio (archivo institucional)
-- ------------------------------------------------------------
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

-- ------------------------------------------------------------
-- 17. configuraciones
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS configuraciones (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    clave VARCHAR(100) NOT NULL,
    valor VARCHAR(500) NULL,
    descripcion VARCHAR(255) NULL,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_config_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 18. password_resets (recuperación de contraseña)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS password_resets (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(150) NOT NULL,
    token VARCHAR(64) NOT NULL,
    expira_en DATETIME NOT NULL,
    usado TINYINT(1) NOT NULL DEFAULT 0,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_resets_token (token),
    KEY idx_resets_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
