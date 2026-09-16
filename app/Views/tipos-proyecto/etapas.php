<?php
/** @var array $tipo */
/** @var array $etapas */
use App\Core\Request;
?>

<div class="max-w-3xl">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Etapas — <?= e($tipo['nombre']) ?></h1>
            <p class="text-gray-500 text-sm">Define las etapas que debe cumplir este tipo de proyecto, en orden</p>
        </div>
        <a href="<?= url('tipos-proyecto') ?>" class="text-[#005880] text-sm font-medium hover:underline">← Volver</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Lista de etapas -->
        <div class="bg-white rounded-xl shadow p-5">
            <h2 class="font-semibold text-gray-900 mb-4">Etapas configuradas</h2>
            <?php if (empty($etapas)): ?>
                <p class="text-sm text-gray-400 text-center py-6">Aún no hay etapas. Agrega la primera.</p>
            <?php endif; ?>
            <ol class="space-y-2">
                <?php foreach ($etapas as $i => $etapa): ?>
                <li class="flex items-center gap-3 bg-gray-50 rounded-lg px-3 py-2">
                    <span class="w-7 h-7 flex items-center justify-center rounded-full bg-[#dceef7] text-[#004764] text-xs font-bold"><?= $i + 1 ?></span>
                    <span class="flex-1 font-medium text-sm"><?= e($etapa['nombre']) ?></span>
                    <form method="post" action="<?= url('tipos-proyecto/mover-etapa/' . $tipo['id']) ?>" class="flex gap-1">
                        <input type="hidden" name="_csrf" value="<?= e(Request::csrfToken()) ?>">
                        <input type="hidden" name="etapa_id" value="<?= (int) $etapa['id'] ?>">
                        <button type="submit" name="direccion" value="arriba" title="Subir" class="p-1 text-gray-400 hover:text-[#005880] disabled:opacity-30" <?= $i === 0 ? 'disabled' : '' ?>>↑</button>
                        <button type="submit" name="direccion" value="abajo" title="Bajar" class="p-1 text-gray-400 hover:text-[#005880] disabled:opacity-30" <?= $i === count($etapas) - 1 ? 'disabled' : '' ?>>↓</button>
                    </form>
                    <form method="post" action="<?= url('tipos-proyecto/eliminar-etapa/' . $tipo['id']) ?>" class="inline" data-confirm="¿Eliminar esta etapa? Los proyectos que la usen se verán afectados.">
                        <input type="hidden" name="_csrf" value="<?= e(Request::csrfToken()) ?>">
                        <input type="hidden" name="etapa_id" value="<?= (int) $etapa['id'] ?>">
                        <button type="submit" title="Eliminar" class="p-1 text-gray-400 hover:text-red-600">✕</button>
                    </form>
                </li>
                <?php endforeach; ?>
            </ol>
        </div>

        <!-- Agregar etapa -->
        <div class="bg-white rounded-xl shadow p-5 h-fit">
            <h2 class="font-semibold text-gray-900 mb-4">Agregar etapa</h2>
            <form method="post" action="<?= url('tipos-proyecto/crear-etapa/' . $tipo['id']) ?>" class="space-y-4">
                <input type="hidden" name="_csrf" value="<?= e(Request::csrfToken()) ?>">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de la etapa *</label>
                    <input type="text" name="nombre" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6f9e] outline-none"
                           placeholder="Ej: Capítulo I">
                </div>
                <button type="submit" class="w-full bg-[#005880] hover:bg-[#004764] text-white font-semibold px-4 py-2 rounded-lg transition">Agregar etapa</button>
            </form>
        </div>
    </div>
</div>
