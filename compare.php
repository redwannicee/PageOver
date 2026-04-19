<?php
session_start();
require_once __DIR__ . '/php/db.php';
require_once __DIR__ . '/php/functions.php';
require_once __DIR__ . '/php/layout.php';

$idA      = trim($_GET['a'] ?? '');
$idB      = trim($_GET['b'] ?? '');
$dataA    = $idA ? db_get($idA) : null;
$dataB    = $idB ? db_get($idB) : null;
$allItems = db_all();

po_head('Compare Projects — PageOver');
po_nav();
?>

<div class="compare-wrap">
  <h1 class="compare-title">Compare Projects</h1>
  <p class="compare-sub">Select two analyzed projects to see them side by side — languages, scores, and metrics.</p>

  <!-- Selectors -->
  <div class="compare-inputs">
    <div class="compare-input-card" style="border-top:3px solid var(--accent2);">
      <h3>Project A</h3>
      <select class="gh-input" id="selectA" style="padding-left:1rem;" onchange="updateCompare()">
        <option value="">— Select a project —</option>
        <?php foreach ($allItems as $a): ?>
        <option value="<?php echo htmlspecialchars($a['id']); ?>" <?php echo ($idA === (string)$a['id']) ? 'selected' : ''; ?>>
          <?php echo htmlspecialchars($a['project_name'] ?? 'Unknown'); ?>
          <?php if (!empty($a['source'])): ?>(<?php echo htmlspecialchars($a['source']); ?>)<?php endif; ?>
        </option>
        <?php endforeach; ?>
      </select>
      <?php if (empty($allItems)): ?>
        <p style="color:var(--text2);font-size:0.85rem;margin-top:0.75rem;">No analyses yet. <a href="index.php" style="color:var(--accent2)">Analyze a project first →</a></p>
      <?php endif; ?>
    </div>
    <div class="compare-input-card" style="border-top:3px solid var(--coral);">
      <h3>Project B</h3>
      <select class="gh-input" id="selectB" style="padding-left:1rem;" onchange="updateCompare()">
        <option value="">— Select a project —</option>
        <?php foreach ($allItems as $a): ?>
        <option value="<?php echo htmlspecialchars($a['id']); ?>" <?php echo ($idB === (string)$a['id']) ? 'selected' : ''; ?>>
          <?php echo htmlspecialchars($a['project_name'] ?? 'Unknown'); ?>
          <?php if (!empty($a['source'])): ?>(<?php echo htmlspecialchars($a['source']); ?>)<?php endif; ?>
        </option>
        <?php endforeach; ?>
      </select>
      <div style="margin-top:1rem;">
        <p style="font-size:0.82rem;color:var(--text2);margin-bottom:0.5rem;">Or analyze a new GitHub repo for B:</p>
        <div style="display:flex;gap:8px;">
          <div style="position:relative;flex:1;">
            <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text3);" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.3 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C7.12 9.573 6.333 9.204 6.333 9.204c-1.089-.745.083-.73.083-.73 1.205.085 1.84 1.237 1.84 1.237 1.07 1.834 2.807 1.304 3.492.997.108-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23A11.509 11.509 0 0112 5.803c1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222 0 1.606-.015 2.898-.015 3.293 0 .319.216.694.825.576C20.565 21.796 24 17.3 24 12c0-6.63-5.37-12-12-12z"/></svg>
            <input type="url" id="newGhUrl" placeholder="https://github.com/owner/repo" class="gh-input" style="padding-left:2rem;">
          </div>
          <button class="btn-primary" onclick="analyzeNewForB()" id="analyzeForBBtn">Go</button>
        </div>
      </div>
    </div>
  </div>

  <?php if ($dataA && $dataB): ?>
  <!-- Results -->
  <div id="compareResults">
    <p class="section-tag" style="margin-bottom:1rem;">Comparison Results</p>

    <!-- Summary cards -->
    <div class="compare-result-grid" style="margin-bottom:1.5rem;">
      <div class="panel" style="border-top:3px solid var(--accent2);">
        <div class="panel-title" style="color:var(--accent2);"><?php echo htmlspecialchars($dataA['project_name']??'Project A'); ?></div>
        <div style="font-family:var(--font-mono);font-size:0.82rem;color:var(--text2);margin-bottom:1rem;">
          Score: <strong style="color:var(--accent2);"><?php echo (int)($dataA['overall_score']??0); ?>/100</strong> &nbsp;
          Unique: <strong style="color:var(--teal);"><?php echo (int)($dataA['uniqueness']??0); ?>%</strong> &nbsp;
          AI: <strong style="color:var(--purple);"><?php echo (int)($dataA['ai_usage']['score']??0); ?>%</strong>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:6px;">
          <?php foreach (($dataA['languages']??[]) as $l): ?>
          <span style="font-size:0.75rem;padding:3px 9px;border-radius:20px;background:rgba(255,255,255,0.06);border:1px solid var(--border2);color:var(--text2);">
            <span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:<?php echo htmlspecialchars($l['color']); ?>;vertical-align:middle;margin-right:4px;"></span>
            <?php echo htmlspecialchars($l['name']); ?> <?php echo $l['percentage']; ?>%
          </span>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="panel" style="border-top:3px solid var(--coral);">
        <div class="panel-title" style="color:var(--coral);"><?php echo htmlspecialchars($dataB['project_name']??'Project B'); ?></div>
        <div style="font-family:var(--font-mono);font-size:0.82rem;color:var(--text2);margin-bottom:1rem;">
          Score: <strong style="color:var(--coral);"><?php echo (int)($dataB['overall_score']??0); ?>/100</strong> &nbsp;
          Unique: <strong style="color:var(--teal);"><?php echo (int)($dataB['uniqueness']??0); ?>%</strong> &nbsp;
          AI: <strong style="color:var(--purple);"><?php echo (int)($dataB['ai_usage']['score']??0); ?>%</strong>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:6px;">
          <?php foreach (($dataB['languages']??[]) as $l): ?>
          <span style="font-size:0.75rem;padding:3px 9px;border-radius:20px;background:rgba(255,255,255,0.06);border:1px solid var(--border2);color:var(--text2);">
            <span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:<?php echo htmlspecialchars($l['color']); ?>;vertical-align:middle;margin-right:4px;"></span>
            <?php echo htmlspecialchars($l['name']); ?> <?php echo $l['percentage']; ?>%
          </span>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Bar chart -->
    <div class="panel" style="margin-bottom:1.5rem;">
      <div class="panel-title"><div class="dot-label" style="background:var(--accent2)"></div>Metric Comparison</div>
      <div class="chart-wrap" style="height:300px;"><canvas id="compareChart"></canvas></div>
    </div>

    <!-- Language bars side-by-side -->
    <div class="compare-result-grid" style="margin-bottom:1.5rem;">
      <div class="panel">
        <div class="panel-title" style="color:var(--accent2);"><?php echo htmlspecialchars($dataA['project_name']??'A'); ?> — Languages</div>
        <?php foreach (($dataA['languages']??[]) as $l): ?>
        <div class="lang-bar">
          <div class="lang-bar-header">
            <span class="lang-bar-name"><?php echo htmlspecialchars($l['name']); ?></span>
            <span class="lang-bar-pct"><?php echo $l['percentage']; ?>%</span>
          </div>
          <div class="lang-bar-track">
            <div class="lang-bar-fill" style="width:0%;background:<?php echo htmlspecialchars($l['color']); ?>;" data-width="<?php echo $l['percentage']; ?>"></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="panel">
        <div class="panel-title" style="color:var(--coral);"><?php echo htmlspecialchars($dataB['project_name']??'B'); ?> — Languages</div>
        <?php foreach (($dataB['languages']??[]) as $l): ?>
        <div class="lang-bar">
          <div class="lang-bar-header">
            <span class="lang-bar-name"><?php echo htmlspecialchars($l['name']); ?></span>
            <span class="lang-bar-pct"><?php echo $l['percentage']; ?>%</span>
          </div>
          <div class="lang-bar-track">
            <div class="lang-bar-fill" style="width:0%;background:<?php echo htmlspecialchars($l['color']); ?>;" data-width="<?php echo $l['percentage']; ?>"></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Verdict -->
    <?php
    $scoreA  = (int)($dataA['overall_score']??0);
    $scoreB  = (int)($dataB['overall_score']??0);
    $diff    = abs($scoreA - $scoreB);
    $winner  = $scoreA >= $scoreB ? ($dataA['project_name']??'Project A') : ($dataB['project_name']??'Project B');
    $winCol  = $scoreA >= $scoreB ? 'var(--accent2)' : 'var(--coral)';
    ?>
    <div class="panel" style="text-align:center;padding:2.5rem;">
      <div class="panel-title" style="justify-content:center;font-size:1.15rem;"><div class="dot-label" style="background:var(--amber)"></div>Verdict</div>
      <p style="font-family:var(--font-display);font-size:2.2rem;font-weight:800;color:<?php echo $winCol; ?>;margin:0.5rem 0;">
        <?php echo htmlspecialchars($winner); ?>
      </p>
      <p style="color:var(--text2);font-size:0.9rem;">
        <?php
        if ($diff === 0) echo 'Both projects scored equally — a very close match!';
        elseif ($diff < 8) echo "Edges ahead by <strong>{$diff} points</strong> — extremely close competition.";
        else echo "Wins with a <strong>{$diff}-point</strong> lead in overall score.";
        ?>
      </p>
      <div style="display:flex;justify-content:center;gap:2rem;margin-top:1.5rem;flex-wrap:wrap;">
        <a href="result.php?id=<?php echo urlencode($idA); ?>" class="btn-outline" style="text-decoration:none;">View <?php echo htmlspecialchars($dataA['project_name']??'A'); ?></a>
        <a href="result.php?id=<?php echo urlencode($idB); ?>" class="btn-outline" style="text-decoration:none;">View <?php echo htmlspecialchars($dataB['project_name']??'B'); ?></a>
      </div>
    </div>
  </div><!-- /compareResults -->

  <?php else: ?>
  <div class="empty-state">
    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M9 3H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-4"/><polyline points="16 3 22 3 22 9"/><line x1="10" y1="14" x2="22" y2="3"/></svg>
    <h3>Select two projects above to compare</h3>
    <p>Analyze projects first using the home page, then select them from the dropdowns.</p>
    <a href="index.php" class="btn-primary" style="text-decoration:none;display:inline-block;margin-top:1.25rem;">Analyze a project →</a>
  </div>
  <?php endif; ?>
</div>

<?php po_loading_overlay(); ?>
<?php po_footer(); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
function updateCompare() {
  const a = document.getElementById('selectA').value;
  const b = document.getElementById('selectB').value;
  let url = 'compare.php';
  if (a || b) url += '?' + (a ? 'a=' + encodeURIComponent(a) : '') + (a && b ? '&' : '') + (b ? 'b=' + encodeURIComponent(b) : '');
  window.location.href = url;
}

function analyzeNewForB() {
  const url = document.getElementById('newGhUrl').value.trim();
  if (!url) { alert('Please enter a GitHub URL'); return; }
  const btn = document.getElementById('analyzeForBBtn');
  btn.textContent = '...'; btn.disabled = true;
  const fd = new FormData();
  fd.append('github_url', url);
  fd.append('action', 'analyze_github');
  fetch('php/analyze.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
      if (d.success) {
        const a = document.getElementById('selectA').value;
        window.location.href = 'compare.php' + (a ? '?a=' + encodeURIComponent(a) + '&b=' + encodeURIComponent(d.id) : '?b=' + encodeURIComponent(d.id));
      } else {
        alert(d.error || 'Analysis failed.');
        btn.textContent = 'Go'; btn.disabled = false;
      }
    })
    .catch(() => { alert('Network error.'); btn.textContent = 'Go'; btn.disabled = false; });
}

window.addEventListener('load', () => {
  document.querySelectorAll('.lang-bar-fill').forEach(el => {
    requestAnimationFrame(() => { el.style.width = el.dataset.width + '%'; });
  });

  <?php if ($dataA && $dataB): ?>
  new Chart(document.getElementById('compareChart'), {
    type: 'bar',
    data: {
      labels: ['Overall Score', 'Uniqueness', 'AI Usage', 'Languages', 'Files (÷20)'],
      datasets: [
        {
          label: <?php echo json_encode($dataA['project_name']??'A'); ?>,
          data: [
            <?php echo (int)($dataA['overall_score']??0); ?>,
            <?php echo (int)($dataA['uniqueness']??0); ?>,
            <?php echo (int)($dataA['ai_usage']['score']??0); ?>,
            <?php echo count($dataA['languages']??[]); ?>,
            <?php echo min(100, (int)(($dataA['file_count']??0)/20)); ?>
          ],
          backgroundColor: 'rgba(77,111,255,0.7)', borderColor: '#4d6fff', borderWidth: 1, borderRadius: 6,
        },
        {
          label: <?php echo json_encode($dataB['project_name']??'B'); ?>,
          data: [
            <?php echo (int)($dataB['overall_score']??0); ?>,
            <?php echo (int)($dataB['uniqueness']??0); ?>,
            <?php echo (int)($dataB['ai_usage']['score']??0); ?>,
            <?php echo count($dataB['languages']??[]); ?>,
            <?php echo min(100, (int)(($dataB['file_count']??0)/20)); ?>
          ],
          backgroundColor: 'rgba(251,113,133,0.7)', borderColor: '#fb7185', borderWidth: 1, borderRadius: 6,
        }
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      scales: {
        x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#8888aa' }},
        y: { max: 100, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#8888aa' }}
      },
      plugins: {
        legend: { labels: { color: '#8888aa', font: { family: 'DM Mono', size: 11 }}}
      }
    }
  });
  <?php endif; ?>
});
</script>
