<?php
/**
 * PageOver - Database Layer
 * Falls back to JSON file storage if MySQL is unavailable.
 * Configure DB credentials below for production.
 */

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'pageover');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

$pdo = null;
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
    $pdo->exec("CREATE TABLE IF NOT EXISTS analyses (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        project_name VARCHAR(255) NOT NULL,
        source       VARCHAR(50)  NOT NULL,
        source_info  TEXT,
        data         LONGTEXT     NOT NULL,
        created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (PDOException $e) {
    $pdo = null;
}

// ── Unified CRUD functions ───────────────────────────────────────────────────

function db_save(array $data): string {
    global $pdo;
    if ($pdo) {
        $stmt = $pdo->prepare(
            "INSERT INTO analyses (project_name, source, source_info, data) VALUES (?,?,?,?)"
        );
        $stmt->execute([
            $data['project_name'],
            $data['source'],
            $data['source_info'],
            json_encode($data, JSON_UNESCAPED_UNICODE)
        ]);
        return (string)$pdo->lastInsertId();
    }
    // JSON fallback
    $dir = __DIR__ . '/../data/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $id = (string)(time() . rand(100, 999));
    $data['id']         = $id;
    $data['created_at'] = date('Y-m-d H:i:s');
    file_put_contents($dir . $id . '.json', json_encode($data, JSON_UNESCAPED_UNICODE));
    return $id;
}

function db_get(string $id): ?array {
    global $pdo;
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT * FROM analyses WHERE id = ?");
        $stmt->execute([(int)$id]);
        $row = $stmt->fetch();
        if (!$row) return null;
        $d = json_decode($row['data'], true);
        $d['id']         = (string)$row['id'];
        $d['created_at'] = $row['created_at'];
        return $d;
    }
    $file = __DIR__ . '/../data/' . preg_replace('/[^0-9]/', '', $id) . '.json';
    if (!file_exists($file)) return null;
    return json_decode(file_get_contents($file), true);
}

function db_all(): array {
    global $pdo;
    if ($pdo) {
        $stmt = $pdo->query(
            "SELECT id, project_name, source, source_info, created_at, data
             FROM analyses ORDER BY created_at DESC LIMIT 100"
        );
        $results = [];
        while ($row = $stmt->fetch()) {
            $d = json_decode($row['data'], true);
            $d['id']         = (string)$row['id'];
            $d['created_at'] = $row['created_at'];
            $results[] = $d;
        }
        return $results;
    }
    $dir = __DIR__ . '/../data/';
    if (!is_dir($dir)) return [];
    $files = glob($dir . '*.json') ?: [];
    $results = [];
    foreach ($files as $f) {
        $d = json_decode(file_get_contents($f), true);
        if (is_array($d)) $results[] = $d;
    }
    usort($results, fn($a, $b) => strcmp((string)($b['id'] ?? ''), (string)($a['id'] ?? '')));
    return $results;
}
