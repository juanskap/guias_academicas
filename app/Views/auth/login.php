<div class="w-full max-w-md">
    <div class="bg-white rounded-xl shadow-2xl p-8 px-10">
        <div class="text-center mb-6">
            <img src="<?= asset('img/logo-instituto.png') ?>" alt="Logo del instituto"
                 class="w-28 h-28 mx-auto object-contain mb-2">
            <h2 class="text-2xl font-bold text-gray-900"><?= e(APP_NAME) ?></h2>
            <p class="text-sm text-gray-500 mt-1"><?= e(APP_FULL_NAME) ?></p>
        </div>

        <form method="post" action="<?= url('auth/login') ?>" class="space-y-4">
            <input type="hidden" name="_csrf" value="<?= e(\App\Core\Request::csrfToken()) ?>">

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico</label>
                <input type="email" id="email" name="email" required autofocus
                       value="<?= e(old('email')) ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6f9e] focus:border-[#0b6f9e] outline-none"
                       placeholder="correo@institucion.edu">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
                <input type="password" id="password" name="password" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6f9e] focus:border-[#0b6f9e] outline-none"
                       placeholder="••••••••">
            </div>

            <button type="submit"
                    class="w-full bg-[#005880] hover:bg-[#004764] text-white font-semibold py-2.5 rounded-lg transition">
                Iniciar sesión
            </button>
        </form>

        <p class="text-sm text-center mt-4">
            <a href="<?= url('auth/olvide') ?>" class="text-[#005880] hover:underline">¿Olvidaste tu contraseña?</a>
        </p>

        <p class="text-xs text-center text-gray-400 mt-6">
            Usuario por defecto: <code>admin@sigep.edu.ec</code> / <code>Admin123</code>
        </p>
    </div>
</div>
