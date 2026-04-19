<?php
session_start();
require_once __DIR__ . '/php/db.php';
require_once __DIR__ . '/php/functions.php';
require_once __DIR__ . '/php/layout.php';

$analyses = db_all();

po_head('History — PageOver');
po_nav();
?>

<div class="history-wrap">
  <h1 class="history-title">Analysis History</h1>

  <?php if (empty($analyses)): ?>
  <div class="empty-state">
    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M12 8v4l3 3M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <h3>No analyses yet</h3>
    <p>Analyze your first project to see results here.</p>
    <a href="index.php" class="btn-primary" style="text-decoration:none;display:inline-block;margin-top:1.25rem;">Analyze a project →</a>
  </div>

  <?php else: ?>

  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
    <p style="color:var(--text2);font-size:0.9rem;">
      <strong style="color:var(--text);"><?php echo count($analyses); ?></strong> project<?php echo count($analyses) !== 1 ? 's' : ''; ?> analyzed
    </p>
    <div style="position:relative;">
      <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text3);" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" id="searchInput" placeholder="Search projects…" class="gh-input" style="padding-left:2rem;max-width:240px;" oninput="filterHistory(this.value)">
    </div>
  </div>

  <div class="history-list" id="historyList">
    <?php foreach ($analyses as $a):
      $name    = $a['project_name'] ?? 'Unknown';
      $source  = $a['source']       ?? 'unknown';
      $info    = $a['source_info']  ?? '';
      $created = $a['created_at']   ?? '';
      $score   = (int)($a['overall_score'] ?? 0);
      $langs   = $a['languages']    ?? [];
      $bugs    = $a['bugs']         ?? [];
      $highBug = count(array_filter($bugs, fn($b) => ($b['severity']??'') === 'high'));
      $id      = $a['id']           ?? '';
    ?>
    <a href="result.php?id=<?php echo urlencode((string)$id); ?>" class="history-item" data-name="<?php echo strtolower(htmlspecialchars($name)); ?>">
      <div class="history-item-left">
        <h3><?php echo htmlspecialchars($name); ?></h3>
        <p>
          <span style="color:<?php echo $source==='github'?'var(--accent2)':'var(--teal)'; ?>">
            <?php echo $source === 'github' ? 'GitHub' : 'Upload'; ?>
          </span>
          &mdash; <?php echo htmlspecialchars(mb_strimwidth($info, 0, 55, '…')); ?>
          <?php if ($created): ?>&mdash; <?php echo date('M j, Y', strtotime($created)); ?><?php endif; ?>
        </p>
      </div>
      <div class="history-item-right">
        <?php if (!empty($langs)): ?>
        <span class="hist-badge" style="background:rgba(77,111,255,0.12);color:var(--accent2);">
          <?php echo htmlspecialchars($langs[0]['name']); ?>
        </span>
        <?php endif; ?>
        <?php if ($highBug > 0): ?>
        <span class="hist-badge" style="background:rgba(212,32,32,0.12);color:var(--red2);">
          <?php echo $highBug; ?> bug<?php echo $highBug>1?'s':''; ?>
        </span>
        <?php endif; ?>
        <span class="hist-badge" style="background:rgba(45,212,191,0.10);color:var(--teal);">
          <?php echo $score; ?>/100
        </span>
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--text3)" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
      </div>
    </a>
    <?php endforeach; ?>
  </div>

  <?php endif; ?>
</div>

<?php po_footer(); ?>

<script>
function filterHistory(val) {
  const v = val.toLowerCase();
  document.querySelectorAll('#historyList .history-item').forEach(item => {
    item.style.display = (item.dataset.name || '').includes(v) ? 'flex' : 'none';
  });
}
</script>
