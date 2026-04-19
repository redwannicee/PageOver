<?php
session_start();
require_once __DIR__ . '/php/db.php';
require_once __DIR__ . '/php/functions.php';
require_once __DIR__ . '/php/layout.php';

$id = trim($_GET['id'] ?? '');
if (!$id) { header('Location: index.php'); exit; }

$data = db_get($id);
if (!$data) { header('Location: index.php?error=notfound'); exit; }

$pname       = $data['project_name'] ?? 'Unnamed Project';
$languages   = $data['languages']    ?? [];
$platforms   = $data['platforms']    ?? [];
$availability= $data['availability'] ?? [];
$files       = $data['files_sample'] ?? [];
$uniqueness  = (int)($data['uniqueness']    ?? 0);
$overall     = (int)($data['overall_score'] ?? 0);
$bugs        = $data['bugs']      ?? [];
$aiUsage     = $data['ai_usage']  ?? [];
$aiScore     = (int)($aiUsage['score'] ?? 0);
$aiLabel     = $aiUsage['label']  ?? 'Unknown';
$aiSignals   = $aiUsage['signals']?? [];

$highBugs   = array_values(array_filter($bugs, fn($b) => ($b['severity']??'') === 'high'));
$medBugs    = array_values(array_filter($bugs, fn($b) => ($b['severity']??'') === 'medium'));
$lowBugs    = array_values(array_filter($bugs, fn($b) => ($b['severity']??'') === 'low'));

po_head(htmlspecialchars($pname) . ' — PageOver');
po_nav();
?>

<div class="result-wrap">

  <!-- Header -->
  <div class="result-header">
    <div class="result-title-block">
      <h1><?php echo htmlspecialchars($pname); ?></h1>
      <p>
        <?php if (($data['source'] ?? '') === 'github'): ?>
          <a href="<?php echo htmlspecialchars($data['source_info']??''); ?>" target="_blank" rel="noopener" style="color:var(--accent2);text-decoration:none;">
            <?php echo htmlspecialchars($data['source_info']??''); ?>
          </a>
        <?php else: ?>
          Uploaded file — <?php echo htmlspecialchars($data['source_info']??''); ?>
        <?php endif; ?>
      </p>
      <?php if (!empty($data['description'])): ?>
        <p style="color:var(--text2);font-size:0.9rem;margin-top:6px;"><?php echo htmlspecialchars($data['description']); ?></p>
      <?php endif; ?>
    </div>
    <div class="result-actions">
      <a href="compare.php?a=<?php echo urlencode($id); ?>" class="btn-outline" style="text-decoration:none;">Compare</a>
      <a href="php/export_pdf.php?id=<?php echo urlencode($id); ?>" class="btn-pdf" target="_blank" rel="noopener">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Download PDF
      </a>
    </div>
  </div>

  <!-- Score cards -->
  <div class="score-cards">
    <div class="score-card accent">
      <div class="score-label">Overall Score</div>
      <div class="score-value accent"><?php echo $overall; ?><small style="font-size:1rem">/100</small></div>
      <div class="score-sub">Quality index</div>
    </div>
    <div class="score-card teal">
      <div class="score-label">Uniqueness</div>
      <div class="score-value teal"><?php echo $uniqueness; ?>%</div>
      <div class="score-sub">Originality estimate</div>
    </div>
    <div class="score-card purple-c">
      <div class="score-label">AI Usage</div>
      <div class="score-value purple-v"><?php echo $aiScore; ?>%</div>
      <div class="score-sub"><?php echo htmlspecialchars($aiLabel); ?></div>
    </div>
    <div class="score-card red-c">
      <div class="score-label">Bugs Found</div>
      <div class="score-value red-v"><?php echo count($bugs); ?></div>
      <div class="score-sub"><?php echo count($highBugs); ?> critical</div>
    </div>
    <div class="score-card amber">
      <div class="score-label">Total Files</div>
      <div class="score-value amber"><?php echo number_format((int)($data['file_count']??0)); ?></div>
      <div class="score-sub"><?php echo formatBytes((int)($data['total_size']??0)); ?></div>
    </div>
    <div class="score-card coral">
      <div class="score-label">Languages</div>
      <div class="score-value coral"><?php echo count($languages); ?></div>
      <div class="score-sub">Detected</div>
    </div>
    <?php if (($data['source']??'') === 'github' && ($data['stars']??0) > 0): ?>
    <div class="score-card green-c">
      <div class="score-label">Stars</div>
      <div class="score-value green-v"><?php echo number_format((int)($data['stars']??0)); ?></div>
      <div class="score-sub"><?php echo number_format((int)($data['forks']??0)); ?> forks</div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Row 1: Language bars + Donut -->
  <div class="result-grid">
    <div class="panel">
      <div class="panel-title"><div class="dot-label" style="background:var(--accent2)"></div>Language Breakdown</div>
      <?php if (empty($languages)): ?>
        <p style="color:var(--text2);">No languages detected.</p>
      <?php else: ?>
        <?php foreach ($languages as $l): ?>
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
      <?php endif; ?>
    </div>
    <div class="panel">
      <div class="panel-title"><div class="dot-label" style="background:var(--teal)"></div>Language Distribution</div>
      <div class="chart-wrap"><canvas id="langChart"></canvas></div>
    </div>
  </div>

  <!-- Row 2: Bug Detection + AI Usage -->
  <div class="result-grid" style="margin-top:1.5rem;">
    <div class="panel">
      <div class="panel-title">
        <div class="dot-label" style="background:var(--red2)"></div>
        Bug &amp; Security Issues
        <div style="margin-left:auto;display:flex;gap:5px;font-weight:400;font-size:0.72rem;">
          <?php if (count($highBugs)>0): ?><span class="bug-sev high"><?php echo count($highBugs); ?> HIGH</span><?php endif; ?>
          <?php if (count($medBugs)>0):  ?><span class="bug-sev medium"><?php echo count($medBugs); ?> MED</span><?php endif; ?>
          <?php if (count($lowBugs)>0):  ?><span class="bug-sev low"><?php echo count($lowBugs); ?> LOW</span><?php endif; ?>
        </div>
      </div>
      <?php if (empty($bugs)): ?>
        <div style="text-align:center;padding:2rem 0;color:var(--green);">
          <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="display:block;margin:0 auto 8px;"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          <p style="font-weight:600;">No issues detected</p>
          <p style="font-size:0.82rem;color:var(--text2);margin-top:4px;">Clean code — no common vulnerability patterns found.</p>
        </div>
      <?php else: ?>
        <?php foreach ($bugs as $b): ?>
        <div class="bug-item">
          <span class="bug-sev <?php echo htmlspecialchars($b['severity']??'low'); ?>"><?php echo strtoupper($b['severity']??'LOW'); ?></span>
          <div>
            <div class="bug-desc"><?php echo htmlspecialchars($b['desc']??''); ?></div>
            <?php if (!empty($b['file'])): ?><div class="bug-file"><?php echo htmlspecialchars($b['file']); ?></div><?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="panel">
      <div class="panel-title"><div class="dot-label" style="background:var(--purple)"></div>AI Usage Detection</div>
      <div class="ai-meter-wrap">
        <div class="ai-score-big" style="color:<?php echo $aiScore>=70?'var(--red2)':($aiScore>=45?'var(--amber)':'var(--green)'); ?>"><?php echo $aiScore; ?>%</div>
        <div class="ai-label"><?php echo htmlspecialchars($aiLabel); ?></div>
      </div>
      <?php if (!empty($aiSignals)): ?>
      <div class="ai-signals">
        <?php
        $sigLabels = [
          'uniform_style'   => 'Uniform Code Style',
          'comment_density' => 'Comment Density',
          'naming_patterns' => 'AI Naming Patterns',
          'boilerplate'     => 'Boilerplate Code',
          'complexity_dist' => 'Complexity Distribution',
        ];
        foreach ($sigLabels as $key => $label):
          $val = (int)($aiSignals[$key] ?? 0);
        ?>
        <div class="ai-signal-row">
          <span class="ai-signal-name"><?php echo $label; ?></span>
          <div class="ai-signal-bar-track">
            <div class="ai-signal-bar" style="width:0%;" data-width="<?php echo $val; ?>"></div>
          </div>
          <span class="ai-signal-pct"><?php echo $val; ?>%</span>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <div style="margin-top:1rem;padding:10px 12px;border-radius:10px;background:var(--bg3);font-size:0.82rem;color:var(--text2);line-height:1.6;">
        <?php
        if      ($aiScore >= 70) echo '⚠️ Strong indicators of AI-generated code detected. High comment ratios and predictable naming patterns suggest heavy AI tool usage.';
        elseif  ($aiScore >= 45) echo '🔍 Some AI-assisted patterns found. The project likely uses AI tools for portions of the code, mixed with human-written sections.';
        elseif  ($aiScore >= 20) echo '✅ Mostly human-written code. Some common patterns detected but overall organic development style.';
        else                     echo '✅ Strongly human-written code. Natural, irregular code style detected throughout.';
        ?>
      </div>
    </div>
  </div>

  <!-- Row 3: Platforms + Uniqueness -->
  <div class="result-grid" style="margin-top:1.5rem;">
    <div class="panel">
      <div class="panel-title"><div class="dot-label" style="background:var(--amber)"></div>Detected Platforms &amp; Frameworks</div>
      <?php if (empty($platforms)): ?>
        <p style="color:var(--text2);font-size:0.9rem;">No specific frameworks detected.</p>
      <?php else: ?>
        <div class="platform-tags">
          <?php foreach ($platforms as $plat): ?>
          <div class="platform-tag">
            <div class="tag-dot" style="background:<?php echo htmlspecialchars($plat['color']); ?>"></div>
            <?php echo htmlspecialchars($plat['name']); ?>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <div class="panel" style="text-align:center;">
      <div class="panel-title" style="justify-content:center;"><div class="dot-label" style="background:var(--accent2)"></div>Uniqueness Score</div>
      <div class="chart-wrap"><canvas id="uniqueChart"></canvas></div>
      <p style="font-size:0.82rem;color:var(--text2);margin-top:0.5rem;">
        <?php
        if      ($uniqueness >= 80) echo 'Highly original — distinctive structure and practices.';
        elseif  ($uniqueness >= 60) echo 'Good originality with custom implementation patterns.';
        elseif  ($uniqueness >= 40) echo 'Moderate uniqueness — some common patterns detected.';
        else                        echo 'Similar structural patterns found in common repositories.';
        ?>
      </p>
    </div>
  </div>

  <!-- Row 4: Files + Availability -->
  <div class="result-grid" style="margin-top:1.5rem;">
    <div class="panel">
      <div class="panel-title"><div class="dot-label" style="background:var(--blue)"></div>File Structure <span style="color:var(--text3);font-size:0.8rem;font-weight:400;">(sample)</span></div>
      <?php if (empty($files)): ?>
        <p style="color:var(--text2);">No files to display.</p>
      <?php else: ?>
        <ul class="file-list">
          <?php foreach ($files as $f): ?>
          <li>
            <span class="fname"><?php echo htmlspecialchars($f['path']??''); ?></span>
            <span class="fsize"><?php echo formatBytes((int)($f['size']??0)); ?></span>
          </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
    <div class="panel">
      <div class="panel-title"><div class="dot-label" style="background:var(--green)"></div>Availability &amp; Deployment</div>
      <?php if (empty($availability)): ?>
        <p style="color:var(--text2);font-size:0.9rem;">Upload-based analysis — no URL availability checks performed.</p>
      <?php else: ?>
        <?php foreach ($availability as $avail): ?>
        <div class="availability-row">
          <span class="avail-status <?php echo htmlspecialchars($avail['status']??'unknown'); ?>"><?php echo strtoupper($avail['status']??'N/A'); ?></span>
          <div style="flex:1;min-width:0;">
            <div style="font-weight:500;font-size:0.88rem;"><?php echo htmlspecialchars($avail['label']??''); ?></div>
            <div style="font-size:0.75rem;color:var(--text3);font-family:var(--font-mono);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo htmlspecialchars($avail['url']??''); ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
      <?php if (($data['source']??'') === 'github' && !empty($data['license']) && $data['license'] !== 'N/A'): ?>
      <div style="margin-top:1.25rem;padding-top:1rem;border-top:1px solid var(--border);">
        <div class="score-label">License</div>
        <div style="margin-top:5px;font-family:var(--font-mono);font-size:0.9rem;color:var(--teal);"><?php echo htmlspecialchars($data['license']); ?></div>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- PDF Banner -->
  <div class="pdf-banner">
    <div>
      <div style="font-family:var(--font-display);font-weight:700;font-size:1rem;margin-bottom:4px;">Download Full Report</div>
      <div style="font-size:0.85rem;color:var(--text2);">Professional PDF with PageOver watermark — all scores, charts, bugs, and AI analysis included.</div>
    </div>
    <a href="php/export_pdf.php?id=<?php echo urlencode($id); ?>" class="btn-pdf" target="_blank" rel="noopener">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      Download PDF Report
    </a>
  </div>

</div><!-- /result-wrap -->

<?php po_footer(); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
const langData   = <?php echo json_encode($languages, JSON_HEX_TAG); ?>;
const uniqueness = <?php echo (int)$uniqueness; ?>;

window.addEventListener('load', () => {
  document.querySelectorAll('.lang-bar-fill').forEach(el => {
    requestAnimationFrame(() => { el.style.width = el.dataset.width + '%'; });
  });
  document.querySelectorAll('.ai-signal-bar').forEach(el => {
    setTimeout(() => { el.style.width = el.dataset.width + '%'; }, 300);
  });

  // Language donut
  if (langData.length > 0) {
    new Chart(document.getElementById('langChart'), {
      type: 'doughnut',
      data: {
        labels: langData.map(l => l.name),
        datasets: [{
          data: langData.map(l => l.percentage),
          backgroundColor: langData.map(l => l.color),
          borderColor: '#191926', borderWidth: 3, hoverOffset: 8,
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false, cutout: '65%',
        plugins: {
          legend: { position: 'right', labels: { color: '#8888aa', font: { family: 'DM Mono', size: 11 }, padding: 12, boxWidth: 12 }},
          tooltip: { callbacks: { label: c => ` ${c.label}: ${c.raw}%` }}
        }
      }
    });
  } else {
    const wrap = document.getElementById('langChart').parentElement;
    wrap.innerHTML = '<p style="text-align:center;color:var(--text2);padding:3rem 0;">No language data available.</p>';
  }

  // Uniqueness gauge
  new Chart(document.getElementById('uniqueChart'), {
    type: 'doughnut',
    data: {
      datasets: [{
        data: [uniqueness, 100 - uniqueness],
        backgroundColor: ['#4d6fff', '#141420'],
        borderWidth: 0, borderRadius: 4,
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      cutout: '72%', rotation: -90, circumference: 180,
      plugins: { legend: { display: false }, tooltip: { enabled: false }},
    },
    plugins: [{
      id: 'centerText',
      afterDraw(chart) {
        const { ctx, chartArea: { top, bottom, left, right } } = chart;
        const cx = (left + right) / 2, cy = (top + bottom) / 2 + 28;
        ctx.save();
        ctx.textAlign = 'center';
        ctx.fillStyle = '#f2f2f8';
        ctx.font = 'bold 34px Syne, sans-serif';
        ctx.fillText(uniqueness + '%', cx, cy);
        ctx.fillStyle = '#8888aa';
        ctx.font = '12px Instrument Sans, sans-serif';
        ctx.fillText('Uniqueness', cx, cy + 22);
        ctx.restore();
      }
    }]
  });
});
</script>
