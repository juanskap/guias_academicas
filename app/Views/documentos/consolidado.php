<?php
/** @var array $proyecto */
/** @var bool $completo */
/** @var array $archivos */
/** @var string|null $fechaGeneracion */
use App\Core\Auth;
use App\Core\Request;

$rol = Auth::role();
$puedeGenerar = in_array($rol, ['admin', 'docente'], true);
$hayAlguno = $archivos['perfil']['pdf'] || $archivos['perfil']['docx']
    || $archivos['proyecto']['pdf'] || $archivos['proyecto']['docx'];
?>

<div class="max-w-4xl">
    <a href="<?= url('proyectos/ver/' . $proyecto['id']) ?>" class="text-sm text-gray-500 hover:text-gray-800">← Volver al proyecto</a>

    <div class="flex flex-wrap items-center justify-between gap-3 mt-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Documento unificado</h1>
            <p class="text-gray-500 text-sm"><?= e($proyecto['codigo']) ?> · <?= e($proyecto['nombre']) ?></p>
        </div>
        <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold <?= $proyecto['porcentaje_avance'] >= 100 ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' ?>">
            Avance <?= (float) $proyecto['porcentaje_avance'] ?>%
        </span>
    </div>

    <?php if (!$completo): ?>
    <div class="bg-amber-50 border-l-4 border-amber-500 text-amber-800 px-4 py-3 text-sm mb-6">
        El documento unificado se genera automáticamente cuando el proyecto alcanza el <strong>100%</strong>
        (todas las etapas aprobadas). Actualmente está en <?= (float) $proyecto['porcentaje_avance'] ?>%.
    </div>
    <?php endif; ?>

    <?php if ($fechaGeneracion): ?>
    <p class="text-xs text-gray-400 mb-4">Última generación: <?= e($fechaGeneracion) ?></p>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <?php
        $bloques = [
            'perfil' => ['titulo' => 'Documento 1 · Perfil', 'desc' => 'Solamente la etapa del Perfil aprobada.'],
            'proyecto' => ['titulo' => 'Documento 2 · Proyecto', 'desc' => 'Capítulos, conclusiones, recomendaciones, bibliografía y anexos (etapas posteriores al Perfil).'],
        ];
        foreach ($bloques as $clave => $info):
            $tienePdf = $archivos[$clave]['pdf'];
            $tieneDocx = $archivos[$clave]['docx'];
        ?>
        <div class="bg-white rounded-xl shadow p-5 flex flex-col">
            <h2 class="font-semibold text-gray-900 mb-1"><?= e($info['titulo']) ?></h2>
            <p class="text-xs text-gray-500 mb-4"><?= e($info['desc']) ?></p>

            <?php if ($tienePdf || $tieneDocx): ?>
            <div class="flex flex-wrap gap-2">
                <?php if ($tienePdf): ?>
                <a href="<?= url('consolidado/descargar/' . $proyecto['id'] . '/' . $clave . '/pdf') ?>" class="text-xs px-3 py-1.5 bg-[#005880] hover:bg-[#004764] text-white rounded-lg">⬇ PDF</a>
                <?php endif; ?>
                <?php if ($tieneDocx): ?>
                <a href="<?= url('consolidado/descargar/' . $proyecto['id'] . '/' . $clave . '/docx') ?>" class="text-xs px-3 py-1.5 bg-[#0B803A] hover:bg-[#0a6b31] text-white rounded-lg">⬇ Word</a>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <p class="text-sm text-gray-400">Aún no generado.</p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($hayAlguno && $archivos['proyecto']['pdf']): ?>
    <div class="bg-white rounded-xl shadow p-5 mt-6">
        <div class="flex items-center justify-between mb-3">
            <h2 class="font-semibold text-gray-900">Vista previa · Documento Proyecto (PDF)</h2>
            <a href="<?= url('consolidado/descargar/' . $proyecto['id'] . '/proyecto/pdf') ?>" class="text-xs px-3 py-1.5 bg-[#005880] hover:bg-[#004764] text-white rounded-lg">⬇ Descargar</a>
        </div>
        <iframe src="<?= url('consolidado/previsualizar/' . $proyecto['id'] . '/proyecto/pdf') ?>" class="w-full h-[600px] border border-gray-200 rounded-lg bg-gray-50" title="Vista previa del documento unificado"></iframe>
    </div>
    <?php endif; ?>

    <?php if ($puedeGenerar && $completo): ?>
    <form method="post" action="<?= url('consolidado/generar/' . $proyecto['id']) ?>" class="mt-6">
        <input type="hidden" name="_csrf" value="<?= e(Request::csrfToken()) ?>">
        <button type="submit" class="px-5 py-2 bg-slate-700 hover:bg-slate-800 text-white text-sm font-semibold rounded-lg transition">
            ↻ Regenerar documento unificado
        </button>
    </form>
    <?php endif; ?>
</div>