<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Calendario;

class DashboardController extends Controller
{
    /** Redirige a la sección inicial según el rol */
    public function index(): void
    {
        $role = Auth::role();

        $stats = match ($role) {
            'admin' => $this->adminStats(),
            'docente' => $this->docenteStats(),
            'estudiante' => $this->estudianteStats(),
            default => [],
        };

        $eventos = (new Calendario())->proximos($role, Auth::id(), 5);

        $pendientes = $role === 'docente' ? $this->docentePendientes() : [];
        $resumen = $role === 'docente' ? $this->docenteResumen() : [];
        $misProyectos = $role === 'estudiante' ? $this->estudianteProyectos() : [];

        $this->view('dashboard/index', [
            'title' => 'Panel principal',
            'stats' => $stats,
            'eventos' => $eventos,
            'pendientes' => $pendientes,
            'resumen' => $resumen,
            'misProyectos' => $misProyectos,
        ]);
    }

    private function adminStats(): array
    {
        $db = Database::getConnection();
        $q = fn (string $sql) => (int) $db->query($sql)->fetchColumn();

        return [
            'total_estudiantes' => $q("SELECT COUNT(*) FROM estudiantes"),
            'total_docentes' => $q("SELECT COUNT(*) FROM docentes"),
            'total_proyectos' => $q("SELECT COUNT(*) FROM proyectos"),
            'proyectos_titulacion' => $q("SELECT COUNT(*) FROM proyectos p JOIN tipos_proyecto t ON t.id = p.tipo_proyecto_id WHERE t.nombre = 'Titulación'"),
            'proyectos_vinculacion' => $q("SELECT COUNT(*) FROM proyectos p JOIN tipos_proyecto t ON t.id = p.tipo_proyecto_id WHERE t.nombre = 'Vinculación'"),
            'proyectos_pis' => $q("SELECT COUNT(*) FROM proyectos p JOIN tipos_proyecto t ON t.id = p.tipo_proyecto_id WHERE t.nombre = 'PIS'"),
            'proyectos_en_revision' => $q("SELECT COUNT(*) FROM proyectos WHERE estado IN ('enviado','en_revision','reenviado')"),
            'proyectos_con_observaciones' => $q("SELECT COUNT(*) FROM proyectos WHERE estado IN ('con_observaciones','en_correccion')"),
            'proyectos_aprobados' => $q("SELECT COUNT(*) FROM proyectos WHERE estado = 'aprobado'"),
            'proyectos_vencidos' => $q("SELECT COUNT(*) FROM proyectos WHERE estado = 'vencido'"),
            'proyectos_finalizados' => $q("SELECT COUNT(*) FROM proyectos WHERE estado = 'finalizado'"),
        ];
    }

    private function docenteStats(): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "SELECT COUNT(DISTINCT a.proyecto_id)
             FROM asignaciones a
             JOIN docentes d ON d.id = a.docente_id
             WHERE d.usuario_id = ? AND a.estado = 'activa'"
        );
        $stmt->execute([Auth::id()]);

        return [
            'proyectos_activos' => (int) $stmt->fetchColumn(),
        ];
    }

    /** Resumen de proyectos activos del docente según su estado */
    private function docenteResumen(): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "SELECT p.estado, COUNT(*) AS total
             FROM proyectos p
             JOIN asignaciones a ON a.proyecto_id = p.id AND a.estado = 'activa'
             JOIN docentes d ON d.id = a.docente_id
             WHERE d.usuario_id = ? AND p.estado NOT IN ('finalizado', 'vencido')
             GROUP BY p.estado"
        );
        $stmt->execute([Auth::id()]);

        $resumen = [
            'por_revisar' => 0,
            'con_observaciones' => 0,
            'aprobados' => 0,
        ];
        foreach ($stmt->fetchAll() as $row) {
            if (in_array($row['estado'], ['enviado', 'en_revision', 'reenviado'], true)) {
                $resumen['por_revisar'] += (int) $row['total'];
            } elseif (in_array($row['estado'], ['con_observaciones', 'en_correccion'], true)) {
                $resumen['con_observaciones'] += (int) $row['total'];
            } elseif ($row['estado'] === 'aprobado') {
                $resumen['aprobados'] += (int) $row['total'];
            }
        }
        return $resumen;
    }

    /** Pendientes de acción para el docente: documentos a revisar y observaciones en curso */
    private function docentePendientes(): array
    {
        $db = Database::getConnection();

        // Documentos de trabajo subidos por el estudiante que aún no se aprueban
        $docsStmt = $db->prepare(
            "SELECT d.id, d.nombre_original, d.version, d.estado AS doc_estado, d.creado_en,
                    p.codigo, p.nombre AS proyecto, et.nombre AS etapa
             FROM documentos d
             INNER JOIN proyectos p ON p.id = d.proyecto_id
             INNER JOIN asignaciones a ON a.proyecto_id = p.id AND a.estado = 'activa'
             INNER JOIN docentes dd ON dd.id = a.docente_id
             INNER JOIN etapas et ON et.id = d.etapa_id
             WHERE dd.usuario_id = ?
               AND d.tipo = 'trabajo'
               AND d.estado = 'enviado'
             ORDER BY d.creado_en DESC"
        );
        $docsStmt->execute([Auth::id()]);

        // Observaciones que el estudiante ya corrigió y el tutor debe aprobar
        $obsStmt = $db->prepare(
            "SELECT o.id, o.comentario, o.estado AS obs_estado, o.creado_en,
                    d.nombre_original, p.codigo, p.nombre AS proyecto, et.nombre AS etapa
             FROM observaciones o
             INNER JOIN documentos d ON d.id = o.documento_id
             INNER JOIN proyectos p ON p.id = o.proyecto_id
             INNER JOIN asignaciones a ON a.proyecto_id = p.id AND a.estado = 'activa'
             INNER JOIN docentes dd ON dd.id = a.docente_id
             INNER JOIN etapas et ON et.id = d.etapa_id
             WHERE dd.usuario_id = ?
               AND o.estado IN ('pendiente', 'en_correccion')
             ORDER BY o.creado_en DESC"
        );
        $obsStmt->execute([Auth::id()]);

        // Actividades vencidas o por vencer en sus proyectos
        $actStmt = $db->prepare(
            "SELECT a.id, a.descripcion, a.estado AS act_estado, a.fecha_limite,
                    p.codigo, p.nombre AS proyecto
             FROM actividades a
             INNER JOIN proyectos p ON p.id = a.proyecto_id
             INNER JOIN asignaciones asg ON asg.proyecto_id = p.id AND asg.estado = 'activa'
             INNER JOIN docentes dd ON dd.id = asg.docente_id
             WHERE dd.usuario_id = ?
               AND a.estado IN ('pendiente', 'en_curso', 'vencida')
               AND a.fecha_limite < DATE_ADD(CURDATE(), INTERVAL 7 DAY)
             ORDER BY a.fecha_limite ASC
             LIMIT 10"
        );
        $actStmt->execute([Auth::id()]);

        return [
            'documentos' => $docsStmt->fetchAll(),
            'observaciones' => $obsStmt->fetchAll(),
            'actividades' => $actStmt->fetchAll(),
        ];
    }

    private function estudianteStats(): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM proyectos p
             JOIN estudiantes e ON e.id = p.estudiante_id
             WHERE e.usuario_id = ?"
        );
        $stmt->execute([Auth::id()]);

        return [
            'mis_proyectos' => (int) $stmt->fetchColumn(),
        ];
    }

    /** Proyectos del estudiante con detalles de avance para el dashboard */
    private function estudianteProyectos(): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "SELECT p.id, p.codigo, p.nombre, p.estado, p.porcentaje_avance,
                    p.fecha_limite, t.nombre AS tipo_proyecto,
                    CONCAT(ud.nombres, ' ', ud.apellidos) AS tutor,
                    es.nombre AS etapa_actual
             FROM proyectos p
             INNER JOIN tipos_proyecto t ON t.id = p.tipo_proyecto_id
             INNER JOIN estudiantes e ON e.id = p.estudiante_id
             LEFT JOIN asignaciones a ON a.proyecto_id = p.id AND a.estado = 'activa'
             LEFT JOIN docentes d ON d.id = a.docente_id
             LEFT JOIN usuarios ud ON ud.id = d.usuario_id
             LEFT JOIN etapas es ON es.id = p.etapa_actual_id
             WHERE e.usuario_id = ?
             ORDER BY p.ultima_actualizacion DESC"
        );
        $stmt->execute([Auth::id()]);

        return $stmt->fetchAll();
    }
}
