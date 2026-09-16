<?php
/** @var array $usuario */
use App\Core\Request;
?>

<div class="max-w-2xl">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Editar usuario</h1>
            <p class="text-gray-500 text-sm"><?= e($usuario['nombres'] . ' ' . $usuario['apellidos']) ?> — <span class="uppercase"><?= e($usuario['rol']) ?></span></p>
        </div>
        <a href="<?= url('usuarios') ?>" class="text-[#005880] text-sm font-medium hover:underline">← Volver</a>
    </div>

    <form method="post" action="<?= url('usuarios/actualizar') ?>" class="bg-white rounded-xl shadow p-6 space-y-4">
        <input type="hidden" name="_csrf" value="<?= e(Request::csrfToken()) ?>">
        <input type="hidden" name="id" value="<?= (int) $usuario['id'] ?>">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombres *</label>
                <input type="text" name="nombres" required value="<?= e($usuario['nombres']) ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6f9e] outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Apellidos *</label>
                <input type="text" name="apellidos" required value="<?= e($usuario['apellidos']) ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6f9e] outline-none">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico *</label>
                <input type="email" name="email" required value="<?= e($usuario['email']) ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6f9e] outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nueva contraseña</label>
                <input type="password" name="password" placeholder="Dejar vacío para no cambiar"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6f9e] outline-none">
            </div>
        </div>

        <?php if ($usuario['rol'] === 'estudiante'): ?>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Código / Matrícula *</label>
                <input type="text" name="codigo" required value="<?= e($usuario['codigo'] ?? '') ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6f9e] outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cédula</label>
                <input type="text" name="cedula" maxlength="10" value="<?= e($usuario['cedula'] ?? '') ?>"
                       placeholder="10 dígitos"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6f9e] outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Carrera *</label>
                <input type="text" name="carrera" required value="<?= e($usuario['carrera'] ?? '') ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6f9e] outline-none">
            </div>
        </div>
        <?php elseif ($usuario['rol'] === 'docente'): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Título</label>
                <input type="text" name="titulo" value="<?= e($usuario['titulo'] ?? '') ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6f9e] outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Especialidad</label>
                <input type="text" name="especialidad" value="<?= e($usuario['especialidad'] ?? '') ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6f9e] outline-none">
            </div>
        </div>
        <?php endif; ?>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
            <input type="text" name="telefono" value="<?= e($usuario['telefono'] ?? '') ?>"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6f9e] outline-none">
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="bg-[#005880] hover:bg-[#004764] text-white font-semibold px-5 py-2 rounded-lg transition">Guardar cambios</button>
            <a href="<?= url('usuarios') ?>" class="px-5 py-2 text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg">Cancelar</a>
        </div>
    </form>
</div>
