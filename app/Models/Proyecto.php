<?php

namespace App\Models;

use App\Core\Model;

class Proyecto extends Model
{
    protected string $table = 'proyectos';

    /** Proyectos visibles según el rol del usuario */
    public function visibleByRole(string $rol, int $usuarioId): array
    {
        if ($rol === 'admin') {
            return $this->query(
                "SELECT p.id, p.codigo, p.nombre, p.estado, p.porcentaje_avance,
                        p.fecha_limite, p.ultima_actualizacion,
                        t.nombre AS tipo_proyecto,
                        CONCAT(ue.nombres, ' ', ue.apellidos) AS estudiante,
                        e.carrera,
                        CONCAT(ud.nombres, ' ', ud.apellidos) AS tutor,
                        es.nombre AS etapa_actual
                 FROM proyectos p
                 INNER JOIN tipos_proyecto t ON t.id = p.tipo_proyecto_id
                 INNER JOIN estudiantes e ON e.id = p.estudiante_id
                 INNER JOIN usuarios ue ON ue.id = e.usuario_id
                 LEFT JOIN asignaciones a ON a.proyecto_id = p.id AND a.estado = 'activa'
                 LEFT JOIN docentes d ON d.id = a.docente_id
                 LEFT JOIN usuarios ud ON ud.id = d.usuario_id
                 LEFT JOIN etapas es ON es.id = p.etapa_actual_id
                 ORDER BY p.ultima_actualizacion DESC"
            );
        }

        if ($rol === 'docente') {
            return $this->query(
                "SELECT p.id, p.codigo, p.nombre, p.estado, p.porcentaje_avance,
                        p.fecha_limite, p.ultima_actualizacion,
                        t.nombre AS tipo_proyecto,
                        CONCAT(ue.nombres, ' ', ue.apellidos) AS estudiante,
                        e.carrera,
                        es.nombre AS etapa_actual
                 FROM proyectos p
                 INNER JOIN tipos_proyecto t ON t.id = p.tipo_proyecto_id
                 INNER JOIN estudiantes e ON e.id = p.estudiante_id
                 INNER JOIN usuarios ue ON ue.id = e.usuario_id
                 INNER JOIN asignaciones a ON a.proyecto_id = p.id AND a.estado = 'activa'
                 INNER JOIN docentes d ON d.id = a.docente_id
                 LEFT JOIN etapas es ON es.id = p.etapa_actual_id
                 WHERE d.usuario_id = ?
                 ORDER BY p.ultima_actualizacion DESC",
                [$usuarioId]
            );
        }

        // estudiante
        return $this->query(
            "SELECT p.id, p.codigo, p.nombre, p.estado, p.porcentaje_avance,
                    p.fecha_limite, p.ultima_actualizacion,
                    t.nombre AS tipo_proyecto,
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
             ORDER BY p.ultima_actualizacion DESC",
            [$usuarioId]
        );
    }

    /** Detalle completo de un proyecto (con permisos) */
    public function detail(int $id): ?array
    {
        $rows = $this->query(
            "SELECT p.*, t.nombre AS tipo_proyecto,
                    CONCAT(ue.nombres, ' ', ue.apellidos) AS estudiante_nombre,
                    e.id AS estudiante_record_id, e.codigo AS estudiante_codigo, e.carrera,
                    CONCAT(ud.nombres, ' ', ud.apellidos) AS tutor_nombre,
                    d.id AS docente_record_id, a.id AS asignacion_id,
                    es.nombre AS etapa_actual_nombre
             FROM proyectos p
             INNER JOIN tipos_proyecto t ON t.id = p.tipo_proyecto_id
             INNER JOIN estudiantes e ON e.id = p.estudiante_id
             INNER JOIN usuarios ue ON ue.id = e.usuario_id
             LEFT JOIN asignaciones a ON a.proyecto_id = p.id AND a.estado = 'activa'
             LEFT JOIN docentes d ON d.id = a.docente_id
             LEFT JOIN usuarios ud ON ud.id = d.usuario_id
             LEFT JOIN etapas es ON es.id = p.etapa_actual_id
             WHERE p.id = ? LIMIT 1",
            [$id]
        );
        return $rows[0] ?? null;
    }

    /** Etapas del proyecto con su estado de documento aprobado */
    public function etapasConEstado(int $proyectoId, int $tipoProyectoId): array
    {
        return $this->query(
            "SELECT et.id, et.nombre, et.orden,
                    CASE WHEN df.id IS NOT NULL THEN 'aprobada'
                         WHEN dw.id IS NOT NULL THEN dw.estado
                         ELSE 'pendiente' END AS estado_etapa,
                    dw.id AS trabajo_id, dw.version AS trabajo_version,
                    dw.nombre_original AS trabajo_nombre, dw.estado AS trabajo_estado,
                    df.id AS final_id, df.nombre_original AS final_nombre, df.ruta AS final_ruta
             FROM etapas et
             LEFT JOIN (
                SELECT d.* FROM documentos d
                INNER JOIN (SELECT etapa_id, MAX(version) AS v FROM documentos
                            WHERE proyecto_id = ? AND tipo = 'trabajo' GROUP BY etapa_id) m
                   ON m.etapa_id = d.etapa_id AND m.v = d.version
                WHERE d.proyecto_id = ? AND d.tipo = 'trabajo'
             ) dw ON dw.etapa_id = et.id
             LEFT JOIN documentos df ON df.etapa_id = et.id AND df.proyecto_id = ? AND df.tipo = 'final'
             WHERE et.tipo_proyecto_id = ?
             ORDER BY et.orden",
            [$proyectoId, $proyectoId, $proyectoId, $tipoProyectoId]
        );
    }

    /** ¿Puede el estudiante subir documento en esta etapa? (secuencial: anteriores deben estar aprobadas) */
    public function etapaDesbloqueada(int $proyectoId, int $etapaId, int $tipoProyectoId): bool
    {
        $etapas = $this->etapasConEstado($proyectoId, $tipoProyectoId);

        $ordenActual = null;
        foreach ($etapas as $et) {
            if ((int) $et['id'] === $etapaId) {
                $ordenActual = (int) $et['orden'];
                break;
            }
        }
        if ($ordenActual === null) {
            return false;
        }

        foreach ($etapas as $et) {
            if ((int) $et['orden'] < $ordenActual && $et['estado_etapa'] !== 'aprobada') {
                return false; // hay una etapa anterior sin aprobar
            }
        }
        return true;
    }

    /** Genera el siguiente código de proyecto */
    public function nextCodigo(): string
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM proyectos");
        $count = (int) $stmt->fetchColumn() + 1;
        return 'PRJ-' . date('Y') . '-' . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    /** ¿Puede el usuario ver/gestionar este proyecto según su rol? */
    public function puedeVer(int $proyectoId, string $rol, int $usuarioId): bool
    {
        $p = $this->detail($proyectoId);
        if (!$p) {
            return false;
        }
        if ($rol === 'admin') {
            return true;
        }
        if ($rol === 'estudiante') {
            return (int) $p['estudiante_record_id'] !== 0 &&
                $this->registroPerteneceA('estudiantes', (int) $p['estudiante_record_id'], $usuarioId);
        }
        if ($rol === 'docente') {
            return $p['docente_record_id'] !== null &&
                $this->registroPerteneceA('docentes', (int) $p['docente_record_id'], $usuarioId);
        }
        return false;
    }

    /** Usuarios involucrados en el proyecto (estudiante y tutor) */
    public function involucrados(int $proyectoId): array
    {
        $rows = $this->query(
            "SELECT ue.id AS estudiante_usuario_id, ue.nombres AS estudiante_nombres, ue.email AS estudiante_email,
                    ud.id AS tutor_usuario_id, ud.nombres AS tutor_nombres, ud.email AS tutor_email
             FROM proyectos p
             INNER JOIN estudiantes e ON e.id = p.estudiante_id
             INNER JOIN usuarios ue ON ue.id = e.usuario_id
             LEFT JOIN asignaciones a ON a.proyecto_id = p.id AND a.estado = 'activa'
             LEFT JOIN docentes d ON d.id = a.docente_id
             LEFT JOIN usuarios ud ON ud.id = d.usuario_id
             WHERE p.id = ? LIMIT 1",
            [$proyectoId]
        );
        return $rows[0] ?? [];
    }

    private function registroPerteneceA(string $tabla, int $recordId, int $usuarioId): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM `{$tabla}` WHERE id = ? AND usuario_id = ? LIMIT 1");
        $stmt->execute([$recordId, $usuarioId]);
        return (bool) $stmt->fetchColumn();
    }
}
