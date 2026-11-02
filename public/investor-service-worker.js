/*
 * The investor portal is installable, but authenticated pages and private
 * investor documents must never be cached by this worker. Leaving fetch
 * requests on the network also prevents stale login/CSRF pages.
 */
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));
