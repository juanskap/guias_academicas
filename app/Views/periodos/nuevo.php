<?php
use App\Core\Request;
?>

<div class="max-w-2xl">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Nuevo periodo académico</h1>
            <p class="text-gray-500 text-sm">Define el periodo para agrupar los proyectos</p>
        </div>
        <a href="<?= url('periodos') ?>" class="text-[#005880] text-sm font-medium hover:underline">← Volver</a>
    </div>

    <form method="post" action="<?= url('periodos/crear') ?>" class="bg-white rounded-xl shadow p-6 space-y-4">
        <input type="hidden" name="_csrf" value="<?= e(Request::csrfToken()) ?>">

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del periodo *</label>
            <input type="text" name="nombre" required value="<?= e(old('nombre')) ?>"
                   placeholder="Ej. 2026-1"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6f9e] outline-none">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de inicio *</label>
                <input type="date" name="fecha_inicio" required value="<?= e(old('fecha_inicio')) ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6f9e] outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de fin *</label>
                <input type="date" name="fecha_fin" required value="<?= e(old('fecha_fin')) ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6f9e] outline-none">
            </div>
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" name="activo" value="1" id="activo"
                   class="w-4 h-4 text-[#005880]">
            <label for="activo" class="text-sm text-gray-700">Marcar como periodo actual (desactiva los demás)</label>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="bg-[#005880] hover:bg-[#004764] text-white font-semibold px-5 py-2 rounded-lg transition">Guardar periodo</button>
            <a href="<?= url('periodos') ?>" class="px-5 py-2 text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg">Cancelar</a>
        </div>
    </form>
</div>