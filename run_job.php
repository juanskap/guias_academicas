<?php
set_time_limit(300);
ignore_user_abort(true);

if ($argc < 2) { file_put_contents('php://stderr', "Usage: php run_job.php <job_id>\n"); exit(1); }

$jobId = $argv[1];
define('PROJECT_ROOT', __DIR__);
define('TEMPLATES_DIR', PROJECT_ROOT . '/templates');
define('OUTPUT_DIR', PROJECT_ROOT . '/output');
define('JOBS_DIR', PROJECT_ROOT . '/jobs');

require_once PROJECT_ROOT . '/lib/OllamaClient.php';
require_once PROJECT_ROOT . '/lib/ContentGenerator.php';
require_once PROJECT_ROOT . '/lib/DocumentGenerator.php';
require_once PROJECT_ROOT . '/lib/PDFGenerator.php';

$jobFile = JOBS_DIR . '/' . basename($jobId) . '.json';
if (!file_exists($jobFile)) exit(1);

function setJob($data) {
    global $jobFile;
    file_put_contents($jobFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$job = json_decode(file_get_contents($jobFile), true);

$materia   = $job['params']['materia'];
$unidad    = $job['params']['unidad'];
$titulo    = $job['params']['titulo'];
$carrera   = $job['params']['carrera'] ?? 'Tecnología Superior en Desarrollo de Software';
$nivel     = $job['params']['nivel'] ?? 'IV';
$docente   = $job['params']['docente'] ?? 'Ing. Diana Ramírez Garófalo';
$nroPractica = $job['params']['nro_practica'] ?? '1';
$horas     = $job['params']['horas'] ?? '3';
$elaborado = $job['params']['elaborado'] ?? 'Ing. Diana Ramírez Garófalo';
$revisado  = $job['params']['revisado'] ?? 'Lcda. Diana Alegría Camino';
$aprobado  = $job['params']['aprobado'] ?? 'Ing. Maribel Fierro Montero';
$referencias = $job['params']['referencias'] ?? [];

$guiaSrc = glob(PROJECT_ROOT . '/Guía de Prácticas *.docx');
$anexoSrc = glob(PROJECT_ROOT . '/Anexo prácticas *.docx');
if (empty($guiaSrc) || empty($anexoSrc)) {
    $job['status'] = 'error';
    $job['error'] = 'No se encontraron las plantillas .docx';
    setJob($job); exit(1);
}

if (!is_dir(OUTPUT_DIR)) mkdir(OUTPUT_DIR, 0777, true);
if (!is_dir(TEMPLATES_DIR)) {
    mkdir(TEMPLATES_DIR, 0777, true);
    foreach (glob(PROJECT_ROOT . '/Guía de Prácticas *.docx') as $f) copy($f, TEMPLATES_DIR . '/' . basename($f));
    foreach (glob(PROJECT_ROOT . '/Anexo prácticas *.docx') as $f) copy($f, TEMPLATES_DIR . '/' . basename($f));
}

$baseName = preg_replace('/[^A-Za-z0-9_\-]/', '_', substr($titulo, 0, 40)) . '_' . date('Ymd_His');
$outGuiaDocx = OUTPUT_DIR . '/Guia_' . $baseName . '.docx';
$outAnexoDocx = OUTPUT_DIR . '/Anexo_' . $baseName . '.docx';
$outGuiaPdf  = OUTPUT_DIR . '/Guia_' . $baseName . '.pdf';
$outAnexoPdf = OUTPUT_DIR . '/Anexo_' . $baseName . '.pdf';

$client = new OllamaClient();
$gen = new ContentGenerator($client);
$docGen = new DocumentGenerator();

$guiaContent = [];
$anexoContent = [];

$steps_guia = ['fundamentos','objetivo_general','objetivos_especificos','preparacion_previa','procedimiento','evaluacion'];
$steps_anexo = ['introduccion','descripcion','metodologia','resultados','conclusiones','recomendaciones','bibliografia'];
$totalSteps = 17;

try {
    // ── Guía ──
    foreach ($steps_guia as $i => $section) {
        $stepNum = $i + 1;
        $pct = round(($stepNum / $totalSteps) * 100);

        $job['status'] = 'running';
        $job['step'] = $stepNum;
        $job['step_label'] = 'Guía: ' . ucfirst(str_replace('_', ' ', $section));
        $job['progress'] = $pct;
        setJob($job);

        $extra = ($section === 'fundamentos') ? $referencias : [];
        $texto = $gen->generateSection('guia', $section, $materia, $unidad, $titulo, $extra);
        $guiaContent[$section] = $texto;

        $job['guia_content'][$section] = $texto;
        setJob($job);
    }

    // ── Anexo objetivo_general ──
    $anexoContent['objetivo_general'] = $guiaContent['objetivo_general'] ?? '';
    $job['step'] = 7;
    $job['step_label'] = 'Anexo: Objetivo (desde Guía)';
    $job['progress'] = round((7 / $totalSteps) * 100);
    $job['anexo_content']['objetivo_general'] = $anexoContent['objetivo_general'];
    setJob($job);

    foreach ($steps_anexo as $i => $section) {
        $stepNum = $i + 8;
        $pct = round(($stepNum / $totalSteps) * 100);

        $job['status'] = 'running';
        $job['step'] = $stepNum;
        $job['step_label'] = 'Anexo: ' . ucfirst(str_replace('_', ' ', $section));
        $job['progress'] = $pct;
        setJob($job);

        $extra = ($section === 'bibliografia') ? $referencias : [];
        $texto = $gen->generateSection('anexo', $section, $materia, $unidad, $titulo, $extra, $guiaContent);
        $anexoContent[$section] = $texto;

        $job['anexo_content'][$section] = $texto;
        setJob($job);
    }

    // ── Word ──
    $job['step'] = 15;
    $job['step_label'] = 'Generando Word...';
    $job['progress'] = 90;
    setJob($job);

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

    // ── PDF ──
    $job['step'] = 16;
    $job['step_label'] = 'Generando PDF...';
    $job['progress'] = 97;
    setJob($job);

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

    // ── Final ──
    $job['status'] = 'completed';
    $job['step'] = 17;
    $job['step_label'] = 'Listo';
    $job['progress'] = 100;
    $job['output']['guia_docx'] = '/Guia_' . $baseName . '.docx';
    $job['output']['anexo_docx'] = '/Anexo_' . $baseName . '.docx';
    $job['output']['guia_pdf'] = '/Guia_' . $baseName . '.pdf';
    $job['output']['anexo_pdf'] = '/Anexo_' . $baseName . '.pdf';
    $job['output']['guia_docx_full'] = $outGuiaDocx;
    $job['output']['anexo_docx_full'] = $outAnexoDocx;
    $job['output']['guia_pdf_full'] = $outGuiaPdf;
    $job['output']['anexo_pdf_full'] = $outAnexoPdf;
    setJob($job);

} catch (Exception $e) {
    $job['status'] = 'error';
    $job['error'] = $e->getMessage();
    setJob($job);
    exit(1);
}
