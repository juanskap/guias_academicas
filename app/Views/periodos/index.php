<?php
/** @var array $periodos */
use App\Core\Request;
?>

<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Periodos académicos</h1>
        <p class="text-gray-500 text-sm">Definen el periodo al que pertenece cada proyecto (asignación manual)</p>
    </div>
    <a href="<?= url('periodos/nuevo') ?>" class="bg-[#005880] hover:bg-[#004764] text-white text-sm font-semibold px-4 py-2 rounded-lg transition">+ Nuevo periodo</a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php foreach ($periodos as $p): ?>
    <div class="bg-white rounded-xl shadow p-5 flex flex-col">
        <div class="flex items-start justify-between mb-2">
            <h2 class="font-bold text-gray-900"><?= e($p['nombre']) ?></h2>
            <?php if ($p['activo']): ?>
            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">Actual</span>
            <?php endif; ?>
        </div>
        <p class="text-sm text-gray-500 mb-3 flex-1">
            Del <strong><?= e(date('d/m/Y', strtotime($p['fecha_inicio']))) ?></strong>
            al <strong><?= e(date('d/m/Y', strtotime($p['fecha_fin']))) ?></strong>
        </p>
        <p class="text-sm mb-3"><span class="font-semibold"><?= (int) $p['total_proyectos'] ?></span> proyectos asignados</p>
        <div class="flex items-center gap-3 text-sm border-t pt-3">
            <a href="<?= url('periodos/editar/' . $p['id']) ?>" class="text-[#005880] hover:text-[#00394f] font-medium">Editar</a>
            <form method="post" action="<?= url('periodos/estado/' . $p['id']) ?>" class="inline ml-auto">
                <input type="hidden" name="_csrf" value="<?= e(Request::csrfToken()) ?>">
                <button type="submit" class="font-medium <?= $p['activo'] ? 'text-red-600 hover:text-red-800' : 'text-green-600 hover:text-green-800' ?>">
                    <?= $p['activo'] ? 'Desactivar' : 'Establecer actual' ?>
                </button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($periodos)): ?>
    <div class="col-span-full bg-white rounded-xl shadow p-8 text-center text-gray-400">Aún no hay periodos académicos. Crea el primero.</div>
    <?php endif; ?>
</div>