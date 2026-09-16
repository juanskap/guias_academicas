<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Helpers\Consolidado;
use App\Models\Proyecto;

/**
 * Documentos unificados del proyecto (Perfil y Proyecto), generados al 100%.
 */
class ConsolidadoController extends Controller
{
    /** Página con los documentos unificados del proyecto */
    public function ver(int $proyectoId): void
    {
        $proyecto = (new Proyecto())->detail($proyectoId);
        if (!$proyecto || !$this->puedeAcceder($proyectoId)) {
            flash('error', 'No tienes acceso a este proyecto.');
            redirect_to('proyectos');
        }

        $consolidado = new Consolidado();
        $completo = (float) $proyecto['porcentaje_avance'] >= 100;

        // Si está al 100% y no existen, generar automáticamente
        if ($completo && !$consolidado->generados($proyectoId)) {
            $consolidado->generar($proyectoId);
        }

        $this->view('documentos/consolidado', [
            'title' => 'Documento unificado',
            'proyecto' => $proyecto,
            'completo' => $completo,
            'archivos' => [
                'perfil' => [
                    'pdf' => $consolidado->archivo($proyectoId, 'perfil', 'pdf') !== null,
                    'docx' => $consolidado->archivo($proyectoId, 'perfil', 'docx') !== null,
                ],
                'proyecto' => [
                    'pdf' => $consolidado->archivo($proyectoId, 'proyecto', 'pdf') !== null,
                    'docx' => $consolidado->archivo($proyectoId, 'proyecto', 'docx') !== null,
                ],
            ],
            'fechaGeneracion' => $consolidado->fechaGeneracion($proyectoId),
        ]);
    }

    /** Regenera los documentos unificados (admin/docente) */
    public function generar(int $proyectoId): void
    {
        $this->requiereGestor();

        if (!Request::csrfValidate()) {
            flash('error', 'La sesión expiró, inténtalo de nuevo.');
            redirect_to('consolidado/ver/' . $proyectoId);
        }

        $proyecto = (new Proyecto())->detail($proyectoId);
        if (!$proyecto || !$this->puedeAcceder($proyectoId)) {
            flash('error', 'No tienes acceso a este proyecto.');
            redirect_to('proyectos');
        }

        if ((float) $proyecto['porcentaje_avance'] < 100) {
            flash('error', 'El proyecto debe estar al 100% (todas las etapas aprobadas) para generar el documento unificado.');
            redirect_to('consolidado/ver/' . $proyectoId);
        }

        $res = (new Consolidado())->generar($proyectoId);
        if ($res === null) {
            flash('error', 'No se pudieron generar los documentos unificados.');
        } else {
            flash('success', 'Documentos unificados generados correctamente.');
        }
        redirect_to('consolidado/ver/' . $proyectoId);
    }

    /** Descarga un archivo consolidado */
    public function descargar(int $proyectoId, string $tipo, string $formato): void
    {
        $this->servir($proyectoId, $tipo, $formato, false);
    }

    /** Previsualiza un archivo consolidado (inline) */
    public function previsualizar(int $proyectoId, string $tipo, string $formato): void
    {
        $this->servir($proyectoId, $tipo, $formato, true);
    }

    // ---------- Privados ----------

    private function servir(int $proyectoId, string $tipo, string $formato, bool $inline): void
    {
        if (!$this->puedeAcceder($proyectoId)) {
            http_response_code(403);
            exit('No autorizado.');
        }

        $ruta = (new Consolidado())->archivo($proyectoId, $tipo, $formato);
        if ($ruta === null) {
            http_response_code(404);
            exit('El documento no existe o aún no se ha generado.');
        }

        $mimes = [
            'pdf' => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];
        $nombre = $tipo . '_' . $proyectoId . '.' . $formato;

        header('Content-Type: ' . ($mimes[$formato] ?? 'application/octet-stream'));
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $nombre . '"');
        header('Content-Length: ' . filesize($ruta));
        readfile($ruta);
        exit;
    }

    private function puedeAcceder(int $proyectoId): bool
    {
        $rol = Auth::role();
        $uid = Auth::id();
        if ($rol === null || $uid === null) {
            return false;
        }
        return (new Proyecto())->puedeVer($proyectoId, $rol, $uid);
    }

    private function requiereGestor(): void
    {
        $rol = Auth::role();
        if ($rol !== 'admin' && $rol !== 'docente') {
            http_response_code(403);
            require VIEW_PATH . '/errors/403.php';
            exit;
        }
    }
}