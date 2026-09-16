<?php
/** @var array $documento */
/** @var array $proyecto */
/** @var array|null $etapa */
use App\Core\Auth;
use App\Core\Request;
$rol = Auth::role();
$esTrabajo = $documento['tipo'] === 'trabajo';
?>

<div class="mb-6">
    <a href="<?= url('proyectos/ver/' . $proyecto['id']) ?>" class="text-sm text-gray-500 hover:text-gray-800">← Volver al proyecto</a>
    <div class="flex flex-wrap items-center justify-between gap-3 mt-2">
        <div>
            <h1 class="text-2xl font-bold text-gray-900"><?= e($documento['nombre_original']) ?></h1>
            <p class="text-gray-500"><?= e($proyecto['nombre']) ?> · Etapa: <?= e($etapa['nombre'] ?? '—') ?></p>
        </div>
        <div class="flex gap-2">
            <a href="<?= url('documentos/descargar/' . $documento['id']) ?>" class="px-4 py-2 bg-[#005880] hover:bg-[#004764] text-white text-sm font-semibold rounded-lg transition">⬇ Descargar</a>
            <?php if ($esTrabajo && $rol === 'estudiante'): ?>
            <a href="#subir" class="px-4 py-2 bg-[#0B803A] hover:bg-[#0a6b31] text-white text-sm font-semibold rounded-lg transition">⬆ Nueva versión</a>
            <?php endif; ?>
            <?php if ($esTrabajo && in_array($rol, ['admin', 'docente'], true)): ?>
            <form method="post" action="<?= url('documentos/aprobar/' . $documento['id']) ?>">
                <input type="hidden" name="_csrf" value="<?= e(Request::csrfToken()) ?>">
                <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg transition">✔ Aprobar etapa</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow p-4">
        <p class="text-xs text-gray-500 uppercase">Tipo</p>
        <p class="font-semibold mt-1"><?= $esTrabajo ? 'De trabajo' : 'Final' ?></p>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <p class="text-xs text-gray-500 uppercase">Versión</p>
        <p class="font-semibold mt-1">v<?= (int) $documento['version'] ?></p>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <p class="text-xs text-gray-500 uppercase">Estado</p>
        <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-xs font-semibold <?= e(estado_badge($documento['estado'])) ?>"><?= e(ucwords(str_replace('_', ' ', $documento['estado']))) ?></span>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <p class="text-xs text-gray-500 uppercase">Subido por</p>
        <p class="font-semibold mt-1"><?= e($documento['nombres']) ?> <?= e($documento['apellidos']) ?></p>
    </div>
</div>

<!-- Subir nueva versión (estudiante) -->
<?php if ($esTrabajo && $rol === 'estudiante'): ?>
<div id="subir" class="bg-white rounded-xl shadow p-5 mb-6">
    <h2 class="font-semibold text-gray-900 mb-3">Subir nueva versión</h2>
    <form method="post" action="<?= url('documentos/subir') ?>" enctype="multipart/form-data" class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
        <input type="hidden" name="_csrf" value="<?= e(Request::csrfToken()) ?>">
        <input type="hidden" name="proyecto_id" value="<?= (int) $documento['proyecto_id'] ?>">
        <input type="hidden" name="etapa_id" value="<?= (int) $documento['etapa_id'] ?>">
        <input type="file" name="documento" accept=".pdf,.doc,.docx,.txt,.odt" required class="text-sm">
        <button type="submit" class="px-4 py-2 bg-[#0B803A] hover:bg-[#0a6b31] text-white text-sm font-semibold rounded-lg transition">Subir v<?= (int) $documento['version'] + 1 ?></button>
    </form>
    <p class="text-xs text-gray-400 mt-2">Permitidos: <?= e(implode(', ', ALLOWED_EXTENSIONS)) ?> · máx. 10 MB · se conserva solo la versión actual.</p>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Vista previa del documento -->
    <div class="lg:col-span-3 bg-white rounded-xl shadow p-5">
        <div class="flex items-center justify-between mb-3">
            <h2 class="font-semibold text-gray-900">Vista previa</h2>
            <a href="<?= url('documentos/descargar/' . $documento['id']) ?>" class="text-xs px-3 py-1.5 bg-[#005880] hover:bg-[#004764] text-white rounded-lg">⬇ Descargar</a>
        </div>
        <?php
        $ext = strtolower(pathinfo($documento['ruta'], PATHINFO_EXTENSION));
        if (in_array($ext, ['pdf', 'txt', 'docx', 'odt', 'doc', 'rtf'], true)):
        ?>
        <iframe src="<?= url('documentos/previsualizar/' . $documento['id']) ?>" class="w-full h-[600px] border border-gray-200 rounded-lg bg-gray-50" title="Vista previa del documento"></iframe>
        <?php else: ?>
        <div class="bg-gray-50 border border-dashed border-gray-300 rounded-lg p-8 text-center text-sm text-gray-500">
            No se puede previsualizar este formato (<?= e(strtoupper($ext)) ?>).<br>
            Descárgalo para revisarlo y haz tus observaciones abajo.
        </div>
        <?php endif; ?>
    </div>

    <?php if (in_array($ext, ['docx', 'odt', 'doc'], true)): ?>
    <p class="text-xs text-gray-500 -mt-3 mb-6"><strong>Vista previa:</strong> el formato original se convierte automáticamente. Para mejor resultado, también puedes subir el documento en PDF (Archivo → Guardar como PDF).</p>
    <?php endif; ?>

    <!-- Hilo de observaciones -->
    <div class="lg:col-span-2 bg-white rounded-xl shadow p-5">
        <h2 class="font-semibold text-gray-900 mb-4">Observaciones (<?= count($documento['observaciones']) ?>)</h2>

        <?php if (empty($documento['observaciones'])): ?>
            <p class="text-sm text-gray-500">Aún no hay observaciones para este documento.</p>
        <?php endif; ?>

        <div class="space-y-4">
            <?php foreach ($documento['observaciones'] as $obs): ?>
            <div class="border rounded-lg p-4 <?= $obs['estado'] === 'aprobada' ? 'border-green-300 bg-green-50' : 'border-gray-200' ?>">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold uppercase bg-gray-100 px-2 py-0.5 rounded-full text-gray-600"><?= e($obs['rol']) ?></span>
                        <span class="text-sm font-medium"><?= e($obs['nombres']) ?> <?= e($obs['apellidos']) ?></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-400"><?= e(format_date($obs['creado_en'])) ?></span>
                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold <?= e(estado_badge($obs['estado'])) ?>"><?= e(ucwords(str_replace('_', ' ', $obs['estado']))) ?></span>
                    </div>
                </div>

                <?php if ($obs['texto_seleccionado']): ?>
                <blockquote class="border-l-4 border-gray-300 bg-gray-50 px-3 py-2 mb-2 text-sm italic text-gray-600"><?= nl2br(e($obs['texto_seleccionado'])) ?></blockquote>
                <?php endif; ?>

                <p class="text-sm text-gray-800"><?= nl2br(e($obs['comentario'])) ?></p>

                <?php if ($obs['respuestas']): ?>
                <div class="mt-3 ml-4 space-y-2 border-l-2 border-gray-100 pl-4">
                    <?php foreach ($obs['respuestas'] as $r): ?>
                    <div class="text-sm">
                        <span class="text-xs font-semibold uppercase bg-[#e8f4fa] px-2 py-0.5 rounded-full text-[#005880] mr-2"><?= e($r['rol']) ?></span>
                        <span class="font-medium"><?= e($r['nombres']) ?> <?= e($r['apellidos']) ?></span>
                        <span class="text-xs text-gray-400 ml-1"><?= e(format_date($r['creado_en'])) ?></span>
                        <p class="text-gray-700 mt-1"><?= nl2br(e($r['mensaje'])) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if ($rol === 'admin' || $rol === 'docente'): ?>
                    <?php if ($obs['estado'] !== 'aprobada'): ?>
                    <form method="post" action="<?= url('documentos/aprobar-observacion/' . $obs['id']) ?>" class="mt-3 inline-block">
                        <input type="hidden" name="_csrf" value="<?= e(Request::csrfToken()) ?>">
                        <button type="submit" class="text-xs px-3 py-1 bg-green-600 hover:bg-green-700 text-white rounded-lg">Marcar aprobada</button>
                    </form>
                    <?php endif; ?>
                <?php endif; ?>

                <form method="post" action="<?= url('documentos/responder') ?>" class="mt-3">
                    <input type="hidden" name="_csrf" value="<?= e(Request::csrfToken()) ?>">
                    <input type="hidden" name="observacion_id" value="<?= (int) $obs['id'] ?>">
                    <textarea name="mensaje" rows="2" placeholder="Escribe una respuesta..." required class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#0b6f9e]"></textarea>
                    <button type="submit" class="mt-1 text-xs px-3 py-1.5 bg-[#005880] hover:bg-[#004764] text-white rounded-lg">Responder</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Nueva observación (tutor/admin) -->
    <div class="space-y-6">
        <?php if ($esTrabajo && in_array($rol, ['admin', 'docente'], true)): ?>
        <div class="bg-white rounded-xl shadow p-5">
            <h2 class="font-semibold text-gray-900 mb-3">Nueva observación</h2>
            <form method="post" action="<?= url('documentos/observar') ?>">
                <input type="hidden" name="_csrf" value="<?= e(Request::csrfToken()) ?>">
                <input type="hidden" name="documento_id" value="<?= (int) $documento['id'] ?>">
                <label class="block text-sm text-gray-600 mb-1">Texto del documento (referencia)</label>
                <textarea name="texto_seleccionado" rows="3" placeholder="Copia aquí el fragmento del documento que quieres señalar (opcional)" class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#0b6f9e]"></textarea>
                <label class="block text-sm text-gray-600 mb-1 mt-3">Comentario</label>
                <textarea name="comentario" rows="3" placeholder="Describe la observación..." required class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#0b6f9e]"></textarea>
                <button type="submit" class="mt-3 w-full px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold rounded-lg transition">Registrar observación</button>
            </form>
        </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow p-5">
            <h2 class="font-semibold text-gray-900 mb-3">Detalles</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Proyecto</dt><dd class="font-medium"><?= e($proyecto['codigo']) ?></dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Etapa</dt><dd><?= e($etapa['nombre'] ?? '—') ?></dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Subido</dt><dd><?= e(format_date($documento['creado_en'])) ?></dd></div>
            </dl>
        </div>
    </div>
</div>
