/**
 * Premium Profile Dropdown Functionality
 * File: public/assets/js/profile-dropdown.js
 */

(function() {
    'use strict';

    const trigger = document.getElementById('pdTrigger');
    const menu = document.getElementById('pdMenuNew');

    if (!trigger || !menu) return;

    // Toggle dropdown on click
    trigger.addEventListener('click', (e) => {
        e.stopPropagation();
        menu.classList.toggle('active');
    });

    // Close on outside click
    document.addEventListener('click', (e) => {
        if (!menu.contains(e.target) && !trigger.contains(e.target)) {
            menu.classList.remove('active');
        }
    });

    // Close on escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            menu.classList.remove('active');
        }
    });

})();
