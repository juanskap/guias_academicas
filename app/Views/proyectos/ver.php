<?php
/** @var array $proyecto */
/** @var array $etapas */
use App\Core\Auth;
use App\Core\Request;
$rol = Auth::role();
?>

<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900"><?= e($proyecto['codigo']) ?></h1>
        <p class="text-gray-500"><?= e($proyecto['nombre']) ?></p>
    </div>
    <div class="flex gap-2">
        <a href="<?= url('proyectos') ?>" class="px-4 py-2 text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg text-sm">← Proyectos</a>
        <a href="<?= url('plan/' . $proyecto['id']) ?>" class="px-4 py-2 text-[#005880] hover:text-[#00394f] border border-indigo-300 rounded-lg text-sm">Plan de actividades</a>
        <?php if ($rol === 'admin'): ?>
        <a href="<?= url('proyectos/asignar/' . $proyecto['id']) ?>" class="bg-[#0B803A] hover:bg-[#0a6b31] text-white text-sm font-semibold px-4 py-2 rounded-lg transition">Asignar tutor</a>
        <?php endif; ?>
    </div>
</div>

<!-- Datos generales -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow p-4">
        <p class="text-xs text-gray-500 uppercase">Tipo de proyecto</p>
        <p class="font-semibold mt-1"><?= e($proyecto['tipo_proyecto']) ?></p>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <p class="text-xs text-gray-500 uppercase">Estudiante</p>
        <p class="font-semibold mt-1"><?= e($proyecto['estudiante_nombre']) ?></p>
        <p class="text-xs text-gray-400"><?= e($proyecto['carrera'] ?? '') ?> · <?= e($proyecto['estudiante_codigo'] ?? '') ?><?= !empty($proyecto['estudiante_cedula']) ? ' · C.C. ' . e($proyecto['estudiante_cedula']) : '' ?></p>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <p class="text-xs text-gray-500 uppercase">Tutor</p>
        <p class="font-semibold mt-1"><?= e($proyecto['tutor_nombre'] ?? 'Sin asignar') ?></p>
        <p class="text-xs text-gray-400"><?= $proyecto['tutor_nombre'] ? 'Tutoría activa' : 'Pendiente de asignación' ?></p>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <p class="text-xs text-gray-500 uppercase">Estado</p>
        <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-xs font-semibold <?= e(estado_badge($proyecto['estado'])) ?>"><?= e(ucwords(str_replace('_', ' ', $proyecto['estado']))) ?></span>
        <p class="text-xs text-gray-400 mt-2"><?= $proyecto['periodo_id'] ? 'Periodo: ' . e($proyecto['periodo_nombre'] ?? '') : 'Periodo: sin asignar' ?></p>
    </div>
</div>

<?php if ($rol === 'admin'): ?>
<form method="post" action="<?= url('proyectos/asignar-periodo/' . $proyecto['id']) ?>" class="bg-white rounded-xl shadow p-4 mb-6 flex flex-wrap items-end gap-3">
    <input type="hidden" name="_csrf" value="<?= e(Request::csrfToken()) ?>">
    <div class="flex-1 min-w-[200px]">
        <label class="block text-xs font-medium text-gray-500 mb-1">Periodo académico</label>
        <select name="periodo_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-sm">
            <option value="0">Sin periodo</option>
            <?php foreach ($periodos as $pa): ?>
            <option value="<?= (int) $pa['id'] ?>" <?= (int) $pa['id'] === (int) $proyecto['periodo_id'] ? 'selected' : '' ?>>
                <?= e($pa['nombre']) ?> (<?= e(date('d/m/Y', strtotime($pa['fecha_inicio']))) ?> — <?= e(date('d/m/Y', strtotime($pa['fecha_fin']))) ?>)
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="bg-slate-700 hover:bg-slate-800 text-white text-sm font-semibold px-4 py-2 rounded-lg">Asignar periodo</button>
</form>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Etapas -->
    <div class="lg:col-span-2 bg-white rounded-xl shadow p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-gray-900">Etapas del proyecto</h2>
            <div class="flex items-center gap-2 text-sm">
                <span class="text-gray-500">Avance total:</span>
                <span class="font-bold text-[#005880]"><?= (float) $proyecto['porcentaje_avance'] ?>%</span>
            </div>
        </div>

        <div class="h-2 bg-gray-200 rounded-full overflow-hidden mb-5">
            <div class="h-full bg-[#0b6f9e] rounded-full transition-all" style="width: <?= (float) $proyecto['porcentaje_avance'] ?>%"></div>
        </div>

        <ol class="space-y-2">
            <?php $anterioresAprobadas = true; ?>
            <?php foreach ($etapas as $i => $et): ?>
            <?php
                $esActual = $anterioresAprobadas && $et['estado_etapa'] !== 'aprobada';
                $bloqueada = !$esActual && $et['estado_etapa'] !== 'aprobada' && $rol === 'estudiante';
                if ($et['estado_etapa'] !== 'aprobada') { $anterioresAprobadas = false; }
            ?>
            <li class="flex items-center gap-3 p-3 rounded-lg <?= $et['estado_etapa'] === 'aprobada' ? 'bg-green-50 border border-green-200' : ($bloqueada ? 'bg-gray-50 opacity-60' : 'bg-gray-50') ?>">
                <span class="w-8 h-8 flex items-center justify-center rounded-full text-xs font-bold <?= $et['estado_etapa'] === 'aprobada' ? 'bg-green-500 text-white' : ($esActual ? 'bg-[#005880] text-white' : 'bg-[#dceef7] text-[#004764]') ?>">
                    <?= $et['estado_etapa'] === 'aprobada' ? '✓' : $i + 1 ?>
                </span>
                <div class="flex-1">
                    <p class="font-medium text-sm"><?= e($et['nombre']) ?></p>
                    <p class="text-xs text-gray-400">
                        <?php if ($et['estado_etapa'] === 'aprobada'): ?>
                            Etapa aprobada · <?= e($et['final_nombre'] ?? 'Documento final') ?>
                        <?php elseif ($bloqueada && $et['trabajo_id']): ?>
                            Documento v<?= (int) $et['trabajo_version'] ?> · <?= e(ucwords(str_replace('_', ' ', $et['trabajo_estado']))) ?> · 🔒 Completa las etapas anteriores
                        <?php elseif ($bloqueada): ?>
                            Pendiente de documento · 🔒 Completa las etapas anteriores
                        <?php elseif ($et['trabajo_id']): ?>
                            Documento v<?= (int) $et['trabajo_version'] ?> · <?= e(ucwords(str_replace('_', ' ', $et['trabajo_estado']))) ?>
                        <?php else: ?>
                            Pendiente de documento
                        <?php endif; ?>
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <?php if ($et['trabajo_id']): ?>
                    <a href="<?= url('documentos/ver/' . $et['trabajo_id']) ?>" class="text-xs px-3 py-1.5 bg-[#005880] hover:bg-[#004764] text-white rounded-lg">Ver documento</a>
                    <?php endif; ?>
                    <?php if ($et['estado_etapa'] === 'aprobada' && $et['final_id']): ?>
                    <a href="<?= url('documentos/ver/' . $et['final_id']) ?>" class="text-xs px-3 py-1.5 bg-[#0B803A] hover:bg-[#0a6b31] text-white rounded-lg">Final</a>
                    <?php endif; ?>
                    <?php if ($rol === 'estudiante' && $esActual): ?>
                    <a href="<?= url('documentos/subir-form/' . $proyecto['id'] . '/' . $et['id']) ?>" class="text-xs px-3 py-1.5 bg-slate-700 hover:bg-slate-800 text-white rounded-lg">Subir documento</a>
                    <?php endif; ?>
                </div>
            </li>
            <?php endforeach; ?>
        </ol>
    </div>

    <!-- Información adicional -->
    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow p-5">
            <h2 class="font-semibold text-gray-900 mb-3">Detalles</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Código</dt><dd class="font-medium"><?= e($proyecto['codigo']) ?></dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Creado</dt><dd><?= e(format_date($proyecto['fecha_creacion'])) ?></dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Fecha límite</dt><dd><?= e($proyecto['fecha_limite'] ? date('d/m/Y', strtotime($proyecto['fecha_limite'])) : '—') ?></dd></div>
            </dl>
            <?php if ($proyecto['descripcion']): ?>
            <p class="text-sm text-gray-600 mt-3 border-t pt-3"><?= nl2br(e($proyecto['descripcion'])) ?></p>
            <?php endif; ?>
        </div>

        <?php if (!empty($consolidadoCompleto) || !empty($consolidadoGenerado)): ?>
        <div class="bg-white rounded-xl shadow p-5 border-l-4 border-[#005880]">
            <h2 class="font-semibold text-gray-900 mb-1">📚 Documento unificado</h2>
            <?php if (!empty($consolidadoGenerado)): ?>
                <p class="text-sm text-gray-500 mb-3">Los documentos finales (PDF y Word) ya fueron generados.</p>
                <a href="<?= url('consolidado/ver/' . $proyecto['id']) ?>" class="inline-block px-4 py-2 bg-[#005880] hover:bg-[#004764] text-white text-sm font-semibold rounded-lg transition">Ver documentos unificados</a>
            <?php else: ?>
                <p class="text-sm text-gray-500 mb-3">El proyecto está al 100%. Genera el documento unificado (Perfil y Proyecto, en PDF y Word).</p>
                <a href="<?= url('consolidado/ver/' . $proyecto['id']) ?>" class="inline-block px-4 py-2 bg-[#0B803A] hover:bg-[#0a6b31] text-white text-sm font-semibold rounded-lg transition">Generar documento unificado</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow p-5">
            <h2 class="font-semibold text-gray-900 mb-3">🕓 Historial de actividades</h2>
            <?php if (!$historial): ?>
                <p class="text-sm text-gray-500">Sin registros de actividad.</p>
            <?php else: ?>
            <ol class="relative border-l-2 border-gray-100 ml-2 space-y-4">
                <?php foreach ($historial as $h): ?>
                <li class="relative pl-5">
                    <span class="absolute -left-[7px] top-1 w-3 h-3 rounded-full bg-[#005880]"></span>
                    <p class="text-sm font-medium"><?= e($h['accion']) ?></p>
                    <p class="text-xs text-gray-500"><?= e($h['descripcion']) ?></p>
                    <p class="text-xs text-gray-400 mt-0.5"><?= e($h['nombres'] . ' ' . ($h['apellidos'] ?? '')) ?> · <?= e(format_date($h['creado_en'])) ?></p>
                </li>
                <?php endforeach; ?>
            </ol>
            <?php endif; ?>
        </div>
    </div>
</div>
