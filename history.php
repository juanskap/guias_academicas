<?php
$outputDir = __DIR__ . '/output';

// Get all generated files
$allFiles = array_merge(
    glob($outputDir . '/*.docx'),
    glob($outputDir . '/*.pdf')
);

$docs = [];
foreach ($allFiles as $f) {
    $name = basename($f);
    $size = filesize($f);
    $time = filemtime($f);
    $ext = pathinfo($name, PATHINFO_EXTENSION);

    // Determine doc type (Guia/Anexo) and format (docx/pdf)
    $docType = str_starts_with($name, 'Guia_') ? 'guia' : (str_starts_with($name, 'Anexo_') ? 'anexo' : null);
    if (!$docType) continue;

    $group = preg_replace('/^(Guia_|Anexo_)(.*)_(\d{8}_\d{6})\.(' . $ext . ')$/', '$2', $name);
    $ts = preg_replace('/^(Guia_|Anexo_)(.*)_(\d{8}_\d{6})\.(' . $ext . ')$/', '$3', $name);

    $docs[$ts][$docType][$ext] = [
        'name' => $name,
        'size' => $size,
        'time' => $time,
        'title' => str_replace('_', ' ', $group),
    ];
}
krsort($docs); // newest first
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Documentos Generados</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
    --primary: #2563eb; --primary-dark: #1d4ed8; --primary-light: #dbeafe;
    --gray-50: #f8fafc; --gray-100: #f1f5f9; --gray-200: #e2e8f0;
    --gray-300: #cbd5e1; --gray-400: #94a3b8; --gray-500: #64748b;
    --gray-600: #475569; --gray-700: #334155; --gray-800: #1e293b; --gray-900: #0f172a;
    --success: #10b981; --accent: #06b6d4;
}
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: linear-gradient(135deg, #0f172a, #1e293b, #0f172a);
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
.topbar-nav a {
    padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 500;
    text-decoration: none; color: var(--gray-400); transition: all .2s;
}
.topbar-nav a:hover { background: rgba(255,255,255,.08); color: #fff; }
.topbar-nav a.active { background: var(--primary); color: #fff; }
.container { max-width: 960px; margin: 32px auto; padding: 0 24px; }
.header-section { margin-bottom: 28px; }
.header-section h1 { color: #fff; font-size: 26px; font-weight: 800; letter-spacing: -.5px; }
.header-section p { color: var(--gray-400); font-size: 14px; margin-top: 4px; }
.docs-count {
    display: inline-block; background: rgba(255,255,255,.1);
    padding: 4px 14px; border-radius: 20px; font-size: 13px; color: var(--gray-300);
    margin-top: 8px;
}
.empty-state {
    text-align: center; padding: 60px 20px;
    background: rgba(255,255,255,.05); border-radius: 16px;
    border: 1px dashed rgba(255,255,255,.1);
}
.empty-state .icon { font-size: 48px; margin-bottom: 16px; }
.empty-state h3 { color: #fff; font-size: 18px; font-weight: 600; margin-bottom: 6px; }
.empty-state p { color: var(--gray-400); font-size: 14px; margin-bottom: 20px; }
.empty-state a {
    display: inline-block; padding: 12px 28px; background: var(--primary);
    color: #fff; text-decoration: none; border-radius: 10px; font-weight: 600; font-size: 14px;
    transition: all .2s;
}
.empty-state a:hover { background: var(--primary-dark); transform: translateY(-1px); }

.doc-group {
    background: rgba(255,255,255,.98);
    border-radius: 16px; overflow: hidden;
    margin-bottom: 16px;
    border: 1px solid rgba(255,255,255,.08);
    box-shadow: 0 4px 20px rgba(0,0,0,.2);
    transition: all .2s;
}
.doc-group:hover { box-shadow: 0 8px 30px rgba(0,0,0,.3); }
.doc-group-header {
    padding: 16px 24px;
    display: flex; align-items: center; justify-content: space-between;
    border-bottom: 1px solid var(--gray-100);
    cursor: pointer;
}
.doc-group-header .info { display: flex; align-items: center; gap: 12px; }
.doc-group-header .title { font-weight: 600; font-size: 14px; color: var(--gray-800); }
.doc-group-header .date { font-size: 12px; color: var(--gray-400); }
.doc-group-header .arrow { color: var(--gray-400); transition: transform .2s; font-size: 14px; }
.doc-group.open .doc-group-header .arrow { transform: rotate(180deg); }
.doc-group-body { display: none; }
.doc-group.open .doc-group-body { display: block; }
.doc-item {
    padding: 14px 24px;
    display: flex; align-items: center; justify-content: space-between;
    border-bottom: 1px solid var(--gray-50);
}
.doc-item:last-child { border-bottom: none; }
.doc-item .icon {
    width: 38px; height: 38px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; flex-shrink: 0;
}
.doc-item .icon.guia { background: var(--primary-light); }
.doc-item .icon.anexo { background: #d1fae5; }
.doc-item .info { flex: 1; margin: 0 14px; }
.doc-item .info .name { font-weight: 500; font-size: 13px; color: var(--gray-700); }
.doc-item .info .meta { font-size: 11px; color: var(--gray-400); margin-top: 2px; }
.doc-item .btn-dl {
    padding: 8px 18px; border-radius: 8px; font-size: 12px; font-weight: 600;
    text-decoration: none; transition: all .2s;
}
.doc-item .btn-dl.guia { background: var(--primary); color: #fff; }
.doc-item .btn-dl.guia:hover { background: var(--primary-dark); }
.doc-item .btn-dl.anexo { background: #065f46; color: #fff; }
.doc-item .btn-dl.anexo:hover { background: #047857; }
@media (max-width:640px) {
    .topbar { padding: 12px 16px; }
    .container { padding: 0 16px; }
    .doc-group-header { flex-direction: column; align-items: flex-start; gap: 6px; }
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
        <a href="history.php" class="active">&#128230; Mis documentos</a>
        <a href="preview.php?doc=guia">&#128196; Plantillas</a>
    </div>
</div>

<div class="container">
    <div class="header-section">
        <h1>&#128230; Mis documentos generados</h1>
        <p>Todos los documentos que generaste est&aacute;n guardados aqu&iacute;</p>
        <div class="docs-count"><?= count($allFiles) ?> archivo(s)</div>
    </div>

    <?php if (empty($docs)): ?>
    <div class="empty-state">
        <div class="icon">&#128196;</div>
        <h3>No hay documentos generados a&uacute;n</h3>
        <p>Gener&aacute; tu primera Gu&iacute;a de Pr&aacute;cticas y su Anexo</p>
        <a href="index.php">+ Generar documentos</a>
    </div>
    <?php else: ?>
        <?php foreach ($docs as $ts => $pair): ?>
        <div class="doc-group">
            <div class="doc-group-header" onclick="this.parentElement.classList.toggle('open')">
                <div class="info">
                    <span class="title"><?= htmlspecialchars(ucwords($pair['guia']['title'] ?? $pair['anexo']['title'] ?? '')) ?></span>
                    <span class="date"><?= date('d/m/Y H:i', $pair['guia']['time'] ?? $pair['anexo']['time'] ?? 0) ?></span>
                </div>
                <span class="arrow">&#9660;</span>
            </div>
            <div class="doc-group-body">
                <?php if (isset($pair['guia'])): ?>
                <div class="doc-item">
                    <div class="icon guia">&#128196;</div>
                    <div class="info">
                        <div class="name">Gu&iacute;a de Pr&aacute;cticas</div>
                        <div class="meta">
                            <?php if (isset($pair['guia']['docx'])): ?><?= number_format($pair['guia']['docx']['size'] / 1024, 1) ?> KB &bull; <?php endif; ?>
                            <?php if (isset($pair['guia']['pdf'])): ?><?= number_format($pair['guia']['pdf']['size'] / 1024, 1) ?> KB<?php endif; ?>
                        </div>
                    </div>
                    <div style="display:flex;gap:6px;">
                        <?php if (isset($pair['guia']['docx'])): ?>
                        <a href="download.php?file=<?= urlencode($pair['guia']['docx']['name']) ?>" class="btn-dl guia" style="font-size:11px;padding:6px 12px;">Word</a>
                        <?php endif; ?>
                        <?php if (isset($pair['guia']['pdf'])): ?>
                        <a href="download.php?file=<?= urlencode($pair['guia']['pdf']['name']) ?>" class="btn-dl guia" style="font-size:11px;padding:6px 12px;background:#059669;">PDF</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (isset($pair['anexo'])): ?>
                <div class="doc-item">
                    <div class="icon anexo">&#128203;</div>
                    <div class="info">
                        <div class="name">Anexo de Pr&aacute;cticas</div>
                        <div class="meta">
                            <?php if (isset($pair['anexo']['docx'])): ?><?= number_format($pair['anexo']['docx']['size'] / 1024, 1) ?> KB &bull; <?php endif; ?>
                            <?php if (isset($pair['anexo']['pdf'])): ?><?= number_format($pair['anexo']['pdf']['size'] / 1024, 1) ?> KB<?php endif; ?>
                        </div>
                    </div>
                    <div style="display:flex;gap:6px;">
                        <?php if (isset($pair['anexo']['docx'])): ?>
                        <a href="download.php?file=<?= urlencode($pair['anexo']['docx']['name']) ?>" class="btn-dl anexo" style="font-size:11px;padding:6px 12px;">Word</a>
                        <?php endif; ?>
                        <?php if (isset($pair['anexo']['pdf'])): ?>
                        <a href="download.php?file=<?= urlencode($pair['anexo']['pdf']['name']) ?>" class="btn-dl anexo" style="font-size:11px;padding:6px 12px;background:#059669;">PDF</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
// Open first group by default
document.querySelector('.doc-group')?.classList.add('open');
</script>
</body>
</html>
