/**
 * Hamro ERP — Institute Admin · ia-sidebar.js
 * Handles sidebar toggling (collapse/expand/mobile active) and state persistence.
 */

(function() {
    const initSidebar = () => {
        const body = document.body;
        const menuToggle = document.getElementById('menuToggle');
        const sbToggle = document.getElementById('sbToggle');
        const sbClose = document.getElementById('sbClose');
        const sbOverlay = document.getElementById('sbOverlay');

        // Restore collapsed state on desktop early to prevent flash
        if (window.innerWidth >= 1024 && localStorage.getItem('_ia_sb_collapsed') === '1') {
            body.classList.add('sb-collapsed');
        }

        const handleToggle = () => {
            if (window.innerWidth >= 1024) {
                // Desktop: Toggle collapsed state
                body.classList.toggle('sb-collapsed');
                localStorage.setItem('_ia_sb_collapsed', body.classList.contains('sb-collapsed') ? '1' : '0');
                
                // If we also have a menuToggle icon that needs an 'active' class
                if (menuToggle) menuToggle.classList.toggle('active');
            } else {
                // Mobile: Toggle active (slide-in) state
                body.classList.toggle('sb-active');
            }
        };

        // Attach listeners
        if (menuToggle) {
            menuToggle.removeEventListener('click', handleToggle);
            menuToggle.addEventListener('click', handleToggle);
        }
        
        if (sbToggle) {
            sbToggle.removeEventListener('click', handleToggle);
            sbToggle.addEventListener('click', handleToggle);
        }

        if (sbClose) {
            sbClose.addEventListener('click', () => {
                body.classList.remove('sb-active');
            });
        }

        if (sbOverlay) {
            sbOverlay.addEventListener('click', () => {
                body.classList.remove('sb-active');
                if (window.innerWidth >= 1024) {
                    body.classList.remove('sb-collapsed');
                    localStorage.setItem('_ia_sb_collapsed', '0');
                }
            });
        }

        // Close mobile sidebar on window resize if it gets large
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024 && body.classList.contains('sb-active')) {
                body.classList.remove('sb-active');
            }
        });
    };

    // Initialize on DOM load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSidebar);
    } else {
        initSidebar();
    }

    // Expose globally for re-initialization if needed (SPA navigation)
    window.iaInitSidebar = initSidebar;
})();
