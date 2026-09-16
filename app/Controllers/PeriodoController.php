<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Middlewares\AuthMiddleware;
use App\Models\PeriodoAcademico;

/**
 * Gestión de periodos académicos. Solo rol administrador.
 */
class PeriodoController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        AuthMiddleware::requireRole('admin');
    }

    /** Lista los periodos académicos */
    public function index(): void
    {
        $this->view('periodos/index', [
            'title' => 'Periodos académicos',
            'periodos' => (new PeriodoAcademico())->allConProyectos(),
        ]);
    }

    /** Muestra el formulario de creación */
    public function nuevo(): void
    {
        $this->view('periodos/nuevo', ['title' => 'Nuevo periodo académico']);
    }

    /** Procesa la creación */
    public function crear(): void
    {
        if (!Request::csrfValidate()) {
            flash('error', 'La sesión expiró, inténtalo de nuevo.');
            redirect_to('periodos');
        }

        $nombre = trim((string) Request::post('nombre', ''));
        $inicio = (string) Request::post('fecha_inicio', '');
        $fin = (string) Request::post('fecha_fin', '');

        $error = $this->validar($nombre, $inicio, $fin);
        if ($error) {
            flash('error', $error);
            redirect_to('periodos/nuevo');
        }

        $model = new PeriodoAcademico();
        if ($model->firstWhere('nombre', $nombre)) {
            flash('error', 'Ya existe un periodo con ese nombre.');
            redirect_to('periodos/nuevo');
        }

        $model->create([
            'nombre' => $nombre,
            'fecha_inicio' => $inicio,
            'fecha_fin' => $fin,
            'activo' => (int) Request::post('activo', 0) ? 1 : 0,
        ]);

        $this->registrarHistorial('Creación de periodo', "Periodo '{$nombre}' creado");
        flash('success', 'Periodo académico creado.');
        redirect_to('periodos');
    }

    /** Muestra el formulario de edición */
    public function editar(int $id): void
    {
        $periodo = (new PeriodoAcademico())->find($id);
        if (!$periodo) {
            flash('error', 'Periodo no encontrado.');
            redirect_to('periodos');
        }

        $this->view('periodos/editar', [
            'title' => 'Editar periodo',
            'periodo' => $periodo,
        ]);
    }

    /** Procesa la actualización */
    public function actualizar(): void
    {
        if (!Request::csrfValidate()) {
            flash('error', 'La sesión expiró, inténtalo de nuevo.');
            redirect_to('periodos');
        }

        $id = (int) Request::post('id', 0);
        $nombre = trim((string) Request::post('nombre', ''));
        $inicio = (string) Request::post('fecha_inicio', '');
        $fin = (string) Request::post('fecha_fin', '');
        $activo = (int) Request::post('activo', 0) ? 1 : 0;

        $error = $this->validar($nombre, $inicio, $fin);
        if ($error) {
            flash('error', $error);
            redirect_to('periodos/editar/' . $id);
        }

        $model = new PeriodoAcademico();
        $periodo = $model->find($id);
        if (!$periodo) {
            flash('error', 'Periodo no encontrado.');
            redirect_to('periodos');
        }

        $dup = $model->query("SELECT id FROM periodos_academicos WHERE nombre = ? AND id != ? LIMIT 1", [$nombre, $id]);
        if ($dup) {
            flash('error', 'Ya existe otro periodo con ese nombre.');
            redirect_to('periodos/editar/' . $id);
        }

        $data = [
            'nombre' => $nombre,
            'fecha_inicio' => $inicio,
            'fecha_fin' => $fin,
        ];
        if ($activo) {
            $model->execute("UPDATE periodos_academicos SET activo = 0");
            $data['activo'] = 1;
        } elseif ((int) $periodo['activo'] === 1) {
            $data['activo'] = 0;
        }

        $model->update($id, $data);
        $this->registrarHistorial('Actualización de periodo', "Periodo '{$nombre}' actualizado");
        flash('success', 'Periodo académico actualizado.');
        redirect_to('periodos');
    }

    /** Activa o desactiva un periodo (al activar se desactivan los demás) */
    public function estado(int $id): void
    {
        if (!Request::csrfValidate()) {
            flash('error', 'La sesión expiró, inténtalo de nuevo.');
            redirect_to('periodos');
        }

        $model = new PeriodoAcademico();
        $periodo = $model->find($id);
        if (!$periodo) {
            flash('error', 'Periodo no encontrado.');
            redirect_to('periodos');
        }

        if ((int) $periodo['activo'] === 1) {
            $model->update($id, ['activo' => 0]);
            flash('success', "Periodo '{$periodo['nombre']}' desactivado.");
        } else {
            $model->execute("UPDATE periodos_academicos SET activo = 0");
            $model->update($id, ['activo' => 1]);
            flash('success', "Periodo '{$periodo['nombre']}' activado como periodo actual.");
        }

        $this->registrarHistorial('Cambio de estado de periodo', "Periodo '{$periodo['nombre']}' actualizado");
        redirect_to('periodos');
    }

    private function validar(string $nombre, string $inicio, string $fin): ?string
    {
        if ($nombre === '') {
            return 'El nombre del periodo es obligatorio.';
        }
        if ($inicio === '' || $fin === '' || !strtotime($inicio) || !strtotime($fin)) {
            return 'Las fechas de inicio y fin son obligatorias.';
        }
        if (strtotime($fin) < strtotime($inicio)) {
            return 'La fecha de fin no puede ser anterior a la de inicio.';
        }
        return null;
    }

    private function registrarHistorial(string $accion, string $descripcion): void
    {
        $stmt = Database::getConnection()->prepare(
            "INSERT INTO historial_acciones (usuario_id, accion, descripcion) VALUES (?, ?, ?)"
        );
        $stmt->execute([\App\Core\Auth::id(), $accion, $descripcion]);
    }
}