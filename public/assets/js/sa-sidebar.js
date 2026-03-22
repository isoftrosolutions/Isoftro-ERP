/**
 * ISOFTRO — Super Admin · sa-sidebar.js
 * Handles sidebar toggling (collapse/expand/mobile active) and state persistence.
 */

(function() {
    const initSidebar = () => {
        const body = document.body;
        const sbToggle = document.getElementById('sbToggle'); // This is the hamburger in main header (if exists)
        const sbToggleAlt = document.querySelector('.hdr-left .sb-toggle'); // Alternative selector for the hamburger in header
        const sbClose = document.getElementById('sbClose');
        const sbOverlay = document.getElementById('sbOverlay');

        // Restore collapsed state on desktop early to prevent flash
        if (window.innerWidth >= 1024 && localStorage.getItem('_sa_sb_collapsed') === '1') {
            body.classList.add('sb-collapsed');
        }

        const handleToggle = () => {
            if (window.innerWidth >= 1024) {
                // Desktop: Toggle collapsed state
                body.classList.toggle('sb-collapsed');
                localStorage.setItem('_sa_sb_collapsed', body.classList.contains('sb-collapsed') ? '1' : '0');
            } else {
                // Mobile: Toggle active (slide-in) state
                body.classList.toggle('sb-active');
            }
        };

        // Attach listeners to the hamburger in header (if exists)
        if (sbToggle) {
            sbToggle.removeEventListener('click', handleToggle);
            sbToggle.addEventListener('click', handleToggle);
        }
        if (sbToggleAlt) {
            sbToggleAlt.removeEventListener('click', handleToggle);
            sbToggleAlt.addEventListener('click', handleToggle);
        }

        // Attach listeners to the close button in mobile sidebar
        if (sbClose) {
            sbClose.addEventListener('click', () => {
                body.classList.remove('sb-active');
            });
        }

        // Attach listener to overlay
        if (sbOverlay) {
            sbOverlay.addEventListener('click', () => {
                body.classList.remove('sb-active');
                if (window.innerWidth >= 1024) {
                    body.classList.remove('sb-collapsed');
                    localStorage.setItem('_sa_sb_collapsed', '0');
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
    window.saInitSidebar = initSidebar;
})();