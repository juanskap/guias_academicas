<?php
/** @var array $documento */
/** @var array $proyecto */
/** @var array|null $etapa */
use App\Core\Auth;
use App\Core\Request;
$rol = Auth::role();
$esTrabajo = $documento['tipo'] === 'trabajo';
$reemplaza = $esTrabajo && in_array($documento['estado'], ['enviado', 'en_revision'], true);
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
            <a href="#subir" class="px-4 py-2 bg-[#0B803A] hover:bg-[#0a6b31] text-white text-sm font-semibold rounded-lg transition"><?= $reemplaza ? '⬆ Corregir documento' : '⬆ Nueva versión' ?></a>
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
        <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-xs font-semibold <?= e(estado_badge($documento['estado'])) ?>"><?= e(documento_estado_label($documento['estado'])) ?></span>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <p class="text-xs text-gray-500 uppercase">Subido por</p>
        <p class="font-semibold mt-1"><?= e($documento['nombres']) ?> <?= e($documento['apellidos']) ?></p>
    </div>
</div>

<!-- Subir nueva versión (estudiante) -->
<?php if ($esTrabajo && $rol === 'estudiante'): ?>
<div id="subir" class="bg-white rounded-xl shadow p-5 mb-6">
    <h2 class="font-semibold text-gray-900 mb-3"><?= $reemplaza ? 'Corregir / reemplazar documento' : 'Subir nueva versión' ?></h2>
    <form method="post" action="<?= url('documentos/subir') ?>" enctype="multipart/form-data" class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
        <input type="hidden" name="_csrf" value="<?= e(Request::csrfToken()) ?>">
        <input type="hidden" name="proyecto_id" value="<?= (int) $documento['proyecto_id'] ?>">
        <input type="hidden" name="etapa_id" value="<?= (int) $documento['etapa_id'] ?>">
        <input type="file" name="documento" accept=".pdf,.doc,.docx,.txt,.odt" required class="text-sm">
        <button type="submit" class="px-4 py-2 bg-[#0B803A] hover:bg-[#0a6b31] text-white text-sm font-semibold rounded-lg transition"><?= $reemplaza ? 'Reemplazar v' . (int) $documento['version'] : 'Subir v' . ((int) $documento['version'] + 1) ?></button>
    </form>
    <p class="text-xs text-gray-400 mt-2">
        Permitidos: <?= e(implode(', ', ALLOWED_EXTENSIONS)) ?> · máx. <?= (int) ((new \App\Models\Configuracion())->getInt('max_upload_mb', (int) (MAX_FILE_SIZE / 1024 / 1024))) ?: (int) (MAX_FILE_SIZE / 1024 / 1024) ?> MB.
        <?= $reemplaza
            ? 'El tutor aún no lo revisa: al subir se reemplaza esta versión.'
            : 'El tutor ya revisó este documento: se conservará como v' . (int) $documento['version'] . ' y se creará la v' . ((int) $documento['version'] + 1) . '.' ?>
    </p>
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
        $conVisor = in_array($ext, ['pdf', 'docx', 'odt', 'doc', 'rtf'], true);
        if (in_array($ext, ['pdf', 'txt', 'docx', 'odt', 'doc', 'rtf'], true)):
        ?>
        <iframe id="previewFrame" src="<?= url('documentos/' . ($conVisor ? 'visor' : 'previsualizar') . '/' . $documento['id']) ?>" class="w-full h-[600px] border border-gray-200 rounded-lg bg-gray-50" title="Vista previa del documento"></iframe>
        <?php if (in_array($rol, ['admin', 'docente'], true) && $esTrabajo): ?>
        <p class="text-xs text-gray-500 mt-2">🖱️ <strong>Marca el texto</strong> que quieres señalar: al seleccionarlo, aparecerá automáticamente en “Texto del documento” de la nueva observación.</p>
        <?php endif; ?>
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
        <h2 class="font-semibold text-gray-900 mb-4">Observaciones (<span id="obsCount"><?= count($documento['observaciones']) ?></span>)</h2>

        <p id="obsVacio" class="text-sm text-gray-500" <?= empty($documento['observaciones']) ? '' : 'hidden' ?>>Aún no hay observaciones para este documento.</p>

        <div id="obsLista" class="space-y-4">
            <?php foreach ($documento['observaciones'] as $obs): ?>
            <?php require VIEW_PATH . '/documentos/_observacion.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Nueva observación (tutor/admin) -->
    <div class="space-y-6">
        <?php if ($esTrabajo && in_array($rol, ['admin', 'docente'], true)): ?>
        <div class="bg-white rounded-xl shadow p-5">
            <h2 class="font-semibold text-gray-900 mb-1">Observación general</h2>
            <p class="text-xs text-gray-500 mb-3">Para señalar un texto concreto, márcalo en la vista previa: se abrirá un cuadro para escribir el comentario sin recargar la página.</p>
            <form id="obsFormGeneral">
                <textarea id="obsComentarioGeneral" rows="3" placeholder="Describe la observación..." required class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#0b6f9e]"></textarea>
                <p id="obsErrorGeneral" class="text-xs text-red-600 mt-1" hidden></p>
                <button type="submit" class="mt-3 w-full px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold rounded-lg transition">Registrar observación</button>
            </form>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var CSRF = "<?= e(Request::csrfToken()) ?>";
                var DOC = <?= (int) $documento['id'] ?>;
                var URL_OBSERVAR = "<?= url('documentos/observar') ?>";
                var frame = document.getElementById('previewFrame');
                var lista = document.getElementById('obsLista');
                var vacio = document.getElementById('obsVacio');
                var conteo = document.getElementById('obsCount');
                var modal = document.getElementById('obsModal');
                var mTexto = document.getElementById('obsModalTexto');
                var mComentario = document.getElementById('obsModalComentario');
                var mError = document.getElementById('obsModalError');
                var mGuardar = document.getElementById('obsModalGuardar');
                var mCancelar = document.getElementById('obsModalCancelar');
                var mCerrar = document.getElementById('obsModalCerrar');
                var fGeneral = document.getElementById('obsFormGeneral');
                var cGeneral = document.getElementById('obsComentarioGeneral');
                var eGeneral = document.getElementById('obsErrorGeneral');
                var seleccion = null;

                function registrar(datos) {
                    var fd = new FormData();
                    Object.keys(datos).forEach(function (k) { fd.append(k, datos[k] == null ? '' : datos[k]); });
                    return fetch(URL_OBSERVAR, {
                        method: 'POST', body: fd, credentials: 'same-origin',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    }).then(function (r) {
                        return r.json().catch(function () { return {}; }).then(function (j) {
                            return { ok: r.ok && j.ok === true, error: j.error || 'No se pudo registrar la observación.', html: j.html, total: j.total };
                        });
                    });
                }

                function exito(res) {
                    if (vacio) { vacio.hidden = true; }
                    if (lista && res.html) { lista.insertAdjacentHTML('beforeend', res.html); }
                    var n = lista ? lista.querySelectorAll('[data-obs]').length : 0;
                    if (conteo) { conteo.textContent = n || (parseInt(conteo.textContent, 10) + 1); }
                    if (frame && frame.contentWindow) { frame.contentWindow.postMessage({ type: 'sigep-repaint' }, '*'); }
                    mostrarToast();
                }

                var toast = document.getElementById('obsToast');
                var toastTimer;
                function mostrarToast() {
                    if (!toast) { return; }
                    toast.classList.add('visible');
                    clearTimeout(toastTimer);
                    toastTimer = setTimeout(function () { toast.classList.remove('visible'); }, 2200);
                }

                function abrirModal(texto, pages) {
                    texto = (texto || '').replace(/\s+/g, ' ').trim();
                    if (texto.length < 2) { return; }
                    if (seleccion && seleccion.texto === texto && modal.style.display !== 'none') { return; }
                    seleccion = { texto: texto, pages: pages || [] };
                    mTexto.textContent = texto;
                    mComentario.value = '';
                    mError.hidden = true;
                    modal.style.display = 'flex';
                    mComentario.focus();
                }

                function cerrarModal() {
                    modal.style.display = 'none';
                    mComentario.value = '';
                    mError.hidden = true;
                    seleccion = null;
                }

                mGuardar.addEventListener('click', function () {
                    var c = mComentario.value.trim();
                    if (c === '') { mError.textContent = 'Escribe un comentario para la observación.'; mError.hidden = false; return; }
                    var datos = { _csrf: CSRF, documento_id: DOC, comentario: c };
                    if (seleccion) {
                        datos.texto_seleccionado = seleccion.texto;
                        if (seleccion.pages.length) { datos.anotacion = JSON.stringify({ pages: seleccion.pages, color: 'amarillo' }); }
                    }
                    mGuardar.disabled = true;
                    registrar(datos).then(function (res) {
                        if (res.ok) { exito(res); cerrarModal(); }
                        else { mError.textContent = res.error; mError.hidden = false; }
                    }).catch(function () {
                        mError.textContent = 'Error de conexión. Inténtalo de nuevo.'; mError.hidden = false;
                    }).then(function () { mGuardar.disabled = false; });
                });

                mCancelar.addEventListener('click', cerrarModal);
                mCerrar.addEventListener('click', cerrarModal);
                modal.addEventListener('click', function (ev) { if (ev.target === modal) { cerrarModal(); } });
                document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape' && modal.style.display !== 'none') { cerrarModal(); } });

                fGeneral.addEventListener('submit', function (ev) {
                    ev.preventDefault();
                    var c = cGeneral.value.trim();
                    if (c === '') { return; }
                    eGeneral.hidden = true;
                    registrar({ _csrf: CSRF, documento_id: DOC, comentario: c }).then(function (res) {
                        if (res.ok) { exito(res); cGeneral.value = ''; }
                        else { eGeneral.textContent = res.error; eGeneral.hidden = false; }
                    }).catch(function () {
                        eGeneral.textContent = 'Error de conexión. Inténtalo de nuevo.'; eGeneral.hidden = false;
                    });
                });

                window.addEventListener('message', function (ev) {
                    if (ev.data && ev.data.type === 'sigep-selection') { abrirModal(ev.data.text, ev.data.pages); }
                });
            });
        </script>
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

<!-- Aviso de confirmación (no mueve la página ni bloquea clics) -->
<style>
    #obsToast { opacity: 0; transform: translate(-50%, -14px); transition: opacity .25s ease, transform .25s ease; pointer-events: none; }
    #obsToast.visible { opacity: 1; transform: translate(-50%, 0); }
</style>
<div id="obsToast" class="fixed top-4 left-1/2 z-[60] flex items-center gap-2 bg-green-600 text-white text-sm font-semibold px-4 py-2 rounded-full shadow-lg">
    <span class="inline-flex items-center justify-center w-5 h-5 bg-white/25 rounded-full text-xs">✓</span>
    <span>Observación registrada</span>
</div>

<!-- Modal para observación sobre texto marcado -->
<div id="obsModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none; background: rgba(0,0,0,.5)">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-lg p-5">
        <div class="flex items-start justify-between mb-3">
            <h3 class="font-semibold text-gray-900">Nueva observación</h3>
            <button type="button" id="obsModalCerrar" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
        </div>
        <p class="text-xs text-gray-500 mb-1">Texto marcado</p>
        <blockquote id="obsModalTexto" class="border-l-4 border-yellow-400 bg-yellow-50 px-3 py-2 mb-3 text-sm italic text-gray-700 max-h-28 overflow-auto"></blockquote>
        <label class="block text-sm text-gray-600 mb-1">Comentario</label>
        <textarea id="obsModalComentario" rows="3" placeholder="Describe la observación..." class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#0b6f9e]"></textarea>
        <p id="obsModalError" class="text-xs text-red-600 mt-1" hidden></p>
        <div class="flex justify-end gap-2 mt-4">
            <button type="button" id="obsModalCancelar" class="px-4 py-2 text-sm font-semibold text-gray-600 hover:text-gray-900">Cancelar</button>
            <button type="button" id="obsModalGuardar" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold rounded-lg disabled:opacity-60">Registrar observación</button>
        </div>
    </div>
</div>

