const CACHE_NAME = 'tonys-theme-cache-v1';
const OFFLINE_PAGE = '/offline/';

// Resurssit, jotka välimuistitetaan
const PRECACHE_URLS = [
    '/',
    '/offline/',
    '/wp-content/themes/tonys-theme/style.css',
    '/wp-content/themes/tonys-theme/assets/css/critical.css',
    '/wp-content/themes/tonys-theme/assets/js/theme.min.js',
    '/wp-content/themes/tonys-theme/assets/fonts/montserrat-v25-latin-regular.woff2'
];

// Service Workerin asennus
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting())
    );
});

// Service Workerin aktivointi
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames
                    .filter(cacheName => cacheName !== CACHE_NAME)
                    .map(cacheName => caches.delete(cacheName))
            );
        })
    );
});

// Fetch-tapahtumien käsittely
self.addEventListener('fetch', event => {
    // Tarkista onko pyyntö GET-tyyppinen
    if (event.request.method !== 'GET') return;

    event.respondWith(
        caches.match(event.request)
            .then(response => {
                // Palauta välimuistista jos löytyy
                if (response) {
                    return response;
                }

                // Muuten hae verkosta
                return fetch(event.request)
                    .then(response => {
                        // Tarkista onko vastaus kelvollinen
                        if (!response || response.status !== 200 || response.type !== 'basic') {
                            return response;
                        }

                        // Kloonaa vastaus välimuistia varten
                        const responseToCache = response.clone();

                        caches.open(CACHE_NAME)
                            .then(cache => {
                                cache.put(event.request, responseToCache);
                            });

                        return response;
                    })
                    .catch(() => {
                        // Jos verkkoyhteyttä ei ole, näytä offline-sivu
                        if (event.request.mode === 'navigate') {
                            return caches.match(OFFLINE_PAGE);
                        }
                    });
            })
    );
});
