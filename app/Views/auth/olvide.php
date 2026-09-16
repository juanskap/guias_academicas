<?php
use App\Core\Request;
?>

<div class="w-full max-w-md">
    <div class="bg-white rounded-xl shadow p-8">
        <h1 class="text-2xl font-bold text-gray-900 text-center">Recuperar contraseña</h1>
        <p class="text-sm text-gray-500 text-center mt-1 mb-6">Ingresa tu correo registrado y te enviaremos un enlace para restablecerla.</p>

        <?php if ($msgDemo = flash('demo')): ?>
            <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-800 px-4 py-3 text-sm mb-4 break-all"><?= e($msgDemo) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= url('auth/olvide') ?>" class="space-y-4">
            <input type="hidden" name="_csrf" value="<?= e(Request::csrfToken()) ?>">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico</label>
                <input type="email" name="email" required value="<?= e(old('email')) ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6f9e] outline-none">
            </div>
            <button type="submit" class="w-full bg-[#005880] hover:bg-[#004764] text-white font-semibold px-5 py-2 rounded-lg transition">Enviar enlace</button>
        </form>

        <p class="text-sm text-center mt-5">
            <a href="<?= url('auth/login') ?>" class="text-[#005880] hover:underline">← Volver al inicio de sesión</a>
        </p>
    </div>
</div>
