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
    exit('Missing id parameter.');
}

$data = db_get($id);
if (!$data) {
    http_response_code(404);
    exit('Analysis not found.');
}

$logoPath   = __DIR__ . '/../logo.png';
$scriptPath = __DIR__ . '/generate_pdf.py';

if (!file_exists($scriptPath)) {
    http_response_code(500);
    exit('generate_pdf.py not found.');
}

if (!file_exists($logoPath)) {
    http_response_code(500);
    exit('logo.png not found.');
}

$safeId  = preg_replace('/[^a-z0-9]/i', '', $id);
$tmpJson = sys_get_temp_dir() . '/pageover_data_' . $safeId . '.json';
$tmpPdf  = sys_get_temp_dir() . '/pageover_out_' . $safeId . '.pdf';

file_put_contents($tmpJson, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

$python3 = trim(shell_exec('command -v python3 2>/dev/null'));
$python  = trim(shell_exec('command -v python 2>/dev/null'));

if ($python3 !== '') {
    $pythonBin = $python3;
} elseif ($python !== '') {
    $pythonBin = $python;
} else {
    @unlink($tmpJson);
    http_response_code(500);
    exit('<h2>PDF generation failed</h2><p>Python is not installed on the server.</p>');
}

$cmd = escapeshellarg($pythonBin) . ' '
     . escapeshellarg($scriptPath) . ' '
     . escapeshellarg($tmpJson) . ' '
     . escapeshellarg($tmpPdf) . ' '
     . escapeshellarg($logoPath) . ' 2>&1';

$output = [];
$retCode = 1;

exec($cmd, $output, $retCode);

@unlink($tmpJson);

if ($retCode !== 0 || !file_exists($tmpPdf)) {
    http_response_code(500);
    echo '<h2>PDF generation failed</h2>';
    echo '<pre>' . htmlspecialchars(implode("\n", $output)) . '</pre>';
    echo '<p>Python and reportlab must be installed on the server.</p>';
    exit;
}

$name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $data['project_name'] ?? 'report');

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="PageOver_' . $name . '_Report.pdf"');
header('Content-Length: ' . filesize($tmpPdf));
header('Cache-Control: no-store');

readfile($tmpPdf);
@unlink($tmpPdf);
exit;
