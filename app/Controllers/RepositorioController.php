<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Models\PeriodoAcademico;
use App\Models\Repositorio;

/**
 * Repositorio institucional de documentos y entregables.
 */
class RepositorioController extends Controller
{
    /** Lista con filtros (cédula, periodo, tipo, carrera, texto, fechas) */
    public function index(): void
    {
        $filtros = [
            'cedula' => trim((string) Request::get('cedula', '')),
            'periodo_id' => (int) Request::get('periodo_id', 0),
            'tipo' => (string) Request::get('tipo', ''),
            'carrera' => trim((string) Request::get('carrera', '')),
            'texto' => trim((string) Request::get('texto', '')),
            'desde' => (string) Request::get('desde', ''),
            'hasta' => (string) Request::get('hasta', ''),
            'historial' => (int) Request::get('historial', 0) ? 1 : 0,
        ];

        $archivos = (new Repositorio())->listar($filtros, Auth::role(), Auth::id());
        $periodos = (new PeriodoAcademico())->all();

        $this->view('repositorio/index', [
            'title' => 'Repositorio institucional',
            'archivos' => $archivos,
            'periodos' => $periodos,
            'tipos' => Repositorio::TIPOS,
            'filtros' => $filtros,
        ]);
    }

    /** Formulario de subida (estudiante, tutor o admin) */
    public function subir(): void
    {
        $proyecto = new \App\Models\Proyecto();
        $rol = Auth::role();
        $uid = Auth::id();

        if ($rol === 'admin') {
            $proyectos = $proyecto->query(
                "SELECT p.id, p.codigo, p.nombre,
                        CONCAT(ue.nombres, ' ', ue.apellidos) AS estudiante
                 FROM proyectos p
                 INNER JOIN estudiantes e ON e.id = p.estudiante_id
                 INNER JOIN usuarios ue ON ue.id = e.usuario_id
                 ORDER BY p.ultima_actualizacion DESC LIMIT 500"
            );
        } elseif ($rol === 'docente') {
            $proyectos = $proyecto->query(
                "SELECT p.id, p.codigo, p.nombre,
                        CONCAT(ue.nombres, ' ', ue.apellidos) AS estudiante
                 FROM proyectos p
                 INNER JOIN estudiantes e ON e.id = p.estudiante_id
                 INNER JOIN usuarios ue ON ue.id = e.usuario_id
                 INNER JOIN asignaciones a ON a.proyecto_id = p.id AND a.estado = 'activa'
                 INNER JOIN docentes d ON d.id = a.docente_id
                 WHERE d.usuario_id = ?
                 ORDER BY p.ultima_actualizacion DESC LIMIT 500",
                [$uid]
            );
        } else {
            $proyectos = $proyecto->query(
                "SELECT p.id, p.codigo, p.nombre
                 FROM proyectos p
                 INNER JOIN estudiantes e ON e.id = p.estudiante_id
                 WHERE e.usuario_id = ?
                 ORDER BY p.ultima_actualizacion DESC LIMIT 500",
                [$uid]
            );
        }

        $tiposManuales = array_filter(
            Repositorio::TIPOS,
            fn ($clave) => $clave !== 'documento_final',
            ARRAY_FILTER_USE_KEY
        );

        $this->view('repositorio/subir', [
            'title' => 'Subir al repositorio',
            'proyectos' => $proyectos,
            'tipos' => $tiposManuales,
        ]);
    }

    /** Procesa la subida al repositorio */
    public function guardar(): void
    {
        if (!Request::csrfValidate()) {
            flash('error', 'La sesión expiró, inténtalo de nuevo.');
            redirect_to('repositorio');
        }

        $proyectoId = (int) Request::post('proyecto_id', 0);
        $tipo = (string) Request::post('tipo', 'anexo');
        $titulo = trim((string) Request::post('titulo', ''));

        $proyecto = (new \App\Models\Proyecto())->detail($proyectoId);
        if (!$proyecto || !$this->puedeSubirAlProyecto((int) $proyecto['id'])) {
            flash('error', 'No tienes acceso al proyecto seleccionado.');
            redirect_to('repositorio/subir');
        }

        if (!array_key_exists($tipo, Repositorio::TIPOS) || $tipo === 'documento_final') {
            flash('error', 'Tipo de archivo no válido.');
            redirect_to('repositorio/subir');
        }
        if ($titulo === '') {
            flash('error', 'El título del archivo es obligatorio.');
            redirect_to('repositorio/subir');
        }

        $file = $_FILES['archivo'] ?? null;
        $error = $this->validarArchivo($file);
        if ($error) {
            flash('error', $error);
            redirect_to('repositorio/subir');
        }

        $ext = mb_strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $nombreGuardado = 'repo_p' . $proyectoId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;

        if (!is_dir(UPLOAD_REPOSITORIO)) {
            mkdir(UPLOAD_REPOSITORIO, 0777, true);
        }

        if (!move_uploaded_file($file['tmp_name'], UPLOAD_REPOSITORIO . '/' . $nombreGuardado)) {
            flash('error', 'No se pudo guardar el archivo.');
            redirect_to('repositorio/subir');
        }

        $estudianteId = (int) $proyecto['estudiante_record_id'];
        $rep = new Repositorio();
        $version = $rep->siguienteVersion($proyectoId, $estudianteId, $tipo, $titulo);

        try {
            $rep->create([
                'proyecto_id' => $proyectoId,
                'periodo_id' => $proyecto['periodo_id'] ? (int) $proyecto['periodo_id'] : null,
                'estudiante_id' => $estudianteId ?: null,
                'grupo' => null,
                'tipo' => $tipo,
                'titulo' => $titulo,
                'nombre_original' => $file['name'],
                'ruta' => $nombreGuardado,
                'formato' => $ext,
                'tamanio' => (int) $file['size'],
                'version' => $version,
                'subido_por' => Auth::id(),
                'estado' => 'activo',
            ]);
        } catch (\Throwable $e) {
            @unlink(UPLOAD_REPOSITORIO . '/' . $nombreGuardado);
            flash('error', 'Error al guardar en el repositorio: ' . $e->getMessage());
            redirect_to('repositorio/subir');
        }

        flash('success', "Archivo guardado en el repositorio (v{$version}).");
        redirect_to('repositorio');
    }

    /** Descarga un archivo del repositorio */
    public function descargar(int $id): void
    {
        $this->servir($id, false);
    }

    /** Previsualiza un archivo del repositorio (inline) */
    public function previsualizar(int $id): void
    {
        $this->servir($id, true);
    }

    /** Eliminación lógica (admin o quien subió) */
    public function eliminar(int $id): void
    {
        if (!Request::csrfValidate()) {
            flash('error', 'La sesión expiró, inténtalo de nuevo.');
            redirect_to('repositorio');
        }

        $archivo = (new Repositorio())->detalle($id);
        if (!$archivo) {
            flash('error', 'El archivo no existe.');
            redirect_to('repositorio');
        }

        $rol = Auth::role();
        if ($rol !== 'admin' && (int) $archivo['subido_por'] !== Auth::id()) {
            flash('error', 'No tienes permiso para eliminar este archivo.');
            redirect_to('repositorio');
        }

        (new Repositorio())->update($id, ['estado' => 'eliminado']);
        flash('success', 'Archivo eliminado del repositorio.');
        redirect_to('repositorio');
    }

    // ---------- Privados ----------

    private function servir(int $id, bool $inline): void
    {
        $archivo = (new Repositorio())->detalle($id);
        if (!$archivo) {
            http_response_code(404);
            exit('El archivo no existe.');
        }

        if (!$this->puedeAcceder($archivo)) {
            http_response_code(403);
            exit('No autorizado.');
        }

        $base = $archivo['tipo'] === 'documento_final' ? UPLOAD_CONSOLIDADOS : UPLOAD_REPOSITORIO;
        $ruta = $base . '/' . $archivo['ruta'];
        if (!is_file($ruta)) {
            http_response_code(404);
            exit('El archivo físico no está disponible.');
        }

        $formato = mb_strtolower($archivo['formato']);
        $sePuedePrevisualizar = in_array($formato, ['pdf', 'txt'], true);

        if ($inline && $sePuedePrevisualizar) {
            header('Content-Type: ' . ($formato === 'pdf' ? 'application/pdf' : 'text/plain; charset=utf-8'));
            header('Content-Disposition: inline; filename="' . $archivo['nombre_original'] . '"');
        } else {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $archivo['nombre_original'] . '"');
        }
        header('Content-Length: ' . filesize($ruta));
        readfile($ruta);
        exit;
    }

    private function validarArchivo(?array $file): ?string
    {
        if (!$file || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return 'Selecciona un archivo para subir.';
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return 'Error al subir el archivo.';
        }
        $maxMb = (new \App\Models\Configuracion())->getInt('max_upload_mb', (int) (MAX_FILE_SIZE / 1024 / 1024));
        $maxBytes = $maxMb < 1 ? MAX_FILE_SIZE : $maxMb * 1024 * 1024;
        if ($file['size'] > $maxBytes) {
            return 'El archivo supera el tamaño máximo permitido (' . max(1, $maxMb) . ' MB).';
        }
        $ext = mb_strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_REPO_EXTENSIONS, true)) {
            return 'Tipo de archivo no permitido. Usa: ' . implode(', ', ALLOWED_REPO_EXTENSIONS);
        }
        return null;
    }

    private function puedeSubirAlProyecto(int $proyectoId): bool
    {
        $proyecto = (new \App\Models\Proyecto())->detail($proyectoId);
        if (!$proyecto) {
            return false;
        }
        $rol = Auth::role();
        if ($rol === 'admin') {
            return true;
        }
        if ($rol === 'estudiante') {
            return (int) $proyecto['estudiante_record_id'] !== 0 &&
                $this->esMiRegistro('estudiantes', (int) $proyecto['estudiante_record_id']);
        }
        if ($rol === 'docente') {
            return $proyecto['docente_record_id'] !== null &&
                $this->esMiRegistro('docentes', (int) $proyecto['docente_record_id']);
        }
        return false;
    }

    private function esMiRegistro(string $tabla, int $recordId): bool
    {
        $stmt = Database::getConnection()->prepare("SELECT id FROM `{$tabla}` WHERE id = ? AND usuario_id = ? LIMIT 1");
        $stmt->execute([$recordId, Auth::id()]);
        return (bool) $stmt->fetchColumn();
    }

    private function puedeAcceder(array $archivo): bool
    {
        return (new Repositorio())->puedeAcceder($archivo, Auth::role(), Auth::id());
    }
}