/* HR PRO — service worker.
 *
 * What it caches is deliberately narrow. Pages carry personal data and are
 * often opened on shared phones, so HTML is never stored: navigations always
 * go to the network, and only when that fails is the static offline page
 * shown. Stylesheets, scripts, icons and fonts are kept (stale-while-
 * revalidate) so the shell opens instantly on a slow connection.
 */
const VERSION = 'hrpro-v1';
const OFFLINE_URL = '/offline.html';
const PRECACHE = [OFFLINE_URL, '/icons/icon-192.png'];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(VERSION).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((key) => key !== VERSION).map((key) => caches.delete(key))))
            .then(() => self.clients.claim())
    );
});

function isStaticAsset(url) {
    return (url.origin === self.location.origin && /^\/(css|js|icons|vendor)\//.test(url.pathname))
        || url.hostname === 'fonts.googleapis.com'
        || url.hostname === 'fonts.gstatic.com';
}

self.addEventListener('fetch', (event) => {
    const request = event.request;
    if (request.method !== 'GET') return;

    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_URL)));
        return;
    }

    const url = new URL(request.url);
    if (!isStaticAsset(url)) return;

    event.respondWith(
        caches.open(VERSION).then((cache) =>
            cache.match(request).then((cached) => {
                const network = fetch(request)
                    .then((response) => {
                        if (response.ok || response.type === 'opaque') cache.put(request, response.clone());
                        return response;
                    })
                    .catch(() => cached);
                return cached || network;
            })
        )
    );
});
