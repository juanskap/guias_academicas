<?php
/** @var string $title */
use App\Core\Auth;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? APP_NAME) ?> | <?= e(APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<?php $autenticado = Auth::check(); ?>
<body class="<?= $autenticado ? 'bg-[#eef4f7]' : 'bg-gradient-to-br from-[#005880] via-[#004764] to-[#0B803A]' ?> text-gray-800 min-h-screen">
<div class="flex min-h-screen">
    <?php if ($autenticado): ?>
    <?php $rol = Auth::role(); ?>
    <!-- ===== Sidebar ===== -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-gradient-to-b from-[#005880] to-[#00394f] text-slate-100 flex flex-col transform -translate-x-full lg:translate-x-0 lg:static transition-transform duration-200">
        <div class="px-5 py-5 border-b border-white/15 flex items-center gap-3">
            <img src="<?= asset('img/logo-instituto.png') ?>" alt="Logo del instituto"
                 class="w-11 h-11 object-contain">
            <div>
                <h1 class="text-xl font-bold text-white"><?= e(APP_NAME) ?></h1>
                <p class="text-xs text-slate-300 mt-1"><?= e(APP_FULL_NAME) ?></p>
            </div>
        </div>
        <nav class="flex-1 py-4 overflow-y-auto">
            <a href="<?= url('dashboard') ?>" class="flex items-center gap-3 px-5 py-3 text-sm hover:bg-white/10 transition">📊 Panel principal</a>

            <?php if ($rol === 'admin'): ?>
            <a href="<?= url('usuarios') ?>" class="flex items-center gap-3 px-5 py-3 text-sm hover:bg-white/10 transition">👥 Usuarios</a>
            <a href="<?= url('tipos-proyecto') ?>" class="flex items-center gap-3 px-5 py-3 text-sm hover:bg-white/10 transition">🗂️ Tipos de proyecto</a>
            <a href="<?= url('configuracion') ?>" class="flex items-center gap-3 px-5 py-3 text-sm hover:bg-white/10 transition">⚙️ Configuración</a>
            <?php endif; ?>

            <?php if ($rol === 'docente'): ?>
            <a href="<?= url('mis-proyectos') ?>" class="flex items-center gap-3 px-5 py-3 text-sm hover:bg-white/10 transition">📁 Mis proyectos</a>
            <?php endif; ?>

            <?php if ($rol === 'estudiante'): ?>
            <a href="<?= url('mis-proyectos') ?>" class="flex items-center gap-3 px-5 py-3 text-sm hover:bg-white/10 transition">📁 Mis proyectos</a>
            <?php endif; ?>

            <a href="<?= url('calendario') ?>" class="flex items-center gap-3 px-5 py-3 text-sm hover:bg-white/10 transition">📅 Calendario</a>
            <a href="<?= url('notificaciones') ?>" class="flex items-center gap-3 px-5 py-3 text-sm hover:bg-white/10 transition">🔔 Notificaciones</a>
        </nav>
        <div class="p-4 border-t border-white/15">
            <form method="post" action="<?= url('auth/logout') ?>">
                <input type="hidden" name="_csrf" value="<?= e(\App\Core\Request::csrfToken()) ?>">
                <button type="submit" class="w-full text-left text-sm text-red-300 hover:text-red-200 py-2 px-3 hover:bg-white/10 rounded transition">🚪 Cerrar sesión</button>
            </form>
        </div>
    </aside>

    <!-- ===== Contenido ===== -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Topbar -->
        <header class="bg-white shadow-sm px-5 py-3 flex items-center justify-between lg:justify-end gap-4">
            <button id="menu-toggle" class="lg:hidden text-2xl" aria-label="Abrir menú">☰</button>
            <div class="flex items-center gap-4">
                <?php $notifModel = new \App\Models\Notificacion(); $notifNoLeidas = $notifModel->noLeidas(Auth::id()); $notifUltimas = $notifModel->porUsuario(Auth::id(), 5); ?>
                <details class="relative" id="notif-menu">
                    <summary class="relative cursor-pointer list-none flex items-center p-2 rounded-lg hover:bg-gray-100 transition" aria-label="Notificaciones">
                        🔔
                        <?php if ($notifNoLeidas > 0): ?>
                        <span class="absolute -top-1 -right-1 min-w-5 h-5 px-1 flex items-center justify-center rounded-full bg-red-500 text-white text-[11px] font-bold"><?= (int) $notifNoLeidas ?></span>
                        <?php endif; ?>
                    </summary>
                    <div class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-lg border border-gray-200 z-50 overflow-hidden">
                        <div class="px-4 py-3 border-b flex items-center justify-between">
                            <span class="font-semibold text-sm">Notificaciones</span>
                            <a href="<?= url('notificaciones') ?>" class="text-xs text-[#005880] hover:underline">Ver todas</a>
                        </div>
                        <?php if (!$notifUltimas): ?>
                        <p class="px-4 py-6 text-sm text-gray-500 text-center">Sin notificaciones.</p>
                        <?php else: ?>
                        <ol class="max-h-72 overflow-y-auto">
                            <?php foreach ($notifUltimas as $n): ?>
                            <li class="px-4 py-3 border-b last:border-0 <?= $n['leida'] ? 'bg-white' : 'bg-[#e8f4fa]' ?>">
                                <p class="text-sm font-medium"><?= e($n['titulo']) ?></p>
                                <p class="text-xs text-gray-500 mt-0.5 line-clamp-2"><?= e($n['mensaje']) ?></p>
                                <p class="text-[11px] text-gray-400 mt-1"><?= e(format_date($n['creado_en'])) ?></p>
                            </li>
                            <?php endforeach; ?>
                        </ol>
                        <?php endif; ?>
                    </div>
                </details>
                <div class="text-sm">
                    <span class="text-gray-500">Sesión:</span>
                    <span class="font-medium"><?= e($_SESSION['user_nombre'] ?? 'Usuario') ?></span>
                    <span class="ml-2 inline-block px-2 py-0.5 rounded-full text-xs font-semibold bg-[#dceef7] text-[#004764] uppercase"><?= e($rol) ?></span>
                </div>
            </div>
        </header>

        <!-- Mensajes flash -->
        <?php $msgSuccess = flash('success'); $msgError = flash('error'); ?>
        <?php if ($msgSuccess): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-800 px-4 py-3 text-sm"><?= e($msgSuccess) ?></div>
        <?php endif; ?>
        <?php if ($msgError): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-800 px-4 py-3 text-sm"><?= e($msgError) ?></div>
        <?php endif; ?>

        <main class="p-5 lg:p-8 flex-1">
            <?= $content ?>
        </main>
    </div>
    <?php else: ?>
        <div class="flex-1 flex flex-col min-w-0">
            <?php if ($msgSuccess = flash('success')): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-800 px-4 py-3 text-sm"><?= e($msgSuccess) ?></div>
            <?php endif; ?>
            <?php if ($msgError = flash('error')): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-800 px-4 py-3 text-sm"><?= e($msgError) ?></div>
            <?php endif; ?>
            <main class="flex-1 flex items-center justify-center p-4">
                <?= $content ?>
            </main>
        </div>
    <?php endif; ?>
</div>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
