humhub.module('firebase', function (module, require, $) {
    var messaging;

    var init = function () {
        if (!firebase.apps.length) {
            firebase.initializeApp({messagingSenderId: this.senderId()});
            this.messaging = firebase.messaging();

            this.messaging.onMessage(function (payload) {
                module.log.info("Received FCM Push Notification", payload);
            });

            // Callback fired if Instance ID token is updated.
            this.messaging.onTokenRefresh(function () {
                this.messaging.getToken().then(function (refreshedToken) {
                    this.deleteTokenLocalStore();
                    this.sendTokenToServer(refreshedToken);
                }).catch(function (err) {
                    console.log('Unable to retrieve refreshed token ', err);
                });
            });
        }
    };

    var afterServiceWorkerRegistration = function (registration) {
        //console.log("After Service Worker Registration");
        //console.log(registration);

        var that = this;

        this.messaging.useServiceWorker(registration);

        // Request for permission
        this.messaging.requestPermission().then(function () {
            //console.log('Notification permission granted.');

            that.messaging.getToken().then(function (currentToken) {
                if (currentToken) {
                    //console.log('Token: ' + currentToken);
                    that.sendTokenToServer(currentToken);
                } else {
                    console.log('No Instance ID token available. Request permission to generate one.');
                    that.deleteTokenLocalStore();
                }
            }).catch(function (err) {
                console.log('An error occurred while retrieving token. ', err);
                that.deleteTokenLocalStore();
            });
        }).catch(function (err) {
            // e.g. Igonito Mode
            console.log('Unable to get permission to notify.', err);
        });
    };

    // Send the Instance ID token your application server, so that it can:
    // - send messages back to this app
    // - subscribe/unsubscribe the token from topics
    var sendTokenToServer = function (token) {
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
        } else {
            //console.log('Token already sent to server so won\'t send it again unless it changes');
        }
    };

    var isTokenSentToServer = function (token) {
        return (this.getTokenLocalStore() === token);
    };

    var deleteTokenLocalStore = function () {
        window.localStorage.removeItem('fcmPushToken_' + this.senderId())
    };

    var setTokenLocalStore = function (token) {
        const item = {
            value: token,
            expiry: (Date.now() / 1000) + (24 * 60 * 60),
        };
        window.localStorage.setItem('fcmPushToken_' + this.senderId(), JSON.stringify(item))
    };

    var getTokenLocalStore = function () {
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

    var tokenUpdateUrl = function () {
        return module.config.tokenUpdateUrl;
    };

    var senderId = function () {
        return module.config.senderId;
    };

    module.export({
        init: init,

        isTokenSentToServer: isTokenSentToServer,
        sendTokenToServer: sendTokenToServer,
        afterServiceWorkerRegistration: afterServiceWorkerRegistration,

        // Config Vars
        senderId: senderId,
        tokenUpdateUrl: tokenUpdateUrl,

        // LocalStore Helper
        setTokenLocalStore: setTokenLocalStore,
        getTokenLocalStore: getTokenLocalStore,
        deleteTokenLocalStore: deleteTokenLocalStore,
    });
});

function afterServiceWorkerRegistration(registration) {
    humhub.modules.firebase.afterServiceWorkerRegistration(registration);
}