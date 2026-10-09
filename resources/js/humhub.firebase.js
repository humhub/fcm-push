humhub.module('firebase', function (module, require, $) {
    // Prevents concurrent token registration when both init() and the PWA service
    // worker callback trigger requestNotificationPermission() on the same page load.
    let _tokenRegistrationPending = false;

    const init = function () {
        const that = this;

        if (!firebase.apps.length) {
            firebase.initializeApp({
                messagingSenderId: this.senderId(),
                projectId: module.config.projectId,
                apiKey: module.config.apiKey,
                appId: module.config.appId,
            });
            this.messaging = firebase.messaging();
        }

        this.messaging.onMessage((payload) => {
            console.log('Suppressed push notification. App has already focus.', payload);
        });

        // If the user already granted notification permission but has no token cached
        // (e.g. they enabled it in browser settings after logging in, or the 24-hour
        // localStorage window expired), trigger registration now via the active service
        // worker instead of waiting for the PWA SW registration callback.
        // requestNotificationPermission() is used as the single code path so that the
        // _tokenRegistrationPending flag prevents a race with the PWA SW callback, which
        // avoids generating two tokens when both callers fire on the same page load.
        if (typeof Notification !== 'undefined' && Notification.permission === 'granted' && !this.getTokenLocalStore() && navigator.serviceWorker) {
            navigator.serviceWorker.ready.then(function (registration) {
                that.requestNotificationPermission(registration);
            });
        }
    };

    // Private helper shared by requestNotificationPermission() and
    // enableNotificationsButtonHandler(): once permission is known to be 'granted'
    // and we have a service worker registration, fetch the FCM token and either
    // send it to the server or clean up the local store on failure.
    // Returns a Promise that resolves once the token is fetched AND sent to the
    // server, or rejects with an Error if either step fails - this lets callers
    // (e.g. the button handler) report success/error to the user.
    // Not exported - same private-state pattern as _tokenRegistrationPending above.
    const registerToken = function (registration) {
        const that = this;

        this.messaging.swRegistration = registration;

        return that.messaging.getToken({
            vapidKey: module.config.vapidKey,
            serviceWorkerRegistration: registration,
        }).then(function (currentToken) {
            _tokenRegistrationPending = false;
            if (currentToken) {
                return that.sendTokenToServer(currentToken);
            }
            module.log.info('No Instance ID token available. Request permission to generate one.');
            that.deleteTokenLocalStore();
            throw new Error('No Instance ID token available.');
        }).catch(function (err) {
            _tokenRegistrationPending = false;
            module.log.error('An error occurred while retrieving token. ', err);
            that.deleteTokenLocalStore();
            throw err;
        });
    };

    // Page-load path (init() and the PWA SW registration callback): silently fetch or
    // refresh the FCM token when the user has ALREADY granted notification permission.
    // This function must never call Notification.requestPermission(): it runs on every
    // page load without a user gesture, so prompting here would ask users who have not
    // granted (or have blocked) notifications again and again on every page load. That
    // pattern is flagged as "excessive notification requests" by browser security
    // extensions (e.g. Malwarebytes Browser Guard) and is also ignored by Firefox and
    // Safari/iOS, which require a user gesture. The only place that may prompt is
    // enableNotificationsButtonHandler() below, which runs from a click.
    const requestNotificationPermission = function (registration) {
        // Guard: skip if another registration call is already in flight, or if a valid
        // token is already cached in localStorage (avoids producing a second token when
        // both init() and the PWA SW callback invoke this function on the same page load).
        if (_tokenRegistrationPending || this.getTokenLocalStore()) {
            return;
        }

        if (typeof Notification === 'undefined') {
            module.log.info('Notification API is not available in this context.');
            return;
        }

        // Guard: never prompt on page load - only proceed when permission already exists.
        if (Notification.permission !== 'granted') {
            module.log.info('Notification permission is not granted.');
            return;
        }

        _tokenRegistrationPending = true;
        registerToken.call(this, registration).catch(function () {
            // Errors are already logged inside registerToken(); nothing else
            // to do here since this path has no user-facing UI to report to.
        });
    };

    // Containers the "Enable notifications" button can live in (see the widgets
    // EnableNotificationsBanner and RegisterDeviceTokenButton).
    const BANNER_SELECTOR = '#fcm-push-enable-notifications-banner';
    const SETTINGS_SELECTOR = '[data-fcm-push-settings]';

    // "No thanks" in the banner hides it for this many days (per browser, in localStorage).
    const BANNER_DISMISS_KEY = 'fcmPushEnableNotificationsBannerDismissedUntil';
    const BANNER_DISMISS_DAYS = 30;

    // The only path that prompts for permission. WebKit/iOS silently ignores
    // Notification.requestPermission() unless it's called synchronously from within a
    // user-gesture handler (tap/click), and requestNotificationPermission() above
    // intentionally never prompts (see its comment).
    // This handler is meant to be bound directly to a button's click event, so
    // Notification.requestPermission() is the very first call made - i.e. still
    // inside the tap's user-activation context - before any async work happens.
    // Token handling itself is shared via registerToken().
    const enableNotificationsButtonHandler = function (evt) {
        const that = this;
        const $trigger = evt.$trigger;

        // Unlike requestNotificationPermission(), only guard against concurrent
        // calls here - NOT against a cached token. This handler runs on an
        // explicit user action, and the localStorage cache may be stale (e.g.
        // the token was deleted server-side): re-registering is idempotent, so
        // honor the user's intent and re-send the token to the server.
        if (_tokenRegistrationPending) {
            return;
        }
        _tokenRegistrationPending = true;
        this.deleteTokenLocalStore();

        // Request for permission - called synchronously from the click handler, so
        // iOS/WebKit recognizes this as a user-gesture-driven call.
        if (typeof Notification === 'undefined') {
            _tokenRegistrationPending = false;
            module.log.error('Could not enable notifications: Notification API is not available in this context.', true);
            return;
        }
        Notification.requestPermission().then(function (permission) {
            if (permission !== 'granted') {
                // 'denied' (the user clicked "Block", or the browser blocks the site) or
                // 'default' (the prompt was closed without a choice): this is the user's
                // decision, not an error, so no error toast. The banner goes away, and the
                // settings page switches to the matching state (unblock steps or button).
                _tokenRegistrationPending = false;
                $trigger.closest(BANNER_SELECTOR).addClass('d-none');
                $trigger.closest(SETTINGS_SELECTOR).each(function () {
                    renderNotificationSettings.call(that, $(this));
                });
                return;
            }
            if (!navigator.serviceWorker) {
                module.log.error('Could not enable notifications: service worker is not available.', true);
                _tokenRegistrationPending = false;
                return;
            }
            navigator.serviceWorker.ready.then(function (registration) {
                registerToken.call(that, registration).then(function () {
                    module.log.success('success.saved', true);
                    $trigger.closest(BANNER_SELECTOR + ', ' + SETTINGS_SELECTOR).addClass('d-none');
                }).catch(function (err) {
                    module.log.error('Could not enable notifications: ' + err.message, true);
                });
            });
        }).catch(function (err) {
            _tokenRegistrationPending = false;
            module.log.error('Could not enable notifications: ' + err.message, true);
        });
    };

    // Shows the enable-notifications banner (see EnableNotificationsBanner widget) only while
    // the permission is undecided ('default') and the user has not clicked "No thanks"
    // recently. Nothing is shown when notifications are blocked: the user chose that, and
    // the notification settings page explains how to lift the block.
    const initEnableNotificationsBanner = function (selector) {
        if (typeof Notification === 'undefined' || Notification.permission !== 'default') {
            return;
        }
        try {
            if (parseInt(window.localStorage.getItem(BANNER_DISMISS_KEY), 10) > Date.now()) {
                return;
            }
        } catch (e) {
            // localStorage unavailable (e.g. blocked site data): show the banner.
        }
        $(selector).removeClass('d-none');
    };

    // "No thanks" button of the banner.
    const dismissEnableNotificationsBanner = function (evt) {
        try {
            window.localStorage.setItem(BANNER_DISMISS_KEY, String(Date.now() + BANNER_DISMISS_DAYS * 24 * 60 * 60 * 1000));
        } catch (e) {
            // localStorage unavailable: the banner still closes for this login.
        }
        evt.$trigger.closest(BANNER_SELECTOR).addClass('d-none');
    };

    // Browser family for the "notifications are blocked" steps of the settings page.
    const detectBrowser = function () {
        const ua = navigator.userAgent;
        if (/iPhone|iPad|iPod/i.test(ua)) {
            return 'ios';
        }
        if (/Firefox\//i.test(ua)) {
            return 'firefox';
        }
        if (/Safari\//i.test(ua) && !/Chrome\/|Chromium\/|CriOS\/|Edg\//i.test(ua)) {
            return 'safari';
        }
        return 'chromium';
    };

    // Notification settings page (see RegisterDeviceTokenButton widget): shows the part that
    // matches the current state of this browser:
    // - 'enable': permission undecided, or granted without a token registered for this device
    //   (only this device's localStorage token tells, and it must still exist server-side)
    // - 'denied': blocked, with the steps to allow it again for the detected browser
    // - 'install': iOS outside of an installed PWA, where the Notification API is missing
    // Nothing is shown when this device is registered already.
    const renderNotificationSettings = function ($container) {
        let state = null;
        if (typeof Notification === 'undefined') {
            state = 'install';
        } else if (Notification.permission === 'denied') {
            state = 'denied';
        } else {
            const localToken = this.getTokenLocalStore();
            const serverTokens = $container.data('fcmPushServerTokens') || [];
            if (Notification.permission === 'default' || !localToken || !serverTokens.includes(localToken)) {
                state = 'enable';
            }
        }
        const browser = $container.is('[data-fcm-push-ios]') ? 'ios' : detectBrowser();

        $container.find('[data-fcm-push-state]').addClass('d-none');
        if (state) {
            $container.find('[data-fcm-push-state="' + state + '"]').removeClass('d-none');
        }
        $container.find('[data-fcm-push-browser]').addClass('d-none');
        $container.find('[data-fcm-push-browser="' + browser + '"]').removeClass('d-none');
    };

    const initNotificationSettings = function (selector) {
        const that = this;
        $(selector).each(function () {
            renderNotificationSettings.call(that, $(this));
        });
    };

    // Send the Instance ID token your application server, so that it can:
    // - send messages back to this app
    // - subscribe/unsubscribe the token from topics
    // Returns a Promise resolving on a successful POST, rejecting on failure -
    // lets registerToken() (and its callers) know whether saving actually worked.
    const sendTokenToServer = function (token) {
        const that = this;
        if (that.isTokenSentToServer(token)) {
            return Promise.resolve(token);
        }
        module.log.info("Send FCM Push Token to Server");
        return new Promise(function (resolve, reject) {
            $.ajax({
                method: "POST",
                url: that.tokenUpdateUrl(),
                data: {token: token},
                success: function (data) {
                    that.setTokenLocalStore(token);
                    resolve(data);
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    reject(new Error(errorThrown || textStatus));
                },
            });
        });
    };

    const deleteTokenToServer = function (token) {
        const that = this;
        if (that.isTokenSentToServer(token)) {
            module.log.info("Delete FCM Push Token to Server");
            $.ajax({
                method: "POST",
                url: that.tokenDeleteUrl(),
                data: {token: token},
                success: function (data) {
                    that.deleteTokenLocalStore();
                }
            });
        }
    };

    const isTokenSentToServer = function (token) {
        return (this.getTokenLocalStore() === token);
    };

    const deleteTokenLocalStore = function () {
        window.localStorage.removeItem('fcmPushToken_' + this.senderId());
    };

    const setTokenLocalStore = function (token) {
        const item = {
            value: token,
            expiry: (Date.now() / 1000) + (24 * 60 * 60),
        };
        window.localStorage.setItem('fcmPushToken_' + this.senderId(), JSON.stringify(item));
    };

    const getTokenLocalStore = function () {
        const itemStr = window.localStorage.getItem('fcmPushToken_' + this.senderId());

        // if the item doesn't exist, return null
        if (!itemStr) {
            return null;
        }
        const item = JSON.parse(itemStr);
        const now = (Date.now() / 1000);
        if (now > item.expiry) {
            this.deleteTokenLocalStore();
            return null;
        }
        return item.value;
    };

    const unregisterNotification = function () {
        const token = this.getTokenLocalStore();
        if (token) {
            this.deleteTokenToServer(token);
        }
    };

    const tokenUpdateUrl = function () {
        return module.config.tokenUpdateUrl;
    };

    const tokenDeleteUrl = function () {
        return module.config.tokenDeleteUrl;
    };

    const senderId = function () {
        return module.config.senderId;
    };

    module.export({
        init,
        isTokenSentToServer,
        sendTokenToServer,
        deleteTokenToServer,
        requestNotificationPermission,
        enableNotificationsButtonHandler,
        initEnableNotificationsBanner,
        dismissEnableNotificationsBanner,
        initNotificationSettings,
        unregisterNotification,

        // Config Vars
        senderId,
        tokenUpdateUrl,
        tokenDeleteUrl,

        // LocalStore Helper
        setTokenLocalStore,
        getTokenLocalStore,
        deleteTokenLocalStore,
    });
});

// Used by LayoutHeader::registerServiceWorker() on every page load.
// Only fetches the token silently if permission was already granted - never prompts.
function afterServiceWorkerRegistration(registration) {
    humhub.modules.firebase.requestNotificationPermission(registration);
}
