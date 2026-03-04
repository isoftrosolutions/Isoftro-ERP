/**
 * FormAutosave - LocalStorage-based draft management.
 */
window.FormAutosave = class FormAutosave {
  constructor(formId, options = {}) {
    this.form = document.getElementById(formId);
    if (!this.form) return;
    this.key = options.storageKey || `draft_${formId}`;
    this.onRestore = options.onRestore || (() => {});
    
    this.init();
  }

  init() {
    this.restore();
    this.form.addEventListener('input', () => this.save());
  }

  save() {
    const formData = new FormData(this.form);
    const data = Object.fromEntries(formData);
    const draft = {
      data,
      timestamp: Date.now()
    };
    localStorage.setItem(this.key, JSON.stringify(draft));
  }

  restore() {
    const saved = localStorage.getItem(this.key);
    if (!saved) return;

    try {
      const draft = JSON.parse(saved);
      // Only restore if less than 24 hours old
      if (Date.now() - draft.timestamp < 86400000) {
        Object.entries(draft.data).forEach(([key, value]) => {
          const field = this.form.querySelector(`[name="${key}"]`);
          if (field) {
            if (field.type === 'checkbox' || field.type === 'radio') {
              field.checked = field.value === value;
            } else {
              field.value = value;
            }
          }
        });
        this.onRestore(draft.data);
      }
    } catch (e) {
      console.error('Failed to restore draft', e);
    }
  }

  clear() {
    localStorage.removeItem(this.key);
  }
}
