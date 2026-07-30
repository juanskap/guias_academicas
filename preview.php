<?php
// Extract document content and structure
function extraerDocumento($path) {
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        throw new Exception("No se puede abrir el documento");
    }
    $content = $zip->getFromName('word/document.xml');
    $zip->close();

    $dom = new DOMDocument();
    $dom->loadXML($content);
    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

    $estructura = [];

    $body = $xpath->query('/w:document/w:body')->item(0);
    foreach ($body->childNodes as $child) {
        if ($child->nodeType !== XML_ELEMENT_NODE) continue;
        $tag = $child->localName;
        
        if ($tag === 'p') {
            $text = '';
            $bold = false;
            $fontSize = '';
            foreach ($child->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'r') as $run) {
                $rPr = $run->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'rPr')->item(0);
                if ($rPr) {
                    $b = $rPr->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'b');
                    if ($b->length > 0) $bold = true;
                    $sz = $rPr->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'sz');
                    if ($sz->length > 0) $fontSize = $sz->item(0)->getAttribute('w:val');
                }
                foreach ($run->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 't') as $t) {
                    $text .= $t->textContent;
                }
            }
            $trimmed = trim($text);
            if ($trimmed !== '') {
                $estructura[] = ['tipo' => 'p', 'texto' => $trimmed, 'bold' => $bold, 'size' => $fontSize];
            } else {
                $estructura[] = ['tipo' => 'p', 'texto' => '', 'bold' => false, 'size' => ''];
            }
        } elseif ($tag === 'tbl') {
            $tabla = [];
            $rows = $child->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'tr');
            foreach ($rows as $row) {
                $fila = [];
                $cells = $row->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'tc');
                foreach ($cells as $cell) {
                    $cellText = '';
                    foreach ($cell->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 't') as $t) {
                        $cellText .= $t->textContent;
                    }
                    $fila[] = trim($cellText);
                }
                $tabla[] = $fila;
            }
            $estructura[] = ['tipo' => 'tbl', 'datos' => $tabla];
        }
    }
    return $estructura;
}

function renderPreview($estructura, $titulo = '') {
    $html = '';
    $w = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
    
    foreach ($estructura as $elem) {
        if ($elem['tipo'] === 'p') {
            $texto = $elem['texto'];
            $class = '';
            $style = '';

            // Detect headings and special content
            if (preg_match('/^GUÍA|^Informe de las prácticas|^REGISTRO DE ASISTENCIA/', $texto)) {
                $class = 'doc-title';
            } elseif (preg_match('/^(Datos Informativos|Fundamentos|Objetivo General|Objetivos específicos|Preparación previa|Normas de seguridad|Procedimiento a emplear|Materiales y equipos|Evaluación|Firmas|Introducción|Descripción|Metodología|Resultados|Conclusiones|Recomendaciones|Bibliografía|Anexos)/', $texto)) {
                $class = 'section-title';
            } elseif (preg_match('/^(Carrera:|Asignatura:|Título de la práctica:|Nivel Académico:|Docente:|Semana|Nro\.|Tiempo)/', $texto)) {
                $class = 'data-field';
            } elseif (preg_match('/^[X\.\,\…\-]+$/', $texto)) {
                $class = 'placeholder';
                $texto = '✦ contenido a generar ✦';
            } elseif (preg_match('/^[\.\,\-\—\…\s]+$/', $texto)) {
                $class = 'placeholder';
                $texto = '✦ contenido a generar ✦';
            } elseif ($texto === '') {
                $class = 'spacer';
            }

            if ($elem['bold'] && !$class) {
                $class = 'bold-text';
            }

            $html .= "<div class=\"elem $class\">" . htmlspecialchars($texto) . "</div>";
        } elseif ($elem['tipo'] === 'tbl') {
            $html .= '<table class="doc-table">';
            foreach ($elem['datos'] as $fila) {
                $html .= '<tr>';
                foreach ($fila as $celda) {
                    $cellClass = '';
                    $displayText = htmlspecialchars($celda);
                    // Highlight table placeholder cells
                    foreach (['Carrera:', 'Asignatura:', 'Nivel', 'Docente:', 'Título', 'No. de pr', 'No. de horas', 'Fecha:', 'Estudiantes:', 'Calificación'] as $patron) {
                        if (strpos($celda, $patron) !== false) {
                            $cellClass = 'label-cell';
                            break;
                        }
                    }
                    if (trim($celda) === '' || preg_match('/^[\.\,\…\-]+$/', trim($celda))) {
                        $cellClass = 'empty-cell';
                        if (trim($celda) === '' || preg_match('/^[\.\,\…\-]+$/', trim($celda))) {
                            $displayText = '✦ campo por llenar ✦';
                        }
                    }
                    $html .= "<td class=\"$cellClass\">$displayText</td>";
                }
                $html .= '</tr>';
            }
            $html .= '</table>';
        }
    }
    return $html;
}

$doc = $_GET['doc'] ?? 'guia';

$base = __DIR__;
if ($doc === 'guia') {
    $path = "$base/Guía de Prácticas Tendencias A.docx";
    $title = 'GUÍA DE PRÁCTICAS';
} else {
    $path = "$base/Anexo prácticas Tendencia.docx";
    $title = 'ANEXO - INFORME DE PRÁCTICAS';
}

$error = $_GET['error'] ?? '';
try {
    $estructura = extraerDocumento($path);
    $preview = renderPreview($estructura);
} catch (Exception $e) {
    $error = $e->getMessage();
    $preview = '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vista previa - <?= $title ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
    --primary: #2563eb;
    --primary-light: #dbeafe;
    --gray-50: #f8fafc;
    --gray-100: #f1f5f9;
    --gray-200: #e2e8f0;
    --gray-300: #cbd5e1;
    --gray-400: #94a3b8;
    --gray-500: #64748b;
    --gray-600: #475569;
    --gray-700: #334155;
    --gray-800: #1e293b;
    --gray-900: #0f172a;
    --highlight: #fef3c7;
    --highlight-border: #f59e0b;
}
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: linear-gradient(135deg, #0f172a, #1e293b, #0f172a);
    min-height: 100vh;
    padding: 0;
}
.topbar {
    background: rgba(15,23,42,.8);
    backdrop-filter: blur(12px);
    border-bottom: 1px solid rgba(255,255,255,.06);
    padding: 14px 32px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 100;
}
.topbar-brand {
    display: flex; align-items: center; gap: 12px;
    color: #fff; text-decoration: none;
}
.topbar-brand .logo {
    width: 38px; height: 38px;
    background: linear-gradient(135deg, var(--primary), #06b6d4);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
    box-shadow: 0 4px 12px rgba(37,99,235,.3);
}
.topbar-brand h1 { font-size: 17px; font-weight: 700; letter-spacing: -.3px; color:#fff; }
.topbar-brand span { color: var(--gray-400); font-weight: 400; }
.topbar-nav { display: flex; gap: 6px; align-items: center; }
.topbar-nav a {
    padding: 8px 16px; border-radius: 8px;
    font-size: 13px; font-weight: 500; text-decoration: none;
    color: var(--gray-400); transition: all .2s;
}
.topbar-nav a:hover { background: rgba(255,255,255,.08); color: #fff; }
.topbar-nav a.active { background: var(--primary); color: #fff; }
.topbar-actions { display: flex; gap: 8px; }
.topbar-actions a {
    padding: 8px 18px; border-radius: 8px;
    font-size: 13px; font-weight: 600; text-decoration: none;
    transition: all .2s;
}
.btn-download { background: #10b981; color: #fff; }
.btn-download:hover { background: #059669; }
.btn-back { background: rgba(255,255,255,.08); color: var(--gray-300); }
.btn-back:hover { background: rgba(255,255,255,.12); color: #fff; }
.page-wrap {
    max-width: 900px;
    margin: 28px auto;
    padding: 0 20px;
}
.preview-container {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,.5);
    padding: 48px 56px;
    border: 1px solid rgba(255,255,255,.1);
}
.doc-title {
    font-size: 20px; font-weight: 800; color: var(--gray-900);
    text-align: center; margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 2px solid var(--gray-200);
    letter-spacing: -.3px;
}
.section-title {
    font-size: 13px; font-weight: 700; color: var(--primary);
    text-transform: uppercase; letter-spacing: .5px;
    margin-top: 20px; margin-bottom: 6px;
    padding: 4px 0; border-bottom: 1px solid var(--gray-100);
}
.data-field { font-size: 13px; color: var(--gray-700); padding: 3px 0; font-weight: 500; }
.placeholder {
    font-size: 13px; color: var(--highlight-border);
    background: var(--highlight); padding: 6px 12px;
    border-radius: 6px; margin: 4px 0;
    font-style: italic; border-left: 3px solid var(--highlight-border);
    font-weight: 500;
}
.bold-text { font-weight: 700; color: var(--gray-800); font-size: 13px; padding: 2px 0; }
.spacer { height: 8px; }
.elem { font-size: 13px; line-height: 1.6; color: var(--gray-600); padding: 2px 0; }
.doc-table {
    width: 100%; border-collapse: collapse;
    margin: 12px 0; font-size: 12px;
    border: 1px solid var(--gray-200);
    border-radius: 8px; overflow: hidden;
}
.doc-table td {
    border: 1px solid var(--gray-200);
    padding: 8px 12px; color: var(--gray-600); vertical-align: top;
}
.label-cell { background: var(--gray-50); font-weight: 600; color: var(--gray-700) !important; white-space: nowrap; width: 1%; }
.empty-cell { color: var(--highlight-border) !important; background: var(--highlight); font-style: italic; font-weight: 500; }
.legend {
    max-width: 900px; margin: 16px auto 0;
    display: flex; gap: 20px; flex-wrap: wrap; padding: 0 20px;
}
.legend-item { display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--gray-400); }
.legend-item .swatch { width: 16px; height: 16px; border-radius: 4px; border: 1px solid rgba(255,255,255,.1); }
.legend-item .swatch.highlight { background: var(--highlight); border-color: var(--highlight-border); }
.legend-item .swatch.label { background: var(--gray-50); border-color: var(--gray-300); }
.error-msg {
    max-width: 900px; margin: 40px auto; text-align: center;
    color: #ef4444; background: rgba(239,68,68,.1);
    border: 1px solid rgba(239,68,68,.2); padding: 20px;
    border-radius: 12px; font-size: 14px;
}
@media (max-width:640px) {
    .preview-container { padding: 24px 16px; }
    .topbar { padding: 12px 16px; flex-wrap: wrap; gap: 8px; }
    .topbar-actions { flex-wrap: wrap; }
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
        <a href="preview.php?doc=guia" class="<?= $doc === 'guia' ? 'active' : '' ?>">&#128196; Gu&iacute;a</a>
        <a href="preview.php?doc=anexo" class="<?= $doc === 'anexo' ? 'active' : '' ?>">&#128203; Anexo</a>
    </div>
    <div class="topbar-actions">
        <a href="index.php" class="btn-back">&#8592; Volver</a>
        <a href="download_template.php?doc=<?= $doc ?>" class="btn-download">&#11015; Descargar plantilla</a>
    </div>
</div>
<div class="page-wrap">

    <?php if ($error): ?>
    <div class="error-msg">&#9888; Error: <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <div class="preview-container">
        <?= $preview ?>
    </div>
</div>
<div class="legend">
    <div class="legend-item"><span class="swatch highlight"></span> Campo generado autom&aacute;ticamente</div>
    <div class="legend-item"><span class="swatch label"></span> Campo informativo</div>
</div>
</body>
</html>
