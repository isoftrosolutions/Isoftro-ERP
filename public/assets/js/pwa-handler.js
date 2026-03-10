/**
 * Hamro ERP — PWA Installation Handler
 */

let deferredPrompt;

// ── HTML INJECTION ──
function injectPwaModal() {
    if (document.getElementById('pwaInstallModal')) return;

    const modalHtml = `
    <div class="pwa-modal-overlay" id="pwaInstallModal">
        <div class="pwa-modal">
            <div class="pwa-close" onclick="closePwaModal()">&times;</div>
            <h2>Install Hamro ERP</h2>
            <p class="modal-desc">Access your dashboard instantly from your home screen with our optimized mobile experience.</p>
            
            <div class="pwa-actions">
                <button class="pwa-btn-install" id="pwaMainInstallBtn" onclick="triggerInstall()">
                    <i class="fa-solid fa-download"></i> Install Now
                </button>
                <button class="pwa-btn-later" onclick="closePwaModalPermanently()">Later</button>
            </div>

            <div id="iosSteps" style="display:none;" class="pwa-steps">
                <div class="pwa-step-item">
                    <div class="pwa-step-ico">1</div>
                    <div>Tap the <b>Share</b> button in Safari</div>
                </div>
                <div class="pwa-step-item">
                    <div class="pwa-step-ico">2</div>
                    <div>Scroll down and tap <b>Add to Home Screen</b></div>
                </div>
            </div>
        </div>
    </div>`;
    document.body.insertAdjacentHTML('beforeend', modalHtml);
}

window.addEventListener('beforeinstallprompt', (e) => {
    // Prevent the mini-infobar from appearing on mobile
    e.preventDefault();
    // Stash the event so it can be triggered later.
    deferredPrompt = e;
    // Update UI notify the user they can install the PWA
    showInstallButtons();
});

window.addEventListener('appinstalled', (evt) => {
    console.log('Hamro ERP was installed.');
    updateInstallButtonToSuccess();
});

function showInstallButtons() {
    const triggers = document.querySelectorAll('.install-btn-trigger');
    triggers.forEach(btn => {
        btn.style.display = 'flex';
        btn.classList.add('ready');
    });
}

function updateInstallButtonToSuccess() {
    const triggers = document.querySelectorAll('.install-btn-trigger');
    triggers.forEach(btn => {
        const span = btn.querySelector('span');
        const icon = btn.querySelector('i');
        if (span) span.innerText = 'App Installed';
        if (icon) {
            icon.className = 'fa-solid fa-circle-check';
        }
        btn.style.borderColor = '#16a34a';
        btn.style.color = '#16a34a';
        btn.style.background = 'rgba(22, 163, 74, 0.1)';
    });
}

// Modal Logic & Dispatcher
function openPwaModal(triggerNative = true) {
    // ── DIRECT ACTION ──
    // If the browser is ready with the native prompt, just trigger it immediately!
    if (deferredPrompt && triggerNative) {
        triggerInstall();
        return;
    }

    // ── FALLBACK UI ──
    const modal = document.getElementById('pwaInstallModal');
    if (modal) {
        modal.classList.add('active');
        // Check if already installed
        if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
            const desc = modal.querySelector('.modal-desc');
            if (desc) desc.innerText = "Hamro ERP is already installed and running as a standalone app on your device.";
            const btn = document.getElementById('pwaMainInstallBtn');
            if (btn) btn.style.display = 'none';
        }
    }
}

function closePwaModal() {
    const modal = document.getElementById('pwaInstallModal');
    if (modal) modal.classList.remove('active');
}

async function triggerInstall() {
    if (!deferredPrompt) {
        if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
            alert("Hamro ERP is already installed.");
        } else if (isIos()) {
            const steps = document.getElementById('iosSteps');
            if (steps) {
                steps.style.display = 'block';
                // Also open modal if not already open
                const modal = document.getElementById('pwaInstallModal');
                if (modal) modal.classList.add('active');
            }
        } else {
            alert("Use your browser's 'Add to Home Screen' or 'Install App' option to install Hamro ERP.");
        }
        return;
    }
    
    // Trigger the native prompt
    deferredPrompt.prompt();
    const { outcome } = await deferredPrompt.userChoice;
    console.log(`User response to the install prompt: ${outcome}`);
    
    if (outcome === 'accepted') {
        closePwaModal();
        updateInstallButtonToSuccess();
    }
    
    deferredPrompt = null;
}

function isIos() {
    return /iphone|ipad|ipod/.test(window.navigator.userAgent.toLowerCase());
}

function isInStandaloneMode() {
    return ('standalone' in window.navigator) && (window.navigator.standalone);
}

document.addEventListener('DOMContentLoaded', () => {
    // 1. Inject the Modal
    injectPwaModal();

    // 2. Show buttons immediately
    showInstallButtons();

    // 3. Handle iOS specific button text/logic
    if (isIos() && !isInStandaloneMode()) {
        const installBtn = document.getElementById('pwaMainInstallBtn');
        if (installBtn) {
            installBtn.innerHTML = '<i class="fa-solid fa-share-from-square"></i> Follow iOS Steps';
            installBtn.onclick = () => {
                const steps = document.getElementById('iosSteps');
                if (steps) steps.style.display = 'block';
            };
        }
    }

    // 4. Check if already in standalone mode
    if (window.matchMedia('(display-mode: standalone)').matches || isInStandaloneMode()) {
        updateInstallButtonToSuccess();
    }

    // 5. Register Service Worker
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            const swPath = (window.APP_URL ? window.APP_URL + '/' : '') + 'public/sw.js';
            navigator.serviceWorker.register(swPath)
                .then(reg => console.log('SW Registered', swPath))
                .catch(err => console.log('SW Registration Failed', err));
        });
    }

    // 6. Proactive Prompt: Show modal after 5 seconds if not installed
    setTimeout(() => {
        if (!isInStandaloneMode() && !localStorage.getItem('pwa_later')) {
            openPwaModal(false);
        }
    }, 5000);
});

function closePwaModalPermanently() {
    closePwaModal();
    localStorage.setItem('pwa_later', 'true');
}

