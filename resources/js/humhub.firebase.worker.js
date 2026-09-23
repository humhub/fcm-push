/**
 * Service worker addon for FCM push notifications, appended to the PWA service
 * worker by ServiceWorkerService before the Firebase importScripts, so the
 * notificationclick handler below is called before Firebase's own handler.
 */

// Activate updated service workers immediately instead of waiting for all
// app windows to close - iOS PWAs may otherwise keep an old SW for a long time.
self.addEventListener('install', function () {
    self.skipWaiting();
});
self.addEventListener('activate', function (event) {
    event.waitUntil(self.clients.claim());
});

// Handle notification clicks ourselves, before Firebase's own handler, which
// only focuses an already-open window without navigating it (it delegates
// navigation to a postMessage the page never listens for).
//
// Known limitation (iOS/WebKit bug, iOS 17/18): when the PWA is already
// running, iOS never dispatches notificationclick at all, so tapping a
// notification only brings the app to the foreground without navigating.
// This is intentionally not worked around here - no service-worker code can
// ever see that tap. See https://github.com/humhub/fcm-push/pull/99 for an
// exploration of a page-side heuristic covering that case.
self.addEventListener('notificationclick', function (event) {
    const fcmMsg = event.notification && event.notification.data && event.notification.data.FCM_MSG;
    const url = fcmMsg && ((fcmMsg.fcmOptions && fcmMsg.fcmOptions.link) || (fcmMsg.data && fcmMsg.data.url));
    if (!url) {
        return; // Not one of our notifications - leave it to Firebase's handler
    }

    // Take over completely: prevent Firebase's handler from running.
    event.stopImmediatePropagation();
    event.notification.close();

    event.waitUntil(
        self.clients.matchAll({type: 'window', includeUncontrolled: true}).then(function (clientList) {
            // matchAll() returns window clients most-recently-focused first.
            const client = clientList.find(function (c) { return 'navigate' in c; });
            if (!client) {
                return self.clients.openWindow(url);
            }
            return Promise.resolve(client.focus())
                .catch(function () { return client; })
                .then(function (c) { return (c || client).navigate(url); })
                .catch(function () { return self.clients.openWindow(url); });
        })
    );
});
