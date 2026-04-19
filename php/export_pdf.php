<?php
/**
 * PageOver - PDF Export Endpoint
 * GET: ?id=<analysis_id>
 */
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$id = trim($_GET['id'] ?? '');
if (!$id) {
    http_response_code(400);
    echo 'Missing id parameter.';
    exit;
}

$data = db_get($id);
if (!$data) {
    http_response_code(404);
    echo 'Analysis not found.';
    exit;
}

$logoPath    = realpath(__DIR__ . '/../logo.png');
$scriptPath  = realpath(__DIR__ . '/generate_pdf.py');
$tmpJson     = sys_get_temp_dir() . '/pageover_data_' . preg_replace('/[^a-z0-9]/i', '', $id) . '.json';
$tmpPdf      = sys_get_temp_dir() . '/pageover_out_'  . preg_replace('/[^a-z0-9]/i', '', $id) . '.pdf';

file_put_contents($tmpJson, json_encode($data, JSON_UNESCAPED_UNICODE));

// Prefer python3, fall back to python
$python = trim(shell_exec('which python3 2>/dev/null') ?: shell_exec('which python 2>/dev/null') ?: 'python3');

$cmd = escapeshellarg($python) . ' '
     . escapeshellarg($scriptPath) . ' '
     . escapeshellarg($tmpJson)    . ' '
     . escapeshellarg($tmpPdf)     . ' '
     . escapeshellarg((string)$logoPath);

exec($cmd . ' 2>&1', $output, $retCode);

@unlink($tmpJson);

if ($retCode !== 0 || !file_exists($tmpPdf)) {
    http_response_code(500);
    echo '<h2>PDF generation failed</h2><pre>' . htmlspecialchars(implode("\n", $output)) . '</pre>';
    echo '<p>Make sure Python 3 and reportlab are installed on the server: <code>pip3 install reportlab</code></p>';
    exit;
}

$name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $data['project_name'] ?? 'report');
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="PageOver_' . $name . '_Report.pdf"');
header('Content-Length: ' . filesize($tmpPdf));
header('Cache-Control: no-store');
readfile($tmpPdf);
@unlink($tmpPdf);
