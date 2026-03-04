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

  // Load CSS (Dynamic)
  if (!document.getElementById('nexus-css')) {
    const link = document.createElement('link');
    link.id = 'nexus-css'; link.rel = 'stylesheet';
    link.href = `${window.APP_URL}/public/assets/css/ia-form-components.css`;
    document.head.appendChild(link);
    const link2 = document.createElement('link');
    link2.rel = 'stylesheet';
    link2.href = `${window.APP_URL}/public/assets/css/ia-add-student-v2.css`;
    document.head.appendChild(link2);
  }

  mc.innerHTML = `
    <div class="nexus-form-container">
      <div class="bc">
        <a href="#" onclick="goNav('overview')">Dashboard</a> <span class="bc-sep">&rsaquo;</span> 
        <a href="#" onclick="goNav('students')">Students</a> <span class="bc-sep">&rsaquo;</span> 
        <span class="bc-cur">Full Admission Flow</span>
      </div>

      <div class="pg-head">
        <div class="pg-left">
          <div class="pg-ico" style="background: var(--nexus-primary-light); color: var(--nexus-primary);">
            <i class="fa-solid fa-user-plus"></i>
          </div>
          <div>
            <div class="pg-title">Nexus Admission System</div>
            <div class="pg-sub">Official Student Onboarding • All-in-One Interface</div>
          </div>
        </div>
      </div>

      <form id="nexusAddStudentForm">
        <div id="formAccordionContainer">
          
          <!-- 1. Identity -->
          <div class="nexus-section expanded">
            <div class="nexus-section-header">
              <div class="nexus-section-title">
                <div class="nexus-section-icon"><i class="fa-solid fa-id-card"></i></div>
                1. Personal Identity
              </div>
              <i class="fa-solid fa-chevron-down"></i>
            </div>
            <div class="nexus-section-body">
              <div class="nexus-grid nexus-grid-2">
                <div id="photoUploadContainer"></div>
                <div>
                  <div class="nexus-field">
                    <label class="nexus-field-label">Full Name <span class="required">*</span></label>
                    <input type="text" name="full_name" class="nexus-input" placeholder="e.g. John Doe" required>
                  </div>
                  <div class="nexus-grid nexus-grid-2">
                    <div class="nexus-field">
                      <label class="nexus-field-label">Email <span class="required">*</span></label>
                      <input type="email" name="email" class="nexus-input" placeholder="student@example.com" required>
                    </div>
                    <div class="nexus-field">
                      <label class="nexus-field-label">Phone <span class="required">*</span></label>
                      <input type="tel" name="contact_number" class="nexus-input" placeholder="98XXXXXXXX" required>
                    </div>
                  </div>
                  <div class="nexus-grid nexus-grid-2">
                    <div class="nexus-field">
                      <label class="nexus-field-label">Gender <span class="required">*</span></label>
                      <select name="gender" class="nexus-select" required>
                        <option value="">Select</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                      </select>
                    </div>
                    <div class="nexus-field">
                        <label class="nexus-field-label">Blood Group</label>
                        <select name="blood_group" class="nexus-select">
                            <option value="">Unknown</option>
                            <option value="A+">A+</option><option value="A-">A-</option>
                            <option value="B+">B+</option><option value="B-">B-</option>
                            <option value="AB+">AB+</option><option value="AB-">AB-</option>
                            <option value="O+">O+</option><option value="O-">O-</option>
                        </select>
                    </div>
                  </div>
                </div>
              </div>
              <div class="nexus-grid nexus-grid-3" style="margin-top: 15px;">
                  <div class="nexus-field">
                      <label class="nexus-field-label">DOB (AD) <span class="required">*</span></label>
                      <input type="date" name="dob_ad" id="inp_dob_ad" class="nexus-input" required>
                  </div>
                  <div class="nexus-field">
                      <label class="nexus-field-label">DOB (BS)</label>
                      <input type="text" name="dob_bs" id="inp_dob_bs" class="nexus-input" placeholder="YYYY-MM-DD">
                  </div>
                  <div class="nexus-field">
                      <label class="nexus-field-label">Citizenship / ID No.</label>
                      <input type="text" name="citizenship_no" class="nexus-input" placeholder="Optional">
                  </div>
              </div>
            </div>
          </div>

          <!-- 2. Academic -->
          <div class="nexus-section">
            <div class="nexus-section-header">
              <div class="nexus-section-title">
                <div class="nexus-section-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                2. Academic Placement
              </div>
              <i class="fa-solid fa-chevron-down"></i>
            </div>
            <div class="nexus-section-body">
              <div class="nexus-grid nexus-grid-2">
                <div class="nexus-field">
                  <label class="nexus-field-label">Target Course <span class="required">*</span></label>
                  <div id="nexus_course_select"></div>
                </div>
                <div class="nexus-field">
                  <label class="nexus-field-label">Assigned Batch <span class="required">*</span></label>
                  <div id="nexus_batch_select"></div>
                </div>
              </div>
            </div>
          </div>

          <!-- 3. Guardian -->
          <div class="nexus-section">
            <div class="nexus-section-header">
              <div class="nexus-section-title">
                <div class="nexus-section-icon"><i class="fa-solid fa-user-shield"></i></div>
                3. Guardian Details
              </div>
              <i class="fa-solid fa-chevron-down"></i>
            </div>
            <div class="nexus-section-body">
              <div class="nexus-grid nexus-grid-3">
                <div class="nexus-field">
                  <label class="nexus-field-label">Guardian Name <span class="required">*</span></label>
                  <input type="text" name="father_name" class="nexus-input" placeholder="Full name" required>
                </div>
                <div class="nexus-field">
                  <label class="nexus-field-label">Occupation</label>
                  <input type="text" name="father_occupation" class="nexus-input" placeholder="e.g. Business">
                </div>
                <div class="nexus-field">
                  <label class="nexus-field-label">Guardian Contact</label>
                  <input type="tel" name="father_contact" class="nexus-input" placeholder="98XXXXXXXX">
                </div>
              </div>
            </div>
          </div>

          <!-- 4. Address -->
          <div class="nexus-section">
            <div class="nexus-section-header">
              <div class="nexus-section-title">
                <div class="nexus-section-icon"><i class="fa-solid fa-location-dot"></i></div>
                4. Location Information
              </div>
              <i class="fa-solid fa-chevron-down"></i>
            </div>
            <div class="nexus-section-body">
               <div class="nexus-field-label">Permanent Address <span class="required">*</span></div>
               <div class="nexus-grid nexus-grid-2">
                 <div class="nexus-field"><div id="perm_province_select"></div></div>
                 <div class="nexus-field"><div id="perm_district_select"></div></div>
               </div>
               <input type="text" name="permanent_municipality" class="nexus-input" placeholder="Municipality / Rural Mun." style="margin: 10px 0;">
               
               <div style="margin: 20px 0; border-top: 1px solid var(--nexus-border); padding-top: 20px;">
                   <label class="nexus-switch">
                       <input type="checkbox" id="same_as_permanent">
                       <span class="nexus-switch-slider"></span>
                       Temporary Address is same as Permanent
                   </label>
               </div>

               <div id="temp_addr_group">
                   <div class="nexus-field-label">Temporary Address</div>
                   <div class="nexus-grid nexus-grid-2">
                     <div class="nexus-field"><div id="temp_province_select"></div></div>
                     <div class="nexus-field"><div id="temp_district_select"></div></div>
                   </div>
                   <input type="text" name="temporary_municipality" class="nexus-input" placeholder="Municipality / Rural Mun." style="margin: 10px 0;">
               </div>
            </div>
          </div>

          <!-- 5. Academic History -->
          <div class="nexus-section">
            <div class="nexus-section-header">
              <div class="nexus-section-title">
                <div class="nexus-section-icon"><i class="fa-solid fa-history"></i></div>
                5. Academic Background
              </div>
              <i class="fa-solid fa-chevron-down"></i>
            </div>
            <div class="nexus-section-body">
              <div class="nexus-table-wrapper">
                  <table class="nexus-table" id="academicTable">
                      <thead>
                          <tr>
                              <th>Level</th>
                              <th>Institution</th>
                              <th>Year</th>
                              <th>Grade/Pass %</th>
                              <th></th>
                          </tr>
                      </thead>
                      <tbody></tbody>
                  </table>
              </div>
              <button type="button" class="nexus-btn-text" id="addAcademicRow">
                  <i class="fa-solid fa-plus"></i> Add Qualification
              </button>
            </div>
          </div>

        </div>

        <div style="margin-top: 30px; display: flex; gap: 15px; justify-content: flex-end;">
          <button type="button" class="btn bs" onclick="goNav('students')">Cancel Admission</button>
          <button type="submit" class="btn bt" style="background: var(--nexus-primary); border-color: var(--nexus-primary); color: white;">
            Complete Enrollment
          </button>
        </div>
      </form>
    </div>
  `;

  // --- Initializations ---
  new FormAccordion('formAccordionContainer');
  new PhotoUpload('photoUploadContainer');
  const autosave = new FormAutosave('nexusAddStudentForm');

  // Search Selects — Courses & Batches
  const cSel = new NexusSearchSelect('nexus_course_select', { name: 'course_id', placeholder: 'Search course...', required: true, onSelect: (id) => _loadBatches(id, bSel) });
  const bSel = new NexusSearchSelect('nexus_batch_select', { name: 'batch_id', placeholder: 'Select course first...', required: true, disabled: true });
  _loadCourses(cSel);

  // Addresses — separate containers for province and district
  const provinceData = (window.nepalData?.provinces || []).map(p => ({id: p.name, label: p.name}));
  const pProv = new NexusSearchSelect('perm_province_select', { name: 'permanent_province', placeholder: 'Province...', required: true, 
    data: provinceData,
    onSelect: (v) => { const districts = (window.getDistrictsByProvinceName ? window.getDistrictsByProvinceName(v) : []).map(d => ({id: d, label: d})); pDist.setData(districts); }
  });
  const pDist = new NexusSearchSelect('perm_district_select', { name: 'permanent_district', placeholder: 'Select province first...', required: true, disabled: true });

  const tProv = new NexusSearchSelect('temp_province_select', { name: 'temporary_province', placeholder: 'Province...', 
    data: provinceData,
    onSelect: (v) => { const districts = (window.getDistrictsByProvinceName ? window.getDistrictsByProvinceName(v) : []).map(d => ({id: d, label: d})); tDist.setData(districts); }
  });
  const tDist = new NexusSearchSelect('temp_district_select', { name: 'temporary_district', placeholder: 'Select province first...', disabled: true });

  // DOB Sync
  document.getElementById('inp_dob_ad').onchange = async (e) => {
      const res = await fetch(`${window.APP_URL}/api/admin/date-convert?date=${e.target.value}&type=ad-to-bs`);
      const r = await res.json();
      if (r.success) document.getElementById('inp_dob_bs').value = r.date;
  };

  // Same as Permanent
  document.getElementById('same_as_permanent').onchange = (e) => {
      document.getElementById('temp_addr_group').style.display = e.target.checked ? 'none' : 'block';
  };

  // Academic Table
  const addRow = () => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
          <td><input type="text" name="qual_level[]" class="nexus-input" placeholder="SLC/SEE"></td>
          <td><input type="text" name="qual_school[]" class="nexus-input" placeholder="School Name"></td>
          <td><input type="number" name="qual_year[]" class="nexus-input" placeholder="2079"></td>
          <td><input type="text" name="qual_grade[]" class="nexus-input" placeholder="3.6 GPA"></td>
          <td><button type="button" class="nexus-btn-icon" onclick="this.closest('tr').remove()"><i class="fa-solid fa-trash"></i></button></td>
      `;
      document.querySelector('#academicTable tbody').appendChild(tr);
  };
  document.getElementById('addAcademicRow').onclick = addRow;
  addRow(); // Initial row

  // --- Final submission ---
  const form = document.getElementById('nexusAddStudentForm');
  form.onsubmit = async (e) => {
    e.preventDefault();
    const btn = form.querySelector('button[type="submit"]');
    btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Processing...';

    const formData = new FormData(form);
    
    // Process Address JSONs
    const getAddr = (pref) => ({
        province: formData.get(`${pref}_province`) || '',
        district: formData.get(`${pref}_district`) || '',
        municipality: formData.get(`${pref}_municipality`) || '',
        ward: ''
    });
    
    const perm = getAddr('permanent');
    formData.set('permanent_address', JSON.stringify(perm));
    
    if (document.getElementById('same_as_permanent').checked) {
        formData.set('temporary_address', JSON.stringify(perm));
    } else {
        formData.set('temporary_address', JSON.stringify(getAddr('temporary')));
    }

    // Process Academic Qualifications JSON
    const quals = [];
    const levels = formData.getAll('qual_level[]');
    const schools = formData.getAll('qual_school[]');
    const years = formData.getAll('qual_year[]');
    const grades = formData.getAll('qual_grade[]');
    for (let i=0; i<levels.length; i++) {
        if (levels[i] && schools[i]) quals.push({ 
            level: levels[i], 
            school: schools[i], 
            year: years[i] || '', 
            percentage: grades[i] || '' 
        });
    }
    formData.set('academic_qualifications', JSON.stringify(quals));
    formData.set('registration_status', 'fully_registered');

    try {
        const res = await fetch(`${window.APP_URL}/api/admin/students`, { method: 'POST', body: formData });
        const result = await res.json();
        if (result.success) {
            autosave.clear();
            Swal.fire('Enrollment Complete!', 'Student record has been created.', 'success').then(() => goNav('students'));
        } else throw new Error(result.message);
    } catch (err) {
        Swal.fire('Enrollment Error', err.message, 'error');
    } finally {
        btn.disabled = false; btn.innerHTML = 'Complete Enrollment';
    }
  };
};

// Helper fetchers — direct inline fetch (no external dependency)
async function _loadCourses(comp) {
    const url = `${window.APP_URL}/api/admin/courses`;
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
    const url = `${window.APP_URL}/api/admin/batches?course_id=${courseId}`;
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
