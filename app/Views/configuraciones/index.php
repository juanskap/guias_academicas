<?php
use App\Core\Request;
?>
<?php
$limitePhpMb = (int) ($limitePhpMb ?? 10);
$maxMb = (int) ($maxMb ?? 10);
?>

<div class="max-w-2xl">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Configuración del sistema</h1>
            <p class="text-gray-500 text-sm">Ajustes generales administrados desde el panel</p>
        </div>
    </div>

    <form method="post" action="<?= url('configuracion/guardar') ?>" class="bg-white rounded-xl shadow p-6 space-y-5">
        <input type="hidden" name="_csrf" value="<?= e(Request::csrfToken()) ?>">

        <div class="border-b border-gray-100 pb-4">
            <h2 class="font-semibold text-gray-900 mb-1">Documentos</h2>
            <p class="text-xs text-gray-500">Aplica a la subida de documentos de las etapas del proyecto.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tamaño máximo de subida *</label>
            <div class="flex items-center gap-2">
                <input type="number" name="max_upload_mb" min="1" max="<?= $limitePhpMb ?>"
                       value="<?= $maxMb ?>" required
                       class="w-32 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6f9e] outline-none">
                <span class="text-sm text-gray-600">MB</span>
            </div>
            <div class="mt-2 text-xs text-gray-500">
                <span class="inline-flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-green-600"></span>
                    Límite del servidor (php.ini): <strong><?= $limitePhpMb ?> MB</strong> — no puedes superarlo.
                </span>
                <br><span>(<?= e($limitePhpDetalle ?? '') ?>)</span>
            </div>
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800">
            <strong>Consejo:</strong> si más adelante necesitas permitir archivos más grandes (por ejemplo 100 MB),
            no basta con subir este valor aquí: habrá que aumentar el límite en el <code>php.ini</code> de XAMPP
            (directivas <code>upload_max_filesize</code> y <code>post_max_size</code>),
            y entonces este ajuste podrá llegar hasta ese nuevo valor.
        </div>

        <div class="flex gap-3 pt-1">
            <button type="submit" class="bg-[#005880] hover:bg-[#004764] text-white font-semibold px-5 py-2 rounded-lg transition">Guardar cambios</button>
        </div>
    </form>
</div>