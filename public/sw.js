const CACHE_NAME = "inventory-v3";
const urlsToCache = [
  "./",
  "./manifest.json"
];

// Install
self.addEventListener("install", event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      return cache.addAll(urlsToCache).catch(err => {
        console.log('Cache addAll error (non-critical):', err);
      });
    })
  );
  self.skipWaiting();
});

// Activate
self.addEventListener("activate", event => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key)))
    )
  );
  return self.clients.claim();
});

// Fetch - Network-first strategy
self.addEventListener("fetch", event => {
  if (event.request.url.includes('/api/')) {
    return event.respondWith(fetch(event.request));
  }
  
  event.respondWith(
    fetch(event.request).then(response => {
      if (!response || response.status !== 200) return response;
      const responseClone = response.clone();
      caches.open(CACHE_NAME).then(cache => {
        cache.put(event.request, responseClone);
      });
      return response;
    }).catch(() => {
      return caches.match(event.request).then(response => {
        return response || caches.match("./offline.html");
      });
    })
  );
});

self.addEventListener('activate', event => {
    console.log('Service Worker activating.');
});

self.addEventListener('fetch', function(event) {
    // Optional: cache logic for offline support
});
