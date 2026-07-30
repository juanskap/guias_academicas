<?php
$requestedFile = $_GET['file'] ?? '';
if ($requestedFile) {
    $filePath = __DIR__ . '/output/' . basename($requestedFile);
    if (file_exists($filePath)) {
        $ext = strtolower(pathinfo($requestedFile, PATHINFO_EXTENSION));
        $mime = $ext === 'pdf' ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . basename($requestedFile) . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }
    $error = 'Archivo no encontrado. Puede que haya sido eliminado o el enlace haya expirado.';
}

$file = $_GET['file'] ?? '';
$file2 = $_GET['file2'] ?? '';
$name = $_GET['name'] ?? 'Documentos';
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
.dl-wrap { display: flex; align-items: center; justify-content: center; padding: 40px 20px; }
.card {
    background: rgba(255,255,255,.98);
    border-radius: 20px;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,.5);
    padding: 40px 36px;
    max-width: 520px;
    width: 100%;
    border: 1px solid rgba(255,255,255,.1);
}
.success-icon {
    width: 64px; height: 64px;
    background: linear-gradient(135deg, #10b981, #059669);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 16px;
    font-size: 28px;
    color: #fff;
    box-shadow: 0 8px 24px rgba(16,185,129,.3);
}
h1 { font-size: 22px; font-weight: 800; color: var(--gray-900); text-align: center; letter-spacing: -.5px; }
.subtitle { color: var(--gray-500); font-size: 14px; text-align: center; margin: 4px 0 28px; }
.doc-list { display: flex; flex-direction: column; gap: 12px; margin-bottom: 28px; }
.doc-item {
    display: flex; align-items: center; gap: 16px;
    padding: 16px 20px;
    background: var(--gray-50);
    border-radius: 12px;
    border: 1px solid var(--gray-100);
    transition: all .2s;
}
.doc-item:hover { border-color: var(--gray-200); background: #fff; }
.doc-icon {
    width: 44px; height: 44px;
    background: var(--primary-light);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.doc-info { flex: 1; }
.doc-name { font-weight: 600; font-size: 14px; color: var(--gray-800); }
.doc-meta { font-size: 12px; color: var(--gray-400); margin-top: 2px; }
.doc-dl {
    padding: 8px 20px;
    background: var(--primary);
    color: #fff;
    text-decoration: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    transition: all .2s;
    white-space: nowrap;
}
.doc-dl:hover { background: var(--primary-dark); transform: translateY(-1px); }
.actions { display: flex; gap: 10px; }
.actions a {
    flex: 1; text-align: center;
    padding: 12px 20px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    transition: all .2s;
}
.btn-back {
    background: var(--gray-100);
    color: var(--gray-700);
}
.btn-back:hover { background: var(--gray-200); }
.btn-new {
    background: linear-gradient(135deg, var(--primary), #1d4ed8);
    color: #fff;
    box-shadow: 0 4px 14px rgba(37,99,235,.3);
}
.btn-new:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(37,99,235,.4); }
.alert {
    padding: 14px 16px;
    border-radius: 10px;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
}
@media (max-width:480px) {
    .card { padding: 28px 20px; }
    .actions { flex-direction: column; }
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
<div class="dl-wrap">
<div class="card">
    <div class="success-icon">&#10003;</div>
    <h1>Documentos Generados Exitosamente</h1>
    <p class="subtitle">Los documentos para &laquo;<strong><?= htmlspecialchars($name) ?></strong>&raquo; est&aacute;n listos para descargar</p>

    <?php if (isset($error)): ?>
    <div class="alert">&#9888; <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="doc-list">
        <div class="doc-item">
            <div class="doc-icon">&#128196;</div>
            <div class="doc-info">
                <div class="doc-name">Gu&iacute;a de Pr&aacute;cticas</div>
                <div class="doc-meta">Documento Word (.docx) &bull; Plantilla institucional</div>
            </div>
            <a href="download.php?file=<?= urlencode($file) ?>" class="doc-dl">Descargar</a>
        </div>
        <div class="doc-item">
            <div class="doc-icon">&#128196;</div>
            <div class="doc-info">
                <div class="doc-name">Anexo de Pr&aacute;cticas</div>
                <div class="doc-meta">Documento Word (.docx) &bull; Informe de experimentaci&oacute;n</div>
            </div>
            <a href="download.php?file=<?= urlencode($file2) ?>" class="doc-dl">Descargar</a>
        </div>
    </div>

    <div class="actions">
        <a href="index.php" class="btn-back">&#8592; Volver al inicio</a>
        <a href="index.php" class="btn-new">+ Generar nuevos</a>
    </div>
</div>
</div>
</body>
</html>
