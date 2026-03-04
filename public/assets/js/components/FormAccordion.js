/**
 * FormAccordion - Manages collapsible sections in the Nexus Form system.
 */
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
      if (header) {
        header.addEventListener('click', () => this.toggle(section));
      }
    });
  }

  toggle(section) {
    const isExpanded = section.classList.contains('expanded');
    
    // Optional: Close other sections (Accordian style)
    /*
    this.sections.forEach(s => {
      if (s !== section) s.classList.remove('expanded');
    });
    */

    if (isExpanded) {
      section.classList.remove('expanded');
    } else {
      section.classList.add('expanded');
    }
    
    // Fire custom event
    const event = new CustomEvent('sectionToggle', { 
      detail: { section, expanded: !isExpanded } 
    });
    this.container.dispatchEvent(event);
  }

  expand(index) {
    if (this.sections[index]) {
      this.sections[index].classList.add('expanded');
    }
  }

  collapseAll() {
    this.sections.forEach(s => s.classList.remove('expanded'));
  }
}
