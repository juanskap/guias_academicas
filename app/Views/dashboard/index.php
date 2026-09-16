<?php
/** @var array $stats */
/** @var array $eventos */
use App\Core\Auth;
$rol = Auth::role();
?>

<h1 class="text-2xl font-bold text-gray-900 mb-1">Panel principal</h1>
<p class="text-gray-500 mb-6">Bienvenido al sistema <?= e(APP_NAME) ?>.</p>

<?php if ($rol === 'admin'): ?>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-white rounded-xl shadow p-5">
        <p class="text-sm text-gray-500">Estudiantes</p>
        <p class="text-3xl font-bold"><?= (int) $stats['total_estudiantes'] ?></p>
    </div>
    <div class="bg-white rounded-xl shadow p-5">
        <p class="text-sm text-gray-500">Docentes</p>
        <p class="text-3xl font-bold"><?= (int) $stats['total_docentes'] ?></p>
    </div>
    <div class="bg-white rounded-xl shadow p-5">
        <p class="text-sm text-gray-500">Proyectos</p>
        <p class="text-3xl font-bold"><?= (int) $stats['total_proyectos'] ?></p>
    </div>
    <div class="bg-white rounded-xl shadow p-5">
        <p class="text-sm text-gray-500">Finalizados</p>
        <p class="text-3xl font-bold text-[#0B803A]"><?= (int) $stats['proyectos_finalizados'] ?></p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-6">
    <div class="bg-white rounded-xl shadow p-5">
        <h3 class="font-semibold mb-3">Proyectos por tipo</h3>
        <div class="space-y-3">
            <?php foreach ([
                'Titulación' => 'proyectos_titulacion',
                'Vinculación' => 'proyectos_vinculacion',
                'PIS' => 'proyectos_pis',
            ] as $label => $key): ?>
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span><?= e($label) ?></span>
                    <span class="font-semibold"><?= (int) $stats[$key] ?></span>
                </div>
                <?php $max = max(1, (int) $stats['total_proyectos']); ?>
                <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div class="h-full bg-[#0b6f9e] rounded-full" style="width: <?= round((int) $stats[$key] / $max * 100) ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow p-5">
        <h3 class="font-semibold mb-3">Estado de proyectos</h3>
        <div class="space-y-3">
            <?php foreach ([
                'En revisión' => 'proyectos_en_revision',
                'Con observaciones' => 'proyectos_con_observaciones',
                'Aprobados' => 'proyectos_aprobados',
                'Vencidos' => 'proyectos_vencidos',
            ] as $label => $key): ?>
            <div class="flex justify-between text-sm">
                <span><?= e($label) ?></span>
                <span class="font-semibold"><?= (int) $stats[$key] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php elseif ($rol === 'docente'): ?>
<div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow p-5">
        <p class="text-sm text-gray-500">Proyectos asignados</p>
        <p class="text-3xl font-bold"><?= (int) $stats['proyectos_activos'] ?></p>
    </div>
    <div class="bg-white rounded-xl shadow p-5">
        <p class="text-sm text-gray-500">Por revisar</p>
        <p class="text-3xl font-bold text-[#005880]"><?= (int) ($resumen['por_revisar'] ?? 0) ?></p>
    </div>
    <div class="bg-white rounded-xl shadow p-5">
        <p class="text-sm text-gray-500">Con observaciones</p>
        <p class="text-3xl font-bold text-amber-600"><?= (int) ($resumen['con_observaciones'] ?? 0) ?></p>
    </div>
    <div class="bg-white rounded-xl shadow p-5">
        <p class="text-sm text-gray-500">Aprobados</p>
        <p class="text-3xl font-bold text-[#0B803A]"><?= (int) ($resumen['aprobados'] ?? 0) ?></p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow p-5">
        <h3 class="font-semibold mb-3">📥 Documentos por revisar</h3>
        <?php if (!$pendientes['documentos']): ?>
            <p class="text-sm text-gray-500">Sin documentos pendientes.</p>
        <?php else: ?>
            <ol class="space-y-2">
                <?php foreach ($pendientes['documentos'] as $doc): ?>
                <li class="p-3 rounded-lg bg-gray-50 text-sm flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <a href="<?= url('documentos/ver/' . $doc['id']) ?>" class="font-medium text-[#005880] hover:underline truncate block"><?= e($doc['nombre_original']) ?></a>
                        <p class="text-xs text-gray-400"><?= e($doc['codigo']) ?> · <?= e($doc['etapa']) ?> · v<?= (int) $doc['version'] ?></p>
                    </div>
                    <a href="<?= url('documentos/ver/' . $doc['id']) ?>" class="text-xs px-2 py-1 bg-[#0B803A] text-white rounded shrink-0 hover:opacity-80">Revisar</a>
                </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-xl shadow p-5">
        <h3 class="font-semibold mb-3">💬 Observaciones por atender</h3>
        <?php if (!$pendientes['observaciones']): ?>
            <p class="text-sm text-gray-500">Sin observaciones en curso.</p>
        <?php else: ?>
            <ol class="space-y-2">
                <?php foreach ($pendientes['observaciones'] as $obs): ?>
                <li class="p-3 rounded-lg bg-gray-50 text-sm">
                    <p class="font-medium truncate"><?= e($obs['comentario']) ?></p>
                    <p class="text-xs text-gray-400 mt-1"><?= e($obs['codigo']) ?> · <?= e($obs['etapa']) ?></p>
                    <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-xs font-semibold <?= $obs['obs_estado'] === 'en_correccion' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600' ?>"><?= e($obs['obs_estado'] === 'en_correccion' ? 'Corregida, falta aprobar' : 'Pendiente') ?></span>
                </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-xl shadow p-5">
        <h3 class="font-semibold mb-3">⏰ Actividades por vencer / vencidas</h3>
        <?php if (!$pendientes['actividades']): ?>
            <p class="text-sm text-gray-500">Sin actividades próximas a vencer.</p>
        <?php else: ?>
            <ol class="space-y-2">
                <?php foreach ($pendientes['actividades'] as $act): ?>
                <li class="p-3 rounded-lg bg-gray-50 text-sm">
                    <p class="font-medium truncate"><?= e($act['descripcion']) ?></p>
                    <p class="text-xs text-gray-400"><?= e($act['codigo']) ?> · Límite <?= e(date('d/m/Y', strtotime($act['fecha_limite']))) ?></p>
                    <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-xs font-semibold <?= e(estado_badge($act['act_estado'])) ?>"><?= e(ucfirst($act['act_estado'])) ?></span>
                </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow p-5">
        <p class="text-sm text-gray-500">Mis proyectos</p>
        <p class="text-3xl font-bold"><?= (int) $stats['mis_proyectos'] ?></p>
    </div>
    <div class="bg-white rounded-xl shadow p-5">
        <p class="text-sm text-gray-500">En revisión</p>
        <p class="text-3xl font-bold text-[#005880]"><?= count(array_filter($misProyectos, fn ($p) => in_array($p['estado'], ['enviado', 'en_revision', 'reenviado'], true))) ?></p>
    </div>
    <div class="bg-white rounded-xl shadow p-5">
        <p class="text-sm text-gray-500">Con observaciones</p>
        <p class="text-3xl font-bold text-amber-600"><?= count(array_filter($misProyectos, fn ($p) => in_array($p['estado'], ['con_observaciones', 'en_correccion'], true))) ?></p>
    </div>
</div>

<?php if (!$misProyectos): ?>
<div class="bg-white rounded-xl shadow p-8 text-center mb-6">
    <p class="text-gray-500 mb-3">Aún no has registrado proyectos.</p>
    <a href="<?= url('proyectos/nuevo') ?>" class="inline-block bg-[#005880] hover:bg-[#004764] text-white text-sm font-semibold px-4 py-2 rounded-lg transition">+ Registrar proyecto</a>
</div>
<?php else: ?>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    <?php foreach ($misProyectos as $mp): ?>
    <a href="<?= url('proyectos/ver/' . $mp['id']) ?>" class="bg-white rounded-xl shadow p-5 block hover:shadow-md transition">
        <div class="flex items-center justify-between gap-2 mb-2">
            <p class="text-xs font-mono text-gray-400"><?= e($mp['codigo']) ?></p>
            <span class="text-xs px-2 py-0.5 rounded-full font-semibold <?= e(estado_badge($mp['estado'])) ?>"><?= e(ucfirst(str_replace('_', ' ', $mp['estado']))) ?></span>
        </div>
        <p class="font-semibold text-gray-900"><?= e($mp['nombre']) ?></p>
        <p class="text-xs text-gray-500 mt-1"><?= e($mp['tipo_proyecto']) ?><?= $mp['tutor'] ? ' · Tutor: ' . e($mp['tutor']) : '' ?></p>
        <?php if ($mp['etapa_actual']): ?>
        <p class="text-xs text-gray-500 mt-0.5">Etapa actual: <?= e($mp['etapa_actual']) ?></p>
        <?php endif; ?>
        <div class="flex items-center gap-2 mt-3">
            <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                <div class="h-full bg-[#0B803A] rounded-full" style="width: <?= (float) $mp['porcentaje_avance'] ?>%"></div>
            </div>
            <span class="text-xs font-semibold"><?= (float) $mp['porcentaje_avance'] ?>%</span>
        </div>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- Próximos eventos -->
<div class="bg-white rounded-xl shadow p-5 mt-6">
    <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold">Próximos eventos</h3>
        <a href="<?= url('calendario') ?>" class="text-sm text-[#005880] hover:underline">Ver calendario →</a>
    </div>
    <?php if (!$eventos): ?>
    <p class="text-sm text-gray-500">No hay eventos próximos.</p>
    <?php else: ?>
    <ol class="space-y-2">
        <?php foreach ($eventos as $ev): ?>
        <li class="flex items-center justify-between gap-3 p-3 rounded-lg bg-gray-50 text-sm">
            <div>
                <p class="font-medium"><?= e($ev['titulo']) ?></p>
                <p class="text-xs text-gray-400"><?= e($ev['codigo']) ?> · <?= e(date('d/m/Y H:i', strtotime($ev['fecha_evento']))) ?></p>
            </div>
            <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?= e(estado_badge($ev['estado'])) ?>"><?= e(ucfirst($ev['estado'])) ?></span>
        </li>
        <?php endforeach; ?>
    </ol>
    <?php endif; ?>
</div>
