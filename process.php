<?php
set_time_limit(300);
session_start();
require_once __DIR__ . '/lib/OllamaClient.php';
require_once __DIR__ . '/lib/ContentGenerator.php';
require_once __DIR__ . '/lib/DocumentGenerator.php';
require_once __DIR__ . '/lib/PDFGenerator.php';

define('PROJECT_ROOT', __DIR__);
define('TEMPLATES_DIR', PROJECT_ROOT . '/templates');
define('OUTPUT_DIR', PROJECT_ROOT . '/output');

if (!is_dir(OUTPUT_DIR)) mkdir(OUTPUT_DIR, 0777, true);
if (!is_dir(TEMPLATES_DIR)) {
    mkdir(TEMPLATES_DIR, 0777, true);
    foreach (glob(PROJECT_ROOT . '/Guía de Prácticas *.docx') as $f) copy($f, TEMPLATES_DIR . '/' . basename($f));
    foreach (glob(PROJECT_ROOT . '/Anexo prácticas *.docx') as $f) copy($f, TEMPLATES_DIR . '/' . basename($f));
}
$guiaSrc = glob(PROJECT_ROOT . '/Guía de Prácticas *.docx');
$anexoSrc = glob(PROJECT_ROOT . '/Anexo prácticas *.docx');
if (empty($guiaSrc) || empty($anexoSrc)) {
    http_response_code(400);
    echo "No se encontraron las plantillas .docx en la raíz del proyecto."; exit;
}

$materia = trim($_POST['materia'] ?? '');
$unidad = trim($_POST['unidad'] ?? '');
$titulo = trim($_POST['titulo'] ?? '');
$carrera = trim($_POST['carrera'] ?? 'Tecnología Superior en Desarrollo de Software');
$nivel = trim($_POST['nivel'] ?? 'IV');
$docente = trim($_POST['docente'] ?? 'Ing. Diana Ramírez Garófalo');
$nroPractica = trim($_POST['nro_practica'] ?? '1');
$horas = trim($_POST['horas'] ?? '3');
$elaborado = trim($_POST['elaborado'] ?? 'Ing. Diana Ramírez Garófalo');
$revisado = trim($_POST['revisado'] ?? 'Lcda. Diana Alegría Camino');
$aprobado = trim($_POST['aprobado'] ?? 'Ing. Maribel Fierro Montero');
$referenciasJson = trim($_POST['referencias_json'] ?? '[]');

if (!$materia || !$unidad || !$titulo) {
    http_response_code(400);
    echo "Todos los campos son obligatorios."; exit;
}

$referencias = json_decode($referenciasJson, true) ?? [];

// ─── Configuración de salida ───
ob_implicit_flush(true);
ob_end_flush();

$nombreTema = $materia . ' - ' . $unidad . ' - ' . $titulo;
$timestamp = date('Ymd_His');
$baseName = preg_replace('/[^A-Za-z0-9_\-]/', '_', substr($titulo, 0, 40)) . '_' . $timestamp;
$outGuiaDocx = OUTPUT_DIR . '/Guia_' . $baseName . '.docx';
$outAnexoDocx = OUTPUT_DIR . '/Anexo_' . $baseName . '.docx';
$outGuiaPdf  = OUTPUT_DIR . '/Guia_' . $baseName . '.pdf';
$outAnexoPdf = OUTPUT_DIR . '/Anexo_' . $baseName . '.pdf';

$client = new OllamaClient();
$gen = new ContentGenerator($client);
$docGen = new DocumentGenerator();

// ─── Arrays compartidos para el JS ───
$guiaContent = [];
$anexoContent = [];

function sendUpdate($type, $key, $value) {
    $pct = $_SESSION['prog_pct'] ?? 0;
    $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    echo "<script>
        document.getElementById('status-text').textContent = 'Generando {$key}...';
        document.getElementById('progress-fill').style.width = '{$pct}%';
        if (parent.renderPreview) parent.renderPreview(" . json_encode($type) . ", " . json_encode($key) . ", " . json_encode($value) . ");
    </script>\n";
    flush();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Generando documentos...</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
    --primary: #2563eb; --primary-dark: #1d4ed8; --primary-light: #dbeafe;
    --gray-50: #f8fafc; --gray-100: #f1f5f9; --gray-200: #e2e8f0;
    --gray-300: #cbd5e1; --gray-400: #94a3b8; --gray-500: #64748b;
    --gray-600: #475569; --gray-700: #334155; --gray-800: #1e293b; --gray-900: #0f172a;
    --success: #10b981; --accent: #06b6d4; --warning: #f59e0b;
}
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: linear-gradient(135deg, #0f172a, #1e293b, #0f172a);
    min-height: 100vh; color: #fff;
}
.topbar {
    background: rgba(15,23,42,.8); backdrop-filter: blur(12px);
    border-bottom: 1px solid rgba(255,255,255,.06);
    padding: 14px 32px; display: flex; align-items: center;
    justify-content: space-between; position: sticky; top: 0; z-index: 100;
}
.topbar-brand { display: flex; align-items: center; gap: 12px; color: #fff; text-decoration: none; }
.topbar-brand .logo {
    width: 38px; height: 38px; background: linear-gradient(135deg, var(--primary), var(--accent));
    border-radius: 10px; display: flex; align-items: center; justify-content: center;
    font-size: 18px; box-shadow: 0 4px 12px rgba(37,99,235,.3);
}
.topbar-brand h1 { font-size: 17px; font-weight: 700; color:#fff; letter-spacing:-.3px; }
.topbar-brand span { color: var(--gray-400); font-weight: 400; }
.topbar-nav a {
    padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 500;
    text-decoration: none; color: var(--gray-400); transition: all .2s;
}
.topbar-nav a:hover { background: rgba(255,255,255,.08); color: #fff; }
.topbar-nav a.active { background: rgba(37,99,235,.15); color: #60a5fa; }

.main { max-width: 1440px; margin: 0 auto; padding: 24px 20px 40px; }

/* Cabecera del progreso */
.progress-header {
    background: rgba(255,255,255,.04); backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 16px; padding: 24px 28px; margin-bottom: 28px;
}
.progress-header h2 { font-size: 22px; font-weight: 700; letter-spacing:-.5px; margin-bottom: 4px; }
.progress-header p { font-size: 14px; color: var(--gray-400); margin-bottom: 16px; }
.progress-bar {
    width: 100%; height: 8px; background: rgba(255,255,255,.08);
    border-radius: 99px; overflow: hidden; margin-bottom: 12px;
}
#progress-fill {
    height: 100%; width: 0%; background: linear-gradient(90deg, var(--primary), var(--accent));
    border-radius: 99px; transition: width .4s ease;
}
.progress-steps {
    display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px;
}
.step {
    padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 500;
    background: rgba(255,255,255,.06); color: var(--gray-500);
    display: flex; align-items: center; gap: 4px;
}
.step.done { background: rgba(16,185,129,.12); color: var(--success); }
.step.active { background: rgba(37,99,235,.12); color: #60a5fa; }

#status-text { color: var(--accent); font-size: 14px; font-weight: 500; margin-top: 8px; }

/* Layout de previews */
.previews { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
@media (max-width: 1024px) { .previews { grid-template-columns: 1fr; } }

.doc-card {
    background: #fff; border-radius: 16px; overflow: hidden;
    box-shadow: 0 8px 32px rgba(0,0,0,.3);
}
.doc-card-header {
    background: linear-gradient(135deg, var(--gray-800), var(--gray-900));
    padding: 16px 20px;
    display: flex; align-items: center; justify-content: space-between;
}
.doc-card-header h3 { font-size: 15px; font-weight: 700; color: #fff; }
.doc-card-header .badge {
    padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600;
    background: rgba(37,99,235,.2); color: #60a5fa;
}
.doc-card-body {
    padding: 20px; max-height: 600px; overflow-y: auto;
    color: var(--gray-800); font-size: 13px; line-height: 1.6;
    background: #fff;
}
.doc-card-body::-webkit-scrollbar { width: 6px; }
.doc-card-body::-webkit-scrollbar-thumb { background: var(--gray-200); border-radius: 99px; }

.preview-section { margin-bottom: 14px; padding-bottom: 14px; border-bottom: 1px solid var(--gray-100); }
.preview-section:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
.preview-section h4 {
    font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
    color: var(--primary); margin-bottom: 3px;
}
.preview-section .placeholder {
    color: var(--gray-400); font-style: italic; font-size: 12px;
}
.preview-section .value { color: var(--gray-800); font-size: 12px; white-space: pre-wrap; }
.preview-section .value.fresh { animation: fadeSlide .3s ease; }
@keyframes fadeSlide {
    from { opacity: 0; transform: translateY(4px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Logs ocultos */
#log-container { display: none; }

/* Botones finales */
.final-actions {
    display: none; margin-top: 28px;
    background: rgba(255,255,255,.04); backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 16px; padding: 28px; text-align: center;
}
.final-actions.visible { display: block; }
.final-actions h3 { font-size: 20px; font-weight: 700; margin-bottom: 4px; }
.final-actions p { color: var(--gray-400); font-size: 14px; margin-bottom: 20px; }
.dl-buttons { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
.btn {
    padding: 12px 24px; border-radius: 10px; font-size: 14px; font-weight: 600;
    text-decoration: none; transition: all .2s; display: inline-flex; align-items: center; gap: 8px;
    border: none; cursor: pointer;
}
.btn-primary { background: linear-gradient(135deg, var(--primary), #1d4ed8); color: #fff; box-shadow: 0 4px 14px rgba(37,99,235,.3); }
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(37,99,235,.4); }
.btn-success { background: linear-gradient(135deg, var(--success), #059669); color: #fff; box-shadow: 0 4px 14px rgba(16,185,129,.3); }
.btn-success:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(16,185,129,.4); }
.btn-ghost { background: rgba(255,255,255,.08); color: var(--gray-300); }
.btn-ghost:hover { background: rgba(255,255,255,.12); color: #fff; }

.notif-banner {
    background: linear-gradient(135deg, rgba(6,182,212,.15), rgba(37,99,235,.1));
    border: 1px solid rgba(6,182,212,.2);
    border-radius: 10px; padding: 12px 16px; margin-bottom: 16px;
    font-size: 13px; font-weight: 500; color: var(--accent);
    display: flex; align-items: center; gap: 8px;
}
.edit-textarea {
    width: 100%; min-height: 60px;
    padding: 8px 10px; border: 1.5px solid var(--gray-200);
    border-radius: 8px; font-family: inherit; font-size: 12px;
    line-height: 1.5; color: var(--gray-800); background: #fffbeb;
    resize: vertical; outline: none; transition: all .15s;
}
.edit-textarea:focus {
    border-color: var(--primary); background: #fff;
    box-shadow: 0 0 0 3px var(--primary-light);
}
</style>
</head>
<body>

<div class="topbar">
    <a href="index.php" class="topbar-brand">
        <div class="logo">&#128221;</div>
        <h1>Gu&iacute;as <span>Acad&eacute;micas</span></h1>
    </a>
    <div class="topbar-nav">
        <a href="index.php">&#127968; Inicio</a>
        <a href="history.php">&#128230; Mis docs</a>
        <a href="preview.php?doc=guia">&#128196; Plantillas</a>
    </div>
</div>

<div class="main">

    <div class="progress-header">
        <h2>&#9889; Generando documentos con IA</h2>
        <p>Materia: <strong><?= htmlspecialchars($materia) ?></strong> &middot; Unidad: <strong><?= htmlspecialchars($unidad) ?></strong> &middot; Tema: <strong><?= htmlspecialchars($titulo) ?></strong></p>
        <div class="progress-bar"><div id="progress-fill"></div></div>
        <div class="progress-steps" id="progress-steps">
            <span class="step" data-step="0">&#128221; Conectando IA</span>
            <span class="step" data-step="1">&#128221; Gu&iacute;a: Fundamentos</span>
            <span class="step" data-step="2">&#128221; Gu&iacute;a: Objetivo General</span>
            <span class="step" data-step="3">&#128221; Gu&iacute;a: Objetivos Espec&iacute;ficos</span>
            <span class="step" data-step="4">&#128221; Gu&iacute;a: Preparaci&oacute;n Previa</span>
            <span class="step" data-step="5">&#128221; Gu&iacute;a: Procedimiento</span>
            <span class="step" data-step="6">&#128221; Gu&iacute;a: Evaluaci&oacute;n</span>
            <span class="step" data-step="7">&#128202; Anexo: Objetivo (desde Gu&iacute;a)</span>
            <span class="step" data-step="8">&#128202; Anexo: Introducci&oacute;n</span>
            <span class="step" data-step="9">&#128202; Anexo: Descripci&oacute;n</span>
            <span class="step" data-step="10">&#128202; Anexo: Metodolog&iacute;a</span>
            <span class="step" data-step="11">&#128202; Anexo: Resultados</span>
            <span class="step" data-step="12">&#128202; Anexo: Conclusiones</span>
            <span class="step" data-step="13">&#128202; Anexo: Recomendaciones</span>
            <span class="step" data-step="14">&#128202; Anexo: Bibliograf&iacute;a</span>
            <span class="step" data-step="15">&#128196; Generando Word</span>
            <span class="step" data-step="16">&#128196; Generando PDF</span>
            <span class="step" data-step="17">&#9989; Listo</span>
        </div>
        <div id="status-text">Conectando con la IA...</div>
    </div>

    <div class="previews">
        <div class="doc-card">
            <div class="doc-card-header">
                <h3>&#128196; Gu&iacute;a de Pr&aacute;cticas</h3>
                <span class="badge" id="guia-step">Esperando datos...</span>
            </div>
            <div class="doc-card-body" id="guia-preview"></div>
        </div>
        <div class="doc-card">
            <div class="doc-card-header">
                <h3>&#128202; Anexo de Pr&aacute;cticas</h3>
                <span class="badge" id="anexo-step">Esperando datos...</span>
            </div>
            <div class="doc-card-body" id="anexo-preview"></div>
        </div>
    </div>

    <div class="final-actions" id="final-actions">
        <div class="notif-banner">&#128640; Documentos generados &mdash; pod&eacute;s editar el contenido directamente en las vistas previas antes de descargar</div>
        <div style="margin-bottom:16px">
            <h3>&#9989; Documentos listos para revisar</h3>
            <p style="color:var(--gray-400);font-size:13px;">Hac&eacute; clic en cualquier secci&oacute;n para editarla. Luego descarg&aacute;.</p>
        </div>
        <form id="buildForm" method="POST" action="build_docs.php" target="_blank">
            <input type="hidden" name="titulo" value="<?= htmlspecialchars($titulo) ?>">
            <input type="hidden" name="materia" value="<?= htmlspecialchars($materia) ?>">
            <input type="hidden" name="unidad" value="<?= htmlspecialchars($unidad) ?>">
            <input type="hidden" name="carrera" value="<?= htmlspecialchars($carrera) ?>">
            <input type="hidden" name="nivel" value="<?= htmlspecialchars($nivel) ?>">
            <input type="hidden" name="docente" value="<?= htmlspecialchars($docente) ?>">
            <input type="hidden" name="nro_practica" value="<?= htmlspecialchars($nroPractica) ?>">
            <input type="hidden" name="horas" value="<?= htmlspecialchars($horas) ?>">
            <input type="hidden" name="elaborado" value="<?= htmlspecialchars($elaborado) ?>">
            <input type="hidden" name="revisado" value="<?= htmlspecialchars($revisado) ?>">
            <input type="hidden" name="aprobado" value="<?= htmlspecialchars($aprobado) ?>">
            <input type="hidden" name="referencias_json" value="<?= htmlspecialchars($referenciasJson) ?>">
            <input type="hidden" name="guia_data" id="guiaData">
            <input type="hidden" name="anexo_data" id="anexoData">
            <input type="hidden" name="base_name" value="<?= htmlspecialchars($baseName) ?>">
            <div class="dl-buttons">
                <button type="submit" name="formato" value="word_guia" class="btn btn-primary">&#128196; Gu&iacute;a (Word)</button>
                <button type="submit" name="formato" value="pdf_guia" class="btn btn-success">&#128196; Gu&iacute;a (PDF)</button>
                <button type="submit" name="formato" value="word_anexo" class="btn btn-primary">&#128202; Anexo (Word)</button>
                <button type="submit" name="formato" value="pdf_anexo" class="btn btn-success">&#128202; Anexo (PDF)</button>
                <button type="submit" name="formato" value="ambos" class="btn btn-ghost" style="background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;">&#128190; Descargar ambos</button>
            </div>
        </form>
        <div style="margin-top:16px;display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
            <a href="history.php" class="btn btn-ghost">&#128230; Ver todos mis documentos</a>
            <a href="index.php" class="btn btn-ghost">+ Generar nuevos</a>
        </div>
    </div>

</div>

<script>
const guiaSections = {};
const anexoSections = {};
const guiaPreview = document.getElementById('guia-preview');
const anexoPreview = document.getElementById('anexo-preview');

const GUIA_LABELS = {
    fundamentos: 'Fundamentos del desarrollo',
    objetivo_general: 'Objetivo General',
    objetivos_especificos: 'Objetivos Espec&iacute;ficos',
    preparacion_previa: 'Preparaci&oacute;n Previa',
    procedimiento: 'Procedimiento',
    evaluacion: 'Evaluaci&oacute;n del aprendizaje'
};

const ANEXO_LABELS = {
    introduccion: 'Introducci&oacute;n',
    objetivo_general: 'Objetivo de la pr&aacute;ctica',
    descripcion: 'Descripci&oacute;n del desarrollo',
    metodologia: 'Metodolog&iacute;a',
    resultados: 'Resultados obtenidos',
    conclusiones: 'Conclusiones',
    recomendaciones: 'Recomendaciones',
    bibliografia: 'Bibliograf&iacute;a'
};

function renderPreview(type, key, value) {
    const labels = type === 'guia' ? GUIA_LABELS : ANEXO_LABELS;
    const container = type === 'guia' ? guiaPreview : anexoPreview;
    const sectionsObj = type === 'guia' ? guiaSections : anexoSections;
    const stepId = type === 'guia' ? 'guia-step' : 'anexo-step';

    sectionsObj[key] = value;

    // Re-render
    let html = '';
    for (const [k, v] of Object.entries(sectionsObj)) {
        const label = labels[k] || k;
        html += '<div class="preview-section">';
        html += '<h4>' + label + '</h4>';
        html += '<div class="value fresh">' + v.replace(/\n/g, '<br>') + '</div>';
        html += '</div>';
    }

    // Add remaining placeholders
    for (const [k, label] of Object.entries(labels)) {
        if (!sectionsObj[k]) {
            html += '<div class="preview-section">';
            html += '<h4>' + label + '</h4>';
            html += '<div class="placeholder">Generando contenido...</div>';
            html += '</div>';
        }
    }

    container.innerHTML = html;
    container.scrollTop = container.scrollHeight;

    const count = Object.keys(sectionsObj).length;
    const total = Object.keys(labels).length;
    document.getElementById(stepId).textContent = count + '/' + total + ' secciones';
}

function updateStep(step, status) {
    const el = document.querySelector(`.step[data-step="${step}"]`);
    if (!el) return;
    el.className = 'step ' + status;
}

function showFinal() {
    document.getElementById('final-actions').classList.add('visible');
    // Replace preview content with textareas for easy editing
    document.querySelectorAll('.preview-section').forEach(section => {
        const valueDiv = section.querySelector('.value');
        if (!valueDiv) return;
        const text = valueDiv.innerText;
        const textarea = document.createElement('textarea');
        textarea.className = 'edit-textarea';
        textarea.value = text;
        textarea.rows = Math.max(3, text.split('\n').length);
        valueDiv.replaceWith(textarea);
    });
}

// Capture edited content from textareas on form submit
document.getElementById('buildForm').addEventListener('submit', function(e) {
    const guiaData = {};
    document.querySelectorAll('#guia-preview .preview-section').forEach(section => {
        const label = section.querySelector('h4');
        const textarea = section.querySelector('.edit-textarea');
        if (label && textarea) {
            for (const [key, lbl] of Object.entries(GUIA_LABELS)) {
                if (lbl === label.innerHTML) { guiaData[key] = textarea.value; break; }
            }
        }
    });
    const anexoData = {};
    document.querySelectorAll('#anexo-preview .preview-section').forEach(section => {
        const label = section.querySelector('h4');
        const textarea = section.querySelector('.edit-textarea');
        if (label && textarea) {
            for (const [key, lbl] of Object.entries(ANEXO_LABELS)) {
                if (lbl === label.innerHTML) { anexoData[key] = textarea.value; break; }
            }
        }
    });
    document.getElementById('guiaData').value = JSON.stringify(guiaData);
    document.getElementById('anexoData').value = JSON.stringify(anexoData);
});

// Initial render with placeholders
(function init() {
    let guiaHtml = '';
    for (const [k, label] of Object.entries(GUIA_LABELS)) {
        guiaHtml += '<div class="preview-section"><h4>' + label + '</h4><div class="placeholder">Generando contenido...</div></div>';
    }
    guiaPreview.innerHTML = guiaHtml;

    let anexoHtml = '';
    for (const [k, label] of Object.entries(ANEXO_LABELS)) {
        anexoHtml += '<div class="preview-section"><h4>' + label + '</h4><div class="placeholder">Generando contenido...</div></div>';
    }
    anexoPreview.innerHTML = anexoHtml;
})();
</script>

<?php
// ─── EJECUCIÓN ───
$totalSteps = 17;
$_SESSION['prog_pct'] = 0;

$steps_guia = [
    'fundamentos', 'objetivo_general', 'objetivos_especificos',
    'preparacion_previa', 'procedimiento', 'evaluacion'
];
$steps_anexo = [
    'introduccion', 'descripcion', 'metodologia', 'resultados',
    'conclusiones', 'recomendaciones', 'bibliografia'
];

echo "<script>updateStep(0, 'active');</script>\n"; flush();

try {
    // ── Guía ──
    foreach ($steps_guia as $i => $section) {
        $stepNum = $i + 1;
        $pct = round(($stepNum / $totalSteps) * 100);
        $_SESSION['prog_pct'] = $pct;
        echo "<script>updateStep($stepNum, 'active'); updateStep(" . ($stepNum - 1) . ", 'done');</script>\n"; flush();

        $extra = ($section === 'fundamentos') ? $referencias : [];
        $texto = $gen->generateSection('guia', $section, $materia, $unidad, $titulo, $extra);
        $guiaContent[$section] = $texto;

        echo "<script>
            document.getElementById('status-text').textContent = 'Guía: " . ucfirst(str_replace('_', ' ', $section)) . "...';
            document.getElementById('progress-fill').style.width = '{$pct}%';
            renderPreview('guia', '{$section}', " . json_encode($texto) . ");
        </script>\n"; flush();
    }

    // ── Anexo (objetivo_general se copia de la Guía) ──
    $anexoContent['objetivo_general'] = $guiaContent['objetivo_general'] ?? '';
    echo "<script>updateStep(6, 'done'); updateStep(7, 'active');
        document.getElementById('status-text').textContent = 'Anexo: Objetivo (desde Guía)...';
        renderPreview('anexo', 'objetivo_general', " . json_encode($anexoContent['objetivo_general']) . ");
    </script>\n"; flush();
    echo "<script>updateStep(7, 'done');</script>\n"; flush();

    foreach ($steps_anexo as $i => $section) {
        $stepNum = $i + 8;
        $pct = round(($stepNum / $totalSteps) * 100);
        $_SESSION['prog_pct'] = $pct;
        echo "<script>updateStep($stepNum, 'active'); updateStep(" . ($stepNum - 1) . ", 'done');</script>\n"; flush();

        $extra = ($section === 'bibliografia') ? $referencias : [];
        $texto = $gen->generateSection('anexo', $section, $materia, $unidad, $titulo, $extra, $guiaContent);
        $anexoContent[$section] = $texto;

        echo "<script>
            document.getElementById('status-text').textContent = 'Anexo: " . ucfirst(str_replace('_', ' ', $section)) . "...';
            document.getElementById('progress-fill').style.width = '{$pct}%';
            renderPreview('anexo', '{$section}', " . json_encode($texto) . ");
        </script>\n"; flush();
    }

    // ── Generar Word ──
    echo "<script>updateStep(14, 'done'); updateStep(15, 'active');
        document.getElementById('status-text').textContent = 'Generando documentos Word...';
        document.getElementById('progress-fill').style.width = '90%';
    </script>\n"; flush();

    $guiaContent['carrera'] = $carrera;
    $guiaContent['asignatura'] = $materia;
    $guiaContent['titulo'] = $titulo;
    $docGen->generateGuia($guiaSrc[0], $outGuiaDocx, $guiaContent, $materia, $unidad, $titulo);

    $anexoContent['carrera'] = $carrera;
    $anexoContent['asignatura'] = $materia;
    $anexoContent['titulo'] = $titulo;
    $anexoContent['nivel'] = $nivel;
    $anexoContent['docente'] = $docente;
    $anexoContent['nro_practica'] = $nroPractica;
    $anexoContent['horas'] = $horas;
    $anexoContent['elaborado'] = $elaborado;
    $anexoContent['revisado'] = $revisado;
    $anexoContent['aprobado'] = $aprobado;
    $anexoContent['referencias'] = $referencias;
    $docGen->generateAnexo($anexoSrc[0], $outAnexoDocx, $anexoContent, $materia, $unidad, $titulo);

    // ── Generar PDF ──
    echo "<script>updateStep(15, 'done'); updateStep(16, 'active');
        document.getElementById('status-text').textContent = 'Generando documentos PDF...';
        document.getElementById('progress-fill').style.width = '97%';
    </script>\n"; flush();

    $guiaData = array_merge($guiaContent, [
        'carrera'=>$carrera, 'asignatura'=>$materia, 'titulo'=>$titulo,
        'nivel'=>$nivel, 'docente'=>$docente, 'nro_practica'=>$nroPractica, 'horas'=>$horas,
        'elaborado'=>$elaborado, 'revisado'=>$revisado, 'aprobado'=>$aprobado
    ]);
    $anexoData = array_merge($anexoContent, [
        'carrera'=>$carrera, 'asignatura'=>$materia, 'titulo'=>$titulo,
        'nivel'=>$nivel, 'docente'=>$docente, 'nro_practica'=>$nroPractica, 'horas'=>$horas,
        'elaborado'=>$elaborado, 'revisado'=>$revisado, 'aprobado'=>$aprobado
    ]);
    generarPDF(contenidoGuiaToHTML($guiaData), $outGuiaPdf);
    generarPDF(contenidoAnexoToHTML($anexoData), $outAnexoPdf);

    echo "<script>updateStep(15, 'done');</script>\n"; flush();

    // ── Final ──
    echo "<script>
        updateStep(16, 'done'); updateStep(17, 'active');
        document.getElementById('status-text').textContent = '¡Documentos generados exitosamente! ✅';
        document.getElementById('progress-fill').style.width = '100%';
        showFinal();
    </script>\n"; flush();

} catch (Exception $e) {
    echo "<script>
        document.getElementById('status-text').textContent = 'Error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES) . " ❌';
        document.getElementById('progress-fill').style.background = 'linear-gradient(90deg, #ef4444, #dc2626)';
    </script>\n"; flush();
}
?>
</body>
</html>
