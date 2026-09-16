<?php
/** @var array $archivos */
/** @var array $periodos */
/** @var array $tipos */
/** @var array $filtros */
use App\Core\Auth;
use App\Core\Request;

function repo_icono(string $ext): string
{
    $mapa = [
        'pdf' => '📄', 'doc' => '📝', 'docx' => '📝', 'txt' => '📄', 'odt' => '📝',
        'xls' => '📊', 'xlsx' => '📊', 'ppt' => '📽️', 'pptx' => '📽️',
        'zip' => '🗜️', 'rar' => '🗜️', '7z' => '🗜️', 'tar' => '🗜️', 'gz' => '🗜️',
        'png' => '🖼️', 'jpg' => '🖼️', 'jpeg' => '🖼️',
    ];
    return $mapa[strtolower($ext)] ?? '📎';
}

function repo_tamano(int $bytes): string
{
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}
$rol = Auth::role();
?>

<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">📦 Repositorio institucional</h1>
        <p class="text-gray-500 text-sm">Documentos finales sellados y entregables técnicos de los proyectos</p>
    </div>
    <a href="<?= url('repositorio/subir') ?>" class="bg-[#005880] hover:bg-[#004764] text-white text-sm font-semibold px-4 py-2 rounded-lg transition">+ Subir archivo</a>
</div>

<!-- Filtros -->
<form method="get" action="<?= url('repositorio') ?>" class="bg-white rounded-xl shadow p-4 mb-6 grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
    <div class="col-span-1">
        <label class="block text-xs font-medium text-gray-500 mb-1">Cédula</label>
        <input type="text" name="cedula" value="<?= e($filtros['cedula']) ?>" placeholder="Nº de cédula"
               class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#0b6f9e]">
    </div>
    <div class="col-span-1">
        <label class="block text-xs font-medium text-gray-500 mb-1">Carrera</label>
        <input type="text" name="carrera" value="<?= e($filtros['carrera']) ?>" placeholder="Carrera"
               class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#0b6f9e]">
    </div>
    <div class="col-span-1">
        <label class="block text-xs font-medium text-gray-500 mb-1">Texto</label>
        <input type="text" name="texto" value="<?= e($filtros['texto']) ?>" placeholder="Título / proyecto"
               class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-[#0b6f9e]">
    </div>
    <div class="col-span-1">
        <label class="block text-xs font-medium text-gray-500 mb-1">Tipo</label>
        <select name="tipo" class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-sm outline-none">
            <option value="">Todos</option>
            <?php foreach ($tipos as $clave => $etiqueta): ?>
            <option value="<?= e($clave) ?>" <?= $filtros['tipo'] === $clave ? 'selected' : '' ?>><?= e($etiqueta) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-span-1">
        <label class="block text-xs font-medium text-gray-500 mb-1">Periodo</label>
        <select name="periodo_id" class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-sm outline-none">
            <option value="0">Todos</option>
            <?php foreach ($periodos as $pa): ?>
            <option value="<?= (int) $pa['id'] ?>" <?= $filtros['periodo_id'] == $pa['id'] ? 'selected' : '' ?>><?= e($pa['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-span-1">
        <label class="block text-xs font-medium text-gray-500 mb-1">Desde</label>
        <input type="date" name="desde" value="<?= e($filtros['desde']) ?>"
               class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-sm outline-none">
    </div>
    <div class="col-span-1">
        <label class="block text-xs font-medium text-gray-500 mb-1">Hasta</label>
        <input type="date" name="hasta" value="<?= e($filtros['hasta']) ?>"
               class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-sm outline-none">
    </div>
    <div class="lg:col-span-7 flex items-center gap-3 pt-1">
        <label class="flex items-center gap-2 text-sm text-gray-600">
            <input type="checkbox" name="historial" value="1" <?= $filtros['historial'] ? 'checked' : '' ?> class="w-4 h-4 text-[#005880]">
            Incluir versiones reemplazadas
        </label>
        <button type="submit" class="ml-auto bg-slate-700 hover:bg-slate-800 text-white text-sm font-semibold px-4 py-1.5 rounded-lg">🔍 Filtrar</button>
        <a href="<?= url('repositorio') ?>" class="text-sm text-gray-500 hover:text-gray-800 border border-gray-300 px-4 py-1.5 rounded-lg">Limpiar</a>
    </div>
</form>

<?php if ($rol !== 'admin'): ?>
<div class="mb-4 text-xs text-gray-500">
    Ves tus archivos<?= $rol === 'docente' ? ' (tutorías)' : '' ?> y las <strong>tesis públicas</strong> de proyectos finalizados (solo PDF, para referencia).
</div>
<?php endif; ?>

<div class="bg-white rounded-xl shadow overflow-x-auto">
    <?php if (!$archivos): ?>
    <p class="p-10 text-center text-gray-400">No se encontraron archivos con los filtros aplicados.</p>
    <?php else: ?>
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-gray-500 border-b bg-gray-50">
                <th class="px-4 py-3">Archivo</th>
                <th class="px-4 py-3">Estudiante</th>
                <th class="px-4 py-3">Proyecto</th>
                <th class="px-4 py-3">Periodo</th>
                <th class="px-4 py-3">Tipo</th>
                <th class="px-4 py-3 text-right">Tamaño</th>
                <th class="px-4 py-3">Fecha</th>
                <th class="px-4 py-3 text-right">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($archivos as $a): ?>
            <tr class="border-b last:border-0 hover:bg-gray-50">
                <td class="px-4 py-3">
                    <div class="flex items-start gap-2">
                        <span class="text-lg leading-none mt-0.5"><?= repo_icono($a['formato']) ?></span>
                        <div>
                            <p class="font-medium text-gray-900"><?= e($a['titulo']) ?>
                                <?php if ((int) $a['version'] > 1): ?><span class="text-[11px] text-gray-400">v<?= (int) $a['version'] ?></span><?php endif; ?>
                                <?php if ($a['estado'] === 'reemplazado'): ?><span class="text-[11px] text-amber-600">(reemplazado)</span><?php endif; ?>
                                <?php if (($a['acceso'] ?? '') === 'publico'): ?><span class="text-[11px] text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-full px-1.5 py-0.5">📖 Tesis pública</span><?php endif; ?>
                            </p>
                            <p class="text-xs text-gray-400"><?= e($a['nombre_original']) ?></p>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3">
                    <p class="text-gray-900"><?= e($a['estudiante_nombre'] ?? '—') ?><?= $rol === 'admin' ? '' : '' ?></p>
                    <p class="text-xs text-gray-400">
                        <?php $mostrarCedula = !empty($a['cedula']) && ($a['acceso'] ?? '') !== 'publico'; ?>
                        <?= $mostrarCedula ? 'C.C. ' . e($a['cedula']) : '' ?><?= $mostrarCedula && !empty($a['carrera']) ? ' · ' : '' ?><?= e($a['carrera'] ?? '') ?>
                    </p>
                </td>
                <td class="px-4 py-3">
                    <p class="text-[#005880] font-medium"><?= e($a['proyecto_codigo'] ?? '—') ?></p>
                    <p class="text-xs text-gray-400 line-clamp-1"><?= e($a['proyecto_nombre'] ?? '') ?></p>
                </td>
                <td class="px-4 py-3"><?= e($a['periodo_nombre'] ?? '—') ?></td>
                <td class="px-4 py-3">
                    <span class="inline-block px-2 py-0.5 rounded-full text-[11px] font-semibold <?= $a['tipo'] === 'documento_final' ? 'bg-emerald-100 text-emerald-700' : ($a['tipo'] === 'entregable_tecnico' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600') ?>">
                        <?= e($tipos[$a['tipo']] ?? $a['tipo']) ?>
                    </span>
                </td>
                <td class="px-4 py-3 text-right whitespace-nowrap"><?= repo_tamano((int) $a['tamanio']) ?></td>
                <td class="px-4 py-3 whitespace-nowrap text-gray-500"><?= e(format_date($a['creado_en'])) ?></td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    <?php if (in_array(strtolower($a['formato']), ['pdf', 'txt'], true)): ?>
                    <a href="<?= url('repositorio/previsualizar/' . $a['id']) ?>" target="_blank" title="Ver"
                       class="inline-block px-2 py-1 text-[#005880] hover:text-[#00394f] border border-[#d5e6ee] rounded-md text-xs">Ver</a>
                    <?php endif; ?>
                    <a href="<?= url('repositorio/descargar/' . $a['id']) ?>" title="Descargar"
                       class="inline-block px-2 py-1 text-[#005880] hover:text-[#00394f] border border-[#d5e6ee] rounded-md text-xs">⬇</a>
                    <?php if ($rol === 'admin' || (int) $a['subido_por'] === Auth::id()): ?>
                    <form method="post" action="<?= url('repositorio/eliminar/' . $a['id']) ?>" class="inline" data-confirm="¿Eliminar este archivo del repositorio?">
                        <input type="hidden" name="_csrf" value="<?= e(Request::csrfToken()) ?>">
                        <button type="submit" title="Eliminar" class="px-2 py-1 text-red-600 hover:text-red-800 border border-red-100 rounded-md text-xs">🗑</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>