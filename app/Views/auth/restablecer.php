<?php
/** @var string $token */
use App\Core\Request;
?>

<div class="w-full max-w-md">
    <div class="bg-white rounded-xl shadow p-8">
        <h1 class="text-2xl font-bold text-gray-900 text-center">Nueva contraseña</h1>
        <p class="text-sm text-gray-500 text-center mt-1 mb-6">Escribe tu nueva contraseña (mínimo 6 caracteres).</p>

        <form method="post" action="<?= url('auth/cambiar') ?>" class="space-y-4">
            <input type="hidden" name="_csrf" value="<?= e(Request::csrfToken()) ?>">
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nueva contraseña</label>
                <input type="password" name="password" required minlength="6"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6f9e] outline-none">
            </div>
            <button type="submit" class="w-full bg-[#005880] hover:bg-[#004764] text-white font-semibold px-5 py-2 rounded-lg transition">Guardar contraseña</button>
        </form>
    </div>
</div>
