/**
 * SmartAddressSelector - Searchable Nepal location picker.
 */
window.SmartAddressSelector = class SmartAddressSelector {
  constructor(containerId, options = {}) {
    this.container = document.getElementById(containerId);
    if (!this.container) return;
    this.onChange = options.onChange || (() => {});
    
    this.init();
  }

  async init() {
    this.render();
    this.provinceInput = this.container.querySelector('[data-type="province"] input');
    this.provinceDropdown = this.container.querySelector('[data-type="province"] .nexus-search-dropdown');
    this.districtInput = this.container.querySelector('[data-type="district"] input');
    this.districtDropdown = this.container.querySelector('[data-type="district"] .nexus-search-dropdown');
    
    this.attachEvents();
  }

  render() {
    this.container.innerHTML = `
      <div class="nexus-grid nexus-grid-2">
        <div class="nexus-field">
          <label class="nexus-field-label">Province <span class="required">*</span></label>
          <div class="nexus-search-select" data-type="province">
            <input type="text" name="permanent_province" class="nexus-input" placeholder="Search province..." autocomplete="off" required>
            <div class="nexus-search-dropdown"></div>
          </div>
        </div>
        <div class="nexus-field">
          <label class="nexus-field-label">District <span class="required">*</span></label>
          <div class="nexus-search-select" data-type="district">
            <input type="text" name="permanent_district" class="nexus-input" placeholder="Select province first" disabled autocomplete="off" required>
            <div class="nexus-search-dropdown"></div>
          </div>
        </div>
      </div>
    `;
  }

  attachEvents() {
    // Province Search
    this.provinceInput.addEventListener('focus', () => this.filterProvinces());
    this.provinceInput.addEventListener('input', () => this.filterProvinces());

    // District Search
    this.districtInput.addEventListener('focus', () => this.filterDistricts());
    this.districtInput.addEventListener('input', () => this.filterDistricts());

    // Close on click outside
    document.addEventListener('click', (e) => {
      if (!this.container.contains(e.target)) {
        this.provinceDropdown.classList.remove('visible');
        this.districtDropdown.classList.remove('visible');
      }
    });
  }

  filterProvinces() {
    const val = this.provinceInput.value.toLowerCase();
    const matches = (window.nepalData?.provinces || []).filter(p => p.name.toLowerCase().includes(val));
    
    this.provinceDropdown.innerHTML = matches.map(p => 
      `<div class="nexus-search-item" data-value="${p.name}">${p.name}</div>`
    ).join('');
    
    this.provinceDropdown.classList.add('visible');
    
    this.provinceDropdown.querySelectorAll('.nexus-search-item').forEach(item => {
      item.onclick = () => {
        this.provinceInput.value = item.dataset.value;
        this.provinceDropdown.classList.remove('visible');
        this.districtInput.disabled = false;
        this.districtInput.value = '';
        this.districtInput.placeholder = 'Search district...';
        this.onChange();
      };
    });
  }

  filterDistricts() {
    const province = this.provinceInput.value;
    if (!province) return;

    const val = this.districtInput.value.toLowerCase();
    const districts = window.getDistrictsByProvinceName ? window.getDistrictsByProvinceName(province) : [];
    const matches = districts.filter(d => d.toLowerCase().includes(val));

    this.districtDropdown.innerHTML = matches.map(d => 
      `<div class="nexus-search-item" data-value="${d}">${d}</div>`
    ).join('');
    
    this.districtDropdown.classList.add('visible');

    this.districtDropdown.querySelectorAll('.nexus-search-item').forEach(item => {
      item.onclick = () => {
        this.districtInput.value = item.dataset.value;
        this.districtDropdown.classList.remove('visible');
        this.onChange();
      };
    });
  }
}
