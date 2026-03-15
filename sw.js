const CACHE_NAME = 'farm-tracker-v4';
const ASSETS = [
  './',
  './index.html',
  './manifest.json',
  './icon.svg',
  'https://cdn.tailwindcss.com',
  'https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js',
  'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js',
  'https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf-autotable.min.js'
];

// On install, delete ALL old caches and cache fresh assets
self.addEventListener('install', (event) => {
  self.skipWaiting(); // Activate immediately without waiting
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.map(key => caches.delete(key)))
    ).then(() =>
      caches.open(CACHE_NAME).then(cache => cache.addAll(ASSETS))
    )
  );
});

// On activate, take control of all clients immediately
self.addEventListener('activate', (event) => {
  event.waitUntil(clients.claim());
});

// Network first for HTML, cache first for everything else
self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);
  const isHTML = url.pathname.endsWith('.html') || url.pathname.endsWith('/');

  if (isHTML) {
    // Always fetch fresh HTML from network
    event.respondWith(
      fetch(event.request).catch(() =>
        caches.match(event.request)
      )
    );
  } else {
    // Cache first for static assets
    event.respondWith(
      caches.match(event.request).then(response =>
        response || fetch(event.request)
      )
    );
  }
});
