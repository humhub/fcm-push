/**
 * Service worker addon for FCM push notifications, appended to the PWA service
 * worker by ServiceWorkerService after humhub.firebase.store.js (fcmPushStore)
 * and before the Firebase importScripts, so the notificationclick handler below
 * is called before Firebase's own handler.
 */

// Activate updated service workers immediately instead of waiting for all
// app windows to close - iOS PWAs may otherwise keep an old SW for a long time.
self.addEventListener('install', function () {
    self.skipWaiting();
});
self.addEventListener('activate', function (event) {
    event.waitUntil(self.clients.claim());
});

// Pending notification URLs, shared with the page via IndexedDB.
// Workaround for an iOS bug (iOS 17/18): when the PWA is already running,
// tapping a notification only brings the app to the foreground - the
// notificationclick event is never dispatched. So the URL of every
// notification we display is recorded here at push time, and the page
// resolves it when the app becomes visible again (see humhub.firebase.js).
function fcmReadPending() {
    return fcmPushStore.get('pending-urls').then(function (list) {
        return list || [];
    }).catch(function () { return []; });
}
function fcmWritePending(list) {
    return fcmPushStore.set('pending-urls', list).catch(function () {});
}

self.addEventListener('push', function (event) {
    let payload = null;
    try {
        payload = event.data ? event.data.json() : null;
    } catch (e) {
    }
    const url = payload && ((payload.fcmOptions && payload.fcmOptions.link) ||
        (payload.fcm_options && payload.fcm_options.link) ||
        (payload.data && payload.data.url));
    if (!url || !payload?.notification) {
        return;
    }
    event.waitUntil(
        self.clients.matchAll({type: 'window', includeUncontrolled: true}).then(function (clientList) {
            // Mirrors the Firebase SDK check: with a visible client the push is
            // forwarded to the page and no notification is shown - nothing pending.
            if (clientList.some(function (c) { return c.visibilityState === 'visible'; })) {
                return;
            }
            return fcmReadPending().then(function (list) {
                list.push({url: url, ts: Date.now()});
                return fcmWritePending(list.slice(-10));
            });
        })
    );
});

// Handle notification clicks ourselves, before Firebase's own handler, which
// only focuses an already-open window without navigating it (it delegates
// navigation to a postMessage the suspended iOS PWA page never receives).
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
        // The click is handled here, so drop the URL from the pending list
        // (which only exists for iOS, where this event does not fire).
        fcmReadPending().then(function (list) {
            return fcmWritePending(list.filter(function (p) { return p.url !== url; }));
        }).then(function () {
            return self.clients.matchAll({type: 'window', includeUncontrolled: true});
        }).then(function (clientList) {
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
