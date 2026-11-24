
const CACHE_VERSION = 'hn-bloom-v1';

self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        (async () => {
            const cacheNames = await caches.keys();

            await Promise.all(
                cacheNames
                    .filter(name =>
                        name.startsWith('hn-bloom-') &&
                        name !== CACHE_VERSION
                    )
                    .map(name => caches.delete(name))
            );

            await self.clients.claim();
        })()
    );
});
