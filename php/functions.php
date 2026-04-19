<?php
/**
 * PageOver - Core Analysis Functions
 */

// ── Language colour map ──────────────────────────────────────────────────────
function langColor(string $lang): string {
    static $map = [
        'JavaScript'=>'#f7df1e','TypeScript'=>'#3178c6','Python'=>'#3572A5',
        'PHP'=>'#4F5D95','Java'=>'#b07219','C#'=>'#178600','C++'=>'#f34b7d',
        'C'=>'#555555','Ruby'=>'#701516','Go'=>'#00ADD8','Rust'=>'#dea584',
        'Swift'=>'#ffac45','Kotlin'=>'#A97BFF','Dart'=>'#00B4AB','HTML'=>'#e34c26',
        'CSS'=>'#563d7c','SCSS'=>'#c6538c','Shell'=>'#89e051','Vue'=>'#41b883',
        'Svelte'=>'#ff3e00','R'=>'#198CE7','Lua'=>'#000080','Perl'=>'#0298c3',
        'Haskell'=>'#5e5086','Elixir'=>'#6e4a7e','Scala'=>'#c22d40','SQL'=>'#e38c00',
        'Markdown'=>'#083fa1','JSON'=>'#cbcb41','Other'=>'#8b8b8b',
    ];
    return $map[$lang] ?? '#8b8b8b';
}

// ── Platform detection from file paths ──────────────────────────────────────
function detectPlatformsFromPaths(array $paths): array {
    $platforms = [];
    $pathStr   = implode("\n", $paths);
    $checks = [
        ['name'=>'React',        'color'=>'#61DAFB','patterns'=>['.jsx','.tsx','react-dom']],
        ['name'=>'Next.js',      'color'=>'#888888','patterns'=>['next.config.js','next.config.ts','pages/_app']],
        ['name'=>'Vue.js',       'color'=>'#41b883','patterns'=>['.vue','vue.config.js']],
        ['name'=>'Angular',      'color'=>'#DD0031','patterns'=>['angular.json','.component.ts','.module.ts']],
        ['name'=>'Laravel',      'color'=>'#FF2D20','patterns'=>['artisan','app/Http/Controllers']],
        ['name'=>'Symfony',      'color'=>'#4444AA','patterns'=>['symfony.lock','config/packages']],
        ['name'=>'Django',       'color'=>'#092E20','patterns'=>['manage.py','settings.py','wsgi.py']],
        ['name'=>'Flask',        'color'=>'#aaaaaa','patterns'=>['requirements.txt','app.py']],
        ['name'=>'FastAPI',      'color'=>'#009688','patterns'=>['fastapi','uvicorn','main.py']],
        ['name'=>'Node.js',      'color'=>'#339933','patterns'=>['package.json','node_modules','server.js']],
        ['name'=>'Express',      'color'=>'#aaaaaa','patterns'=>['express','app.listen']],
        ['name'=>'Spring Boot',  'color'=>'#6DB33F','patterns'=>['pom.xml','application.properties','Application.java']],
        ['name'=>'Docker',       'color'=>'#2496ED','patterns'=>['Dockerfile','docker-compose.yml','docker-compose.yaml']],
        ['name'=>'Kubernetes',   'color'=>'#326CE5','patterns'=>['k8s/','helm/','deployment.yaml']],
        ['name'=>'WordPress',    'color'=>'#21759B','patterns'=>['wp-content','wp-includes','functions.php']],
        ['name'=>'React Native', 'color'=>'#61DAFB','patterns'=>['android/','ios/','metro.config.js']],
        ['name'=>'Flutter',      'color'=>'#54C5F8','patterns'=>['pubspec.yaml','lib/main.dart']],
        ['name'=>'Nuxt.js',      'color'=>'#00DC82','patterns'=>['nuxt.config.js','nuxt.config.ts']],
        ['name'=>'SvelteKit',    'color'=>'#ff3e00','patterns'=>['svelte.config.js','.svelte']],
        ['name'=>'Tailwind CSS', 'color'=>'#38BDF8','patterns'=>['tailwind.config.js','tailwind.config.ts']],
        ['name'=>'Vite',         'color'=>'#646CFF','patterns'=>['vite.config.js','vite.config.ts']],
    ];
    foreach ($checks as $check) {
        foreach ($check['patterns'] as $p) {
            if (stripos($pathStr, $p) !== false) {
                $platforms[] = ['name' => $check['name'], 'color' => $check['color']];
                break;
            }
        }
    }
    return array_slice(array_unique($platforms, SORT_REGULAR), 0, 10);
}

// ── Language percentages from GitHub API ────────────────────────────────────
function computeLanguages(array $langsData): array {
    $total = array_sum($langsData);
    if ($total === 0) return [];
    arsort($langsData);
    $langs = [];
    foreach ($langsData as $lang => $bytes) {
        $langs[] = [
            'name'       => $lang,
            'percentage' => round(($bytes / $total) * 100, 1),
            'bytes'      => $bytes,
            'color'      => langColor($lang),
        ];
    }
    return $langs;
}

// ── Analyse a local extracted directory ────────────────────────────────────
function analyzeDirectory(string $dir): array {
    static $extMap = [
        'js'=>'JavaScript','jsx'=>'JavaScript','ts'=>'TypeScript','tsx'=>'TypeScript',
        'py'=>'Python','php'=>'PHP','java'=>'Java','cs'=>'C#','cpp'=>'C++','cc'=>'C++',
        'c'=>'C','h'=>'C','rb'=>'Ruby','go'=>'Go','rs'=>'Rust','swift'=>'Swift',
        'kt'=>'Kotlin','dart'=>'Dart','html'=>'HTML','htm'=>'HTML','css'=>'CSS',
        'scss'=>'SCSS','sass'=>'SCSS','vue'=>'Vue','svelte'=>'Svelte','sh'=>'Shell',
        'bash'=>'Shell','r'=>'R','lua'=>'Lua','pl'=>'Perl','hs'=>'Haskell',
        'ex'=>'Elixir','scala'=>'Scala','sql'=>'SQL','md'=>'Markdown','json'=>'JSON',
    ];
    $langBytes    = [];
    $files        = [];
    $codeSnippets = [];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iterator as $file) {
        if (!$file->isFile()) continue;
        $ext     = strtolower($file->getExtension());
        $size    = $file->getSize();
        $relPath = ltrim(str_replace(rtrim($dir, '/'), '', $file->getPathname()), '/\\');
        $files[] = ['path' => $relPath, 'size' => $size];
        if (isset($extMap[$ext])) {
            $lang = $extMap[$ext];
            $langBytes[$lang] = ($langBytes[$lang] ?? 0) + $size;
            if (in_array($ext, ['js','ts','jsx','tsx','py','php','java','cs','cpp','c','rb','go','rs'])
                && $size > 0 && $size < 60000 && count($codeSnippets) < 30) {
                $content = @file_get_contents($file->getPathname());
                if ($content !== false) {
                    $codeSnippets[] = [
                        'path'    => $relPath,
                        'content' => substr($content, 0, 3000),
                        'lang'    => $lang,
                    ];
                }
            }
        }
    }

    $languages  = computeLanguages($langBytes);
    $paths      = array_column($files, 'path');
    $platforms  = detectPlatformsFromPaths($paths);
    $uniqueness = computeUniqueness($files, $languages);
    $bugs       = detectBugs($codeSnippets, $paths);
    $aiUsage    = detectAiUsage($codeSnippets, $files, $languages);

    return [
        'languages'     => $languages,
        'platforms'     => $platforms,
        'file_count'    => count($files),
        'total_size'    => array_sum(array_column($files, 'size')),
        'uniqueness'    => $uniqueness,
        'availability'  => [],
        'description'   => '',
        'stars'         => 0,
        'forks'         => 0,
        'open_issues'   => 0,
        'license'       => 'N/A',
        'files_sample'  => array_slice($files, 0, 20),
        'bugs'          => $bugs,
        'ai_usage'      => $aiUsage,
        'overall_score' => computeOverallScore($languages, $uniqueness, count($files), $bugs),
    ];
}

// ── Bug detection (static analysis) ────────────────────────────────────────
function detectBugs(array $codeSnippets, array $allPaths): array {
    $patterns = [
        ['regex'=>'/eval\s*\(/i',                    'desc'=>'Use of eval() — potential remote code execution.',           'sev'=>'high'],
        ['regex'=>'/innerHTML\s*=/i',                'desc'=>'Direct innerHTML assignment — XSS vulnerability.',           'sev'=>'high'],
        ['regex'=>'/\$_GET\[|\$_POST\[|\$_REQUEST\[/', 'desc'=>'Unvalidated superglobal input — sanitize before use.',   'sev'=>'high'],
        ['regex'=>'/SELECT\s.+FROM\s.+WHERE\s.*\$/i', 'desc'=>'Possible SQL injection — use prepared statements.',        'sev'=>'high'],
        ['regex'=>'/password\s*=\s*["\'][^"\']{4,}/i','desc'=>'Hardcoded password detected in source code.',              'sev'=>'high'],
        ['regex'=>'/api[_\-]?key\s*[=:]\s*["\'][a-zA-Z0-9\-_]{10,}/i','desc'=>'Hardcoded API key in source code.',       'sev'=>'high'],
        ['regex'=>'/secret\s*[=:]\s*["\'][^"\']{6,}/i','desc'=>'Hardcoded secret value detected.',                       'sev'=>'high'],
        ['regex'=>'/md5\s*\(\s*\$password/i',        'desc'=>'MD5 used for password hashing — use bcrypt/argon2.',        'sev'=>'high'],
        ['regex'=>'/document\.write\s*\(/i',         'desc'=>'document.write() usage — performance and XSS risk.',        'sev'=>'medium'],
        ['regex'=>'/setTimeout\s*\(\s*["\']/',        'desc'=>'setTimeout with string argument — behaves like eval.',      'sev'=>'medium'],
        ['regex'=>'/catch\s*\([^)]*\)\s*\{\s*\}/',   'desc'=>'Empty catch block — errors silently swallowed.',            'sev'=>'medium'],
        ['regex'=>'/==\s*null(?!\s*\?)/',             'desc'=>'Use === null for strict null comparison.',                  'sev'=>'low'],
        ['regex'=>'/console\.log\s*\(/i',             'desc'=>'console.log() left in code — remove before production.',   'sev'=>'low'],
        ['regex'=>'/\bvar\s+[a-zA-Z_$]/',            'desc'=>'var declaration found — prefer const or let.',              'sev'=>'low'],
        ['regex'=>'/TODO|FIXME|HACK|XXX/',            'desc'=>'Unresolved TODO/FIXME/HACK comment found.',                'sev'=>'low'],
        ['regex'=>'/new\s+Array\s*\(/',               'desc'=>'new Array() usage — prefer array literal [] instead.',     'sev'=>'low'],
    ];

    $bugs = [];
    $seen = [];
    foreach ($codeSnippets as $snippet) {
        foreach ($patterns as $p) {
            if (isset($seen[$p['desc']])) continue;
            if (preg_match($p['regex'], $snippet['content'])) {
                $seen[$p['desc']] = true;
                $bugs[] = ['desc' => $p['desc'], 'severity' => $p['sev'], 'file' => $snippet['path']];
                if (count($bugs) >= 15) break 2;
            }
        }
    }

    // File-level checks
    $pathStr = implode("\n", $allPaths);
    if (stripos($pathStr, '.env') !== false && stripos($pathStr, '.gitignore') === false) {
        $bugs[] = ['desc' => '.env file present but no .gitignore found — secrets may be committed.', 'severity' => 'high', 'file' => '.env'];
    }
    if (stripos($pathStr, 'node_modules') !== false) {
        $bugs[] = ['desc' => 'node_modules directory committed — add to .gitignore.', 'severity' => 'low', 'file' => 'node_modules/'];
    }

    usort($bugs, function ($a, $b) {
        $order = ['high' => 0, 'medium' => 1, 'low' => 2];
        return ($order[$a['severity']] ?? 3) <=> ($order[$b['severity']] ?? 3);
    });
    return array_slice($bugs, 0, 12);
}

// ── Bug detection for GitHub (path-based heuristics, no source code) ────────
function detectBugsFromPaths(array $paths, array $languages): array {
    $bugs = [];
    $pathStr = implode("\n", $paths);

    if (stripos($pathStr, '.env') !== false && stripos($pathStr, '.gitignore') === false)
        $bugs[] = ['desc' => '.env file present without .gitignore — secrets may be exposed.', 'severity' => 'high', 'file' => '.env'];
    if (stripos($pathStr, 'node_modules') !== false)
        $bugs[] = ['desc' => 'node_modules committed to repository — should be in .gitignore.', 'severity' => 'low', 'file' => 'node_modules/'];
    if (stripos($pathStr, 'config.php') !== false || stripos($pathStr, 'database.php') !== false)
        $bugs[] = ['desc' => 'Database config file committed — verify credentials are not hardcoded.', 'severity' => 'medium', 'file' => 'config.php'];
    if (stripos($pathStr, 'wp-config.php') !== false)
        $bugs[] = ['desc' => 'WordPress wp-config.php found — ensure DB credentials are secured.', 'severity' => 'high', 'file' => 'wp-config.php'];

    // Check for missing standard files
    if (stripos($pathStr, 'readme') === false)
        $bugs[] = ['desc' => 'No README file found — documentation is missing.', 'severity' => 'low', 'file' => '/'];
    if (stripos($pathStr, 'license') === false && stripos($pathStr, 'licence') === false)
        $bugs[] = ['desc' => 'No LICENSE file found — consider adding one.', 'severity' => 'low', 'file' => '/'];

    usort($bugs, function ($a, $b) {
        $order = ['high' => 0, 'medium' => 1, 'low' => 2];
        return ($order[$a['severity']] ?? 3) <=> ($order[$b['severity']] ?? 3);
    });
    return $bugs;
}

// ── AI usage detection ───────────────────────────────────────────────────────
function detectAiUsage(array $codeSnippets, array $files, array $languages): array {
    $signals = [
        'uniform_style'   => 0,
        'comment_density' => 0,
        'naming_patterns' => 0,
        'boilerplate'     => 0,
        'complexity_dist' => 0,
    ];
    $n = count($codeSnippets);
    if ($n === 0) {
        return ['score' => 0, 'signals' => $signals, 'label' => 'Unknown — no source files scanned'];
    }

    $aiNamingPatterns = [
        '/\bhandleChange\b/', '/\bhandleSubmit\b/', '/\buseEffect\b/', '/\buseState\b/',
        '/\bconst\s+\w+\s*=\s*async\s*\(/',
        '/^\s*\/\/\s*(Initialize|Handle|Check if|Step \d+:|Update|Process|Validate)/m',
        '/\/\*\*\s*\n\s*\*\s*@(param|returns|description)/s',
        '/interface\s+I[A-Z]\w+\s*\{/',
        '/type\s+[A-Z]\w+\s*=\s*\{/',
        '/const\s+\[\w+,\s*set[A-Z]\w+\]\s*=\s*useState/',
    ];
    $boilerplatePatterns = [
        '/Hello,?\s*World/i', '/This is a sample/i', '/TODO:\s*implement/i',
        '/placeholder/i', '/Lorem ipsum/i', '/example\.com/i', '/your[_-]?api[_-]?key/i',
    ];

    $uniformCount = 0; $richCommentCount = 0; $aiNamingCount = 0; $boilerCount = 0;

    foreach ($codeSnippets as $s) {
        $c     = $s['content'];
        $lines = explode("\n", $c);
        $lc    = count($lines);
        if ($lc < 4) continue;

        // Comment density
        $commentLines = 0;
        foreach ($lines as $l) {
            if (preg_match('/^\s*(\/\/|#|\/\*|\*(?!\/)|<!--)/', $l)) $commentLines++;
        }
        if ($lc > 0 && ($commentLines / $lc) > 0.15) $richCommentCount++;

        // AI naming patterns
        foreach ($aiNamingPatterns as $p) {
            if (preg_match($p, $c)) { $aiNamingCount++; break; }
        }

        // Boilerplate
        foreach ($boilerplatePatterns as $p) {
            if (preg_match($p, $c)) { $boilerCount++; break; }
        }

        // Uniform indentation
        $goodIndent = 0; $totalIndent = 0;
        foreach ($lines as $l) {
            if (strlen(trim($l)) < 4) continue;
            preg_match('/^(\s+)/', $l, $m);
            if (isset($m[1])) {
                $len = strlen($m[1]);
                $totalIndent++;
                if ($len % 2 === 0 || $len % 4 === 0) $goodIndent++;
            }
        }
        if ($totalIndent > 4 && ($goodIndent / $totalIndent) > 0.88) $uniformCount++;
    }

    $signals['uniform_style']   = min(100, (int)round($uniformCount   / $n * 100));
    $signals['comment_density'] = min(100, (int)round($richCommentCount/ $n * 100));
    $signals['naming_patterns'] = min(100, (int)round($aiNamingCount  / $n * 90));
    $signals['boilerplate']     = min(100, (int)round($boilerCount    / $n * 70));
    $lc = count($languages);
    $signals['complexity_dist'] = $lc <= 1 ? 65 : ($lc <= 3 ? 40 : 20);

    $score = (int)round(
        $signals['uniform_style']   * 0.25 +
        $signals['comment_density'] * 0.20 +
        $signals['naming_patterns'] * 0.30 +
        $signals['boilerplate']     * 0.15 +
        $signals['complexity_dist'] * 0.10
    );
    $score = min(98, max(2, $score));

    if      ($score >= 70) $label = 'Likely AI-Generated';
    elseif  ($score >= 45) $label = 'Possibly AI-Assisted';
    elseif  ($score >= 20) $label = 'Mostly Human-Written';
    else                   $label = 'Human-Written';

    return ['score' => $score, 'signals' => $signals, 'label' => $label];
}

// ── AI usage estimation for GitHub (no source) ──────────────────────────────
function estimateAiUsageFromMeta(array $languages, int $fileCount): array {
    $signals = [
        'uniform_style'   => 0,
        'comment_density' => 0,
        'naming_patterns' => 0,
        'boilerplate'     => 0,
        'complexity_dist' => count($languages) <= 2 ? 55 : (count($languages) <= 4 ? 35 : 18),
    ];
    $score = (int)round(
        $signals['complexity_dist'] * 0.5 +
        ($fileCount > 50 ? 20 : 10)
    );
    $score = min(60, max(5, $score));
    return [
        'score'   => $score,
        'signals' => $signals,
        'label'   => $score >= 45 ? 'Possibly AI-Assisted' : 'Indeterminate (GitHub — source not scanned)',
    ];
}

// ── Uniqueness scoring ───────────────────────────────────────────────────────
function computeUniqueness(array $files, array $languages): int {
    $score   = 50;
    $langCnt = count($languages);
    if      ($langCnt >= 5) $score += 18;
    elseif  ($langCnt >= 3) $score += 10;
    elseif  ($langCnt >= 2) $score += 5;
    $fc = count($files);
    if      ($fc > 200) $score += 18;
    elseif  ($fc > 80)  $score += 12;
    elseif  ($fc > 20)  $score += 6;
    elseif  ($fc < 5)   $score -= 12;
    $paths = implode("\n", array_column($files, 'path'));
    if (stripos($paths, 'readme')    !== false) $score += 6;
    if (stripos($paths, 'test')      !== false || stripos($paths, 'spec') !== false) $score += 14;
    if (stripos($paths, 'docs/')     !== false) $score += 6;
    if (stripos($paths, '.github/')  !== false) $score += 5;
    if (stripos($paths, 'ci.yml')    !== false || stripos($paths, 'workflow') !== false) $score += 5;
    if (stripos($paths, 'license')   !== false) $score += 4;
    return min(100, max(8, $score));
}

// ── Overall score ────────────────────────────────────────────────────────────
function computeOverallScore(array $languages, int $uniqueness, int $fileCount, array $bugs = []): int {
    $langScore = min(25, count($languages) * 5);
    $uniqScore = (int)round($uniqueness * 0.35);
    $sizeScore = min(25, (int)round($fileCount / 8 * 2));
    $bugPenalty = min(20, count(array_filter($bugs, fn($b) => $b['severity'] === 'high')) * 5
                        + count(array_filter($bugs, fn($b) => $b['severity'] === 'medium')) * 2);
    return min(100, max(5, $langScore + $uniqScore + $sizeScore - $bugPenalty + 15));
}

// ── GitHub API helper ────────────────────────────────────────────────────────
function githubApiGet(string $url): ?array {
    $ch = curl_init($url);
    $headers = [
        'Accept: application/vnd.github.v3+json',
        'X-GitHub-Api-Version: 2022-11-28',
        'User-Agent: PageOver/1.0',
    ];
    // Optionally use a token from env for higher rate limits
    $token = getenv('GITHUB_TOKEN');
    if ($token) $headers[] = 'Authorization: Bearer ' . $token;

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    curl_close($ch);
    if ($err || !$resp) return null;
    $decoded = json_decode($resp, true);
    return is_array($decoded) ? $decoded : null;
}

// ── Availability check ───────────────────────────────────────────────────────
function checkAvailability(string $homepage, string $githubUrl): array {
    $checks = [['label' => 'GitHub Repository', 'url' => $githubUrl, 'status' => 'live']];
    if ($homepage && filter_var($homepage, FILTER_VALIDATE_URL)) {
        $checks[] = ['label' => 'Live Website', 'url' => $homepage, 'status' => urlIsAlive($homepage) ? 'live' : 'offline'];
    }
    // GitHub Pages guess
    if (preg_match('#github\.com/([^/]+)/([^/#?]+)#i', $githubUrl, $m)) {
        $pagesUrl = 'https://' . strtolower($m[1]) . '.github.io/' . strtolower(rtrim($m[2], '/'));
        $checks[] = ['label' => 'GitHub Pages', 'url' => $pagesUrl, 'status' => urlIsAlive($pagesUrl) ? 'live' : 'offline'];
    }
    return $checks;
}

function urlIsAlive(string $url): bool {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_NOBODY         => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'PageOver/1.0',
    ]);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code >= 200 && $code < 400;
}

// ── Utilities ────────────────────────────────────────────────────────────────
function formatBytes(int $bytes): string {
    if ($bytes < 1024)    return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
    return round($bytes / 1048576, 1) . ' MB';
}

function cleanupDir(string $dir): void {
    if (!is_dir($dir)) return;
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $item) {
        $item->isDir() ? rmdir($item->getRealPath()) : unlink($item->getRealPath());
    }
    rmdir($dir);
}
