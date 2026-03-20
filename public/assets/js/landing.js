/**
 * isoftro Academic ERP — Landing Page Interactivity
 * Green SaaS Theme
 */
document.addEventListener('DOMContentLoaded', () => {

  /* ----------- NAVBAR ----------- */
  const navbar = document.getElementById('lNavbar');
  const navToggle = document.getElementById('navToggle');
  const navLinks = document.getElementById('navLinks');

  if (navbar) {
    window.addEventListener('scroll', () => {
      if (window.scrollY > 50) {
        navbar.style.boxShadow = '0 4px 20px rgba(0,0,0,0.1)';
      } else {
        navbar.style.boxShadow = '0 2px 12px rgba(0,0,0,0.06)';
      }
    });
  }

  if (navToggle && navLinks) {
    navToggle.addEventListener('click', () => {
      navLinks.classList.toggle('open');
    });
  }

  /* ----------- SMOOTH SCROLL ----------- */
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        e.preventDefault();
        const offset = navbar ? navbar.offsetHeight + 10 : 80;
        const y = target.getBoundingClientRect().top + window.pageYOffset - offset;
        window.scrollTo({ top: y, behavior: 'smooth' });
        if (navLinks) navLinks.classList.remove('open');
      }
    });
  });

  /* ----------- SCROLL REVEAL ----------- */
  const revealElements = document.querySelectorAll('.reveal');
  if (revealElements.length > 0 && 'IntersectionObserver' in window) {
    const revealObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          revealObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
    revealElements.forEach(el => revealObserver.observe(el));
  }

  /* ----------- COUNTER ANIMATION ----------- */
  const counters = document.querySelectorAll('[data-count]');
  if (counters.length > 0 && 'IntersectionObserver' in window) {
    const counterObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const el = entry.target;
          const target = parseInt(el.dataset.count, 10);
          const suffix = el.dataset.suffix || '';
          const duration = 2000;
          const steps = 60;
          const increment = target / steps;
          let current = 0;
          const stepTime = duration / steps;
          const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
              current = target;
              clearInterval(timer);
            }
            el.textContent = Math.floor(current).toLocaleString() + suffix;
          }, stepTime);
          counterObserver.unobserve(el);
        }
      });
    }, { threshold: 0.5 });
    counters.forEach(c => counterObserver.observe(c));
  }

  /* ----------- FAQ ACCORDION ----------- */
  document.querySelectorAll('.faq-item__question').forEach(q => {
    q.addEventListener('click', () => {
      const item = q.parentElement;
      const wasActive = item.classList.contains('active');
      document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('active'));
      if (!wasActive) item.classList.add('active');
    });
  });

  /* ----------- DASHBOARD TABS ----------- */
  const tabs = document.querySelectorAll('.dashboard-tab');
  const panels = document.querySelectorAll('.dashboard-panel');
  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      tabs.forEach(t => t.classList.remove('active'));
      panels.forEach(p => p.classList.remove('active'));
      tab.classList.add('active');
      const panel = document.getElementById(tab.dataset.tab);
      if (panel) panel.classList.add('active');
    });
  });

  /* ----------- ACTIVE NAV LINK ----------- */
  const sections = document.querySelectorAll('section[id]');
  const navAnchors = document.querySelectorAll('.l-navbar__links a[href^="#"]');
  if (sections.length > 0 && navAnchors.length > 0) {
    window.addEventListener('scroll', () => {
      let current = '';
      const offset = (navbar ? navbar.offsetHeight : 70) + 60;
      sections.forEach(section => {
        const st = section.offsetTop - offset;
        if (window.scrollY >= st) current = section.getAttribute('id');
      });
      navAnchors.forEach(a => {
        a.style.color = '';
        if (a.getAttribute('href') === `#${current}`) {
          a.style.color = '#006D44';
        }
      });
    });
  }

});
