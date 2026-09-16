<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Middlewares\AuthMiddleware;
use App\Models\Configuracion;

/**
 * Configuración general del sistema (panel de administración).
 */
class ConfiguracionController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        AuthMiddleware::requireRole('admin');
    }

    /** Clave de la configuración de tamaño máximo de subida */
    private const CLAVE_MAX_MB = 'max_upload_mb';

    /** Formulario de configuración */
    public function index(): void
    {
        $conf = new Configuracion();

        $limitePhp = $this->limitePhpMb();
        $actual = $conf->getInt(self::CLAVE_MAX_MB, (int) (MAX_FILE_SIZE / 1024 / 1024));
        if ($actual < 1 || $actual > $limitePhp) {
            $actual = min($limitePhp, (int) (MAX_FILE_SIZE / 1024 / 1024));
        }

        $this->view('configuraciones/index', [
            'title' => 'Configuración',
            'maxMb' => $actual,
            'limitePhpMb' => $limitePhp,
            'limitePhpDetalle' => $this->limitePhpDetalle(),
        ]);
    }

    /** Procesa el guardado de la configuración */
    public function guardar(): void
    {
        if (!Request::csrfValidate()) {
            flash('error', 'La sesión expiró, inténtalo de nuevo.');
            redirect_to('configuracion');
        }

        $maxMb = (int) Request::post('max_upload_mb', 0);
        $limitePhp = $this->limitePhpMb();

        if ($maxMb < 1) {
            flash('error', 'El tamaño mínimo permitido es 1 MB.');
            redirect_to('configuracion');
        }
        if ($maxMb > $limitePhp) {
            flash('error', "El servidor PHP solo permite hasta {$limitePhp} MB por subida.");
            redirect_to('configuracion');
        }

        $conf = new Configuracion();
        $conf->set(
            self::CLAVE_MAX_MB,
            (string) $maxMb,
            'Tamaño máximo de subida de documentos en megabytes (1-' . $limitePhp . ' MB según php.ini).'
        );

        $this->registrarHistorial('Configuración', "Tamaño máximo de subida actualizado a {$maxMb} MB");
        flash('success', "Tamaño máximo de subida actualizado a {$maxMb} MB.");
        redirect_to('configuracion');
    }

    /** Límite real de PHP en MB (el menor entre upload_max_filesize y post_max_size) */
    private function limitePhpMb(): int
    {
        $uploads = $this->iniMb('upload_max_filesize');
        $post = $this->iniMb('post_max_size');
        $efectivo = min(max($uploads, 0), max($post, 0));
        return $efectivo > 0 ? $efectivo : 10;
    }

    private function iniMb(string $directiva): int
    {
        $valor = (string) ini_get($directiva);
        if ($valor === '') {
            return 0;
        }
        if (preg_match('/(\d+)\s*(K|M|G)?/i', trim($valor), $m)) {
            $num = (int) $m[1];
            $unidad = strtoupper($m[2] ?? '');
            return match ($unidad) {
                'G' => $num * 1024,
                'M' => $num,
                'K' => (int) round($num / 1024),
                default => $num,
            };
        }
        return 0;
    }

    /** Detalle legible del límite de PHP para la vista */
    private function limitePhpDetalle(): string
    {
        return 'upload_max_filesize = ' . ini_get('upload_max_filesize')
            . ' · post_max_size = ' . ini_get('post_max_size');
    }

    private function registrarHistorial(string $accion, string $descripcion): void
    {
        Database::getConnection()
            ->prepare('INSERT INTO historial_acciones (usuario_id, accion, descripcion) VALUES (?, ?, ?)')
            ->execute([Auth::id(), $accion, $descripcion]);
    }
}