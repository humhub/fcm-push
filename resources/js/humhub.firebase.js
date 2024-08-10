/**
 * @fileoverview HumHub Firebase Module
 * This module handles Firebase Cloud Messaging (FCM) integration for HumHub.
 * It manages notification permissions, token handling, and message reception.
 * @module firebase
 */

humhub.module('firebase', function (module, require, $) {
    let messaging;

    /**
     * Initializes the Firebase module.
     * Sets up the Firebase app and messaging, and defines behavior for message reception and token refresh.
     * @function
     */
    const init = function () {
        if (!firebase.apps.length) {
            firebase.initializeApp({ messagingSenderId: this.senderId() });
            this.messaging = firebase.messaging();

            this.messaging.onMessage(function (payload) {
                module.log.info("Received FCM Push Notification", payload);
            });

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

    /**
     * Gets the content to display for notification permission status.
     * @function
     * @returns {string} The message corresponding to the current notification permission status.
     */
    const getNotificationPermissionContent = function () {
        if (!("Notification" in window)) {
            return module.text('status.not-supported');
        }
        switch (Notification.permission) {
            case "granted":
                return module.text('status.granted');
            case "denied":
                return module.text('status.denied');
            default:
                return module.text('status.default');
        }
    }

    /**
     * Adds information about push notification permissions to the UI.
     * @function
     * @param {string} permission - The notification permission status.
     * @param {boolean} [isAfterRequest=false] - Indicates if this is after a permission request.
     */
    const addPushNotificationPermissionsInfo = function (permission, isAfterRequest = false) {
        let content = getNotificationPermissionContent();
        $('#pushNotificationPermissionInfo').html(content);

        if (isAfterRequest && permission === 'granted') {
            module.afterServiceWorkerRegistration();
        }
    }

    /**
     * Handles actions after the service worker registration.
     * @function
     * @param {ServiceWorkerRegistration} registration - The service worker registration object.
     */
    const afterServiceWorkerRegistration = function (registration) {
        const that = this;

        this.messaging.useServiceWorker(registration);

        this.messaging.requestPermission().then(function () {
            addPushNotificationPermissionsInfo('granted');

            that.messaging.getToken().then(function (currentToken) {
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
            addPushNotificationPermissionsInfo(Notification.permission);
        });
    };

    /**
     * Sends the FCM token to the server.
     * @function
     * @param {string} token - The FCM token.
     */
    const sendTokenToServer = function (token) {
        const that = this;
        if (!that.isTokenSentToServer(token)) {
            module.log.info("Send FCM Push Token to Server");
            $.ajax({
                method: "POST",
                url: that.tokenUpdateUrl(),
                data: { token: token },
                success: function (data) {
                    that.setTokenLocalStore(token);
                }
            });
        } else {
            //console.log('Token already sent to server so won\'t send it again unless it changes');
        }
    };

    /**
     * Checks if the token has already been sent to the server.
     * @function
     * @param {string} token - The FCM token.
     * @returns {boolean} Whether the token has been sent to the server.
     */
    const isTokenSentToServer = function (token) {
        return (this.getTokenLocalStore() === token);
    };

    /**
     * Deletes the locally stored FCM token.
     * @function
     */
    const deleteTokenLocalStore = function () {
        window.localStorage.removeItem('fcmPushToken_' + this.senderId())
    };

    /**
     * Sets the FCM token in local storage.
     * @function
     * @param {string} token - The FCM token.
     */
    const setTokenLocalStore = function (token) {
        const item = {
            value: token,
            expiry: (Date.now() / 1000) + (24 * 60 * 60),
        };
        window.localStorage.setItem('fcmPushToken_' + this.senderId(), JSON.stringify(item))
    };

    /**
     * Gets the FCM token from local storage.
     * @function
     * @returns {string|null} The FCM token or null if it doesn't exist or is expired.
     */
    const getTokenLocalStore = function () {
        const itemStr = window.localStorage.getItem('fcmPushToken_' + this.senderId())

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

    /**
     * Gets the URL for updating the token on the server.
     * @function
     * @returns {string} The token update URL.
     */
    const tokenUpdateUrl = function () {
        return module.config.tokenUpdateUrl;
    };

    /**
     * Gets the sender ID from the module configuration.
     * @function
     * @returns {string} The sender ID.
     */
    const senderId = function () {
        return module.config.senderId;
    };

    module.export({
        init: init,
        isTokenSentToServer: isTokenSentToServer,
        sendTokenToServer: sendTokenToServer,
        afterServiceWorkerRegistration: afterServiceWorkerRegistration,
        senderId: senderId,
        tokenUpdateUrl: tokenUpdateUrl,
        setTokenLocalStore: setTokenLocalStore,
        getTokenLocalStore: getTokenLocalStore,
        deleteTokenLocalStore: deleteTokenLocalStore,
        addPushNotificationPermissionsInfo: addPushNotificationPermissionsInfo
    });
});

/**
 * Handles actions after the service worker registration (global scope).
 * @function
 * @param {ServiceWorkerRegistration} registration - The service worker registration object.
 */
function afterServiceWorkerRegistration(registration) {
    humhub.modules.firebase.afterServiceWorkerRegistration(registration);
}
