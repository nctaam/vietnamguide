/**
 * VietnamGuide Service Worker (PWA & Offline Travel Field Guide)
 * 
 * Provides network-first caching for field guides and itineraries,
 * reliable static asset delivery, and offline fallback support for travelers.
 */

'use strict';

var CACHE_NAME = 'vg-travel-handbook-v1.0.0';
var OFFLINE_URL = '/offline.html';

var PRECACHE_ASSETS = [
  OFFLINE_URL,
  '/wp-content/themes/vietnamguide-premium/assets/css/homepage.css',
  '/wp-content/themes/vietnamguide-premium/assets/images/vg-icon.svg',
  '/wp-content/themes/vietnamguide-premium/assets/images/vg-icon-192.png',
  '/wp-content/themes/vietnamguide-premium/site.webmanifest'
];

self.addEventListener('install', function (event) {
  event.waitUntil(
    caches.open(CACHE_NAME).then(function (cache) {
      return cache.addAll(PRECACHE_ASSETS);
    }).then(function () {
      return self.skipWaiting();
    })
  );
});

self.addEventListener('activate', function (event) {
  event.waitUntil(
    caches.keys().then(function (cacheNames) {
      return Promise.all(
        cacheNames.map(function (cacheName) {
          if (cacheName !== CACHE_NAME && cacheName.indexOf('vg-') === 0) {
            return caches.delete(cacheName);
          }
          return null;
        })
      );
    }).then(function () {
      return self.clients.claim();
    })
  );
});

self.addEventListener('fetch', function (event) {
  var request = event.request;

  // Ignore non-GET requests
  if (request.method !== 'GET') {
    return;
  }

  var url = new URL(request.url);

  // Only handle same-origin requests
  if (url.origin !== self.location.origin) {
    return;
  }

  // Bypass admin, login, and dynamic query endpoints
  if (
    url.pathname.indexOf('/wp-admin/') !== -1 ||
    url.pathname.indexOf('/wp-login.php') !== -1 ||
    url.search.indexOf('preview=true') !== -1 ||
    url.pathname.indexOf('/wp-json/wp/v2/users') !== -1
  ) {
    return;
  }

  // Handle navigation (HTML pages) with Network-First, falling back to cache, then offline fallback
  var isHtmlNavigation = request.mode === 'navigate' ||
    (request.headers.get('accept') && request.headers.get('accept').indexOf('text/html') !== -1);

  if (isHtmlNavigation) {
    event.respondWith(
      fetch(request)
        .then(function (networkResponse) {
          if (networkResponse && networkResponse.status === 200) {
            var responseClone = networkResponse.clone();
            caches.open(CACHE_NAME).then(function (cache) {
              cache.put(request, responseClone);
            });
          }
          return networkResponse;
        })
        .catch(function () {
          return caches.match(request).then(function (cachedResponse) {
            if (cachedResponse) {
              return cachedResponse;
            }
            return caches.match(OFFLINE_URL);
          });
        })
    );
    return;
  }

  // Handle static assets (CSS, JS, images, fonts, manifest) with Cache-First, falling back to network
  var isStaticAsset = (
    url.pathname.match(/\.(css|js|svg|png|jpg|jpeg|webp|woff|woff2|webmanifest)$/i) ||
    url.pathname.indexOf('/assets/') !== -1
  );

  if (isStaticAsset) {
    event.respondWith(
      caches.match(request).then(function (cachedResponse) {
        if (cachedResponse) {
          // Revalidate in background
          fetch(request).then(function (networkResponse) {
            if (networkResponse && networkResponse.status === 200) {
              caches.open(CACHE_NAME).then(function (cache) {
                cache.put(request, networkResponse);
              });
            }
          }).catch(function () {
            // Revalidation failed quietly
          });
          return cachedResponse;
        }

        return fetch(request).then(function (networkResponse) {
          if (networkResponse && networkResponse.status === 200) {
            var responseClone = networkResponse.clone();
            caches.open(CACHE_NAME).then(function (cache) {
              cache.put(request, responseClone);
            });
          }
          return networkResponse;
        });
      })
    );
  }
});
