<?php
/** Parcial: una tarjeta de observación. Requiere $obs y $rol en el scope. */
use App\Core\Request;
?>
<div data-obs class="border rounded-lg p-4 <?= $obs['estado'] === 'aprobada' ? 'border-green-300 bg-green-50' : 'border-gray-200' ?>">
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

    <?php if (!empty($obs['texto_seleccionado'])): ?>
    <blockquote class="border-l-4 border-yellow-400 bg-yellow-50 px-3 py-2 mb-2 text-sm italic text-gray-700"><?= nl2br(e($obs['texto_seleccionado'])) ?></blockquote>
    <?php endif; ?>

    <p class="text-sm text-gray-800"><?= nl2br(e($obs['comentario'])) ?></p>

    <?php if (!empty($obs['respuestas'])): ?>
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
