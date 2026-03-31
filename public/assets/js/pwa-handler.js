/**
 * iSoftro ERP - PWA Handler
 * Handles service worker registration and installation prompts.
 */

let deferredPrompt;
let isAppInstalled = false;

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        // Always use root-relative path — works on any domain
        const swPath = '/sw.js';

        navigator.serviceWorker.register(swPath)
            .then(reg => {
                console.log('SW Registered', reg);

                // Force update check so old v1.0 SW is replaced immediately
                reg.update();
            })
            .catch(err => console.warn('SW Registration Failed', err));

        // Unregister any stale service workers from old paths (/erp/sw.js, /saas/...)
        navigator.serviceWorker.getRegistrations().then(registrations => {
            registrations.forEach(reg => {
                if (reg.scope && !reg.scope.endsWith(window.location.origin + '/')) {
                    console.log('Removing stale SW:', reg.scope);
                    reg.unregister();
                }
            });
        });
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
            alert('App is already installed on your device!');
            return;
        }
        return;
    }

    deferredPrompt.prompt();
    const { outcome } = await deferredPrompt.userChoice;
    deferredPrompt = null;

    if (outcome === 'accepted') {
        isAppInstalled = true;
        hideInstallUI();
    }
}

window.triggerPwaInstall = triggerPwaInstall;

window.addEventListener('appinstalled', () => {
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
            mobileBtn.style.display = window.matchMedia('(display-mode: standalone)').matches ? 'none' : 'flex';
        }
    }, 3000);
});
