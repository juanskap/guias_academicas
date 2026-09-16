<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Middlewares\AuthMiddleware;
use App\Models\Documento;
use App\Models\Etapa;
use App\Models\Notificacion;
use App\Models\Proyecto;

/**
 * Fase 4 (demo): documentos por etapa y observaciones.
 */
class DocumentoController extends Controller
{
    /** Formulario para subir el documento de una etapa (estudiante) */
    public function subirForm(int $proyectoId, int $etapaId): void
    {
        AuthMiddleware::requireRole('estudiante');

        $proyecto = (new Proyecto())->detail($proyectoId);
        if (!$proyecto || !$this->esMiProyecto((int) $proyecto['estudiante_record_id'])) {
            flash('error', 'No tienes acceso a este proyecto.');
            redirect_to('proyectos');
        }

        $etapa = (new Etapa())->find($etapaId);
        if (!$etapa || (int) $etapa['tipo_proyecto_id'] !== (int) $proyecto['tipo_proyecto_id']) {
            flash('error', 'Etapa no válida para este proyecto.');
            redirect_to('proyectos/ver/' . $proyectoId);
        }

        if (!(new Proyecto())->etapaDesbloqueada($proyectoId, $etapaId, (int) $proyecto['tipo_proyecto_id'])) {
            flash('error', 'Debes completar y aprobar las etapas anteriores antes de subir documento en esta etapa.');
            redirect_to('proyectos/ver/' . $proyectoId);
        }

        $docActual = (new Documento())->actualDeEtapa($proyectoId, $etapaId, 'trabajo');

        $this->view('documentos/subir', [
            'title' => 'Subir documento — ' . $etapa['nombre'],
            'proyecto' => $proyecto,
            'etapa' => $etapa,
            'docActual' => $docActual,
        ]);
    }

    /** Sube (o reemplaza) el documento de trabajo de una etapa (estudiante) */
    public function subir(): void
    {
        AuthMiddleware::requireRole('estudiante');

        if (!Request::csrfValidate()) {
            flash('error', 'La sesión expiró, inténtalo de nuevo.');
            redirect_to('proyectos');
        }

        $proyectoId = (int) Request::post('proyecto_id', 0);
        $etapaId = (int) Request::post('etapa_id', 0);

        $proyecto = (new Proyecto())->detail($proyectoId);
        if (!$proyecto || !$this->esMiProyecto((int) $proyecto['estudiante_record_id'])) {
            flash('error', 'No tienes acceso a este proyecto.');
            redirect_to('proyectos');
        }

        $etapa = (new Etapa())->find($etapaId);
        if (!$etapa || (int) $etapa['tipo_proyecto_id'] !== (int) $proyecto['tipo_proyecto_id']) {
            flash('error', 'Etapa no válida para este proyecto.');
            redirect_to('proyectos/ver/' . $proyectoId);
        }

        if (!(new Proyecto())->etapaDesbloqueada($proyectoId, $etapaId, (int) $proyecto['tipo_proyecto_id'])) {
            flash('error', 'Debes completar y aprobar las etapas anteriores antes de subir documento en esta etapa.');
            redirect_to('proyectos/ver/' . $proyectoId);
        }

        $file = $_FILES['documento'] ?? null;
        $error = $this->validarArchivo($file);
        if ($error) {
            flash('error', $error);
            redirect_to('proyectos/ver/' . $proyectoId);
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $version = (new Documento())->siguienteVersion($proyectoId, $etapaId, 'trabajo');
        $nombreGuardado = sprintf('p%d_e%d_v%d_%s.%s', $proyectoId, $etapaId, $version, bin2hex(random_bytes(6)), $ext);

        if (!is_dir(UPLOAD_DOCUMENTOS)) {
            mkdir(UPLOAD_DOCUMENTOS, 0777, true);
        }

        if (!move_uploaded_file($file['tmp_name'], UPLOAD_DOCUMENTOS . '/' . $nombreGuardado)) {
            flash('error', 'No se pudo guardar el archivo.');
            redirect_to('proyectos/ver/' . $proyectoId);
        }

        $db = Database::getConnection();
        try {
            $db->beginTransaction();
            $docId = (new Documento())->create([
                'proyecto_id' => $proyectoId,
                'etapa_id' => $etapaId,
                'tipo' => 'trabajo',
                'nombre_original' => $file['name'],
                'ruta' => $nombreGuardado,
                'version' => $version,
                'subido_por' => Auth::id(),
                'estado' => 'enviado',
            ]);

            // Si el proyecto estaba en borrador, pasa a enviado
            if ($proyecto['estado'] === 'borrador') {
                (new Proyecto())->update($proyectoId, ['estado' => 'enviado']);
            }

            $this->registrarHistorial($proyectoId, 'Subida de documento', "Documento subido a etapa {$etapa['nombre']} (v{$version})");
            $db->commit();

            $inv = (new Proyecto())->involucrados($proyectoId);
            $dest = $inv['tutor_usuario_id'] ? [$inv['tutor_usuario_id']] : (new Notificacion())->adminIds();
            notificar($dest, 'Documento subido', "Se subió el documento de la etapa \"{$etapa['nombre']}\" (v{$version}).", $proyectoId, 'documento', Auth::id());

            flash('success', "Documento subido (versión {$version}).");
            redirect_to('documentos/ver/' . $docId);
        } catch (\Throwable $e) {
            $db->rollBack();
            @unlink(UPLOAD_DOCUMENTOS . '/' . $nombreGuardado);
            flash('error', 'Error al registrar el documento: ' . $e->getMessage());
            redirect_to('proyectos/ver/' . $proyectoId);
        }
    }

    /** Detalle del documento con hilo de observaciones */
    public function ver(int $id): void
    {
        $doc = (new Documento())->conObservaciones($id);
        if (!$doc) {
            flash('error', 'Documento no encontrado.');
            redirect_to('proyectos');
        }

        $proyecto = (new Proyecto())->detail((int) $doc['proyecto_id']);
        if (!$proyecto || !$this->canAccess($proyecto)) {
            AuthMiddleware::requireRole('admin');
        }

        $etapa = (new Etapa())->find((int) $doc['etapa_id']);

        $this->view('documentos/ver', [
            'title' => 'Documento — ' . $doc['nombre_original'],
            'documento' => $doc,
            'proyecto' => $proyecto,
            'etapa' => $etapa,
        ]);
    }

    /** Descarga el archivo del documento (con control de acceso) */
    public function descargar(int $id): void
    {
        $doc = (new Documento())->find($id);
        if (!$doc) {
            http_response_code(404);
            exit('Documento no encontrado.');
        }

        $proyecto = (new Proyecto())->detail((int) $doc['proyecto_id']);
        if (!$proyecto || !$this->canAccess($proyecto)) {
            AuthMiddleware::requireRole('admin');
        }

        $directorio = $doc['tipo'] === 'final' ? UPLOAD_FINALES : UPLOAD_DOCUMENTOS;
        $ruta = $directorio . '/' . $doc['ruta'];

        if (!file_exists($ruta)) {
            http_response_code(404);
            exit('El archivo ya no está disponible.');
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($doc['nombre_original']) . '"');
        header('Content-Length: ' . filesize($ruta));
        readfile($ruta);
        exit;
    }

    /** Sirve el archivo en línea para vista previa (PDF/txt) */
    public function previsualizar(int $id): void
    {
        $doc = (new Documento())->find($id);
        if (!$doc) {
            http_response_code(404);
            exit('Documento no encontrado.');
        }

        $proyecto = (new Proyecto())->detail((int) $doc['proyecto_id']);
        if (!$proyecto || !$this->canAccess($proyecto)) {
            AuthMiddleware::requireRole('admin');
        }

        $directorio = $doc['tipo'] === 'final' ? UPLOAD_FINALES : UPLOAD_DOCUMENTOS;
        $ruta = $directorio . '/' . $doc['ruta'];

        if (!file_exists($ruta)) {
            http_response_code(404);
            exit('El archivo ya no está disponible.');
        }

        $ext = strtolower(pathinfo($doc['ruta'], PATHINFO_EXTENSION));

        if ($ext === 'pdf') {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . basename($doc['nombre_original']) . '"');
            header('Content-Length: ' . filesize($ruta));
            readfile($ruta);
            exit;
        }

        if (in_array($ext, ['txt'], true)) {
            header('Content-Type: text/plain; charset=UTF-8');
            header('Content-Disposition: inline');
            header('Content-Length: ' . filesize($ruta));
            readfile($ruta);
            exit;
        }

        // Word/OpenDocument: convertir a PDF con LibreOffice y mostrarlo
        if (in_array($ext, ['docx', 'odt', 'doc', 'rtf'], true)) {
            $pdf = \App\Helpers\OfficeConverter::toPdf($ruta);

            if ($pdf) {
                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="' . basename($doc['nombre_original'], '.' . $ext) . '.pdf"');
                header('Content-Length: ' . filesize($pdf));
                readfile($pdf);
                exit;
            }

            // Fallback: si LibreOffice no está disponible, mostrar texto plano
            if (in_array($ext, ['docx', 'odt'], true)) {
                header('Content-Type: text/html; charset=UTF-8');
                exit($this->textoOffice($ruta, $ext));
            }

            http_response_code(415);
            exit('No se pudo previsualizar este documento. Intenta exportarlo como PDF y subirlo nuevamente.');
        }

        // Formatos no previsualizables
        http_response_code(415);
        exit('Este formato no se puede previsualizar en el navegador.');
    }

    /** Extrae el texto legible de un .docx o .odt (son ZIP con XML interno) */
    private function textoOffice(string $ruta, string $ext): string
    {
        if (!class_exists('ZipArchive') || !extension_loaded('zip')) {
            http_response_code(415);
            return 'La extensión ZIP no está disponible en este servidor.';
        }

        $zip = new \ZipArchive();
        if ($zip->open($ruta) !== true) {
            http_response_code(415);
            return 'No se pudo leer este documento para la vista previa.';
        }

        // .docx → word/document.xml | .odt → content.xml
        $archivoInterno = $ext === 'docx' ? 'word/document.xml' : 'content.xml';
        $xml = $zip->getFromName($archivoInterno);
        $zip->close();

        if ($xml === false) {
            http_response_code(415);
            return 'Formato interno no reconocido.';
        }

        // Extraer párrafos (paragraphs) según el formato
        if ($ext === 'docx') {
            preg_match_all('#<w:p(?:\s[^>]*)?>(.*?)</w:p>#s', $xml, $m);
            $parrafos = $m[1] ?? [];
            foreach ($parrafos as &$p) {
                preg_match_all('#<w:t(?:\s[^>]*)?>(.*?)</w:t>#s', $p, $tm);
                $p = implode('', $tm[1] ?? []);
            }
        } else { // odt
            preg_match_all('#<text:p(?:\s[^>]*)?>(.*?)</text:p>#s', $xml, $m);
            $parrafos = $m[1] ?? [];
            foreach ($parrafos as &$p) {
                $p = preg_replace('#<text:tab[^>]*/>#', "\t", $p);
                $p = strip_tags($p);
            }
        }

        $parrafos = array_map(fn ($p) => trim($p), $parrafos);
        $parrafos = array_values(array_filter($parrafos, fn ($p) => $p !== ''));

        $html = '<div style="font-family:Arial,sans-serif;font-size:14px;line-height:1.6;padding:10px;">';
        $html .= '<p style="background:#fff3cd;border:1px solid #ffc107;color:#856404;padding:8px 12px;border-radius:6px;font-size:13px;">⚠ LibreOffice no está disponible. Mostrando texto sin formato. Para el formato original, sube el documento en PDF (Archivo → Guardar como PDF).</p>';
        if (!$parrafos) {
            $html .= '<p style="color:#999;">No se encontró texto legible en este documento (¿solo imágenes? Usa la descarga para revisar el original).</p>';
        }
        foreach ($parrafos as $p) {
            $html .= '<p>' . nl2br(e($p)) . '</p>';
        }
        $html .= '</div>';
        return $html;
    }

    /** Crea una observación sobre un documento (tutor o admin) */
    public function observar(): void
    {
        if (!Request::csrfValidate()) {
            flash('error', 'La sesión expiró, inténtalo de nuevo.');
            redirect_to('proyectos');
        }

        $doc = (new Documento())->find((int) Request::post('documento_id', 0));
        if (!$doc) {
            flash('error', 'Documento no encontrado.');
            redirect_to('proyectos');
        }

        $proyecto = (new Proyecto())->detail((int) $doc['proyecto_id']);
        if (!$proyecto || !$this->canAccess($proyecto) || !in_array(Auth::role(), ['admin', 'docente'], true)) {
            AuthMiddleware::requireRole('admin');
        }

        $comentario = trim((string) Request::post('comentario', ''));
        $textoSeleccionado = trim((string) Request::post('texto_seleccionado', ''));

        if ($comentario === '') {
            flash('error', 'Escribe un comentario para la observación.');
            redirect_to('documentos/ver/' . $doc['id']);
        }

        (new Documento())->execute(
            "INSERT INTO observaciones (documento_id, proyecto_id, usuario_id, texto_seleccionado, comentario, estado)
             VALUES (?, ?, ?, ?, ?, 'pendiente')",
            [$doc['id'], $doc['proyecto_id'], Auth::id(), $textoSeleccionado ?: null, $comentario]
        );

        // El documento pasa a 'con_observaciones'
        (new Documento())->update((int) $doc['id'], ['estado' => 'con_observaciones']);
        if ($proyecto['estado'] !== 'con_observaciones') {
            (new Proyecto())->update((int) $proyecto['id'], ['estado' => 'con_observaciones']);
        }

        $this->registrarHistorial((int) $doc['proyecto_id'], 'Observación', "Observación añadida al documento {$doc['nombre_original']}");

        $inv = (new Proyecto())->involucrados((int) $doc['proyecto_id']);
        notificar([$inv['estudiante_usuario_id']], 'Nueva observación', "El tutor observó el documento \"{$doc['nombre_original']}\".", (int) $doc['proyecto_id'], 'observacion', Auth::id());

        flash('success', 'Observación registrada.');
        redirect_to('documentos/ver/' . $doc['id']);
    }

    /** Responde a una observación (estudiante o tutor) */
    public function responder(): void
    {
        if (!Request::csrfValidate()) {
            flash('error', 'La sesión expiró, inténtalo de nuevo.');
            redirect_to('proyectos');
        }

        $obsId = (int) Request::post('observacion_id', 0);
        $stmt = Database::getConnection()->prepare("SELECT * FROM observaciones WHERE id = ?");
        $stmt->execute([$obsId]);
        $obs = $stmt->fetch();

        if (!$obs) {
            flash('error', 'Observación no encontrada.');
            redirect_to('proyectos');
        }

        $proyecto = (new Proyecto())->detail((int) $obs['proyecto_id']);
        if (!$proyecto || !$this->canAccess($proyecto)) {
            AuthMiddleware::requireRole('admin');
        }

        $mensaje = trim((string) Request::post('mensaje', ''));
        if ($mensaje === '') {
            flash('error', 'Escribe una respuesta.');
            redirect_to('documentos/ver/' . $obs['documento_id']);
        }

        // Si el estudiante corrige, la observación pasa a 'en_correccion'
        $estado = Auth::role() === 'estudiante' ? 'en_correccion' : $obs['estado'];

        (new Documento())->execute(
            "INSERT INTO respuestas_observaciones (observacion_id, usuario_id, mensaje, tipo) VALUES (?, ?, ?, ?)",
            [$obsId, Auth::id(), $mensaje, Auth::role() === 'estudiante' ? 'correccion' : 'respuesta']
        );

        if ($estado !== $obs['estado']) {
            Database::getConnection()->prepare("UPDATE observaciones SET estado = ? WHERE id = ?")
                ->execute([$estado, $obsId]);
        }

        $inv = (new Proyecto())->involucrados((int) $obs['proyecto_id']);
        if (Auth::role() === 'estudiante') {
            $dest = $inv['tutor_usuario_id'] ? [$inv['tutor_usuario_id']] : (new Notificacion())->adminIds();
            notificar($dest, 'Corrección enviada', 'El estudiante respondió en el hilo de observaciones.', (int) $obs['proyecto_id'], 'correccion', Auth::id());
        } else {
            notificar([$inv['estudiante_usuario_id']], 'Respuesta del tutor', 'El tutor respondió en el hilo de observaciones.', (int) $obs['proyecto_id'], 'observacion', Auth::id());
        }

        flash('success', 'Respuesta enviada.');
        redirect_to('documentos/ver/' . $obs['documento_id']);
    }

    /** El tutor marca una observación como aprobada */
    public function aprobarObservacion(int $id): void
    {
        $this->cambiarEstadoObservacion($id, 'aprobada');
    }

    /** El tutor aprueba el documento de trabajo → crea el documento final (permanente) */
    public function aprobar(int $id): void
    {
        AuthMiddleware::requireRole('admin', 'docente');

        if (!Request::csrfValidate()) {
            flash('error', 'La sesión expiró, inténtalo de nuevo.');
            redirect_to('proyectos');
        }

        $doc = (new Documento())->find($id);
        if (!$doc || $doc['tipo'] !== 'trabajo') {
            flash('error', 'Documento no válido para aprobar.');
            redirect_to('proyectos');
        }

        $proyecto = (new Proyecto())->detail((int) $doc['proyecto_id']);
        if (!$proyecto || !$this->canAccess($proyecto)) {
            AuthMiddleware::requireRole('admin');
        }

        $rutaTrabajo = UPLOAD_DOCUMENTOS . '/' . $doc['ruta'];
        if (!file_exists($rutaTrabajo)) {
            flash('error', 'El archivo no está disponible.');
            redirect_to('documentos/ver/' . $id);
        }

        if (!is_dir(UPLOAD_FINALES)) {
            mkdir(UPLOAD_FINALES, 0777, true);
        }

        $ext = strtolower(pathinfo($doc['ruta'], PATHINFO_EXTENSION));
        $nombreFinal = 'final_p' . $doc['proyecto_id'] . '_e' . $doc['etapa_id'] . '_' . bin2hex(random_bytes(6)) . '.' . $ext;

        if (!copy($rutaTrabajo, UPLOAD_FINALES . '/' . $nombreFinal)) {
            flash('error', 'No se pudo archivar el documento final.');
            redirect_to('documentos/ver/' . $id);
        }

        $db = Database::getConnection();
        try {
            $db->beginTransaction();

            (new Documento())->update((int) $doc['id'], ['estado' => 'aprobado']);
            (new Documento())->create([
                'proyecto_id' => $doc['proyecto_id'],
                'etapa_id' => $doc['etapa_id'],
                'tipo' => 'final',
                'nombre_original' => $doc['nombre_original'],
                'ruta' => $nombreFinal,
                'version' => 1,
                'subido_por' => Auth::id(),
                'estado' => 'final',
            ]);

            // Avance: etapas aprobadas / total
            $total = (int) $db->query("SELECT COUNT(*) FROM etapas WHERE tipo_proyecto_id = {$proyecto['tipo_proyecto_id']}")->fetchColumn();
            $aprobadas = (int) $db->query(
                "SELECT COUNT(DISTINCT etapa_id) FROM documentos WHERE proyecto_id = {$doc['proyecto_id']} AND tipo = 'final'"
            )->fetchColumn();
            $avance = $total > 0 ? round(($aprobadas / $total) * 100, 2) : 0;

            (new Proyecto())->update((int) $doc['proyecto_id'], [
                'porcentaje_avance' => $avance,
                'estado' => $avance >= 100 ? 'aprobado' : 'en_revision',
                'etapa_actual_id' => $this->siguienteEtapa((int) $doc['etapa_id'], (int) $proyecto['tipo_proyecto_id']),
            ]);

            $this->registrarHistorial((int) $doc['proyecto_id'], 'Aprobación de etapa', "Etapa aprobada y documento final archivado");
            $db->commit();

            $inv = (new Proyecto())->involucrados((int) $doc['proyecto_id']);
            notificar([$inv['estudiante_usuario_id']], 'Etapa aprobada', "La etapa fue aprobada y su documento final se archivó. Avance: {$avance}%.", (int) $doc['proyecto_id'], 'aprobacion', Auth::id());

            flash('success', "Etapa aprobada. Avance: {$avance}%");
            redirect_to('proyectos/ver/' . $doc['proyecto_id']);
        } catch (\Throwable $e) {
            $db->rollBack();
            @unlink(UPLOAD_FINALES . '/' . $nombreFinal);
            flash('error', 'Error al aprobar: ' . $e->getMessage());
            redirect_to('documentos/ver/' . $id);
        }
    }

    // ---------- Privados ----------

    private function cambiarEstadoObservacion(int $id, string $estado): void
    {
        AuthMiddleware::requireRole('admin', 'docente');

        if (!Request::csrfValidate()) {
            flash('error', 'La sesión expiró, inténtalo de nuevo.');
            redirect_to('proyectos');
        }

        $stmt = Database::getConnection()->prepare("SELECT * FROM observaciones WHERE id = ?");
        $stmt->execute([$id]);
        $obs = $stmt->fetch();

        if (!$obs) {
            flash('error', 'Observación no encontrada.');
            redirect_to('proyectos');
        }

        $proyecto = (new Proyecto())->detail((int) $obs['proyecto_id']);
        if (!$proyecto || !$this->canAccess($proyecto)) {
            AuthMiddleware::requireRole('admin');
        }

        Database::getConnection()->prepare("UPDATE observaciones SET estado = ? WHERE id = ?")
            ->execute([$estado, $id]);

        $inv = (new Proyecto())->involucrados((int) $obs['proyecto_id']);
        notificar([$inv['estudiante_usuario_id']], 'Observación aprobada', 'La observación fue aprobada por el tutor.', (int) $obs['proyecto_id'], 'aprobacion', Auth::id());

        flash('success', 'Estado de la observación actualizado.');
        redirect_to('documentos/ver/' . $obs['documento_id']);
    }

    private function siguienteEtapa(int $etapaActualId, int $tipoProyectoId): ?int
    {
        $stmt = Database::getConnection()->prepare(
            "SELECT id FROM etapas WHERE tipo_proyecto_id = ? AND orden > (SELECT orden FROM etapas WHERE id = ?) ORDER BY orden LIMIT 1"
        );
        $stmt->execute([$tipoProyectoId, $etapaActualId]);
        $id = $stmt->fetchColumn();
        return $id ? (int) $id : null;
    }

    private function validarArchivo(?array $file): ?string
    {
        if (!$file || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return 'Selecciona un archivo para subir.';
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return 'Error al subir el archivo.';
        }
        if ($file['size'] > MAX_FILE_SIZE) {
            return 'El archivo supera el tamaño máximo permitido (10 MB).';
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_EXTENSIONS, true)) {
            return 'Tipo de archivo no permitido. Usa: ' . implode(', ', ALLOWED_EXTENSIONS);
        }
        return null;
    }

    private function canAccess(array $proyecto): bool
    {
        $rol = Auth::role();
        if ($rol === 'admin') return true;
        if ($rol === 'estudiante') {
            return $this->esMiProyecto((int) $proyecto['estudiante_record_id']);
        }
        if ($rol === 'docente') {
            return $proyecto['asignacion_id'] !== null &&
                $proyecto['docente_record_id'] !== null &&
                $this->esMiTutoria((int) $proyecto['docente_record_id']);
        }
        return false;
    }

    private function esMiProyecto(int $estudianteRecordId): bool
    {
        $stmt = Database::getConnection()->prepare(
            "SELECT id FROM estudiantes WHERE id = ? AND usuario_id = ? LIMIT 1"
        );
        $stmt->execute([$estudianteRecordId, Auth::id()]);
        return (bool) $stmt->fetchColumn();
    }

    private function esMiTutoria(int $docenteRecordId): bool
    {
        $stmt = Database::getConnection()->prepare(
            "SELECT id FROM docentes WHERE id = ? AND usuario_id = ? LIMIT 1"
        );
        $stmt->execute([$docenteRecordId, Auth::id()]);
        return (bool) $stmt->fetchColumn();
    }

    private function registrarHistorial(int $proyectoId, string $accion, string $descripcion): void
    {
        $stmt = Database::getConnection()->prepare(
            "INSERT INTO historial_acciones (usuario_id, accion, descripcion, proyecto_id) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([Auth::id(), $accion, $descripcion, $proyectoId]);
    }
}
