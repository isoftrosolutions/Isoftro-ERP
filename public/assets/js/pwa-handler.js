/**
 * iSoftro ERP — PWA Handler
 * Handles service worker registration and installation prompts.
 */

let deferredPrompt;
let isAppInstalled = false;

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        const swPath = (window.APP_URL || '/erp') + '/sw.js';
        navigator.serviceWorker.register(swPath)
            .then(reg => console.log('SW Registered', reg))
            .catch(err => console.log('SW Registration Failed', err));
    });
}

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    
    showInstallUI();
    
    console.log('PWA Install Prompt Captured');
});

function showInstallUI() {
    const installUI = document.querySelectorAll('.pwa-install-btn, .install-btn-trigger, .pwa-install-banner, #pwaInstallBanner, #mobileInstallBtn');
    installUI.forEach(el => {
        if (el) {
            el.style.display = el.classList.contains('pwa-install-banner') || el.classList.contains('mobile-install-btn') || el.tagName === 'DIV' ? 'block' : 'flex';
        }
    });
}

function hideInstallUI() {
    const installUI = document.querySelectorAll('.pwa-install-btn, .install-btn-trigger, .pwa-install-banner, #pwaInstallBanner, #mobileInstallBtn');
    installUI.forEach(el => {
        if (el) el.style.display = 'none';
    });
}

async function triggerPwaInstall() {
    if (!deferredPrompt) {
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches;
        if (isStandalone) {
            console.log('App already installed');
            alert('App is already installed on your device!');
            return;
        }
        console.log('No deferred prompt available - trying native prompt');
        return;
    }
    
    deferredPrompt.prompt();
    
    const { outcome } = await deferredPrompt.userChoice;
    console.log(`User response to install prompt: ${outcome}`);
    
    deferredPrompt = null;
    
    if (outcome === 'accepted') {
        isAppInstalled = true;
        hideInstallUI();
    }
}

window.triggerPwaInstall = triggerPwaInstall;

window.addEventListener('appinstalled', (evt) => {
    console.log('App was installed successfully');
    isAppInstalled = true;
    hideInstallUI();
});

if (window.matchMedia('(display-mode: standalone)').matches) {
    isAppInstalled = true;
    hideInstallUI();
}

window.addEventListener('load', () => {
    setTimeout(() => {
        const mobileBtn = document.getElementById('mobileInstallBtn');
        if (mobileBtn && !deferredPrompt && !isAppInstalled) {
            if (window.matchMedia('(display-mode: standalone)').matches) {
                mobileBtn.style.display = 'none';
            } else {
                mobileBtn.style.display = 'flex';
            }
        }
    }, 3000);
});
