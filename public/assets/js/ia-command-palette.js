/**
 * Unified Command Palette Logic
 * Trigger: Ctrl + K or Click on Search
 */
(function() {
    'use strict';

    let paletteEl = null;
    let isOpen = false;

    const createPalette = () => {
        if (paletteEl) return paletteEl;

        paletteEl = document.createElement('div');
        paletteEl.className = 'cp-overlay';
        paletteEl.innerHTML = `
            <div class="cp-modal">
                <div class="cp-search-wrap">
                    <i class="fa-solid fa-magnifying-glass cp-search-icon"></i>
                    <input type="text" class="cp-input" placeholder="Search students, staff, or menu..." id="cpInput">
                    <div class="cp-esc-hint">ESC</div>
                </div>
                <div class="cp-results" id="cpResults">
                    <div class="cp-section">
                        <div class="cp-section-title">Quick Actions</div>
                        <div class="cp-item" onclick="goNav('students', 'add')">
                            <i class="fa-solid fa-user-plus cp-icon blue"></i>
                            <div class="cp-label">Add New Student</div>
                            <div class="cp-hint">Menu</div>
                        </div>
                        <div class="cp-item" onclick="goNav('fee', 'quick')">
                            <i class="fa-solid fa-hand-holding-dollar cp-icon green"></i>
                            <div class="cp-label">Collect Fee</div>
                            <div class="cp-hint">Menu</div>
                        </div>
                    </div>
                </div>
                <div class="cp-footer">
                    <div class="cp-footer-item"><kbd>↑↓</kbd> Navigate</div>
                    <div class="cp-footer-item"><kbd>↵</kbd> Select</div>
                    <div class="cp-footer-item"><kbd>ESC</kbd> Close</div>
                </div>
            </div>
        `;
        document.body.appendChild(paletteEl);
        
        const input = paletteEl.querySelector('#cpInput');
        input.addEventListener('input', (e) => handleSearch(e.target.value));
        input.addEventListener('keydown', handleKeyDown);

        paletteEl.addEventListener('click', (e) => {
            if (e.target === paletteEl) closePalette();
        });

        return paletteEl;
    };

    const openPalette = () => {
        const el = createPalette();
        el.classList.add('active');
        isOpen = true;
        setTimeout(() => el.querySelector('#cpInput').focus(), 10);
    };

    const closePalette = () => {
        if (paletteEl) paletteEl.classList.remove('active');
        isOpen = false;
    };

    const handleSearch = async (query) => {
        const resultsEl = document.getElementById('cpResults');
        if (query.length < 2) {
            // Restore default quick actions
            return;
        }

        try {
            const res = await fetch(`${window.APP_URL}/api/admin/global-search?q=${encodeURIComponent(query)}`);
            const data = await res.json();
            renderResults(data);
        } catch (e) {
            console.error('CP search error', e);
        }
    };

    const renderResults = (data) => {
        const resultsEl = document.getElementById('cpResults');
        let html = '';

        if (data.students?.length) {
            html += `<div class="cp-section"><div class="cp-section-title">Students</div>`;
            data.students.forEach(s => {
                html += `
                    <div class="cp-item" onclick="goNav('students', 'view', {id: ${s.id}})">
                        <div class="cp-avatar">${s.name[0]}</div>
                        <div class="cp-label">${s.name}</div>
                        <div class="cp-hint">${s.roll_no || ''}</div>
                    </div>
                `;
            });
            html += `</div>`;
        }

        // Add more sections as needed...

        if (!html) html = '<div style="padding:40px; text-align:center; color:var(--tl);">No results found</div>';
        resultsEl.innerHTML = html;
    };

    const handleKeyDown = (e) => {
        if (e.key === 'Escape') closePalette();
        // Add arrow key navigation logic here
    };

    // Listen for Ctrl+K
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            openPalette();
        }
    });

    // Expose global
    window.CommandPalette = { open: openPalette, close: closePalette };

})();
