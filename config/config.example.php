<?php
/**
 * SIGEP - Configuración general (PLANTILLA)
 * 
 * Copia este archivo como config.php y ajusta los valores.
 */

// URL base de la aplicación (ajustar según el despliegue)
define('BASE_URL', 'http://localhost/guia_academica/public');

define('APP_NAME', 'SIGEP');
define('APP_FULL_NAME', 'Sistema de Gestión y Seguimiento de Proyectos Académicos');
define('APP_VERSION', '1.0.0');

// Base de datos MySQL
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'sigep');
define('DB_USER', 'root');
define('DB_PASS', '');

// Rutas del sistema
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('VIEW_PATH', APP_PATH . '/Views');
define('UPLOAD_PATH', ROOT_PATH . '/storage/uploads');
define('UPLOAD_DOCUMENTOS', UPLOAD_PATH . '/documentos');
define('UPLOAD_FINALES', UPLOAD_PATH . '/finales');
define('UPLOAD_CONSOLIDADOS', UPLOAD_PATH . '/consolidados');
define('UPLOAD_REPOSITORIO', UPLOAD_PATH . '/repositorio');

// Límite de subida de archivos (en bytes) - 25 MB por defecto (ajustable desde Configuración)
define('MAX_FILE_SIZE', 25 * 1024 * 1024);
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'txt', 'odt']);
// Extensiones del repositorio: documentos y comprimidos (código de aplicaciones)
define('ALLOWED_REPO_EXTENSIONS', ['pdf', 'doc', 'docx', 'txt', 'odt', 'xlsx', 'xls', 'pptx', 'ppt', 'zip', 'rar', '7z', 'tar', 'gz']);

// Contraseña por defecto al crear usuarios desde el panel
define('DEFAULT_PASSWORD', 'Istel2026+');

// Correo: SMTP (cambiar MAIL_ENABLED a false para modo local sin envío real)
define('MAIL_ENABLED', false);
define('MAIL_HOST', 'smtp.ejemplo.com');
define('MAIL_PORT', 587);
define('MAIL_USER', 'tu@correo.com');
define('MAIL_PASS', 'tu-contraseña');
define('MAIL_FROM', 'tu@correo.com');
define('MAIL_FROM_NAME', 'SIGEP');
