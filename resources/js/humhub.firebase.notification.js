/**
 * Opens the URL of a tapped push notification on platforms where the click
 * could not be handled in the service worker.
 *
 * Workaround for an iOS bug (iOS 17/18): when the PWA is already running,
 * tapping a notification only brings the app to the foreground - the service
 * worker never gets a notificationclick event. The service worker records the
 * URL of every displayed notification in IndexedDB at push time (see
 * humhub.firebase.worker.js). When the app becomes visible again, any recorded
 * notification that is no longer displayed was tapped (or dismissed), so we
 * navigate to the most recent one. On other platforms the service worker
 * handles the click itself and removes the entry beforehand.
 */
humhub.module('firebase.notification', function (module, require, $) {
    const PENDING_MAX_AGE_MS = 30 * 60 * 1000;
    // Minimum age before a poll-resolved entry may navigate: covers the gap between
    // the pending entry being written and the notification appearing in
    // getNotifications() - without it a fresh entry would navigate on arrival.
    const PENDING_MIN_AGE_MS = 5 * 1000;
    let _pendingCheckRunning = false;
    // URLs of notifications this page has observed via getNotifications(). A URL
    // that was seen displayed and then disappeared was just tapped or dismissed.
    const _seenDisplayedUrls = {};

    const checkPendingNotificationUrls = function (fromPoll) {
        if (!window.indexedDB || !navigator.serviceWorker || _pendingCheckRunning) {
            return;
        }
        _pendingCheckRunning = true;
        Promise.all([
            fcmPushStore.get('pending-urls').then((list) => list || []),
            navigator.serviceWorker.ready.then((reg) => reg.getNotifications()),
            fcmPushStore.get('notifications-observable').catch(() => false),
        ]).then(function ([pending, notifications, observable]) {
            if (!pending.length) {
                return;
            }
            const displayedUrls = notifications.map(function (n) {
                const fcmMsg = n.data && n.data.FCM_MSG;
                return fcmMsg && ((fcmMsg.fcmOptions && fcmMsg.fcmOptions.link) || (fcmMsg.data && fcmMsg.data.url));
            });
            displayedUrls.forEach(function (u) {
                if (u) {
                    _seenDisplayedUrls[u] = true;
                }
            });
            // Once one of our notifications has ever been observed as displayed, the
            // device has proven that getNotifications() works: remember it, so that
            // "no longer displayed" can be trusted to mean tapped/dismissed.
            if (!observable && displayedUrls.some((u) => u)) {
                observable = true;
                fcmPushStore.set('notifications-observable', true).catch(() => null);
            }
            const stillDisplayed = pending.filter((p) => displayedUrls.indexOf(p.url) !== -1);
            let resolved = pending.filter((p) => displayedUrls.indexOf(p.url) === -1);
            if (fromPoll) {
                // No user-driven event brought us here (app in foreground, where a
                // notification tap fires no event at all), so only navigate for
                // notifications that demonstrably just disappeared: either this page
                // saw them displayed, or the device is known to report displayed
                // notifications and the entry is old enough to rule out the
                // just-arrived race. If getNotifications() does not work on the
                // device, the poll never navigates - safe by construction.
                resolved = resolved.filter((p) => _seenDisplayedUrls[p.url] ||
                    (observable && Date.now() - p.ts > PENDING_MIN_AGE_MS));
            }
            if (!resolved.length) {
                return;
            }
            return fcmPushStore.set('pending-urls', stillDisplayed)
                .then(function () {
                    const fresh = resolved.filter((p) => Date.now() - p.ts < PENDING_MAX_AGE_MS);
                    if (fresh.length) {
                        const url = fresh[fresh.length - 1].url;
                        if (url !== window.location.href) {
                            window.location.assign(url);
                        }
                    }
                });
        }).catch(function (e) {
            module.log.info('Pending notification URL check failed.', e);
        }).then(function () {
            _pendingCheckRunning = false;
        });
    };

    const init = function () {
        checkPendingNotificationUrls();
        setInterval(function () {
            if (!document.hidden) {
                checkPendingNotificationUrls(true);
            }
        }, 3000);
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                checkPendingNotificationUrls();
            }
        });
        window.addEventListener('focus', function () {
            checkPendingNotificationUrls();
        });
    };

    module.export({
        init,
    });
});
