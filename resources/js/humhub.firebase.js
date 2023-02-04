humhub.module('firebase', function (module, require, $) {
    var messaging;

    var init = function () {
        if (!firebase.apps.length) {
            firebase.initializeApp({messagingSenderId: module.config.senderId});
            this.messaging = firebase.messaging();

            this.messaging.onMessage(function (payload) {
                console.log("FCM Notification received: ", payload);
            });

            // Callback fired if Instance ID token is updated.
            this.messaging.onTokenRefresh(function () {
                this.messaging.getToken().then(function (refreshedToken) {
                    this.setTokenSentToServer('');
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
                    that.setTokenSentToServer('');
                }
            }).catch(function (err) {
                console.log('An error occurred while retrieving token. ', err);
                that.setTokenSentToServer('');
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
        var that = this;
        if (!this.isTokenSentToServer(token)) {
            console.log('Sending token to server...');
            $.ajax({
                method: "POST",
                url: module.config.tokenUpdateUrl,
                data: {token: token},
                success: function (data) {
                    that.setTokenSentToServer(token);
                }
            });
        } else {
            console.log('Token already sent to server so won\'t send it again unless it changes');
        }
    };

    var setTokenSentToServer = function (token) {
        window.localStorage.setItem('sentFcmToken', token);
    };

    var isTokenSentToServer = function (token) {
        return (window.localStorage.getItem('sentFcmToken') === token);
    };

    module.export({
        init: init,
        isTokenSentToServer: isTokenSentToServer,
        setTokenSentToServer: setTokenSentToServer,
        sendTokenToServer: sendTokenToServer,
        afterServiceWorkerRegistration: afterServiceWorkerRegistration,

    });
});

function afterServiceWorkerRegistration(registration) {
    humhub.modules.firebase.afterServiceWorkerRegistration(registration);
}