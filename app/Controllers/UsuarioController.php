<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Middlewares\AuthMiddleware;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Usuario;

/**
 * Gestión de usuarios. Solo acceso del rol administrador.
 */
class UsuarioController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requireRoleAdmin();
    }

    private function requireRoleAdmin(): void
    {
        AuthMiddleware::requireRole('admin');
    }

    /** Lista todos los usuarios */
    public function index(): void
    {
        $usuario = new Usuario();
        $usuarios = $usuario->allWithRole();

        $this->view('usuarios/index', [
            'title' => 'Usuarios',
            'usuarios' => $usuarios,
        ]);
    }

    /** Muestra el formulario para crear un usuario (estudiante o docente) */
    public function nuevo(): void
    {
        $this->view('usuarios/nuevo', [
            'title' => 'Nuevo usuario',
            'tipo' => Request::get('tipo', 'estudiante'),
        ]);
    }

    /** Procesa la creación de un usuario */
    public function crear(): void
    {
        if (!Request::csrfValidate()) {
            flash('error', 'La sesión expiró, inténtalo de nuevo.');
            redirect_to('usuarios');
        }

        $db = Database::getConnection();

        $tipo = Request::post('tipo', 'estudiante');
        $nombres = trim((string) Request::post('nombres', ''));
        $apellidos = trim((string) Request::post('apellidos', ''));
        $email = trim((string) Request::post('email', ''));
        $telefono = trim((string) Request::post('telefono', ''));

        // La contraseña inicial es fija; el usuario la cambia al iniciar sesión
        $password = DEFAULT_PASSWORD;

        // Campos específicos
        $codigo = trim((string) Request::post('codigo', ''));
        $cedula = trim((string) Request::post('cedula', ''));
        $carrera = trim((string) Request::post('carrera', ''));
        $titulo = trim((string) Request::post('titulo', ''));
        $especialidad = trim((string) Request::post('especialidad', ''));

        $error = $this->validateCreate(
            $tipo, $nombres, $apellidos, $email,
            $codigo, $cedula, $carrera, $titulo, $especialidad
        );

        if ($error) {
            flash('error', $error);
            $this->flashOld(Request::all());
            redirect_to('usuarios/nuevo');
        }

        // Verificar email y código únicos
        $usuario = new Usuario();
        if ($usuario->firstWhere('email', $email)) {
            flash('error', 'Ya existe un usuario con ese correo electrónico.');
            $this->flashOld(Request::all());
            redirect_to('usuarios/nuevo');
        }
        if ($cedula !== '' && (new Estudiante())->firstWhere('cedula', $cedula)) {
            flash('error', 'Ya existe un estudiante con esa cédula.');
            $this->flashOld(Request::all());
            redirect_to('usuarios/nuevo');
        }

        $rol = $tipo === 'docente' ? 'docente' : 'estudiante';
        $stmtRol = $db->prepare("SELECT id FROM roles WHERE nombre = ?");
        $stmtRol->execute([$rol]);
        $rolId = $stmtRol->fetchColumn();

        try {
            $db->beginTransaction();

            $usuarioId = $usuario->create([
                'rol_id' => $rolId,
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_BCRYPT),
                'telefono' => $telefono ?: null,
                'estado' => 'activo',
            ]);

            if ($tipo === 'docente') {
                (new Docente())->create([
                    'usuario_id' => $usuarioId,
                    'titulo' => $titulo ?: null,
                    'especialidad' => $especialidad ?: null,
                ]);
            } else {
                (new Estudiante())->create([
                    'usuario_id' => $usuarioId,
                    'codigo' => $codigo,
                    'cedula' => $cedula !== '' ? $cedula : null,
                    'carrera' => $carrera,
                ]);
            }

            $db->commit();

            $this->registrarHistorial($usuarioId, "Creación de usuario", "Se creó el usuario {$email} ({$rol})");
            flash('success', "Usuario {$tipo} creado correctamente. Contraseña inicial: " . DEFAULT_PASSWORD);
            redirect_to('usuarios');
        } catch (\Throwable $e) {
            $db->rollBack();
            flash('error', 'Error al crear el usuario: ' . $e->getMessage());
            $this->flashOld(Request::all());
            redirect_to('usuarios/nuevo');
        }
    }

    /** Muestra el formulario de edición */
    public function editar(int $id): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "SELECT u.*, r.nombre AS rol,
                    e.id AS estudiante_id, e.codigo, e.cedula, e.carrera,
                    d.id AS docente_id, d.titulo, d.especialidad
             FROM usuarios u
             INNER JOIN roles r ON r.id = u.rol_id
             LEFT JOIN estudiantes e ON e.usuario_id = u.id
             LEFT JOIN docentes d ON d.usuario_id = u.id
             WHERE u.id = ?"
        );
        $stmt->execute([$id]);
        $usuario = $stmt->fetch();

        if (!$usuario) {
            flash('error', 'Usuario no encontrado.');
            redirect_to('usuarios');
        }

        $this->view('usuarios/editar', [
            'title' => 'Editar usuario',
            'usuario' => $usuario,
        ]);
    }

    /** Procesa la actualización de un usuario */
    public function actualizar(): void
    {
        if (!Request::csrfValidate()) {
            flash('error', 'La sesión expiró, inténtalo de nuevo.');
            redirect_to('usuarios');
        }

        $id = (int) Request::post('id', 0);
        $usuario = new Usuario();
        $actual = $usuario->find($id);

        if (!$actual) {
            flash('error', 'Usuario no encontrado.');
            redirect_to('usuarios');
        }

        $nombres = trim((string) Request::post('nombres', ''));
        $apellidos = trim((string) Request::post('apellidos', ''));
        $email = trim((string) Request::post('email', ''));
        $telefono = trim((string) Request::post('telefono', ''));
        $password = (string) Request::post('password', '');

        $codigo = trim((string) Request::post('codigo', ''));
        $cedula = trim((string) Request::post('cedula', ''));
        $carrera = trim((string) Request::post('carrera', ''));
        $titulo = trim((string) Request::post('titulo', ''));
        $especialidad = trim((string) Request::post('especialidad', ''));

        if ($nombres === '' || $apellidos === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Nombres, apellidos y un correo válido son obligatorios.');
            $this->flashOld(Request::all());
            redirect_to('usuarios/editar/' . $id);
        }

        // Email único (excluyendo al mismo usuario)
        $stmt = $this->queryEmailUnique($email, $id);
        if ($stmt) {
            flash('error', 'Ya existe otro usuario con ese correo electrónico.');
            $this->flashOld(Request::all());
            redirect_to('usuarios/editar/' . $id);
        }

        $data = [
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'email' => $email,
            'telefono' => $telefono ?: null,
        ];
        if ($password !== '') {
            if (strlen($password) < 6) {
                flash('error', 'La contraseña debe tener al menos 6 caracteres.');
                redirect_to('usuarios/editar/' . $id);
            }
            $data['password'] = password_hash($password, PASSWORD_BCRYPT);
        }

        $db = Database::getConnection();
        try {
            $db->beginTransaction();
            $usuario->update($id, $data);

            if ($actual['rol'] === 'docente') {
                (new Docente())->execute(
                    "UPDATE docentes SET titulo = ?, especialidad = ? WHERE usuario_id = ?",
                    [$titulo ?: null, $especialidad ?: null, $id]
                );
            } elseif ($actual['rol'] === 'estudiante') {
                if ($cedula !== '' && !preg_match('/^\d{10}$/', $cedula)) {
                    flash('error', 'La cédula debe tener 10 dígitos.');
                    redirect_to('usuarios/editar/' . $id);
                }
                $stmt = $db->prepare("SELECT id FROM estudiantes WHERE cedula = ? AND usuario_id != ? LIMIT 1");
                $stmt->execute([$cedula, $id]);
                if ($cedula !== '' && $stmt->fetchColumn()) {
                    flash('error', 'Ya existe otro estudiante con esa cédula.');
                    redirect_to('usuarios/editar/' . $id);
                }
                (new Estudiante())->execute(
                    "UPDATE estudiantes SET codigo = ?, cedula = ?, carrera = ? WHERE usuario_id = ?",
                    [$codigo, $cedula !== '' ? $cedula : null, $carrera, $id]
                );
            }

            $db->commit();
            $this->registrarHistorial($id, "Actualización de usuario", "Se actualizó el usuario {$email}");
            flash('success', 'Usuario actualizado correctamente.');
            redirect_to('usuarios');
        } catch (\Throwable $e) {
            $db->rollBack();
            flash('error', 'Error al actualizar: ' . $e->getMessage());
            redirect_to('usuarios/editar/' . $id);
        }
    }

    /** Activa o desactiva un usuario */
    public function estado(int $id): void
    {
        if (!Request::csrfValidate()) {
            flash('error', 'La sesión expiró, inténtalo de nuevo.');
            redirect_to('usuarios');
        }

        $usuario = new Usuario();
        $actual = $usuario->find($id);

        if (!$actual) {
            flash('error', 'Usuario no encontrado.');
            redirect_to('usuarios');
        }

        if ($id === \App\Core\Auth::id()) {
            flash('error', 'No puedes desactivar tu propia cuenta.');
            redirect_to('usuarios');
        }

        $nuevoEstado = $actual['estado'] === 'activo' ? 'inactivo' : 'activo';
        $usuario->update($id, ['estado' => $nuevoEstado]);

        $this->registrarHistorial($id, "Cambio de estado", "Usuario {$actual['email']} → {$nuevoEstado}");
        flash('success', "Usuario " . ($nuevoEstado === 'activo' ? 'activado' : 'desactivado') . " correctamente.");
        redirect_to('usuarios');
    }

    /** Registra una acción en el historial */
    private function registrarHistorial(int $usuarioId, string $accion, string $descripcion): void
    {
        $stmt = Database::getConnection()->prepare(
            "INSERT INTO historial_acciones (usuario_id, accion, descripcion) VALUES (?, ?, ?)"
        );
        $stmt->execute([\App\Core\Auth::id(), $accion, $descripcion]);
    }

    private function validateCreate(
        string $tipo, string $nombres, string $apellidos, string $email,
        string $codigo, string $cedula, string $carrera, string $titulo, string $especialidad
    ): ?string {
        if (!in_array($tipo, ['estudiante', 'docente'], true)) {
            return 'Tipo de usuario inválido.';
        }
        if ($nombres === '' || $apellidos === '') {
            return 'Nombres y apellidos son obligatorios.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Ingresa un correo electrónico válido.';
        }
        if ($tipo === 'estudiante') {
            if ($codigo === '') {
                return 'El código de estudiante es obligatorio.';
            }
            if ($carrera === '') {
                return 'La carrera es obligatoria.';
            }
            if (!preg_match('/^\d{10}$/', $cedula)) {
                return 'La cédula debe tener 10 dígitos.';
            }
        }
        return null;
    }

    private function queryEmailUnique(string $email, int $excludeId): bool
    {
        $stmt = Database::getConnection()->prepare(
            "SELECT id FROM usuarios WHERE email = ? AND id != ? LIMIT 1"
        );
        $stmt->execute([$email, $excludeId]);
        return (bool) $stmt->fetchColumn();
    }
}
