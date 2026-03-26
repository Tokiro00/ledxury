const CACHE_NAME = 'mam-vendedores-v1';
const SHELL_FILES = [
  './',
  './index.html',
  './manifest.json'
];

// Install: cache app shell
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(SHELL_FILES))
  );
  self.skipWaiting();
});

// Activate: clean old caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(
        keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k))
      )
    )
  );
  self.clients.claim();
});

// Fetch: network-first for API, cache-first for shell
self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);

  // API calls: network first, no cache fallback
  if (url.pathname.includes('/api/')) {
    event.respondWith(
      fetch(event.request).catch(() =>
        new Response(JSON.stringify({ success: false, message: 'Sin conexion' }), {
          headers: { 'Content-Type': 'application/json' }
        })
      )
    );
    return;
  }

  // App shell: cache first, then network
  event.respondWith(
    caches.match(event.request).then((cached) => cached || fetch(event.request))
  );
});
