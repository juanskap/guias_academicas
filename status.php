<?php
define('JOBS_DIR', __DIR__ . '/jobs');

$jobId = $_GET['job'] ?? '';
if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $jobId)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid job ID']);
    exit;
}

$jobFile = JOBS_DIR . '/' . $jobId . '.json';
if (!file_exists($jobFile)) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Job not found']);
    exit;
}

// If job is running, update step status based on step number
$job = json_decode(file_get_contents($jobFile), true);

header('Content-Type: application/json');
echo json_encode($job);
