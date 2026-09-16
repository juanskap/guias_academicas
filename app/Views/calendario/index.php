<?php
/** @var array $eventos */
/** @var array $proyectosGestionables */
/** @var array $etapasPorTipo */
/** @var bool $puedeGestionar */
use App\Core\Auth;
$rol = Auth::role();

$proximos = array_filter($eventos, fn ($e) => strtotime($e['fecha_evento']) >= time() && $e['estado'] !== 'completado');
$pasados = array_filter($eventos, fn ($e) => strtotime($e['fecha_evento']) < time() || $e['estado'] === 'completado');
?>

<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Calendario</h1>
        <p class="text-gray-500">Entregas, revisiones, correcciones y límites de tus proyectos.</p>
    </div>
</div>

<?php if ($puedeGestionar && $proyectosGestionables): ?>
<div class="bg-white rounded-xl shadow p-5 mb-6">
    <h2 class="font-semibold text-gray-900 mb-4">Nuevo evento</h2>
    <form method="post" action="<?= url('calendario/guardar') ?>" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
        <?= csrf_field() ?>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Proyecto y etapa *</label>
            <select name="proyecto_etapa" required class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white">
                <option value="">— Proyecto —</option>
                <?php foreach ($proyectosGestionables as $pr): ?>
                <optgroup label="<?= e($pr['codigo']) ?> · <?= e($pr['nombre']) ?>">
                    <option value="<?= (int) $pr['id'] ?>:0">Proyecto completo (sin etapa)</option>
                    <?php foreach (($etapasPorTipo[(int) $pr['tipo_proyecto_id']] ?? []) as $et): ?>
                    <option value="<?= (int) $pr['id'] ?>:<?= (int) $et['id'] ?>"><?= e($et['nombre']) ?></option>
                    <?php endforeach; ?>
                </optgroup>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Título *</label>
            <input type="text" name="titulo" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6f9e] outline-none">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
            <select name="tipo" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white">
                <option value="entrega">Entrega</option>
                <option value="revision">Revisión</option>
                <option value="correccion">Corrección</option>
                <option value="limite">Límite</option>
                <option value="otro">Otro</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha y hora *</label>
            <input type="datetime-local" name="fecha_evento" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
        </div>
        <div class="md:col-span-2 lg:col-span-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Descripción (opcional)</label>
            <input type="text" name="descripcion" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6f9e] outline-none">
        </div>
        <div class="lg:col-span-4">
            <button type="submit" class="bg-[#005880] hover:bg-[#004764] text-white font-semibold px-5 py-2 rounded-lg transition">Registrar evento</button>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Próximos -->
    <div class="bg-white rounded-xl shadow p-5">
        <h2 class="font-semibold text-gray-900 mb-4">Próximos eventos (<?= count($proximos) ?>)</h2>
        <?php if (!$proximos): ?>
        <p class="text-sm text-gray-500">Sin eventos próximos.</p>
        <?php else: ?>
        <ol class="space-y-3">
            <?php foreach ($proximos as $ev): ?>
            <li class="flex items-start gap-3 p-3 rounded-lg bg-gray-50">
                <div class="flex-1">
                    <p class="font-medium text-sm"><?= e($ev['titulo']) ?></p>
                    <p class="text-xs text-gray-400"><?= e($ev['codigo']) ?><?= $ev['etapa_nombre'] ? ' · ' . e($ev['etapa_nombre']) : '' ?></p>
                    <?php if ($ev['descripcion']): ?><p class="text-xs text-gray-500 mt-1"><?= e($ev['descripcion']) ?></p><?php endif; ?>
                    <p class="text-xs text-gray-400 mt-1"><?= e(date('d/m/Y H:i', strtotime($ev['fecha_evento']))) ?></p>
                </div>
                <div class="text-right shrink-0">
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?= e(estado_badge($ev['estado'])) ?>"><?= e(ucfirst($ev['estado'])) ?></span>
                    <?php if ($puedeGestionar && $ev['estado'] !== 'completado'): ?>
                    <form method="post" action="<?= url('calendario/estado/' . $ev['id']) ?>" class="mt-2">
                        <?= csrf_field() ?>
                        <input type="hidden" name="estado" value="completado">
                        <button class="text-xs px-2 py-1 bg-green-600 hover:bg-green-700 text-white rounded">Completar</button>
                    </form>
                    <?php endif; ?>
                    <?php if ($puedeGestionar): ?>
                    <form method="post" action="<?= url('calendario/eliminar/' . $ev['id']) ?>" class="mt-1" onsubmit="return confirm('¿Eliminar este evento?');">
                        <?= csrf_field() ?>
                        <button class="text-xs px-2 py-1 bg-red-100 hover:bg-red-200 text-red-700 rounded">Eliminar</button>
                    </form>
                    <?php endif; ?>
                </div>
            </li>
            <?php endforeach; ?>
        </ol>
        <?php endif; ?>
    </div>

    <!-- Historial -->
    <div class="bg-white rounded-xl shadow p-5">
        <h2 class="font-semibold text-gray-900 mb-4">Historial (<?= count($pasados) ?>)</h2>
        <?php if (!$pasados): ?>
        <p class="text-sm text-gray-500">Sin eventos pasados.</p>
        <?php else: ?>
        <ol class="space-y-3">
            <?php foreach ($pasados as $ev): ?>
            <li class="flex items-start gap-3 p-3 rounded-lg bg-gray-50 opacity-80">
                <div class="flex-1">
                    <p class="font-medium text-sm"><?= e($ev['titulo']) ?></p>
                    <p class="text-xs text-gray-400"><?= e($ev['codigo']) ?><?= $ev['etapa_nombre'] ? ' · ' . e($ev['etapa_nombre']) : '' ?></p>
                    <p class="text-xs text-gray-400 mt-1"><?= e(date('d/m/Y H:i', strtotime($ev['fecha_evento']))) ?></p>
                </div>
                <div class="text-right shrink-0">
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?= e(estado_badge($ev['estado'])) ?>"><?= e(ucfirst($ev['estado'])) ?></span>
                    <?php if ($puedeGestionar): ?>
                    <form method="post" action="<?= url('calendario/eliminar/' . $ev['id']) ?>" class="mt-2" onsubmit="return confirm('¿Eliminar este evento?');">
                        <?= csrf_field() ?>
                        <button class="text-xs px-2 py-1 bg-red-100 hover:bg-red-200 text-red-700 rounded">Eliminar</button>
                    </form>
                    <?php endif; ?>
                </div>
            </li>
            <?php endforeach; ?>
        </ol>
        <?php endif; ?>
    </div>
</div>
