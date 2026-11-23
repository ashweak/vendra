/* Vendra dashboard service worker: offline page + device alerts.
   Pushes carry no data; the worker asks /app/push/latest (with the user's own session) what to show. */
const CACHE = 'vendra-shell-v1';
const OFFLINE = '/offline.html';
const SHELL = [OFFLINE, '/icons/icon-192.png'];

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(CACHE).then((c) => c.addAll(SHELL)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

// Pages always come from the network (they hold live, private data and are never cached);
// only when the network fails is the offline page shown.
self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.mode !== 'navigate' || req.method !== 'GET') return;
  event.respondWith(fetch(req).catch(() => caches.match(OFFLINE)));
});

self.addEventListener('push', (event) => {
  event.waitUntil((async () => {
    let n = { title: 'Vendra', body: 'You have new activity.', url: '/app', tag: 'vendra' };
    try {
      const res = await fetch('/app/push/latest', { credentials: 'same-origin', headers: { Accept: 'application/json' }, cache: 'no-store' });
      if (res.ok && (res.headers.get('content-type') || '').includes('application/json')) n = await res.json();
    } catch (e) { /* offline or signed out: show the generic alert */ }
    await self.registration.showNotification(n.title, {
      body: n.body, tag: n.tag, renotify: true, icon: '/icons/icon-192.png', badge: '/icons/icon-192.png', data: { url: n.url },
    });
  })());
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const target = new URL((event.notification.data && event.notification.data.url) || '/app', self.location.origin);
  if (target.origin !== self.location.origin) return;
  event.waitUntil((async () => {
    const wins = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
    for (const w of wins) {
      if (new URL(w.url).pathname.startsWith('/app') && 'navigate' in w) { await w.focus(); return w.navigate(target.href); }
    }
    return self.clients.openWindow(target.href);
  })());
});
