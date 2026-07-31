<?php
$jobId = $_GET['job'] ?? '';
if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $jobId)) {
    header('Location: index.php?error=ID de trabajo inválido');
    exit;
}
$jobFile = __DIR__ . '/jobs/' . $jobId . '.json';
if (!file_exists($jobFile)) {
    header('Location: index.php?error=Trabajo no encontrado');
    exit;
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
.step.error { background: rgba(239,68,68,.12); color: #ef4444; }

#status-text { color: var(--accent); font-size: 14px; font-weight: 500; margin-top: 8px; }

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
        <p id="job-info">Cargando...</p>
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
        <div id="status-text">Iniciando...</div>
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
        <div class="notif-banner">&#128640; Documentos generados con &eacute;xito</div>
        <div style="margin-bottom:16px">
            <h3>&#9989; Documentos listos</h3>
            <p style="color:var(--gray-400);font-size:13px;">Pod&eacute;s descargarlos desde aqu&iacute; o ir a &quot;Mis docs&quot; para ver el historial.</p>
        </div>
        <div class="dl-buttons" id="dl-buttons"></div>
        <div style="margin-top:16px;display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
            <a href="history.php" class="btn btn-ghost">&#128230; Ver todos mis documentos</a>
            <a href="index.php" class="btn btn-ghost">+ Generar nuevos</a>
        </div>
    </div>

</div>

<script>
const JOB_ID = '<?= $jobId ?>';
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

let prevGuia = {};
let prevAnexo = {};

function renderPreview(containerId, labels, sections) {
    const container = document.getElementById(containerId);
    let html = '';
    for (const [k, label] of Object.entries(labels)) {
        const v = sections[k];
        html += '<div class="preview-section">';
        html += '<h4>' + label + '</h4>';
        if (v) {
            html += '<div class="value fresh">' + v.replace(/\n/g, '<br>') + '</div>';
        } else {
            html += '<div class="placeholder">Generando contenido...</div>';
        }
        html += '</div>';
    }
    container.innerHTML = html;
    container.scrollTop = container.scrollHeight;
}

function updateSteps(job) {
    document.querySelectorAll('.step').forEach(el => {
        const s = parseInt(el.dataset.step);
        el.className = 'step';
        if (job.status === 'error') {
            el.classList.add('error');
        } else if (s < job.step) {
            el.classList.add('done');
        } else if (s === job.step) {
            el.classList.add('active');
        }
    });
    document.getElementById('progress-fill').style.width = job.progress + '%';
    document.getElementById('status-text').textContent = job.status === 'error'
        ? 'Error: ' + (job.error || 'desconocido')
        : job.step_label || 'Procesando...';
    const guiaCount = Object.keys(job.guia_content || {}).length;
    const anexoCount = Object.keys(job.anexo_content || {}).length;
    document.getElementById('guia-step').textContent = guiaCount + '/6 secciones';
    document.getElementById('anexo-step').textContent = anexoCount + '/8 secciones';
}

function showFinal(job) {
    const el = document.getElementById('final-actions');
    el.classList.add('visible');
    const btns = document.getElementById('dl-buttons');
    if (job.output) {
        const base = job.output.guia_docx || '';
        const name = base.replace('/Guia_', '').replace('.docx', '');
        btns.innerHTML = [
            '<a href="download.php?file=Guia_' + name + '.docx" class="btn btn-primary">&#128196; Gu&iacute;a (Word)</a>',
            '<a href="download.php?file=Guia_' + name + '.pdf" class="btn btn-success">&#128196; Gu&iacute;a (PDF)</a>',
            '<a href="download.php?file=Anexo_' + name + '.docx" class="btn btn-primary">&#128202; Anexo (Word)</a>',
            '<a href="download.php?file=Anexo_' + name + '.pdf" class="btn btn-success">&#128202; Anexo (PDF)</a>'
        ].join('');
    }
}

async function poll() {
    try {
        const r = await fetch('status.php?job=' + JOB_ID);
        const job = await r.json();

        if (job.status === 'error') {
            document.getElementById('job-info').textContent = 'Error en la generaci&oacute;n';
            document.getElementById('status-text').textContent = 'Error: ' + (job.error || 'desconocido');
            document.getElementById('progress-fill').style.background = 'linear-gradient(90deg, #ef4444, #dc2626)';
            updateSteps(job);
            return;
        }

        document.getElementById('job-info').textContent = 'Materia: ' + (job.params?.materia || '') + ' &middot; Tema: ' + (job.params?.titulo || '');

        updateSteps(job);

        if (job.guia_content && JSON.stringify(job.guia_content) !== JSON.stringify(prevGuia)) {
            prevGuia = JSON.parse(JSON.stringify(job.guia_content));
            renderPreview('guia-preview', GUIA_LABELS, job.guia_content);
        }
        if (job.anexo_content && JSON.stringify(job.anexo_content) !== JSON.stringify(prevAnexo)) {
            prevAnexo = JSON.parse(JSON.stringify(job.anexo_content));
            renderPreview('anexo-preview', ANEXO_LABELS, job.anexo_content);
        }

        if (job.status === 'completed') {
            updateSteps(job);
            showFinal(job);
            return;
        }

        setTimeout(poll, 2000);
    } catch (e) {
        document.getElementById('status-text').textContent = 'Esperando al servidor...';
        setTimeout(poll, 3000);
    }
}

poll();
</script>
</body>
</html>
