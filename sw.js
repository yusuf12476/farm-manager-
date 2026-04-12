// Farm Tracker Pro — Service Worker
// Handles background Web Push delivery & notification tap → open app

const CACHE_NAME = 'ftp-notif-v2';

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', e => e.waitUntil(self.clients.claim()));

// ── Receive push from Cloudflare Worker ─────────────────────────
self.addEventListener('push', function(event) {
    let data = {};
    try { data = event.data ? event.data.json() : {}; } catch(e) {
        data = { title: '🌅 Farm Briefing', body: event.data?.text() || '' };
    }

    const title   = data.title || '🌾 Farm Tracker Pro';
    const options = {
        body: data.body || 'Open the app to see your daily farm summary.',
        icon: `data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'><path fill='%23059669' d='M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8h5z'/></svg>`,
        badge: `data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'><circle fill='%23059669' cx='12' cy='12' r='10'/></svg>`,
        tag: data.tag || 'ftp-morning-brief',
        requireInteraction: false,
        vibrate: [200, 100, 200],
        data: { url: self.registration.scope + '#dashboard' }
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

// ── Tapping the notification → focus/open the PWA ───────────────
self.addEventListener('notificationclick', function(event) {
    event.notification.close();

    const targetUrl = (event.notification.data && event.notification.data.url)
        ? event.notification.data.url
        : self.registration.scope + '#dashboard';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(clients => {
            for (const client of clients) {
                if (client.url.startsWith(self.registration.scope) && 'focus' in client) {
                    client.focus();
                    if (client.navigate) client.navigate(targetUrl);
                    return;
                }
            }
            return self.clients.openWindow(targetUrl);
        })
    );
});
