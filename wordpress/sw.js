/**
 * VietnamGuide Service Worker (PWA & Offline Travel Field Guide)
 *
 * Provides network-first caching for field guides and itineraries,
 * resilient precaching, bounded storage pruning, and offline fallback
 * support for travelers outside cellular coverage.
 */

'use strict';

var CACHE_NAME = 'vg-travel-handbook-v1.5.0';
var OFFLINE_URL = '/offline.html';
var MAX_CACHE_ENTRIES = 80;

var PRECACHE_ASSETS = [
  OFFLINE_URL,
  '/wp-content/themes/vietnamguide-premium/assets/css/homepage.css',
  '/wp-content/themes/vietnamguide-premium/assets/css/guide-patterns.css',
  '/wp-content/themes/vietnamguide-premium/assets/css/guide-experience.css',
  '/wp-content/themes/vietnamguide-premium/assets/css/guide-interactive.css',
  '/wp-content/themes/vietnamguide-premium/assets/js/homepage.js',
  '/wp-content/themes/vietnamguide-premium/assets/js/guide-experience.js',
  '/wp-content/themes/vietnamguide-premium/assets/js/guide-interactive.js',
  '/wp-content/themes/vietnamguide-premium/assets/images/vg-icon.svg',
  '/wp-content/themes/vietnamguide-premium/assets/images/vg-icon-192.png',
  '/wp-content/themes/vietnamguide-premium/site.webmanifest',
  '/plan/vietnam-travel-guide/',
  '/plan/vietnam-travel-cost/',
  '/plan/best-time-to-visit-vietnam/',
  '/plan/vietnam-evisa/',
  '/plan/vietnam-first-trip-planning-checklist/',
  '/plan/vietnam-airport-arrival-checklist/',
  '/itineraries/',
  '/itineraries/10-days-in-vietnam/'
];

function trimCache(cacheName, maxItems) {
  return caches.open(cacheName).then(function (cache) {
    return cache.keys().then(function (keys) {
      if (keys.length <= maxItems) {
        return;
      }
      var excess = keys.length - maxItems;
      var deletions = [];
      for (var i = 0; i < keys.length && deletions.length < excess; i++) {
        var reqUrl = new URL(keys[i].url);
        if (PRECACHE_ASSETS.indexOf(reqUrl.pathname) === -1) {
          deletions.push(cache.delete(keys[i]));
        }
      }
      return Promise.all(deletions);
    });
  }).catch(function () {});
}

self.addEventListener('install', function (event) {
  event.waitUntil(
    caches.open(CACHE_NAME).then(function (cache) {
      return Promise.allSettled(
        PRECACHE_ASSETS.map(function (assetUrl) {
          return cache.add(assetUrl);
        })
      );
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

  // Bypass admin, login, search, REST API, and dynamic preview endpoints
  if (
    url.pathname.indexOf('/wp-admin/') !== -1 ||
    url.pathname.indexOf('/wp-login.php') !== -1 ||
    url.pathname.indexOf('/wp-json/') !== -1 ||
    url.search.indexOf('preview=true') !== -1 ||
    url.searchParams.has('s')
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
            var cc = networkResponse.headers.get('cache-control') || '';
            if (cc.indexOf('no-store') === -1 && cc.indexOf('private') === -1) {
              var responseClone = networkResponse.clone();
              caches.open(CACHE_NAME).then(function (cache) {
                return cache.put(request, responseClone);
              }).then(function () {
                return trimCache(CACHE_NAME, MAX_CACHE_ENTRIES);
              });
            }
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

  // Handle static assets (CSS, JS, images, fonts, manifest) with Stale-While-Revalidate
  var isStaticAsset = (
    url.pathname.match(/\.(css|js|svg|png|jpg|jpeg|webp|woff|woff2|webmanifest)$/i) ||
    url.pathname.indexOf('/assets/') !== -1
  );

  if (isStaticAsset) {
    event.respondWith(
      caches.match(request).then(function (exactMatch) {
        if (exactMatch) return exactMatch;
        return caches.match(request, { ignoreSearch: true });
      }).then(function (cachedResponse) {
        if (cachedResponse) {
          fetch(request).then(function (networkResponse) {
            if (networkResponse && networkResponse.status === 200) {
              caches.open(CACHE_NAME).then(function (cache) {
                cache.put(request, networkResponse);
              });
            }
          }).catch(function () {});
          return cachedResponse;
        }

        return fetch(request).then(function (networkResponse) {
          if (networkResponse && networkResponse.status === 200) {
            var responseClone = networkResponse.clone();
            caches.open(CACHE_NAME).then(function (cache) {
              return cache.put(request, responseClone);
            }).then(function () {
              return trimCache(CACHE_NAME, MAX_CACHE_ENTRIES);
            });
          }
          return networkResponse;
        });
      })
    );
  }
});
