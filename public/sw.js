const CORE_APP_CACHE = 'core-app-shell-v1';
const CORE_APP_ASSETS = [
  './assets/css/adminlte.min.css',
  './assets/css/all.min.css',
  './assets/css/bootstrap-icons.css',
  './assets/css/dataTables.bootstrap4.min.css',
  './assets/js/vue.min.js',
  './assets/js/jquery.min.js',
  './assets/js/bootstrap.bundle.min.js',
  './assets/js/adminlte.min.js',
  './assets/js/core-offline.js',
  './favicon.ico'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CORE_APP_CACHE).then(cache => cache.addAll(CORE_APP_ASSETS)).catch(() => null)
  );
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => Promise.all(keys.filter(key => key !== CORE_APP_CACHE).map(key => caches.delete(key))))
  );
  self.clients.claim();
});

self.addEventListener('fetch', event => {
  const request = event.request;
  if (request.method !== 'GET') return;

  event.respondWith(
    fetch(request).then(response => {
      const copy = response.clone();
      if (response.ok && request.url.indexOf('/admin/') >= 0) {
        caches.open(CORE_APP_CACHE).then(cache => cache.put(request, copy)).catch(() => null);
      }
      return response;
    }).catch(() => caches.match(request).then(cached => {
      if (cached) return cached;
      if (request.mode === 'navigate') {
        return new Response('<!doctype html><meta charset="utf-8"><title>Core-App offline</title><body style="font-family:sans-serif;padding:2rem"><h1>Sin conexion</h1><p>Abre una pantalla visitada previamente o vuelve a intentar cuando regrese internet.</p></body>', { headers: { 'Content-Type': 'text/html; charset=utf-8' } });
      }
      return new Response('', { status: 503, statusText: 'Offline' });
    }))
  );
});
