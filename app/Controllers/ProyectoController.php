<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Middlewares\AuthMiddleware;
use App\Models\Docente;
use App\Models\Etapa;
use App\Models\Notificacion;
use App\Models\Proyecto;
use App\Models\TipoProyecto;

/**
 * Gestión de proyectos académicos.
 * El acceso depende del rol:
 * - estudiante: solo sus proyectos
 * - docente: solo los asignados
 * - admin: todos
 */
class ProyectoController extends Controller
{
    /** Lista de proyectos según el rol */
    public function index(): void
    {
        $proyectos = (new Proyecto())->visibleByRole(Auth::role(), Auth::id());

        $this->view('proyectos/index', [
            'title' => 'Proyectos',
            'proyectos' => $proyectos,
        ]);
    }

    /** Formulario de creación de proyecto (solo estudiantes) */
    public function nuevo(): void
    {
        AuthMiddleware::requireRole('estudiante');
        $tipos = (new TipoProyecto())->allActivos();

        $this->view('proyectos/nuevo', [
            'title' => 'Registrar proyecto',
            'tipos' => $tipos,
        ]);
    }

    /** Procesa la creación de un proyecto (solo estudiantes) */
    public function crear(): void
    {
        AuthMiddleware::requireRole('estudiante');

        if (!Request::csrfValidate()) {
            flash('error', 'La sesión expiró, inténtalo de nuevo.');
            redirect_to('proyectos');
        }

        $nombre = trim((string) Request::post('nombre', ''));
        $tipoId = (int) Request::post('tipo_proyecto_id', 0);
        $descripcion = trim((string) Request::post('descripcion', ''));

        if ($nombre === '') {
            flash('error', 'El nombre del proyecto es obligatorio.');
            redirect_to('proyectos/nuevo');
        }

        $tipo = (new TipoProyecto())->find($tipoId);
        if (!$tipo || !$tipo['activo']) {
            flash('error', 'Selecciona un tipo de proyecto válido.');
            redirect_to('proyectos/nuevo');
        }

        $db = Database::getConnection();

        // Obtener el registro de estudiante del usuario
        $stmt = $db->prepare("SELECT id FROM estudiantes WHERE usuario_id = ?");
        $stmt->execute([Auth::id()]);
        $estudianteId = $stmt->fetchColumn();

        if (!$estudianteId) {
            flash('error', 'No tienes un perfil de estudiante asociado.');
            redirect_to('proyectos');
        }

        $proyecto = new Proyecto();
        $primeraEtapa = (new Etapa())->query(
            "SELECT id FROM etapas WHERE tipo_proyecto_id = ? ORDER BY orden LIMIT 1",
            [$tipoId]
        );
        $primeraEtapaId = $primeraEtapa[0]['id'] ?? null;

        try {
            $db->beginTransaction();

            $proyectoId = $proyecto->create([
                'codigo' => $proyecto->nextCodigo(),
                'nombre' => $nombre,
                'descripcion' => $descripcion ?: null,
                'tipo_proyecto_id' => $tipoId,
                'estudiante_id' => $estudianteId,
                'estado' => 'borrador',
                'etapa_actual_id' => $primeraEtapaId,
                'porcentaje_avance' => 0,
            ]);

            // Registrar actividad
            $this->registrarHistorial($proyectoId, 'Creación de proyecto', "Proyecto '{$nombre}' registrado");

            $db->commit();

            notificar((new Notificacion())->adminIds(), 'Nuevo proyecto', "Se registró el proyecto \"{$nombre}\".", $proyectoId, 'proyecto', Auth::id());

            flash('success', 'Proyecto registrado. Ahora puedes cargar el documento de la primera etapa.');
            redirect_to('proyectos/ver/' . $proyectoId);
        } catch (\Throwable $e) {
            $db->rollBack();
            flash('error', 'Error al crear el proyecto: ' . $e->getMessage());
            redirect_to('proyectos/nuevo');
        }
    }

    /** Detalle del proyecto (con control de acceso) */
    public function ver(int $id): void
    {
        $proyecto = (new Proyecto())->detail($id);
        if (!$proyecto) {
            flash('error', 'Proyecto no encontrado.');
            redirect_to('proyectos');
        }

        if (!$this->canAccess($proyecto)) {
            AuthMiddleware::requireRole('admin'); // genera 403 si no tiene permiso
        }

        $etapas = (new Proyecto())->etapasConEstado($id, (int) $proyecto['tipo_proyecto_id']);

        $historial = Database::getConnection()->prepare(
            "SELECT h.*, u.nombres, u.apellidos
             FROM historial_acciones h
             INNER JOIN usuarios u ON u.id = h.usuario_id
             WHERE h.proyecto_id = ?
             ORDER BY h.creado_en DESC
             LIMIT 25"
        );
        $historial->execute([$id]);
        $historial = $historial->fetchAll();

        $this->view('proyectos/ver', [
            'title' => $proyecto['codigo'],
            'proyecto' => $proyecto,
            'etapas' => $etapas,
            'historial' => $historial,
        ]);
    }

    /** Formulario para asignar tutor (solo admin) */
    public function asignar(int $id): void
    {
        AuthMiddleware::requireRole('admin');

        $proyecto = (new Proyecto())->detail($id);
        if (!$proyecto) {
            flash('error', 'Proyecto no encontrado.');
            redirect_to('proyectos');
        }

        $docentes = (new Docente())->allFull();

        $this->view('proyectos/asignar', [
            'title' => 'Asignar tutor — ' . $proyecto['codigo'],
            'proyecto' => $proyecto,
            'docentes' => $docentes,
        ]);
    }

    /** Procesa la asignación de tutor (solo admin) */
    public function guardarTutor(int $id): void
    {
        AuthMiddleware::requireRole('admin');

        if (!Request::csrfValidate()) {
            flash('error', 'La sesión expiró, inténtalo de nuevo.');
            redirect_to('proyectos/asignar/' . $id);
        }

        $docenteId = (int) Request::post('docente_id', 0);
        if ($docenteId <= 0) {
            flash('error', 'Selecciona un docente para asignar como tutor.');
            redirect_to('proyectos/asignar/' . $id);
        }

        $db = Database::getConnection();

        // Verificar que el docente existe
        $stmt = $db->prepare("SELECT id FROM docentes WHERE id = ?");
        $stmt->execute([$docenteId]);
        if (!$stmt->fetchColumn()) {
            flash('error', 'El docente seleccionado no existe.');
            redirect_to('proyectos/asignar/' . $id);
        }

        // Actualizar o crear la asignación activa
        $stmt = $db->prepare("SELECT id FROM asignaciones WHERE proyecto_id = ?");
        $stmt->execute([$id]);
        $asignacionId = $stmt->fetchColumn();

        if ($asignacionId) {
            $db->prepare("UPDATE asignaciones SET docente_id = ?, estado = 'activa' WHERE id = ?")
                ->execute([$docenteId, $asignacionId]);
        } else {
            $db->prepare("INSERT INTO asignaciones (proyecto_id, docente_id) VALUES (?, ?)")
                ->execute([$id, $docenteId]);
        }

        $this->registrarHistorial($id, 'Asignación de tutor', "Tutor asignado al proyecto {$id}");
        flash('success', 'Tutor asignado correctamente.');
        redirect_to('proyectos/ver/' . $id);
    }

    /** Control de acceso por proyecto */
    private function canAccess(array $proyecto): bool
    {
        return (new Proyecto())->puedeVer((int) $proyecto['id'], Auth::role(), Auth::id());
    }

    private function registrarHistorial(int $proyectoId, string $accion, string $descripcion): void
    {
        $stmt = Database::getConnection()->prepare(
            "INSERT INTO historial_acciones (usuario_id, accion, descripcion, proyecto_id) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([Auth::id(), $accion, $descripcion, $proyectoId]);
    }
}
