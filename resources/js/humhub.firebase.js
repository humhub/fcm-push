humhub.module('firebase', function (module, require, $) {
    // Prevents concurrent token registration when both init() and the PWA service
    // worker callback trigger afterServiceWorkerRegistration() on the same page load.
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
        // afterServiceWorkerRegistration() is used as the single code path so that the
        // _tokenRegistrationPending flag prevents a race with the PWA SW callback, which
        // avoids generating two tokens when both callers fire on the same page load.
        if (Notification.permission === 'granted' && !this.getTokenLocalStore() && navigator.serviceWorker) {
            navigator.serviceWorker.ready.then(function (registration) {
                that.afterServiceWorkerRegistration(registration);
            });
        }
    };

    const afterServiceWorkerRegistration = function (registration) {
        const that = this;

        // Guard: skip if another registration call is already in flight, or if a valid
        // token is already cached in localStorage (avoids producing a second token when
        // both init() and the PWA SW callback invoke this function on the same page load).
        if (_tokenRegistrationPending || this.getTokenLocalStore()) {
            return;
        }
        _tokenRegistrationPending = true;

        this.messaging.swRegistration = registration;

        // Request for permission
        Notification.requestPermission().then(function (permission) {
            if (permission !== 'granted') {
                module.log.info('Notification permission is not granted.');
                _tokenRegistrationPending = false;
                return;
            }

            that.messaging.getToken({
                vapidKey: module.config.vapidKey,
                serviceWorkerRegistration: registration,
            }).then(function (currentToken) {
                _tokenRegistrationPending = false;
                if (currentToken) {
                    that.sendTokenToServer(currentToken);
                } else {
                    module.log.info('No Instance ID token available. Request permission to generate one.');
                    that.deleteTokenLocalStore();
                }
            }).catch(function (err) {
                _tokenRegistrationPending = false;
                module.log.error('An error occurred while retrieving token. ', err);
                that.deleteTokenLocalStore();
            });
        }).catch(function (err) {
            _tokenRegistrationPending = false;
            module.log.info('Could not get Push Notification permission!', err);
        });
    };

    // Send the Instance ID token your application server, so that it can:
    // - send messages back to this app
    // - subscribe/unsubscribe the token from topics
    const sendTokenToServer = function (token) {
        const that = this;
        if (!that.isTokenSentToServer(token)) {
            module.log.info("Send FCM Push Token to Server");
            $.ajax({
                method: "POST",
                url: that.tokenUpdateUrl(),
                data: {token: token},
                success: function (data) {
                    that.setTokenLocalStore(token);
                }
            });
        }
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
        window.localStorage.removeItem('fcmPushToken_' + this.senderId())
    };

    const setTokenLocalStore = function (token) {
        const item = {
            value: token,
            expiry: (Date.now() / 1000) + (24 * 60 * 60),
        };
        window.localStorage.setItem('fcmPushToken_' + this.senderId(), JSON.stringify(item))
    };

    const getTokenLocalStore = function () {
        const itemStr = window.localStorage.getItem('fcmPushToken_' + this.senderId())

        // if the item doesn't exist, return null
        if (!itemStr) {
            return null
        }
        const item = JSON.parse(itemStr)
        const now = (Date.now() / 1000)
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
    }

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
        afterServiceWorkerRegistration,
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

function afterServiceWorkerRegistration(registration) {
    humhub.modules.firebase.afterServiceWorkerRegistration(registration);
}
