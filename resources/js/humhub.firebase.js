humhub.module('firebase', function (module, require, $) {
    const init = function () {
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
    };

    const afterServiceWorkerRegistration = function (registration) {
        const that = this;

        this.messaging.swRegistration = registration;

        // Request for permission
        Notification.requestPermission().then(function (permission) {
            if (permission !== 'granted') {
                module.log.info('Notification permission is not granted.');
                return;
            }

            that.messaging.getToken({
                vapidKey: module.config.vapidKey,
                serviceWorkerRegistration: registration,
            }).then(function (currentToken) {
                if (currentToken) {
                    that.sendTokenToServer(currentToken);
                } else {
                    module.log.info('No Instance ID token available. Request permission to generate one.');
                    that.deleteTokenLocalStore();
                }
            }).catch(function (err) {
                module.log.error('An error occurred while retrieving token. ', err);
                that.deleteTokenLocalStore();
            });
        }).catch(function (err) {
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

    const tokenUpdateUrl = function () {
        return module.config.tokenUpdateUrl;
    };

    const senderId = function () {
        return module.config.senderId;
    };

    module.export({
        init,

        isTokenSentToServer,
        sendTokenToServer,
        afterServiceWorkerRegistration,

        // Config Vars
        senderId,
        tokenUpdateUrl,

        // LocalStore Helper
        setTokenLocalStore,
        getTokenLocalStore,
        deleteTokenLocalStore,
    });
});

function afterServiceWorkerRegistration(registration) {
    humhub.modules.firebase.afterServiceWorkerRegistration(registration);
}
