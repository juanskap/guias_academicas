<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Generador de Guías Académicas</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
    --primary: #2563eb; --primary-dark: #1d4ed8; --primary-light: #dbeafe;
    --secondary: #0f172a; --accent: #06b6d4;
    --gray-50: #f8fafc; --gray-100: #f1f5f9; --gray-200: #e2e8f0;
    --gray-300: #cbd5e1; --gray-400: #94a3b8; --gray-500: #64748b;
    --gray-600: #475569; --gray-700: #334155; --gray-800: #1e293b; --gray-900: #0f172a;
    --success: #10b981; --error: #ef4444; --warning: #f59e0b;
    --radius: 10px; --radius-lg: 16px;
}
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
    min-height: 100vh;
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
.topbar-nav { display: flex; gap: 6px; align-items: center; }
.topbar-nav a {
    padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 500;
    text-decoration: none; color: var(--gray-400); transition: all .2s;
}
.topbar-nav a:hover { background: rgba(255,255,255,.08); color: #fff; }

.main-layout { display: grid; grid-template-columns: 1fr 420px; gap: 28px; max-width: 1320px; margin: 28px auto; padding: 0 28px; }
@media (max-width:1024px) { .main-layout { grid-template-columns: 1fr; } }

/* Preview cards */
.preview-section h2 { color: #fff; font-size: 14px; font-weight: 600; margin-bottom: 14px; letter-spacing: .3px; text-transform: uppercase; opacity: .7; }
.preview-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
.preview-card { background: #fff; border-radius: var(--radius-lg); box-shadow: 0 20px 40px -12px rgba(0,0,0,.4); overflow: hidden; border: 1px solid rgba(255,255,255,.08); }
.preview-card-header { padding: 16px 20px 12px; border-bottom: 1px solid var(--gray-100); display: flex; align-items: center; justify-content: space-between; }
.preview-card-header .title { font-size: 13px; font-weight: 700; color: var(--gray-800); display: flex; align-items: center; gap: 8px; }
.preview-card-header .badge { font-size: 10px; font-weight: 600; padding: 3px 10px; border-radius: 20px; letter-spacing: .3px; }
.badge-guia { background: var(--primary-light); color: var(--primary); }
.badge-anexo { background: #d1fae5; color: #065f46; }
.preview-card-body { padding: 16px 20px; font-size: 11px; line-height: 1.6; color: var(--gray-600); max-height: 440px; overflow-y: auto; }
.preview-card-body::-webkit-scrollbar { width: 4px; }
.preview-card-body::-webkit-scrollbar-thumb { background: var(--gray-200); border-radius: 4px; }
.preview-content .p-title {
    font-weight: 700; color: var(--gray-800); font-size: 11px;
    padding: 5px 0 2px; margin-top: 5px; border-bottom: 1px solid var(--gray-100);
    text-transform: uppercase; letter-spacing: .3px;
}
.preview-content .p-line { padding: 2px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.preview-content .p-field { font-weight: 500; color: var(--gray-700); }
.preview-content .p-empty {
    color: var(--warning); background: #fef3c7; padding: 2px 8px;
    border-radius: 4px; font-style: italic; font-size: 10px; border-left: 2px solid var(--warning);
    display: inline-block;
}
.preview-card-footer { padding: 12px 20px; border-top: 1px solid var(--gray-100); display: flex; gap: 8px; }
.preview-card-footer a { flex: 1; text-align: center; padding: 8px; border-radius: 8px; font-size: 12px; font-weight: 600; text-decoration: none; transition: all .2s; }
.btn-outline-preview { background: var(--gray-50); color: var(--gray-600); border: 1px solid var(--gray-200); }
.btn-outline-preview:hover { background: var(--gray-100); }
.btn-solid-preview { background: var(--primary); color: #fff; }
.btn-solid-preview:hover { background: var(--primary-dark); }

/* Form panel */
.form-panel {
    background: rgba(255,255,255,.98); border-radius: var(--radius-lg);
    box-shadow: 0 20px 40px -12px rgba(0,0,0,.4);
    padding: 24px; border: 1px solid rgba(255,255,255,.08);
    position: sticky; top: 90px; align-self: start;
}
.form-panel h2 { font-size: 17px; font-weight: 700; color: var(--gray-900); margin-bottom: 2px; letter-spacing:-.3px; }
.form-panel .form-sub { font-size: 12px; color: var(--gray-500); margin-bottom: 18px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.form-group { margin-bottom: 12px; }
.form-group label { display: block; font-size: 11px; font-weight: 600; color: var(--gray-700); margin-bottom: 4px; }
.form-group label .req { color: var(--error); }
.input-wrap { position: relative; }
.input-wrap .icn { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--gray-400); font-size: 14px; pointer-events: none; }
.input-wrap input, .input-wrap select {
    width: 100%; padding: 9px 11px 9px 36px;
    border: 1.5px solid var(--gray-200); border-radius: var(--radius);
    font-size: 13px; font-family: inherit; background: var(--gray-50);
    color: var(--gray-800); outline: none; transition: all .2s;
}
.input-wrap input:focus, .input-wrap select:focus {
    border-color: var(--primary); background: #fff;
    box-shadow: 0 0 0 3px var(--primary-light);
}
.input-wrap select { padding-left: 36px; cursor: pointer; }
.input-wrap input::placeholder { color: var(--gray-400); }

/* Source search */
.source-section { margin-bottom: 12px; }
.source-section label { display: block; font-size: 11px; font-weight: 600; color: var(--gray-700); margin-bottom: 4px; }
.source-search { display: flex; gap: 6px; }
.source-search input {
    flex: 1; padding: 9px 12px; border: 1.5px solid var(--gray-200);
    border-radius: var(--radius); font-size: 13px; font-family: inherit;
    background: var(--gray-50); color: var(--gray-800); outline: none;
}
.source-search input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-light); }
.source-search button {
    padding: 9px 16px; border: none; border-radius: var(--radius);
    background: var(--success); color: #fff; font-size: 13px; font-weight: 600;
    cursor: pointer; transition: all .2s; white-space: nowrap;
}
.source-search button:hover { background: #059669; }
.source-results { margin-top: 6px; max-height: 180px; overflow-y: auto; }
.source-results::-webkit-scrollbar { width: 4px; }
.source-results::-webkit-scrollbar-thumb { background: var(--gray-200); border-radius: 4px; }
.source-item {
    padding: 8px 10px; border: 1px solid var(--gray-100); border-radius: 6px;
    margin-bottom: 4px; cursor: pointer; transition: all .15s;
    display: flex; align-items: flex-start; gap: 8px;
}
.source-item:hover { background: var(--gray-50); border-color: var(--gray-200); }
.source-item.selected { background: #ecfdf5; border-color: var(--success); }
.source-item .s-check { margin-top: 2px; font-size: 14px; color: var(--gray-400); }
.source-item.selected .s-check { color: var(--success); }
.source-item .s-info { flex: 1; font-size: 11px; line-height: 1.4; }
.source-item .s-info .s-title { font-weight: 600; color: var(--gray-800); }
.source-item .s-info .s-meta { color: var(--gray-500); font-size: 10px; }
.source-count { font-size: 11px; color: var(--gray-500); margin-top: 4px; }

.btn-generate {
    width: 100%; padding: 12px; border: none; border-radius: var(--radius);
    font-size: 14px; font-weight: 600; font-family: inherit; cursor: pointer;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: #fff; box-shadow: 0 4px 14px rgba(37,99,235,.3);
    transition: all .2s; display: flex; align-items: center; justify-content: center; gap: 8px;
}
.btn-generate:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(37,99,235,.4); }
.btn-generate:disabled { opacity: .5; cursor: not-allowed; }

.alert-error { padding: 10px 14px; border-radius: var(--radius); font-size: 12px; display: flex; align-items: center; gap: 8px; margin-bottom: 14px; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
</style>
</head>
<body>

<div class="topbar">
    <a href="index.php" class="topbar-brand">
        <div class="logo">&#128221;</div>
        <h1>Gu&iacute;as <span>Acad&eacute;micas</span></h1>
    </a>
    <div class="topbar-nav">
        <a href="index.php" class="active">&#127968; Inicio</a>
        <a href="history.php">&#128230; Mis docs</a>
        <a href="preview.php?doc=guia">&#128196; Plantillas</a>
    </div>
</div>

<div class="main-layout">

    <!-- LEFT: Document Previews -->
    <div class="preview-section">
        <h2>&#128196; Vista previa de documentos</h2>
        <div class="preview-grid">

            <!-- Guía Card -->
            <div class="preview-card">
                <div class="preview-card-header">
                    <span class="title">&#128221; Gu&iacute;a de Pr&aacute;cticas</span>
                    <span class="badge badge-guia">Plantilla</span>
                </div>
                <div class="preview-card-body">
                    <div class="preview-content">
                        <div class="p-title">Datos Informativos</div>
                        <div class="p-line"><span class="p-field">Carrera:</span> <span class="p-empty">pendiente</span></div>
                        <div class="p-line"><span class="p-field">Asignatura:</span> <span class="p-empty">pendiente</span></div>
                        <div class="p-line"><span class="p-field">Unidad:</span> <span class="p-empty">pendiente</span></div>
                        <div class="p-line"><span class="p-field">T&iacute;tulo:</span> <span class="p-empty">pendiente</span></div>
                        <div class="p-line"><span class="p-field">Nivel:</span> <span class="p-empty">pendiente</span> &bull; <span class="p-field">Docente:</span> <span class="p-empty">pendiente</span></div>
                        <div class="p-line"><span class="p-field">Nro. pr&aacute;ctica:</span> <span class="p-empty">pendiente</span> &bull; <span class="p-field">Tiempo:</span> <span class="p-empty">pendiente</span></div>
                        <div class="p-title">Fundamentos</div>
                        <div class="p-line"><span class="p-empty">conceptos y referencias APA</span></div>
                        <div class="p-title">Objetivo General &bull; Espec&iacute;ficos</div>
                        <div class="p-line"><span class="p-empty">generado por IA</span></div>
                        <div class="p-title">Preparaci&oacute;n Previa &bull; Procedimiento</div>
                        <div class="p-line"><span class="p-empty">conocimientos y pasos</span></div>
                        <div class="p-title">Evaluaci&oacute;n</div>
                        <div class="p-line"><span class="p-empty">criterios de evaluaci&oacute;n</span></div>
                    </div>
                </div>
                <div class="preview-card-footer">
                    <a href="preview.php?doc=guia" class="btn-outline-preview">&#128065; Ver detalle</a>
                    <a href="download_template.php?doc=guia" class="btn-solid-preview">&#11015; Descargar</a>
                </div>
            </div>

            <!-- Anexo Card -->
            <div class="preview-card">
                <div class="preview-card-header">
                    <span class="title">&#128203; Anexo de Pr&aacute;cticas</span>
                    <span class="badge badge-anexo">Se genera de la Gu&iacute;a</span>
                </div>
                <div class="preview-card-body">
                    <div class="preview-content">
                        <div class="p-title">Datos Informativos</div>
                        <div class="p-line"><span class="p-empty">mismos datos de la Gu&iacute;a</span></div>
                        <div class="p-title">Introducci&oacute;n &bull; Objetivo</div>
                        <div class="p-line"><span class="p-empty">generado por IA</span></div>
                        <div class="p-title">Descripci&oacute;n &bull; Metodolog&iacute;a</div>
                        <div class="p-line"><span class="p-empty">desarrollo y enfoque</span></div>
                        <div class="p-title">Resultados &bull; Conclusiones</div>
                        <div class="p-line"><span class="p-empty">resultados esperados</span></div>
                        <div class="p-title">Recomendaciones &bull; Bibliograf&iacute;a</div>
                        <div class="p-line"><span class="p-empty">generado por IA + fuentes OpenAlex</span></div>
                    </div>
                </div>
                <div class="preview-card-footer">
                    <a href="preview.php?doc=anexo" class="btn-outline-preview">&#128065; Ver detalle</a>
                    <a href="download_template.php?doc=anexo" class="btn-solid-preview" style="background:#065f46;">&#11015; Descargar</a>
                </div>
            </div>

        </div>
    </div>

    <!-- RIGHT: Form Panel -->
    <div class="form-panel">
        <h2>&#9889; Generar documentos</h2>
        <p class="form-sub">Complet&aacute; todos los datos y la IA crear&aacute; el contenido</p>

        <?php if (isset($_GET['error'])): ?>
        <div class="alert-error"><span>&#9888;</span> <?= htmlspecialchars($_GET['error']) ?></div>
        <?php endif; ?>

        <form action="process.php" method="POST" id="mainForm">

            <div class="form-group">
                <label>Materia / Asignatura <span class="req">*</span></label>
                <div class="input-wrap">
                    <span class="icn">&#128218;</span>
                    <input type="text" name="materia" required placeholder="Ej: Tendencias Actuales de Desarrollo de Software">
                </div>
            </div>

            <div class="form-group">
                <label>Unidad <span class="req">*</span></label>
                <div class="input-wrap">
                    <span class="icn">&#128196;</span>
                    <input type="text" name="unidad" required placeholder="Ej: Unidad 1 - Arquitecturas">
                </div>
            </div>

            <div class="form-group">
                <label>T&iacute;tulo del Tema <span class="req">*</span></label>
                <div class="input-wrap">
                    <span class="icn">&#128295;</span>
                    <input type="text" name="titulo" required placeholder="Ej: Implementaci&oacute;n de Microservicios">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Carrera</label>
                    <div class="input-wrap">
                        <span class="icn">&#127891;</span>
                        <input type="text" name="carrera" value="Tecnología Superior en Desarrollo de Software" placeholder="Ej: Tecnología en Desarrollo de Software">
                    </div>
                </div>
                <div class="form-group">
                    <label>Nivel Acad&eacute;mico</label>
                    <div class="input-wrap">
                        <span class="icn">&#127891;</span>
                        <select name="nivel">
                            <option value="I">I</option><option value="II">II</option>
                            <option value="III">III</option><option value="IV" selected>IV</option>
                            <option value="V">V</option><option value="VI">VI</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Docente</label>
                <div class="input-wrap">
                    <span class="icn">&#128218;</span>
                    <input type="text" name="docente" value="Ing. Diana Ramírez Garófalo" placeholder="Nombre del docente">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Nro. de Pr&aacute;ctica</label>
                    <div class="input-wrap">
                        <span class="icn">&#35;</span>
                        <input type="number" name="nro_practica" value="1" min="1">
                    </div>
                </div>
                <div class="form-group">
                    <label>Horas / Tiempo</label>
                    <div class="input-wrap">
                        <span class="icn">&#9200;</span>
                        <input type="text" name="horas" value="3" placeholder="Ej: 3 horas">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Elaborado por</label>
                <div class="input-wrap">
                    <span class="icn">&#9997;</span>
                    <input type="text" name="elaborado" value="Ing. Diana Ramírez Garófalo" placeholder="Nombre de quien elabora">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Revisado por</label>
                    <div class="input-wrap">
                        <span class="icn">&#128065;</span>
                        <input type="text" name="revisado" value="Lcda. Diana Alegría Camino" placeholder="Nombre de quien revisa">
                    </div>
                </div>
                <div class="form-group">
                    <label>Aprobado por</label>
                    <div class="input-wrap">
                        <span class="icn">&#10003;</span>
                        <input type="text" name="aprobado" value="Ing. Maribel Fierro Montero" placeholder="Nombre de quien aprueba">
                    </div>
                </div>
            </div>

            <!-- Source Search -->
            <div class="source-section">
                <label>&#128269; Fuentes acad&eacute;micas verificadas (OpenAlex)</label>
                <div class="source-search">
                    <input type="text" id="searchQuery" placeholder="Buscar por tema..." value="">
                    <button type="button" onclick="buscarFuentes()">Buscar</button>
                </div>
                <div id="sourceResults" class="source-results"></div>
                <input type="hidden" name="referencias_json" id="referencias_json" value="[]">
                <div class="source-count" id="sourceCount">0 fuentes seleccionadas</div>
            </div>

            <button type="submit" id="submitBtn" class="btn-generate">
                <span>Generar Gu&iacute;a + Anexo</span>
                <span style="font-size:18px">&#10132;</span>
            </button>
        </form>

        <div style="margin-top:14px;padding:10px 12px;background:var(--gray-50);border-radius:8px;font-size:11px;color:var(--gray-500);line-height:1.5">
            <strong>&#9432; Info:</strong> Usa Ollama (IA local) + fuentes OpenAlex verificables. Tiempo estimado: 2-4 min.
        </div>
    </div>
</div>

<script>
let selectedSources = [];

function buscarFuentes() {
    const q = document.getElementById('searchQuery').value.trim();
    if (!q) return;
    const container = document.getElementById('sourceResults');
    container.innerHTML = '<div style="padding:8px;color:var(--gray-400);font-size:12px;">Buscando...</div>';
    fetch('api/sources.php?q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(data => {
            if (!data || data.error || data.length === 0) {
                container.innerHTML = '<div style="padding:8px;color:var(--gray-500);font-size:12px;">Sin resultados. Intent&aacute; con otros t&eacute;rminos.</div>';
                return;
            }
            container.innerHTML = '';
            data.forEach((item, i) => {
                const div = document.createElement('div');
                div.className = 'source-item';
                if (selectedSources.some(s => s.titulo === item.titulo)) div.classList.add('selected');
                div.innerHTML = '<div class="s-check">' + (div.classList.contains('selected') ? '&#9989;' : '&#9744;') + '</div>' +
                    '<div class="s-info"><div class="s-title">' + (item.titulo || 'Sin título') + '</div>' +
                    '<div class="s-meta">' + (item.autores || '') + ' (' + (item.anio || '') + ')' +
                    (item.doi ? ' &bull; DOI: ' + item.doi : '') + '</div></div>';
                div.onclick = function() {
                    const idx = selectedSources.findIndex(s => s.titulo === item.titulo);
                    if (idx >= 0) {
                        selectedSources.splice(idx, 1);
                        this.classList.remove('selected');
                        this.querySelector('.s-check').textContent = '\u2744';
                    } else {
                        selectedSources.push(item);
                        this.classList.add('selected');
                        this.querySelector('.s-check').textContent = '\u2705';
                    }
                    document.getElementById('referencias_json').value = JSON.stringify(selectedSources);
                    document.getElementById('sourceCount').textContent = selectedSources.length + ' fuente(s) seleccionada(s)';
                };
                container.appendChild(div);
            });
        })
        .catch(() => {
            container.innerHTML = '<div style="padding:8px;color:var(--gray-500);font-size:12px;">Error de conexi&oacute;n al buscar fuentes.</div>';
        });
}
</script>
</body>
</html>
