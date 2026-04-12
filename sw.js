// Farm Tracker Pro — Notification Service Worker
// Handles push display and notification click → open app

const CACHE_NAME = 'ftp-notif-v1';

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', e => e.waitUntil(self.clients.claim()));

// Handle push events (if you ever add a backend push server)
self.addEventListener('push', function(event) {
    const data = event.data ? event.data.json() : {};
    const title = data.title || '🌾 Farm Tracker Pro';
    const options = {
        body: data.body || 'You have a new farm notification.',
        icon: data.icon || `data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'><path fill='%23059669' d='M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8h5z'/></svg>`,
        badge: `data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'><path fill='%23059669' d='M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8h5z'/></svg>`,
        tag: data.tag || 'ftp-push',
        data: data.data || {},
        requireInteraction: false,
        vibrate: [200, 100, 200]
    };
    event.waitUntil(self.registration.showNotification(title, options));
});

// Tapping a notification → focus existing window or open new one
self.addEventListener('notificationclick', function(event) {
    event.notification.close();

    const targetUrl = (event.notification.data && event.notification.data.url)
        ? event.notification.data.url
        : self.registration.scope;

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(clients => {
            // Find an existing window and focus it
            for (const client of clients) {
                const clientUrl = client.url.split('#')[0];
                const scopeUrl  = self.registration.scope;
                if (clientUrl === scopeUrl || clientUrl.startsWith(scopeUrl)) {
                    client.focus();
                    // Navigate to dashboard
                    if (client.navigate) client.navigate(targetUrl);
                    return;
                }
            }
            // No window open — open a new one
            return self.clients.openWindow(targetUrl);
        })
    );
});
