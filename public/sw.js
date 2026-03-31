const CACHE_NAME = 'isoftro-erp-v3.1';
const OFFLINE_URL = '/offline.html';

const ASSETS_TO_CACHE = [
  '/offline.html',
  '/assets/images/logo.png',
  '/assets/css/login.css'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS_TO_CACHE).catch(err => {
        console.warn('[SW] Cache install partial error:', err);
      });
    })
  );
  // Force this SW to replace any old SW immediately
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cache) => {
          if (cache !== CACHE_NAME) {
            console.log('[SW] Deleting old cache:', cache);
            return caches.delete(cache);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  // Only handle GET requests, skip API calls
  if (event.request.method !== 'GET') return;
  if (event.request.url.includes('/api/')) return;

  event.respondWith(
    fetch(event.request)
      .then((response) => {
        // Valid network response — return it directly (no aggressive caching)
        return response;
      })
      .catch(async () => {
        // Network failed — try to serve offline fallback
        if (event.request.mode === 'navigate') {
          const cached = await caches.match(OFFLINE_URL);
          // Always return a valid Response, even if offline.html isn't cached
          return cached || new Response(
            '<html><body><h1>You are offline</h1><p>Please check your connection.</p></body></html>',
            { status: 503, headers: { 'Content-Type': 'text/html' } }
          );
        }
        // For non-navigate (JS/CSS/images) — return empty 503, never undefined
        return new Response('', { status: 503, statusText: 'Offline' });
      })
  );
});
