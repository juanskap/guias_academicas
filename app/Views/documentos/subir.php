<?php
/** @var array $proyecto */
/** @var array $etapa */
/** @var array|null $docActual */
use App\Core\Request;
$nuevaVersion = $docActual ? (int) $docActual['version'] + 1 : 1;
$maxMbConfigurado = (new \App\Models\Configuracion())->getInt('max_upload_mb', (int) (MAX_FILE_SIZE / 1024 / 1024));
$maxMbConfigurado = $maxMbConfigurado < 1 ? (int) (MAX_FILE_SIZE / 1024 / 1024) : $maxMbConfigurado;
?>

<div class="max-w-xl mx-auto">
    <a href="<?= url('proyectos/ver/' . $proyecto['id']) ?>" class="text-sm text-gray-500 hover:text-gray-800">← Volver al proyecto</a>

    <div class="bg-white rounded-xl shadow p-6 mt-3">
        <h1 class="text-2xl font-bold text-gray-900 mb-1">Subir documento</h1>
        <p class="text-gray-500 mb-5"><?= e($proyecto['codigo']) ?> · Etapa: <span class="font-semibold"><?= e($etapa['nombre']) ?></span></p>

        <?php if ($docActual): ?>
        <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-800 px-4 py-3 text-sm mb-5">
            Ya existe una versión de trabajo (v<?= (int) $docActual['version'] ?>). Si subes otra, reemplazará a la actual.
        </div>
        <?php endif; ?>

        <form method="post" action="<?= url('documentos/subir') ?>" enctype="multipart/form-data">
            <input type="hidden" name="_csrf" value="<?= e(Request::csrfToken()) ?>">
            <input type="hidden" name="proyecto_id" value="<?= (int) $proyecto['id'] ?>">
            <input type="hidden" name="etapa_id" value="<?= (int) $etapa['id'] ?>">

            <label class="block text-sm text-gray-600 mb-1">Archivo (<?= $docActual ? 'nueva versión v' . $nuevaVersion : 'v1' ?>)</label>
            <input type="file" name="documento" accept=".pdf,.doc,.docx,.txt,.odt" required class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2">
            <p class="text-xs text-gray-400 mt-1 mb-4">Permitidos: <?= e(implode(', ', ALLOWED_EXTENSIONS)) ?> · máximo <?= $maxMbConfigurado ?> MB</p>

            <button type="submit" class="w-full px-4 py-2 bg-[#005880] hover:bg-[#004764] text-white text-sm font-semibold rounded-lg transition">Subir documento</button>
        </form>
    </div>
</div>
