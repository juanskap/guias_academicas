<?php
$doc = $_GET['doc'] ?? '';
if ($doc === 'guia') {
    $file = __DIR__ . '/Guía de Prácticas Tendencias A.docx';
    $name = 'Guia_de_Practicas_Plantilla.docx';
} elseif ($doc === 'anexo') {
    $file = __DIR__ . '/Anexo prácticas Tendencia.docx';
    $name = 'Anexo_Plantilla.docx';
} else {
    header('Location: preview.php');
    exit;
}

if (!file_exists($file)) {
    header('Location: preview.php?doc=' . urlencode($doc) . '&error=' . urlencode('Archivo no encontrado'));
    exit;
}

header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $name . '"');
header('Content-Length: ' . filesize($file));
readfile($file);
exit;
