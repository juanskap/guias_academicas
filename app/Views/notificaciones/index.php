<?php
/** @var array $notificaciones */
/** @var int $noLeidas */
?>

<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Notificaciones</h1>
        <p class="text-gray-500"><?= count($notificaciones) ?> en total · <?= (int) $noLeidas ?> sin leer</p>
    </div>
    <?php if ($noLeidas > 0): ?>
    <form method="post" action="<?= url('notificaciones/marcar-todas') ?>">
        <?= csrf_field() ?>
        <button class="px-4 py-2 bg-[#005880] hover:bg-[#004764] text-white text-sm font-semibold rounded-lg transition">Marcar todas como leídas</button>
    </form>
    <?php endif; ?>
</div>

<?php if (!$notificaciones): ?>
<div class="bg-white rounded-xl shadow p-8 text-center text-gray-500">No tienes notificaciones.</div>
<?php else: ?>
<ol class="space-y-3">
    <?php foreach ($notificaciones as $n): ?>
    <li class="bg-white rounded-xl shadow p-4 flex items-start gap-4 <?= $n['leida'] ? 'opacity-75' : 'border-l-4 border-[#0b6f9e]' ?>">
        <div class="flex-1">
            <p class="font-medium text-sm"><?= e($n['titulo']) ?></p>
            <p class="text-sm text-gray-600 mt-0.5"><?= e($n['mensaje']) ?></p>
            <p class="text-xs text-gray-400 mt-1">
                <?= e(format_date($n['creado_en'])) ?>
                <?php if ($n['codigo']): ?>
                · <a href="<?= url('proyectos/ver/' . $n['proyecto_id']) ?>" class="text-[#005880] hover:underline"><?= e($n['codigo']) ?></a>
                <?php endif; ?>
            </p>
        </div>
        <?php if (!$n['leida']): ?>
        <form method="post" action="<?= url('notificaciones/marcar-leida/' . $n['id']) ?>">
            <?= csrf_field() ?>
            <button class="text-xs px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg">Marcar leída</button>
        </form>
        <?php endif; ?>
    </li>
    <?php endforeach; ?>
</ol>
<?php endif; ?>
