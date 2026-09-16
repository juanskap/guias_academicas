<?php
/** @var array $tipos */
use App\Core\Request;
?>

<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Tipos de proyecto</h1>
        <p class="text-gray-500 text-sm">Configura los tipos de proyecto y sus etapas</p>
    </div>
    <a href="<?= url('tipos-proyecto/nuevo') ?>" class="bg-[#005880] hover:bg-[#004764] text-white text-sm font-semibold px-4 py-2 rounded-lg transition">+ Nuevo tipo</a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php foreach ($tipos as $t): ?>
    <div class="bg-white rounded-xl shadow p-5 flex flex-col">
        <div class="flex items-start justify-between mb-2">
            <h2 class="font-bold text-gray-900"><?= e($t['nombre']) ?></h2>
            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold <?= $t['activo'] ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600' ?>">
                <?= $t['activo'] ? 'Activo' : 'Inactivo' ?>
            </span>
        </div>
        <p class="text-sm text-gray-500 mb-3 flex-1"><?= e($t['descripcion'] ?? 'Sin descripción') ?></p>
        <p class="text-sm mb-3"><span class="font-semibold"><?= (int) $t['total_etapas'] ?></span> etapas configuradas</p>
        <div class="flex items-center gap-3 text-sm border-t pt-3">
            <a href="<?= url('tipos-proyecto/etapas/' . $t['id']) ?>" class="text-[#005880] hover:text-[#00394f] font-medium">Etapas</a>
            <a href="<?= url('tipos-proyecto/editar/' . $t['id']) ?>" class="text-[#005880] hover:text-[#00394f] font-medium">Editar</a>
            <form method="post" action="<?= url('tipos-proyecto/estado/' . $t['id']) ?>" class="inline ml-auto" data-confirm="¿Cambiar el estado de este tipo de proyecto?">
                <input type="hidden" name="_csrf" value="<?= e(Request::csrfToken()) ?>">
                <button type="submit" class="font-medium <?= $t['activo'] ? 'text-red-600 hover:text-red-800' : 'text-green-600 hover:text-green-800' ?>">
                    <?= $t['activo'] ? 'Desactivar' : 'Activar' ?>
                </button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($tipos)): ?>
    <div class="col-span-full bg-white rounded-xl shadow p-8 text-center text-gray-400">No hay tipos de proyecto configurados.</div>
    <?php endif; ?>
</div>
