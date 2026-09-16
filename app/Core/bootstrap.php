<?php
/**
 * SIGEP - Bootstrap: autoloader, sesión y configuración de errores
 */

// Autoloader PSR-4 (App\ → app/)
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (str_starts_with($class, $prefix)) {
        $relative = substr($class, strlen($prefix));
        $file = APP_PATH . '/' . str_replace('\\', '/', $relative) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// Funciones auxiliares globales
require_once APP_PATH . '/Helpers/helpers.php';

// Librerías de vendor: FPDF (clase base) y FPDI 2.x (autoload propio setasign\Fpdi)
if (file_exists(ROOT_PATH . '/vendor/fpdf/fpdf.php')) {
    require_once ROOT_PATH . '/vendor/fpdf/fpdf.php';
}
if (file_exists(ROOT_PATH . '/vendor/fpdi2/src/autoload.php')) {
    require_once ROOT_PATH . '/vendor/fpdi2/src/autoload.php';
}

// Sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Errores en desarrollo
error_reporting(E_ALL);
ini_set('display_errors', '1');
