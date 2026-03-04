/**
 * FieldValidator - Real-time validation for Nexus Forms.
 */
window.FieldValidator = class FieldValidator {
  constructor(inputElement, rules = []) {
    this.input = inputElement;
    this.rules = rules;
    this.debounceTimer = null;
    this.isValid = true;
    
    this.input.addEventListener('blur', () => this.validate());
    this.input.addEventListener('input', () => {
      clearTimeout(this.debounceTimer);
      this.debounceTimer = setTimeout(() => this.validate(), 500);
    });
  }

  static get Rules() {
    return {
      email: {
        pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
        message: 'Invalid email address'
      },
      phone: {
        pattern: /^[0-9]{10}$/,
        message: 'Phone number must be 10 digits'
      },
      required: {
        test: (val) => val.trim().length > 0,
        message: 'This field is required'
      }
    };
  }

  async validate() {
    const value = this.input.value;
    this.clearError();

    for (const ruleKey of this.rules) {
      const rule = FieldValidator.Rules[ruleKey];
      if (!rule) continue;

      let pass = true;
      if (rule.pattern) pass = rule.pattern.test(value);
      if (rule.test) pass = rule.test(value);

      if (!pass) {
        this.showError(rule.message);
        this.isValid = false;
        return false;
      }
    }

    this.showValid();
    this.isValid = true;
    return true;
  }

  showError(message) {
    this.input.classList.add('error');
    this.input.classList.remove('valid');
    
    let errorEl = this.input.parentNode.querySelector('.nexus-field-error');
    if (!errorEl) {
      errorEl = document.createElement('div');
      errorEl.className = 'nexus-field-error';
      errorEl.style.color = 'var(--nexus-error)';
      errorEl.style.fontSize = '12px';
      errorEl.style.marginTop = '4px';
      this.input.parentNode.appendChild(errorEl);
    }
    errorEl.textContent = message;
  }

  showValid() {
    this.input.classList.remove('error');
    this.input.classList.add('valid');
    this.clearError();
  }

  clearError() {
    const errorEl = this.input.parentNode.querySelector('.nexus-field-error');
    if (errorEl) errorEl.remove();
  }
}
