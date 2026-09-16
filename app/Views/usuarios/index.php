<?php
/** @var array $usuarios */
use App\Core\Request;
?>

<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Usuarios</h1>
        <p class="text-gray-500 text-sm">Administración de cuentas de estudiantes y docentes</p>
    </div>
    <div class="flex gap-2">
        <a href="<?= url('usuarios/nuevo?tipo=estudiante') ?>" class="bg-[#005880] hover:bg-[#004764] text-white text-sm font-semibold px-4 py-2 rounded-lg transition">+ Estudiante</a>
        <a href="<?= url('usuarios/nuevo?tipo=docente') ?>" class="bg-[#0B803A] hover:bg-[#0a6b31] text-white text-sm font-semibold px-4 py-2 rounded-lg transition">+ Docente</a>
    </div>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">Usuario</th>
                    <th class="px-4 py-3 text-left">Correo</th>
                    <th class="px-4 py-3 text-left">Rol</th>
                    <th class="px-4 py-3 text-left">Teléfono</th>
                    <th class="px-4 py-3 text-left">Estado</th>
                    <th class="px-4 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($usuarios as $u): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <div class="font-medium"><?= e($u['nombres'] . ' ' . $u['apellidos']) ?></div>
                        <div class="text-xs text-gray-400">Creado: <?= e(format_date($u['creado_en'])) ?></div>
                    </td>
                    <td class="px-4 py-3"><?= e($u['email']) ?></td>
                    <td class="px-4 py-3">
                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold bg-[#dceef7] text-[#004764] uppercase"><?= e($u['rol']) ?></span>
                    </td>
                    <td class="px-4 py-3"><?= e($u['telefono'] ?? '—') ?></td>
                    <td class="px-4 py-3">
                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold <?= e(estado_badge($u['estado'])) ?>"><?= e(ucfirst($u['estado'])) ?></span>
                    </td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <a href="<?= url('usuarios/editar/' . $u['id']) ?>" class="text-[#005880] hover:text-[#00394f] text-sm font-medium mr-3">Editar</a>
                        <form method="post" action="<?= url('usuarios/estado/' . $u['id']) ?>" class="inline" data-confirm="¿Cambiar el estado de este usuario?">
                            <input type="hidden" name="_csrf" value="<?= e(Request::csrfToken()) ?>">
                            <button type="submit" class="text-sm font-medium <?= $u['estado'] === 'activo' ? 'text-red-600 hover:text-red-800' : 'text-green-600 hover:text-green-800' ?>">
                                <?= $u['estado'] === 'activo' ? 'Desactivar' : 'Activar' ?>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($usuarios)): ?>
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No hay usuarios registrados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
