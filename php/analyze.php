<?php
/**
 * PageOver - Analysis Endpoint
 * POST: action=analyze_file (multipart) | action=analyze_github
 *
 * IMPORTANT: Output buffering + error suppression must come FIRST —
 * before any require/include — so PHP warnings never corrupt JSON.
 */

// ── 1. Capture ALL output so PHP errors never reach the client as HTML ───────
ob_start();

// ── 2. Turn off HTML error display — errors go to log only ──────────────────
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);                // still log everything, just don't display

// ── 3. Register a shutdown handler to catch fatal errors ────────────────────
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_clean();
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode([
            'success' => false,
            'error'   => 'Server error: ' . $err['message'] . ' in ' . basename($err['file']) . ':' . $err['line'],
        ]);
    }
});

// ── 4. Send JSON header now ──────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// ── 5. Helper: flush buffer then exit with JSON ──────────────────────────────
function json_exit(array $payload): void {
    ob_clean();                         // discard any stray output / PHP warnings
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

// ── 6. Load dependencies inside try/catch ───────────────────────────────────
try {
    session_start();
    require_once __DIR__ . '/db.php';
    require_once __DIR__ . '/functions.php';
} catch (Throwable $e) {
    json_exit(['success' => false, 'error' => 'Boot error: ' . $e->getMessage()]);
}

$action = trim($_POST['action'] ?? '');

// ── File upload analysis ─────────────────────────────────────────────────────
if ($action === 'analyze_file') {
    try {
        if (!isset($_FILES['project_file']) || $_FILES['project_file']['error'] !== UPLOAD_ERR_OK) {
            $code = $_FILES['project_file']['error'] ?? 'N/A';
            $msgs = [
                1 => 'File exceeds server upload limit (upload_max_filesize).',
                2 => 'File exceeds form upload limit (MAX_FILE_SIZE).',
                3 => 'File was only partially uploaded.',
                4 => 'No file was uploaded.',
                6 => 'Missing temp folder on server.',
                7 => 'Failed to write file to disk — check server permissions.',
            ];
            $errMsg = $msgs[$code] ?? 'Upload error (code ' . $code . ').';
            json_exit(['success' => false, 'error' => $errMsg]);
        }

        $file = $_FILES['project_file'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($ext !== 'zip') {
            json_exit(['success' => false, 'error' => 'Only .zip files are supported. Please zip your project folder first.']);
        }
        if ($file['size'] > 50 * 1024 * 1024) {
            json_exit(['success' => false, 'error' => 'File exceeds the 50 MB limit. Please zip only essential source files (exclude node_modules, vendor, etc.).']);
        }
        if ($file['size'] === 0) {
            json_exit(['success' => false, 'error' => 'Uploaded file is empty.']);
        }

        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                json_exit(['success' => false, 'error' => 'Cannot create uploads directory. Check server folder permissions.']);
            }
        }
        if (!is_writable($uploadDir)) {
            json_exit(['success' => false, 'error' => 'Uploads directory is not writable. Run: chmod 755 uploads/']);
        }

        $uid      = uniqid('proj_', true);
        $destPath = $uploadDir . $uid . '.zip';

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            json_exit(['success' => false, 'error' => 'Could not save uploaded file. Check upload directory permissions.']);
        }

        $extractDir = $uploadDir . $uid . '_ext/';
        if (!mkdir($extractDir, 0755, true)) {
            @unlink($destPath);
            json_exit(['success' => false, 'error' => 'Cannot create extraction directory.']);
        }

        if (!class_exists('ZipArchive')) {
            @unlink($destPath);
            json_exit(['success' => false, 'error' => 'PHP zip extension is not enabled on this server. Contact your host.']);
        }

        $zip = new ZipArchive();
        $res = $zip->open($destPath);
        if ($res !== true) {
            @unlink($destPath);
            $zipErrors = [
                ZipArchive::ER_NOZIP  => 'Not a valid ZIP file.',
                ZipArchive::ER_INCONS => 'ZIP file is inconsistent/corrupted.',
                ZipArchive::ER_MEMORY => 'Memory allocation failure.',
                ZipArchive::ER_OPEN   => 'Cannot open ZIP file.',
            ];
            $zipMsg = $zipErrors[$res] ?? 'Could not open ZIP (code: ' . $res . ').';
            json_exit(['success' => false, 'error' => $zipMsg]);
        }

        $zip->extractTo($extractDir);
        $zip->close();

        $analysis                  = analyzeDirectory($extractDir);
        $analysis['project_name']  = pathinfo($file['name'], PATHINFO_FILENAME);
        $analysis['source']        = 'file';
        $analysis['source_info']   = $file['name'];

        $id = db_save($analysis);

        cleanupDir($extractDir);
        @unlink($destPath);

        json_exit(['success' => true, 'id' => $id]);

    } catch (Throwable $e) {
        json_exit(['success' => false, 'error' => 'Analysis error: ' . $e->getMessage()]);
    }
}

// ── GitHub URL analysis ──────────────────────────────────────────────────────
if ($action === 'analyze_github') {
    try {
        $url = trim($_POST['github_url'] ?? '');
        if (empty($url)) {
            json_exit(['success' => false, 'error' => 'Please enter a GitHub repository URL.']);
        }

        // Accept URLs with optional trailing slash, .git suffix, or /tree/branch
        if (!preg_match('#^https://github\.com/([A-Za-z0-9_.\-]+)/([A-Za-z0-9_.\-]+?)(?:\.git|/.*)?$#i', $url, $m)) {
            json_exit(['success' => false, 'error' => 'Invalid GitHub URL. Expected format: https://github.com/owner/repo']);
        }

        $owner    = $m[1];
        $repo     = $m[2];
        $cleanUrl = "https://github.com/{$owner}/{$repo}";
        $apiBase  = "https://api.github.com/repos/{$owner}/{$repo}";

        if (!function_exists('curl_init')) {
            json_exit(['success' => false, 'error' => 'PHP cURL extension is not enabled on this server. GitHub analysis requires cURL.']);
        }

        $repoData = githubApiGet($apiBase);
        if ($repoData === null) {
            json_exit(['success' => false, 'error' => 'Could not reach GitHub API. Check server internet access or try again.']);
        }
        if (isset($repoData['message'])) {
            $msg = $repoData['message'];
            if (stripos($msg, 'Not Found') !== false) {
                json_exit(['success' => false, 'error' => 'Repository not found. Make sure the URL is correct and the repo is public.']);
            }
            if (stripos($msg, 'rate limit') !== false) {
                json_exit(['success' => false, 'error' => 'GitHub API rate limit exceeded. Wait a minute and try again, or set a GITHUB_TOKEN on the server.']);
            }
            json_exit(['success' => false, 'error' => 'GitHub API error: ' . $msg]);
        }

        $langsData = githubApiGet($apiBase . '/languages') ?: [];
        $treesData = githubApiGet($apiBase . '/git/trees/HEAD?recursive=1') ?: [];

        $files      = [];
        $totalBytes = 0;
        if (!empty($treesData['tree'])) {
            foreach ($treesData['tree'] as $item) {
                if (($item['type'] ?? '') === 'blob') {
                    $sz          = (int)($item['size'] ?? 0);
                    $files[]     = ['path' => $item['path'], 'size' => $sz];
                    $totalBytes += $sz;
                }
            }
        }

        $paths        = array_column($files, 'path');
        $languages    = computeLanguages($langsData);
        $platforms    = detectPlatformsFromPaths($paths);
        $uniqueness   = computeUniqueness($files, $languages);
        $availability = checkAvailability($repoData['homepage'] ?? '', $cleanUrl);
        $bugs         = detectBugsFromPaths($paths, $languages);
        $aiUsage      = estimateAiUsageFromMeta($languages, count($files));

        $analysis = [
            'project_name'  => $repo,
            'source'        => 'github',
            'source_info'   => $cleanUrl,
            'languages'     => $languages,
            'platforms'     => $platforms,
            'file_count'    => count($files),
            'total_size'    => $totalBytes,
            'uniqueness'    => $uniqueness,
            'availability'  => $availability,
            'description'   => $repoData['description'] ?? '',
            'stars'         => (int)($repoData['stargazers_count'] ?? 0),
            'forks'         => (int)($repoData['forks_count'] ?? 0),
            'open_issues'   => (int)($repoData['open_issues_count'] ?? 0),
            'license'       => $repoData['license']['spdx_id'] ?? 'N/A',
            'files_sample'  => array_slice($files, 0, 20),
            'bugs'          => $bugs,
            'ai_usage'      => $aiUsage,
            'overall_score' => computeOverallScore($languages, $uniqueness, count($files), $bugs),
        ];

        $id = db_save($analysis);
        json_exit(['success' => true, 'id' => $id]);

    } catch (Throwable $e) {
        json_exit(['success' => false, 'error' => 'Analysis error: ' . $e->getMessage()]);
    }
}

json_exit(['success' => false, 'error' => 'Unknown action. Expected: analyze_file or analyze_github.']);
