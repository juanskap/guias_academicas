<?php

namespace App\Models;

use App\Core\Model;

/**
 * Repositorio institucional: archivos sellados (documentos finales) y
 * entregables (comprimidos de aplicaciones, anexos, etc.).
 */
class Repositorio extends Model
{
    protected string $table = 'repositorio';

    public const TIPOS = [
        'documento_final' => 'Documento final',
        'entregable_tecnico' => 'Entregable técnico',
        'anexo' => 'Anexo',
        'otro' => 'Otro',
    ];

    /** Archivos visibles según rol, con filtros dinámicos */
    public function listar(array $filtros, string $rol, int $usuarioId): array
    {
        $sql = "SELECT r.id, r.proyecto_id, r.periodo_id, r.estudiante_id, r.grupo,
                       r.tipo, r.titulo, r.nombre_original, r.ruta, r.formato,
                       r.tamanio, r.version, r.subido_por, r.estado, r.creado_en,
                       CONCAT(u.nombres, ' ', u.apellidos) AS estudiante_nombre,
                       u.email AS estudiante_email, e.cedula, e.carrera, e.codigo AS estudiante_codigo,
                       p.codigo AS proyecto_codigo, p.nombre AS proyecto_nombre,
                       pa.nombre AS periodo_nombre
                FROM repositorio r
                LEFT JOIN estudiantes e ON e.id = r.estudiante_id
                LEFT JOIN usuarios u ON u.id = e.usuario_id
                LEFT JOIN proyectos p ON p.id = r.proyecto_id
                LEFT JOIN periodos_academicos pa ON pa.id = r.periodo_id
                WHERE 1=1";
        $params = [];

        // Estado: activo por defecto; con historial se incluyen reemplazados
        $estados = ["'activo'"];
        if (!empty($filtros['historial'])) {
            $estados[] = "'reemplazado'";
        }
        $sql .= ' AND r.estado IN (' . implode(',', $estados) . ')';

        // Alcance por rol
        if ($rol === 'admin') {
            // ve todo
        } elseif ($rol === 'estudiante') {
            // Suyos + tesis públicas de proyectos finalizados (solo PDF sellado)
            $sql .= " AND (r.estudiante_id IN (SELECT id FROM estudiantes WHERE usuario_id = ?)
                           OR (r.tipo = 'documento_final' AND r.formato = 'pdf' AND r.estado = 'activo' AND
                               EXISTS (SELECT 1 FROM proyectos p2 WHERE p2.id = r.proyecto_id
                                       AND p2.estado IN ('aprobado', 'finalizado'))))";
            $params[] = $usuarioId;
        } elseif ($rol === 'docente') {
            // Sus tutorías + lo que subió + tesis públicas de proyectos finalizados (solo PDF sellado)
            $sql .= " AND ((r.id IN (
                        SELECT r2.id FROM repositorio r2
                        INNER JOIN proyectos p2 ON p2.id = r2.proyecto_id
                        INNER JOIN asignaciones a ON a.proyecto_id = p2.id AND a.estado = 'activa'
                        INNER JOIN docentes d ON d.id = a.docente_id
                        WHERE d.usuario_id = ?
                      ) OR r.subido_por = ?)
                      OR (r.tipo = 'documento_final' AND r.formato = 'pdf' AND r.estado = 'activo' AND
                          EXISTS (SELECT 1 FROM proyectos p3 WHERE p3.id = r.proyecto_id
                                  AND p3.estado IN ('aprobado', 'finalizado'))))";
            $params[] = $usuarioId;
            $params[] = $usuarioId;
        }

        // Filtros
        if (!empty($filtros['cedula'])) {
            $sql .= " AND e.cedula LIKE ?";
            $params[] = '%' . $filtros['cedula'] . '%';
        }
        if (!empty($filtros['periodo_id'])) {
            $sql .= ' AND r.periodo_id = ?';
            $params[] = (int) $filtros['periodo_id'];
        }
        if (!empty($filtros['tipo']) && array_key_exists($filtros['tipo'], self::TIPOS)) {
            $sql .= ' AND r.tipo = ?';
            $params[] = $filtros['tipo'];
        }
        if (!empty($filtros['carrera'])) {
            $sql .= ' AND e.carrera LIKE ?';
            $params[] = '%' . $filtros['carrera'] . '%';
        }
        if (!empty($filtros['texto'])) {
            $sql .= ' AND (r.titulo LIKE ? OR r.nombre_original LIKE ? OR p.nombre LIKE ?)';
            $params[] = '%' . $filtros['texto'] . '%';
            $params[] = '%' . $filtros['texto'] . '%';
            $params[] = '%' . $filtros['texto'] . '%';
        }
        if (!empty($filtros['desde'])) {
            $sql .= ' AND DATE(r.creado_en) >= ?';
            $params[] = $filtros['desde'];
        }
        if (!empty($filtros['hasta'])) {
            $sql .= ' AND DATE(r.creado_en) <= ?';
            $params[] = $filtros['hasta'];
        }

        $sql .= " ORDER BY r.creado_en DESC, r.id DESC LIMIT 500";

        $rows = $this->query($sql, $params);

        // Etiquetar el tipo de acceso de cada fila para la vista
        $miEstudianteId = 0;
        $misTutorias = [];
        if ($rol === 'estudiante') {
            $s = $this->db->prepare("SELECT id FROM estudiantes WHERE usuario_id = ? LIMIT 1");
            $s->execute([$usuarioId]);
            $miEstudianteId = (int) $s->fetchColumn();
        } elseif ($rol === 'docente') {
            $s = $this->db->prepare(
                "SELECT p.id FROM proyectos p
                 INNER JOIN asignaciones a ON a.proyecto_id = p.id AND a.estado = 'activa'
                 INNER JOIN docentes d ON d.id = a.docente_id
                 WHERE d.usuario_id = ?"
            );
            $s->execute([$usuarioId]);
            $misTutorias = array_map('intval', $s->fetchAll(\PDO::FETCH_COLUMN));
        }

        foreach ($rows as &$fila) {
            if ($rol === 'admin') {
                $fila['acceso'] = 'admin';
            } elseif ($rol === 'estudiante') {
                $fila['acceso'] = ((int) $fila['estudiante_id'] === $miEstudianteId) ? 'propio' : 'publico';
            } else {
                $fila['acceso'] = (in_array((int) $fila['proyecto_id'], $misTutorias, true)
                    || (int) $fila['subido_por'] === $usuarioId) ? 'tutoria' : 'publico';
            }
        }
        unset($fila);

        return $rows;
    }

    /** Detalle de un archivo del repositorio con datos del estudiante/proyecto */
    public function detalle(int $id): ?array
    {
        $rows = $this->query(
            "SELECT r.*,
                    CONCAT(u.nombres, ' ', u.apellidos) AS estudiante_nombre,
                    u.email AS estudiante_email, e.cedula, e.carrera, e.codigo AS estudiante_codigo,
                    p.codigo AS proyecto_codigo, p.nombre AS proyecto_nombre,
                    pa.nombre AS periodo_nombre,
                    us.nombres AS subido_por_nombres, us.apellidos AS subido_por_apellidos
             FROM repositorio r
             LEFT JOIN estudiantes e ON e.id = r.estudiante_id
             LEFT JOIN usuarios u ON u.id = e.usuario_id
             LEFT JOIN proyectos p ON p.id = r.proyecto_id
             LEFT JOIN periodos_academicos pa ON pa.id = r.periodo_id
             LEFT JOIN usuarios us ON us.id = r.subido_por
             WHERE r.id = ? LIMIT 1",
            [$id]
        );
        return $rows[0] ?? null;
    }

    /** ¿Puede el usuario descargar/ver este archivo? */
    public function puedeAcceder(array $archivo, string $rol, int $usuarioId): bool
    {
        if ($rol === 'admin') {
            return true;
        }

        if ($rol === 'estudiante') {
            // Dueño del archivo
            if (!empty($archivo['estudiante_id'])) {
                $stmt = $this->db->prepare(
                    "SELECT id FROM estudiantes WHERE id = ? AND usuario_id = ? LIMIT 1"
                );
                $stmt->execute([(int) $archivo['estudiante_id'], $usuarioId]);
                if ((bool) $stmt->fetchColumn()) {
                    return true;
                }
            }
            // Si no es suyo, cae al chequeo de acceso público
        } elseif ($rol === 'docente') {
            // Quien subió el archivo
            if ((int) $archivo['subido_por'] === $usuarioId) {
                return true;
            }
            // Tutor del proyecto asociado
            if (!empty($archivo['proyecto_id'])) {
                $stmt = $this->db->prepare(
                    "SELECT COUNT(*) FROM asignaciones a
                     INNER JOIN docentes d ON d.id = a.docente_id
                     WHERE a.proyecto_id = ? AND a.estado = 'activa' AND d.usuario_id = ?"
                );
                $stmt->execute([(int) $archivo['proyecto_id'], $usuarioId]);
                if ((int) $stmt->fetchColumn() > 0) {
                    return true;
                }
            }
        }

        // Acceso público interno: solo el PDF sellado (documento_final) de proyectos finalizados
        if (($archivo['tipo'] ?? '') === 'documento_final'
            && mb_strtolower((string) ($archivo['formato'] ?? '')) === 'pdf') {
            $stmt = $this->db->prepare(
                "SELECT 1 FROM proyectos WHERE id = ? AND estado IN ('aprobado', 'finalizado') LIMIT 1"
            );
            $stmt->execute([(int) $archivo['proyecto_id']]);
            return (bool) $stmt->fetchColumn();
        }

        return false;
    }

    /** Siguiente número de versión para un título/tipo dentro del proyecto */
    public function siguienteVersion(int $proyectoId, int $estudianteId, string $tipo, string $titulo): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) + 1 FROM repositorio
             WHERE proyecto_id = ? AND estudiante_id = ? AND tipo = ? AND titulo = ?"
        );
        $stmt->execute([$proyectoId, $estudianteId, $tipo, $titulo]);
        return (int) $stmt->fetchColumn();
    }

    /** Marca como reemplazados los anteriores activos de un grupo (proyecto, tipo, título, formato) */
    public function reemplazarAnteriores(int $proyectoId, string $tipo, string $titulo, string $formato): void
    {
        $stmt = $this->db->prepare(
            "UPDATE repositorio SET estado = 'reemplazado'
             WHERE proyecto_id = ? AND tipo = ? AND titulo = ? AND formato = ? AND estado = 'activo'"
        );
        $stmt->execute([$proyectoId, $tipo, $titulo, $formato]);
    }
}