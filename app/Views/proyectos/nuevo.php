<?php
/** @var array $tipos */
use App\Core\Request;
?>

<div class="max-w-2xl">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Registrar proyecto</h1>
            <p class="text-gray-500 text-sm">Registra tu proyecto académico para iniciar el seguimiento</p>
        </div>
        <a href="<?= url('proyectos') ?>" class="text-[#005880] text-sm font-medium hover:underline">← Volver</a>
    </div>

    <form method="post" action="<?= url('proyectos/crear') ?>" class="bg-white rounded-xl shadow p-6 space-y-4">
        <input type="hidden" name="_csrf" value="<?= e(Request::csrfToken()) ?>">

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del proyecto *</label>
            <input type="text" name="nombre" required value="<?= e(old('nombre')) ?>"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6f9e] outline-none"
                   placeholder="Ej: Sistema web para la gestión de inventarios">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de proyecto *</label>
            <select name="tipo_proyecto_id" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6f9e] outline-none">
                <option value="">Selecciona un tipo...</option>
                <?php foreach ($tipos as $t): ?>
                <option value="<?= (int) $t['id'] ?>"><?= e($t['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
            <textarea name="descripcion" rows="4"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6f9e] outline-none"><?= e(old('descripcion')) ?></textarea>
        </div>

        <div class="bg-[#e8f4fa] border border-[#b9d9e8] rounded-lg p-3 text-sm text-[#004764]">
            Tu proyecto se creará en estado <strong>Borrador</strong>. Después podrás cargar el documento de la primera etapa.
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="bg-[#005880] hover:bg-[#004764] text-white font-semibold px-5 py-2 rounded-lg transition">Registrar proyecto</button>
            <a href="<?= url('proyectos') ?>" class="px-5 py-2 text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg">Cancelar</a>
        </div>
    </form>
</div>
