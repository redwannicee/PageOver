<?php
/**
 * PageOver - Shared layout helpers
 */

function po_head(string $title = 'PageOver — Project Intelligence Platform'): void { ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($title); ?></title>
  <meta name="description" content="PageOver — Analyze any project. Detect languages, bugs, AI usage, and compare projects side by side.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Mono:wght@300;400;500&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo po_root(); ?>css/style.css">
</head>
<body>
<div class="noise"></div>
<?php } ?>

<?php
function po_nav(): void {
    $root = po_root(); ?>
<nav class="nav">
  <a href="<?php echo $root; ?>index.php">
    <img src="<?php echo $root; ?>logo.png" alt="PageOver" class="logo-img">
  </a>
  <div class="nav-links">
    <a href="<?php echo $root; ?>compare.php">Compare</a>
    <a href="<?php echo $root; ?>history.php">History</a>
    <a href="<?php echo $root; ?>index.php" class="nav-cta">+ Analyze</a>
  </div>
</nav>
<?php } ?>

<?php
function po_footer(): void {
    $root = po_root(); ?>
<footer class="footer">
  <div class="footer-inner">
    <div class="footer-logo-block">
      <img src="<?php echo $root; ?>logo.png" alt="PageOver" class="footer-logo">
    </div>
    <div class="footer-center">
      <p class="footer-tagline">Intelligent Project Analysis Platform</p>
      <p class="footer-links">
        <a href="<?php echo $root; ?>index.php">Home</a>
        <span>·</span>
        <a href="<?php echo $root; ?>compare.php">Compare</a>
        <span>·</span>
        <a href="<?php echo $root; ?>history.php">History</a>
      </p>
    </div>
    <div class="footer-right">
      <p class="footer-copy">&copy; <?php echo date('Y'); ?> PageOver</p>
      <p class="footer-dev">Developed by <strong>Md Redwan Rashid Nice</strong></p>
    </div>
  </div>
  <div class="footer-bar">
    <span>PageOver — Open source project analysis tool</span>
  </div>
</footer>
</body>
</html>
<?php } ?>

<?php
function po_root(): string {
    // Works whether files are in root or a subdirectory
    return '';
}

function po_loading_overlay(): void { ?>
<div class="loading-overlay" id="loadingOverlay" style="display:none;">
  <div class="loading-box">
    <div class="spinner"></div>
    <p class="loading-title">Analyzing project…</p>
    <p class="loading-sub" id="loadingMsg">Reading file structure</p>
    <div class="loading-bar"><div class="loading-progress" id="loadingProgress"></div></div>
  </div>
</div>
<?php }
