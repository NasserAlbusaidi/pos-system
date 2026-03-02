const CACHE_PREFIX = 'bite-static-';
const CACHE_VERSION = 'v2';
const STATIC_CACHE = `${CACHE_PREFIX}${CACHE_VERSION}`;
const LEGACY_CACHES = ['bite-pos-cache-v1'];
const OFFLINE_URL = '/offline';
const PRECACHE_URLS = [OFFLINE_URL];

const STATIC_DESTINATIONS = new Set([
  'style',
  'script',
  'image',
  'font',
  'manifest',
  'worker',
]);

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(STATIC_CACHE).then((cache) => cache.addAll(PRECACHE_URLS))
  );

  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(
      keys.map((key) => {
        const isLegacy = LEGACY_CACHES.includes(key);
        const isOldStatic = key.startsWith(CACHE_PREFIX) && key !== STATIC_CACHE;

        if (isLegacy || isOldStatic) {
          return caches.delete(key);
        }

        return null;
      })
    ))
  );

  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const { request } = event;
  if (request.method !== 'GET') {
    return;
  }

  if (request.mode === 'navigate') {
    event.respondWith(handleNavigationRequest(request));
    return;
  }

  if (!isStaticAssetRequest(request)) {
    return;
  }

  event.respondWith(handleStaticAssetRequest(request, event));
});

async function handleNavigationRequest(request) {
  try {
    return await fetch(request);
  } catch {
    const cache = await caches.open(STATIC_CACHE);
    return (await cache.match(OFFLINE_URL)) || Response.error();
  }
}

async function handleStaticAssetRequest(request, event) {
  const cache = await caches.open(STATIC_CACHE);
  const cached = await cache.match(request);

  if (cached) {
    event.waitUntil(refreshStaticCache(request));
    return cached;
  }

  try {
    const response = await fetch(request);
    return await cacheStaticResponse(request, response);
  } catch {
    return cached || Response.error();
  }
}

function isStaticAssetRequest(request) {
  let url;

  try {
    url = new URL(request.url);
  } catch {
    return false;
  }

  if (url.origin !== self.location.origin) {
    return false;
  }

  if (url.pathname.startsWith('/build/')) {
    return true;
  }

  if (url.pathname === '/favicon.ico' || url.pathname === '/manifest.webmanifest') {
    return true;
  }

  if (STATIC_DESTINATIONS.has(request.destination)) {
    return true;
  }

  return /\.(?:css|js|mjs|png|jpe?g|gif|svg|ico|webp|woff2?|ttf|otf)$/i.test(url.pathname);
}

async function refreshStaticCache(request) {
  try {
    const response = await fetch(request);
    await cacheStaticResponse(request, response);
  } catch {
    // Ignore refresh failures and serve existing cache entry.
  }
}

async function cacheStaticResponse(request, response) {
  if (!response || response.status !== 200 || response.type === 'opaque') {
    return response;
  }

  const cacheControl = response.headers.get('Cache-Control') || '';
  if (cacheControl.includes('no-store') || cacheControl.includes('private')) {
    return response;
  }

  const cache = await caches.open(STATIC_CACHE);
  await cache.put(request, response.clone());

  return response;
}
