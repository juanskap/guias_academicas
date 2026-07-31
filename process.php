<?php
set_time_limit(30);
define('PROJECT_ROOT', __DIR__);
define('JOBS_DIR', PROJECT_ROOT . '/jobs');
if (!is_dir(JOBS_DIR)) mkdir(JOBS_DIR, 0777, true);

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
    header('Location: index.php?error=Todos los campos obligatorios');
    exit;
}

$referencias = json_decode($referenciasJson, true) ?? [];

// ─── Create job ───
$jobId = bin2hex(random_bytes(8));
$job = [
    'id' => $jobId,
    'status' => 'pending',
    'progress' => 0,
    'step' => 0,
    'step_label' => 'Iniciando...',
    'error' => '',
    'params' => [
        'materia' => $materia,
        'unidad' => $unidad,
        'titulo' => $titulo,
        'carrera' => $carrera,
        'nivel' => $nivel,
        'docente' => $docente,
        'nro_practica' => $nroPractica,
        'horas' => $horas,
        'elaborado' => $elaborado,
        'revisado' => $revisado,
        'aprobado' => $aprobado,
        'referencias' => $referencias,
    ],
    'guia_content' => [],
    'anexo_content' => [],
    'output' => [],
    'created_at' => date('Y-m-d H:i:s'),
];

$jobFile = JOBS_DIR . '/' . $jobId . '.json';
file_put_contents($jobFile, json_encode($job, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// ─── Start background process ───
$phpPath = 'C:\xampp\php\php.exe';
$script = PROJECT_ROOT . '\run_job.php';
$cmd = sprintf('"%s" "%s" %s', $phpPath, $script, $jobId);
pclose(popen('start /B "" ' . $cmd, 'r'));

// ─── Redirect to monitor ───
header('Location: monitor.php?job=' . $jobId);
exit;
