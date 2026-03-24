/**
 * Hamro ERP — Institute Admin · ia-sidebar.v3.js
 * SaaS Grade Sidebar System
 * - Zero-flicker state persistence (Immediate execution)
 * - Robust Event Delegation (AJAX-safe)
 * - Mobile & Desktop agnostic logic
 */

(function() {
    if (window.__ia_sidebar_initialized) return;
    window.__ia_sidebar_initialized = true;

    const DEBUG = true; // Toggle for console logs
    const STORAGE_KEY = '_ia_sb_collapsed';
    const TOGGLE_CLASS = 'js-sidebar-toggle';
    const body = document.body;

    /**
     * Helper: Log debug messages
     */
    const log = (msg) => {
        if (DEBUG) console.log(`[ia-sidebar] ${msg}`);
    };

    /**
     * Immediate State Restoration (Prevents FOUC/Flicker)
     * We apply the class to <body> or <html> as soon as possible.
     */
    const restoreState = () => {
        if (window.innerWidth >= 1024) {
            const isCollapsed = localStorage.getItem(STORAGE_KEY) === '1';
            if (isCollapsed) {
                document.documentElement.classList.add('sb-collapsed');
                // Ensure body gets it too if script runs after body is available
                if (document.body) document.body.classList.add('sb-collapsed');
                log('Restored collapsed state from storage.');
            }
        }
    };

    /**
     * Main Toggle Action
     */
    const toggleSidebar = (forceClose = false) => {
        const isDesktop = window.innerWidth >= 1024;

        if (isDesktop) {
            // Desktop: Toggle collapsed (mini) state
            const willCollapse = forceClose ? true : !body.classList.contains('sb-collapsed');
            
            if (willCollapse) {
                body.classList.add('sb-collapsed');
                localStorage.setItem(STORAGE_KEY, '1');
            } else {
                body.classList.remove('sb-collapsed');
                localStorage.setItem(STORAGE_KEY, '0');
            }
            log(`Desktop Sidebar: ${willCollapse ? 'Collapsed' : 'Expanded'}`);
        } else {
            // Mobile: Toggle active (slide-in) state
            const willOpen = forceClose ? false : !body.classList.contains('sb-active');
            
            if (willOpen) {
                body.classList.add('sb-active');
            } else {
                body.classList.remove('sb-active');
            }
            log(`Mobile Sidebar: ${willOpen ? 'Opened' : 'Closed'}`);
        }
    };

    /**
     * Event Delegation Setup
     */
    const initListeners = () => {
        // 1. Unified Click Delegation (The Fix for AJAX/Dynamic DOM)
        document.addEventListener('click', (e) => {
            // Check if clicked element or any parent has the toggle class
            const toggleBtn = e.target.closest(`.${TOGGLE_CLASS}`);
            const isOverlay = e.target.id === 'sbOverlay' || e.target.classList.contains('sb-overlay');
            const isCloseBtn = e.target.id === 'sbClose' || e.target.closest('#sbClose');

            if (toggleBtn) {
                e.preventDefault();
                toggleSidebar();
            } else if (isOverlay || isCloseBtn) {
                // Clicking overlay or Close button always closes mobile sidebar
                body.classList.remove('sb-active');
                log('Closed via overlay/close button.');
            }
        });

        // 2. Mobile cleanup on resize
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024 && body.classList.contains('sb-active')) {
                body.classList.remove('sb-active');
            }
        });
        
        log('Listeners initialized via Delegation.');
    };

    // --- EXECUTION ---
    
    // 1. Restore state NOW (Atomic)
    restoreState();

    // 2. Initialize listeners on DOM ready (or now if already ready)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            // Re-apply collapsed state to body just in case
            if (document.documentElement.classList.contains('sb-collapsed')) {
                document.body.classList.add('sb-collapsed');
            }
            initListeners();
        });
    } else {
        initListeners();
    }

    // Expose for manual control if needed
    window.SidebarManager = {
        toggle: toggleSidebar,
        closeMobile: () => body.classList.remove('sb-active'),
        isCollapsed: () => body.classList.contains('sb-collapsed')
    };

})();
