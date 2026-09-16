<?php
/** @var array $proyectos */
/** @var array $tipos */
use App\Core\Request;
?>

<div class="max-w-2xl">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Subir al repositorio</h1>
            <p class="text-gray-500 text-sm">Entregables técnicos (comprimidos de aplicaciones), anexos u otros documentos</p>
        </div>
        <a href="<?= url('repositorio') ?>" class="text-[#005880] text-sm font-medium hover:underline">← Volver</a>
    </div>

    <form method="post" action="<?= url('repositorio/guardar') ?>" enctype="multipart/form-data" class="bg-white rounded-xl shadow p-6 space-y-4">
        <input type="hidden" name="_csrf" value="<?= e(Request::csrfToken()) ?>">
        <input type="hidden" name="MAX_FILE_SIZE" value="<?= MAX_FILE_SIZE ?>">

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Proyecto *</label>
            <select name="proyecto_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-[#0b6f9e] outline-none">
                <option value="">Selecciona el proyecto</option>
                <?php foreach ($proyectos as $p): ?>
                <option value="<?= (int) $p['id'] ?>">
                    <?= e($p['codigo']) ?> — <?= e($p['nombre']) ?><?= isset($p['estudiante']) ? ' (' . e($p['estudiante']) . ')' : '' ?>
                </option>
                <?php endforeach; ?>
            </select>
            <?php if (!$proyectos): ?>
            <p class="text-xs text-gray-400 mt-1">No tienes proyectos disponibles para archivar.</p>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de archivo *</label>
                <select name="tipo" required class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-[#0b6f9e] outline-none">
                    <?php foreach ($tipos as $clave => $etiqueta): ?>
                    <option value="<?= e($clave) ?>"><?= e($etiqueta) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Título *</label>
                <input type="text" name="titulo" required placeholder="Ej. Código fuente (Módulo de matrículas)"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6f9e] outline-none">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Archivo *</label>
            <input type="file" name="archivo" required
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-sm">
            <p class="text-xs text-gray-400 mt-1">Formatos: <?= e(implode(', ', ALLOWED_REPO_EXTENSIONS)) ?>. Cada subida se registra como una versión nueva.</p>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="bg-[#005880] hover:bg-[#004764] text-white font-semibold px-5 py-2 rounded-lg transition">Guardar en el repositorio</button>
            <a href="<?= url('repositorio') ?>" class="px-5 py-2 text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg">Cancelar</a>
        </div>
    </form>
</div>