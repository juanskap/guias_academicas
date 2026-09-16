<?php
/** @var array $proyecto */
/** @var array $actividades */
/** @var array $plazos */
/** @var bool $puedeGestionar */
/** @var array $etapas */
/** @var array $responsables */
use App\Core\Request;
use App\Core\Auth;
$rol = Auth::role();
?>

<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Plan de actividades</h1>
        <p class="text-gray-500"><?= e($proyecto['codigo']) ?> · <?= e($proyecto['nombre']) ?></p>
    </div>
    <div class="flex gap-2">
        <a href="<?= url('proyectos/ver/' . $proyecto['id']) ?>" class="px-4 py-2 text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg text-sm">← Volver al proyecto</a>
    </div>
</div>

<?php if ($puedeGestionar): ?>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Crear actividad -->
    <div class="bg-white rounded-xl shadow p-5">
        <h2 class="font-semibold text-gray-900 mb-4">Nueva actividad</h2>
        <form method="post" action="<?= url('plan/actividad-guardar/' . $proyecto['id']) ?>" class="space-y-3">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Descripción *</label>
                <input type="text" name="descripcion" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6f9e] outline-none">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                    <select name="tipo" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white">
                        <option value="entrega">Entrega</option>
                        <option value="revision">Revisión</option>
                        <option value="correccion">Corrección</option>
                        <option value="aprobacion">Aprobación</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Etapa (opcional)</label>
                    <select name="etapa_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white">
                        <option value="0">— Sin etapa —</option>
                        <?php foreach ($etapas as $et): ?>
                        <option value="<?= (int) $et['id'] ?>"><?= e($et['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Responsable *</label>
                <select name="responsable_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white">
                    <option value="">— Seleccionar —</option>
                    <?php foreach ($responsables as $rid => $rnombre): ?>
                    <option value="<?= (int) $rid ?>"><?= e($rnombre) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Inicio *</label>
                    <input type="date" name="fecha_inicio" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Límite *</label>
                    <input type="date" name="fecha_limite" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
            </div>
            <button type="submit" class="w-full bg-[#005880] hover:bg-[#004764] text-white font-semibold py-2 rounded-lg transition">Registrar actividad</button>
        </form>
    </div>

    <!-- Crear plazo -->
    <div class="bg-white rounded-xl shadow p-5">
        <h2 class="font-semibold text-gray-900 mb-4">Nuevo plazo</h2>
        <form method="post" action="<?= url('plan/plazo-guardar/' . $proyecto['id']) ?>" class="space-y-3">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Descripción *</label>
                <input type="text" name="descripcion" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6f9e] outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Actividad asociada (opcional)</label>
                <select name="actividad_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white">
                    <option value="0">— Sin actividad —</option>
                    <?php foreach ($actividades as $ac): ?>
                    <option value="<?= (int) $ac['id'] ?>"><?= e($ac['descripcion']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Inicio *</label>
                    <input type="date" name="fecha_inicio" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Límite *</label>
                    <input type="date" name="fecha_limite" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
            </div>
            <button type="submit" class="w-full bg-[#0B803A] hover:bg-[#0a6b31] text-white font-semibold py-2 rounded-lg transition">Registrar plazo</button>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Actividades -->
    <div class="bg-white rounded-xl shadow p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-gray-900">Actividades (<?= count($actividades) ?>)</h2>
        </div>
        <?php if (!$actividades): ?>
        <p class="text-sm text-gray-500">Sin actividades registradas.</p>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-gray-500 uppercase border-b">
                        <th class="py-2 pr-2">Actividad</th>
                        <th class="py-2 pr-2">Responsable</th>
                        <th class="py-2 pr-2">Inicio</th>
                        <th class="py-2 pr-2">Límite</th>
                        <th class="py-2 pr-2">Estado</th>
                        <th class="py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($actividades as $ac): ?>
                    <tr class="border-b last:border-0 align-top">
                        <td class="py-3 pr-2">
                            <p class="font-medium"><?= e($ac['descripcion']) ?></p>
                            <p class="text-xs text-gray-400"><?= e(ucfirst($ac['tipo'])) ?><?= $ac['etapa_nombre'] ? ' · ' . e($ac['etapa_nombre']) : '' ?></p>
                        </td>
                        <td class="py-3 pr-2 text-gray-600"><?= e($ac['responsable_nombre']) ?></td>
                        <td class="py-3 pr-2 text-gray-600 whitespace-nowrap"><?= date('d/m/Y', strtotime($ac['fecha_inicio'])) ?></td>
                        <td class="py-3 pr-2 whitespace-nowrap">
                            <?= date('d/m/Y', strtotime($ac['fecha_limite'])) ?>
                            <?php $dr = dias_restantes($ac['fecha_limite']); if ($dr !== null && $ac['estado'] !== 'completada' && $ac['estado'] !== 'vencida'): ?>
                            <p class="text-xs <?= $dr < 0 ? 'text-red-600 font-semibold' : 'text-gray-400' ?>"><?= $dr < 0 ? abs($dr) . ' días atrasada' : $dr . ' días restantes' ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 pr-2"><span class="px-2 py-0.5 rounded-full text-xs font-semibold <?= e(estado_badge($ac['estado'])) ?>"><?= e(ucfirst($ac['estado'])) ?></span></td>
                        <td class="py-3 text-right whitespace-nowrap">
                            <?php if ($puedeGestionar): ?>
                            <div class="flex gap-1 justify-end">
                                <?php if ($ac['estado'] === 'pendiente'): ?>
                                <form method="post" action="<?= url('plan/actividad-estado/' . $ac['id']) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="estado" value="en_curso">
                                    <button class="text-xs px-2 py-1 bg-cyan-600 hover:bg-cyan-700 text-white rounded">Iniciar</button>
                                </form>
                                <?php endif; ?>
                                <?php if (in_array($ac['estado'], ['pendiente', 'en_curso'], true)): ?>
                                <form method="post" action="<?= url('plan/actividad-estado/' . $ac['id']) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="estado" value="completada">
                                    <button class="text-xs px-2 py-1 bg-green-600 hover:bg-green-700 text-white rounded">Completar</button>
                                </form>
                                <?php endif; ?>
                                <form method="post" action="<?= url('plan/actividad-eliminar/' . $ac['id']) ?>" onsubmit="return confirm('¿Eliminar esta actividad?');">
                                    <?= csrf_field() ?>
                                    <button class="text-xs px-2 py-1 bg-red-100 hover:bg-red-200 text-red-700 rounded">Eliminar</button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Plazos -->
    <div class="bg-white rounded-xl shadow p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-gray-900">Plazos (<?= count($plazos) ?>)</h2>
        </div>
        <?php if (!$plazos): ?>
        <p class="text-sm text-gray-500">Sin plazos registrados.</p>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-gray-500 uppercase border-b">
                        <th class="py-2 pr-2">Plazo</th>
                        <th class="py-2 pr-2">Inicio</th>
                        <th class="py-2 pr-2">Límite</th>
                        <th class="py-2 pr-2">Estado</th>
                        <th class="py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($plazos as $pl): ?>
                    <tr class="border-b last:border-0 align-top">
                        <td class="py-3 pr-2">
                            <p class="font-medium"><?= e($pl['descripcion']) ?></p>
                            <?php if ($pl['actividad_descripcion']): ?>
                            <p class="text-xs text-gray-400"><?= e($pl['actividad_descripcion']) ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 pr-2 text-gray-600 whitespace-nowrap"><?= date('d/m/Y', strtotime($pl['fecha_inicio'])) ?></td>
                        <td class="py-3 pr-2 whitespace-nowrap">
                            <?= date('d/m/Y', strtotime($pl['fecha_limite'])) ?>
                            <?php $dr = dias_restantes($pl['fecha_limite']); if ($dr !== null && $pl['estado'] !== 'completado' && $pl['estado'] !== 'vencido'): ?>
                            <p class="text-xs <?= $dr < 0 ? 'text-red-600 font-semibold' : 'text-gray-400' ?>"><?= $dr < 0 ? abs($dr) . ' días atrasado' : $dr . ' días restantes' ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 pr-2"><span class="px-2 py-0.5 rounded-full text-xs font-semibold <?= e(estado_badge($pl['estado'])) ?>"><?= e(ucfirst($pl['estado'])) ?></span></td>
                        <td class="py-3 text-right whitespace-nowrap">
                            <?php if ($puedeGestionar): ?>
                            <div class="flex gap-1 justify-end">
                                <?php if ($pl['estado'] === 'activo'): ?>
                                <form method="post" action="<?= url('plan/plazo-estado/' . $pl['id']) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="estado" value="completado">
                                    <button class="text-xs px-2 py-1 bg-green-600 hover:bg-green-700 text-white rounded">Completar</button>
                                </form>
                                <?php endif; ?>
                                <form method="post" action="<?= url('plan/plazo-eliminar/' . $pl['id']) ?>" onsubmit="return confirm('¿Eliminar este plazo?');">
                                    <?= csrf_field() ?>
                                    <button class="text-xs px-2 py-1 bg-red-100 hover:bg-red-200 text-red-700 rounded">Eliminar</button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
