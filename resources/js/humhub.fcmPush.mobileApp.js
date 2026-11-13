/**
 * Keeps the app icon badge of the HumHub mobile app in sync while a page is open.
 *
 * The unread count is sent to the app with every page render (Events::syncMobileAppBadge()). This
 * module sends it again when the count changes on the page without navigation, e.g. when the
 * notification dropdown marks the notifications as seen or a conversation is read in the messenger,
 * so the badge is already correct when the user leaves the app.
 *
 * The count is requested from the server instead of being summed up here, because other modules can
 * add their own counts to it (MessagingService::EVENT_NOTIFICATION_COUNT).
 */
humhub.module('fcmPush.mobileApp', function (module, require, $) {
    const client = require('client');
    const event = require('event');

    // Several counts often change at once, e.g. of notifications and conversations
    const SYNC_DELAY = 500;

    let syncTimeout = null;
    let mailCount = null;

    const init = function () {
        if (!window.flutterChannel) {
            return;
        }

        // Never bind the handlers twice
        event.off('.fcmPushMobileApp');

        // Triggered when the notification count has changed
        event.on('humhub:notification:updateCount.fcmPushMobileApp', sync);

        // Triggered by the mail module on every update of the conversation count, also if unchanged
        event.on('humhub:modules:notification:UpdateTitleNotificationCount.fcmPushMobileApp', function () {
            const count = getMailCount();
            if (mailCount !== null && count !== mailCount) {
                sync();
            }
            mailCount = count;
        });
    };

    const getMailCount = function () {
        const mail = humhub.modules.mail && humhub.modules.mail.notification;
        return (mail && mail.getNewMessageCount) ? (parseInt(mail.getNewMessageCount()) || 0) : 0;
    };

    const sync = function () {
        clearTimeout(syncTimeout);
        syncTimeout = setTimeout(function () {
            client.get(module.config.notificationCountUrl).then(function (response) {
                window.flutterChannel.postMessage(JSON.stringify({
                    type: 'updateNotificationCount',
                    count: response.count,
                }));
            }).catch(function (e) {
                module.log.error(e);
            });
        }, SYNC_DELAY);
    };

    module.export({
        init: init,
    });
});
