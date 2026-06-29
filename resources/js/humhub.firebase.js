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
        if (Notification.permission === 'granted' && !this.getTokenLocalStore() && navigator.serviceWorker) {
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

    const requestNotificationPermission = function (registration) {
        const that = this;

        // Guard: skip if another registration call is already in flight, or if a valid
        // token is already cached in localStorage (avoids producing a second token when
        // both init() and the PWA SW callback invoke this function on the same page load).
        if (_tokenRegistrationPending || this.getTokenLocalStore()) {
            return;
        }
        _tokenRegistrationPending = true;

        // Request for permission
        Notification.requestPermission().then(function (permission) {
            if (permission !== 'granted') {
                module.log.info('Notification permission is not granted.');
                _tokenRegistrationPending = false;
                return;
            }
            registerToken.call(that, registration).catch(function () {
                // Errors are already logged inside registerToken(); nothing else
                // to do here since this path has no user-facing UI to report to.
            });
        }).catch(function (err) {
            _tokenRegistrationPending = false;
            module.log.info('Could not get Push Notification permission!', err);
        });
    };

    // iOS-only path: WebKit/iOS silently ignores Notification.requestPermission()
    // unless it's called synchronously from within a user-gesture handler (tap/click).
    // requestNotificationPermission() above is always invoked from a Promise callback
    // (serviceWorker.ready / SW registration callback), which works fine on desktop
    // browsers but never shows the permission dialog on iOS.
    // This handler is meant to be bound directly to a button's click event, so
    // Notification.requestPermission() is the very first call made - i.e. still
    // inside the tap's user-activation context - before any async work happens.
    // Token handling itself is shared via registerToken().
    const enableNotificationsButtonHandler = function (evt) {
        const that = this;
        const $trigger = evt.$trigger;

        // Same guard as requestNotificationPermission(): avoid a redundant call
        // if a valid token is already cached.
        if (_tokenRegistrationPending || this.getTokenLocalStore()) {
            return;
        }
        _tokenRegistrationPending = true;

        // Request for permission - called synchronously from the click handler, so
        // iOS/WebKit recognizes this as a user-gesture-driven call.
        Notification.requestPermission().then(function (permission) {
            if (permission !== 'granted') {
                module.log.error('Could not enable notifications: notification permission is not granted.', true);
                _tokenRegistrationPending = false;
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
                    $trigger.addClass('d-none'); // hide the button
                }).catch(function (err) {
                    module.log.error('Could not enable notifications: ' + err.message, true);
                });
            });
        }).catch(function (err) {
            _tokenRegistrationPending = false;
            module.log.error('Could not enable notifications: ' + err.message, true);
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

// Used by LayoutHeader::registerServiceWorker()
function afterServiceWorkerRegistration(registration) {
    humhub.modules.firebase.requestNotificationPermission(registration);
}
