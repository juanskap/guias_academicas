<?php
/** @var array $proyecto */
/** @var array $docentes */
use App\Core\Request;
?>

<div class="max-w-2xl">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Asignar tutor</h1>
            <p class="text-gray-500 text-sm"><?= e($proyecto['codigo']) ?> — <?= e($proyecto['nombre']) ?></p>
        </div>
        <a href="<?= url('proyectos/ver/' . $proyecto['id']) ?>" class="text-[#005880] text-sm font-medium hover:underline">← Volver</a>
    </div>

    <form method="post" action="<?= url('proyectos/guardar-tutor/' . $proyecto['id']) ?>" class="bg-white rounded-xl shadow p-6 space-y-4">
        <input type="hidden" name="_csrf" value="<?= e(Request::csrfToken()) ?>">

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tutor actual</label>
            <p class="text-sm text-gray-600"><?= e($proyecto['tutor_nombre'] ?? 'Sin tutor asignado') ?></p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Seleccionar docente *</label>
            <select name="docente_id" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6f9e] outline-none">
                <option value="">Selecciona un docente...</option>
                <?php foreach ($docentes as $d): ?>
                <option value="<?= (int) $d['id'] ?>" <?= $proyecto['docente_record_id'] == $d['id'] ? 'selected' : '' ?>>
                    <?= e($d['nombres'] . ' ' . $d['apellidos']) ?><?= $d['titulo'] ? ' — ' . e($d['titulo']) : '' ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="bg-[#0B803A] hover:bg-[#0a6b31] text-white font-semibold px-5 py-2 rounded-lg transition">Asignar tutor</button>
            <a href="<?= url('proyectos/ver/' . $proyecto['id']) ?>" class="px-5 py-2 text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg">Cancelar</a>
        </div>
    </form>
</div>
