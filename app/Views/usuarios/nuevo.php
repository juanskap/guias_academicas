<?php
/** @var string $tipo */
use App\Core\Request;
$tipo = $tipo === 'docente' ? 'docente' : 'estudiante';
?>

<div class="max-w-2xl">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Nuevo <?= $tipo === 'docente' ? 'docente' : 'estudiante' ?></h1>
            <p class="text-gray-500 text-sm">La contraseña inicial del usuario será: <code class="bg-gray-100 px-1.5 py-0.5 rounded text-[#004764]"><?= e(DEFAULT_PASSWORD) ?></code></p>
        </div>
        <a href="<?= url('usuarios') ?>" class="text-[#005880] text-sm font-medium hover:underline">← Volver</a>
    </div>

    <form method="post" action="<?= url('usuarios/crear') ?>" class="bg-white rounded-xl shadow p-6 space-y-4">
        <input type="hidden" name="_csrf" value="<?= e(Request::csrfToken()) ?>">
        <input type="hidden" name="tipo" value="<?= e($tipo) ?>">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombres *</label>
                <input type="text" name="nombres" required value="<?= e(old('nombres')) ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6f9e] outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Apellidos *</label>
                <input type="text" name="apellidos" required value="<?= e(old('apellidos')) ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6f9e] outline-none">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico *</label>
                <input type="email" name="email" required value="<?= e(old('email')) ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6f9e] outline-none">
            </div>
        </div>

        <?php if ($tipo === 'estudiante'): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Código / Matrícula *</label>
                <input type="text" name="codigo" required value="<?= e(old('codigo')) ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6f9e] outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Carrera *</label>
                <input type="text" name="carrera" required value="<?= e(old('carrera')) ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6f9e] outline-none">
            </div>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Título</label>
                <input type="text" name="titulo" value="<?= e(old('titulo')) ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6f9e] outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Especialidad</label>
                <input type="text" name="especialidad" value="<?= e(old('especialidad')) ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6f9e] outline-none">
            </div>
        </div>
        <?php endif; ?>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
            <input type="text" name="telefono" value="<?= e(old('telefono')) ?>"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6f9e] outline-none">
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="bg-[#005880] hover:bg-[#004764] text-white font-semibold px-5 py-2 rounded-lg transition">Guardar usuario</button>
            <a href="<?= url('usuarios') ?>" class="px-5 py-2 text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg">Cancelar</a>
        </div>
    </form>
</div>
