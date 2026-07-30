<?php
set_time_limit(300);
require_once __DIR__ . '/lib/OllamaClient.php';
require_once __DIR__ . '/lib/ContentGenerator.php';
require_once __DIR__ . '/lib/DocumentGenerator.php';
require_once __DIR__ . '/lib/PDFGenerator.php';

define('PROJECT_ROOT', __DIR__);

$titulo = $_POST['titulo'] ?? 'Documento';
$materia = $_POST['materia'] ?? '';
$unidad = $_POST['unidad'] ?? '';
$carrera = $_POST['carrera'] ?? '';
$nivel = $_POST['nivel'] ?? 'IV';
$docente = $_POST['docente'] ?? '';
$nroPractica = $_POST['nro_practica'] ?? '1';
$horas = $_POST['horas'] ?? '3';
$elaborado = $_POST['elaborado'] ?? '';
$revisado = $_POST['revisado'] ?? '';
$aprobado = $_POST['aprobado'] ?? '';
$guiaData = json_decode($_POST['guia_data'] ?? '{}', true) ?: [];
$anexoData = json_decode($_POST['anexo_data'] ?? '{}', true) ?: [];
$baseName = $_POST['base_name'] ?? ('doc_' . date('Ymd_His'));
$formato = $_POST['formato'] ?? 'ambos';

$guiaSrc = glob(PROJECT_ROOT . '/Guía de Prácticas *.docx');
$anexoSrc = glob(PROJECT_ROOT . '/Anexo prácticas *.docx');
if (empty($guiaSrc) || empty($anexoSrc)) {
    http_response_code(400);
    echo "No se encontraron las plantillas .docx."; exit;
}

$guiaContent = array_merge($guiaData, [
    'carrera' => $carrera, 'asignatura' => $materia, 'titulo' => $titulo,
    'unidad' => $unidad, 'nivel' => $nivel, 'docente' => $docente,
    'nro_practica' => $nroPractica, 'horas' => $horas,
    'elaborado' => $elaborado, 'revisado' => $revisado, 'aprobado' => $aprobado
]);
$anexoContent = array_merge($anexoData, [
    'carrera' => $carrera, 'asignatura' => $materia, 'titulo' => $titulo,
    'unidad' => $unidad, 'nivel' => $nivel, 'docente' => $docente,
    'nro_practica' => $nroPractica, 'horas' => $horas,
    'elaborado' => $elaborado, 'revisado' => $revisado, 'aprobado' => $aprobado
]);

$docGen = new DocumentGenerator();
$outputDir = PROJECT_ROOT . '/output';
if (!is_dir($outputDir)) mkdir($outputDir, 0777, true);

$files = [];

if (in_array($formato, ['word_guia', 'ambos'])) {
    $out = "$outputDir/Guia_{$baseName}.docx";
    $docGen->generateGuia($guiaSrc[0], $out, $guiaContent, $materia, $unidad, $titulo);
    $files['Guía (Word)'] = "download.php?file=Guia_{$baseName}.docx";
}
if (in_array($formato, ['pdf_guia', 'ambos'])) {
    $out = "$outputDir/Guia_{$baseName}.pdf";
    generarPDF(contenidoGuiaToHTML($guiaContent), $out);
    $files['Guía (PDF)'] = "download.php?file=Guia_{$baseName}.pdf";
}
if (in_array($formato, ['word_anexo', 'ambos'])) {
    $out = "$outputDir/Anexo_{$baseName}.docx";
    $docGen->generateAnexo($anexoSrc[0], $out, $anexoContent, $materia, $unidad, $titulo);
    $files['Anexo (Word)'] = "download.php?file=Anexo_{$baseName}.docx";
}
if (in_array($formato, ['pdf_anexo', 'ambos'])) {
    $out = "$outputDir/Anexo_{$baseName}.pdf";
    generarPDF(contenidoAnexoToHTML($anexoContent), $out);
    $files['Anexo (PDF)'] = "download.php?file=Anexo_{$baseName}.pdf";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Descargar documentos</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root { --primary: #2563eb; --primary-dark: #1d4ed8; --gray-400: #94a3b8; --gray-500: #64748b; --gray-800: #1e293b; --gray-900: #0f172a; --success: #10b981; --accent: #06b6d4; }
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, #0f172a, #1e293b, #0f172a);
    min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;
}
.card {
    background: rgba(255,255,255,.98); border-radius: 20px;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,.5);
    padding: 40px 36px; max-width: 480px; width: 100%;
}
.icon { font-size: 48px; text-align: center; margin-bottom: 12px; }
h1 { font-size: 22px; font-weight: 800; text-align: center; color: var(--gray-900); letter-spacing:-.5px; }
.subtitle { text-align: center; color: var(--gray-500); font-size: 14px; margin: 4px 0 24px; }
.dl-list { display: flex; flex-direction: column; gap: 10px; margin-bottom: 24px; }
.dl-item {
    display: flex; align-items: center; gap: 12px;
    padding: 14px 18px; background: #f8fafc; border-radius: 12px;
    border: 1px solid #e2e8f0; text-decoration: none; transition: all .2s;
}
.dl-item:hover { border-color: var(--primary); background: #fff; transform: translateY(-1px); }
.dl-item .name { flex: 1; font-weight: 600; font-size: 14px; color: var(--gray-800); }
.dl-item .arrow { color: var(--primary); font-size: 18px; }
.actions { display: flex; gap: 10px; }
.actions a {
    flex: 1; text-align: center; padding: 12px; border-radius: 10px;
    font-size: 14px; font-weight: 600; text-decoration: none; transition: all .2s;
}
.btn-back { background: #f1f5f9; color: #475569; }
.btn-back:hover { background: #e2e8f0; }
.btn-new { background: linear-gradient(135deg, var(--primary), #1d4ed8); color: #fff; box-shadow: 0 4px 14px rgba(37,99,235,.3); }
.btn-new:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(37,99,235,.4); }
</style>
</head>
<body>
<div class="card">
    <div class="icon">&#9989;</div>
    <h1>Documentos generados con tus cambios</h1>
    <p class="subtitle">Se aplicaron las ediciones que realizaste</p>
    <div class="dl-list">
        <?php foreach ($files as $label => $url): ?>
        <a href="<?= $url ?>" class="dl-item">
            <span class="name"><?= htmlspecialchars($label) ?></span>
            <span class="arrow">&#8595;</span>
        </a>
        <?php endforeach; ?>
    </div>
    <div class="actions">
        <a href="javascript:window.close()" class="btn-back">&#8592; Cerrar</a>
        <a href="index.php" class="btn-new">+ Nuevos documentos</a>
    </div>
</div>
<script>
<?php $firstFile = reset($files); ?>
// Auto-download first file
setTimeout(function() {
    window.location.href = '<?= $firstFile ?>';
}, 500);
</script>
</body>
</html>
