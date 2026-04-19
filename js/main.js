/**
 * PageOver - Frontend JavaScript
 * Handles: tabs, drag-and-drop, file/GitHub analysis submission, loading overlay
 */

// ── Tab switching ──────────────────────────────────────────────────────────
document.querySelectorAll('.tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    tab.classList.add('active');
    const pane = document.getElementById('tab-' + tab.dataset.tab);
    if (pane) pane.classList.add('active');
  });
});

// ── Drag & Drop ────────────────────────────────────────────────────────────
const dropzone  = document.getElementById('dropzone');
const fileInput = document.getElementById('fileInput');
const fileInfo  = document.getElementById('fileInfo');
const fileName  = document.getElementById('fileName');

if (dropzone) {
  ['dragenter','dragover'].forEach(e => dropzone.addEventListener(e, ev => {
    ev.preventDefault(); dropzone.classList.add('dragover');
  }));
  ['dragleave','drop'].forEach(e => dropzone.addEventListener(e, ev => {
    dropzone.classList.remove('dragover');
  }));
  dropzone.addEventListener('drop', ev => {
    ev.preventDefault();
    const f = ev.dataTransfer?.files?.[0];
    if (f) handleFileSelect(f);
  });
  // Click on dropzone opens file picker (but not if clicking the label/button directly)
  dropzone.addEventListener('click', ev => {
    if (ev.target.closest('label') || ev.target.closest('input')) return;
    fileInput && fileInput.click();
  });
}

if (fileInput) {
  fileInput.addEventListener('change', () => {
    if (fileInput.files && fileInput.files[0]) handleFileSelect(fileInput.files[0]);
  });
}

function handleFileSelect(file) {
  if (!file.name.toLowerCase().endsWith('.zip')) {
    showError('Only .zip files are supported. Please zip your project first.');
    return;
  }
  if (file.size > 50 * 1024 * 1024) {
    showError('File is too large (max 50 MB). Please zip only essential project files.');
    return;
  }
  if (fileName)  fileName.textContent = file.name;
  if (fileInfo)  fileInfo.style.display = 'flex';
  if (dropzone)  dropzone.style.display = 'none';
  // Store reference for submit
  window._selectedFile = file;
}

// ── Analyze buttons ────────────────────────────────────────────────────────
const analyzeFileBtn = document.getElementById('analyzeFileBtn');
const analyzeGhBtn   = document.getElementById('analyzeGhBtn');

if (analyzeFileBtn) {
  analyzeFileBtn.addEventListener('click', () => {
    const file = window._selectedFile || (fileInput && fileInput.files && fileInput.files[0]);
    if (!file) { showError('No file selected.'); return; }
    showLoading();
    submitFile(file);
  });
}

if (analyzeGhBtn) {
  analyzeGhBtn.addEventListener('click', () => {
    const input = document.getElementById('githubUrl');
    const url   = input ? input.value.trim() : '';
    if (!url) { showError('Please enter a GitHub repository URL.'); return; }
    if (!url.match(/^https:\/\/github\.com\/[^/]+\/[^/]+/i)) {
      showError('URL must be in the format: https://github.com/owner/repo');
      return;
    }
    showLoading();
    submitGitHub(url);
  });
}

// Allow Enter key in GitHub input
const ghInput = document.getElementById('githubUrl');
if (ghInput) {
  ghInput.addEventListener('keydown', e => {
    if (e.key === 'Enter' && analyzeGhBtn) analyzeGhBtn.click();
  });
}

// ── Submit helpers ─────────────────────────────────────────────────────────
function submitFile(file) {
  const fd = new FormData();
  fd.append('project_file', file);
  fd.append('action', 'analyze_file');
  fetch('php/analyze.php', { method: 'POST', body: fd })
    .then(r => {
      if (!r.ok) throw new Error('Server returned ' + r.status);
      return r.json();
    })
    .then(handleAnalysisResponse)
    .catch(err => { hideLoading(); showError('Upload failed: ' + err.message); });
}

function submitGitHub(url) {
  const fd = new FormData();
  fd.append('github_url', url);
  fd.append('action', 'analyze_github');
  fetch('php/analyze.php', { method: 'POST', body: fd })
    .then(r => {
      if (!r.ok) throw new Error('Server returned ' + r.status);
      return r.json();
    })
    .then(handleAnalysisResponse)
    .catch(err => { hideLoading(); showError('Request failed: ' + err.message); });
}

function handleAnalysisResponse(data) {
  hideLoading();
  if (data.success && data.id) {
    window.location.href = 'result.php?id=' + encodeURIComponent(data.id);
  } else {
    showError(data.error || 'Analysis failed. Please try again.');
  }
}

// ── Loading overlay ────────────────────────────────────────────────────────
const loadingMessages = [
  'Reading file structure…',
  'Detecting languages…',
  'Scanning dependencies…',
  'Identifying frameworks…',
  'Running bug detection…',
  'Analysing AI patterns…',
  'Computing uniqueness score…',
  'Checking availability…',
  'Building your report…',
];

let _loadingInterval = null;

function showLoading() {
  const overlay  = document.getElementById('loadingOverlay');
  const msgEl    = document.getElementById('loadingMsg');
  const progEl   = document.getElementById('loadingProgress');
  if (!overlay) return;
  overlay.style.display = 'flex';
  let i = 0;
  const total = loadingMessages.length;
  if (msgEl)  msgEl.textContent = loadingMessages[0];
  if (progEl) progEl.style.width = '5%';
  _loadingInterval = setInterval(() => {
    i++;
    if (i < total) {
      if (msgEl)  msgEl.textContent = loadingMessages[i];
      if (progEl) progEl.style.width = Math.round((i / total) * 90) + '%';
    } else {
      clearInterval(_loadingInterval);
    }
  }, 900);
}

function hideLoading() {
  if (_loadingInterval) { clearInterval(_loadingInterval); _loadingInterval = null; }
  const overlay = document.getElementById('loadingOverlay');
  const progEl  = document.getElementById('loadingProgress');
  if (progEl)  progEl.style.width = '100%';
  setTimeout(() => {
    if (overlay) overlay.style.display = 'none';
    if (progEl)  progEl.style.width = '0%';
  }, 300);
}

// ── Error display ──────────────────────────────────────────────────────────
function showError(msg) {
  // Try inline error first
  const card = document.querySelector('.upload-card');
  if (card) {
    let errEl = document.getElementById('po-error');
    if (!errEl) {
      errEl = document.createElement('div');
      errEl.id = 'po-error';
      errEl.style.cssText = 'margin-top:1rem;padding:10px 14px;background:rgba(212,32,32,0.1);border:1px solid rgba(212,32,32,0.3);border-radius:10px;font-size:0.84rem;color:#ff4d4d;line-height:1.5;';
      card.appendChild(errEl);
    }
    errEl.textContent = '⚠ ' + msg;
    errEl.style.display = 'block';
    setTimeout(() => { if (errEl) errEl.style.display = 'none'; }, 6000);
  } else {
    alert(msg);
  }
}
