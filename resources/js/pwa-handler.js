/**
 * Hamro ERP — PWA Handler
 * Handles service worker registration and installation prompts.
 */

let deferredPrompt;

// Register Service Worker
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        const swPath = (window.APP_URL || '/erp') + '/public/sw.js';
        navigator.serviceWorker.register(swPath)
            .then(reg => console.log('SW Registered', reg))
            .catch(err => console.log('SW Registration Failed', err));
    });
}

// Catch the install prompt
window.addEventListener('beforeinstallprompt', (e) => {
    // Prevent Chrome 67 and earlier from automatically showing the prompt
    e.preventDefault();
    // Stash the event so it can be triggered later.
    deferredPrompt = e;
    
    // Show the install buttons and banners
    const installUI = document.querySelectorAll('.pwa-install-btn, .install-btn-trigger, .pwa-install-banner, #pwaInstallBanner');
    installUI.forEach(el => {
        el.style.display = 'block'; // Or flex, based on the element
        if (el.classList.contains('pwa-install-banner')) {
            el.style.display = 'block';
        }
        if (el.classList.contains('pwa-install-btn') || el.classList.contains('install-btn-trigger')) {
             if (el.tagName !== 'DIV') el.style.display = 'flex';
        }
    });

    console.log('PWA Prompt Captured');
});

// Function to trigger installation
async function triggerPwaInstall() {
    if (!deferredPrompt) {
        console.log('No deferred prompt available');
        return;
    }
    
    // Show the prompt
    deferredPrompt.prompt();
    
    // Wait for the user to respond to the prompt
    const { outcome } = await deferredPrompt.userChoice;
    console.log(`User response to install prompt: ${outcome}`);
    
    // We've used the prompt, and can't use it again, throw it away
    deferredPrompt = null;
    
    // Hide UI if successful or chosen
    const installUI = document.querySelectorAll('.pwa-install-btn, .install-btn-trigger, .pwa-install-banner, #pwaInstallBanner');
    installUI.forEach(el => {
        el.style.display = 'none';
    });
}

// Sidebar Modal logic (if still used)
function openPwaModal(triggerNative = true) {
    if (triggerNative) {
        triggerPwaInstall();
    }
}

// Listen for successful installation
window.addEventListener('appinstalled', (evt) => {
    console.log('App was installed');
});
