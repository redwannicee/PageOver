<?php
session_start();
require_once __DIR__ . '/php/layout.php';
po_head('PageOver — Project Intelligence Platform');
po_nav();
?>

<section class="hero">
  <div class="hero-bg-grid"></div>
  <div class="hero-content">
    <div class="hero-logo-wrap">
      <img src="logo.png" alt="PageOver" class="hero-logo">
    </div>
    <div class="hero-badge"><span class="dot"></span> Intelligent Project Analysis</div>
    <h1 class="hero-title">
      <span class="blue">Judge</span> any project.<br>
      <span class="red">Instantly.</span>
    </h1>
    <p class="hero-sub">Upload a ZIP file or paste a GitHub URL. PageOver dissects your entire codebase — languages, platforms, bugs, AI usage, and more — visualized in seconds.</p>
  </div>

  <!-- Upload card -->
  <div class="upload-card">
    <div class="tabs">
      <button class="tab active" data-tab="upload">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        Upload ZIP
      </button>
      <button class="tab" data-tab="github">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.3 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.73.083-.73 1.205.085 1.84 1.237 1.84 1.237 1.07 1.834 2.807 1.304 3.492.997.108-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23A11.509 11.509 0 0112 5.803c1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222 0 1.606-.015 2.898-.015 3.293 0 .319.216.694.825.576C20.565 21.796 24 17.3 24 12c0-6.63-5.37-12-12-12z"/></svg>
        GitHub URL
      </button>
    </div>

    <!-- Upload tab -->
    <div class="tab-pane active" id="tab-upload">
      <div class="dropzone" id="dropzone">
        <div class="drop-icon">
          <svg width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><polyline points="9 15 12 12 15 15"/></svg>
        </div>
        <p class="drop-title">Drop your project ZIP here</p>
        <p class="drop-sub">or click to browse — max 50 MB</p>
        <label class="btn-outline" id="browseLabel">
          Browse Files
          <input type="file" id="fileInput" accept=".zip" hidden>
        </label>
      </div>
      <div class="file-info" id="fileInfo" style="display:none;">
        <div style="display:flex;align-items:center;gap:8px;min-width:0;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--teal)" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          <span class="file-name" id="fileName"></span>
        </div>
        <button class="btn-primary" id="analyzeFileBtn">Analyze →</button>
      </div>
    </div>

    <!-- GitHub tab -->
    <div class="tab-pane" id="tab-github">
      <div class="github-input-wrap">
        <svg class="gh-icon" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.3 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.73.083-.73 1.205.085 1.84 1.237 1.84 1.237 1.07 1.834 2.807 1.304 3.492.997.108-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23A11.509 11.509 0 0112 5.803c1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222 0 1.606-.015 2.898-.015 3.293 0 .319.216.694.825.576C20.565 21.796 24 17.3 24 12c0-6.63-5.37-12-12-12z"/></svg>
        <input type="url" id="githubUrl" placeholder="https://github.com/username/repository" class="gh-input" autocomplete="off" spellcheck="false">
      </div>
      <button class="btn-primary full" id="analyzeGhBtn">Analyze Repository →</button>
      <p style="font-size:0.78rem;color:var(--text3);margin-top:0.6rem;text-align:center;">Public repositories only. For higher rate limits, set <code style="color:var(--accent2)">GITHUB_TOKEN</code> on the server.</p>
    </div>
  </div><!-- /upload-card -->

  <div class="stats-row">
    <div class="stat"><span class="stat-num">40+</span><span class="stat-label">Languages</span></div>
    <div class="stat-divider"></div>
    <div class="stat"><span class="stat-num">Bug</span><span class="stat-label">Detection</span></div>
    <div class="stat-divider"></div>
    <div class="stat"><span class="stat-num">AI</span><span class="stat-label">Usage Score</span></div>
    <div class="stat-divider"></div>
    <div class="stat"><span class="stat-num">PDF</span><span class="stat-label">Export</span></div>
  </div>
</section><!-- /hero -->

<!-- Features -->
<section class="features">
  <div class="features-header">
    <p class="section-tag">What PageOver does</p>
    <h2>Everything you need to<br>judge a project.</h2>
  </div>
  <div class="features-grid">
    <div class="feat"><div class="feat-icon-wrap blue-i"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 20V10M12 20V4M6 20v-6"/></svg></div><h3>Language Breakdown</h3><p>Detects every language with byte counts and percentage charts — animated and interactive.</p></div>
    <div class="feat"><div class="feat-icon-wrap amber"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg></div><h3>Platform Detection</h3><p>Identifies frameworks and runtimes (React, Laravel, Django, Docker, and 20+ more) from config files.</p></div>
    <div class="feat"><div class="feat-icon-wrap red-i"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div><h3>Bug Detection</h3><p>Static analysis scans for XSS, SQL injection, hardcoded secrets, empty catch blocks, and 15+ more patterns.</p></div>
    <div class="feat"><div class="feat-icon-wrap purple"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg></div><h3>AI Usage Detection</h3><p>Analyses code patterns, comment density, and naming conventions to score how much AI was involved.</p></div>
    <div class="feat"><div class="feat-icon-wrap teal"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg></div><h3>Uniqueness Score</h3><p>Estimates originality using file structure analysis, test presence, documentation, and CI setup.</p></div>
    <div class="feat"><div class="feat-icon-wrap coral"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 3H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-4M16 3h5v5M10 14L21 3"/></svg></div><h3>Availability Check</h3><p>Pings GitHub Pages, live websites, and deployed URLs to see if the project is publicly accessible.</p></div>
    <div class="feat"><div class="feat-icon-wrap blue2"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 3H5a2 2 0 00-2 2v16a2 2 0 002 2h14a2 2 0 002-2V8z"/><polyline points="9 3 9 9 15 9"/></svg></div><h3>PDF Report</h3><p>Download a professional branded PDF report with PageOver logo watermark — ready to share instantly.</p></div>
    <div class="feat"><div class="feat-icon-wrap green"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="3" width="9" height="9"/><rect x="13" y="3" width="9" height="9"/><rect x="2" y="14" width="9" height="7"/><rect x="13" y="14" width="9" height="7"/></svg></div><h3>Side-by-Side Compare</h3><p>Compare two analyzed projects across all metrics with bar charts and an automatic winner verdict.</p></div>
  </div>
</section>

<?php po_loading_overlay(); ?>
<?php po_footer(); ?>
<script src="js/main.js"></script>
