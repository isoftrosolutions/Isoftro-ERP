/**
 * ia-students-v2.js - Improved Student Registration Flow
 * Powered by Nexus Forms design system.
 * FULL INTEGRATION VERSION (All Phases)
 */

/* ── NEXUS CORE COMPONENTS ─────────────────────────────────────── */

window.FormAccordion = class FormAccordion {
  constructor(containerId) {
    this.container = document.getElementById(containerId);
    if (!this.container) return;
    this.sections = this.container.querySelectorAll('.nexus-section');
    this.init();
  }
  init() {
    this.sections.forEach(section => {
      const header = section.querySelector('.nexus-section-header');
      if (header) { header.addEventListener('click', () => this.toggle(section)); }
    });
  }
  toggle(section) {
    const isExpanded = section.classList.contains('expanded');
    this.sections.forEach(s => s.classList.remove('expanded'));
    if (!isExpanded) section.classList.add('expanded');
  }
};

window.PhotoUpload = class PhotoUpload {
  constructor(containerId, options = {}) {
    this.container = document.getElementById(containerId);
    if (!this.container) return;
    this.previewId = options.previewId || 'photoPreview';
    this.inputId = options.inputId || 'stdPhotoInput';
    this.init();
  }
  init() {
    this.container.innerHTML = `
      <div class="nexus-photo-upload-wrapper">
        <div class="nexus-photo-upload">
          <i class="fa-solid fa-camera fa-2x" style="color: var(--nexus-text-muted); margin-bottom: 10px;"></i>
          <span style="font-size: 11px; color: var(--nexus-text-muted); text-align: center; padding: 0 10px;">Drag & Drop or Click</span>
          <img id="${this.previewId}" class="nexus-photo-preview" style="display: none;">
          <input type="file" id="${this.inputId}" name="profile_image" accept="image/*" style="display: none;">
        </div>
      </div>`;
    this.dropZone = this.container.querySelector('.nexus-photo-upload');
    this.fileInput = this.container.querySelector('input[type="file"]');
    this.preview = this.container.querySelector('img');
    this.dropZone.onclick = () => this.fileInput.click();
    this.fileInput.onchange = (e) => this.handleFiles(e.target.files);
  }
  handleFiles(files) {
    if (files && files[0]) {
      const reader = new FileReader();
      reader.onload = (e) => {
        this.preview.src = e.target.result;
        this.preview.style.display = 'block';
        this.dropZone.classList.add('has-image');
      };
      reader.readAsDataURL(files[0]);
    }
  }
};

window.NexusSearchSelect = class NexusSearchSelect {
  constructor(containerId, options = {}) {
    this.container = document.getElementById(containerId);
    if (!this.container) return;
    this.name = options.name || '';
    this.placeholder = options.placeholder || 'Search...';
    this.data = options.data || [];
    this.required = options.required || false;
    this.disabled = options.disabled || false;
    this.onSelect = options.onSelect || (() => {});
    this.init();
  }
  init() {
    this.container.innerHTML = `
      <div class="nexus-search-select">
        <input type="text" class="nexus-input" placeholder="${this.placeholder}" autocomplete="off" ${this.required ? 'required' : ''} ${this.disabled ? 'disabled' : ''}>
        <input type="hidden" name="${this.name}">
        <div class="nexus-search-dropdown"></div>
      </div>`;
    this.input = this.container.querySelector('input[type="text"]');
    this.hidden = this.container.querySelector('input[type="hidden"]');
    this.dropdown = this.container.querySelector('.nexus-search-dropdown');
    this.input.onfocus = () => this.filterData();
    this.input.oninput = () => this.filterData();
    document.addEventListener('click', (e) => { if (!this.container.contains(e.target)) this.dropdown.classList.remove('visible'); });
  }
  setData(newData) {
    this.data = newData;
    this.disabled = false; this.input.disabled = false; this.input.placeholder = this.placeholder;
  }
  filterData() {
    const val = this.input.value.toLowerCase();
    const matches = this.data.filter(i => i.label.toLowerCase().includes(val));
    this.dropdown.innerHTML = matches.map(i => `<div class="nexus-search-item" data-id="${i.id}" data-label="${i.label}">${i.label}</div>`).join('') || '<div class="nexus-search-item" style="opacity:0.5">No results found</div>';
    this.dropdown.classList.add('visible');
    this.dropdown.querySelectorAll('.nexus-search-item[data-id]').forEach(item => {
      item.onclick = () => {
        this.input.value = item.dataset.label;
        this.hidden.value = item.dataset.id;
        this.dropdown.classList.remove('visible');
        this.onSelect(item.dataset.id, item.dataset.label);
      };
    });
  }
};

window.FormAutosave = class FormAutosave {
  constructor(formId) {
    this.form = document.getElementById(formId);
    if (!this.form) return;
    this.key = `nexus_draft_${formId}`;
    this.init();
  }
  init() {
    this.restore();
    this.form.addEventListener('input', () => this.save());
  }
  save() {
    const data = Object.fromEntries(new FormData(this.form));
    localStorage.setItem(this.key, JSON.stringify({ data, time: Date.now() }));
  }
  restore() {
    const saved = localStorage.getItem(this.key);
    if (!saved) return;
    try {
      const d = JSON.parse(saved);
      if (Date.now() - d.time < 86400000) {
        Object.entries(d.data).forEach(([k, v]) => { if (this.form[k]) this.form[k].value = v; });
      }
    } catch(e) {}
  }
  clear() { localStorage.removeItem(this.key); }
};

/* ── MAIN MODULE LOGIC ─────────────────────────────────────────── */

window.renderAddStudentFormV2 = async () => {
  const mc = document.getElementById('mainContent');
  if (!mc) return;

  // Show loading state
  mc.innerHTML = `
    <div style="display:flex; align-items:center; justify-content:center; min-height:300px; flex-direction:column; gap:16px;">
      <i class="fas fa-spinner fa-spin" style="font-size:2.5rem; color:#00b894;"></i>
      <p style="color:#475569; font-weight:600;">Loading Admission Form...</p>
    </div>
  `;

  try {
    // Fetch the PHP partial from the front-desk admission-form view
    const res = await fetch(`${window.APP_URL}/dash/front-desk/admission-form?partial=true`, {
      credentials: 'same-origin'
    });

    if (!res.ok) throw new Error(`HTTP ${res.status}`);

    const html = await res.text();

    // Inject the fetched PHP-rendered HTML into the SPA main content
    mc.innerHTML = `
      <div class="bc">
        <a href="#" onclick="goNav('overview')">Dashboard</a>
        <span class="bc-sep">&rsaquo;</span>
        <a href="#" onclick="goNav('students')">Students</a>
        <span class="bc-sep">&rsaquo;</span>
        <span class="bc-cur">Add New Student</span>
      </div>
      ${html}
    `;

    // Execute inline scripts from the fetched PHP partial (same pattern as frontdesk.js)
    mc.querySelectorAll('script').forEach(s => {
      try { eval(s.innerHTML); } catch(ex) { console.warn('[renderAddStudentFormV2] Script eval error:', ex); }
    });

    // Patch the "View All Students" button for SPA navigation
    const viewBtn = mc.querySelector('.pg-acts .btn-p');
    if (viewBtn && typeof window.goNav === 'function') {
      viewBtn.href = '#';
      viewBtn.addEventListener('click', (e) => {
        e.preventDefault();
        window.goNav('students');
      });
    }

    // Patch the success modal "View Student Database" button for SPA navigation
    // Override the admission submit handler to reroute success redirect
    const origSubmitFn = window['handleAdmissionSubmit_adm'];
    if (origSubmitFn) {
      window['handleAdmissionSubmit_adm'] = async function(e) {
        // Temporarily patch REDIRECT action on success modal to use SPA nav
        window._admSpaRedirectPatch = true;
        return origSubmitFn.call(this, e);
      };
    }

  } catch (err) {
    console.error('[renderAddStudentFormV2] Failed to load admission form:', err);
    mc.innerHTML = `
      <div style="text-align:center; padding:60px 20px;">
        <i class="fas fa-exclamation-triangle" style="font-size:3rem; color:#ff7675; margin-bottom:20px;"></i>
        <h3 style="color:#1e293b; font-weight:800;">Failed to Load Form</h3>
        <p style="color:#64748b; margin-bottom:20px;">Could not load the admission form. Please try again.</p>
        <button class="btn bt" onclick="window.renderAddStudentFormV2()">
          <i class="fas fa-redo"></i> Retry
        </button>
        <button class="btn bs" onclick="goNav('students')" style="margin-left:10px;">
          Back to Students
        </button>
      </div>
    `;
  }
};

// Helper fetchers — direct inline fetch (no external dependency)
async function _loadCourses(comp) {
    const url = `${window.APP_URL}/api/frontdesk/courses`;
    console.log('[Nexus] Fetching courses from:', url);
    try {
        const res = await fetch(url);
        const r = await res.json();
        console.log('[Nexus] Courses response:', r);
        if (r.success && r.data) {
            comp.setData(r.data.map(c => ({ id: c.id, label: `${c.name}${c.code ? ' (' + c.code + ')' : ''}` })));
            console.log('[Nexus] Loaded', r.data.length, 'courses');
        } else {
            console.error('[Nexus] Course API returned:', r.message || 'no data');
        }
    } catch (e) {
        console.error('[Nexus] Course fetch failed:', e);
    }
}
async function _loadBatches(courseId, comp) {
    const url = `${window.APP_URL}/api/frontdesk/batches?course_id=${courseId}`;
    console.log('[Nexus] Fetching batches from:', url);
    try {
        const res = await fetch(url);
        const r = await res.json();
        console.log('[Nexus] Batches response:', r);
        if (r.success && r.data) {
            comp.setData(r.data.map(b => ({ id: b.id, label: `${b.name}${b.shift ? ' (' + b.shift + ')' : ''}` })));
            console.log('[Nexus] Loaded', r.data.length, 'batches');
        } else {
            console.error('[Nexus] Batch API returned:', r.message || 'no data');
        }
    } catch (e) {
        console.error('[Nexus] Batch fetch failed:', e);
    }
}
