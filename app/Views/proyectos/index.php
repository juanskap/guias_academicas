<?php
/** @var array $proyectos */
use App\Core\Auth;
use App\Core\Request;
$rol = Auth::role();
?>

<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Proyectos</h1>
        <p class="text-gray-500 text-sm"><?= $rol === 'estudiante' ? 'Tus proyectos registrados' : ($rol === 'docente' ? 'Proyectos asignados a tu tutoría' : 'Todos los proyectos del sistema') ?></p>
    </div>
    <?php if ($rol === 'estudiante'): ?>
    <a href="<?= url('proyectos/nuevo') ?>" class="bg-[#005880] hover:bg-[#004764] text-white text-sm font-semibold px-4 py-2 rounded-lg transition">+ Registrar proyecto</a>
    <?php endif; ?>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">Código</th>
                    <th class="px-4 py-3 text-left">Proyecto</th>
                    <th class="px-4 py-3 text-left">Tipo</th>
                    <?php if ($rol !== 'estudiante'): ?>
                    <th class="px-4 py-3 text-left">Estudiante</th>
                    <?php endif; ?>
                    <?php if ($rol !== 'docente'): ?>
                    <th class="px-4 py-3 text-left">Tutor</th>
                    <?php endif; ?>
                    <th class="px-4 py-3 text-left">Etapa actual</th>
                    <th class="px-4 py-3 text-left">Avance</th>
                    <th class="px-4 py-3 text-left">Estado</th>
                    <th class="px-4 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($proyectos as $p): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-500"><?= e($p['codigo']) ?></td>
                    <td class="px-4 py-3">
                        <div class="font-medium"><?= e($p['nombre']) ?></div>
                        <div class="text-xs text-gray-400">Actualizado: <?= e(format_date($p['ultima_actualizacion'])) ?></div>
                    </td>
                    <td class="px-4 py-3"><?= e($p['tipo_proyecto']) ?></td>
                    <?php if ($rol !== 'estudiante'): ?>
                    <td class="px-4 py-3"><?= e($p['estudiante'] ?? '—') ?></td>
                    <?php endif; ?>
                    <?php if ($rol !== 'docente'): ?>
                    <td class="px-4 py-3"><?= e($p['tutor'] ?? 'Sin asignar') ?></td>
                    <?php endif; ?>
                    <td class="px-4 py-3"><?= e($p['etapa_actual'] ?? '—') ?></td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <div class="w-16 h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-[#0b6f9e] rounded-full" style="width: <?= (float) $p['porcentaje_avance'] ?>%"></div>
                            </div>
                            <span class="text-xs font-medium"><?= (float) $p['porcentaje_avance'] ?>%</span>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold <?= e(estado_badge($p['estado'])) ?>"><?= e(ucwords(str_replace('_', ' ', $p['estado']))) ?></span>
                    </td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <a href="<?= url('proyectos/ver/' . $p['id']) ?>" class="text-[#005880] hover:text-[#00394f] text-sm font-medium">Ver</a>
                        <?php if ($rol === 'admin'): ?>
                        <a href="<?= url('proyectos/asignar/' . $p['id']) ?>" class="text-[#0B803A] hover:text-emerald-800 text-sm font-medium ml-3">Asignar tutor</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($proyectos)): ?>
                <tr><td colspan="9" class="px-4 py-8 text-center text-gray-400">No hay proyectos.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
