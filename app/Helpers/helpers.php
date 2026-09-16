<?php

/**
 * Funciones auxiliares globales
 */

/** Crea (si no existe) y devuelve una carpeta temporal propia de la app */
function temp_dir(string $nombre): string
{
    $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sigep';
    if (!is_dir($base)) {
        mkdir($base, 0777, true);
    }
    $dir = $base . DIRECTORY_SEPARATOR . $nombre;
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    return $dir;
}

/** Escapa texto para salida HTML */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** Genera una URL absoluta dentro de la aplicación */
function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/** Genera una URL a un asset estático */
function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

/** Redirige a una ruta interna y finaliza la ejecución */
function redirect_to(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

/** Devuelve el valor anterior de un campo de formulario (para validación) */
function old(string $key, string $default = ''): string
{
    return $_SESSION['old'][$key] ?? $default;
}

/** Muestra un mensaje flash y lo elimina de la sesión */
function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    $value = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $value;
}

/** Devuelve la fecha y hora actuales en formato de base de datos */
function now(): string
{
    return date('Y-m-d H:i:s');
}

/** Formatea una fecha de base de datos a formato legible */
function format_date(?string $datetime): string
{
    if (!$datetime) return '—';
    $ts = strtotime($datetime);
    return $ts ? date('d/m/Y H:i', $ts) : '—';
}

/** Calcula días restantes entre hoy y una fecha límite */
function dias_restantes(?string $fecha_limite): ?int
{
    if (!$fecha_limite) return null;
    $hoy = new DateTime('today');
    $limite = new DateTime($fecha_limite);
    return (int) $hoy->diff($limite)->format('%r%a');
}

/** Traduce un estado a una clase de color para badges */
function estado_badge(string $estado): string
{
    $map = [
        'activo'       => 'bg-green-100 text-green-700',
        'inactivo'     => 'bg-gray-200 text-gray-600',
        'borrador'     => 'bg-gray-200 text-gray-700',
        'enviado'      => 'bg-blue-100 text-blue-700',
        'en_revision'  => 'bg-yellow-100 text-yellow-700',
        'con_observaciones' => 'bg-orange-100 text-orange-700',
        'en_correccion' => 'bg-purple-100 text-purple-700',
        'reenviado'    => 'bg-cyan-100 text-cyan-700',
        'aprobado'     => 'bg-green-100 text-green-700',
        'finalizado'   => 'bg-emerald-100 text-emerald-700',
        'vencido'      => 'bg-red-100 text-red-700',
        'pendiente'    => 'bg-gray-200 text-gray-600',
        'en_curso'     => 'bg-cyan-100 text-cyan-700',
        'completada'   => 'bg-green-100 text-green-700',
        'vencida'      => 'bg-red-100 text-red-700',
        'corregida'    => 'bg-green-100 text-green-700',
        'aprobada'     => 'bg-emerald-100 text-emerald-700',
        'completado'   => 'bg-green-100 text-green-700',
    ];
    return $map[$estado] ?? 'bg-gray-200 text-gray-600';
}

/** Etiqueta explícita (para el estudiante) del estado de un documento de etapa */
function documento_estado_label(string $estado): string
{
    $map = [
        'enviado'           => 'Enviado · esperando revisión del tutor',
        'en_revision'       => 'En revisión por el tutor',
        'con_observaciones' => 'Revisado · el tutor dejó observaciones',
        'en_correccion'     => 'Revisado · en corrección',
        'aprobado'          => 'Revisado y aprobado',
        'final'             => 'Documento final aprobado',
    ];
    return $map[$estado] ?? ucwords(str_replace('_', ' ', $estado));
}

/** Devuelve el campo oculto CSRF listo para formularios POST */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(\App\Core\Request::csrfToken()) . '">';
}

/** Crea notificaciones en la app para una lista de usuarios (omite al actor) */
function notificar(array $destinatarios, string $titulo, string $mensaje, ?int $proyectoId = null, string $tipo = 'info', ?int $excepto = null): void
{
    $modelo = new \App\Models\Notificacion();
    foreach ($destinatarios as $usuarioId) {
        $usuarioId = (int) $usuarioId;
        if ($usuarioId <= 0 || $usuarioId === (int) $excepto) {
            continue;
        }
        $modelo->crear($usuarioId, $titulo, $mensaje, $proyectoId, $tipo);
    }
}

/** Envía un correo en HTML. Devuelve true si se pudo entregar. */
function enviar_correo(string $para, string $asunto, string $cuerpoHtml): bool
{
    if (!MAIL_ENABLED) {
        return false;
    }
    $mailer = new \App\Core\Mailer(
        MAIL_HOST,
        MAIL_PORT,
        MAIL_USER,
        MAIL_PASS,
        MAIL_FROM,
        MAIL_FROM_NAME,
        true
    );
    return $mailer->send($para, $asunto, $cuerpoHtml);
}
